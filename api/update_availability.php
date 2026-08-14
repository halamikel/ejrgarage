<?php
/**
 * EJR Garage — Mechanic Update Availability API
 *
 * Called when a mechanic flips the "Available for jobs" switch on
 * mechanic_main_screen.dart (api_service.dart updateMechanicAvailability()).
 * Updates the mechanics roster row's status, which is the same column
 * the admin dashboard's Mechanics tab already reads/filters on.
 *
 * Uses the same self-healing mechanic<->user linkage as
 * get_mechanic_jobs.php — a mechanic (role_id 3) who hasn't been linked
 * to a "mechanics" roster row yet gets one created/matched automatically
 * rather than erroring out.
 *
 * Body: {status: 'available' | 'unavailable' | 'on_break' | 'on_duty'}
 *
 * 'on_break' and 'on_duty' only make sense while the mechanic is
 * otherwise available (the app only shows those choices when the
 * "Available for jobs" switch is on) — see mechanic_main_screen.dart.
 *   - on_break: mechanic is still assignable; admin can hand them a job.
 *   - on_duty:  mechanic is mid-job on another vehicle; admin CANNOT
 *               assign them a new appointment until they clear this
 *               (see api/admin/manage_mechanics.php "assign" action).
 *
 * Response: {status, message, mechanic_status}
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
$newStatus = trim((string)($input['status'] ?? ''));

if (!in_array($newStatus, ['available', 'unavailable', 'on_break', 'on_duty'], true)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "status must be 'available', 'unavailable', 'on_break', or 'on_duty'."]);
    exit;
}

// ── Self-healing schema (same as get_mechanic_jobs.php) ──────────
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

// Widen the status ENUM for DBs created before on_break/on_duty existed.
$statusCol = $conn->query("SHOW COLUMNS FROM mechanics LIKE 'status'")->fetch_assoc();
if ($statusCol && strpos($statusCol['Type'], 'on_break') === false) {
    $conn->query("ALTER TABLE mechanics MODIFY COLUMN status ENUM('available','unavailable','on_break','on_duty') DEFAULT 'available'");
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
}

// ── Update availability ───────────────────────────────────────────
$upd = $conn->prepare("UPDATE mechanics SET status = ? WHERE id = ?");
$upd->bind_param("si", $newStatus, $mechId);

if (!$upd->execute()) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Could not update availability."]);
    exit;
}

echo json_encode([
    "status"          => "success",
    "message"         => "Availability updated.",
    "mechanic_status" => $newStatus,
]);