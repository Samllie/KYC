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
	<style>
		.policy-coming-wrap {
			min-height: calc(100vh - 150px);
			display: grid;
			place-items: center;
			padding: 18px;
		}

		.policy-coming-banner {
			width: min(980px, 100%);
			border-radius: 28px;
			padding: clamp(38px, 6vw, 72px);
			position: relative;
			overflow: hidden;
			border: 1px solid rgba(255, 255, 255, 0.6);
			background:
				radial-gradient(circle at 15% 20%, rgba(255, 255, 255, 0.48) 0%, rgba(255, 255, 255, 0) 40%),
				radial-gradient(circle at 88% 12%, rgba(240, 253, 244, 0.95) 0%, rgba(240, 253, 244, 0) 45%),
				linear-gradient(140deg, #0b3a2f 0%, #146c43 52%, #1f9d57 100%);
			box-shadow: 0 24px 54px rgba(20, 55, 43, 0.24);
			color: #ffffff;
			text-align: center;
			isolation: isolate;
		}

		.policy-coming-banner::before,
		.policy-coming-banner::after {
			content: '';
			position: absolute;
			border-radius: 999px;
			z-index: -1;
		}

		.policy-coming-banner::before {
			width: 340px;
			height: 340px;
			right: -110px;
			bottom: -140px;
			background: rgba(220, 252, 231, 0.12);
			border: 1px solid rgba(220, 252, 231, 0.25);
		}

		.policy-coming-banner::after {
			width: 220px;
			height: 220px;
			left: -80px;
			top: -90px;
			background: rgba(255, 255, 255, 0.1);
			border: 1px solid rgba(255, 255, 255, 0.2);
		}

		.policy-pill {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 8px 14px;
			border-radius: 999px;
			background: rgba(255, 255, 255, 0.18);
			border: 1px solid rgba(255, 255, 255, 0.3);
			font-size: 0.78rem;
			font-weight: 600;
			letter-spacing: 0.08em;
			text-transform: uppercase;
		}

		.policy-coming-title {
			margin-top: 14px;
			font-size: clamp(2.2rem, 8vw, 5.2rem);
			line-height: 0.95;
			font-weight: 800;
			letter-spacing: 0.04em;
			text-transform: uppercase;
			text-shadow: 0 6px 24px rgba(0, 0, 0, 0.22);
		}

		.policy-coming-sub {
			margin: 18px auto 0;
			max-width: 700px;
			font-size: clamp(0.92rem, 2vw, 1.12rem);
			color: rgba(240, 253, 244, 0.95);
			line-height: 1.6;
		}
	</style>
</head>
<body>

<?php
$activePage = 'policy';
include '../includes/sidebar.php';
?>

<div class="main">
	<header class="topbar">
		<div class="topbar-left">
			<h1>Policy</h1>
			<div class="breadcrumb-trail">
				<i class="bi bi-house" style="font-size:.65rem;"></i>
				Dashboard &rsaquo; <span>Policy</span>
			</div>
		</div>
		<div class="topbar-right">
		</div>
	</header>

	<main class="content">
		<section class="policy-coming-wrap">
			<div class="policy-coming-banner">
				<span class="policy-pill"><i class="bi bi-hourglass-split"></i> In Development</span>
				<h2 class="policy-coming-title">Coming Soon</h2>
				<p class="policy-coming-sub">
					The policy module is currently being prepared. We are building a complete and streamlined experience for policy details,
					status tracking, and updates.
				</p>
			</div>
		</section>
	</main>
</div>

</body>
</html>
