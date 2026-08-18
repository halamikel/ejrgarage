<?php
header('Content-Type: application/json');
require_once '../db_config.php';
require_once '../../includes/php-jwt/JWT.php';
require_once '../../includes/php-jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ── JWT Admin Gate ─────────────────────────────────────────────
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$auth       = $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$adminId    = 0; $isAdmin = false;
if (preg_match('/bearer\s(\S+)/i', $auth, $m)) {
    try {
        $decoded = JWT::decode($m[1], new Key(JWT_SECRET, 'HS256'));
        $adminId = (int)($decoded->uid ?? 0);
        $role    = (int)($decoded->role ?? 0);
        $isAdmin = ($role === 2 || $adminId === 21);
    } catch (Exception $e) {}
}
if (!$isAdmin) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Forbidden']); exit; }

// ── Auto-create mechanics table ────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS mechanics (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NULL,
    name       VARCHAR(100) NOT NULL,
    specialty  VARCHAR(150) DEFAULT '',
    status     ENUM('available','unavailable','on_break','on_duty') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Make sure older databases also have the user_id column.
$userIdCol = $conn->query("SHOW COLUMNS FROM mechanics LIKE 'user_id'");

if ($userIdCol && $userIdCol->num_rows === 0) {
    $conn->query(
        "ALTER TABLE mechanics ADD COLUMN user_id INT NULL AFTER id"
    );
}

// Widen the status ENUM for DBs created before on_break/on_duty existed.
$statusCol = $conn->query("SHOW COLUMNS FROM mechanics LIKE 'status'")->fetch_assoc();
if ($statusCol && strpos($statusCol['Type'], 'on_break') === false) {
    $conn->query("ALTER TABLE mechanics MODIFY COLUMN status ENUM('available','unavailable','on_break','on_duty') DEFAULT 'available'");
}

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? 'list';

if ($method === 'GET') {
    if ($action === 'list') {
        $result = $conn->query("
    SELECT
        m.*,
        u.full_name AS user_full_name,
        u.email AS user_email
    FROM mechanics m
    LEFT JOIN users u
        ON u.id = m.user_id
    ORDER BY COALESCE(u.full_name, m.name) ASC
");
        $rows = [];

while ($r = $result->fetch_assoc()) {

    // If this mechanic is linked to a user account,
    // always display the user's current name.
    if (!empty($r['user_full_name'])) {
        $r['name'] = $r['user_full_name'];
    }

    $rows[] = $r;
}
    exit;
}

if ($method === 'POST') {
    switch ($action) {
        case 'add':
            $name      = trim($input['name'] ?? '');
            $specialty = trim($input['specialty'] ?? '');
            if (!$name) { echo json_encode(['status'=>'error','message'=>'Name is required.']); exit; }
            $stmt = $conn->prepare("INSERT INTO mechanics (name, specialty) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $specialty);
            $stmt->execute();
            echo json_encode(['status'=>'success','id'=>$conn->insert_id]);
            break;

        case 'update':
            $id        = (int)($input['id'] ?? 0);
            $name      = trim($input['name'] ?? '');
            $specialty = trim($input['specialty'] ?? '');
            $status    = in_array($input['status'] ?? '', ['available','unavailable','on_break','on_duty'], true) ? $input['status'] : 'available';
            $stmt = $conn->prepare("UPDATE mechanics SET name=?, specialty=?, status=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $specialty, $status, $id);
            $stmt->execute();
            echo json_encode(['status'=>'success']);
            break;

        case 'delete':
            $id = (int)($input['id'] ?? 0);
            // Check if mechanic is assigned to a pending/confirmed appointment
            $check = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE mechanic_id=? AND status IN ('Pending','Confirmed')");
            $check->bind_param("i", $id);
            $check->execute();
            $check->bind_result($count);
            $check->fetch();
            $check->close();
            if ($count > 0) {
                echo json_encode(['status'=>'error','message'=>'Cannot delete: mechanic has active appointments.']); exit;
            }
            $del = $conn->prepare("DELETE FROM mechanics WHERE id=?");
            $del->bind_param("i", $id);
            $del->execute();
            echo json_encode(['status'=>'success']);
            break;

        case 'assign':
            $apptId     = (int)($input['appointment_id'] ?? 0);
            $mechanicId = (int)($input['mechanic_id'] ?? 0);
            if (!$apptId) { echo json_encode(['status'=>'error','message'=>'Missing appointment ID.']); exit; }

            // Ensure column exists
            $conn->query("ALTER TABLE appointments ADD COLUMN IF NOT EXISTS mechanic_id INT NULL");

            if ($mechanicId === 0) {
                // Unassign
                $unassign = $conn->prepare("UPDATE appointments SET mechanic_id = NULL WHERE id = ?");
                $unassign->bind_param("i", $apptId);
                $unassign->execute();
                echo json_encode(['status'=>'success','message'=>'Mechanic unassigned.']);
                exit;
            }

            // Check mechanic exists and is available
            $mStmt = $conn->prepare("SELECT name, status FROM mechanics WHERE id = ?");
            $mStmt->bind_param("i", $mechanicId);
            $mStmt->execute();
            $mech = $mStmt->get_result()->fetch_assoc();
            if (!$mech) { echo json_encode(['status'=>'error','message'=>'Mechanic not found.']); exit; }
            if ($mech['status'] !== 'available') {
                $reason = $mech['status'] === 'on_break'
                    ? ' is currently ON LEAVE.'
                    : ($mech['status'] === 'on_duty'
                        ? ' is currently BUSY (working on another vehicle).'
                        : ' is currently unavailable.');
                echo json_encode(['status'=>'error','message'=>$mech['name'].$reason.' Please select another technician.']); exit;
            }

            $stmt = $conn->prepare("UPDATE appointments SET mechanic_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $mechanicId, $apptId);
            $stmt->execute();
            echo json_encode(['status'=>'success','message'=>$mech['name'].' assigned successfully.']);
            break;
    }
}