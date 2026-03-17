<?php
/**
 * Fetch Clients Data Handler
 * Returns all clients from database
 */

header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

$response = ['success' => false, 'data' => []];

// Check user session
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

// Get all clients
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

if (!$clients) {
    $clients = [];
}

$response['success'] = true;
$response['data'] = $clients;
$response['count'] = count($clients);

echo json_encode($response);
?>
