<?php
session_start();
header('Content-Type: application/json');
require_once 'db_config.php';

$root    = dirname(__DIR__);
$libPath = $root . '/includes/php-jwt/';
require_once $libPath . 'Key.php';
require_once $libPath . 'JWT.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

date_default_timezone_set('Asia/Manila');

if (!defined("JWT_SECRET")) require_once __DIR__ . "/env.php";
$secret_key = JWT_SECRET;

function ejr_get_bearer_token(): string {
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

function resolveAdminId(string $secret): int {
    $tok = ejr_get_bearer_token();
    if ($tok) {
        try {
            $d = \Firebase\JWT\JWT::decode($tok, new \Firebase\JWT\Key($secret, 'HS256'));
            $role = (int)($d->role ?? 0);
            $uid  = (int)($d->uid  ?? 0);
            if ($role === 2 || $uid === 21) return $uid;
        } catch (\Exception $e) {}
    }
    if (isset($_SESSION['user_id'])) {
        $role = (int)($_SESSION['role_id'] ?? 0);
        $uid  = (int)$_SESSION['user_id'];
        if ($role === 2 || $uid === 21) return $uid;
    }
    return 0;
}
$input      = json_decode(file_get_contents('php://input'), true) ?? [];
$type       = $input['type'] ?? '';

// ── ADMIN CHAT HANDLERS (session-based) ───────────────────────────
if ($type === 'admin_get_chat_users') {
    $admin_id = resolveAdminId($secret_key);
    if (!$admin_id) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit;
    }
    $admin_id = (int)$_SESSION['user_id'];
    $sql = "SELECT u.id, u.full_name, u.chat_session, c.message AS last_message,
                (SELECT COUNT(*) FROM ejr_chat WHERE user_id = u.id AND is_read = 0 AND sender = 'user') AS unread_count
            FROM users u
            INNER JOIN ejr_chat c ON u.id = c.user_id
            WHERE c.id IN (SELECT MAX(id) FROM ejr_chat GROUP BY user_id)
            AND u.id != ?
            ORDER BY c.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = []; $total_unread = 0;
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
        $total_unread += (int)$row['unread_count'];
    }
    echo json_encode(['status' => 'success', 'users' => $users, 'total_unread' => $total_unread]);
    exit;
}

if ($type === 'admin_get_messages') {
    $admin_id = resolveAdminId($secret_key);
    if (!$admin_id) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit;
    }
    $target_uid = (int)($input['user_id'] ?? 0);
    $sessStmt = $conn->prepare("SELECT chat_session FROM users WHERE id = ?");
    $sessStmt->bind_param("i", $target_uid); $sessStmt->execute();
    $sessRow = $sessStmt->get_result()->fetch_assoc();
    $currentMode = $sessRow['chat_session'] ?? 'bot';
    $stmt = $conn->prepare("SELECT sender, message, created_at as time FROM ejr_chat WHERE user_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("i", $target_uid); $stmt->execute();
    $result = $stmt->get_result(); $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = ['sender' => $row['sender'], 'message' => $row['message'], 'time' => date('h:i A', strtotime($row['time']))];
    }
    $markRead = $conn->prepare("UPDATE ejr_chat SET is_read = 1 WHERE user_id = ? AND sender = 'user'");
    $markRead->bind_param("i", $target_uid); $markRead->execute();
    echo json_encode(['status' => 'success', 'messages' => $messages, 'chat_session' => $currentMode, 'count' => count($messages)]);
    exit;
}

if ($type === 'admin_send') {
    $admin_id = resolveAdminId($secret_key);
    if (!$admin_id) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit;
    }
    $target_uid   = (int)($input['user_id'] ?? 0);
    $admin_msg    = trim($input['message'] ?? '');
    $admin_action = $input['action'] ?? 'send';
    if ($admin_action === 'accept') {
        $stmt = $conn->prepare("UPDATE users SET chat_session = 'live' WHERE id = ?");
        $stmt->bind_param("i", $target_uid); $stmt->execute();
        echo json_encode(['status' => 'success']); exit;
    }
    if ($admin_action === 'decline') {
        $stmt = $conn->prepare("UPDATE users SET chat_session = 'bot' WHERE id = ?");
        $stmt->bind_param("i", $target_uid); $stmt->execute();
        $declineMsg = "Sorry, no agents are available right now. Our bot will continue to assist you.";
        $ins = $conn->prepare("INSERT INTO ejr_chat (user_id, sender, message) VALUES (?, 'admin', ?)");
        $ins->bind_param("is", $target_uid, $declineMsg); $ins->execute();
        echo json_encode(['status' => 'success']); exit;
    }
    if (!empty($admin_msg)) {
        $stmt = $conn->prepare("INSERT INTO ejr_chat (user_id, sender, message) VALUES (?, 'admin', ?)");
        $stmt->bind_param("is", $target_uid, $admin_msg); $stmt->execute();
    }
    echo json_encode(['status' => 'success']); exit;
}

if ($type === 'admin_end_session') {
    $admin_id = resolveAdminId($secret_key);
    if (!$admin_id) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit;
    }
    $target_uid = (int)($input['user_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE users SET chat_session = 'bot' WHERE id = ?");
    $stmt->bind_param("i", $target_uid); $stmt->execute();
    $del = $conn->prepare("DELETE FROM ejr_chat WHERE user_id = ?");
    $del->bind_param("i", $target_uid); $del->execute();
    echo json_encode(['status' => 'success']); exit;
}

// ── CUSTOMER CHAT HANDLERS (JWT-based) ────────────────────────────
if (in_array($type, ['chat', 'get_messages', 'end_session'])) {
    $headers    = array_change_key_case(getallheaders(), CASE_LOWER);
    $authHeader = $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    $userId = 0;
    if (preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
        try {
            $decoded = JWT::decode($matches[1], new Key($secret_key, 'HS256'));
            $userId  = (int)($decoded->uid ?? 0);
        } catch (Exception $e) {}
    }
    if (!$userId) {
        echo json_encode(['status' => 'error', 'reply' => 'Please log in.', 'mode' => 'error']); exit;
    }

    if ($type === 'get_messages') {
        $sessStmt = $conn->prepare("SELECT chat_session FROM users WHERE id = ?");
        $sessStmt->bind_param("i", $userId); $sessStmt->execute();
        $sessRow = $sessStmt->get_result()->fetch_assoc();
        $currentMode = $sessRow['chat_session'] ?? 'bot';
        $stmt = $conn->prepare("SELECT sender, message, created_at as time FROM ejr_chat WHERE user_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("i", $userId); $stmt->execute();
        $result = $stmt->get_result(); $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = ['sender' => $row['sender'], 'message' => $row['message'], 'time' => date('h:i A', strtotime($row['time']))];
        }
        echo json_encode(['status' => 'success', 'messages' => $messages, 'chat_session' => $currentMode, 'count' => count($messages)]);
        exit;
    }

    if ($type === 'end_session') {
        $stmt = $conn->prepare("UPDATE users SET chat_session = 'bot' WHERE id = ?");
        $stmt->bind_param("i", $userId); $stmt->execute();
        $del = $conn->prepare("DELETE FROM ejr_chat WHERE user_id = ?");
        $del->bind_param("i", $userId); $del->execute();
        echo json_encode(['status' => 'success']); exit;
    }

    // type === 'chat'
    $action   = $input['action']  ?? 'chat';
    $user_msg = trim($input['message'] ?? '');

    if ($action === 'request_live') {
        // Clear leftover messages from a previous ended live session so
        // they don't reappear (both for the user and in the admin's
        // chat view) when a new live request starts.
        $clear = $conn->prepare("DELETE FROM ejr_chat WHERE user_id = ?");
        $clear->bind_param("i", $userId); $clear->execute();

        $stmt = $conn->prepare("UPDATE users SET chat_session = 'waiting' WHERE id = ?");
        $stmt->bind_param("i", $userId); $stmt->execute();
        $sysMsg = "A user has requested live assistance.";
        $stmt = $conn->prepare("INSERT INTO ejr_chat (user_id, sender, message) VALUES (?, 'admin', ?)");
        $stmt->bind_param("is", $userId, $sysMsg); $stmt->execute();
        echo json_encode(["status" => "success", "reply" => "Connecting to admin...", "mode" => "waiting"]); exit;
    }

    if ($action === 'cancel_live_request') {
        $stmt = $conn->prepare("UPDATE users SET chat_session = 'bot' WHERE id = ?");
        $stmt->bind_param("i", $userId); $stmt->execute();
        $stmt = $conn->prepare("DELETE FROM ejr_chat WHERE user_id = ? AND sender = 'admin' AND message = 'A user has requested live assistance.'");
        $stmt->bind_param("i", $userId); $stmt->execute();
        echo json_encode(['status' => 'success']); exit;
    }

    if (empty($user_msg)) {
        echo json_encode(["status" => "error", "reply" => "No message received.", "mode" => "bot"]); exit;
    }

    $sessCheck = $conn->prepare("SELECT chat_session FROM users WHERE id = ?");
    $sessCheck->bind_param("i", $userId); $sessCheck->execute();
    $sessResult = $sessCheck->get_result()->fetch_assoc();
    $isLive = ($sessResult['chat_session'] ?? 'bot') === 'live';

    if ($isLive) {
        $stmtUser = $conn->prepare("INSERT INTO ejr_chat (user_id, sender, message) VALUES (?, 'user', ?)");
        $stmtUser->bind_param("is", $userId, $user_msg); $stmtUser->execute();
        echo json_encode(["status" => "success", "reply" => "", "mode" => "live"]); exit;
    }

    $botReply = null;
    $allKeywords = $conn->query("SELECT keyword, response FROM ejr_bot");
    if ($allKeywords) {
        $lowerMsg = strtolower($user_msg);
        while ($kw = $allKeywords->fetch_assoc()) {
            $keyword = strtolower(trim($kw['keyword']));
            if (!empty($keyword) && strpos($lowerMsg, $keyword) !== false) {
                $botReply = $kw['response']; break;
            }
        }
    }
    echo json_encode(["status" => "success", "reply" => $botReply ?? "I'm not sure about that. Would you like to talk to a live agent?", "mode" => "bot"]);
    exit;
}

// ── ORIGINAL CHECK SESSION LOGIC ──────────────────────────────────
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';

if (preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    try {
        $decoded = JWT::decode($matches[1], new Key($secret_key, 'HS256'));
        $userId  = $decoded->uid;
        $role    = (int)($decoded->role ?? 0);

        // Admin dashboard doesn't send/store a session_token fingerprint, so it
        // can't be compared here — skip the single-device check for admins.
        if ($role === 2 || $userId === 21) {
            echo json_encode(['valid' => true]); exit;
        }

        $clientToken = $input['session_token'] ?? '';
        $stmt = $conn->prepare("SELECT session_token FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId); $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row || $row['session_token'] !== $clientToken) {
            echo json_encode(['valid' => false, 'reason' => 'another_device']); exit;
        }
        echo json_encode(['valid' => true]); exit;
    } catch (Exception $e) {
        // JWT expired or invalid — treat as idle session timeout
        echo json_encode(['valid' => false, 'reason' => 'expired']); exit;
    }
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
    echo json_encode(['valid' => false, 'reason' => 'expired']); exit;
}
$stmt = $conn->prepare("SELECT session_token FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']); $stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row || $row['session_token'] !== $_SESSION['session_token']) {
    session_destroy();
    echo json_encode(['valid' => false, 'reason' => 'another_device']); exit;
}
echo json_encode(['valid' => true]);