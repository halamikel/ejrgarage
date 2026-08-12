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
$isAdmin = false;


if (
    preg_match(
        '/bearer\s(\S+)/i',
        $auth,
        $m
    )
) {

    try {

        $decoded = JWT::decode(
            $m[1],
            new Key(JWT_SECRET, 'HS256')
        );

        $adminId = (int)($decoded->uid ?? 0);

        $role = (int)($decoded->role ?? 0);

        $isAdmin =
            ($role === 2 || $adminId === 21);

    } catch (Exception $e) {

        $isAdmin = false;

    }
}


if (!$isAdmin) {

    http_response_code(403);

    echo json_encode([
        'status' => 'error',
        'message' => 'Admin access required.'
    ]);

    exit;
}


// ============================================================
// GET CUSTOMER ID
// ============================================================

$user_id = (int)(
    $_GET['user_id'] ?? 0
);


if (!$user_id) {

    echo json_encode([
        'status' => 'error',
        'messages' => []
    ]);

    exit;
}


// ============================================================
// GET CHAT
// ============================================================

try {

    // --------------------------------------------------------
    // Get current chat mode
    // --------------------------------------------------------

    $sessStmt = $conn->prepare(
        "SELECT chat_session
         FROM users
         WHERE id = ?"
    );

    $sessStmt->bind_param(
        "i",
        $user_id
    );

    $sessStmt->execute();

    $sessRow =
        $sessStmt
            ->get_result()
            ->fetch_assoc();


    $currentMode =
        $sessRow['chat_session']
        ?? 'bot';


    // --------------------------------------------------------
    // Get messages
    // --------------------------------------------------------

    $stmt = $conn->prepare(
        "SELECT
            sender,
            message,
            created_at AS time
         FROM ejr_chat
         WHERE user_id = ?
         ORDER BY created_at ASC"
    );

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result =
        $stmt->get_result();


    $messages = [];


    while (
        $row =
        $result->fetch_assoc()
    ) {

        $messages[] = [

            'sender' =>
                $row['sender'],

            'message' =>
                $row['message'],

            'time' =>
                date(
                    'h:i A',
                    strtotime(
                        $row['time']
                    )
                )

        ];

    }


    // --------------------------------------------------------
    // Mark customer messages as read
    // --------------------------------------------------------

    $markRead = $conn->prepare(
        "UPDATE ejr_chat
         SET is_read = 1
         WHERE user_id = ?
         AND sender = 'user'"
    );

    $markRead->bind_param(
        "i",
        $user_id
    );

    $markRead->execute();


    // --------------------------------------------------------
    // Return messages
    // --------------------------------------------------------

    echo json_encode([

        'status' =>
            'success',

        'messages' =>
            $messages,

        'chat_session' =>
            $currentMode,

        'count' =>
            count($messages)

    ]);


} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([

        'status' =>
            'error',

        'message' =>
            $e->getMessage()

    ]);

}

?>