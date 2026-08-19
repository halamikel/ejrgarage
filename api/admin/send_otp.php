<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../db_config.php';   // loads env.php
require_once __DIR__ . '/../../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/php-jwt/Key.php';
require_once __DIR__ . '/../mailer_helper.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ── JWT auth ──────────────────────────────────────────────────
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';

if (!preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token missing.']);
    exit;
}

try {
    $decoded = JWT::decode($matches[1], new Key(JWT_SECRET, 'HS256'));
    $userId  = (int)($decoded->uid ?? 0);
    $role    = (int)($decoded->role ?? 0);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

// ── Role check: admin only (role_id = 2) ─────────────────────
if ($role !== 2) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden. Admin access required.']);
    exit;
}

// ── Get user from DB ──────────────────────────────────────────
$stmt = $conn->prepare("SELECT email, full_name FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    exit;
}

$data  = json_decode(file_get_contents('php://input'), true);
$phone = trim($data['phone'] ?? '');

if (empty($phone)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Phone number is required.']);
    exit;
}

// ── Generate OTP ──────────────────────────────────────────────
$otp = rand(100000, 999999);
$_SESSION['pending_otp']   = $otp;
$_SESSION['pending_phone'] = $phone;
$_SESSION['otp_user_id']   = $userId;
$_SESSION['otp_expires']   = time() + 300;

// ── Send via Brevo — API key from env.php ──────────────────────
try {
    $emailSubject = 'EJR Garage - Phone Verification Code';
    $emailBody    = '
    <div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
        <div style="background:#fff;border-radius:12px;max-width:480px;margin:auto;padding:30px;text-align:center;border:1px solid #e3e8ef;">
            <h2 style="color:#1e2a36;margin-bottom:10px;">Phone Verification</h2>
            <p style="color:#5f6b7a;margin-bottom:10px;">Hi ' . htmlspecialchars($user['full_name']) . ',</p>
            <p style="color:#5f6b7a;margin-bottom:20px;">Enter this code to verify your phone number.<br><strong>Expires in 5 minutes.</strong></p>
            <div style="font-size:36px;font-weight:bold;letter-spacing:6px;color:#e62429;background:#f8f9fa;border-radius:8px;padding:15px;margin:20px 0;">
                ' . $otp . '
            </div>
            <p style="color:#a0aab5;font-size:12px;">If you didn\'t request this, you can ignore this email.</p>
        </div>
    </div>';

    sendEmailViaBrevo($user['email'], $user['full_name'], $emailSubject, $emailBody);
    echo json_encode(['status' => 'success', 'message' => 'OTP sent to your email.']);

} catch (\Throwable $e) {
    error_log('EJR admin OTP mail error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP. Please try again.']);
}