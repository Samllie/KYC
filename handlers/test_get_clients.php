<?php
/**
 * Diagnostic: Test Clients Data Handler
 * Debug version to check what's happening
 */

header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

$response = [
    'success' => false,
    'data' => [],
    'debug' => []
];

// Debug: Check session
$response['debug']['session_user'] = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NO SESSION';

// Get all clients (no session check for debugging)
$clients = fetchAll("
    SELECT 
        client_id, 
        reference_code, 
        client_number,
        first_name, 
        last_name, 
        client_type, 
        mobile_phone, 
        email, 
        verification_status,
        created_at
    FROM clients 
    ORDER BY created_at DESC
", []);

$response['debug']['fetchAll_result_type'] = gettype($clients);
$response['debug']['query_executed'] = true;

if (is_array($clients) && count($clients) > 0) {
    $response['success'] = true;
    $response['data'] = $clients;
    $response['count'] = count($clients);
} else {
    $response['success'] = false;
    $response['message'] = 'No clients found or query failed';
    $response['data'] = [];
    $response['count'] = 0;
}

echo json_encode($response);
?>
