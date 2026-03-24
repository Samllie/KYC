<?php
/**
 * Switch Account
 *
 * Clears current session and lets user choose an account or sign in with another one.
 */
require_once '../config/session.php';

// Keep current session alive on this page so users can still go back to dashboard.
$hasActiveSession = isset($_SESSION['user_id']);

require_once '../config/db.php';

$accounts = [];
try {
	$accounts = fetchAll(
		"SELECT full_name, email, department, role FROM users WHERE status = 'active' ORDER BY full_name ASC LIMIT 30"
	);
} catch (Exception $e) {
	$accounts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>KYC System - Switch Account</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" href="../../public/css/auth.css">
	<style>
		.switch-accounts {
			display: grid;
			gap: 10px;
			margin: 14px 0 12px;
			max-height: 250px;
			overflow-y: auto;
			padding-right: 2px;
		}

		.switch-account-btn {
			width: 100%;
			border: 1px solid var(--gray-300);
			border-radius: 10px;
			background: #fff;
			padding: 10px 12px;
			text-align: left;
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			cursor: pointer;
			transition: var(--transition);
			text-decoration: none;
		}

		.switch-account-btn:hover {
			border-color: var(--green-500);
			background: var(--green-50);
		}

		.switch-account-main {
			min-width: 0;
		}

		.switch-account-name {
			font-size: 0.9rem;
			font-weight: 600;
			color: var(--gray-900);
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.switch-account-meta {
			font-size: 0.75rem;
			color: var(--gray-600);
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.switch-empty {
			border: 1px dashed var(--gray-300);
			border-radius: 10px;
			padding: 14px;
			color: var(--gray-600);
			font-size: 0.82rem;
			text-align: center;
			margin: 14px 0;
		}
	</style>
</head>
<body>
<div class="auth-container">
	<div class="auth-wrapper">
		<div class="auth-brand">
			<img src="../../public/images/SterlingLogo2.png" alt="Sterling Insurance Logo" class="brand-logo-image">
			<h1>Sterling Insurance Company</h1>
			<p>KYC System</p>
			<div class="brand-description">
				<p>Select an account to continue or sign in with another account.</p>
			</div>
		</div>

		<div class="auth-form-container">
			<div class="auth-form">
				<div class="panel-logo-wrap">
					<img src="../../public/images/SterlingLogo2.png" alt="Sterling Insurance Logo" class="panel-logo">
				</div>
				<div class="form-header">
					<h2>Switch Account</h2>
					<p>Choose an account or sign in with another one</p>
				</div>

				<?php if (!empty($accounts)): ?>
					<div class="switch-accounts">
						<?php foreach ($accounts as $account): ?>
							<a class="switch-account-btn" href="login.php?switch=1&email=<?php echo urlencode($account['email']); ?>">
								<div class="switch-account-main">
									<div class="switch-account-name"><?php echo htmlspecialchars($account['full_name']); ?></div>
									<div class="switch-account-meta"><?php echo htmlspecialchars($account['email']); ?> | <?php echo htmlspecialchars($account['department']); ?></div>
								</div>
								<i class="bi bi-chevron-right" style="color:#6b7280;"></i>
							</a>
						<?php endforeach; ?>
					</div>
				<?php else: ?>
					<div class="switch-empty">No active accounts found. Use another account sign in below.</div>
				<?php endif; ?>

				<a href="login.php?switch=1" class="btn btn-primary btn-block">
					<i class="bi bi-box-arrow-in-right"></i> Sign In With Another Account
				</a>

				<div class="form-divider">
					<span>Need a new account?</span>
				</div>

				<a href="register.php" class="btn btn-outline btn-block">
					<i class="bi bi-person-plus"></i> Create Account
				</a>

				<?php if ($hasActiveSession): ?>
					<a href="../pages/dashboard.php" class="btn btn-outline btn-block" style="margin-top: 10px;">
						<i class="bi bi-arrow-left"></i> Back to Dashboard
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
</body>
</html>

