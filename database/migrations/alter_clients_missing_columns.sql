-- =====================================================
-- ALTER TABLE: Add Missing Corporate & Business Columns
-- Run this if you have an existing clients table
-- =====================================================

ALTER TABLE clients ADD COLUMN IF NOT EXISTS business_type ENUM('private', 'government') AFTER designation;

ALTER TABLE clients ADD COLUMN IF NOT EXISTS business_address VARCHAR(255) AFTER business_type;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS business_ctm VARCHAR(50) AFTER business_address;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS business_province VARCHAR(50) AFTER business_ctm;

ALTER TABLE clients ADD COLUMN IF NOT EXISTS home_address VARCHAR(255) AFTER business_province;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS home_ctm VARCHAR(50) AFTER home_address;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS home_province VARCHAR(50) AFTER home_ctm;

ALTER TABLE clients ADD COLUMN IF NOT EXISTS mailing_address_type ENUM('business', 'home') AFTER home_province;

ALTER TABLE clients ADD COLUMN IF NOT EXISTS office_phone VARCHAR(20) AFTER contact_person;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS home_phone VARCHAR(20) AFTER office_phone;

ALTER TABLE clients ADD COLUMN IF NOT EXISTS ap_sl_code VARCHAR(50) AFTER ar_sl_code;

ALTER TABLE clients ADD COLUMN IF NOT EXISTS client_classification ENUM('client', 'agent') AFTER ap_sl_code;

-- Verify the columns were added
DESCRIBE clients;
