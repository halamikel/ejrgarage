<?php
$pageTitle = "Download App - EJR Garage";
include 'includes/header.php';
?>

<section class="py-5" style="background: linear-gradient(135deg, rgba(230,36,41,0.06), rgba(255,107,0,0.06)); min-height: 80vh; display:flex; align-items:center;">
  <div class="container">
    <div class="row align-items-center g-5">

      <!-- Left: Phone mockup -->
      <div class="col-lg-5 text-center order-lg-1 order-2">
        <div style="position:relative; display:inline-block;">
          <div style="
              width: 260px; height: 520px; margin: 0 auto;
              background: #1e2a36; border-radius: 36px; padding: 14px;
              box-shadow: 0 30px 60px rgba(0,0,0,0.25);
              position: relative;">
            <div style="
                width: 100%; height: 100%; border-radius: 24px; overflow:hidden;
                background: linear-gradient(160deg, #e62429, #ff6b00);
                display:flex; flex-direction:column; align-items:center; justify-content:center;
                position:relative;">
              <div style="position:absolute; top:18px; left:50%; transform:translateX(-50%); width:70px; height:6px; background:rgba(255,255,255,0.4); border-radius:10px;"></div>
              <img src="/assets/images/Inverted_EJRLogo.png" onerror="this.style.display='none'" alt="EJR Garage" style="width:90px; margin-bottom:16px;">
              <div style="color:#fff; font-family:var(--font-display); font-weight:700; font-size:1.4rem; letter-spacing:1px;">EJR GARAGE</div>
              <div style="color:rgba(255,255,255,0.85); font-size:0.85rem; margin-top:4px;">Automobile Services</div>
              <i class="bi bi-wrench-adjustable-circle" style="font-size:4rem; color:rgba(255,255,255,0.25); margin-top:30px;"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Download info -->
      <div class="col-lg-7 order-lg-2 order-1">
        <p class="fw-bold text-uppercase mb-2" style="color: var(--primary-red); letter-spacing: 2px; font-size: 0.85rem;">
          <i class="bi bi-android2 me-1"></i> Now on Android
        </p>
        <h1 class="fw-bold mb-3" style="font-family: var(--font-display); color: var(--text-dark); font-size: 2.6rem;">
          Take EJR Garage <br>Wherever You Go
        </h1>
        <p class="text-muted mb-4" style="font-size: 1.05rem; max-width: 520px;">
          Book appointments, track your vehicle's service history, chat with our team, and manage your parts orders — all from your phone.
        </p>

        <div class="d-flex flex-wrap gap-3 mb-4">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-calendar-check-fill" style="color: var(--primary-red); font-size:1.3rem;"></i>
            <span class="text-dark fw-semibold small">Book & Track Appointments</span>
          </div>
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-chat-dots-fill" style="color: var(--primary-red); font-size:1.3rem;"></i>
            <span class="text-dark fw-semibold small">Live Chat Support</span>
          </div>
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-truck" style="color: var(--primary-red); font-size:1.3rem;"></i>
            <span class="text-dark fw-semibold small">Manage Your Vehicles</span>
          </div>
        </div>

        <!-- Download button -->
        <a href="/assets/downloads/EJRGarage.apk"
           id="downloadApkBtn"
           class="btn d-inline-flex align-items-center gap-3"
           style="background: linear-gradient(45deg, #e62429, #ff6b00); color:#fff; padding: 16px 32px; border-radius: 14px; text-decoration:none; box-shadow: 0 10px 30px rgba(230,36,41,0.3);">
          <i class="bi bi-android2" style="font-size: 2.2rem;"></i>
          <span class="text-start">
            <span class="d-block" style="font-size:0.72rem; opacity:0.9;">DOWNLOAD FOR</span>
            <span class="d-block fw-bold" style="font-family: var(--font-display); font-size:1.3rem; line-height:1.1;">Android</span>
          </span>
        </a>

        <p class="text-muted small mt-3 mb-0">
          <i class="bi bi-info-circle me-1"></i>
          Requires Android 8.0 or higher. You may need to allow installs from unknown sources in your phone settings.
        </p>

        <div class="mt-4 pt-3 border-top" style="max-width: 520px; border-color: rgba(0,0,0,0.08) !important;">
          <p class="small text-muted mb-2 fw-semibold">Installation steps:</p>
          <ol class="small text-muted ps-3 mb-0">
            <li>Tap the download button above on your Android phone.</li>
            <li>Open the downloaded <code>.apk</code> file.</li>
            <li>If prompted, allow installation from this source.</li>
            <li>Tap <strong>Install</strong> and open the app once complete.</li>
          </ol>
        </div>
      </div>

    </div>
  </div>
</section>

<script>
document.getElementById('downloadApkBtn').addEventListener('click', function (e) {
    // Friendly heads-up if the APK hasn't been uploaded yet
    fetch(this.getAttribute('href'), { method: 'HEAD' })
        .then(res => {
            if (!res.ok) {
                e.preventDefault();
                Swal.fire({
                    icon: 'info',
                    title: 'App Coming Soon',
                    text: 'The Android app is being finalized. Please check back shortly!',
                    confirmButtonColor: '#e62429'
                });
            }
        })
        .catch(() => {
            e.preventDefault();
            Swal.fire({
                icon: 'info',
                title: 'App Coming Soon',
                text: 'The Android app is being finalized. Please check back shortly!',
                confirmButtonColor: '#e62429'
            });
        });
});
</script>

<?php include 'includes/footer.php'; ?>
