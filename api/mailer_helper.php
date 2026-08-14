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
    $link = "https://ejrgarage.onrender.com/api/verify.php?token=" . urlencode($token);
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

// ── Phone normalization (PH mobile numbers) ────────────────────
// Semaphore accepts local format: 09XXXXXXXXX (11 digits, leading 0).
// Accepts input as 09XXXXXXXXX, 9XXXXXXXXX, 639XXXXXXXXX, +639XXXXXXXXX,
// or with dashes/spaces (e.g. "09XX-XXX-XXXX") and normalizes it.
// Returns '' if the number doesn't look like a valid PH mobile number.
function ejrNormalizePhPhone(string $rawPhone): string {
    $digits = preg_replace('/\D/', '', $rawPhone);

    if (strlen($digits) === 12 && str_starts_with($digits, '63')) {
        // 639XXXXXXXXX -> 09XXXXXXXXX
        $digits = '0' . substr($digits, 2);
    } elseif (strlen($digits) === 10 && $digits[0] === '9') {
        // 9XXXXXXXXX -> 09XXXXXXXXX
        $digits = '0' . $digits;
    }

    if (strlen($digits) === 11 && $digits[0] === '0' && $digits[1] === '9') {
        return $digits;
    }

    return '';
}

// ── SMS via Semaphore (https://semaphore.co) ────────────────────
// Docs: https://semaphore.co/docs — POST apikey, number, message,
// sendername to https://api.semaphore.co/api/v4/messages
// Returns ['status' => 'success'|'error'|'skipped', 'message' => string]
// so callers (and the admin dashboard) can surface *why* a send failed,
// instead of just a bool.
function sendSmsViaSemaphore(string $toPhone, string $message, ?string $senderName = null): array {
    if (!defined('SEMAPHORE_API_KEY')) require_once __DIR__ . '/env.php';

    if (!SEMAPHORE_API_KEY) {
        error_log("EJR SMS: SEMAPHORE_API_KEY is not configured.");
        return ['status' => 'error', 'message' => 'SMS is not configured (missing Semaphore API key).'];
    }

    $phone = ejrNormalizePhPhone($toPhone);
    if (!$phone) {
        error_log("EJR SMS: invalid phone number '{$toPhone}', skipping send.");
        return ['status' => 'error', 'message' => 'Invalid phone number.'];
    }

    $payload = [
        'apikey'     => SEMAPHORE_API_KEY,
        'number'     => $phone,
        'message'    => $message,
        'sendername' => $senderName ?: (defined('SEMAPHORE_SENDER_NAME') ? SEMAPHORE_SENDER_NAME : 'SEMAPHORE'),
    ];

    $ch = curl_init('https://api.semaphore.co/api/v4/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($payload),
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("EJR SMS cURL error: $curlError");
        return ['status' => 'error', 'message' => 'Connection error: ' . $curlError];
    }

    $decoded = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log("EJR SMS Semaphore HTTP {$httpCode}: {$response}");
        $apiMsg = $decoded['message'] ?? (is_array($decoded) ? json_encode($decoded) : $response);
        return ['status' => 'error', 'message' => "Semaphore API error ({$httpCode}): {$apiMsg}"];
    }

    // Success responses are a JSON array (single number) or object; either
    // way, a 2xx with no clear error means Semaphore queued the message.
    return ['status' => 'success', 'message' => 'SMS queued to ' . $phone];
}