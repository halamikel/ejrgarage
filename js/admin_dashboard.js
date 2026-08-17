function getAuthHeaders() {
    const token = localStorage.getItem('jwt_token');
    return {
        'Content-Type': 'application/json',
        'Authorization': token ? 'Bearer ' + token : ''
    };
}

// =============================================================
// admin_dashboard.js — EJR Garage Admin Dashboard
// =============================================================

let masterData = { users: [], appointments: [], vehicles: [] };
let historyData = [];
let currentChatUserId = null;

// Raw datasets kept in memory so search + filter dropdowns can be
// combined and re-applied without re-fetching from the server.
let partsData = [];
let inquiriesData = [];
let ordersData = [];
let servicesData = [];
let mechanicsData = [];
let globalSearchTerm = '';

document.addEventListener("DOMContentLoaded", () => {

    loadFullSiteData();
    loadAdminChatUsers();
    loadServiceHistory();
    loadParts();
    loadInquiries();
    loadOrders();
    loadBusyDates();
    loadServices();
    loadMechanics();

    updateLiveChatBadge();
    setInterval(updateLiveChatBadge, 20000);

    // Enter key handled by onkeydown in HTML to avoid double-fire

    const adminSearch = document.getElementById("adminSearch");
    if (adminSearch) {
        adminSearch.addEventListener("input", function (e) {
            globalSearchTerm = e.target.value.toLowerCase().trim();
            applyAllFilters();
        });
    }

    // ── Chat Sync Loop ───────────────────────────────────────
    setInterval(() => {
        const chatTabActive = document.querySelector("#tab-live-chat")?.classList.contains("show");
        if (chatTabActive) {
            loadAdminChatUsers();
            if (currentChatUserId) loadAdminMessages(currentChatUserId);
        }
    }, 8000);

    const btnAddBusyDate = document.getElementById('btnAddBusyDate');
    if (btnAddBusyDate) btnAddBusyDate.addEventListener('click', openBlockDate);

    startRealtime();
});


// =============================================================
// CORE DATA LOADERS
// =============================================================

function loadFullSiteData() {
    fetch('api/admin/get_full_site_data.php?t=' + Date.now(), { credentials: 'include', headers: getAuthHeaders() })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                masterData = data;
                filterAndRenderAppointments();
                filterAndRenderUsers();
                filterAndRenderVehicles();
                updateStats(data);
            }
        })
        .catch(err => console.error("Fetch failed:", err));
}

function loadServiceHistory() {
    fetch('api/admin/get_service_history.php', { credentials: 'include', headers: getAuthHeaders() })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                historyData = data.history;
                document.getElementById('badgeHistory').textContent = historyData.length;
                filterAndRenderHistory();
            }
        })
        .catch(() => {
            document.getElementById('historyTableBody').innerHTML =
                '<tr><td colspan="6" class="text-center text-danger py-4">Failed to load history.</td></tr>';
        });
}

function loadParts() {
    fetch('api/admin/manage_parts.php', { credentials: 'include', headers: getAuthHeaders() })
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') return;
            partsData = data.parts;
            document.getElementById('badgeParts').textContent = partsData.length;
            populatePartsCategoryFilter();
            filterAndRenderParts();
        });
}

function populatePartsCategoryFilter() {
    const sel = document.getElementById('partsCategoryFilter');
    if (!sel) return;
    const current = sel.value;
    const categories = [...new Set(partsData.map(p => p.category).filter(Boolean))].sort();
    sel.innerHTML = '<option value="">All Categories</option>' +
        categories.map(c => `<option value="${c}">${c}</option>`).join('');
    if (categories.includes(current)) sel.value = current;
}

function filterAndRenderParts() {
    const term = globalSearchTerm;
    const category = document.getElementById('partsCategoryFilter')?.value || '';
    const stock = document.getElementById('partsStockFilter')?.value || '';

    const filtered = partsData.filter(p => {
        const matchesTerm = !term ||
            (p.name && p.name.toLowerCase().includes(term)) ||
            (p.category && p.category.toLowerCase().includes(term));
        const matchesCategory = !category || p.category === category;
        const matchesStock = !stock ||
            (stock === 'in' && p.stock > 0) ||
            (stock === 'out' && p.stock <= 0);
        return matchesTerm && matchesCategory && matchesStock;
    });
    renderParts(filtered);
}

function renderParts(parts) {
    const tbody = document.getElementById('partsTableBody');
    if (!tbody) return;
    if (!parts.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No parts match your search.</td></tr>';
        return;
    }
    tbody.innerHTML = parts.map(p => `
        <tr>
            <td><img src="${p.image_url || ''}" style="width:50px;height:40px;object-fit:cover;border-radius:6px;"
                     onerror="this.style.display='none'"></td>
            <td class="fw-bold">${p.name}</td>
            <td><span class="badge bg-light text-dark border">${p.category}</span></td>
            <td>₱${parseFloat(p.price).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
            <td><span class="badge ${p.stock > 0 ? 'bg-success' : 'bg-danger'}">${p.stock}</span></td>
            <td>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-dark"
                            onclick="openEditPart(${p.id},'${p.name.replace(/'/g,"\\'")}','${(p.description||'').replace(/'/g,"\\'")}',${p.price},'${p.category}','${p.image_url||''}',${p.stock})">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="deletePart(${p.id},'${p.name.replace(/'/g,"\\'")}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`).join('');
}

function loadInquiries() {
    fetch('api/admin/manage_parts.php?action=get_inquiries', { credentials: 'include', headers: getAuthHeaders() })
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') return;
            inquiriesData = data.inquiries;
            const pendingCount = inquiriesData.filter(i => i.status !== 'responded').length;
            document.getElementById('badgeInquiries').textContent = pendingCount;
            filterAndRenderInquiries();
        });
}

function filterAndRenderInquiries() {
    const term = globalSearchTerm;
    const statusFilter = document.getElementById('inquiriesStatusFilter')?.value || 'pending';

    const filtered = inquiriesData.filter(i => {
        const matchesTerm = !term ||
            (i.customer_name && i.customer_name.toLowerCase().includes(term)) ||
            (i.part_name && i.part_name.toLowerCase().includes(term)) ||
            (i.contact && i.contact.toLowerCase().includes(term));
        const matchesStatus = statusFilter === 'all' ||
            (statusFilter === 'pending' && i.status !== 'responded') ||
            (statusFilter === 'responded' && i.status === 'responded');
        return matchesTerm && matchesStatus;
    });
    renderInquiries(filtered);
}

function renderInquiries(inquiries) {
    const tbody = document.getElementById('inquiriesTableBody');
    if (!tbody) return;
    tbody.innerHTML = inquiries.length ? inquiries.map(i => `
        <tr>
            <td><span class="badge bg-light text-dark border">${i.part_name}</span></td>
            <td class="fw-bold">${i.customer_name}</td>
            <td>${i.contact}</td>
            <td class="text-muted small">${i.message || '—'}</td>
            <td>${new Date(i.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
            <td><span class="badge ${i.status === 'responded' ? 'bg-success' : 'bg-warning text-dark'}">${i.status === 'responded' ? 'responded' : 'pending'}</span></td>
            <td>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-success" onclick="updateInquiry(${i.id})">
                        <i class="bi bi-check-lg"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteInquiry(${i.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`).join('')
    : '<tr><td colspan="7" class="text-center text-muted py-4">No inquiries match your search.</td></tr>';
}

function loadOrders() {
    const tbody = document.getElementById('ordersTableBody');
    if (!tbody) return;

    fetch('api/admin/manage_orders.php', { credentials: 'include', headers: getAuthHeaders() })
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') throw new Error(data.message);
            ordersData = data.orders ?? [];
            document.getElementById('badgeOrders').textContent = ordersData.length;
            filterAndRenderOrders();
        })
        .catch(err => {
            console.error('Orders error:', err);
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Failed to load orders.</td></tr>';
        });
}

function filterAndRenderOrders() {
    const term = globalSearchTerm;
    const statusFilter = document.getElementById('ordersStatusFilter')?.value || '';

    const filtered = ordersData.filter(o => {
        const year = new Date(o.created_at).getFullYear();
        const orderNo = `EJR-ORD-${year}-${String(o.id).padStart(5, '0')}`.toLowerCase();
        const matchesTerm = !term ||
            (o.customer_name && o.customer_name.toLowerCase().includes(term)) ||
            (o.contact && o.contact.toLowerCase().includes(term)) ||
            orderNo.includes(term);
        const matchesStatus = !statusFilter || o.status.toLowerCase() === statusFilter.toLowerCase();
        return matchesTerm && matchesStatus;
    });
    renderOrders(filtered);
}

function renderOrders(orders) {
    const tbody = document.getElementById('ordersTableBody');
    if (!tbody) return;

    if (!orders.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No orders match your search.</td></tr>';
        return;
    }

    tbody.innerHTML = orders.map(o => {
        const year      = new Date(o.created_at).getFullYear();
        const orderNo   = `EJR-ORD-${year}-${String(o.id).padStart(5, '0')}`;
        const itemsArr  = typeof o.items === 'string' ? JSON.parse(o.items) : o.items;
        const itemsList = Array.isArray(itemsArr)
            ? itemsArr.map(i => `${i.name} ×${i.qty}`).join(', ')
            : o.items;
        const date      = new Date(o.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const safeName  = o.customer_name.replace(/'/g, "\\'");
        const isPending = o.status.toLowerCase() === 'pending';

        return `
        <tr>
            <td><code style="color:#b30000;">${orderNo}</code></td>
            <td class="fw-bold">${o.customer_name}</td>
            <td>${o.contact}</td>
            <td class="text-muted small" style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                title="${itemsList}">${itemsList}</td>
            <td class="fw-bold text-danger">₱${parseFloat(o.total_price).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
            <td>${date}</td>
            <td>${statusBadge(o.status)}</td>
            <td>
                <div class="btn-group">
                    ${isPending ? `
                        <button class="btn btn-sm btn-outline-success"
                                onclick="updateOrder(${o.id}, '${o.status}', '${safeName}')" title="Manage">
                            <i class="bi bi-pencil-square"></i>
                        </button>` : ''}
                    <button class="btn btn-sm btn-outline-secondary"
                            onclick="deleteOrder(${o.id})" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                    <a href="api/admin/generate_parts_receipt.php?id=${o.id}" target="_blank"
                       class="btn btn-sm btn-outline-primary" title="View Invoice">
                        <i class="bi bi-receipt"></i>
                    </a>
                </div>
            </td>
        </tr>`;
    }).join('');
}


// =============================================================
// RENDER FUNCTIONS
// =============================================================

function updateStats(data) {
    const active = data.appointments;
    document.getElementById("statTotal").textContent    = active.length;
    document.getElementById("statPending").textContent  = active.filter(a => a.status === 'Pending').length;
    document.getElementById("statUsers").textContent    = data.users.length;
    document.getElementById("statVehicles").textContent = data.vehicles.length;
    document.getElementById("badgeAppt").textContent    = active.length;
    document.getElementById("badgeUsers").textContent   = data.users.length;
    document.getElementById("badgeVehs").textContent    = data.vehicles.length;
}

function statusBadge(status) {
    if (!status) return `<span class="badge bg-secondary">Unknown</span>`;
    const s = status.toLowerCase().trim();
    const map = {
        'pending':   'bg-warning text-dark',
        'approved':  'bg-success',
        'confirmed': 'bg-success',
        'completed': 'bg-success',
        'cancelled': 'bg-danger'
    };
    const displayStatus = s.charAt(0).toUpperCase() + s.slice(1);
    return `<span class="badge ${map[s] || 'bg-secondary'}">${displayStatus}</span>`;
}

function filterAndRenderAppointments() {
    const term = globalSearchTerm;
    const statusFilter = document.getElementById('appointmentsStatusFilter')?.value || '';

    const filtered = masterData.appointments.filter(a => {
        const matchesTerm = !term ||
            (a.full_name && a.full_name.toLowerCase().includes(term)) ||
            (a.service_type && a.service_type.toLowerCase().includes(term)) ||
            (a.vehicle_info && a.vehicle_info.toLowerCase().includes(term)) ||
            (a.mechanic_name && a.mechanic_name.toLowerCase().includes(term));
        const matchesStatus = !statusFilter || a.status === statusFilter;
        return matchesTerm && matchesStatus;
    });
    renderAppointments(filtered);
}

function filterAndRenderUsers() {
    const term = globalSearchTerm;
    const roleFilter = document.getElementById('usersRoleFilter')?.value || '';

    const filtered = masterData.users.filter(u => {
        const matchesTerm = !term ||
            (u.full_name && u.full_name.toLowerCase().includes(term)) ||
            (u.email && u.email.toLowerCase().includes(term)) ||
            (u.phone && u.phone.toLowerCase().includes(term));
        const matchesRole = !roleFilter || String(u.role_id) === roleFilter;
        return matchesTerm && matchesRole;
    });
    renderUsers(filtered);
}

function filterAndRenderVehicles() {
    const term = globalSearchTerm;
    const filtered = masterData.vehicles.filter(v =>
        !term ||
        (v.owner_name && v.owner_name.toLowerCase().includes(term)) ||
        (v.plate_number && v.plate_number.toLowerCase().includes(term)) ||
        (v.model && v.model.toLowerCase().includes(term)) ||
        (v.brand && v.brand.toLowerCase().includes(term))
    );
    renderVehicles(filtered);
}

function filterAndRenderHistory() {
    const term = globalSearchTerm;
    const filtered = historyData.filter(h =>
        !term ||
        (h.full_name && h.full_name.toLowerCase().includes(term)) ||
        (h.service_type && h.service_type.toLowerCase().includes(term)) ||
        (h.plate_number && h.plate_number.toLowerCase().includes(term)) ||
        (h.brand && h.brand.toLowerCase().includes(term)) ||
        (h.model && h.model.toLowerCase().includes(term))
    );
    renderHistory(filtered);
}

// Re-applies globalSearchTerm + every tab's own dropdown filter across
// all ten data tabs at once. Called both by the global search box and
// by every individual filter dropdown's onchange, so search text and
// dropdown filters always combine correctly no matter which changed.
function applyAllFilters() {
    filterAndRenderAppointments();
    filterAndRenderUsers();
    filterAndRenderVehicles();
    filterAndRenderHistory();
    filterAndRenderParts();
    filterAndRenderInquiries();
    filterAndRenderOrders();
    filterAndRenderServices();
    filterAndRenderMechanics();
    if (typeof applyBusyFilter === 'function') applyBusyFilter();
}

function renderAppointments(apps) {
    const active = apps;
    document.getElementById("appointmentTableBody").innerHTML = active.length
        ? active.map(a => `
            <tr>
                <td class="fw-bold">${a.full_name || 'Guest'}</td>
                <td><span class="badge bg-light text-dark border">${a.service_type}</span></td>
                <td>${a.vehicle_info || '—'}</td>
                <td>${a.appointment_date
                    ? new Date(a.appointment_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                    : '—'}</td>
                <td>
                    ${a.mechanic_name
                        ? `<span class="badge bg-info text-dark"><i class="bi bi-person-gear me-1"></i>${a.mechanic_name}</span>`
                        : `<span class="text-muted small">Unassigned</span>`}
                </td>
                <td>${statusBadge(a.status)}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-dark" onclick="manageApp(${a.id}, '${a.status}')">
                            <i class="bi bi-pencil-square"></i> Manage
                        </button>
                        <button class="btn btn-sm btn-outline-primary" onclick="assignMechanic(${a.id})" title="Assign Mechanic">
                            <i class="bi bi-person-gear"></i>
                        </button>
                    </div>
                </td>
            </tr>`).join('')
        : '<tr><td colspan="7" class="text-center text-muted py-4">No appointments found.</td></tr>';
}

function renderUsers(users) {
    if (!users.length) {
        document.getElementById("userTableBody").innerHTML =
            '<tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>';
        return;
    }
    document.getElementById("userTableBody").innerHTML = users.map(u => {
        const name  = u.full_name.replace(/'/g, "\\'");
        const email = u.email.replace(/'/g, "\\'");
        return `
        <tr>
            <td>${u.id}</td>
            <td class="fw-bold">${u.full_name}</td>
            <td>${u.email}</td>
            <td>${u.phone || '—'}</td>
            <td><span class="badge ${u.role_id == 2 ? 'bg-danger' : u.role_id == 3 ? 'bg-info text-dark' : 'bg-secondary'}">
                ${u.role_id == 2 ? 'Admin' : u.role_id == 3 ? 'Mechanic' : 'Customer'}
            </span></td>
            <td>${u.created_at
                ? new Date(u.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                : '—'}</td>
            <td>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-dark"
                            onclick="editUser(${u.id})">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="deleteUser(${u.id}, '${name}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function renderVehicles(vehs) {
    document.getElementById("vehicleTableBody").innerHTML = vehs.length
        ? vehs.map(v => {
            const brand = (v.brand || '').replace(/'/g, "\\'");
            const model = (v.model || '').replace(/'/g, "\\'");
            const plate = (v.plate_number || '').replace(/'/g, "\\'");
            const fuel  = (v.fuel_type || '').replace(/'/g, "\\'");
            const trans = (v.transmission || '').replace(/'/g, "\\'");
            return `
            <tr>
                <td class="fw-bold">${v.owner_name || '—'}</td>
                <td>${v.brand || '—'}</td>
                <td>${v.model || '—'}</td>
                <td>${v.year_model || '—'}</td>
                <td><code class="text-uppercase" style="color:#b30000;">${v.plate_number}</code></td>
                <td>${v.fuel_type || '—'}</td>
                <td>${v.transmission || '—'}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-dark"
                                onclick="editVehicle(${v.id}, ${v.user_id}, '${brand}', '${model}', '${v.year_model || ''}', '${plate}', '${fuel}', '${trans}')"
                                title="Edit">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="deleteVehicle(${v.id}, '${plate}')"
                                title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('')
        : '<tr><td colspan="8" class="text-center text-muted py-4">No vehicles found.</td></tr>';
}

function renderHistory(data) {
    const tbody = document.getElementById('historyTableBody');
    document.getElementById('historyCount').textContent =
        data.length ? `Showing ${data.length} record(s)` : '';

    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No service history found.</td></tr>';
        return;
    }

    tbody.innerHTML = data.map(h => {
        const vehicle = h.brand && h.model
            ? `${h.brand} ${h.model} (${h.year_model || '—'})`
            : '—';
        const date = h.service_date
            ? new Date(h.service_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
            : '—';
        const rawDate = h.service_date ? h.service_date.substring(0, 10) : '';
        const service = (h.service_type || '').replace(/'/g, "\\'");
        const custName = (h.full_name || '').replace(/'/g, "\\'");

        return `
        <tr>
            <td class="fw-bold">${h.full_name || '—'}</td>
            <td><span class="badge bg-light text-dark border">${h.service_type || '—'}</span></td>
            <td>${vehicle}</td>
            <td><code class="text-uppercase" style="color:#b30000;">${h.plate_number || '—'}</code></td>
            <td>${date}</td>
            <td>
                <div class="btn-group">
                    <a href="api/admin/generate_receipt.php?id=${h.id}" target="_blank"
                       class="btn btn-sm btn-outline-primary" title="View Invoice">
                        <i class="bi bi-receipt"></i>
                    </a>
                    <button class="btn btn-sm btn-outline-success"
                            onclick="sendReceiptToUser(${h.id}, '${custName}')" title="Send Receipt">
                        <i class="bi bi-send"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-dark"
                            onclick="editHistory(${h.id}, '${service}', '${rawDate}')">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="deleteHistory(${h.id}, '${custName}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}


// =============================================================
// APPOINTMENTS
// =============================================================

function manageApp(id, currentStatus) {

    // =========================================================
    // FINAL STATUS PROTECTION
    // =========================================================
    //
    // Completed and Cancelled appointments are FINAL.
    //
    // Completed cannot become:
    // - Pending
    // - Confirmed
    // - In Progress
    // - Cancelled
    //
    // Cancelled cannot become:
    // - Pending
    // - Confirmed
    // - In Progress
    // - Completed
    //
    // The backend also enforces this rule. This check simply
    // prevents the admin from opening the status selector.
    // =========================================================

    if (currentStatus === 'Completed') {

        Swal.fire({
            icon: 'info',
            title: 'Appointment Completed',
            text: 'This appointment is already completed and cannot be changed.',
            confirmButtonColor: '#b30000'
        });

        return;
    }


    if (currentStatus === 'Cancelled') {

        Swal.fire({
            icon: 'info',
            title: 'Appointment Cancelled',
            text: 'This appointment is already cancelled and cannot be changed.',
            confirmButtonColor: '#b30000'
        });

        return;
    }


    // =========================================================
    // NORMAL STATUS UPDATE
    // =========================================================

    Swal.fire({

        title: 'Update Appointment Status',

        input: 'select',

        inputOptions: {

            'Pending':
                'Pending',

            'Confirmed':
                'Confirmed',

            'In Progress':
                'In Progress',

            'Completed':
                'Completed',

            'Cancelled':
                'Cancelled'
        },

        inputValue: currentStatus,

        showCancelButton: true,

        confirmButtonColor: '#b30000',

        confirmButtonText: 'Update',

        cancelButtonText: 'Cancel'

    }).then(result => {

        if (result.isConfirmed && result.value) {

            postData(
                'api/admin/update_appointment.php',
                {
                    appointment_id: id,
                    status: result.value
                }
            );

        }

    });
}

    // =========================================================
    // NORMAL STATUS UPDATE
    // =========================================================

    Swal.fire({

        title: 'Update Appointment Status',

        input: 'select',

        inputOptions: {

            'Pending':
                'Pending',

            'Confirmed':
                'Confirmed',

            'In Progress':
                'In Progress',

            'Completed':
                'Completed',

            'Cancelled':
                'Cancelled'
        },

        inputValue: currentStatus,

        showCancelButton: true,

        confirmButtonColor: '#b30000',

        confirmButtonText: 'Update',

        cancelButtonText: 'Cancel'

    }).then(result => {

        if (!result.isConfirmed || !result.value) {
            return;
        }

        postData(
            'api/admin/update_appointment.php',
            {
                appointment_id: id,
                status: result.value
            }
        );

    });
}


// =============================================================
// USERS
// =============================================================

function editUser(id) {
    fetch(`api/admin/manage_user.php?id=${id}`, { credentials: 'include', headers: getAuthHeaders() })
        .then(response => response.json())
        .then(res => {
            if (res.status !== 'success') {
                Swal.fire('Error', 'Could not load user data', 'error');
                return;
            }
            const user = res.data;
            Swal.fire({
                title: 'Edit User',
                html: `
                    <div class="text-start mb-3">
                        <label class="form-label fw-semibold small">Full Name</label>
                        <input id="swal-name" class="form-control" value="${user.full_name}">
                    </div>
                    <div class="text-start mb-3">
                        <label class="form-label fw-semibold small">Email</label>
                        <input id="swal-email" class="form-control" value="${user.email}">
                    </div>
                    <div class="text-start mb-3">
                        <label class="form-label fw-semibold small">Role</label>
                        <select id="swal-role" class="form-select">
                            <option value="1" ${user.role_id == 1 ? 'selected' : ''}>Customer</option>
                            <option value="2" ${user.role_id == 2 ? 'selected' : ''}>Admin</option>
                            <option value="3" ${user.role_id == 3 ? 'selected' : ''}>Mechanic</option>
                        </select>
                    </div>
                    <div class="d-flex gap-3 mt-2">
                        <div class="form-check form-switch text-start">
                            <input class="form-check-input" type="checkbox" id="swal-email-verified" ${user.is_verified == 1 ? 'checked' : ''}>
                            <label class="form-check-label fw-semibold small" for="swal-email-verified">
                                <i class="bi bi-envelope-check me-1"></i> Email Verified
                            </label>
                        </div>
                        <div class="form-check form-switch text-start">
                            <input class="form-check-input" type="checkbox" id="swal-phone-verified" ${user.is_phone_verified == 1 ? 'checked' : ''}>
                            <label class="form-check-label fw-semibold small" for="swal-phone-verified">
                                <i class="bi bi-phone me-1"></i> Phone Verified
                            </label>
                        </div>
                    </div>`,
                showCancelButton: true,
                confirmButtonColor: '#b30000',
                confirmButtonText: 'Save Changes',
                preConfirm: () => ({
                    action: 'update_info',
                    user_id: id,
                    full_name: document.getElementById('swal-name').value.trim(),
                    email: document.getElementById('swal-email').value.trim(),
                    role_id: parseInt(document.getElementById('swal-role').value),
                    is_verified: document.getElementById('swal-email-verified').checked ? 1 : 0,
                    is_phone_verified: document.getElementById('swal-phone-verified').checked ? 1 : 0
                })
            }).then(result => {
                if (result.isConfirmed) postData('api/admin/manage_user.php', result.value);
            });
        })
        .catch(err => {
            console.error('Fetch error:', err);
            Swal.fire('Error', 'An unexpected error occurred.', 'error');
        });
}

function deleteUser(id, name) {
    Swal.fire({
        title: `Delete ${name}?`,
        text: 'This will permanently delete the user, their vehicles, and cancel their pending appointments.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete'
    }).then(result => {
        if (result.isConfirmed) postData('api/admin/manage_user.php', { action: 'delete', user_id: id });
    });
}


// =============================================================
// VEHICLES
// =============================================================

function vehicleForm(v = {}) {
    return `
        <div class="text-start">
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label fw-semibold small">Brand</label>
                    <input id="sv-brand" class="form-control" placeholder="e.g. Toyota" value="${v.brand || ''}">
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold small">Model</label>
                    <input id="sv-model" class="form-control" placeholder="e.g. Vios" value="${v.model || ''}">
                </div>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-4">
                    <label class="form-label fw-semibold small">Year</label>
                    <input id="sv-year" type="number" class="form-control" placeholder="e.g. 2020" min="1970" max="2099" value="${v.year_model || ''}">
                </div>
                <div class="col-8">
                    <label class="form-label fw-semibold small">Plate Number</label>
                    <input id="sv-plate" class="form-control text-uppercase" placeholder="e.g. ABC 1234" value="${v.plate_number || ''}">
                </div>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label fw-semibold small">Fuel Type</label>
                    <select id="sv-fuel" class="form-select">
                        <option value="">Select</option>
                        <option value="Gasoline"  ${v.fuel_type === 'Gasoline'  ? 'selected' : ''}>Gasoline</option>
                        <option value="Diesel"    ${v.fuel_type === 'Diesel'    ? 'selected' : ''}>Diesel</option>
                        <option value="Electric"  ${v.fuel_type === 'Electric'  ? 'selected' : ''}>Electric</option>
                        <option value="Hybrid"    ${v.fuel_type === 'Hybrid'    ? 'selected' : ''}>Hybrid</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold small">Transmission</label>
                    <select id="sv-trans" class="form-select">
                        <option value="">Select</option>
                        <option value="Automatic" ${v.transmission === 'Automatic' ? 'selected' : ''}>Automatic</option>
                        <option value="Manual"    ${v.transmission === 'Manual'    ? 'selected' : ''}>Manual</option>
                        <option value="CVT"       ${v.transmission === 'CVT'       ? 'selected' : ''}>CVT</option>
                    </select>
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label fw-semibold small">Owner (User ID)</label>
                <input id="sv-userid" type="number" class="form-control" placeholder="User ID" value="${v.user_id || ''}">
                <small class="text-muted">Check the Users tab for the correct ID.</small>
            </div>
        </div>`;
}

function getVehicleFormValues(action, id = null) {
    const val = {
        action,
        user_id:      parseInt(document.getElementById('sv-userid').value),
        brand:        document.getElementById('sv-brand').value.trim(),
        model:        document.getElementById('sv-model').value.trim(),
        year_model:   document.getElementById('sv-year').value.trim(),
        plate_number: document.getElementById('sv-plate').value.trim().toUpperCase(),
        fuel_type:    document.getElementById('sv-fuel').value,
        transmission: document.getElementById('sv-trans').value
    };
    if (id) val.id = id;
    if (!val.brand || !val.model || !val.plate_number || !val.year_model || !val.fuel_type || !val.transmission) {
        Swal.showValidationMessage('All fields are required.');
        return false;
    }
    if (!val.user_id || isNaN(val.user_id)) {
        Swal.showValidationMessage('Please enter a valid User ID.');
        return false;
    }
    return val;
}

function postVehicleData(payload) {
    fetch('/api/admin/manage_vehicles.php', { method: 'POST', credentials: 'include', headers: getAuthHeaders(),
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: data.message || 'Vehicle updated', showConfirmButton: false, timer: 2000 });
            loadFullSiteData();
        } else {
            Swal.fire('Error', data.message || 'Something went wrong.', 'error');
        }
    })
    .catch(() => Swal.fire('Error', 'Could not reach manage_vehicles.php', 'error'));
}

function editVehicle(id, userId, brand, model, yearModel, plate, fuelType, transmission) {
    Swal.fire({
        title: '<i class="bi bi-pencil-square me-2"></i>Edit Vehicle',
        html: vehicleForm({ user_id: userId, brand, model, year_model: yearModel, plate_number: plate, fuel_type: fuelType, transmission }),
        showCancelButton: true,
        confirmButtonColor: '#b30000',
        confirmButtonText: 'Save Changes',
        preConfirm: () => getVehicleFormValues('edit', id)
    }).then(result => {
        if (result.isConfirmed) postVehicleData(result.value);
    });
}

function deleteVehicle(id, plate) {
    Swal.fire({
        title: `Delete vehicle "${plate}"?`,
        text: 'This will permanently remove the vehicle record.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete'
    }).then(result => {
        if (result.isConfirmed) postVehicleData({ action: 'delete', id });
    });
}


// =============================================================
// SERVICE HISTORY
// =============================================================

function applyHistoryFilter() {
    const term  = document.getElementById('historySearch').value.toLowerCase();
    const month = document.getElementById('historyMonth').value;
    const filtered = historyData.filter(h => {
        const matchTerm = !term ||
            (h.full_name && h.full_name.toLowerCase().includes(term)) ||
            (h.plate_number && h.plate_number.toLowerCase().includes(term)) ||
            (h.model && h.model.toLowerCase().includes(term));
        const matchMonth = !month || (h.service_date && h.service_date.startsWith(month));
        return matchTerm && matchMonth;
    });
    renderHistory(filtered);
}

function clearHistoryFilter() {
    document.getElementById('historySearch').value = '';
    document.getElementById('historyMonth').value  = '';
    renderHistory(historyData);
}

function sendReceiptToUser(id, name) {
    Swal.fire({
        title: 'Send Receipt?',
        text: `Do you want to send the digital receipt to ${name}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Yes, Send Email',
        showLoaderOnConfirm: true,
        preConfirm: () => fetch('api/admin/send_receipt.php', { method: 'POST', credentials: 'include', headers: getAuthHeaders(),
            body: JSON.stringify({ appointment_id: id })
        })
        .then(res => { if (!res.ok) throw new Error(res.statusText); return res.json(); })
        .catch(err => Swal.showValidationMessage(`Request failed: ${err}`)),
        allowOutsideClick: () => !Swal.isLoading()
    }).then(result => {
        if (result.isConfirmed && result.value?.status === 'success') {
            Swal.fire({ icon: 'success', title: 'Sent!', text: `Receipt sent to ${name}.`, timer: 2000, showConfirmButton: false });
        } else if (result.isConfirmed) {
            Swal.fire('Error', result.value?.message || 'Failed to send receipt.', 'error');
        }
    });
}

function editHistory(id, service, date) {
    Swal.fire({
        title: 'Edit Service Record',
        html: `
            <div class="text-start mb-3">
                <label class="form-label fw-semibold small">Service Type</label>
                <input id="swal-service" class="form-control" value="${service}">
            </div>
            <div class="text-start">
                <label class="form-label fw-semibold small">Date</label>
                <input id="swal-date" type="date" class="form-control" value="${date}">
            </div>`,
        showCancelButton: true,
        confirmButtonColor: '#b30000',
        confirmButtonText: 'Save Changes',
        preConfirm: () => ({
            action:           'update',
            id,
            service_type:     document.getElementById('swal-service').value.trim(),
            appointment_date: document.getElementById('swal-date').value
        })
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('api/admin/get_service_history.php', { method: 'POST', credentials: 'include', headers: getAuthHeaders(),
            body: JSON.stringify(result.value)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: 'Record updated', showConfirmButton: false, timer: 2000 });
                historyData = [];
                loadServiceHistory();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    });
}

function deleteHistory(id, name) {
    Swal.fire({
        title: `Delete record for ${name}?`,
        text: 'This will permanently remove this service history entry.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete'
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('api/admin/get_service_history.php', { method: 'POST', credentials: 'include', headers: getAuthHeaders(),
            body: JSON.stringify({ action: 'delete', id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: 'Record deleted', showConfirmButton: false, timer: 2000 });
                historyData = [];
                loadServiceHistory();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    });
}


// =============================================================
// PARTS
// =============================================================

function openAddPart() {
    Swal.fire({
        title: 'Add New Part',
        html: partForm(),
        showCancelButton: true,
        confirmButtonColor: '#b30000',
        confirmButtonText: 'Add Part',
        preConfirm: () => getPartFormValues('add')
    }).then(result => {
        if (result.isConfirmed) postPartData(result.value);
    });
}

function openEditPart(id, name, desc, price, category, image_url, stock) {
    Swal.fire({
        title: 'Edit Part',
        html: partForm({ name, desc, price, category, image_url, stock }),
        showCancelButton: true,
        confirmButtonColor: '#b30000',
        confirmButtonText: 'Save Changes',
        preConfirm: () => getPartFormValues('edit', id)
    }).then(result => {
        if (result.isConfirmed) postPartData(result.value);
    });
}

function partForm(v = {}) {
    return `
        <div class="text-start">
            <div class="mb-2"><label class="form-label fw-semibold small">Name</label>
            <input id="sp-name" class="form-control" value="${v.name || ''}"></div>
            <div class="mb-2"><label class="form-label fw-semibold small">Description</label>
            <textarea id="sp-desc" class="form-control" rows="2">${v.desc || ''}</textarea></div>
            <div class="mb-2"><label class="form-label fw-semibold small">Price (₱)</label>
            <input id="sp-price" type="number" class="form-control" value="${v.price || ''}"></div>
            <div class="mb-2"><label class="form-label fw-semibold small">Category</label>
            <select id="sp-cat" class="form-select">
                <option value="engine"     ${v.category === 'engine'     ? 'selected' : ''}>Engine</option>
                <option value="brakes"     ${v.category === 'brakes'     ? 'selected' : ''}>Brakes</option>
                <option value="suspension" ${v.category === 'suspension' ? 'selected' : ''}>Suspension</option>
                <option value="electrical" ${v.category === 'electrical' ? 'selected' : ''}>Electrical</option>
            </select></div>
            <div class="mb-2"><label class="form-label fw-semibold small">Image URL</label>
            <input id="sp-img" class="form-control" value="${v.image_url || ''}"></div>
            <div class="mb-2"><label class="form-label fw-semibold small">Stock</label>
            <input id="sp-stock" type="number" class="form-control" value="${v.stock || 0}"></div>
        </div>`;
}

function getPartFormValues(action, id = null) {
    const val = {
        action,
        name:        document.getElementById('sp-name').value.trim(),
        description: document.getElementById('sp-desc').value.trim(),
        price:       parseFloat(document.getElementById('sp-price').value),
        category:    document.getElementById('sp-cat').value,
        image_url:   document.getElementById('sp-img').value.trim(),
        stock:       parseInt(document.getElementById('sp-stock').value)
    };
    if (id) val.id = id;
    if (!val.name || !val.price) {
        Swal.showValidationMessage('Name and price are required.');
        return false;
    }
    return val;
}

function postPartData(payload) {
    fetch('api/admin/manage_parts.php', { method: 'POST', credentials: 'include', headers: getAuthHeaders(),
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 2000 });
            loadParts();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
}

function deletePart(id, name) {
    Swal.fire({
        title: `Delete "${name}"?`,
        text: 'This will also delete all inquiries for this part.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete'
    }).then(result => {
        if (result.isConfirmed) postPartData({ action: 'delete', id });
    });
}


// =============================================================
// INQUIRIES
// =============================================================

function updateInquiry(id) {
    Swal.fire({
        title: 'Reply to Inquiry',
        input: 'textarea',
        inputLabel: 'Your Message to Customer',
        inputPlaceholder: 'e.g. This part is available, you can visit us tomorrow...',
        showCancelButton: true,
        confirmButtonText: 'Send Response',
        confirmButtonColor: '#28a745',
        preConfirm: (message) => {
            if (!message) Swal.showValidationMessage('Please enter a reply message');
            return message;
        }
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('api/admin/manage_parts.php', { method: 'POST', credentials: 'include', headers: getAuthHeaders(),
            body: JSON.stringify({ action: 'update_inquiry', id, status: 'responded', reply_message: result.value })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: 'Reply Sent', showConfirmButton: false, timer: 2000 });
                loadInquiries();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    });
}

function deleteInquiry(id) {
    Swal.fire({
        title: 'Delete this inquiry?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete'
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('api/admin/manage_parts.php', { method: 'POST', credentials: 'include', headers: getAuthHeaders(),
            body: JSON.stringify({ action: 'delete_inquiry', id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: 'Deleted', showConfirmButton: false, timer: 2000 });
                loadInquiries();
            }
        });
    });
}


// =============================================================
// ORDERS
// =============================================================

function updateOrder(id, status, name) {
    const currentStatus = status.toLowerCase();
    if (currentStatus === 'approved' || currentStatus === 'completed') {
        Swal.fire({ title: 'Already Approved', html: `Order for <b>${name}</b> is already approved.`, icon: 'info', confirmButtonColor: '#b30000' });
        return;
    }
    if (currentStatus === 'cancelled') {
        Swal.fire({ title: 'Order Cancelled', html: `Order for <b>${name}</b> has already been cancelled.`, icon: 'info', confirmButtonColor: '#b30000' });
        return;
    }
    Swal.fire({
        title: 'Manage Order',
        html: `How would you like to proceed with the order for <b>${name}</b>?`,
        icon: 'question',
        showConfirmButton: true,
        confirmButtonText: '✓ Approve',
        confirmButtonColor: '#198754',
        showDenyButton: true,
        denyButtonText: '✗ Decline',
        denyButtonColor: '#dc3545',
        showCancelButton: true,
        cancelButtonText: 'Keep Pending'
    }).then(result => {
        if (result.isConfirmed) executeStatusUpdate(id, 'approved', name);
        else if (result.isDenied) executeStatusUpdate(id, 'cancelled', name);
    });
}

function executeStatusUpdate(id, newStatus, name) {
    fetch('api/admin/manage_orders.php', { method: 'POST', credentials: 'include', headers: getAuthHeaders(),
        body: JSON.stringify({ action: 'update_status', id, status: newStatus })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({ icon: 'success', title: 'Order Updated!', text: `Order for ${name} is now ${newStatus}.`, timer: 1500, showConfirmButton: false });
            loadOrders();
        } else {
            Swal.fire('Error', data.message || 'Failed to update order', 'error');
        }
    })
    .catch(err => {
        console.error("Update error:", err);
        Swal.fire('Error', 'Connection failed.', 'error');
    });
}

function deleteOrder(id) {
    Swal.fire({
        title: 'Delete this order?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete'
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('api/admin/manage_orders.php', { method: 'POST', credentials: 'include', headers: getAuthHeaders(),
            body: JSON.stringify({ action: 'delete', id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: 'Order deleted', showConfirmButton: false, timer: 2000 });
                loadOrders();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    });
}


// =============================================================
// LIVE CHAT
// =============================================================

function loadAdminChatUsers() {

    fetch('api/check_session.php', {
        method: 'POST',
        credentials: 'include',
        headers: getAuthHeaders(),
        body: JSON.stringify({
            type: 'admin_get_chat_users'
        })
    })
    .then(res => {
        if (!res.ok) {
            throw new Error('Unable to load chat users.');
        }

        return res.json();
    })
    .then(data => {

        if (data.status !== 'success') {
            throw new Error(
                data.message || 'Unable to load chat users.'
            );
        }

        const list =
            document.getElementById('adminChatUserList');

        if (!list) {
            return;
        }

        const users = data.users || [];

        if (!users.length) {

            list.innerHTML = `
                <div class="p-4 text-center text-muted">
                    No active conversations.
                </div>
            `;

        } else {

            list.innerHTML = users.map(user => {

                const isActive =
                    String(currentChatUserId) ===
                    String(user.id);

                const unread =
                    parseInt(user.unread_count || 0);

                const safeName =
                    String(user.full_name || 'User')
                        .replace(/"/g, '&quot;');

                return `
                    <div
                        class="p-3 border-bottom chat-user-item
                        ${isActive ? 'active-chat' : ''}"
                        style="cursor:pointer;"
                        data-id="${user.id}"
                        data-name="${safeName}"
                        onclick="selectUserChat(this)"
                    >

                        <div
                            class="d-flex
                            justify-content-between
                            align-items-center mb-1"
                        >

                            <span
                                class="fw-bold text-truncate"
                                style="max-width:150px;"
                            >
                                ${user.full_name || 'User'}
                            </span>

                            <div
                                class="d-flex
                                gap-1
                                align-items-center"
                            >

                                ${
                                    unread > 0
                                    ? `
                                        <span
                                            class="badge
                                            rounded-pill
                                            bg-danger"
                                        >
                                            ${unread}
                                        </span>
                                    `
                                    : ''
                                }

                                ${
                                    user.chat_session === 'live'
                                    ? `
                                        <span
                                            class="badge
                                            bg-success"
                                        >
                                            Live
                                        </span>
                                    `
                                    : ''
                                }

                            </div>

                        </div>

                        <small
                            class="text-muted
                            text-truncate d-block"
                        >
                            ${user.last_message || 'No messages yet'}
                        </small>

                    </div>
                `;

            }).join('');

        }

        const badge =
            document.getElementById('badgeChat');

        if (badge) {

            const unread =
                parseInt(data.total_unread || 0);

            badge.textContent = unread;

            badge.style.display =
                unread > 0
                    ? 'inline-block'
                    : 'none';
        }

    })
    .catch(err => {

        console.error(
            'Chat users error:',
            err
        );

        const list =
            document.getElementById(
                'adminChatUserList'
            );

        if (list) {

            list.innerHTML = `
                <div
                    class="p-4
                    text-center
                    text-danger
                    small"
                >
                    Could not load chats.
                </div>
            `;

        }

    });

}


/* =============================================================
   SELECT CUSTOMER
   ============================================================= */

function selectUserChat(element) {

    if (!element) {
        return;
    }

    const userId =
        element.dataset.id;

    const userName =
        element.dataset.name || 'Customer';

    currentChatUserId =
        userId;

    const inputArea =
        document.getElementById(
            'adminInputArea'
        );

    if (inputArea) {
        inputArea.classList.remove('d-none');
    }

    const title =
        document.getElementById(
            'chattingWithTitle'
        );

    if (title) {

        title.innerText =
            'Chatting with: ' + userName;

    }

    loadAdminMessages(userId);

    loadAdminChatUsers();

}


/* =============================================================
   LOAD CUSTOMER MESSAGES
   ============================================================= */

function loadAdminMessages(userId) {

    if (!userId) {
        return;
    }

    currentChatUserId =
        userId;

    fetch('api/check_session.php', {

        method: 'POST',

        credentials: 'include',

        headers: getAuthHeaders(),

        body: JSON.stringify({

            type:
                'admin_get_messages',

            user_id:
                userId

        })

    })
    .then(res => {

        if (!res.ok) {
            throw new Error(
                'Unable to load messages.'
            );
        }

        return res.json();

    })
    .then(data => {

        const body =
            document.getElementById(
                'adminChatBody'
            );

        if (!body) {
            return;
        }

        const msgs =
            data.messages || [];

        body.innerHTML =
            msgs.length

            ?

            msgs.map(message => {

                const isAdmin =
                    message.sender === 'admin';

                return `
                    <div
                        class="mb-3 d-flex
                        ${
                            isAdmin
                            ? 'justify-content-end'
                            : 'justify-content-start'
                        }"
                    >

                        <div
                            class="p-3 rounded-3 shadow-sm"
                            style="
                                max-width:75%;
                                ${
                                    isAdmin
                                    ?
                                    'background:#b30000;color:white;'
                                    :
                                    'background:#fff;border:1px solid #ddd;'
                                }
                            "
                        >

                            <div>
                                ${message.message}
                            </div>

                            <small
                                style="
                                    font-size:0.7rem;
                                    display:block;
                                    margin-top:5px;
                                    opacity:0.8;
                                "
                            >
                                ${message.time || ''}
                            </small>

                        </div>

                    </div>
                `;

            }).join('')

            :

            `
                <div
                    class="text-center
                    text-muted
                    mt-5"
                >
                    No messages yet.
                </div>
            `;

        setTimeout(() => {

            body.scrollTop =
                body.scrollHeight;

        }, 50);

    })
    .catch(err => {

        console.error(
            'Messages error:',
            err
        );

    });

}


/* =============================================================
   SEND ADMIN REPLY
   ============================================================= */

function sendAdminReply() {

    const input =
        document.getElementById(
            'adminReplyInput'
        );

    if (!input) {
        return;
    }

    const msg =
        input.value.trim();

    if (!msg) {
        return;
    }

    if (!currentChatUserId) {

        Swal.fire(
            'No Customer Selected',
            'Please select a customer first.',
            'info'
        );

        return;
    }


    fetch('api/check_session.php', {

        method: 'POST',

        credentials: 'include',

        headers: getAuthHeaders(),

        body: JSON.stringify({

            type:
                'admin_send',

            user_id:
                currentChatUserId,

            message:
                msg

        })

    })
    .then(res => {

        if (!res.ok) {
            throw new Error(
                'Unable to send message.'
            );
        }

        return res.json();

    })
    .then(data => {

        if (data.status !== 'success') {

            Swal.fire(
                'Error',
                data.message ||
                'Unable to send message.',
                'error'
            );

            return;
        }

        input.value = '';

        loadAdminMessages(
            currentChatUserId
        );

        loadAdminChatUsers();

    })
    .catch(err => {

        console.error(
            'Send error:',
            err
        );

        Swal.fire(
            'Error',
            'Unable to send your message.',
            'error'
        );

    });

}


/* =============================================================
   END LIVE SESSION
   ============================================================= */

function endLiveSession() {

    if (!currentChatUserId) {
        return;
    }

    Swal.fire({

        title:
            'End Live Session?',

        text:
            'This will switch the customer back to Bot Mode.',

        icon:
            'warning',

        showCancelButton:
            true,

        confirmButtonColor:
            '#e62429',

        cancelButtonColor:
            '#6c757d',

        confirmButtonText:
            'Yes, End Session',

        cancelButtonText:
            'Cancel'

    })
    .then(result => {

        if (!result.isConfirmed) {
            return;
        }

        fetch(
            'api/check_session.php',
            {
                method: 'POST',

                credentials: 'include',

                headers: getAuthHeaders(),

                body: JSON.stringify({

                    type:
                        'admin_end_session',

                    user_id:
                        currentChatUserId

                })

            }
        )
        .then(res => {

            if (!res.ok) {

                throw new Error(
                    'Unable to end live session.'
                );

            }

            return res.json();

        })
        .then(data => {

            if (
                data.status !==
                'success'
            ) {

                Swal.fire(
                    'Error',
                    data.message ||
                    'Failed to end session.',
                    'error'
                );

                return;
            }

            const inputArea =
                document.getElementById(
                    'adminInputArea'
                );

            if (inputArea) {

                inputArea.classList.add(
                    'd-none'
                );

            }

            const body =
                document.getElementById(
                    'adminChatBody'
                );

            if (body) {

                body.innerHTML = `
                    <div
                        class="text-center
                        mt-5
                        text-muted"
                    >

                        <i
                            class="bi
                            bi-chat-left-dots
                            fs-1
                            d-block
                            mb-2"
                        ></i>

                        <p>
                            Select a customer
                            to start chatting
                        </p>

                    </div>
                `;

            }

            currentChatUserId =
                null;

            loadAdminChatUsers();

            Swal.fire(
                'Ended',
                'Live session ended.',
                'success'
            );

        })
        .catch(err => {

            console.error(
                'End session error:',
                err
            );

            Swal.fire(
                'System Error',
                'Could not end the live session.',
                'error'
            );

        });

    });

}

// =============================================================
// EXPORT CSV
// =============================================================

function exportCSV(type) {
    let rows = [], headers = [], filename = '';
    if (type === 'appointments') {
        headers  = ['Customer', 'Service', 'Vehicle', 'Date', 'Status'];
        rows     = masterData.appointments.map(a => [a.full_name || 'Guest', a.service_type, a.vehicle_info || '—', a.appointment_date, a.status]);
        filename = 'appointments.csv';
    } else if (type === 'users') {
        headers  = ['ID', 'Full Name', 'Email', 'Phone', 'Role', 'Joined'];
        rows     = masterData.users.map(u => [u.id, u.full_name, u.email, u.phone || '—', u.role_id == 2 ? 'Admin' : 'Customer', u.created_at || '—']);
        filename = 'users.csv';
    } else if (type === 'history') {
        headers  = ['Customer', 'Service', 'Vehicle', 'Plate No.', 'Date'];
        rows     = historyData.map(h => [h.full_name || '—', h.service_type || '—', h.brand && h.model ? `${h.brand} ${h.model} (${h.year_model || '—'})` : '—', h.plate_number || '—', h.service_date || '—']);
        filename = 'service_history.csv';
    }
    const csv  = [headers, ...rows].map(r => r.map(c => `"${c}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = filename; a.click();
    URL.revokeObjectURL(url);
}


function postData(endpoint, payload) {
    fetch(endpoint, {
        method: 'POST', credentials: 'include',
        headers: getAuthHeaders(),
        body: JSON.stringify(payload)
    })
    .then(res => {
        if (!res.ok) {
            return res.text().then(text => {
                throw new Error(`Server error ${res.status}: ${text}`);
            });
        }
        return res.json();
    })
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: data.message || 'Updated successfully', showConfirmButton: false, timer: 2500 });
            // ✅ Wrapped separately so errors here don't trigger the catch above
            try {
                loadFullSiteData();
                historyData = [];
                loadServiceHistory();
            } catch (reloadErr) {
                console.warn('Reload error (non-critical):', reloadErr.message);
            }
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(err => {
        console.error('postData error:', err.message);
        Swal.fire('Error', 'Something went wrong. Check the console.', 'error');
    });
}


// =============================================================
// REALTIME AUTO-REFRESH
// =============================================================

function startRealtime() {
    async function loop() {
        try {
            loadFullSiteData();
            loadAdminChatUsers();
            loadParts();
            loadInquiries();
            loadOrders();
            loadBusyDates();
        } catch (e) {
            console.error("Realtime error:", e);
        }
        setTimeout(loop, 5000);
    }
    loop();
}

function updateLiveChatBadge() {
    fetch('api/check_session.php', { method: 'POST', credentials: 'include', headers: getAuthHeaders(), body: JSON.stringify({type:'admin_get_chat_users'}) })
        .then(res => {
            if (!res.ok) throw new Error('Unauthorized');
            return res.json();
        })
        .then(data => {
            if (data.status === 'success') {
                const users        = data.users ?? [];
                const waitingCount = users.filter(u => u.chat_session === 'waiting').length;
                const totalUnread  = data.total_unread ?? 0;
                document.getElementById('badgeChat').textContent = waitingCount + totalUnread;
            }
        })
        .catch(err => console.error('Badge update error:', err));
}


// =============================================================
// BUSY DATES
// =============================================================

let busyDatesData = [];

function loadBusyDates() {
    fetch('api/admin/manage_busy_dates.php', { credentials: 'include', headers: getAuthHeaders() })
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') return;
            busyDatesData = data.dates;
            document.getElementById('badgeBusyDates').textContent =
                data.dates.filter(d => d.is_busy == 1).length;
            renderBusyDates(data.dates);
        });
}

function applyBusyFilter() {
    const filter = document.getElementById('busyDateFilter')?.value || '';
    const term = globalSearchTerm;
    const filtered = busyDatesData.filter(d => {
        const matchesFilter = filter === '' || d.is_busy == filter;
        const matchesTerm = !term ||
            (d.appointment_date && d.appointment_date.toLowerCase().includes(term));
        return matchesFilter && matchesTerm;
    });
    renderBusyDates(filtered);
}

function renderBusyDates(dates) {
    const tbody = document.getElementById('busyDatesTableBody');
    if (!dates.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No dates found.</td></tr>';
        return;
    }
    tbody.innerHTML = dates.map(d => {
        const dateObj   = new Date(d.appointment_date + 'T00:00:00');
        const formatted = dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        const dayName   = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
        const isBusy    = d.is_busy == 1;
        const pct       = d.slot_limit > 0 ? Math.min(Math.round((d.current_bookings / d.slot_limit) * 100), 100) : 100;
        const barColor  = pct >= 100 ? 'bg-danger' : pct >= 60 ? 'bg-warning' : 'bg-success';
        return `
        <tr>
            <td class="fw-bold">${formatted}</td>
            <td><span class="badge bg-light text-dark border">${dayName}</span></td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <span>${d.current_bookings} / ${d.slot_limit}</span>
                    <div class="progress flex-grow-1" style="height:6px;min-width:80px;">
                        <div class="progress-bar ${barColor}" style="width:${pct}%"></div>
                    </div>
                </div>
            </td>
            <td><span class="badge ${isBusy ? 'bg-danger' : 'bg-success'}">${isBusy ? 'Busy' : 'Available'}</span></td>
            <td>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-dark"
                            onclick="editBusyDate(${d.id}, '${d.appointment_date}', ${d.slot_limit}, ${d.is_busy})"
                            title="Edit">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-sm ${isBusy ? 'btn-outline-success' : 'btn-outline-warning'}"
                            onclick="toggleBusy(${d.id}, ${isBusy ? 0 : 1})"
                            title="${isBusy ? 'Mark as Available' : 'Mark as Busy'}">
                        <i class="bi bi-${isBusy ? 'unlock' : 'lock'}"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="deleteBusyDate(${d.id}, '${formatted}')"
                            title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function busyDateForm(v = {}) {
    const today = new Date().toISOString().split('T')[0];
    return `
        <div class="text-start">
            <div class="mb-3">
                <label class="form-label fw-semibold small">Date</label>
                <input type="date" id="bd-date" class="form-control"
                       value="${v.date || ''}" min="${today}">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small">Slot Limit</label>
                <input type="number" id="bd-limit" class="form-control"
                       value="${v.limit ?? 5}" min="1">
                <small class="text-muted">Max appointments allowed on this day before marked busy</small>
            </div>
            <div class="mb-2">
                <label class="form-label fw-semibold small">Status</label>
                <select id="bd-busy" class="form-select">
                    <option value="1" ${v.isBusy == 1 || v.isBusy === undefined ? 'selected' : ''}>🔴 Busy (Blocked)</option>
                    <option value="0" ${v.isBusy == 0 ? 'selected' : ''}>🟢 Available</option>
                </select>
            </div>
        </div>`;
}

function getBusyDateFormValues(action, id = null) {
    const date    = document.getElementById('bd-date').value;
    const limit   = parseInt(document.getElementById('bd-limit').value);
    const is_busy = parseInt(document.getElementById('bd-busy').value);
    if (!date) { Swal.showValidationMessage('Please select a date.'); return false; }
    if (!limit || limit < 1) { Swal.showValidationMessage('Slot limit must be at least 1.'); return false; }
    const payload = { action, date, limit, is_busy };
    if (id) payload.id = id;
    return payload;
}

function openBlockDate() {
    Swal.fire({
        title: '<i class="bi bi-calendar-plus me-2"></i> Add Busy Date',
        html: busyDateForm(),
        showCancelButton: true,
        confirmButtonColor: '#b30000',
        confirmButtonText: '<i class="bi bi-plus-lg me-1"></i> Add Date',
        preConfirm: () => getBusyDateFormValues('block')
    }).then(result => {
        if (result.isConfirmed) postBusyDate(result.value);
    });
}

function editBusyDate(id, date, limit, isBusy) {
    Swal.fire({
        title: '<i class="bi bi-pencil-square me-2"></i>Edit Busy Date',
        html: busyDateForm({ date, limit, isBusy }),
        showCancelButton: true,
        confirmButtonColor: '#b30000',
        confirmButtonText: 'Save Changes',
        preConfirm: () => getBusyDateFormValues('edit', id)
    }).then(result => {
        if (result.isConfirmed) postBusyDate(result.value);
    });
}

function postBusyDate(payload) {
    fetch('api/admin/manage_busy_dates.php', { method: 'POST', credentials: 'include', headers: getAuthHeaders(),
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 2000 });
            loadBusyDates();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
}

function toggleBusy(id, isBusy) {
    fetch('api/admin/manage_busy_dates.php', { method: 'POST', credentials: 'include', headers: getAuthHeaders(),
        body: JSON.stringify({ action: 'toggle_busy', id, is_busy: isBusy })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: isBusy ? 'Marked as Busy' : 'Marked as Available', showConfirmButton: false, timer: 2000 });
            loadBusyDates();
        }
    });
}

function deleteBusyDate(id, date) {
    Swal.fire({
        title: `Remove ${date}?`,
        text: 'This will remove the date record but not the actual appointments.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, remove'
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('api/admin/manage_busy_dates.php', { method: 'POST', credentials: 'include', headers: getAuthHeaders(),
            body: JSON.stringify({ action: 'delete', id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: 'Date removed', showConfirmButton: false, timer: 2000 });
                loadBusyDates();
            }
        });
    });
}

setInterval(() => {
    fetch('/api/check_session.php', { method: 'POST', credentials: 'include', headers: getAuthHeaders(),
        body: JSON.stringify({})
    })
    .then(res => {
        if (!res.ok) return null;
        return res.json();
    })
    .then(data => {
        if (data && data.valid === false) {
            Swal.fire({
                icon: 'warning',
                title: 'Session Ended',
                text: 'Your account was logged in from another device. You have been logged out.',
                confirmButtonColor: '#e62429',
                allowOutsideClick: false
            }).then(() => {
                window.location.href = '/login.php';
            });
        }
    })
    .catch(() => {});
}, 30000);
// =============================================================
// SERVICES MANAGEMENT
// =============================================================

function loadServices() {
    fetch('api/admin/manage_services.php', { headers: getAuthHeaders() })
        .then(r => r.json())
        .then(data => {
            servicesData = data.services || [];
            populateServicesCategoryFilter();
            filterAndRenderServices();
        })
        .catch(() => {
            const tbody = document.getElementById('servicesTableBody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="3" class="text-danger text-center">Failed to load services.</td></tr>';
        });
}

function populateServicesCategoryFilter() {
    const sel = document.getElementById('servicesCategoryFilter');
    if (!sel) return;
    const current = sel.value;
    const categories = [...new Set(servicesData.map(s => s.category).filter(Boolean))].sort();
    sel.innerHTML = '<option value="">All Categories</option>' +
        categories.map(c => `<option value="${c}">${c}</option>`).join('');
    if (categories.includes(current)) sel.value = current;
}

function filterAndRenderServices() {
    const term = globalSearchTerm;
    const category = document.getElementById('servicesCategoryFilter')?.value || '';
    const activeFilter = document.getElementById('servicesActiveFilter')?.value || '';

    const filtered = servicesData.filter(s => {
        const matchesTerm = !term ||
            (s.name && s.name.toLowerCase().includes(term)) ||
            (s.category && s.category.toLowerCase().includes(term));
        const matchesCategory = !category || s.category === category;
        const matchesActive = activeFilter === '' || String(s.is_active) === activeFilter;
        return matchesTerm && matchesCategory && matchesActive;
    });
    renderServices(filtered);
}

function renderServices(services) {
    const tbody = document.getElementById('servicesTableBody');
    if (!tbody) return;
    if (!services.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No services match your search.</td></tr>';
        return;
    }
    tbody.innerHTML = services.map(s => `
        <tr>
            <td><strong>${s.name}</strong></td>
            <td><span class="badge bg-secondary">${s.category}</span></td>
            <td>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch"
                           style="cursor:pointer; width:2.5em; height:1.3em;"
                           ${s.is_active == 1 ? 'checked' : ''}
                           onchange="toggleService(${s.id}, this.checked ? 1 : 0)">
                </div>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-danger"
                        onclick="deleteService(${s.id}, '${s.name.replace(/'/g,"\\'")}')" title="Remove Service">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>`).join('');
}

function deleteService(id, name) {
    Swal.fire({
        title: 'Remove Service?',
        html: `Remove <strong>${name}</strong> from the services list?<br>
               <small class="text-muted">This can't be undone. Services with active appointments can't be removed — deactivate them instead.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Remove'
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('api/admin/manage_services.php', {
            method: 'POST',
            headers: getAuthHeaders(),
            body: JSON.stringify({ action: 'delete', id })
        })
        .then(r => r.json())
        .then(d => {
            if (d.status === 'success') {
                Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: 'Service removed', showConfirmButton: false, timer: 2000 });
                loadServices();
            } else {
                Swal.fire('Cannot Remove', d.message || 'This service could not be removed.', 'error');
            }
        })
        .catch(() => Swal.fire('Error', 'Something went wrong. Please try again.', 'error'));
    });
}

function openAddService() {
    Swal.fire({
        title: 'Add New Service',
        html: `
            <div class="text-start">
                <label class="form-label fw-semibold">Service Name *</label>
                <input id="svcName" class="form-control mb-3" placeholder="e.g. Engine Overhaul">
                <label class="form-label fw-semibold">Category</label>
                <input id="svcCategory" class="form-control" placeholder="e.g. Popular Services" value="Popular Services">
            </div>`,
        confirmButtonText: 'Add Service',
        confirmButtonColor: '#e62429',
        showCancelButton: true,
        preConfirm: () => {
            const name = document.getElementById('svcName').value.trim();
            if (!name) { Swal.showValidationMessage('Service name is required.'); return false; }
            return {
                action: 'add',
                name,
                category: document.getElementById('svcCategory').value.trim() || 'General'
            };
        }
    }).then(result => {
        if (result.isConfirmed) {
            fetch('api/admin/manage_services.php', { method:'POST', headers:getAuthHeaders(), body:JSON.stringify(result.value) })
                .then(r=>r.json()).then(d=>{
                    if(d.status==='success'){ Swal.fire({toast:true,position:'bottom-end',icon:'success',title:d.message||'Service added',showConfirmButton:false,timer:2000}); loadServices(); }
                    else Swal.fire('Error', d.message, 'error');
                });
        }
    });
}

function toggleService(id, newStatus) {
    fetch('api/admin/manage_services.php', { method:'POST', headers:getAuthHeaders(), body:JSON.stringify({ action:'toggle', id, is_active:newStatus }) })
        .then(r=>r.json()).then(d=>{ if(d.status!=='success'){ Swal.fire('Error', d.message, 'error'); loadServices(); } });
}

// =============================================================
// MECHANICS MANAGEMENT
// =============================================================

function loadMechanics() {
    fetch('api/admin/manage_mechanics.php', { headers: getAuthHeaders() })
        .then(r => r.json())
        .then(data => {
            mechanicsData = data.mechanics || [];
            filterAndRenderMechanics();
        })
        .catch(() => {
            const tbody = document.getElementById('mechanicsTableBody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="4" class="text-danger text-center">Failed to load mechanics.</td></tr>';
        });
}

function filterAndRenderMechanics() {
    const term = globalSearchTerm;
    const statusFilter = document.getElementById('mechanicsStatusFilter')?.value || '';

    const filtered = mechanicsData.filter(m => {
        const matchesTerm = !term ||
            (m.name && m.name.toLowerCase().includes(term)) ||
            (m.specialty && m.specialty.toLowerCase().includes(term));
        const matchesStatus = !statusFilter || m.status === statusFilter;
        return matchesTerm && matchesStatus;
    });
    renderMechanics(filtered);
}

// Mechanic status is one of: available, on_break, on_duty, unavailable.
// In the app these show as "Available", "On Leave" (on_break), "Busy"
// (on_duty), "Unavailable". Only plain 'available' is assignable — On
// Leave and Busy both block the admin from assigning a job, same as
// Unavailable.
function mechanicStatusLabel(status) {
    return { available: 'Available', on_break: 'On Leave', on_duty: 'Busy', unavailable: 'Unavailable' }[status] || status;
}
function mechanicStatusBadgeClass(status) {
    if (status === 'available') return 'bg-success';
    if (status === 'on_break') return 'bg-secondary';
    if (status === 'on_duty') return 'bg-warning text-dark';
    return 'bg-secondary'; // unavailable
}
function mechanicIsAssignable(status) {
    return status === 'available';
}

function renderMechanics(mechanics) {
    const tbody = document.getElementById('mechanicsTableBody');
    if (!tbody) return;
    if (!mechanics.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No mechanics match your search.</td></tr>';
        return;
    }
    tbody.innerHTML = mechanics.map(m => `
        <tr>
            <td><strong>${m.name}</strong></td>
            <td>${m.specialty || '—'}</td>
            <td><span class="badge ${mechanicStatusBadgeClass(m.status)}">${mechanicStatusLabel(m.status)}</span></td>
            <td>
                <button class="btn btn-sm btn-outline-primary me-1" onclick="editMechanic(${m.id})"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteMechanic(${m.id}, '${m.name.replace(/'/g,"\\'")}')"><i class="bi bi-trash"></i></button>
            </td>
        </tr>`).join('');
}

function openAddMechanic() {
    Swal.fire({
        title: 'Add Mechanic',
        html: `
            <div class="text-start">
                <label class="form-label fw-semibold">Full Name *</label>
                <input id="mName" class="form-control mb-3" placeholder="e.g. Juan Dela Cruz">
                <label class="form-label fw-semibold">Specialty</label>
                <input id="mSpec" class="form-control" placeholder="e.g. Engine, Electrical, etc.">
            </div>`,
        confirmButtonText: 'Add Mechanic',
        confirmButtonColor: '#e62429',
        showCancelButton: true,
        preConfirm: () => {
            const name = document.getElementById('mName').value.trim();
            if (!name) { Swal.showValidationMessage('Name is required.'); return false; }
            return { action: 'add', name, specialty: document.getElementById('mSpec').value.trim() };
        }
    }).then(result => {
        if (result.isConfirmed) fetch('api/admin/manage_mechanics.php', { method:'POST', headers:getAuthHeaders(), body:JSON.stringify(result.value) })
                .then(r=>r.json()).then(d=>{
                    if(d.status==='success'){ Swal.fire({toast:true,position:'bottom-end',icon:'success',title:d.message||'Done',showConfirmButton:false,timer:2000}); loadMechanics(); }
                    else Swal.fire('Error', d.message, 'error');
                });
    });
}

function editMechanic(id) {
    fetch('api/admin/manage_mechanics.php', { headers: getAuthHeaders() })
        .then(r => r.json())
        .then(data => {
            const m = data.mechanics.find(x => x.id == id);
            if (!m) return;
            Swal.fire({
                title: 'Edit Mechanic',
                html: `
                    <div class="text-start">
                        <label class="form-label fw-semibold">Full Name *</label>
                        <input id="mName" class="form-control mb-3" value="${m.name}">
                        <label class="form-label fw-semibold">Specialty</label>
                        <input id="mSpec" class="form-control mb-3" value="${m.specialty || ''}">
                        <label class="form-label fw-semibold">Availability</label>
                        <select id="mStatus" class="form-select">
                            <option value="available" ${m.status === 'available' ? 'selected' : ''}>Available</option>
                            <option value="on_break" ${m.status === 'on_break' ? 'selected' : ''}>On Leave</option>
                            <option value="on_duty" ${m.status === 'on_duty' ? 'selected' : ''}>Busy</option>
                            <option value="unavailable" ${m.status === 'unavailable' ? 'selected' : ''}>Unavailable</option>
                        </select>
                        <small class="text-muted d-block mt-1">On Leave and Busy mechanics cannot be assigned jobs until set back to Available.</small>
                    </div>`,
                confirmButtonText: 'Save',
                confirmButtonColor: '#e62429',
                showCancelButton: true,
                preConfirm: () => ({
                    action: 'update', id,
                    name: document.getElementById('mName').value.trim(),
                    specialty: document.getElementById('mSpec').value.trim(),
                    status: document.getElementById('mStatus').value
                })
            }).then(result => {
                if (result.isConfirmed) fetch('api/admin/manage_mechanics.php', { method:'POST', headers:getAuthHeaders(), body:JSON.stringify(result.value) })
                .then(r=>r.json()).then(d=>{
                    if(d.status==='success'){ Swal.fire({toast:true,position:'bottom-end',icon:'success',title:d.message||'Done',showConfirmButton:false,timer:2000}); loadMechanics(); }
                    else Swal.fire('Error', d.message, 'error');
                });
            });
        });
}

function deleteMechanic(id, name) {
    Swal.fire({
        title: 'Remove Mechanic?',
        text: `Remove ${name} from the roster?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e62429',
        confirmButtonText: 'Remove'
    }).then(result => {
        if (result.isConfirmed) {
            fetch('api/admin/manage_mechanics.php', { method:'POST', headers:getAuthHeaders(), body:JSON.stringify({ action:'delete', id }) })
                .then(r=>r.json()).then(d=>{ if(d.status==='success') loadMechanics(); else Swal.fire('Error', d.message, 'error'); });
        }
    });
}

// =============================================================
// ASSIGN MECHANIC TO APPOINTMENT
// =============================================================

function assignMechanic(appointmentId) {
    // Load mechanics list then show assignment modal
    fetch('api/admin/manage_mechanics.php', { headers: getAuthHeaders() })
        .then(r => r.json())
        .then(data => {
            if (!data.mechanics || !data.mechanics.length) {
                Swal.fire('No Mechanics', 'Add mechanics in the Mechanics tab first.', 'info');
                return;
            }
            const options = data.mechanics
                .map(m => `<option value="${m.id}" ${mechanicIsAssignable(m.status) ? '' : 'disabled'}>${m.name} (${m.specialty || 'General'}) — ${mechanicStatusLabel(m.status)}</option>`)
                .join('');
            Swal.fire({
                title: 'Assign Mechanic',
                html: `
                    <div class="text-start">
                        <label class="form-label fw-semibold">Select Technician</label>
                        <select id="mechanicSelect" class="form-select">
                            <option value="0">— Unassign —</option>
                            ${options}
                        </select>
                        <small class="text-muted mt-1 d-block">Unavailable, On Leave, and Busy mechanics are greyed out.</small>
                    </div>`,
                confirmButtonText: 'Assign',
                confirmButtonColor: '#e62429',
                showCancelButton: true,
                preConfirm: () => ({
                    action: 'assign',
                    appointment_id: appointmentId,
                    mechanic_id: parseInt(document.getElementById('mechanicSelect').value)
                })
            }).then(result => {
                if (result.isConfirmed) {
                    fetch('api/admin/manage_mechanics.php', { method:'POST', headers:getAuthHeaders(), body:JSON.stringify(result.value) })
                    .then(r=>r.json()).then(d=>{
                        if(d.status==='success'){ Swal.fire({toast:true,position:'bottom-end',icon:'success',title:d.message||'Assigned',showConfirmButton:false,timer:2000}); loadFullSiteData(); }
                        else Swal.fire('Error', d.message, 'error');
                    });
                }
            });
        });
}