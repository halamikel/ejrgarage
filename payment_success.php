<?php
require_once 'api/db_config.php';
require_once 'api/paymongo_helper.php';

$pageTitle = "Payment Successful - EJR Garage";
$order_id = (int)($_GET['order_id'] ?? 0);

$verified = false;
$errorMsg = '';

if ($order_id > 0) {
    $stmt = $conn->prepare("SELECT paymongo_intent_id, payment_status FROM part_orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if ($order && $order['paymongo_intent_id']) {
        if ($order['payment_status'] === 'paid') {
            $verified = true; // already confirmed previously
        } else {
            $session = paymongo_get_checkout($order['paymongo_intent_id']);
            $paymentStatus = $session['attributes']['payment_intent']['attributes']['status'] ?? null;

            if ($paymentStatus === 'succeeded') {
                $upd = $conn->prepare("UPDATE part_orders SET payment_status = 'paid' WHERE id = ?");
                $upd->bind_param("i", $order_id);
                $upd->execute();
                $verified = true;
            } else {
                $errorMsg = "Payment not yet confirmed (status: " . htmlspecialchars($paymentStatus ?? 'unknown') . "). If you just paid, please refresh this page in a few seconds.";
            }
        }
    } else {
        $errorMsg = "Order not found.";
    }
} else {
    $errorMsg = "Missing order reference.";
}

include 'includes/header.php';
?>

<section class="py-5" style="min-height:60vh; display:flex; align-items:center;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6 text-center">
        <div class="card border-0 shadow-sm rounded-4 p-5">
        <?php if ($verified): ?>
          <i class="bi bi-check-circle-fill" style="font-size:4.5rem; color:#198754;"></i>
          <h2 class="fw-bold mt-4 mb-2" style="color:#1e2a36;">Payment Successful!</h2>
          <p class="text-muted mb-4">Your payment for Order #<?= $order_id ?> has been received. We'll process it shortly.</p>
        <?php else: ?>
          <i class="bi bi-exclamation-circle-fill" style="font-size:4.5rem; color:#ffc107;"></i>
          <h2 class="fw-bold mt-4 mb-2" style="color:#1e2a36;">Payment Pending</h2>
          <p class="text-muted mb-4"><?= $errorMsg ?></p>
        <?php endif; ?>
        <a href="my_orders.php" class="btn fw-bold px-5 py-2"
           style="background-color:#dc3545; color:#fff; border:none; border-radius:8px; font-size:1rem; display:inline-block;">
          View My Orders
        </a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>