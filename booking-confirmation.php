<?php 
  $pageTitle = "Booking Confirmed - EJR Garage";
  include 'includes/header.php'; 
?>


<section class="appointment-header text-center">
  <div class="container">
    <div class="calendar-icon"><i class="bi bi-calendar-check"></i></div>
    <h1>Book Your Appointment</h1>
    <p>To provide your vehicle with the best car service</p>
  </div>
</section>

<section class="steps">
  <div class="container">
    <div class="step-wrapper">
      <div class="step">1</div><span>Select Your Service</span><div class="line"></div>
      <div class="step">2</div><span>Your Details</span><div class="line"></div>
      <div class="step active">3</div><span>Confirm</span>
    </div>
  </div>
</section>

<section class="services-section">
  <div class="container">
    <div class="confirmation-card mx-auto p-4 p-md-5" style="max-width: 700px;">
      
      <div class="confirmation-head text-center mb-4">
        <h2 class="mt-3 mb-2 fw-bold text-dark">Review Your Appointment</h2>
        <p class="text-muted mb-1">Thank you for choosing EJR Garage!</p>
        <small class="text-muted">Please review the details below before finalizing.</small>
      </div>
      
      <div class="confirmation-box">
        <h5 class="fw-bold m-0">Appointment Details</h5>
        
        <div class="detail-group">
          <div class="detail-title">Selected Services</div>
          <ul id="serviceList" class="detail-list mb-0" style="padding-left: 20px;"></ul>
        </div>
        
        <div class="detail-group">
          <div class="detail-title">Vehicle Information</div>
          <div class="detail-item d-flex justify-content-between mb-2">
            <span class="text-muted">Brand:</span> <strong id="confirmBrand" class="text-dark"></strong>
          </div>
          <div class="detail-item d-flex justify-content-between mb-2">
            <span class="text-muted">Model:</span> <strong id="confirmModel" class="text-dark"></strong>
          </div>
          <div class="detail-item d-flex justify-content-between mb-2">
            <span class="text-muted">Year:</span> <strong id="confirmYear" class="text-dark"></strong>
          </div>
          <div class="detail-item d-flex justify-content-between mb-2">
            <span class="text-muted">Plate Number:</span> <strong id="confirmPlate" class="text-dark"></strong>
          </div>
          <div class="detail-item d-flex justify-content-between mb-2">
            <span class="text-muted">Transmission:</span> <strong id="confirmTransmission" class="text-dark"></strong>
          </div>
          <div class="detail-item d-flex justify-content-between">
            <span class="text-muted">Fuel Type:</span> <strong id="confirmFuel" class="text-dark"></strong>
          </div>
        </div>
      </div>
      
      <div class="confirmation-actions text-center mt-5 d-flex flex-column flex-md-row justify-content-center gap-3">
    <button type="button" class="btn btn-outline-secondary px-4" onclick="window.location.href='booking.php'">Back to Details</button>
    <button type="button" id="btnFinalize" class="btn btn-next px-5 fw-bold text-white" style="background: #e62429; border: none;">CONFIRM APPOINTMENT</button>
</div>
      
    </div>
  </div>
</section>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    // 1. DATA RETRIEVAL
    const services = JSON.parse(localStorage.getItem("selectedServices") || "[]");
    const details = JSON.parse(localStorage.getItem("vehicleDetails") || "{}");

    /**
     * Extra info shown for specific services on the confirmation page.
     * Add more entries here (matching the exact label used in booking.php)
     * to show similar detail cards for other services later.
     */
    const serviceDetails = {
      "PMS": {
        includes: [
          "Engine oil and oil filter change",
          "Multi-point inspection (brakes, tires, belts, and hoses)",
          "Fluid level check and top-up",
          "Air filter inspection"
        ]
      }
    };

    // 2. DISPLAY LOGIC
    const list = document.getElementById("serviceList");
    if (list && services.length) {
      services.forEach(s => {
        const info = serviceDetails[s];
        const li = document.createElement("li");
        li.className = "mb-2 text-dark fw-medium service-list-item";

        if (info) {
          li.innerHTML = `
            <div class="service-name-row">
              <span>${s}</span>
              <span class="service-info-badge"><i class="bi bi-info-circle"></i> </span>
            </div>
            <div class="service-detail-card">
              <div class="service-detail-includes-title">Includes:</div>
              <ul class="service-detail-includes mb-0">
                ${info.includes.map(item => `<li>${item}</li>`).join("")}
              </ul>
            </div>`;
        } else {
          li.textContent = s;
        }
        list.appendChild(li);
      });

      // Toggle expand/collapse for any service detail badges
      list.querySelectorAll(".service-info-badge").forEach(badge => {
        badge.addEventListener("click", () => {
          badge.closest(".service-list-item").classList.toggle("expanded");
        });
      });
    }
    
    if(details.brand) {
      document.getElementById("confirmBrand").textContent = details.brand;
      document.getElementById("confirmModel").textContent = details.model;
      document.getElementById("confirmYear").textContent = details.year;
      document.getElementById("confirmPlate").textContent = details.plate;
      document.getElementById("confirmTransmission").textContent = details.transmission;
      document.getElementById("confirmFuel").textContent = details.fuel;
    }

    // 3. THE FINALIZE LOGIC WITH SWEETALERT2
    const btnFinalize = document.getElementById("btnFinalize");
    if (btnFinalize) {
      btnFinalize.addEventListener("click", async () => {
        const token = localStorage.getItem("jwt_token");

        const bookingPayload = {
          services: services,
          vehicle: details
        };

        try {
          // Set UI to loading state
          btnFinalize.disabled = true;
          btnFinalize.innerHTML = '<span class="spinner-border spinner-border-sm"></span> PROCESSING...';

          const response = await fetch('api/save_booking.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(bookingPayload)
          });

          // Handle cases where server might return non-JSON (like a 500 error)
          const result = await response.json();

          if (result.status === "success") {
            // SUCCESS POPUP
            Swal.fire({
                title: 'Booking Successful!',
                text: 'Redirecting to your appointments...',
                icon: 'success',
                iconColor: '#e62429', // Matches your red theme
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                willClose: () => {
                    localStorage.removeItem("selectedServices");
                    localStorage.removeItem("vehicleDetails");
                    window.location.href = "my_appointments.php"; 
                }
            });
          } else {
            // SERVER ERROR TOAST
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Server Error',
                text: result.message,
                showConfirmButton: false,
                timer: 4000
            });
            btnFinalize.disabled = false;
            btnFinalize.textContent = "CONFIRM APPOINTMENT";
          }
        } catch (error) {
          // CONNECTION ERROR TOAST
          Swal.fire({
              toast: true,
              position: 'top-end',
              icon: 'error',
              title: 'Connection Failed',
              text: 'Could not connect to the server. Please check your connection.',
              showConfirmButton: false,
              timer: 4000
          });
          btnFinalize.disabled = false;
          btnFinalize.textContent = "CONFIRM APPOINTMENT";
        }
      });
    }
  });
</script>


<?php include 'includes/footer.php'; ?>