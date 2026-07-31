<?php
session_start();
// Security: Redirect if no pending session exists
if (!isset($_SESSION['pending_uid'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Account | EJR Garage</title>

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="EJR Garage">
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/icons/apple-touch-icon-180.png">
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#b30000">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/verification.css">
</head>
<body>

<div class="verify-card">
    <div class="spinner-border spinner-custom mb-4" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    
    <h2 class="fw-bold mb-3">Checking Verification</h2>
    <p class="text-muted">We've sent a verification link to your email. Please check your inbox and click the link to activate your account.</p>
    
    <hr>
    
    <p class="status-text">This page will <strong>automatically redirect</strong> once you are verified. Please do not close this window.</p>
    
    <div class="mt-4">
        <small>Didn't get the email? <a href="#" id="resendBtn" class="btn-resend">Resend Email</a></small>
    </div>
</div>

<script>
    // POLLING LOGIC: Check database every 3 seconds
    const checkInterval = setInterval(async () => {
        try {
            const response = await fetch('api/check_status.php');
            const data = await response.json();

            if (data.status === "verified") {
                clearInterval(checkInterval);
                window.location.href = 'index.php'; // Verification successful!
            }
        } catch (error) {
            console.error("Status check failed:", error);
        }
    }, 3000);

    // Resend Logic Placeholder
    document.getElementById('resendBtn').addEventListener('click', (e) => {
        e.preventDefault();
        alert("A new verification link is being processed.");
    });
</script>

</body>
</html>