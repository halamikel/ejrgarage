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

        const chatTabActive =
            document
                .querySelector("#tab-live-chat")
                ?.classList
                .contains("show");

        if (chatTabActive) {

            loadAdminChatUsers();

            if (currentChatUserId) {
                loadAdminMessages(currentChatUserId);
            }

        }

    }, 8000);


    const btnAddBusyDate =
        document.getElementById('btnAddBusyDate');

    if (btnAddBusyDate) {
        btnAddBusyDate.addEventListener(
            'click',
            openBlockDate
        );
    }

    startRealtime();

});


// =============================================================
// CORE DATA LOADERS
// =============================================================

function loadFullSiteData() {

    fetch(
        'api/admin/get_full_site_data.php?t=' + Date.now(),
        {
            credentials: 'include',
            headers: getAuthHeaders()
        }
    )

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

    .catch(err =>
        console.error(
            "Fetch failed:",
            err
        )
    );

}


function loadServiceHistory() {

    fetch(
        'api/admin/get_service_history.php',
        {
            credentials: 'include',
            headers: getAuthHeaders()
        }
    )

    .then(res => res.json())

    .then(data => {

        if (data.status === 'success') {

            historyData = data.history;

            document.getElementById(
                'badgeHistory'
            ).textContent =
                historyData.length;

            filterAndRenderHistory();

        }

    })

    .catch(() => {

        document.getElementById(
            'historyTableBody'
        ).innerHTML =
            '<tr><td colspan="6" class="text-center text-danger py-4">Failed to load history.</td></tr>';

    });

}


function loadParts() {

    fetch(
        'api/admin/manage_parts.php',
        {
            credentials: 'include',
            headers: getAuthHeaders()
        }
    )

    .then(res => res.json())

    .then(data => {

        if (data.status !== 'success') {
            return;
        }

        partsData = data.parts;

        document.getElementById(
            'badgeParts'
        ).textContent =
            partsData.length;

        populatePartsCategoryFilter();

        filterAndRenderParts();

    });

}


function populatePartsCategoryFilter() {

    const sel =
        document.getElementById(
            'partsCategoryFilter'
        );

    if (!sel) {
        return;
    }

    const current =
        sel.value;

    const categories =
        [
            ...new Set(
                partsData
                    .map(p => p.category)
                    .filter(Boolean)
            )
        ].sort();

    sel.innerHTML =
        '<option value="">All Categories</option>' +
        categories
            .map(
                c =>
                    `<option value="${c}">${c}</option>`
            )
            .join('');

    if (categories.includes(current)) {
        sel.value = current;
    }

}


function filterAndRenderParts() {

    const term =
        globalSearchTerm;

    const category =
        document
            .getElementById(
                'partsCategoryFilter'
            )
            ?.value || '';

    const stock =
        document
            .getElementById(
                'partsStockFilter'
            )
            ?.value || '';


    const filtered =
        partsData.filter(p => {

            const matchesTerm =
                !term ||

                (p.name &&
                    p.name
                        .toLowerCase()
                        .includes(term)) ||

                (p.category &&
                    p.category
                        .toLowerCase()
                        .includes(term));


            const matchesCategory =
                !category ||
                p.category === category;


            const matchesStock =
                !stock ||

                (stock === 'in' &&
                    p.stock > 0) ||

                (stock === 'out' &&
                    p.stock <= 0);


            return (
                matchesTerm &&
                matchesCategory &&
                matchesStock
            );

        });


    renderParts(filtered);

}


function renderParts(parts) {

    const tbody =
        document.getElementById(
            'partsTableBody'
        );

    if (!tbody) {
        return;
    }


    if (!parts.length) {

        tbody.innerHTML =
            '<tr><td colspan="6" class="text-center text-muted py-4">No parts match your search.</td></tr>';

        return;
    }


    tbody.innerHTML =
        parts.map(p => `

        <tr>

            <td>

                <img
                    src="${p.image_url || ''}"
                    style="
                        width:50px;
                        height:40px;
                        object-fit:cover;
                        border-radius:6px;
                    "
                    onerror="this.style.display='none'"
                >

            </td>

            <td class="fw-bold">
                ${p.name}
            </td>

            <td>
                <span class="badge bg-light text-dark border">
                    ${p.category}
                </span>
            </td>

            <td>
                ₱${parseFloat(p.price)
                    .toLocaleString(
                        'en-PH',
                        {
                            minimumFractionDigits: 2
                        }
                    )}
            </td>

            <td>
                <span
                    class="badge ${
                        p.stock > 0
                            ? 'bg-success'
                            : 'bg-danger'
                    }"
                >
                    ${p.stock}
                </span>
            </td>

            <td>

                <div class="btn-group">

                    <button
                        class="btn btn-sm btn-outline-dark"
                        onclick="openEditPart(
                            ${p.id},
                            '${p.name.replace(/'/g,"\\'")}',
                            '${(p.description||'').replace(/'/g,"\\'")}',
                            ${p.price},
                            '${p.category}',
                            '${p.image_url||''}',
                            ${p.stock}
                        )"
                    >
                        <i class="bi bi-pencil-square"></i>
                    </button>


                    <button
                        class="btn btn-sm btn-outline-danger"
                        onclick="deletePart(
                            ${p.id},
                            '${p.name.replace(/'/g,"\\'")}'
                        )"
                    >
                        <i class="bi bi-trash"></i>
                    </button>

                </div>

            </td>

        </tr>

    `).join('');

}


function loadInquiries() {

    fetch(
        'api/admin/manage_parts.php?action=get_inquiries',
        {
            credentials: 'include',
            headers: getAuthHeaders()
        }
    )

    .then(res => res.json())

    .then(data => {

        if (data.status !== 'success') {
            return;
        }

        inquiriesData =
            data.inquiries;

        const pendingCount =
            inquiriesData.filter(
                i => i.status !== 'responded'
            ).length;

        document.getElementById(
            'badgeInquiries'
        ).textContent =
            pendingCount;

        filterAndRenderInquiries();

    });

}


function filterAndRenderInquiries() {

    const term =
        globalSearchTerm;

    const statusFilter =
        document
            .getElementById(
                'inquiriesStatusFilter'
            )
            ?.value || 'pending';


    const filtered =
        inquiriesData.filter(i => {

            const matchesTerm =
                !term ||

                (
                    i.customer_name &&
                    i.customer_name
                        .toLowerCase()
                        .includes(term)
                ) ||

                (
                    i.part_name &&
                    i.part_name
                        .toLowerCase()
                        .includes(term)
                ) ||

                (
                    i.contact &&
                    i.contact
                        .toLowerCase()
                        .includes(term)
                );


            const matchesStatus =
                statusFilter === 'all' ||

                (
                    statusFilter === 'pending' &&
                    i.status !== 'responded'
                ) ||

                (
                    statusFilter === 'responded' &&
                    i.status === 'responded'
                );


            return (
                matchesTerm &&
                matchesStatus
            );

        });


    renderInquiries(filtered);

}


function renderInquiries(inquiries) {

    const tbody =
        document.getElementById(
            'inquiriesTableBody'
        );

    if (!tbody) {
        return;
    }


    tbody.innerHTML =
        inquiries.length

            ? inquiries.map(i => `

        <tr>

            <td>
                <span class="badge bg-light text-dark border">
                    ${i.part_name}
                </span>
            </td>

            <td class="fw-bold">
                ${i.customer_name}
            </td>

            <td>
                ${i.contact}
            </td>

            <td class="text-muted small">
                ${i.message || '—'}
            </td>

            <td>
                ${new Date(
                    i.created_at
                ).toLocaleDateString(
                    'en-US',
                    {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    }
                )}
            </td>

            <td>

                <span
                    class="badge ${
                        i.status === 'responded'
                            ? 'bg-success'
                            : 'bg-warning text-dark'
                    }"
                >
                    ${
                        i.status === 'responded'
                            ? 'responded'
                            : 'pending'
                    }
                </span>

            </td>

            <td>

                <div class="btn-group">

                    <button
                        class="btn btn-sm btn-outline-success"
                        onclick="updateInquiry(${i.id})"
                    >
                        <i class="bi bi-check-lg"></i>
                    </button>

                    <button
                        class="btn btn-sm btn-outline-danger"
                        onclick="deleteInquiry(${i.id})"
                    >
                        <i class="bi bi-trash"></i>
                    </button>

                </div>

            </td>

        </tr>

    `).join('')

    :

    '<tr><td colspan="7" class="text-center text-muted py-4">No inquiries match your search.</td></tr>';

}


function loadOrders() {

    const tbody =
        document.getElementById(
            'ordersTableBody'
        );

    if (!tbody) {
        return;
    }


    fetch(
        'api/admin/manage_orders.php',
        {
            credentials: 'include',
            headers: getAuthHeaders()
        }
    )

    .then(res => res.json())

    .then(data => {

        if (data.status !== 'success') {
            throw new Error(
                data.message
            );
        }

        ordersData =
            data.orders ?? [];

        document.getElementById(
            'badgeOrders'
        ).textContent =
            ordersData.length;

        filterAndRenderOrders();

    })

    .catch(err => {

        console.error(
            'Orders error:',
            err
        );

        tbody.innerHTML =
            '<tr><td colspan="8" class="text-center text-danger py-4">Failed to load orders.</td></tr>';

    });

}


function filterAndRenderOrders() {

    const term =
        globalSearchTerm;

    const statusFilter =
        document
            .getElementById(
                'ordersStatusFilter'
            )
            ?.value || '';


    const filtered =
        ordersData.filter(o => {

            const year =
                new Date(
                    o.created_at
                ).getFullYear();

            const orderNo =
                `EJR-ORD-${year}-${String(
                    o.id
                ).padStart(5, '0')}`
                .toLowerCase();


            const matchesTerm =
                !term ||

                (
                    o.customer_name &&
                    o.customer_name
                        .toLowerCase()
                        .includes(term)
                ) ||

                (
                    o.contact &&
                    o.contact
                        .toLowerCase()
                        .includes(term)
                ) ||

                orderNo.includes(term);


            const matchesStatus =
                !statusFilter ||
                o.status
                    .toLowerCase() ===
                    statusFilter
                        .toLowerCase();


            return (
                matchesTerm &&
                matchesStatus
            );

        });


    renderOrders(filtered);

}


function renderOrders(orders) {

    const tbody =
        document.getElementById(
            'ordersTableBody'
        );

    if (!tbody) {
        return;
    }


    if (!orders.length) {

        tbody.innerHTML =
            '<tr><td colspan="8" class="text-center text-muted py-4">No orders match your search.</td></tr>';

        return;
    }


    tbody.innerHTML =
        orders.map(o => {

            const year =
                new Date(
                    o.created_at
                ).getFullYear();


            const orderNo =
                `EJR-ORD-${year}-${String(
                    o.id
                ).padStart(5, '0')}`;


            const itemsArr =
                typeof o.items === 'string'
                    ? JSON.parse(o.items)
                    : o.items;


            const itemsList =
                Array.isArray(itemsArr)

                    ? itemsArr
                        .map(
                            i =>
                                `${i.name} ×${i.qty}`
                        )
                        .join(', ')

                    : o.items;


            const date =
                new Date(
                    o.created_at
                ).toLocaleDateString(
                    'en-US',
                    {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    }
                );


            const safeName =
                o.customer_name
                    .replace(
                        /'/g,
                        "\\'"
                    );


            const isPending =
                o.status
                    .toLowerCase() ===
                'pending';


            return `

        <tr>

            <td>
                <code style="color:#b30000;">
                    ${orderNo}
                </code>
            </td>

            <td class="fw-bold">
                ${o.customer_name}
            </td>

            <td>
                ${o.contact}
            </td>

            <td
                class="text-muted small"
                style="
                    max-width:180px;
                    white-space:nowrap;
                    overflow:hidden;
                    text-overflow:ellipsis;
                "
                title="${itemsList}"
            >
                ${itemsList}
            </td>

            <td class="fw-bold text-danger">
                ₱${parseFloat(
                    o.total_price
                ).toLocaleString(
                    'en-PH',
                    {
                        minimumFractionDigits: 2
                    }
                )}
            </td>

            <td>
                ${date}
            </td>

            <td>
                ${statusBadge(o.status)}
            </td>

            <td>

                <div class="btn-group">

                    ${
                        isPending
                            ? `
                        <button
                            class="btn btn-sm btn-outline-success"
                            onclick="updateOrder(
                                ${o.id},
                                '${o.status}',
                                '${safeName}'
                            )"
                            title="Manage"
                        >
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        `
                            : ''
                    }


                    <button
                        class="btn btn-sm btn-outline-secondary"
                        onclick="deleteOrder(${o.id})"
                        title="Delete"
                    >
                        <i class="bi bi-trash"></i>
                    </button>


                    <a
                        href="api/admin/generate_parts_receipt.php?id=${o.id}"
                        target="_blank"
                        class="btn btn-sm btn-outline-primary"
                        title="View Invoice"
                    >
                        <i class="bi bi-receipt"></i>
                    </a>

                </div>

            </td>

        </tr>

        `;

        }).join('');

}


// =============================================================
// RENDER FUNCTIONS
// =============================================================

function updateStats(data) {

    const active =
        data.appointments;

    document.getElementById(
        "statTotal"
    ).textContent =
        active.length;

    document.getElementById(
        "statPending"
    ).textContent =
        active.filter(
            a => a.status === 'Pending'
        ).length;

    document.getElementById(
        "statUsers"
    ).textContent =
        data.users.length;

    document.getElementById(
        "statVehicles"
    ).textContent =
        data.vehicles.length;

    document.getElementById(
        "badgeAppt"
    ).textContent =
        active.length;

    document.getElementById(
        "badgeUsers"
    ).textContent =
        data.users.length;

    document.getElementById(
        "badgeVehs"
    ).textContent =
        data.vehicles.length;

}


function statusBadge(status) {

    if (!status) {
        return `
            <span class="badge bg-secondary">
                Unknown
            </span>
        `;
    }

    const s =
        status
            .toLowerCase()
            .trim();


    const map = {

        'pending':
            'bg-warning text-dark',

        'approved':
            'bg-success',

        'confirmed':
            'bg-success',

        'in progress':
            'bg-warning text-dark',

        'completed':
            'bg-success',

        'cancelled':
            'bg-danger'

    };


    const displayStatus =
        s.charAt(0).toUpperCase() +
        s.slice(1);


    return `
        <span class="badge ${map[s] || 'bg-secondary'}">
            ${displayStatus}
        </span>
    `;

}


function filterAndRenderAppointments() {

    const term =
        globalSearchTerm;

    const statusFilter =
        document
            .getElementById(
                'appointmentsStatusFilter'
            )
            ?.value || '';


    const filtered =
        masterData.appointments.filter(a => {

            const searchable = [

                a.full_name,

                a.service_type,

                a.vehicle_info,

                a.mechanic_name,

                a.appointment_date,

                a.status,

                a.notes,

                a.cancellation_response,

                a.cancellation_reason

            ]

            .filter(Boolean)

            .join(' ')

            .toLowerCase();


            const matchesTerm =
                !term ||
                searchable.includes(term);


            const matchesStatus =
                !statusFilter ||
                a.status === statusFilter;


            return (
                matchesTerm &&
                matchesStatus
            );

        });


    renderAppointments(
        filtered
    );

}


function filterAndRenderUsers() {

    const term =
        globalSearchTerm;

    const roleFilter =
        document
            .getElementById(
                'usersRoleFilter'
            )
            ?.value || '';


    const filtered =
        masterData.users.filter(u => {

            const matchesTerm =
                !term ||

                (
                    u.full_name &&
                    u.full_name
                        .toLowerCase()
                        .includes(term)
                ) ||

                (
                    u.email &&
                    u.email
                        .toLowerCase()
                        .includes(term)
                ) ||

                (
                    u.phone &&
                    u.phone
                        .toLowerCase()
                        .includes(term)
                );


            const matchesRole =
                !roleFilter ||
                String(u.role_id) ===
                    roleFilter;


            return (
                matchesTerm &&
                matchesRole
            );

        });


    renderUsers(
        filtered
    );

}


function filterAndRenderVehicles() {

    const term =
        globalSearchTerm;


    const filtered =
        masterData.vehicles.filter(v =>

            !term ||

            (
                v.owner_name &&
                v.owner_name
                    .toLowerCase()
                    .includes(term)
            ) ||

            (
                v.plate_number &&
                v.plate_number
                    .toLowerCase()
                    .includes(term)
            ) ||

            (
                v.model &&
                v.model
                    .toLowerCase()
                    .includes(term)
            ) ||

            (
                v.brand &&
                v.brand
                    .toLowerCase()
                    .includes(term)
            )

        );


    renderVehicles(
        filtered
    );

}


function filterAndRenderHistory() {

    const term =
        globalSearchTerm;


    const filtered =
        historyData.filter(h =>

            !term ||

            (
                h.full_name &&
                h.full_name
                    .toLowerCase()
                    .includes(term)
            ) ||

            (
                h.service_type &&
                h.service_type
                    .toLowerCase()
                    .includes(term)
            ) ||

            (
                h.plate_number &&
                h.plate_number
                    .toLowerCase()
                    .includes(term)
            ) ||

            (
                h.brand &&
                h.brand
                    .toLowerCase()
                    .includes(term)
            ) ||

            (
                h.model &&
                h.model
                    .toLowerCase()
                    .includes(term)
            )

        );


    renderHistory(
        filtered
    );

}


// Re-applies globalSearchTerm + every tab's own dropdown filter.
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

    if (
        typeof applyBusyFilter ===
        'function'
    ) {
        applyBusyFilter();
    }

}


function renderAppointments(apps) {

    const tbody =
        document.getElementById(
            "appointmentTableBody"
        );

    if (!tbody) {
        return;
    }


    if (!apps.length) {

        tbody.innerHTML =
            '<tr><td colspan="8" class="text-center text-muted py-4">No appointments found.</td></tr>';

        return;
    }


    tbody.innerHTML =
        apps.map(a => {

            const currentStatus =
                appointmentStatusKey(
                    a.status
                );


            const isLocked =
                currentStatus ===
                    'Completed' ||

                currentStatus ===
                    'Cancelled';


            const notes =
                a.notes || '';


            const cancellationResponse =
                a.cancellation_response ||
                a.cancellation_reason ||
                '';


            const notesHtml = [

                notes
                    ? `<div>${escapeHtml(notes)}</div>`
                    : '',

                cancellationResponse
                    ? `
                        <div class="small text-danger mt-1">
                            <strong>
                                Cancellation:
                            </strong>

                            ${escapeHtml(
                                cancellationResponse
                            )}
                        </div>
                    `
                    : ''

            ].join('');


            return `

            <tr>

                <td class="fw-bold">
                    ${escapeHtml(
                        a.full_name ||
                        'Guest'
                    )}
                </td>


                <td>

                    <span
                        class="badge bg-light text-dark border"
                    >
                        ${escapeHtml(
                            a.service_type ||
                            '—'
                        )}
                    </span>

                </td>


                <td>
                    ${escapeHtml(
                        a.vehicle_info ||
                        '—'
                    )}
                </td>


                <td>

                    ${
                        a.appointment_date

                            ? new Date(
                                a.appointment_date
                            ).toLocaleDateString(
                                'en-US',
                                {
                                    month:
                                        'short',
                                    day:
                                        'numeric',
                                    year:
                                        'numeric'
                                }
                            )

                            : '—'
                    }

                </td>


                <td>

                    ${
                        a.mechanic_name

                            ? `
                                <span
                                    class="badge bg-info text-dark"
                                >
                                    <i
                                        class="bi bi-person-gear me-1"
                                    ></i>

                                    ${escapeHtml(
                                        a.mechanic_name
                                    )}
                                </span>
                            `

                            : `
                                <span
                                    class="text-muted small"
                                >
                                    Unassigned
                                </span>
                            `
                    }

                </td>


                <td>

                    ${
                        notesHtml ||
                        '<span class="text-muted small">—</span>'
                    }

                </td>


                <td>

                    ${statusBadge(
                        currentStatus
                    )}

                    ${
                        isLocked

                            ? `
                                <div
                                    class="small text-muted mt-1"
                                >
                                    <i
                                        class="bi bi-lock-fill me-1"
                                    ></i>

                                    Locked
                                </div>
                            `

                            : ''
                    }

                </td>


                <td>

                    <div class="btn-group">

                        <button
                            class="
                                btn
                                btn-sm
                                ${
                                    isLocked
                                        ? 'btn-outline-secondary'
                                        : 'btn-outline-dark'
                                }
                            "
                            onclick="manageApp(
                                ${Number(a.id)},
                                '${String(
                                    currentStatus
                                ).replace(
                                    /'/g,
                                    "\\'"
                                )}'
                            )"

                            ${
                                isLocked
                                    ? 'title="This appointment is locked"'
                                    : ''
                            }
                        >

                            <i
                                class="
                                    bi
                                    ${
                                        isLocked
                                            ? 'bi-lock-fill'
                                            : 'bi-pencil-square'
                                    }
                                "
                            ></i>

                            ${
                                isLocked
                                    ? 'Locked'
                                    : 'Manage'
                            }

                        </button>


                        ${
                            !isLocked

                                ? `
                                    <button
                                        class="
                                            btn
                                            btn-sm
                                            btn-outline-primary
                                        "
                                        onclick="assignMechanic(
                                            ${Number(a.id)}
                                        )"
                                        title="Assign Mechanic"
                                    >

                                        <i
                                            class="
                                                bi
                                                bi-person-gear
                                            "
                                        ></i>

                                    </button>
                                `

                                : ''
                        }

                    </div>

                </td>

            </tr>

            `;

        }).join('');

}


function renderUsers(users) {

    if (!users.length) {

        document.getElementById(
            "userTableBody"
        ).innerHTML =
            '<tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>';

        return;
    }


    document.getElementById(
        "userTableBody"
    ).innerHTML =

        users.map(u => {

            const name =
                u.full_name.replace(
                    /'/g,
                    "\\'"
                );

            const email =
                u.email.replace(
                    /'/g,
                    "\\'"
                );


            return `

        <tr>

            <td>
                ${u.id}
            </td>

            <td class="fw-bold">
                ${u.full_name}
            </td>

            <td>
                ${u.email}
            </td>

            <td>
                ${u.phone || '—'}
            </td>

            <td>

                <span
                    class="
                        badge
                        ${
                            u.role_id == 2
                                ? 'bg-danger'
                                : u.role_id == 3
                                    ? 'bg-info text-dark'
                                    : 'bg-secondary'
                        }
                    "
                >

                    ${
                        u.role_id == 2
                            ? 'Admin'
                            : u.role_id == 3
                                ? 'Mechanic'
                                : 'Customer'
                    }

                </span>

            </td>

            <td>

                ${
                    u.created_at

                        ? new Date(
                            u.created_at
                        ).toLocaleDateString(
                            'en-US',
                            {
                                month:
                                    'short',
                                day:
                                    'numeric',
                                year:
                                    'numeric'
                            }
                        )

                        : '—'
                }

            </td>

            <td>

                <div class="btn-group">

                    <button
                        class="
                            btn
                            btn-sm
                            btn-outline-dark
                        "
                        onclick="editUser(
                            ${u.id}
                        )"
                    >
                        <i
                            class="
                                bi
                                bi-pencil-square
                            "
                        ></i>
                    </button>


                    <button
                        class="
                            btn
                            btn-sm
                            btn-outline-danger
                        "
                        onclick="deleteUser(
                            ${u.id},
                            '${name}'
                        )"
                    >
                        <i
                            class="
                                bi
                                bi-trash
                            "
                        ></i>
                    </button>

                </div>

            </td>

        </tr>

        `;

        }).join('');

}


function renderVehicles(vehs) {

    document.getElementById(
        "vehicleTableBody"
    ).innerHTML =

        vehs.length

            ? vehs.map(v => {

                const brand =
                    (v.brand || '')
                        .replace(
                            /'/g,
                            "\\'"
                        );

                const model =
                    (v.model || '')
                        .replace(
                            /'/g,
                            "\\'"
                        );

                const plate =
                    (v.plate_number || '')
                        .replace(
                            /'/g,
                            "\\'"
                        );

                const fuel =
                    (v.fuel_type || '')
                        .replace(
                            /'/g,
                            "\\'"
                        );

                const trans =
                    (v.transmission || '')
                        .replace(
                            /'/g,
                            "\\'"
                        );


                return `

            <tr>

                <td class="fw-bold">
                    ${v.owner_name || '—'}
                </td>

                <td>
                    ${v.brand || '—'}
                </td>

                <td>
                    ${v.model || '—'}
                </td>

                <td>
                    ${v.year_model || '—'}
                </td>

                <td>

                    <code
                        class="
                            text-uppercase
                        "
                        style="color:#b30000;"
                    >
                        ${v.plate_number}
                    </code>

                </td>

                <td>
                    ${v.fuel_type || '—'}
                </td>

                <td>
                    ${v.transmission || '—'}
                </td>

                <td>

                    <div class="btn-group">

                        <button
                            class="
                                btn
                                btn-sm
                                btn-outline-dark
                            "
                            onclick="editVehicle(
                                ${v.id},
                                ${v.user_id},
                                '${brand}',
                                '${model}',
                                '${v.year_model || ''}',
                                '${plate}',
                                '${fuel}',
                                '${trans}'
                            )"
                            title="Edit"
                        >

                            <i
                                class="
                                    bi
                                    bi-pencil-square
                                "
                            ></i>

                        </button>


                        <button
                            class="
                                btn
                                btn-sm
                                btn-outline-danger
                            "
                            onclick="deleteVehicle(
                                ${v.id},
                                '${plate}'
                            )"
                            title="Delete"
                        >

                            <i
                                class="
                                    bi
                                    bi-trash
                                "
                            ></i>

                        </button>

                    </div>

                </td>

            </tr>

            `;

            }).join('')

            :

            '<tr><td colspan="8" class="text-center text-muted py-4">No vehicles found.</td></tr>';

}


function renderHistory(data) {

    const tbody =
        document.getElementById(
            'historyTableBody'
        );


    document.getElementById(
        'historyCount'
    ).textContent =

        data.length
            ? `Showing ${data.length} record(s)`
            : '';


    if (!data.length) {

        tbody.innerHTML =
            '<tr><td colspan="6" class="text-center text-muted py-4">No service history found.</td></tr>';

        return;
    }


    tbody.innerHTML =

        data.map(h => {

            const vehicle =
                h.brand &&
                h.model

                    ? `${h.brand} ${h.model} (${h.year_model || '—'})`

                    : '—';


            const date =
                h.service_date

                    ? new Date(
                        h.service_date
                    ).toLocaleDateString(
                        'en-US',
                        {
                            month:
                                'short',
                            day:
                                'numeric',
                            year:
                                'numeric'
                        }
                    )

                    : '—';


            const rawDate =
                h.service_date
                    ? h.service_date.substring(
                        0,
                        10
                    )
                    : '';


            const service =
                (h.service_type || '')
                    .replace(
                        /'/g,
                        "\\'"
                    );


            const custName =
                (h.full_name || '')
                    .replace(
                        /'/g,
                        "\\'"
                    );


            return `

        <tr>

            <td class="fw-bold">
                ${h.full_name || '—'}
            </td>

            <td>

                <span
                    class="
                        badge
                        bg-light
                        text-dark
                        border
                    "
                >
                    ${h.service_type || '—'}
                </span>

            </td>

            <td>
                ${vehicle}
            </td>

            <td>

                <code
                    class="
                        text-uppercase
                    "
                    style="color:#b30000;"
                >
                    ${h.plate_number || '—'}
                </code>

            </td>

            <td>
                ${date}
            </td>

            <td>

                <div class="btn-group">

                    <a
                        href="api/admin/generate_receipt.php?id=${h.id}"
                        target="_blank"
                        class="
                            btn
                            btn-sm
                            btn-outline-primary
                        "
                        title="View Invoice"
                    >
                        <i
                            class="
                                bi
                                bi-receipt
                            "
                        ></i>
                    </a>


                    <button
                        class="
                            btn
                            btn-sm
                            btn-outline-success
                        "
                        onclick="sendReceiptToUser(
                            ${h.id},
                            '${custName}'
                        )"
                        title="Send Receipt"
                    >
                        <i
                            class="
                                bi
                                bi-send
                            "
                        ></i>
                    </button>


                    <button
                        class="
                            btn
                            btn-sm
                            btn-outline-dark
                        "
                        onclick="editHistory(
                            ${h.id},
                            '${service}',
                            '${rawDate}'
                        )"
                    >
                        <i
                            class="
                                bi
                                bi-pencil-square
                            "
                        ></i>
                    </button>


                    <button
                        class="
                            btn
                            btn-sm
                            btn-outline-danger
                        "
                        onclick="deleteHistory(
                            ${h.id},
                            '${custName}'
                        )"
                    >
                        <i
                            class="
                                bi
                                bi-trash
                            "
                        ></i>
                    </button>

                </div>

            </td>

        </tr>

        `;

        }).join('');

}


// =============================================================
// APPOINTMENTS
// =============================================================

/*
 * Appointment status flow:
 *
 * Pending
 *   ├── Confirmed
 *   └── Cancelled (locked)
 *
 * Confirmed
 *   ├── In Progress
 *   └── Cancelled (locked)
 *
 * In Progress
 *   ├── Completed (locked)
 *   └── Cancelled (locked)
 *
 * Completed and Cancelled are terminal states and cannot be
 * changed back to another status from the admin dashboard.
 */

const APPOINTMENT_STATUS_FLOW = {

    'Pending': [
        'Confirmed',
        'Cancelled'
    ],

    'Confirmed': [
        'In Progress',
        'Cancelled'
    ],

    'In Progress': [
        'Completed',
        'Cancelled'
    ],

    'Completed': [],

    'Cancelled': []

};


const CANCELLATION_RESPONSES = [

    'The selected date is fully booked.',

    'No mechanic is available on the selected date.',

    'The requested service is unavailable on the selected date.',

    'The requested service requires a different schedule.',

    'Please contact EJR Garage so we can arrange another schedule.',

    'Customer requested cancellation.',

    'Other / Custom Reply'

];


function escapeHtml(value) {

    return String(
        value ?? ''
    )

    .replace(
        /&/g,
        '&amp;'
    )

    .replace(
        /</g,
        '&lt;'
    )

    .replace(
        />/g,
        '&gt;'
    )

    .replace(
        /"/g,
        '&quot;'
    )

    .replace(
        /'/g,
        '&#039;'
    );

}


function appointmentStatusKey(status) {

    return String(
        status || ''
    ).trim();

}


function manageApp(id, currentStatus) {

    const status =
        appointmentStatusKey(
            currentStatus
        );

    const allowed =
        APPOINTMENT_STATUS_FLOW[
            status
        ] || [];


    // Completed and Cancelled are intentionally locked.

    if (allowed.length === 0) {

        Swal.fire({

            icon:
                status === 'Completed'
                    ? 'success'
                    : 'info',

            title:
                `Appointment ${status}`,

            text:
                `A ${status.toLowerCase()} appointment cannot be changed or reverted.`,

            confirmButtonColor:
                '#b30000'

        });

        return;
    }


    Swal.fire({

        title:
            'Update Appointment Status',

        html: `

            <div class="text-start">

                <div class="mb-3">

                    <label
                        class="form-label fw-semibold"
                    >
                        Current Status
                    </label>

                    <input
                        class="form-control"
                        value="${escapeHtml(status)}"
                        disabled
                    >

                </div>


                <div>

                    <label
                        class="form-label fw-semibold"
                    >
                        Next Status
                    </label>

                    <select
                        id="appointmentNextStatus"
                        class="form-select"
                    >

                        ${allowed.map(
                            nextStatus =>
                                `
                                <option
                                    value="${escapeHtml(
                                        nextStatus
                                    )}"
                                >
                                    ${escapeHtml(
                                        nextStatus
                                    )}
                                </option>
                                `
                        ).join('')}

                    </select>

                </div>

            </div>

        `,

        showCancelButton:
            true,

        confirmButtonColor:
            '#b30000',

        confirmButtonText:
            'Continue',


        preConfirm: () => {

            const nextStatus =
                document
                    .getElementById(
                        'appointmentNextStatus'
                    )
                    ?.value || '';


            if (
                !nextStatus ||
                !allowed.includes(
                    nextStatus
                )
            ) {

                Swal.showValidationMessage(
                    'Please select a valid next status.'
                );

                return false;
            }


            return nextStatus;

        }

    })

    .then(result => {

        if (
            !result.isConfirmed ||
            !result.value
        ) {
            return;
        }


        const nextStatus =
            result.value;


        // Cancellation requires an explicit response/reason.

        if (
            nextStatus ===
            'Cancelled'
        ) {

            openCancellationResponse(
                id,
                status
            );

            return;
        }


        postData(
            'api/admin/update_appointment.php',
            {
                appointment_id:
                    id,

                status:
                    nextStatus
            }
        );

    });

}


function openCancellationResponse(
    id,
    currentStatus
) {

    Swal.fire({

        title:
            'Cancel Appointment',

        html: `

            <div class="text-start">

                <p class="text-muted mb-3">
                    Choose the response that will be recorded for the customer.
                </p>


                <label
                    class="form-label fw-semibold"
                >
                    Cancellation Response
                </label>


                <select
                    id="cancellationResponse"
                    class="form-select mb-3"
                >

                    <option value="">
                        Select a response...
                    </option>

                    ${CANCELLATION_RESPONSES.map(
                        response =>
                            `
                            <option
                                value="${escapeHtml(
                                    response
                                )}"
                            >
                                ${escapeHtml(
                                    response
                                )}
                            </option>
                            `
                    ).join('')}

                </select>


                <div
                    id="customCancellationWrap"
                    style="display:none;"
                >

                    <label
                        class="form-label fw-semibold"
                    >
                        Custom Response
                    </label>


                    <textarea
                        id="customCancellationResponse"
                        class="form-control"
                        rows="3"
                        maxlength="500"
                        placeholder="Enter the cancellation response..."
                    ></textarea>

                </div>


                <div class="mt-3">

                    <label
                        class="form-label fw-semibold"
                    >
                        Additional Notes

                        <span class="text-muted">
                            (optional)
                        </span>

                    </label>


                    <textarea
                        id="appointmentNotes"
                        class="form-control"
                        rows="3"
                        maxlength="1000"
                        placeholder="Add any internal appointment notes..."
                    ></textarea>

                </div>

            </div>

        `,

        icon:
            'warning',

        showCancelButton:
            true,

        confirmButtonColor:
            '#b30000',

        confirmButtonText:
            'Cancel Appointment',


        didOpen: () => {

            const responseSelect =
                document.getElementById(
                    'cancellationResponse'
                );

            const customWrap =
                document.getElementById(
                    'customCancellationWrap'
                );


            if (
                responseSelect &&
                customWrap
            ) {

                responseSelect.addEventListener(
                    'change',
                    () => {

                        customWrap.style.display =
                            responseSelect.value ===
                            'Other / Custom Reply'

                                ? 'block'
                                : 'none';

                    }
                );

            }

        },


        preConfirm: () => {

            const selected =
                document
                    .getElementById(
                        'cancellationResponse'
                    )
                    ?.value || '';


            const custom =
                document
                    .getElementById(
                        'customCancellationResponse'
                    )
                    ?.value
                    .trim() || '';


            const notes =
                document
                    .getElementById(
                        'appointmentNotes'
                    )
                    ?.value
                    .trim() || '';


            if (!selected) {

                Swal.showValidationMessage(
                    'Please select a cancellation response.'
                );

                return false;
            }


            if (
                selected ===
                    'Other / Custom Reply' &&
                !custom
            ) {

                Swal.showValidationMessage(
                    'Please enter the custom cancellation response.'
                );

                return false;
            }


            return {

                response:
                    selected ===
                        'Other / Custom Reply'

                        ? custom
                        : selected,

                notes:
                    notes

            };

        }

    })

    .then(result => {

        if (
            !result.isConfirmed ||
            !result.value
        ) {
            return;
        }


        postData(
            'api/admin/update_appointment.php',
            {

                appointment_id:
                    id,

                status:
                    'Cancelled',

                cancellation_response:
                    result.value.response,

                notes:
                    result.value.notes

            }
        );

    });

}


// =============================================================
// USERS
// =============================================================

function filterAndRenderUsers() {

    const term =
        globalSearchTerm;

    const roleFilter =
        document
            .getElementById(
                'usersRoleFilter'
            )
            ?.value || '';


    const filtered =
        masterData.users.filter(u => {

            const matchesTerm =
                !term ||

                (
                    u.full_name &&
                    u.full_name
                        .toLowerCase()
                        .includes(term)
                ) ||

                (
                    u.email &&
                    u.email
                        .toLowerCase()
                        .includes(term)
                ) ||

                (
                    u.phone &&
                    u.phone
                        .toLowerCase()
                        .includes(term)
                );


            const matchesRole =
                !roleFilter ||
                String(u.role_id) ===
                    roleFilter;


            return (
                matchesTerm &&
                matchesRole
            );

        });


    renderUsers(filtered);

}


function renderUsers(users) {

    const tbody =
        document.getElementById(
            "userTableBody"
        );

    if (!tbody) {
        return;
    }


    if (!users.length) {

        tbody.innerHTML =
            `
            <tr>
                <td
                    colspan="7"
                    class="text-center text-muted py-4"
                >
                    No users found.
                </td>
            </tr>
            `;

        return;
    }


    tbody.innerHTML =
        users.map(u => {

            const name =
                String(
                    u.full_name || ''
                ).replace(
                    /'/g,
                    "\\'"
                );


            return `

                <tr>

                    <td>
                        ${escapeHtml(u.id)}
                    </td>


                    <td class="fw-bold">
                        ${escapeHtml(
                            u.full_name ||
                            '—'
                        )}
                    </td>


                    <td>
                        ${escapeHtml(
                            u.email ||
                            '—'
                        )}
                    </td>


                    <td>
                        ${escapeHtml(
                            u.phone ||
                            '—'
                        )}
                    </td>


                    <td>

                        <span
                            class="
                                badge
                                ${
                                    u.role_id == 2
                                        ? 'bg-danger'
                                        : u.role_id == 3
                                            ? 'bg-info text-dark'
                                            : 'bg-secondary'
                                }
                            "
                        >

                            ${
                                u.role_id == 2
                                    ? 'Admin'
                                    : u.role_id == 3
                                        ? 'Mechanic'
                                        : 'Customer'
                            }

                        </span>

                    </td>


                    <td>

                        ${
                            u.created_at

                                ? new Date(
                                    u.created_at
                                ).toLocaleDateString(
                                    'en-US',
                                    {
                                        month:
                                            'short',
                                        day:
                                            'numeric',
                                        year:
                                            'numeric'
                                    }
                                )

                                : '—'
                        }

                    </td>


                    <td>

                        <div class="btn-group">

                            <button
                                class="
                                    btn
                                    btn-sm
                                    btn-outline-dark
                                "
                                onclick="editUser(
                                    ${Number(u.id)}
                                )"
                                title="Edit User"
                            >

                                <i
                                    class="
                                        bi
                                        bi-pencil-square
                                    "
                                ></i>

                            </button>


                            <button
                                class="
                                    btn
                                    btn-sm
                                    btn-outline-danger
                                "
                                onclick="deleteUser(
                                    ${Number(u.id)},
                                    '${name}'
                                )"
                                title="Delete User"
                            >

                                <i
                                    class="
                                        bi
                                        bi-trash
                                    "
                                ></i>

                            </button>

                        </div>

                    </td>

                </tr>

            `;

        }).join('');

}


// =============================================================
// VEHICLES
// =============================================================

function filterAndRenderVehicles() {

    const term =
        globalSearchTerm;


    const filtered =
        masterData.vehicles.filter(v => {

            const searchable = [

                v.owner_name,

                v.plate_number,

                v.model,

                v.brand,

                v.year_model,

                v.fuel_type,

                v.transmission

            ]

            .filter(Boolean)

            .join(' ')

            .toLowerCase();


            return (
                !term ||
                searchable.includes(term)
            );

        });


    renderVehicles(filtered);

}


function renderVehicles(vehs) {

    const tbody =
        document.getElementById(
            "vehicleTableBody"
        );

    if (!tbody) {
        return;
    }


    if (!vehs.length) {

        tbody.innerHTML =
            `
            <tr>
                <td
                    colspan="8"
                    class="text-center text-muted py-4"
                >
                    No vehicles found.
                </td>
            </tr>
            `;

        return;
    }


    tbody.innerHTML =
        vehs.map(v => {

            const brand =
                String(
                    v.brand || ''
                ).replace(
                    /'/g,
                    "\\'"
                );


            const model =
                String(
                    v.model || ''
                ).replace(
                    /'/g,
                    "\\'"
                );


            const plate =
                String(
                    v.plate_number || ''
                ).replace(
                    /'/g,
                    "\\'"
                );


            const fuel =
                String(
                    v.fuel_type || ''
                ).replace(
                    /'/g,
                    "\\'"
                );


            const trans =
                String(
                    v.transmission || ''
                ).replace(
                    /'/g,
                    "\\'"
                );


            return `

                <tr>

                    <td class="fw-bold">
                        ${escapeHtml(
                            v.owner_name ||
                            '—'
                        )}
                    </td>


                    <td>
                        ${escapeHtml(
                            v.brand ||
                            '—'
                        )}
                    </td>


                    <td>
                        ${escapeHtml(
                            v.model ||
                            '—'
                        )}
                    </td>


                    <td>
                        ${escapeHtml(
                            v.year_model ||
                            '—'
                        )}
                    </td>


                    <td>

                        <code
                            class="text-uppercase"
                            style="color:#b30000;"
                        >
                            ${escapeHtml(
                                v.plate_number ||
                                '—'
                            )}
                        </code>

                    </td>


                    <td>
                        ${escapeHtml(
                            v.fuel_type ||
                            '—'
                        )}
                    </td>


                    <td>
                        ${escapeHtml(
                            v.transmission ||
                            '—'
                        )}
                    </td>


                    <td>

                        <div class="btn-group">

                            <button
                                class="
                                    btn
                                    btn-sm
                                    btn-outline-dark
                                "
                                onclick="editVehicle(
                                    ${Number(v.id)},
                                    ${Number(v.user_id)},
                                    '${brand}',
                                    '${model}',
                                    '${String(
                                        v.year_model ||
                                        ''
                                    ).replace(
                                        /'/g,
                                        "\\'"
                                    )}',
                                    '${plate}',
                                    '${fuel}',
                                    '${trans}'
                                )"
                                title="Edit Vehicle"
                            >

                                <i
                                    class="
                                        bi
                                        bi-pencil-square
                                    "
                                ></i>

                            </button>


                            <button
                                class="
                                    btn
                                    btn-sm
                                    btn-outline-danger
                                "
                                onclick="deleteVehicle(
                                    ${Number(v.id)},
                                    '${plate}'
                                )"
                                title="Delete Vehicle"
                            >

                                <i
                                    class="
                                        bi
                                        bi-trash
                                    "
                                ></i>

                            </button>

                        </div>

                    </td>

                </tr>

            `;

        }).join('');

}


// =============================================================
// SERVICE HISTORY
// =============================================================

function filterAndRenderHistory() {

    const term =
        globalSearchTerm;


    const filtered =
        historyData.filter(h => {

            const searchable = [

                h.full_name,

                h.service_type,

                h.plate_number,

                h.brand,

                h.model,

                h.year_model,

                h.service_date

            ]

            .filter(Boolean)

            .join(' ')

            .toLowerCase();


            return (
                !term ||
                searchable.includes(term)
            );

        });


    renderHistory(filtered);

}


function renderHistory(data) {

    const tbody =
        document.getElementById(
            'historyTableBody'
        );

    if (!tbody) {
        return;
    }


    const count =
        document.getElementById(
            'historyCount'
        );


    if (count) {

        count.textContent =
            data.length
                ? `Showing ${data.length} record(s)`
                : '';

    }


    if (!data.length) {

        tbody.innerHTML =
            `
            <tr>
                <td
                    colspan="6"
                    class="text-center text-muted py-4"
                >
                    No service history found.
                </td>
            </tr>
            `;

        return;
    }


    tbody.innerHTML =
        data.map(h => {

            const vehicle =
                h.brand &&
                h.model

                    ? `${h.brand} ${h.model} (${h.year_model || '—'})`

                    : '—';


            const date =
                h.service_date

                    ? new Date(
                        h.service_date
                    ).toLocaleDateString(
                        'en-US',
                        {
                            month:
                                'short',
                            day:
                                'numeric',
                            year:
                                'numeric'
                        }
                    )

                    : '—';


            const rawDate =
                h.service_date
                    ? String(
                        h.service_date
                    ).substring(
                        0,
                        10
                    )
                    : '';


            const service =
                String(
                    h.service_type ||
                    ''
                ).replace(
                    /'/g,
                    "\\'"
                );


            const custName =
                String(
                    h.full_name ||
                    ''
                ).replace(
                    /'/g,
                    "\\'"
                );


            return `

                <tr>

                    <td class="fw-bold">
                        ${escapeHtml(
                            h.full_name ||
                            '—'
                        )}
                    </td>


                    <td>

                        <span
                            class="
                                badge
                                bg-light
                                text-dark
                                border
                            "
                        >
                            ${escapeHtml(
                                h.service_type ||
                                '—'
                            )}
                        </span>

                    </td>


                    <td>
                        ${escapeHtml(
                            vehicle
                        )}
                    </td>


                    <td>

                        <code
                            class="text-uppercase"
                            style="color:#b30000;"
                        >
                            ${escapeHtml(
                                h.plate_number ||
                                '—'
                            )}
                        </code>

                    </td>


                    <td>
                        ${date}
                    </td>


                    <td>

                        <div class="btn-group">

                            <a
                                href="api/admin/generate_receipt.php?id=${Number(h.id)}"
                                target="_blank"
                                class="
                                    btn
                                    btn-sm
                                    btn-outline-primary
                                "
                                title="View Invoice"
                            >

                                <i
                                    class="
                                        bi
                                        bi-receipt
                                    "
                                ></i>

                            </a>


                            <button
                                class="
                                    btn
                                    btn-sm
                                    btn-outline-success
                                "
                                onclick="sendReceiptToUser(
                                    ${Number(h.id)},
                                    '${custName}'
                                )"
                                title="Send Receipt"
                            >

                                <i
                                    class="
                                        bi
                                        bi-send
                                    "
                                ></i>

                            </button>


                            <button
                                class="
                                    btn
                                    btn-sm
                                    btn-outline-dark
                                "
                                onclick="editHistory(
                                    ${Number(h.id)},
                                    '${service}',
                                    '${rawDate}'
                                )"
                                title="Edit History"
                            >

                                <i
                                    class="
                                        bi
                                        bi-pencil-square
                                    "
                                ></i>

                            </button>


                            <button
                                class="
                                    btn
                                    btn-sm
                                    btn-outline-danger
                                "
                                onclick="deleteHistory(
                                    ${Number(h.id)},
                                    '${custName}'
                                )"
                                title="Delete History"
                            >

                                <i
                                    class="
                                        bi
                                        bi-trash
                                    ></i>

                            </button>

                        </div>

                    </td>

                </tr>

            `;

        }).join('');

}


// =============================================================
// APPOINTMENT FILTERS
// =============================================================

function applyAppointmentFilter() {

    filterAndRenderAppointments();

}


// =============================================================
// USER FILTER
// =============================================================

function applyUserFilter() {

    filterAndRenderUsers();

}


// =============================================================
// PARTS FILTER
// =============================================================

function applyPartsFilter() {

    filterAndRenderParts();

}


// =============================================================
// INQUIRIES FILTER
// =============================================================

function applyInquiryFilter() {

    filterAndRenderInquiries();

}


// =============================================================
// ORDERS FILTER
// =============================================================

function applyOrderFilter() {

    filterAndRenderOrders();

}


// =============================================================
// GLOBAL FILTER
// =============================================================

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

    if (
        typeof applyBusyFilter ===
        'function'
    ) {

        applyBusyFilter();

    }

}


// =============================================================
// APPOINTMENT STATUS HELPERS
// =============================================================

function getAllowedAppointmentStatuses(
    currentStatus
) {

    const status =
        appointmentStatusKey(
            currentStatus
        );


    return (
        APPOINTMENT_STATUS_FLOW[
            status
        ] || []
    );

}


function isAppointmentLocked(
    status
) {

    const normalized =
        appointmentStatusKey(
            status
        );


    return (
        normalized ===
            'Completed' ||

        normalized ===
            'Cancelled'
    );

}


// =============================================================
// APPOINTMENT DISPLAY HELPERS
// =============================================================

function getAppointmentNotes(
    appointment
) {

    const notes =
        appointment.notes ||
        '';


    const cancellation =
        appointment.cancellation_response ||
        appointment.cancellation_reason ||
        '';


    let html = '';


    if (notes) {

        html +=
            `
            <div>
                ${escapeHtml(notes)}
            </div>
            `;

    }


    if (cancellation) {

        html +=
            `
            <div
                class="
                    small
                    text-danger
                    mt-1
                "
            >

                <strong>
                    Cancellation:
                </strong>

                ${escapeHtml(
                    cancellation
                )}

            </div>
            `;

    }


    return html ||
        '<span class="text-muted small">—</span>';

}


// =============================================================
// APPOINTMENT MANAGEMENT
// =============================================================

function openAppointmentStatusModal(
    id,
    currentStatus
) {

    manageApp(
        id,
        currentStatus
    );

}


// =============================================================
// REFRESH DATA
// =============================================================

function refreshAppointments() {

    loadFullSiteData();

}


function refreshUsers() {

    loadFullSiteData();

}


function refreshVehicles() {

    loadFullSiteData();

}


function refreshHistory() {

    loadServiceHistory();

}


// =============================================================
// END PART 2
// =============================================================

// =============================================================
// PARTS MANAGEMENT
// =============================================================

function loadParts() {

    fetch(
        'api/admin/manage_parts.php',
        {
            credentials: 'include',
            headers: getAuthHeaders()
        }
    )

    .then(res => res.json())

    .then(data => {

        if (data.status !== 'success') {

            console.error(
                'Failed to load parts:',
                data.message
            );

            return;
        }


        partsData =
            Array.isArray(data.parts)
                ? data.parts
                : [];


        const badge =
            document.getElementById(
                'badgeParts'
            );


        if (badge) {

            badge.textContent =
                partsData.length;

        }


        populatePartsCategoryFilter();

        filterAndRenderParts();

    })

    .catch(error => {

        console.error(
            'Parts loading error:',
            error
        );

    });

}


// -------------------------------------------------------------
// Populate Parts Category Filter
// -------------------------------------------------------------

function populatePartsCategoryFilter() {

    const select =
        document.getElementById(
            'partsCategoryFilter'
        );


    if (!select) {
        return;
    }


    const currentValue =
        select.value;


    const categories =
        [
            ...new Set(

                partsData

                    .map(
                        part =>
                            part.category
                    )

                    .filter(Boolean)

            )
        ]

        .sort(
            (a, b) =>
                String(a)
                    .localeCompare(
                        String(b)
                    )
        );


    select.innerHTML =
        `
        <option value="">
            All Categories
        </option>
        `;


    categories.forEach(
        category => {

            const option =
                document.createElement(
                    'option'
                );


            option.value =
                category;


            option.textContent =
                category;


            select.appendChild(
                option
            );

        }
    );


    if (
        categories.includes(
            currentValue
        )
    ) {

        select.value =
            currentValue;

    }

}


// -------------------------------------------------------------
// Filter Parts
// -------------------------------------------------------------

function filterAndRenderParts() {

    const term =
        globalSearchTerm;


    const category =
        document
            .getElementById(
                'partsCategoryFilter'
            )
            ?.value || '';


    const stock =
        document
            .getElementById(
                'partsStockFilter'
            )
            ?.value || '';


    const filtered =
        partsData.filter(
            part => {

                const searchable = [

                    part.name,

                    part.category,

                    part.description,

                    part.stock

                ]

                .filter(Boolean)

                .join(' ')

                .toLowerCase();


                const matchesSearch =
                    !term ||
                    searchable.includes(
                        term
                    );


                const matchesCategory =
                    !category ||
                    String(
                        part.category || ''
                    ) ===
                    String(category);


                let matchesStock =
                    true;


                if (
                    stock === 'in'
                ) {

                    matchesStock =
                        Number(
                            part.stock
                        ) > 0;

                }


                if (
                    stock === 'out'
                ) {

                    matchesStock =
                        Number(
                            part.stock
                        ) <= 0;

                }


                if (
                    stock === 'low'
                ) {

                    matchesStock =
                        Number(
                            part.stock
                        ) > 0 &&
                        Number(
                            part.stock
                        ) <= 5;

                }


                return (
                    matchesSearch &&
                    matchesCategory &&
                    matchesStock
                );

            }
        );


    renderParts(
        filtered
    );

}


// -------------------------------------------------------------
// Render Parts
// -------------------------------------------------------------

function renderParts(parts) {

    const tbody =
        document.getElementById(
            'partsTableBody'
        );


    if (!tbody) {
        return;
    }


    if (!parts.length) {

        tbody.innerHTML =
            `
            <tr>

                <td
                    colspan="6"
                    class="
                        text-center
                        text-muted
                        py-4
                    "
                >

                    <i
                        class="
                            bi
                            bi-box-seam
                            fs-3
                            d-block
                            mb-2
                        "
                    ></i>

                    No parts match your search.

                </td>

            </tr>
            `;

        return;
    }


    tbody.innerHTML =
        parts.map(
            part => {

                const id =
                    Number(
                        part.id
                    );


                const name =
                    escapeHtml(
                        part.name ||
                        'Unnamed Part'
                    );


                const category =
                    escapeHtml(
                        part.category ||
                        'Uncategorized'
                    );


                const description =
                    escapeHtml(
                        part.description ||
                        ''
                    );


                const price =
                    Number(
                        part.price || 0
                    );


                const stock =
                    Number(
                        part.stock || 0
                    );


                const image =
                    part.image_url ||
                    '';


                let stockClass =
                    'bg-success';


                if (
                    stock <= 0
                ) {

                    stockClass =
                        'bg-danger';

                }
                else if (
                    stock <= 5
                ) {

                    stockClass =
                        'bg-warning text-dark';

                }


                return `

                    <tr>

                        <td>

                            ${
                                image

                                    ? `
                                        <img
                                            src="${escapeHtml(
                                                image
                                            )}"
                                            alt="${name}"
                                            style="
                                                width:50px;
                                                height:40px;
                                                object-fit:cover;
                                                border-radius:6px;
                                            "
                                            onerror="
                                                this.style.display='none'
                                            "
                                        >
                                    `

                                    : `
                                        <div
                                            class="
                                                bg-light
                                                border
                                                rounded
                                                d-flex
                                                align-items-center
                                                justify-content-center
                                            "
                                            style="
                                                width:50px;
                                                height:40px;
                                            "
                                        >

                                            <i
                                                class="
                                                    bi
                                                    bi-box-seam
                                                    text-muted
                                                "
                                            ></i>

                                        </div>
                                    `
                            }

                        </td>


                        <td class="fw-bold">

                            ${name}

                        </td>


                        <td>

                            <span
                                class="
                                    badge
                                    bg-light
                                    text-dark
                                    border
                                "
                            >

                                ${category}

                            </span>

                        </td>


                        <td>

                            ₱${price.toLocaleString(
                                'en-PH',
                                {
                                    minimumFractionDigits:
                                        2
                                }
                            )}

                        </td>


                        <td>

                            <span
                                class="
                                    badge
                                    ${stockClass}
                                "
                            >

                                ${stock}

                            </span>

                        </td>


                        <td>

                            <div
                                class="btn-group"
                            >

                                <button
                                    class="
                                        btn
                                        btn-sm
                                        btn-outline-dark
                                    "
                                    onclick="
                                        openEditPart(
                                            ${id},
                                            '${String(
                                                part.name ||
                                                ''
                                            ).replace(
                                                /'/g,
                                                "\\'"
                                            )}',
                                            '${String(
                                                part.description ||
                                                ''
                                            ).replace(
                                                /'/g,
                                                "\\'"
                                            )}',
                                            ${price},
                                            '${String(
                                                part.category ||
                                                ''
                                            ).replace(
                                                /'/g,
                                                "\\'"
                                            )}',
                                            '${String(
                                                part.image_url ||
                                                ''
                                            ).replace(
                                                /'/g,
                                                "\\'"
                                            )}',
                                            ${stock}
                                        )
                                    "
                                    title="Edit Part"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-pencil-square
                                        "
                                    ></i>

                                </button>


                                <button
                                    class="
                                        btn
                                        btn-sm
                                        btn-outline-danger
                                    "
                                    onclick="
                                        deletePart(
                                            ${id},
                                            '${String(
                                                part.name ||
                                                ''
                                            ).replace(
                                                /'/g,
                                                "\\'"
                                            )}'
                                        )
                                    "
                                    title="Delete Part"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-trash
                                        "
                                    ></i>

                                </button>

                            </div>

                        </td>

                    </tr>

                `;

            }
        ).join('');

}


// =============================================================
// INQUIRIES
// =============================================================

function loadInquiries() {

    fetch(
        'api/admin/manage_parts.php?action=get_inquiries',
        {
            credentials: 'include',
            headers: getAuthHeaders()
        }
    )

    .then(res => res.json())

    .then(data => {

        if (
            data.status !==
            'success'
        ) {

            console.error(
                'Failed to load inquiries:',
                data.message
            );

            return;
        }


        inquiriesData =
            Array.isArray(
                data.inquiries
            )
                ? data.inquiries
                : [];


        const pendingCount =
            inquiriesData.filter(
                inquiry =>
                    inquiry.status !==
                    'responded'
            ).length;


        const badge =
            document.getElementById(
                'badgeInquiries'
            );


        if (badge) {

            badge.textContent =
                pendingCount;

        }


        filterAndRenderInquiries();

    })

    .catch(error => {

        console.error(
            'Inquiry loading error:',
            error
        );

    });

}


// -------------------------------------------------------------
// Inquiry Filter
// -------------------------------------------------------------

function filterAndRenderInquiries() {

    const term =
        globalSearchTerm;


    const statusFilter =
        document
            .getElementById(
                'inquiriesStatusFilter'
            )
            ?.value || 'pending';


    const filtered =
        inquiriesData.filter(
            inquiry => {

                const searchable = [

                    inquiry.customer_name,

                    inquiry.part_name,

                    inquiry.contact,

                    inquiry.email,

                    inquiry.message,

                    inquiry.status

                ]

                .filter(Boolean)

                .join(' ')

                .toLowerCase();


                const matchesSearch =
                    !term ||
                    searchable.includes(
                        term
                    );


                let matchesStatus =
                    true;


                if (
                    statusFilter ===
                    'pending'
                ) {

                    matchesStatus =
                        inquiry.status !==
                        'responded';

                }
                else if (
                    statusFilter ===
                    'responded'
                ) {

                    matchesStatus =
                        inquiry.status ===
                        'responded';

                }


                return (
                    matchesSearch &&
                    matchesStatus
                );

            }
        );


    renderInquiries(
        filtered
    );

}


// -------------------------------------------------------------
// Render Inquiries
// -------------------------------------------------------------

function renderInquiries(
    inquiries
) {

    const tbody =
        document.getElementById(
            'inquiriesTableBody'
        );


    if (!tbody) {
        return;
    }


    if (!inquiries.length) {

        tbody.innerHTML =
            `
            <tr>

                <td
                    colspan="7"
                    class="
                        text-center
                        text-muted
                        py-4
                    "
                >

                    <i
                        class="
                            bi
                            bi-inbox
                            fs-3
                            d-block
                            mb-2
                        "
                    ></i>

                    No inquiries match your search.

                </td>

            </tr>
            `;

        return;
    }


    tbody.innerHTML =
        inquiries.map(
            inquiry => {

                const id =
                    Number(
                        inquiry.id
                    );


                const status =
                    String(
                        inquiry.status ||
                        'pending'
                    ).toLowerCase();


                const isResponded =
                    status ===
                    'responded';


                const date =
                    inquiry.created_at

                        ? new Date(
                            inquiry.created_at
                        ).toLocaleDateString(
                            'en-US',
                            {
                                month:
                                    'short',
                                day:
                                    'numeric',
                                year:
                                    'numeric'
                            }
                        )

                        : '—';


                return `

                    <tr>

                        <td>

                            <span
                                class="
                                    badge
                                    bg-light
                                    text-dark
                                    border
                                "
                            >

                                ${escapeHtml(
                                    inquiry.part_name ||
                                    '—'
                                )}

                            </span>

                        </td>


                        <td class="fw-bold">

                            ${escapeHtml(
                                inquiry.customer_name ||
                                '—'
                            )}

                        </td>


                        <td>

                            ${escapeHtml(
                                inquiry.contact ||
                                inquiry.email ||
                                '—'
                            )}

                        </td>


                        <td
                            class="
                                text-muted
                                small
                            "
                            style="
                                max-width:220px;
                            "
                        >

                            <div
                                style="
                                    white-space:nowrap;
                                    overflow:hidden;
                                    text-overflow:ellipsis;
                                "
                                title="${escapeHtml(
                                    inquiry.message ||
                                    ''
                                )}"
                            >

                                ${escapeHtml(
                                    inquiry.message ||
                                    '—'
                                )}

                            </div>

                        </td>


                        <td>

                            ${date}

                        </td>


                        <td>

                            <span
                                class="
                                    badge
                                    ${
                                        isResponded
                                            ? 'bg-success'
                                            : 'bg-warning text-dark'
                                    }
                                "
                            >

                                ${
                                    isResponded
                                        ? 'Responded'
                                        : 'Pending'
                                }

                            </span>

                        </td>


                        <td>

                            <div
                                class="btn-group"
                            >

                                ${
                                    !isResponded

                                        ? `
                                            <button
                                                class="
                                                    btn
                                                    btn-sm
                                                    btn-outline-success
                                                "
                                                onclick="
                                                    updateInquiry(
                                                        ${id}
                                                    )
                                                "
                                                title="Mark as Responded"
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-check-lg
                                                    "
                                                ></i>

                                            </button>
                                        `

                                        : ''
                                }


                                <button
                                    class="
                                        btn
                                        btn-sm
                                        btn-outline-danger
                                    "
                                    onclick="
                                        deleteInquiry(
                                            ${id}
                                        )
                                    "
                                    title="Delete Inquiry"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-trash
                                        "
                                    ></i>

                                </button>

                            </div>

                        </td>

                    </tr>

                `;

            }
        ).join('');

}


// =============================================================
// INQUIRY ACTIONS
// =============================================================

function updateInquiry(
    id
) {

    Swal.fire({

        title:
            'Mark Inquiry as Responded?',

        text:
            'This inquiry will be marked as responded.',

        icon:
            'question',

        showCancelButton:
            true,

        confirmButtonColor:
            '#198754',

        cancelButtonColor:
            '#6c757d',

        confirmButtonText:
            'Yes, Mark Responded'

    })

    .then(result => {

        if (
            !result.isConfirmed
        ) {

            return;

        }


        postData(
            'api/admin/manage_parts.php',
            {

                action:
                    'update_inquiry',

                id:
                    id,

                status:
                    'responded'

            },

            () => {

                loadInquiries();

            }
        );

    });

}


function deleteInquiry(
    id
) {

    Swal.fire({

        title:
            'Delete Inquiry?',

        text:
            'This action cannot be undone.',

        icon:
            'warning',

        showCancelButton:
            true,

        confirmButtonColor:
            '#dc3545',

        cancelButtonColor:
            '#6c757d',

        confirmButtonText:
            'Delete'

    })

    .then(result => {

        if (
            !result.isConfirmed
        ) {

            return;

        }


        postData(
            'api/admin/manage_parts.php',
            {

                action:
                    'delete_inquiry',

                id:
                    id

            },

            () => {

                loadInquiries();

            }
        );

    });

}


// =============================================================
// PARTS ACTIONS
// =============================================================

function openEditPart(
    id,
    name,
    description,
    price,
    category,
    imageUrl,
    stock
) {

    Swal.fire({

        title:
            'Edit Part',

        width:
            650,

        html: `

            <div
                class="text-start"
            >

                <div
                    class="mb-3"
                >

                    <label
                        class="form-label fw-semibold"
                    >
                        Part Name
                    </label>

                    <input
                        id="editPartName"
                        class="form-control"
                        value="${escapeHtml(name)}"
                    >

                </div>


                <div
                    class="mb-3"
                >

                    <label
                        class="form-label fw-semibold"
                    >
                        Description
                    </label>

                    <textarea
                        id="editPartDescription"
                        class="form-control"
                        rows="3"
                    >${escapeHtml(
                        description
                    )}</textarea>

                </div>


                <div
                    class="row"
                >

                    <div
                        class="col-md-6 mb-3"
                    >

                        <label
                            class="form-label fw-semibold"
                        >
                            Price
                        </label>

                        <input
                            id="editPartPrice"
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-control"
                            value="${Number(
                                price || 0
                            )}"
                        >

                    </div>


                    <div
                        class="col-md-6 mb-3"
                    >

                        <label
                            class="form-label fw-semibold"
                        >
                            Stock
                        </label>

                        <input
                            id="editPartStock"
                            type="number"
                            min="0"
                            class="form-control"
                            value="${Number(
                                stock || 0
                            )}"
                        >

                    </div>

                </div>


                <div
                    class="mb-3"
                >

                    <label
                        class="form-label fw-semibold"
                    >
                        Category
                    </label>

                    <input
                        id="editPartCategory"
                        class="form-control"
                        value="${escapeHtml(
                            category
                        )}"
                    >

                </div>


                <div
                    class="mb-3"
                >

                    <label
                        class="form-label fw-semibold"
                    >
                        Image URL
                    </label>

                    <input
                        id="editPartImage"
                        class="form-control"
                        value="${escapeHtml(
                            imageUrl
                        )}"
                    >

                </div>

            </div>

        `,

        showCancelButton:
            true,

        confirmButtonColor:
            '#b30000',

        confirmButtonText:
            'Save Changes',

        preConfirm: () => {

            const newName =
                document
                    .getElementById(
                        'editPartName'
                    )
                    ?.value
                    .trim();


            const newDescription =
                document
                    .getElementById(
                        'editPartDescription'
                    )
                    ?.value
                    .trim();


            const newPrice =
                document
                    .getElementById(
                        'editPartPrice'
                    )
                    ?.value;


            const newStock =
                document
                    .getElementById(
                        'editPartStock'
                    )
                    ?.value;


            const newCategory =
                document
                    .getElementById(
                        'editPartCategory'
                    )
                    ?.value
                    .trim();


            const newImage =
                document
                    .getElementById(
                        'editPartImage'
                    )
                    ?.value
                    .trim();


            if (!newName) {

                Swal.showValidationMessage(
                    'Part name is required.'
                );

                return false;

            }


            if (
                newPrice === '' ||
                Number(newPrice) < 0
            ) {

                Swal.showValidationMessage(
                    'Please enter a valid price.'
                );

                return false;

            }


            if (
                newStock === '' ||
                Number(newStock) < 0
            ) {

                Swal.showValidationMessage(
                    'Please enter a valid stock quantity.'
                );

                return false;

            }


            return {

                id:
                    Number(id),

                name:
                    newName,

                description:
                    newDescription,

                price:
                    Number(newPrice),

                stock:
                    Number(newStock),

                category:
                    newCategory,

                image_url:
                    newImage

            };

        }

    })

    .then(result => {

        if (
            !result.isConfirmed ||
            !result.value
        ) {

            return;

        }


        postData(
            'api/admin/manage_parts.php',
            {

                action:
                    'update',

                ...result.value

            },

            () => {

                loadParts();

            }
        );

    });

}


function deletePart(
    id,
    name
) {

    Swal.fire({

        title:
            'Delete Part?',

        html:
            `
            Are you sure you want to delete
            <strong>
                ${escapeHtml(name)}
            </strong>?
            `,

        icon:
            'warning',

        showCancelButton:
            true,

        confirmButtonColor:
            '#dc3545',

        cancelButtonColor:
            '#6c757d',

        confirmButtonText:
            'Delete'

    })

    .then(result => {

        if (
            !result.isConfirmed
        ) {

            return;

        }


        postData(
            'api/admin/manage_parts.php',
            {

                action:
                    'delete',

                id:
                    Number(id)

            },

            () => {

                loadParts();

            }
        );

    });

}


// =============================================================
// PART 3 END
// =============================================================

// =============================================================
// ORDERS
// =============================================================

function loadOrders() {

    const tbody =
        document.getElementById(
            'ordersTableBody'
        );

    if (!tbody) {
        return;
    }


    fetch(
        'api/admin/manage_orders.php',
        {
            credentials: 'include',
            headers: getAuthHeaders()
        }
    )

    .then(res => res.json())

    .then(data => {

        if (
            data.status !==
            'success'
        ) {

            throw new Error(
                data.message ||
                'Failed to load orders.'
            );

        }


        ordersData =
            Array.isArray(
                data.orders
            )
                ? data.orders
                : [];


        const badge =
            document.getElementById(
                'badgeOrders'
            );


        if (badge) {

            badge.textContent =
                ordersData.length;

        }


        filterAndRenderOrders();

    })

    .catch(error => {

        console.error(
            'Orders error:',
            error
        );


        tbody.innerHTML =
            `
            <tr>

                <td
                    colspan="8"
                    class="
                        text-center
                        text-danger
                        py-4
                    "
                >

                    Failed to load orders.

                </td>

            </tr>
            `;

    });

}


// -------------------------------------------------------------
// Filter Orders
// -------------------------------------------------------------

function filterAndRenderOrders() {

    const term =
        globalSearchTerm;


    const statusFilter =
        document
            .getElementById(
                'ordersStatusFilter'
            )
            ?.value || '';


    const filtered =
        ordersData.filter(
            order => {

                const year =
                    order.created_at

                        ? new Date(
                            order.created_at
                        ).getFullYear()

                        : new Date()
                            .getFullYear();


                const orderNumber =
                    `EJR-ORD-${year}-${String(
                        order.id
                    ).padStart(
                        5,
                        '0'
                    )}`;


                const searchable = [

                    orderNumber,

                    order.customer_name,

                    order.contact,

                    order.email,

                    order.status,

                    order.items,

                    order.total_price

                ]

                .filter(Boolean)

                .join(' ')

                .toLowerCase();


                const matchesSearch =
                    !term ||
                    searchable.includes(
                        term
                    );


                const matchesStatus =
                    !statusFilter ||

                    String(
                        order.status ||
                        ''
                    ).toLowerCase() ===

                    String(
                        statusFilter
                    ).toLowerCase();


                return (
                    matchesSearch &&
                    matchesStatus
                );

            }
        );


    renderOrders(
        filtered
    );

}


// -------------------------------------------------------------
// Render Orders
// -------------------------------------------------------------

function renderOrders(
    orders
) {

    const tbody =
        document.getElementById(
            'ordersTableBody'
        );


    if (!tbody) {
        return;
    }


    if (!orders.length) {

        tbody.innerHTML =
            `
            <tr>

                <td
                    colspan="8"
                    class="
                        text-center
                        text-muted
                        py-4
                    "
                >

                    <i
                        class="
                            bi
                            bi-receipt
                            fs-3
                            d-block
                            mb-2
                        "
                    ></i>

                    No orders match your search.

                </td>

            </tr>
            `;

        return;
    }


    tbody.innerHTML =
        orders.map(
            order => {

                const year =
                    order.created_at

                        ? new Date(
                            order.created_at
                        ).getFullYear()

                        : new Date()
                            .getFullYear();


                const orderNumber =
                    `EJR-ORD-${year}-${String(
                        order.id
                    ).padStart(
                        5,
                        '0'
                    )}`;


                let items = [];


                try {

                    items =
                        typeof order.items ===
                        'string'

                            ? JSON.parse(
                                order.items
                            )

                            : (
                                Array.isArray(
                                    order.items
                                )
                                    ? order.items
                                    : []
                            );

                }
                catch (error) {

                    console.warn(
                        'Could not parse order items:',
                        error
                    );

                }


                let itemsList =
                    '—';


                if (
                    Array.isArray(items) &&
                    items.length
                ) {

                    itemsList =
                        items
                            .map(
                                item => {

                                    const itemName =
                                        item.name ||
                                        item.part_name ||
                                        'Item';


                                    const quantity =
                                        item.qty ||
                                        item.quantity ||
                                        1;


                                    return `
                                        ${escapeHtml(
                                            itemName
                                        )}
                                        ×${quantity}
                                    `;

                                }
                            )
                            .join(', ');

                }
                else if (
                    order.items &&
                    typeof order.items ===
                    'string'
                ) {

                    itemsList =
                        escapeHtml(
                            order.items
                        );

                }


                const date =
                    order.created_at

                        ? new Date(
                            order.created_at
                        ).toLocaleDateString(
                            'en-US',
                            {
                                month:
                                    'short',
                                day:
                                    'numeric',
                                year:
                                    'numeric'
                            }
                        )

                        : '—';


                const customerName =
                    String(
                        order.customer_name ||
                        ''
                    ).replace(
                        /'/g,
                        "\\'"
                    );


                const isPending =
                    String(
                        order.status ||
                        ''
                    ).toLowerCase() ===
                    'pending';


                return `

                    <tr>

                        <td>

                            <code
                                style="
                                    color:#b30000;
                                "
                            >
                                ${orderNumber}
                            </code>

                        </td>


                        <td class="fw-bold">

                            ${escapeHtml(
                                order.customer_name ||
                                '—'
                            )}

                        </td>


                        <td>

                            ${escapeHtml(
                                order.contact ||
                                order.email ||
                                '—'
                            )}

                        </td>


                        <td
                            class="text-muted small"
                            style="
                                max-width:180px;
                                white-space:nowrap;
                                overflow:hidden;
                                text-overflow:ellipsis;
                            "
                            title="${escapeHtml(
                                itemsList
                            )}"
                        >

                            ${itemsList}

                        </td>


                        <td class="fw-bold text-danger">

                            ₱${Number(
                                order.total_price ||
                                0
                            ).toLocaleString(
                                'en-PH',
                                {
                                    minimumFractionDigits:
                                        2
                                }
                            )}

                        </td>


                        <td>

                            ${date}

                        </td>


                        <td>

                            ${statusBadge(
                                order.status
                            )}

                        </td>


                        <td>

                            <div
                                class="btn-group"
                            >

                                ${
                                    isPending

                                        ? `
                                            <button
                                                class="
                                                    btn
                                                    btn-sm
                                                    btn-outline-dark
                                                "
                                                onclick="
                                                    updateOrder(
                                                        ${Number(
                                                            order.id
                                                        )},
                                                        '${String(
                                                            order.status ||
                                                            ''
                                                        ).replace(
                                                            /'/g,
                                                            "\\'"
                                                        )}',
                                                        '${customerName}'
                                                    )
                                                "
                                                title="Manage Order"
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-pencil-square
                                                    "
                                                ></i>

                                            </button>
                                        `

                                        : ''
                                }


                                <button
                                    class="
                                        btn
                                        btn-sm
                                        btn-outline-secondary
                                    "
                                    onclick="
                                        deleteOrder(
                                            ${Number(
                                                order.id
                                            )}
                                        )
                                    "
                                    title="Delete Order"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-trash
                                        "
                                    ></i>

                                </button>


                                <a
                                    href="
                                        api/admin/generate_parts_receipt.php?id=${Number(
                                            order.id
                                        )}
                                    "
                                    target="_blank"
                                    class="
                                        btn
                                        btn-sm
                                        btn-outline-primary
                                    "
                                    title="View Invoice"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-receipt
                                        "
                                    ></i>

                                </a>

                            </div>

                        </td>

                    </tr>

                `;

            }
        ).join('');

}


// =============================================================
// SERVICES
// =============================================================

function loadServices() {

    fetch(
        'api/admin/manage_services.php',
        {
            credentials: 'include',
            headers: getAuthHeaders()
        }
    )

    .then(res => res.json())

    .then(data => {

        if (
            data.status !==
            'success'
        ) {

            console.error(
                'Failed to load services:',
                data.message
            );

            return;
        }


        servicesData =
            Array.isArray(
                data.services
            )
                ? data.services
                : [];


        const badge =
            document.getElementById(
                'badgeServices'
            );


        if (badge) {

            badge.textContent =
                servicesData.length;

        }


        filterAndRenderServices();

    })

    .catch(error => {

        console.error(
            'Services loading error:',
            error
        );

    });

}


// -------------------------------------------------------------
// Filter Services
// -------------------------------------------------------------

function filterAndRenderServices() {

    const term =
        globalSearchTerm;


    const filtered =
        servicesData.filter(
            service => {

                const searchable = [

                    service.name,

                    service.service_name,

                    service.description,

                    service.category,

                    service.price,

                    service.status

                ]

                .filter(Boolean)

                .join(' ')

                .toLowerCase();


                return (
                    !term ||
                    searchable.includes(
                        term
                    )
                );

            }
        );


    renderServices(
        filtered
    );

}


// -------------------------------------------------------------
// Render Services
// -------------------------------------------------------------

function renderServices(
    services
) {

    const tbody =
        document.getElementById(
            'servicesTableBody'
        );


    if (!tbody) {
        return;
    }


    if (!services.length) {

        tbody.innerHTML =
            `
            <tr>

                <td
                    colspan="6"
                    class="
                        text-center
                        text-muted
                        py-4
                    "
                >

                    No services match your search.

                </td>

            </tr>
            `;

        return;
    }


    tbody.innerHTML =
        services.map(
            service => {

                const id =
                    Number(
                        service.id
                    );


                const name =
                    service.name ||
                    service.service_name ||
                    'Unnamed Service';


                const price =
                    Number(
                        service.price ||
                        0
                    );


                const category =
                    service.category ||
                    'General';


                const description =
                    service.description ||
                    '';


                const status =
                    String(
                        service.status ||
                        'active'
                    ).toLowerCase();


                return `

                    <tr>

                        <td class="fw-bold">

                            ${escapeHtml(
                                name
                            )}

                        </td>


                        <td>

                            <span
                                class="
                                    badge
                                    bg-light
                                    text-dark
                                    border
                                "
                            >

                                ${escapeHtml(
                                    category
                                )}

                            </span>

                        </td>


                        <td>

                            ₱${price.toLocaleString(
                                'en-PH',
                                {
                                    minimumFractionDigits:
                                        2
                                }
                            )}

                        </td>


                        <td
                            class="text-muted small"
                            style="
                                max-width:250px;
                            "
                        >

                            ${escapeHtml(
                                description ||
                                '—'
                            )}

                        </td>


                        <td>

                            <span
                                class="
                                    badge
                                    ${
                                        status ===
                                        'active'

                                            ? 'bg-success'

                                            : 'bg-secondary'
                                    }
                                "
                            >

                                ${
                                    status ===
                                    'active'
                                        ? 'Active'
                                        : 'Inactive'
                                }

                            </span>

                        </td>


                        <td>

                            <div
                                class="btn-group"
                            >

                                <button
                                    class="
                                        btn
                                        btn-sm
                                        btn-outline-dark
                                    "
                                    onclick="
                                        editService(
                                            ${id}
                                        )
                                    "
                                    title="Edit Service"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-pencil-square
                                        "
                                    ></i>

                                </button>


                                <button
                                    class="
                                        btn
                                        btn-sm
                                        btn-outline-danger
                                    "
                                    onclick="
                                        deleteService(
                                            ${id},
                                            '${String(
                                                name
                                            ).replace(
                                                /'/g,
                                                "\\'"
                                            )}'
                                        )
                                    "
                                    title="Delete Service"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-trash
                                        "
                                    ></i>

                                </button>

                            </div>

                        </td>

                    </tr>

                `;

            }
        ).join('');

}


// =============================================================
// MECHANICS
// =============================================================

function loadMechanics() {

    fetch(
        'api/admin/manage_mechanics.php',
        {
            credentials: 'include',
            headers: getAuthHeaders()
        }
    )

    .then(res => res.json())

    .then(data => {

        if (
            data.status !==
            'success'
        ) {

            console.error(
                'Failed to load mechanics:',
                data.message
            );

            return;
        }


        mechanicsData =
            Array.isArray(
                data.mechanics
            )
                ? data.mechanics
                : [];


        const badge =
            document.getElementById(
                'badgeMechanics'
            );


        if (badge) {

            badge.textContent =
                mechanicsData.length;

        }


        filterAndRenderMechanics();

    })

    .catch(error => {

        console.error(
            'Mechanics loading error:',
            error
        );

    });

}


// -------------------------------------------------------------
// Filter Mechanics
// -------------------------------------------------------------

function filterAndRenderMechanics() {

    const term =
        globalSearchTerm;


    const filtered =
        mechanicsData.filter(
            mechanic => {

                const searchable = [

                    mechanic.full_name,

                    mechanic.name,

                    mechanic.email,

                    mechanic.phone,

                    mechanic.specialization,

                    mechanic.status

                ]

                .filter(Boolean)

                .join(' ')

                .toLowerCase();


                return (
                    !term ||
                    searchable.includes(
                        term
                    )
                );

            }
        );


    renderMechanics(
        filtered
    );

}


// -------------------------------------------------------------
// Render Mechanics
// -------------------------------------------------------------

function renderMechanics(
    mechanics
) {

    const tbody =
        document.getElementById(
            'mechanicsTableBody'
        );


    if (!tbody) {
        return;
    }


    if (!mechanics.length) {

        tbody.innerHTML =
            `
            <tr>

                <td
                    colspan="6"
                    class="
                        text-center
                        text-muted
                        py-4
                    "
                >

                    No mechanics match your search.

                </td>

            </tr>
            `;

        return;
    }


    tbody.innerHTML =
        mechanics.map(
            mechanic => {

                const id =
                    Number(
                        mechanic.id
                    );


                const name =
                    mechanic.full_name ||
                    mechanic.name ||
                    'Unnamed Mechanic';


                const status =
                    String(
                        mechanic.status ||
                        'active'
                    ).toLowerCase();


                return `

                    <tr>

                        <td class="fw-bold">

                            ${escapeHtml(
                                name
                            )}

                        </td>


                        <td>

                            ${escapeHtml(
                                mechanic.email ||
                                '—'
                            )}

                        </td>


                        <td>

                            ${escapeHtml(
                                mechanic.phone ||
                                '—'
                            )}

                        </td>


                        <td>

                            ${escapeHtml(
                                mechanic.specialization ||
                                'General'
                            )}

                        </td>


                        <td>

                            <span
                                class="
                                    badge
                                    ${
                                        status ===
                                        'active'

                                            ? 'bg-success'

                                            : 'bg-secondary'
                                    }
                                "
                            >

                                ${
                                    status ===
                                    'active'
                                        ? 'Active'
                                        : 'Inactive'
                                }

                            </span>

                        </td>


                        <td>

                            <div
                                class="btn-group"
                            >

                                <button
                                    class="
                                        btn
                                        btn-sm
                                        btn-outline-dark
                                    "
                                    onclick="
                                        editMechanic(
                                            ${id}
                                        )
                                    "
                                    title="Edit Mechanic"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-pencil-square
                                        "
                                    ></i>

                                </button>


                                <button
                                    class="
                                        btn
                                        btn-sm
                                        btn-outline-danger
                                    "
                                    onclick="
                                        deleteMechanic(
                                            ${id},
                                            '${String(
                                                name
                                            ).replace(
                                                /'/g,
                                                "\\'"
                                            )}'
                                        )
                                    "
                                    title="Delete Mechanic"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-trash
                                        "
                                    ></i>

                                </button>

                            </div>

                        </td>

                    </tr>

                `;

            }
        ).join('');

}


// =============================================================
// ORDER MANAGEMENT
// =============================================================

function updateOrder(
    id,
    currentStatus,
    customerName
) {

    const status =
        String(
            currentStatus ||
            'Pending'
        );


    const allowed = {

        'Pending': [
            'Confirmed',
            'Cancelled'
        ],

        'Confirmed': [
            'Completed',
            'Cancelled'
        ],

        'Completed': []

    };


    const nextStatuses =
        allowed[status] ||
        [];


    if (!nextStatuses.length) {

        Swal.fire({

            icon:
                'info',

            title:
                'Order Locked',

            text:
                'This order can no longer be changed.',

            confirmButtonColor:
                '#b30000'

        });

        return;

    }


    Swal.fire({

        title:
            'Update Order',

        html: `

            <div
                class="text-start"
            >

                <p
                    class="mb-3"
                >

                    Customer:

                    <strong>
                        ${escapeHtml(
                            customerName ||
                            'Customer'
                        )}
                    </strong>

                </p>


                <label
                    class="form-label fw-semibold"
                >

                    New Status

                </label>


                <select
                    id="orderStatusSelect"
                    class="form-select"
                >

                    ${nextStatuses.map(
                        s =>
                            `
                            <option
                                value="${escapeHtml(
                                    s
                                )}"
                            >

                                ${escapeHtml(
                                    s
                                )}

                            </option>
                            `
                    ).join('')}

                </select>

            </div>

        `,

        showCancelButton:
            true,

        confirmButtonColor:
            '#b30000',

        confirmButtonText:
            'Update',

        preConfirm: () => {

            return document
                .getElementById(
                    'orderStatusSelect'
                )
                ?.value || '';

        }

    })

    .then(result => {

        if (
            !result.isConfirmed ||
            !result.value
        ) {

            return;

        }


        postData(
            'api/admin/manage_orders.php',
            {

                action:
                    'update_status',

                id:
                    Number(id),

                status:
                    result.value

            },

            () => {

                loadOrders();

            }
        );

    });

}


function deleteOrder(
    id
) {

    Swal.fire({

        title:
            'Delete Order?',

        text:
            'This action cannot be undone.',

        icon:
            'warning',

        showCancelButton:
            true,

        confirmButtonColor:
            '#dc3545',

        cancelButtonColor:
            '#6c757d',

        confirmButtonText:
            'Delete'

    })

    .then(result => {

        if (
            !result.isConfirmed
        ) {

            return;

        }


        postData(
            'api/admin/manage_orders.php',
            {

                action:
                    'delete',

                id:
                    Number(id)

            },

            () => {

                loadOrders();

            }
        );

    });

}


// =============================================================
// GENERIC POST HELPER
// =============================================================

function postData(
    url,
    data,
    successCallback
) {

    fetch(
        url,
        {

            method:
                'POST',

            credentials:
                'include',

            headers:
                getAuthHeaders(),

            body:
                JSON.stringify(
                    data
                )

        }
    )

    .then(response => {

        if (
            !response.ok
        ) {

            throw new Error(
                `HTTP ${response.status}`
            );

        }


        return response.json();

    })

    .then(result => {

        if (
            result.status ===
            'success'
        ) {

            Swal.fire({

                icon:
                    'success',

                title:
                    'Success',

                text:
                    result.message ||
                    'Changes saved successfully.',

                confirmButtonColor:
                    '#b30000',

                timer:
                    1800,

                showConfirmButton:
                    false

            });


            if (
                typeof successCallback ===
                'function'
            ) {

                successCallback(
                    result
                );

            }


            // Refresh dashboard data so that
            // filters and counts stay current.

            setTimeout(
                () => {

                    if (
                        typeof loadFullSiteData ===
                        'function'
                    ) {

                        loadFullSiteData();

                    }

                },
                300
            );

        }
        else {

            throw new Error(
                result.message ||
                'The server rejected the request.'
            );

        }

    })

    .catch(error => {

        console.error(
            'POST request failed:',
            error
        );


        Swal.fire({

            icon:
                'error',

            title:
                'Request Failed',

            text:
                error.message ||
                'Something went wrong while saving the changes.',

            confirmButtonColor:
                '#b30000'

        });

    });

}


// =============================================================
// APPOINTMENT UPDATE
// =============================================================

function updateAppointmentStatus(
    id,
    status,
    extraData = {}
) {

    postData(
        'api/admin/update_appointment.php',
        {

            appointment_id:
                Number(id),

            status:
                status,

            ...extraData

        },

        () => {

            loadFullSiteData();

        }
    );

}


// =============================================================
// APPOINTMENT STATUS BUTTON
// =============================================================

function confirmAppointment(
    id
) {

    updateAppointmentStatus(
        id,
        'Confirmed'
    );

}


function startAppointment(
    id
) {

    updateAppointmentStatus(
        id,
        'In Progress'
    );

}


function completeAppointment(
    id
) {

    updateAppointmentStatus(
        id,
        'Completed'
    );

}


// =============================================================
// PART 4 END
// =============================================================

// =============================================================
// PART 5 — APPOINTMENTS & MECHANIC ASSIGNMENT
// =============================================================


// =============================================================
// LOAD APPOINTMENTS
// =============================================================

function loadAppointments() {

    const tbody =
        document.getElementById(
            'appointmentTableBody'
        );

    if (!tbody) {
        return;
    }


    fetch(
        'api/admin/manage_appointments.php',
        {
            credentials: 'include',
            headers: getAuthHeaders()
        }
    )

    .then(response => {

        if (!response.ok) {
            throw new Error(
                `HTTP ${response.status}`
            );
        }

        return response.json();

    })

    .then(data => {

        if (
            data.status !== 'success'
        ) {

            throw new Error(
                data.message ||
                'Unable to load appointments.'
            );

        }


        appointmentsData =
            Array.isArray(
                data.appointments
            )
                ? data.appointments
                : [];


        // Keep the main data object updated.

        if (
            typeof masterData ===
            'object'
        ) {

            masterData.appointments =
                appointmentsData;

        }


        updateAppointmentBadge();

        populateAppointmentFilters();

        filterAndRenderAppointments();

    })

    .catch(error => {

        console.error(
            'Appointment loading error:',
            error
        );


        tbody.innerHTML =
            `
            <tr>

                <td
                    colspan="10"
                    class="
                        text-center
                        text-danger
                        py-4
                    "
                >

                    <i
                        class="
                            bi
                            bi-exclamation-triangle
                            fs-3
                            d-block
                            mb-2
                        "
                    ></i>

                    Failed to load appointments.

                    <div
                        class="
                            small
                            text-muted
                            mt-1
                        "
                    >

                        ${escapeHtml(
                            error.message
                        )}

                    </div>

                </td>

            </tr>
            `;

    });

}


// =============================================================
// APPOINTMENT BADGE
// =============================================================

function updateAppointmentBadge() {

    const badge =
        document.getElementById(
            'badgeAppointments'
        );


    if (!badge) {
        return;
    }


    const pending =
        appointmentsData.filter(
            appointment => {

                const status =
                    appointmentStatusKey(
                        appointment.status
                    );


                return (
                    status ===
                    'Pending'
                );

            }
        ).length;


    badge.textContent =
        pending;


}


// =============================================================
// APPOINTMENT FILTER DROPDOWNS
// =============================================================

function populateAppointmentFilters() {

    const mechanicSelect =
        document.getElementById(
            'appointmentMechanicFilter'
        );


    if (
        mechanicSelect
    ) {

        const current =
            mechanicSelect.value;


        const mechanics =
            [
                ...new Map(

                    appointmentsData

                        .filter(
                            appointment =>
                                appointment.mechanic_id ||
                                appointment.mechanic_name
                        )

                        .map(
                            appointment => [

                                String(
                                    appointment.mechanic_id ||
                                    appointment.mechanic_name
                                ),

                                appointment.mechanic_name ||
                                `Mechanic #${appointment.mechanic_id}`

                            ]
                        )

                ).entries()
            ];


        mechanicSelect.innerHTML =
            `
            <option value="">
                All Mechanics
            </option>
            `;


        mechanics.forEach(
            ([id, name]) => {

                const option =
                    document.createElement(
                        'option'
                    );


                option.value =
                    id;


                option.textContent =
                    name;


                mechanicSelect.appendChild(
                    option
                );

            }
        );


        mechanicSelect.value =
            current;

    }


    const statusSelect =
        document.getElementById(
            'appointmentStatusFilter'
        );


    if (
        statusSelect &&
        statusSelect.options.length <= 1
    ) {

        const statuses = [

            'Pending',

            'Confirmed',

            'In Progress',

            'Completed',

            'Cancelled'

        ];


        statuses.forEach(
            status => {

                const option =
                    document.createElement(
                        'option'
                    );


                option.value =
                    status;


                option.textContent =
                    status;


                statusSelect.appendChild(
                    option
                );

            }
        );

    }

}


// =============================================================
// FILTER APPOINTMENTS
// =============================================================

function filterAndRenderAppointments() {

    const search =
        globalSearchTerm;


    const status =
        document
            .getElementById(
                'appointmentStatusFilter'
            )
            ?.value || '';


    const mechanic =
        document
            .getElementById(
                'appointmentMechanicFilter'
            )
            ?.value || '';


    const date =
        document
            .getElementById(
                'appointmentDateFilter'
            )
            ?.value || '';


    const filtered =
        appointmentsData.filter(
            appointment => {

                const searchable = [

                    appointment.id,

                    appointment.customer_name,

                    appointment.full_name,

                    appointment.email,

                    appointment.phone,

                    appointment.plate_number,

                    appointment.vehicle_plate,

                    appointment.brand,

                    appointment.model,

                    appointment.service_name,

                    appointment.service_type,

                    appointment.mechanic_name,

                    appointment.status,

                    appointment.notes

                ]

                .filter(Boolean)

                .join(' ')

                .toLowerCase();


                const matchesSearch =
                    !search ||
                    searchable.includes(
                        search
                    );


                const normalizedStatus =
                    appointmentStatusKey(
                        appointment.status
                    );


                const matchesStatus =
                    !status ||
                    normalizedStatus ===
                    appointmentStatusKey(
                        status
                    );


                const mechanicId =
                    String(
                        appointment.mechanic_id ||
                        ''
                    );


                const mechanicName =
                    String(
                        appointment.mechanic_name ||
                        ''
                    );


                const matchesMechanic =
                    !mechanic ||

                    mechanicId ===
                    String(mechanic)

                    ||

                    mechanicName ===
                    String(mechanic);


                let matchesDate =
                    true;


                if (date) {

                    const appointmentDate =
                        String(
                            appointment.appointment_date ||
                            appointment.date ||
                            appointment.scheduled_date ||
                            ''
                        ).substring(
                            0,
                            10
                        );


                    matchesDate =
                        appointmentDate ===
                        date;

                }


                return (

                    matchesSearch &&

                    matchesStatus &&

                    matchesMechanic &&

                    matchesDate

                );

            }
        );


    renderAppointments(
        filtered
    );

}


// =============================================================
// RENDER APPOINTMENTS
// =============================================================

function renderAppointments(
    appointments
) {

    const tbody =
        document.getElementById(
            'appointmentTableBody'
        );


    if (!tbody) {
        return;
    }


    if (!appointments.length) {

        tbody.innerHTML =
            `
            <tr>

                <td
                    colspan="10"
                    class="
                        text-center
                        text-muted
                        py-5
                    "
                >

                    <i
                        class="
                            bi
                            bi-calendar-x
                            fs-2
                            d-block
                            mb-2
                        "
                    ></i>

                    No appointments found.

                </td>

            </tr>
            `;

        return;
    }


    tbody.innerHTML =
        appointments.map(
            appointment => {

                const id =
                    Number(
                        appointment.id
                    );


                const status =
                    appointmentStatusKey(
                        appointment.status
                    );


                const customer =
                    appointment.customer_name ||
                    appointment.full_name ||
                    '—';


                const plate =
                    appointment.plate_number ||
                    appointment.vehicle_plate ||
                    '—';


                const vehicle =
                    [

                        appointment.brand,

                        appointment.model,

                        appointment.year_model

                    ]

                    .filter(Boolean)

                    .join(' ') ||

                    '—';


                const service =
                    appointment.service_name ||
                    appointment.service_type ||
                    appointment.service ||
                    '—';


                const mechanic =
                    appointment.mechanic_name ||
                    'Unassigned';


                const appointmentDate =
                    appointment.appointment_date ||
                    appointment.date ||
                    appointment.scheduled_date ||
                    '';


                const appointmentTime =
                    appointment.appointment_time ||
                    appointment.time ||
                    appointment.scheduled_time ||
                    '';


                const formattedDate =
                    formatAppointmentDate(
                        appointmentDate
                    );


                const formattedTime =
                    formatAppointmentTime(
                        appointmentTime
                    );


                const locked =
                    isAppointmentLocked(
                        status
                    );


                const customerSafe =
                    String(
                        customer
                    ).replace(
                        /'/g,
                        "\\'"
                    );


                return `

                    <tr>

                        <td>

                            <span
                                class="
                                    fw-bold
                                    text-danger
                                "
                            >

                                #${id}

                            </span>

                        </td>


                        <td>

                            <div
                                class="fw-semibold"
                            >

                                ${escapeHtml(
                                    customer
                                )}

                            </div>


                            ${
                                appointment.email

                                    ? `
                                        <div
                                            class="
                                                small
                                                text-muted
                                            "
                                        >
                                            ${escapeHtml(
                                                appointment.email
                                            )}
                                        </div>
                                    `

                                    : ''
                            }

                        </td>


                        <td>

                            <div
                                class="fw-semibold"
                            >

                                ${escapeHtml(
                                    vehicle
                                )}

                            </div>


                            <code
                                class="
                                    small
                                    text-uppercase
                                "
                                style="
                                    color:#b30000;
                                "
                            >

                                ${escapeHtml(
                                    plate
                                )}

                            </code>

                        </td>


                        <td>

                            <span
                                class="
                                    small
                                    fw-semibold
                                "
                            >

                                ${escapeHtml(
                                    service
                                )}

                            </span>

                        </td>


                        <td>

                            <div
                                class="
                                    fw-semibold
                                "
                            >

                                ${escapeHtml(
                                    formattedDate
                                )}

                            </div>


                            ${
                                formattedTime

                                    ? `
                                        <div
                                            class="
                                                small
                                                text-muted
                                            "
                                        >

                                            <i
                                                class="
                                                    bi
                                                    bi-clock
                                                "
                                            ></i>

                                            ${escapeHtml(
                                                formattedTime
                                            )}

                                        </div>
                                    `

                                    : ''
                            }

                        </td>


                        <td>

                            <span
                                class="
                                    mechanic-name
                                    ${
                                        mechanic ===
                                        'Unassigned'
                                            ? 'text-muted'
                                            : ''
                                    }
                                "
                            >

                                ${
                                    mechanic ===
                                    'Unassigned'

                                        ? `
                                            <i
                                                class="
                                                    bi
                                                    bi-person-x
                                                "
                                            ></i>
                                          `

                                        : `
                                            <i
                                                class="
                                                    bi
                                                    bi-person-check
                                                "
                                            ></i>
                                          `
                                }

                                ${escapeHtml(
                                    mechanic
                                )}

                            </span>

                        </td>


                        <td>

                            ${statusBadge(
                                status
                            )}

                        </td>


                        <td
                            class="
                                appointment-notes-cell
                            "
                        >

                            ${getAppointmentNotes(
                                appointment
                            )}

                        </td>


                        <td>

                            <div
                                class="
                                    btn-group
                                    btn-group-sm
                                "
                            >

                                <button
                                    type="button"
                                    class="
                                        btn
                                        btn-outline-dark
                                    "
                                    onclick="
                                        manageAppointment(
                                            ${id}
                                        )
                                    "
                                    title="Manage Appointment"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-pencil-square
                                        "
                                    ></i>

                                </button>


                                ${
                                    !locked

                                        ? `
                                            <button
                                                type="button"
                                                class="
                                                    btn
                                                    btn-outline-primary
                                                "
                                                onclick="
                                                    assignAppointmentMechanic(
                                                        ${id}
                                                    )
                                                "
                                                title="Assign Mechanic"
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-person-plus
                                                    "
                                                ></i>

                                            </button>
                                        `

                                        : ''
                                }


                                ${
                                    status ===
                                    'Completed'

                                        ? `
                                            <a
                                                href="
                                                    api/admin/generate_receipt.php?id=${id}
                                                "
                                                target="_blank"
                                                class="
                                                    btn
                                                    btn-outline-success
                                                "
                                                title="View Receipt"
                                            >

                                                <i
                                                    class="
                                                        bi
                                                        bi-receipt
                                                    "
                                                ></i>

                                            </a>
                                        `

                                        : ''
                                }

                            </div>

                        </td>

                    </tr>

                `;

            }
        ).join('');

}


// =============================================================
// APPOINTMENT DATE FORMAT
// =============================================================

function formatAppointmentDate(
    value
) {

    if (!value) {
        return '—';
    }


    const date =
        new Date(
            String(value)
        );


    if (
        Number.isNaN(
            date.getTime()
        )
    ) {

        return String(
            value
        );

    }


    return date.toLocaleDateString(
        'en-US',
        {

            month:
                'short',

            day:
                'numeric',

            year:
                'numeric'

        }
    );

}


// =============================================================
// APPOINTMENT TIME FORMAT
// =============================================================

function formatAppointmentTime(
    value
) {

    if (!value) {
        return '';
    }


    const parts =
        String(
            value
        ).split(':');


    if (
        parts.length <
        2
    ) {

        return String(
            value
        );

    }


    let hours =
        parseInt(
            parts[0],
            10
        );


    const minutes =
        parts[1];


    if (
        Number.isNaN(hours)
    ) {

        return String(
            value
        );

    }


    const suffix =
        hours >= 12
            ? 'PM'
            : 'AM';


    hours =
        hours % 12 ||
        12;


    return `${hours}:${minutes} ${suffix}`;

}


// =============================================================
// MANAGE APPOINTMENT
// =============================================================

function manageAppointment(
    id
) {

    const appointment =
        appointmentsData.find(
            item =>
                Number(item.id) ===
                Number(id)
        );


    if (!appointment) {

        Swal.fire({

            icon:
                'error',

            title:
                'Appointment Not Found',

            text:
                'The appointment could not be found.'

        });

        return;

    }


    const currentStatus =
        appointmentStatusKey(
            appointment.status
        );


    const locked =
        isAppointmentLocked(
            currentStatus
        );


    const allowedStatuses =
        getAllowedAppointmentStatuses(
            currentStatus
        );


    const statusOptions =
        allowedStatuses.length

            ? allowedStatuses

            : [currentStatus];


    const selectedMechanic =
        appointment.mechanic_id ||
        '';


    Swal.fire({

        title:
            `Appointment #${Number(id)}`,

        width:
            700,

        html: `

            <div
                class="text-start"
            >

                <div
                    class="
                        alert
                        alert-light
                        border
                        mb-3
                    "
                >

                    <div
                        class="fw-bold"
                    >

                        ${escapeHtml(
                            appointment.customer_name ||
                            appointment.full_name ||
                            'Customer'
                        )}

                    </div>


                    <div
                        class="
                            small
                            text-muted
                        "
                    >

                        ${escapeHtml(
                            appointment.service_name ||
                            appointment.service_type ||
                            'Service'
                        )}

                    </div>

                </div>


                <div
                    class="mb-3"
                >

                    <label
                        class="
                            form-label
                            fw-semibold
                        "
                    >

                        Appointment Status

                    </label>


                    <select
                        id="manageAppointmentStatus"
                        class="form-select"
                        ${locked ? 'disabled' : ''}
                    >

                        ${statusOptions.map(
                            option =>
                                `
                                <option
                                    value="${escapeHtml(
                                        option
                                    )}"
                                    ${
                                        appointmentStatusKey(
                                            option
                                        ) ===
                                        currentStatus
                                            ? 'selected'
                                            : ''
                                    }
                                >

                                    ${escapeHtml(
                                        option
                                    )}

                                </option>
                                `
                        ).join('')}

                    </select>

                </div>


                <div
                    class="mb-3"
                >

                    <label
                        class="
                            form-label
                            fw-semibold
                        "
                    >

                        Assign Mechanic

                    </label>


                    <select
                        id="manageAppointmentMechanic"
                        class="form-select"
                        ${locked ? 'disabled' : ''}
                    >

                        <option
                            value=""
                        >
                            Unassigned
                        </option>

                        ${getMechanicOptions(
                            selectedMechanic
                        )}

                    </select>

                </div>


                <div
                    class="mb-3"
                >

                    <label
                        class="
                            form-label
                            fw-semibold
                        "
                    >

                        Notes

                    </label>


                    <textarea
                        id="manageAppointmentNotes"
                        class="form-control"
                        rows="3"
                        ${
                            locked
                                ? 'disabled'
                                : ''
                        }
                    >${escapeHtml(
                        appointment.notes ||
                        ''
                    )}</textarea>

                </div>


                ${
                    locked

                        ? `
                            <div
                                class="
                                    alert
                                    alert-secondary
                                    mb-0
                                "
                            >

                                <i
                                    class="
                                        bi
                                        bi-lock-fill
                                    "
                                ></i>

                                This appointment is
                                <strong>
                                    ${escapeHtml(
                                        currentStatus
                                    )}
                                </strong>
                                and can no longer be edited.

                            </div>
                          `

                        : ''
                }

            </div>

        `,

        showCancelButton:
            true,

        confirmButtonColor:
            '#b30000',

        confirmButtonText:
            locked
                ? 'Close'
                : 'Save Changes',

        showConfirmButton:
            !locked,

        preConfirm: () => {

            const newStatus =
                document
                    .getElementById(
                        'manageAppointmentStatus'
                    )
                    ?.value;


            const mechanic =
                document
                    .getElementById(
                        'manageAppointmentMechanic'
                    )
                    ?.value || '';


            const notes =
                document
                    .getElementById(
                        'manageAppointmentNotes'
                    )
                    ?.value
                    .trim() || '';


            if (
                !newStatus
            ) {

                Swal.showValidationMessage(
                    'Please select an appointment status.'
                );

                return false;

            }


            return {

                status:
                    newStatus,

                mechanic_id:
                    mechanic
                        ? Number(mechanic)
                        : null,

                notes:
                    notes

            };

        }

    })

    .then(result => {

        if (
            !result.isConfirmed ||
            !result.value ||
            locked
        ) {

            return;

        }


        updateAppointmentStatus(
            id,
            result.value.status,
            {

                mechanic_id:
                    result.value
                        .mechanic_id,

                notes:
                    result.value
                        .notes

            }
        );

    });

}


// =============================================================
// ASSIGN MECHANIC
// =============================================================

function assignAppointmentMechanic(
    id
) {

    const appointment =
        appointmentsData.find(
            item =>
                Number(item.id) ===
                Number(id)
        );


    if (!appointment) {

        return;

    }


    const currentMechanic =
        appointment.mechanic_id ||
        '';


    Swal.fire({

        title:
            'Assign Mechanic',

        html: `

            <div
                class="text-start"
            >

                <label
                    class="
                        form-label
                        fw-semibold
                    "
                >

                    Select Mechanic

                </label>


                <select
                    id="assignMechanicSelect"
                    class="form-select"
                >

                    <option
                        value=""
                    >
                        Unassigned
                    </option>

                    ${getMechanicOptions(
                        currentMechanic
                    )}

                </select>

            </div>

        `,

        showCancelButton:
            true,

        confirmButtonColor:
            '#b30000',

        confirmButtonText:
            'Assign',

        preConfirm: () => {

            return document
                .getElementById(
                    'assignMechanicSelect'
                )
                ?.value || '';

        }

    })

    .then(result => {

        if (
            !result.isConfirmed
        ) {

            return;

        }


        updateAppointmentStatus(
            id,
            appointmentStatusKey(
                appointment.status
            ),
            {

                mechanic_id:
                    result.value
                        ? Number(
                            result.value
                        )
                        : null

            }
        );

    });

}


// =============================================================
// MECHANIC OPTIONS
// =============================================================

function getMechanicOptions(
    selectedId = ''
) {

    if (
        !Array.isArray(
            mechanicsData
        ) ||
        !mechanicsData.length
    ) {

        return `
            <option
                value=""
                disabled
            >
                No mechanics available
            </option>
        `;

    }


    return mechanicsData
        .filter(
            mechanic => {

                const status =
                    String(
                        mechanic.status ||
                        'active'
                    ).toLowerCase();


                return (
                    status ===
                    'active' ||
                    !mechanic.status
                );

            }
        )
        .map(
            mechanic => {

                const id =
                    mechanic.id;


                const name =
                    mechanic.full_name ||
                    mechanic.name ||
                    `Mechanic #${id}`;


                return `

                    <option
                        value="${Number(id)}"
                        ${
                            String(id) ===
                            String(selectedId)
                                ? 'selected'
                                : ''
                        }
                    >

                        ${escapeHtml(
                            name
                        )}

                    </option>

                `;

            }
        )
        .join('');

}


// =============================================================
// APPOINTMENT STATUS NORMALIZATION
// =============================================================

function appointmentStatusKey(
    value
) {

    if (!value) {
        return 'Pending';
    }


    const raw =
        String(
            value
        )
        .trim()
        .toLowerCase();


    const map = {

        pending:
            'Pending',

        confirmed:
            'Confirmed',

        accepted:
            'Confirmed',

        scheduled:
            'Confirmed',

        'in progress':
            'In Progress',

        in_progress:
            'In Progress',

        ongoing:
            'In Progress',

        completed:
            'Completed',

        complete:
            'Completed',

        cancelled:
            'Cancelled',

        canceled:
            'Cancelled',

        rejected:
            'Cancelled'

    };


    return (
        map[raw] ||
        String(value)
    );

}


// =============================================================
// APPOINTMENT STATUS FLOW
// =============================================================

const APPOINTMENT_STATUS_FLOW = {

    Pending: [

        'Confirmed',

        'Cancelled'

    ],

    Confirmed: [

        'In Progress',

        'Cancelled'

    ],

    'In Progress': [

        'Completed',

        'Cancelled'

    ],

    Completed: [],

    Cancelled: []

};


// =============================================================
// STATUS BADGE
// =============================================================

function statusBadge(
    status
) {

    const normalized =
        appointmentStatusKey(
            status
        );


    let badgeClass =
        'bg-secondary';


    switch (
        normalized
    ) {

        case 'Pending':

            badgeClass =
                'bg-warning text-dark';

            break;


        case 'Confirmed':

            badgeClass =
                'bg-primary';

            break;


        case 'In Progress':

            badgeClass =
                'bg-info text-dark';

            break;


        case 'Completed':

            badgeClass =
                'bg-success';

            break;


        case 'Cancelled':

            badgeClass =
                'bg-danger';

            break;

    }


    return `

        <span
            class="
                badge
                ${badgeClass}
            "
        >

            ${escapeHtml(
                normalized
            )}

        </span>

    `;

}


// =============================================================
// QUICK APPOINTMENT ACTIONS
// =============================================================

function approveAppointment(
    id
) {

    Swal.fire({

        title:
            'Confirm Appointment?',

        text:
            'The customer will see the appointment as confirmed.',

        icon:
            'question',

        showCancelButton:
            true,

        confirmButtonColor:
            '#198754',

        cancelButtonColor:
            '#6c757d',

        confirmButtonText:
            'Confirm Appointment'

    })

    .then(result => {

        if (
            !result.isConfirmed
        ) {

            return;

        }


        confirmAppointment(
            id
        );

    });

}


function cancelAppointment(
    id
) {

    Swal.fire({

        title:
            'Cancel Appointment',

        input:
            'textarea',

        inputLabel:
            'Cancellation Reason',

        inputPlaceholder:
            'Enter the reason for cancellation...',

        inputAttributes: {

            'aria-label':
                'Cancellation reason'

        },

        showCancelButton:
            true,

        confirmButtonColor:
            '#dc3545',

        cancelButtonColor:
            '#6c757d',

        confirmButtonText:
            'Cancel Appointment',

        preConfirm: reason => {

            if (
                !reason ||
                !reason.trim()
            ) {

                Swal.showValidationMessage(
                    'Please provide a cancellation reason.'
                );

                return false;

            }


            return reason.trim();

        }

    })

    .then(result => {

        if (
            !result.isConfirmed
        ) {

            return;

        }


        updateAppointmentStatus(
            id,
            'Cancelled',
            {

                cancellation_reason:
                    result.value

            }
        );

    });

}


// =============================================================
// REFRESH APPOINTMENT DATA
// =============================================================

function refreshAppointmentData() {

    loadAppointments();

}


// =============================================================
// PART 5 END
// =============================================================

// =============================================================
// PART 6 — USERS, VEHICLES & SERVICE HISTORY ACTIONS
// =============================================================


// =============================================================
// USER MANAGEMENT
// =============================================================

function editUser(id) {

    const user =
        masterData.users.find(
            item =>
                Number(item.id) ===
                Number(id)
        );


    if (!user) {

        Swal.fire({

            icon: 'error',

            title: 'User Not Found',

            text:
                'The selected user could not be found.'

        });

        return;

    }


    Swal.fire({

        title: 'Edit User',

        width: 650,

        html: `

            <div class="text-start">

                <div class="mb-3">

                    <label
                        class="form-label fw-semibold"
                    >
                        Full Name
                    </label>

                    <input
                        id="editUserName"
                        type="text"
                        class="form-control"
                        value="${escapeHtml(
                            user.full_name || ''
                        )}"
                    >

                </div>


                <div class="mb-3">

                    <label
                        class="form-label fw-semibold"
                    >
                        Email
                    </label>

                    <input
                        id="editUserEmail"
                        type="email"
                        class="form-control"
                        value="${escapeHtml(
                            user.email || ''
                        )}"
                    >

                </div>


                <div class="mb-3">

                    <label
                        class="form-label fw-semibold"
                    >
                        Phone
                    </label>

                    <input
                        id="editUserPhone"
                        type="text"
                        class="form-control"
                        value="${escapeHtml(
                            user.phone || ''
                        )}"
                    >

                </div>


                <div class="mb-3">

                    <label
                        class="form-label fw-semibold"
                    >
                        Role
                    </label>

                    <select
                        id="editUserRole"
                        class="form-select"
                    >

                        <option
                            value="1"
                            ${
                                Number(user.role_id) === 1
                                    ? 'selected'
                                    : ''
                            }
                        >
                            Customer
                        </option>

                        <option
                            value="2"
                            ${
                                Number(user.role_id) === 2
                                    ? 'selected'
                                    : ''
                            }
                        >
                            Admin
                        </option>

                        <option
                            value="3"
                            ${
                                Number(user.role_id) === 3
                                    ? 'selected'
                                    : ''
                            }
                        >
                            Mechanic
                        </option>

                    </select>

                </div>

            </div>

        `,

        showCancelButton: true,

        confirmButtonColor: '#b30000',

        confirmButtonText: 'Save Changes',

        preConfirm: () => {

            const name =
                document
                    .getElementById(
                        'editUserName'
                    )
                    ?.value
                    .trim();


            const email =
                document
                    .getElementById(
                        'editUserEmail'
                    )
                    ?.value
                    .trim();


            const phone =
                document
                    .getElementById(
                        'editUserPhone'
                    )
                    ?.value
                    .trim();


            const role =
                document
                    .getElementById(
                        'editUserRole'
                    )
                    ?.value;


            if (!name) {

                Swal.showValidationMessage(
                    'Full name is required.'
                );

                return false;

            }


            if (!email) {

                Swal.showValidationMessage(
                    'Email is required.'
                );

                return false;

            }


            return {

                id:
                    Number(id),

                full_name:
                    name,

                email:
                    email,

                phone:
                    phone,

                role_id:
                    Number(role)

            };

        }

    })

    .then(result => {

        if (
            !result.isConfirmed ||
            !result.value
        ) {

            return;

        }


        postData(
            'api/admin/manage_users.php',
            {

                action:
                    'update',

                ...result.value

            },

            () => {

                loadUsers();

            }
        );

    });

}


// =============================================================
// DELETE USER
// =============================================================

function deleteUser(
    id,
    name
) {

    Swal.fire({

        title:
            'Delete User?',

        html:
            `
            Are you sure you want to delete
            <strong>
                ${escapeHtml(
                    name ||
                    'this user'
                )}
            </strong>?
            <br><br>
            <span class="text-danger">
                This action cannot be undone.
            </span>
            `,

        icon:
            'warning',

        showCancelButton:
            true,

        confirmButtonColor:
            '#dc3545',

        cancelButtonColor:
            '#6c757d',

        confirmButtonText:
            'Yes, Delete'

    })

    .then(result => {

        if (
            !result.isConfirmed
        ) {

            return;

        }


        postData(
            'api/admin/manage_users.php',
            {

                action:
                    'delete',

                id:
                    Number(id)

            },

            () => {

                loadUsers();

            }
        );

    });

}


// =============================================================
// LOAD USERS
// =============================================================

function loadUsers() {

    fetch(
        'api/admin/manage_users.php?action=list',
        {

            credentials:
                'include',

            headers:
                getAuthHeaders()

        }
    )

    .then(response => {

        if (!response.ok) {

            throw new Error(
                `HTTP ${response.status}`
            );

        }


        return response.json();

    })

    .then(data => {

        if (
            data.status !==
            'success'
        ) {

            throw new Error(
                data.message ||
                'Unable to load users.'
            );

        }


        masterData.users =
            Array.isArray(
                data.users
            )
                ? data.users
                : [];


        filterAndRenderUsers();

    })

    .catch(error => {

        console.error(
            'User loading error:',
            error
        );

    });

}


// =============================================================
// VEHICLE MANAGEMENT
// =============================================================

function editVehicle(
    id,
    userId,
    brand,
    model,
    yearModel,
    plateNumber,
    fuelType,
    transmission
) {

    Swal.fire({

        title:
            'Edit Vehicle',

        width:
            700,

        html: `

            <div class="text-start">

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label
                            class="form-label fw-semibold"
                        >
                            Brand
                        </label>

                        <input
                            id="editVehicleBrand"
                            class="form-control"
                            value="${escapeHtml(
                                brand || ''
                            )}"
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label
                            class="form-label fw-semibold"
                        >
                            Model
                        </label>

                        <input
                            id="editVehicleModel"
                            class="form-control"
                            value="${escapeHtml(
                                model || ''
                            )}"
                        >

                    </div>

                </div>


                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label
                            class="form-label fw-semibold"
                        >
                            Year Model
                        </label>

                        <input
                            id="editVehicleYear"
                            type="number"
                            class="form-control"
                            min="1900"
                            max="2100"
                            value="${escapeHtml(
                                yearModel || ''
                            )}"
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label
                            class="form-label fw-semibold"
                        >
                            Plate Number
                        </label>

                        <input
                            id="editVehiclePlate"
                            class="form-control text-uppercase"
                            value="${escapeHtml(
                                plateNumber || ''
                            )}"
                        >

                    </div>

                </div>


                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label
                            class="form-label fw-semibold"
                        >
                            Fuel Type
                        </label>

                        <select
                            id="editVehicleFuel"
                            class="form-select"
                        >

                            <option
                                value="Gasoline"
                                ${
                                    String(
                                        fuelType
                                    ).toLowerCase() ===
                                    'gasoline'
                                        ? 'selected'
                                        : ''
                                }
                            >
                                Gasoline
                            </option>

                            <option
                                value="Diesel"
                                ${
                                    String(
                                        fuelType
                                    ).toLowerCase() ===
                                    'diesel'
                                        ? 'selected'
                                        : ''
                                }
                            >
                                Diesel
                            </option>

                            <option
                                value="Electric"
                                ${
                                    String(
                                        fuelType
                                    ).toLowerCase() ===
                                    'electric'
                                        ? 'selected'
                                        : ''
                                }
                            >
                                Electric
                            </option>

                            <option
                                value="Hybrid"
                                ${
                                    String(
                                        fuelType
                                    ).toLowerCase() ===
                                    'hybrid'
                                        ? 'selected'
                                        : ''
                                }
                            >
                                Hybrid
                            </option>

                        </select>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label
                            class="form-label fw-semibold"
                        >
                            Transmission
                        </label>

                        <select
                            id="editVehicleTransmission"
                            class="form-select"
                        >

                            <option
                                value="Automatic"
                                ${
                                    String(
                                        transmission
                                    ).toLowerCase() ===
                                    'automatic'
                                        ? 'selected'
                                        : ''
                                }
                            >
                                Automatic
                            </option>

                            <option
                                value="Manual"
                                ${
                                    String(
                                        transmission
                                    ).toLowerCase() ===
                                    'manual'
                                        ? 'selected'
                                        : ''
                                }
                            >
                                Manual
                            </option>

                            <option
                                value="CVT"
                                ${
                                    String(
                                        transmission
                                    ).toLowerCase() ===
                                    'cvt'
                                        ? 'selected'
                                        : ''
                                }
                            >
                                CVT
                            </option>

                        </select>

                    </div>

                </div>

            </div>

        `,

        showCancelButton:
            true,

        confirmButtonColor:
            '#b30000',

        confirmButtonText:
            'Save Changes',

        preConfirm: () => {

            const newBrand =
                document
                    .getElementById(
                        'editVehicleBrand'
                    )
                    ?.value
                    .trim();


            const newModel =
                document
                    .getElementById(
                        'editVehicleModel'
                    )
                    ?.value
                    .trim();


            const newYear =
                document
                    .getElementById(
                        'editVehicleYear'
                    )
                    ?.value
                    .trim();


            const newPlate =
                document
                    .getElementById(
                        'editVehiclePlate'
                    )
                    ?.value
                    .trim()
                    .toUpperCase();


            const newFuel =
                document
                    .getElementById(
                        'editVehicleFuel'
                    )
                    ?.value;


            const newTransmission =
                document
                    .getElementById(
                        'editVehicleTransmission'
                    )
                    ?.value;


            if (!newBrand) {

                Swal.showValidationMessage(
                    'Vehicle brand is required.'
                );

                return false;

            }


            if (!newModel) {

                Swal.showValidationMessage(
                    'Vehicle model is required.'
                );

                return false;

            }


            if (!newPlate) {

                Swal.showValidationMessage(
                    'Plate number is required.'
                );

                return false;

            }


            return {

                id:
                    Number(id),

                user_id:
                    Number(userId),

                brand:
                    newBrand,

                model:
                    newModel,

                year_model:
                    newYear
                        ? Number(newYear)
                        : null,

                plate_number:
                    newPlate,

                fuel_type:
                    newFuel,

                transmission:
                    newTransmission

            };

        }

    })

    .then(result => {

        if (
            !result.isConfirmed ||
            !result.value
        ) {

            return;

        }


        postData(
            'api/admin/manage_vehicles.php',
            {

                action:
                    'update',

                ...result.value

            },

            () => {

                loadVehicles();

            }
        );

    });

}


// =============================================================
// DELETE VEHICLE
// =============================================================

function deleteVehicle(
    id,
    plateNumber
) {

    Swal.fire({

        title:
            'Delete Vehicle?',

        html:
            `
            Are you sure you want to delete
            the vehicle with plate
            <strong>
                ${escapeHtml(
                    plateNumber ||
                    'this vehicle'
                )}
            </strong>?
            `,

        icon:
            'warning',

        showCancelButton:
            true,

        confirmButtonColor:
            '#dc3545',

        cancelButtonColor:
            '#6c757d',

        confirmButtonText:
            'Yes, Delete'

    })

    .then(result => {

        if (
            !result.isConfirmed
        ) {

            return;

        }


        postData(
            'api/admin/manage_vehicles.php',
            {

                action:
                    'delete',

                id:
                    Number(id)

            },

            () => {

                loadVehicles();

            }
        );

    });

}


// =============================================================
// LOAD VEHICLES
// =============================================================

function loadVehicles() {

    fetch(
        'api/admin/manage_vehicles.php?action=list',
        {

            credentials:
                'include',

            headers:
                getAuthHeaders()

        }
    )

    .then(response => {

        if (!response.ok) {

            throw new Error(
                `HTTP ${response.status}`
            );

        }


        return response.json();

    })

    .then(data => {

        if (
            data.status !==
            'success'
        ) {

            throw new Error(
                data.message ||
                'Unable to load vehicles.'
            );

        }


        masterData.vehicles =
            Array.isArray(
                data.vehicles
            )
                ? data.vehicles
                : [];


        filterAndRenderVehicles();

    })

    .catch(error => {

        console.error(
            'Vehicle loading error:',
            error
        );

    });

}


// =============================================================
// SERVICE HISTORY
// =============================================================

function editHistory(
    id,
    service,
    serviceDate
) {

    Swal.fire({

        title:
            'Edit Service History',

        width:
            600,

        html: `

            <div class="text-start">

                <div class="mb-3">

                    <label
                        class="form-label fw-semibold"
                    >
                        Service
                    </label>

                    <input
                        id="editHistoryService"
                        class="form-control"
                        value="${escapeHtml(
                            service || ''
                        )}"
                    >

                </div>


                <div class="mb-3">

                    <label
                        class="form-label fw-semibold"
                    >
                        Service Date
                    </label>

                    <input
                        id="editHistoryDate"
                        type="date"
                        class="form-control"
                        value="${escapeHtml(
                            serviceDate || ''
                        )}"
                    >

                </div>

            </div>

        `,

        showCancelButton:
            true,

        confirmButtonColor:
            '#b30000',

        confirmButtonText:
            'Save Changes',

        preConfirm: () => {

            const newService =
                document
                    .getElementById(
                        'editHistoryService'
                    )
                    ?.value
                    .trim();


            const newDate =
                document
                    .getElementById(
                        'editHistoryDate'
                    )
                    ?.value;


            if (!newService) {

                Swal.showValidationMessage(
                    'Service is required.'
                );

                return false;

            }


            if (!newDate) {

                Swal.showValidationMessage(
                    'Service date is required.'
                );

                return false;

            }


            return {

                id:
                    Number(id),

                service_type:
                    newService,

                service_date:
                    newDate

            };

        }

    })

    .then(result => {

        if (
            !result.isConfirmed ||
            !result.value
        ) {

            return;

        }


        postData(
            'api/admin/manage_service_history.php',
            {

                action:
                    'update',

                ...result.value

            },

            () => {

                loadServiceHistory();

            }
        );

    });

}


// =============================================================
// DELETE SERVICE HISTORY
// =============================================================

function deleteHistory(
    id,
    customerName
) {

    Swal.fire({

        title:
            'Delete Service History?',

        html:
            `
            Delete the service history
            for
            <strong>
                ${escapeHtml(
                    customerName ||
                    'this customer'
                )}
            </strong>?
            <br><br>
            <span class="text-danger">
                This action cannot be undone.
            </span>
            `,

        icon:
            'warning',

        showCancelButton:
            true,

        confirmButtonColor:
            '#dc3545',

        cancelButtonColor:
            '#6c757d',

        confirmButtonText:
            'Yes, Delete'

    })

    .then(result => {

        if (
            !result.isConfirmed
        ) {

            return;

        }


        postData(
            'api/admin/manage_service_history.php',
            {

                action:
                    'delete',

                id:
                    Number(id)

            },

            () => {

                loadServiceHistory();

            }
        );

    });

}


// =============================================================
// LOAD SERVICE HISTORY
// =============================================================

function loadServiceHistory() {

    fetch(
        'api/admin/manage_service_history.php?action=list',
        {

            credentials:
                'include',

            headers:
                getAuthHeaders()

        }
    )

    .then(response => {

        if (!response.ok) {

            throw new Error(
                `HTTP ${response.status}`
            );

        }


        return response.json();

    })

    .then(data => {

        if (
            data.status !==
            'success'
        ) {

            throw new Error(
                data.message ||
                'Unable to load service history.'
            );

        }


        historyData =
            Array.isArray(
                data.history
            )
                ? data.history
                : [];


        filterAndRenderHistory();

    })

    .catch(error => {

        console.error(
            'Service history loading error:',
            error
        );

    });

}


// =============================================================
// SEND RECEIPT TO USER
// =============================================================

function sendReceiptToUser(
    historyId,
    customerName
) {

    Swal.fire({

        title:
            'Send Receipt?',

        html:
            `
            Send the service receipt to
            <strong>
                ${escapeHtml(
                    customerName ||
                    'the customer'
                )}
            </strong>?
            `,

        icon:
            'question',

        showCancelButton:
            true,

        confirmButtonColor:
            '#198754',

        cancelButtonColor:
            '#6c757d',

        confirmButtonText:
            'Send Receipt'

    })

    .then(result => {

        if (
            !result.isConfirmed
        ) {

            return;

        }


        postData(
            'api/admin/send_receipt.php',
            {

                history_id:
                    Number(
                        historyId
                    )

            },

            () => {

                console.log(
                    'Receipt sent successfully.'
                );

            }
        );

    });

}


// =============================================================
// LOAD ALL BASIC ADMIN DATA
// =============================================================

function loadBasicAdminData() {

    loadUsers();

    loadVehicles();

    loadServiceHistory();

    loadAppointments();

    loadParts();

    loadInquiries();

    loadOrders();

    loadServices();

    loadMechanics();

}


// =============================================================
// REFRESH EVERYTHING
// =============================================================

function refreshDashboardData() {

    if (
        typeof loadFullSiteData ===
        'function'
    ) {

        loadFullSiteData();

    }
    else {

        loadBasicAdminData();

    }

}


// =============================================================
// PART 6 END
// =============================================================

// =============================================================
// PART 7 — DASHBOARD SEARCH, FILTERING & REFRESH
// =============================================================


// =============================================================
// GLOBAL SEARCH STATE
// =============================================================

if (typeof globalSearchTerm === 'undefined') {
    var globalSearchTerm = '';
}


// =============================================================
// SEARCH INPUT INITIALIZATION
// =============================================================

function initializeAdminSearch() {

    const searchInput =
        document.getElementById(
            'adminSearch'
        ) ||
        document.getElementById(
            'globalSearch'
        ) ||
        document.querySelector(
            '[data-admin-search]'
        );


    if (!searchInput) {
        return;
    }


    searchInput.addEventListener(
        'input',
        function () {

            globalSearchTerm =
                String(
                    this.value || ''
                )
                .trim()
                .toLowerCase();


            refreshVisibleTables();

        }
    );


    // Allow ESC to clear the search.

    searchInput.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key ===
                'Escape'
            ) {

                this.value = '';

                globalSearchTerm = '';

                refreshVisibleTables();

            }

        }
    );

}


// =============================================================
// CLEAR GLOBAL SEARCH
// =============================================================

function clearAdminSearch() {

    const searchInput =
        document.getElementById(
            'adminSearch'
        ) ||
        document.getElementById(
            'globalSearch'
        ) ||
        document.querySelector(
            '[data-admin-search]'
        );


    if (searchInput) {

        searchInput.value = '';

    }


    globalSearchTerm = '';


    refreshVisibleTables();

}


// =============================================================
// REFRESH VISIBLE TABLES
// =============================================================

function refreshVisibleTables() {

    try {

        if (
            typeof filterAndRenderAppointments ===
            'function'
        ) {

            filterAndRenderAppointments();

        }

    }
    catch (error) {

        console.warn(
            'Appointment filter refresh failed:',
            error
        );

    }


    try {

        if (
            typeof filterAndRenderOrders ===
            'function'
        ) {

            filterAndRenderOrders();

        }

    }
    catch (error) {

        console.warn(
            'Order filter refresh failed:',
            error
        );

    }


    try {

        if (
            typeof filterAndRenderServices ===
            'function'
        ) {

            filterAndRenderServices();

        }

    }
    catch (error) {

        console.warn(
            'Service filter refresh failed:',
            error
        );

    }


    try {

        if (
            typeof filterAndRenderMechanics ===
            'function'
        ) {

            filterAndRenderMechanics();

        }

    }
    catch (error) {

        console.warn(
            'Mechanic filter refresh failed:',
            error
        );

    }


    try {

        if (
            typeof filterAndRenderUsers ===
            'function'
        ) {

            filterAndRenderUsers();

        }

    }
    catch (error) {

        console.warn(
            'User filter refresh failed:',
            error
        );

    }


    try {

        if (
            typeof filterAndRenderVehicles ===
            'function'
        ) {

            filterAndRenderVehicles();

        }

    }
    catch (error) {

        console.warn(
            'Vehicle filter refresh failed:',
            error
        );

    }


    try {

        if (
            typeof filterAndRenderHistory ===
            'function'
        ) {

            filterAndRenderHistory();

        }

    }
    catch (error) {

        console.warn(
            'History filter refresh failed:',
            error
        );

    }


    try {

        if (
            typeof filterAndRenderInquiries ===
            'function'
        ) {

            filterAndRenderInquiries();

        }

    }
    catch (error) {

        console.warn(
            'Inquiry filter refresh failed:',
            error
        );

    }

}


// =============================================================
// USER FILTERING
// =============================================================

function filterAndRenderUsers() {

    const users =
        Array.isArray(
            masterData?.users
        )
            ? masterData.users
            : [];


    const filtered =
        users.filter(
            user => {

                const searchable = [

                    user.id,

                    user.full_name,

                    user.name,

                    user.email,

                    user.phone,

                    user.role,

                    user.role_name,

                    user.status

                ]

                .filter(Boolean)

                .join(' ')

                .toLowerCase();


                return (
                    !globalSearchTerm ||
                    searchable.includes(
                        globalSearchTerm
                    )
                );

            }
        );


    if (
        typeof renderUsers ===
        'function'
    ) {

        renderUsers(
            filtered
        );

        return;

    }


    renderUsersFallback(
        filtered
    );

}


// =============================================================
// USER FALLBACK RENDERER
// =============================================================

function renderUsersFallback(
    users
) {

    const tbody =
        document.getElementById(
            'usersTableBody'
        ) ||
        document.getElementById(
            'userTableBody'
        );


    if (!tbody) {
        return;
    }


    if (!users.length) {

        tbody.innerHTML =
            `
            <tr>

                <td
                    colspan="7"
                    class="
                        text-center
                        text-muted
                        py-4
                    "
                >

                    No users match your search.

                </td>

            </tr>
            `;

        return;

    }


    tbody.innerHTML =
        users.map(
            user => {

                const role =
                    user.role_name ||
                    user.role ||
                    getRoleName(
                        user.role_id
                    );


                return `

                    <tr>

                        <td>
                            ${Number(
                                user.id || 0
                            )}
                        </td>

                        <td class="fw-semibold">

                            ${escapeHtml(
                                user.full_name ||
                                user.name ||
                                '—'
                            )}

                        </td>

                        <td>

                            ${escapeHtml(
                                user.email ||
                                '—'
                            )}

                        </td>

                        <td>

                            ${escapeHtml(
                                user.phone ||
                                '—'
                            )}

                        </td>

                        <td>

                            ${escapeHtml(
                                role
                            )}

                        </td>

                        <td>

                            ${statusBadge(
                                user.status ||
                                'Active'
                            )}

                        </td>

                        <td>

                            <div
                                class="btn-group btn-group-sm"
                            >

                                <button
                                    class="
                                        btn
                                        btn-outline-dark
                                    "
                                    onclick="
                                        editUser(
                                            ${Number(
                                                user.id
                                            )}
                                        )
                                    "
                                    title="Edit User"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-pencil-square
                                        "
                                    ></i>

                                </button>


                                <button
                                    class="
                                        btn
                                        btn-outline-danger
                                    "
                                    onclick="
                                        deleteUser(
                                            ${Number(
                                                user.id
                                            )},
                                            '${String(
                                                user.full_name ||
                                                user.name ||
                                                ''
                                            ).replace(
                                                /'/g,
                                                "\\'"
                                            )}'
                                        )
                                    "
                                    title="Delete User"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-trash
                                        "
                                    ></i>

                                </button>

                            </div>

                        </td>

                    </tr>

                `;

            }
        ).join('');

}


// =============================================================
// VEHICLE FILTERING
// =============================================================

function filterAndRenderVehicles() {

    const vehicles =
        Array.isArray(
            masterData?.vehicles
        )
            ? masterData.vehicles
            : [];


    const filtered =
        vehicles.filter(
            vehicle => {

                const searchable = [

                    vehicle.id,

                    vehicle.user_id,

                    vehicle.owner_name,

                    vehicle.customer_name,

                    vehicle.brand,

                    vehicle.model,

                    vehicle.year_model,

                    vehicle.plate_number,

                    vehicle.license_plate,

                    vehicle.fuel_type,

                    vehicle.transmission

                ]

                .filter(Boolean)

                .join(' ')

                .toLowerCase();


                return (
                    !globalSearchTerm ||
                    searchable.includes(
                        globalSearchTerm
                    )
                );

            }
        );


    if (
        typeof renderVehicles ===
        'function'
    ) {

        renderVehicles(
            filtered
        );

        return;

    }


    renderVehiclesFallback(
        filtered
    );

}


// =============================================================
// VEHICLE FALLBACK RENDERER
// =============================================================

function renderVehiclesFallback(
    vehicles
) {

    const tbody =
        document.getElementById(
            'vehiclesTableBody'
        ) ||
        document.getElementById(
            'vehicleTableBody'
        );


    if (!tbody) {
        return;
    }


    if (!vehicles.length) {

        tbody.innerHTML =
            `
            <tr>

                <td
                    colspan="8"
                    class="
                        text-center
                        text-muted
                        py-4
                    "
                >

                    No vehicles match your search.

                </td>

            </tr>
            `;

        return;

    }


    tbody.innerHTML =
        vehicles.map(
            vehicle => {

                const owner =
                    vehicle.owner_name ||
                    vehicle.customer_name ||
                    '—';


                const plate =
                    vehicle.plate_number ||
                    vehicle.license_plate ||
                    '—';


                return `

                    <tr>

                        <td>

                            ${escapeHtml(
                                owner
                            )}

                        </td>

                        <td>

                            ${escapeHtml(
                                vehicle.brand ||
                                '—'
                            )}

                        </td>

                        <td>

                            ${escapeHtml(
                                vehicle.model ||
                                '—'
                            )}

                        </td>

                        <td>

                            ${escapeHtml(
                                vehicle.year_model ||
                                '—'
                            )}

                        </td>

                        <td>

                            <code>
                                ${escapeHtml(
                                    plate
                                )}
                            </code>

                        </td>

                        <td>

                            ${escapeHtml(
                                vehicle.fuel_type ||
                                '—'
                            )}

                        </td>

                        <td>

                            ${escapeHtml(
                                vehicle.transmission ||
                                '—'
                            )}

                        </td>

                        <td>

                            <div
                                class="btn-group btn-group-sm"
                            >

                                <button
                                    class="
                                        btn
                                        btn-outline-dark
                                    "
                                    onclick="
                                        editVehicle(
                                            ${Number(
                                                vehicle.id
                                            )},
                                            ${Number(
                                                vehicle.user_id ||
                                                0
                                            )},
                                            '${String(
                                                vehicle.brand ||
                                                ''
                                            ).replace(
                                                /'/g,
                                                "\\'"
                                            )}',
                                            '${String(
                                                vehicle.model ||
                                                ''
                                            ).replace(
                                                /'/g,
                                                "\\'"
                                            )}',
                                            '${String(
                                                vehicle.year_model ||
                                                ''
                                            ).replace(
                                                /'/g,
                                                "\\'"
                                            )}',
                                            '${String(
                                                plate
                                            ).replace(
                                                /'/g,
                                                "\\'"
                                            )}',
                                            '${String(
                                                vehicle.fuel_type ||
                                                ''
                                            ).replace(
                                                /'/g,
                                                "\\'"
                                            )}',
                                            '${String(
                                                vehicle.transmission ||
                                                ''
                                            ).replace(
                                                /'/g,
                                                "\\'"
                                            )}'
                                        )
                                    "
                                    title="Edit Vehicle"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-pencil-square
                                        "
                                    ></i>

                                </button>


                                <button
                                    class="
                                        btn
                                        btn-outline-danger
                                    "
                                    onclick="
                                        deleteVehicle(
                                            ${Number(
                                                vehicle.id
                                            )},
                                            '${String(
                                                plate
                                            ).replace(
                                                /'/g,
                                                "\\'"
                                            )}'
                                        )
                                    "
                                    title="Delete Vehicle"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-trash
                                        "
                                    ></i>

                                </button>

                            </div>

                        </td>

                    </tr>

                `;

            }
        ).join('');

}


// =============================================================
// SERVICE HISTORY FILTERING
// =============================================================

function filterAndRenderHistory() {

    const history =
        Array.isArray(
            historyData
        )
            ? historyData
            : [];


    const filtered =
        history.filter(
            record => {

                const searchable = [

                    record.id,

                    record.customer_name,

                    record.full_name,

                    record.email,

                    record.plate_number,

                    record.vehicle_plate,

                    record.brand,

                    record.model,

                    record.service_type,

                    record.service_name,

                    record.mechanic_name,

                    record.service_date,

                    record.status,

                    record.notes

                ]

                .filter(Boolean)

                .join(' ')

                .toLowerCase();


                return (
                    !globalSearchTerm ||
                    searchable.includes(
                        globalSearchTerm
                    )
                );

            }
        );


    if (
        typeof renderHistory ===
        'function'
    ) {

        renderHistory(
            filtered
        );

        return;

    }


    renderHistoryFallback(
        filtered
    );

}


// =============================================================
// SERVICE HISTORY FALLBACK
// =============================================================

function renderHistoryFallback(
    history
) {

    const tbody =
        document.getElementById(
            'historyTableBody'
        ) ||
        document.getElementById(
            'serviceHistoryTableBody'
        );


    if (!tbody) {
        return;
    }


    if (!history.length) {

        tbody.innerHTML =
            `
            <tr>

                <td
                    colspan="8"
                    class="
                        text-center
                        text-muted
                        py-4
                    "
                >

                    No service history matches your search.

                </td>

            </tr>
            `;

        return;

    }


    tbody.innerHTML =
        history.map(
            record => {

                const customer =
                    record.customer_name ||
                    record.full_name ||
                    '—';


                const plate =
                    record.plate_number ||
                    record.vehicle_plate ||
                    '—';


                const service =
                    record.service_type ||
                    record.service_name ||
                    '—';


                const date =
                    record.service_date ||
                    record.created_at ||
                    '';


                return `

                    <tr>

                        <td>

                            #${Number(
                                record.id || 0
                            )}

                        </td>

                        <td>

                            ${escapeHtml(
                                customer
                            )}

                        </td>

                        <td>

                            ${escapeHtml(
                                record.brand ||
                                '—'
                            )}

                            ${escapeHtml(
                                record.model ||
                                ''
                            )}

                            <div>

                                <code>
                                    ${escapeHtml(
                                        plate
                                    )}
                                </code>

                            </div>

                        </td>

                        <td>

                            ${escapeHtml(
                                service
                            )}

                        </td>

                        <td>

                            ${escapeHtml(
                                formatAppointmentDate(
                                    date
                                )
                            )}

                        </td>

                        <td>

                            ${escapeHtml(
                                record.mechanic_name ||
                                '—'
                            )}

                        </td>

                        <td>

                            ${statusBadge(
                                record.status ||
                                'Completed'
                            )}

                        </td>

                        <td>

                            <div
                                class="btn-group btn-group-sm"
                            >

                                <button
                                    class="
                                        btn
                                        btn-outline-dark
                                    "
                                    onclick="
                                        editHistory(
                                            ${Number(
                                                record.id
                                            )},
                                            '${String(
                                                service
                                            ).replace(
                                                /'/g,
                                                "\\'"
                                            )}',
                                            '${String(
                                                date
                                            ).substring(
                                                0,
                                                10
                                            )}'
                                        )
                                    "
                                    title="Edit"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-pencil-square
                                        "
                                    ></i>

                                </button>


                                <button
                                    class="
                                        btn
                                        btn-outline-danger
                                    "
                                    onclick="
                                        deleteHistory(
                                            ${Number(
                                                record.id
                                            )},
                                            '${String(
                                                customer
                                            ).replace(
                                                /'/g,
                                                "\\'"
                                            )}'
                                        )
                                    "
                                    title="Delete"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-trash
                                        "
                                    ></i>

                                </button>

                            </div>

                        </td>

                    </tr>

                `;

            }
        ).join('');

}


// =============================================================
// INQUIRY FILTERING
// =============================================================

function filterAndRenderInquiries() {

    const inquiries =
        Array.isArray(
            inquiriesData
        )
            ? inquiriesData
            : [];


    const filtered =
        inquiries.filter(
            inquiry => {

                const searchable = [

                    inquiry.id,

                    inquiry.name,

                    inquiry.full_name,

                    inquiry.email,

                    inquiry.phone,

                    inquiry.subject,

                    inquiry.message,

                    inquiry.status,

                    inquiry.type

                ]

                .filter(Boolean)

                .join(' ')

                .toLowerCase();


                return (
                    !globalSearchTerm ||
                    searchable.includes(
                        globalSearchTerm
                    )
                );

            }
        );


    if (
        typeof renderInquiries ===
        'function'
    ) {

        renderInquiries(
            filtered
        );

        return;

    }


    renderInquiriesFallback(
        filtered
    );

}


// =============================================================
// INQUIRY FALLBACK
// =============================================================

function renderInquiriesFallback(
    inquiries
) {

    const tbody =
        document.getElementById(
            'inquiriesTableBody'
        ) ||
        document.getElementById(
            'inquiryTableBody'
        );


    if (!tbody) {
        return;
    }


    if (!inquiries.length) {

        tbody.innerHTML =
            `
            <tr>

                <td
                    colspan="7"
                    class="
                        text-center
                        text-muted
                        py-4
                    "
                >

                    No inquiries match your search.

                </td>

            </tr>
            `;

        return;

    }


    tbody.innerHTML =
        inquiries.map(
            inquiry => {

                return `

                    <tr>

                        <td>

                            #${Number(
                                inquiry.id || 0
                            )}

                        </td>

                        <td class="fw-semibold">

                            ${escapeHtml(
                                inquiry.name ||
                                inquiry.full_name ||
                                '—'
                            )}

                        </td>

                        <td>

                            ${escapeHtml(
                                inquiry.email ||
                                '—'
                            )}

                        </td>

                        <td>

                            ${escapeHtml(
                                inquiry.subject ||
                                inquiry.type ||
                                'General Inquiry'
                            )}

                        </td>

                        <td
                            style="
                                max-width:280px;
                                white-space:nowrap;
                                overflow:hidden;
                                text-overflow:ellipsis;
                            "
                            title="${escapeHtml(
                                inquiry.message ||
                                ''
                            )}"
                        >

                            ${escapeHtml(
                                inquiry.message ||
                                '—'
                            )}

                        </td>

                        <td>

                            ${statusBadge(
                                inquiry.status ||
                                'Pending'
                            )}

                        </td>

                        <td>

                            ${
                                typeof viewInquiry ===
                                'function'

                                    ? `
                                        <button
                                            class="
                                                btn
                                                btn-sm
                                                btn-outline-dark
                                            "
                                            onclick="
                                                viewInquiry(
                                                    ${Number(
                                                        inquiry.id
                                                    )}
                                                )
                                            "
                                        >

                                            <i
                                                class="
                                                    bi
                                                    bi-eye
                                                "
                                            ></i>

                                        </button>
                                    `

                                    : ''
                            }

                        </td>

                    </tr>

                `;

            }
        ).join('');

}


// =============================================================
// ROLE NAME HELPER
// =============================================================

function getRoleName(
    roleId
) {

    const roles = {

        1:
            'Customer',

        2:
            'Admin',

        3:
            'Mechanic'

    };


    return (
        roles[
            Number(roleId)
        ] ||
        'User'
    );

}


// =============================================================
// APPOINTMENT NOTES HELPER
// =============================================================

function getAppointmentNotes(
    appointment
) {

    const notes =
        appointment.notes ||
        appointment.customer_notes ||
        '';


    if (!notes) {

        return `
            <span
                class="
                    text-muted
                    small
                "
            >
                —
            </span>
        `;

    }


    return `

        <span
            class="
                small
                d-inline-block
            "
            style="
                max-width:180px;
                white-space:nowrap;
                overflow:hidden;
                text-overflow:ellipsis;
            "
            title="${escapeHtml(
                notes
            )}"
        >

            ${escapeHtml(
                notes
            )}

        </span>

    `;

}


// =============================================================
// LOCKED APPOINTMENT CHECK
// =============================================================

function isAppointmentLocked(
    status
) {

    const normalized =
        appointmentStatusKey(
            status
        );


    return (

        normalized ===
        'Completed'

        ||

        normalized ===
        'Cancelled'

    );

}


// =============================================================
// GET ALLOWED APPOINTMENT STATUSES
// =============================================================

function getAllowedAppointmentStatuses(
    currentStatus
) {

    const normalized =
        appointmentStatusKey(
            currentStatus
        );


    return Array.isArray(
        APPOINTMENT_STATUS_FLOW[
            normalized
        ]
    )

        ? APPOINTMENT_STATUS_FLOW[
            normalized
        ]

        : [];

}


// =============================================================
// DASHBOARD DATA REFRESH
// =============================================================

function loadFullSiteData() {

    console.log(
        'Refreshing admin dashboard data...'
    );


    const loaders = [];


    if (
        typeof loadAppointments ===
        'function'
    ) {

        loaders.push(
            safeLoader(
                loadAppointments,
                'appointments'
            )
        );

    }


    if (
        typeof loadUsers ===
        'function'
    ) {

        loaders.push(
            safeLoader(
                loadUsers,
                'users'
            )
        );

    }


    if (
        typeof loadVehicles ===
        'function'
    ) {

        loaders.push(
            safeLoader(
                loadVehicles,
                'vehicles'
            )
        );

    }


    if (
        typeof loadOrders ===
        'function'
    ) {

        loaders.push(
            safeLoader(
                loadOrders,
                'orders'
            )
        );

    }


    if (
        typeof loadServices ===
        'function'
    ) {

        loaders.push(
            safeLoader(
                loadServices,
                'services'
            )
        );

    }


    if (
        typeof loadMechanics ===
        'function'
    ) {

        loaders.push(
            safeLoader(
                loadMechanics,
                'mechanics'
            )
        );

    }


    if (
        typeof loadServiceHistory ===
        'function'
    ) {

        loaders.push(
            safeLoader(
                loadServiceHistory,
                'service history'
            )
        );

    }


    if (
        typeof loadInquiries ===
        'function'
    ) {

        loaders.push(
            safeLoader(
                loadInquiries,
                'inquiries'
            )
        );

    }


    return Promise.allSettled(
        loaders
    );

}


// =============================================================
// SAFE LOADER
// =============================================================

function safeLoader(
    callback,
    name
) {

    return Promise.resolve()

        .then(
            () => callback()
        )

        .catch(
            error => {

                console.error(
                    `Failed to refresh ${name}:`,
                    error
                );

            }
        );

}


// =============================================================
// AUTO REFRESH
// =============================================================

let dashboardRefreshTimer =
    null;


function startDashboardAutoRefresh() {

    stopDashboardAutoRefresh();


    dashboardRefreshTimer =
        setInterval(
            function () {

                loadFullSiteData();

            },
            30000
        );

}


// =============================================================
// STOP AUTO REFRESH
// =============================================================

function stopDashboardAutoRefresh() {

    if (
        dashboardRefreshTimer
    ) {

        clearInterval(
            dashboardRefreshTimer
        );

        dashboardRefreshTimer =
            null;

    }

}


// =============================================================
// MANUAL REFRESH BUTTON
// =============================================================

function initializeRefreshButton() {

    const buttons =
        document.querySelectorAll(
            '[data-admin-refresh]'
        );


    buttons.forEach(
        button => {

            button.addEventListener(
                'click',
                function () {

                    const originalHTML =
                        this.innerHTML;


                    this.disabled =
                        true;


                    this.innerHTML =
                        `
                        <i
                            class="
                                bi
                                bi-arrow-repeat
                                spin
                            "
                        ></i>

                        Refreshing...
                        `;


                    Promise.resolve(
                        loadFullSiteData()
                    )

                    .finally(
                        () => {

                            this.disabled =
                                false;


                            this.innerHTML =
                                originalHTML;

                        }
                    );

                }
            );

        }
    );

}


// =============================================================
// FILTER DROPDOWN EVENTS
// =============================================================

function initializeAdminFilters() {

    const filterIds = [

        'appointmentStatusFilter',

        'appointmentMechanicFilter',

        'appointmentDateFilter',

        'ordersStatusFilter',

        'userRoleFilter',

        'userStatusFilter',

        'serviceStatusFilter',

        'mechanicStatusFilter'

    ];


    filterIds.forEach(
        id => {

            const element =
                document.getElementById(
                    id
                );


            if (!element) {
                return;
            }


            element.addEventListener(
                'change',
                function () {

                    refreshVisibleTables();

                }
            );

        }
    );

}


// =============================================================
// INITIALIZE ADMIN DASHBOARD
// =============================================================

function initializeAdminDashboard() {

    initializeAdminSearch();

    initializeAdminFilters();

    initializeRefreshButton();

    loadFullSiteData();

    startDashboardAutoRefresh();

}


// =============================================================
// CLEANUP WHEN LEAVING PAGE
// =============================================================

window.addEventListener(
    'beforeunload',
    function () {

        stopDashboardAutoRefresh();

    }
);


// =============================================================
// DOM READY
// =============================================================

if (
    document.readyState ===
    'loading'
) {

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            initializeAdminDashboard();

        }
    );

}
else {

    initializeAdminDashboard();

}


// =============================================================
// PART 7 END
// =============================================================

// =============================================================
// PART 8 — FINAL HELPERS & DASHBOARD INITIALIZATION
// =============================================================


// =============================================================
// HTML ESCAPE HELPER
// =============================================================

function escapeHtml(value) {

    if (
        value === null ||
        value === undefined
    ) {

        return '';

    }


    return String(value)
        .replace(
            /&/g,
            '&amp;'
        )
        .replace(
            /</g,
            '&lt;'
        )
        .replace(
            />/g,
            '&gt;'
        )
        .replace(
            /"/g,
            '&quot;'
        )
        .replace(
            /'/g,
            '&#039;'
        );

}


// =============================================================
// AUTHORIZATION HEADERS
// =============================================================

function getAuthHeaders() {

    const headers = {

        'Accept':
            'application/json',

        'Content-Type':
            'application/json'

    };


    try {

        const token =
            localStorage.getItem(
                'jwt_token'
            );


        if (token) {

            headers[
                'Authorization'
            ] =
                `Bearer ${token}`;

        }

    }
    catch (error) {

        console.warn(
            'Unable to read authentication token:',
            error
        );

    }


    return headers;

}


// =============================================================
// POST DATA HELPER
// =============================================================

function postData(
    url,
    data,
    onSuccess,
    onError
) {

    fetch(
        url,
        {

            method:
                'POST',

            credentials:
                'include',

            headers:
                getAuthHeaders(),

            body:
                JSON.stringify(
                    data
                )

        }
    )

    .then(
        async response => {

            let result;


            try {

                result =
                    await response.json();

            }
            catch (error) {

                throw new Error(
                    `Invalid server response (${response.status}).`
                );

            }


            if (!response.ok) {

                throw new Error(
                    result.message ||
                    `HTTP ${response.status}`
                );

            }


            return result;

        }
    )

    .then(
        result => {

            if (
                result.status &&
                result.status !==
                    'success'
            ) {

                throw new Error(
                    result.message ||
                    'The operation failed.'
                );

            }


            if (
                typeof onSuccess ===
                'function'
            ) {

                onSuccess(
                    result
                );

            }


            if (
                result.message
            ) {

                Swal.fire({

                    icon:
                        'success',

                    title:
                        'Success',

                    text:
                        result.message,

                    timer:
                        1800,

                    showConfirmButton:
                        false

                });

            }

        }
    )

    .catch(
        error => {

            console.error(
                'POST request error:',
                error
            );


            if (
                typeof onError ===
                'function'
            ) {

                onError(
                    error
                );

                return;

            }


            Swal.fire({

                icon:
                    'error',

                title:
                    'Operation Failed',

                text:
                    error.message ||
                    'Something went wrong.'

            });

        }
    );

}


// =============================================================
// GET DATA HELPER
// =============================================================

function getData(
    url
) {

    return fetch(
        url,
        {

            method:
                'GET',

            credentials:
                'include',

            headers:
                getAuthHeaders()

        }
    )

    .then(
        async response => {

            let result;


            try {

                result =
                    await response.json();

            }
            catch (error) {

                throw new Error(
                    `Invalid server response (${response.status}).`
                );

            }


            if (!response.ok) {

                throw new Error(
                    result.message ||
                    `HTTP ${response.status}`
                );

            }


            if (
                result.status &&
                result.status !==
                    'success'
            ) {

                throw new Error(
                    result.message ||
                    'Request failed.'
                );

            }


            return result;

        }
    );

}


// =============================================================
// DELETE DATA HELPER
// =============================================================

function deleteData(
    url,
    id,
    onSuccess
) {

    Swal.fire({

        title:
            'Are you sure?',

        text:
            'This action cannot be undone.',

        icon:
            'warning',

        showCancelButton:
            true,

        confirmButtonColor:
            '#dc3545',

        cancelButtonColor:
            '#6c757d',

        confirmButtonText:
            'Yes, Delete'

    })

    .then(
        result => {

            if (
                !result.isConfirmed
            ) {

                return;

            }


            fetch(
                url,
                {

                    method:
                        'POST',

                    credentials:
                        'include',

                    headers:
                        getAuthHeaders(),

                    body:
                        JSON.stringify({

                            action:
                                'delete',

                            id:
                                Number(id)

                        })

                }
            )

            .then(
                async response => {

                    let data;


                    try {

                        data =
                            await response.json();

                    }
                    catch (error) {

                        throw new Error(
                            'Invalid server response.'
                        );

                    }


                    if (
                        !response.ok ||
                        (
                            data.status &&
                            data.status !==
                                'success'
                        )
                    ) {

                        throw new Error(
                            data.message ||
                            'Delete failed.'
                        );

                    }


                    return data;

                }
            )

            .then(
                data => {

                    Swal.fire({

                        icon:
                            'success',

                        title:
                            'Deleted',

                        text:
                            data.message ||
                            'The record was deleted.',

                        timer:
                            1600,

                        showConfirmButton:
                            false

                    });


                    if (
                        typeof onSuccess ===
                        'function'
                    ) {

                        onSuccess(
                            data
                        );

                    }

                }
            )

            .catch(
                error => {

                    console.error(
                        'Delete error:',
                        error
                    );


                    Swal.fire({

                        icon:
                            'error',

                        title:
                            'Delete Failed',

                        text:
                            error.message ||
                            'Unable to delete the record.'

                    });

                }
            );

        }
    );

}


// =============================================================
// DATE HELPER
// =============================================================

function formatDate(
    value
) {

    if (!value) {

        return '—';

    }


    const date =
        new Date(
            value
        );


    if (
        Number.isNaN(
            date.getTime()
        )
    ) {

        return escapeHtml(
            value
        );

    }


    return date.toLocaleDateString(
        'en-US',
        {

            year:
                'numeric',

            month:
                'short',

            day:
                'numeric'

        }
    );

}


// =============================================================
// DATETIME HELPER
// =============================================================

function formatDateTime(
    value
) {

    if (!value) {

        return '—';

    }


    const date =
        new Date(
            value
        );


    if (
        Number.isNaN(
            date.getTime()
        )
    ) {

        return escapeHtml(
            value
        );

    }


    return date.toLocaleString(
        'en-US',
        {

            year:
                'numeric',

            month:
                'short',

            day:
                'numeric',

            hour:
                'numeric',

            minute:
                '2-digit'

        }
    );

}


// =============================================================
// NUMBER FORMATTER
// =============================================================

function formatNumber(
    value
) {

    const number =
        Number(
            value
        );


    if (
        Number.isNaN(
            number
        )
    ) {

        return '0';

    }


    return number.toLocaleString(
        'en-US'
    );

}


// =============================================================
// CURRENCY FORMATTER
// =============================================================

function formatCurrency(
    value
) {

    const number =
        Number(
            value
        );


    if (
        Number.isNaN(
            number
        )
    ) {

        return '₱0.00';

    }


    return number.toLocaleString(
        'en-PH',
        {

            style:
                'currency',

            currency:
                'PHP'

        }
    );

}


// =============================================================
// SAFE NUMBER
// =============================================================

function safeNumber(
    value,
    fallback = 0
) {

    const number =
        Number(
            value
        );


    return Number.isFinite(
        number
    )
        ? number
        : fallback;

}


// =============================================================
// SHOW LOADING
// =============================================================

function showTableLoading(
    elementId,
    colspan = 6
) {

    const element =
        document.getElementById(
            elementId
        );


    if (!element) {

        return;

    }


    element.innerHTML =
        `
        <tr>

            <td
                colspan="${Number(
                    colspan
                )}"
                class="
                    text-center
                    py-5
                "
            >

                <div
                    class="
                        spinner-border
                        text-danger
                    "
                    role="status"
                >

                    <span
                        class="visually-hidden"
                    >
                        Loading...
                    </span>

                </div>


                <div
                    class="
                        small
                        text-muted
                        mt-2
                    "
                >

                    Loading data...

                </div>

            </td>

        </tr>
        `;

}


// =============================================================
// SHOW ERROR
// =============================================================

function showTableError(
    elementId,
    message,
    colspan = 6
) {

    const element =
        document.getElementById(
            elementId
        );


    if (!element) {

        return;

    }


    element.innerHTML =
        `
        <tr>

            <td
                colspan="${Number(
                    colspan
                )}"
                class="
                    text-center
                    py-5
                    text-danger
                "
            >

                <i
                    class="
                        bi
                        bi-exclamation-triangle
                        fs-3
                        d-block
                        mb-2
                    "
                ></i>


                ${escapeHtml(
                    message ||
                    'Unable to load data.'
                )}

            </td>

        </tr>
        `;

}


// =============================================================
// EMPTY TABLE MESSAGE
// =============================================================

function showEmptyTable(
    elementId,
    message = 'No records found.',
    colspan = 6
) {

    const element =
        document.getElementById(
            elementId
        );


    if (!element) {

        return;

    }


    element.innerHTML =
        `
        <tr>

            <td
                colspan="${Number(
                    colspan
                )}"
                class="
                    text-center
                    text-muted
                    py-5
                "
            >

                <i
                    class="
                        bi
                        bi-inbox
                        fs-3
                        d-block
                        mb-2
                    "
                ></i>


                ${escapeHtml(
                    message
                )}

            </td>

        </tr>
        `;

}


// =============================================================
// CONFIRM LOGOUT
// =============================================================

function confirmLogout() {

    Swal.fire({

        title:
            'Logout?',

        text:
            'You will be signed out of the admin panel.',

        icon:
            'question',

        showCancelButton:
            true,

        confirmButtonColor:
            '#b30000',

        cancelButtonColor:
            '#6c757d',

        confirmButtonText:
            'Logout'

    })

    .then(
        result => {

            if (
                !result.isConfirmed
            ) {

                return;

            }


            performLogout();

        }
    );

}


// =============================================================
// LOGOUT
// =============================================================

function performLogout() {

    try {

        localStorage.removeItem(
            'jwt_token'
        );

        localStorage.removeItem(
            'user'
        );

        localStorage.removeItem(
            'admin_user'
        );

    }
    catch (error) {

        console.warn(
            'Unable to clear local storage:',
            error
        );

    }


    fetch(
        'api/logout.php',
        {

            method:
                'POST',

            credentials:
                'include',

            headers:
                getAuthHeaders()

        }
    )

    .catch(
        error => {

            console.warn(
                'Logout request failed:',
                error
            );

        }
    )

    .finally(
        () => {

            window.location.href =
                'account.php';

        }
    );

}


// =============================================================
// TAB HANDLER
// =============================================================

function switchAdminTab(
    tabName
) {

    if (!tabName) {

        return;

    }


    const tabButtons =
        document.querySelectorAll(
            '[data-admin-tab]'
        );


    tabButtons.forEach(
        button => {

            const isActive =
                button.dataset.adminTab ===
                tabName;


            button.classList.toggle(
                'active',
                isActive
            );

        }
    );


    const tabPanels =
        document.querySelectorAll(
            '[data-admin-panel]'
        );


    tabPanels.forEach(
        panel => {

            const isActive =
                panel.dataset.adminPanel ===
                tabName;


            panel.classList.toggle(
                'active',
                isActive
            );


            if (isActive) {

                panel.style.display =
                    '';

            }
            else {

                panel.style.display =
                    'none';

            }

        }
    );


    // Load data when a tab is opened.

    switch (
        String(tabName)
            .toLowerCase()
    ) {

        case 'appointments':

            if (
                typeof loadAppointments ===
                'function'
            ) {

                loadAppointments();

            }

            break;


        case 'users':

            if (
                typeof loadUsers ===
                'function'
            ) {

                loadUsers();

            }

            break;


        case 'vehicles':

            if (
                typeof loadVehicles ===
                'function'
            ) {

                loadVehicles();

            }

            break;


        case 'history':

        case 'service-history':

            if (
                typeof loadServiceHistory ===
                'function'
            ) {

                loadServiceHistory();

            }

            break;


        case 'mechanics':

            if (
                typeof loadMechanics ===
                'function'
            ) {

                loadMechanics();

            }

            break;


        case 'services':

            if (
                typeof loadServices ===
                'function'
            ) {

                loadServices();

            }

            break;


        case 'orders':

            if (
                typeof loadOrders ===
                'function'
            ) {

                loadOrders();

            }

            break;


        case 'inquiries':

            if (
                typeof loadInquiries ===
                'function'
            ) {

                loadInquiries();

            }

            break;

    }

}


// =============================================================
// INITIALIZE TAB BUTTONS
// =============================================================

function initializeAdminTabs() {

    const buttons =
        document.querySelectorAll(
            '[data-admin-tab]'
        );


    buttons.forEach(
        button => {

            button.addEventListener(
                'click',
                function (event) {

                    event.preventDefault();


                    switchAdminTab(
                        this.dataset.adminTab
                    );

                }
            );

        }
    );

}


// =============================================================
// KEYBOARD SHORTCUTS
// =============================================================

function initializeKeyboardShortcuts() {

    document.addEventListener(
        'keydown',
        function (event) {

            // Ctrl + K / Cmd + K
            // focuses admin search.

            if (
                (
                    event.ctrlKey ||
                    event.metaKey
                ) &&
                event.key.toLowerCase() ===
                    'k'
            ) {

                event.preventDefault();


                const search =
                    document.getElementById(
                        'adminSearch'
                    ) ||
                    document.getElementById(
                        'globalSearch'
                    );


                if (search) {

                    search.focus();

                    search.select();

                }

            }


            // ESC closes SweetAlert.

            if (
                event.key ===
                'Escape'
            ) {

                const popup =
                    Swal.getPopup();


                if (popup) {

                    Swal.close();

                }

            }

        }
    );

}


// =============================================================
// SEARCH EMPTY STATE
// =============================================================

function updateSearchEmptyState() {

    const search =
        globalSearchTerm;


    const clearButton =
        document.getElementById(
            'clearAdminSearch'
        );


    if (!clearButton) {

        return;

    }


    if (search) {

        clearButton.style.display =
            '';

    }
    else {

        clearButton.style.display =
            'none';

    }

}


// =============================================================
// SEARCH CLEAR BUTTON
// =============================================================

function initializeSearchClearButton() {

    const button =
        document.getElementById(
            'clearAdminSearch'
        );


    if (!button) {

        return;

    }


    button.addEventListener(
        'click',
        function () {

            clearAdminSearch();

            updateSearchEmptyState();

        }
    );

}


// =============================================================
// WATCH SEARCH INPUT
// =============================================================

function initializeSearchWatcher() {

    const search =
        document.getElementById(
            'adminSearch'
        ) ||
        document.getElementById(
            'globalSearch'
        );


    if (!search) {

        return;

    }


    search.addEventListener(
        'input',
        function () {

            updateSearchEmptyState();

        }
    );


    updateSearchEmptyState();

}


// =============================================================
// DASHBOARD HEARTBEAT
// =============================================================

function startDashboardHeartbeat() {

    window.adminDashboardAlive =
        true;


    document.addEventListener(
        'visibilitychange',
        function () {

            if (
                document.visibilityState ===
                'visible'
            ) {

                window.adminDashboardAlive =
                    true;

            }

        }
    );

}


// =============================================================
// STOP EVERYTHING
// =============================================================

function destroyAdminDashboard() {

    stopDashboardAutoRefresh();


    window.adminDashboardAlive =
        false;

}


// =============================================================
// FINAL INITIALIZATION
// =============================================================

function initializeAdminDashboardFinal() {

    try {

        initializeAdminTabs();

    }
    catch (error) {

        console.error(
            'Tab initialization error:',
            error
        );

    }


    try {

        initializeKeyboardShortcuts();

    }
    catch (error) {

        console.error(
            'Keyboard shortcut initialization error:',
            error
        );

    }


    try {

        initializeSearchClearButton();

    }
    catch (error) {

        console.error(
            'Search clear initialization error:',
            error
        );

    }


    try {

        initializeSearchWatcher();

    }
    catch (error) {

        console.error(
            'Search watcher initialization error:',
            error
        );

    }


    try {

        startDashboardHeartbeat();

    }
    catch (error) {

        console.error(
            'Dashboard heartbeat error:',
            error
        );

    }

}


// =============================================================
// RUN FINAL INITIALIZATION
// =============================================================

if (
    document.readyState ===
    'loading'
) {

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            initializeAdminDashboardFinal();

        }
    );

}
else {

    initializeAdminDashboardFinal();

}


// =============================================================
// GLOBAL EXPORTS
// =============================================================
//
// These make the functions available to inline
// onclick="..." handlers in admin_dashboard.php.
//

window.adminDashboard = {

    loadAppointments:
        loadAppointments,

    loadUsers:
        loadUsers,

    loadVehicles:
        loadVehicles,

    loadServiceHistory:
        loadServiceHistory,

    filterAndRenderAppointments:
        filterAndRenderAppointments,

    filterAndRenderUsers:
        filterAndRenderUsers,

    filterAndRenderVehicles:
        filterAndRenderVehicles,

    filterAndRenderHistory:
        filterAndRenderHistory,

    clearSearch:
        clearAdminSearch,

    refresh:
        loadFullSiteData,

    logout:
        confirmLogout

};


// =============================================================
// PART 8 END
// =============================================================