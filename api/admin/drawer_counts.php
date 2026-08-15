<?php
/**
 * api/admin/drawer_counts.php
 *
 * Feeds the mobile app's AdminDrawer badges (ApiService.getAdminDrawerCounts()).
 * The drawer used to be built with hardcoded placeholder numbers
 * (Appointments: 2, Users: 3, Vehicles: 5, ...) that never changed no
 * matter what was actually in the database. This endpoint replaces
 * those placeholders with real, live counts.
 *
 * Each count mirrors the same "needs attention" / total logic already
 * used by the web admin dashboard (see js/admin_dashboard.js) so the
 * mobile badges agree with the web badges:
 *   - pending_appointments : appointments.status = 'Pending'
 *   - new_users            : customers (role_id = 1) created in the last 7 days
 *   - vehicles             : total vehicles on file
 *   - history              : completed appointments (service history rows)
 *   - parts                : total parts in inventory
 *   - pending_inquiries    : part_inquiries.status != 'responded'
 *   - pending_orders       : part_orders.status = 'pending'
 *   - services             : active services offered
 *   - available_mechanics  : mechanics.status = 'available'
 *   - busy_dates           : appointment_dates.is_busy = 1
 *   - unread_chats         : waiting chat sessions + unread user messages
 *
 * Auth: same JWT-bearer-or-session admin gate used by every other
 * admin/*.php endpoint (role_id === 2, or the hardcoded owner
 * account uid === 21).
 *
 * Response:
 * {
 *   status: "success",
 *   counts: {
 *     pending_appointments, new_users, vehicles, history, parts,
 *     pending_inquiries, pending_orders, services, available_mechanics,
 *     busy_dates, unread_chats
 *   }
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

// Self-healing guards so a fresh/partially-migrated DB doesn't 500 here
// (same pattern used by dashboard_stats.php / get_full_site_data.php).
$conn->query("CREATE TABLE IF NOT EXISTS mechanics (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    specialty  VARCHAR(150) DEFAULT '',
    status     ENUM('available','unavailable','on_break','on_duty') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS ejr_services (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    category    VARCHAR(100) DEFAULT 'General',
    description TEXT NULL,
    price_from  DECIMAL(10,2) DEFAULT 0.00,
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function ejr_count(mysqli $conn, string $sql): int {
    $res = $conn->query($sql);
    if (!$res) return 0;
    return (int)($res->fetch_assoc()['c'] ?? 0);
}

try {
    $counts = [];

    $counts['pending_appointments'] = ejr_count($conn,
        "SELECT COUNT(*) c FROM appointments WHERE status = 'Pending'");

    $counts['new_users'] = ejr_count($conn,
        "SELECT COUNT(*) c FROM users WHERE role_id = 1 AND created_at >= (NOW() - INTERVAL 7 DAY)");

    $counts['vehicles'] = ejr_count($conn,
        "SELECT COUNT(*) c FROM vehicles");

    $counts['history'] = ejr_count($conn,
        "SELECT COUNT(*) c FROM appointments WHERE status = 'Completed'");

    $counts['parts'] = ejr_count($conn,
        "SELECT COUNT(*) c FROM parts");

    $counts['pending_inquiries'] = ejr_count($conn,
        "SELECT COUNT(*) c FROM part_inquiries WHERE status != 'responded'");

    $counts['pending_orders'] = ejr_count($conn,
        "SELECT COUNT(*) c FROM part_orders WHERE status = 'pending'");

    $counts['services'] = ejr_count($conn,
        "SELECT COUNT(*) c FROM ejr_services WHERE is_active = 1");

    $counts['available_mechanics'] = ejr_count($conn,
        "SELECT COUNT(*) c FROM mechanics WHERE status = 'available'");

    $counts['busy_dates'] = ejr_count($conn,
        "SELECT COUNT(*) c FROM appointment_dates WHERE is_busy = 1");

    // Unread chats: waiting sessions + unread user messages, same math
    // as badgeChat on the web admin dashboard.
    $waiting = ejr_count($conn,
        "SELECT COUNT(*) c FROM users WHERE chat_session = 'waiting'");
    $unreadMsgs = ejr_count($conn,
        "SELECT COUNT(*) c FROM ejr_chat WHERE is_read = 0 AND sender = 'user'");
    $counts['unread_chats'] = $waiting + $unreadMsgs;

    echo json_encode([
        'status' => 'success',
        'counts' => $counts,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
