<?php
/**
 * Modular Sidebar Component
 * 
 * Usage:
 * <?php $activePage = 'dashboard'; include 'includes/sidebar.php'; ?>
 * 
 * Parameters: $activePage (string) - the current page (dashboard, clients, kyc-verification, policy)
 */

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
        'label' => 'Clients',
        'icon' => 'bi-people',
        'href' => 'clients.php',
        'page' => 'clients',
        'badge' => '24'
    ],
    [
        'label' => 'KYC Verification',
        'icon' => 'bi-person-check',
        'href' => 'kyc-verification.php',
        'page' => 'kyc-verification',
        'badge' => null
    ],
    [
        'label' => 'Policy Issuance',
        'icon' => 'bi-file-earmark-text',
        'href' => 'policy.php',
        'page' => 'policy',
        'badge' => null
    ]
];

// Default active page if not set
$activePage = isset($activePage) ? $activePage : 'dashboard';

$displayName = $_SESSION['full_name'] ?? 'User';
$displayRole = $_SESSION['role'] ?? 'KYC Officer';
$avatarInitials = function_exists('getAvatarInitials') ? getAvatarInitials($displayName) : 'US';
?>

<!-- ═══════════════════════════════════════════════ SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <a href="#" class="brand-logo">
            <div class="brand-icon">
                <img src="../../public/images/SterlingLogo.png" alt="Sterling Insurance" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <div class="brand-text sidebar-text">

                <strong>KYC System</strong>
            </div>
        </a>
        <button type="button" id="sidebarToggleBtn" class="sidebar-toggle" aria-label="Toggle sidebar" title="Toggle sidebar">
            <i class="bi bi-arrow-left"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label sidebar-text">Main Menu</div>

        <?php foreach ($menuItems as $item): ?>
            <a href="<?php echo htmlspecialchars($item['href']); ?>" class="nav-item <?php echo ($activePage === $item['page']) ? 'active' : ''; ?>" title="<?php echo htmlspecialchars($item['label']); ?>">
                <i class="bi <?php echo htmlspecialchars($item['icon']); ?>"></i> 
                <span class="nav-text sidebar-text"><?php echo htmlspecialchars($item['label']); ?></span>
                <?php if ($item['badge']): ?>
                    <span class="nav-badge"><?php echo htmlspecialchars($item['badge']); ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar"><?php echo htmlspecialchars($avatarInitials); ?></div>
            <div class="user-info sidebar-text">
                <span><?php echo htmlspecialchars($displayName); ?></span>
                <span><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $displayRole))); ?></span>
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
    const COLLAPSE_KEY = 'kyc.sidebar.collapsed';
    const isMobile = function () {
        return window.matchMedia('(max-width: 768px)').matches;
    };

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
        sidebarToggleBtn.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        sidebarToggleBtn.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
    }

    function applyCollapsedState(collapsed) {
        if (isMobile()) {
            document.body.classList.remove('sidebar-collapsed');
            syncToggleA11y(false);
            return;
        }

        document.body.classList.toggle('sidebar-collapsed', collapsed);
        syncToggleA11y(collapsed);
    }

    function initSidebarState() {
        applyCollapsedState(readCollapsedState());
    }

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function () {
            const willCollapse = !document.body.classList.contains('sidebar-collapsed');
            applyCollapsedState(willCollapse);
            persistCollapsedState(willCollapse);
        });
    }

    window.addEventListener('resize', function () {
        applyCollapsedState(readCollapsedState());
    });

    initSidebarState();

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
