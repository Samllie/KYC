<?php
require_once '../config/session.php';
require_once '../config/db.php';
require_once __DIR__ . '/client_activity_utils.php';

requireLogin();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

@ini_set('display_errors', '0');
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', '0');
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}

ignore_user_abort(true);
set_time_limit(0);
while (ob_get_level() > 0) {
    ob_end_flush();
}

$currentUserRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
$currentUserDepartment = strtoupper(trim((string)($_SESSION['department'] ?? '')));
$currentUserBranch = strtoupper(trim((string)($_SESSION['branch'] ?? '')));
$isHeadOfficeUser = $currentUserRole === 'admin'
    || $currentUserDepartment === 'HEAD OFFICE'
    || in_array($currentUserBranch, ['HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH'], true);

session_write_close();

function dashboardRecentActivityTableExists(mysqli $db, string $tableName): bool
{
    static $cache = [];

    $tableKey = trim($tableName);
    if ($tableKey === '') {
        return false;
    }

    if (array_key_exists($tableKey, $cache)) {
        return $cache[$tableKey];
    }

    $stmt = $db->prepare(
        'SELECT 1
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name = ?
         LIMIT 1'
    );
    if (!$stmt) {
        $cache[$tableKey] = false;
        return false;
    }

    $stmt->bind_param('s', $tableKey);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result instanceof mysqli_result && $result->num_rows > 0;
    $stmt->close();

    $cache[$tableKey] = $exists;
    return $exists;
}

function dashboardRecentActivityHasColumn(mysqli $db, string $tableName, string $columnName): bool
{
    static $cache = [];

    $cacheKey = trim($tableName) . '::' . trim($columnName);
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $tableKey = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
    $columnKey = preg_replace('/[^a-zA-Z0-9_]/', '', $columnName);
    if ($tableKey === '' || $columnKey === '') {
        $cache[$cacheKey] = false;
        return false;
    }

    $stmt = $db->prepare(
        'SELECT 1
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = ?
           AND column_name = ?
         LIMIT 1'
    );
    if (!$stmt) {
        $cache[$cacheKey] = false;
        return false;
    }

    $stmt->bind_param('ss', $tableKey, $columnKey);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result instanceof mysqli_result && $result->num_rows > 0;
    $stmt->close();

    $cache[$cacheKey] = $exists;
    return $exists;
}

function dashboardRecentActivitySnapshot(mysqli $db, bool $isHeadOfficeUser, string $currentUserBranch): array
{
    $clientsHasClassification = dashboardRecentActivityHasColumn($db, 'clients', 'client_classification');
    $clientsHasSubmittedBranch = dashboardRecentActivityHasColumn($db, 'clients', 'submitted_by_branch');
    $usersHasBranch = dashboardRecentActivityHasColumn($db, 'users', 'branch');
    $hasApprovalHistoryTable = dashboardRecentActivityTableExists($db, 'client_approval_status_history');

    $clientsScopeWhere = '';
    $clientsScopeParams = [];
    if (!$isHeadOfficeUser) {
        if ($currentUserBranch !== '') {
            $clientBranchExpr = $clientsHasSubmittedBranch
                ? "COALESCE(NULLIF(TRIM(c.submitted_by_branch), ''), NULLIF(TRIM(su.branch), ''))"
                : "NULLIF(TRIM(su.branch), '')";
            $clientsScopeWhere = " WHERE UPPER(TRIM(COALESCE($clientBranchExpr, ''))) = ?";
            $clientsScopeParams[] = $currentUserBranch;
        } else {
            return [
                'items' => [],
                'signature' => sha1(''),
                'latest_action_ts' => 0,
            ];
        }
    }

    $clientsClassificationExpr = $clientsHasClassification
        ? "COALESCE(NULLIF(LOWER(TRIM(c.client_classification)), ''), 'client')"
        : "'client'";
    $clientActivityBranchExpr = $clientsHasSubmittedBranch
        ? "COALESCE(NULLIF(TRIM(c.submitted_by_branch), ''), NULLIF(TRIM(su.branch), ''), 'UNASSIGNED')"
        : "COALESCE(NULLIF(TRIM(su.branch), ''), 'UNASSIGNED')";
    $approvalActivityBranchExpr = $usersHasBranch
        ? "COALESCE(NULLIF(TRIM(r.branch), ''), 'UNASSIGNED')"
        : "COALESCE(CAST(r.user_id AS CHAR), 'UNASSIGNED')";
    $wherePrefix = $clientsScopeWhere === '' ? 'WHERE' : ' AND';

    $clientRecentActivity = fetchAll("SELECT
        c.client_id,
        c.reference_code,
        c.client_type,
        c.client_classification,
        c.verification_status AS activity_status,
        COALESCE(NULLIF(c.client_name, ''), TRIM(CONCAT(c.first_name, ' ', c.last_name))) AS display_name,
        COALESCE(c.updated_at, c.submitted_at, c.created_at) AS action_time,
        'client' AS activity_kind,
        'Added' AS activity_label,
        COALESCE(su.full_name, 'System') AS activity_actor_name,
        COALESCE(su.full_name, 'System') AS submitted_by_name,
        {$clientActivityBranchExpr} AS submitted_by_branch
    FROM clients c
    LEFT JOIN users su ON su.user_id = c.submitted_by
    {$clientsScopeWhere}
    {$wherePrefix} 1=1
    AND {$clientsClassificationExpr} = 'client'
    ORDER BY COALESCE(c.updated_at, c.submitted_at, c.created_at) DESC
    LIMIT 6", $clientsScopeParams);

    $approvalHistoryRecentActivity = [];
    if ($hasApprovalHistoryTable) {
        $approvalHistoryScopeWhere = '';
        $approvalHistoryScopeParams = [];
        if (!$isHeadOfficeUser) {
            if ($currentUserBranch !== '') {
                $approvalHistoryScopeWhere = " WHERE UPPER(TRIM(COALESCE($approvalActivityBranchExpr, ''))) = ?";
                $approvalHistoryScopeParams[] = $currentUserBranch;
            } else {
                $approvalHistoryScopeWhere = ' WHERE 1 = 0';
            }
        }

        $approvalHistoryRecentActivity = fetchAll("SELECT
            h.client_id,
            h.reference_code,
            c.client_type,
            c.client_classification,
            h.new_status AS activity_status,
            COALESCE(NULLIF(c.client_name, ''), TRIM(CONCAT(c.first_name, ' ', c.last_name))) AS display_name,
            h.reviewed_at AS action_time,
            CASE h.new_status
                WHEN 'approved' THEN 'Approved'
                WHEN 'declined' THEN 'Declined'
                WHEN 'resubmit' THEN 'Resubmitted'
                ELSE 'Reviewed'
            END AS activity_label,
            'approval' AS activity_kind,
            COALESCE(r.full_name, 'System') AS activity_actor_name,
            COALESCE(r.full_name, 'System') AS submitted_by_name,
            {$approvalActivityBranchExpr} AS submitted_by_branch
        FROM client_approval_status_history h
        LEFT JOIN clients c ON c.client_id = h.client_id
        LEFT JOIN users r ON r.user_id = h.reviewed_by
        {$approvalHistoryScopeWhere}
        ORDER BY h.reviewed_at DESC
        LIMIT 6", $approvalHistoryScopeParams);
    }

    $recentActivity = array_merge($clientRecentActivity, $approvalHistoryRecentActivity);
    usort($recentActivity, static function (array $left, array $right): int {
        $leftTime = appParseTimestampLocal((string)($left['action_time'] ?? ''));
        $rightTime = appParseTimestampLocal((string)($right['action_time'] ?? ''));
        $leftTs = $leftTime instanceof DateTimeInterface ? $leftTime->getTimestamp() : 0;
        $rightTs = $rightTime instanceof DateTimeInterface ? $rightTime->getTimestamp() : 0;

        if ($leftTs === $rightTs) {
            return strcmp((string)($right['reference_code'] ?? ''), (string)($left['reference_code'] ?? ''));
        }

        return $rightTs <=> $leftTs;
    });
    $recentActivity = array_slice($recentActivity, 0, 6);

    $latestActionTs = 0;
    $signatureParts = [];
    foreach ($recentActivity as $row) {
        $actionTime = appParseTimestampLocal((string)($row['action_time'] ?? ''));
        if ($actionTime instanceof DateTimeInterface) {
            $latestActionTs = max($latestActionTs, $actionTime->getTimestamp());
        }

        $signatureParts[] = implode(':', [
            (string)($row['activity_kind'] ?? 'client'),
            (string)($row['activity_label'] ?? ''),
            (string)($row['client_id'] ?? ''),
            (string)($row['reference_code'] ?? ''),
            (string)($row['action_time'] ?? ''),
            (string)($row['submitted_by_branch'] ?? ''),
        ]);
    }

    return [
        'items' => $recentActivity,
        'signature' => sha1(implode('|', $signatureParts)),
        'latest_action_ts' => $latestActionTs,
    ];
}

$lastSignature = '';
$initialSnapshot = dashboardRecentActivitySnapshot($db, $isHeadOfficeUser, $currentUserBranch);
$lastSignature = (string)($initialSnapshot['signature'] ?? '');

echo "event: recent-activity\n";
echo 'data: ' . json_encode([
    'success' => true,
    'data' => $initialSnapshot,
]) . "\n\n";
@ob_flush();
flush();

$heartbeatIntervalSeconds = 15;
$pollIntervalSeconds = 5;
$lastHeartbeatAt = time();

while (!connection_aborted()) {
    sleep($pollIntervalSeconds);

    $snapshot = dashboardRecentActivitySnapshot($db, $isHeadOfficeUser, $currentUserBranch);
    $signature = (string)($snapshot['signature'] ?? '');

    if ($signature !== $lastSignature) {
        $lastSignature = $signature;
        echo "event: recent-activity\n";
        echo 'data: ' . json_encode([
            'success' => true,
            'data' => $snapshot,
        ]) . "\n\n";
        @ob_flush();
        flush();
        $lastHeartbeatAt = time();
        continue;
    }

    if ((time() - $lastHeartbeatAt) >= $heartbeatIntervalSeconds) {
        echo ": heartbeat " . time() . "\n\n";
        @ob_flush();
        flush();
        $lastHeartbeatAt = time();
    }
}
