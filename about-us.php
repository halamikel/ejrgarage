<?php 
  $pageTitle = "About Us - EJR Garage";
  include 'includes/header.php'; 
?>

<section class="about-hero" style="background: linear-gradient(rgba(5, 5, 5, 0.7), rgba(5, 5, 5, 0.7)), url('assets/images/about.jpg') center/cover no-repeat; padding: 120px 0;">
  <div class="container text-center">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <h1 style="color: #ffffff;">Locally Owned<br>and Operated</h1>
        <p class="mx-auto" style="color: #e3e8ef; font-size: 1.1rem; line-height: 1.8;">
          At EJR Garage, we take pride in delivering dependable automotive solutions for all types of vehicles. From preventive maintenance to urgent roadside help, our skilled technicians are committed to fast turnaround times, accurate diagnostics, and customer-first service.
        </p>
      </div>
    </div>
  </div>
</section>

<section class="why-section">
  <div class="container text-center">
    <h2 class="section-title mb-5">Why Choose EJR Garage?</h2>
    
    <div class="row g-4 justify-content-center">
      <div class="col-md-6 col-lg-3 d-flex">
        <div class="why-card w-100">
          <i class="bi bi-gear-fill"></i>
          <h5>Quality Service & Parts</h5>
          <p>We provide reliable service using high quality automotive parts.</p>
        </div>
      </div>
      
      <div class="col-md-6 col-lg-3 d-flex">
        <div class="why-card w-100">
          <i class="bi bi-person-check-fill"></i>
          <h5>Trusted Mechanics</h5>
          <p>Experienced professionals who ensure your vehicle is safe.</p>
        </div>
      </div>
      
      <div class="col-md-6 col-lg-3 d-flex">
        <div class="why-card w-100">
          <i class="bi bi-emoji-smile-fill"></i>
          <h5>Customer Satisfaction</h5>
          <p>Your satisfaction and safety are always our top priority.</p>
        </div>
      </div>
      
      <div class="col-md-6 col-lg-3 d-flex">
        <div class="why-card w-100">
          <i class="peso-icon">₱</i>
          <h5>Affordable Pricing</h5>
          <p>Fair pricing without sacrificing quality service.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="map-section">
  <div class="container text-center">
    <h5 class="section-title mb-4">GET DIRECTIONS</h5>
    <div class="map-box">
      <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3849.653766482857!2d120.57582317425096!3d15.232102585332552!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3396ed92e49fc18b%3A0x1371f631ba9a7f8e!2sEJR%20Garage!5e0!3m2!1sen!2sph!4v1773301971469!5m2!1sen!2sph" 
              width="100%" 
              height="450" 
              style="border:0; border-radius: 8px;" 
              allowfullscreen="" 
              loading="lazy" 
              referrerpolicy="no-referrer-when-downgrade">
      </iframe>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>