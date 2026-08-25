<?php
header('Content-Type: application/json');
session_start();

require_once 'db_config.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';
require_once 'mailer_helper.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!defined("JWT_SECRET")) require_once __DIR__ . "/env.php";
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
} catch (\Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: ' . $e->getMessage()]);
    exit;
}

// ✅ Get user email from DB
$stmt = $conn->prepare("SELECT email, full_name FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

$data  = json_decode(file_get_contents('php://input'), true);
$phone = $data['phone'] ?? '';

if (empty($phone)) {
    echo json_encode(['status' => 'error', 'message' => 'Phone number is required']);
    exit;
}

// ✅ Generate OTP and store in session
$otp = rand(100000, 999999);
$_SESSION['pending_otp']   = $otp;
$_SESSION['pending_phone'] = $phone;
$_SESSION['otp_user_id']   = $userId;
$_SESSION['otp_expires']   = time() + 300; // 5 minutes

// ✅ Send via SMS (Semaphore) — code goes to the number being verified,
// not the user's registered email.
try {
    $smsText = "EJR Garage: Your phone verification code is {$otp}. It expires in 5 minutes. Don't share this code with anyone.";

    $smsResult = sendSmsViaSemaphore($phone, $smsText);

    if ($smsResult['status'] === 'success') {
        echo json_encode(['status' => 'success', 'message' => 'OTP sent to your phone']);
    } else {
        error_log("EJR PHONE VERIFY SMS: user_id={$userId} phone={$phone} FAILED — " . $smsResult['message']);
        echo json_encode([
            'status'  => 'error',
            'message' => $smsResult['message'] ?: 'Failed to send verification code. Please try again.'
        ]);
    }

} catch (\Throwable $e) {
    error_log('EJR PHONE VERIFY SMS error: ' . $e->getMessage());
    echo json_encode([
        'status'  => 'error',
        'message' => 'SMS error: ' . $e->getMessage()
    ]);
}