<?php
session_start();
require_once 'db_config.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die(renderPage('Invalid Request', 'No verification token provided.', false));
}

// Look up the pending request
$stmt = $conn->prepare(
    "SELECT * FROM email_change_requests WHERE token = ? AND expires_at > NOW() LIMIT 1"
);
$stmt->bind_param("s", $token);
$stmt->execute();
$req = $stmt->get_result()->fetch_assoc();

if (!$req) {
    die(renderPage('Link Expired', 'This verification link is invalid or has expired. Please update your profile again.', false));
}

// Apply the changes
$upStmt = $conn->prepare("UPDATE users SET email = ?, phone = ?, full_name = ? WHERE id = ?");
$upStmt->bind_param("sssi", $req['new_email'], $req['new_phone'], $req['new_name'], $req['user_id']);

if ($upStmt->execute()) {
    // Clean up
    $del = $conn->prepare("DELETE FROM email_change_requests WHERE user_id = ?");
    $del->bind_param("i", $req['user_id']);
    $del->execute();

    die(renderPage('Email Updated!', 'Your email address has been successfully changed to <strong>' . htmlspecialchars($req['new_email']) . '</strong>. You may now log in with your new email.', true));
} else {
    die(renderPage('Error', 'A database error occurred. Please try again.', false));
}

function renderPage($title, $message, $success) {
    $color  = $success ? '#28a745' : '#e62429';
    $icon   = $success ? '✓' : '✗';
    $btnTxt = $success ? 'Go to Login' : 'Go Back';
    $btnHref= $success ? '../login.php' : '../account.php';
    return "<!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width,initial-scale=1'>
<title>$title – EJR Garage</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:Arial,sans-serif;background:#f4f6f8;display:flex;align-items:center;justify-content:center;min-height:100vh;}
  .card{background:#fff;border-radius:16px;padding:48px 40px;max-width:460px;width:90%;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,.08);}
  .icon{width:72px;height:72px;border-radius:50%;background:$color;color:#fff;font-size:36px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;}
  h2{color:#1e2a36;margin-bottom:12px;}
  p{color:#5f6b7a;line-height:1.6;margin-bottom:24px;}
  a{display:inline-block;background:linear-gradient(45deg,#e62429,#ff6b00);color:#fff;padding:13px 32px;border-radius:8px;text-decoration:none;font-weight:bold;}
  .brand{color:#e62429;font-size:1.1rem;font-weight:bold;margin-bottom:6px;}
</style>
</head>
<body>
  <div class='card'>
    <div class='brand'>EJR Garage</div>
    <div class='icon'>$icon</div>
    <h2>$title</h2>
    <p>$message</p>
    <a href='$btnHref'>$btnTxt</a>
  </div>
</body>
</html>";
}
