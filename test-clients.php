<?php
require_once 'config/db.php';
session_start();
$_SESSION['user_id'] = 1; // Fake session for testing

// Test 1: Check database connection
echo "<h3>Database Connection Test</h3>";
echo "<p>DB_HOST: " . DB_HOST . "</p>";
echo "<p>DB_NAME: " . DB_NAME . "</p>";

// Test 2: Check if clients table exists
echo "<h3>Table Structure Test</h3>";
$tableCheck = $db->query("DESCRIBE clients LIMIT 5");
if ($tableCheck) {
    echo "<pre>";
    while ($row = $tableCheck->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color: red;'>Error: " . $db->error . "</p>";
}

// Test 3: Count total clients
echo "<h3>Client Count Test</h3>";
$countResult = $db->query("SELECT COUNT(*) as total FROM clients");
if ($countResult) {
    $row = $countResult->fetch_assoc();
    echo "<p>Total clients in database: <strong>" . $row['total'] . "</strong></p>";
} else {
    echo "<p style='color: red;'>Error: " . $db->error . "</p>";
}

// Test 4: Fetch all clients
echo "<h3>Fetch All Clients Test</h3>";
$result = $db->query("
    SELECT 
        client_id, 
        reference_code, 
        client_number,
        first_name, 
        last_name, 
        client_type, 
        mobile_phone, 
        email, 
        verification_status
    FROM clients 
    LIMIT 5
");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Ref Code</th><th>Name</th><th>Type</th><th>Email</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $fullName = $row['first_name'] . " " . $row['last_name'];
        echo "<tr>";
        echo "<td>" . $row['client_id'] . "</td>";
        echo "<td>" . $row['reference_code'] . "</td>";
        echo "<td>" . $fullName . "</td>";
        echo "<td>" . $row['client_type'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['verification_status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No clients found or query error: " . $db->error . "</p>";
}

// Test 5: Check fetchAll function
echo "<h3>fetchAll() Function Test</h3>";
$clients = fetchAll("SELECT * FROM clients LIMIT 5", []);
echo "<pre>";
echo "Result type: " . gettype($clients) . "\n";
echo "Count: " . count($clients) . "\n";
echo "First row: " . json_encode($clients[0] ?? 'EMPTY') . "\n";
echo "</pre>";
?>
