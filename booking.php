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
     SERVICE DETAILS MODAL
============================================================ -->

<div
    id="serviceDetailsModal"
    class="service-details-modal"
    style="display:none;"
>

    <div class="service-details-modal-content">

        <button
            type="button"
            class="service-details-close"
            aria-label="Close"
            onclick="window.closeServiceDetails();"
        >
            &times;
        </button>

        <div class="service-details-icon">
            <i class="bi bi-tools"></i>
        </div>

        <h3 id="serviceDetailsName">Service Name</h3>

        <div id="serviceDetailsPrice" class="service-details-price" style="display:none;"></div>

        <p id="serviceDetailsDescription" class="service-details-desc"></p>

        <button
            type="button"
            id="serviceDetailsSelectBtn"
            class="btn btn-next w-100 mt-2"
            onclick="window.toggleServiceFromModal();"
        >
            Select This Service
        </button>

    </div>

</div>



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

                            const description =
                                (service.description || '').trim();

                            const isUnavailable =
                                String(service.is_active) === '0';

                            html += `

                                <div
                                    class="col-6 col-md-3"
                                >

                                    <div
                                        class="service-item${isUnavailable ? ' service-unavailable' : ''}"
                                        data-service-name="${escapeHtml(service.name)}"
                                        data-description="${escapeHtml(description)}"
                                        ${
                                            price > 0
                                                ? `data-price="${price}"`
                                                : ''
                                        }
                                        ${
                                            isUnavailable
                                                ? 'data-admin-unavailable="true" aria-disabled="true"'
                                                : ''
                                        }
                                    >

                                        <button
                                            type="button"
                                            class="service-info-btn"
                                            aria-label="View details for ${escapeHtml(service.name)}"
                                            onclick="event.stopPropagation(); window.showServiceDetails(this.closest('.service-item'));"
                                        >
                                            <i class="bi bi-info-circle"></i>
                                        </button>

                                        <span class="service-name-text">${escapeHtml(
                                            service.name
                                        )}</span>

                                        ${
                                            isUnavailable
                                                ? '<span class="service-unavailable-badge">Temporarily Unavailable</span>'
                                                : ''
                                        }

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
   SERVICE ITEM — INFO BUTTON
============================================================ */

.service-item {

    position: relative;

}

.service-info-btn {

    position: absolute;

    top: 6px;

    right: 6px;

    width: 24px;

    height: 24px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: rgba(0, 0, 0, 0.06);

    border: none;

    border-radius: 50%;

    color: #5f6b7a;

    font-size: 14px;

    line-height: 1;

    cursor: pointer;

    z-index: 2;

    transition: background 0.2s, color 0.2s;

}

.service-info-btn:hover {

    background: #e62429;

    color: #ffffff;

}

.service-item.ev-disabled .service-info-btn,
.service-item.vehicle-disabled .service-info-btn {

    pointer-events: none;

    opacity: 0.5;

}



/* ============================================================
   SERVICE ITEM — TEMPORARILY UNAVAILABLE (admin-disabled)
============================================================ */

.service-item.service-unavailable {

    cursor: not-allowed;

    background: #f4f6f8;

    border-color: var(--border-light);

    color: #9aa4af;

    opacity: 0.7;

    padding-bottom: 28px;

}

.service-item.service-unavailable:hover {

    background: #f4f6f8;

    border-color: var(--border-light);

}

.service-item.service-unavailable .service-name-text {

    color: #9aa4af;

}

.service-unavailable-badge {

    position: absolute;

    left: 0;

    right: 0;

    bottom: 6px;

    display: block;

    font-size: 10px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 0.4px;

    color: #b3542d;

}



/* ============================================================
   SERVICE DETAILS MODAL
============================================================ */

.service-details-modal {

    position: fixed;

    z-index: 10000;

    left: 0;

    top: 0;

    width: 100%;

    height: 100%;

    background: rgba(0, 0, 0, 0.65);

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 20px;

}

.service-details-modal-content {

    background: #ffffff;

    border-radius: 16px;

    width: 100%;

    max-width: 420px;

    padding: 34px 28px 28px;

    position: relative;

    text-align: center;

    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);

    border: 1px solid #e3e8ef;

}

.service-details-close {

    position: absolute;

    top: 12px;

    right: 16px;

    background: none;

    border: none;

    font-size: 26px;

    line-height: 1;

    color: #a0aab5;

    cursor: pointer;

}

.service-details-icon {

    width: 64px;

    height: 64px;

    border-radius: 50%;

    background: rgba(230, 36, 41, 0.1);

    color: #e62429;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: 0 auto 16px;

    font-size: 28px;

}

#serviceDetailsName {

    font-family: 'Rajdhani', sans-serif;

    font-weight: 700;

    color: #1e2a36;

    font-size: 1.4rem;

    margin-bottom: 8px;

    text-transform: uppercase;

}

.service-details-price {

    display: inline-block;

    background: rgba(230, 36, 41, 0.08);

    color: #e62429;

    font-weight: 700;

    font-size: 0.95rem;

    padding: 4px 14px;

    border-radius: 20px;

    margin-bottom: 14px;

}

.service-details-desc {

    color: #5f6b7a;

    font-size: 0.92rem;

    line-height: 1.6;

    margin-bottom: 18px;

}

#serviceDetailsSelectBtn.is-selected {

    background: #ffffff !important;

    color: #e62429 !important;

    border: 2px solid #e62429 !important;

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


    .service-info-btn {

        width: 20px;

        height: 20px;

        font-size: 12px;

        top: 4px;

        right: 4px;

    }


    .service-details-modal-content {

        padding: 28px 20px 22px;

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

<script src="js/efi-carb-exclusivity.js"></script>



<?php

include 'includes/footer.php';

?>