<?php
/**
 * api/admin/upload_part_image.php
 *
 * Admin-only endpoint for uploading a part's photo. Same shape as
 * ../upload_avatar.php: stores the image on Cloudinary (Render's local
 * disk doesn't survive restarts) and returns a full Cloudinary URL that
 * the mobile app hands straight to manage_parts.php's add/edit action
 * as image_url.
 *
 * Expects: multipart/form-data POST with a single file field "image".
 * Returns: {"status":"success","message":"...","image_url":"https://res.cloudinary.com/..."}
 *       or {"status":"error","message":"..."}
 */

header('Content-Type: application/json');
session_start();

require_once '../db_config.php';
require_once __DIR__ . '/../../includes/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined("JWT_SECRET")) require_once __DIR__ . '/../env.php';
require_once '../cloudinary_helper.php';
$secret_key = JWT_SECRET;

// ── JWT Auth (same shape as manage_parts.php) ──────────────────────
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$auth    = $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$adminId = 0;
$isAdmin = false;

if (preg_match('/bearer\s(\S+)/i', $auth, $m)) {
    try {
        $decoded = JWT::decode($m[1], new Key($secret_key, 'HS256'));
        $adminId = (int)($decoded->uid ?? 0);
        $role    = (int)($decoded->role ?? 0);
        $isAdmin = ($role === 2 || $adminId === 21);
    } catch (Exception $e) {
        // falls through to the 403 below
    }
}

if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Admin access required.']);
    exit;
}

try {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'No image uploaded or upload error.']);
        exit;
    }

    $file = $_FILES['image'];

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo        = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType     = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        echo json_encode(['status' => 'error', 'message' => 'Only JPG, PNG, WEBP, or GIF images are allowed.']);
        exit;
    }

    // Validate file size (max 3MB — same cap as avatar uploads)
    if ($file['size'] > 3 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'Image must be smaller than 3MB.']);
        exit;
    }

    // ── UPLOAD TO CLOUDINARY ────────────────────────────────────────
    // Separate folder from avatars ("ejr-parts" vs "ejr-avatars") so
    // they're easy to tell apart in the Cloudinary dashboard. No DB
    // row to update here — unlike avatars, the caller (add/edit part)
    // decides which part this image belongs to, so we just return the
    // URL and let manage_parts.php save it.
    $publicId = 'ejr-parts/part_' . $adminId . '_' . time();

    $upload = cloudinary_upload($file['tmp_name'], $publicId);

    if (!$upload['ok']) {
        error_log("Cloudinary part image upload failed (admin {$adminId}): " . $upload['error']);
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload photo: ' . $upload['error']]);
        exit;
    }

    echo json_encode([
        'status'    => 'success',
        'message'   => 'Photo uploaded.',
        'image_url' => $upload['url'],
    ]);
} catch (\Throwable $e) {
    error_log("EJR Garage part image upload error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Server error while saving photo: ' . $e->getMessage()
    ]);
}
