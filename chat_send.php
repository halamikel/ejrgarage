<?php
header('Content-Type: application/json');

$root = __DIR__;
require_once $root . '/api/db_config.php';
date_default_timezone_set('Asia/Manila');

// Everything via GET params — InfinityFree blocks POST requests
$userId   = (int)($_GET['uid']     ?? 0);
$action   = $_GET['action']        ?? 'chat';
$user_msg = trim($_GET['message']  ?? '');

if ($userId === 0) {
    echo json_encode(["status" => "error", "reply" => "Please log in to use the chat.", "mode" => "error"]);
    exit;
}

if ($action === 'request_live') {
    $stmt = $conn->prepare("UPDATE users SET chat_session = 'live' WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    echo json_encode(["status" => "success", "reply" => "You are now connected to a live agent. How can we help you?", "mode" => "live"]);
    exit;
}

if ($action === 'cancel_live_request') {
    $stmt = $conn->prepare("UPDATE users SET chat_session = 'bot' WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt = $conn->prepare("DELETE FROM ejr_chat WHERE user_id = ? AND sender = 'admin' AND message = 'A user has requested live assistance.'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    echo json_encode(['status' => 'success']);
    exit;
}

if (empty($user_msg)) {
    echo json_encode(["status" => "error", "reply" => "No message received.", "mode" => "bot"]);
    exit;
}

$sessCheck = $conn->prepare("SELECT chat_session FROM users WHERE id = ?");
$sessCheck->bind_param("i", $userId);
$sessCheck->execute();
$sessResult = $sessCheck->get_result()->fetch_assoc();
$isLive     = ($sessResult['chat_session'] ?? 'bot') === 'live';

if ($isLive) {
    $stmtUser = $conn->prepare("INSERT INTO ejr_chat (user_id, sender, message) VALUES (?, 'user', ?)");
    $stmtUser->bind_param("is", $userId, $user_msg);
    $stmtUser->execute();
    echo json_encode(["status" => "success", "reply" => "", "mode" => "live"]);
    exit;
}

$botReply = null;
try {
    $allKeywords = $conn->query("SELECT keyword, response FROM ejr_bot");
    if ($allKeywords) {
        $lowerMsg = strtolower($user_msg);
        while ($kw = $allKeywords->fetch_assoc()) {
            $keyword = strtolower(trim($kw['keyword']));
            if (!empty($keyword) && strpos($lowerMsg, $keyword) !== false) {
                $botReply = $kw['response'];
                break;
            }
        }
    }
} catch (Exception $e) {}

$reply = $botReply ?? "I'm not sure about that. Would you like to talk to a live agent?";
echo json_encode(["status" => "success", "reply" => $reply, "mode" => "bot"]);
?>