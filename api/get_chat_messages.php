<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../includes/php-jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ── AUTH: JWT bearer (mobile app) first, PHP session (web) as fallback ──
// Previously this trusted a raw ?uid= query param with no verification
// at all — anyone could read any other user's chat history just by
// guessing IDs. Now the user id always comes from a verified identity.
$user_id = 0;
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';
if (preg_match('/bearer\s(\S+)/i', $authHeader, $m)) {
    try {
        if (!defined('JWT_SECRET')) require_once __DIR__ . '/env.php';
        $decoded = JWT::decode($m[1], new Key(JWT_SECRET, 'HS256'));
        $user_id = (int)($decoded->uid ?? 0);
    } catch (Exception $e) {
        // Invalid/expired token — fall through to session check below.
    }
}
if ($user_id === 0) {
    $user_id = (int)($_SESSION['user_id'] ?? 0);
}

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'messages' => [], 'chat_session' => 'bot', 'message' => 'Not logged in.']);
    exit;
}

try {
    $sessStmt = $conn->prepare("SELECT chat_session FROM users WHERE id = ?");
    $sessStmt->bind_param("i", $user_id);
    $sessStmt->execute();
    $sessRow     = $sessStmt->get_result()->fetch_assoc();
    $currentMode = $sessRow['chat_session'] ?? 'bot';

    $stmt = $conn->prepare("SELECT sender, message, created_at as time FROM ejr_chat WHERE user_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'sender'  => $row['sender'],
            'message' => $row['message'],
            'time'    => date('h:i A', strtotime($row['time']))
        ];
    }

    echo json_encode([
        'status'       => 'success',
        'messages'     => $messages,
        'chat_session' => $currentMode,
        'count'        => count($messages)
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>