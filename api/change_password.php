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

if (!preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token missing']);
    exit;
}

try {
    $decoded = JWT::decode($matches[1], new Key($secret_key, 'HS256'));
    $userId  = $decoded->uid;
    ejr_rate_limit($conn, "change_password", ejr_get_ip() . "|" . $userId, 5, 300);
} catch (\Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: ' . $e->getMessage()]);
    exit;
}

$data             = json_decode(file_get_contents('php://input'), true);
$current_password = $data['current_password'] ?? '';
$new_password     = $data['new_password'] ?? '';
$confirm_password = $data['confirm_password'] ?? '';

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}
if ($new_password !== $confirm_password) {
    echo json_encode(['status' => 'error', 'message' => 'New passwords do not match.']);
    exit;
}
if (strlen($new_password) < 8) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters.']);
    exit;
}

// Fetch user
$stmt = $conn->prepare("SELECT email, full_name, password_hash FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    exit;
}

if (!password_verify($current_password, $user['password_hash'])) {
    echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect.']);
    exit;
}

$new_hash = password_hash($new_password, PASSWORD_BCRYPT);
$update   = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
$update->bind_param("si", $new_hash, $userId);
$update->execute();

if ($update->affected_rows > 0) {
    // Send email notification
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
        $mail->addAddress($user['email'], $user['full_name']);
        $mail->isHTML(true);
        $mail->Subject = 'EJR Garage - Password Changed';
        $mail->Body = '
        <div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
            <div style="background:#ffffff;border-radius:12px;max-width:480px;margin:auto;padding:30px;text-align:center;border:1px solid #e3e8ef;">
                <h2 style="color:#1e2a36;margin-bottom:10px;">Password Changed</h2>
                <p style="color:#5f6b7a;">Hi ' . htmlspecialchars($user['full_name']) . ',</p>
                <p style="color:#5f6b7a;">Your account password was just changed successfully.</p>
                <p style="color:#5f6b7a;">If you did not make this change, please contact us immediately or reset your password.</p>
                <div style="margin-top:20px;">
                    <a href="https://ejrgarage.onrender.com/login.php"
                       style="background:#e62429;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;">
                        Reset Password
                    </a>
                </div>
                <p style="color:#a0aab5;font-size:12px;margin-top:20px;">
                    If this was you, no further action is needed.
                </p>
            </div>
        </div>';
        $mail->send();
    } catch (MailException $e) {
        // Don't fail the request if email fails, password is already changed
    }


    // BUGFIX: Respond to browser BEFORE sending email so PHP execution
    // timeout can't produce a blank response. See save_booking.php for details.
    $responseBody = json_encode(['status' => 'success', 'message' => 'Password updated successfully.']);
    if (function_exists('fastcgi_finish_request')) {
        echo $responseBody; fastcgi_finish_request();
    } else {
        ob_start(); echo $responseBody;
        header('Content-Length: ' . ob_get_length());
        header('Connection: close');
        ob_end_flush(); @ob_flush(); flush();
    }

    echo json_encode(['status' => 'success', 'message' => 'Password updated successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No changes made. Try a different password.']);
}
?>