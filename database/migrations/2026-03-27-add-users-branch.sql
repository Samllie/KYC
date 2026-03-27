-- Add branch column to users for registration branch selection
ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `branch` varchar(80) NOT NULL DEFAULT 'ALABANG BRANCH' AFTER `department`;

UPDATE `users`
SET `branch` = 'ALABANG BRANCH'
WHERE `branch` IS NULL OR `branch` = '';
