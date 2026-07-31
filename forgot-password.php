<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>EJR Garage — Forgot Password</title>

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="EJR Garage">
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/icons/apple-touch-icon-180.png">
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#b30000">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/shared.css"/>
  <link rel="stylesheet" href="css/login.css"/>
</head>
<body>

  <div class="stripe"></div>

  <div class="page">
    <div class="panel">
      <div class="logo-wrap">
        <div class="logo-icon"><i class="bi bi-wrench"></i></div>
        <div>
          <div class="logo-text">EJR<span>Garage</span></div>
          <div class="logo-tagline">Certified Repair Specialists</div>
        </div>
      </div>
      <h1 class="hero-title">Reset <span class="hl">Access.</span></h1>
      <p class="hero-sub">Enter your email address and we'll send you a secure link to reset your password and regain access to your dashboard.</p>
    </div>

    <div class="panel panel-form">
      <div class="card">
        <div class="card-title">Forgot Password</div>
        <p class="card-sub">We'll send a recovery link to your inbox.</p>
        
        <div class="field">
          <label for="f-email">Email Address</label>
          <input type="email" id="f-email" placeholder="juan@example.com"/>
        </div>
        
        <div class="row-util centered">
          <a href="login.php" class="forgot"><i class="bi bi-arrow-left"></i> Back to Sign In</a>
        </div>
        
        <button class="btn" id="resetBtn" onclick="handleReset()">Send Reset Link</button>
      </div>
    </div>
  </div>

  <div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="authToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" style="border: none;">
        <div class="toast-header" style="background: transparent; border-bottom: 1px solid #f0f0f0;">
            <i class="bi bi-wrench-adjustable me-2"></i>
            <strong class="me-auto" style="color: #111;">EJR Garage</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMsg" style="color: #444; font-weight: 500; padding: 15px;">
        </div>
    </div>
</div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/auth.js"></script>
</body>
</html>