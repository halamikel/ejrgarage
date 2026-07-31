<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once 'db_config.php';
require_once 'rate_limit.php';
require_once 'security.php';
require_once 'mailer_helper.php';

// ── Rate limit: 3 reset requests per hour per IP ──────────────
// OWASP A01: also prevents user enumeration via timing attacks
ejr_rate_limit($conn, 'check_email', ejr_get_ip(), 3, 300);

// Auto-cleanup expired tokens
$conn->query("DELETE FROM password_resets WHERE expires_at < NOW()");

$raw  = ejr_json_input();
$data = ejr_validate($raw, [
    'email' => ['type' => 'email', 'max' => 254],
]);
$email = $data['email'];

// ── OWASP A01: always return the same message ─────────────────
// Never reveal whether the email exists in the DB.
$genericMessage = 'If an account with that email exists, a reset link has been sent.';

$stmt = $conn->prepare("SELECT full_name FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user) {
    $fullName  = $user['full_name'];
    $token     = bin2hex(random_bytes(32));
    $resetLink = "https://ejrgarage.site.je/reset-password.php?token=" . $token;

    $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $del->bind_param("s", $email);
    $del->execute();

    $ins = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
    $ins->bind_param("ss", $email, $token);
    $ins->execute();

    $html  = '<div style="background:#f4f6f8;padding:40px;font-family:Arial,sans-serif;">';
    $html .= '<div style="background:#fff;border-radius:12px;max-width:480px;margin:auto;padding:36px;border:1px solid #e3e8ef;text-align:center;">';
    $html .= '<h2 style="color:#e62429;margin:0 0 4px;">EJR Garage</h2>';
    $html .= '<p style="color:#5f6b7a;font-size:0.85rem;margin:0 0 24px;">Certified Repair Specialists</p>';
    $html .= '<h3 style="color:#1e2a36;margin:0 0 12px;">Password Reset Request</h3>';
    $html .= '<p style="color:#5f6b7a;">Hi <strong>' . htmlspecialchars($fullName) . '</strong>,</p>';
    $html .= '<p style="color:#5f6b7a;line-height:1.6;">We received a request to reset your password. Click the button below to set a new one. This link expires in <strong>1 hour</strong>.</p>';
    $html .= '<a href="' . $resetLink . '" style="display:inline-block;background:linear-gradient(45deg,#e62429,#ff6b00);color:#fff;padding:14px 36px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:15px;margin:24px 0;">Reset My Password</a>';
    $html .= '<p style="color:#a0aab5;font-size:11px;margin-top:16px;">If you did not request this, ignore this email.</p>';
    $html .= '<p style="color:#c0c8d0;font-size:11px;">&copy; ' . date("Y") . ' EJR Garage</p>';
    $html .= '</div></div>';

    sendEmailViaBrevo($email, $fullName, "EJR Garage - Password Reset Request", $html);
}

// Always return the same response — success or not
echo json_encode(['status' => 'success', 'message' => $genericMessage]);