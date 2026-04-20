<?php
require_once '../config/session.php';
require_once '../config/db.php';
requireLogin();

$classificationFromQuery = strtolower(trim($_GET['classification'] ?? 'client'));
$selectedClassification = $classificationFromQuery === 'agent' ? 'agent' : 'client';
$isAgentFlow = $selectedClassification === 'agent';

$currentUser = getCurrentUser() ?? [];
$currentUserBranch = strtoupper(trim((string)($currentUser['branch'] ?? '')));
$currentUserRole = strtolower(trim((string)($currentUser['role'] ?? '')));
$currentUserDepartment = strtoupper(trim((string)($currentUser['department'] ?? '')));
$currentUserFullName = trim((string)($currentUser['full_name'] ?? ''));
$isHeadOfficeUser = $currentUserRole === 'admin'
    || $currentUserDepartment === 'HEAD OFFICE'
    || in_array($currentUserBranch, ['HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH'], true);
$effectiveBranch = $currentUserBranch !== '' ? $currentUserBranch : ($isHeadOfficeUser ? 'HEAD OFFICE' : '');

function kycPageResolveBranchManagerName(string $branch, string $fallbackName = '', string $fallbackRole = ''): string {
    global $db;

    $branch = strtoupper(trim($branch));
    if ($branch === '' || !$db instanceof mysqli) {
        return '';
    }

    $stmt = $db->prepare(
        "SELECT full_name
         FROM users
         WHERE UPPER(TRIM(branch)) = ?
           AND LOWER(TRIM(role)) IN ('manager', 'admin')
           AND full_name IS NOT NULL
           AND TRIM(full_name) <> ''
         ORDER BY CASE WHEN LOWER(TRIM(role)) = 'manager' THEN 0 ELSE 1 END, user_id ASC
         LIMIT 1"
    );

    if ($stmt) {
        $stmt->bind_param('s', $branch);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;

        if ($result instanceof mysqli_result) {
            $result->free();
        }

        $stmt->close();

        if (!empty($row['full_name'])) {
            return trim((string)$row['full_name']);
        }
    }

    $fallbackName = trim($fallbackName);
    $fallbackRole = strtolower(trim($fallbackRole));
    if ($fallbackName !== '' && in_array($fallbackRole, ['manager', 'admin'], true)) {
        return $fallbackName;
    }

    return '';
}

function kycPageFetchHeadAgentOptions(string $branch): array {
    global $db;

    $branch = strtoupper(trim($branch));
    if ($branch === '' || !$db instanceof mysqli) {
        return [];
    }

    $options = [];
    $stmt = $db->prepare(
        "SELECT DISTINCT
            COALESCE(
                NULLIF(TRIM(c.client_name), ''),
                NULLIF(TRIM(CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, ''))), ''),
                c.reference_code
            ) AS head_agent_name
         FROM clients c
         WHERE COALESCE(NULLIF(LOWER(TRIM(c.client_classification)), ''), 'client') = 'agent'
           AND COALESCE(NULLIF(LOWER(TRIM(c.agent_type)), ''), 'agent') = 'agent'
                     AND COALESCE(NULLIF(UPPER(TRIM(c.agent_branch)), ''), '') = ?
           AND COALESCE(NULLIF(LOWER(TRIM(c.verification_status)), ''), '') = 'verified'
           AND COALESCE(NULLIF(LOWER(TRIM(c.activity_status)), ''), 'active') = 'active'
         ORDER BY head_agent_name ASC"
    );

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('s', $branch);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $name = trim((string)($row['head_agent_name'] ?? ''));
            if ($name !== '') {
                $options[] = $name;
            }
        }
        $result->free();
    }

    $stmt->close();

    return array_values(array_unique($options));
}

$branchManagerName = kycPageResolveBranchManagerName($effectiveBranch, $currentUserFullName, $currentUserRole);
$headAgentOptions = kycPageFetchHeadAgentOptions($effectiveBranch);
$headAgentOptions = array_values(array_filter($headAgentOptions, static function ($name) use ($branchManagerName) {
    return $branchManagerName === '' || strcasecmp(trim((string)$name), $branchManagerName) !== 0;
}));

$clientTypeLabel = $isAgentFlow ? 'Individual Agent' : 'Individual Client';
$newClientLabel = $isAgentFlow ? 'New Individual Agent' : 'New Individual Client';
$breadcrumbParentLabel = $isAgentFlow ? 'Agents' : 'Clients';
$recordNumberLabel = $isAgentFlow ? 'Agent Number' : 'Client Number';
$recordNumberPlaceholder = $isAgentFlow ? 'Auto-generated agent number' : 'CN - 000000';
$agentOccupationOptions = ['Insurance Agent', 'Senior Insurance Agent', 'Unit Manager'];
$agentTypeOptions = [
    'agent' => 'Agent',
    'sub_agent' => 'Sub agent',
];
$branchOptions = [
    'HEAD OFFICE',
    'ALABANG BRANCH',
    'MANILA BRANCH I',
    'MANILA BRANCH II',
    'WEST AVENUE BRANCH',
    'CUBAO BRANCH',
    'ANGELES BRANCH',
    'BATANGAS BRANCH',
    'BACOLOD BRANCH',
    'CABANATUAN BRANCH',
    'BUTUAN BRANCH',
    'CAGAYAN DE ORO BRANCH',
    'CEBU BRANCH',
    'CEBU REGIONAL OFFICE BRANCH',
    'DAGUPAN BRANCH',
    'DAVAO I BRANCH',
    'DAVAO II BRANCH',
    'GENSAN BRANCH',
    'ISABELA BRANCH',
    'LA UNION BRANCH',
    'LAOAG BRANCH',
    'LEGAZPI I BRANCH',
    'LEGAZPI II BRANCH',
    'MINDORO BRANCH',
    'NAGA BRANCH',
    'ORMOC BRANCH',
    'OZAMIZ BRANCH',
    'PAGADIAN BRANCH',
    'SAN FERNANDO, PAMPANGA BRANCH',
    'HEAD OFFICE BRANCH',
    'SMRO BRANCH',
    'TACLOBAN BRANCH',
    'TUGUEGARAO BRANCH',
    'VIGAN BRANCH',
    'ILOILO BRANCH',
];
$verificationUrl = 'kyc-verification.php?classification=' . urlencode($selectedClassification);
$reviewUrl = 'kyc-individual-review.php?classification=' . urlencode($selectedClassification);
$pageBackground = $isAgentFlow
    ? 'radial-gradient(circle at 15% 20%, rgba(243, 232, 255, 0.92) 0%, transparent 38%), radial-gradient(circle at 85% 85%, rgba(235, 224, 255, 0.5) 0%, transparent 34%), linear-gradient(160deg, #fbf7ff 0%, #f3eaff 46%, #ffffff 100%)'
    : 'radial-gradient(circle at 15% 20%, rgba(232, 240, 251, 0.9) 0%, transparent 38%), radial-gradient(circle at 85% 85%, rgba(220, 236, 255, 0.45) 0%, transparent 34%), linear-gradient(160deg, #f7fbff 0%, #eef6ff 46%, #ffffff 100%)';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sterling insurance Company Incorporated</title>
    <link rel='icon' type='image/png' href='../css/images/SterlingLogo.png'>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

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

        #draftsCard {
            position: fixed;
            top: 50%;
            left: 50%;
            right: auto;
            bottom: auto;
            width: 360px;
            max-width: calc(100vw - 28px);
            max-height: 48vh;
            overflow: hidden;
            z-index: 9999;
            display: block;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translate(-50%, calc(-50% - 8px)) scale(0.985);
            transform-origin: center;
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
            transform: translate(-50%, -50%) scale(1);
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
            background: linear-gradient(180deg, var(--wizard-accent-deep, #1f5ea9) 0%, var(--wizard-accent, #2f7fd6) 100%);
        }

        #kycForm > .card[data-wizard-step="3"]:not(#draftsCard)::before {
            background: linear-gradient(180deg, var(--wizard-accent-deep, #1f5ea9) 0%, var(--wizard-accent, #2f7fd6) 100%);
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
            background: var(--wizard-accent-soft, #e8f0fb);
            color: var(--wizard-accent-deep, #1f5ea9);
            font-size: 0.86rem;
        }

        #kycForm > .card[data-wizard-step="3"] .card-title i {
            background: var(--wizard-accent-soft, #e8f0fb);
            color: var(--wizard-accent-deep, #1f5ea9);
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

        #draftsCard.flow-reveal,
        #draftsCard.open.flow-reveal {
            animation: none !important;
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

                .id-upload-status {
            margin-top: 10px;
            font-size: 0.82rem;
            color: var(--gray-500);
            line-height: 1.5;
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
            --bs-gutter-x: 0.35rem;
            --bs-gutter-y: 0.3rem;
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

        .agent-warning {
            margin: 0 0 10px;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid #f2d27a;
            background: #fff8e1;
            color: #7a5600;
            font-size: 0.9rem;
            line-height: 1.45;
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
            top: 50%;
            left: 50%;
            right: auto;
            bottom: auto;
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

        /* Modal backdrop for drafts popup (all breakpoints) */
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

        @media (max-width: 900px) {
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
<body class="kyc-compact" style="--wizard-accent:<?php echo $isAgentFlow ? '#7c3aed' : '#2f7fd6'; ?>;--wizard-accent-soft:<?php echo $isAgentFlow ? '#f3e8ff' : '#e8f0fb'; ?>;--wizard-accent-deep:<?php echo $isAgentFlow ? '#5b21b6' : '#1f5ea9'; ?>;--page-background:<?php echo $pageBackground; ?>;">

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
                Dashboard &rsaquo; <?php echo htmlspecialchars($breadcrumbParentLabel); ?> &rsaquo; <span><?php echo htmlspecialchars($newClientLabel); ?></span>
            </div>
        </div>
        <div class="topbar-right">
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
                    <strong>Personal Details</strong>
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
                    <div class="client-type-display <?php echo $isAgentFlow ? 'agent' : 'individual'; ?>">
                        <i class="bi bi-person-fill"></i>
                        <span><?php echo htmlspecialchars($clientTypeLabel); ?></span>
                    </div>
                </div>
                <a href="<?php echo htmlspecialchars($verificationUrl); ?>" class="back-to-type-btn">
                    <i class="bi bi-arrow-left"></i>
                    Change Type
                </a>
                <input type="hidden" name="clientType" value="individual">
                <input type="hidden" name="clientClassification" value="<?php echo htmlspecialchars($selectedClassification); ?>">
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
                                <label for="clientNumber" class="form-label"><?php echo htmlspecialchars($recordNumberLabel); ?></label>
                                <input type="text" id="clientNumber" name="clientNumber" class="form-control" placeholder="<?php echo htmlspecialchars($recordNumberPlaceholder); ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Information Card -->
            <div class="card" data-wizard-step="2">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-person"></i> Personal Information</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="lastName" class="form-label">Last Name <span class="req">*</span></label>
                                <input type="text" id="lastName" name="lastName" class="form-control" placeholder="Last Name" required>
                                <div class="form-error">Last name is required</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="firstName" class="form-label">First Name <span class="req">*</span></label>
                                <input type="text" id="firstName" name="firstName" class="form-control" placeholder="First Name" required>
                                <div class="form-error">First name is required</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="middleName" class="form-label">Middle Name</label>
                                <input type="text" id="middleName" name="middleName" class="form-control" placeholder="Middle Name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="salutation" class="form-label">Salutations</label>
                                <input type="text" id="salutation" name="salutation" class="form-control" placeholder="e.g., Mr., Ms., Dr.">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="birthdate" class="form-label">Date of Birth <span class="req">*</span></label>
                                <input type="date" id="birthdate" name="birthdate" class="form-control" required>
                                <div class="form-error">Date of birth is required</div>
                            </div>
                        </div>
                        <div class="col-md-12">
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
                        <?php if (!$isAgentFlow): ?>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="apSlCode" class="form-label">AP SL Code</label>
                                <input type="text" id="apSlCode" name="apSlCode" class="form-control" placeholder="Enter AP SL Code">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="arSlCode" class="form-label">AR SL Code</label>
                                <input type="text" id="arSlCode" name="arSlCode" class="form-control" placeholder="Enter AR SL Code">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="clientSince" class="form-label">Client Since</label>
                                <input type="date" id="clientSince" name="clientSince" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tinNumber" class="form-label">TIN Number</label>
                                <input type="text" id="tinNumber" name="tinNumber" class="form-control" placeholder="TIN #">
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nationality" class="form-label">Nationality</label>
                                <input type="text" id="nationality" name="nationality" class="form-control" placeholder="e.g. Filipino">
                            </div>
                        </div>
                        <?php if ($isAgentFlow): ?>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="occupation" class="form-label">Occupation <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select id="occupation" name="occupation" class="form-select" required>
                                        <option value="">Select occupation...</option>
                                        <?php foreach ($agentOccupationOptions as $agentOccupationOption): ?>
                                            <option value="<?php echo htmlspecialchars($agentOccupationOption); ?>"><?php echo htmlspecialchars($agentOccupationOption); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-error">Occupation is required</div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!$isAgentFlow): ?>
            <!-- Spouse Information Card -->
            <div class="card" data-wizard-step="2">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-people"></i> Spouse Information</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="spouseName" class="form-label">Spouse Name</label>
                                <input type="text" id="spouseName" name="spouseName" class="form-control" placeholder="Spouse Full Name">
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
                                <input type="text" id="spouseOccupation" name="spouseOccupation" class="form-control" placeholder="Spouse Occupation">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($isAgentFlow): ?>
            <!-- Agent Identification Card -->
            <div class="card" data-wizard-step="2">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-person-badge"></i> Agent Identification</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="agentType" class="form-label">Agent Type <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select id="agentType" name="agentType" class="form-select" required>
                                        <?php foreach ($agentTypeOptions as $agentTypeValue => $agentTypeLabel): ?>
                                            <option value="<?php echo htmlspecialchars($agentTypeValue); ?>"<?php echo $agentTypeValue === 'agent' ? ' selected' : ''; ?>><?php echo htmlspecialchars($agentTypeLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-error">Agent type is required</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="agentBranch" class="form-label">Branch <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select id="agentBranch" name="agentBranch" class="form-select" required>
                                        <option value="">Select branch...</option>
                                        <?php foreach ($branchOptions as $branchOption): ?>
                                            <option value="<?php echo htmlspecialchars($branchOption); ?>"><?php echo htmlspecialchars($branchOption); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-error">Branch is required</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12" id="headAgentNameGroup" style="display:none;">
                            <div class="form-group">
                                <label for="headAgentName" class="form-label">Head Agent Name <span style="font-size:0.85rem;color:#999;">(Required for Sub agent)</span></label>
                                <div class="agent-warning">
                                    Warning: only agents from the selected branch will appear here. If you choose None of the above, the system will use that branch's manager instead.
                                </div>
                                <div class="select-wrap">
                                    <select id="headAgentName" name="headAgentName" class="form-select">
                                        <option value="">Select head agent...</option>
                                        <?php foreach ($headAgentOptions as $headAgentOption): ?>
                                            <option value="<?php echo htmlspecialchars($headAgentOption); ?>"><?php echo htmlspecialchars($headAgentOption); ?></option>
                                        <?php endforeach; ?>
                                        <option value="__none_of_the_above__">None of the above</option>
                                    </select>
                                </div>
                                <div class="form-error">Head Agent Name is required for Sub agent</div>
                                <div class="form-hint" style="margin-top:6px;">Choose None of the above only if you want the selected branch manager saved as the main agent.</div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="idNumber" class="form-label">Existing ID Number <span style="font-size:0.85rem;color:#999;">(Optional)</span></label>
                                <input type="text" id="idNumber" name="idNumber" class="form-control" placeholder="Enter existing ID number">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Government ID Verification Card -->
            <div class="card" data-wizard-step="2">
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
                                    <div class="form-error">Government ID type is required</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="form-group">
                                <label for="idNumber" class="form-label">ID Number <span class="req">*</span></label>
                                <input type="text" id="idNumber" name="idNumber" class="form-control" placeholder="Enter ID number" required>
                                <div class="form-error">ID number is required</div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-label">ID File Attachment <span class="req">*</span></label>
                                <div class="upload-zone" id="governmentIdUploadZone" onclick="document.getElementById('governmentIdInput').click()">
                                    <i class="bi bi-camera upload-icon"></i>
                                    <p><strong>Click to upload</strong> or drag and drop the ID file</p>
                                    <small>JPG, PNG, PDF (Max 5MB)</small>
                                </div>
                                <input type="file" id="governmentIdInput" accept=".jpg,.jpeg,.png" style="display:none;">
                                <div class="id-upload-hint">Upload your ID photo, then enter the ID number manually.</div>
                                <div class="file-list" id="governmentIdFileList" style="margin-top:12px;"></div>
                                <div class="id-upload-status" id="governmentIdStatus">No ID photo uploaded yet.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$isAgentFlow): ?>
            <!-- Occupation Card -->
            <div class="card" data-wizard-step="3">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-briefcase"></i> Occupation</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="occupation" class="form-label">Occupation <span class="req">*</span></label>
                                <input type="text" id="occupation" name="occupation" class="form-control" placeholder="e.g., Employee, Self-employed, Manager" required>
                                <div class="form-error">Occupation is required</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="employer" class="form-label">Employer</label>
                                <input type="text" id="employer" name="employer" class="form-control" placeholder="Company Name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="officePhone" class="form-label">Office Phone</label>
                                <input type="tel" id="officePhone" name="officePhone" class="form-control" placeholder="(02) 8XXX-XXXX">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$isAgentFlow): ?>
            <!-- Address Information Card -->
            <div class="card" data-wizard-step="3">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-geo-alt"></i> Address Information</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="businessRegion" class="form-label">Region</label>
                                <div class="select-wrap">
                                    <select id="businessRegion" name="businessRegion" class="form-select">
                                        <option value="">Select region...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="businessProvince" class="form-label">Province</label>
                                <div class="select-wrap">
                                    <select id="businessProvince" name="businessProvince" class="form-select">
                                        <option value="">Select province...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="businessCtm" class="form-label">City / Municipality</label>
                                <div class="select-wrap">
                                    <select id="businessCtm" name="businessCtm" class="form-select">
                                        <option value="">Select city/municipality...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="businessBarangay" class="form-label">Barangay</label>
                                <div class="select-wrap">
                                    <select id="businessBarangay" name="businessBarangay" class="form-select">
                                        <option value="">Select barangay...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="businessStreet" class="form-label">Street / Unit / Building</label>
                                <input type="text" id="businessStreet" name="businessStreet" class="form-control" placeholder="House/Unit No., Street, Building">
                                <input type="hidden" id="businessAddress" name="businessAddress">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Home Address Card -->
            <div class="card" data-wizard-step="3">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-house"></i> Home Address</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="homeRegion" class="form-label">Region <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select id="homeRegion" name="homeRegion" class="form-select" required>
                                        <option value="">Select region...</option>
                                    </select>
                                    <div class="form-error">Region is required</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="homeProvince" class="form-label">Province <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select id="homeProvince" name="homeProvince" class="form-select" required>
                                        <option value="">Select province...</option>
                                    </select>
                                    <div class="form-error">Province is required</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="homeCtm" class="form-label">City / Municipality <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select id="homeCtm" name="homeCtm" class="form-select" required>
                                        <option value="">Select city/municipality...</option>
                                    </select>
                                    <div class="form-error">City / municipality is required</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="homeBarangay" class="form-label">Barangay <span class="req">*</span></label>
                                <div class="select-wrap">
                                    <select id="homeBarangay" name="homeBarangay" class="form-select" required>
                                        <option value="">Select barangay...</option>
                                    </select>
                                    <div class="form-error">Barangay is required</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="homeStreet" class="form-label">Street / Unit / Building <span class="req">*</span></label>
                                <input type="text" id="homeStreet" name="homeStreet" class="form-control" placeholder="House/Unit No., Street, Building" required>
                                <input type="hidden" id="homeAddress" name="homeAddress">
                                <div class="form-error">Home street/unit is required</div>
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
                                <label for="mobile" class="form-label">Mobile Number <span class="req">*</span></label>
                                <input type="tel" id="mobile" name="mobile" class="form-control" placeholder="09XX-XXXX-XXXX" required>
                                <div class="form-error">Valid mobile number is required</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="telephone" class="form-label">Telephone</label>
                                <input type="tel" id="telephone" name="telephone" class="form-control" placeholder="(02) 8XXX-XXXX">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address <span class="req">*</span></label>
                                <input type="email" id="email" name="email" class="form-control" placeholder="user@example.com" required>
                                <div class="form-error">Valid email is required</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!$isAgentFlow): ?>
            <!-- Mailing Address Preference Card -->
            <div class="card" data-wizard-step="3">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-mailbox"></i> Mailing Address</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-label">Use Which Mailing Address? <span class="req">*</span></label>
                                <div style="display:flex;gap:20px;margin-top:8px;">
                                    <label style="display:flex;align-items:center;gap:8px;">
                                        <input type="radio" id="mailingBusiness" name="mailingAddressType" value="business" required> Business
                                    </label>
                                    <label style="display:flex;align-items:center;gap:8px;">
                                        <input type="radio" id="mailingHome" name="mailingAddressType" value="home" required> Home
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Card -->
            <div class="card" data-wizard-step="3">
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
            <?php endif; ?>

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
const currentReviewUrl = <?php echo json_encode($reviewUrl); ?>;
const currentVerificationUrl = <?php echo json_encode($verificationUrl); ?>;
const isAgentFlow = <?php echo $isAgentFlow ? 'true' : 'false'; ?>;

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

function generateRecordNumber(prefix, includeDate = true) {
    if (!includeDate) {
        const numericPart = String(Math.floor(Math.random() * 1000000)).padStart(6, '0');
        return `${prefix} - ${numericPart}`;
    }

    const now = new Date();
    const datePart = [
        now.getFullYear(),
        String(now.getMonth() + 1).padStart(2, '0'),
        String(now.getDate()).padStart(2, '0'),
        String(now.getHours()).padStart(2, '0'),
        String(now.getMinutes()).padStart(2, '0'),
        String(now.getSeconds()).padStart(2, '0')
    ].join('');
    const suffix = Array.from({ length: 6 }, () => Math.floor(Math.random() * 16).toString(16)).join('').toUpperCase();
    return `${prefix}-${datePart}-${suffix}`;
}

function ensureRecordNumber() {
    const field = document.getElementById('clientNumber');
    if (!field || field.value.trim()) return;
    field.value = isAgentFlow
        ? generateRecordNumber('AG', false)
        : `CN - ${String(Math.floor(Math.random() * 1000000)).padStart(6, '0')}`;
}

function syncAgentTypeFields() {
    if (!isAgentFlow) {
        return;
    }

    const agentTypeField = document.getElementById('agentType');
    const headAgentGroup = document.getElementById('headAgentNameGroup');
    const headAgentField = document.getElementById('headAgentName');

    const agentBranchField = document.getElementById('agentBranch');
    if (!agentTypeField || !headAgentGroup || !headAgentField || !agentBranchField) {
        return;
    }

    const isSubAgent = agentTypeField.value === 'sub_agent';
    headAgentGroup.style.display = isSubAgent ? '' : 'none';
    const agentBranchValue = String(agentBranchField?.value || '').trim();
    const requireHeadAgent = isSubAgent && agentBranchValue !== '';
    headAgentField.required = requireHeadAgent;
    headAgentField.dataset.required = requireHeadAgent ? 'true' : 'false';

    if (!isSubAgent) {
        headAgentField.value = '';
        headAgentField.classList.remove('is-invalid', 'is-valid');
    }
}

function revealFlowCards() {
    const cards = document.querySelectorAll('main.content .card');
    cards.forEach((card, idx) => {
        card.classList.add('flow-reveal');
        card.style.animationDelay = `${Math.min(idx * 45, 280)}ms`;
    });
}

const NONE_OF_THE_ABOVE_HEAD_AGENT_VALUE = '__none_of_the_above__';
let headAgentOptionsRequestToken = 0;

function getHeadAgentField() {
    return document.getElementById('headAgentName');
}

function getAgentBranchField() {
    return document.getElementById('agentBranch');
}

function setHeadAgentDropdownState(branchName, options, branchManagerName, preferredValue = '') {
    const headAgentField = getHeadAgentField();
    if (!headAgentField) {
        return;
    }

    const normalizedBranch = String(branchName || '').trim();
    const normalizedPreferred = String(preferredValue || headAgentField.value || '').trim();
    const allowedValues = new Set();

    headAgentField.innerHTML = '';

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = normalizedBranch === '' ? 'Select branch first...' : 'Select head agent...';
    headAgentField.appendChild(placeholder);

    (Array.isArray(options) ? options : []).forEach((optionLabel) => {
        const value = String(optionLabel || '').trim();
        if (value === '') {
            return;
        }

        allowedValues.add(value.toLowerCase());
        const option = document.createElement('option');
        option.value = value;
        option.textContent = value;
        headAgentField.appendChild(option);
    });

    const noneOption = document.createElement('option');
    noneOption.value = NONE_OF_THE_ABOVE_HEAD_AGENT_VALUE;
    noneOption.textContent = 'None of the above';
    headAgentField.appendChild(noneOption);

    headAgentField.disabled = normalizedBranch === '';

    let nextValue = normalizedPreferred;
    if (nextValue !== '' && nextValue.toLowerCase() === String(branchManagerName || '').trim().toLowerCase()) {
        nextValue = NONE_OF_THE_ABOVE_HEAD_AGENT_VALUE;
    }

    if (nextValue !== '' && nextValue !== NONE_OF_THE_ABOVE_HEAD_AGENT_VALUE && !allowedValues.has(nextValue.toLowerCase())) {
        nextValue = '';
    }

    headAgentField.value = nextValue;
    headAgentField.classList.remove('is-invalid', 'is-valid');
}

async function refreshHeadAgentOptions(preferredValue = '') {
    if (!isAgentFlow) {
        return;
    }

    const agentBranchField = getAgentBranchField();
    const headAgentField = getHeadAgentField();
    if (!agentBranchField || !headAgentField) {
        return;
    }

    const branch = String(agentBranchField.value || '').trim();
    const requestToken = ++headAgentOptionsRequestToken;

    if (branch === '') {
        setHeadAgentDropdownState('', [], '', preferredValue);
        return;
    }

    setHeadAgentDropdownState(branch, [], '', preferredValue);

    try {
        const response = await fetch(`../handlers/kyc.php?action=head_agent_options&branch=${encodeURIComponent(branch)}`, {
            method: 'GET',
            credentials: 'include'
        });
        const payload = await response.json();

        if (requestToken !== headAgentOptionsRequestToken) {
            return;
        }

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to load head agent options.');
        }

        setHeadAgentDropdownState(
            branch,
            Array.isArray(payload.options) ? payload.options : [],
            payload.branch_manager_name || '',
            preferredValue
        );
    } catch (error) {
        if (requestToken !== headAgentOptionsRequestToken) {
            return;
        }

        setHeadAgentDropdownState(branch, [], '', preferredValue);
        console.error(error);
    }
}

// ── Form Validation ────────────────────────────────────────
function validateField(id) {
    const el = document.getElementById(id);
    if (!el) return true;
    
    // Skip validation if field is hidden
    if (el.offsetParent === null) return true;
    
    const value = el.value.trim();
    const isRequired = el.required || el.dataset.required === 'true';

    if (!isRequired && value === '') {
        el.classList.remove('is-invalid');
        el.classList.remove('is-valid');
        return true;
    }

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

    const radiosArr = Array.from(radios);
    const checked = radiosArr.some(radio => radio.checked);

    radiosArr.forEach(radio => {
        const label = radio.closest('label');
        if (label) label.classList.toggle('is-invalid', !checked);
        radio.classList.toggle('is-invalid', !checked);
    });

    return checked;
}

function validateAllRequired() {
    syncAgentTypeFields();

    const requiredFields = isAgentFlow
        ? ['lastName', 'firstName', 'birthdate', 'occupation', 'agentType', 'agentBranch', 'mobile', 'email', 'homeRegion', 'homeProvince', 'homeCtm', 'homeBarangay', 'homeStreet']
        : ['lastName', 'firstName', 'birthdate', 'occupation', 'mobile', 'email', 'homeRegion', 'homeProvince', 'homeCtm', 'homeBarangay', 'homeStreet', 'governmentIdType', 'idNumber'];
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
    
    // Validate mailingAddressType radio
    if (!validateRadioGroup('mailingAddressType')) allValid = false;
    if (!validateGovernmentIdSection()) allValid = false;
    if (isAgentFlow && !validateAgentAssignmentSection()) allValid = false;
    if (!allValid && failedFields.length > 0) {
        console.log('Failed fields:', failedFields);
    }
    
    return allValid;
}

function validateAgentAssignmentSection() {
    if (!isAgentFlow) {
        return true;
    }

    syncAgentTypeFields();

    const agentTypeOk = validateField('agentType');
    const agentBranchOk = validateField('agentBranch');
    const headAgentField = document.getElementById('headAgentName');
    const needsHeadAgent = Boolean(headAgentField && headAgentField.required);
    const headAgentOk = !needsHeadAgent || validateField('headAgentName');

    return agentTypeOk && agentBranchOk && headAgentOk;
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

document.querySelectorAll('input[type="radio"]').forEach((radio) => {
    radio.addEventListener('change', function () {
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

async function initAddressChain(regionId, provinceId, cityId, barangayId) {
    const regionEl = document.getElementById(regionId);
    const provinceEl = document.getElementById(provinceId);
    const cityEl = document.getElementById(cityId);
    const barangayEl = document.getElementById(barangayId);
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

function syncComposedAddressFields() {
    const businessStreet = document.getElementById('businessStreet')?.value || '';
    const businessBarangay = document.getElementById('businessBarangay')?.value || '';
    const businessCity = document.getElementById('businessCtm')?.value || '';
    const businessProvince = document.getElementById('businessProvince')?.value || '';
    const businessRegion = document.getElementById('businessRegion')?.value || '';
    const businessAddressField = document.getElementById('businessAddress');
    if (businessAddressField) {
        businessAddressField.value = buildAddress(businessStreet, businessBarangay, businessCity, businessProvince, businessRegion);
    }

    const homeStreet = document.getElementById('homeStreet')?.value || '';
    const homeBarangay = document.getElementById('homeBarangay')?.value || '';
    const homeCity = document.getElementById('homeCtm')?.value || '';
    const homeProvince = document.getElementById('homeProvince')?.value || '';
    const homeRegion = document.getElementById('homeRegion')?.value || '';
    const homeAddressField = document.getElementById('homeAddress');
    if (homeAddressField) {
        homeAddressField.value = buildAddress(homeStreet, homeBarangay, homeCity, homeProvince, homeRegion);
    }
}

initAddressChain('businessRegion', 'businessProvince', 'businessCtm', 'businessBarangay');
initAddressChain('homeRegion', 'homeProvince', 'homeCtm', 'homeBarangay');

function restoreFormData() {
    const savedData = sessionStorage.getItem('kycFormData');
    const savedAddressData = sessionStorage.getItem('individualAddressData');
    
    if (!savedData) return;
    
    try {
        const formData = JSON.parse(savedData);
        const form = document.getElementById('kycForm');
        if (!form) return;
        
        // Fields to skip in the general restore (we'll handle address fields separately)
        const addressFields = ['businessRegion', 'businessProvince', 'businessCtm', 'businessBarangay', 'businessStreet', 'businessAddress', 'homeRegion', 'homeProvince', 'homeCtm', 'homeBarangay', 'homeStreet', 'homeAddress'];
        
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

        syncAgentTypeFields();
        
        // Restore address data after API populates options
        // This requires waiting for PSGC API calls in the correct cascade order
        if (savedAddressData) {
            try {
                const addressData = JSON.parse(savedAddressData);
                
                // Restore BUSINESS address in cascade order with delays for API calls
                setTimeout(() => {
                    const businessRegionEl = document.getElementById('businessRegion');
                    if (businessRegionEl && addressData.businessRegion) {
                        businessRegionEl.value = addressData.businessRegion;
                        businessRegionEl.dispatchEvent(new Event('change'));
                    }
                    
                    // Wait for provinces to load, then restore province
                    setTimeout(() => {
                        const businessProvinceEl = document.getElementById('businessProvince');
                        if (businessProvinceEl && addressData.businessProvince) {
                            businessProvinceEl.value = addressData.businessProvince;
                            businessProvinceEl.dispatchEvent(new Event('change'));
                        }
                        
                        // Wait for cities to load, then restore city
                        setTimeout(() => {
                            const businessCityEl = document.getElementById('businessCtm');
                            if (businessCityEl && addressData.businessCity) {
                                businessCityEl.value = addressData.businessCity;
                                businessCityEl.dispatchEvent(new Event('change'));
                            }
                            
                            // Wait for barangays to load, then restore barangay
                            setTimeout(() => {
                                const businessBarangayEl = document.getElementById('businessBarangay');
                                if (businessBarangayEl && addressData.businessBarangay) {
                                    businessBarangayEl.value = addressData.businessBarangay;
                                }
                                
                                const businessStreetEl = document.getElementById('businessStreet');
                                if (businessStreetEl && addressData.businessStreet) {
                                    businessStreetEl.value = addressData.businessStreet;
                                }
                                
                                // Now restore HOME address in cascade order
                                restoreHomeAddress(addressData);
                                
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

function restoreHomeAddress(addressData) {
    setTimeout(() => {
        const homeRegionEl = document.getElementById('homeRegion');
        if (homeRegionEl && addressData.homeRegion) {
            homeRegionEl.value = addressData.homeRegion;
            homeRegionEl.dispatchEvent(new Event('change'));
        }
        
        // Wait for provinces to load, then restore province
        setTimeout(() => {
            const homeProvinceEl = document.getElementById('homeProvince');
            if (homeProvinceEl && addressData.homeProvince) {
                homeProvinceEl.value = addressData.homeProvince;
                homeProvinceEl.dispatchEvent(new Event('change'));
            }
            
            // Wait for cities to load, then restore city
            setTimeout(() => {
                const homeCityEl = document.getElementById('homeCtm');
                if (homeCityEl && addressData.homeCity) {
                    homeCityEl.value = addressData.homeCity;
                    homeCityEl.dispatchEvent(new Event('change'));
                }
                
                // Wait for barangays to load, then restore barangay
                setTimeout(() => {
                    const homeBarangayEl = document.getElementById('homeBarangay');
                    if (homeBarangayEl && addressData.homeBarangay) {
                        homeBarangayEl.value = addressData.homeBarangay;
                    }
                    
                    const homeStreetEl = document.getElementById('homeStreet');
                    if (homeStreetEl && addressData.homeStreet) {
                        homeStreetEl.value = addressData.homeStreet;
                    }
                    
                    syncComposedAddressFields();
                }, 500);
            }, 500);
        }, 500);
    }, 500);
}

// Restore form data on page load
const KYC_NAVIGATION_TYPE = (performance.getEntriesByType('navigation')[0]?.type) || (performance.navigation && performance.navigation.type === 1 ? 'reload' : 'navigate');

async function clearFormStateOnRefresh() {
    const regularUploads = getStoredUploads();
    const governmentIdUploads = getStoredGovernmentIdUploads();

    sessionStorage.removeItem('kycFormData');
    sessionStorage.removeItem('individualAddressData');
    sessionStorage.removeItem('kycUploadedFiles');
    sessionStorage.removeItem('kycGovernmentIdFiles');

    await Promise.all([
        ...((regularUploads || []).map(upload => deleteTempUpload(upload?.temp_path))),
        ...((governmentIdUploads || []).map(upload => deleteTempUpload(upload?.temp_path)))
    ]);
}

if (KYC_NAVIGATION_TYPE === 'reload') {
    void clearFormStateOnRefresh();
}

document.addEventListener('DOMContentLoaded', restoreFormData);

function validateGovernmentIdSection() {
    if (isAgentFlow) {
        return true;
    }

    const typeOk = validateField('governmentIdType');
    const numberOk = validateField('idNumber');
    const uploadsOk = getStoredGovernmentIdUploads().length > 0;
    const zone = document.getElementById('governmentIdUploadZone');
    const status = document.getElementById('governmentIdStatus');

    if (zone) zone.classList.toggle('is-invalid', !uploadsOk);

    return typeOk && numberOk && uploadsOk;
}

function setGovernmentIdStatus(message, isError = false) {
    const status = document.getElementById('governmentIdStatus');
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

        const name = file.original_name || file.file_name || 'ID file';
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
            renderGovernmentIdUploads();
            if (removed?.temp_path) {
                await deleteTempUpload(removed.temp_path);
            }
            setGovernmentIdStatus('No ID photo uploaded yet.');
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

function sanitizeGovernmentIdFiles(fileList) {
    const files = Array.from(fileList || []).slice(0, 1);
    if (!files.length) {
        return [];
    }

    const file = files[0];
    const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    const maxBytes = 5 * 1024 * 1024;

    if (!allowedTypes.includes(file.type)) {
        showToast('error', 'Invalid File', 'Only JPG or PNG files are allowed.');
        setGovernmentIdStatus('Please upload a JPG or PNG ID photo.', true);
        return [];
    }

    if (Number(file.size || 0) > maxBytes) {
        showToast('error', 'File Too Large', 'ID photo must be 5MB or below.');
        setGovernmentIdStatus('ID photo must be 5MB or below.', true);
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
        setGovernmentIdStatus('ID photo uploaded. Enter the ID number manually.');
    } finally {
        if (zone) zone.classList.remove('is-uploading');
    }
}


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
            setGovernmentIdStatus('Unable to upload the ID photo. Please try again.', true);
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
            setGovernmentIdStatus('Unable to upload the ID photo. Please try again.', true);
        }
    });
}

if (governmentIdTypeSelect) {
    governmentIdTypeSelect.addEventListener('change', () => {
        validateGovernmentIdSection();
    });
}

document.addEventListener('DOMContentLoaded', renderGovernmentIdUploads);

let kycMasonryRaf = 0;
let kycMasonryObserver = null;

function getKycMasonryItems(form) {
    return Array.from(form.children).filter((el) => {
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
const WIZARD_MAX_STEP = 3;
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
        3: document.getElementById('step-3')
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
    const agentTypeField = document.getElementById('agentType');
    const agentBranchField = document.getElementById('agentBranch');
    if (agentTypeField) {
        agentTypeField.addEventListener('change', async () => {
            syncAgentTypeFields();
            await refreshHeadAgentOptions();
        });
    }

    if (agentBranchField) {
        agentBranchField.addEventListener('change', async () => {
            syncAgentTypeFields();
            await refreshHeadAgentOptions();
        });
    }

    syncAgentTypeFields();
    refreshHeadAgentOptions();

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
        stepEl.addEventListener('click', () => {
            if (step === 4) {
                if (currentWizardStep !== WIZARD_MAX_STEP) {
                    showToast('info', 'Step Locked', 'Complete Step 3 before opening the review.');
                    return;
                }
                proceedToReview();
                return;
            }
            goToWizardStep(step);
        });
        stepEl.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                if (step === 4) {
                    if (currentWizardStep !== WIZARD_MAX_STEP) {
                        showToast('info', 'Step Locked', 'Complete Step 3 before opening the review.');
                        return;
                    }
                    proceedToReview();
                    return;
                }
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

    syncComposedAddressFields();
    syncAgentTypeFields();

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
    const getValue = (fieldId) => document.getElementById(fieldId)?.value || '';
    const addressData = {
        businessRegion: getValue('businessRegion'),
        businessProvince: getValue('businessProvince'),
        businessCity: getValue('businessCtm'),
        businessBarangay: getValue('businessBarangay'),
        businessStreet: getValue('businessStreet'),
        businessAddress: getValue('businessAddress'),
        homeRegion: getValue('homeRegion'),
        homeProvince: getValue('homeProvince'),
        homeCity: getValue('homeCtm'),
        homeBarangay: getValue('homeBarangay'),
        homeStreet: getValue('homeStreet'),
        homeAddress: getValue('homeAddress')
    };
    sessionStorage.setItem('individualAddressData', JSON.stringify(addressData));
    sessionStorage.setItem('kycGovernmentIdFiles', JSON.stringify(getStoredGovernmentIdUploads()));
    
    // Navigate to review page
    const reviewUrl = new URL(currentReviewUrl, window.location.href);
    reviewUrl.searchParams.set('classification', isAgentFlow ? 'agent' : 'client');
    window.location.href = `${reviewUrl.pathname}${reviewUrl.search}`;
}

function submitForm() {
    syncComposedAddressFields();
    syncAgentTypeFields();

    if (!validateAllRequired()) {
        showToast('error', 'Validation Failed', 'Please fill in all required fields marked with *');
        return;
    }
    
    // Collect form data
    const formData = new FormData();
    formData.append('action', 'submit_kyc');
    
    // Add all form fields
    const form = document.getElementById('kycForm');
    const elements = form.querySelectorAll('input, select, textarea');
    elements.forEach(el => {
        if (el.name && el.value) {
            formData.append(el.name, el.value);
        }
    });

    const uploadedFiles = isAgentFlow ? [] : (getStoredUploads ? getStoredUploads() : []);
    formData.append('uploadedFiles', JSON.stringify(uploadedFiles || []));
    formData.append('uploadedIdFiles', JSON.stringify(isAgentFlow ? [] : (getStoredGovernmentIdUploads() || [])));
    
    // Submit to handler
    fetch('../handlers/kyc.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const entityLabel = isAgentFlow ? 'Agent' : 'Client';
            if (data.reference_code && !document.getElementById('refCode').value) {
                document.getElementById('refCode').value = data.reference_code;
                document.getElementById('refCode').readOnly = true;
            }
            showToast('success', `${entityLabel} Saved!`, data.reference_code ? `Reference Code: ${data.reference_code}` : `${entityLabel} registered successfully.`);
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

async function clearForm() {
    const clearBtn = document.getElementById('clearBtn');
    setButtonBusy(clearBtn, true, 'Clearing...');

    document.getElementById('kycForm').querySelectorAll('input, select').forEach(el => {
        if (el.readOnly) return;
        el.value = '';
        el.classList.remove('is-invalid','is-valid');
    });

    // Clear any temp-uploaded documents
    const uploads = (typeof getStoredUploads === 'function') ? getStoredUploads() : [];
    await Promise.all((uploads || []).map(u => deleteTempUpload(u?.temp_path)));
    sessionStorage.removeItem('kycUploadedFiles');
    const idUploads = getStoredGovernmentIdUploads();
    await Promise.all((idUploads || []).map(u => deleteTempUpload(u?.temp_path)));
    sessionStorage.removeItem('kycGovernmentIdFiles');
    currentGovernmentIdFile = null;
    document.getElementById('fileList').innerHTML = '';
    renderGovernmentIdUploads();
    showToast('info', 'Form Cleared', 'All fields have been reset.');
    setButtonBusy(clearBtn, false);
}

function goBack() {
    window.location.href = currentVerificationUrl;
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
document.addEventListener('DOMContentLoaded', ensureRecordNumber);

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



