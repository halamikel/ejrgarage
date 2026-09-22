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
  <link rel="stylesheet" href="/css/account.css?v=6">
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
         <li class="nav-item"><a class="nav-link" href="/available_parts.php">Shop</a></li>
        <li class="nav-item"><a class="nav-link" href="/about-us.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="/contact.php">Contact</a></li>
        <li class="nav-item"><a class="nav-link" href="/download-app.php"><i class="bi bi-android2 me-1"></i>Get the App</a></li>
      </ul>
      
      <span class="nav-greeting me-3" id="navGreeting" style="display:none; color:#1e2a36; font-weight:600;"></span>

      <a href="/login.php" class="btn btn-quote" id="navAuthBtn">Get Started</a>

      <div class="nav-account-dropdown" id="navAccountDropdown" style="display:none; position:relative;">
        <button type="button" class="nav-avatar-btn" id="navAvatarBtn" aria-haspopup="true" aria-expanded="false">
          <span class="nav-avatar-btn-photo" style="width:42px; height:42px; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center;">
            <img id="navAvatarImg" alt="Profile photo" style="display:none; width:100%; height:100%; object-fit:cover; border-radius:50%;">
            <i class="bi bi-person-fill" id="navAvatarIcon"></i>
          </span>
          <i class="bi bi-chevron-down nav-avatar-caret" id="navAvatarCaret" aria-hidden="true"></i>
        </button>

        <div class="nav-account-menu" id="navAccountMenu" style="display:none; position:absolute; top:calc(100% + 12px); right:0;">
          <div class="nav-account-menu-header">
            <div class="nav-account-menu-avatar" style="width:48px; height:48px; border-radius:50%; overflow:hidden;">
              <img id="navMenuAvatarImg" alt="Profile photo" style="display:none; width:100%; height:100%; object-fit:cover; border-radius:50%;">
              <i class="bi bi-person-fill" id="navMenuAvatarIcon"></i>
            </div>
            <div class="nav-account-menu-info">
              <div class="nav-account-menu-name" id="navMenuName">...</div>
              <div class="nav-account-menu-email" id="navMenuEmail"></div>
            </div>
          </div>

          <hr class="nav-account-menu-divider">

          <a href="/account.php" class="nav-account-menu-item">
            <i class="bi bi-person"></i> Profile
          </a>
          <a href="/my_vehicles.php" class="nav-account-menu-item">
            <i class="bi bi-truck"></i> My Vehicles
          </a>
          <a href="/my_orders.php" class="nav-account-menu-item">
            <i class="bi bi-bag-check"></i> My Orders
          </a>
          <a href="/my_inquiries.php" class="nav-account-menu-item">
            <i class="bi bi-chat-left-text"></i> My Inquiries
          </a>
          <a href="/my_appointments.php" class="nav-account-menu-item">
            <i class="bi bi-calendar-event"></i> Appointments
          </a>
          <a href="/service_history.php" class="nav-account-menu-item">
            <i class="bi bi-clock-history"></i> Service History
          </a>
          <a href="/account.php" class="nav-account-menu-item">
            <i class="bi bi-gear"></i> Settings
          </a>

          <hr class="nav-account-menu-divider">

          <a href="javascript:void(0)" class="nav-account-menu-item text-danger" id="navLogoutBtn">
            <i class="bi bi-box-arrow-left"></i> Logout
          </a>
        </div>
      </div>
    </div>
  </div>
</nav>

<script>
(function () {
    var token       = localStorage.getItem('jwt_token');
    var authBtn     = document.getElementById('navAuthBtn');
    var dropdown    = document.getElementById('navAccountDropdown');
    var avatarBtn   = document.getElementById('navAvatarBtn');
    var menu        = document.getElementById('navAccountMenu');
    var greeting    = document.getElementById('navGreeting');
    var logoutBtn   = document.getElementById('navLogoutBtn');

    function applyAvatar(user) {
        var pic = user && user.profile_picture;
        [document.getElementById('navAvatarImg'), document.getElementById('navMenuAvatarImg')].forEach(function (img, i) {
            var icon = i === 0 ? document.getElementById('navAvatarIcon') : document.getElementById('navMenuAvatarIcon');
            if (!img || !icon) return;
            if (pic) {
                var src = pic.indexOf('http') === 0 ? pic : ('/' + pic);
                img.onerror = function () { img.style.display = 'none'; icon.style.display = ''; };
                img.src = src + '?v=' + Date.now();
                img.style.display = 'block';
                icon.style.display = 'none';
            } else {
                img.removeAttribute('src');
                img.style.display = 'none';
                icon.style.display = '';
            }
        });

        var name = (user && (user.full_name || user.name)) || 'EJR Customer';
        var nameEl  = document.getElementById('navMenuName');
        var emailEl = document.getElementById('navMenuEmail');
        if (nameEl)  nameEl.textContent  = name;
        if (emailEl) emailEl.textContent = (user && user.email) || '';

        if (greeting) {
            var firstName = name.trim().split(' ')[0];
            greeting.textContent = 'Hi, ' + firstName;
            greeting.style.display = 'inline-block';
        }
    }

    if (!authBtn || !dropdown) return;

    if (token) {
        authBtn.style.display  = 'none';
        dropdown.style.display = '';

        var cachedUser = null;
        try { cachedUser = JSON.parse(localStorage.getItem('user')); } catch (e) {}
        if (cachedUser) applyAvatar(cachedUser);

        // Refresh with the latest profile info (e.g. a just-changed avatar)
        fetch('api/me.php', {
            headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' }
        })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            if (data && data.status === 'success' && data.user) {
                localStorage.setItem('user', JSON.stringify(data.user));
                applyAvatar(data.user);
            }
        })
        .catch(function () {});

        if (avatarBtn && menu) {
            function openMenu() {
                menu.classList.add('show');
                menu.style.display = 'block';
                avatarBtn.setAttribute('aria-expanded', 'true');
            }
            function closeMenu() {
                menu.classList.remove('show');
                menu.style.display = 'none';
                avatarBtn.setAttribute('aria-expanded', 'false');
            }
            avatarBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (menu.classList.contains('show')) { closeMenu(); } else { openMenu(); }
            });
            document.addEventListener('click', function (e) {
                if (!dropdown.contains(e.target)) closeMenu();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeMenu();
            });
        }

        if (logoutBtn) {
            logoutBtn.addEventListener('click', function () {
                var t = localStorage.getItem('jwt_token');
                var finish = function () {
                    localStorage.removeItem('jwt_token');
                    localStorage.removeItem('user');
                    fetch('api/logout.php').finally(function () { window.location.href = '/index.php'; });
                };
                if (t) {
                    fetch('api/user_end_session.php', { method: 'POST', headers: { 'Authorization': 'Bearer ' + t } }).finally(finish);
                } else {
                    finish();
                }
            });
        }
    } else {
        authBtn.style.display  = '';
        dropdown.style.display = 'none';
        authBtn.textContent    = 'Get Started';
        authBtn.href           = '/login.php';
        if (greeting) greeting.style.display = 'none';
    }
})();
</script>