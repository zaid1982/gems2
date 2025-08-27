-- Seed missing PTW roles into ref_role (id will auto-increment)
-- Assumption: role_type=1 denotes internal roles; adjust if your convention differs.

INSERT INTO ref_role (role_desc, role_type, role_status)
SELECT 'PTW Supervisor', 1, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ref_role WHERE role_desc = 'PTW Supervisor');

INSERT INTO ref_role (role_desc, role_type, role_status)
SELECT 'PTW SHE', 1, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ref_role WHERE role_desc = 'PTW SHE');

INSERT INTO ref_role (role_desc, role_type, role_status)
SELECT 'PTW Facility Manager', 1, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ref_role WHERE role_desc = 'PTW Facility Manager');
