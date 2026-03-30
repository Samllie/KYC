<?php
require_once '../config/session.php';
requireLogin();

$allowedClientTypes = ['corporate', 'obligee'];
$selectedClientType = strtolower(trim($_GET['clientType'] ?? 'corporate'));
if (!in_array($selectedClientType, $allowedClientTypes, true)) {
    $selectedClientType = 'corporate';
}

$selectedClientTypeLabel = $selectedClientType === 'obligee' ? 'Obligee' : 'Corporate';
$selectedClientHeading = $selectedClientTypeLabel . ' Client';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sterling insurance Company Incorporated</title>
    <link rel='icon' type='image/png' href='../css/images/SterlingLogo.png'>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../public/css/index.css">
    <link rel="stylesheet" href="../../public/css/global.css">
    <style>
        .review-section {
            margin-bottom: 20px;
            padding: 16px;
            border: 1px solid #d8e5dd;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.92);
            opacity: 0;
            transform: translateY(8px);
            animation: reviewSlideIn 0.3s ease forwards;
        }

        .review-title {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #d7e5dc;
        }

        .review-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .review-label {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-bottom: 3px;
        }

        .review-value {
            font-weight: 600;
            color: #1e352b;
            word-break: break-word;
        }

        .action-group {
            display: flex;
            gap: 10px;
        }

        .review-empty {
            text-align: center;
            color: var(--gray-500);
            padding: 18px;
            border: 1px dashed #d0ded6;
            border-radius: 10px;
            background: #fbfdfb;
        }

        @keyframes reviewSlideIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .review-grid {
                grid-template-columns: 1fr;
            }

            .action-group {
                width: 100%;
            }

            .action-group .btn {
                width: 100%;
                justify-content: center;
            }
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
            <h1>KYC Verification — <?php echo htmlspecialchars($selectedClientHeading, ENT_QUOTES, 'UTF-8'); ?> Review</h1>
            <div class="breadcrumb-trail">
                <i class="bi bi-house" style="font-size:.65rem;"></i>
                Dashboard &rsaquo; Clients &rsaquo; <span>Review Information</span>
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
            <div class="step done" id="step-2">
                <div class="step-num"><i class="bi bi-check" style="font-size:.9rem;"></i></div>
                <div class="step-info">
                    <span>Step 2</span>
                    <strong>Business Details</strong>
                </div>
            </div>
            <div class="step-line done"></div>
            <div class="step done" id="step-3">
                <div class="step-num"><i class="bi bi-check" style="font-size:.9rem;"></i></div>
                <div class="step-info">
                    <span>Step 3</span>
                    <strong>Contact Details</strong>
                </div>
            </div>
            <div class="step-line done"></div>
            <div class="step active" id="step-4">
                <div class="step-num">4</div>
                <div class="step-info">
                    <span>Step 4</span>
                    <strong>Review</strong>
                </div>
            </div>
        </div>

        <!-- Review Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="bi bi-eye"></i> Review Information</div>
                <div class="card-subtitle">Please review the information before submitting</div>
            </div>
            <div class="card-body" id="reviewBody">
                <p class="review-empty">
                    <i class="bi bi-hourglass-split"></i> Loading information...
                </p>
            </div>
        </div>

        <!-- Action Buttons Card -->
        <div class="card">
            <div class="card-footer">
                <div class="action-group">
                    <button type="button" id="backBtn" class="btn btn-outline" onclick="goBackToEdit()">
                        <i class="bi bi-pencil"></i> Back to Edit
                    </button>
                    <button type="button" id="submitBtn" class="btn btn-primary" onclick="submitForm()">
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

function escapeHtml(value) {
    return String(value || '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

// ── Display Review Information ─────────────────────────
function displayReview() {
    const formData = JSON.parse(sessionStorage.getItem('kycFormData') || '{}');
    const selectedClientType = (formData.clientType || '<?php echo htmlspecialchars($selectedClientType, ENT_QUOTES, 'UTF-8'); ?>').toLowerCase();
    const selectedClientLabel = selectedClientType === 'obligee' ? 'Obligee' : 'Corporate';
    
    if (Object.keys(formData).length === 0) {
        document.getElementById('reviewBody').innerHTML = '<div class="review-empty">No data found. Please fill the form first.</div>';
        return;
    }
    
    const sections = [
        {
            title: 'Client Type',
            fields: [
                { label: 'Client Type', key: 'clientType', value: `${selectedClientLabel} Client` }
            ]
        },
        {
            title: 'Reference',
            fields: [
                { label: 'Reference Code', key: 'refCode' },
                { label: 'Client Number', key: 'clientNumber' }
            ]
        },
        {
            title: 'Company Information',
            fields: [
                { label: 'Business / Company Name', key: 'corporateClientName' },
                { label: 'Business Type', key: 'businessType' },
                { label: 'Client Since', key: 'corporateClientSince' }
            ]
        },
        {
            title: 'Business Details',
            fields: [
                { label: 'TIN Number', key: 'tinNumber' },
                { label: 'AP SL Code', key: 'corporateApSlCode' },
                { label: 'AR SL Code', key: 'corporateArSlCode' },
                { label: 'Contact Person Designation', key: 'designation' }
            ]
        },
        {
            title: 'Business Address',
            fields: [
                { label: 'Full Address', key: 'corporateBusinessAddress' }
            ]
        },
        {
            title: 'Contact Information',
            fields: [
                { label: 'Phone Number', key: 'corporatePhone' },
                { label: 'Company Owner', key: 'corporateContactPerson' },
                { label: 'Email Address', key: 'corporateEmail' }
            ]
        },
        {
            title: 'Contact Person Details',
            fields: [
                { label: 'Gender', key: 'corporateGender' },
                { label: 'Client Classification', key: 'clientClassification' }
            ]
        },
        {
            title: 'Government ID',
            fields: [
                { label: 'Government ID Type', key: 'idType' },
                { label: 'ID Number', key: 'idNumber' }
            ]
        }
    ];

    const governmentIdUploads = JSON.parse(sessionStorage.getItem('kycGovernmentIdFiles') || '[]');
    const governmentIdProfile = JSON.parse(sessionStorage.getItem('kycGovernmentIdOcrData') || '{}');
    
    let html = '';
    sections.forEach((section, idx) => {
        html += `
            <section class="review-section" style="animation-delay:${Math.min(idx * 70, 350)}ms;">
                <div class="review-title">${section.title}</div>
                <div class="review-grid">
        `;
        
        section.fields.forEach(field => {
            const value = field.value || formData[field.key] || '';
            if (value) {
                html += `
                    <div>
                        <div class="review-label">${field.label}</div>
                        <div class="review-value">${value}</div>
                    </div>
                `;
            }
        });
        
        html += '</div></section>';
    });

    if (governmentIdUploads.length) {
        const uploadedNames = governmentIdUploads.map(file => escapeHtml(file.original_name || file.file_name || 'ID photo')).join(', ');
        html += `
            <section class="review-section" style="animation-delay:${Math.min(sections.length * 70, 350)}ms;">
                <div class="review-title">Government ID Upload</div>
                <div class="review-grid">
                    <div>
                        <div class="review-label">Uploaded File</div>
                        <div class="review-value">${uploadedNames}</div>
                    </div>
                </div>
            </section>
        `;
    }

    const profileParts = [];
    if (governmentIdProfile.fullName) profileParts.push(`<div><strong>Name:</strong> ${escapeHtml(governmentIdProfile.fullName)}</div>`);
    if (governmentIdProfile.idNumber) profileParts.push(`<div><strong>ID Number:</strong> ${escapeHtml(governmentIdProfile.idNumber)}</div>`);
    if (governmentIdProfile.birthdate) profileParts.push(`<div><strong>Birthdate:</strong> ${escapeHtml(governmentIdProfile.birthdate)}</div>`);
    if (governmentIdProfile.gender) profileParts.push(`<div><strong>Gender:</strong> ${escapeHtml(governmentIdProfile.gender)}</div>`);
    if (governmentIdProfile.nationality) profileParts.push(`<div><strong>Nationality:</strong> ${escapeHtml(governmentIdProfile.nationality)}</div>`);
    if (governmentIdProfile.address) profileParts.push(`<div><strong>Address:</strong> ${escapeHtml(governmentIdProfile.address)}</div>`);

    if (profileParts.length) {
        html += `
            <section class="review-section" style="animation-delay:${Math.min((sections.length + 1) * 70, 420)}ms;">
                <div class="review-title">OCR Extracted Profile</div>
                <div class="review-grid">
                    ${profileParts.join('')}
                </div>
            </section>
        `;
    }
    
    document.getElementById('reviewBody').innerHTML = html;
}

function goBackToEdit() {
    const formData = JSON.parse(sessionStorage.getItem('kycFormData') || '{}');
    const selectedClientType = formData.clientType || '<?php echo htmlspecialchars($selectedClientType, ENT_QUOTES, 'UTF-8'); ?>';
    window.location.href = `kyc-corporate.php?clientType=${encodeURIComponent(selectedClientType)}`;
}

function submitForm() {
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn.disabled) return;

    const formData = JSON.parse(sessionStorage.getItem('kycFormData') || '{}');
    const uploadedFiles = JSON.parse(sessionStorage.getItem('kycUploadedFiles') || '[]');
    
    if (Object.keys(formData).length === 0) {
        showToast('error', 'No Data', 'Form data not found. Please fill the form first.');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.dataset.defaultHtml = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner"></span> Submitting...';
    
    const formDataObj = new FormData();
    formDataObj.append('action', 'add_client');
    formDataObj.append('uploadedFiles', JSON.stringify(uploadedFiles || []));
    formDataObj.append('uploadedIdFiles', JSON.stringify(JSON.parse(sessionStorage.getItem('kycGovernmentIdFiles') || '[]')));
    formDataObj.append('governmentIdOcrData', JSON.stringify(JSON.parse(sessionStorage.getItem('kycGovernmentIdOcrData') || '{}')));
    
    Object.keys(formData).forEach(key => {
        formDataObj.append(key, formData[key]);
    });
    
    fetch('../handlers/client.php', {
        method: 'POST',
        body: formDataObj
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Client Saved!', data.reference_code ? `Reference Code: ${data.reference_code}` : 'Client registered successfully.');
            
            // Clear sessionStorage
            sessionStorage.removeItem('kycFormData');
            sessionStorage.removeItem('kycUploadedFiles');
            sessionStorage.removeItem('kycGovernmentIdFiles');
            sessionStorage.removeItem('kycGovernmentIdOcrData');
            
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
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = submitBtn.dataset.defaultHtml || '<i class="bi bi-check-circle"></i> Submit & Continue';
    });
}

// Load review on page load
document.addEventListener('DOMContentLoaded', displayReview);

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
