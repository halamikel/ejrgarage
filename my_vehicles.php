<?php 
  $pageTitle = "My Vehicles - EJR Garage";
  include 'includes/header.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/vehicle-form.js"></script>

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

                        <p class="text-muted small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Fill in your vehicle information below.
                        </p>

                        <div class="row g-3">

                            <!-- CAR BRAND -->
                            <div class="col-6 col-md-6">
                                <label class="form-label" for="brand">Car Brand</label>
                                <select class="form-select" id="brand">
                                    <option value="">Select brand</option>
                                    <option value="Toyota">Toyota</option>
                                    <option value="Honda">Honda</option>
                                    <option value="Mitsubishi">Mitsubishi</option>
                                    <option value="Nissan">Nissan</option>
                                    <option value="Ford">Ford</option>
                                    <option value="Suzuki">Suzuki</option>
                                    <option value="Isuzu">Isuzu</option>
                                    <option value="Hyundai">Hyundai</option>
                                    <option value="Kia">Kia</option>
                                    <option value="Mazda">Mazda</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <!-- CAR MODEL -->
                            <div class="col-6 col-md-6">
                                <label class="form-label" for="model">Car Model</label>

                                <!-- NORMAL MODEL DROPDOWN -->
                                <select class="form-select" id="model" disabled>
                                    <option value="">Select a brand first</option>
                                </select>

                                <!-- CUSTOM MODEL TEXTBOX (hidden by default) -->
                                <input type="text" class="form-control" id="modelOther" placeholder="Enter your car model" style="display:none;" disabled autocomplete="off">

                                <small class="text-muted d-block mt-1" id="modelHelpText">
                                    Models are based on vehicles commonly sold/registered in the Philippines.
                                </small>
                            </div>

                            <!-- YEAR -->
                            <div class="col-6 col-md-6">
                                <label class="form-label" for="year">Year Model</label>
                                <select class="form-select" id="year">
                                    <option value="">Select year</option>
                                    <?php for ($y = date("Y"); $y >= 1990; $y--): ?>
                                        <option><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- PLATE NUMBER -->
                            <div class="col-6 col-md-6">
                                <label class="form-label" for="plate">Plate Number</label>
                                <input type="text" class="form-control" id="plate" placeholder="e.g. ABC 1234" autocomplete="off">
                            </div>

                            <!-- TRANSMISSION -->
                            <div class="col-6 col-md-6">
                                <label class="form-label" for="transmission">Transmission</label>
                                <select class="form-select" id="transmission">
                                    <option value="">Select transmission</option>
                                    <option value="Automatic">Automatic</option>
                                    <option value="Manual">Manual</option>
                                    <option value="CVT">CVT</option>
                                </select>
                            </div>

                            <!-- FUEL TYPE -->
                            <div class="col-6 col-md-6">
                                <label class="form-label" for="fuel">Fuel Type</label>
                                <select class="form-select" id="fuel">
                                    <option value="">Select fuel type</option>
                                    <option value="Gasoline">Gasoline</option>
                                    <option value="Diesel">Diesel</option>
                                    <option value="Hybrid">Hybrid</option>
                                    <option value="Electric">Electric</option>
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

<!-- Edit Vehicle Modal -->
<div class="modal fade" id="editVehicleModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-0 p-4" style="background: linear-gradient(135deg, #1e2a36, #2d3f50); color: white; border-radius: 15px 15px 0 0;">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-pencil-square me-2"></i>Edit Vehicle
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="editVehicleForm" onsubmit="handleUpdateVehicle(event)">
        <div class="modal-body p-4">
          <input type="hidden" id="editVehicleId">

          <div class="row g-3">

            <!-- CAR BRAND -->
            <div class="col-6">
              <label class="form-label" for="editBrand">Car Brand</label>
              <select class="form-select" id="editBrand">
                <option value="">Select brand</option>
                <option value="Toyota">Toyota</option>
                <option value="Honda">Honda</option>
                <option value="Mitsubishi">Mitsubishi</option>
                <option value="Nissan">Nissan</option>
                <option value="Ford">Ford</option>
                <option value="Suzuki">Suzuki</option>
                <option value="Isuzu">Isuzu</option>
                <option value="Hyundai">Hyundai</option>
                <option value="Kia">Kia</option>
                <option value="Mazda">Mazda</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <!-- CAR MODEL -->
            <div class="col-6">
              <label class="form-label" for="editModel">Car Model</label>
              <select class="form-select" id="editModel" disabled>
                <option value="">Select a brand first</option>
              </select>
              <input type="text" class="form-control" id="editModelOther" placeholder="Enter your car model" style="display:none;" disabled autocomplete="off">
              <small class="text-muted d-block mt-1" id="editModelHelpText">
                Models are based on vehicles commonly sold/registered in the Philippines.
              </small>
            </div>

            <!-- YEAR -->
            <div class="col-6">
              <label class="form-label" for="editYear">Year Model</label>
              <select class="form-select" id="editYear">
                <option value="">Select year</option>
                <?php for ($y = date("Y"); $y >= 1990; $y--): ?>
                    <option><?php echo $y; ?></option>
                <?php endfor; ?>
              </select>
            </div>

            <!-- PLATE NUMBER -->
            <div class="col-6">
              <label class="form-label" for="editPlate">Plate Number</label>
              <input type="text" class="form-control" id="editPlate" placeholder="e.g. ABC 1234" autocomplete="off">
            </div>

            <!-- TRANSMISSION -->
            <div class="col-6">
              <label class="form-label" for="editTransmission">Transmission</label>
              <select class="form-select" id="editTransmission">
                <option value="">Select transmission</option>
                <option value="Automatic">Automatic</option>
                <option value="Manual">Manual</option>
                <option value="CVT">CVT</option>
              </select>
            </div>

            <!-- FUEL TYPE -->
            <div class="col-6">
              <label class="form-label" for="editFuel">Fuel Type</label>
              <select class="form-select" id="editFuel">
                <option value="">Select fuel type</option>
                <option value="Gasoline">Gasoline</option>
                <option value="Diesel">Diesel</option>
                <option value="Hybrid">Hybrid</option>
                <option value="Electric">Electric</option>
              </select>
            </div>

          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="updateVehicleBtn" class="btn fw-bold px-4" style="background-color:#dc3545; color:#fff; border:none;">
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    initVehicleBrandModel({
        brandId: "brand",
        modelId: "model",
        modelOtherId: "modelOther",
        modelHelpId: "modelHelpText"
    });

    initVehicleBrandModel({
        brandId: "editBrand",
        modelId: "editModel",
        modelOtherId: "editModelOther",
        modelHelpId: "editModelHelpText"
    });

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