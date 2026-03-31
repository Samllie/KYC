<?php
require_once '../config/session.php';
requireLogin();
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
<body class="clients-page">

<?php
$activePage = 'clients';
include '../includes/sidebar.php';
?>

<!-- ═══════════════════════════════════════════════ MAIN -->
<div class="main">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <h1>Clients Management</h1>
            <div class="breadcrumb-trail">
                <i class="bi bi-house" style="font-size:.65rem;"></i>
                Dashboard &rsaquo; <span>Clients</span>
            </div>
        </div>
        <div class="topbar-right">

        </div>
    </header>

    <!-- Content -->
    <main class="content">

        <!-- Table Controls -->
        <div class="table-controls">
            <div class="controls-left">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" placeholder="Search clients..." class="search-input">
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
            <div class="controls-right">
                <button class="btn-export" title="Export" onclick="showExportPreview()">
                    <i class="bi bi-download"></i> Export
                </button>
                <button class="btn-add-client" title="Add New Client" onclick="window.location.href='kyc-verification.php'">
                    <i class="bi bi-plus-circle"></i> New Client
                </button>
            </div>
        </div>

        <!-- Clients Table -->
        <div class="card">
            <div class="table-wrapper">
                <table class="clients-table">
                    <thead>
                        <tr>
                            <th class="col-checkbox"><input type="checkbox" id="selectAll"></th>
                            <th class="col-ref">Ref Code</th>
                            <th class="col-name">Business/Client Name</th>
                            <th class="col-owner">Company Owner</th>
                            <th class="col-type">Type</th>
                            <th class="col-contact">Contact</th>
                            <th class="col-email">Email</th>
                            <th class="col-status">Client Number</th>
                            <th class="col-verified">Submitted By</th>
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
                    Showing <span class="info-start">1</span> to <span class="info-end">8</span> of <span class="info-total">0</span> clients
                </div>
                <div class="pagination" id="paginationContainer">
                    <!-- Pagination buttons will be generated dynamically -->
                </div>
            </div>
        </div>

    </main>

</div>

<!-- ═══════════════════════════════════════════════ MODAL: Edit Client -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Client Information</h2>
            <button id="editModalCloseBtn" type="button" class="modal-close" title="Close"><i class="bi bi-x"></i></button>
        </div>
        <div class="modal-body">
            <form id="editForm">
                <input type="hidden" id="editClientId">
                <!-- Row 1: Reference & Type -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Ref Code</label>
                        <input type="text" id="editRefCode" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Client Type</label>
                        <select id="editClientType" class="form-select">
                            <option value="individual">Individual</option>
                            <option value="corporate">Corporate</option>
                            <option value="obligee">Obligee</option>
                        </select>
                    </div>
                </div>

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
            <h2>Client Preview</h2>
            <button class="modal-close" title="Close" onclick="document.getElementById('viewModal').style.display='none'"><i class="bi bi-x"></i></button>
        </div>
        <div class="modal-body">
            <form id="viewForm">
                <input type="hidden" id="viewClientId">

                <!-- Row 1: Reference & Number -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Ref Code</label>
                        <input type="text" id="viewRefCode" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Client Number</label>
                        <input type="text" id="viewClientNumber" class="form-control" readonly>
                    </div>
                </div>

                <!-- Row 2: Type -->
                <div class="form-row">
                    <div class="form-group full">
                        <label class="form-label">Client Type</label>
                        <select id="viewClientType" class="form-select" disabled>
                            <option value="individual">Individual</option>
                            <option value="corporate">Corporate</option>
                            <option value="obligee">Obligee</option>
                        </select>
                    </div>
                </div>

                <!-- Row 3: Name -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" id="viewFirstName" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Middle Name</label>
                        <input type="text" id="viewMiddleName" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" id="viewLastName" class="form-control" readonly>
                    </div>
                </div>

                <!-- Row 4: Personal Details -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Birthdate</label>
                        <input type="date" id="viewBirthdate" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select id="viewGender" class="form-select" disabled>
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Prefer not to say</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Civil Status</label>
                        <select id="viewCivilStatus" class="form-select" disabled>
                            <option>Single</option>
                            <option>Married</option>
                            <option>Widowed</option>
                            <option>Separated</option>
                        </select>
                    </div>
                </div>

                <!-- Row 5: Additional Details -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Occupation</label>
                        <input type="text" id="viewOccupation" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nationality</label>
                        <input type="text" id="viewNationality" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">TIN / Tax ID</label>
                        <input type="text" id="viewTin" class="form-control" readonly>
                    </div>
                </div>

                <!-- Row 6: Contact Information -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" id="viewEmail" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mobile Number</label>
                        <input type="tel" id="viewMobile" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telephone</label>
                        <input type="tel" id="viewTelephone" class="form-control" readonly>
                    </div>
                </div>

                <!-- Row 7: Address -->
                <div class="form-row">
                    <div class="form-group full">
                        <label class="form-label">Present Address</label>
                        <input type="text" id="viewAddress" class="form-control" readonly>
                    </div>
                </div>
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
            <h2>Export Clients Report</h2>
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

<script>
    // Pagination state
    let currentPage = 1;
    let pageSize = 8;
    let totalPages = 1;
    let totalClients = 0;
    let currentEditingClientId = null;
    let searchDebounceTimer = null;
    let currentPageClients = [];
    const selectedClientIds = new Set();
    const selectedClientRows = new Map();

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

    function getActiveFilters() {
        return {
            search: document.getElementById('searchInput').value.trim(),
            type: document.getElementById('filterType').value
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
            type: filters.type
        });

        console.log('loadClients() starting for page:', page);
        fetch(`../handlers/get_clients.php?${query.toString()}`, {
            method: 'GET',
            credentials: 'include'  // Include session cookies in the request
        })
            .then(response => {
                console.log('Response received:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Data parsed:', data);
                if (data.success) {
                    console.log('Rendering ' + data.data.length + ' clients');
                    currentPage = data.page;
                    totalPages = data.totalPages;
                    totalClients = data.total;
                    currentPageClients = Array.isArray(data.data) ? data.data : [];
                    if (data.data && data.data.length > 0) {
                        renderClientsTable(data.data);
                        attachClientEventListeners();
                        syncSelectAllCheckbox();
                    } else {
                        document.getElementById('clientsTableBody').innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 20px;">No clients found</td></tr>';
                        syncSelectAllCheckbox();
                    }

                    updatePaginationInfo(data);
                    generatePaginationButtons(data);
                } else {
                    currentPageClients = [];
                    console.log('No clients found or fetch failed');
                    document.getElementById('clientsTableBody').innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 20px;">No clients found</td></tr>';
                    updatePaginationInfo({ page: 1, total: 0, pageSize: pageSize, totalPages: 0 });
                    generatePaginationButtons({ page: 1, totalPages: 0 });
                }
            })
            .catch(error => {
                currentPageClients = [];
                console.error('Error loading clients:', error);
                document.getElementById('clientsTableBody').innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 20px; color: red;">Error loading clients: ' + error.message + '</td></tr>';
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

        const isCorporateLike = (rawType) => {
            const normalizedType = (rawType || '').toLowerCase();
            return normalizedType === 'corporate' || normalizedType === 'obligee';
        };

        clients.forEach(client => {
            const normalizedType = (client.client_type || '').toLowerCase();
            const typeClass = normalizedType || 'corporate';
            const typeText = formatClientType(client.client_type);
            const displayName = `${client.first_name || ''} ${client.last_name || ''}`.trim() || client.client_name || 'N/A';
            const ownerName = isCorporateLike(client.client_type)
                ? (client.contact_person || 'N/A')
                : 'N/A';
            const submittedByName = client.submitted_by_name || 'N/A';
            const clientNumber = client.client_number || 'N/A';
            const contactNumber = isCorporateLike(client.client_type)
                ? (client.office_phone || 'N/A')
                : (client.mobile_phone || 'N/A');

            const row = document.createElement('tr');
            row.classList.add('row-enter');
            row.dataset.clientId = client.client_id;
            row.dataset.clientType = client.client_type || '';
            row.style.animationDelay = `${Math.min(tbody.children.length * 35, 220)}ms`;
            row.innerHTML = `
                <td class="col-checkbox"><input type="checkbox" class="row-select" data-client-id="${client.client_id}"></td>
                <td class="col-ref"><span class="ref-badge">${client.reference_code}</span></td>
                <td class="col-name">${displayName}</td>
                <td class="col-owner">${ownerName}</td>
                <td class="col-type"><span class="type-badge ${typeClass}">${typeText}</span></td>
                <td class="col-contact">${contactNumber}</td>
                <td class="col-email">${client.email}</td>
                <td class="col-status">${clientNumber}</td>
                <td class="col-verified">${submittedByName}</td>
                <td class="col-actions">
                    <button class="action-icon" title="View"><i class="bi bi-eye"></i></button>
                    <button class="action-icon" title="Edit"><i class="bi bi-pencil"></i></button>
                    <button class="action-icon delete" title="Delete"><i class="bi bi-trash"></i></button>
                </td>
            `;

            const rowCheckbox = row.querySelector('.row-select');
            if (rowCheckbox) {
                rowCheckbox.checked = selectedClientIds.has(String(client.client_id));
            }

            tbody.appendChild(row);
        });
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
            const viewBtn = row.querySelector('.action-icon[title="View"]');
            const editBtn = row.querySelector('.action-icon[title="Edit"]');
            const deleteBtn = row.querySelector('.action-icon.delete');

            if (viewBtn) {
                viewBtn.addEventListener('click', function() {
                    const data = getClientDataFromRow(row);
                    viewClient(data);
                });
            }

            if (editBtn) {
                editBtn.addEventListener('click', function() {
                    const data = getClientDataFromRow(row);
                    editClient(data);
                });
            }

            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    const clientId = row.dataset.clientId;
                    deleteClient(clientId, row);
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
    document.addEventListener('DOMContentLoaded', () => loadClients(1));

    // Get client data from row
    function getClientDataFromRow(row) {
        const cells = row.querySelectorAll('td');
        const displayName = cells[2].textContent.trim();
        const nameParts = displayName.split(' ');
        return {
            clientId: row.dataset.clientId,
            refCode: cells[1].textContent.trim(),
            firstName: nameParts[0] || '',
            lastName: nameParts.length > 1 ? nameParts[nameParts.length - 1] : '',
            displayName: displayName,
            ownerName: cells[3].textContent.trim(),
            type: cells[4].textContent.trim(),
            contact: cells[5].textContent.trim(),
            email: cells[6].textContent.trim(),
            clientNumber: cells[7].textContent.trim()
        };
    }

    // Modal functionality
    const editModal = document.getElementById('editModal');
    const viewModal = document.getElementById('viewModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const editModalCloseBtn = document.getElementById('editModalCloseBtn');

    // View Client Function
    function viewClient(data) {
        if (!data || !data.clientId) {
            createToast('error', 'Error', 'Unable to identify selected client.', 'toastContainer');
            return;
        }

        fetch(`../handlers/client.php?action=get_client&client_id=${encodeURIComponent(data.clientId)}`, {
            method: 'GET',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(result => {
            if (!result.success || !result.data) {
                createToast('error', 'Error', result.message || 'Failed to load client details.', 'toastContainer');
                return;
            }

            const client = result.data;
            const fallbackName = (client.client_name || '').trim();

            document.getElementById('viewClientId').value = client.client_id || '';
            document.getElementById('viewRefCode').value = client.reference_code || '';
            document.getElementById('viewClientNumber').value = client.client_number || '';
            document.getElementById('viewClientType').value = client.client_type || 'individual';
            const viewVerificationStatusEl = document.getElementById('viewVerificationStatus');
            if (viewVerificationStatusEl) {
                viewVerificationStatusEl.value = client.verification_status || 'pending';
            }
            document.getElementById('viewFirstName').value = client.first_name || fallbackName;
            document.getElementById('viewMiddleName').value = client.middle_name || '';
            document.getElementById('viewLastName').value = client.last_name || '';
            document.getElementById('viewBirthdate').value = client.date_of_birth || '';
            document.getElementById('viewGender').value = (client.gender || '').toLowerCase();
            document.getElementById('viewCivilStatus').value = client.civil_status || 'Single';
            document.getElementById('viewOccupation').value = client.occupation || '';
            document.getElementById('viewNationality').value = client.nationality || '';
            document.getElementById('viewTin').value = client.tin_number || '';
            document.getElementById('viewEmail').value = client.email || '';
            document.getElementById('viewMobile').value = client.mobile_phone || client.office_phone || '';
            document.getElementById('viewTelephone').value = client.landline_phone || client.office_phone || '';
            document.getElementById('viewAddress').value = client.full_address || client.home_address || client.business_address || '';

            viewModal.style.display = 'block';
        })
        .catch(error => {
            createToast('error', 'Error', 'Failed to load client details.', 'toastContainer');
            console.error('Error loading client details:', error);
        });
    }

    // Edit Client Function
    function editClient(data) {
        currentEditingClientId = data.clientId;

        if (!currentEditingClientId) {
            createToast('error', 'Error', 'Unable to identify selected client.', 'toastContainer');
            return;
        }

        fetch(`../handlers/client.php?action=get_client&client_id=${encodeURIComponent(currentEditingClientId)}`, {
            method: 'GET',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(result => {
            if (!result.success || !result.data) {
                createToast('error', 'Error', result.message || 'Failed to load client details.', 'toastContainer');
                return;
            }

            const client = result.data;
            const fallbackName = (client.client_name || '').trim();

            document.getElementById('editClientId').value = client.client_id || '';
            document.getElementById('editRefCode').value = client.reference_code || '';
            document.getElementById('editClientType').value = client.client_type || 'individual';
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

            editModal.style.display = 'block';
        })
        .catch(error => {
            createToast('error', 'Error', 'Failed to load client details.', 'toastContainer');
            console.error('Error loading client details:', error);
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
        formData.append('clientType', document.getElementById('editClientType').value);

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

            editModal.style.display = 'none';
            createToast('success', 'Updated', 'Client information saved successfully.', 'toastContainer');
            loadClients(currentPage);
        })
        .catch(error => {
            createToast('error', 'Error', 'Failed to save client changes.', 'toastContainer');
            console.error('Error saving client:', error);
        })
        .finally(() => {
            setButtonBusy(saveBtn, false);
        });
    }

    // Delete Client Function
    function deleteClient(clientId, row) {
        if (!clientId) {
            createToast('error', 'Error', 'Unable to identify selected client.', 'toastContainer');
            return;
        }

        const refCode = row.querySelector('.col-ref span').textContent;
        if (!confirm('Are you sure you want to delete this client?\n\n' + refCode)) {
            return;
        }
        
        // Extract client name for reference
        const clientName = row.querySelector('.col-name').textContent;
        
        const formData = new FormData();
        formData.append('action', 'delete_client');
        formData.append('client_id', clientId);

        const deleteBtn = row.querySelector('.action-icon.delete');
        setButtonBusy(deleteBtn, true, '');
        
        fetch('../handlers/client.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                selectedClientIds.delete(String(clientId));
                selectedClientRows.delete(String(clientId));
                syncSelectAllCheckbox();

                // Show toast
                const toast = createToast('success', 'Deleted', clientName + ' has been removed.', 'toastContainer');
                // Fade out and remove row
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            } else {
                const toast = createToast('error', 'Error', data.message || 'Failed to delete client.', 'toastContainer');
            }
        })
        .catch(error => {
            const toast = createToast('error', 'Error', 'An error occurred.', 'toastContainer');
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

    document.getElementById('saveBtn').addEventListener('click', saveClientChanges);

    window.addEventListener('click', function(event) {
        if (event.target === editModal) {
            editModal.style.display = 'none';
        }
        if (event.target === viewModal) {
            viewModal.style.display = 'none';
        }
    });

    // Select All functionality
    document.getElementById('selectAll').addEventListener('change', function() {
        document.querySelectorAll('#clientsTableBody .row-select').forEach(checkbox => {
            checkbox.checked = this.checked;
            updateSelection(checkbox.dataset.clientId, checkbox.checked);
        });

        this.indeterminate = false;
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

    // Export Clients functionality
    let exportData = [];
    let exportScopeLabel = 'Filtered clients';

    const exportHeaders = ['Ref Code', 'Business / Client Name', 'Company Owner', 'Type', 'Contact', 'Email', 'Client Number', 'Submitted By'];

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

        if (filters.search) {
            parts.push(`Search: ${filters.search}`);
        }
        if (filters.type) {
            parts.push(`Type: ${formatClientType(filters.type)}`);
        }

        return parts.length > 0 ? parts.join(' | ') : 'No filters applied';
    }

    function mapClientToExportRow(client) {
        const displayName = `${client.first_name || ''} ${client.last_name || ''}`.trim() || client.client_name || 'N/A';
        const normalizedType = (client.client_type || '').toLowerCase();
        const isCorporateLike = normalizedType === 'corporate' || normalizedType === 'obligee';

        const ownerName = isCorporateLike
            ? (client.contact_person || 'N/A')
            : 'N/A';

        let typeText = 'N/A';
        if (normalizedType === 'individual') typeText = 'Individual';
        if (normalizedType === 'corporate') typeText = 'Corporate';
        if (normalizedType === 'obligee') typeText = 'Obligee';

        const contactNumber = isCorporateLike
            ? (client.office_phone || 'N/A')
            : (client.mobile_phone || 'N/A');

        return {
            refCode: client.reference_code || 'N/A',
            displayName: displayName,
            ownerName: ownerName,
            type: typeText || 'N/A',
            contact: contactNumber,
            email: client.email || 'N/A',
            clientNumber: client.client_number || 'N/A',
            submittedBy: client.submitted_by_name || 'N/A'
        };
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
                label: 'Selected clients only (checked rows)'
            };
        }

        const filteredRows = await getServerExportData();
        return {
            data: filteredRows,
            scope: 'filtered',
            label: `Filtered clients (${getFilterSummaryText()})`
        };
    }

    async function renderExportPreview() {
        const previewContent = document.getElementById('previewContent');
        const resolved = await resolveExportPayload();
        const data = resolved.data;

        if (data.length === 0) {
            exportData = [];
            exportScopeLabel = resolved.label;
            previewContent.innerHTML = `<div style="padding: 20px; color: #6b7280;"><strong>No clients found</strong> for ${resolved.label}.</div>`;
            return;
        }

        // Build HTML preview table
        let html = '<div class="export-preview-shell">';
        html += `<div class="export-preview-summary"><strong>Scope:</strong> ${resolved.label}</div>`;
        html += '<table class="export-preview-table">';
        html += '<thead><tr>';

        exportHeaders.forEach(header => {
            html += `<th>${header}</th>`;
        });
        html += '</tr></thead><tbody>';
        
        data.forEach((row, index) => {
            html += `<tr class="${index % 2 === 0 ? 'is-even' : 'is-odd'}">`;
            html += `<td>${row.refCode}</td>`;
            html += `<td>${row.displayName}</td>`;
            html += `<td>${row.ownerName}</td>`;
            html += `<td>${row.type}</td>`;
            html += `<td>${row.contact}</td>`;
            html += `<td>${row.email}</td>`;
            html += `<td>${row.clientNumber}</td>`;
            html += `<td>${row.submittedBy}</td>`;
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        html += `<div class="export-preview-footer">`;
        html += `<p><strong>Total Records:</strong> ${data.length}</p>`;
        html += `<p><strong>Scope:</strong> ${resolved.label}</p>`;
        html += `<p><strong>Export Date:</strong> ${new Date().toLocaleString()}</p>`;
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
            const cells = [
                row.refCode,
                row.displayName,
                row.ownerName,
                row.type,
                row.contact,
                row.email,
                row.clientNumber,
                row.submittedBy
            ].map(cell => {
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
        link.setAttribute('download', `clients_export_${new Date().toISOString().split('T')[0]}.csv`);
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
            filename: `clients_export_${new Date().toISOString().split('T')[0]}.pdf`,
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
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    h1 { text-align: center; color: #374151; margin-bottom: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    th { background: #f3f4f6; padding: 10px; text-align: left; border: 1px solid #d1d5db; font-weight: bold; }
                    td { padding: 10px; border: 1px solid #d1d5db; }
                    tr:nth-child(even) { background: #f9fafb; }
                    .footer { margin-top: 20px; font-size: 0.9rem; color: #6b7280; border-top: 1px solid #d1d5db; padding-top: 10px; }
                    @media print { body { margin: 0; padding: 10px; } }
                </style>
            </head>
            <body>
                <h1>Clients Management Report</h1>
                ${content}
                <div class="footer">
                    <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
                    <p><strong>Total Records:</strong> ${exportData.length}</p>
                    <p><strong>Scope:</strong> ${exportScopeLabel}</p>
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
