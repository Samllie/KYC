<?php
require_once '../config/session.php';
requireLogin();
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
            <h1>KYC Verification — Individual Client Review</h1>
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
                    <strong>Personal Details</strong>
                </div>
            </div>
            <div class="step-line done"></div>
            <div class="step done" id="step-3">
                <div class="step-num"><i class="bi bi-check" style="font-size:.9rem;"></i></div>
                <div class="step-info">
                    <span>Step 3</span>
                    <strong>Documents</strong>
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
                <p style="text-align: center; color: var(--gray-500);">
                    <i class="bi bi-hourglass-split"></i> Loading information...
                </p>
            </div>
        </div>

        <!-- Action Buttons Card -->
        <div class="card">
            <div class="card-footer">
                <div style="display:flex;gap:10px;">
                    <button type="button" class="btn btn-outline" onclick="goBackToEdit()">
                        <i class="bi bi-pencil"></i> Back to Edit
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

// ── Display Review Information ─────────────────────────
function displayReview() {
    const formData = JSON.parse(sessionStorage.getItem('kycFormData') || '{}');
    
    if (Object.keys(formData).length === 0) {
        document.getElementById('reviewBody').innerHTML = '<p style="color: red;">No data found. Please fill the form first.</p>';
        return;
    }
    
    const sections = [
        {
            title: 'Client Type',
            fields: [
                { label: 'Client Type', key: 'clientType', format: 'individual' }
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
            title: 'Personal Information',
            fields: [
                { label: 'Last Name', key: 'lastName' },
                { label: 'First Name', key: 'firstName' },
                { label: 'Middle Name', key: 'middleName' },
                { label: 'Date of Birth', key: 'birthdate' },
                { label: 'Gender', key: 'gender' }
            ]
        },
        {
            title: 'Occupation',
            fields: [
                { label: 'Occupation', key: 'occupation' },
                { label: 'Employer', key: 'employer' }
            ]
        },
        {
            title: 'Address Information',
            fields: [
                { label: 'Business Address', key: 'businessAddress' }
            ]
        },
        {
            title: 'Home Address',
            fields: [
                { label: 'Home Address', key: 'homeAddress' }
            ]
        },
        {
            title: 'Contact Information',
            fields: [
                { label: 'Mobile Number', key: 'mobile' },
                { label: 'Telephone', key: 'telephone' },
                { label: 'Email Address', key: 'email' }
            ]
        },
        {
            title: 'Client Classification',
            fields: [
                { label: 'Classification', key: 'clientClassification' }
            ]
        }
    ];
    
    let html = '';
    sections.forEach((section, idx) => {
        const hasFields = section.fields.some(f => formData[f.key]);
        if (!hasFields) return;
        
        html += `
            <div style="margin-bottom: 30px;">
                <div style="font-weight: 700; color: var(--primary); margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid var(--primary);">
                    ${section.title}
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        `;
        
        section.fields.forEach(field => {
            const value = formData[field.key] || '';
            if (value) {
                html += `
                    <div>
                        <div style="font-size: 0.85rem; color: var(--gray-500); margin-bottom: 4px;">${field.label}</div>
                        <div style="font-weight: 600; color: var(--gray-800);">${value}</div>
                    </div>
                `;
            }
        });
        
        html += '</div></div>';
    });
    
    document.getElementById('reviewBody').innerHTML = html;
}

function goBackToEdit() {
    window.location.href = 'kyc-individual.php';
}

function submitForm() {
    const formData = JSON.parse(sessionStorage.getItem('kycFormData') || '{}');
    
    if (Object.keys(formData).length === 0) {
        showToast('error', 'No Data', 'Form data not found. Please fill the form first.');
        return;
    }
    
    const formDataObj = new FormData();
    formDataObj.append('action', 'submit_kyc');
    
    Object.keys(formData).forEach(key => {
        formDataObj.append(key, formData[key]);
    });
    
    fetch('../handlers/kyc.php', {
        method: 'POST',
        body: formDataObj
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Client Saved!', data.reference_code ? `Reference Code: ${data.reference_code}` : 'Client registered successfully.');
            
            // Clear sessionStorage
            sessionStorage.removeItem('kycFormData');
            
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
