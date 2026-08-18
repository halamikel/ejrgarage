<?php
/**
 * EJR Garage — Employee Directory API (mechanic-facing)
 *
 * Feeds the mobile app's EmployeesScreen (api_service.dart getEmployees()).
 * Returns staff accounts only — role_id 2 (Admin) and 3 (Mechanic) — never
 * role_id 1 (Customer). Mechanics get their live availability from the
 * same "mechanics" roster table update_availability.php writes to.
 *
 * Response: {status, employees: [{id, full_name, email, role_id,
 * profile_picture, is_available}]}
 */
header('Content-Type: application/json');
require_once 'db_config.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';
require_once '../includes/leave_sync.php';

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

// ── Same self-healing schema guard as the other mechanic endpoints ──
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

// Keep on-leave status current before this staff directory is read.
ejr_sync_leave_statuses($conn);

// ── Staff directory: admins + mechanics only, never customers ──────
$query = "SELECT u.id, u.full_name, u.email, u.role_id, u.profile_picture,
                 m.status AS mechanic_status
          FROM users u
          LEFT JOIN mechanics m ON m.user_id = u.id
          WHERE u.role_id IN (2, 3)
          ORDER BY u.role_id ASC, u.full_name ASC";

$result = $conn->query($query);

$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = [
        "id"               => $row['id'],
        "full_name"        => $row['full_name'],
        "email"            => $row['email'],
        "role_id"          => $row['role_id'],
        "profile_picture"  => $row['profile_picture'],
        // Only meaningful for mechanics (role_id 3). Admins have no
        // mechanics roster row, so this defaults to available/hidden,
        // which matches the UI only rendering the badge for role_id 3.
        // "On Leave" (on_break) and "Busy" (on_duty) both count as
        // NOT assignable — only plain 'available' does.
        "is_available"     => ($row['mechanic_status'] ?? 'available') === 'available' ? 1 : 0,
        // Raw sub-status so the UI can show "On Break" / "On Duty" badges
        // instead of just a binary available/unavailable indicator.
        "mechanic_status"  => $row['mechanic_status'] ?? 'available',
    ];
}

echo json_encode(["status" => "success", "employees" => $employees]);