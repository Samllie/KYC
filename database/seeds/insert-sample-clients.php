<?php
require_once 'config/db.php';

// Insert sample clients for testing
$sampleClients = [
    [
        'reference_code' => 'REF-20260317-00001',
        'client_number' => 'CN-1710643200',
        'client_type' => 'individual',
        'first_name' => 'Juan',
        'middle_name' => 'Santos',
        'last_name' => 'Dela Cruz',
        'date_of_birth' => '1990-05-15',
        'email' => 'juan@sterlingins.com',
        'mobile_phone' => '+63 912 345 6789',
        'occupation' => 'Engineer',
        'full_address' => '123 Main St, Manila',
        'verification_status' => 'verified'
    ],
    [
        'reference_code' => 'REF-20260317-00002',
        'client_number' => 'CN-1710643201',
        'client_type' => 'individual',
        'first_name' => 'Maria',
        'middle_name' => 'Garcia',
        'last_name' => 'Santos',
        'date_of_birth' => '1995-08-20',
        'email' => 'maria@example.com',
        'mobile_phone' => '+63 921 654 3210',
        'occupation' => 'Doctor',
        'full_address' => '456 Oak Ave, Manila',
        'verification_status' => 'pending'
    ],
    [
        'reference_code' => 'REF-20260317-00003',
        'client_number' => 'CN-1710643202',
        'client_type' => 'corporate',
        'first_name' => 'ABC',
        'middle_name' => '',
        'last_name' => 'Corporation',
        'date_of_birth' => '2015-01-01',
        'email' => 'info@abccorp.com',
        'mobile_phone' => '+63 2 8123-4567',
        'occupation' => 'Business',
        'full_address' => '789 Corporate Blvd, Manila',
        'verification_status' => 'verified'
    ]
];

echo "<h2>Inserting Sample Clients</h2>";

foreach ($sampleClients as $client) {
    $result = insert('clients', $client);
    
    if (isset($result['success']) && $result['success']) {
        echo "<p style='color: green;'>✓ Inserted: " . $client['first_name'] . " " . $client['last_name'] . " (ID: " . $result['id'] . ")</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed: " . $client['first_name'] . " " . $client['last_name'] . " - " . ($result['error'] ?? 'Unknown error') . "</p>";
    }
}

echo "<h2>Verify Insertion</h2>";
$clients = fetchAll("SELECT client_id, reference_code, first_name, last_name, email, verification_status FROM clients ORDER BY created_at DESC LIMIT 10", []);

if (!empty($clients)) {
    echo "<p><strong>Total clients in database: " . count($clients) . "</strong></p>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Ref Code</th><th>Name</th><th>Email</th><th>Status</th></tr>";
    foreach ($clients as $client) {
        echo "<tr>";
        echo "<td>" . $client['client_id'] . "</td>";
        echo "<td>" . $client['reference_code'] . "</td>";
        echo "<td>" . $client['first_name'] . " " . $client['last_name'] . "</td>";
        echo "<td>" . $client['email'] . "</td>";
        echo "<td>" . $client['verification_status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No clients found</p>";
}

?>
