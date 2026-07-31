<?php
header("Content-Type: application/json");
require_once 'db_config.php'; 

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? '';
$new_password = $data['password'] ?? '';

if (empty($token) || empty($new_password)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data.']);
    exit;
}

try {
    // 1. Verify token exists in the 'password_resets' table
    $check = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1");
    $check->bind_param("s", $token);
    $check->execute();
    $res = $check->get_result();
    $request = $res->fetch_assoc();

    if (!$request) {
        echo json_encode(['status' => 'error', 'message' => 'Link expired or invalid.']);
        exit;
    }

    $email = $request['email'];
    
    // 2. Hash the new password using BCRYPT to match your existing login
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    /**
     * 3. THE FIX: Update the 'users' table 
     * CHANGED 'password' TO 'password_hash' to match your DB screenshot
     */
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // 4. Cleanup the used token
        $clear = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $clear->bind_param("s", $email);
        $clear->execute();

        echo json_encode(['status' => 'success', 'message' => 'Password updated!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No changes made. Try a different password.']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server database error.']);
}
?>