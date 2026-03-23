<?php
/**
 * Login Handler
 * API Endpoint for user authentication
 */

// Start session FIRST before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once '../config/db.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $rememberMe = isset($_POST['remember']) && $_POST['remember'] === 'on';
    
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
    
    // Check database connection first
    global $db;
    if (!isset($db) || $db->connect_error) {
        $response['message'] = 'Database connection error. Please check database configuration.';
        echo json_encode($response);
        exit;
    }
    
    // Query user
    $user = fetchOne("SELECT user_id, full_name, email, password, department, role FROM users WHERE email = ?", [$email]);
    
    if (!$user) {
        // User not found - could be wrong email or users table doesn't exist
        $response['message'] = 'Email or password is incorrect';
        echo json_encode($response);
        exit;
    }
    
    // Verify password
    $inputHash = hash('sha256', $password);
    $storedHash = strtolower($user['password']); // Case-insensitive comparison
    
    if (strtolower($inputHash) !== $storedHash) {
        $response['message'] = 'Email or password is incorrect';
        echo json_encode($response);
        exit;
    }
    
    // Update last login
    update('users', ['last_login' => date('Y-m-d H:i:s')], 'user_id = ?', [$user['user_id']]);
    
    // Create session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['department'] = $user['department'];
    $_SESSION['role'] = $user['role'];

    // Remember email for future logins (including after logout)
    if ($rememberMe) {
        setcookie('remembered_email', $user['email'], time() + (60 * 60 * 24 * 30), '/');
    } else {
        setcookie('remembered_email', '', time() - 3600, '/');
    }
    
    $response['success'] = true;
    $response['message'] = 'Login successful';
    $response['redirect'] = '../application/dashboard.php';
}

echo json_encode($response);
?>
