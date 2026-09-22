<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo isset($pageTitle) ? $pageTitle : 'EJR Garage'; ?></title>

  <!-- iOS Add-to-Home-Screen support -->
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="EJR Garage">
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/icons/apple-touch-icon-180.png">
  <link rel="apple-touch-icon" sizes="167x167" href="/assets/icons/apple-touch-icon-167.png">
  <link rel="apple-touch-icon" sizes="152x152" href="/assets/icons/apple-touch-icon-152.png">
  <link rel="apple-touch-icon" sizes="120x120" href="/assets/icons/apple-touch-icon-120.png">

  <!-- Web app manifest (Android / general PWA support) -->
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#b30000">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Rajdhani:wght@500;700&display=swap">
  <?php
    $cssFiles = ['variables.css', 'base.css', 'layout.css', 'components.css', 'pages.css', 'chat-ui.css', 'verification.css', 'admin_dashboard.css', 'responsive.css'];
    foreach ($cssFiles as $cssFile) {
        $path = __DIR__ . '/../css/' . $cssFile;
        $v = @filemtime($path) ?: time();
        echo '<link rel="stylesheet" href="/css/' . $cssFile . '?v=' . $v . '">' . "\n  ";
    }
  ?>
  <link rel="stylesheet" href="/css/account.css?v=4">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="/js/global.js"></script>
  <?php if (isset($extraJs)) echo $extraJs; ?>
</head>
<body class="<?php echo isset($bodyClass) ? $bodyClass : ''; ?>">

<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container d-flex align-items-center">
    <a class="navbar-brand" href="/index.php">
      <img src="/assets/images/EJRgarage_logoo.png" class="logo" alt="EJR Garage Logo">
    </a>

    <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navContent">
      <ul class="navbar-nav ms-auto align-items-lg-center me-lg-3">
        <li class="nav-item"><a class="nav-link" href="/index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="/booking-details.php">Book Appointment</a></li>
         <li class="nav-item"><a class="nav-link" href="/available_parts.php">Available Parts</a></li>
        <li class="nav-item"><a class="nav-link" href="/about-us.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="/contact.php">Contact</a></li>
        <li class="nav-item"><a class="nav-link" href="/download-app.php"><i class="bi bi-android2 me-1"></i>Get the App</a></li>
      </ul>
      
      <span class="nav-greeting me-3" id="navGreeting" style="display:none; color:#1e2a36; font-weight:600;"></span>

      <?php if(isset($_SESSION['user_id'])): ?>
          <a href="/account.php" class="btn btn-quote" id="navAuthBtn">My Account</a>
      <?php else: ?>
          <a href="/login.php" class="btn btn-quote" id="navAuthBtn">Get Started</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<script>
(function () {
    var token    = localStorage.getItem('jwt_token');
    var btn      = document.getElementById('navAuthBtn');
    var greeting = document.getElementById('navGreeting');
    if (!btn) return;
    if (token) {
        btn.textContent = 'My Account';
        btn.href        = '/account.php';

        if (greeting) {
            var firstName = '';
            try {
                var user = JSON.parse(localStorage.getItem('user'));
                if (user && user.name) {
                    firstName = user.name.trim().split(' ')[0];
                }
            } catch (e) {}

            if (firstName) {
                greeting.textContent = 'Hi, ' + firstName;
                greeting.style.display = 'inline-block';
            }
        }
    } else {
        btn.textContent = 'Get Started';
        btn.href        = '/login.php';
        if (greeting) greeting.style.display = 'none';
    }
})();
</script>