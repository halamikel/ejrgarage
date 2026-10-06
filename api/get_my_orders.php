<?php
header('Content-Type: application/json');
require_once 'db_config.php';
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

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid token payload.']);
        exit;
    }

    // ── SELF-HEALING: cancellation_reply column ─────────────────
    $replyColCheck = $conn->query("SHOW COLUMNS FROM part_orders LIKE 'cancellation_reply'");
    if ($replyColCheck && $replyColCheck->num_rows === 0) {
        $conn->query("ALTER TABLE part_orders ADD COLUMN cancellation_reply VARCHAR(255) NULL");
    }

    ejr_auto_approve_pending_orders($conn, 5);

    // ── SYNC ONLINE PAYMENTS ────────────────────────────────────
    try {
        $pendingStmt = $conn->prepare("
            SELECT id, paymongo_intent_id
            FROM part_orders
            WHERE user_id = ?
              AND payment_method = 'online'
              AND (payment_status IS NULL OR payment_status <> 'paid')
              AND paymongo_intent_id LIKE 'cs\\_%'
              AND created_at >= (NOW() - INTERVAL 3 DAY)
            ORDER BY created_at DESC
            LIMIT 5
        ");
        if ($pendingStmt) {
            $pendingStmt->bind_param("i", $user_id);
            $pendingStmt->execute();
            $pendingRes = $pendingStmt->get_result();
            while ($po = $pendingRes->fetch_assoc()) {
                $session = paymongo_get_checkout($po['paymongo_intent_id']);
                if (!$session) continue;

                $attrs = $session['attributes'] ?? [];
                $paid  = false;

                foreach (($attrs['payments'] ?? []) as $pay) {
                    if (($pay['attributes']['status'] ?? '') === 'paid') { $paid = true; break; }
                }
                if (!$paid && (($attrs['payment_intent']['attributes']['status'] ?? '') === 'succeeded')) {
                    $paid = true;
                }

                if ($paid) {
                    $mark = $conn->prepare("UPDATE part_orders SET payment_status = 'paid' WHERE id = ?");
                    if ($mark) {
                        $mark->bind_param("i", $po['id']);
                        $mark->execute();
                    }
                }
            }
        }
    } catch (\Throwable $e) {
        error_log('EJR get_my_orders payment sync error: ' . $e->getMessage());
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