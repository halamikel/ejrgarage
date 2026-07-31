<?php
header('Content-Type: application/json');
session_start();
require_once 'db_config.php';   // loads env.php
require_once 'rate_limit.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Rate limit: 5 OTP verification attempts per 10 minutes per IP
ejr_rate_limit($conn, 'verify_otp', ejr_get_ip(), 5, 300);

$secret_key = JWT_SECRET;

$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';

if (!preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token missing']);
    exit;
}

try {
    $decoded = JWT::decode($matches[1], new Key($secret_key, 'HS256'));
    $userId  = $decoded->uid;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: ' . $e->getMessage()]);
    exit;
}

$data    = json_decode(file_get_contents('php://input'), true);
$userOtp = $data['otp']   ?? '';
$phone   = $data['phone'] ?? '';

if (empty($userOtp) || empty($phone)) {
    echo json_encode(['status' => 'error', 'message' => 'OTP and phone are required']);
    exit;
}

// ✅ Check expiry
if (!isset($_SESSION['otp_expires']) || time() > $_SESSION['otp_expires']) {
    echo json_encode(['status' => 'error', 'message' => 'OTP has expired. Please request a new one.']);
    exit;
}

// ✅ Check OTP matches and belongs to correct user
if (
    !isset($_SESSION['pending_otp'])        ||
    $userOtp != $_SESSION['pending_otp']    ||
    $_SESSION['otp_user_id'] != $userId
) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired code']);
    exit;
}

// ✅ Update DB
$query = "UPDATE users SET is_phone_verified = 1, phone = ? WHERE id = ?";
$stmt  = $conn->prepare($query);
$stmt->bind_param("si", $phone, $userId);

if ($stmt->execute()) {
    unset(
        $_SESSION['pending_otp'],
        $_SESSION['pending_phone'],
        $_SESSION['otp_expires'],
        $_SESSION['otp_user_id']
    );
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
}