<?php
/**
 * Session Management Utility
 * Provides session checking and user info retrieval
 */

session_start();

/**
 * Check if user is logged in
 * Redirect to login page if not
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Check if user has required role
 */
function requireRole($requiredRole) {
    requireLogin();
    if ($_SESSION['role'] !== $requiredRole && $_SESSION['role'] !== 'admin') {
        header('HTTP/1.0 403 Forbidden');
        die('Access Denied');
    }
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'department' => $_SESSION['department'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'avatar_initials' => getAvatarInitials($_SESSION['full_name'] ?? '')
    ];
}

/**
 * Generate avatar initials from full name
 */
function getAvatarInitials($fullName) {
    $parts = explode(' ', trim($fullName));
    $initials = '';
    foreach ($parts as $part) {
        $initials .= strtoupper($part[0]);
    }
    return substr($initials, 0, 2);
}

/**
 * Logout user
 */
function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if user is KYC officer
 */
function isKYCOfficer() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['kyc_officer', 'manager', 'admin']);
}

/**
 * Log user action for audit trail
 */
function logAction($action, $tableName, $recordId, $oldValue = null, $newValue = null) {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $query = "INSERT INTO audit_logs (user_id, action, table_name, record_id, old_value, new_value, ip_address, user_agent) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        (int)$_SESSION['user_id'],
        $action,
        $tableName,
        (int)$recordId,
        $oldValue,
        $newValue,
        $ipAddress,
        $userAgent
    ];
    
    return executeQuery($query, $params);
}

?>
