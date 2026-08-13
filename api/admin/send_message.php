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
// AUTHENTICATION CHECK
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
// role_id = 2 means ADMIN.
//
// Do NOT use a hardcoded user ID such as 21.
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
// READ REQUEST DATA
// ============================================================

$data = json_decode(
    file_get_contents('php://input'),
    true
);


$target_user_id = isset($data['user_id'])
    ? (int)$data['user_id']
    : 0;

$message = trim(
    $data['message'] ?? ''
);


// ============================================================
// VALIDATE REQUEST
// ============================================================

if (
    $target_user_id <= 0 ||
    $message === ''
) {

    http_response_code(400);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid data.'
    ]);

    exit;
}


// ============================================================
// VERIFY TARGET IS A CUSTOMER
// ============================================================
//
// Admins should only reply to customer accounts.
//
// If the target account also has role_id = 2,
// reject the message.
// ============================================================

$customerStmt = $conn->prepare(
    "SELECT role_id
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
    $target_user_id
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


if ($customerRole === 2) {

    http_response_code(403);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Messages can only be sent to customer accounts.'
    ]);

    exit;
}


// ============================================================
// SEND ADMIN MESSAGE
// ============================================================
//
// sender = 'admin'
//
// The message is stored under the customer's user_id so the
// customer can retrieve the response through their chat.
//
// is_read = 1 because the admin has already sent/read it.
// ============================================================

try {

    $stmt = $conn->prepare(
        "INSERT INTO ejr_chat
            (user_id, sender, message, is_read)
         VALUES
            (?, 'admin', ?, 1)"
    );


    if (!$stmt) {

        throw new Exception(
            'Unable to prepare admin message.'
        );

    }


    $stmt->bind_param(
        "is",
        $target_user_id,
        $message
    );


    if ($stmt->execute()) {

        echo json_encode([
            'status' => 'success'
        ]);

    } else {

        http_response_code(500);

        echo json_encode([
            'status'  => 'error',
            'message' => 'Unable to send message.'
        ]);
    }


    $stmt->close();


} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Unable to send message.'
    ]);

}

?>