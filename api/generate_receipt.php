<?php
// ── DEPENDENCIES ──────────────────────────────────────────────
require_once '../db_config.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';
require '../includes/PHPMailer/Exception.php';
require '../includes/PHPMailer/PHPMailer.php';
require '../includes/PHPMailer/SMTP.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isAdmin = false;

// ── AUTH: check session (admin) or JWT (customer) ─────────────
session_start();
if (isset($_SESSION['user_id']) && ($_SESSION['role_id'] == 2 || $_SESSION['user_id'] == 21)) {
    $isAdmin = true;
} else {
    $headers    = array_change_key_case(getallheaders(), CASE_LOWER);
    $authHeader = $headers['authorization'] ?? $_GET['token'] ?? '';
    $token      = preg_replace('/^bearer\s+/i', '', $authHeader);
    try {
        if (!defined("JWT_SECRET")) require_once __DIR__ . "/env.php";
        $secret  = JWT_SECRET;
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        $userId  = $decoded->uid ?? $decoded->id;
    } catch (\Exception $e) {
        http_response_code(401);
        die("Unauthorized");
    }
}

// ── FETCH DATA ────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT a.id, a.service_type, a.appointment_date, a.user_id,
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

if (!$appt) {
    http_response_code(404);
    die("Receipt not found.");
}

// ── CUSTOMER: make sure they own it ──────────────────────────
if (!$isAdmin && $appt['user_id'] != $userId) {
    http_response_code(403);
    die("Forbidden.");
}

// ── DATA PREP ─────────────────────────────────────────────────
$receiptNo   = 'EJR-' . date('Y') . '-' . str_pad($appt['id'], 5, '0', STR_PAD_LEFT);
$date        = date("F j, Y", strtotime($appt['appointment_date']));
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
        <td style='padding:10px 14px;text-align:center;color:#5f6b7a; border-bottom:1px solid #e3e8ef;'>" . ($i + 1) . "</td>
        <td style='padding:10px 14px;color:#1e2a36;font-weight:500; border-bottom:1px solid #e3e8ef;'>" . htmlspecialchars($svc) . "</td>
        <td style='padding:10px 14px;text-align:center; border-bottom:1px solid #e3e8ef;'>
            <span style='background:#d1e7dd;color:#0a3622;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:bold;'>Completed</span>
        </td>
    </tr>";
}

// ── EMAIL LOGIC ───────────────────────────────────────────────
// Only send email if the 'send_email' trigger is in URL, or customize logic here
if (isset($_GET['send_email'])) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom(SMTP_USER, 'EJR Garage');
        $mail->addAddress($email, $customer);
        $mail->isHTML(true);
        $mail->Subject = "Service Receipt - $receiptNo";

        // Reuse the same design for the email body
        $mail->Body = "
        <div style='background:#f4f6f8; padding:30px; font-family: Arial, sans-serif;'>
            <div style='background:#fff; border-radius:12px; max-width:600px; margin:auto; padding:40px; border:1px solid #e3e8ef;'>
                <div style='text-align:center; margin-bottom:24px;'>
                    <h1 style='color:#e62429; font-size:28px; margin:0;'>EJR Garage</h1>
                    <p style='color:#5f6b7a; font-size:13px;'>Service Receipt</p>
                </div>
                <hr style='border:none; border-top:2px solid #e62429; margin:20px 0;'>
                <p style='color:#1e2a36;'>Hi <strong>$customer</strong>, your service is complete. Please find your receipt details below.</p>
                
                <div style='background:#f8f9fa; border-radius:8px; padding:14px 20px; margin-bottom:20px;'>
                    <table style='width:100%; font-size:13px;'>
                        <tr>
                            <td>Receipt No: <strong>$receiptNo</strong></td>
                            <td style='text-align:right;'>Date: <strong>$date</strong></td>
                        </tr>
                    </table>
                </div>

                <div style='margin-bottom:20px;'>
                    <h4 style='color:#e62429; font-size:14px; margin-bottom:10px;'>Vehicle: $vehicle ($plate)</h4>
                    <table style='width:100%; border-collapse:collapse; border:1px solid #e3e8ef;'>
                        <thead>
                            <tr style='background:#1e2a36; color:#fff;'>
                                <th style='padding:10px; font-size:12px; text-align:left;'>Service</th>
                                <th style='padding:10px; font-size:12px; text-align:center;'>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            $serviceRows
                        </tbody>
                    </table>
                </div>

                <div style='background:#0a3622; color:#fff; text-align:center; padding:14px; border-radius:8px; font-size:15px; font-weight:bold;'>
                    SERVICE COMPLETED
                </div>
                
                <p style='color:#a0aab5; font-size:11px; text-align:center; margin-top:20px;'>
                    Thank you for choosing EJR Garage!
                </p>
            </div>
        </div>";

        $mail->send();
    } catch (MailException $e) {
        // Silently fail or log error
    }
}

// ── OUTPUT HTML FOR BROWSER ──────────────────────────────────
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt <?= $receiptNo ?></title>
    <style>
        /* ... Keep your existing CSS styles here ... */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; background:#f4f6f8; padding:30px; }
        .receipt {
            background:#fff; border-radius:12px; max-width:680px;
            margin:auto; padding:40px; border:1px solid #e3e8ef;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .header { text-align:center; margin-bottom:24px; }
        .header h1 { color:#e62429; font-size:28px; margin-bottom:4px; }
        .header p { color:#5f6b7a; font-size:13px; }
        .divider { border:none; border-top:2px solid #e62429; margin:20px 0; }
        .meta { background:#f8f9fa; border-radius:8px; padding:14px 20px; display:flex; justify-content:space-between; margin-bottom:20px; }
        .meta div { font-size:13px; color:#5f6b7a; }
        .meta div strong { color:#1e2a36; display:block; font-size:14px; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
        .box { border:1px solid #e3e8ef; border-radius:8px; overflow:hidden; }
        .box-header { background:#f8f9fa; padding:10px 16px; font-size:13px; font-weight:bold; color:#e62429; border-bottom:1px solid #e3e8ef; }
        .box-body { padding:14px 16px; }
        .box-body p { font-size:13px; color:#1e2a36; margin-bottom:6px; }
        .box-body p span { color:#5f6b7a; font-weight:normal; }
        .section-title { font-size:14px; font-weight:bold; color:#e62429; margin-bottom:10px; }
        table { width:100%; border-collapse:collapse; border:1px solid #e3e8ef; border-radius:8px; overflow:hidden; }
        thead tr { background:#1e2a36; }
        thead th { padding:11px 14px; color:#fff; font-size:12px; text-align:left; text-transform:uppercase; letter-spacing:0.5px; }
        thead th:first-child { text-align:center; width:50px; }
        thead th:last-child  { text-align:center; width:110px; }
        .completed-bar { background:#0a3622; color:#fff; text-align:center; padding:14px; border-radius:8px; font-size:15px; font-weight:bold; margin:20px 0; letter-spacing:1px; }
        .footer { text-align:center; color:#a0aab5; font-size:11px; margin-top:20px; border-top:1px solid #e3e8ef; padding-top:16px; }
        .print-btn { display:block; width:fit-content; margin:0 auto 24px; padding:10px 28px; background:#e62429; color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:bold; cursor:pointer; }
        @media print {
            body { background:#fff; padding:0; }
            .print-btn { display:none; }
            .receipt { box-shadow:none; border:none; }
        }
    </style>
</head>
<body>
<div class="receipt">
    <div class="header">
        <h1>EJR Garage</h1>
        <p>Service Receipt</p>
        <p>Mabalacat City, Pampanga &mdash; support@ejrgarage.com</p>
    </div>
    <hr class="divider">
    <div class="meta">
        <div>Receipt No. <strong><?= $receiptNo ?></strong></div>
        <div>Date Completed <strong><?= $date ?></strong></div>
        <div>Status <strong style="color:#0a3622;">&#10003; Completed</strong></div>
    </div>
    <div class="grid">
        <div class="box">
            <div class="box-header">Customer Information</div>
            <div class="box-body">
                <p><span>Name: </span><?= $customer ?></p>
                <p><span>Email: </span><?= $email ?></p>
            </div>
        </div>
        <div class="box">
            <div class="box-header">Vehicle Information</div>
            <div class="box-body">
                <p><span>Vehicle: </span><?= $vehicle ?></p>
                <p><span>Year: </span><?= $year ?></p>
                <p><span>Plate No.: </span><?= $plate ?></p>
            </div>
        </div>
    </div>
    <div class="section-title">Services Performed</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Service</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?= $serviceRows ?>
        </tbody>
    </table>
    <div class="completed-bar">&#10003;&nbsp; SERVICE COMPLETED</div>
    <button class="print-btn" onclick="window.print()">
        &#128438; Download / Print Receipt
    </button>
    <div class="footer">
        <p>Thank you for trusting EJR Garage with your vehicle!</p>
        <p style="margin-top:6px;">ejrgarage.site.je &mdash; This is a system-generated receipt. <?= $receiptNo ?></p>
    </div>
</div>
</body>
</html>