<?php
header('Content-Type: application/json');
error_reporting(0);

require_once '../db_config.php';
require_once __DIR__ . '/../../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/php-jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ── JWT Admin Gate ─────────────────────────────────────────────
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$auth       = $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
$adminId    = 0;
$isAdmin    = false;

if (preg_match('/bearer\s(\S+)/i', $auth, $m)) {
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

$method = $_SERVER['REQUEST_METHOD'];

// ── DELETE / UPDATE ───────────────────────────────────────────
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $data['action'] ?? '';

    if ($action === 'delete') {
        $historyId = (int)($data['id'] ?? 0);
        ejr_ensure_archive_columns($conn, 'appointments');
        $stmt = $conn->prepare("
            UPDATE appointments
            SET is_archived = 1, archived_at = NOW(), archived_by = ?
            WHERE id = ? AND status = 'Completed'
              AND (is_archived IS NULL OR is_archived = 0)
        ");
        $stmt->bind_param("ii", $adminId, $historyId);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Record archived']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Record not found or already archived']);
        }
        exit;
    }

    if ($action === 'update') {
        $stmt = $conn->prepare("UPDATE appointments SET service_type = ?, appointment_date = ? WHERE id = ? AND status = 'Completed'");
        $stmt->bind_param("ssi", $data['service_type'], $data['appointment_date'], $data['id']);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'Record updated']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
    exit;
}

// ── GET ALL COMPLETED ─────────────────────────────────────────
ejr_ensure_archive_columns($conn, 'appointments');
$stmt = $conn->prepare("
    SELECT a.id, a.service_type, a.appointment_date AS service_date,
           u.full_name,
           v.brand, v.model, v.year_model, v.plate_number,
           m.name AS mechanic_name
    FROM appointments a
    JOIN users u ON u.id = a.user_id
    LEFT JOIN vehicles v ON v.id = a.vehicle_id
    LEFT JOIN mechanics m ON m.id = a.mechanic_id
    WHERE a.status = 'Completed'
      AND (a.is_archived IS NULL OR a.is_archived = 0)
    ORDER BY a.appointment_date DESC
");
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}

echo json_encode(['status' => 'success', 'history' => $history]);
?>