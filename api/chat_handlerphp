<?php
session_start();
header('Content-Type: application/json');
require_once 'db_config.php';
date_default_timezone_set('Asia/Manila');

$data     = json_decode(file_get_contents('php://input'), true);
$user_msg = trim($data['message'] ?? '');
$action   = $data['action'] ?? 'chat';
$userId   = (int)($_SESSION['user_id'] ?? 0);

// ── BLOCK GUESTS ─────────────────────────────────────────────
if ($userId === 0) {
    echo json_encode([
        "status" => "error",
        "reply"  => "Please log in to use the chat.",
        "mode"   => "error"
    ]);
    exit;
}

// Everything below touches the database. Wrap it all so that any DB
// problem (bad column, dropped connection, etc.) always comes back as
// clean JSON instead of a raw PHP fatal error that breaks the widget.
try {

    // ── 1. LIVE CHAT REQUEST ─────────────────────────────────────
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
    $isLive = ($sessResult['chat_session'] ?? 'bot') === 'live';

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

    $reply = $botReply ?? "I'm not sure about that. Would you like to talk to a live agent?";

    // ── 6. Bot replies are not logged — admin only sees live conversations ──
    echo json_encode(["status" => "success", "reply" => $reply, "mode" => "bot"]);

} catch (Throwable $e) {
    error_log("chat_handler.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "reply"  => "Sorry, something went wrong on our end. Please try again in a moment.",
        "mode"   => "error"
    ]);
}
