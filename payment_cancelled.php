<?php
$pageTitle = "Payment Cancelled - EJR Garage";
$order_id = (int)($_GET['order_id'] ?? 0);
include 'includes/header.php';
?>

<section class="py-5" style="min-height:60vh; display:flex; align-items:center;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6 text-center">
        <div class="card border-0 shadow-sm rounded-4 p-5">
          <i class="bi bi-x-circle-fill" style="font-size:4.5rem; color:#dc3545;"></i>
          <h2 class="fw-bold mt-4 mb-2" style="color:#1e2a36;">Payment Cancelled</h2>
          <p class="text-muted mb-4">
            Your order #<?= $order_id ?> was saved but payment wasn't completed.
            You can retry payment anytime from your orders page.
          </p>
          <a href="my_inquiries.php" class="btn fw-bold px-5 py-2"
             style="background-color:#dc3545; color:#fff; border:none; border-radius:8px; font-size:1rem; display:inline-block;">
            View My Orders
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>