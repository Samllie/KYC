-- Add new columns to clients table for Individual and Corporate client information
-- Only adding columns that don't already exist

ALTER TABLE clients 
ADD COLUMN client_name VARCHAR(200) AFTER client_type,
ADD COLUMN last_name_first BOOLEAN DEFAULT FALSE AFTER suffix,
ADD COLUMN comma_separated BOOLEAN DEFAULT FALSE AFTER last_name_first,
ADD COLUMN middle_initial_only BOOLEAN DEFAULT FALSE AFTER comma_separated,
ADD COLUMN client_since DATE AFTER nationality,
ADD COLUMN spouse_name VARCHAR(100) AFTER client_since,
ADD COLUMN spouse_birthdate DATE AFTER spouse_name,
ADD COLUMN spouse_occupation VARCHAR(100) AFTER spouse_birthdate,
ADD COLUMN tin_number VARCHAR(50) AFTER id_number,
ADD COLUMN designation VARCHAR(100) AFTER company_name,
ADD COLUMN business_type ENUM('private', 'government') AFTER designation,
ADD COLUMN business_address VARCHAR(255) AFTER business_type,
ADD COLUMN business_ctm VARCHAR(50) AFTER business_address,
ADD COLUMN business_province VARCHAR(50) AFTER business_ctm,
ADD COLUMN home_address VARCHAR(255) AFTER business_province,
ADD COLUMN home_ctm VARCHAR(50) AFTER home_address,
ADD COLUMN home_province VARCHAR(50) AFTER home_ctm,
ADD COLUMN mailing_address_type ENUM('business', 'home') AFTER home_province,
ADD COLUMN region VARCHAR(100) AFTER mailing_address_type,
ADD COLUMN office_phone VARCHAR(20) AFTER region,
ADD COLUMN home_phone VARCHAR(20) AFTER office_phone,
ADD COLUMN contact_person VARCHAR(100) AFTER home_phone,
ADD COLUMN ap_sl_code VARCHAR(50) AFTER contact_person,
ADD COLUMN ar_sl_code VARCHAR(50) AFTER ap_sl_code,
ADD COLUMN client_classification ENUM('client', 'agent') AFTER ar_sl_code;
