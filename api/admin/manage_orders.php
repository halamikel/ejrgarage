<?php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';
require_once __DIR__ . '/../../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/php-jwt/Key.php';
require_once __DIR__ . '/../mailer_helper.php';
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

// ── PARSE POST BODY (action, id) ────────────────────────────────
$action   = '';
$order_id = 0;
if ($method === 'POST') {
    $postData = json_decode(file_get_contents('php://input'), true) ?? [];
    $action   = $postData['action'] ?? '';
    $order_id = (int)($postData['id'] ?? 0);
}

// ── SELF-HEALING: cancellation_reply column ─────────────────────
// Mirrors the same pattern used for appointments — adds the column
// on first use so existing installs don't need a manual migration.
$replyColCheck = $conn->query("SHOW COLUMNS FROM part_orders LIKE 'cancellation_reply'");
if ($replyColCheck && $replyColCheck->num_rows === 0) {
    $conn->query("ALTER TABLE part_orders ADD COLUMN cancellation_reply VARCHAR(255) NULL");
}

ejr_ensure_archive_columns($conn, 'part_orders');

// ── GET ORDERS ────────────────────────────────────────────────
// ── GET ORDERS ────────────────────────────────────────────────
if ($method === 'GET') {

    /*
     * IMPORTANT:
     * Use LEFT JOIN instead of JOIN.
     *
     * Some old orders may contain a user_id that no longer has
     * a matching users record. INNER JOIN would make those orders
     * disappear completely from the admin dashboard.
     *
     * We therefore:
     *   1. Keep every order.
     *   2. Use the current user's information when available.
     *   3. Fall back to the information stored in the order.
     */

    $result = $conn->query("
        SELECT
            o.*,

            /* Current user information */
            u.email AS current_email,
            u.full_name AS current_full_name,
            u.phone AS current_phone,
            u.notif_email,
            u.notif_sms

        FROM part_orders o

        LEFT JOIN users u
            ON u.id = o.user_id

        WHERE (o.is_archived IS NULL OR o.is_archived = 0)

        ORDER BY o.created_at DESC
    ");

    if (!$result) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Failed to load orders: ' . $conn->error
        ]);
        exit;
    }

    $orders = [];

    while ($row = $result->fetch_assoc()) {

        /*
         * Use CURRENT user data when available.
         * Otherwise preserve the original order data.
         */

        if (
            isset($row['current_full_name']) &&
            trim((string)$row['current_full_name']) !== ''
        ) {
            $row['customer_name'] = $row['current_full_name'];
        }

        if (
            isset($row['current_phone']) &&
            trim((string)$row['current_phone']) !== ''
        ) {
            $row['contact'] = $row['current_phone'];
        }

        if (
            isset($row['current_email']) &&
            trim((string)$row['current_email']) !== ''
        ) {
            $row['email'] = $row['current_email'];
        }

        /*
         * Decode items safely.
         */
        $decodedItems = json_decode(
            $row['items'] ?? '',
            true
        );

        $row['items'] = is_array($decodedItems)
            ? $decodedItems
            : [];

        /*
         * Remove helper fields from the API response.
         * The dashboard doesn't need these.
         */
        unset(
            $row['current_email'],
            $row['current_full_name'],
            $row['current_phone']
        );

        $orders[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'orders' => $orders
    ]);

    exit;
}

if ($action === 'update_status') {
    $newStatus = $postData['status'] ?? '';
    $allowed   = ['pending', 'approved', 'delivered', 'cancelled', 'completed'];

    if (!$order_id || !in_array($newStatus, $allowed, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid order or status.']);
        exit;
    }

    // Admin's reply/reason when declining/cancelling an order — optional,
    // only meaningful when the new status is 'cancelled'.
    $cancellationReply = trim((string)($postData['cancellation_reply'] ?? ''));

    if ($newStatus === 'cancelled') {
        $stmt = $conn->prepare("UPDATE part_orders SET status = ?, cancellation_reply = ? WHERE id = ?");
        $replyOrNull = $cancellationReply !== '' ? $cancellationReply : null;
        $stmt->bind_param("ssi", $newStatus, $replyOrNull, $order_id);
    } else {
        $stmt = $conn->prepare("UPDATE part_orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $order_id);
    }

    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update order status.']);
        exit;
    }

    echo json_encode(['status' => 'success', 'message' => 'Order status updated.']);

    // ── NOTIFY CUSTOMER (cancellations only) ────────────────────
    // Sent after the response so the admin UI isn't held up waiting
    // on email/SMS delivery.
    if ($newStatus === 'cancelled') {
        $orderStmt = $conn->prepare("
            SELECT o.customer_name, o.total_price,
                   u.email, u.full_name, u.phone, u.notif_email, u.notif_sms
            FROM part_orders o
            LEFT JOIN users u ON u.id = o.user_id
            WHERE o.id = ?
        ");
        $orderStmt->bind_param("i", $order_id);
        $orderStmt->execute();
        $orderRow = $orderStmt->get_result()->fetch_assoc();

        if ($orderRow && !empty($orderRow['email'])) {
            $orderNo   = 'EJR-ORD-' . date('Y') . '-' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
            $firstName = explode(' ', trim($orderRow['full_name'] ?? $orderRow['customer_name'] ?? 'Customer'))[0];

            if ((int)($orderRow['notif_email'] ?? 1) === 1) {
                $html = '
                <div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
                    <div style="background:#fff;border-radius:12px;max-width:520px;margin:auto;padding:30px;border:1px solid #e3e8ef;">
                        <div style="text-align:center;margin-bottom:24px;">
                            <h2 style="color:#e62429;margin:0;">EJR Garage</h2>
                            <p style="color:#5f6b7a;margin:4px 0 0;">Order Update</p>
                        </div>
                        <p style="color:#1e2a36;">Hi <strong>' . htmlspecialchars($orderRow['full_name'] ?? $orderRow['customer_name']) . '</strong>,</p>
                        <p style="color:#5f6b7a;">Your order <strong>' . $orderNo . '</strong> has been:</p>
                        <div style="text-align:center;margin:16px 0;">
                            <span style="background:#f8d7da;color:#842029;padding:6px 20px;border-radius:20px;font-size:15px;font-weight:bold;">CANCELLED</span>
                        </div>' .
                        ($cancellationReply !== '' ? '
                        <div style="background:#f8d7da;border-radius:8px;padding:16px 20px;margin:0 0 20px;">
                            <p style="color:#842029;margin:0;font-size:13px;font-weight:bold;">Message from EJR Garage</p>
                            <p style="color:#842029;margin:6px 0 0;font-size:14px;">' . htmlspecialchars($cancellationReply) . '</p>
                        </div>' : '') . '
                        <div style="text-align:center;margin-top:24px;">
                            <a href="https://ejrgarage.onrender.com/my_orders.php" style="background:#e62429;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;">
                                View My Orders
                            </a>
                        </div>
                        <p style="color:#a0aab5;font-size:11px;text-align:center;margin-top:24px;">EJR Garage &mdash; Santo Tomas, Batangas</p>
                    </div>
                </div>';

                sendEmailViaBrevo($orderRow['email'], $orderRow['full_name'] ?? $orderRow['customer_name'], "EJR Garage - Order Cancelled", $html);
            }

            if ((int)($orderRow['notif_sms'] ?? 0) === 1 && !empty($orderRow['phone'])) {
                $smsText = "EJR Garage: Hi {$firstName}, your order {$orderNo} has been CANCELLED.";
                if ($cancellationReply !== '') {
                    $smsText .= " Note: {$cancellationReply}";
                }
                sendSmsViaSemaphore($orderRow['phone'], $smsText);
            }
        }
    }

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
    $adminId = (int)($_SESSION['user_id'] ?? 0);
    if (ejr_archive_row($conn, 'part_orders', $order_id, $adminId)) {
        echo json_encode(['status' => 'success', 'message' => 'Order archived.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Order not found or already archived.']);
    }
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
        'sender'      => ['name' => BREVO_SENDER_NAME, 'email' => BREVO_SENDER_EMAIL],
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