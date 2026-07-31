<?php
/**
 * EJR Garage — Login API
 *
 * Security hardening (OWASP):
 *  A07 – Rate limiting: 10 attempts per 15 min per IP, plus per IP+email
 *  A03 – Input validated via security.php schema
 *  A02 – JWT secret loaded from env.php
 */
header('Content-Type: application/json');
require_once 'db_config.php';   // loads env.php, $conn
require_once 'security.php';
require_once 'rate_limit.php';

$ip   = ejr_get_ip();
$raw  = ejr_json_input();

// ── Schema validation ─────────────────────────────────────────
$data = ejr_validate($raw, [
    'email'    => ['type' => 'email',    'max' => 254],
    'password' => ['type' => 'password', 'min' => 1, 'max' => 128],
]);

// ── IP-level rate limit (broad): 20 attempts / 15 min ────────
ejr_rate_limit($conn, 'login_ip', $ip, 20, 300);

// ── IP+email rate limit (targeted): 10 attempts / 15 min ─────
ejr_rate_limit($conn, 'login_user', $ip . '|' . $data['email'], 10, 300);

// ── JWT setup ─────────────────────────────────────────────────
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (session_status() === PHP_SESSION_NONE) session_start();

$stmt = $conn->prepare("SELECT id, full_name, email, phone, address, profile_picture, password_hash, login_attempts, lockout_time, is_verified, is_phone_verified, role_id, created_at FROM users WHERE email = ?");
$stmt->bind_param("s", $data['email']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user) {
    $max_attempts    = 5;
    $lockout_minutes = 5;

    if ($user['lockout_time'] && strtotime($user['lockout_time']) > time()) {
        $remaining = ceil((strtotime($user['lockout_time']) - time()) / 60);
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Too many attempts. Locked for $remaining more minute(s)."]);
        exit;
    }

    // Clear stale lockout once window passes
    if ($user['login_attempts'] > 0 && $user['lockout_time'] && strtotime($user['lockout_time']) <= time()) {
        $conn->prepare("UPDATE users SET login_attempts = 0, lockout_time = NULL WHERE id = ?")->execute([$user['id']]);
        $user['login_attempts'] = 0;
        $user['lockout_time']   = null;
    }

    if (password_verify($data['password'], $user['password_hash'])) {

        if ($user['is_verified'] == 0) {
            $_SESSION['pending_uid'] = $user['id'];
            echo json_encode(["status" => "error", "message" => "Please verify your email first.", "redirect" => "waiting_verification.php"]);
            exit;
        }

        // SUCCESS — reset attempts
        $conn->prepare("UPDATE users SET login_attempts = 0, lockout_time = NULL WHERE id = ?")->execute([$user['id']]);

        $sessionToken = bin2hex(random_bytes(32));
        $conn->prepare("UPDATE users SET session_token = ? WHERE id = ?")->execute([$sessionToken, $user['id']]);

        $payload = [
            'uid'  => $user['id'],
            'role' => $user['role_id'],
            'jti'  => $sessionToken, // unique per login session — prevents token collisions and ties the JWT 1:1 to this session
            'iat'  => time(),
            'exp'  => time() + 86400,
        ];
        $jwt = JWT::encode($payload, JWT_SECRET, 'HS256');

        echo json_encode([
            "status"        => "success",
            "token"         => $jwt,
            "session_token" => $sessionToken,
            "user"          => [
                "id"               => $user['id'],
                "name"             => $user['full_name'],
                "full_name"        => $user['full_name'],
                "email"            => $user['email'],
                "phone"            => $user['phone'],
                "phone_number"     => $user['phone'],
                "address"          => $user['address'],
                "profile_picture"  => $user['profile_picture'],
                "role_id"          => $user['role_id'],
                "is_phone_verified"=> $user['is_phone_verified'],
                "created_at"       => $user['created_at'],
            ]
        ]);

    } else {
        // Wrong password — increment attempts
        $newAttempts = $user['login_attempts'] + 1;
        $lockout     = null;
        if ($newAttempts >= $max_attempts) {
            $lockout = date('Y-m-d H:i:s', time() + $lockout_minutes * 60);
        }
        $upd = $conn->prepare("UPDATE users SET login_attempts = ?, lockout_time = ? WHERE id = ?");
        $upd->bind_param("isi", $newAttempts, $lockout, $user['id']);
        $upd->execute();

        $remaining = $max_attempts - $newAttempts;
        http_response_code(401);
        echo json_encode([
            "status"  => "error",
            "message" => $remaining > 0
                ? "Invalid password. Attempt $newAttempts of $max_attempts."
                : "Account locked for $lockout_minutes minutes due to too many failed attempts.",
            "field"   => "password",
        ]);
    }

} else {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "No account found with that email address.", "field" => "email"]);
}