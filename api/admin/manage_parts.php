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
if (preg_match('/bearer\\s(\\S+)/i', $auth, $m)) {
    try {
        $decoded = JWT::decode($m[1], new Key(JWT_SECRET, 'HS256'));
        $adminId = (int)($decoded->uid  ?? 0);
        $role    = (int)($decoded->role ?? 0);
        $isAdmin = ($role === 2 || $adminId === 21);
    } catch (Exception $e) {}
}
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Admin access required.']);
    exit;
}
$current_user_id  = $adminId;
$current_admin_id = $adminId;
$is_admin         = true;

// Store common variables
$current_user_id = $adminId;
$is_admin = ($role == 2 || $current_user_id == 21);

$data   = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'get_inquiries':
        // 1. Verify Admin Status
        if ($role != 2 && $adminId != 21) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
    
        // 2. Query (Adjust 'customer_name' if it's stored in the 'users' table)
        $sql = "SELECT pi.*, p.name as part_name 
                FROM part_inquiries pi 
                JOIN parts p ON pi.part_id = p.id 
                ORDER BY pi.created_at DESC";
                
        $result = $conn->query($sql);
        
        if (!$result) {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
            exit;
        }
    
        $rows = [];
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
        echo json_encode(['status' => 'success', 'inquiries' => $rows]);
        break;

    case 'add':
        $stmt = $conn->prepare("INSERT INTO parts (name, description, price, category, image_url, stock) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssi", 
            $data['name'], $data['description'], $data['price'], 
            $data['category'], $data['image_url'], $data['stock']
        );
        echo $stmt->execute() 
            ? json_encode(['status' => 'success', 'message' => 'Part added.']) 
            : json_encode(['status' => 'error', 'message' => $conn->error]);
        break;

    case 'edit':
        $stmt = $conn->prepare("UPDATE parts SET name=?, description=?, price=?, category=?, image_url=?, stock=? WHERE id=?");
        $stmt->bind_param("ssdssii", 
            $data['name'], $data['description'], $data['price'], 
            $data['category'], $data['image_url'], $data['stock'], $data['id']
        );
        echo $stmt->execute() 
            ? json_encode(['status' => 'success', 'message' => 'Part updated.']) 
            : json_encode(['status' => 'error', 'message' => $conn->error]);
        break;

    case 'delete':
        $stmt = $conn->prepare("DELETE FROM parts WHERE id=?");
        $stmt->bind_param("i", $data['id']);
        echo $stmt->execute() 
            ? json_encode(['status' => 'success', 'message' => 'Part deleted.']) 
            : json_encode(['status' => 'error', 'message' => $conn->error]);
        break;

    case 'update_inquiry':
        if (!isset($data['id'], $data['status'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing inquiry data']);
            exit;
        }
        
        // Handle optional reply message
        $reply = $data['reply_message'] ?? null;
        
        $stmt = $conn->prepare("UPDATE part_inquiries SET status = ?, reply_message = ? WHERE id = ?");
        $stmt->bind_param("ssi", $data['status'], $reply, $data['id']);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Response sent to customer.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
        break;

    case 'delete_inquiry':
        $stmt = $conn->prepare("DELETE FROM part_inquiries WHERE id=?");
        $stmt->bind_param("i", $data['id']);
        echo $stmt->execute() 
            ? json_encode(['status' => 'success', 'message' => 'Inquiry deleted.']) 
            : json_encode(['status' => 'error', 'message' => $conn->error]);
        break;

    default:
        // If no specific action, default to fetching the full parts list
        $result = $conn->query("SELECT * FROM parts ORDER BY created_at DESC");
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        echo json_encode(['status' => 'success', 'parts' => $rows]);
        break;
}
?>