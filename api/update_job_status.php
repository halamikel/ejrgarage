<?php
/**
 * EJR Garage — Mechanic Job Status API
 *
 * Allows assigned mechanics to update their appointment status.
 *
 * IMPORTANT:
 * Completed and Cancelled appointments are FINAL.
 * They cannot be changed after reaching either status.
 */

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

if ($userId <= 0) {

    http_response_code(401);

    echo json_encode([
        "status" => "error",
        "message" => "You must be logged in."
    ]);

    exit;
}

/* ============================================================
   READ REQUEST
============================================================ */

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($data)) {
    $data = $_POST;
}

$jobId = isset($data['appointment_id'])
    ? (int)$data['appointment_id']
    : (isset($data['job_id'])
        ? (int)$data['job_id']
        : 0);

$status = isset($data['status'])
    ? trim((string)$data['status'])
    : '';

/* ============================================================
   VALIDATION
============================================================ */

if ($jobId <= 0) {

    http_response_code(400);

    echo json_encode([
        "status" => "error",
        "message" => "Invalid appointment ID."
    ]);

    exit;
}

if ($status === '') {

    http_response_code(400);

    echo json_encode([
        "status" => "error",
        "message" => "Appointment status is required."
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
        "status" => "error",
        "message" => "Invalid appointment status."
    ]);

    exit;
}

/* ============================================================
   GET JOB / APPOINTMENT
============================================================ */

$stmt = $conn->prepare("
    SELECT a.user_id,
           a.service_type,
           a.appointment_date,
           a.status,
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
        "status" => "error",
        "message" => "Database error."
    ]);

    exit;
}

$stmt->bind_param("i", $jobId);
$stmt->execute();

$result = $stmt->get_result();
$job = $result->fetch_assoc();

$stmt->close();

/* ============================================================
   APPOINTMENT EXISTS
============================================================ */

if (!$job) {

    http_response_code(404);

    echo json_encode([
        "status" => "error",
        "message" => "Appointment not found."
    ]);

    exit;
}

/* ============================================================
   MECHANIC OWNERSHIP
============================================================ */

if ((int)($job['mechanic_user_id'] ?? 0) !== $userId) {

    http_response_code(403);

    echo json_encode([
        "status" => "error",
        "message" => "This job is not assigned to you."
    ]);

    exit;
}

/* ============================================================
   TERMINAL STATUS PROTECTION
============================================================ */

/*
 * Completed and Cancelled are FINAL.
 *
 * Completed -> anything     BLOCKED
 * Cancelled -> anything     BLOCKED
 */

$currentStatus = trim((string)($job['status'] ?? ''));

if ($currentStatus === 'Completed') {

    http_response_code(409);

    echo json_encode([
        "status" => "error",
        "message" => "This appointment is already Completed and cannot be changed."
    ]);

    exit;
}

if ($currentStatus === 'Cancelled') {

    http_response_code(409);

    echo json_encode([
        "status" => "error",
        "message" => "This appointment is already Cancelled and cannot be changed."
    ]);

    exit;
}

/* ============================================================
   UPDATE STATUS
============================================================ */

/*
 * Protect the actual SQL UPDATE as well.
 */

$upd = $conn->prepare("
    UPDATE appointments
    SET status = ?
    WHERE id = ?
      AND status NOT IN ('Completed', 'Cancelled')
");

if (!$upd) {

    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "message" => "Database error."
    ]);

    exit;
}

$upd->bind_param(
    "si",
    $status,
    $jobId
);

if (!$upd->execute()) {

    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "message" => "Update failed."
    ]);

    $upd->close();
    exit;
}

/*
 * If no row changed, the appointment can no longer be modified.
 */

if ($upd->affected_rows <= 0) {

    http_response_code(409);

    echo json_encode([
        "status" => "error",
        "message" => "This appointment can no longer be changed."
    ]);

    $upd->close();
    exit;
}

$upd->close();

/* ============================================================
   SUCCESS
============================================================ */

echo json_encode([
    "status" => "success",
    "message" => "Appointment status updated successfully.",
    "appointment_id" => $jobId,
    "new_status" => $status
]);

exit;
?>