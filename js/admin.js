/**
 * js/admin.js - Admin Dashboard Controller
 */
document.addEventListener("DOMContentLoaded", () => {
    const user = JSON.parse(localStorage.getItem("user"));
    
    // SECURITY: If not admin, kick them out
    if (!user || user.role_id != 2) {
        window.location.href = "../login.php";
        return;
    }

    loadDashboard();
});

async function loadDashboard() {
    const token = localStorage.getItem("jwt_token");
    const tableBody = document.getElementById("adminRecentTable");

    try {
        const response = await fetch('api/admin_stats.php', {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();

        if (data.status === "success") {
            // Update Stat Cards
            document.getElementById("pendingCount").innerText = data.stats.pending;
            document.getElementById("userCount").innerText = data.stats.users;
            document.getElementById("salesTotal").innerText = data.stats.total; // Using count as placeholder

            // Render Recent Table
            let rows = "";
            data.recent.forEach(app => {
                let badgeClass = "bg-success";
                if (app.status === 'Pending') badgeClass = "bg-warning text-dark";
                else if (app.status === 'Cancelled') badgeClass = "bg-secondary";

                rows += `
                <tr>
                    <td class="px-4">
                        <div class="fw-bold">${app.full_name}</div>
                    </td>
                    <td>${app.brand} ${app.model}</td>
                    <td><span class="text-danger fw-bold">${app.service_type}</span></td>
                    <td>${app.appointment_date}</td>
                    <td><span class="badge ${badgeClass}">${app.status}</span></td>
                    <td class="text-end px-4">
                        <button onclick="viewAppointment(${app.id})" class="btn btn-sm btn-outline-dark">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>`;
            });
            tableBody.innerHTML = rows;
        }
    } catch (error) {
        console.error("Dashboard Load Error:", error);
    }
}

function viewAppointment(id) {
    // Redirect to the full appointments page or open a modal
    window.location.href = `admin_appointments.php?id=${id}`;
}