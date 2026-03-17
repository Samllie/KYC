<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC System — Dashboard</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

<!-- ═══════════════════════════════════════════════ SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <a href="#" class="brand-logo">
            <div class="brand-icon"><i class="bi bi-shield-check"></i></div>
            <div class="brand-text">
                <span>STerling Insurance Company</span>
                <strong>KYC System</strong>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Main Menu</div>

        <a href="dashboard.php" class="nav-item active">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>
        <a href="clients.php" class="nav-item">
            <i class="bi bi-people"></i> Clients
            <span class="nav-badge">24</span>
        </a>
        <a href="index.php" class="nav-item">
            <i class="bi bi-person-check"></i> KYC Verification
        </a>
        <a href="policy.php" class="nav-item">
            <i class="bi bi-file-earmark-text"></i> Policy Issuance
        </a>

        <div class="nav-label">Analytics</div>

        <a href="#" class="nav-item">
            <i class="bi bi-bar-chart"></i> Reports
        </a>
        <a href="#" class="nav-item">
            <i class="bi bi-bell"></i> Notifications
            <span class="nav-badge">3</span>
        </a>
        <a href="#" class="nav-item">
            <i class="bi bi-gear"></i> Settings
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar">JD</div>
            <div class="user-info">
                <span>Juan Dela Cruz</span>
                <span>KYC Officer</span>
            </div>
            <i class="bi bi-three-dots-vertical" style="color:rgba(255,255,255,.35);margin-left:auto;"></i>
        </div>
    </div>
</aside>

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
            <button class="topbar-btn" title="Search"><i class="bi bi-search"></i></button>
            <button class="topbar-btn" title="Notifications">
                <i class="bi bi-bell"></i>
                <span class="notif-dot"></span>
            </button>
            <button class="topbar-btn" title="Help"><i class="bi bi-question-circle"></i></button>
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
            <a href="index.php">
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

        <!-- Charts Row -->
        <div class="dashboard-row">
            <!-- Verification Status Chart -->
            <div class="card" style="grid-column: span 2;">
                <div class="card-header">
                    <div>
                        <div class="card-title">Verification Status</div>
                        <div class="card-subtitle">Overview of KYC verification progress</div>
                    </div>
                    <div class="card-actions">
                        <button class="card-btn" title="Refresh"><i class="bi bi-arrow-clockwise"></i></button>
                        <button class="card-btn" title="More"><i class="bi bi-three-dots"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-placeholder">
                        <div class="chart-bar">
                            <div class="chart-bar-item">
                                <div class="chart-label">Verified</div>
                                <div class="chart-fill" style="width: 78%; background: var(--green-500);"></div>
                                <div class="chart-value">194 (78%)</div>
                            </div>
                            <div class="chart-bar-item">
                                <div class="chart-label">Pending</div>
                                <div class="chart-fill" style="width: 15%; background: var(--warning);"></div>
                                <div class="chart-value">37 (15%)</div>
                            </div>
                            <div class="chart-bar-item">
                                <div class="chart-label">Rejected</div>
                                <div class="chart-fill" style="width: 7%; background: var(--danger);"></div>
                                <div class="chart-value">17 (7%)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Quick Summary</div>
                        <div class="card-subtitle">Last 30 days</div>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="summary-list">
                        <li>
                            <span class="summary-icon" style="background: var(--green-100);"><i class="bi bi-check-circle" style="color: var(--green-500);"></i></span>
                            <div>
                                <div class="summary-title">Successful Verifications</div>
                                <div class="summary-value">+8</div>
                            </div>
                        </li>
                        <li>
                            <span class="summary-icon" style="background: #FEF3C7;"><i class="bi bi-exclamation-circle" style="color: var(--warning);"></i></span>
                            <div>
                                <div class="summary-title">Pending Review</div>
                                <div class="summary-value">37</div>
                            </div>
                        </li>
                        <li>
                            <span class="summary-icon" style="background: #FEE2E2;"><i class="bi bi-x-circle" style="color: var(--danger);"></i></span>
                            <div>
                                <div class="summary-title">Rejections</div>
                                <div class="summary-value">+2</div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Recent Activity
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Recent Activity</div>
                    <div class="card-subtitle">Latest KYC verification updates</div>
                </div>
                <a href="#" class="link" style="font-size: 0.85rem;">View All</a>
            </div>
            <div class="card-body">
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon" style="background: var(--green-100);"><i class="bi bi-check-circle" style="color: var(--green-500);"></i></div>
                        <div class="activity-info">
                            <div class="activity-title">Maria Garcia's KYC Verified</div>
                            <div class="activity-desc">Successfully completed all verification steps</div>
                        </div>
                        <div class="activity-time">2 hours ago</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon" style="background: #FEF3C7;"><i class="bi bi-hourglass-split" style="color: var(--warning);"></i></div>
                        <div class="activity-info">
                            <div class="activity-title">Robert Santos Under Review</div>
                            <div class="activity-desc">Documents submitted and waiting for verification</div>
                        </div>
                        <div class="activity-time">5 hours ago</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon" style="background: #FEE2E2;"><i class="bi bi-x-circle" style="color: var(--danger);"></i></div>
                        <div class="activity-info">
                            <div class="activity-title">John Reyes KYC Rejected</div>
                            <div class="activity-desc">Documents do not meet verification requirements</div>
                        </div>
                        <div class="activity-time">1 day ago</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon" style="background: var(--info); opacity: 0.2;"><i class="bi bi-person-plus" style="color: var(--info);"></i></div>
                        <div class="activity-info">
                            <div class="activity-title">New Client Added</div>
                            <div class="activity-desc">Angela Torres started KYC verification process</div>
                        </div>
                        <div class="activity-time">2 days ago</div>
                    </div>
                </div>
            </div>
        </div> -->

        <!-- Top Performers & Quick Actions -->
        <div class="dashboard-row">
            <!-- Top Performers -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Top Performers</div>
                        <div class="card-subtitle">This month</div>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="performer-list">
                        <li>
                            <div class="performer-rank">🥇</div>
                            <div class="performer-info">
                                <div class="performer-name">Maria Rodriguez</div>
                                <div class="performer-stat">45 verifications</div>
                            </div>
                        </li>
                        <li>
                            <div class="performer-rank">🥈</div>
                            <div class="performer-info">
                                <div class="performer-name">Juan Santos</div>
                                <div class="performer-stat">38 verifications</div>
                            </div>
                        </li>
                        <li>
                            <div class="performer-rank">🥉</div>
                            <div class="performer-info">
                                <div class="performer-name">LuisaCruz</div>
                                <div class="performer-stat">32 verifications</div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Quick Actions</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="action-buttons">
                        <button class="action-btn">
                            <i class="bi bi-person-plus"></i>
                            <span>New Client</span>
                        </button>
                        <button class="action-btn">
                            <i class="bi bi-file-earmark-check"></i>
                            <span>Review KYC</span>
                        </button>
                        <button class="action-btn">
                            <i class="bi bi-file-earmark-pdf"></i>
                            <span>Generate Report</span>
                        </button>
                        <button class="action-btn">
                            <i class="bi bi-envelope"></i>
                            <span>Send Notice</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </main>

</div>

</body>
</html>
