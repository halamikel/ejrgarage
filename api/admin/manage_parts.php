<?php
session_start();
header('Content-Type: application/json');

require_once '../db_config.php';
require_once __DIR__ . '/../../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

date_default_timezone_set('Asia/Manila');


// -- JWT Auth --
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$auth       = $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$adminId    = 0;
$isAdmin    = false;

if (preg_match('/bearer\s(\S+)/i', $auth, $m)) {

    try {

        $decoded = JWT::decode(
            $m[1],
            new Key(JWT_SECRET, 'HS256')
        );

        $adminId = (int)($decoded->uid ?? 0);
        $role    = (int)($decoded->role ?? 0);

        $isAdmin = ($role === 2 || $adminId === 21);

    } catch (Exception $e) {

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

$current_user_id  = $adminId;
$current_admin_id = $adminId;
$is_admin         = true;


// Store common variables
$current_user_id = $adminId;
$is_admin = ($role == 2 || $current_user_id == 21);


$data   = json_decode(
    file_get_contents('php://input'),
    true
);

$action = $data['action'] ?? $_GET['action'] ?? '';

ejr_ensure_archive_columns($conn, 'parts');
ejr_ensure_archive_columns($conn, 'part_inquiries');


switch ($action) {


    /* ========================================================
       GET INQUIRIES
    ======================================================== */

    case 'get_inquiries':

        // 1. Verify Admin Status
        if ($role != 2 && $adminId != 21) {

            echo json_encode([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]);

            exit;
        }


        // 2. Query
        $sql = "
    SELECT
        pi.*,
        p.name AS part_name,
        u.full_name AS customer_name,
        u.email AS customer_email,
        u.phone AS contact
    FROM part_inquiries pi
    JOIN parts p
        ON pi.part_id = p.id
    LEFT JOIN users u
        ON u.id = pi.user_id
    WHERE (pi.is_archived IS NULL OR pi.is_archived = 0)
    ORDER BY pi.created_at DESC
";

        $result = $conn->query($sql);

        if (!$result) {

            echo json_encode([
                'status' => 'error',
                'message' => $conn->error
            ]);

            exit;
        }


        $rows = [];

        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }


        echo json_encode([
            'status' => 'success',
            'inquiries' => $rows
        ]);

        break;


    /* ========================================================
       ADD PART
    ======================================================== */

    case 'add':

        $stmt = $conn->prepare("
            INSERT INTO parts
            (
                name,
                brand,
                description,
                price,
                category,
                image_url,
                stock
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssdssi",
            $data['name'],
            $data['brand'],
            $data['description'],
            $data['price'],
            $data['category'],
            $data['image_url'],
            $data['stock']
        );


        echo $stmt->execute()

            ? json_encode([
                'status' => 'success',
                'message' => 'Part added.'
            ])

            : json_encode([
                'status' => 'error',
                'message' => $conn->error
            ]);

        break;


    /* ========================================================
       EDIT PART
    ======================================================== */

    case 'edit':

        $stmt = $conn->prepare("
            UPDATE parts
            SET
                name = ?,
                brand = ?,
                description = ?,
                price = ?,
                category = ?,
                image_url = ?,
                stock = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "sssdssii",
            $data['name'],
            $data['brand'],
            $data['description'],
            $data['price'],
            $data['category'],
            $data['image_url'],
            $data['stock'],
            $data['id']
        );


        echo $stmt->execute()

            ? json_encode([
                'status' => 'success',
                'message' => 'Part updated.'
            ])

            : json_encode([
                'status' => 'error',
                'message' => $conn->error
            ]);

        break;


    /* ========================================================
       DELETE PART
    ======================================================== */

    case 'delete':

        $partId = (int)($data['id'] ?? 0);

        // Archive the part instead of deleting it, and archive its
        // inquiries along with it (matches the admin UI's warning
        // that deleting a part also removes its inquiries).
        $archived = ejr_archive_row($conn, 'parts', $partId, $adminId);
        ejr_archive_where($conn, 'part_inquiries', 'part_id', $partId, $adminId);

        echo $archived

            ? json_encode([
                'status' => 'success',
                'message' => 'Part archived.'
            ])

            : json_encode([
                'status' => 'error',
                'message' => 'Part not found or already archived.'
            ]);

        break;


    /* ========================================================
       UPDATE INQUIRY
    ======================================================== */

    case 'update_inquiry':

        if (!isset($data['id'], $data['status'])) {

            echo json_encode([
                'status' => 'error',
                'message' => 'Missing inquiry data'
            ]);

            exit;
        }


        // Handle optional reply message
        $reply = $data['reply_message'] ?? null;


        $stmt = $conn->prepare("
            UPDATE part_inquiries
            SET
                status = ?,
                reply_message = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ssi",
            $data['status'],
            $reply,
            $data['id']
        );


        if ($stmt->execute()) {

            echo json_encode([
                'status' => 'success',
                'message' => 'Response sent to customer.'
            ]);

        } else {

            echo json_encode([
                'status' => 'error',
                'message' => $conn->error
            ]);
        }

        break;


    /* ========================================================
       DELETE INQUIRY
    ======================================================== */

    case 'delete_inquiry':

        $inquiryId = (int)($data['id'] ?? 0);

        echo ejr_archive_row($conn, 'part_inquiries', $inquiryId, $adminId)

            ? json_encode([
                'status' => 'success',
                'message' => 'Inquiry archived.'
            ])

            : json_encode([
                'status' => 'error',
                'message' => 'Inquiry not found or already archived.'
            ]);

        break;


    /* ========================================================
       DEFAULT — GET ALL PARTS
    ======================================================== */

    default:

        $result = $conn->query("
            SELECT *
            FROM parts
            WHERE (is_archived IS NULL OR is_archived = 0)
            ORDER BY created_at DESC
        ");

        $rows = [];

        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }


        echo json_encode([
            'status' => 'success',
            'parts' => $rows
        ]);

        break;
}

?>