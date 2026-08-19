<?php
header('Content-Type: application/json');
require_once 'db_config.php';
require_once 'rate_limit.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';
require_once 'mailer_helper.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined("JWT_SECRET")) require_once __DIR__ . "/env.php";
$secret_key = JWT_SECRET;
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';

if (!preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

try {
    $decoded = JWT::decode($matches[1], new Key($secret_key, 'HS256'));
    $userId  = $decoded->uid;
    ejr_rate_limit($conn, "add_vehicle", ejr_get_ip() . "|" . $userId, 20, 300);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$data  = json_decode(file_get_contents('php://input'), true);
$plate = strtoupper(trim($data['plate'] ?? ''));

if (empty($plate) || empty($data['brand'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}

// Duplicate plate check
$checkStmt = $conn->prepare("SELECT id FROM vehicles WHERE plate_number = ?");
$checkStmt->bind_param("s", $plate);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "This Plate Number ($plate) is already registered."]);
    exit;
}

// Insert vehicle
$stmt = $conn->prepare(
    "INSERT INTO vehicles (user_id, brand, model, year_model, plate_number, transmission, fuel_type)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("issssss",
    $userId, $data['brand'], $data['model'], $data['year'],
    $plate, $data['transmission'], $data['fuel']
);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database Error: " . $stmt->error]);
    exit;
}

// ── RESPOND TO BROWSER IMMEDIATELY ───────────────────────────────────────
// BUGFIX: The email send below can hang for up to 15s if SMTP is unreachable.
// Respond first so the user sees success instantly regardless of email outcome.
$responseBody = json_encode(["status" => "success"]);
if (function_exists('fastcgi_finish_request')) {
    echo $responseBody;
    fastcgi_finish_request();
} else {
    ob_start();
    echo $responseBody;
    header('Content-Length: ' . ob_get_length());
    header('Connection: close');
    ob_end_flush();
    @ob_flush();
    flush();
}

// ── SEND EMAIL (after response already sent) ──────────────────────────────
$uStmt = $conn->prepare("SELECT email, full_name FROM users WHERE id = ?");
$uStmt->bind_param("i", $userId);
$uStmt->execute();
$user = $uStmt->get_result()->fetch_assoc();

if ($user) {
    try {
        $emailSubject = 'EJR Garage - Vehicle Registered';
        $emailBody    = '
        <div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
            <div style="background:#fff;border-radius:12px;max-width:520px;margin:auto;padding:30px;border:1px solid #e3e8ef;">
                <div style="text-align:center;margin-bottom:24px;">
                    <h2 style="color:#e62429;margin:0;">EJR Garage</h2>
                    <p style="color:#5f6b7a;margin:4px 0 0;">Vehicle Registration</p>
                </div>
                <p style="color:#1e2a36;">Hi <strong>' . htmlspecialchars($user['full_name']) . '</strong>,</p>
                <p style="color:#5f6b7a;">A new vehicle has been <strong>successfully registered</strong> to your account.</p>
                <div style="background:#f8f9fa;border-radius:8px;padding:20px;margin:20px 0;">
                    <h4 style="color:#1e2a36;margin:0 0 12px;">Vehicle Details</h4>
                    <table style="width:100%;border-collapse:collapse;font-size:14px;">
                        <tr><td style="color:#5f6b7a;padding:6px 0;width:40%;">Vehicle</td>
                            <td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($data['brand'] . ' ' . $data['model']) . '</td></tr>
                        <tr><td style="color:#5f6b7a;padding:6px 0;">Plate No.</td>
                            <td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($plate) . '</td></tr>
                        <tr><td style="color:#5f6b7a;padding:6px 0;">Transmission</td>
                            <td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($data['transmission']) . '</td></tr>
                    </table>
                </div>
                <div style="text-align:center;margin-top:24px;">
                    <a href="https://ejrgarage.onrender.com/booking.php"
                       style="background:#e62429;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;">
                        Book a Service
                    </a>
                </div>
            </div>
        </div>';
        sendEmailViaBrevo($user['email'], $user['full_name'], $emailSubject, $emailBody);
    } catch (\Throwable $e) {
        error_log("EJR Garage MAIL ERROR (vehicle registered to " . $user['email'] . "): " . $e->getMessage());
    }
}