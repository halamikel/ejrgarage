<?php
session_start();
session_write_close();
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

require_once __DIR__ . '/../../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/php-jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// -- JWT Auth --
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$auth       = $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$adminId    = 0;
$isAdmin    = false;
if (preg_match('/bearer\\s(\\S+)/i', $auth, $m)) {
    try {
        $decoded = JWT::decode($m[1], new Key(JWT_SECRET, 'HS256'));
        $adminId = (int)($decoded->uid  ?? 0);
        $role    = (int)($decoded->role ?? 0);
        $isAdmin = ($role === 2 || $adminId === 21);
    } catch (Exception $e) {}
}
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Admin access required.']);
    exit;
}
$current_user_id  = $adminId;
$current_admin_id = $adminId;
$is_admin         = true;

try {
    $current_admin_id = (int)$adminId;

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