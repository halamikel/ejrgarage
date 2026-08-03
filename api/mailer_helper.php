<?php
/**
 * EJR Garage — Email & SMS Helper
 *
 * API keys are loaded from env.php — never hardcoded here.
 * To rotate a key: update env.php, revoke the old key in the dashboard.
 */
if (!defined('BREVO_API_KEY')) require_once __DIR__ . '/env.php';

// ── Email via Brevo REST API ──────────────────────────────────
function sendEmailViaBrevo(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
    $payload = json_encode([
        'sender'      => ['name' => BREVO_SENDER_NAME, 'email' => BREVO_SENDER_EMAIL],
        'to'          => [['email' => $toEmail, 'name'  => $toName]],
        'subject'     => $subject,
        'htmlContent' => $htmlBody,
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'api-key: ' . BREVO_API_KEY,
            'content-type: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) { error_log("EJR MAIL cURL: $curlError"); return false; }
    if ($httpCode !== 201) { error_log("EJR MAIL Brevo $httpCode: $response"); return false; }
    return true;
}

function sendVerificationEmail(string $userEmail, string $token): bool {
    $link = "https://ejrgarage.site.je/api/verify.php?token=" . urlencode($token);
    $html = "
    <div style='background:#f4f6f8;padding:40px;font-family:Arial,sans-serif;'>
      <div style='background:#fff;border-radius:12px;max-width:480px;margin:auto;padding:36px;border:1px solid #e3e8ef;text-align:center;'>
        <h2 style='color:#e62429;margin:0 0 8px;'>EJR Garage</h2>
        <p style='color:#5f6b7a;margin:0 0 24px;font-size:0.9rem;'>Automotive Services</p>
        <h3 style='color:#1e2a36;'>Verify Your Email</h3>
        <p style='color:#5f6b7a;'>Welcome! Click the button below to verify your account.</p>
        <a href='$link' style='display:inline-block;background:linear-gradient(45deg,#e62429,#ff6b00);color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:bold;margin:20px 0;'>
          Verify Account
        </a>
        <p style='color:#aab3bc;font-size:0.78rem;margin-top:20px;'>If you didn't sign up, you can ignore this email.</p>
      </div>
    </div>";
    return sendEmailViaBrevo($userEmail, $userEmail, 'Verify Your EJR Garage Account', $html);
}

// ── Generic notification email (wraps the standard card template) ─
// Used anywhere that previously used PHPMailer/SMTP directly, which is
// unreliable on hosts (like Render) that block outbound SMTP ports.
function sendNotificationEmail(
    string $toEmail,
    string $toName,
    string $subject,
    string $heading,
    string $bodyHtml,
    ?string $ctaText = null,
    ?string $ctaUrl = null
): bool {
    $cta = '';
    if ($ctaText && $ctaUrl) {
        $cta = '<div style="text-align:center;margin-top:24px;">
            <a href="' . htmlspecialchars($ctaUrl) . '" style="background:#e62429;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;">'
            . htmlspecialchars($ctaText) . '</a>
        </div>';
    }

    $html = '
    <div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
        <div style="background:#fff;border-radius:12px;max-width:520px;margin:auto;padding:30px;border:1px solid #e3e8ef;">
            <div style="text-align:center;margin-bottom:24px;">
                <h2 style="color:#e62429;margin:0;">EJR Garage</h2>
                <p style="color:#5f6b7a;margin:4px 0 0;">' . htmlspecialchars($heading) . '</p>
            </div>
            ' . $bodyHtml . '
            ' . $cta . '
            <p style="color:#a0aab5;font-size:11px;text-align:center;margin-top:24px;">
                EJR Garage &mdash; Santo Tomas, Batangas
            </p>
        </div>
    </div>';

    return sendEmailViaBrevo($toEmail, $toName, $subject, $html);
}

// ── SMS via Twilio ────────────────────────────────────────────
function sendSmsViaBrevo(string $toPhone, string $message, string $senderName = 'EJRGarage'): bool {
    if (!defined('TWILIO_ACCOUNT_SID')) require_once __DIR__ . '/env.php';

    // Normalize to +639XXXXXXXXX
    $phone = preg_replace('/\D/', '', $toPhone);
    if (strlen($phone) === 11 && $phone[0] === '0') $phone = '63' . substr($phone, 1);
    if (strlen($phone) === 10 && $phone[0] === '9')  $phone = '63' . $phone;
    $phone = '+' . ltrim($phone, '+');

    $url = "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_ACCOUNT_SID . "/Messages.json";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_USERPWD        => TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN,
        CURLOPT_POSTFIELDS     => http_build_query(['To' => $phone, 'From' => TWILIO_FROM_NUMBER, 'Body' => $message]),
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) { error_log("EJR SMS cURL: $curlError"); return false; }
    if ($httpCode < 200 || $httpCode >= 300) { error_log("EJR SMS Twilio $httpCode: $response"); return false; }
    return true;
}