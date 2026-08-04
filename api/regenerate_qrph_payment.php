<?php
/**
 * EJR Garage — Regenerate QR Ph Code
 *
 * QR Ph codes expire after 30 minutes. Rather than creating a duplicate
 * order, this generates a fresh Payment Intent + QR for an existing
 * pending/unpaid order the customer already placed.
 */
header('Content-Type: application/json');
require_once 'db_config.php';
require_once 'rate_limit.php';
require_once 'paymongo_helper.php';
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
    $user_id = $decoded->uid ?? 0;
    ejr_rate_limit($conn, "regenerate_qrph", ejr_get_ip() . "|" . $user_id, 10, 300);

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid token payload.']);
        exit;
    }

    $data     = json_decode(file_get_contents('php://input'), true);
    $order_id = (int) ($data['order_id'] ?? 0);

    if (!$order_id) {
        echo json_encode(['status' => 'error', 'message' => 'Missing order_id.']);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT total_price, payment_status
        FROM part_orders
        WHERE id = ? AND user_id = ? AND payment_method = 'qrph'
    ");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found.']);
        exit;
    }

    if ($order['payment_status'] === 'paid') {
        echo json_encode(['status' => 'error', 'message' => 'This order has already been paid.']);
        exit;
    }

    $qr = paymongo_generate_qrph($order_id, "EJR Garage Parts Order #{$order_id}", (float) $order['total_price']);

    if (!$qr['ok']) {
        echo json_encode(['status' => 'error', 'message' => $qr['error'] ?? 'Could not generate a new QR code.']);
        exit;
    }

    $upd = $conn->prepare("UPDATE part_orders SET paymongo_intent_id = ? WHERE id = ?");
    $upd->bind_param("si", $qr['payment_intent_id'], $order_id);
    $upd->execute();

    echo json_encode([
        'status'            => 'success',
        'order_id'          => $order_id,
        'payment_intent_id' => $qr['payment_intent_id'],
        'qr_image'          => $qr['qr_image'],
        'expires_in'        => 1800,
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired or invalid: ' . $e->getMessage()]);
}
