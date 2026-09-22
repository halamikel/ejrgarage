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

              <!-- =====================================================
                   SAVED PROFILES
              ====================================================== -->
              <div id="savedProfilesSection" class="mb-4" style="display:none;">
                <label class="form-label fw-bold text-dark" for="savedProfileSelect">
                  <i class="bi bi-person-lines-fill me-2 text-danger"></i>Use a Saved Profile
                </label>
                <select class="form-select form-select-lg" id="savedProfileSelect" style="border-color:#e62429;">
                  <option value="">-- Select a saved profile --</option>
                </select>
                <div class="text-end mt-2">
                  <button type="button" class="btn btn-sm text-danger fw-bold" id="btnAddNewProfile">
                    <i class="bi bi-plus-circle me-1"></i> Add a new profile
                  </button>
                </div>
              </div>

              <!-- =====================================================
                   MANUAL PROFILE FORM
              ====================================================== -->
              <div id="manualProfileForm">
                <p class="text-muted small mb-3">
                  <i class="bi bi-info-circle me-1"></i>
                  Tell us who this order is for and where to deliver or pick it up.
                </p>

                <div class="mb-3">
                  <label class="form-label fw-semibold small text-muted text-uppercase">Full Name</label>
                  <input type="text" id="orderCustomerName" class="form-control shadow-sm" placeholder="Juan Dela Cruz" required>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold small text-muted text-uppercase">Contact Number</label>
                  <div class="phone-input-wrap">
                    <span class="phone-prefix">+63</span>
                    <input
                      type="tel"
                      id="orderContact"
                      class="form-control shadow-sm"
                      placeholder="9XXXXXXXXX"
                      inputmode="numeric"
                      maxlength="10"
                      autocomplete="off"
                      required
                    >
                  </div>
                  <div class="form-text">Enter your 10-digit mobile number, numbers only.</div>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold small text-muted text-uppercase">Address Line 1</label>
                  <input type="text" id="orderAddress1" class="form-control shadow-sm" placeholder="House/Unit No., Street Name" required>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold small text-muted text-uppercase">
                    Address Line 2 <span class="fw-normal text-muted text-normal">(optional)</span>
                  </label>
                  <input type="text" id="orderAddress2" class="form-control shadow-sm" placeholder="Barangay, Subdivision, Landmark">
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-6 col-md-5">
                    <label class="form-label fw-semibold small text-muted text-uppercase">City / Town</label>
                    <input type="text" id="orderCity" class="form-control shadow-sm" placeholder="e.g. Santo Tomas" required>
                  </div>
                  <div class="col-6 col-md-4">
                    <label class="form-label fw-semibold small text-muted text-uppercase">Province</label>
                    <input type="text" id="orderProvince" class="form-control shadow-sm" placeholder="e.g. Batangas" required>
                  </div>
                  <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold small text-muted text-uppercase">ZIP Code</label>
                    <input type="text" id="orderZip" class="form-control shadow-sm" placeholder="4234" inputmode="numeric" maxlength="4" required>
                  </div>
                </div>

                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" id="saveAsProfile">
                  <label class="form-check-label small" for="saveAsProfile">
                    Save these details as a new profile for next time
                  </label>
                </div>
                <div class="mb-3" id="profileLabelWrap" style="display:none;">
                  <label class="form-label fw-semibold small text-muted text-uppercase">Profile Label</label>
                  <input type="text" id="profileLabel" class="form-control shadow-sm" placeholder="e.g. Home, Office" maxlength="50">
                </div>

                <div class="text-end mb-3" id="backToProfilesContainer" style="display:none;">
                  <button type="button" class="btn btn-sm text-secondary" id="btnBackToProfiles">
                    <i class="bi bi-arrow-left me-1"></i> Back to saved profiles
                  </button>
                </div>
              </div>

              <!-- =====================================================
                   SHIPPING INSTRUCTIONS
              ====================================================== -->
              <div class="mb-4">
                <label class="form-label fw-semibold small text-muted text-uppercase">
                  Shipping Instructions <span class="fw-normal text-muted text-normal">(optional)</span>
                </label>
                <textarea
                  id="shippingInstructions"
                  class="form-control shadow-sm"
                  rows="2"
                  placeholder="e.g. Gate code, preferred drop-off time, landmark for the rider"
                ></textarea>
              </div>

              <div class="alert alert-warning d-flex align-items-start gap-2 small mb-4">
                <i class="bi bi-truck fs-5"></i>
                <div>
                  <strong>Delivery Fee Notice:</strong> Orders are shipped via <strong>Lalamove</strong>.
                  The delivery fee will be <strong>shouldered by you (the customer)</strong> and is paid
                  directly to the rider upon delivery.
                </div>
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
.text-normal {
    text-transform: none !important;
}
.phone-input-wrap {
    position: relative;
}
.phone-input-wrap .phone-prefix {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #495057;
    font-weight: 600;
    pointer-events: none;
}
.phone-input-wrap input[type="tel"] {
    padding-left: 48px;
}
</style>

<script>
let cart = JSON.parse(localStorage.getItem('ejr_cart') || '[]');
let savedProfiles = [];

// ── PHONE INPUT: DIGITS ONLY, 10 DIGITS MAX ───────────────────
function enforceDigitsOnlyPhone(input) {
    if (!input) return;

    input.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 10);
    });

    input.addEventListener('keydown', function (event) {
        const allowedKeys = ['Backspace','Delete','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Tab','Home','End'];
        if (allowedKeys.includes(event.key) || /^\d$/.test(event.key)) return;
        event.preventDefault();
    });

    input.addEventListener('paste', function (event) {
        event.preventDefault();
        const pasted = (event.clipboardData || window.clipboardData).getData('text');
        const digits = pasted.replace(/\D/g, '').slice(0, 10);
        document.execCommand('insertText', false, digits);
    });
}

// ── REFRESH USER FROM API (basic account fallback) ────────────
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
            // Only use the account's basic info as a starting point when
            // the user has no saved profiles yet.
            if (!savedProfiles.length) {
                prefillFromAccount(data.user);
            }
        }
    })
    .catch(err => console.warn('Could not refresh user:', err));
}

function prefillFromAccount(user) {
    if (user.full_name) {
        document.getElementById('orderCustomerName').value = user.full_name.trim();
    }

    if (user.phone) {
        // users.phone is stored as 639XXXXXXXXX — strip the leading 63
        const digits = String(user.phone).replace(/\D/g, '');
        document.getElementById('orderContact').value = digits.startsWith('63') ? digits.slice(2) : digits.slice(-10);
    }

    if (user.address) {
        document.getElementById('orderAddress1').value = user.address.trim();
    }
}

// ── SAVED PROFILES ─────────────────────────────────────────────
function fetchProfiles() {
    const token = localStorage.getItem('jwt_token');
    if (!token) return;

    fetch('api/get_profiles.php', {
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' && Array.isArray(data.profiles)) {
            savedProfiles = data.profiles;
            renderProfileSelect();
        }
    })
    .catch(err => console.warn('Could not load saved profiles:', err));
}

function renderProfileSelect() {
    const section = document.getElementById('savedProfilesSection');
    const manualForm = document.getElementById('manualProfileForm');
    const select = document.getElementById('savedProfileSelect');
    const backToProfilesContainer = document.getElementById('backToProfilesContainer');

    if (!section || !select) return;

    if (!savedProfiles.length) {
        section.style.display = 'none';
        manualForm.style.display = 'block';
        return;
    }

    select.innerHTML = '<option value="">-- Select a saved profile --</option>';

    savedProfiles.forEach(profile => {
        const option = document.createElement('option');
        option.value = JSON.stringify(profile);
        const addressBits = [profile.address_line1, profile.city].filter(Boolean).join(', ');
        option.textContent = `${profile.label || 'Saved Address'} — ${profile.full_name} (${addressBits})`;
        select.appendChild(option);
    });

    section.style.display = 'block';
    manualForm.style.display = 'none';
    if (backToProfilesContainer) backToProfilesContainer.style.display = 'none';
}

function clearManualFields() {
    ['orderCustomerName','orderContact','orderAddress1','orderAddress2','orderCity','orderProvince','orderZip','profileLabel'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    const saveCheck = document.getElementById('saveAsProfile');
    if (saveCheck) saveCheck.checked = false;
    const labelWrap = document.getElementById('profileLabelWrap');
    if (labelWrap) labelWrap.style.display = 'none';
}

function setupProfileControls() {
    const btnAddNew = document.getElementById('btnAddNewProfile');
    const btnBack = document.getElementById('btnBackToProfiles');
    const savedSection = document.getElementById('savedProfilesSection');
    const manualForm = document.getElementById('manualProfileForm');
    const savedSelect = document.getElementById('savedProfileSelect');
    const backToProfilesContainer = document.getElementById('backToProfilesContainer');
    const saveAsProfile = document.getElementById('saveAsProfile');
    const profileLabelWrap = document.getElementById('profileLabelWrap');

    if (btnAddNew) {
        btnAddNew.addEventListener('click', function () {
            clearManualFields();
            manualForm.style.display = 'block';
            savedSection.style.display = 'none';
            if (savedSelect) savedSelect.value = '';
            if (backToProfilesContainer) backToProfilesContainer.style.display = savedProfiles.length ? 'block' : 'none';
        });
    }

    if (btnBack) {
        btnBack.addEventListener('click', function () {
            manualForm.style.display = 'none';
            savedSection.style.display = 'block';
        });
    }

    if (saveAsProfile && profileLabelWrap) {
        saveAsProfile.addEventListener('change', function () {
            profileLabelWrap.style.display = this.checked ? 'block' : 'none';
        });
    }

    enforceDigitsOnlyPhone(document.getElementById('orderContact'));

    // ZIP code: digits only, 4 digits max
    const zipInput = document.getElementById('orderZip');
    if (zipInput) {
        zipInput.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 4);
        });
    }
}

function saveNewProfile(profileData) {
    const token = localStorage.getItem('jwt_token');
    if (!token) return Promise.resolve(null);

    return fetch('api/add_profile.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + token
        },
        body: JSON.stringify(profileData)
    })
    .then(res => res.json())
    .catch(() => null);
}

document.addEventListener('DOMContentLoaded', () => {
    const user = JSON.parse(localStorage.getItem('user') || '{}');

    if (user.full_name || user.address) {
        prefillFromAccount(user);
    }

    setupProfileControls();
    fetchProfiles();
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
                    <button class="btn btn-outline-secondary qty-btn" onclick="changeQty(${idx}, 1)"
                            ${(item.stock !== undefined && item.qty >= item.stock) ? 'disabled title="Maximum available stock reached"' : ''}>+</button>
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
    const item = cart[idx];

    if (delta > 0 && item.stock !== undefined && item.qty >= item.stock) {
        Swal.fire({
            icon: 'warning',
            title: 'Stock Limit Reached',
            text: `Only ${item.stock} unit(s) of "${item.name}" available.`,
            confirmButtonColor: '#e62429'
        });
        return;
    }

    item.qty += delta;
    if (item.qty <= 0) cart.splice(idx, 1);
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

    // ── DETERMINE PROFILE SOURCE (saved vs manual entry) ───────
    const savedSection = document.getElementById('savedProfilesSection');
    const savedSelect  = document.getElementById('savedProfileSelect');
    const isUsingSavedProfile = savedSection && savedSection.style.display !== 'none' &&
                                 savedSelect && savedSelect.value !== '';

    let customerName, contactDigits, address1, address2, city, province, zip;

    if (isUsingSavedProfile) {
        const profile = JSON.parse(savedSelect.value);
        customerName  = profile.full_name || '';
        contactDigits = (profile.contact_number || '').replace(/\D/g, '').slice(-10);
        address1      = profile.address_line1 || '';
        address2      = profile.address_line2 || '';
        city          = profile.city || '';
        province      = profile.province || '';
        zip           = profile.zip_code || '';
    } else {
        customerName  = document.getElementById('orderCustomerName').value.trim();
        contactDigits = document.getElementById('orderContact').value.trim();
        address1      = document.getElementById('orderAddress1').value.trim();
        address2      = document.getElementById('orderAddress2').value.trim();
        city          = document.getElementById('orderCity').value.trim();
        province      = document.getElementById('orderProvince').value.trim();
        zip           = document.getElementById('orderZip').value.trim();
    }

    // ── VALIDATION ───────────────────────────────────────────────
    if (!customerName || !address1 || !city || !province || !zip) {
        Swal.fire('Missing Details', 'Please complete your name and full address before placing the order.', 'warning');
        return;
    }

    if (!/^9\d{9}$/.test(contactDigits)) {
        Swal.fire('Invalid Contact Number', 'Please enter a valid 10-digit mobile number (e.g. 9XXXXXXXXX).', 'warning');
        return;
    }

    if (!/^\d{4}$/.test(zip)) {
        Swal.fire('Invalid ZIP Code', 'Please enter a valid 4-digit ZIP code.', 'warning');
        return;
    }

    const composedAddress = [address1, address2, city, province, zip].filter(Boolean).join(', ');
    const formattedContact = '+63 ' + contactDigits;

    const btn = this.querySelector('[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Placing Order...';

    const total = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;

    // ── OPTIONALLY SAVE AS A NEW PROFILE FIRST ──────────────────
    const saveAsProfile = document.getElementById('saveAsProfile');
    const shouldSaveProfile = !isUsingSavedProfile && saveAsProfile && saveAsProfile.checked;

    const savePromise = shouldSaveProfile
        ? saveNewProfile({
            label:          (document.getElementById('profileLabel').value || '').trim(),
            full_name:      customerName,
            contact_number: contactDigits,
            address_line1:  address1,
            address_line2:  address2,
            city:           city,
            province:       province,
            zip_code:       zip
        })
        : Promise.resolve(null);

    savePromise.finally(() => {
        fetch('api/place_order.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({
                customer_name:          customerName,
                contact:                formattedContact,
                address:                composedAddress,
                shipping_instructions:  document.getElementById('shippingInstructions').value.trim(),
                items:                  cart,
                total_price:            total,
                payment_method:         paymentMethod
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
                            ? 'Your order is pending. Please prepare exact cash for delivery/pickup. Remember: the Lalamove delivery fee is shouldered by you and paid to the rider.'
                            : 'Your order is now pending. We will contact you shortly. Remember: the Lalamove delivery fee is shouldered by you and paid to the rider.',
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
});
</script>

<?php include 'includes/footer.php'; ?>