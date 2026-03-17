<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC System — Register</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body>

<!-- ═══════════════════════════════════════════════ AUTH CONTAINER -->
<div class="auth-container">
    <div class="auth-wrapper">
        
        <!-- Left Side - Branding -->
        <div class="auth-brand">
            <div class="brand-logo-large">
                <i class="bi bi-shield-check"></i>
            </div>
            <h1>STerling Insurance Company</h1>
            <p>KYC System</p>
            <div class="brand-description">
                <p>Secure Know Your Customer verification system for streamlined client onboarding and compliance.</p>
            </div>
        </div>

        <!-- Right Side - Register Form -->
        <div class="auth-form-container">
            <div class="auth-form">
                <div class="form-header">
                    <h2>Create Account</h2>
                    <p>Register as a KYC Officer</p>
                </div>

                <form id="registerForm" method="POST">
                    <!-- Full Name Field -->
                    <div class="form-group">
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
                                required>
                        </div>
                        <div class="form-error"></div>
                    </div>

                    <!-- Email Field -->
                    <div class="form-group">
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
                                placeholder="your@email.com" 
                                required>
                        </div>
                        <div class="form-error"></div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password" class="form-label">
                            Password <span class="req">*</span>
                        </label>
                        <div class="input-icon-wrap">
                            <i class="bi bi-lock"></i>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                placeholder="At least 8 characters" 
                                required>
                        </div>
                        <div class="form-hint">Password must be at least 8 characters long</div>
                        <div class="form-error"></div>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="form-group">
                        <label for="confirmPassword" class="form-label">
                            Confirm Password <span class="req">*</span>
                        </label>
                        <div class="input-icon-wrap">
                            <i class="bi bi-lock-check"></i>
                            <input 
                                type="password" 
                                id="confirmPassword" 
                                name="confirmPassword" 
                                class="form-control" 
                                placeholder="Re-enter your password" 
                                required>
                        </div>
                        <div class="form-error"></div>
                    </div>

                    <!-- Department Field -->
                    <div class="form-group">
                        <label for="department" class="form-label">
                            Department <span class="req">*</span>
                        </label>
                        <div class="select-wrap">
                            <select id="department" name="department" class="form-select" required>
                                <option value="">Select department...</option>
                                <option value="kyc-officer">KYC Officer</option>
                                <option value="compliance">Compliance</option>
                                <option value="operations">Operations</option>
                                <option value="management">Management</option>
                            </select>
                        </div>
                        <div class="form-error"></div>
                    </div>

                    <!-- Terms & Conditions -->
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="terms" name="terms" required>
                            <span>I agree to the <a href="#" class="link">Terms & Conditions</a></span>
                        </label>
                        <div class="form-error"></div>
                    </div>

                    <!-- Register Button -->
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="bi bi-person-plus"></i> Create Account
                    </button>
                </form>

                <!-- Divider -->
                <div class="form-divider">
                    <span>Already have an account?</span>
                </div>

                <!-- Login Link -->
                <a href="login.php" class="btn btn-outline btn-block">
                    <i class="bi bi-box-arrow-right"></i> Sign In
                </a>

                <!-- Footer -->
                <div class="auth-footer">
                    <p>&copy; 2026 STerling Insurance Company. All rights reserved.</p>
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
const passwordInput = document.getElementById('password');
const confirmPasswordInput = document.getElementById('confirmPassword');
const departmentInput = document.getElementById('department');
const termsInput = document.getElementById('terms');

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = false;

    if (field.id === 'fullname') {
        isValid = value.length >= 3;
    } else if (field.id === 'email') {
        isValid = validateEmail(value);
    } else if (field.id === 'password') {
        isValid = value.length >= 8;
    } else if (field.id === 'confirmPassword') {
        isValid = value === passwordInput.value && value.length >= 8;
    } else if (field.id === 'department') {
        isValid = value !== '';
    }

    field.classList.toggle('is-invalid', !isValid && value !== '');
    field.classList.toggle('is-valid', isValid);
    return isValid || (field.id !== 'fullname' && field.id !== 'department' && value === '');
}

fullnameInput.addEventListener('blur', () => validateField(fullnameInput));
emailInput.addEventListener('blur', () => validateField(emailInput));
passwordInput.addEventListener('blur', () => validateField(passwordInput));
confirmPasswordInput.addEventListener('blur', () => validateField(confirmPasswordInput));
departmentInput.addEventListener('blur', () => validateField(departmentInput));

fullnameInput.addEventListener('input', () => {
    if (fullnameInput.classList.contains('is-invalid')) validateField(fullnameInput);
});

emailInput.addEventListener('input', () => {
    if (emailInput.classList.contains('is-invalid')) validateField(emailInput);
});

passwordInput.addEventListener('input', () => {
    if (passwordInput.classList.contains('is-invalid')) validateField(passwordInput);
    if (confirmPasswordInput.classList.contains('is-invalid')) validateField(confirmPasswordInput);
});

confirmPasswordInput.addEventListener('input', () => {
    if (confirmPasswordInput.classList.contains('is-invalid')) validateField(confirmPasswordInput);
});

departmentInput.addEventListener('change', () => {
    if (departmentInput.classList.contains('is-invalid')) validateField(departmentInput);
});

form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const fullnameValid = fullnameInput.value.trim().length >= 3;
    const emailValid = validateEmail(emailInput.value);
    const passwordValid = passwordInput.value.length >= 8;
    const confirmPasswordValid = confirmPasswordInput.value === passwordInput.value && confirmPasswordInput.value.length >= 8;
    const departmentValid = departmentInput.value !== '';
    const termsValid = termsInput.checked;

    fullnameInput.classList.toggle('is-invalid', !fullnameValid);
    fullnameInput.classList.toggle('is-valid', fullnameValid);
    emailInput.classList.toggle('is-invalid', !emailValid);
    emailInput.classList.toggle('is-valid', emailValid);
    passwordInput.classList.toggle('is-invalid', !passwordValid);
    passwordInput.classList.toggle('is-valid', passwordValid);
    confirmPasswordInput.classList.toggle('is-invalid', !confirmPasswordValid);
    confirmPasswordInput.classList.toggle('is-valid', confirmPasswordValid);
    departmentInput.classList.toggle('is-invalid', !departmentValid);
    departmentInput.classList.toggle('is-valid', departmentValid);

    if (!fullnameValid || !emailValid || !passwordValid || !confirmPasswordValid || !departmentValid || !termsValid) {
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
            showToast('success', 'Account Created!', 'Redirecting to login...');
            setTimeout(() => {
                window.location.href = data.redirect || 'login.php';
            }, 1500);
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
