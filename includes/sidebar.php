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
?>

<!-- ═══════════════════════════════════════════════ SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <a href="#" class="brand-logo">
            <div class="brand-icon">
                <img src="../SterlingLogo.png" alt="Sterling Insurance" style="width: 100%; height: 100%; object-fit: contain;">
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
            <div class="user-avatar">JD</div>
            <div class="user-info">
                <span>Juan Dela Cruz</span>
                <span>KYC Officer</span>
            </div>
            <i class="bi bi-three-dots-vertical" style="color:rgba(255,255,255,.35);margin-left:auto;"></i>
        </div>
    </div>
</aside>
