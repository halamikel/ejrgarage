<?php

header('Content-Type: application/json');

$root = __DIR__;

require_once $root . '/api/db_config.php';

date_default_timezone_set('Asia/Manila');


// ============================================================
// GET PARAMETERS
// ============================================================
//
// Your current hosting setup uses GET parameters because
// POST requests are blocked on the server.
//

$userId   = (int)($_GET['uid'] ?? 0);
$action   = $_GET['action'] ?? 'chat';
$user_msg = trim($_GET['message'] ?? '');


// ============================================================
// BASIC USER CHECK
// ============================================================

if ($userId === 0) {

    echo json_encode([
        "status" => "error",
        "reply"  => "Please log in to use the chat.",
        "mode"   => "error"
    ]);

    exit;
}


// ============================================================
// VERIFY USER + ROLE FROM DATABASE
// ============================================================
//
// IMPORTANT:
//
// role_id = 2 means ADMIN.
//
// We check the database instead of trusting the value sent
// by the browser.
//

$userStmt = $conn->prepare(
    "SELECT role_id, chat_session
     FROM users
     WHERE id = ?
     LIMIT 1"
);

if (!$userStmt) {

    echo json_encode([
        "status" => "error",
        "reply"  => "Unable to verify your account.",
        "mode"   => "error"
    ]);

    exit;
}

$userStmt->bind_param("i", $userId);

$userStmt->execute();

$userResult = $userStmt->get_result()->fetch_assoc();

$userStmt->close();


if (!$userResult) {

    echo json_encode([
        "status" => "error",
        "reply"  => "User account not found.",
        "mode"   => "error"
    ]);

    exit;
}


$userRole = (int)($userResult['role_id'] ?? 0);

$currentChatSession =
    $userResult['chat_session'] ?? 'bot';


// ============================================================
// ADMIN PROTECTION
// ============================================================
//
// Admin accounts must NEVER use the customer-side chat API.
//
// Admins receive customer messages through:
//
// Admin Dashboard → Live Chat
//
// role_id = 2 = ADMIN
// ============================================================

if ($userRole === 2) {

    echo json_encode([
        "status" => "error",
        "reply"  => "Administrators must use the Admin Live Chat dashboard.",
        "mode"   => "admin"
    ]);

    exit;
}


// ============================================================
// REQUEST LIVE AGENT
// ============================================================

if ($action === 'request_live') {

    $stmt = $conn->prepare(
        "UPDATE users
         SET chat_session = 'live'
         WHERE id = ?"
    );

    if (!$stmt) {

        echo json_encode([
            "status" => "error",
            "reply"  => "Unable to start live chat.",
            "mode"   => "error"
        ]);

        exit;
    }

    $stmt->bind_param(
        "i",
        $userId
    );

    $stmt->execute();

    $stmt->close();


    echo json_encode([
        "status" => "success",
        "reply"  => "You are now connected to a live agent. How can we help you?",
        "mode"   => "live"
    ]);

    exit;
}


// ============================================================
// CANCEL LIVE CHAT REQUEST
// ============================================================

if ($action === 'cancel_live_request') {

    $stmt = $conn->prepare(
        "UPDATE users
         SET chat_session = 'bot'
         WHERE id = ?"
    );

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $userId
        );

        $stmt->execute();

        $stmt->close();
    }


    /*
     * Remove the automatically-created live-chat notification
     * if your existing system created one.
     */

    $stmt = $conn->prepare(
        "DELETE FROM ejr_chat
         WHERE user_id = ?
         AND sender = 'admin'
         AND message = 'A user has requested live assistance.'"
    );

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $userId
        );

        $stmt->execute();

        $stmt->close();
    }


    echo json_encode([
        'status' => 'success'
    ]);

    exit;
}


// ============================================================
// MESSAGE VALIDATION
// ============================================================

if (empty($user_msg)) {

    echo json_encode([
        "status" => "error",
        "reply"  => "No message received.",
        "mode"   => "bot"
    ]);

    exit;
}


// ============================================================
// CHECK CURRENT CHAT SESSION
// ============================================================

$isLive = ($currentChatSession === 'live');


// ============================================================
// LIVE CHAT MESSAGE
// ============================================================
//
// When the customer is in live-chat mode:
//
// Customer
//    ↓
// ejr_chat
//    ↓
// Admin Dashboard
//
// The bot does NOT answer the message.
//

if ($isLive) {

    $stmtUser = $conn->prepare(
        "INSERT INTO ejr_chat
            (user_id, sender, message)
         VALUES
            (?, 'user', ?)"
    );

    if (!$stmtUser) {

        echo json_encode([
            "status" => "error",
            "reply"  => "Unable to send your message.",
            "mode"   => "live"
        ]);

        exit;
    }


    $stmtUser->bind_param(
        "is",
        $userId,
        $user_msg
    );

    $stmtUser->execute();

    $stmtUser->close();


    echo json_encode([
        "status" => "success",
        "reply"  => "",
        "mode"   => "live"
    ]);

    exit;
}


// ============================================================
// CUSTOMER IS STILL USING BOT
// ============================================================

$botReply = null;


try {

    $allKeywords = $conn->query(
        "SELECT keyword, response
         FROM ejr_bot"
    );


    if ($allKeywords) {

        $lowerMsg = strtolower($user_msg);


        while ($kw = $allKeywords->fetch_assoc()) {

            $keyword = strtolower(
                trim($kw['keyword'])
            );


            if (
                !empty($keyword) &&
                strpos($lowerMsg, $keyword) !== false
            ) {

                $botReply = $kw['response'];

                break;
            }
        }
    }

} catch (Exception $e) {

    // Keep the default response below.
}


// ============================================================
// BOT RESPONSE
// ============================================================

$reply = $botReply
    ?? "I'm not sure about that. Would you like to talk to a live agent?";


echo json_encode([
    "status" => "success",
    "reply"  => $reply,
    "mode"   => "bot"
]);

?>