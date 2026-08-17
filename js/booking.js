document.addEventListener(
    "DOMContentLoaded",
    function () {


        // ============================================================
        // ELEMENTS
        // ============================================================

        const vehicleForm =
            document.getElementById(
                "vehicleForm"
            );


        const savedSection =
            document.getElementById(
                "savedVehicleSection"
            );


        const manualForm =
            document.getElementById(
                "manualVehicleForm"
            );


        const savedSelect =
            document.getElementById(
                "savedVehicle"
            );


        const brandSelect =
            document.getElementById(
                "brand"
            );


        const modelSelect =
            document.getElementById(
                "model"
            );


        const yearSelect =
            document.getElementById(
                "year"
            );


        const fuelSelect =
            document.getElementById(
                "fuel"
            );


        const transmissionSelect =
            document.getElementById(
                "transmission"
            );


        // ============================================================
        // FUEL / TRANSMISSION DEPENDENCY
        //
        // Hybrid and Electric vehicles use CVT only.
        // Gasoline and Diesel allow all transmission options.
        // ============================================================

        function updateTransmissionOptions() {

            if (
                !fuelSelect ||
                !transmissionSelect
            ) {

                return;

            }


            const selectedFuel =
                fuelSelect.value;


            const isHybridOrElectric =
                selectedFuel === "Hybrid" ||
                selectedFuel === "Electric";


            const transmissionOptions =
                Array.from(
                    transmissionSelect.options
                );


            if (isHybridOrElectric) {

                // ====================================================
                // HYBRID / ELECTRIC
                // CVT IS THE ONLY AVAILABLE OPTION
                // ====================================================

                transmissionOptions.forEach(
                    function (option) {

                        if (
                            option.value ===
                            "CVT"
                        ) {

                            option.hidden =
                                false;

                            option.disabled =
                                false;

                        }

                        else {

                            option.hidden =
                                true;

                            option.disabled =
                                true;

                        }

                    }
                );


                // Automatically select CVT

                transmissionSelect.value =
                    "CVT";

            }

            else {

                // ====================================================
                // GASOLINE / DIESEL
                // RESTORE ALL TRANSMISSION OPTIONS
                // ====================================================

                transmissionOptions.forEach(
                    function (option) {

                        option.hidden =
                            false;

                        option.disabled =
                            false;

                    }
                );

            }

        }


        // ============================================================
        // WATCH FOR FUEL TYPE CHANGES
        // ============================================================

        if (fuelSelect) {

            fuelSelect.addEventListener(
                "change",
                updateTransmissionOptions
            );


            // Run once when page loads

            updateTransmissionOptions();

        }



        // ============================================================
        // MODEL DATA
        // ============================================================

        const vehicleModels = {

            Toyota: [
                "Vios",
                "Wigo",
                "Avanza",
                "Innova",
                "Fortuner",
                "Hilux",
                "Rush",
                "Corolla Altis",
                "Camry",
                "Raize",
                "Yaris Cross",
                "RAV4",
                "Land Cruiser",
                "Hiace"
            ],

            Honda: [
                "City",
                "Civic",
                "CR-V",
                "HR-V",
                "Br-V",
                "Jazz",
                "Mobilio",
                "Odyssey"
            ],

            Mitsubishi: [
                "Mirage",
                "Mirage G4",
                "Xpander",
                "Montero Sport",
                "Triton",
                "Outlander"
            ],

            Nissan: [
                "Almera",
                "Navara",
                "Terra",
                "Kicks",
                "X-Trail",
                "Patrol"
            ],

            Ford: [
                "Ranger",
                "Everest",
                "Territory",
                "Mustang",
                "Explorer"
            ],

            Hyundai: [
                "Accent",
                "Elantra",
                "Tucson",
                "Santa Fe",
                "Stargazer",
                "Staria"
            ],

            Kia: [
                "Picanto",
                "Soluto",
                "Seltos",
                "Sportage",
                "Sorento",
                "Carnival"
            ],

            Suzuki: [
                "Celerio",
                "Dzire",
                "Ertiga",
                "Jimny",
                "Swift",
                "Vitara"
            ],

            Isuzu: [
                "D-Max",
                "mu-X"
            ],

            Chevrolet: [
                "Spark",
                "Cruze",
                "Trailblazer",
                "Colorado"
            ],

            Mazda: [
                "Mazda 2",
                "Mazda 3",
                "CX-3",
                "CX-5",
                "CX-8",
                "CX-9"
            ],

            Volkswagen: [
                "Polo",
                "Golf",
                "Tiguan",
                "Teramont"
            ],

            Subaru: [
                "Impreza",
                "Forester",
                "Outback",
                "XV"
            ],

            Geely: [
                "Coolray",
                "Azkarra",
                "Okavango",
                "Emgrand"
            ],

            MG: [
                "MG 3",
                "MG 5",
                "MG ZS",
                "MG HS"
            ],

            BYD: [
                "Dolphin",
                "Atto 3",
                "Seal",
                "Tang"
            ],

            Tesla: [
                "Model 3",
                "Model Y",
                "Model S",
                "Model X"
            ]

        };



        // ============================================================
        // CUSTOM MODEL
        // ============================================================

        const customModelContainer =
            document.getElementById(
                "customModelContainer"
            );


        const customModelInput =
            document.getElementById(
                "modelOther"
            );


        function showCustomModelInput() {

            if (
                customModelContainer
            ) {

                customModelContainer.style.display =
                    "block";

            }


            if (
                modelSelect
            ) {

                modelSelect.style.display =
                    "none";

            }

        }



        function showNormalModelDropdown() {

            if (
                customModelContainer
            ) {

                customModelContainer.style.display =
                    "none";

            }


            if (
                modelSelect
            ) {

                modelSelect.style.display =
                    "block";

            }

        }



        // ============================================================
        // BRAND CHANGE
        // ============================================================

        if (brandSelect) {

            brandSelect.addEventListener(
                "change",
                function () {

                    const selectedBrand =
                        this.value;


                    if (
                        selectedBrand ===
                        "Other"
                    ) {

                        showCustomModelInput();

                        return;

                    }


                    showNormalModelDropdown();


                    if (
                        !modelSelect
                    ) {

                        return;

                    }


                    modelSelect.innerHTML =
                        '<option value="">Select Model</option>';


                    if (
                        vehicleModels[
                            selectedBrand
                        ]
                    ) {

                        vehicleModels[
                            selectedBrand
                        ].forEach(
                            function (model) {

                                const option =
                                    document.createElement(
                                        "option"
                                    );


                                option.value =
                                    model;


                                option.textContent =
                                    model;


                                modelSelect.appendChild(
                                    option
                                );

                            }
                        );

                    }


                    const otherOption =
                        document.createElement(
                            "option"
                        );


                    otherOption.value =
                        "Other";


                    otherOption.textContent =
                        "Other";


                    modelSelect.appendChild(
                        otherOption
                    );

                }
            );

        }



        // ============================================================
        // MODEL CHANGE
        // ============================================================

        if (modelSelect) {

            modelSelect.addEventListener(
                "change",
                function () {

                    const selectedModel =
                        this.value;


                    // ==================================================
                    // OTHER MODEL
                    // ==================================================

                    if (
                        selectedModel ===
                        "Other"
                    ) {

                        showCustomModelInput();

                    }


                    // ==================================================
                    // NORMAL MODEL
                    // ==================================================

                    else {

                        showNormalModelDropdown();


                        if (
                            selectedModel
                        ) {

                            modelSelect.value =
                                selectedModel;

                        }

                    }

                }
            );

        }



        // ============================================================
        // YEAR DROPDOWN
        // ============================================================

        if (yearSelect) {

            const currentYear =
                new Date().getFullYear();


            for (
                let year = currentYear;
                year >= 1990;
                year--
            ) {

                const option =
                    document.createElement(
                        "option"
                    );


                option.value =
                    year;


                option.textContent =
                    year;


                yearSelect.appendChild(
                    option
                );

            }

        }



        // ============================================================
        // LOAD SAVED VEHICLES
        // ============================================================

        async function loadUserGarage() {

            if (
                !savedSelect
            ) {

                return;

            }


            const token =
                localStorage.getItem(
                    "jwt_token"
                );


            try {

                const response =
                    await fetch(
                        "api/get_vehicles.php",
                        {
                            headers: {
                                "Authorization":
                                    "Bearer " +
                                    token
                            }
                        }
                    );


                const data =
                    await response.json();


                if (
                    data.status ===
                        "success" &&
                    data.vehicles &&
                    data.vehicles.length >
                        0
                ) {

                    savedSection.style.display =
                        "block";


                    manualForm.style.display =
                        "none";


                    const backToSavedContainer =
                        document.getElementById(
                            "backToSavedContainer"
                        );


                    if (
                        backToSavedContainer
                    ) {

                        backToSavedContainer.style.display =
                            "block";

                    }


                    savedSelect.innerHTML =
                        '<option value="">-- Select Saved Vehicle --</option>';


                    data.vehicles.forEach(
                        function (vehicle) {

                            const option =
                                document.createElement(
                                    "option"
                                );


                            option.value =
                                JSON.stringify(
                                    vehicle
                                );


                            option.textContent =
                                vehicle.brand +
                                " " +
                                vehicle.model +
                                " (" +
                                vehicle.plate_number +
                                ")";


                            savedSelect.appendChild(
                                option
                            );

                        }
                    );

                }

            }

            catch (error) {

                console.error(
                    "Garage load failed:",
                    error
                );

            }

        }



        // ============================================================
        // CALENDAR
        // ============================================================

        async function initCalendar() {

            let busyDates = [];


            try {

                const response =
                    await fetch(
                        "api/get_busy_dates.php"
                    );


                const data =
                    await response.json();


                if (
                    data.status ===
                    "success"
                ) {

                    busyDates =
                        data.busy_dates ||
                        [];

                }

            }

            catch (error) {

                console.error(
                    "Failed to load busy dates:",
                    error
                );

            }


            if (
                typeof flatpickr ===
                "undefined"
            ) {

                console.error(
                    "Flatpickr is not loaded."
                );

                return;

            }


            const disableDates =
                busyDates.map(
                    function (dateString) {

                        const parts =
                            dateString.split(
                                "-"
                            );


                        const year =
                            Number(
                                parts[0]
                            );


                        const month =
                            Number(
                                parts[1]
                            );


                        const day =
                            Number(
                                parts[2]
                            );


                        return new Date(
                            year,
                            month - 1,
                            day
                        );

                    }
                );


            const appointmentDate =
                document.getElementById(
                    "appointmentDate"
                );


            if (
                !appointmentDate
            ) {

                return;

            }


            flatpickr(
                appointmentDate,
                {

                    minDate:
                        "today",

                    dateFormat:
                        "Y-m-d",

                    disableMobile:
                        true,

                    disable:
                        disableDates,


                    onDayCreate:
                        function (
                            selectedDates,
                            dateString,
                            instance,
                            dayElement
                        ) {

                            const date =
                                dayElement.dateObj;


                            const year =
                                date.getFullYear();


                            const month =
                                String(
                                    date.getMonth() +
                                    1
                                ).padStart(
                                    2,
                                    "0"
                                );


                            const day =
                                String(
                                    date.getDate()
                                ).padStart(
                                    2,
                                    "0"
                                );


                            const formattedDate =
                                year +
                                "-" +
                                month +
                                "-" +
                                day;


                            if (
                                busyDates.includes(
                                    formattedDate
                                )
                            ) {

                                dayElement.style.backgroundColor =
                                    "#e62429";


                                dayElement.style.color =
                                    "#ffffff";


                                dayElement.style.borderRadius =
                                    "50%";


                                dayElement.style.opacity =
                                    "1";


                                dayElement.style.pointerEvents =
                                    "none";


                                dayElement.style.cursor =
                                    "not-allowed";


                                dayElement.title =
                                    "Fully booked";

                            }

                        }

                }
            );

        }



        // ============================================================
        // VEHICLE FORM
        // ============================================================

        if (vehicleForm) {

            loadUserGarage();

            initCalendar();


            // ========================================================
            // USE DIFFERENT VEHICLE
            // ========================================================

            const addNewManualButton =
                document.getElementById(
                    "btnAddNewManual"
                );


            if (
                addNewManualButton
            ) {

                addNewManualButton.addEventListener(
                    "click",
                    function () {

                        manualForm.style.display =
                            "block";


                        savedSection.style.display =
                            "none";


                        if (
                            savedSelect
                        ) {

                            savedSelect.value =
                                "";

                        }

                    }
                );

            }


            // ========================================================
            // BACK TO SAVED VEHICLES
            // ========================================================

            const backToSavedButton =
                document.getElementById(
                    "btnBackToSaved"
                );


            if (
                backToSavedButton
            ) {

                backToSavedButton.addEventListener(
                    "click",
                    function () {

                        manualForm.style.display =
                            "none";


                        savedSection.style.display =
                            "block";

                    }
                );

            }


            // ========================================================
            // VEHICLE FORM SUBMISSION
            // ========================================================

            vehicleForm.addEventListener(
                "submit",
                function (event) {

                    event.preventDefault();


                    let vehicleDetails =
                        {};


                    // ==================================================
                    // CHECK SAVED VEHICLE
                    // ==================================================

                    const isUsingSavedVehicle =
                        savedSection &&
                        savedSection.style.display !==
                            "none" &&
                        savedSelect &&
                        savedSelect.value !==
                            "";


                    // ==================================================
                    // SAVED VEHICLE
                    // ==================================================

                    if (
                        isUsingSavedVehicle
                    ) {

                        const vehicle =
                            JSON.parse(
                                savedSelect.value
                            );


                        vehicleDetails = {

                            brand:
                                vehicle.brand ||
                                "",

                            model:
                                vehicle.model ||
                                "",

                            year:
                                vehicle.year_model ||
                                "",

                            plate:
                                vehicle.plate_number ||
                                "",

                            transmission:
                                vehicle.transmission ||
                                "",

                            fuel:
                                vehicle.fuel_type ||
                                "",

                            vehicle_id:
                                vehicle.id ||
                                ""

                        };

                    }


                    // ==================================================
                    // MANUAL VEHICLE
                    // ==================================================

                    else {

                        const selectedBrand =
                            document
                                .getElementById(
                                    "brand"
                                )
                                .value;


                        let selectedModel =
                            "";


                        const modelDropdown =
                            document.getElementById(
                                "model"
                            );


                        const customModelInput =
                            document.getElementById(
                                "modelOther"
                            );


                        // ==============================================
                        // BRAND = OTHER
                        // ==============================================

                        if (
                            selectedBrand ===
                            "Other"
                        ) {

                            selectedModel =
                                customModelInput
                                    ? customModelInput.value.trim()
                                    : "";

                        }


                        // ==============================================
                        // MODEL = OTHER
                        // ==============================================

                        else if (
                            modelDropdown &&
                            modelDropdown.value ===
                                "Other"
                        ) {

                            selectedModel =
                                customModelInput
                                    ? customModelInput.value.trim()
                                    : "";

                        }


                        // ==============================================
                        // NORMAL MODEL
                        // ==============================================

                        else {

                            selectedModel =
                                modelDropdown
                                    ? modelDropdown.value
                                    : "";

                        }


                        // =================================================
                        // BUILD VEHICLE DETAILS
                        // =================================================

                        vehicleDetails = {

                            brand:
                                selectedBrand,

                            model:
                                selectedModel,

                            year:
                                document
                                    .getElementById(
                                        "year"
                                    )
                                    .value,

                            plate:
                                document
                                    .getElementById(
                                        "plate"
                                    )
                                    .value
                                    .trim(),

                            transmission:
                                document
                                    .getElementById(
                                        "transmission"
                                    )
                                    .value,

                            fuel:
                                document
                                    .getElementById(
                                        "fuel"
                                    )
                                    .value

                        };


                        // =================================================
                        // VALIDATION
                        // =================================================

                        if (
                            !vehicleDetails.brand ||
                            !vehicleDetails.model ||
                            !vehicleDetails.year ||
                            !vehicleDetails.plate ||
                            !vehicleDetails.transmission ||
                            !vehicleDetails.fuel
                        ) {

                            const errorMessage =
                                document.getElementById(
                                    "errorMsg"
                                );


                            if (
                                errorMessage
                            ) {

                                errorMessage.style.display =
                                    "block";

                            }


                            // If the custom model textbox
                            // is being used, focus it.

                            if (
                                (
                                    selectedBrand ===
                                    "Other"
                                ||
                                    (
                                        modelDropdown &&
                                        modelDropdown.value ===
                                            "Other"
                                    )
                                ) &&
                                !selectedModel
                            ) {

                                if (
                                    customModelInput
                                ) {

                                    customModelInput.focus();

                                }

                            }


                            return;

                        }


                        // =================================================
                        // HIDE ERROR
                        // =================================================

                        const errorMessage =
                            document.getElementById(
                                "errorMsg"
                            );


                        if (
                            errorMessage
                        ) {

                            errorMessage.style.display =
                                "none";

                        }

                    }


                    // ==================================================
                    // APPOINTMENT DATE
                    // ==================================================

                    const appointmentDate =
                        document.getElementById(
                            "appointmentDate"
                        );


                    vehicleDetails.appointmentDate =
                        appointmentDate
                            ? appointmentDate.value
                            : "";


                    // ==================================================
                    // NOTES
                    // ==================================================

                    const notes =
                        document.getElementById(
                            "notes"
                        );


                    vehicleDetails.notes =
                        notes
                            ? notes.value.trim()
                            : "";


                    // ==================================================
                    // APPOINTMENT DATE VALIDATION
                    // ==================================================

                    if (
                        !vehicleDetails.appointmentDate
                    ) {

                        alert(
                            "Please select an appointment date."
                        );

                        return;

                    }


                    // ==================================================
                    // SAVE VEHICLE DETAILS
                    // ==================================================

                    localStorage.setItem(
                        "vehicleDetails",
                        JSON.stringify(
                            vehicleDetails
                        )
                    );


                    // ==================================================
                    // GO TO SERVICE SELECTION
                    // ==================================================

                    window.location.href =
                        "booking.php";

                }
            );

        }

    }
);