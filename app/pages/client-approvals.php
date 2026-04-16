<?php
require_once '../config/session.php';
requireLogin();

$currentUserRole = strtolower(trim($_SESSION['role'] ?? ''));
$currentUserDepartment = strtoupper(trim($_SESSION['department'] ?? ''));
$currentUserBranch = strtoupper(trim($_SESSION['branch'] ?? ''));
$isHeadOfficeUser = $currentUserRole === 'admin'
    || $currentUserDepartment === 'HEAD OFFICE'
    || in_array($currentUserBranch, ['HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH'], true);
$approvalQueue = strtolower(trim($_GET['queue'] ?? 'client'));
if (!in_array($approvalQueue, ['client', 'agent'], true)) {
    $approvalQueue = 'client';
}
$approvalPageTitle = $approvalQueue === 'agent' ? 'Agents Approval' : 'Client Approvals';
$approvalBreadcrumbTitle = $approvalQueue === 'agent' ? 'Agents Approval' : 'Client Approvals';
$approvalAccessLabel = $approvalQueue === 'agent' ? 'Agent approvals' : 'Client approvals';
$approvalDefaultClassification = $approvalQueue === 'agent' ? 'agent' : '';

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

        .clients-table th.col-ref,
        .clients-table td.col-ref {
            width: 13%;
            min-width: 140px;
        }

        .clients-table th.col-name,
        .clients-table td.col-name {
            width: 16%;
            min-width: 170px;
        }

        .clients-table th.col-type,
        .clients-table td.col-type {
            width: 9%;
            min-width: 96px;
        }

        .clients-table th.col-branch,
        .clients-table td.col-branch {
            width: 15%;
            min-width: 132px;
        }

        .clients-table th.col-submitted-by,
        .clients-table td.col-submitted-by {
            width: 15%;
            min-width: 140px;
        }

        .clients-table th.col-submitted-at,
        .clients-table td.col-submitted-at {
            width: 10%;
            min-width: 120px;
        }

        .clients-table th.col-status,
        .clients-table td.col-status {
            width: 10%;
            min-width: 92px;
            overflow: visible;
            text-overflow: clip;
        }

        .clients-table th.col-actions,
        .clients-table td.col-actions {
            width: 14%;
            min-width: 170px;
            overflow: visible;
            text-overflow: clip;
        }

        .col-name {
            font-weight: 600;
            color: #111827;
        }

        .col-submitted-by {
            color: #111827;
        }

        .col-branch {
            font-weight: 700;
            color: #111827;
            font-size: 0.72rem;
            letter-spacing: 0.01em;
        }

        .clients-table th.col-type,
        .clients-table td.col-type {
            overflow: hidden;
            text-overflow: clip;
        }

        .approval-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            line-height: 1;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .approval-pill-client {
            background: #e7f8ee;
            color: #0d6b37;
            border-color: #b7e6ca;
        }

        .approval-pill-agent {
            background: #f3e8ff;
            color: #5b21b6;
            border-color: #d9c2ff;
        }

        .approval-pill-individual {
            background: #eaf2ff;
            color: #1f5ea9;
            border-color: #c8ddf6;
        }

        .approval-pill-corporate {
            background: #ecfdf5;
            color: #16633f;
            border-color: #bbe6c9;
        }

        .approval-pill-obligee {
            background: #f4e8de;
            color: #6b4320;
            border-color: #e3c39c;
        }

        .approval-pill-muted {
            background: #f3f4f6;
            color: #4b5563;
            border-color: #d1d5db;
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
            padding: 2px 7px;
            font-size: 0.64rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            background: #ffe9d6;
            color: #8a3800;
            border: 1px solid #f2c49f;
            white-space: nowrap;
        }

        .notes-cell {
            max-width: 180px;
            color: #4b5563;
            font-size: 0.74rem;
            line-height: 1.28;
            white-space: normal;
            overflow: hidden;
        }

        .notes-cell .notes-text {
            margin-top: 3px;
            white-space: normal;
            word-break: break-word;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .action-stack {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 4px;
            align-items: stretch;
        }

        .action-stack .action-icon {
            width: 100%;
            min-width: 0;
            height: auto;
            border-radius: 8px;
            padding: 4px 6px;
            font-size: 0.64rem;
            line-height: 1;
            font-weight: 600;
            display: inline-flex;
            gap: 4px;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }

        .action-stack .action-resubmit {
            grid-column: 1 / -1;
        }

        .action-stack .action-icon[disabled],
        .application-modal-actions .action-icon[disabled] {
            opacity: 0.45;
            cursor: not-allowed;
            pointer-events: none;
            filter: grayscale(0.1);
            box-shadow: none;
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

        #approvalsTableBody tr.approval-row.is-checked td {
            background-color: #f3faf5;
        }

        #approvalsTableBody tr.approval-row.is-selected td {
            background-color: #eaf5ef;
        }

        .clients-table th.col-checkbox,
        .clients-table td.col-checkbox {
            width: 42px;
            min-width: 42px;
            max-width: 42px;
            padding-left: 4px;
            padding-right: 4px;
            text-align: center;
            white-space: nowrap;
            box-sizing: border-box;
        }

        .clients-table th.col-checkbox input,
        .clients-table td.col-checkbox input {
            display: block;
            margin: 0 auto;
        }

        .table-wrapper {
            flex: 1 1 auto;
            min-height: 0;
        }

        .clients-table {
            width: max(100%, 1080px);
            min-width: 1080px;
        }

        .clients-table th,
        .clients-table td {
            padding: 5px 8px;
        }

        .clients-table th.col-ref,
        .clients-table td.col-ref {
            width: 12%;
            min-width: 120px;
        }

        .clients-table th.col-name,
        .clients-table td.col-name {
            width: 15%;
            min-width: 150px;
        }

        .clients-table th.col-type,
        .clients-table td.col-type {
            width: 8%;
            min-width: 84px;
        }

        .clients-table th.col-branch,
        .clients-table td.col-branch {
            width: 13%;
            min-width: 120px;
        }

        .clients-table th.col-submitted-by,
        .clients-table td.col-submitted-by {
            width: 14%;
            min-width: 128px;
        }

        .clients-table th.col-submitted-at,
        .clients-table td.col-submitted-at {
            width: 10%;
            min-width: 108px;
        }

        .clients-table th.col-status,
        .clients-table td.col-status {
            width: 8%;
            min-width: 88px;
        }

        .clients-table th.col-actions,
        .clients-table td.col-actions {
            width: 14%;
            min-width: 150px;
        }

        .table-footer {
            padding-top: 8px;
            gap: 10px;
        }

        .pagination {
            gap: 6px;
        }

        .pagination-btn {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            font-size: 0.72rem;
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
            background: rgba(15, 23, 42, 0.58);
        }

        .application-details-dialog {
            position: relative;
            width: min(1180px, calc(100vw - 26px));
            max-height: calc(100vh - 32px);
            margin: 16px auto;
            background: #ffffff;
            border: 1px solid #d4e3da;
            border-radius: 14px;
            box-shadow: 0 22px 52px rgba(15, 23, 42, 0.28);
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
            padding: 14px 16px;
            border-bottom: 1px solid #d7e8dc;
            background: #fbfefd;
        }

        .application-details-header h2 {
            margin: 0;
            font-size: 1.1rem;
            color: #0f172a;
        }

        .filled-fields-hint {
            margin: 0;
            font-size: 0.72rem;
            color: #6b7280;
            line-height: 1.35;
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
            padding: 12px;
        }

        .application-modal-subtitle {
            font-size: 0.8rem;
            color: #6b7280;
            margin: 4px 0 0;
        }

        .application-modal-body {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        @media (max-width: 900px) {
            .application-details-dialog {
                width: calc(100vw - 14px);
                max-height: calc(100vh - 14px);
                margin: 7px;
            }
        }

        .detail-section {
            border: 1px solid #dbe7df;
            border-radius: 12px;
            background: #fdfefe;
            overflow: hidden;
        }

        .detail-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 11px 14px;
            background: linear-gradient(180deg, #f4faf6 0%, #edf6ef 100%);
            border-bottom: 1px solid #d7e8dc;
        }

        .detail-section-header h3 {
            margin: 0;
            font-size: 0.9rem;
            color: #1f3e2f;
        }

        .detail-section-header-copy {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .section-help {
            margin: 0;
            font-size: 0.72rem;
            color: #6b7280;
            line-height: 1.35;
        }

        .match-summary-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            white-space: nowrap;
            background: #ecfdf5;
            color: #16633f;
            border: 1px solid #bbe6c9;
        }

        .credential-match-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 12px;
            padding: 12px;
        }

        .credential-match-card {
            border: 1px solid #d9e4de;
            border-radius: 12px;
            background: #fff;
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 12px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.03);
        }

        .credential-match-card.credential-match-empty {
            grid-column: 1 / -1;
            flex-direction: row;
            align-items: center;
            gap: 12px;
            background: #f8fbf9;
        }

        .credential-match-empty-icon {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            background: #edf6ef;
            color: #1f3e2f;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            font-size: 1rem;
        }

        .credential-match-empty-title {
            font-weight: 700;
            color: #111827;
            margin-bottom: 2px;
        }

        .credential-match-empty-text {
            color: #6b7280;
            font-size: 0.82rem;
            line-height: 1.4;
        }

        .credential-match-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .credential-match-name {
            margin: 0;
            font-size: 0.92rem;
            font-weight: 700;
            color: #111827;
            word-break: break-word;
        }

        .credential-match-email {
            margin-top: 3px;
            font-size: 0.78rem;
            color: #64748b;
            word-break: break-word;
        }

        .credential-match-score {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 4px 8px;
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            white-space: nowrap;
            border: 1px solid transparent;
            line-height: 1;
        }

        .credential-match-score.exact {
            background: #e7f8ee;
            color: #0d6b37;
            border-color: #b7e6ca;
        }

        .credential-match-score.strong {
            background: #e8f1ff;
            color: #245ea8;
            border-color: #c6daf8;
        }

        .credential-match-score.medium {
            background: #fff7e0;
            color: #8d6400;
            border-color: #f1d187;
        }

        .credential-match-score.low {
            background: #f3f4f6;
            color: #4b5563;
            border-color: #d1d5db;
        }

        .credential-match-meta,
        .credential-match-comparison {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px 10px;
        }

        .credential-match-meta-item {
            border: 1px solid #e6eeea;
            border-radius: 10px;
            padding: 8px 9px;
            background: #fbfdfc;
            min-height: 54px;
        }

        .credential-match-meta-item .label {
            display: block;
            font-size: 0.66rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #728091;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .credential-match-meta-item .value {
            font-size: 0.8rem;
            color: #1f2937;
            line-height: 1.35;
            word-break: break-word;
        }

        .credential-match-note {
            padding: 8px 10px;
            border-radius: 10px;
            background: #f8fbf9;
            border: 1px solid #d8e7dd;
            color: #4b5563;
            font-size: 0.78rem;
            line-height: 1.35;
        }

        @media (max-width: 640px) {
            .credential-match-meta,
            .credential-match-comparison {
                grid-template-columns: 1fr;
            }
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 10px;
            padding: 12px;
        }

        .detail-item {
            border: 1px solid #e6eeea;
            border-radius: 10px;
            padding: 9px 10px;
            background: #fff;
            min-height: 62px;
        }

        .detail-item .label {
            display: block;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #728091;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .detail-input {
            width: 100%;
            border: 1px solid #d3e1d8;
            border-radius: 8px;
            background: #f8fbfa;
            color: #1f2937;
            padding: 8px 9px;
            font-size: 0.82rem;
            line-height: 1.35;
        }

        textarea.detail-input {
            min-height: 76px;
            resize: vertical;
            font-family: 'Sora', sans-serif;
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
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 12px;
            padding: 12px;
        }

        .document-card {
            border: 1px solid #d9e3eb;
            border-radius: 12px;
            background: #fff;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-height: 280px;
        }

        .document-preview {
            background: #f6f8fb;
            min-height: 190px;
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
            min-height: 220px;
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
            padding: 10px 12px;
            display: grid;
            gap: 5px;
        }

        .document-meta .doc-name {
            font-size: 0.84rem;
            color: #111827;
            font-weight: 600;
            word-break: break-word;
        }

        .document-meta .doc-info {
            font-size: 0.76rem;
            color: #6b7280;
        }

        .document-meta .doc-link {
            font-size: 0.78rem;
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
    </style>
</head>
<body class="clients-page">
<?php if (!$isHeadOfficeUser): ?>
    <main class="denied-shell">
        <section class="denied-card">
            <i class="bi bi-shield-lock"></i>
            <h1>Access Restricted</h1>
            <p><?php echo htmlspecialchars($approvalAccessLabel); ?> are visible only to Head Office and equivalent accounts.</p>
            <a href="dashboard.php">Return to Dashboard</a>
        </section>
    </main>
<?php else: ?>

<?php
$activePage = $approvalQueue === 'agent' ? 'agents-approval' : 'client-approvals';
include '../includes/sidebar.php';
?>

<div class="main">
    <header class="topbar">
        <div class="topbar-left">
            <h1><?php echo htmlspecialchars($approvalPageTitle); ?></h1>
            <div class="breadcrumb-trail">
                <i class="bi bi-house" style="font-size:.65rem;"></i>
                Dashboard &rsaquo; <span><?php echo htmlspecialchars($approvalBreadcrumbTitle); ?></span>
            </div>
        </div>
        <div class="topbar-right">
            <button type="button" class="btn-delete-selected" id="deleteSelectedApprovalsBtn" disabled>
                <i class="bi bi-trash"></i> Delete Selected
            </button>
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
                        <th class="col-checkbox"><input type="checkbox" id="selectAll"></th>
                        <th class="col-ref">Ref Code</th>
                        <th class="col-name">Name</th>
                        <th class="col-type">Class</th>
                        <th class="col-type">Type</th>
                        <th class="col-branch">Branch</th>
                        <th class="col-submitted-by">Submitted By</th>
                        <th class="col-status">Status</th>
                        <th class="col-submitted-at">Submitted At</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="approvalsTableBody">
                    <tr>
                        <td colspan="10" style="text-align:center; padding:20px;">Loading approvals...</td>
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
                <p id="applicationModalSubtitle" class="application-modal-subtitle">Select a row to compare closest client matches and review filled fields only.</p>
            </div>
            <button type="button" class="btn-cancel" id="applicationDetailsClearBtn">Close</button>
        </div>

        <div class="application-details-scroll">
            <div id="applicationDetailsEmpty" class="application-details-empty">
                Choose an approval row from the table to compare submitted names against client records, review only the populated fields, and inspect documents.
            </div>

            <div id="applicationDetailsContent" class="application-modal-body" hidden>
                <section class="detail-section">
                    <div class="detail-section-header">
                        <div class="detail-section-header-copy">
                            <h3>Closest Client Matches</h3>
                            <p class="section-help">Top name-based matches from the clients table appear first so the reviewer can compare possible duplicates.</p>
                        </div>
                        <span id="matchingCredentialsSummary" class="match-summary-badge">Searching...</span>
                    </div>
                    <div id="matchingCredentialsGrid" class="credential-match-grid"></div>
                </section>

                <section class="detail-section">
                    <div class="detail-section-header">
                        <h3>Approval Summary</h3>
                    </div>
                    <div id="approvalDetailsGrid" class="detail-grid"></div>
                </section>

                <section class="detail-section">
                    <div class="detail-section-header">
                        <div class="detail-section-header-copy">
                            <h3>Filled Submission Fields</h3>
                            <p class="filled-fields-hint">Only fields with values are shown here from the approval, client, and KYC records.</p>
                        </div>
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

<script src="../../public/js/dialog-modal.js"></script>

<script>
    const approvalQueue = <?php echo json_encode($approvalQueue); ?>;
    const approvalDefaultClassification = <?php echo json_encode($approvalDefaultClassification); ?>;
    let currentPage = 1;
    let totalPages = 1;
    const pageSize = 8;
    const APPROVALS_AUTO_REFRESH_MS = 12000;
    const OFFICER_RESUBMITTED_JUST_NOW_MS = 5 * 60 * 1000;
    let searchDebounceTimer;
    let approvalsRefreshTimer = null;
    let approvalsRequestInFlight = false;
    let lastOfficerUpdateSignature = '';
    let officerUpdateSignatureInitialized = false;
    let currentOpenApprovalId = 0;
    let currentOpenApprovalStatus = '';
    let detailsActionsBusy = false;

    const applicationDetailsPanel = document.getElementById('applicationDetailsPanel');
    const applicationDetailsBackdrop = document.getElementById('applicationDetailsBackdrop');
    const applicationDetailsContent = document.getElementById('applicationDetailsContent');
    const applicationDetailsEmpty = document.getElementById('applicationDetailsEmpty');
    const applicationDetailsClearBtn = document.getElementById('applicationDetailsClearBtn');
    const applicationModalTitle = document.getElementById('applicationModalTitle');
    const applicationModalSubtitle = document.getElementById('applicationModalSubtitle');
    const approvalDetailsGrid = document.getElementById('approvalDetailsGrid');
    const matchingCredentialsSummary = document.getElementById('matchingCredentialsSummary');
    const matchingCredentialsGrid = document.getElementById('matchingCredentialsGrid');
    const allSubmittedDetailsGrid = document.getElementById('allSubmittedDetailsGrid');
    const applicationDocumentGrid = document.getElementById('applicationDocumentGrid');
    const decisionReviewNotes = document.getElementById('decisionReviewNotes');
    const modalApproveBtn = document.getElementById('modalApproveBtn');
    const modalDeclineBtn = document.getElementById('modalDeclineBtn');
    const modalResubmitBtn = document.getElementById('modalResubmitBtn');
    const modalActionButtons = [modalApproveBtn, modalDeclineBtn, modalResubmitBtn].filter(Boolean);
    const hasApprovalCheckboxes = true;
    const approvalTableColumnCount = 10;
    const selectedApprovalIds = new Set();
    const selectedApprovalRows = new Map();
    const deleteSelectedApprovalsBtn = document.getElementById('deleteSelectedApprovalsBtn');
    let totalApprovals = 0;

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

        const parts = text.split(/\s+/).filter(Boolean);
        if (parts.length > 1 && parts[0].toLowerCase() === parts[1].toLowerCase()) {
            parts.shift();
        }

        const titleCased = parts.join(' ').replace(/\b\w/g, char => char.toUpperCase());

        return titleCased
            .replace(/\bKyc\b/g, 'KYC')
            .replace(/\bTin\b/g, 'TIN')
            .replace(/\bId\b/g, 'ID');
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
            <article class="detail-item">
                <label class="label">Info</label>
                <textarea class="detail-input" rows="3" readonly>${escapeHtml(message || 'No data available')}</textarea>
            </article>
        `;
    }

    function renderRecordGrid(container, record, priorityKeys = []) {
        if (!container) return;

        if (!record || typeof record !== 'object') {
            renderFallbackGrid(container, 'No filled fields available');
            return;
        }

        const entries = orderedRecordEntries(record, priorityKeys)
            .filter(([key, rawValue]) => String(key || '').toLowerCase() !== 'password' && !isBlank(rawValue));

        if (entries.length === 0) {
            renderFallbackGrid(container, 'No filled fields available');
            return;
        }

        const html = entries.map(([key, rawValue], index) => {
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
            const keyId = String(key || 'field').toLowerCase().replace(/[^a-z0-9_]+/g, '_');
            const inputId = `${container.id}_${keyId}_${index}`;
            const useTextarea = displayValue.length > 80 || displayValue.includes('\n');
            const control = useTextarea
                ? `<textarea id="${escapeHtml(inputId)}" class="detail-input" rows="3" readonly>${escapeHtml(displayValue)}</textarea>`
                : `<input id="${escapeHtml(inputId)}" class="detail-input" type="text" value="${escapeHtml(displayValue)}" readonly>`;

            return `
                <article class="detail-item">
                    <label class="label" for="${escapeHtml(inputId)}">${escapeHtml(humanizeKey(key))}</label>
                    ${control}
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

    function credentialMatchScoreClass(score) {
        const numericScore = Number(score || 0);

        if (numericScore >= 100) {
            return 'exact';
        }

        if (numericScore >= 85) {
            return 'strong';
        }

        if (numericScore >= 60) {
            return 'medium';
        }

        return 'low';
    }

    function credentialMatchMethodLabel(method) {
        switch (String(method || '').toLowerCase()) {
            case 'exact_name':
                return 'Exact name';
            case 'contains_name':
                return 'Name overlap';
            case 'exact_email':
                return 'Exact email';
            case 'similar_name':
                return 'Name similarity';
            default:
                return 'Potential match';
        }
    }

    function renderCredentialMatches(container, matches, options = {}) {
        if (!container) return;

        const config = typeof options === 'string' ? { emptyMessage: options } : (options || {});
        const items = Array.isArray(matches) ? matches : [];
        if (items.length === 0) {
            const isLoading = Boolean(config.loading);
            const isError = Boolean(config.error);
            const emptyMessage = config.emptyMessage || 'No registered client records match the submitted names.';
            const emptyTitle = isLoading
                ? 'Searching client records'
                : (isError ? 'Unable to load client matches' : 'No close matches found');
            const emptyIcon = isLoading
                ? 'bi-hourglass-split'
                : (isError ? 'bi-exclamation-circle' : 'bi-person-badge');

            container.innerHTML = `
                <article class="credential-match-card credential-match-empty">
                    <div class="credential-match-empty-icon">
                        <i class="bi ${emptyIcon}"></i>
                    </div>
                    <div>
                        <div class="credential-match-empty-title">${escapeHtml(emptyTitle)}</div>
                        <div class="credential-match-empty-text">${escapeHtml(emptyMessage)}</div>
                    </div>
                </article>
            `;
            return;
        }

        container.innerHTML = items.map(match => {
            const score = Number(match.match_score || 0);
            const scoreClass = credentialMatchScoreClass(score);
            const displayName = isBlank(match.display_name) ? 'Unnamed client' : String(match.display_name);
            const email = isBlank(match.email) ? 'N/A' : String(match.email);
            const clientId = isBlank(match.client_id) ? 'N/A' : String(match.client_id);
            const clientNumber = isBlank(match.client_number) ? 'N/A' : String(match.client_number);
            const referenceCode = isBlank(match.reference_code) ? 'N/A' : String(match.reference_code);
            const clientType = isBlank(match.client_type) ? 'N/A' : String(match.client_type);
            const clientClassification = isBlank(match.client_classification) ? 'N/A' : String(match.client_classification);
            const verificationStatus = isBlank(match.verification_status) ? 'N/A' : String(match.verification_status);
            const mobilePhone = isBlank(match.mobile_phone) ? 'N/A' : String(match.mobile_phone);
            const officePhone = isBlank(match.office_phone) ? 'N/A' : String(match.office_phone);
            const contactPerson = isBlank(match.contact_person) ? 'N/A' : String(match.contact_person);
            const companyName = isBlank(match.company_name) ? 'N/A' : String(match.company_name);
            const matchedSourceLabel = isBlank(match.matched_source_label) ? 'Submitted name' : String(match.matched_source_label);
            const matchedSourceValue = isBlank(match.matched_source_value) ? 'N/A' : String(match.matched_source_value);
            const matchReason = isBlank(match.match_reason)
                ? credentialMatchMethodLabel(match.match_method)
                : String(match.match_reason);

            return `
                <article class="credential-match-card">
                    <div class="credential-match-head">
                        <div>
                            <div class="credential-match-name">${escapeHtml(displayName)}</div>
                            <div class="credential-match-email">${escapeHtml(email)}</div>
                        </div>
                        <span class="credential-match-score ${scoreClass}">${escapeHtml(String(Math.round(score)))}%</span>
                    </div>

                    <div class="credential-match-meta">
                        <div class="credential-match-meta-item">
                            <span class="label">Client ID</span>
                            <div class="value">${escapeHtml(clientId)}</div>
                        </div>
                        <div class="credential-match-meta-item">
                            <span class="label">Client Number</span>
                            <div class="value">${escapeHtml(clientNumber)}</div>
                        </div>
                        <div class="credential-match-meta-item">
                            <span class="label">Reference Code</span>
                            <div class="value">${escapeHtml(referenceCode)}</div>
                        </div>
                        <div class="credential-match-meta-item">
                            <span class="label">Client Type</span>
                            <div class="value">${escapeHtml(clientType)}</div>
                        </div>
                        <div class="credential-match-meta-item">
                            <span class="label">Classification</span>
                            <div class="value">${escapeHtml(clientClassification)}</div>
                        </div>
                        <div class="credential-match-meta-item">
                            <span class="label">Verification Status</span>
                            <div class="value">${escapeHtml(verificationStatus)}</div>
                        </div>
                        <div class="credential-match-meta-item">
                            <span class="label">Mobile Phone</span>
                            <div class="value">${escapeHtml(mobilePhone)}</div>
                        </div>
                        <div class="credential-match-meta-item">
                            <span class="label">Office Phone</span>
                            <div class="value">${escapeHtml(officePhone)}</div>
                        </div>
                        <div class="credential-match-meta-item">
                            <span class="label">Contact Person</span>
                            <div class="value">${escapeHtml(contactPerson)}</div>
                        </div>
                        <div class="credential-match-meta-item">
                            <span class="label">Company Name</span>
                            <div class="value">${escapeHtml(companyName)}</div>
                        </div>
                    </div>

                    <div class="credential-match-comparison">
                        <div class="credential-match-meta-item">
                            <span class="label">Compared against</span>
                            <div class="value">${escapeHtml(matchedSourceLabel)}</div>
                        </div>
                        <div class="credential-match-meta-item">
                            <span class="label">Matched value</span>
                            <div class="value">${escapeHtml(matchedSourceValue)}</div>
                        </div>
                    </div>

                    <div class="credential-match-note">${escapeHtml(matchReason)}</div>
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

    function formatDateOnly(value) {
        if (!value) return 'N/A';
        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return value;
        return date.toLocaleDateString();
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

    function classificationBadgeClass(value) {
        const normalized = String(value || '').toLowerCase();
        if (normalized === 'client') return 'approval-pill-client';
        if (normalized === 'agent') return 'approval-pill-agent';
        return 'approval-pill-muted';
    }

    function typeBadgeClass(value) {
        const normalized = String(value || '').toLowerCase();
        if (normalized === 'individual') return 'approval-pill-individual';
        if (normalized === 'corporate') return 'approval-pill-corporate';
        if (normalized === 'obligee') return 'approval-pill-obligee';
        return 'approval-pill-muted';
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

    function isFinalApprovalStatus(status) {
        const normalized = String(status || '').toLowerCase();
        return normalized === 'approved' || normalized === 'declined';
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
        const isLockedStatus = isFinalApprovalStatus(currentOpenApprovalStatus);

        modalActionButtons.forEach(button => {
            const action = String(button.dataset.action || '').toLowerCase();
            const disableByStatus = !isLockedStatus && currentOpenApprovalStatus !== '' && action === currentOpenApprovalStatus;
            button.disabled = detailsActionsBusy || currentOpenApprovalId <= 0 || isLockedStatus || disableByStatus;
            button.setAttribute('aria-disabled', button.disabled ? 'true' : 'false');
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

    function setApprovalDeleteButtonBusy(button, isBusy, busyText = 'Working...') {
        if (!button) return;

        if (isBusy) {
            button.dataset.originalHtml = button.innerHTML;
            button.innerHTML = `<span class="spinner" style="width:14px;height:14px;"></span> ${busyText}`;
            button.disabled = true;
            return;
        }

        button.disabled = false;
        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
            delete button.dataset.originalHtml;
        }
    }

    function updateApprovalBulkDeleteButtonState() {
        if (!deleteSelectedApprovalsBtn || !hasApprovalCheckboxes) {
            return;
        }

        deleteSelectedApprovalsBtn.disabled = selectedApprovalIds.size === 0;
    }

    function syncApprovalSelectAllCheckbox() {
        if (!hasApprovalCheckboxes) {
            return;
        }

        const selectAll = document.getElementById('selectAll');
        if (!selectAll) {
            return;
        }

        const rowCheckboxes = document.querySelectorAll('#approvalsTableBody .row-select');
        const totalVisible = rowCheckboxes.length;
        const checkedVisible = Array.from(rowCheckboxes).filter(cb => cb.checked).length;

        if (totalVisible === 0) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
            return;
        }

        selectAll.checked = checkedVisible === totalVisible;
        selectAll.indeterminate = checkedVisible > 0 && checkedVisible < totalVisible;
    }

    function updateApprovalSelection(approvalId, isSelected, rowData) {
        if (!hasApprovalCheckboxes) {
            return;
        }

        const id = String(approvalId || '');
        if (!id) {
            return;
        }

        if (isSelected) {
            selectedApprovalIds.add(id);
            if (rowData) {
                selectedApprovalRows.set(id, rowData);
            }
        } else {
            selectedApprovalIds.delete(id);
        }

        updateApprovalBulkDeleteButtonState();
    }

    function deleteApprovalRecord(approvalId) {
        const formData = new FormData();
        formData.append('action', 'delete_approval_record');
        formData.append('approval_id', String(approvalId));
        formData.append('queue', approvalQueue);

        return fetch('../handlers/client_approvals.php', {
            method: 'POST',
            credentials: 'include',
            body: formData
        }).then(response => response.json());
    }

    async function deleteSelectedApprovals() {
        if (!hasApprovalCheckboxes) {
            return;
        }

        const selectedIds = Array.from(selectedApprovalIds);
        if (selectedIds.length === 0) {
            createToast('info', 'Nothing Selected', approvalQueue === 'agent'
                ? 'Select one or more agent approvals first.'
                : 'Select one or more client approvals first.');
            return;
        }

        setApprovalDeleteButtonBusy(deleteSelectedApprovalsBtn, true, 'Deleting...');

        let successCount = 0;
        let failureCount = 0;
        let currentOpenApprovalDeleted = false;

        try {
            for (const approvalId of selectedIds) {
                try {
                    const payload = await deleteApprovalRecord(approvalId);
                    if (payload.success) {
                        successCount += 1;
                        selectedApprovalIds.delete(String(approvalId));
                        selectedApprovalRows.delete(String(approvalId));
                        if (Number(approvalId) === currentOpenApprovalId) {
                            currentOpenApprovalDeleted = true;
                        }
                    } else {
                        failureCount += 1;
                    }
                } catch (error) {
                    failureCount += 1;
                }
            }

            updateApprovalBulkDeleteButtonState();

            if (currentOpenApprovalDeleted) {
                closeApplicationModal();
            }

            if (successCount > 0) {
                const remainingTotal = Math.max(0, totalApprovals - successCount);
                const maxPageAfterDelete = Math.max(1, Math.ceil(remainingTotal / pageSize));
                const targetPage = Math.min(currentPage, maxPageAfterDelete);
                createToast('success', 'Deleted', `${successCount} selected ${selectionLabel}${successCount === 1 ? '' : 's'} deleted.`);
                loadApprovals(targetPage);
            }

            if (failureCount > 0) {
                createToast('error', 'Delete Failed', `${failureCount} selected approval${failureCount === 1 ? '' : 's'} could not be deleted.`);
            }
        } finally {
            setApprovalDeleteButtonBusy(deleteSelectedApprovalsBtn, false);
        }
    }

    if (deleteSelectedApprovalsBtn) {
        deleteSelectedApprovalsBtn.addEventListener('click', deleteSelectedApprovals);
    }

    if (hasApprovalCheckboxes) {
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('#approvalsTableBody .row-select').forEach(checkbox => {
                    checkbox.checked = this.checked;
                    updateApprovalSelection(checkbox.dataset.approvalId, checkbox.checked, selectedApprovalRows.get(String(checkbox.dataset.approvalId)) || null);
                });

                this.indeterminate = false;
                updateApprovalBulkDeleteButtonState();
            });
        }
    }

    function renderTable(rows) {
        const tbody = document.getElementById('approvalsTableBody');
        if (!tbody) return;

        if (!Array.isArray(rows) || rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="${approvalTableColumnCount}" style="text-align:center; padding: 22px;">No approval records found</td></tr>`;
            return;
        }

        tbody.innerHTML = '';

        rows.forEach(row => {
            const tr = document.createElement('tr');
            const clientName = resolveClientName(row);
            const status = String(row.approval_status || 'pending').toLowerCase();
            const approvalId = Number(row.approval_id || 0);
            const isLockedStatus = isFinalApprovalStatus(status);
            const officerUpdated = hasOfficerUpdates(row);
            const officerResubmittedJustNow = officerUpdated && isOfficerResubmittedJustNow(row.officer_resubmitted_at);
            const classificationValue = formatClassification(row.client_classification);
            const typeValue = formatType(row.client_type);
            const rowId = String(approvalId || '');
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
            if (hasApprovalCheckboxes && selectedApprovalIds.has(rowId)) {
                tr.classList.add('is-checked');
            }
            tr.dataset.approvalId = String(approvalId);
            tr.dataset.clientId = String(row.client_id || 0);
            selectedApprovalRows.set(rowId, row);

            tr.innerHTML = `
                ${hasApprovalCheckboxes ? `<td class="col-checkbox"><input type="checkbox" class="row-select" data-approval-id="${approvalId}" data-client-id="${escapeHtml(String(row.client_id || 0))}"></td>` : ''}
                <td class="col-ref">
                    <span class="ref-badge">${escapeHtml(row.reference_code || 'N/A')}</span>
                </td>
                <td class="col-name">${escapeHtml(clientName)}</td>
                <td class="col-type"><span class="approval-pill ${classificationBadgeClass(row.client_classification)}">${escapeHtml(classificationValue)}</span></td>
                <td class="col-type"><span class="approval-pill ${typeBadgeClass(row.client_type)}">${escapeHtml(typeValue)}</span></td>
                <td class="col-branch">${escapeHtml(row.submitted_by_branch || 'N/A')}</td>
                <td class="col-submitted-by">${escapeHtml(row.submitted_by_name || 'N/A')}</td>
                <td class="col-status">${statusHtml}</td>
                <td class="col-submitted-at">${escapeHtml(formatDateOnly(row.submitted_at))}</td>
                <td class="col-actions">
                    <div class="action-stack">
                        <button type="button" class="action-icon action-approve" data-action="approve" data-id="${approvalId}" ${isLockedStatus ? 'disabled aria-disabled="true" title="Action locked after final decision"' : ''}><i class="bi bi-check2-circle"></i>Approve</button>
                        <button type="button" class="action-icon action-decline" data-action="decline" data-id="${approvalId}" ${isLockedStatus ? 'disabled aria-disabled="true" title="Action locked after final decision"' : ''}><i class="bi bi-x-circle"></i>Decline</button>
                        <button type="button" class="action-icon action-resubmit" data-action="resubmit" data-id="${approvalId}" ${isLockedStatus ? 'disabled aria-disabled="true" title="Action locked after final decision"' : ''}><i class="bi bi-arrow-repeat"></i>Resubmit</button>
                    </div>
                </td>
            `;

            if (hasApprovalCheckboxes) {
                const rowCheckbox = tr.querySelector('.row-select');
                if (rowCheckbox) {
                    rowCheckbox.checked = selectedApprovalIds.has(rowId);
                    rowCheckbox.addEventListener('click', event => event.stopPropagation());
                    rowCheckbox.addEventListener('change', function () {
                        updateApprovalSelection(this.dataset.approvalId, this.checked, row);
                        syncApprovalSelectAllCheckbox();
                    });
                }
            }

            tbody.appendChild(tr);
        });

        syncApprovalSelectAllCheckbox();
        updateApprovalBulkDeleteButtonState();
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
            applicationModalSubtitle.textContent = 'Loading application details, closest client matches, and filled fields...';
        }

        if (matchingCredentialsSummary) {
            matchingCredentialsSummary.textContent = 'Searching client records...';
        }

        renderCredentialMatches(matchingCredentialsGrid, null, {
            loading: true,
            emptyMessage: 'Searching client records for the closest name match...'
        });

        renderFallbackGrid(approvalDetailsGrid, 'Loading approval summary...');
        renderFallbackGrid(allSubmittedDetailsGrid, 'Loading filled fields...');

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
            applicationModalSubtitle.textContent = 'Select a row to compare closest client matches and review filled fields only.';
        }

        if (applicationDetailsEmpty) {
            applicationDetailsEmpty.textContent = 'Choose an approval row from the table to compare submitted names against client records, review only the populated fields, and inspect documents.';
        }

        if (matchingCredentialsSummary) {
            matchingCredentialsSummary.textContent = 'No application loaded';
        }
    }

    function renderApplicationDetails(payload) {
        const approval = payload.approval || null;
        const client = payload.client || null;
        const kyc = payload.kyc || null;
        const allSubmittedData = payload.all_submitted_data || null;
        const documents = Array.isArray(payload.documents) ? payload.documents : [];
        const matchingCredentialsPayload = payload.matching_credentials || {};
        const matchingCredentials = Array.isArray(matchingCredentialsPayload.items)
            ? matchingCredentialsPayload.items
            : [];

        const referenceCode = approval && approval.reference_code ? approval.reference_code : 'N/A';
        const status = approval && approval.approval_status ? String(approval.approval_status).toUpperCase() : 'N/A';
        currentOpenApprovalStatus = approval && approval.approval_status
            ? String(approval.approval_status).toLowerCase()
            : '';
        setDetailsPanelVisibility(true);
        highlightSelectedApprovalRow();
        setModalActionsBusy(false);

        renderCredentialMatches(
            matchingCredentialsGrid,
            matchingCredentials,
            'No registered client records closely match the submitted names for this application.'
        );

        if (matchingCredentialsSummary) {
            const topMatch = matchingCredentials[0] || null;
            matchingCredentialsSummary.textContent = topMatch
                ? `Top client match: ${String(topMatch.display_name || 'Unknown')} (${Math.round(Number(topMatch.match_score || 0))}%)`
                : 'No matching client records found';
        }

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
            'agent_type',
            'head_agent_name',
            'agent_branch',
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

        renderRecordGrid(allSubmittedDetailsGrid, allSubmittedData, [
            'approval_reference_code',
            'approval_client_type',
            'approval_client_classification',
            'approval_agent_type',
            'approval_head_agent_name',
            'approval_agent_branch',
            'approval_approval_status',
            'approval_submitted_by_name',
            'approval_submitted_by_branch',
            'approval_submitted_at',
            'client_reference_code',
            'client_client_type',
            'client_client_classification',
            'client_agent_type',
            'client_head_agent_name',
            'client_agent_branch',
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
            'kyc_agent_type',
            'kyc_head_agent_name',
            'kyc_agent_branch',
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
            approval_id: String(parsedApprovalId),
            queue: approvalQueue
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
                renderFallbackGrid(allSubmittedDetailsGrid, 'Filled fields are unavailable.');

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

                renderCredentialMatches(
                    matchingCredentialsGrid,
                    null,
                    {
                        error: true,
                        emptyMessage: 'Unable to load client matches for this application.'
                    }
                );

                if (matchingCredentialsSummary) {
                    matchingCredentialsSummary.textContent = 'Unavailable';
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
                if (event.target.closest('.action-stack, .row-select, .col-checkbox')) {
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
            queue: approvalQueue,
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
                totalApprovals = Number(payload.total || 0);

                updateBranchFilterOptions(payload.availableBranches || []);
                renderTable(payload.data || []);
                notifyOfficerResubmissions(payload.data || []);
                updatePaginationInfo(payload);
                renderPagination(payload);
            })
            .catch(error => {
                const tbody = document.getElementById('approvalsTableBody');
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="${approvalTableColumnCount}" style="text-align:center; color:#b42318; padding: 22px;">${escapeHtml(error.message || 'Failed to load approvals')}</td></tr>`;
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

    async function runAction(approvalId, action, options = {}) {
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
            const promptedNote = await showPromptModal({
                title: `${actionTitle} Review Note`,
                message: notePrompt,
                promptLabel: 'Review note',
                promptPlaceholder: 'Enter a note or reason',
                defaultValue: '',
                confirmText: 'Continue',
                cancelText: 'Cancel',
                variant: action === 'decline' ? 'danger' : 'success'
            });
            if (promptedNote === null) {
                return;
            }
            reviewNote = promptedNote;
        }

        if (action !== 'approve') {
            const confirmed = await showConfirmModal({
                title: `Confirm ${actionTitle}`,
                message: `Confirm ${actionTitle.toLowerCase()} for approval #${approvalId}?`,
                confirmText: actionTitle,
                cancelText: 'Cancel',
                variant: action === 'decline' ? 'danger' : 'success'
            });
            if (!confirmed) {
                return;
            }
        }

        if (source === 'details') {
            setModalActionsBusy(true);
        }

        const formData = new FormData();
        formData.append('action', action);
        formData.append('approval_id', String(approvalId));
        formData.append('review_notes', reviewNote);
        formData.append('queue', approvalQueue);

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

    function attachActionHandlers() {
        document.querySelectorAll('#approvalsTableBody .action-icon[data-action]').forEach(button => {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                const approvalId = Number(this.dataset.id || 0);
                const action = this.dataset.action || '';
                if (!approvalId || !action) return;
                void runAction(approvalId, action);
            });
        });
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

                void runAction(currentOpenApprovalId, action, { source: 'details' });
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
        const classificationFilter = document.getElementById('filterClassification');
        if (classificationFilter) {
            classificationFilter.value = approvalDefaultClassification;
        }
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
