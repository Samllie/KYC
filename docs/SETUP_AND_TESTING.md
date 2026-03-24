# KYC System - Complete Setup & Testing Guide

## Quick Start

### 1. Database Setup

**Option A: phpMyAdmin (Easiest)**
1. Open browser: `http://localhost/phpmyadmin`
2. Click on "SQL" tab
3. Copy entire contents of `database.sql`
4. Paste into the SQL editor
5. Click "Go" to execute
6. Database `kyc_system` will be created with all tables and sample data

**Option B: Command Line**
```bash
cd C:\xampp\mysql\bin
mysql -u root -p < C:\xampp\htdocs\kyc\database.sql
```
(Press Enter when prompted for password - XAMPP has no default password)

### 2. Verify Database Created

In phpMyAdmin, check for `kyc_system` database with these 8 tables:
- ✓ users (6 sample records)
- ✓ clients (24 sample records)
- ✓ kyc_verifications (3 sample records)
- ✓ documents (empty, ready for uploads)
- ✓ verification_history (audit trail)
- ✓ audit_logs (audit trail)

### 3. Start XAMPP & Access System

1. Open XAMPP Control Panel
2. Click "Start" on Apache
3. Click "Start" on MySQL
4. Open browser: `http://localhost/kyc`

**Expected Result**: Redirects to login.php

---

## Login Test

### Test Credentials (6 available)

```
Email: juan@sterlingins.com
Password: password123
```

Or any of:
- maria@sterlingins.com
- robert@sterlingins.com
- angela@sterlingins.com
- john@sterlingins.com
- luisa@sterlingins.com

All have password: `password123`

### Login Flow
1. Enter email and password
2. Click "Sign In"
3. Form validates client-side
4. Submits to `handlers/login.php`
5. Server validates against `users` table
6. Session created with user info
7. Redirected to dashboard.php

### Expected Behavior
- ✓ Valid credentials → Dashboard
- ✓ Invalid credentials → Error toast "Email or password is incorrect"
- ✓ Fields validation → Red border on invalid fields
- ✓ Success toast → Green notification

---

## Registration Test

### Create New Account

1. On login page, click "Create Account"
2. Fill form:
   - Full Name: (min 3 characters)
   - Email: (unique, valid format)
   - Password: (min 8 characters)
   - Confirm Password: (must match)
   - Department: (select one)
   - Check Terms & Conditions
3. Click "Create Account"

### Form Validation
- Full name < 3 chars → Error
- Invalid email format → Error
- Password < 8 chars → Error
- Passwords don't match → Error
- Email already exists → Error from server
- Missing department → Error
- Terms not checked → Unable to submit

### Expected Result
- ✓ Account created → Toast "Registration successful"
- ✓ Redirected to login page
- ✓ Can now login with new credentials

---

## KYC Verification Form Test

### Access Form
1. Login successfully
2. Click "KYC Verification" in sidebar OR
3. Click "New Client" button

### Form Submission

**Option 1: Save Draft**
1. Fill any fields
2. Click "Save Draft"
3. **Expected**: Toast "Draft saved successfully"
4. Form data saved to `kyc_verifications` table with status="draft"

**Option 2: Submit Complete Form**
1. Fill ALL required fields (marked with *)
   - Reference Code
   - Client Type
   - First Name
   - Last Name
   - Date of Birth
   - Occupation
   - Email
   - Mobile Phone
   - Address
2. Click "Submit & Continue"
3. **Expected**: 
   - Toast "Client Saved!"
   - Progress indicator advances to step 3
   - New record in `clients` table
   - Client number auto-generated: CN-[timestamp]
   - Status set to "pending"

**Form Fields Map to Database**:
```
Form Field          Database Column
───────────────────────────────────
refCode        →    reference_code
clientType     →    client_type
firstName      →    first_name
lastName       →    last_name
middleName     →    middle_name
suffixName     →    suffix
birthdate      →    date_of_birth
gender         →    gender
nationality    →    nationality
idType         →    id_type
idNumber       →    id_number
occupation     →    occupation
company        →    company_name
mobile         →    mobile_phone
phone          →    landline_phone
email          →    email
address        →    full_address
```

### Document Upload Test
1. Drag & drop files to upload zone OR
2. Click to select files
3. Supported: PDF, JPG, JPEG, PNG
4. **Expected**: 
   - Toast "Files Attached"
   - File list updates with file size
   - Delete button allows removal

### Clear Form Test
1. Fill some fields
2. Click "Clear Form"
3. **Expected**: All fields cleared, toast "Form Cleared"

---

## Clients Management Test

### View All Clients
1. Click "Clients" in sidebar
2. Table displays 24 sample clients
3. Status filter: All, Verified (14), Pending (6), Rejected (4)
4. Type filter: Individual (20), Corporate (4)

### Search Functionality
1. Type in search box
2. Table filters in real-time
3. Works on: Reference code, Name, Email, Contact

### View Client
1. Click eye icon on any row
2. **Expected**: Alert with client details

### Edit Client
1. Click pencil icon on any row
2. **Expected**: Edit modal opens (placeholder for now)

### Delete Client
1. Click trash icon on any row
2. Confirmation dialog: "Are you sure?"
3. Click OK
4. **Expected**:
   - Row fades out and disappears
   - Toast "Client deleted successfully"
   - Record removed from `clients` table
   - Cascading deletes: related KYC records and documents

### Export Functionality

#### CSV Export
1. Click "Export" button
2. Click "CSV" in modal
3. **Expected**: `clients_export_YYYY-MM-DD.csv` downloaded

#### PDF Export
1. Click "Export" button
2. Click "PDF" in modal
3. **Expected**: `clients_export_YYYY-MM-DD.pdf` downloaded

#### Print
1. Click "Export" button
2. Click "Print" in modal
3. **Expected**: Print dialog opens with formatted table

---

## Database Operations Test

### Test Admin Functions

```sql
-- View all users
SELECT * FROM users;

-- View all clients
SELECT * FROM clients;

-- View verification status breakdown
SELECT verification_status, COUNT(*) as count
FROM clients
GROUP BY verification_status;

-- View audit trail
SELECT * FROM audit_logs ORDER BY action_at DESC LIMIT 10;

-- Check last login of users
SELECT email, last_login FROM users;
```

### Update Client Status (Admin)
```sql
-- Mark client as verified
UPDATE clients 
SET verification_status = 'verified', 
    verified_by = 1,
    verification_date = NOW()
WHERE client_id = 1;

-- Log the status change
INSERT INTO verification_history 
(client_id, old_status, new_status, changed_by, change_reason)
VALUES (1, 'pending', 'verified', 1, 'Manually approved');
```

---

## Session Management

### Session Variables
After login, these are stored:
```php
$_SESSION['user_id']       // User ID
$_SESSION['full_name']     // Full name
$_SESSION['email']         // Email address
$_SESSION['department']    // Department
$_SESSION['role']          // Role (kyc_officer, manager, admin)
```

### Protected Pages
All main pages require login:
- dashboard.php
- clients.php
- kyc-verification.php

### Logout
1. Click logout link (add to dashboard later)
2. Session destroyed
3. Redirected to login.php

### Access Control
**Current Implementation**: Basic session check
```php
require_once 'config/session.php';
requireLogin();  // Redirects to login if not logged in
```

---

## API Endpoints

### Authentication
```
POST /handlers/login.php
POST /handlers/register.php
```

### Clients Management
```
POST /handlers/client.php?action=add_client
POST /handlers/client.php?action=edit_client
POST /handlers/client.php?action=delete_client
POST /handlers/client.php?action=update_status
GET  /handlers/client.php?action=get_client&client_id=1
GET  /handlers/client.php?action=get_all_clients
```

### KYC Operations
```
POST /handlers/kyc.php?action=submit_kyc
POST /handlers/kyc.php?action=save_draft
GET  /handlers/kyc.php?action=get_kyc&ref_code=ABC123
```

---

## Error Messages & Responses

### Login Errors
- "Email and password are required"
- "Invalid email format"
- "Email or password is incorrect"

### Registration Errors
- "Full name must be at least 3 characters"
- "Invalid email format"
- "Password must be at least 8 characters"
- "Passwords do not match"
- "Email already registered"

### KYC Errors
- "All required fields must be filled"
- (Specific field validations on submit)

### Client Errors
- "Invalid client ID"
- "Client not found"

---

## Toast Notifications System

### Toast Types
1. **Success** (Green)
   - Border: Green (#10b981)
   - Icon: Checkmark
   - Uses: Successful operations

2. **Error** (Red)
   - Border: Red (#ef4444)
   - Icon: X mark
   - Uses: Failed operations

3. **Info** (Blue)
   - Border: Blue (#3b82f6)
   - Icon: Info circle
   - Uses: Informational messages

### Common Toasts
```javascript
showToast('success', 'Login Successful', 'Redirecting to dashboard...');
showToast('error', 'Login Failed', 'Invalid credentials');
showToast('info', 'Draft Saved', 'Your progress has been saved');
showToast('success', 'Client Added', 'New client created successfully');
showToast('error', 'Validation Failed', 'Please fill all required fields');
```

---

## Testing Checklist

### Authentication ✓
- [ ] Login with valid credentials
- [ ] Login with invalid credentials
- [ ] Register new account
- [ ] Try register with existing email
- [ ] Logout functionality

### Forms ✓
- [ ] KYC form save as draft
- [ ] KYC form submit complete
- [ ] File upload drag & drop
- [ ] File upload through file picker
- [ ] Clear form resets fields
- [ ] Form validation on required fields

### Client Management ✓
- [ ] View all clients (24 records)
- [ ] Search clients
- [ ] Filter by status
- [ ] Filter by type
- [ ] View client details
- [ ] Edit client (modal)
- [ ] Delete client (with confirmation)

### Export ✓
- [ ] Export as CSV downloads file
- [ ] Export as PDF generates PDF
- [ ] Print opens print dialog
- [ ] Export respects search/filter

### Database ✓
- [ ] New client creates record
- [ ] Client status changes logged
- [ ] User actions tracked in audit_logs
- [ ] Draft saves without submission
- [ ] Login updates last_login

---

## Troubleshooting

### "Access Denied" on dashboard
- **Cause**: Not logged in
- **Solution**: Login first at login.php

### "404 handlers not found"
- **Cause**: Handlers directory missing
- **Solution**: Create folder `handlers/` in `c:\xampp\htdocs\kyc\`

### Database connection error
- **Cause**: MySQL not running or wrong credentials
- **Solution**: 
  1. Check MySQL running in XAMPP
  2. Verify credentials in config/db.php
  3. Ensure database.sql was executed

### Form won't submit
- **Cause**: Validation failed or network error
- **Solution**: 
  1. Check browser console (F12) for errors
  2. Verify required fields filled
  3. Check network tab for API responses

### Toast not showing
- **Cause**: global.css not linked or timeout too short
- **Solution**: 
  1. Verify global.css linked in HTML
  2. Check toast container exists
  3. Wait 4 seconds (default duration)

---

## Performance Notes

### Database Optimization
- Indexes created on:
  - email (users) - Login queries
  - reference_code (clients) - KYC lookups
  - verification_status (clients) - Filtering
  - created_at (audit_logs) - History queries

### Query Performance
- All queries use prepared statements
- Connection pooling ready in config/db.php
- Max results limited in queries

### Client-Side Performance
- AJAX requests prevent page reload
- Search filters in real-time
- Export generates on-demand

---

## Security Reminders

### Passwords
⚠️ All passwords hashed with SHA2(256)
⚠️ Change sample credentials before production
⚠️ Add password complexity rules before production

### Database
⚠️ Empty DB password - CHANGE for production
⚠️ Add user privileges/roles implementation
⚠️ Enable SSL for database connections

### Sessions
⚠️ Add session timeout (default: none)
⚠️ Implement CSRF tokens for forms
⚠️ Add rate limiting for login attempts

---

## Next Steps After Setup

1. **Change Passwords**
   - All users have password123
   - Update to secure passwords

2. **Customize User Info**
   - Update sidebar to show logged-in user
   - Add user profile page
   - Add password change function

3. **Implement Features**
   - Document upload storage
   - Email notifications
   - Policy issuance
   - Reports generation

4. **Add Validations**
   - Email verification
   - Phone number format
   - Address validation
   - File size limits

5. **Production Preparation**
   - Enable HTTPS
   - Change all database credentials
   - Set proper file permissions
   - Enable error logging
   - Configure backups

