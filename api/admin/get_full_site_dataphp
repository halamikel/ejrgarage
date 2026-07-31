<?php
session_start();
session_write_close(); // ✅ add this line
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');
require_once '../db_config.php';

try {
    $data = ['users' => [], 'appointments' => [], 'vehicles' => []];

    // 1. Fetch Users
    $userResult = $conn->query("
        SELECT id, full_name, email, phone, role_id, created_at 
        FROM users 
        ORDER BY id ASC
    ");
    if ($userResult) while ($row = $userResult->fetch_assoc()) $data['users'][] = $row;

    // 2. Fetch Appointments with vehicle info
    $appResult = $conn->query("
        SELECT a.*, u.full_name,
               CONCAT(v.brand, ' ', v.model, ' (', v.plate_number, ')') AS vehicle_info
        FROM appointments a 
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN vehicles v ON a.vehicle_id = v.id
        ORDER BY a.appointment_date DESC
    ");
    if ($appResult) while ($row = $appResult->fetch_assoc()) $data['appointments'][] = $row;

    // 3. Fetch Vehicles
    $vehResult = $conn->query("
        SELECT v.*, u.full_name AS owner_name 
        FROM vehicles v 
        LEFT JOIN users u ON v.user_id = u.id
    ");
    if ($vehResult) while ($row = $vehResult->fetch_assoc()) $data['vehicles'][] = $row;

    echo json_encode(['status' => 'success'] + $data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
}
?>