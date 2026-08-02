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

// ── Auto-create services table ─────────────────────────────────
$tableExistsCheck = $conn->query("SHOW TABLES LIKE 'ejr_services'");
$tableIsNew = ($tableExistsCheck && $tableExistsCheck->num_rows === 0);

$conn->query("CREATE TABLE IF NOT EXISTS ejr_services (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    category    VARCHAR(100) DEFAULT 'General',
    description TEXT NULL,
    price_from  DECIMAL(10,2) DEFAULT 0.00,
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed with the exact services already listed on the public booking page,
// so the admin's list starts in sync with what customers currently see.
if ($tableIsNew) {
    $seedServices = [
        // [name, category]
        ['PMS', 'Popular Services'],
        ['Change Oil', 'Popular Services'],
        ['Engine Tune Up', 'Popular Services'],
        ['Auto Electrical Transmission', 'Popular Services'],
        ['EFI', 'Popular Services'],
        ['Carburator Services', 'Popular Services'],
        ['Decarbonizer', 'Popular Services'],
        ['Scanning Fault Codes', 'Popular Services'],
        ['Paint Services', 'Popular Services'],
        ['Back to Zero (Anti-bacterial fogging)', 'Popular Services'],
        ['Car Checkup', 'Other Services'],
        ['Scheduled Maintenance', 'Other Services'],
        ['Tow/Roadside Assistance', 'Other Services'],
    ];
    $seedStmt = $conn->prepare("INSERT INTO ejr_services (name, category) VALUES (?, ?)");
    foreach ($seedServices as [$name, $category]) {
        $seedStmt->bind_param("ss", $name, $category);
        $seedStmt->execute();
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? 'list';

if ($method === 'GET') {
    $activeOnly = ($_GET['active_only'] ?? '0') === '1';
    $where = $activeOnly ? 'WHERE is_active = 1' : '';
    $result = $conn->query("SELECT * FROM ejr_services $where ORDER BY category, name ASC");
    $rows = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    echo json_encode(['status'=>'success','services'=>$rows]);
    exit;
}

if ($method === 'POST') {
    switch ($action) {
        case 'add':
            $name        = trim($input['name'] ?? '');
            $category    = trim($input['category'] ?? 'General');
            $description = trim($input['description'] ?? '');
            $price_from  = (float)($input['price_from'] ?? 0);
            if (!$name) { echo json_encode(['status'=>'error','message'=>'Service name is required.']); exit; }
            $stmt = $conn->prepare("INSERT INTO ejr_services (name, category, description, price_from) VALUES (?,?,?,?)");
            $stmt->bind_param("sssd", $name, $category, $description, $price_from);
            $stmt->execute();
            echo json_encode(['status'=>'success','id'=>$conn->insert_id]);
            break;

        case 'update':
            $id          = (int)($input['id'] ?? 0);
            $name        = trim($input['name'] ?? '');
            $category    = trim($input['category'] ?? 'General');
            $description = trim($input['description'] ?? '');
            $price_from  = (float)($input['price_from'] ?? 0);
            $is_active   = (int)($input['is_active'] ?? 1);
            $stmt = $conn->prepare("UPDATE ejr_services SET name=?,category=?,description=?,price_from=?,is_active=? WHERE id=?");
            $stmt->bind_param("sssdi i", $name, $category, $description, $price_from, $is_active, $id);
            $stmt->execute();
            echo json_encode(['status'=>'success']);
            break;

        case 'delete':
            $id = (int)($input['id'] ?? 0);
            // Check if service is in use in any pending/confirmed appointment
            $check = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE service_type = (SELECT name FROM ejr_services WHERE id=?) AND status IN ('Pending','Confirmed')");
            $check->bind_param("i", $id);
            $check->execute();
            $check->bind_result($count);
            $check->fetch();
            $check->close();
            if ($count > 0) {
                echo json_encode(['status'=>'error','message'=>'Cannot delete: this service has active appointments. Deactivate it instead.']); exit;
            }
            $conn->prepare("DELETE FROM ejr_services WHERE id=?")->execute([$id]);
            echo json_encode(['status'=>'success']);
            break;

        case 'toggle':
            $id        = (int)($input['id'] ?? 0);
            $is_active = (int)($input['is_active'] ?? 1);
            $conn->prepare("UPDATE ejr_services SET is_active=? WHERE id=?")->execute([$is_active, $id]);
            echo json_encode(['status'=>'success']);
            break;
    }
}