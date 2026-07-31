<?php
header('Content-Type: application/json');
require_once 'db_config.php';
require_once 'rate_limit.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';
require '../includes/PHPMailer/Exception.php';
require '../includes/PHPMailer/PHPMailer.php';
require '../includes/PHPMailer/SMTP.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

if (!defined("JWT_SECRET")) require_once __DIR__ . "/env.php";
$secret_key = JWT_SECRET;
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';

if (preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    try {
        $decoded = JWT::decode($matches[1], new Key($secret_key, 'HS256'));
        $userId  = $decoded->uid;
        ejr_rate_limit($conn, "remove_vehicle", ejr_get_ip() . "|" . $userId, 20, 300);

        $data      = json_decode(file_get_contents('php://input'), true);
        $vehicleId = $data['id'];

        // --- FETCH DETAILS BEFORE DELETION ---
        $infoStmt = $conn->prepare("
            SELECT v.brand, v.model, v.plate_number, v.transmission, u.email, u.full_name 
            FROM vehicles v 
            JOIN users u ON v.user_id = u.id 
            WHERE v.id = ? AND v.user_id = ?
        ");
        $infoStmt->bind_param("ii", $vehicleId, $userId);
        $infoStmt->execute();
        $details = $infoStmt->get_result()->fetch_assoc();

        if ($details) {
            $delStmt = $conn->prepare("DELETE FROM vehicles WHERE id = ? AND user_id = ?");
            $delStmt->bind_param("ii", $vehicleId, $userId);

            if ($delStmt->execute()) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USER;
                    $mail->Password   = SMTP_PASS;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;
            $mail->Timeout    = 15;
                    $mail->setFrom(SMTP_USER, 'EJR Garage');
                    $mail->addAddress($details['email'], $details['full_name']);
                    $mail->isHTML(true);
                    
                    $mail->Subject = 'EJR Garage - Vehicle Removed';
                    $mail->Body    = '
                    <div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
                        <div style="background:#fff;border-radius:12px;max-width:520px;margin:auto;padding:30px;border:1px solid #e3e8ef;">
                            <div style="text-align:center;margin-bottom:24px;">
                                <h2 style="color:#e62429;margin:0;">EJR Garage</h2>
                                <p style="color:#5f6b7a;margin:4px 0 0;">Vehicle Removal Notification</p>
                            </div>
                            <p style="color:#1e2a36;">Hi <strong>' . htmlspecialchars($details['full_name']) . '</strong>,</p>
                            <p style="color:#5f6b7a;">This is to confirm that the following vehicle has been <strong>removed</strong> from your account profile.</p>
                            
                            <div style="background:#f8f9fa;border-radius:8px;padding:20px;margin:20px 0;">
                                <h4 style="color:#1e2a36;margin:0 0 12px;">Vehicle Details</h4>
                                <table style="width:100%;border-collapse:collapse;font-size:14px;">
                                    <tr>
                                        <td style="color:#5f6b7a;padding:6px 0;width:40%;">Vehicle</td>
                                        <td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($details['brand'] . ' ' . $details['model']) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color:#5f6b7a;padding:6px 0;">Plate No.</td>
                                        <td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($details['plate_number']) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color:#5f6b7a;padding:6px 0;">Transmission</td>
                                        <td style="color:#1e2a36;font-weight:bold;">' . htmlspecialchars($details['transmission']) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color:#5f6b7a;padding:6px 0;">Status</td>
                                        <td>
                                            <span style="background:#d1e7dd;color:#0f5132;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:bold;display:inline-block;">
                                                Removed
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <p style="color:#5f6b7a;">If this was a mistake, you can add your vehicle back anytime from your dashboard.</p>
                            <div style="text-align:center;margin-top:24px;">
                                <a href="https://ejrgarage.com/my_vehicles.php"
                                   style="background:#e62429;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;">
                                    Go to Garage
                                </a>
                            </div>
                            <p style="color:#a0aab5;font-size:11px;text-align:center;margin-top:24px;">
                                EJR Garage &mdash; Santo Tomas, Batangas
                            </p>
                        </div>
                    </div>';
                    $mail->send();
                } catch (MailException $e) {
                    // Fail silently
                }

    // BUGFIX: Respond to browser BEFORE sending email so PHP execution
    // timeout can't produce a blank response. See save_booking.php for details.
    $responseBody = json_encode(["status" => "success"]);
    if (function_exists('fastcgi_finish_request')) {
        echo $responseBody; fastcgi_finish_request();
    } else {
        ob_start(); echo $responseBody;
        header('Content-Length: ' . ob_get_length());
        header('Connection: close');
        ob_end_flush(); @ob_flush(); flush();
    }

                echo json_encode(["status" => "success"]);
            }
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Invalid session"]);
    }
}