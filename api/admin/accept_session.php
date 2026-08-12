<?php

session_start();

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
// READ REQUEST
// ============================================================

$data = json_decode(
    file_get_contents('php://input'),
    true
) ?? [];

$user_id = isset($data['user_id'])
    ? (int)$data['user_id']
    : 0;


// ============================================================
// VALIDATE USER
// ============================================================

if (!$user_id) {

    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid user'
    ]);

    exit;
}


// ============================================================
// LIVE CHAT
// ============================================================
//
// ACCEPT / DECLINE HAS BEEN REMOVED.
//
// This endpoint now simply makes sure that the selected
// customer is in live chat mode.
//
// ============================================================

try {

    $stmt = $conn->prepare(
        "UPDATE users
         SET chat_session = 'live'
         WHERE id = ?"
    );

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();


    /*
     * Send a message informing the customer that the
     * admin is available.
     *
     * We do not use "accepted" or "declined".
     */

    $msg =
        "An EJR Garage admin is now available to chat with you. How can we help you?";


    $ins = $conn->prepare(
        "INSERT INTO ejr_chat
        (user_id, sender, message)
        VALUES (?, 'admin', ?)"
    );

    $ins->bind_param(
        "is",
        $user_id,
        $msg
    );

    $ins->execute();


    echo json_encode([
        'status' => 'success',
        'message' => 'Live chat started',
        'mode' => 'live'
    ]);


} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);

}

?>