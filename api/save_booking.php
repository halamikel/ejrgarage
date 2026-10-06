<?php
header("Content-Type: application/json");
include 'db_config.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';
require_once 'mailer_helper.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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

// ── GET USER INFO ─────────────────────────────────────────────
$userStmt = $conn->prepare("SELECT email, full_name, notif_email FROM users WHERE id = ? LIMIT 1");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    exit;
}

// ── PARSE INPUT ───────────────────────────────────────────────
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["status" => "error", "message" => "No data received"]);
    exit;
}

try {
    $conn->begin_transaction();

    $v        = $data['vehicle'];
    $services = is_array($data['services']) ? implode(", ", $data['services']) : $data['services'];
    $notes    = trim((string)($v['notes'] ?? ''));
    $contact  = trim((string)($v['contact'] ?? ''));
    $address  = trim((string)($v['address'] ?? ''));

    // Self-healing: the "Additional Notes" field on the booking form
    // (booking-details.php) was being captured and sent here all along,
    // but this table never had a column to store it in — see the admin
    // dashboard's Appointments tab, which now surfaces it.
    $notesColCheck = $conn->query("SHOW COLUMNS FROM appointments LIKE 'notes'");
    if ($notesColCheck && $notesColCheck->num_rows === 0) {
        $conn->query("ALTER TABLE appointments ADD COLUMN notes TEXT NULL");
    }

    // Self-healing: contact number + address collected on the new
    // "Your Details" section of booking-details.php.
    $contactColCheck = $conn->query("SHOW COLUMNS FROM appointments LIKE 'contact_number'");
    if ($contactColCheck && $contactColCheck->num_rows === 0) {
        $conn->query("ALTER TABLE appointments ADD COLUMN contact_number VARCHAR(15) NULL");
    }
    $addressColCheck = $conn->query("SHOW COLUMNS FROM appointments LIKE 'address'");
    if ($addressColCheck && $addressColCheck->num_rows === 0) {
        $conn->query("ALTER TABLE appointments ADD COLUMN address TEXT NULL");
    }

    // ── CHECK / INSERT VEHICLE ────────────────────────────────
    $checkStmt = $conn->prepare("SELECT id FROM vehicles WHERE plate_number = ? LIMIT 1");
    $checkStmt->bind_param("s", $v['plate']);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $vehicleId = $row['id'];
    } else {
        $stmtVeh = $conn->prepare(
            "INSERT INTO vehicles (user_id, brand, model, year_model, plate_number, transmission, fuel_type)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmtVeh->bind_param("issssss",
            $userId, $v['brand'], $v['model'], $v['year'], $v['plate'], $v['transmission'], $v['fuel']
        );
        if (!$stmtVeh->execute()) {
            throw new Exception("Vehicle Save Failed: " . $stmtVeh->error);
        }
        $vehicleId = $conn->insert_id;
    }

    // ── PER-CAR APPOINTMENT LIMIT ─────────────────────────────
    // A single vehicle can have at most 2 appointments open at once
    // (Pending or Confirmed). Completed/Cancelled ones don't count
    // toward the limit, so once one wraps up the customer can book
    // again for that same car.
    $activeCountStmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM appointments
        WHERE vehicle_id = ?
          AND status NOT IN ('Completed', 'Cancelled')
    ");
    $activeCountStmt->bind_param("i", $vehicleId);
    $activeCountStmt->execute();
    $activeCount = (int)($activeCountStmt->get_result()->fetch_assoc()['cnt'] ?? 0);

    if ($activeCount >= 2) {
        $conn->rollback();
        echo json_encode([
            "status"  => "error",
            "message" => "This vehicle already has 2 ongoing appointments. Please wait until one is completed or cancelled before booking another for this car."
        ]);
        exit;
    }

    // ── PREFERRED MECHANIC (optional) ─────────────────────────
    // The app sends the chosen roster id as "mechanic_id" (top level, or
    // inside "vehicle"). Empty / 0 / invalid = leave unassigned so an
    // admin can assign one later.
    $colMech = $conn->query("SHOW COLUMNS FROM appointments LIKE 'mechanic_id'");
    if ($colMech && $colMech->num_rows === 0) {
        $conn->query("ALTER TABLE appointments ADD COLUMN mechanic_id INT NULL");
    }

    $mechanicId = (int)($data['mechanic_id'] ?? ($v['mechanic_id'] ?? 0));
    if ($mechanicId > 0) {
        // Only accept a mechanic that exists, isn't archived, and is still available.
        $mchk = $conn->prepare(
            "SELECT id FROM mechanics
             WHERE id = ? AND status = 'available'
               AND (is_archived IS NULL OR is_archived = 0) LIMIT 1"
        );
        $mchk->bind_param("i", $mechanicId);
        $mchk->execute();
        if (!$mchk->get_result()->fetch_assoc()) {
            $mechanicId = 0; // stale pick -> fall back to "we'll assign one"
        }
    }
    $mechanicParam = $mechanicId > 0 ? $mechanicId : null;

    // ── SAVE APPOINTMENT ──────────────────────────────────────
    $stmtApp = $conn->prepare(
        "INSERT INTO appointments (user_id, vehicle_id, service_type, appointment_date, notes, contact_number, address, mechanic_id, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')"
    );
    $stmtApp->bind_param("iisssssi", $userId, $vehicleId, $services, $v['appointmentDate'], $notes, $contact, $address, $mechanicParam);

    if (!$stmtApp->execute()) {
        throw new Exception("Appointment Save Failed: " . $stmtApp->error);
    }

    $conn->commit();

    /**
     * BUGFIX: Same pattern as register.php — the email send was happening
     * BEFORE the JSON success response was echoed. On InfinityFree's free
     * tier, set_time_limit() is disabled so PHP's execution limit kills
     * the script mid-SMTP-wait, producing an empty response that the
     * frontend's fetch().json() can't parse, triggering the catch block's
     * "Connection Failed" toast — even though the booking was already saved.
     * Fix: send the success response first, then attempt email.
     */
    $responseBody = json_encode(["status" => "success", "message" => "Booking Saved!"]);

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

    // ── SEND EMAIL (after response is already sent) ───────────
    if ($user['notif_email'] == 1) {
        $formattedDate = date("F j, Y", strtotime($v['appointmentDate']));
        $serviceList   = implode("</li><li style='margin-bottom:4px;'>", $data['services']);

        try {
            $emailSubject = 'EJR Garage - Appointment Booked!';
            $emailBody    = '
            <div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
                <div style="background:#fff;border-radius:12px;max-width:520px;margin:auto;padding:30px;border:1px solid #e3e8ef;">
                    <div style="text-align:center;margin-bottom:24px;">
                        <h2 style="color:#e62429;margin:0;">EJR Garage</h2>
                        <p style="color:#5f6b7a;margin:4px 0 0;">Appointment Confirmation</p>
                    </div>
                    <p style="color:#1e2a36;">Hi <strong>' . htmlspecialchars($user['full_name']) . '</strong>,</p>
                    <p style="color:#5f6b7a;">Your appointment has been successfully booked and is currently <strong>Pending</strong> approval. We will notify you once it is confirmed.</p>
                    <div style="background:#f8f9fa;border-radius:8px;padding:20px;margin:20px 0;">
                        <h4 style="color:#1e2a36;margin:0 0 12px;">Appointment Details</h4>
                        <table style="width:100%;border-collapse:collapse;font-size:14px;">
                            <tr>
                                <td style="color:#5f6b7a;padding:6px 0;width:40%;">Date</td>
                                <td style="color:#1e2a36;font-weight:bold;">' . $formattedDate . '</td>
                            </tr>
                            <tr>
                                <td style="color:#5f6b7a;padding:6px 0;">Vehicle</td>
                                <td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($v['brand'] . ' ' . $v['model'] . ' (' . $v['year'] . ')') . '</td>
                            </tr>
                            <tr>
                                <td style="color:#5f6b7a;padding:6px 0;">Plate No.</td>
                                <td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($v['plate']) . '</td>
                            </tr>
                            <tr>
                                <td style="color:#5f6b7a;padding:6px 0;vertical-align:top;">Services</td>
                                <td style="color:#1e2a36;font-weight:bold;">
                                    <ul style="margin:0;padding-left:16px;">
                                        <li style="margin-bottom:4px;">' . $serviceList . '</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <td style="color:#5f6b7a;padding:6px 0;">Status</td>
                                <td><span style="background:#fff3cd;color:#856404;padding:2px 10px;border-radius:20px;font-size:13px;font-weight:bold;">Pending</span></td>
                            </tr>
                        </table>
                    </div>
                    <div style="text-align:center;margin-top:24px;">
                        <a href="https://ejrgarage.onrender.com/my_appointments.php"
                           style="background:#e62429;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;">
                            View My Appointments
                        </a>
                    </div>
                    <p style="color:#a0aab5;font-size:11px;text-align:center;margin-top:24px;">
                        EJR Garage &mdash; Santo Tomas, Batangas
                    </p>
                </div>
            </div>';
            sendEmailViaBrevo($user['email'], $user['full_name'], $emailSubject, $emailBody);
        } catch (\Throwable $e) {
            error_log("EJR Garage MAIL ERROR (booking confirmation to " . $user['email'] . "): " . $e->getMessage());
        }
    }

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>