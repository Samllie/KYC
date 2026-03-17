# KYC System - Reorganized Project Structure

## 📁 **Final Directory Organization**

```
kyc/
├── index.php                    # Entry point (redirects to auth/login.php)
├── config/
│   ├── db.php                  # Database connection & helper functions
│   └── session.php             # Session & authentication management
├── auth/                        # Authentication Pages
│   ├── login.php               # User login (POST → handlers/logins.php)
│   ├── register.php            # User registration (POST → handlers/register.php)
│   └── logout.php              # Logout handler (SESSION destroy)
├── application/                 # Main Application Pages
│   ├── dashboard.php           # Dashboard & metrics
│   ├── clients.php             # Client management table (AJAX → handlers/get_clients.php, handlers/client.php)
│   ├── kyc-verification.php    # KYC form & verification (AJAX → handlers/kyc.php)
│   └── policy.php              # Policy management (⏳ pending)
├── handlers/                   # Backend API Endpoints
│   ├── logins.php              # Login authentication
│   ├── register.php            # User registration
│   ├── client.php              # Client CRUD (add, edit, delete)
│   ├── kyc.php                 # KYC submission (submit, draft, get)
│   └── get_clients.php         # Fetch clients for table
├── css/                        # Stylesheets
│   ├── auth.css                # Auth pages styling
│   ├── index.css               # Layout styling
│   ├── global.css              # Global styles
│   ├── dashboard.css           # Dashboard styling
│   └── clients.css             # Client table styling
├── database/                   # Database
│   └── database.sql            # Complete schema
└── docs/                       # Documentation
    ├── PROJECT_STRUCTURE.md    # This file
    ├── DATABASE_SETUP.md
    ├── FILE_REFERENCE.md
    ├── HANDLERS_GUIDE.md
    └── IMPLEMENTATION_SUMMARY.md
```

---

## 🔗 **Form Submission Flows**

### **1. Authentication Flow**
```
index.php
  ↓
auth/login.php
  ├─ Email/Password Form
  ├─ AJAX POST → ../handlers/logins.php
  └─ Redirect → ../application/dashboard.php (on success)

auth/register.php
  ├─ Registration Form
  ├─ AJAX POST → ../handlers/register.php
  └─ Redirect → auth/login.php (on success)

auth/logout.php
  ├─ Destroy session
  └─ Redirect → auth/login.php
```

### **2. Client Management Flow**
```
application/clients.php
  ├─ Load clients (GET)
  │  └─ ../handlers/get_clients.php → SQL SELECT * FROM clients
  │     └─ Render dynamic table rows
  │
  ├─ Edit Client (POST)
  │  └─ ../handlers/client.php (action: edit_client)
  │     └─ Update clients table
  │
  └─ Delete Client (POST)
     └─ ../handlers/client.php (action: delete_client)
        └─ Delete from clients table
```

### **3. KYC Verification Flow**
```
application/kyc-verification.php
  ├─ Submit KYC Form (POST)
  │  ├─ ../handlers/kyc.php (action: submit_kyc)
  │  ├─ Auto-generate reference code (REF-YYYYMMDD-XXXXX)
  │  ├─ Insert into kyc_verifications table
  │  ├─ Create/Update clients table
  │  └─ Return: { success, reference_code }
  │
  └─ Save as Draft (POST)
     ├─ ../handlers/kyc.php (action: save_draft)
     ├─ Auto-generate reference code (if not provided)
     ├─ Insert/Update kyc_verifications (status: draft)
     └─ Return: { success, reference_code }
```

---

## 📂 **Path Mapping**

### **Auth Pages → Resources**
```
auth/login.php
├─ Includes: ../css/auth.css
├─ Submits: ../handlers/logins.php
└─ Redirects: ../application/dashboard.php

auth/register.php
├─ Includes: ../css/auth.css
├─ Submits: ../handlers/register.php
└─ Redirects: auth/login.php

auth/logout.php
├─ Includes: ../config/session.php
└─ Redirects: auth/login.php
```

### **Application Pages → Resources**
```
application/dashboard.php
├─ Includes: ../config/session.php
└─ Stylesheets: ../css/index.css, ../css/dashboard.css, ../css/global.css

application/clients.php
├─ Includes: ../config/session.php
├─ Fetches: ../handlers/get_clients.php
├─ Posts: ../handlers/client.php
└─ Stylesheets: ../css/index.css, ../css/clients.css, ../css/global.css

application/kyc-verification.php
├─ Includes: ../config/session.php
├─ Posts: ../handlers/kyc.php (submit_kyc, save_draft)
└─ Stylesheets: ../css/index.css, ../css/global.css

application/policy.php
├─ Includes: ../config/session.php
└─ ⏳ Under development
```

### **Handlers → Config**
```
All handlers/ files
├─ handlers/logins.php
├─ handlers/register.php
├─ handlers/client.php
├─ handlers/kyc.php
└─ handlers/get_clients.php
    └─ All include: ../config/db.php
```

---

## ✅ **Complete Path Checklist**

| File | CSS Path | Config Path | Handler Path | Redirect Path |
|------|----------|-------------|-------------|---------------|
| auth/login.php | `../css/auth.css` | - | `../handlers/logins.php` | `../application/dashboard.php` |
| auth/register.php | `../css/auth.css` | - | `../handlers/register.php` | `auth/login.php` |
| auth/logout.php | - | `../config/session.php` | - | `auth/login.php` |
| application/dashboard.php | `../css/*.css` | `../config/session.php` | - | - |
| application/clients.php | `../css/*.css` | `../config/session.php` | `../handlers/{get_clients,client}.php` | - |
| application/kyc-verification.php | `../css/*.css` | `../config/session.php` | `../handlers/kyc.php` | `../application/dashboard.php` |
| application/policy.php | `../css/*.css` | `../config/session.php` | - | - |

---

## 🔄 **Dependencies Summary**

```
All Pages (auth + application)
    ↓
Require: ../(config/session.php)
    ↓
Calls: ../(config/db.php)
    ↓
Database: kyc_system
```

**Auth Pages:**
- `login.php` ← `logins.php` → Database (users table)
- `register.php` ← `register.php` → Database (users table)
- `logout.php` → Destroy session

**Application Pages:**
- All require session verification via `requireLogin()` from `../config/session.php`
- Submit forms to `../handlers/*.php` endpoints
- Handlers execute database queries via `../config/db.php` functions

---

## 🚀 **Adding New Features**

### **New Authentication Page**
```
1. Create file: auth/newauth.php
2. Include: ../css/auth.css
3. Create handler: handlers/newauth.php
4. Update fetch: ../handlers/newauth.php
```

### **New Application Page**
```
1. Create file: application/newpage.php
2. Include: ../config/session.php
3. Include: ../css/global.css (+ any specific CSS)
4. Create handler: handlers/newpage.php (if needed)
5. Update fetch: ../handlers/newpage.php
6. Add link in sidebar navigation
```

### **New Handler Endpoint**
```
1. Create file: handlers/newhandler.php
2. Include: ../config/db.php
3. Include: session_start()
4. Check: $_SESSION['user_id']
5. Implement database logic
6. Echo: json_encode($response)
```

---

## 📊 **Status**

| Component | Status | Location |
|-----------|--------|----------|
| Authentication | ✅ Complete | auth/ |
| Client Management | ✅ Complete | application/clients.php |
| KYC Verification | ✅ Complete | application/kyc-verification.php |
| Dashboard | ✅ Complete | application/dashboard.php |
| Policy Management | ⏳ Pending | application/policy.php |
| Document Upload | ⏳ In Progress | - |
| Reporting | ⏳ Pending | - |

---

## 🔐 **Security Notes**

- All pages require `requireLogin()` from `../config/session.php`
- All handlers verify `$_SESSION['user_id']` before processing
- All database queries use prepared statements with parameter binding
- No direct SQL injection possible
- Passwords hashed with SHA2(256) in database

---

## 📝 **Configuration Files**

**config/db.php:**
- Database connection: `kyc_system` database
- Helper functions: `fetchOne()`, `fetchAll()`, `insert()`, `update()`
- Reference code generator: `generateUniqueReferenceCode()`

**config/session.php:**
- Session validation: `requireLogin()`
- Session creation/destruction: `logout()`
- User authentication middleware

---

## ✨ **Version History**

- **v1.0** - Initial project structure (root level)
- **v1.1** - Phase 1: Authentication implementation
- **v1.2** - Phase 2: Client management (database type fix, dynamic table loading)
- **v1.3** - Phase 3: Auto-generated reference codes
- **v1.4** - Phase 4: Project reorganization (folders by type)

