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
let leaveRequestsData = [];
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
    loadLeaveRequests();

    updateLiveChatBadge();
    setInterval(updateLiveChatBadge, 20000);

    // Orders auto-approve server-side after 5 minutes of sitting as
    // 'pending' — poll so the table picks that up without a manual reload.
    setInterval(loadOrders, 30000);

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
            (p.brand && p.brand.toLowerCase().includes(term)) ||
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
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No parts match your search.</td></tr>';
        return;
    }
    tbody.innerHTML = parts.map(p => {
        const brand = (p.brand || '').replace(/'/g, "\\'");
        return `
        <tr>
            <td>${
                p.image_url
                    ? `<img src="${p.image_url}" style="width:50px;height:40px;object-fit:cover;border-radius:6px;"
                            onerror="this.style.display='none';this.nextElementSibling.style.display='inline';">
                       <span class="text-muted" style="display:none;">No image</span>`
                    : `<span class="text-muted">No image</span>`
            }</td>
            <td class="fw-bold">${p.name}</td>
            <td>${p.brand ? `<span class="badge bg-light text-dark border">${p.brand}</span>` : `<span class="text-muted">—</span>`}</td>
            <td><span class="badge bg-light text-dark border">${p.category}</span></td>
            <td>₱${parseFloat(p.price).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
            <td><span class="badge ${p.stock > 0 ? 'bg-success' : 'bg-danger'}">${p.stock}</span></td>
            <td>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-dark"
                            onclick="openEditPart(${p.id},'${p.name.replace(/'/g,"\\'")}','${brand}','${(p.description||'').replace(/'/g,"\\'")}',${p.price},'${p.category}','${p.image_url||''}',${p.stock})">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="deletePart(${p.id},'${p.name.replace(/'/g,"\\'")}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
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
                    <button class="btn btn-sm btn-outline-success" onclick="updateInquiry(${i.id})" title="Reply to Inquiry" aria-label="Reply to Inquiry">
                        <i class="bi bi-chat-left-text-fill"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteInquiry(${i.id})" title="Delete Inquiry" aria-label="Delete Inquiry">
                        <i class="bi bi-trash3-fill"></i>
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
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger py-4">Failed to load orders.</td></tr>';
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
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">No orders match your search.</td></tr>';
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
        const statusLower = o.status.toLowerCase();
        const isPending  = statusLower === 'pending';
        const isApproved = statusLower === 'approved';
        const isShipped  = statusLower === 'shipped';
        const address   = o.address || '—';
        const replyHtml = (o.cancellation_reply || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

        return `
        <tr>
            <td><code style="color:#b30000;">${orderNo}</code></td>
            <td class="fw-bold">${o.customer_name}</td>
            <td>${o.contact}</td>
            <td class="text-muted small" style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                title="${address}">${address}</td>
            <td class="text-muted small" style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                title="${itemsList}">${itemsList}</td>
            <td class="fw-bold text-danger">₱${parseFloat(o.total_price).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
            <td>${date}</td>
            <td>
                ${statusBadge(o.status)}
                ${o.status.toLowerCase() === 'cancelled' && replyHtml
                    ? `<div class="small text-muted mt-1" style="max-width:110px;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.3em;" title="${replyHtml}"><i class="bi bi-reply-fill me-1"></i>${replyHtml}</div>`
                    : ''}
            </td>
            <td>
                <div class="btn-group">
                    ${isPending ? `
                        <button class="btn btn-sm btn-outline-success"
                                onclick="updateOrder(${o.id}, '${o.status}', '${safeName}')" title="Review Order Status" aria-label="Review Order Status">
                            <i class="bi bi-clipboard-check-fill"></i>
                        </button>` : ''}
                    ${isApproved ? `
                        <button class="btn btn-sm btn-outline-info"
                                onclick="updateOrder(${o.id}, '${o.status}', '${safeName}')" title="Mark as Shipped" aria-label="Mark as Shipped">
                            <i class="bi bi-truck-front-fill"></i> Ship
                        </button>` : ''}
                    ${isShipped ? `
                        <button class="btn btn-sm btn-outline-primary"
                                onclick="updateOrder(${o.id}, '${o.status}', '${safeName}')" title="Mark as Delivered" aria-label="Mark as Delivered">
                            <i class="bi bi-box-seam-fill"></i> Deliver
                        </button>` : ''}
                    <button class="btn btn-sm btn-outline-secondary"
                            onclick="deleteOrder(${o.id})" title="Delete Order" aria-label="Delete Order">
                        <i class="bi bi-trash3-fill"></i>
                    </button>
                    <a href="api/admin/generate_parts_receipt.php?id=${o.id}" target="_blank"
                       class="btn btn-sm btn-outline-primary" title="View Order Receipt" aria-label="View Order Receipt">
                        <i class="bi bi-receipt-cutoff"></i>
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
        'shipped':   'bg-info text-dark',
        'delivered': 'bg-primary',
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
    filterAndRenderLeaveRequests();
    if (typeof applyBusyFilter === 'function') applyBusyFilter();
}

function renderAppointments(apps) {
    const active = apps;
    document.getElementById("appointmentTableBody").innerHTML = active.length
        ? active.map(a => {
            const notesText = (a.notes || '').trim();
            const notesHtml = notesText.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
            // data-note (not an inline onclick JS string) so multi-line
            // notes with quotes/newlines can't break out of the markup —
            // HTML attributes tolerate raw newlines; JS string literals
            // embedded in onclick="..." do not.
            const notesCell = notesText
                ? `<span class="d-inline-block" style="max-width:110px;max-height:2.6em;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.3em;cursor:pointer;" title="Click to view full note" data-note="${notesHtml}" onclick="viewAppointmentNote(this.dataset.note)">${notesHtml}</span>`
                : `<span class="text-muted small">—</span>`;
            const replyHtml = (a.cancellation_reply || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
            return `
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
                <td>${notesCell}</td>
                <td>
                    ${statusBadge(a.status)}
                    ${a.status === 'Cancelled' && replyHtml
                        ? `<div class="small text-muted mt-1" style="max-width:110px;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.3em;" title="${replyHtml}"><i class="bi bi-reply-fill me-1"></i>${replyHtml}</div>`
                        : ''}
                </td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-dark" onclick="manageApp(${a.id}, '${a.status}')">
                            <i class="bi bi-pencil-square"></i> Manage
                        </button>
                        ${a.status === 'Pending'
                            ? `<button class="btn btn-sm btn-outline-primary" onclick="assignMechanic(${a.id})" title="Assign Mechanic">
                                   <i class="bi bi-person-plus-fill"></i>
                               </button>`
                            : `<button class="btn btn-sm btn-outline-secondary" disabled title="Mechanic is locked once the appointment is ${a.status.toLowerCase()}">
                                   <i class="bi bi-lock-fill"></i>
                               </button>`}
                    </div>
                </td>
            </tr>`;
        }).join('')
        : '<tr><td colspan="8" class="text-center text-muted py-4">No appointments found.</td></tr>';
}

function viewAppointmentNote(note) {
    Swal.fire({
        title: 'Additional Notes',
        text: note,
        icon: 'info',
        confirmButtonColor: '#b30000'
    });
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
                       class="btn btn-sm btn-outline-primary" title="View Order Receipt" aria-label="View Order Receipt">
                        <i class="bi bi-receipt-cutoff"></i>
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

// Canned reply options shown in the "Reply to customer" dropdown when
// an admin sets an appointment to Cancelled. Selecting one fills the
// textarea below it (still editable before sending) — matches the same
// "canned reply, fully editable" pattern as rejectLeaveRequest().
const CANCELLATION_REPLIES = {
    'reschedule':  "We're sorry to cancel — please contact us to reschedule at your convenience.",
    'mechanic':    "Cancelled due to mechanic unavailability on your selected date. Please rebook for another day.",
    'parts':       "Cancelled due to parts unavailability for this service. We'll notify you once they're back in stock.",
    'customer':    "Cancelled as requested.",
    'conflict':    "Cancelled due to a scheduling conflict. Please choose another available date.",
    'custom':      ''
};

function manageApp(id, currentStatus) {
    const status = (currentStatus || '').toLowerCase();
    const appt   = masterData.appointments.find(a => a.id === id);
    const name   = appt?.full_name || 'this customer';
    const hasMechanic = !!(appt && (appt.mechanic_id || appt.mechanic_name));

    if (status === 'completed') {
        Swal.fire({ title: 'Already Completed', html: `Appointment for <b>${name}</b> has already been completed.`, icon: 'info', confirmButtonColor: '#b30000' });
        return;
    }
    if (status === 'cancelled') {
        Swal.fire({ title: 'Appointment Cancelled', html: `Appointment for <b>${name}</b> has already been cancelled.`, icon: 'info', confirmButtonColor: '#b30000' });
        return;
    }

    // A Pending booking can only be accepted/Confirmed after a mechanic
    // has been assigned. Cancellation remains available without one.
    // Every appointment status change now requires an assigned mechanic.
    // Keep the current status visible, but do not allow the admin to
    // submit a new status until a mechanic has been assigned.
    if (!hasMechanic) {
        Swal.fire({
            title: 'Assign a Mechanic First',
            html: `Appointment for <b>${name}</b> cannot be updated yet.<br><br>
                   <span class="text-muted">A mechanic must be assigned before the appointment status can be changed.</span>`,
            icon: 'warning',
            confirmButtonColor: '#e62429',
            confirmButtonText: 'Assign Mechanic'
        }).then(result => {
            if (result.isConfirmed && typeof assignMechanic === 'function') {
                assignMechanic(id);
            }
        });
        return;
    }

    const inputOptions = {
        'Pending': 'Pending',
        'Confirmed': 'Confirmed',
        'Completed': 'Completed',
        'Cancelled': 'Cancelled',
        'In Progress': 'In Progress',
        'No Show': 'No Show'
    };

    Swal.fire({
        title: 'Update Appointment Status',
        input: 'select',
        inputOptions,
        inputValue: currentStatus,
        footer: '<span class="text-success fw-semibold"><i class="bi bi-person-check-fill me-1"></i>Mechanic assigned — status changes are enabled.</span>',
        showCancelButton: true,
        confirmButtonColor: '#b30000',
        confirmButtonText: 'Update'
    }).then(result => {
        if (!result.isConfirmed || !result.value) return;

        if (result.value === 'Confirmed' && status !== 'pending') {
            Swal.fire({
                title: 'Cannot Accept This Appointment',
                text: 'Only pending appointments can be accepted as Confirmed.',
                icon: 'warning',
                confirmButtonColor: '#e62429'
            });
            return;
        }

        if (result.value === 'Cancelled') {
            promptCancellationReply(id);
        } else {
            postData('api/admin/update_appointment.php', {
                appointment_id: id,
                status: result.value
            });
        }
    });
}

// Populates the cancellation-reply textarea from the dropdown's canned
// text. Kept as a standalone global function (rather than inlining the
// lookup in the <select>'s onchange attribute) because several canned
// replies contain apostrophes/quotes that would otherwise break out of
// the attribute's own quoting.
function applyCannedReply(selectEl) {
    const text = CANCELLATION_REPLIES[selectEl.value] || '';
    document.getElementById('swal-cancel-reply-text').value = text;
}

// Second step shown only when cancelling: a dropdown of canned replies
// (editable in the textarea below before sending) that gets emailed/
// texted to the customer alongside the cancellation notice.
function promptCancellationReply(id) {
    Swal.fire({
        title: 'Reply to Customer',
        html: `
            <div class="text-start mb-3">
                <label class="form-label fw-semibold small">Quick reply</label>
                <select id="swal-cancel-reply-select" class="form-select mb-2" onchange="applyCannedReply(this)">
                    <option value="reschedule" selected>Please contact us to reschedule</option>
                    <option value="mechanic">Mechanic unavailable that day</option>
                    <option value="parts">Parts unavailable</option>
                    <option value="customer">Cancelled as requested</option>
                    <option value="conflict">Scheduling conflict</option>
                    <option value="custom">Custom message...</option>
                </select>
                <label class="form-label fw-semibold small">Message sent to customer (optional — edit as needed)</label>
                <textarea id="swal-cancel-reply-text" class="form-control" rows="3">${CANCELLATION_REPLIES.reschedule}</textarea>
            </div>`,
        showCancelButton: true,
        confirmButtonColor: '#b30000',
        confirmButtonText: 'Cancel Appointment',
        cancelButtonText: 'Back',
        preConfirm: () => document.getElementById('swal-cancel-reply-text').value.trim()
    }).then(result => {
        if (!result.isConfirmed) return;
        postData('api/admin/update_appointment.php', {
            appointment_id: id,
            status: 'Cancelled',
            cancellation_reply: result.value || ''
        });
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

function openEditPart(id, name, brand, desc, price, category, image_url, stock) {
    Swal.fire({
        title: 'Edit Part',
        html: partForm({ name, brand, desc, price, category, image_url, stock }),
        showCancelButton: true,
        confirmButtonColor: '#b30000',
        confirmButtonText: 'Save Changes',
        preConfirm: () => getPartFormValues('edit', id)
    }).then(result => {
        if (result.isConfirmed) postPartData(result.value);
    });
}

function uploadPartImage(inputEl) {
    const file = inputEl.files[0];
    const statusEl = document.getElementById('sp-img-status');
    if (!file) return;

    const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!allowed.includes(file.type)) {
        if (statusEl) statusEl.textContent = 'Only JPG, PNG, WEBP, or GIF images are allowed.';
        inputEl.value = '';
        return;
    }
    if (file.size > 3 * 1024 * 1024) {
        if (statusEl) statusEl.textContent = 'Image must be smaller than 3MB.';
        inputEl.value = '';
        return;
    }

    if (statusEl) statusEl.textContent = 'Uploading...';

    const token = localStorage.getItem('jwt_token');
    const formData = new FormData();
    formData.append('image', file);

    fetch('api/admin/upload_part_image.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Authorization': token ? 'Bearer ' + token : '' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const hidden = document.getElementById('sp-img');
            const preview = document.getElementById('sp-img-preview');
            if (hidden) hidden.value = data.image_url;
            if (preview) {
                preview.src = data.image_url;
                preview.style.display = '';
            }
            if (statusEl) statusEl.textContent = 'Photo uploaded.';
        } else {
            if (statusEl) statusEl.textContent = data.message || 'Upload failed.';
            inputEl.value = '';
        }
    })
    .catch(() => {
        if (statusEl) statusEl.textContent = 'Upload failed. Check your connection.';
        inputEl.value = '';
    });
}

function partForm(v = {}) {
    return `
        <div class="text-start">
            <div class="mb-2"><label class="form-label fw-semibold small">Name</label>
            <input id="sp-name" class="form-control" value="${v.name || ''}"></div>
            <div class="mb-2"><label class="form-label fw-semibold small">Brand</label>
            <input id="sp-brand" class="form-control" value="${v.brand || ''}" placeholder="e.g. Bosch, Brembo, Toyota"></div>
            <div class="mb-2"><label class="form-label fw-semibold small">Description</label>
            <textarea id="sp-desc" class="form-control" rows="2">${v.desc || ''}</textarea></div>
            <div class="mb-2"><label class="form-label fw-semibold small">Price (₱)</label>
            <input id="sp-price" type="number" class="form-control" value="${v.price || ''}"></div>
            <div class="mb-2"><label class="form-label fw-semibold small">Category</label>
            <input id="sp-cat" class="form-control" value="${v.category || ''}" placeholder="e.g. Engine, Brakes, Suspension"></div>
            <div class="mb-2">
                <label class="form-label fw-semibold small">Photo</label>
                <input id="sp-img-file" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control" onchange="uploadPartImage(this)">
                <input type="hidden" id="sp-img" value="${v.image_url || ''}">
                <div class="mt-2 d-flex align-items-center gap-2">
                    <img id="sp-img-preview" src="${v.image_url || ''}" style="width:70px;height:56px;object-fit:cover;border-radius:8px;${v.image_url ? '' : 'display:none;'}">
                    <span id="sp-img-status" class="small text-muted"></span>
                </div>
            </div>
            <div class="mb-2"><label class="form-label fw-semibold small">Stock</label>
            <input id="sp-stock" type="number" class="form-control" value="${v.stock || 0}"></div>
        </div>`;
}

function getPartFormValues(action, id = null) {
    const val = {
        action,
        name:        document.getElementById('sp-name').value.trim(),
        brand:       document.getElementById('sp-brand').value.trim(),
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
    if (currentStatus === 'delivered' || currentStatus === 'completed') {
        Swal.fire({ title: 'Already Delivered', html: `Order for <b>${name}</b> has already been delivered.`, icon: 'info', confirmButtonColor: '#b30000' });
        return;
    }
    if (currentStatus === 'cancelled') {
        Swal.fire({ title: 'Order Cancelled', html: `Order for <b>${name}</b> has already been cancelled.`, icon: 'info', confirmButtonColor: '#b30000' });
        return;
    }
    if (currentStatus === 'approved') {
        Swal.fire({
            title: 'Mark as Shipped?',
            html: `Confirm that the order for <b>${name}</b> has been shipped out.`,
            icon: 'question',
            showConfirmButton: true,
            confirmButtonText: '🚚 Mark Shipped',
            confirmButtonColor: '#0dcaf0',
            showCancelButton: true,
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (result.isConfirmed) executeStatusUpdate(id, 'shipped', name);
        });
        return;
    }
    if (currentStatus === 'shipped') {
        Swal.fire({
            title: 'Mark as Delivered?',
            html: `Confirm that the order for <b>${name}</b> has been delivered/picked up.`,
            icon: 'question',
            showConfirmButton: true,
            confirmButtonText: '✓ Mark Delivered',
            confirmButtonColor: '#198754',
            showCancelButton: true,
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (result.isConfirmed) executeStatusUpdate(id, 'delivered', name);
        });
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
        else if (result.isDenied) promptOrderCancellationReply(id, name);
    });
}

// Canned reply options shown in the "Reply to customer" dropdown when
// an admin declines/cancels a parts order — same pattern as
// CANCELLATION_REPLIES for appointments above.
const ORDER_CANCELLATION_REPLIES = {
    'stock':      "We're sorry, one or more items in your order are currently out of stock.",
    'payment':    "Cancelled due to a payment issue. Please contact us or place a new order.",
    'customer':   "Cancelled as requested.",
    'incorrect':  "Cancelled due to incorrect or incomplete order details. Please place a new order.",
    'unreachable':"Cancelled — we were unable to reach you to confirm delivery/pickup details.",
    'custom':     ''
};

// Populates the order cancellation-reply textarea from the dropdown's
// canned text — mirrors applyCannedReply() for appointments.
function applyOrderCannedReply(selectEl) {
    const text = ORDER_CANCELLATION_REPLIES[selectEl.value] || '';
    document.getElementById('swal-order-cancel-reply-text').value = text;
}

// Second step shown only when declining/cancelling an order: a dropdown
// of canned replies (editable in the textarea below before sending)
// that gets emailed/texted to the customer alongside the cancellation
// notice — mirrors promptCancellationReply() for appointments.
function promptOrderCancellationReply(id, name) {
    Swal.fire({
        title: 'Reply to Customer',
        html: `
            <div class="text-start mb-3">
                <label class="form-label fw-semibold small">Quick reply</label>
                <select id="swal-order-cancel-reply-select" class="form-select mb-2" onchange="applyOrderCannedReply(this)">
                    <option value="stock" selected>Item(s) out of stock</option>
                    <option value="payment">Payment issue</option>
                    <option value="customer">Cancelled as requested</option>
                    <option value="incorrect">Incorrect/incomplete order details</option>
                    <option value="unreachable">Unable to reach customer</option>
                    <option value="custom">Custom message...</option>
                </select>
                <label class="form-label fw-semibold small">Message sent to customer (optional — edit as needed)</label>
                <textarea id="swal-order-cancel-reply-text" class="form-control" rows="3">${ORDER_CANCELLATION_REPLIES.stock}</textarea>
            </div>`,
        showCancelButton: true,
        confirmButtonColor: '#b30000',
        confirmButtonText: 'Decline Order',
        cancelButtonText: 'Back',
        preConfirm: () => document.getElementById('swal-order-cancel-reply-text').value.trim()
    }).then(result => {
        if (!result.isConfirmed) return;
        executeStatusUpdate(id, 'cancelled', name, result.value || '');
    });
}

function executeStatusUpdate(id, newStatus, name, cancellationReply) {
    fetch('api/admin/manage_orders.php', { method: 'POST', credentials: 'include', headers: getAuthHeaders(),
        body: JSON.stringify({ action: 'update_status', id, status: newStatus, cancellation_reply: cancellationReply || '' })
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
// EXPORT HELPERS
// =============================================================
// Exports always use the SAME filters currently applied to the dashboard.
// This means the global search box and each tab's dropdown filter are
// respected when CSV/PDF is generated.

function getExportData(type) {
    // IMPORTANT: Export the SAME records currently shown after filtering.
    // Do not use a separate/looser filter here, otherwise CSV/PDF can contain
    // records that are hidden on the dashboard.
    const term = (globalSearchTerm || '').toLowerCase().trim();

    const getMonthFilter = (...ids) => {
        for (const id of ids) {
            const el = document.getElementById(id);
            if (el && el.value) return el.value;
        }
        return '';
    };

    const matchesMonth = (value, monthFilter) => {
        if (!monthFilter) return true;
        if (!value) return false;
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) return false;
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        return `${yyyy}-${mm}` === monthFilter;
    };

    if (type === 'appointments') {
        const statusFilter = document.getElementById('appointmentsStatusFilter')?.value || '';
        const monthFilter = getMonthFilter(
            'appointmentsMonth', 'appointmentsDateFilter', 'appointmentsMonthFilter'
        );

        return masterData.appointments.filter(a => {
            const matchesTerm = !term ||
                (a.full_name && a.full_name.toLowerCase().includes(term)) ||
                (a.service_type && a.service_type.toLowerCase().includes(term)) ||
                (a.vehicle_info && a.vehicle_info.toLowerCase().includes(term)) ||
                (a.mechanic_name && a.mechanic_name.toLowerCase().includes(term));
            const matchesStatus = !statusFilter || a.status === statusFilter;
            const matchesDate = matchesMonth(a.appointment_date, monthFilter);
            return matchesTerm && matchesStatus && matchesDate;
        });
    }

    if (type === 'users') {
        const roleFilter = document.getElementById('usersRoleFilter')?.value || '';
        const monthFilter = getMonthFilter(
            'usersMonth', 'usersDateFilter', 'usersMonthFilter'
        );

        return masterData.users.filter(u => {
            const matchesTerm = !term ||
                (u.full_name && u.full_name.toLowerCase().includes(term)) ||
                (u.email && u.email.toLowerCase().includes(term)) ||
                (u.phone && u.phone.toLowerCase().includes(term));
            const matchesRole = !roleFilter || String(u.role_id) === roleFilter;
            const matchesDate = matchesMonth(u.created_at, monthFilter);
            return matchesTerm && matchesRole && matchesDate;
        });
    }

    if (type === 'vehicles') {
        const monthFilter = getMonthFilter(
            'vehiclesMonth', 'vehiclesDateFilter', 'vehiclesMonthFilter'
        );

        return masterData.vehicles.filter(v => {
            const matchesTerm = !term ||
                (v.owner_name && v.owner_name.toLowerCase().includes(term)) ||
                (v.plate_number && v.plate_number.toLowerCase().includes(term)) ||
                (v.model && v.model.toLowerCase().includes(term)) ||
                (v.brand && v.brand.toLowerCase().includes(term));

            // Vehicles normally do not have a creation date. If a date/month
            // filter exists in the page, use the first available date field.
            const vehicleDate = v.created_at || v.date_created || v.registration_date || '';
            const matchesDate = matchesMonth(vehicleDate, monthFilter);
            return matchesTerm && matchesDate;
        });
    }

    if (type === 'history') {
        // History has its own search + month controls in the dashboard.
        const historySearch = (document.getElementById('historySearch')?.value || '')
            .toLowerCase().trim();
        const historyMonth = document.getElementById('historyMonth')?.value || '';
        const combinedTerm = historySearch || term;

        return historyData.filter(h => {
            const matchesTerm = !combinedTerm ||
                (h.full_name && h.full_name.toLowerCase().includes(combinedTerm)) ||
                (h.service_type && h.service_type.toLowerCase().includes(combinedTerm)) ||
                (h.plate_number && h.plate_number.toLowerCase().includes(combinedTerm)) ||
                (h.brand && h.brand.toLowerCase().includes(combinedTerm)) ||
                (h.model && h.model.toLowerCase().includes(combinedTerm));

            const matchesDate = !historyMonth ||
                (h.service_date && String(h.service_date).startsWith(historyMonth));

            return matchesTerm && matchesDate;
        });
    }

    if (type === 'orders') {
        const statusFilter = document.getElementById('ordersStatusFilter')?.value || '';
        const monthFilter = getMonthFilter(
            'ordersMonth', 'ordersDateFilter', 'ordersMonthFilter'
        );

        return ordersData.filter(o => {
            const year = new Date(o.created_at).getFullYear();
            const orderNo = `EJR-ORD-${year}-${String(o.id).padStart(5, '0')}`.toLowerCase();

            const matchesTerm = !term ||
                (o.customer_name && o.customer_name.toLowerCase().includes(term)) ||
                (o.contact && o.contact.toLowerCase().includes(term)) ||
                (o.address && o.address.toLowerCase().includes(term)) ||
                orderNo.includes(term);

            const matchesStatus = !statusFilter ||
                String(o.status || '').toLowerCase() === statusFilter.toLowerCase();

            const matchesDate = matchesMonth(o.created_at, monthFilter);

            return matchesTerm && matchesStatus && matchesDate;
        });
    }

    return [];
}

// Clears only the export/date filters for the selected tab.
// This function is explicitly attached to window because the PHP dashboard
// uses inline onclick="clearDateFilters('...')" handlers.
function clearDateFilters(type) {
    const prefix = String(type || '').trim();
    if (!prefix) return;

    const ids = [
        `${prefix}MonthFilter`,
        `${prefix}FromDate`,
        `${prefix}ToDate`
    ];

    let cleared = 0;

    ids.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;

        if (el.value !== '') {
            el.value = '';
            cleared++;
        }

        // Refresh any listeners attached to these controls.
        try {
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (e) {
            // The value has already been cleared; no further action needed.
        }
    });

    // Keep the normal search/status filters intact while refreshing the
    // dashboard. Export functions read the cleared controls directly.
    if (typeof applyAllFilters === 'function') {
        applyAllFilters();
    }

    // Give the user visible confirmation that the export date filters were
    // actually reset, even when the table itself does not visibly change.
    if (typeof Swal !== 'undefined' && cleared > 0) {
        Swal.fire({
            toast: true,
            position: 'bottom-end',
            icon: 'success',
            title: 'Export filters cleared',
            text: 'Month and date range filters have been reset.',
            showConfirmButton: false,
            timer: 1800,
            timerProgressBar: true
        });
    }
}

window.clearDateFilters = clearDateFilters;

function getExportConfig(type) {
    const configs = {
        appointments: {
            title: 'Service Appointments',
            csvFilename: 'appointments.csv',
            pdfFilename: 'appointments.pdf',
            headers: ['Customer', 'Service', 'Vehicle', 'Date', 'Mechanic', 'Notes', 'Status', 'Cancellation Reply']
        },
        users: {
            title: 'Users',
            csvFilename: 'users.csv',
            pdfFilename: 'users.pdf',
            headers: ['ID', 'Full Name', 'Email', 'Phone', 'Role', 'Joined']
        },
        vehicles: {
            title: 'Customer Vehicles',
            csvFilename: 'vehicles.csv',
            pdfFilename: 'vehicles.pdf',
            headers: ['Owner', 'Brand', 'Model', 'Year', 'Plate Number', 'Fuel', 'Transmission']
        },
        history: {
            title: 'Service History',
            csvFilename: 'service_history.csv',
            pdfFilename: 'service_history.pdf',
            headers: ['Customer', 'Service', 'Vehicle', 'Plate No.', 'Date']
        },
        orders: {
            title: 'Parts Orders',
            csvFilename: 'orders.csv',
            pdfFilename: 'orders.pdf',
            headers: ['Order #', 'Customer', 'Contact', 'Address', 'Items', 'Total', 'Date', 'Status', 'Cancellation Reply']
        }
    };

    return configs[type];
}

function getExportRows(type, data) {
    if (type === 'appointments') {
        return data.map(a => [
            a.full_name || 'Guest',
            a.service_type || '—',
            a.vehicle_info || '—',
            a.appointment_date
                ? new Date(a.appointment_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                : '—',
            a.mechanic_name || 'Unassigned',
            a.notes || '—',
            a.status || '—',
            a.cancellation_reply || '—'
        ]);
    }

    if (type === 'users') {
        return data.map(u => [
            u.id,
            u.full_name || '—',
            u.email || '—',
            u.phone || '—',
            u.role_id == 2 ? 'Admin' : u.role_id == 3 ? 'Mechanic' : 'Customer',
            u.created_at
                ? new Date(u.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                : '—'
        ]);
    }

    if (type === 'vehicles') {
        return data.map(v => [
            v.owner_name || '—',
            v.brand || '—',
            v.model || '—',
            v.year_model || '—',
            v.plate_number || '—',
            v.fuel_type || '—',
            v.transmission || '—'
        ]);
    }

    if (type === 'history') {
        return data.map(h => [
            h.full_name || '—',
            h.service_type || '—',
            h.brand && h.model
                ? `${h.brand} ${h.model} (${h.year_model || '—'})`
                : '—',
            h.plate_number || '—',
            h.service_date
                ? new Date(h.service_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                : '—'
        ]);
    }

    if (type === 'orders') {
        return data.map(o => {
            const year = new Date(o.created_at).getFullYear();
            const orderNo = `EJR-ORD-${year}-${String(o.id).padStart(5, '0')}`;

            let itemsList = '—';
            try {
                const itemsArr = typeof o.items === 'string' ? JSON.parse(o.items) : o.items;
                if (Array.isArray(itemsArr)) {
                    itemsList = itemsArr.map(i => `${i.name} ×${i.qty}`).join(', ');
                } else if (o.items) {
                    itemsList = String(o.items);
                }
            } catch (e) {
                itemsList = String(o.items || '—');
            }

            return [
                orderNo,
                o.customer_name || '—',
                o.contact || '—',
                o.address || '—',
                itemsList,
                `₱${parseFloat(o.total_price || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`,
                o.created_at
                    ? new Date(o.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                    : '—',
                o.status || '—',
                o.cancellation_reply || '—'
            ];
        });
    }

    return [];
}

function csvEscape(value) {
    return `"${String(value ?? '').replace(/"/g, '""')}"`;
}

// =============================================================
// EXPORT CSV
// =============================================================

function exportCSV(type) {
    const config = getExportConfig(type);
    if (!config) return;

    const data = getExportData(type);
    const rows = getExportRows(type, data);

    if (!rows.length) {
        Swal.fire({
            icon: 'info',
            title: 'No records to export',
            text: 'The current search/filter does not contain any records.'
        });
        return;
    }

    const csv = [config.headers, ...rows]
        .map(row => row.map(csvEscape).join(','))
        .join('\r\n');

    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');

    a.href = url;
    a.download = config.csvFilename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);

    Swal.fire({
        toast: true,
        position: 'bottom-end',
        icon: 'success',
        title: `Exported ${rows.length} filtered record${rows.length === 1 ? '' : 's'}`,
        showConfirmButton: false,
        timer: 2200
    });
}

// =============================================================
// EXPORT PDF
// =============================================================

function exportPDF(type) {
    const config = getExportConfig(type);
    if (!config) return;

    const data = getExportData(type);
    const rows = getExportRows(type, data);

    if (!rows.length) {
        Swal.fire({
            icon: 'info',
            title: 'No records to export',
            text: 'The current search/filter does not contain any records.'
        });
        return;
    }

    if (typeof window.jspdf === 'undefined') {
        Swal.fire('Error', 'PDF library failed to load. Check your connection and try again.', 'error');
        return;
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape' });

    doc.setFontSize(14);
    doc.setTextColor(179, 0, 0);
    doc.text('EJR Garage', 14, 15);

    doc.setFontSize(11);
    doc.setTextColor(60, 60, 60);
    doc.text(config.title, 14, 22);

    doc.setFontSize(9);
    doc.setTextColor(120, 120, 120);

    const filterText = globalSearchTerm
        ? `Filtered records: ${rows.length} | Search: "${globalSearchTerm}"`
        : `Filtered records: ${rows.length}`;

    doc.text(filterText, 14, 27);
    doc.text('Generated ' + new Date().toLocaleString('en-US'), 14, 32);

    doc.autoTable({
        head: [config.headers],
        body: rows,
        startY: 38,
        styles: { fontSize: 7.5, cellPadding: 2.5, overflow: 'linebreak' },
        headStyles: { fillColor: [179, 0, 0], textColor: [255, 255, 255] },
        alternateRowStyles: { fillColor: [248, 248, 248] },
        margin: { left: 10, right: 10 }
    });

    doc.save(config.pdfFilename);

    Swal.fire({
        toast: true,
        position: 'bottom-end',
        icon: 'success',
        title: `Exported ${rows.length} filtered record${rows.length === 1 ? '' : 's'}`,
        showConfirmButton: false,
        timer: 2200
    });
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
                            title="${isBusy ? 'Make Date Available' : 'Block Date'}"
                            aria-label="${isBusy ? 'Make Date Available' : 'Block Date'}">
                        <i class="bi bi-${isBusy ? 'calendar-check-fill' : 'calendar-x-fill'}"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="deleteBusyDate(${d.id}, '${formatted}')"
                            title="Delete Busy Date" aria-label="Delete Busy Date">
                        <i class="bi bi-calendar-minus-fill"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function busyDateForm(v = {}) {
    const today = new Date().toISOString().split('T')[0];
    const allowRange = !!v.allowRange; // only offered when adding, not editing

    const rangeToggle = allowRange ? `
            <div class="mb-3">
                <div class="btn-group w-100" role="group" aria-label="Add mode">
                    <input type="radio" class="btn-check" name="bd-mode" id="bd-mode-single" checked
                           onchange="toggleBusyDateMode(false)">
                    <label class="btn btn-outline-danger btn-sm" for="bd-mode-single">Single Date</label>

                    <input type="radio" class="btn-check" name="bd-mode" id="bd-mode-range"
                           onchange="toggleBusyDateMode(true)">
                    <label class="btn btn-outline-danger btn-sm" for="bd-mode-range">Date Range</label>
                </div>
            </div>` : '';

    const endDateField = allowRange ? `
            <div class="mb-3" id="bd-end-date-wrap" style="display:none;">
                <label class="form-label fw-semibold small">End Date</label>
                <input type="date" id="bd-end-date" class="form-control"
                       value="${v.date || ''}" min="${today}">
                <small class="text-muted">All dates from Date to End Date (inclusive) will be updated</small>
            </div>` : '';

    return `
        <div class="text-start">
            ${rangeToggle}
            <div class="mb-3">
                <label class="form-label fw-semibold small" id="bd-date-label">${allowRange ? 'Date' : 'Date'}</label>
                <input type="date" id="bd-date" class="form-control"
                       value="${v.date || ''}" min="${today}">
            </div>
            ${endDateField}
            <div class="mb-3">
                <label class="form-label fw-semibold small">Slot Limit</label>
                <input type="number" id="bd-limit" class="form-control"
                       value="${v.limit ?? 5}" min="1">
                <small class="text-muted">Max appointments allowed per day before marked busy</small>
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

function toggleBusyDateMode(isRange) {
    const wrap  = document.getElementById('bd-end-date-wrap');
    const label = document.getElementById('bd-date-label');
    if (!wrap || !label) return;
    wrap.style.display = isRange ? '' : 'none';
    label.textContent  = isRange ? 'Start Date' : 'Date';
}

function getBusyDateFormValues(action, id = null) {
    const rangeModeEl = document.getElementById('bd-mode-range');
    const isRange = !!(rangeModeEl && rangeModeEl.checked);

    const date    = document.getElementById('bd-date').value;
    const limit   = parseInt(document.getElementById('bd-limit').value);
    const is_busy = parseInt(document.getElementById('bd-busy').value);

    if (!date) { Swal.showValidationMessage(isRange ? 'Please select a start date.' : 'Please select a date.'); return false; }
    if (!limit || limit < 1) { Swal.showValidationMessage('Slot limit must be at least 1.'); return false; }

    if (isRange) {
        const endDateEl = document.getElementById('bd-end-date');
        const end_date  = endDateEl ? endDateEl.value : '';
        if (!end_date) { Swal.showValidationMessage('Please select an end date.'); return false; }
        if (end_date < date) { Swal.showValidationMessage('End date must be on or after the start date.'); return false; }
        return { action: 'block_range', start_date: date, end_date, limit, is_busy };
    }

    const payload = { action, date, limit, is_busy };
    if (id) payload.id = id;
    return payload;
}

function openBlockDate() {
    Swal.fire({
        title: '<i class="bi bi-calendar-plus me-2"></i> Add Busy Date',
        html: busyDateForm({ allowRange: true }),
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
            if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-danger text-center">Failed to load services.</td></tr>';
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
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No services match your search.</td></tr>';
        return;
    }
    tbody.innerHTML = services.map(s => {
        const hasDesc = !!(s.description && s.description.trim());
        const price = parseFloat(s.price_from) || 0;
        const detailsBadge = hasDesc
            ? '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Has details</span>'
            : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-exclamation-circle me-1"></i>No description</span>';
        const priceLine = price > 0
            ? `<div class="small text-muted mt-1">Starts at &#8369;${price.toLocaleString('en-PH')}</div>`
            : '';
        return `
        <tr>
            <td><strong>${s.name}</strong></td>
            <td><span class="badge bg-secondary">${s.category}</span></td>
            <td>${detailsBadge}${priceLine}</td>
            <td>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch"
                           style="cursor:pointer; width:2.5em; height:1.3em;"
                           ${s.is_active == 1 ? 'checked' : ''}
                           onchange="toggleService(${s.id}, this.checked ? 1 : 0)">
                </div>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-secondary me-1"
                        onclick="openEditService(${s.id})" title="Edit Details">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger"
                        onclick="deleteService(${s.id}, '${s.name.replace(/'/g,"\\'")}')" title="Remove Service">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>`;
    }).join('');
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
                <input id="svcCategory" class="form-control mb-3" placeholder="e.g. Popular Services" value="Popular Services">
                <label class="form-label fw-semibold">Starting Price (&#8369;)</label>
                <input id="svcPrice" type="number" min="0" step="0.01" class="form-control mb-3" placeholder="e.g. 850 (optional)">
                <label class="form-label fw-semibold">Description</label>
                <textarea id="svcDescription" class="form-control" rows="3" placeholder="What's included in this service? Shown to customers when they tap the info icon."></textarea>
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
                category: document.getElementById('svcCategory').value.trim() || 'General',
                price_from: parseFloat(document.getElementById('svcPrice').value) || 0,
                description: document.getElementById('svcDescription').value.trim()
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

function openEditService(id) {
    const s = servicesData.find(svc => Number(svc.id) === Number(id));
    if (!s) return;

    Swal.fire({
        title: 'Edit Service Details',
        html: `
            <div class="text-start">
                <label class="form-label fw-semibold">Service Name *</label>
                <input id="svcEditName" class="form-control mb-3" value="${(s.name || '').replace(/"/g, '&quot;')}">
                <label class="form-label fw-semibold">Category</label>
                <input id="svcEditCategory" class="form-control mb-3" value="${(s.category || '').replace(/"/g, '&quot;')}">
                <label class="form-label fw-semibold">Starting Price (&#8369;)</label>
                <input id="svcEditPrice" type="number" min="0" step="0.01" class="form-control mb-3" value="${s.price_from || 0}" placeholder="0 = not shown to customers">
                <label class="form-label fw-semibold">Description</label>
                <textarea id="svcEditDescription" class="form-control" rows="4" placeholder="What's included in this service? Shown to customers when they tap the info icon.">${s.description || ''}</textarea>
            </div>`,
        confirmButtonText: 'Save Changes',
        confirmButtonColor: '#e62429',
        showCancelButton: true,
        preConfirm: () => {
            const name = document.getElementById('svcEditName').value.trim();
            if (!name) { Swal.showValidationMessage('Service name is required.'); return false; }
            return {
                action: 'update',
                id: s.id,
                name,
                category: document.getElementById('svcEditCategory').value.trim() || 'General',
                price_from: parseFloat(document.getElementById('svcEditPrice').value) || 0,
                description: document.getElementById('svcEditDescription').value.trim(),
                is_active: s.is_active
            };
        }
    }).then(result => {
        if (result.isConfirmed) {
            fetch('api/admin/manage_services.php', { method:'POST', headers:getAuthHeaders(), body:JSON.stringify(result.value) })
                .then(r=>r.json()).then(d=>{
                    if(d.status==='success'){ Swal.fire({toast:true,position:'bottom-end',icon:'success',title:'Service updated',showConfirmButton:false,timer:2000}); loadServices(); }
                    else Swal.fire('Error', d.message || 'Could not update this service.', 'error');
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
                <button class="btn btn-sm btn-outline-primary me-1" onclick="editMechanic(${m.id})" title="Edit Mechanic" aria-label="Edit Mechanic"><i class="bi bi-person-gear"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteMechanic(${m.id}, '${m.name.replace(/'/g,"\\'")}')" title="Remove Mechanic" aria-label="Remove Mechanic"><i class="bi bi-person-dash-fill"></i></button>
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
// MECHANIC LEAVE REQUESTS
// =============================================================
// Mechanics file a leave request (api/submit_leave_request.php) which
// sits 'pending' here until an admin approves or rejects it. Approving
// immediately sets that mechanic's roster status to "On Leave"
// (on_break) — see api/admin/manage_leave_requests.php.

function loadLeaveRequests() {
    fetch('api/admin/manage_leave_requests.php', { headers: getAuthHeaders() })
        .then(r => r.json())
        .then(data => {
            leaveRequestsData = data.leave_requests || [];
            filterAndRenderLeaveRequests();
            updateLeaveRequestsBadge();
        })
        .catch(() => {
            const tbody = document.getElementById('leaveRequestsTableBody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-danger text-center">Failed to load leave requests.</td></tr>';
        });
}

function filterAndRenderLeaveRequests() {
    const term = globalSearchTerm;
    const statusFilter = document.getElementById('leaveRequestsStatusFilter')?.value ?? 'pending';

    const filtered = leaveRequestsData.filter(r => {
        const matchesTerm = !term ||
            (r.mechanic_name && r.mechanic_name.toLowerCase().includes(term)) ||
            (r.reason && r.reason.toLowerCase().includes(term));
        const matchesStatus = !statusFilter || r.status === statusFilter;
        return matchesTerm && matchesStatus;
    });
    renderLeaveRequests(filtered);
}

function leaveStatusLabel(status) {
    return { pending: 'Pending', approved: 'Approved', rejected: 'Rejected' }[status] || status;
}
function leaveStatusBadgeClass(status) {
    if (status === 'approved') return 'bg-success';
    if (status === 'rejected') return 'bg-danger';
    return 'bg-warning text-dark'; // pending
}

function formatLeaveDate(d) {
    if (!d) return '—';
    return new Date(d + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function updateLeaveRequestsBadge() {
    const badge = document.getElementById('leaveRequestsPendingBadge');
    if (!badge) return;
    const pendingCount = leaveRequestsData.filter(r => r.status === 'pending').length;
    if (pendingCount > 0) {
        badge.textContent = pendingCount + ' pending';
        badge.style.display = 'inline-block';
    } else {
        badge.style.display = 'none';
    }
}

function renderLeaveRequests(requests) {
    const tbody = document.getElementById('leaveRequestsTableBody');
    if (!tbody) return;
    if (!requests.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No leave requests found.</td></tr>';
        return;
    }
    tbody.innerHTML = requests.map(r => `
        <tr>
            <td><strong>${r.mechanic_name}</strong></td>
            <td>${formatLeaveDate(r.start_date)} &ndash; ${formatLeaveDate(r.end_date)}</td>
            <td>${r.reason ? r.reason : '<span class="text-muted">No reason given</span>'}</td>
            <td><span class="badge ${leaveStatusBadgeClass(r.status)}">${leaveStatusLabel(r.status)}</span></td>
            <td>${formatLeaveDate(r.requested_at ? r.requested_at.split(' ')[0] : '')}</td>
            <td>
                ${r.status === 'pending' ? `
                    <button class="btn btn-sm btn-success me-1" onclick="approveLeaveRequest(${r.id}, '${r.mechanic_name.replace(/'/g,"\\'")}')" title="Approve Leave Request"><i class="bi bi-check-circle-fill"></i></button>
                    <button class="btn btn-sm btn-outline-danger me-1" onclick="rejectLeaveRequest(${r.id}, '${r.mechanic_name.replace(/'/g,"\\'")}')" title="Decline Leave Request"><i class="bi bi-x-circle-fill"></i></button>
                ` : ''}
                <button class="btn btn-sm btn-outline-secondary" onclick="deleteLeaveRequest(${r.id}, '${r.mechanic_name.replace(/'/g,"\\'")}')" title="Delete Leave Request"><i class="bi bi-trash3-fill"></i></button>
            </td>
        </tr>`).join('');
}

function approveLeaveRequest(id, name) {
    Swal.fire({
        title: 'Approve Leave Request?',
        html: `${name} will be marked <b>On Leave</b> right away. You can set them back to Available later from the mechanic roster.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: 'Approve'
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('api/admin/manage_leave_requests.php', { method:'POST', headers:getAuthHeaders(), body:JSON.stringify({ action:'approve', id }) })
            .then(r=>r.json()).then(d=>{
                if (d.status === 'success') {
                    Swal.fire({ toast:true, position:'bottom-end', icon:'success', title:d.message||'Approved', showConfirmButton:false, timer:2000 });
                    loadLeaveRequests();
                    loadMechanics();
                } else {
                    Swal.fire('Error', d.message, 'error');
                }
            });
    });
}

function rejectLeaveRequest(id, name) {
    Swal.fire({
        title: 'Decline Leave Request?',
        html: `<div class="text-start">
                <p>Decline ${name}'s leave request?</p>
                <label class="form-label fw-semibold">Note to mechanic (optional)</label>
                <textarea id="leaveRejectNotes" class="form-control" rows="2" placeholder="e.g. Short-staffed that week"></textarea>
               </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e62429',
        confirmButtonText: 'Decline',
        preConfirm: () => document.getElementById('leaveRejectNotes').value.trim()
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('api/admin/manage_leave_requests.php', { method:'POST', headers:getAuthHeaders(), body:JSON.stringify({ action:'reject', id, admin_notes: result.value }) })
            .then(r=>r.json()).then(d=>{
                if (d.status === 'success') {
                    Swal.fire({ toast:true, position:'bottom-end', icon:'success', title:d.message||'Declined', showConfirmButton:false, timer:2000 });
                    loadLeaveRequests();
                } else {
                    Swal.fire('Error', d.message, 'error');
                }
            });
    });
}

function deleteLeaveRequest(id, name) {
    Swal.fire({
        title: 'Delete Leave Request?',
        text: `Remove ${name}'s leave request from the list?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e62429',
        confirmButtonText: 'Delete'
    }).then(result => {
        if (result.isConfirmed) {
            fetch('api/admin/manage_leave_requests.php', { method:'POST', headers:getAuthHeaders(), body:JSON.stringify({ action:'delete', id }) })
                .then(r=>r.json()).then(d=>{ if(d.status==='success') loadLeaveRequests(); else Swal.fire('Error', d.message, 'error'); });
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