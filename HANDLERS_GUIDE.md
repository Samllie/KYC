# KYC System - Database Functions Implementation Guide

## Overview

All buttons in the KYC system now connect to database handlers using AJAX calls. Form submissions are processed server-side with proper validation and error handling.

## Handlers Directory Structure

```
handlers/
├── login.php          # User authentication
├── register.php       # New account registration  
├── client.php         # Client CRUD operations
└── kyc.php           # KYC verification form submission
```

## Implementation Details

### 1. LOGIN HANDLER (`handlers/login.php`)

**Purpose**: Authenticate users against the database

**Entry Point**: `login.php` form submission

**Form Fields**:
- `email` (required) - User email address
- `password` (required) - User password (minimum 6 characters)
- `remember` (optional) - Remember me checkbox

**Process**:
1. Validates email and password format
2. Queries `users` table by email
3. Compares input password with SHA2(256) hash from database
4. Updates `last_login` timestamp on success
5. Creates session variables:
   - `user_id`
   - `full_name`
   - `email`
   - `department`
   - `role`

**Response**:
```json
{
  "success": true/false,
  "message": "Login successful" or error message,
  "redirect": "dashboard.php"
}
```

**Sample Test Credentials**:
- Email: `juan@sterlingins.com`
- Password: `password123`

---

### 2. REGISTER HANDLER (`handlers/register.php`)

**Purpose**: Create new KYC officer accounts

**Entry Point**: `register.php` form submission

**Form Fields**:
- `fullname` (required) - Full name (min 3 characters)
- `email` (required) - Email address (must be unique)
- `password` (required) - Password (min 8 characters)
- `confirmPassword` (required) - Password confirmation (must match)
- `department` (required) - Department selection
- `terms` (required) - Terms & Conditions checkbox

**Process**:
1. Validates all fields
2. Checks if email already registered
3. Hashes password with SHA2(256)
4. Generates avatar initials from name
5. Inserts new user into `users` table
6. Default role: `kyc_officer`, status: `active`

**Response**:
```json
{
  "success": true/false,
  "message": "Registration successful" or error message,
  "redirect": "login.php"
}
```

**Validation Rules**:
- Full name: 3-100 characters
- Email: Valid format, unique in database
- Password: 8+ characters, matches confirmation
- Department: One of (kyc-officer, compliance, operations, management)

---

### 3. CLIENT HANDLER (`handlers/client.php`)

**Purpose**: Manage client records (CRUD operations)

**Actions**:

#### A. ADD_CLIENT
**POST Parameters**:
- `action`: "add_client"
- `refCode`, `clientType`, `firstName`, `lastName`, `middleName`, `birthdate`, `email`, `mobile`, `occupation`, `address` (all required except middleName)

**Process**:
- Validates all required fields
- Auto-generates unique client number (CN-[timestamp])
- Inserts into `clients` table
- Sets default status: "pending"

**Response**:
```json
{
  "success": true,
  "message": "Client added successfully",
  "client_id": 123
}
```

#### B. EDIT_CLIENT
**POST Parameters**:
- `action`: "edit_client"
- `client_id` (required)
- Client fields to update

**Process**:
- Validates client exists
- Updates specified fields in `clients` table
- Timestamp `updated_at` auto-updated by DB

#### C. DELETE_CLIENT
**POST Parameters**:
- `action`: "delete_client"
- `client_id` (required)

**Process**:
- Validates client exists
- Deletes from `clients` table (cascades delete related records)
- Removes associated KYC records and documents

#### D. GET_CLIENT
**GET Parameters**:
- `action`: "get_client"
- `client_id` (required)

**Returns**: Complete client record with all details

#### E. GET_ALL_CLIENTS
**GET Parameters**:
- `action`: "get_all_clients"
- `status` (optional) - Filter by verification status
- `type` (optional) - Filter by client type (individual/corporate)

**Returns**: Array of all clients matching filters

#### F. UPDATE_STATUS
**POST Parameters**:
- `action`: "update_status"
- `client_id` (required)
- `status` (required) - draft, pending, verified, or rejected
- `reason` (optional) - Rejection reason if status is rejected

**Process**:
1. Updates `verification_status` in clients table
2. Sets `verified_by` to current user
3. Logs status change to `verification_history` table
4. Adds rejection reason if provided

---

### 4. KYC HANDLER (`handlers/kyc.php`)

**Purpose**: Handle multi-step KYC form submissions

**Actions**:

#### A. SUBMIT_KYC
**POST Parameters**:
- `action`: "submit_kyc"
- All KYC form fields from kyc-verification.php

**Field Mapping**:
```
Form Input                Database Field
─────────────────────────────────────────
refCode              →    reference_code
clientType           →    client_type
lastName             →    last_name
firstName            →    first_name
middleName           →    middle_name
suffixName           →    suffix
birthdate            →    date_of_birth (birthdate)
gender               →    gender
nationality          →    nationality
idType               →    id_type
idNumber             →    id_number
occupation           →    occupation
company              →    company_name
mobile               →    mobile_phone
phone                →    landline_phone
email                →    email
address              →    full_address
```

**Process**:
1. Validates all required fields filled
2. Checks if client exists by reference code
3. If exists: Updates clients table
4. If new: Creates new client record
5. Creates/updates KYC verification record with all form data
6. Sets status to "submitted"
7. Marks all 4 steps as completed

**Response**:
```json
{
  "success": true,
  "message": "KYC verification submitted successfully",
  "client_id": 123
}
```

#### B. SAVE_DRAFT
**POST Parameters**:
- `action`: "save_draft"
- Partial or complete KYC form fields

**Process**:
1. Collects form data
2. Creates or updates KYC verification record
3. Sets status to "draft"
4. Preserves all entered data without validation

**Response**:
```json
{
  "success": true,
  "message": "Draft saved successfully"
}
```

#### C. GET_KYC
**GET Parameters**:
- `action`: "get_kyc"
- `ref_code` (required) - Reference code to retrieve

**Returns**: Complete KYC record with all progress and form data

---

## JavaScript Functions

### In login.php:
```javascript
// Form validation and submission
sessionStorage.login.addEventListener('submit', handleLogin);

// Result: Validates → Submits to handlers/login.php → Redirects to dashboard
```

### In register.php:
```javascript
registerForm.addEventListener('submit', handleRegistration);

// Result: Validates → Submits to handlers/register.php → Redirects to login
```

### In kyc-verification.php:
```javascript
submitForm()    // Submits complete KYC form
saveDraft()     // Saves incomplete form as draft
clearForm()     // Resets all form fields
```

### In clients.php:
```javascript
viewClient(data)              // Display client details
editClient(data)              // Open edit modal
deleteClient(refCode, row)    // Delete client after confirmation
showExportPreview()           // Show export modal
exportAsCSV()                 // Download CSV file
exportAsPDF()                 // Generate and download PDF
printReport()                 // Print report
```

---

## Database Schema Integration

### Users Table - Authentication
```sql
user_id → Primary identifier
email → Login credential (UNIQUE)
password → SHA2(256) hash
role → kyc_officer, manager, admin
department → Organizational unit
status → active, inactive, suspended
```

### Clients Table - Core Data
```sql
client_id → Primary identifier
reference_code → Form reference (UNIQUE)
client_number → Auto-generated (CN-[timestamp])
verification_status → draft → pending → verified/rejected
verified_by → Foreign key to users.user_id
All form fields stored for historical reference
```

### KYC_Verifications Table - Multi-step Progress
```sql
kyc_id → Primary identifier
client_id → Foreign key to clients
step_current → Current step (1-4)
step_1_completed through step_4_completed → Booleans
All form fields duplicated for form recovery
status → draft, in_progress, submitted, approved, rejected
```

### Verification_History Table - Audit Trail
```sql
Records every status change for compliance
old_status → Previous status
new_status → New status
changed_by → User who made change
change_reason → Why (e.g., rejection reason)
```

---

## Error Handling

All handlers implement consistent error responses:

```json
{
  "success": false,
  "message": "Specific error message"
}
```

**Session Check**: All client/KYC handlers verify `$_SESSION['user_id']` before processing.

**Input Validation**: All inputs are trimmed, validated for type/length, and checked for required fields.

**SQL Injection Prevention**: All queries use prepared statements with parameter binding.

---

## Testing

### 1. Test Login
- Use: `juan@sterlingins.com` / `password123`
- Expected: Redirect to dashboard.php

### 2. Test Registration
- Create new account with unique email
- Password minimum 8 characters
- Expected: Redirect to login.php

### 3. Test KYC Submission
- Fill all required fields in kyc-verification.php
- Click "Submit & Continue"
- Expected: Client record created, redirect to dashboard

### 4. Test Client Operations
- Click View/Edit/Delete on any client row
- Expected: Appropriate action performed, feedback shown

### 5. Test Export
- Click Export button
- Select CSV/PDF/Print
- Expected: File downloaded or print dialog opens

---

## Database Credentials

**File**: `config/db.php`

```php
DB_HOST = 'localhost'
DB_USER = 'root'
DB_PASS = ''           // Empty by default (XAMPP)
DB_NAME = 'kyc_system'
DB_PORT = 3306
```

**⚠️ IMPORTANT**: Change these for production deployments!

---

## Session Management

After successful login, session variables are set:
```php
$_SESSION['user_id']
$_SESSION['full_name']
$_SESSION['email']
$_SESSION['department']
$_SESSION['role']
```

Use `session_start()` at the beginning of any protected page.

---

## Database Helper Functions

**Location**: `config/db.php`

```php
// Execute prepared statement
executeQuery($query, $params)

// Get all matching rows
fetchAll($query, $params)

// Get single row
fetchOne($query, $params)

// Insert new record
insert($table, $data)

// Update existing record
update($table, $data, $where, $whereParams)
```

---

## API Response Pattern

All handlers follow this pattern:

```javascript
fetch('handlers/action.php', {
    method: 'POST',
    body: formData
})
.then(res => res.json())
.then(data => {
    if (data.success) {
        showToast('success', 'Title', data.message);
        // Perform action...
    } else {
        showToast('error', 'Error', data.message);
    }
})
.catch(err => showToast('error', 'Error', 'Network error'));
```

All buttons in the system now follow this pattern for consistency.

