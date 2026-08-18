<?php
/**
 * EJR Garage — Mechanic: Submit Leave Request
 *
 * Companion to update_availability.php, but instead of instantly
 * flipping the mechanic's status, this files a request that shows up
 * pending in the admin dashboard's Mechanics tab until an admin
 * approves or rejects it (see api/admin/manage_leave_requests.php).
 *
 * Uses the same self-healing mechanic<->user linkage as
 * update_availability.php / get_mechanic_jobs.php.
 *
 * Body: {start_date: 'YYYY-MM-DD', end_date: 'YYYY-MM-DD', reason: ''}
 * Response: {status, message, id}
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

$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$startDate = trim((string)($input['start_date'] ?? ''));
$endDate   = trim((string)($input['end_date'] ?? ''));
$reason    = trim((string)($input['reason'] ?? ''));

$dateRe = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($dateRe, $startDate) || !preg_match($dateRe, $endDate)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "start_date and end_date must be YYYY-MM-DD."]);
    exit;
}
if (strtotime($endDate) < strtotime($startDate)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "End date cannot be before start date."]);
    exit;
}

// ── Self-healing schema (same tables as update_availability.php) ─
$conn->query("CREATE TABLE IF NOT EXISTS mechanics (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    specialty  VARCHAR(150) DEFAULT '',
    status     ENUM('available','unavailable','on_break','on_duty') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$colCheck = $conn->query("SHOW COLUMNS FROM mechanics LIKE 'user_id'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE mechanics ADD COLUMN user_id INT NULL");
}

$conn->query("CREATE TABLE IF NOT EXISTS leave_requests (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    mechanic_id    INT NOT NULL,
    mechanic_name  VARCHAR(100) NOT NULL,
    start_date     DATE NOT NULL,
    end_date       DATE NOT NULL,
    reason         TEXT,
    status         ENUM('pending','approved','rejected') DEFAULT 'pending',
    admin_notes    VARCHAR(255) DEFAULT NULL,
    requested_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at    TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Resolve this login to a mechanics roster row ─────────────────
$mechId = null;
$mechName = 'Mechanic';

$stmt = $conn->prepare("SELECT id, name FROM mechanics WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if ($row) {
    $mechId   = (int)$row['id'];
    $mechName = $row['name'];
}

if ($mechId === null) {
    $uStmt = $conn->prepare("SELECT full_name FROM users WHERE id = ? LIMIT 1");
    $uStmt->bind_param("i", $userId);
    $uStmt->execute();
    $u = $uStmt->get_result()->fetch_assoc();
    $fullName = $u['full_name'] ?? 'Mechanic';

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
        $ins = $conn->prepare("INSERT INTO mechanics (name, user_id, status) VALUES (?, ?, 'available')");
        $ins->bind_param("si", $fullName, $userId);
        $ins->execute();
        $mechId = (int)$conn->insert_id;
    }
    $mechName = $fullName;
}

// ── Insert the leave request ──────────────────────────────────────
$ins = $conn->prepare("INSERT INTO leave_requests (mechanic_id, mechanic_name, start_date, end_date, reason) VALUES (?, ?, ?, ?, ?)");
$ins->bind_param("issss", $mechId, $mechName, $startDate, $endDate, $reason);

if (!$ins->execute()) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Could not submit leave request."]);
    exit;
}

echo json_encode([
    "status"  => "success",
    "message" => "Leave request submitted. Waiting for admin approval.",
    "id"      => $conn->insert_id,
]);
