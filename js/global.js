// Don't touch this line
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
// 1. PAGE LOAD & USER SESSION LOGIC
// ==========================================
document.addEventListener("DOMContentLoaded", function () {
    const accountBtn = document.getElementById("accountBtn");
    const user = JSON.parse(localStorage.getItem("user"));
    if (user && accountBtn) {
        accountBtn.textContent = "My Account";
        accountBtn.href = "account.php";
    } else if (accountBtn) {
        accountBtn.textContent = "Get Started";
        accountBtn.href = "registration.php";
    }
});

// ==========================================
// 2. GLOBAL UTILITIES
// ==========================================
function logout() {
    localStorage.clear();

    window.location.href = '/logout.php';
}

function togglePw(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector("i");
    const isHidden = input.type === "password";
    input.type = isHidden ? "text" : "password";
    icon.classList.toggle("bi-eye", !isHidden);
    icon.classList.toggle("bi-eye-slash", isHidden);
}

function showToast(message, color) {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.style.background = color || '#40c060';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3200);
}

// ==========================================
// 3. PAGE CLEANUP (Form Hard Reset)
// ==========================================
function clearAllInputs() {
    const inputs = document.querySelectorAll('input[type="email"], input[type="password"], input[type="text"]');
    inputs.forEach(input => {
        if (input.type === "hidden" || input.id === "chatInput") return;
        input.value = '';
        input.style.border = '';
    });

    const errorMessages = document.querySelectorAll('.field-error, #forgotError');
    errorMessages.forEach(error => {
        if (error) {
            error.style.display = 'none';
            error.textContent = '';
        }
    });

    const actionButtons = document.querySelectorAll('.btn');
    actionButtons.forEach(btn => {
        btn.disabled = false;
        if (btn.style.background === "rgb(64, 192, 96)" || btn.style.background === "#40c060") {
            btn.style.background = "";
            btn.style.backgroundImage = "";
            if (btn.id === "loginBtn") btn.textContent = "SIGN IN";
            if (btn.id === "registerBtn") btn.textContent = "CREATE ACCOUNT";
        }
    });
}

window.addEventListener('load', clearAllInputs);
window.addEventListener('pageshow', clearAllInputs);

// ==========================================
// 4. FORGOT PASSWORD MODAL LOGIC
// ==========================================
function openForgotModal() {
    let modal = document.getElementById('forgotModal');
    if (!modal) {
        modal = document.createElement("div");
        modal.id = "forgotModal";
        modal.style.cssText = "display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); align-items: center; justify-content: center;";
        modal.innerHTML = `
            <div style="background: #ffffff; border-radius: 16px; width: 90%; max-width: 400px; padding: 40px 30px; text-align: center; position: relative;">
                <button onclick="closeForgotModal()" style="position: absolute; top: 15px; right: 20px; background: none; border: none; font-size: 28px; cursor: pointer; color: #a0aab5;">&times;</button>
                <h3 style="font-family: 'Rajdhani', sans-serif; font-weight: 700; color: #1e2a36; margin-bottom: 10px; text-transform: uppercase;">Reset Password</h3>
                <p style="color: #5f6b7a; margin-bottom: 25px; font-size: 0.9rem;">Enter your email to verify your account.</p>
                <div style="text-align: left; margin-bottom: 20px;">
                    <label style="font-size: 0.7rem; font-weight: 700; color: #5f6b7a; text-transform: uppercase; display: block; margin-bottom: 8px;">Email Address</label>
                    <input type="email" id="resetEmail" placeholder="juan@example.com" style="background: #f4f6f8; border: 1px solid #e3e8ef; border-radius: 8px; padding: 12px; width: 100%; outline: none;">
                    <div id="forgotError" style="color: #e62429; font-size: 0.75rem; font-weight: 600; margin-top: 5px; display: none;"></div>
                </div>
                <button id="resetSubmitBtn" class="btn" onclick="handleResetRequest(event)" style="width: 100%; font-weight: 700; text-transform: uppercase; cursor: pointer; transition: background 0.3s; background-image: linear-gradient(45deg, #e62429, #ff6b00); color: white; border: none; padding: 12px; border-radius: 8px;">
                    Send Reset Link
                </button>
            </div>
        `;
        document.body.appendChild(modal);
    }
    modal.style.display = "flex";
}

function closeForgotModal() {
    const modal = document.getElementById('forgotModal');
    if (modal) {
        modal.style.display = "none";
        const emailInput = document.getElementById('resetEmail');
        const errorDisplay = document.getElementById('forgotError');
        const btn = document.getElementById('resetSubmitBtn');
        if (emailInput) { emailInput.value = ""; emailInput.style.border = "1px solid #e3e8ef"; }
        if (errorDisplay) { errorDisplay.style.display = "none"; errorDisplay.textContent = ""; }
        if (btn) { btn.textContent = "SEND RESET LINK"; btn.disabled = false; btn.style.background = ""; btn.style.backgroundImage = "linear-gradient(45deg, #e62429, #ff6b00)"; }
    }
}

window.addEventListener("click", (e) => {
    const modal = document.getElementById("forgotModal");
    if (e.target === modal) closeForgotModal();
});

async function handleResetRequest(event) {
    if (event) event.preventDefault();
    const emailInput = document.getElementById('resetEmail');
    const errorDisplay = document.getElementById('forgotError');
    const btn = document.getElementById('resetSubmitBtn');
    if (!emailInput || !btn) return;
    const email = emailInput.value.trim();
    if (errorDisplay) { errorDisplay.style.display = "none"; errorDisplay.textContent = ""; }
    emailInput.style.border = "1px solid #e3e8ef";
    if (!email) { showToast("Please enter your email address.", "#e8a020"); return; }
    btn.textContent = "CHECKING...";
    btn.disabled = true;
    try {
        const response = await fetch('api/check-email.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email })
        });
        const data = await response.json();
        if (data.status === 'success') {
            btn.textContent = "RESET LINK SENT";
            btn.style.background = "#40c060";
            btn.style.backgroundImage = "none";
            showToast("Success! Check your email inbox.", "#40c060");
        } else {
            if (errorDisplay) { errorDisplay.textContent = data.message; errorDisplay.style.display = "block"; }
            emailInput.style.border = "1.5px solid #e62429";
            btn.textContent = "SEND RESET LINK";
            btn.disabled = false;
        }
    } catch (error) {
        showToast("Server error. Check your connection.", "#e62429");
        btn.textContent = "SEND RESET LINK";
        btn.disabled = false;
    }
}

// ==========================================
// 5. PRESS "ENTER" TO SUBMIT FORMS
// ==========================================
document.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
        const activeElem = document.activeElement;
        if (activeElem.id === "chatInput") return; // handled by footer.php

        if (activeElem.tagName === "INPUT") {
            const forgotModal = document.getElementById("forgotModal");
            const resetBtn = document.getElementById("resetSubmitBtn");
            const loginBtn = document.getElementById("loginBtn");
            const registerBtn = document.getElementById("regBtn");

            if (forgotModal && forgotModal.style.display === "flex") {
                if (resetBtn) { event.preventDefault(); resetBtn.click(); }
            } else if (loginBtn) {
                event.preventDefault();
                loginBtn.click();
            } else if (registerBtn) {
                event.preventDefault();
                registerBtn.click();
            }
        }
    }
});