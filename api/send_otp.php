<?php
header('Content-Type: application/json');
session_start();

require '../includes/PHPMailer/Exception.php';
require '../includes/PHPMailer/PHPMailer.php';
require '../includes/PHPMailer/SMTP.php';
require_once 'db_config.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;
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

// ✅ Send email
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'support@ejrgarage.com';
    $mail->Password   = 'Ejrgarage2026!';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

            $mail->Timeout    = 15;
    $mail->setFrom('support@ejrgarage.com', 'EJR Garage');
    $mail->addAddress($user['email'], $user['full_name']);

    $mail->isHTML(true);
    $mail->Subject = 'EJR Garage - Phone Verification Code';
    $mail->Body = '
    <div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
        <div style="background:#ffffff;border-radius:12px;max-width:480px;margin:auto;padding:30px;text-align:center;border:1px solid #e3e8ef;">
            
            <h2 style="color:#1e2a36;margin-bottom:10px;">
                Phone Verification
            </h2>
    
            <p style="color:#5f6b7a;margin-bottom:10px;">
                Hi ' . htmlspecialchars($user['full_name']) . ',
            </p>
    
            <p style="color:#5f6b7a;margin-bottom:20px;">
                Enter this code to verify your phone number.<br>
                <strong>Expires in 5 minutes</strong>
            </p>
    
            <div style="font-size:36px;font-weight:bold;letter-spacing:6px;
                        color:#e62429;background:#f8f9fa;border-radius:8px;
                        padding:15px;margin:20px 0;">
                ' . $otp . '
            </div>
    
            <p style="color:#a0aab5;font-size:12px;">
                If you didn’t request this, you can ignore this email.
            </p>
    
        </div>
    </div>';

    $mail->send();
    echo json_encode(['status' => 'success', 'message' => 'OTP sent to your email']);

} catch (MailException $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Mail error: ' . $mail->ErrorInfo
    ]);
}