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
// ── Auto-create mechanics table ────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS mechanics (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NULL,
    name       VARCHAR(100) NOT NULL,
    specialty  VARCHAR(150) DEFAULT '',
    status     ENUM('available','unavailable','on_break','on_duty') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");


/*
 * Existing installations may already have the mechanics table
 * without user_id.
 *
 * Add user_id only when it doesn't already exist.
 */

$userIdColumn = $conn->query(
    "SHOW COLUMNS FROM mechanics LIKE 'user_id'"
);

if (
    $userIdColumn &&
    $userIdColumn->num_rows === 0
) {
    $conn->query(
        "ALTER TABLE mechanics
         ADD COLUMN user_id INT NULL AFTER id"
    );
}

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

        /*
         * LEFT JOIN is intentional.
         *
         * Existing mechanics may not yet have a user_id.
         * They must still appear in the Mechanics tab.
         */

        $result = $conn->query("
            SELECT
                m.id,
                m.user_id,
                m.name,
                m.specialty,
                m.status,
                m.created_at,

                u.full_name AS current_user_name,
                u.email AS current_user_email

            FROM mechanics m

            LEFT JOIN users u
                ON u.id = m.user_id

            ORDER BY
                COALESCE(u.full_name, m.name) ASC
        ");

        if (!$result) {
            http_response_code(500);

            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to load mechanics: ' . $conn->error
            ]);

            exit;
        }

        $rows = [];

        while ($r = $result->fetch_assoc()) {

            /*
             * If the mechanic is connected to a user account,
             * display the user's CURRENT name.
             *
             * Otherwise keep the existing mechanic name.
             */

            if (
                isset($r['current_user_name']) &&
                trim((string)$r['current_user_name']) !== ''
            ) {
                $r['name'] = $r['current_user_name'];
            }

            unset(
                $r['current_user_name'],
                $r['current_user_email']
            );

            $rows[] = $r;
        }

        echo json_encode([
            'status'    => 'success',
            'mechanics' => $rows
        ]);
    }

    exit;
}

if ($method === 'POST') {
    switch ($action) {
        case 'add':
            $name      = trim($input['name'] ?? '');
            $specialty = trim($input['specialty'] ?? '');
            if (!$name) { echo json_encode(['status'=>'error','message'=>'Name is required.']); exit; }
            $stmt = $conn->prepare("
    INSERT INTO mechanics
        (user_id, name, specialty)
    VALUES
        (NULL, ?, ?)
");

$stmt->bind_param(
    "ss",
    $name,
    $specialty
);
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