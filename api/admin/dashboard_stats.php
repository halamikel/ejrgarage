<?php
/**
 * api/admin/dashboard_stats.php
 *
 * Feeds the mobile app's Admin Dashboard (AdminDashboard screen /
 * ApiService.getAdminDashboardStats()). Purpose-built summary endpoint:
 * top-line counts + today's appointments + the 5 most recent orders.
 *
 * Auth: same JWT-bearer-or-session admin gate as admin_stats.php /
 * get_full_site_data.php (role_id === 2, or the hardcoded owner
 * account uid === 21).
 *
 * Response:
 * {
 *   status: "success",
 *   stats: { appointments, users, mechanics, orders },
 *   today_appointments: [{ id, customer, service, time, status }],
 *   recent_orders: [{ id, order_number, customer, total, status }]
 * }
 */

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');
require_once '../db_config.php';
require_once __DIR__ . '/../../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/php-jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

session_start();

function ejr_get_bearer(): string {
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
    $tok = ejr_get_bearer();
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

// Same self-healing guard used elsewhere so a fresh DB doesn't 500 here.
$conn->query("CREATE TABLE IF NOT EXISTS mechanics (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    specialty  VARCHAR(150) DEFAULT '',
    status     ENUM('available','unavailable','on_break','on_duty') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

try {
    $stats = [];

    $stats['appointments'] = (int)($conn->query("SELECT COUNT(*) c FROM appointments")->fetch_assoc()['c'] ?? 0);
    $stats['users']        = (int)($conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'] ?? 0);
    $stats['mechanics']    = (int)($conn->query("SELECT COUNT(*) c FROM mechanics")->fetch_assoc()['c'] ?? 0);
    $stats['orders']       = (int)($conn->query("SELECT COUNT(*) c FROM part_orders")->fetch_assoc()['c'] ?? 0);

    // Today's appointments (Asia/Manila) with customer + service info.
    $today = [];
    $todayRes = $conn->query("
        SELECT a.id, a.status, a.service_type, a.appointment_date, u.full_name
        FROM appointments a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE DATE(a.appointment_date) = CURDATE()
        ORDER BY a.appointment_date ASC
    ");
    if ($todayRes) {
        while ($row = $todayRes->fetch_assoc()) {
            $today[] = [
                'id'       => (int)$row['id'],
                'customer' => $row['full_name'] ?? 'Unknown',
                'service'  => $row['service_type'] ?? 'Service',
                'time'     => date('g:i A', strtotime($row['appointment_date'])),
                'status'   => $row['status'],
            ];
        }
    }

    // 5 most recent part orders.
    $recentOrders = [];
    $ordersRes = $conn->query("
        SELECT id, customer_name, total_price, status, created_at
        FROM part_orders
        ORDER BY created_at DESC
        LIMIT 5
    ");
    if ($ordersRes) {
        while ($row = $ordersRes->fetch_assoc()) {
            $recentOrders[] = [
                'id'            => (int)$row['id'],
                'order_number'  => 'ORD-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
                'customer'      => $row['customer_name'],
                'total'         => (float)$row['total_price'],
                'status'        => $row['status'],
            ];
        }
    }

    echo json_encode([
        'status'             => 'success',
        'stats'              => $stats,
        'today_appointments' => $today,
        'recent_orders'      => $recentOrders,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
