<?php 
  $pageTitle = "My Vehicles - EJR Garage";
  include 'includes/header.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<section class="profile-header-bg">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div>
                    <h2 class="mb-0 fw-bold">My Vehicles</h2>
                    <p class="mb-0 opacity-75">Manage the vehicles registered to your account</p>
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
                        <a href="my_vehicles.php" class="nav-link-custom active">
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

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h4 class="mb-4 fw-bold">My Vehicles</h4>
                    <div id="vehicleList">
                        <div class="text-center py-4 text-secondary">
                            <div class="spinner-border text-danger spinner-border-sm" role="status"></div>
                            <p class="mt-2">Loading vehicles...</p>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm p-4">
                    <h4 class="mb-4 fw-bold">Add New Vehicle</h4>
                    <form id="addVehicleForm" onsubmit="handleAddVehicle(event)">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Car Brand</label>
                                <select class="form-select bg-light" aria-label="Select car brand" id="carBrand" required>
                                    <option selected disabled value="">Select brand</option>
                                    <option>Toyota</option>
                                    <option>Honda</option>
                                    <option>Mitsubishi</option>
                                    <option>Nissan</option>
                                    <option>Suzuki</option>
                                    <option>Ford</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Car Model</label>
                                <input type="text" class="form-control bg-light" aria-label="Enter car model" id="carModel" placeholder="e.g. Vios or Xpander" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Year Model</label>
                                <select class="form-select bg-light" aria-label="Select year model" id="yearModel" required>
                                    <option selected disabled value="">Select year</option>
                                    <?php for ($y = date("Y"); $y >= 1990; $y--): ?>
                                        <option><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Plate Number</label>
                                <input type="text" class="form-control bg-light text-uppercase" aria-label="Enter plate number" id="plateNumber" placeholder="ABC 1234" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Transmission</label>
                                <select class="form-select bg-light" aria-label="Select transmission" id="transmission" required>
                                    <option selected disabled value="">Select transmission</option>
                                    <option>Automatic</option>
                                    <option>Manual</option>
                                    <option>CVT</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fuel Type</label>
                                <select class="form-select bg-light" aria-label="Select fuel type" id="fuelType" required>
                                    <option selected disabled value="">Select fuel type</option>
                                    <option>Gasoline</option>
                                    <option>Diesel</option>
                                    <option>Hybrid</option>
                                    <option>Electric</option>
                                </select>
                            </div>

                            <div class="col-12 mt-4">
                                <button 
                                    type="submit" 
                                    id="addVehicleBtn" 
                                    class="btn fw-bold px-4"
                                    style="background-color:#dc3545; color:#fff; border:none;"
                                >
                                    Add Vehicle
                                </button>
                            </div>
                        </div>
                    </form>
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

    if (typeof loadVehicles === "function") {
        loadVehicles();
    }
});

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