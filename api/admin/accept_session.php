<?php
session_start();
include '../db_config.php';
header('Content-Type: application/json');


// -- JWT Auth --
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$auth       = $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$adminId    = 0;
$isAdmin    = false;
if (preg_match('/bearer\\s(\\S+)/i', $auth, $m)) {
    try {
        $decoded = JWT::decode($m[1], new Key(JWT_SECRET, 'HS256'));
        $adminId = (int)($decoded->uid  ?? 0);
        $role    = (int)($decoded->role ?? 0);
        $isAdmin = ($role === 2 || $adminId === 21);
    } catch (Exception $e) {}
}
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Admin access required.']);
    exit;
}
$current_user_id  = $adminId;
$current_admin_id = $adminId;
$is_admin         = true;

$data    = json_decode(file_get_contents('php://input'), true);
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
$action  = $data['action'] ?? ''; // 'accept' or 'decline'

if (!$user_id || !in_array($action, ['accept', 'decline'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

try {
    if ($action === 'accept') {
        // Set user to live mode
        $stmt = $conn->prepare("UPDATE users SET chat_session = 'live' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Notify user that admin accepted
        $msg  = "An admin has joined the chat. How can we help you?";
        $ins  = $conn->prepare("INSERT INTO ejr_chat (user_id, sender, message) VALUES (?, 'admin', ?)");
        $ins->bind_param("is", $user_id, $msg);
        $ins->execute();

        echo json_encode(['status' => 'success', 'message' => 'Session accepted']);

    } elseif ($action === 'decline') {
        // Reset back to bot mode
        $stmt = $conn->prepare("UPDATE users SET chat_session = 'bot' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Notify user that request was declined
        $msg  = "Sorry, no agents are available right now. Our bot will continue to assist you.";
        $ins  = $conn->prepare("INSERT INTO ejr_chat (user_id, sender, message) VALUES (?, 'admin', ?)");
        $ins->bind_param("is", $user_id, $msg);
        $ins->execute();

        echo json_encode(['status' => 'success', 'message' => 'Session declined']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>