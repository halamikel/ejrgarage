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
        <h2 class="section-title text-center mb-4">Our Services</h2>
        <div class="ejr-services-panel">
            <div class="row g-4 text-center" id="services">
                <div class="col-6 col-md-3">
                    <div class="ejr-service-card">
                        <div class="ejr-service-icon">🚗</div>
                        <h6>Home Service</h6>
                        <p>We come to you for routine maintenance, tire changes, and minor repairs anywhere in Pampanga.</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="ejr-service-card">
                        <div class="ejr-service-icon">🔧</div>
                        <h6>General Repair</h6>
                        <p>Full mechanical diagnostics and repair for all vehicle makes and models by expert technicians.</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="ejr-service-card">
                        <div class="ejr-service-icon">🛞</div>
                        <h6>Tire Services</h6>
                        <p>Tire rotation, balancing, alignment, and replacement with top-brand tires in stock.</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="ejr-service-card">
                        <div class="ejr-service-icon">⚡</div>
                        <h6>Electrical &amp; AC</h6>
                        <p>Complete electrical system diagnostics, battery replacement, and full aircon servicing.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5" style="background:#0a1128;">
  <div class="container">
    <div class="row g-4">
      <div class="col-6 col-md-3">
        <div class="stat-item text-center">
          <div class="number h2 fw-bold" style="color:#ff4d3c;">5,000+</div>
          <div class="label small text-uppercase text-white-50">Cars Serviced</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-item text-center">
          <div class="number h2 fw-bold" style="color:#ff4d3c;">40+</div>
          <div class="label small text-uppercase text-white-50">Years Experience</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-item text-center">
          <div class="number h2 fw-bold" style="color:#ff4d3c;">4.6 ★</div>
          <div class="label small text-uppercase text-white-50">Google Rating</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-item text-center">
          <div class="number h2 fw-bold" style="color:#ff4d3c;">24/7</div>
          <div class="label small text-uppercase text-white-50">Emergency Support</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- What Our Customers Say / Google Reviews -->
<section class="py-5" style="background:#f5f7fa;">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="section-title">What Our <span style="text-decoration:underline; text-decoration-color:var(--accent-orange); text-decoration-thickness:3px;">Customers Say</span></h2>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h6 class="fw-bold mb-1">Google Reviews</h6>
            <div class="d-flex align-items-center gap-2">
                <span class="h4 fw-bold mb-0">4.6</span>
                <span style="color:#fbbc04; letter-spacing:2px;">★★★★★</span>
                <span class="text-muted">(14)</span>
            </div>
        </div>
        <a href="contact.php" class="btn btn-primary" style="border-radius:8px;">Share your feedback</a>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <div class="ejr-review-card">
                <div class="ejr-review-stars">★★★★★</div>
                <p class="ejr-review-text">Solid mechanics, very friendly and accomodating.</p>
                <div class="ejr-review-author">
                    <div class="ejr-review-avatar">LS</div>
                    <div>
                        <div class="fw-semibold">Li Sevidal</div>
                        <div class="text-muted small">8 months ago</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="ejr-review-card">
                <div class="ejr-review-stars">★★★★★</div>
                <p class="ejr-review-text">Best quality automotive repair at a budget-friendly price. The mechanics know what they're doing and gets the job done the right way. Definitely five stars.</p>
                <div class="ejr-review-author">
                    <div class="ejr-review-avatar">GF</div>
                    <div>
                        <div class="fw-semibold">Gerard Farin</div>
                        <div class="text-muted small">3 years ago</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="ejr-review-card">
                <div class="ejr-review-stars">★★★★★</div>
                <p class="ejr-review-text">It was a pleasant experience visiting here, bc the mechanics were fast in diagnosis of the problems on my car, and acted fast to get me back on the road.</p>
                <div class="ejr-review-author">
                    <div class="ejr-review-avatar">IK</div>
                    <div>
                        <div class="fw-semibold">Isaac Kim</div>
                        <div class="text-muted small">3 years ago</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="ejr-review-card">
                <div class="ejr-review-stars">★★★★★</div>
                <p class="ejr-review-text">Very accommodating and honest service.</p>
                <div class="ejr-review-author">
                    <div class="ejr-review-avatar">CE</div>
                    <div>
                        <div class="fw-semibold">Carl Ezekiel Geronimo</div>
                        <div class="text-muted small">9 months ago</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="text-end text-muted small mt-2">Powered by Google</div>
  </div>
</section>

<!-- Ready to Book CTA -->
<section class="py-5 text-center text-white" style="background:var(--accent-orange);">
    <div class="container">
        <h2 class="fw-bold mb-2" style="font-family: var(--font-display);">Ready to Book a Service?</h2>
        <p class="mb-4">Schedule your appointment today and our technicians will take care of everything.</p>
        <a href="booking-details.php" class="btn btn-light fw-bold px-4 py-2" style="color: var(--accent-orange); border-radius:8px;">Book Now</a>
    </div>
</section>

<style>
.ejr-services-panel {
    background: #fafbfc;
    border: 1px solid var(--border-light);
    border-radius: 16px;
    padding: 40px 20px;
}
.ejr-service-card {
    padding: 10px;
}
.ejr-service-icon {
    font-size: 2.2rem;
    margin-bottom: 12px;
}
.ejr-service-card h6 {
    font-family: var(--font-display);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    color: var(--text-dark);
}
.ejr-service-card p {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-bottom: 0;
}
.ejr-review-card {
    background: #fff;
    border: 1px solid var(--border-light);
    border-radius: 12px;
    padding: 24px;
    height: 100%;
    display: flex;
    flex-direction: column;
}
.ejr-review-stars {
    color: #fbbc04;
    letter-spacing: 2px;
    margin-bottom: 12px;
}
.ejr-review-text {
    color: var(--text-dark);
    font-size: 0.9rem;
    flex-grow: 1;
}
.ejr-review-author {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 16px;
}
.ejr-review-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #1a73e8;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    flex-shrink: 0;
}
</style>

<?php 
    include 'includes/footer.php'; 
?>