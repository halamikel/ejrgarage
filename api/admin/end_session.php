<?php

session_start();

include '../db_config.php';

header('Content-Type: application/json');


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
// GET USER
// ============================================================

$data = json_decode(
    file_get_contents('php://input'),
    true
) ?? [];

$user_id = isset($data['user_id'])
    ? (int)$data['user_id']
    : 0;


if (!$user_id) {

    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid user'
    ]);

    exit;
}


// ============================================================
// END LIVE CHAT
// ============================================================

try {

    /*
     * We ONLY return the customer to bot mode.
     *
     * Chat history is NOT deleted.
     */

    $upd = $conn->prepare(
        "UPDATE users
         SET chat_session = 'bot'
         WHERE id = ?"
    );

    $upd->bind_param(
        "i",
        $user_id
    );

    $upd->execute();


    echo json_encode([
        'status' => 'success',
        'message' => 'Live chat ended'
    ]);


} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);

}

?>