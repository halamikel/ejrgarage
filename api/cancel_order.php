<?php
header("Content-Type: application/json");
require_once 'db_config.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';
require_once 'mailer_helper.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ── JWT AUTH ─────────────────────────────────────────────────
if (!defined("JWT_SECRET")) require_once __DIR__ . "/env.php";
$secret_key = JWT_SECRET;
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';

if (!preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token missing']);
    exit;
}

try {
    $decoded = JWT::decode($matches[1], new Key($secret_key, 'HS256'));
    $userId  = $decoded->uid ?? $decoded->id;
} catch (\Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// ── GET INPUT ─────────────────────────────────────────────────
$data     = json_decode(file_get_contents("php://input"), true);
$order_id = $data['id'] ?? null;

if (!$order_id) {
    echo json_encode(["status" => "error", "message" => "Missing order ID"]);
    exit;
}

// ── SELF-HEALING: cancellation_reply column ─────────────────
// Mirrors the pattern used elsewhere so this endpoint doesn't
// break on installs where the column hasn't been added yet.
$replyColCheck = $conn->query("SHOW COLUMNS FROM part_orders LIKE 'cancellation_reply'");
if ($replyColCheck && $replyColCheck->num_rows === 0) {
    $conn->query("ALTER TABLE part_orders ADD COLUMN cancellation_reply VARCHAR(255) NULL");
}

// ── FETCH ORDER + USER INFO BEFORE CANCELLING (scoped to this user) ──
$fetchStmt = $conn->prepare("
    SELECT o.status, o.total_price, o.customer_name,
           u.email, u.full_name, u.phone, u.notif_email, u.notif_sms
    FROM part_orders o
    LEFT JOIN users u ON u.id = o.user_id
    WHERE o.id = ? AND o.user_id = ? LIMIT 1
");
$fetchStmt->bind_param("ii", $order_id, $userId);
$fetchStmt->execute();
$order = $fetchStmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(["status" => "error", "message" => "Order not found."]);
    exit;
}

if ($order['status'] !== 'pending') {
    echo json_encode(["status" => "error", "message" => "Could not cancel. Order may already be processed."]);
    exit;
}

// ── CANCEL ORDER ────────────────────────────────────────────────
$stmt = $conn->prepare("UPDATE part_orders SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'");
$stmt->bind_param("ii", $order_id, $userId);
$stmt->execute();

if ($stmt->affected_rows <= 0) {
    echo json_encode(["status" => "error", "message" => "Could not cancel. Order may already be processed."]);
    exit;
}

// ── RESPOND IMMEDIATELY, THEN NOTIFY (best-effort) ────────────
$responseBody = json_encode(["status" => "success", "message" => "Order cancelled successfully"]);
if (function_exists('fastcgi_finish_request')) {
    echo $responseBody;
    fastcgi_finish_request();
} else {
    ob_start();
    echo $responseBody;
    header('Content-Length: ' . ob_get_length());
    header('Connection: close');
    ob_end_flush();
    @ob_flush();
    flush();
}

// ── SEND CANCELLATION EMAIL (best-effort, after response sent) ─
if (!empty($order['email']) && (int)($order['notif_email'] ?? 1) === 1) {
    $orderNo = 'EJR-ORD-' . date('Y') . '-' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
    $name    = $order['full_name'] ?? $order['customer_name'] ?? 'Customer';
    $total   = '₱' . number_format((float)$order['total_price'], 2);

    $html = '
    <div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
        <div style="background:#fff;border-radius:12px;max-width:520px;margin:auto;padding:30px;border:1px solid #e3e8ef;">
            <div style="text-align:center;margin-bottom:24px;">
                <h2 style="color:#e62429;margin:0;">EJR Garage</h2>
                <p style="color:#5f6b7a;margin:4px 0 0;">Order Cancellation</p>
            </div>
            <p style="color:#1e2a36;">Hi <strong>' . htmlspecialchars($name) . '</strong>,</p>
            <p style="color:#5f6b7a;">Your order has been <strong>cancelled</strong> as requested. Here are the details:</p>
            <div style="background:#f8f9fa;border-radius:8px;padding:20px;margin:20px 0;">
                <table style="width:100%;border-collapse:collapse;font-size:14px;">
                    <tr>
                        <td style="color:#5f6b7a;padding:6px 0;width:40%;">Order No.</td>
                        <td style="color:#1e2a36;font-weight:bold;">' . $orderNo . '</td>
                    </tr>
                    <tr>
                        <td style="color:#5f6b7a;padding:6px 0;">Total</td>
                        <td style="color:#1e2a36;font-weight:bold;">' . $total . '</td>
                    </tr>
                    <tr>
                        <td style="color:#5f6b7a;padding:6px 0;">Status</td>
                        <td><span style="background:#f8d7da;color:#842029;padding:2px 10px;border-radius:20px;font-size:13px;font-weight:bold;">Cancelled</span></td>
                    </tr>
                </table>
            </div>
            <p style="color:#5f6b7a;">If this was a mistake, you're welcome to place a new order anytime.</p>
            <div style="text-align:center;margin-top:24px;">
                <a href="https://ejrgarage.onrender.com/available_parts.php"
                   style="background:#e62429;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;">
                    Browse Parts
                </a>
            </div>
            <p style="color:#a0aab5;font-size:11px;text-align:center;margin-top:24px;">
                EJR Garage &mdash; Santo Tomas, Batangas
            </p>
        </div>
    </div>';

    try {
        sendEmailViaBrevo($order['email'], $name, 'EJR Garage - Order Cancelled', $html);
    } catch (\Throwable $e) {
        // Silent — cancellation already saved
    }
}
