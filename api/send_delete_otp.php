<?php
header('Content-Type: application/json');
session_start();

require_once 'db_config.php';
require_once 'mailer_helper.php'; // ✅ your Brevo functions

require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ── JWT AUTH ─────────────────────────────────────────────────
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
    $userId  = $decoded->uid ?? $decoded->id;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// ── GET USER ──────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT email, full_name FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    exit;
}

// ── GENERATE OTP ──────────────────────────────────────────────
$otp = rand(100000, 999999);

$_SESSION['delete_otp']         = $otp;
$_SESSION['delete_user_id']     = $userId;
$_SESSION['delete_otp_expires'] = time() + 300; // 5 minutes

// ── EMAIL CONTENT ─────────────────────────────────────────────
$html = '
<div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
    <div style="background:#fff;border-radius:12px;max-width:480px;margin:auto;padding:30px;text-align:center;border:1px solid #e3e8ef;">
        <h2 style="color:#e62429;margin-bottom:10px;">Account Deletion</h2>
        <p style="color:#5f6b7a;">Hi ' . htmlspecialchars($user['full_name']) . ',</p>
        <p style="color:#5f6b7a;">
            You requested to permanently delete your EJR Garage account.<br>
            Enter this code to confirm. <strong>This cannot be undone.</strong>
        </p>

        <div style="font-size:36px;font-weight:bold;letter-spacing:6px;
                    color:#e62429;background:#f8f9fa;border-radius:8px;
                    padding:15px;margin:20px 0;">
            ' . $otp . '
        </div>

        <p style="color:#a0aab5;font-size:12px;">
            Code expires in 5 minutes.<br>
            If you did not request this, ignore this email — your account is safe.
        </p>
    </div>
</div>';

// ── SEND EMAIL USING BREVO ────────────────────────────────────
$sent = sendEmailViaBrevo(
    $user['email'],
    $user['full_name'],
    'EJR Garage - Account Deletion Code',
    $html
);

// ── RESPONSE ─────────────────────────────────────────────────
if ($sent) {
    echo json_encode([
        'status' => 'success',
        'message' => 'OTP sent to your email.'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to send email.'
    ]);
}
?>