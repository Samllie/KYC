<?php
/**
 * Client Operations Handler
 * API Endpoints for client management
 */

header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

$response = ['success' => false, 'message' => ''];

// Check user session
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================
// ADD NEW CLIENT
// ============================================
if ($action === 'add_client' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientType = trim($_POST['clientType'] ?? '');
    
    // Validation
    if (empty($clientType)) {
        $response['message'] = 'Client type is required';
        echo json_encode($response);
        exit;
    }
    
    // Auto-generate unique reference code
    $refCode = generateUniqueReferenceCode();
    
    // Generate client number
    $clientNumber = 'CN-' . time();
    
    // Build insert data based on client type
    $insertData = [
        'reference_code' => $refCode,
        'client_number' => $clientNumber,
        'client_type' => $clientType,
        'verification_status' => 'pending',
        'submitted_by' => $_SESSION['user_id'],
        'submitted_at' => date('Y-m-d H:i:s')
    ];
    
    // Map all posted fields to database columns
    $fieldMap = [
        // Individual fields
        'firstName' => 'first_name',
        'lastName' => 'last_name',
        'middleName' => 'middle_name',
        'birthdate' => 'date_of_birth',
        'gender' => 'gender',
        'nationality' => 'nationality',
        'clientSince' => 'client_since',
        'apSlCode' => 'ap_sl_code',
        'arSlCode' => 'ar_sl_code',
        'occupation' => 'occupation',
        'company' => 'company_name',
        'businessAddress' => 'business_address',
        'businessCtm' => 'business_ctm',
        'businessProvince' => 'business_province',
        'homeAddress' => 'home_address',
        'homeCtm' => 'home_ctm',
        'homeProvince' => 'home_province',
        'officePhone' => 'office_phone',
        'homePhone' => 'home_phone',
        'mobile' => 'mobile_phone',
        'email' => 'email',
        'spouseName' => 'spouse_name',
        'spouseBirthdate' => 'spouse_birthdate',
        'spouseOccupation' => 'spouse_occupation',
        'mailingAddressType' => 'mailing_address_type',
        'lastNameFirst' => 'last_name_first',
        'commaSeparated' => 'comma_separated',
        'middleInitialOnly' => 'middle_initial_only',
        // Corporate fields
        'corporateClientName' => 'client_name',
        'businessType' => 'business_type',
        'corporateClientSince' => 'client_since',
        'region' => 'region',
        'tinNumber' => 'tin_number',
        'corporateApSlCode' => 'ap_sl_code',
        'corporateArSlCode' => 'ar_sl_code',
        'designation' => 'designation',
        'corporateBusinessAddress' => 'business_address',
        'corporateBusinessCtm' => 'business_ctm',
        'corporateBusinessProvince' => 'business_province',
        'corporatePhone' => 'office_phone',
        'corporateContactPerson' => 'contact_person',
        'corporateEmail' => 'email',
        'corporateGender' => 'gender',
        'clientClassification' => 'client_classification'
    ];
    
    // Process all fields
    foreach ($fieldMap as $postKey => $dbColumn) {
        if (isset($_POST[$postKey])) {
            $value = trim($_POST[$postKey]);
            if (!empty($value)) {
                // Convert checkboxes to boolean
                if (in_array($dbColumn, ['last_name_first', 'comma_separated', 'middle_initial_only'])) {
                    $insertData[$dbColumn] = 1;
                } else {
                    $insertData[$dbColumn] = $value;
                }
            }
        }
    }
    
    // Insert client
    $result = insert('clients', $insertData);
    
    if (!isset($result['success'])) {
        $response['message'] = 'Failed to add client';
        echo json_encode($response);
        exit;
    }
    
    $response['success'] = true;
    $response['message'] = 'Client added successfully';
    $response['client_id'] = $result['id'];
    $response['reference_code'] = $refCode;
}

// ============================================
// EDIT CLIENT
// ============================================
else if ($action === 'edit_client' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = intval($_POST['client_id'] ?? 0);
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $middleName = trim($_POST['middleName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $clientType = trim($_POST['clientType'] ?? '');
    
    if ($clientId === 0) {
        $response['message'] = 'Invalid client ID';
        echo json_encode($response);
        exit;
    }
    
    // Check if client exists
    $client = fetchOne("SELECT * FROM clients WHERE client_id = ?", [$clientId]);
    if (!$client) {
        $response['message'] = 'Client not found';
        echo json_encode($response);
        exit;
    }
    
    // Update client
    $result = update('clients', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'middle_name' => $middleName,
        'email' => $email,
        'mobile_phone' => $mobile,
        'occupation' => $occupation,
        'full_address' => $address,
        'client_type' => $clientType
    ], 'client_id = ?', [$clientId]);
    
    $response['success'] = true;
    $response['message'] = 'Client updated successfully';
}

// ============================================
// DELETE CLIENT
// ============================================
else if ($action === 'delete_client' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = intval($_POST['client_id'] ?? 0);
    
    if ($clientId === 0) {
        $response['message'] = 'Invalid client ID';
        echo json_encode($response);
        exit;
    }
    
    // Check if client exists
    $client = fetchOne("SELECT * FROM clients WHERE client_id = ?", [$clientId]);
    if (!$client) {
        $response['message'] = 'Client not found';
        echo json_encode($response);
        exit;
    }
    
    // Delete client (will cascade delete related records)
    $db->query("DELETE FROM clients WHERE client_id = $clientId");
    
    $response['success'] = true;
    $response['message'] = 'Client deleted successfully';
}

// ============================================
// GET CLIENT DETAILS
// ============================================
else if ($action === 'get_client' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $clientId = intval($_GET['client_id'] ?? 0);
    
    if ($clientId === 0) {
        $response['message'] = 'Invalid client ID';
        echo json_encode($response);
        exit;
    }
    
    $client = fetchOne("SELECT * FROM clients WHERE client_id = ?", [$clientId]);
    
    if (!$client) {
        $response['message'] = 'Client not found';
        echo json_encode($response);
        exit;
    }
    
    $response['success'] = true;
    $response['data'] = $client;
}

// ============================================
// GET ALL CLIENTS
// ============================================
else if ($action === 'get_all_clients' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $status = $_GET['status'] ?? '';
    $type = $_GET['type'] ?? '';
    
    $query = "SELECT * FROM clients WHERE 1=1";
    $params = [];
    
    if (!empty($status)) {
        $query .= " AND verification_status = ?";
        $params[] = $status;
    }
    
    if (!empty($type)) {
        $query .= " AND client_type = ?";
        $params[] = $type;
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $clients = fetchAll($query, $params);
    
    $response['success'] = true;
    $response['data'] = $clients;
    $response['count'] = count($clients);
}

// ============================================
// UPDATE CLIENT STATUS
// ============================================
else if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = intval($_POST['client_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    
    if ($clientId === 0 || empty($status)) {
        $response['message'] = 'Invalid client ID or status';
        echo json_encode($response);
        exit;
    }
    
    // Get current status for history
    $client = fetchOne("SELECT verification_status FROM clients WHERE client_id = ?", [$clientId]);
    if (!$client) {
        $response['message'] = 'Client not found';
        echo json_encode($response);
        exit;
    }
    
    // Update client status
    $updateData = [
        'verification_status' => $status,
        'verification_date' => date('Y-m-d H:i:s'),
        'verified_by' => $_SESSION['user_id']
    ];
    
    if ($status === 'rejected' && !empty($reason)) {
        $updateData['rejection_reason'] = $reason;
    }
    
    update('clients', $updateData, 'client_id = ?', [$clientId]);
    
    // Log to history
    insert('verification_history', [
        'client_id' => $clientId,
        'old_status' => $client['verification_status'],
        'new_status' => $status,
        'changed_by' => $_SESSION['user_id'],
        'change_reason' => $reason
    ]);
    
    $response['success'] = true;
    $response['message'] = 'Client status updated successfully';
}

else {
    $response['message'] = 'Invalid action';
}

echo json_encode($response);
?>
