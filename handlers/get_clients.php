<?php
/**
 * Fetch Clients Data Handler
 * Returns all clients from database
 */

header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

$response = ['success' => false, 'data' => [], 'debug' => []];

// Check user session
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

// Get pagination parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$pageSize = isset($_GET['pageSize']) ? intval($_GET['pageSize']) : 6;
$page = max(1, $page);  // Ensure page is at least 1

// Calculate offset
$offset = ($page - 1) * $pageSize;

// First, get total count
$countQuery = "SELECT COUNT(*) as total FROM clients";
$countStmt = $db->prepare($countQuery);
$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$totalClients = intval($countRow['total']);

// Get paginated clients with verified_by user name
$query = "
    SELECT 
        c.client_id, 
        c.reference_code, 
        c.client_number,
        c.first_name, 
        c.last_name, 
        c.client_type, 
        c.mobile_phone, 
        c.email, 
        c.verification_status,
        c.verified_by,
        c.created_at,
        u.full_name as verified_by_name
    FROM clients c
    LEFT JOIN users u ON c.verified_by = u.user_id
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
";

// Get paginated clients using prepared statement
$stmt = $db->prepare($query);

if (!$stmt) {
    $response['message'] = 'Database error: ' . $db->error;
    $response['debug']['prepare_error'] = $db->error;
    echo json_encode($response);
    exit;
}

$stmt->bind_param('ii', $pageSize, $offset);

if (!$stmt->execute()) {
    $response['message'] = 'Query execution failed: ' . $stmt->error;
    $response['debug']['execute_error'] = $stmt->error;
    echo json_encode($response);
    exit;
}

$result = $stmt->get_result();
$clients = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }
}

$totalPages = ceil($totalClients / $pageSize);

$response['success'] = true;
$response['data'] = $clients;
$response['count'] = count($clients);
$response['total'] = $totalClients;
$response['page'] = $page;
$response['pageSize'] = $pageSize;
$response['totalPages'] = $totalPages;
$response['debug']['row_count'] = $result ? $result->num_rows : 0;

echo json_encode($response);
?>
