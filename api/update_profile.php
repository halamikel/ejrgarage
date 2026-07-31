<?php
header('Content-Type: application/json');
session_start();
require_once 'db_config.php';
require_once 'rate_limit.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';
require_once 'mailer_helper.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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
    $userId  = $decoded->uid ?? $decoded->user_id ?? 0;
    ejr_rate_limit($conn, "update_profile", ejr_get_ip() . "|" . $userId, 10, 300);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: ' . $e->getMessage()]);
    exit;
}

$data      = json_decode(file_get_contents('php://input'), true);
$full_name = trim($data['full_name'] ?? '');
$email     = trim($data['email'] ?? '');
$rawPhone  = preg_replace('/\D/', '', $data['phone'] ?? '');
// Normalize any input variant down to the bare 10-digit mobile number (9XXXXXXXXX)
if (strlen($rawPhone) === 12 && substr($rawPhone, 0, 2) === '63') {
    $rawPhone = substr($rawPhone, 2);          // 639XXXXXXXXX -> 9XXXXXXXXX
} elseif (strlen($rawPhone) === 11 && $rawPhone[0] === '0') {
    $rawPhone = substr($rawPhone, 1);          // 09XXXXXXXXX  -> 9XXXXXXXXX
}
// Always store as 639XXXXXXXXX
$phone = (strlen($rawPhone) === 10 && $rawPhone[0] === '9') ? '63' . $rawPhone : '';
$address = trim($data['address'] ?? '');

if (empty($full_name) || empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Name and email are required']);
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
    exit;
}

// Get current user data
$stmt = $conn->prepare("SELECT email, phone, full_name, address FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();

if (!$current) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

$emailChanged = strtolower($email) !== strtolower($current['email']);
$phoneChanged = $phone !== ($current['phone'] ?? '');

// Check if new email is already taken by another user
if ($emailChanged) {
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkStmt->bind_param("si", $email, $userId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email is already in use by another account']);
        exit;
    }
}

// If email changed, send verification email with token
if ($emailChanged) {
    $token     = bin2hex(random_bytes(32));
    $expires   = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $newPhone  = $phone;
    $newName   = $full_name;

    // Store pending update in DB
    $ins = $conn->prepare(
        "INSERT INTO email_change_requests (user_id, new_email, new_phone, new_name, token, expires_at)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE new_email=VALUES(new_email), new_phone=VALUES(new_phone),
         new_name=VALUES(new_name), token=VALUES(token), expires_at=VALUES(expires_at)"
    );
    $ins->bind_param("isssss", $userId, $email, $newPhone, $newName, $token, $expires);

    if (!$ins->execute()) {
        // Table might not exist yet — auto-create it
        $conn->query("CREATE TABLE IF NOT EXISTS email_change_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            new_email VARCHAR(255) NOT NULL,
            new_phone VARCHAR(30),
            new_name VARCHAR(255),
            token VARCHAR(100) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $ins->execute();
    }

    // Build verification link
    $verifyLink = "https://ejrgarage.infinityfreeapp.com/api/confirm_email_change.php?token=" . urlencode($token);

    $html = "
    <div style='background:#f4f6f8;padding:40px;font-family:Arial,sans-serif;'>
      <div style='background:#fff;border-radius:12px;max-width:480px;margin:auto;padding:36px;border:1px solid #e3e8ef;text-align:center;'>
        <h2 style='color:#e62429;margin:0 0 8px;'>EJR Garage</h2>
        <p style='color:#5f6b7a;margin:0 0 24px;font-size:0.9rem;'>Automotive Services</p>
        <h3 style='color:#1e2a36;'>Confirm Email Change</h3>
        <p style='color:#5f6b7a;'>Hi " . htmlspecialchars($current['full_name']) . ",</p>
        <p style='color:#5f6b7a;'>You requested to update your account email to <strong>" . htmlspecialchars($email) . "</strong>.<br>Click the button below to confirm this change.</p>
        <a href='$verifyLink'
           style='display:inline-block;background:linear-gradient(45deg,#e62429,#ff6b00);color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:bold;margin:20px 0;'>
          Confirm Email Change
        </a>
        <p style='color:#aab3bc;font-size:0.78rem;margin-top:20px;'>
          This link expires in 1 hour. If you didn't request this, you can safely ignore this email.
        </p>
      </div>
    </div>";

    $sent = sendEmailViaBrevo($email, $full_name, 'EJR Garage – Confirm Your New Email Address', $html);

    if (!$sent) {
        echo json_encode(['status' => 'error', 'message' => 'Could not send verification email. Please try again.']);
        exit;
    }

    // Still update name, phone and address immediately if those changed
    if ($full_name !== $current['full_name'] || ($phoneChanged && !$emailChanged)) {
        $upStmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?");
        $upStmt->bind_param("sssi", $full_name, $phone, $address, $userId);
        $upStmt->execute();
    } elseif ($full_name !== $current['full_name']) {
        $upStmt = $conn->prepare("UPDATE users SET full_name = ?, address = ? WHERE id = ?");
        $upStmt->bind_param("ssi", $full_name, $address, $userId);
        $upStmt->execute();
    }

    echo json_encode([
        'status'  => 'pending_email',
        'message' => "A verification link has been sent to $email. Please check your inbox to confirm the email change. Your name" . ($phoneChanged ? " and phone" : "") . " have been updated."
    ]);
    exit;
}

// No email change — update name, phone and address directly
$upStmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?");
$upStmt->bind_param("sssi", $full_name, $phone, $address, $userId);

if ($upStmt->execute()) {
    // Return updated user
    $selStmt = $conn->prepare("SELECT id, full_name, email, phone, address, role_id, created_at FROM users WHERE id = ?");
    $selStmt->bind_param("i", $userId);
    $selStmt->execute();
    $updatedUser = $selStmt->get_result()->fetch_assoc();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Profile updated successfully.',
        'user'    => $updatedUser
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update profile']);
}