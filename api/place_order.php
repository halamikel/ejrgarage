<?php
header('Content-Type: application/json');
header('X-EJR-Version: place-order-pm-fix-4');
require_once 'db_config.php';
require_once 'rate_limit.php'; 
require_once 'paymongo_helper.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';
require_once 'voucher_helper.php';

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

$voucher_code    = strtoupper(trim($data['voucher_code'] ?? ''));

// place_order.php handles the cash-on-pickup/delivery flow (QR Ph goes
// through create_qrph_payment.php), so default to 'cod'.
$payment_method  = strtolower(trim((string)($data['payment_method'] ?? '')));
if ($payment_method === '' || $payment_method === 'qrph') {
    $payment_method = 'cod';
}

    // Self-healing: shipping_instructions wasn't part of the original
    // part_orders schema — add it the first time it's missing (same
    // pattern used for appointments.notes in save_booking.php).
    $shipColCheck = $conn->query("SHOW COLUMNS FROM part_orders LIKE 'shipping_instructions'");
    if ($shipColCheck && $shipColCheck->num_rows === 0) {
        $conn->query("ALTER TABLE part_orders ADD COLUMN shipping_instructions TEXT NULL");
    }

if (
    !$customer_name ||
    !$contact ||
    !$address ||
    empty($items)
) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Name, contact number, address, and items are required.'
    ]);
    exit;
}

// --- STOCK CHECK + SERVER-SIDE PRICE CALCULATION + VOUCHER ---
// Everything runs inside one transaction.
// FOR UPDATE prevents two simultaneous orders from overselling stock.

$conn->begin_transaction();

try {

    $subtotal = 0;

    // These are the trusted items that will be stored in the order.
    $serverItems = [];

    foreach ($items as $item) {

        $id  = (int)($item['id'] ?? 0);
        $qty = (int)($item['qty'] ?? 0);

        if ($id <= 0 || $qty <= 0) {
            throw new Exception('Invalid order item.');
        }

        /*
         * IMPORTANT:
         *
         * We get the price from TiDB.
         * We DO NOT trust the price sent by the Expo app.
         */
        $stockCheck = $conn->prepare("
            SELECT
                id,
                stock,
                name,
                price
            FROM parts
            WHERE id = ?
            FOR UPDATE
        ");

        if (!$stockCheck) {
            throw new Exception('Unable to check product stock.');
        }

        $stockCheck->bind_param("i", $id);

        if (!$stockCheck->execute()) {
            throw new Exception('Unable to check product stock.');
        }

        $result = $stockCheck
            ->get_result()
            ->fetch_assoc();

        if (!$result) {
            throw new Exception('The selected part could not be found.');
        }

        if ((int)$result['stock'] < $qty) {

            throw new Exception(
                "Sorry, {$result['name']} is out of stock or insufficient."
            );
        }

        // TRUSTED PRICE FROM DATABASE
        $price = (float)$result['price'];

        $lineTotal = $price * $qty;

        $subtotal += $lineTotal;

        /*
         * Store the database values rather than the values
         * supplied by the mobile app.
         */
        $serverItems[] = [
            'id'    => (int)$result['id'],
            'name'  => $result['name'],
            'price' => $price,
            'qty'   => $qty
        ];
    }

    // Always round money to 2 decimal places.
    $subtotal = round($subtotal, 2);

    /*
     * -------------------------------------------------------
     * APPLY VOUCHER
     * -------------------------------------------------------
     */

    $voucher = null;

    $voucher_discount = 0;

    // Default: no voucher, so total = subtotal.
    $total_price = $subtotal;

    if ($voucher_code !== '') {

        /*
         * $lock = true
         *
         * This locks the voucher row while this order
         * is being created.
         */
        $voucher = ejr_get_voucher(
            $conn,
            $voucher_code,
            $user_id,
            $subtotal,
            true
        );

        $voucher_discount = $voucher['discount'];

        $total_price = $voucher['total'];
    }

    if ($total_price <= 0) {
        throw new Exception('Invalid order total.');
    }

    /*
     * Store the server-calculated items.
     */
    $items_json = json_encode(
        $serverItems,
        JSON_UNESCAPED_UNICODE
    );

    if ($items_json === false) {
        throw new Exception('Unable to prepare order items.');
    }

    /*
     * -------------------------------------------------------
     * CREATE ORDER
     * -------------------------------------------------------
     */

    $stmt = $conn->prepare("
        INSERT INTO part_orders (
            user_id,
            customer_name,
            contact,
            address,
            shipping_instructions,
            items,
            total_price,
            voucher_id,
            voucher_code,
            voucher_discount,
            status,
            payment_method
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', COALESCE(?, 'cod'))
    ");

    if (!$stmt) {
        throw new Exception(
            'Unable to prepare order creation.'
        );
    }

    $voucher_id = $voucher
        ? (int)$voucher['id']
        : null;

    $storedVoucherCode = $voucher
        ? $voucher['code']
        : null;

    /*
     * Type string:
     *
     * i = user_id
     * s = customer_name
     * s = contact
     * s = address
     * s = shipping_instructions
     * s = items_json
     * d = total_price
     * i = voucher_id
     * s = voucher_code
     * d = voucher_discount
     * s = payment_method
     */
    $stmt->bind_param(
        "isssssdisds",
        $user_id,
        $customer_name,
        $contact,
        $address,
        $shipping_instructions,
        $items_json,
        $total_price,
        $voucher_id,
        $storedVoucherCode,
        $voucher_discount,
        $payment_method
    );

    if (!$stmt->execute()) {
        throw new Exception(
            'Database execution failed.'
        );
    }

    $order_id = $stmt->insert_id;

    /*
     * -------------------------------------------------------
     * RECORD VOUCHER REDEMPTION
     * -------------------------------------------------------
     */

    if ($voucher) {

        $redemptionStmt = $conn->prepare("
            INSERT INTO voucher_redemptions (
                voucher_id,
                user_id,
                order_id,
                discount_amount
            )
            VALUES (?, ?, ?, ?)
        ");

        if (!$redemptionStmt) {
            throw new Exception(
                'Unable to record voucher redemption.'
            );
        }

        $voucherId = (int)$voucher['id'];

        $redemptionStmt->bind_param(
            "iiid",
            $voucherId,
            $user_id,
            $order_id,
            $voucher_discount
        );

        if (!$redemptionStmt->execute()) {
            throw new Exception(
                'Unable to record voucher redemption.'
            );
        }

        /*
         * Increase global usage count.
         */
        $usageStmt = $conn->prepare("
            UPDATE vouchers
            SET
                usage_count = usage_count + 1,
                updated_at = NOW()
            WHERE id = ?
        ");

        if (!$usageStmt) {
            throw new Exception(
                'Unable to update voucher usage.'
            );
        }

        $usageStmt->bind_param(
            "i",
            $voucherId
        );

        if (!$usageStmt->execute()) {
            throw new Exception(
                'Unable to update voucher usage.'
            );
        }
    }

    /*
     * -------------------------------------------------------
     * DECREASE STOCK
     * -------------------------------------------------------
     *
     * The rows are already locked with FOR UPDATE.
     */
    $deductStmt = $conn->prepare("
        UPDATE parts
        SET stock = stock - ?
        WHERE id = ?
    ");

    if (!$deductStmt) {
        throw new Exception(
            'Unable to update stock.'
        );
    }

    foreach ($serverItems as $item) {

        $id  = (int)$item['id'];
        $qty = (int)$item['qty'];

        $deductStmt->bind_param(
            "ii",
            $qty,
            $id
        );

        if (!$deductStmt->execute()) {
            throw new Exception(
                'Unable to update stock.'
            );
        }
    }

    /*
     * Everything succeeded.
     *
     * Commit:
     * - order
     * - voucher redemption
     * - voucher usage
     * - stock deduction
     */
    $conn->commit();

} catch (\Throwable $e) {

    /*
     * If anything failed, undo:
     * - order
     * - voucher redemption
     * - voucher usage
     * - stock changes
     */
    $conn->rollback();

    error_log(
        'EJR place_order transaction error: ' .
        $e->getMessage()
    );

    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);

    exit;
}

} catch (Exception $e) {
    error_log('EJR place_order error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Something went wrong. Please try again.']);
}
?>