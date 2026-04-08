<?php
/**
 * Client Operations Handler
 * API Endpoints for client management
 */

header('Content-Type: application/json');
require_once '../config/db.php';
require_once __DIR__ . '/upload_utils.php';
require_once __DIR__ . '/client_activity_utils.php';
session_start();

$response = ['success' => false, 'message' => ''];

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

        $includeActivityColumns = clientActivityHasColumn($db, 'clients', 'last_transaction_date')
            && clientActivityHasColumn($db, 'clients', 'activity_status')
            && clientActivityHasColumn($db, 'clients', 'activity_status_updated_at')
            && clientActivityHasColumn($db, 'agents', 'last_transaction_date')
            && clientActivityHasColumn($db, 'agents', 'activity_status')
            && clientActivityHasColumn($db, 'agents', 'activity_status_updated_at');

        $activityInsertColumns = $includeActivityColumns ? ",\n                last_transaction_date,\n                activity_status,\n                activity_status_updated_at" : '';
        $activitySelectColumns = $includeActivityColumns ? ",\n                c.last_transaction_date,\n                c.activity_status,\n                c.activity_status_updated_at" : '';
        $activityUpdateColumns = $includeActivityColumns ? ",\n                last_transaction_date = VALUES(last_transaction_date),\n                activity_status = VALUES(activity_status),\n                activity_status_updated_at = VALUES(activity_status_updated_at)" : '';

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
                created_at{$activityInsertColumns}
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
                c.created_at{$activitySelectColumns}
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
                verified_by = VALUES(verified_by){$activityUpdateColumns},
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

if (!function_exists('clientApprovalsTableExists')) {
    function clientApprovalsTableExists() {
        global $db;

        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        $result = $db->query("SHOW TABLES LIKE 'client_approvals'");
        $exists = $result && $result->num_rows > 0;

        if ($result instanceof mysqli_result) {
            $result->free();
        }

        return $exists;
    }
}

if (!function_exists('queueClientForApproval')) {
    function queueClientForApproval($clientId) {
        global $db;

        $clientId = intval($clientId);
        if ($clientId <= 0 || !clientApprovalsTableExists()) {
            return;
        }

        $sql = "
            INSERT INTO client_approvals (
                client_id,
                reference_code,
                client_number,
                client_classification,
                client_type,
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

if (!function_exists('generateClientNumber')) {
    function generateClientNumber() {
        try {
            $suffix = strtoupper(bin2hex(random_bytes(3)));
        } catch (Exception $e) {
            $suffix = strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
        }

        return 'CN-' . date('YmdHis') . '-' . $suffix;
    }
}

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
    $clientType = strtolower(trim($_POST['clientType'] ?? ''));
    $allowedClientTypes = ['individual', 'corporate', 'obligee'];
    
    // Validation
    if (empty($clientType) || !in_array($clientType, $allowedClientTypes, true)) {
        $response['message'] = 'Invalid client type';
        echo json_encode($response);
        exit;
    }
    
    // Auto-generate unique reference code
    $refCode = generateUniqueReferenceCode();
    
    // Generate client number
    $clientNumber = generateClientNumber();
    
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
        'salutation' => 'salutation',
        'firstName' => 'first_name',
        'lastName' => 'last_name',
        'middleName' => 'middle_name',
        'birthdate' => 'date_of_birth',
        'gender' => 'gender',
        'nationality' => 'nationality',
        'clientSince' => 'client_since',
        'apSlCode' => 'ap_sl_code',
        'apSlCode2' => 'ar_sl_code',
        'arSlCode' => 'ar_sl_code',
        'occupation' => 'occupation',
        'company' => 'company_name',
        'idType' => 'id_type',
        'idNumber' => 'id_number',
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

    $classificationRaw = strtolower(trim($_POST['clientClassification'] ?? ($insertData['client_classification'] ?? 'client')));
    $insertData['client_classification'] = $classificationRaw === 'agent' ? 'agent' : 'client';

    if (in_array($clientType, ['corporate', 'obligee'], true)) {
        $corporateLikeName = trim($_POST['corporateClientName'] ?? '');
        if ($corporateLikeName !== '') {
            $insertData['client_name'] = $corporateLikeName;
            $insertData['company_name'] = $corporateLikeName;
        }
    }
    
    // Insert client
    $result = insert('clients', $insertData);
    
    if (!isset($result['success'])) {
        $response['message'] = 'Failed to add client';
        echo json_encode($response);
        exit;
    }
    
    $clientId = intval($result['id'] ?? 0);

    // Finalize any temp-uploaded files (from form page) and record documents
    $uploadedFilesRaw = $_POST['uploadedFiles'] ?? '[]';
    $uploadedFiles = [];
    if (is_string($uploadedFilesRaw) && $uploadedFilesRaw !== '') {
        $decoded = json_decode($uploadedFilesRaw, true);
        if (is_array($decoded)) $uploadedFiles = $decoded;
    }

    if (!empty($uploadedFiles) && $clientId) {
        // Ensure a KYC record exists to satisfy documents.kyc_id NOT NULL
        $existingKyc = fetchOne("SELECT kyc_id FROM kyc_verifications WHERE client_id = ?", [$clientId]);
        $kycId = intval($existingKyc['kyc_id'] ?? 0);

        if (!$kycId) {
            $kycInsert = insert('kyc_verifications', [
                'client_id' => $clientId,
                'reference_code' => $refCode,
                'ref_code' => $refCode,
                'client_type' => $clientType,
                'status' => 'submitted',
                'submitted_at' => date('Y-m-d H:i:s'),
                'step_current' => 4,
                'step_1_completed' => true,
                'step_2_completed' => true,
                'step_3_completed' => true,
                'step_4_completed' => true,
            ]);
            $kycId = intval($kycInsert['id'] ?? 0);
        }

        if ($kycId) {
            $finalize = kyc_finalize_temp_uploads($_SESSION['user_id'], $uploadedFiles, $clientId, $kycId);
            if (($finalize['success'] ?? false) && !empty($finalize['files'])) {
                foreach ($finalize['files'] as $doc) {
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
    }

    $uploadedIdFilesRaw = $_POST['uploadedIdFiles'] ?? '[]';
    $uploadedIdFiles = [];
    if (is_string($uploadedIdFilesRaw) && $uploadedIdFilesRaw !== '') {
        $decoded = json_decode($uploadedIdFilesRaw, true);
        if (is_array($decoded)) $uploadedIdFiles = $decoded;
    }

    if (!empty($uploadedIdFiles) && $clientId) {
        $existingKyc = fetchOne("SELECT kyc_id FROM kyc_verifications WHERE client_id = ?", [$clientId]);
        $kycId = intval($existingKyc['kyc_id'] ?? 0);

        if (!$kycId) {
            $kycInsert = insert('kyc_verifications', [
                'client_id' => $clientId,
                'reference_code' => $refCode,
                'ref_code' => $refCode,
                'client_type' => $clientType,
                'status' => 'submitted',
                'submitted_at' => date('Y-m-d H:i:s'),
                'step_current' => 4,
                'step_1_completed' => true,
                'step_2_completed' => true,
                'step_3_completed' => true,
                'step_4_completed' => true,
            ]);
            $kycId = intval($kycInsert['id'] ?? 0);
        }

        if ($kycId) {
            $finalize = kyc_finalize_temp_uploads($_SESSION['user_id'], $uploadedIdFiles, $clientId, $kycId);
            if (($finalize['success'] ?? false) && !empty($finalize['files'])) {
                foreach ($finalize['files'] as $doc) {
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
    }

    if ($clientId > 0) {
        if (($insertData['client_classification'] ?? 'client') === 'agent') {
            syncAgentRowFromClient($clientId);
        } else {
            deleteAgentRowByClient($clientId);
        }

        queueClientForApproval($clientId);
    }

    $response['success'] = true;
    $response['message'] = 'Client added successfully';
    $response['client_id'] = $clientId;
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
    $lastTransactionDateInput = trim($_POST['lastTransactionDate'] ?? '');
    $lastTransactionDate = null;
    
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

    $currentLastTransactionDate = null;
    if (clientActivityHasColumn($db, 'clients', 'last_transaction_date')) {
        $currentLastTransaction = clientActivityParseDate($client['last_transaction_date'] ?? null);
        $currentLastTransactionDate = $currentLastTransaction ? $currentLastTransaction->format('Y-m-d') : null;
    }

    if ($lastTransactionDateInput !== '') {
        $parsedLastTransactionDate = clientActivityParseDate($lastTransactionDateInput);
        if (!$parsedLastTransactionDate) {
            $response['message'] = 'Invalid last transaction date';
            echo json_encode($response);
            exit;
        }

        $lastTransactionDate = $parsedLastTransactionDate->format('Y-m-d');
    }
    
    // Update client
    $updateData = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'middle_name' => $middleName,
        'email' => $email,
        'mobile_phone' => $mobile,
        'occupation' => $occupation,
        'full_address' => $address,
        'client_type' => $clientType
    ];

    if (clientActivityHasColumn($db, 'clients', 'last_transaction_date')) {
        $updateData['last_transaction_date'] = $lastTransactionDate;
    }

    $lastTransactionDateChanged = $lastTransactionDateInput !== ''
        ? $currentLastTransactionDate !== $lastTransactionDate
        : $currentLastTransactionDate !== null;

    $result = update('clients', $updateData, 'client_id = ?', [$clientId]);
    if (!($result['success'] ?? false)) {
        $response['message'] = $result['error'] ?? 'Failed to update client';
        echo json_encode($response);
        exit;
    }

    if (clientActivityHasColumn($db, 'clients', 'last_transaction_date')
        && clientActivityHasColumn($db, 'clients', 'activity_status')
        && clientActivityHasColumn($db, 'clients', 'activity_status_updated_at')
    ) {
        clientActivityRefreshRow($db, 'clients', $clientId, 'client_id', $lastTransactionDateChanged);
    }

    $effectiveClassification = strtolower(trim($client['client_classification'] ?? 'client'));
    if ($effectiveClassification === 'agent') {
        syncAgentRowFromClient($clientId);
    } else {
        deleteAgentRowByClient($clientId);
    }
    
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

    deleteAgentRowByClient($clientId);
    
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
    
    if (clientActivityHasColumn($db, 'clients', 'last_transaction_date')
        && clientActivityHasColumn($db, 'clients', 'activity_status')
        && clientActivityHasColumn($db, 'clients', 'activity_status_updated_at')
    ) {
        clientActivityRefreshRow($db, 'clients', $clientId);
    }

    $client = fetchOne("SELECT * FROM clients WHERE client_id = ?", [$clientId]);
    
    if (!$client) {
        $response['message'] = 'Client not found';
        echo json_encode($response);
        exit;
    }
    
    $response['success'] = true;
    $response['data'] = array_merge($client, clientActivityBuildSnapshot($client));
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
    $clients = array_map(static function (array $client): array {
        return array_merge($client, clientActivityBuildSnapshot($client));
    }, $clients);
    
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
    $client = fetchOne("SELECT verification_status, client_classification FROM clients WHERE client_id = ?", [$clientId]);
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

    $effectiveClassification = strtolower(trim($client['client_classification'] ?? 'client'));
    if ($effectiveClassification === 'agent') {
        syncAgentRowFromClient($clientId);
    } else {
        deleteAgentRowByClient($clientId);
    }
    
    $response['success'] = true;
    $response['message'] = 'Client status updated successfully';
}

else {
    $response['message'] = 'Invalid action';
}

echo json_encode($response);
?>
