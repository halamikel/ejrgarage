<?php
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
    ejr_rate_limit($conn, "place_order", ejr_get_ip() . "|" . $user_id, 20, 300);

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid token payload.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $customer_name   = trim($data['customer_name'] ?? '');
$contact         = trim($data['contact'] ?? '');
$address         = trim($data['address'] ?? '');
$shipping_instructions = trim($data['shipping_instructions'] ?? '');
$items           = $data['items'] ?? [];
    $total_price     = floatval($data['total_price'] ?? 0);
    $payment_method  = ($data['payment_method'] ?? 'card') === 'cod' ? 'cod' : 'card';

    // Self-healing: shipping_instructions wasn't part of the original
    // part_orders schema — add it the first time it's missing (same
    // pattern used for appointments.notes in save_booking.php).
    $shipColCheck = $conn->query("SHOW COLUMNS FROM part_orders LIKE 'shipping_instructions'");
    if ($shipColCheck && $shipColCheck->num_rows === 0) {
        $conn->query("ALTER TABLE part_orders ADD COLUMN shipping_instructions TEXT NULL");
    }

    if (!$customer_name || !$contact || !$address || empty($items) || $total_price <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Name, contact number, address, items, and total price are required.'
    ]);
    exit;
}

    // --- PRE-ORDER STOCK CHECK + RESERVE STOCK ---
    // Runs inside a transaction with row locks (FOR UPDATE) so two
    // simultaneous orders can't both "pass" the check and oversell
    // the same part.
    $conn->begin_transaction();

    try {
        foreach ($items as $item) {
            $id  = (int)$item['id'];
            $qty = (int)$item['qty'];

            $stockCheck = $conn->prepare("SELECT stock, name FROM parts WHERE id = ? FOR UPDATE");
            $stockCheck->bind_param("i", $id);
            $stockCheck->execute();
            $result = $stockCheck->get_result()->fetch_assoc();

            if (!$result || $result['stock'] < $qty) {
                $name = $result['name'] ?? 'Unknown Part';
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => "Sorry, $name is out of stock or insufficient."]);
                exit;
            }
        }

        $items_json = json_encode($items);

        $stmt = $conn->prepare("
        INSERT INTO part_orders (
            user_id,
            customer_name,
            contact,
            address,
            shipping_instructions,
            items,
            total_price,
            status,
            payment_method
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)
    ");

        $stmt->bind_param(
            "isssssds",
            $user_id,
            $customer_name,
            $contact,
            $address,
            $shipping_instructions,
            $items_json,
            $total_price,
            $payment_method
        );

        if (!$stmt->execute()) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Database execution failed.']);
            exit;
        }

        $order_id = $stmt->insert_id;

        // --- DECREASE STOCK NOW THAT THE ORDER EXISTS ---
        $deductStmt = $conn->prepare("UPDATE parts SET stock = stock - ? WHERE id = ?");
        foreach ($items as $item) {
            $id  = (int)$item['id'];
            $qty = (int)$item['qty'];
            $deductStmt->bind_param("ii", $qty, $id);
            $deductStmt->execute();
        }

        $conn->commit();
    } catch (\Throwable $e) {
        $conn->rollback();
        error_log('EJR place_order stock error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Something went wrong placing your order. Please try again.']);
        exit;
    }

    // Stock reserved & order saved — continue to payment/checkout below.
    {
        // ── CASH ON DELIVERY: no online payment needed, order is just pending fulfillment ──
        if ($payment_method === 'cod') {
            echo json_encode([
                'status'       => 'success',
                'message'      => 'Order placed! You will pay in cash upon delivery/pickup.',
                'checkout_url' => null
            ]);
            exit;
        }

        // ── ONLINE PAYMENT: create PayMongo Checkout Session (QRPH / Card) ──
        $checkout = paymongo_create_checkout(
            $order_id,
            "EJR Garage Parts Order #{$order_id}",
            $total_price,
            "https://ejrgarage.onrender.com/payment_success.php?order_id={$order_id}",
            "https://ejrgarage.onrender.com/payment_cancelled.php?order_id={$order_id}"
        );

        if ($checkout['ok']) {
            $upd = $conn->prepare("UPDATE part_orders SET paymongo_checkout_id = ? WHERE id = ?");
            $upd->bind_param("si", $checkout['checkout_id'], $order_id);
            $upd->execute();

            echo json_encode([
                'status'       => 'success',
                'message'      => 'Order placed. Redirecting to payment...',
                'checkout_url' => $checkout['checkout_url']
            ]);
        } else {
            // Order is saved either way; payment can be retried/handled by admin.
            error_log("PayMongo checkout creation failed for order #{$order_id}: " . $checkout['error']);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Order placed, but payment session could not be created. Please contact us to pay.',
                'checkout_url' => null
            ]);
        }
    }

} catch (Exception $e) {
    error_log('EJR place_order error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Something went wrong. Please try again.']);
}
?>