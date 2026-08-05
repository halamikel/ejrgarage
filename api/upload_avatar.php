<?php
header('Content-Type: application/json');
session_start();

require_once 'db_config.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined("JWT_SECRET")) require_once __DIR__ . "/env.php";
require_once 'cloudinary_helper.php';
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

    // ── UPLOAD TO CLOUDINARY (survives restarts — Render's local disk
    // does not, see cloudinary_helper.php) ──────────────────────────
    $publicId = 'ejr-avatars/avatar_' . $userId . '_' . time();

    // Fetch old picture first so we can clean it up on Cloudinary after
    // a successful new upload (only if it's actually a Cloudinary URL —
    // legacy local-disk paths have nothing to delete there).
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $oldPublicId = $current && !empty($current['profile_picture'])
        ? cloudinary_public_id_from_url($current['profile_picture'])
        : null;

    $upload = cloudinary_upload($file['tmp_name'], $publicId);

    if (!$upload['ok']) {
        error_log("Cloudinary avatar upload failed for user {$userId}: " . $upload['error']);
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload photo: ' . $upload['error']]);
        exit;
    }

    $imageUrl = $upload['url'];

    // Save to DB
    $upStmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $upStmt->bind_param("si", $imageUrl, $userId);

    if ($upStmt->execute()) {
        // Best-effort cleanup of the previous image — do this after the
        // DB write succeeds so a delete failure never leaves a user with
        // no photo referenced at all.
        if ($oldPublicId) {
            cloudinary_delete($oldPublicId);
        }
        echo json_encode([
            'status'          => 'success',
            'message'         => 'Profile picture updated successfully.',
            'profile_picture' => $imageUrl
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