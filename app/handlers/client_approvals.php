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

function tableExistsByName($db, $tableName) {
    static $cache = [];

    $tableName = trim((string)$tableName);
    if ($tableName === '') {
        return false;
    }

    if (array_key_exists($tableName, $cache)) {
        return $cache[$tableName];
    }

    $safeTable = preg_replace('/[^a-z0-9_]/i', '', $tableName);
    if ($safeTable === '') {
        $cache[$tableName] = false;
        return false;
    }

    $result = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($safeTable) . "'");
    $cache[$tableName] = $result && $result->num_rows > 0;

    if ($result instanceof mysqli_result) {
        $result->free();
    }

    return $cache[$tableName];
}

function approvalsTableExists($db) {
    return tableExistsByName($db, 'client_approvals');
}

function agentApprovalsTableExists($db) {
    return tableExistsByName($db, 'agent_approvals');
}

function approvedAgentsTableExists($db) {
    return tableExistsByName($db, 'approved_agents');
}

function approvalStatusHistoryTableExists($db, $tableName = 'client_approval_status_history') {
    return tableExistsByName($db, $tableName);
}

function recordApprovalStatusHistory($db, $historyTableName, $existingApproval, $targetStatus, $reviewNotes, $reviewerId, $reviewedAt) {
    if (!approvalStatusHistoryTableExists($db, $historyTableName)) {
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

    $historyNotes = trim((string)$reviewNotes);
    $historyNotesOrNull = $historyNotes !== '' ? $historyNotes : null;

    $stmt = $db->prepare(
        "INSERT INTO {$historyTableName} (
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

    $safeTargetStatus = in_array($targetStatus, ['pending', 'approved', 'declined', 'resubmit'], true)
        ? $targetStatus
        : 'pending';
    $safeReviewerId = intval($reviewerId);
    $safeReviewedAt = trim((string)$reviewedAt);

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

function syncApprovedAgentRowFromClient($db, $clientId) {
    $clientId = intval($clientId);
    if ($clientId <= 0 || !approvedAgentsTableExists($db)) {
        return;
    }

    $sql = "
        INSERT INTO approved_agents (
            client_id,
            reference_code,
            client_number,
            client_type,
            agent_type,
            head_agent_name,
            agent_branch,
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
            c.agent_type,
            c.head_agent_name,
            c.agent_branch,
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
            agent_type = VALUES(agent_type),
            head_agent_name = VALUES(head_agent_name),
            agent_branch = VALUES(agent_branch),
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

function deleteApprovedAgentRowByClient($db, $clientId) {
    $clientId = intval($clientId);
    if ($clientId <= 0 || !approvedAgentsTableExists($db)) {
        return;
    }

    $stmt = $db->prepare("DELETE FROM approved_agents WHERE client_id = ?");
    if (!$stmt) {
        return;
    }

    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $stmt->close();
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

function normalizeCredentialText($value) {
    $text = trim((string)$value);
    if ($text === '') {
        return '';
    }

    $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
    $text = preg_replace('/\s+/u', ' ', $text);
    $text = trim($text);

    if ($text === '') {
        return '';
    }

    return function_exists('mb_strtolower')
        ? mb_strtolower($text, 'UTF-8')
        : strtolower($text);
}

function combineCredentialNameParts($parts) {
    $filteredParts = [];

    foreach ((array)$parts as $part) {
        $text = trim((string)$part);
        if ($text !== '') {
            $filteredParts[] = $text;
        }
    }

    if (empty($filteredParts)) {
        return '';
    }

    return trim(preg_replace('/\s+/u', ' ', implode(' ', $filteredParts)));
}

function collectClientSearchTerms($approval, $client, $kyc) {
    $searchTerms = [
        'names' => [],
        'emails' => [],
    ];
    $seenNames = [];
    $seenEmails = [];
    $approvalClientType = is_array($approval) ? ($approval['client_type'] ?? '') : '';
    $clientClientType = is_array($client) ? ($client['client_type'] ?? '') : '';
    $kycClientType = is_array($kyc) ? ($kyc['client_type'] ?? '') : '';
    $clientType = strtolower(trim((string)($approvalClientType ?: $clientClientType ?: $kycClientType)));
    $isCorporateLike = in_array($clientType, ['corporate', 'obligee'], true);

    $addName = function($label, $value) use (&$searchTerms, &$seenNames) {
        $text = trim((string)$value);
        if ($text === '') {
            return;
        }

        $normalized = normalizeCredentialText($text);
        if ($normalized === '' || isset($seenNames[$normalized])) {
            return;
        }

        $seenNames[$normalized] = true;
        $searchTerms['names'][] = [
            'label' => $label,
            'value' => $text,
            'normalized' => $normalized,
        ];
    };

    $addEmail = function($label, $value) use (&$searchTerms, &$seenEmails) {
        $text = strtolower(trim((string)$value));
        if ($text === '' || !filter_var($text, FILTER_VALIDATE_EMAIL) || isset($seenEmails[$text])) {
            return;
        }

        $seenEmails[$text] = true;
        $searchTerms['emails'][] = [
            'label' => $label,
            'value' => $text,
            'normalized' => $text,
        ];
    };

    if (is_array($approval)) {
        $addName('Approval display name', $approval['display_name'] ?? '');
        $addName('Approval client name', $approval['client_name'] ?? '');
        $addName('Approval contact person', $approval['contact_person'] ?? '');
        $addName('Approval full name', combineCredentialNameParts([
            $approval['first_name'] ?? '',
            $approval['middle_name'] ?? '',
            $approval['last_name'] ?? '',
            $approval['suffix'] ?? '',
        ]));
        $addEmail('Approval email', $approval['email'] ?? '');
    }

    if (is_array($client)) {
        $addName('Client display name', $client['client_name'] ?? '');
        $addName('Client contact person', $client['contact_person'] ?? '');
        $addName('Client full name', combineCredentialNameParts([
            $client['first_name'] ?? '',
            $client['middle_name'] ?? '',
            $client['last_name'] ?? '',
            $client['suffix'] ?? '',
        ]));
        $addEmail('Client email', $client['email'] ?? '');
    }

    if (is_array($kyc)) {
        if ($isCorporateLike) {
            $addName('KYC contact person', $kyc['occupation'] ?? '');
            $addName('KYC company', $kyc['company'] ?? '');
        }
        $addName('KYC full name', combineCredentialNameParts([
            $kyc['first_name'] ?? '',
            $kyc['middle_name'] ?? '',
            $kyc['last_name'] ?? '',
            $kyc['suffix'] ?? '',
        ]));
        $addEmail('KYC email', $kyc['email'] ?? '');
    }

    return $searchTerms;
}

function buildClientMatchReason($method, $sourceLabel) {
    $sourceLabel = trim((string)$sourceLabel);
    if ($sourceLabel === '') {
        $sourceLabel = 'submitted name';
    }

    switch ($method) {
        case 'exact_name':
            return 'Exact client name match against ' . $sourceLabel;
        case 'contains_name':
            return 'Client name overlap with ' . $sourceLabel;
        case 'exact_email':
            return 'Exact client email match against ' . $sourceLabel;
        case 'similar_name':
        default:
            return 'Closest client name similarity to ' . $sourceLabel;
    }
}

function combineClientRecordName($clientRow) {
    if (!is_array($clientRow)) {
        return '';
    }

    $candidateNames = [
        trim((string)($clientRow['display_name'] ?? '')),
        trim((string)($clientRow['client_name'] ?? '')),
        combineCredentialNameParts([
            $clientRow['first_name'] ?? '',
            $clientRow['middle_name'] ?? '',
            $clientRow['last_name'] ?? '',
            $clientRow['suffix'] ?? '',
        ]),
        trim((string)($clientRow['contact_person'] ?? '')),
        trim((string)($clientRow['company_name'] ?? '')),
    ];

    foreach ($candidateNames as $candidateName) {
        if ($candidateName !== '') {
            return $candidateName;
        }
    }

    return '';
}

function findMatchingClients($approval, $client, $kyc, $excludeClientId = 0, $excludeReferenceCode = '', $limit = 5) {
    $searchTerms = collectClientSearchTerms($approval, $client, $kyc);
    if (empty($searchTerms['names']) && empty($searchTerms['emails'])) {
        return [
            'items' => [],
            'summary' => [
                'count' => 0,
                'best_name' => '',
                'best_score' => 0,
                'best_method' => '',
            ],
        ];
    }

    $clients = fetchAll(
        "SELECT
            client_id,
            reference_code,
            client_number,
            client_type,
            client_classification,
            client_name,
            first_name,
            middle_name,
            last_name,
            suffix,
            contact_person,
            company_name,
            mobile_phone,
            office_phone,
            email,
            verification_status,
            submitted_at,
            created_at,
            updated_at
         FROM clients
         ORDER BY client_id ASC"
    );

    if (empty($clients)) {
        return [
            'items' => [],
            'summary' => [
                'count' => 0,
                'best_name' => '',
                'best_score' => 0,
                'best_method' => '',
            ],
        ];
    }

    $matches = [];
    $excludedClientId = intval($excludeClientId);
    $excludedReferenceCode = trim((string)$excludeReferenceCode);

    foreach ($clients as $clientRow) {
        $clientId = intval($clientRow['client_id'] ?? 0);
        $referenceCode = trim((string)($clientRow['reference_code'] ?? ''));

        if (($excludedClientId > 0 && $clientId === $excludedClientId)
            || ($excludedReferenceCode !== '' && $referenceCode !== '' && strcasecmp($referenceCode, $excludedReferenceCode) === 0)) {
            continue;
        }

        $displayName = combineClientRecordName($clientRow);
        $clientName = trim((string)($clientRow['client_name'] ?? ''));
        $contactPerson = trim((string)($clientRow['contact_person'] ?? ''));
        $companyName = trim((string)($clientRow['company_name'] ?? ''));
        $firstName = trim((string)($clientRow['first_name'] ?? ''));
        $middleName = trim((string)($clientRow['middle_name'] ?? ''));
        $lastName = trim((string)($clientRow['last_name'] ?? ''));
        $suffix = trim((string)($clientRow['suffix'] ?? ''));
        $clientEmail = strtolower(trim((string)($clientRow['email'] ?? '')));

        $candidateNames = array_values(array_unique(array_filter([
            normalizeCredentialText($displayName),
            normalizeCredentialText($clientName),
            normalizeCredentialText($contactPerson),
            normalizeCredentialText($companyName),
            normalizeCredentialText(combineCredentialNameParts([$firstName, $middleName, $lastName, $suffix])),
        ])));

        $bestMatch = [
            'client_id' => $clientId,
            'reference_code' => $referenceCode,
            'client_number' => trim((string)($clientRow['client_number'] ?? '')),
            'client_type' => trim((string)($clientRow['client_type'] ?? '')),
            'client_classification' => trim((string)($clientRow['client_classification'] ?? '')),
            'display_name' => $displayName,
            'client_name' => $clientName,
            'contact_person' => $contactPerson,
            'company_name' => $companyName,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'suffix' => $suffix,
            'email' => trim((string)($clientRow['email'] ?? '')),
            'mobile_phone' => trim((string)($clientRow['mobile_phone'] ?? '')),
            'office_phone' => trim((string)($clientRow['office_phone'] ?? '')),
            'verification_status' => trim((string)($clientRow['verification_status'] ?? '')),
            'submitted_at' => trim((string)($clientRow['submitted_at'] ?? '')),
            'match_score' => 0,
            'match_method' => '',
            'matched_source_label' => '',
            'matched_source_value' => '',
            'match_reason' => '',
        ];

        foreach ($searchTerms['names'] as $term) {
            $candidate = trim((string)($term['normalized'] ?? ''));
            if (empty($candidateNames) || $candidate === '') {
                continue;
            }

            $score = 0;
            $method = 'similar_name';

            foreach ($candidateNames as $candidateName) {
                if ($candidateName === '') {
                    continue;
                }

                if ($candidateName === $candidate) {
                    $score = 100;
                    $method = 'exact_name';
                    break;
                }

                if (strpos($candidateName, $candidate) !== false || strpos($candidate, $candidateName) !== false) {
                    $score = max($score, 95);
                    $method = 'contains_name';
                    continue;
                }

                similar_text($candidateName, $candidate, $percent);
                $score = max($score, intval(round($percent)));
            }

            if ($score > $bestMatch['match_score']) {
                $bestMatch['match_score'] = $score;
                $bestMatch['match_method'] = $method;
                $bestMatch['matched_source_label'] = trim((string)($term['label'] ?? ''));
                $bestMatch['matched_source_value'] = trim((string)($term['value'] ?? ''));
                $bestMatch['match_reason'] = buildClientMatchReason($method, $bestMatch['matched_source_label']);
            }

            if ($bestMatch['match_score'] >= 100) {
                break;
            }
        }

        if ($bestMatch['match_score'] < 100) {
            foreach ($searchTerms['emails'] as $term) {
                $candidate = trim((string)($term['normalized'] ?? ''));
                if ($clientEmail === '' || $candidate === '') {
                    continue;
                }

                if ($clientEmail === $candidate) {
                    $bestMatch['match_score'] = 100;
                    $bestMatch['match_method'] = 'exact_email';
                    $bestMatch['matched_source_label'] = trim((string)($term['label'] ?? ''));
                    $bestMatch['matched_source_value'] = trim((string)($term['value'] ?? ''));
                    $bestMatch['match_reason'] = buildClientMatchReason('exact_email', $bestMatch['matched_source_label']);
                    break;
                }
            }
        }

        if ($bestMatch['match_score'] > 0) {
            $matches[] = $bestMatch;
        }
    }

    usort($matches, function ($left, $right) {
        $scoreComparison = intval($right['match_score'] ?? 0) <=> intval($left['match_score'] ?? 0);
        if ($scoreComparison !== 0) {
            return $scoreComparison;
        }

        $verificationLeft = strtolower(trim((string)($left['verification_status'] ?? '')));
        $verificationRight = strtolower(trim((string)($right['verification_status'] ?? '')));
        if ($verificationLeft !== $verificationRight) {
            if ($verificationLeft === 'verified' || $verificationLeft === 'approved') return -1;
            if ($verificationRight === 'verified' || $verificationRight === 'approved') return 1;
        }

        return strcasecmp((string)($left['display_name'] ?? ''), (string)($right['display_name'] ?? ''));
    });

    $matches = array_slice($matches, 0, max(1, intval($limit)));
    $bestMatch = $matches[0] ?? null;

    return [
        'items' => $matches,
        'summary' => [
            'count' => count($matches),
            'best_name' => $bestMatch['display_name'] ?? '',
            'best_score' => intval($bestMatch['match_score'] ?? 0),
            'best_method' => $bestMatch['match_method'] ?? '',
        ],
    ];
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

$queueScope = strtolower(trim($_GET['queue'] ?? $_POST['queue'] ?? 'client'));
if (!in_array($queueScope, ['client', 'agent'], true)) {
    $queueScope = 'client';
}

$queueTable = $queueScope === 'agent' ? 'agent_approvals' : 'client_approvals';
$historyTable = $queueScope === 'agent' ? 'agent_approval_status_history' : 'client_approval_status_history';

if (!tableExistsByName($db, $queueTable)) {
    $response['message'] = ($queueScope === 'agent'
        ? 'Agent approvals table is not available. Please run database migrations.'
        : 'Client approvals table is not available. Please run database migrations.');
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $page = max(1, intval($_GET['page'] ?? 1));
    $pageSize = max(1, intval($_GET['pageSize'] ?? 10));

    $search = trim($_GET['search'] ?? '');
    $status = strtolower(trim($_GET['status'] ?? ''));
    $classification = strtolower(trim($_GET['classification'] ?? ''));
    $type = strtolower(trim($_GET['type'] ?? ''));
    $branch = trim($_GET['branch'] ?? '');

    $allowedStatuses = ['pending', 'approved', 'declined', 'resubmit', ''];
    if (!in_array($status, $allowedStatuses, true)) {
        $status = '';
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

    // Limit queue view to applications submitted by KYC officer accounts.
    $whereClauses[] = "LOWER(REPLACE(COALESCE(su.role, ''), '-', '_')) = 'kyc_officer'";

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
        FROM {$queueTable} ca
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

    $resubmissionJoinSql = '';
    $resubmissionSelectSql = "
            NULL AS officer_resubmitted_at,
            0 AS has_officer_updates,
    ";
    $resubmissionOrderSql = "
            COALESCE(ca.submitted_at, ca.created_at) DESC,
            ca.approval_id DESC
    ";

    if (approvalStatusHistoryTableExists($db, $historyTable)) {
        $resubmissionJoinSql = "
        LEFT JOIN (
            SELECT
                h.approval_id,
                MAX(h.reviewed_at) AS officer_resubmitted_at
            FROM {$historyTable} h
            LEFT JOIN users hu ON h.reviewed_by = hu.user_id
            WHERE h.previous_status = 'resubmit'
              AND h.new_status = 'pending'
              AND LOWER(REPLACE(COALESCE(hu.role, ''), '-', '_')) = 'kyc_officer'
            GROUP BY h.approval_id
        ) hr ON hr.approval_id = ca.approval_id
        ";

        $resubmissionSelectSql = "
            hr.officer_resubmitted_at,
            CASE
                WHEN ca.approval_status = 'pending' AND hr.officer_resubmitted_at IS NOT NULL THEN 1
                ELSE 0
            END AS has_officer_updates,
        ";

        $resubmissionOrderSql = "
            CASE
                WHEN ca.approval_status = 'pending' AND hr.officer_resubmitted_at IS NOT NULL THEN 0
                ELSE 1
            END ASC,
            COALESCE(hr.officer_resubmitted_at, ca.submitted_at, ca.created_at) DESC,
            ca.approval_id DESC
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
            $resubmissionSelectSql
            su.full_name AS submitted_by_name,
            su.branch AS submitted_by_branch,
            ru.full_name AS reviewed_by_name
        FROM {$queueTable} ca
        LEFT JOIN users su ON ca.submitted_by = su.user_id
        LEFT JOIN users ru ON ca.reviewed_by = ru.user_id
        $resubmissionJoinSql
        $whereSql
        ORDER BY
            FIELD(ca.approval_status, 'pending', 'resubmit', 'declined', 'approved'),
            $resubmissionOrderSql
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
         FROM {$queueTable} ca
         LEFT JOIN users su ON ca.submitted_by = su.user_id
                 WHERE LOWER(REPLACE(COALESCE(su.role, ''), '-', '_')) = 'kyc_officer'
                     AND su.branch IS NOT NULL AND TRIM(su.branch) <> ''
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
         FROM {$queueTable} ca
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

    $matchingCredentials = findMatchingClients($approval, $client, $kyc, $clientId, $approval['reference_code'] ?? '');

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
        'matching_credentials' => $matchingCredentials,
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
        "SELECT approval_id, client_id, reference_code, approval_status, client_classification FROM {$queueTable} WHERE approval_id = ?",
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
    $reviewerId = intval($_SESSION['user_id']);

    $updatePayload = [
        'approval_status' => $targetStatus,
        'review_notes' => $reviewNotes !== '' ? $reviewNotes : null,
        'reviewed_by' => $reviewerId,
        'reviewed_at' => $now,
        'approved_at' => $targetStatus === 'approved' ? $now : null,
    ];

    $updateResult = update($queueTable, $updatePayload, 'approval_id = ?', [$approvalId]);
    if (isset($updateResult['error'])) {
        $response['message'] = $updateResult['error'];
        echo json_encode($response);
        exit;
    }

    recordApprovalStatusHistory(
        $db,
        $historyTable,
        $existing,
        $targetStatus,
        $reviewNotes,
        $reviewerId,
        $now
    );

    $targetClientId = intval($existing['client_id']);
    $targetClassification = strtolower(trim((string)($existing['client_classification'] ?? 'client')));
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

            if ($targetClassification === 'agent') {
                syncApprovedAgentRowFromClient($db, $targetClientId);
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

            if ($targetClassification === 'agent') {
                deleteApprovedAgentRowByClient($db, $targetClientId);
            }
        } elseif ($targetStatus === 'resubmit') {
            $resubmitReason = $reviewNotes !== '' ? $reviewNotes : 'Resubmission requested by Head Office review';

            $stmt = $db->prepare(
                "UPDATE clients
                 SET verification_status = 'draft', verification_date = NULL, verified_by = ?, rejection_reason = ?
                 WHERE client_id = ?"
            );
            if ($stmt) {
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

            if ($targetClassification === 'agent') {
                deleteApprovedAgentRowByClient($db, $targetClientId);
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
