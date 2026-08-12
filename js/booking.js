document.addEventListener("DOMContentLoaded", function () {

    // ============================================================
    // GET VEHICLE DETAILS
    // ============================================================

    let vehicleDetails = {};

    try {

        vehicleDetails = JSON.parse(
            localStorage.getItem("vehicleDetails") || "{}"
        );

    } catch (error) {

        console.error(
            "Could not read vehicle details:",
            error
        );

        vehicleDetails = {};

    }


    // ============================================================
    // GET FUEL TYPE
    // ============================================================

    const fuelType =
        String(
            vehicleDetails.fuel || ""
        ).trim();


    const isElectricVehicle =
        fuelType.toLowerCase() === "electric";


    // ============================================================
    // SERVICES THAT ARE NOT APPLICABLE TO ELECTRIC VEHICLES
    // ============================================================

    const electricDisabledServices = [

        "Change Oil",

        "Oil Change",

        "Oil Filter Replacement",

        "Engine Oil Replacement",

        "Engine Tune-Up",

        "Engine Tuning",

        "Engine Repair",

        "Engine Overhaul",

        "Engine Maintenance",

        "Carburator Services",

        "Carburetor Services",

        "EFI",

        "Fuel Injection Service",

        "Fuel System Cleaning",

        "Decarbonizer",

        "Engine Flush",

        "Transmission Fluid Change",

        "Transmission Oil Change",

        "Auto Electrical Transmission"

    ];


    // ============================================================
    // SELECTED SERVICES
    // ============================================================

    let selectedServices = [];


    // ============================================================
    // LOAD PREVIOUSLY SELECTED SERVICES
    // ============================================================

    try {

        const savedServices =
            JSON.parse(
                localStorage.getItem(
                    "selectedServices"
                ) || "[]"
            );


        if (Array.isArray(savedServices)) {

            selectedServices =
                savedServices;

        }

    } catch (error) {

        console.error(
            "Could not load selected services:",
            error
        );

        selectedServices = [];

    }


    // ============================================================
    // INITIALIZE SERVICE SELECTION
    // ============================================================

    function initServiceSelection() {

        const services =
            document.querySelectorAll(
                ".service-item"
            );


        if (!services.length) {

            return;

        }


        // ========================================================
        // GET ELECTRIC VEHICLE NOTICE
        // ========================================================

        const electricVehicleNotice =
            document.getElementById(
                "electricVehicleNotice"
            );


        // ========================================================
        // CHECK IF SERVICE IS NOT AVAILABLE FOR ELECTRIC
        // ========================================================

        function isServiceDisabledForElectric(
            serviceName
        ) {

            if (!isElectricVehicle) {

                return false;

            }


            const normalizedName =
                String(serviceName)
                    .trim()
                    .toLowerCase();


            return electricDisabledServices.some(
                function (disabledService) {

                    return (
                        normalizedName ===
                        disabledService
                            .trim()
                            .toLowerCase()
                    );

                }
            );

        }


        // ========================================================
        // APPLY ELECTRIC VEHICLE RULES
        // ========================================================

        function applyElectricVehicleRules() {

            services.forEach(
                function (service) {

                    const serviceName =
                        (
                            service.dataset.serviceName ||
                            service.textContent
                        )
                        .trim();


                    const disabled =
                        isServiceDisabledForElectric(
                            serviceName
                        );


                    if (disabled) {

                        // ----------------------------------------
                        // MARK SERVICE AS DISABLED
                        // ----------------------------------------

                        service.classList.add(
                            "ev-disabled"
                        );


                        service.dataset.evDisabled =
                            "true";


                        // ----------------------------------------
                        // REMOVE FROM SELECTED SERVICES
                        // ----------------------------------------

                        selectedServices =
                            selectedServices.filter(
                                function (selected) {

                                    return (
                                        selected
                                            .trim()
                                            .toLowerCase() !==
                                        serviceName
                                            .trim()
                                            .toLowerCase()
                                    );

                                }
                            );


                        // ----------------------------------------
                        // REMOVE SELECTED STYLE
                        // ----------------------------------------

                        service.classList.remove(
                            "selected"
                        );


                        // ----------------------------------------
                        // ADD DISABLED MESSAGE
                        // ----------------------------------------

                        if (
                            !service.querySelector(
                                ".ev-disabled-label"
                            )
                        ) {

                            const label =
                                document.createElement(
                                    "span"
                                );


                            label.className =
                                "ev-disabled-label";


                            label.textContent =
                                "Not available for electric vehicles";


                            service.appendChild(
                                label
                            );

                        }

                    } else {

                        // ----------------------------------------
                        // ENABLE SERVICE
                        // ----------------------------------------

                        service.classList.remove(
                            "ev-disabled"
                        );


                        service.dataset.evDisabled =
                            "false";


                        const label =
                            service.querySelector(
                                ".ev-disabled-label"
                            );


                        if (label) {

                            label.remove();

                        }

                    }

                }
            );


            // ====================================================
            // ELECTRIC VEHICLE NOTICE
            // ====================================================

            if (electricVehicleNotice) {

                if (isElectricVehicle) {

                    electricVehicleNotice.style.display =
                        "block";

                } else {

                    electricVehicleNotice.style.display =
                        "none";

                }

            }


            // ====================================================
            // SAVE UPDATED SERVICES
            // ====================================================

            saveSelectedServices();

        }


        // ========================================================
        // RESTORE SELECTED SERVICES
        // ========================================================

        services.forEach(
            function (service) {

                const serviceName =
                    (
                        service.dataset.serviceName ||
                        service.textContent
                    )
                    .trim();


                const disabled =
                    isServiceDisabledForElectric(
                        serviceName
                    );


                if (disabled) {

                    return;

                }


                const wasSelected =
                    selectedServices.some(
                        function (selected) {

                            return (
                                selected
                                    .trim()
                                    .toLowerCase() ===
                                serviceName
                                    .trim()
                                    .toLowerCase()
                            );

                        }
                    );


                if (wasSelected) {

                    service.classList.add(
                        "selected"
                    );

                }

            }
        );


        // ========================================================
        // SERVICE CLICK EVENTS
        // ========================================================

        services.forEach(
            function (service) {

                service.addEventListener(
                    "click",
                    function () {

                        // ----------------------------------------
                        // DO NOT ALLOW DISABLED SERVICES
                        // ----------------------------------------

                        if (
                            this.dataset.evDisabled ===
                            "true"
                        ) {

                            return;

                        }


                        const serviceName =
                            (
                                this.dataset.serviceName ||
                                this.textContent
                            )
                            .trim();


                        // ----------------------------------------
                        // CHECK IF ALREADY SELECTED
                        // ----------------------------------------

                        const selectedIndex =
                            selectedServices.findIndex(
                                function (selected) {

                                    return (
                                        selected
                                            .trim()
                                            .toLowerCase() ===
                                        serviceName
                                            .trim()
                                            .toLowerCase()
                                    );

                                }
                            );


                        // ----------------------------------------
                        // REMOVE SERVICE
                        // ----------------------------------------

                        if (
                            selectedIndex !== -1
                        ) {

                            selectedServices.splice(
                                selectedIndex,
                                1
                            );


                            this.classList.remove(
                                "selected"
                            );


                            saveSelectedServices();


                            hideErrors();


                            return;

                        }


                        // ----------------------------------------
                        // MAXIMUM OF 5 SERVICES
                        // ----------------------------------------

                        if (
                            selectedServices.length >= 5
                        ) {

                            showLimitError();

                            return;

                        }


                        // ----------------------------------------
                        // ADD SERVICE
                        // ----------------------------------------

                        selectedServices.push(
                            serviceName
                        );


                        this.classList.add(
                            "selected"
                        );


                        saveSelectedServices();


                        hideErrors();

                    }
                );

            }
        );


        // ========================================================
        // APPLY EV RULES
        // ========================================================

        applyElectricVehicleRules();


        // ========================================================
        // NEXT BUTTON
        // ========================================================

        const nextButton =
            document.querySelector(
                ".btn-next"
            );


        if (nextButton) {

            nextButton.addEventListener(
                "click",
                function () {

                    // --------------------------------------------
                    // HIDE OLD ERRORS
                    // --------------------------------------------

                    hideErrors();


                    // --------------------------------------------
                    // CHECK SERVICE SELECTION
                    // --------------------------------------------

                    if (
                        selectedServices.length === 0
                    ) {

                        showServiceError();

                        return;

                    }


                    // --------------------------------------------
                    // SAVE SERVICES
                    // --------------------------------------------

                    saveSelectedServices();


                    // --------------------------------------------
                    // GO TO CONFIRMATION
                    // --------------------------------------------

                    window.location.href =
                        "booking-confirmation.php";

                }
            );

        }

    }


    // ============================================================
    // SAVE SELECTED SERVICES
    // ============================================================

    function saveSelectedServices() {

        localStorage.setItem(
            "selectedServices",
            JSON.stringify(
                selectedServices
            )
        );

    }


    // ============================================================
    // SHOW SERVICE ERROR
    // ============================================================

    function showServiceError() {

        const error =
            document.getElementById(
                "serviceError"
            );


        if (error) {

            error.style.display =
                "block";

        }

    }


    // ============================================================
    // SHOW SERVICE LIMIT ERROR
    // ============================================================

    function showLimitError() {

        const error =
            document.getElementById(
                "serviceLimitError"
            );


        if (error) {

            error.style.display =
                "block";

        }

    }


    // ============================================================
    // HIDE ERRORS
    // ============================================================

    function hideErrors() {

        const serviceError =
            document.getElementById(
                "serviceError"
            );


        const limitError =
            document.getElementById(
                "serviceLimitError"
            );


        if (serviceError) {

            serviceError.style.display =
                "none";

        }


        if (limitError) {

            limitError.style.display =
                "none";

        }

    }


    // ============================================================
    // SERVICES LOADED EVENT
    // ============================================================

    document.addEventListener(
        "ejrServicesLoaded",
        function () {

            initServiceSelection();

        }
    );


    // ============================================================
    // INITIALIZE IF SERVICES ALREADY EXIST
    // ============================================================

    if (
        document.querySelectorAll(
            ".service-item"
        ).length > 0
    ) {

        initServiceSelection();

    }

});