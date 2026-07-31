<?php 
  $pageTitle = "My Inquiries - EJR Garage";
  include 'includes/header.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<section class="profile-header-bg">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div>
                    <h2 class="mb-0 fw-bold">My Inquiries</h2>
                    <p class="mb-0 opacity-75">Track messages you've sent to EJR Garage</p>
                </div>
            </div>
            
            <?php if(isset($_SESSION['role_id']) && ($_SESSION['role_id'] == 2 || $_SESSION['user_id'] == 21)): ?>
                <div class="ms-auto">
                    <a href="admin_dashboard.php" class="btn btn-outline-light btn-sm px-3 rounded-pill shadow-sm fw-bold">
                        <i class="bi bi-shield-lock-fill me-1"></i> Admin Panel
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container account-container">
        <div class="row g-4">
            
            <div class="col-lg-4">
                <div class="sidebar-card card border-0 shadow-sm p-3">
                    <nav class="d-flex flex-column gap-1">
                        <a href="account.php" class="nav-link-custom">
                            <i class="bi bi-person"></i> Profile
                        </a>
                        <a href="my_vehicles.php" class="nav-link-custom">
                            <i class="bi bi-truck"></i> My Vehicles
                        </a>
                        <a href="my_orders.php" class="nav-link-custom">
                            <i class="bi bi-bag-check"></i> My Orders
                        </a>
                        <a href="my_inquiries.php" class="nav-link-custom active">
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

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4">
                    <h4 class="mb-4 fw-bold">Part Inquiries History</h4>
                    <div id="inquiryList">
                        <div class="text-center py-4 text-secondary">
                            <div class="spinner-border text-danger spinner-border-sm" role="status"></div>
                            <p class="mt-2">Loading inquiries...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const token = localStorage.getItem("jwt_token");
    const userStr = localStorage.getItem("user");

    if (!token) {
        window.location.href = "login.php";
        return;
    }

    if (userStr) {
        const user = JSON.parse(userStr);
        const userNameEl = document.getElementById("userName");
        if (userNameEl) userNameEl.textContent = user.name || user.full_name || "EJR Customer";

        if (user.created_at) {
            const date = new Date(user.created_at.replace(/-/g, "/"));
            const options = { month: 'long', year: 'numeric' };
            const joinedEl = document.getElementById("userJoinedDate");
            if (joinedEl) joinedEl.textContent = date.toLocaleDateString('en-US', options);
        }

        // JS Fallback for the original button style
        if ((user.role_id == 2 || user.id == 21) && !document.querySelector('.btn-outline-light')) {
            const adminBtn = `
                <div class="ms-auto">
                    <a href="admin_dashboard.php" class="btn btn-outline-light btn-sm px-3 rounded-pill shadow-sm fw-bold">
                        <i class="bi bi-shield-lock-fill me-1"></i> Admin Panel
                    </a>
                </div>`;
            document.querySelector('.profile-header-bg .container .d-flex').insertAdjacentHTML('beforeend', adminBtn);
        }
    }
    loadMyInquiries();
});

async function loadMyInquiries() {
    try {
        // Pointing to the specific get_inquiries API
        const response = await fetch('api/get_inquiries.php', { 
            credentials: 'include' 
        });
        
        const data = await response.json();
        const container = document.getElementById("inquiryList");

        if (data && data.status === 'success' && Array.isArray(data.inquiries) && data.inquiries.length > 0) {
            let html = `
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Part & Date</th>
                                <th style="width: 55%;">Inquiry Details</th>
                                <th style="width: 20%;" class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>`;

            data.inquiries.forEach(inq => {
                const status = (inq.status || 'pending').toLowerCase();
                const badgeClass = status === 'pending' ? 'bg-warning text-dark' : 'bg-success';
                
                const adminReply = inq.reply_message 
                    ? `<div class="mt-3 p-3 bg-light border-start border-danger border-4 rounded-3 shadow-sm">
                         <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-chat-left-dots-fill text-danger me-2 small"></i>
                            <small class="fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Garage Response</small>
                         </div>
                         <p class="mb-0 text-dark small">${inq.reply_message}</p>
                       </div>` 
                    : '';

                html += `
                    <tr>
                        <td class="py-3">
                            <span class="fw-bold d-block text-dark">${inq.part_name}</span>
                            <small class="text-muted">
                                <i class="bi bi-calendar3 me-1"></i>${new Date(inq.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                            </small>
                        </td>
                        <td class="py-3">
                            <div class="pe-lg-4">
                                <small class="text-secondary d-block mb-1">My Message:</small>
                                <p class="mb-0 text-muted small italic">"${inq.message || 'No message'}"</p>
                                ${adminReply}
                            </div>
                        </td>
                        <td class="py-3 text-end">
                            <span class="badge rounded-pill ${badgeClass} px-3 py-2">
                                ${status.toUpperCase()}
                            </span>
                        </td>
                    </tr>`;
            });

            html += `</tbody></table></div>`;
            container.innerHTML = html;
        } else {
            container.innerHTML = `
                <div class="text-center py-5 text-secondary">
                    <i class="bi bi-chat-left-quote fs-1 opacity-25"></i>
                    <p class="mt-2">No inquiries found yet.</p>
                </div>`;
        }
    } catch (err) {
        console.error("Fetch error:", err);
        const container = document.getElementById("inquiryList");
        if (container) {
            container.innerHTML = `
                <div class="alert alert-danger mx-auto" style="max-width: 400px;">
                    <i class="bi bi-exclamation-triangle me-2"></i> Failed to load inquiries. Please try again later.
                </div>`;
        }
    }
}

function handleLogout() {
    const token = localStorage.getItem("jwt_token");
    const finish = () => {
        localStorage.clear();
        window.location.href = "login.php";
    };
    if (token) {
        fetch('api/user_end_session.php', { method: 'POST', headers: { 'Authorization': 'Bearer ' + token } }).finally(finish);
    } else {
        finish();
    }
}
</script>

<style>
.nav-link-custom {
    padding: 12px 18px;
    border-radius: 10px;
    transition: all 0.2s ease;
    color: #495057;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
}

.nav-link-custom:hover {
    background-color: #f8f9fa;
    color: #b30000;
}

.nav-link-custom.active {
    background-color: #b30000;
    color: #ffffff !important;
    font-weight: 600;
}
</style>

<?php include 'includes/footer.php'; ?>