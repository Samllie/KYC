<?php
require_once '../config/session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC System — Individual Client Registration</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../public/css/index.css">
    <link rel="stylesheet" href="../../public/css/global.css">
    <style>
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-to-type-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background-color: #f0f0f0;
            color: #333;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .back-to-type-btn:hover {
            background-color: #e8e8e8;
            border-color: #d0d0d0;
            transform: translateX(-2px);
        }

        .back-to-type-btn i {
            transition: transform 0.2s ease;
        }

        .back-to-type-btn:hover i {
            transform: translateX(-3px);
        }
    </style>
</head>
<body>

<?php
$activePage = 'kyc-verification';
include '../includes/sidebar.php';
?>

<!-- ═══════════════════════════════════════════════ MAIN -->
<div class="main">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <h1>KYC Verification — Individual Client</h1>
            <div class="breadcrumb-trail">
                <i class="bi bi-house" style="font-size:.65rem;"></i>
                Dashboard &rsaquo; Clients &rsaquo; <span>New Individual Client</span>
            </div>
        </div>
        <div class="topbar-right">
        </div>
    </header>

    <!-- Content -->
    <main class="content">

        <!-- Steps -->
        <div class="steps-bar">
            <div class="step done" id="step-1">
                <div class="step-num"><i class="bi bi-check" style="font-size:.9rem;"></i></div>
                <div class="step-info">
                    <span>Step 1</span>
                    <strong>Client Type</strong>
                </div>
            </div>
            <div class="step-line done"></div>
            <div class="step active" id="step-2">
                <div class="step-num">2</div>
                <div class="step-info">
                    <span>Step 2</span>
                    <strong>Personal Details</strong>
                </div>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step-3">
                <div class="step-num">3</div>
                <div class="step-info">
                    <span>Step 3</span>
                    <strong>Documents</strong>
                </div>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step-4">
                <div class="step-num">4</div>
                <div class="step-info">
                    <span>Step 4</span>
                    <strong>Review</strong>
                </div>
            </div>
        </div>

        <!-- Form Start -->
        <form id="kycForm" novalidate>

            <!-- Client Type Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-list"></i> Client Type</div>
                    <a href="kyc-verification.php" class="back-to-type-btn">
                        <i class="bi bi-arrow-left"></i>
                        Change Type
                    </a>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="client-type-display individual">
                                    <i class="bi bi-person-fill"></i>
                                    <span>Individual Client</span>
                                </div>
                                <input type="hidden" name="clientType" value="individual">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reference Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-hash"></i> Reference</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="refCode" class="form-label">Reference Code <span style="font-size:0.85rem;color:#999;">(Optional)</span></label>
                                <input type="text" id="refCode" name="refCode" class="form-control" placeholder="Leave blank to auto-generate">
                                <small class="text-muted">Leave empty for automatic generation</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="clientNumber" class="form-label">Client Number</label>
                                <input type="text" id="clientNumber" name="clientNumber" class="form-control" placeholder="Auto-generated" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Information Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-person"></i> Personal Information</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="lastName" class="form-label">Last Name <span class="req">*</span></label>
                                <input type="text" id="lastName" name="lastName" class="form-control" placeholder="Last Name" required>
                                <div class="form-error">Last name is required</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="firstName" class="form-label">First Name <span class="req">*</span></label>
                                <input type="text" id="firstName" name="firstName" class="form-control" placeholder="First Name" required>
                                <div class="form-error">First name is required</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="middleName" class="form-label">Middle Name</label>
                                <input type="text" id="middleName" name="middleName" class="form-control" placeholder="Middle Name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="birthdate" class="form-label">Date of Birth <span class="req">*</span></label>
                                <input type="date" id="birthdate" name="birthdate" class="form-control" required>
                                <div class="form-error">Date of birth is required</div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="gender" class="form-label">Gender</label>
                                <div class="select-wrap">
                                    <select id="gender" name="gender" class="form-select">
                                        <option value="">Select...</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Occupation Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-briefcase"></i> Occupation</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="occupation" class="form-label">Occupation <span class="req">*</span></label>
                                <input type="text" id="occupation" name="occupation" class="form-control" placeholder="e.g., Employee, Self-employed, Manager" required>
                                <div class="form-error">Occupation is required</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="employer" class="form-label">Employer</label>
                                <input type="text" id="employer" name="employer" class="form-control" placeholder="Company Name">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Address Information Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-geo-alt"></i> Address Information</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="businessRegion" class="form-label">Region</label>
                                <div class="select-wrap">
                                    <select id="businessRegion" name="businessRegion" class="form-select">
                                        <option value="">Select region...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="businessProvince" class="form-label">Province</label>
                                <div class="select-wrap">
                                    <select id="businessProvince" name="businessProvince" class="form-select">
                                        <option value="">Select province...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="businessCtm" class="form-label">City / Municipality</label>
                                <div class="select-wrap">
                                    <select id="businessCtm" name="businessCtm" class="form-select">
                                        <option value="">Select city/municipality...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="businessBarangay" class="form-label">Barangay</label>
                                <div class="select-wrap">
                                    <select id="businessBarangay" name="businessBarangay" class="form-select">
                                        <option value="">Select barangay...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="businessStreet" class="form-label">Street / Unit / Building</label>
                                <input type="text" id="businessStreet" name="businessStreet" class="form-control" placeholder="House/Unit No., Street, Building">
                                <input type="hidden" id="businessAddress" name="businessAddress">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Home Address Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-house"></i> Home Address</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="homeRegion" class="form-label">Region</label>
                                <div class="select-wrap">
                                    <select id="homeRegion" name="homeRegion" class="form-select">
                                        <option value="">Select region...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="homeProvince" class="form-label">Province</label>
                                <div class="select-wrap">
                                    <select id="homeProvince" name="homeProvince" class="form-select">
                                        <option value="">Select province...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="homeCtm" class="form-label">City / Municipality</label>
                                <div class="select-wrap">
                                    <select id="homeCtm" name="homeCtm" class="form-select">
                                        <option value="">Select city/municipality...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="homeBarangay" class="form-label">Barangay</label>
                                <div class="select-wrap">
                                    <select id="homeBarangay" name="homeBarangay" class="form-select">
                                        <option value="">Select barangay...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="homeStreet" class="form-label">Street / Unit / Building <span class="req">*</span></label>
                                <input type="text" id="homeStreet" name="homeStreet" class="form-control" placeholder="House/Unit No., Street, Building" required>
                                <input type="hidden" id="homeAddress" name="homeAddress">
                                <div class="form-error">Home street/unit is required</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Contact Information Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-telephone"></i> Contact Information</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="mobile" class="form-label">Mobile Number <span class="req">*</span></label>
                                <input type="tel" id="mobile" name="mobile" class="form-control" placeholder="09XX-XXXX-XXXX" required>
                                <div class="form-error">Valid mobile number is required</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="telephone" class="form-label">Telephone</label>
                                <input type="tel" id="telephone" name="telephone" class="form-control" placeholder="(02) 8XXX-XXXX">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address <span class="req">*</span></label>
                                <input type="email" id="email" name="email" class="form-control" placeholder="user@example.com" required>
                                <div class="form-error">Valid email is required</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Client Classification Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-tag"></i> Client Classification</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-label">Client Type <span class="req">*</span></label>
                                <div style="display:flex;gap:20px;margin-top:8px;">
                                    <label style="display:flex;align-items:center;gap:8px;">
                                        <input type="radio" id="clientType1" name="clientClassification" value="client" required> Client
                                    </label>
                                    <label style="display:flex;align-items:center;gap:8px;">
                                        <input type="radio" id="agentType1" name="clientClassification" value="agent" required> Agent
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-file-earmark"></i> Supporting Documents</div>
                </div>
                <div class="card-body">
                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                        <i class="bi bi-cloud-arrow-up upload-icon"></i>
                        <p><strong>Click to upload</strong> or drag and drop</p>
                        <small>PDF, JPG, PNG (Max 5MB each)</small>
                    </div>
                    <input type="file" id="fileInput" multiple accept=".jpg,.jpeg,.png,.pdf" style="display:none;">
                    <div class="file-list" id="fileList"></div>
                </div>
            </div>

        </form>

        <!-- Action Buttons Card -->
        <div class="card">
            <div class="card-footer">
                <div style="font-size:.75rem;color:var(--gray-500);">
                    <i class="bi bi-info-circle" style="margin-right:4px;"></i>
                    All fields marked <span style="color:var(--danger);font-weight:700;">*</span> are required.
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="button" class="btn btn-outline" onclick="goBack()">
                        <i class="bi bi-arrow-left"></i> Back to Type Selection
                    </button>
                    <button type="button" class="btn btn-outline" onclick="clearForm()">
                        <i class="bi bi-arrow-counterclockwise"></i> Clear Form
                    </button>
                    <button type="button" class="btn btn-outline" onclick="saveDraft()">
                        <i class="bi bi-download"></i> Save Draft
                    </button>
                    <button type="button" class="btn btn-primary" onclick="proceedToReview()">
                        <i class="bi bi-arrow-right"></i> Proceed to Review
                    </button>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- ═══════════════════════════════════════════════ SCRIPTS -->
<script>
// ── Toast ──────────────────────────────────────────────────
function showToast(type, title, msg) {
    const icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', info: 'bi-info-circle-fill' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="bi ${icons[type]} toast-icon"></i>
        <div class="toast-body">
            <div class="toast-title">${title}</div>
            <div class="toast-msg">${msg}</div>
        </div>
        <i class="bi bi-x toast-close" onclick="removeToast(this.parentElement)"></i>`;
    document.getElementById('toastContainer').appendChild(toast);
    setTimeout(() => removeToast(toast), 4000);
}

function removeToast(el) {
    el.classList.add('out');
    setTimeout(() => el.remove(), 250);
}

// ── Form Validation ────────────────────────────────────────
function validateField(id) {
    const el = document.getElementById(id);
    if (!el) return true;
    
    // Skip validation if field is hidden
    if (el.offsetParent === null) return true;
    
    const value = el.value.trim();
    let ok = value !== '';
    
    // Additional validation for specific field types
    if (ok && el.type === 'email') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        ok = emailRegex.test(value);
    } else if (ok && el.type === 'tel') {
        // Phone validation: at least 7 digits
        const phoneDigits = value.replace(/\D/g, '');
        ok = phoneDigits.length >= 7;
    }
    
    el.classList.toggle('is-invalid', !ok);
    el.classList.toggle('is-valid', ok);
    return ok;
}

function validateRadioGroup(name) {
    const radios = document.querySelectorAll(`input[name="${name}"]`);
    if (radios.length === 0) return true;
    
    const checked = radios.some(radio => radio.checked);
    radios.forEach(radio => {
        const label = radio.closest('label');
        if (label) label.classList.toggle('is-invalid', !checked);
    });
    return checked;
}

function validateAllRequired() {
    const requiredFields = ['lastName', 'firstName', 'birthdate', 'occupation', 'mobile', 'email', 'homeRegion', 'homeProvince', 'homeCtm', 'homeBarangay', 'homeStreet'];
    let allValid = true;
    let failedFields = [];
    
    requiredFields.forEach(id => {
        const el = document.getElementById(id);
        if (el && el.offsetParent !== null) {
            const isValid = validateField(id);
            if (!isValid) {
                allValid = false;
                failedFields.push(id);
            }
        }
    });
    
    // Validate clientClassification radio
    if (!validateRadioGroup('clientClassification')) allValid = false;
    if (!allValid && failedFields.length > 0) {
        console.log('Failed fields:', failedFields);
    }
    
    return allValid;
}

// Add event listeners to all form fields
document.querySelectorAll('input:not([type="checkbox"]):not([type="radio"]), select, textarea').forEach(el => {
    el.addEventListener('blur', function() {
        if (this.id) validateField(this.id);
    });
    el.addEventListener('input', function() {
        if (this.classList.contains('is-invalid') && this.id) validateField(this.id);
    });
    el.addEventListener('change', function() {
        if (this.id) validateField(this.id);
    });
});

// ── PSGC Address API (Philippines) ───────────────────────
const PSGC_BASE_URL = 'https://psgc.gitlab.io/api';

async function psgcFetch(path) {
    const response = await fetch(`${PSGC_BASE_URL}${path}`);
    if (!response.ok) {
        throw new Error(`PSGC request failed: ${response.status}`);
    }
    return response.json();
}

function fillSelectOptions(selectEl, items, labelKey = 'name', valueKey = 'name', placeholder = 'Select...') {
    if (!selectEl) return;
    selectEl.innerHTML = `<option value="">${placeholder}</option>`;
    items.forEach(item => {
        const option = document.createElement('option');
        option.value = item[valueKey];
        option.textContent = item[labelKey];
        option.dataset.code = item.code;
        selectEl.appendChild(option);
    });
}

function setSelectLoading(selectEl, text) {
    if (!selectEl) return;
    selectEl.innerHTML = `<option value="">${text}</option>`;
    selectEl.disabled = true;
}

async function initAddressChain(regionId, provinceId, cityId, barangayId) {
    const regionEl = document.getElementById(regionId);
    const provinceEl = document.getElementById(provinceId);
    const cityEl = document.getElementById(cityId);
    const barangayEl = document.getElementById(barangayId);
    if (!regionEl || !provinceEl || !cityEl || !barangayEl) return;

    setSelectLoading(regionEl, 'Loading regions...');
    setSelectLoading(provinceEl, 'Select region first...');
    setSelectLoading(cityEl, 'Select province first...');
    setSelectLoading(barangayEl, 'Select city first...');

    try {
        const regions = await psgcFetch('/regions/');
        fillSelectOptions(regionEl, regions, 'name', 'name', 'Select region...');
        regionEl.disabled = false;
    } catch (error) {
        console.error(error);
        setSelectLoading(regionEl, 'Unable to load regions');
        return;
    }

    regionEl.addEventListener('change', async function () {
        const selectedRegionCode = this.options[this.selectedIndex]?.dataset?.code || '';
        fillSelectOptions(provinceEl, [], 'name', 'name', 'Select province...');
        fillSelectOptions(cityEl, [], 'name', 'name', 'Select city/municipality...');
        fillSelectOptions(barangayEl, [], 'name', 'name', 'Select barangay...');

        if (!selectedRegionCode) {
            provinceEl.disabled = true;
            cityEl.disabled = true;
            barangayEl.disabled = true;
            return;
        }

        setSelectLoading(provinceEl, 'Loading provinces...');
        cityEl.disabled = true;
        barangayEl.disabled = true;

        try {
            const provinces = await psgcFetch(`/regions/${selectedRegionCode}/provinces/`);
            if (provinces.length === 0) {
                fillSelectOptions(provinceEl, [{ name: 'NCR', code: selectedRegionCode }], 'name', 'name', 'No province (NCR)');
                provinceEl.value = 'NCR';
                provinceEl.disabled = true;

                setSelectLoading(cityEl, 'Loading cities/municipalities...');
                const citiesInRegion = await psgcFetch(`/regions/${selectedRegionCode}/cities-municipalities/`);
                fillSelectOptions(cityEl, citiesInRegion, 'name', 'name', 'Select city/municipality...');
                cityEl.disabled = false;
                fillSelectOptions(barangayEl, [], 'name', 'name', 'Select city first...');
                barangayEl.disabled = true;
                return;
            }

            fillSelectOptions(provinceEl, provinces, 'name', 'name', 'Select province...');
            provinceEl.disabled = false;
            cityEl.disabled = true;
            barangayEl.disabled = true;
        } catch (error) {
            console.error(error);
            setSelectLoading(provinceEl, 'Unable to load provinces');
            cityEl.disabled = true;
            barangayEl.disabled = true;
        }
    });

    provinceEl.addEventListener('change', async function () {
        const selectedProvinceCode = this.options[this.selectedIndex]?.dataset?.code || '';
        const selectedRegionCode = regionEl.options[regionEl.selectedIndex]?.dataset?.code || '';

        fillSelectOptions(cityEl, [], 'name', 'name', 'Select city/municipality...');
        fillSelectOptions(barangayEl, [], 'name', 'name', 'Select barangay...');

        if (!selectedProvinceCode && this.value !== 'NCR') {
            cityEl.disabled = true;
            barangayEl.disabled = true;
            return;
        }

        setSelectLoading(cityEl, 'Loading cities/municipalities...');
        barangayEl.disabled = true;
        try {
            const cities = this.value === 'NCR'
                ? await psgcFetch(`/regions/${selectedRegionCode}/cities-municipalities/`)
                : await psgcFetch(`/provinces/${selectedProvinceCode}/cities-municipalities/`);

            fillSelectOptions(cityEl, cities, 'name', 'name', 'Select city/municipality...');
            cityEl.disabled = false;
            fillSelectOptions(barangayEl, [], 'name', 'name', 'Select city first...');
            barangayEl.disabled = true;
        } catch (error) {
            console.error(error);
            setSelectLoading(cityEl, 'Unable to load cities/municipalities');
            barangayEl.disabled = true;
        }
    });

    cityEl.addEventListener('change', async function () {
        const selectedCityCode = this.options[this.selectedIndex]?.dataset?.code || '';
        fillSelectOptions(barangayEl, [], 'name', 'name', 'Select barangay...');

        if (!selectedCityCode) {
            barangayEl.disabled = true;
            return;
        }

        setSelectLoading(barangayEl, 'Loading barangays...');
        try {
            const barangays = await psgcFetch(`/cities-municipalities/${selectedCityCode}/barangays/`);
            fillSelectOptions(barangayEl, barangays, 'name', 'name', 'Select barangay...');
            barangayEl.disabled = false;
        } catch (error) {
            console.error(error);
            setSelectLoading(barangayEl, 'Unable to load barangays');
        }
    });
}

function buildAddress(street, barangay, city, province, region) {
    return [street, barangay, city, province, region].filter(part => part && part.trim() !== '').join(', ');
}

function syncComposedAddressFields() {
    const businessStreet = document.getElementById('businessStreet')?.value || '';
    const businessBarangay = document.getElementById('businessBarangay')?.value || '';
    const businessCity = document.getElementById('businessCtm')?.value || '';
    const businessProvince = document.getElementById('businessProvince')?.value || '';
    const businessRegion = document.getElementById('businessRegion')?.value || '';
    document.getElementById('businessAddress').value = buildAddress(businessStreet, businessBarangay, businessCity, businessProvince, businessRegion);

    const homeStreet = document.getElementById('homeStreet')?.value || '';
    const homeBarangay = document.getElementById('homeBarangay')?.value || '';
    const homeCity = document.getElementById('homeCtm')?.value || '';
    const homeProvince = document.getElementById('homeProvince')?.value || '';
    const homeRegion = document.getElementById('homeRegion')?.value || '';
    document.getElementById('homeAddress').value = buildAddress(homeStreet, homeBarangay, homeCity, homeProvince, homeRegion);
}

initAddressChain('businessRegion', 'businessProvince', 'businessCtm', 'businessBarangay');
initAddressChain('homeRegion', 'homeProvince', 'homeCtm', 'homeBarangay');

function restoreFormData() {
    const savedData = sessionStorage.getItem('kycFormData');
    const savedAddressData = sessionStorage.getItem('individualAddressData');
    
    if (!savedData) return;
    
    try {
        const formData = JSON.parse(savedData);
        const form = document.getElementById('kycForm');
        if (!form) return;
        
        // Fields to skip in the general restore (we'll handle address fields separately)
        const addressFields = ['businessRegion', 'businessProvince', 'businessCtm', 'businessBarangay', 'businessStreet', 'businessAddress', 'homeRegion', 'homeProvince', 'homeCtm', 'homeBarangay', 'homeStreet', 'homeAddress'];
        
        Object.keys(formData).forEach(key => {
            // Skip address fields - restore them separately
            if (addressFields.includes(key)) return;
            
            const el = form.querySelector(`[name="${key}"]`);
            if (el) {
                if (el.type === 'radio') {
                    const selectedRadio = form.querySelector(`[name="${key}"][value="${formData[key]}"]`);
                    if (selectedRadio) selectedRadio.checked = true;
                } else if (el.tagName === 'SELECT') {
                    el.value = formData[key];
                    el.dispatchEvent(new Event('change'));
                } else {
                    el.value = formData[key];
                }
            }
        });
        
        // Restore address data after API populates options
        // This requires waiting for PSGC API calls in the correct cascade order
        if (savedAddressData) {
            try {
                const addressData = JSON.parse(savedAddressData);
                
                // Restore BUSINESS address in cascade order with delays for API calls
                setTimeout(() => {
                    const businessRegionEl = document.getElementById('businessRegion');
                    if (businessRegionEl && addressData.businessRegion) {
                        businessRegionEl.value = addressData.businessRegion;
                        businessRegionEl.dispatchEvent(new Event('change'));
                    }
                    
                    // Wait for provinces to load, then restore province
                    setTimeout(() => {
                        const businessProvinceEl = document.getElementById('businessProvince');
                        if (businessProvinceEl && addressData.businessProvince) {
                            businessProvinceEl.value = addressData.businessProvince;
                            businessProvinceEl.dispatchEvent(new Event('change'));
                        }
                        
                        // Wait for cities to load, then restore city
                        setTimeout(() => {
                            const businessCityEl = document.getElementById('businessCtm');
                            if (businessCityEl && addressData.businessCity) {
                                businessCityEl.value = addressData.businessCity;
                                businessCityEl.dispatchEvent(new Event('change'));
                            }
                            
                            // Wait for barangays to load, then restore barangay
                            setTimeout(() => {
                                const businessBarangayEl = document.getElementById('businessBarangay');
                                if (businessBarangayEl && addressData.businessBarangay) {
                                    businessBarangayEl.value = addressData.businessBarangay;
                                }
                                
                                const businessStreetEl = document.getElementById('businessStreet');
                                if (businessStreetEl && addressData.businessStreet) {
                                    businessStreetEl.value = addressData.businessStreet;
                                }
                                
                                // Now restore HOME address in cascade order
                                restoreHomeAddress(addressData);
                                
                            }, 500);
                        }, 500);
                    }, 500);
                }, 500);
                
            } catch (error) {
                console.error('Error restoring address data:', error);
            }
        }
    } catch (error) {
        console.error('Error restoring form data:', error);
    }
}

function restoreHomeAddress(addressData) {
    setTimeout(() => {
        const homeRegionEl = document.getElementById('homeRegion');
        if (homeRegionEl && addressData.homeRegion) {
            homeRegionEl.value = addressData.homeRegion;
            homeRegionEl.dispatchEvent(new Event('change'));
        }
        
        // Wait for provinces to load, then restore province
        setTimeout(() => {
            const homeProvinceEl = document.getElementById('homeProvince');
            if (homeProvinceEl && addressData.homeProvince) {
                homeProvinceEl.value = addressData.homeProvince;
                homeProvinceEl.dispatchEvent(new Event('change'));
            }
            
            // Wait for cities to load, then restore city
            setTimeout(() => {
                const homeCityEl = document.getElementById('homeCtm');
                if (homeCityEl && addressData.homeCity) {
                    homeCityEl.value = addressData.homeCity;
                    homeCityEl.dispatchEvent(new Event('change'));
                }
                
                // Wait for barangays to load, then restore barangay
                setTimeout(() => {
                    const homeBarangayEl = document.getElementById('homeBarangay');
                    if (homeBarangayEl && addressData.homeBarangay) {
                        homeBarangayEl.value = addressData.homeBarangay;
                    }
                    
                    const homeStreetEl = document.getElementById('homeStreet');
                    if (homeStreetEl && addressData.homeStreet) {
                        homeStreetEl.value = addressData.homeStreet;
                    }
                    
                    syncComposedAddressFields();
                }, 500);
            }, 500);
        }, 500);
    }, 500);
}

// Restore form data on page load
document.addEventListener('DOMContentLoaded', restoreFormData);

function proceedToReview() {
    syncComposedAddressFields();

    if (!validateAllRequired()) {
        showToast('error', 'Validation Failed', 'Please fill in all required fields marked with *');
        return;
    }
    
    // Collect form data
    const formData = {};
    const form = document.getElementById('kycForm');
    const elements = form.querySelectorAll('input, select, textarea');
    elements.forEach(el => {
        if (el.name && el.value) {
            formData[el.name] = el.value;
        }
    });
    
    // Store in sessionStorage
    sessionStorage.setItem('kycFormData', JSON.stringify(formData));
    
    // Also store address components separately for reliable restoration
    const addressData = {
        businessRegion: document.getElementById('businessRegion').value,
        businessProvince: document.getElementById('businessProvince').value,
        businessCity: document.getElementById('businessCtm').value,
        businessBarangay: document.getElementById('businessBarangay').value,
        businessStreet: document.getElementById('businessStreet').value,
        businessAddress: document.getElementById('businessAddress').value,
        homeRegion: document.getElementById('homeRegion').value,
        homeProvince: document.getElementById('homeProvince').value,
        homeCity: document.getElementById('homeCtm').value,
        homeBarangay: document.getElementById('homeBarangay').value,
        homeStreet: document.getElementById('homeStreet').value,
        homeAddress: document.getElementById('homeAddress').value
    };
    sessionStorage.setItem('individualAddressData', JSON.stringify(addressData));
    
    // Navigate to review page
    window.location.href = 'kyc-individual-review.php';
}

function submitForm() {
    syncComposedAddressFields();

    if (!validateAllRequired()) {
        showToast('error', 'Validation Failed', 'Please fill in all required fields marked with *');
        return;
    }
    
    // Collect form data
    const formData = new FormData();
    formData.append('action', 'submit_kyc');
    
    // Add all form fields
    const form = document.getElementById('kycForm');
    const elements = form.querySelectorAll('input, select, textarea');
    elements.forEach(el => {
        if (el.name && el.value) {
            formData.append(el.name, el.value);
        }
    });
    
    // Submit to handler
    fetch('../handlers/kyc.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.reference_code && !document.getElementById('refCode').value) {
                document.getElementById('refCode').value = data.reference_code;
                document.getElementById('refCode').readOnly = true;
            }
            showToast('success', 'Client Saved!', data.reference_code ? `Reference Code: ${data.reference_code}` : 'Client registered successfully.');
            // Increment stat
            const tv = document.getElementById('stat-total');
            if (tv) tv.textContent = parseInt(tv.textContent) + 1;
            
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 2000);
        } else {
            showToast('error', 'Submission Failed', data.message || 'Please try again.');
        }
    })
    .catch(error => {
        showToast('error', 'Error', 'An error occurred. Please try again.');
        console.error('Error:', error);
    });
}

function saveDraft() {
    syncComposedAddressFields();

    // Collect form data
    const formData = new FormData();
    formData.append('action', 'save_draft');
    
    // Add all form fields
    const form = document.getElementById('kycForm');
    const elements = form.querySelectorAll('input, select, textarea');
    elements.forEach(el => {
        if (el.name) {
            formData.append(el.name, el.value);
        }
    });
    
    // Submit to handler
    fetch('../handlers/kyc.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.reference_code && !document.getElementById('refCode').value) {
                document.getElementById('refCode').value = data.reference_code;
                document.getElementById('refCode').readOnly = true;
            }
            showToast('info', 'Draft Saved', data.reference_code ? `Reference Code: ${data.reference_code}` : 'Your progress has been saved successfully.');
        } else {
            showToast('error', 'Save Failed', data.message || 'Please try again.');
        }
    })
    .catch(error => {
        showToast('error', 'Error', 'An error occurred. Please try again.');
        console.error('Error:', error);
    });
}

function clearForm() {
    document.getElementById('kycForm').querySelectorAll('input, select').forEach(el => {
        if (el.readOnly) return;
        el.value = '';
        el.classList.remove('is-invalid','is-valid');
    });
    document.getElementById('fileList').innerHTML = '';
    showToast('info', 'Form Cleared', 'All fields have been reset.');
}

function goBack() {
    window.location.href = 'kyc-verification.php';
}

// ── File Upload ────────────────────────────────────────────
const zone   = document.getElementById('uploadZone');
const input  = document.getElementById('fileInput');
const list   = document.getElementById('fileList');

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes/1024).toFixed(1) + ' KB';
    return (bytes/1048576).toFixed(1) + ' MB';
}

function addFile(file) {
    const ext = file.name.split('.').pop().toLowerCase();
    const icons = { pdf:'bi-file-earmark-pdf', jpg:'bi-file-earmark-image', jpeg:'bi-file-earmark-image', png:'bi-file-earmark-image' };
    const item = document.createElement('div');
    item.className = 'file-item';
    item.innerHTML = `
        <i class="bi ${icons[ext] || 'bi-file-earmark'}"></i>
        <span>${file.name}</span>
        <small>${formatSize(file.size)}</small>
        <i class="bi bi-trash file-remove" onclick="this.parentElement.remove(); showToast('info','File Removed','${file.name} was removed.');"></i>`;
    list.appendChild(item);
}

input.addEventListener('change', () => {
    Array.from(input.files).forEach(addFile);
    if (input.files.length) showToast('success', 'Files Attached', `${input.files.length} file(s) added.`);
    input.value = '';
});

zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('dragover');
    Array.from(e.dataTransfer.files).forEach(addFile);
});

// ── Auto-gen Client Number ─────────────────────────────────
document.getElementById('refCode').addEventListener('input', function() {
    const cn = this.value ? 'CN-' + Date.now().toString().slice(-6) : '';
    document.getElementById('clientNumber').value = cn;
});

// ── Sticky Progress Bar on Scroll ────────────────────────────
const stepsBar = document.querySelector('.steps-bar');
const mainContent = document.querySelector('.main');

window.addEventListener('scroll', function() {
    if (!stepsBar) return;
    
    const scrollPosition = mainContent?.getBoundingClientRect().top || 0;
    
    // If main content top is above viewport, make progress bar sticky
    if (scrollPosition < 0) {
        stepsBar.classList.add('sticky');
    } else {
        stepsBar.classList.remove('sticky');
    }
});
</script>

</body>
</html>
