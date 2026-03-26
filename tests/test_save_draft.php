<?php
/**
 * Test Script for Save Draft Functionality
 * This script tests the save_draft action by simulating a form submission
 */

// Simulate the save_draft POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'action' => 'save_draft',
    'refCode' => 'TEST-001',
    'clientType' => 'individual',
    'lastName' => 'Doe',
    'firstName' => 'John',
    'middleName' => 'M',
    'birthdate' => '1990-01-01',
    'gender' => 'Male',
    'occupation' => 'Engineer',
    'employer' => 'Tech Company',  // Test the field name mapping
    'mobile' => '09123456789',
    'telephone' => '(02) 1234567',  // Test the field name mapping
    'email' => 'john@example.com',
    'homeAddress' => '123 Main St, City, State'
];

// Start session for the test
session_start();
$_SESSION['user_id'] = 1;  // Simulate logged-in user

// Include the handler
require_once '../app/handlers/kyc.php';
?>
