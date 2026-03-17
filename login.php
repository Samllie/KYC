<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC System — Login</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/auth.css">
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

        <!-- Right Side - Login Form -->
        <div class="auth-form-container">
            <div class="auth-form">
                <div class="form-header">
                    <h2>Welcome Back</h2>
                    <p>Sign in to your account</p>
                </div>

                <form id="loginForm" method="POST">
                    <!-- Email Field -->
                    <div class="form-group">
                        <label for="email" class="form-label">
                            Email Address
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
                            Password
                        </label>
                        <div class="input-icon-wrap">
                            <i class="bi bi-lock"></i>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                placeholder="Enter your password" 
                                required>
                        </div>
                        <div class="form-error"></div>
                    </div>

                    <!-- Remember & Forgot Password -->
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" id="remember" name="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>

                    <!-- Login Button -->
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="bi bi-box-arrow-right"></i> Sign In
                    </button>
                </form>

                <!-- Divider -->
                <div class="form-divider">
                    <span>Don't have an account?</span>
                </div>

                <!-- Register Link -->
                <a href="register.php" class="btn btn-outline btn-block">
                    <i class="bi bi-person-plus"></i> Create Account
                </a>

                <!-- Footer -->
                <div class="auth-footer">
                    <p>&copy; 2026 Sterling Insurance Company. All rights reserved.</p>
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
const form = document.getElementById('loginForm');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = false;

    if (field.id === 'email') {
        isValid = validateEmail(value);
    } else if (field.id === 'password') {
        isValid = value.length >= 6;
    }

    field.classList.toggle('is-invalid', !isValid && value !== '');
    field.classList.toggle('is-valid', isValid);
    return isValid || value === '';
}

emailInput.addEventListener('blur', () => validateField(emailInput));
passwordInput.addEventListener('blur', () => validateField(passwordInput));

emailInput.addEventListener('input', () => {
    if (emailInput.classList.contains('is-invalid')) validateField(emailInput);
});

passwordInput.addEventListener('input', () => {
    if (passwordInput.classList.contains('is-invalid')) validateField(passwordInput);
});

form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const emailValid = emailInput.value.trim() !== '' && validateEmail(emailInput.value);
    const passwordValid = passwordInput.value.trim() !== '' && passwordInput.value.length >= 6;

    emailInput.classList.toggle('is-invalid', !emailValid);
    emailInput.classList.toggle('is-valid', emailValid);
    passwordInput.classList.toggle('is-invalid', !passwordValid);
    passwordInput.classList.toggle('is-valid', passwordValid);

    if (!emailValid || !passwordValid) {
        showToast('error', 'Validation Failed', 'Please enter valid email and password.');
        return;
    }

    // Submit to handler
    const formData = new FormData(form);
    
    fetch('handlers/login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Login Successful', 'Redirecting to dashboard...');
            setTimeout(() => {
                window.location.href = data.redirect || 'dashboard.php';
            }, 1500);
        } else {
            showToast('error', 'Login Failed', data.message || 'Invalid credentials');
            passwordInput.value = '';
            emailInput.classList.remove('is-valid');
            passwordInput.classList.remove('is-valid');
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
