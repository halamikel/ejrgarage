// Don't touch this line of code.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('a[href*="logout"]').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            localStorage.clear();
            window.location.href = '/api/logout.php';
        });
    });
});


// ==========================================
// --- HELPER FUNCTIONS ---
// ==========================================

function showFieldError(input, message) {
    input.classList.add('error');
    let parent = input.parentNode;

    // Check if it's the password field with the eye icon
    if (input.classList.contains('has-eye')) {
        parent = input.parentNode.parentNode; 
    }

    let errorEl = parent.querySelector('.field-error');
    if (!errorEl) {
        errorEl = document.createElement('div');
        errorEl.className = 'field-error';
        parent.appendChild(errorEl);
    }
    
    errorEl.textContent = message;
    errorEl.style.display = 'block';
}

function clearFieldError(input) {
    input.classList.remove('error');
    let parent = input.parentNode;

    if (input.classList.contains('has-eye')) {
        parent = input.parentNode.parentNode;
    }

    const errorEl = parent.querySelector('.field-error');
    if (errorEl) {
        errorEl.style.display = 'none';
        errorEl.textContent = '';
    }
}

/**
 * Professional UI Toast Notification
 */
function showToast(message, color = "#40c060") {
    const toastEl = document.getElementById('authToast');
    const msgEl = document.getElementById('toastMsg');

    if (toastEl && msgEl && typeof bootstrap !== 'undefined') {
        const iconEl = toastEl.querySelector('.bi-wrench-adjustable');

        toastEl.style.backgroundColor = "#ffffff";
        toastEl.style.borderLeft = `5px solid ${color}`;
        toastEl.style.borderRadius = "8px";
        toastEl.style.boxShadow = "0 4px 12px rgba(0,0,0,0.15)";

        if (iconEl) iconEl.style.color = color;
        msgEl.textContent = message;

        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        return;
    }

    // Fallback: simple #toast div (present on login.php / registration.php)
    const simpleToast = document.getElementById('toast');
    if (simpleToast) {
        simpleToast.textContent = message;
        simpleToast.style.background = color;
        simpleToast.classList.add('show');
        setTimeout(() => simpleToast.classList.remove('show'), 3200);
        return;
    }

    console.warn('showToast: no toast element found on this page.', message);
}

// ==========================================
// --- LOGIN LOGIC ---
// ==========================================

function handleLogin() {
    const emailInput = document.getElementById('l-email');
    const pwInput    = document.getElementById('l-pw');
    const rememberMe = document.getElementById('rememberMe');

    if (!emailInput || !pwInput) return;

    const email    = emailInput.value.trim();
    const pw       = pwInput.value;
    const remember = rememberMe ? rememberMe.checked : false;

    clearFieldError(emailInput);
    clearFieldError(pwInput);

    let hasError = false;
    if (!email) { showFieldError(emailInput, 'Email is required.'); hasError = true; }
    if (!pw)    { showFieldError(pwInput, 'Password is required.'); hasError = true; }
    if (hasError) return;

    const btn = document.getElementById('loginBtn');
    btn.textContent = 'SIGNING IN…';
    btn.disabled = true;

    fetch('api/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email, password: pw })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            btn.textContent = '✓ WELCOME BACK!';
            btn.style.background = '#40c060';

            if (remember) {
                localStorage.setItem('remembered_email', email);
                localStorage.setItem('remembered_pw', pw);
                localStorage.setItem('remember_me', '1');
            } else {
                localStorage.removeItem('remembered_email');
                localStorage.removeItem('remembered_pw');
                localStorage.removeItem('remember_me');
            }

            localStorage.setItem("jwt_token", data.token);
            localStorage.setItem("session_token", data.session_token);
            localStorage.setItem("user", JSON.stringify(data.user));
            setTimeout(() => window.location.href = 'index.php', 1000);
        } else {
            // Show error on the specific field if the API tells us which one
            if (data.field === 'email') {
                showFieldError(emailInput, data.message);
            } else {
                // Wrong password — show under password field
                showFieldError(pwInput, data.message || 'Invalid email or password.');
            }
            btn.textContent = 'Sign In';
            btn.disabled = false;
        }
    })
    .catch(() => {
        showToast("Server error.", "#e84040");
        btn.disabled = false;
        btn.textContent = 'Sign In';
    });
}

// ==========================================
// --- REGISTRATION LOGIC ---
// ==========================================

function handleRegister() {
    const fnameEl = document.getElementById('r-fname');
    const lnameEl = document.getElementById('r-lname');
    const emailEl = document.getElementById('r-email');
    const phoneEl = document.getElementById('r-phone');
    const pwEl    = document.getElementById('r-pw');
    const pw2El   = document.getElementById('r-pw2');
    const termsEl = document.getElementById('terms');

    if (!fnameEl || !lnameEl || !emailEl || !phoneEl || !pwEl || !pw2El || !termsEl) return;

    const fname = fnameEl.value.trim();
    const lname = lnameEl.value.trim();
    const email = emailEl.value.trim();
    // Strip any accidental non-digits before validation
    const phone = phoneEl.value.replace(/\D/g, '');
    const pw    = pwEl.value;
    const pw2   = pw2El.value;
    const terms = termsEl.checked;

    if (!fname || !lname || !email || !phone || !pw) return showToast("Fill in all fields.", "#e8a020");

    // Must be exactly 10 digits starting with 9 (because +63 prefix is shown separately)
    if (!/^9\d{9}$/.test(phone)) {
        return showToast("Enter a valid phone number starting with 9 (10 digits).", "#e84040");
    }

    if (pw !== pw2) return showToast("Passwords do not match.", "#e84040");
    if (!terms) return showToast("Agree to terms.", "#e84040");

    const btn = document.getElementById('regBtn');
    btn.textContent = 'CREATING ACCOUNT…';
    btn.disabled = true;

    fetch('api/register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: fname + " " + lname, email: email, phone: phone, password: pw })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showToast('Account created! Verification Email Sent.');
            setTimeout(() => window.location.href = 'login.php', 2000);
        } else {
            showToast(data.message, "#e84040");
            btn.textContent = 'Create My Account';
            btn.disabled = false;
        }
    });
}

// ==========================================
// --- FORGOT PASSWORD LOGIC ---
// ==========================================

function handleReset() {
    const emailInput = document.getElementById('f-email');
    if (!emailInput) return;

    const email = emailInput.value.trim();
    const btn = document.getElementById('resetBtn');

    if (!email) {
        return showToast("Please enter your email address.", "#e8a020");
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> SENDING...';

    fetch('api/forgot_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            showToast(data.message, "#40c060"); 
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 3000);
        } else {
            showToast(data.message, "#e84040"); 
            btn.disabled = false;
            btn.innerHTML = 'Send Reset Link';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast("Server connection failed.", "#e84040");
        btn.disabled = false;
        btn.innerHTML = 'Send Reset Link';
    });
}

// ==========================================
// --- PASSWORD STRENGTH LOGIC ---
// ==========================================

function updateStrength(value) {
    const fill = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    if (!fill || !label) return;

    let score = 0;
    if (value.length >= 8) score++;
    if (/[A-Z]/.test(value)) score++;
    if (/[0-9]/.test(value)) score++;
    if (/[^A-Za-z0-9]/.test(value)) score++;

    const colors = ['#e84040', '#e8a020', '#f5c050', '#40c060'];
    const labels = ['Weak', 'Fair', 'Good', 'Strong'];
    const idx = Math.max(0, score - 1);

    fill.style.width = value.length ? (score * 25) + '%' : '0%';
    fill.style.background = value.length ? colors[idx] : 'transparent';
    label.textContent = value.length ? labels[idx] : 'Password strength';
    label.style.color = value.length ? colors[idx] : 'inherit';
}

// ==========================================
// --- PROFESSIONAL UI HELPERS ---
// ==========================================

function showSuccessModal(message) {
    Swal.fire({
        title: 'Success!',
        text: message,
        icon: 'success',
        iconColor: '#dc3545',
        showConfirmButton: false,     
        timer: 3000,                  
        timerProgressBar: true,
        allowOutsideClick: false,
        background: '#ffffff',
        customClass: {
            popup: 'rounded-0', 
            timerProgressBar: 'bg-danger'
        },
        showClass: { 
            popup: 'animate__animated animate__fadeInDown animate__faster' 
        },
        hideClass: { 
            popup: 'animate__animated animate__fadeOutUp animate__faster' 
        },
        customClass: {
            timerProgressBar: 'bg-danger' 
        }
    });
}

function showNotification(message, type = 'success') {
    const isError = type === 'error';
    Swal.fire({
        title: isError ? 'Oops!' : 'Success!',
        text: message,
        icon: type,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        iconColor: isError ? '#f27474' : '#dc3545',
        background: '#fff',
        color: '#333'
    });
}

// ==========================================
// --- VEHICLE MANAGEMENT ---
// ==========================================

let currentVehicles = [];

async function loadVehicles() {
    const token = localStorage.getItem("jwt_token");
    const container = document.getElementById("vehicleList");
    if (!container) return;

    try {
        const response = await fetch('api/get_vehicles.php', {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (!response.ok) throw new Error("API Not Found");
        
        const data = await response.json();
        if (data.status === "success") {
            currentVehicles = data.vehicles || [];

            if (data.vehicles.length === 0) {
                container.innerHTML = '<p class="text-center text-muted py-4">No vehicles found in your garage.</p>';
                return;
            }

            container.innerHTML = data.vehicles.map(v => `
                <div class="card border-0 shadow-sm p-3 mb-3 bg-white d-flex flex-row justify-content-between align-items-center animate__animated animate__fadeIn">
                    <div>
                        <h6 class="fw-bold mb-1">${v.brand} ${v.model}</h6>
                        <p class="small text-muted mb-0">${v.plate_number} • ${v.year_model}</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button onclick="openEditVehicleModal(${v.id})" class="btn btn-sm btn-outline-secondary border-0">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button onclick="handleRemoveVehicle(${v.id})" class="btn btn-sm btn-outline-danger border-0">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }
    } catch (err) {
        container.innerHTML = `<p class="text-danger text-center py-4 small">Connection error. Please check api/get_vehicles.php.</p>`;
    }
}

function openEditVehicleModal(vehicleId) {
    const vehicle = currentVehicles.find(v => v.id == vehicleId);
    if (!vehicle) return;

    const brandSelect = document.getElementById("editBrand");
    const modelSelect = document.getElementById("editModel");
    const modelOtherInput = document.getElementById("editModelOther");

    if (!brandSelect || !modelSelect) return;

    document.getElementById("editVehicleId").value = vehicle.id;
    document.getElementById("editPlate").value = vehicle.plate_number || "";
    document.getElementById("editYear").value = vehicle.year_model || "";
    document.getElementById("editTransmission").value = vehicle.transmission || "";
    document.getElementById("editFuel").value = vehicle.fuel_type || "";

    const knownBrands = Array.from(brandSelect.options).map(o => o.value).filter(Boolean);
    brandSelect.value = knownBrands.includes(vehicle.brand) ? vehicle.brand : "Other";
    brandSelect.dispatchEvent(new Event("change"));

    if (brandSelect.value === "Other") {
        if (modelOtherInput) modelOtherInput.value = vehicle.model || "";
    } else {
        const knownModels = Array.from(modelSelect.options).map(o => o.value).filter(Boolean);
        if (knownModels.includes(vehicle.model)) {
            modelSelect.value = vehicle.model;
            modelSelect.dispatchEvent(new Event("change"));
        } else {
            modelSelect.value = "Other";
            modelSelect.dispatchEvent(new Event("change"));
            if (modelOtherInput) modelOtherInput.value = vehicle.model || "";
        }
    }

    new bootstrap.Modal(document.getElementById("editVehicleModal")).show();
}

async function handleUpdateVehicle(event) {
    event.preventDefault();
    const token = localStorage.getItem("jwt_token");
    const submitBtn = event.target.querySelector('button[type="submit"]');

    const formData = {
        id: document.getElementById("editVehicleId").value,
        brand: document.getElementById("editBrand").value,
        model: (typeof getVehicleModelValue === "function")
            ? getVehicleModelValue({ brandId: "editBrand", modelId: "editModel", modelOtherId: "editModelOther" })
            : document.getElementById("editModel").value,
        year: document.getElementById("editYear").value,
        plate: document.getElementById("editPlate").value.trim(),
        transmission: document.getElementById("editTransmission").value,
        fuel: document.getElementById("editFuel").value
    };

    if (!formData.brand || !formData.model || !formData.year || !formData.plate || !formData.transmission || !formData.fuel) {
        showNotification("Please fill in all vehicle fields before saving.", "error");
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

    try {
        const response = await fetch('api/update_vehicle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.status === "success") {
            bootstrap.Modal.getInstance(document.getElementById("editVehicleModal")).hide();
            showSuccessModal("Your vehicle details have been updated!");
            loadVehicles();
        } else {
            showNotification(result.message, "error");
        }
    } catch (error) {
        showNotification("Connection to server failed.", "error");
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = "Save Changes";
    }
}

async function handleAddVehicle(event) {
    event.preventDefault();
    const token = localStorage.getItem("jwt_token");
    const submitBtn = event.target.querySelector('button[type="submit"]');

    const formData = {
        brand: document.getElementById("brand").value,
        model: (typeof getVehicleModelValue === "function")
            ? getVehicleModelValue({ brandId: "brand", modelId: "model", modelOtherId: "modelOther" })
            : document.getElementById("model").value,
        year: document.getElementById("year").value,
        plate: document.getElementById("plate").value.trim(),
        transmission: document.getElementById("transmission").value,
        fuel: document.getElementById("fuel").value
    };

    // Same manual validation approach as the booking form's vehicle step:
    // native "required" isn't used since the model field toggles between
    // a <select> and a free-text "Other" input.
    if (!formData.brand || !formData.model || !formData.year || !formData.plate || !formData.transmission || !formData.fuel) {
        showNotification("Please fill in all vehicle fields before saving.", "error");

        const brandSelect = document.getElementById("brand");
        const modelOtherInput = document.getElementById("modelOther");
        if (!formData.model && brandSelect && (brandSelect.value === "Other" || document.getElementById("model")?.value === "Other") && modelOtherInput) {
            modelOtherInput.focus();
        }

        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

    try {
        const response = await fetch('api/add_vehicle.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();

        if (result.status === "success") {
            event.target.reset();
            const brandField = document.getElementById("brand");
            if (brandField) {
                brandField.dispatchEvent(new Event("change"));
            }
            showSuccessModal("Your car has been added to the garage!");
            loadVehicles(); 
        } else {
            showNotification(result.message, "error");
        }
    } catch (error) {
        showNotification("Connection to server failed.", "error");
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = "Add Vehicle";
    }
}

async function handleRemoveVehicle(vehicleId) {
    Swal.fire({
        title: 'Remove Vehicle?',
        text: "This will permanently delete it from your garage list.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        background: '#fff',
        borderRadius: '15px'
    }).then(async (result) => {
        if (result.isConfirmed) {
            const token = localStorage.getItem("jwt_token");
            try {
                const response = await fetch('api/remove_vehicle.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({ id: vehicleId })
                });
                
                const res = await response.json();
                if (res.status === "success") {
                    showNotification("Vehicle removed.");
                    loadVehicles();
                }
            } catch (err) {
                showNotification("Request failed.", "error");
            }
        }
    });
}

// ==========================================
// --- BOOKING & APPOINTMENTS ---
// ==========================================

async function finalizeBooking() {
    const btn = document.getElementById('confirmBtn');
    const token = localStorage.getItem("jwt_token");
    const services = JSON.parse(localStorage.getItem("selectedServices") || "[]");
    const vehicle = JSON.parse(localStorage.getItem("vehicleDetails") || "{}");

    if (!services.length || !vehicle.brand) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Details',
            text: 'Please ensure services and vehicle details are selected.',
            confirmButtonColor: '#dc3545'
        });
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> PROCESSING...';

    try {
        const response = await fetch('api/add_appointment.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}` 
            },
            body: JSON.stringify({
                services: services,
                vehicle_id: vehicle.id,
                date: vehicle.appointmentDate,
                notes: vehicle.notes
            })
        });

        const result = await response.json();

        if (result.status === "success") {
            Swal.fire({
                title: 'Booking Successful!',
                text: 'Your schedule at EJR Garage has been secured.',
                icon: 'success',
                iconColor: '#dc3545',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                willClose: () => {
                    localStorage.removeItem("selectedServices");
                    localStorage.removeItem("vehicleDetails");
                    window.location.href = 'my_appointments.php';
                }
            });
        } else {
            throw new Error(result.message || "Server rejected the booking.");
        }
    } catch (err) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: 'Booking Failed',
            text: err.message,
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true
        });
        btn.disabled = false;
        btn.textContent = 'CONFIRM APPOINTMENT';
    }
}

async function loadAppointments() {
    const token = localStorage.getItem("jwt_token");
    const upcomingBox = document.getElementById("upcomingContainer");
    const completedBox = document.getElementById("completedContainer");

    try {
        const response = await fetch('api/get_appointments.php', {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();

        if (data.status === "success") {
            let upcoming = "";
            let completed = "";

            data.appointments.forEach(app => {
                const isPending    = app.status === 'Pending';
                const isConfirmed  = app.status === 'Confirmed';
                const isCompleted  = app.status === 'Completed';
                const isCancelled  = app.status === 'Cancelled';

                let badgeClass = "bg-success";
                if (isPending)   badgeClass = "bg-warning text-dark";
                if (isConfirmed) badgeClass = "bg-success";
                if (isCancelled) badgeClass = "bg-secondary";
                if (isCompleted) badgeClass = "bg-primary";

                const vehicleDisplay = (app.brand && app.model)
                    ? `${app.brand} ${app.model}${app.year_model ? ' ' + app.year_model : ''}${app.plate_number ? ' — ' + app.plate_number : ''}`
                    : "Vehicle Details Pending";

                const replyHtml = (app.cancellation_reply || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
                const cancellationNotice = (isCancelled && replyHtml)
                    ? `<div class="small mt-2 p-2" style="background:#f8d7da;color:#842029;border-radius:8px;">
                           <i class="bi bi-reply-fill me-1"></i><strong>Message from EJR Garage:</strong> ${replyHtml}
                       </div>`
                    : '';

                const html = `
                <div class="border rounded p-3 mb-3 bg-white shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1">${app.service_type}</h6>
                            <p class="small text-muted mb-1">${vehicleDisplay}</p>
                            <p class="small text-danger mb-0">
                                <strong>Schedule:</strong> ${app.appointment_date}
                            </p>
                            ${cancellationNotice}
                        </div>
                        <div class="text-end">
                            <span class="badge ${badgeClass} mb-2 d-inline-block">${app.status}</span>
                            <br>
                            ${isPending ? `
                                <button onclick="handleCancelAppointment(${app.id})"
                                        class="btn btn-sm btn-link text-danger p-0 border-0 text-decoration-none"
                                        style="font-size:0.8rem;">
                                    <i class="bi bi-x-circle"></i> Cancel Appointment
                                </button>
                            ` : ''}
                            ${isCompleted ? `
                                <a href="service_history.php"
                                   class="btn btn-sm btn-link text-primary p-0 border-0 text-decoration-none"
                                   style="font-size:0.8rem;">
                                    <i class="bi bi-clock-history"></i> View in Service History
                                </a>
                            ` : ''}
                        </div>
                    </div>
                </div>`;

                if (isPending || isConfirmed) {
                    upcoming += html;
                } else {
                    completed += html;
                }
            });

            upcomingBox.innerHTML  = upcoming  || '<p class="text-muted small">No upcoming appointments.</p>';
            completedBox.innerHTML = completed || '<p class="text-muted small">No past history.</p>';
        }
    } catch (error) {
        console.error("Error loading appointments:", error);
        if (upcomingBox) upcomingBox.innerHTML = '<p class="text-danger small">Failed to load appointments.</p>';
    }
}

async function handleCancelAppointment(appointmentId) {
    const result = await Swal.fire({
        title: 'Cancel Appointment?',
        text: "This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, cancel it!',
        background: '#fff',
        borderRadius: '15px'
    });

    if (result.isConfirmed) {
        const token = localStorage.getItem("jwt_token");
        try {
            const response = await fetch('api/cancel_appointment.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}` 
                },
                body: JSON.stringify({ id: appointmentId })
            });
            
            const res = await response.json();
            if (res.status === "success") {
                showNotification("Appointment cancelled.");
                loadAppointments(); 
            } else {
                throw new Error(res.message);
            }
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Request failed',
                text: err.message || 'Connection error.'
            });
        }
    }
}

// ==========================================
// --- SESSION WATCH ---
// ==========================================

let _sessionWatchStarted = false;
let _sessionFailCount    = 0;

function startSessionWatch() {
    if (_sessionWatchStarted) return;
    _sessionWatchStarted = true;

    function doCheck() {
        const token        = localStorage.getItem('jwt_token');
        const sessionToken = localStorage.getItem('session_token');
        if (!token || !sessionToken) return;

        fetch('/api/check_session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ session_token: sessionToken })
        })
        .then(res => {
            if (!res.ok) { _sessionFailCount = 0; return null; }
            return res.json();
        })
        .then(data => {
            if (!data) return;
            if (data.valid) { _sessionFailCount = 0; return; }
            _sessionFailCount++;
            if (_sessionFailCount < 2) return; // require 2 consecutive failures
            // Silently clear session and redirect — no popup
            localStorage.removeItem('jwt_token');
            localStorage.removeItem('session_token');
            localStorage.removeItem('user');
            window.location.href = '/login.php';
        })
        .catch(() => { _sessionFailCount = 0; });
    }

    // Wait 15s before first check so page settles after login, then every 30s
    setTimeout(() => { doCheck(); setInterval(doCheck, 30000); }, 15000);
}

// Auto-start on every page if logged in
document.addEventListener('DOMContentLoaded', () => {
});
// ==========================================
// --- REMEMBER ME AUTO-FILL ---
// ==========================================
document.addEventListener('DOMContentLoaded', function () {
    if (localStorage.getItem('remember_me') === '1') {
        const emailInput = document.getElementById('l-email');
        const pwInput    = document.getElementById('l-pw');
        const rememberMe = document.getElementById('rememberMe');
        if (emailInput) emailInput.value = localStorage.getItem('remembered_email') || '';
        if (pwInput)    pwInput.value    = localStorage.getItem('remembered_pw') || '';
        if (rememberMe) rememberMe.checked = true;
    }
});