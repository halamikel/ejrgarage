<?php
header('Content-Type: application/json');
error_reporting(0);

require_once '../db_config.php';
require_once '../../includes/php-jwt/JWT.php';
require_once '../../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once '../mailer_helper.php';

/* ============================================================
   JWT AUTH
============================================================ */

$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$auth    = $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

$adminId = 0;
$isAdmin = false;

if (preg_match('/bearer\s(\S+)/i', $auth, $m)) {

    try {

        $decoded = JWT::decode(
            $m[1],
            new Key(JWT_SECRET, 'HS256')
        );

        $adminId = (int)($decoded->uid ?? 0);
        $role    = (int)($decoded->role ?? 0);

        $isAdmin = ($role === 2 || $adminId === 21);

    } catch (Exception $e) {
        $isAdmin = false;
    }
}

if (!$isAdmin) {

    http_response_code(403);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Admin access required.'
    ]);

    exit;
}

$current_user_id  = $adminId;
$current_admin_id = $adminId;


/* ============================================================
   READ REQUEST
============================================================ */

$raw = file_get_contents('php://input');

$data = json_decode($raw, true);

if (!is_array($data)) {
    $data = $_POST;
}

$appointment_id = isset($data['appointment_id'])
    ? (int)$data['appointment_id']
    : 0;

$new_status = isset($data['status'])
    ? trim((string)$data['status'])
    : '';

$action = isset($data['action'])
    ? trim((string)$data['action'])
    : '';


/* ============================================================
   VALIDATE APPOINTMENT ID
============================================================ */

if ($appointment_id <= 0) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid appointment ID.'
    ]);

    exit;
}


/* ============================================================
   GET CURRENT APPOINTMENT
============================================================ */

$fetch = $conn->prepare("
    SELECT
        a.id,
        a.user_id,
        a.service_type,
        a.appointment_date,
        a.status,
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

    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error.'
    ]);

    exit;
}

$fetch->bind_param(
    "i",
    $appointment_id
);

$fetch->execute();

$result = $fetch->get_result();

$appt = $result->fetch_assoc();

$fetch->close();


/* ============================================================
   APPOINTMENT NOT FOUND
============================================================ */

if (!$appt) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Appointment not found.'
    ]);

    exit;
}


/* ============================================================
   DELETE APPOINTMENT
============================================================ */

if ($action === 'delete') {

    $delete = $conn->prepare("
        DELETE FROM appointments
        WHERE id = ?
    ");

    if (!$delete) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Database error.'
        ]);

        exit;
    }

    $delete->bind_param(
        "i",
        $appointment_id
    );

    if (!$delete->execute()) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Failed to delete appointment.'
        ]);

        $delete->close();

        exit;
    }

    $delete->close();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Appointment deleted successfully.'
    ]);

    exit;
}


/* ============================================================
   STATUS REQUIRED
============================================================ */

if ($new_status === '') {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Appointment status is required.'
    ]);

    exit;
}


/* ============================================================
   ALLOWED STATUSES
============================================================ */

$allowed_statuses = [
    'Pending',
    'Confirmed',
    'In Progress',
    'Completed',
    'Cancelled',
    'No Show'
];

if (!in_array($new_status, $allowed_statuses, true)) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid appointment status.'
    ]);

    exit;
}


/* ============================================================
   ============================================================
   FINAL STATUS PROTECTION
   ============================================================
   
   Completed and Cancelled are FINAL.

   Completed:
       Completed -> Pending       ❌
       Completed -> Confirmed     ❌
       Completed -> In Progress   ❌
       Completed -> Cancelled     ❌

   Cancelled:
       Cancelled -> Pending       ❌
       Cancelled -> Confirmed     ❌
       Cancelled -> In Progress   ❌
       Cancelled -> Completed     ❌

   ============================================================
============================================================ */

$current_status = trim(
    (string)($appt['status'] ?? '')
);


/* ------------------------------------------------------------
   COMPLETED IS LOCKED
------------------------------------------------------------ */

if ($current_status === 'Completed') {

    echo json_encode([
        'status'  => 'error',
        'message' => 'This appointment is already Completed and cannot be changed.'
    ]);

    exit;
}


/* ------------------------------------------------------------
   CANCELLED IS LOCKED
------------------------------------------------------------ */

if ($current_status === 'Cancelled') {

    echo json_encode([
        'status'  => 'error',
        'message' => 'This appointment is already Cancelled and cannot be changed.'
    ]);

    exit;
}


/* ============================================================
   UPDATE STATUS
============================================================ */

/*
 * The same protection is also placed directly in SQL.
 *
 * This prevents a Completed or Cancelled appointment from
 * being changed even if two requests happen at nearly the
 * same time.
 */

$stmt = $conn->prepare("
    UPDATE appointments
    SET status = ?
    WHERE id = ?
      AND status NOT IN ('Completed', 'Cancelled')
");

if (!$stmt) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error: ' . $conn->error
    ]);

    exit;
}

$stmt->bind_param(
    "si",
    $new_status,
    $appointment_id
);

if (!$stmt->execute()) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed to update appointment status.'
    ]);

    $stmt->close();

    exit;
}


/* ============================================================
   CHECK WHETHER UPDATE ACTUALLY HAPPENED
============================================================ */

if ($stmt->affected_rows <= 0) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'This appointment can no longer be changed.'
    ]);

    $stmt->close();

    exit;
}

$stmt->close();


/* ============================================================
   SUCCESS
============================================================ */

echo json_encode([
    'status'         => 'success',
    'message'        => 'Appointment status updated successfully.',
    'appointment_id' => $appointment_id,
    'new_status'     => $new_status
]);

exit;
?>