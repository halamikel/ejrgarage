<?php
/**
 * EJR Garage — Create Parts Order + In-App QR Ph Payment
 *
 * Same order-creation contract as place_order.php (customer_name, contact,
 * items, total_price), but instead of returning a PayMongo hosted
 * checkout_url to redirect to, this generates a QR Ph code directly via
 * the Payment Intent workflow and returns it as a Base64 image so the
 * client (web or mobile) can render it in-app and let the customer pay
 * without leaving the page/app.
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
    ejr_rate_limit($conn, "create_qrph_payment", ejr_get_ip() . "|" . $user_id, 20, 300);

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid token payload.']);
        exit;
    }

    $data          = json_decode(file_get_contents('php://input'), true);
    $customer_name = trim($data['customer_name'] ?? '');
    $contact       = trim($data['contact'] ?? '');
    $items         = $data['items'] ?? [];
    $total_price   = floatval($data['total_price'] ?? 0);

    if (!$customer_name || !$contact || empty($items) || $total_price <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        exit;
    }

    // --- PRE-ORDER STOCK CHECK (same as place_order.php) ---
    foreach ($items as $item) {
        $id  = (int) $item['id'];
        $qty = (int) $item['qty'];

        $stockCheck = $conn->prepare("SELECT stock, name FROM parts WHERE id = ?");
        $stockCheck->bind_param("i", $id);
        $stockCheck->execute();
        $result = $stockCheck->get_result()->fetch_assoc();

        if (!$result || $result['stock'] < $qty) {
            $name = $result['name'] ?? 'Unknown Part';
            echo json_encode(['status' => 'error', 'message' => "Sorry, $name is out of stock or insufficient."]);
            exit;
        }
    }

    $items_json = json_encode($items);

    $stmt = $conn->prepare("
        INSERT INTO part_orders (user_id, customer_name, contact, items, total_price, status, payment_method)
        VALUES (?, ?, ?, ?, ?, 'pending', 'qrph')
    ");
    $stmt->bind_param("isssd", $user_id, $customer_name, $contact, $items_json, $total_price);

    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Database execution failed.']);
        exit;
    }

    $order_id = $stmt->insert_id;

    $qr = paymongo_generate_qrph($order_id, "EJR Garage Parts Order #{$order_id}", $total_price);

    if (!$qr['ok']) {
        // Order is saved regardless — customer can retry the QR via
        // regenerate_qrph_payment.php, or staff can follow up manually.
        error_log("PayMongo QR Ph generation failed for order #{$order_id}: " . $qr['error']);
        echo json_encode([
            'status'   => 'error',
            'message'  => 'Order saved, but the QR code could not be generated. Please try again.',
            'order_id' => $order_id,
        ]);
        exit;
    }

    $upd = $conn->prepare("UPDATE part_orders SET paymongo_intent_id = ? WHERE id = ?");
    $upd->bind_param("si", $qr['payment_intent_id'], $order_id);
    $upd->execute();

    echo json_encode([
        'status'            => 'success',
        'message'           => 'Scan the QR code to complete payment.',
        'order_id'          => $order_id,
        'payment_intent_id' => $qr['payment_intent_id'],
        'qr_image'          => $qr['qr_image'],
        'expires_in'        => 1800, // PayMongo default QR Ph expiry (seconds)
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired or invalid: ' . $e->getMessage()]);
}
