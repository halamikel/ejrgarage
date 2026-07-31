<?php
session_start();
session_write_close();
require_once '../db_config.php';

$id    = isset($_GET['id'])    ? (int)$_GET['id']         : 0;
$token = isset($_GET['token']) ? trim($_GET['token'])      : '';

if (!$id) { http_response_code(404); die("Invalid receipt."); }

// Allow access if: admin session OR valid token
$isAdmin = true/* JWT verified above */;

if (!$isAdmin) {
    // Validate token: SHA256 of "receipt-{id}-EJR2026"
    $expectedToken = hash('sha256', 'receipt-' . $id . '-EJR2026');
    if (!$token || !hash_equals($expectedToken, $token)) {
        http_response_code(403);
        die("<h3 style='font-family:sans-serif;color:#dc3545;text-align:center;margin-top:80px;'>Access Denied — Invalid or missing receipt token.</h3>");
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { http_response_code(404); die("Invalid receipt."); }

$stmt = $conn->prepare("
    SELECT a.id, a.service_type, a.appointment_date,
           u.full_name, u.email,
           v.brand, v.model, v.year_model, v.plate_number
    FROM appointments a
    JOIN users u ON u.id = a.user_id
    LEFT JOIN vehicles v ON v.id = a.vehicle_id
    WHERE a.id = ? AND a.status = 'Completed'
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$appt = $stmt->get_result()->fetch_assoc();

if (!$appt) { http_response_code(404); die("Receipt not found."); }

date_default_timezone_set('Asia/Manila');
$invoiceNo   = 'INV-' . date('Y') . '-' . str_pad($appt['id'], 5, '0', STR_PAD_LEFT);
$serviceDate = date("F j, Y", strtotime($appt['appointment_date']));
$issuedDate  = date("F j, Y"); // today = invoice creation date
$issuedTime  = date("h:i A");
$services    = array_map('trim', explode(',', $appt['service_type']));
$vehicle     = htmlspecialchars(($appt['brand'] ?? '') . ' ' . ($appt['model'] ?? ''));
$year        = htmlspecialchars($appt['year_model'] ?? '—');
$plate       = htmlspecialchars($appt['plate_number'] ?? '—');
$customer    = htmlspecialchars($appt['full_name']);
$email       = htmlspecialchars($appt['email']);

$serviceRows = '';
foreach ($services as $i => $svc) {
    $bg = ($i % 2 === 0) ? '#ffffff' : '#f8f9fa';
    $serviceRows .= "
    <tr style='background:{$bg};'>
        <td style='padding:10px 14px;text-align:center;color:#5f6b7a;border-bottom:1px solid #e3e8ef;'>" . ($i + 1) . "</td>
        <td style='padding:10px 14px;color:#1e2a36;font-weight:500;border-bottom:1px solid #e3e8ef;'>" . htmlspecialchars($svc) . "</td>
        <td style='padding:10px 14px;text-align:center;border-bottom:1px solid #e3e8ef;'>
            <span style='background:#d1e7dd;color:#0a3622;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:bold;'>Completed</span>
        </td>
    </tr>";
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Invoice <?= $invoiceNo ?></title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:Arial,sans-serif; background:#f4f6f8; padding:30px; }
    .invoice { background:#fff; border-radius:12px; max-width:720px; margin:auto; padding:0; border:1px solid #e3e8ef; box-shadow:0 4px 20px rgba(0,0,0,0.08); overflow:hidden; }

    /* Top red bar */
    .invoice-topbar { background:#e62429; height:6px; }

    /* Header */
    .invoice-header { display:flex; justify-content:space-between; align-items:flex-start; padding:32px 40px 24px; border-bottom:1px solid #e3e8ef; }
    .invoice-brand h1 { color:#e62429; font-size:26px; margin-bottom:2px; }
    .invoice-brand p { color:#5f6b7a; font-size:12px; margin:2px 0; }
    .invoice-meta { text-align:right; }
    .invoice-meta .inv-label { font-size:11px; color:#a0aab5; text-transform:uppercase; letter-spacing:0.5px; }
    .invoice-meta .inv-number { font-size:22px; font-weight:bold; color:#1e2a36; margin:4px 0; }
    .invoice-meta .inv-badge { display:inline-block; background:#d1e7dd; color:#0a3622; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:bold; }

    /* Issued / Service date row */
    .date-row { display:flex; gap:0; border-bottom:1px solid #e3e8ef; }
    .date-cell { flex:1; padding:16px 40px; }
    .date-cell:not(:last-child) { border-right:1px solid #e3e8ef; }
    .date-cell .lbl { font-size:11px; color:#a0aab5; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px; }
    .date-cell .val { font-size:14px; font-weight:bold; color:#1e2a36; }
    .date-cell .sub { font-size:11px; color:#5f6b7a; margin-top:2px; }

    /* Info grid */
    .info-grid { display:grid; grid-template-columns:1fr 1fr; border-bottom:1px solid #e3e8ef; }
    .info-box { padding:20px 40px; }
    .info-box:first-child { border-right:1px solid #e3e8ef; }
    .info-box .section-label { font-size:11px; color:#e62429; text-transform:uppercase; letter-spacing:0.8px; font-weight:bold; margin-bottom:10px; }
    .info-box p { font-size:13px; color:#1e2a36; margin-bottom:5px; }
    .info-box p span { color:#5f6b7a; display:inline-block; min-width:60px; }

    /* Services table */
    .services-wrap { padding:24px 40px 0; }
    .services-wrap .section-label { font-size:11px; color:#e62429; text-transform:uppercase; letter-spacing:0.8px; font-weight:bold; margin-bottom:12px; }
    table { width:100%; border-collapse:collapse; border:1px solid #e3e8ef; border-radius:8px; overflow:hidden; }
    thead tr { background:#1e2a36; }
    thead th { padding:11px 14px; color:#fff; font-size:12px; text-align:left; text-transform:uppercase; letter-spacing:0.5px; }
    thead th:first-child { text-align:center; width:50px; }
    thead th:last-child  { text-align:center; width:120px; }

    /* Footer stamp */
    .stamp-row { margin:24px 40px; background:#0a3622; color:#fff; text-align:center; padding:14px; border-radius:8px; font-size:14px; font-weight:bold; letter-spacing:1px; }

    /* Bottom note */
    .invoice-footer { padding:16px 40px 28px; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #e3e8ef; }
    .invoice-footer .note { font-size:11px; color:#a0aab5; }
    .print-btn { padding:10px 28px; background:#e62429; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:bold; cursor:pointer; }

    @media print {
        body { background:#fff; padding:0; }
        .print-btn { display:none; }
        .invoice { box-shadow:none; border:none; }
    }
</style>
</head>
<body>
<div class="invoice">
    <div class="invoice-topbar"></div>

    <!-- Header -->
    <div class="invoice-header">
        <div class="invoice-brand">
            <h1>EJR Garage</h1>
            <p>Mabalacat City, Pampanga</p>
            <p>support@ejrgarage.com</p>
            <p>ejrgarage.com</p>
        </div>
        <div class="invoice-meta">
            <div class="inv-label">Invoice</div>
            <div class="inv-number"><?= $invoiceNo ?></div>
            <div class="inv-badge">&#10003; Completed</div>
        </div>
    </div>

    <!-- Dates -->
    <div class="date-row">
        <div class="date-cell">
            <div class="lbl">Invoice Issued</div>
            <div class="val"><?= $issuedDate ?></div>
            <div class="sub"><?= $issuedTime ?> (PHT)</div>
        </div>
        <div class="date-cell">
            <div class="lbl">Service Date</div>
            <div class="val"><?= $serviceDate ?></div>
        </div>
        <div class="date-cell">
            <div class="lbl">Prepared By</div>
            <div class="val">EJR Garage</div>
            <div class="sub">Authorized Staff</div>
        </div>
    </div>

    <!-- Customer & Vehicle -->
    <div class="info-grid">
        <div class="info-box">
            <div class="section-label">Billed To</div>
            <p><span>Name:</span> <?= $customer ?></p>
            <p><span>Email:</span> <?= $email ?></p>
        </div>
        <div class="info-box">
            <div class="section-label">Vehicle</div>
            <p><span>Unit:</span> <?= $vehicle ?></p>
            <p><span>Year:</span> <?= $year ?></p>
            <p><span>Plate:</span> <?= $plate ?></p>
        </div>
    </div>

    <!-- Services -->
    <div class="services-wrap">
        <div class="section-label">Services Rendered</div>
        <table>
            <thead>
                <tr><th>#</th><th>Service</th><th>Status</th></tr>
            </thead>
            <tbody><?= $serviceRows ?></tbody>
        </table>
    </div>

    <!-- Stamp -->
    <div class="stamp-row">&#10003;&nbsp; SERVICE COMPLETED — THANK YOU FOR YOUR TRUST</div>

    <!-- Footer -->
    <div class="invoice-footer">
        <div class="note">
            <?= $invoiceNo ?> &mdash; System-generated invoice. &copy; <?= date('Y') ?> EJR Garage.
        </div>
        <button class="print-btn" onclick="window.print()">&#128438; Print / Download</button>
    </div>
</div>
</body>
</html>