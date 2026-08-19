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
if (!isset($data['appointment_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit;
}

// ── DELETE (used by the mobile admin app's "Delete Appointment" action) ──
// Kept in this file rather than a new endpoint: same JWT-admin gate above
// already applies, and it's the only place appointment_id is authorized.
if (($data['action'] ?? '') === 'delete') {
    $del = $conn->prepare("DELETE FROM appointments WHERE id = ?");
    $del->bind_param("i", $data['appointment_id']);
    if (!$del->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Delete failed.']);
        exit;
    }
    if ($del->affected_rows <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Appointment not found']);
        exit;
    }
    echo json_encode(['status' => 'success', 'message' => 'Appointment deleted.']);
    exit;
}

if (!isset($data['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit;
}

// The admin's reply/reason when declining or cancelling an appointment
// from the Manage dialog's cancellation-reply dropdown — optional, and
// only meaningful when the new status is 'Cancelled'.
$cancellationReply = trim((string)($data['cancellation_reply'] ?? ''));

// Self-healing: same column this endpoint writes to below. Guards
// against the column not existing yet if this endpoint is hit before
// the admin dashboard's own self-healing check in get_full_site_data.php
// has ever run.
$replyColCheck = $conn->query("SHOW COLUMNS FROM appointments LIKE 'cancellation_reply'");
if ($replyColCheck && $replyColCheck->num_rows === 0) {
    $conn->query("ALTER TABLE appointments ADD COLUMN cancellation_reply VARCHAR(255) NULL");
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
// Only write cancellation_reply when a status is actually 'Cancelled'
// with a reply given — an admin re-opening a previously-cancelled
// appointment to a different status shouldn't need to clear it
// manually, but a fresh cancellation always overwrites it (including
// clearing it back to NULL if this cancellation has no reply chosen).
if ($data['status'] === 'Cancelled') {
    $stmt = $conn->prepare("UPDATE appointments SET status = ?, cancellation_reply = ? WHERE id = ?");
    $replyOrNull = $cancellationReply !== '' ? $cancellationReply : null;
    $stmt->bind_param("ssi", $data['status'], $replyOrNull, $data['appointment_id']);
} else {
    $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $data['status'], $data['appointment_id']);
}
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $conn->error]);
    exit;
}
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
            </div>' .
            ($status === 'Cancelled' && $cancellationReply !== '' ? '
            <div style="background:#f8d7da;border-radius:8px;padding:16px 20px;margin:0 0 20px;">
                <p style="color:#842029;margin:0;font-size:13px;font-weight:bold;">Message from EJR Garage</p>
                <p style="color:#842029;margin:6px 0 0;font-size:14px;">' . htmlspecialchars($cancellationReply) . '</p>
            </div>' : '') . '
            <div style="text-align:center;margin-top:24px;">
                <a href="https://ejrgarage.onrender.com/my_appointments.php" style="background:#e62429;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;">
                    View My Appointments
                </a>
            </div>
            <p style="color:#a0aab5;font-size:11px;text-align:center;margin-top:24px;">EJR Garage &mdash; Santo Tomas, Batangas</p>
        </div>
    </div>';

    $emailSent = sendEmailViaBrevo($user['email'], $user['full_name'], "EJR Garage - Appointment {$status}", $html);
    error_log("EJR APPT MAIL: send to {$user['email']} result=" . ($emailSent ? 'success' : 'FAILED'));
}

// ── SEND SMS via Semaphore ──────────────────────────────────────
if ($user && $user['notif_sms'] == 1 && !empty($user['phone'])) {

    $statusMessages = [
        'Confirmed'   => "Hi {$firstName}, your appointment is CONFIRMED. See you on your scheduled date! View: ejrgarage.onrender.com/my_appointments.php",
        'Pending'     => "Hi {$firstName}, your appointment is PENDING review. We will confirm it shortly. View: ejrgarage.onrender.com/my_appointments.php",
        'Cancelled'   => "Hi {$firstName}, your appointment is CANCELLED. Please call us to reschedule. View: ejrgarage.onrender.com/my_appointments.php",
        'Completed'   => "Hi {$firstName}, your appointment is COMPLETED. Thank you for trusting EJR Garage!",
        'In Progress' => "Hi {$firstName}, your appointment is now IN PROGRESS. Our team is currently working on your vehicle.",
        'No Show'     => "Hi {$firstName}, we missed you for your appointment. Please contact us to reschedule.",
    ];

    $smsText = isset($statusMessages[$status])
        ? "EJR Garage: " . $statusMessages[$status]
        : "EJR Garage: Hi {$firstName}, your appointment on {$formattedDate} has been updated to [{$status}]. Vehicle: {$vehicle}.";

    if ($status === 'Cancelled' && $cancellationReply !== '') {
        $smsText .= " Note: {$cancellationReply}";
    }

    $smsResult = sendSmsViaSemaphore($user['phone'], $smsText);
    if ($smsResult['status'] !== 'success') {
        error_log("EJR APPT SMS: appt_id={$data['appointment_id']} to={$user['phone']} FAILED — " . $smsResult['message']);
    }
}
?>