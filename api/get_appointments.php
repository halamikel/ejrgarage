<?php
/**
 * ============================================================
 * EJR GARAGE
 * GET USER APPOINTMENTS API
 *
 * File:
 * api/get_appointments.php
 *
 * Purpose:
 *  - Get appointments belonging to the logged-in user
 *  - Authenticate using JWT
 *  - Include vehicle information
 *  - Include assigned mechanic information
 * ============================================================
 */

header('Content-Type: application/json; charset=utf-8');

require_once 'db_config.php';

require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


/* ============================================================
   JWT SECRET
   ============================================================ */

if (!defined('JWT_SECRET')) {

    require_once __DIR__ . '/env.php';
}


$secretKey = JWT_SECRET;


/* ============================================================
   HELPER: JSON RESPONSE
   ============================================================ */

function jsonResponse(
    string $status,
    string $message = '',
    array $extra = [],
    int $httpCode = 200
): void {

    http_response_code($httpCode);

    $response = [
        'status' => $status
    ];


    if ($message !== '') {

        $response['message'] =
            $message;
    }


    if (!empty($extra)) {

        $response =
            array_merge(
                $response,
                $extra
            );
    }


    echo json_encode(
        $response,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* ============================================================
   GET AUTHORIZATION HEADER
   ============================================================ */

$headers = [];


if (function_exists('getallheaders')) {

    $headers =
        getallheaders();
}


$headers =
    array_change_key_case(
        $headers,
        CASE_LOWER
    );


$authHeader =
    $headers['authorization']
    ?? $_SERVER['HTTP_AUTHORIZATION']
    ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
    ?? '';


/* ============================================================
   CHECK BEARER TOKEN
   ============================================================ */

if (
    !preg_match(
        '/bearer\s+(\S+)/i',
        $authHeader,
        $matches
    )
) {

    jsonResponse(
        'error',
        'Token missing.',
        [],
        401
    );
}


$token =
    trim($matches[1]);


/* ============================================================
   DECODE JWT
   ============================================================ */

try {

    $decoded =
        JWT::decode(
            $token,
            new Key(
                $secretKey,
                'HS256'
            )
        );

} catch (Exception $e) {

    jsonResponse(
        'error',
        'Invalid or expired token.',
        [],
        401
    );
}


/* ============================================================
   GET USER ID
   ============================================================ */

$userId =
    (int)($decoded->uid ?? 0);


if ($userId <= 0) {

    jsonResponse(
        'error',
        'Invalid user information in token.',
        [],
        401
    );
}


/* ============================================================
   VERIFY DATABASE CONNECTION
   ============================================================ */

if (!isset($conn) || !$conn) {

    jsonResponse(
        'error',
        'Database connection failed.',
        [],
        500
    );
}


/* ============================================================
   GET USER APPOINTMENTS
   ============================================================ */

$query = "

    SELECT

        a.*,

        /* ----------------------------------------------------
           Vehicle information
        ---------------------------------------------------- */

        v.brand,

        v.model,

        v.plate_number,

        CONCAT_WS(
            ' ',
            v.brand,
            v.model,

            CASE

                WHEN
                    v.plate_number IS NOT NULL
                    AND v.plate_number <> ''

                THEN
                    CONCAT(
                        '(',
                        v.plate_number,
                        ')'
                    )

                ELSE
                    NULL

            END

        ) AS vehicle_info,


        /* ----------------------------------------------------
           Mechanic information
        ---------------------------------------------------- */

        m.name AS mechanic_name,

        m.specialty AS mechanic_specialty,

        m.status AS mechanic_status


    FROM appointments a


    LEFT JOIN vehicles v

        ON a.vehicle_id = v.id


    LEFT JOIN mechanics m

        ON a.mechanic_id = m.id


    WHERE a.user_id = ?


    ORDER BY
        a.appointment_date DESC

";


$stmt =
    $conn->prepare($query);


if (!$stmt) {

    jsonResponse(
        'error',
        'Failed to prepare appointment query.',
        [],
        500
    );
}


/* ============================================================
   BIND USER ID
   ============================================================ */

$stmt->bind_param(
    'i',
    $userId
);


/* ============================================================
   EXECUTE QUERY
   ============================================================ */

if (!$stmt->execute()) {

    $stmt->close();

    jsonResponse(
        'error',
        'Failed to retrieve appointments.',
        [],
        500
    );
}


/* ============================================================
   GET RESULTS
   ============================================================ */

$result =
    $stmt->get_result();


$appointments = [];


/* ============================================================
   BUILD APPOINTMENT ARRAY
   ============================================================ */

while (
    $row =
    $result->fetch_assoc()
) {

    /*
     * --------------------------------------------------------
     * Vehicle fallback
     * --------------------------------------------------------
     */

    if (
        !isset($row['vehicle_info']) ||
        trim(
            (string)$row['vehicle_info']
        ) === ''
    ) {

        $row['vehicle_info'] =
            'No vehicle assigned';
    }


    /*
     * --------------------------------------------------------
     * Mechanic fallback
     * --------------------------------------------------------
     */

    if (
        !isset($row['mechanic_name']) ||
        trim(
            (string)$row['mechanic_name']
        ) === ''
    ) {

        $row['mechanic_name'] =
            'Not yet assigned';
    }


    /*
     * --------------------------------------------------------
     * Status fallback
     * --------------------------------------------------------
     */

    if (
        !isset($row['status']) ||
        trim(
            (string)$row['status']
        ) === ''
    ) {

        $row['status'] =
            'Pending';
    }


    $appointments[] =
        $row;
}


$stmt->close();


/* ============================================================
   RESPONSE
   ============================================================ */

jsonResponse(
    'success',
    '',
    [
        'appointments' =>
            $appointments,

        'count' =>
            count($appointments)
    ]
);

?>