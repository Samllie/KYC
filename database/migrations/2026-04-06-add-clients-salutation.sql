-- Ensure individual KYC submission can store salutation without SQL errors.
ALTER TABLE `clients`
ADD COLUMN IF NOT EXISTS `salutation` varchar(20) DEFAULT NULL AFTER `last_name`;
