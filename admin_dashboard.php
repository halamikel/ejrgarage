<?php
$pageTitle = "Admin Dashboard - EJR Garage";
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


            <!-- LIVE CHAT REMOVED -->

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


                        <button
                            class="ejr-btn ejr-btn--outline"
                            onclick="exportCSV('appointments')"
                        >

                            <i class="bi bi-download me-1"></i>

                            Export CSV

                        </button>

                    </div>


                    <!-- APPOINTMENT FILTER -->

                    <div class="ejr-filter-bar mb-3">

                        <select
                            id="appointmentsStatusFilter"
                            class="ejr-filter-select"
                            onchange="applyAllFilters()"
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

                            <option value="In Progress">
                                In Progress
                            </option>

                            <option value="Completed">
                                Completed
                            </option>

                            <option value="Cancelled">
                                Cancelled
                            </option>

                        </select>

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

                                    <th>
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

                            Registered Users

                        </div>


                        <button
                            class="ejr-btn ejr-btn--outline"
                            onclick="exportCSV('users')"
                        >

                            <i class="bi bi-download me-1"></i>

                            Export CSV

                        </button>

                    </div>


                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>
                                        Name
                                    </th>

                                    <th>
                                        Email
                                    </th>

                                    <th>
                                        Phone
                                    </th>

                                    <th>
                                        Role
                                    </th>

                                    <th>
                                        Joined
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="usersTableBody">

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


                        <button
                            class="ejr-btn ejr-btn--outline"
                            onclick="exportCSV('vehicles')"
                        >

                            <i class="bi bi-download me-1"></i>

                            Export CSV

                        </button>

                    </div>


                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>
                                        Owner
                                    </th>

                                    <th>
                                        Brand
                                    </th>

                                    <th>
                                        Model
                                    </th>

                                    <th>
                                        Year
                                    </th>

                                    <th>
                                        Plate Number
                                    </th>

                                    <th>
                                        Fuel Type
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="vehiclesTableBody">

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

            </div>


            <!-- ==================================================
                 HISTORY
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
                                    background:rgba(107,114,128,0.1);
                                "
                            >

                                <i
                                    class="bi bi-clock-history"
                                    style="color:#6b7280;"
                                ></i>

                            </span>

                            Appointment History

                        </div>


                        <button
                            class="ejr-btn ejr-btn--outline"
                            onclick="exportCSV('history')"
                        >

                            <i class="bi bi-download me-1"></i>

                            Export CSV

                        </button>

                    </div>


                    <div class="ejr-filter-bar mb-3">

                        <div class="ejr-search-wrap">

                            <i class="bi bi-search ejr-search-icon"></i>

                            <input
                                type="text"
                                id="historySearch"
                                class="ejr-search-input"
                                placeholder="Search by customer or vehicle..."
                            >

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
                                        Vehicle
                                    </th>

                                    <th>
                                        Service
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Mechanic
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="historyTableBody">

                                <tr>

                                    <td
                                        colspan="6"
                                        class="ejr-loading"
                                    >

                                        Loading...

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


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
                                    background:rgba(245,158,11,0.1);
                                "
                            >

                                <i
                                    class="bi bi-box-seam"
                                    style="color:#f59e0b;"
                                ></i>

                            </span>

                            Parts Inventory

                        </div>


                        <button
                            class="ejr-btn ejr-btn--outline"
                            onclick="exportCSV('parts')"
                        >

                            <i class="bi bi-download me-1"></i>

                            Export CSV

                        </button>

                    </div>


                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>
                                        Part
                                    </th>

                                    <th>
                                        Category
                                    </th>

                                    <th>
                                        Price
                                    </th>

                                    <th>
                                        Stock
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="partsTableBody">

                                <tr>

                                    <td
                                        colspan="5"
                                        class="ejr-loading"
                                    >

                                        Loading...

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 PART INQUIRIES
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
                                    background:rgba(59,130,246,0.1);
                                "
                            >

                                <i
                                    class="bi bi-envelope-open"
                                    style="color:#3b82f6;"
                                ></i>

                            </span>

                            Part Inquiries

                        </div>


                        <button
                            class="ejr-btn ejr-btn--outline"
                            onclick="exportCSV('inquiries')"
                        >

                            <i class="bi bi-download me-1"></i>

                            Export CSV

                        </button>

                    </div>


                    <div class="ejr-filter-bar mb-3">

                        <select
                            id="inquiriesStatusFilter"
                            class="ejr-filter-select"
                            onchange="applyAllFilters()"
                        >

                            <option value="">
                                All Statuses
                            </option>

                            <option value="Pending">
                                Pending
                            </option>

                            <option value="Responded">
                                Responded
                            </option>

                            <option value="Closed">
                                Closed
                            </option>

                        </select>

                    </div>


                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>
                                        Customer
                                    </th>

                                    <th>
                                        Part
                                    </th>

                                    <th>
                                        Message
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="inquiriesTableBody">

                                <tr>

                                    <td
                                        colspan="6"
                                        class="ejr-loading"
                                    >

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
                                    background:rgba(16,185,129,0.1);
                                "
                            >

                                <i
                                    class="bi bi-bag-check"
                                    style="color:#10b981;"
                                ></i>

                            </span>

                            Orders

                        </div>


                        <button
                            class="ejr-btn ejr-btn--outline"
                            onclick="exportCSV('orders')"
                        >

                            <i class="bi bi-download me-1"></i>

                            Export CSV

                        </button>

                    </div>


                    <div class="ejr-filter-bar mb-3">

                        <select
                            id="ordersStatusFilter"
                            class="ejr-filter-select"
                            onchange="applyAllFilters()"
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

                            <option value="Processing">
                                Processing
                            </option>

                            <option value="Completed">
                                Completed
                            </option>

                            <option value="Cancelled">
                                Cancelled
                            </option>

                        </select>

                    </div>


                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>
                                        Order
                                    </th>

                                    <th>
                                        Customer
                                    </th>

                                    <th>
                                        Items
                                    </th>

                                    <th>
                                        Total
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="ordersTableBody">

                                <tr>

                                    <td
                                        colspan="7"
                                        class="ejr-loading"
                                    >

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
                                    class="bi bi-calendar-x"
                                    style="color:#e62429;"
                                ></i>

                            </span>

                            Busy Dates

                        </div>

                    </div>


                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Reason
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="busyDatesTableBody">

                                <tr>

                                    <td
                                        colspan="3"
                                        class="ejr-loading"
                                    >

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
                                    class="bi bi-tools"
                                    style="color:#e62429;"
                                ></i>

                            </span>

                            Services

                        </div>

                    </div>


                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>
                                        Service
                                    </th>

                                    <th>
                                        Category
                                    </th>

                                    <th>
                                        Price
                                    </th>

                                    <th>
                                        Duration
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="servicesTableBody">

                                <tr>

                                    <td
                                        colspan="6"
                                        class="ejr-loading"
                                    >

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
                                    class="bi bi-person-gear"
                                    style="color:#3b82f6;"
                                ></i>

                            </span>

                            Mechanics

                        </div>

                    </div>


                    <div class="ejr-filter-bar mb-3">

                        <select
                            id="mechanicsStatusFilter"
                            class="ejr-filter-select"
                            onchange="applyAllFilters()"
                        >

                            <option value="">
                                All Statuses
                            </option>

                            <option value="Active">
                                Active
                            </option>

                            <option value="Inactive">
                                Inactive
                            </option>

                        </select>

                    </div>


                    <div class="table-responsive">

                        <table class="ejr-table">

                            <thead>

                                <tr>

                                    <th>
                                        Name
                                    </th>

                                    <th>
                                        Specialty
                                    </th>

                                    <th>
                                        Phone
                                    </th>

                                    <th>
                                        Email
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="mechanicsTableBody">

                                <tr>

                                    <td
                                        colspan="6"
                                        class="ejr-loading"
                                    >

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
     REQUIRED LIBRARIES
============================================================ -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="/js/auth.js"></script>


<script>

/* ============================================================
   JWT SESSION WATCH
============================================================ */

if (localStorage.getItem('jwt_token')) {

    if (typeof startSessionWatch === 'function') {

        startSessionWatch();

    }

}


/* ============================================================
   ADMIN DASHBOARD SCRIPT
============================================================ */

document.addEventListener('DOMContentLoaded', function () {

    /*
     * Make sure the appointment tab is visible
     * when the dashboard first loads.
     */

    const appointmentsTab =
        document.getElementById('tab-appointments');

    if (appointmentsTab) {

        appointmentsTab.style.display = 'block';

    }


    /*
     * Global search.
     */

    const adminSearch =
        document.getElementById('adminSearch');

    if (adminSearch) {

        adminSearch.addEventListener(
            'input',
            function () {

                if (typeof applyAllFilters === 'function') {

                    applyAllFilters();

                }

            }
        );

    }


    /*
     * History search.
     */

    const historySearch =
        document.getElementById('historySearch');

    if (historySearch) {

        historySearch.addEventListener(
            'input',
            function () {

                if (typeof applyAllFilters === 'function') {

                    applyAllFilters();

                }

            }
        );

    }

});

</script>


<!-- ============================================================
     ADMIN DASHBOARD JAVASCRIPT
============================================================ -->

<script src="js/admin_dashboard.js"></script>


<?php
include 'includes/footer.php';
?>