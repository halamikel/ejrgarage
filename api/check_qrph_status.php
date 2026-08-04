<?php
/**
 * EJR Garage — Check QR Ph Payment Status
 *
 * Polled by the client (web/mobile) while a QR code is on screen.
 * Fast path: if the order is already marked paid in our DB, return
 * immediately without hitting PayMongo. Otherwise ask PayMongo for the
 * Payment Intent's current status and persist it if it just succeeded.
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
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired or invalid. Please log in again.']);
    exit;
}

try {
    $user_id = $decoded->uid ?? 0;
    ejr_rate_limit($conn, "check_qrph_status", ejr_get_ip() . "|" . $user_id, 60, 300);

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid token payload.']);
        exit;
    }

    $order_id = (int) ($_GET['order_id'] ?? 0);
    if (!$order_id) {
        echo json_encode(['status' => 'error', 'message' => 'Missing order_id.']);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT payment_status, paymongo_intent_id
        FROM part_orders
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found.']);
        exit;
    }

    if ($order['payment_status'] === 'paid') {
        echo json_encode(['status' => 'success', 'payment_status' => 'paid']);
        exit;
    }

    if (!$order['paymongo_intent_id']) {
        echo json_encode(['status' => 'success', 'payment_status' => 'pending']);
        exit;
    }

    $intentStatus = paymongo_get_payment_intent_status($order['paymongo_intent_id']);

    if ($intentStatus === 'succeeded') {
        $upd = $conn->prepare("UPDATE part_orders SET payment_status = 'paid' WHERE id = ?");
        $upd->bind_param("i", $order_id);
        $upd->execute();
        echo json_encode(['status' => 'success', 'payment_status' => 'paid']);
    } elseif ($intentStatus === 'awaiting_payment_method') {
        // PayMongo returns the intent to this state when the QR expired
        // or the attempt failed — the customer needs a fresh QR code.
        echo json_encode(['status' => 'success', 'payment_status' => 'expired']);
    } else {
        echo json_encode(['status' => 'success', 'payment_status' => 'pending']);
    }

} catch (Exception $e) {
    error_log('EJR check_qrph_status error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Something went wrong while checking your payment status. Please try again.']);
}