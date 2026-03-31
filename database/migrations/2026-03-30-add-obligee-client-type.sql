-- Add obligee as a valid client type in clients table.
ALTER TABLE clients
MODIFY COLUMN client_type ENUM('individual','corporate','obligee') NOT NULL;
