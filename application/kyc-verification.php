<?php
require_once '../config/session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC System — Select Client Type</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/global.css">
    <style>
        .type-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }
        
        .type-card {
            background: white;
            border: 2px solid var(--border-gray);
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .type-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .type-card-icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .type-card-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-dark);
        }
        
        .type-card-desc {
            color: var(--gray-500);
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .type-card-btn {
            display: inline-block;
            padding: 10px 30px;
            background: var(--primary);
            color: white;
            border-radius: 4px;
            font-weight: 500;
            transition: background 0.2s ease;
        }
        
        .type-card:hover .type-card-btn {
            background: var(--primary-dark, #0056b3);
        }
    </style>
</head>
<body>

<?php
$activePage = 'kyc-verification';
include '../includes/sidebar.php';
?>

<!-- ═══════════════════════════════════════════════ MAIN -->
<div class="main">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <h1>KYC Verification</h1>
            <div class="breadcrumb-trail">
                <i class="bi bi-house" style="font-size:.65rem;"></i>
                Dashboard &rsaquo; Clients &rsaquo; <span>Select Client Type</span>
            </div>
        </div>
        <div class="topbar-right">
        </div>
    </header>

    <!-- Content -->
    <main class="content">


        <!-- Steps -->
        <div class="steps-bar">
            <div class="step active" id="step-1">
                <div class="step-num">1</div>
                <div class="step-info">
                    <span>Step 1</span>
                    <strong>Client Type</strong>
                </div>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step-2">
                <div class="step-num">2</div>
                <div class="step-info">
                    <span>Step 2</span>
                    <strong>Client Details</strong>
                </div>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step-3">
                <div class="step-num">3</div>
                <div class="step-info">
                    <span>Step 3</span>
                    <strong>Documents</strong>
                </div>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step-4">
                <div class="step-num">4</div>
                <div class="step-info">
                    <span>Step 4</span>
                    <strong>Review</strong>
                </div>
            </div>
        </div>

        <!-- Client Type Selection -->
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Select Client Type</div>
                    <div class="card-subtitle">Choose the type of client you're registering for KYC verification</div>
                </div>
            </div>

            <div class="card-body">
                <div class="type-selector">
                    <!-- Individual Client Card -->
                    <a href="kyc-individual.php" class="type-card">
                        <div class="type-card-icon">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div class="type-card-title">Individual Client</div>
                        <div class="type-card-desc">
                            Register a natural person as a client. Complete personal information and identity verification.
                        </div>
                        <div class="type-card-btn">Select Individual</div>
                    </a>

                    <!-- Corporate Client Card -->
                    <a href="kyc-corporate.php" class="type-card">
                        <div class="type-card-icon">
                            <i class="bi bi-building"></i>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lastName" class="form-label">Last Name <span class="req">*</span></label>
                                    <input type="text" id="lastName" name="lastName" class="form-control" placeholder="Dela Cruz" required>
                                    <div class="form-error">Last name is required</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName" class="form-label">First Name <span class="req">*</span></label>
                                    <input type="text" id="firstName" name="firstName" class="form-control" placeholder="Juan" required>
                                    <div class="form-error">First name is required</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="middleName" class="form-label">Middle Name</label>
                                    <input type="text" id="middleName" name="middleName" class="form-control" placeholder="Optional">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div style="display:flex;gap:20px;margin:10px 0;">
                                    <label style="display:flex;align-items:center;gap:8px;">
                                        <input type="checkbox" id="lastNameFirst" name="lastNameFirst"> Last Name First
                                    </label>
                                    <label style="display:flex;align-items:center;gap:8px;">
                                        <input type="checkbox" id="commaSeparated" name="commaSeparated"> Comma Separated
                                    </label>
                                    <label style="display:flex;align-items:center;gap:8px;">
                                        <input type="checkbox" id="middleInitialOnly" name="middleInitialOnly"> Middle Name Initial Only
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- ── Section: Personal Details ── -->
                        <div class="section-divider">
                            <span class="section-divider-label"><i class="bi bi-card-list"></i> Personal Details</span>
                            <div class="section-divider-line"></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="birthdate" class="form-label">Date of Birth <span class="req">*</span></label>
                                    <input type="date" id="birthdate" name="birthdate" class="form-control" required>
                                    <div class="form-error">Date of birth is required</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="clientSince" class="form-label">Client Since</label>
                                    <input type="date" id="clientSince" name="clientSince" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="gender" class="form-label">Gender</label>
                                    <div class="select-wrap">
                                        <select id="gender" name="gender" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nationality" class="form-label">Nationality</label>
                                    <input type="text" id="nationality" name="nationality" class="form-control" placeholder="Philippine">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="apSlCode" class="form-label">AP SL Code</label>
                                    <input type="text" id="apSlCode" name="apSlCode" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="arSlCode" class="form-label">AR SL Code</label>
                                    <input type="text" id="arSlCode" name="arSlCode" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- ── Section: Occupation ── -->
                        <div class="section-divider">
                            <span class="section-divider-label"><i class="bi bi-briefcase"></i> Occupation</span>
                            <div class="section-divider-line"></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="occupation" class="form-label">Occupation <span class="req">*</span></label>
                                    <input type="text" id="occupation" name="occupation" class="form-control" placeholder="Your occupation" required>
                                    <div class="form-error">Occupation is required</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="company" class="form-label">Company Name</label>
                                    <input type="text" id="company" name="company" class="form-control" placeholder="Company name">
                                </div>
                            </div>
                        </div>

                        <!-- ── Section: Business Address ── -->
                        <div class="section-divider">
                            <span class="section-divider-label"><i class="bi bi-shop"></i> Business Address</span>
                            <div class="section-divider-line"></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="businessAddress" class="form-label">Business Address</label>
                                    <input type="text" id="businessAddress" name="businessAddress" class="form-control" placeholder="Street, Barangay, City">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="businessCtm" class="form-label">CTM</label>
                                    <input type="text" id="businessCtm" name="businessCtm" class="form-control" placeholder="City Code">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="businessProvince" class="form-label">Province</label>
                                    <input type="text" id="businessProvince" name="businessProvince" class="form-control" placeholder="Province">
                                </div>
                            </div>
                        </div>

                        <!-- ── Section: Home Address ── -->
                        <div class="section-divider">
                            <span class="section-divider-label"><i class="bi bi-house"></i> Home Address</label></span>
                            <div class="section-divider-line"></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="homeAddress" class="form-label">Home Address <span class="req">*</span></label>
                                    <input type="text" id="homeAddress" name="homeAddress" class="form-control" placeholder="Street, Barangay, City" required>
                                    <div class="form-error">Home address is required</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="homeCtm" class="form-label">CTM</label>
                                    <input type="text" id="homeCtm" name="homeCtm" class="form-control" placeholder="City Code">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="homeProvince" class="form-label">Province</label>
                                    <input type="text" id="homeProvince" name="homeProvince" class="form-control" placeholder="Province">
                                </div>
                            </div>
                        </div>

                        <!-- ── Section: Contact Details ── -->
                        <div class="section-divider">
                            <span class="section-divider-label"><i class="bi bi-telephone"></i> Contact Information</span>
                            <div class="section-divider-line"></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="officePhone" class="form-label">Office Phone</label>
                                    <input type="tel" id="officePhone" name="officePhone" class="form-control" placeholder="(02) 8000 0000">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="homePhone" class="form-label">Home Phone</label>
                                    <input type="tel" id="homePhone" name="homePhone" class="form-control" placeholder="(02) 8000 0000">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="mobile" class="form-label">Mobile Phone <span class="req">*</span></label>
                                    <input type="tel" id="mobile" name="mobile" class="form-control" placeholder="+63 900 000 0000" required>
                                    <div class="form-error">Mobile phone is required</div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address <span class="req">*</span></label>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="user@example.com" required>
                                    <div class="form-error">Valid email is required</div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Section: Spouse Information ── -->
                        <div class="section-divider">
                            <span class="section-divider-label"><i class="bi bi-person-check"></i> Spouse Information</span>
                            <div class="section-divider-line"></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="spouseName" class="form-label">Spouse Name</label>
                                    <input type="text" id="spouseName" name="spouseName" class="form-control" placeholder="Full name">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="spouseBirthdate" class="form-label">Spouse Birthdate</label>
                                    <input type="date" id="spouseBirthdate" name="spouseBirthdate" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="spouseOccupation" class="form-label">Spouse Occupation</label>
                                    <input type="text" id="spouseOccupation" name="spouseOccupation" class="form-control" placeholder="Occupation">
                                </div>
                            </div>
                        </div>

                        <!-- ── Section: Mailing Address ── -->
                        <div class="section-divider">
                            <span class="section-divider-label"><i class="bi bi-envelope"></i> Mailing Address</span>
                            <div class="section-divider-line"></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <div style="display:flex;gap:20px;">
                                    <label style="display:flex;align-items:center;gap:8px;">
                                        <input type="radio" id="mailingBusiness" name="mailingAddressType" value="business"> Business Address
                                    </label>
                                    <label style="display:flex;align-items:center;gap:8px;">
                                        <input type="radio" id="mailingHome" name="mailingAddressType" value="home"> Home Address
                                    </label>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ════════════════════════════════════════════════════════════════ -->
                    <!-- CORPORATE CLIENT FORM -->
                    <!-- ════════════════════════════════════════════════════════════════ -->
                    <div id="corporateSection" style="display:none;">

                        <!-- ── Section: Company Name ── -->
                        <div class="section-divider">
                            <span class="section-divider-label"><i class="bi bi-building"></i> Company Information</span>
                            <div class="section-divider-line"></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="corporateClientName" class="form-label">Client Name <span class="req">*</span></label>
                                    <input type="text" id="corporateClientName" name="corporateClientName" class="form-control" placeholder="Company Name" required>
                                    <div class="form-error">Client name is required</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Business Type <span class="req">*</span></label>
                                    <div style="display:flex;gap:20px;margin-top:8px;">
                                        <label style="display:flex;align-items:center;gap:8px;">
                                            <input type="radio" id="businessPrivate" name="businessType" value="private" required> Private
                                        </label>
                                        <label style="display:flex;align-items:center;gap:8px;">
                                            <input type="radio" id="businessGov" name="businessType" value="government" required> Government
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="corporateClientSince" class="form-label">Client Since</label>
                                    <input type="date" id="corporateClientSince" name="corporateClientSince" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- ── Section: Business Details ── -->
                        <div class="section-divider">
                            <span class="section-divider-label"><i class="bi bi-info-circle"></i> Business Details</span>
                            <div class="section-divider-line"></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="region" class="form-label">Region</label>
                                    <input type="text" id="region" name="region" class="form-control" placeholder="Region">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tinNumber" class="form-label">TIN Number</label>
                                    <input type="text" id="tinNumber" name="tinNumber" class="form-control" placeholder="TIN #">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="corporateApSlCode" class="form-label">AP SL Code</label>
                                    <input type="text" id="corporateApSlCode" name="corporateApSlCode" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="corporateArSlCode" class="form-label">AR SL Code</label>
                                    <input type="text" id="corporateArSlCode" name="corporateArSlCode" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="designation" class="form-label">Contact Person Designation</label>
                                    <input type="text" id="designation" name="designation" class="form-control" placeholder="e.g., Manager, Director">
                                </div>
                            </div>
                        </div>

                        <!-- ── Section: Business Address ── -->
                        <div class="section-divider">
                            <span class="section-divider-label"><i class="bi bi-shop"></i> Business Address</span>
                            <div class="section-divider-line"></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="corporateBusinessAddress" class="form-label">Business Address <span class="req">*</span></label>
                                    <input type="text" id="corporateBusinessAddress" name="corporateBusinessAddress" class="form-control" placeholder="Street, Barangay, City" required>
                                    <div class="form-error">Business address is required</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="corporateBusinessCtm" class="form-label">CTM</label>
                                    <input type="text" id="corporateBusinessCtm" name="corporateBusinessCtm" class="form-control" placeholder="City Code">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="corporateBusinessProvince" class="form-label">Province</label>
                                    <input type="text" id="corporateBusinessProvince" name="corporateBusinessProvince" class="form-control" placeholder="Province">
                                </div>
                            </div>
                        </div>

                        <!-- ── Section: Contact Details ── -->
                        <div class="section-divider">
                            <span class="section-divider-label"><i class="bi bi-telephone"></i> Contact Information</span>
                            <div class="section-divider-line"></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="corporatePhone" class="form-label">Phone Number <span class="req">*</span></label>
                                    <input type="tel" id="corporatePhone" name="corporatePhone" class="form-control" placeholder="(02) 8000 0000" required>
                                    <div class="form-error">Phone number is required</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="corporateContactPerson" class="form-label">Contact Person <span class="req">*</span></label>
                                    <input type="text" id="corporateContactPerson" name="corporateContactPerson" class="form-control" placeholder="Full Name" required>
                                    <div class="form-error">Contact person is required</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="corporateEmail" class="form-label">Email Address <span class="req">*</span></label>
                                    <input type="email" id="corporateEmail" name="corporateEmail" class="form-control" placeholder="user@example.com" required>
                                    <div class="form-error">Valid email is required</div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Section: Additional Info ── -->
                        <div class="section-divider">
                            <span class="section-divider-label"><i class="bi bi-info-circle"></i> Contact Person Details</span>
                            <div class="section-divider-line"></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="corporateGender" class="form-label">Gender</label>
                                    <div class="select-wrap">
                                        <select id="corporateGender" name="corporateGender" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Client Classification <span class="req">*</span></label>
                                    <div style="display:flex;gap:20px;margin-top:8px;">
                                        <label style="display:flex;align-items:center;gap:8px;">
                                            <input type="radio" id="clientType1" name="clientClassification" value="client" required> Client
                                        </label>
                                        <label style="display:flex;align-items:center;gap:8px;">
                                            <input type="radio" id="agentType1" name="clientClassification" value="agent" required> Agent
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ── Section: Documents ── -->
                    <div class="section-divider">
                        <span class="section-divider-label"><i class="bi bi-file-earmark"></i> Supporting Documents</span>
                        <div class="section-divider-line"></div>
                    </div>

                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                        <i class="bi bi-cloud-arrow-up upload-icon"></i>
                        <p><strong>Click to upload</strong> or drag and drop</p>
                        <small>PDF, JPG, PNG (Max 5MB each)</small>
                    </div>
                    <input type="file" id="fileInput" multiple accept=".jpg,.jpeg,.png,.pdf" style="display:none;">
                    <div class="file-list" id="fileList"></div>

                </form>
            </div>

            <div class="card-footer">
                <div style="font-size:.75rem;color:var(--gray-500);">
                    <i class="bi bi-info-circle" style="margin-right:4px;"></i>
                    All fields marked <span style="color:var(--danger);font-weight:700;">*</span> are required.
                </div>
                <div style="display:flex;gap:10px;">
                    <button class="btn btn-outline" onclick="clearForm()">
                        <i class="bi bi-arrow-counterclockwise"></i> Clear Form
                    </button>
                    <button class="btn btn-outline" onclick="saveDraft()">
                        <i class="bi bi-download"></i> Save Draft
                    </button>
                    <button class="btn btn-primary" onclick="submitForm()">
                        <i class="bi bi-check-circle"></i> Submit & Continue
                    </button>
                </div>
            </div>
        </div>

    </main>
</div>
    
</body>
</html>
