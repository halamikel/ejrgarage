<?php
session_start();
include 'db_config.php'; 
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? null;

if ($user_id && $message) {
    try {
        $stmt = $conn->prepare("INSERT INTO ejr_chat (user_id, sender, message) VALUES (?, 'user', ?)");
        $stmt->execute([$user_id, $message]);
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in or empty message']);
}