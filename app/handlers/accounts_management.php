<?php
/**
 * Accounts Management Handler
 * Head-office-only API for account re-authentication and updates.
 */

header('Content-Type: application/json');
ini_set('display_errors', '0');

require_once '../config/session.php';
require_once '../config/db.php';

$response = ['success' => false, 'message' => ''];

function accountsManagementIsHeadOfficeUser(): bool
{
    $currentUserRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
    $currentUserDepartment = strtoupper(trim((string)($_SESSION['department'] ?? '')));
    $currentUserBranch = strtoupper(trim((string)($_SESSION['branch'] ?? '')));

    return $currentUserRole === 'admin'
        || $currentUserDepartment === 'HEAD OFFICE'
        || in_array($currentUserBranch, ['HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH'], true);
}

function accountsManagementAllowedBranches(): array
{
    return [
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
}

function accountsManagementAllowedRoles(): array
{
    return ['admin', 'kyc_officer', 'manager', 'compliance'];
}

function accountsManagementAllowedStatuses(): array
{
    return ['active', 'inactive', 'suspended'];
}

function accountsManagementNormalizeRole(string $role): string
{
    $normalized = strtolower(trim($role));
    return $normalized === 'kyc officer' ? 'kyc_officer' : str_replace('-', '_', $normalized);
}

function accountsManagementNormalizeStatus(string $status): string
{
    $normalized = strtolower(trim($status));
    return in_array($normalized, accountsManagementAllowedStatuses(), true) ? $normalized : 'active';
}

function accountsManagementClearReauthSession(): void
{
    unset($_SESSION['accounts_mgmt_reauth_token'], $_SESSION['accounts_mgmt_reauth_at'], $_SESSION['accounts_mgmt_reauth_user_id']);
}

function accountsManagementHasValidReauthToken(string $token): bool
{
    $sessionToken = trim((string)($_SESSION['accounts_mgmt_reauth_token'] ?? ''));
    $sessionUserId = intval($_SESSION['accounts_mgmt_reauth_user_id'] ?? 0);
    $sessionTime = intval($_SESSION['accounts_mgmt_reauth_at'] ?? 0);

    if ($sessionToken === '' || $sessionUserId !== intval($_SESSION['user_id'] ?? 0)) {
        return false;
    }

    if ($sessionTime <= 0 || (time() - $sessionTime) > 300) {
        accountsManagementClearReauthSession();
        return false;
    }

    return hash_equals($sessionToken, trim($token));
}

function accountsManagementMakeAvatarInitials(string $fullName): string
{
    if (function_exists('getAvatarInitials')) {
        return getAvatarInitials($fullName);
    }

    $cleanName = trim(preg_replace('/\s+/', ' ', $fullName));
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

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

if (!accountsManagementIsHeadOfficeUser()) {
    http_response_code(403);
    $response['message'] = 'Access denied';
    echo json_encode($response);
    exit;
}

$action = strtolower(trim((string)($_POST['action'] ?? $_GET['action'] ?? '')));

if ($action === 'reauth_access' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = trim((string)($_POST['current_password'] ?? ''));
    if ($currentPassword === '') {
        $response['message'] = 'Password is required';
        echo json_encode($response);
        exit;
    }

    $currentUserId = intval($_SESSION['user_id'] ?? 0);
    $currentUser = fetchOne('SELECT user_id, password FROM users WHERE user_id = ?', [$currentUserId]);
    if (!$currentUser) {
        $response['message'] = 'Current account not found';
        echo json_encode($response);
        exit;
    }

    $inputHash = hash('sha256', $currentPassword);
    $storedHash = strtolower(trim((string)($currentUser['password'] ?? '')));
    if (strtolower($inputHash) !== $storedHash) {
        $response['message'] = 'Password is incorrect';
        echo json_encode($response);
        exit;
    }

    $token = bin2hex(random_bytes(16));
    $_SESSION['accounts_mgmt_reauth_token'] = $token;
    $_SESSION['accounts_mgmt_reauth_user_id'] = $currentUserId;
    $_SESSION['accounts_mgmt_reauth_at'] = time();

    $response['success'] = true;
    $response['message'] = 'Authorization successful';
    $response['reauth_token'] = $token;
    echo json_encode($response);
    exit;
}

if ($action === 'clear_reauth' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    accountsManagementClearReauthSession();
    $response['success'] = true;
    $response['message'] = 'Authorization cleared';
    echo json_encode($response);
    exit;
}

if ($action === 'update_account' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $reauthToken = trim((string)($_POST['reauth_token'] ?? ''));
    if (!accountsManagementHasValidReauthToken($reauthToken)) {
        http_response_code(403);
        $response['message'] = 'Re-authentication required';
        echo json_encode($response);
        exit;
    }

    $targetUserId = intval($_POST['user_id'] ?? 0);
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $department = trim((string)($_POST['department'] ?? ''));
    $branch = trim((string)($_POST['branch'] ?? ''));
    $role = accountsManagementNormalizeRole((string)($_POST['role'] ?? ''));
    $status = accountsManagementNormalizeStatus((string)($_POST['status'] ?? 'active'));
    $newPassword = trim((string)($_POST['new_password'] ?? ''));
    $confirmPassword = trim((string)($_POST['confirm_password'] ?? ''));

    if ($targetUserId <= 0) {
        $response['message'] = 'Invalid account selected';
        echo json_encode($response);
        exit;
    }

    if ($fullName === '' || strlen($fullName) < 3 || strlen($fullName) > 100) {
        $response['message'] = 'Full name must be between 3 and 100 characters';
        echo json_encode($response);
        exit;
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) {
        $response['message'] = 'Invalid email address';
        echo json_encode($response);
        exit;
    }

    if ($department === '' || strlen($department) > 50) {
        $response['message'] = 'Department is required';
        echo json_encode($response);
        exit;
    }

    if (!in_array($branch, accountsManagementAllowedBranches(), true)) {
        $response['message'] = 'Invalid branch selected';
        echo json_encode($response);
        exit;
    }

    if (!in_array($role, accountsManagementAllowedRoles(), true)) {
        $response['message'] = 'Invalid role selected';
        echo json_encode($response);
        exit;
    }

    if (!in_array($status, accountsManagementAllowedStatuses(), true)) {
        $response['message'] = 'Invalid status selected';
        echo json_encode($response);
        exit;
    }

    $existingAccount = fetchOne('SELECT user_id, email, password FROM users WHERE user_id = ? LIMIT 1', [$targetUserId]);
    if (!$existingAccount) {
        $response['message'] = 'Account not found';
        echo json_encode($response);
        exit;
    }

    $duplicateEmail = fetchOne('SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1', [$email, $targetUserId]);
    if ($duplicateEmail) {
        $response['message'] = 'Email is already used by another account';
        echo json_encode($response);
        exit;
    }

    if (($newPassword !== '' || $confirmPassword !== '') && $newPassword !== $confirmPassword) {
        $response['message'] = 'Passwords do not match';
        echo json_encode($response);
        exit;
    }

    if ($newPassword !== '' && (strlen($newPassword) < 8 || strlen($newPassword) > 32)) {
        $response['message'] = 'Password must be between 8 and 32 characters';
        echo json_encode($response);
        exit;
    }

    $updateData = [
        'full_name' => $fullName,
        'email' => $email,
        'department' => $department,
        'branch' => $branch,
        'role' => $role,
        'status' => $status,
        'avatar_initials' => accountsManagementMakeAvatarInitials($fullName),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if ($newPassword !== '') {
        $updateData['password'] = hash('sha256', $newPassword);
    }

    $result = update('users', $updateData, 'user_id = ?', [$targetUserId]);
    if (!($result['success'] ?? false)) {
        $response['message'] = $result['error'] ?? 'Failed to update account';
        echo json_encode($response);
        exit;
    }

    if ($targetUserId === intval($_SESSION['user_id'] ?? 0)) {
        $_SESSION['full_name'] = $fullName;
        $_SESSION['email'] = $email;
        $_SESSION['department'] = $department;
        $_SESSION['branch'] = $branch;
        $_SESSION['role'] = $role;
    }

    accountsManagementClearReauthSession();

    $response['success'] = true;
    $response['message'] = 'Account updated successfully';
    echo json_encode($response);
    exit;
}

$response['message'] = 'Invalid action';
echo json_encode($response);
?>