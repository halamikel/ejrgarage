<?php
header('Content-Type: application/json');
session_start();

require_once 'db_config.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined("JWT_SECRET")) require_once __DIR__ . "/env.php";
$secret_key = JWT_SECRET;

$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';

if (!preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token missing']);
    exit;
}

try {
    $decoded = JWT::decode($matches[1], new Key($secret_key, 'HS256'));
    $userId  = $decoded->uid ?? $decoded->id ?? 0;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'No image uploaded or upload error.']);
        exit;
    }

    $file = $_FILES['avatar'];

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo        = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType     = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        echo json_encode(['status' => 'error', 'message' => 'Only JPG, PNG, WEBP, or GIF images are allowed.']);
        exit;
    }

    // Validate file size (max 3MB)
    if ($file['size'] > 3 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'Image must be smaller than 3MB.']);
        exit;
    }

    // Map MIME type to a safe, fixed extension — never trust the uploaded filename's extension.
    // OWASP: An attacker can upload a valid JPEG named shell.php; using pathinfo() on the
    // original name would save it with a .php extension, enabling remote code execution.
    $mimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    $ext      = $mimeToExt[$mimeType]; // Always derived from finfo, never from $_FILES['name']
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
    $uploadDir = '../uploads/avatars/';
    $uploadPath = $uploadDir . $filename;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Delete old avatar if exists
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();

    if ($current && !empty($current['profile_picture'])) {
        $oldPath = $uploadDir . basename($current['profile_picture']);
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save uploaded image. Check folder permissions.']);
        exit;
    }

    $publicPath = 'uploads/avatars/' . $filename;

    // Save to DB
    $upStmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $upStmt->bind_param("si", $publicPath, $userId);

    if ($upStmt->execute()) {
        echo json_encode([
            'status'          => 'success',
            'message'         => 'Profile picture updated successfully.',
            'profile_picture' => $publicPath
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
    }
} catch (\Throwable $e) {
    error_log("EJR Garage avatar upload error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Server error while saving photo: ' . $e->getMessage()
    ]);
}
?>