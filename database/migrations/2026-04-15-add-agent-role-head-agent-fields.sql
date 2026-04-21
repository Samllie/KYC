-- Add agent assignment fields to the KYC submission flow and agent approval snapshots.
ALTER TABLE `clients`
  ADD COLUMN IF NOT EXISTS `agent_type` enum('agent','sub_agent') DEFAULT NULL AFTER `client_classification`,
  ADD COLUMN IF NOT EXISTS `head_agent_name` varchar(150) DEFAULT NULL AFTER `agent_type`,
  ADD COLUMN IF NOT EXISTS `agent_branch` varchar(80) DEFAULT NULL AFTER `head_agent_name`,
  ADD INDEX IF NOT EXISTS `idx_clients_agent_type` (`agent_type`),
  ADD INDEX IF NOT EXISTS `idx_clients_agent_branch` (`agent_branch`);

ALTER TABLE `kyc_verifications`
  ADD COLUMN IF NOT EXISTS `agent_type` enum('agent','sub_agent') DEFAULT NULL AFTER `client_type`,
  ADD COLUMN IF NOT EXISTS `head_agent_name` varchar(150) DEFAULT NULL AFTER `agent_type`,
  ADD COLUMN IF NOT EXISTS `agent_branch` varchar(80) DEFAULT NULL AFTER `head_agent_name`,
  ADD INDEX IF NOT EXISTS `idx_kyc_verifications_agent_type` (`agent_type`),
  ADD INDEX IF NOT EXISTS `idx_kyc_verifications_agent_branch` (`agent_branch`);

ALTER TABLE `client_approvals`
  ADD COLUMN IF NOT EXISTS `agent_type` enum('agent','sub_agent') DEFAULT NULL AFTER `client_classification`,
  ADD COLUMN IF NOT EXISTS `head_agent_name` varchar(150) DEFAULT NULL AFTER `agent_type`,
  ADD COLUMN IF NOT EXISTS `agent_branch` varchar(80) DEFAULT NULL AFTER `head_agent_name`,
  ADD INDEX IF NOT EXISTS `idx_client_approvals_agent_type` (`agent_type`),
  ADD INDEX IF NOT EXISTS `idx_client_approvals_agent_branch` (`agent_branch`);

ALTER TABLE `agent_approvals`
  ADD COLUMN IF NOT EXISTS `agent_type` enum('agent','sub_agent') DEFAULT NULL AFTER `client_classification`,
  ADD COLUMN IF NOT EXISTS `head_agent_name` varchar(150) DEFAULT NULL AFTER `agent_type`,
  ADD COLUMN IF NOT EXISTS `agent_branch` varchar(80) DEFAULT NULL AFTER `head_agent_name`,
  ADD INDEX IF NOT EXISTS `idx_agent_approvals_agent_type` (`agent_type`),
  ADD INDEX IF NOT EXISTS `idx_agent_approvals_agent_branch` (`agent_branch`);

ALTER TABLE `approved_agents`
  ADD COLUMN IF NOT EXISTS `agent_type` enum('agent','sub_agent') DEFAULT NULL AFTER `client_type`,
  ADD COLUMN IF NOT EXISTS `head_agent_name` varchar(150) DEFAULT NULL AFTER `agent_type`,
  ADD COLUMN IF NOT EXISTS `agent_branch` varchar(80) DEFAULT NULL AFTER `head_agent_name`,
  ADD INDEX IF NOT EXISTS `idx_approved_agents_agent_type` (`agent_type`),
  ADD INDEX IF NOT EXISTS `idx_approved_agents_agent_branch` (`agent_branch`);

-- Legacy fallback table for installs that do not yet have the original agents table.
CREATE TABLE IF NOT EXISTS `agents` (
  `agent_id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `reference_code` varchar(50) NOT NULL,
  `client_number` varchar(30) DEFAULT NULL,
  `client_type` enum('individual','corporate','obligee') NOT NULL DEFAULT 'individual',
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
  PRIMARY KEY (`agent_id`),
  UNIQUE KEY `uniq_agents_client_id` (`client_id`),
  UNIQUE KEY `uniq_agents_reference_code` (`reference_code`),
  KEY `idx_agents_client_type` (`client_type`),
  KEY `idx_agents_status` (`verification_status`),
  KEY `idx_agents_submitted_by` (`submitted_by`),
  KEY `idx_agents_verified_by` (`verified_by`),
  CONSTRAINT `agents_ibfk_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE,
  CONSTRAINT `agents_ibfk_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `agents_ibfk_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `agents`
  ADD COLUMN IF NOT EXISTS `agent_type` enum('agent','sub_agent') DEFAULT NULL AFTER `client_type`,
  ADD COLUMN IF NOT EXISTS `head_agent_name` varchar(150) DEFAULT NULL AFTER `agent_type`,
  ADD COLUMN IF NOT EXISTS `agent_branch` varchar(80) DEFAULT NULL AFTER `head_agent_name`,
  ADD INDEX IF NOT EXISTS `idx_agents_agent_type` (`agent_type`),
  ADD INDEX IF NOT EXISTS `idx_agents_agent_branch` (`agent_branch`);

-- Backfill the default agent role for existing agent-classified records.
UPDATE `clients` c
SET c.`agent_type` = 'agent'
WHERE COALESCE(NULLIF(LOWER(TRIM(c.`client_classification`)), ''), 'client') = 'agent'
  AND (c.`agent_type` IS NULL OR TRIM(c.`agent_type`) = '');

UPDATE `kyc_verifications` kv
JOIN `clients` c ON c.`client_id` = kv.`client_id`
SET kv.`agent_type` = 'agent'
WHERE COALESCE(NULLIF(LOWER(TRIM(c.`client_classification`)), ''), 'client') = 'agent'
  AND (kv.`agent_type` IS NULL OR TRIM(kv.`agent_type`) = '');

UPDATE `client_approvals` ca
JOIN `clients` c ON c.`client_id` = ca.`client_id`
SET ca.`agent_type` = 'agent'
WHERE COALESCE(NULLIF(LOWER(TRIM(c.`client_classification`)), ''), 'client') = 'agent'
  AND (ca.`agent_type` IS NULL OR TRIM(ca.`agent_type`) = '');

UPDATE `agent_approvals` aa
SET aa.`agent_type` = 'agent'
WHERE aa.`agent_type` IS NULL OR TRIM(aa.`agent_type`) = '';

UPDATE `approved_agents` aa
JOIN `clients` c ON c.`client_id` = aa.`client_id`
SET aa.`agent_type` = 'agent'
WHERE COALESCE(NULLIF(LOWER(TRIM(c.`client_classification`)), ''), 'client') = 'agent'
  AND (aa.`agent_type` IS NULL OR TRIM(aa.`agent_type`) = '');

UPDATE `agents` a
JOIN `clients` c ON c.`client_id` = a.`client_id`
SET a.`agent_type` = 'agent'
WHERE COALESCE(NULLIF(LOWER(TRIM(c.`client_classification`)), ''), 'client') = 'agent'
  AND (a.`agent_type` IS NULL OR TRIM(a.`agent_type`) = '');
