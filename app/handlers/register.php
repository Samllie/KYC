<?php
/**
 * Registration Handler
 * API Endpoint for user registration
 */

header('Content-Type: application/json');
require_once '../config/session.php';
require_once '../config/db.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

$currentUserRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
$currentUserDepartment = strtoupper(trim((string)($_SESSION['department'] ?? '')));
$currentUserBranch = strtoupper(trim((string)($_SESSION['branch'] ?? '')));
$isHeadOfficeUser = $currentUserRole === 'admin'
    || $currentUserDepartment === 'HEAD OFFICE'
    || in_array($currentUserBranch, ['HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH'], true);

if (!$isHeadOfficeUser) {
    http_response_code(403);
    $response['message'] = 'Access denied';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $branch = trim($_POST['branch'] ?? '');
    $accountClassification = strtolower(trim($_POST['account_classification'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    $maxCredentialLength = 32;
    $maxEmailLength = 120;
    $allowedEmailDomain = '@sterling-insurance.com.ph';
    $allowedClassifications = [
        'head_office',
        'branch_manager',
        'kyc_officer'
    ];
    $classificationRoleMap = [
        'head_office' => 'admin',
        'branch_manager' => 'manager',
        'kyc_officer' => 'kyc_officer'
    ];
    $classificationLevelMap = [
        'head_office' => 3,
        'branch_manager' => 2,
        'kyc_officer' => 1
    ];
    $classificationDepartmentMap = [
        'head_office' => 'HEAD OFFICE',
        'branch_manager' => 'BRANCH MANAGEMENT',
        'kyc_officer' => 'KYC'
    ];
    $allowedBranches = [
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
        'HEAD OFFICE',
        'HEAD OFFICE BRANCH',
        'SMRO BRANCH',
        'TACLOBAN BRANCH',
        'TUGUEGARAO BRANCH',
        'VIGAN BRANCH',
        'ILOILO BRANCH'
    ];
    
    // Validation
    if (empty($fullname) || empty($email) || empty($accountClassification) || empty($branch) || empty($password) || empty($confirmPassword)) {
        $response['message'] = 'All fields are required';
        echo json_encode($response);
        exit;
    }

    if (!in_array($accountClassification, $allowedClassifications, true)) {
        $response['message'] = 'Invalid account classification selected';
        echo json_encode($response);
        exit;
    }

    if (!in_array($branch, $allowedBranches, true)) {
        $response['message'] = 'Invalid branch selected';
        echo json_encode($response);
        exit;
    }
    
    if (strlen($fullname) < 3) {
        $response['message'] = 'Full name must be at least 3 characters';
        echo json_encode($response);
        exit;
    }

    if (strlen($fullname) > $maxCredentialLength) {
        $response['message'] = 'Full name must not exceed 32 characters';
        echo json_encode($response);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format';
        echo json_encode($response);
        exit;
    }

    $normalizedEmail = strtolower($email);
    if (substr($normalizedEmail, -strlen($allowedEmailDomain)) !== $allowedEmailDomain) {
        $response['message'] = 'Email must use the @sterling-insurance.com.ph domain';
        echo json_encode($response);
        exit;
    }

    if (strlen($email) > $maxEmailLength) {
        $response['message'] = 'Email must not exceed 120 characters';
        echo json_encode($response);
        exit;
    }

    if (strlen($password) > $maxCredentialLength || strlen($confirmPassword) > $maxCredentialLength) {
        $response['message'] = 'Password must not exceed 32 characters';
        echo json_encode($response);
        exit;
    }
    
    if (strlen($password) < 8) {
        $response['message'] = 'Password must be at least 8 characters';
        echo json_encode($response);
        exit;
    }

    $role = $classificationRoleMap[$accountClassification] ?? 'kyc_officer';
    $department = $classificationDepartmentMap[$accountClassification] ?? 'KYC';
    $accountLevel = $classificationLevelMap[$accountClassification] ?? 1;

    if ($password !== $confirmPassword) {
        $response['message'] = 'Passwords do not match';
        echo json_encode($response);
        exit;
    }
    
    // Check if email exists
    $existing = fetchOne("SELECT user_id FROM users WHERE email = ?", [$email]);
    if ($existing) {
        $response['message'] = 'Email already registered';
        echo json_encode($response);
        exit;
    }
    
    // Hash password
    $passwordHash = hash('sha256', $password);
    $avatarInitials = getAvatarInitials($fullname);
    
    // Insert user
    $result = insert('users', [
        'full_name' => $fullname,
        'email' => $email,
        'password' => $passwordHash,
        'department' => $department,
        'branch' => $branch,
        'role' => $role,
        'account_classification' => $accountClassification,
        'account_level' => $accountLevel,
        'avatar_initials' => $avatarInitials,
        'status' => 'active'
    ]);
    
    if (!isset($result['success'])) {
        $response['message'] = 'Registration failed. Please try again.';
        echo json_encode($response);
        exit;
    }
    
    $response['success'] = true;
    $response['message'] = 'Account added successfully.';
}

echo json_encode($response);
?>
