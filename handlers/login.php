<?php
/**
 * Login Handler
 * API Endpoint for user authentication
 */

header('Content-Type: application/json');
require_once '../config/db.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Validation
    if (empty($email) || empty($password)) {
        $response['message'] = 'Email and password are required';
        echo json_encode($response);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format';
        echo json_encode($response);
        exit;
    }
    
    // Query user
    $user = fetchOne("SELECT user_id, full_name, email, password, department, role FROM users WHERE email = ?", [$email]);
    
    if (!$user) {
        $response['message'] = 'Email or password is incorrect';
        echo json_encode($response);
        exit;
    }
    
    // Verify password
    $passwordHash = hash('sha256', $password);
    if ($user['password'] !== $passwordHash) {
        $response['message'] = 'Email or password is incorrect';
        echo json_encode($response);
        exit;
    }
    
    // Update last login
    update('users', ['last_login' => date('Y-m-d H:i:s')], 'user_id = ?', [$user['user_id']]);
    
    // Create session
    session_start();
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['department'] = $user['department'];
    $_SESSION['role'] = $user['role'];
    
    $response['success'] = true;
    $response['message'] = 'Login successful';
    $response['redirect'] = 'dashboard.php';
}

echo json_encode($response);
?>
