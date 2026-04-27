-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 24, 2026 at 07:22 AM
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
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `agent_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `reference_code` varchar(50) NOT NULL,
  `client_number` varchar(30) DEFAULT NULL,
  `client_type` enum('individual','corporate','obligee') NOT NULL DEFAULT 'individual',
  `agent_type` enum('agent','sub_agent') DEFAULT NULL,
  `head_agent_name` varchar(150) DEFAULT NULL,
  `agent_branch` varchar(80) DEFAULT NULL,
  `client_name` varchar(200) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `mobile_phone` varchar(20) DEFAULT NULL,
  `office_phone` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `verification_status` enum('draft','pending','verified','rejected') DEFAULT 'draft',
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `last_transaction_date` date DEFAULT NULL,
  `activity_status` enum('active','inactive','deactivated') DEFAULT NULL,
  `activity_status_updated_at` datetime DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agent_approvals`
--

CREATE TABLE `agent_approvals` (
  `approval_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `reference_code` varchar(50) NOT NULL,
  `client_number` varchar(30) DEFAULT NULL,
  `client_classification` enum('agent') NOT NULL DEFAULT 'agent',
  `agent_type` enum('agent','sub_agent') DEFAULT NULL,
  `head_agent_name` varchar(150) DEFAULT NULL,
  `agent_branch` varchar(80) DEFAULT NULL,
  `client_type` enum('individual','corporate','obligee') NOT NULL DEFAULT 'individual',
  `display_name` varchar(200) DEFAULT NULL,
  `client_name` varchar(200) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `mobile_phone` varchar(20) DEFAULT NULL,
  `office_phone` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_by_branch` varchar(80) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `approval_status` enum('pending','approved','declined','resubmit') NOT NULL DEFAULT 'pending',
  `review_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agent_approval_status_history`
--

CREATE TABLE `agent_approval_status_history` (
  `history_id` int(11) NOT NULL,
  `approval_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `reference_code` varchar(50) NOT NULL,
  `previous_status` enum('pending','approved','declined','resubmit') DEFAULT NULL,
  `new_status` enum('pending','approved','declined','resubmit') NOT NULL,
  `review_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `approved_agents`
--

CREATE TABLE `approved_agents` (
  `agent_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `reference_code` varchar(50) NOT NULL,
  `client_number` varchar(30) DEFAULT NULL,
  `client_type` enum('individual','corporate','obligee') NOT NULL DEFAULT 'individual',
  `agent_type` enum('agent','sub_agent') DEFAULT NULL,
  `head_agent_name` varchar(150) DEFAULT NULL,
  `agent_branch` varchar(80) DEFAULT NULL,
  `client_name` varchar(200) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `mobile_phone` varchar(20) DEFAULT NULL,
  `office_phone` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `verification_status` enum('draft','pending','verified','rejected') DEFAULT 'draft',
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_transaction_date` date DEFAULT NULL,
  `activity_status` enum('active','inactive','deactivated') DEFAULT NULL,
  `activity_status_updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `salutation` varchar(20) DEFAULT NULL,
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
  `agent_type` enum('agent','sub_agent') DEFAULT NULL,
  `head_agent_name` varchar(150) DEFAULT NULL,
  `agent_branch` varchar(80) DEFAULT NULL,
  `mobile_phone` varchar(20) DEFAULT NULL,
  `landline_phone` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `full_address` varchar(255) DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `last_transaction_date` date DEFAULT NULL,
  `activity_status` enum('active','inactive','deactivated') DEFAULT NULL,
  `activity_status_updated_at` datetime DEFAULT NULL,
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

INSERT INTO `clients` (`client_id`, `reference_code`, `client_number`, `client_type`, `client_name`, `first_name`, `middle_name`, `last_name`, `salutation`, `suffix`, `last_name_first`, `comma_separated`, `middle_initial_only`, `date_of_birth`, `gender`, `nationality`, `client_since`, `spouse_name`, `spouse_birthdate`, `spouse_occupation`, `id_type`, `id_number`, `tin_number`, `occupation`, `company_name`, `designation`, `business_type`, `business_address`, `business_ctm`, `business_province`, `home_address`, `home_ctm`, `home_province`, `mailing_address_type`, `region`, `office_phone`, `home_phone`, `contact_person`, `ap_sl_code`, `ar_sl_code`, `client_classification`, `agent_type`, `head_agent_name`, `agent_branch`, `mobile_phone`, `landline_phone`, `email`, `full_address`, `submitted_by`, `submitted_at`, `last_transaction_date`, `activity_status`, `activity_status_updated_at`, `verification_status`, `verification_date`, `verified_by`, `rejection_reason`, `total_clients_count`, `pending_kyc_count`, `verified_count`, `rejected_count`, `created_at`, `updated_at`) VALUES
(53, 'REF - 000001', 'CN - 004108', 'corporate', 'Jollibee', '', NULL, '', NULL, NULL, 0, 0, 0, NULL, 'male', 'Filipino', '2026-04-23', NULL, NULL, NULL, 'senior_citizen_id', 'adw', '12345678', NULL, 'Jollibee', 'Ezekiel Robin Codillo', 'government', 'Land Transportation Office - NCR Regional Office, Pamplona Tres, City of Las Piñas, NCR, NCR', 'City of Las Piñas', 'NCR', NULL, NULL, NULL, NULL, 'NCR', '09959735489', NULL, 'Paulynous', '42312', '31255', 'client', NULL, NULL, NULL, NULL, NULL, 'ezekielcodillo56@gmail.com', NULL, 1, '2026-04-23 13:35:33', NULL, 'active', '2026-04-23 13:42:51', 'draft', NULL, 9, 'Resubmission requested by Head Office review', 0, 0, 0, 0, '2026-04-23 05:35:33', '2026-04-23 05:42:51'),
(54, 'REF - 000002', 'CN - 483547', 'corporate', 'Jollibee', '', NULL, '', NULL, NULL, 0, 0, 0, NULL, 'male', 'Filipino', '2026-04-23', NULL, NULL, NULL, 'drivers_license', 'zxz', '12345678', NULL, 'Jollibee', 'Ezekiel Robin Codillo', 'government', 'Land Transportation Office - NCR Regional Office, Urdaneta, Magallanes, Cavite, CALABARZON', 'Magallanes', 'Cavite', NULL, NULL, NULL, NULL, 'CALABARZON', '09959735489', NULL, 'Jollibee', '42312', '31255', 'client', NULL, NULL, NULL, NULL, NULL, 'ezekielcodillo56@gmail.com', NULL, 1, '2026-04-23 13:36:50', NULL, 'active', '2026-04-23 13:42:51', 'pending', NULL, NULL, NULL, 0, 0, 0, 0, '2026-04-23 05:36:50', '2026-04-23 05:42:51');

-- --------------------------------------------------------

--
-- Table structure for table `client_approvals`
--

CREATE TABLE `client_approvals` (
  `approval_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `reference_code` varchar(50) NOT NULL,
  `client_number` varchar(30) DEFAULT NULL,
  `client_classification` enum('client','agent') NOT NULL DEFAULT 'client',
  `agent_type` enum('agent','sub_agent') DEFAULT NULL,
  `head_agent_name` varchar(150) DEFAULT NULL,
  `agent_branch` varchar(80) DEFAULT NULL,
  `client_type` enum('individual','corporate','obligee') NOT NULL DEFAULT 'individual',
  `display_name` varchar(200) DEFAULT NULL,
  `client_name` varchar(200) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `mobile_phone` varchar(20) DEFAULT NULL,
  `office_phone` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_by_branch` varchar(80) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `approval_status` enum('pending','approved','declined','resubmit') NOT NULL DEFAULT 'pending',
  `review_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `client_approvals`
--

INSERT INTO `client_approvals` (`approval_id`, `client_id`, `reference_code`, `client_number`, `client_classification`, `agent_type`, `head_agent_name`, `agent_branch`, `client_type`, `display_name`, `client_name`, `first_name`, `middle_name`, `last_name`, `contact_person`, `mobile_phone`, `office_phone`, `email`, `submitted_by`, `submitted_by_branch`, `submitted_at`, `approval_status`, `review_notes`, `reviewed_by`, `reviewed_at`, `approved_at`, `created_at`, `updated_at`) VALUES
(45, 53, 'REF - 000001', 'CN - 004108', 'client', NULL, NULL, NULL, 'corporate', 'Jollibee', 'Jollibee', '', NULL, '', 'Paulynous', NULL, '09959735489', 'ezekielcodillo56@gmail.com', 1, 'ALABANG BRANCH', '2026-04-23 13:35:33', 'resubmit', NULL, 9, '2026-04-23 13:40:03', NULL, '2026-04-23 05:35:33', '2026-04-23 05:40:03'),
(46, 54, 'REF - 000002', 'CN - 483547', 'client', NULL, NULL, NULL, 'corporate', 'Jollibee', 'Jollibee', '', NULL, '', 'Jollibee', NULL, '09959735489', 'ezekielcodillo56@gmail.com', 1, 'ALABANG BRANCH', '2026-04-23 13:36:50', 'approved', NULL, 9, '2026-04-23 13:38:22', '2026-04-23 13:38:22', '2026-04-23 05:36:50', '2026-04-23 05:38:22');

-- --------------------------------------------------------

--
-- Table structure for table `client_approval_status_history`
--

CREATE TABLE `client_approval_status_history` (
  `history_id` int(11) NOT NULL,
  `approval_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `reference_code` varchar(50) NOT NULL,
  `previous_status` enum('pending','approved','declined','resubmit') DEFAULT NULL,
  `new_status` enum('pending','approved','declined','resubmit') NOT NULL,
  `review_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `client_approval_status_history`
--

INSERT INTO `client_approval_status_history` (`history_id`, `approval_id`, `client_id`, `reference_code`, `previous_status`, `new_status`, `review_notes`, `reviewed_by`, `reviewed_at`, `created_at`) VALUES
(25, 46, 54, 'REF - 000002', 'pending', 'approved', NULL, 9, '2026-04-23 13:38:22', '2026-04-23 05:38:22'),
(26, 45, 53, 'REF - 000001', 'pending', 'resubmit', NULL, 9, '2026-04-23 13:40:03', '2026-04-23 05:40:03');

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
(14, 33, 53, 'Post_OJT_REQ_-_CHECKLIST.png', 'image/png', 88251, 'uploads/client_53_kyc_33_1776922533_f89236c0f70fcb57.png', 'government_id', 1, '2026-04-23 05:35:33', 'pending', NULL),
(15, 34, 54, 'Post_OJT_REQ_-_CHECKLIST.png', 'image/png', 88251, 'uploads/client_54_kyc_34_1776922610_58816017a63e05e8.png', 'government_id', 1, '2026-04-23 05:36:50', 'pending', NULL);

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
  `agent_type` enum('agent','sub_agent') DEFAULT NULL,
  `head_agent_name` varchar(150) DEFAULT NULL,
  `agent_branch` varchar(80) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `id_type` varchar(50) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `tin_number` varchar(50) DEFAULT NULL,
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

INSERT INTO `kyc_verifications` (`kyc_id`, `client_id`, `reference_code`, `step_current`, `step_1_completed`, `step_2_completed`, `step_3_completed`, `step_4_completed`, `ref_code`, `client_type`, `agent_type`, `head_agent_name`, `agent_branch`, `last_name`, `first_name`, `middle_name`, `suffix`, `birthdate`, `gender`, `nationality`, `id_type`, `id_number`, `tin_number`, `occupation`, `company`, `mobile`, `phone`, `email`, `address`, `status`, `created_at`, `updated_at`, `submitted_at`) VALUES
(33, 53, 'REF - 000001', 4, 1, 1, 1, 1, 'REF - 000001', 'corporate', NULL, NULL, NULL, '', '', '', '', NULL, 'male', 'Filipino', 'senior_citizen_id', 'adw', '12345678', 'Paulynous', 'Jollibee', '09959735489', '09959735489', 'ezekielcodillo56@gmail.com', 'Land Transportation Office - NCR Regional Office, Pamplona Tres, City of Las Piñas, NCR, NCR', 'draft', '2026-04-23 05:35:33', '2026-04-23 05:40:03', '2026-04-23 13:35:33'),
(34, 54, 'REF - 000002', 4, 1, 1, 1, 1, 'REF - 000002', 'corporate', NULL, NULL, NULL, '', '', '', '', NULL, 'male', 'Filipino', 'drivers_license', 'zxz', '12345678', 'Jollibee', 'Jollibee', '09959735489', '09959735489', 'ezekielcodillo56@gmail.com', 'Land Transportation Office - NCR Regional Office, Urdaneta, Magallanes, Cavite, CALABARZON', 'submitted', '2026-04-23 05:36:50', '2026-04-23 05:36:50', '2026-04-23 13:36:50');

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
  `branch` enum('ALABANG BRANCH','MANILA BRANCH I','MANILA BRANCH II','WEST AVENUE BRANCH','CUBAO BRANCH','ANGELES BRANCH','BATANGAS BRANCH','BACOLOD BRANCH','CABANATUAN BRANCH','BUTUAN BRANCH','CAGAYAN DE ORO BRANCH','CEBU BRANCH','CEBU REGIONAL OFFICE BRANCH','DAGUPAN BRANCH','DAVAO I BRANCH','DAVAO II BRANCH','GENSAN BRANCH','ISABELA BRANCH','LA UNION BRANCH','LAOAG BRANCH','LEGAZPI I BRANCH','LEGAZPI II BRANCH','MINDORO BRANCH','NAGA BRANCH','ORMOC BRANCH','OZAMIZ BRANCH','PAGADIAN BRANCH','SAN FERNANDO, PAMPANGA BRANCH','HEAD OFFICE BRANCH','SMRO BRANCH','TACLOBAN BRANCH','TUGUEGARAO BRANCH','VIGAN BRANCH','ILOILO BRANCH') NOT NULL DEFAULT 'ALABANG BRANCH',
  `role` varchar(30) DEFAULT 'kyc_officer',
  `account_classification` enum('head_office','branch_manager','kyc_officer') NOT NULL DEFAULT 'kyc_officer',
  `account_level` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `avatar_initials` varchar(5) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `department`, `branch`, `role`, `account_classification`, `account_level`, `avatar_initials`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Juan Dela Cruz', 'juan@sterlingins.com', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'kyc-officer', 'ALABANG BRANCH', 'kyc_officer', 'kyc_officer', 1, 'JD', 'active', '2026-04-23 13:40:24', '2026-03-17 03:27:01', '2026-04-23 05:40:24'),
(2, 'Maria Garcia', 'maria@sterlingins.com', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'compliance', 'MANILA BRANCH I', 'kyc_officer', 'kyc_officer', 1, 'MG', 'active', '2026-04-16 02:48:07', '2026-03-17 03:27:01', '2026-04-16 00:48:07'),
(3, 'Robert Santos', 'robert@sterlingins.com', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'operations', 'MANILA BRANCH II', 'manager', 'branch_manager', 2, 'RS', 'active', NULL, '2026-03-17 03:27:01', '2026-04-20 01:46:22'),
(4, 'Angela Torres', 'angela@sterlingins.com', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'kyc-officer', 'WEST AVENUE BRANCH', 'kyc_officer', 'kyc_officer', 1, 'AT', 'active', NULL, '2026-03-17 03:27:01', '2026-04-06 00:39:37'),
(5, 'John Reyes', 'john@sterlingins.com', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'compliance', 'CUBAO BRANCH', 'kyc_officer', 'kyc_officer', 1, 'JR', 'active', NULL, '2026-03-17 03:27:01', '2026-04-06 00:39:37'),
(6, 'Luisa Cruz', 'luisa@sterlingins.com', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'kyc-officer', 'CEBU BRANCH', 'kyc_officer', 'kyc_officer', 1, 'LC', 'active', '2026-04-06 07:40:36', '2026-03-17 03:27:01', '2026-04-06 05:40:36'),
(7, 'Ezekiel Robin Codillo', 'ezekielcodillo56@gmail.com', '7a12a69239582aaeffc5010f059685d5756b2996dc1853f0c973ce72f93b5f39', 'kyc-officer', 'BATANGAS BRANCH', 'kyc_officer', 'kyc_officer', 1, 'EC', 'active', NULL, '2026-03-23 03:36:50', '2026-04-06 00:39:37'),
(8, 'Paulynous K. Gonzales', 'gonzalespaul528@gmail.com', '7a12a69239582aaeffc5010f059685d5756b2996dc1853f0c973ce72f93b5f39', 'kyc-officer', 'ILOILO BRANCH', 'kyc_officer', 'kyc_officer', 1, 'PG', 'active', '2026-03-24 08:37:59', '2026-03-23 07:37:10', '2026-04-06 00:39:37'),
(9, 'Jun H. Geronimo', 'junix@sterlingins.com', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'KYC', 'HEAD OFFICE BRANCH', 'kyc_officer', 'head_office', 3, 'JH', 'active', '2026-04-23 13:49:27', '2026-04-06 05:37:39', '2026-04-23 05:49:27'),
(10, 'Eizomi', 'eizomi@sterling-insurance.com.ph', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'KYC', 'CEBU REGIONAL OFFICE BRANCH', 'kyc_officer', 'kyc_officer', 1, 'E', 'active', '2026-04-20 15:03:47', '2026-04-16 02:41:31', '2026-04-20 07:03:47'),
(11, 'Paul', 'paul@sterling-insurance.com.ph', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'KYC', 'DAGUPAN BRANCH', 'kyc_officer', 'kyc_officer', 1, 'P', 'active', '2026-04-16 13:46:54', '2026-04-16 05:42:02', '2026-04-16 05:46:54'),
(12, 'Ezekiel Robin Codillo', 'ezekielcodillo56@sterling-insurance.com.ph', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'KYC', 'CAGAYAN DE ORO BRANCH', 'kyc_officer', 'kyc_officer', 1, 'EC', 'active', NULL, '2026-04-17 07:49:07', '2026-04-17 07:49:07'),
(13, 'Alexus Magpantay', 'alex@sterling-insurance.com.ph', '7f27475990b287a3fb34b9e3521e67dfffae849e87edf35f54a72e909c64bcb3', 'BRANCH MANAGEMENT', 'ALABANG BRANCH', 'manager', 'branch_manager', 2, 'AM', 'active', NULL, '2026-04-20 02:29:24', '2026-04-20 02:29:24');

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
-- Indexes for dumped tables
--

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`agent_id`),
  ADD UNIQUE KEY `uniq_agents_client_id` (`client_id`),
  ADD UNIQUE KEY `uniq_agents_reference_code` (`reference_code`),
  ADD KEY `idx_agents_client_type` (`client_type`),
  ADD KEY `idx_agents_status` (`verification_status`),
  ADD KEY `idx_agents_submitted_by` (`submitted_by`),
  ADD KEY `idx_agents_verified_by` (`verified_by`),
  ADD KEY `idx_agents_agent_type` (`agent_type`),
  ADD KEY `idx_agents_agent_branch` (`agent_branch`),
  ADD KEY `idx_agents_last_transaction_date` (`last_transaction_date`),
  ADD KEY `idx_agents_activity_status` (`activity_status`);

--
-- Indexes for table `agent_approvals`
--
ALTER TABLE `agent_approvals`
  ADD PRIMARY KEY (`approval_id`),
  ADD UNIQUE KEY `uniq_agent_approvals_client_id` (`client_id`),
  ADD UNIQUE KEY `uniq_agent_approvals_reference_code` (`reference_code`),
  ADD KEY `idx_agent_approvals_status` (`approval_status`),
  ADD KEY `idx_agent_approvals_classification` (`client_classification`),
  ADD KEY `idx_agent_approvals_type` (`client_type`),
  ADD KEY `idx_agent_approvals_submitted_by` (`submitted_by`),
  ADD KEY `idx_agent_approvals_reviewed_by` (`reviewed_by`),
  ADD KEY `idx_agent_approvals_agent_type` (`agent_type`),
  ADD KEY `idx_agent_approvals_agent_branch` (`agent_branch`);

--
-- Indexes for table `agent_approval_status_history`
--
ALTER TABLE `agent_approval_status_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_agent_history_approval_id` (`approval_id`),
  ADD KEY `idx_agent_history_client_id` (`client_id`),
  ADD KEY `idx_agent_history_reviewed_by` (`reviewed_by`),
  ADD KEY `idx_agent_history_reviewed_at` (`reviewed_at`);

--
-- Indexes for table `approved_agents`
--
ALTER TABLE `approved_agents`
  ADD PRIMARY KEY (`agent_id`),
  ADD UNIQUE KEY `uniq_approved_agents_client_id` (`client_id`),
  ADD UNIQUE KEY `uniq_approved_agents_reference_code` (`reference_code`),
  ADD KEY `idx_approved_agents_client_type` (`client_type`),
  ADD KEY `idx_approved_agents_status` (`verification_status`),
  ADD KEY `idx_approved_agents_submitted_by` (`submitted_by`),
  ADD KEY `idx_approved_agents_verified_by` (`verified_by`),
  ADD KEY `idx_approved_agents_last_transaction_date` (`last_transaction_date`),
  ADD KEY `idx_approved_agents_activity_status` (`activity_status`),
  ADD KEY `idx_approved_agents_agent_type` (`agent_type`),
  ADD KEY `idx_approved_agents_agent_branch` (`agent_branch`);

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
  ADD KEY `submitted_by` (`submitted_by`),
  ADD KEY `idx_clients_agent_type` (`agent_type`),
  ADD KEY `idx_clients_agent_branch` (`agent_branch`),
  ADD KEY `idx_clients_last_transaction_date` (`last_transaction_date`),
  ADD KEY `idx_clients_activity_status` (`activity_status`);

--
-- Indexes for table `client_approvals`
--
ALTER TABLE `client_approvals`
  ADD PRIMARY KEY (`approval_id`),
  ADD UNIQUE KEY `uniq_client_approvals_client_id` (`client_id`),
  ADD UNIQUE KEY `uniq_client_approvals_reference_code` (`reference_code`),
  ADD KEY `idx_client_approvals_status` (`approval_status`),
  ADD KEY `idx_client_approvals_classification` (`client_classification`),
  ADD KEY `idx_client_approvals_type` (`client_type`),
  ADD KEY `idx_client_approvals_submitted_by` (`submitted_by`),
  ADD KEY `idx_client_approvals_reviewed_by` (`reviewed_by`),
  ADD KEY `idx_client_approvals_agent_type` (`agent_type`),
  ADD KEY `idx_client_approvals_agent_branch` (`agent_branch`);

--
-- Indexes for table `client_approval_status_history`
--
ALTER TABLE `client_approval_status_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_cash_approval_id` (`approval_id`),
  ADD KEY `idx_cash_client_id` (`client_id`),
  ADD KEY `idx_cash_reviewed_by` (`reviewed_by`),
  ADD KEY `idx_cash_reviewed_at` (`reviewed_at`);

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
  ADD KEY `idx_kyc_client_status` (`client_id`,`status`),
  ADD KEY `idx_kyc_verifications_agent_type` (`agent_type`),
  ADD KEY `idx_kyc_verifications_agent_branch` (`agent_branch`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_users_role_status` (`role`,`status`),
  ADD KEY `idx_users_branch` (`branch`);

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
-- AUTO_INCREMENT for table `agents`
--
ALTER TABLE `agents`
  MODIFY `agent_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `agent_approvals`
--
ALTER TABLE `agent_approvals`
  MODIFY `approval_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `agent_approval_status_history`
--
ALTER TABLE `agent_approval_status_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `approved_agents`
--
ALTER TABLE `approved_agents`
  MODIFY `agent_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `client_approvals`
--
ALTER TABLE `client_approvals`
  MODIFY `approval_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `client_approval_status_history`
--
ALTER TABLE `client_approval_status_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `kyc_verifications`
--
ALTER TABLE `kyc_verifications`
  MODIFY `kyc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `verification_history`
--
ALTER TABLE `verification_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `agents`
--
ALTER TABLE `agents`
  ADD CONSTRAINT `agents_ibfk_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agents_ibfk_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `agents_ibfk_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `agent_approvals`
--
ALTER TABLE `agent_approvals`
  ADD CONSTRAINT `agent_approvals_ibfk_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agent_approvals_ibfk_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `agent_approvals_ibfk_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `agent_approval_status_history`
--
ALTER TABLE `agent_approval_status_history`
  ADD CONSTRAINT `agent_history_ibfk_approval` FOREIGN KEY (`approval_id`) REFERENCES `agent_approvals` (`approval_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agent_history_ibfk_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agent_history_ibfk_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `approved_agents`
--
ALTER TABLE `approved_agents`
  ADD CONSTRAINT `approved_agents_ibfk_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `approved_agents_ibfk_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `approved_agents_ibfk_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

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
-- Constraints for table `client_approvals`
--
ALTER TABLE `client_approvals`
  ADD CONSTRAINT `client_approvals_ibfk_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_approvals_ibfk_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `client_approvals_ibfk_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `client_approval_status_history`
--
ALTER TABLE `client_approval_status_history`
  ADD CONSTRAINT `cash_ibfk_approval` FOREIGN KEY (`approval_id`) REFERENCES `client_approvals` (`approval_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cash_ibfk_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cash_ibfk_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

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
