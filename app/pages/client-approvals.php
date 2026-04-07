<?php
require_once '../config/session.php';
requireLogin();

$currentUserRole = strtolower(trim($_SESSION['role'] ?? ''));
$currentUserDepartment = strtoupper(trim($_SESSION['department'] ?? ''));
$currentUserBranch = strtoupper(trim($_SESSION['branch'] ?? ''));
$isHeadOfficeUser = $currentUserRole === 'admin'
    || $currentUserDepartment === 'HEAD OFFICE'
    || in_array($currentUserBranch, ['HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH'], true);

if (!$isHeadOfficeUser) {
    http_response_code(403);
}
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
    <link rel="stylesheet" href="../../public/css/clients.css">

    <style>
        .approval-status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .approval-status-pending {
            background: #fff7e0;
            color: #8d6400;
        }

        .approval-status-approved {
            background: #e7f8ee;
            color: #0d6b37;
        }

        .approval-status-declined {
            background: #fde9e9;
            color: #a61d24;
        }

        .approval-status-resubmit {
            background: #e8f1ff;
            color: #245ea8;
        }

        .action-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .action-stack .action-icon {
            width: auto;
            height: auto;
            border-radius: 8px;
            padding: 7px 9px;
            font-size: 0.78rem;
            font-weight: 600;
            display: inline-flex;
            gap: 6px;
            align-items: center;
            justify-content: center;
        }

        .action-icon.action-approve {
            background: #eaf9f0;
            color: #136c39;
            border-color: #b7e6ca;
        }

        .action-icon.action-decline {
            background: #fff0f0;
            color: #a61d24;
            border-color: #f0c3c6;
        }

        .action-icon.action-resubmit {
            background: #edf4ff;
            color: #245ea8;
            border-color: #c6daf8;
        }

        .notes-cell {
            max-width: 240px;
            white-space: normal;
            color: #4b5563;
            font-size: 0.82rem;
            line-height: 1.35;
        }

        .notes-cell .notes-text {
            margin-top: 4px;
            white-space: normal;
            word-break: break-word;
        }

        .officer-update-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 999px;
            padding: 3px 9px;
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            background: #fff4d8;
            color: #8d6400;
            border: 1px solid #f1d187;
        }

        .officer-update-meta {
            margin-top: 4px;
            font-size: 0.72rem;
            color: #6b7280;
        }

        .table-wrapper {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-gutter: stable both-edges;
            flex: 1;
            min-height: 0;
        }

        .clients-table {
            min-width: 1260px;
        }

        .clients-table th.col-ref,
        .clients-table td.col-ref {
            width: 22%;
            min-width: 260px;
        }

        .ref-with-name {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .ref-name {
            font-weight: 600;
            color: #1f2937;
        }

        .status-stack {
            display: inline-flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }

        .resubmitted-now-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-radius: 999px;
            padding: 2px 8px;
            font-size: 0.64rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            background: #ffe9d6;
            color: #8a3800;
            border: 1px solid #f2c49f;
            white-space: nowrap;
        }

        #approvalsTableBody tr.approval-row {
            cursor: pointer;
        }

        #approvalsTableBody tr.approval-row td {
            transition: background-color 0.18s ease;
        }

        #approvalsTableBody tr.approval-row:hover td {
            background-color: #f5fbf8;
        }

        #approvalsTableBody tr.approval-row.is-selected td {
            background-color: #eaf5ef;
        }

        .application-details-panel {
            position: fixed;
            inset: 0;
            z-index: 1200;
            display: none;
        }

        .application-details-panel.open {
            display: block;
        }

        .application-details-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(10, 24, 18, 0.56);
            backdrop-filter: blur(2px);
        }

        .application-details-dialog {
            position: relative;
            width: min(1180px, calc(100vw - 26px));
            max-height: calc(100vh - 32px);
            margin: 16px auto;
            background: #fbfefd;
            border: 1px solid #c9ded0;
            border-radius: 18px;
            box-shadow: 0 30px 64px rgba(15, 23, 42, 0.3);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .application-details-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding: 12px 14px;
            border-bottom: 1px solid #d4e5d9;
            background: linear-gradient(160deg, #fbfefd 0%, #f1f9f4 100%);
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .application-details-header h2 {
            margin: 0;
            font-size: 1rem;
            color: #143927;
            letter-spacing: -0.01em;
        }

        #applicationDetailsClearBtn {
            min-height: 30px;
            padding: 5px 12px;
            border-radius: 999px;
            border: 1px solid #c9ddd0;
            background: #ffffff;
            color: #24523a;
        }

        #applicationDetailsClearBtn:hover {
            background: #eef8f2;
            border-color: #b7d4c3;
        }

        .application-details-empty {
            border: 1px dashed #c8d7ce;
            border-radius: 12px;
            padding: 18px;
            background: #f8fbf9;
            color: #4b5563;
            font-size: 0.88rem;
            margin: 12px;
        }

        .application-details-scroll {
            overflow-y: auto;
            min-height: 0;
            flex: 1;
            padding: 10px;
            background: linear-gradient(180deg, #f8fcf9 0%, #f3f9f5 100%);
        }

        .application-modal-subtitle {
            font-size: 0.74rem;
            color: #4f6a5c;
            margin: 2px 0 0;
        }

        .application-modal-body {
            column-count: 2;
            column-gap: 10px;
        }

        @media (max-width: 900px) {
            .application-details-dialog {
                width: calc(100vw - 14px);
                max-height: calc(100vh - 14px);
                margin: 7px;
            }
        }

        .detail-section {
            border: 1px solid #d4e5da;
            border-radius: 11px;
            background: #ffffff;
            overflow: hidden;
            box-shadow: 0 10px 20px -22px rgba(21, 56, 38, 0.65);
            display: inline-block;
            width: 100%;
            margin: 0 0 10px;
            break-inside: avoid;
        }

        .application-modal-body .detail-section:nth-child(1) .detail-section-header {
            background: linear-gradient(180deg, #ecf7ff 0%, #e2f1ff 100%);
            border-bottom-color: #d1e3f5;
        }

        .application-modal-body .detail-section:nth-child(2) .detail-section-header {
            background: linear-gradient(180deg, #effaf2 0%, #e4f2e9 100%);
            border-bottom-color: #d2e7d9;
        }

        .application-modal-body .detail-section:nth-child(3) .detail-section-header {
            background: linear-gradient(180deg, #fff8ea 0%, #fef1d6 100%);
            border-bottom-color: #eedbb4;
        }

        .application-modal-body .detail-section:nth-child(4) .detail-section-header {
            background: linear-gradient(180deg, #f5f3ff 0%, #ece8ff 100%);
            border-bottom-color: #ddd6fe;
        }

        .application-modal-body .detail-section:nth-child(5) .detail-section-header {
            background: linear-gradient(180deg, #eaf8ff 0%, #ddf1ff 100%);
            border-bottom-color: #cde6f8;
        }

        .detail-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 11px;
            background: linear-gradient(180deg, #eff8f2 0%, #e8f2eb 100%);
            border-bottom: 1px solid #d4e5da;
        }

        .detail-section-header h3 {
            margin: 0;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #23503b;
        }

        .detail-grid {
            display: flex;
            flex-direction: column;
            gap: 0;
            padding: 4px 10px 8px;
        }

        .detail-row {
            display: grid;
            grid-template-columns: minmax(160px, 220px) minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            padding: 7px 4px;
            border-bottom: 1px solid #e7efe9;
        }

        .detail-row:nth-child(even) {
            background: #f9fdfb;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-row .label {
            display: inline-flex;
            align-items: center;
            font-size: 0.63rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #5f7082;
            font-weight: 600;
            line-height: 1.3;
        }

        .detail-value {
            color: #1f2937;
            font-size: 0.75rem;
            line-height: 1.35;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .detail-row-fallback {
            grid-template-columns: 1fr;
            background: #f8fbf9;
            border-bottom: none;
            border-radius: 8px;
            border: 1px dashed #d5e4db;
            padding: 10px;
        }

        .details-decision-notes {
            padding: 12px;
            display: grid;
            gap: 8px;
            border-top: 1px solid #d7e8dc;
        }

        .details-decision-notes label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            font-weight: 600;
        }

        .details-decision-notes small {
            color: #64748b;
            font-size: 0.74rem;
        }

        .document-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 9px;
            padding: 9px;
        }

        .document-card {
            border: 1px solid #d3e1ea;
            border-radius: 10px;
            background: #fff;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: transform 0.16s ease, box-shadow 0.16s ease;
        }

        .document-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px -18px rgba(17, 24, 39, 0.55);
        }

        .document-preview {
            background: #f6f8fb;
            min-height: 130px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #d9e3eb;
            overflow: hidden;
        }

        .document-preview img {
            width: 100%;
            height: 100%;
            max-height: 240px;
            object-fit: cover;
        }

        .document-preview iframe {
            width: 100%;
            min-height: 150px;
            border: 0;
            background: #fff;
        }

        .document-preview .doc-fallback {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: #64748b;
            text-align: center;
            padding: 14px;
        }

        .document-preview .doc-fallback i {
            font-size: 1.8rem;
        }

        .document-meta {
            padding: 8px 10px;
            display: grid;
            gap: 4px;
        }

        .document-meta .doc-name {
            font-size: 0.76rem;
            color: #111827;
            font-weight: 600;
            word-break: break-word;
        }

        .document-meta .doc-info {
            font-size: 0.7rem;
            color: #6b7280;
        }

        .document-meta .doc-link {
            font-size: 0.72rem;
            font-weight: 600;
            color: #0b5b87;
            text-decoration: none;
        }

        .document-meta .doc-link:hover {
            text-decoration: underline;
        }

        .application-modal-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            border-top: 1px solid #d7e8dc;
            padding-top: 12px;
        }

        .application-modal-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .application-modal-actions .action-icon[disabled] {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
            filter: grayscale(0.2);
        }

        .denied-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: linear-gradient(145deg, #f5faf7 0%, #eef4ff 100%);
            padding: 24px;
        }

        .denied-card {
            width: min(520px, 100%);
            background: #fff;
            border-radius: 16px;
            border: 1px solid #d7e0ec;
            box-shadow: 0 18px 38px rgba(17, 24, 39, 0.12);
            padding: 24px;
            text-align: center;
        }

        .denied-card i {
            font-size: 2.2rem;
            color: #a61d24;
            margin-bottom: 8px;
        }

        .denied-card h1 {
            font-size: 1.3rem;
            margin: 0;
            color: #111827;
        }

        .denied-card p {
            margin: 10px 0 0;
            color: #4b5563;
        }

        .denied-card a {
            display: inline-flex;
            margin-top: 16px;
            padding: 9px 14px;
            border-radius: 10px;
            text-decoration: none;
            background: #0b5b87;
            color: #fff;
            font-weight: 600;
        }

        .approvals-page .main {
            position: relative;
            isolation: isolate;
        }

        .approvals-page .main::before {
            content: '';
            position: absolute;
            top: -140px;
            right: -180px;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(151, 214, 173, 0.34) 0%, rgba(151, 214, 173, 0) 72%);
            z-index: -1;
            pointer-events: none;
        }

        .approvals-topbar {
            margin-bottom: 8px;
            border: 1px solid #d9e8dd;
            border-radius: 12px;
            background: linear-gradient(130deg, rgba(255, 255, 255, 0.95) 0%, rgba(244, 251, 246, 0.98) 60%, rgba(235, 245, 255, 0.9) 100%);
            box-shadow: 0 12px 24px -26px rgba(16, 54, 33, 0.45);
            padding: 12px 14px;
        }

        .approvals-topbar .topbar-left h1 {
            margin-bottom: 4px;
            letter-spacing: -0.01em;
        }

        .controls-container {
            border: none;
            border-radius: 0;
            padding: 0;
            margin-bottom: 8px;
            background: transparent;
            box-shadow: none;
        }

        .controls-container .controls-left {
            gap: 6px;
            align-items: center;
        }

        .controls-container .filter-group {
            min-width: 135px;
            flex: 0 1 150px;
        }

        .controls-container .filter-select,
        .controls-container .search-input {
            width: 100%;
            min-height: 32px;
            border-radius: 999px;
            border-color: #ccded2;
            background: rgba(253, 255, 254, 0.96);
            font-size: 0.76rem;
        }

        .controls-container .search-input {
            padding: 6px 12px 6px 28px;
        }

        .controls-container .filter-select {
            padding: 5px 28px 5px 12px;
        }

        .controls-container .search-box i {
            left: 9px;
            font-size: 0.78rem;
        }

        .controls-container .search-box {
            width: min(240px, 100%);
            flex: 0 1 240px;
        }

        .approvals-page .table-wrapper {
            border: 1px solid #d7e8dd;
            border-radius: 14px 14px 0 0;
            background: #ffffff;
            box-shadow: 0 16px 28px -28px rgba(23, 62, 42, 0.7);
        }

        .approvals-page .clients-table thead {
            background: linear-gradient(180deg, #2f7d4e 0%, #266741 100%);
            border-top: 1px solid #2a7549;
        }

        .approvals-page .clients-table th {
            font-size: 0.67rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 4px 7px;
        }

        .approvals-page .clients-table td {
            padding-top: 5px;
            padding-bottom: 5px;
            padding-left: 7px;
            padding-right: 7px;
            font-size: 0.76rem;
        }

        .approvals-page .table-footer {
            border: 1px solid #d7e8dd;
            border-top: none;
            border-radius: 0 0 14px 14px;
            background: linear-gradient(180deg, #f6fcf8 0%, #edf7f1 100%);
            padding: 12px 14px;
        }

        .approvals-page .action-stack {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            width: 100%;
            gap: 0;
        }

        .approvals-page .action-stack .action-icon {
            padding: 5px 8px;
            font-size: 0.69rem;
            border-radius: 8px;
        }

        .approvals-page .action-stack .action-icon i {
            font-size: 0.78rem;
        }

        .row-action-toggle {
            min-height: 26px;
            padding: 4px 9px;
            border-radius: 999px;
            border: 1px solid #c8ddd0;
            background: #f7fcf8;
            color: #174a31;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: all 0.18s ease;
        }

        .row-action-toggle:hover {
            border-color: #a9ccb8;
            background: #eff8f2;
        }

        .row-action-toggle i {
            font-size: 0.72rem;
        }

        .row-action-menu {
            position: absolute;
            right: 0;
            top: calc(100% + 6px);
            min-width: 150px;
            border: 1px solid #cfe1d6;
            border-radius: 10px;
            background: #ffffff;
            box-shadow: 0 12px 22px rgba(17, 24, 39, 0.13);
            padding: 5px;
            display: none;
            z-index: 20;
        }

        .action-stack.is-open .row-action-menu {
            display: grid;
            gap: 4px;
        }

        .row-action-item {
            width: 100%;
            border: 1px solid transparent;
            justify-content: flex-start;
            padding: 5px 8px;
            font-size: 0.7rem;
            font-weight: 600;
            background: #f8fcfa;
        }

        .approvals-page .application-details-dialog {
            border-radius: 16px;
            border-color: #cadfd2;
            box-shadow: 0 26px 52px rgba(15, 23, 42, 0.26);
        }

        .approvals-page .application-details-header {
            padding: 16px;
        }

        @media (max-width: 980px) {
            .approvals-topbar {
                padding: 10px 12px;
            }

            .controls-container .search-box,
            .controls-container .filter-group {
                width: 100%;
                flex: 1 1 100%;
            }
        }

        @media (max-width: 640px) {
            .approvals-page .table-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .application-modal-body {
                column-count: 1;
            }

            .detail-row {
                grid-template-columns: 1fr;
                gap: 4px;
                padding: 8px 2px;
            }

            .approvals-page .application-details-header {
                padding: 12px;
            }

            .approvals-page .application-modal-subtitle {
                font-size: 0.74rem;
            }
        }
    </style>
</head>
<body class="clients-page approvals-page">
<?php if (!$isHeadOfficeUser): ?>
    <main class="denied-shell">
        <section class="denied-card">
            <i class="bi bi-shield-lock"></i>
            <h1>Access Restricted</h1>
            <p>Client approvals are visible only to Head Office and equivalent accounts.</p>
            <a href="dashboard.php">Return to Dashboard</a>
        </section>
    </main>
<?php else: ?>

<?php
$activePage = 'client-approvals';
include '../includes/sidebar.php';
?>

<div class="main">
    <header class="topbar approvals-topbar">
        <div class="topbar-left">
            <h1>Client Approvals</h1>
            <div class="breadcrumb-trail">
                <i class="bi bi-house" style="font-size:.65rem;"></i>
                Dashboard &rsaquo; <span>Client Approvals</span>
            </div>
        </div>
    </header>

    <main class="content">
        <div class="controls-container">
            <div class="controls-left">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" placeholder="Search approvals..." class="search-input">
                </div>

                <div class="filter-group">
                    <select id="filterStatus" class="filter-select">
                        <option value="" selected>All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="declined">Declined</option>
                        <option value="resubmit">Resubmit</option>
                    </select>
                </div>

                <div class="filter-group">
                    <select id="filterClassification" class="filter-select">
                        <option value="">All Classifications</option>
                        <option value="client">Client</option>
                        <option value="agent">Agent</option>
                    </select>
                </div>

                <div class="filter-group">
                    <select id="filterType" class="filter-select">
                        <option value="">All Types</option>
                        <option value="individual">Individual</option>
                        <option value="corporate">Corporate</option>
                        <option value="obligee">Obligee</option>
                    </select>
                </div>

                <div class="filter-group">
                    <select id="filterBranch" class="filter-select">
                        <option value="">All Branches</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="clients-table">
                <thead>
                    <tr>
                        <th class="col-ref">Ref Code</th>
                        <th class="col-name">Display Name</th>
                        <th class="col-type">Class</th>
                        <th class="col-type">Type</th>
                        <th class="col-contact">Contact</th>
                        <th class="col-email">Email</th>
                        <th class="col-owner">Submitted By</th>
                        <th class="col-owner">Branch</th>
                        <th class="col-owner">Submitted At</th>
                        <th class="col-status">Status</th>
                        <th class="col-owner">Notes</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="approvalsTableBody">
                    <tr>
                        <td colspan="12" style="text-align:center; padding:20px;">Loading approvals...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div class="pagination-info">
                Showing <span class="info-start">0</span> to <span class="info-end">0</span> of <span class="info-total">0</span> approvals
            </div>
            <div class="pagination" id="paginationContainer"></div>
        </div>
    </main>
</div>

<div id="toastContainer" class="toast-container"></div>

<section id="applicationDetailsPanel" class="application-details-panel" hidden>
    <div id="applicationDetailsBackdrop" class="application-details-backdrop"></div>
    <div class="application-details-dialog" role="dialog" aria-modal="true" aria-labelledby="applicationModalTitle" aria-describedby="applicationModalSubtitle">
        <div class="application-details-header">
            <div>
                <h2 id="applicationModalTitle">Application Details</h2>
                <p id="applicationModalSubtitle" class="application-modal-subtitle">Select a row to view full credentials and submitted KYC fields.</p>
            </div>
            <button type="button" class="btn-cancel" id="applicationDetailsClearBtn">Close</button>
        </div>

        <div class="application-details-scroll">
            <div id="applicationDetailsEmpty" class="application-details-empty">
                Choose an approval row from the table to load all submitted credentials and documents.
            </div>

            <div id="applicationDetailsContent" class="application-modal-body" hidden>
                <section class="detail-section">
                    <div class="detail-section-header">
                        <h3>Approval Summary</h3>
                    </div>
                    <div id="approvalDetailsGrid" class="detail-grid"></div>
                </section>

                <section class="detail-section">
                    <div class="detail-section-header">
                        <h3>Client Information</h3>
                    </div>
                    <div id="clientDetailsGrid" class="detail-grid"></div>
                </section>

                <section class="detail-section">
                    <div class="detail-section-header">
                        <h3>KYC Verification</h3>
                    </div>
                    <div id="kycDetailsGrid" class="detail-grid"></div>
                </section>

                <section class="detail-section">
                    <div class="detail-section-header">
                        <h3>Submitted Credentials (All Fields)</h3>
                    </div>
                    <div id="allSubmittedDetailsGrid" class="detail-grid"></div>
                </section>

                <section class="detail-section">
                    <div class="detail-section-header">
                        <h3>Submitted Documents</h3>
                    </div>
                    <div id="applicationDocumentGrid" class="document-grid"></div>
                </section>
            </div>
        </div>
    </div>
</section>

<script>
    let currentPage = 1;
    let totalPages = 1;
    const pageSize = 10;
    const APPROVALS_AUTO_REFRESH_MS = 12000;
    const OFFICER_RESUBMITTED_JUST_NOW_MS = 15 * 60 * 1000;
    let searchDebounceTimer;
    let approvalsRefreshTimer = null;
    let approvalsRequestInFlight = false;
    let lastOfficerUpdateSignature = '';
    let officerUpdateSignatureInitialized = false;
    let currentOpenApprovalId = 0;
    let currentOpenApprovalStatus = '';
    let detailsActionsBusy = false;
    let rowActionOutsideClickBound = false;

    const applicationDetailsPanel = document.getElementById('applicationDetailsPanel');
    const applicationDetailsBackdrop = document.getElementById('applicationDetailsBackdrop');
    const applicationDetailsContent = document.getElementById('applicationDetailsContent');
    const applicationDetailsEmpty = document.getElementById('applicationDetailsEmpty');
    const applicationDetailsClearBtn = document.getElementById('applicationDetailsClearBtn');
    const applicationModalTitle = document.getElementById('applicationModalTitle');
    const applicationModalSubtitle = document.getElementById('applicationModalSubtitle');
    const approvalDetailsGrid = document.getElementById('approvalDetailsGrid');
    const clientDetailsGrid = document.getElementById('clientDetailsGrid');
    const kycDetailsGrid = document.getElementById('kycDetailsGrid');
    const allSubmittedDetailsGrid = document.getElementById('allSubmittedDetailsGrid');
    const applicationDocumentGrid = document.getElementById('applicationDocumentGrid');
    const decisionReviewNotes = document.getElementById('decisionReviewNotes');
    const modalApproveBtn = document.getElementById('modalApproveBtn');
    const modalDeclineBtn = document.getElementById('modalDeclineBtn');
    const modalResubmitBtn = document.getElementById('modalResubmitBtn');
    const modalActionButtons = [modalApproveBtn, modalDeclineBtn, modalResubmitBtn].filter(Boolean);

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function isBlank(value) {
        const text = String(value ?? '').trim();
        return text === '' || text.toLowerCase() === 'null' || text.toLowerCase() === 'undefined';
    }

    function humanizeKey(key) {
        const text = String(key || '').replace(/_/g, ' ').trim();
        if (!text) return 'Field';
        return text.replace(/\b\w/g, char => char.toUpperCase());
    }

    function orderedRecordEntries(record, priorityKeys = []) {
        if (!record || typeof record !== 'object') {
            return [];
        }

        const sourceEntries = Object.entries(record);
        if (!Array.isArray(priorityKeys) || priorityKeys.length === 0) {
            return sourceEntries;
        }

        const entryMap = new Map(sourceEntries);
        const ordered = [];

        priorityKeys.forEach(key => {
            if (!entryMap.has(key)) return;
            ordered.push([key, entryMap.get(key)]);
            entryMap.delete(key);
        });

        entryMap.forEach((val, key) => ordered.push([key, val]));
        return ordered;
    }

    function renderFallbackGrid(container, message) {
        if (!container) return;
        container.innerHTML = `
            <article class="detail-row detail-row-fallback">
                <label class="label">Info</label>
                <div class="detail-value">${escapeHtml(message || 'No data available')}</div>
            </article>
        `;
    }

    function renderRecordGrid(container, record, priorityKeys = []) {
        if (!container) return;

        if (!record || typeof record !== 'object') {
            renderFallbackGrid(container, 'No data available');
            return;
        }

        const entries = orderedRecordEntries(record, priorityKeys)
            .filter(([key]) => String(key || '').toLowerCase() !== 'password');

        if (entries.length === 0) {
            renderFallbackGrid(container, 'No data available');
            return;
        }

        const html = entries.map(([key, rawValue]) => {
            const normalizedKey = String(key || '').toLowerCase();
            let value = rawValue;

            if (normalizedKey.endsWith('_at') || normalizedKey.includes('date')) {
                const rawText = String(rawValue ?? '').trim();
                if (normalizedKey.includes('date') && /^\d{4}-\d{2}-\d{2}$/.test(rawText)) {
                    value = rawText;
                } else {
                    value = formatDateTime(rawValue);
                }
            } else if (typeof rawValue === 'boolean') {
                value = rawValue ? 'Yes' : 'No';
            } else if (rawValue && typeof rawValue === 'object') {
                value = JSON.stringify(rawValue);
            }

            const displayValue = isBlank(value) ? 'N/A' : String(value);
            const valueHtml = escapeHtml(displayValue).replace(/\n/g, '<br>');

            return `
                <article class="detail-row">
                    <label class="label">${escapeHtml(humanizeKey(key))}</label>
                    <div class="detail-value">${valueHtml}</div>
                </article>
            `;
        }).join('');

        container.innerHTML = html;
    }

    function formatFileSize(bytes) {
        const size = Number(bytes || 0);
        if (!Number.isFinite(size) || size <= 0) return 'Unknown size';

        const units = ['B', 'KB', 'MB', 'GB'];
        let value = size;
        let unitIndex = 0;

        while (value >= 1024 && unitIndex < units.length - 1) {
            value /= 1024;
            unitIndex += 1;
        }

        const rounded = value >= 10 || unitIndex === 0 ? value.toFixed(0) : value.toFixed(1);
        return `${rounded} ${units[unitIndex]}`;
    }

    function getFileExtension(fileName) {
        const text = String(fileName || '').toLowerCase().trim();
        if (!text.includes('.')) return '';
        return text.split('.').pop() || '';
    }

    function getDocumentPreviewKind(doc) {
        const fileType = String(doc.file_type || '').toLowerCase();
        const ext = getFileExtension(doc.file_name || doc.file_path || '');

        if (fileType.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(ext)) {
            return 'image';
        }

        if (fileType.includes('pdf') || ext === 'pdf') {
            return 'pdf';
        }

        return 'other';
    }

    function safePreviewUrl(rawUrl) {
        const value = String(rawUrl || '').trim();
        if (!value) return '';

        try {
            return encodeURI(value);
        } catch (error) {
            return value;
        }
    }

    function renderDocuments(documents) {
        if (!applicationDocumentGrid) return;

        if (!Array.isArray(documents) || documents.length === 0) {
            applicationDocumentGrid.innerHTML = `
                <article class="document-card">
                    <div class="document-preview">
                        <div class="doc-fallback">
                            <i class="bi bi-file-earmark"></i>
                            <span>No uploaded documents found for this application.</span>
                        </div>
                    </div>
                </article>
            `;
            return;
        }

        applicationDocumentGrid.innerHTML = documents.map(doc => {
            const previewUrl = safePreviewUrl(doc.preview_url || '');
            const kind = getDocumentPreviewKind(doc);
            const docName = doc.file_name || doc.document_type || 'Document';
            const uploadedAt = formatDateTime(doc.uploaded_at);
            const fileType = doc.file_type || 'Unknown type';
            const fileSize = formatFileSize(doc.file_size);

            let previewHtml = `
                <div class="doc-fallback">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Preview unavailable for this file type.</span>
                </div>
            `;

            if (previewUrl && kind === 'image') {
                previewHtml = `<img src="${escapeHtml(previewUrl)}" alt="${escapeHtml(docName)}">`;
            } else if (previewUrl && kind === 'pdf') {
                previewHtml = `<iframe src="${escapeHtml(previewUrl)}" title="${escapeHtml(docName)}"></iframe>`;
            }

            return `
                <article class="document-card">
                    <div class="document-preview">${previewHtml}</div>
                    <div class="document-meta">
                        <div class="doc-name">${escapeHtml(docName)}</div>
                        <div class="doc-info">Type: ${escapeHtml(fileType)}</div>
                        <div class="doc-info">Size: ${escapeHtml(fileSize)}</div>
                        <div class="doc-info">Uploaded: ${escapeHtml(uploadedAt || 'N/A')}</div>
                        <div class="doc-info">Uploader: ${escapeHtml(doc.uploaded_by_name || 'N/A')}</div>
                        ${previewUrl ? `<a class="doc-link" href="${escapeHtml(previewUrl)}" target="_blank" rel="noopener">Open full document</a>` : ''}
                    </div>
                </article>
            `;
        }).join('');
    }

    function createToast(type, title, msg, containerId = 'toastContainer') {
        const icons = {
            success: 'bi-check-circle-fill',
            error: 'bi-x-circle-fill',
            info: 'bi-info-circle-fill'
        };

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <i class="bi ${icons[type] || icons.info} toast-icon"></i>
            <div class="toast-body">
                <div class="toast-title">${escapeHtml(title)}</div>
                <div class="toast-message">${escapeHtml(msg)}</div>
            </div>
            <i class="bi bi-x toast-close"></i>
        `;

        const closeBtn = toast.querySelector('.toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => removeToast(toast));
        }

        let container = document.getElementById(containerId);
        if (!container) {
            container = document.createElement('div');
            container.id = containerId;
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        container.appendChild(toast);
        setTimeout(() => removeToast(toast), 4200);
    }

    function removeToast(element) {
        if (!element) return;
        element.classList.add('out');
        setTimeout(() => element.remove(), 220);
    }

    function getActiveFilters() {
        return {
            search: document.getElementById('searchInput').value.trim(),
            status: document.getElementById('filterStatus').value,
            classification: document.getElementById('filterClassification').value,
            type: document.getElementById('filterType').value,
            branch: document.getElementById('filterBranch').value
        };
    }

    function setTableLoading(isLoading) {
        const wrapper = document.querySelector('.table-wrapper');
        if (!wrapper) return;
        wrapper.classList.toggle('is-loading', isLoading);
    }

    function updateBranchFilterOptions(branches) {
        const select = document.getElementById('filterBranch');
        if (!select) return;

        const currentValue = select.value;
        const uniqueBranches = Array.from(new Set((Array.isArray(branches) ? branches : [])
            .map(branch => String(branch || '').trim())
            .filter(Boolean)));

        select.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'All Branches';
        select.appendChild(defaultOption);

        uniqueBranches.forEach(branch => {
            const option = document.createElement('option');
            option.value = branch;
            option.textContent = branch;
            select.appendChild(option);
        });

        if (currentValue && uniqueBranches.includes(currentValue)) {
            select.value = currentValue;
        }
    }

    function formatDateTime(value) {
        if (!value) return 'N/A';
        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return value;
        return date.toLocaleString();
    }

    function formatType(value) {
        const text = String(value || '').toLowerCase();
        if (!text) return 'N/A';
        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    function formatClassification(value) {
        const text = String(value || '').toLowerCase();
        if (text === 'agent') return 'Agent';
        return text === 'client' ? 'Client' : 'N/A';
    }

    function statusBadgeClass(status) {
        const normalized = String(status || '').toLowerCase();
        if (normalized === 'approved') return 'approval-status-approved';
        if (normalized === 'declined') return 'approval-status-declined';
        if (normalized === 'resubmit') return 'approval-status-resubmit';
        return 'approval-status-pending';
    }

    function resolveClientName(row) {
        const reference = String(row && row.reference_code ? row.reference_code : '').trim().toLowerCase();
        const fullName = [
            row && row.first_name ? row.first_name : '',
            row && row.middle_name ? row.middle_name : '',
            row && row.last_name ? row.last_name : ''
        ]
            .map(part => String(part || '').trim())
            .filter(Boolean)
            .join(' ');

        const candidates = [
            row && row.client_name ? row.client_name : '',
            fullName,
            row && row.contact_person ? row.contact_person : '',
            row && row.display_name ? row.display_name : ''
        ];

        for (const candidate of candidates) {
            const value = String(candidate || '').trim();
            if (!value) {
                continue;
            }

            if (reference && value.toLowerCase() === reference) {
                continue;
            }

            return value;
        }

        return 'N/A';
    }

    function hasOfficerUpdates(row) {
        return Number(row && row.has_officer_updates ? row.has_officer_updates : 0) === 1;
    }

    function isOfficerResubmittedJustNow(value) {
        if (!value) {
            return false;
        }

        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return false;
        }

        const elapsed = Date.now() - date.getTime();
        return elapsed >= 0 && elapsed <= OFFICER_RESUBMITTED_JUST_NOW_MS;
    }

    function buildOfficerUpdateSignature(rows) {
        if (!Array.isArray(rows) || rows.length === 0) {
            return '';
        }

        return rows
            .filter(row => hasOfficerUpdates(row))
            .map(row => `${Number(row.approval_id || 0)}:${String(row.officer_resubmitted_at || '')}`)
            .sort()
            .join('|');
    }

    function notifyOfficerResubmissions(rows) {
        const signature = buildOfficerUpdateSignature(rows);

        if (!officerUpdateSignatureInitialized) {
            lastOfficerUpdateSignature = signature;
            officerUpdateSignatureInitialized = true;
            return;
        }

        if (signature !== '' && signature !== lastOfficerUpdateSignature) {
            const count = Array.isArray(rows)
                ? rows.filter(row => hasOfficerUpdates(row)).length
                : 0;
            createToast(
                'info',
                'Officer Update Received',
                `${count} application${count === 1 ? '' : 's'} updated by officer and pending review.`
            );
        }

        lastOfficerUpdateSignature = signature;
    }

    function refreshModalActionButtons() {
        modalActionButtons.forEach(button => {
            const action = String(button.dataset.action || '').toLowerCase();
            const disableByStatus = currentOpenApprovalStatus !== '' && action === currentOpenApprovalStatus;
            button.disabled = detailsActionsBusy || currentOpenApprovalId <= 0 || disableByStatus;
        });
    }

    function setModalActionsBusy(isBusy) {
        detailsActionsBusy = Boolean(isBusy);
        refreshModalActionButtons();
    }

    function setDetailsPanelVisibility(hasSelection) {
        if (applicationDetailsContent) {
            applicationDetailsContent.hidden = !hasSelection;
        }

        if (applicationDetailsEmpty) {
            applicationDetailsEmpty.hidden = hasSelection;
        }
    }

    function highlightSelectedApprovalRow() {
        document.querySelectorAll('#approvalsTableBody tr.approval-row').forEach(row => {
            const rowApprovalId = Number(row.dataset.approvalId || 0);
            row.classList.toggle('is-selected', currentOpenApprovalId > 0 && rowApprovalId === currentOpenApprovalId);
        });
    }

    function renderTable(rows) {
        const tbody = document.getElementById('approvalsTableBody');
        if (!tbody) return;

        if (!Array.isArray(rows) || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="12" style="text-align:center; padding: 22px;">No approval records found</td></tr>';
            return;
        }

        tbody.innerHTML = '';

        rows.forEach(row => {
            const tr = document.createElement('tr');
            const contact = row.mobile_phone || row.office_phone || 'N/A';
            const notes = row.review_notes || '';
            const clientName = resolveClientName(row);
            const status = String(row.approval_status || 'pending').toLowerCase();
            const approvalId = Number(row.approval_id || 0);
            const officerUpdated = hasOfficerUpdates(row);
            const officerResubmittedJustNow = officerUpdated && isOfficerResubmittedJustNow(row.officer_resubmitted_at);
            const officerUpdatedAt = officerUpdated ? formatDateTime(row.officer_resubmitted_at) : '';
            const notesHtml = officerUpdated
                ? `
                    <div class="officer-update-badge"><i class="bi bi-bell-fill"></i>Updated by Officer</div>
                    <div class="notes-text">${escapeHtml(notes || 'Changes were submitted and sent back for review.')}</div>
                    <div class="officer-update-meta">Resubmitted: ${escapeHtml(officerUpdatedAt || 'N/A')}</div>
                `
                : `<div class="notes-text">${escapeHtml(notes || '—')}</div>`;
            const statusHtml = `
                <span class="status-stack">
                    <span class="approval-status-badge ${statusBadgeClass(status)}">${escapeHtml(status)}</span>
                    ${officerResubmittedJustNow ? '<span class="resubmitted-now-badge">Resubmitted just now</span>' : ''}
                </span>
            `;

            tr.className = 'approval-row';
            if (approvalId === currentOpenApprovalId) {
                tr.classList.add('is-selected');
            }
            tr.dataset.approvalId = String(approvalId);

            tr.innerHTML = `
                <td class="col-ref">
                    <span class="ref-badge">${escapeHtml(row.reference_code || 'N/A')}</span>
                </td>
                <td class="col-name">${escapeHtml(clientName)}</td>
                <td class="col-type">${escapeHtml(formatClassification(row.client_classification))}</td>
                <td class="col-type">${escapeHtml(formatType(row.client_type))}</td>
                <td class="col-contact">${escapeHtml(contact)}</td>
                <td class="col-email">${escapeHtml(row.email || 'N/A')}</td>
                <td class="col-owner">${escapeHtml(row.submitted_by_name || 'N/A')}</td>
                <td class="col-owner">${escapeHtml(row.submitted_by_branch || 'N/A')}</td>
                <td class="col-owner">${escapeHtml(formatDateTime(row.submitted_at))}</td>
                <td class="col-status">${statusHtml}</td>
                <td class="notes-cell">${notesHtml}</td>
                <td class="col-actions">
                    <div class="action-stack">
                        <button type="button" class="row-action-toggle" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-three-dots"></i>Actions
                        </button>
                        <div class="row-action-menu" role="menu">
                            <button type="button" class="action-icon row-action-item action-approve" data-action="approve" data-id="${approvalId}" role="menuitem"><i class="bi bi-check2-circle"></i>Approve</button>
                            <button type="button" class="action-icon row-action-item action-decline" data-action="decline" data-id="${approvalId}" role="menuitem"><i class="bi bi-x-circle"></i>Decline</button>
                            <button type="button" class="action-icon row-action-item action-resubmit" data-action="resubmit" data-id="${approvalId}" role="menuitem"><i class="bi bi-arrow-repeat"></i>Resubmit</button>
                        </div>
                    </div>
                </td>
            `;

            tbody.appendChild(tr);
        });

        attachActionHandlers();
        attachRowHandlers();
    }

    function updatePaginationInfo(payload) {
        const start = document.querySelector('.info-start');
        const end = document.querySelector('.info-end');
        const total = document.querySelector('.info-total');

        const totalItems = Number(payload.total || 0);
        const page = Number(payload.page || 1);
        const size = Number(payload.pageSize || pageSize);

        const startRecord = totalItems > 0 ? ((page - 1) * size) + 1 : 0;
        const endRecord = totalItems > 0 ? Math.min(page * size, totalItems) : 0;

        if (start) start.textContent = String(startRecord);
        if (end) end.textContent = String(endRecord);
        if (total) total.textContent = String(totalItems);
    }

    function renderPagination(payload) {
        const container = document.getElementById('paginationContainer');
        if (!container) return;

        container.innerHTML = '';

        const page = Number(payload.page || 1);
        const pages = Number(payload.totalPages || 1);

        const createButton = (label, disabled, targetPage) => {
            const btn = document.createElement('button');
            btn.className = 'pagination-btn';
            btn.disabled = disabled;
            btn.innerHTML = label;
            if (!disabled) {
                btn.addEventListener('click', () => loadApprovals(targetPage));
            }
            return btn;
        };

        container.appendChild(createButton('<i class="bi bi-chevron-left"></i>', page <= 1, page - 1));

        const maxButtons = 5;
        let start = Math.max(1, page - 2);
        let end = Math.min(pages, start + maxButtons - 1);
        if ((end - start) < (maxButtons - 1)) {
            start = Math.max(1, end - maxButtons + 1);
        }

        for (let i = start; i <= end; i += 1) {
            const btn = createButton(String(i), false, i);
            if (i === page) btn.classList.add('active');
            container.appendChild(btn);
        }

        container.appendChild(createButton('<i class="bi bi-chevron-right"></i>', page >= pages, page + 1));
    }

    function openApplicationModalShell(approvalId) {
        if (!applicationDetailsPanel) return;

        applicationDetailsPanel.hidden = false;
        applicationDetailsPanel.classList.add('open');
        document.body.style.overflow = 'hidden';

        currentOpenApprovalId = Number(approvalId || 0);
        currentOpenApprovalStatus = '';
        setModalActionsBusy(true);
        setDetailsPanelVisibility(true);
        highlightSelectedApprovalRow();

        if (applicationModalTitle) {
            applicationModalTitle.textContent = `Application #${currentOpenApprovalId || '...'}`;
        }

        if (applicationModalSubtitle) {
            applicationModalSubtitle.textContent = 'Loading application details...';
        }

        renderFallbackGrid(approvalDetailsGrid, 'Loading approval summary...');
        renderFallbackGrid(clientDetailsGrid, 'Loading client details...');
        renderFallbackGrid(kycDetailsGrid, 'Loading KYC details...');
        renderFallbackGrid(allSubmittedDetailsGrid, 'Loading submitted KYC data...');

        if (applicationDocumentGrid) {
            applicationDocumentGrid.innerHTML = `
                <article class="document-card">
                    <div class="document-preview">
                        <div class="doc-fallback">
                            <i class="bi bi-hourglass-split"></i>
                            <span>Loading documents...</span>
                        </div>
                    </div>
                </article>
            `;
        }
    }

    function closeApplicationModal() {
        if (applicationDetailsPanel) {
            applicationDetailsPanel.classList.remove('open');
            applicationDetailsPanel.hidden = true;
        }
        document.body.style.overflow = '';

        currentOpenApprovalId = 0;
        currentOpenApprovalStatus = '';
        setModalActionsBusy(false);
        setDetailsPanelVisibility(false);
        highlightSelectedApprovalRow();

        if (decisionReviewNotes) {
            decisionReviewNotes.value = '';
        }

        if (applicationModalTitle) {
            applicationModalTitle.textContent = 'Application Details';
        }

        if (applicationModalSubtitle) {
            applicationModalSubtitle.textContent = 'Select a row to view full credentials and submitted KYC fields.';
        }

        if (applicationDetailsEmpty) {
            applicationDetailsEmpty.textContent = 'Choose an approval row from the table to load all submitted credentials and documents.';
        }
    }

    function renderApplicationDetails(payload) {
        const approval = payload.approval || null;
        const client = payload.client || null;
        const kyc = payload.kyc || null;
        const allSubmittedData = payload.all_submitted_data || null;
        const documents = Array.isArray(payload.documents) ? payload.documents : [];

        const referenceCode = approval && approval.reference_code ? approval.reference_code : 'N/A';
        const status = approval && approval.approval_status ? String(approval.approval_status).toUpperCase() : 'N/A';
        currentOpenApprovalStatus = approval && approval.approval_status
            ? String(approval.approval_status).toLowerCase()
            : '';
        setDetailsPanelVisibility(true);
        highlightSelectedApprovalRow();
        setModalActionsBusy(false);

        if (applicationModalTitle) {
            applicationModalTitle.textContent = `Application ${referenceCode}`;
        }

        if (applicationModalSubtitle) {
            const submittedBy = approval && approval.submitted_by_name ? approval.submitted_by_name : 'Unknown submitter';
            const submittedAt = approval ? formatDateTime(approval.submitted_at) : 'N/A';
            applicationModalSubtitle.textContent = `Status: ${status} | Submitted by ${submittedBy} | ${submittedAt}`;
        }

        if (decisionReviewNotes) {
            decisionReviewNotes.value = approval && !isBlank(approval.review_notes)
                ? String(approval.review_notes)
                : '';
        }

        renderRecordGrid(approvalDetailsGrid, approval, [
            'approval_id',
            'reference_code',
            'client_classification',
            'client_type',
            'approval_status',
            'display_name',
            'client_name',
            'submitted_by_name',
            'submitted_by_branch',
            'submitted_at',
            'reviewed_by_name',
            'reviewed_at',
            'approved_at',
            'review_notes',
            'client_id'
        ]);

        renderRecordGrid(clientDetailsGrid, client, [
            'client_id',
            'reference_code',
            'client_number',
            'client_classification',
            'client_type',
            'client_name',
            'first_name',
            'middle_name',
            'last_name',
            'contact_person',
            'mobile_phone',
            'office_phone',
            'email',
            'address',
            'city',
            'province',
            'birth_date',
            'verification_status',
            'created_at',
            'updated_at'
        ]);

        renderRecordGrid(kycDetailsGrid, kyc, [
            'kyc_id',
            'client_id',
            'id_type',
            'id_number',
            'tin',
            'nationality',
            'civil_status',
            'occupation',
            'status',
            'submitted_at',
            'created_at',
            'updated_at'
        ]);

        renderRecordGrid(allSubmittedDetailsGrid, allSubmittedData, [
            'approval_reference_code',
            'approval_client_type',
            'approval_client_classification',
            'approval_approval_status',
            'approval_submitted_by_name',
            'approval_submitted_by_branch',
            'approval_submitted_at',
            'client_reference_code',
            'client_client_type',
            'client_client_classification',
            'client_client_name',
            'client_first_name',
            'client_middle_name',
            'client_last_name',
            'client_contact_person',
            'client_mobile_phone',
            'client_email',
            'client_home_address',
            'client_business_address',
            'client_verification_status',
            'kyc_reference_code',
            'kyc_ref_code',
            'kyc_client_type',
            'kyc_last_name',
            'kyc_first_name',
            'kyc_middle_name',
            'kyc_birthdate',
            'kyc_gender',
            'kyc_nationality',
            'kyc_id_type',
            'kyc_id_number',
            'kyc_occupation',
            'kyc_company',
            'kyc_mobile',
            'kyc_phone',
            'kyc_email',
            'kyc_address',
            'kyc_status',
            'kyc_submitted_at'
        ]);

        renderDocuments(documents);
    }

    function openApplicationModal(approvalId) {
        const parsedApprovalId = Number(approvalId || 0);
        if (!parsedApprovalId) {
            return;
        }

        openApplicationModalShell(parsedApprovalId);

        const query = new URLSearchParams({
            action: 'get_application',
            approval_id: String(parsedApprovalId)
        });

        fetch(`../handlers/client_approvals.php?${query.toString()}`, {
            method: 'GET',
            credentials: 'include'
        })
            .then(response => response.json())
            .then(payload => {
                if (!payload.success || !payload.data) {
                    throw new Error(payload.message || 'Failed to load application details');
                }

                if (currentOpenApprovalId !== parsedApprovalId) {
                    return;
                }

                renderApplicationDetails(payload.data);
            })
            .catch(error => {
                currentOpenApprovalStatus = '';
                setModalActionsBusy(false);

                renderFallbackGrid(approvalDetailsGrid, error.message || 'Unable to load application details.');
                renderFallbackGrid(clientDetailsGrid, 'Client information is unavailable.');
                renderFallbackGrid(kycDetailsGrid, 'KYC information is unavailable.');
                renderFallbackGrid(allSubmittedDetailsGrid, 'Submitted KYC data is unavailable.');

                if (applicationDocumentGrid) {
                    applicationDocumentGrid.innerHTML = `
                        <article class="document-card">
                            <div class="document-preview">
                                <div class="doc-fallback">
                                    <i class="bi bi-exclamation-circle"></i>
                                    <span>${escapeHtml(error.message || 'Unable to load documents.')}</span>
                                </div>
                            </div>
                        </article>
                    `;
                }

                if (applicationModalSubtitle) {
                    applicationModalSubtitle.textContent = 'Unable to load this application.';
                }

                createToast('error', 'Load Failed', error.message || 'Unable to load application details.');
            });
    }

    function attachRowHandlers() {
        if (!applicationDetailsPanel) {
            return;
        }

        document.querySelectorAll('#approvalsTableBody tr.approval-row[data-approval-id]').forEach(row => {
            row.addEventListener('click', function (event) {
                if (event.target.closest('.action-stack')) {
                    return;
                }

                const approvalId = Number(this.dataset.approvalId || 0);
                if (!approvalId) return;
                openApplicationModal(approvalId);
            });
        });
    }

    function loadApprovals(page = 1, options = {}) {
        if (approvalsRequestInFlight) {
            return;
        }

        const silent = Boolean(options.silent);
        if (!silent) {
            setTableLoading(true);
        }

        approvalsRequestInFlight = true;

        const filters = getActiveFilters();
        const query = new URLSearchParams({
            action: 'list',
            page: String(page),
            pageSize: String(pageSize),
            search: filters.search,
            status: filters.status,
            classification: filters.classification,
            type: filters.type,
            branch: filters.branch
        });

        fetch(`../handlers/client_approvals.php?${query.toString()}`, {
            method: 'GET',
            credentials: 'include'
        })
            .then(response => response.json())
            .then(payload => {
                if (!payload.success) {
                    throw new Error(payload.message || 'Failed to load approvals');
                }

                currentPage = Number(payload.page || 1);
                totalPages = Number(payload.totalPages || 1);

                updateBranchFilterOptions(payload.availableBranches || []);
                renderTable(payload.data || []);
                notifyOfficerResubmissions(payload.data || []);
                updatePaginationInfo(payload);
                renderPagination(payload);
            })
            .catch(error => {
                const tbody = document.getElementById('approvalsTableBody');
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="12" style="text-align:center; color:#b42318; padding: 22px;">${escapeHtml(error.message || 'Failed to load approvals')}</td></tr>`;
                }
                updatePaginationInfo({ total: 0, page: 1, pageSize, totalPages: 1 });
                renderPagination({ page: 1, totalPages: 1 });
            })
            .finally(() => {
                approvalsRequestInFlight = false;
                if (!silent) {
                    setTableLoading(false);
                }
            });
    }

    function stopApprovalsAutoRefresh() {
        if (approvalsRefreshTimer) {
            window.clearInterval(approvalsRefreshTimer);
            approvalsRefreshTimer = null;
        }
    }

    function startApprovalsAutoRefresh() {
        stopApprovalsAutoRefresh();
        approvalsRefreshTimer = window.setInterval(() => {
            if (document.hidden) {
                return;
            }

            loadApprovals(currentPage, { silent: true });
        }, APPROVALS_AUTO_REFRESH_MS);
    }

    function getActionTitle(action) {
        if (action === 'approve') return 'Approve';
        if (action === 'decline') return 'Decline';
        return 'Resubmit';
    }

    function runAction(approvalId, action, options = {}) {
        const source = String(options.source || 'table');
        const actionTitle = getActionTitle(action);
        let reviewNote = '';

        if (source === 'details') {
            reviewNote = decisionReviewNotes ? String(decisionReviewNotes.value || '').trim() : '';
            if (action !== 'approve' && reviewNote === '') {
                createToast('info', 'Notes Required', `Please provide a reason before you ${actionTitle.toLowerCase()} this application.`);
                if (decisionReviewNotes) {
                    decisionReviewNotes.focus();
                }
                return;
            }
        } else {
            const notePrompt = action === 'approve'
                ? 'Optional note for approval:'
                : `Reason for ${actionTitle.toLowerCase()}:`;
            const promptedNote = window.prompt(notePrompt, '');
            if (promptedNote === null) {
                return;
            }
            reviewNote = promptedNote;
        }

        const confirmed = window.confirm(`Confirm ${actionTitle.toLowerCase()} for approval #${approvalId}?`);
        if (!confirmed) {
            return;
        }

        if (source === 'details') {
            setModalActionsBusy(true);
        }

        const formData = new FormData();
        formData.append('action', action);
        formData.append('approval_id', String(approvalId));
        formData.append('review_notes', reviewNote);

        fetch('../handlers/client_approvals.php', {
            method: 'POST',
            credentials: 'include',
            body: formData
        })
            .then(response => response.json())
            .then(payload => {
                if (!payload.success) {
                    throw new Error(payload.message || `Failed to ${action} approval`);
                }

                createToast('success', 'Updated', `Approval has been marked as ${action}.`);
                loadApprovals(currentPage);

                if (currentOpenApprovalId === approvalId) {
                    openApplicationModal(approvalId);
                }
            })
            .catch(error => {
                createToast('error', 'Update Failed', error.message || `Unable to ${action} approval.`);

                if (source === 'details') {
                    setModalActionsBusy(false);
                }
            });
    }

    function closeAllRowActionMenus() {
        document.querySelectorAll('#approvalsTableBody .action-stack.is-open').forEach(stack => {
            stack.classList.remove('is-open');
            const toggle = stack.querySelector('.row-action-toggle');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function attachRowActionMenuHandlers() {
        document.querySelectorAll('#approvalsTableBody .row-action-toggle').forEach(toggle => {
            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                const stack = this.closest('.action-stack');
                if (!stack) return;

                const shouldOpen = !stack.classList.contains('is-open');
                closeAllRowActionMenus();

                if (shouldOpen) {
                    stack.classList.add('is-open');
                    this.setAttribute('aria-expanded', 'true');
                } else {
                    this.setAttribute('aria-expanded', 'false');
                }
            });
        });

        if (!rowActionOutsideClickBound) {
            document.addEventListener('click', event => {
                if (!event.target.closest('#approvalsTableBody .action-stack')) {
                    closeAllRowActionMenus();
                }
            });
            rowActionOutsideClickBound = true;
        }
    }

    function attachActionHandlers() {
        document.querySelectorAll('#approvalsTableBody .action-icon[data-action]').forEach(button => {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                closeAllRowActionMenus();

                const approvalId = Number(this.dataset.id || 0);
                const action = this.dataset.action || '';
                if (!approvalId || !action) return;
                runAction(approvalId, action);
            });
        });

        attachRowActionMenuHandlers();
    }

    function initializeApplicationModalEvents() {
        if (applicationDetailsClearBtn) {
            applicationDetailsClearBtn.addEventListener('click', closeApplicationModal);
        }

        if (applicationDetailsBackdrop) {
            applicationDetailsBackdrop.addEventListener('click', closeApplicationModal);
        }

        modalActionButtons.forEach(button => {
            button.addEventListener('click', event => {
                event.preventDefault();

                const action = String(button.dataset.action || '').toLowerCase();
                if (!action || currentOpenApprovalId <= 0) {
                    return;
                }

                runAction(currentOpenApprovalId, action, { source: 'details' });
            });
        });

        setDetailsPanelVisibility(false);

        refreshModalActionButtons();

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && applicationDetailsPanel && applicationDetailsPanel.classList.contains('open')) {
                closeApplicationModal();
            }
        });
    }

    function applyFilters() {
        loadApprovals(1);
    }

    document.getElementById('searchInput').addEventListener('keyup', function () {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => applyFilters(), 300);
    });

    document.getElementById('filterStatus').addEventListener('change', applyFilters);
    document.getElementById('filterClassification').addEventListener('change', applyFilters);
    document.getElementById('filterType').addEventListener('change', applyFilters);
    document.getElementById('filterBranch').addEventListener('change', applyFilters);

    document.addEventListener('DOMContentLoaded', () => {
        initializeApplicationModalEvents();
        loadApprovals(1);
        startApprovalsAutoRefresh();
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            loadApprovals(currentPage, { silent: true });
        }
    });

    window.addEventListener('beforeunload', () => {
        stopApprovalsAutoRefresh();
    });
</script>
<?php endif; ?>
</body>
</html>
