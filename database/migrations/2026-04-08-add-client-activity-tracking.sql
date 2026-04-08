SET @clients_has_activity_columns := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'clients'
    AND column_name = 'last_transaction_date'
);

SET @clients_sql := IF(
  @clients_has_activity_columns = 0,
  'ALTER TABLE `clients`
     ADD COLUMN `last_transaction_date` date DEFAULT NULL AFTER `submitted_at`,
     ADD COLUMN `activity_status` enum(''active'',''inactive'',''deactivated'') DEFAULT NULL AFTER `last_transaction_date`,
     ADD COLUMN `activity_status_updated_at` datetime DEFAULT NULL AFTER `activity_status`,
     ADD KEY `idx_clients_last_transaction_date` (`last_transaction_date`),
     ADD KEY `idx_clients_activity_status` (`activity_status`)',
  'SELECT 1'
);

PREPARE clients_stmt FROM @clients_sql;
EXECUTE clients_stmt;
DEALLOCATE PREPARE clients_stmt;

SET @agents_table_exists := (
  SELECT COUNT(*)
  FROM information_schema.tables
  WHERE table_schema = DATABASE()
    AND table_name = 'agents'
);

SET @agents_has_activity_columns := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'agents'
    AND column_name = 'last_transaction_date'
);

SET @agents_sql := IF(
  @agents_table_exists > 0 AND @agents_has_activity_columns = 0,
  'ALTER TABLE `agents`
     ADD COLUMN `last_transaction_date` date DEFAULT NULL AFTER `submitted_at`,
     ADD COLUMN `activity_status` enum(''active'',''inactive'',''deactivated'') DEFAULT NULL AFTER `last_transaction_date`,
     ADD COLUMN `activity_status_updated_at` datetime DEFAULT NULL AFTER `activity_status`,
     ADD KEY `idx_agents_last_transaction_date` (`last_transaction_date`),
     ADD KEY `idx_agents_activity_status` (`activity_status`)',
  'SELECT 1'
);

PREPARE agents_stmt FROM @agents_sql;
EXECUTE agents_stmt;
DEALLOCATE PREPARE agents_stmt;