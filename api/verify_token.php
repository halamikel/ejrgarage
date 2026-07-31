<?php
header('Content-Type: application/json');
require_once 'db_config.php';

$root    = dirname(__DIR__); // BUGFIX: __DIR__ is more reliable than DOCUMENT_ROOT on shared hosts
$libPath = $root . '/includes/php-jwt/';
require_once $libPath . 'Key.php';
require_once $libPath . 'JWT.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined("JWT_SECRET")) require_once __DIR__ . "/env.php";
$secret_key = JWT_SECRET;
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';

if (!preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    echo json_encode(['valid' => false]);
    exit;
}

try {
    $decoded = JWT::decode($matches[1], new Key($secret_key, 'HS256'));
    $userId  = $decoded->uid;

    // Check session_token in DB matches the one stored at login
    $stmt = $conn->prepare("SELECT session_token FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    // Get the token fingerprint sent from client
    $input         = json_decode(file_get_contents('php://input'), true);
    $clientToken   = $input['session_token'] ?? '';

    if (!$row || $row['session_token'] !== $clientToken) {
        echo json_encode(['valid' => false]);
        exit;
    }

    echo json_encode(['valid' => true]);
} catch (Exception $e) {
    echo json_encode(['valid' => false]);
}