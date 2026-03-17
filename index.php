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
        <a href="index.php" class="nav-item active">
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
                                <label class="form-label">Ref Code <span class="req">*</span></label>
                                <div class="input-icon-wrap">
                                    <i class="bi bi-upc-scan"></i>
                                    <input type="text" class="form-control" id="refCode"
                                           placeholder="e.g. KYC-2024-0001" required>
                                </div>
                                <div class="form-error">Reference code is required.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Client Number <span class="req">*</span></label>
                                <div class="input-icon-wrap">
                                    <i class="bi bi-person-badge"></i>
                                    <input type="text" class="form-control" id="clientNumber"
                                           placeholder="Auto-generated" readonly
                                           style="background:var(--gray-50);cursor:not-allowed;">
                                </div>
                                <div class="form-hint">Generated upon save.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Client Type <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select class="form-select" id="clientType">
                                        <option value="">— Select type —</option>
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
                                <label class="form-label">Last Name <span class="req">*</span></label>
                                <input type="text" class="form-control" id="lastName"
                                       placeholder="Dela Cruz" required>
                                <div class="form-error">Last name is required.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">First Name <span class="req">*</span></label>
                                <input type="text" class="form-control" id="firstName"
                                       placeholder="Juan" required>
                                <div class="form-error">First name is required.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middleName"
                                       placeholder="Santos">
                                <div class="form-hint">Optional</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Suffix</label>
                                <div class="select-wrap">
                                    <select class="form-select" id="suffix">
                                        <option value="">None</option>
                                        <option>Jr.</option>
                                        <option>Sr.</option>
                                        <option>II</option>
                                        <option>III</option>
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
                                <label class="form-label">Birthdate <span class="req">*</span></label>
                                <div class="input-icon-wrap">
                                    <i class="bi bi-calendar3"></i>
                                    <input type="date" class="form-control" id="birthdate" required>
                                </div>
                                <div class="form-error">Birthdate is required.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Gender <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select class="form-select" id="gender">
                                        <option value="">— Select —</option>
                                        <option>Male</option>
                                        <option>Female</option>
                                        <option>Prefer not to say</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Civil Status</label>
                                <div class="select-wrap">
                                    <select class="form-select" id="civilStatus">
                                        <option value="">— Select —</option>
                                        <option>Single</option>
                                        <option>Married</option>
                                        <option>Widowed</option>
                                        <option>Separated</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Occupation <span class="req">*</span></label>
                                <div class="input-icon-wrap">
                                    <i class="bi bi-briefcase"></i>
                                    <input type="text" class="form-control" id="occupation"
                                           placeholder="e.g. Engineer" required>
                                </div>
                                <div class="form-error">Occupation is required.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Nationality</label>
                                <div class="input-icon-wrap">
                                    <i class="bi bi-globe2"></i>
                                    <input type="text" class="form-control" id="nationality"
                                           placeholder="Filipino">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">TIN / Tax ID</label>
                                <div class="input-icon-wrap">
                                    <i class="bi bi-hash"></i>
                                    <input type="text" class="form-control" id="tin"
                                           placeholder="000-000-000-000">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Section: Contact ── -->
                    <div class="section-divider">
                        <span class="section-divider-label"><i class="bi bi-envelope"></i> Contact Information</span>
                        <div class="section-divider-line"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Email Address <span class="req">*</span></label>
                                <div class="input-icon-wrap">
                                    <i class="bi bi-envelope"></i>
                                    <input type="email" class="form-control" id="email"
                                           placeholder="juan@example.com" required>
                                </div>
                                <div class="form-error">A valid email is required.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Mobile Number <span class="req">*</span></label>
                                <div class="input-icon-wrap">
                                    <i class="bi bi-phone"></i>
                                    <input type="tel" class="form-control" id="mobile"
                                           placeholder="+63 9XX XXX XXXX" required>
                                </div>
                                <div class="form-error">Mobile number is required.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Telephone</label>
                                <div class="input-icon-wrap">
                                    <i class="bi bi-telephone"></i>
                                    <input type="tel" class="form-control" id="telephone"
                                           placeholder="(02) XXXX-XXXX">
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Present Address <span class="req">*</span></label>
                                <div class="input-icon-wrap">
                                    <i class="bi bi-geo-alt"></i>
                                    <input type="text" class="form-control" id="address"
                                           placeholder="Unit/Blk/Lot, Street, Barangay, City, Province, ZIP" required>
                                </div>
                                <div class="form-error">Address is required.</div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Section: Documents ── -->
                    <div class="section-divider">
                        <span class="section-divider-label"><i class="bi bi-paperclip"></i> Supporting Documents</span>
                        <div class="section-divider-line"></div>
                    </div>

                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                        <i class="bi bi-cloud-arrow-up upload-icon"></i>
                        <p>Drop files here or <strong>click to browse</strong></p>
                        <small>Accepted: JPG, PNG, PDF — Max 5MB per file</small>
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
                        <i class="bi bi-floppy"></i> Save Draft
                    </button>
                    <button class="btn btn-primary" onclick="submitForm()">
                        Save &amp; Continue <i class="bi bi-arrow-right"></i>
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