# KYC Save Draft Feature - Comprehensive Analysis

## Executive Summary
The save draft feature has **multiple critical issues** including:
1. Missing `name` attribute on corporate street input field
2. Field name mismatches between form and handler (employer→company, telephone→phone)
3. Corporate fields completely lost during save_draft (no support in kyc_verifications table)
4. Architectural mismatch: kyc_verifications designed for individual clients only

---

## 1. INDIVIDUAL FORM - Field Names Sent vs Expected

### kyc-individual.php - Actual Form Fields Sent

```javascript
// saveDraft() collects all form fields:
const elements = form.querySelectorAll('input, select, textarea');
elements.forEach(el => {
    if (el.name) {
        formData.append(el.name, el.value);
    }
});
```

**Fields sent to save_draft handler:**
- `clientType` = "individual"
- `refCode`
- `clientNumber`
- `lastName` ✓
- `firstName` ✓
- `middleName` ✓
- `birthdate` ✓
- `gender` ✓
- `occupation` ✓
- `employer` ← **Mismatch: handler expects `company`**
- `businessRegion`
- `businessProvince`
- `businessCtm`
- `businessBarangay`
- `businessStreet`
- `businessAddress`
- `homeRegion`
- `homeProvince`
- `homeCtm`
- `homeBarangay`
- `homeStreet`
- `homeAddress` ✓
- `mobile` ✓
- `telephone` ← **Mismatch: handler expects `phone`**
- `email` ✓
- `clientClassification`
- `documents[]`

### kyc.php Handler - What It Expects

```php
// Line 188-210: save_draft extracts these fields:
$formData = [
    'ref_code' => trim($_POST['refCode'] ?? ''),            // ✓ Match
    'client_type' => trim($_POST['clientType'] ?? ''),      // ✓ Match
    'last_name' => trim($_POST['lastName'] ?? ''),          // ✓ Match
    'first_name' => trim($_POST['firstName'] ?? ''),        // ✓ Match
    'middle_name' => trim($_POST['middleName'] ?? ''),      // ✓ Match
    'suffix' => trim($_POST['suffixName'] ?? ''),           // ✗ FORM NEVER SENDS "suffixName"
    'birthdate' => trim($_POST['birthdate'] ?? ''),         // ✓ Match
    'gender' => trim($_POST['gender'] ?? ''),               // ✓ Match
    'nationality' => trim($_POST['nationality'] ?? ''),     // ✗ FORM NEVER SENDS "nationality"
    'id_type' => trim($_POST['idType'] ?? ''),              // ✗ FORM NEVER SENDS "idType"
    'id_number' => trim($_POST['idNumber'] ?? ''),          // ✗ FORM NEVER SENDS "idNumber"
    'occupation' => trim($_POST['occupation'] ?? ''),       // ✓ Match
    'company' => trim($_POST['company'] ?? ''),             // ✗ FORM SENDS "employer" INSTEAD!
    'mobile' => trim($_POST['mobile'] ?? ''),               // ✓ Match
    'phone' => trim($_POST['phone'] ?? ''),                 // ✗ FORM SENDS "telephone" INSTEAD!
    'email' => trim($_POST['email'] ?? ''),                 // ✓ Match
    'address' => trim($_POST['homeAddress'] ?? $_POST['address'] ?? '')  // ✓ Match (homeAddress)
];
```

**Result:** Individual form has:
- ✓ 10 fields matching correctly
- ✗ 2 field name mismatches (employer, telephone)
- ✗ 3 fields never sent but expected (suffixName, nationality, idType, idNumber)

---

## 2. CORPORATE FORM - Critical Issues

### kyc-corporate.php - Actual Form Fields Sent

**MAJOR BUG on line 288:**
```html
<!-- MISSING name attribute! Should be name="corporateStreet" -->
<label for="corporateStreet" class="form-label">Street / Unit / Building <span class="req">*</span></label>
<input type="text" id="corporateStreet" class="form-control" placeholder="House/Unit No., Street, Building" required>
```

**Fields actually sent:**
- `clientType` = "corporate"
- `refCode`
- `clientNumber`
- `corporateClientName` ← **NOT in kyc_verifications table!**
- `businessType` ← **NOT in kyc_verifications table!**
- `corporateClientSince` ← **NOT in kyc_verifications table!**
- `tinNumber` ← **NOT in kyc_verifications table!**
- `corporateApSlCode` ← **NOT in kyc_verifications table!**
- `corporateArSlCode` ← **NOT in kyc_verifications table!**
- `designation` ← **NOT in kyc_verifications table!**
- `region`
- `corporateBusinessProvince`
- `corporateBusinessCtm`
- `corporateBusinessBarangay`
- ~~`corporateStreet`~~ ← **NOT SENT - missing name attribute!**
- `corporateBusinessAddress` (the composed address)
- `corporatePhone` ← **NOT in kyc_verifications table!**
- `corporateContactPerson` ← **NOT in kyc_verifications table!**
- `corporateEmail`
- `corporateGender`
- `clientClassification`

---

## 3. DATABASE SCHEMA ANALYSIS

### kyc_verifications Table Structure
```sql
CREATE TABLE `kyc_verifications` (
  `kyc_id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `client_id` int(11) NOT NULL FOREIGN KEY,
  `reference_code` varchar(50),
  `ref_code` varchar(50),
  `client_type` varchar(20),
  
  -- INDIVIDUAL-ORIENTED FIELDS:
  `last_name` varchar(50),
  `first_name` varchar(50),
  `middle_name` varchar(50),
  `suffix` varchar(10),
  `birthdate` date,
  `gender` varchar(20),
  `nationality` varchar(50),
  `id_type` varchar(50),
  `id_number` varchar(50),
  `occupation` varchar(100),
  `company` varchar(100),
  `mobile` varchar(20),
  `phone` varchar(20),
  `email` varchar(120),
  `address` varchar(255),
  
  -- EMPTY - NO CORPORATE FIELDS!
  -- Missing:
  -- - business_type
  -- - tin_number
  -- - ap_sl_code
  -- - ar_sl_code
  -- - designation
  -- - contact_person
  -- - office_phone
  
  `status` enum('draft','in_progress','submitted','approved','rejected'),
  `step_current` int(11),
  `step_1_completed` tinyint(1),
  `step_2_completed` tinyint(1),
  `step_3_completed` tinyint(1),
  `step_4_completed` tinyint(1),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE,
  `submitted_at` datetime
);
```

### clients Table (where corporate data SHOULD go)
```sql
CREATE TABLE `clients` (
  -- Supports BOTH individual and corporate:
  `client_id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `client_type` enum('individual','corporate'),
  
  -- Individual fields:
  `first_name` varchar(50),
  `last_name` varchar(50),
  `middle_name` varchar(50),
  `date_of_birth` date,
  
  -- Corporate fields:
  `company_name` varchar(100),
  `business_type` enum('private','government'),
  `contact_person` varchar(100),
  `designation` varchar(100),
  `tin_number` varchar(50),
  `ap_sl_code` varchar(50),
  `ar_sl_code` varchar(50),
  `office_phone` varchar(20),
  `business_address` varchar(255),
  
  -- Both:
  `email` varchar(120),
  `verification_status` enum('draft','pending','verified','rejected'),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
);
```

---

## 4. SAVE DRAFT HANDLER FLOW (kyc.php)

### Current Save Draft Implementation Issues

**Line 185-225: For ALL client types (both individual and corporate!):**

```php
if ($action === 'save_draft' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userProvidedRefCode = trim($_POST['refCode'] ?? '');
    
    // ← PROBLEM: Uses same formData extraction for both types!
    $formData = [
        'ref_code' => $userProvidedRefCode,
        'client_type' => trim($_POST['clientType'] ?? ''),
        'last_name' => trim($_POST['lastName'] ?? ''),      // ← Individual only!
        'first_name' => trim($_POST['firstName'] ?? ''),    // ← Individual only!
        // ... etc (all individual-named fields)
    ];
    
    // Creates client in clients table
    $clientInsert = insert('clients', [
        'reference_code' => $formData['ref_code'],
        'client_type' => $formData['client_type'] ?: 'individual',
        'first_name' => $formData['first_name'] ?: null,
        'last_name' => $formData['last_name'] ?: null,
        // ... etc
    ]);
    
    // Creates/updates KYC record (kyc_verifications table)
    // ↓ CRITICAL: Tries to save all fields, but corporate fields don't exist in table!
    $kycInsert = insert('kyc_verifications', array_merge($formData, [
        'client_id' => $clientId,
        'reference_code' => $formData['ref_code'],
        'status' => 'draft',
        'step_current' => 1
    ]));
}
```

**What Actually Gets Saved to kyc_verifications:**

For **individual** client:
- ✓ ref_code, client_type, first_name, last_name, middle_name
- ✓ birthdate, gender, occupation, mobile, email, address
- ✗ company = EMPTY (form sends "employer", not "company")
- ✗ phone = EMPTY (form sends "telephone", not "phone")
- ✗ suffix, nationality, id_type, id_number = EMPTY (never sent)

For **corporate** client:
- ✓ ref_code, client_type, email
- ✗ first_name = EMPTY
- ✗ last_name = EMPTY
- ✗ ALL corporate fields = LOST! (corporateClientName, businessType, tinNumber, etc.)
- ✗ No street component saved (missing name attribute)

---

## 5. EXACT FIELD MISMATCHES SUMMARY

| Field Purpose | Form Input Name | Handler Extracts | Database Column | Issue |
|---|---|---|---|---|
| Street/Unit | employer (ind.) | company | company | Name mismatch |
| Phone | telephone | phone | phone | Name mismatch |
| Suffix | (not sent) | suffixName | suffix | Never sent |
| Nationality | (not sent) | nationality | nationality | Never sent |
| ID Type | (not sent) | idType | id_type | Never sent |
| ID Number | (not sent) | idNumber | id_number | Never sent |
| **Corporate Company** | corporateClientName | *(ignored)* | *(N/A)* | Handler doesn't extract |
| **Corporate Street** | *(missing name)* | *(not sent)* | *(N/A)* | **CRITICAL BUG** |
| **Corporate Phone** | corporatePhone | *(ignored)* | *(N/A)* | Handler doesn't extract |
| **Corporate Contact** | corporateContactPerson | *(ignored)* | *(N/A)* | Handler doesn't extract |

---

## 6. ROOT CAUSE ANALYSIS

### Why save_draft is Breaking

1. **Architectural Flaw:**
   - `kyc_verifications` table only designed for individual clients
   - Handler uses same logic for both individual and corporate
   - Corporate data fields don't exist in kyc_verifications

2. **Form Field Naming Issues:**
   - Individual: `employer` vs handler's `company` 
   - Individual: `telephone` vs handler's `phone`
   - Corporate: Missing `name` attribute on street input

3. **Data Flow Mismatch:**
   - Individual data → kyc_verifications table (mostly works)
   - Corporate data → kyc_verifications table (FAILS - columns don't exist)
   - Corporate data → clients table (works perfectly)

### Impact

**Individual drafts:**
- Missing: suffix, nationality, id_type, id_number
- Mismatched: company and phone fields empty
- Partially works but incomplete

**Corporate drafts:**
- **Total data loss!** All company-specific fields (name, business type, TIN, contact person) not saved to kyc_verifications
- Corporate street value never sent (missing name attribute)
- Only basic data (refCode, clientType, email) saved

---

## 7. DATABASE CONSTRAINT VIOLATIONS

### kyc_verifications.client_id NOT NULL

The handler creates a `clients` record to satisfy this NOT NULL constraint:

```php
// kyc.php line 201-240: Creates client first
$clientInsert = insert('clients', [ ... ]);
$clientId = intval($clientInsert['id'] ?? 0);

// Then creates kyc_verifications with client_id reference
$kycInsert = insert('kyc_verifications', [
    'client_id' => $clientId,  // ← Satisfies NOT NULL
    ...
]);
```

This works, but the kyc_verifications data is **never used** for corporate clients - it's incomplete.

---

## 8. RECOMMENDATIONS FOR FIXES

### Immediate Fixes Required:

1. **Add name attribute to corporateStreet input** (kyc-corporate.php:288)
   ```html
   <!-- Fix: Add name="corporateStreet" -->
   <input type="text" id="corporateStreet" name="corporateStreet" class="form-control" placeholder="House/Unit No., Street, Building" required>
   ```

2. **Fix field name mappings in kyc.php save_draft:**
   ```php
   'company' => trim($_POST['employer'] ?? $_POST['company'] ?? ''),  // Handle both names
   'phone' => trim($_POST['telephone'] ?? $_POST['phone'] ?? ''),      // Handle both names
   ```

3. **Handle corporate data separately:**
   - Extract corporate-specific fields for corporateclients
   - Save them to clients table (already works)
   - OR: Extend kyc_verifications with corporate columns
   - OR: Create separate kyc_corporate_data table

4. **Add missing individual fields:**
   ```php
   'suffix' => trim($_POST['suffixName'] ?? ''),
   'nationality' => trim($_POST['nationality'] ?? ''),
   'id_type' => trim($_POST['idType'] ?? ''),
   'id_number' => trim($_POST['idNumber'] ?? ''),
   ```

---

## 9. ERROR LOGS TO CHECK

When save_draft fails, check:
- **Browser console:** JavaScript errors in fetch response
- **PHP error log:** Database query errors
- **Browser Network tab:** POST to `../handlers/kyc.php` response JSON
- **Database:** Check if `clients` record created but `kyc_verifications` failed
- **Database:** Check if `kyc_verifications` has NULL values where data should exist

---

## 10. TESTING CHECKLIST

- [ ] Individual draft: All fields exist in kyc_verifications
- [ ] Individual draft: company field NOT empty (must extract from "employer")
- [ ] Individual draft: phone field NOT empty (must extract from "telephone")
- [ ] Corporate draft: Street value sent (add name attribute)
- [ ] Corporate draft: All business data exists (verify storage approach)
- [ ] Corporate draft: No NULL constraint violations
- [ ] Test with and without reference code (auto-generation)
- [ ] Test draft overwrite (update existing draft)

