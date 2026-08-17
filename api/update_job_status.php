<?php

header('Content-Type: application/json');

require_once '../db_config.php';

/* ============================================================
   AUTHENTICATION
============================================================ */

session_start();

$userId = 0;

if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
}

if (isset($_SESSION['id'])) {
    $userId = (int)$_SESSION['id'];
}

/*
 * Also support the existing Authorization header if the
 * mechanic/mobile application is using a Bearer token.
 */

if ($userId <= 0) {

    $headers = function_exists('getallheaders')
        ? getallheaders()
        : [];

    $headers = array_change_key_case($headers, CASE_LOWER);

    $authorization =
        $headers['authorization']
        ?? $_SERVER['HTTP_AUTHORIZATION']
        ?? '';

    if (preg_match('/bearer\s+(\S+)/i', $authorization, $matches)) {

        $token = $matches[1];

        /*
         * Keep the existing token handling if your project
         * already provides it elsewhere.
         */
    }
}


/* ============================================================
   REQUIRE LOGIN
============================================================ */

if ($userId <= 0) {

    http_response_code(401);

    echo json_encode([
        'status'  => 'error',
        'message' => 'You must be logged in.'
    ]);

    exit;
}


/* ============================================================
   READ REQUEST
============================================================ */

$raw = file_get_contents('php://input');

$data = json_decode(
    $raw,
    true
);

if (!is_array($data)) {
    $data = $_POST;
}


/* ============================================================
   GET APPOINTMENT ID
============================================================ */

$jobId = isset($data['appointment_id'])
    ? (int)$data['appointment_id']
    : 0;

if ($jobId <= 0) {

    /*
     * Some existing requests may use job_id.
     */

    $jobId = isset($data['job_id'])
        ? (int)$data['job_id']
        : 0;
}


/* ============================================================
   GET NEW STATUS
============================================================ */

$status = isset($data['status'])
    ? trim((string)$data['status'])
    : '';


/* ============================================================
   VALIDATION
============================================================ */

if ($jobId <= 0) {

    http_response_code(400);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid appointment ID.'
    ]);

    exit;
}

if ($status === '') {

    http_response_code(400);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Appointment status is required.'
    ]);

    exit;
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

    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid appointment status.'
    ]);

    exit;
}


/* ============================================================
   GET APPOINTMENT + CURRENT STATUS
============================================================ */

$stmt = $conn->prepare("
    SELECT
        a.id,
        a.user_id,
        a.service_type,
        a.appointment_date,
        a.status,
        a.mechanic_id,
        v.brand,
        v.model,
        v.plate_number,
        m.user_id AS mechanic_user_id
    FROM appointments a
    LEFT JOIN vehicles v
        ON v.id = a.vehicle_id
    LEFT JOIN mechanics m
        ON m.id = a.mechanic_id
    WHERE a.id = ?
    LIMIT 1
");

if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error.'
    ]);

    exit;
}

$stmt->bind_param(
    'i',
    $jobId
);

$stmt->execute();

$result = $stmt->get_result();

$job = $result->fetch_assoc();

$stmt->close();


/* ============================================================
   APPOINTMENT NOT FOUND
============================================================ */

if (!$job) {

    http_response_code(404);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Appointment not found.'
    ]);

    exit;
}


/* ============================================================
   CHECK MECHANIC ASSIGNMENT
============================================================ */

$assignedMechanicUserId =
    (int)($job['mechanic_user_id'] ?? 0);

if ($assignedMechanicUserId !== $userId) {

    http_response_code(403);

    echo json_encode([
        'status'  => 'error',
        'message' => 'This job is not assigned to you.'
    ]);

    exit;
}


/* ============================================================
   ============================================================
   FINAL STATUS PROTECTION
   ============================================================
   
   COMPLETED and CANCELLED are permanent.

   Completed -> Pending       ❌
   Completed -> Confirmed     ❌
   Completed -> In Progress   ❌
   Completed -> Cancelled     ❌

   Cancelled -> Pending       ❌
   Cancelled -> Confirmed     ❌
   Cancelled -> In Progress   ❌
   Cancelled -> Completed     ❌

============================================================ */

$currentStatus = trim(
    (string)($job['status'] ?? '')
);


/* ------------------------------------------------------------
   COMPLETED
------------------------------------------------------------ */

if ($currentStatus === 'Completed') {

    http_response_code(409);

    echo json_encode([
        'status'  => 'error',
        'message' => 'This appointment is already Completed and cannot be changed.'
    ]);

    exit;
}


/* ------------------------------------------------------------
   CANCELLED
------------------------------------------------------------ */

if ($currentStatus === 'Cancelled') {

    http_response_code(409);

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
 * The protection is also inside the SQL statement.
 *
 * This means that even if another request attempts to modify
 * the appointment at the exact same time, Completed and
 * Cancelled appointments cannot be overwritten.
 */

$update = $conn->prepare("
    UPDATE appointments
    SET status = ?
    WHERE id = ?
      AND status NOT IN ('Completed', 'Cancelled')
");

if (!$update) {

    http_response_code(500);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error.'
    ]);

    exit;
}

$update->bind_param(
    'si',
    $status,
    $jobId
);

if (!$update->execute()) {

    http_response_code(500);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed to update appointment status.'
    ]);

    $update->close();

    exit;
}


/* ============================================================
   CHECK UPDATE RESULT
============================================================ */

if ($update->affected_rows <= 0) {

    http_response_code(409);

    echo json_encode([
        'status'  => 'error',
        'message' => 'This appointment can no longer be changed.'
    ]);

    $update->close();

    exit;
}

$update->close();


/* ============================================================
   SUCCESS
============================================================ */

echo json_encode([
    'status'         => 'success',
    'message'        => 'Appointment status updated successfully.',
    'appointment_id' => $jobId,
    'new_status'     => $status
]);

exit;
?>