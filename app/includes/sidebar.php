<?php
/**
 * Modular Sidebar Component
 * 
 * Usage:
 * <?php $activePage = 'dashboard'; include 'includes/sidebar.php'; ?>
 * 
 * Parameters: $activePage (string) - the current page (dashboard, clients, agents, my-applications, client-approvals, kyc-verification, policy)
 */

$currentUserRole = strtolower(trim($_SESSION['role'] ?? ''));
$currentUserDepartment = strtoupper(trim($_SESSION['department'] ?? ''));
$currentUserBranch = strtoupper(trim($_SESSION['branch'] ?? ''));
$normalizedRole = str_replace('-', '_', $currentUserRole);
$isHeadOfficeUser = $currentUserRole === 'admin'
    || $currentUserDepartment === 'HEAD OFFICE'
    || in_array($currentUserBranch, ['HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH'], true);
$isKycOfficerUser = $normalizedRole === 'kyc_officer' && !$isHeadOfficeUser;

$myApplicationsBadge = null;
$myApplicationsBadgeType = null;
if ($isKycOfficerUser) {
    $officerUserId = intval($_SESSION['user_id'] ?? 0);

    if ($officerUserId > 0) {
        if (!isset($db) || !($db instanceof mysqli)) {
            $dbConfigPath = __DIR__ . '/../config/db.php';
            if (is_file($dbConfigPath)) {
                require_once $dbConfigPath;
            }
        }

        if (isset($db) && $db instanceof mysqli) {
            $tableResult = $db->query("SHOW TABLES LIKE 'client_approvals'");
            $approvalsTableExists = $tableResult instanceof mysqli_result && $tableResult->num_rows > 0;

            if ($tableResult instanceof mysqli_result) {
                $tableResult->free();
            }

            if ($approvalsTableExists) {
                $stmt = $db->prepare(
                    "SELECT
                        SUM(CASE WHEN approval_status = 'resubmit' THEN 1 ELSE 0 END) AS resubmit_total,
                        SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) AS pending_total
                     FROM client_approvals
                     WHERE submitted_by = ?
                       AND approval_status IN ('pending', 'resubmit')"
                );

                if ($stmt) {
                    $stmt->bind_param('i', $officerUserId);

                    if ($stmt->execute()) {
                        $result = $stmt->get_result();
                        $row = $result ? $result->fetch_assoc() : null;
                        $resubmitCount = intval($row['resubmit_total'] ?? 0);
                        $pendingCount = intval($row['pending_total'] ?? 0);

                        if ($resubmitCount > 0) {
                            $myApplicationsBadge = (string)$resubmitCount;
                            $myApplicationsBadgeType = 'resubmit';
                        } elseif ($pendingCount > 0) {
                            $myApplicationsBadge = (string)$pendingCount;
                            $myApplicationsBadgeType = 'pending';
                        }
                    }

                    $stmt->close();
                }
            }
        }
    }
}

// Define sidebar menu items
$menuItems = [
    [
        'label' => 'Dashboard',
        'icon' => 'bi-grid-1x2',
        'href' => 'dashboard.php',
        'page' => 'dashboard',
        'badge' => null
    ],
    [
        'label' => 'KYC Verification',
        'icon' => 'bi-person-check',
        'href' => 'kyc-verification.php',
        'page' => 'kyc-verification',
        'badge' => null
    ],
    [
        'label' => 'Clients',
        'icon' => 'bi-people',
        'href' => 'clients.php',
        'page' => 'clients',
        'badge' => null
    ],
    [
        'label' => 'Agents',
        'icon' => 'bi-person-badge',
        'href' => 'clients.php?classification=agent',
        'page' => 'agents',
        'badge' => null
    ],
];

if ($isKycOfficerUser) {
    $menuItems[] = [
        'label' => 'My Applications',
        'icon' => 'bi-clipboard-data',
        'href' => 'my-applications.php',
        'page' => 'my-applications',
        'badge' => $myApplicationsBadge,
        'badge_type' => $myApplicationsBadgeType
    ];
}

if ($isHeadOfficeUser) {
    $menuItems[] = [
        'label' => 'Client Approvals',
        'icon' => 'bi-clipboard-check',
        'href' => 'client-approvals.php',
        'page' => 'client-approvals',
        'badge' => null
    ];

    $menuItems[] = [
        'label' => 'Accounts Management',
        'icon' => 'bi-person-gear',
        'href' => 'accounts-management.php',
        'page' => 'accounts-management',
        'badge' => null
    ];

    $menuItems[] = [
        'label' => 'Add Account',
        'icon' => 'bi-person-plus',
        'href' => '../auth/register.php',
        'page' => 'register',
        'badge' => null
    ];
}

// Default active page if not set
$activePage = isset($activePage) ? $activePage : 'dashboard';

$displayName = $_SESSION['full_name'] ?? 'User';
$displayRole = $_SESSION['role'] ?? 'kyc_officer';
$displayRoleNormalized = str_replace('-', '_', strtolower(trim((string)$displayRole)));

$accountTitle = 'User';
if ($isHeadOfficeUser) {
    $accountTitle = 'Head Office Reviewer';
} elseif ($displayRoleNormalized === 'kyc_officer') {
    $accountTitle = 'KYC Officer';
} elseif ($displayRoleNormalized === 'manager') {
    $accountTitle = 'Branch Manager';
} elseif ($displayRoleNormalized === 'compliance') {
    $accountTitle = 'Compliance Officer';
} elseif ($displayRoleNormalized !== '') {
    $accountTitle = ucwords(str_replace('_', ' ', $displayRoleNormalized));
}

$avatarInitials = function_exists('getAvatarInitials') ? getAvatarInitials($displayName) : 'US';
?>

<script>
(function () {
    try {
        if (window.matchMedia('(max-width: 768px)').matches) {
            return;
        }

        if (localStorage.getItem('kyc.sidebar.collapsed') === '1') {
            document.body.classList.add('sidebar-collapsed');
        }
    } catch (error) {
    }
})();
</script>

<!-- ═══════════════════════════════════════════════ SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <a href="dashboard.php" class="brand-logo" id="brandDashboardLink">
            <div class="brand-icon">
                <img src="../../public/images/SterlingLogo.png" alt="Sterling Insurance" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <div class="brand-text sidebar-text">

                <strong>KYC System</strong>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-toggle-row">
            <button type="button" id="sidebarToggleBtn" class="sidebar-toggle" aria-label="Hide sidebar" title="Hide sidebar">
                <i class="bi bi-list"></i>
            </button>
        </div>
        <div class="nav-label sidebar-text">Main Menu</div>

        <?php foreach ($menuItems as $item): ?>
            <a href="<?php echo htmlspecialchars($item['href']); ?>" class="nav-item <?php echo ($activePage === $item['page']) ? 'active' : ''; ?>" title="<?php echo htmlspecialchars($item['label']); ?>">
                <i class="bi <?php echo htmlspecialchars($item['icon']); ?>"></i> 
                <span class="nav-text sidebar-text"><?php echo htmlspecialchars($item['label']); ?></span>
                <?php if ($item['badge']): ?>
                    <?php
                        $badgeTypeRaw = strtolower(trim((string)($item['badge_type'] ?? '')));
                        $badgeClass = in_array($badgeTypeRaw, ['pending', 'resubmit'], true)
                            ? (' nav-badge-' . $badgeTypeRaw)
                            : '';
                    ?>
                    <span class="nav-badge<?php echo htmlspecialchars($badgeClass); ?>"><?php echo htmlspecialchars($item['badge']); ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar"><?php echo htmlspecialchars($avatarInitials); ?></div>
            <div class="user-info sidebar-text">
                <span><?php echo htmlspecialchars($displayName); ?></span>
                <span><?php echo htmlspecialchars($accountTitle); ?></span>
            </div>
            <button type="button" id="userMenuBtn" class="user-menu-btn" aria-expanded="false" aria-label="Open user menu">
                <i class="bi bi-three-dots-vertical"></i>
            </button>

            <div id="userMenuDropdown" class="user-menu-dropdown" role="menu" aria-hidden="true">
                <button type="button" id="logoutMenuItem" class="user-menu-item logout" role="menuitem">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            <button type="button" id="switchAccountMenuItem" class="user-menu-item switch-account" role="menuitem">
                <i class="bi bi-arrow-left-right"></i> Switch Account
            </button>
            </div>
        </div>
    </div>
</aside>

<button type="button" id="mobileSidebarToggle" class="mobile-sidebar-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="sidebar">
    <i class="bi bi-list"></i>
</button>

<div id="sidebarBackdrop" class="sidebar-backdrop" aria-hidden="true"></div>

<div id="logoutConfirmModal" class="logout-modal" aria-hidden="true">
    <div class="logout-modal-card" role="dialog" aria-modal="true" aria-labelledby="logoutConfirmTitle">
        <div class="logout-modal-header">
            <i class="bi bi-exclamation-triangle"></i>
            <h3 id="logoutConfirmTitle">Confirm Logout</h3>
        </div>
        <p id="accountConfirmMessage">Are you sure you want to log out?</p>
        <div class="logout-modal-actions">
            <button type="button" id="logoutCancelBtn" class="logout-btn cancel">Cancel</button>
            <button type="button" id="logoutConfirmBtn" class="logout-btn confirm">Logout</button>
        </div>
    </div>
</div>

<script>
(function () {
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const sidebarElement = document.getElementById('sidebar');
    const brandLogoLink = document.getElementById('brandDashboardLink');
    const sidebarNavLinks = document.querySelectorAll('.sidebar .nav-item');
    const COLLAPSE_KEY = 'kyc.sidebar.collapsed';
    const NAVIGATION_TRANSITION_MS = 240;
    let pendingSidebarNavigation = null;
    const isMobile = function () {
        return window.matchMedia('(max-width: 768px)').matches;
    };

    function isPrimaryNavigationClick(event) {
        return (typeof event.button === 'undefined' || event.button === 0)
            && !event.metaKey
            && !event.ctrlKey
            && !event.shiftKey
            && !event.altKey;
    }

    function clearPendingSidebarNavigation() {
        if (!pendingSidebarNavigation) {
            return;
        }

        if (pendingSidebarNavigation.timeoutId) {
            window.clearTimeout(pendingSidebarNavigation.timeoutId);
        }

        if (pendingSidebarNavigation.transitionTarget && pendingSidebarNavigation.transitionHandler) {
            pendingSidebarNavigation.transitionTarget.removeEventListener('transitionend', pendingSidebarNavigation.transitionHandler);
        }

        pendingSidebarNavigation = null;
    }

    function navigateAfterSidebarTransition(href, transitionTarget) {
        clearPendingSidebarNavigation();

        if (!transitionTarget) {
            window.location.href = href;
            return;
        }

        let finished = false;

        function finishNavigation() {
            if (finished) {
                return;
            }

            finished = true;
            clearPendingSidebarNavigation();
            window.location.href = href;
        }

        function handleTransitionEnd(event) {
            if (event.target !== transitionTarget) {
                return;
            }

            if (event.propertyName !== 'width' && event.propertyName !== 'transform') {
                return;
            }

            finishNavigation();
        }

        pendingSidebarNavigation = {
            transitionTarget: transitionTarget,
            transitionHandler: handleTransitionEnd,
            timeoutId: window.setTimeout(finishNavigation, NAVIGATION_TRANSITION_MS)
        };

        transitionTarget.addEventListener('transitionend', handleTransitionEnd);
    }

    function isDashboardPage() {
        const normalizedPath = (window.location.pathname || '').replace(/\\/g, '/').toLowerCase();
        return normalizedPath.endsWith('/dashboard.php');
    }

    function readCollapsedState() {
        try {
            return localStorage.getItem(COLLAPSE_KEY) === '1';
        } catch (error) {
            return false;
        }
    }

    function persistCollapsedState(collapsed) {
        try {
            localStorage.setItem(COLLAPSE_KEY, collapsed ? '1' : '0');
        } catch (error) {
            // Ignore storage failures so sidebar interactions keep working.
        }
    }

    function syncToggleA11y(collapsed) {
        if (!sidebarToggleBtn) {
            return;
        }

        sidebarToggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        sidebarToggleBtn.setAttribute('title', collapsed ? 'Show sidebar' : 'Hide sidebar');
        sidebarToggleBtn.setAttribute('aria-label', collapsed ? 'Show sidebar' : 'Hide sidebar');
    }

    function syncToggleIcon(collapsed) {
        if (!sidebarToggleBtn) {
            return;
        }

        const icon = sidebarToggleBtn.querySelector('i');
        if (!icon) {
            return;
        }

        icon.className = 'bi bi-list';
    }

    function applyCollapsedState(collapsed) {
        if (isMobile()) {
            document.body.classList.remove('sidebar-collapsed');
            syncToggleA11y(false);
            syncToggleIcon(false);
            return;
        }

        document.body.classList.toggle('sidebar-collapsed', collapsed);
        syncToggleA11y(collapsed);
        syncToggleIcon(collapsed);
    }

    function initSidebarState() {
        applyCollapsedState(readCollapsedState());
    }

    function handleSidebarNavLinkClick(event) {
        const link = event.currentTarget;

        if (!link || !isPrimaryNavigationClick(event) || (link.target && link.target !== '_self') || link.hasAttribute('download')) {
            return;
        }

        const isMobileSidebarOpen = isMobile() && document.body.classList.contains('sidebar-mobile-open');
        const shouldCollapseDesktopSidebar = !isMobile() && !document.body.classList.contains('sidebar-collapsed');

        if (!isMobileSidebarOpen && !shouldCollapseDesktopSidebar) {
            return;
        }

        event.preventDefault();

        if (isMobileSidebarOpen) {
            closeMobileSidebar();
        }

        if (shouldCollapseDesktopSidebar) {
            applyCollapsedState(true);
            persistCollapsedState(true);
        }

        navigateAfterSidebarTransition(link.href, sidebarElement);
    }

    function handleSidebarNavigationClick() {
        if (isMobile()) {
            closeMobileSidebar();
        }
    }

    function syncMobileToggleState(isOpen) {
        if (!mobileSidebarToggle) {
            return;
        }

        mobileSidebarToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        mobileSidebarToggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
        mobileSidebarToggle.title = isOpen ? 'Close menu' : 'Open menu';

        const icon = mobileSidebarToggle.querySelector('i');
        if (icon) {
            icon.className = isOpen ? 'bi bi-x-lg' : 'bi bi-list';
        }
    }

    function closeMobileSidebar() {
        document.body.classList.remove('sidebar-mobile-open');
        syncMobileToggleState(false);
    }

    function toggleMobileSidebar() {
        if (!isMobile()) {
            return;
        }

        const willOpen = !document.body.classList.contains('sidebar-mobile-open');
        document.body.classList.toggle('sidebar-mobile-open', willOpen);
        syncMobileToggleState(willOpen);
    }

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function () {
            const willCollapse = !document.body.classList.contains('sidebar-collapsed');
            applyCollapsedState(willCollapse);
            persistCollapsedState(willCollapse);
        });
    }

    if (mobileSidebarToggle) {
        mobileSidebarToggle.addEventListener('click', toggleMobileSidebar);
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', closeMobileSidebar);
    }

    if (brandLogoLink) {
        brandLogoLink.addEventListener('click', function (event) {
            handleSidebarNavigationClick();

            if (isDashboardPage()) {
                event.preventDefault();
                window.location.reload();
            }
        });
    }

    sidebarNavLinks.forEach(function (link) {
        link.addEventListener('click', handleSidebarNavLinkClick);
    });

    window.addEventListener('resize', function () {
        applyCollapsedState(readCollapsedState());
        if (!isMobile()) {
            closeMobileSidebar();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMobileSidebar();
        }
    });

    initSidebarState();
    syncMobileToggleState(false);

    const menuBtn = document.getElementById('userMenuBtn');
    const dropdown = document.getElementById('userMenuDropdown');
    const logoutItem = document.getElementById('logoutMenuItem');
    const switchAccountItem = document.getElementById('switchAccountMenuItem');
    const userCard = document.querySelector('.user-card');
    const userAvatar = document.querySelector('.user-avatar');
    const modal = document.getElementById('logoutConfirmModal');
    const cancelBtn = document.getElementById('logoutCancelBtn');
    const confirmBtn = document.getElementById('logoutConfirmBtn');
    const confirmTitle = document.getElementById('logoutConfirmTitle');
    const accountConfirmMessage = document.getElementById('accountConfirmMessage');

    if (!menuBtn || !dropdown || !logoutItem || !switchAccountItem || !modal || !cancelBtn || !confirmBtn || !confirmTitle || !accountConfirmMessage || !userCard || !userAvatar) {
        return;
    }

    let pendingActionUrl = '../auth/logout.php';

    function setMenuOpen(isOpen) {
        dropdown.classList.toggle('open', isOpen);
        menuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        dropdown.setAttribute('aria-hidden', isOpen ? 'false' : 'true');

        // When collapsed, render dropdown outside the sidebar using fixed positioning
        positionDropdown();
    }

    function resetDropdownPosition() {
        dropdown.style.position = '';
        dropdown.style.left = '';
        dropdown.style.top = '';
        dropdown.style.right = '';
        dropdown.style.bottom = '';
    }

    function positionDropdown() {
        if (!dropdown.classList.contains('open')) {
            resetDropdownPosition();
            return;
        }

        if (!document.body.classList.contains('sidebar-collapsed')) {
            resetDropdownPosition();
            return;
        }

        // Make it fixed relative to viewport so it doesn't affect sidebar scrolling
        dropdown.style.position = 'fixed';
        dropdown.style.right = 'auto';
        dropdown.style.bottom = 'auto';

        const gap = 12;
        const pad = 8;
        const cardRect = userCard.getBoundingClientRect();

        // After opening, dropdown has measurable size
        const dropRect = dropdown.getBoundingClientRect();

        let left = cardRect.right + gap;
        let top = cardRect.bottom - dropRect.height;

        const maxLeft = window.innerWidth - dropRect.width - pad;
        const maxTop = window.innerHeight - dropRect.height - pad;

        if (left > maxLeft) left = Math.max(pad, maxLeft);
        if (top < pad) top = pad;
        if (top > maxTop) top = Math.max(pad, maxTop);

        dropdown.style.left = `${left}px`;
        dropdown.style.top = `${top}px`;
    }

    function setModalOpen(isOpen) {
        modal.classList.toggle('open', isOpen);
        modal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    }

    menuBtn.addEventListener('click', function (event) {
        event.stopPropagation();
        const isOpen = dropdown.classList.contains('open');
        setMenuOpen(!isOpen);
    });

    function toggleMenuFromCollapsed(event) {
        // In collapsed sidebar, the "..." button is hidden; make avatar/card open the menu.
        if (!document.body.classList.contains('sidebar-collapsed')) {
            return;
        }

        event.stopPropagation();
        const isOpen = dropdown.classList.contains('open');
        setMenuOpen(!isOpen);
    }

    userAvatar.addEventListener('click', toggleMenuFromCollapsed);
    userCard.addEventListener('click', function (event) {
        // Avoid double-toggle when clicking the (visible) menu button.
        if (event.target === menuBtn || menuBtn.contains(event.target)) {
            return;
        }
        toggleMenuFromCollapsed(event);
    });

    document.addEventListener('click', function (event) {
        if (!dropdown.contains(event.target) && event.target !== menuBtn && !menuBtn.contains(event.target)) {
            setMenuOpen(false);
        }
    });

    window.addEventListener('resize', positionDropdown);
    window.addEventListener('scroll', positionDropdown, true);

    logoutItem.addEventListener('click', function () {
        setMenuOpen(false);
        pendingActionUrl = '../auth/logout.php';
        confirmTitle.textContent = 'Confirm Logout';
        accountConfirmMessage.textContent = 'Are you sure you want to log out?';
        confirmBtn.innerHTML = 'Logout';
        setModalOpen(true);
    });

    switchAccountItem.addEventListener('click', function () {
        setMenuOpen(false);
        pendingActionUrl = '../auth/switch_account.php';
        confirmTitle.textContent = 'Confirm Switch Account';
        accountConfirmMessage.textContent = 'Are you sure you want to switch accounts? You will need to sign in again.';
        confirmBtn.innerHTML = 'Switch';
        setModalOpen(true);
    });

    cancelBtn.addEventListener('click', function () {
        setModalOpen(false);
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            setModalOpen(false);
        }
    });

    confirmBtn.addEventListener('click', function () {
        window.location.href = pendingActionUrl;
    });
})();
</script>
