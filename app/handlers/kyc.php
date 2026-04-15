<?php
/**
 * KYC Verification Handler
 * API Endpoints for KYC operations
 */

header('Content-Type: application/json');
require_once '../config/db.php';
require_once __DIR__ . '/upload_utils.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('kyc_finalize_temp_uploads')) {
    function kyc_finalize_temp_uploads($userId, $uploadedFiles, $clientId, $kycId) {
        if (function_exists('finalize_temp_uploads')) {
            return finalize_temp_uploads($userId, $uploadedFiles, $clientId, $kycId);
        }

        if (function_exists('kyc_finalize_uploads')) {
            return kyc_finalize_uploads($userId, $uploadedFiles, $clientId, $kycId);
        }

        return [
            'success' => false,
            'message' => 'Upload finalizer function is not available',
            'files' => []
        ];
    }
}

if (!function_exists('agentsTableExists')) {
    function agentsTableExists() {
        global $db;

        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        $result = $db->query("SHOW TABLES LIKE 'agents'");
        $exists = $result && $result->num_rows > 0;

        if ($result instanceof mysqli_result) {
            $result->free();
        }

        return $exists;
    }
}

if (!function_exists('syncAgentRowFromClient')) {
    function syncAgentRowFromClient($clientId) {
        global $db;

        $clientId = intval($clientId);
        if ($clientId <= 0 || !agentsTableExists()) {
            return;
        }

        $sql = "
            INSERT INTO agents (
                client_id,
                reference_code,
                client_number,
                client_type,
                client_name,
                first_name,
                middle_name,
                last_name,
                mobile_phone,
                office_phone,
                email,
                verification_status,
                submitted_by,
                submitted_at,
                verified_by,
                created_at
            )
            SELECT
                c.client_id,
                c.reference_code,
                c.client_number,
                c.client_type,
                c.client_name,
                c.first_name,
                c.middle_name,
                c.last_name,
                c.mobile_phone,
                c.office_phone,
                c.email,
                c.verification_status,
                c.submitted_by,
                c.submitted_at,
                c.verified_by,
                c.created_at
            FROM clients c
            WHERE c.client_id = ?
            LIMIT 1
            ON DUPLICATE KEY UPDATE
                reference_code = VALUES(reference_code),
                client_number = VALUES(client_number),
                client_type = VALUES(client_type),
                client_name = VALUES(client_name),
                first_name = VALUES(first_name),
                middle_name = VALUES(middle_name),
                last_name = VALUES(last_name),
                mobile_phone = VALUES(mobile_phone),
                office_phone = VALUES(office_phone),
                email = VALUES(email),
                verification_status = VALUES(verification_status),
                submitted_by = VALUES(submitted_by),
                submitted_at = VALUES(submitted_at),
                verified_by = VALUES(verified_by),
                updated_at = CURRENT_TIMESTAMP
        ";

        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('i', $clientId);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('deleteAgentRowByClient')) {
    function deleteAgentRowByClient($clientId) {
        global $db;

        $clientId = intval($clientId);
        if ($clientId <= 0 || !agentsTableExists()) {
            return;
        }

        $stmt = $db->prepare("DELETE FROM agents WHERE client_id = ?");
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('i', $clientId);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('approvalQueueTableExists')) {
    function approvalQueueTableExists($tableName) {
        global $db;

        static $exists = [];
        $tableName = trim((string)$tableName);
        if ($tableName === '') {
            return false;
        }

        if (array_key_exists($tableName, $exists)) {
            return $exists[$tableName];
        }

        $safeTable = preg_replace('/[^a-z0-9_]/i', '', $tableName);
        if ($safeTable === '') {
            $exists[$tableName] = false;
            return false;
        }

        $result = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($safeTable) . "'");
        $exists[$tableName] = $result && $result->num_rows > 0;

        if ($result instanceof mysqli_result) {
            $result->free();
        }

        return $exists[$tableName];
    }
}

if (!function_exists('clientApprovalsTableExists')) {
    function clientApprovalsTableExists() {
        global $db;

        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        return approvalQueueTableExists('client_approvals');
    }
}

if (!function_exists('queueClientForApproval')) {
    function queueClientForApproval($clientId, $clientClassification = 'client') {
        global $db;

        $clientId = intval($clientId);
        $clientClassification = strtolower(trim((string)$clientClassification));
        $queueTable = $clientClassification === 'agent' ? 'agent_approvals' : 'client_approvals';

        if ($clientId <= 0 || !approvalQueueTableExists($queueTable)) {
            return;
        }

        $sql = "
            INSERT INTO {$queueTable} (
                client_id,
                reference_code,
                client_number,
                client_classification,
                client_type,
                agent_type,
                head_agent_name,
                agent_branch,
                display_name,
                client_name,
                first_name,
                middle_name,
                last_name,
                contact_person,
                mobile_phone,
                office_phone,
                email,
                submitted_by,
                submitted_by_branch,
                submitted_at,
                approval_status,
                review_notes,
                reviewed_by,
                reviewed_at,
                approved_at,
                created_at
            )
            SELECT
                c.client_id,
                c.reference_code,
                c.client_number,
                COALESCE(NULLIF(LOWER(TRIM(c.client_classification)), ''), 'client') AS client_classification,
                c.client_type,
                c.agent_type,
                c.head_agent_name,
                c.agent_branch,
                COALESCE(
                    NULLIF(TRIM(c.client_name), ''),
                    NULLIF(TRIM(c.contact_person), ''),
                    NULLIF(TRIM(CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, ''))), ''),
                    c.reference_code
                ) AS display_name,
                c.client_name,
                c.first_name,
                c.middle_name,
                c.last_name,
                c.contact_person,
                c.mobile_phone,
                c.office_phone,
                c.email,
                c.submitted_by,
                su.branch AS submitted_by_branch,
                c.submitted_at,
                'pending' AS approval_status,
                NULL AS review_notes,
                NULL AS reviewed_by,
                NULL AS reviewed_at,
                NULL AS approved_at,
                c.created_at
            FROM clients c
            LEFT JOIN users su ON c.submitted_by = su.user_id
            WHERE c.client_id = ?
            LIMIT 1
            ON DUPLICATE KEY UPDATE
                client_number = VALUES(client_number),
                client_classification = VALUES(client_classification),
                client_type = VALUES(client_type),
                agent_type = VALUES(agent_type),
                head_agent_name = VALUES(head_agent_name),
                agent_branch = VALUES(agent_branch),
                display_name = VALUES(display_name),
                client_name = VALUES(client_name),
                first_name = VALUES(first_name),
                middle_name = VALUES(middle_name),
                last_name = VALUES(last_name),
                contact_person = VALUES(contact_person),
                mobile_phone = VALUES(mobile_phone),
                office_phone = VALUES(office_phone),
                email = VALUES(email),
                submitted_by = VALUES(submitted_by),
                submitted_by_branch = VALUES(submitted_by_branch),
                submitted_at = VALUES(submitted_at),
                approval_status = 'pending',
                review_notes = NULL,
                reviewed_by = NULL,
                reviewed_at = NULL,
                approved_at = NULL,
                updated_at = CURRENT_TIMESTAMP
        ";

        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('i', $clientId);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('generateRecordNumber')) {
    function generateRecordNumber($prefix = 'CN', $includeDate = true) {
        if (!$includeDate) {
            $numericPart = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            return $prefix . '-' . $numericPart;
        }

        try {
            $suffix = strtoupper(bin2hex(random_bytes(3)));
        } catch (Exception $e) {
            $suffix = strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
        }

        return $prefix . '-' . date('YmdHis') . '-' . $suffix;
    }
}

if (!function_exists('generateClientNumber')) {
    function generateClientNumber() {
        return sprintf('CN - %06d', random_int(0, 999999));
    }
}

if (!function_exists('generateAgentNumber')) {
    function generateAgentNumber() {
        return generateRecordNumber('AG', false);
    }
}

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
    $clientTypeRaw = strtolower(trim($_POST['clientType'] ?? ''));
    $allowedClientTypes = ['individual', 'corporate', 'obligee'];
    if (!in_array($clientTypeRaw, $allowedClientTypes, true)) {
        $response['message'] = 'Invalid client type';
        echo json_encode($response);
        exit;
    }

    $clientType = $clientTypeRaw;
    $isCorporateLike = in_array($clientType, ['corporate', 'obligee'], true);

    $classificationRaw = strtolower(trim($_POST['clientClassification'] ?? 'client'));
    // Product rule: only individual can be classified as agent.
    $clientClassification = ($classificationRaw === 'agent' && $clientType === 'individual')
        ? 'agent'
        : 'client';

    $postedClientNumber = trim($_POST['clientNumber'] ?? '');
    $resolvedClientNumber = $postedClientNumber !== ''
        ? $postedClientNumber
        : ($clientClassification === 'agent' ? generateAgentNumber() : generateClientNumber());

    $individualOccupation = trim($_POST['occupation'] ?? '');
    if ($clientClassification === 'agent' && $individualOccupation === '') {
        $individualOccupation = 'Insurance Agent';
    }

    $agentTypeRaw = strtolower(trim($_POST['agentType'] ?? 'agent'));
    $agentType = in_array($agentTypeRaw, ['agent', 'sub_agent'], true) ? $agentTypeRaw : 'agent';
    $headAgentName = trim($_POST['headAgentName'] ?? '');
    $agentBranch = trim($_POST['agentBranch'] ?? '');

    if ($clientClassification !== 'agent') {
        $agentType = null;
        $headAgentName = null;
        $agentBranch = null;
    }
    
    // Map form field names to database field names (handling form field mismatches)
    $formData = [
        'ref_code' => $userProvidedRefCode,
        'client_type' => $clientType,
        'last_name' => $isCorporateLike ? '' : trim($_POST['lastName'] ?? ''),
        'first_name' => $isCorporateLike ? '' : trim($_POST['firstName'] ?? ''),
        'middle_name' => $isCorporateLike ? '' : trim($_POST['middleName'] ?? ''),
        'suffix' => $isCorporateLike ? '' : trim($_POST['suffixName'] ?? ''),
        'birthdate' => $isCorporateLike ? null : trim($_POST['birthdate'] ?? ''),
        'gender' => trim($_POST['gender'] ?? $_POST['corporateGender'] ?? ''),
        'nationality' => trim($_POST['nationality'] ?? ''),
        'id_type' => trim($_POST['idType'] ?? ''),
        'id_number' => trim($_POST['idNumber'] ?? ''),
        'tin_number' => trim($_POST['tinNumber'] ?? ''),
        'occupation' => $isCorporateLike
            ? trim($_POST['corporateContactPerson'] ?? '')
            : $individualOccupation,
        'agent_type' => $clientClassification === 'agent' ? $agentType : null,
        'head_agent_name' => $clientClassification === 'agent' && $agentType === 'sub_agent' ? $headAgentName : null,
        'agent_branch' => $clientClassification === 'agent' ? $agentBranch : null,
        'company' => $isCorporateLike
            ? trim($_POST['corporateClientName'] ?? '')
            : trim($_POST['employer'] ?? $_POST['company'] ?? ''),
        'mobile' => $isCorporateLike
            ? trim($_POST['corporatePhone'] ?? '')
            : trim($_POST['mobile'] ?? ''),
        'phone' => $isCorporateLike
            ? trim($_POST['corporatePhone'] ?? '')
            : trim($_POST['telephone'] ?? $_POST['phone'] ?? ''),
        'email' => $isCorporateLike
            ? trim($_POST['corporateEmail'] ?? '')
            : trim($_POST['email'] ?? ''),
        'address' => $isCorporateLike
            ? trim($_POST['corporateBusinessAddress'] ?? $_POST['address'] ?? '')
            : trim($_POST['homeAddress'] ?? $_POST['address'] ?? '')
    ];
    
    // Validation of required fields (excluding ref_code since it will be auto-generated)
    $required = ['client_type', 'email', 'address'];
    if ($isCorporateLike) {
        $required = array_merge($required, ['company', 'occupation', 'mobile']);
    } else {
        $required = array_merge($required, ['last_name', 'first_name', 'birthdate', 'mobile']);
        if ($clientClassification === 'agent') {
            $required = array_merge($required, ['occupation', 'agent_branch']);
        } else {
            $required = array_merge($required, ['occupation', 'id_type', 'id_number']);
        }
    }

    $postedBusinessType = trim($_POST['businessType'] ?? '');

    foreach ($required as $field) {
        if (trim((string)($formData[$field] ?? '')) === '') {
            $response['message'] = 'All required fields must be filled';
            echo json_encode($response);
            exit;
        }
    }

    if ($isCorporateLike && $postedBusinessType === '') {
        $response['message'] = 'Business type is required';
        echo json_encode($response);
        exit;
    }

    if ($clientType === 'obligee' && $postedBusinessType !== 'government') {
        $response['message'] = 'Obligee clients must be registered as Philippine government bodies';
        echo json_encode($response);
        exit;
    }

    if ($clientClassification === 'agent') {
        if ($agentBranch === '') {
            $response['message'] = 'Branch is required for agents';
            echo json_encode($response);
            exit;
        }

        if ($agentType === 'sub_agent' && $headAgentName === '') {
            $response['message'] = 'Head Agent Name is required for Sub agent';
            echo json_encode($response);
            exit;
        }
    }
    
    // If no reference code provided, generate a unique one
    if (empty($userProvidedRefCode)) {
        $formData['ref_code'] = generateUniqueReferenceCode();
    }
    
    // Check if client already exists using provided/generated reference code
    $existingClient = fetchOne("SELECT client_id, submitted_by, client_number FROM clients WHERE reference_code = ?", [$formData['ref_code']]);
    
    // Prepare client data for insertion/update based on type
    if ($clientType === 'individual') {
        $clientUpdateData = [
            'client_type' => $formData['client_type'],
            'client_classification' => $clientClassification,
            'first_name' => $formData['first_name'],
            'middle_name' => $formData['middle_name'],
            'last_name' => $formData['last_name'],
            'suffix' => $formData['suffix'],
            'date_of_birth' => $formData['birthdate'],
            'gender' => $formData['gender'],
            'nationality' => $formData['nationality'],
            'id_type' => $clientClassification === 'agent' ? ($formData['id_type'] ?: null) : $formData['id_type'],
            'id_number' => $clientClassification === 'agent' ? ($formData['id_number'] ?: null) : $formData['id_number'],
            'tin_number' => $formData['tin_number'] ?: null,
            'salutation' => trim($_POST['salutation'] ?? ''),
            'client_since' => trim($_POST['clientSince'] ?? ''),
            'ap_sl_code' => trim($_POST['apSlCode'] ?? ''),
            'ar_sl_code' => trim($_POST['arSlCode'] ?? $_POST['apSlCode2'] ?? ''),
            'occupation' => $clientClassification === 'agent' ? ($formData['occupation'] ?: 'Insurance Agent') : $formData['occupation'],
            'agent_type' => $clientClassification === 'agent' ? $agentType : null,
            'head_agent_name' => $clientClassification === 'agent' && $agentType === 'sub_agent' ? $headAgentName : null,
            'agent_branch' => $clientClassification === 'agent' ? $agentBranch : null,
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
        $corporateName = trim($_POST['corporateClientName'] ?? $formData['company']);

        $clientUpdateData = [
            'client_type' => $formData['client_type'],
            'client_classification' => $clientClassification,
            'client_name' => $corporateName,
            'company_name' => $corporateName,
            'business_type' => $clientType === 'obligee' ? 'government' : $postedBusinessType,
            'id_type' => $formData['id_type'],
            'id_number' => $formData['id_number'],
            'client_since' => trim($_POST['corporateClientSince'] ?? ''),
            'tin_number' => trim($_POST['tinNumber'] ?? ''),
            'ap_sl_code' => trim($_POST['corporateApSlCode'] ?? ''),
            'ar_sl_code' => trim($_POST['corporateArSlCode'] ?? ''),
            'designation' => trim($_POST['designation'] ?? ''),
            'business_address' => $formData['address'],
            'business_ctm' => trim($_POST['corporateBusinessCtm'] ?? ''),
            'business_province' => trim($_POST['corporateBusinessProvince'] ?? ''),
            'region' => trim($_POST['region'] ?? ''),
            'office_phone' => $formData['mobile'],
            'email' => $formData['email'],
            'contact_person' => $formData['occupation'],
            'gender' => $formData['gender'],
            'nationality' => $formData['nationality'],
            'agent_type' => null,
            'head_agent_name' => null,
            'agent_branch' => null,
            'verification_status' => 'pending'
        ];
    }
    
    if ($existingClient) {
        $clientId = intval($existingClient['client_id']);

        // Keep original submitter if already set; otherwise record current account.
        if (empty($existingClient['submitted_by'])) {
            $clientUpdateData['submitted_by'] = intval($_SESSION['user_id']);
            $clientUpdateData['submitted_at'] = date('Y-m-d H:i:s');
        }

        if (empty($existingClient['client_number']) && $resolvedClientNumber !== '') {
            $clientUpdateData['client_number'] = $resolvedClientNumber;
        }

        update('clients', $clientUpdateData, 'client_id = ?', [$clientId]);
    } else {
        // Create new client
        $clientInsertData = array_merge([
            'reference_code' => $formData['ref_code'],
            'client_number' => $resolvedClientNumber,
            'submitted_by' => intval($_SESSION['user_id']),
            'submitted_at' => date('Y-m-d H:i:s'),
        ], $clientUpdateData);
        
        $result = insert('clients', $clientInsertData);
        $clientId = intval($result['id'] ?? 0);
    }

    if ($clientId <= 0) {
        $response['message'] = 'Failed to save client record';
        echo json_encode($response);
        exit;
    }

    if ($clientId > 0) {
        if ($clientClassification === 'agent') {
            syncAgentRowFromClient($clientId);
        } else {
            deleteAgentRowByClient($clientId);
        }

        queueClientForApproval($clientId, $clientClassification);
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

    $uploadedIdFilesRaw = $_POST['uploadedIdFiles'] ?? '[]';
    $uploadedIdFiles = [];
    if (is_string($uploadedIdFilesRaw) && $uploadedIdFilesRaw !== '') {
        $decoded = json_decode($uploadedIdFilesRaw, true);
        if (is_array($decoded)) $uploadedIdFiles = $decoded;
    }

    if (!empty($uploadedIdFiles) && $clientId && $kycId) {
        $finalize = kyc_finalize_temp_uploads($_SESSION['user_id'], $uploadedIdFiles, $clientId, $kycId);
        if (($finalize['success'] ?? false) && !empty($finalize['files'])) {
            foreach ($finalize['files'] as $doc) {
                $filePath = $doc['file_path'] ?? null;
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
                    'document_type' => 'government_id',
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
    $clientTypeRaw = strtolower(trim($_POST['clientType'] ?? ''));
    $allowedClientTypes = ['individual', 'corporate', 'obligee'];
    $clientType = in_array($clientTypeRaw, $allowedClientTypes, true) ? $clientTypeRaw : 'individual';

    $isCorporateLike = in_array($clientType, ['corporate', 'obligee'], true);
    $postedBusinessType = trim($_POST['businessType'] ?? '');

    $classificationRaw = strtolower(trim($_POST['clientClassification'] ?? 'client'));
    // Product rule: only individual can be classified as agent.
    $clientClassification = ($classificationRaw === 'agent' && $clientType === 'individual')
        ? 'agent'
        : 'client';

    $postedClientNumber = trim($_POST['clientNumber'] ?? '');
    $resolvedClientNumber = $postedClientNumber !== ''
        ? $postedClientNumber
        : ($clientClassification === 'agent' ? generateAgentNumber() : generateClientNumber());

    $individualOccupation = trim($_POST['occupation'] ?? '');
    if ($clientClassification === 'agent' && $individualOccupation === '') {
        $individualOccupation = 'Insurance Agent';
    }

    $agentTypeRaw = strtolower(trim($_POST['agentType'] ?? 'agent'));
    $agentType = in_array($agentTypeRaw, ['agent', 'sub_agent'], true) ? $agentTypeRaw : 'agent';
    $headAgentName = trim($_POST['headAgentName'] ?? '');
    $agentBranch = trim($_POST['agentBranch'] ?? '');

    if ($clientClassification !== 'agent') {
        $agentType = null;
        $headAgentName = null;
        $agentBranch = null;
    }

    // Keep kyc_verifications values in a unified shape so get_kyc/load draft works for both client types.
    $formData = [
        'ref_code' => $userProvidedRefCode,
        'client_type' => $clientType,
        'last_name' => $isCorporateLike ? '' : trim($_POST['lastName'] ?? ''),
        'first_name' => $isCorporateLike ? '' : trim($_POST['firstName'] ?? ''),
        'middle_name' => $isCorporateLike ? '' : trim($_POST['middleName'] ?? ''),
        'suffix' => $isCorporateLike ? '' : trim($_POST['suffixName'] ?? ''),
        'birthdate' => $isCorporateLike ? null : trim($_POST['birthdate'] ?? ''),
        'gender' => trim($_POST['gender'] ?? $_POST['corporateGender'] ?? ''),
        'nationality' => trim($_POST['nationality'] ?? ''),
        'id_type' => trim($_POST['idType'] ?? ''),
        'id_number' => trim($_POST['idNumber'] ?? ''),
        'tin_number' => trim($_POST['tinNumber'] ?? ''),
        'occupation' => $isCorporateLike
            ? trim($_POST['corporateContactPerson'] ?? '')
            : $individualOccupation,
        'agent_type' => $clientClassification === 'agent' ? $agentType : null,
        'head_agent_name' => $clientClassification === 'agent' && $agentType === 'sub_agent' ? $headAgentName : null,
        'agent_branch' => $clientClassification === 'agent' ? $agentBranch : null,
        'company' => $isCorporateLike
            ? trim($_POST['corporateClientName'] ?? '')
            : trim($_POST['employer'] ?? $_POST['company'] ?? ''),
        'mobile' => $isCorporateLike
            ? trim($_POST['corporatePhone'] ?? '')
            : trim($_POST['mobile'] ?? ''),
        'phone' => $isCorporateLike
            ? trim($_POST['corporatePhone'] ?? '')
            : trim($_POST['telephone'] ?? $_POST['phone'] ?? ''),
        'email' => $isCorporateLike
            ? trim($_POST['corporateEmail'] ?? '')
            : trim($_POST['email'] ?? ''),
        'address' => $isCorporateLike
            ? trim($_POST['corporateBusinessAddress'] ?? $_POST['address'] ?? '')
            : trim($_POST['homeAddress'] ?? $_POST['address'] ?? '')
    ];
    
    // If no reference code provided, generate a unique one
    if (empty($userProvidedRefCode)) {
        $formData['ref_code'] = generateUniqueReferenceCode();
    }

    if (empty($formData['client_type'])) {
        $formData['client_type'] = 'individual';
    }

    if ($isCorporateLike) {
        $clientUpdateData = [
            'client_type' => $clientType,
            'client_classification' => $clientClassification,
            'client_name' => trim($_POST['corporateClientName'] ?? '') ?: null,
            'company_name' => trim($_POST['corporateClientName'] ?? '') ?: null,
            'business_type' => $clientType === 'obligee' ? 'government' : ($postedBusinessType ?: null),
            'id_type' => trim($_POST['idType'] ?? '') ?: null,
            'id_number' => trim($_POST['idNumber'] ?? '') ?: null,
            'client_since' => trim($_POST['corporateClientSince'] ?? '') ?: null,
            'tin_number' => trim($_POST['tinNumber'] ?? '') ?: null,
            'ap_sl_code' => trim($_POST['corporateApSlCode'] ?? '') ?: null,
            'ar_sl_code' => trim($_POST['corporateArSlCode'] ?? '') ?: null,
            'designation' => trim($_POST['designation'] ?? '') ?: null,
            'business_address' => trim($_POST['corporateBusinessAddress'] ?? '') ?: null,
            'business_ctm' => trim($_POST['corporateBusinessCtm'] ?? '') ?: null,
            'business_province' => trim($_POST['corporateBusinessProvince'] ?? '') ?: null,
            'region' => trim($_POST['region'] ?? '') ?: null,
            'office_phone' => trim($_POST['corporatePhone'] ?? '') ?: null,
            'email' => trim($_POST['corporateEmail'] ?? '') ?: null,
            'contact_person' => trim($_POST['corporateContactPerson'] ?? '') ?: null,
            'gender' => trim($_POST['corporateGender'] ?? '') ?: null,
            'nationality' => trim($_POST['nationality'] ?? '') ?: null,
            'agent_type' => null,
            'head_agent_name' => null,
            'agent_branch' => null,
            'verification_status' => 'draft'
        ];
    } else {
        $clientUpdateData = [
            'client_type' => $formData['client_type'],
            'client_classification' => $clientClassification,
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
            'tin_number' => $formData['tin_number'] ?: null,
            'occupation' => $formData['occupation'] ?: null,
            'agent_type' => $clientClassification === 'agent' ? $agentType : null,
            'head_agent_name' => $clientClassification === 'agent' && $agentType === 'sub_agent' ? $headAgentName : null,
            'agent_branch' => $clientClassification === 'agent' ? $agentBranch : null,
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
        ];
    }

    // Ensure a client exists for this ref_code (kyc_verifications.client_id is NOT NULL)
    $clientId = 0;
    $existingClient = fetchOne("SELECT client_id, submitted_by, client_number FROM clients WHERE reference_code = ?", [$formData['ref_code']]);
    if ($existingClient) {
        $clientId = intval($existingClient['client_id']);

        if (empty($existingClient['submitted_by'])) {
            $clientUpdateData['submitted_by'] = intval($_SESSION['user_id']);
            $clientUpdateData['submitted_at'] = date('Y-m-d H:i:s');
        }

        if (empty($existingClient['client_number']) && $resolvedClientNumber !== '') {
            $clientUpdateData['client_number'] = $resolvedClientNumber;
        }

        update('clients', $clientUpdateData, 'client_id = ?', [$clientId]);
    } else {
        $clientInsert = insert('clients', array_merge([
            'reference_code' => $formData['ref_code'],
            'client_number' => $resolvedClientNumber,
            'submitted_by' => intval($_SESSION['user_id']),
            'submitted_at' => date('Y-m-d H:i:s')
        ], $clientUpdateData));
        $clientId = intval($clientInsert['id'] ?? 0);
    }

    if ($clientId > 0) {
        if ($clientClassification === 'agent') {
            syncAgentRowFromClient($clientId);
        } else {
            deleteAgentRowByClient($clientId);
        }
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

    $uploadedIdFilesRaw = $_POST['uploadedIdFiles'] ?? '[]';
    $uploadedIdFiles = [];
    if (is_string($uploadedIdFilesRaw) && $uploadedIdFilesRaw !== '') {
        $decoded = json_decode($uploadedIdFilesRaw, true);
        if (is_array($decoded)) $uploadedIdFiles = $decoded;
    }

    if (!empty($uploadedIdFiles) && $clientId && $kycId) {
        $finalize = kyc_finalize_temp_uploads($_SESSION['user_id'], $uploadedIdFiles, $clientId, $kycId);
        if (($finalize['success'] ?? false) && !empty($finalize['files'])) {
            foreach ($finalize['files'] as $doc) {
                $filePath = $doc['file_path'] ?? null;
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
                    'document_type' => 'government_id',
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
            d.document_type,
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
