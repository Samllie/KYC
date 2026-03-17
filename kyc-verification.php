<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC System — Client Registration</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<!-- ═══════════════════════════════════════════════ SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <a href="#" class="brand-logo">
            <div class="brand-icon"><i class="bi bi-shield-check"></i></div>
            <div class="brand-text">
                <span>STerling Insurance Company</span>
                <strong>KYC System</strong>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Main Menu</div>

        <a href="dashboard.php" class="nav-item">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>
        <a href="clients.php" class="nav-item">
            <i class="bi bi-people"></i> Clients
            <span class="nav-badge">24</span>
        </a>
        <a href="kyc-verification.php" class="nav-item active">
            <i class="bi bi-person-check"></i> KYC Verification
        </a>
        <a href="policy.php" class="nav-item">
            <i class="bi bi-file-earmark-text"></i> Policy Issuance
        </a>

        <div class="nav-label">Analytics</div>

        <a href="#" class="nav-item">
            <i class="bi bi-bar-chart"></i> Reports
        </a>
        <a href="#" class="nav-item">
            <i class="bi bi-bell"></i> Notifications
            <span class="nav-badge">3</span>
        </a>
        <a href="#" class="nav-item">
            <i class="bi bi-gear"></i> Settings
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar">JD</div>
            <div class="user-info">
                <span>Juan Dela Cruz</span>
                <span>KYC Officer</span>
            </div>
            <i class="bi bi-three-dots-vertical" style="color:rgba(255,255,255,.35);margin-left:auto;"></i>
        </div>
    </div>
</aside>

<!-- ═══════════════════════════════════════════════ MAIN -->
<div class="main">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <h1>KYC Verification</h1>
            <div class="breadcrumb-trail">
                <i class="bi bi-house" style="font-size:.65rem;"></i>
                Dashboard &rsaquo; Clients &rsaquo; <span>New Client</span>
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

        <!-- Stat Cards -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-value" id="stat-total">248</div>
                    <div class="stat-label">Total Clients</div>
                    <div class="stat-change up"><i class="bi bi-arrow-up-short"></i> +12 this month</div>
                </div>
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-value">37</div>
                    <div class="stat-label">Pending KYC</div>
                    <div class="stat-change down"><i class="bi bi-arrow-down-short"></i> -5 from last week</div>
                </div>
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-value">194</div>
                    <div class="stat-label">Verified</div>
                    <div class="stat-change up"><i class="bi bi-arrow-up-short"></i> +8 this month</div>
                </div>
                <div class="stat-icon"><i class="bi bi-patch-check-fill"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-value">17</div>
                    <div class="stat-label">Rejected</div>
                    <div class="stat-change up"><i class="bi bi-arrow-up-short"></i> +2 this week</div>
                </div>
                <div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
            </div>
        </div>

        <!-- Steps -->
        <div class="steps-bar">
            <div class="step done" id="step-1">
                <div class="step-num"><i class="bi bi-check" style="font-size:.9rem;"></i></div>
                <div class="step-info">
                    <span>Step 1</span>
                    <strong>Basic Info</strong>
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
                    <div class="card-title">Client Information</div>
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
                                <label for="refCode" class="form-label">Reference Code <span class="req">*</span></label>
                                <input type="text" id="refCode" name="refCode" class="form-control" placeholder="e.g., REF-001" required>
                                <div class="form-error">Reference code is required</div>
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
                                <label for="clientType" class="form-label">Client Type <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select id="clientType" name="clientType" class="form-select" required>
                                        <option value="">Select Type...</option>
                                        <option value="individual">Individual</option>
                                        <option value="corporate">Corporate</option>
                                    </select>
                                </div>
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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="suffixName" class="form-label">Suffix</label>
                                <div class="select-wrap">
                                    <select id="suffixName" name="suffixName" class="form-select">
                                        <option value="">None</option>
                                        <option value="jr">Jr.</option>
                                        <option value="sr">Sr.</option>
                                        <option value="ii">II</option>
                                        <option value="iii">III</option>
                                    </select>
                                </div>
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
                                <label for="gender" class="form-label">Gender</label>
                                <div class="select-wrap">
                                    <select id="gender" name="gender" class="form-select">
                                        <option value="">Select...</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
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
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="idType" class="form-label">ID Type</label>
                                <div class="select-wrap">
                                    <select id="idType" name="idType" class="form-select">
                                        <option value="">Select ID Type...</option>
                                        <option value="passport">Passport</option>
                                        <option value="license">Driver's License</option>
                                        <option value="nbi">NBI Clearance</option>
                                        <option value="tin">TIN ID</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="idNumber" class="form-label">ID Number</label>
                                <input type="text" id="idNumber" name="idNumber" class="form-control" placeholder="ID Number">
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

                    <!-- ── Section: Contact ── -->
                    <div class="section-divider">
                        <span class="section-divider-label"><i class="bi bi-telephone"></i> Contact Information</span>
                        <div class="section-divider-line"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="mobile" class="form-label">Mobile Phone <span class="req">*</span></label>
                                <div class="input-icon-wrap">
                                    <i class="bi bi-telephone"></i>
                                    <input type="tel" id="mobile" name="mobile" class="form-control" placeholder="+63 900 000 0000" required>
                                </div>
                                <div class="form-error">Mobile phone is required</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="phone" class="form-label">Landline</label>
                                <div class="input-icon-wrap">
                                    <i class="bi bi-telephone"></i>
                                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="(02) 8000 0000">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address <span class="req">*</span></label>
                                <div class="input-icon-wrap">
                                    <i class="bi bi-envelope"></i>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="user@example.com" required>
                                </div>
                                <div class="form-error">Valid email is required</div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Section: Address ── -->
                    <div class="section-divider">
                        <span class="section-divider-label"><i class="bi bi-pin-map"></i> Address</span>
                        <div class="section-divider-line"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="address" class="form-label">Full Address <span class="req">*</span></label>
                                <input type="text" id="address" name="address" class="form-control" placeholder="Street, Barangay, City" required>
                                <div class="form-error">Full address is required</div>
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
                    <button class="btn btn-outline" onclick="clearForm()">
                        <i class="bi bi-arrow-counterclockwise"></i> Clear Form
                    </button>
                    <button class="btn btn-outline" onclick="saveDraft()">
                        <i class="bi bi-download"></i> Save Draft
                    </button>
                    <button class="btn btn-primary" onclick="submitForm()">
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
const requiredFields = ['refCode','lastName','firstName','birthdate','occupation','email','mobile','address'];

function validateField(id) {
    const el = document.getElementById(id);
    if (!el) return true;
    const ok = el.value.trim() !== '';
    el.classList.toggle('is-invalid', !ok);
    el.classList.toggle('is-valid', ok);
    return ok;
}

requiredFields.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('blur', () => validateField(id));
    if (el) el.addEventListener('input', () => {
        if (el.classList.contains('is-invalid')) validateField(id);
    });
});

function submitForm() {
    const allValid = requiredFields.every(id => validateField(id));
    if (!allValid) {
        showToast('error', 'Validation Failed', 'Please fill in all required fields.');
        return;
    }
    showToast('success', 'Client Saved!', 'Proceeding to Document Verification.');
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
}

function saveDraft() {
    showToast('info', 'Draft Saved', 'Your progress has been saved locally.');
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
