<?php
header('Content-Type: application/json');
require_once 'db_config.php';
require_once 'rate_limit.php';
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
    ejr_rate_limit($conn, "delete_account", ejr_get_ip() . "|" . $userId, 3, 300);
} catch (\Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// ── GET USER INFO ─────────────────────────────────────────────
$stmt = $conn->prepare("SELECT email, full_name, password_hash FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    exit;
}

// ── VERIFY OTP ────────────────────────────────────────────────
session_start();
$data = json_decode(file_get_contents('php://input'), true);
$otp  = $data['otp'] ?? '';

if (
    empty($_SESSION['delete_otp']) ||
    empty($_SESSION['delete_user_id']) ||
    $_SESSION['delete_user_id'] != $userId ||
    time() > $_SESSION['delete_otp_expires']
) {
    echo json_encode(['status' => 'error', 'message' => 'OTP expired. Please request a new one.']);
    exit;
}

if ($_SESSION['delete_otp'] != $otp) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid OTP code.']);
    exit;
}

// ── DELETE ACCOUNT ────────────────────────────────────────────
try {
    $conn->begin_transaction();

    // Cancel any pending appointments first
    $conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE user_id = ? AND status = 'Pending'")->bind_param("i", $userId) && true;

    // Delete vehicles
    $del1 = $conn->prepare("DELETE FROM vehicles WHERE user_id = ?");
    $del1->bind_param("i", $userId);
    $del1->execute();

    // Delete user
    $del2 = $conn->prepare("DELETE FROM users WHERE id = ?");
    $del2->bind_param("i", $userId);
    $del2->execute();

    $conn->commit();

    // Clear OTP session
    unset($_SESSION['delete_otp'], $_SESSION['delete_user_id'], $_SESSION['delete_otp_expires']);

    // ── SEND FAREWELL EMAIL ───────────────────────────────────
    try {
        $emailSubject = 'EJR Garage - Account Deleted';
        $emailBody    = '
        <div style="background:#f4f6f8;padding:30px;font-family:Arial,sans-serif;">
            <div style="background:#fff;border-radius:12px;max-width:520px;margin:auto;padding:30px;border:1px solid #e3e8ef;">
                <div style="text-align:center;margin-bottom:24px;">
                    <h2 style="color:#e62429;margin:0;">EJR Garage</h2>
                    <p style="color:#5f6b7a;margin:4px 0 0;">Account Deletion Confirmation</p>
                </div>
                <p style="color:#1e2a36;">Hi <strong>' . htmlspecialchars($user['full_name']) . '</strong>,</p>
                <p style="color:#5f6b7a;">Your account has been permanently deleted. All your data including vehicles and appointments have been removed from our system.</p>
                <p style="color:#5f6b7a;">We are sorry to see you go. If this was a mistake or you change your mind, you are always welcome to create a new account.</p>
                <div style="text-align:center;margin-top:24px;">
                    <a href="https://ejrgarage.onrender.com/register.php"
                       style="background:#e62429;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;">
                        Create New Account
                    </a>
                </div>
                <p style="color:#a0aab5;font-size:11px;text-align:center;margin-top:24px;">
                    EJR Garage &mdash; Santo Tomas, Batangas
                </p>
            </div>
        </div>';
        sendEmailViaBrevo($user['email'], $user['full_name'], $emailSubject, $emailBody);
    } catch (\Throwable $e) {
        // Silent
    }


    // BUGFIX: Respond to browser BEFORE sending email so PHP execution
    // timeout can't produce a blank response. See save_booking.php for details.
    $responseBody = json_encode(['status' => 'success', 'message' => 'Account deleted successfully.']);
    if (function_exists('fastcgi_finish_request')) {
        echo $responseBody; fastcgi_finish_request();
    } else {
        ob_start(); echo $responseBody;
        header('Content-Length: ' . ob_get_length());
        header('Connection: close');
        ob_end_flush(); @ob_flush(); flush();
    }

    echo json_encode(['status' => 'success', 'message' => 'Account deleted successfully.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete account: ' . $e->getMessage()]);
}
?>