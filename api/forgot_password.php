<?php
/**
 * EJR Garage — Forgot Password API
 *
 * Security hardening (OWASP):
 *  A07 – Rate limit: 3 resets per hour per IP (prevents email bombing)
 *  A03 – Schema validation on email
 *  A01 – Always return the same response whether email exists or not
 *        (prevents user enumeration)
 */
header('Content-Type: application/json');
require_once 'db_config.php';
require_once 'security.php';
require_once 'rate_limit.php';
require_once 'mailer_helper.php';

$ip  = ejr_get_ip();
$raw = ejr_json_input();

// ── Rate limit: 3 attempts / hour per IP ─────────────────────
ejr_rate_limit($conn, 'forgot_password', $ip, 3, 300);

// ── Schema validation ─────────────────────────────────────────
$data = ejr_validate($raw, [
    'email' => ['type' => 'email', 'max' => 254],
]);

$email = $data['email'];

$stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// OWASP A01: always return the same message — don't reveal whether
// the email exists in the DB (prevents user enumeration).
$genericMessage = "If an account with that email exists, a reset link has been sent.";

if ($user) {
    $token   = bin2hex(random_bytes(50));
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

    $upd = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
    $upd->bind_param("ssi", $token, $expires, $user['id']);
    $upd->execute();

    $resetLink = "https://ejrgarage-staging.onrender.com/reset-password.php?token=" . urlencode($token);
    $html = "
    <div style='background:#f4f6f8;padding:40px;font-family:Arial,sans-serif;'>
      <div style='background:#fff;border-radius:12px;max-width:480px;margin:auto;padding:36px;border:1px solid #e3e8ef;text-align:center;'>
        <h2 style='color:#e62429;'>EJR Garage</h2>
        <h3 style='color:#1e2a36;'>Reset Your Password</h3>
        <p style='color:#5f6b7a;'>Hi {$user['full_name']}, click below to reset your password. This link expires in 1 hour.</p>
        <a href='$resetLink' style='display:inline-block;background:linear-gradient(45deg,#e62429,#ff6b00);color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:bold;margin:20px 0;'>
          Reset Password
        </a>
        <p style='color:#aab3bc;font-size:0.78rem;'>If you didn't request this, you can ignore this email.</p>
      </div>
    </div>";

    sendEmailViaBrevo($email, $user['full_name'], 'Reset Your EJR Garage Password', $html);
}

echo json_encode(["status" => "success", "message" => $genericMessage]);