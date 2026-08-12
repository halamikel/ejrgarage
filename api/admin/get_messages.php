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

$user_id = (int)($_GET['user_id'] ?? 0);
if (!$user_id) {
    echo json_encode(['status' => 'error', 'messages' => []]);
    exit;
}

try {
    $sessStmt = $conn->prepare("SELECT chat_session FROM users WHERE id = ?");
    $sessStmt->bind_param("i", $user_id);
    $sessStmt->execute();
    $sessRow     = $sessStmt->get_result()->fetch_assoc();
    $currentMode = $sessRow['chat_session'] ?? 'bot';

    $stmt = $conn->prepare("SELECT sender, message, created_at as time FROM ejr_chat WHERE user_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result   = $stmt->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'sender'  => $row['sender'],
            'message' => $row['message'],
            'time'    => date('h:i A', strtotime($row['time']))
        ];
    }

    $markRead = $conn->prepare("UPDATE ejr_chat SET is_read = 1 WHERE user_id = ? AND sender = 'user'");
    $markRead->bind_param("i", $user_id);
    $markRead->execute();

    echo json_encode([
        'status'       => 'success',
        'messages'     => $messages,
        'chat_session' => $currentMode,
        'count'        => count($messages)
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>