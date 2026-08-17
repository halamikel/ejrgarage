<?php

header("Content-Type: application/json");

include 'db_config.php';

require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';

require '../includes/PHPMailer/Exception.php';
require '../includes/PHPMailer/PHPMailer.php';
require '../includes/PHPMailer/SMTP.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;


// ── JWT AUTH ─────────────────────────────────────────────────

if (!defined("JWT_SECRET")) {
    require_once __DIR__ . "/env.php";
}

$secret_key = JWT_SECRET;

$headers =
    array_change_key_case(
        getallheaders(),
        CASE_LOWER
    );

$authHeader =
    $headers['authorization'] ?? '';


if (
    !preg_match(
        '/bearer\s(\S+)/i',
        $authHeader,
        $matches
    )
) {

    http_response_code(401);

    echo json_encode([
        'status' =>
            'error',

        'message' =>
            'Token missing'
    ]);

    exit;
}


try {

    $decoded =
        JWT::decode(
            $matches[1],
            new Key(
                $secret_key,
                'HS256'
            )
        );


    $userId =
        $decoded->uid ??
        $decoded->id;

}

catch (\Exception $e) {

    http_response_code(401);

    echo json_encode([
        'status' =>
            'error',

        'message' =>
            'Unauthorized'
    ]);

    exit;
}


// ── GET USER INFO ─────────────────────────────────────────────

$userStmt =
    $conn->prepare(
        "SELECT
            email,
            full_name,
            notif_email
         FROM users
         WHERE id = ?
         LIMIT 1"
    );


$userStmt->bind_param(
    "i",
    $userId
);


$userStmt->execute();


$user =
    $userStmt
        ->get_result()
        ->fetch_assoc();


if (!$user) {

    echo json_encode([
        'status' =>
            'error',

        'message' =>
            'User not found.'
    ]);

    exit;
}


// ── PARSE INPUT ───────────────────────────────────────────────

$data =
    json_decode(
        file_get_contents(
            "php://input"
        ),
        true
    );


if (!$data) {

    echo json_encode([
        "status" =>
            "error",

        "message" =>
            "No data received"
    ]);

    exit;
}


try {

    $conn->begin_transaction();


    // ── BASIC DATA ────────────────────────────────────────────

    $v =
        $data['vehicle'];


    $services =
        is_array(
            $data['services']
        )
        ? implode(
            ", ",
            $data['services']
        )
        : $data['services'];


    // ==========================================================
    // OPTIONAL MECHANIC
    // ==========================================================
    //
    // mechanic_id can be:
    //
    // NULL  = customer did not choose a mechanic
    // ID    = customer selected a mechanic
    //
    // The mechanic is NOT required.
    // ==========================================================

    $mechanicId = null;


    if (
        isset(
            $data['mechanic_id']
        )
        &&
        $data['mechanic_id'] !== null
        &&
        $data['mechanic_id'] !== ''
    ) {

        $mechanicId =
            (int)$data['mechanic_id'];

    }


    // ==========================================================
    // CHECK SELECTED MECHANIC
    // ==========================================================

    $selectedMechanic = null;


    if (
        $mechanicId !== null
    ) {

        $mechanicStmt =
            $conn->prepare(
                "SELECT
                    id,
                    name,
                    specialty,
                    status
                 FROM mechanics
                 WHERE id = ?
                 LIMIT 1"
            );


        $mechanicStmt->bind_param(
            "i",
            $mechanicId
        );


        $mechanicStmt->execute();


        $selectedMechanic =
            $mechanicStmt
                ->get_result()
                ->fetch_assoc();


        $mechanicStmt->close();


        // ======================================================
        // MECHANIC NO LONGER EXISTS
        // ======================================================

        if (
            !$selectedMechanic
        ) {

            throw new Exception(
                "The selected mechanic is no longer available. Please choose another mechanic."
            );

        }


        // ======================================================
        // MECHANIC IS NO LONGER AVAILABLE
        // ======================================================

        if (
            $selectedMechanic['status']
            !==
            'available'
        ) {

            throw new Exception(
                $selectedMechanic['name'] .
                " is no longer available. Please choose another mechanic."
            );

        }


        // ======================================================
        // CHECK MECHANIC'S EXISTING BOOKINGS
        // ======================================================
        //
        // This prevents the same mechanic from being booked
        // by multiple customers for the same appointment date.
        //
        // Cancelled and Completed appointments do not count.
        // ======================================================

        $busyStmt =
            $conn->prepare(
                "SELECT
                    COUNT(*) AS total
                 FROM appointments
                 WHERE mechanic_id = ?
                   AND appointment_date = ?
                   AND status NOT IN (
                       'Cancelled',
                       'Completed'
                   )"
            );


        $busyStmt->bind_param(
            "is",
            $mechanicId,
            $v['appointmentDate']
        );


        $busyStmt->execute();


        $busyResult =
            $busyStmt
                ->get_result();


        $busyRow =
            $busyResult
                ->fetch_assoc();


        $busyStmt->close();


        if (
            (int)$busyRow['total'] > 0
        ) {

            throw new Exception(
                $selectedMechanic['name'] .
                " already has an appointment on this date. Please choose another mechanic."
            );

        }

    }


    // ==========================================================
    // CHECK / INSERT VEHICLE
    // ==========================================================

    $checkStmt =
        $conn->prepare(
            "SELECT
                id
             FROM vehicles
             WHERE plate_number = ?
             LIMIT 1"
        );


    $checkStmt->bind_param(
        "s",
        $v['plate']
    );


    $checkStmt->execute();


    $result =
        $checkStmt->get_result();


    if (
        $row =
            $result->fetch_assoc()
    ) {

        $vehicleId =
            $row['id'];

    }

    else {

        $stmtVeh =
            $conn->prepare(
                "INSERT INTO vehicles
                (
                    user_id,
                    brand,
                    model,
                    year_model,
                    plate_number,
                    transmission,
                    fuel_type
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )"
            );


        $stmtVeh->bind_param(
            "issssss",
            $userId,
            $v['brand'],
            $v['model'],
            $v['year'],
            $v['plate'],
            $v['transmission'],
            $v['fuel']
        );


        if (
            !$stmtVeh->execute()
        ) {

            throw new Exception(
                "Vehicle Save Failed: " .
                $stmtVeh->error
            );

        }


        $vehicleId =
            $conn->insert_id;

    }


    // ==========================================================
    // SAVE APPOINTMENT
    // ==========================================================

    $stmtApp =
        $conn->prepare(
            "INSERT INTO appointments
            (
                user_id,
                vehicle_id,
                service_type,
                appointment_date,
                status,
                mechanic_id
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                'Pending',
                ?
            )"
        );


    $stmtApp->bind_param(
        "iissi",
        $userId,
        $vehicleId,
        $services,
        $v['appointmentDate'],
        $mechanicId
    );


    if (
        !$stmtApp->execute()
    ) {

        throw new Exception(
            "Appointment Save Failed: " .
            $stmtApp->error
        );

    }


    // ==========================================================
    // COMMIT
    // ==========================================================

    $conn->commit();


    /**
     * BUGFIX:
     *
     * Send the success response BEFORE attempting SMTP.
     *
     * This preserves the existing behavior of your project and
     * prevents a slow email server from making the frontend think
     * the booking failed even though it was already saved.
     */

    $responseBody =
        json_encode([
            "status" =>
                "success",

            "message" =>
                "Booking Saved!"
        ]);


    if (
        function_exists(
            'fastcgi_finish_request'
        )
    ) {

        echo $responseBody;

        fastcgi_finish_request();

    }

    else {

        ob_start();

        echo $responseBody;

        header(
            'Content-Length: ' .
            ob_get_length()
        );

        header(
            'Connection: close'
        );

        ob_end_flush();

        @ob_flush();

        flush();

    }


    // ==========================================================
    // SEND EMAIL
    // ==========================================================

    if (
        $user['notif_email'] == 1
    ) {

        $formattedDate =
            date(
                "F j, Y",
                strtotime(
                    $v['appointmentDate']
                )
            );


        $serviceList =
            implode(
                "</li><li style='margin-bottom:4px;'>",
                $data['services']
            );


        // ======================================================
        // MECHANIC EMAIL TEXT
        // ======================================================

        if (
            $selectedMechanic
        ) {

            $mechanicText =
                htmlspecialchars(
                    $selectedMechanic['name']
                );

        }

        else {

            $mechanicText =
                "To be assigned by EJR Garage";

        }


        $mail =
            new PHPMailer(true);


        try {

            $mail->isSMTP();

            $mail->Host =
                SMTP_HOST;

            $mail->SMTPAuth =
                true;

            $mail->Username =
                SMTP_USER;

            $mail->Password =
                SMTP_PASS;

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_SMTPS;

            $mail->Port =
                465;

            $mail->Timeout =
                15;


            $mail->setFrom(
                SMTP_USER,
                'EJR Garage'
            );


            $mail->addAddress(
                $user['email'],
                $user['full_name']
            );


            $mail->isHTML(true);


            $mail->Subject =
                'EJR Garage - Appointment Booked!';


            $mail->Body = '

            <div
                style="
                    background:#f4f6f8;
                    padding:30px;
                    font-family:Arial,sans-serif;
                "
            >

                <div
                    style="
                        background:#fff;
                        border-radius:12px;
                        max-width:520px;
                        margin:auto;
                        padding:30px;
                        border:1px solid #e3e8ef;
                    "
                >

                    <div
                        style="
                            text-align:center;
                            margin-bottom:24px;
                        "
                    >

                        <h2
                            style="
                                color:#e62429;
                                margin:0;
                            "
                        >
                            EJR Garage
                        </h2>

                        <p
                            style="
                                color:#5f6b7a;
                                margin:4px 0 0;
                            "
                        >
                            Appointment Confirmation
                        </p>

                    </div>


                    <p
                        style="
                            color:#1e2a36;
                        "
                    >

                        Hi
                        <strong>
                            ' .
                            htmlspecialchars(
                                $user['full_name']
                            ) .
                        '
                        </strong>,

                    </p>


                    <p
                        style="
                            color:#5f6b7a;
                        "
                    >

                        Your appointment has been
                        successfully booked and is
                        currently
                        <strong>
                            Pending
                        </strong>
                        approval.

                        We will notify you once it
                        is confirmed.

                    </p>


                    <div
                        style="
                            background:#f8f9fa;
                            border-radius:8px;
                            padding:20px;
                            margin:20px 0;
                        "
                    >

                        <h4
                            style="
                                color:#1e2a36;
                                margin:0 0 12px;
                            "
                        >
                            Appointment Details
                        </h4>


                        <table
                            style="
                                width:100%;
                                border-collapse:collapse;
                                font-size:14px;
                            "
                        >

                            <tr>

                                <td
                                    style="
                                        color:#5f6b7a;
                                        padding:6px 0;
                                        width:40%;
                                    "
                                >
                                    Date
                                </td>

                                <td
                                    style="
                                        color:#1e2a36;
                                        font-weight:bold;
                                    "
                                >
                                    ' .
                                    $formattedDate .
                                    '
                                </td>

                            </tr>


                            <tr>

                                <td
                                    style="
                                        color:#5f6b7a;
                                        padding:6px 0;
                                    "
                                >
                                    Vehicle
                                </td>

                                <td
                                    style="
                                        color:#1e2a36;
                                        font-weight:bold;
                                    "
                                >
                                    ' .
                                    htmlspecialchars(
                                        $v['brand'] .
                                        ' ' .
                                        $v['model'] .
                                        ' (' .
                                        $v['year'] .
                                        ')'
                                    ) .
                                    '
                                </td>

                            </tr>


                            <tr>

                                <td
                                    style="
                                        color:#5f6b7a;
                                        padding:6px 0;
                                    "
                                >
                                    Plate No.
                                </td>

                                <td
                                    style="
                                        color:#1e2a36;
                                        font-weight:bold;
                                    "
                                >
                                    ' .
                                    htmlspecialchars(
                                        $v['plate']
                                    ) .
                                    '
                                </td>

                            </tr>


                            <tr>

                                <td
                                    style="
                                        color:#5f6b7a;
                                        padding:6px 0;
                                    "
                                >
                                    Mechanic
                                </td>

                                <td
                                    style="
                                        color:#1e2a36;
                                        font-weight:bold;
                                    "
                                >
                                    ' .
                                    $mechanicText .
                                    '
                                </td>

                            </tr>


                            <tr>

                                <td
                                    style="
                                        color:#5f6b7a;
                                        padding:6px 0;
                                        vertical-align:top;
                                    "
                                >
                                    Services
                                </td>

                                <td
                                    style="
                                        color:#1e2a36;
                                        font-weight:bold;
                                    "
                                >

                                    <ul
                                        style="
                                            margin:0;
                                            padding-left:16px;
                                        "
                                    >

                                        <li
                                            style="
                                                margin-bottom:4px;
                                            "
                                        >
                                            ' .
                                            $serviceList .
                                            '
                                        </li>

                                    </ul>

                                </td>

                            </tr>


                            <tr>

                                <td
                                    style="
                                        color:#5f6b7a;
                                        padding:6px 0;
                                    "
                                >
                                    Status
                                </td>

                                <td>

                                    <span
                                        style="
                                            background:#fff3cd;
                                            color:#856404;
                                            padding:2px 10px;
                                            border-radius:20px;
                                            font-size:13px;
                                            font-weight:bold;
                                        "
                                    >
                                        Pending
                                    </span>

                                </td>

                            </tr>

                        </table>

                    </div>


                    <div
                        style="
                            text-align:center;
                            margin-top:24px;
                        "
                    >

                        <a
                            href="https://ejrgarage.onrender.com/my_appointments.php"
                            style="
                                background:#e62429;
                                color:#fff;
                                padding:12px 28px;
                                border-radius:8px;
                                text-decoration:none;
                                font-weight:bold;
                            "
                        >
                            View My Appointments
                        </a>

                    </div>


                    <p
                        style="
                            color:#a0aab5;
                            font-size:11px;
                            text-align:center;
                            margin-top:24px;
                        "
                    >
                        EJR Garage &mdash;
                        Santo Tomas, Batangas
                    </p>

                </div>

            </div>';



            $mail->send();

        }

        catch (MailException $e) {

            error_log(
                "EJR Garage MAIL ERROR " .
                "(booking confirmation to " .
                $user['email'] .
                "): " .
                $mail->ErrorInfo
            );

        }

    }

}

catch (Exception $e) {

    $conn->rollback();


    http_response_code(500);


    echo json_encode([
        "status" =>
            "error",

        "message" =>
            $e->getMessage()
    ]);

}

?>