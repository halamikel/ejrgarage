<?php
header('Content-Type: application/json');
session_start();
require_once 'db_config.php';

// Path setup for JWT (using your specific structure)
$root = dirname(__DIR__); // BUGFIX: __DIR__ is more reliable than DOCUMENT_ROOT on shared hosts
$libPath = $root . '/includes/php-jwt/';

try {
    require_once $libPath . 'JWTExceptionWithPayloadInterface.php';
    require_once $libPath . 'BeforeValidException.php';
    require_once $libPath . 'ExpiredException.php';
    require_once $libPath . 'SignatureInvalidException.php';
    require_once $libPath . 'Key.php';
    require_once $libPath . 'JWT.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Library load failed."]);
    exit;
}

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined("JWT_SECRET")) require_once __DIR__ . "/env.php";
$secret_key = JWT_SECRET;

// Get Token from Header
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';

if (!preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token missing']);
    exit;
}

try {
    // Decode JWT to get User ID
    $decoded = JWT::decode($matches[1], new Key($secret_key, 'HS256'));
    $userId = $decoded->uid;

    // Database Query
    $stmt = $conn->prepare("SELECT is_phone_verified, phone FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        echo json_encode([
            "status" => "success",
            "is_phone_verified" => (int)$user['is_phone_verified'],
            "phone" => $user['phone']
        ]);
    } else {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "User not found"]);
    }

    $stmt->close();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: ' . $e->getMessage()]);
}

$conn->close();
?>