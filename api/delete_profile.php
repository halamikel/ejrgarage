<?php
header('Content-Type: application/json');
require_once 'db_config.php';
ejr_ensure_customer_profiles_table($conn);
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
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

try {
    $decoded = JWT::decode($matches[1], new Key($secret_key, 'HS256'));
    $userId  = $decoded->uid ?? $decoded->id;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id   = (int)($data['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing profile id."]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM customer_profiles WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(["status" => "success"]);
} else {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Profile not found."]);
}
