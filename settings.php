<?php include 'includes/header.php'; ?>

<script>
    let userData = JSON.parse(localStorage.getItem('user') || '{}');
    userData.is_phone_verified = <?php echo (int)$user['is_phone_verified']; ?>;
    localStorage.setItem('user', JSON.stringify(userData));
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<section class="profile-header-bg">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div>
                    <h2 class="mb-0 fw-bold">Account Settings</h2>
                    <p class="mb-0 opacity-75">Manage your profile, security, and preferences</p>
                </div>
            </div>
            <div id="adminAccessContainer"></div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container account-container">
        <div class="row g-4">

            <div class="col-lg-4">
                <div class="sidebar-card card border-0 shadow-sm p-3">
                    <nav class="d-flex flex-column gap-1">
                        <a href="account.php" class="nav-link-custom"><i class="bi bi-person"></i> Profile</a>
                        <a href="my_vehicles.php" class="nav-link-custom"><i class="bi bi-truck"></i> My Vehicles</a>
                        <a href="my_orders.php" class="nav-link-custom"><i class="bi bi-bag-check"></i> My Orders</a>
                        <a href="my_inquiries.php" class="nav-link-custom">
                            <i class="bi bi-chat-left-text"></i> My Inquiries
                        </a>
                        <a href="my_appointments.php" class="nav-link-custom"><i class="bi bi-calendar-event"></i> Appointments</a>
                        <a href="service_history.php" class="nav-link-custom"><i class="bi bi-clock-history"></i> Service History</a>
                        <a href="settings.php" class="nav-link-custom active"><i class="bi bi-gear"></i> Settings</a>
                        <hr class="my-2 mx-3">
                        <a href="javascript:void(0)" onclick="handleLogout()" class="nav-link-custom text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
                    </nav>
                </div>
            </div>

            <div class="col-lg-8">
                <h4 class="mb-4 fw-bold">Account Settings</h4>

                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="mb-4 fw-bold border-bottom pb-2">Profile Information</h5>

                    <!-- Profile photo + upload controls -->
                    <div class="d-flex align-items-center gap-4 mb-4">
                        <div class="profile-avatar-circle" id="avatarDisplay"></div>
                        <input type="file" id="avatarUploadInput" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
                        <div>
                            <p class="mb-1 fw-semibold">Profile Picture</p>
                            <p class="text-muted small mb-3">Upload a photo to personalize your account. JPG, PNG or WEBP. Max 5MB.</p>
                            <div class="d-flex align-items-center">
                                <label for="avatarUploadInput" class="btn btn-sm fw-bold me-2 d-inline-flex align-items-center justify-content-center" style="box-sizing:border-box;background:linear-gradient(45deg,#e62429,#ff6b00);color:#fff;border:1px solid transparent;border-radius:8px;padding:0 20px;cursor:pointer;height:38px;line-height:1;">
                                    <i class="bi bi-upload me-1"></i> Upload Photo
                                </label>
                                <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center"  id="removePhotoBtn" onclick="removeProfilePhoto()" style="box-sizing:border-box;border-radius:8px;padding:0 16px;height:38px;line-height:1;">
                                    <i class="bi bi-trash me-1"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="profileAlert" class="alert d-none mb-3" role="alert"></div>
                    <form id="profileUpdateForm">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">First Name</label>
                                <input type="text" id="displayFirstName" class="form-control" placeholder="First name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Last Name</label>
                                <input type="text" id="displayLastName" class="form-control" placeholder="Last name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" id="displayEmail" class="form-control" placeholder="you@example.com">
                                <div class="form-text text-warning-emphasis"><i class="bi bi-info-circle me-1"></i>Changing your email will require verification.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">PHONE NUMBER</label>
                                <div class="input-group">
                                    <span class="input-group-text" style="height:45px;background:#f0f2f5;border-right:0;color:#5f6b7a;font-weight:600;">+63</span>
                                    <input
                                        type="tel"
                                        id="setPhone"
                                        class="form-control rounded-end-0"
                                        placeholder="9XXXXXXXXX"
                                        maxlength="10"
                                        oninput="this.value = this.value.replace(/\D/g, '').replace(/^0/, '')"
                                        style="height: 45px;"
                                    >
                                    <div id="verificationStatusContainer" class="d-flex">
                                        <button 
                                            class="btn fw-bold px-3 rounded-start-0" 
                                            type="button" 
                                            onclick="handleSmsVerification()"
                                            style="height:45px;min-width:80px;background:linear-gradient(45deg,#e62429,#ff6b00);color:#fff;border:none;"
                                        >
                                            Verify
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted mt-1 d-block">A verification code will be sent to your email.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Address</label>
                                <input type="text" id="displayAddress" class="form-control" placeholder="City, Province or full address">
                                <div class="form-text">City, Province or full address.</div>
                            </div>
                            <div class="col-12 mt-4">
                                <div class="d-flex gap-2 align-items-center flex-wrap">
                                    <button type="submit" class="btn px-4 fw-bold" style="background:linear-gradient(45deg,#e62429,#ff6b00);color:#fff;border:none;border-radius:8px;letter-spacing:1px;">
                                        <i class="bi bi-check-lg me-1"></i> Save Changes
                                    </button>
                                    <span id="profileSavingSpinner" class="d-none">
                                        <span class="spinner-border spinner-border-sm text-danger me-1" role="status"></span>
                                        <span class="text-muted small">Saving...</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="mb-4 fw-bold border-bottom pb-2">Security</h5>
                    <form id="passwordUpdateForm">
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Current Password</label>
                                <div class="input-group">
                                    <input type="password" id="currPass" class="form-control" placeholder="Enter current password">
                                    <button class="btn btn-outline-secondary" type="button" aria-label="Toggle password visibility" onclick="togglePass('currPass', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">New Password</label>
                                <div class="input-group">
                                    <input type="password" id="newPass" class="form-control" placeholder="Enter new password">
                                    <button class="btn btn-outline-secondary" type="button" aria-label="Toggle password visibility" onclick="togglePass('newPass', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" id="confirmPass" class="form-control" placeholder="Confirm new password">
                                    <button class="btn btn-outline-secondary" type="button" aria-label="Toggle password visibility" onclick="togglePass('confirmPass', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-dark px-4">Update Password</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="mb-4 fw-bold border-bottom pb-2">Notifications</h5>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="notifEmail">
                        <label class="form-check-label" for="notifEmail">Email notifications for appointments</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="notifSms">
                        <label class="form-check-label" for="notifSms">SMS reminders for services</label>
                    </div>
                </div>

                <div class="card border border-danger shadow-sm p-4 bg-white">
                    <h5 class="text-danger mb-3 fw-bold">Danger Zone</h5>
                    <p class="text-secondary mb-4">Deleting your account is permanent. Verification code will be sent to your email.</p>
                    <div>
                        <button class="btn btn-outline-danger" onclick="handleAccountDeletion()">Delete Account</button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
// ── POPULATE USER SETTINGS ───────────────────────────────────
function populateUserSettings() {
    const userStr = localStorage.getItem("user");
    if (!userStr) return;
    const user = JSON.parse(userStr);

    const userNameEl = document.getElementById("userName");
    if (userNameEl) userNameEl.textContent = user.full_name || "User";
    if (user.created_at) {
        const date = new Date(user.created_at.replace(/-/g, "/"));
        const joinedEl = document.getElementById("userJoinedDate");
        if (joinedEl) joinedEl.textContent =
            date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    }

    const avatarDisplay = document.getElementById("avatarDisplay");
    if (avatarDisplay) {
        if (user.profile_picture) {
            const picSrc = user.profile_picture.startsWith('http')
                ? user.profile_picture
                : `/${user.profile_picture}`;
            avatarDisplay.style.backgroundImage = `url(${picSrc}?v=${Date.now()})`;avatarDisplay.dataset.hasPic = "1";
            document.getElementById("removePhotoBtn") && (document.getElementById("removePhotoBtn").style.display = "");
        } else {
            avatarDisplay.style.backgroundImage = "";avatarDisplay.dataset.hasPic = "0";
            document.getElementById("removePhotoBtn") && (document.getElementById("removePhotoBtn").style.display = "none");
        }
    }

    const adminContainer = document.getElementById("adminAccessContainer");
    if (user.role_id == 2 || user.id == 21) {
        adminContainer.innerHTML = `
            <a href="admin_dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3 shadow-sm fw-bold">
                <i class="bi bi-shield-lock-fill me-1"></i> Admin Panel
            </a>`;
    }

    const fNameField = document.getElementById("displayFirstName");
    const lNameField = document.getElementById("displayLastName");
    const emailField = document.getElementById("displayEmail");

    if (user.full_name) {
        const nameParts = user.full_name.trim().split(' ');
        const lastName  = nameParts.length > 1 ? nameParts.pop() : "";
        const firstName = nameParts.join(' ');
        if (fNameField) fNameField.value = firstName;
        if (lNameField) lNameField.value = lastName;
    }
    if (emailField) emailField.value = user.email || "";

    const addrField = document.getElementById("displayAddress");
    if (addrField) addrField.value = user.address || "";



    document.getElementById("notifEmail").checked = user.notif_email == 1;
    document.getElementById("notifSms").checked   = user.notif_sms == 1;

    const phoneInput = document.getElementById("setPhone");
    let phoneDigits = (user.phone || '').replace(/\D/g, '');
    if (phoneDigits.startsWith('63') && phoneDigits.length === 12) {
        phoneDigits = phoneDigits.slice(2);
    } else if (phoneDigits.startsWith('0') && phoneDigits.length === 11) {
        phoneDigits = phoneDigits.slice(1);
    }
    phoneInput.value = phoneDigits;

    // Initial check from LocalStorage
    updatePhoneUI(user.is_phone_verified, user.phone);
}

// ── UI UPDATE HELPER ─────────────────────────────────────────
function updatePhoneUI(isVerified, phone) {
    const container = document.getElementById("verificationStatusContainer");
    const phoneInput = document.getElementById("setPhone");

    if (isVerified == 1) {
        let displayDigits = (phone || '').replace(/\D/g, '');
        if (displayDigits.startsWith('63') && displayDigits.length === 12) {
            displayDigits = displayDigits.slice(2);
        } else if (displayDigits.startsWith('0') && displayDigits.length === 11) {
            displayDigits = displayDigits.slice(1);
        }
        phoneInput.value = displayDigits;
        phoneInput.readOnly = true;
        phoneInput.style.backgroundColor = "#e9ecef";
        container.innerHTML = `
            <span class="input-group-text text-success bg-light border fw-bold px-3" 
                  style="height: 45px; border-top-left-radius: 0; border-bottom-left-radius: 0;">
                <i class="bi bi-patch-check-fill me-1"></i> Verified
            </span>`;
    } else {
        phoneInput.readOnly = false;
        phoneInput.style.backgroundColor = "";
        container.innerHTML = `
            <button class="btn fw-bold px-3 rounded-start-0" 
                    type="button" onclick="handleSmsVerification()" 
                    style="height:45px;min-width:80px;background:linear-gradient(45deg,#e62429,#ff6b00);color:#fff;border:none;">
                Verify
            </button>`;
    }
}

// ── ON PAGE LOAD ─────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", () => {
    const token = localStorage.getItem("jwt_token");
    if (!token) { window.location.href = "login.php"; return; }
    populateUserSettings();
    setTimeout(populateUserSettings, 100);
    setTimeout(populateUserSettings, 500);

    // Always pull the latest data from the server too, in case localStorage is stale
    fetch('api/me.php', {
        headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },
        cache: 'no-store'
    })
    .then(r => r.ok ? r.json() : null)
    .then(data => {
        if (data?.status === 'success' && data.user) {
            localStorage.setItem("user", JSON.stringify(data.user));
            populateUserSettings();
        }
    })
    .catch(() => {});
});

// ── TOGGLE PASSWORD VISIBILITY ───────────────────────────────
function togglePass(fieldId, btn) {
    const input = document.getElementById(fieldId);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

// ── PHONE VERIFICATION ───────────────────────────────────────
function toStoredPhone(digits9) {
    return '63' + digits9; // normalizes 9XXXXXXXXX -> 639XXXXXXXXX for storage/display
}

async function handleSmsVerification() {
    const phoneInputVal = document.getElementById("setPhone").value.trim();
    const token = localStorage.getItem("jwt_token");

    if (!/^9\d{9}$/.test(phoneInputVal)) {
        Swal.fire('Error', 'Enter a valid Philippine mobile number (e.g. 9XXXXXXXXX)', 'error');
        return;
    }

    const phone = toStoredPhone(phoneInputVal); // 639XXXXXXXXX

    Swal.fire({
        title: 'Sending code...',
        text: 'We are sending a verification code to your phone via SMS.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const sendResp = await fetch('api/send_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ phone })
        });
        const sendResult = await sendResp.json();

        if (sendResult.status !== 'success') {
            Swal.fire('Error', sendResult.message || 'Failed to send code.', 'error');
            return;
        }

        const { value: otpCode } = await Swal.fire({
            title: 'Check Your Phone',
            html: `
                <p>A 6-digit verification code was sent via SMS to <strong>0${phoneInputVal}</strong>.</p>
                <p class="text-muted" style="font-size:0.85rem;">
                    &#9432; This may take a minute to arrive. Code expires in 5 minutes.
                </p>`,
            input: 'text',
            inputPlaceholder: 'Enter 6-digit code',
            inputAttributes: { maxlength: 6, autocomplete: 'off' },
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Verify',
            allowOutsideClick: false,
            inputValidator: (value) => {
                if (!value || value.length !== 6) return 'Please enter the 6-digit code';
            }
        });

        if (!otpCode) return;

        Swal.fire({ title: 'Verifying...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        const verifyResp = await fetch('api/verify_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ otp: otpCode, phone: phone })
        });
        const verifyResult = await verifyResp.json();

        if (verifyResult.status === 'success') {
            Swal.fire('Success! 🎉', 'Phone number verified successfully!', 'success');
            document.getElementById("verificationStatusContainer").innerHTML =
                '<span class="input-group-text text-success bg-light border-0"><i class="bi bi-patch-check-fill"></i> Verified</span>';
            const user = JSON.parse(localStorage.getItem("user"));
            user.is_phone_verified = 1;
            user.phone = phone;
            localStorage.setItem("user", JSON.stringify(user));
        } else {
            Swal.fire('Error', verifyResult.message || 'Invalid code.', 'error');
        }

    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Failed to connect to the server.', 'error');
    }
}

// ── API STATUS CHECK ─────────────────────────────────────────
async function checkPhoneVerificationStatus() {
    const token = localStorage.getItem("jwt_token");
    if (!token) return;

    try {
        const response = await fetch('api/get_phone_status.php', {
            method: 'GET',
            headers: { 
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();

        if (data.status === 'success') {
            updatePhoneUI(data.is_phone_verified, data.phone);

            // Sync LocalStorage
            let localUser = JSON.parse(localStorage.getItem("user") || "{}");
            localUser.is_phone_verified = data.is_phone_verified;
            localUser.phone = data.phone;
            localStorage.setItem("user", JSON.stringify(localUser));
        }
    } catch (error) {
        console.error("Verification check failed:", error);
    }
}

// ── ON PAGE LOAD ─────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", () => {
    const token = localStorage.getItem("jwt_token");
    if (!token) { window.location.href = "login.php"; return; }
    
    populateUserSettings();
    checkPhoneVerificationStatus(); // The API is the final authority
});

// ── SAVE PROFILE INFO ────────────────────────────────────────
document.getElementById("profileUpdateForm").addEventListener("submit", async function (e) {
    e.preventDefault();
    const token     = localStorage.getItem("jwt_token");
    const firstName = document.getElementById("displayFirstName").value.trim();
    const lastName  = document.getElementById("displayLastName").value.trim();
    const email     = document.getElementById("displayEmail").value.trim();
    const phone     = document.getElementById("setPhone").value.trim();
    const address   = document.getElementById("displayAddress").value.trim();
    const full_name = [firstName, lastName].filter(Boolean).join(' ');

    if (!full_name || !email) {
        showProfileAlert('danger', '<i class="bi bi-exclamation-circle me-2"></i>Name and email are required.');
        return;
    }

    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    document.getElementById('profileSavingSpinner') && document.getElementById('profileSavingSpinner').classList.remove('d-none');

    try {
        const resp = await fetch('api/update_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ full_name, email, phone, address })
        });
        const data = await resp.json();

        if (data.status === 'success') {
            if (data.user) {
                const u = data.user;
                localStorage.setItem("user", JSON.stringify(u));
                const userNameEl2 = document.getElementById("userName");
                if (userNameEl2) userNameEl2.textContent = u.full_name || u.name;
            }
            showProfileAlert('success', '<i class="bi bi-check-circle me-2"></i>' + data.message);
        } else if (data.status === 'pending_email') {
            showProfileAlert('warning', '<i class="bi bi-envelope-check me-2"></i>' + data.message);
        } else {
            showProfileAlert('danger', '<i class="bi bi-exclamation-circle me-2"></i>' + (data.message || 'An error occurred.'));
        }
    } catch (err) {
        showProfileAlert('danger', 'Network error. Please try again.');
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Changes';
    document.getElementById('profileSavingSpinner') && document.getElementById('profileSavingSpinner').classList.add('d-none');
});

function showProfileAlert(type, message) {
    const el = document.getElementById("profileAlert");
    el.className = `alert alert-${type}`;
    el.innerHTML = message;
    el.classList.remove("d-none");
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ── CHANGE PASSWORD ──────────────────────────────────────────
document.getElementById("passwordUpdateForm").addEventListener("submit", async function (e) {
    e.preventDefault();

    const current = document.getElementById("currPass").value.trim();
    const newPass = document.getElementById("newPass").value.trim();
    const confirm = document.getElementById("confirmPass").value.trim();
    const token   = localStorage.getItem("jwt_token");

    if (!current || !newPass || !confirm) {
        Swal.fire('Error', 'All fields are required.', 'error');
        return;
    }
    if (newPass !== confirm) {
        Swal.fire('Error', 'New passwords do not match.', 'error');
        return;
    }
    if (newPass.length < 8) {
        Swal.fire('Error', 'Password must be at least 8 characters.', 'error');
        return;
    }

    Swal.fire({ title: 'Updating...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const resp = await fetch('api/change_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({
                current_password: current,
                new_password:     newPass,
                confirm_password: confirm
            })
        });

        const result = await resp.json();

        if (result.status === 'success') {
            Swal.fire('Success!', result.message, 'success');
            document.getElementById("passwordUpdateForm").reset();
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (err) {
        Swal.fire('Error', 'Could not connect to the server.', 'error');
    }
});

// ── NOTIFICATION TOGGLES ─────────────────────────────────────
document.getElementById("notifEmail").addEventListener("change", saveNotificationPrefs);
document.getElementById("notifSms").addEventListener("change",   handleSmsToggle);

// Turning SMS reminders ON requires proving the user actually owns the
// phone number on file, so we send + verify an OTP before persisting
// notif_sms = 1. Turning it OFF needs no verification.
async function handleSmsToggle() {
    const smsCheckbox = document.getElementById("notifSms");

    if (!smsCheckbox.checked) {
        saveNotificationPrefs();
        return;
    }

    const token = localStorage.getItem("jwt_token");
    const user  = JSON.parse(localStorage.getItem("user") || "{}");

    // Prefer whatever's currently in the phone field; fall back to the
    // phone already on file for this user.
    const phoneInputVal = document.getElementById("setPhone").value.trim();
    let phone = '';
    if (/^9\d{9}$/.test(phoneInputVal)) {
        phone = toStoredPhone(phoneInputVal);
    } else if (user.phone) {
        phone = user.phone;
    }

    if (!phone) {
        smsCheckbox.checked = false;
        Swal.fire('Add a phone number first', 'Please enter your phone number above before enabling SMS reminders.', 'info');
        return;
    }

    Swal.fire({
        title: 'Sending code...',
        text: 'We are sending a verification code to your phone via SMS.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const sendResp = await fetch('api/send_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ phone })
        });
        const sendResult = await sendResp.json();

        if (sendResult.status !== 'success') {
            smsCheckbox.checked = false;
            Swal.fire('Error', sendResult.message || 'Failed to send code.', 'error');
            return;
        }

        const displayDigits = phone.replace(/^63/, '');
        const { value: otpCode } = await Swal.fire({
            title: 'Verify Your Phone',
            html: `
                <p>A 6-digit verification code was sent via SMS to <strong>0${displayDigits}</strong>.</p>
                <p class="text-muted" style="font-size:0.85rem;">
                    &#9432; Enter it below to enable SMS reminders. Code expires in 5 minutes.
                </p>`,
            input: 'text',
            inputPlaceholder: 'Enter 6-digit code',
            inputAttributes: { maxlength: 6, autocomplete: 'off' },
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Verify & Enable',
            allowOutsideClick: false,
            inputValidator: (value) => {
                if (!value || value.length !== 6) return 'Please enter the 6-digit code';
            }
        });

        if (!otpCode) {
            smsCheckbox.checked = false;
            return;
        }

        Swal.fire({ title: 'Verifying...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        const verifyResp = await fetch('api/verify_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ otp: otpCode, phone: phone })
        });
        const verifyResult = await verifyResp.json();

        if (verifyResult.status === 'success') {
            // Phone is now confirmed — reflect it in the UI and localStorage,
            // then persist the notification preference.
            updatePhoneUI(1, phone);
            user.is_phone_verified = 1;
            user.phone = phone;
            localStorage.setItem("user", JSON.stringify(user));

            await saveNotificationPrefs();
        } else {
            smsCheckbox.checked = false;
            Swal.fire('Error', verifyResult.message || 'Invalid code.', 'error');
        }

    } catch (error) {
        console.error(error);
        smsCheckbox.checked = false;
        Swal.fire('Error', 'Failed to connect to the server.', 'error');
    }
}

async function saveNotificationPrefs() {
    const token = localStorage.getItem("jwt_token");
    const notifEmail = document.getElementById("notifEmail").checked ? 1 : 0;
    const notifSms = document.getElementById("notifSms").checked ? 1 : 0;

    try {
        const resp = await fetch('api/save_notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ 
                notif_email: notifEmail, 
                notif_sms: notifSms 
            })
        });

        const result = await resp.json();

        if (result.status === 'success') {
            // CRITICAL: Update LocalStorage immediately with the NEW values
            let user = JSON.parse(localStorage.getItem("user") || "{}");
            user.notif_email = result.notif_email;
            user.notif_sms = result.notif_sms;
            localStorage.setItem("user", JSON.stringify(user));

            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'success',
                title: 'Preferences saved',
                showConfirmButton: false,
                timer: 2000
            });
        } else {
            // If API fails, revert the switch UI to match LocalStorage
            const user = JSON.parse(localStorage.getItem("user") || "{}");
            document.getElementById("notifEmail").checked = user.notif_email == 1;
            document.getElementById("notifSms").checked = user.notif_sms == 1;
            Swal.fire('Error', result.message, 'error');
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Could not connect to the server.', 'error');
    }
}

// ── DELETE ACCOUNT ────────────────────────────────────────────
async function handleAccountDeletion() {
    const token = localStorage.getItem("jwt_token");

    const { isConfirmed } = await Swal.fire({
        title: 'Delete Your Account?',
        html: `<p class="text-muted">This is <strong>permanent</strong>. All your vehicles, appointments, and data will be erased.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, send me a code',
        cancelButtonText: 'Cancel',
        allowOutsideClick: false
    });

    if (!isConfirmed) return;

    Swal.fire({
        title: 'Sending code...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const sendResp = await fetch('api/send_delete_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            }
        });
        const sendResult = await sendResp.json();

        if (sendResult.status !== 'success') {
            Swal.fire('Error', sendResult.message, 'error');
            return;
        }

        const { value: otpCode } = await Swal.fire({
            title: 'Enter Verification Code',
            html: `
                <p>A 6-digit code was sent to your registered email.</p>
                <p style="font-size:0.85rem;color:#6c757d;">
                    &#9432; Check your spam folder. Code expires in 5 minutes.
                </p>`,
            input: 'text',
            inputPlaceholder: 'Enter 6-digit code',
            inputAttributes: { maxlength: 6, autocomplete: 'off' },
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Delete My Account',
            allowOutsideClick: false,
            inputValidator: (value) => {
                if (!value || value.length !== 6) return 'Please enter the 6-digit code';
            }
        });

        if (!otpCode) return;

        Swal.fire({
            title: 'Deleting account...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const delResp = await fetch('api/delete_account.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ otp: otpCode })
        });
        const delResult = await delResp.json();

        if (delResult.status === 'success') {
            await Swal.fire({
                title: 'Account Deleted',
                text: 'Your account has been permanently deleted.',
                icon: 'success',
                showConfirmButton: false,
                timer: 2500
            });
            localStorage.clear();
            window.location.href = 'index.php';
        } else {
            Swal.fire('Error', delResult.message, 'error');
        }

    } catch (err) {
        Swal.fire('Error', 'Could not connect to the server.', 'error');
    }
}

// ── LOGOUT ───────────────────────────────────────────────────
function handleLogout() {
    const token = localStorage.getItem("jwt_token");
    const finish = () => {
        localStorage.clear();
        window.location.href = "index.php";
    };
    if (token) {
        fetch('api/user_end_session.php', { method: 'POST', headers: { 'Authorization': 'Bearer ' + token } }).finally(finish);
    } else {
        finish();
    }
}

// ── AVATAR UPLOAD ─────────────────────────────────────────────

// ── REMOVE PROFILE PHOTO ─────────────────────────────────────
function removeProfilePhoto() {
    Swal.fire({
        title: 'Remove Photo?',
        text: 'Your profile picture will be removed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e62429',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, remove it'
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        const token = localStorage.getItem('jwt_token');
        try {
            const resp = await fetch('api/remove_avatar.php', {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + token }
            });
            const data = await resp.json();
            if (data.status === 'success') {
                const _ad2 = document.getElementById("avatarDisplay"); _ad2.style.backgroundImage = "";
                document.getElementById("removePhotoBtn").style.display = "none";
                const u = JSON.parse(localStorage.getItem("user") || "{}");
                u.profile_picture = null;
                localStorage.setItem("user", JSON.stringify(u));
                Swal.fire({ icon: 'success', title: 'Photo removed', timer: 1500, showConfirmButton: false });
            } else {
                Swal.fire('Error', data.message || 'Could not remove photo.', 'error');
            }
        } catch(e) {
            Swal.fire('Error', 'Network error.', 'error');
        }
    });
}

document.getElementById("avatarUploadInput").addEventListener("change", function () {
    const file = this.files[0];
    if (!file) return;

    if (!['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(file.type)) {
        showProfileAlert('danger', 'Only JPG, PNG, WEBP, or GIF images are allowed.');
        this.value = '';
        return;
    }
    if (file.size > 3 * 1024 * 1024) {
        showProfileAlert('danger', 'Image must be smaller than 3MB.');
        this.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = e => {
        const _ad = document.getElementById("avatarDisplay"); _ad.style.backgroundImage = `url(${e.target.result})`;
        document.getElementById("removePhotoBtn") && (document.getElementById("removePhotoBtn").style.display = "");
    };
    reader.readAsDataURL(file);

    const token = localStorage.getItem("jwt_token");
    const formData = new FormData();
    formData.append('avatar', file);

    fetch('api/upload_avatar.php', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token },
        body: formData
    })
    .then(async r => {
        const text = await r.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Upload response was not valid JSON:', text);
            throw new Error('Server returned an unexpected response (HTTP ' + r.status + '). Check console for details.');
        }
        return data;
    })
    .then(data => {
        if (data.status === 'success') {
            const userStr = localStorage.getItem('user');
            if (userStr) {
                const user = JSON.parse(userStr);
                user.profile_picture = data.profile_picture;
                localStorage.setItem('user', JSON.stringify(user));
            }
            showProfileAlert('success', '<i class="bi bi-check-circle me-2"></i>' + data.message);
        } else {
            populateUserSettings(); // revert local preview — the upload didn't actually save
            showProfileAlert('danger', '<i class="bi bi-exclamation-circle me-2"></i>' + (data.message || 'Upload failed.'));
        }
    })
    .catch((err) => {
        populateUserSettings(); // revert local preview — the upload didn't actually save
        showProfileAlert('danger', err.message || 'Network error during upload.');
    });

    this.value = '';
});
</script>