<?php
session_start();
header("Content-Type: application/json");
require_once 'db_config.php';

$userId = $_SESSION['pending_uid'] ?? 0;

if ($userId > 0) {
    $stmt = $conn->prepare("SELECT is_verified FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && $user['is_verified'] == 1) {
        $_SESSION['user_id'] = $userId;
        unset($_SESSION['pending_uid']);
        echo json_encode(["status" => "verified"]);
    } else {
        echo json_encode(["status" => "waiting"]);
    }
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "No session found"]);
}
$conn->close();