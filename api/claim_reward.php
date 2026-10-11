<?php
header('Content-Type: application/json');
require_once 'db_config.php';
require_once 'points_helper.php';
require_once 'voucher_helper.php';

// Claimed vouchers use the reward's duration_days when available.
const EJR_DEFAULT_VOUCHER_VALID_DAYS = 30;

$userId = ejr_require_user_id();

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$rewardId = (int)($input['reward_id'] ?? 0);
if ($rewardId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing reward.']);
    exit;
}

try {
    // Make sure the voucher tables exist BEFORE the transaction (DDL would
    // implicitly commit it). Without this, claiming fails on a fresh database.
    ejr_ensure_voucher_tables($conn);
    $conn->query("
        CREATE TABLE IF NOT EXISTS user_vouchers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            reward_id INT NOT NULL,
            code VARCHAR(50) NOT NULL,
            expires_at DATETIME NULL,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_voucher_code (code),
            INDEX idx_user_vouchers_user (user_id)
        )
    ");

    $conn->begin_transaction();

    // Lock the user row so two taps can't spend the same points twice.
    $u = $conn->prepare("SELECT points FROM users WHERE id = ? FOR UPDATE");
    $u->bind_param("i", $userId);
    $u->execute();
    $user = $u->get_result()->fetch_assoc();
    if (!$user) throw new Exception('User not found.');

    $r = $conn->prepare("SELECT * FROM rewards WHERE id = ? AND is_active = 1 LIMIT 1");
    $r->bind_param("i", $rewardId);
    $r->execute();
    $reward = $r->get_result()->fetch_assoc();
    if (!$reward) throw new Exception('This reward is no longer available.');

    $cost = (int)$reward['points_required'];
    if ((int)$user['points'] < $cost) {
        throw new Exception('You need ' . ($cost - (int)$user['points']) . ' more points to claim this.');
    }

    /*
     * Resolve the reward's voucher configuration.
     *
     * Older databases may have rewards whose voucher_id is NULL/0 or points
     * to a voucher that no longer exists. In that case we derive the voucher
     * from the reward's own title/description instead of blocking the claim.
     * This keeps old reward records compatible without requiring a manual DB
     * migration for every existing reward.
     */
    $tpl = null;
    $tid = isset($reward['voucher_id']) ? (int)$reward['voucher_id'] : 0;

    if ($tid > 0) {
        $t = $conn->prepare("SELECT * FROM vouchers WHERE id = ? LIMIT 1");
        $t->bind_param("i", $tid);
        $t->execute();
        $tpl = $t->get_result()->fetch_assoc();
    }

    if (!$tpl) {
        $text = trim(($reward['title'] ?? '') . ' ' . ($reward['description'] ?? ''));
        $discountType = null;
        $discountValue = null;

        // Percentage rewards, e.g. "10% Off Parts".
        if (preg_match('/(\d+(?:\.\d+)?)\s*%/', $text, $m)) {
            $discountType = 'percentage';
            $discountValue = (float)$m[1];
        // Fixed-value rewards, e.g. "₱100 Off", "PHP 100 Discount".
        } elseif (preg_match('/(?:₱|PHP\s*)([0-9][0-9,]*(?:\.[0-9]+)?)/i', $text, $m)) {
            $discountType = 'fixed';
            $discountValue = (float)str_replace(',', '', $m[1]);
        }

        if ($discountType === null || $discountValue === null || $discountValue <= 0) {
            throw new Exception(
                'This reward has no discount amount configured. Please set a percentage (for example 10%) or fixed amount (for example ₱100) in the reward title or description.'
            );
        }

        $tpl = [
            'discount_type'   => $discountType,
            'discount_value'  => $discountValue,
            'minimum_amount'  => 0,
            'maximum_discount'=> null,
        ];
    }

    $code = 'EJR-' . strtoupper(bin2hex(random_bytes(4)));
    $validDays = isset($reward['duration_days']) && (int)$reward['duration_days'] > 0
        ? (int)$reward['duration_days']
        : EJR_DEFAULT_VOUCHER_VALID_DAYS;
    $expires = date('Y-m-d H:i:s', strtotime('+' . $validDays . ' days'));

    $v = $conn->prepare("
        INSERT INTO vouchers
            (code, description, discount_type, discount_value, minimum_amount,
             maximum_discount, usage_limit, usage_count, per_user_limit, starts_at, expires_at, is_active)
        VALUES (?, ?, ?, ?, ?, ?, 1, 0, 1, NULL, ?, 1)
    ");
    $desc = $reward['title'];
    $v->bind_param("sssddds", $code, $desc, $tpl['discount_type'], $tpl['discount_value'],
                   $tpl['minimum_amount'], $tpl['maximum_discount'], $expires);
    $v->execute();

    $ins = $conn->prepare("INSERT INTO user_vouchers (user_id, reward_id, code, expires_at) VALUES (?, ?, ?, ?)");
    $ins->bind_param("iiss", $userId, $rewardId, $code, $expires);
    $ins->execute();
    $uvId = (int)$ins->insert_id;    // unique ref for the ledger row below

    $d = $conn->prepare("UPDATE users SET points = points - ? WHERE id = ?");
    $d->bind_param("ii", $cost, $userId);
    $d->execute();

    $neg = -$cost;
    $l = $conn->prepare("INSERT INTO points_ledger (user_id, points, reason, ref_id) VALUES (?, ?, 'redeem', ?)");
    $l->bind_param("iii", $userId, $neg, $uvId);
    $l->execute();

    $bal = $conn->prepare("SELECT points FROM users WHERE id = ?");
    $bal->bind_param("i", $userId);
    $bal->execute();
    $newBalance = (int)($bal->get_result()->fetch_assoc()['points'] ?? 0);

    $conn->commit();
    echo json_encode([
        'status'     => 'success',
        'message'    => "Voucher claimed! Your code is {$code}. Valid for " . $validDays . " days.",
        'code'       => $code,
        'expires_at' => $expires,
        'points'     => $newBalance,
    ]);
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $e2) {}
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
