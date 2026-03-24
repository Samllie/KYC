<?php
/**
 * KYC Verification Handler
 * API Endpoints for KYC operations
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
// SUBMIT KYC VERIFICATION FORM
// ============================================
if ($action === 'submit_kyc' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect all form data
    $userProvidedRefCode = trim($_POST['refCode'] ?? '');
    $clientType = trim($_POST['clientType'] ?? '');
    
    $formData = [
        'ref_code' => $userProvidedRefCode,
        'client_type' => $clientType,
        'last_name' => trim($_POST['lastName'] ?? ''),
        'first_name' => trim($_POST['firstName'] ?? ''),
        'middle_name' => trim($_POST['middleName'] ?? ''),
        'suffix' => trim($_POST['suffixName'] ?? ''),
        'birthdate' => trim($_POST['birthdate'] ?? ''),
        'gender' => trim($_POST['gender'] ?? ''),
        'nationality' => trim($_POST['nationality'] ?? ''),
        'id_type' => trim($_POST['idType'] ?? ''),
        'id_number' => trim($_POST['idNumber'] ?? ''),
        'occupation' => trim($_POST['occupation'] ?? ''),
        'company' => trim($_POST['company'] ?? ''),
        'mobile' => trim($_POST['mobile'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'address' => trim($_POST['homeAddress'] ?? $_POST['address'] ?? '')
    ];
    
    // Validation of required fields (excluding ref_code since it will be auto-generated)
    $required = ['client_type', 'last_name', 'first_name', 'birthdate', 'occupation', 'email', 'mobile', 'address'];
    foreach ($required as $field) {
        if (empty($formData[$field])) {
            $response['message'] = 'All required fields must be filled';
            echo json_encode($response);
            exit;
        }
    }
    
    // If no reference code provided, generate a unique one
    if (empty($userProvidedRefCode)) {
        $formData['ref_code'] = generateUniqueReferenceCode();
    }
    
    // Check if client already exists using provided/generated reference code
    $existingClient = fetchOne("SELECT client_id FROM clients WHERE reference_code = ?", [$formData['ref_code']]);
    
    // Prepare client data for insertion/update based on type
    if ($clientType === 'individual') {
        $clientUpdateData = [
            'client_type' => $formData['client_type'],
            'first_name' => $formData['first_name'],
            'middle_name' => $formData['middle_name'],
            'last_name' => $formData['last_name'],
            'suffix' => $formData['suffix'],
            'date_of_birth' => $formData['birthdate'],
            'gender' => $formData['gender'],
            'nationality' => $formData['nationality'],
            'id_type' => $formData['id_type'],
            'id_number' => $formData['id_number'],
            'occupation' => $formData['occupation'],
            'company_name' => $formData['company'],
            'mobile_phone' => $formData['mobile'],
            'landline_phone' => $formData['phone'],
            'email' => $formData['email'],
            'home_address' => $formData['address'],
            'verification_status' => 'pending'
        ];
    } else {
        // Corporate client
        $clientUpdateData = [
            'client_type' => $formData['client_type'],
            'company_name' => $formData['corporate_client_name'],
            'business_type' => $formData['business_type'],
            'business_address' => $formData['corporate_business_address'],
            'office_phone' => $formData['corporate_phone'],
            'email' => $formData['corporate_email'],
            'contact_person' => $formData['corporate_contact_person'],
            'verification_status' => 'pending'
        ];
    }
    
    if ($existingClient) {
        $clientId = $existingClient['client_id'];
        update('clients', $clientUpdateData, 'client_id = ?', [$clientId]);
    } else {
        // Create new client
        $clientInsertData = array_merge([
            'reference_code' => $formData['ref_code'],
            'client_number' => 'CN-' . time(),
        ], $clientUpdateData);
        
        $result = insert('clients', $clientInsertData);
        $clientId = $result['id'] ?? 0;
    }
    
    // Create/Update KYC verification record
    $existingKyc = fetchOne("SELECT kyc_id FROM kyc_verifications WHERE client_id = ?", [$clientId]);
    
    if ($existingKyc) {
        update('kyc_verifications', array_merge($formData, [
            'status' => 'submitted',
            'submitted_at' => date('Y-m-d H:i:s'),
            'step_current' => 4,
            'step_1_completed' => true,
            'step_2_completed' => true,
            'step_3_completed' => true,
            'step_4_completed' => true
        ]), 'kyc_id = ?', [$existingKyc['kyc_id']]);
    } else {
        insert('kyc_verifications', array_merge($formData, [
            'client_id' => $clientId,
            'reference_code' => $formData['ref_code'],
            'status' => 'submitted',
            'submitted_at' => date('Y-m-d H:i:s'),
            'step_current' => 4,
            'step_1_completed' => true,
            'step_2_completed' => true,
            'step_3_completed' => true,
            'step_4_completed' => true
        ]));
    }
    
    $response['success'] = true;
    $response['message'] = 'KYC verification submitted successfully';
    $response['client_id'] = $clientId;
    $response['reference_code'] = $formData['ref_code'];
}

// ============================================
// SAVE DRAFT
// ============================================
else if ($action === 'save_draft' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userProvidedRefCode = trim($_POST['refCode'] ?? '');
    
    $formData = [
        'ref_code' => $userProvidedRefCode,
        'client_type' => trim($_POST['clientType'] ?? ''),
        'last_name' => trim($_POST['lastName'] ?? ''),
        'first_name' => trim($_POST['firstName'] ?? ''),
        'middle_name' => trim($_POST['middleName'] ?? ''),
        'suffix' => trim($_POST['suffixName'] ?? ''),
        'birthdate' => trim($_POST['birthdate'] ?? ''),
        'gender' => trim($_POST['gender'] ?? ''),
        'nationality' => trim($_POST['nationality'] ?? ''),
        'id_type' => trim($_POST['idType'] ?? ''),
        'id_number' => trim($_POST['idNumber'] ?? ''),
        'occupation' => trim($_POST['occupation'] ?? ''),
        'company' => trim($_POST['company'] ?? ''),
        'mobile' => trim($_POST['mobile'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'address' => trim($_POST['homeAddress'] ?? $_POST['address'] ?? '')
    ];
    
    // If no reference code provided, generate a unique one
    if (empty($userProvidedRefCode)) {
        $formData['ref_code'] = generateUniqueReferenceCode();
    }
    
    // Check if KYC record exists
    $existingKyc = fetchOne("SELECT kyc_id, client_id FROM kyc_verifications WHERE ref_code = ?", [$formData['ref_code']]);
    
    if ($existingKyc) {
        update('kyc_verifications', array_merge($formData, ['status' => 'draft']), 'kyc_id = ?', [$existingKyc['kyc_id']]);
    } else {
        insert('kyc_verifications', array_merge($formData, [
            'status' => 'draft',
            'step_current' => 1
        ]));
    }
    
    $response['success'] = true;
    $response['message'] = 'Draft saved successfully';
    $response['reference_code'] = $formData['ref_code'];
}

// ============================================
// GET KYC RECORD
// ============================================
else if ($action === 'get_kyc' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $refCode = trim($_GET['ref_code'] ?? '');
    
    if (empty($refCode)) {
        $response['message'] = 'Reference code is required';
        echo json_encode($response);
        exit;
    }
    
    $kyc = fetchOne("SELECT * FROM kyc_verifications WHERE ref_code = ?", [$refCode]);
    
    if (!$kyc) {
        $response['message'] = 'KYC record not found';
        echo json_encode($response);
        exit;
    }
    
    $response['success'] = true;
    $response['data'] = $kyc;
}

else {
    $response['message'] = 'Invalid action';
}

echo json_encode($response);
?>
