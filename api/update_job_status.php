<?php
/**
 * EJR Garage — Mechanic Update Job Status API
 *
 * Called by the mobile app's mechanic dashboard when a mechanic taps
 * "Start Job" / "Complete Job" (api_service.dart updateJobStatus()).
 * Only lets a mechanic update a job that is actually assigned to them.
 *
 * Body: {job_id: int, status: string}
 * Response: {status, message}
 */
header('Content-Type: application/json');
require_once 'db_config.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';
require_once 'mailer_helper.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined("JWT_SECRET")) require_once __DIR__ . "/env.php";
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';

if (!preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Token missing"]);
    exit;
}

try {
    $decoded = JWT::decode($matches[1], new Key(JWT_SECRET, 'HS256'));
    $userId  = (int)$decoded->uid;
    $role    = (int)($decoded->role ?? 0);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized: " . $e->getMessage()]);
    exit;
}

if ($role !== 3) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Mechanic access required."]);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$jobId  = (int)($input['job_id'] ?? 0);
$status = trim((string)($input['status'] ?? ''));

$allowedStatuses = ['Pending', 'Confirmed', 'In Progress', 'Completed', 'Cancelled', 'No Show'];
if (!$jobId || !in_array($status, $allowedStatuses, true)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing or invalid job_id / status."]);
    exit;
}

// ── Confirm this job actually belongs to this mechanic ────────────
$stmt = $conn->prepare("
    SELECT a.user_id, a.service_type, a.appointment_date,
           v.brand, v.model, v.plate_number,
           m.user_id AS mechanic_user_id
    FROM appointments a
    LEFT JOIN vehicles v ON v.id = a.vehicle_id
    LEFT JOIN mechanics m ON m.id = a.mechanic_id
    WHERE a.id = ? LIMIT 1
");
$stmt->bind_param("i", $jobId);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Job not found."]);
    exit;
}

if ((int)($job['mechanic_user_id'] ?? 0) !== $userId) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "This job is not assigned to you."]);
    exit;
}

// ── Update status ──────────────────────────────────────────────
// Self-healing schema: appointments.status was created too narrow
// (VARCHAR(9)) to hold 'In Progress' (11 chars) or 'Cancelled'/
// 'Completed'/'Confirmed' (9 chars exactly, right at the edge).
// Widen it once so every real status in the app's canonical set fits.
$colInfo = $conn->query("SHOW COLUMNS FROM appointments LIKE 'status'");
if ($colInfo) {
    $col = $colInfo->fetch_assoc();
    $isWideEnough = $col && preg_match('/^(?:var)?char\((\d+)\)/i', $col['Type'], $m) && (int)$m[1] >= 20;
    if ($col && !$isWideEnough) {
        // Covers both a too-narrow VARCHAR and a too-restrictive ENUM.
        $conn->query("ALTER TABLE appointments MODIFY status VARCHAR(20) NOT NULL DEFAULT 'Pending'");
    }
}

$upd = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
$upd->bind_param("si", $status, $jobId);
if (!$upd->execute()) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Update failed."]);
    exit;
}

echo json_encode(["status" => "success", "message" => "Job status updated to $status."]);

// ── Best-effort customer email notification (non-blocking) ───────
try {
    $cStmt = $conn->prepare("SELECT email, full_name, notif_email FROM users WHERE id = ? LIMIT 1");
    $cStmt->bind_param("i", $job['user_id']);
    $cStmt->execute();
    $customer = $cStmt->get_result()->fetch_assoc();

    if ($customer && (int)($customer['notif_email'] ?? 0) === 1 && !empty($customer['email'])) {
        $vehicle = trim(($job['brand'] ?? '') . ' ' . ($job['model'] ?? ''));
        $html = '
        <div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
            <div style="background:#fff;border-radius:12px;max-width:520px;margin:auto;padding:30px;border:1px solid #e3e8ef;">
                <h2 style="color:#e62429;margin:0;text-align:center;">EJR Garage</h2>
                <p style="color:#5f6b7a;text-align:center;margin:4px 0 24px;">Appointment Update</p>
                <p style="color:#1e2a36;">Hi ' . htmlspecialchars($customer['full_name'] ?? 'there') . ',</p>
                <p style="color:#5f6b7a;">Your mechanic just updated your appointment to <strong>' . htmlspecialchars($status) . '</strong>.</p>
                <p style="color:#5f6b7a;">Vehicle: ' . htmlspecialchars($vehicle) . '<br>Service: ' . htmlspecialchars($job['service_type'] ?? '') . '</p>
            </div>
        </div>';
        sendEmailViaBrevo($customer['email'], $customer['full_name'] ?? 'Customer', "EJR Garage - Appointment {$status}", $html);
    }
} catch (Throwable $e) {
    error_log("EJR MECH NOTIFY: " . $e->getMessage());
}