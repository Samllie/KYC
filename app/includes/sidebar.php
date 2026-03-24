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
            <div class="brand-text">
                <span>STerling Insurance Company</span>
                <strong>KYC System</strong>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Main Menu</div>

        <?php foreach ($menuItems as $item): ?>
            <a href="<?php echo htmlspecialchars($item['href']); ?>" class="nav-item <?php echo ($activePage === $item['page']) ? 'active' : ''; ?>">
                <i class="bi <?php echo htmlspecialchars($item['icon']); ?>"></i> 
                <?php echo htmlspecialchars($item['label']); ?>
                <?php if ($item['badge']): ?>
                    <span class="nav-badge"><?php echo htmlspecialchars($item['badge']); ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar"><?php echo htmlspecialchars($avatarInitials); ?></div>
            <div class="user-info">
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
    const menuBtn = document.getElementById('userMenuBtn');
    const dropdown = document.getElementById('userMenuDropdown');
    const logoutItem = document.getElementById('logoutMenuItem');
    const switchAccountItem = document.getElementById('switchAccountMenuItem');
    const modal = document.getElementById('logoutConfirmModal');
    const cancelBtn = document.getElementById('logoutCancelBtn');
    const confirmBtn = document.getElementById('logoutConfirmBtn');
    const confirmTitle = document.getElementById('logoutConfirmTitle');
    const accountConfirmMessage = document.getElementById('accountConfirmMessage');

    if (!menuBtn || !dropdown || !logoutItem || !switchAccountItem || !modal || !cancelBtn || !confirmBtn || !confirmTitle || !accountConfirmMessage) return;

    let pendingActionUrl = '../auth/logout.php';

    function setMenuOpen(isOpen) {
        dropdown.classList.toggle('open', isOpen);
        menuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        dropdown.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
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

    document.addEventListener('click', function (event) {
        if (!dropdown.contains(event.target) && event.target !== menuBtn && !menuBtn.contains(event.target)) {
            setMenuOpen(false);
        }
    });

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
