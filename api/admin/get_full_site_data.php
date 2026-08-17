<?php
/**
 * ============================================================
 * EJR GARAGE
 * ADMIN - GET FULL SITE DATA
 *
 * File:
 * api/admin/get_full_site_data.php
 *
 * Provides:
 *  - Users
 *  - Appointments
 *  - Vehicles
 *  - Mechanics
 *  - Services
 *
 * Used by:
 *  - Admin Dashboard
 *  - Appointment Management
 *  - Customer Management
 *  - Vehicle Management
 *  - Mechanic Management
 * ============================================================
 */

session_start();

date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json; charset=utf-8');

require_once '../db_config.php';

require_once __DIR__ . '/../../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


/* ============================================================
   HELPER: JSON RESPONSE
   ============================================================ */

function ejr_json_response(
    string $status,
    string $message = '',
    array $data = [],
    int $httpCode = 200
): void {

    http_response_code($httpCode);

    $response = [
        'status' => $status
    ];

    if ($message !== '') {
        $response['message'] = $message;
    }

    if (!empty($data)) {
        $response = array_merge(
            $response,
            $data
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
   GET JWT TOKEN
   ============================================================ */

function ejr_get_token(): string
{
    $headers = [];

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    }

    $headers = array_change_key_case(
        $headers,
        CASE_LOWER
    );


    $checks = [

        $headers['authorization'] ?? '',

        $_SERVER['HTTP_AUTHORIZATION'] ?? '',

        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''
    ];


    foreach ($checks as $header) {

        if (
            preg_match(
                '/bearer\s+(\S+)/i',
                $header,
                $matches
            )
        ) {

            return trim(
                $matches[1]
            );
        }
    }


    return '';
}


/* ============================================================
   ADMIN AUTHENTICATION
   ============================================================ */

function ejr_admin_authorized(): bool
{
    global $headers;

    /*
     * --------------------------------------------------------
     * JWT AUTH
     * --------------------------------------------------------
     */

    $token = ejr_get_token();


    if ($token !== '') {

        try {

            $decoded = JWT::decode(
                $token,
                new Key(
                    JWT_SECRET,
                    'HS256'
                )
            );


            $role = (int)(
                $decoded->role ?? 0
            );


            $uid = (int)(
                $decoded->uid ?? 0
            );


            /*
             * role 2 = administrator
             *
             * user ID 21 is also treated as admin
             * to maintain compatibility with the existing
             * EJR Garage authentication system.
             */

            if (
                $role === 2 ||
                $uid === 21
            ) {

                return true;
            }

        } catch (Exception $e) {

            /*
             * Continue to session authentication below.
             */
        }
    }


    /*
     * --------------------------------------------------------
     * PHP SESSION AUTH
     * --------------------------------------------------------
     */

    if (
        isset($_SESSION['user_id']) &&
        isset($_SESSION['role_id'])
    ) {

        $sessionUserId =
            (int)$_SESSION['user_id'];

        $sessionRole =
            (int)$_SESSION['role_id'];


        if (
            $sessionRole === 2 ||
            $sessionUserId === 21
        ) {

            return true;
        }
    }


    return false;
}


/* ============================================================
   ADMIN ACCESS CHECK
   ============================================================ */

if (!ejr_admin_authorized()) {

    ejr_json_response(
        'error',
        'Unauthorized',
        [],
        401
    );
}


session_write_close();


/* ============================================================
   DATABASE CHECK
   ============================================================ */

if (!isset($conn) || !$conn) {

    ejr_json_response(
        'error',
        'Database connection failed.',
        [],
        500
    );
}


/* ============================================================
   SELF-HEALING DATABASE SCHEMA
   ============================================================ */

/*
 * ------------------------------------------------------------
 * MECHANICS TABLE
 * ------------------------------------------------------------
 */

$conn->query("
    CREATE TABLE IF NOT EXISTS mechanics (

        id
            INT
            AUTO_INCREMENT
            PRIMARY KEY,

        name
            VARCHAR(100)
            NOT NULL,

        specialty
            VARCHAR(150)
            DEFAULT '',

        status
            ENUM(
                'available',
                'unavailable'
            )
            DEFAULT 'available',

        created_at
            TIMESTAMP
            DEFAULT CURRENT_TIMESTAMP

    )

    ENGINE=InnoDB
    DEFAULT CHARSET=utf8mb4
");


/*
 * ------------------------------------------------------------
 * APPOINTMENTS.mechanic_id
 * ------------------------------------------------------------
 */

$mechanicColumnCheck =
    $conn->query("
        SHOW COLUMNS
        FROM appointments
        LIKE 'mechanic_id'
    ");


if (
    $mechanicColumnCheck &&
    $mechanicColumnCheck->num_rows === 0
) {

    $conn->query("
        ALTER TABLE appointments
        ADD COLUMN mechanic_id INT NULL
    ");
}


/*
 * ------------------------------------------------------------
 * SERVICES TABLE
 * ------------------------------------------------------------
 */

$serviceTableCheck =
    $conn->query("
        SHOW TABLES
        LIKE 'ejr_services'
    ");


$serviceTableWasNew =
    (
        $serviceTableCheck &&
        $serviceTableCheck->num_rows === 0
    );


$conn->query("
    CREATE TABLE IF NOT EXISTS ejr_services (

        id
            INT
            AUTO_INCREMENT
            PRIMARY KEY,

        name
            VARCHAR(150)
            NOT NULL,

        category
            VARCHAR(100)
            DEFAULT 'General',

        description
            TEXT NULL,

        price_from
            DECIMAL(10,2)
            DEFAULT 0.00,

        is_active
            TINYINT(1)
            DEFAULT 1,

        created_at
            TIMESTAMP
            DEFAULT CURRENT_TIMESTAMP

    )

    ENGINE=InnoDB
    DEFAULT CHARSET=utf8mb4
");


/*
 * ------------------------------------------------------------
 * SEED SERVICES IF TABLE WAS JUST CREATED
 * ------------------------------------------------------------
 */

if ($serviceTableWasNew) {

    $seedServices = [

        [
            'PMS',
            'Popular Services'
        ],

        [
            'Change Oil',
            'Popular Services'
        ],

        [
            'Engine Tune Up',
            'Popular Services'
        ],

        [
            'Auto Electrical Transmission',
            'Popular Services'
        ],

        [
            'EFI',
            'Popular Services'
        ],

        [
            'Carburator Services',
            'Popular Services'
        ],

        [
            'Decarbonizer',
            'Popular Services'
        ],

        [
            'Scanning Fault Codes',
            'Popular Services'
        ],

        [
            'Paint Services',
            'Popular Services'
        ],

        [
            'Back to Zero (Anti-bacterial fogging)',
            'Popular Services'
        ],

        [
            'Car Checkup',
            'Other Services'
        ],

        [
            'Scheduled Maintenance',
            'Other Services'
        ],

        [
            'Tow/Roadside Assistance',
            'Other Services'
        ]

    ];


    $seedStmt =
        $conn->prepare("
            INSERT INTO
                ejr_services
                (
                    name,
                    category
                )
            VALUES
                (?, ?)
        ");


    if ($seedStmt) {

        foreach (
            $seedServices as $service
        ) {

            $serviceName =
                $service[0];

            $serviceCategory =
                $service[1];


            $seedStmt->bind_param(
                "ss",
                $serviceName,
                $serviceCategory
            );


            $seedStmt->execute();
        }


        $seedStmt->close();
    }
}


/* ============================================================
   INITIAL RESPONSE DATA
   ============================================================ */

$data = [

    'users' => [],

    'appointments' => [],

    'vehicles' => [],

    'mechanics' => [],

    'services' => []

];


/* ============================================================
   1. USERS
   ============================================================ */

$userResult = $conn->query("
    SELECT

        id,

        full_name,

        email,

        phone,

        role_id,

        created_at

    FROM users

    ORDER BY id ASC
");


if ($userResult) {

    while (
        $row =
        $userResult->fetch_assoc()
    ) {

        $data['users'][] =
            $row;
    }
}


/* ============================================================
   2. APPOINTMENTS
   ============================================================ */

/*
 * We explicitly select the fields instead of using a.*
 * so the dashboard receives predictable data.
 *
 * Important fields:
 *
 * id
 * user_id
 * vehicle_id
 * service_type
 * appointment_date
 * status
 * mechanic_id
 * customer name
 * vehicle information
 * mechanic name
 */

$appResult = $conn->query("
    SELECT

        a.id,

        a.user_id,

        a.vehicle_id,

        a.service_type,

        a.appointment_date,

        a.status,

        a.mechanic_id,

        u.full_name,

        u.email AS customer_email,

        u.phone AS customer_phone,

        v.brand,

        v.model,

        v.plate_number,

        CONCAT(
            COALESCE(v.brand, ''),
            ' ',
            COALESCE(v.model, ''),
            ' (',
            COALESCE(v.plate_number, 'N/A'),
            ')'
        ) AS vehicle_info,

        m.name AS mechanic_name,

        m.specialty AS mechanic_specialty,

        m.status AS mechanic_status

    FROM appointments a

    LEFT JOIN users u
        ON a.user_id = u.id

    LEFT JOIN vehicles v
        ON a.vehicle_id = v.id

    LEFT JOIN mechanics m
        ON a.mechanic_id = m.id

    ORDER BY
        a.appointment_date DESC
");


if ($appResult) {

    while (
        $row =
        $appResult->fetch_assoc()
    ) {

        /*
         * Provide safe defaults for the frontend.
         */

        if (
            !isset($row['vehicle_info']) ||
            trim($row['vehicle_info']) === ''
        ) {

            $row['vehicle_info'] =
                'No vehicle assigned';
        }


        if (
            !isset($row['mechanic_name']) ||
            trim((string)$row['mechanic_name']) === ''
        ) {

            $row['mechanic_name'] =
                'Unassigned';
        }


        if (
            !isset($row['status']) ||
            trim((string)$row['status']) === ''
        ) {

            $row['status'] =
                'Pending';
        }


        $data['appointments'][] =
            $row;
    }
}


/* ============================================================
   3. VEHICLES
   ============================================================ */

$vehResult = $conn->query("
    SELECT

        v.*,

        u.full_name AS owner_name,

        u.email AS owner_email,

        u.phone AS owner_phone

    FROM vehicles v

    LEFT JOIN users u
        ON v.user_id = u.id

    ORDER BY v.id DESC
");


if ($vehResult) {

    while (
        $row =
        $vehResult->fetch_assoc()
    ) {

        if (
            !isset($row['owner_name']) ||
            trim((string)$row['owner_name']) === ''
        ) {

            $row['owner_name'] =
                'Unknown Owner';
        }


        $data['vehicles'][] =
            $row;
    }
}


/* ============================================================
   4. MECHANICS
   ============================================================ */

$mechanicResult = $conn->query("
    SELECT

        id,

        name,

        specialty,

        status,

        created_at

    FROM mechanics

    ORDER BY name ASC
");


if ($mechanicResult) {

    while (
        $row =
        $mechanicResult->fetch_assoc()
    ) {

        $data['mechanics'][] =
            $row;
    }
}


/* ============================================================
   5. SERVICES
   ============================================================ */

$serviceResult = $conn->query("
    SELECT

        id,

        name,

        category,

        description,

        price_from,

        is_active,

        created_at

    FROM ejr_services

    ORDER BY

        category ASC,

        name ASC
");


if ($serviceResult) {

    while (
        $row =
        $serviceResult->fetch_assoc()
    ) {

        /*
         * Convert numeric values into useful JSON types.
         */

        if (isset($row['id'])) {

            $row['id'] =
                (int)$row['id'];
        }


        if (isset($row['price_from'])) {

            $row['price_from'] =
                (float)$row['price_from'];
        }


        if (isset($row['is_active'])) {

            $row['is_active'] =
                (int)$row['is_active'];
        }


        $data['services'][] =
            $row;
    }
}


/* ============================================================
   DASHBOARD COUNTS
   ============================================================ */

$counts = [

    'users' =>
        count($data['users']),

    'appointments' =>
        count($data['appointments']),

    'vehicles' =>
        count($data['vehicles']),

    'mechanics' =>
        count($data['mechanics']),

    'services' =>
        count($data['services'])

];


/* ============================================================
   APPOINTMENT STATUS COUNTS
   ============================================================ */

$statusCounts = [

    'Pending' => 0,

    'Confirmed' => 0,

    'In Progress' => 0,

    'Completed' => 0,

    'Cancelled' => 0,

    'No Show' => 0

];


foreach (
    $data['appointments']
    as $appointment
) {

    $appointmentStatus =
        trim(
            (string)(
                $appointment['status']
                ?? ''
            )
        );


    if (
        isset(
            $statusCounts[
                $appointmentStatus
            ]
        )
    ) {

        $statusCounts[
            $appointmentStatus
        ]++;
    }
}


/* ============================================================
   FINAL RESPONSE
   ============================================================ */

echo json_encode(

    [

        'status' => 'success',

        'data' => $data,

        /*
         * Keep the original top-level arrays too.
         *
         * This makes the endpoint compatible with older
         * versions of admin_dashboard.js that expect:
         *
         * response.users
         * response.appointments
         * response.vehicles
         */

        'users' =>
            $data['users'],

        'appointments' =>
            $data['appointments'],

        'vehicles' =>
            $data['vehicles'],

        'mechanics' =>
            $data['mechanics'],

        'services' =>
            $data['services'],

        'counts' =>
            $counts,

        'appointment_status_counts' =>
            $statusCounts

    ],

    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES

);

?>