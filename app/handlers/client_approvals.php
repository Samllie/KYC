<?php
/**
 * Client Approvals Handler
 * Head Office review queue for incoming KYC client and agent submissions.
 */

header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

$response = [
    'success' => false,
    'message' => '',
    'data' => [],
];

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

function isHeadOfficeUser() {
    $currentUserRole = strtolower(trim($_SESSION['role'] ?? ''));
    $currentUserDepartment = strtoupper(trim($_SESSION['department'] ?? ''));
    $currentUserBranch = strtoupper(trim($_SESSION['branch'] ?? ''));

    return $currentUserRole === 'admin'
        || $currentUserDepartment === 'HEAD OFFICE'
        || in_array($currentUserBranch, ['HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH'], true);
}

function approvalsTableExists($db) {
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

function buildDocumentPreviewUrl($rawPath) {
    $path = trim((string)$rawPath);
    if ($path === '') {
        return '';
    }

    $normalized = str_replace('\\', '/', $path);

    if (preg_match('/^https?:\/\//i', $normalized)) {
        return $normalized;
    }

    $uploadsPos = stripos($normalized, 'uploads/');
    if ($uploadsPos !== false) {
        $normalized = substr($normalized, $uploadsPos);
    } elseif (strpos($normalized, '/uploads/') === 0) {
        $normalized = ltrim($normalized, '/');
    } else {
        $normalized = 'uploads/' . ltrim($normalized, '/');
    }

    return '../../' . $normalized;
}

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

if (!isHeadOfficeUser()) {
    http_response_code(403);
    $response['message'] = 'Access denied';
    echo json_encode($response);
    exit;
}

if (!approvalsTableExists($db)) {
    $response['message'] = 'Client approvals table is not available. Please run database migrations.';
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $page = max(1, intval($_GET['page'] ?? 1));
    $pageSize = max(1, intval($_GET['pageSize'] ?? 10));

    $search = trim($_GET['search'] ?? '');
    $status = strtolower(trim($_GET['status'] ?? 'pending'));
    $classification = strtolower(trim($_GET['classification'] ?? ''));
    $type = strtolower(trim($_GET['type'] ?? ''));
    $branch = trim($_GET['branch'] ?? '');

    $allowedStatuses = ['pending', 'approved', 'declined', 'resubmit', ''];
    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'pending';
    }

    $allowedClassifications = ['client', 'agent', ''];
    if (!in_array($classification, $allowedClassifications, true)) {
        $classification = '';
    }

    $allowedTypes = ['individual', 'corporate', 'obligee', ''];
    if (!in_array($type, $allowedTypes, true)) {
        $type = '';
    }

    $whereClauses = [];
    $filterParams = [];
    $filterTypes = '';

    if ($search !== '') {
        $searchLike = '%' . $search . '%';
        $whereClauses[] = "(
            ca.reference_code LIKE ? OR
            ca.client_number LIKE ? OR
            ca.display_name LIKE ? OR
            ca.email LIKE ? OR
            ca.mobile_phone LIKE ? OR
            ca.office_phone LIKE ? OR
            su.full_name LIKE ? OR
            su.branch LIKE ?
        )";

        for ($i = 0; $i < 8; $i++) {
            $filterParams[] = $searchLike;
            $filterTypes .= 's';
        }
    }

    if ($status !== '') {
        $whereClauses[] = 'ca.approval_status = ?';
        $filterParams[] = $status;
        $filterTypes .= 's';
    }

    if ($classification !== '') {
        $whereClauses[] = 'ca.client_classification = ?';
        $filterParams[] = $classification;
        $filterTypes .= 's';
    }

    if ($type !== '') {
        $whereClauses[] = 'ca.client_type = ?';
        $filterParams[] = $type;
        $filterTypes .= 's';
    }

    if ($branch !== '') {
        $whereClauses[] = 'su.branch = ?';
        $filterParams[] = $branch;
        $filterTypes .= 's';
    }

    $whereSql = '';
    if (!empty($whereClauses)) {
        $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
    }

    $offset = ($page - 1) * $pageSize;

    $countQuery = "
        SELECT COUNT(*) AS total
        FROM client_approvals ca
        LEFT JOIN users su ON ca.submitted_by = su.user_id
        $whereSql
    ";

    $countStmt = $db->prepare($countQuery);
    if (!$countStmt) {
        $response['message'] = 'Database error: ' . $db->error;
        echo json_encode($response);
        exit;
    }

    bindDynamicParams($countStmt, $filterTypes, $filterParams);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $countRow = $countResult->fetch_assoc();
    $total = intval($countRow['total'] ?? 0);

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
            ca.first_name,
            ca.middle_name,
            ca.last_name,
            ca.contact_person,
            ca.mobile_phone,
            ca.office_phone,
            ca.email,
            ca.approval_status,
            ca.review_notes,
            ca.submitted_at,
            ca.reviewed_at,
            ca.approved_at,
            ca.submitted_by,
            ca.reviewed_by,
            su.full_name AS submitted_by_name,
            su.branch AS submitted_by_branch,
            ru.full_name AS reviewed_by_name
        FROM client_approvals ca
        LEFT JOIN users su ON ca.submitted_by = su.user_id
        LEFT JOIN users ru ON ca.reviewed_by = ru.user_id
        $whereSql
        ORDER BY
            FIELD(ca.approval_status, 'pending', 'resubmit', 'declined', 'approved'),
            COALESCE(ca.submitted_at, ca.created_at) DESC,
            ca.approval_id DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $db->prepare($query);
    if (!$stmt) {
        $response['message'] = 'Database error: ' . $db->error;
        echo json_encode($response);
        exit;
    }

    $queryParams = $filterParams;
    $queryTypes = $filterTypes;
    $queryParams[] = $pageSize;
    $queryParams[] = $offset;
    $queryTypes .= 'ii';

    bindDynamicParams($stmt, $queryTypes, $queryParams);
    if (!$stmt->execute()) {
        $response['message'] = 'Query execution failed: ' . $stmt->error;
        echo json_encode($response);
        exit;
    }

    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $availableBranches = [];
    $branchesResult = $db->query(
        "SELECT DISTINCT su.branch
         FROM client_approvals ca
         LEFT JOIN users su ON ca.submitted_by = su.user_id
         WHERE su.branch IS NOT NULL AND TRIM(su.branch) <> ''
         ORDER BY su.branch ASC"
    );

    if ($branchesResult) {
        while ($branchRow = $branchesResult->fetch_assoc()) {
            $availableBranches[] = $branchRow['branch'];
        }
    }

    $response['success'] = true;
    $response['data'] = $rows;
    $response['total'] = $total;
    $response['page'] = $page;
    $response['pageSize'] = $pageSize;
    $response['totalPages'] = intval(ceil($total / $pageSize));
    $response['availableBranches'] = $availableBranches;
    echo json_encode($response);
    exit;
}

if ($action === 'get_application' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $approvalId = intval($_GET['approval_id'] ?? 0);

    if ($approvalId <= 0) {
        $response['message'] = 'Invalid approval ID';
        echo json_encode($response);
        exit;
    }

    $approval = fetchOne(
        "SELECT
            ca.*,
            su.full_name AS submitted_by_name,
            su.branch AS submitted_by_branch,
            ru.full_name AS reviewed_by_name
         FROM client_approvals ca
         LEFT JOIN users su ON ca.submitted_by = su.user_id
         LEFT JOIN users ru ON ca.reviewed_by = ru.user_id
         WHERE ca.approval_id = ?",
        [$approvalId]
    );

    if (!$approval) {
        $response['message'] = 'Application not found';
        echo json_encode($response);
        exit;
    }

    $clientId = intval($approval['client_id'] ?? 0);
    $client = $clientId > 0
        ? fetchOne("SELECT * FROM clients WHERE client_id = ?", [$clientId])
        : null;

    $kyc = $clientId > 0
        ? fetchOne(
            "SELECT *
             FROM kyc_verifications
             WHERE client_id = ?
             ORDER BY updated_at DESC, kyc_id DESC
             LIMIT 1",
            [$clientId]
        )
        : null;

    $documents = [];
    if ($clientId > 0) {
        $documents = fetchAll(
            "SELECT
                d.document_id,
                d.kyc_id,
                d.client_id,
                d.file_name,
                d.file_type,
                d.file_size,
                d.file_path,
                d.document_type,
                d.status,
                d.verification_notes,
                d.uploaded_at,
                u.full_name AS uploaded_by_name
             FROM documents d
             LEFT JOIN users u ON d.uploaded_by = u.user_id
             WHERE d.client_id = ?
             ORDER BY d.uploaded_at DESC, d.document_id DESC",
            [$clientId]
        );
    }

    if (is_array($documents)) {
        foreach ($documents as &$doc) {
            $doc['preview_url'] = buildDocumentPreviewUrl($doc['file_path'] ?? '');
        }
        unset($doc);
    }

    $allSubmittedData = [];
    if (is_array($approval)) {
        foreach ($approval as $key => $value) {
            $allSubmittedData['approval_' . $key] = $value;
        }
    }

    if (is_array($client)) {
        foreach ($client as $key => $value) {
            $allSubmittedData['client_' . $key] = $value;
        }
    }

    if (is_array($kyc)) {
        foreach ($kyc as $key => $value) {
            $allSubmittedData['kyc_' . $key] = $value;
        }
    }

    $response['success'] = true;
    $response['data'] = [
        'approval' => $approval,
        'client' => $client,
        'kyc' => $kyc,
        'all_submitted_data' => $allSubmittedData,
        'documents' => is_array($documents) ? $documents : [],
    ];
    echo json_encode($response);
    exit;
}

if (in_array($action, ['approve', 'decline', 'resubmit'], true) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $approvalId = intval($_POST['approval_id'] ?? 0);
    $reviewNotes = trim($_POST['review_notes'] ?? '');

    if ($approvalId <= 0) {
        $response['message'] = 'Invalid approval ID';
        echo json_encode($response);
        exit;
    }

    $existing = fetchOne(
        "SELECT approval_id, client_id FROM client_approvals WHERE approval_id = ?",
        [$approvalId]
    );

    if (!$existing) {
        $response['message'] = 'Approval record not found';
        echo json_encode($response);
        exit;
    }

    $targetStatus = $action === 'approve'
        ? 'approved'
        : ($action === 'decline' ? 'declined' : 'resubmit');

    $now = date('Y-m-d H:i:s');

    $updatePayload = [
        'approval_status' => $targetStatus,
        'review_notes' => $reviewNotes !== '' ? $reviewNotes : null,
        'reviewed_by' => intval($_SESSION['user_id']),
        'reviewed_at' => $now,
        'approved_at' => $targetStatus === 'approved' ? $now : null,
    ];

    $updateResult = update('client_approvals', $updatePayload, 'approval_id = ?', [$approvalId]);
    if (isset($updateResult['error'])) {
        $response['message'] = $updateResult['error'];
        echo json_encode($response);
        exit;
    }

    $targetClientId = intval($existing['client_id']);
    if ($targetClientId > 0) {
        if ($targetStatus === 'approved') {
            $stmt = $db->prepare(
                "UPDATE clients
                 SET verification_status = 'pending', verification_date = NULL, verified_by = NULL, rejection_reason = NULL
                 WHERE client_id = ?"
            );
            if ($stmt) {
                $stmt->bind_param('i', $targetClientId);
                $stmt->execute();
                $stmt->close();
            }

            $stmt = $db->prepare(
                "UPDATE kyc_verifications
                 SET status = 'submitted', submitted_at = COALESCE(submitted_at, ?)
                 WHERE client_id = ?"
            );
            if ($stmt) {
                $stmt->bind_param('si', $now, $targetClientId);
                $stmt->execute();
                $stmt->close();
            }
        } elseif ($targetStatus === 'declined') {
            $declineReason = $reviewNotes !== '' ? $reviewNotes : 'Declined by Head Office review';

            $stmt = $db->prepare(
                "UPDATE clients
                 SET verification_status = 'rejected', verification_date = ?, verified_by = ?, rejection_reason = ?
                 WHERE client_id = ?"
            );
            if ($stmt) {
                $reviewerId = intval($_SESSION['user_id']);
                $stmt->bind_param('sisi', $now, $reviewerId, $declineReason, $targetClientId);
                $stmt->execute();
                $stmt->close();
            }

            $stmt = $db->prepare(
                "UPDATE kyc_verifications
                 SET status = 'rejected'
                 WHERE client_id = ?"
            );
            if ($stmt) {
                $stmt->bind_param('i', $targetClientId);
                $stmt->execute();
                $stmt->close();
            }
        } elseif ($targetStatus === 'resubmit') {
            $resubmitReason = $reviewNotes !== '' ? $reviewNotes : 'Resubmission requested by Head Office review';

            $stmt = $db->prepare(
                "UPDATE clients
                 SET verification_status = 'draft', verification_date = NULL, verified_by = ?, rejection_reason = ?
                 WHERE client_id = ?"
            );
            if ($stmt) {
                $reviewerId = intval($_SESSION['user_id']);
                $stmt->bind_param('isi', $reviewerId, $resubmitReason, $targetClientId);
                $stmt->execute();
                $stmt->close();
            }

            $stmt = $db->prepare(
                "UPDATE kyc_verifications
                 SET status = 'draft'
                 WHERE client_id = ?"
            );
            if ($stmt) {
                $stmt->bind_param('i', $targetClientId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    $response['success'] = true;
    $response['message'] = 'Approval status updated successfully';
    echo json_encode($response);
    exit;
}

$response['message'] = 'Invalid action';
echo json_encode($response);
