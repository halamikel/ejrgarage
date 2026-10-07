<?php
header('Content-Type: application/json');
require_once 'db_config.php';
require_once 'points_helper.php';

$userId = ejr_require_user_id();

try {
    // Only vouchers that are still usable: not used, not expired.
    $stmt = $conn->prepare("
        SELECT uv.id, uv.code, uv.expires_at, uv.created_at,
               r.title, r.description, r.reward_type
        FROM user_vouchers uv
        JOIN rewards r ON r.id = uv.reward_id
        WHERE uv.user_id = ?
          AND uv.used_at IS NULL
          AND (uv.expires_at IS NULL OR uv.expires_at > NOW())
        ORDER BY uv.created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();

    $vouchers = [];
    while ($row = $res->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $vouchers[] = $row;
    }
    echo json_encode(['status' => 'success', 'vouchers' => $vouchers]);
} catch (Throwable $e) {
    error_log('EJR get_my_vouchers: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Could not load vouchers.']);
}
