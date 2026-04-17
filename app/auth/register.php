<?php
require_once '../config/session.php';
requireLogin();

$currentUserRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
$currentUserDepartment = strtoupper(trim((string)($_SESSION['department'] ?? '')));
$currentUserBranch = strtoupper(trim((string)($_SESSION['branch'] ?? '')));
$isHeadOfficeUser = $currentUserRole === 'admin'
    || $currentUserDepartment === 'HEAD OFFICE'
    || in_array($currentUserBranch, ['HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH'], true);

if (!$isHeadOfficeUser) {
    header('Location: ../pages/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sterling insurance Company, Inc.</title>
    <link rel='icon' type='image/png' href='../../css/images/SterlingLogo.png'>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../public/css/auth.css">
</head>
<body>

<!-- ═══════════════════════════════════════════════ AUTH CONTAINER -->
<div class="auth-container register-layout">
    <div class="page-corner-logo-wrap" aria-hidden="true">
        <img src="../../css/images/SterlingLogo2.jpg" alt="" class="page-corner-logo">
    </div>

    <div class="auth-wrapper">
        
        <!-- Left Side - Branding -->
        <div class="auth-brand">
            <h1>Sterling Insurance Company, Inc.</h1>
            <div class="brand-description">
                <p>Ensuring Integrity, Security, and Compliance in Every Client Engagement.</p>
            </div>
        </div>

        <!-- Right Side - Register Form -->
        <div class="auth-form-container">
            <div class="auth-form">
                <div class="form-header register-form-header">
                    <h2>Add Account</h2>
                    <p>Fill in the details below to add a head office, branch manager, or KYC officer account.</p>
                </div>

                <form id="registerForm" class="register-form-grid" method="POST">
                    <!-- Full Name Field -->
                    <div class="form-group register-col-6">
                        <label for="fullname" class="form-label">
                            Full Name <span class="req">*</span>
                        </label>
                        <div class="input-icon-wrap">
                            <i class="bi bi-person"></i>
                            <input 
                                type="text" 
                                id="fullname" 
                                name="fullname" 
                                class="form-control" 
                                placeholder="Juan Dela Cruz" 
                                maxlength="32"
                                required>
                        </div>
                        <div class="form-error"></div>
                    </div>

                    <!-- Email Field -->
                    <div class="form-group register-col-6">
                        <label for="email" class="form-label">
                            Email Address <span class="req">*</span>
                        </label>
                        <div class="input-icon-wrap">
                            <i class="bi bi-envelope"></i>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control" 
                                placeholder="name@sterling-insurance.com.ph" 
                                maxlength="120"
                                required>
                        </div>
                        <div class="form-hint">Only @sterling-insurance.com.ph email addresses are allowed.</div>
                        <div class="form-error"></div>
                    </div>

                    <!-- Branch Field -->
                    <div class="form-group register-col-12">
                        <label for="branch" class="form-label">
                            Branch <span class="req">*</span>
                        </label>
                        <div class="select-wrap">
                            <select id="branch" name="branch" class="form-select" required>
                                <option value="" selected disabled>Select branch</option>
                                <option value="HEAD OFFICE">HEAD OFFICE</option>
                                <option value="ALABANG BRANCH">ALABANG BRANCH</option>
                                <option value="MANILA BRANCH I">MANILA BRANCH I</option>
                                <option value="MANILA BRANCH II">MANILA BRANCH II</option>
                                <option value="WEST AVENUE BRANCH">WEST AVENUE BRANCH</option>
                                <option value="CUBAO BRANCH">CUBAO BRANCH</option>
                                <option value="ANGELES BRANCH">ANGELES BRANCH</option>
                                <option value="BATANGAS BRANCH">BATANGAS BRANCH</option>
                                <option value="BACOLOD BRANCH">BACOLOD BRANCH</option>
                                <option value="CABANATUAN BRANCH">CABANATUAN BRANCH</option>
                                <option value="BUTUAN BRANCH">BUTUAN BRANCH</option>
                                <option value="CAGAYAN DE ORO BRANCH">CAGAYAN DE ORO BRANCH</option>
                                <option value="CEBU BRANCH">CEBU BRANCH</option>
                                <option value="CEBU REGIONAL OFFICE BRANCH">CEBU REGIONAL OFFICE BRANCH</option>
                                <option value="DAGUPAN BRANCH">DAGUPAN BRANCH</option>
                                <option value="DAVAO I BRANCH">DAVAO I BRANCH</option>
                                <option value="DAVAO II BRANCH">DAVAO II BRANCH</option>
                                <option value="GENSAN BRANCH">GENSAN BRANCH</option>
                                <option value="ISABELA BRANCH">ISABELA BRANCH</option>
                                <option value="LA UNION BRANCH">LA UNION BRANCH</option>
                                <option value="LAOAG BRANCH">LAOAG BRANCH</option>
                                <option value="LEGAZPI I BRANCH">LEGAZPI I BRANCH</option>
                                <option value="LEGAZPI II BRANCH">LEGAZPI II BRANCH</option>
                                <option value="MINDORO BRANCH">MINDORO BRANCH</option>
                                <option value="NAGA BRANCH">NAGA BRANCH</option>
                                <option value="ORMOC BRANCH">ORMOC BRANCH</option>
                                <option value="OZAMIZ BRANCH">OZAMIZ BRANCH</option>
                                <option value="PAGADIAN BRANCH">PAGADIAN BRANCH</option>
                                <option value="SAN FERNANDO, PAMPANGA BRANCH">SAN FERNANDO, PAMPANGA BRANCH</option>
                                <option value="HEAD OFFICE BRANCH">HEAD OFFICE BRANCH</option>
                                <option value="SMRO BRANCH">SMRO BRANCH</option>
                                <option value="TACLOBAN BRANCH">TACLOBAN BRANCH</option>
                                <option value="TUGUEGARAO BRANCH">TUGUEGARAO BRANCH</option>
                                <option value="VIGAN BRANCH">VIGAN BRANCH</option>
                                <option value="ILOILO BRANCH">ILOILO BRANCH</option>
                            </select>
                        </div>
                        <div class="form-hint">Branch assignment is separate from account level.</div>
                        <div class="form-error"></div>
                    </div>

                    <!-- Account Classification -->
                    <div class="form-group register-col-12">
                        <label for="account_classification" class="form-label">
                            Account Classification <span class="req">*</span>
                        </label>
                        <div class="select-wrap">
                            <select id="account_classification" name="account_classification" class="form-select" required>
                                <option value="" selected disabled>Select classification</option>
                                <option value="head_office">HEAD OFFICE</option>
                                <option value="branch_manager">BRANCH MANAGER</option>
                                <option value="kyc_officer">KYC OFFICER</option>
                            </select>
                        </div>
                        <div class="form-hint">This controls the account access level.</div>
                        <div class="form-error"></div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group register-col-6">
                        <label for="password" class="form-label">
                            Password <span class="req">*</span>
                        </label>
                        <div class="input-icon-wrap has-toggle">
                            <i class="bi bi-lock"></i>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                placeholder="At least 8 characters" 
                                maxlength="32"
                                required>
                            <button type="button" class="password-toggle" data-target="password" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-hint">Password must be at least 8 characters long</div>
                        <div class="form-error"></div>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="form-group register-col-6">
                        <label for="confirm_password" class="form-label">
                            Confirm Password <span class="req">*</span>
                        </label>
                        <div class="input-icon-wrap has-toggle">
                            <i class="bi bi-lock"></i>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-control" 
                                placeholder="At least 8 characters" 
                                maxlength="32"
                                required>
                            <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-hint">Password must be at least 8 characters long</div>
                        <div class="form-error"></div>
                    </div>

                    <div class="form-group register-col-12">
                        <button type="button" id="generatePasswordBtn" class="btn btn-outline btn-block">
                            <i class="bi bi-shuffle"></i> Generate 10-Character Password
                        </button>
                    </div>

                    <!-- Register Button -->
                    <button type="submit" class="btn btn-primary btn-block register-col-12">
                        <i class="bi bi-person-plus"></i> Add Account
                    </button>

                    <a href="../pages/dashboard.php" class="btn btn-outline btn-block register-col-12">
                        <i class="bi bi-arrow-left"></i> Back to Head Office Dashboard
                    </a>
                </form>

                <!-- Footer -->
                <div class="auth-footer">
                    <p>&copy; 2026 Sterling Insurance Company, Inc. All rights reserved.</p>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- ═══════════════════════════════════════════════ SCRIPTS -->
<script>
// ── Toast ──────────────────────────────────────────────────
function showToast(type, title, msg) {
    const icons = { 
        success: 'bi-check-circle-fill', 
        error: 'bi-x-circle-fill', 
        info: 'bi-info-circle-fill' 
    };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="bi ${icons[type]} toast-icon"></i>
        <div class="toast-body">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${msg}</div>
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
const form = document.getElementById('registerForm');
const fullnameInput = document.getElementById('fullname');
const emailInput = document.getElementById('email');
const branchInput = document.getElementById('branch');
const accountClassificationInput = document.getElementById('account_classification');
const passwordInput = document.getElementById('password');
const confirmPasswordInput = document.getElementById('confirm_password');
const generatePasswordBtn = document.getElementById('generatePasswordBtn');
const MAX_CREDENTIAL_LENGTH = 32;
const MAX_EMAIL_LENGTH = 120;
const ALLOWED_EMAIL_DOMAIN = '@sterling-insurance.com.ph';
const VALID_ACCOUNT_CLASSIFICATIONS = ['head_office', 'branch_manager', 'kyc_officer'];
const VALID_BRANCHES = [
    'ALABANG BRANCH',
    'MANILA BRANCH I',
    'MANILA BRANCH II',
    'WEST AVENUE BRANCH',
    'CUBAO BRANCH',
    'ANGELES BRANCH',
    'BATANGAS BRANCH',
    'BACOLOD BRANCH',
    'CABANATUAN BRANCH',
    'BUTUAN BRANCH',
    'CAGAYAN DE ORO BRANCH',
    'CEBU BRANCH',
    'CEBU REGIONAL OFFICE BRANCH',
    'DAGUPAN BRANCH',
    'DAVAO I BRANCH',
    'DAVAO II BRANCH',
    'GENSAN BRANCH',
    'ISABELA BRANCH',
    'LA UNION BRANCH',
    'LAOAG BRANCH',
    'LEGAZPI I BRANCH',
    'LEGAZPI II BRANCH',
    'MINDORO BRANCH',
    'NAGA BRANCH',
    'ORMOC BRANCH',
    'OZAMIZ BRANCH',
    'PAGADIAN BRANCH',
    'SAN FERNANDO, PAMPANGA BRANCH',
    'HEAD OFFICE',
    'HEAD OFFICE BRANCH',
    'SMRO BRANCH',
    'TACLOBAN BRANCH',
    'TUGUEGARAO BRANCH',
    'VIGAN BRANCH',
    'ILOILO BRANCH'
];

function getSecureRandomIndex(max) {
    if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
        const values = new Uint32Array(1);
        window.crypto.getRandomValues(values);
        return values[0] % max;
    }

    return Math.floor(Math.random() * max);
}

function shuffleCharacters(characters) {
    for (let index = characters.length - 1; index > 0; index -= 1) {
        const swapIndex = getSecureRandomIndex(index + 1);
        const temp = characters[index];
        characters[index] = characters[swapIndex];
        characters[swapIndex] = temp;
    }

    return characters;
}

function generateSuggestedPassword(length = 10) {
    const characterSets = [
        'ABCDEFGHJKLMNPQRSTUVWXYZ',
        'abcdefghijkmnopqrstuvwxyz',
        '23456789',
        '!@#$%&*?'
    ];
    const allCharacters = characterSets.join('');
    const characters = characterSets.map(set => set[getSecureRandomIndex(set.length)]);

    while (characters.length < length) {
        characters.push(allCharacters[getSecureRandomIndex(allCharacters.length)]);
    }

    return shuffleCharacters(characters).slice(0, length).join('');
}

function applySuggestedPassword() {
    const suggestedPassword = generateSuggestedPassword(10);

    passwordInput.value = suggestedPassword;
    confirmPasswordInput.value = suggestedPassword;
    validateField(passwordInput);
    validateField(confirmPasswordInput);
}

function resetRegisterFormState() {
    form.reset();

    [fullnameInput, emailInput, branchInput, accountClassificationInput, passwordInput, confirmPasswordInput].forEach(field => {
        if (field) {
            field.classList.remove('is-valid', 'is-invalid');
        }
    });

    document.querySelectorAll('.password-toggle').forEach(toggleBtn => {
        const icon = toggleBtn.querySelector('i');
        if (icon) {
            icon.className = 'bi bi-eye';
        }
        toggleBtn.setAttribute('aria-label', 'Show password');
    });

    passwordInput.type = 'password';
    confirmPasswordInput.type = 'password';

    applySuggestedPassword();
}

// ── Password Visibility Toggle ─────────────────────────────
document.querySelectorAll('.password-toggle').forEach(toggleBtn => {
    toggleBtn.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const icon = this.querySelector('i');

        if (!input || !icon) return;

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });
});

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email) && email.toLowerCase().endsWith(ALLOWED_EMAIL_DOMAIN);
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = false;

    if (field.id === 'fullname') {
        isValid = value.length >= 3 && value.length <= MAX_CREDENTIAL_LENGTH;
    } else if (field.id === 'email') {
        isValid = value.length <= MAX_EMAIL_LENGTH && validateEmail(value);
    } else if (field.id === 'account_classification') {
        isValid = VALID_ACCOUNT_CLASSIFICATIONS.includes(value);
    } else if (field.id === 'branch') {
        isValid = VALID_BRANCHES.includes(value);
    } else if (field.id === 'password') {
        isValid = value.length >= 8 && value.length <= MAX_CREDENTIAL_LENGTH;
    } else if (field.id === 'confirm_password') {
        isValid = value === passwordInput.value && value.length >= 8 && value.length <= MAX_CREDENTIAL_LENGTH;
    }

    field.classList.toggle('is-invalid', !isValid && value !== '');
    field.classList.toggle('is-valid', isValid);
    return isValid || (field.id !== 'fullname' && value === '');
}

fullnameInput.addEventListener('blur', () => validateField(fullnameInput));
emailInput.addEventListener('blur', () => validateField(emailInput));
if (accountClassificationInput) {
    accountClassificationInput.addEventListener('blur', () => validateField(accountClassificationInput));
}
branchInput.addEventListener('blur', () => validateField(branchInput));
passwordInput.addEventListener('blur', () => validateField(passwordInput));
confirmPasswordInput.addEventListener('blur', () => validateField(confirmPasswordInput));

fullnameInput.addEventListener('input', () => {
    if (fullnameInput.classList.contains('is-invalid')) validateField(fullnameInput);
});

emailInput.addEventListener('input', () => {
    if (emailInput.classList.contains('is-invalid')) validateField(emailInput);
});

if (accountClassificationInput) {
    accountClassificationInput.addEventListener('change', () => {
        if (accountClassificationInput.classList.contains('is-invalid')) validateField(accountClassificationInput);
    });
}

branchInput.addEventListener('change', () => {
    if (branchInput.classList.contains('is-invalid')) validateField(branchInput);
});

if (generatePasswordBtn) {
    generatePasswordBtn.addEventListener('click', function() {
        applySuggestedPassword();
        passwordInput.focus();
        passwordInput.select();
    });
}

passwordInput.addEventListener('input', () => {
    if (passwordInput.classList.contains('is-invalid')) validateField(passwordInput);
    if (confirmPasswordInput.classList.contains('is-invalid')) validateField(confirmPasswordInput);
});

confirmPasswordInput.addEventListener('input', () => {
    if (confirmPasswordInput.classList.contains('is-invalid')) validateField(confirmPasswordInput);
});

applySuggestedPassword();

form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const fullnameValue = fullnameInput.value.trim();
    const fullnameValid = fullnameValue.length >= 3 && fullnameValue.length <= MAX_CREDENTIAL_LENGTH;
    const emailValue = emailInput.value.trim();
    const classificationValue = accountClassificationInput ? accountClassificationInput.value.trim() : '';
    const branchValue = branchInput.value.trim();
    const passwordValue = passwordInput.value;
    const confirmPasswordValue = confirmPasswordInput.value;
    const emailValid = emailValue.length <= MAX_EMAIL_LENGTH && validateEmail(emailValue);
    const classificationValid = VALID_ACCOUNT_CLASSIFICATIONS.includes(classificationValue);
    const branchValid = VALID_BRANCHES.includes(branchValue);
    const passwordValid = passwordValue.length >= 8 && passwordValue.length <= MAX_CREDENTIAL_LENGTH;
    const confirmPasswordValid = confirmPasswordValue === passwordValue && confirmPasswordValue.length >= 8 && confirmPasswordValue.length <= MAX_CREDENTIAL_LENGTH;

    fullnameInput.classList.toggle('is-invalid', !fullnameValid);
    fullnameInput.classList.toggle('is-valid', fullnameValid);
    emailInput.classList.toggle('is-invalid', !emailValid);
    emailInput.classList.toggle('is-valid', emailValid);
    if (accountClassificationInput) {
        accountClassificationInput.classList.toggle('is-invalid', !classificationValid);
        accountClassificationInput.classList.toggle('is-valid', classificationValid);
    }
    branchInput.classList.toggle('is-invalid', !branchValid);
    branchInput.classList.toggle('is-valid', branchValid);
    passwordInput.classList.toggle('is-invalid', !passwordValid);
    passwordInput.classList.toggle('is-valid', passwordValid);
    confirmPasswordInput.classList.toggle('is-invalid', !confirmPasswordValid);
    confirmPasswordInput.classList.toggle('is-valid', confirmPasswordValid);

    if (!fullnameValid || !emailValid || !classificationValid || !branchValid || !passwordValid || !confirmPasswordValid) {
        showToast('error', 'Validation Failed', 'Please fill in all required fields correctly.');
        return;
    }

    // Submit to handler
    const formData = new FormData(form);
    
    fetch('../handlers/register.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Account Added!', data.message || 'The account was created successfully.');
            resetRegisterFormState();
        } else {
            showToast('error', 'Registration Failed', data.message || 'Please try again.');
        }
    })
    .catch(error => {
        showToast('error', 'Error', 'An error occurred. Please try again.');
        console.error('Error:', error);
    });
});
</script>

</body>
</html>
