<?php
/**
 * My Applications Handler
 * KYC officers can view only their submitted applications and review outcomes.
 */

header('Content-Type: application/json');
ini_set('display_errors', '0');
ini_set('html_errors', '0');
mysqli_report(MYSQLI_REPORT_OFF);
require_once '../config/db.php';
session_start();

$response = [
    'success' => false,
    'message' => '',
    'data' => [],
];

function jsonExit($payload, $statusCode = 200) {
    http_response_code(intval($statusCode));
    echo json_encode($payload);
    exit;
}

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

function getTableColumns($db, $table) {
    static $cache = [];

    $safeName = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
    if ($safeName === '') {
        return [];
    }

    if (array_key_exists($safeName, $cache)) {
        return $cache[$safeName];
    }

    $columns = [];
    $result = $db->query("SHOW COLUMNS FROM `$safeName`");
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $field = $row['Field'] ?? '';
            if ($field !== '') {
                $columns[$field] = true;
            }
        }
        $result->free();
    }

    $cache[$safeName] = $columns;
    return $columns;
}

function tableExists($db, $table) {
    static $cache = [];

    $safeName = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
    if ($safeName === '') {
        return false;
    }

    if (array_key_exists($safeName, $cache)) {
        return $cache[$safeName];
    }

    $result = $db->query("SHOW TABLES LIKE '$safeName'");
    $exists = $result && $result->num_rows > 0;

    if ($result instanceof mysqli_result) {
        $result->free();
    }

    $cache[$safeName] = $exists;
    return $exists;
}

function normalizeRole($role) {
    return strtolower(str_replace('-', '_', trim((string)$role)));
}

function getEditableFieldLengths() {
    return [
        'client_type' => 20,
        'client_name' => 200,
        'salutation' => 20,
        'first_name' => 50,
        'middle_name' => 50,
        'last_name' => 50,
        'suffix' => 10,
        'client_since' => 10,
        'contact_person' => 100,
        'mobile_phone' => 20,
        'office_phone' => 20,
        'home_phone' => 20,
        'email' => 120,
        'id_type' => 50,
        'id_number' => 50,
        'tin_number' => 50,
        'occupation' => 100,
        'company_name' => 100,
        'designation' => 100,
        'gender' => 10,
        'nationality' => 50,
        'date_of_birth' => 10,
        'spouse_name' => 100,
        'spouse_birthdate' => 10,
        'spouse_occupation' => 100,
        'business_type' => 20,
        'business_street' => 150,
        'business_barangay' => 100,
        'business_address' => 255,
        'business_ctm' => 50,
        'business_province' => 50,
        'home_address' => 255,
        'home_ctm' => 50,
        'home_province' => 50,
        'region' => 100,
        'full_address' => 255,
    ];
}

function normalizeNullableDate($value) {
    $trimmed = trim((string)$value);
    if ($trimmed === '') {
        return null;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed)) {
        return null;
    }

    return $trimmed;
}

function parseBusinessAddressComponents($addressStr) {
    $trimmed = trim((string)$addressStr);
    if ($trimmed === '') {
        return null;
    }

    $parts = array_map('trim', explode(',', $trimmed));
    $parts = array_values(array_filter($parts, static fn($part) => $part !== ''));

    if (count($parts) < 5) {
        return null;
    }

    return [
        'street' => $parts[0],
        'barangay' => $parts[1],
        'city' => $parts[2],
        'province' => $parts[3],
        'region' => implode(', ', array_slice($parts, 4)),
    ];
}

function buildBusinessAddressString($street, $barangay, $city, $province, $region) {
    $parts = [];
    foreach ([$street, $barangay, $city, $province, $region] as $part) {
        $text = trim((string)$part);
        if ($text !== '') {
            $parts[] = $text;
        }
    }

    return implode(', ', $parts);
}

function myApplicationsNormalizeIncomingFieldName($fieldName) {
    $field = strtolower(trim((string)$fieldName));
    if ($field === '') {
        return '';
    }

    $aliases = [
        'corporateclientname' => 'client_name',
        'businesstype' => 'business_type',
        'corporateclientsince' => 'client_since',
        'tinnumber' => 'tin_number',
        'corporateapslcode' => 'ap_sl_code',
        'corporatearslcode' => 'ar_sl_code',
        'corporatestreet' => 'business_street',
        'corporatebusinessbarangay' => 'business_barangay',
        'corporatebusinessaddress' => 'business_address',
        'corporatebusinessctm' => 'business_ctm',
        'corporatebusinessprovince' => 'business_province',
        'corporatephone' => 'office_phone',
        'corporatecontactperson' => 'contact_person',
        'corporateemail' => 'email',
        'corporategender' => 'gender',
        'governmentidtype' => 'id_type',
        'idtype' => 'id_type',
        'idnumber' => 'id_number',
        'clientclassification' => 'client_classification',
    ];

    return $aliases[$field] ?? $field;
}

function coalesceDisplayName($values, $fallbackReference = '') {
    $clientName = trim((string)($values['client_name'] ?? ''));
    if ($clientName !== '') {
        return $clientName;
    }

    $contactName = trim((string)($values['contact_person'] ?? ''));
    if ($contactName !== '') {
        return $contactName;
    }

    $fullName = trim((string)($values['first_name'] ?? '') . ' ' . (string)($values['last_name'] ?? ''));
    if ($fullName !== '') {
        return $fullName;
    }

    return trim((string)$fallbackReference);
}

function executeUpdateById($db, $table, $idColumn, $idValue, $updateData, &$errorMessage = null) {
    if (empty($updateData)) {
        return true;
    }

    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
    $safeIdColumn = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$idColumn);
    if ($safeTable === '' || $safeIdColumn === '') {
        $errorMessage = 'Invalid table or key column.';
        return false;
    }

    $setParts = [];
    $params = [];
    $types = '';

    foreach ($updateData as $column => $value) {
        $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$column);
        if ($safeColumn === '') {
            continue;
        }

        $setParts[] = "`$safeColumn` = ?";
        $params[] = $value;
        $types .= 's';
    }

    if (empty($setParts)) {
        return true;
    }

    $params[] = intval($idValue);
    $types .= 'i';

    $sql = "UPDATE `$safeTable` SET " . implode(', ', $setParts) . " WHERE `$safeIdColumn` = ?";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        $errorMessage = $db->error;
        return false;
    }

    bindDynamicParams($stmt, $types, $params);
    $ok = $stmt->execute();
    if (!$ok) {
        $errorMessage = $stmt->error ?: 'Database update failed.';
    }
    $stmt->close();

    return $ok;
}

function deleteApplicationRowById($db, $table, $approvalId) {
    $approvalId = intval($approvalId);
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);

    if ($approvalId <= 0 || $safeTable === '' || !tableExists($db, $safeTable)) {
        return false;
    }

    $stmt = $db->prepare("DELETE FROM {$safeTable} WHERE approval_id = ?");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('i', $approvalId);
    $stmt->execute();
    $deleted = $stmt->affected_rows > 0;
    $stmt->close();

    return $deleted;
}

function isHeadOfficeSession() {
    $sessionRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
    $sessionDepartment = strtoupper(trim((string)($_SESSION['department'] ?? '')));
    $sessionBranch = strtoupper(trim((string)($_SESSION['branch'] ?? '')));

    return $sessionRole === 'admin'
        || $sessionDepartment === 'HEAD OFFICE'
        || in_array($sessionBranch, ['HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH'], true);
}

function recordApprovalStatusHistory($db, $existingApproval, $targetStatus, $reviewNotes, $reviewerId, $reviewedAt, $approvalTable = '') {
    $historyTable = myApplicationsHistoryTableForApprovalTable($approvalTable !== '' ? $approvalTable : ($existingApproval['source_table'] ?? 'client_approvals'));

    if (!tableExists($db, $historyTable)) {
        return;
    }

    $approvalId = intval($existingApproval['approval_id'] ?? 0);
    $clientId = intval($existingApproval['client_id'] ?? 0);
    $referenceCode = trim((string)($existingApproval['reference_code'] ?? ''));
    $previousStatus = strtolower(trim((string)($existingApproval['approval_status'] ?? 'pending')));

    if ($approvalId <= 0 || $clientId <= 0 || $referenceCode === '') {
        return;
    }

    if (!in_array($previousStatus, ['pending', 'approved', 'declined', 'resubmit'], true)) {
        $previousStatus = 'pending';
    }

    $safeTargetStatus = in_array($targetStatus, ['pending', 'approved', 'declined', 'resubmit'], true)
        ? $targetStatus
        : 'pending';
    $historyNotes = trim((string)$reviewNotes);
    $historyNotesOrNull = $historyNotes !== '' ? $historyNotes : null;
    $safeReviewerId = intval($reviewerId);
    $safeReviewedAt = trim((string)$reviewedAt);

    $stmt = $db->prepare(
        "INSERT INTO `{$historyTable}` (
            approval_id,
            client_id,
            reference_code,
            previous_status,
            new_status,
            review_notes,
            reviewed_by,
            reviewed_at
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return;
    }

    $stmt->bind_param(
        'iissssis',
        $approvalId,
        $clientId,
        $referenceCode,
        $previousStatus,
        $safeTargetStatus,
        $historyNotesOrNull,
        $safeReviewerId,
        $safeReviewedAt
    );

    $stmt->execute();
    $stmt->close();
}

function resolveResubmitEditUrl($row) {
    $clientType = strtolower(trim((string)($row['client_type'] ?? '')));
    $classification = strtolower(trim((string)($row['client_classification'] ?? 'client')));
    $referenceCode = trim((string)($row['reference_code'] ?? ''));

    if ($referenceCode === '') {
        return '';
    }

    if ($classification === 'agent') {
        return 'kyc-individual.php?classification=agent&resume_ref=' . rawurlencode($referenceCode);
    }

    if ($clientType === 'corporate') {
        return 'kyc-corporate.php?type=corporate&classification=client&resume_ref=' . rawurlencode($referenceCode);
    }

    if ($clientType === 'obligee') {
        return 'kyc-obligee.php?classification=client&resume_ref=' . rawurlencode($referenceCode);
    }

    return 'kyc-individual.php?classification=client&resume_ref=' . rawurlencode($referenceCode);
}

function myApplicationsQueueTables() {
    global $db;

    static $tables = null;
    if ($tables !== null) {
        return $tables;
    }

    $tables = [];
    if (tableExists($db, 'client_approvals')) {
        $tables[] = 'client_approvals';
    }

    return $tables;
}

function myApplicationsHistoryTableForApprovalTable($approvalTable) {
    return 'client_approval_status_history';
}

function myApplicationsBuildQueueQuery($approvalTable, $scope, array $extraWhereClauses = [], array $extraParams = [], $extraTypes = '', $includeHistory = true) {
    global $db;

    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$approvalTable);
    if ($safeTable === '' || !tableExists($db, $safeTable)) {
        return null;
    }

    $approvalColumns = getTableColumns($db, $safeTable);

    $whereClauses = [$scope['sql']];
    $params = $scope['params'];
    $types = $scope['types'];

    foreach ($extraWhereClauses as $clause) {
        $clauseText = trim((string)$clause);
        if ($clauseText !== '') {
            $whereClauses[] = $clauseText;
        }
    }

    if (!empty($extraParams)) {
        $params = array_merge($params, $extraParams);
        $types .= $extraTypes;
    }

    if (isset($approvalColumns['client_classification'])) {
        $whereClauses[] = "LOWER(COALESCE(NULLIF(TRIM(ca.client_classification), ''), 'client')) = 'client'";
    }

    $historySelectSql = "NULL AS previous_review_status,\n            NULL AS latest_reviewed_at";
    $historyJoinSql = '';

    if ($includeHistory) {
        $historyTable = myApplicationsHistoryTableForApprovalTable($safeTable);
        if (tableExists($db, $historyTable)) {
            $historyJoinSql = "\n        LEFT JOIN (\n            SELECT h.approval_id, h.previous_status, h.reviewed_at\n            FROM `{$historyTable}` h\n            INNER JOIN (\n                SELECT approval_id, MAX(history_id) AS latest_history_id\n                FROM `{$historyTable}`\n                GROUP BY approval_id\n            ) hx ON hx.latest_history_id = h.history_id\n        ) hs ON hs.approval_id = ca.approval_id";
            $historySelectSql = "hs.previous_status AS previous_review_status,\n            hs.reviewed_at AS latest_reviewed_at";
        }
    }

    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

    return [
        'sql' => "\n        SELECT\n            ca.*,\n            ru.full_name AS reviewed_by_name,\n            '{$safeTable}' AS source_table,\n            {$historySelectSql}\n        FROM `{$safeTable}` ca\n        {$scope['join_sql']}\n        LEFT JOIN users ru ON ca.reviewed_by = ru.user_id{$historyJoinSql}\n        {$whereSql}\n    ",
        'params' => $params,
        'types' => $types,
        'table' => $safeTable,
    ];
}

function myApplicationsFetchApprovalRecord($approvalId, $scope, $requestedTable = '') {
    global $db;

    $approvalId = intval($approvalId);
    if ($approvalId <= 0) {
        return null;
    }

    $tables = myApplicationsQueueTables();
    if ($requestedTable !== '') {
        $safeRequestedTable = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$requestedTable);
        $tables = in_array($safeRequestedTable, $tables, true) ? [$safeRequestedTable] : [];
    }

    foreach ($tables as $queueTable) {
        $queryInfo = myApplicationsBuildQueueQuery($queueTable, $scope, ['ca.approval_id = ?'], [$approvalId], 'i', true);
        if (!$queryInfo) {
            continue;
        }

        $stmt = $db->prepare($queryInfo['sql'] . "\n        LIMIT 1");
        if (!$stmt) {
            continue;
        }

        bindDynamicParams($stmt, $queryInfo['types'], $queryInfo['params']);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result instanceof mysqli_result ? $result->fetch_assoc() : null;
            $stmt->close();

            if ($row) {
                return $row;
            }
        } else {
            $stmt->close();
        }
    }

    return null;
}

function myApplicationsFetchClientRecord($clientId) {
    global $db;

    $clientId = intval($clientId);
    if ($clientId <= 0 || !tableExists($db, 'clients')) {
        return null;
    }

    $stmt = $db->prepare("SELECT * FROM `clients` WHERE client_id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $clientId);
    $row = null;

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result instanceof mysqli_result) {
            $row = $result->fetch_assoc();
        }
    }

    $stmt->close();

    return $row;
}

function myApplicationsScopeInfo() {
    global $db;

    static $scope = null;
    if ($scope !== null) {
        return $scope;
    }

    $approvalColumns = getTableColumns($db, 'client_approvals');
    $userColumns = getTableColumns($db, 'users');

    $hasApprovalBranch = isset($approvalColumns['submitted_by_branch']);
    $hasUserBranch = isset($userColumns['branch']);

    $branchJoinSql = $hasUserBranch ? ' LEFT JOIN users su ON ca.submitted_by = su.user_id' : '';

    if ($hasApprovalBranch && $hasUserBranch) {
        $branchExpr = "COALESCE(NULLIF(TRIM(ca.submitted_by_branch), ''), NULLIF(TRIM(su.branch), ''))";
    } elseif ($hasApprovalBranch) {
        $branchExpr = "NULLIF(TRIM(ca.submitted_by_branch), '')";
    } elseif ($hasUserBranch) {
        $branchExpr = "NULLIF(TRIM(su.branch), '')";
    } else {
        $branchExpr = '';
    }

    $scope = [
        'join_sql' => $branchJoinSql,
        'branch_expr' => $branchExpr,
        'can_scope_by_branch' => $branchExpr !== '',
    ];

    return $scope;
}

function myApplicationsScopeCondition() {
    $scope = myApplicationsScopeInfo();

    $currentBranch = strtoupper(trim((string)($_SESSION['branch'] ?? '')));
    if ($scope['can_scope_by_branch'] && $currentBranch !== '') {
        return [
            'sql' => "UPPER(COALESCE({$scope['branch_expr']}, '')) = ?",
            'params' => [$currentBranch],
            'types' => 's',
            'join_sql' => $scope['join_sql'],
        ];
    }

    return [
        'sql' => 'ca.submitted_by = ?',
        'params' => [intval($_SESSION['user_id'] ?? 0)],
        'types' => 'i',
        'join_sql' => $scope['join_sql'],
    ];
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

$currentRole = normalizeRole($_SESSION['role'] ?? '');
if ($currentRole !== 'kyc_officer' || isHeadOfficeSession()) {
    $response['message'] = 'Access denied';
    jsonExit($response, 403);
}

if (empty(myApplicationsQueueTables())) {
    $response['message'] = 'Client approvals table is not available. Please run database migrations.';
    jsonExit($response, 500);
}


$action = strtolower(trim((string)($_REQUEST['action'] ?? 'list')));
$userId = intval($_SESSION['user_id']);

if ($action === 'details' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $approvalId = intval($_GET['approval_id'] ?? 0);
    if ($approvalId <= 0) {
        $response['message'] = 'Invalid approval id.';
        jsonExit($response, 400);
    }

    $editableFieldLengths = getEditableFieldLengths();
    $desiredFields = array_keys($editableFieldLengths);
    $scope = myApplicationsScopeCondition();
    $requestedTable = trim((string)($_GET['source_table'] ?? ''));
    $row = myApplicationsFetchApprovalRecord($approvalId, $scope, $requestedTable);

    if (!$row) {
        $response['message'] = 'Application not found.';
        jsonExit($response, 404);
    }

    $status = strtolower(trim((string)($row['approval_status'] ?? 'pending')));
    $clientRow = myApplicationsFetchClientRecord(intval($row['client_id'] ?? 0));
    if (is_array($clientRow)) {
        foreach ($clientRow as $field => $value) {
            if (!array_key_exists($field, $row) || $row[$field] === null || $row[$field] === '') {
                $row[$field] = $value;
            }
        }
    }

    $credentials = [];
    foreach ($desiredFields as $field) {
        $credentials[$field] = isset($row[$field]) && $row[$field] !== null
            ? (string)$row[$field]
            : '';
    }

    if (($credentials['client_type'] ?? '') === '') {
        $credentials['client_type'] = (string)($row['client_type'] ?? '');
    }

    if (($credentials['client_name'] ?? '') === '' && isset($row['company_name']) && trim((string)$row['company_name']) !== '') {
        $credentials['client_name'] = (string)$row['company_name'];
    }

    $parsedBusinessAddress = parseBusinessAddressComponents((string)($credentials['business_address'] ?? ''));
    if (is_array($parsedBusinessAddress)) {
        $credentials['business_street'] = $parsedBusinessAddress['street'] ?? '';
        $credentials['business_barangay'] = $parsedBusinessAddress['barangay'] ?? '';
    }

    $response['success'] = true;
    $response['data'] = [
        'approval_id' => intval($row['approval_id'] ?? 0),
        'client_id' => intval($row['client_id'] ?? 0),
        'reference_code' => (string)($row['reference_code'] ?? ''),
        'approval_status' => $status,
        'admin_remarks' => (string)($row['review_notes'] ?? ''),
        'editable' => $status === 'resubmit',
        'credentials' => $credentials,
    ];

    jsonExit($response);
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload = $_POST;
    if (is_string($rawInput) && trim($rawInput) !== '') {
        $decoded = json_decode($rawInput, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $payload = $decoded;
        }
    }

    $approvalId = intval($payload['approval_id'] ?? 0);
    if ($approvalId <= 0) {
        $response['message'] = 'Invalid approval id.';
        jsonExit($response, 400);
    }

    $incomingFields = $payload['fields'] ?? [];
    if (!is_array($incomingFields)) {
        $response['message'] = 'Invalid fields payload.';
        jsonExit($response, 400);
    }

    $normalizedIncomingFields = [];
    foreach ($incomingFields as $field => $value) {
        $normalizedField = myApplicationsNormalizeIncomingFieldName($field);
        if ($normalizedField === '') {
            continue;
        }

        $normalizedIncomingFields[$normalizedField] = $value;
    }
    $incomingFields = $normalizedIncomingFields;

    $scope = myApplicationsScopeCondition();
    $requestedTable = trim((string)($payload['source_table'] ?? ''));
    $existingApproval = myApplicationsFetchApprovalRecord($approvalId, $scope, $requestedTable);

    if (!$existingApproval) {
        $response['message'] = 'Application not found.';
        jsonExit($response, 404);
    }

    $status = strtolower(trim((string)($existingApproval['approval_status'] ?? 'pending')));
    if ($status !== 'resubmit') {
        $response['message'] = 'Only resubmit applications can be edited.';
        jsonExit($response, 409);
    }

    $resubmittedAt = date('Y-m-d H:i:s');
    $resubmissionMarker = 'Updated by officer and resubmitted on ' . $resubmittedAt;

    $editableFieldLengths = getEditableFieldLengths();
    $dateFields = ['date_of_birth', 'spouse_birthdate'];
    $enumRules = [
        'client_type' => ['individual', 'corporate', 'obligee'],
        'gender' => ['male', 'female', 'other'],
        'business_type' => ['private', 'government'],
    ];

    $sanitized = [];
    foreach ($editableFieldLengths as $field => $maxLength) {
        if (!array_key_exists($field, $incomingFields)) {
            continue;
        }

        $rawValue = $incomingFields[$field];
        if (is_array($rawValue) || is_object($rawValue)) {
            continue;
        }

        $value = trim((string)$rawValue);

        if (in_array($field, $dateFields, true)) {
            $normalizedDate = normalizeNullableDate($value);
            if ($value !== '' && $normalizedDate === null) {
                $response['message'] = 'Invalid date format for ' . str_replace('_', ' ', $field) . '. Use YYYY-MM-DD.';
                jsonExit($response, 422);
            }
            $sanitized[$field] = $normalizedDate;
            continue;
        }

        if (isset($enumRules[$field])) {
            $lowerValue = strtolower($value);
            if ($lowerValue === '') {
                if ($field === 'client_type') {
                    continue;
                }
                $sanitized[$field] = null;
                continue;
            }

            if (!in_array($lowerValue, $enumRules[$field], true)) {
                $response['message'] = 'Invalid value for ' . str_replace('_', ' ', $field) . '.';
                jsonExit($response, 422);
            }

            $sanitized[$field] = $lowerValue;
            continue;
        }

        if ($value === '') {
            if (in_array($field, ['first_name', 'last_name'], true)) {
                $sanitized[$field] = '';
            } else {
                $sanitized[$field] = null;
            }
            continue;
        }

        $sanitized[$field] = substr($value, 0, intval($maxLength));
    }

    $composedBusinessAddress = buildBusinessAddressString(
        $sanitized['business_street'] ?? '',
        $sanitized['business_barangay'] ?? '',
        $sanitized['business_ctm'] ?? '',
        $sanitized['business_province'] ?? '',
        $sanitized['region'] ?? ''
    );
    if ($composedBusinessAddress !== '') {
        $sanitized['business_address'] = $composedBusinessAddress;
    }

    $clientColumns = getTableColumns($db, 'clients');
    $approvalTable = trim((string)($existingApproval['source_table'] ?? 'client_approvals'));
    $approvalColumns = getTableColumns($db, $approvalTable);

    $clientUpdate = [];
    foreach ($sanitized as $field => $value) {
        if (isset($clientColumns[$field])) {
            $clientUpdate[$field] = $value;
        }
    }

    if (isset($clientColumns['verification_status'])) {
        $clientUpdate['verification_status'] = 'pending';
    }
    if (isset($clientColumns['verification_date'])) {
        $clientUpdate['verification_date'] = null;
    }
    if (isset($clientColumns['verified_by'])) {
        $clientUpdate['verified_by'] = null;
    }
    if (isset($clientColumns['rejection_reason'])) {
        $clientUpdate['rejection_reason'] = null;
    }

    $effectiveClientType = strtolower(trim((string)($sanitized['client_type'] ?? $existingApproval['client_type'] ?? '')));
    if ($effectiveClientType === 'corporate') {
        $clientNameForCorporate = trim((string)($sanitized['client_name'] ?? ''));
        if ($clientNameForCorporate !== '' && isset($clientColumns['company_name'])) {
            $clientUpdate['company_name'] = $clientNameForCorporate;
        }

        $businessAddressForCorporate = trim((string)($sanitized['business_address'] ?? ''));
        if ($businessAddressForCorporate !== '' && isset($clientColumns['full_address'])) {
            $clientUpdate['full_address'] = $businessAddressForCorporate;
        }
    }

    $approvalUpdatable = [
        'client_type',
        'client_name',
        'first_name',
        'middle_name',
        'last_name',
        'contact_person',
        'mobile_phone',
        'office_phone',
        'email',
    ];

    $approvalUpdate = [];
    foreach ($approvalUpdatable as $field) {
        if (array_key_exists($field, $sanitized) && isset($approvalColumns[$field])) {
            $approvalUpdate[$field] = $sanitized[$field];
        }
    }

    if (isset($approvalColumns['approval_status'])) {
        $approvalUpdate['approval_status'] = 'pending';
    }
    if (isset($approvalColumns['reviewed_by'])) {
        $approvalUpdate['reviewed_by'] = null;
    }
    if (isset($approvalColumns['reviewed_at'])) {
        $approvalUpdate['reviewed_at'] = null;
    }
    if (isset($approvalColumns['approved_at'])) {
        $approvalUpdate['approved_at'] = null;
    }
    if (isset($approvalColumns['submitted_at'])) {
        // Refresh queue timestamp so the resubmitted record returns to the active head-office queue immediately.
        $approvalUpdate['submitted_at'] = $resubmittedAt;
    }
    if (isset($approvalColumns['review_notes'])) {
        $existingReviewNotes = trim((string)($existingApproval['review_notes'] ?? ''));
        $approvalUpdate['review_notes'] = $existingReviewNotes !== ''
            ? ($existingReviewNotes . "\n\n" . $resubmissionMarker)
            : $resubmissionMarker;
    }

    if (isset($approvalColumns['display_name'])) {
        $displayName = coalesceDisplayName(
            array_merge($existingApproval, $sanitized),
            trim((string)($existingApproval['reference_code'] ?? ''))
        );
        $approvalUpdate['display_name'] = $displayName;
    }

    $clientId = intval($existingApproval['client_id'] ?? 0);
    if ($clientId <= 0) {
        $clientUpdate = [];
    }

    $db->begin_transaction();

    $updateError = null;
    if (!empty($clientUpdate)) {
        $clientUpdated = executeUpdateById($db, 'clients', 'client_id', $clientId, $clientUpdate, $updateError);
        if (!$clientUpdated) {
            $db->rollback();
            $response['message'] = 'Failed to update client credentials: ' . ($updateError ?: 'Unknown database error');
            jsonExit($response, 500);
        }
    }

    if (!empty($approvalUpdate)) {
        $approvalUpdated = executeUpdateById($db, $approvalTable, 'approval_id', $approvalId, $approvalUpdate, $updateError);
        if (!$approvalUpdated) {
            $db->rollback();
            $response['message'] = 'Failed to update application credentials: ' . ($updateError ?: 'Unknown database error');
            jsonExit($response, 500);
        }
    }

    if ($clientId > 0 && tableExists($db, 'kyc_verifications')) {
        $kycColumns = getTableColumns($db, 'kyc_verifications');
        $kycSetParts = [];
        $kycParams = [];
        $kycTypes = '';

        if (isset($kycColumns['status'])) {
            $kycSetParts[] = "`status` = ?";
            $kycParams[] = 'submitted';
            $kycTypes .= 's';
        }

        if (isset($kycColumns['submitted_at'])) {
            $kycSetParts[] = "`submitted_at` = ?";
            $kycParams[] = $resubmittedAt;
            $kycTypes .= 's';
        }

        if (!empty($kycSetParts)) {
            $kycSql = "UPDATE `kyc_verifications` SET " . implode(', ', $kycSetParts) . " WHERE `client_id` = ?";
            $kycStmt = $db->prepare($kycSql);

            if (!$kycStmt) {
                $db->rollback();
                $response['message'] = 'Failed to prepare KYC status update: ' . $db->error;
                jsonExit($response, 500);
            }

            $kycParams[] = $clientId;
            $kycTypes .= 'i';
            bindDynamicParams($kycStmt, $kycTypes, $kycParams);

            if (!$kycStmt->execute()) {
                $kycError = $kycStmt->error ?: 'Unknown database error';
                $kycStmt->close();
                $db->rollback();
                $response['message'] = 'Failed to update KYC status: ' . $kycError;
                jsonExit($response, 500);
            }

            $kycStmt->close();
        }
    }

    recordApprovalStatusHistory(
        $db,
        $existingApproval,
        'pending',
        $resubmissionMarker,
        $userId,
        $resubmittedAt,
        $approvalTable
    );

    $db->commit();

    $response['success'] = true;
    $response['message'] = 'Submitted credentials were updated and resubmitted for review.';
    $response['data'] = ['approval_id' => $approvalId];
    jsonExit($response);
}

if ($action === 'delete_application_record' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $approvalId = intval($_POST['approval_id'] ?? 0);

    if ($approvalId <= 0) {
        $response['message'] = 'Invalid application id.';
        jsonExit($response, 400);
    }

    $scope = myApplicationsScopeCondition();
    $requestedTable = trim((string)($_POST['source_table'] ?? ''));
    $existing = myApplicationsFetchApprovalRecord($approvalId, $scope, $requestedTable);

    if (!$existing) {
        $response['message'] = 'Application record not found.';
        jsonExit($response, 404);
    }

    $approvalTable = trim((string)($existing['source_table'] ?? 'client_approvals'));
    if (!deleteApplicationRowById($db, $approvalTable, $approvalId)) {
        $response['message'] = 'Application record not found.';
        jsonExit($response, 404);
    }

    $response['success'] = true;
    $response['message'] = 'Application record deleted successfully';
    jsonExit($response);
}

if ($action !== 'list' || $_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response['message'] = 'Unsupported action.';
    jsonExit($response, 405);
}

$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = max(1, intval($_GET['pageSize'] ?? 10));

$search = trim((string)($_GET['search'] ?? ''));
$status = strtolower(trim((string)($_GET['status'] ?? '')));
$type = strtolower(trim((string)($_GET['type'] ?? '')));

$allowedStatuses = ['pending', 'approved', 'declined', 'resubmit', ''];
if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

$allowedTypes = ['individual', 'corporate', 'obligee', ''];
if (!in_array($type, $allowedTypes, true)) {
    $type = '';
}

$scope = myApplicationsScopeCondition();
$extraWhereClauses = [];
$filterParams = [];
$filterTypes = '';

if ($search !== '') {
    $searchLike = '%' . $search . '%';
    $extraWhereClauses[] = "(
        ca.reference_code LIKE ? OR
        ca.client_number LIKE ? OR
        ca.display_name LIKE ? OR
        ca.email LIKE ? OR
        ca.mobile_phone LIKE ? OR
        ca.office_phone LIKE ? OR
        ru.full_name LIKE ?
    )";

    for ($i = 0; $i < 7; $i++) {
        $filterParams[] = $searchLike;
        $filterTypes .= 's';
    }
}

if ($status !== '') {
    $extraWhereClauses[] = 'ca.approval_status = ?';
    $filterParams[] = $status;
    $filterTypes .= 's';
}

if ($type !== '') {
    $extraWhereClauses[] = 'ca.client_type = ?';
    $filterParams[] = $type;
    $filterTypes .= 's';
}

$queueTables = myApplicationsQueueTables();
if (empty($queueTables)) {
    $response['message'] = 'Approval queue tables are not available. Please run database migrations.';
    jsonExit($response, 500);
}

$queueSelectSqlParts = [];
$queueSelectParams = [];
$queueSelectTypes = '';
foreach ($queueTables as $queueTable) {
    $queryInfo = myApplicationsBuildQueueQuery($queueTable, $scope, $extraWhereClauses, $filterParams, $filterTypes, true);
    if (!$queryInfo) {
        continue;
    }

    $queueSelectSqlParts[] = $queryInfo['sql'];
    $queueSelectParams = array_merge($queueSelectParams, $queryInfo['params']);
    $queueSelectTypes .= $queryInfo['types'];
}

if (empty($queueSelectSqlParts)) {
    $response['message'] = 'Approval queue tables are not available. Please run database migrations.';
    jsonExit($response, 500);
}

$unionSql = implode("\nUNION ALL\n", $queueSelectSqlParts);
$offset = ($page - 1) * $pageSize;

$countQuery = "
    SELECT COUNT(*) AS total
    FROM (
{$unionSql}
    ) queue_union
";

$countStmt = $db->prepare($countQuery);
if (!$countStmt) {
    $response['message'] = 'Database error: ' . $db->error;
    jsonExit($response, 500);
}

$countStmtParams = $queueSelectParams;
$countStmtTypes = $queueSelectTypes;
bindDynamicParams($countStmt, $countStmtTypes, $countStmtParams);
$countExecuted = $countStmt->execute();
if (!$countExecuted) {
    $response['message'] = 'Failed to count applications: ' . ($countStmt->error ?: 'Unknown database error');
    $countStmt->close();
    jsonExit($response, 500);
}

$countResult = $countStmt->get_result();
$countRow = $countResult ? $countResult->fetch_assoc() : null;
$total = intval($countRow['total'] ?? 0);
$countStmt->close();

$historyEnabled = tableExists($db, 'client_approval_status_history');

$query = "
    SELECT *
    FROM (
{$unionSql}
    ) queue_union
    ORDER BY COALESCE(submitted_at, created_at) DESC, approval_id DESC
    LIMIT ? OFFSET ?
";

$stmt = $db->prepare($query);
if (!$stmt) {
    $response['message'] = 'Database error: ' . $db->error;
    jsonExit($response, 500);
}

$queryParams = $queueSelectParams;
$queryTypes = $queueSelectTypes;
$queryParams[] = $pageSize;
$queryParams[] = $offset;
$queryTypes .= 'ii';

bindDynamicParams($stmt, $queryTypes, $queryParams);
if (!$stmt->execute()) {
    $response['message'] = 'Query execution failed: ' . $stmt->error;
    jsonExit($response, 500);
}

$result = $stmt->get_result();
if (!$result instanceof mysqli_result) {
    $response['message'] = 'Failed to fetch applications result set.';
    $stmt->close();
    jsonExit($response, 500);
}

$rows = [];

while ($row = $result->fetch_assoc()) {
    $currentStatus = strtolower(trim((string)($row['approval_status'] ?? 'pending')));
    if ($currentStatus === '') {
        $currentStatus = 'pending';
    }

    $beforeStatus = strtolower(trim((string)($row['previous_review_status'] ?? '')));
    if ($beforeStatus === '') {
        $beforeStatus = 'pending';
    }

    $row['status_before_review'] = $beforeStatus;
    $row['status_after_review'] = $currentStatus;
    $row['admin_remarks'] = (string)($row['review_notes'] ?? '');
    $row['can_edit'] = $currentStatus === 'resubmit';
    $row['edit_url'] = $row['can_edit'] ? resolveResubmitEditUrl($row) : '';

    $rows[] = $row;
}

$stmt->close();

$response['success'] = true;
$response['data'] = $rows;
$response['total'] = $total;
$response['page'] = $page;
$response['pageSize'] = $pageSize;
$response['totalPages'] = intval(ceil($total / $pageSize));
$response['historyEnabled'] = $historyEnabled;
$response['filters'] = [
    'search' => $search,
    'status' => $status,
    'type' => $type
];

jsonExit($response);
?>