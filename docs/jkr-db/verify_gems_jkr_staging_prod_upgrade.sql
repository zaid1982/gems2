    -- ============================================================================
-- GEMS JKR staging/prod dump verification
-- Purpose: read-only checks after restoring/upgrading the target database.
-- Run this against the restored target schema.
-- Expected source: docs/jkr-db/gems_jkr_staging_prod.sql compared on 2026-05-20.
-- ============================================================================

SELECT 'base_table_count' AS check_name,
       IF(COUNT(*) = 141, 'PASS', 'FAIL') AS status,
       COUNT(*) AS actual,
       141 AS expected
  FROM information_schema.tables
 WHERE table_schema = DATABASE()
   AND table_type = 'BASE TABLE'
UNION ALL
SELECT 'view_count' AS check_name,
       IF(COUNT(*) = 3, 'PASS', 'FAIL') AS status,
       COUNT(*) AS actual,
       3 AS expected
  FROM information_schema.tables
 WHERE table_schema = DATABASE()
   AND table_type = 'VIEW';

SELECT 'required_views' AS check_name,
       IF(COUNT(*) = 3, 'PASS', 'FAIL') AS status,
       GROUP_CONCAT(table_name ORDER BY table_name) AS actual,
       'vw_item_image,vw_part_with_image,vw_ppm_set_asset_details' AS expected
  FROM information_schema.tables
 WHERE table_schema = DATABASE()
   AND table_type = 'VIEW'
   AND table_name IN ('vw_item_image', 'vw_part_with_image', 'vw_ppm_set_asset_details');

SELECT 'critical_tables' AS check_name,
       IF(COUNT(*) = 21, 'PASS', 'FAIL') AS status,
       COUNT(*) AS actual,
       21 AS expected
  FROM information_schema.tables
 WHERE table_schema = DATABASE()
   AND table_type = 'BASE TABLE'
   AND table_name IN (
       'wfl_flow', 'wfl_checkpoint', 'wfl_checkpoint_assign', 'wfl_checkpoint_user',
       'ref_role', 'sys_nav', 'sys_nav_role', 'email_template', 'email_parameter',
       'noti_text', 'noti_parameter', 'noti_web',
       'ptw_permit', 'ptw_worker', 'ptw_document', 'ptw_status_history',
       'ptw_approval_log', 'ptw_number_sequence',
       'cli_client', 'cli_site', 'cli_contract'
   );

SELECT 'noti_web_columns' AS check_name,
       IF(COUNT(*) = 11, 'PASS', 'FAIL') AS status,
       GROUP_CONCAT(column_name ORDER BY ordinal_position) AS actual,
       'noti_web_id,noti_web_type,user_id,noti_web_title,noti_web_text,noti_web_icon,noti_web_color,noti_web_link,nav_id,nav_second_id,noti_web_timestamp' AS expected
  FROM information_schema.columns
 WHERE table_schema = DATABASE()
   AND table_name = 'noti_web'
   AND column_name IN (
       'noti_web_id', 'noti_web_type', 'user_id', 'noti_web_title', 'noti_web_text',
       'noti_web_icon', 'noti_web_color', 'noti_web_link', 'nav_id', 'nav_second_id',
       'noti_web_timestamp'
   );

SELECT 'ptw_core_columns' AS check_name,
       IF(COUNT(*) = 8, 'PASS', 'FAIL') AS status,
       GROUP_CONCAT(column_name ORDER BY ordinal_position) AS actual,
      'ptw_permit_id,ptw_permit_number,ptw_request_number,ptw_risk_level,ptw_status,site_id,created_date,updated_date' AS expected
  FROM information_schema.columns
 WHERE table_schema = DATABASE()
   AND table_name = 'ptw_permit'
   AND column_name IN (
       'ptw_permit_id', 'ptw_permit_number', 'ptw_request_number', 'site_id',
       'ptw_status', 'ptw_risk_level', 'created_date', 'updated_date'
   );

SELECT 'config_count_wfl_flow' AS check_name, IF(COUNT(*) = 5, 'PASS', 'FAIL') AS status, COUNT(*) AS actual, 5 AS expected FROM wfl_flow
UNION ALL
SELECT 'config_count_wfl_checkpoint', IF(COUNT(*) = 33, 'PASS', 'FAIL'), COUNT(*), 33 FROM wfl_checkpoint
UNION ALL
SELECT 'config_count_wfl_checkpoint_assign', IF(COUNT(*) = 18, 'PASS', 'FAIL'), COUNT(*), 18 FROM wfl_checkpoint_assign
UNION ALL
SELECT 'config_count_ref_role', IF(COUNT(*) = 27, 'PASS', 'FAIL'), COUNT(*), 27 FROM ref_role
UNION ALL
SELECT 'config_count_sys_nav', IF(COUNT(*) = 26, 'PASS', 'FAIL'), COUNT(*), 26 FROM sys_nav
UNION ALL
SELECT 'config_count_sys_nav_role', IF(COUNT(*) = 305, 'PASS', 'FAIL'), COUNT(*), 305 FROM sys_nav_role
UNION ALL
SELECT 'config_count_email_template', IF(COUNT(*) = 27, 'PASS', 'FAIL'), COUNT(*), 27 FROM email_template
UNION ALL
SELECT 'config_count_email_parameter', IF(COUNT(*) = 59, 'PASS', 'FAIL'), COUNT(*), 59 FROM email_parameter
UNION ALL
SELECT 'config_count_noti_text', IF(COUNT(*) = 25, 'PASS', 'FAIL'), COUNT(*), 25 FROM noti_text
UNION ALL
SELECT 'config_count_noti_parameter', IF(COUNT(*) = 31, 'PASS', 'FAIL'), COUNT(*), 31 FROM noti_parameter
UNION ALL
SELECT 'config_count_cli_client', IF(COUNT(*) = 17, 'PASS', 'FAIL'), COUNT(*), 17 FROM cli_client
UNION ALL
SELECT 'config_count_cli_site', IF(COUNT(*) = 20, 'PASS', 'FAIL'), COUNT(*), 20 FROM cli_site
UNION ALL
SELECT 'config_count_cli_contract', IF(COUNT(*) = 20, 'PASS', 'FAIL'), COUNT(*), 20 FROM cli_contract;

-- Work Order workflow critical path.
SELECT 'wo_cp13_routes_to_cp20' AS check_name,
       IF(COUNT(*) = 1, 'PASS', 'FAIL') AS status
  FROM wfl_checkpoint
 WHERE checkpoint_id = 13
   AND flow_id = 2
   AND checkpoint_desc = 'Execute WO'
   AND checkpoint_next = 20
   AND checkpoint_case_1 = 12
   AND checkpoint_case_2 = 16
   AND checkpoint_case_3 = 13
   AND role_id = 8
UNION ALL
SELECT 'wo_cp20_check_wo_exists',
       IF(COUNT(*) = 1, 'PASS', 'FAIL')
  FROM wfl_checkpoint
 WHERE checkpoint_id = 20
   AND flow_id = 2
   AND checkpoint_desc = 'Check WO'
   AND checkpoint_next = 14
   AND checkpoint_case_1 = 16
   AND checkpoint_case_2 = 13
   AND checkpoint_case_3 IS NULL
   AND role_id = 7
   AND group_id = 1
   AND checkpoint_order = 5
UNION ALL
SELECT 'wo_cp19_verify_wr_assigner_role',
       IF(COUNT(*) = 1, 'PASS', 'FAIL')
  FROM wfl_checkpoint
 WHERE checkpoint_id = 19
   AND flow_id = 2
   AND checkpoint_desc = 'Verify WR'
   AND checkpoint_next = 13
   AND checkpoint_case_1 = 15
   AND checkpoint_case_2 = 18
   AND checkpoint_case_3 IS NULL
   AND role_id = 7
   AND group_id = 1;

SELECT 'wo_checkpoint_assign_edges' AS check_name,
       IF(COUNT(*) = 4, 'PASS', 'FAIL') AS status,
       GROUP_CONCAT(CONCAT(checkpoint_assign_type, ':', checkpoint_id, '->', checkpoint_to) ORDER BY checkpoint_id, checkpoint_to) AS actual,
       '1:12->20,1:17->16,1:17->19,1:17->20' AS expected
  FROM wfl_checkpoint_assign
 WHERE (checkpoint_assign_type = 1 AND checkpoint_id = 12 AND checkpoint_to = 20)
    OR (checkpoint_assign_type = 1 AND checkpoint_id = 17 AND checkpoint_to = 16)
    OR (checkpoint_assign_type = 1 AND checkpoint_id = 17 AND checkpoint_to = 19)
    OR (checkpoint_assign_type = 1 AND checkpoint_id = 17 AND checkpoint_to = 20);

-- Material Request workflow critical path.
SELECT 'mr_cp41_routes_to_reviewer' AS check_name,
       IF(COUNT(*) = 1, 'PASS', 'FAIL') AS status
  FROM wfl_checkpoint
 WHERE checkpoint_id = 41
   AND flow_id = 4
   AND checkpoint_desc = 'Request Material'
   AND checkpoint_next = 56
   AND role_id = 8
UNION ALL
SELECT 'mr_cp56_reviewer_exists',
       IF(COUNT(*) = 1, 'PASS', 'FAIL')
  FROM wfl_checkpoint
 WHERE checkpoint_id = 56
   AND flow_id = 4
   AND checkpoint_desc = 'MR Reviewer'
   AND checkpoint_next = 42
   AND checkpoint_case_1 = 45
   AND role_id = 27
   AND checkpoint_order = 2
UNION ALL
SELECT 'mr_cp42_verify_request_exists',
       IF(COUNT(*) = 1, 'PASS', 'FAIL')
  FROM wfl_checkpoint
 WHERE checkpoint_id = 42
   AND flow_id = 4
   AND checkpoint_desc = 'Verify Request'
   AND checkpoint_next = 43
   AND checkpoint_case_1 = 45
   AND role_id = 17;

-- Informational checks: these can vary by user setup, but should not be empty.
SELECT 'checkpoint_user_cp20_count' AS check_name, COUNT(*) AS actual FROM wfl_checkpoint_user WHERE checkpoint_id = 20
UNION ALL
SELECT 'checkpoint_user_cp56_count', COUNT(*) FROM wfl_checkpoint_user WHERE checkpoint_id = 56;
