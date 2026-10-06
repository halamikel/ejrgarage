<?php
/**
 * EJR Garage — Customer-facing mechanic list (for the "Preferred Mechanic" picker)
 *
 * Why this exists: get_employees.php only allows role 3 (mechanic) and
 * admin/manage_mechanics.php only allows role 2 (admin). A customer (role 1)
 * calling either gets 403, so the booking form falls back to
 * "Mechanic list is unavailable right now."
 *
 * Any logged-in user may call this. It returns only what a customer needs:
 * roster id + name + specialty of mechanics whose status is 'available'.
 *
 * Response: {status, mechanics: [{id, name, specialty}]}
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

if (!preg_match('/bearer\s(\S+)/i', $authHeader, $m)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Token missing"]);
    exit;
}

try {
    JWT::decode($m[1], new Key(JWT_SECRET, 'HS256')); // any valid role is fine
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

ejr_ensure_archive_columns($conn, 'mechanics');
ejr_sync_leave_statuses($conn); // keep on-leave status current

$result = $conn->query(
    "SELECT m.id,
            COALESCE(NULLIF(u.full_name, ''), m.name) AS name,
            m.specialty
     FROM mechanics m
     LEFT JOIN users u ON u.id = m.user_id
     WHERE m.status = 'available'
       AND (m.is_archived IS NULL OR m.is_archived = 0)
       AND (u.id IS NULL OR (u.is_archived IS NULL OR u.is_archived = 0))
     ORDER BY name ASC"
);

if (!$result) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Could not load mechanics."]);
    exit;
}

$mechanics = [];
while ($row = $result->fetch_assoc()) {
    $mechanics[] = [
        "id"        => (int)$row['id'],
        "name"      => $row['name'],
        "specialty" => $row['specialty'] ?? '',
    ];
}

echo json_encode(["status" => "success", "mechanics" => $mechanics]);
