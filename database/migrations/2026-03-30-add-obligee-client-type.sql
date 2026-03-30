-- Add obligee as a supported client type.
-- This keeps the same data model as corporate and only extends allowed values.
ALTER TABLE `clients`
MODIFY COLUMN `client_type` enum('individual','corporate','obligee') NOT NULL;
