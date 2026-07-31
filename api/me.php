<?php
header("Content-Type: application/json");
require_once 'db_config.php'; // loads env.php

require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization']
    ?? $_SERVER['HTTP_AUTHORIZATION']
    ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

if (!$authHeader || !preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Token missing."]);
    exit;
}

try {
    $decoded = JWT::decode($matches[1], new Key(JWT_SECRET, 'HS256'));
    $userId  = (int)($decoded->uid ?? 0);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid or expired token."]);
    exit;
}

$stmt = $conn->prepare("SELECT id, full_name, email, phone, address, profile_picture, role_id, is_verified, is_phone_verified, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "User not found."]);
    exit;
}

echo json_encode([
    "status" => "success",
    "user"   => [
        "id"               => $user['id'],
        "name"             => $user['full_name'],
        "full_name"        => $user['full_name'],
        "email"            => $user['email'],
        "phone"            => $user['phone'],
        "phone_number"     => $user['phone'],
        "address"          => $user['address'],
        "profile_picture"  => $user['profile_picture'],
        "role_id"          => $user['role_id'],
        "is_verified"      => $user['is_verified'],
        "is_phone_verified"=> $user['is_phone_verified'],
        "created_at"       => $user['created_at'],
    ]
]);