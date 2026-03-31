-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 31, 2026 at 03:58 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kyc_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `action_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `client_id` int(11) NOT NULL,
  `reference_code` varchar(50) NOT NULL,
  `client_number` varchar(30) DEFAULT NULL,
  `client_type` enum('individual','corporate','obligee') NOT NULL,
  `client_name` varchar(200) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `last_name_first` tinyint(1) DEFAULT 0,
  `comma_separated` tinyint(1) DEFAULT 0,
  `middle_initial_only` tinyint(1) DEFAULT 0,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `client_since` date DEFAULT NULL,
  `spouse_name` varchar(100) DEFAULT NULL,
  `spouse_birthdate` date DEFAULT NULL,
  `spouse_occupation` varchar(100) DEFAULT NULL,
  `id_type` varchar(50) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `tin_number` varchar(50) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `business_type` enum('private','government') DEFAULT NULL,
  `business_address` varchar(255) DEFAULT NULL,
  `business_ctm` varchar(50) DEFAULT NULL,
  `business_province` varchar(50) DEFAULT NULL,
  `home_address` varchar(255) DEFAULT NULL,
  `home_ctm` varchar(50) DEFAULT NULL,
  `home_province` varchar(50) DEFAULT NULL,
  `mailing_address_type` enum('business','home') DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `office_phone` varchar(20) DEFAULT NULL,
  `home_phone` varchar(20) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `ap_sl_code` varchar(50) DEFAULT NULL,
  `ar_sl_code` varchar(50) DEFAULT NULL,
  `client_classification` enum('client','agent') DEFAULT NULL,
  `mobile_phone` varchar(20) DEFAULT NULL,
  `landline_phone` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `full_address` varchar(255) DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `verification_status` enum('draft','pending','verified','rejected') DEFAULT 'draft',
  `verification_date` datetime DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `total_clients_count` int(11) DEFAULT 0,
  `pending_kyc_count` int(11) DEFAULT 0,
  `verified_count` int(11) DEFAULT 0,
  `rejected_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`client_id`, `reference_code`, `client_number`, `client_type`, `client_name`, `first_name`, `middle_name`, `last_name`, `suffix`, `last_name_first`, `comma_separated`, `middle_initial_only`, `date_of_birth`, `gender`, `nationality`, `client_since`, `spouse_name`, `spouse_birthdate`, `spouse_occupation`, `id_type`, `id_number`, `tin_number`, `occupation`, `company_name`, `designation`, `business_type`, `business_address`, `business_ctm`, `business_province`, `home_address`, `home_ctm`, `home_province`, `mailing_address_type`, `region`, `office_phone`, `home_phone`, `contact_person`, `ap_sl_code`, `ar_sl_code`, `client_classification`, `mobile_phone`, `landline_phone`, `email`, `full_address`, `submitted_by`, `submitted_at`, `verification_status`, `verification_date`, `verified_by`, `rejection_reason`, `total_clients_count`, `pending_kyc_count`, `verified_count`, `rejected_count`, `created_at`, `updated_at`) VALUES
(1, 'KYC-2024-0001', 'CN-024001', 'individual', NULL, 'Juan', 'Santos', 'Dela Cruz', '', 0, 0, 0, '1985-05-15', 'male', 'Philippine', NULL, NULL, NULL, NULL, 'passport', 'PP-123456789', NULL, 'Accountant', 'ABC Corporation', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 912 345 6789', '(02) 8123-4567', 'juan@example.com', '123 Main St, Barangay San Juan, Manila, NCR 1500', NULL, NULL, 'verified', NULL, 1, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(2, 'KYC-2024-0002', 'CN-024002', 'individual', NULL, 'Maria', 'Santos', 'Garcia', '', 0, 0, 0, '1990-03-22', 'female', 'Philippine', NULL, NULL, NULL, NULL, 'drivers_license', 'DL-0987654321', NULL, 'Manager', 'XYZ Company', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 921 654 3210', '(02) 8765-4321', 'maria@example.com', '456 Oak Ave, Barangay Makati, Manila, NCR 1200', NULL, NULL, 'pending', NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(3, 'KYC-2024-0003', 'CN-024003', 'corporate', NULL, 'Robert', '', 'Santos', 'Jr.', 0, 0, 0, '1982-07-10', 'male', 'Philippine', NULL, NULL, NULL, NULL, 'nbi', 'NBI-111222333', NULL, 'Managing Director', 'Tech Solutions Inc.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 945 123 4567', '(02) 8123-4567', 'robert@company.com', '789 Business Plaza, Barangay Fort Bonifacio, Taguig, NCR 1634', NULL, NULL, 'verified', NULL, 1, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(4, 'KYC-2024-0004', 'CN-024004', 'individual', NULL, 'Angela', 'Marie', 'Torres', '', 0, 0, 0, '1992-11-08', 'female', 'Philippine', NULL, NULL, NULL, NULL, 'tin', 'TIN-456789012', NULL, 'Consultant', 'Global Consulting', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 938 765 4321', '(02) 8987-6543', 'angela@example.com', '321 Green St, Barangay Pasay, Manila, NCR 1300', NULL, NULL, 'rejected', NULL, 2, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(5, 'KYC-2024-0005', 'CN-024005', 'individual', NULL, 'John', 'Michael', 'Reyes', '', 0, 0, 0, '1988-02-14', 'male', 'Philippine', NULL, NULL, NULL, NULL, 'passport', 'PP-987654321', NULL, 'Engineer', 'Engineering Solutions Ltd', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 945 678 9012', '(02) 8654-3210', 'john@example.com', '654 Blue Ave, Barangay Himig, Quezon City, NCR 1100', NULL, NULL, 'verified', NULL, 1, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(6, 'KYC-2024-0006', 'CN-024006', 'individual', NULL, 'Luisa', 'Ana', 'Cruz', '', 0, 0, 0, '1995-09-20', 'female', 'Philippine', NULL, NULL, NULL, NULL, 'drivers_license', 'DL-5555666677', NULL, 'Analyst', 'Data Analytics Corp', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 956 234 5678', '(02) 8345-6789', 'luisa@example.com', '987 Purple Blvd, Barangay Libis, Quezon City, NCR 1100', NULL, NULL, 'pending', NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(7, 'KYC-2024-0007', 'CN-024007', 'individual', NULL, 'Carlos', 'Antonio', 'Reyes', '', 0, 0, 0, '1986-06-12', 'male', 'Philippine', NULL, NULL, NULL, NULL, 'passport', 'PP-111222333', NULL, 'Architect', 'Design Studios', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 917 234 5678', '(02) 8234-5678', 'carlos@example.com', '111 Design Ave, Barangay Ermita, Manila, NCR 1000', NULL, NULL, 'verified', NULL, 1, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(8, 'KYC-2024-0008', 'CN-024008', 'corporate', NULL, 'Patricia', '', 'Lopez', '', 0, 0, 0, '1980-12-25', 'female', 'Philippine', NULL, NULL, NULL, NULL, 'nbi', 'NBI-444555666', NULL, 'CEO', 'Innovation Tech Co.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 998 765 4321', '(02) 8765-4321', 'patricia@innovtech.com', '500 Corporate Center, Makati, NCR 1200', NULL, NULL, 'verified', NULL, 3, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(9, 'KYC-2024-0009', 'CN-024009', 'individual', NULL, 'Miguel', 'David', 'Fernandez', '', 0, 0, 0, '1993-04-18', 'male', 'Philippine', NULL, NULL, NULL, NULL, 'drivers_license', 'DL-7777888899', NULL, 'Developer', 'Software House', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 922 111 2222', '(02) 8111-2222', 'miguel@example.com', '222 Tech Park, BGY Shaw, Pasig, NCR 1600', NULL, NULL, 'pending', NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(10, 'KYC-2024-0010', 'CN-024010', 'individual', NULL, 'Rosa', 'Gabriela', 'Morales', 'Sr.', 0, 0, 0, '1975-08-30', 'female', 'Philippine', NULL, NULL, NULL, NULL, 'passport', 'PP-555666777', NULL, 'Director', 'Education Board', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 909 333 4444', '(02) 8333-4444', 'rosa@example.com', '333 Public Admin, BGY Bagong Taguig, Taguig, NCR 1600', NULL, NULL, 'verified', NULL, 2, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(11, 'KYC-2024-0011', 'CN-024011', 'individual', NULL, 'Fernando', 'Luis', 'Guerrero', '', 0, 0, 0, '1987-10-05', 'male', 'Philippine', NULL, NULL, NULL, NULL, 'nbi', 'NBI-888999000', NULL, 'Businessman', 'Import Export Ltd', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 925 555 6666', '(02) 8555-6666', 'fernando@example.com', '444 Commerce St, BGY Divisoria, Manila, NCR 1000', NULL, NULL, 'verified', NULL, 1, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(12, 'KYC-2024-0012', 'CN-024012', 'individual', NULL, 'Sandra', 'Elizabeth', 'Navarro', '', 0, 0, 0, '1991-07-22', 'female', 'Philippine', NULL, NULL, NULL, NULL, 'drivers_license', 'DL-3333444455', NULL, 'Nurse', 'Medical Center', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 933 777 8888', '(02) 8777-8888', 'sandra@example.com', '555 Hospital Way, BGY Tandang Sora, QC, NCR 1100', NULL, NULL, 'pending', NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(13, 'KYC-2024-0013', 'CN-024013', 'corporate', NULL, 'Vicente', '', 'Ramos', 'III', 0, 0, 0, '1978-01-14', 'male', 'Philippine', NULL, NULL, NULL, NULL, 'tin', 'TIN-111222333', NULL, 'Board Chairman', 'Logistics Group Inc.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 916 999 0000', '(02) 8999-0000', 'vicente@logistics.com', '600 Logistics Hub, Port Area, Manila, NCR 1000', NULL, NULL, 'verified', NULL, 3, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(14, 'KYC-2024-0014', 'CN-024014', 'individual', NULL, 'Yvonne', 'Marie', 'Villanueva', '', 0, 0, 0, '1994-03-09', 'female', 'Philippine', NULL, NULL, NULL, NULL, 'passport', 'PP-888999111', NULL, 'Lawyer', 'Law Firm Partners', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 942 111 2222', '(02) 8111-2222', 'yvonne@example.com', '700 Justice Bldg, BGY Intramuros, Manila, NCR 1000', NULL, NULL, 'verified', NULL, 1, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(15, 'KYC-2024-0015', 'CN-024015', 'individual', NULL, 'Xavier', 'Paolo', 'Gonzales', '', 0, 0, 0, '1989-11-17', 'male', 'Philippine', NULL, NULL, NULL, NULL, 'drivers_license', 'DL-6666777788', NULL, 'Photographer', 'Creative Studios', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 920 333 4444', '(02) 8333-4444', 'xavier@example.com', '800 Arts District, BGY Malate, Manila, NCR 1000', NULL, NULL, 'pending', NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(16, 'KYC-2024-0016', 'CN-024016', 'individual', NULL, 'Zita', 'Sofia', 'Montoya', '', 0, 0, 0, '1986-09-25', 'female', 'Philippine', NULL, NULL, NULL, NULL, 'nbi', 'NBI-222333444', NULL, 'Writer', 'Publishing House', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 949 555 6666', '(02) 8555-6666', 'zita@example.com', '900 Literary Lane, BGY Sampaloc, Manila, NCR 1000', NULL, NULL, 'verified', NULL, 2, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(17, 'KYC-2024-0017', 'CN-024017', 'corporate', NULL, 'Andres', '', 'Santiago', 'Jr.', 0, 0, 0, '1983-05-30', 'male', 'Philippine', NULL, NULL, NULL, NULL, 'passport', 'PP-333444555', NULL, 'President', 'Manufacturing Corp', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 917 777 8888', '(02) 8777-8888', 'andres@manufcorp.com', '1000 Industrial Park, BGY Kawit, Kawit, CAVITE 0000', NULL, NULL, 'verified', NULL, 1, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(18, 'KYC-2024-0018', 'CN-024018', 'individual', NULL, 'Bella', 'Rose', 'Aquino', '', 0, 0, 0, '1997-02-11', 'female', 'Philippine', NULL, NULL, NULL, NULL, 'drivers_license', 'DL-9999000011', NULL, 'Student', 'University', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 911 999 0000', '(02) 8999-0000', 'bella@example.com', '1100 Campus Ave, BGY Dansalan, QC, NCR 1100', NULL, NULL, 'pending', NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(19, 'KYC-2024-0019', 'CN-024019', 'individual', NULL, 'Crispin', 'Manuel', 'Bustamante', '', 0, 0, 0, '1979-08-06', 'male', 'Philippine', NULL, NULL, NULL, NULL, 'nbi', 'NBI-555666777', NULL, 'Businessman', 'Real Estate Dev', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 923 111 2222', '(02) 8111-2222', 'crispin@example.com', '1200 Property Lane, BGY Forbes, QC, NCR 1100', NULL, NULL, 'verified', NULL, 3, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(20, 'KYC-2024-0020', 'CN-024020', 'individual', NULL, 'Dolores', 'Amelia', 'Castillo', '', 0, 0, 0, '1984-12-19', 'female', 'Philippine', NULL, NULL, NULL, NULL, 'passport', 'PP-666777888', NULL, 'HR Manager', 'Recruitment Agency', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 936 333 4444', '(02) 8333-4444', 'dolores@example.com', '1300 People Plaza, BGY Alabang, Muntinlupa, NCR 1700', NULL, NULL, 'verified', NULL, 1, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(21, 'KYC-2024-0021', 'CN-024021', 'individual', NULL, 'Emilio', 'Gabriel', 'Delgado', '', 0, 0, 0, '1992-10-02', 'male', 'Philippine', NULL, NULL, NULL, NULL, 'drivers_license', 'DL-2222333344', NULL, 'Chef', 'Restaurant', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 946 555 6666', '(02) 8555-6666', 'emilio@example.com', '1400 Culinary St, BGY Greenbelt, Makati, NCR 1200', NULL, NULL, 'pending', NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(22, 'KYC-2024-0022', 'CN-024022', 'corporate', NULL, 'Fiona', '', 'Echevarria', '', 0, 0, 0, '1981-07-14', 'female', 'Philippine', NULL, NULL, NULL, NULL, 'nbi', 'NBI-777888999', NULL, 'Director', 'Fashion House', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 953 777 8888', '(02) 8777-8888', 'fiona@fashionco.com', '1500 Fashion Hub, BGY BLVD, Makati, NCR 1200', NULL, NULL, 'verified', NULL, 2, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(23, 'KYC-2024-0023', 'CN-024023', 'individual', NULL, 'Gregorio', 'Antonio', 'Franco', 'Sr.', 0, 0, 0, '1975-04-28', 'male', 'Philippine', NULL, NULL, NULL, NULL, 'passport', 'PP-999000111', NULL, 'Banker', 'Bank', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 927 999 0000', '(02) 8999-0000', 'gregorio@example.com', '1600 Financial Center, BGY BGC, Taguig, NCR 1600', NULL, NULL, 'verified', NULL, 1, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(24, 'KYC-2024-0024', 'CN-024024', 'individual', NULL, 'Helena', 'Jasmine', 'Guzman', '', 0, 0, 0, '1996-06-09', 'female', 'Philippine', NULL, NULL, NULL, NULL, 'drivers_license', 'DL-4444555566', NULL, 'Athlete', 'Sports Org', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '+63 941 111 2222', '(02) 8111-2222', 'helena@example.com', '1700 Athletic Park, BGY Villamor, Pasay, NCR 1300', NULL, NULL, 'pending', NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(25, 'REF-20260317-00001', 'CN-1773728622', 'individual', NULL, 'Ezekiel', 'Robin', 'Codillo', '', 0, 0, 0, '2002-12-27', 'male', 'Filipino', NULL, NULL, NULL, NULL, 'license', 'D22-8236487', NULL, 'Student', 'BSU', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '09075732796', '', 'ezekielcodillo56@gmail.com', 'City of Santo Tomas', NULL, NULL, 'pending', NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-17 06:23:42', '2026-03-17 06:23:42'),
(26, 'REF-20260317-00002', 'CN-1773730174', 'corporate', NULL, 'Ansel', 'Cadag', 'Doton', '', 0, 0, 0, '2003-12-17', 'male', 'Filipino', NULL, NULL, NULL, NULL, 'tin', '1225-545-5455', NULL, 'Student', 'BSU', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '09917745489', '', 'ansel@gmail.com', 'sta maria, Santo Tomas, Batangas', NULL, NULL, 'pending', NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-17 06:49:34', '2026-03-17 06:49:34'),
(27, 'REF-20260323-00001', 'CN-1774225373', 'individual', NULL, 'Ezekiel', 'Robin', 'Codillo', '', 0, 0, 0, '2003-12-27', 'male', 'Filipino', NULL, NULL, NULL, NULL, '', '', NULL, 'Student', 'BSU', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '09075732796', '', 'ezekielcodillo56@gmail.com', 'Lumina', NULL, NULL, 'pending', NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-23 00:22:53', '2026-03-23 00:22:53'),
(28, 'REF-20260323-00002', 'CN-1774227596', 'corporate', 'Kiel Courier Services', '', NULL, '', NULL, 0, 0, 0, NULL, 'male', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'government', 'Lumina, San vicente', 'City of Santo Tomas', 'Batangas', NULL, NULL, NULL, NULL, NULL, '09568439760', NULL, 'Ezekiel Robin Codillo', NULL, NULL, 'agent', NULL, NULL, 'ezekielcodillo56@gmail.com', NULL, 1, '2026-03-23 01:59:56', 'pending', NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-23 00:59:56', '2026-03-23 00:59:56'),
(29, 'REF-20260323-00003', 'CN-1774234364', 'individual', NULL, 'Ezekiel', 'Robin', 'Codillo', '', 0, 0, 0, '2026-03-23', 'male', 'Filipino', NULL, NULL, NULL, NULL, '', '', NULL, 'Student', 'BSU', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '09917745489', '', 'ezekielcodillo56@gmail.com', 'Sambat', NULL, NULL, 'pending', NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-23 02:52:44', '2026-03-23 02:52:44'),
(30, 'REF-20260326-00001', 'CN-1774490538', 'corporate', NULL, '', '', '', '', 0, 0, 0, NULL, '', '', NULL, NULL, NULL, NULL, '', '', NULL, '', '', NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', NULL, 1, '2026-03-26 03:02:18', 'draft', NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-26 02:02:18', '2026-03-26 02:02:18'),
(31, 'REF-20260326-00002', 'CN-1774492173', 'corporate', NULL, '', '', '', '', 0, 0, 0, NULL, '', '', NULL, NULL, NULL, NULL, '', '', NULL, '', '', NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', NULL, 1, '2026-03-26 03:29:33', 'draft', NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-26 02:29:33', '2026-03-26 02:29:33'),
(32, 'REF-20260326-00003', 'CN-1774493824', 'corporate', NULL, '', '', '', '', 0, 0, 0, NULL, '', '', NULL, NULL, NULL, NULL, '', '', NULL, '', 'waw', NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', NULL, 1, '2026-03-26 03:57:04', 'draft', NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-26 02:57:04', '2026-03-26 02:57:04');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `document_id` int(11) NOT NULL,
  `kyc_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `document_type` varchar(50) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('verified','pending','rejected') DEFAULT 'pending',
  `verification_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`document_id`, `kyc_id`, `client_id`, `file_name`, `file_type`, `file_size`, `file_path`, `document_type`, `uploaded_by`, `uploaded_at`, `status`, `verification_notes`) VALUES
(1, 11, 31, 'Paul.png', 'image/png', 416535, 'uploads/client_31_kyc_11_1774493415_4a4cc71fc30c9d5f.png', 'supporting', 1, '2026-03-26 02:50:15', 'pending', NULL),
(2, 12, 32, '648838554_1940055316714537_8223827028323029279_n.jpg', 'image/jpeg', 518967, 'uploads/client_32_kyc_12_1774493856_1b56855ce7768c27.jpg', 'supporting', 1, '2026-03-26 02:57:36', 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kyc_verifications`
--

CREATE TABLE `kyc_verifications` (
  `kyc_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `reference_code` varchar(50) DEFAULT NULL,
  `step_current` int(11) DEFAULT 1,
  `step_1_completed` tinyint(1) DEFAULT 0,
  `step_2_completed` tinyint(1) DEFAULT 0,
  `step_3_completed` tinyint(1) DEFAULT 0,
  `step_4_completed` tinyint(1) DEFAULT 0,
  `ref_code` varchar(50) DEFAULT NULL,
  `client_type` varchar(20) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `id_type` varchar(50) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` enum('draft','in_progress','submitted','approved','rejected') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `submitted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kyc_verifications`
--

INSERT INTO `kyc_verifications` (`kyc_id`, `client_id`, `reference_code`, `step_current`, `step_1_completed`, `step_2_completed`, `step_3_completed`, `step_4_completed`, `ref_code`, `client_type`, `last_name`, `first_name`, `middle_name`, `suffix`, `birthdate`, `gender`, `nationality`, `id_type`, `id_number`, `occupation`, `company`, `mobile`, `phone`, `email`, `address`, `status`, `created_at`, `updated_at`, `submitted_at`) VALUES
(1, 1, 'KYC-2024-0001', 4, 1, 1, 1, 1, 'REF-001', 'individual', 'Dela Cruz', 'Juan', 'Santos', NULL, '1985-05-15', 'male', 'Philippine', 'passport', 'PP-123456789', 'Accountant', 'ABC Corporation', '+63 912 345 6789', NULL, 'juan@example.com', '123 Main St, Barangay San Juan, Manila, NCR 1500', 'approved', '2026-03-17 03:27:01', '2026-03-17 03:27:01', '2024-03-10 10:30:00'),
(2, 2, 'KYC-2024-0002', 2, 1, 0, 0, 0, 'REF-002', 'individual', 'Garcia', 'Maria', 'Santos', NULL, '1990-03-22', 'female', 'Philippine', 'drivers_license', 'DL-0987654321', 'Manager', 'XYZ Company', '+63 921 654 3210', NULL, 'maria@example.com', '456 Oak Ave, Barangay Makati, Manila, NCR 1200', 'in_progress', '2026-03-17 03:27:01', '2026-03-17 03:27:01', NULL),
(3, 3, 'KYC-2024-0003', 4, 1, 1, 1, 1, 'REF-003', 'corporate', 'Santos', 'Robert', '', NULL, '1982-07-10', 'male', 'Philippine', 'nbi', 'NBI-111222333', 'Managing Director', 'Tech Solutions Inc.', '+63 945 123 4567', NULL, 'robert@company.com', '789 Business Plaza, Barangay Fort Bonifacio, Taguig, NCR 1634', 'approved', '2026-03-17 03:27:01', '2026-03-17 03:27:01', '2024-02-15 14:45:00'),
(4, 25, 'REF-20260317-00001', 4, 1, 1, 1, 1, 'REF-20260317-00001', 'individual', 'Codillo', 'Ezekiel', 'Robin', '', '2002-12-27', 'male', 'Filipino', 'license', 'D22-8236487', 'Student', 'BSU', '09075732796', '', 'ezekielcodillo56@gmail.com', 'City of Santo Tomas', 'submitted', '2026-03-17 06:23:42', '2026-03-17 06:23:42', '2026-03-17 07:23:42'),
(5, 26, 'REF-20260317-00002', 4, 1, 1, 1, 1, 'REF-20260317-00002', 'corporate', 'Doton', 'Ansel', 'Cadag', '', '2003-12-17', 'male', 'Filipino', 'tin', '1225-545-5455', 'Student', 'BSU', '09917745489', '', 'ansel@gmail.com', 'sta maria, Santo Tomas, Batangas', 'submitted', '2026-03-17 06:49:34', '2026-03-17 06:49:34', '2026-03-17 07:49:34'),
(7, 27, 'REF-20260323-00001', 4, 1, 1, 1, 1, 'REF-20260323-00001', 'individual', 'Codillo', 'Ezekiel', 'Robin', '', '2003-12-27', 'male', 'Filipino', '', '', 'Student', 'BSU', '09075732796', '', 'ezekielcodillo56@gmail.com', 'Lumina', 'submitted', '2026-03-23 00:22:53', '2026-03-23 00:22:53', '2026-03-23 01:22:53'),
(9, 29, 'REF-20260323-00003', 4, 1, 1, 1, 1, 'REF-20260323-00003', 'individual', 'Codillo', 'Ezekiel', 'Robin', '', '2026-03-23', 'male', 'Filipino', '', '', 'Student', 'BSU', '09917745489', '', 'ezekielcodillo56@gmail.com', 'Sambat', 'submitted', '2026-03-23 02:52:44', '2026-03-23 02:52:44', '2026-03-23 03:52:44'),
(10, 30, 'REF-20260326-00001', 1, 0, 0, 0, 0, 'REF-20260326-00001', 'corporate', '', '', '', '', '0000-00-00', '', '', '', '', '', '', '', '', '', '', 'draft', '2026-03-26 02:02:18', '2026-03-26 02:02:18', NULL),
(11, 31, 'REF-20260326-00002', 1, 0, 0, 0, 0, 'REF-20260326-00002', '', '', '', '', '', '0000-00-00', '', '', '', '', '', 'saadw', '', '', '', '', 'draft', '2026-03-26 02:29:33', '2026-03-26 02:55:53', NULL),
(12, 32, 'REF-20260326-00003', 1, 0, 0, 0, 0, 'REF-20260326-00003', '', '', '', '', '', '0000-00-00', '', '', '', '', '', 'waw', '', '', '', '', 'draft', '2026-03-26 02:57:04', '2026-03-26 03:25:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department` varchar(50) NOT NULL,
  `role` varchar(30) DEFAULT 'kyc_officer',
  `avatar_initials` varchar(5) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `department`, `role`, `avatar_initials`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Juan Dela Cruz', 'juan@sterlingins.com', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'kyc-officer', 'kyc_officer', 'JD', 'active', '2026-03-31 02:11:04', '2026-03-17 03:27:01', '2026-03-31 00:11:04'),
(2, 'Maria Garcia', 'maria@sterlingins.com', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'compliance', 'kyc_officer', 'MG', 'active', NULL, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(3, 'Robert Santos', 'robert@sterlingins.com', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'operations', 'manager', 'RS', 'active', NULL, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(4, 'Angela Torres', 'angela@sterlingins.com', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'kyc-officer', 'kyc_officer', 'AT', 'active', NULL, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(5, 'John Reyes', 'john@sterlingins.com', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'compliance', 'kyc_officer', 'JR', 'active', NULL, '2026-03-17 03:27:01', '2026-03-17 03:27:01'),
(6, 'Luisa Cruz', 'luisa@sterlingins.com', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'kyc-officer', 'kyc_officer', 'LC', 'active', '2026-03-23 07:47:42', '2026-03-17 03:27:01', '2026-03-23 06:47:42'),
(7, 'Ezekiel Robin Codillo', 'ezekielcodillo56@gmail.com', '7a12a69239582aaeffc5010f059685d5756b2996dc1853f0c973ce72f93b5f39', 'kyc-officer', 'kyc_officer', 'EC', 'active', NULL, '2026-03-23 03:36:50', '2026-03-23 03:36:50'),
(8, 'Paulynous K. Gonzales', 'gonzalespaul528@gmail.com', '7a12a69239582aaeffc5010f059685d5756b2996dc1853f0c973ce72f93b5f39', 'kyc-officer', 'kyc_officer', 'PG', 'active', '2026-03-24 08:37:59', '2026-03-23 07:37:10', '2026-03-24 07:37:59');

-- --------------------------------------------------------

--
-- Table structure for table `verification_history`
--

CREATE TABLE `verification_history` (
  `history_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `kyc_id` int(11) DEFAULT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `verification_history`
--

INSERT INTO `verification_history` (`history_id`, `client_id`, `kyc_id`, `old_status`, `new_status`, `changed_by`, `change_reason`, `changed_at`) VALUES
(1, 1, 1, 'pending', 'verified', 1, 'All documents verified and approved', '2026-03-17 03:27:01'),
(2, 2, 2, 'draft', 'pending', 1, 'Submitted for review', '2026-03-17 03:27:01'),
(3, 3, 3, 'pending', 'verified', 1, 'Corporate documents verified', '2026-03-17 03:27:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action_at` (`action_at`),
  ADD KEY `idx_table_name` (`table_name`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`),
  ADD UNIQUE KEY `reference_code` (`reference_code`),
  ADD UNIQUE KEY `client_number` (`client_number`),
  ADD KEY `idx_reference_code` (`reference_code`),
  ADD KEY `idx_client_number` (`client_number`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_verification_status` (`verification_status`),
  ADD KEY `idx_client_type` (`client_type`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_clients_status_type` (`verification_status`,`client_type`),
  ADD KEY `submitted_by` (`submitted_by`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `idx_kyc_id` (`kyc_id`),
  ADD KEY `idx_client_id` (`client_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_documents_created` (`uploaded_at`);

--
-- Indexes for table `kyc_verifications`
--
ALTER TABLE `kyc_verifications`
  ADD PRIMARY KEY (`kyc_id`),
  ADD KEY `idx_client_id` (`client_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_kyc_client_status` (`client_id`,`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_users_role_status` (`role`,`status`);

--
-- Indexes for table `verification_history`
--
ALTER TABLE `verification_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_client_id` (`client_id`),
  ADD KEY `idx_kyc_id` (`kyc_id`),
  ADD KEY `idx_changed_at` (`changed_at`),
  ADD KEY `changed_by` (`changed_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kyc_verifications`
--
ALTER TABLE `kyc_verifications`
  MODIFY `kyc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `verification_history`
--
ALTER TABLE `verification_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `clients_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`kyc_id`) REFERENCES `kyc_verifications` (`kyc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `kyc_verifications`
--
ALTER TABLE `kyc_verifications`
  ADD CONSTRAINT `kyc_verifications_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE;

--
-- Constraints for table `verification_history`
--
ALTER TABLE `verification_history`
  ADD CONSTRAINT `verification_history_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `verification_history_ibfk_2` FOREIGN KEY (`kyc_id`) REFERENCES `kyc_verifications` (`kyc_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `verification_history_ibfk_3` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
