<?php 
  $pageTitle = "Book Appointment - EJR Garage";
  $extraJs = '<script src="js/booking.js"></script>';
  include 'includes/header.php'; 
?>
<script>
    const userLoggedIn = !!localStorage.getItem('jwt_token');
</script>

<section class="appointment-header text-center">
  <div class="container">
    <h1>Book Your Appointment</h1>
    <p>To provide our visitors with the best car service</p>
  </div>
</section>

<section class="steps py-4">
  <div class="container">
    <div class="step-wrapper">
      <div class="step">1</div>
      <span class="step-label">Your Details</span>
      <div class="line"></div>
      <div class="step active">2</div>
      <span class="step-label">Select Service</span>
      <div class="line"></div>
      <div class="step">3</div>
      <span class="step-label">Confirm</span>
    </div>
  </div>
</section>

<section class="services-section">
  <div class="container">
    <h4 class="mb-4 fw-bold">Select Your Service</h4>
    <div class="service-card">

      <div id="servicesLoading" class="text-center text-muted py-4">
        <div class="spinner-border spinner-border-sm text-danger me-2"></div> Loading services...
      </div>

      <div id="servicesDynamicContainer" style="display:none;"></div>

      <div class="mt-3 text-muted small">
        <i class="bi bi-info-circle me-1"></i> You may select up to <strong>5 services</strong> per booking.
      </div>

      <div class="d-flex justify-content-between align-items-center mt-4">
        <button type="button" class="btn btn-outline-secondary px-4 fw-bold"
                onclick="window.location.href='booking-details.php'">
          <i class="bi bi-arrow-left me-1"></i> Back to Details
        </button>
        <button class="btn btn-next px-5">Next <i class="bi bi-arrow-right ms-1"></i></button>
      </div>

      <div class="mt-3" id="serviceError" style="display:none;">
        <div class="alert alert-danger py-2 mb-0">
          <i class="bi bi-exclamation-circle me-1"></i> Please select at least one service before continuing.
        </div>
      </div>
      <div class="mt-3" id="serviceLimitError" style="display:none;">
        <div class="alert alert-warning py-2 mb-0">
          <i class="bi bi-exclamation-triangle me-1"></i> You can select up to 5 services only per booking.
        </div>
      </div>

    </div>
  </div>
</section>

<script>
// ── Load services from admin-managed list (falls back to defaults) ──
(function () {
    const loadingEl   = document.getElementById('servicesLoading');
    const containerEl = document.getElementById('servicesDynamicContainer');

    fetch('api/get_services.php')
        .then(res => res.json())
        .then(data => {
            loadingEl.style.display = 'none';
            containerEl.style.display = 'block';

            if (data.status !== 'success' || !data.services.length) {
                containerEl.innerHTML = '<p class="text-muted">No services available right now. Please contact us directly.</p>';
                return;
            }

            // Group by category
            const grouped = {};
            data.services.forEach(s => {
                const cat = s.category || 'Services';
                if (!grouped[cat]) grouped[cat] = [];
                grouped[cat].push(s);
            });

            // Popular Services always shown first, then remaining categories alphabetically
            const categoryOrder = Object.keys(grouped).sort((a, b) => {
                if (a === 'Popular Services') return -1;
                if (b === 'Popular Services') return 1;
                return a.localeCompare(b);
            });

            let html = '';
            categoryOrder.forEach((cat, idx) => {
                html += `
                    <h5 class="${idx > 0 ? 'mt-5' : ''} mb-3 fw-bold text-dark">
                        <i class="bi bi-tools me-2 text-danger"></i>${cat}
                    </h5>
                    <div class="row g-3">
                        ${grouped[cat].map(s => `
                            <div class="col-6 col-md-3">
                                <div class="service-item" ${s.price_from > 0 ? `data-price="${s.price_from}"` : ''}>
                                    ${s.name}
                                </div>
                            </div>
                        `).join('')}
                    </div>`;
            });
            containerEl.innerHTML = html;

            // booking.js's DOMContentLoaded already ran before this fetch
            // resolved, so its click bindings never saw these elements.
            // Dispatch a custom event booking.js listens for to re-bind.
            document.dispatchEvent(new CustomEvent('ejrServicesLoaded'));
        })
        .catch(() => {
            loadingEl.innerHTML = '<p class="text-danger">Could not load services. Please refresh the page.</p>';
        });
})();
</script>

<style>
.step-label {
  font-size: 13px;
  font-weight: 600;
  color: #5f6b7a;
}
@media (max-width: 576px) {
  .step-label {
    font-size: 11px;
  }
  .line {
    width: 30px !important;
  }
  .service-item {
    font-size: 13px;
    padding: 12px 8px;
    height: 84px !important;
    min-height: 84px !important;
    line-height: 1.3;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    text-align: center !important;
  }
  .appointment-header {
    padding: 40px 0;
  }
  .appointment-header h1 {
    font-size: 1.8rem;
  }
  .service-card {
    padding: 20px !important;
  }
}
</style>



<?php include 'includes/footer.php'; ?>