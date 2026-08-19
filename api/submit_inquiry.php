<?php
header('Content-Type: application/json');
require_once 'db_config.php';   // loads env.php
require_once 'rate_limit.php';
require_once 'security.php';
date_default_timezone_set('Asia/Manila');

// ── Rate limit: 5 inquiries per hour per IP ───────────────────
ejr_rate_limit($conn, 'submit_inquiry', ejr_get_ip(), 5, 300);

// ── Auth: must be logged in via JWT ──────────────────────────
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';
require_once 'mailer_helper.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';

if (!preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Login required to submit an inquiry.']);
    exit;
}

try {
    $decoded = JWT::decode($matches[1], new Key(JWT_SECRET, 'HS256'));
    $userId  = (int)($decoded->uid ?? $decoded->id ?? 0);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

// ── Input validation ──────────────────────────────────────────
$raw  = ejr_json_input();
$data = ejr_validate($raw, [
    'part_id'       => ['type' => 'int',    'min' => 1],
    'customer_name' => ['type' => 'string', 'min' => 2,  'max' => 100],
    'contact'       => ['type' => 'string', 'min' => 7,  'max' => 20],
    'message'       => ['type' => 'string', 'min' => 0,  'max' => 1000, 'required' => false],
]);

$partId  = $data['part_id'];
$name    = $data['customer_name'];
$contact = $data['contact'];
$message = $data['message'] ?? '';

// ── Verify part exists ────────────────────────────────────────
$pStmt = $conn->prepare("SELECT id, name, price FROM parts WHERE id = ?");
$pStmt->bind_param("i", $partId);
$pStmt->execute();
$part = $pStmt->get_result()->fetch_assoc();

if (!$part) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Part not found.']);
    exit;
}

// ── Save inquiry ──────────────────────────────────────────────
$stmt = $conn->prepare("INSERT INTO part_inquiries (part_id, user_id, customer_name, contact, message) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iisss", $partId, $userId, $name, $contact, $message);
$stmt->execute();

// ── Respond immediately, then send email ─────────────────────
$responseBody = json_encode(['status' => 'success', 'message' => 'Inquiry submitted successfully.']);
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
    @ob_flush();
    flush();
}

// ── Send email to admin (after response) ─────────────────────
// Sent via Brevo's HTTPS API — API key loaded from env.php, never hardcoded.
try {
    $emailSubject = 'New Part Inquiry - ' . $part['name'];
    $emailBody    = '
    <div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
        <div style="background:#fff;border-radius:12px;max-width:520px;margin:auto;padding:30px;border:1px solid #e3e8ef;">
            <h2 style="color:#e62429;">New Part Inquiry</h2>
            <table style="width:100%;font-size:14px;border-collapse:collapse;">
                <tr><td style="color:#5f6b7a;padding:6px 0;width:40%;">Part</td><td style="font-weight:bold;">' . htmlspecialchars($part['name']) . '</td></tr>
                <tr><td style="color:#5f6b7a;padding:6px 0;">Price</td><td style="font-weight:bold;">₱' . number_format($part['price'], 2) . '</td></tr>
                <tr><td style="color:#5f6b7a;padding:6px 0;">Customer</td><td style="font-weight:bold;">' . htmlspecialchars($name) . '</td></tr>
                <tr><td style="color:#5f6b7a;padding:6px 0;">Contact</td><td style="font-weight:bold;">' . htmlspecialchars($contact) . '</td></tr>
                <tr><td style="color:#5f6b7a;padding:6px 0;">Message</td><td>' . htmlspecialchars($message ?: '—') . '</td></tr>
            </table>
        </div>
    </div>';
    sendEmailViaBrevo(BREVO_SENDER_EMAIL, 'EJR Garage Admin', $emailSubject, $emailBody);
} catch (\Throwable $e) {
    error_log('EJR inquiry mail error: ' . $e->getMessage());
}
exit;