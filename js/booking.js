document.addEventListener("DOMContentLoaded", function () {

    // ============================================================
    // LOGIN CHECK
    // ============================================================

    function isLoggedIn() {

        return (
            typeof userLoggedIn !== "undefined" &&
            userLoggedIn === true
        );

    }


    // ============================================================
    // SERVICE SELECTION
    // ============================================================

    const nextBtn =
        document.querySelector(".btn-next");

    const serviceError =
        document.getElementById("serviceError");

    const serviceLimitError =
        document.getElementById("serviceLimitError");

    let selectedServices = [];

    let authModal = null;

    let step1Initialized = false;


    function initServiceSelection() {

        const services =
            document.querySelectorAll(".service-item");


        if (
            services.length === 0 ||
            !nextBtn ||
            document.getElementById("vehicleForm")
        ) {

            return;

        }


        // ========================================================
        // LOGIN MODAL
        // ========================================================

        if (!authModal) {

            authModal =
                document.createElement("div");

            authModal.id =
                "authModal";


            authModal.style.cssText =
                "display:none;" +
                "position:fixed;" +
                "z-index:9999;" +
                "left:0;" +
                "top:0;" +
                "width:100%;" +
                "height:100%;" +
                "background:rgba(0,0,0,0.7);" +
                "align-items:center;" +
                "justify-content:center;";


            authModal.innerHTML = `

                <div
                    style="
                        background:#ffffff;
                        border-radius:16px;
                        width:90%;
                        max-width:420px;
                        box-shadow:0 15px 40px rgba(0,0,0,0.3);
                        overflow:hidden;
                        position:relative;
                        border:1px solid #e3e8ef;
                        text-align:center;
                        padding:40px 30px;
                    "
                >

                    <button
                        onclick="
                            document.getElementById('authModal').style.display='none'
                        "
                        style="
                            position:absolute;
                            top:15px;
                            right:20px;
                            background:none;
                            border:none;
                            font-size:28px;
                            cursor:pointer;
                            color:#a0aab5;
                        "
                    >
                        &times;
                    </button>


                    <div
                        style="
                            width:70px;
                            height:70px;
                            border-radius:50%;
                            background:rgba(230,36,41,0.1);
                            color:#e62429;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            margin:0 auto 20px auto;
                            font-size:32px;
                        "
                    >
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>


                    <h3
                        style="
                            font-family:'Rajdhani',sans-serif;
                            font-weight:700;
                            color:#1e2a36;
                            margin-bottom:10px;
                            font-size:1.8rem;
                            text-transform:uppercase;
                        "
                    >
                        Login Required
                    </h3>


                    <p
                        style="
                            color:#5f6b7a;
                            margin-bottom:30px;
                            font-size:0.95rem;
                            line-height:1.6;
                        "
                    >
                        You must be logged in to book an appointment
                        with EJR Garage.
                    </p>


                    <div
                        style="
                            display:flex;
                            gap:12px;
                            flex-direction:column;
                        "
                    >

                        <a
                            href="login.php"
                            style="
                                width:100%;
                                background:linear-gradient(45deg,#e62429,#ff6b00);
                                color:#fff;
                                padding:12px;
                                border-radius:8px;
                                font-family:'Rajdhani',sans-serif;
                                font-weight:700;
                                text-decoration:none;
                                text-transform:uppercase;
                            "
                        >
                            Log In Here
                        </a>


                        <a
                            href="registration.php"
                            style="
                                width:100%;
                                padding:12px;
                                border:1px solid #e3e8ef;
                                border-radius:8px;
                                color:#1e2a36;
                                font-family:'Rajdhani',sans-serif;
                                font-weight:700;
                                text-decoration:none;
                                text-transform:uppercase;
                            "
                        >
                            Register Now
                        </a>

                    </div>

                </div>

            `;


            document.body.appendChild(
                authModal
            );


            window.addEventListener(
                "click",
                function (event) {

                    if (
                        event.target === authModal
                    ) {

                        authModal.style.display =
                            "none";

                    }

                }
            );

        }


        // ========================================================
        // SERVICE CLICK EVENTS
        // ========================================================

        services.forEach(
            function (service) {

                service.addEventListener(
                    "click",
                    function () {

                        if (
                            this.dataset.adminUnavailable === "true"
                        ) {

                            return;

                        }


                        const serviceName =
                            (this.dataset.serviceName || this.textContent).trim();


                        if (serviceError) {

                            serviceError.style.display =
                                "none";

                        }


                        if (serviceLimitError) {

                            serviceLimitError.style.display =
                                "none";

                        }


                        if (
                            this.classList.contains(
                                "selected"
                            )
                        ) {

                            this.classList.remove(
                                "selected"
                            );


                            selectedServices =
                                selectedServices.filter(
                                    function (service) {

                                        return (
                                            service !==
                                            serviceName
                                        );

                                    }
                                );

                        }

                        else {

                            if (
                                selectedServices.length >= 5
                            ) {

                                if (serviceLimitError) {

                                    serviceLimitError.style.display =
                                        "block";

                                }

                                return;

                            }


                            this.classList.add(
                                "selected"
                            );


                            selectedServices.push(
                                serviceName
                            );

                        }

                    }
                );

            }
        );


        // ========================================================
        // SERVICE DETAILS MODAL
        // ========================================================

        const detailsModal =
            document.getElementById("serviceDetailsModal");

        const detailsNameEl =
            document.getElementById("serviceDetailsName");

        const detailsPriceEl =
            document.getElementById("serviceDetailsPrice");

        const detailsDescEl =
            document.getElementById("serviceDetailsDescription");

        const detailsSelectBtn =
            document.getElementById("serviceDetailsSelectBtn");

        let activeDetailsService = null;

        function formatPrice(price) {

            const num = parseFloat(price);

            if (!num || num <= 0) {

                return "";

            }

            return "Starts at \u20B1" +
                num.toLocaleString("en-PH", {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });

        }

        function refreshDetailsSelectBtn() {

            if (!activeDetailsService || !detailsSelectBtn) {

                return;

            }

            const isDisabled =
                activeDetailsService.dataset.vehicleDisabled === "true";

            const isAdminUnavailable =
                activeDetailsService.dataset.adminUnavailable === "true";

            const isSelected =
                activeDetailsService.classList.contains("selected");

            if (isAdminUnavailable) {

                detailsSelectBtn.textContent =
                    "Temporarily Unavailable";

                detailsSelectBtn.disabled = true;

                detailsSelectBtn.classList.remove("is-selected");

                return;

            }

            if (isDisabled) {

                detailsSelectBtn.textContent =
                    "Not Available For This Vehicle";

                detailsSelectBtn.disabled = true;

                detailsSelectBtn.classList.remove("is-selected");

                return;

            }

            detailsSelectBtn.disabled = false;

            if (isSelected) {

                detailsSelectBtn.textContent =
                    "Remove From Selection";

                detailsSelectBtn.classList.add("is-selected");

            } else {

                detailsSelectBtn.textContent =
                    "Select This Service";

                detailsSelectBtn.classList.remove("is-selected");

            }

        }

        window.showServiceDetails = function (serviceEl) {

            if (!serviceEl || !detailsModal) {

                return;

            }

            activeDetailsService = serviceEl;

            const name =
                serviceEl.dataset.serviceName ||
                serviceEl.textContent.trim();

            const description =
                serviceEl.dataset.description || "";

            const price =
                serviceEl.dataset.price || "";

            if (detailsNameEl) {

                detailsNameEl.textContent = name;

            }

            const isAdminUnavailable =
                serviceEl.dataset.adminUnavailable === "true";

            if (detailsDescEl) {

                const baseText =
                    description ||
                    "No additional details are available for this service yet. Feel free to ask our team when you arrive.";

                detailsDescEl.textContent =
                    isAdminUnavailable
                        ? "This service is temporarily unavailable and can't be booked right now. " + baseText
                        : baseText;

            }

            const formattedPrice =
                formatPrice(price);

            if (detailsPriceEl) {

                if (formattedPrice) {

                    detailsPriceEl.textContent =
                        formattedPrice;

                    detailsPriceEl.style.display =
                        "inline-block";

                } else {

                    detailsPriceEl.style.display =
                        "none";

                }

            }

            refreshDetailsSelectBtn();

            detailsModal.style.display = "flex";

            document.body.style.overflow = "hidden";

        };

        window.closeServiceDetails = function () {

            if (!detailsModal) {

                return;

            }

            detailsModal.style.display = "none";

            document.body.style.overflow = "";

            activeDetailsService = null;

        };

        window.toggleServiceFromModal = function () {

            if (
                !activeDetailsService ||
                activeDetailsService.dataset.vehicleDisabled === "true" ||
                activeDetailsService.dataset.adminUnavailable === "true"
            ) {

                return;

            }

            activeDetailsService.click();

            refreshDetailsSelectBtn();

        };

        if (detailsModal && detailsModal.dataset.listenersAttached !== "true") {

            detailsModal.addEventListener(
                "click",
                function (event) {

                    if (event.target === detailsModal) {

                        window.closeServiceDetails();

                    }

                }
            );

            document.addEventListener(
                "keydown",
                function (event) {

                    if (
                        event.key === "Escape" &&
                        detailsModal.style.display === "flex"
                    ) {

                        window.closeServiceDetails();

                    }

                }
            );

            detailsModal.dataset.listenersAttached = "true";

        }


        // ========================================================
        // NEXT BUTTON
        // ========================================================

        if (!step1Initialized) {

            nextBtn.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();


                    if (
                        selectedServices.length === 0
                    ) {

                        if (serviceError) {

                            serviceError.style.display =
                                "block";

                        }

                        return;

                    }


                    if (!isLoggedIn()) {

                        if (authModal) {

                            authModal.style.display =
                                "flex";

                        }

                        return;

                    }


                    localStorage.setItem(
                        "selectedServices",
                        JSON.stringify(
                            selectedServices
                        )
                    );


                    window.location.href =
                        "booking-confirmation.php";

                }
            );


            step1Initialized = true;

        }

    }


    initServiceSelection();


    document.addEventListener(
        "ejrServicesLoaded",
        initServiceSelection
    );



    // ============================================================
    // VEHICLE DETAILS
    // ============================================================

    const vehicleForm =
        document.getElementById(
            "vehicleForm"
        );


    const yearSelect =
        document.getElementById(
            "year"
        );


    const savedSection =
        document.getElementById(
            "savedVehiclesSection"
        );


    const manualForm =
        document.getElementById(
            "manualVehicleForm"
        );


    const savedSelect =
        document.getElementById(
            "savedVehicleSelect"
        );


    const brandSelect =
        document.getElementById(
            "brand"
        );


    const modelSelect =
        document.getElementById(
            "model"
        );


    const modelOtherInput =
        document.getElementById(
            "modelOther"
        );


    const modelHelpText =
        document.getElementById(
            "modelHelpText"
        );



    // ============================================================
    // PHILIPPINE CAR BRANDS AND MODELS
    // ============================================================

    const philippineCarModels = {

        Toyota: [

            "86 / GR86",
            "Alphard",
            "Avanza",
            "Camry",
            "Coaster",
            "Corolla Altis",
            "Corolla Cross",
            "FJ Cruiser",
            "Fortuner",
            "GR Supra",
            "GR Yaris",
            "Hiace",
            "Hilux",
            "Innova",
            "Land Cruiser",
            "Land Cruiser Prado",
            "Lite Ace",
            "RAV4",
            "Raize",
            "Rush",
            "Tamaraw",
            "Veloz",
            "Vios",
            "Wigo",
            "Yaris",
            "Yaris Cross",
            "Zenix"

        ],


        Honda: [

            "Accord",
            "Brio",
            "BR-V",
            "City",
            "City Hatchback",
            "Civic",
            "CR-V",
            "HR-V",
            "Jazz",
            "Mobilio",
            "Odyssey"

        ],


        Mitsubishi: [

            "Adventure",
            "ASX",
            "Eclipse Cross",
            "Lancer",
            "Lancer EX",
            "Lancer Evolution",
            "L300",
            "Mirage",
            "Mirage G4",
            "Montero",
            "Montero Sport",
            "Outlander",
            "Outlander PHEV",
            "Triton",
            "Xpander",
            "Xpander Cross"

        ],


        Nissan: [

            "Almera",
            "Juke",
            "Kicks",
            "Livina",
            "Navara",
            "Patrol",
            "Sentra",
            "Serena",
            "Terra",
            "Urvan",
            "X-Trail"

        ],


        Ford: [

            "EcoSport",
            "Everest",
            "Explorer",
            "Expedition",
            "Fiesta",
            "Focus",
            "Mustang",
            "Ranger",
            "Territory",
            "Transit"

        ],


        Suzuki: [

            "APV",
            "Alto",
            "Celerio",
            "Dzire",
            "Ertiga",
            "Ignis",
            "Jimny",
            "S-Presso",
            "Swift",
            "Vitara",
            "XL7",
            "Carry"

        ],


        Isuzu: [

            "Crosswind",
            "D-Max",
            "mu-X",
            "Traviz",
            "Alterra",
            "Trooper"

        ],


        Hyundai: [

            "Accent",
            "Avante",
            "Creta",
            "Eon",
            "Elantra",
            "Getz",
            "H-100",
            "i10",
            "Ioniq",
            "Ioniq 5",
            "Ioniq 6",
            "Kona",
            "Santa Fe",
            "Sonata",
            "Stargazer",
            "Staria",
            "Tucson",
            "Veloster"

        ],


        Kia: [

            "Carens",
            "Carnival",
            "EV6",
            "EV9",
            "K3",
            "K5",
            "Picanto",
            "Rio",
            "Seltos",
            "Soluto",
            "Sorento",
            "Sportage",
            "Stonic"

        ],


        Mazda: [

            "2",
            "3",
            "6",
            "BT-50",
            "CX-3",
            "CX-30",
            "CX-5",
            "CX-8",
            "CX-9",
            "CX-60",
            "CX-90",
            "MX-5"

        ]

    };



    // ============================================================
    // SHOW CUSTOM MODEL INPUT
    // ============================================================

    function showCustomModelInput() {

        if (!modelSelect) {
            return;
        }


        // Hide dropdown

        modelSelect.style.display =
            "none";

        modelSelect.disabled =
            true;


        // Show textbox

        if (modelOtherInput) {

            modelOtherInput.style.display =
                "block";

            modelOtherInput.disabled =
                false;

            modelOtherInput.value = "";

            modelOtherInput.focus();

        }


        // Update help text

        if (modelHelpText) {

            modelHelpText.textContent =
                "Please enter your vehicle's model.";

        }

    }



    // ============================================================
    // SHOW NORMAL MODEL DROPDOWN
    // ============================================================

    function showNormalModelDropdown() {

        if (!modelSelect) {
            return;
        }


        modelSelect.style.display =
            "block";

        modelSelect.disabled =
            false;


        if (modelOtherInput) {

            modelOtherInput.style.display =
                "none";

            modelOtherInput.disabled =
                true;

            modelOtherInput.value = "";

        }


        if (modelHelpText) {

            modelHelpText.textContent =
                "Can't find your model? Select Other and enter it manually.";

        }

    }



    // ============================================================
    // UPDATE CAR MODEL FIELD
    //
    // IMPORTANT:
    //
    // Every brand gets an "Other" option.
    //
    // Example:
    //
    // Toyota
    //   ↓
    // Vios
    // Fortuner
    // Wigo
    // Other
    //
    // Selecting Other changes the dropdown into a textbox.
    // ============================================================

    function updateModelDropdown() {

        if (
            !brandSelect ||
            !modelSelect
        ) {

            return;

        }


        const selectedBrand =
            brandSelect.value;


        // ========================================================
        // RESET
        // ========================================================

        showNormalModelDropdown();


        modelSelect.innerHTML = "";


        // ========================================================
        // NO BRAND SELECTED
        // ========================================================

        if (!selectedBrand) {

            modelSelect.disabled =
                true;


            modelSelect.innerHTML =
                '<option value="">Select a brand first</option>';


            if (modelHelpText) {

                modelHelpText.textContent =
                    "Models are based on vehicles commonly sold/registered in the Philippines.";

            }


            return;

        }


        // ========================================================
        // BRAND = OTHER
        //
        // If brand itself is Other, there is no model dropdown.
        // The user enters the model manually.
        // ========================================================

        if (
            selectedBrand === "Other"
        ) {

            showCustomModelInput();


            if (modelHelpText) {

                modelHelpText.textContent =
                    "Please enter your vehicle's model.";

            }


            return;

        }


        // ========================================================
        // NORMAL BRAND
        // ========================================================

        modelSelect.disabled =
            false;


        // Placeholder

        const placeholder =
            document.createElement(
                "option"
            );


        placeholder.value =
            "";


        placeholder.textContent =
            "Select model";


        modelSelect.appendChild(
            placeholder
        );


        // ========================================================
        // GET MODELS
        // ========================================================

        const models =
            philippineCarModels[
                selectedBrand
            ] || [];


        // ========================================================
        // ADD MODELS
        // ========================================================

        models.forEach(
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


        // ========================================================
        // ADD "OTHER"
        //
        // THIS IS THE IMPORTANT NEW PART.
        // ========================================================

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


        // ========================================================
        // HELP TEXT
        // ========================================================

        if (modelHelpText) {

            modelHelpText.textContent =
                "Can't find your model? Select Other and enter it manually.";

        }

    }



    // ============================================================
    // BRAND CHANGE EVENT
    // ============================================================

    if (brandSelect) {

        brandSelect.addEventListener(
            "change",
            updateModelDropdown
        );


        // Initialize model dropdown

        updateModelDropdown();

    }



    // ============================================================
    // MODEL CHANGE EVENT
    //
    // Detects:
    //
    // Model = Other
    //
    // and changes the dropdown into a textbox.
    // ============================================================

    if (modelSelect) {

        modelSelect.addEventListener(
            "change",
            function () {

                const selectedModel =
                    modelSelect.value;


                // ==================================================
                // OTHER MODEL
                // ==================================================

                if (
                    selectedModel === "Other"
                ) {

                    showCustomModelInput();

                }


                // ==================================================
                // NORMAL MODEL
                // ==================================================

                else {

                    showNormalModelDropdown();


                    // Keep the selected normal model

                    if (selectedModel) {

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

        if (!savedSelect) {

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
                                "Bearer " + token
                        }
                    }
                );


            const data =
                await response.json();


            if (
                data.status === "success" &&
                data.vehicles &&
                data.vehicles.length > 0
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
                data.status === "success"
            ) {

                busyDates =
                    data.busy_dates || [];

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
                        dateString.split("-");


                    const year =
                        Number(parts[0]);


                    const month =
                        Number(parts[1]);


                    const day =
                        Number(parts[2]);


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


        if (!appointmentDate) {

            return;

        }


        flatpickr(
            appointmentDate,
            {

                minDate: "today",

                dateFormat: "Y-m-d",

                disableMobile: true,

                disable: disableDates,


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
                                date.getMonth() + 1
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


                    if (savedSelect) {

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


                let vehicleDetails = {};


                // ==================================================
                // CHECK SAVED VEHICLE
                // ==================================================

                const isUsingSavedVehicle =
                    savedSection &&
                    savedSection.style.display !==
                        "none" &&
                    savedSelect &&
                    savedSelect.value !== "";


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
                // CONTACT DETAILS (name / contact number / address)
                //
                // This page doesn't currently render the "Your
                // Details" contact/address form (#manualContactForm),
                // so window.getSelectedContactDetails is only
                // defined when that section exists elsewhere.
                // Guard against it being missing so the vehicle
                // form can still submit.
                // ==================================================

                if (
                    typeof getSelectedContactDetails ===
                        "function"
                ) {

                    const contactInfo =
                        getSelectedContactDetails();

                    if (
                        !contactInfo.full_name ||
                        !contactInfo.address_line1 ||
                        !contactInfo.city ||
                        !contactInfo.province ||
                        !contactInfo.zip_code
                    ) {

                        Swal.fire(
                            "Missing Details",
                            "Please complete your name and full address before continuing.",
                            "warning"
                        );

                        return;

                    }

                    if (
                        !/^9\d{9}$/.test(
                            contactInfo.contact_digits
                        )
                    ) {

                        Swal.fire(
                            "Invalid Contact Number",
                            "Please enter a valid 10-digit mobile number (e.g. 9XXXXXXXXX).",
                            "warning"
                        );

                        return;

                    }

                    if (
                        !/^\d{4}$/.test(
                            contactInfo.zip_code
                        )
                    ) {

                        Swal.fire(
                            "Invalid ZIP Code",
                            "Please enter a valid 4-digit ZIP code.",
                            "warning"
                        );

                        return;

                    }

                    vehicleDetails.customerName =
                        contactInfo.full_name;

                    vehicleDetails.contact =
                        "+63 " + contactInfo.contact_digits;

                    vehicleDetails.address =
                        [
                            contactInfo.address_line1,
                            contactInfo.address_line2,
                            contactInfo.city,
                            contactInfo.province,
                            contactInfo.zip_code
                        ]
                            .filter(Boolean)
                            .join(", ");


                    // ==============================================
                    // OPTIONALLY SAVE AS A NEW PROFILE
                    // ==============================================

                    maybeSaveContactProfile(
                        contactInfo
                    );

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


    // ============================================================
    // YOUR DETAILS — SAVED CONTACT / ADDRESS PROFILES
    //
    // Same "saved list + add new" pattern as the vehicle garage
    // above, but for the customer's name, contact number and
    // delivery/contact address (used on the "Your Details" step).
    // ============================================================

    (function initContactProfiles() {

        const manualContactForm =
            document.getElementById(
                "manualContactForm"
            );

        if (!manualContactForm) {

            return;

        }

        function enforceDigitsOnly(input, maxLen) {

            if (!input) return;

            input.addEventListener(
                "input",
                function () {

                    this.value =
                        this.value
                            .replace(/\D/g, "")
                            .slice(0, maxLen);

                }
            );

            input.addEventListener(
                "keydown",
                function (event) {

                    const allowedKeys = [
                        "Backspace", "Delete", "ArrowLeft",
                        "ArrowRight", "ArrowUp", "ArrowDown",
                        "Tab", "Home", "End"
                    ];

                    if (
                        allowedKeys.includes(event.key) ||
                        /^\d$/.test(event.key)
                    ) {

                        return;

                    }

                    event.preventDefault();

                }
            );

            input.addEventListener(
                "paste",
                function (event) {

                    event.preventDefault();

                    const pasted =
                        (event.clipboardData || window.clipboardData)
                            .getData("text");

                    const digits =
                        pasted
                            .replace(/\D/g, "")
                            .slice(0, maxLen);

                    document.execCommand(
                        "insertText",
                        false,
                        digits
                    );

                }
            );

        }

        enforceDigitsOnly(
            document.getElementById("contactNumber"),
            10
        );

        enforceDigitsOnly(
            document.getElementById("contactZip"),
            4
        );

        function prefillFromAccount() {

            const user =
                JSON.parse(
                    localStorage.getItem("user") || "{}"
                );

            const nameInput =
                document.getElementById("contactFullName");

            if (nameInput && user.full_name) {

                nameInput.value =
                    user.full_name.trim();

            }

            const contactInput =
                document.getElementById("contactNumber");

            if (contactInput && user.phone) {

                const digits =
                    String(user.phone).replace(/\D/g, "");

                contactInput.value =
                    digits.startsWith("63")
                        ? digits.slice(2)
                        : digits.slice(-10);

            }

            const address1Input =
                document.getElementById("contactAddress1");

            if (address1Input && user.address) {

                address1Input.value =
                    user.address.trim();

            }

        }

        const saveAsProfileCheck =
            document.getElementById("saveAsContactProfile");

        const profileLabelWrap =
            document.getElementById("contactProfileLabelWrap");

        if (saveAsProfileCheck && profileLabelWrap) {

            saveAsProfileCheck.addEventListener(
                "change",
                function () {

                    profileLabelWrap.style.display =
                        this.checked ? "block" : "none";

                }
            );

        }

        // Expose a getter so the vehicle form submit handler above
        // can read the currently entered contact details.
        window.getSelectedContactDetails = function () {

            return {
                full_name:
                    (document.getElementById("contactFullName").value || "").trim(),
                contact_digits:
                    (document.getElementById("contactNumber").value || "").trim(),
                address_line1:
                    (document.getElementById("contactAddress1").value || "").trim(),
                address_line2:
                    (document.getElementById("contactAddress2").value || "").trim(),
                city:
                    (document.getElementById("contactCity").value || "").trim(),
                province:
                    (document.getElementById("contactProvince").value || "").trim(),
                zip_code:
                    (document.getElementById("contactZip").value || "").trim()
            };

        };

        window.maybeSaveContactProfile = function (contactInfo) {

            const saveCheck =
                document.getElementById("saveAsContactProfile");

            if (!saveCheck || !saveCheck.checked) return;

            const token =
                localStorage.getItem("jwt_token");

            if (!token) return;

            const labelInput =
                document.getElementById("contactProfileLabel");

            fetch("api/add_profile.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Authorization": "Bearer " + token
                },
                body: JSON.stringify({
                    label: labelInput ? labelInput.value.trim() : "",
                    full_name: contactInfo.full_name,
                    contact_number: contactInfo.contact_digits,
                    address_line1: contactInfo.address_line1,
                    address_line2: contactInfo.address_line2,
                    city: contactInfo.city,
                    province: contactInfo.province,
                    zip_code: contactInfo.zip_code
                })
            }).catch(function (err) {

                console.warn(
                    "Could not save profile:",
                    err
                );

            });

        };

        const user =
            JSON.parse(
                localStorage.getItem("user") || "{}"
            );

        if (user.full_name || user.address) {

            prefillFromAccount();

        }

    })();

});