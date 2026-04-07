-- Head Office approval queue for incoming KYC client/agent submissions.
CREATE TABLE IF NOT EXISTS `client_approvals` (
  `approval_id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `reference_code` varchar(50) NOT NULL,
  `client_number` varchar(30) DEFAULT NULL,
  `client_classification` enum('client','agent') NOT NULL DEFAULT 'client',
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`approval_id`),
  UNIQUE KEY `uniq_client_approvals_client_id` (`client_id`),
  UNIQUE KEY `uniq_client_approvals_reference_code` (`reference_code`),
  KEY `idx_client_approvals_status` (`approval_status`),
  KEY `idx_client_approvals_classification` (`client_classification`),
  KEY `idx_client_approvals_type` (`client_type`),
  KEY `idx_client_approvals_submitted_by` (`submitted_by`),
  KEY `idx_client_approvals_reviewed_by` (`reviewed_by`),
  CONSTRAINT `client_approvals_ibfk_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE,
  CONSTRAINT `client_approvals_ibfk_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `client_approvals_ibfk_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill existing clients into the approval queue with best-effort historical status mapping.
INSERT INTO `client_approvals` (
  `client_id`,
  `reference_code`,
  `client_number`,
  `client_classification`,
  `client_type`,
  `display_name`,
  `client_name`,
  `first_name`,
  `middle_name`,
  `last_name`,
  `contact_person`,
  `mobile_phone`,
  `office_phone`,
  `email`,
  `submitted_by`,
  `submitted_by_branch`,
  `submitted_at`,
  `approval_status`,
  `review_notes`,
  `reviewed_by`,
  `reviewed_at`,
  `approved_at`,
  `created_at`
)
SELECT
  c.`client_id`,
  c.`reference_code`,
  c.`client_number`,
  COALESCE(NULLIF(LOWER(TRIM(c.`client_classification`)), ''), 'client') AS `client_classification`,
  c.`client_type`,
  COALESCE(
    NULLIF(TRIM(c.`client_name`), ''),
    NULLIF(TRIM(c.`contact_person`), ''),
    NULLIF(TRIM(CONCAT(COALESCE(c.`first_name`, ''), ' ', COALESCE(c.`last_name`, ''))), ''),
    c.`reference_code`
  ) AS `display_name`,
  c.`client_name`,
  c.`first_name`,
  c.`middle_name`,
  c.`last_name`,
  c.`contact_person`,
  c.`mobile_phone`,
  c.`office_phone`,
  c.`email`,
  c.`submitted_by`,
  u.`branch` AS `submitted_by_branch`,
  c.`submitted_at`,
  CASE
    WHEN c.`verification_status` = 'verified' THEN 'approved'
    WHEN c.`verification_status` = 'rejected' THEN 'declined'
    ELSE 'pending'
  END AS `approval_status`,
  CASE
    WHEN c.`verification_status` = 'rejected' THEN c.`rejection_reason`
    ELSE NULL
  END AS `review_notes`,
  c.`verified_by` AS `reviewed_by`,
  CASE
    WHEN c.`verification_status` IN ('verified', 'rejected') THEN c.`verification_date`
    ELSE NULL
  END AS `reviewed_at`,
  CASE
    WHEN c.`verification_status` = 'verified' THEN c.`verification_date`
    ELSE NULL
  END AS `approved_at`,
  c.`created_at`
FROM `clients` c
LEFT JOIN `users` u ON u.`user_id` = c.`submitted_by`
ON DUPLICATE KEY UPDATE
  `client_number` = VALUES(`client_number`),
  `client_classification` = VALUES(`client_classification`),
  `client_type` = VALUES(`client_type`),
  `display_name` = VALUES(`display_name`),
  `client_name` = VALUES(`client_name`),
  `first_name` = VALUES(`first_name`),
  `middle_name` = VALUES(`middle_name`),
  `last_name` = VALUES(`last_name`),
  `contact_person` = VALUES(`contact_person`),
  `mobile_phone` = VALUES(`mobile_phone`),
  `office_phone` = VALUES(`office_phone`),
  `email` = VALUES(`email`),
  `submitted_by` = VALUES(`submitted_by`),
  `submitted_by_branch` = VALUES(`submitted_by_branch`),
  `submitted_at` = VALUES(`submitted_at`),
  `approval_status` = VALUES(`approval_status`),
  `review_notes` = VALUES(`review_notes`),
  `reviewed_by` = VALUES(`reviewed_by`),
  `reviewed_at` = VALUES(`reviewed_at`),
  `approved_at` = VALUES(`approved_at`),
  `updated_at` = CURRENT_TIMESTAMP;