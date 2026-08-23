<?php
session_start();

header('Content-Type: application/json');

require_once '../db_config.php';
require_once __DIR__ . '/../../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined('JWT_SECRET')) {
    require_once __DIR__ . '/../env.php';
}

// ============================================================
// AUTHORIZATION: JWT OR SESSION
// ============================================================

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role_id'] != 2 && $_SESSION['user_id'] != 21)
) {
    $checks = [
        getallheaders()['Authorization'] ?? '',
        getallheaders()['authorization'] ?? '',
        $_SERVER['HTTP_AUTHORIZATION'] ?? '',
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '',
    ];

    foreach ($checks as $h) {

        if (preg_match('/bearer\s(\S+)/i', $h, $m)) {

            try {

                $dec = JWT::decode(
                    $m[1],
                    new Key(JWT_SECRET, 'HS256')
                );

                $_SESSION['user_id'] = (int)($dec->uid ?? 0);
                $_SESSION['role_id'] = (int)($dec->role ?? 0);

            } catch (Exception $e) {
                // Invalid JWT - authorization check below will fail.
            }

            break;
        }
    }
}

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role_id'] != 2 && $_SESSION['user_id'] != 21)
) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Unauthorized.'
    ]);

    exit;
}


// ============================================================
// GET USER DATA
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['id'])
) {

    $id = (int)$_GET['id'];

    if ($id <= 0) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Invalid user ID.'
        ]);

        exit;
    }

    $stmt = $conn->prepare("
        SELECT
            id,
            full_name,
            email,
            role_id,
            is_verified,
            is_phone_verified
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Failed to prepare user query.'
        ]);

        exit;
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Failed to load user.'
        ]);

        exit;
    }

    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'User not found.'
        ]);

        exit;
    }

    // Make sure JavaScript receives numbers instead of strings.
    $user['id']                = (int)$user['id'];
    $user['role_id']           = (int)$user['role_id'];
    $user['is_verified']       = (int)$user['is_verified'];
    $user['is_phone_verified'] = (int)$user['is_phone_verified'];

    echo json_encode([
        'status' => 'success',
        'data'   => $user
    ]);

    exit;
}


// ============================================================
// POST ACTIONS
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid request method.'
    ]);

    exit;
}


// ============================================================
// READ JSON BODY
// ============================================================

$rawInput = file_get_contents('php://input');

$data = json_decode($rawInput, true);

if (!is_array($data)) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid JSON request.'
    ]);

    exit;
}

$action = $data['action'] ?? '';


// ============================================================
// UPDATE USER INFORMATION
// ============================================================

if ($action === 'update_info') {

    $user_id = (int)($data['user_id'] ?? 0);

    $full_name = trim(
        (string)($data['full_name'] ?? '')
    );

    $email = trim(
        (string)($data['email'] ?? '')
    );

    $role_id = (int)($data['role_id'] ?? 1);

    $is_verified = (int)(
        $data['is_verified'] ?? 0
    );

    $is_phone_verified = (int)(
        $data['is_phone_verified'] ?? 0
    );


    // ========================================================
    // VALIDATION
    // ========================================================

    if ($user_id <= 0) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Invalid user ID.'
        ]);

        exit;
    }

    if ($full_name === '') {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Full name is required.'
        ]);

        exit;
    }

    if ($email === '') {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Email is required.'
        ]);

        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Please enter a valid email address.'
        ]);

        exit;
    }


    // ========================================================
    // CHECK THAT USER EXISTS
    // ========================================================

    $checkUser = $conn->prepare("
        SELECT id
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    if (!$checkUser) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Failed to check user.'
        ]);

        exit;
    }

    $checkUser->bind_param(
        "i",
        $user_id
    );

    $checkUser->execute();

    $existingUser = $checkUser
        ->get_result()
        ->fetch_assoc();

    if (!$existingUser) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'User not found.'
        ]);

        exit;
    }


    // ========================================================
    // CHECK EMAIL DUPLICATE
    // ========================================================

    $emailStmt = $conn->prepare("
        SELECT id
        FROM users
        WHERE email = ?
          AND id != ?
        LIMIT 1
    ");

    if (!$emailStmt) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Failed to check email.'
        ]);

        exit;
    }

    $emailStmt->bind_param(
        "si",
        $email,
        $user_id
    );

    $emailStmt->execute();

    $emailExists = $emailStmt
        ->get_result()
        ->fetch_assoc();

    if ($emailExists) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Email already in use.'
        ]);

        exit;
    }


    // ========================================================
    // SANITIZE BOOLEAN VALUES
    // ========================================================

    $is_verified = $is_verified ? 1 : 0;

    $is_phone_verified = $is_phone_verified
        ? 1
        : 0;


    // ========================================================
    // VALIDATE ROLE
    // ========================================================

    if ($role_id <= 0) {
        $role_id = 1;
    }


    // ========================================================
    // UPDATE USERS TABLE
    //
    // IMPORTANT:
    // Do NOT touch mechanics, orders, inquiries, appointments,
    // vehicles, etc. from this endpoint.
    //
    // Those tables should reference the updated user record
    // independently.
    // ========================================================

    $stmt = $conn->prepare("
        UPDATE users
        SET
            full_name = ?,
            email = ?,
            role_id = ?,
            is_verified = ?,
            is_phone_verified = ?
        WHERE id = ?
    ");

    if (!$stmt) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Failed to prepare user update.'
        ]);

        exit;
    }

    $stmt->bind_param(
        "ssiiii",
        $full_name,
        $email,
        $role_id,
        $is_verified,
        $is_phone_verified,
        $user_id
    );


    // ========================================================
    // EXECUTE UPDATE
    // ========================================================

    if (!$stmt->execute()) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Failed to update user: ' . $stmt->error
        ]);

        exit;
    }


    // ========================================================
    // SUCCESS
    // ========================================================

    echo json_encode([
        'status'  => 'success',
        'message' => 'User updated successfully.',
        'user_id' => $user_id
    ]);

    exit;
}


// ============================================================
// DELETE USER
// ============================================================

if ($action === 'delete') {

    $user_id = (int)($data['user_id'] ?? 0);

    if ($user_id <= 0) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Missing user_id.'
        ]);

        exit;
    }


    // ========================================================
    // PREVENT ADMIN FROM DELETING THEMSELVES
    // ========================================================

    if (
        $user_id === (int)$_SESSION['user_id']
    ) {

        echo json_encode([
            'status'  => 'error',
            'message' =>
                'You cannot delete your own account from here.'
        ]);

        exit;
    }


    // ========================================================
    // ARCHIVE USER SAFELY (no hard deletes — this is a soft
    // delete: the row stays in the database, just marked as
    // archived so it disappears from the admin dashboard).
    // ========================================================

    $adminId = (int)($_SESSION['user_id'] ?? 0);

    try {

        $conn->begin_transaction();


        // ----------------------------------------------------
        // Cancel pending appointments
        // ----------------------------------------------------

        $stmt = $conn->prepare("
            UPDATE appointments
            SET status = 'Cancelled'
            WHERE user_id = ?
              AND status = 'Pending'
        ");

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $user_id
            );

            $stmt->execute();
        }


        // ----------------------------------------------------
        // Archive vehicles owned by this user
        // ----------------------------------------------------

        ejr_archive_where($conn, 'vehicles', 'user_id', $user_id, $adminId);


        // ----------------------------------------------------
        // Archive the user
        // ----------------------------------------------------

        $archived = ejr_archive_row($conn, 'users', $user_id, $adminId);

        if (!$archived) {

            $conn->rollback();

            echo json_encode([
                'status'  => 'error',
                'message' => 'User not found.'
            ]);

            exit;
        }


        $conn->commit();


        echo json_encode([
            'status'  => 'success',
            'message' => 'User archived successfully.'
        ]);

    } catch (Throwable $e) {

        $conn->rollback();

        error_log(
            "manage_user.php archive failed: " .
            $e->getMessage()
        );

        echo json_encode([
            'status'  => 'error',
            'message' => 'Failed to archive user.'
        ]);
    }

    exit;
}


// ============================================================
// INVALID ACTION
// ============================================================

echo json_encode([
    'status'  => 'error',
    'message' => 'Invalid action.'
]);

exit;
?>