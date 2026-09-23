<?php
/**
 * EJR Garage — Email & SMS Helper
 *
 * API keys are loaded from env.php — never hardcoded here.
 * To rotate a key: update env.php, revoke the old key in the dashboard.
 */
if (!defined('BREVO_API_KEY')) require_once __DIR__ . '/env.php';

// ── Email via Brevo REST API ──────────────────────────────────
// Remembers WHY the last send failed so callers can log/surface it.
// (Every call site used to just get `false` with no explanation.)
function ejrLastMailError(): string {
    return $GLOBALS['EJR_LAST_MAIL_ERROR'] ?? '';
}

function sendEmailViaBrevo(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
    $GLOBALS['EJR_LAST_MAIL_ERROR'] = '';

    // Fail fast with a clear message if the Render env vars are missing/empty.
    // getenv() returns false for unset vars, and define(..., false) makes the
    // "api-key" header blank -> Brevo answers 401 and the user just sees
    // "Failed to send email."
    $missing = [];
    foreach (['BREVO_API_KEY', 'BREVO_SENDER_EMAIL', 'BREVO_SENDER_NAME'] as $c) {
        if (!defined($c) || !constant($c)) $missing[] = $c;
    }
    if ($missing) {
        $GLOBALS['EJR_LAST_MAIL_ERROR'] = 'Missing config: ' . implode(', ', $missing);
        error_log("EJR MAIL: " . $GLOBALS['EJR_LAST_MAIL_ERROR']);
        return false;
    }

    $payload = json_encode([
        'sender'      => ['name' => BREVO_SENDER_NAME, 'email' => BREVO_SENDER_EMAIL],
        // Brevo can reject an empty "name", so fall back to the address.
        'to'          => [['email' => $toEmail, 'name' => trim($toName) !== '' ? $toName : $toEmail]],
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

    if ($curlError) {
        $GLOBALS['EJR_LAST_MAIL_ERROR'] = "cURL: $curlError";
        error_log("EJR MAIL cURL: $curlError");
        return false;
    }

    // Brevo normally answers 201, but any 2xx means the message was accepted.
    if ($httpCode < 200 || $httpCode >= 300) {
        $decoded = json_decode((string)$response, true);
        $detail  = is_array($decoded) && !empty($decoded['message']) ? $decoded['message'] : (string)$response;
        $GLOBALS['EJR_LAST_MAIL_ERROR'] = "Brevo HTTP $httpCode: $detail";
        error_log("EJR MAIL Brevo $httpCode: $response");
        return false;
    }
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

    // Always log the raw response so real failures are visible in the
    // server logs, even when Semaphore answers with HTTP 200.
    error_log("EJR SMS Semaphore HTTP {$httpCode} response: {$response}");

    if ($httpCode < 200 || $httpCode >= 300) {
        $apiMsg = $decoded['message'] ?? (is_array($decoded) ? json_encode($decoded) : $response);
        return ['status' => 'error', 'message' => "Semaphore API error ({$httpCode}): {$apiMsg}"];
    }

    // Semaphore can respond with HTTP 200 while still failing to queue the
    // message (bad/unapproved sender name, no account balance, malformed
    // number, etc). A real success is a JSON array of message objects.
    // Anything else — an object, an empty body, or a non-array — means it
    // did NOT actually queue, even though the HTTP status looks fine.
    if (!is_array($decoded) || empty($decoded) || !isset($decoded[0])) {
        $apiMsg = is_array($decoded) ? ($decoded['message'] ?? json_encode($decoded)) : $response;
        error_log("EJR SMS: Semaphore returned HTTP 200 but no message was queued — {$apiMsg}");
        return ['status' => 'error', 'message' => $apiMsg ?: 'Semaphore did not confirm the message was queued.'];
    }

    $first = $decoded[0];
    if (isset($first['status']) && strtolower($first['status']) === 'failed') {
        return ['status' => 'error', 'message' => 'Semaphore reported delivery failure for ' . $phone];
    }

    return ['status' => 'success', 'message' => 'SMS queued to ' . $phone];
}

// ── OTP via Semaphore's dedicated OTP endpoint ──────────────────
// Docs: https://semaphore.co/docs — POST to /api/v4/otp (NOT /messages).
//
// Regular /messages traffic carrying OTP-style wording ("verification
// code", "expires in", "don't share this code") gets silently filtered
// by PH telcos' anti-smishing rules even when Semaphore reports the
// send as successful — the message never reaches the phone. The /otp
// endpoint routes through a channel dedicated to OTP traffic so it
// isn't caught by that filtering.
//
// Pass a $messageTemplate containing the literal placeholder "{otp}"
// (e.g. "EJR Garage: Your code is {otp}. It expires in 5 minutes.").
// Semaphore generates the code itself and substitutes it in — the
// generated code comes back in the response as 'code', and THAT is
// the value that must be stored/checked, not a locally-generated one,
// since it's the only code guaranteed to match what was actually sent.
//
// Returns ['status' => 'success'|'error', 'message' => string, 'code' => string|null]
function sendOtpViaSemaphore(string $toPhone, string $messageTemplate, ?string $senderName = null): array {
    if (!defined('SEMAPHORE_API_KEY')) require_once __DIR__ . '/env.php';

    if (!SEMAPHORE_API_KEY) {
        error_log("EJR OTP: SEMAPHORE_API_KEY is not configured.");
        return ['status' => 'error', 'message' => 'SMS is not configured (missing Semaphore API key).', 'code' => null];
    }

    $phone = ejrNormalizePhPhone($toPhone);
    if (!$phone) {
        error_log("EJR OTP: invalid phone number '{$toPhone}', skipping send.");
        return ['status' => 'error', 'message' => 'Invalid phone number.', 'code' => null];
    }

    if (strpos($messageTemplate, '{otp}') === false) {
        // Not fatal — Semaphore will append the code to the end — but
        // worth knowing about, since it usually means a template got
        // copy-pasted from the old self-generated flow.
        error_log("EJR OTP: message template has no {otp} placeholder, Semaphore will append the code.");
    }

    $payload = [
        'apikey'     => SEMAPHORE_API_KEY,
        'number'     => $phone,
        'message'    => $messageTemplate,
        'sendername' => $senderName ?: (defined('SEMAPHORE_SENDER_NAME') ? SEMAPHORE_SENDER_NAME : 'SEMAPHORE'),
    ];

    $ch = curl_init('https://api.semaphore.co/api/v4/otp');
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
        error_log("EJR OTP cURL error: $curlError");
        return ['status' => 'error', 'message' => 'Connection error: ' . $curlError, 'code' => null];
    }

    $decoded = json_decode($response, true);
    error_log("EJR OTP Semaphore HTTP {$httpCode} response: {$response}");

    if ($httpCode < 200 || $httpCode >= 300) {
        $apiMsg = $decoded['message'] ?? (is_array($decoded) ? json_encode($decoded) : $response);
        return ['status' => 'error', 'message' => "Semaphore OTP API error ({$httpCode}): {$apiMsg}", 'code' => null];
    }

    if (!is_array($decoded) || empty($decoded) || !isset($decoded[0])) {
        $apiMsg = is_array($decoded) ? ($decoded['message'] ?? json_encode($decoded)) : $response;
        error_log("EJR OTP: Semaphore returned HTTP 200 but no message was queued — {$apiMsg}");
        return ['status' => 'error', 'message' => $apiMsg ?: 'Semaphore did not confirm the OTP was queued.', 'code' => null];
    }

    $first = $decoded[0];
    if (isset($first['status']) && strtolower($first['status']) === 'failed') {
        return ['status' => 'error', 'message' => 'Semaphore reported delivery failure for ' . $phone, 'code' => null];
    }

    if (empty($first['code'])) {
        error_log("EJR OTP: Semaphore response had no 'code' field — " . $response);
        return ['status' => 'error', 'message' => 'Semaphore did not return an OTP code.', 'code' => null];
    }

    return ['status' => 'success', 'message' => 'OTP sent to ' . $phone, 'code' => (string) $first['code']];
}