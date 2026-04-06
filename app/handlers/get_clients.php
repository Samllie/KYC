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

    $whereClauses = [];
    $params = [];

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

    if ($status !== '') {
        $whereClauses[] = 'c.verification_status = ?';
        $params[] = $status;
    }

    if ($type !== '') {
        $whereClauses[] = 'c.client_type = ?';
        $params[] = $type;
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

echo json_encode($response);
?>
