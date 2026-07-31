<?php
// TEMPORARY DEBUG SCRIPT — delete this file after use.
// Requires admin login (role_id = 2) to access.

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/php-jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ── Admin-only gate ───────────────────────────────────────────
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';
$role       = 0;
if (preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    try {
        $decoded = JWT::decode($matches[1], new Key(JWT_SECRET, 'HS256'));
        $role    = (int)($decoded->role ?? 0);
    } catch (Exception $e) {}
}
if ($role !== 2) {
    http_response_code(403);
    die('<h2 style="color:red;font-family:sans-serif;">403 — Admin access required.</h2>');
}
// ─────────────────────────────────────────────────────────────

// 🔧 CHANGE THIS to your own email so you can check if it arrives:
$testRecipient = 'youremail@example.com';

if (!defined('BREVO_API_KEY')) require_once __DIR__ . '/../env.php';
$brevoApiKey = BREVO_API_KEY;

$payload = [
    'sender'      => ['name' => 'EJR Garage', 'email' => 'support@ejrgarage.com'],
    'to'          => [['email' => $testRecipient, 'name' => 'Test']],
    'subject'     => 'EJR Garage - Brevo Test',
    'htmlContent' => '<p>This is a test email sent via the Brevo HTTP API.</p>',
];

echo "<h3>Checking environment...</h3>";
echo "<p>cURL extension loaded: <strong>" . (extension_loaded('curl') ? 'YES' : 'NO - this is the problem') . "</strong></p>";

if (!extension_loaded('curl')) {
    die("<p style='color:red;'>cURL isn't available on this server. Contact your host or ask me for a non-cURL fallback using file_get_contents + stream_context.</p>");
}

$ch = curl_init('https://api.brevo.com/v3/smtp/email');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: application/json',
    'api-key: ' . $brevoApiKey,
    'content-type: application/json',
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
curl_close($ch);

echo "<h3>Result</h3>";
echo "<p><strong>HTTP code:</strong> " . htmlspecialchars($httpCode ?: '(none)') . "</p>";
echo "<p><strong>cURL errno:</strong> " . htmlspecialchars($curlErrno) . "</p>";
echo "<p><strong>cURL error:</strong> " . htmlspecialchars($curlError ?: '(none)') . "</p>";
echo "<p><strong>Raw response from Brevo:</strong></p>";
echo "<pre style='background:#f4f4f4;padding:12px;border-radius:6px;'>" . htmlspecialchars($response ?: '(empty - request likely never reached Brevo)') . "</pre>";

if ($httpCode >= 200 && $httpCode < 300) {
    echo "<h2 style='color:green;'>SUCCESS — check {$testRecipient}'s inbox (and spam folder)</h2>";
} else {
    echo "<h2 style='color:red;'>FAILED — see response above for the reason</h2>";
}