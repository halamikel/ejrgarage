<?php
session_start();

header('Content-Type: application/json');

// ── CORS: reflect exact origin ──
$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['https://ejrgarage.onrender.com', 'http://localhost'];
if (in_array($origin, $allowed)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: https://ejrgarage.onrender.com');
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$root = dirname(__DIR__);
require_once $root . '/api/db_config.php';
require_once $root . '/includes/php-jwt/JWT.php';
require_once $root . '/includes/php-jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ── AUTH: JWT bearer token (with session fallback) ──
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId === 0) {
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    $auth    = $headers['authorization'] ?? '';
    if (preg_match('/bearer\s(\S+)/i', $auth, $m)) {
        try {
            $decoded = JWT::decode($m[1], new Key(JWT_SECRET, 'HS256'));
            $userId  = (int)($decoded->uid ?? 0);
        } catch (Exception $e) {}
    }
}

if ($userId === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// 1. Reset chat session to bot
$stmt = $conn->prepare("UPDATE users SET chat_session = 'bot' WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();

// 2. Delete conversation messages
$del = $conn->prepare("DELETE FROM ejr_chat WHERE user_id = ?");
$del->bind_param("i", $userId);

if ($del->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to clear chat history']);
}
?>