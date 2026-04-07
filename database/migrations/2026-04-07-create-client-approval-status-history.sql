-- Track approval status transitions so officers can see before/after review states and remarks.
CREATE TABLE IF NOT EXISTS `client_approval_status_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `approval_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `reference_code` varchar(50) NOT NULL,
  `previous_status` enum('pending','approved','declined','resubmit') DEFAULT NULL,
  `new_status` enum('pending','approved','declined','resubmit') NOT NULL,
  `review_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`),
  KEY `idx_cash_approval_id` (`approval_id`),
  KEY `idx_cash_client_id` (`client_id`),
  KEY `idx_cash_reviewed_by` (`reviewed_by`),
  KEY `idx_cash_reviewed_at` (`reviewed_at`),
  CONSTRAINT `cash_ibfk_approval` FOREIGN KEY (`approval_id`) REFERENCES `client_approvals` (`approval_id`) ON DELETE CASCADE,
  CONSTRAINT `cash_ibfk_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE,
  CONSTRAINT `cash_ibfk_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill one history row for already-reviewed approvals.
INSERT INTO `client_approval_status_history` (
  `approval_id`,
  `client_id`,
  `reference_code`,
  `previous_status`,
  `new_status`,
  `review_notes`,
  `reviewed_by`,
  `reviewed_at`
)
SELECT
  ca.`approval_id`,
  ca.`client_id`,
  ca.`reference_code`,
  'pending' AS `previous_status`,
  ca.`approval_status` AS `new_status`,
  ca.`review_notes`,
  ca.`reviewed_by`,
  COALESCE(ca.`reviewed_at`, ca.`updated_at`) AS `reviewed_at`
FROM `client_approvals` ca
WHERE ca.`reviewed_at` IS NOT NULL
  AND ca.`approval_status` IN ('approved', 'declined', 'resubmit')
  AND NOT EXISTS (
    SELECT 1
    FROM `client_approval_status_history` h
    WHERE h.`approval_id` = ca.`approval_id`
  );
