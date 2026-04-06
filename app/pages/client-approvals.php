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

        #approvalsTableBody tr.approval-row {
            cursor: pointer;
        }

        #approvalsTableBody tr.approval-row td {
            transition: background-color 0.18s ease;
        }

        #approvalsTableBody tr.approval-row:hover td {
            background-color: #f5fbf8;
        }

        .application-modal-content {
            width: min(1120px, calc(100vw - 32px));
            height: min(92vh, calc(100vh - 32px));
            max-height: calc(100vh - 16px);
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 24px;
            top: 24px;
            margin: 0;
            transform: none;
            transition: box-shadow 0.18s ease;
            overflow: hidden;
        }

        #applicationModalHeader {
            cursor: move;
            user-select: none;
        }

        body.application-modal-dragging {
            user-select: none;
            cursor: grabbing;
        }

        body.application-modal-dragging #applicationModalHeader {
            cursor: grabbing;
        }

        .application-modal-subtitle {
            font-size: 0.8rem;
            color: #6b7280;
            margin: 4px 0 0;
        }

        .application-modal-body {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            gap: 16px;
            overflow-y: auto;
            overscroll-behavior: contain;
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

        .detail-item .value {
            font-size: 0.84rem;
            color: #1f2937;
            line-height: 1.35;
            word-break: break-word;
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
    <header class="topbar">
        <div class="topbar-left">
            <h1>Client Approvals</h1>
            <div class="breadcrumb-trail">
                <i class="bi bi-house" style="font-size:.65rem;"></i>
                Dashboard &rsaquo; <span>Client Approvals</span>
            </div>
        </div>
        <div class="topbar-right"></div>
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
                        <option value="pending" selected>Pending</option>
                        <option value="approved">Approved</option>
                        <option value="declined">Declined</option>
                        <option value="resubmit">Resubmit</option>
                        <option value="">All Statuses</option>
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
                        <th class="col-name">Name</th>
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

<div id="applicationModal" class="modal" aria-hidden="true">
    <div class="modal-content application-modal-content">
        <div class="modal-header" id="applicationModalHeader">
            <div>
                <h2 id="applicationModalTitle">Application Details</h2>
                <p id="applicationModalSubtitle" class="application-modal-subtitle">Loading application details...</p>
            </div>
            <button type="button" class="modal-close" id="applicationModalCloseBtn" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="modal-body application-modal-body">
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
                    <h3>KYC Submission Data (All Fields)</h3>
                </div>
                <div id="allSubmittedDetailsGrid" class="detail-grid"></div>
            </section>

            <section class="detail-section">
                <div class="detail-section-header">
                    <h3>Uploaded Documents</h3>
                </div>
                <div id="applicationDocumentGrid" class="document-grid"></div>
            </section>
        </div>

        <div class="modal-footer application-modal-footer">
            <div class="application-modal-actions">
                <button type="button" class="action-icon action-approve" id="modalApproveBtn" data-action="approve">
                    <i class="bi bi-check2-circle"></i>Approve
                </button>
                <button type="button" class="action-icon action-decline" id="modalDeclineBtn" data-action="decline">
                    <i class="bi bi-x-circle"></i>Decline
                </button>
                <button type="button" class="action-icon action-resubmit" id="modalResubmitBtn" data-action="resubmit">
                    <i class="bi bi-arrow-repeat"></i>Resubmit
                </button>
            </div>
            <button type="button" class="btn-cancel" id="applicationModalCloseFooterBtn">Close</button>
        </div>
    </div>
</div>

<div id="toastContainer" class="toast-container"></div>

<script>
    let currentPage = 1;
    let totalPages = 1;
    const pageSize = 10;
    let searchDebounceTimer;
    let currentOpenApprovalId = 0;
    let currentOpenApprovalStatus = '';
    let modalActionsBusy = false;

    const applicationModal = document.getElementById('applicationModal');
    const applicationModalTitle = document.getElementById('applicationModalTitle');
    const applicationModalSubtitle = document.getElementById('applicationModalSubtitle');
    const approvalDetailsGrid = document.getElementById('approvalDetailsGrid');
    const clientDetailsGrid = document.getElementById('clientDetailsGrid');
    const kycDetailsGrid = document.getElementById('kycDetailsGrid');
    const allSubmittedDetailsGrid = document.getElementById('allSubmittedDetailsGrid');
    const applicationDocumentGrid = document.getElementById('applicationDocumentGrid');
    const applicationModalContent = applicationModal ? applicationModal.querySelector('.application-modal-content') : null;
    const applicationModalHeader = document.getElementById('applicationModalHeader');
    const modalApproveBtn = document.getElementById('modalApproveBtn');
    const modalDeclineBtn = document.getElementById('modalDeclineBtn');
    const modalResubmitBtn = document.getElementById('modalResubmitBtn');
    const modalActionButtons = [modalApproveBtn, modalDeclineBtn, modalResubmitBtn].filter(Boolean);

    const modalDragState = {
        active: false,
        startX: 0,
        startY: 0,
        startLeft: 0,
        startTop: 0
    };

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
            <article class="detail-item">
                <span class="label">Info</span>
                <span class="value">${escapeHtml(message || 'No data available')}</span>
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

            return `
                <article class="detail-item">
                    <span class="label">${escapeHtml(humanizeKey(key))}</span>
                    <span class="value">${escapeHtml(displayValue)}</span>
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

    function refreshModalActionButtons() {
        modalActionButtons.forEach(button => {
            const action = String(button.dataset.action || '').toLowerCase();
            const disableByStatus = currentOpenApprovalStatus !== '' && action === currentOpenApprovalStatus;
            button.disabled = modalActionsBusy || currentOpenApprovalId <= 0 || disableByStatus;
        });
    }

    function setModalActionsBusy(isBusy) {
        modalActionsBusy = Boolean(isBusy);
        refreshModalActionButtons();
    }

    function clampModalPosition(left, top) {
        if (!applicationModalContent) {
            return { left, top };
        }

        const padding = 10;
        const width = applicationModalContent.offsetWidth || 0;
        const height = applicationModalContent.offsetHeight || 0;

        const minLeft = padding;
        const minTop = padding;
        const maxLeft = Math.max(minLeft, window.innerWidth - width - padding);
        const maxTop = Math.max(minTop, window.innerHeight - height - padding);

        return {
            left: Math.min(Math.max(left, minLeft), maxLeft),
            top: Math.min(Math.max(top, minTop), maxTop)
        };
    }

    function setApplicationModalPosition(left, top) {
        if (!applicationModalContent) return;

        const clamped = clampModalPosition(Number(left || 0), Number(top || 0));
        applicationModalContent.style.left = `${clamped.left}px`;
        applicationModalContent.style.top = `${clamped.top}px`;
    }

    function centerApplicationModalContent() {
        if (!applicationModalContent) return;

        const width = applicationModalContent.offsetWidth || 980;
        const height = applicationModalContent.offsetHeight || 620;

        const centeredLeft = Math.max(10, Math.round((window.innerWidth - width) / 2));
        const centeredTop = Math.max(10, Math.round((window.innerHeight - height) / 2));

        setApplicationModalPosition(centeredLeft, centeredTop);
    }

    function beginApplicationModalDrag(event) {
        if (!applicationModalContent || !applicationModal) return;
        if (applicationModal.style.display !== 'block') return;
        if (event.button !== 0) return;
        if (event.target.closest('button, a, input, select, textarea, [data-no-drag]')) return;

        const rect = applicationModalContent.getBoundingClientRect();
        modalDragState.active = true;
        modalDragState.startX = event.clientX;
        modalDragState.startY = event.clientY;
        modalDragState.startLeft = rect.left;
        modalDragState.startTop = rect.top;

        document.body.classList.add('application-modal-dragging');
        event.preventDefault();
    }

    function onApplicationModalDrag(event) {
        if (!modalDragState.active) return;

        const deltaX = event.clientX - modalDragState.startX;
        const deltaY = event.clientY - modalDragState.startY;

        setApplicationModalPosition(modalDragState.startLeft + deltaX, modalDragState.startTop + deltaY);
    }

    function endApplicationModalDrag() {
        if (!modalDragState.active) return;

        modalDragState.active = false;
        document.body.classList.remove('application-modal-dragging');
    }

    function keepApplicationModalInViewport() {
        if (!applicationModalContent || !applicationModal || applicationModal.style.display !== 'block') {
            return;
        }

        const rect = applicationModalContent.getBoundingClientRect();
        setApplicationModalPosition(rect.left, rect.top);
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
            const status = String(row.approval_status || 'pending').toLowerCase();
            const approvalId = Number(row.approval_id || 0);

            tr.className = 'approval-row';
            tr.dataset.approvalId = String(approvalId);

            tr.innerHTML = `
                <td class="col-ref"><span class="ref-badge">${escapeHtml(row.reference_code || 'N/A')}</span></td>
                <td class="col-name">${escapeHtml(row.display_name || 'N/A')}</td>
                <td class="col-type">${escapeHtml(formatClassification(row.client_classification))}</td>
                <td class="col-type">${escapeHtml(formatType(row.client_type))}</td>
                <td class="col-contact">${escapeHtml(contact)}</td>
                <td class="col-email">${escapeHtml(row.email || 'N/A')}</td>
                <td class="col-owner">${escapeHtml(row.submitted_by_name || 'N/A')}</td>
                <td class="col-owner">${escapeHtml(row.submitted_by_branch || 'N/A')}</td>
                <td class="col-owner">${escapeHtml(formatDateTime(row.submitted_at))}</td>
                <td class="col-status"><span class="approval-status-badge ${statusBadgeClass(status)}">${escapeHtml(status)}</span></td>
                <td class="notes-cell">${escapeHtml(notes || '—')}</td>
                <td class="col-actions">
                    <div class="action-stack">
                        <button class="action-icon action-approve" data-action="approve" data-id="${approvalId}"><i class="bi bi-check2-circle"></i>Approve</button>
                        <button class="action-icon action-decline" data-action="decline" data-id="${approvalId}"><i class="bi bi-x-circle"></i>Decline</button>
                        <button class="action-icon action-resubmit" data-action="resubmit" data-id="${approvalId}"><i class="bi bi-arrow-repeat"></i>Resubmit</button>
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
        if (!applicationModal) return;

        currentOpenApprovalId = Number(approvalId || 0);
        currentOpenApprovalStatus = '';
        applicationModal.style.display = 'block';
        applicationModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        setModalActionsBusy(true);
        centerApplicationModalContent();

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
        if (!applicationModal) return;
        endApplicationModalDrag();
        applicationModal.style.display = 'none';
        applicationModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        currentOpenApprovalId = 0;
        currentOpenApprovalStatus = '';
        setModalActionsBusy(false);
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
        setModalActionsBusy(false);

        if (applicationModalTitle) {
            applicationModalTitle.textContent = `Application ${referenceCode}`;
        }

        if (applicationModalSubtitle) {
            const submittedBy = approval && approval.submitted_by_name ? approval.submitted_by_name : 'Unknown submitter';
            const submittedAt = approval ? formatDateTime(approval.submitted_at) : 'N/A';
            applicationModalSubtitle.textContent = `Status: ${status} | Submitted by ${submittedBy} | ${submittedAt}`;
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

    function loadApprovals(page = 1) {
        setTableLoading(true);

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
                setTableLoading(false);
            });
    }

    function getActionTitle(action) {
        if (action === 'approve') return 'Approve';
        if (action === 'decline') return 'Decline';
        return 'Resubmit';
    }

    function runAction(approvalId, action, options = {}) {
        const source = String(options.source || 'table');
        const actionTitle = getActionTitle(action);
        const notePrompt = action === 'approve'
            ? 'Optional note for approval:'
            : `Reason for ${actionTitle.toLowerCase()}:`;

        const reviewNote = window.prompt(notePrompt, '');
        if (reviewNote === null) {
            return;
        }

        const confirmed = window.confirm(`Confirm ${actionTitle.toLowerCase()} for approval #${approvalId}?`);
        if (!confirmed) {
            return;
        }

        if (source === 'modal') {
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

                if (source === 'modal' && currentOpenApprovalId === approvalId) {
                    openApplicationModal(approvalId);
                }
            })
            .catch(error => {
                createToast('error', 'Update Failed', error.message || `Unable to ${action} approval.`);

                if (source === 'modal') {
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
                runAction(approvalId, action);
            });
        });
    }

    function initializeApplicationModalEvents() {
        const closeBtn = document.getElementById('applicationModalCloseBtn');
        const footerCloseBtn = document.getElementById('applicationModalCloseFooterBtn');

        if (applicationModalHeader) {
            applicationModalHeader.addEventListener('mousedown', beginApplicationModalDrag);
        }

        document.addEventListener('mousemove', onApplicationModalDrag);
        document.addEventListener('mouseup', endApplicationModalDrag);
        window.addEventListener('resize', keepApplicationModalInViewport);

        if (closeBtn) {
            closeBtn.addEventListener('click', closeApplicationModal);
        }

        if (footerCloseBtn) {
            footerCloseBtn.addEventListener('click', closeApplicationModal);
        }

        modalActionButtons.forEach(button => {
            button.addEventListener('click', event => {
                event.preventDefault();

                const action = String(button.dataset.action || '').toLowerCase();
                if (!action || currentOpenApprovalId <= 0) {
                    return;
                }

                runAction(currentOpenApprovalId, action, { source: 'modal' });
            });
        });

        if (applicationModal) {
            applicationModal.addEventListener('click', event => {
                if (event.target === applicationModal) {
                    closeApplicationModal();
                }
            });
        }

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && applicationModal && applicationModal.style.display === 'block') {
                closeApplicationModal();
            }
        });

        refreshModalActionButtons();
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
    });
</script>
<?php endif; ?>
</body>
</html>
