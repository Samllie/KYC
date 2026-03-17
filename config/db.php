<?php
/**
 * Database Configuration and Connection
 * KYC System - Sterling Insurance Company
 */

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
    
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
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
    
    $stmt->bind_param(str_repeat('s', count($data)), ...array_values($data));
    
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
    $stmt->bind_param($types, ...$params);
    
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
