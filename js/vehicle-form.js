// ============================================================
// SHARED VEHICLE BRAND / MODEL LOGIC
//
// Powers the "brand -> model" cascading dropdown used by both
// the booking flow's "Add a vehicle" form and the My Vehicles
// "Add New Vehicle" form, so both stay in sync.
// ============================================================

const philippineCarModels = {

    Toyota: [
        "86 / GR86", "Alphard", "Avanza", "Camry", "Coaster",
        "Corolla Altis", "Corolla Cross", "FJ Cruiser", "Fortuner",
        "GR Supra", "GR Yaris", "Hiace", "Hilux", "Innova",
        "Land Cruiser", "Land Cruiser Prado", "Lite Ace", "RAV4",
        "Raize", "Rush", "Tamaraw", "Veloz", "Vios", "Wigo",
        "Yaris", "Yaris Cross", "Zenix"
    ],

    Honda: [
        "Accord", "Brio", "BR-V", "City", "City Hatchback",
        "Civic", "CR-V", "HR-V", "Jazz", "Mobilio", "Odyssey"
    ],

    Mitsubishi: [
        "Adventure", "ASX", "Eclipse Cross", "Lancer", "Lancer EX",
        "Lancer Evolution", "L300", "Mirage", "Mirage G4", "Montero",
        "Montero Sport", "Outlander", "Outlander PHEV", "Triton",
        "Xpander", "Xpander Cross"
    ],

    Nissan: [
        "Almera", "Juke", "Kicks", "Livina", "Navara", "Patrol",
        "Sentra", "Serena", "Terra", "Urvan", "X-Trail"
    ],

    Ford: [
        "EcoSport", "Everest", "Explorer", "Expedition", "Fiesta",
        "Focus", "Mustang", "Ranger", "Territory", "Transit"
    ],

    Suzuki: [
        "APV", "Alto", "Celerio", "Dzire", "Ertiga", "Ignis",
        "Jimny", "S-Presso", "Swift", "Vitara", "XL7", "Carry"
    ],

    Isuzu: [
        "Crosswind", "D-Max", "mu-X", "Traviz", "Alterra", "Trooper"
    ],

    Hyundai: [
        "Accent", "Avante", "Creta", "Eon", "Elantra", "Getz",
        "H-100", "i10", "Ioniq", "Ioniq 5", "Ioniq 6", "Kona",
        "Santa Fe", "Sonata", "Stargazer", "Staria", "Tucson", "Veloster"
    ],

    Kia: [
        "Carens", "Carnival", "EV6", "EV9", "K3", "K5", "Picanto",
        "Rio", "Seltos", "Soluto", "Sorento", "Sportage", "Stonic"
    ],

    Mazda: [
        "2", "3", "6", "BT-50", "CX-3", "CX-30", "CX-5", "CX-8",
        "CX-9", "CX-60", "CX-90", "MX-5"
    ]

};


/**
 * Wires a brand <select> to a model <select>, automatically
 * falling back to a free-text "Other" input when the brand or
 * model isn't in the list.
 *
 * @param {Object} ids
 * @param {string} ids.brandId       - id of the brand <select>
 * @param {string} ids.modelId       - id of the model <select>
 * @param {string} ids.modelOtherId  - id of the custom model <input>
 * @param {string} ids.modelHelpId   - id of the helper <small> text
 */
function initVehicleBrandModel(ids) {
    const brandSelect = document.getElementById(ids.brandId);
    const modelSelect = document.getElementById(ids.modelId);
    const modelOtherInput = document.getElementById(ids.modelOtherId);
    const modelHelpText = document.getElementById(ids.modelHelpId);

    if (!brandSelect || !modelSelect) {
        return;
    }

    function showCustomModelInput() {
        modelSelect.style.display = "none";
        modelSelect.disabled = true;

        if (modelOtherInput) {
            modelOtherInput.style.display = "block";
            modelOtherInput.disabled = false;
            modelOtherInput.value = "";
            modelOtherInput.focus();
        }

        if (modelHelpText) {
            modelHelpText.textContent = "Please enter your vehicle's model.";
        }
    }

    function showNormalModelDropdown() {
        modelSelect.style.display = "block";
        modelSelect.disabled = false;

        if (modelOtherInput) {
            modelOtherInput.style.display = "none";
            modelOtherInput.disabled = true;
            modelOtherInput.value = "";
        }

        if (modelHelpText) {
            modelHelpText.textContent = "Can't find your model? Select Other and enter it manually.";
        }
    }

    function updateModelDropdown() {
        const selectedBrand = brandSelect.value;

        showNormalModelDropdown();
        modelSelect.innerHTML = "";

        // No brand selected yet
        if (!selectedBrand) {
            modelSelect.disabled = true;
            modelSelect.innerHTML = '<option value="">Select a brand first</option>';
            if (modelHelpText) {
                modelHelpText.textContent = "Models are based on vehicles commonly sold/registered in the Philippines.";
            }
            return;
        }

        // Brand = Other -> no model dropdown, straight to free text
        if (selectedBrand === "Other") {
            showCustomModelInput();
            if (modelHelpText) {
                modelHelpText.textContent = "Please enter your vehicle's model.";
            }
            return;
        }

        // Normal brand -> populate models
        modelSelect.disabled = false;

        const placeholder = document.createElement("option");
        placeholder.value = "";
        placeholder.textContent = "Select model";
        modelSelect.appendChild(placeholder);

        const models = philippineCarModels[selectedBrand] || [];
        models.forEach(function (model) {
            const option = document.createElement("option");
            option.value = model;
            option.textContent = model;
            modelSelect.appendChild(option);
        });

        const otherOption = document.createElement("option");
        otherOption.value = "Other";
        otherOption.textContent = "Other";
        modelSelect.appendChild(otherOption);

        if (modelHelpText) {
            modelHelpText.textContent = "Can't find your model? Select Other and enter it manually.";
        }
    }

    brandSelect.addEventListener("change", updateModelDropdown);

    modelSelect.addEventListener("change", function () {
        if (modelSelect.value === "Other") {
            showCustomModelInput();
        } else {
            showNormalModelDropdown();
            if (modelSelect.value) {
                modelSelect.value = modelSelect.value;
            }
        }
    });

    // Initialize
    updateModelDropdown();
}


/**
 * Resolves the final model text for a brand/model form, accounting
 * for the "Other" free-text fallback.
 *
 * @param {Object} ids
 * @param {string} ids.brandId
 * @param {string} ids.modelId
 * @param {string} ids.modelOtherId
 * @returns {string}
 */
function getVehicleModelValue(ids) {
    const brandSelect = document.getElementById(ids.brandId);
    const modelSelect = document.getElementById(ids.modelId);
    const modelOtherInput = document.getElementById(ids.modelOtherId);

    if (!brandSelect) {
        return "";
    }

    if (brandSelect.value === "Other") {
        return modelOtherInput ? modelOtherInput.value.trim() : "";
    }

    if (modelSelect && modelSelect.value === "Other") {
        return modelOtherInput ? modelOtherInput.value.trim() : "";
    }

    return modelSelect ? modelSelect.value : "";
}
