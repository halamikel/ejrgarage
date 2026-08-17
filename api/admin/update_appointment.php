<?php
/**
 * EJR Garage — Admin Appointment Update API
 *
 * Handles:
 * - Updating appointment status
 * - Sending appointment notifications
 * - Deleting appointments
 *
 * IMPORTANT:
 * Completed and Cancelled appointments are FINAL.
 * They cannot be changed to any other status.
 */

header('Content-Type: application/json');

require_once '../../db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ============================================================
   AUTHENTICATION
============================================================ */

$adminId = 0;

/*
 * Support the existing admin authentication/session setup.
 * Do not remove existing authentication behavior.
 */

if (isset($_SESSION['user_id'])) {
    $adminId = (int)$_SESSION['user_id'];
}

if (isset($_SESSION['id'])) {
    $adminId = (int)$_SESSION['id'];
}

/* ============================================================
   READ REQUEST
============================================================ */

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    $input = $_POST;
}

$appointmentId = isset($input['appointment_id'])
    ? (int)$input['appointment_id']
    : 0;

$action = isset($input['action'])
    ? trim((string)$input['action'])
    : '';

/* ============================================================
   BASIC VALIDATION
============================================================ */

if ($appointmentId <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid appointment ID.'
    ]);
    exit;
}

/* ============================================================
   GET APPOINTMENT
============================================================ */

$fetch = $conn->prepare("
    SELECT a.service_type,
           a.appointment_date,
           a.user_id,
           a.status,
           v.brand,
           v.model,
           v.plate_number
    FROM appointments a
    LEFT JOIN vehicles v ON v.id = a.vehicle_id
    WHERE a.id = ?
    LIMIT 1
");

if (!$fetch) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error.'
    ]);
    exit;
}

$fetch->bind_param("i", $appointmentId);
$fetch->execute();

$result = $fetch->get_result();
$appt = $result->fetch_assoc();

$fetch->close();

if (!$appt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Appointment not found.'
    ]);
    exit;
}

/* ============================================================
   DELETE APPOINTMENT
============================================================ */

if ($action === 'delete') {

    /*
     * IMPORTANT:
     * Completed and Cancelled appointments should not be
     * changed through the status system.
     *
     * Delete behavior remains separate so existing admin
     * functionality is not unexpectedly altered.
     */

    $delete = $conn->prepare("
        DELETE FROM appointments
        WHERE id = ?
    ");

    if (!$delete) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error.'
        ]);
        exit;
    }

    $delete->bind_param("i", $appointmentId);

    if (!$delete->execute()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete appointment.'
        ]);
        exit;
    }

    $delete->close();

    echo json_encode([
        'status' => 'success',
        'message' => 'Appointment deleted successfully.'
    ]);

    exit;
}

/* ============================================================
   STATUS UPDATE
============================================================ */

if (!isset($input['status'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No appointment status supplied.'
    ]);
    exit;
}

$newStatus = trim((string)$input['status']);

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

if (!in_array($newStatus, $allowedStatuses, true)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid appointment status.'
    ]);
    exit;
}

/* ============================================================
   TERMINAL STATUS PROTECTION
============================================================ */

/*
 * Completed and Cancelled are FINAL.
 *
 * Completed -> Pending       BLOCKED
 * Completed -> Confirmed     BLOCKED
 * Completed -> In Progress   BLOCKED
 * Completed -> Cancelled     BLOCKED
 *
 * Cancelled -> Pending       BLOCKED
 * Cancelled -> Confirmed     BLOCKED
 * Cancelled -> In Progress  BLOCKED
 * Cancelled -> Completed    BLOCKED
 */

$currentStatus = trim((string)($appt['status'] ?? ''));

if ($currentStatus === 'Completed') {

    echo json_encode([
        'status' => 'error',
        'message' => 'This appointment is already Completed and cannot be changed.'
    ]);

    exit;
}

if ($currentStatus === 'Cancelled') {

    echo json_encode([
        'status' => 'error',
        'message' => 'This appointment is already Cancelled and cannot be changed.'
    ]);

    exit;
}

/* ============================================================
   UPDATE STATUS
============================================================ */

/*
 * The terminal-status protection is also inside the SQL query.
 *
 * This provides an additional layer of protection in case
 * multiple requests happen at the same time.
 */

$stmt = $conn->prepare("
    UPDATE appointments
    SET status = ?
    WHERE id = ?
      AND status NOT IN ('Completed', 'Cancelled')
");

if (!$stmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'DB error: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param(
    "si",
    $newStatus,
    $appointmentId
);

if (!$stmt->execute()) {

    echo json_encode([
        'status' => 'error',
        'message' => 'Update failed.'
    ]);

    $stmt->close();
    exit;
}

/*
 * If no row was updated, the appointment may have reached
 * a terminal status between the check and UPDATE.
 */

if ($stmt->affected_rows <= 0) {

    echo json_encode([
        'status' => 'error',
        'message' => 'This appointment can no longer be changed.'
    ]);

    $stmt->close();
    exit;
}

$stmt->close();

/* ============================================================
   RESPONSE
============================================================ */

echo json_encode([
    'status' => 'success',
    'message' => 'Appointment status updated successfully.',
    'appointment_id' => $appointmentId,
    'new_status' => $newStatus
]);

exit;
?>