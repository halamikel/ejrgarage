<?php
session_start();
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');
require_once '../db_config.php';
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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Sync current_bookings from appointments table
    $conn->query("
        INSERT INTO appointment_dates (appointment_date, current_bookings, is_busy)
        SELECT 
            DATE(a.appointment_date),
            COUNT(*),
            0
        FROM appointments a
        WHERE a.status NOT IN ('Cancelled')
        GROUP BY DATE(a.appointment_date)
        ON DUPLICATE KEY UPDATE
            current_bookings = VALUES(current_bookings),
            is_busy = IF(VALUES(current_bookings) >= slot_limit, 1, is_busy)
    ");

    $result = $conn->query("
        SELECT * FROM appointment_dates 
        ORDER BY appointment_date ASC
    ");

    $dates = [];
    while ($row = $result->fetch_assoc()) $dates[] = $row;

    echo json_encode(['status' => 'success', 'dates' => $dates]);
    exit;
}

$data   = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if ($action === 'block') {
    $date  = $data['date']       ?? '';
    $limit = (int)($data['limit'] ?? 5);

    if (!$date) {
        echo json_encode(['status' => 'error', 'message' => 'Date is required.']);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO appointment_dates (appointment_date, slot_limit, current_bookings, is_busy)
        VALUES (?, ?, 0, 1)
        ON DUPLICATE KEY UPDATE slot_limit = ?, is_busy = 1
    ");
    $stmt->bind_param("sii", $date, $limit, $limit);
    $stmt->execute();
    echo json_encode(['status' => 'success', 'message' => 'Date blocked successfully.']);
    exit;
}

if ($action === 'update_limit') {
    $id    = (int)$data['id'];
    $limit = (int)$data['limit'];

    $stmt = $conn->prepare("
        UPDATE appointment_dates 
        SET slot_limit = ?, is_busy = IF(current_bookings >= ?, 1, 0)
        WHERE id = ?
    ");
    $stmt->bind_param("iii", $limit, $limit, $id);
    $stmt->execute();
    echo json_encode(['status' => 'success', 'message' => 'Slot limit updated.']);
    exit;
}

if ($action === 'toggle_busy') {
    $id       = (int)$data['id'];
    $is_busy  = (int)$data['is_busy'];

    $stmt = $conn->prepare("UPDATE appointment_dates SET is_busy = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_busy, $id);
    $stmt->execute();
    echo json_encode(['status' => 'success', 'message' => 'Status updated.']);
    exit;
}

if ($action === 'delete') {
    $id = (int)$data['id'];
    $stmt = $conn->prepare("DELETE FROM appointment_dates WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(['status' => 'success', 'message' => 'Date removed.']);
    exit;
}

if ($action === 'edit') {
    $id       = (int)$data['id'];
    $date     = $data['date']    ?? '';
    $limit    = (int)$data['limit'];
    $is_busy  = (int)$data['is_busy'];

    if (!$date) {
        echo json_encode(['status' => 'error', 'message' => 'Date is required.']);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE appointment_dates 
        SET appointment_date = ?, slot_limit = ?, is_busy = ?
        WHERE id = ?
    ");
    $stmt->bind_param("siii", $date, $limit, $is_busy, $id);
    $stmt->execute();
    echo json_encode(['status' => 'success', 'message' => 'Date updated successfully.']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
?>