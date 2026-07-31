<?php
session_start();
header('Content-Type: application/json');
require_once 'db_config.php'; // Ensure path is correct relative to this file
date_default_timezone_set('Asia/Manila');

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // 2. Fetch inquiries only for this specific user
    // We JOIN with the 'parts' table to get the part name
    $stmt = $conn->prepare("
        SELECT 
            pi.id, 
            pi.message, 
            pi.status, 
            pi.reply_message, 
            pi.created_at, 
            p.name AS part_name 
        FROM part_inquiries pi
        LEFT JOIN parts p ON pi.part_id = p.id
        WHERE pi.user_id = ?
        ORDER BY pi.created_at DESC
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $inquiries = [];
    while ($row = $result->fetch_assoc()) {
        $inquiries[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'inquiries' => $inquiries
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$stmt->close();
$conn->close();
?>