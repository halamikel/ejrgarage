<?php 
    session_start();
    $pageTitle = "EJR Garage - Home";
    $bodyClass = "landing-page";
    include 'includes/header.php'; 
?>

<section class="hero" style="background: linear-gradient(rgba(5, 5, 5, 0.7), rgba(5, 5, 5, 0.7)), url('assets/images/index1.png') center/cover no-repeat;">
  <div class="container">
    <div class="row">
      <div class="col-lg-7">
        <div class="subtitle">Fast • Reliable • Professional</div>
        <h1 style="color: #ffffff;">Your Trusted Auto Repair Shop</h1>
        <p style="color: #e3e8ef;">Quality automotive maintenance, diagnostics, emergency rescue, and home service solutions for every vehicle owner.</p>
        <div class="d-flex flex-wrap gap-3">
          <a href="booking-details.php" class="btn btn-yellow" style="display:inline-flex; justify-content:center; align-items:center; text-align:center;">Book Now</a>
          <a href="about-us.php" class="btn btn-outline-light-custom">Explore More</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="services-intro py-5" id="about">
    <div class="container text-center">
        <h2 class="section-title">Quality Car Repair and Auto Parts</h2>
        <h5 class="fw-semibold text-dark">Mabalacat, Pampanga</h5>
        <div class="mini-line mx-auto"></div>
        <p class="lead-text mx-auto text-dark" style="max-width: 850px;">
            We take pride in delivering dependable automotive solutions for all types of vehicles. From preventive maintenance to urgent roadside help, our skilled technicians are committed to fast turnaround times, accurate diagnostics, and customer-first service.
        </p>
    </div>

    <div class="container mt-4">
        <div class="row justify-content-center g-4" id="services">
            <div class="col-md-5 col-lg-4">
                <div class="service-box red">
                    <i class="bi bi-truck"></i>
                    <h5>Emergency Rescue</h5>
                    <p>Immediate support for breakdowns and urgent roadside car issues.</p>
                </div>
            </div>
            <div class="col-md-5 col-lg-4">
                <div class="service-box blue">
                    <i class="bi bi-tools"></i>
                    <h5>Maintenance Services</h5>
                    <p>Convenient on-site vehicle checks, repairs, and basic maintenance.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="stats py-5 bg-dark text-white">
  <div class="container">
    <div class="row g-4">
      <div class="col-6 col-md-3">
        <div class="stat-item text-center">
          <div class="number h2 fw-bold text-danger">40+</div>
          <div class="label small text-uppercase text-white">Years in Service</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-item text-center">
          <div class="number h2 fw-bold text-danger">5,000+</div>
          <div class="label small text-uppercase text-white">Satisfied Clients</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-item text-center">
          <div class="number h2 fw-bold text-danger">4.6</div>
          <div class="label small text-uppercase text-white">Average Rating</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-item text-center">
          <div class="number h2 fw-bold text-danger">24/7</div>
          <div class="label small text-uppercase text-white">Emergency Response</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Google Reviews / Jotform Widget -->
<section class="py-5" style="background: #f8f9fa;">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="section-title">What Our Customers Say</h2>
      <div class="mini-line mx-auto"></div>
    </div>
    
    <div id="JFWebsiteWidget-01a032ad4f7070008426079b5956f32824a3"></div>
    <script src="https://www.jotform.com/website-widgets/embed/01a032ad4f7070008426079b5956f32824a3"></script>

  </div>
</section>

<?php 
    include 'includes/footer.php'; 
?>