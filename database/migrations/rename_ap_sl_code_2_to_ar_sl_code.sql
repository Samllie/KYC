-- Revert ap_sl_code_2 back to legacy ar_sl_code in clients table.
-- Safe for repeated runs: renames when new exists, adds old when neither exists.

SET @db_name = DATABASE();

SET @has_old = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'clients'
      AND COLUMN_NAME = 'ar_sl_code'
);

SET @has_new = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'clients'
      AND COLUMN_NAME = 'ap_sl_code_2'
);

SET @ddl = IF(
    @has_new = 1 AND @has_old = 0,
    'ALTER TABLE clients CHANGE COLUMN ap_sl_code_2 ar_sl_code VARCHAR(50) DEFAULT NULL',
    IF(
        @has_new = 0 AND @has_old = 0,
        'ALTER TABLE clients ADD COLUMN ar_sl_code VARCHAR(50) DEFAULT NULL AFTER ap_sl_code',
        'SELECT 1'
    )
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_old = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'clients'
      AND COLUMN_NAME = 'ar_sl_code'
);

SET @has_new = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'clients'
      AND COLUMN_NAME = 'ap_sl_code_2'
);

SET @backfill = IF(
    @has_old = 1 AND @has_new = 1,
    'UPDATE clients SET ar_sl_code = ap_sl_code_2 WHERE (ar_sl_code IS NULL OR ar_sl_code = '''') AND (ap_sl_code_2 IS NOT NULL AND ap_sl_code_2 <> '''')',
    'SELECT 1'
);

PREPARE stmt2 FROM @backfill;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
