<?php
session_start();
include '../db_config.php';
header('Content-Type: application/json');

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

$data    = json_decode(file_get_contents('php://input'), true);
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
    exit;
}

try {
    // 1. Delete all chat messages for this user
    $del = $conn->prepare("DELETE FROM ejr_chat WHERE user_id = ?");
    $del->bind_param("i", $user_id);
    $del->execute();

    // 2. Reset chat_session back to 'bot'
    $upd = $conn->prepare("UPDATE users SET chat_session = 'bot' WHERE id = ?");
    $upd->bind_param("i", $user_id);
    $upd->execute();

    echo json_encode(['status' => 'success', 'message' => 'Session ended and chat cleared']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>