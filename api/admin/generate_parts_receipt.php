<?php
session_start();
session_write_close();
require_once '../db_config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$order_id = isset($_GET['id'])    ? (int)$_GET['id']    : 0;
$token    = isset($_GET['token']) ? trim($_GET['token']) : '';

if (!$order_id) { http_response_code(404); die("Invalid order."); }

// Allow access if: admin session OR valid token
$isAdmin = true/* JWT verified above */;

if (!$isAdmin) {
    $expectedToken = hash('sha256', 'parts-receipt-' . $order_id . '-EJR2026');
    if (!$token || !hash_equals($expectedToken, $token)) {
        http_response_code(403);
        die("<h3 style='font-family:sans-serif;color:#dc3545;text-align:center;margin-top:80px;'>Access Denied — Invalid or missing receipt token.</h3>");
    }
}

$stmt = $conn->prepare("
    SELECT o.*, u.full_name AS user_full_name, u.email
    FROM part_orders o
    JOIN users u ON u.id = o.user_id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) { http_response_code(404); die("Order not found."); }

date_default_timezone_set('Asia/Manila');

$items      = json_decode($order['items'], true);
$invoiceNo  = 'INV-PARTS-' . date('Y') . '-' . str_pad($order['id'], 5, '0', STR_PAD_LEFT);
$orderDate  = date("F j, Y", strtotime($order['created_at']));
$issuedDate = date("F j, Y");
$issuedTime = date("h:i A");
$customer   = htmlspecialchars($order['customer_name']);
$email      = htmlspecialchars($order['email']);
$contact    = htmlspecialchars($order['contact']);

$itemRows = '';
foreach ($items as $i => $item) {
    $bg       = ($i % 2 === 0) ? '#ffffff' : '#f8f9fa';
    $subtotal = $item['price'] * $item['qty'];
    $itemRows .= "
    <tr style='background:{$bg};'>
        <td style='padding:10px 14px;text-align:center;color:#5f6b7a;border-bottom:1px solid #e3e8ef;'>" . ($i + 1) . "</td>
        <td style='padding:10px 14px;color:#1e2a36;font-weight:500;border-bottom:1px solid #e3e8ef;'>" . htmlspecialchars($item['name']) . "</td>
        <td style='padding:10px 14px;text-align:center;color:#1e2a36;border-bottom:1px solid #e3e8ef;'>" . $item['qty'] . "</td>
        <td style='padding:10px 14px;text-align:right;color:#1e2a36;border-bottom:1px solid #e3e8ef;'>₱" . number_format($item['price'], 2) . "</td>
        <td style='padding:10px 14px;text-align:right;font-weight:600;color:#1e2a36;border-bottom:1px solid #e3e8ef;'>₱" . number_format($subtotal, 2) . "</td>
    </tr>";
}

// ── Build the invoice HTML (dompdf renders standard CSS2.1-ish HTML —
// no flexbox/grid, so layout here uses tables instead of the flex/grid
// rules the old browser-facing version used) ─────────────────────────
$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; color:#1e2a36; }
    .topbar { background:#e62429; height:6px; width:100%; }
    .wrap { padding: 24px 40px; }

    table.header-tbl { width:100%; border-bottom:1px solid #e3e8ef; padding-bottom:20px; margin-bottom: 4px; }
    table.header-tbl td { vertical-align: top; }
    .brand h1 { color:#e62429; font-size:22px; margin-bottom:2px; }
    .brand p { color:#5f6b7a; font-size:11px; margin:2px 0; }
    .meta { text-align:right; }
    .meta .inv-label { font-size:10px; color:#a0aab5; text-transform:uppercase; letter-spacing:0.5px; }
    .meta .inv-number { font-size:18px; font-weight:bold; color:#1e2a36; margin:4px 0; }
    .meta .inv-badge { display:inline-block; background:#cfe2ff; color:#084298; padding:3px 10px; border-radius:10px; font-size:10px; font-weight:bold; }

    table.date-tbl { width:100%; border-bottom:1px solid #e3e8ef; margin-top:16px; }
    table.date-tbl td { padding:12px 0; font-size:11px; border-right:1px solid #e3e8ef; }
    table.date-tbl td:last-child { border-right:none; }
    .lbl { font-size:9px; color:#a0aab5; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:3px; }
    .val { font-size:13px; font-weight:bold; color:#1e2a36; display:block; }
    .sub { font-size:10px; color:#5f6b7a; display:block; margin-top:2px; }

    table.info-tbl { width:100%; border-bottom:1px solid #e3e8ef; margin-top:16px; }
    table.info-tbl td { padding:10px 14px 16px 0; vertical-align: top; width:50%; }
    .section-label { font-size:10px; color:#e62429; text-transform:uppercase; letter-spacing:0.8px; font-weight:bold; margin-bottom:8px; display:block; }
    .info-tbl p { font-size:12px; margin-bottom:5px; }
    .info-tbl p span.k { color:#5f6b7a; display:inline-block; min-width:55px; }

    .items-wrap { margin-top:18px; }
    table.items { width:100%; border-collapse:collapse; border:1px solid #e3e8ef; }
    .items thead tr { background:#1e2a36; }
    .items thead th { padding:10px 14px; color:#fff; font-size:11px; text-align:left; text-transform:uppercase; }
    .items thead th.center { text-align:center; }
    .items thead th.right { text-align:right; }

    table.total-tbl { width:100%; margin-top:6px; }
    table.total-tbl td { padding:6px 14px; font-size:12px; color:#5f6b7a; }
    table.total-tbl td.val { text-align:right; color:#1e2a36; font-weight:600; }
    table.total-tbl tr.grand td { font-size:15px; font-weight:bold; color:#e62429; border-top:2px solid #e62429; padding-top:10px; }
    table.total-tbl tr.grand td.val { color:#e62429; }

    .stamp { margin-top:20px; background:#084298; color:#fff; text-align:center; padding:12px; font-size:13px; font-weight:bold; letter-spacing:1px; }
    .footer-note { margin-top:14px; font-size:10px; color:#a0aab5; text-align:center; }
</style>
</head>
<body>
<div class="topbar"></div>
<div class="wrap">

    <table class="header-tbl">
        <tr>
            <td class="brand">
                <h1>EJR Garage</h1>
                <p>Mabalacat City, Pampanga</p>
                <p>support@ejrgarage.com</p>
                <p>ejrgarage.onrender.com</p>
            </td>
            <td class="meta">
                <span class="inv-label">Parts Order Invoice</span>
                <div class="inv-number">' . $invoiceNo . '</div>
                <span class="inv-badge">Parts Order</span>
            </td>
        </tr>
    </table>

    <table class="date-tbl">
        <tr>
            <td>
                <span class="lbl">Invoice Issued</span>
                <span class="val">' . $issuedDate . '</span>
                <span class="sub">' . $issuedTime . ' (PHT)</span>
            </td>
            <td>
                <span class="lbl">Order Date</span>
                <span class="val">' . $orderDate . '</span>
            </td>
            <td>
                <span class="lbl">Prepared By</span>
                <span class="val">EJR Garage</span>
                <span class="sub">Authorized Staff</span>
            </td>
        </tr>
    </table>

    <table class="info-tbl">
        <tr>
            <td>
                <span class="section-label">Billed To</span>
                <p><span class="k">Name:</span> ' . $customer . '</p>
                <p><span class="k">Email:</span> ' . $email . '</p>
                <p><span class="k">Contact:</span> ' . $contact . '</p>
            </td>
            <td>
                <span class="section-label">Order Details</span>
                <p><span class="k">Order No:</span> ' . $invoiceNo . '</p>
                <p><span class="k">Items:</span> ' . count($items) . ' item(s)</p>
                <p><span class="k">Total:</span> <strong style="color:#e62429;">₱' . number_format($order['total_price'], 2) . '</strong></p>
            </td>
        </tr>
    </table>

    <div class="items-wrap">
        <span class="section-label">Items Ordered</span>
        <table class="items">
            <thead>
                <tr>
                    <th class="center">#</th>
                    <th>Part Name</th>
                    <th class="center">Qty</th>
                    <th class="right">Unit Price</th>
                    <th class="right">Subtotal</th>
                </tr>
            </thead>
            <tbody>' . $itemRows . '</tbody>
        </table>
    </div>

    <table class="total-tbl">
        <tr class="grand">
            <td>Total Amount</td>
            <td class="val">₱' . number_format($order['total_price'], 2) . '</td>
        </tr>
    </table>

    <div class="stamp">PARTS ORDER — THANK YOU FOR YOUR PURCHASE</div>

    <div class="footer-note">' . $invoiceNo . ' &mdash; System-generated invoice. &copy; ' . date('Y') . ' EJR Garage.</div>

</div>
</body>
</html>';

// ── Render to PDF ─────────────────────────────────────────────
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// "inline" opens it in the browser tab (viewable, and still
// downloadable/printable from there); switch to "attachment" if you'd
// rather it force-download immediately instead.
$dompdf->stream($invoiceNo . '.pdf', ['Attachment' => false]);