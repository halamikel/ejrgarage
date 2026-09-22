<?php
header('Content-Type: application/json');
require_once 'db_config.php';
ejr_ensure_customer_profiles_table($conn);
require_once 'rate_limit.php';
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
    ejr_rate_limit($conn, "add_profile", ejr_get_ip() . "|" . $userId, 20, 300);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$label         = trim($data['label'] ?? '');
$fullName      = trim($data['full_name'] ?? '');
$rawContact    = preg_replace('/\D/', '', $data['contact_number'] ?? '');
$addressLine1  = trim($data['address_line1'] ?? '');
$addressLine2  = trim($data['address_line2'] ?? '');
$city          = trim($data['city'] ?? '');
$province      = trim($data['province'] ?? '');
$zipCode       = trim($data['zip_code'] ?? '');
$isDefault     = !empty($data['is_default']) ? 1 : 0;

// ── VALIDATION ──────────────────────────────────────────────
if ($fullName === '' || $addressLine1 === '' || $city === '' || $province === '' || $zipCode === '') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Please fill in all required fields."]);
    exit;
}

// Contact number must be a valid PH mobile number: 9XXXXXXXXX (10 digits)
if (!preg_match('/^9\d{9}$/', $rawContact)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Please enter a valid 10-digit contact number (e.g. 9XXXXXXXXX)."]);
    exit;
}
$contactNumber = '63' . $rawContact; // normalized, same format as users.phone

if (!preg_match('/^\d{4}$/', $zipCode)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Please enter a valid 4-digit ZIP code."]);
    exit;
}

if ($label === '') {
    $label = 'Saved Address';
}

// ── IF SETTING AS DEFAULT, CLEAR OTHER DEFAULTS FIRST ────────
if ($isDefault) {
    $clear = $conn->prepare("UPDATE customer_profiles SET is_default = 0 WHERE user_id = ?");
    $clear->bind_param("i", $userId);
    $clear->execute();
}

$stmt = $conn->prepare(
    "INSERT INTO customer_profiles
        (user_id, label, full_name, contact_number, address_line1, address_line2, city, province, zip_code, is_default)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    "issssssssi",
    $userId, $label, $fullName, $contactNumber, $addressLine1, $addressLine2, $city, $province, $zipCode, $isDefault
);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database Error: " . $stmt->error]);
    exit;
}

echo json_encode([
    "status"  => "success",
    "profile" => [
        "id"             => $stmt->insert_id,
        "label"          => $label,
        "full_name"      => $fullName,
        "contact_number" => $contactNumber,
        "address_line1"  => $addressLine1,
        "address_line2"  => $addressLine2,
        "city"           => $city,
        "province"       => $province,
        "zip_code"       => $zipCode,
        "is_default"     => $isDefault
    ]
]);
