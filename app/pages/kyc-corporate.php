<?php
require_once '../config/session.php';
requireLogin();

$typeFromQuery = strtolower(trim($_GET['type'] ?? 'corporate'));
$selectedClientType = $typeFromQuery === 'obligee' ? 'obligee' : 'corporate';
$isObligee = $selectedClientType === 'obligee';

$clientTypeLabel = $isObligee ? 'Obligee Client' : 'Corporate Client';
$newClientLabel = $isObligee ? 'New Obligee Client' : 'New Corporate Client';
$clientTypeIcon = $isObligee ? 'bi-shield-check' : 'bi-building';
$reviewUrl = 'kyc-corporate-review.php?type=' . urlencode($selectedClientType);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sterling insurance Company Incorporated</title>
    <link rel='icon' type='image/png' href='../css/images/SterlingLogo.png'>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../public/css/index.css">
    <link rel="stylesheet" href="../../public/css/global.css">
    <style>
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-to-type-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background-color: rgba(255, 255, 255, 0.88);
            color: #183026;
            border: 1px solid #d2e0d8;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .back-to-type-btn:hover {
            background-color: #eef8f2;
            border-color: #b8d5c6;
            transform: translateX(-2px);
        }

        .back-to-type-btn i {
            transition: transform 0.2s ease;
        }

        .back-to-type-btn:hover i {
            transform: translateX(-3px);
        }

        body {
            --draft-btn-size: 46px;
            --draft-btn-bottom: 18px;
            --draft-panel-gap: 8px;
        }

        body.kyc-compact {
            --draft-btn-size: 42px;
        }

        /* Saved Drafts floating panel */
        #draftsCard {
            position: fixed;
            top: 0;
            left: 0;
            width: 360px;
            max-width: calc(100vw - 28px);
            max-height: 48vh;
            overflow: hidden;
            z-index: 9999;
            display: block;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateY(-8px) scale(0.985);
            transform-origin: top right;
            border: 1px solid #d8dee6;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 20px 44px rgba(17, 24, 39, 0.16), 0 4px 14px rgba(17, 24, 39, 0.08);
            transition: opacity 0.22s ease, transform 0.22s ease, visibility 0.22s ease;
        }
        #draftsCard.open {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateY(0) scale(1);
        }

        #draftsCard .card-header {
            padding: 10px 12px;
            border-bottom: 1px solid #e7ebf0;
            background: #f9fafb;
        }

        #draftsCard .card-title {
            font-size: .82rem;
            color: #1f2937;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        #draftsCard .card-body {
            padding: 10px 12px 12px;
            overflow: auto;
            max-height: calc(48vh - 48px);
        }

        .drafts-fields {
            display: grid;
            grid-template-columns: 1fr;
            row-gap: 8px;
        }

        .drafts-action-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 8px;
        }

        #loadDraftBtn {
            min-width: 104px;
            height: 30px;
            padding: 0 10px;
            font-size: .72rem;
            border-radius: 9px;
        }

        #draftInfo,
        #draftDocsWrapper,
        #draftDocsContainer {
            font-size: .76rem;
        }

        #draftSelect {
            height: 34px;
            font-size: .78rem;
        }

        #kycForm {
            --masonry-gap: 14px;
            position: relative;
            width: min(1120px, 100%);
            margin: 0 auto 16px;
            min-height: 0;
        }

        #kycForm > #draftsCard {
            display: none;
        }

        #kycForm > #draftsCard.open {
            display: block;
        }

        #kycForm > .card,
        .client-type-inline {
            display: block;
            margin: 0;
        }

        #kycForm > .card {
            display: flex;
            flex-direction: column;
            align-self: stretch;
            position: relative;
            border: 1px solid #cfded4;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(248, 252, 250, 0.92) 100%);
            box-shadow: 0 10px 24px rgba(18, 52, 38, 0.08);
        }

        #kycForm > .card .card-body {
            flex: 0 0 auto;
        }

        #kycForm > .card .card-footer {
            flex: 0 0 auto;
        }

        #kycForm > .card.card-span-2 {
            width: 100%;
        }

        #kycForm > .card:not(#draftsCard)::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 4px;
            background: linear-gradient(180deg, #1e8a5c 0%, #2ea371 100%);
        }

        #kycForm > .card[data-wizard-step="3"]:not(#draftsCard)::before {
            background: linear-gradient(180deg, #2f7fd6 0%, #4b95e6 100%);
        }

        #kycForm > .card .card-header {
            padding: 18px 22px 0;
        }

        #kycForm > .card .card-title {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            font-size: 0.94rem;
            letter-spacing: 0.01em;
        }

        #kycForm > .card .card-title i {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e8f4ee;
            color: #16633f;
            font-size: 0.86rem;
        }

        #kycForm > .card[data-wizard-step="3"] .card-title i {
            background: #e8f0fb;
            color: #1f5ea9;
        }

        #kycForm > .card .card-body {
            padding: 18px 22px 20px;
        }

        #kycForm > .card .card-footer {
            padding: 14px 22px;
        }

        @media (max-width: 1100px) {
            #kycForm {
                --masonry-gap: 12px;
            }
        }

        #kycForm > .card.wizard-hidden {
            display: none;
        }

        .drafts-toggle-btn {
            width: var(--draft-btn-size);
            height: var(--draft-btn-size);
            border-radius: 10px;
            border: 1px solid #d2e0d8;
            background: rgba(255,255,255,0.85);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            align-self: center;
        }
        .drafts-toggle-btn:hover {
            background: #eef8f2;
            border-color: #b9d6c7;
        }

        .card.flow-reveal {
            animation: flowCardIn 0.28s ease both;
        }

        @keyframes flowCardIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .upload-zone.is-uploading {
            pointer-events: none;
            opacity: 0.75;
            border-color: #9ecfb3;
        }

        .upload-zone.is-invalid {
            border-color: var(--danger);
            background: rgba(220, 53, 69, 0.05);
        }

        .id-upload-hint {
            margin-top: 8px;
            color: var(--gray-500);
            font-size: 0.8rem;
        }

        .id-ocr-status {
            margin-top: 10px;
            font-size: 0.82rem;
            color: var(--gray-500);
            line-height: 1.5;
        }

        .id-ocr-summary {
            margin-top: 10px;
            padding: 12px 14px;
            border: 1px solid #d9eadf;
            border-radius: 12px;
            background: #f8fcf9;
            color: #264337;
            font-size: 0.82rem;
            line-height: 1.55;
        }

        .id-ocr-summary strong {
            color: #173625;
        }

        .ocr-quality-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .ocr-quality-badge.is-high {
            background: #def7e5;
            border-color: #9ad7b0;
            color: #0f5f36;
        }

        .ocr-quality-badge.is-good {
            background: #e3f0ff;
            border-color: #a9c9f2;
            color: #1f5ea9;
        }

        .ocr-quality-badge.is-fair {
            background: #fff4d9;
            border-color: #f0d48f;
            color: #8c5b00;
        }

        .ocr-quality-badge.is-low {
            background: #fde8e8;
            border-color: #f2b4b4;
            color: #9d1f1f;
        }

        .ocr-field-highlight {
            border-color: #f0d48f !important;
            background: #fff9e8 !important;
            box-shadow: 0 0 0 3px rgba(240, 212, 143, 0.22);
        }

        .id-ocr-status strong {
            color: #1f3d2e;
        }

        .flow-actions .btn:active,
        .drafts-toggle-btn:active,
        .back-to-type-btn:active {
            transform: translateY(1px) scale(0.98);
        }

        .flow-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .flow-note {
            font-size: .75rem;
            color: var(--gray-500);
            flex: 1 1 240px;
        }

        .flow-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .steps-bar .step.step-clickable {
            cursor: pointer;
        }

        .steps-bar .step.step-clickable .step-num {
            cursor: pointer;
        }

        .client-type-inline {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 0;
        }

        .client-type-inline-left {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .client-type-inline-label {
            font-size: 0.73rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--gray-500);
        }

        /* Compact density mode for KYC form layout */
        body.kyc-compact .topbar {
            height: 56px;
            padding: 0 22px;
        }

        body.kyc-compact .topbar-left h1 {
            font-size: 0.95rem;
        }

        body.kyc-compact .breadcrumb-trail {
            font-size: 0.64rem;
        }

        body.kyc-compact .content {
            padding: 18px 22px;
        }

        body.kyc-compact .steps-bar {
         
            margin-bottom: 14px;
            border-radius: 12px;
        }

        body.kyc-compact .steps-bar.sticky {
            top: 56px;
        }

        body.kyc-compact .step {
            gap: 8px;
        }

        body.kyc-compact .step-num {
            width: 28px;
            height: 28px;
            font-size: 0.72rem;
        }

        body.kyc-compact .step-info span:first-child {
            font-size: 0.62rem;
        }

        body.kyc-compact .step-info strong {
            font-size: 0.72rem;
        }

        body.kyc-compact #kycForm {
            --masonry-gap: 10px;
            margin-bottom: 12px;
        }

        body.kyc-compact #kycForm > .card .card-header {
            padding: 12px 16px 0;
        }

        body.kyc-compact #kycForm > .card .card-body,
        body.kyc-compact #kycForm > .card .card-footer {
            padding-left: 16px;
            padding-right: 16px;
        }

        body.kyc-compact .client-type-inline {
            margin-bottom: 0;
        }

        body.kyc-compact .client-type-inline-label {
            font-size: 0.66rem;
        }

        body.kyc-compact .client-type-display {
            min-height: 34px;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 0.78rem;
        }

        body.kyc-compact .client-type-display i {
            font-size: 0.8rem;
        }

        body.kyc-compact #kycForm .row.g-3 {
            --bs-gutter-x: 0.8rem;
            --bs-gutter-y: 0.6rem;
        }

        body.kyc-compact .card {
            margin-bottom: 12px;
            border-radius: 12px;
        }

        body.kyc-compact .card-header {
            padding: 14px 18px 0;
        }

        body.kyc-compact .card-title {
            font-size: 0.86rem;
        }

        body.kyc-compact .card-subtitle {
            font-size: 0.7rem;
        }

        body.kyc-compact .card-body {
         
        }

        body.kyc-compact .card-footer {
            padding: 12px 18px;
        }

        body.kyc-compact .section-divider {
            margin: 14px 0 10px;
        }

        body.kyc-compact .form-group {
            margin-bottom: 12px;
        }

        body.kyc-compact .form-label {
            margin-bottom: 4px;
            font-size: 0.71rem;
        }

        body.kyc-compact input.form-control,
        body.kyc-compact select.form-select {
            height: 36px;
            padding: 0 10px;
            font-size: 0.8rem;
        }

        body.kyc-compact textarea.form-control {
            min-height: 74px;
            padding: 8px 10px;
            font-size: 0.8rem;
            line-height: 1.35;
        }

        body.kyc-compact .input-icon-wrap .form-control {
            padding-left: 34px;
        }

        body.kyc-compact .input-icon-wrap i {
            left: 11px;
            font-size: 0.82rem;
        }

        body.kyc-compact .form-hint,
        body.kyc-compact .form-error {
            font-size: 0.66rem;
            margin-top: 3px;
        }

        body.kyc-compact .btn {
            height: 36px;
            padding: 0 14px;
            font-size: 0.78rem;
        }

        body.kyc-compact .back-to-type-btn {
            padding: 6px 10px;
            font-size: 0.8rem;
            border-radius: 8px;
        }

        body.kyc-compact .drafts-toggle-btn {
            width: var(--draft-btn-size);
            height: var(--draft-btn-size);
        }

        body.kyc-compact #draftsCard {
            width: 336px;
            top: 74px;
            bottom: calc(var(--draft-btn-bottom) + var(--draft-btn-size) + var(--draft-panel-gap));
            border-radius: 12px;
        }

        body.kyc-compact #draftsCard .card-header {
            padding: 10px 12px;
        }

        body.kyc-compact #draftsCard .card-body {
            padding: 10px 12px 12px;
            max-height: calc(44vh - 48px);
        }

        body.kyc-compact #draftSelect {
            height: 36px;
            font-size: 0.8rem;
        }

        body.kyc-compact #loadDraftBtn {
            min-width: 104px;
            height: 32px;
            padding: 0 10px;
            font-size: 0.72rem;
        }

        @media (max-width: 900px) {
            body::before {
                content: '';
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.28);
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.2s ease;
                z-index: 9997;
            }

            body.drafts-popup-open::before {
                opacity: 1;
            }

            body {
                --draft-btn-bottom: 12px;
            }

            .client-type-inline {
                align-items: flex-start;
                flex-wrap: wrap;
                gap: 8px;
            }

            .client-type-inline-left {
                flex: 1 1 100%;
            }

            body.kyc-compact .topbar {
                height: auto;
                min-height: 52px;
                padding: 8px 12px;
            }

            body.kyc-compact .content {
                padding: 14px;
            }

            body.kyc-compact .steps-bar {
                padding: 10px 12px;
                margin-bottom: 10px;
            }

            body.kyc-compact .steps-bar.sticky {
                top: 52px;
            }

            body.kyc-compact .card-header {
                padding: 10px 12px 0;
            }

            body.kyc-compact .card-body,
            body.kyc-compact .card-footer {
                padding: 10px 12px;
            }

            .flow-footer {
                align-items: stretch;
            }

            .flow-note {
                flex: 1 1 100%;
            }

            .flow-actions {
                width: 100%;
                justify-content: stretch;
            }

            .flow-actions .btn {
                flex: 1 1 calc(50% - 8px);
                min-width: 0;
                justify-content: center;
            }

            #draftsCard,
            body.kyc-compact #draftsCard {
                width: min(360px, calc(100vw - 20px));
                max-height: min(50dvh, 360px);
            }

            body.kyc-compact #draftsCard .card-body {
                max-height: calc(min(50dvh, 360px) - 48px);
            }

        }

        @media (max-width: 640px) {
            #draftsCard,
            body.kyc-compact #draftsCard {
                width: min(330px, calc(100vw - 23px));
                max-height: min(48dvh, 330px);
            }

            #draftsCard .card-body,
            body.kyc-compact #draftsCard .card-body {
                max-height: calc(min(48dvh, 330px) - 48px);
            }
        }

        @media (max-width: 560px) {
            .flow-actions .btn {
                flex: 1 1 100%;
            }
        }
    </style>
</head>
<body class="kyc-compact">

<?php
$activePage = 'kyc-verification';
include '../includes/sidebar.php';
?>

<!-- ═══════════════════════════════════════════════ MAIN -->
<div class="main">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <h1>KYC Verification — <?php echo htmlspecialchars($clientTypeLabel); ?></h1>
            <div class="breadcrumb-trail">
                <i class="bi bi-house" style="font-size:.65rem;"></i>
                Dashboard &rsaquo; Clients &rsaquo; <span><?php echo htmlspecialchars($newClientLabel); ?></span>
            </div>
        </div>
        <div class="topbar-right">
            <button type="button" class="drafts-toggle-btn" title="Saved Drafts" onclick="toggleDraftsPanel()">
                <i class="bi bi-inbox"></i>
            </button>
        </div>
    </header>

    <!-- Content -->
    <main class="content">

        <!-- Steps -->
        <div class="steps-bar">
            <div class="step done" id="step-1">
                <div class="step-num"><i class="bi bi-check" style="font-size:.9rem;"></i></div>
                <div class="step-info">
                    <span>Step 1</span>
                    <strong>Client Type</strong>
                </div>
            </div>
            <div class="step-line done"></div>
            <div class="step active" id="step-2">
                <div class="step-num">2</div>
                <div class="step-info">
                    <span>Step 2</span>
                    <strong>Business Details</strong>
                </div>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step-3">
                <div class="step-num">3</div>
                <div class="step-info">
                    <span>Step 3</span>
                    <strong>Contact Details</strong>
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

        <!-- Form Start -->
        <form id="kycForm" novalidate>

            <!-- Client Type Display -->
            <div class="client-type-inline" data-wizard-step="2">
                <div class="client-type-inline-left">
                    <span class="client-type-inline-label">Client Type</span>
                    <div class="client-type-display <?php echo htmlspecialchars($selectedClientType); ?>">
                        <i class="bi <?php echo htmlspecialchars($clientTypeIcon); ?>"></i>
                        <span><?php echo htmlspecialchars($clientTypeLabel); ?></span>
                    </div>
                </div>
                <a href="kyc-verification.php" class="back-to-type-btn">
                    <i class="bi bi-arrow-left"></i>
                    Change Type
                </a>
                <input type="hidden" name="clientType" value="<?php echo htmlspecialchars($selectedClientType); ?>">
            </div>

            <!-- Reference Card -->
            <div class="card" data-wizard-step="2">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-hash"></i> Reference</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="refCode" class="form-label">Reference Code <span style="font-size:0.85rem;color:#999;">(Optional)</span></label>
                                <input type="text" id="refCode" name="refCode" class="form-control" placeholder="Leave blank to auto-generate">
                                <small class="text-muted">Leave empty for automatic generation</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="clientNumber" class="form-label">Client Number</label>
                                <input type="text" id="clientNumber" name="clientNumber" class="form-control" placeholder="Auto-generated" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Drafts Card -->
            <div class="card" id="draftsCard">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-inbox"></i> Saved Drafts</div>
                    <button type="button" id="refreshDraftBtn" class="btn btn-sm btn-outline-secondary" onclick="refreshDrafts()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    <div class="drafts-fields">
                        <div>
                            <label for="draftSelect" class="form-label">Drafts</label>
                            <select id="draftSelect" class="form-select">
                                <option value="">Loading...</option>
                            </select>
                        </div>
                    </div>
                    <div id="draftInfo" style="margin-top:10px; color: var(--gray-500); font-size: .85rem;"></div>
                    <div id="draftDocsWrapper" style="margin-top:14px;">
                        <div style="color: var(--gray-500); font-size:.85rem;">Attachments saved to the selected draft:</div>
                        <div id="draftDocsContainer" style="margin-top:8px;"></div>
                    </div>
                    <div class="drafts-action-row">
                        <button type="button" class="btn btn-primary" id="loadDraftBtn" onclick="loadSelectedDraft()" disabled>
                            <i class="bi bi-box-arrow-in-right"></i> Load Draft
                        </button>
                    </div>
                </div>
            </div>

            <!-- Company Information Card -->
            <div class="card" data-wizard-step="2">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-building"></i> Company Information</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="corporateClientName" class="form-label">Business / Company Name <span class="req">*</span></label>
                                <input type="text" id="corporateClientName" name="corporateClientName" class="form-control" placeholder="Registered Business/Company Name" required>
                                <div class="form-error">Business/Company name is required</div>
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
                </div>
            </div>

            <!-- Business Details Card -->
            <div class="card" data-wizard-step="2">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-info-circle"></i> Business Details</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
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
                </div>
            </div>

            <!-- Business Address Card -->
            <div class="card" data-wizard-step="2">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-shop"></i> Business Address</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="region" class="form-label">Region</label>
                                <div class="select-wrap">
                                    <select id="region" name="region" class="form-select">
                                        <option value="">Select region...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="corporateBusinessProvince" class="form-label">Province</label>
                                <div class="select-wrap">
                                    <select id="corporateBusinessProvince" name="corporateBusinessProvince" class="form-select">
                                        <option value="">Select province...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="corporateBusinessCtm" class="form-label">City / Municipality</label>
                                <div class="select-wrap">
                                    <select id="corporateBusinessCtm" name="corporateBusinessCtm" class="form-select">
                                        <option value="">Select city/municipality...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="corporateBusinessBarangay" class="form-label">Barangay</label>
                                <div class="select-wrap">
                                    <select id="corporateBusinessBarangay" name="corporateBusinessBarangay" class="form-select">
                                        <option value="">Select barangay...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="corporateStreet" class="form-label">Street / Unit / Building <span class="req">*</span></label>
                                <input type="text" id="corporateStreet" name="corporateStreet" class="form-control" placeholder="House/Unit No., Street, Building" required>
                                <input type="hidden" id="corporateBusinessAddress" name="corporateBusinessAddress">
                                <div class="form-error">Business address is required</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information Card -->
            <div class="card" data-wizard-step="3">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-telephone"></i> Contact Information</div>
                </div>
                <div class="card-body">
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
                                <label for="corporateContactPerson" class="form-label">Company Owner <span class="req">*</span></label>
                                <input type="text" id="corporateContactPerson" name="corporateContactPerson" class="form-control" placeholder="Owner Full Name" required>
                                <div class="form-error">Company owner is required</div>
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
                </div>
            </div>

            <!-- Contact Person Details Card -->
            <div class="card" data-wizard-step="3">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-info-circle"></i> Contact Person Details</div>
                </div>
                <div class="card-body">
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
            </div>

            <!-- Government ID Verification Card -->
            <div class="card" data-wizard-step="3">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-person-vcard"></i> Government ID Verification</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="governmentIdType" class="form-label">Government ID Type <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select id="governmentIdType" name="idType" class="form-select" required>
                                        <option value="">Select government ID...</option>
                                        <option value="philippine_passport">Philippine Passport</option>
                                        <option value="drivers_license">Driver's License</option>
                                        <option value="umid">UMID</option>
                                        <option value="philsys_national_id">PhilSys National ID</option>
                                        <option value="postal_id">Postal ID</option>
                                        <option value="sss_id">SSS ID</option>
                                        <option value="gsis_id">GSIS ID</option>
                                        <option value="prc_id">PRC ID</option>
                                        <option value="tin_id">TIN ID</option>
                                        <option value="philhealth_id">PhilHealth ID</option>
                                        <option value="pagibig_id">Pag-IBIG ID</option>
                                        <option value="voters_id">Voter's ID</option>
                                        <option value="senior_citizen_id">Senior Citizen ID</option>
                                        <option value="ofw_id">OFW ID</option>
                                        <option value="barangay_id">Barangay ID</option>
                                        <option value="acr_id">Alien Certificate of Registration</option>
                                    </select>
                                </div>
                                <div class="form-error">Government ID type is required</div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="form-group">
                                <label for="idNumber" class="form-label">ID Number <span class="req">*</span></label>
                                <input type="text" id="idNumber" name="idNumber" class="form-control" placeholder="Auto-filled by OCR or enter manually" required>
                                <div class="form-error">ID number is required</div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-label">ID Photo Upload <span class="req">*</span></label>
                                <div class="upload-zone" id="governmentIdUploadZone" onclick="document.getElementById('governmentIdInput').click()">
                                    <i class="bi bi-camera upload-icon"></i>
                                    <p><strong>Click to upload</strong> or drag and drop the ID photo</p>
                                    <small>JPG, PNG (Max 5MB)</small>
                                </div>
                                <input type="file" id="governmentIdInput" accept=".jpg,.jpeg,.png" style="display:none;">
                                <div class="id-upload-hint">OCR will scan the uploaded image and try to fill the ID number automatically.</div>
                                <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
                                    <button type="button" id="scanIdBtn" class="btn btn-sm btn-primary" onclick="scanCurrentGovernmentId()">
                                        <i class="bi bi-search"></i> Scan ID
                                    </button>
                                    <button type="button" id="ocrHealthCheckBtn" class="btn btn-sm btn-outline-secondary" onclick="checkGoogleVisionHealth()">
                                        <i class="bi bi-shield-check"></i> Check Google Vision
                                    </button>
                                </div>
                                <div class="file-list" id="governmentIdFileList" style="margin-top:12px;"></div>
                                <div class="id-ocr-status" id="governmentIdOcrStatus">No ID photo uploaded yet.</div>
                                <div class="id-ocr-summary" id="governmentIdOcrSummary">OCR summary will appear here after scanning.</div>
                                <div class="id-ocr-summary" id="governmentIdOcrDebug" style="margin-top:8px; background:#f7faf8; border-color:#dce8e1; color:#2b4036;">Raw OCR debug output will appear here.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Card -->
            <div class="card card-span-2" data-wizard-step="3">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-file-earmark"></i> Supporting Documents</div>
                </div>
                <div class="card-body">
                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                        <i class="bi bi-cloud-arrow-up upload-icon"></i>
                        <p><strong>Click to upload</strong> or drag and drop</p>
                        <small>PDF, JPG, PNG (Max 5MB each)</small>
                    </div>
                    <input type="file" id="fileInput" name="documents[]" multiple accept=".jpg,.jpeg,.png,.pdf" style="display:none;">
                    <div class="file-list" id="fileList"></div>
                </div>
            </div>

        </form>

        <!-- Action Buttons Card -->
        <div class="card">
            <div class="card-footer flow-footer">
                <div class="flow-note">
                    <i class="bi bi-info-circle" style="margin-right:4px;"></i>
                    All fields marked <span style="color:var(--danger);font-weight:700;">*</span> are required.
                </div>
                <div class="flow-actions">
                    <button type="button" id="backBtn" class="btn btn-outline" onclick="goBack()">
                        <i class="bi bi-arrow-left"></i> Back to Type Selection
                    </button>
                    <button type="button" id="wizardPrevBtn" class="btn btn-outline">
                        <i class="bi bi-chevron-left"></i> Previous
                    </button>
                    <button type="button" id="clearBtn" class="btn btn-outline" onclick="clearForm()">
                        <i class="bi bi-arrow-counterclockwise"></i> Clear Form
                    </button>
                    <button type="button" id="saveDraftBtn" class="btn btn-outline" onclick="saveDraft()">
                        <i class="bi bi-download"></i> Save Draft
                    </button>
                    <button type="button" id="wizardNextBtn" class="btn btn-primary">
                        Next <i class="bi bi-chevron-right"></i>
                    </button>
                    <button type="button" id="proceedBtn" class="btn btn-primary" onclick="proceedToReview()">
                        <i class="bi bi-arrow-right"></i> Go to Summary Page
                    </button>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- ═══════════════════════════════════════════════ SCRIPTS -->
<script>
const currentClientType = <?php echo json_encode($selectedClientType); ?>;
const currentReviewUrl = <?php echo json_encode($reviewUrl); ?>;

// ── Toast ──────────────────────────────────────────────────
function showToast(type, title, msg) {
    const icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', info: 'bi-info-circle-fill' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="bi ${icons[type]} toast-icon"></i>
        <div class="toast-body">
            <div class="toast-title">${title}</div>
            <div class="toast-msg">${msg}</div>
        </div>
        <i class="bi bi-x toast-close" onclick="removeToast(this.parentElement)"></i>`;
    document.getElementById('toastContainer').appendChild(toast);
    setTimeout(() => removeToast(toast), 4000);
}

function removeToast(el) {
    el.classList.add('out');
    setTimeout(() => el.remove(), 250);
}

function setButtonBusy(button, isBusy, label = 'Working...') {
    if (!button) return;
    if (isBusy) {
        button.dataset.originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = `<span class="spinner" style="width:14px;height:14px;"></span> ${label}`;
    } else {
        button.disabled = false;
        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
            delete button.dataset.originalHtml;
        }
    }
}

function revealFlowCards() {
    const cards = document.querySelectorAll('main.content .card');
    cards.forEach((card, idx) => {
        card.classList.add('flow-reveal');
        card.style.animationDelay = `${Math.min(idx * 45, 280)}ms`;
    });
}

// ── Form Validation ────────────────────────────────────────
function validateField(id) {
    const el = document.getElementById(id);
    if (!el) return true;
    
    // Skip validation if field is hidden
    if (el.offsetParent === null) return true;
    
    const value = el.value.trim();
    let ok = value !== '';
    
    // Additional validation for specific field types
    if (ok && el.type === 'email') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        ok = emailRegex.test(value);
    } else if (ok && el.type === 'tel') {
        // Phone validation: at least 7 digits
        const phoneDigits = value.replace(/\D/g, '');
        ok = phoneDigits.length >= 7;
    }
    
    el.classList.toggle('is-invalid', !ok);
    el.classList.toggle('is-valid', ok);
    return ok;
}

function validateRadioGroup(name) {
    const radios = document.querySelectorAll(`input[name="${name}"]`);
    if (radios.length === 0) return true;
    
    const checked = Array.from(radios).some(radio => radio.checked);
    radios.forEach(radio => {
        const label = radio.closest('label');
        if (label) label.classList.toggle('is-invalid', !checked);
    });
    return checked;
}

function validateAllRequired() {
    const requiredFields = ['corporateClientName', 'region', 'corporateBusinessProvince', 'corporateBusinessCtm', 'corporateBusinessBarangay', 'corporateStreet', 'corporatePhone', 'corporateContactPerson', 'corporateEmail', 'governmentIdType', 'idNumber'];
    let allValid = true;
    let failedFields = [];
    
    requiredFields.forEach(id => {
        const el = document.getElementById(id);
        if (el && el.offsetParent !== null) {
            const isValid = validateField(id);
            if (!isValid) {
                allValid = false;
                failedFields.push(id);
            }
        }
    });
    
    // Validate businessType radio
    if (!validateRadioGroup('businessType')) allValid = false;
    // Validate clientClassification radio
    if (!validateRadioGroup('clientClassification')) allValid = false;
    if (!validateGovernmentIdSection()) allValid = false;
    
    if (!allValid && failedFields.length > 0) {
        console.log('Failed fields:', failedFields);
    }
    
    return allValid;
}

// Add event listeners to all form fields
document.querySelectorAll('input:not([type="checkbox"]):not([type="radio"]), select, textarea').forEach(el => {
    el.addEventListener('blur', function() {
        if (this.id) validateField(this.id);
    });
    el.addEventListener('input', function() {
        if (this.classList.contains('is-invalid') && this.id) validateField(this.id);
    });
    el.addEventListener('change', function() {
        if (this.id) validateField(this.id);
    });
});

// Add listeners for radio buttons
document.querySelectorAll('input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        validateRadioGroup(this.name);
    });
});

// ── PSGC Address API (Philippines) ───────────────────────
const PSGC_BASE_URL = 'https://psgc.gitlab.io/api';

async function psgcFetch(path) {
    const response = await fetch(`${PSGC_BASE_URL}${path}`);
    if (!response.ok) {
        throw new Error(`PSGC request failed: ${response.status}`);
    }
    return response.json();
}

function fillSelectOptions(selectEl, items, labelKey = 'name', valueKey = 'name', placeholder = 'Select...') {
    if (!selectEl) return;
    selectEl.innerHTML = `<option value="">${placeholder}</option>`;
    items.forEach(item => {
        const option = document.createElement('option');
        option.value = item[valueKey];
        option.textContent = item[labelKey];
        option.dataset.code = item.code;
        selectEl.appendChild(option);
    });
}

function setSelectLoading(selectEl, text) {
    if (!selectEl) return;
    selectEl.innerHTML = `<option value="">${text}</option>`;
    selectEl.disabled = true;
}

async function initCorporateAddressSelectors() {
    const regionEl = document.getElementById('region');
    const provinceEl = document.getElementById('corporateBusinessProvince');
    const cityEl = document.getElementById('corporateBusinessCtm');
    const barangayEl = document.getElementById('corporateBusinessBarangay');

    if (!regionEl || !provinceEl || !cityEl || !barangayEl) return;

    setSelectLoading(regionEl, 'Loading regions...');
    setSelectLoading(provinceEl, 'Select region first...');
    setSelectLoading(cityEl, 'Select province first...');
    setSelectLoading(barangayEl, 'Select city first...');

    try {
        const regions = await psgcFetch('/regions/');
        fillSelectOptions(regionEl, regions, 'name', 'name', 'Select region...');
        regionEl.disabled = false;
    } catch (error) {
        console.error(error);
        setSelectLoading(regionEl, 'Unable to load regions');
        return;
    }

    regionEl.addEventListener('change', async function () {
        const selectedRegionCode = this.options[this.selectedIndex]?.dataset?.code || '';
        fillSelectOptions(provinceEl, [], 'name', 'name', 'Select province...');
        fillSelectOptions(cityEl, [], 'name', 'name', 'Select city/municipality...');
        fillSelectOptions(barangayEl, [], 'name', 'name', 'Select barangay...');

        if (!selectedRegionCode) {
            provinceEl.disabled = true;
            cityEl.disabled = true;
            barangayEl.disabled = true;
            return;
        }

        setSelectLoading(provinceEl, 'Loading provinces...');
        cityEl.disabled = true;
        barangayEl.disabled = true;

        try {
            const provinces = await psgcFetch(`/regions/${selectedRegionCode}/provinces/`);
            if (provinces.length === 0) {
                fillSelectOptions(provinceEl, [{ name: 'NCR', code: selectedRegionCode }], 'name', 'name', 'No province (NCR)');
                provinceEl.value = 'NCR';
                provinceEl.disabled = true;

                setSelectLoading(cityEl, 'Loading cities/municipalities...');
                const citiesInRegion = await psgcFetch(`/regions/${selectedRegionCode}/cities-municipalities/`);
                fillSelectOptions(cityEl, citiesInRegion, 'name', 'name', 'Select city/municipality...');
                cityEl.disabled = false;
                fillSelectOptions(barangayEl, [], 'name', 'name', 'Select city first...');
                barangayEl.disabled = true;
                return;
            }

            fillSelectOptions(provinceEl, provinces, 'name', 'name', 'Select province...');
            provinceEl.disabled = false;
            cityEl.disabled = true;
            barangayEl.disabled = true;
        } catch (error) {
            console.error(error);
            setSelectLoading(provinceEl, 'Unable to load provinces');
            cityEl.disabled = true;
            barangayEl.disabled = true;
        }
    });

    provinceEl.addEventListener('change', async function () {
        const selectedProvinceCode = this.options[this.selectedIndex]?.dataset?.code || '';
        const selectedRegionCode = regionEl.options[regionEl.selectedIndex]?.dataset?.code || '';

        fillSelectOptions(cityEl, [], 'name', 'name', 'Select city/municipality...');
        fillSelectOptions(barangayEl, [], 'name', 'name', 'Select barangay...');

        if (!selectedProvinceCode && this.value !== 'NCR') {
            cityEl.disabled = true;
            barangayEl.disabled = true;
            return;
        }

        setSelectLoading(cityEl, 'Loading cities/municipalities...');
        barangayEl.disabled = true;
        try {
            const cities = this.value === 'NCR'
                ? await psgcFetch(`/regions/${selectedRegionCode}/cities-municipalities/`)
                : await psgcFetch(`/provinces/${selectedProvinceCode}/cities-municipalities/`);

            fillSelectOptions(cityEl, cities, 'name', 'name', 'Select city/municipality...');
            cityEl.disabled = false;
            fillSelectOptions(barangayEl, [], 'name', 'name', 'Select city first...');
            barangayEl.disabled = true;
        } catch (error) {
            console.error(error);
            setSelectLoading(cityEl, 'Unable to load cities/municipalities');
            barangayEl.disabled = true;
        }
    });

    cityEl.addEventListener('change', async function () {
        const selectedCityCode = this.options[this.selectedIndex]?.dataset?.code || '';
        fillSelectOptions(barangayEl, [], 'name', 'name', 'Select barangay...');

        if (!selectedCityCode) {
            barangayEl.disabled = true;
            return;
        }

        setSelectLoading(barangayEl, 'Loading barangays...');
        try {
            const barangays = await psgcFetch(`/cities-municipalities/${selectedCityCode}/barangays/`);
            fillSelectOptions(barangayEl, barangays, 'name', 'name', 'Select barangay...');
            barangayEl.disabled = false;
        } catch (error) {
            console.error(error);
            setSelectLoading(barangayEl, 'Unable to load barangays');
        }
    });
}

function buildAddress(street, barangay, city, province, region) {
    return [street, barangay, city, province, region].filter(part => part && part.trim() !== '').join(', ');
}

function syncCorporateAddressField() {
    const street = document.getElementById('corporateStreet')?.value || '';
    const barangay = document.getElementById('corporateBusinessBarangay')?.value || '';
    const city = document.getElementById('corporateBusinessCtm')?.value || '';
    const province = document.getElementById('corporateBusinessProvince')?.value || '';
    const region = document.getElementById('region')?.value || '';
    document.getElementById('corporateBusinessAddress').value = buildAddress(street, barangay, city, province, region);
}

function restoreFormData() {
    const savedData = sessionStorage.getItem('kycFormData');
    const savedAddressData = sessionStorage.getItem('corporateAddressData');
    
    if (!savedData) return;
    
    try {
        const formData = JSON.parse(savedData);
        const form = document.getElementById('kycForm');
        if (!form) return;
        
        // Fields to skip in the general restore (we'll handle address fields separately)
        const addressFields = ['region', 'corporateBusinessProvince', 'corporateBusinessCtm', 'corporateBusinessBarangay', 'corporateStreet', 'corporateBusinessAddress'];
        
        Object.keys(formData).forEach(key => {
            // Skip address fields - restore them separately
            if (addressFields.includes(key)) return;
            
            const el = form.querySelector(`[name="${key}"]`);
            if (el) {
                if (el.type === 'radio') {
                    const selectedRadio = form.querySelector(`[name="${key}"][value="${formData[key]}"]`);
                    if (selectedRadio) selectedRadio.checked = true;
                } else if (el.tagName === 'SELECT') {
                    el.value = formData[key];
                    el.dispatchEvent(new Event('change'));
                } else {
                    el.value = formData[key];
                }
            }
        });
        
        // Restore address data after API populates options
        // This requires waiting for PSGC API calls in the correct cascade order
        if (savedAddressData) {
            try {
                const addressData = JSON.parse(savedAddressData);
                
                // Restore address components in cascade order with delays for API calls
                setTimeout(() => {
                    const regionEl = document.getElementById('region');
                    if (regionEl && addressData.region) {
                        regionEl.value = addressData.region;
                        regionEl.dispatchEvent(new Event('change'));
                    }
                    
                    // Wait for provinces to load, then restore province
                    setTimeout(() => {
                        const provinceEl = document.getElementById('corporateBusinessProvince');
                        if (provinceEl && addressData.province) {
                            provinceEl.value = addressData.province;
                            provinceEl.dispatchEvent(new Event('change'));
                        }
                        
                        // Wait for cities to load, then restore city
                        setTimeout(() => {
                            const cityEl = document.getElementById('corporateBusinessCtm');
                            if (cityEl && addressData.city) {
                                cityEl.value = addressData.city;
                                cityEl.dispatchEvent(new Event('change'));
                            }
                            
                            // Wait for barangays to load, then restore barangay
                            setTimeout(() => {
                                const barangayEl = document.getElementById('corporateBusinessBarangay');
                                if (barangayEl && addressData.barangay) {
                                    barangayEl.value = addressData.barangay;
                                }
                                
                                // Finally restore street and rebuild composed address
                                const streetEl = document.getElementById('corporateStreet');
                                if (streetEl && addressData.street) {
                                    streetEl.value = addressData.street;
                                }
                                
                                syncCorporateAddressField();
                            }, 500);
                        }, 500);
                    }, 500);
                }, 500);
                
            } catch (error) {
                console.error('Error restoring address data:', error);
            }
        }
    } catch (error) {
        console.error('Error restoring form data:', error);
    }
}

initCorporateAddressSelectors();

// Restore form data on page load
const KYC_NAVIGATION_TYPE = (performance.getEntriesByType('navigation')[0]?.type) || (performance.navigation && performance.navigation.type === 1 ? 'reload' : 'navigate');

async function clearDraftStateOnRefresh() {
    const regularUploads = getStoredUploads();
    const governmentIdUploads = getStoredGovernmentIdUploads();

    sessionStorage.removeItem('kycFormData');
    sessionStorage.removeItem('corporateAddressData');
    sessionStorage.removeItem('kycUploadedFiles');
    sessionStorage.removeItem('kycGovernmentIdFiles');
    sessionStorage.removeItem('kycGovernmentIdOcrData');
    sessionStorage.removeItem('kycGovernmentIdOcrMeta');
    sessionStorage.removeItem('kycGovernmentIdOcrRaw');

    await Promise.all([
        ...((regularUploads || []).map(upload => deleteTempUpload(upload?.temp_path))),
        ...((governmentIdUploads || []).map(upload => deleteTempUpload(upload?.temp_path)))
    ]);
}

if (KYC_NAVIGATION_TYPE === 'reload') {
    void clearDraftStateOnRefresh();
}

document.addEventListener('DOMContentLoaded', restoreFormData);

function validateGovernmentIdSection() {
    const typeOk = validateField('governmentIdType');
    const numberOk = validateField('idNumber');
    const uploadsOk = getStoredGovernmentIdUploads().length > 0;
    const zone = document.getElementById('governmentIdUploadZone');
    const status = document.getElementById('governmentIdOcrStatus');

    if (zone) zone.classList.toggle('is-invalid', !uploadsOk);
    if (status && !uploadsOk) {
        status.textContent = 'Government ID photo is required.';
    }

    return typeOk && numberOk && uploadsOk;
}

function inferGovernmentIdNumber(text, idType) {
    const cleanedText = String(text || '')
        .replace(/\|/g, 'I')
        .replace(/[_]+/g, ' ')
        .replace(/[ \t]+/g, ' ')
        .trim();

    const compactText = cleanedText.replace(/\s+/g, ' ');
    const lines = cleanedText.split(/\r?\n/).map(line => line.trim()).filter(Boolean);
    const type = String(idType || '').toLowerCase();
    const candidates = new Set();
    const blocked = new Set([
        'license', 'number', 'id', 'no', 'date', 'agency', 'code',
        'republic', 'department', 'transportation', 'office'
    ]);

    const pushMatches = (regex) => {
        const matches = compactText.match(regex) || [];
        matches.forEach(match => candidates.add(match.trim()));
    };

    const normalizePhilsysNumber = (value) => {
        const digits = String(value || '').replace(/\D/g, '');
        if (digits.length === 16) {
            return digits.replace(/(\d{4})(?=\d)/g, '$1-').replace(/-$/, '');
        }
        return String(value || '').replace(/\s+/g, '').trim();
    };

    if (type.includes('philsys')) {
        const philsysPattern = /\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/i;
        const philsysLabel = /\b(philsys\s*(card\s*)?number|philsys\s*no\.?|pcn|psn|national\s*id\s*no\.?)\b/i;

        for (let index = 0; index < lines.length; index += 1) {
            if (!philsysLabel.test(lines[index])) continue;

            for (let offset = 0; offset <= 6; offset += 1) {
                let target = lines[index + offset] || '';
                if (!target) continue;
                if (offset === 0) {
                    target = target.replace(philsysLabel, ' ');
                }

                const match = target.match(philsysPattern);
                if (match?.[0]) {
                    return normalizePhilsysNumber(match[0]);
                }
            }
        }
    }

    if (type.includes('driver')) {
        const driverPatterns = [
            /\b[A-Z]\d{2}-\d{2}-\d{6}\b/i,
            /\b[A-Z]{1,3}-?\d{2,3}-?\d{4,7}\b/i
        ];

        for (let index = 0; index < lines.length; index += 1) {
            if (!/\blicense\s*no\.?\b/i.test(lines[index])) continue;

            for (let offset = 0; offset <= 6; offset += 1) {
                let target = lines[index + offset] || '';
                if (!target) continue;
                if (offset === 0) {
                    target = target.replace(/\blicense\s*no\.?\b/i, ' ');
                }

                for (const pattern of driverPatterns) {
                    const match = target.match(pattern);
                    if (match?.[0]) {
                        return match[0].trim();
                    }
                }
            }
        }
    }

    if (type.includes('passport')) {
        pushMatches(/\b[A-Z0-9]{8,10}\b/gi);
    } else if (type.includes('driver')) {
        pushMatches(/\b[A-Z]{1,3}-?\d{2,3}-?\d{4,7}\b/gi);
    } else if (type.includes('umid') || type.includes('philsys')) {
        pushMatches(/\b\d{4}-?\d{4}-?\d{4}-?\d{4}\b/g);
    } else if (type.includes('sss')) {
        pushMatches(/\b\d{2}-?\d{7}-?\d\b/g);
    } else if (type.includes('gsis')) {
        pushMatches(/\b\d{2}-?\d{7}-?\d\b/g);
    } else if (type.includes('prc')) {
        pushMatches(/\b\d{7}\b/g);
    } else if (type.includes('tin')) {
        pushMatches(/\b\d{3}-?\d{3}-?\d{3}(?:-?\d{3})?\b/g);
    } else if (type.includes('philhealth')) {
        pushMatches(/\b\d{2}-?\d{9}-?\d\b/g);
    } else if (type.includes('pagibig')) {
        pushMatches(/\b\d{4}-?\d{4}-?\d{4}\b/g);
    } else if (type.includes('voter')) {
        pushMatches(/\b[A-Z0-9]{8,20}\b/gi);
    } else {
        pushMatches(/\b[A-Z0-9]{6,24}\b/gi);
    }

    lines.forEach(line => {
        const lowered = line.toLowerCase();
        if (lowered.includes('id') || lowered.includes('no') || lowered.includes('number')) {
            (line.match(/\b[A-Z0-9][A-Z0-9\-\/]{4,}\b/gi) || []).forEach(match => candidates.add(match.trim()));
        }
    });

    const sorted = Array.from(candidates)
        .map(candidate => candidate.replace(/\s+/g, '').trim())
        .filter(candidate => {
            if (!candidate) return false;
            if (blocked.has(candidate.toLowerCase())) return false;
            return /\d/.test(candidate) || /[-/]/.test(candidate);
        })
        .sort((a, b) => b.length - a.length);

    return sorted[0] || '';
}

function setGovernmentIdOcrStatus(message, isError = false) {
    const status = document.getElementById('governmentIdOcrStatus');
    if (!status) return;
    status.textContent = message;
    status.style.color = isError ? 'var(--danger)' : 'var(--gray-500)';
}

function getStoredGovernmentIdUploads() {
    try {
        const raw = sessionStorage.getItem('kycGovernmentIdFiles');
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function setStoredGovernmentIdUploads(files) {
    sessionStorage.setItem('kycGovernmentIdFiles', JSON.stringify(files || []));
}

let governmentIdOcrProfile = {};
let governmentIdOcrScanMeta = {};
let governmentIdRawOutput = { lines: [], confidences: [] };

function scoreGovernmentIdProfile(profile, confidence = 0) {
    const fields = [profile?.idNumber, profile?.fullName, profile?.birthdate, profile?.gender, profile?.nationality, profile?.address].filter(Boolean).length;
    return (fields * 1000) + Math.max(0, Number(confidence) || 0);
}

function normalizeOcrDate(value) {
    const raw = String(value || '').trim();
    if (!raw) return '';

    const isoMatch = raw.match(/\b(\d{4})[-\/](\d{2})[-\/](\d{2})\b/);
    if (isoMatch) {
        return `${isoMatch[1]}-${isoMatch[2]}-${isoMatch[3]}`;
    }

    const parts = raw.match(/\b(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})\b/);
    if (!parts) return '';

    let first = Number(parts[1]);
    let second = Number(parts[2]);
    const year = parts[3];

    let month = first;
    let day = second;
    if (first > 12 && second <= 12) {
        day = first;
        month = second;
    }

    const monthText = String(month).padStart(2, '0');
    const dayText = String(day).padStart(2, '0');
    return `${year}-${monthText}-${dayText}`;
}

function extractGovernmentIdProfile(text, idType) {
    const normalized = String(text || '')
        .replace(/\|/g, 'I')
        .replace(/[_]+/g, ' ')
        .replace(/[ \t]+/g, ' ')
        .trim();
    const lines = normalized.split(/\r?\n/).map(line => line.trim()).filter(Boolean);
    const lower = normalized.toLowerCase();
    const addressStopPattern = /\b(id|number|sex|gender|nationality|birth|date|name|signature|issue|valid)\b/i;

    function collectAddressFromIndex(startIndex) {
        const collected = [];
        for (let index = startIndex; index < lines.length && collected.length < 3; index += 1) {
            const line = lines[index].trim();
            if (!line) continue;
            if (addressStopPattern.test(line) && collected.length > 0) break;
            if (collected.length > 0 && /\b(id|number|sex|gender|nationality|birth|date|name)\b/i.test(line)) break;
            collected.push(line);
        }
        return collected.join(', ').replace(/^.*?(address|residence|home address|present address|permanent address)\s*[:\-]?\s*/i, '').trim();
    }

    const profile = {
        idNumber: inferGovernmentIdNumber(normalized, idType),
        fullName: '',
        birthdate: '',
        gender: '',
        nationality: '',
        address: ''
    };

    const type = String(idType || '').toLowerCase();
    if (type.includes('driver') || type.includes('philsys')) {
        const sexIndex = lines.findIndex(line => /\bsex\b/i.test(line));
        if (sexIndex !== -1) {
            for (let offset = 0; offset <= 6; offset += 1) {
                let target = lines[sexIndex + offset] || '';
                if (!target) continue;
                if (offset === 0) {
                    target = target.replace(/\bsex(?:\s+at\s+birth)?\b/i, ' ');
                }

                const upper = target.toUpperCase().trim();
                if (/\bFEMALE\b/.test(upper) || /^F$/.test(upper)) {
                    profile.gender = 'female';
                    break;
                }
                if (/\bMALE\b/.test(upper) || /^M$/.test(upper)) {
                    profile.gender = 'male';
                    break;
                }
            }
        }
    }

    if (type.includes('philsys')) {
        const pickAfterLabel = (labelRegex) => {
            const idx = lines.findIndex(line => labelRegex.test(line));
            if (idx === -1) return '';

            for (let offset = 0; offset <= 2; offset += 1) {
                let target = lines[idx + offset] || '';
                if (!target) continue;
                if (offset === 0) {
                    target = target.replace(labelRegex, ' ').replace(/[:\-]+/g, ' ');
                }

                const cleaned = target.replace(/[^A-Za-z\s,.-]/g, ' ').replace(/\s+/g, ' ').trim();
                if (cleaned && !/\d/.test(cleaned)) {
                    return cleaned;
                }
            }

            return '';
        };

        const surname = pickAfterLabel(/\b(surname|last\s*name)\b/i);
        const given = pickAfterLabel(/\b(given\s*name|first\s*name)\b/i);
        const middle = pickAfterLabel(/\b(middle\s*name)\b/i);

        if (!profile.fullName && (surname || given)) {
            profile.fullName = [
                surname ? `${surname},` : '',
                given,
                middle
            ].filter(Boolean).join(' ').replace(/\s+,/g, ',').replace(/\s+/g, ' ').trim();
        }

        if (!profile.nationality) {
            profile.nationality = 'Philippine';
        }
    }

    if (!profile.gender) {
        const genderMatch = lower.match(/\b(male|female)\b/);
        if (genderMatch) profile.gender = genderMatch[1];
    }

    if (/\b(filipino|philippine|philippines)\b/i.test(normalized)) {
        profile.nationality = 'Philippine';
    }

    const dobMatch = normalized.match(/\b(\d{4}[-\/]\d{2}[-\/]\d{2}|\d{1,2}[-\/]\d{1,2}[-\/]\d{4})\b/);
    if (dobMatch) {
        profile.birthdate = normalizeOcrDate(dobMatch[1]);
    }

    const addressDirect = normalized.match(/(?:address|residence|home address|present address|permanent address)\s*[:\-]\s*([A-Za-z0-9 ,.'\/#-]{8,})/i);
    if (addressDirect && addressDirect[1]) {
        profile.address = addressDirect[1].trim();
    } else {
        const addressIndex = lines.findIndex(line => /\b(address|residence|home address|present address|permanent address)\b/i.test(line));
        if (addressIndex !== -1) {
            profile.address = collectAddressFromIndex(addressIndex);
        }
    }

    const keywordLines = lines.filter(line => /\b(name|surname|given|first|middle|last)\b/i.test(line));
    const nextLine = keywordLines.length > 0 ? lines[lines.indexOf(keywordLines[0]) + 1] : '';
    const directName = normalized.match(/(?:name|surname|given name|first name|middle name|last name)\s*[:\-]\s*([A-Za-z ,.'-]{4,})/i);

    if (directName && directName[1]) {
        profile.fullName = directName[1].trim();
    } else if (nextLine && !/\b(id|number|sex|gender|nationality|birth|date)\b/i.test(nextLine)) {
        profile.fullName = nextLine.trim();
    }

    return profile;
}

function parseGovernmentIdAddress(addressText) {
    const normalized = String(addressText || '')
        .replace(/\r/g, '\n')
        .replace(/[•·]/g, ' ')
        .replace(/\b(?:address|residence|business address|present address|office address)\s*[:\-]\s*/ig, '')
        .replace(/\s{2,}/g, ' ')
        .trim();

    const emptyParts = { street: '', barangay: '', city: '', province: '', region: '' };
    if (!normalized) {
        return emptyParts;
    }

    const lines = normalized
        .split(/\n+/)
        .map(line => line.trim())
        .filter(Boolean);

    const removePostalCode = value => String(value || '').replace(/\b\d{4}\b/g, '').replace(/\s{2,}/g, ' ').trim();
    const cleanValue = value => removePostalCode(String(value || '').replace(/^[\s,;:\-]+|[\s,;:\-]+$/g, '').trim());
    const labeledValue = (line, labels) => {
        for (const label of labels) {
            const match = line.match(new RegExp(`\\b${label}\\b\\s*[:\\-]?\\s*(.+)$`, 'i'));
            if (match && match[1]) {
                return cleanValue(match[1]);
            }
        }
        return '';
    };
    const stripLabels = line => cleanValue(line.replace(/\b(?:region|province|prov\.?|city|municipality|mun\.?|town|barangay|brgy\.?|street|st\.?|address|residence|office|unit|building|block|lot|floor|house|no\.?|#)\b[:\-]?/ig, ' ').replace(/\s{2,}/g, ' '));
    const looksLikeRegion = value => /\b(region|ncr|car|mimaropa|calabarzon|soccsksargen|bangsamoro|barmm)\b/i.test(value);
    const looksLikeCity = value => /\b(city|municipality|mun\.?|town)\b/i.test(value);
    const looksLikeBarangay = value => /\b(barangay|brgy\.?|bgy\.?)\b/i.test(value);

    let region = '';
    let province = '';
    let city = '';
    let barangay = '';
    const streetParts = [];
    const unlabeledParts = [];

    for (const line of lines) {
        const regionValue = labeledValue(line, ['region', 'rgn']);
        const provinceValue = labeledValue(line, ['province', 'prov']);
        const cityValue = labeledValue(line, ['city', 'municipality', 'municipal', 'mun', 'town']);
        const barangayValue = labeledValue(line, ['barangay', 'brgy', 'bgy']);
        const streetValue = labeledValue(line, ['street', 'st', 'address', 'residence', 'office', 'house', 'unit', 'building', 'lot', 'block', 'floor']);

        if (regionValue && !region) {
            region = regionValue;
            continue;
        }

        if (provinceValue && !province) {
            province = provinceValue;
            continue;
        }

        if (cityValue && !city) {
            city = cityValue;
            continue;
        }

        if (barangayValue && !barangay) {
            barangay = barangayValue;
            continue;
        }

        if (streetValue) {
            streetParts.push(streetValue);
            continue;
        }

        const strippedLine = stripLabels(line);
        if (!strippedLine) {
            continue;
        }

        if (!region && looksLikeRegion(strippedLine)) {
            region = strippedLine;
            continue;
        }

        if (!barangay && looksLikeBarangay(strippedLine)) {
            barangay = strippedLine;
            continue;
        }

        if (!city && looksLikeCity(strippedLine)) {
            city = strippedLine;
            continue;
        }

        unlabeledParts.push(strippedLine);
    }

    if (!region && unlabeledParts.length >= 1) {
        const regionGuess = unlabeledParts[unlabeledParts.length - 1];
        if (looksLikeRegion(regionGuess) || unlabeledParts.length >= 4) {
            region = regionGuess;
            unlabeledParts.pop();
        }
    }

    if (!province && unlabeledParts.length >= 1) {
        province = unlabeledParts[unlabeledParts.length - 1];
        unlabeledParts.pop();
    }

    if (!city && unlabeledParts.length >= 1) {
        city = unlabeledParts[unlabeledParts.length - 1];
        unlabeledParts.pop();
    }

    if (!barangay && unlabeledParts.length >= 1) {
        barangay = unlabeledParts[unlabeledParts.length - 1];
        unlabeledParts.pop();
    }

    const street = [...streetParts, ...unlabeledParts].filter(Boolean).join(', ').trim();

    return {
        street,
        barangay,
        city,
        province,
        region
    };
}

function applyParsedAddressToFieldSet(prefix, addressText, touchedFields = []) {
    const parts = parseGovernmentIdAddress(addressText);
    const setIfEmpty = (id, value) => {
        const el = document.getElementById(id);
        if (!el || !value || el.value.trim()) return;
        el.value = value;
        el.dispatchEvent(new Event('input'));
        el.dispatchEvent(new Event('change'));
        touchedFields.push(id);
    };

    if (prefix === 'business') {
        setIfEmpty('corporateStreet', parts.street || addressText);
        setIfEmpty('corporateBusinessBarangay', parts.barangay);
        setIfEmpty('corporateBusinessCtm', parts.city);
        setIfEmpty('corporateBusinessProvince', parts.province);
        setIfEmpty('region', parts.region);
        setIfEmpty('corporateBusinessAddress', addressText);
    }
}

function setGovernmentIdFieldHighlight(id, enabled) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.toggle('ocr-field-highlight', Boolean(enabled));
}

function clearGovernmentIdFieldHighlights() {
    [
        'idNumber', 'gender', 'corporateGender', 'corporateContactPerson',
        'corporateStreet', 'corporateBusinessBarangay', 'corporateBusinessCtm', 'corporateBusinessProvince', 'region', 'corporateBusinessAddress'
    ].forEach(id => setGovernmentIdFieldHighlight(id, false));
}

function setGovernmentIdOcrProfile(profile) {
    governmentIdOcrProfile = profile || {};
    sessionStorage.setItem('kycGovernmentIdOcrData', JSON.stringify(governmentIdOcrProfile));
    renderGovernmentIdOcrSummary();
}

function setGovernmentIdOcrScanMeta(meta) {
    governmentIdOcrScanMeta = meta || {};
    sessionStorage.setItem('kycGovernmentIdOcrMeta', JSON.stringify(governmentIdOcrScanMeta));
    renderGovernmentIdOcrSummary();
}

function setGovernmentIdRawOutput(lines = [], confidences = []) {
    const normalizedLines = Array.isArray(lines)
        ? lines.map(line => String(line || '').trim()).filter(Boolean)
        : [];
    const normalizedConfidences = Array.isArray(confidences)
        ? confidences.map(value => Number(value) || 0)
        : [];

    governmentIdRawOutput = {
        lines: normalizedLines,
        confidences: normalizedConfidences,
    };

    sessionStorage.setItem('kycGovernmentIdOcrRaw', JSON.stringify(governmentIdRawOutput));
    renderGovernmentIdRawOutput();
}

function renderGovernmentIdRawOutput() {
    const debugEl = document.getElementById('governmentIdOcrDebug');
    if (!debugEl) return;

    const payload = governmentIdRawOutput || JSON.parse(sessionStorage.getItem('kycGovernmentIdOcrRaw') || '{}');
    const lines = Array.isArray(payload?.lines) ? payload.lines : [];
    const confidences = Array.isArray(payload?.confidences) ? payload.confidences : [];

    if (!lines.length) {
        debugEl.innerHTML = 'Raw OCR debug output will appear here.';
        return;
    }

    const rows = lines.map((line, index) => {
        const confidence = Number(confidences[index]);
        const confidenceText = Number.isFinite(confidence) && confidence > 0
            ? `${Math.round(confidence * 100)}%`
            : 'n/a';
        return `<div style="display:flex; gap:10px; margin-bottom:4px;"><span style="min-width:56px; color:var(--gray-500);">#${index + 1}</span><span style="flex:1; word-break:break-word;">${escapeHtml(line)}</span><span style="color:var(--gray-500);">${escapeHtml(confidenceText)}</span></div>`;
    }).join('');

    debugEl.innerHTML = `<strong>OCR Debug (raw lines)</strong><div style="margin-top:6px; font-size:0.8rem;">${rows}</div>`;
}

function applyGovernmentIdProfile(profile) {
    const touchedFields = [];
    const setIfEmpty = (id, value) => {
        const el = document.getElementById(id);
        if (!el || !value || el.value.trim()) return;
        el.value = value;
        el.dispatchEvent(new Event('input'));
        el.dispatchEvent(new Event('change'));
        touchedFields.push(id);
    };

    setIfEmpty('idNumber', profile.idNumber);
    setIfEmpty('gender', profile.gender);
    setIfEmpty('corporateGender', profile.gender);
    applyParsedAddressToFieldSet('business', profile.address, touchedFields);

    if (profile.fullName) {
        const contactPersonEl = document.getElementById('corporateContactPerson');
        if (contactPersonEl && !contactPersonEl.value.trim()) {
            contactPersonEl.value = profile.fullName;
            contactPersonEl.dispatchEvent(new Event('input'));
            contactPersonEl.dispatchEvent(new Event('change'));
            touchedFields.push('corporateContactPerson');
        }
    }

    clearGovernmentIdFieldHighlights();
    const confidence = Number(governmentIdOcrScanMeta?.confidence);
    if (!Number.isNaN(confidence) && confidence < 65) {
        touchedFields.forEach(id => setGovernmentIdFieldHighlight(id, true));
    }
}

function renderGovernmentIdOcrSummary() {
    const summaryEl = document.getElementById('governmentIdOcrSummary');
    if (!summaryEl) return;

    const profile = governmentIdOcrProfile || JSON.parse(sessionStorage.getItem('kycGovernmentIdOcrData') || '{}');
    const scanMeta = governmentIdOcrScanMeta || JSON.parse(sessionStorage.getItem('kycGovernmentIdOcrMeta') || '{}');
    const parts = [];
    const metaParts = [];
    const confidence = Number(scanMeta.confidence);
    let confidenceBadge = '';
    let warningText = '';

    if (!Number.isNaN(confidence) && confidence >= 0) {
        let qualityClass = 'is-low';
        let qualityLabel = 'Low';

        if (confidence >= 85) {
            qualityClass = 'is-high';
            qualityLabel = 'High';
        } else if (confidence >= 65) {
            qualityClass = 'is-good';
            qualityLabel = 'Good';
        } else if (confidence >= 40) {
            qualityClass = 'is-fair';
            qualityLabel = 'Fair';
        }

        confidenceBadge = `<span class="ocr-quality-badge ${qualityClass}">Quality: ${qualityLabel}</span>`;

        if (confidence < 65) {
            warningText = '<div style="margin-bottom:8px; color:#9d1f1f; font-weight:600;">Low-confidence scan. Please verify the extracted details before continuing.</div>';
        }
    }

    if (scanMeta.variant) metaParts.push(`Variant: ${escapeHtml(scanMeta.variant)}`);
    if (scanMeta.engine) metaParts.push(`Engine: ${escapeHtml(scanMeta.engine)}`);
    if (scanMeta.psm !== undefined && scanMeta.psm !== null && scanMeta.psm !== '') metaParts.push(`PSM: ${escapeHtml(String(scanMeta.psm))}`);
    if (scanMeta.confidence !== undefined && scanMeta.confidence !== null && scanMeta.confidence !== '') metaParts.push(`Confidence: ${escapeHtml(String(Math.round(scanMeta.confidence)))}%`);

    if (profile.fullName) parts.push(`<div><strong>Name:</strong> ${escapeHtml(profile.fullName)}</div>`);
    if (profile.idNumber) parts.push(`<div><strong>ID Number:</strong> ${escapeHtml(profile.idNumber)}</div>`);
    if (profile.birthdate) parts.push(`<div><strong>Birthdate:</strong> ${escapeHtml(profile.birthdate)}</div>`);
    if (profile.gender) parts.push(`<div><strong>Gender:</strong> ${escapeHtml(profile.gender)}</div>`);
    if (profile.nationality) parts.push(`<div><strong>Nationality:</strong> ${escapeHtml(profile.nationality)}</div>`);
    if (profile.address) parts.push(`<div><strong>Address:</strong> ${escapeHtml(profile.address)}</div>`);

    summaryEl.innerHTML = parts.length
        ? `<strong>OCR Summary</strong><div style="margin-top:6px;">${warningText}${metaParts.length ? `<div style="font-size:0.78rem; color:var(--gray-500); margin-bottom:8px; line-height:1.5; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">${confidenceBadge}${metaParts.map(item => `<span>${escapeHtml(item)}</span>`).join('<span style="opacity:.45;">•</span>')}</div>` : ''}${parts.join('')}</div>`
        : 'OCR summary will appear here after scanning.';
}

function loadImageFromFile(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
            const image = new Image();
            image.onload = () => resolve(image);
            image.onerror = () => reject(new Error('Unable to load image'));
            image.src = reader.result;
        };
        reader.onerror = () => reject(new Error('Unable to read file'));
        reader.readAsDataURL(file);
    });
}

async function preprocessGovernmentIdImage(file, mode = 'balanced') {
    const image = await loadImageFromFile(file);
    const maxWidth = mode === 'highContrast' ? 2200 : 1800;
    const scale = Math.min(1, maxWidth / image.width);
    const width = Math.max(1, Math.round(image.width * scale));
    const height = Math.max(1, Math.round(image.height * scale));

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const context = canvas.getContext('2d', { willReadFrequently: true });
    if (!context) throw new Error('Canvas not available');

    context.drawImage(image, 0, 0, width, height);
    const imageData = context.getImageData(0, 0, width, height);
    const data = imageData.data;

    for (let index = 0; index < data.length; index += 4) {
        const red = data[index];
        const green = data[index + 1];
        const blue = data[index + 2];
        let value = (red * 0.299) + (green * 0.587) + (blue * 0.114);

        if (mode === 'highContrast') {
            value = value > 160 ? 255 : 0;
        } else {
            value = Math.min(255, Math.max(0, (value - 18) * 1.15));
        }

        data[index] = value;
        data[index + 1] = value;
        data[index + 2] = value;
    }

    context.putImageData(imageData, 0, 0);
    return new Promise(resolve => canvas.toBlob(blob => resolve(blob || file), 'image/png', 1));
}

function renderGovernmentIdUploads() {
    const list = document.getElementById('governmentIdFileList');
    if (!list) return;

    const stored = getStoredGovernmentIdUploads();
    list.innerHTML = '';

    if (!stored.length) {
        list.innerHTML = '<div style="color: var(--gray-500); font-size: .85rem;">No ID uploaded yet.</div>';
        return;
    }

    stored.forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'file-item';
        item.dataset.idx = String(index);

        const name = file.original_name || file.file_name || 'ID photo';
        const openUrl = buildGovernmentIdOpenUrl(file);
        const previewImage = openUrl
            ? `<img src="${openUrl}" alt="ID Preview" style="width:64px;height:44px;object-fit:cover;border-radius:6px;border:1px solid #d5e3db;">`
            : '<i class="bi bi-file-earmark-image"></i>';
        item.innerHTML = `
            ${previewImage}
            <span>${escapeHtml(name)}</span>
            ${openUrl ? `<a href="${openUrl}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" style="padding:4px 10px; margin-left:auto;">Open</a>` : ''}
            <i class="bi bi-trash file-remove" title="Remove"></i>
        `;

        item.querySelector('.file-remove')?.addEventListener('click', async () => {
            const current = getStoredGovernmentIdUploads();
            const removed = current.splice(index, 1)[0];
            setStoredGovernmentIdUploads(current);
            currentGovernmentIdFile = null;
            renderGovernmentIdUploads();
            if (removed?.temp_path) {
                await deleteTempUpload(removed.temp_path);
            }
            setGovernmentIdOcrStatus('No ID photo uploaded yet.');
            setGovernmentIdRawOutput([], []);
        });

        list.appendChild(item);
    });
}

function buildGovernmentIdOpenUrl(file) {
    const rawPath = String(file?.file_path || file?.temp_path || '').trim();
    if (!rawPath) return '';

    const normalized = rawPath
        .replace(/^\.{1,2}[\\/]+/, '')
        .replace(/^[\\/]+/, '')
        .replace(/\\/g, '/');

    if (!normalized) return '';
    return normalized.startsWith('uploads/') ? `../../${normalized}` : `../../uploads/${normalized}`;
}

async function runGovernmentIdOcr(file) {
    if (!file) {
        setGovernmentIdOcrStatus('No ID photo selected for OCR.', true);
        renderGovernmentIdOcrSummary();
        return '';
    }

    const currentType = document.getElementById('governmentIdType')?.value || '';
    setGovernmentIdOcrStatus('Scanning...');

    try {
        const variants = [
            { label: 'balanced', blob: await preprocessGovernmentIdImage(file, 'balanced') },
            { label: 'high-contrast', blob: await preprocessGovernmentIdImage(file, 'highContrast') }
        ];

        let bestProfile = { idNumber: '' };
        let bestText = '';
        let bestConfidence = -1;
        let bestVariantLabel = '';
        let bestLines = [];
        let bestLineConfidences = [];
        let lastScanError = '';

        for (const variant of variants) {
            setGovernmentIdOcrStatus(`Scanning... (${variant.label})`);

            try {
                const result = await scanGovernmentIdWithGoogleVision(variant.blob, currentType, file.name || 'government-id.png');
                const extractedText = result?.text || '';
                const parsedProfile = result?.parsed || {};
                const extractedProfile = extractGovernmentIdProfile(extractedText, currentType);
                const profile = {
                    ...extractedProfile,
                    idNumber: parsedProfile.idNumber || extractedProfile.idNumber || '',
                    fullName: parsedProfile.fullName || extractedProfile.fullName || '',
                    birthdate: parsedProfile.birthdate || extractedProfile.birthdate || '',
                    gender: parsedProfile.gender || extractedProfile.gender || '',
                    nationality: parsedProfile.nationality || extractedProfile.nationality || '',
                    address: parsedProfile.address || extractedProfile.address || ''
                };

                setGovernmentIdOcrStatus('Processing...');
                const confidence = Number(result?.confidence) || 0;
                const score = scoreGovernmentIdProfile(profile, confidence);

                if (score > scoreGovernmentIdProfile(bestProfile, bestConfidence)) {
                    bestProfile = profile;
                    bestText = extractedText;
                    bestConfidence = confidence;
                    bestVariantLabel = variant.label;
                    bestLines = Array.isArray(result?.textLines) ? result.textLines : [];
                    bestLineConfidences = Array.isArray(result?.confidenceItems) ? result.confidenceItems : [];
                }

                if (profile.idNumber && (profile.fullName || profile.birthdate || profile.gender || profile.nationality)) {
                    break;
                }
            } catch (scanError) {
                lastScanError = scanError?.message || 'Google Vision scan failed';
            }

            if (bestProfile.idNumber && (bestProfile.fullName || bestProfile.birthdate || bestProfile.gender || bestProfile.nationality)) {
                break;
            }
        }

        if (!bestText && lastScanError) {
            throw new Error(lastScanError);
        }

        setGovernmentIdOcrScanMeta({
            variant: bestVariantLabel,
            engine: 'Google Vision',
            confidence: bestConfidence >= 0 ? bestConfidence : ''
        });
        setGovernmentIdRawOutput(bestLines, bestLineConfidences);
        setGovernmentIdOcrProfile(bestProfile);
        applyGovernmentIdProfile(bestProfile);

        if (bestProfile.idNumber) {
            const extractedFields = [bestProfile.fullName, bestProfile.birthdate, bestProfile.gender, bestProfile.nationality].filter(Boolean).length;
            setGovernmentIdOcrStatus(
                extractedFields > 0
                    ? `OCR completed. ID number and details found: ${bestProfile.idNumber}${bestConfidence >= 0 ? ` (confidence ${Math.round(bestConfidence)}%)` : ''}`
                    : `OCR completed. ID number found: ${bestProfile.idNumber}`
            );
            showToast('success', 'ID Scanned', 'ID number was extracted from the uploaded photo.');
            return bestProfile.idNumber;
        }

        if (bestText) {
            setGovernmentIdOcrStatus('OCR completed, but no confident ID number was found. Please enter it manually.', true);
            return '';
        }

        setGovernmentIdOcrStatus('Unable to scan ID. Please try again or enter manually.', true);
        return '';
    } catch (error) {
        console.error('OCR error:', error);
        setGovernmentIdOcrScanMeta({});
        setGovernmentIdRawOutput([], []);
        clearGovernmentIdFieldHighlights();
        setGovernmentIdOcrProfile({});
        const message = error?.message || 'Unable to scan ID. Please try again or enter manually.';
        setGovernmentIdOcrStatus(message, true);
        showToast('error', 'OCR Scan Failed', message);
        return '';
    }
}

async function scanGovernmentIdWithGoogleVision(fileBlob, idType, sourceName = 'government-id.png') {
    const extension = (sourceName.split('.').pop() || 'png').toLowerCase();
    const safeExt = ['jpg', 'jpeg', 'png'].includes(extension) ? extension : 'png';
    const fileName = `government-id.${safeExt}`;

    const fd = new FormData();
    fd.append('action', 'scan_id');
    fd.append('idType', idType || '');
    fd.append('id_image', fileBlob, fileName);

    const response = await fetch('../handlers/google_vision_ocr.php', {
        method: 'POST',
        credentials: 'include',
        body: fd
    });

    const raw = await response.text();
    let data = null;
    try {
        data = raw ? JSON.parse(raw) : null;
    } catch (parseError) {
        throw new Error(`OCR handler returned invalid JSON (HTTP ${response.status}).`);
    }

    if (!response.ok || !data?.success) {
        throw new Error(data?.message || 'Google Vision request failed');
    }

    const text = String(data.text || '');
    const textLines = text
        .split(/\r?\n/)
        .map(line => String(line || '').trim())
        .filter(Boolean);

    return {
        text,
        confidence: Number(data.confidence) || 0,
        parsed: data.parsed || {},
        textLines: Array.isArray(data.textLines) ? data.textLines : textLines,
        confidenceItems: Array.isArray(data.confidenceItems)
            ? data.confidenceItems
            : textLines.map(() => Number(data.confidence) || 0)
    };
}

async function checkGoogleVisionHealth() {
    const button = document.getElementById('ocrHealthCheckBtn');
    setButtonBusy(button, true, 'Testing...');
    setGovernmentIdOcrStatus('Checking Google Vision service...');

    try {
        const fd = new FormData();
        fd.append('action', 'health_check');

        const response = await fetch('../handlers/google_vision_ocr.php', {
            method: 'POST',
            credentials: 'include',
            body: fd
        });

        const data = await response.json();
        if (!response.ok || !data?.success) {
            throw new Error(data?.message || 'OCR health check failed');
        }

        setGovernmentIdOcrStatus('Google Vision is ready. You can scan an ID now.');
        showToast('success', 'OCR Ready', 'Google Vision API is configured and reachable.');
    } catch (error) {
        setGovernmentIdOcrStatus(error?.message || 'OCR health check failed.', true);
        showToast('error', 'OCR Check Failed', error?.message || 'Please check Google Vision API configuration.');
    } finally {
        setButtonBusy(button, false);
    }
}

async function scanCurrentGovernmentId() {
    if (!currentGovernmentIdFile) {
        setGovernmentIdOcrStatus('No ID photo selected for OCR.', true);
        showToast('error', 'No ID Photo', 'Please upload an ID photo first.');
        return;
    }

    await runGovernmentIdOcr(currentGovernmentIdFile);
}

function sanitizeGovernmentIdFiles(fileList) {
    const files = Array.from(fileList || []).slice(0, 1);
    if (!files.length) {
        return [];
    }

    const file = files[0];
    const allowedTypes = ['image/jpeg', 'image/png'];
    const maxBytes = 5 * 1024 * 1024;

    if (!allowedTypes.includes(file.type)) {
        showToast('error', 'Invalid File', 'Only JPG or PNG files are allowed.');
        setGovernmentIdOcrStatus('Please upload a JPG or PNG ID photo.', true);
        return [];
    }

    if (Number(file.size || 0) > maxBytes) {
        showToast('error', 'File Too Large', 'ID photo must be 5MB or below.');
        setGovernmentIdOcrStatus('ID photo must be 5MB or below.', true);
        return [];
    }

    return [file];
}

async function uploadGovernmentIdTempFile(files) {
    const selectedFiles = Array.from(files || []).slice(0, 1);
    if (!selectedFiles.length) return;

    const zone = document.getElementById('governmentIdUploadZone');
    if (zone) zone.classList.add('is-uploading');

    try {
        const previous = getStoredGovernmentIdUploads();
        await Promise.all((previous || []).map(item => deleteTempUpload(item?.temp_path)));

        const fd = new FormData();
        fd.append('action', 'upload_temp');
        selectedFiles.forEach(file => fd.append('documents[]', file, file.name));

        const resp = await fetch('../handlers/upload.php', { method: 'POST', body: fd });
        const data = await resp.json();
        if (!data || !data.success) {
            throw new Error(data?.message || 'Upload failed');
        }

        const saved = Array.isArray(data.files) ? data.files : [];
        setStoredGovernmentIdUploads(saved);
        renderGovernmentIdUploads();

        const file = selectedFiles[0];
        currentGovernmentIdFile = file;
        await runGovernmentIdOcr(file);
    } finally {
        if (zone) zone.classList.remove('is-uploading');
    }
}

let currentGovernmentIdFile = null;

const governmentIdInput = document.getElementById('governmentIdInput');
const governmentIdZone = document.getElementById('governmentIdUploadZone');
const governmentIdTypeSelect = document.getElementById('governmentIdType');

if (governmentIdInput) {
    governmentIdInput.addEventListener('change', async () => {
        const files = sanitizeGovernmentIdFiles(governmentIdInput.files || []);
        governmentIdInput.value = '';
        if (!files.length) return;
        try {
            await uploadGovernmentIdTempFile(files);
        } catch (error) {
            console.error(error);
            showToast('error', 'ID Upload Failed', error?.message || 'Please try again.');
            setGovernmentIdOcrStatus('Unable to upload the ID photo. Please try again.', true);
        }
    });
}

if (governmentIdZone) {
    governmentIdZone.addEventListener('dragenter', event => {
        event.preventDefault();
        event.stopPropagation();
        governmentIdZone.classList.add('dragover');
    });
    governmentIdZone.addEventListener('dragover', event => {
        event.preventDefault();
        event.stopPropagation();
        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'copy';
        }
        governmentIdZone.classList.add('dragover');
    });
    governmentIdZone.addEventListener('dragleave', event => {
        event.preventDefault();
        event.stopPropagation();
        governmentIdZone.classList.remove('dragover');
    });
    governmentIdZone.addEventListener('drop', async event => {
        event.preventDefault();
        event.stopPropagation();
        governmentIdZone.classList.remove('dragover');
        const files = sanitizeGovernmentIdFiles(event.dataTransfer?.files || []);
        if (!files.length) return;
        try {
            await uploadGovernmentIdTempFile(files);
        } catch (error) {
            console.error(error);
            showToast('error', 'ID Upload Failed', error?.message || 'Please try again.');
            setGovernmentIdOcrStatus('Unable to upload the ID photo. Please try again.', true);
        }
    });
}

if (governmentIdTypeSelect) {
    governmentIdTypeSelect.addEventListener('change', async () => {
        validateGovernmentIdSection();
        if (currentGovernmentIdFile) {
            await runGovernmentIdOcr(currentGovernmentIdFile);
        }
    });
}

document.addEventListener('DOMContentLoaded', renderGovernmentIdUploads);
document.addEventListener('DOMContentLoaded', () => {
    const savedProfile = JSON.parse(sessionStorage.getItem('kycGovernmentIdOcrData') || '{}');
    const savedRaw = JSON.parse(sessionStorage.getItem('kycGovernmentIdOcrRaw') || '{}');
    governmentIdOcrProfile = savedProfile || {};
    governmentIdRawOutput = savedRaw || { lines: [], confidences: [] };
    renderGovernmentIdOcrSummary();
    renderGovernmentIdRawOutput();
});

// ── Drafts UI (resume/load) ─────────────────────────────────────────────
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function parseComposedAddress(addressStr) {
    // Expected format from buildAddress(): "street, barangay, city, province, region"
    if (!addressStr) return null;
    const parts = String(addressStr).split(',').map(p => p.trim()).filter(Boolean);
    if (parts.length < 5) return null;
    return {
        street: parts[0],
        barangay: parts[1],
        city: parts[2],
        province: parts[3],
        region: parts.slice(4).join(', ')
    };
}

function waitForSelectReady(selectEl, minOptions = 2, timeoutMs = 8000) {
    return new Promise(resolve => {
        const start = Date.now();
        const timer = setInterval(() => {
            const ok = selectEl && selectEl.options && selectEl.options.length >= minOptions && !selectEl.disabled;
            if (ok) {
                clearInterval(timer);
                resolve(true);
                return;
            }
            if (Date.now() - start >= timeoutMs) {
                clearInterval(timer);
                resolve(false);
            }
        }, 200);
    });
}

async function restoreCorporateAddressFromDraftAddress(addressStr) {
    const parsed = parseComposedAddress(addressStr);
    if (!parsed) return;

    const regionEl = document.getElementById('region');
    const provinceEl = document.getElementById('corporateBusinessProvince');
    const cityEl = document.getElementById('corporateBusinessCtm');
    const barangayEl = document.getElementById('corporateBusinessBarangay');
    const streetEl = document.getElementById('corporateStreet');

    if (!regionEl || !provinceEl || !cityEl || !barangayEl || !streetEl) return;

    const regionReady = await waitForSelectReady(regionEl, 2);
    if (!regionReady) return;

    regionEl.value = parsed.region;
    regionEl.dispatchEvent(new Event('change'));

    const provinceReady = await waitForSelectReady(provinceEl, 2);
    if (!provinceReady) return;
    provinceEl.value = parsed.province;
    provinceEl.dispatchEvent(new Event('change'));

    const cityReady = await waitForSelectReady(cityEl, 2);
    if (!cityReady) return;
    cityEl.value = parsed.city;
    cityEl.dispatchEvent(new Event('change'));

    const barangayReady = await waitForSelectReady(barangayEl, 2);
    if (!barangayReady) return;
    barangayEl.value = parsed.barangay;

    streetEl.value = parsed.street;
    syncCorporateAddressField();
}

async function loadSelectedDraft() {
    const draftSelect = document.getElementById('draftSelect');
    const loadDraftBtn = document.getElementById('loadDraftBtn');
    const draftDocsContainer = document.getElementById('draftDocsContainer');
    const draftInfoEl = document.getElementById('draftInfo');

    if (!draftSelect || !loadDraftBtn) return;
    const refCode = draftSelect.value;
    if (!refCode) return;

    setButtonBusy(loadDraftBtn, true, 'Loading...');
    if (draftDocsContainer) draftDocsContainer.innerHTML = 'Loading attachments...';
    if (draftInfoEl) draftInfoEl.textContent = 'Loading draft...';

    try {
        const kycResp = await fetch(`../handlers/kyc.php?action=get_kyc&ref_code=${encodeURIComponent(refCode)}`, {
            method: 'GET',
            credentials: 'include'
        });
        const kycData = await kycResp.json();
        if (!kycData || !kycData.success) {
            showToast('error', 'Load Draft Failed', kycData?.message || 'Unable to load the selected draft.');
            return;
        }

        const draft = kycData.data || {};

        // Apply fields (only those present/mapped for the corporate form).
        const refInput = document.getElementById('refCode');
        if (refInput) {
            refInput.value = draft.ref_code || draft.reference_code || refCode;
            refInput.readOnly = true;
        }

        const setIfEl = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.value = value ?? '';
        };

        setIfEl('corporateClientName', draft.company);
        setIfEl('corporatePhone', draft.mobile || draft.phone);
        setIfEl('corporateEmail', draft.email);
        setIfEl('corporateContactPerson', draft.occupation);
        setIfEl('corporateGender', draft.gender);

        await restoreCorporateAddressFromDraftAddress(draft.address);

        if (draftInfoEl) {
            const updatedAt = draft.updated_at ? new Date(draft.updated_at).toLocaleString() : '';
            draftInfoEl.textContent = `Loaded ${refCode}${updatedAt ? ` (updated: ${escapeHtml(updatedAt)})` : ''}.`;
        }

        const docsResp = await fetch(`../handlers/kyc.php?action=get_draft_documents&ref_code=${encodeURIComponent(refCode)}`, {
            method: 'GET',
            credentials: 'include'
        });
        const docsData = await docsResp.json();
        const docs = (docsData && docsData.success) ? (docsData.data || []) : [];
        const governmentIdDocs = docs.filter(doc => {
            const docType = String(doc.document_type || '').toLowerCase();
            return docType === 'government_id' || docType === 'id' || docType === 'id_photo';
        });

        if (!draftDocsContainer) return;
        if (!docs.length) {
            draftDocsContainer.innerHTML = `<div style="color: var(--gray-500);">No saved attachments for this draft yet.</div>`;
        } else {
            draftDocsContainer.innerHTML = docs.map(doc => {
                const fileUrl = `../../${doc.file_path}`;
                const name = escapeHtml(doc.file_name || 'file');
                const ext = (doc.file_name || '').split('.').pop().toLowerCase();
                const icon = ext === 'pdf' ? 'bi-file-earmark-pdf' : 'bi-file-earmark';
                const size = doc.file_size ? ` (${escapeHtml(String(doc.file_size))} bytes)` : '';

                return `
                    <div class="file-item" style="margin-bottom:10px;">
                        <i class="bi ${icon}"></i>
                        <span>${name}</span>
                        <span style="color: var(--gray-500); font-size: .8rem;">${escapeHtml(size)}</span>
                        <div style="margin-top:6px;">
                            <a href="${fileUrl}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Open</a>
                        </div>
                    </div>
                `;
            }).join('');
        }

        setStoredGovernmentIdUploads(governmentIdDocs.map(doc => ({
            original_name: doc.file_name || '',
            file_name: doc.file_name || '',
            file_type: doc.file_type || null,
            file_size: doc.file_size || null,
            file_path: doc.file_path || null
        })));
        renderGovernmentIdUploads();

        // Also load attachments into the form's attachment holder.
        const draftSessionUploads = docs.map(doc => ({
            file_name: doc.file_name || '',
            original_name: doc.file_name || '',
            file_type: doc.file_type || null,
            file_size: doc.file_size || null,
            file_path: doc.file_path || null
        }));

        if (typeof setStoredUploads === 'function' && typeof renderStoredUploads === 'function') {
            setStoredUploads(draftSessionUploads || []);
            renderStoredUploads();
        }
    } catch (error) {
        console.error('Error loading draft:', error);
        showToast('error', 'Load Draft Failed', 'Unexpected error while loading the draft.');
    } finally {
        setButtonBusy(loadDraftBtn, false);
    }
}

async function refreshDrafts() {
    const draftSelect = document.getElementById('draftSelect');
    const loadDraftBtn = document.getElementById('loadDraftBtn');
    const refreshDraftBtn = document.getElementById('refreshDraftBtn');
    const draftDocsContainer = document.getElementById('draftDocsContainer');
    const draftInfoEl = document.getElementById('draftInfo');

    if (!draftSelect) return;

    setButtonBusy(refreshDraftBtn, true, 'Refreshing...');

    draftSelect.innerHTML = `<option value="">Loading...</option>`;
    draftSelect.value = '';
    if (loadDraftBtn) loadDraftBtn.disabled = true;
    if (draftDocsContainer) draftDocsContainer.innerHTML = '';
    if (draftInfoEl) draftInfoEl.textContent = '';

    try {
        const resp = await fetch(`../handlers/kyc.php?action=get_drafts&draftType=${encodeURIComponent(currentClientType)}`, {
            method: 'GET',
            credentials: 'include'
        });
        const data = await resp.json();
        const drafts = (data && data.success) ? (data.data || []) : [];

        if (!drafts.length) {
            draftSelect.innerHTML = `<option value="">No drafts found</option>`;
            if (loadDraftBtn) loadDraftBtn.disabled = true;
            return;
        }

        draftSelect.innerHTML = `
            <option value="">Select a draft...</option>
        ` + drafts.map(d => {
            const refCode = d.ref_code || d.reference_code || '';
            const label = (d.company || d.email || 'Draft');
            return `<option value="${escapeHtml(refCode)}">${escapeHtml(refCode)} - ${escapeHtml(label)}</option>`;
        }).join('');

        draftSelect.onchange = function () {
            if (loadDraftBtn) loadDraftBtn.disabled = !this.value;
        };
    } catch (error) {
        console.error('Error loading drafts:', error);
        draftSelect.innerHTML = `<option value="">Failed to load drafts</option>`;
        if (loadDraftBtn) loadDraftBtn.disabled = true;
    } finally {
        setButtonBusy(refreshDraftBtn, false);
    }
}

// Load drafts list on page open.
document.addEventListener('DOMContentLoaded', () => {
    const draftSelect = document.getElementById('draftSelect');
    if (!draftSelect) return;
    refreshDrafts();
});

function toggleDraftsPanel() {
    const panel = document.getElementById('draftsCard');
    const toggleBtn = document.querySelector('.drafts-toggle-btn');
    if (!panel) return;
    const willOpen = !panel.classList.contains('open');
    panel.classList.toggle('open', willOpen);
    document.body.classList.toggle('drafts-popup-open', willOpen);
    if (toggleBtn) {
        toggleBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    }
    if (willOpen && typeof refreshDrafts === 'function') {
        requestAnimationFrame(positionDraftsPanel);
        refreshDrafts();
    }
}

function positionDraftsPanel() {
    const panel = document.getElementById('draftsCard');
    const toggleBtn = document.querySelector('.drafts-toggle-btn');
    if (!panel || !toggleBtn || !panel.classList.contains('open')) {
        return;
    }

    const gap = 10;
    const pad = 8;

    panel.style.right = 'auto';
    panel.style.bottom = 'auto';
    panel.style.left = '-9999px';
    panel.style.top = '-9999px';

    const buttonRect = toggleBtn.getBoundingClientRect();
    const panelRect = panel.getBoundingClientRect();

    let left = buttonRect.right - panelRect.width;
    let top = buttonRect.bottom + gap;

    const maxLeft = window.innerWidth - panelRect.width - pad;
    if (left > maxLeft) left = Math.max(pad, maxLeft);
    if (left < pad) left = pad;

    if (top + panelRect.height > window.innerHeight - pad) {
        top = buttonRect.top - panelRect.height - gap;
    }
    if (top < pad) top = pad;

    panel.style.left = `${Math.round(left)}px`;
    panel.style.top = `${Math.round(top)}px`;
}

function closeDraftsPanel() {
    const panel = document.getElementById('draftsCard');
    const toggleBtn = document.querySelector('.drafts-toggle-btn');
    if (!panel) return;
    panel.classList.remove('open');
    document.body.classList.remove('drafts-popup-open');
    if (toggleBtn) {
        toggleBtn.setAttribute('aria-expanded', 'false');
    }
}

document.addEventListener('click', function (event) {
    const panel = document.getElementById('draftsCard');
    const toggleBtn = document.querySelector('.drafts-toggle-btn');
    if (!panel || !panel.classList.contains('open')) return;

    const clickedInsidePanel = panel.contains(event.target);
    const clickedToggle = !!(toggleBtn && (toggleBtn === event.target || toggleBtn.contains(event.target)));
    if (!clickedInsidePanel && !clickedToggle) {
        closeDraftsPanel();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeDraftsPanel();
    }
});

window.addEventListener('resize', positionDraftsPanel);
window.addEventListener('scroll', positionDraftsPanel, true);

let kycMasonryRaf = 0;
let kycMasonryObserver = null;

function getKycMasonryItems(form) {
    return Array.from(form.children).filter((el) => {
        if (el.id === 'draftsCard') return false;
        if (el.classList.contains('wizard-hidden')) return false;
        return el.classList.contains('card') || el.classList.contains('client-type-inline');
    });
}

function layoutKycMasonry() {
    const form = document.getElementById('kycForm');
    if (!form) return;

    const items = getKycMasonryItems(form);
    if (!items.length) {
        form.style.minHeight = '0px';
        return;
    }

    const columns = window.matchMedia('(max-width: 1100px)').matches ? 1 : 2;
    const gap = parseFloat(getComputedStyle(form).getPropertyValue('--masonry-gap')) || 14;
    const formWidth = form.clientWidth;
    const columnWidth = columns > 1 ? (formWidth - gap) / columns : formWidth;
    let heights = new Array(columns).fill(0);

    items.forEach((item) => {
        const isSpanAll = columns === 1 || item.classList.contains('client-type-inline') || item.classList.contains('card-span-2');

        item.style.position = 'absolute';
        item.style.maxWidth = 'none';

        if (isSpanAll) {
            const top = Math.max(...heights);
            item.style.left = '0px';
            item.style.top = `${Math.round(top)}px`;
            item.style.width = `${Math.round(formWidth)}px`;

            const nextTop = top + item.offsetHeight + gap;
            heights = heights.map(() => nextTop);
            return;
        }

        let targetColumn = 0;
        for (let i = 1; i < heights.length; i += 1) {
            if (heights[i] < heights[targetColumn]) {
                targetColumn = i;
            }
        }

        const top = heights[targetColumn];
        const left = targetColumn * (columnWidth + gap);

        item.style.left = `${Math.round(left)}px`;
        item.style.top = `${Math.round(top)}px`;
        item.style.width = `${Math.round(columnWidth)}px`;

        heights[targetColumn] = top + item.offsetHeight + gap;
    });

    const contentHeight = Math.max(...heights) - gap;
    form.style.minHeight = `${Math.max(0, Math.round(contentHeight))}px`;
}

function scheduleKycMasonryLayout() {
    if (kycMasonryRaf) return;
    kycMasonryRaf = requestAnimationFrame(() => {
        kycMasonryRaf = 0;
        layoutKycMasonry();
    });
}

function initKycMasonryObserver() {
    const form = document.getElementById('kycForm');
    if (!form || typeof ResizeObserver === 'undefined') return;

    if (kycMasonryObserver) {
        kycMasonryObserver.disconnect();
    }

    kycMasonryObserver = new ResizeObserver(() => {
        scheduleKycMasonryLayout();
    });

    getKycMasonryItems(form).forEach((item) => {
        kycMasonryObserver.observe(item);
    });
}

window.addEventListener('resize', scheduleKycMasonryLayout);
window.addEventListener('load', scheduleKycMasonryLayout);

const WIZARD_MIN_STEP = 2;
const WIZARD_MAX_STEP = 4;
let currentWizardStep = WIZARD_MIN_STEP;

function validateWizardStep(step) {
    const stepCards = document.querySelectorAll(`#kycForm > .card[data-wizard-step="${step}"]`);
    if (!stepCards.length) return true;

    let allValid = true;
    const requiredRadioNames = new Set();

    stepCards.forEach((card) => {
        const requiredInputs = card.querySelectorAll('input[required], select[required], textarea[required]');

        requiredInputs.forEach((el) => {
            if (el.type === 'radio') {
                if (el.name) requiredRadioNames.add(el.name);
                return;
            }

            if (el.id) {
                if (!validateField(el.id)) allValid = false;
            } else {
                const value = (el.value || '').trim();
                if (!value) {
                    allValid = false;
                    el.classList.add('is-invalid');
                } else {
                    el.classList.remove('is-invalid');
                }
            }
        });

        if (card.querySelector('#governmentIdUploadZone') && !validateGovernmentIdSection()) {
            allValid = false;
        }
    });

    requiredRadioNames.forEach((name) => {
        if (!validateRadioGroup(name)) allValid = false;
    });

    return allValid;
}

function updateWizardProgress(step) {
    const steps = {
        2: document.getElementById('step-2'),
        3: document.getElementById('step-3'),
        4: document.getElementById('step-4')
    };
    const lines = document.querySelectorAll('.steps-bar .step-line');

    Object.entries(steps).forEach(([key, el]) => {
        if (!el) return;
        const n = Number(key);
        el.classList.toggle('done', n < step);
        el.classList.toggle('active', n === step);
    });

    if (lines[1]) lines[1].classList.toggle('done', step >= 3);
    if (lines[2]) lines[2].classList.toggle('done', step >= 4);
}

function applyWizardStep(step) {
    const cards = document.querySelectorAll('#kycForm > .card[data-wizard-step]');
    cards.forEach((card) => {
        const cardStep = Number(card.getAttribute('data-wizard-step'));
        card.classList.toggle('wizard-hidden', cardStep !== step);
    });

    const prevBtn = document.getElementById('wizardPrevBtn');
    const nextBtn = document.getElementById('wizardNextBtn');
    const proceedBtn = document.getElementById('proceedBtn');

    if (prevBtn) prevBtn.style.display = step > WIZARD_MIN_STEP ? '' : 'none';
    if (nextBtn) nextBtn.style.display = step < WIZARD_MAX_STEP ? '' : 'none';
    if (proceedBtn) proceedBtn.style.display = step === WIZARD_MAX_STEP ? '' : 'none';

    updateWizardProgress(step);
    scheduleKycMasonryLayout();
}

function goToWizardStep(step) {
    const bounded = Math.max(WIZARD_MIN_STEP, Math.min(WIZARD_MAX_STEP, step));

    if (bounded > currentWizardStep + 1) {
        showToast('info', 'Step Locked', 'Complete the current step before jumping ahead.');
        return;
    }

    if (bounded > currentWizardStep && !validateWizardStep(currentWizardStep)) {
        showToast('error', 'Validation Failed', 'Please complete required fields in the current step first.');
        return;
    }

    currentWizardStep = bounded;
    applyWizardStep(currentWizardStep);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.addEventListener('DOMContentLoaded', () => {
    const prevBtn = document.getElementById('wizardPrevBtn');
    const nextBtn = document.getElementById('wizardNextBtn');

    if (prevBtn) {
        prevBtn.addEventListener('click', () => goToWizardStep(currentWizardStep - 1));
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', () => goToWizardStep(currentWizardStep + 1));
    }

    const stepOne = document.getElementById('step-1');
    if (stepOne) {
        stepOne.classList.add('step-clickable');
        stepOne.setAttribute('role', 'button');
        stepOne.setAttribute('tabindex', '0');
        stepOne.addEventListener('click', () => goBack());
        stepOne.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                goBack();
            }
        });
    }

    [2, 3, 4].forEach((step) => {
        const stepEl = document.getElementById(`step-${step}`);
        if (!stepEl) return;
        stepEl.classList.add('step-clickable');
        stepEl.setAttribute('role', 'button');
        stepEl.setAttribute('tabindex', '0');
        stepEl.addEventListener('click', () => goToWizardStep(step));
        stepEl.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                goToWizardStep(step);
            }
        });
    });

    applyWizardStep(currentWizardStep);
    initKycMasonryObserver();
    scheduleKycMasonryLayout();
});

function proceedToReview() {
    const proceedBtn = document.getElementById('proceedBtn');
    if (proceedBtn?.disabled) return;

    syncCorporateAddressField();

    if (!validateAllRequired()) {
        showToast('error', 'Validation Failed', 'Please fill in all required fields marked with *');
        return;
    }

    setButtonBusy(proceedBtn, true, 'Preparing...');
    
    // Collect form data
    const formData = {};
    const form = document.getElementById('kycForm');
    const elements = form.querySelectorAll('input, select, textarea');
    elements.forEach(el => {
        if (el.name && el.value) {
            formData[el.name] = el.value;
        }
    });
    
    // Store in sessionStorage
    sessionStorage.setItem('kycFormData', JSON.stringify(formData));
    
    // Also store address components separately for reliable restoration
    const addressData = {
        region: document.getElementById('region').value,
        province: document.getElementById('corporateBusinessProvince').value,
        city: document.getElementById('corporateBusinessCtm').value,
        barangay: document.getElementById('corporateBusinessBarangay').value,
        street: document.getElementById('corporateStreet').value,
        composed: document.getElementById('corporateBusinessAddress').value
    };
    sessionStorage.setItem('corporateAddressData', JSON.stringify(addressData));
    sessionStorage.setItem('kycGovernmentIdFiles', JSON.stringify(getStoredGovernmentIdUploads()));
    
    // Navigate to review page
    window.location.href = currentReviewUrl;
}

function submitForm() {
    syncCorporateAddressField();

    if (!validateAllRequired()) {
        showToast('error', 'Validation Failed', 'Please fill in all required fields marked with *');
        return;
    }
    
    // Collect form data
    const formData = new FormData();
    formData.append('action', 'add_client');
    
    // Add all form fields
    const form = document.getElementById('kycForm');
    const elements = form.querySelectorAll('input, select, textarea');
    elements.forEach(el => {
        if (el.name && el.value) {
            formData.append(el.name, el.value);
        }
    });
    
    // Submit to handler
    fetch('../handlers/client.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.reference_code && !document.getElementById('refCode').value) {
                document.getElementById('refCode').value = data.reference_code;
                document.getElementById('refCode').readOnly = true;
            }
            showToast('success', 'Client Saved!', data.reference_code ? `Reference Code: ${data.reference_code}` : 'Proceeding to Document Verification.');
            // Increment stat
            const tv = document.getElementById('stat-total');
            if (tv) tv.textContent = parseInt(tv.textContent) + 1;
            
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 2000);
        } else {
            showToast('error', 'Submission Failed', data.message || 'Please try again.');
        }
    })
    .catch(error => {
        showToast('error', 'Error', 'An error occurred. Please try again.');
        console.error('Error:', error);
    });
}

function saveDraft() {
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    if (saveDraftBtn?.disabled) return;

    syncCorporateAddressField();
    setButtonBusy(saveDraftBtn, true, 'Saving...');

    // Collect form data
    const formData = new FormData();
    formData.append('action', 'save_draft');
    
    // Add all form fields
    const form = document.getElementById('kycForm');
    const elements = form.querySelectorAll('input, select, textarea');
    elements.forEach(el => {
        if (el.name) {
            formData.append(el.name, el.value);
        }
    });

    // Persist attachments into `documents` for this draft.
    const uploadedFiles = getStoredUploads ? getStoredUploads() : [];
    formData.append('uploadedFiles', JSON.stringify(uploadedFiles || []));
    formData.append('uploadedIdFiles', JSON.stringify(getStoredGovernmentIdUploads() || []));
    
    // Submit to handler
    fetch('../handlers/kyc.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.reference_code && !document.getElementById('refCode').value) {
                document.getElementById('refCode').value = data.reference_code;
                document.getElementById('refCode').readOnly = true;
            }
            showToast('info', 'Draft Saved', data.reference_code ? `Reference Code: ${data.reference_code}` : 'Your progress has been saved successfully.');
        } else {
            showToast('error', 'Save Failed', data.message || 'Please try again.');
        }
    })
    .catch(error => {
        showToast('error', 'Error', 'An error occurred. Please try again.');
        console.error('Error:', error);
    })
    .finally(() => {
        setButtonBusy(saveDraftBtn, false);
    });
}

async function clearForm() {
    const clearBtn = document.getElementById('clearBtn');
    setButtonBusy(clearBtn, true, 'Clearing...');

    document.getElementById('kycForm').querySelectorAll('input, select').forEach(el => {
        if (el.readOnly) return;
        el.value = '';
        el.classList.remove('is-invalid','is-valid');
    });

    const draftSelect = document.getElementById('draftSelect');
    if (draftSelect) draftSelect.value = '';
    const loadDraftBtn = document.getElementById('loadDraftBtn');
    if (loadDraftBtn) loadDraftBtn.disabled = true;
    const draftInfoEl = document.getElementById('draftInfo');
    if (draftInfoEl) draftInfoEl.textContent = '';
    const draftDocsContainer = document.getElementById('draftDocsContainer');
    if (draftDocsContainer) draftDocsContainer.innerHTML = '';

    // Clear any temp-uploaded documents
    const uploads = (typeof getStoredUploads === 'function') ? getStoredUploads() : [];
    await Promise.all((uploads || []).map(u => deleteTempUpload(u?.temp_path)));
    sessionStorage.removeItem('kycUploadedFiles');
    const idUploads = getStoredGovernmentIdUploads();
    await Promise.all((idUploads || []).map(u => deleteTempUpload(u?.temp_path)));
    sessionStorage.removeItem('kycGovernmentIdFiles');
    sessionStorage.removeItem('kycGovernmentIdOcrData');
    sessionStorage.removeItem('kycGovernmentIdOcrRaw');
    currentGovernmentIdFile = null;
    governmentIdOcrProfile = {};
    governmentIdRawOutput = { lines: [], confidences: [] };
    document.getElementById('fileList').innerHTML = '';
    renderGovernmentIdUploads();
    renderGovernmentIdOcrSummary();
    showToast('info', 'Form Cleared', 'All fields have been reset.');
    setButtonBusy(clearBtn, false);
}

function goBack() {
    window.location.href = 'kyc-verification.php';
}

// ── File Upload ────────────────────────────────────────────
const zone   = document.getElementById('uploadZone');
const input  = document.getElementById('fileInput');
const list   = document.getElementById('fileList');

const UPLOAD_STORAGE_KEY = 'kycUploadedFiles';

function getStoredUploads() {
    try {
        const raw = sessionStorage.getItem(UPLOAD_STORAGE_KEY);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function setStoredUploads(files) {
    sessionStorage.setItem(UPLOAD_STORAGE_KEY, JSON.stringify(files || []));
}

function fileIconClass(filename) {
    const ext = (filename || '').split('.').pop().toLowerCase();
    const icons = { pdf:'bi-file-earmark-pdf', jpg:'bi-file-earmark-image', jpeg:'bi-file-earmark-image', png:'bi-file-earmark-image' };
    return icons[ext] || 'bi-file-earmark';
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes/1024).toFixed(1) + ' KB';
    return (bytes/1048576).toFixed(1) + ' MB';
}

async function deleteTempUpload(tempPath) {
    if (!tempPath) return;
    const fd = new FormData();
    fd.append('action', 'delete_temp');
    fd.append('path', tempPath);
    try {
        await fetch('../handlers/upload.php', { method: 'POST', body: fd });
    } catch {
        // Best-effort cleanup
    }
}

function buildUploadOpenUrl(file) {
    const rawPath = String(file?.file_path || file?.temp_path || '').trim();
    if (!rawPath) return '';

    const normalized = rawPath
        .replace(/^\.{1,2}[\\/]+/, '')
        .replace(/^[\\/]+/, '')
        .replace(/\\/g, '/');

    if (!normalized) return '';
    return normalized.startsWith('uploads/') ? `../../${normalized}` : `../../uploads/${normalized}`;
}

function renderStoredUploads() {
    if (!list) return;
    const stored = getStoredUploads();
    list.innerHTML = '';

    stored.forEach((f, idx) => {
        const item = document.createElement('div');
        item.className = 'file-item';
        item.dataset.idx = String(idx);

        const name = f.original_name || f.file_name || 'file';
        const size = Number(f.file_size || 0);
        const openUrl = buildUploadOpenUrl(f);
        const openBtnHtml = openUrl
            ? `<a href="${openUrl}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" style="padding:4px 10px; margin-left:auto;">Open</a>`
            : '';
        item.innerHTML = `
            <i class="bi ${fileIconClass(name)}"></i>
            <span>${name}</span>
            <small>${size ? formatSize(size) : ''}</small>
            ${openBtnHtml}
            <i class="bi bi-trash file-remove" title="Remove"></i>
        `;

        item.querySelector('.file-remove')?.addEventListener('click', async () => {
            const current = getStoredUploads();
            const removed = current.splice(idx, 1)[0];
            setStoredUploads(current);
            renderStoredUploads();
            if (removed?.temp_path) {
                await deleteTempUpload(removed?.temp_path);
            }
            showToast('info', 'File Removed', `${name} was removed.`);
        });

        list.appendChild(item);
    });
}

async function uploadTempFiles(files) {
    if (!files || !files.length) return;
    if (!zone) return;
    zone.classList.add('is-uploading');
    try {
        const fd = new FormData();
        fd.append('action', 'upload_temp');
        files.forEach(file => fd.append('documents[]', file, file.name));

        const resp = await fetch('../handlers/upload.php', { method: 'POST', body: fd });
        const data = await resp.json();
        if (!data || !data.success) {
            throw new Error(data?.message || 'Upload failed');
        }

        const stored = getStoredUploads();
        const newlySaved = Array.isArray(data.files) ? data.files : [];
        newlySaved.forEach(f => stored.push(f));
        setStoredUploads(stored);
        renderStoredUploads();

        showToast('success', 'Files Uploaded', `${newlySaved.length} file(s) uploaded.`);
    } finally {
        zone.classList.remove('is-uploading');
    }
}

if (input) {
    input.addEventListener('change', async () => {
        const files = Array.from(input.files || []);
        input.value = '';
        try {
            await uploadTempFiles(files);
        } catch (e) {
            showToast('error', 'Upload Failed', e?.message || 'Please try again.');
        }
    });
}

if (zone) {
    zone.addEventListener('dragenter', e => {
        e.preventDefault();
        e.stopPropagation();
        zone.classList.add('dragover');
    });
    zone.addEventListener('dragover', e => {
        e.preventDefault();
        e.stopPropagation();
        zone.classList.add('dragover');
    });
    zone.addEventListener('dragleave', e => {
        e.preventDefault();
        e.stopPropagation();
        zone.classList.remove('dragover');
    });
    zone.addEventListener('drop', async e => {
        e.preventDefault();
        e.stopPropagation();
        zone.classList.remove('dragover');
        const files = Array.from(e.dataTransfer?.files || []);
        try {
            await uploadTempFiles(files);
        } catch (err) {
            showToast('error', 'Upload Failed', err?.message || 'Please try again.');
        }
    });
}

// Render any existing temp uploads (e.g., returning from review)
document.addEventListener('DOMContentLoaded', renderStoredUploads);
document.addEventListener('DOMContentLoaded', revealFlowCards);

// ── Auto-gen Client Number ─────────────────────────────────
document.getElementById('refCode').addEventListener('input', function() {
    const cn = this.value ? 'CN-' + Date.now().toString().slice(-6) : '';
    document.getElementById('clientNumber').value = cn;
});

// ── Collapse Steps to Tiny Progress on Scroll ───────────────
const stepsBar = document.querySelector('.steps-bar');
const mainContent = document.querySelector('.main');

window.addEventListener('scroll', function() {
    if (!stepsBar) return;

    const scrollPosition = mainContent?.getBoundingClientRect().top || 0;

    if (scrollPosition < 0) {
        stepsBar.classList.add('sticky');
    } else {
        stepsBar.classList.remove('sticky');
    }
});

</script>

</body>
</html>
