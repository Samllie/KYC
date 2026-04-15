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
        // Redirect to auth folder login
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $scriptPath = dirname(dirname($_SERVER['PHP_SELF']));
        header('Location: ' . $protocol . '://' . $host . $scriptPath . '/auth/login.php');
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
        'branch' => $_SESSION['branch'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'avatar_initials' => getAvatarInitials($_SESSION['full_name'] ?? '')
    ];
}

/**
 * Generate avatar initials from full name
 */
function getAvatarInitials($fullName) {
    $cleanName = trim(preg_replace('/\s+/', ' ', (string)$fullName));

    if ($cleanName === '') {
        return 'U';
    }

    $parts = preg_split('/\s+/', $cleanName) ?: [];
    $firstPart = $parts[0] ?? '';
    $lastPart = $parts[count($parts) - 1] ?? '';

    $firstInitial = strtoupper(substr($firstPart, 0, 1));
    if ($firstInitial === '') {
        return 'U';
    }

    $lastInitial = strtoupper(substr($lastPart, 0, 1));
    if ($lastInitial === '' || count($parts) === 1) {
        return $firstInitial;
    }

    return $firstInitial . $lastInitial;
}

/**
 * Logout user
 */
function logout() {
    session_destroy();
    // Redirect to auth folder login
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptPath = dirname(dirname($_SERVER['PHP_SELF']));
    header('Location: ' . $protocol . '://' . $host . $scriptPath . '/auth/login.php');
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
