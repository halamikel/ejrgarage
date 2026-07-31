<?php
/**
 * TEMPORARY DEBUG VERSION of get_vehicles.php
 * Upload this as get_vehicles.php, test, then replace with the real version.
 * DELETE THIS FILE after debugging is done.
 */
header('Content-Type: application/json');
require_once 'db_config.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secret_key = "EJR_Garage_Management_System_2026_Secure_Key_ite302";

// ── STEP 1: Check getallheaders ───────────────────────────────────────────
$allHeaders = getallheaders();
$headerKeys = array_keys(array_change_key_case($allHeaders, CASE_LOWER));

// ── STEP 2: Check every possible place the token might be ────────────────
$authFromHeaders  = $allHeaders['Authorization']
                 ?? $allHeaders['authorization']
                 ?? $_SERVER['HTTP_AUTHORIZATION']
                 ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                 ?? null;

// ── STEP 3: Try to decode if we got something ─────────────────────────────
$decoded   = null;
$jwtError  = null;
$token     = null;

if ($authFromHeaders && preg_match('/bearer\s(\S+)/i', $authFromHeaders, $m)) {
    $token = $m[1];
    try {
        $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
    } catch (Exception $e) {
        $jwtError = $e->getMessage();
    }
}

// ── STEP 4: Check DB ──────────────────────────────────────────────────────
$dbOk = isset($conn) && !$conn->connect_error;

// ── RETURN FULL DIAGNOSTIC ────────────────────────────────────────────────
echo json_encode([
    "debug"              => true,
    "php_sapi"           => php_sapi_name(),
    "getallheaders_exists" => function_exists('getallheaders'),
    "header_keys_found"  => $headerKeys,
    "HTTP_AUTHORIZATION_in_SERVER" => isset($_SERVER['HTTP_AUTHORIZATION']),
    "REDIRECT_HTTP_AUTHORIZATION"  => isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']),
    "auth_header_value"  => $authFromHeaders ? substr($authFromHeaders, 0, 30) . '...' : null,
    "token_found"        => $token !== null,
    "jwt_decoded"        => $decoded !== null,
    "jwt_uid"            => $decoded->uid ?? null,
    "jwt_error"          => $jwtError,
    "db_connected"       => $dbOk,
    "db_error"           => $conn->connect_error ?? null,
], JSON_PRETTY_PRINT);
