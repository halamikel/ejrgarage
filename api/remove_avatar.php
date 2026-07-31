<?php
header('Content-Type: application/json');
require_once 'db_config.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined("JWT_SECRET")) require_once __DIR__ . "/env.php";
$secret_key = JWT_SECRET;
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';

if (!preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $decoded = JWT::decode($matches[1], new Key($secret_key, 'HS256'));
    $userId  = $decoded->uid ?? $decoded->user_id ?? 0;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
    exit;
}

// Get current profile picture path to delete the file
$stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user && !empty($user['profile_picture'])) {
    $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($user['profile_picture'], '/');
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

// Clear profile_picture in DB
$upd = $conn->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
$upd->bind_param("i", $userId);

if ($upd->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Profile photo removed.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Could not remove photo.']);
}
?>
