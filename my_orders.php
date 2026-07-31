<?php
session_start();
$pageTitle = "My Orders - EJR Garage";
include 'includes/header.php';
?>

<section class="profile-header-bg">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div>
                    <h2 class="mb-0 fw-bold">My Orders</h2>
                    <p class="mb-0 opacity-75">Track the status of your parts orders</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container account-container">
        <div class="row g-4">

            <div class="col-lg-4">
                <div class="sidebar-card card border-0 shadow-sm p-3">
                    <nav class="d-flex flex-column gap-1">
                        <a href="account.php" class="nav-link-custom">
                            <i class="bi bi-person"></i> Profile
                        </a>
                        <a href="my_vehicles.php" class="nav-link-custom">
                            <i class="bi bi-truck"></i> My Vehicles
                        </a>
                        <a href="my_orders.php" class="nav-link-custom active">
                            <i class="bi bi-bag-check"></i> My Orders
                        </a>
                        <a href="my_inquiries.php" class="nav-link-custom">
                            <i class="bi bi-chat-left-text"></i> My Inquiries
                        </a>
                        <a href="my_appointments.php" class="nav-link-custom">
                            <i class="bi bi-calendar-event"></i> Appointments
                        </a>
                        <a href="service_history.php" class="nav-link-custom">
                            <i class="bi bi-clock-history"></i> Service History
                        </a>
                        <a href="settings.php" class="nav-link-custom">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                        <hr class="my-2 mx-3">
                        <a href="javascript:void(0)" onclick="handleLogout()" class="nav-link-custom text-danger">
                            <i class="bi bi-box-arrow-left"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm" style="border-radius:16px; overflow:hidden;">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 px-4" style="border-bottom:1px solid #f0f0f0;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-bag-check text-danger"></i>
                            <span class="fw-bold">My Orders</span>
                        </div>
                        <span class="text-muted small" id="orderCount"></span>
                    </div>
                    <div style="max-height:620px; overflow-y:auto; scrollbar-width:thin; scrollbar-color:#e0e0e0 transparent;">
                        <div id="ordersContainer" class="p-3 d-flex flex-column gap-3"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<style>
.order-track-card {
    border: none;
    border-radius: 14px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    margin-bottom: 0;
    overflow: hidden;
}
.order-track-head {
    background: #f8f9fa;
    padding: 16px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
.status-badge {
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.status-pending   { background:#fff3cd; color:#8a6d00; }
.status-approved  { background:#d1f3df; color:#0f7b3e; }
.status-cancelled { background:#fde2e2; color:#c0282d; }
.pay-badge-paid    { background:#d1e7ff; color:#0a4a9e; }
.pay-badge-unpaid  { background:#f1f1f1; color:#6c757d; }

.order-track-steps {
    display: flex;
    align-items: center;
    padding: 24px 20px 8px;
}
.order-step {
    flex: 1;
    text-align: center;
    position: relative;
}
.order-step .step-circle {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: #e9ecef;
    color: #adb5bd;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    font-size: 1rem;
    transition: all 0.2s;
}
.order-step.done .step-circle {
    background: #198754;
    color: #fff;
}
.order-step.current .step-circle {
    background: #dc3545;
    color: #fff;
}
.order-step.cancelled-step .step-circle {
    background: #dc3545;
    color: #fff;
}
.order-step small {
    display: block;
    font-size: 0.72rem;
    color: #6c757d;
    font-weight: 600;
    text-transform: uppercase;
}
.order-step::after {
    content: '';
    position: absolute;
    top: 17px;
    left: 50%;
    width: 100%;
    height: 3px;
    background: #e9ecef;
    z-index: -1;
}
.order-step:last-child::after { display: none; }
.order-step.done::after { background: #198754; }

.order-track-body {
    padding: 20px;
}
.order-item-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
    padding: 6px 0;
    border-bottom: 1px dashed #eee;
}
.order-item-row:last-child { border-bottom: none; }
</style>

<script>
function statusStepIndex(status) {
    return { pending: 0, approved: 1, cancelled: -1 }[status] ?? 0;
}

function renderSteps(status) {
    if (status === 'cancelled') {
        return `
            <div class="order-track-steps">
                <div class="order-step cancelled-step">
                    <div class="step-circle"><i class="bi bi-x-lg"></i></div>
                    <small>Cancelled</small>
                </div>
            </div>`;
    }
    const idx = statusStepIndex(status);
    const steps = [
        { label: 'Order Placed', icon: 'bi-bag-check' },
        { label: 'Approved',     icon: 'bi-check2'    },
    ];
    return `
        <div class="order-track-steps">
            ${steps.map((s, i) => `
                <div class="order-step ${i < idx ? 'done' : ''} ${i === idx ? 'current' : ''}">
                    <div class="step-circle"><i class="bi ${s.icon}"></i></div>
                    <small>${s.label}</small>
                </div>
            `).join('')}
        </div>`;
}

function statusBadgeClass(status) {
    return { pending: 'status-pending', approved: 'status-approved', cancelled: 'status-cancelled' }[status] || 'status-pending';
}

function loadOrders() {
    const token = localStorage.getItem('jwt_token');
    const container = document.getElementById('ordersContainer');

    if (!token) {
        window.location.href = 'login.php';
        return;
    }

    fetch('api/get_my_orders.php', {
        method: 'GET',
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status !== 'success') {
            container.innerHTML = `<div class="alert alert-danger">${data.message || 'Could not load your orders.'}</div>`;
            return;
        }

        const countEl = document.getElementById('orderCount');
        if (countEl) countEl.textContent = data.orders.length + ' order' + (data.orders.length !== 1 ? 's' : '');

        if (!data.orders.length) {
            container.innerHTML = `
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
                    <i class="bi bi-bag-x fs-1 d-block mb-3"></i>
                    <h5>No orders yet</h5>
                    <a href="available_parts.php" class="btn btn-danger mt-3 px-4 fw-bold">Browse Parts</a>
                </div>`;
            return;
        }

        container.innerHTML = data.orders.map(order => {
            const date = new Date(order.created_at.replace(/-/g, '/')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const payBadge = order.payment_status === 'paid'
                ? `<span class="status-badge pay-badge-paid"><i class="bi bi-check-circle-fill me-1"></i>Paid</span>`
                : order.payment_method === 'cod'
                    ? `<span class="status-badge pay-badge-unpaid"><i class="bi bi-cash-coin me-1"></i>Cash on Delivery</span>`
                    : `<span class="status-badge pay-badge-unpaid"><i class="bi bi-clock-history me-1"></i>Unpaid</span>`;

            const itemsHtml = order.items.map(it => `
                <div class="order-item-row">
                    <span>${it.name || 'Part'} <span class="text-muted">x${it.qty}</span></span>
                    <span class="fw-semibold">₱${(it.price * it.qty).toLocaleString('en-PH', {minimumFractionDigits:2})}</span>
                </div>
            `).join('');

            return `
            <div class="order-track-card card">
                <div class="order-track-head">
                    <div>
                        <span class="fw-bold">Order #${order.user_order_number}</span>
                        <span class="text-muted small ms-2">${date}</span>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="status-badge ${statusBadgeClass(order.status)}">${order.status}</span>
                        ${payBadge}
                    </div>
                </div>
                ${renderSteps(order.status)}
                <div class="order-track-body">
                    ${itemsHtml}
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold text-danger fs-5">₱${parseFloat(order.total_price).toLocaleString('en-PH', {minimumFractionDigits:2})}</span>
                    </div>
                </div>
            </div>`;
        }).join('');
    })
    .catch(() => {
        container.innerHTML = `<div class="alert alert-danger">Failed to connect to server.</div>`;
    });
}

document.addEventListener('DOMContentLoaded', loadOrders);

function handleLogout() {
    const token = localStorage.getItem("jwt_token");
    const finish = () => {
        localStorage.removeItem("jwt_token");
        localStorage.removeItem("user");
        fetch('api/logout.php').finally(() => {
            window.location.href = "index.php";
        });
    };
    if (token) {
        fetch('api/user_end_session.php', { method: 'POST', headers: { 'Authorization': 'Bearer ' + token } }).finally(finish);
    } else {
        finish();
    }
}
</script>

<?php include 'includes/footer.php'; ?>