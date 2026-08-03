<?php
header("Content-Type: application/json");
require_once 'db_config.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';
require '../includes/PHPMailer/Exception.php';
require '../includes/PHPMailer/PHPMailer.php';
require '../includes/PHPMailer/SMTP.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

// ── JWT AUTH ─────────────────────────────────────────────────
if (!defined("JWT_SECRET")) require_once __DIR__ . "/env.php";
$secret_key = JWT_SECRET;
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';

if (!preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token missing']);
    exit;
}

try {
    $decoded = JWT::decode($matches[1], new Key($secret_key, 'HS256'));
    $userId  = $decoded->uid ?? $decoded->id;
} catch (\Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// ── GET INPUT ─────────────────────────────────────────────────
$data           = json_decode(file_get_contents("php://input"), true);
$appointment_id = $data['id'] ?? null;

if (!$appointment_id) {
    echo json_encode(["status" => "error", "message" => "Missing appointment ID"]);
    exit;
}

// ── FETCH APPOINTMENT + USER INFO BEFORE CANCELLING ──────────
$fetchStmt = $conn->prepare("
    SELECT a.service_type, a.appointment_date, a.status,
           u.email, u.full_name, u.notif_email,
           v.brand, v.model, v.plate_number
    FROM appointments a
    JOIN users u ON u.id = a.user_id
    LEFT JOIN vehicles v ON v.id = a.vehicle_id
    WHERE a.id = ? AND a.user_id = ? LIMIT 1
");
$fetchStmt->bind_param("ii", $appointment_id, $userId);
$fetchStmt->execute();
$appt = $fetchStmt->get_result()->fetch_assoc();

if (!$appt) {
    echo json_encode(["status" => "error", "message" => "Appointment not found."]);
    exit;
}

if ($appt['status'] !== 'Pending') {
    echo json_encode(["status" => "error", "message" => "Could not cancel. Appointment may already be processed."]);
    exit;
}

// ── CANCEL APPOINTMENT ────────────────────────────────────────
$stmt = $conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND status = 'Pending'");
$stmt->bind_param("ii", $appointment_id, $userId);
$stmt->execute();

if ($stmt->affected_rows <= 0) {
    echo json_encode(["status" => "error", "message" => "Could not cancel. Appointment may already be processed."]);
    exit;
}

// ── SEND CANCELLATION EMAIL ───────────────────────────────────
if ($appt['notif_email'] == 1) {
    $formattedDate = date("F j, Y", strtotime($appt['appointment_date']));
    $vehicle       = trim(($appt['brand'] ?? '') . ' ' . ($appt['model'] ?? ''));
    $plate         = $appt['plate_number'] ?? 'N/A';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
            $mail->Timeout    = 15;
        $mail->setFrom(SMTP_USER, 'EJR Garage');
        $mail->addAddress($appt['email'], $appt['full_name']);
        $mail->isHTML(true);
        $mail->Subject = 'EJR Garage - Appointment Cancelled';
        $mail->Body    = '
        <div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
            <div style="background:#fff;border-radius:12px;max-width:520px;margin:auto;padding:30px;border:1px solid #e3e8ef;">
                <div style="text-align:center;margin-bottom:24px;">
                    <h2 style="color:#e62429;margin:0;">EJR Garage</h2>
                    <p style="color:#5f6b7a;margin:4px 0 0;">Appointment Cancellation</p>
                </div>
                <p style="color:#1e2a36;">Hi <strong>' . htmlspecialchars($appt['full_name']) . '</strong>,</p>
                <p style="color:#5f6b7a;">Your appointment has been <strong>cancelled</strong> as requested. Here are the details of the cancelled booking:</p>
                <div style="background:#f8f9fa;border-radius:8px;padding:20px;margin:20px 0;">
                    <h4 style="color:#1e2a36;margin:0 0 12px;">Cancelled Appointment</h4>
                    <table style="width:100%;border-collapse:collapse;font-size:14px;">
                        <tr>
                            <td style="color:#5f6b7a;padding:6px 0;width:40%;">Date</td>
                            <td style="color:#1e2a36;font-weight:bold;">' . $formattedDate . '</td>
                        </tr>
                        <tr>
                            <td style="color:#5f6b7a;padding:6px 0;">Vehicle</td>
                            <td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($vehicle) . '</td>
                        </tr>
                        <tr>
                            <td style="color:#5f6b7a;padding:6px 0;">Plate No.</td>
                            <td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($plate) . '</td>
                        </tr>
                        <tr>
                            <td style="color:#5f6b7a;padding:6px 0;">Services</td>
                            <td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($appt['service_type']) . '</td>
                        </tr>
                        <tr>
                            <td style="color:#5f6b7a;padding:6px 0;">Status</td>
                            <td><span style="background:#f8d7da;color:#842029;padding:2px 10px;border-radius:20px;font-size:13px;font-weight:bold;">Cancelled</span></td>
                        </tr>
                    </table>
                </div>
                <p style="color:#5f6b7a;">If you would like to rebook, you can do so anytime from your account.</p>
                <div style="text-align:center;margin-top:24px;">
                    <a href="https://ejrgarage.onrender.com/booking.php"
                       style="background:#e62429;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;">
                        Book Again
                    </a>
                </div>
                <p style="color:#a0aab5;font-size:11px;text-align:center;margin-top:24px;">
                    EJR Garage &mdash; Santo Tomas, Batangas
                </p>
            </div>
        </div>';
        $mail->send();
    } catch (MailException $e) {
        // Silent — cancellation already saved
    }
}


    // BUGFIX: Respond to browser BEFORE sending email so PHP execution
    // timeout can't produce a blank response. See save_booking.php for details.
    $responseBody = json_encode(["status" => "success", "message" => "Appointment cancelled successfully"]);
    if (function_exists('fastcgi_finish_request')) {
        echo $responseBody; fastcgi_finish_request();
    } else {
        ob_start(); echo $responseBody;
        header('Content-Length: ' . ob_get_length());
        header('Connection: close');
        ob_end_flush(); @ob_flush(); flush();
    }

echo json_encode(["status" => "success", "message" => "Appointment cancelled successfully"]);
?>