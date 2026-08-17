<?php
/**
 * ============================================================
 * EJR GARAGE
 * ADMIN APPOINTMENT UPDATE API
 *
 * File:
 * api/admin/update_appointent.php
 *
 * Supports:
 *  - Admin JWT authentication
 *  - Update appointment status
 *  - Delete appointment
 *  - Email notification
 *  - SMS notification
 * ============================================================
 */

header('Content-Type: application/json; charset=utf-8');

error_reporting(0);

require_once '../db_config.php';

require_once '../../includes/php-jwt/JWT.php';
require_once '../../includes/php-jwt/Key.php';

require_once '../mailer_helper.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


/* ============================================================
   HELPER: JSON RESPONSE
   ============================================================ */

function jsonResponse($status, $message = '', $extra = [])
{
    $response = [
        'status' => $status
    ];

    if ($message !== '') {
        $response['message'] = $message;
    }

    if (!empty($extra) && is_array($extra)) {
        $response = array_merge($response, $extra);
    }

    echo json_encode($response);
    exit;
}


/* ============================================================
   JWT ADMIN AUTHENTICATION
   ============================================================ */

$headers = [];

if (function_exists('getallheaders')) {
    $headers = getallheaders();
}

$headers = array_change_key_case($headers, CASE_LOWER);

$auth =
    $headers['authorization']
    ?? $_SERVER['HTTP_AUTHORIZATION']
    ?? '';

$adminId = 0;
$isAdmin = false;


/* ------------------------------------------------------------
   Read Bearer token
------------------------------------------------------------ */

if (preg_match('/bearer\s+(\S+)/i', $auth, $matches)) {

    try {

        $decoded = JWT::decode(
            $matches[1],
            new Key(JWT_SECRET, 'HS256')
        );

        $adminId = (int)($decoded->uid ?? 0);

        $role = (int)($decoded->role ?? 0);

        /*
         * Admin:
         *
         * role_id 2
         * OR
         * special admin ID 21
         */

        $isAdmin =
            ($role === 2 || $adminId === 21);

    } catch (Exception $e) {

        $isAdmin = false;
    }
}


/* ============================================================
   ADMIN ACCESS CHECK
   ============================================================ */

if (!$isAdmin) {

    http_response_code(403);

    jsonResponse(
        'error',
        'Admin access required.'
    );
}


/*
 * Variables retained for compatibility with
 * existing admin functionality.
 */

$current_user_id  = $adminId;
$current_admin_id = $adminId;
$is_admin         = true;


/* ============================================================
   READ REQUEST BODY
   ============================================================ */

$rawInput = file_get_contents('php://input');

$data = json_decode(
    $rawInput,
    true
);


/* ------------------------------------------------------------
   Invalid JSON
------------------------------------------------------------ */

if (!is_array($data)) {

    http_response_code(400);

    jsonResponse(
        'error',
        'Invalid request data.'
    );
}


/* ============================================================
   APPOINTMENT ID
   ============================================================ */

$appointmentId = isset($data['appointment_id'])
    ? (int)$data['appointment_id']
    : 0;


if ($appointmentId <= 0) {

    http_response_code(400);

    jsonResponse(
        'error',
        'Invalid appointment ID.'
    );
}


/* ============================================================
   DELETE APPOINTMENT
   ============================================================ */

if (($data['action'] ?? '') === 'delete') {

    $deleteStmt = $conn->prepare(
        "DELETE FROM appointments WHERE id = ?"
    );

    if (!$deleteStmt) {

        http_response_code(500);

        jsonResponse(
            'error',
            'Database error while preparing delete.'
        );
    }


    $deleteStmt->bind_param(
        "i",
        $appointmentId
    );


    if (!$deleteStmt->execute()) {

        $deleteStmt->close();

        http_response_code(500);

        jsonResponse(
            'error',
            'Failed to delete appointment.'
        );
    }


    if ($deleteStmt->affected_rows <= 0) {

        $deleteStmt->close();

        http_response_code(404);

        jsonResponse(
            'error',
            'Appointment not found.'
        );
    }


    $deleteStmt->close();


    jsonResponse(
        'success',
        'Appointment deleted successfully.',
        [
            'appointment_id' => $appointmentId
        ]
    );
}


/* ============================================================
   STATUS REQUIRED
   ============================================================ */

if (!isset($data['status'])) {

    http_response_code(400);

    jsonResponse(
        'error',
        'Appointment status is required.'
    );
}


$status = trim(
    (string)$data['status']
);


if ($status === '') {

    http_response_code(400);

    jsonResponse(
        'error',
        'Appointment status cannot be empty.'
    );
}


/* ============================================================
   ALLOWED STATUSES
   ============================================================ */

$allowedStatuses = [
    'Pending',
    'Confirmed',
    'In Progress',
    'Completed',
    'Cancelled',
    'No Show'
];


if (!in_array($status, $allowedStatuses, true)) {

    http_response_code(400);

    jsonResponse(
        'error',
        'Invalid appointment status.'
    );
}


/* ============================================================
   FETCH APPOINTMENT
   ============================================================ */

$fetch = $conn->prepare("
    SELECT
        a.id,
        a.service_type,
        a.appointment_date,
        a.status,
        a.user_id,

        v.brand,
        v.model,
        v.plate_number

    FROM appointments a

    LEFT JOIN vehicles v
        ON v.id = a.vehicle_id

    WHERE a.id = ?

    LIMIT 1
");


if (!$fetch) {

    http_response_code(500);

    jsonResponse(
        'error',
        'Database error while fetching appointment.'
    );
}


$fetch->bind_param(
    "i",
    $appointmentId
);


if (!$fetch->execute()) {

    $fetch->close();

    http_response_code(500);

    jsonResponse(
        'error',
        'Failed to fetch appointment.'
    );
}


$result = $fetch->get_result();

$appt = $result->fetch_assoc();

$fetch->close();


/* ============================================================
   APPOINTMENT NOT FOUND
   ============================================================ */

if (!$appt) {

    http_response_code(404);

    jsonResponse(
        'error',
        'Appointment not found.'
    );
}


/* ============================================================
   OLD STATUS
   ============================================================ */

$oldStatus = trim(
    (string)($appt['status'] ?? '')
);


/* ============================================================
   FETCH USER
   ============================================================ */

$user = null;

$userId = (int)($appt['user_id'] ?? 0);


if ($userId > 0) {

    $userStmt = $conn->prepare("
        SELECT
            email,
            full_name,
            phone,
            notif_email,
            notif_sms

        FROM users

        WHERE id = ?

        LIMIT 1
    ");


    if ($userStmt) {

        $userStmt->bind_param(
            "i",
            $userId
        );


        if ($userStmt->execute()) {

            $userResult =
                $userStmt->get_result();

            $user =
                $userResult->fetch_assoc();
        }


        $userStmt->close();
    }
}


/* ============================================================
   UPDATE APPOINTMENT STATUS
   ============================================================ */

$updateStmt = $conn->prepare("
    UPDATE appointments

    SET status = ?

    WHERE id = ?
");


if (!$updateStmt) {

    http_response_code(500);

    jsonResponse(
        'error',
        'Database error while preparing appointment update.'
    );
}


$updateStmt->bind_param(
    "si",
    $status,
    $appointmentId
);


if (!$updateStmt->execute()) {

    $updateStmt->close();

    http_response_code(500);

    jsonResponse(
        'error',
        'Failed to update appointment.'
    );
}


$updateStmt->close();


/* ============================================================
   CHECK WHETHER STATUS ACTUALLY CHANGED
   ============================================================ */

$statusChanged =
    strcasecmp($oldStatus, $status) !== 0;


/* ============================================================
   PREPARE APPOINTMENT INFORMATION
   ============================================================ */

$appointmentDate =
    $appt['appointment_date'] ?? '';


$timestamp =
    strtotime($appointmentDate);


if ($timestamp !== false) {

    $formattedDate =
        date(
            "F j, Y",
            $timestamp
        );

} else {

    $formattedDate =
        $appointmentDate;
}


$vehicle =
    trim(
        ($appt['brand'] ?? '') .
        ' ' .
        ($appt['model'] ?? '')
    );


if ($vehicle === '') {

    $vehicle = 'N/A';
}


$plate =
    trim(
        (string)($appt['plate_number'] ?? '')
    );


if ($plate === '') {

    $plate = 'N/A';
}


$serviceType =
    trim(
        (string)($appt['service_type'] ?? '')
    );


if ($serviceType === '') {

    $serviceType = 'N/A';
}


$fullName =
    trim(
        (string)($user['full_name'] ?? 'Customer')
    );


if ($fullName === '') {

    $fullName = 'Customer';
}


$nameParts =
    preg_split(
        '/\s+/',
        $fullName
    );


$firstName =
    $nameParts[0] ?? 'Customer';


/* ============================================================
   SUCCESS RESPONSE
   ============================================================ */

$response = [
    'appointment_id' => $appointmentId,
    'old_status'     => $oldStatus,
    'status'         => $status,
    'status_changed' => $statusChanged
];


/*
 * We can safely return the success response before sending
 * email/SMS. This prevents the dashboard from waiting on
 * external notification services.
 */

echo json_encode([
    'status' => 'success',
    'message' => 'Appointment updated successfully.',
    ...$response
]);


/*
 * Stop PHP from continuing if the server supports
 * finishing the response early.
 */

if (function_exists('fastcgi_finish_request')) {

    fastcgi_finish_request();
}


/* ============================================================
   DO NOT SEND NOTIFICATIONS IF STATUS DID NOT CHANGE
   ============================================================ */

if (!$statusChanged) {

    exit;
}


/* ============================================================
   SEND EMAIL
   ============================================================ */

if (
    $user &&
    isset($user['notif_email']) &&
    (int)$user['notif_email'] === 1 &&
    !empty($user['email'])
) {

    $badgeColors = [

        'Confirmed' =>
            'background:#d1e7dd;color:#0a3622;',

        'Completed' =>
            'background:#cfe2ff;color:#084298;',

        'Cancelled' =>
            'background:#f8d7da;color:#842029;',

        'In Progress' =>
            'background:#fff3cd;color:#856404;',

        'Pending' =>
            'background:#e2e3e5;color:#383d41;',

        'No Show' =>
            'background:#f8d7da;color:#842029;'
    ];


    $badgeColor =
        $badgeColors[$status]
        ?? 'background:#fff3cd;color:#856404;';


    /*
     * Escape values before putting them into HTML.
     */

    $safeFullName =
        htmlspecialchars(
            $fullName,
            ENT_QUOTES,
            'UTF-8'
        );


    $safeVehicle =
        htmlspecialchars(
            $vehicle,
            ENT_QUOTES,
            'UTF-8'
        );


    $safePlate =
        htmlspecialchars(
            $plate,
            ENT_QUOTES,
            'UTF-8'
        );


    $safeService =
        htmlspecialchars(
            $serviceType,
            ENT_QUOTES,
            'UTF-8'
        );


    $safeStatus =
        htmlspecialchars(
            $status,
            ENT_QUOTES,
            'UTF-8'
        );


    $safeDate =
        htmlspecialchars(
            $formattedDate,
            ENT_QUOTES,
            'UTF-8'
        );


    /* --------------------------------------------------------
       Email HTML
    -------------------------------------------------------- */

    $html = '

    <div
        style="
            background:#f4f6f8;
            padding:30px;
            font-family:Arial,sans-serif;
        "
    >

        <div
            style="
                background:#fff;
                border-radius:12px;
                max-width:520px;
                margin:auto;
                padding:30px;
                border:1px solid #e3e8ef;
            "
        >

            <div
                style="
                    text-align:center;
                    margin-bottom:24px;
                "
            >

                <h2
                    style="
                        color:#e62429;
                        margin:0;
                    "
                >
                    EJR Garage
                </h2>

                <p
                    style="
                        color:#5f6b7a;
                        margin:4px 0 0;
                    "
                >
                    Appointment Update
                </p>

            </div>


            <p style="color:#1e2a36;">

                Hi
                <strong>
                    ' . $safeFullName . '
                </strong>,

            </p>


            <p style="color:#5f6b7a;">

                Your appointment status has been
                updated to:

            </p>


            <div
                style="
                    text-align:center;
                    margin:16px 0;
                "
            >

                <span
                    style="
                        ' . $badgeColor . '
                        padding:6px 20px;
                        border-radius:20px;
                        font-size:15px;
                        font-weight:bold;
                    "
                >

                    ' . $safeStatus . '

                </span>

            </div>


            <div
                style="
                    background:#f8f9fa;
                    border-radius:8px;
                    padding:20px;
                    margin:20px 0;
                "
            >

                <table
                    style="
                        width:100%;
                        border-collapse:collapse;
                        font-size:14px;
                    "
                >

                    <tr>

                        <td
                            style="
                                color:#5f6b7a;
                                padding:6px 0;
                                width:40%;
                            "
                        >
                            Date
                        </td>

                        <td
                            style="
                                color:#1e2a36;
                                font-weight:bold;
                            "
                        >
                            ' . $safeDate . '
                        </td>

                    </tr>


                    <tr>

                        <td
                            style="
                                color:#5f6b7a;
                                padding:6px 0;
                            "
                        >
                            Vehicle
                        </td>

                        <td
                            style="
                                color:#1e2a36;
                                font-weight:bold;
                            "
                        >
                            ' . $safeVehicle . '
                        </td>

                    </tr>


                    <tr>

                        <td
                            style="
                                color:#5f6b7a;
                                padding:6px 0;
                            "
                        >
                            Plate No.
                        </td>

                        <td
                            style="
                                color:#1e2a36;
                                font-weight:bold;
                            "
                        >
                            ' . $safePlate . '
                        </td>

                    </tr>


                    <tr>

                        <td
                            style="
                                color:#5f6b7a;
                                padding:6px 0;
                            "
                        >
                            Services
                        </td>

                        <td
                            style="
                                color:#1e2a36;
                                font-weight:bold;
                            "
                        >
                            ' . $safeService . '
                        </td>

                    </tr>

                </table>

            </div>


            <div
                style="
                    text-align:center;
                    margin-top:24px;
                "
            >

                <a
                    href="https://ejrgarage.onrender.com/my_appointments.php"
                    style="
                        background:#e62429;
                        color:#fff;
                        padding:12px 28px;
                        border-radius:8px;
                        text-decoration:none;
                        font-weight:bold;
                    "
                >
                    View My Appointments
                </a>

            </div>


            <p
                style="
                    color:#a0aab5;
                    font-size:11px;
                    text-align:center;
                    margin-top:24px;
                "
            >

                EJR Garage
                &mdash;
                Santo Tomas, Batangas

            </p>

        </div>

    </div>
    ';


    /* --------------------------------------------------------
       Send email
    -------------------------------------------------------- */

    error_log(
        "EJR APPT MAIL: " .
        "appt_id={$appointmentId} " .
        "user_id={$userId} " .
        "user_found=" .
        ($user ? 'yes' : 'no') .
        " notif_email=" .
        ($user['notif_email'] ?? 'n/a')
    );


    try {

        $emailSent =
            sendEmailViaBrevo(
                $user['email'],
                $fullName,
                "EJR Garage - Appointment {$status}",
                $html
            );


        error_log(
            "EJR APPT MAIL: send to " .
            $user['email'] .
            " result=" .
            ($emailSent ? 'success' : 'FAILED')
        );

    } catch (Exception $e) {

        error_log(
            "EJR APPT MAIL ERROR: " .
            $e->getMessage()
        );
    }
}


/* ============================================================
   SEND SMS VIA SEMAPHORE
   ============================================================ */

if (
    $user &&
    isset($user['notif_sms']) &&
    (int)$user['notif_sms'] === 1 &&
    !empty($user['phone'])
) {


    $statusMessages = [

        'Confirmed' =>
            "Hi {$firstName}, your appointment is CONFIRMED. See you on your scheduled date! View: ejrgarage.onrender.com/my_appointments.php",

        'Pending' =>
            "Hi {$firstName}, your appointment is PENDING review. We will confirm it shortly. View: ejrgarage.onrender.com/my_appointments.php",

        'Cancelled' =>
            "Hi {$firstName}, your appointment is CANCELLED. Please call us to reschedule. View: ejrgarage.onrender.com/my_appointments.php",

        'Completed' =>
            "Hi {$firstName}, your appointment is COMPLETED. Thank you for trusting EJR Garage!",

        'In Progress' =>
            "Hi {$firstName}, your appointment is now IN PROGRESS. Our team is currently working on your vehicle.",

        'No Show' =>
            "Hi {$firstName}, we missed you for your appointment. Please contact us to reschedule."
    ];


    if (isset($statusMessages[$status])) {

        $smsText =
            "EJR Garage: " .
            $statusMessages[$status];

    } else {

        $smsText =
            "EJR Garage: Hi {$firstName}, " .
            "your appointment on {$formattedDate} " .
            "has been updated to [{$status}]. " .
            "Vehicle: {$vehicle}.";
    }


    try {

        $smsResult =
            sendSmsViaSemaphore(
                $user['phone'],
                $smsText
            );


        if (
            !is_array($smsResult) ||
            ($smsResult['status'] ?? '') !== 'success'
        ) {

            error_log(
                "EJR APPT SMS: " .
                "appt_id={$appointmentId} " .
                "to={$user['phone']} FAILED — " .
                ($smsResult['message'] ?? 'Unknown error')
            );
        }

    } catch (Exception $e) {

        error_log(
            "EJR APPT SMS ERROR: " .
            $e->getMessage()
        );
    }
}


/* ============================================================
   END
   ============================================================ */
?>