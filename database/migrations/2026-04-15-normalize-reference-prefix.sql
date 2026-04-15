-- Normalize existing reference codes to the uppercase REF format.
UPDATE clients
SET reference_code = CONCAT('REF - ', SUBSTRING(TRIM(reference_code), 7))
WHERE UPPER(TRIM(reference_code)) LIKE 'REF - %';

UPDATE client_approvals
SET reference_code = CONCAT('REF - ', SUBSTRING(TRIM(reference_code), 7))
WHERE UPPER(TRIM(reference_code)) LIKE 'REF - %';

UPDATE client_approval_status_history
SET reference_code = CONCAT('REF - ', SUBSTRING(TRIM(reference_code), 7))
WHERE UPPER(TRIM(reference_code)) LIKE 'REF - %';

UPDATE kyc_verifications
SET reference_code = CONCAT('REF - ', SUBSTRING(TRIM(reference_code), 7))
WHERE UPPER(TRIM(reference_code)) LIKE 'REF - %';

UPDATE kyc_verifications
SET ref_code = CONCAT('REF - ', SUBSTRING(TRIM(ref_code), 7))
WHERE UPPER(TRIM(ref_code)) LIKE 'REF - %';

UPDATE agents
SET reference_code = CONCAT('REF - ', SUBSTRING(TRIM(reference_code), 7))
WHERE UPPER(TRIM(reference_code)) LIKE 'REF - %';

UPDATE approved_agents
SET reference_code = CONCAT('REF - ', SUBSTRING(TRIM(reference_code), 7))
WHERE UPPER(TRIM(reference_code)) LIKE 'REF - %';

UPDATE agent_approvals
SET reference_code = CONCAT('REF - ', SUBSTRING(TRIM(reference_code), 7))
WHERE UPPER(TRIM(reference_code)) LIKE 'REF - %';

UPDATE agent_approval_status_history
SET reference_code = CONCAT('REF - ', SUBSTRING(TRIM(reference_code), 7))
WHERE UPPER(TRIM(reference_code)) LIKE 'REF - %';