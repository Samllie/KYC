-- Ensure branch values exist for both newly registering and already registered accounts.
ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `branch` varchar(80) NOT NULL DEFAULT 'ALABANG BRANCH' AFTER `department`;

UPDATE `users`
SET `branch` = UPPER(TRIM(`branch`))
WHERE `branch` IS NOT NULL;

UPDATE `users`
SET `branch` = 'ALABANG BRANCH'
WHERE `branch` IS NULL OR `branch` = '';

-- Backfill known seeded accounts only when still on the default branch.
UPDATE `users` SET `branch` = 'MANILA BRANCH I' WHERE `email` = 'maria@sterlingins.com' AND `branch` = 'ALABANG BRANCH';
UPDATE `users` SET `branch` = 'MANILA BRANCH II' WHERE `email` = 'robert@sterlingins.com' AND `branch` = 'ALABANG BRANCH';
UPDATE `users` SET `branch` = 'WEST AVENUE BRANCH' WHERE `email` = 'angela@sterlingins.com' AND `branch` = 'ALABANG BRANCH';
UPDATE `users` SET `branch` = 'CUBAO BRANCH' WHERE `email` = 'john@sterlingins.com' AND `branch` = 'ALABANG BRANCH';
UPDATE `users` SET `branch` = 'CEBU BRANCH' WHERE `email` = 'luisa@sterlingins.com' AND `branch` = 'ALABANG BRANCH';
UPDATE `users` SET `branch` = 'BATANGAS BRANCH' WHERE `email` = 'ezekielcodillo56@gmail.com' AND `branch` = 'ALABANG BRANCH';
UPDATE `users` SET `branch` = 'ILOILO BRANCH' WHERE `email` = 'gonzalespaul528@gmail.com' AND `branch` = 'ALABANG BRANCH';

UPDATE `users`
SET `branch` = 'ALABANG BRANCH'
WHERE `branch` NOT IN (
  'ALABANG BRANCH',
  'MANILA BRANCH I',
  'MANILA BRANCH II',
  'WEST AVENUE BRANCH',
  'CUBAO BRANCH',
  'ANGELES BRANCH',
  'BATANGAS BRANCH',
  'BACOLOD BRANCH',
  'CABANATUAN BRANCH',
  'BUTUAN BRANCH',
  'CAGAYAN DE ORO BRANCH',
  'CEBU BRANCH',
  'CEBU REGIONAL OFFICE BRANCH',
  'DAGUPAN BRANCH',
  'DAVAO I BRANCH',
  'DAVAO II BRANCH',
  'GENSAN BRANCH',
  'ISABELA BRANCH',
  'LA UNION BRANCH',
  'LAOAG BRANCH',
  'LEGAZPI I BRANCH',
  'LEGAZPI II BRANCH',
  'MINDORO BRANCH',
  'NAGA BRANCH',
  'ORMOC BRANCH',
  'OZAMIZ BRANCH',
  'PAGADIAN BRANCH',
  'SAN FERNANDO, PAMPANGA BRANCH',
  'HEAD OFFICE BRANCH',
  'SMRO BRANCH',
  'TACLOBAN BRANCH',
  'TUGUEGARAO BRANCH',
  'VIGAN BRANCH',
  'ILOILO BRANCH'
);

ALTER TABLE `users`
MODIFY COLUMN `branch` enum(
  'ALABANG BRANCH',
  'MANILA BRANCH I',
  'MANILA BRANCH II',
  'WEST AVENUE BRANCH',
  'CUBAO BRANCH',
  'ANGELES BRANCH',
  'BATANGAS BRANCH',
  'BACOLOD BRANCH',
  'CABANATUAN BRANCH',
  'BUTUAN BRANCH',
  'CAGAYAN DE ORO BRANCH',
  'CEBU BRANCH',
  'CEBU REGIONAL OFFICE BRANCH',
  'DAGUPAN BRANCH',
  'DAVAO I BRANCH',
  'DAVAO II BRANCH',
  'GENSAN BRANCH',
  'ISABELA BRANCH',
  'LA UNION BRANCH',
  'LAOAG BRANCH',
  'LEGAZPI I BRANCH',
  'LEGAZPI II BRANCH',
  'MINDORO BRANCH',
  'NAGA BRANCH',
  'ORMOC BRANCH',
  'OZAMIZ BRANCH',
  'PAGADIAN BRANCH',
  'SAN FERNANDO, PAMPANGA BRANCH',
  'HEAD OFFICE BRANCH',
  'SMRO BRANCH',
  'TACLOBAN BRANCH',
  'TUGUEGARAO BRANCH',
  'VIGAN BRANCH',
  'ILOILO BRANCH'
) NOT NULL DEFAULT 'ALABANG BRANCH';

SET @idx_users_branch_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'users'
    AND index_name = 'idx_users_branch'
);

SET @idx_users_branch_sql = IF(
  @idx_users_branch_exists = 0,
  'CREATE INDEX `idx_users_branch` ON `users` (`branch`)',
  'SELECT 1'
);

PREPARE idx_users_branch_stmt FROM @idx_users_branch_sql;
EXECUTE idx_users_branch_stmt;
DEALLOCATE PREPARE idx_users_branch_stmt;
