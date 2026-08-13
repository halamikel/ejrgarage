<?php

header('Content-Type: application/json');

session_start();

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


// ============================================================
// AUTHENTICATION
// ============================================================

$user_id = 0;
$user_role = 0;


// ------------------------------------------------------------
// Try JWT authentication first
// ------------------------------------------------------------

$headers = array_change_key_case(getallheaders(), CASE_LOWER);

$authHeader = $headers['authorization'] ?? '';

if (preg_match('/bearer\s(\S+)/i', $authHeader, $m)) {

    try {

        if (!defined('JWT_SECRET')) {
            require_once __DIR__ . '/env.php';
        }

        $decoded = JWT::decode(
            $m[1],
            new Key(JWT_SECRET, 'HS256')
        );

        $user_id = (int)($decoded->uid ?? 0);

        /*
         * Get role directly from JWT if available.
         */
        $user_role = (int)($decoded->role ?? 0);

    } catch (Exception $e) {

        // Invalid/expired token.
        // Fall through to PHP session authentication.

    }
}


// ------------------------------------------------------------
// Fall back to PHP session
// ------------------------------------------------------------

if ($user_id === 0) {

    $user_id = (int)($_SESSION['user_id'] ?? 0);

}

if ($user_role === 0) {

    $user_role = (int)($_SESSION['role_id'] ?? 0);

}


// ============================================================
// NOT LOGGED IN
// ============================================================

if (!$user_id) {

    http_response_code(401);

    echo json_encode([
        'status'       => 'error',
        'messages'     => [],
        'chat_session' => 'bot',
        'message'      => 'Not logged in.'
    ]);

    exit;
}


// ============================================================
// GET USER ROLE FROM DATABASE
// ============================================================
//
// This is the important part.
//
// role_id = 2 means ADMIN.
//
// We get the role from the database instead of trusting
// information sent by the browser.
// ============================================================

try {

    $roleStmt = $conn->prepare(
        "SELECT role_id, chat_session
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    if (!$roleStmt) {

        throw new Exception(
            'Unable to prepare user authentication query.'
        );

    }

    $roleStmt->bind_param(
        "i",
        $user_id
    );

    $roleStmt->execute();

    $userRow = $roleStmt
        ->get_result()
        ->fetch_assoc();

    $roleStmt->close();


    if (!$userRow) {

        http_response_code(401);

        echo json_encode([
            'status'       => 'error',
            'messages'     => [],
            'chat_session' => 'bot',
            'message'      => 'User account not found.'
        ]);

        exit;
    }


    // Always trust the database role.
    $user_role = (int)($userRow['role_id'] ?? 0);

    $currentMode = $userRow['chat_session'] ?? 'bot';


    // ========================================================
    // ADMIN PROTECTION
    // ========================================================
    //
    // Admin accounts should NOT retrieve their own customer
    // chat through this endpoint.
    //
    // The admin receives customer conversations through:
    //
    //     Admin Dashboard → Live Chat
    //
    // ========================================================

    if ($user_role === 2) {

        http_response_code(403);

        echo json_encode([
            'status'       => 'error',
            'messages'     => [],
            'chat_session' => 'admin',
            'message'      => 'Administrators must use the Admin Live Chat dashboard.'
        ]);

        exit;
    }


    // ========================================================
    // GET CUSTOMER CHAT MESSAGES
    // ========================================================

    $stmt = $conn->prepare(
        "SELECT
            sender,
            message,
            created_at AS time
         FROM ejr_chat
         WHERE user_id = ?
         ORDER BY created_at ASC"
    );

    if (!$stmt) {

        throw new Exception(
            'Unable to prepare chat messages query.'
        );

    }

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();


    $messages = [];


    while ($row = $result->fetch_assoc()) {

        $messages[] = [
            'sender'  => $row['sender'],
            'message' => $row['message'],
            'time'    => date(
                'h:i A',
                strtotime($row['time'])
            )
        ];

    }


    $stmt->close();


    // ========================================================
    // RETURN CUSTOMER CHAT
    // ========================================================

    echo json_encode([
        'status'       => 'success',
        'messages'     => $messages,
        'chat_session' => $currentMode,
        'count'        => count($messages)
    ]);


} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Unable to retrieve chat messages.'
    ]);

}

?>