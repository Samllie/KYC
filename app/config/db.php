<?php
/**
 * Database Configuration and Connection
 * KYC System - Sterling Insurance Company
 */

date_default_timezone_set('Asia/Taipei');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Default XAMPP password (empty)
define('DB_NAME', 'kyc_system');
define('DB_PORT', 3306);

// Create a new connection
try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    // Check connection
    if ($db->connect_error) {
        die("Connection failed: " . $db->connect_error);
    }
    
    // Set charset to UTF-8
    $db->set_charset("utf8mb4");

    // Keep MySQL timestamps aligned with the PHP timezone used by the app.
    $db->query("SET time_zone = '" . $db->real_escape_string(date('P')) . "'");
    
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

function appTimestampTimezone(): DateTimeZone {
    static $timezone = null;

    if ($timezone instanceof DateTimeZone) {
        return $timezone;
    }

    $timezoneName = date_default_timezone_get() ?: 'Asia/Taipei';
    try {
        $timezone = new DateTimeZone($timezoneName);
    } catch (Throwable $e) {
        $timezone = new DateTimeZone('Asia/Taipei');
    }

    return $timezone;
}

function appParseTimestampLocal(?string $value): ?DateTimeImmutable {
    $trimmed = trim((string)$value);
    if ($trimmed === '') {
        return null;
    }

    $timezone = appTimestampTimezone();
    $normalized = preg_replace('/\.(\d+)$/', '', str_replace('T', ' ', $trimmed)) ?? $trimmed;

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized)) {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $normalized, $timezone);
        return $date instanceof DateTimeImmutable ? $date : null;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $normalized)) {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $normalized, $timezone);
        return $date instanceof DateTimeImmutable ? $date : null;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $normalized)) {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $normalized, $timezone);
        return $date instanceof DateTimeImmutable ? $date : null;
    }

    try {
        return (new DateTimeImmutable($trimmed, $timezone))->setTimezone($timezone);
    } catch (Throwable $e) {
        return null;
    }
}

function appFormatTimestampLocal(?string $value, string $format = 'M j, Y g:i A'): string {
    $date = appParseTimestampLocal($value);
    return $date ? $date->format($format) : 'N/A';
}

function appFormatDateLocal(?string $value, string $format = 'M j, Y'): string {
    $date = appParseTimestampLocal($value);
    return $date ? $date->format($format) : 'N/A';
}

function appRelativeTimeLocal(?string $value, ?DateTimeInterface $now = null): string {
    $date = appParseTimestampLocal($value);
    if (!$date) {
        return 'just now';
    }

    $current = $now instanceof DateTimeInterface
        ? DateTimeImmutable::createFromInterface($now)
        : new DateTimeImmutable('now', appTimestampTimezone());

    $diff = $current->getTimestamp() - $date->getTimestamp();
    if ($diff < 60) {
        return 'just now';
    }

    if ($diff < 3600) {
        return floor($diff / 60) . ' min ago';
    }

    if ($diff < 86400) {
        return floor($diff / 3600) . ' hr ago';
    }

    return floor($diff / 86400) . ' day ago';
}

/**
 * Helper function to execute queries
 */
function executeQuery($query, $params = []) {
    global $db;
    
    $stmt = $db->prepare($query);
    
    if (!$stmt) {
        return ['error' => 'Query preparation failed: ' . $db->error];
    }
    
    if (!empty($params)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) $types .= 'i';
            elseif (is_float($param)) $types .= 'd';
            else $types .= 's';
        }
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        return ['error' => 'Query execution failed: ' . $stmt->error];
    }
    
    return $stmt;
}

/**
 * Helper function to fetch all results
 */
function fetchAll($query, $params = []) {
    $result = executeQuery($query, $params);
    
    if (is_array($result) && isset($result['error'])) {
        return [];
    }
    
    $resultSet = $result->get_result();
    $rows = [];
    
    while ($row = $resultSet->fetch_assoc()) {
        $rows[] = $row;
    }
    
    return $rows;
}

/**
 * Helper function to fetch single row
 */
function fetchOne($query, $params = []) {
    $result = executeQuery($query, $params);
    
    if (is_array($result) && isset($result['error'])) {
        return null;
    }
    
    $resultSet = $result->get_result();
    return $resultSet->fetch_assoc();
}

/**
 * Generate unique reference code
 * Format: REF - 000000
 */
function generateUniqueReferenceCode() {
    global $db;
    
    $query = "
        SELECT COALESCE(MAX(ref_number), 0) AS max_ref_number
        FROM (
            SELECT CAST(SUBSTRING(TRIM(reference_code), 7) AS UNSIGNED) AS ref_number
            FROM clients
            WHERE UPPER(TRIM(reference_code)) LIKE 'REF - %'

            UNION ALL

            SELECT CAST(SUBSTRING(TRIM(reference_code), 7) AS UNSIGNED) AS ref_number
            FROM client_approvals
            WHERE UPPER(TRIM(reference_code)) LIKE 'REF - %'

            UNION ALL

            SELECT CAST(SUBSTRING(
                COALESCE(NULLIF(TRIM(ref_code), ''), NULLIF(TRIM(reference_code), '')),
                7
            ) AS UNSIGNED) AS ref_number
            FROM kyc_verifications
            WHERE UPPER(COALESCE(NULLIF(TRIM(ref_code), ''), NULLIF(TRIM(reference_code), ''))) LIKE 'REF - %'
        ) AS reference_numbers
    ";

    $result = $db->query($query);
    $nextNumber = 1;

    if ($result instanceof mysqli_result) {
        $row = $result->fetch_assoc();
        $nextNumber = intval($row['max_ref_number'] ?? 0) + 1;
        $result->free();
    }

    if ($nextNumber > 999999) {
        throw new RuntimeException('Reference code limit reached.');
    }

    return sprintf('REF - %06d', $nextNumber);
}

/**
 * Helper function to insert data
 */
function insert($table, $data) {
    global $db;
    
    $columns = implode(',', array_keys($data));
    $placeholders = implode(',', array_fill(0, count($data), '?'));
    
    $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $stmt = $db->prepare($query);
    
    if (!$stmt) {
        return ['error' => 'Insert preparation failed: ' . $db->error];
    }
    
    // Build proper type string and convert values to references
    $types = str_repeat('s', count($data));
    $values = array_values($data);
    
    // Create reference array for bind_param
    $refs = [];
    foreach ($values as &$val) {
        $refs[] = &$val;
    }
    
    // Bind parameters with types
    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
    
    if (!$stmt->execute()) {
        return ['error' => 'Insert failed: ' . $stmt->error];
    }
    
    return ['success' => true, 'id' => $stmt->insert_id];
}

/**
 * Helper function to update data
 */
function update($table, $data, $where, $whereParams = []) {
    global $db;
    
    $set = [];
    foreach ($data as $key => $value) {
        $set[] = "$key = ?";
    }
    $setClause = implode(',', $set);
    
    $query = "UPDATE $table SET $setClause WHERE $where";
    $stmt = $db->prepare($query);
    
    if (!$stmt) {
        return ['error' => 'Update preparation failed: ' . $db->error];
    }
    
    $params = array_merge(array_values($data), $whereParams);
    $types = str_repeat('s', count($params));
    
    // Create reference array for bind_param
    $refs = [];
    foreach ($params as &$val) {
        $refs[] = &$val;
    }
    
    // Bind parameters with types
    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
    
    if (!$stmt->execute()) {
        return ['error' => 'Update failed: ' . $stmt->error];
    }
    
    return ['success' => true];
}

/**
 * Close database connection
 */
function closeDB() {
    global $db;
    $db->close();
}

// Automatically close connection on script exit
register_shutdown_function('closeDB');
?>
