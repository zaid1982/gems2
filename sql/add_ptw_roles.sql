-- Seed PTW + MR workflow roles into ref_role with explicit IDs (must match UI: modal_user.html)
-- role_type 2 = module/workflow role (consistent with cloud / jkr_sync_mr_workflow.sql)

INSERT INTO ref_role (role_id, role_desc, role_type, role_status)
SELECT v.id, v.d, v.t, 1
FROM (
        SELECT 23 AS id, 'Standalone Material Requestor' AS d, 2 AS t
  UNION SELECT 24,       'PTW Supervisor',                    2
  UNION SELECT 25,       'PTW SHE',                           2
  UNION SELECT 26,       'PTW Facility Manager',              2
  UNION SELECT 27,       'MR Reviewer',                       1
) v
WHERE NOT EXISTS (SELECT 1 FROM ref_role r WHERE r.role_id = v.id);

-- Bump gems_role local cache version (version_id 2 in sys_version)
UPDATE sys_version SET version_no = version_no + 1 WHERE version_id = 2;
