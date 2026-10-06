<?php

header('Content-Type: application/json');

require_once 'db_config.php';
require_once 'voucher_helper.php';

require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined("JWT_SECRET")) {
    require_once __DIR__ . "/env.php";
}

$headers = array_change_key_case(
    getallheaders(),
    CASE_LOWER
);

$authHeader = $headers['authorization'] ?? '';

if (!preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized.'
    ]);
    exit;
}

try {

    $decoded = JWT::decode(
        $matches[1],
        new Key(JWT_SECRET, 'HS256')
    );

    $user_id = (int)($decoded->uid ?? 0);

    if (!$user_id) {
        throw new Exception('Invalid user.');
    }

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    $code = trim(
        $data['code'] ?? ''
    );

    $subtotal = (float)(
        $data['subtotal'] ?? 0
    );

    ejr_ensure_voucher_tables($conn);

    /*
     * IMPORTANT:
     *
     * For validation we only use subtotal for preview.
     * place_order.php recalculates the real subtotal from TiDB.
     */

    $voucher = ejr_get_voucher(
        $conn,
        $code,
        $user_id,
        $subtotal
    );

    echo json_encode([
        'status' => 'success',
        'voucher' => [
            'id' => $voucher['id'],
            'code' => $voucher['code'],
            'discount_type' => $voucher['discount_type'],
            'discount_value' => $voucher['discount_value']
        ],
        'subtotal' => $voucher['subtotal'],
        'discount' => $voucher['discount'],
        'total' => $voucher['total']
    ]);

} catch (Throwable $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}