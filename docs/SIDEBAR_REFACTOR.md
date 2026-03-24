# Modular Sidebar Documentation

## Overview
The sidebar has been **refactored from hardcoded HTML** on each page into a **single reusable component** located at `includes/sidebar.php`.

## What Was Changed

### Files Updated
1. **dashboard.php** - Now includes modular sidebar with `$activePage = 'dashboard'`
2. **clients.php** - Now includes modular sidebar with `$activePage = 'clients'`
3. **kyc-verification.php** - Now includes modular sidebar with `$activePage = 'kyc-verification'`

### Removed
- ~35 lines of hardcoded sidebar HTML from each file
- Duplicate brand sections, navigation menus, and user cards

### Created
- **`includes/sidebar.php`** - Single source of truth for sidebar markup and logic

---

## How to Use

### For Existing Pages
All main pages (dashboard, clients, kyc-verification) automatically use the modular sidebar.

### For New Pages
To add the sidebar to a new page:

```php
<?php
require_once 'config/session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- your head content -->
</head>
<body>

<?php
$activePage = 'your-page-name';  // Use a page identifier
include 'includes/sidebar.php';
?>

<!-- Rest of your page content -->
```

The `$activePage` variable must match one of these values to highlight the active menu item:
- `'dashboard'`
- `'clients'`
- `'kyc-verification'`
- `'policy'`

---

## Structure of includes/sidebar.php

The modular sidebar includes:

1. **Menu Items Array** - Located at the top, easy to modify:
```php
$menuItems = [
    ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'href' => 'dashboard.php', 'page' => 'dashboard', 'badge' => null],
    ['label' => 'Clients', 'icon' => 'bi-people', 'href' => 'clients.php', 'page' => 'clients', 'badge' => '24'],
    // ...
];
```

2. **Dynamic Active State** - Automatically sets `active` class based on `$activePage`

3. **Dynamic Badge Display** - Shows badges only when defined

4. **Brand Logo Logic** - Shows logo image on dashboard, icon on other pages

5. **User Card** - Displays current logged-in user information

---

## How to Modify

### Add a New Menu Item
Edit `includes/sidebar.php` and add to the `$menuItems` array:

```php
[
    'label' => 'New Page',
    'icon' => 'bi-icon-name',  // Bootstrap icon class
    'href' => 'new-page.php',
    'page' => 'new-page',      // Must match $activePage in your new page
    'badge' => null            // Or a number like '5'
]
```

### Change Menu Labels, Icons, or Links
Simply edit the corresponding field in the `$menuItems` array in `includes/sidebar.php`.

### Update User Information
The sidebar displays hardcoded user info (Juan Dela Cruz). To make this dynamic, modify the sidebar-footer section:

```php
<div class="user-info">
    <span><?php echo htmlspecialchars($userName); ?></span>
    <span><?php echo htmlspecialchars($userRole); ?></span>
</div>
```

Then pass variables from your page PHP:
```php
<?php
$userName = 'John Doe';
$userRole = 'KYC Officer';
$activePage = 'dashboard';
include 'includes/sidebar.php';
?>
```

---

## Benefits of This Approach

✅ **Single Source of Truth** - Update sidebar once, affects all pages  
✅ **Reduced Duplication** - ~35 lines of code eliminated from each page  
✅ **Easier Maintenance** - Menu changes in one place  
✅ **Consistent UX** - Same sidebar on all pages  
✅ **Scalable** - Easy to add new pages or menu items  
✅ **Dynamic Active States** - Active menu item automatically highlights  

---

## Testing Checklist

- [x] Dashboard page loads with sidebar
- [x] Clients page loads with sidebar
- [x] KYC Verification page loads with sidebar  
- [x] Active menu item highlights correctly on each page
- [x] All links navigate properly
- [x] Badges display correctly (Clients: 24)
- [x] User card displays in footer
- [x] Logo displays on dashboard
- [x] Icon displays on other pages

---

## Future Enhancements

Consider these improvements:
1. Make user information (`userCard`) dynamic from session/database
2. Add analytics menu section conditionally
3. Make badge counts dynamic from database queries
4. Add role-based menu visibility (show different items for different user roles)
5. Add user dropdown menu on avatar/three-dots click
