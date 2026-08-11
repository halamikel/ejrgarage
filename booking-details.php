<?php 
  $pageTitle = "Vehicle Details - EJR Garage";
  $extraJs = '<script src="js/booking.js"></script>';
  include 'includes/header.php'; 
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<section class="appointment-header text-center">
  <div class="container">
    <h1>Book Your Appointment</h1>
    <p>To provide our visitors with the best car service</p>
  </div>
</section>

<section class="steps py-4">
  <div class="container">
    <div class="step-wrapper">
      <div class="step active">1</div>
      <span class="step-label">Your Details</span>
      <div class="line"></div>
      <div class="step">2</div>
      <span class="step-label">Select Service</span>
      <div class="line"></div>
      <div class="step">3</div>
      <span class="step-label">Confirm</span>
    </div>
  </div>
</section>

<section class="services-section">
  <div class="container">
    <h4 class="mb-4 fw-bold">Vehicle Details</h4>
    <div class="service-card p-4 shadow-sm border-0 rounded-4">
      <form id="vehicleForm">

        <!-- Saved Vehicles -->
        <div id="savedVehiclesSection" class="mb-4" style="display:none;">
          <label class="form-label fw-bold text-dark" for="savedVehicleSelect">
            <i class="bi bi-car-front me-2 text-danger"></i>Select from Your Garage
          </label>
          <select class="form-select form-select-lg" id="savedVehicleSelect"
                  style="border-color:#e62429;">
            <option value="">-- Choose a saved vehicle --</option>
          </select>
          <div class="text-end mt-2">
            <button type="button" class="btn btn-sm text-danger fw-bold" id="btnAddNewManual">
              <i class="bi bi-plus-circle me-1"></i> Use a different vehicle
            </button>
          </div>
        </div>

        <!-- Manual Vehicle Form -->
        <div id="manualVehicleForm">
          <p class="text-muted small mb-3">
            <i class="bi bi-info-circle me-1"></i> Fill in your vehicle information below.
          </p>
          <div class="row g-3">
            <div class="col-6 col-md-6">
              <label class="form-label" for="brand">Car Brand</label>
              <select class="form-select" id="brand">
                <option value="">Select brand</option>
                <option>Toyota</option>
                <option>Honda</option>
                <option>Mitsubishi</option>
                <option>Nissan</option>
                <option>Ford</option>
                <option>Suzuki</option>
                <option>Isuzu</option>
                <option>Hyundai</option>
                <option>Kia</option>
                <option>Mazda</option>
                <option>Other</option>
              </select>
            </div>
            <div class="col-6 col-md-6">
              <label class="form-label" for="model">Car Model</label>
              <input type="text" class="form-control" id="model" placeholder="e.g. Vios">
            </div>
            <div class="col-6 col-md-6">
              <label class="form-label" for="year">Year Model</label>
              <select class="form-select" id="year">
                <option value="">Select year</option>
              </select>
            </div>
            <div class="col-6 col-md-6">
              <label class="form-label" for="plate">Plate Number</label>
              <input type="text" class="form-control" id="plate" placeholder="e.g. ABC 1234">
            </div>
            <div class="col-6 col-md-6">
              <label class="form-label" for="transmission">Transmission</label>
              <select class="form-select" id="transmission">
                <option value="">Select transmission</option>
                <option>Automatic</option>
                <option>Manual</option>
                <option>CVT</option>
              </select>
            </div>
            <div class="col-6 col-md-6">
              <label class="form-label" for="fuel">Fuel Type</label>
              <select class="form-select" id="fuel">
                <option value="">Select fuel type</option>
                <option>Gasoline</option>
                <option>Diesel</option>
                <option>Hybrid</option>
                <option>Electric</option>
              </select>
            </div>
            <div class="col-12 text-end" id="backToSavedContainer" style="display:none;">
              <button type="button" class="btn btn-sm text-secondary" id="btnBackToSaved">
                <i class="bi bi-arrow-left me-1"></i> Back to my garage
              </button>
            </div>
          </div>
        </div>

        <hr class="my-4 opacity-25">

        <!-- Appointment Date & Notes -->
        <div class="col-12 col-md-6">
            <label class="form-label fw-bold" for="appointmentDate">
                <i class="bi bi-calendar3 me-1 text-danger"></i> Appointment Date
            </label>
            <input type="text" class="form-control" id="appointmentDate" 
                   placeholder="Select a date..." readonly required>
            <small class="text-muted mt-1 d-block">
                <span style="display:inline-block;width:12px;height:12px;background:#e62429;border-radius:50%;margin-right:4px;"></span>
                Red dates are fully booked
            </small>
        </div>
          <div class="col-12">
            <label class="form-label fw-bold" for="notes">
              <i class="bi bi-chat-left-text me-1 text-danger"></i> Additional Notes
            </label>
            <textarea class="form-control" id="notes" rows="3"
                      placeholder="Enter any special requests or issues with your vehicle..."></textarea>
          </div>
        </div>

        <div class="mt-3" id="errorMsg" style="display:none;">
          <div class="alert alert-danger py-2 mb-0">
            <i class="bi bi-exclamation-circle me-1"></i> Please complete all vehicle details before continuing.
          </div>
        </div>

        <div class="d-flex justify-content-between mt-5">
          <button type="button" class="btn btn-next px-5 fw-bold text-white"
                  style="background: linear-gradient(45deg, #e62429, #ff6b00); border: none;"
                  onclick="window.location.href='booking.php'">
            NEXT <i class="bi bi-arrow-right ms-1"></i>
          </button>
        </div>

      </form>
    </div>
  </div>
</section>

<style>
.step-label {
  font-size: 13px;
  font-weight: 600;
  color: #5f6b7a;
}
@media (max-width: 576px) {
  .step-label { font-size: 11px; }
  .line { width: 30px !important; }
  .appointment-header { padding: 40px 0; }
  .appointment-header h1 { font-size: 1.8rem; }
  .service-card { padding: 16px !important; }
  .form-select, .form-control { font-size: 14px; }
}
</style>


<?php include 'includes/footer.php'; ?>