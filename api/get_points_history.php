<?php
/**
 * EJR Garage — Points history (customer app)
 *
 * GET get_points_history.php
 * Response: {status, balance, total_earned, total_spent, history: [
 *   {id, points, reason, label, created_at}  // points > 0 earned, < 0 spent
 * ]}  newest first, last 200 entries.
 */
header('Content-Type: application/json');
require_once 'db_config.php';
require_once 'points_helper.php';

$userId = ejr_require_user_id();

try {
    ejr_ensure_points_schema($conn);
    // Credit anything earned but never awarded, so history matches the balance.
    ejr_sync_user_points($conn, $userId);

    $stmt = $conn->prepare("
        SELECT l.id, l.points, l.reason, l.ref_id, l.created_at,
               a.service_type,
               r.title AS reward_title
        FROM points_ledger l
        LEFT JOIN appointments a  ON l.reason IN ('appointment', 'review') AND a.id = l.ref_id
        LEFT JOIN user_vouchers uv ON l.reason = 'redeem' AND uv.id = l.ref_id
        LEFT JOIN rewards r        ON r.id = uv.reward_id
        WHERE l.user_id = ?
        ORDER BY l.created_at DESC, l.id DESC
        LIMIT 200
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();

    $history = [];
    while ($row = $res->fetch_assoc()) {
        switch ($row['reason']) {
            case 'appointment':
                $label = 'Completed appointment' . ($row['service_type'] ? ' — ' . $row['service_type'] : '');
                break;
            case 'review':
                $label = 'Service review' . ($row['service_type'] ? ' — ' . $row['service_type'] : '');
                break;
            case 'parts_order':
                $label = 'Parts order #' . (int)$row['ref_id'];
                break;
            case 'redeem':
                $label = 'Redeemed: ' . ($row['reward_title'] ?: 'Reward voucher');
                break;
            default:
                $label = ucfirst(str_replace('_', ' ', (string)$row['reason']));
        }
        $history[] = [
            'id'         => (int)$row['id'],
            'points'     => (int)$row['points'],
            'reason'     => $row['reason'],
            'label'      => $label,
            'created_at' => $row['created_at'],
        ];
    }

    // Totals over the whole ledger (not just the 200 returned rows).
    $t = $conn->prepare("
        SELECT COALESCE(SUM(CASE WHEN points > 0 THEN points END), 0) AS earned,
               COALESCE(SUM(CASE WHEN points < 0 THEN -points END), 0) AS spent
        FROM points_ledger WHERE user_id = ?
    ");
    $t->bind_param("i", $userId);
    $t->execute();
    $tot = $t->get_result()->fetch_assoc();

    $b = $conn->prepare("SELECT points FROM users WHERE id = ? LIMIT 1");
    $b->bind_param("i", $userId);
    $b->execute();
    $bal = $b->get_result()->fetch_assoc();

    echo json_encode([
        'status'       => 'success',
        'balance'      => (int)($bal['points'] ?? 0),
        'total_earned' => (int)$tot['earned'],
        'total_spent'  => (int)$tot['spent'],
        'history'      => $history,
    ]);
} catch (Throwable $e) {
    error_log('EJR get_points_history: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Could not load points history.']);
}
