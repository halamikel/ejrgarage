<?php
  $pageTitle = "My Cart - EJR Garage";
  include 'includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<section class="appointment-header text-center">
  <div class="container">
    <h1>My Cart</h1>
    <p>Review your selected parts before placing an order</p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">

        <div class="d-flex justify-content-between align-items-center mb-4">
          <a href="available_parts.php" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Continue Shopping
          </a>
          <button class="btn btn-outline-danger btn-sm" onclick="clearCart()" id="clearBtn" style="display:none;">
            <i class="bi bi-trash me-1"></i> Clear Cart
          </button>
        </div>

        <!-- Cart Items -->
        <div id="cartContainer"></div>

        <!-- Order Form -->
        <div id="cartSummary" style="display:none;">
          <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center">
              <span class="fw-bold fs-5">Total</span>
              <span class="fw-bold fs-4 text-danger" id="cartTotal">₱0.00</span>
            </div>
          </div>

          <div class="card border-0 shadow-sm rounded-4 p-4">
            <h5 class="fw-bold mb-4 border-bottom pb-2">
              <i class="bi bi-bag-check me-2 text-danger"></i>Place Order
            </h5>
            <form id="orderForm">
              <div class="mb-3">
                <label class="form-label fw-semibold small text-muted text-uppercase">Your Name</label>
                <input type="text" id="orderCustomerName" class="form-control shadow-sm" placeholder="Juan Dela Cruz" required>
              </div>
              <div class="mb-4">
                <label class="form-label fw-semibold small text-muted text-uppercase">Contact Number</label>
                <input type="text" id="orderContact" class="form-control shadow-sm" placeholder="09XX-XXX-XXXX" required>
              </div>

              <div class="mb-4">
                <label class="form-label fw-semibold small text-muted text-uppercase mb-2">Payment Method</label>
                <div class="payment-option-card">
                  <input type="radio" name="paymentMethod" id="payOnline" value="online" checked>
                  <label for="payOnline">
                    <i class="bi bi-credit-card fs-4 me-2"></i>
                    <div>
                      <div class="fw-bold">Pay Online</div>
                      <div class="small text-muted">Card or QRPH via secure checkout</div>
                    </div>
                  </label>
                </div>
                <div class="payment-option-card">
                  <input type="radio" name="paymentMethod" id="payCod" value="cod">
                  <label for="payCod">
                    <i class="bi bi-cash-coin fs-4 me-2"></i>
                    <div>
                      <div class="fw-bold">Cash on Delivery</div>
                      <div class="small text-muted">Pay in cash upon pickup/delivery</div>
                    </div>
                  </label>
                </div>
              </div>

              <button type="submit" class="btn btn-danger w-100 fw-bold py-2"
                      style="background-color:#dc3545!important;border:none!important;">
                <i class="bi bi-bag-check-fill me-2"></i>Place Order
              </button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<style>
.cart-item-card {
    border: none;
    border-radius: 14px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    margin-bottom: 14px;
    transition: box-shadow 0.2s;
}
.cart-item-card:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.qty-btn {
    width: 30px;
    height: 30px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    border-radius: 50% !important;
}
.payment-option-card {
    position: relative;
    margin-bottom: 10px;
}
.payment-option-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}
.payment-option-card label {
    display: flex;
    align-items: center;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 12px 16px;
    cursor: pointer;
    transition: all 0.15s;
    margin: 0;
    color: #495057;
}
.payment-option-card input[type="radio"]:checked + label {
    border-color: #dc3545;
    background: #fff5f5;
    color: #1e2a36;
}
.payment-option-card input[type="radio"]:checked + label i {
    color: #dc3545;
}
</style>

<script>
let cart = JSON.parse(localStorage.getItem('ejr_cart') || '[]');

// ── REFRESH USER FROM API ─────────────────────────────────────
function refreshUserFromApi() {
    const token = localStorage.getItem('jwt_token');
    if (!token) return;
    fetch('api/get_profile.php', {
        method: 'GET',
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' && data.user) {
            localStorage.setItem('user', JSON.stringify(data.user));
            prefillUser(data.user);
        }
    })
    .catch(err => console.warn('Could not refresh user:', err));
}

function prefillUser(user) {
    let fullName = '';
    if (user.full_name) {
        const nameParts = user.full_name.trim().split(' ');
        const lastName  = nameParts.length > 1 ? nameParts.pop() : '';
        const firstName = nameParts.join(' ');
        fullName = firstName + (lastName ? ' ' + lastName : '');
    }
    document.getElementById('orderCustomerName').value = fullName;
    document.getElementById('orderContact').value      = user.phone || '';
}

document.addEventListener('DOMContentLoaded', () => {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    if (user.full_name) prefillUser(user);
    refreshUserFromApi();
    renderCart();
});

// ── RENDER CART ───────────────────────────────────────────────
function renderCart() {
    const container = document.getElementById('cartContainer');
    const summary   = document.getElementById('cartSummary');
    const clearBtn  = document.getElementById('clearBtn');

    if (!cart.length) {
        container.innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="bi bi-cart-x fs-1 d-block mb-3"></i>
                <h5>Your cart is empty</h5>
                <a href="available_parts.php" class="btn mt-3 px-4 fw-bold"
                   style="background-color:#dc3545; color:#fff; border:none; opacity:1;">
                    <i class="bi bi-arrow-left me-1"></i> Browse Parts
                </a>
            </div>`;
        summary.style.display  = 'none';
        clearBtn.style.display = 'none';
        return;
    }

    const total = cart.reduce((sum, item) => sum + item.price * item.qty, 0);

    container.innerHTML = cart.map((item, idx) => `
        <div class="card cart-item-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold">${item.name}</div>
                    <div class="text-muted small">₱${item.price.toLocaleString('en-PH', {minimumFractionDigits:2})} each</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary qty-btn" onclick="changeQty(${idx}, -1)">−</button>
                    <span class="fw-bold px-1">${item.qty}</span>
                    <button class="btn btn-outline-secondary qty-btn" onclick="changeQty(${idx}, 1)">+</button>
                    <span class="fw-bold text-danger ms-3" style="min-width:80px;text-align:right;">
                        ₱${(item.price * item.qty).toLocaleString('en-PH', {minimumFractionDigits:2})}
                    </span>
                    <button class="btn btn-sm btn-outline-danger qty-btn ms-2" onclick="removeItem(${idx})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>`).join('');

    document.getElementById('cartTotal').textContent =
        '₱' + total.toLocaleString('en-PH', {minimumFractionDigits:2});

    summary.style.display  = 'block';
    clearBtn.style.display = 'inline-block';
}

function saveCart() {
    localStorage.setItem('ejr_cart', JSON.stringify(cart));
}

function changeQty(idx, delta) {
    cart[idx].qty += delta;
    if (cart[idx].qty <= 0) cart.splice(idx, 1);
    saveCart();
    renderCart();
}

function removeItem(idx) {
    cart.splice(idx, 1);
    saveCart();
    renderCart();
}

function clearCart() {
    Swal.fire({
        title: 'Clear cart?',
        text: 'All items will be removed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, clear it'
    }).then(result => {
        if (result.isConfirmed) {
            cart = [];
            saveCart();
            renderCart();
        }
    });
}

// ── PLACE ORDER SUBMIT ────────────────────────────────────────
document.getElementById('orderForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const token = localStorage.getItem('jwt_token');
    if (!token) {
        Swal.fire('Not logged in', 'Please log in to place an order.', 'warning');
        return;
    }

    const btn = this.querySelector('[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Placing Order...';

    const total = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;

    fetch('api/place_order.php', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + token
        },
        body: JSON.stringify({
            customer_name:   document.getElementById('orderCustomerName').value,
            contact:         document.getElementById('orderContact').value,
            items:           cart,
            total_price:     total,
            payment_method:  paymentMethod
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            cart = [];
            saveCart();
            if (data.checkout_url) {
                window.location.href = data.checkout_url; // → PayMongo sandbox checkout (QRPH / Card)
            } else {
                Swal.fire({
                    icon: 'success',
                    title: 'Order Placed!',
                    text: paymentMethod === 'cod'
                        ? 'Your order is pending. Please prepare exact cash for delivery/pickup.'
                        : 'Your order is now pending. We will contact you shortly.',
                    confirmButtonColor: '#e62429'
                }).then(() => {
                    window.location.href = 'available_parts.php';
                });
            }
        } else {
            Swal.fire('Error', data.message || 'Failed to place order.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-bag-check-fill me-2"></i>Place Order';
        }
    })
    .catch(() => {
        Swal.fire('Error', 'Failed to connect to server.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-bag-check-fill me-2"></i>Place Order';
    });
});
</script>

<?php include 'includes/footer.php'; ?>