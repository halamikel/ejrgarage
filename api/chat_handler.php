<?php
ob_start();
session_start();

header('Content-Type: application/json');

// ── CORS: reflect exact origin (required when credentials:'include' is used) ──
$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['https://ejrgarage.onrender.com', 'http://localhost'];
if (in_array($origin, $allowed)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: https://ejrgarage.onrender.com');
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ── Use __DIR__ for reliable paths on shared/InfinityFree hosting ──
$root = dirname(__DIR__); // api/ -> root
require_once $root . '/api/db_config.php';
require_once $root . '/includes/php-jwt/JWT.php';
require_once $root . '/includes/php-jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
date_default_timezone_set('Asia/Manila');

$data     = json_decode(file_get_contents('php://input'), true);
$user_msg = trim($data['message'] ?? '');
$action   = $data['action']  ?? 'chat';

// ── AUTH: session (web) OR JWT Bearer (mobile app) ────────────
// The web frontend relies on PHP sessions since it never sends an
// Authorization header (to route around InfinityFree's WAF). The
// Flutter app has no cookie jar and always sends a JWT, so it's
// checked here as a fallback when no session is present.
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId === 0) {
    $headers    = array_change_key_case(getallheaders(), CASE_LOWER);
    $authHeader = $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/bearer\s(\S+)/i', $authHeader, $m)) {
        try {
            if (!defined('JWT_SECRET')) require_once $root . '/api/env.php';
            $decoded = JWT::decode($m[1], new Key(JWT_SECRET, 'HS256'));
            $userId  = (int)($decoded->uid ?? 0);
        } catch (Exception $e) {}
    }
}

if ($userId === 0) {
    echo json_encode([
        "status" => "error",
        "reply"  => "Please log in to use the chat.",
        "mode"   => "error"
    ]);
    exit;
}

// ── ADMIN GUARD ───────────────────────────────────────────────
// role_id = 2 means ADMIN.
//
// Admin accounts use the Live Chat inbox in the admin dashboard.
// They must NOT use the customer-side chat API.

$userRole = 0;

/*
 * First try the PHP session.
 */
if (isset($_SESSION['role_id'])) {
    $userRole = (int)$_SESSION['role_id'];
}

/*
 * If the session does not contain the role, get it directly
 * from the database using the authenticated user ID.
 */
if ($userRole === 0 && $userId > 0) {

    $roleStmt = $conn->prepare(
        "SELECT role_id FROM users WHERE id = ? LIMIT 1"
    );

    if ($roleStmt) {

        $roleStmt->bind_param("i", $userId);
        $roleStmt->execute();

        $roleResult = $roleStmt->get_result()->fetch_assoc();

        if ($roleResult) {
            $userRole = (int)($roleResult['role_id'] ?? 0);
        }

        $roleStmt->close();
    }
}

/*
 * Admins are not allowed to use customer chat.
 */
if ($userRole === 2) {

    echo json_encode([
        "status" => "error",
        "reply"  => "Administrators must use the Admin Live Chat dashboard.",
        "mode"   => "admin"
    ]);

    exit;
}

// ── 1. LIVE CHAT REQUEST ──────────────────────────────────────
if ($action === 'request_live') {
    // Clear any leftover messages from a previous, already-ended live
    // session first — otherwise the old back-and-forth (and the admin's
    // view of it) reappears every time the user starts a new one.
    $clear = $conn->prepare("DELETE FROM ejr_chat WHERE user_id = ?");
    $clear->bind_param("i", $userId);
    $clear->execute();

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