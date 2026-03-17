# KYC System - Project File Organization

## 📁 Directory Structure by File Type

---

## 🔵 **1. AUTHENTICATION & CORE PAGES**

### User Authentication Pages
| File | Purpose | Form Name | Submits To | Status |
|------|---------|-----------|-----------|--------|
| `login.php` | User login form | Login Form | `handlers/logins.php` | ✅ Active |
| `register.php` | User registration | Register Form | `handlers/register.php` | ✅ Active |
| `logout.php` | Logout handler | - | Session destroy | ✅ Active |
| `index.php` | Landing/Redirect page | - | Redirects to `login.php` | ✅ Active |

### Main Application Pages
| File | Purpose | Form Name(s) | Submits To | Status |
|------|---------|--------------|-----------|--------|
| `dashboard.php` | Main dashboard | - | Display metrics & stats | ✅ Active |
| `clients.php` | Client management | Edit Client Modal | `handlers/client.php` (action: `edit_client`, `delete_client`) | ✅ Active |
| `kyc-verification.php` | KYC form & verification | KYC Verification Form | `handlers/kyc.php` (actions: `submit_kyc`, `save_draft`) | ✅ Active |
| `policy.php` | Policy management | - | TBD | ⏳ Pending |

---

## 🔧 **2. CONFIGURATION & DATABASE**

### Database Configuration
| File | Purpose | Dependencies |
|------|---------|--------------|
| `config/db.php` | Database connection & helper functions | MySQLi, `config/session.php` |
| `config/session.php` | Session management & authentication | `config/db.php` |

### Database Setup
| File | Purpose | Type |
|------|---------|------|
| `database.sql` | Complete database schema | SQL Script |

**Helper Functions in `config/db.php`:**
- `generateUniqueReferenceCode()` - Auto-generates unique reference codes (REF-YYYYMMDD-XXXXX)
- `executeQuery($query, $params)` - Execute prepared statements
- `fetchOne($query, $params)` - Fetch single row
- `fetchAll($query, $params)` - Fetch multiple rows
- `insert($table, $data)` - Insert records with proper parameter binding
- `update($table, $data, $where, $whereParams)` - Update records
- `requireLogin()` - Session validation middleware

---

## 🔗 **3. BACKEND HANDLERS (API Endpoints)**

### Handler Files Location: `handlers/`

#### Authentication Handler
| Handler | Method | Action(s) | Form Source | Connects To |
|---------|--------|-----------|-------------|------------|
| `logins.php` | POST | Authenticate user | `login.php` | Session creation, redirect to `dashboard.php` |

#### User Registration Handler
| Handler | Method | Action(s) | Form Source | Connects To |
|---------|--------|-----------|-------------|------------|
| `register.php` | POST | Register new user | `register.php` | User creation & validation |

#### Client Management Handler
| Handler | Method | Action(s) | Form Source | Connects To |
|---------|--------|-----------|-------------|------------|
| `client.php` | POST | `add_client` | `kyc-verification.php` (via redirect) | Clients table creation |
| | | `edit_client` | `clients.php` (edit modal) | Clients table update |
| | | `delete_client` | `clients.php` (delete button) | Clients table deletion |

#### KYC Verification Handler
| Handler | Method | Action(s) | Form Source | Connects To |
|---------|--------|-----------|-------------|------------|
| `kyc.php` | POST | `submit_kyc` | `kyc-verification.php` | KYC verifications table, Clients table |
| | | `save_draft` | `kyc-verification.php` | KYC verifications table (draft status) |
| | | `get_kyc` | JavaScript fetch | Populate form with saved data |

#### Client Data Fetch Handler
| Handler | Method | Action(s) | Form Source | Connects To |
|---------|--------|-----------|-------------|------------|
| `get_clients.php` | GET | Fetch all clients | `clients.php` (on page load) | Renders dynamic client table |

---

## 🎨 **4. STYLESHEETS (CSS)**

### CSS Files Location: `css/`

| File | Used By | Purpose |
|------|---------|---------|
| `auth.css` | `login.php`, `register.php` | Authentication page styling |
| `index.css` | All pages (layout) | Main layout & grid styling |
| `global.css` | All pages | Global styles, variables, utilities |
| `clients.css` | `clients.php` | Client table & modal styling |
| `dashboard.css` | `dashboard.php` | Dashboard cards & widgets styling |

---

## 🖼️ **5. ASSETS**

### Images & Media
| File | Type | Used By | Purpose |
|------|------|---------|---------|
| `SterlingLogo.png` | PNG | All pages (header) | Company logo |
| `SterlingLogo2.png` | PNG | Alternative logo | Company branding |
| `building_stock.jpg` | JPG | Dashboard/Background | Stock image |
| `guilloche1.png` | PNG | Decorative elements | Background pattern |
| `guilloche2.png` | PNG | Decorative elements | Background pattern |
| `guilloche3.png` | PNG | Decorative elements | Background pattern |

---

## 📖 **6. DOCUMENTATION & TESTING**

### Documentation Files
| File | Purpose |
|------|---------|
| `DATABASE_SETUP.md` | Database setup instructions |
| `FILE_REFERENCE.md` | File reference guide |
| `HANDLERS_GUIDE.md` | Handler endpoints documentation |
| `IMPLEMENTATION_SUMMARY.md` | System implementation overview |
| `SETUP_AND_TESTING.md` | Setup & testing guide |
| `PROJECT_STRUCTURE.md` | This file - project organization |

### Testing Files
| File | Purpose | Type |
|------|---------|------|
| `test-login.php` | Login authentication testing | Diagnostic tool |

---

## 📊 **FORM → HANDLER → DATABASE FLOW**

### Authentication Flow
```
login.php (Form: Email, Password)
    ↓
handlers/logins.php
    ↓
Config/db.php (fetchOne - SELECT from users)
    ↓
Session creation & redirect to dashboard.php
```

### Client Registration/KYC Flow
```
kyc-verification.php (Form: Client Details, KYC Info)
    ↓
handlers/kyc.php (action: submit_kyc or save_draft)
    ↓
Config/db.php (insert/update - clients, kyc_verifications)
    ↓
Auto-generates unique reference code (REF-YYYYMMDD-XXXXX)
    ↓
Response with reference_code → Front-end displays & reloads table
```

### Client Management Flow
```
clients.php (Table Display + Modals)
    ↓
1. Load Action: handlers/get_clients.php → Fetch all clients
    ↓
    Render dynamic table rows
    
2. Edit Action: handlers/client.php (action: edit_client)
    ↓
    Update clients table
    
3. Delete Action: handlers/client.php (action: delete_client)
    ↓
    Delete from clients table & remove row from UI
```

---

## 🗄️ **DATABASE TABLES**

```
kyc_system (Database)
├── users (Authentication)
├── clients (Client Information)
├── kyc_verifications (KYC Form Data)
├── documents (Document Storage)
├── verification_history (Audit Trail)
└── audit_logs (System Logs)
```

**Key Fields:**
- **reference_code** (clients, kyc_verifications): Unique auto-generated identifier
- **client_number**: Auto-generated client identifier (CN-TIMESTAMP)
- **verification_status**: pending, verified, rejected
- **client_type**: individual, corporate

---

## 🔄 **DEPENDENCIES MAP**

```
All Pages
    ↓
config/session.php (requireLogin middleware)
    ↓
config/db.php (Database functions)
    ↓
Database (kyc_system)
    
All Forms
    ↓
JavaScript (AJAX fetch)
    ↓
handlers/* (PHP endpoints)
    ↓
config/db.php (Insert/Update/Select)
    ↓
Database operations
    ↓
JSON response → Front-end JS → UI update/toast notification
```

---

## 📋 **QUICK REFERENCE: What Goes Where**

### Adding a New Form
1. Create form in page file (e.g., `newpage.php`)
2. Form submits to `handlers/newhandler.php` via AJAX
3. Handler uses functions from `config/db.php`
4. Returns JSON response
5. Front-end displays toast & refreshes table (if applicable)

### Adding New Page
1. Create `newpage.php` in root
2. Include `config/session.php` at top (requireLogin)
3. Include CSS files from `css/`
4. Create form or data display
5. Link from `dashboard.php` (sidebar navigation)

### Database Changes
1. Update schema in `database.sql`
2. Update insert/update/select functions in handlers
3. Update helper functions in `config/db.php` if needed

---

## ✅ **Status Summary**

| Component | Status |
|-----------|--------|
| Authentication | ✅ Complete |
| Client Management | ✅ Complete |
| KYC Verification | ✅ Complete (auto-generated ref codes) |
| Dynamic table loading | ✅ Complete |
| Form validation | ✅ Complete |
| Session management | ✅ Complete |
| Policy Management | ⏳ Pending |
| Document Upload | ⏳ In Progress |
| Reporting | ⏳ Pending |

