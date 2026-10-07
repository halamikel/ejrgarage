<?php
header('Content-Type: application/json');
require_once 'db_config.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';
require_once '../includes/leave_sync.php';

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
        // Credit any points earned but never awarded (self-heals past silent failures).
        require_once __DIR__ . '/points_helper.php';
        ejr_sync_user_points($conn, (int)$userId);

        // SELECT * so a missing optional column (e.g. points) can't 500 the whole profile.
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Only expose the fields the app needs (never password hashes etc.),
            // defaulting any column that doesn't exist yet.
            $user = [];
            foreach (['id','full_name','email','phone','address','role_id','notif_email','notif_sms','profile_picture','created_at','points'] as $f) {
                $user[$f] = $row[$f] ?? ($f === 'points' ? 0 : null);
            }
            $user['points'] = (int)$user['points'];
            if (!array_key_exists('points', $row)) {
                error_log('EJR get_profile: users.points column is missing - run the rewards migration.');
            }
            // Same is_available lookup as login.php — keeps the mechanic
            // app's availability toggle accurate after a splash-screen
            // auto-login / profile refresh, not just a fresh login.
            $user['is_available']    = 0;
            $user['mechanic_status'] = 'unavailable';
            if ((int)$user['role_id'] === 3) {
                try {
                    // Re-derive on-leave status from approved
                    // leave_requests date ranges before reading it.
                    ejr_sync_leave_statuses($conn);

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
        // A database error is a server problem, not a bad token. Returning 401
        // here makes the app think the session expired and log the user out.
        if ($e instanceof mysqli_sql_exception) {
            error_log('EJR get_profile DB error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Server error loading profile."]);
            exit;
        }
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized: " . $e->getMessage()]);
    }
} else {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "No token provided"]);
}