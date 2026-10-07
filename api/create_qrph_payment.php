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

    $user_id = (int)($decoded->uid ?? 0);

    ejr_rate_limit(
        $conn,
        "create_qrph_payment",
        ejr_get_ip() . "|" . $user_id,
        20,
        300
    );

    if (!$user_id) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid token payload.'
        ]);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $customer_name = trim($data['customer_name'] ?? '');
    $contact       = trim($data['contact'] ?? '');
    $items         = $data['items'] ?? [];

    $voucher_code = strtoupper(
        trim($data['voucher_code'] ?? '')
    );

    if (!$customer_name || !$contact || empty($items)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required fields.'
        ]);
        exit;
    }

    /*
     * ------------------------------------------------------------
     * START TRANSACTION
     * ------------------------------------------------------------
     *
     * Everything below is calculated from database prices.
     * The client is NOT trusted for:
     *
     * - item prices
     * - subtotal
     * - voucher discount
     * - final total
     *
     * Stock rows are also locked so two simultaneous orders
     * cannot oversell the same part.
     */

    $conn->begin_transaction();

    try {

        $subtotal = 0.00;
        $serverItems = [];

        /*
         * --------------------------------------------------------
         * CHECK STOCK + GET SERVER-SIDE PRICES
         * --------------------------------------------------------
         */
        foreach ($items as $item) {

            $id  = (int)($item['id'] ?? 0);
            $qty = (int)($item['qty'] ?? 0);

            if ($id <= 0 || $qty <= 0) {
                throw new Exception('Invalid item or quantity.');
            }

            $stockCheck = $conn->prepare("
                SELECT id, stock, name, price
                FROM parts
                WHERE id = ?
                FOR UPDATE
            ");

            if (!$stockCheck) {
                throw new Exception('Unable to check product stock.');
            }

            $stockCheck->bind_param("i", $id);
            $stockCheck->execute();

            $result = $stockCheck->get_result()->fetch_assoc();

            if (!$result || (int)$result['stock'] < $qty) {

                $name = $result['name'] ?? 'Unknown Part';

                throw new Exception(
                    "Sorry, $name is out of stock or insufficient."
                );
            }

            /*
             * IMPORTANT:
             * Use the price from the database, not the price
             * sent by the mobile app.
             */
            $unitPrice = (float)$result['price'];
            $lineTotal = round($unitPrice * $qty, 2);

            $subtotal += $lineTotal;

            /*
             * Store trusted item information in the order.
             * This prevents someone from changing the price
             * in the request.
             */
            $serverItems[] = [
                'id'    => $id,
                'qty'   => $qty,
                'price' => $unitPrice,
                'name'  => $result['name']
            ];
        }

        $subtotal = round($subtotal, 2);

        if ($subtotal <= 0) {
            throw new Exception('Invalid order amount.');
        }

        /*
         * --------------------------------------------------------
         * APPLY VOUCHER
         * --------------------------------------------------------
         *
         * If no voucher was supplied, no discount is applied.
         *
         * When a voucher IS supplied, $lock = true causes the
         * voucher row to be locked during this transaction.
         */
        $voucher_id = null;
        $storedVoucherCode = null;
        $voucher_discount = 0.00;
        $total_price = $subtotal;

        if ($voucher_code !== '') {

            $voucher = ejr_get_voucher(
                $conn,
                $voucher_code,
                $user_id,
                $subtotal,
                true
            );

            $voucher_id = (int)$voucher['id'];
            $storedVoucherCode = $voucher['code'];
            $voucher_discount = (float)$voucher['discount'];

            $total_price = (float)$voucher['total'];
        }

        $total_price = round($total_price, 2);

        /*
         * --------------------------------------------------------
         * SAVE TRUSTED ITEMS
         * --------------------------------------------------------
         */
        $items_json = json_encode(
            $serverItems,
            JSON_UNESCAPED_UNICODE
        );

        if ($items_json === false) {
            throw new Exception('Unable to encode order items.');
        }

        /*
         * --------------------------------------------------------
         * CREATE ORDER
         * --------------------------------------------------------
         */
        $stmt = $conn->prepare("
            INSERT INTO part_orders (
                user_id,
                customer_name,
                contact,
                items,
                total_price,
                status,
                payment_method,
                voucher_id,
                voucher_code,
                voucher_discount
            )
            VALUES (
                ?, ?, ?, ?, ?, 'pending', 'qrph', ?, ?, ?
            )
        ");

        if (!$stmt) {
            throw new Exception('Unable to prepare order insert.');
        }

        $stmt->bind_param(
            "isssdisd",
            $user_id,
            $customer_name,
            $contact,
            $items_json,
            $total_price,
            $voucher_id,
            $storedVoucherCode,
            $voucher_discount
        );

        if (!$stmt->execute()) {
            throw new Exception('Database execution failed.');
        }

        $order_id = $stmt->insert_id;

        /*
         * --------------------------------------------------------
         * RECORD VOUCHER REDEMPTION
         * --------------------------------------------------------
         */
        if ($voucher_id !== null) {

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
                    'Unable to prepare voucher redemption.'
                );
            }

            $redemptionStmt->bind_param(
                "iiid",
                $voucher_id,
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
             * Increment global voucher usage count.
             */
            $usageStmt = $conn->prepare("
                UPDATE vouchers
                SET usage_count = usage_count + 1
                WHERE id = ?
            ");

            if (!$usageStmt) {
                throw new Exception(
                    'Unable to update voucher usage.'
                );
            }

            $usageStmt->bind_param("i", $voucher_id);

            if (!$usageStmt->execute()) {
                throw new Exception(
                    'Unable to update voucher usage.'
                );
            }

            /*
             * If this was a reward voucher, mark it used so it leaves
             * "My Vouchers" (place_order.php already does this).
             */
            try {
                $usedStmt = $conn->prepare(
                    "UPDATE user_vouchers SET used_at = NOW() WHERE code = ? AND user_id = ? AND used_at IS NULL"
                );
                if ($usedStmt) {
                    $usedStmt->bind_param("si", $storedVoucherCode, $user_id);
                    $usedStmt->execute();
                }
            } catch (Throwable $e) {
                error_log('EJR user_vouchers mark-used: ' . $e->getMessage());
            }
        }

        /*
         * --------------------------------------------------------
         * DECREASE STOCK
         * --------------------------------------------------------
         */
        $deductStmt = $conn->prepare("
            UPDATE parts
            SET stock = stock - ?
            WHERE id = ?
        ");

        if (!$deductStmt) {
            throw new Exception('Unable to prepare stock update.');
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
                    'Unable to update product stock.'
                );
            }
        }

        /*
         * --------------------------------------------------------
         * COMMIT ORDER
         * --------------------------------------------------------
         */
        $conn->commit();

    } catch (\Throwable $e) {

        $conn->rollback();

        throw $e;
    }

    /*
     * ------------------------------------------------------------
     * GENERATE PAYMONGO QR PH
     * ------------------------------------------------------------
     *
     * IMPORTANT:
     * Use the SERVER-CALCULATED $total_price here.
     */
    $qr = paymongo_generate_qrph(
        $order_id,
        "EJR Garage Parts Order #{$order_id}",
        $total_price
    );

    if (!$qr['ok']) {

        /*
         * Order is already saved.
         * Customer can retry QR generation later.
         */
        error_log(
            "PayMongo QR Ph generation failed for order #{$order_id}: "
            . $qr['error']
        );

        echo json_encode([
            'status'  => 'error',
            'message' => 'Order saved, but the QR code could not be generated. Please try again.',
            'order_id' => $order_id,
        ]);

        exit;
    }

    /*
     * ------------------------------------------------------------
     * SAVE PAYMONGO PAYMENT INTENT
     * ------------------------------------------------------------
     */
    $upd = $conn->prepare("
        UPDATE part_orders
        SET paymongo_intent_id = ?
        WHERE id = ?
    ");

    $upd->bind_param(
        "si",
        $qr['payment_intent_id'],
        $order_id
    );

    $upd->execute();

    /*
     * ------------------------------------------------------------
     * RETURN QR TO APP
     * ------------------------------------------------------------
     */
    echo json_encode([
        'status'            => 'success',
        'message'           => 'Scan the QR code to complete payment.',
        'order_id'          => $order_id,
        'payment_intent_id' => $qr['payment_intent_id'],
        'qr_image'         => $qr['qr_image'],
        'subtotal'          => $subtotal,
        'voucher_code'      => $storedVoucherCode,
        'voucher_discount'  => $voucher_discount,
        'total_price'       => $total_price,
        'expires_in'        => 1800
    ]);

} catch (Exception $e) {

    error_log(
        'EJR create_qrph_payment error: '
        . $e->getMessage()
    );

    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}

