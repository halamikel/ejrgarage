<?php
ob_start();
session_start();

header('Content-Type: application/json');

// ── CORS: reflect exact origin (required when credentials:'include' is used) ──
$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['https://ejrgarage.site.je', 'http://ejrgarage.site.je'];
if (in_array($origin, $allowed)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: https://ejrgarage.site.je');
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ── Use __DIR__ for reliable paths on shared/InfinityFree hosting ──
$root = dirname(__DIR__); // api/ -> root
require_once $root . '/api/db_config.php';
date_default_timezone_set('Asia/Manila');

$data     = json_decode(file_get_contents('php://input'), true);
$user_msg = trim($data['message'] ?? '');
$action   = $data['action']  ?? 'chat';

// ── AUTH: Session only (no Authorization header — avoids InfinityFree WAF) ──
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId === 0) {
    echo json_encode([
        "status" => "error",
        "reply"  => "Please log in to use the chat.",
        "mode"   => "error"
    ]);
    exit;
}

// ── 1. LIVE CHAT REQUEST ──────────────────────────────────────
if ($action === 'request_live') {
    $stmt = $conn->prepare("UPDATE users SET chat_session = 'waiting' WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $sysMsg = "A user has requested live assistance.";
    $stmt   = $conn->prepare("INSERT INTO ejr_chat (user_id, sender, message) VALUES (?, 'admin', ?)");
    $stmt->bind_param("is", $userId, $sysMsg);
    $stmt->execute();
    echo json_encode(["status" => "success", "reply" => "Connecting to admin...", "mode" => "waiting"]);
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

// ── 2. GUARD: must have a message ────────────────────────────
if (empty($user_msg)) {
    echo json_encode(["status" => "error", "reply" => "No message received.", "mode" => "bot"]);
    exit;
}

// ── 3. CHECK IF ADMIN IS LIVE ─────────────────────────────────
$sessCheck = $conn->prepare("SELECT chat_session FROM users WHERE id = ?");
$sessCheck->bind_param("i", $userId);
$sessCheck->execute();
$sessResult = $sessCheck->get_result()->fetch_assoc();
$isLive     = ($sessResult['chat_session'] ?? 'bot') === 'live';

// ── 4. LOG USER MESSAGE (only if live) ───────────────────────
if ($isLive) {
    $stmtUser = $conn->prepare("INSERT INTO ejr_chat (user_id, sender, message) VALUES (?, 'user', ?)");
    $stmtUser->bind_param("is", $userId, $user_msg);
    $stmtUser->execute();
    echo json_encode(["status" => "success", "reply" => "", "mode" => "live"]);
    exit;
}

// ── 5. BOT KEYWORD MATCHING ──────────────────────────────────
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
} catch (Exception $e) {
    error_log("Bot error: " . $e->getMessage());
}

$reply = $botReply ?? "I'm not sure about that. Would you like to talk to a live agent?";
echo json_encode(["status" => "success", "reply" => $reply, "mode" => "bot"]);