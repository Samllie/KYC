<?php
/**
 * Registration Handler
 * API Endpoint for user registration
 */

header('Content-Type: application/json');
require_once '../config/db.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    $department = trim($_POST['department'] ?? 'KYC');
    $maxCredentialLength = 32;
    
    // Validation
    if (empty($fullname) || empty($email) || empty($password) || empty($confirmPassword)) {
        $response['message'] = 'All fields are required';
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

    if (strlen($email) > $maxCredentialLength || strlen($password) > $maxCredentialLength || strlen($confirmPassword) > $maxCredentialLength) {
        $response['message'] = 'Email and password must not exceed 32 characters';
        echo json_encode($response);
        exit;
    }
    
    if (strlen($password) < 8) {
        $response['message'] = 'Password must be at least 8 characters';
        echo json_encode($response);
        exit;
    }
    
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
    $avatarInitials = strtoupper(substr($fullname, 0, 1) . substr(strrchr($fullname, ' '), 1, 1));
    
    // Insert user
    $result = insert('users', [
        'full_name' => $fullname,
        'email' => $email,
        'password' => $passwordHash,
        'department' => $department,
        'role' => 'kyc_officer',
        'avatar_initials' => $avatarInitials,
        'status' => 'active'
    ]);
    
    if (!isset($result['success'])) {
        $response['message'] = 'Registration failed. Please try again.';
        echo json_encode($response);
        exit;
    }
    
    $response['success'] = true;
    $response['message'] = 'Registration successful. Please log in.';
    $response['redirect'] = 'login.php';
}

echo json_encode($response);
?>
