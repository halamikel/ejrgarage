<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EJR Garage — Register</title>

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="EJR Garage">
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/icons/apple-touch-icon-180.png">
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#b30000">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/shared.css?v=<?php echo time(); ?>" />
  <link rel="stylesheet" href="css/registration.css" />
</head>

<body>

  <div class="stripe"></div>
  <svg class="gear gear-1" viewBox="0 0 100 100">
    <path
      d="M43.3 5.8l-2.5 9.4a35 35 0 0 0-8.6 3.6l-9-4.3-9.3 9.3 4.3 9A35 35 0 0 0 14.6 42l-9.4 2.5v13.2l9.4 2.5a35 35 0 0 0 3.6 8.6l-4.3 9 9.3 9.3 9-4.3a35 35 0 0 0 8.6 3.6l2.5 9.4h13.2l2.5-9.4a35 35 0 0 0 8.6-3.6l9 4.3 9.3-9.3-4.3-9a35 35 0 0 0 3.6-8.6l9.4-2.5V43.3l-9.4-2.5a35 35 0 0 0-3.6-8.6l4.3-9-9.3-9.3-9 4.3a35 35 0 0 0-8.6-3.6L56.5 5.8zM50 34a16 16 0 1 1 0 32 16 16 0 0 1 0-32z" />
  </svg>
  <svg class="gear gear-2" viewBox="0 0 100 100">
    <path
      d="M43.3 5.8l-2.5 9.4a35 35 0 0 0-8.6 3.6l-9-4.3-9.3 9.3 4.3 9A35 35 0 0 0 14.6 42l-9.4 2.5v13.2l9.4 2.5a35 35 0 0 0 3.6 8.6l-4.3 9 9.3 9.3 9-4.3a35 35 0 0 0 8.6 3.6l2.5 9.4h13.2l2.5-9.4a35 35 0 0 0 8.6-3.6l9 4.3 9.3-9.3-4.3-9a35 35 0 0 0 3.6-8.6l9.4-2.5V43.3l-9.4-2.5a35 35 0 0 0-3.6-8.6l4.3-9-9.3-9.3-9 4.3a35 35 0 0 0-8.6-3.6L56.5 5.8zM50 34a16 16 0 1 1 0 32 16 16 0 0 1 0-32z" />
  </svg>
  <svg class="gear gear-3" viewBox="0 0 100 100">
    <path
      d="M43.3 5.8l-2.5 9.4a35 35 0 0 0-8.6 3.6l-9-4.3-9.3 9.3 4.3 9A35 35 0 0 0 14.6 42l-9.4 2.5v13.2l9.4 2.5a35 35 0 0 0 3.6 8.6l-4.3 9 9.3 9.3 9-4.3a35 35 0 0 0 8.6 3.6l2.5 9.4h13.2l2.5-9.4a35 35 0 0 0 8.6-3.6l9 4.3 9.3-9.3-4.3-9a35 35 0 0 0 3.6-8.6l9.4-2.5V43.3l-9.4-2.5a35 35 0 0 0-3.6-8.6l4.3-9-9.3-9.3-9 4.3a35 35 0 0 0-8.6-3.6L56.5 5.8zM50 34a16 16 0 1 1 0 32 16 16 0 0 1 0-32z" />
  </svg>

  <div class="toast" id="toast"></div>

  <div class="page">
    <div class="panel">
      <div class="logo-wrap">
        <div class="logo-icon"><i class="bi bi-wrench"></i></div>
        <div>
          <div class="logo-text">EJR<span>Garage</span></div>
          <div class="logo-tagline">Certified Repair Specialists</div>
        </div>
      </div>
      <h1 class="hero-title">Your Car, <span class="hl">Our Priority.</span></h1>
      <p class="hero-sub">Create your account and get instant access to service booking, repair tracking, and your full
        vehicle history.</p>
    </div>

    <div class="panel panel-form">
      <div class="card">
        <div class="card-title">Create Account</div>
    
        <div class="divider"><span>Personal Details</span></div>
        <div class="row-2">
          <div class="field"><label>First Name</label><input type="text" id="r-fname" placeholder="Juan" required></div>
          <div class="field"><label>Last Name</label><input type="text" id="r-lname" placeholder="dela Cruz" required></div>
        </div>
        
        <div class="field"><label>Email Address</label><input type="email" id="r-email" placeholder="juan@example.com" /></div>
        
        <div class="field"><label>Phone Number</label>
          <div class="input-wrap">
            <span class="prefix">+63</span>
            <input
              type="tel"
              id="r-phone"
              placeholder="9XXXXXXXXX"
              pattern="^9\d{9}$"
              inputmode="numeric"
              maxlength="10"
              required
              oninput="this.value = this.value.replace(/\D/g, '')"
              onkeydown="return ['Backspace','Delete','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Tab','Home','End'].includes(event.key) || /^\d$/.test(event.key)"
              onpaste="event.preventDefault(); const t = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,10); document.execCommand('insertText', false, t)"
            >
          </div>
        </div>
    
        <div class="divider"><span>Security</span></div>
        <div class="field">
          <label>Password</label>
          <div class="input-wrap">
            <input type="password" id="r-pw" class="has-eye" placeholder="Enter password" minlength="8" required oninput="updateStrength(this.value)">
            <button class="eye" type="button" onclick="togglePw('r-pw', this)"><i class="bi bi-eye"></i></button>
          </div>
          <div class="strength-wrap">
            <div class="strength-bar">
              <div class="strength-fill" id="strengthFill"></div>
            </div>
            <div class="strength-label" id="strengthLabel">Password strength</div>
          </div>
        </div>
    
        <div class="field">
          <label>Confirm Password</label>
          <div class="input-wrap">
            <input type="password" id="r-pw2" class="has-eye" placeholder="Re-enter password" required>
            <button class="eye" type="button" onclick="togglePw('r-pw2', this)"><i class="bi bi-eye"></i></button>
          </div>
        </div>
    
        <div class="check-wrap">
          <input type="checkbox" id="terms" /><span>I agree to the <a href="javascript:void(0)" onclick="openModal('tosModal')">Terms of Service</a></span>
        </div>
        
        <button class="btn" id="regBtn" onclick="handleRegister()">Create My Account</button>
        <p class="bottom-link">Already have an account? <a href="login.php">Sign In</a></p>
      </div>
    </div>

  <div id="tosModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal('tosModal')">&times;</span>
      <h2>Terms of Service</h2>
      <div class="modal-body">
        <p><strong>Last updated:</strong> <?php echo date('F j, Y'); ?></p>

        <h3>1. Acceptance of Terms</h3>
        <p>By creating an account with EJR Garage, you agree to be bound by these Terms of Service. If you do not agree with any part of these terms, please do not register for or use our services.</p>

        <h3>2. Account Registration</h3>
        <p>You must provide accurate, current, and complete information when creating your account, including your name, email address, and phone number. You are responsible for maintaining the confidentiality of your password and for all activity that occurs under your account.</p>

        <h3>3. Service Bookings</h3>
        <p>EJR Garage allows you to book vehicle repair and maintenance appointments, track service history, and manage your vehicles online. Appointment availability is subject to confirmation, and we reserve the right to reschedule or decline bookings due to capacity or unforeseen circumstances.</p>

        <h3>4. Parts &amp; Payments</h3>
        <p>Prices for parts and services are subject to change without prior notice. Payments made through the platform are processed via our third-party payment providers. You agree to pay all charges incurred under your account at the prices in effect when such charges are incurred.</p>

        <h3>5. User Conduct</h3>
        <ul>
          <li>You will not use the platform for any unlawful purpose or in violation of these Terms.</li>
          <li>You will not attempt to gain unauthorized access to other accounts, systems, or networks connected to EJR Garage.</li>
          <li>You will not submit false, misleading, or fraudulent information when booking services or placing orders.</li>
        </ul>

        <h3>6. Vehicle &amp; Personal Information</h3>
        <p>Information you provide about yourself and your vehicles is used to deliver and improve our services, including appointment scheduling, service history tracking, and customer support. We do not sell your personal information to third parties.</p>

        <h3>7. Limitation of Liability</h3>
        <p>EJR Garage strives to provide accurate and reliable service, but we make no warranties regarding the availability, accuracy, or reliability of the platform. To the fullest extent permitted by law, EJR Garage shall not be liable for any indirect, incidental, or consequential damages arising from your use of the platform.</p>

        <h3>8. Account Termination</h3>
        <p>We reserve the right to suspend or terminate accounts that violate these Terms of Service, provide fraudulent information, or misuse the platform in any way.</p>

        <h3>9. Changes to These Terms</h3>
        <p>We may update these Terms of Service from time to time. Continued use of your account after changes are posted constitutes your acceptance of the revised terms.</p>

        <h3>10. Contact Us</h3>
        <p>If you have any questions about these Terms of Service, please reach out to us through the Contact page.</p>
      </div>
    </div>
  </div>

  <script src="js/global.js?v=<?php echo time(); ?>"></script>
  <script src="js/auth.js?v=<?php echo time(); ?>"></script>

</body>

</html>