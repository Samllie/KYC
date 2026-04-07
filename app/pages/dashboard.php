<?php
require_once '../config/session.php';
require_once '../config/db.php';
requireLogin();

$currentUserRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
$currentUserDepartment = strtoupper(trim((string)($_SESSION['department'] ?? '')));
$currentUserBranch = strtoupper(trim((string)($_SESSION['branch'] ?? '')));
$currentUserRoleNormalized = str_replace('-', '_', $currentUserRole);
$isHeadOfficeUser = $currentUserRole === 'admin'
    || $currentUserDepartment === 'HEAD OFFICE'
    || in_array($currentUserBranch, ['HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH'], true);
$isKycOfficerUser = $currentUserRoleNormalized === 'kyc_officer' && !$isHeadOfficeUser;

function tableExists(string $tableName): bool {
    global $db;

    if (!$db instanceof mysqli || trim($tableName) === '') {
        return false;
    }

    $stmt = $db->prepare(
        'SELECT 1
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name = ?
         LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $tableName);
    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }

    $result = $stmt->get_result();
    $exists = $result instanceof mysqli_result && $result->num_rows > 0;
    $stmt->close();

    return $exists;
}

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

function normalizeActivityStatus(?string $status): string {
    $value = strtolower(trim((string)$status));
    if ($value === '') {
        return 'pending';
    }

    if (in_array($value, ['approved', 'verified'], true)) {
        return 'approved';
    }

    if (in_array($value, ['declined', 'rejected'], true)) {
        return 'declined';
    }

    if (in_array($value, ['resubmit'], true)) {
        return 'resubmit';
    }

    return 'pending';
}

function activityStatusLabel(?string $status): string {
    $normalized = normalizeActivityStatus($status);
    if ($normalized === 'approved') {
        return 'Approved';
    }

    if ($normalized === 'declined') {
        return 'Declined';
    }

    if ($normalized === 'resubmit') {
        return 'Resubmit';
    }

    return 'Pending';
}

$workflowQuickAction = [
    'href' => 'kyc-verification.php',
    'icon' => 'bi-diagram-3',
    'label' => 'KYC Queue',
];

if ($isHeadOfficeUser) {
    $workflowQuickAction = [
        'href' => 'client-approvals.php',
        'icon' => 'bi-clipboard2-check',
        'label' => 'Approvals',
    ];
} elseif ($isKycOfficerUser) {
    $workflowQuickAction = [
        'href' => 'my-applications.php',
        'icon' => 'bi-clipboard-data',
        'label' => 'Applications',
    ];
}

$scopeLabel = $isHeadOfficeUser
    ? 'All branches'
    : ($currentUserBranch !== '' ? $currentUserBranch : 'Unassigned branch');

$clientsScopeWhere = '';
$clientsScopeParams = [];
if (!$isHeadOfficeUser) {
    if ($currentUserBranch !== '') {
        $clientsScopeWhere = " WHERE UPPER(TRIM(COALESCE(su.branch, ''))) = ?";
        $clientsScopeParams[] = $currentUserBranch;
    } else {
        $clientsScopeWhere = ' WHERE 1 = 0';
    }
}

$hasClientApprovalsTable = tableExists('client_approvals');

$approvalsScopeWhere = '';
$approvalsScopeParams = [];
if (!$isHeadOfficeUser) {
    if ($currentUserBranch !== '') {
        $approvalsScopeWhere = " WHERE UPPER(TRIM(COALESCE(ca.submitted_by_branch, su.branch, ''))) = ?";
        $approvalsScopeParams[] = $currentUserBranch;
    } else {
        $approvalsScopeWhere = ' WHERE 1 = 0';
    }
}

$stats = [];
$obligeeTodayRow = [];
$newThisWeekRow = [];
$clientTypeSplit = [];
$pipeline = [];
$recentActivity = [];

if ($hasClientApprovalsTable) {
    $stats = fetchOne("SELECT
        COUNT(*) AS total_clients,
        SUM(ca.client_type = 'obligee') AS obligee_count
    FROM client_approvals ca
    LEFT JOIN users su ON su.user_id = ca.submitted_by
    {$approvalsScopeWhere}", $approvalsScopeParams) ?? [];

    $obligeeTodayRow = fetchOne("SELECT
        COUNT(*) AS obligee_today
    FROM client_approvals ca
    LEFT JOIN users su ON su.user_id = ca.submitted_by
    {$approvalsScopeWhere}
    " . ($approvalsScopeWhere === '' ? 'WHERE' : ' AND') . " ca.client_type = 'obligee' AND DATE(COALESCE(ca.submitted_at, ca.created_at)) = CURDATE()", $approvalsScopeParams) ?? [];

    $newThisWeekRow = fetchOne("SELECT
        COUNT(*) AS new_this_week
    FROM client_approvals ca
    LEFT JOIN users su ON su.user_id = ca.submitted_by
    {$approvalsScopeWhere}
    " . ($approvalsScopeWhere === '' ? 'WHERE' : ' AND') . " COALESCE(ca.submitted_at, ca.created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY)", $approvalsScopeParams) ?? [];

    $clientTypeSplit = fetchOne("SELECT
        SUM(ca.client_type = 'individual') AS individual_count,
        SUM(ca.client_type = 'corporate') AS corporate_count,
        SUM(ca.client_type = 'obligee') AS obligee_count
    FROM client_approvals ca
    LEFT JOIN users su ON su.user_id = ca.submitted_by
    {$approvalsScopeWhere}", $approvalsScopeParams) ?? [];

    $pipeline = fetchOne("SELECT
        SUM(ca.approval_status = 'pending') AS pending_count,
        SUM(ca.approval_status = 'resubmit') AS resubmit_count,
        SUM(ca.approval_status = 'approved') AS approved_count
    FROM client_approvals ca
    LEFT JOIN users su ON su.user_id = ca.submitted_by
    {$approvalsScopeWhere}", $approvalsScopeParams) ?? [];

    $recentActivity = fetchAll("SELECT
        ca.client_id,
        ca.reference_code,
        ca.client_type,
        ca.client_classification,
        ca.approval_status AS activity_status,
        COALESCE(
            NULLIF(ca.display_name, ''),
            NULLIF(ca.client_name, ''),
            NULLIF(TRIM(CONCAT(COALESCE(ca.first_name, ''), ' ', COALESCE(ca.last_name, ''))), ''),
            ca.reference_code
        ) AS display_name,
        COALESCE(ca.reviewed_at, ca.submitted_at, ca.updated_at, ca.created_at) AS action_time,
        COALESCE(su.full_name, 'System') AS submitted_by_name,
        COALESCE(NULLIF(TRIM(ca.submitted_by_branch), ''), NULLIF(TRIM(su.branch), ''), 'N/A') AS submitted_by_branch
    FROM client_approvals ca
    LEFT JOIN users su ON su.user_id = ca.submitted_by
    {$approvalsScopeWhere}
    ORDER BY COALESCE(ca.reviewed_at, ca.submitted_at, ca.updated_at, ca.created_at) DESC
    LIMIT 6", $approvalsScopeParams);
} else {
    $stats = fetchOne("SELECT
        COUNT(*) AS total_clients,
        SUM(c.client_type = 'obligee') AS obligee_count
    FROM clients c
    LEFT JOIN users su ON su.user_id = c.submitted_by
    {$clientsScopeWhere}", $clientsScopeParams) ?? [];

    $obligeeTodayRow = fetchOne("SELECT
        COUNT(*) AS obligee_today
    FROM clients c
    LEFT JOIN users su ON su.user_id = c.submitted_by
    {$clientsScopeWhere}
    " . ($clientsScopeWhere === '' ? 'WHERE' : ' AND') . " c.client_type = 'obligee' AND DATE(c.created_at) = CURDATE()", $clientsScopeParams) ?? [];

    $newThisWeekRow = fetchOne("SELECT
        COUNT(*) AS new_this_week
    FROM clients c
    LEFT JOIN users su ON su.user_id = c.submitted_by
    {$clientsScopeWhere}
    " . ($clientsScopeWhere === '' ? 'WHERE' : ' AND') . " c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)", $clientsScopeParams) ?? [];

    $clientTypeSplit = fetchOne("SELECT
        SUM(c.client_type = 'individual') AS individual_count,
        SUM(c.client_type = 'corporate') AS corporate_count,
        SUM(c.client_type = 'obligee') AS obligee_count
    FROM clients c
    LEFT JOIN users su ON su.user_id = c.submitted_by
    {$clientsScopeWhere}", $clientsScopeParams) ?? [];

    $pipeline = fetchOne("SELECT
        SUM(k.status = 'submitted') AS pending_count,
        SUM(k.status = 'in_progress') AS resubmit_count,
        SUM(k.status = 'approved') AS approved_count
    FROM kyc_verifications k
    LEFT JOIN clients c ON c.client_id = k.client_id
    LEFT JOIN users su ON su.user_id = c.submitted_by
    {$clientsScopeWhere}", $clientsScopeParams) ?? [];

    $recentActivity = fetchAll("SELECT
        c.client_id,
        c.reference_code,
        c.client_type,
        c.client_classification,
        c.verification_status AS activity_status,
        COALESCE(NULLIF(c.client_name, ''), TRIM(CONCAT(c.first_name, ' ', c.last_name))) AS display_name,
        COALESCE(c.submitted_at, c.created_at) AS action_time,
        COALESCE(su.full_name, 'System') AS submitted_by_name,
        COALESCE(NULLIF(TRIM(su.branch), ''), 'N/A') AS submitted_by_branch
    FROM clients c
    LEFT JOIN users su ON su.user_id = c.submitted_by
    {$clientsScopeWhere}
    ORDER BY COALESCE(c.submitted_at, c.created_at) DESC
    LIMIT 6", $clientsScopeParams);
}

$totalClients = intval($stats['total_clients'] ?? 0);
$obligeeCount = intval($stats['obligee_count'] ?? 0);
$obligeeToday = intval($obligeeTodayRow['obligee_today'] ?? 0);
$newThisWeek = intval($newThisWeekRow['new_this_week'] ?? 0);

$individualCount = intval($clientTypeSplit['individual_count'] ?? 0);
$corporateCount = intval($clientTypeSplit['corporate_count'] ?? 0);
$obligeeMixCount = intval($clientTypeSplit['obligee_count'] ?? 0);
$typeTotal = max(1, $individualCount + $corporateCount + $obligeeMixCount);
$individualPct = round(($individualCount / $typeTotal) * 100);
$obligeePct = round(($obligeeMixCount / $typeTotal) * 100);
$corporatePct = max(0, 100 - $individualPct - $obligeePct);


$pipelinePending = intval($pipeline['pending_count'] ?? 0);
$pipelineResubmit = intval($pipeline['resubmit_count'] ?? 0);
$pipelineApproved = intval($pipeline['approved_count'] ?? 0);
$pipelineTotal = max(1, $pipelinePending + $pipelineResubmit + $pipelineApproved);
$dashboardDataSourceLabel = $hasClientApprovalsTable ? 'Approvals Queue' : 'Legacy Clients';
$dashboardDataSourceClass = $hasClientApprovalsTable ? 'is-approvals' : 'is-legacy';
$dashboardDataSourceIcon = $hasClientApprovalsTable ? 'bi-diagram-3' : 'bi-database';
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
<body class="kyc-compact">

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
            <div class="dashboard-source-indicator" aria-label="Dashboard data source">
                <span class="source-kicker">Data Source</span>
                <span class="source-pill <?php echo e($dashboardDataSourceClass); ?>">
                    <i class="bi <?php echo e($dashboardDataSourceIcon); ?>"></i>
                    <?php echo e($dashboardDataSourceLabel); ?>
                </span>
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
                    <div class="stat-value"><?php echo e(number_format($obligeeCount)); ?></div>
                    <div class="stat-label">Obligee Clients</div>
                    <div class="stat-change up"><i class="bi bi-shield-check"></i> <?php echo e($obligeeToday); ?> obligee added today</div>
                </div>
                <div class="stat-icon"><i class="bi bi-shield-check"></i></div>
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
                    <div class="stat-label">Corporate Clients</div>
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
                        <a class="action-btn" href="<?php echo e($workflowQuickAction['href']); ?>"><i class="bi <?php echo e($workflowQuickAction['icon']); ?>"></i><span><?php echo e($workflowQuickAction['label']); ?></span></a>
                        <a class="action-btn" href="clients.php"><i class="bi bi-inboxes"></i><span>View Clients</span></a>
                    </div>
                </div>
            </div>

            <div class="card pipeline-card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">KYC Pipeline</h3>
                        <div class="card-subtitle">Approval flow snapshot · <?php echo e($scopeLabel); ?></div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="pipeline-list">
                        <div class="pipeline-item pending">
                            <div class="pipeline-head"><span>Pending Review</span><strong><?php echo e($pipelinePending); ?></strong></div>
                            <div class="pipeline-track"><span style="width: <?php echo e(round(($pipelinePending / $pipelineTotal) * 100)); ?>%;"></span></div>
                        </div>
                        <div class="pipeline-item resubmit">
                            <div class="pipeline-head"><span>For Resubmission</span><strong><?php echo e($pipelineResubmit); ?></strong></div>
                            <div class="pipeline-track"><span style="width: <?php echo e(round(($pipelineResubmit / $pipelineTotal) * 100)); ?>%;"></span></div>
                        </div>
                        <div class="pipeline-item approved">
                            <div class="pipeline-head"><span>Approved</span><strong><?php echo e($pipelineApproved); ?></strong></div>
                            <div class="pipeline-track"><span style="width: <?php echo e(round(($pipelineApproved / $pipelineTotal) * 100)); ?>%;"></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card split-card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Client Mix</h3>
                        <div class="card-subtitle">Individual, corporate, and obligee split</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="split-visual">
                        <div class="split-bar">
                            <span class="individual" style="width: <?php echo e($individualPct); ?>%;"></span>
                            <span class="corporate" style="width: <?php echo e($corporatePct); ?>%;"></span>
                            <span class="obligee" style="width: <?php echo e($obligeePct); ?>%;"></span>
                        </div>
                        <div class="split-legend">
                            <div><i class="bi bi-circle-fill"></i> Individual: <?php echo e($individualCount); ?> (<?php echo e($individualPct); ?>%)</div>
                            <div><i class="bi bi-circle-fill"></i> Corporate: <?php echo e($corporateCount); ?> (<?php echo e($corporatePct); ?>%)</div>
                            <div><i class="bi bi-circle-fill"></i> Obligee: <?php echo e($obligeeMixCount); ?> (<?php echo e($obligeePct); ?>%)</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="card activity-card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Recent Activity</h3>
                    <div class="card-subtitle">
                        <?php if ($isHeadOfficeUser): ?>
                            Latest KYC updates across all branches
                        <?php else: ?>
                            Latest KYC updates for <?php echo e($scopeLabel); ?>
                        <?php endif; ?>
                    </div>
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
                                    <div class="activity-desc"><?php echo e(ucfirst($row['client_type'])); ?> <?php echo e(($row['client_classification'] ?? 'client') === 'agent' ? 'agent' : 'client'); ?> record · Submitted by <?php echo e($row['submitted_by_name']); ?></div>
                                    <div class="activity-meta">
                                        <span class="activity-status status-<?php echo e(normalizeActivityStatus($row['activity_status'] ?? 'pending')); ?>"><?php echo e(activityStatusLabel($row['activity_status'] ?? 'pending')); ?></span>
                                        <?php if ($isHeadOfficeUser): ?>
                                            <span class="activity-branch"><?php echo e($row['submitted_by_branch'] ?? 'N/A'); ?></span>
                                        <?php endif; ?>
                                    </div>
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
