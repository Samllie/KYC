-- Add account classification and level to users.
-- Classification is the source of truth; level is derived from it.

ALTER TABLE `users`
  ADD COLUMN `account_classification` enum('head_office','branch_manager','kyc_officer') NOT NULL DEFAULT 'kyc_officer' AFTER `role`,
  ADD COLUMN `account_level` tinyint(3) unsigned NOT NULL DEFAULT 1 AFTER `account_classification`;

UPDATE `users`
SET `account_classification` = CASE
    WHEN UPPER(TRIM(`role`)) = 'ADMIN'
      OR UPPER(TRIM(`department`)) = 'HEAD OFFICE'
      OR UPPER(TRIM(`branch`)) IN ('HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH') THEN 'head_office'
    WHEN UPPER(TRIM(`role`)) = 'MANAGER' THEN 'branch_manager'
    ELSE 'kyc_officer'
  END,
  `account_level` = CASE
    WHEN UPPER(TRIM(`role`)) = 'ADMIN'
      OR UPPER(TRIM(`department`)) = 'HEAD OFFICE'
      OR UPPER(TRIM(`branch`)) IN ('HEAD OFFICE', 'HEAD OFFICE BRANCH', 'SMRO', 'SMRO BRANCH') THEN 3
    WHEN UPPER(TRIM(`role`)) = 'MANAGER' THEN 2
    ELSE 1
  END;