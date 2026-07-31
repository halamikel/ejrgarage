<?php
header('Content-Type: application/json');

$root = __DIR__;
require_once $root . '/api/db_config.php';

$userId = (int)($_GET['uid'] ?? 0);

if ($userId === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET chat_session = 'bot' WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();

$del = $conn->prepare("DELETE FROM ejr_chat WHERE user_id = ?");
$del->bind_param("i", $userId);

if ($del->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to clear chat history']);
}
?>