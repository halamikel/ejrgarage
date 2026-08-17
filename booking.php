<?php

$pageTitle = "Book Appointment - EJR Garage";

$extraJs = '
<script src="js/booking.js"></script>
<script src="js/ev-services.js"></script>
';

include 'includes/header.php';

?>

<script>
    const userLoggedIn =
        !!localStorage.getItem('jwt_token');
</script>


<!-- ============================================================
     APPOINTMENT HEADER
============================================================ -->

<section class="appointment-header text-center">

    <div class="container">

        <h1>
            Book Your Appointment
        </h1>

        <p>
            To provide our visitors with the best car service
        </p>

    </div>

</section>



<!-- ============================================================
     BOOKING STEPS
============================================================ -->

<section class="steps py-4">

    <div class="container">

        <div class="step-wrapper">

            <!-- STEP 1 -->

            <div class="step">
                1
            </div>

            <span class="step-label">
                Your Details
            </span>


            <div class="line"></div>


            <!-- STEP 2 -->

            <div class="step active">
                2
            </div>

            <span class="step-label">
                Select Service
            </span>


            <div class="line"></div>


            <!-- STEP 3 -->

            <div class="step">
                3
            </div>

            <span class="step-label">
                Confirm
            </span>

        </div>

    </div>

</section>



<!-- ============================================================
     SERVICES SECTION
============================================================ -->

<section class="services-section">

    <div class="container">


        <h4 class="mb-4 fw-bold">
            Select Your Service
        </h4>



        <!-- =====================================================
             ELECTRIC VEHICLE NOTICE
        ====================================================== -->

        <div
            id="electricVehicleNotice"
            class="alert alert-info mb-4"
            style="display:none;"
        >

            <i class="bi bi-lightning-charge-fill me-2"></i>

            <strong>
                Electric Vehicle Selected
            </strong>

            <br>

            <small>
                Some services are disabled because they are not
                applicable to electric vehicles.
            </small>

        </div>



        <!-- =====================================================
             SERVICE CARD
        ====================================================== -->

        <div class="service-card">


            <!-- =================================================
                 LOADING
            ================================================== -->

            <div
                id="servicesLoading"
                class="text-center text-muted py-4"
            >

                <div
                    class="spinner-border spinner-border-sm text-danger me-2"
                    role="status"
                ></div>

                Loading services...

            </div>



            <!-- =================================================
                 DYNAMIC SERVICES
            ================================================== -->

            <div
                id="servicesDynamicContainer"
                style="display:none;"
            ></div>



            <!-- =================================================
                 SERVICE INFORMATION
            ================================================== -->

            <div class="mt-3 text-muted small">

                <i class="bi bi-info-circle me-1"></i>

                You may select up to
                <strong>5 services</strong>
                per booking.

            </div>



            <!-- =================================================
                 NAVIGATION BUTTONS
            ================================================== -->

            <div
                class="d-flex justify-content-between align-items-center mt-4"
            >


                <!-- BACK -->

                <button
                    type="button"
                    class="btn btn-outline-secondary px-4 fw-bold"
                    onclick="window.location.href='booking-details.php'"
                >

                    <i class="bi bi-arrow-left me-1"></i>

                    Back to Details

                </button>



                <!-- NEXT -->

                <button
                    type="button"
                    class="btn btn-next px-5"
                >

                    Next

                    <i class="bi bi-arrow-right ms-1"></i>

                </button>

            </div>

            <!-- ============================================================
     MECHANIC SELECTION
     OPTIONAL
============================================================ -->

<div class="mechanic-selection mt-4">

    <h5 class="fw-bold mb-2">
        Choose a Mechanic
        <span class="text-muted fw-normal" style="font-size: 14px;">
            (Optional)
        </span>
    </h5>

    <p class="text-muted small mb-3">
        You can choose a preferred mechanic, or leave this blank
        and let EJR Garage assign one for you.
    </p>


    <div class="form-group">

        <label for="mechanicSelect">
            Preferred Mechanic
        </label>

        <select
            id="mechanicSelect"
            name="mechanic_id"
            class="form-control"
        >

            <option value="">
                No preferred mechanic
            </option>

        </select>

    </div>


    <!-- Loading message -->

    <div
        id="mechanicLoading"
        class="text-muted small mt-2"
        style="display: none;"
    >

        <span class="spinner-border spinner-border-sm me-1"></span>

        Loading available mechanics...

    </div>


    <!-- Error message -->

    <div
        id="mechanicError"
        class="alert alert-danger mt-2"
        style="display: none;"
    ></div>


    <small class="text-muted">
        <i class="bi bi-info-circle"></i>

        Only mechanics currently available for booking
        will appear here.
    </small>

</div>



            <!-- =================================================
                 NO SERVICE ERROR
            ================================================== -->

            <div
                class="mt-3"
                id="serviceError"
                style="display:none;"
            >

                <div
                    class="alert alert-danger py-2 mb-0"
                >

                    <i
                        class="bi bi-exclamation-circle me-1"
                    ></i>

                    Please select at least one service
                    before continuing.

                </div>

            </div>



            <!-- =================================================
                 SERVICE LIMIT ERROR
            ================================================== -->

            <div
                class="mt-3"
                id="serviceLimitError"
                style="display:none;"
            >

                <div
                    class="alert alert-warning py-2 mb-0"
                >

                    <i
                        class="bi bi-exclamation-triangle me-1"
                    ></i>

                    You can select up to 5 services only
                    per booking.

                </div>

            </div>


        </div>

    </div>

</section>



<!-- ============================================================
     LOAD SERVICES FROM DATABASE
============================================================ -->

<script>

(function () {

    const loadingEl =
        document.getElementById(
            'servicesLoading'
        );


    const containerEl =
        document.getElementById(
            'servicesDynamicContainer'
        );


    fetch('api/get_services.php')

        .then(function (response) {

            if (!response.ok) {

                throw new Error(
                    'Unable to load services'
                );

            }

            return response.json();

        })


        .then(function (data) {

            loadingEl.style.display =
                'none';


            containerEl.style.display =
                'block';


            // ==================================================
            // CHECK SERVICE DATA
            // ==================================================

            if (
                data.status !== 'success' ||
                !data.services ||
                !data.services.length
            ) {

                containerEl.innerHTML = `

                    <p class="text-muted">

                        No services available right now.
                        Please contact us directly.

                    </p>

                `;

                return;

            }



            // ==================================================
            // GROUP SERVICES BY CATEGORY
            // ==================================================

            const grouped = {};


            data.services.forEach(
                function (service) {

                    const category =
                        service.category ||
                        'Services';


                    if (!grouped[category]) {

                        grouped[category] = [];

                    }


                    grouped[category].push(
                        service
                    );

                }
            );



            // ==================================================
            // CATEGORY ORDER
            // ==================================================

            const categoryOrder =
                Object.keys(
                    grouped
                ).sort(
                    function (a, b) {

                        if (
                            a ===
                            'Popular Services'
                        ) {

                            return -1;

                        }


                        if (
                            b ===
                            'Popular Services'
                        ) {

                            return 1;

                        }


                        return a.localeCompare(b);

                    }
                );



            // ==================================================
            // BUILD HTML
            // ==================================================

            let html = '';


            categoryOrder.forEach(
                function (category, index) {


                    html += `

                        <h5
                            class="${
                                index > 0
                                    ? 'mt-5'
                                    : ''
                            } mb-3 fw-bold text-dark"
                        >

                            <i
                                class="bi bi-tools me-2 text-danger"
                            ></i>

                            ${escapeHtml(category)}

                        </h5>


                        <div class="row g-3">

                    `;



                    grouped[category].forEach(
                        function (service) {

                            const price =
                                parseFloat(
                                    service.price_from
                                ) || 0;


                            html += `

                                <div
                                    class="col-6 col-md-3"
                                >

                                    <div
                                        class="service-item"
                                        data-service-name="${escapeHtml(service.name)}"
                                        ${
                                            price > 0
                                                ? `data-price="${price}"`
                                                : ''
                                        }
                                    >

                                        ${escapeHtml(
                                            service.name
                                        )}

                                    </div>

                                </div>

                            `;

                        }
                    );



                    html += `

                        </div>

                    `;

                }
            );



            // ==================================================
            // DISPLAY SERVICES
            // ==================================================

            containerEl.innerHTML =
                html;



            // ==================================================
            // TELL booking.js / ev-services.js
            // THAT SERVICES ARE READY
            // ==================================================

            document.dispatchEvent(
                new CustomEvent(
                    'ejrServicesLoaded'
                )
            );

        })


        .catch(
            function (error) {

                console.error(
                    'Service loading error:',
                    error
                );


                loadingEl.innerHTML = `

                    <p class="text-danger">

                        Could not load services.
                        Please refresh the page.

                    </p>

                `;

            }
        );



    // ==========================================================
    // ESCAPE HTML
    // ==========================================================

    function escapeHtml(value) {

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

})();

// ============================================================
// MECHANIC SELECTION
// ============================================================

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const mechanicSelect =
            document.getElementById(
                "mechanicSelect"
            );

        const mechanicLoading =
            document.getElementById(
                "mechanicLoading"
            );

        const mechanicError =
            document.getElementById(
                "mechanicError"
            );


        if (!mechanicSelect) {
            return;
        }


        // ========================================================
        // LOAD AVAILABLE MECHANICS
        // ========================================================

        async function loadMechanics() {

            mechanicLoading.style.display =
                "block";

            mechanicError.style.display =
                "none";


            try {

                const response =
                    await fetch(
                        "api/get_mechanics.php"
                    );


                if (!response.ok) {

                    throw new Error(
                        "Failed to load mechanics."
                    );

                }


                const data =
                    await response.json();


                if (
                    data.status !==
                    "success"
                ) {

                    throw new Error(
                        data.message ||
                        "Failed to load mechanics."
                    );

                }


                // Clear existing options

                mechanicSelect.innerHTML = "";


                // Default option

                const defaultOption =
                    document.createElement(
                        "option"
                    );

                defaultOption.value =
                    "";

                defaultOption.textContent =
                    "No preferred mechanic";

                mechanicSelect.appendChild(
                    defaultOption
                );


                // ==================================================
                // ADD AVAILABLE MECHANICS
                // ==================================================

                if (
                    data.mechanics &&
                    data.mechanics.length > 0
                ) {

                    data.mechanics.forEach(
                        function (mechanic) {

                            const option =
                                document.createElement(
                                    "option"
                                );


                            option.value =
                                mechanic.id;


                            option.textContent =
                                mechanic.name;


                            if (
                                mechanic.specialty
                            ) {

                                option.textContent +=
                                    " - " +
                                    mechanic.specialty;

                            }


                            mechanicSelect.appendChild(
                                option
                            );

                        }
                    );

                }

                else {

                    const noMechanicsOption =
                        document.createElement(
                            "option"
                        );

                    noMechanicsOption.disabled =
                        true;

                    noMechanicsOption.textContent =
                        "No mechanics currently available";

                    mechanicSelect.appendChild(
                        noMechanicsOption
                    );

                }


                // ==================================================
                // RESTORE PREVIOUS SELECTION
                // ==================================================

                const savedMechanic =
                    localStorage.getItem(
                        "selectedMechanicId"
                    );


                if (
                    savedMechanic
                ) {

                    const mechanicExists =
                        Array.from(
                            mechanicSelect.options
                        ).some(
                            function (option) {

                                return (
                                    option.value ===
                                    savedMechanic
                                );

                            }
                        );


                    if (
                        mechanicExists
                    ) {

                        mechanicSelect.value =
                            savedMechanic;

                    }

                }

            }

            catch (error) {

                console.error(
                    "Mechanic loading error:",
                    error
                );


                mechanicError.textContent =
                    "Unable to load mechanics. You can continue without selecting a mechanic.";


                mechanicError.style.display =
                    "block";

            }

            finally {

                mechanicLoading.style.display =
                    "none";

            }

        }


        // ========================================================
        // MECHANIC SELECTION
        // ========================================================

        mechanicSelect.addEventListener(
            "change",
            function () {

                const mechanicId =
                    this.value;


                if (
                    mechanicId
                ) {

                    localStorage.setItem(
                        "selectedMechanicId",
                        mechanicId
                    );

                }

                else {

                    localStorage.removeItem(
                        "selectedMechanicId"
                    );

                }

            }
        );


        // ========================================================
        // INITIAL LOAD
        // ========================================================

        loadMechanics();

    }
);

</script>



<!-- ============================================================
     PAGE STYLES
============================================================ -->

<style>


/* ============================================================
   STEP LABEL
============================================================ */

.step-label {

    font-size: 13px;

    font-weight: 600;

    color: #5f6b7a;

}



/* ============================================================
   ELECTRIC VEHICLE NOTICE
============================================================ */

#electricVehicleNotice {

    border-left: 5px solid #0dcaf0;

}



/* ============================================================
   DISABLED ELECTRIC VEHICLE SERVICE
============================================================ */

.service-item.ev-disabled {

    background: #eeeeee !important;

    color: #999999 !important;

    border-color: #dddddd !important;

    cursor: not-allowed !important;

    opacity: 0.60;

    transform: none !important;

    box-shadow: none !important;

}



/* ============================================================
   DISABLED SERVICE HOVER
============================================================ */

.service-item.ev-disabled:hover {

    transform: none !important;

    box-shadow: none !important;

    cursor: not-allowed !important;

}



/* ============================================================
   EV DISABLED MESSAGE
============================================================ */

.ev-disabled-label {

    display: block;

    width: 100%;

    margin-top: 5px;

    font-size: 9px;

    line-height: 1.2;

    color: #888888;

    font-weight: 600;

}



/* ============================================================
   MOBILE
============================================================ */

@media (max-width: 576px) {


    .step-label {

        font-size: 11px;

    }


    .line {

        width: 30px !important;

    }


    .service-item {

        font-size: 13px;

        padding: 12px 8px;

        height: 84px !important;

        min-height: 84px !important;

        line-height: 1.3;

        display: flex !important;

        align-items: center !important;

        justify-content: center !important;

        text-align: center !important;

        flex-direction: column;

    }


    .appointment-header {

        padding: 40px 0;

    }


    .appointment-header h1 {

        font-size: 1.8rem;

    }


    .service-card {

        padding: 20px !important;

    }


    .ev-disabled-label {

        font-size: 9px;

    }

}

</style>



<?php

include 'includes/footer.php';

?>