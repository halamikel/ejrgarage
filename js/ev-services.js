document.addEventListener("DOMContentLoaded", function () {

    function applyElectricVehicleRules() {

        // Get vehicle information saved by booking-details.php
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

            return;
        }


        // Get fuel type
        const fuelType = String(
            vehicleDetails.fuel || ""
        ).trim().toLowerCase();


        // Check if vehicle is electric
        const isElectric = fuelType === "electric";


        // Services that should not be offered to EVs
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


        // Get all service buttons
        const services =
            document.querySelectorAll(
                ".service-item"
            );


        if (!services.length) {
            return;
        }


        // Electric vehicle notice
        const notice =
            document.getElementById(
                "electricVehicleNotice"
            );


        // Show/hide EV notice
        if (notice) {

            notice.style.display =
                isElectric
                    ? "block"
                    : "none";

        }


        services.forEach(function (service) {

            // Get the service name from data attribute
            // instead of textContent because the disabled
            // message can become part of the text.
            const serviceName =
                String(
                    service.dataset.serviceName ||
                    service.textContent
                ).trim();


            const normalizedName =
                serviceName.toLowerCase();


            const shouldDisable =
                isElectric &&
                electricDisabledServices.some(
                    function (disabledService) {

                        return (
                            disabledService
                                .toLowerCase() ===
                            normalizedName
                        );

                    }
                );


            if (shouldDisable) {

                // ------------------------------------------
                // DISABLE SERVICE
                // ------------------------------------------

                service.classList.add(
                    "ev-disabled"
                );


                service.dataset.evDisabled =
                    "true";


                // Remove selected state
                service.classList.remove(
                    "selected"
                );


                // Remove this service from localStorage
                removeSelectedService(
                    serviceName
                );


                // Add message
                if (
                    !service.querySelector(
                        ".ev-disabled-label"
                    )
                ) {

                    const label =
                        document.createElement(
                            "small"
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

                // ------------------------------------------
                // ENABLE SERVICE
                // ------------------------------------------

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

        });

    }


    // ========================================================
    // REMOVE SERVICE FROM SAVED SELECTION
    // ========================================================

    function removeSelectedService(
        serviceName
    ) {

        let selectedServices = [];


        try {

            selectedServices =
                JSON.parse(
                    localStorage.getItem(
                        "selectedServices"
                    ) || "[]"
                );

        } catch (error) {

            selectedServices = [];

        }


        if (!Array.isArray(selectedServices)) {
            selectedServices = [];
        }


        selectedServices =
            selectedServices.filter(
                function (service) {

                    return (
                        String(service)
                            .trim()
                            .toLowerCase() !==
                        String(serviceName)
                            .trim()
                            .toLowerCase()
                    );

                }
            );


        localStorage.setItem(
            "selectedServices",
            JSON.stringify(
                selectedServices
            )
        );

    }


    // ========================================================
    // BLOCK CLICKS ON DISABLED SERVICES
    // ========================================================

    document.addEventListener(
        "click",
        function (event) {

            const service =
                event.target.closest(
                    ".service-item"
                );


            if (!service) {
                return;
            }


            if (
                service.dataset.evDisabled ===
                "true"
            ) {

                event.preventDefault();
                event.stopPropagation();

            }

        },
        true
    );


    // ========================================================
    // SERVICES ARE LOADED DYNAMICALLY
    // ========================================================

    document.addEventListener(
        "ejrServicesLoaded",
        function () {

            applyElectricVehicleRules();

        }
    );


    // ========================================================
    // RUN ON PAGE LOAD
    // ========================================================

    applyElectricVehicleRules();

});