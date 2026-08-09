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
    name       VARCHAR(100) NOT NULL,
    specialty  VARCHAR(150) DEFAULT '',
    status     ENUM('available','unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? 'list';

if ($method === 'GET') {
    if ($action === 'list') {
        $result = $conn->query("SELECT * FROM mechanics ORDER BY name ASC");
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        echo json_encode(['status'=>'success','mechanics'=>$rows]);
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
            $status    = in_array($input['status'] ?? '', ['available','unavailable']) ? $input['status'] : 'available';
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
            if ($mech['status'] === 'unavailable') {
                echo json_encode(['status'=>'error','message'=>$mech['name'].' is currently unavailable. Please select another technician.']); exit;
            }

            $stmt = $conn->prepare("UPDATE appointments SET mechanic_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $mechanicId, $apptId);
            $stmt->execute();
            echo json_encode(['status'=>'success','message'=>$mech['name'].' assigned successfully.']);
            break;
    }
}