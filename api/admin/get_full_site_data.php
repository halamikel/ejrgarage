<?php
session_start();
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');
require_once '../db_config.php';
require_once __DIR__ . '/../../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/php-jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ── Self-healing schema: ensure mechanics table + mechanic_id column
// exist before the query below joins against them. Without this, the
// very first dashboard load after deploying this feature throws a SQL
// error and every tab (Appointments, Users, Vehicles...) hangs on
// "Loading..." forever since this single endpoint feeds all of them.
$conn->query("CREATE TABLE IF NOT EXISTS mechanics (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    specialty  VARCHAR(150) DEFAULT '',
    status     ENUM('available','unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$colCheck = $conn->query("SHOW COLUMNS FROM appointments LIKE 'mechanic_id'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE appointments ADD COLUMN mechanic_id INT NULL");
}

// notes: the customer's "Additional Notes" free-text field from the
// booking form (booking-details.php). cancellation_reply: the admin's
// reply/reason when declining/cancelling an appointment from the
// Manage dialog — see api/admin/update_appointment.php.
$notesColCheck = $conn->query("SHOW COLUMNS FROM appointments LIKE 'notes'");
if ($notesColCheck && $notesColCheck->num_rows === 0) {
    $conn->query("ALTER TABLE appointments ADD COLUMN notes TEXT NULL");
}
$replyColCheck = $conn->query("SHOW COLUMNS FROM appointments LIKE 'cancellation_reply'");
if ($replyColCheck && $replyColCheck->num_rows === 0) {
    $conn->query("ALTER TABLE appointments ADD COLUMN cancellation_reply VARCHAR(255) NULL");
}

$svcTableCheck = $conn->query("SHOW TABLES LIKE 'ejr_services'");
$svcTableIsNew = ($svcTableCheck && $svcTableCheck->num_rows === 0);

$conn->query("CREATE TABLE IF NOT EXISTS ejr_services (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    category    VARCHAR(100) DEFAULT 'General',
    description TEXT NULL,
    price_from  DECIMAL(10,2) DEFAULT 0.00,
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($svcTableIsNew) {
    $seedServices = [
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

function ejr_get_token(): string {
    $checks = [
        getallheaders()['Authorization'] ?? '',
        getallheaders()['authorization'] ?? '',
        $_SERVER['HTTP_AUTHORIZATION'] ?? '',
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '',
    ];
    foreach ($checks as $h) {
        if (preg_match('/bearer\s(\S+)/i', $h, $m)) return $m[1];
    }
    return '';
}

function ejr_admin_authorized(): bool {
    $tok = ejr_get_token();
    if ($tok) {
        try {
            $decoded = JWT::decode($tok, new Key(JWT_SECRET, 'HS256'));
            $role = (int)($decoded->role ?? 0);
            $uid  = (int)($decoded->uid ?? 0);
            if ($role === 2 || $uid === 21) return true;
        } catch (Exception $e) {}
    }
    if (isset($_SESSION['user_id']) && ($_SESSION['role_id'] == 2 || $_SESSION['user_id'] == 21)) {
        return true;
    }
    return false;
}

if (!ejr_admin_authorized()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
session_write_close();

try {
    $data = ['users' => [], 'appointments' => [], 'vehicles' => []];

    // 1. Fetch Users
    $userResult = $conn->query("
        SELECT id, full_name, email, phone, role_id, created_at 
        FROM users 
        ORDER BY id ASC
    ");
    if ($userResult) while ($row = $userResult->fetch_assoc()) $data['users'][] = $row;

    // 2. Fetch Appointments with vehicle info and mechanic
    $appResult = $conn->query("
        SELECT a.*, u.full_name,
               CONCAT(v.brand, ' ', v.model, ' (', v.plate_number, ')') AS vehicle_info,
               m.name AS mechanic_name
        FROM appointments a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN vehicles v ON a.vehicle_id = v.id
        LEFT JOIN mechanics m ON a.mechanic_id = m.id
        ORDER BY a.appointment_date DESC
    ");
    if ($appResult) while ($row = $appResult->fetch_assoc()) $data['appointments'][] = $row;

    // 3. Fetch Vehicles
    $vehResult = $conn->query("
        SELECT v.*, u.full_name AS owner_name 
        FROM vehicles v 
        LEFT JOIN users u ON v.user_id = u.id
    ");
    if ($vehResult) while ($row = $vehResult->fetch_assoc()) $data['vehicles'][] = $row;

    echo json_encode(['status' => 'success'] + $data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
}
?>