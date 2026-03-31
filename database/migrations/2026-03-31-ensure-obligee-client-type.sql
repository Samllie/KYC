-- Ensure obligee remains a supported client type in clients table.
-- Safe to run on environments that have not applied earlier obligee migrations.
ALTER TABLE clients
MODIFY COLUMN client_type ENUM('individual','corporate','obligee') NOT NULL;
