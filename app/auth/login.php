<?php
require_once '../config/session.php';

$isSwitchMode = isset($_GET['switch']);

if (isset($_SESSION['user_id']) && !$isSwitchMode) {
    header('Location: ../pages/dashboard.php');
    exit();
}

if (isset($_SESSION['user_id']) && $isSwitchMode) {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }
    session_start();
}

$prefillEmail = trim($_GET['email'] ?? '');
$prefillEmail = filter_var($prefillEmail, FILTER_VALIDATE_EMAIL) ? $prefillEmail : '';

$rememberedEmail = trim($_COOKIE['remembered_email'] ?? '');
$rememberedEmail = filter_var($rememberedEmail, FILTER_VALIDATE_EMAIL) ? $rememberedEmail : '';

if ($prefillEmail === '' && $rememberedEmail !== '') {
    $prefillEmail = $rememberedEmail;
}

$isRememberChecked = $rememberedEmail !== '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sterling insurance Company Incorporated</title>
    <link rel='icon' type='image/png' href='../css/images/SterlingLogo.png'>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../public/css/auth.css">
</head>
<body>

<!-- ═══════════════════════════════════════════════ AUTH CONTAINER -->
<div class="auth-container">
    <div class="page-corner-logo-wrap" aria-hidden="true">
        <img src="../css/images/SterlingLogo2.jpg" alt="" class="page-corner-logo">
    </div>

    <div class="auth-wrapper">
        
        <!-- Left Side - Branding -->
        <div class="auth-brand">
            <h1>Sterling Insurance Company Incorporated</h1>
            <div class="brand-description">
                <p>Ensuring Integrity, Security, and Compliance in Every Client Engagement.</p>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="auth-form-container">
            <div class="auth-form">
                <div class="form-header">
                    <h2>Welcome</h2>
                    <p>Sign in to your account</p>
                </div>

                <?php if ($isSwitchMode): ?>
                    <div class="status-message status-info" style="display:block; margin-bottom: 14px;">
                        <i class="bi bi-arrow-left-right"></i>
                        Switch account mode: sign in to continue.
                    </div>
                <?php endif; ?>

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
                                value="<?php echo htmlspecialchars($prefillEmail); ?>"
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
                                <?php echo $prefillEmail ? 'autofocus' : ''; ?>
                                required>
                        </div>
                        <div class="form-error"></div>
                    </div>

                    <!-- Remember Me -->
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" id="remember" name="remember" <?php echo $isRememberChecked ? 'checked' : ''; ?>>
                            <span>Remember me</span>
                        </label>
                    </div>

                    <!-- Login Button -->
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="bi bi-box-arrow-right"></i> Login
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
    
    fetch('../handlers/logins.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Login Successful', 'Redirecting to dashboard...');
            setTimeout(() => {
                window.location.href = data.redirect || '../pages/dashboard.php';
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
