<?php
/**
 * EJR Garage — Mechanic Customer History API
 *
 * Feeds the mobile app's mechanic-side CustomerDetailsScreen "Repair
 * History" card. Returns a customer's completed appointments.
 *
 * Security: a mechanic can only pull history for a customer they've
 * actually been assigned to at least once (checked via
 * appointments.mechanic_id + appointments.user_id) — not an arbitrary
 * user_id passed from the app.
 *
 * Query param: ?user_id=int
 * Response: {status, history: [{id, service_type, appointment_date,
 * status, vehicle_brand, vehicle_model, plate_number, mechanic_name}]}
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

$customerId = (int)($_GET['user_id'] ?? 0);
if (!$customerId) {
    echo json_encode(["status" => "error", "message" => "Missing user_id."]);
    exit;
}

// ── Confirm this mechanic has actually served this customer before ──
$authCheck = $conn->prepare("
    SELECT a.id
    FROM appointments a
    LEFT JOIN mechanics m ON m.id = a.mechanic_id
    WHERE m.user_id = ? AND a.user_id = ?
    LIMIT 1
");
$authCheck->bind_param("ii", $userId, $customerId);
$authCheck->execute();
if (!$authCheck->get_result()->fetch_assoc()) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "You have not been assigned to this customer."]);
    exit;
}

$historyStmt = $conn->prepare("
    SELECT a.id, a.service_type, a.appointment_date, a.status,
           v.brand AS vehicle_brand, v.model AS vehicle_model, v.plate_number,
           m.name AS mechanic_name
    FROM appointments a
    LEFT JOIN vehicles v ON v.id = a.vehicle_id
    LEFT JOIN mechanics m ON m.id = a.mechanic_id
    WHERE a.user_id = ? AND a.status = 'Completed'
    ORDER BY a.appointment_date DESC
");
$historyStmt->bind_param("i", $customerId);
$historyStmt->execute();
$result = $historyStmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) $history[] = $row;

echo json_encode(["status" => "success", "history" => $history]);
