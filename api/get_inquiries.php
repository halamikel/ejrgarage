<?php

header('Content-Type: application/json');

session_start();

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

date_default_timezone_set('Asia/Manila');

/*
|--------------------------------------------------------------------------
| GET AUTHORIZATION HEADER
|--------------------------------------------------------------------------
*/

$headers = array_change_key_case(getallheaders(), CASE_LOWER);

$authHeader =
    $headers['authorization']
    ?? $_SERVER['HTTP_AUTHORIZATION']
    ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
    ?? '';

$user_id = 0;


/*
|--------------------------------------------------------------------------
| JWT AUTHENTICATION
|--------------------------------------------------------------------------
|
| The customer website stores the JWT in localStorage.
| We therefore read the Bearer token and get the user ID from it.
|
*/

if (preg_match('/bearer\s+(\S+)/i', $authHeader, $matches)) {

    try {

        if (!defined('JWT_SECRET')) {
            require_once __DIR__ . '/env.php';
        }

        $decoded = JWT::decode(
            $matches[1],
            new Key(JWT_SECRET, 'HS256')
        );

        $user_id = (int)($decoded->uid ?? $decoded->id ?? 0);

    } catch (Exception $e) {

        $user_id = 0;
    }
}


/*
|--------------------------------------------------------------------------
| PHP SESSION FALLBACK
|--------------------------------------------------------------------------
|
| This keeps compatibility with older pages that still use PHP sessions.
|
*/

if (!$user_id && isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
}


/*
|--------------------------------------------------------------------------
| AUTHORIZATION CHECK
|--------------------------------------------------------------------------
*/

if (!$user_id) {

    http_response_code(401);

    echo json_encode([
        'status' => 'error',
        'message' => 'User not authenticated.',
        'inquiries' => []
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| GET CUSTOMER INQUIRIES
|--------------------------------------------------------------------------
*/

try {

    $stmt = $conn->prepare("
        SELECT
            pi.id,
            pi.part_id,
            pi.user_id,
            pi.message,
            pi.status,
            pi.reply_message,
            pi.created_at,
            p.name AS part_name
        FROM part_inquiries pi
        LEFT JOIN parts p
            ON pi.part_id = p.id
        WHERE pi.user_id = ?
        ORDER BY pi.created_at DESC
    ");

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("i", $user_id);

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $result = $stmt->get_result();

    $inquiries = [];

    while ($row = $result->fetch_assoc()) {

        $inquiries[] = [
            'id' => (int)$row['id'],
            'part_id' => (int)$row['part_id'],
            'user_id' => (int)$row['user_id'],
            'part_name' => $row['part_name'] ?? 'Unknown Part',
            'message' => $row['message'] ?? '',
            'status' => $row['status'] ?? 'pending',
            'reply_message' => $row['reply_message'] ?? '',
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'inquiries' => $inquiries,
        'count' => count($inquiries)
    ]);

    $stmt->close();

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'inquiries' => []
    ]);
}

$conn->close();
?>