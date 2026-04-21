<?php
/**
 * Switch Account
 *
 * Clears current session and lets user choose an account or sign in with another one.
 */
require_once '../config/session.php';
require_once '../config/device_accounts.php';

// Keep current session alive on this page so users can still go back to dashboard.
$hasActiveSession = isset($_SESSION['user_id']);

require_once '../config/db.php';

$accounts = [];
$knownEmails = deviceAccountsGetKnownEmails();

if ($hasActiveSession) {
	$currentEmail = strtolower(trim((string)($_SESSION['email'] ?? '')));
	if ($currentEmail !== '' && filter_var($currentEmail, FILTER_VALIDATE_EMAIL)) {
		deviceAccountsRememberEmail($currentEmail);
		$knownEmails = deviceAccountsGetKnownEmails();
	}
}

try {
	if (!empty($knownEmails)) {
		$placeholders = implode(',', array_fill(0, count($knownEmails), '?'));
		$accounts = fetchAll(
			"SELECT full_name, email, department, branch, role
			 FROM users
			 WHERE status = 'active'
			   AND LOWER(email) IN ($placeholders)
			 ORDER BY full_name ASC
			 LIMIT 30",
			$knownEmails
		);
	}
} catch (Exception $e) {
	$accounts = [];
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
	<style>
		.switch-accounts {
			display: grid;
			gap: 10px;
			margin: 14px 0 12px;
			max-height: 250px;
			overflow-y: auto;
			padding-right: 2px;
			scrollbar-gutter: stable;
		}

		.switch-account-btn {
			width: 100%;
			border: 1px solid var(--gray-300);
			border-radius: 10px;
			background: #fff;
			padding: 10px 12px;
			text-align: left;
			display: flex;
			flex-direction: row;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			cursor: pointer;
			transition: var(--transition);
			text-decoration: none;
			min-width: 0;
		}

		.switch-account-btn:hover {
			border-color: var(--green-500);
			background: var(--green-50);
		}

		.switch-account-main {
			min-width: 0;
			flex: 1 1 auto;
		}

		.switch-account-name {
			font-size: 0.9rem;
			font-weight: 600;
			color: var(--gray-900);
			line-height: 1.35;
			overflow-wrap: anywhere;
		}

		.switch-account-meta {
			display: flex;
			flex-wrap: wrap;
			gap: 4px 8px;
			font-size: 0.75rem;
			color: var(--gray-600);
			line-height: 1.35;
			margin-top: 2px;
		}

		.switch-account-meta-item {
			min-width: 0;
			overflow-wrap: anywhere;
		}

		.switch-account-btn > .switch-account-chevron {
			flex-shrink: 0;
			color: #6b7280;
			font-size: 0.95rem;
			align-self: center;
			margin-top: 0;
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

		@media (max-width: 768px) {
			.switch-accounts {
				max-height: clamp(180px, 30dvh, 240px);
				gap: 8px;
				margin: 12px 0 10px;
				padding-right: 4px;
			}

			.switch-account-btn {
				flex-direction: row;
				padding: 11px 12px;
				align-items: flex-start;
			}

			.switch-account-btn > .switch-account-chevron {
				margin-top: 2px;
			}
		}

		@media (max-width: 480px) {
			.switch-accounts {
				max-height: clamp(160px, 28dvh, 210px);
				gap: 7px;
				padding-right: 2px;
			}

			.switch-account-btn {
				flex-direction: row;
				padding: 10px 11px;
				gap: 10px;
			}

			.switch-account-name {
				font-size: 0.86rem;
			}

			.switch-account-meta {
				font-size: 0.72rem;
				gap: 2px 6px;
			}

			.switch-empty {
				padding: 12px;
				font-size: 0.8rem;
			}
		}
	</style>
</head>
<body>
<div class="auth-container login-layout">
	<div class="auth-wrapper">
		<div class="auth-brand">
			<h1>Sterling Insurance Company, Inc.</h1>
			<div class="brand-description">
				<p>Ensuring Integrity, Security, and Compliance in Every Client Engagement.</p>
			</div>
		</div>

		<div class="auth-form-container">
			<div class="auth-panel-logo" aria-hidden="true">
				<img src="../../css/images/SterlingLogo2.jpg" alt="" class="auth-panel-logo-image">
			</div>

			<div class="auth-form">
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
									<div class="switch-account-meta">
										<span class="switch-account-meta-item"><?php echo htmlspecialchars($account['email']); ?></span>
										<span class="switch-account-meta-item"><?php echo htmlspecialchars($account['department']); ?></span>
										<span class="switch-account-meta-item"><?php echo htmlspecialchars($account['branch']); ?></span>
									</div>
								</div>
								<i class="bi bi-chevron-right switch-account-chevron" aria-hidden="true"></i>
							</a>
						<?php endforeach; ?>
					</div>
				<?php else: ?>
					<div class="switch-empty">No previously used accounts were found on this device. Use another account sign in below.</div>
				<?php endif; ?>

				<a href="login.php?switch=1" class="btn btn-primary btn-block">
					<i class="bi bi-box-arrow-in-right"></i> Sign In With Another Account
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

