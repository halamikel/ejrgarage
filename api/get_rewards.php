<?php
header('Content-Type: application/json');
require_once 'db_config.php';
require_once 'points_helper.php';

ejr_require_user_id();

try {
    $res = $conn->query("
        SELECT id, title, description, reward_type, points_required, duration_days
        FROM rewards
        WHERE is_active = 1
        ORDER BY points_required ASC, id ASC
    ");
    $rewards = [];
    while ($row = $res->fetch_assoc()) {
        $row['id']              = (int)$row['id'];
        $row['points_required'] = (int)$row['points_required'];
        $rewards[] = $row;
    }
    echo json_encode(['status' => 'success', 'rewards' => $rewards]);
} catch (Throwable $e) {
    error_log('EJR get_rewards: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Could not load rewards.']);
}
