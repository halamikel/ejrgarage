<?php
header('Content-Type: application/json');
error_reporting(0);

require_once '../db_config.php';
require_once '../../includes/php-jwt/JWT.php';
require_once '../../includes/php-jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require_once '../mailer_helper.php';


// -- JWT Auth --
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$auth       = $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$adminId    = 0;
$isAdmin    = false;
if (preg_match('/bearer\\s(\\S+)/i', $auth, $m)) {
    try {
        $decoded = JWT::decode($m[1], new Key(JWT_SECRET, 'HS256'));
        $adminId = (int)($decoded->uid  ?? 0);
        $role    = (int)($decoded->role ?? 0);
        $isAdmin = ($role === 2 || $adminId === 21);
    } catch (Exception $e) {}
}
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Admin access required.']);
    exit;
}
$current_user_id  = $adminId;
$current_admin_id = $adminId;
$is_admin         = true;

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['appointment_id']) || !isset($data['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit;
}

// ── FETCH APPOINTMENT ─────────────────────────────────────────
$fetch = $conn->prepare("
    SELECT a.service_type, a.appointment_date, a.user_id,
           v.brand, v.model, v.plate_number
    FROM appointments a
    LEFT JOIN vehicles v ON v.id = a.vehicle_id
    WHERE a.id = ? LIMIT 1
");
$fetch->bind_param("i", $data['appointment_id']);
$fetch->execute();
$appt = $fetch->get_result()->fetch_assoc();

if (!$appt) {
    echo json_encode(['status' => 'error', 'message' => 'Appointment not found']);
    exit;
}

// ── FETCH USER ────────────────────────────────────────────────
$userStmt = $conn->prepare("SELECT email, full_name, phone, notif_email, notif_sms FROM users WHERE id = ? LIMIT 1");
$userStmt->bind_param("i", $appt['user_id']);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

// ── UPDATE STATUS ─────────────────────────────────────────────
$stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("si", $data['status'], $data['appointment_id']);
if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Update failed.']);
    exit;
}

// ── RESPOND ───────────────────────────────────────────────────
echo json_encode(['status' => 'success']);

// ── PREPARE SHARED VARS ───────────────────────────────────────
$formattedDate = date("F j, Y", strtotime($appt['appointment_date']));
$vehicle       = trim(($appt['brand'] ?? '') . ' ' . ($appt['model'] ?? ''));
$plate         = $appt['plate_number'] ?? 'N/A';
$status        = $data['status'];
$firstName     = explode(' ', trim($user['full_name'] ?? 'Customer'))[0];

// ── SEND EMAIL ────────────────────────────────────────────────
error_log("EJR APPT MAIL: appt_id={$data['appointment_id']} user_id=" . ($appt['user_id'] ?? 'NULL') . " user_found=" . ($user ? 'yes' : 'no') . " notif_email=" . ($user['notif_email'] ?? 'n/a'));
if ($user && $user['notif_email'] == 1) {
    $badgeColors = [
        'Confirmed'   => 'background:#d1e7dd;color:#0a3622;',
        'Completed'   => 'background:#cfe2ff;color:#084298;',
        'Cancelled'   => 'background:#f8d7da;color:#842029;',
        'In Progress' => 'background:#fff3cd;color:#856404;',
        'Pending'     => 'background:#e2e3e5;color:#383d41;',
        'No Show'     => 'background:#f8d7da;color:#842029;',
    ];
    $badgeColor = $badgeColors[$status] ?? 'background:#fff3cd;color:#856404;';

    $html = '
    <div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
        <div style="background:#fff;border-radius:12px;max-width:520px;margin:auto;padding:30px;border:1px solid #e3e8ef;">
            <div style="text-align:center;margin-bottom:24px;">
                <h2 style="color:#e62429;margin:0;">EJR Garage</h2>
                <p style="color:#5f6b7a;margin:4px 0 0;">Appointment Update</p>
            </div>
            <p style="color:#1e2a36;">Hi <strong>' . htmlspecialchars($user['full_name']) . '</strong>,</p>
            <p style="color:#5f6b7a;">Your appointment status has been updated to:</p>
            <div style="text-align:center;margin:16px 0;">
                <span style="' . $badgeColor . 'padding:6px 20px;border-radius:20px;font-size:15px;font-weight:bold;">' . $status . '</span>
            </div>
            <div style="background:#f8f9fa;border-radius:8px;padding:20px;margin:20px 0;">
                <table style="width:100%;border-collapse:collapse;font-size:14px;">
                    <tr><td style="color:#5f6b7a;padding:6px 0;width:40%;">Date</td><td style="color:#1e2a36;font-weight:bold;">' . $formattedDate . '</td></tr>
                    <tr><td style="color:#5f6b7a;padding:6px 0;">Vehicle</td><td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($vehicle) . '</td></tr>
                    <tr><td style="color:#5f6b7a;padding:6px 0;">Plate No.</td><td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($plate) . '</td></tr>
                    <tr><td style="color:#5f6b7a;padding:6px 0;">Services</td><td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($appt['service_type']) . '</td></tr>
                </table>
            </div>
            <div style="text-align:center;margin-top:24px;">
                <a href="https://ejrgarage.com/my_appointments.php" style="background:#e62429;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;">
                    View My Appointments
                </a>
            </div>
            <p style="color:#a0aab5;font-size:11px;text-align:center;margin-top:24px;">EJR Garage &mdash; Santo Tomas, Batangas</p>
        </div>
    </div>';

    $emailSent = sendEmailViaBrevo($user['email'], $user['full_name'], "EJR Garage - Appointment {$status}", $html);
    error_log("EJR APPT MAIL: send to {$user['email']} result=" . ($emailSent ? 'success' : 'FAILED'));
}

// ── SEND SMS via CommPeak Streams API ─────────────────────────
if ($user && $user['notif_sms'] == 1 && !empty($user['phone'])) {

    // Matches exactly the templates created in CommPeak TextPeak dashboard
    $statusMessages = [
        'Confirmed'   => "Hi {$firstName}, your appointment is CONFIRMED. See you on your scheduled date! View: ejrgarage.site.je/my_appointments.php",
        'Pending'     => "Hi {$firstName}, your appointment is PENDING review. We will confirm it shortly. View: ejrgarage.site.je/my_appointments.php",
        'Cancelled'   => "Hi {$firstName}, your appointment is CANCELLED. Please call us to reschedule. View: ejrgarage.site.je/my_appointments.php",
        'Completed'   => "Hi {$firstName}, your appointment is COMPLETED. Thank you for trusting EJR Garage!",
        'In Progress' => "Hi {$firstName}, your appointment is now IN PROGRESS. Our team is currently working on your vehicle.",
        'No Show'     => "Hi {$firstName}, we missed you for your appointment. Please contact us to reschedule.",
    ];

    $smsText = isset($statusMessages[$status])
        ? "EJR Garage: " . $statusMessages[$status]
        : "EJR Garage: Hi {$firstName}, your appointment on {$formattedDate} has been updated to [{$status}]. Vehicle: {$vehicle}.";

    // Normalise PH number → E.164 (+63XXXXXXXXXX)
    $phone = preg_replace('/\D/', '', $user['phone']);
    if (str_starts_with($phone, '0')) {
        $phone = '63' . substr($phone, 1);
    } elseif (!str_starts_with($phone, '63')) {
        $phone = '63' . $phone;
    }
    $phone = '+' . $phone;

    // CommPeak Streams API — Transactional token
    $nameParts = explode(' ', trim($user['full_name'] ?? 'Customer'));
    $lastName  = count($nameParts) > 1 ? array_pop($nameParts) : '';
    $firstNameFull = implode(' ', $nameParts);

    // Map status to event key created in CommPeak TextPeak dashboard
    $eventKeys = [
        'Confirmed'   => 'appt_stat1',
        'Pending'     => 'appt_stat2',
        'Completed'   => 'appt_stat3',
        'Cancelled'   => 'appt_stat4',
        'In Progress' => 'appt_stat5',
        'No Show'     => 'appt_stat6',
    ];
    $eventKey = $eventKeys[$status] ?? 'appt_stat1';

    $smsPayload = json_encode([
        'recipient_phone'    => $phone,
        'template_variables' => [
            'first_name' => $firstNameFull,
            'last_name'  => $lastName,
            'code'       => '',
        ],
        'sender' => 'GLOBAL',
        'event'  => $eventKey,
    ]);

    $ch = curl_init('https://textpeak-streams.commpeak.com/otp/auth');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $smsPayload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json; charset=UTF-8',
            'Authorization: b6e00371c83f933b6a89b0fbc2cd3f74dc26e633dd7f6ea1e215d357b7ba68f7cdc388cc8d41eb1c0cb15aafcbcbfce6c33376bc73',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $smsResponse = curl_exec($ch);
    $smsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($smsHttpCode < 200 || $smsHttpCode >= 300) {
        error_log("SMS ERROR (CommPeak): HTTP {$smsHttpCode} — {$smsResponse}");
    }
}
?>