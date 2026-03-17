<?php
/**
 * Database & Login Diagnostic Script
 * Check if database is set up correctly and test login
 */

require_once 'config/db.php';

echo "<h2>KYC System - Diagnostic Report</h2>";

// Check database connection
echo "<h3>1. Database Connection</h3>";
if ($db->connect_error) {
    echo "<p style='color: red;'>❌ Failed to connect: " . $db->connect_error . "</p>";
    exit;
} else {
    echo "<p style='color: green;'>✓ Connected to: " . DB_NAME . "</p>";
}

// Check if users table exists
echo "<h3>2. Users Table</h3>";
$result = $db->query("SELECT COUNT(*) as count FROM users");
if (!$result) {
    echo "<p style='color: red;'>❌ Users table not found. Have you run database.sql?</p>";
    echo "<p>Error: " . $db->error . "</p>";
} else {
    $row = $result->fetch_assoc();
    echo "<p style='color: green;'>✓ Users table exists with " . $row['count'] . " records</p>";
    
    // List all users
    echo "<h4>Users in database:</h4>";
    $users = $db->query("SELECT user_id, email, full_name, password FROM users");
    if ($users) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Password Hash (first 20 chars)</th></tr>";
        while ($user = $users->fetch_assoc()) {
            $hashPreview = substr($user['password'], 0, 20) . "...";
            echo "<tr><td>" . $user['user_id'] . "</td><td>" . $user['email'] . "</td><td>" . $user['full_name'] . "</td><td><code>" . htmlspecialchars($hashPreview) . "</code></td></tr>";
        }
        echo "</table>";
    }
}

// Test password hashing
echo "<h3>3. Password Hash Test</h3>";
$testPassword = "password123";
$phpHash = hash('sha256', $testPassword);
echo "<p>Test Password: <code>" . $testPassword . "</code></p>";
echo "<p>PHP hash('sha256'): <code>" . $phpHash . "</code></p>";
echo "<p>Length: " . strlen($phpHash) . " characters</p>";

// Test actual login
echo "<h3>4. Login Test</h3>";
$testEmail = "juan@sterlingins.com";
$user = fetchOne("SELECT user_id, email, password, full_name FROM users WHERE email = ?", [$testEmail]);

if (!$user) {
    echo "<p style='color: orange;'>⚠ User not found: " . $testEmail . "</p>";
} else {
    echo "<p style='color: green;'>✓ User found: " . $user['full_name'] . "</p>";
    echo "<p>Stored Hash: <code>" . htmlspecialchars($user['password']) . "</code></p>";
    echo "<p>Input Hash:  <code>" . $phpHash . "</code></p>";
    echo "<p>Hashes Match (case-insensitive): " . (strtolower($phpHash) === strtolower($user['password']) ? '<span style="color: green;">✓ YES</span>' : '<span style="color: red;">✗ NO</span>') . "</p>";
    
    if (strtolower($phpHash) === strtolower($user['password'])) {
        echo "<p style='color: green;'><strong>✓ Login should work!</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>✗ Password hashes do not match</strong></p>";
        echo "<p><strong>Comparison:</strong></p>";
        echo "<p>Stored (lowercase): " . strtolower($user['password']) . "</p>";
        echo "<p>Input  (lowercase): " . strtolower($phpHash) . "</p>";
    }
}

// Summary
echo "<h3>5. Summary</h3>";
if ($row['count'] > 0 && $user && strtolower($phpHash) === strtolower($user['password'])) {
    echo "<p style='color: green;'>✓ Everything looks good! Login should work.</p>";
} else {
    echo "<p style='color: red;'>✗ There's an issue. Check details above.</p>";
}

// Close connection
$db->close();

?>
