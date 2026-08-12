<?php
/**
 * EJR Garage — Mechanic Jobs API
 *
 * Feeds the mobile app's mechanic dashboard (DashboardScreen / api_service.dart
 * getMechanicJobs()). Returns the appointments assigned to the logged-in
 * mechanic via the "mechanics" roster table (appointments.mechanic_id).
 *
 * Self-healing: a mechanic who logs in (role_id = 3) may not yet have a row
 * in the "mechanics" roster the admin dashboard uses for assignment. Rather
 * than error out, we auto-link (by matching full_name) or auto-create the
 * roster row on first load — same "self-healing schema" pattern used in
 * api/admin/get_full_site_data.php and api/admin/manage_mechanics.php.
 *
 * Response: {status, jobs: [{id, service_type, vehicle_brand, vehicle_model,
 * plate_number, customer_name, customer_phone, appointment_date, status}]}
 */
header('Content-Type: application/json');
require_once 'db_config.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined("JWT_SECRET")) require_once __DIR__ . "/env.php";
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';

if (!preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Token missing"]);
    exit;
}

try {
    $decoded = JWT::decode($matches[1], new Key(JWT_SECRET, 'HS256'));
    $userId  = (int)$decoded->uid;
    $role    = (int)($decoded->role ?? 0);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized: " . $e->getMessage()]);
    exit;
}

if ($role !== 3) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Mechanic access required."]);
    exit;
}

// ── Self-healing schema ─────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS mechanics (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    specialty  VARCHAR(150) DEFAULT '',
    status     ENUM('available','unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$colCheck = $conn->query("SHOW COLUMNS FROM mechanics LIKE 'user_id'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE mechanics ADD COLUMN user_id INT NULL");
}

$colCheck = $conn->query("SHOW COLUMNS FROM appointments LIKE 'mechanic_id'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE appointments ADD COLUMN mechanic_id INT NULL");
}

// ── Resolve this login to a mechanics roster row ─────────────────
$mechId = null;

$stmt = $conn->prepare("SELECT id FROM mechanics WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if ($row) {
    $mechId = (int)$row['id'];
}

if ($mechId === null) {
    // Fetch this user's name so we can match/create the roster entry.
    $uStmt = $conn->prepare("SELECT full_name FROM users WHERE id = ? LIMIT 1");
    $uStmt->bind_param("i", $userId);
    $uStmt->execute();
    $u = $uStmt->get_result()->fetch_assoc();
    $fullName = $u['full_name'] ?? 'Mechanic';

    // Try matching an existing unlinked roster row by name (e.g. admin
    // added "Juan Dela Cruz" to the roster before he ever logged in).
    $mStmt = $conn->prepare("SELECT id FROM mechanics WHERE name = ? AND user_id IS NULL LIMIT 1");
    $mStmt->bind_param("s", $fullName);
    $mStmt->execute();
    $m = $mStmt->get_result()->fetch_assoc();

    if ($m) {
        $mechId = (int)$m['id'];
        $link = $conn->prepare("UPDATE mechanics SET user_id = ? WHERE id = ?");
        $link->bind_param("ii", $userId, $mechId);
        $link->execute();
    } else {
        // No roster row yet — create one so this mechanic account works
        // immediately without requiring a manual admin step first.
        $ins = $conn->prepare("INSERT INTO mechanics (name, user_id, status) VALUES (?, ?, 'available')");
        $ins->bind_param("si", $fullName, $userId);
        $ins->execute();
        $mechId = (int)$conn->insert_id;
    }
}

// ── Fetch assigned jobs ───────────────────────────────────────────
$query = "SELECT a.id, a.user_id, a.service_type, a.appointment_date, a.status, 
          v.brand AS vehicle_brand, v.model AS vehicle_model, v.plate_number, 
          u.full_name AS customer_name, u.phone AS customer_phone, 
          u.profile_picture AS customer_profile_picture
FROM appointments a
LEFT JOIN vehicles v ON a.vehicle_id = v.id
LEFT JOIN users u ON a.user_id = u.id
WHERE a.mechanic_id = ?
ORDER BY a.appointment_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $mechId);
$stmt->execute();
$result = $stmt->get_result();

$jobs = [];
while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}

echo json_encode(["status" => "success", "jobs" => $jobs]);