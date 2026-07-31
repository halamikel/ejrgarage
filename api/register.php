<?php
/**
 * EJR Garage — Registration API
 *
 * Security hardening (OWASP):
 *  A07 – Rate limit: 5 registrations per hour per IP
 *  A03 – Schema-based input validation (security.php)
 *  A02 – No secrets hardcoded; mailer loaded after response flush
 */
header('Content-Type: application/json');
require_once 'db_config.php';
require_once 'security.php';
require_once 'rate_limit.php';

$ip  = ejr_get_ip();
$raw = ejr_json_input();

// ── Rate limit: 5 registrations / hour per IP ─────────────────
ejr_rate_limit($conn, 'register', $ip, 5, 300);

// ── Schema validation ─────────────────────────────────────────
$data = ejr_validate($raw, [
    'name'     => ['type' => 'string',   'min' => 2,  'max' => 100],
    'email'    => ['type' => 'email',    'max' => 254],
    'phone'    => ['type' => 'phone'],
    'password' => ['type' => 'password', 'min' => 8,  'max' => 128],
]);

$fullName = $data['name'];
$email    = $data['email'];
$phone    = $data['phone']; // already normalized to 639XXXXXXXXX by ejr_validate
$role_id  = 1;

// ── Duplicate checks ──────────────────────────────────────────
$chkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
$chkEmail->bind_param("s", $email);
$chkEmail->execute();
if ($chkEmail->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["status" => "error", "message" => "This email is already registered."]);
    exit;
}

$chkPhone = $conn->prepare("SELECT id FROM users WHERE phone = ?");
$chkPhone->bind_param("s", $phone);
$chkPhone->execute();
if ($chkPhone->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["status" => "error", "message" => "This phone number is already registered."]);
    exit;
}

$hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
$token          = bin2hex(random_bytes(50));

$stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password_hash, verification_token, is_verified, role_id) VALUES (?, ?, ?, ?, ?, 0, ?)");
$stmt->bind_param("sssssi", $fullName, $email, $phone, $hashedPassword, $token, $role_id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Registration failed. Please try again."]);
    exit;
}
$stmt->close();
$conn->close();

// ── Respond immediately, send email after ─────────────────────
// InfinityFree buffers the whole response until the script ends,
// so we echo + flush before the slow email call.
$responseBody = json_encode(["status" => "success"]);
if (function_exists('fastcgi_finish_request')) {
    echo $responseBody;
    fastcgi_finish_request();
} else {
    while (ob_get_level()) ob_end_clean();
    ob_start();
    echo $responseBody;
    header('Content-Length: ' . ob_get_length());
    header('Connection: close');
    ob_end_flush();
    flush();
}

require_once 'mailer_helper.php';
sendVerificationEmail($email, $token);
exit;
