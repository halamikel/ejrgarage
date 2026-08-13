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
// CHECK AUTHENTICATION
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
// VERIFY ADMIN ROLE
// ============================================================
//
// role_id = 2 means ADMIN.
//
// No hardcoded admin ID is used.
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
// GET CUSTOMER ID
// ============================================================

$data = json_decode(
    file_get_contents('php://input'),
    true
);


$customerId = isset($data['user_id'])
    ? (int)$data['user_id']
    : (int)($_GET['user_id'] ?? 0);


if ($customerId <= 0) {

    http_response_code(400);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Customer ID is required.'
    ]);

    exit;
}


// ============================================================
// VERIFY CUSTOMER ACCOUNT
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
    $customerId
);

$customerStmt->execute();

$customerRow = $customerStmt
    ->get_result()
    ->fetch_assoc();

$customerStmt->close();


if (!$customerRow) {

    http_response_code(404);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Customer account not found.'
    ]);

    exit;
}


$customerRole = (int)(
    $customerRow['role_id'] ?? 0
);


// ============================================================
// DO NOT END ANOTHER ADMIN'S SESSION
// ============================================================

if ($customerRole === 2) {

    http_response_code(403);

    echo json_encode([
        'status'  => 'error',
        'message' => 'This account is an administrator.'
    ]);

    exit;
}


// ============================================================
// END LIVE CHAT SESSION
// ============================================================

try {

    $stmt = $conn->prepare(
        "UPDATE users
         SET chat_session = 'bot'
         WHERE id = ?"
    );


    if (!$stmt) {

        throw new Exception(
            'Unable to prepare session update.'
        );

    }


    $stmt->bind_param(
        "i",
        $customerId
    );


    if (!$stmt->execute()) {

        throw new Exception(
            'Unable to end live chat session.'
        );

    }


    $stmt->close();


    // ========================================================
    // RETURN SUCCESS
    // ========================================================

    echo json_encode([

        'status' => 'success',

        'message' => 'Live chat session ended.',

        'user_id' => $customerId,

        'chat_session' => 'bot'

    ]);


} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([

        'status' => 'error',

        'message' => 'Unable to end live chat session.'

    ]);

}

?>