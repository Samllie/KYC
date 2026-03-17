# KYC System - Complete File Reference

## Directory Structure

```
c:\xampp\htdocs\kyc\
│
├─ 📄 INDEX.PHP (MODIFIED)
│  └─ Redirects to login.php - Landing page for all users
│
├─ 📄 LOGIN.PHP (MODIFIED)
│  └─ User authentication form - Now submits to handlers/login.php
│  └─ Features: Email/password fields, remember me, registration link
│  └─ Validation: Email format, password length, server-side auth check
│
├─ 📄 REGISTER.PHP (MODIFIED)
│  └─ New account registration form - Now submits to handlers/register.php
│  └─ Fields: Full name, email, password, department, terms checkbox
│  └─ Validation: Name length, email uniqueness, password confirmation
│
├─ 📄 DASHBOARD.PHP (MODIFIED)
│  └─ Main dashboard with statistics - Now has session protection
│  └─ Features: Stats cards, quick links, navigation sidebar
│  └─ Security: requireLogin() checks, session variables used
│
├─ 📄 CLIENTS.PHP (MODIFIED)
│  └─ Client management interface - Now fully functional with handlers
│  └─ Features: View, search, filter, export, delete clients
│  └─ Actions: view/edit/delete buttons now work with APIs
│  └─ Export: CSV, PDF, Print with preview modal
│
├─ 📄 KYC-VERIFICATION.PHP (MODIFIED)
│  └─ Multi-step KYC client registration form - Now submits to handlers
│  └─ Features: 4-step progress indicator, auto-generated client number
│  └─ Sections: Reference, Full Name, Personal, Occupation, Contact, Address, Documents
│  └─ Functions: submitForm(), saveDraft(), clearForm() now functional
│
├─ 📄 LOGOUT.PHP (NEW)
│  └─ Logout handler - Destroys session and redirects to login
│  └─ Security: Uses config/session.php logout() function
│
├─ 📄 POLICY.PHP
│  └─ Policy issuance page (placeholder)
│
├─ 📦 CONFIG/ (NEW DIRECTORY)
│  │
│  ├─ 📄 DB.PHP (NEW)
│  │  └─ Database connection utility
│  │  └─ Constants: DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT
│  │  └─ Connection: mysqli with UTF-8 charset
│  │  └─ Functions:
│  │     • executeQuery($query, $params) - Prepared statement executor
│  │     • fetchAll($query, $params) - Get multiple rows
│  │     • fetchOne($query, $params) - Get single row
│  │     • insert($table, $data) - INSERT wrapper
│  │     • update($table, $data, $where, $whereParams) - UPDATE wrapper
│  │     • closeDB() - Cleanup on shutdown
│  │  └─ Security: All queries are prepared statements
│  │
│  └─ 📄 SESSION.PHP (NEW)
│     └─ Session management utilities
│     └─ Functions:
│        • requireLogin() - Redirect to login if not authenticated
│        • requireRole($role) - Check user role
│        • getCurrentUser() - Get logged-in user info
│        • getAvatarInitials($name) - Generate user initials
│        • logout() - Destroy session
│        • isAdmin() - Check if admin
│        • isKYCOfficer() - Check if KYC officer
│        • logAction(...) - Audit trail logging
│     └─ Security: Session verification on every call
│
├─ 📦 HANDLERS/ (NEW DIRECTORY)
│  │
│  ├─ 📄 LOGIN.PHP (NEW)
│  │  └─ Authentication API endpoint
│  │  └─ POST endpoint: /handlers/login.php
│  │  └─ Parameters: email, password, remember
│  │  └─ Process:
│  │     1. Validate email format and password length
│  │     2. Query users table by email
│  │     3. Compare input password with SHA2(256) hash
│  │     4. Update last_login timestamp
│  │     5. Create session variables
│  │  └─ Response: JSON {success, message, redirect}
│  │  └─ Error Handling: Validation errors, user not found, password mismatch
│  │
│  ├─ 📄 REGISTER.PHP (NEW)
│  │  └─ Account registration API endpoint
│  │  └─ POST endpoint: /handlers/register.php
│  │  └─ Parameters: fullname, email, password, confirmPassword, department, terms
│  │  └─ Process:
│  │     1. Validate all fields (name, email, password, department)
│  │     2. Check email not already registered
│  │     3. Hash password with SHA2(256)
│  │     4. Generate avatar initials
│  │     5. Insert new user into users table
│  │  └─ Response: JSON {success, message, redirect}
│  │  └─ Validation Rules:
│  │     • Name: 3-100 characters
│  │     • Email: Valid format, unique
│  │     • Password: 8+ characters, matches confirmation
│  │     • Department: kyc-officer, compliance, operations, management
│  │
│  ├─ 📄 CLIENT.PHP (NEW)
│  │  └─ Client management API endpoint
│  │  └─ POST/GET endpoint: /handlers/client.php?action=X
│  │  └─ Actions:
│  │     • ADD_CLIENT - Create new client
│  │       Parameters: refCode, clientType, firstName, lastName, etc.
│  │       Creates: clients table record, auto-generates client_number
│  │     • EDIT_CLIENT - Update existing client
│  │       Parameters: client_id, fields to update
│  │       Updates: clients table, sets updated_at timestamp
│  │     • DELETE_CLIENT - Remove client
│  │       Parameters: client_id
│  │       Deletes: client record, cascades to related records
│  │     • GET_CLIENT - Retrieve client details
│  │       Parameters: client_id
│  │       Returns: Complete client record
│  │     • GET_ALL_CLIENTS - List all clients with filters
│  │       Parameters: status (optional), type (optional)
│  │       Returns: Array of matching clients
│  │     • UPDATE_STATUS - Change verification status
│  │       Parameters: client_id, status, reason (if rejected)
│  │       Updates: clients.verification_status, verified_by, rejection_reason
│  │       Logs: verification_history record
│  │  └─ Security: Session check, SQL injection prevention
│  │
│  └─ 📄 KYC.PHP (NEW)
│     └─ KYC verification form API endpoint
│     └─ POST/GET endpoint: /handlers/kyc.php?action=X
│     └─ Actions:
│        • SUBMIT_KYC - Submit complete KYC form
│          Parameters: All form fields (refCode, firstName, lastName, etc.)
│          Field Mapping: Form inputs → database columns
│          Process:
│            1. Validate all required fields
│            2. Check if client exists by reference code
│            3. Create/update client record
│            4. Create/update kyc_verifications record
│            5. Mark all steps completed
│            6. Set status to submitted
│          Response: {success, message, client_id}
│        • SAVE_DRAFT - Save incomplete form data
│          Parameters: Partial or complete form fields
│          Creates/Updates: kyc_verifications with status=draft
│          Response: {success, message}
│        • GET_KYC - Retrieve saved KYC record
│          Parameters: ref_code
│          Returns: Complete KYC record for form recovery
│
├─ 📦 CSS/ (MODIFIED & EXPANDED)
│  │
│  ├─ 📄 AUTH.CSS
│  │  └─ Authentication page styles (login/register)
│  │  └─ Features:
│  │     • Split-screen layout (desktop), single column (mobile)
│  │     • Branding section with company info
│  │     • Form controls with validation styling
│  │     • Toast notifications with animations
│  │     • Responsive breakpoints: 1024px, 768px, 480px
│  │     • Button styles, input states, error messages
│  │
│  ├─ 📄 INDEX.CSS
│  │  └─ Main layout styles (sidebar, topbar, content)
│  │  └─ Features:
│  │     • Sidebar navigation with badges
│  │     • Topbar with search and notifications
│  │     • Grid layout for forms
│  │     • Form sections with dividers
│  │     • File upload zone with drag-drop
│  │     • Progress indicators
│  │
│  ├─ 📄 DASHBOARD.CSS
│  │  └─ Dashboard-specific styles
│  │  └─ Features:
│  │     • Stat cards with icons
│  │     • Quick access panels
│  │     • Chart/graph containers
│  │
│  ├─ 📄 CLIENTS.CSS
│  │  └─ Client management page styles
│  │  └─ Features:
│  │     • Data table with sorting capability
│  │     • Filter controls
│  │     • Action buttons with icons
│  │     • Modal dialogs for edit/view
│  │     • Export preview modal
│  │     • Status badges (verified, pending, rejected)
│  │     • Responsive table scrolling on mobile
│  │
│  └─ 📄 GLOBAL.CSS (NEW)
│     └─ Global utilities and shared components
│     └─ Includes:
│        • Toast notification styles (.toast-container, .toast)
│        • Status badge helpers (.status-badge.verified, etc.)
│        • Utility classes (spacing, opacity, cursor, etc.)
│        • Modal overlay styles
│        • Loading spinner animation
│        • Form error/success helpers
│        • Responsive utilities
│        • Transition utilities
│
├─ 📄 DATABASE.SQL
│  └─ Complete database schema creation script
│  └─ 8 Tables:
│     • users (6 sample records with test accounts)
│     • clients (24 sample records with realistic data)
│     • kyc_verifications (3 sample records)
│     • documents (empty, structure ready)
│     • verification_history (audit trail)
│     • audit_logs (audit trail)
│  └─ Features:
│     • Foreign keys with cascade deletes
│     • Indexes for performance
│     • UTF-8 encoding for international support
│     • InnoDB engine for transactions
│     • UNIQUE constraints
│     • DEFAULT values
│
├─ 📄 HANDLERS_GUIDE.MD (NEW)
│  └─ Complete API documentation
│  └─ Sections:
│     • Overview of handlers directory
│     • Detailed documentation for each handler
│     • Request/response formats with examples
│     • Field mappings (form → database)
│     • Validation rules for each endpoint
│     • Error handling patterns
│     • Database integration details
│     • Test procedures
│     • Security features
│
├─ 📄 SETUP_AND_TESTING.MD (NEW)
│  └─ Setup instructions and testing guide
│  └─ Includes:
│     • Quick start (3 setup options)
│     • Database creation steps
│     • XAMPP configuration
│     • Login test procedures
│     • Registration test
│     • Form submission tests
│     • Client management tests
│     • Export functionality tests
│     • Database verification queries
│     • Session management info
│     • Complete testing checklist
│     • Troubleshooting guide
│     • Performance notes
│     • Security reminders
│
├─ 📄 DATABASE_SETUP.MD
│  └─ Database configuration reference
│  └─ Includes:
│     • Table descriptions
│     • Setup methods (3 options)
│     • Sample login credentials
│     • Database statistics
│     • Maintenance queries
│     • Security notes
│
├─ 📄 IMPLEMENTATION_SUMMARY.MD (NEW)
│  └─ High-level implementation overview
│  └─ Includes:
│     • Project status and completion checklist
│     • Feature implementation matrix
│     • Database tables overview
│     • API endpoints summary
│     • JavaScript functions implemented
│     • Security features
│     • Testing coverage
│     • Sample test data
│     • Known limitations
│     • Deployment checklist
│     • Statistics and metrics
│
└─ 📄 THIS_FILE.MD
   └─ File reference guide (you are here)
```

---

## Database Tables Reference

### 1. USERS Table
```sql
Fields: user_id, full_name, email, password, department, role, 
        avatar_initials, status, last_login, created_at, updated_at
Sample Records: 6 KYC officers with test credentials
Primary Key: user_id
Unique: email
Purpose: Authentication and user management
```

### 2. CLIENTS Table
```sql
Fields: client_id, reference_code, client_number, client_type,
        first_name, middle_name, last_name, suffix,
        date_of_birth, gender, nationality, 
        id_type, id_number,
        occupation, company_name,
        mobile_phone, landline_phone, email, full_address,
        verification_status, verification_date, verified_by, rejection_reason,
        created_at, updated_at
Sample Records: 24 clients (14 verified, 6 pending, 4 rejected)
Primary Key: client_id
Unique: reference_code, client_number, email
Purpose: Store all client information
```

### 3. KYC_VERIFICATIONS Table
```sql
Fields: kyc_id, client_id, reference_code,
        step_current, step_1_completed through step_4_completed,
        ref_code, client_type, last_name, first_name, middle_name, suffix,
        birthdate, gender, nationality, id_type, id_number,
        occupation, company, mobile, phone, email, address,
        status, submitted_at, created_at, updated_at
Sample Records: 3 KYC records
Primary Key: kyc_id
Foreign Key: client_id
Purpose: Track multi-step form progress and store form data
```

### 4. DOCUMENTS Table
```sql
Fields: document_id, kyc_id, client_id, 
        file_name, file_type, file_size, file_path, document_type,
        uploaded_by, uploaded_at, status, verification_notes
Primary Key: document_id
Foreign Keys: kyc_id, client_id, uploaded_by
Purpose: Store references to uploaded documents
```

### 5. VERIFICATION_HISTORY Table
```sql
Fields: history_id, client_id, kyc_id,
        old_status, new_status, changed_by, change_reason, changed_at
Primary Key: history_id
Foreign Keys: client_id, kyc_id, changed_by
Purpose: Audit trail for status changes
```

### 6. AUDIT_LOGS Table
```sql
Fields: log_id, user_id, action, table_name, record_id,
        old_value, new_value, ip_address, user_agent, action_at
Primary Key: log_id
Foreign Key: user_id
Purpose: System audit trail for compliance
```

---

## Function Reference

### Authentication Functions (config/session.php)
- `requireLogin()` - Ensure user is logged in
- `requireRole($role)` - Check specific role
- `getCurrentUser()` - Get logged-in user info
- `getAvatarInitials($name)` - Generate user initials
- `logout()` - Destroy session
- `isAdmin()` - Check admin status
- `isKYCOfficer()` - Check KYC officer status
- `logAction()` - Log to audit trail

### Database Functions (config/db.php)
- `executeQuery($query, $params)` - Run prepared statement
- `fetchAll($query, $params)` - Get multiple rows
- `fetchOne($query, $params)` - Get single row
- `insert($table, $data)` - INSERT operation
- `update($table, $data, $where, $params)` - UPDATE operation
- `closeDB()` - Clean shutdown

### Form Functions (JavaScript)
- `showToast(type, title, msg)` - Show notification
- `removeToast(element)` - Remove notification
- `validateField(field)` - Validate form field
- `validateEmail(email)` - Email regex validation

### KYC Form Functions (kyc-verification.php)
- `submitForm()` - Submit KYC form to handler
- `saveDraft()` - Save incomplete form
- `clearForm()` - Reset all fields
- `addFile(file)` - Add file to upload list
- `formatSize(bytes)` - Format file size for display

### Client Functions (clients.php)
- `viewClient(data)` - Display client details
- `editClient(data)` - Open edit modal
- `deleteClient(refCode, row)` - Delete client
- `showExportPreview()` - Show export modal
- `exportAsCSV()` - Download CSV file
- `exportAsPDF()` - Generate and download PDF
- `printReport()` - Open print dialog
- `getTableData()` - Extract table data

### Login Functions (login.php)
- Form submission handler with AJAX
- Email and password validation
- Server authentication
- Session creation

### Register Functions (register.php)
- Form submission handler with AJAX
- Full name, email, password validation
- Department selection
- Terms and conditions check

---

## Key Integration Points

### 1. Form to Handler Flow
```
Form Page (login.php)
  ↓
User fills fields & validates
  ↓
Form submission with AJAX
  ↓
Handler (handlers/login.php)
  ↓
Server-side validation & database query
  ↓
Return JSON response
  ↓
JavaScript processes response
  ↓
Toast notification shown
  ↓
Redirect or show error
```

### 2. Session Flow
```
User logged in
  ↓
Session variables created:
  - user_id
  - full_name
  - email
  - department
  - role
  ↓
Protected pages check requireLogin()
  ↓
Access granted or redirect to login.php
  ↓
On logout: session destroyed, redirect to login
```

### 3. Database Flow
```
Handler receives request
  ↓
requireLogin() checks session
  ↓
Validate input parameters
  ↓
Build parameterized SQL query
  ↓
Execute via config/db.php helpers
  ↓
Get results from database
  ↓
Process and build response
  ↓
Return JSON to JavaScript
  ↓
JavaScript updates UI and shows feedback
```

---

## How to Use Each Handler

### Login Handler: `POST /handlers/login.php`
```javascript
const formData = new FormData();
formData.append('email', 'juan@sterlingins.com');
formData.append('password', 'password123');

fetch('handlers/login.php', {method: 'POST', body: formData})
  .then(r => r.json())
  .then(data => {
    if (data.success) window.location = data.redirect;
    else showToast('error', 'Login Failed', data.message);
  });
```

### Register Handler: `POST /handlers/register.php`
```javascript
const formData = new FormData(registerForm);

fetch('handlers/register.php', {method: 'POST', body: formData})
  .then(r => r.json())
  .then(data => {
    if (data.success) window.location = data.redirect;
    else showToast('error', 'Registration Failed', data.message);
  });
```

### Client Handler: `POST /handlers/client.php`
```javascript
const formData = new FormData();
formData.append('action', 'delete_client');
formData.append('client_id', 123);

fetch('handlers/client.php', {method: 'POST', body: formData})
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showToast('success', 'Deleted', 'Client removed');
      row.remove(); // Remove from table
    }
  });
```

### KYC Handler: `POST /handlers/kyc.php`
```javascript
const formData = new FormData(kycForm);
formData.append('action', 'submit_kyc');

fetch('handlers/kyc.php', {method: 'POST', body: formData})
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showToast('success', 'Submitted', 'KYC form saved');
      setTimeout(() => window.location = 'dashboard.php', 2000);
    }
  });
```

---

## Testing Files Created

All files have built-in test data:
- 6 user test accounts (see SETUP_AND_TESTING.md)
- 24 sample clients with various statuses
- Complete test procedures documented

---

## Next Steps

1. **Execute database.sql** - Creates database with all tables
2. **Access http://localhost/kyc** - Should redirect to login
3. **Login with test credentials** - Any of 6 test user accounts
4. **Test each feature** - See SETUP_AND_TESTING.md
5. **Check documentation** - See HANDLERS_GUIDE.md for API

All buttons and database functions are now fully operational! 🎉

