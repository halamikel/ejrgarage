document.addEventListener("DOMContentLoaded", function () {

    // ============================================================
    // VEHICLE SERVICE RESTRICTIONS
    // ============================================================
    //
    // ELECTRIC:
    //   - Engine-related services are disabled.
    //
    // HYBRID:
    //   - Carburator Services are disabled.
    //
    // Disabled services become:
    //   - Gray
    //   - Unclickable
    //   - Unselectable
    //
    // ============================================================


    // ============================================================
    // ELECTRIC VEHICLE DISABLED SERVICES
    // ============================================================

    const electricDisabledServices = [

        // --------------------------------------------------------
        // ENGINE / OIL SERVICES
        // --------------------------------------------------------

        "Change Oil",

        "Oil Change",

        "Oil Filter Replacement",

        "Engine Oil Replacement",

        "Engine Tune-Up",

        "Engine Tuning",

        "Engine Repair",

        "Engine Overhaul",

        "Engine Maintenance",


        // --------------------------------------------------------
        // FUEL / CARBURETOR SERVICES
        // --------------------------------------------------------

        "Carburator Services",

        "Carburetor Services",

        "EFI",

        "Fuel Injection Service",

        "Fuel System Cleaning",

        "Decarbonizer",

        "Engine Flush",


        // --------------------------------------------------------
        // TRANSMISSION FLUID SERVICES
        // --------------------------------------------------------

        "Transmission Fluid Change",

        "Transmission Oil Change",

        "Auto Electrical Transmission"

    ];


    // ============================================================
    // HYBRID VEHICLE DISABLED SERVICES
    // ============================================================
    //
    // Hybrid vehicles should not use carburetor services.
    //
    // IMPORTANT:
    // We include both spellings because your service database
    // may use either "Carburator" or "Carburetor".
    //
    // ============================================================

    const hybridDisabledServices = [

        "Carburator Services",

        "Carburetor Services"

    ];



    // ============================================================
    // NORMALIZE SERVICE NAME
    // ============================================================

    function normalizeServiceName(
        serviceName
    ) {

        return String(
            serviceName || ""
        )
            .trim()
            .toLowerCase();

    }



    // ============================================================
    // CHECK IF SERVICE IS IN DISABLED LIST
    // ============================================================

    function serviceIsInList(
        serviceName,
        serviceList
    ) {

        const normalizedName =
            normalizeServiceName(
                serviceName
            );


        return serviceList.some(
            function (disabledService) {

                return (
                    normalizeServiceName(
                        disabledService
                    ) ===
                    normalizedName
                );

            }
        );

    }



    // ============================================================
    // GET CURRENT VEHICLE FUEL TYPE
    // ============================================================

    function getCurrentFuelType() {

        let vehicleDetails = {};


        try {

            vehicleDetails =
                JSON.parse(
                    localStorage.getItem(
                        "vehicleDetails"
                    ) || "{}"
                );

        }

        catch (error) {

            console.error(
                "Could not read vehicle details:",
                error
            );

            return "";

        }


        return normalizeServiceName(
            vehicleDetails.fuel
        );

    }



    // ============================================================
    // GET DISABLED SERVICES FOR CURRENT FUEL TYPE
    // ============================================================

    function getDisabledServices(
        fuelType
    ) {

        // ========================================================
        // ELECTRIC
        // ========================================================

        if (
            fuelType === "electric"
        ) {

            return electricDisabledServices;

        }


        // ========================================================
        // HYBRID
        // ========================================================

        if (
            fuelType === "hybrid"
        ) {

            return hybridDisabledServices;

        }


        // ========================================================
        // GASOLINE / DIESEL / OTHER
        // ========================================================

        return [];

    }



    // ============================================================
    // REMOVE SERVICE FROM SELECTED SERVICES
    // ============================================================

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

        }

        catch (error) {

            selectedServices = [];

        }


        // ========================================================
        // MAKE SURE IT IS AN ARRAY
        // ========================================================

        if (
            !Array.isArray(
                selectedServices
            )
        ) {

            selectedServices = [];

        }


        // ========================================================
        // REMOVE DISABLED SERVICE
        // ========================================================

        selectedServices =
            selectedServices.filter(
                function (service) {

                    return (
                        normalizeServiceName(
                            service
                        ) !==
                        normalizeServiceName(
                            serviceName
                        )
                    );

                }
            );


        // ========================================================
        // SAVE UPDATED LIST
        // ========================================================

        localStorage.setItem(
            "selectedServices",
            JSON.stringify(
                selectedServices
            )
        );

    }



    // ============================================================
    // REMOVE OLD EV/HYBRID DISABLED LABEL
    // ============================================================

    function removeDisabledLabel(
        service
    ) {

        const label =
            service.querySelector(
                ".vehicle-disabled-label"
            );


        if (label) {

            label.remove();

        }

    }



    // ============================================================
    // ADD DISABLED LABEL
    // ============================================================

    function addDisabledLabel(
        service,
        fuelType
    ) {

        removeDisabledLabel(
            service
        );


        const label =
            document.createElement(
                "small"
            );


        label.className =
            "vehicle-disabled-label";


        // ========================================================
        // ELECTRIC MESSAGE
        // ========================================================

        if (
            fuelType === "electric"
        ) {

            label.textContent =
                "Not available for electric vehicles";

        }


        // ========================================================
        // HYBRID MESSAGE
        // ========================================================

        else if (
            fuelType === "hybrid"
        ) {

            label.textContent =
                "Not available for hybrid vehicles";

        }


        service.appendChild(
            label
        );

    }



    // ============================================================
    // APPLY SERVICE RESTRICTIONS
    // ============================================================

    function applyVehicleServiceRules() {

        // ========================================================
        // GET FUEL TYPE
        // ========================================================

        const fuelType =
            getCurrentFuelType();


        // ========================================================
        // GET SERVICES THAT SHOULD BE DISABLED
        // ========================================================

        const disabledServices =
            getDisabledServices(
                fuelType
            );


        // ========================================================
        // GET SERVICE CARDS
        // ========================================================

        const services =
            document.querySelectorAll(
                ".service-item"
            );


        // ========================================================
        // SERVICES NOT LOADED YET
        // ========================================================

        if (
            !services.length
        ) {

            return;

        }


        // ========================================================
        // PROCESS EACH SERVICE
        // ========================================================

        services.forEach(
            function (service) {

                // ==================================================
                // GET SERVICE NAME
                // ==================================================

                const serviceName =
                    String(
                        service.dataset.serviceName ||
                        service.textContent ||
                        ""
                    )
                        .trim();


                // ==================================================
                // CHECK WHETHER SERVICE IS DISABLED
                // ==================================================

                const shouldDisable =
                    serviceIsInList(
                        serviceName,
                        disabledServices
                    );


                // ==================================================
                // DISABLE
                // ==================================================

                if (
                    shouldDisable
                ) {

                    // ----------------------------------------------
                    // GRAY STYLE CLASS
                    // ----------------------------------------------

                    service.classList.add(
                        "vehicle-disabled"
                    );


                    // ----------------------------------------------
                    // STORE DISABLED STATE
                    // ----------------------------------------------

                    service.dataset.vehicleDisabled =
                        "true";


                    // ----------------------------------------------
                    // ACCESSIBILITY
                    // ----------------------------------------------

                    service.setAttribute(
                        "aria-disabled",
                        "true"
                    );


                    // ----------------------------------------------
                    // REMOVE SELECTED STATE
                    // ----------------------------------------------

                    service.classList.remove(
                        "selected"
                    );


                    // ----------------------------------------------
                    // REMOVE FROM SELECTED SERVICES
                    // ----------------------------------------------

                    removeSelectedService(
                        serviceName
                    );


                    // ----------------------------------------------
                    // ADD EXPLANATION
                    // ----------------------------------------------

                    addDisabledLabel(
                        service,
                        fuelType
                    );

                }


                // ==================================================
                // ENABLE
                // ==================================================

                else {

                    service.classList.remove(
                        "vehicle-disabled"
                    );


                    service.dataset.vehicleDisabled =
                        "false";


                    service.removeAttribute(
                        "aria-disabled"
                    );


                    removeDisabledLabel(
                        service
                    );

                }

            }
        );


        // ========================================================
        // ADD / UPDATE CSS
        // ========================================================

        addVehicleDisabledStyles();

    }



    // ============================================================
    // PREVENT CLICKING DISABLED SERVICES
    // ============================================================
    //
    // Capture phase is used so this runs before the normal
    // booking service click handler.
    //
    // ============================================================

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
                service.dataset.vehicleDisabled ===
                "true"
            ) {

                event.preventDefault();

                event.stopPropagation();

                event.stopImmediatePropagation();

            }

        },
        true
    );



    // ============================================================
    // DISABLED SERVICE CSS
    // ============================================================

    function addVehicleDisabledStyles() {

        // ========================================================
        // DON'T ADD STYLE TWICE
        // ========================================================

        if (
            document.getElementById(
                "vehicle-disabled-service-styles"
            )
        ) {

            return;

        }


        const style =
            document.createElement(
                "style"
            );


        style.id =
            "vehicle-disabled-service-styles";


        style.textContent = `

            /* ==================================================
               DISABLED SERVICE
            ================================================== */

            .service-item.vehicle-disabled {

                background:
                    #e9ecef !important;

                color:
                    #999999 !important;

                border-color:
                    #d1d5d9 !important;

                opacity:
                    0.65 !important;

                cursor:
                    not-allowed !important;

                box-shadow:
                    none !important;

                transform:
                    none !important;

                user-select:
                    none !important;

            }


            /* ==================================================
               DISABLED SERVICE HOVER
            ================================================== */

            .service-item.vehicle-disabled:hover {

                background:
                    #e9ecef !important;

                color:
                    #999999 !important;

                border-color:
                    #d1d5d9 !important;

                opacity:
                    0.65 !important;

                cursor:
                    not-allowed !important;

                box-shadow:
                    none !important;

                transform:
                    none !important;

            }


            /* ==================================================
               DISABLED ICON
            ================================================== */

            .service-item.vehicle-disabled i,

            .service-item.vehicle-disabled .bi,

            .service-item.vehicle-disabled svg {

                color:
                    #999999 !important;

                fill:
                    #999999 !important;

            }


            /* ==================================================
               DISABLED TEXT
            ================================================== */

            .service-item.vehicle-disabled h4,

            .service-item.vehicle-disabled h5,

            .service-item.vehicle-disabled h6,

            .service-item.vehicle-disabled p,

            .service-item.vehicle-disabled span,

            .service-item.vehicle-disabled .service-name {

                color:
                    #999999 !important;

            }


            /* ==================================================
               DISABLED EXPLANATION
            ================================================== */

            .vehicle-disabled-label {

                display:
                    block;

                margin-top:
                    6px;

                font-size:
                    11px;

                line-height:
                    1.3;

                color:
                    #777777 !important;

                font-weight:
                    600;

            }


            /* ==================================================
               DISABLED + SELECTED
            ================================================== */

            .service-item.vehicle-disabled.selected {

                background:
                    #e9ecef !important;

                color:
                    #999999 !important;

                border-color:
                    #d1d5d9 !important;

                box-shadow:
                    none !important;

            }

        `;


        document.head.appendChild(
            style
        );

    }



    // ============================================================
    // SERVICE LOADING EVENT
    // ============================================================
    //
    // booking.php loads services dynamically.
    // Run the rules after they appear.
    //
    // ============================================================

    document.addEventListener(
        "ejrServicesLoaded",
        function () {

            applyVehicleServiceRules();

        }
    );



    // ============================================================
    // RUN WHEN PAGE LOADS
    // ============================================================

    applyVehicleServiceRules();


    // ============================================================
    // RUN AGAIN AFTER SERVICES HAVE HAD TIME TO LOAD
    // ============================================================

    setTimeout(
        function () {

            applyVehicleServiceRules();

        },
        500
    );


    setTimeout(
        function () {

            applyVehicleServiceRules();

        },
        1500
    );


});