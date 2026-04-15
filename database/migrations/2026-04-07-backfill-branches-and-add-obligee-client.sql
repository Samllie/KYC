-- Backfill missing approval branch metadata so branch filtering/display works consistently.
UPDATE client_approvals ca
LEFT JOIN users u ON u.user_id = ca.submitted_by
SET ca.submitted_by_branch = TRIM(u.branch)
WHERE (ca.submitted_by_branch IS NULL OR TRIM(ca.submitted_by_branch) = '')
  AND u.branch IS NOT NULL
  AND TRIM(u.branch) <> '';

-- Keep legacy rows visible in branch column/filter instead of rendering as N/A.
UPDATE client_approvals
SET submitted_by_branch = 'UNASSIGNED'
WHERE submitted_by_branch IS NULL OR TRIM(submitted_by_branch) = '';

-- Seed one obligee client when none exists yet.
INSERT INTO clients (
    reference_code,
    client_number,
    client_type,
    client_name,
    first_name,
    middle_name,
    last_name,
    contact_person,
    office_phone,
    email,
    client_classification,
    submitted_by,
    submitted_at,
    verification_status,
    verification_date,
    verified_by,
    created_at,
    updated_at
)
SELECT
    'Ref - 000004',
    'CN - 000004',
    'obligee',
    'Sample Obligee Client',
    'Sample',
    '',
    'Obligee',
    'Sample Obligee Contact',
    '02-7000-0000',
    'sample.obligee@sterling.test',
    'client',
    u.user_id,
    NOW(),
    'verified',
    NOW(),
    u.user_id,
    NOW(),
    NOW()
FROM users u
WHERE u.user_id = (
    SELECT MIN(u2.user_id)
    FROM users u2
)
AND NOT EXISTS (
    SELECT 1
    FROM clients c
    WHERE c.client_type = 'obligee'
);

-- Ensure obligee row is represented in approval queue and visible in list (approved).
INSERT INTO client_approvals (
    client_id,
    reference_code,
    client_number,
    client_classification,
    client_type,
    display_name,
    client_name,
    first_name,
    middle_name,
    last_name,
    contact_person,
    mobile_phone,
    office_phone,
    email,
    submitted_by,
    submitted_by_branch,
    submitted_at,
    approval_status,
    review_notes,
    reviewed_by,
    reviewed_at,
    approved_at,
    created_at
)
SELECT
    c.client_id,
    c.reference_code,
    c.client_number,
    COALESCE(NULLIF(LOWER(TRIM(c.client_classification)), ''), 'client'),
    c.client_type,
    COALESCE(
        NULLIF(TRIM(c.client_name), ''),
        NULLIF(TRIM(c.contact_person), ''),
        NULLIF(TRIM(CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, ''))), ''),
        c.reference_code
    ),
    c.client_name,
    c.first_name,
    c.middle_name,
    c.last_name,
    c.contact_person,
    c.mobile_phone,
    c.office_phone,
    c.email,
    c.submitted_by,
    COALESCE(NULLIF(TRIM(ca_existing.submitted_by_branch), ''), NULLIF(TRIM(u.branch), ''), 'UNASSIGNED'),
    COALESCE(c.submitted_at, NOW()),
    'approved',
    'Seeded obligee approval entry',
    COALESCE(c.verified_by, c.submitted_by),
    COALESCE(c.verification_date, NOW()),
    COALESCE(c.verification_date, NOW()),
    c.created_at
FROM clients c
LEFT JOIN users u ON u.user_id = c.submitted_by
LEFT JOIN client_approvals ca_existing ON ca_existing.client_id = c.client_id
WHERE c.client_type = 'obligee'
ORDER BY c.client_id DESC
LIMIT 1
ON DUPLICATE KEY UPDATE
    client_number = VALUES(client_number),
    client_classification = VALUES(client_classification),
    client_type = VALUES(client_type),
    display_name = VALUES(display_name),
    client_name = VALUES(client_name),
    first_name = VALUES(first_name),
    middle_name = VALUES(middle_name),
    last_name = VALUES(last_name),
    contact_person = VALUES(contact_person),
    mobile_phone = VALUES(mobile_phone),
    office_phone = VALUES(office_phone),
    email = VALUES(email),
    submitted_by = VALUES(submitted_by),
    submitted_by_branch = VALUES(submitted_by_branch),
    submitted_at = VALUES(submitted_at),
    approval_status = 'approved',
    review_notes = VALUES(review_notes),
    reviewed_by = VALUES(reviewed_by),
    reviewed_at = VALUES(reviewed_at),
    approved_at = VALUES(approved_at),
    updated_at = CURRENT_TIMESTAMP;
