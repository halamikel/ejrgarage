<?php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';
require_once __DIR__ . '/../../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/php-jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Authorization: JWT bearer OR session
function ejr_get_bearer(): string {
    // Try all locations where InfinityFree might put the Authorization header
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

if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 2 && $_SESSION['user_id'] != 21)) {
    $tok = ejr_get_bearer();
    if ($tok) {
        try {
            $decoded = JWT::decode($tok, new Key(JWT_SECRET, 'HS256'));
            $_SESSION['user_id'] = (int)($decoded->uid ?? 0);
            $_SESSION['role_id'] = (int)($decoded->role ?? 0);
        } catch (Exception $e) {}
    }
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 2 && $_SESSION['user_id'] != 21)) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ── GET ORDERS ────────────────────────────────────────────────
if ($method === 'GET') {
    // Added WHERE o.status = 'pending' to filter out approved/cancelled orders
    $result = $conn->query("
        SELECT o.*, u.email, u.full_name as user_full_name, u.notif_email
        FROM part_orders o
        JOIN users u ON u.id = o.user_id
        WHERE o.status = 'pending' 
        ORDER BY o.created_at DESC
    ");
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $row['items'] = json_decode($row['items'], true);
        $orders[] = $row;
    }
    echo json_encode(['status' => 'success', 'orders' => $orders]);
    exit;
}

// ── POST ACTIONS ──────────────────────────────────────────────
$data      = json_decode(file_get_contents('php://input'), true);
$action    = $data['action'] ?? '';
$order_id  = isset($data['id']) ? (int)$data['id'] : 0;

if ($action === 'update_status') {
    $new_status = $data['status'] ?? '';
    if (!in_array($new_status, ['pending', 'approved', 'cancelled'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid status.']);
        exit;
    }

    // 1. Get the current status and items before updating
    $checkStmt = $conn->prepare("SELECT status, items FROM part_orders WHERE id = ?");
    $checkStmt->bind_param("i", $order_id);
    $checkStmt->execute();
    $currentOrder = $checkStmt->get_result()->fetch_assoc();

    if (!$currentOrder) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found.']);
        exit;
    }

    // 2. Update the order status
    $stmt = $conn->prepare("UPDATE part_orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();

    // 3. Handle Stock and Email only if status is changing TO 'approved'
    if ($new_status === 'approved' && $currentOrder['status'] !== 'approved') {
        $items = json_decode($currentOrder['items'], true);
        
        if (is_array($items)) {
            foreach ($items as $item) {
                $qty = (int)$item['qty'];
                $part_id = (int)$item['id'];

                // Update parts table stock (ensures stock doesn't go below 0)
                $stockStmt = $conn->prepare("UPDATE parts SET stock = stock - ? WHERE id = ? AND stock >= ?");
                $stockStmt->bind_param("iii", $qty, $part_id, $qty);
                $stockStmt->execute();
            }
        }

        // 4. Auto-send receipt email
        $stmt2 = $conn->prepare("
            SELECT o.*, u.email, u.full_name as user_full_name, u.notif_email
            FROM part_orders o
            JOIN users u ON u.id = o.user_id
            WHERE o.id = ?
        ");
        $stmt2->bind_param("i", $order_id);
        $stmt2->execute();
        $order = $stmt2->get_result()->fetch_assoc();

        if ($order) {
            $emailResult = sendOrderReceipt($order);
            if ($emailResult['status'] !== 'success') {
                error_log("Order #{$order_id} approved but receipt email failed: " . $emailResult['message']);
            }
        } else {
            $emailResult = ['status' => 'error', 'message' => 'Could not find order/customer record for email (order_id=' . $order_id . ').'];
        }
    } else {
        $emailResult = ['status' => 'skipped', 'message' => 'Not a new approval.'];
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Order updated successfully.'
    ]);
    exit;
}

if ($action === 'send_receipt') {
    $stmt = $conn->prepare("
        SELECT o.*, u.email, u.full_name as user_full_name, u.notif_email
        FROM part_orders o
        JOIN users u ON u.id = o.user_id
        WHERE o.id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found.']);
        exit;
    }
    
    $result = sendOrderReceipt($order);
    echo json_encode($result);
    exit;
}

if ($action === 'delete') {
    $stmt = $conn->prepare("DELETE FROM part_orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    echo json_encode(['status' => 'success', 'message' => 'Order deleted.']);
    exit;
}

// ── SEND RECEIPT FUNCTION ─────────────────────────────────────
function sendOrderReceipt($order) {
    $items       = json_decode($order['items'], true);
    $orderNo     = 'EJR-ORD-' . date('Y') . '-' . str_pad($order['id'], 5, '0', STR_PAD_LEFT);
    $orderDate   = date("F j, Y", strtotime($order['created_at']));
    $name        = htmlspecialchars($order['customer_name']);
    $total       = '₱' . number_format($order['total_price'], 2);
    
    $receiptToken = hash('sha256', 'parts-receipt-' . $order['id'] . '-EJR2026');
    $receiptLink = "https://ejrgarage.onrender.com/api/admin/generate_parts_receipt.php?id=" . $order['id'] . "&token=" . $receiptToken;

    $itemsHtml = '';
    if (is_array($items)) {
        foreach ($items as $item) {
            $subtotal    = $item['price'] * $item['qty'];
            $itemsHtml  .= '
            <tr>
                <td style="padding:8px 0;color:#1e2a36;">' . htmlspecialchars($item['name']) . '</td>
                <td style="padding:8px 0;color:#5f6b7a;text-align:center;">' . $item['qty'] . '</td>
                <td style="padding:8px 0;color:#1e2a36;text-align:right;">₱' . number_format($subtotal, 2) . '</td>
            </tr>';
        }
    }

    // ── Send via Brevo's HTTP API (works on InfinityFree; SMTP ports are blocked there) ──
    if (!defined('BREVO_API_KEY')) require_once __DIR__ . '/../env.php';
    $brevoApiKey = BREVO_API_KEY;

    $htmlBody = '
        <div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
            <div style="background:#fff;border-radius:12px;max-width:520px;margin:auto;padding:30px;border:1px solid #e3e8ef;">
                <div style="text-align:center;margin-bottom:24px;">
                    <h2 style="color:#e62429;margin:0;">EJR Garage</h2>
                    <p style="color:#5f6b7a;margin:4px 0 0;">Parts Order Receipt</p>
                </div>
                <p style="color:#1e2a36;">Hi <strong>' . $name . '</strong>,</p>
                <p style="color:#5f6b7a;">Great news! Your order has been <strong style="color:#198754;">approved</strong>. Here are your order details:</p>

                <div style="background:#f8f9fa;border-radius:8px;padding:16px;margin:16px 0;">
                    <table style="width:100%;border-collapse:collapse;font-size:14px;">
                        <tr>
                            <td style="color:#5f6b7a;padding:6px 0;">Order No.</td>
                            <td style="color:#1e2a36;font-weight:bold;">' . $orderNo . '</td>
                        </tr>
                        <tr>
                            <td style="color:#5f6b7a;padding:6px 0;">Date</td>
                            <td style="color:#1e2a36;font-weight:bold;">' . $orderDate . '</td>
                        </tr>
                        <tr>
                            <td style="color:#5f6b7a;padding:6px 0;">Contact</td>
                            <td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($order['contact']) . '</td>
                        </tr>
                    </table>
                </div>

                <table style="width:100%;border-collapse:collapse;font-size:14px;margin:16px 0;">
                    <thead>
                        <tr style="border-bottom:2px solid #e3e8ef;">
                            <th style="text-align:left;padding:8px 0;color:#5f6b7a;">Part</th>
                            <th style="text-align:center;padding:8px 0;color:#5f6b7a;">Qty</th>
                            <th style="text-align:right;padding:8px 0;color:#5f6b7a;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>' . $itemsHtml . '</tbody>
                    <tfoot>
                        <tr style="border-top:2px solid #e3e8ef;">
                            <td colspan="2" style="padding:10px 0;font-weight:bold;color:#1e2a36;">Total</td>
                            <td style="padding:10px 0;font-weight:bold;color:#e62429;text-align:right;">' . $total . '</td>
                        </tr>
                    </tfoot>
                </table>

                <div style="text-align:center;margin-top:24px;">
                    <a href="' . $receiptLink . '" 
                       style="background:#198754;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;">
                        View / Print Official Receipt
                    </a>
                </div>
            </div>
        </div>';

    $payload = [
        'sender'      => ['name' => 'EJR Garage', 'email' => 'ejrgaragetest@gmail.com'],
        'to'          => [['email' => $order['email'], 'name' => $order['user_full_name']]],
        'subject'     => "Order Approved: {$orderNo}",
        'htmlContent' => $htmlBody,
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'api-key: ' . $brevoApiKey,
        'content-type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("Brevo cURL error: " . $curlError);
        return ['status' => 'error', 'message' => 'Connection error: ' . $curlError];
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        return ['status' => 'success', 'message' => 'Receipt sent to ' . $order['email']];
    }

    error_log("Brevo API error ({$httpCode}): " . $response);
    return ['status' => 'error', 'message' => "Brevo API error ({$httpCode}): " . $response];
}
?>