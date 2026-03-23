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
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/global.css">
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
            <button class="topbar-btn" title="Search"><i class="bi bi-search"></i></button>
            <button class="topbar-btn" title="Notifications">
                <i class="bi bi-bell"></i>
                <span class="notif-dot"></span>
            </button>
            <button class="topbar-btn" title="Help"><i class="bi bi-question-circle"></i></button>
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

        <!-- Client Information Card -->
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Individual Client Information</div>
                    <div class="card-subtitle">Fill in all required fields marked with <span style="color:var(--danger)">*</span></div>
                </div>
                <span class="badge badge-draft"><i class="bi bi-circle-fill" style="font-size:.4rem;"></i> Draft</span>
            </div>

            <div class="card-body">
                <form id="kycForm" novalidate>

                    <!-- ── Section: Reference ── -->
                    <div class="section-divider">
                        <span class="section-divider-label"><i class="bi bi-hash"></i> Reference</span>
                        <div class="section-divider-line"></div>
                    </div>

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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Client Type</label>
                                <div class="client-type-display individual">
                                    <i class="bi bi-person-fill"></i>
                                    <span>Individual Client</span>
                                </div>
                                <input type="hidden" name="clientType" value="individual">
                            </div>
                        </div>
                    </div>

                    <!-- ── Section: Full Name ── -->
                    <div class="section-divider">
                        <span class="section-divider-label"><i class="bi bi-person"></i> Full Name</span>
                        <div class="section-divider-line"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="lastName" class="form-label">Last Name <span class="req">*</span></label>
                                <input type="text" id="lastName" name="lastName" class="form-control" placeholder="Dela Cruz" required>
                                <div class="form-error">Last name is required</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="firstName" class="form-label">First Name <span class="req">*</span></label>
                                <input type="text" id="firstName" name="firstName" class="form-control" placeholder="Juan" required>
                                <div class="form-error">First name is required</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="middleName" class="form-label">Middle Name</label>
                                <input type="text" id="middleName" name="middleName" class="form-control" placeholder="Optional">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div style="display:flex;gap:20px;margin:10px 0;">
                                <label style="display:flex;align-items:center;gap:8px;">
                                    <input type="checkbox" id="lastNameFirst" name="lastNameFirst"> Last Name First
                                </label>
                                <label style="display:flex;align-items:center;gap:8px;">
                                    <input type="checkbox" id="commaSeparated" name="commaSeparated"> Comma Separated
                                </label>
                                <label style="display:flex;align-items:center;gap:8px;">
                                    <input type="checkbox" id="middleInitialOnly" name="middleInitialOnly"> Middle Name Initial Only
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- ── Section: Personal Details ── -->
                    <div class="section-divider">
                        <span class="section-divider-label"><i class="bi bi-card-list"></i> Personal Details</span>
                        <div class="section-divider-line"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="birthdate" class="form-label">Date of Birth <span class="req">*</span></label>
                                <input type="date" id="birthdate" name="birthdate" class="form-control" required>
                                <div class="form-error">Date of birth is required</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="clientSince" class="form-label">Client Since</label>
                                <input type="date" id="clientSince" name="clientSince" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="nationality" class="form-label">Nationality</label>
                                <input type="text" id="nationality" name="nationality" class="form-control" placeholder="Philippine">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="apSlCode" class="form-label">AP SL Code</label>
                                <input type="text" id="apSlCode" name="apSlCode" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="arSlCode" class="form-label">AR SL Code</label>
                                <input type="text" id="arSlCode" name="arSlCode" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- ── Section: Occupation ── -->
                    <div class="section-divider">
                        <span class="section-divider-label"><i class="bi bi-briefcase"></i> Occupation</span>
                        <div class="section-divider-line"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="occupation" class="form-label">Occupation <span class="req">*</span></label>
                                <input type="text" id="occupation" name="occupation" class="form-control" placeholder="Your occupation" required>
                                <div class="form-error">Occupation is required</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="company" class="form-label">Company Name</label>
                                <input type="text" id="company" name="company" class="form-control" placeholder="Company name">
                            </div>
                        </div>
                    </div>

                    <!-- ── Section: Business Address ── -->
                    <div class="section-divider">
                        <span class="section-divider-label"><i class="bi bi-shop"></i> Business Address</span>
                        <div class="section-divider-line"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="businessAddress" class="form-label">Business Address</label>
                                <input type="text" id="businessAddress" name="businessAddress" class="form-control" placeholder="Street, Barangay, City">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="businessCtm" class="form-label">CTM</label>
                                <input type="text" id="businessCtm" name="businessCtm" class="form-control" placeholder="City Code">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="businessProvince" class="form-label">Province</label>
                                <input type="text" id="businessProvince" name="businessProvince" class="form-control" placeholder="Province">
                            </div>
                        </div>
                    </div>

                    <!-- ── Section: Home Address ── -->
                    <div class="section-divider">
                        <span class="section-divider-label"><i class="bi bi-house"></i> Home Address</span>
                        <div class="section-divider-line"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="homeAddress" class="form-label">Home Address <span class="req">*</span></label>
                                <input type="text" id="homeAddress" name="homeAddress" class="form-control" placeholder="Street, Barangay, City" required>
                                <div class="form-error">Home address is required</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="homeCtm" class="form-label">CTM</label>
                                <input type="text" id="homeCtm" name="homeCtm" class="form-control" placeholder="City Code">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="homeProvince" class="form-label">Province</label>
                                <input type="text" id="homeProvince" name="homeProvince" class="form-control" placeholder="Province">
                            </div>
                        </div>
                    </div>

                    <!-- ── Section: Contact Details ── -->
                    <div class="section-divider">
                        <span class="section-divider-label"><i class="bi bi-telephone"></i> Contact Information</span>
                        <div class="section-divider-line"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="officePhone" class="form-label">Office Phone</label>
                                <input type="tel" id="officePhone" name="officePhone" class="form-control" placeholder="(02) 8000 0000">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="homePhone" class="form-label">Home Phone</label>
                                <input type="tel" id="homePhone" name="homePhone" class="form-control" placeholder="(02) 8000 0000">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="mobile" class="form-label">Mobile Phone <span class="req">*</span></label>
                                <input type="tel" id="mobile" name="mobile" class="form-control" placeholder="+63 900 000 0000" required>
                                <div class="form-error">Mobile phone is required</div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address <span class="req">*</span></label>
                                <input type="email" id="email" name="email" class="form-control" placeholder="user@example.com" required>
                                <div class="form-error">Valid email is required</div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Section: Spouse Information ── -->
                    <div class="section-divider">
                        <span class="section-divider-label"><i class="bi bi-person-check"></i> Spouse Information</span>
                        <div class="section-divider-line"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="spouseName" class="form-label">Spouse Name</label>
                                <input type="text" id="spouseName" name="spouseName" class="form-control" placeholder="Full name">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="spouseBirthdate" class="form-label">Spouse Birthdate</label>
                                <input type="date" id="spouseBirthdate" name="spouseBirthdate" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="spouseOccupation" class="form-label">Spouse Occupation</label>
                                <input type="text" id="spouseOccupation" name="spouseOccupation" class="form-control" placeholder="Occupation">
                            </div>
                        </div>
                    </div>

                    <!-- ── Section: Mailing Address ── -->
                    <div class="section-divider">
                        <span class="section-divider-label"><i class="bi bi-envelope"></i> Mailing Address</span>
                        <div class="section-divider-line"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <div style="display:flex;gap:20px;">
                                <label style="display:flex;align-items:center;gap:8px;">
                                    <input type="radio" id="mailingBusiness" name="mailingAddressType" value="business"> Business Address
                                </label>
                                <label style="display:flex;align-items:center;gap:8px;">
                                    <input type="radio" id="mailingHome" name="mailingAddressType" value="home"> Home Address
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- ── Section: Documents ── -->
                    <div class="section-divider">
                        <span class="section-divider-label"><i class="bi bi-file-earmark"></i> Supporting Documents</span>
                        <div class="section-divider-line"></div>
                    </div>

                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                        <i class="bi bi-cloud-arrow-up upload-icon"></i>
                        <p><strong>Click to upload</strong> or drag and drop</p>
                        <small>PDF, JPG, PNG (Max 5MB each)</small>
                    </div>
                    <input type="file" id="fileInput" multiple accept=".jpg,.jpeg,.png,.pdf" style="display:none;">
                    <div class="file-list" id="fileList"></div>

                </form>
            </div>

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
                    <button type="button" class="btn btn-primary" onclick="submitForm()">
                        <i class="bi bi-check-circle"></i> Submit & Continue
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
    } else if (ok && (el.type === 'tel' || id === 'mobile')) {
        // Phone validation: at least 7 digits
        const phoneDigits = value.replace(/\D/g, '');
        ok = phoneDigits.length >= 7;
    }
    
    el.classList.toggle('is-invalid', !ok);
    el.classList.toggle('is-valid', ok);
    return ok;
}

function validateAllRequired() {
    const requiredFields = ['lastName', 'firstName', 'birthdate', 'occupation', 'mobile', 'email', 'homeAddress'];
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

function submitForm() {
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
            showToast('success', 'Client Saved!', data.reference_code ? `Reference Code: ${data.reference_code}` : 'Proceeding to Document Verification.');
            // Advance step indicator
            const s2 = document.getElementById('step-2');
            s2.classList.remove('active'); s2.classList.add('done');
            s2.querySelector('.step-num').innerHTML = '<i class="bi bi-check" style="font-size:.9rem;"></i>';
            const s3 = document.getElementById('step-3');
            s3.classList.add('active');
            document.querySelectorAll('.step-line')[1].classList.add('done');
            // Increment stat
            const tv = document.getElementById('stat-total');
            tv.textContent = parseInt(tv.textContent) + 1;
            
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
</script>

</body>
</html>
