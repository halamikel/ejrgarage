<?php 
  $pageTitle = "Contact - EJR Garage";
  include 'includes/header.php'; 
?>

<section class="contact-hero text-center">
  <div class="container">
    <div class="contact-hero-icon"><i class="bi bi-envelope"></i></div>
    <h1>Get In Touch</h1>
    <p>We're here to help with all your automotive needs.</p>
  </div>
</section>

<section class="contact-details-section">
  <div class="container">
    <h2 class="contact-section-title section-title text-center">Contact Information</h2>
    <div class="row g-4 mt-2">
      <div class="col-md-4">
        <div class="contact-info-card h-100 text-center">
          <div class="contact-info-icon">📞</div>
          <h6>Call Us</h6>
          <p>0906-704-5422</p>
          <small>Emergency Line</small>
        </div>
      </div>
      <div class="col-md-4">
        <div class="contact-info-card h-100 text-center">
          <div class="contact-info-icon">✉️</div>
          <h6>Email Us</h6>
          <p>ejrgaragetest@gmail.com</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="contact-info-card h-100 text-center">
          <div class="contact-info-icon"><i class="bi bi-facebook"></i></div>
          <h6>Message Us</h6>
          <p>Raymond Dale Yandan</p>
          <a href="https://www.facebook.com/raymonddale1?mibextid=wwXIfr&rdid=QOKimwkysEpBxhhR&share_url=https%3A%2F%2Fwww.facebook.com%2Fshare%2F19a9uVQcDH%2F%3Fmibextid%3DwwXIfr" target="_blank" class="btn btn-outline-orange">
            <i class="bi bi-facebook"></i> Chat on Facebook
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="contact-form-section">
  <div class="container">
    <div class="contact-form-card">
      <div class="row g-4 align-items-center">
        <div class="col-lg-5">
          <h3>Send Us a Message</h3>
          <p>Have questions about our services, pricing, or appointment availability? Fill out the form and our team will get back to you.</p>
        </div>
        <div class="col-lg-7">
          <form id="contactForm">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Full Name</label><input type="text" id="cf_name" class="form-control" placeholder="Enter your full name" required></div>
              <div class="col-md-6"><label class="form-label">Phone Number</label><input type="text" id="cf_phone" class="form-control" placeholder="Enter your phone number" required></div>
              <div class="col-md-6"><label class="form-label">Email Address</label><input type="email" id="cf_email" class="form-control" placeholder="Enter your email" required></div>
              <div class="col-md-6"><label class="form-label">Subject</label><input type="text" id="cf_subject" class="form-control" placeholder="Enter subject" required></div>
              <div class="col-12"><label class="form-label">Message</label><textarea id="cf_message" class="form-control" rows="5" placeholder="Write your message here" required></textarea></div>
              <div class="col-12">
                <button type="submit" id="contactSubmitBtn" class="btn btn-next">Send Message</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
document.getElementById('contactForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const btn = document.getElementById('contactSubmitBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Sending...';

    const payload = {
        name:    document.getElementById('cf_name').value.trim(),
        phone:   document.getElementById('cf_phone').value.trim(),
        email:   document.getElementById('cf_email').value.trim(),
        subject: document.getElementById('cf_subject').value.trim(),
        message: document.getElementById('cf_message').value.trim(),
    };

    fetch('api/send_contact_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Message Sent!',
                text: "We'll get back to you as soon as possible.",
                confirmButtonColor: '#e62429'
            });
            document.getElementById('contactForm').reset();
        } else {
            Swal.fire('Error', data.message || 'Failed to send message.', 'error');
        }
    })
    .catch(() => {
        Swal.fire('Error', 'Could not connect to server. Please try again.', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});
</script>

<?php include 'includes/footer.php'; ?>