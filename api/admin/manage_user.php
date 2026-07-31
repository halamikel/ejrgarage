<?php
session_start();
header('Content-Type: application/json');
require_once '../db_config.php';
require_once __DIR__ . '/../../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/php-jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined('JWT_SECRET')) require_once __DIR__ . '/../env.php';

// ── AUTHORIZATION: JWT or session ─────────────────────────────
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 2 && $_SESSION['user_id'] != 21)) {
    $checks = [
        getallheaders()['Authorization'] ?? '',
        getallheaders()['authorization'] ?? '',
        $_SERVER['HTTP_AUTHORIZATION'] ?? '',
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '',
    ];
    foreach ($checks as $h) {
        if (preg_match('/bearer\s(\S+)/i', $h, $m)) {
            try {
                $dec = JWT::decode($m[1], new Key(JWT_SECRET, 'HS256'));
                $_SESSION['user_id'] = (int)($dec->uid  ?? 0);
                $_SESSION['role_id'] = (int)($dec->role ?? 0);
            } catch (Exception $e) {}
            break;
        }
    }
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 2 && $_SESSION['user_id'] != 21)) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

// ── GET HANDLER (FETCH DATA) ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT id, full_name, email, role_id, is_verified, is_phone_verified FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        // Ensure values are integers for correct JS checkbox handling
        $user['is_verified'] = (int)$user['is_verified'];
        $user['is_phone_verified'] = (int)$user['is_phone_verified'];
        echo json_encode(['status' => 'success', 'data' => $user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
    exit;
}

// ── POST ACTIONS (UPDATE DATA) ────────────────────────────────
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if ($action === 'update_info') {
    $user_id           = (int)($data['user_id'] ?? 0);
    $full_name         = trim($data['full_name'] ?? '');
    $email             = trim($data['email'] ?? '');
    $role_id           = (int)($data['role_id'] ?? 1);
    $is_verified       = (int)($data['is_verified'] ?? 0);
    $is_phone_verified = (int)($data['is_phone_verified'] ?? 0);

    if (!$user_id || empty($full_name) || empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        exit;
    }

    // Check email duplicate excluding current user
    $emailStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $emailStmt->bind_param("si", $email, $user_id);
    $emailStmt->execute();
    if ($emailStmt->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email already in use.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, role_id = ?, is_verified = ?, is_phone_verified = ? WHERE id = ?");
    $stmt->bind_param("ssiiii", $full_name, $email, $role_id, $is_verified, $is_phone_verified, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'User updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update user.']);
    }
    exit;
}

if ($action === 'delete') {
    $user_id = (int)($data['user_id'] ?? 0);

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Missing user_id.']);
        exit;
    }

    // Don't let an admin delete themselves through this panel
    if ($user_id === (int)$_SESSION['user_id']) {
        echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account from here.']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Cancel pending appointments
        $stmt = $conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE user_id = ? AND status = 'Pending'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Delete vehicles
        $stmt = $conn->prepare("DELETE FROM vehicles WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'User not found.']);
            exit;
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'User deleted successfully.']);
    } catch (Throwable $e) {
        $conn->rollback();
        error_log("manage_user.php delete failed: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete user: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
exit;
?>