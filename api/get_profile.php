<?php
header('Content-Type: application/json');
require_once 'db_config.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined("JWT_SECRET")) require_once __DIR__ . "/env.php";
$secret_key = JWT_SECRET;

// Get headers and extract Bearer token
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';

if (preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    try {
        // Decode the token - matching your UID structure
        $decoded = JWT::decode($matches[1], new Key($secret_key, 'HS256'));
        
        // Handle both 'uid' or 'user_id' based on your payload structure
        $userId = $decoded->uid ?? $decoded->user_id ?? 0;

        if (!$userId) {
            echo json_encode(["status" => "error", "message" => "Invalid token payload"]);
            exit;
        }

        // Fetch user data
        // Note: we select 'full_name' and 'phone' to match your JS prefillUser() function
        $stmt = $conn->prepare("SELECT id, full_name, email, phone, address, role_id, notif_email, notif_sms, profile_picture, created_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Same is_available lookup as login.php — keeps the mechanic
            // app's availability toggle accurate after a splash-screen
            // auto-login / profile refresh, not just a fresh login.
            $user['is_available']    = 0;
            $user['mechanic_status'] = 'unavailable';
            if ((int)$user['role_id'] === 3) {
                try {
                    $mStmt = $conn->prepare("SELECT status FROM mechanics WHERE user_id = ? LIMIT 1");
                    $mStmt->bind_param("i", $userId);
                    $mStmt->execute();
                    $mRow = $mStmt->get_result()->fetch_assoc();
                    if ($mRow) {
                        $user['mechanic_status'] = $mRow['status'];
                        if ($mRow['status'] !== 'unavailable') {
                            $user['is_available'] = 1;
                        }
                    }
                } catch (\Throwable $e) {
                    // mechanics table/user_id column not created yet.
                }
            }

            echo json_encode([
                "status" => "success",
                "user" => $user
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "User not found"]);
        }

    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized: " . $e->getMessage()]);
    }
} else {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "No token provided"]);
}