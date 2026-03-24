<?php
/**
 * Fetch Clients Data Handler
 * Returns all clients from database
 */

header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

$response = ['success' => false, 'data' => [], 'debug' => []];

function bindDynamicParams($stmt, $types, $params) {
    if ($types === '' || empty($params)) {
        return;
    }

    $bindParams = [];
    $bindParams[] = &$types;

    foreach ($params as $index => $value) {
        $bindParams[] = &$params[$index];
    }

    call_user_func_array([$stmt, 'bind_param'], $bindParams);
}

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
$pageSize = max(1, $pageSize);
$exportAll = isset($_GET['exportAll']) && $_GET['exportAll'] === '1';

// Get filters
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$type = trim($_GET['type'] ?? '');

$whereClauses = [];
$filterParams = [];
$filterTypes = '';

if ($search !== '') {
    $searchLike = '%' . $search . '%';
    $whereClauses[] = "(
        c.reference_code LIKE ? OR
        c.client_number LIKE ? OR
        c.client_name LIKE ? OR
        c.contact_person LIKE ? OR
        CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, '')) LIKE ? OR
        c.email LIKE ? OR
        c.mobile_phone LIKE ? OR
        c.office_phone LIKE ?
    )";

    for ($i = 0; $i < 8; $i++) {
        $filterParams[] = $searchLike;
        $filterTypes .= 's';
    }
}

if ($status !== '') {
    $whereClauses[] = "c.verification_status = ?";
    $filterParams[] = $status;
    $filterTypes .= 's';
}

if ($type !== '') {
    $whereClauses[] = "c.client_type = ?";
    $filterParams[] = $type;
    $filterTypes .= 's';
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Calculate offset
$offset = ($page - 1) * $pageSize;

// First, get filtered total count
$countQuery = "SELECT COUNT(*) as total FROM clients c $whereSql";
$countStmt = $db->prepare($countQuery);

if (!$countStmt) {
    $response['message'] = 'Database error: ' . $db->error;
    $response['debug']['prepare_error'] = $db->error;
    echo json_encode($response);
    exit;
}

bindDynamicParams($countStmt, $filterTypes, $filterParams);
$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$totalClients = intval($countRow['total']);

// Get paginated clients with submitted_by and verified_by user names
$query = "
    SELECT 
        c.client_id, 
        c.reference_code, 
        c.client_number,
        c.client_name,
        c.contact_person,
        c.first_name, 
        c.last_name, 
        c.client_type, 
        c.mobile_phone, 
        c.office_phone,
        c.email, 
        c.verification_status,
        c.submitted_by,
        c.verified_by,
        c.created_at,
        su.full_name as submitted_by_name,
        vu.full_name as verified_by_name
    FROM clients c
    LEFT JOIN users su ON c.submitted_by = su.user_id
    LEFT JOIN users vu ON c.verified_by = vu.user_id
    $whereSql
    ORDER BY c.created_at DESC
";

if (!$exportAll) {
    $query .= " LIMIT ? OFFSET ?";
}

// Get paginated clients using prepared statement
$stmt = $db->prepare($query);

if (!$stmt) {
    $response['message'] = 'Database error: ' . $db->error;
    $response['debug']['prepare_error'] = $db->error;
    echo json_encode($response);
    exit;
}

$queryParams = $filterParams;
$queryTypes = $filterTypes;

if (!$exportAll) {
    $queryParams[] = $pageSize;
    $queryParams[] = $offset;
    $queryTypes .= 'ii';
}

bindDynamicParams($stmt, $queryTypes, $queryParams);

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

$totalPages = $exportAll ? 1 : ceil($totalClients / $pageSize);

$response['success'] = true;
$response['data'] = $clients;
$response['count'] = count($clients);
$response['total'] = $totalClients;
$response['page'] = $page;
$response['pageSize'] = $pageSize;
$response['totalPages'] = $totalPages;
$response['debug']['row_count'] = $result ? $result->num_rows : 0;
$response['filters'] = [
    'search' => $search,
    'status' => $status,
    'type' => $type,
    'exportAll' => $exportAll
];

echo json_encode($response);
?>
