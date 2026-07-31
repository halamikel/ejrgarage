// Don't touch this code of line
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('a[href*="logout"]').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            localStorage.clear();
            window.location.href = '/api/logout.php';
        });
    });
});


// ==========================================
// 1. UTILITY FUNCTIONS
// ==========================================
function togglePw(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon = btn.querySelector("i");
  const isHidden = input.type === "password";

  input.type = isHidden ? "text" : "password";

  if (isHidden) {
    icon.classList.remove("bi-eye");
    icon.classList.add("bi-eye-slash");
  } else {
    icon.classList.remove("bi-eye-slash");
    icon.classList.add("bi-eye");
  }
}

function showToast(message, color) {
  const toast = document.getElementById('toast');
  if(!toast) return;
  toast.textContent = message;
  toast.style.background = color || '#40c060';
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3200);
}

function showFieldError(input, message) {
  input.classList.add('error');
  let parent = input.parentNode;
  if (input.classList.contains('has-eye')) {
    parent = input.parentNode.parentNode;
  }
  let errorEl = parent.querySelector('.field-error');
  if (!errorEl) {
    errorEl = document.createElement('div');
    errorEl.className = 'field-error';
    parent.appendChild(errorEl);
  }
  errorEl.textContent = message;
}

function clearFieldError(input) {
  input.classList.remove('error');
  const errorEl = input.parentNode.querySelector('.field-error');
  if (errorEl) errorEl.remove();
}

// Check if User is Logged In
function isLoggedIn() {
  return localStorage.getItem("jwt_token") !== null || localStorage.getItem("user") !== null;
}

// ==========================================
// 2. AUTHENTICATION (Login, Register, Logout)
// ==========================================
function checkCredentials(email, password) {
  return email === 'juan@example.com' && password === '123456';
}

function handleLogin() {
  const emailInput = document.getElementById('l-email');
  const pwInput = document.getElementById('l-pw');
  if(!emailInput || !pwInput) return;

  const email = emailInput.value.trim();
  const pw = pwInput.value;

  clearFieldError(emailInput);
  clearFieldError(pwInput);

  let hasError = false;

  if (!email) { showFieldError(emailInput, 'Email is required'); hasError = true; }
  if (!pw) { showFieldError(pwInput, 'Password is required'); hasError = true; }
  if (hasError) return;

  const btn = document.getElementById('loginBtn');
  btn.textContent = 'SIGNING IN…';
  btn.disabled = true;

  setTimeout(() => {
    if (!checkCredentials(email, pw)) {
      showFieldError(emailInput, 'Incorrect email address');
      showFieldError(pwInput, 'Incorrect password');
      btn.textContent = 'Sign In';
      btn.disabled = false;
      return;
    }
    btn.textContent = '✓  WELCOME BACK!';
    btn.style.background = '#40c060';
    
    localStorage.setItem("user", JSON.stringify({ name: "Juan dela Cruz", email: email, phone: "0912-345-6789" }));
    localStorage.setItem("jwt_token", "active_session");
    
    setTimeout(() => { window.location.href = 'account.php'; }, 1000);
  }, 1200);
}

function handleRegister() {
  const fname = document.getElementById('r-fname').value.trim();
  const email = document.getElementById('r-email').value.trim();
  const phone = document.getElementById('r-phone').value.trim();
  const pw    = document.getElementById('r-pw').value;
  const pw2   = document.getElementById('r-pw2').value;
  const terms = document.getElementById('terms').checked;

  const passwordRule = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/;
  const emailRule    = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const phoneRule    = /^9\d{9}$/;

  if (!fname || !email || !phone || !pw) {
    showToast("Please fill in required fields.", "#e8a020"); return;
  }
  if (!emailRule.test(email)) {
    showToast("Please enter a valid email address.", "#e84040"); return;
  }
  if (!phoneRule.test(phone)) {
    showToast("Please enter a valid Philippine phone number.", "#e84040"); return;
  }
  if (!passwordRule.test(pw)) {
    showToast("Password must be 8+ characters with a letter, number, and symbol.", "#e84040"); return;
  }
  if (pw !== pw2) {
    showToast("Passwords do not match.", "#e84040"); return;
  }
  if (!terms) {
    showToast("You must agree to the terms.", "#e84040"); return;
  }

  const btn = document.getElementById('regBtn');
  btn.textContent = 'CREATING ACCOUNT…';
  btn.disabled = true;

  setTimeout(() => {
    btn.textContent = 'ACCOUNT CREATED!';
    btn.style.background = '#40c060';
    showToast('Account created! Welcome to EJR Garage.');
    
    setTimeout(() => {
      showToast('Redirecting to login…', '#40c060');
      window.location.href = 'login.php';
    }, 2000);
  }, 1600);
}

function logout() {
  localStorage.removeItem("user");
  localStorage.removeItem("jwt_token");
  window.location.href = "index.php";
}

function updateStrength(value) {
  const requirements = [
    { id: 'req-length', rule: val => val.length >= 8 },
    { id: 'req-uppercase', rule: val => /[A-Z]/.test(val) },
    { id: 'req-lowercase', rule: val => /[a-z]/.test(val) },
    { id: 'req-number', rule: val => /[0-9]/.test(val) },
    { id: 'req-symbol', rule: val => /[^A-Za-z0-9]/.test(val) },
  ];

  requirements.forEach(req => {
    const el = document.getElementById(req.id);
    if(el) {
        if (req.rule(value)) { el.classList.add('valid'); el.classList.remove('invalid'); } 
        else { el.classList.add('invalid'); el.classList.remove('valid'); }
    }
  });

  const fill  = document.getElementById('strengthFill');
  const label = document.getElementById('strengthLabel');

  let score = 0;
  if (value.length >= 8)           score++;
  if (/[A-Z]/.test(value))         score++;
  if (/[0-9]/.test(value))         score++;
  if (/[^A-Za-z0-9]/.test(value))  score++;

  const colors = ['#e84040', '#e8a020', '#f5c050', '#40c060'];
  const labels = ['Weak', 'Fair', 'Good', 'Strong'];
  const idx = Math.max(0, score - 1);

  if(fill) {
      fill.style.width = (score * 25) + '%';
      fill.style.background = colors[idx] || 'rgba(255,255,255,.1)';
  }
  if(label) {
      label.textContent = value.length ? (labels[idx] || 'Very Weak') : 'Password strength';
      label.style.color = value.length ? (colors[idx] || 'var(--steel)') : 'var(--steel)';
  }
}

// ==========================================
// 3. ON PAGE LOAD (Booking Logic & Header)
// ==========================================
document.addEventListener("DOMContentLoaded", function () {
  


  // --- A. SERVICE SELECTION PAGE (booking.php) ---
  const services = document.querySelectorAll(".service-item");
  const nextBtn = document.querySelector(".btn-next");
  const serviceError = document.getElementById("serviceError");
  const serviceLimitError = document.getElementById("serviceLimitError");
  let selectedServices = [];

  if (services.length > 0 && nextBtn && !document.getElementById("vehicleForm")) {
    
    // Create the Popup Card Notification dynamically
    let authPopup = document.createElement("div");
    authPopup.id = "authPopup";
    authPopup.style.display = "none";
    authPopup.style.position = "fixed";
    authPopup.style.top = "50%";
    authPopup.style.left = "50%";
    authPopup.style.transform = "translate(-50%, -50%)";
    authPopup.style.zIndex = "9999";
    authPopup.style.width = "90%";
    authPopup.style.maxWidth = "420px";
    authPopup.style.background = "#ffffff";
    authPopup.style.border = "1px solid #e3e8ef";
    authPopup.style.borderRadius = "16px";
    authPopup.style.padding = "40px 30px";
    authPopup.style.boxShadow = "0 20px 50px rgba(0,0,0,0.15)";
    authPopup.style.textAlign = "center";
    
    // The design inside the popup
    authPopup.innerHTML = `
      <button onclick="document.getElementById('authPopup').style.display='none'" style="position: absolute; top: 15px; right: 20px; background: none; border: none; font-size: 28px; cursor: pointer; color: #a0aab5; line-height: 1; padding: 0;">&times;</button>
      <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(230, 36, 41, 0.1); color: #e62429; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; font-size: 32px;">
        <i class="bi bi-lock-fill"></i>
      </div>
      <h3 style="font-family: 'Rajdhani', sans-serif; font-weight: 700; color: #1e2a36; margin-bottom: 10px; font-size: 1.8rem; text-transform: uppercase; letter-spacing: 1px;">Access Denied</h3>
      <p style="color: #5f6b7a; margin-bottom: 30px; font-size: 0.95rem; line-height: 1.6;">Please log in or create an account to secure your appointment schedule.</p>
      <div style="display: flex; gap: 12px; flex-direction: column;">
        <a href="login.php" class="btn" style="width: 100%; display: block;">Login Here</a>
        <a href="registration.php" class="btn-outline-light-custom" style="width: 100%; display: block; text-align: center;">Register Now</a>
      </div>
    `;
    
    // Attach the popup directly to the body so it floats above everything
    document.body.appendChild(authPopup);

    // Select/Deselect Services
    services.forEach(service => {
      service.addEventListener("click", function () {
        const serviceName = this.textContent.trim();
        if (serviceError) serviceError.style.display = "none";
        if (serviceLimitError) serviceLimitError.style.display = "none";
        if (authPopup) authPopup.style.display = "none";
        services.forEach(item => item.classList.remove("input-error"));

        if (this.classList.contains("selected")) {
          this.classList.remove("selected");
          selectedServices = selectedServices.filter(s => s !== serviceName);
          return;
        }
        if (selectedServices.length >= 5) {
          if (serviceLimitError) serviceLimitError.style.display = "block";
          return;
        }
        this.classList.add("selected");
        selectedServices.push(serviceName);
      });
    });

    // Handle "Next" Button
    nextBtn.addEventListener("click", function (e) {
      e.preventDefault(); 
      
      if (serviceError) serviceError.style.display = "none";
      if (serviceLimitError) serviceLimitError.style.display = "none";
      if (authPopup) authPopup.style.display = "none";
      
      // Validation 1: At least one service selected
      if (selectedServices.length === 0) {
        services.forEach(item => item.classList.add("input-error"));
        if (serviceError) serviceError.style.display = "block";
        return;
      }

      // Validation 2: Show Popup if not logged in
      if (!isLoggedIn()) {
         if (authPopup) authPopup.style.display = "block";
         return; 
      }

      services.forEach(item => item.classList.remove("input-error"));
      localStorage.setItem("selectedServices", JSON.stringify(selectedServices));
      window.location.href = "booking-details.php";
    });
  }

  // --- B. VEHICLE DETAILS PAGE (booking-details.php) ---
  const vehicleForm = document.getElementById("vehicleForm");
  const errorMsg = document.getElementById("errorMsg");
  const yearSelect = document.getElementById("year");

  // Populate Year Dropdown automatically
  if (yearSelect) {
    const currentYear = new Date().getFullYear();
    for (let year = currentYear; year >= 1990; year--) {
      const option = document.createElement("option");
      option.value = year;
      option.textContent = year;
      yearSelect.appendChild(option);
    }
  }

  // Handle Details Form Submission securely
  if (vehicleForm) {
    if (!isLoggedIn()) {
        window.location.href = "login.php"; 
    }

    vehicleForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const brand = document.getElementById("brand").value.trim();
      const model = document.getElementById("model").value.trim();
      const year = document.getElementById("year").value.trim();
      const plate = document.getElementById("plate").value.trim();
      const transmission = document.getElementById("transmission").value.trim();
      const fuel = document.getElementById("fuel").value.trim();

      if (!brand || !model || !year || !plate || !transmission || !fuel) {
        if (errorMsg) {
            errorMsg.style.display = "block";
        } else {
            alert("Please complete all vehicle details before continuing.");
        }
        return;
      }
      
      if (errorMsg) errorMsg.style.display = "none";
      const vehicleDetails = { brand, model, year, plate, transmission, fuel };
      localStorage.setItem("vehicleDetails", JSON.stringify(vehicleDetails));
      window.location.href = "booking-confirmation.php";
    });
  }
});

// ==========================================
// --- SESSION WATCH ---
// ==========================================
// startSessionWatch() is defined in auth.js with the full implementation
// (15s initial delay, 30s interval, consecutive-failure guard, reason-aware popup).
// Do NOT redefine it here — just auto-start it when the user is logged in.

// Auto-start on every page if logged in
document.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('jwt_token')) {
        if (typeof startSessionWatch === 'function') startSessionWatch();
    }
});