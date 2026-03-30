<?php
require_once '../config/session.php';
require_once '../config/db.php';
requireLogin();

$stats = fetchOne("SELECT
    COUNT(*) AS total_clients,
    SUM(verification_status = 'verified') AS verified_count
FROM clients") ?? [];

$verifiedTodayRow = fetchOne("SELECT COUNT(*) AS verified_today FROM clients WHERE verification_status = 'verified' AND DATE(verification_date) = CURDATE()") ?? [];
$newThisWeekRow = fetchOne("SELECT COUNT(*) AS new_this_week FROM clients WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)") ?? [];

$kycFunnel = fetchOne("SELECT
    SUM(status = 'draft') AS draft_count,
    SUM(status = 'submitted') AS submitted_count,
    SUM(status = 'in_progress') AS in_progress_count
FROM kyc_verifications") ?? [];

$clientTypeSplit = fetchOne("SELECT
    SUM(client_type = 'individual') AS individual_count,
    SUM(client_type IN ('corporate', 'obligee')) AS corporate_count
FROM clients") ?? [];

$recentActivity = fetchAll("SELECT
    c.client_id,
    c.reference_code,
    c.client_type,
    c.verification_status,
    COALESCE(NULLIF(c.client_name, ''), TRIM(CONCAT(c.first_name, ' ', c.last_name))) AS display_name,
    COALESCE(c.submitted_at, c.created_at) AS action_time,
    COALESCE(u.full_name, 'System') AS submitted_by_name
FROM clients c
LEFT JOIN users u ON u.user_id = c.submitted_by
ORDER BY COALESCE(c.submitted_at, c.created_at) DESC
LIMIT 6");

$totalClients = intval($stats['total_clients'] ?? 0);
$verifiedCount = intval($stats['verified_count'] ?? 0);
$verifiedToday = intval($verifiedTodayRow['verified_today'] ?? 0);
$newThisWeek = intval($newThisWeekRow['new_this_week'] ?? 0);

$individualCount = intval($clientTypeSplit['individual_count'] ?? 0);
$corporateCount = intval($clientTypeSplit['corporate_count'] ?? 0);
$typeTotal = max(1, $individualCount + $corporateCount);
$individualPct = round(($individualCount / $typeTotal) * 100);
$corporatePct = 100 - $individualPct;

$funnelDraft = intval($kycFunnel['draft_count'] ?? 0);
$funnelSubmitted = intval($kycFunnel['submitted_count'] ?? 0);
$funnelInProgress = intval($kycFunnel['in_progress_count'] ?? 0);
$funnelTotal = max(1, $funnelDraft + $funnelSubmitted + $funnelInProgress);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function relativeTime(?string $dateTime): string {
    if (!$dateTime) {
        return 'just now';
    }

    $timestamp = strtotime($dateTime);
    if (!$timestamp) {
        return 'just now';
    }

    $diff = time() - $timestamp;
    if ($diff < 60) {
        return 'just now';
    }

    if ($diff < 3600) {
        return floor($diff / 60) . ' min ago';
    }

    if ($diff < 86400) {
        return floor($diff / 3600) . ' hr ago';
    }

    return floor($diff / 86400) . ' day ago';
}
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
    <link rel="stylesheet" href="../../public/css/dashboard.css">
    <link rel="stylesheet" href="../../public/css/global.css">
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
                <div class="day-label"><?php echo e(date('l, F j')); ?></div>
                <h2>KYC operations center</h2>
                <p><?php echo e($newThisWeek); ?> new client<?php echo $newThisWeek === 1 ? '' : 's'; ?> added in the last 7 days.</p>
            </div>

            <div class="add client hero-action">
            <a href="kyc-verification.php">
                <button class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> New Client
                </button>
            </a>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-value"><?php echo e(number_format($totalClients)); ?></div>
                    <div class="stat-label">Total Clients</div>
                    <div class="stat-change up"><i class="bi bi-arrow-up-short"></i> +<?php echo e($newThisWeek); ?> this week</div>
                </div>
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-value"><?php echo e(number_format($verifiedCount)); ?></div>
                    <div class="stat-label">Verified Clients</div>
                    <div class="stat-change up"><i class="bi bi-check2-circle"></i> <?php echo e($verifiedToday); ?> verified today</div>
                </div>
                <div class="stat-icon"><i class="bi bi-patch-check-fill"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-value"><?php echo e(number_format($individualCount)); ?></div>
                    <div class="stat-label">Individual Clients</div>
                    <div class="stat-change up"><i class="bi bi-person"></i> <?php echo e($individualPct); ?>% of total clients</div>
                </div>
                <div class="stat-icon"><i class="bi bi-person-fill"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-value"><?php echo e(number_format($corporateCount)); ?></div>
                    <div class="stat-label">Corporate / Obligee</div>
                    <div class="stat-change up"><i class="bi bi-building"></i> <?php echo e($corporatePct); ?>% of total clients</div>
                </div>
                <div class="stat-icon"><i class="bi bi-building-fill"></i></div>
            </div>
        </div>

        <section class="dashboard-grid">
            <div class="card quick-card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Quick Actions</h3>
                        <div class="card-subtitle">Most-used workflow shortcuts</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="action-buttons">
                        <a class="action-btn" href="kyc-individual.php"><i class="bi bi-person-plus"></i><span>New Individual</span></a>
                        <a class="action-btn" href="kyc-corporate.php"><i class="bi bi-building-add"></i><span>New Corporate</span></a>
                        <a class="action-btn" href="kyc-corporate.php?clientType=obligee"><i class="bi bi-shield-check"></i><span>New Obligee</span></a>
                        <a class="action-btn" href="kyc-verification.php"><i class="bi bi-file-earmark-check"></i><span>Continue Draft</span></a>
                        <a class="action-btn" href="clients.php"><i class="bi bi-inboxes"></i><span>View Clients</span></a>
                    </div>
                </div>
            </div>

            <div class="card pipeline-card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">KYC Pipeline</h3>
                        <div class="card-subtitle">Current flow across active statuses</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="pipeline-list">
                        <div class="pipeline-item">
                            <div class="pipeline-head"><span>Draft</span><strong><?php echo e($funnelDraft); ?></strong></div>
                            <div class="pipeline-track"><span style="width: <?php echo e(round(($funnelDraft / $funnelTotal) * 100)); ?>%;"></span></div>
                        </div>
                        <div class="pipeline-item">
                            <div class="pipeline-head"><span>Submitted</span><strong><?php echo e($funnelSubmitted); ?></strong></div>
                            <div class="pipeline-track"><span style="width: <?php echo e(round(($funnelSubmitted / $funnelTotal) * 100)); ?>%;"></span></div>
                        </div>
                        <div class="pipeline-item">
                            <div class="pipeline-head"><span>In Progress</span><strong><?php echo e($funnelInProgress); ?></strong></div>
                            <div class="pipeline-track"><span style="width: <?php echo e(round(($funnelInProgress / $funnelTotal) * 100)); ?>%;"></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card split-card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Client Mix</h3>
                        <div class="card-subtitle">Individual vs corporate/obligee split</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="split-visual">
                        <div class="split-bar">
                            <span class="individual" style="width: <?php echo e($individualPct); ?>%;"></span>
                            <span class="corporate" style="width: <?php echo e($corporatePct); ?>%;"></span>
                        </div>
                        <div class="split-legend">
                            <div><i class="bi bi-circle-fill"></i> Individual: <?php echo e($individualCount); ?> (<?php echo e($individualPct); ?>%)</div>
                            <div><i class="bi bi-circle-fill"></i> Corporate/Obligee: <?php echo e($corporateCount); ?> (<?php echo e($corporatePct); ?>%)</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="card activity-card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Recent Activity</h3>
                    <div class="card-subtitle">Latest KYC submissions and updates</div>
                </div>
                <a href="clients.php" class="link">View all clients</a>
            </div>
            <div class="card-body">
                <div class="activity-list">
                    <?php if (empty($recentActivity)): ?>
                        <div class="empty-state">No activity available yet.</div>
                    <?php else: ?>
                        <?php foreach ($recentActivity as $row): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="bi bi-clipboard2-check"></i>
                                </div>
                                <div class="activity-info">
                                    <div class="activity-title"><?php echo e($row['display_name'] ?: 'Unnamed Client'); ?> (<?php echo e($row['reference_code']); ?>)</div>
                                    <div class="activity-desc"><?php echo e(ucfirst($row['client_type'])); ?> client record · Submitted by <?php echo e($row['submitted_by_name']); ?></div>
                                </div>
                                <div class="activity-time"><?php echo e(relativeTime($row['action_time'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

</div>

</body>
</html>
