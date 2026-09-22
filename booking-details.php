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

        <!-- =====================================================
             YOUR CONTACT DETAILS
        ====================================================== -->

        <div id="manualContactForm" class="mb-4">

          <h6 class="fw-bold text-dark mb-1">
            <i class="bi bi-person-vcard me-2 text-danger"></i>Your Details
          </h6>
          <p class="text-muted small mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Who should we contact and where should we reach you about this appointment?
          </p>

          <div class="row g-3">

            <div class="col-12 col-md-6">
              <label class="form-label" for="contactFullName">Full Name</label>
              <input
                type="text"
                class="form-control"
                id="contactFullName"
                placeholder="Juan Dela Cruz"
                autocomplete="off"
              >
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label" for="contactNumber">Contact Number</label>
              <div class="phone-input-wrap">
                <span class="phone-prefix">+63</span>
                <input
                  type="tel"
                  class="form-control"
                  id="contactNumber"
                  placeholder="9XXXXXXXXX"
                  inputmode="numeric"
                  maxlength="10"
                  autocomplete="off"
                >
              </div>
              <small class="text-muted">10-digit mobile number, numbers only.</small>
            </div>

            <div class="col-12">
              <label class="form-label" for="contactAddress1">Address Line 1</label>
              <input
                type="text"
                class="form-control"
                id="contactAddress1"
                placeholder="House/Unit No., Street Name"
                autocomplete="off"
              >
            </div>

            <div class="col-6 col-md-5">
              <label class="form-label" for="contactCity">City / Town</label>
              <input type="text" class="form-control" id="contactCity" placeholder="e.g. Santo Tomas" autocomplete="off">
            </div>

            <div class="col-6 col-md-4">
              <label class="form-label" for="contactProvince">Province</label>
              <input type="text" class="form-control" id="contactProvince" placeholder="e.g. Batangas" autocomplete="off">
            </div>

            <div class="col-6 col-md-3">
              <label class="form-label" for="contactZip">ZIP Code</label>
              <input type="text" class="form-control" id="contactZip" placeholder="4234" inputmode="numeric" maxlength="4" autocomplete="off">
            </div>

            <div class="col-12">
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="saveAsContactProfile">
                <label class="form-check-label small" for="saveAsContactProfile">
                  Save these details as a new profile for next time
                </label>
              </div>
              <div id="contactProfileLabelWrap" style="display:none;">
                <label class="form-label" for="contactProfileLabel">Profile Label</label>
                <input type="text" class="form-control" id="contactProfileLabel" placeholder="e.g. Home, Office" maxlength="50">
              </div>
            </div>

          </div>

        </div>

        <hr class="my-4 opacity-25">

        <!-- =====================================================
             SAVED VEHICLES
        ====================================================== -->

        <div
          id="savedVehiclesSection"
          class="mb-4"
          style="display:none;"
        >

          <label
            class="form-label fw-bold text-dark"
            for="savedVehicleSelect"
          >

            <i class="bi bi-car-front me-2 text-danger"></i>

            Select from Your Garage

          </label>


          <select
            class="form-select form-select-lg"
            id="savedVehicleSelect"
            style="border-color:#e62429;"
          >

            <option value="">
              -- Choose a saved vehicle --
            </option>

          </select>


          <div class="text-end mt-2">

            <button
              type="button"
              class="btn btn-sm text-danger fw-bold"
              id="btnAddNewManual"
            >

              <i class="bi bi-plus-circle me-1"></i>

              Add a vehicle

            </button>

          </div>

        </div>


        <!-- =====================================================
             MANUAL VEHICLE FORM
        ====================================================== -->

        <div id="manualVehicleForm">

          <p class="text-muted small mb-3">

            <i class="bi bi-info-circle me-1"></i>

            Fill in your vehicle information below.

          </p>


          <div class="row g-3">


            <!-- =================================================
                 CAR BRAND
            ================================================== -->

            <div class="col-6 col-md-6">

              <label
                class="form-label"
                for="brand"
              >
                Car Brand
              </label>


              <select
                class="form-select"
                id="brand"
              >

                <option value="">
                  Select brand
                </option>

                <option value="Toyota">
                  Toyota
                </option>

                <option value="Honda">
                  Honda
                </option>

                <option value="Mitsubishi">
                  Mitsubishi
                </option>

                <option value="Nissan">
                  Nissan
                </option>

                <option value="Ford">
                  Ford
                </option>

                <option value="Suzuki">
                  Suzuki
                </option>

                <option value="Isuzu">
                  Isuzu
                </option>

                <option value="Hyundai">
                  Hyundai
                </option>

                <option value="Kia">
                  Kia
                </option>

                <option value="Mazda">
                  Mazda
                </option>

                <option value="Other">
                  Other
                </option>

              </select>

            </div>


            <!-- =================================================
                 CAR MODEL
            ================================================== -->

            <div class="col-6 col-md-6">

              <label
                class="form-label"
                for="model"
              >
                Car Model
              </label>


              <!-- =================================================
                   NORMAL MODEL DROPDOWN
              ================================================== -->

              <select
                class="form-select"
                id="model"
                disabled
              >

                <option value="">
                  Select a brand first
                </option>

              </select>


              <!-- =================================================
                   CUSTOM MODEL TEXTBOX

                   This is hidden by default.

                   It appears when:
                   1. Brand = Other
                   2. Model = Other
              ================================================== -->

              <input
                type="text"
                class="form-control"
                id="modelOther"
                placeholder="Enter your car model"
                style="display:none;"
                disabled
                autocomplete="off"
              >


              <small
                class="text-muted d-block mt-1"
                id="modelHelpText"
              >

                Models are based on vehicles commonly
                sold/registered in the Philippines.

              </small>

            </div>


            <!-- =================================================
                 YEAR
            ================================================== -->

            <div class="col-6 col-md-6">

              <label
                class="form-label"
                for="year"
              >
                Year Model
              </label>


              <select
                class="form-select"
                id="year"
              >

                <option value="">
                  Select year
                </option>

              </select>

            </div>


            <!-- =================================================
                 PLATE NUMBER
            ================================================== -->

            <div class="col-6 col-md-6">

              <label
                class="form-label"
                for="plate"
              >
                Plate Number
              </label>


              <input
                type="text"
                class="form-control"
                id="plate"
                placeholder="e.g. ABC 1234"
                autocomplete="off"
              >

            </div>


            <!-- =================================================
                 TRANSMISSION
            ================================================== -->

            <div class="col-6 col-md-6">

              <label
                class="form-label"
                for="transmission"
              >
                Transmission
              </label>


              <select
                class="form-select"
                id="transmission"
              >

                <option value="">
                  Select transmission
                </option>

                <option value="Automatic">
                  Automatic
                </option>

                <option value="Manual">
                  Manual
                </option>

                <option value="CVT">
                  CVT
                </option>

              </select>

            </div>


            <!-- =================================================
                 FUEL TYPE
            ================================================== -->

            <div class="col-6 col-md-6">

              <label
                class="form-label"
                for="fuel"
              >
                Fuel Type
              </label>


              <select
                class="form-select"
                id="fuel"
              >

                <option value="">
                  Select fuel type
                </option>

                <option value="Gasoline">
                  Gasoline
                </option>

                <option value="Diesel">
                  Diesel
                </option>

                <option value="Hybrid">
                  Hybrid
                </option>

                <option value="Electric">
                  Electric
                </option>

              </select>

            </div>


            <!-- =================================================
                 BACK TO SAVED VEHICLES
            ================================================== -->

            <div
              class="col-12 text-end"
              id="backToSavedContainer"
              style="display:none;"
            >

              <button
                type="button"
                class="btn btn-sm text-secondary"
                id="btnBackToSaved"
              >

                <i class="bi bi-arrow-left me-1"></i>

                Back to my garage

              </button>

            </div>

          </div>

        </div>


        <hr class="my-4 opacity-25">


        <!-- =====================================================
             APPOINTMENT DATE
        ====================================================== -->

        <div class="col-12 col-md-6">

          <label
            class="form-label fw-bold"
            for="appointmentDate"
          >

            <i class="bi bi-calendar3 me-1 text-danger"></i>

            Appointment Date

          </label>


          <input
            type="text"
            class="form-control"
            id="appointmentDate"
            placeholder="Select a date..."
            readonly
            required
          >


          <small class="text-muted mt-1 d-block">

            <span
              style="
                display:inline-block;
                width:12px;
                height:12px;
                background:#e62429;
                border-radius:50%;
                margin-right:4px;
              "
            ></span>

            Red dates are fully booked

          </small>

        </div>


        <!-- =====================================================
             ERROR MESSAGE
        ====================================================== -->

        <div
          class="mt-3"
          id="errorMsg"
          style="display:none;"
        >

          <div class="alert alert-danger py-2 mb-0">

            <i class="bi bi-exclamation-circle me-1"></i>

            Please complete all vehicle details before continuing.

          </div>

        </div>


        <!-- =====================================================
             NEXT BUTTON
        ====================================================== -->

        <div class="d-flex justify-content-end mt-5">

          <button
            type="submit"
            class="btn btn-next px-5 fw-bold text-white"
            style="
              background:linear-gradient(45deg,#e62429,#ff6b00);
              border:none;
            "
          >

            NEXT

            <i class="bi bi-arrow-right ms-1"></i>

          </button>

        </div>

      </form>

    </div>

  </div>
</section>


<!-- =============================================================
     PAGE STYLES
============================================================= -->

<style>

.step-label {
  font-size: 13px;
  font-weight: 600;
  color: #5f6b7a;
}


/* =============================================================
   CUSTOM MODEL INPUT
============================================================= */

#modelOther {
  min-height: 48px;
}


/* =============================================================
   PHONE PREFIX INPUT
============================================================= */

.phone-input-wrap {
  position: relative;
}

.phone-input-wrap .phone-prefix {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #495057;
  font-weight: 600;
  pointer-events: none;
}

.phone-input-wrap input[type="tel"] {
  padding-left: 48px;
}


/* =============================================================
   MOBILE
============================================================= */

@media (max-width: 576px) {

  .step-label {
    font-size: 11px;
  }

  .line {
    width: 30px !important;
  }

  .appointment-header {
    padding: 40px 0;
  }

  .appointment-header h1 {
    font-size: 1.8rem;
  }

  .service-card {
    padding: 16px !important;
  }

  .form-select,
  .form-control {
    font-size: 14px;
  }

}

</style>


<?php include 'includes/footer.php'; ?>