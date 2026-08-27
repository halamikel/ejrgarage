<?php
header('Content-Type: application/json');
session_start();
session_write_close();

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/php-jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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

require_once '../mailer_helper.php';

$data           = json_decode(file_get_contents("php://input"), true);
$appointment_id = isset($data['appointment_id']) ? (int)$data['appointment_id'] : 0;

if (!$appointment_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing Appointment ID']);
    exit;
}

// ── FETCH DATA ────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT a.id, a.service_type, a.appointment_date,
           u.full_name, u.email, u.notif_email,
           v.brand, v.model, v.plate_number
    FROM appointments a
    JOIN users u ON u.id = a.user_id
    LEFT JOIN vehicles v ON v.id = a.vehicle_id
    WHERE a.id = ? AND a.status = 'Completed'
    LIMIT 1
");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$appt = $stmt->get_result()->fetch_assoc();

if (!$appt) {
    echo json_encode(['status' => 'error', 'message' => 'Completed appointment not found.']);
    exit;
}

if ($appt['notif_email'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'User has email notifications disabled.']);
    exit;
}

// ── BUILD EMAIL ───────────────────────────────────────────────
$receiptNo     = 'EJR-' . date('Y') . '-' . str_pad($appt['id'], 5, '0', STR_PAD_LEFT);
$formattedDate = date("F j, Y", strtotime($appt['appointment_date']));
$vehicle       = trim(($appt['brand'] ?? '') . ' ' . ($appt['model'] ?? ''));
$receiptToken  = hash('sha256', 'receipt-' . $appt['id'] . '-EJR2026');
$receiptLink   = "https://ejrgarage-staging.onrender.comapi/admin/generate_receipt.php?id=" . $appt['id'] . "&token=" . $receiptToken;

$html = '
<div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
    <div style="background:#fff;border-radius:12px;max-width:520px;margin:auto;padding:30px;border:1px solid #e3e8ef;">
        <div style="text-align:center;margin-bottom:24px;">
            <h2 style="color:#e62429;margin:0;">EJR Garage</h2>
            <p style="color:#5f6b7a;margin:4px 0 0;">Digital Service Receipt</p>
        </div>
        <p style="color:#1e2a36;">Hi <strong>' . htmlspecialchars($appt['full_name']) . '</strong>,</p>
        <p style="color:#5f6b7a;">Your vehicle service is complete! Below are your service details and a link to your official receipt.</p>
        <div style="background:#f8f9fa;border-radius:8px;padding:20px;margin:20px 0;">
            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                <tr><td style="color:#5f6b7a;padding:6px 0;">Receipt No.</td><td style="color:#1e2a36;font-weight:bold;">' . $receiptNo . '</td></tr>
                <tr><td style="color:#5f6b7a;padding:6px 0;">Date</td><td style="color:#1e2a36;font-weight:bold;">' . $formattedDate . '</td></tr>
                <tr><td style="color:#5f6b7a;padding:6px 0;">Vehicle</td><td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($vehicle) . '</td></tr>
                <tr><td style="color:#5f6b7a;padding:6px 0;">Services</td><td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($appt['service_type']) . '</td></tr>
            </table>
        </div>
        <div style="text-align:center;margin-top:24px;">
            <a href="' . $receiptLink . '" style="background:#198754;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;">
                View / Download Receipt
            </a>
        </div>
        <p style="color:#a0aab5;font-size:11px;text-align:center;margin-top:24px;">
            Thank you for trusting EJR Garage!<br>Mabalacat City, Pampanga
        </p>
    </div>
</div>';

$sent = sendEmailViaBrevo($appt['email'], $appt['full_name'], "Service Receipt: {$receiptNo}", $html);

if ($sent) {
    echo json_encode(['status' => 'success', 'message' => 'Receipt sent to ' . $appt['email']]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send receipt. Please try again.']);
}
?>