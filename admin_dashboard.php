<?php
$pageTitle = "Admin Dashboard - EJR Garage";
$bodyClass = "ejr-admin-page";
include 'includes/header.php';
?>

<script>
// ── JWT-based admin gate ──────────────────────────────────────
// PHP session is never set in this app — auth is JWT in localStorage.
// Verify role client-side first; the real enforcement is on every
// admin API endpoint server-side (role_id = 2 check via JWT decode).
(function () {
    const token = localStorage.getItem('jwt_token');
    const user  = JSON.parse(localStorage.getItem('user') || '{}');

    if (!token || (user.role_id != 2 && user.id != 21)) {
        window.location.href = 'account.php';
    }
})();
</script>

<link rel="stylesheet" href="css/admin_dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>

<!-- ── HERO HEADER ─────────────────────────────────────────── -->
<div class="ejr-admin-header">
    <div class="ejr-header-glow"></div>

    <div class="container position-relative">

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

            <div class="d-flex align-items-center gap-3">

                <div class="ejr-avatar-shield">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>

                <div>

                    <div class="ejr-admin-label">
                        CONTROL CENTER
                    </div>

                    <h1 class="ejr-admin-title">
                        Admin Dashboard
                    </h1>

                    <p class="ejr-admin-sub">
                        EJR Garage — Site-Wide Management
                    </p>

                </div>

            </div>

            <a href="index.php" class="ejr-back-btn">
                <i class="bi bi-house-door-fill me-2"></i>
                Back to Site
            </a>

        </div>

    </div>
</div>


<div class="ejr-admin-body">

    <div class="container">


        <!-- ── STAT CARDS ──────────────────────────────────── -->

        <div class="row g-3 mb-5">

            <!-- APPOINTMENTS -->

            <div class="col-6 col-md-3">

                <div
                    class="ejr-stat-card"
                    style="--accent:#e62429;"
                >

                    <div
                        class="ejr-stat-icon"
                        style="
                            background:rgba(230,36,41,0.12);
                        "
                    >

                        <i
                            class="bi bi-calendar-check-fill"
                            style="color:#e62429;"
                        ></i>

                    </div>

                    <div
                        class="ejr-stat-num"
                        id="statTotal"
                    >
                        —
                    </div>

                    <div class="ejr-stat-label">
                        Total Appointments
                    </div>

                    <div
                        class="ejr-stat-bar"
                        style="background:#e62429;"
                    ></div>

                </div>

            </div>


            <!-- PENDING -->

            <div class="col-6 col-md-3">

                <div
                    class="ejr-stat-card"
                    style="--accent:#f59e0b;"
                >

                    <div
                        class="ejr-stat-icon"
                        style="
                            background:rgba(245,158,11,0.12);
                        "
                    >

                        <i
                            class="bi bi-hourglass-split"
                            style="color:#f59e0b;"
                        ></i>

                    </div>

                    <div
                        class="ejr-stat-num"
                        style="color:#f59e0b;"
                        id="statPending"
                    >
                        —
                    </div>

                    <div class="ejr-stat-label">
                        Pending
                    </div>

                    <div
                        class="ejr-stat-bar"
                        style="background:#f59e0b;"
                    ></div>

                </div>

            </div>


            <!-- USERS -->

            <div class="col-6 col-md-3">

                <div
                    class="ejr-stat-card"
                    style="--accent:#10b981;"
                >

                    <div
                        class="ejr-stat-icon"
                        style="
                            background:rgba(16,185,129,0.12);
                        "
                    >

                        <i
                            class="bi bi-people-fill"
                            style="color:#10b981;"
                        ></i>

                    </div>

                    <div
                        class="ejr-stat-num"
                        style="color:#10b981;"
                        id="statUsers"
                    >
                        —
                    </div>

                    <div class="ejr-stat-label">
                        Registered Users
                    </div>

                    <div
                        class="ejr-stat-bar"
                        style="background:#10b981;"
                    ></div>

                </div>

            </div>


            <!-- VEHICLES -->

            <div class="col-6 col-md-3">

                <div
                    class="ejr-stat-card"
                    style="--accent:#3b82f6;"
                >

                    <div
                        class="ejr-stat-icon"
                        style="
                            background:rgba(59,130,246,0.12);
                        "
                    >

                        <i
                            class="bi bi-truck-front-fill"
                            style="color:#3b82f6;"
                        ></i>

                    </div>

                    <div
                        class="ejr-stat-num"
                        style="color:#3b82f6;"
                        id="statVehicles"
                    >
                        —
                    </div>

                    <div class="ejr-stat-label">
                        Total Vehicles
                    </div>

                    <div
                        class="ejr-stat-bar"
                        style="background:#3b82f6;"
                    ></div>

                </div>

            </div>

        </div>


        <!-- ── SEARCH ──────────────────────────────────────── -->

        <div class="ejr-controls mb-4">

          <div class="ejr-search-wrap">

                <i class="bi bi-search ejr-search-icon"></i>

                <input
                    type="text"
                    id="adminSearch"
                    class="ejr-search-input"
                    placeholder="Search users, vehicles, appointments..."
                >

            </div>

        <!-- ── TAB NAVIGATION ─────────────────────────────── -->

        <div class="ejr-tab-nav mb-4">

            <!-- APPOINTMENTS -->

            <button
                class="ejr-tab-btn active"
                data-ejr-tab="tab-appointments"
            >

                <i class="bi bi-calendar-event"></i>

                <span>
                    Appointments
                </span>

                <span
                    class="ejr-tab-badge"
                    id="badgeAppt"
                >
                    0
                </span>

            </button>


            <!-- USERS -->

            <button
                class="ejr-tab-btn"
                data-ejr-tab="tab-users"
            >

                <i class="bi bi-people"></i>

                <span>
                    Users
                </span>

                <span
                    class="ejr-tab-badge"
                    id="badgeUsers"
                >
                    0
                </span>

            </button>


            <!-- VEHICLES -->

            <button
                class="ejr-tab-btn"
                data-ejr-tab="tab-vehicles"
            >

                <i class="bi bi-truck"></i>

                <span>
                    Vehicles
                </span>

                <span
                    class="ejr-tab-badge"
                    id="badgeVehs"
                >
                    0
                </span>

            </button>


            <!-- HISTORY -->

            <button
                class="ejr-tab-btn"
                data-ejr-tab="tab-history"
            >

                <i class="bi bi-clock-history"></i>

                <span>
                    History
                </span>

                <span
                    class="ejr-tab-badge"
                    id="badgeHistory"
                >
                    0
                </span>

            </button>


            <!--
                LIVE CHAT REMOVED

                The admin now uses the floating
                chat widget from includes/footer.php.
            -->


            <!-- PARTS -->

            <button
                class="ejr-tab-btn"
                data-ejr-tab="tab-parts"
            >

                <i class="bi bi-box-seam"></i>

                <span>
                    Parts
                </span>

                <span
                    class="ejr-tab-badge"
                    id="badgeParts"
                >
                    0
                </span>

            </button>


            <!-- PART INQUIRIES -->

            <button
                class="ejr-tab-btn"
                data-ejr-tab="tab-inquiries"
            >

                <i class="bi bi-envelope-open"></i>

                <span>
                    Part Inquiries
                </span>

                <span
                    class="ejr-tab-badge"
                    id="badgeInquiries"
                >
                    0
                </span>

            </button>


            <!-- ORDERS -->

            <button
                class="ejr-tab-btn"
                data-ejr-tab="tab-orders"
            >

                <i class="bi bi-bag-check"></i>

                <span>
                    Orders
                </span>

                <span
                    class="ejr-tab-badge"
                    id="badgeOrders"
                >
                    0
                </span>

            </button>


            <!-- BUSY DATES -->

            <button
                class="ejr-tab-btn"
                data-ejr-tab="tab-busy-dates"
            >

                <i class="bi bi-calendar-x"></i>

                <span>
                    Busy Dates
                </span>

                <span
                    class="ejr-tab-badge"
                    id="badgeBusyDates"
                >
                    0
                </span>

            </button>


            <!-- SERVICES -->

            <button
                class="ejr-tab-btn"
                data-ejr-tab="tab-services"
            >

                <i class="bi bi-tools"></i>

                <span>
                    Services
                </span>

            </button>


            <!-- MECHANICS -->

            <button
                class="ejr-tab-btn"
                data-ejr-tab="tab-mechanics"
            >

                <i class="bi bi-person-gear"></i>

                <span>
                    Mechanics
                </span>

            </button>

        </div>


        <!-- ── CUSTOM TAB SWITCHER ────────────────────────── -->

        <script>

        document
            .querySelectorAll(
                '.ejr-tab-btn[data-ejr-tab]'
            )
            .forEach(function(btn) {

                btn.addEventListener(
                    'click',
                    function() {

                        var targetId =
                            this.getAttribute(
                                'data-ejr-tab'
                            );


                        /*
                         * Update button states.
                         */

                        document
                            .querySelectorAll(
                                '.ejr-tab-btn[data-ejr-tab]'
                            )
                            .forEach(function(b) {

                                b.classList.remove(
                                    'active'
                                );

                            });


                        this.classList.add(
                            'active'
                        );


                        /*
                         * Hide all panes.
                         */

                        document
                            .querySelectorAll(
                                '.tab-content .tab-pane'
                            )
                            .forEach(function(pane) {

                                pane.classList.remove(
                                    'show',
                                    'active'
                                );

                                pane.style.display =
                                    'none';

                            });


                        /*
                         * Show selected pane.
                         */

                        var target =
                            document.getElementById(
                                targetId
                            );


                        if (target) {

                            target.classList.add(
                                'show',
                                'active'
                            );

                            target.style.display =
                                'block';

                        }

                    }
                );

            });

        </script>


        <!-- ── TAB CONTENT ───────────────────────────────── -->

        <div class="tab-content">


            <!-- ==================================================
                 APPOINTMENTS
            =================================================== -->

            <div
                class="tab-pane fade show active"
                id="tab-appointments"
            >

                <div class="ejr-card">

                    <div class="ejr-card-header">

                        <div class="ejr-card-title">

                            <span
                                class="ejr-card-icon"
                                style="
                                    background:rgba(230,36,41,0.1);
                                "
                            >

                                <i
                                    class="bi bi-calendar-check-fill text-danger"
                                ></i>

                            </span>

                            Service Appointments

                        </div>


                        <div class="d-flex gap-2">

                            <button
                                class="ejr-btn ejr-btn--outline"
                                onclick="exportCSV('appointments')"
                            >

                                <i class="bi bi-filetype-csv me-1"></i>

                                Export CSV

                            </button>

                            <button
                                class="ejr-btn ejr-btn--outline"
                                onclick="exportPDF('appointments')"
                            >

                                <i class="bi bi-filetype-pdf me-1"></i>

                                Export PDF

                            </button>

                        </div>

                    </div>


                    <div class="ejr-filter-bar mb-3">

                        <select
                            id="appointmentsStatusFilter"
                            class="ejr-filter-select"
                            onchange="applyAllFilters()"
                            aria-label="Filter appointments by status"
                        >

                            <option value="">
                                All Statuses
                            </option>

                            <option value="Pending">
                                Pending
                            </option>

                            <option value="Confirmed">
                                Confirmed
                            </option>

                            <option value="Completed">
                                Completed
                            </option>

                            <option value="Cancelled">
                                Cancelled
                            </option>

                        </select>

                        <div class="d-flex gap-2 flex-wrap align-items-center mt-2">
                            <label class="small text-muted fw-semibold mb-0">Date:</label>
                            <input type="month" id="appointmentsMonthFilter" class="ejr-filter-select" onchange="applyAllFilters()" aria-label="Filter appointments by month" title="Filter by month">
                            <input type="date" id="appointmentsFromDate" class="ejr-filter-select" onchange="applyAllFilters()" aria-label="Filter appointments from date" title="From date">
                            <span class="text-muted small">to</span>
                            <input type="date" id="appointmentsToDate" class="ejr-filter-select" onchange="applyAllFilters()" aria-label="Filter appointments to date" title="To date">
                            <button type="button" class="ejr-btn ejr-btn--outline btn-sm" onclick="clearDateFilters('appointments')" title="Clear date filters">
                                <i class="bi bi-x-circle me-1"></i>Clear Dates
                            </button>
                        </div>

                    </div>


                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>
                                        Customer
                                    </th>

                                    <th>
                                        Service
                                    </th>

                                    <th>
                                        Vehicle
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Mechanic
                                    </th>

                                    <th style="width:120px;">
                                        Notes
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="appointmentTableBody">

                                <tr>

                                    <td
                                        colspan="8"
                                        class="ejr-loading"
                                    >

                                        <div
                                            class="spinner-border spinner-border-sm text-danger"
                                        ></div>

                                        Loading...

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 USERS
            =================================================== -->

            <div
                class="tab-pane fade"
                id="tab-users"
            >

                <div class="ejr-card">

                    <div class="ejr-card-header">

                        <div class="ejr-card-title">

                            <span
                                class="ejr-card-icon"
                                style="
                                    background:rgba(16,185,129,0.1);
                                "
                            >

                                <i
                                    class="bi bi-people-fill"
                                    style="color:#10b981;"
                                ></i>

                            </span>

                            Registered Customers

                        </div>


                        <div class="d-flex gap-2 flex-wrap">

                            <button
                                class="ejr-btn ejr-btn--outline"
                                onclick="exportCSV('users')"
                                title="Export the currently filtered users"
                            >

                                <i class="bi bi-filetype-csv me-1"></i>

                                Export CSV

                            </button>

                            <button
                                class="ejr-btn ejr-btn--outline"
                                onclick="exportPDF('users')"
                                title="Export the currently filtered users"
                            >

                                <i class="bi bi-filetype-pdf me-1"></i>

                                Export PDF

                            </button>

                        </div>

                    </div>


                    <div class="ejr-filter-bar mb-3">

                        <select
                            id="usersRoleFilter"
                            class="ejr-filter-select"
                            onchange="applyAllFilters()"
                            aria-label="Filter users by role"
                        >

                            <option value="">
                                All Roles
                            </option>

                            <option value="1">
                                Customer
                            </option>

                            <option value="2">
                                Admin
                            </option>

                            <option value="3">
                                Mechanic
                            </option>

                        </select>

                        <div class="d-flex gap-2 flex-wrap align-items-center mt-2">
                            <label class="small text-muted fw-semibold mb-0">Date:</label>
                            <input type="month" id="usersMonthFilter" class="ejr-filter-select" onchange="applyAllFilters()" aria-label="Filter users by month" title="Filter by month">
                            <input type="date" id="usersFromDate" class="ejr-filter-select" onchange="applyAllFilters()" aria-label="Filter users from date" title="From date">
                            <span class="text-muted small">to</span>
                            <input type="date" id="usersToDate" class="ejr-filter-select" onchange="applyAllFilters()" aria-label="Filter users to date" title="To date">
                            <button type="button" class="ejr-btn ejr-btn--outline btn-sm" onclick="clearDateFilters('users')" title="Clear date filters">
                                <i class="bi bi-x-circle me-1"></i>Clear Dates
                            </button>
                        </div>

                    </div>


                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th>Actions</th>

                                </tr>

                            </thead>


                            <tbody id="userTableBody">

                                <tr>

                                    <td
                                        colspan="7"
                                        class="ejr-loading"
                                    >

                                        <div
                                            class="spinner-border spinner-border-sm text-danger"
                                        ></div>

                                        Loading...

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 VEHICLES
            =================================================== -->

            <div
                class="tab-pane fade"
                id="tab-vehicles"
            >

                <div class="ejr-card">

                    <div class="ejr-card-header">

                        <div class="ejr-card-title">

                            <span
                                class="ejr-card-icon"
                                style="
                                    background:rgba(59,130,246,0.1);
                                "
                            >

                                <i
                                    class="bi bi-truck-front-fill"
                                    style="color:#3b82f6;"
                                ></i>

                            </span>

                            Customer Vehicles

                        </div>


                    <div class="d-flex gap-2 flex-wrap">

                        <button class="ejr-btn ejr-btn--outline" onclick="exportCSV('vehicles')" title="Export the currently filtered vehicles">
                            <i class="bi bi-filetype-csv me-1"></i>
                            Export CSV
                        </button>

                        <button class="ejr-btn ejr-btn--outline" onclick="exportPDF('vehicles')" title="Export the currently filtered vehicles">
                            <i class="bi bi-filetype-pdf me-1"></i>
                            Export PDF
                        </button>

                    </div>

                    </div>

                    <div class="ejr-filter-bar mb-3">

                        <div class="d-flex gap-2 flex-wrap align-items-center mt-2">
                            <label class="small text-muted fw-semibold mb-0">Date:</label>
                            <input type="month" id="vehiclesMonthFilter" class="ejr-filter-select" onchange="applyAllFilters()" aria-label="Filter vehicles by month" title="Filter by month">
                            <input type="date" id="vehiclesFromDate" class="ejr-filter-select" onchange="applyAllFilters()" aria-label="Filter vehicles from date" title="From date">
                            <span class="text-muted small">to</span>
                            <input type="date" id="vehiclesToDate" class="ejr-filter-select" onchange="applyAllFilters()" aria-label="Filter vehicles to date" title="To date">
                            <button type="button" class="ejr-btn ejr-btn--outline btn-sm" onclick="clearDateFilters('vehicles')" title="Clear date filters">
                                <i class="bi bi-x-circle me-1"></i>Clear Dates
                            </button>
                        </div>

                    </div>


<div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>Owner</th>
                                    <th>Brand</th>
                                    <th>Model</th>
                                    <th>Year</th>
                                    <th>Plate Number</th>
                                    <th>Fuel</th>
                                    <th>Transmission</th>
                                    <th>Actions</th>

                                </tr>

                            </thead>


                            <tbody id="vehicleTableBody">

                                <tr>

                                    <td
                                        colspan="7"
                                        class="ejr-loading"
                                    >

                                        <div
                                            class="spinner-border spinner-border-sm text-danger"
                                        ></div>

                                        Loading...

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 SERVICE HISTORY
            =================================================== -->

            <div
                class="tab-pane fade"
                id="tab-history"
            >

                <div class="ejr-card">

                    <div class="ejr-card-header">

                        <div class="ejr-card-title">

                            <span
                                class="ejr-card-icon"
                                style="
                                    background:rgba(245,158,11,0.1);
                                "
                            >

                                <i
                                    class="bi bi-clock-history"
                                    style="color:#f59e0b;"
                                ></i>

                            </span>

                            Service History

                        </div>


                        <div class="d-flex gap-2 flex-wrap">

                            <button
                                class="ejr-btn ejr-btn--outline"
                                onclick="exportCSV('history')"
                                title="Export the currently filtered history"
                            >

                                <i class="bi bi-filetype-csv me-1"></i>

                                Export CSV

                            </button>

                            <button
                                class="ejr-btn ejr-btn--outline"
                                onclick="exportPDF('history')"
                                title="Export the currently filtered history"
                            >

                                <i class="bi bi-filetype-pdf me-1"></i>

                                Export PDF

                            </button>

                        </div>

                    </div>

                    <div class="ejr-filter-bar mb-3">

                        <div class="d-flex gap-2 flex-wrap align-items-center mt-2">
                            <label class="small text-muted fw-semibold mb-0">Date:</label>
                            <input type="month" id="historyMonthFilter" class="ejr-filter-select" onchange="applyAllFilters()" aria-label="Filter history by month" title="Filter by month">
                            <input type="date" id="historyFromDate" class="ejr-filter-select" onchange="applyAllFilters()" aria-label="Filter history from date" title="From date">
                            <span class="text-muted small">to</span>
                            <input type="date" id="historyToDate" class="ejr-filter-select" onchange="applyAllFilters()" aria-label="Filter history to date" title="To date">
                            <button type="button" class="ejr-btn ejr-btn--outline btn-sm" onclick="clearDateFilters('history')" title="Clear date filters">
                                <i class="bi bi-x-circle me-1"></i>Clear Dates
                            </button>
                        </div>

                    </div>

                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Vehicle</th>
                                    <th>Plate No.</th>
                                    <th>Date</th>
                                    <th>Actions</th>

                                </tr>

                            </thead>


                            <tbody id="historyTableBody">

                                <tr>

                                    <td
                                        colspan="6"
                                        class="ejr-loading"
                                    >

                                        <div
                                            class="spinner-border spinner-border-sm text-danger"
                                        ></div>

                                        Loading...

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>


                    <div
                        class="text-muted small mt-2"
                        id="historyCount"
                    ></div>

                </div>

            </div>


            <!-- ==================================================
                 LIVE CHAT REMOVED
                 
                 The admin's Live Chat is now handled entirely
                 by the floating widget in includes/footer.php.
            =================================================== -->


            <!-- ==================================================
                 PARTS
            =================================================== -->

            <div
                class="tab-pane fade"
                id="tab-parts"
            >

                <div class="ejr-card">

                    <div class="ejr-card-header">

                        <div class="ejr-card-title">

                            <span
                                class="ejr-card-icon"
                                style="
                                    background:rgba(230,36,41,0.1);
                                "
                            >

                                <i
                                    class="bi bi-box-seam-fill text-danger"
                                ></i>

                            </span>

                            Parts Inventory

                        </div>


                        <button
                            class="ejr-btn ejr-btn--red"
                            onclick="openAddPart()"
                        >

                            <i class="bi bi-plus-lg me-1"></i>

                            Add Part

                        </button>

                    </div>


                    <div class="ejr-filter-bar mb-3">

                        <select
                            id="partsCategoryFilter"
                            class="ejr-filter-select"
                            onchange="applyAllFilters()"
                            aria-label="Filter parts by category"
                        >

                            <option value="">
                                All Categories
                            </option>

                        </select>


                        <select
                            id="partsStockFilter"
                            class="ejr-filter-select"
                            onchange="applyAllFilters()"
                            aria-label="Filter parts by stock status"
                        >

                            <option value="">
                                All Stock
                            </option>

                            <option value="in">
                                In Stock
                            </option>

                            <option value="out">
                                Out of Stock
                            </option>

                        </select>

                    </div>


                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Brand</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Actions</th>

                                </tr>

                            </thead>


                            <tbody id="partsTableBody">

                                <tr>

                                    <td
                                        colspan="7"
                                        class="ejr-loading"
                                    >

                                        <div
                                            class="spinner-border spinner-border-sm text-danger"
                                        ></div>

                                        Loading...

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 INQUIRIES
            =================================================== -->

            <div
                class="tab-pane fade"
                id="tab-inquiries"
            >

                <div class="ejr-card">

                    <div class="ejr-card-header">

                        <div class="ejr-card-title">

                            <span
                                class="ejr-card-icon"
                                style="
                                    background:rgba(245,158,11,0.1);
                                "
                            >

                                <i
                                    class="bi bi-envelope-open-fill"
                                    style="color:#f59e0b;"
                                ></i>

                            </span>

                            Part Inquiries

                        </div>

                    </div>


                    <div class="ejr-filter-bar mb-3">

                        <select
                            id="inquiriesStatusFilter"
                            class="ejr-filter-select"
                            aria-label="Filter inquiries by status"
                            onchange="applyAllFilters()"
                        >

                            <option
                                value="pending"
                                selected
                            >
                                Pending Only
                            </option>

                            <option value="responded">
                                Responded Only
                            </option>

                            <option value="all">
                                All Inquiries
                            </option>

                        </select>

                    </div>


                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>Part</th>
                                    <th>Customer</th>
                                    <th>Contact</th>
                                    <th>Message</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>

                                </tr>

                            </thead>


                            <tbody id="inquiriesTableBody">

                                <tr>

                                    <td
                                        colspan="7"
                                        class="ejr-loading"
                                    >

                                        <div
                                            class="spinner-border spinner-border-sm text-danger"
                                        ></div>

                                        Loading...

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 ORDERS
            =================================================== -->

            <div
                class="tab-pane fade"
                id="tab-orders"
            >

                <div class="ejr-card">

                    <div class="ejr-card-header">

                        <div class="ejr-card-title">

                            <span
                                class="ejr-card-icon"
                                style="
                                    background:rgba(59,130,246,0.1);
                                "
                            >

                                <i
                                    class="bi bi-bag-check-fill"
                                    style="color:#3b82f6;"
                                ></i>

                            </span>

                            Parts Orders

                        </div>


                    <div class="d-flex gap-2 flex-wrap">

                        <button class="ejr-btn ejr-btn--outline" onclick="exportCSV('orders')" title="Export the currently filtered orders">
                            <i class="bi bi-filetype-csv me-1"></i>
                            Export CSV
                        </button>

                        <button class="ejr-btn ejr-btn--outline" onclick="exportPDF('orders')" title="Export the currently filtered orders">
                            <i class="bi bi-filetype-pdf me-1"></i>
                            Export PDF
                        </button>

                    </div>

                    </div>


<div class="ejr-filter-bar mb-3">

                        <select
                            id="ordersStatusFilter"
                            class="ejr-filter-select"
                            aria-label="Filter orders by status"
                            onchange="applyAllFilters()"
                        >

                            <option value="">
                                All Statuses
                            </option>

                            <option value="Pending">
                                Pending
                            </option>

                            <option value="Approved">
                                Approved
                            </option>

                            <option value="Shipped">
                                Shipped
                            </option>

                            <option value="Delivered">
                                Delivered
                            </option>

                            <option value="Completed">
                                Completed
                            </option>

                            <option value="Cancelled">
                                Cancelled
                            </option>

                        </select>

                    </div>

                    <div
                        class="mt-3 p-3 rounded-3"
                        style="
                            background:rgba(59,130,246,0.06);
                            border:1px solid rgba(59,130,246,0.16);
                        "
                    >

                        <div
                            class="d-flex align-items-center gap-2 mb-2"
                            style="color:#2563eb;"
                        >

                            <i class="bi bi-file-earmark-arrow-down-fill"></i>

                            <strong>Export Filters</strong>

                        </div>

                        <div class="small text-muted mb-3">
                            Choose a month or date range before exporting. The CSV and PDF exports will use the currently filtered orders.
                        </div>

                        <div class="d-flex gap-2 flex-wrap align-items-center">

                            <div class="d-flex align-items-center gap-2">

                                <i class="bi bi-calendar3 text-primary"></i>

                                <label
                                    for="ordersMonthFilter"
                                    class="small fw-semibold mb-0"
                                >
                                    Month
                                </label>

                                <input
                                    type="month"
                                    id="ordersMonthFilter"
                                    class="form-control form-control-sm"
                                    style="min-width:150px;"
                                    onchange="applyAllFilters()"
                                    aria-label="Filter orders by month"
                                    title="Filter by month"
                                >

                            </div>

                            <div class="d-flex align-items-center gap-2">

                                <label
                                    for="ordersFromDate"
                                    class="small fw-semibold mb-0"
                                >
                                    From
                                </label>

                                <input
                                    type="date"
                                    id="ordersFromDate"
                                    class="form-control form-control-sm"
                                    onchange="applyAllFilters()"
                                    aria-label="Filter orders from date"
                                    title="From date"
                                >

                            </div>

                            <div class="d-flex align-items-center gap-2">

                                <label
                                    for="ordersToDate"
                                    class="small fw-semibold mb-0"
                                >
                                    To
                                </label>

                                <input
                                    type="date"
                                    id="ordersToDate"
                                    class="form-control form-control-sm"
                                    onchange="applyAllFilters()"
                                    aria-label="Filter orders to date"
                                    title="To date"
                                >

                            </div>

                            <button
                                type="button"
                                class="ejr-btn ejr-btn--outline btn-sm"
                                onclick="clearDateFilters('orders')"
                                title="Clear order export date filters"
                            >
                                <i class="bi bi-arrow-counterclockwise me-1"></i>
                                Clear Filters
                            </button>

                        </div>

                    </div>


                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Contact</th>
                                    <th>Address</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>

                                </tr>

                            </thead>


                            <tbody id="ordersTableBody">

                                <tr>

                                    <td
                                        colspan="9"
                                        class="ejr-loading"
                                    >

                                        <div
                                            class="spinner-border spinner-border-sm text-danger"
                                        ></div>

                                        Loading...

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 BUSY DATES
            =================================================== -->

            <div
                class="tab-pane fade"
                id="tab-busy-dates"
            >

                <div class="ejr-card">

                    <div class="ejr-card-header">

                        <div class="ejr-card-title">

                            <span
                                class="ejr-card-icon"
                                style="
                                    background:rgba(230,36,41,0.1);
                                "
                            >

                                <i
                                    class="bi bi-calendar-x-fill text-danger"
                                ></i>

                            </span>

                            Busy Dates Management

                        </div>


                        <button
                            class="ejr-btn ejr-btn--red"
                            id="btnAddBusyDate"
                        >

                            <i class="bi bi-plus-lg me-1"></i>

                            Add Busy Date

                        </button>

                    </div>


                    <div class="ejr-filter-bar mb-3">

                        <select
                            id="busyDateFilter"
                            class="ejr-filter-select"
                            aria-label="Filter busy dates"
                            onchange="applyBusyFilter()"
                        >

                            <option value="">
                                All Dates
                            </option>

                            <option value="1">
                                Busy Only
                            </option>

                            <option value="0">
                                Available Only
                            </option>

                        </select>

                    </div>


                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Bookings</th>
                                    <th>Status</th>
                                    <th>Actions</th>

                                </tr>

                            </thead>


                            <tbody id="busyDatesTableBody">

                                <tr>

                                    <td
                                        colspan="5"
                                        class="ejr-loading"
                                    >

                                        <div
                                            class="spinner-border spinner-border-sm text-danger"
                                        ></div>

                                        Loading...

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 SERVICES
            =================================================== -->

            <div
                class="tab-pane fade"
                id="tab-services"
                style="display:none;"
            >

                <div class="ejr-card">

                    <div class="ejr-card-header">

                        <div class="ejr-card-title">

                            <span
                                class="ejr-card-icon"
                                style="
                                    background:rgba(230,36,41,0.1);
                                "
                            >

                                <i
                                    class="bi bi-tools text-danger"
                                ></i>

                            </span>

                            Automotive Services

                        </div>


                        <button
                            class="ejr-btn ejr-btn--red"
                            onclick="openAddService()"
                        >

                            <i class="bi bi-plus-lg me-1"></i>

                            Add Service

                        </button>

                    </div>


                    <div class="ejr-filter-bar mb-3">

                        <select
                            id="servicesCategoryFilter"
                            class="ejr-filter-select"
                            aria-label="Filter services by category"
                            onchange="applyAllFilters()"
                        >

                            <option value="">
                                All Categories
                            </option>

                        </select>


                        <select
                            id="servicesActiveFilter"
                            class="ejr-filter-select"
                            aria-label="Filter services by availability"
                            onchange="applyAllFilters()"
                        >

                            <option value="">
                                All
                            </option>

                            <option value="1">
                                Available Only
                            </option>

                            <option value="0">
                                Unavailable Only
                            </option>

                        </select>

                    </div>


                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>
                                        Service Name
                                    </th>

                                    <th>
                                        Category
                                    </th>

                                    <th>
                                        Details
                                    </th>

                                    <th>
                                        Available
                                    </th>

                                    <th>
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="servicesTableBody">

                                <tr>

                                    <td
                                        colspan="5"
                                        class="ejr-loading"
                                    >

                                        <div
                                            class="spinner-border spinner-border-sm text-danger"
                                        ></div>

                                        Loading...

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 MECHANICS
            =================================================== -->

            <div
                class="tab-pane fade"
                id="tab-mechanics"
                style="display:none;"
            >

                <!-- ===========================================================
                     LEAVE REQUESTS
                =========================================================== -->

                <div class="ejr-card mb-4">

                    <div class="ejr-card-header">

                        <div class="ejr-card-title">

                            <span
                                class="ejr-card-icon"
                                style="
                                    background:rgba(230,36,41,0.1);
                                "
                            >

                                <i
                                    class="bi bi-calendar2-week text-danger"
                                ></i>

                            </span>

                            Leave Requests

                            <span
                                id="leaveRequestsPendingBadge"
                                class="badge bg-warning text-dark ms-2"
                                style="display:none;"
                            ></span>

                        </div>

                    </div>


                    <div class="ejr-filter-bar mb-3">

                        <select
                            id="leaveRequestsStatusFilter"
                            class="ejr-filter-select"
                            aria-label="Filter leave requests by status"
                            onchange="applyAllFilters()"
                        >

                            <option value="pending" selected>
                                Pending Only
                            </option>

                            <option value="">
                                All
                            </option>

                            <option value="approved">
                                Approved Only
                            </option>

                            <option value="rejected">
                                Rejected Only
                            </option>

                        </select>

                    </div>


                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>Mechanic</th>
                                    <th>Dates</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Requested</th>
                                    <th>Actions</th>

                                </tr>

                            </thead>


                            <tbody id="leaveRequestsTableBody">

                                <tr>

                                    <td
                                        colspan="6"
                                        class="ejr-loading"
                                    >

                                        <div
                                            class="spinner-border spinner-border-sm text-danger"
                                        ></div>

                                        Loading...

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>


                <!-- ===========================================================
                     MECHANIC ROSTER
                =========================================================== -->

                <div class="ejr-card">

                    <div class="ejr-card-header">

                        <div class="ejr-card-title">

                            <span
                                class="ejr-card-icon"
                                style="
                                    background:rgba(230,36,41,0.1);
                                "
                            >

                                <i
                                    class="bi bi-person-gear text-danger"
                                ></i>

                            </span>

                            Mechanics / Technicians

                        </div>


                        <button
                            class="ejr-btn ejr-btn--red"
                            onclick="openAddMechanic()"
                        >

                            <i class="bi bi-plus-lg me-1"></i>

                            Add Mechanic

                        </button>

                    </div>


                    <div class="ejr-filter-bar mb-3">

                        <select
                            id="mechanicsStatusFilter"
                            aria-label="Filter mechanics by status"
                            class="ejr-filter-select"
                            onchange="applyAllFilters()"
                        >

                            <option value="">
                                All
                            </option>

                            <option value="available">
                                Available Only
                            </option>

                            <option value="on_break">
                                On Leave Only
                            </option>

                            <option value="on_duty">
                                Busy Only
                            </option>

                            <option value="unavailable">
                                Unavailable Only
                            </option>

                        </select>

                    </div>


                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>Name</th>
                                    <th>Specialty</th>
                                    <th>Availability</th>
                                    <th>Actions</th>

                                </tr>

                            </thead>


                            <tbody id="mechanicsTableBody">

                                <tr>

                                    <td
                                        colspan="4"
                                        class="ejr-loading"
                                    >

                                        <div
                                            class="spinner-border spinner-border-sm text-danger"
                                        ></div>

                                        Loading...

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- ============================================================
     ADMIN DASHBOARD JAVASCRIPT
============================================================ -->

<script src="js/admin_dashboard.js?v=20260713"></script>


<?php include 'includes/footer.php'; ?>