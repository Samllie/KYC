<?php
require_once '../config/session.php';
requireLogin();

$currentUserRole = strtolower(trim($_SESSION['role'] ?? ''));
$currentUserRoleNormalized = str_replace('-', '_', $currentUserRole);
$currentUserDepartment = strtoupper(trim($_SESSION['department'] ?? ''));
$currentUserBranch = strtoupper(trim($_SESSION['branch'] ?? ''));
$isHeadOfficeUser = $currentUserRole === 'admin'
    || $currentUserDepartment === 'HEAD OFFICE'
    || in_array($currentUserBranch, ['HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH'], true);
$isKycOfficerUser = $currentUserRoleNormalized === 'kyc_officer' && !$isHeadOfficeUser;
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
        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 0.73rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff7e0;
            color: #8d6400;
        }

        .status-approved {
            background: #e7f8ee;
            color: #0d6b37;
        }

        .status-declined {
            background: #fde9e9;
            color: #a61d24;
        }

        .status-resubmit {
            background: #e8f1ff;
            color: #245ea8;
        }

        .remarks-cell {
            min-width: 340px;
            max-width: 560px;
            white-space: normal;
            color: #4b5563;
            font-size: 0.82rem;
            line-height: 1.35;
        }

        .remarks-header {
            min-width: 340px;
        }

        .review-meta-cell {
            min-width: 150px;
        }

        .action-icon.action-resubmit {
            background: #edf4ff;
            color: #245ea8;
            border-color: #c6daf8;
        }

        .action-icon[disabled] {
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

        .edit-modal {
            position: fixed;
            inset: 0;
            z-index: 1200;
            display: none;
        }

        .edit-modal.open {
            display: block;
        }

        .edit-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.56);
        }

        .edit-modal-dialog {
            position: relative;
            width: min(980px, calc(100vw - 28px));
            max-height: calc(100vh - 40px);
            margin: 20px auto;
            background: #fff;
            border: 1px solid #d7e0ec;
            border-radius: 16px;
            box-shadow: 0 28px 60px rgba(17, 24, 39, 0.28);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .edit-modal-header {
            padding: 14px 18px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .edit-modal-header h3 {
            margin: 0;
            font-size: 1rem;
            color: #111827;
        }

        .edit-modal-header p {
            margin: 4px 0 0;
            font-size: 0.78rem;
            color: #6b7280;
        }

        .edit-modal-close {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 8px;
            background: #f3f4f6;
            color: #4b5563;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .edit-modal-close:hover {
            background: #e5e7eb;
        }

        .edit-modal-form {
            display: flex;
            flex-direction: column;
            min-height: 0;
            flex: 1;
        }

        .edit-modal-body {
            padding: 14px 18px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 14px;
            overflow-y: auto;
            min-height: 0;
            max-height: calc(100vh - 190px);
        }

        .edit-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .edit-field.full {
            grid-column: 1 / -1;
        }

        .edit-section-title {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 8px;
            margin-bottom: 2px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #1f4f84;
        }

        .edit-section-title::after {
            content: '';
            height: 1px;
            flex: 1;
            background: #dbe6f2;
        }

        .edit-field label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .edit-input,
        .edit-select,
        .edit-textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #fff;
            color: #111827;
            font-size: 0.83rem;
            padding: 9px 10px;
        }

        .edit-textarea {
            min-height: 88px;
            resize: vertical;
        }

        .edit-input:focus,
        .edit-select:focus,
        .edit-textarea:focus {
            outline: none;
            border-color: #245ea8;
            box-shadow: 0 0 0 3px rgba(36, 94, 168, 0.15);
        }

        .edit-input[disabled],
        .edit-select[disabled],
        .edit-textarea[disabled] {
            background: #f3f4f6;
            color: #6b7280;
            cursor: not-allowed;
        }

        .edit-modal-loading,
        .edit-modal-error {
            grid-column: 1 / -1;
            padding: 20px;
            text-align: center;
            border-radius: 12px;
            background: #f9fafb;
            color: #374151;
            border: 1px dashed #d1d5db;
        }

        .edit-modal-error {
            color: #b42318;
            border-color: #f1c4c0;
            background: #fef2f2;
        }

        .edit-modal-footer {
            border-top: 1px solid #e5e7eb;
            padding: 12px 18px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background: #fff;
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 900px) {
            .edit-modal-body {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="clients-page">
<?php if (!$isKycOfficerUser): ?>
    <main class="denied-shell">
        <section class="denied-card">
            <i class="bi bi-shield-lock"></i>
            <h1>Access Restricted</h1>
            <p>This page is only available for KYC officer accounts.</p>
            <a href="dashboard.php">Return to Dashboard</a>
        </section>
    </main>
<?php else: ?>

<?php
$activePage = 'my-applications';
include '../includes/sidebar.php';
?>

<div class="main">
    <header class="topbar">
        <div class="topbar-left">
            <h1>My Applications</h1>
            <div class="breadcrumb-trail">
                <i class="bi bi-house" style="font-size:.65rem;"></i>
                Dashboard &rsaquo; <span>My Applications</span>
            </div>
        </div>
        <div class="topbar-right"></div>
    </header>

    <main class="content">
        <div class="controls-container">
            <div class="controls-left">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" placeholder="Search your applications..." class="search-input">
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
                    <select id="filterType" class="filter-select">
                        <option value="">All Types</option>
                        <option value="individual">Individual</option>
                        <option value="corporate">Corporate</option>
                        <option value="obligee">Obligee</option>
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
                        <th class="col-owner">Submitted At</th>
                        <th class="col-status">Current Status</th>
                        <th class="col-owner">Reviewed By</th>
                        <th class="col-owner">Reviewed At</th>
                        <th class="col-owner remarks-header">Admin Remarks</th>
                        <th class="col-actions">Action</th>
                    </tr>
                </thead>
                <tbody id="applicationsTableBody">
                    <tr>
                        <td colspan="10" style="text-align:center; padding:20px;">Loading applications...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div class="pagination-info">
                Showing <span class="info-start">0</span> to <span class="info-end">0</span> of <span class="info-total">0</span> applications
            </div>
            <div class="pagination" id="paginationContainer"></div>
        </div>
    </main>
</div>

<div id="toastContainer" class="toast-container"></div>

<div id="editApplicationModal" class="edit-modal" hidden>
    <div class="edit-modal-backdrop" data-close-modal="true"></div>
    <div class="edit-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
        <div class="edit-modal-header">
            <div>
                <h3 id="editModalTitle">Edit Submitted Credentials</h3>
                <p id="editModalMeta">Load the submitted credentials to edit this application.</p>
            </div>
            <button type="button" class="edit-modal-close" id="editModalCloseBtn" aria-label="Close edit modal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="editApplicationForm" class="edit-modal-form">
            <div id="editModalFields" class="edit-modal-body"></div>
            <div class="edit-modal-footer">
                <button type="button" class="btn-cancel" id="editModalCancelBtn">Cancel</button>
                <button type="submit" class="action-icon action-resubmit" id="editModalSaveBtn">
                    <i class="bi bi-save"></i>Save & Resubmit
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentPage = 1;
    const pageSize = 10;
    let searchDebounceTimer;
    const modalState = {
        approvalId: 0,
        editable: false,
        credentials: {},
    };

    const credentialFieldDefs = [
        { key: 'client_type', label: 'Client Type', type: 'select', options: ['individual', 'corporate', 'obligee'], group: 'Application' },

        { key: 'client_name', label: 'Client Name', visibleFor: ['corporate', 'obligee'], group: 'Identity' },
        { key: 'salutation', label: 'Salutation', visibleFor: ['individual', 'obligee'], group: 'Identity' },
        { key: 'first_name', label: 'First Name', visibleFor: ['individual', 'obligee'], group: 'Identity' },
        { key: 'middle_name', label: 'Middle Name', visibleFor: ['individual', 'obligee'], group: 'Identity' },
        { key: 'last_name', label: 'Last Name', visibleFor: ['individual', 'obligee'], group: 'Identity' },
        { key: 'suffix', label: 'Suffix', visibleFor: ['individual', 'obligee'], group: 'Identity' },
        { key: 'gender', label: 'Gender', type: 'select', options: ['male', 'female', 'other'], visibleFor: ['individual', 'obligee'], group: 'Identity' },
        { key: 'nationality', label: 'Nationality', visibleFor: ['individual', 'obligee'], group: 'Identity' },
        { key: 'date_of_birth', label: 'Date of Birth', type: 'date', visibleFor: ['individual', 'obligee'], group: 'Identity' },

        { key: 'email', label: 'Email', type: 'email', group: 'Contact' },
        { key: 'mobile_phone', label: 'Mobile Phone', group: 'Contact' },
        { key: 'office_phone', label: 'Office Phone', group: 'Contact' },
        { key: 'home_phone', label: 'Home Phone', visibleFor: ['individual', 'obligee'], group: 'Contact' },
        { key: 'contact_person', label: 'Contact Person', visibleFor: ['corporate', 'obligee'], group: 'Contact' },

        { key: 'id_type', label: 'ID Type', group: 'Government IDs' },
        { key: 'id_number', label: 'ID Number', group: 'Government IDs' },
        { key: 'tin_number', label: 'TIN Number', group: 'Government IDs' },

        { key: 'occupation', label: 'Occupation', visibleFor: ['individual', 'obligee'], group: 'Work & Business' },
        { key: 'company_name', label: 'Company Name', visibleFor: ['corporate', 'obligee'], group: 'Work & Business' },
        { key: 'designation', label: 'Designation', visibleFor: ['corporate', 'obligee'], group: 'Work & Business' },
        { key: 'business_type', label: 'Business Type', type: 'select', options: ['private', 'government'], visibleFor: ['corporate', 'obligee'], group: 'Work & Business' },

        { key: 'spouse_name', label: 'Spouse Name', visibleFor: ['individual', 'obligee'], group: 'Family' },
        { key: 'spouse_birthdate', label: 'Spouse Birthdate', type: 'date', visibleFor: ['individual', 'obligee'], group: 'Family' },
        { key: 'spouse_occupation', label: 'Spouse Occupation', visibleFor: ['individual', 'obligee'], group: 'Family' },

        { key: 'region', label: 'Region', group: 'Address' },
        { key: 'business_ctm', label: 'Business City/Municipality', visibleFor: ['corporate', 'obligee'], group: 'Address' },
        { key: 'business_province', label: 'Business Province', visibleFor: ['corporate', 'obligee'], group: 'Address' },
        { key: 'home_ctm', label: 'Home City/Municipality', visibleFor: ['individual', 'obligee'], group: 'Address' },
        { key: 'home_province', label: 'Home Province', visibleFor: ['individual', 'obligee'], group: 'Address' },
        { key: 'business_address', label: 'Business Address', type: 'textarea', fullWidth: true, visibleFor: ['corporate', 'obligee'], group: 'Address' },
        { key: 'home_address', label: 'Home Address', type: 'textarea', fullWidth: true, visibleFor: ['individual', 'obligee'], group: 'Address' },
        { key: 'full_address', label: 'Full Address', type: 'textarea', fullWidth: true, group: 'Address' },
    ];

    const editModal = document.getElementById('editApplicationModal');
    const editModalMeta = document.getElementById('editModalMeta');
    const editModalFields = document.getElementById('editModalFields');
    const editApplicationForm = document.getElementById('editApplicationForm');
    const editModalSaveBtn = document.getElementById('editModalSaveBtn');
    const editModalCloseBtn = document.getElementById('editModalCloseBtn');
    const editModalCancelBtn = document.getElementById('editModalCancelBtn');

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
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

    function setTableLoading(isLoading) {
        const wrapper = document.querySelector('.table-wrapper');
        if (!wrapper) return;
        wrapper.classList.toggle('is-loading', isLoading);
    }

    function getActiveFilters() {
        return {
            search: document.getElementById('searchInput').value.trim(),
            status: document.getElementById('filterStatus').value,
            type: document.getElementById('filterType').value
        };
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
        if (normalized === 'approved') return 'status-approved';
        if (normalized === 'declined') return 'status-declined';
        if (normalized === 'resubmit') return 'status-resubmit';
        return 'status-pending';
    }

    function toTitleCase(value) {
        const text = String(value || '').toLowerCase();
        if (!text) return '';
        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    function normalizeClientType(value) {
        const text = String(value || '').toLowerCase().trim();
        if (text === 'corporate' || text === 'obligee') {
            return text;
        }
        return 'individual';
    }

    function getVisibleFieldDefs(clientType) {
        const normalizedType = normalizeClientType(clientType);

        return credentialFieldDefs.filter(definition => {
            if (definition.key === 'client_type') {
                return true;
            }

            if (!Array.isArray(definition.visibleFor) || definition.visibleFor.length === 0) {
                return true;
            }

            return definition.visibleFor.includes(normalizedType);
        });
    }

    function collectModalFieldValues() {
        const values = {};
        if (!editModalFields) {
            return values;
        }

        editModalFields.querySelectorAll('[name]').forEach(control => {
            values[String(control.name)] = String(control.value ?? '');
        });

        return values;
    }

    function parseJsonResponse(response) {
        return response.text().then(raw => {
            let payload;
            try {
                payload = JSON.parse(raw);
            } catch (error) {
                throw new Error('Invalid server response. Please refresh and try again.');
            }

            if (!response.ok) {
                throw new Error(payload.message || 'Request failed.');
            }

            return payload;
        });
    }

    function showEditModal() {
        if (!editModal) return;
        editModal.hidden = false;
        editModal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function hideEditModal() {
        if (!editModal) return;
        editModal.classList.remove('open');
        editModal.hidden = true;
        document.body.style.overflow = '';
        modalState.approvalId = 0;
        modalState.editable = false;
        modalState.credentials = {};

        if (editApplicationForm) {
            editApplicationForm.reset();
        }
    }

    function createEditField(definition, value, disabled) {
        const wrapper = document.createElement('div');
        wrapper.className = `edit-field${definition.fullWidth ? ' full' : ''}`;

        const label = document.createElement('label');
        label.textContent = definition.label;
        label.setAttribute('for', `editField-${definition.key}`);
        wrapper.appendChild(label);

        let control;
        if (definition.type === 'textarea') {
            control = document.createElement('textarea');
            control.className = 'edit-textarea';
            control.rows = 3;
            control.value = value;
        } else if (definition.type === 'select') {
            control = document.createElement('select');
            control.className = 'edit-select';

            const blankOption = document.createElement('option');
            blankOption.value = '';
            blankOption.textContent = 'Select option';
            control.appendChild(blankOption);

            (definition.options || []).forEach(optionValue => {
                const option = document.createElement('option');
                option.value = String(optionValue);
                option.textContent = toTitleCase(optionValue);
                control.appendChild(option);
            });

            control.value = value;
        } else {
            control = document.createElement('input');
            control.className = 'edit-input';
            control.type = definition.type || 'text';
            control.value = value;
        }

        control.id = `editField-${definition.key}`;
        control.name = definition.key;
        control.disabled = disabled;
        wrapper.appendChild(control);

        return wrapper;
    }

    function createSectionHeading(text) {
        const heading = document.createElement('div');
        heading.className = 'edit-section-title';
        heading.textContent = String(text || 'Details');
        return heading;
    }

    function renderEditFields(credentials, disabled) {
        if (!editModalFields) return;

        const normalizedType = normalizeClientType(credentials.client_type || 'individual');
        const visibleFieldDefs = getVisibleFieldDefs(normalizedType);

        editModalFields.innerHTML = '';
        let lastGroup = '';
        visibleFieldDefs.forEach(definition => {
            const currentGroup = String(definition.group || 'Details');
            if (currentGroup !== lastGroup) {
                editModalFields.appendChild(createSectionHeading(currentGroup));
                lastGroup = currentGroup;
            }

            const rawValue = Object.prototype.hasOwnProperty.call(credentials, definition.key)
                ? credentials[definition.key]
                : '';
            const safeValue = rawValue === null || typeof rawValue === 'undefined'
                ? ''
                : String(rawValue);

            editModalFields.appendChild(createEditField(definition, safeValue, disabled));
        });

        const clientTypeControl = editModalFields.querySelector('#editField-client_type');
        if (clientTypeControl && !disabled) {
            clientTypeControl.addEventListener('change', () => {
                const liveValues = collectModalFieldValues();
                liveValues.client_type = normalizeClientType(clientTypeControl.value);
                modalState.credentials = {
                    ...modalState.credentials,
                    ...liveValues,
                };

                renderEditFields(modalState.credentials, !modalState.editable);
            });
        }
    }

    function openEditModal(approvalId) {
        if (!approvalId) {
            return;
        }

        modalState.approvalId = Number(approvalId);
        modalState.editable = false;

        if (editModalMeta) {
            editModalMeta.textContent = 'Loading submitted credentials...';
        }
        if (editModalFields) {
            editModalFields.innerHTML = '<div class="edit-modal-loading">Loading submitted credentials...</div>';
        }
        if (editModalSaveBtn) {
            editModalSaveBtn.disabled = true;
        }

        showEditModal();

        fetch(`../handlers/my_applications.php?action=details&approval_id=${encodeURIComponent(String(approvalId))}`, {
            method: 'GET',
            credentials: 'include'
        })
            .then(parseJsonResponse)
            .then(payload => {
                if (!payload.success) {
                    throw new Error(payload.message || 'Failed to load submitted credentials.');
                }

                const details = payload.data || {};
                modalState.editable = Boolean(details.editable);
                modalState.credentials = {
                    ...(details.credentials || {}),
                };
                modalState.credentials.client_type = normalizeClientType(
                    modalState.credentials.client_type || details.client_type || 'individual'
                );

                if (editModalMeta) {
                    const statusText = toTitleCase(details.approval_status || 'pending') || 'Pending';
                    editModalMeta.textContent = `Reference ${details.reference_code || 'N/A'} | Status: ${statusText}`;
                }

                renderEditFields(modalState.credentials, !modalState.editable);

                if (editModalSaveBtn) {
                    editModalSaveBtn.disabled = !modalState.editable;
                }

                if (!modalState.editable) {
                    createToast('info', 'Not Editable', 'Only applications with Resubmit status can be edited.');
                }
            })
            .catch(error => {
                if (editModalFields) {
                    editModalFields.innerHTML = `<div class="edit-modal-error">${escapeHtml(error.message || 'Failed to load submitted credentials.')}</div>`;
                }
                if (editModalMeta) {
                    editModalMeta.textContent = 'Unable to load submitted credentials.';
                }
            });
    }

    function renderTable(rows) {
        const tbody = document.getElementById('applicationsTableBody');
        if (!tbody) return;

        if (!Array.isArray(rows) || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" style="text-align:center; padding: 22px;">No application records found</td></tr>';
            return;
        }

        tbody.innerHTML = '';

        rows.forEach(row => {
            const statusAfter = String(row.status_after_review || row.approval_status || 'pending').toLowerCase();
            const canEdit = Boolean(row.can_edit);
            const remarks = String(row.admin_remarks || '').trim();

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="col-ref"><span class="ref-badge">${escapeHtml(row.reference_code || 'N/A')}</span></td>
                <td class="col-name">${escapeHtml(row.display_name || row.client_name || 'N/A')}</td>
                <td class="col-type">${escapeHtml(formatClassification(row.client_classification))}</td>
                <td class="col-type">${escapeHtml(formatType(row.client_type))}</td>
                <td class="col-owner">${escapeHtml(formatDateTime(row.submitted_at))}</td>
                <td class="col-status"><span class="status-badge ${statusBadgeClass(statusAfter)}">${escapeHtml(statusAfter)}</span></td>
                <td class="col-owner review-meta-cell">${escapeHtml(row.reviewed_by_name || 'N/A')}</td>
                <td class="col-owner review-meta-cell">${escapeHtml(formatDateTime(row.reviewed_at || row.latest_reviewed_at))}</td>
                <td class="remarks-cell">${escapeHtml(remarks || 'No remarks')}</td>
                <td class="col-actions">
                    <button class="action-icon action-resubmit" data-action="edit" data-approval-id="${Number(row.approval_id || 0)}" ${canEdit ? '' : 'disabled'}>
                        <i class="bi bi-pencil-square"></i>Edit
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
        });

        attachActionHandlers();
    }

    function attachActionHandlers() {
        document.querySelectorAll('#applicationsTableBody .action-icon[data-action="edit"]').forEach(button => {
            button.addEventListener('click', event => {
                event.preventDefault();
                const approvalId = Number(button.dataset.approvalId || 0);
                if (!approvalId) {
                    return;
                }
                openEditModal(approvalId);
            });
        });
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
        const pages = Math.max(1, Number(payload.totalPages || 1));

        const createButton = (label, disabled, targetPage) => {
            const btn = document.createElement('button');
            btn.className = 'pagination-btn';
            btn.disabled = disabled;
            btn.innerHTML = label;
            if (!disabled) {
                btn.addEventListener('click', () => loadMyApplications(targetPage));
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

    function loadMyApplications(page = 1) {
        setTableLoading(true);

        const filters = getActiveFilters();
        const query = new URLSearchParams({
            page: String(page),
            pageSize: String(pageSize),
            search: filters.search,
            status: filters.status,
            type: filters.type
        });

        fetch(`../handlers/my_applications.php?${query.toString()}`, {
            method: 'GET',
            credentials: 'include'
        })
            .then(response => response.json())
            .then(payload => {
                if (!payload.success) {
                    throw new Error(payload.message || 'Failed to load your applications');
                }

                currentPage = Number(payload.page || 1);

                renderTable(payload.data || []);
                updatePaginationInfo(payload);
                renderPagination(payload);
            })
            .catch(error => {
                const tbody = document.getElementById('applicationsTableBody');
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="10" style="text-align:center; color:#b42318; padding: 22px;">${escapeHtml(error.message || 'Failed to load applications')}</td></tr>`;
                }
                updatePaginationInfo({ total: 0, page: 1, pageSize, totalPages: 1 });
                renderPagination({ page: 1, totalPages: 1 });
                createToast('error', 'Load Failed', error.message || 'Unable to load applications.');
            })
            .finally(() => {
                setTableLoading(false);
            });
    }

    function applyFilters() {
        loadMyApplications(1);
    }

    if (editApplicationForm) {
        editApplicationForm.addEventListener('submit', event => {
            event.preventDefault();

            if (!modalState.approvalId || !modalState.editable) {
                return;
            }

            modalState.credentials = {
                ...modalState.credentials,
                ...collectModalFieldValues(),
            };
            modalState.credentials.client_type = normalizeClientType(modalState.credentials.client_type || 'individual');
            const fields = { ...modalState.credentials };

            const originalButtonHtml = editModalSaveBtn ? editModalSaveBtn.innerHTML : '';
            if (editModalSaveBtn) {
                editModalSaveBtn.disabled = true;
                editModalSaveBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i>Saving...';
            }

            fetch('../handlers/my_applications.php?action=update', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    approval_id: modalState.approvalId,
                    fields
                })
            })
                .then(parseJsonResponse)
                .then(payload => {
                    if (!payload.success) {
                        throw new Error(payload.message || 'Failed to save application updates.');
                    }

                    createToast('success', 'Resubmitted', payload.message || 'Submitted credentials were updated and resubmitted for review.');
                    hideEditModal();
                    loadMyApplications(currentPage);
                })
                .catch(error => {
                    createToast('error', 'Save Failed', error.message || 'Unable to save submitted credentials.');
                })
                .finally(() => {
                    if (editModalSaveBtn) {
                        editModalSaveBtn.innerHTML = originalButtonHtml;
                        editModalSaveBtn.disabled = !modalState.editable;
                    }
                });
        });
    }

    if (editModal) {
        editModal.addEventListener('click', event => {
            const target = event.target;
            if (target && target.getAttribute && target.getAttribute('data-close-modal') === 'true') {
                hideEditModal();
            }
        });
    }

    if (editModalCloseBtn) {
        editModalCloseBtn.addEventListener('click', hideEditModal);
    }

    if (editModalCancelBtn) {
        editModalCancelBtn.addEventListener('click', hideEditModal);
    }

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && editModal && editModal.classList.contains('open')) {
            hideEditModal();
        }
    });

    document.getElementById('searchInput').addEventListener('keyup', function () {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => applyFilters(), 300);
    });

    document.getElementById('filterStatus').addEventListener('change', applyFilters);
    document.getElementById('filterType').addEventListener('change', applyFilters);

    document.addEventListener('DOMContentLoaded', () => {
        loadMyApplications(1);
    });
</script>
<?php endif; ?>
</body>
</html>
