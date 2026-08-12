document.addEventListener("DOMContentLoaded", function () {

  // ============================================================
  // CHECK LOGIN STATUS
  // ============================================================

  function isLoggedIn() {
    return (
      typeof userLoggedIn !== "undefined" &&
      userLoggedIn === true
    );
  }


  // ============================================================
  // STEP 1: SERVICE SELECTION
  // booking.php
  // ============================================================

  const nextBtn = document.querySelector(".btn-next");

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


    /*
     * Do not initialize service selection on
     * booking-details.php.
     */

    if (
      services.length === 0 ||
      !nextBtn ||
      document.getElementById("vehicleForm")
    ) {
      return;
    }


    // ==========================================================
    // LOGIN MODAL
    // ==========================================================

    if (!authModal) {

      authModal =
        document.createElement("div");

      authModal.id = "authModal";

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
              font-family:'Rajdhani', sans-serif;
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
              "
            >
              LOGIN
            </a>

          </div>

        </div>

      `;

      document.body.appendChild(authModal);

    }


    // ==========================================================
    // SERVICE SELECTION
    // ==========================================================

    services.forEach(function (service) {

      service.addEventListener("click", function () {

        const serviceName =
          this.dataset.service ||
          this.querySelector(".service-name")?.textContent.trim();


        if (
          this.classList.contains("selected")
        ) {

          this.classList.remove("selected");

          selectedServices =
            selectedServices.filter(
              function (s) {
                return s !== serviceName;
              }
            );

        } else {

          if (selectedServices.length >= 5) {

            if (serviceLimitError) {
              serviceLimitError.style.display = "block";
            }

            return;
          }

          this.classList.add("selected");

          selectedServices.push(serviceName);

        }

      });

    });


    // ==========================================================
    // NEXT BUTTON
    // ==========================================================

    if (!step1Initialized) {

      nextBtn.addEventListener("click", function (e) {

        e.preventDefault();


        if (selectedServices.length === 0) {

          if (serviceError) {
            serviceError.style.display = "block";
          }

          return;
        }


        if (!isLoggedIn()) {

          if (authModal) {
            authModal.style.display = "flex";
          }

          return;
        }


        localStorage.setItem(
          "selectedServices",
          JSON.stringify(selectedServices)
        );


        window.location.href =
          "booking-details.php";

      });


      step1Initialized = true;

    }

  }


  initServiceSelection();


  document.addEventListener(
    "ejrServicesLoaded",
    initServiceSelection
  );



  // ============================================================
  // STEP 2: VEHICLE DETAILS
  // booking-details.php
  // ============================================================

  const vehicleForm =
    document.getElementById("vehicleForm");

  const yearSelect =
    document.getElementById("year");

  const savedSection =
    document.getElementById("savedVehiclesSection");

  const manualForm =
    document.getElementById("manualVehicleForm");

  const savedSelect =
    document.getElementById("savedVehicleSelect");

  const brandSelect =
    document.getElementById("brand");

  const modelSelect =
    document.getElementById("model");



  // ============================================================
  // PHILIPPINE CAR MODELS
  // ============================================================
  //
  // These are brands/models commonly sold, used, or encountered
  // in the Philippines.
  //
  // The list includes current and older models because customers
  // may bring older vehicles to the garage.
  //
  // ============================================================

  const philippineCarModels = {


    // ==========================================================
    // TOYOTA
    // ==========================================================

    "Toyota": [

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



    // ==========================================================
    // HONDA
    // ==========================================================

    "Honda": [

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



    // ==========================================================
    // MITSUBISHI
    // ==========================================================

    "Mitsubishi": [

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



    // ==========================================================
    // NISSAN
    // ==========================================================

    "Nissan": [

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



    // ==========================================================
    // FORD
    // ==========================================================

    "Ford": [

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



    // ==========================================================
    // SUZUKI
    // ==========================================================

    "Suzuki": [

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



    // ==========================================================
    // ISUZU
    // ==========================================================

    "Isuzu": [

      "Crosswind",
      "D-Max",
      "mu-X",
      "Traviz",
      "Alterra",
      "Trooper"

    ],



    // ==========================================================
    // HYUNDAI
    // ==========================================================

    "Hyundai": [

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



    // ==========================================================
    // KIA
    // ==========================================================

    "Kia": [

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



    // ==========================================================
    // MAZDA
    // ==========================================================

    "Mazda": [

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

    ],



    // ==========================================================
    // OTHER
    // ==========================================================

    "Other": [

      "Other / Not Listed"

    ]

  };



  // ============================================================
  // UPDATE CAR MODEL DROPDOWN
  // ============================================================

  function updateModelDropdown() {

    if (!brandSelect || !modelSelect) {
      return;
    }


    const selectedBrand =
      brandSelect.value;


    const models =
      philippineCarModels[selectedBrand] || [];


    // Remove old models

    modelSelect.innerHTML = "";



    // ==========================================================
    // NO BRAND SELECTED
    // ==========================================================

    if (!selectedBrand) {

      modelSelect.disabled = true;

      modelSelect.innerHTML = `
        <option value="">
          Select a brand first
        </option>
      `;

      return;
    }



    // ==========================================================
    // ENABLE MODEL DROPDOWN
    // ==========================================================

    modelSelect.disabled = false;



    // Placeholder

    const placeholder =
      document.createElement("option");

    placeholder.value = "";

    placeholder.textContent =
      "Select model";

    modelSelect.appendChild(
      placeholder
    );



    // ==========================================================
    // ADD MODELS
    // ==========================================================

    models.forEach(function (model) {

      const option =
        document.createElement("option");

      option.value = model;

      option.textContent = model;

      modelSelect.appendChild(
        option
      );

    });

  }



  // ============================================================
  // BRAND CHANGE EVENT
  // ============================================================

  if (brandSelect && modelSelect) {

    brandSelect.addEventListener(
      "change",
      updateModelDropdown
    );


    // Initialize when page loads

    updateModelDropdown();

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
        document.createElement("option");

      option.value = year;

      option.textContent = year;

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
      localStorage.getItem("jwt_token");


    try {

      const response =
        await fetch(
          "api/get_vehicles.php",
          {
            headers: {
              "Authorization":
                `Bearer ${token}`
            }
          }
        );


      const data =
        await response.json();


      if (
        data.status === "success" &&
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


        if (backToSavedContainer) {

          backToSavedContainer.style.display =
            "block";

        }


        savedSelect.innerHTML =
          `
            <option value="">
              -- Select Saved Vehicle --
            </option>
          `;


        data.vehicles.forEach(
          function (v) {

            const option =
              document.createElement(
                "option"
              );


            option.value =
              JSON.stringify(v);


            option.textContent =
              `${v.brand} ${v.model} (${v.plate_number})`;


            savedSelect.appendChild(
              option
            );

          }
        );

      }

    } catch (error) {

      console.error(
        "Garage load failed:",
        error
      );

    }

  }



  // ============================================================
  // FLATPICKR CALENDAR
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
          data.busy_dates;

      }

    } catch (error) {

      console.error(
        "Failed to load busy dates:",
        error
      );

    }



    // ==========================================================
    // CONVERT BUSY DATES TO LOCAL DATES
    // ==========================================================

    const disableDates =
      busyDates.map(
        function (dateString) {

          const [
            year,
            month,
            day
          ] =
            dateString
              .split("-")
              .map(Number);


          return new Date(
            year,
            month - 1,
            day
          );

        }
      );



    // ==========================================================
    // INITIALIZE FLATPICKR
    // ==========================================================

    flatpickr(
      "#appointmentDate",
      {

        minDate: "today",

        dateFormat: "Y-m-d",

        disableMobile: true,

        disable: disableDates,


        onDayCreate:
          function (
            dObj,
            dStr,
            fp,
            dayElem
          ) {

            const date =
              dayElem.dateObj;


            const year =
              date.getFullYear();


            const month =
              String(
                date.getMonth() + 1
              ).padStart(2, "0");


            const day =
              String(
                date.getDate()
              ).padStart(2, "0");


            const dateString =
              `${year}-${month}-${day}`;


            if (
              busyDates.includes(
                dateString
              )
            ) {

              dayElem.style.backgroundColor =
                "#e62429";

              dayElem.style.color =
                "#ffffff";

              dayElem.style.borderRadius =
                "50%";

              dayElem.style.opacity =
                "1";

              dayElem.style.pointerEvents =
                "none";

              dayElem.style.cursor =
                "not-allowed";

              dayElem.title =
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

    // Load saved vehicles

    loadUserGarage();


    // Initialize calendar

    initCalendar();



    // ==========================================================
    // USE DIFFERENT VEHICLE
    // ==========================================================

    const addNewButton =
      document.getElementById(
        "btnAddNewManual"
      );


    if (addNewButton) {

      addNewButton.addEventListener(
        "click",
        function () {

          manualForm.style.display =
            "block";

          savedSection.style.display =
            "none";

          savedSelect.value = "";

        }
      );

    }



    // ==========================================================
    // BACK TO SAVED VEHICLES
    // ==========================================================

    const backButton =
      document.getElementById(
        "btnBackToSaved"
      );


    if (backButton) {

      backButton.addEventListener(
        "click",
        function () {

          manualForm.style.display =
            "none";

          savedSection.style.display =
            "block";

        }
      );

    }



    // ==========================================================
    // VEHICLE FORM SUBMIT
    // ==========================================================

    vehicleForm.addEventListener(
      "submit",
      function (e) {

        e.preventDefault();


        let vehicleDetails = {};



        // ======================================================
        // CHECK IF USING SAVED VEHICLE
        // ======================================================

        const isUsingSaved =
          savedSection &&
          savedSection.style.display !== "none" &&
          savedSelect.value !== "";



        // ======================================================
        // SAVED VEHICLE
        // ======================================================

        if (isUsingSaved) {

          const vehicle =
            JSON.parse(
              savedSelect.value
            );


          vehicleDetails = {

            brand:
              vehicle.brand,

            model:
              vehicle.model,

            year:
              vehicle.year_model,

            plate:
              vehicle.plate_number,

            transmission:
              vehicle.transmission,

            fuel:
              vehicle.fuel_type,

            vehicle_id:
              vehicle.id

          };

        }



        // ======================================================
        // MANUAL VEHICLE
        // ======================================================

        else {

          vehicleDetails = {

            brand:
              document
                .getElementById("brand")
                .value,

            model:
              document
                .getElementById("model")
                .value,

            year:
              document
                .getElementById("year")
                .value,

            plate:
              document
                .getElementById("plate")
                .value,

            transmission:
              document
                .getElementById("transmission")
                .value,

            fuel:
              document
                .getElementById("fuel")
                .value

          };



          // ====================================================
          // VALIDATE VEHICLE
          // ====================================================

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


            if (errorMessage) {

              errorMessage.style.display =
                "block";

            }


            return;

          }

        }



        // ======================================================
        // APPOINTMENT DATE
        // ======================================================

        vehicleDetails.appointmentDate =
          document
            .getElementById(
              "appointmentDate"
            )
            .value;



        // ======================================================
        // NOTES
        // ======================================================

        vehicleDetails.notes =
          document
            .getElementById(
              "notes"
            )
            .value;



        // ======================================================
        // VALIDATE DATE
        // ======================================================

        if (
          !vehicleDetails.appointmentDate
        ) {

          alert(
            "Please select an appointment date."
          );

          return;

        }



        // ======================================================
        // SAVE VEHICLE DETAILS
        // ======================================================

        localStorage.setItem(
          "vehicleDetails",
          JSON.stringify(
            vehicleDetails
          )
        );



        // ======================================================
        // GO TO CONFIRMATION
        // ======================================================

        window.location.href =
          "booking-confirmation.php";

      }
    );

  }

});