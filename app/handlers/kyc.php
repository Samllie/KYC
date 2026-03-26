<?php
/**
 * KYC Verification Handler
 * API Endpoints for KYC operations
 */

header('Content-Type: application/json');
require_once '../config/db.php';
require_once __DIR__ . '/upload_utils.php';
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
    
    // Map form field names to database field names (handling form field mismatches)
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
        'company' => trim($_POST['employer'] ?? $_POST['company'] ?? ''),
        'mobile' => trim($_POST['mobile'] ?? ''),
        'phone' => trim($_POST['telephone'] ?? $_POST['phone'] ?? ''),
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
    $existingClient = fetchOne("SELECT client_id, submitted_by FROM clients WHERE reference_code = ?", [$formData['ref_code']]);
    
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
            'salutation' => trim($_POST['salutation'] ?? ''),
            'client_since' => trim($_POST['clientSince'] ?? ''),
            'ap_sl_code' => trim($_POST['apSlCode'] ?? ''),
            'ar_sl_code' => trim($_POST['arSlCode'] ?? $_POST['apSlCode2'] ?? ''),
            'occupation' => $formData['occupation'],
            'company_name' => $formData['company'],
            'office_phone' => trim($_POST['officePhone'] ?? ''),
            'spouse_name' => trim($_POST['spouseName'] ?? ''),
            'spouse_birthdate' => trim($_POST['spouseBirthdate'] ?? ''),
            'spouse_occupation' => trim($_POST['spouseOccupation'] ?? ''),
            'mailing_address_type' => trim($_POST['mailingAddressType'] ?? ''),
            'business_address' => trim($_POST['businessAddress'] ?? ''),
            'business_ctm' => trim($_POST['businessCtm'] ?? ''),
            'business_province' => trim($_POST['businessProvince'] ?? ''),
            'mobile_phone' => $formData['mobile'],
            'home_phone' => $formData['phone'],
            'landline_phone' => $formData['phone'],
            'email' => $formData['email'],
            'home_address' => $formData['address'],
            'home_ctm' => trim($_POST['homeCtm'] ?? ''),
            'home_province' => trim($_POST['homeProvince'] ?? ''),
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

        // Keep original submitter if already set; otherwise record current account.
        if (empty($existingClient['submitted_by'])) {
            $clientUpdateData['submitted_by'] = intval($_SESSION['user_id']);
            $clientUpdateData['submitted_at'] = date('Y-m-d H:i:s');
        }

        update('clients', $clientUpdateData, 'client_id = ?', [$clientId]);
    } else {
        // Create new client
        $clientInsertData = array_merge([
            'reference_code' => $formData['ref_code'],
            'client_number' => 'CN-' . time(),
            'submitted_by' => intval($_SESSION['user_id']),
            'submitted_at' => date('Y-m-d H:i:s'),
        ], $clientUpdateData);
        
        $result = insert('clients', $clientInsertData);
        $clientId = $result['id'] ?? 0;
    }
    
    // Create/Update KYC verification record
    $existingKyc = fetchOne("SELECT kyc_id FROM kyc_verifications WHERE client_id = ?", [$clientId]);
    
    $kycId = 0;
    if ($existingKyc) {
        $kycId = intval($existingKyc['kyc_id']);
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
        $kycInsert = insert('kyc_verifications', array_merge($formData, [
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
        $kycId = intval($kycInsert['id'] ?? 0);
    }

    // Finalize any temp-uploaded files (from form page) and record documents
    $uploadedFilesRaw = $_POST['uploadedFiles'] ?? '[]';
    $uploadedFiles = [];
    if (is_string($uploadedFilesRaw) && $uploadedFilesRaw !== '') {
        $decoded = json_decode($uploadedFilesRaw, true);
        if (is_array($decoded)) $uploadedFiles = $decoded;
    }

    if (!empty($uploadedFiles) && $clientId && $kycId) {
        $finalize = kyc_finalize_temp_uploads($_SESSION['user_id'], $uploadedFiles, $clientId, $kycId);
        if (($finalize['success'] ?? false) && !empty($finalize['files'])) {
            foreach ($finalize['files'] as $doc) {
                $filePath = $doc['file_path'] ?? null;
                // Avoid duplicating rows when resuming drafts (attachments may already be finalized).
                if ($filePath) {
                    $already = fetchOne(
                        "SELECT document_id FROM documents WHERE kyc_id = ? AND file_path = ? LIMIT 1",
                        [$kycId, $filePath]
                    );
                    if ($already) continue;
                }
                // Best-effort insert (do not fail submission if documents table is missing)
                insert('documents', [
                    'kyc_id' => $kycId,
                    'client_id' => $clientId,
                    'file_name' => $doc['file_name'] ?? '',
                    'file_type' => $doc['file_type'] ?? null,
                    'file_size' => $doc['file_size'] ?? null,
                    'file_path' => $doc['file_path'] ?? null,
                    'document_type' => 'supporting',
                    'uploaded_by' => $_SESSION['user_id'],
                    'status' => 'pending'
                ]);
            }
        }
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
    
    // Map form field names to database field names (handling form field mismatches)
    $formData = [
        'ref_code' => $userProvidedRefCode,
        'client_type' => trim($_POST['clientType'] ?? ''),
        'last_name' => trim($_POST['lastName'] ?? ''),
        'first_name' => trim($_POST['firstName'] ?? ''),
        'middle_name' => trim($_POST['middleName'] ?? ''),
        'suffix' => trim($_POST['suffixName'] ?? ''),
        'birthdate' => trim($_POST['birthdate'] ?? ''),
        'gender' => trim($_POST['gender'] ?? $_POST['corporateGender'] ?? ''),
        'nationality' => trim($_POST['nationality'] ?? ''),
        'id_type' => trim($_POST['idType'] ?? ''),
        'id_number' => trim($_POST['idNumber'] ?? ''),
        'occupation' => trim($_POST['occupation'] ?? ''),
        'company' => trim($_POST['employer'] ?? $_POST['company'] ?? ''),
        'mobile' => trim($_POST['mobile'] ?? ''),
        'phone' => trim($_POST['telephone'] ?? $_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'address' => trim($_POST['homeAddress'] ?? $_POST['address'] ?? '')
    ];
    
    // If no reference code provided, generate a unique one
    if (empty($userProvidedRefCode)) {
        $formData['ref_code'] = generateUniqueReferenceCode();
    }

    // Ensure a client exists for this ref_code (kyc_verifications.client_id is NOT NULL)
    $clientId = 0;
    $existingClient = fetchOne("SELECT client_id FROM clients WHERE reference_code = ?", [$formData['ref_code']]);
    if ($existingClient) {
        $clientId = intval($existingClient['client_id']);

        update('clients', [
            'client_type' => $formData['client_type'] ?: 'individual',
            'first_name' => $formData['first_name'] ?: null,
            'middle_name' => $formData['middle_name'] ?: null,
            'last_name' => $formData['last_name'] ?: null,
            'suffix' => $formData['suffix'] ?: null,
            'salutation' => trim($_POST['salutation'] ?? '') ?: null,
            'date_of_birth' => $formData['birthdate'] ?: null,
            'gender' => $formData['gender'] ?: null,
            'nationality' => $formData['nationality'] ?: null,
            'client_since' => trim($_POST['clientSince'] ?? '') ?: null,
            'spouse_name' => trim($_POST['spouseName'] ?? '') ?: null,
            'spouse_birthdate' => trim($_POST['spouseBirthdate'] ?? '') ?: null,
            'spouse_occupation' => trim($_POST['spouseOccupation'] ?? '') ?: null,
            'id_type' => $formData['id_type'] ?: null,
            'id_number' => $formData['id_number'] ?: null,
            'occupation' => $formData['occupation'] ?: null,
            'company_name' => $formData['company'] ?: null,
            'ap_sl_code' => trim($_POST['apSlCode'] ?? '') ?: null,
            'ar_sl_code' => trim($_POST['arSlCode'] ?? $_POST['apSlCode2'] ?? '') ?: null,
            'mailing_address_type' => trim($_POST['mailingAddressType'] ?? '') ?: null,
            'business_address' => trim($_POST['businessAddress'] ?? '') ?: null,
            'business_ctm' => trim($_POST['businessCtm'] ?? '') ?: null,
            'business_province' => trim($_POST['businessProvince'] ?? '') ?: null,
            'home_address' => $formData['address'] ?: null,
            'home_ctm' => trim($_POST['homeCtm'] ?? '') ?: null,
            'home_province' => trim($_POST['homeProvince'] ?? '') ?: null,
            'office_phone' => trim($_POST['officePhone'] ?? '') ?: null,
            'home_phone' => $formData['phone'] ?: null,
            'mobile_phone' => $formData['mobile'] ?: null,
            'landline_phone' => $formData['phone'] ?: null,
            'email' => $formData['email'] ?: null,
            'verification_status' => 'draft'
        ], 'client_id = ?', [$clientId]);
    } else {
        $clientInsert = insert('clients', [
            'reference_code' => $formData['ref_code'],
            'client_number' => 'CN-' . time(),
            'client_type' => $formData['client_type'] ?: 'individual',
            'first_name' => $formData['first_name'] ?: null,
            'middle_name' => $formData['middle_name'] ?: null,
            'last_name' => $formData['last_name'] ?: null,
            'suffix' => $formData['suffix'] ?: null,
            'salutation' => trim($_POST['salutation'] ?? '') ?: null,
            'date_of_birth' => $formData['birthdate'] ?: null,
            'gender' => $formData['gender'] ?: null,
            'nationality' => $formData['nationality'] ?: null,
            'client_since' => trim($_POST['clientSince'] ?? '') ?: null,
            'spouse_name' => trim($_POST['spouseName'] ?? '') ?: null,
            'spouse_birthdate' => trim($_POST['spouseBirthdate'] ?? '') ?: null,
            'spouse_occupation' => trim($_POST['spouseOccupation'] ?? '') ?: null,
            'id_type' => $formData['id_type'] ?: null,
            'id_number' => $formData['id_number'] ?: null,
            'occupation' => $formData['occupation'] ?: null,
            'company_name' => $formData['company'] ?: null,
            'ap_sl_code' => trim($_POST['apSlCode'] ?? '') ?: null,
            'ar_sl_code' => trim($_POST['arSlCode'] ?? $_POST['apSlCode2'] ?? '') ?: null,
            'mailing_address_type' => trim($_POST['mailingAddressType'] ?? '') ?: null,
            'business_address' => trim($_POST['businessAddress'] ?? '') ?: null,
            'business_ctm' => trim($_POST['businessCtm'] ?? '') ?: null,
            'business_province' => trim($_POST['businessProvince'] ?? '') ?: null,
            'mobile_phone' => $formData['mobile'] ?: null,
            'office_phone' => trim($_POST['officePhone'] ?? '') ?: null,
            'home_phone' => $formData['phone'] ?: null,
            'landline_phone' => $formData['phone'] ?: null,
            'email' => $formData['email'] ?: null,
            'home_address' => $formData['address'] ?: null,
            'home_ctm' => trim($_POST['homeCtm'] ?? '') ?: null,
            'home_province' => trim($_POST['homeProvince'] ?? '') ?: null,
            'verification_status' => 'draft',
            'submitted_by' => $_SESSION['user_id'],
            'submitted_at' => date('Y-m-d H:i:s')
        ]);
        $clientId = intval($clientInsert['id'] ?? 0);
    }
    
    // Check if KYC record exists
    $existingKyc = fetchOne("SELECT kyc_id FROM kyc_verifications WHERE ref_code = ?", [$formData['ref_code']]);

    $kycId = 0;
    if ($existingKyc) {
        $kycId = intval($existingKyc['kyc_id']);
        update('kyc_verifications', array_merge($formData, ['status' => 'draft']), 'kyc_id = ?', [$existingKyc['kyc_id']]);
    } else {
        // Create draft record
        $kycInsert = insert('kyc_verifications', array_merge($formData, [
            'client_id' => $clientId,
            'reference_code' => $formData['ref_code'],
            'status' => 'draft',
            'step_current' => 1
        ]));
        $kycId = intval($kycInsert['id'] ?? 0);
    }

    // Finalize any temp-uploaded files even for drafts (optional)
    $uploadedFilesRaw = $_POST['uploadedFiles'] ?? '[]';
    $uploadedFiles = [];
    if (is_string($uploadedFilesRaw) && $uploadedFilesRaw !== '') {
        $decoded = json_decode($uploadedFilesRaw, true);
        if (is_array($decoded)) $uploadedFiles = $decoded;
    }

    if (!empty($uploadedFiles) && $clientId && $kycId) {
        $finalize = kyc_finalize_temp_uploads($_SESSION['user_id'], $uploadedFiles, $clientId, $kycId);
        if (($finalize['success'] ?? false) && !empty($finalize['files'])) {
            foreach ($finalize['files'] as $doc) {
                $filePath = $doc['file_path'] ?? null;
                // Avoid duplicating rows when resuming drafts.
                if ($filePath) {
                    $already = fetchOne(
                        "SELECT document_id FROM documents WHERE kyc_id = ? AND file_path = ? LIMIT 1",
                        [$kycId, $filePath]
                    );
                    if ($already) continue;
                }
                insert('documents', [
                    'kyc_id' => $kycId,
                    'client_id' => $clientId,
                    'file_name' => $doc['file_name'] ?? '',
                    'file_type' => $doc['file_type'] ?? null,
                    'file_size' => $doc['file_size'] ?? null,
                    'file_path' => $doc['file_path'] ?? null,
                    'document_type' => 'supporting',
                    'uploaded_by' => $_SESSION['user_id'],
                    'status' => 'pending'
                ]);
            }
        }
    }
    
    $response['success'] = true;
    $response['message'] = 'Draft saved successfully';
    $response['reference_code'] = $formData['ref_code'];
}

// ============================================
// LIST DRAFTS
// ============================================
else if ($action === 'get_drafts' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $draftType = trim($_GET['draftType'] ?? '');

    $params = [$_SESSION['user_id']];
    $whereSql = "WHERE k.status = 'draft' AND c.submitted_by = ?";

    if (!empty($draftType)) {
        $whereSql .= " AND k.client_type = ?";
        $params[] = $draftType;
    }

    $sql = "
        SELECT
            k.kyc_id,
            COALESCE(k.ref_code, k.reference_code) AS ref_code,
            k.client_type,
            k.status,
            k.updated_at,
            k.first_name,
            k.last_name,
            k.company,
            k.mobile,
            k.email
        FROM kyc_verifications k
        INNER JOIN clients c ON c.client_id = k.client_id
        $whereSql
        ORDER BY k.updated_at DESC
    ";

    $drafts = fetchAll($sql, $params);

    $response['success'] = true;
    $response['data'] = $drafts;
    echo json_encode($response);
    exit;
}

// ============================================
// GET DRAFT DOCUMENTS
// ============================================
else if ($action === 'get_draft_documents' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $refCode = trim($_GET['ref_code'] ?? '');
    if (empty($refCode)) {
        $response['message'] = 'Reference code is required';
        echo json_encode($response);
        exit;
    }

    $sql = "
        SELECT
            d.document_id,
            d.file_name,
            d.file_type,
            d.file_size,
            d.file_path,
            d.uploaded_at,
            d.status
        FROM documents d
        INNER JOIN kyc_verifications k ON k.kyc_id = d.kyc_id
        INNER JOIN clients c ON c.client_id = k.client_id
        WHERE k.status = 'draft'
          AND c.submitted_by = ?
          AND COALESCE(k.ref_code, k.reference_code) = ?
        ORDER BY d.uploaded_at DESC
    ";

    $docs = fetchAll($sql, [$_SESSION['user_id'], $refCode]);

    $response['success'] = true;
    $response['data'] = $docs;
    echo json_encode($response);
    exit;
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
    
    $kyc = fetchOne("SELECT * FROM kyc_verifications WHERE COALESCE(ref_code, reference_code) = ?", [$refCode]);
    
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
