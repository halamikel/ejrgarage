<?php
session_start();
session_write_close(); // ✅ add this line
require_once '../db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || 
    ((int)$_SESSION['role_id'] !== 2 && (int)$_SESSION['user_id'] !== 21)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $current_admin_id = $_SESSION['user_id'];

    $sql = "SELECT 
                u.id, 
                u.full_name,
                u.chat_session,
                c.message AS last_message,
                (SELECT COUNT(*) FROM ejr_chat WHERE user_id = u.id AND is_read = 0 AND sender = 'user') AS unread_count
            FROM users u
            INNER JOIN ejr_chat c ON u.id = c.user_id
            WHERE c.id IN (SELECT MAX(id) FROM ejr_chat GROUP BY user_id)
            AND u.id != ?
            ORDER BY c.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $current_admin_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $users        = [];
    $total_unread = 0;
    while ($row = $result->fetch_assoc()) {
        $users[]       = $row;
        $total_unread += (int)$row['unread_count'];
    }

    echo json_encode([
        'status'       => 'success',
        'users'        => $users,
        'total_unread' => $total_unread
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>