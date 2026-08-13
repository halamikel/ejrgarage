<?php

session_start();
session_write_close();

header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

require_once __DIR__ . '/../../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


// ============================================================
// ADMIN AUTHENTICATION
// ============================================================

$headers = array_change_key_case(
    getallheaders(),
    CASE_LOWER
);

$auth = $headers['authorization']
    ?? $_SERVER['HTTP_AUTHORIZATION']
    ?? '';

$adminId = 0;


// ============================================================
// GET ADMIN ID FROM JWT
// ============================================================

if (preg_match('/bearer\s(\S+)/i', $auth, $m)) {

    try {

        $decoded = JWT::decode(
            $m[1],
            new Key(JWT_SECRET, 'HS256')
        );

        $adminId = (int)($decoded->uid ?? 0);

    } catch (Exception $e) {

        $adminId = 0;
    }
}


// ============================================================
// FALL BACK TO PHP SESSION
// ============================================================

if ($adminId === 0) {

    $adminId = (int)(
        $_SESSION['user_id'] ?? 0
    );
}


// ============================================================
// NO AUTHENTICATION
// ============================================================

if ($adminId <= 0) {

    http_response_code(401);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Authentication required.'
    ]);

    exit;
}


// ============================================================
// VERIFY ADMIN ROLE FROM DATABASE
// ============================================================
//
// role_id = 2 = ADMIN
//
// We deliberately do NOT use a hardcoded admin ID.
// ============================================================

$roleStmt = $conn->prepare(
    "SELECT role_id
     FROM users
     WHERE id = ?
     LIMIT 1"
);

if (!$roleStmt) {

    http_response_code(500);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Unable to verify administrator account.'
    ]);

    exit;
}


$roleStmt->bind_param(
    "i",
    $adminId
);

$roleStmt->execute();

$roleRow = $roleStmt
    ->get_result()
    ->fetch_assoc();

$roleStmt->close();


if (!$roleRow) {

    http_response_code(403);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Administrator account not found.'
    ]);

    exit;
}


$adminRole = (int)(
    $roleRow['role_id'] ?? 0
);


// ============================================================
// ADMIN-ONLY ACCESS
// ============================================================

if ($adminRole !== 2) {

    http_response_code(403);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Admin access required.'
    ]);

    exit;
}


// ============================================================
// GET SELECTED CUSTOMER
// ============================================================

$user_id = (int)(
    $_GET['user_id'] ?? 0
);


if ($user_id <= 0) {

    echo json_encode([
        'status'       => 'error',
        'messages'     => [],
        'chat_session' => 'bot'
    ]);

    exit;
}


// ============================================================
// VERIFY SELECTED ACCOUNT IS A CUSTOMER
// ============================================================
//
// An admin should only open customer conversations.
// Another admin should not be treated as a customer.
// ============================================================

$customerStmt = $conn->prepare(
    "SELECT
        id,
        role_id,
        chat_session
     FROM users
     WHERE id = ?
     LIMIT 1"
);

if (!$customerStmt) {

    http_response_code(500);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Unable to verify customer account.'
    ]);

    exit;
}


$customerStmt->bind_param(
    "i",
    $user_id
);

$customerStmt->execute();

$customerRow = $customerStmt
    ->get_result()
    ->fetch_assoc();

$customerStmt->close();


if (!$customerRow) {

    echo json_encode([
        'status'       => 'error',
        'messages'     => [],
        'chat_session' => 'bot',
        'message'      => 'Customer account not found.'
    ]);

    exit;
}


$customerRole = (int)(
    $customerRow['role_id'] ?? 0
);


/*
 * Do not allow an admin to open another admin's
 * conversation through the customer chat viewer.
 */

if ($customerRole === 2) {

    http_response_code(403);

    echo json_encode([
        'status'       => 'error',
        'messages'     => [],
        'chat_session' => 'admin',
        'message'      => 'This account is an administrator.'
    ]);

    exit;
}


$currentMode = $customerRow['chat_session']
    ?? 'bot';


// ============================================================
// GET CUSTOMER MESSAGES
// ============================================================

try {

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

            'sender' => $row['sender'],

            'message' => $row['message'],

            'time' => date(
                'h:i A',
                strtotime($row['time'])
            )

        ];

    }


    $stmt->close();


    // ========================================================
    // MARK CUSTOMER MESSAGES AS READ
    // ========================================================
    //
    // Only messages sent by the CUSTOMER are marked as read.
    //
    // Admin replies remain untouched.
    // ========================================================

    $markRead = $conn->prepare(
        "UPDATE ejr_chat
         SET is_read = 1
         WHERE user_id = ?
           AND sender = 'user'"
    );


    if ($markRead) {

        $markRead->bind_param(
            "i",
            $user_id
        );

        $markRead->execute();

        $markRead->close();
    }


    // ========================================================
    // RETURN MESSAGES TO ADMIN DASHBOARD
    // ========================================================

    echo json_encode([

        'status' => 'success',

        'messages' => $messages,

        'chat_session' => $currentMode,

        'count' => count($messages)

    ]);


} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([

        'status' => 'error',

        'message' => 'Unable to retrieve customer messages.'

    ]);

}

?>