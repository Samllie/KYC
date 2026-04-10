<?php
require_once '../config/session.php';
require_once '../config/db.php';
requireLogin();

$currentUserRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
$currentUserDepartment = strtoupper(trim((string)($_SESSION['department'] ?? '')));
$currentUserBranch = strtoupper(trim((string)($_SESSION['branch'] ?? '')));
$isHeadOfficeUser = $currentUserRole === 'admin'
    || $currentUserDepartment === 'HEAD OFFICE'
    || in_array($currentUserBranch, ['HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH'], true);

if (!$isHeadOfficeUser) {
    header('Location: ../pages/dashboard.php');
    exit;
}

function accountValue(?string $value, string $fallback = 'N/A'): string
{
    $trimmed = trim((string)$value);
    return $trimmed !== '' ? $trimmed : $fallback;
}

function formatAccountDateTime(?string $value): string
{
    $trimmed = trim((string)$value);
    if ($trimmed === '') {
        return 'N/A';
    }

    $timestamp = strtotime($trimmed);
    return $timestamp ? date('M j, Y g:i A', $timestamp) : 'N/A';
}

function normalizeAccountStatus(?string $status): string
{
    $normalized = strtolower(trim((string)$status));
    if (!in_array($normalized, ['active', 'inactive', 'suspended'], true)) {
        return 'active';
    }

    return $normalized;
}

function formatRoleLabel(?string $role): string
{
    $normalized = strtolower(trim((string)$role));
    if ($normalized === '') {
        return 'N/A';
    }

    if ($normalized === 'kyc_officer') {
        return 'KYC Officer';
    }

    return ucwords(str_replace(['-', '_'], ' ', $normalized));
}

function formatDepartmentLabel(?string $department): string
{
    $normalized = trim((string)$department);
    if ($normalized === '') {
        return 'N/A';
    }

    $pretty = str_replace(['-', '_'], ' ', strtolower($normalized));
    return ucwords($pretty);
}

function formatAvatarInitials(?string $initials, ?string $fullName): string
{
    $value = strtoupper(trim((string)$initials));
    if ($value !== '') {
        return substr($value, 0, 2);
    }

    $name = trim((string)$fullName);
    if ($name === '') {
        return 'U';
    }

    $parts = preg_split('/\s+/', $name) ?: [];
    $result = '';
    foreach ($parts as $part) {
        $result .= strtoupper(substr($part, 0, 1));
        if (strlen($result) >= 2) {
            break;
        }
    }

    return $result !== '' ? substr($result, 0, 2) : 'U';
}

$allowedBranches = [
    'HEAD OFFICE',
    'ALABANG BRANCH',
    'MANILA BRANCH I',
    'MANILA BRANCH II',
    'WEST AVENUE BRANCH',
    'CUBAO BRANCH',
    'ANGELES BRANCH',
    'BATANGAS BRANCH',
    'BACOLOD BRANCH',
    'CABANATUAN BRANCH',
    'BUTUAN BRANCH',
    'CAGAYAN DE ORO BRANCH',
    'CEBU BRANCH',
    'CEBU REGIONAL OFFICE BRANCH',
    'DAGUPAN BRANCH',
    'DAVAO I BRANCH',
    'DAVAO II BRANCH',
    'GENSAN BRANCH',
    'ISABELA BRANCH',
    'LA UNION BRANCH',
    'LAOAG BRANCH',
    'LEGAZPI I BRANCH',
    'LEGAZPI II BRANCH',
    'MINDORO BRANCH',
    'NAGA BRANCH',
    'ORMOC BRANCH',
    'OZAMIZ BRANCH',
    'PAGADIAN BRANCH',
    'SAN FERNANDO, PAMPANGA BRANCH',
    'HEAD OFFICE BRANCH',
    'SMRO BRANCH',
    'TACLOBAN BRANCH',
    'TUGUEGARAO BRANCH',
    'VIGAN BRANCH',
    'ILOILO BRANCH'
];

$allowedRoles = [
    'admin' => 'Admin',
    'kyc_officer' => 'KYC Officer',
    'manager' => 'Branch Manager',
    'compliance' => 'Compliance Officer'
];

$accounts = [];
$accountCounts = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'suspended' => 0,
];
$accountsError = '';

try {
    $result = $db->query(
        "SELECT user_id, full_name, email, department, branch, role, avatar_initials, status, last_login, created_at, updated_at
         FROM users
         ORDER BY created_at DESC"
    );

    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $normalizedStatus = normalizeAccountStatus($row['status'] ?? 'active');
            $accounts[] = $row;
            $accountCounts['total']++;
            $accountCounts[$normalizedStatus]++;
        }

        $result->free();
    } else {
        $accountsError = $db->error ?: 'Unable to load accounts.';
    }
} catch (Throwable $e) {
    $accountsError = $e->getMessage();
}

$activePage = 'accounts-management';
$pageHeading = 'Accounts Management';
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
    <link rel="stylesheet" href="../../public/css/clients.css">
    <link rel="stylesheet" href="../../public/css/global.css">
    <style>
        .accounts-page .accounts-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .accounts-page .account-stat {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid #d7e5dd;
            border-radius: 16px;
            padding: 14px 16px;
            box-shadow: 0 10px 24px rgba(13, 56, 34, 0.08);
        }

        .accounts-page .account-stat .stat-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7f76;
            margin-bottom: 4px;
        }

        .accounts-page .account-stat .stat-value {
            font-size: 1.55rem;
            font-weight: 700;
            color: #116a3a;
        }

        .accounts-page .account-stat.total {
            border-left: 4px solid #2563eb;
        }

        .accounts-page .account-stat.active {
            border-left: 4px solid #116a3a;
        }

        .accounts-page .account-stat.inactive {
            border-left: 4px solid #d97706;
        }

        .accounts-page .account-stat.suspended {
            border-left: 4px solid #b91c1c;
        }

        .accounts-page .account-note {
            margin-top: 12px;
            font-size: 0.82rem;
            color: #5b746c;
        }

        .accounts-page .account-avatar-badge,
        .accounts-page .account-status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            font-weight: 700;
            letter-spacing: 0.01em;
            white-space: nowrap;
        }

        .accounts-page .account-avatar-badge {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #116a3a, #1e8a5c);
            color: #fff;
            box-shadow: 0 8px 16px rgba(17, 106, 58, 0.18);
            font-size: 0.82rem;
        }

        .accounts-page .account-status-badge {
            min-width: 92px;
            padding: 6px 10px;
            font-size: 0.76rem;
            text-transform: capitalize;
            border: 1px solid transparent;
        }

        .accounts-page .account-status-badge.active {
            background: #e5f8ec;
            color: #146b40;
            border-color: #9bd3b1;
        }

        .accounts-page .account-status-badge.inactive {
            background: #fef3c7;
            color: #92400e;
            border-color: #f5d08b;
        }

        .accounts-page .account-status-badge.suspended {
            background: #fee2e2;
            color: #991b1b;
            border-color: #f5b5b5;
        }

        .accounts-page .account-role-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: #eef6ef;
            color: #27523d;
            border: 1px solid #d7e5dd;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: capitalize;
        }

        .accounts-page .accounts-table {
            width: max(100%, 1680px);
            min-width: 1680px;
        }

        .accounts-page .table-empty-state {
            padding: 16px;
            border: 1px dashed #d7e5dd;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.84);
            color: #5b746c;
            text-align: center;
            margin-top: 12px;
        }

        .accounts-page .controls-right {
            align-items: center;
        }

        .accounts-page .account-disclaimer {
            font-size: 0.8rem;
            color: #5b746c;
            max-width: 320px;
            text-align: right;
        }

        .accounts-page .accounts-table tbody tr[data-account] {
            cursor: pointer;
            transition: background-color 0.18s ease, transform 0.18s ease;
        }

        .accounts-page .accounts-table tbody tr[data-account]:hover {
            background: #f4fbf6;
        }

        .accounts-page .accounts-table tbody tr[data-account]:active {
            transform: scale(0.999);
        }

        .accounts-page .account-form-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 10px 12px;
        }

        .accounts-page .account-col-4 { grid-column: span 4; }
        .accounts-page .account-col-6 { grid-column: span 6; }
        .accounts-page .account-col-8 { grid-column: span 8; }
        .accounts-page .account-col-12 { grid-column: 1 / -1; }

        .accounts-page .account-password-note,
        .accounts-page .reauth-note {
            font-size: 0.78rem;
            color: #5b746c;
            margin-top: 4px;
        }

        .accounts-page .account-modal-content {
            max-width: 1120px;
            max-height: 92vh;
            display: flex;
            flex-direction: column;
        }

        .accounts-page .reauth-modal-content {
            max-width: 520px;
            max-height: 92vh;
            display: flex;
            flex-direction: column;
        }

        .accounts-page .account-modal-body,
        .accounts-page .reauth-modal-body {
            overflow-y: auto;
        }

        .accounts-page .account-detail-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 8px 12px;
            background: #eef6ef;
            color: #27523d;
            border: 1px solid #d7e5dd;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .accounts-page .account-detail-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .accounts-page .account-detail-header h3 {
            margin: 0;
            font-size: 1.1rem;
        }

        .accounts-page .account-edit-divider {
            grid-column: 1 / -1;
            height: 1px;
            background: #d7e5dd;
            margin: 2px 0 6px;
        }

        @media (max-width: 1024px) {
            .accounts-page .accounts-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .accounts-page .account-disclaimer {
                text-align: left;
                max-width: none;
            }
        }

        @media (max-width: 768px) {
            .accounts-page .accounts-summary {
                grid-template-columns: 1fr;
            }

            .accounts-page .account-col-4,
            .accounts-page .account-col-6,
            .accounts-page .account-col-8 {
                grid-column: 1 / -1;
            }
        }
    </style>
</head>
<body class="clients-page accounts-page">

<?php include '../includes/sidebar.php'; ?>

<div class="main">
    <header class="topbar">
        <div class="topbar-left">
            <h1><?php echo htmlspecialchars($pageHeading); ?></h1>
            <div class="breadcrumb-trail">
                <i class="bi bi-house" style="font-size:.65rem;"></i>
                Dashboard &rsaquo; <span><?php echo htmlspecialchars($pageHeading); ?></span>
            </div>
        </div>
    </header>

    <main class="content">
        <div class="accounts-summary">
            <div class="account-stat total">
                <div class="stat-label">Total Accounts</div>
                <div class="stat-value"><?php echo number_format($accountCounts['total']); ?></div>
            </div>
            <div class="account-stat active">
                <div class="stat-label">Active</div>
                <div class="stat-value"><?php echo number_format($accountCounts['active']); ?></div>
            </div>
            <div class="account-stat inactive">
                <div class="stat-label">Inactive</div>
                <div class="stat-value"><?php echo number_format($accountCounts['inactive']); ?></div>
            </div>
            <div class="account-stat suspended">
                <div class="stat-label">Suspended</div>
                <div class="stat-value"><?php echo number_format($accountCounts['suspended']); ?></div>
            </div>
        </div>

        <div class="table-controls">
            <div class="controls-left">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" placeholder="Search accounts..." class="search-input">
                </div>
                <div class="filter-group">
                    <select id="filterStatus" class="filter-select">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
            </div>
            <div class="controls-right">
                <div class="account-disclaimer">Click an account row to view and edit details. Passwords are protected.</div>
            </div>
        </div>

        <?php if ($accountsError !== ''): ?>
            <div class="table-empty-state" style="border-color:#f5b5b5;color:#991b1b;background:#fff5f5;">
                <?php echo htmlspecialchars($accountsError); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="table-wrapper">
                <table class="clients-table accounts-table">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Branch</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                        </tr>
                    </thead>
                    <tbody id="accountsTableBody">
                        <?php if (empty($accounts)): ?>
                            <tr>
                                <td colspan="11" style="text-align:center; padding:20px;">No registered accounts found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($accounts as $account): ?>
                                <?php
                                    $status = normalizeAccountStatus($account['status'] ?? 'active');
                                    $accountPayload = [
                                        'user_id' => intval($account['user_id'] ?? 0),
                                        'full_name' => $account['full_name'] ?? '',
                                        'email' => $account['email'] ?? '',
                                        'department' => $account['department'] ?? '',
                                        'branch' => $account['branch'] ?? '',
                                        'role' => $account['role'] ?? '',
                                        'status' => $status,
                                        'avatar_initials' => $account['avatar_initials'] ?? '',
                                        'last_login' => $account['last_login'] ?? '',
                                        'created_at' => $account['created_at'] ?? '',
                                        'updated_at' => $account['updated_at'] ?? ''
                                    ];
                                    $searchBlob = strtolower(trim(implode(' ', array_map('strval', array_values($accountPayload)))));
                                    $avatarInitials = formatAvatarInitials($account['avatar_initials'] ?? null, $account['full_name'] ?? null);
                                    $roleLabel = formatRoleLabel($account['role'] ?? null);
                                    $departmentLabel = formatDepartmentLabel($account['department'] ?? null);
                                    $accountDataAttr = htmlspecialchars(json_encode($accountPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                                ?>
                                <tr data-account="<?php echo $accountDataAttr; ?>" data-status="<?php echo htmlspecialchars($status); ?>" data-search="<?php echo htmlspecialchars($searchBlob); ?>">
                                    <td><span class="account-avatar-badge"><?php echo htmlspecialchars($avatarInitials); ?></span></td>
                                    <td><?php echo htmlspecialchars(accountValue($account['user_id'] ?? null)); ?></td>
                                    <td><?php echo htmlspecialchars(accountValue($account['full_name'] ?? null)); ?></td>
                                    <td><?php echo htmlspecialchars(accountValue($account['email'] ?? null)); ?></td>
                                    <td><?php echo htmlspecialchars($departmentLabel); ?></td>
                                    <td><?php echo htmlspecialchars(accountValue($account['branch'] ?? null)); ?></td>
                                    <td><span class="account-role-badge"><?php echo htmlspecialchars($roleLabel); ?></span></td>
                                    <td><span class="account-status-badge <?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span></td>
                                    <td><?php echo htmlspecialchars(formatAccountDateTime($account['last_login'] ?? null)); ?></td>
                                    <td><?php echo htmlspecialchars(formatAccountDateTime($account['created_at'] ?? null)); ?></td>
                                    <td><?php echo htmlspecialchars(formatAccountDateTime($account['updated_at'] ?? null)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <div class="pagination-info">
                    Showing <span id="visibleAccountCount"><?php echo number_format(count($accounts)); ?></span> of <span id="totalAccountCount"><?php echo number_format(count($accounts)); ?></span> accounts
                </div>
                <div class="pagination" style="justify-content:flex-end;">
                    <span class="table-note">Passwords are excluded for safety.</span>
                </div>
            </div>
        </div>

        <div id="emptyState" class="table-empty-state" hidden>No accounts match the current filters.</div>

        <div id="reauthModal" class="modal" aria-hidden="true">
            <div class="modal-content reauth-modal-content" role="dialog" aria-modal="true" aria-labelledby="reauthModalTitle">
                <div class="modal-header">
                    <h2 id="reauthModalTitle">Confirm Head Office Password</h2>
                    <button type="button" class="modal-close" id="reauthModalCloseBtn" title="Close"><i class="bi bi-x"></i></button>
                </div>
                <div class="modal-body reauth-modal-body">
                    <p class="reauth-note" id="reauthModalMessage">Re-enter your head office password before opening account details.</p>
                    <form id="reauthForm">
                        <input type="hidden" id="reauthTargetAccountId">
                        <div class="form-row">
                            <div class="form-group full">
                                <label class="form-label" for="reauthPassword">Your Password</label>
                                <input type="password" id="reauthPassword" class="form-control" autocomplete="current-password" placeholder="Enter your current password" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="reauthCancelBtn">Cancel</button>
                    <button type="button" class="btn-save" id="reauthSubmitBtn">Open Account</button>
                </div>
            </div>
        </div>

        <div id="accountModal" class="modal" aria-hidden="true">
            <div class="modal-content account-modal-content" role="dialog" aria-modal="true" aria-labelledby="accountModalTitle">
                <div class="modal-header">
                    <div>
                        <h2 id="accountModalTitle">Edit Account</h2>
                        <div class="account-detail-pill" id="accountModalSubtitle">Account details and password reset</div>
                    </div>
                    <button type="button" class="modal-close" id="accountModalCloseBtn" title="Close"><i class="bi bi-x"></i></button>
                </div>
                <div class="modal-body account-modal-body">
                    <form id="accountForm">
                        <input type="hidden" id="editAccountId" name="user_id">
                        <input type="hidden" id="editReauthToken" name="reauth_token">
                        <div class="account-form-grid">
                            <div class="form-group account-col-4">
                                <label class="form-label">User ID</label>
                                <input type="text" id="editAccountUserId" class="form-control" readonly>
                            </div>
                            <div class="form-group account-col-4">
                                <label class="form-label">Avatar Initials</label>
                                <input type="text" id="editAccountAvatarInitials" class="form-control" readonly>
                            </div>
                            <div class="form-group account-col-4">
                                <label class="form-label">Status</label>
                                <select id="editAccountStatus" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>

                            <div class="form-group account-col-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" id="editAccountFullName" class="form-control" maxlength="100" required>
                            </div>
                            <div class="form-group account-col-6">
                                <label class="form-label">Email</label>
                                <input type="email" id="editAccountEmail" class="form-control" maxlength="120" required>
                            </div>

                            <div class="form-group account-col-6">
                                <label class="form-label">Department</label>
                                <input type="text" id="editAccountDepartment" class="form-control" maxlength="50" required>
                            </div>
                            <div class="form-group account-col-6">
                                <label class="form-label">Branch</label>
                                <select id="editAccountBranch" class="form-select" required>
                                    <option value="">Select branch</option>
                                    <?php foreach ($allowedBranches as $branchOption): ?>
                                        <option value="<?php echo htmlspecialchars($branchOption); ?>"><?php echo htmlspecialchars($branchOption); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group account-col-6">
                                <label class="form-label">Role</label>
                                <select id="editAccountRole" class="form-select" required>
                                    <?php foreach ($allowedRoles as $roleValue => $roleLabel): ?>
                                        <option value="<?php echo htmlspecialchars($roleValue); ?>"><?php echo htmlspecialchars($roleLabel); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group account-col-6">
                                <label class="form-label">Password</label>
                                <input type="password" id="editAccountPassword" class="form-control" autocomplete="new-password" placeholder="Leave blank to keep current password">
                                <div class="account-password-note">Current passwords are protected. Enter a new password only if you want to change it.</div>
                            </div>

                            <div class="form-group account-col-6">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" id="editAccountPasswordConfirm" class="form-control" autocomplete="new-password" placeholder="Repeat new password">
                            </div>
                            <div class="form-group account-col-6">
                                <label class="form-label">Last Login</label>
                                <input type="text" id="editAccountLastLogin" class="form-control" readonly>
                            </div>

                            <div class="account-edit-divider"></div>

                            <div class="form-group account-col-6">
                                <label class="form-label">Created At</label>
                                <input type="text" id="editAccountCreatedAt" class="form-control" readonly>
                            </div>
                            <div class="form-group account-col-6">
                                <label class="form-label">Updated At</label>
                                <input type="text" id="editAccountUpdatedAt" class="form-control" readonly>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="accountCancelBtn">Cancel</button>
                    <button type="button" class="btn-save" id="accountSaveBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('filterStatus');
    const tableBody = document.getElementById('accountsTableBody');
    const visibleAccountCount = document.getElementById('visibleAccountCount');
    const emptyState = document.getElementById('emptyState');

    function applyAccountFilters() {
        if (!tableBody) {
            return;
        }

        const searchValue = (searchInput ? searchInput.value : '').trim().toLowerCase();
        const statusValue = statusFilter ? statusFilter.value : '';
        const rows = Array.from(tableBody.querySelectorAll('tr[data-search]'));
        let visibleCount = 0;

        rows.forEach(row => {
            const rowStatus = String(row.dataset.status || '').toLowerCase();
            const rowSearch = String(row.dataset.search || '').toLowerCase();
            const matchesSearch = searchValue === '' || rowSearch.includes(searchValue);
            const matchesStatus = statusValue === '' || rowStatus === statusValue;
            const isVisible = matchesSearch && matchesStatus;

            row.style.display = isVisible ? '' : 'none';

            if (isVisible && row.cells && row.cells.length > 1) {
                visibleCount += 1;
            }
        });

        if (visibleAccountCount) {
            visibleAccountCount.textContent = String(visibleCount);
        }

        if (emptyState) {
            emptyState.hidden = rows.length === 0 || visibleCount !== 0;
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyAccountFilters);
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', applyAccountFilters);
    }

    document.addEventListener('DOMContentLoaded', applyAccountFilters);

    const reauthModal = document.getElementById('reauthModal');
    const reauthModalCloseBtn = document.getElementById('reauthModalCloseBtn');
    const reauthCancelBtn = document.getElementById('reauthCancelBtn');
    const reauthSubmitBtn = document.getElementById('reauthSubmitBtn');
    const reauthForm = document.getElementById('reauthForm');
    const reauthTargetAccountId = document.getElementById('reauthTargetAccountId');
    const reauthPassword = document.getElementById('reauthPassword');
    const reauthModalMessage = document.getElementById('reauthModalMessage');

    const accountModal = document.getElementById('accountModal');
    const accountModalTitle = document.getElementById('accountModalTitle');
    const accountModalSubtitle = document.getElementById('accountModalSubtitle');
    const accountModalCloseBtn = document.getElementById('accountModalCloseBtn');
    const accountCancelBtn = document.getElementById('accountCancelBtn');
    const accountSaveBtn = document.getElementById('accountSaveBtn');
    const accountForm = document.getElementById('accountForm');

    const editAccountId = document.getElementById('editAccountId');
    const editReauthToken = document.getElementById('editReauthToken');
    const editAccountUserId = document.getElementById('editAccountUserId');
    const editAccountAvatarInitials = document.getElementById('editAccountAvatarInitials');
    const editAccountStatus = document.getElementById('editAccountStatus');
    const editAccountFullName = document.getElementById('editAccountFullName');
    const editAccountEmail = document.getElementById('editAccountEmail');
    const editAccountDepartment = document.getElementById('editAccountDepartment');
    const editAccountBranch = document.getElementById('editAccountBranch');
    const editAccountRole = document.getElementById('editAccountRole');
    const editAccountPassword = document.getElementById('editAccountPassword');
    const editAccountPasswordConfirm = document.getElementById('editAccountPasswordConfirm');
    const editAccountLastLogin = document.getElementById('editAccountLastLogin');
    const editAccountCreatedAt = document.getElementById('editAccountCreatedAt');
    const editAccountUpdatedAt = document.getElementById('editAccountUpdatedAt');

    const DEFAULT_REAUTH_MESSAGE = reauthModalMessage ? reauthModalMessage.textContent : '';
    const DEFAULT_ACCOUNT_SUBTITLE = accountModalSubtitle ? accountModalSubtitle.textContent : '';

    let pendingAccount = null;
    let activeReauthToken = '';

    function setButtonBusy(button, isBusy, busyText = 'Working...') {
        if (!button) {
            return;
        }

        if (isBusy) {
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = `<i class="bi bi-hourglass-split"></i> ${busyText}`;
            button.disabled = true;
        } else {
            button.disabled = false;
            if (button.dataset.originalText) {
                button.innerHTML = button.dataset.originalText;
                delete button.dataset.originalText;
            }
        }
    }

    function setModalOpen(modal, isOpen) {
        if (!modal) {
            return;
        }

        modal.style.display = isOpen ? 'block' : 'none';
        modal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    }

    function getAccountFromRow(row) {
        try {
            return JSON.parse(row.dataset.account || '{}');
        } catch (error) {
            return null;
        }
    }

    function formatAccountDateTimeValue(value) {
        const trimmed = String(value || '').trim();
        if (trimmed === '') {
            return 'N/A';
        }

        const parsed = new Date(trimmed.replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) {
            return 'N/A';
        }

        return parsed.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function resetReauthModalState() {
        if (reauthModalMessage) {
            reauthModalMessage.textContent = DEFAULT_REAUTH_MESSAGE;
            reauthModalMessage.style.color = '';
        }

        if (reauthForm) {
            reauthForm.reset();
        }

        if (reauthTargetAccountId) {
            reauthTargetAccountId.value = '';
        }
    }

    function resetAccountModalState() {
        if (accountModalSubtitle) {
            accountModalSubtitle.textContent = DEFAULT_ACCOUNT_SUBTITLE;
            accountModalSubtitle.style.color = '';
        }

        if (accountForm) {
            accountForm.reset();
        }

        if (editReauthToken) {
            editReauthToken.value = '';
        }

        activeReauthToken = '';
        pendingAccount = null;
    }

    function invalidateReauthSession() {
        fetch('../handlers/accounts_management.php', {
            method: 'POST',
            credentials: 'include',
            body: new URLSearchParams({ action: 'clear_reauth' })
        }).catch(() => {});
    }

    function closeReauthModal(clearSession = true) {
        setModalOpen(reauthModal, false);
        resetReauthModalState();
        if (clearSession) {
            invalidateReauthSession();
            pendingAccount = null;
        }
    }

    function closeAccountModal() {
        setModalOpen(accountModal, false);
        resetAccountModalState();
        invalidateReauthSession();
    }

    function populateAccountModal(account) {
        if (!account) {
            return;
        }

        if (accountModalTitle) {
            accountModalTitle.textContent = `Edit ${String(account.full_name || 'Account')}`;
        }

        if (accountModalSubtitle) {
            accountModalSubtitle.textContent = `${String(account.email || 'N/A')} • ${String(account.branch || 'N/A')}`;
        }

        if (editAccountId) editAccountId.value = account.user_id || '';
        if (editAccountUserId) editAccountUserId.value = account.user_id || '';
        if (editAccountAvatarInitials) editAccountAvatarInitials.value = String(account.avatar_initials || '').toUpperCase() || 'U';
        if (editAccountStatus) editAccountStatus.value = String(account.status || 'active');
        if (editAccountFullName) editAccountFullName.value = account.full_name || '';
        if (editAccountEmail) editAccountEmail.value = account.email || '';
        if (editAccountDepartment) editAccountDepartment.value = account.department || '';
        if (editAccountBranch) editAccountBranch.value = account.branch || '';
        if (editAccountRole) editAccountRole.value = account.role || 'kyc_officer';
        if (editAccountPassword) editAccountPassword.value = '';
        if (editAccountPasswordConfirm) editAccountPasswordConfirm.value = '';
        if (editAccountLastLogin) editAccountLastLogin.value = formatAccountDateTimeValue(account.last_login || '');
        if (editAccountCreatedAt) editAccountCreatedAt.value = formatAccountDateTimeValue(account.created_at || '');
        if (editAccountUpdatedAt) editAccountUpdatedAt.value = formatAccountDateTimeValue(account.updated_at || '');
    }

    function openReauthModal(account) {
        if (!reauthModal || !account) {
            return;
        }

        pendingAccount = account;
        resetReauthModalState();

        if (reauthTargetAccountId) {
            reauthTargetAccountId.value = account.user_id || '';
        }

        if (reauthModalMessage) {
            reauthModalMessage.textContent = `Re-enter your head office password to open ${String(account.full_name || 'this account')}.`;
            reauthModalMessage.style.color = '';
        }

        setModalOpen(reauthModal, true);
        if (reauthPassword) {
            reauthPassword.focus();
        }
    }

    function openAccountModal(account, reauthToken) {
        if (!accountModal || !account) {
            return;
        }

        populateAccountModal(account);
        activeReauthToken = reauthToken || '';

        if (editReauthToken) {
            editReauthToken.value = activeReauthToken;
        }

        setModalOpen(accountModal, true);
    }

    function showAccountError(message) {
        if (!accountModalSubtitle) {
            return;
        }

        accountModalSubtitle.textContent = message;
        accountModalSubtitle.style.color = '#991b1b';
    }

    function showReauthError(message) {
        if (!reauthModalMessage) {
            return;
        }

        reauthModalMessage.textContent = message;
        reauthModalMessage.style.color = '#991b1b';
    }

    if (tableBody) {
        tableBody.addEventListener('click', function(event) {
            const row = event.target.closest('tr[data-account]');
            if (!row) {
                return;
            }

            const account = getAccountFromRow(row);
            if (!account || !account.user_id) {
                return;
            }

            openReauthModal(account);
        });
    }

    if (reauthModalCloseBtn) {
        reauthModalCloseBtn.addEventListener('click', closeReauthModal);
    }

    if (reauthCancelBtn) {
        reauthCancelBtn.addEventListener('click', closeReauthModal);
    }

    if (accountModalCloseBtn) {
        accountModalCloseBtn.addEventListener('click', closeAccountModal);
    }

    if (accountCancelBtn) {
        accountCancelBtn.addEventListener('click', closeAccountModal);
    }

    if (reauthForm) {
        reauthForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const currentPasswordValue = reauthPassword ? reauthPassword.value.trim() : '';
            if (currentPasswordValue === '') {
                showReauthError('Password is required.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'reauth_access');
            formData.append('current_password', currentPasswordValue);

            setButtonBusy(reauthSubmitBtn, true, 'Checking...');

            fetch('../handlers/accounts_management.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            })
                .then(async response => {
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Password verification failed.');
                    }

                    return result;
                })
                .then(result => {
                    if (!pendingAccount) {
                        throw new Error('No account selected.');
                    }

                    closeReauthModal(false);
                    openAccountModal(pendingAccount, result.reauth_token || '');
                })
                .catch(error => {
                    showReauthError(error.message || 'Password verification failed.');
                })
                .finally(() => {
                    setButtonBusy(reauthSubmitBtn, false, 'Open Account');
                });
        });
    }

    if (reauthSubmitBtn) {
        reauthSubmitBtn.addEventListener('click', function() {
            if (reauthForm) {
                reauthForm.requestSubmit ? reauthForm.requestSubmit() : reauthForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            }
        });
    }

    if (accountForm) {
        accountForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const accountId = editAccountId ? editAccountId.value.trim() : '';
            const fullName = editAccountFullName ? editAccountFullName.value.trim() : '';
            const email = editAccountEmail ? editAccountEmail.value.trim() : '';
            const department = editAccountDepartment ? editAccountDepartment.value.trim() : '';
            const branch = editAccountBranch ? editAccountBranch.value.trim() : '';
            const role = editAccountRole ? editAccountRole.value.trim() : '';
            const status = editAccountStatus ? editAccountStatus.value.trim() : '';
            const passwordValue = editAccountPassword ? editAccountPassword.value : '';
            const confirmPasswordValue = editAccountPasswordConfirm ? editAccountPasswordConfirm.value : '';
            const token = editReauthToken ? editReauthToken.value.trim() : '';

            if (!accountId) {
                showAccountError('No account selected.');
                return;
            }

            if ((passwordValue !== '' || confirmPasswordValue !== '') && passwordValue !== confirmPasswordValue) {
                showAccountError('Passwords do not match.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update_account');
            formData.append('user_id', accountId);
            formData.append('reauth_token', token);
            formData.append('full_name', fullName);
            formData.append('email', email);
            formData.append('department', department);
            formData.append('branch', branch);
            formData.append('role', role);
            formData.append('status', status);
            formData.append('new_password', passwordValue);
            formData.append('confirm_password', confirmPasswordValue);

            setButtonBusy(accountSaveBtn, true, 'Saving...');

            fetch('../handlers/accounts_management.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            })
                .then(async response => {
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Failed to update account.');
                    }

                    return result;
                })
                .then(() => {
                    closeAccountModal();
                    window.location.reload();
                })
                .catch(error => {
                    showAccountError(error.message || 'Failed to update account.');
                })
                .finally(() => {
                    setButtonBusy(accountSaveBtn, false, 'Save Changes');
                });
        });
    }

    if (accountSaveBtn) {
        accountSaveBtn.addEventListener('click', function() {
            if (accountForm) {
                accountForm.requestSubmit ? accountForm.requestSubmit() : accountForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            }
        });
    }

    window.addEventListener('click', function(event) {
        if (event.target === reauthModal) {
            closeReauthModal();
        }

        if (event.target === accountModal) {
            closeAccountModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (accountModal && accountModal.style.display === 'block') {
                closeAccountModal();
                return;
            }

            if (reauthModal && reauthModal.style.display === 'block') {
                closeReauthModal();
            }
        }
    });
</script>

</body>
</html>