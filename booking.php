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
      <div class="step active">1</div>
      <span class="step-label">Select Service</span>
      <div class="line"></div>
      <div class="step">2</div>
      <span class="step-label">Your Details</span>
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

      <h5 class="mb-3 fw-bold text-dark">
        <i class="bi bi-tools me-2 text-danger"></i>Popular Services
      </h5>
      <div class="row g-3">
        <div class="col-6 col-md-3"><div class="service-item">PMS</div></div>
        <div class="col-6 col-md-3"><div class="service-item">Change Oil</div></div>
        <div class="col-6 col-md-3"><div class="service-item">Engine Tune Up</div></div>
        <div class="col-6 col-md-3"><div class="service-item">Auto Electrical Transmission</div></div>
        <div class="col-6 col-md-3"><div class="service-item">EFI</div></div>
        <div class="col-6 col-md-3"><div class="service-item">Carburator Services</div></div>
        <div class="col-6 col-md-3"><div class="service-item">Decarbonizer</div></div>
        <div class="col-6 col-md-3"><div class="service-item">Scanning Fault Codes</div></div>
        <div class="col-6 col-md-3"><div class="service-item">Paint Services</div></div>
        <div class="col-6 col-md-3"><div class="service-item">Back to Zero (Anti-bacterial fogging)</div></div>
      </div>

      <h5 class="mt-5 mb-3 fw-bold text-dark">
        <i class="bi bi-list-check me-2 text-danger"></i>Other Services
      </h5>
      <div class="row g-3">
        <div class="col-6 col-md-4"><div class="service-item">Car Checkup</div></div>
        <div class="col-6 col-md-4"><div class="service-item">Scheduled Maintenance</div></div>
        <div class="col-12 col-md-4"><div class="service-item">Tow/Roadside Assistance</div></div>
      </div>

      <div class="mt-3 text-muted small">
        <i class="bi bi-info-circle me-1"></i> You may select up to <strong>5 services</strong> per booking.
      </div>

      <div class="text-end mt-4">
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



<!-- ── 15-minute booking timer (persists across booking steps) ── -->
<script>
(function () {
    if (!localStorage.getItem('jwt_token')) return;

    const DURATION  = 15 * 60 * 1000; // 15 min in ms
    const WARN_AT   = 2  * 60 * 1000; // warn at 2 min
    const KEY_START = 'ejr_booking_timer_start';
    const PAGES     = ['booking.php', 'booking-details.php', 'booking-confirmation.php'];

    const currentPage = window.location.pathname.split('/').pop();
    const isFirstPage = currentPage === 'booking.php';

    // Start or resume timer
    let startTime = parseInt(localStorage.getItem(KEY_START) || '0');
    if (!startTime || isFirstPage) {
        startTime = Date.now();
        localStorage.setItem(KEY_START, startTime);
    }

    // Build timer pill
    const wrap = document.createElement('div');
    wrap.innerHTML = `
        <div id="ejrTimerInner" style="
            position:fixed; bottom:80px; left:50%; transform:translateX(-50%);
            background:#1e2a36; color:#fff; border-radius:50px;
            padding:10px 22px; font-size:13px; font-weight:500;
            display:flex; align-items:center; gap:10px;
            box-shadow:0 4px 20px rgba(0,0,0,.25); z-index:9998;
            white-space:nowrap; transition:background .4s;">
            <i class="bi bi-clock" style="font-size:15px;"></i>
            <span id="ejrTimerLabel">Booking session: <strong id="ejrTimerCount">15:00</strong></span>
        </div>`;
    document.body.appendChild(wrap);

    const inner   = document.getElementById('ejrTimerInner');
    const label   = document.getElementById('ejrTimerLabel');
    const countEl = document.getElementById('ejrTimerCount');

    function fmt(ms) {
        const s = Math.ceil(ms / 1000);
        return Math.floor(s/60) + ':' + String(s%60).padStart(2,'0');
    }

    const iv = setInterval(() => {
        const elapsed = Date.now() - startTime;
        const rem     = DURATION - elapsed;

        if (rem <= 0) {
            clearInterval(iv);
            wrap.remove();
            localStorage.removeItem(KEY_START);
            localStorage.removeItem('selectedServices');
            localStorage.removeItem('vehicleDetails');
            Swal.fire({
                icon: 'info',
                title: 'Booking Session Expired',
                text: 'Your 15-minute booking session has expired. Please start over.',
                confirmButtonColor: '#e62429',
                allowOutsideClick: false
            }).then(() => { window.location.href = 'booking.php'; });
            return;
        }

        countEl.textContent = fmt(rem);

        if (rem <= WARN_AT) {
            inner.style.background = '#e62429';
            label.innerHTML = '&#9888; Only <strong>' + fmt(rem) + '</strong> left to complete!';
        }
    }, 1000);

    // Clear timer on successful booking completion
    window.addEventListener('bookingComplete', () => {
        clearInterval(iv);
        localStorage.removeItem(KEY_START);
    });

    window.addEventListener('beforeunload', () => {
        // Don't clear — we want it to persist across booking pages
    });
})();
</script>

<?php include 'includes/footer.php'; ?>