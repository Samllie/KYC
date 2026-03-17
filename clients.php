<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC System — Clients Management</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/clients.css">
</head>
<body>

<!-- ═══════════════════════════════════════════════ SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <a href="#" class="brand-logo">
            <div class="brand-icon"><i class="bi bi-shield-check"></i></div>
            <div class="brand-text">
                <span>STerling Insurance Company</span>
                <strong>KYC System</strong>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Main Menu</div>

        <a href="dashboard.php" class="nav-item">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>
        <a href="clients.php" class="nav-item active">
            <i class="bi bi-people"></i> Clients
            <span class="nav-badge">24</span>
        </a>
        <a href="kyc-verification.php" class="nav-item">
            <i class="bi bi-person-check"></i> KYC Verification
        </a>
        <a href="#" class="nav-item">
            <i class="bi bi-file-earmark-text"></i> Policy Issuance
        </a>

        <div class="nav-label">Analytics</div>

        <a href="#" class="nav-item">
            <i class="bi bi-bar-chart"></i> Reports
        </a>
        <a href="#" class="nav-item">
            <i class="bi bi-bell"></i> Notifications
            <span class="nav-badge">3</span>
        </a>
        <a href="#" class="nav-item">
            <i class="bi bi-gear"></i> Settings
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar">JD</div>
            <div class="user-info">
                <span>Juan Dela Cruz</span>
                <span>KYC Officer</span>
            </div>
            <i class="bi bi-three-dots-vertical" style="color:rgba(255,255,255,.35);margin-left:auto;"></i>
        </div>
    </div>
</aside>

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
                <button class="btn-export" title="Export">
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
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Sample Row 1 -->
                        <tr>
                            <td class="col-checkbox"><input type="checkbox"></td>
                            <td class="col-ref"><span class="ref-badge">KYC-2024-0001</span></td>
                            <td class="col-name">Juan Dela Cruz</td>
                            <td class="col-type"><span class="type-badge individual">Individual</span></td>
                            <td class="col-contact">+63 912 345 6789</td>
                            <td class="col-email">juan@example.com</td>
                            <td class="col-status"><span class="status-badge verified"><i class="bi bi-check-circle"></i> Verified</span></td>
                            <td class="col-actions">
                                <button class="action-icon" title="View"><i class="bi bi-eye"></i></button>
                                <button class="action-icon" title="Edit"><i class="bi bi-pencil"></i></button>
                                <button class="action-icon delete" title="Delete"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>

                        <!-- Sample Row 2 -->
                        <tr>
                            <td class="col-checkbox"><input type="checkbox"></td>
                            <td class="col-ref"><span class="ref-badge">KYC-2024-0002</span></td>
                            <td class="col-name">Maria Garcia</td>
                            <td class="col-type"><span class="type-badge individual">Individual</span></td>
                            <td class="col-contact">+63 921 654 3210</td>
                            <td class="col-email">maria@example.com</td>
                            <td class="col-status"><span class="status-badge pending"><i class="bi bi-hourglass-split"></i> Pending</span></td>
                            <td class="col-actions">
                                <button class="action-icon" title="View"><i class="bi bi-eye"></i></button>
                                <button class="action-icon" title="Edit"><i class="bi bi-pencil"></i></button>
                                <button class="action-icon delete" title="Delete"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>

                        <!-- Sample Row 3 -->
                        <tr>
                            <td class="col-checkbox"><input type="checkbox"></td>
                            <td class="col-ref"><span class="ref-badge">KYC-2024-0003</span></td>
                            <td class="col-name">Robert Santos</td>
                            <td class="col-type"><span class="type-badge corporate">Corporate</span></td>
                            <td class="col-contact">(02) 8123-4567</td>
                            <td class="col-email">robert@company.com</td>
                            <td class="col-status"><span class="status-badge verified"><i class="bi bi-check-circle"></i> Verified</span></td>
                            <td class="col-actions">
                                <button class="action-icon" title="View"><i class="bi bi-eye"></i></button>
                                <button class="action-icon" title="Edit"><i class="bi bi-pencil"></i></button>
                                <button class="action-icon delete" title="Delete"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>

                        <!-- Sample Row 4 -->
                        <tr>
                            <td class="col-checkbox"><input type="checkbox"></td>
                            <td class="col-ref"><span class="ref-badge">KYC-2024-0004</span></td>
                            <td class="col-name">Angela Torres</td>
                            <td class="col-type"><span class="type-badge individual">Individual</span></td>
                            <td class="col-contact">+63 938 765 4321</td>
                            <td class="col-email">angela@example.com</td>
                            <td class="col-status"><span class="status-badge rejected"><i class="bi bi-x-circle"></i> Rejected</span></td>
                            <td class="col-actions">
                                <button class="action-icon" title="View"><i class="bi bi-eye"></i></button>
                                <button class="action-icon" title="Edit"><i class="bi bi-pencil"></i></button>
                                <button class="action-icon delete" title="Delete"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>

                        <!-- Sample Row 5 -->
                        <tr>
                            <td class="col-checkbox"><input type="checkbox"></td>
                            <td class="col-ref"><span class="ref-badge">KYC-2024-0005</span></td>
                            <td class="col-name">John Reyes</td>
                            <td class="col-type"><span class="type-badge individual">Individual</span></td>
                            <td class="col-contact">+63 945 678 9012</td>
                            <td class="col-email">john@example.com</td>
                            <td class="col-status"><span class="status-badge verified"><i class="bi bi-check-circle"></i> Verified</span></td>
                            <td class="col-actions">
                                <button class="action-icon" title="View"><i class="bi bi-eye"></i></button>
                                <button class="action-icon" title="Edit"><i class="bi bi-pencil"></i></button>
                                <button class="action-icon delete" title="Delete"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>

                        <!-- Sample Row 6 -->
                        <tr>
                            <td class="col-checkbox"><input type="checkbox"></td>
                            <td class="col-ref"><span class="ref-badge">KYC-2024-0006</span></td>
                            <td class="col-name">Luisa Cruz</td>
                            <td class="col-type"><span class="type-badge individual">Individual</span></td>
                            <td class="col-contact">+63 956 234 5678</td>
                            <td class="col-email">luisa@example.com</td>
                            <td class="col-status"><span class="status-badge pending"><i class="bi bi-hourglass-split"></i> Pending</span></td>
                            <td class="col-actions">
                                <button class="action-icon" title="View"><i class="bi bi-eye"></i></button>
                                <button class="action-icon" title="Edit"><i class="bi bi-pencil"></i></button>
                                <button class="action-icon delete" title="Delete"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="table-footer">
                <div class="pagination-info">
                    Showing <span class="info-start">1</span> to <span class="info-end">6</span> of <span class="info-total">24</span> clients
                </div>
                <div class="pagination">
                    <button class="pagination-btn" disabled><i class="bi bi-chevron-left"></i></button>
                    <button class="pagination-btn active">1</button>
                    <button class="pagination-btn">2</button>
                    <button class="pagination-btn">3</button>
                    <button class="pagination-btn">4</button>
                    <button class="pagination-btn"><i class="bi bi-chevron-right"></i></button>
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

<script>
    // Modal functionality
    const editModal = document.getElementById('editModal');
    const cancelBtn = document.getElementById('cancelBtn');

    document.querySelectorAll('.action-icon:not(.delete)').forEach(btn => {
        btn.addEventListener('click', function() {
            editModal.style.display = 'block';
        });
    });

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
</script>

</body>
</html>
