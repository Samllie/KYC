<?php
require_once '../config/session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC System — Dashboard</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/global.css">
</head>
<body>

<?php
$activePage = 'dashboard';
include '../includes/sidebar.php';
?>

<!-- ═══════════════════════════════════════════════ MAIN -->
<div class="main">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <h1>Dashboard</h1>
            <div class="breadcrumb-trail">
                <i class="bi bi-house" style="font-size:.65rem;"></i>
                <span>Home</span>
            </div>
        </div>
        <div class="topbar-right">

        </div>
    </header>

    <!-- Content -->
    <main class="content">

        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-text">
                <h2>Welcome back! 👋</h2>
                <p>Here's what's happening today!</p>
            </div>
            <div class="add client">
            <a href="kyc-verification.php">
                <button class="btn btn-primary" style="margin-left:auto;">
                    <i class="bi bi-plus-circle"></i> New Client
                </button>
            </a>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-value">248</div>
                    <div class="stat-label">Total Clients</div>
                    <div class="stat-change up"><i class="bi bi-arrow-up-short"></i> +12 this month</div>
                </div>
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-value">37</div>
                    <div class="stat-label">Pending KYC</div>
                    <div class="stat-change down"><i class="bi bi-arrow-down-short"></i> -5 from last week</div>
                </div>
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-value">194</div>
                    <div class="stat-label">Verified</div>
                    <div class="stat-change up"><i class="bi bi-arrow-up-short"></i> +8 this month</div>
                </div>
                <div class="stat-icon"><i class="bi bi-patch-check-fill"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-value">17</div>
                    <div class="stat-label">Rejected</div>
                    <div class="stat-change up"><i class="bi bi-arrow-up-short"></i> +2 this week</div>
                </div>
                <div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
            </div>
        </div>
    </main>

</div>

</body>
</html>
