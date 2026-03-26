-- =====================================================
-- KYC SYSTEM - COMPLETE DATABASE SCHEMA
-- Sterling Insurance Company
-- Created: March 17, 2026
-- =====================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS kyc_system;
USE kyc_system;

-- =====================================================
-- TABLE: users (KYC Officers & System Users)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(50) NOT NULL,
    role VARCHAR(30) DEFAULT 'kyc_officer',
    avatar_initials VARCHAR(5),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_department (department),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: clients (Client Information)
-- =====================================================
CREATE TABLE IF NOT EXISTS clients (
    client_id INT AUTO_INCREMENT PRIMARY KEY,
    reference_code VARCHAR(50) UNIQUE NOT NULL,
    client_number VARCHAR(30) UNIQUE,
    client_type ENUM('individual', 'corporate') NOT NULL,
    
    -- Name Information
    first_name VARCHAR(50),
    middle_name VARCHAR(50),
    last_name VARCHAR(50),
    salutation VARCHAR(20),
    suffix VARCHAR(10),
    client_name VARCHAR(200),
    
    -- Name Format Options (Individual)
    last_name_first BOOLEAN DEFAULT FALSE,
    comma_separated BOOLEAN DEFAULT FALSE,
    middle_initial_only BOOLEAN DEFAULT FALSE,
    
    -- Personal Details (Individual)
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    nationality VARCHAR(50),
    client_since DATE,
    
    -- Spouse Information (Individual)
    spouse_name VARCHAR(100),
    spouse_birthdate DATE,
    spouse_occupation VARCHAR(100),
    
    -- Identification
    id_type VARCHAR(50),
    id_number VARCHAR(50),
    tin_number VARCHAR(50),
    
    -- Occupation & Business
    occupation VARCHAR(100),
    company_name VARCHAR(100),
    designation VARCHAR(100),
    business_type ENUM('private', 'government'),
    
    -- Address Information
    business_address VARCHAR(255),
    business_ctm VARCHAR(50),
    business_province VARCHAR(50),
    home_address VARCHAR(255),
    home_ctm VARCHAR(50),
    home_province VARCHAR(50),
    mailing_address_type ENUM('business', 'home'),
    region VARCHAR(100),
    
    -- Contact Information
    office_phone VARCHAR(20),
    home_phone VARCHAR(20),
    mobile_phone VARCHAR(20),
    landline_phone VARCHAR(20),
    contact_person VARCHAR(100),
    email VARCHAR(120),
    
    -- SL Codes
    ap_sl_code VARCHAR(50),
    ar_sl_code VARCHAR(50),
    
    -- Classification
    client_classification ENUM('client', 'agent'),
    
    -- Submission & Verification Status
    submitted_by INT,
    submitted_at DATETIME,
    verification_status ENUM('draft', 'pending', 'verified', 'rejected') DEFAULT 'draft',
    verification_date DATETIME,
    verified_by INT,
    rejection_reason TEXT,
    
    -- Additional Info
    total_clients_count INT DEFAULT 0,
    pending_kyc_count INT DEFAULT 0,
    verified_count INT DEFAULT 0,
    rejected_count INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_reference_code (reference_code),
    INDEX idx_client_number (client_number),
    INDEX idx_email (email),
    INDEX idx_verification_status (verification_status),
    INDEX idx_client_type (client_type),
    FOREIGN KEY (submitted_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: kyc_verifications (KYC Verification Records)
-- =====================================================
CREATE TABLE IF NOT EXISTS kyc_verifications (
    kyc_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    reference_code VARCHAR(50),
    
    -- Steps Progress
    step_current INT DEFAULT 1,
    step_1_completed BOOLEAN DEFAULT FALSE,
    step_2_completed BOOLEAN DEFAULT FALSE,
    step_3_completed BOOLEAN DEFAULT FALSE,
    step_4_completed BOOLEAN DEFAULT FALSE,
    
    -- Form Data
    ref_code VARCHAR(50),
    client_type VARCHAR(20),
    last_name VARCHAR(50),
    first_name VARCHAR(50),
    middle_name VARCHAR(50),
    suffix VARCHAR(10),
    birthdate DATE,
    gender VARCHAR(20),
    nationality VARCHAR(50),
    id_type VARCHAR(50),
    id_number VARCHAR(50),
    occupation VARCHAR(100),
    company VARCHAR(100),
    mobile VARCHAR(20),
    phone VARCHAR(20),
    email VARCHAR(120),
    address VARCHAR(255),
    
    status ENUM('draft', 'in_progress', 'submitted', 'approved', 'rejected') DEFAULT 'draft',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    submitted_at DATETIME,
    
    INDEX idx_client_id (client_id),
    INDEX idx_status (status),
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: documents (Uploaded Documents)
-- =====================================================
CREATE TABLE IF NOT EXISTS documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    kyc_id INT NOT NULL,
    client_id INT NOT NULL,
    
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    file_size BIGINT,
    file_path VARCHAR(500),
    
    document_type VARCHAR(50),
    
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    status ENUM('verified', 'pending', 'rejected') DEFAULT 'pending',
    verification_notes TEXT,
    
    INDEX idx_kyc_id (kyc_id),
    INDEX idx_client_id (client_id),
    INDEX idx_status (status),
    FOREIGN KEY (kyc_id) REFERENCES kyc_verifications(kyc_id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: verification_history (Verification Status Tracking)
-- =====================================================
CREATE TABLE IF NOT EXISTS verification_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    kyc_id INT,
    
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    
    changed_by INT,
    change_reason TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_client_id (client_id),
    INDEX idx_kyc_id (kyc_id),
    INDEX idx_changed_at (changed_at),
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE,
    FOREIGN KEY (kyc_id) REFERENCES kyc_verifications(kyc_id) ON DELETE SET NULL,
    FOREIGN KEY (changed_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: audit_logs (System Audit Trail)
-- =====================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    
    action VARCHAR(100),
    table_name VARCHAR(50),
    record_id INT,
    
    old_value TEXT,
    new_value TEXT,
    
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    
    action_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_action_at (action_at),
    INDEX idx_table_name (table_name),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT SAMPLE DATA: Users (KYC Officers)
-- =====================================================
INSERT INTO users (full_name, email, password, department, role, avatar_initials, status) VALUES
('Juan Dela Cruz', 'juan@sterlingins.com', SHA2('password123', 256), 'kyc-officer', 'kyc_officer', 'JD', 'active'),
('Maria Garcia', 'maria@sterlingins.com', SHA2('password123', 256), 'compliance', 'kyc_officer', 'MG', 'active'),
('Robert Santos', 'robert@sterlingins.com', SHA2('password123', 256), 'operations', 'manager', 'RS', 'active'),
('Angela Torres', 'angela@sterlingins.com', SHA2('password123', 256), 'kyc-officer', 'kyc_officer', 'AT', 'active'),
('John Reyes', 'john@sterlingins.com', SHA2('password123', 256), 'compliance', 'kyc_officer', 'JR', 'active'),
('Luisa Cruz', 'luisa@sterlingins.com', SHA2('password123', 256), 'kyc-officer', 'kyc_officer', 'LC', 'active');

-- =====================================================
-- INSERT SAMPLE DATA: Clients
-- =====================================================
INSERT INTO clients (
    reference_code, client_number, client_type,
    first_name, middle_name, last_name, suffix,
    date_of_birth, gender, nationality,
    id_type, id_number,
    occupation, company_name,
    mobile_phone, landline_phone, email,
    full_address,
    verification_status, verified_by
) VALUES

-- Client 1: Juan Dela Cruz (Verified)
('KYC-2024-0001', 'CN-024001', 'individual',
'Juan', 'Santos', 'Dela Cruz', '',
'1985-05-15', 'male', 'Philippine',
'passport', 'PP-123456789',
'Accountant', 'ABC Corporation',
'+63 912 345 6789', '(02) 8123-4567', 'juan@example.com',
'123 Main St, Barangay San Juan, Manila, NCR 1500',
'verified', 1),

-- Client 2: Maria Garcia (Pending)
('KYC-2024-0002', 'CN-024002', 'individual',
'Maria', 'Santos', 'Garcia', '',
'1990-03-22', 'female', 'Philippine',
'drivers_license', 'DL-0987654321',
'Manager', 'XYZ Company',
'+63 921 654 3210', '(02) 8765-4321', 'maria@example.com',
'456 Oak Ave, Barangay Makati, Manila, NCR 1200',
'pending', NULL),

-- Client 3: Robert Santos (Verified, Corporate)
('KYC-2024-0003', 'CN-024003', 'corporate',
'Robert', '', 'Santos', 'Jr.',
'1982-07-10', 'male', 'Philippine',
'nbi', 'NBI-111222333',
'Managing Director', 'Tech Solutions Inc.',
'+63 945 123 4567', '(02) 8123-4567', 'robert@company.com',
'789 Business Plaza, Barangay Fort Bonifacio, Taguig, NCR 1634',
'verified', 1),

-- Client 4: Angela Torres (Rejected)
('KYC-2024-0004', 'CN-024004', 'individual',
'Angela', 'Marie', 'Torres', '',
'1992-11-08', 'female', 'Philippine',
'tin', 'TIN-456789012',
'Consultant', 'Global Consulting',
'+63 938 765 4321', '(02) 8987-6543', 'angela@example.com',
'321 Green St, Barangay Pasay, Manila, NCR 1300',
'rejected', 2),

-- Client 5: John Reyes (Verified)
('KYC-2024-0005', 'CN-024005', 'individual',
'John', 'Michael', 'Reyes', '',
'1988-02-14', 'male', 'Philippine',
'passport', 'PP-987654321',
'Engineer', 'Engineering Solutions Ltd',
'+63 945 678 9012', '(02) 8654-3210', 'john@example.com',
'654 Blue Ave, Barangay Himig, Quezon City, NCR 1100',
'verified', 1),

-- Client 6: Luisa Cruz (Pending)
('KYC-2024-0006', 'CN-024006', 'individual',
'Luisa', 'Ana', 'Cruz', '',
'1995-09-20', 'female', 'Philippine',
'drivers_license', 'DL-5555666677',
'Analyst', 'Data Analytics Corp',
'+63 956 234 5678', '(02) 8345-6789', 'luisa@example.com',
'987 Purple Blvd, Barangay Libis, Quezon City, NCR 1100',
'pending', NULL),

-- Additional Clients (7-24 for total of 24 shown in dashboard)
('KYC-2024-0007', 'CN-024007', 'individual',
'Carlos', 'Antonio', 'Reyes', '',
'1986-06-12', 'male', 'Philippine',
'passport', 'PP-111222333',
'Architect', 'Design Studios',
'+63 917 234 5678', '(02) 8234-5678', 'carlos@example.com',
'111 Design Ave, Barangay Ermita, Manila, NCR 1000',
'verified', 1),

('KYC-2024-0008', 'CN-024008', 'corporate',
'Patricia', '', 'Lopez', '',
'1980-12-25', 'female', 'Philippine',
'nbi', 'NBI-444555666',
'CEO', 'Innovation Tech Co.',
'+63 998 765 4321', '(02) 8765-4321', 'patricia@innovtech.com',
'500 Corporate Center, Makati, NCR 1200',
'verified', 3),

('KYC-2024-0009', 'CN-024009', 'individual',
'Miguel', 'David', 'Fernandez', '',
'1993-04-18', 'male', 'Philippine',
'drivers_license', 'DL-7777888899',
'Developer', 'Software House',
'+63 922 111 2222', '(02) 8111-2222', 'miguel@example.com',
'222 Tech Park, BGY Shaw, Pasig, NCR 1600',
'pending', NULL),

('KYC-2024-0010', 'CN-024010', 'individual',
'Rosa', 'Gabriela', 'Morales', 'Sr.',
'1975-08-30', 'female', 'Philippine',
'passport', 'PP-555666777',
'Director', 'Education Board',
'+63 909 333 4444', '(02) 8333-4444', 'rosa@example.com',
'333 Public Admin, BGY Bagong Taguig, Taguig, NCR 1600',
'verified', 2),

('KYC-2024-0011', 'CN-024011', 'individual',
'Fernando', 'Luis', 'Guerrero', '',
'1987-10-05', 'male', 'Philippine',
'nbi', 'NBI-888999000',
'Businessman', 'Import Export Ltd',
'+63 925 555 6666', '(02) 8555-6666', 'fernando@example.com',
'444 Commerce St, BGY Divisoria, Manila, NCR 1000',
'verified', 1),

('KYC-2024-0012', 'CN-024012', 'individual',
'Sandra', 'Elizabeth', 'Navarro', '',
'1991-07-22', 'female', 'Philippine',
'drivers_license', 'DL-3333444455',
'Nurse', 'Medical Center',
'+63 933 777 8888', '(02) 8777-8888', 'sandra@example.com',
'555 Hospital Way, BGY Tandang Sora, QC, NCR 1100',
'pending', NULL),

('KYC-2024-0013', 'CN-024013', 'corporate',
'Vicente', '', 'Ramos', 'III',
'1978-01-14', 'male', 'Philippine',
'tin', 'TIN-111222333',
'Board Chairman', 'Logistics Group Inc.',
'+63 916 999 0000', '(02) 8999-0000', 'vicente@logistics.com',
'600 Logistics Hub, Port Area, Manila, NCR 1000',
'verified', 3),

('KYC-2024-0014', 'CN-024014', 'individual',
'Yvonne', 'Marie', 'Villanueva', '',
'1994-03-09', 'female', 'Philippine',
'passport', 'PP-888999111',
'Lawyer', 'Law Firm Partners',
'+63 942 111 2222', '(02) 8111-2222', 'yvonne@example.com',
'700 Justice Bldg, BGY Intramuros, Manila, NCR 1000',
'verified', 1),

('KYC-2024-0015', 'CN-024015', 'individual',
'Xavier', 'Paolo', 'Gonzales', '',
'1989-11-17', 'male', 'Philippine',
'drivers_license', 'DL-6666777788',
'Photographer', 'Creative Studios',
'+63 920 333 4444', '(02) 8333-4444', 'xavier@example.com',
'800 Arts District, BGY Malate, Manila, NCR 1000',
'pending', NULL),

('KYC-2024-0016', 'CN-024016', 'individual',
'Zita', 'Sofia', 'Montoya', '',
'1986-09-25', 'female', 'Philippine',
'nbi', 'NBI-222333444',
'Writer', 'Publishing House',
'+63 949 555 6666', '(02) 8555-6666', 'zita@example.com',
'900 Literary Lane, BGY Sampaloc, Manila, NCR 1000',
'verified', 2),

('KYC-2024-0017', 'CN-024017', 'corporate',
'Andres', '', 'Santiago', 'Jr.',
'1983-05-30', 'male', 'Philippine',
'passport', 'PP-333444555',
'President', 'Manufacturing Corp',
'+63 917 777 8888', '(02) 8777-8888', 'andres@manufcorp.com',
'1000 Industrial Park, BGY Kawit, Kawit, CAVITE 0000',
'verified', 1),

('KYC-2024-0018', 'CN-024018', 'individual',
'Bella', 'Rose', 'Aquino', '',
'1997-02-11', 'female', 'Philippine',
'drivers_license', 'DL-9999000011',
'Student', 'University',
'+63 911 999 0000', '(02) 8999-0000', 'bella@example.com',
'1100 Campus Ave, BGY Dansalan, QC, NCR 1100',
'pending', NULL),

('KYC-2024-0019', 'CN-024019', 'individual',
'Crispin', 'Manuel', 'Bustamante', '',
'1979-08-06', 'male', 'Philippine',
'nbi', 'NBI-555666777',
'Businessman', 'Real Estate Dev',
'+63 923 111 2222', '(02) 8111-2222', 'crispin@example.com',
'1200 Property Lane, BGY Forbes, QC, NCR 1100',
'verified', 3),

('KYC-2024-0020', 'CN-024020', 'individual',
'Dolores', 'Amelia', 'Castillo', '',
'1984-12-19', 'female', 'Philippine',
'passport', 'PP-666777888',
'HR Manager', 'Recruitment Agency',
'+63 936 333 4444', '(02) 8333-4444', 'dolores@example.com',
'1300 People Plaza, BGY Alabang, Muntinlupa, NCR 1700',
'verified', 1),

('KYC-2024-0021', 'CN-024021', 'individual',
'Emilio', 'Gabriel', 'Delgado', '',
'1992-10-02', 'male', 'Philippine',
'drivers_license', 'DL-2222333344',
'Chef', 'Restaurant',
'+63 946 555 6666', '(02) 8555-6666', 'emilio@example.com',
'1400 Culinary St, BGY Greenbelt, Makati, NCR 1200',
'pending', NULL),

('KYC-2024-0022', 'CN-024022', 'corporate',
'Fiona', '', 'Echevarria', '',
'1981-07-14', 'female', 'Philippine',
'nbi', 'NBI-777888999',
'Director', 'Fashion House',
'+63 953 777 8888', '(02) 8777-8888', 'fiona@fashionco.com',
'1500 Fashion Hub, BGY BLVD, Makati, NCR 1200',
'verified', 2),

('KYC-2024-0023', 'CN-024023', 'individual',
'Gregorio', 'Antonio', 'Franco', 'Sr.',
'1975-04-28', 'male', 'Philippine',
'passport', 'PP-999000111',
'Banker', 'Bank',
'+63 927 999 0000', '(02) 8999-0000', 'gregorio@example.com',
'1600 Financial Center, BGY BGC, Taguig, NCR 1600',
'verified', 1),

('KYC-2024-0024', 'CN-024024', 'individual',
'Helena', 'Jasmine', 'Guzman', '',
'1996-06-09', 'female', 'Philippine',
'drivers_license', 'DL-4444555566',
'Athlete', 'Sports Org',
'+63 941 111 2222', '(02) 8111-2222', 'helena@example.com',
'1700 Athletic Park, BGY Villamor, Pasay, NCR 1300',
'pending', NULL);

-- =====================================================
-- INSERT SAMPLE DATA: KYC Verifications
-- =====================================================
INSERT INTO kyc_verifications (
    client_id, reference_code, 
    step_current, step_1_completed, step_2_completed, step_3_completed, step_4_completed,
    ref_code, client_type, last_name, first_name, middle_name,
    birthdate, gender, nationality,
    id_type, id_number,
    occupation, company,
    mobile, email, address,
    status, submitted_at
) VALUES
(1, 'KYC-2024-0001', 4, TRUE, TRUE, TRUE, TRUE,
'REF-001', 'individual', 'Dela Cruz', 'Juan', 'Santos',
'1985-05-15', 'male', 'Philippine',
'passport', 'PP-123456789',
'Accountant', 'ABC Corporation',
'+63 912 345 6789', 'juan@example.com', '123 Main St, Barangay San Juan, Manila, NCR 1500',
'approved', '2024-03-10 10:30:00'),

(2, 'KYC-2024-0002', 2, TRUE, FALSE, FALSE, FALSE,
'REF-002', 'individual', 'Garcia', 'Maria', 'Santos',
'1990-03-22', 'female', 'Philippine',
'drivers_license', 'DL-0987654321',
'Manager', 'XYZ Company',
'+63 921 654 3210', 'maria@example.com', '456 Oak Ave, Barangay Makati, Manila, NCR 1200',
'in_progress', NULL),

(3, 'KYC-2024-0003', 4, TRUE, TRUE, TRUE, TRUE,
'REF-003', 'corporate', 'Santos', 'Robert', '',
'1982-07-10', 'male', 'Philippine',
'nbi', 'NBI-111222333',
'Managing Director', 'Tech Solutions Inc.',
'+63 945 123 4567', 'robert@company.com', '789 Business Plaza, Barangay Fort Bonifacio, Taguig, NCR 1634',
'approved', '2024-02-15 14:45:00');

-- =====================================================
-- INSERT SAMPLE DATA: Verification History
-- =====================================================
INSERT INTO verification_history (client_id, kyc_id, old_status, new_status, changed_by, change_reason) VALUES
(1, 1, 'pending', 'verified', 1, 'All documents verified and approved'),
(2, 2, 'draft', 'pending', 1, 'Submitted for review'),
(3, 3, 'pending', 'verified', 1, 'Corporate documents verified');

-- =====================================================
-- CREATE INDEXES FOR PERFORMANCE
-- =====================================================
CREATE INDEX idx_clients_status_type ON clients(verification_status, client_type);
CREATE INDEX idx_users_role_status ON users(role, status);
CREATE INDEX idx_kyc_client_status ON kyc_verifications(client_id, status);
CREATE INDEX idx_documents_created ON documents(uploaded_at);

-- =====================================================
-- DISPLAY DATABASE SUMMARY
-- =====================================================
COMMIT;

-- Show Statistics
SELECT CONCAT('Database Created Successfully!') AS Status;
SELECT COUNT(*) AS TotalUsers FROM users;
SELECT COUNT(*) AS TotalClients FROM clients;
SELECT COUNT(*) AS TotalKYCRecords FROM kyc_verifications;
SELECT verification_status, COUNT(*) AS Count FROM clients GROUP BY verification_status;
