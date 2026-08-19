<?php
header('Content-Type: application/json');
require_once 'db_config.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined("JWT_SECRET")) require_once __DIR__ . "/env.php";
$secret_key = JWT_SECRET;

$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';
$token      = '';

if (preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    $token = $matches[1];
}

if (!$token) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. No token provided.']);
    exit;
}

try {
    $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired or invalid. Please log in again.']);
    exit;
}

try {
    $user_id = $decoded->uid ?? 0;

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid token payload.']);
        exit;
    }

    // ── SELF-HEALING: cancellation_reply column ─────────────────
    // Guards against the column not existing yet if this endpoint is
    // hit before an admin has ever cancelled an order.
    $replyColCheck = $conn->query("SHOW COLUMNS FROM part_orders LIKE 'cancellation_reply'");
    if ($replyColCheck && $replyColCheck->num_rows === 0) {
        $conn->query("ALTER TABLE part_orders ADD COLUMN cancellation_reply VARCHAR(255) NULL");
    }

    $stmt = $conn->prepare("
        SELECT id, items, total_price, status, payment_status, payment_method, created_at, cancellation_reply,
               ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY created_at ASC) AS user_order_number
        FROM part_orders
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $row['items'] = json_decode($row['items'], true) ?: [];
        $orders[] = $row;
    }

    echo json_encode(['status' => 'success', 'orders' => $orders]);

} catch (Exception $e) {
    error_log('EJR get_my_orders error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Something went wrong. Please try again.']);
}