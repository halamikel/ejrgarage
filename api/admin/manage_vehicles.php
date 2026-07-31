<?php
// api/admin/manage_vehicles.php

session_start();
header('Content-Type: application/json');
require_once '../db_config.php';
require_once __DIR__ . '/../../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/php-jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function ejr_admin_authorized(): bool {
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    $auth    = $headers['authorization'] ?? '';
    if (preg_match('/bearer\s(\S+)/i', $auth, $m)) {
        try {
            $decoded = JWT::decode($m[1], new Key(JWT_SECRET, 'HS256'));
            $role = (int)($decoded->role ?? 0);
            $uid  = (int)($decoded->uid ?? 0);
            if ($role === 2 || $uid === 21) return true;
        } catch (Exception $e) {}
    }
    if (isset($_SESSION['user_id']) && ($_SESSION['role_id'] == 2 || $_SESSION['user_id'] == 21)) {
        return true;
    }
    return false;
}

if (!ejr_admin_authorized()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? 'get';

// ── GET all vehicles ─────────────────────────────────────────────
if ($action === 'get' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $result   = $conn->query("
        SELECT v.*, u.full_name AS owner_name
        FROM vehicles v
        LEFT JOIN users u ON u.id = v.user_id
        ORDER BY v.created_at DESC
    ");
    $vehicles = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['status' => 'success', 'vehicles' => $vehicles]);
    exit();
}

// ── ADD ──────────────────────────────────────────────────────────
if ($action === 'add') {
    $required = ['user_id','brand','model','year_model','plate_number','fuel_type','transmission'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            echo json_encode(['status' => 'error', 'message' => "Missing field: $field"]);
            exit();
        }
    }

    $plate = strtoupper($input['plate_number']);

    $check = $conn->prepare("SELECT id FROM vehicles WHERE plate_number = ?");
    $check->bind_param("s", $plate);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Plate number already exists.']);
        exit();
    }

    $stmt = $conn->prepare("
        INSERT INTO vehicles (user_id, brand, model, year_model, plate_number, fuel_type, transmission)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $userId       = (int)$input['user_id'];
    $brand        = $input['brand'];
    $model        = $input['model'];
    $year_model   = $input['year_model'];
    $fuel_type    = $input['fuel_type'];
    $transmission = $input['transmission'];

    $stmt->bind_param("issssss", $userId, $brand, $model, $year_model, $plate, $fuel_type, $transmission);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Vehicle added successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
    exit();
}

// ── EDIT ─────────────────────────────────────────────────────────
if ($action === 'edit') {
    if (empty($input['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Vehicle ID required.']);
        exit();
    }

    $plate = strtoupper($input['plate_number']);
    $id    = (int)$input['id'];

    $check = $conn->prepare("SELECT id FROM vehicles WHERE plate_number = ? AND id != ?");
    $check->bind_param("si", $plate, $id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Plate number already used by another vehicle.']);
        exit();
    }

    $stmt = $conn->prepare("
        UPDATE vehicles
        SET user_id = ?, brand = ?, model = ?, year_model = ?,
            plate_number = ?, fuel_type = ?, transmission = ?
        WHERE id = ?
    ");
    $userId       = (int)$input['user_id'];
    $brand        = $input['brand'];
    $model        = $input['model'];
    $year_model   = $input['year_model'];
    $fuel_type    = $input['fuel_type'];
    $transmission = $input['transmission'];

    $stmt->bind_param("issssssi", $userId, $brand, $model, $year_model, $plate, $fuel_type, $transmission, $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Vehicle updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
    exit();
}

// ── DELETE ───────────────────────────────────────────────────────
if ($action === 'delete') {
    if (empty($input['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Vehicle ID required.']);
        exit();
    }

    $id   = (int)$input['id'];
    $stmt = $conn->prepare("DELETE FROM vehicles WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Vehicle deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);