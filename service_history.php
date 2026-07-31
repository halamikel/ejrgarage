<?php 
  $pageTitle = "Service History - EJR Garage";
  include 'includes/header.php'; 
?>

<section class="profile-header-bg">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div>
                    <h2 class="mb-0 fw-bold">Service History</h2>
                    <p class="mb-0 opacity-75">View past services performed on your vehicles</p>
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
                        <a href="account.php" class="nav-link-custom">
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
                        <a href="service_history.php" class="nav-link-custom active">
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
                <h4 class="mb-4 fw-bold">Service History</h4>
                <div id="historyContainer">
                    <div class="text-center py-5">
                        <div class="spinner-border text-danger"></div>
                        <p class="mt-2 text-muted">Loading service history...</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const token   = localStorage.getItem("jwt_token");
    const userStr = localStorage.getItem("user");

    if (!token) {
        window.location.href = "login.php";
        return;
    }

    if (userStr) {
        const user = JSON.parse(userStr);
        const userNameEl = document.getElementById("userName");
        if (userNameEl) userNameEl.textContent = user.name || user.full_name || "EJR Customer";

        const adminContainer = document.getElementById("adminAccessContainer");
        if (user.role_id == 2 || user.id == 21) {
            adminContainer.innerHTML = `
                <a href="admin_dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3 shadow-sm fw-bold">
                    <i class="bi bi-shield-lock-fill me-1"></i> Admin Panel
                </a>`;
        }

        if (user.created_at) {
            const date = new Date(user.created_at.replace(/-/g, "/"));
            const joinedEl = document.getElementById("userJoinedDate");
            if (joinedEl) joinedEl.textContent =
                date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        }
    }

    loadServiceHistory(token);
});

function loadServiceHistory(token) {
    fetch('api/get_service_history.php', {
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById('historyContainer');

        if (data.status !== 'success' || !data.history.length) {
            container.innerHTML = `
                <div class="card border-0 shadow-sm p-5 text-center">
                    <i class="bi bi-clock-history fs-1 text-muted mb-3"></i>
                    <h5 class="text-muted">No service history yet</h5>
                    <p class="text-muted small">Completed appointments will appear here once approved by EJR Garage.</p>
                </div>`;
            return;
        }

        const icons = {
            'oil':          'bi-droplet',
            'brake':        'bi-exclamation-circle',
            'transmission': 'bi-gear',
            'maintenance':  'bi-calendar-check',
            'inspection':   'bi-search',
            'carburator':   'bi-tools',
            'pms':          'bi-wrench'
        };

        function getIcon(service) {
            const s = service.toLowerCase();
            for (const key in icons) {
                if (s.includes(key)) return icons[key];
            }
            return 'bi-tools';
        }

        container.innerHTML = data.history.map(h => {
            const vehicle = h.brand && h.model
                ? `${h.brand} ${h.model} (${h.year_model || '—'})`
                : '—';
            const date = h.appointment_date
                ? new Date(h.appointment_date).toLocaleDateString('en-US', {
                    month: 'long', day: 'numeric', year: 'numeric'
                  })
                : '—';

            return `
            <div class="card border-0 shadow-sm p-4 mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <div class="mb-3 mb-md-0">
                        <h5 class="fw-bold mb-2 text-dark">
                            <i class="bi ${getIcon(h.service_type)} text-danger me-2"></i>
                            ${h.service_type}
                        </h5>
                        <div class="text-secondary small">
                            <span class="me-3"><strong class="text-dark">Vehicle:</strong> ${vehicle}</span>
                            <span class="me-3"><strong class="text-dark">Date:</strong> ${date}</span>
                            <span><strong class="text-dark">Technician:</strong> EJR Garage Staff</span>
                        </div>
                    </div>
                    <div class="text-md-end">
                        <span class="badge bg-success px-3 py-2 d-block mb-2">Completed</span>
                        <small class="text-muted" style="font-size:11px;">
                            <i class="bi bi-info-circle me-1"></i>Receipt available on E-mail.
                        </small>
                    </div>
                </div>
            </div>`;
        }).join('');
    })
    .catch(() => {
        document.getElementById('historyContainer').innerHTML =
            '<div class="alert alert-danger">Failed to load service history.</div>';
    });
}

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
</script>

<?php include 'includes/footer.php'; ?>