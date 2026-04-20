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

if (!function_exists('kycCurrentUserBranchContext')) {
    function kycCurrentUserBranchContext(): string {
        $branch = strtoupper(trim((string)($_SESSION['branch'] ?? '')));
        $department = strtoupper(trim((string)($_SESSION['department'] ?? '')));
        $role = strtolower(trim((string)($_SESSION['role'] ?? '')));

        if ($branch !== '') {
            return $branch;
        }

        if ($role === 'admin' || $department === 'HEAD OFFICE') {
            return 'HEAD OFFICE';
        }

        return '';
    }
}

if (!function_exists('kycResolveBranchManagerName')) {
    function kycResolveBranchManagerName(string $branch, string $fallbackName = '', string $fallbackRole = ''): string {
        global $db;

        $branch = strtoupper(trim($branch));
        if ($branch === '' || !$db instanceof mysqli) {
            return '';
        }

        $stmt = $db->prepare(
            "SELECT full_name
             FROM users
             WHERE UPPER(TRIM(branch)) = ?
               AND LOWER(TRIM(role)) IN ('manager', 'admin')
               AND full_name IS NOT NULL
               AND TRIM(full_name) <> ''
             ORDER BY CASE WHEN LOWER(TRIM(role)) = 'manager' THEN 0 ELSE 1 END, user_id ASC
             LIMIT 1"
        );

        if ($stmt) {
            $stmt->bind_param('s', $branch);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;

            if ($result instanceof mysqli_result) {
                $result->free();
            }

            $stmt->close();

            if (!empty($row['full_name'])) {
                return trim((string)$row['full_name']);
            }
        }

        $fallbackName = trim($fallbackName);
        $fallbackRole = strtolower(trim($fallbackRole));
        if ($fallbackName !== '' && in_array($fallbackRole, ['manager', 'admin'], true)) {
            return $fallbackName;
        }

        return '';
    }
}

if (!function_exists('kycFetchAllowedHeadAgentNames')) {
    function kycFetchAllowedHeadAgentNames(string $branch): array {
        global $db;

        $branch = strtoupper(trim($branch));
        if ($branch === '' || !$db instanceof mysqli) {
            return [];
        }

        $names = [];
        $stmt = $db->prepare(
            "SELECT DISTINCT
                COALESCE(
                    NULLIF(TRIM(c.client_name), ''),
                    NULLIF(TRIM(CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, ''))), ''),
                    c.reference_code
                ) AS head_agent_name
             FROM clients c
                         WHERE COALESCE(NULLIF(LOWER(TRIM(c.client_classification)), ''), 'client') = 'agent'
                             AND COALESCE(NULLIF(LOWER(TRIM(c.agent_type)), ''), 'agent') = 'agent'
                             AND COALESCE(NULLIF(UPPER(TRIM(c.agent_branch)), ''), '') = ?
                             AND COALESCE(NULLIF(LOWER(TRIM(c.activity_status)), ''), 'active') = 'active'
             ORDER BY head_agent_name ASC"
        );

        if ($stmt) {
            $stmt->bind_param('s', $branch);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result instanceof mysqli_result) {
                while ($row = $result->fetch_assoc()) {
                    $name = trim((string)($row['head_agent_name'] ?? ''));
                    if ($name !== '') {
                        $names[] = $name;
                    }
                }
                $result->free();
            }

            $stmt->close();
        }

        return array_values(array_unique($names));
    }
}

if (!function_exists('kycHeadAgentNameIsAllowed')) {
    function kycHeadAgentNameIsAllowed(string $headAgentName, array $allowedNames): bool {
        $headAgentName = trim($headAgentName);
        if ($headAgentName === '') {
            return false;
        }

        foreach ($allowedNames as $allowedName) {
            if (strcasecmp($headAgentName, trim((string)$allowedName)) === 0) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('kycIsNoneOfTheAboveHeadAgentValue')) {
    function kycIsNoneOfTheAboveHeadAgentValue(string $value): bool {
        return strtolower(trim($value)) === '__none_of_the_above__';
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

if ($action === 'head_agent_options' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $branch = strtoupper(trim((string)($_GET['branch'] ?? '')));
    if ($branch === '') {
        echo json_encode([
            'success' => true,
            'branch_manager_name' => '',
            'options' => []
        ]);
        exit;
    }

    $branchManagerName = kycResolveBranchManagerName($branch, (string)($_SESSION['full_name'] ?? ''), (string)($_SESSION['role'] ?? ''));
    $options = kycFetchAllowedHeadAgentNames($branch);
    $options = array_values(array_filter($options, static function ($name) use ($branchManagerName) {
        return $branchManagerName === '' || strcasecmp(trim((string)$name), $branchManagerName) !== 0;
    }));

    echo json_encode([
        'success' => true,
        'branch_manager_name' => $branchManagerName,
        'options' => $options
    ]);
    exit;
}

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
        $response['message'] = $clientType === 'obligee' ? 'Body type is required' : 'Business type is required';
        echo json_encode($response);
        exit;
    }

    if ($isCorporateLike && !in_array($postedBusinessType, ['private', 'government'], true)) {
        $response['message'] = 'Please select a valid business type';
        echo json_encode($response);
        exit;
    }

    if ($clientClassification === 'agent') {
        $branchContext = strtoupper(trim($agentBranch));
        if ($branchContext === '') {
            $branchContext = kycCurrentUserBranchContext();
        }
        $branchManagerName = $agentType === 'sub_agent'
            ? kycResolveBranchManagerName($branchContext, (string)($_SESSION['full_name'] ?? ''), (string)($_SESSION['role'] ?? ''))
            : '';
        $allowedHeadAgentNames = $agentType === 'sub_agent'
            ? kycFetchAllowedHeadAgentNames($branchContext)
            : [];

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

        if ($agentType === 'sub_agent' && kycIsNoneOfTheAboveHeadAgentValue($headAgentName)) {
            $headAgentName = $branchManagerName;
            if ($headAgentName === '') {
                $response['message'] = 'No branch manager is registered for the selected branch';
                echo json_encode($response);
                exit;
            }
        }

        if ($agentType === 'sub_agent' && !kycHeadAgentNameIsAllowed($headAgentName, $allowedHeadAgentNames) && !kycIsNoneOfTheAboveHeadAgentValue($_POST['headAgentName'] ?? '')) {
            $response['message'] = 'Please select a valid head agent for your branch';
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
            'business_type' => $postedBusinessType,
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
