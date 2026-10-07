<?php
/**
 * EJR Garage — Points helper
 *
 * Requires tables: users.points, points_ledger (UNIQUE user_id+reason+ref_id).
 * The unique key makes every award idempotent: re-saving the same
 * "Completed" status can never credit points twice.
 */

const EJR_PTS_APPOINTMENT = 50;   // per completed appointment
const EJR_PESO_PER_POINT  = 200;  // 1 pt per ₱200 spent on parts

/**
 * Credit points once per (user, reason, ref). Returns points actually awarded (0 if duplicate).
 */
function ejr_award_points(mysqli $conn, int $userId, int $points, string $reason, int $refId): int
{
    if ($userId <= 0 || $points <= 0) return 0;

    try {
        $conn->begin_transaction();

        $ins = $conn->prepare("INSERT IGNORE INTO points_ledger (user_id, points, reason, ref_id) VALUES (?, ?, ?, ?)");
        $ins->bind_param("iisi", $userId, $points, $reason, $refId);
        $ins->execute();

        if ($ins->affected_rows < 1) {      // already awarded
            $conn->rollback();
            return 0;
        }

        $upd = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $upd->bind_param("ii", $points, $userId);
        $upd->execute();

        $conn->commit();
        return $points;
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $e2) {}
        error_log('EJR points award failed: ' . $e->getMessage());
        return 0;
    }
}

/** 50 pts when an appointment becomes Completed. */
function ejr_award_appointment_points(mysqli $conn, int $appointmentId): int
{
    $s = $conn->prepare("SELECT user_id FROM appointments WHERE id = ? LIMIT 1");
    $s->bind_param("i", $appointmentId);
    $s->execute();
    $row = $s->get_result()->fetch_assoc();
    if (!$row || empty($row['user_id'])) return 0;

    return ejr_award_points($conn, (int)$row['user_id'], EJR_PTS_APPOINTMENT, 'appointment', $appointmentId);
}

/** 1 pt per ₱200 of the order's final (post-voucher) total when a parts order is completed. */
function ejr_award_order_points(mysqli $conn, int $orderId): int
{
    $s = $conn->prepare("SELECT user_id, total_price FROM part_orders WHERE id = ? LIMIT 1");
    $s->bind_param("i", $orderId);
    $s->execute();
    $row = $s->get_result()->fetch_assoc();
    if (!$row || empty($row['user_id'])) return 0;

    $pts = (int)floor(((float)$row['total_price']) / EJR_PESO_PER_POINT);
    return ejr_award_points($conn, (int)$row['user_id'], $pts, 'parts_order', $orderId);
}

/** Small JWT helper for the customer rewards endpoints. Exits 401 on failure; returns user id. */
function ejr_require_user_id(): int
{
    require_once __DIR__ . '/../includes/php-jwt/JWT.php';
    require_once __DIR__ . '/../includes/php-jwt/Key.php';
    if (!defined('JWT_SECRET')) require_once __DIR__ . '/env.php';

    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    $auth    = $headers['authorization'] ?? '';
    if (preg_match('/bearer\s(\S+)/i', $auth, $m)) {
        try {
            $d   = \Firebase\JWT\JWT::decode($m[1], new \Firebase\JWT\Key(JWT_SECRET, 'HS256'));
            $uid = (int)($d->uid ?? $d->user_id ?? 0);
            if ($uid > 0) return $uid;
        } catch (Throwable $e) {}
    }
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Please log in again.']);
    exit;
}
