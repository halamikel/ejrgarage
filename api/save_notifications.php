<?php
header('Content-Type: application/json');
require_once 'db_config.php';

// Setup JWT paths based on your structure
$root = dirname(__DIR__); // BUGFIX: __DIR__ is more reliable than DOCUMENT_ROOT on shared hosts
$libPath = $root . '/includes/php-jwt/';
require_once $libPath . 'Key.php';
require_once $libPath . 'JWT.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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
} catch (\Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$data        = json_decode(file_get_contents('php://input'), true);
$notifEmail  = isset($data['notif_email']) ? (int)$data['notif_email'] : 0;
$notifSms    = isset($data['notif_sms'])   ? (int)$data['notif_sms']   : 0;

$stmt = $conn->prepare("UPDATE users SET notif_email = ?, notif_sms = ? WHERE id = ?");
$stmt->bind_param("iii", $notifEmail, $notifSms, $userId);

if ($stmt->execute()) {
    // Return the values back to the frontend so it can sync LocalStorage
    echo json_encode([
        'status' => 'success', 
        'message' => 'Preferences saved.',
        'notif_email' => $notifEmail,
        'notif_sms' => $notifSms
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save preferences.']);
}

$stmt->close();
$conn->close();
?>