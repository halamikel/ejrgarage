<?php
session_start();
session_write_close(); // ✅ add this line
session_start();
require_once '../db_config.php'; 
header('Content-Type: application/json');


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

try {
    $current_admin_id = $adminId;

    // Updated Query: Removed 'user_id != ?' so you can see your own test messages
    // and used LIKE to be less strict with the 'user' string.
    $sql = "SELECT COUNT(*) AS total_unread 
            FROM ejr_chat 
            WHERE is_read = 0 
            AND sender LIKE 'user'";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    echo json_encode([
        'status' => 'success',
        'count' => (int)$data['total_unread']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>