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
                            <th class="col-name">Full Name</th>
                            <th class="col-type">Type</th>
                            <th class="col-contact">Contact</th>
                            <th class="col-email">Email</th>
                            <th class="col-status">Status</th>
                            <th class="col-verified">Verified By</th>
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
                <!-- Row 1: Reference & Type -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Ref Code</label>
                        <input type="text" class="form-control" value="KYC-2024-0001" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Client Type</label>
                        <select class="form-select">
                            <option>Individual</option>
                            <option>Corporate</option>
                        </select>
                    </div>
                </div>

                <!-- Row 2: Name -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" value="Juan">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Middle Name</label>
                        <input type="text" class="form-control" value="Santos">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" value="Dela Cruz">
                    </div>
                </div>

                <!-- Row 3: Personal Details -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Birthdate</label>
                        <input type="date" class="form-control" value="1990-05-15">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select class="form-select">
                            <option>Male</option>
                            <option>Female</option>
                            <option>Prefer not to say</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Civil Status</label>
                        <select class="form-select">
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
                        <input type="text" class="form-control" value="Engineer">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nationality</label>
                        <input type="text" class="form-control" value="Filipino">
                    </div>
                    <div class="form-group">
                        <label class="form-label">TIN / Tax ID</label>
                        <input type="text" class="form-control" value="000-000-000-000">
                    </div>
                </div>

                <!-- Row 5: Contact Information -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" value="juan@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mobile Number</label>
                        <input type="tel" class="form-control" value="+63 912 345 6789">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telephone</label>
                        <input type="tel" class="form-control" value="(02) 8123-4567">
                    </div>
                </div>

                <!-- Row 6: Address -->
                <div class="form-row">
                    <div class="form-group full">
                        <label class="form-label">Present Address</label>
                        <input type="text" class="form-control" value="123 Main St, Barangay San Juan, Manila, NCR 1500">
                    </div>
                </div>

                <!-- Row 7: Status -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Verification Status</label>
                        <select class="form-select">
                            <option>Verified</option>
                            <option>Pending</option>
                            <option>Rejected</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" id="cancelBtn">Cancel</button>
            <button class="btn-save">Save Changes</button>
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

    // Load clients from database on page load
    function loadClients(page = 1) {
        console.log('loadClients() starting for page:', page);
        fetch(`../handlers/get_clients.php?page=${page}&pageSize=${pageSize}`, {
            method: 'GET',
            credentials: 'include'  // Include session cookies in the request
        })
            .then(response => {
                console.log('Response received:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Data parsed:', data);
                if (data.success && data.data && data.data.length > 0) {
                    console.log('Rendering ' + data.data.length + ' clients');
                    currentPage = data.page;
                    totalPages = data.totalPages;
                    totalClients = data.total;
                    renderClientsTable(data.data);
                    attachClientEventListeners();
                    updatePaginationInfo(data);
                    generatePaginationButtons(data);
                } else {
                    console.log('No clients found or fetch failed');
                    document.getElementById('clientsTableBody').innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 20px;">No clients found</td></tr>';
                    updatePaginationInfo({ page: 1, total: 0, pageSize: 6, totalPages: 0 });
                    generatePaginationButtons({ page: 1, totalPages: 0 });
                }
            })
            .catch(error => {
                console.error('Error loading clients:', error);
                document.getElementById('clientsTableBody').innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 20px; color: red;">Error loading clients: ' + error.message + '</td></tr>';
            });
    }

    // Render clients table with data from database
    function renderClientsTable(clients) {
        const tbody = document.getElementById('clientsTableBody');
        tbody.innerHTML = '';

        clients.forEach(client => {
            const statusClass = client.verification_status === 'verified' ? 'verified' : 
                               client.verification_status === 'pending' ? 'pending' : 'rejected';
            const statusText = client.verification_status.charAt(0).toUpperCase() + client.verification_status.slice(1);
            const statusIcon = client.verification_status === 'verified' ? 'bi-check-circle' : 
                              client.verification_status === 'pending' ? 'bi-hourglass-split' : 'bi-x-circle';
            const typeClass = client.client_type === 'individual' ? 'individual' : 'corporate';
            const typeText = client.client_type.charAt(0).toUpperCase() + client.client_type.slice(1);
            const fullName = `${client.first_name} ${client.last_name}`.trim();
            const verifiedByName = client.verified_by_name || 'N/A';

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="col-checkbox"><input type="checkbox"></td>
                <td class="col-ref"><span class="ref-badge">${client.reference_code}</span></td>
                <td class="col-name">${fullName}</td>
                <td class="col-type"><span class="type-badge ${typeClass}">${typeText}</span></td>
                <td class="col-contact">${client.mobile_phone || 'N/A'}</td>
                <td class="col-email">${client.email}</td>
                <td class="col-status"><span class="status-badge ${statusClass}"><i class="bi ${statusIcon}"></i> ${statusText}</span></td>
                <td class="col-verified">${verifiedByName}</td>
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
        // View Client
        document.querySelectorAll('.action-icon:not(.delete)').forEach((btn, index) => {
            if (index % 3 === 0) {
                // View
                btn.addEventListener('click', function(e) {
                    const row = this.closest('tr');
                    const data = getClientDataFromRow(row);
                    viewClient(data);
                });
            } else if (index % 3 === 1) {
                // Edit
                btn.addEventListener('click', function(e) {
                    const row = this.closest('tr');
                    const data = getClientDataFromRow(row);
                    editClient(data);
                });
            }
        });

        // Delete Client
        document.querySelectorAll('.action-icon.delete').forEach(btn => {
            btn.addEventListener('click', function() {
                const row = this.closest('tr');
                const refCode = row.querySelector('.col-ref span').textContent;
                deleteClient(refCode, row);
            });
        });
    }

    // Load clients on page load
    document.addEventListener('DOMContentLoaded', () => loadClients(1));

    // Get client data from row
    function getClientDataFromRow(row) {
        const cells = row.querySelectorAll('td');
        return {
            refCode: cells[1].textContent.trim(),
            firstName: cells[2].textContent.split(' ')[0],
            lastName: cells[2].textContent.split(' ').pop(),
            type: cells[3].textContent.trim(),
            contact: cells[4].textContent.trim(),
            email: cells[5].textContent.trim(),
            status: cells[6].textContent.trim(),
            verifiedBy: cells[7].textContent.trim()
        };
    }

    // Modal functionality
    const editModal = document.getElementById('editModal');
    const cancelBtn = document.getElementById('cancelBtn');

    // View Client
    document.querySelectorAll('.action-icon:not(.delete)').forEach((btn, index) => {
        // Only add event listener to first icon (View) and second icon (Edit)
        if (index % 3 === 0) {
            // View
            btn.addEventListener('click', function(e) {
                const row = this.closest('tr');
                const data = getClientDataFromRow(row);
                viewClient(data);
            });
        } else if (index % 3 === 1) {
            // Edit
            btn.addEventListener('click', function(e) {
                const row = this.closest('tr');
                const data = getClientDataFromRow(row);
                editClient(data);
            });
        }
    });

    // Delete Client
    document.querySelectorAll('.action-icon.delete').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const refCode = row.querySelector('.col-ref span').textContent;
            deleteClient(refCode, row);
        });
    });

    // View Client Function
    function viewClient(data) {
        alert('Client Details:\n\nRef Code: ' + data.refCode + '\nName: ' + data.firstName + ' ' + data.lastName + '\nType: ' + data.type + '\nEmail: ' + data.email + '\nContact: ' + data.contact);
    }

    // Edit Client Function
    function editClient(data) {
        // Populate edit modal with data
        // For now just show in edit modal
        editModal.style.display = 'block';
    }

    // Delete Client Function
    function deleteClient(refCode, row) {
        if (!confirm('Are you sure you want to delete this client?\n\n' + refCode)) {
            return;
        }
        
        // Extract client name for reference
        const clientName = row.querySelector('.col-name').textContent;
        
        const formData = new FormData();
        formData.append('action', 'delete_client');
        formData.append('client_id', refCode);
        
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

    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Export Clients functionality
    let exportData = [];

    function getTableData() {
        const table = document.querySelector('.clients-table');
        const data = [];
        
        // Add data rows (only visible rows)
        table.querySelectorAll('tbody tr').forEach(tr => {
            if (tr.style.display !== 'none') {
                const cells = [];
                let cellIndex = 0;
                tr.querySelectorAll('td').forEach(td => {
                    // Skip checkbox column
                    if (cellIndex === 0) {
                        cellIndex++;
                        return;
                    }
                    cellIndex++;
                    const content = td.textContent.trim();
                    cells.push(content);
                });
                if (cells.length > 0) {
                    data.push(cells);
                }
            }
        });
        return data;
    }

    function showExportPreview() {
        const table = document.querySelector('.clients-table');
        const modal = document.getElementById('exportPreviewModal');
        const previewContent = document.getElementById('previewContent');
        
        // Get visible rows
        const data = getTableData();
        
        if (data.length === 0) {
            alert('No clients to export!');
            return;
        }

        // Build HTML preview table
        let html = '<table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">';
        html += '<thead><tr style="background: #f3f4f6; border: 1px solid #d1d5db;">';
        
        const headers = ['Ref Code', 'Full Name', 'Type', 'Contact', 'Email', 'Status', 'Verified By', 'Actions'];
        headers.forEach(header => {
            html += `<th style="padding: 10px; text-align: left; border: 1px solid #d1d5db; font-weight: 600;">${header}</th>`;
        });
        html += '</tr></thead><tbody>';
        
        data.forEach((row, index) => {
            html += `<tr style="background: ${index % 2 === 0 ? '#ffffff' : '#f9fafb'}; border: 1px solid #d1d5db;">`;
            row.forEach(cell => {
                html += `<td style="padding: 10px; border: 1px solid #d1d5db;">${cell}</td>`;
            });
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        html += `<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #d1d5db; font-size: 0.85rem; color: #6b7280;">`;
        html += `<p><strong>Total Records:</strong> ${data.length}</p>`;
        html += `<p><strong>Export Date:</strong> ${new Date().toLocaleString()}</p>`;
        html += `</div>`;

        previewContent.innerHTML = html;
        exportData = data;
        modal.style.display = 'block';
    }

    function exportAsCSV() {
        if (exportData.length === 0) return;

        const table = document.querySelector('.clients-table');
        const rows = [];
        
        // Add header row
        const headers = [];
        table.querySelectorAll('thead th').forEach(th => {
            const text = th.textContent.trim();
            if (text && text !== '') {
                headers.push(text);
            }
        });
        rows.push(headers.slice(0, -2).join(','));

        // Add data rows
        exportData.forEach(row => {
            const cells = row.map(cell => {
                let content = cell.replace(/\s+/g, ' ').replace(/,/g, ';');
                if (content.includes(',') || content.includes('"')) {
                    content = '"' + content.replace(/"/g, '""') + '"';
                }
                return content;
            });
            rows.push(cells.slice(0, -1).join(','));
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
