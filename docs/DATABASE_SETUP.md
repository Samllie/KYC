# KYC System - Database Setup Guide

## 📊 Database Schema Overview

The KYC System uses a comprehensive MySQL database with 8 tables:

### Tables

#### 1. **users** - System Users & KYC Officers
- Stores user accounts for login/registration
- Fields: user_id, full_name, email, password (hashed), department, branch, role, status
- Total Sample Records: 6 users

#### 2. **clients** - Client Master Data
- Stores all client information
- Fields: client_id, reference_code, client_number, client_type, personal info, contact, verification_status
- Total Sample Records: 24 clients (from the system)

#### 3. **kyc_verifications** - KYC Verification Records
- Tracks KYC verification progress (4 steps)
- Fields: kyc_id, client_id, step progress tracking, form data, status
- Total Sample Records: 3 sample records

#### 4. **documents** - Uploaded Documents
- Stores uploaded documents for verification
- Fields: document_id, kyc_id, client_id, file info, document_type, status
- Supports: PDF, JPG, PNG files

#### 5. **verification_history** - Status Change Log
- Audit trail of verification status changes
- Fields: history_id, client_id, old_status, new_status, changed_by, reason
- Total Sample Records: 3 history entries

#### 6. **audit_logs** - System Activity Log
- Tracks all system changes for compliance
- Fields: log_id, user_id, action, table_name, old_value, new_value, timestamp

---

## 🚀 Setup Instructions

### Step 1: Open phpMyAdmin
1. Open your browser
2. Go to: `http://localhost/phpmyadmin`
3. Login with default credentials:
   - Username: `root`
   - Password: (leave blank)

### Step 2: Create Database
1. Click on "SQL" tab (or "New" button)
2. Copy and paste all contents from `database.sql`
3. Click "Go" to execute

### Step 3: Verify Installation
After execution, you should see:
- Database: `kyc_system` created
- 6 tables created with indexes
- 6 users inserted
- 24 clients inserted
- Status message showing success

---

## 📋 Sample Data Included

### Users (Login Credentials)
```
Email: juan@sterlingins.com      | Password: password123 | Role: KYC Officer
Email: maria@sterlingins.com     | Password: password123 | Role: KYC Officer
Email: robert@sterlingins.com    | Password: password123 | Role: Manager
Email: angela@sterlingins.com    | Password: password123 | Role: KYC Officer
Email: john@sterlingins.com      | Password: password123 | Role: KYC Officer
Email: luisa@sterlingins.com     | Password: password123 | Role: KYC Officer
```

### Client Status Distribution
- ✅ Verified: 14 clients
- ⏳ Pending: 6 clients
- ❌ Rejected: 4 clients

---

## 🔧 Database Connection

### Using PHP with config file:
```php
<?php
include 'config/db.php';

// Fetch all clients
$clients = fetchAll("SELECT * FROM clients");

// Fetch single client
$client = fetchOne("SELECT * FROM clients WHERE client_id = ?", [1]);

// Insert client
$data = [
    'reference_code' => 'KYC-2024-0025',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'client_type' => 'individual'
];
$result = insert('clients', $data);
?>
```

---

## 📊 Database Statistics

| Table | Records |
|-------|---------|
| users | 6 |
| clients | 24 |
| kyc_verifications | 3 |
| documents | 0 (ready to add) |
| verification_history | 3 |
| audit_logs | 0 (tracks runtime) |

---

## 🔐 Security Features

1. **Password Hashing**: All passwords stored using SHA2(256)
2. **Foreign Keys**: Referential integrity across tables
3. **Indexes**: Performance optimization for frequently searched fields
4. **UTF-8 Support**: Handles special characters and international data
5. **Audit Trail**: Complete history of all status changes
6. **Default Values**: Automatic timestamps (created_at, updated_at)

---

## 📝 Important Notes

1. **Default Password**: `password123` for all users - **CHANGE IN PRODUCTION**
2. **Database Name**: `kyc_system`
3. **Encoding**: UTF-8 (utf8mb4_unicode_ci)
4. **Engine**: InnoDB with foreign key constraints

---

## 🛠️ Maintenance Queries

### Check Database Health
```sql
SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'kyc_system';
```

### Count Verified Clients
```sql
SELECT COUNT(*) as verified_clients 
FROM clients 
WHERE verification_status = 'verified';
```

### Get Recent Activity
```sql
SELECT * FROM verification_history 
ORDER BY changed_at DESC LIMIT 10;
```

### Export All Clients
```sql
SELECT * FROM clients 
ORDER BY created_at DESC;
```

---

## 📞 Support

For questions about the database schema or setup, refer to the SQL file comments or contact your administrator.

**Created**: March 17, 2026
**System**: KYC System v1.0
**Company**: Sterling Insurance Company
