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

function isHeadOfficeSession() {
    $sessionRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
    $sessionDepartment = strtoupper(trim((string)($_SESSION['department'] ?? '')));
    $sessionBranch = strtoupper(trim((string)($_SESSION['branch'] ?? '')));

    return $sessionRole === 'admin'
        || $sessionDepartment === 'HEAD OFFICE'
        || in_array($sessionBranch, ['HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH'], true);
}

function recordApprovalStatusHistory($db, $existingApproval, $targetStatus, $reviewNotes, $reviewerId, $reviewedAt) {
    if (!tableExists($db, 'client_approval_status_history')) {
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
        "INSERT INTO client_approval_status_history (
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

if (!tableExists($db, 'client_approvals')) {
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
    $clientColumns = getTableColumns($db, 'clients');
    $clientSelectParts = [];
    foreach ($desiredFields as $field) {
        if (isset($clientColumns[$field])) {
            $clientSelectParts[] = "c.`$field` AS `$field`";
        }
    }
    $clientSelectSql = empty($clientSelectParts)
        ? ''
        : (",\n        " . implode(",\n        ", $clientSelectParts));

    $query = "
        SELECT
            ca.approval_id,
            ca.client_id,
            ca.reference_code,
            ca.client_type,
            ca.client_classification,
            ca.approval_status,
            ca.review_notes$clientSelectSql
        FROM client_approvals ca
        LEFT JOIN clients c ON c.client_id = ca.client_id
        WHERE ca.approval_id = ?
          AND ca.submitted_by = ?
        LIMIT 1
    ";

    $stmt = $db->prepare($query);
    if (!$stmt) {
        $response['message'] = 'Database error: ' . $db->error;
        jsonExit($response, 500);
    }

    $stmt->bind_param('ii', $approvalId, $userId);
    if (!$stmt->execute()) {
        $response['message'] = 'Failed to load application details: ' . ($stmt->error ?: 'Unknown database error');
        $stmt->close();
        jsonExit($response, 500);
    }

    $result = $stmt->get_result();
    $row = $result instanceof mysqli_result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        $response['message'] = 'Application not found.';
        jsonExit($response, 404);
    }

    $status = strtolower(trim((string)($row['approval_status'] ?? 'pending')));
    $credentials = [];
    foreach ($desiredFields as $field) {
        $credentials[$field] = isset($row[$field]) && $row[$field] !== null
            ? (string)$row[$field]
            : '';
    }

    if (($credentials['client_type'] ?? '') === '') {
        $credentials['client_type'] = (string)($row['client_type'] ?? '');
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

    $ownershipSql = "
        SELECT
            approval_id,
            client_id,
            reference_code,
            approval_status,
            client_type,
            client_name,
            first_name,
            middle_name,
            last_name,
            contact_person,
            mobile_phone,
            office_phone,
                        email,
                        review_notes
        FROM client_approvals
        WHERE approval_id = ?
          AND submitted_by = ?
        LIMIT 1
    ";

    $ownershipStmt = $db->prepare($ownershipSql);
    if (!$ownershipStmt) {
        $response['message'] = 'Database error: ' . $db->error;
        jsonExit($response, 500);
    }

    $ownershipStmt->bind_param('ii', $approvalId, $userId);
    if (!$ownershipStmt->execute()) {
        $response['message'] = 'Failed to validate application ownership: ' . ($ownershipStmt->error ?: 'Unknown database error');
        $ownershipStmt->close();
        jsonExit($response, 500);
    }

    $ownershipResult = $ownershipStmt->get_result();
    $existingApproval = $ownershipResult instanceof mysqli_result ? $ownershipResult->fetch_assoc() : null;
    $ownershipStmt->close();

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

    $clientColumns = getTableColumns($db, 'clients');
    $approvalColumns = getTableColumns($db, 'client_approvals');

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
        $approvalUpdated = executeUpdateById($db, 'client_approvals', 'approval_id', $approvalId, $approvalUpdate, $updateError);
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
        $resubmittedAt
    );

    $db->commit();

    $response['success'] = true;
    $response['message'] = 'Submitted credentials were updated and resubmitted for review.';
    $response['data'] = ['approval_id' => $approvalId];
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

$whereClauses = ['ca.submitted_by = ?'];
$filterParams = [$userId];
$filterTypes = 'i';

if ($search !== '') {
    $searchLike = '%' . $search . '%';
    $whereClauses[] = "(
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
    $whereClauses[] = 'ca.approval_status = ?';
    $filterParams[] = $status;
    $filterTypes .= 's';
}

if ($type !== '') {
    $whereClauses[] = 'ca.client_type = ?';
    $filterParams[] = $type;
    $filterTypes .= 's';
}

$whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
$offset = ($page - 1) * $pageSize;

$countQuery = "
    SELECT COUNT(*) AS total
    FROM client_approvals ca
    LEFT JOIN users ru ON ca.reviewed_by = ru.user_id
    $whereSql
";

$countStmt = $db->prepare($countQuery);
if (!$countStmt) {
    $response['message'] = 'Database error: ' . $db->error;
    jsonExit($response, 500);
}

bindDynamicParams($countStmt, $filterTypes, $filterParams);
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
$historyJoinSql = '';
$historySelectSql = "
    NULL AS previous_review_status,
    NULL AS latest_reviewed_at,
";

if ($historyEnabled) {
    $historyJoinSql = "
        LEFT JOIN (
            SELECT h.approval_id, h.previous_status, h.reviewed_at
            FROM client_approval_status_history h
            INNER JOIN (
                SELECT approval_id, MAX(history_id) AS latest_history_id
                FROM client_approval_status_history
                GROUP BY approval_id
            ) hx ON hx.latest_history_id = h.history_id
        ) hs ON hs.approval_id = ca.approval_id
    ";

    $historySelectSql = "
        hs.previous_status AS previous_review_status,
        hs.reviewed_at AS latest_reviewed_at,
    ";
}

$query = "
    SELECT
        ca.approval_id,
        ca.client_id,
        ca.reference_code,
        ca.client_number,
        ca.client_classification,
        ca.client_type,
        ca.display_name,
        ca.client_name,
        ca.email,
        ca.mobile_phone,
        ca.office_phone,
        ca.approval_status,
        ca.review_notes,
        ca.submitted_at,
        ca.reviewed_at,
        ca.approved_at,
        ca.reviewed_by,
        ru.full_name AS reviewed_by_name,
        $historySelectSql
        ca.updated_at
    FROM client_approvals ca
    LEFT JOIN users ru ON ca.reviewed_by = ru.user_id
    $historyJoinSql
    $whereSql
    ORDER BY COALESCE(ca.submitted_at, ca.created_at) DESC, ca.approval_id DESC
    LIMIT ? OFFSET ?
";

$stmt = $db->prepare($query);
if (!$stmt) {
    $response['message'] = 'Database error: ' . $db->error;
    jsonExit($response, 500);
}

$queryParams = $filterParams;
$queryTypes = $filterTypes;
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