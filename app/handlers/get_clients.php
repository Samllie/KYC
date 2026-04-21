<?php
/**
 * Fetch Clients Data Handler
 * Returns paginated clients as JSON.
 */

header('Content-Type: application/json');
ini_set('display_errors', '0');

require_once '../config/db.php';
require_once __DIR__ . '/client_activity_utils.php';
session_start();

$response = ['success' => false, 'data' => []];

function hasColumn(mysqli $db, string $table, string $column): bool
{
    static $cache = [];

    $cacheKey = $table . '::' . $column;
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    try {
        $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $columnSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

        if ($tableSafe === '' || $columnSafe === '') {
            $cache[$cacheKey] = false;
            return false;
        }

        $stmt = $db->prepare(
            "SELECT 1
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND column_name = ?
             LIMIT 1"
        );
        if (!$stmt) {
            $cache[$cacheKey] = false;
            return false;
        }

        $stmt->bind_param('ss', $tableSafe, $columnSafe);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result instanceof mysqli_result && $result->num_rows > 0;
        $stmt->close();

        $cache[$cacheKey] = $exists;

        return $exists;
    } catch (Throwable $e) {
        $cache[$cacheKey] = false;
        return false;
    }
}

function tableExists(mysqli $db, string $table): bool
{
    static $cache = [];

    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    if ($tableSafe === '') {
        $cache[$table] = false;
        return false;
    }

    $result = $db->query("SHOW TABLES LIKE '$tableSafe'");
    $exists = $result instanceof mysqli_result && $result->num_rows > 0;

    if ($result instanceof mysqli_result) {
        $result->free();
    }

    $cache[$table] = $exists;
    return $exists;
}

function bindDynamicParams(mysqli_stmt $stmt, string $types, array $params): void
{
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

function fetchRows(mysqli $db, string $sql, string $types = '', array $params = []): array
{
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Database prepare failed: ' . $db->error);
    }

    bindDynamicParams($stmt, $types, $params);

    if (!$stmt->execute()) {
        $error = $stmt->error ?: 'Query execution failed';
        $stmt->close();
        throw new RuntimeException($error);
    }

    $result = $stmt->get_result();
    $rows = [];

    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    $stmt->close();
    return $rows;
}

function fetchSingle(mysqli $db, string $sql, string $types = '', array $params = []): ?array
{
    $rows = fetchRows($db, $sql, $types, $params);
    return $rows[0] ?? null;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

try {
    $page = max(1, intval($_GET['page'] ?? 1));
    $pageSize = max(1, intval($_GET['pageSize'] ?? 8));
    $exportAll = (($_GET['exportAll'] ?? '') === '1');

    $search = trim((string)($_GET['search'] ?? ''));
    $status = trim((string)($_GET['status'] ?? ''));
    $type = trim((string)($_GET['type'] ?? ''));
    $activity = strtolower(trim((string)($_GET['activity'] ?? '')));
    $sort = strtolower(trim((string)($_GET['sort'] ?? 'created_desc')));
    $branch = trim((string)($_GET['branch'] ?? ''));
    $classification = strtolower(trim((string)($_GET['classification'] ?? 'client')));
    if (!in_array($classification, ['client', 'agent'], true)) {
        $classification = 'client';
    }

    $allowedActivityFilters = ['', 'active', 'inactive', 'deactivated'];
    if (!in_array($activity, $allowedActivityFilters, true)) {
        $activity = '';
    }

    $allowedSorts = [
        'created_desc',
        'alphabetical_asc',
        'alphabetical_desc',
        'updated_asc',
        'updated_desc'
    ];
    if (!in_array($sort, $allowedSorts, true)) {
        $sort = 'created_desc';
    }

    $currentUserId = intval($_SESSION['user_id'] ?? 0);
    $currentUserRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
    $currentUserDepartment = strtoupper(trim((string)($_SESSION['department'] ?? '')));
    $currentUserBranch = trim((string)($_SESSION['branch'] ?? ''));
    $isHeadOfficeUser = $currentUserRole === 'admin'
        || $currentUserDepartment === 'HEAD OFFICE'
        || in_array(strtoupper($currentUserBranch), ['HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH'], true);

    $usersHasBranch = hasColumn($db, 'users', 'branch');
    $clientsHasClassification = hasColumn($db, 'clients', 'client_classification');
    $usingApprovedAgentsTable = $classification === 'agent' && tableExists($db, 'approved_agents');
    $usingAgentsTable = $classification === 'agent';
    $usingLegacyAgentsTable = $usingAgentsTable && !$usingApprovedAgentsTable && tableExists($db, 'agents');
    $usingApprovalQueue = !$usingApprovedAgentsTable && tableExists($db, $classification === 'agent' ? 'agent_approvals' : 'client_approvals');
    $activityTable = $usingApprovedAgentsTable ? 'approved_agents' : ($usingLegacyAgentsTable ? 'agents' : 'clients');
    $activityColumnsAvailable = clientActivityHasColumn($db, $activityTable, 'activity_status')
        && clientActivityHasColumn($db, $activityTable, 'activity_status_updated_at');

    if ($activityColumnsAvailable) {
        clientActivityRefreshTable($db, $activityTable);
    }

    $branchSqlExpr = "NULLIF(TRIM(su.branch), '')";
    if ($usersHasBranch && $usingApprovalQueue) {
        $branchSqlExpr = "COALESCE(NULLIF(TRIM(ca.submitted_by_branch), ''), NULLIF(TRIM(su.branch), ''))";
    }

    $branchFilterExpr = "UPPER(COALESCE($branchSqlExpr, ''))";
    $branchSelectExpr = "COALESCE($branchSqlExpr, 'UNASSIGNED')";

    $tableAlias = $usingAgentsTable ? 'a' : 'c';
    $baseTableSql = $usingAgentsTable
        ? ($usingApprovedAgentsTable ? 'approved_agents a' : 'agents a')
        : 'clients c';
    $agentTypeSelect = hasColumn($db, $activityTable, 'agent_type') ? "{$tableAlias}.agent_type" : "NULL AS agent_type";
    $headAgentNameSelect = hasColumn($db, $activityTable, 'head_agent_name') ? "{$tableAlias}.head_agent_name" : "NULL AS head_agent_name";
    $agentBranchSelect = hasColumn($db, $activityTable, 'agent_branch') ? "{$tableAlias}.agent_branch" : "NULL AS agent_branch";
    $approvalJoinSql = ($usingApprovalQueue && !$usingApprovedAgentsTable)
        ? " LEFT JOIN " . ($classification === 'agent' ? 'agent_approvals' : 'client_approvals') . " ca ON ca.reference_code = {$tableAlias}.reference_code"
        : '';

    $clientsClassificationSqlExpr = $clientsHasClassification
        ? "COALESCE(NULLIF(LOWER(TRIM(c.client_classification)), ''), 'client')"
        : "'client'";

    $whereClauses = [];
    $filterParams = [];
    $filterTypes = '';

    if (!$usingAgentsTable) {
        $whereClauses[] = $clientsClassificationSqlExpr . ' = ?';
        $filterParams[] = $classification;
        $filterTypes .= 's';
    }

    if ($usingApprovalQueue) {
        $whereClauses[] = "(ca.approval_status IS NULL OR ca.approval_status = 'approved')";
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
        $whereClauses[] = $tableAlias . '.verification_status = ?';
        $filterParams[] = $status;
        $filterTypes .= 's';
    }

    if ($type !== '') {
        $typeColumn = $usingAgentsTable && hasColumn($db, $activityTable, 'agent_type')
            ? $tableAlias . '.agent_type'
            : $tableAlias . '.client_type';
        $whereClauses[] = $typeColumn . ' = ?';
        $filterParams[] = $type;
        $filterTypes .= 's';
    }

    if ($activity !== '') {
        $activityFilterExpr = $activityColumnsAvailable
            ? "COALESCE(NULLIF(LOWER(TRIM({$tableAlias}.activity_status)), ''), 'active')"
            : "'active'";
        $whereClauses[] = $activityFilterExpr . ' = ?';
        $filterParams[] = $activity;
        $filterTypes .= 's';
    }

    if ($usersHasBranch) {
        if ($isHeadOfficeUser) {
            if ($branch !== '') {
                $whereClauses[] = $branchFilterExpr . ' = ?';
                $filterParams[] = strtoupper($branch);
                $filterTypes .= 's';
            }
        } else {
            if ($currentUserBranch === '') {
                $whereClauses[] = '1 = 0';
            } else {
                $whereClauses[] = $branchFilterExpr . ' = ?';
                $filterParams[] = strtoupper($currentUserBranch);
                $filterTypes .= 's';
            }
        }
    } elseif (!$isHeadOfficeUser) {
        if ($currentUserId <= 0) {
            $whereClauses[] = '1 = 0';
        } else {
            $whereClauses[] = $tableAlias . '.submitted_by = ?';
            $filterParams[] = $currentUserId;
            $filterTypes .= 'i';
        }
    }

    $whereSql = empty($whereClauses) ? '' : (' WHERE ' . implode(' AND ', $whereClauses));

    $countSql = "
        SELECT COUNT(*) AS total
        FROM {$baseTableSql}
        LEFT JOIN users su ON {$tableAlias}.submitted_by = su.user_id
        {$approvalJoinSql}
        {$whereSql}
    ";
    $countRow = fetchSingle($db, $countSql, $filterTypes, $filterParams);
    $totalClients = intval($countRow['total'] ?? 0);

    $submittedBranchSelect = $usersHasBranch ? $branchSelectExpr : "''";
    $contactPersonSelect = $usingAgentsTable ? 'NULL AS contact_person' : 'c.contact_person';
    $activitySelectSql = $activityColumnsAvailable
        ? "{$tableAlias}.activity_status, {$tableAlias}.activity_status_updated_at"
        : "NULL AS activity_status, NULL AS activity_status_updated_at";

    $displayNameExpr = $usingAgentsTable
        ? "COALESCE(NULLIF(TRIM(a.client_name), ''), NULLIF(TRIM(CONCAT(COALESCE(a.first_name, ''), ' ', COALESCE(a.last_name, ''))), ''), a.reference_code)"
        : "COALESCE(NULLIF(TRIM(c.client_name), ''), NULLIF(TRIM(CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, ''))), ''), c.reference_code)";

    $activityUpdatedSortExpr = $activityColumnsAvailable
        ? "COALESCE(NULLIF(TRIM({$tableAlias}.activity_status_updated_at), ''), {$tableAlias}.created_at)"
        : "{$tableAlias}.created_at";

    $orderBySql = "{$tableAlias}.created_at DESC";
    if ($sort === 'alphabetical_asc') {
        $orderBySql = "LOWER({$displayNameExpr}) ASC, {$tableAlias}.created_at DESC";
    } elseif ($sort === 'alphabetical_desc') {
        $orderBySql = "LOWER({$displayNameExpr}) DESC, {$tableAlias}.created_at DESC";
    } elseif ($sort === 'updated_asc') {
        $orderBySql = "{$activityUpdatedSortExpr} ASC, LOWER({$displayNameExpr}) ASC";
    } elseif ($sort === 'updated_desc') {
        $orderBySql = "{$activityUpdatedSortExpr} DESC, LOWER({$displayNameExpr}) ASC";
    }

    $listSql = "
        SELECT
            {$tableAlias}.client_id,
            {$tableAlias}.reference_code,
            {$tableAlias}.client_number,
            {$tableAlias}.client_name,
            {$contactPersonSelect},
            {$tableAlias}.first_name,
            {$tableAlias}.last_name,
            {$tableAlias}.client_type,
            {$agentTypeSelect},
            {$headAgentNameSelect},
            {$agentBranchSelect},
            {$tableAlias}.mobile_phone,
            {$tableAlias}.office_phone,
            {$tableAlias}.email,
            {$activitySelectSql},
            {$tableAlias}.verification_status,
            {$tableAlias}.submitted_by,
            {$tableAlias}.verified_by,
            {$tableAlias}.created_at,
            su.full_name AS submitted_by_name,
            {$submittedBranchSelect} AS submitted_by_branch,
            vu.full_name AS verified_by_name
        FROM {$baseTableSql}
        LEFT JOIN users su ON {$tableAlias}.submitted_by = su.user_id
        LEFT JOIN users vu ON {$tableAlias}.verified_by = vu.user_id
        {$approvalJoinSql}
        {$whereSql}
        ORDER BY {$orderBySql}
    ";

    $listParams = $filterParams;
    $listTypes = $filterTypes;

    if (!$exportAll) {
        $offset = ($page - 1) * $pageSize;
        $listSql .= ' LIMIT ? OFFSET ?';
        $listParams[] = $pageSize;
        $listParams[] = $offset;
        $listTypes .= 'ii';
    }

    $clients = fetchRows($db, $listSql, $listTypes, $listParams);
    $clients = array_map(static function (array $client): array {
        return array_merge($client, clientActivityBuildSnapshot($client));
    }, $clients);

    $availableBranches = [];
    if ($isHeadOfficeUser && $usersHasBranch) {
        if ($usingAgentsTable) {
            $branchExpr = $usingApprovalQueue
                ? "COALESCE(NULLIF(TRIM(ca.submitted_by_branch), ''), NULLIF(TRIM(su.branch), ''))"
                : "NULLIF(TRIM(su.branch), '')";

            $branchWhereClauses = [
                $branchExpr . ' IS NOT NULL'
            ];

            if ($usingApprovalQueue) {
                $branchWhereClauses[] = "(ca.approval_status IS NULL OR ca.approval_status = 'approved')";
            }

            $branchSql = "
                SELECT DISTINCT $branchExpr AS branch
                FROM {$baseTableSql}
                LEFT JOIN users su ON {$tableAlias}.submitted_by = su.user_id
                {$approvalJoinSql}
                WHERE " . implode(' AND ', $branchWhereClauses) . "
                ORDER BY branch ASC
            ";

            $branchRows = fetchRows($db, $branchSql);
        } else {
            $branchJoinSql = $usingApprovalQueue
                ? ' LEFT JOIN client_approvals ca ON ca.reference_code = c.reference_code'
                : '';

            $branchExpr = $usingApprovalQueue
                ? "COALESCE(NULLIF(TRIM(ca.submitted_by_branch), ''), NULLIF(TRIM(su.branch), ''))"
                : "NULLIF(TRIM(su.branch), '')";

            $branchWhereClauses = [
                $branchExpr . ' IS NOT NULL'
            ];
            $branchParams = [];
            $branchTypes = '';

            if ($clientsHasClassification) {
                $branchWhereClauses[] = $clientsClassificationSqlExpr . ' = ?';
                $branchParams[] = $classification;
                $branchTypes .= 's';
            } elseif ($classification !== 'client') {
                $branchWhereClauses[] = '1 = 0';
            }

            if ($usingApprovalQueue) {
                $branchWhereClauses[] = "(ca.approval_status IS NULL OR ca.approval_status = 'approved')";
            }

            $branchSql = "
                SELECT DISTINCT $branchExpr AS branch
                FROM clients c
                LEFT JOIN users su ON c.submitted_by = su.user_id
                {$branchJoinSql}
                WHERE " . implode(' AND ', $branchWhereClauses) . "
                ORDER BY branch ASC
            ";

            $branchRows = fetchRows($db, $branchSql, $branchTypes, $branchParams);
        }

        foreach ($branchRows as $branchRow) {
            $branchValue = trim((string)($branchRow['branch'] ?? ''));
            if ($branchValue !== '') {
                $availableBranches[] = $branchValue;
            }
        }
    }

    $totalPages = $exportAll ? 1 : (int)ceil($totalClients / $pageSize);

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
    $response['filters'] = [
        'search' => $search,
        'status' => $status,
        'type' => $type,
        'activity' => $activity,
        'sort' => $sort,
        'branch' => $branch,
        'classification' => $classification,
        'exportAll' => $exportAll
    ];
} catch (Throwable $e) {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Failed to load clients.';
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>