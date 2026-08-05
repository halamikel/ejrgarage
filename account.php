<?php
session_start();
$pageTitle = "My Account - EJR Garage";
include 'includes/header.php';
?>

<section class="profile-header-bg">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div>
                    <h2 class="mb-0 fw-bold">My Profile</h2>
                    <p class="mb-0 opacity-75">Member since <span id="userJoinedDate">...</span></p>
                </div>
            </div>
            <div id="adminAccessContainer"></div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container account-container">
        <div class="row g-4">

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="sidebar-card card border-0 shadow-sm p-3">
                    <nav class="d-flex flex-column gap-1">
                        <a href="account.php" class="nav-link-custom active">
                            <i class="bi bi-person"></i> Profile
                        </a>
                        <a href="my_vehicles.php" class="nav-link-custom">
                            <i class="bi bi-truck"></i> My Vehicles
                        </a>
                        <a href="my_orders.php" class="nav-link-custom">
                            <i class="bi bi-bag-check"></i> My Orders
                        </a>
                        <a href="my_inquiries.php" class="nav-link-custom">
                            <i class="bi bi-chat-left-text"></i> My Inquiries
                        </a>
                        <a href="my_appointments.php" class="nav-link-custom">
                            <i class="bi bi-calendar-event"></i> Appointments
                        </a>
                        <a href="service_history.php" class="nav-link-custom">
                            <i class="bi bi-clock-history"></i> Service History
                        </a>
                        <a href="settings.php" class="nav-link-custom">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                        <hr class="my-2 mx-3">
                        <a href="javascript:void(0)" onclick="handleLogout()" class="nav-link-custom text-danger">
                            <i class="bi bi-box-arrow-left"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Profile Card -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 p-md-5">

                    <!-- Profile photo + header row -->
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="profile-card-avatar" id="profileCardAvatar">
                                <img id="profileCardAvatarImg" alt="Profile photo" style="display:none;">
                                <i class="bi bi-person" id="profileCardAvatarIcon"></i>
                            </div>
                            <div>
                                <h4 class="fw-bold mb-0" id="profileCardName">Loading...</h4>
                                <small class="text-muted">Member since <span id="profileCardDate">...</span></small>
                            </div>
                        </div>
                        <a href="settings.php" class="btn btn-outline-edit">
                            <i class="bi bi-pencil me-1"></i> Edit Profile
                        </a>
                    </div>
                    <hr class="mb-4">

                    <!-- Alert banner -->
                    <div id="profileAlert" class="alert d-none mb-3" role="alert"></div>

                    <!-- View mode -->
                    <div id="viewMode">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="info-label text-muted small text-uppercase fw-bold"><i class="bi bi-person me-2"></i> Full Name</div>
                                <div class="info-value fw-semibold fs-5 mt-1" id="userNameBox">Loading...</div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label text-muted small text-uppercase fw-bold"><i class="bi bi-envelope me-2"></i> Email Address</div>
                                <div class="info-value fw-semibold fs-5 mt-1" id="userEmail">Loading...</div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label text-muted small text-uppercase fw-bold"><i class="bi bi-telephone me-2"></i> Phone Number</div>
                                <div class="info-value fw-semibold fs-5 mt-1" id="userPhone">Loading...</div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label text-muted small text-uppercase fw-bold"><i class="bi bi-geo-alt me-2"></i> Address</div>
                                <div class="info-value fw-semibold fs-5 mt-1" id="userAddress">Not Provided</div>
                            </div>
                        </div>


                    </div>

                </div><!-- /.card -->
            </div>

        </div>
    </div>
</section>

<script>
let _userData = {};

document.addEventListener("DOMContentLoaded", () => {
    const token   = localStorage.getItem("jwt_token");
    const userStr = localStorage.getItem("user");

    if (!token) { window.location.href = "login.php"; return; }

    if (userStr) {
        try { updateUI(JSON.parse(userStr)); } catch(e) {}
    }

    fetch('api/me.php', {
        headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' }
    })
    .then(r => r.ok ? r.json() : null)
    .then(data => {
        if (data?.status === 'success' && data.user) {
            updateUI(data.user);
            localStorage.setItem("user", JSON.stringify(data.user));
        }
    })
    .catch(() => {});
});

function updateUI(user) {
    _userData = user;
    const displayName = user.name || user.full_name || "EJR Customer";

    const userNameEl = document.getElementById("userName");
    if (userNameEl) userNameEl.textContent = displayName;
    document.getElementById("userNameBox").textContent = displayName;
    document.getElementById("userEmail").textContent   = user.email || "Not Provided";

    // Profile card avatar
    const cardAvatar = document.getElementById("profileCardAvatar");
    const cardAvatarImg = document.getElementById("profileCardAvatarImg");
    const cardAvatarIcon = document.getElementById("profileCardAvatarIcon");
    if (cardAvatar && cardAvatarImg && cardAvatarIcon) {
        if (user.profile_picture) {
            cardAvatarImg.onerror = () => {
                // The image failed to load (e.g. file missing on the server).
                // Fall back to the icon instead of letting the browser render
                // broken alt text inside the circular frame.
                cardAvatarImg.style.display = "none";
                cardAvatarIcon.style.display = "";
            };
            const picSrc = user.profile_picture.startsWith('http')
                ? user.profile_picture
                : `/${user.profile_picture}`;
            cardAvatarImg.src = `${picSrc}?v=${Date.now()}`;
            cardAvatarImg.style.display = "block";
            cardAvatarIcon.style.display = "none";
        } else {
            cardAvatarImg.removeAttribute("src");
            cardAvatarImg.style.display = "none";
            cardAvatarIcon.style.display = "";
        }
    }
    // Sync name + date in card header
    const profileCardName = document.getElementById("profileCardName");
    const profileCardDate = document.getElementById("profileCardDate");
    if (profileCardName) profileCardName.textContent = user.full_name || user.name || "";
    if (profileCardDate && user.created_at) {
        const d = new Date(user.created_at);
        profileCardDate.textContent = d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    }

    // Admin button
    const adminContainer = document.getElementById("adminAccessContainer");
    if (user.role_id == 2 || user.id == 21) {
        adminContainer.innerHTML = `
            <a href="admin_dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3 shadow-sm fw-bold">
                <i class="bi bi-shield-lock-fill me-1"></i> Admin Panel
            </a>`;
    }

    // Join date
    const joinDate = user.created_at || user.date_joined;
    if (joinDate) {
        const d = new Date(joinDate.replace(/-/g, "/"));
        const el = document.getElementById("userJoinedDate");
        if (el) el.textContent = d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    }

    // Phone
    let phone = user.phone || user.phone_number;
    if (phone) {
        let clean = phone.toString().replace(/\D/g, '');
        document.getElementById("userPhone").textContent = clean.startsWith('63') ? `+${clean}` : `+63 ${clean}`;
    } else {
        document.getElementById("userPhone").textContent = "Not Provided";
    }
    document.getElementById("userAddress").textContent = user.address || "Not Provided";
}

function showAlert(type, message) {
    const el = document.getElementById("profileAlert");
    el.className = `alert alert-${type} d-flex align-items-start gap-2`;
    el.innerHTML = message;
    el.classList.remove("d-none");
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function handleLogout() {
    const token = localStorage.getItem("jwt_token");
    const finish = () => {
        localStorage.removeItem("jwt_token");
        localStorage.removeItem("user");
        fetch('api/logout.php').finally(() => { window.location.href = "index.php"; });
    };
    if (token) {
        fetch('api/user_end_session.php', { method: 'POST', headers: { 'Authorization': 'Bearer ' + token } }).finally(finish);
    } else {
        finish();
    }
}

// ── AVATAR UPLOAD ─────────────────────────────────────────────
const _avatarInput = document.getElementById("avatarUploadInput");
if (_avatarInput) _avatarInput.addEventListener("change", function () {
    const file = this.files[0];
    if (!file) return;

    if (!['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(file.type)) {
        showAlert('danger', 'Only JPG, PNG, WEBP, or GIF images are allowed.');
        this.value = '';
        return;
    }
    if (file.size > 3 * 1024 * 1024) {
        showAlert('danger', 'Image must be smaller than 3MB.');
        this.value = '';
        return;
    }

    // Instant local preview
    const reader = new FileReader();
    reader.onload = e => {
        const cardAv = document.getElementById("profileCardAvatar");
        if (cardAv) { cardAv.style.backgroundImage = `url(${e.target.result})`; cardAv.innerHTML = ""; }
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
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            _userData.profile_picture = data.profile_picture;
            localStorage.setItem("user", JSON.stringify(_userData));
            showAlert('success', '<i class="bi bi-check-circle me-2"></i>' + data.message);
        } else {
            showAlert('danger', '<i class="bi bi-exclamation-circle me-2"></i>' + (data.message || 'Upload failed.'));
            updateUI(_userData); // revert preview
        }
    })
    .catch(() => {
        showAlert('danger', 'Network error during upload.');
        updateUI(_userData);
    });

    this.value = '';
});
</script>

<?php include 'includes/footer.php'; ?>