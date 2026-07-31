<?php 
  $pageTitle = "Available Parts - EJR Garage";
  include 'includes/header.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<section class="appointment-header text-center">
  <div class="container">
    <h1>Available Parts</h1>
    <p>Browse high-quality automotive parts available at EJR Garage</p>
  </div>
</section>

<section class="services-section py-5">
  <div class="container">

    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="input-group shadow-sm">
          <span class="input-group-text bg-white border-end-0">
            <i class="bi bi-search text-danger"></i>
          </span>
          <input type="text" id="searchInput" class="form-control border-start-0 ps-0"
                 placeholder="Search parts...">
        </div>
      </div>
      <div class="col-md-6 d-flex gap-2">
        <select id="categoryFilter" class="form-select shadow-sm">
          <option value="">All Categories</option>
          <option value="engine">Engine</option>
          <option value="brakes">Brakes</option>
          <option value="suspension">Suspension</option>
          <option value="electrical">Electrical</option>
        </select>
        <!-- Cart Button -->
        <a href="my_cart.php" class="btn btn-dark position-relative flex-shrink-0 px-3">
          <i class="bi bi-cart3 fs-5"></i>
          <span id="cartBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">0</span>
        </a>
      </div>
    </div>

    <div class="row g-4" id="partsContainer">
      <div class="col-12 text-center py-5">
        <div class="spinner-border text-danger"></div>
        <p class="mt-2 text-muted">Loading parts...</p>
      </div>
    </div>

  </div>
</section>

<!-- Inquire Modal -->
<div class="modal fade" id="inquireModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-0 p-4" style="background: linear-gradient(135deg, #1e2a36, #2d3f50); color: white; border-radius: 15px 15px 0 0;">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-chat-dots me-2"></i>Request / Inquire
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="inquiryForm">
        <div class="modal-body p-4">
          <input type="hidden" id="partId">

          <div class="mb-3">
            <label class="form-label fw-semibold small text-muted text-uppercase">Part</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-0"><i class="bi bi-box-seam"></i></span>
              <input type="text" id="partName" class="form-control bg-light border-0 fw-bold text-dark"
                     readonly style="opacity:1!important;color:#212529!important;background-color:#f8f9fa!important;">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold small text-muted text-uppercase">Your Name</label>
            <input type="text" id="customerName" class="form-control border-secondary-subtle shadow-sm" placeholder="Juan Dela Cruz" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold small text-muted text-uppercase">Contact Number</label>
            <input type="text" id="contact" class="form-control border-secondary-subtle shadow-sm" placeholder="09XX-XXX-XXXX" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold small text-muted text-uppercase">Message (Optional)</label>
            <textarea id="message" class="form-control border-secondary-subtle shadow-sm" rows="3" placeholder="Any specific questions or requests..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger px-4 fw-bold text-white shadow"
                  style="background-color:#dc3545!important;border:none!important;opacity:1!important;">
            <i class="bi bi-send-fill me-1"></i> Submit Inquiry
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.part-card {
    border: none;
    border-radius: 16px;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 15px rgba(0,0,0,0.07);
}
.part-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
}
.part-card img {
    height: 200px;
    object-fit: cover;
    width: 100%;
}
.part-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(0,0,0,0.6);
    color: white;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}
.stock-badge {
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
}
@media (max-width: 576px) {
    .part-card img { height: 110px; }
    .part-card .card-body {
        text-align: center;
        padding: 10px !important;
    }
    .part-card .card-body h6 {
        font-size: 13px;
        margin-bottom: 4px !important;
    }
    .part-card .card-body p {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        font-size: 11px;
        margin-bottom: 6px !important;
    }
    .part-card .card-body .d-flex.justify-content-between {
        justify-content: center !important;
        gap: 6px;
        flex-wrap: wrap;
        margin-bottom: 8px !important;
    }
    .part-card .fs-6 {
        font-size: 13px !important;
    }
    .stock-badge {
        font-size: 10px;
        padding: 2px 8px;
    }
    .part-card .btn-sm {
        font-size: 11px;
        padding: 5px 8px;
    }
}
</style>

<script>
let allParts = [];
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
        }
    })
    .catch(err => console.warn('Could not refresh user:', err));
}

document.addEventListener('DOMContentLoaded', () => {
    refreshUserFromApi();
    loadParts();
    updateCartBadge();
});

function updateCartBadge() {
    const total = cart.reduce((sum, item) => sum + item.qty, 0);
    const badge = document.getElementById('cartBadge');
    badge.textContent   = total;
    badge.style.display = total > 0 ? 'inline-block' : 'none';
}

function loadParts() {
    const search   = document.getElementById('searchInput').value;
    const category = document.getElementById('categoryFilter').value;

    fetch(`api/get_parts.php?search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}`)
        .then(res => res.json())
        .then(data => {
            allParts = data.parts || [];
            renderParts(allParts);
        });
}

function renderParts(parts) {
    const container = document.getElementById('partsContainer');
    if (!parts.length) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="bi bi-box-seam fs-1 text-muted d-block mb-2"></i>
                <p class="text-muted">No parts found.</p>
            </div>`;
        return;
    }

    container.innerHTML = parts.map(p => `
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card part-card h-100">
                <div style="position:relative;">
                    <img src="${p.image_url || 'assets/images/no-image.png'}"
                         alt="${p.name}"
                         onerror="this.src='assets/images/no-image.png'">
                    <span class="part-badge">${p.category}</span>
                </div>
                <div class="card-body d-flex flex-column p-3">
                    <h6 class="fw-bold mb-1">${p.name}</h6>
                    <p class="text-muted small mb-2" style="flex:1;">${p.description || ''}</p>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold text-danger fs-6">₱${parseFloat(p.price).toLocaleString('en-PH', {minimumFractionDigits:2})}</span>
                        <span class="stock-badge ${p.stock > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">
                            ${p.stock > 0 ? p.stock + ' in stock' : 'Out of stock'}
                        </span>
                    </div>
                    ${p.stock > 0 ? `
                    <div class="d-flex gap-2">
                        <button class="btn btn-dark btn-sm fw-bold flex-fill add-cart-btn"
                                data-id="${p.id}" data-name="${p.name}" data-price="${p.price}">
                            <i class="bi bi-cart-plus me-1"></i>Add to Cart
                        </button>
                        <button class="btn btn-outline-danger btn-sm fw-bold inquire-btn"
                                data-id="${p.id}" data-name="${p.name}" title="Inquire">
                            <i class="bi bi-chat-dots"></i>
                        </button>
                    </div>` : `
                    <button class="btn btn-secondary btn-sm w-100 fw-bold" disabled>
                        Out of Stock
                    </button>`}
                </div>
            </div>
        </div>`).join('');

    // ── Inquire button ────────────────────────────────────────
    document.querySelectorAll('.inquire-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!isLoggedIn()) { requireLogin('submit an inquiry'); return; }

            const user = JSON.parse(localStorage.getItem('user') || '{}');

            let fullName = '';
            if (user.full_name) {
                const nameParts = user.full_name.trim().split(' ');
                const lastName  = nameParts.length > 1 ? nameParts.pop() : '';
                const firstName = nameParts.join(' ');
                fullName = firstName + (lastName ? ' ' + lastName : '');
            }

            document.getElementById('partId').value       = this.dataset.id;
            document.getElementById('partName').value     = this.dataset.name;
            document.getElementById('customerName').value = fullName;
            document.getElementById('contact').value      = user.phone || '';
            document.getElementById('message').value      = '';
            new bootstrap.Modal(document.getElementById('inquireModal')).show();
        });
    });

    // ── Add to Cart button ────────────────────────────────────
    document.querySelectorAll('.add-cart-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!isLoggedIn()) { requireLogin('add items to cart'); return; }

            const id    = this.dataset.id;
            const name  = this.dataset.name;
            const price = parseFloat(this.dataset.price);

            const existing = cart.find(item => item.id === id);
            if (existing) {
                existing.qty += 1;
            } else {
                cart.push({ id, name, price, qty: 1 });
            }

            localStorage.setItem('ejr_cart', JSON.stringify(cart));
            updateCartBadge();

            // Button feedback
            this.innerHTML = '<i class="bi bi-check-lg me-1"></i>Added!';
            this.classList.replace('btn-dark', 'btn-success');
            setTimeout(() => {
                this.innerHTML = '<i class="bi bi-cart-plus me-1"></i>Add to Cart';
                this.classList.replace('btn-success', 'btn-dark');
            }, 1200);
        });
    });
}

// ── AUTH HELPER ───────────────────────────────────────────────
function isLoggedIn() {
    return !!localStorage.getItem('jwt_token');
}

function requireLogin(action) {
    Swal.fire({
        icon: 'info',
        title: 'Login Required',
        text: `Please log in to ${action}.`,
        confirmButtonText: 'Log In',
        showCancelButton: true,
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#e62429'
    }).then(result => {
        if (result.isConfirmed) window.location.href = 'login.php';
    });
}


document.getElementById('inquiryForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = this.querySelector('[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';

    const token = localStorage.getItem('jwt_token');
    if (!token) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-1"></i> Submit Inquiry';
        requireLogin('submit an inquiry');
        return;
    }

    fetch('api/submit_inquiry.php', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + token
        },
        body: JSON.stringify({
            part_id:       document.getElementById('partId').value,
            customer_name: document.getElementById('customerName').value,
            contact:       document.getElementById('contact').value,
            message:       document.getElementById('message').value
        })
    })
    .then(res => res.json().then(data => ({ ok: res.ok, data })))
    .then(({ ok, data }) => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-1"></i> Submit Inquiry';

        if (!ok || data.status !== 'success') {
            Swal.fire('Error', data.message || 'Failed to submit inquiry.', 'error');
            return;
        }

        bootstrap.Modal.getInstance(document.getElementById('inquireModal')).hide();
        Swal.fire({
            icon: 'success',
            title: 'Inquiry Sent!',
            text: 'We will contact you shortly.',
            confirmButtonColor: '#e62429'
        });
    })
    .catch(() => {
        Swal.fire('Error', 'Failed to submit inquiry.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-1"></i> Submit Inquiry';
    });
});

document.getElementById('searchInput').addEventListener('input', loadParts);
document.getElementById('categoryFilter').addEventListener('change', loadParts);
</script>

<?php include 'includes/footer.php'; ?>