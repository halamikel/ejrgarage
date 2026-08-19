<?php 
  session_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head role="header">
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>EJR Garage — Sign In</title>

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="EJR Garage">
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/icons/apple-touch-icon-180.png">
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#b30000">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/shared.css?v=<?php echo time(); ?>" />
  <link rel="stylesheet" href="css/login.css"/>
</head>
<main role="main">

  <div class="stripe"></div>
  <svg class="gear gear-1" viewBox="0 0 100 100"><path d="M43.3 5.8l-2.5 9.4a35 35 0 0 0-8.6 3.6l-9-4.3-9.3 9.3 4.3 9A35 35 0 0 0 14.6 42l-9.4 2.5v13.2l9.4 2.5a35 35 0 0 0 3.6 8.6l-4.3 9 9.3 9.3 9-4.3a35 35 0 0 0 8.6 3.6l2.5 9.4h13.2l2.5-9.4a35 35 0 0 0 8.6-3.6l9 4.3 9.3-9.3-4.3-9a35 35 0 0 0 3.6-8.6l9.4-2.5V43.3l-9.4-2.5a35 35 0 0 0-3.6-8.6l4.3-9-9.3-9.3-9 4.3a35 35 0 0 0-8.6-3.6L56.5 5.8zM50 34a16 16 0 1 1 0 32 16 16 0 0 1 0-32z"/></svg>
  <svg class="gear gear-2" viewBox="0 0 100 100"><path d="M43.3 5.8l-2.5 9.4a35 35 0 0 0-8.6 3.6l-9-4.3-9.3 9.3 4.3 9A35 35 0 0 0 14.6 42l-9.4 2.5v13.2l9.4 2.5a35 35 0 0 0 3.6 8.6l-4.3 9 9.3 9.3 9-4.3a35 35 0 0 0 8.6 3.6l2.5 9.4h13.2l2.5-9.4a35 35 0 0 0 8.6-3.6l9 4.3 9.3-9.3-4.3-9a35 35 0 0 0 3.6-8.6l9.4-2.5V43.3l-9.4-2.5a35 35 0 0 0-3.6-8.6l4.3-9-9.3-9.3-9 4.3a35 35 0 0 0-8.6-3.6L56.5 5.8zM50 34a16 16 0 1 1 0 32 16 16 0 0 1 0-32z"/></svg>
  <svg class="gear gear-3" viewBox="0 0 100 100"><path d="M43.3 5.8l-2.5 9.4a35 35 0 0 0-8.6 3.6l-9-4.3-9.3 9.3 4.3 9A35 35 0 0 0 14.6 42l-9.4 2.5v13.2l9.4 2.5a35 35 0 0 0 3.6 8.6l-4.3 9 9.3 9.3 9-4.3a35 35 0 0 0 8.6 3.6l2.5 9.4h13.2l2.5-9.4a35 35 0 0 0 8.6-3.6l9 4.3 9.3-9.3-4.3-9a35 35 0 0 0 3.6-8.6l9.4-2.5V43.3l-9.4-2.5a35 35 0 0 0-3.6-8.6l4.3-9-9.3-9.3-9 4.3a35 35 0 0 0-8.6-3.6L56.5 5.8zM50 34a16 16 0 1 1 0 32 16 16 0 0 1 0-32z"/></svg>

  <div class="page">
    <div class="panel">
      <div class="logo-wrap">
        <div class="logo-icon"><i class="bi bi-wrench"></i></div>
        <div>
          <div class="logo-text">EJR<span>Garage</span></div>
          <div class="logo-tagline">Certified Repair Specialists</div>
        </div>
      </div>
      <h1 class="hero-title">Welcome <span class="hl">Back.</span></h1>
      <p class="hero-sub">Sign in to check your repair status, upcoming appointments, and full vehicle service history.</p>
    </div>

    <div class="panel panel-form">
      <div class="card">
        <div class="card-title">Sign In</div>
        <p class="card-sub">Access your EJR Garage dashboard.</p>
        
        <div class="field">
          <label for="l-email">Email Address</label>
          <input type="email" id="l-email" placeholder="juan@example.com"/>
          <div class="field-error" id="emailError"></div>
        </div>
        
        <div class="field">
          <label for="l-pw">Password</label>
          <div class="input-wrap">
            <input type="password" id="l-pw" class="has-eye aria-label='Show-Password'" placeholder="Your password"/>
            <button class="eye aria-label='Toggle Password Visibility'" type="button" onclick="togglePw('l-pw', this)"><i class="bi bi-eye"></i></button>
          </div>
          <div class="field-error" id="pwError"></div>
        </div>
        
        <div class="row-util">
          <label class="remember"><input type="checkbox" id="rememberMe"/> Remember me</label>
          <a href="javascript:void(0)" class="forgot" onclick="openForgotModal()">Forgot Password?</a>
        </div>
        
        <button class="btn" id="loginBtn" onclick="handleLogin()">Sign In</button>
        <p class="bottom-link">Don't have an account? <a href="registration.php">Create one</a></p>
      </div>
    </div>
  </div>

  <script src="js/global.js?v=<?php echo time(); ?>"></script>
  <script src="js/auth.js?v=<?php echo time(); ?>"></script>

</main>
</html>