<?php
header('Content-Type: application/json');
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/rate_limit.php';
require_once __DIR__ . '/db_config.php';

// Rate limit: 5 contact messages per hour per IP
ejr_rate_limit($conn, 'contact', ejr_get_ip(), 5, 300);

$raw     = ejr_json_input();
$clean   = ejr_validate($raw, [
    'name'    => ['type' => 'string', 'min' => 2,  'max' => 100],
    'phone'   => ['type' => 'string', 'min' => 7,  'max' => 20],
    'email'   => ['type' => 'email',  'max' => 254],
    'subject' => ['type' => 'string', 'min' => 3,  'max' => 150],
    'message' => ['type' => 'string', 'min' => 10, 'max' => 2000],
]);

$name    = $clean['name'];
$phone   = $clean['phone'];
$email   = $clean['email'];
$subject = $clean['subject'];
$message = $clean['message'];

$brevoApiKey = BREVO_API_KEY;

$htmlBody = '
    <div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
        <div style="background:#fff;border-radius:12px;max-width:520px;margin:auto;padding:30px;border:1px solid #e3e8ef;">
            <div style="text-align:center;margin-bottom:24px;">
                <h2 style="color:#e62429;margin:0;">EJR Garage</h2>
                <p style="color:#5f6b7a;margin:4px 0 0;">New Contact Form Message</p>
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                <tr><td style="color:#5f6b7a;padding:6px 0;width:90px;">Name</td><td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($name) . '</td></tr>
                <tr><td style="color:#5f6b7a;padding:6px 0;">Phone</td><td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($phone) . '</td></tr>
                <tr><td style="color:#5f6b7a;padding:6px 0;">Email</td><td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($email) . '</td></tr>
                <tr><td style="color:#5f6b7a;padding:6px 0;">Subject</td><td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($subject) . '</td></tr>
            </table>
            <div style="background:#f8f9fa;border-radius:8px;padding:16px;margin-top:16px;">
                <p style="color:#1e2a36;white-space:pre-wrap;margin:0;">' . htmlspecialchars($message) . '</p>
            </div>
        </div>
    </div>';

$payload = [
    'sender'      => ['name' => 'EJR Garage Website', 'email' => 'ejrgaragetest@gmail.com'],
    'to'          => [['email' => 'ejrgaragetest@gmail.com', 'name' => 'EJR Garage']],
    'replyTo'     => ['email' => $email, 'name' => $name],
    'subject'     => "Contact Form: {$subject}",
    'htmlContent' => $htmlBody,
];

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

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    error_log("Contact form Brevo cURL error: " . $curlError);
    echo json_encode(['status' => 'error', 'message' => 'Connection error. Please try again later.']);
    exit;
}

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['status' => 'success', 'message' => 'Message sent successfully.']);
} else {
    error_log("Contact form Brevo API error ({$httpCode}): " . $response);
    echo json_encode(['status' => 'error', 'message' => 'Failed to send message. Please try again later.']);
}
