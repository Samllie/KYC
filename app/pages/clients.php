<?php
require_once '../config/session.php';
requireLogin();

$currentUserBranch = trim($_SESSION['branch'] ?? '');
$currentUserRole = strtolower(trim($_SESSION['role'] ?? ''));
$currentUserDepartment = strtoupper(trim($_SESSION['department'] ?? ''));
$isHeadOfficeView = $currentUserRole === 'admin'
    || $currentUserDepartment === 'HEAD OFFICE'
    || in_array(strtoupper($currentUserBranch), ['HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH'], true);

$requestedClassification = strtolower(trim($_GET['classification'] ?? 'client'));
$listClassification = $requestedClassification === 'agent' ? 'agent' : 'client';
$isAgentsMode = $listClassification === 'agent';
$requestedType = strtolower(trim($_GET['type'] ?? ''));
$allowedInitialTypes = $isAgentsMode ? ['agent', 'sub_agent'] : ['individual', 'corporate', 'obligee'];
$initialTypeFilter = in_array($requestedType, $allowedInitialTypes, true) ? $requestedType : '';

$pageHeading = $isAgentsMode ? 'Agents Management' : 'Clients Management';
$recordLabelSingular = $isAgentsMode ? 'agent' : 'client';
$recordLabelPlural = $isAgentsMode ? 'agents' : 'clients';
$recordTitleCaseSingular = ucfirst($recordLabelSingular);
$recordTitleCasePlural = ucfirst($recordLabelPlural);
$newRecordLabel = $isAgentsMode ? 'New Agent' : 'New Client';
$kycEntryUrl = $isAgentsMode
    ? 'kyc-individual.php?classification=agent'
    : ('kyc-verification.php?classification=' . urlencode($listClassification));
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
    <link rel="stylesheet" href="../../public/css/clients.css">
    <link rel="stylesheet" href="../../public/css/global.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body class="clients-page clients-management-page <?php echo $isAgentsMode ? 'agents-mode' : ''; ?>">

<?php
$activePage = $isAgentsMode ? 'agents' : 'clients';
include '../includes/sidebar.php';
?>

<!-- ═══════════════════════════════════════════════ MAIN -->
<div class="main">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <h1><?php echo htmlspecialchars($pageHeading); ?></h1>
            <div class="breadcrumb-trail">
                <i class="bi bi-house" style="font-size:.65rem;"></i>
                Dashboard &rsaquo; <span><?php echo htmlspecialchars($recordTitleCasePlural); ?></span>
            </div>
        </div>
        <div class="topbar-right">

        </div>
    </header>

    <!-- Content -->
    <main class="content">
        <section class="clients-table-shell">
            <div class="controls-container">
                <!-- Table Controls -->
                <div class="table-controls">
                    <div class="controls-left">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" id="searchInput" placeholder="Search <?php echo htmlspecialchars($recordLabelPlural); ?>..." class="search-input">
                        </div>
                        <div class="filter-group">
                            <select id="filterType" class="filter-select">
                                <?php if ($isAgentsMode): ?>
                                    <option value="">All Agent Types</option>
                                    <option value="agent">Agent</option>
                                    <option value="sub_agent">Sub agent</option>
                                <?php else: ?>
                                    <option value="">All Types</option>
                                    <option value="individual">Individual</option>
                                    <option value="corporate">Corporate</option>
                                    <option value="obligee">Obligee</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <select id="filterActivity" class="filter-select">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <select id="sortOrder" class="filter-select">
                                <option value="created_desc">Latest Added</option>
                                <option value="alphabetical_asc">Alphabetical A-Z</option>
                                <option value="alphabetical_desc">Alphabetical Z-A</option>
                                <option value="updated_asc">Time Updated: Oldest First</option>
                                <option value="updated_desc">Time Updated: Newest First</option>
                            </select>
                        </div>
                        <?php if ($isHeadOfficeView): ?>
                        <div class="filter-group">
                            <select id="filterBranch" class="filter-select">
                                <option value="">All Branches</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="controls-right">
                        <?php if ($isAgentsMode): ?>
                        <button type="button" class="btn-delete-selected" id="deleteSelectedBtn" disabled>
                            <i class="bi bi-trash"></i> Delete Selected
                        </button>
                        <?php endif; ?>
                        <button class="btn-export" title="Export" onclick="showExportPreview()">
                            <i class="bi bi-download"></i> Export
                        </button>
                        <button class="btn-add-client" title="Add New <?php echo htmlspecialchars($recordTitleCaseSingular); ?>" onclick="window.location.href='<?php echo htmlspecialchars($kycEntryUrl); ?>'">
                            <i class="bi bi-plus-circle"></i> <?php echo htmlspecialchars($newRecordLabel); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Clients Table -->
            <div class="table-wrapper">
                <table class="clients-table">
                    <thead>
                        <tr>
                            <th class="col-checkbox"><input type="checkbox" id="selectAll"></th>
                            <th class="col-ref">Ref Code</th>
                            <th class="col-name">Business/<?php echo htmlspecialchars($recordTitleCaseSingular); ?> Name</th>
                            <th class="col-owner">Branch</th>
                            <th class="col-type"><?php echo $isAgentsMode ? 'Agent Type' : 'Type'; ?></th>
                            <?php if ($isAgentsMode): ?>
                            <th class="col-main-agent">Main Agent</th>
                            <?php endif; ?>
                            <th class="col-contact">Contact</th>
                            <?php if (!$isAgentsMode): ?>
                            <th class="col-email">Email</th>
                            <?php endif; ?>
                            <th class="col-verified">Submitted By</th>
                            <th class="col-activity">Activity Status</th>
                            <th class="col-activity-updated">Status Updated</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="clientsTableBody">
                        <!-- Clients will be loaded dynamically -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="table-footer">
                <div class="pagination-info">
                    Showing <span class="info-start">0</span> to <span class="info-end">0</span> of <span class="info-total">0</span> <?php echo htmlspecialchars($recordLabelPlural); ?>
                </div>
                <div class="pagination" id="paginationContainer">
                    <!-- Pagination buttons will be generated dynamically -->
                </div>
            </div>
        </section>

    </main>

</div>

<!-- ═══════════════════════════════════════════════ MODAL: Edit Client -->
<div id="editModal" class="modal">
    <div class="modal-content edit-modal-content">
        <div class="modal-header">
            <h2>Edit <?php echo htmlspecialchars($recordTitleCaseSingular); ?> Information</h2>
            <button id="editModalCloseBtn" type="button" class="modal-close" title="Close"><i class="bi bi-x"></i></button>
        </div>
        <div class="modal-body">
            <form id="editForm">
                <input type="hidden" id="editClientId">
                <?php if ($isAgentsMode): ?>
                <input type="hidden" id="editClientType" value="individual">
                <?php endif; ?>
                <!-- Row 1: Reference & Type -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Ref Code</label>
                        <input type="text" id="editRefCode" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Submitted Branch</label>
                        <input type="text" id="editSubmittedBranch" class="form-control" readonly>
                    </div>
                    <?php if ($isAgentsMode): ?>
                    <div class="form-group">
                        <label class="form-label">Agent Type</label>
                        <select id="editAgentType" class="form-select">
                            <option value="agent">Agent</option>
                            <option value="sub_agent">Sub agent</option>
                        </select>
                    </div>
                    <?php else: ?>
                    <div class="form-group">
                        <label class="form-label">Client Type</label>
                        <select id="editClientType" class="form-select">
                            <option value="individual">Individual</option>
                            <option value="corporate">Corporate</option>
                            <option value="obligee">Obligee</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($isAgentsMode): ?>
                <div class="form-row">
                    <div class="form-group full" id="editHeadAgentGroup" style="display:none;">
                        <label class="form-label">Head Agent Name</label>
                        <input type="text" id="editHeadAgentName" class="form-control" placeholder="Enter the main agent name">
                    </div>
                </div>
                <?php endif; ?>

                <!-- Row 2: Name -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" id="editFirstName" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Middle Name</label>
                        <input type="text" id="editMiddleName" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" id="editLastName" class="form-control">
                    </div>
                </div>

                <!-- Row 3: Personal Details -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Birthdate</label>
                        <input type="date" id="editBirthdate" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select id="editGender" class="form-select">
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Prefer not to say</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Civil Status</label>
                        <select id="editCivilStatus" class="form-select">
                            <option>Single</option>
                            <option>Married</option>
                            <option>Widowed</option>
                            <option>Separated</option>
                        </select>
                    </div>
                </div>

                <!-- Row 4: Additional Details -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Occupation</label>
                        <input type="text" id="editOccupation" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nationality</label>
                        <input type="text" id="editNationality" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">TIN / Tax ID</label>
                        <input type="text" id="editTin" class="form-control">
                    </div>
                </div>

                <!-- Row 5: Contact Information -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" id="editEmail" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mobile Number</label>
                        <input type="tel" id="editMobile" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telephone</label>
                        <input type="tel" id="editTelephone" class="form-control">
                    </div>
                </div>

                <!-- Row 6: Address -->
                <div class="form-row">
                    <div class="form-group full">
                        <label class="form-label">Present Address</label>
                        <input type="text" id="editAddress" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full">
                        <label class="form-label">Activity Status</label>
                        <input type="hidden" id="editActivityStatus" value="active">
                        <div class="activity-status-toggle-group" id="editActivityStatusToggleGroup" role="group" aria-label="Activity Status">
                            <button type="button" class="activity-status-toggle is-selected active" data-status="active">Active</button>
                            <button type="button" class="activity-status-toggle inactive" data-status="inactive">Inactive</button>
                        </div>
                        <div class="activity-status-summary">Selected: <strong id="editActivityStatusLabel">Active</strong></div>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Status Updated At</label>
                        <input type="text" id="editActivityStatusUpdatedAt" class="form-control" readonly>
                    </div>
                </div>

            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" id="cancelBtn">Cancel</button>
            <button class="btn-save" id="saveBtn" type="button">Save Changes</button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ MODAL: View Client -->
<div id="viewModal" class="modal">
    <div class="modal-content view-modal-content" style="max-width: 900px; max-height: 92vh; display: flex; flex-direction: column;">
        <div class="modal-header">
            <h2 id="viewModalTitle"><?php echo htmlspecialchars($recordTitleCaseSingular); ?> Preview</h2>
            <button class="modal-close" title="Close" onclick="document.getElementById('viewModal').style.display='none'"><i class="bi bi-x"></i></button>
        </div>
        <div class="modal-body">
            <form id="viewForm">
                <input type="hidden" id="viewClientId">
                <div id="viewClientDetails" class="client-preview-shell"></div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="document.getElementById('viewModal').style.display='none'">Close</button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ MODAL: Export Preview -->
<div id="exportPreviewModal" class="modal">
    <div class="modal-content export-preview-modal-content" style="max-width: 1440px; max-height: 92vh; display: flex; flex-direction: column;">
        <div class="modal-header">
            <h2>Export <?php echo htmlspecialchars($recordTitleCasePlural); ?> Report</h2>
            <button class="modal-close export-modal-close" title="Close" onclick="document.getElementById('exportPreviewModal').style.display='none'"><i class="bi bi-x"></i></button>
        </div>
        <div class="modal-body" style="flex: 1; overflow-y: auto;">
            <div id="previewContent" class="export-preview-content"></div>
        </div>
        <div class="modal-footer" style="justify-content: space-between;">
            <button class="btn-cancel export-modal-close" onclick="document.getElementById('exportPreviewModal').style.display='none'">
                <i class="bi bi-x-circle"></i> Close
            </button>
            <div style="display: flex; gap: 8px;">
                <button class="btn btn-outline" onclick="exportAsCSV()">
                    <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                </button>
                <button class="btn btn-outline" onclick="exportAsPDF()">
                    <i class="bi bi-file-earmark-pdf"></i> PDF
                </button>
                <button class="btn btn-primary" onclick="printReport()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ MODAL: Delete Confirmation -->
<div id="deleteConfirmModal" class="modal" aria-hidden="true">
    <div class="modal-content delete-modal-content" role="dialog" aria-modal="true" aria-labelledby="deleteConfirmTitle">
        <div class="modal-header">
            <h2 id="deleteConfirmTitle">Confirm Delete</h2>
            <button id="deleteModalCloseBtn" type="button" class="modal-close" title="Close"><i class="bi bi-x"></i></button>
        </div>
        <div class="modal-body delete-modal-body">
            <p>Are you sure you want to delete this <?php echo htmlspecialchars($recordLabelSingular); ?> record? This action cannot be undone.</p>
            <div class="delete-client-meta" aria-live="polite">
                <div>
                    <span>Ref Code</span>
                    <strong id="deleteConfirmRefCode">N/A</strong>
                </div>
                <div>
                    <span><?php echo htmlspecialchars($recordTitleCaseSingular); ?> Name</span>
                    <strong id="deleteConfirmName">N/A</strong>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" id="deleteCancelBtn" type="button">Cancel</button>
            <button class="btn-delete-confirm" id="deleteConfirmBtn" type="button">
                <i class="bi bi-trash"></i> Confirm Delete
            </button>
        </div>
    </div>
</div>

<script src="../../public/js/dialog-modal.js"></script>

<script>
    // Pagination state
    let currentPage = 1;
    let pageSize = 10;
    let totalPages = 1;
    let totalClients = 0;
    let currentEditingClientId = null;
    let searchDebounceTimer = null;
    let currentPageClients = [];
    let pendingDeleteClient = null;
    const selectedClientIds = new Set();
    const selectedClientRows = new Map();
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const isHeadOfficeUser = <?php echo $isHeadOfficeView ? 'true' : 'false'; ?>;
    const listClassification = <?php echo json_encode($listClassification); ?>;
    const initialTypeFilter = <?php echo json_encode($initialTypeFilter); ?>;
    const isAgentsMode = <?php echo $isAgentsMode ? 'true' : 'false'; ?>;
    const recordLabelSingular = <?php echo json_encode($recordLabelSingular); ?>;
    const recordLabelPlural = <?php echo json_encode($recordLabelPlural); ?>;
    const recordTitleCaseSingular = <?php echo json_encode($recordTitleCaseSingular); ?>;
    const recordTitleCasePlural = <?php echo json_encode($recordTitleCasePlural); ?>;
    const clientTableColumnCount = 11;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalizeActivityStatusClass(status) {
        const normalized = String(status || '').toLowerCase();
        if (normalized === 'inactive' || normalized === 'deactivated') {
            return normalized;
        }
        return 'active';
    }

    function normalizeEditableActivityStatus(status) {
        const normalized = String(status || '').toLowerCase();
        if (normalized === 'inactive' || normalized === 'deactivated') {
            return 'inactive';
        }
        return 'active';
    }

    function setEditActivityStatus(status) {
        const activityStatusInput = document.getElementById('editActivityStatus');
        const activityStatusLabel = document.getElementById('editActivityStatusLabel');
        const normalized = normalizeEditableActivityStatus(status);

        if (activityStatusInput) {
            activityStatusInput.value = normalized;
        }

        if (activityStatusLabel) {
            activityStatusLabel.textContent = normalized.charAt(0).toUpperCase() + normalized.slice(1);
        }

        document.querySelectorAll('.activity-status-toggle').forEach(button => {
            const buttonStatus = normalizeEditableActivityStatus(button.dataset.status || 'active');
            button.classList.toggle('is-selected', buttonStatus === normalized);
        });
    }

    function syncEditAgentFields() {
        if (!isAgentsMode) {
            return;
        }

        const agentTypeField = document.getElementById('editAgentType');
        const headAgentGroup = document.getElementById('editHeadAgentGroup');
        const headAgentField = document.getElementById('editHeadAgentName');

        if (!agentTypeField || !headAgentGroup || !headAgentField) {
            return;
        }

        const isSubAgent = agentTypeField.value === 'sub_agent';
        headAgentGroup.style.display = isSubAgent ? '' : 'none';
        headAgentField.required = isSubAgent;

        if (!isSubAgent) {
            headAgentField.value = '';
        }
    }

    function normalizePreviewText(value) {
        return String(value ?? '').trim();
    }

    function capitalizePreviewText(value) {
        const text = normalizePreviewText(value);
        if (!text) {
            return '';
        }

        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    function humanizePreviewText(value) {
        const text = normalizePreviewText(value);
        if (!text) {
            return '';
        }

        return text
            .replace(/[_-]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .split(' ')
            .map(part => {
                if (!part) {
                    return part;
                }

                return part.charAt(0).toUpperCase() + part.slice(1).toLowerCase();
            })
            .join(' ');
    }

    function getFirstPreviewValue(client, keys) {
        if (!client || !Array.isArray(keys)) {
            return '';
        }

        for (const key of keys) {
            const value = normalizePreviewText(client[key]);
            if (value !== '') {
                return value;
            }
        }

        return '';
    }

    function getClientTypeDisplayLabel(clientType) {
        const normalized = normalizePreviewText(clientType).toLowerCase();
        if (normalized === 'corporate') {
            return 'Corporate';
        }
        if (normalized === 'obligee') {
            return 'Obligee';
        }
        if (normalized === 'individual') {
            return 'Individual';
        }

        return normalized ? capitalizePreviewText(normalized) : 'Client';
    }

    function getPreviewRecordNumberLabel(client) {
        return normalizePreviewText(client?.client_classification).toLowerCase() === 'agent'
            ? 'Agent Number'
            : 'Client Number';
    }

    function getPreviewDisplayName(client) {
        const clientType = normalizePreviewText(client?.client_type).toLowerCase();
        const classification = normalizePreviewText(client?.client_classification).toLowerCase();
        const firstName = normalizePreviewText(client?.first_name);
        const middleName = normalizePreviewText(client?.middle_name);
        const lastName = normalizePreviewText(client?.last_name);
        const personalName = [firstName, middleName, lastName].filter(Boolean).join(' ').trim();
        const companyName = normalizePreviewText(client?.client_name || client?.company_name);
        const contactPerson = normalizePreviewText(client?.contact_person);
        const fallbackName = normalizePreviewText(client?.reference_code) || 'N/A';

        if (classification === 'agent') {
            if (clientType === 'corporate' || clientType === 'obligee') {
                return companyName || contactPerson || personalName || fallbackName;
            }

            return personalName || companyName || contactPerson || fallbackName;
        }

        if (clientType === 'corporate' || clientType === 'obligee') {
            return companyName || contactPerson || personalName || fallbackName;
        }

        return personalName || companyName || contactPerson || fallbackName;
    }

    function formatPreviewValue(field, rawValue) {
        const text = normalizePreviewText(rawValue);
        if (!text) {
            return '';
        }

        const format = normalizePreviewText(field?.format).toLowerCase();

        if (format === 'clienttype') {
            return getClientTypeDisplayLabel(text);
        }

        if (format === 'classification') {
            return text.toLowerCase() === 'agent' ? 'Agent' : 'Client';
        }

        if (format === 'agenttype') {
            return text.toLowerCase() === 'sub_agent' ? 'Sub agent' : 'Agent';
        }

        if (format === 'businesstype') {
            return text.toLowerCase() === 'government' ? 'Government' : 'Private';
        }

        if (format === 'mailingaddresstype') {
            return humanizePreviewText(text);
        }

        if (format === 'gender' || format === 'status') {
            return capitalizePreviewText(text);
        }

        if (format === 'humanize' || format === 'idtype') {
            return humanizePreviewText(text);
        }

        return text;
    }

    function resolvePreviewFieldValue(client, field) {
        if (typeof field.value === 'function') {
            return field.value(client);
        }

        if (Array.isArray(field.keys)) {
            return getFirstPreviewValue(client, field.keys);
        }

        if (field.key) {
            return normalizePreviewText(client?.[field.key]);
        }

        return '';
    }

    function renderPreviewField(client, field) {
        const rawValue = resolvePreviewFieldValue(client, field);
        const formattedValue = formatPreviewValue(field, rawValue);

        if (!formattedValue) {
            return '';
        }

        const groupClass = field.fullWidth ? 'form-group full' : 'form-group';

        if (normalizePreviewText(field.type).toLowerCase() === 'textarea') {
            const rows = Math.max(2, parseInt(field.rows || 2, 10) || 2);
            return `
                <div class="${groupClass}">
                    <label class="form-label">${escapeHtml(field.label)}</label>
                    <textarea class="form-control" rows="${rows}" readonly>${escapeHtml(formattedValue)}</textarea>
                </div>`;
        }

        return `
            <div class="${groupClass}">
                <label class="form-label">${escapeHtml(field.label)}</label>
                <input type="text" class="form-control" readonly value="${escapeHtml(formattedValue)}">
            </div>`;
    }

    function renderPreviewSection(title, fields, client, extraClass = '') {
        const renderedFields = (Array.isArray(fields) ? fields : [])
            .map(field => renderPreviewField(client, field))
            .filter(Boolean);

        if (renderedFields.length === 0) {
            return '';
        }

        const sectionClasses = ['client-preview-section'];
        if (extraClass) {
            sectionClasses.push(extraClass);
        }

        return `
            <section class="${sectionClasses.join(' ')}">
                <div class="client-preview-section-title">${escapeHtml(title)}</div>
                <div class="form-row">
                    ${renderedFields.join('')}
                </div>
            </section>`;
    }

    function buildIndividualPreviewSections(client) {
        return [
            renderPreviewSection('Personal Information', [
                { label: 'Salutation', keys: ['salutation'] },
                { label: 'First Name', keys: ['first_name'] },
                { label: 'Middle Name', keys: ['middle_name'] },
                { label: 'Last Name', keys: ['last_name'] },
                { label: 'Suffix', keys: ['suffix'] },
                { label: 'Date of Birth', keys: ['date_of_birth'] },
                { label: 'Gender', keys: ['gender'], format: 'gender' },
                { label: 'Nationality', keys: ['nationality'] }
            ], client),
            renderPreviewSection('Account Details', [
                { label: 'Client Since', keys: ['client_since'] },
                { label: 'Occupation', keys: ['occupation'], format: 'humanize' },
                { label: 'Employer / Company', keys: ['company_name'] },
                { label: 'AP SL Code', keys: ['ap_sl_code'] },
                { label: 'AR SL Code', keys: ['ar_sl_code'] },
                { label: 'TIN Number', keys: ['tin_number'] }
            ], client),
            renderPreviewSection('Family Information', [
                { label: 'Spouse Name', keys: ['spouse_name'] },
                { label: 'Spouse Birthdate', keys: ['spouse_birthdate'] },
                { label: 'Spouse Occupation', keys: ['spouse_occupation'], format: 'humanize' }
            ], client),
            renderPreviewSection('Address Information', [
                { label: 'Home Address', keys: ['home_address'], type: 'textarea', fullWidth: true, rows: 2 },
                { label: 'Business Address', keys: ['business_address'], type: 'textarea', fullWidth: true, rows: 2 },
                { label: 'Mailing Address Type', keys: ['mailing_address_type'], format: 'mailingAddressType' },
                { label: 'Region', keys: ['region'] },
                { label: 'Home City / Municipality', keys: ['home_ctm'] },
                { label: 'Home Province', keys: ['home_province'] },
                { label: 'Full Address', keys: ['full_address'], type: 'textarea', fullWidth: true, rows: 2 }
            ], client),
            renderPreviewSection('Contact Information', [
                { label: 'Mobile Number', keys: ['mobile_phone'] },
                { label: 'Telephone', keys: ['landline_phone', 'office_phone'] },
                { label: 'Email Address', keys: ['email'] }
            ], client),
            renderPreviewSection('Government ID', [
                { label: 'Government ID Type', keys: ['id_type'], format: 'idType' },
                { label: 'ID Number', keys: ['id_number'] }
            ], client)
        ].filter(Boolean).join('');
    }

    function buildCorporatePreviewSections(client, isObligee = false) {
        const companySectionTitle = isObligee ? 'Government Agency Information' : 'Company Information';
        const companyNameLabel = isObligee ? 'Government Agency / Office Name' : 'Business / Company Name';
        const businessTypeLabel = isObligee ? 'Government Body Type' : 'Business Type';
        const clientSinceLabel = isObligee ? 'Date of Registration / Establishment' : 'Client Since';
        const detailsSectionTitle = isObligee ? 'Agency Details' : 'Business Details';
        const addressSectionTitle = isObligee ? 'Government Office Address' : 'Business Address';
        const addressLabel = isObligee ? 'Government Office Address' : 'Business Address';
        const regionLabel = isObligee ? 'Region / Jurisdiction' : 'Region';
        const provinceLabel = isObligee ? 'Province / Area' : 'Province';
        const contactPersonLabel = isObligee ? 'Authorized Contact Person' : 'Company Owner / Contact Person';
        const contactPhoneLabel = isObligee ? 'Agency Phone Number' : 'Phone Number';
        const designationLabel = isObligee ? 'Authorized Contact Position' : 'Contact Person Designation';
        const emailLabel = isObligee ? 'Official Email Address' : 'Email Address';

        return [
            renderPreviewSection(companySectionTitle, [
                { label: companyNameLabel, keys: ['client_name', 'company_name'], fullWidth: true },
                { label: businessTypeLabel, keys: ['business_type'], format: 'businessType' },
                { label: clientSinceLabel, keys: ['client_since'] },
                { label: 'Gender', keys: ['gender'], format: 'gender' },
                { label: 'Nationality', keys: ['nationality'] }
            ], client),
            renderPreviewSection(detailsSectionTitle, [
                { label: 'TIN Number', keys: ['tin_number'] },
                { label: 'AP SL Code', keys: ['ap_sl_code'] },
                { label: 'AR SL Code', keys: ['ar_sl_code'] },
                { label: designationLabel, keys: ['designation'] }
            ], client),
            renderPreviewSection(addressSectionTitle, [
                { label: addressLabel, keys: ['business_address'], type: 'textarea', fullWidth: true, rows: 2 },
                { label: 'City / Municipality', keys: ['business_ctm'] },
                { label: provinceLabel, keys: ['business_province'] },
                { label: regionLabel, keys: ['region'] },
                { label: 'Full Address', keys: ['full_address'], type: 'textarea', fullWidth: true, rows: 2 }
            ], client),
            renderPreviewSection('Contact Information', [
                { label: contactPhoneLabel, keys: ['office_phone', 'mobile_phone', 'landline_phone'] },
                { label: contactPersonLabel, keys: ['contact_person'] },
                { label: emailLabel, keys: ['email'] }
            ], client),
            renderPreviewSection('Government ID', [
                { label: 'Government ID Type', keys: ['id_type'], format: 'idType' },
                { label: 'ID Number', keys: ['id_number'] }
            ], client)
        ].filter(Boolean).join('');
    }

    function buildClientPreviewHtml(client) {
        const clientType = normalizePreviewText(client?.client_type).toLowerCase() || 'individual';
        const classification = normalizePreviewText(client?.client_classification).toLowerCase() || 'client';
        const recordNumberLabel = getPreviewRecordNumberLabel(client);
        const sections = [
            renderPreviewSection('Record Overview', [
                { label: 'Display Name', value: record => getPreviewDisplayName(record), fullWidth: true },
                { label: 'Reference Code', keys: ['reference_code'] },
                { label: recordNumberLabel, keys: ['client_number'] },
                { label: 'Submitted Branch', keys: ['submitted_by_branch'] },
                { label: 'Client Type', keys: ['client_type'], format: 'clientType' },
                { label: 'Classification', keys: ['client_classification'], format: 'classification' },
                { label: 'Verification Status', keys: ['verification_status'], format: 'status' },
                { label: 'Activity Status', keys: ['activity_status_display'], format: 'status' },
                { label: 'Status Updated At', keys: ['activity_status_updated_display'] }
            ], client, 'client-preview-summary')
        ];

        if (classification === 'agent') {
            sections.push(renderPreviewSection('Agent Details', [
                { label: 'Agent Type', keys: ['agent_type'], format: 'agentType' },
                { label: 'Agent Branch', keys: ['agent_branch'] },
                { label: 'Head Agent Name', keys: ['head_agent_name'] }
            ], client));
        }

        if (clientType === 'corporate') {
            sections.push(buildCorporatePreviewSections(client, false));
        } else if (clientType === 'obligee') {
            sections.push(buildCorporatePreviewSections(client, true));
        } else {
            sections.push(buildIndividualPreviewSections(client));
        }

        const html = sections.filter(Boolean).join('');
        return html || '<div class="client-preview-empty">No preview data available.</div>';
    }

    function getPreviewModalTitle(client) {
        const clientTypeLabel = getClientTypeDisplayLabel(client?.client_type);
        const classification = normalizePreviewText(client?.client_classification).toLowerCase();

        if (classification === 'agent') {
            return clientTypeLabel === 'Client'
                ? 'Agent Preview'
                : `${clientTypeLabel} Agent Preview`;
        }

        return `${clientTypeLabel} Client Preview`;
    }

    function formatTableDateOnly(value) {
        const text = normalizePreviewText(value);
        if (!text || text === 'N/A') {
            return 'N/A';
        }

        const normalized = text.includes('T') ? text : text.replace(' ', 'T');
        const parsedDate = new Date(normalized);

        if (!Number.isNaN(parsedDate.getTime())) {
            return parsedDate.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        }

        const dateMatch = text.match(/^(\d{4}-\d{2}-\d{2})/);
        if (dateMatch) {
            const fallbackDate = new Date(`${dateMatch[1]}T00:00:00`);
            if (!Number.isNaN(fallbackDate.getTime())) {
                return fallbackDate.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
            }
        }

        return text;
    }

    function updateBranchFilterOptions(branches) {
        if (!isHeadOfficeUser) {
            return;
        }

        const branchSelect = document.getElementById('filterBranch');
        if (!branchSelect) {
            return;
        }

        const currentValue = branchSelect.value;
        const uniqueBranches = Array.from(
            new Set(
                (Array.isArray(branches) ? branches : [])
                    .map(branch => String(branch || '').trim())
                    .filter(branch => branch !== '')
            )
        );

        let optionsHtml = '<option value="">All Branches</option>';
        uniqueBranches.forEach(branch => {
            const safeBranch = escapeHtml(branch);
            optionsHtml += `<option value="${safeBranch}">${safeBranch}</option>`;
        });

        branchSelect.innerHTML = optionsHtml;

        if (currentValue && uniqueBranches.includes(currentValue)) {
            branchSelect.value = currentValue;
        }
    }

    function setTableLoading(isLoading) {
        const wrapper = document.querySelector('.table-wrapper');
        if (!wrapper) return;
        wrapper.classList.toggle('is-loading', isLoading);
    }

    function setButtonBusy(button, isBusy, busyText = 'Working...') {
        if (!button) return;
        if (isBusy) {
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = `<span class="spinner" style="width:14px;height:14px;"></span> ${busyText}`;
            button.disabled = true;
        } else {
            button.disabled = false;
            if (button.dataset.originalText) {
                button.innerHTML = button.dataset.originalText;
                delete button.dataset.originalText;
            }
        }
    }

    function updateBulkDeleteButtonState() {
        if (!deleteSelectedBtn) {
            return;
        }

        deleteSelectedBtn.disabled = selectedClientIds.size === 0;
    }

    function getActiveFilters() {
        const branchSelect = document.getElementById('filterBranch');

        return {
            search: document.getElementById('searchInput').value.trim(),
            type: document.getElementById('filterType').value,
            activity: document.getElementById('filterActivity').value,
            sort: document.getElementById('sortOrder').value,
            branch: isHeadOfficeUser && branchSelect ? branchSelect.value : ''
        };
    }

    // Load clients from database on page load
    function loadClients(page = 1) {
        setTableLoading(true);
        const filters = getActiveFilters();
        const query = new URLSearchParams({
            page: page,
            pageSize: pageSize,
            search: filters.search,
            type: filters.type,
            activity: filters.activity,
            sort: filters.sort,
            branch: filters.branch,
            classification: listClassification
        });

        console.log('loadClients() starting for page:', page);
        fetch(`../handlers/get_clients.php?${query.toString()}`, {
            method: 'GET',
            credentials: 'include'  // Include session cookies in the request
        })
            .then(async response => {
                console.log('Response received:', response.status);
                const rawText = await response.text();
                try {
                    return JSON.parse(rawText);
                } catch (parseError) {
                    const preview = rawText.slice(0, 200).replace(/\s+/g, ' ').trim();
                    throw new Error(`Invalid JSON response from server (HTTP ${response.status}). ${preview}`);
                }
            })
            .then(data => {
                console.log('Data parsed:', data);
                if (data.success) {
                    console.log('Rendering ' + data.data.length + ' ' + recordLabelPlural);
                    updateBranchFilterOptions(data.availableBranches || []);
                    currentPage = data.page;
                    totalPages = data.totalPages;
                    totalClients = data.total;
                    currentPageClients = Array.isArray(data.data) ? data.data : [];
                    if (data.data && data.data.length > 0) {
                        renderClientsTable(data.data);
                        attachClientEventListeners();
                        syncSelectAllCheckbox();
                    } else {
                        document.getElementById('clientsTableBody').innerHTML = `<tr><td colspan="${clientTableColumnCount}" style="text-align: center; padding: 20px;">No ${recordLabelPlural} found</td></tr>`;
                        syncSelectAllCheckbox();
                    }

                    updatePaginationInfo(data);
                    generatePaginationButtons(data);
                } else {
                    currentPageClients = [];
                    console.log(`No ${recordLabelPlural} found or fetch failed`);
                    document.getElementById('clientsTableBody').innerHTML = `<tr><td colspan="${clientTableColumnCount}" style="text-align: center; padding: 20px;">No ${recordLabelPlural} found</td></tr>`;
                    updatePaginationInfo({ page: 1, total: 0, pageSize: pageSize, totalPages: 0 });
                    generatePaginationButtons({ page: 1, totalPages: 0 });
                }
            })
            .catch(error => {
                currentPageClients = [];
                console.error('Error loading records:', error);
                document.getElementById('clientsTableBody').innerHTML = `<tr><td colspan="${clientTableColumnCount}" style="text-align: center; padding: 20px; color: red;">Error loading ${recordLabelPlural}: ${error.message}</td></tr>`;
                syncSelectAllCheckbox();
            })
            .finally(() => {
                setTableLoading(false);
            });
    }

    // Render clients table with data from database
    function renderClientsTable(clients) {
        const tbody = document.getElementById('clientsTableBody');
        tbody.innerHTML = '';

        const formatClientType = (rawType) => {
            const normalizedType = (rawType || '').toLowerCase();
            if (normalizedType === 'individual') return 'Individual';
            if (normalizedType === 'corporate') return 'Corporate';
            if (normalizedType === 'obligee') return 'Obligee';
            return normalizedType ? normalizedType.charAt(0).toUpperCase() + normalizedType.slice(1) : 'N/A';
        };

        const formatAgentType = (rawType) => {
            const normalizedType = (rawType || '').toLowerCase();
            if (normalizedType === 'sub_agent') return 'Sub agent';
            if (normalizedType === 'agent') return 'Agent';
            return normalizedType ? normalizedType.charAt(0).toUpperCase() + normalizedType.slice(1) : 'Agent';
        };

        const isCorporateLike = (rawType) => {
            const normalizedType = (rawType || '').toLowerCase();
            return normalizedType === 'corporate' || normalizedType === 'obligee';
        };

        clients.forEach(client => {
            const normalizedType = (client.client_type || '').toLowerCase();
            const typeClass = normalizedType || 'corporate';
            const agentType = (client.agent_type || 'agent').toLowerCase();
            const typeText = isAgentsMode ? formatAgentType(client.agent_type) : formatClientType(client.client_type);
            const agentTypeClass = agentType || 'agent';
            const mainAgentName = (client.head_agent_name || '').trim();
            const displayName = `${client.first_name || ''} ${client.last_name || ''}`.trim() || client.client_name || 'N/A';
            const submittedBranch = client.submitted_by_branch || 'N/A';
            const submittedByName = client.submitted_by_name || 'N/A';
            const contactNumber = isCorporateLike(client.client_type)
                ? (client.office_phone || 'N/A')
                : (client.mobile_phone || 'N/A');
            const activityStatus = client.activity_status_display || 'Active';
            const activityStatusClass = normalizeActivityStatusClass(client.activity_status_class || 'active');
            const activityUpdatedAt = formatTableDateOnly(client.activity_status_updated_at || client.activity_status_updated_display || 'N/A');
            const mainAgentText = isAgentsMode
                ? (agentType === 'sub_agent' ? (mainAgentName !== '' ? mainAgentName : 'N/A') : 'None')
                : '';
            const typeCellHtml = isAgentsMode
                ? `<span class="type-badge ${escapeHtml(agentTypeClass)}">${escapeHtml(typeText)}</span>`
                : `<span class="type-badge ${typeClass}">${escapeHtml(typeText)}</span>`;
            const mainAgentCellHtml = isAgentsMode
                ? `<span class="agent-main-agent${mainAgentText === 'None' ? ' is-none' : ''}">${escapeHtml(mainAgentText)}</span>`
                : '';

            const row = document.createElement('tr');
            row.classList.add('row-enter');
            row.dataset.clientId = client.client_id;
            row.dataset.clientType = client.client_type || '';
            row.dataset.agentType = client.agent_type || '';
            row.dataset.headAgentName = client.head_agent_name || '';
            row.dataset.contactNumber = contactNumber;
            row.dataset.email = client.email || '';
            row.style.animationDelay = `${Math.min(tbody.children.length * 35, 220)}ms`;
            row.innerHTML = `
                <td class="col-checkbox"><input type="checkbox" class="row-select" data-client-id="${client.client_id}"></td>
                <td class="col-ref"><span class="ref-badge">${escapeHtml(client.reference_code || 'N/A')}</span></td>
                <td class="col-name">${escapeHtml(displayName)}</td>
                <td class="col-owner">${escapeHtml(submittedBranch)}</td>
                <td class="col-type">${typeCellHtml}</td>
                ${isAgentsMode ? `<td class="col-main-agent">${mainAgentCellHtml}</td>` : ''}
                <td class="col-contact">${escapeHtml(contactNumber)}</td>
                ${isAgentsMode ? '' : `<td class="col-email">${escapeHtml(client.email || 'N/A')}</td>`}
                <td class="col-verified">${escapeHtml(submittedByName)}</td>
                <td class="col-activity">
                    <div class="activity-cell">
                        <span class="activity-badge ${activityStatusClass}">${escapeHtml(activityStatus)}</span>
                    </div>
                </td>
                <td class="col-activity-updated">${escapeHtml(activityUpdatedAt)}</td>
                <td class="col-actions">
                    <div class="action-stack">
                        <button type="button" class="action-icon action-edit" title="Edit"><i class="bi bi-pencil"></i><span>Edit</span></button>
                        <button type="button" class="action-icon delete" title="Delete"><i class="bi bi-trash"></i><span>Delete</span></button>
                    </div>
                </td>
            `;

            const rowCheckbox = row.querySelector('.row-select');
            if (rowCheckbox) {
                rowCheckbox.checked = selectedClientIds.has(String(client.client_id));
            }

            tbody.appendChild(row);
        });

        syncSelectAllCheckbox();
        updateBulkDeleteButtonState();
    }

    function getCurrentPageClientById(clientId) {
        const id = String(clientId);
        return currentPageClients.find(client => String(client.client_id) === id) || null;
    }

    function updateSelection(clientId, isSelected) {
        const id = String(clientId);
        const client = getCurrentPageClientById(id);

        if (isSelected) {
            selectedClientIds.add(id);
            if (client) {
                selectedClientRows.set(id, mapClientToExportRow(client));
            }
        } else {
            selectedClientIds.delete(id);
            selectedClientRows.delete(id);
        }

        updateBulkDeleteButtonState();
    }

    function deleteClientRecord(clientId) {
        const formData = new FormData();
        formData.append('action', isAgentsMode ? 'delete_agent_record' : 'delete_client');
        formData.append('client_id', clientId);

        return fetch('../handlers/client.php', {
            method: 'POST',
            body: formData
        }).then(response => response.json());
    }

    async function deleteSelectedClients() {
        const selectedIds = Array.from(selectedClientIds);

        if (selectedIds.length === 0) {
            createToast('info', 'Nothing Selected', `Select one or more ${recordLabelPlural} first.`, 'toastContainer');
            return;
        }

        const confirmMessage = isAgentsMode
            ? `Delete ${selectedIds.length} selected ${recordLabelPlural}? This will remove the selected agent records from the agents table only.`
            : `Delete ${selectedIds.length} selected ${recordLabelPlural}? This will permanently remove the records and related approval data.`;

        const confirmed = await showConfirmModal({
            title: 'Confirm Delete',
            message: confirmMessage,
            confirmText: 'Delete Selected',
            cancelText: 'Cancel',
            variant: 'danger'
        });

        if (!confirmed) {
            return;
        }

        setButtonBusy(deleteSelectedBtn, true, 'Deleting...');

        let successCount = 0;
        let failureCount = 0;

        try {
            for (const clientId of selectedIds) {
                try {
                    const payload = await deleteClientRecord(clientId);
                    if (payload.success) {
                        successCount += 1;
                        selectedClientIds.delete(String(clientId));
                        selectedClientRows.delete(String(clientId));
                    } else {
                        failureCount += 1;
                    }
                } catch (error) {
                    failureCount += 1;
                }
            }

            updateBulkDeleteButtonState();

            if (successCount > 0) {
                const remainingTotal = Math.max(0, totalClients - successCount);
                const maxPageAfterDelete = Math.max(1, Math.ceil(remainingTotal / pageSize));
                const targetPage = Math.min(currentPage, maxPageAfterDelete);

                createToast('success', 'Deleted', `${successCount} selected ${recordLabelPlural} deleted.`, 'toastContainer');
                loadClients(targetPage);
            }

            if (failureCount > 0) {
                createToast('error', 'Delete Failed', `${failureCount} selected ${recordLabelPlural} could not be deleted.`, 'toastContainer');
            }
        } finally {
            setButtonBusy(deleteSelectedBtn, false);
        }
    }

    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', deleteSelectedClients);
    }

    function syncSelectAllCheckbox() {
        const selectAll = document.getElementById('selectAll');
        if (!selectAll) return;

        const rowCheckboxes = document.querySelectorAll('#clientsTableBody .row-select');
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

    // Update pagination info
    function updatePaginationInfo(data) {
        const start = document.querySelector('.info-start');
        const end = document.querySelector('.info-end');
        const totalEl = document.querySelector('.info-total');
        
        const startRecord = data.total > 0 ? ((data.page - 1) * data.pageSize) + 1 : 0;
        const endRecord = Math.min(data.page * data.pageSize, data.total);
        
        if (start) start.textContent = startRecord;
        if (end) end.textContent = endRecord;
        if (totalEl) totalEl.textContent = data.total;
    }

    // Generate pagination buttons dynamically
    function generatePaginationButtons(data) {
        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';

        if (!data.totalPages || data.totalPages <= 0) {
            return;
        }

        const maxButtons = 5;
        let startPage = Math.max(1, data.page - 2);
        let endPage = Math.min(data.totalPages, startPage + maxButtons - 1);
        
        // Adjust if we're at the end
        if (endPage - startPage < maxButtons - 1) {
            startPage = Math.max(1, endPage - maxButtons + 1);
        }

        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.className = 'pagination-btn';
        prevBtn.innerHTML = '<i class="bi bi-chevron-left"></i>';
        prevBtn.disabled = data.page === 1;
        prevBtn.addEventListener('click', () => {
            if (data.page > 1) {
                loadClients(data.page - 1);
            }
        });
        container.appendChild(prevBtn);

        // Page buttons
        for (let i = startPage; i <= endPage; i++) {
            const btn = document.createElement('button');
            btn.className = 'pagination-btn';
            if (i === data.page) btn.classList.add('active');
            btn.textContent = i;
            btn.addEventListener('click', () => loadClients(i));
            container.appendChild(btn);
        }

        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = 'pagination-btn';
        nextBtn.innerHTML = '<i class="bi bi-chevron-right"></i>';
        nextBtn.disabled = data.page === data.totalPages || data.totalPages === 0;
        nextBtn.addEventListener('click', () => {
            if (data.page < data.totalPages) {
                loadClients(data.page + 1);
            }
        });
        container.appendChild(nextBtn);
    }

    // Attach event listeners to dynamically loaded rows
    function attachClientEventListeners() {
        document.querySelectorAll('#clientsTableBody tr').forEach(row => {
            const editBtn = row.querySelector('.action-icon[title="Edit"]');
            const deleteBtn = row.querySelector('.action-icon.delete');

            row.addEventListener('click', function(event) {
                if (event.target.closest('.row-select, .action-icon, button, a, input, label, select, textarea')) {
                    return;
                }

                const clientId = row.dataset.clientId;
                if (clientId) {
                    viewClient({ clientId });
                }
            });

            if (editBtn) {
                editBtn.addEventListener('click', function() {
                    const data = getClientDataFromRow(row);
                    editClient(data);
                });
            }

            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    const clientId = row.dataset.clientId;
                    openDeleteClientModal(clientId, row);
                });
            }

            const rowCheckbox = row.querySelector('.row-select');
            if (rowCheckbox) {
                rowCheckbox.addEventListener('change', function() {
                    updateSelection(this.dataset.clientId, this.checked);
                    syncSelectAllCheckbox();
                });
            }
        });
    }

    // Load clients on page load
    document.addEventListener('DOMContentLoaded', () => {
        const filterType = document.getElementById('filterType');
        if (filterType && initialTypeFilter) {
            filterType.value = initialTypeFilter;
        }

        loadClients(1);
    });

    // Get client data from row
    function getClientDataFromRow(row) {
        const cells = row.querySelectorAll('td');
        const displayName = cells[2].textContent.trim();
        const nameParts = displayName.split(' ');
        const agentType = (row.dataset.agentType || '').trim();
        const headAgentName = (row.dataset.headAgentName || '').trim();
        return {
            clientId: row.dataset.clientId,
            refCode: cells[1].textContent.trim(),
            firstName: nameParts[0] || '',
            lastName: nameParts.length > 1 ? nameParts[nameParts.length - 1] : '',
            displayName: displayName,
            submittedBranch: cells[3].textContent.trim(),
            type: agentType || cells[4].textContent.trim(),
            agentType: agentType,
            headAgentName: headAgentName,
            contact: (row.dataset.contactNumber || cells[5]?.textContent || '').trim(),
            email: (row.dataset.email || (isAgentsMode ? '' : cells[6]?.textContent || '')).trim()
        };
    }

    // Modal functionality
    const editModal = document.getElementById('editModal');
    const viewModal = document.getElementById('viewModal');
    const viewModalTitle = document.getElementById('viewModalTitle');
    const viewClientDetails = document.getElementById('viewClientDetails');
    const cancelBtn = document.getElementById('cancelBtn');
    const editModalCloseBtn = document.getElementById('editModalCloseBtn');
    const editActivityStatusButtons = document.querySelectorAll('.activity-status-toggle');
    const deleteConfirmModal = document.getElementById('deleteConfirmModal');
    const deleteConfirmRefCode = document.getElementById('deleteConfirmRefCode');
    const deleteConfirmName = document.getElementById('deleteConfirmName');
    const deleteCancelBtn = document.getElementById('deleteCancelBtn');
    const deleteConfirmBtn = document.getElementById('deleteConfirmBtn');
    const deleteModalCloseBtn = document.getElementById('deleteModalCloseBtn');

    function setDeleteModalOpen(isOpen) {
        if (!deleteConfirmModal) {
            return;
        }

        deleteConfirmModal.style.display = isOpen ? 'block' : 'none';
        deleteConfirmModal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    }

    function closeDeleteClientModal() {
        pendingDeleteClient = null;
        setDeleteModalOpen(false);
    }

    function openDeleteClientModal(clientId, row) {
        if (!clientId || !row || !deleteConfirmModal) {
            createToast('error', 'Error', `Unable to identify selected ${recordLabelSingular}.`, 'toastContainer');
            return;
        }

        const refCode = (row.querySelector('.col-ref span')?.textContent || '').trim() || 'N/A';
        const clientName = (row.querySelector('.col-name')?.textContent || '').trim() || recordTitleCaseSingular;

        pendingDeleteClient = {
            clientId,
            row,
            clientName,
            refCode
        };

        if (deleteConfirmRefCode) {
            deleteConfirmRefCode.textContent = refCode;
        }

        if (deleteConfirmName) {
            deleteConfirmName.textContent = clientName;
        }

        setDeleteModalOpen(true);
    }

    // View Client Function
    function viewClient(data) {
        if (!data || !data.clientId) {
            createToast('error', 'Error', `Unable to identify selected ${recordLabelSingular}.`, 'toastContainer');
            return;
        }

        fetch(`../handlers/client.php?action=get_client&client_id=${encodeURIComponent(data.clientId)}`, {
            method: 'GET',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(result => {
            if (!result.success || !result.data) {
                createToast('error', 'Error', result.message || `Failed to load ${recordLabelSingular} details.`, 'toastContainer');
                return;
            }

            const client = result.data;

            document.getElementById('viewClientId').value = client.client_id || '';
            if (viewModalTitle) {
                viewModalTitle.textContent = getPreviewModalTitle(client);
            }

            if (viewClientDetails) {
                viewClientDetails.innerHTML = buildClientPreviewHtml(client);
            }

            viewModal.style.display = 'block';
        })
        .catch(error => {
            createToast('error', 'Error', `Failed to load ${recordLabelSingular} details.`, 'toastContainer');
            console.error('Error loading details:', error);
        });
    }

    // Edit Client Function
    function editClient(data) {
        currentEditingClientId = data.clientId;

        if (!currentEditingClientId) {
            createToast('error', 'Error', `Unable to identify selected ${recordLabelSingular}.`, 'toastContainer');
            return;
        }

        fetch(`../handlers/client.php?action=get_client&client_id=${encodeURIComponent(currentEditingClientId)}`, {
            method: 'GET',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(result => {
            if (!result.success || !result.data) {
                createToast('error', 'Error', result.message || `Failed to load ${recordLabelSingular} details.`, 'toastContainer');
                return;
            }

            const client = result.data;
            const fallbackName = (client.client_name || '').trim();

            document.getElementById('editClientId').value = client.client_id || '';
            document.getElementById('editRefCode').value = client.reference_code || '';
            document.getElementById('editSubmittedBranch').value = client.submitted_by_branch || 'N/A';
            if (isAgentsMode) {
                const clientTypeField = document.getElementById('editClientType');
                if (clientTypeField) {
                    clientTypeField.value = client.client_type || 'individual';
                }

                const agentTypeField = document.getElementById('editAgentType');
                if (agentTypeField) {
                    agentTypeField.value = (client.agent_type || 'agent').toLowerCase();
                }

                const headAgentField = document.getElementById('editHeadAgentName');
                if (headAgentField) {
                    headAgentField.value = client.head_agent_name || '';
                }

                syncEditAgentFields();
            } else {
                document.getElementById('editClientType').value = client.client_type || 'individual';
            }
            document.getElementById('editFirstName').value = client.first_name || fallbackName;
            document.getElementById('editMiddleName').value = client.middle_name || '';
            document.getElementById('editLastName').value = client.last_name || '';
            document.getElementById('editBirthdate').value = client.date_of_birth || '';
            document.getElementById('editGender').value = (client.gender || '').toLowerCase();
            document.getElementById('editCivilStatus').value = client.civil_status || 'Single';
            document.getElementById('editOccupation').value = client.occupation || '';
            document.getElementById('editNationality').value = client.nationality || '';
            document.getElementById('editTin').value = client.tin_number || '';
            document.getElementById('editEmail').value = client.email || '';
            document.getElementById('editMobile').value = client.mobile_phone || client.office_phone || '';
            document.getElementById('editTelephone').value = client.landline_phone || client.office_phone || '';
            document.getElementById('editAddress').value = client.full_address || client.home_address || client.business_address || '';
            setEditActivityStatus(client.activity_status_class || client.activity_status_display || 'active');
            document.getElementById('editActivityStatusUpdatedAt').value = client.activity_status_updated_display || 'N/A';

            editModal.style.display = 'block';
        })
        .catch(error => {
            createToast('error', 'Error', `Failed to load ${recordLabelSingular} details.`, 'toastContainer');
            console.error('Error loading details:', error);
        });
    }

    function saveClientChanges() {
        const saveBtn = document.getElementById('saveBtn');
        const clientId = document.getElementById('editClientId').value;
        if (!clientId) {
            createToast('error', 'Error', 'No client selected for update.', 'toastContainer');
            return;
        }

        setButtonBusy(saveBtn, true, 'Saving...');

        const formData = new FormData();
        formData.append('action', 'edit_client');
        formData.append('client_id', clientId);
        formData.append('firstName', document.getElementById('editFirstName').value.trim());
        formData.append('middleName', document.getElementById('editMiddleName').value.trim());
        formData.append('lastName', document.getElementById('editLastName').value.trim());
        formData.append('email', document.getElementById('editEmail').value.trim());
        formData.append('mobile', document.getElementById('editMobile').value.trim());
        formData.append('occupation', document.getElementById('editOccupation').value.trim());
        formData.append('address', document.getElementById('editAddress').value.trim());
        formData.append('activityStatus', document.getElementById('editActivityStatus').value.trim());
        formData.append('clientType', document.getElementById('editClientType').value);
        if (isAgentsMode) {
            const agentTypeField = document.getElementById('editAgentType');
            const headAgentField = document.getElementById('editHeadAgentName');
            const agentType = agentTypeField ? agentTypeField.value : 'agent';
            const headAgentName = headAgentField ? headAgentField.value.trim() : '';

            if (agentType === 'sub_agent' && headAgentName === '') {
                createToast('error', 'Validation Error', 'Head Agent Name is required for Sub agent.', 'toastContainer');
                setButtonBusy(saveBtn, false);
                return;
            }

            formData.append('agentType', agentType);
            formData.append('headAgentName', headAgentName);
        }

        fetch('../handlers/client.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                createToast('error', 'Error', result.message || 'Failed to save changes.', 'toastContainer');
                return;
            }

            const updatedStatusValue = result.activity_status_updated_display || result.activity_status_updated_at || '';
            if (updatedStatusValue) {
                const updatedAtField = document.getElementById('editActivityStatusUpdatedAt');
                if (updatedAtField) {
                    updatedAtField.value = updatedStatusValue;
                }
            }

            editModal.style.display = 'none';
            createToast('success', 'Updated', `${recordTitleCaseSingular} information saved successfully.`, 'toastContainer');
            loadClients(currentPage);
        })
        .catch(error => {
            createToast('error', 'Error', `Failed to save ${recordLabelSingular} changes.`, 'toastContainer');
            console.error('Error saving record:', error);
        })
        .finally(() => {
            setButtonBusy(saveBtn, false);
        });
    }

    // Delete Client Function
    function deleteClient(clientId, row, clientNameOverride = '') {
        if (!clientId) {
            createToast('error', 'Error', `Unable to identify selected ${recordLabelSingular}.`, 'toastContainer');
            return;
        }
        
        // Extract client name for reference
        const clientName = clientNameOverride || (row.querySelector('.col-name')?.textContent || 'Client').trim();
        
        const deleteBtn = row.querySelector('.action-icon.delete');
        setButtonBusy(deleteBtn, true, '');
        
        deleteClientRecord(clientId)
        .then(data => {
            if (data.success) {
                selectedClientIds.delete(String(clientId));
                selectedClientRows.delete(String(clientId));
                const remainingTotal = Math.max(0, totalClients - 1);
                const maxPageAfterDelete = Math.max(1, Math.ceil(remainingTotal / pageSize));
                const targetPage = Math.min(currentPage, maxPageAfterDelete);

                createToast('success', 'Deleted', clientName + ' has been removed.', 'toastContainer');
                updateBulkDeleteButtonState();
                loadClients(targetPage);
            } else {
                createToast('error', 'Error', data.message || `Failed to delete ${recordLabelSingular}.`, 'toastContainer');
            }
        })
        .catch(error => {
            createToast('error', 'Error', 'An error occurred.', 'toastContainer');
            console.error('Error:', error);
        })
        .finally(() => {
            setButtonBusy(deleteBtn, false);
        });
    }

    // Create Toast helper function
    function createToast(type, title, msg, containerId) {
        const icons = { 
            success: 'bi-check-circle-fill', 
            error: 'bi-x-circle-fill', 
            info: 'bi-info-circle-fill' 
        };
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <i class="bi ${icons[type]} toast-icon"></i>
            <div class="toast-body">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${msg}</div>
            </div>
            <i class="bi bi-x toast-close" onclick="removeToast(this.parentElement)"></i>`;
        
        let container = document.getElementById(containerId);
        if (!container) {
            container = document.createElement('div');
            container.id = containerId;
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        container.appendChild(toast);
        setTimeout(() => removeToast(toast), 4000);
        return toast;
    }

    function removeToast(el) {
        el.classList.add('out');
        setTimeout(() => el.remove(), 250);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            editModal.style.display = 'none';
        });
    }

    if (editModalCloseBtn) {
        editModalCloseBtn.addEventListener('click', function() {
            editModal.style.display = 'none';
        });
    }

    editActivityStatusButtons.forEach(button => {
        button.addEventListener('click', function() {
            setEditActivityStatus(this.dataset.status || 'active');
        });
    });

    if (deleteCancelBtn) {
        deleteCancelBtn.addEventListener('click', closeDeleteClientModal);
    }

    if (deleteModalCloseBtn) {
        deleteModalCloseBtn.addEventListener('click', closeDeleteClientModal);
    }

    if (deleteConfirmBtn) {
        deleteConfirmBtn.addEventListener('click', function() {
            if (!pendingDeleteClient) {
                setDeleteModalOpen(false);
                return;
            }

            const target = pendingDeleteClient;
            setDeleteModalOpen(false);
            pendingDeleteClient = null;
            deleteClient(target.clientId, target.row, target.clientName);
        });
    }

    document.getElementById('saveBtn').addEventListener('click', saveClientChanges);
    if (isAgentsMode) {
        const agentTypeField = document.getElementById('editAgentType');
        if (agentTypeField) {
            agentTypeField.addEventListener('change', syncEditAgentFields);
        }
    }

    window.addEventListener('click', function(event) {
        if (event.target === editModal) {
            editModal.style.display = 'none';
        }
        if (event.target === viewModal) {
            viewModal.style.display = 'none';
        }
        if (event.target === deleteConfirmModal) {
            closeDeleteClientModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && deleteConfirmModal && deleteConfirmModal.style.display === 'block') {
            closeDeleteClientModal();
        }
    });

    // Select All functionality
    document.getElementById('selectAll').addEventListener('change', function() {
        document.querySelectorAll('#clientsTableBody .row-select').forEach(checkbox => {
            checkbox.checked = this.checked;
            updateSelection(checkbox.dataset.clientId, checkbox.checked);
        });

        this.indeterminate = false;
        updateBulkDeleteButtonState();
    });

    function applyServerFilters() {
        loadClients(1);
    }

    document.getElementById('searchInput').addEventListener('keyup', function() {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            applyServerFilters();
        }, 300);
    });

    document.getElementById('filterType').addEventListener('change', applyServerFilters);

    document.getElementById('filterActivity').addEventListener('change', applyServerFilters);

    document.getElementById('sortOrder').addEventListener('change', applyServerFilters);

    const filterBranchEl = document.getElementById('filterBranch');
    if (filterBranchEl) {
        filterBranchEl.addEventListener('change', applyServerFilters);
    }

    // Export list functionality
    let exportData = [];
    let exportScopeLabel = `Filtered ${recordLabelPlural}`;
    const exportLogoUrl = new URL('../../public/images/SterlingLogo2.png', window.location.href).href;

    const exportHeaders = isAgentsMode
        ? ['Ref Code', `Business / ${recordTitleCaseSingular} Name`, 'Submitted Branch', 'Agent Type', 'Main Agent', 'Contact', 'Client Number', 'Submitted By']
        : ['Ref Code', `Business / ${recordTitleCaseSingular} Name`, 'Submitted Branch', 'Type', 'Contact', 'Email', 'Client Number', 'Submitted By'];

    function getFilterSummaryText() {
        const filters = getActiveFilters();
        const parts = [];

        const formatClientType = (rawType) => {
            const normalizedType = (rawType || '').toLowerCase();
            if (normalizedType === 'individual') return 'Individual';
            if (normalizedType === 'corporate') return 'Corporate';
            if (normalizedType === 'obligee') return 'Obligee';
            return normalizedType ? normalizedType.charAt(0).toUpperCase() + normalizedType.slice(1) : 'N/A';
        };

        const formatAgentType = (rawType) => {
            const normalizedType = (rawType || '').toLowerCase();
            if (normalizedType === 'sub_agent') return 'Sub agent';
            if (normalizedType === 'agent') return 'Agent';
            return normalizedType ? normalizedType.charAt(0).toUpperCase() + normalizedType.slice(1) : 'Agent';
        };

        if (filters.search) {
            parts.push(`Search: ${filters.search}`);
        }
        if (filters.type) {
            parts.push(`Type: ${isAgentsMode ? formatAgentType(filters.type) : formatClientType(filters.type)}`);
        }
        if (filters.activity) {
            const activityLabels = {
                active: 'Active',
                inactive: 'Inactive',
                deactivated: 'Deactivated'
            };
            parts.push(`Activity: ${activityLabels[filters.activity] || filters.activity}`);
        }
        if (filters.branch) {
            parts.push(`Branch: ${filters.branch}`);
        }
        if (filters.sort) {
            const sortLabels = {
                created_desc: 'Latest Added',
                alphabetical_asc: 'Alphabetical A-Z',
                alphabetical_desc: 'Alphabetical Z-A',
                updated_asc: 'Time Updated: Oldest First',
                updated_desc: 'Time Updated: Newest First'
            };
            parts.push(`Sort: ${sortLabels[filters.sort] || filters.sort}`);
        }

        return parts.length > 0 ? parts.join(' | ') : 'No filters applied';
    }

    function mapClientToExportRow(client) {
        const displayName = `${client.first_name || ''} ${client.last_name || ''}`.trim() || client.client_name || 'N/A';
        const normalizedType = (client.client_type || '').toLowerCase();
        const normalizedAgentType = (client.agent_type || '').toLowerCase();
        const mainAgentName = (client.head_agent_name || '').trim();
        const isCorporateLike = normalizedType === 'corporate' || normalizedType === 'obligee';
        const submittedBranch = client.submitted_by_branch || 'N/A';

        let typeText = 'N/A';
        if (isAgentsMode) {
            if (normalizedAgentType === 'sub_agent') typeText = 'Sub agent';
            else typeText = 'Agent';
        } else {
            if (normalizedType === 'individual') typeText = 'Individual';
            if (normalizedType === 'corporate') typeText = 'Corporate';
            if (normalizedType === 'obligee') typeText = 'Obligee';
        }

        const mainAgentText = isAgentsMode
            ? (normalizedAgentType === 'sub_agent' && mainAgentName !== '' ? mainAgentName : 'None')
            : 'N/A';

        const contactNumber = isCorporateLike
            ? (client.office_phone || 'N/A')
            : (client.mobile_phone || 'N/A');

        return {
            refCode: client.reference_code || 'N/A',
            displayName: displayName,
            submittedBranch: submittedBranch,
            type: typeText || 'N/A',
            mainAgent: mainAgentText,
            contact: contactNumber,
            email: client.email || 'N/A',
            clientNumber: client.client_number || 'N/A',
            submittedBy: client.submitted_by_name || 'N/A'
        };
    }

    function getExportRowValues(row) {
        const values = [
            row.refCode,
            row.displayName,
            row.submittedBranch,
            row.type
        ];

        if (isAgentsMode) {
            values.push(row.mainAgent || 'N/A');
            values.push(row.contact, row.clientNumber, row.submittedBy);
            return values;
        }

        values.push(row.contact, row.email, row.clientNumber, row.submittedBy);
        return values;
    }

    function getSelectedExportData() {
        const orderedIds = Array.from(selectedClientIds);
        return orderedIds
            .map(id => selectedClientRows.get(id))
            .filter(Boolean);
    }

    async function getServerExportData() {
        const filters = getActiveFilters();
        const query = new URLSearchParams({
            page: '1',
            pageSize: String(pageSize),
            exportAll: '1'
        });

        query.set('search', filters.search);
        query.set('type', filters.type);
        query.set('branch', filters.branch);
        query.set('classification', listClassification);

        const response = await fetch(`../handlers/get_clients.php?${query.toString()}`, {
            method: 'GET',
            credentials: 'include'
        });

        const payload = await response.json();
        if (!payload.success) {
            throw new Error(payload.message || 'Failed to load export data');
        }

        return (payload.data || []).map(mapClientToExportRow);
    }

    async function resolveExportPayload() {
        const selectedRows = getSelectedExportData();

        if (selectedRows.length > 0) {
            return {
                data: selectedRows,
                scope: 'selected',
                label: `Selected ${recordLabelPlural} only (checked rows)`
            };
        }

        const filteredRows = await getServerExportData();
        return {
            data: filteredRows,
            scope: 'filtered',
            label: `Filtered ${recordLabelPlural} (${getFilterSummaryText()})`
        };
    }

    async function renderExportPreview() {
        const previewContent = document.getElementById('previewContent');
        const resolved = await resolveExportPayload();
        const data = resolved.data;
        const safeGeneratedAt = escapeHtml(new Date().toLocaleString());

        if (data.length === 0) {
            exportData = [];
            exportScopeLabel = resolved.label;
            previewContent.innerHTML = `
                <div class="export-preview-empty-state">
                    <strong>No ${escapeHtml(recordLabelPlural)} found</strong>
                </div>`;
            return;
        }

        // Build HTML preview table
        let html = '<div class="export-preview-shell">';
        html += `
            <div class="export-preview-brand">
                <img class="export-preview-brand-logo" src="${exportLogoUrl}" alt="Sterling logo">
                <div class="export-preview-brand-copy">
                    <div class="export-preview-kicker">Sterling Insurance Company Incorporated</div>
                    <h2>${escapeHtml(recordTitleCasePlural)} Management Report</h2>
                </div>
            </div>`;
        html += '<table class="export-preview-table">';
        html += '<thead><tr>';

        exportHeaders.forEach(header => {
            html += `<th>${escapeHtml(header)}</th>`;
        });
        html += '</tr></thead><tbody>';
        
        data.forEach((row, index) => {
            html += `<tr class="${index % 2 === 0 ? 'is-even' : 'is-odd'}">`;
            getExportRowValues(row).forEach(value => {
                html += `<td>${escapeHtml(value)}</td>`;
            });
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        html += `<div class="export-preview-footer">`;
        html += `<p><strong>Total Records:</strong> ${data.length}</p>`;
        html += `<p><strong>Export Date:</strong> ${safeGeneratedAt}</p>`;
        html += `</div>`;
        html += `</div>`;

        exportData = data;
        exportScopeLabel = resolved.label;
        previewContent.innerHTML = html;
    }

    async function showExportPreview() {
        const modal = document.getElementById('exportPreviewModal');
        const exportBtn = document.querySelector('.btn-export');

        setButtonBusy(exportBtn, true, 'Preparing...');

        try {
            await renderExportPreview();
            modal.style.display = 'block';
        } catch (error) {
            createToast('error', 'Error', error.message || 'Failed to prepare export.', 'toastContainer');
        } finally {
            setButtonBusy(exportBtn, false);
        }
    }

    function exportAsCSV() {
        if (exportData.length === 0) return;
        const rows = [];

        rows.push(exportHeaders.join(','));

        // Add data rows
        exportData.forEach(row => {
            const cells = getExportRowValues(row).map(cell => {
                let content = cell.replace(/\s+/g, ' ').replace(/,/g, ';');
                if (content.includes(',') || content.includes('"')) {
                    content = '"' + content.replace(/"/g, '""') + '"';
                }
                return content;
            });
            rows.push(cells.join(','));
        });

        const csvContent = rows.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', `${recordLabelPlural}_export_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function exportAsPDF() {
        if (exportData.length === 0) return;

        const element = document.getElementById('previewContent');
        const opt = {
            margin: 10,
            filename: `${recordLabelPlural}_export_${new Date().toISOString().split('T')[0]}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { orientation: 'landscape', unit: 'mm', format: 'a4' }
        };

        html2pdf().set(opt).from(element).save();
    }

    function printReport() {
        if (exportData.length === 0) return;

        const printWindow = window.open('', '_blank');
        const content = document.getElementById('previewContent').innerHTML;
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Sterling insurance Company Incorporated</title>
    <link rel='icon' type='image/png' href='../css/images/SterlingLogo.png'>
                <style>
                    :root { color-scheme: light; }
                    * { box-sizing: border-box; }
                    body {
                        margin: 0;
                        padding: 18px;
                        font-family: 'Sora', Arial, sans-serif;
                        font-size: 9pt;
                        line-height: 1.3;
                        color: #173226;
                        background: #ffffff;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    .print-sheet {
                        display: flex;
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 14px;
                    }
                    .export-preview-brand {
                        display: flex;
                        flex-direction: row;
                        align-items: flex-start;
                        justify-content: flex-start;
                        gap: 14px;
                        padding: 0;
                        margin: 0 0 50px;
                        text-align: left;
                    }
                    .export-preview-brand-logo {
                        width: 176px;
                        height: auto;
                        display: block;
                        flex: 0 0 auto;
                        object-fit: contain;
                        object-position: center;
                    }
                    .export-preview-brand-copy {
                        min-width: 0;
                        padding: 2px 0 0;
                    }
                    .export-preview-kicker {
                        font-size: 9pt;
                        font-weight: 600;
                        letter-spacing: 0.12em;
                        text-transform: uppercase;
                        color: #6b7f76;
                        white-space: normal;
                        line-height: 1.1;
                        text-align: left;
                        word-break: break-word;
                    }
                    .export-preview-brand-copy h2 {
                        margin: 0;
                        padding: 0;
                        font-size: 9pt;
                        font-weight: 700;
                        line-height: 1.15;
                        color: #116a3a;
                        text-align: left;
                    }
                    .export-preview-footer {
                        border-radius: 0;
                        border: 1px solid #d8e9df;
                        font-size: 9pt;
                        text-overflow: clip;
                        white-space: normal;
                        overflow-wrap: anywhere;
                        word-break: break-word;
                    }
                    .export-preview-table {
                        width: 100%;
                        border-collapse: collapse;
                        table-layout: fixed;
                        border: 1px solid #cfe4d6;
                        border-radius: 0;
                        overflow: hidden;
                    }
                    .export-preview-table thead th {
                        padding: 10px 8px;
                        background: #2f7a54;
                        color: #ffffff;
                        text-align: center;
                        font-size: 9pt;
                        font-weight: 700;
                        letter-spacing: 0.03em;
                        border-right: 1px solid rgba(255, 255, 255, 0.14);
                        vertical-align: middle;
                    }
                    .export-preview-table thead th:last-child { border-right: none; }
                    .export-preview-table tbody td {
                        padding: 9px 8px;
                        font-size: 9pt;
                        color: #244236;
                        border-top: 1px solid #e2eee6;
                        border-right: 1px solid #edf5ef;
                        text-align: center;
                        vertical-align: middle;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    }
                    .export-preview-table tbody td:last-child { border-right: none; }
                    .export-preview-footer {
                        display: grid;
                        grid-template-columns: repeat(2, minmax(0, 1fr));
                        gap: 10px;
                        padding: 12px 14px;
                        font-size: 9pt;
                    }
                    .export-preview-footer p { margin: 0; }
                    .export-preview-empty-state {
                        padding: 22px 18px;
                        border-radius: 14px;
                        border: 1px dashed #c9ded2;
                        background: #fbfdfb;
                        color: #4f635a;
                        display: flex;
                        flex-direction: column;
                        gap: 4px;
                        align-items: center;
                        justify-content: center;
                        text-align: center;
                    }
                    .export-preview-empty-state strong { color: #116a3a; }
                    @page {
                        size: portrait;
                        margin: 10mm;
                    }
                    @media print {
                        body { padding: 0; }
                        .print-sheet { gap: 10px; }
                        .export-preview-brand,
                        .export-preview-footer {
                            break-inside: avoid;
                            page-break-inside: avoid;
                        }
                        .export-preview-brand,
                        .export-preview-table,
                        .export-preview-footer,
                        .export-preview-empty-state {
                            border-radius: 0;
                        }
                        .export-preview-table {
                            width: 100%;
                            min-width: 0;
                            table-layout: auto;
                        }
                        .export-preview-table th:nth-child(5),
                        .export-preview-table td:nth-child(5) {
                            white-space: nowrap;
                            overflow-wrap: normal;
                            word-break: normal;
                            min-width: 10px;
                            font-variant-numeric: tabular-nums;
                        }
                        .export-preview-table th:nth-child(4),
                        .export-preview-table td:nth-child(4) {
                            white-space: nowrap;
                            overflow-wrap: normal;
                            word-break: normal;
                            min-width: 90px;
                        }
                        .export-preview-table thead th,
                        .export-preview-table tbody td {
                            white-space: normal;
                            overflow: visible;
                            text-overflow: clip;
                            overflow-wrap: anywhere;
                            word-break: break-word;
                        }
                        .export-preview-table thead th {
                            font-size: 9pt;
                        }
                        .export-preview-table tbody td {
                            background: #ffffff;
                            font-size: 9pt;
                            padding: 7px 6px;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="print-sheet">
                    ${content}
                </div>
            </body>
            </html>
        `);
        
        printWindow.document.close();
        setTimeout(() => {
            printWindow.print();
        }, 250);
    }

</script>

</body>
</html>
