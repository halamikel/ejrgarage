<?php 
  // We start the session to maintain any temporary security states if needed
  session_start();
  
  // This captures the 'token' sent via the email link (e.g., reset-password.php?token=xyz)
  // We use the null coalescing operator (??) to prevent errors if the token is missing
  $token = $_GET['token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>EJR Garage — Reset Password</title>

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="EJR Garage">
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/icons/apple-touch-icon-180.png">
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#b30000">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/shared.css"/>
  <link rel="stylesheet" href="css/registration.css"/> 
</head>

<style>
  /* Centering the main container for a professional look */
  .page {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    width: 100%;
    background: var(--bg-main);
  }

  /* Centering the form panel within the viewport */
  .panel-form {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 40px;
    width: 100%;
    max-width: 550px;
  }

  /* Visual card styling for the input section */
  .card {
    margin: 0 auto;
    width: 100%;
    max-width: 460px;
    text-align: left;
  }

  /* Maintaining vertical spacing for each input field */
  .field {
    display: flex;
    flex-direction: column;
    width: 100%;
    margin-bottom: 20px;
  }
</style>

<body>

  <div class="stripe"></div>
  <div class="toast" id="toast"></div>

  <div class="page">
    <div class="panel-form">
      
      <div class="logo-wrap mb-4" style="animation: fadeUp .6s ease both;">
        <div class="logo-icon"><i class="bi bi-wrench"></i></div>
        <div>
          <div class="logo-text">EJR<span>Garage</span></div>
          <div class="logo-tagline">Certified Repair Specialists</div>
        </div>
      </div>

      <div class="card">
        <div class="card-title">Reset Password</div>
        <p class="card-sub">Please create a secure password you haven't used before.</p>
        
        <input type="hidden" id="reset-token" value="<?php echo htmlspecialchars($token); ?>">

        <div class="field">
          <label>New Password</label>
          <div class="input-wrap">
            <input type="password" id="r-pw" class="has-eye" placeholder="Enter new password" oninput="updateStrength(this.value)">
            <button class="eye" type="button" onclick="togglePw('r-pw', this)"><i class="bi bi-eye"></i></button>
          </div>
          <div class="strength-wrap">
            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
            <div class="strength-label" id="strengthLabel">Password strength</div>
          </div>
        </div>

        <div class="field">
          <label>Confirm New Password</label>
          <div class="input-wrap">
            <input type="password" id="r-pw2" class="has-eye" placeholder="Confirm new password">
            <button class="eye" type="button" onclick="togglePw('r-pw2', this)"><i class="bi bi-eye"></i></button>
          </div>
        </div>

        <button class="btn mt-3" id="resetBtn" onclick="handlePasswordReset()">Update Password</button>
        <p class="bottom-link"><a href="login.php">Back to Sign In</a></p>
      </div>
    </div>
  </div>

  <script src="js/main.js"></script>
  <script>
    /**
     * handlePasswordReset
     * This function sends the new password and token to the backend.
     */
    async function handlePasswordReset() {
        const token = document.getElementById('reset-token').value;
        const pass1 = document.getElementById('r-pw').value;
        const pass2 = document.getElementById('r-pw2').value;
        const btn = document.getElementById('resetBtn');

        // Basic validation before bothering the server
        if (!pass1 || pass1 !== pass2) {
            showToast("Passwords do not match or are empty!", "#e62429");
            return;
        }

        // Change button state to prevent multiple clicks
        btn.textContent = "UPDATING...";
        btn.disabled = true;

        try {
            // Sends a POST request to the update-password script
            const response = await fetch('api/update-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: token, password: pass1 })
            });

            const data = await response.json();

            if (data.status === 'success') {
                showToast("Password updated! Redirecting to login...", "#40c060");
                // Brief delay so the user can see the success message before redirection
                setTimeout(() => { window.location.href = 'login.php'; }, 2000);
            } else {
                // Handle errors like expired tokens or database issues
                showToast(data.message, "#e62429");
                btn.textContent = "Update Password";
                btn.disabled = false;
            }
        } catch (error) {
            console.error('Error:', error);
            showToast("Server error. Check your connection.", "#e62429");
            btn.disabled = false;
        }
    }
  </script>
</body>
</html>