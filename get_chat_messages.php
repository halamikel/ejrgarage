<?php
header('Content-Type: application/json');

$root = __DIR__;
require_once $root . '/api/db_config.php';

$user_id = (int)($_GET['uid'] ?? 0);

if (!$user_id) {
    echo json_encode(['status' => 'error', 'messages' => [], 'chat_session' => 'bot']);
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