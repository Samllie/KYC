-- Add salutation column to clients table for individual client forms
ALTER TABLE clients
ADD COLUMN IF NOT EXISTS salutation VARCHAR(20) AFTER last_name;
