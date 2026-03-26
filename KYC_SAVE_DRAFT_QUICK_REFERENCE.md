# KYC Save Draft - Quick Reference

## CRITICAL FINDINGS

### 🔴 CRITICAL BUG #1: Missing name Attribute (kyc-corporate.php:288)
```html
<!-- BROKEN: Missing name="corporateStreet" -->
<input type="text" id="corporateStreet" class="form-control" required>

<!-- SHOULD BE: -->
<input type="text" id="corporateStreet" name="corporateStreet" class="form-control" required>
```
**Impact:** Corporate street data never sent to handler

---

### 🔴 CRITICAL BUG #2: Corporate Data Completely Lost
**kyc_verifications table has NO fields for:**
- corporateClientName
- businessType
- tinNumber
- corporateApSlCode
- corporateArSlCode
- designation
- corporatePhone
- corporateContactPerson

**Result:** All corporate KYC data lost when saving draft!

---

### 🟡 FIELD NAME MISMATCHES

| What Form Sends | What Handler Expects | Result |
|---|---|---|
| `employer` | `company` | **EMPTY - data lost** |
| `telephone` | `phone` | **EMPTY - data lost** |
| `suffixName` | *(never sent)* | **EMPTY** |
| `nationality` | *(never sent)* | **EMPTY** |
| `idType` | *(never sent)* | **EMPTY** |
| `idNumber` | *(never sent)* | **EMPTY** |

---

## FORM FIELDS BEING SENT

### Individual Form - saveDraft() Fields
**Correctly received:**
- clientType, refCode, firstName, lastName, middleName, birthdate
- gender, mobile, email, homeAddress, occupation
- clientClassification

**Mismatched (lost on entry):**
- employer → expects "company" (EMPTY in DB)
- telephone → expects "phone" (EMPTY in DB)

**Never sent but expected:**
- suffixName, nationality, idType, idNumber (EMPTY in DB)

---

### Corporate Form - saveDraft() Fields  
**Sent but NOT EXTRACTED by handler:**
- corporateClientName, businessType, corporateClientSince
- tinNumber, corporateApSlCode, corporateArSlCode
- designation, corporatePhone, corporateContactPerson

**Critical issue:**
- corporateStreet has NO name attribute (never sent at all!)

---

## WHAT ACTUALLY GETS SAVED TO kyc_verifications

### For Individual Client:
✓ Saved: ref_code, client_type, first_name, last_name, middle_name, birthdate, gender, occupation, mobile, email, address, client_id

✗ Empty: company (should be employer), phone (should be telephone), suffix, nationality, id_type, id_number

### For Corporate Client:
✓ Saved: ref_code, client_type, email, client_id

✗ Lost: corporateClientName, businessType, tinNumber, contactPerson, phone, address components, gender

✗ Never sent: corporateStreet (missing name attribute)

---

## DATABASE TABLE DESIGN ISSUES

### kyc_verifications (INDIVIDUAL-ONLY DESIGN)
```
Columns: ref_code, client_type, first_name, last_name, middle_name,
         suffix, birthdate, gender, nationality, id_type, id_number,
         occupation, company, mobile, phone, email, address
         
Missing for Corporate: business_type, tin_number, ap_sl_code, 
         ar_sl_code, designation, contact_person, office_phone
```

### clients (SUPPORTS BOTH)
```
Has all fields for individual AND corporate clients
But kyc_verifications tries to replicate data poorly
```

---

## HANDLER EXTRACTION LOGIC (kyc.php:188-225)

```php
// saveDraft extracts from $_POST:
$formData = [
    'ref_code' => $_POST['refCode'],              // ✓
    'client_type' => $_POST['clientType'],        // ✓
    'first_name' => $_POST['firstName'],          // ✓
    'last_name' => $_POST['lastName'],            // ✓
    'middle_name' => $_POST['middleName'],        // ✓
    'suffix' => $_POST['suffixName'],             // ✗ (none sent)
    'birthdate' => $_POST['birthdate'],           // ✓
    'gender' => $_POST['gender'],                 // ✓
    'nationality' => $_POST['nationality'],       // ✗ (none sent)
    'id_type' => $_POST['idType'],                // ✗ (none sent)
    'id_number' => $_POST['idNumber'],            // ✗ (none sent)
    'occupation' => $_POST['occupation'],         // ✓
    'company' => $_POST['company'],               // ✗ (form sends "employer")
    'mobile' => $_POST['mobile'],                 // ✓
    'phone' => $_POST['phone'],                   // ✗ (form sends "telephone")
    'email' => $_POST['email'],                   // ✓
    'address' => $_POST['homeAddress'] ??
                 $_POST['address']                // ✓
];

// NO logic for corporate fields!
// corporateClientName, businessType, tinNumber, etc. ignored
```

---

## FLOW DIAGRAM

```
USER SAVES DRAFT (Individual)
  ↓
Form sends: firstName, lastName, telephone, employer, ...
  ↓
saveDraft() collects all form fields with name attribute
  ↓
POST to kyc.php with action=save_draft
  ↓
Handler extracts to $formData (mismatches happen here)
  ↓
Create/update clients table ✓ (works)
  ↓
Create/update kyc_verifications table
  ↓ (mostly works but company/phone empty)
✓ Partially successful - data loss on company, phone, identity fields

---

USER SAVES DRAFT (Corporate)
  ↓
Form sends: corporateClientName, businessType, corporatePhone, 
            designation, corporateStreet (MISSING NAME!), ...
  ✗ corporateStreet never sent (no name attribute)
  ↓
saveDraft() collects ALL form fields with name attribute
  ↓
POST to kyc.php with corporate fields INCLUDED
  ↓
Handler extracts ONLY individual fields, ignores corporate data!
  ↓
Create clients table ✓ (works - all data there)
  ↓
Create kyc_verifications table
  ✗ NO corporate data saved to this table
  ✗ kyc_verifications has NO columns for corporate fields!
✗ COMPLETE FAILURE - corporate data lost to kyc_verifications
```

---

## WHERE TO LOOK FOR ERRORS

1. **Browser Console** - Check for JavaScript errors in saveDraft()
2. **Network Tab** - POST response from kyc.php (check JSON error)
3. **PHP Logs** - Database query errors
4. **Database Queries** - Check what SQL is being generated
5. **kyc_verifications rows** - Verify NULL values where data should be
6. **clients rows** - Verify data saved (if only going to clients table)

---

## SEVERITY

| Issue | Severity | Affects |
|---|---|---|
| Missing corporateStreet name | 🔴 CRITICAL | All corporate drafts lose address |
| Corporate data not in table | 🔴 CRITICAL | All corporate KYC tracking broken |
| Field name mismatches | 🟡 HIGH | Some individual KYC data lost |
| Missing identity fields | 🟡 HIGH | Incomplete individual KYC records |

