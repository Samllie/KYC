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

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" href="../../public/css/index.css">
	<link rel="stylesheet" href="../../public/css/global.css">
	<style>
		.content {
			display: grid;
			align-items: center;
			justify-items: center;
			min-height: calc(100vh - 64px);
		}

		.policy-coming-wrap {
			width: min(980px, 100%);
		}

		.policy-coming-banner {
			width: 100%;
			border-radius: 22px;
			padding: clamp(30px, 5vw, 52px);
			position: relative;
			overflow: hidden;
			border: 1px solid rgba(255, 255, 255, 0.62);
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
			width: 320px;
			height: 320px;
			right: -120px;
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
			font-size: clamp(2.2rem, 8vw, 4.2rem);
			line-height: 0.95;
			font-weight: 800;
			letter-spacing: 0.04em;
			text-transform: uppercase;
			text-shadow: 0 6px 24px rgba(0, 0, 0, 0.22);
		}

		.policy-coming-sub {
			margin: 16px auto 0;
			max-width: 760px;
			font-size: 0.95rem;
			line-height: 1.6;
			color: rgba(240, 253, 244, 0.95);
		}

		.policy-hero {
			padding: 28px;
			border-radius: var(--radius-lg);
			border: 1px solid #cfe2d8;
			background:
				radial-gradient(circle at 12% 15%, rgba(195, 243, 211, 0.46), transparent 44%),
				linear-gradient(125deg, #fbfffc 0%, #f2fbf6 48%, #ffffff 100%);
			box-shadow: var(--shadow-sm);
			margin-bottom: 18px;
		}

		.policy-hero .policy-kicker {
			font-size: 0.74rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.08em;
			color: #1a6b46;
			margin-bottom: 10px;
		}

		.policy-hero h2 {
			font-size: 1.85rem;
			line-height: 1.1;
			color: #123222;
			margin-bottom: 8px;
		}

		.policy-hero p {
			font-size: 0.92rem;
			line-height: 1.65;
			color: #4a6660;
			max-width: 760px;
		}

		.policy-grid {
			display: grid;
			grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
			gap: 18px;
		}

		.policy-list {
			display: flex;
			flex-direction: column;
			gap: 10px;
		}

		.policy-item {
			display: grid;
			grid-template-columns: auto 1fr auto;
			gap: 10px;
			align-items: center;
			padding: 10px;
			border-radius: 10px;
			border: 1px solid #d8e7de;
			background: #fbfefc;
		}

		.policy-item i {
			color: #1a7348;
			font-size: 1rem;
		}

		.policy-item strong {
			font-size: 0.86rem;
			color: #173227;
		}

		.policy-item small {
			display: block;
			margin-top: 2px;
			font-size: 0.76rem;
			color: #5b7570;
		}

		.policy-tag {
			padding: 5px 9px;
			border-radius: 999px;
			font-size: 0.68rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.04em;
		}

		.policy-tag.live {
			background: #e4f7ea;
			color: #0f5c36;
		}

		.policy-tag.building {
			background: #fff7d8;
			color: #846100;
		}

		.policy-tag.pending {
			background: #ecf4ff;
			color: #1f4f8c;
		}

		.policy-timeline {
			display: flex;
			flex-direction: column;
			gap: 12px;
		}

		.timeline-step {
			display: flex;
			gap: 10px;
			align-items: flex-start;
		}

		.timeline-dot {
			width: 26px;
			height: 26px;
			border-radius: 50%;
			display: grid;
			place-items: center;
			background: #e5f8ec;
			color: #11673d;
			font-size: 0.78rem;
			font-weight: 700;
			flex-shrink: 0;
		}

		.timeline-content {
			padding-top: 2px;
		}

		.timeline-content strong {
			display: block;
			font-size: 0.84rem;
			color: #173227;
			margin-bottom: 2px;
		}

		.timeline-content span {
			font-size: 0.76rem;
			color: #5d7771;
		}

		.policy-actions {
			display: flex;
			gap: 10px;
			flex-wrap: wrap;
			margin-top: 12px;
		}

		.policy-actions .btn:active {
			transform: translateY(1px) scale(0.98);
		}

		@media (max-width: 1024px) {
			.policy-grid {
				grid-template-columns: 1fr;
			}
		}

		@media (max-width: 768px) {
			.policy-hero {
				padding: 20px;
			}

			.policy-hero h2 {
				font-size: 1.5rem;
			}

			.policy-item {
				grid-template-columns: auto 1fr;
			}
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
					The policy issuance module is currently being finalized. We are preparing a complete workflow for policy details, approval, and document output.
				</p>
			</div>
		</section>
	</main>
</div>

</body>
</html>
