document.addEventListener("DOMContentLoaded", function () {

    // ============================================================
    // EFI <-> CARBURETOR SERVICES MUTUAL EXCLUSIVITY
    // ============================================================
    //
    // RULE:
    //   - If "EFI" is selected, "Carburator/Carburetor Services"
    //     becomes disabled (and vice versa).
    //   - Deselecting either one re-enables the other.
    //
    // This does not replace or modify the existing vehicle-based
    // (electric / hybrid) service restrictions in ev-services.js —
    // it simply adds an additional, independent restriction.
    //
    // ============================================================


    // ============================================================
    // SERVICE NAME GROUPS
    // ============================================================

    const efiServiceNames = [

        "EFI"

    ];


    const carburetorServiceNames = [

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
    // CHECK IF SERVICE IS IN GROUP
    // ============================================================

    function serviceIsInGroup(
        serviceName,
        serviceGroup
    ) {

        const normalizedName =
            normalizeServiceName(
                serviceName
            );


        return serviceGroup.some(
            function (groupService) {

                return (
                    normalizeServiceName(
                        groupService
                    ) ===
                    normalizedName
                );

            }
        );

    }



    // ============================================================
    // HELPERS
    // ============================================================

    function getServiceName(
        el
    ) {

        return String(
            el.dataset.serviceName ||
            el.textContent ||
            ""
        )
            .trim();

    }


    function getAllServiceItems() {

        return document.querySelectorAll(
            ".service-item"
        );

    }


    function isSelected(
        el
    ) {

        return el.classList.contains(
            "selected"
        );

    }


    function findSelectedInGroup(
        serviceGroup
    ) {

        let foundEl = null;


        getAllServiceItems().forEach(
            function (el) {

                if (foundEl) {

                    return;

                }


                if (
                    serviceIsInGroup(
                        getServiceName(el),
                        serviceGroup
                    ) &&
                    isSelected(el)
                ) {

                    foundEl = el;

                }

            }
        );


        return foundEl;

    }



    // ============================================================
    // DISABLED LABEL
    // ============================================================

    function removeDisabledLabel(
        el
    ) {

        const label =
            el.querySelector(
                ".efi-carb-disabled-label"
            );


        if (label) {

            label.remove();

        }

    }


    function addDisabledLabel(
        el,
        text
    ) {

        removeDisabledLabel(
            el
        );


        const label =
            document.createElement(
                "small"
            );


        label.className =
            "efi-carb-disabled-label";


        label.textContent =
            text;


        el.appendChild(
            label
        );

    }



    // ============================================================
    // ENABLE / DISABLE A SERVICE
    // ============================================================

    function setServiceDisabled(
        el,
        disabled,
        labelText
    ) {

        if (disabled) {

            el.classList.add(
                "efi-carb-disabled"
            );


            el.dataset.efiCarbDisabled =
                "true";


            el.setAttribute(
                "aria-disabled",
                "true"
            );


            addDisabledLabel(
                el,
                labelText
            );

        }

        else {

            el.classList.remove(
                "efi-carb-disabled"
            );


            el.dataset.efiCarbDisabled =
                "false";


            // Only clear aria-disabled if the vehicle rules
            // (ev-services.js) don't also need it disabled.

            if (
                el.dataset.vehicleDisabled !== "true"
            ) {

                el.removeAttribute(
                    "aria-disabled"
                );

            }


            removeDisabledLabel(
                el
            );

        }

    }



    // ============================================================
    // APPLY EXCLUSIVITY RULES
    // ============================================================

    function applyExclusivityRules() {

        const efiSelectedEl =
            findSelectedInGroup(
                efiServiceNames
            );


        const carbSelectedEl =
            findSelectedInGroup(
                carburetorServiceNames
            );


        getAllServiceItems().forEach(
            function (el) {

                const name =
                    getServiceName(
                        el
                    );


                // ==================================================
                // CARBURETOR SERVICES ITEM
                // ==================================================

                if (
                    serviceIsInGroup(
                        name,
                        carburetorServiceNames
                    )
                ) {

                    const shouldDisable =
                        !!efiSelectedEl &&
                        el !== efiSelectedEl;


                    if (
                        shouldDisable &&
                        isSelected(el)
                    ) {

                        el.classList.remove(
                            "selected"
                        );

                    }


                    setServiceDisabled(
                        el,
                        shouldDisable,
                        "Not available while EFI is selected"
                    );

                }


                // ==================================================
                // EFI ITEM
                // ==================================================

                else if (
                    serviceIsInGroup(
                        name,
                        efiServiceNames
                    )
                ) {

                    const shouldDisable =
                        !!carbSelectedEl &&
                        el !== carbSelectedEl;


                    if (
                        shouldDisable &&
                        isSelected(el)
                    ) {

                        el.classList.remove(
                            "selected"
                        );

                    }


                    setServiceDisabled(
                        el,
                        shouldDisable,
                        "Not available while Carburetor Services is selected"
                    );

                }

            }
        );


        addExclusivityStyles();

    }



    // ============================================================
    // PREVENT CLICKING DISABLED SERVICES
    // ============================================================
    //
    // Capture phase so this runs before the normal booking
    // service click handler in booking.js.
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
                service.dataset.efiCarbDisabled ===
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
    // RE-APPLY RULES AFTER A SELECTION CHANGES
    // ============================================================
    //
    // Bubble phase, so this runs after booking.js has already
    // toggled the "selected" class for the clicked service.
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


            applyExclusivityRules();

        }
    );



    // ============================================================
    // EXCLUSIVITY CSS
    // ============================================================

    function addExclusivityStyles() {

        if (
            document.getElementById(
                "efi-carb-exclusivity-styles"
            )
        ) {

            return;

        }


        const style =
            document.createElement(
                "style"
            );


        style.id =
            "efi-carb-exclusivity-styles";


        style.textContent = `

            /* ==================================================
               DISABLED SERVICE (EFI / CARBURETOR EXCLUSIVITY)
            ================================================== */

            .service-item.efi-carb-disabled {

                background:
                    #eeeeee !important;

                color:
                    #999999 !important;

                border-color:
                    #dddddd !important;

                cursor:
                    not-allowed !important;

                opacity:
                    0.60 !important;

                transform:
                    none !important;

                box-shadow:
                    none !important;

                user-select:
                    none !important;

            }


            .service-item.efi-carb-disabled:hover {

                transform:
                    none !important;

                box-shadow:
                    none !important;

                cursor:
                    not-allowed !important;

            }


            .service-item.efi-carb-disabled .service-info-btn {

                pointer-events:
                    none;

                opacity:
                    0.5;

            }


            .efi-carb-disabled-label {

                display:
                    block;

                width:
                    100%;

                margin-top:
                    5px;

                font-size:
                    9px;

                line-height:
                    1.2;

                color:
                    #888888;

                font-weight:
                    600;

            }

        `;


        document.head.appendChild(
            style
        );

    }



    // ============================================================
    // KEEP SERVICE DETAILS MODAL IN SYNC
    // ============================================================
    //
    // booking.js exposes window.showServiceDetails(). Wrap it
    // (without editing booking.js) so the modal also reflects
    // the EFI / Carburetor exclusivity state.
    //
    // ============================================================

    function wrapShowServiceDetailsOnce() {

        if (
            window.__efiCarbModalWrapped ||
            typeof window.showServiceDetails !== "function"
        ) {

            return;

        }


        const originalShowServiceDetails =
            window.showServiceDetails;


        window.showServiceDetails = function (
            serviceEl
        ) {

            originalShowServiceDetails(
                serviceEl
            );


            if (
                serviceEl &&
                serviceEl.dataset.efiCarbDisabled === "true"
            ) {

                const selectBtn =
                    document.getElementById(
                        "serviceDetailsSelectBtn"
                    );


                if (selectBtn) {

                    selectBtn.textContent =
                        "Not Available Right Now";


                    selectBtn.disabled =
                        true;


                    selectBtn.classList.remove(
                        "is-selected"
                    );

                }

            }

        };


        window.__efiCarbModalWrapped =
            true;

    }



    // ============================================================
    // SERVICE LOADING EVENT
    // ============================================================
    //
    // booking.php loads services dynamically. Run the rules
    // (and wrap the modal helper) once they appear.
    //
    // ============================================================

    document.addEventListener(
        "ejrServicesLoaded",
        function () {

            applyExclusivityRules();

            wrapShowServiceDetailsOnce();

        }
    );



    // ============================================================
    // RUN WHEN PAGE LOADS / SHORTLY AFTER
    // ============================================================

    applyExclusivityRules();

    wrapShowServiceDetailsOnce();


    setTimeout(
        function () {

            applyExclusivityRules();

            wrapShowServiceDetailsOnce();

        },
        500
    );


    setTimeout(
        function () {

            applyExclusivityRules();

            wrapShowServiceDetailsOnce();

        },
        1500
    );

});
