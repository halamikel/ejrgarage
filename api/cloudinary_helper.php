<?php
/**
 * EJR Garage — Cloudinary Helper (avatar storage)
 *
 * Render's web service filesystem is ephemeral: anything upload_avatar.php
 * writes with move_uploaded_file() only survives until the next restart
 * (idle spin-down, redeploy, crash) unless it happens to already be
 * committed in the git repo. Cloudinary stores the image externally so
 * it survives regardless of what happens to the app container.
 *
 * Uses Cloudinary's raw REST API (signed requests) directly via cURL —
 * no SDK dependency, same approach as paymongo_helper.php.
 * Docs: https://cloudinary.com/documentation/upload_images#generating_authentication_signatures
 */

// Builds Cloudinary's required signature: every param except file/api_key/
// signature/resource_type, sorted alphabetically by key, joined as
// "key=value&key=value", with the API secret appended, then SHA1'd.
function cloudinary_sign(array $params): string {
    ksort($params);
    $pairs = [];
    foreach ($params as $key => $value) {
        $pairs[] = "{$key}={$value}";
    }
    $toSign = implode('&', $pairs) . CLOUDINARY_API_SECRET;
    return sha1($toSign);
}

// Uploads a local temp file (e.g. $_FILES['avatar']['tmp_name']) to
// Cloudinary under the given public_id. Returns ['ok' => bool, 'url' =>
// secure_url, 'public_id' => ..., 'error' => ...] .
function cloudinary_upload(string $tmpFilePath, string $publicId): array {
    if (!defined('CLOUDINARY_CLOUD_NAME') || !CLOUDINARY_CLOUD_NAME) {
        return ['ok' => false, 'error' => 'Cloudinary is not configured on the server.'];
    }

    $timestamp = time();
    $signParams = [
        'public_id' => $publicId,
        'timestamp' => $timestamp,
        'overwrite' => 'true',
    ];
    $signature = cloudinary_sign($signParams);

    $postFields = [
        'file'       => new CURLFile($tmpFilePath),
        'public_id'  => $publicId,
        'timestamp'  => $timestamp,
        'overwrite'  => 'true',
        'api_key'    => CLOUDINARY_API_KEY,
        'signature'  => $signature,
    ];

    $ch = curl_init("https://api.cloudinary.com/v1_1/" . CLOUDINARY_CLOUD_NAME . "/image/upload");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'error' => 'Could not reach Cloudinary: ' . $curlError];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200 || !isset($data['secure_url'])) {
        $msg = $data['error']['message'] ?? "Cloudinary upload failed (HTTP {$httpCode}).";
        return ['ok' => false, 'error' => $msg];
    }

    return [
        'ok'        => true,
        'url'       => $data['secure_url'],
        'public_id' => $data['public_id'],
    ];
}

// Deletes an image from Cloudinary by public_id. Best-effort — callers
// should not fail the overall request just because cleanup of the old
// image didn't succeed.
function cloudinary_delete(string $publicId): bool {
    if (!defined('CLOUDINARY_CLOUD_NAME') || !CLOUDINARY_CLOUD_NAME) {
        return false;
    }

    $timestamp = time();
    $signParams = [
        'public_id' => $publicId,
        'timestamp' => $timestamp,
    ];
    $signature = cloudinary_sign($signParams);

    $postFields = [
        'public_id' => $publicId,
        'timestamp' => $timestamp,
        'api_key'   => CLOUDINARY_API_KEY,
        'signature' => $signature,
    ];

    $ch = curl_init("https://api.cloudinary.com/v1_1/" . CLOUDINARY_CLOUD_NAME . "/image/destroy");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) return false;
    $data = json_decode($response, true);
    return ($data['result'] ?? '') === 'ok';
}

// A stored profile_picture value is either a legacy relative path
// ("uploads/avatars/avatar_3_....jpg" — pre-Cloudinary, still committed
// in the repo) or a full Cloudinary URL. Only the latter has anything to
// clean up on Cloudinary's side.
function cloudinary_public_id_from_url(string $url): ?string {
    if (strpos($url, 'res.cloudinary.com') === false) {
        return null; // legacy local path, nothing to delete on Cloudinary
    }
    // .../image/upload/v1234567890/ejr-avatars/avatar_39_167....jpg
    if (!preg_match('#/upload/v\d+/(.+)\.[a-zA-Z0-9]+$#', $url, $m)) {
        return null;
    }
    return $m[1];
}
