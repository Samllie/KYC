<?php
/**
 * Fetch Clients Data Handler
 * Returns paginated clients as JSON.
 */

header('Content-Type: application/json');
ini_set('display_errors', '0');
require_once '../config/db.php';
session_start();

$response = ['success' => false, 'data' => []];

function hasColumn($db, $table, $column) {
    try {
        $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
        $columnSafe = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$column);
        if ($tableSafe === '' || $columnSafe === '') {
            return false;
        }

        $stmt = $db->prepare("SHOW COLUMNS FROM `$tableSafe` LIKE ?");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $columnSafe);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result && $result->num_rows > 0;
    } catch (Throwable $e) {
        return false;
    }
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

function agentsTableExists($db) {
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

function clientApprovalsTableExists($db) {
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

// Check user session
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

try {
    $page = max(1, intval($_GET['page'] ?? 1));
    $pageSize = max(1, intval($_GET['pageSize'] ?? 8));
    $exportAll = (($_GET['exportAll'] ?? '') === '1');

    $search = trim($_GET['search'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $type = trim($_GET['type'] ?? '');
// Get pagination parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$pageSize = isset($_GET['pageSize']) ? intval($_GET['pageSize']) : 8;
$page = max(1, $page);  // Ensure page is at least 1
$pageSize = max(1, $pageSize);
$exportAll = isset($_GET['exportAll']) && $_GET['exportAll'] === '1';

// Get filters
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$type = trim($_GET['type'] ?? '');
$branch = trim($_GET['branch'] ?? '');
$classification = strtolower(trim($_GET['classification'] ?? 'client'));
if (!in_array($classification, ['client', 'agent'], true)) {
    $classification = 'client';
}

$currentUserRole = strtolower(trim($_SESSION['role'] ?? ''));
$currentUserDepartment = strtoupper(trim($_SESSION['department'] ?? ''));
$currentUserBranch = trim($_SESSION['branch'] ?? '');
$isHeadOfficeUser = $currentUserRole === 'admin'
    || $currentUserDepartment === 'HEAD OFFICE'
    || in_array(strtoupper($currentUserBranch), ['HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH'], true);

$usingAgentsTable = $classification === 'agent' && agentsTableExists($db);
$usingApprovalQueue = clientApprovalsTableExists($db);
$classificationSqlExpr = "COALESCE(NULLIF(LOWER(TRIM(c.client_classification)), ''), 'client')";

$availableBranches = [];
if ($isHeadOfficeUser) {
    if ($usingAgentsTable) {
        if ($usingApprovalQueue) {
            $availableBranchResult = $db->query(
                "SELECT DISTINCT su.branch
                 FROM agents a
                 LEFT JOIN users su ON a.submitted_by = su.user_id
                 LEFT JOIN client_approvals ca ON ca.reference_code = a.reference_code
                 WHERE su.branch IS NOT NULL AND TRIM(su.branch) <> ''
                   AND (ca.approval_status IS NULL OR ca.approval_status = 'approved')
                 ORDER BY su.branch ASC"
            );
        } else {
            $availableBranchResult = $db->query(
                "SELECT DISTINCT su.branch
                 FROM agents a
                 LEFT JOIN users su ON a.submitted_by = su.user_id
                 WHERE su.branch IS NOT NULL AND TRIM(su.branch) <> ''
                 ORDER BY su.branch ASC"
            );
        }
    } else {
        if ($usingApprovalQueue) {
            $availableBranchResult = $db->query(
                "SELECT DISTINCT su.branch
                 FROM clients c
                 LEFT JOIN users su ON c.submitted_by = su.user_id
                 LEFT JOIN client_approvals ca ON ca.reference_code = c.reference_code
                 WHERE su.branch IS NOT NULL AND TRIM(su.branch) <> ''
                   AND $classificationSqlExpr = '" . $classification . "'
                   AND (ca.approval_status IS NULL OR ca.approval_status = 'approved')
                 ORDER BY su.branch ASC"
            );
        } else {
            $availableBranchResult = $db->query(
                "SELECT DISTINCT su.branch
                 FROM clients c
                 LEFT JOIN users su ON c.submitted_by = su.user_id
                 WHERE su.branch IS NOT NULL AND TRIM(su.branch) <> ''
                   AND $classificationSqlExpr = '" . $classification . "'
                 ORDER BY su.branch ASC"
            );
        }
    }

    if ($availableBranchResult) {
        while ($branchRow = $availableBranchResult->fetch_assoc()) {
            $availableBranches[] = $branchRow['branch'];
        }
    }
}

    $whereClauses = [];
    $params = [];
$whereClauses = [];
$filterParams = [];
$filterTypes = '';

if (!$usingAgentsTable) {
    $whereClauses[] = "$classificationSqlExpr = ?";
    $filterParams[] = $classification;
    $filterTypes .= 's';
}

if ($usingApprovalQueue) {
    $whereClauses[] = "(ca.approval_status IS NULL OR ca.approval_status = 'approved')";
}

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
            $params[] = $searchLike;
        }
    }
if ($search !== '') {
    $searchLike = '%' . $search . '%';
    if ($usingAgentsTable) {
        $whereClauses[] = "(
            a.reference_code LIKE ? OR
            a.client_number LIKE ? OR
            a.client_name LIKE ? OR
            CONCAT(COALESCE(a.first_name, ''), ' ', COALESCE(a.last_name, '')) LIKE ? OR
            a.email LIKE ? OR
            a.mobile_phone LIKE ? OR
            a.office_phone LIKE ?
        )";

        for ($i = 0; $i < 7; $i++) {
            $filterParams[] = $searchLike;
            $filterTypes .= 's';
        }
    } else {
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
}

    if ($status !== '') {
        $whereClauses[] = 'c.verification_status = ?';
        $params[] = $status;
    }
if ($status !== '') {
    $whereClauses[] = $usingAgentsTable ? "a.verification_status = ?" : "c.verification_status = ?";
    $filterParams[] = $status;
    $filterTypes .= 's';
}

    if ($type !== '') {
        $whereClauses[] = 'c.client_type = ?';
        $params[] = $type;
    }
if ($type !== '') {
    $whereClauses[] = $usingAgentsTable ? "a.client_type = ?" : "c.client_type = ?";
    $filterParams[] = $type;
    $filterTypes .= 's';
}

if ($isHeadOfficeUser) {
    if ($branch !== '') {
        $whereClauses[] = "su.branch = ?";
        $filterParams[] = $branch;
        $filterTypes .= 's';
    }
} else {
    if ($currentUserBranch === '') {
        $whereClauses[] = "1 = 0";
    } else {
        $whereClauses[] = "su.branch = ?";
        $filterParams[] = $currentUserBranch;
        $filterTypes .= 's';
    }
}

    $whereSql = empty($whereClauses) ? '' : ('WHERE ' . implode(' AND ', $whereClauses));
    $offset = ($page - 1) * $pageSize;

    $countSql = "SELECT COUNT(*) AS total FROM clients c $whereSql";
    $countRow = fetchOne($countSql, $params);
    $totalClients = intval($countRow['total'] ?? 0);

    $usersHasBranch = hasColumn($db, 'users', 'branch');
    $branchSelect = $usersHasBranch ? 'su.branch' : "''";

    $listSql = "
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
            su.full_name AS submitted_by_name,
            $branchSelect AS submitted_by_branch,
            vu.full_name AS verified_by_name
        FROM clients c
        LEFT JOIN users su ON c.submitted_by = su.user_id
        LEFT JOIN users vu ON c.verified_by = vu.user_id
        $whereSql
        ORDER BY c.created_at DESC
    ";
$approvalJoinForAgents = $usingApprovalQueue
    ? "LEFT JOIN client_approvals ca ON ca.reference_code = a.reference_code"
    : "";
$approvalJoinForClients = $usingApprovalQueue
    ? "LEFT JOIN client_approvals ca ON ca.reference_code = c.reference_code"
    : "";

// First, get filtered total count
$countQuery = $usingAgentsTable
        ? "
                SELECT COUNT(*) as total
                FROM agents a
                LEFT JOIN users su ON a.submitted_by = su.user_id
                $approvalJoinForAgents
                $whereSql
            "
        : "
                SELECT COUNT(*) as total
                FROM clients c
                LEFT JOIN users su ON c.submitted_by = su.user_id
                $approvalJoinForClients
                $whereSql
            ";
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

// Get paginated clients with submitted-by account details and verifier name
$query = $usingAgentsTable
    ? "
        SELECT
            a.client_id,
            a.reference_code,
            a.client_number,
            a.client_name,
            NULL AS contact_person,
            a.first_name,
            a.last_name,
            a.client_type,
            a.mobile_phone,
            a.office_phone,
            a.email,
            a.verification_status,
            a.submitted_by,
            a.verified_by,
            a.created_at,
            su.full_name AS submitted_by_name,
            su.branch AS submitted_by_branch,
            vu.full_name AS verified_by_name
        FROM agents a
        LEFT JOIN users su ON a.submitted_by = su.user_id
        LEFT JOIN users vu ON a.verified_by = vu.user_id
                $approvalJoinForAgents
        $whereSql
        ORDER BY a.created_at DESC
      "
    : "
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
            su.branch as submitted_by_branch,
            vu.full_name as verified_by_name
        FROM clients c
        LEFT JOIN users su ON c.submitted_by = su.user_id
        LEFT JOIN users vu ON c.verified_by = vu.user_id
                $approvalJoinForClients
        $whereSql
        ORDER BY c.created_at DESC
      ";

    $listParams = $params;
    if (!$exportAll) {
        $listSql .= ' LIMIT ' . intval($pageSize) . ' OFFSET ' . intval($offset);
    }

    $clients = fetchAll($listSql, $listParams);

    // If join query still fails in this environment, fall back to a safe base query.
    if (!is_array($clients)) {
        $fallbackSql = "
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
                '' AS submitted_by_name,
                '' AS submitted_by_branch,
                '' AS verified_by_name
            FROM clients c
            $whereSql
            ORDER BY c.created_at DESC
        ";

        if (!$exportAll) {
            $fallbackSql .= ' LIMIT ' . intval($pageSize) . ' OFFSET ' . intval($offset);
        }

        $clients = fetchAll($fallbackSql, $params);
    }

    $totalPages = $exportAll ? 1 : (int)ceil($totalClients / $pageSize);

    $response['success'] = true;
    $response['data'] = is_array($clients) ? $clients : [];
    $response['count'] = count($response['data']);
    $response['total'] = $totalClients;
    $response['page'] = $page;
    $response['pageSize'] = $pageSize;
    $response['totalPages'] = $totalPages;
} catch (Throwable $e) {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Failed to load clients.';
    $response['error'] = $e->getMessage();
}
$response['success'] = true;
$response['data'] = $clients;
$response['count'] = count($clients);
$response['total'] = $totalClients;
$response['page'] = $page;
$response['pageSize'] = $pageSize;
$response['totalPages'] = $totalPages;
$response['isHeadOffice'] = $isHeadOfficeUser;
$response['availableBranches'] = $availableBranches;
$response['sourceTable'] = $usingAgentsTable ? 'agents' : 'clients';
$response['debug']['row_count'] = $result ? $result->num_rows : 0;
$response['filters'] = [
    'search' => $search,
    'status' => $status,
    'type' => $type,
    'branch' => $branch,
    'classification' => $classification,
    'exportAll' => $exportAll
];

echo json_encode($response);
?>
