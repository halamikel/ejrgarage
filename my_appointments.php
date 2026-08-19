<?php 
  $pageTitle = "My Appointments - EJR Garage";
  include 'includes/header.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<section class="profile-header-bg">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div>
                    <h2 class="mb-0 fw-bold">Appointments</h2>
                    <p class="mb-0 opacity-75">View and manage your scheduled service appointments</p>
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
                        
                        <a href="my_appointments.php" class="nav-link-custom active">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0 fw-bold">My Appointments</h4>
                    <a href="booking.php" class="btn px-4 fw-bold text-white" 
                       style="background: linear-gradient(45deg, #e62429, #ff6b00); border: none;">
                        <i class="bi bi-plus-lg me-1"></i> Book Appointment
                    </a>
                </div>

                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="mb-3 fw-bold text-dark">
                        <i class="bi bi-calendar-check text-danger me-2"></i> Upcoming Appointment
                    </h5>
                    <div id="upcomingContainer" class="appointments-scroll">
                        <p class="text-muted small">Checking for scheduled services...</p>
                    </div>
                </div>

                <div class="card border-0 shadow-sm p-4">
                    <h5 class="mb-3 fw-bold text-dark">
                        <i class="bi bi-tools text-secondary me-2"></i> Completed Appointment
                    </h5>
                    <div id="completedContainer" class="appointments-scroll">
                        <p class="text-muted small">No past records to display.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<style>
    .appointments-scroll {
        max-height: 420px;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 6px;
        scrollbar-width: thin;
        scrollbar-color: #e62429 #f1f1f1;
    }

    .appointments-scroll::-webkit-scrollbar {
        width: 6px;
    }

    .appointments-scroll::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .appointments-scroll::-webkit-scrollbar-thumb {
        background-color: #e62429;
        border-radius: 10px;
    }

    .appointments-scroll::-webkit-scrollbar-thumb:hover {
        background-color: #b30000;
    }

    .appointments-scroll .border.rounded:last-child {
        margin-bottom: 0 !important;
    }

    @media (max-width: 576px) {
        .appointments-scroll {
            max-height: 320px;
        }
    }
</style>

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

        // Show Admin Panel Button for Admins or Lance (ID 21)
        const adminContainer = document.getElementById("adminAccessContainer");
        if (user.role_id == 2 || user.id == 21) {
            adminContainer.innerHTML = `
                <a href="admin_dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3 shadow-sm fw-bold">
                    <i class="bi bi-shield-lock-fill me-1"></i> Admin Panel
                </a>
            `;
        }

        if (user.created_at) {
            const date = new Date(user.created_at.replace(/-/g, "/"));
            const options = { month: 'long', year: 'numeric' };
            const joinedEl = document.getElementById("userJoinedDate");
            if (joinedEl) joinedEl.textContent = date.toLocaleDateString('en-US', options);
        }
    }

    if (typeof loadAppointments === "function") {
        loadAppointments();
    }
});

async function handleCancelAppointment(appointmentId) {
    const result = await Swal.fire({
        title: 'Cancel Appointment?',
        text: "This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e62429',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, cancel it!',
        background: '#fff'
    });

    if (result.isConfirmed) {
        try {
            const token = localStorage.getItem("jwt_token");
            const response = await fetch('api/cancel_appointment.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}` 
                },
                body: JSON.stringify({ id: appointmentId })
            });
            
            const res = await response.json();
            if (res.status === "success") {
                Swal.fire({
                    icon: 'success',
                    title: 'Cancelled!',
                    text: 'Your appointment was removed.',
                    timer: 2000,
                    showConfirmButton: false
                });
                loadAppointments();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Connection failed.', 'error');
        }
    }
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