<?php
require_once '../config/session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC System — Clients Management</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/clients.css">
    <link rel="stylesheet" href="../css/global.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>

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
            <button class="topbar-btn" title="Search"><i class="bi bi-search"></i></button>
            <button class="topbar-btn" title="Notifications">
                <i class="bi bi-bell"></i>
                <span class="notif-dot"></span>
            </button>
            <button class="topbar-btn" title="Help"><i class="bi bi-question-circle"></i></button>
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
                    <select id="filterStatus" class="filter-select">
                        <option value="">All Status</option>
                        <option value="verified">Verified</option>
                        <option value="pending">Pending</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <select id="filterType" class="filter-select">
                        <option value="">All Types</option>
                        <option value="individual">Individual</option>
                        <option value="corporate">Corporate</option>
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
                            <th class="col-name">Business / Client Name</th>
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
                    Showing <span class="info-start">1</span> to <span class="info-end">6</span> of <span class="info-total">0</span> clients
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
            <button class="modal-close" title="Close"><i class="bi bi-x"></i></button>
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

                <!-- Row 7: Status -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Verification Status</label>
                        <select id="editVerificationStatus" class="form-select" disabled>
                            <option value="verified">Verified</option>
                            <option value="pending">Pending</option>
                            <option value="rejected">Rejected</option>
                        </select>
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

<!-- ═══════════════════════════════════════════════ MODAL: Export Preview -->
<div id="exportPreviewModal" class="modal">
    <div class="modal-content" style="max-width: 900px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="modal-header">
            <h2>Export Clients Report</h2>
            <button class="modal-close" title="Close" onclick="document.getElementById('exportPreviewModal').style.display='none'"><i class="bi bi-x"></i></button>
        </div>
        <div class="modal-body" style="flex: 1; overflow-y: auto;">
            <div id="previewContent" style="background: white; padding: 20px; border-radius: 8px;"></div>
        </div>
        <div class="modal-footer" style="justify-content: space-between;">
            <button class="btn-cancel" onclick="document.getElementById('exportPreviewModal').style.display='none'">
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
    let pageSize = 6;
    let totalPages = 1;
    let totalClients = 0;
    let currentEditingClientId = null;
    let searchDebounceTimer = null;

    function getActiveFilters() {
        return {
            search: document.getElementById('searchInput').value.trim(),
            status: document.getElementById('filterStatus').value,
            type: document.getElementById('filterType').value
        };
    }

    // Load clients from database on page load
    function loadClients(page = 1) {
        const filters = getActiveFilters();
        const query = new URLSearchParams({
            page: page,
            pageSize: pageSize,
            search: filters.search,
            status: filters.status,
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
                    if (data.data && data.data.length > 0) {
                        renderClientsTable(data.data);
                        attachClientEventListeners();
                    } else {
                        document.getElementById('clientsTableBody').innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 20px;">No clients found</td></tr>';
                    }

                    updatePaginationInfo(data);
                    generatePaginationButtons(data);
                } else {
                    console.log('No clients found or fetch failed');
                    document.getElementById('clientsTableBody').innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 20px;">No clients found</td></tr>';
                    updatePaginationInfo({ page: 1, total: 0, pageSize: 6, totalPages: 0 });
                    generatePaginationButtons({ page: 1, totalPages: 0 });
                }
            })
            .catch(error => {
                console.error('Error loading clients:', error);
                document.getElementById('clientsTableBody').innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 20px; color: red;">Error loading clients: ' + error.message + '</td></tr>';
            });
    }

    // Render clients table with data from database
    function renderClientsTable(clients) {
        const tbody = document.getElementById('clientsTableBody');
        tbody.innerHTML = '';

        clients.forEach(client => {
            const typeClass = client.client_type === 'individual' ? 'individual' : 'corporate';
            const typeText = client.client_type.charAt(0).toUpperCase() + client.client_type.slice(1);
            const displayName = `${client.first_name || ''} ${client.last_name || ''}`.trim() || client.client_name || 'N/A';
            const ownerName = client.client_type === 'corporate'
                ? (client.contact_person || 'N/A')
                : 'N/A';
            const submittedByName = client.submitted_by_name || 'N/A';
            const clientNumber = client.client_number || 'N/A';
            const contactNumber = client.client_type === 'corporate'
                ? (client.office_phone || 'N/A')
                : (client.mobile_phone || 'N/A');

            const row = document.createElement('tr');
            row.dataset.clientId = client.client_id;
            row.dataset.clientType = client.client_type || '';
            row.dataset.status = client.verification_status || '';
            row.innerHTML = `
                <td class="col-checkbox"><input type="checkbox"></td>
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
            tbody.appendChild(row);
        });
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
    const cancelBtn = document.getElementById('cancelBtn');

    // View Client Function
    function viewClient(data) {
        alert('Client Details:\n\nRef Code: ' + data.refCode + '\nName: ' + data.displayName + '\nOwner: ' + data.ownerName + '\nType: ' + data.type + '\nEmail: ' + data.email + '\nContact: ' + data.contact);
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
            document.getElementById('editVerificationStatus').value = client.verification_status || 'pending';

            editModal.style.display = 'block';
        })
        .catch(error => {
            createToast('error', 'Error', 'Failed to load client details.', 'toastContainer');
            console.error('Error loading client details:', error);
        });
    }

    function saveClientChanges() {
        const clientId = document.getElementById('editClientId').value;
        if (!clientId) {
            createToast('error', 'Error', 'No client selected for update.', 'toastContainer');
            return;
        }

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
        
        fetch('../handlers/client.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
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

    cancelBtn.addEventListener('click', function() {
        editModal.style.display = 'none';
    });

    document.getElementById('saveBtn').addEventListener('click', saveClientChanges);

    window.addEventListener('click', function(event) {
        if (event.target === editModal) {
            editModal.style.display = 'none';
        }
    });

    // Select All functionality
    document.getElementById('selectAll').addEventListener('change', function() {
        document.querySelectorAll('tbody input[type="checkbox"]').forEach(checkbox => {
            checkbox.checked = this.checked;
        });
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

    document.getElementById('filterStatus').addEventListener('change', applyServerFilters);
    document.getElementById('filterType').addEventListener('change', applyServerFilters);

    // Export Clients functionality
    let exportData = [];

    const exportHeaders = ['Ref Code', 'Business / Client Name', 'Company Owner', 'Type', 'Contact', 'Email', 'Client Number', 'Submitted By'];

    function getFilterSummaryText() {
        const filters = getActiveFilters();
        const parts = [];

        if (filters.search) {
            parts.push(`Search: ${filters.search}`);
        }
        if (filters.status) {
            parts.push(`Status: ${filters.status.charAt(0).toUpperCase() + filters.status.slice(1)}`);
        }
        if (filters.type) {
            parts.push(`Type: ${filters.type.charAt(0).toUpperCase() + filters.type.slice(1)}`);
        }

        return parts.length > 0 ? parts.join(' | ') : 'No filters applied';
    }

    function mapClientToExportRow(client) {
        const displayName = `${client.first_name || ''} ${client.last_name || ''}`.trim() || client.client_name || 'N/A';
        const ownerName = client.client_type === 'corporate'
            ? (client.contact_person || 'N/A')
            : 'N/A';
        const typeText = (client.client_type || '').charAt(0).toUpperCase() + (client.client_type || '').slice(1);
        const contactNumber = client.client_type === 'corporate'
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

    async function getExportData() {
        const filters = getActiveFilters();
        const query = new URLSearchParams({
            page: '1',
            pageSize: String(pageSize),
            search: filters.search,
            status: filters.status,
            type: filters.type,
            exportAll: '1'
        });

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

    async function showExportPreview() {
        const modal = document.getElementById('exportPreviewModal');
        const previewContent = document.getElementById('previewContent');

        let data = [];
        try {
            data = await getExportData();
        } catch (error) {
            createToast('error', 'Error', error.message || 'Failed to prepare export.', 'toastContainer');
            return;
        }
        
        if (data.length === 0) {
            alert('No clients to export!');
            return;
        }

        // Build HTML preview table
        let html = '<table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">';
        html += `<caption style="caption-side: top; text-align: left; margin-bottom: 8px; font-size: 0.85rem; color: #4b5563;"><strong>Active Filters:</strong> ${getFilterSummaryText()}</caption>`;
        html += '<thead><tr style="background: #f3f4f6; border: 1px solid #d1d5db;">';

        exportHeaders.forEach(header => {
            html += `<th style="padding: 10px; text-align: left; border: 1px solid #d1d5db; font-weight: 600;">${header}</th>`;
        });
        html += '</tr></thead><tbody>';
        
        data.forEach((row, index) => {
            html += `<tr style="background: ${index % 2 === 0 ? '#ffffff' : '#f9fafb'}; border: 1px solid #d1d5db;">`;
            html += `<td style="padding: 10px; border: 1px solid #d1d5db;">${row.refCode}</td>`;
            html += `<td style="padding: 10px; border: 1px solid #d1d5db;">${row.displayName}</td>`;
            html += `<td style="padding: 10px; border: 1px solid #d1d5db;">${row.ownerName}</td>`;
            html += `<td style="padding: 10px; border: 1px solid #d1d5db;">${row.type}</td>`;
            html += `<td style="padding: 10px; border: 1px solid #d1d5db;">${row.contact}</td>`;
            html += `<td style="padding: 10px; border: 1px solid #d1d5db;">${row.email}</td>`;
            html += `<td style="padding: 10px; border: 1px solid #d1d5db;">${row.clientNumber}</td>`;
            html += `<td style="padding: 10px; border: 1px solid #d1d5db;">${row.submittedBy}</td>`;
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        html += `<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #d1d5db; font-size: 0.85rem; color: #6b7280;">`;
        html += `<p><strong>Total Records:</strong> ${data.length}</p>`;
        html += `<p><strong>Active Filters:</strong> ${getFilterSummaryText()}</p>`;
        html += `<p><strong>Export Date:</strong> ${new Date().toLocaleString()}</p>`;
        html += `</div>`;

        previewContent.innerHTML = html;
        exportData = data;
        modal.style.display = 'block';
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
                <title>Clients Report - KYC System</title>
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
