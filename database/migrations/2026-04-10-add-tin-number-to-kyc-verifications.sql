-- Keep KYC verification storage aligned with the submit handler payload.
ALTER TABLE `kyc_verifications`
ADD COLUMN IF NOT EXISTS `tin_number` varchar(50) DEFAULT NULL AFTER `id_number`;