<?php
/**
 * EJR Garage — Mechanic: My Leave Requests
 *
 * Companion to submit_leave_request.php — lets the logged-in mechanic
 * see the status of their own past/pending leave requests (the admin
 * side of this data lives in api/admin/manage_leave_requests.php).
 *
 * Uses the same self-healing mechanic<->user linkage as
 * update_availability.php / submit_leave_request.php. A mechanic who
 * has no roster row yet (hasn't toggled availability or filed a leave
 * request before) simply has no leave requests — this returns an empty
 * list rather than erroring, since there's nothing to create yet.
 *
 * Response: {status, leave_requests: [{id, start_date, end_date, reason,
 * status, admin_notes, requested_at, resolved_at}]}
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

// ── Self-healing schema (same tables as submit_leave_request.php) ─
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
$stmt = $conn->prepare("SELECT id FROM mechanics WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    // No roster row yet -> this mechanic has never filed a request.
    echo json_encode(["status" => "success", "leave_requests" => []]);
    exit;
}
$mechId = (int)$row['id'];

$lStmt = $conn->prepare("SELECT id, start_date, end_date, reason, status, admin_notes, requested_at, resolved_at
    FROM leave_requests WHERE mechanic_id = ? ORDER BY requested_at DESC");
$lStmt->bind_param("i", $mechId);
$lStmt->execute();
$result = $lStmt->get_result();

$rows = [];
while ($r = $result->fetch_assoc()) $rows[] = $r;

echo json_encode(["status" => "success", "leave_requests" => $rows]);
