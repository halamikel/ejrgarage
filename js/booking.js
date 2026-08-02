document.addEventListener("DOMContentLoaded", function () {

  function isLoggedIn() {
    return typeof userLoggedIn !== 'undefined' && userLoggedIn === true;
  }

  // ==========================================
  // STEP 1: SERVICE SELECTION (booking.php)
  // ==========================================
  const nextBtn = document.querySelector(".btn-next");
  const serviceError = document.getElementById("serviceError");
  const serviceLimitError = document.getElementById("serviceLimitError");

  let selectedServices = [];
  let authModal = null;
  let step1Initialized = false;

  function initServiceSelection() {
    // booking.php loads .service-item elements via an async fetch to
    // api/get_services.php, which resolves AFTER DOMContentLoaded has
    // already fired. So this re-queries the DOM each time it's called
    // (on load, and again on the 'ejrServicesLoaded' event) instead of
    // relying on a stale NodeList captured before the elements existed.
    const services = document.querySelectorAll(".service-item");

    if (services.length === 0 || !nextBtn || document.getElementById("vehicleForm")) {
      return;
    }

    if (!authModal) {
      authModal = document.createElement("div");
      authModal.id = "authModal";
      authModal.style.cssText = "display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); align-items: center; justify-content: center;";

      authModal.innerHTML = `
        <div style="background: #ffffff; border-radius: 16px; width: 90%; max-width: 420px; box-shadow: 0 15px 40px rgba(0,0,0,0.3); overflow: hidden; position: relative; border: 1px solid #e3e8ef; text-align: center; padding: 40px 30px;">
          <button onclick="document.getElementById('authModal').style.display='none'" style="position: absolute; top: 15px; right: 20px; background: none; border: none; font-size: 28px; cursor: pointer; color: #a0aab5;">&times;</button>
          <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(230, 36, 41, 0.1); color: #e62429; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; font-size: 32px;">
            <i class="bi bi-shield-lock-fill"></i>
          </div>
          <h3 style="font-family: 'Rajdhani', sans-serif; font-weight: 700; color: #1e2a36; margin-bottom: 10px; font-size: 1.8rem; text-transform: uppercase;">Login Required</h3>
          <p style="color: #5f6b7a; margin-bottom: 30px; font-size: 0.95rem; line-height: 1.6;">You must be logged in to book an appointment with EJR Garage.</p>
          <div style="display: flex; gap: 12px; flex-direction: column;">
            <a href="login.php" style="width: 100%; background: linear-gradient(45deg, #e62429, #ff6b00); color: #fff; padding: 12px; border-radius: 8px; font-family: 'Rajdhani', sans-serif; font-weight: 700; text-decoration: none; text-transform: uppercase;">Log In Here</a>
            <a href="registration.php" style="width: 100%; padding: 12px; border: 1px solid #e3e8ef; border-radius: 8px; color: #1e2a36; font-family: 'Rajdhani', sans-serif; font-weight: 700; text-decoration: none; text-transform: uppercase;">Register Now</a>
          </div>
        </div>
      `;

      document.body.appendChild(authModal);
      window.addEventListener('click', (e) => { if (e.target == authModal) authModal.style.display = "none"; });
    }

    services.forEach(service => {
      service.addEventListener("click", function () {
        const serviceName = this.textContent.trim();
        if (serviceError) serviceError.style.display = "none";
        if (serviceLimitError) serviceLimitError.style.display = "none";

        if (this.classList.contains("selected")) {
          this.classList.remove("selected");
          selectedServices = selectedServices.filter(s => s !== serviceName);
        } else {
          if (selectedServices.length >= 5) {
            if (serviceLimitError) serviceLimitError.style.display = "block";
            return;
          }
          this.classList.add("selected");
          selectedServices.push(serviceName);
        }
      });
    });

    if (!step1Initialized) {
      nextBtn.addEventListener("click", function (e) {
        e.preventDefault();
        if (selectedServices.length === 0) {
          if (serviceError) serviceError.style.display = "block";
          return;
        }
        if (!isLoggedIn()) {
          document.getElementById('authModal').style.display = "flex";
          return;
        }
        localStorage.setItem("selectedServices", JSON.stringify(selectedServices));
        window.location.href = "booking-details.php";
      });
      step1Initialized = true;
    }
  }

  // Try immediately (covers any server-rendered .service-item markup),
  // and again once booking.php's async fetch injects the real ones.
  initServiceSelection();
  document.addEventListener('ejrServicesLoaded', initServiceSelection);

  // ==========================================
  // STEP 2: VEHICLE DETAILS (booking-details.php)
  // ==========================================
  const vehicleForm = document.getElementById("vehicleForm");
  const yearSelect = document.getElementById("year");
  const savedSection = document.getElementById("savedVehiclesSection");
  const manualForm = document.getElementById("manualVehicleForm");
  const savedSelect = document.getElementById("savedVehicleSelect");

  if (yearSelect) {
    const currentYear = new Date().getFullYear();
    for (let year = currentYear; year >= 1990; year--) {
      const option = document.createElement("option");
      option.value = year;
      option.textContent = year;
      yearSelect.appendChild(option);
    }
  }

  async function loadUserGarage() {
    if (!savedSelect) return;
    const token = localStorage.getItem("jwt_token");

    try {
      const response = await fetch('api/get_vehicles.php', {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const data = await response.json();

      if (data.status === "success" && data.vehicles.length > 0) {
        savedSection.style.display = "block";
        manualForm.style.display = "none";
        if(document.getElementById("backToSavedContainer")) {
            document.getElementById("backToSavedContainer").style.display = "block";
        }

        savedSelect.innerHTML = '<option value="">-- Select Saved Vehicle --</option>';
        data.vehicles.forEach(v => {
          const opt = document.createElement("option");
          opt.value = JSON.stringify(v);
          opt.textContent = `${v.brand} ${v.model} (${v.plate_number})`;
          savedSelect.appendChild(opt);
        });
      }
    } catch (err) {
      console.error("Garage load failed:", err);
    }
  }

  // ==========================================
  // FLATPICKR CALENDAR WITH BUSY DATES
  // ==========================================
  async function initCalendar() {
    let busyDates = [];

    try {
      const res = await fetch('api/get_busy_dates.php');
      const data = await res.json();
      if (data.status === 'success') {
        // Store raw date strings e.g. ["2026-04-28"]
        busyDates = data.busy_dates;
      }
    } catch (err) {
      console.error("Failed to load busy dates:", err);
    }

    // ✅ FIX: Parse each busy date as LOCAL midnight to avoid UTC timezone shift
    const disableDates = busyDates.map(dateStr => {
      const [year, month, day] = dateStr.split("-").map(Number);
      return new Date(year, month - 1, day); // local time, no UTC offset
    });

    flatpickr("#appointmentDate", {
      minDate: "today",
      dateFormat: "Y-m-d",
      disableMobile: true,
      disable: disableDates,

      onDayCreate: function(dObj, dStr, fp, dayElem) {
        // ✅ FIX: Build date string from local date, not UTC
        const date = dayElem.dateObj;
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, "0");
        const d = String(date.getDate()).padStart(2, "0");
        const dateStr = `${y}-${m}-${d}`;

        if (busyDates.includes(dateStr)) {
          // Style as red
          dayElem.style.backgroundColor = "#e62429";
          dayElem.style.color = "#ffffff";
          dayElem.style.borderRadius = "50%";
          dayElem.style.opacity = "1";
          // ✅ FIX: Make fully booked dates non-clickable
          dayElem.style.pointerEvents = "none";
          dayElem.style.cursor = "not-allowed";
          dayElem.title = "Fully booked";
        }
      }
    });
  }

  if (vehicleForm) {
    loadUserGarage();

    // Initialize flatpickr calendar
    initCalendar();

    document.getElementById("btnAddNewManual")?.addEventListener("click", () => {
      manualForm.style.display = "block";
      savedSection.style.display = "none";
      savedSelect.value = ""; 
    });

    document.getElementById("btnBackToSaved")?.addEventListener("click", () => {
      manualForm.style.display = "none";
      savedSection.style.display = "block";
    });

    vehicleForm.addEventListener("submit", function (e) {
      e.preventDefault();
      
      let vehicleDetails = {};
      const isUsingSaved = savedSection && savedSection.style.display !== "none" && savedSelect.value !== "";

      if (isUsingSaved) {
        const v = JSON.parse(savedSelect.value);
        vehicleDetails = {
          brand: v.brand,
          model: v.model,
          year: v.year_model,
          plate: v.plate_number,
          transmission: v.transmission,
          fuel: v.fuel_type,
          vehicle_id: v.id
        };
      } else {
        vehicleDetails = {
          brand: document.getElementById("brand").value,
          model: document.getElementById("model").value,
          year: document.getElementById("year").value,
          plate: document.getElementById("plate").value,
          transmission: document.getElementById("transmission").value,
          fuel: document.getElementById("fuel").value
        };

        if (!vehicleDetails.brand || !vehicleDetails.plate) {
          if(document.getElementById("errorMsg")) document.getElementById("errorMsg").style.display = "block";
          return;
        }
      }

      vehicleDetails.appointmentDate = document.getElementById("appointmentDate").value;
      vehicleDetails.notes = document.getElementById("notes").value;

      if(!vehicleDetails.appointmentDate) {
          alert("Please select an appointment date.");
          return;
      }

      localStorage.setItem("vehicleDetails", JSON.stringify(vehicleDetails));
      window.location.href = "booking-confirmation.php";
    });
  }
});