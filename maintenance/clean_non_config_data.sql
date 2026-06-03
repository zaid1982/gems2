-- ============================================================================
-- GEMS2 non-configuration data cleanup
-- ============================================================================
-- Purpose:
--   Clear runtime/transactional data after patching an environment while keeping
--   system configuration, role/navigation setup, reference data, users, sites,
--   workflow definitions, notification templates, and email templates.
--
-- IMPORTANT:
--   This script is destructive only when @CONFIRM_CLEANUP is set to 1.
--   Leave @CONFIRM_CLEANUP = 0 to preview the table list and approximate rows.
--
-- Recommended order before using on a subsidiary database:
--   1. Take a full database backup.
--   2. Run with @CONFIRM_CLEANUP = 0 and review the output.
--   3. Set @CONFIRM_CLEANUP = 1 only when the target table list is correct.
--   4. Run the script once.
--
-- The script uses INFORMATION_SCHEMA, so tables that do not exist in an older
-- subsidiary schema are skipped automatically.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Execution switches
-- ---------------------------------------------------------------------------
SET @CONFIRM_CLEANUP := 0;

-- Standard cleanup clears high-confidence runtime data only.
-- Set this to 1 only when you also want to wipe business/master setup such as
-- assets, PPM master definitions, space masters, attendance groups, meters, etc.
SET @INCLUDE_BUSINESS_MASTER_DATA := 0;

-- Stock rows are separated because ast_part_sub represents current inventory
-- instances as well as checkout/return state. Enable only if stock should be
-- emptied for the subsidiary reset.
-- This is also included automatically when @INCLUDE_BUSINESS_MASTER_DATA = 1.
SET @CLEAR_INVENTORY_STOCK_ROWS := 0;

-- User accounts are preserved. This only clears user runtime files/signature
-- metadata that may point to removed upload files.
SET @CLEAR_USER_RUNTIME_DATA := 0;

-- Reset transaction running numbers on cli_site/sys_site after clearing tasks.
-- The part-sub counter is reset only when stock rows are cleared.
SET @RESET_RUNNING_NUMBERS := 1;

-- ---------------------------------------------------------------------------
-- Build cleanup table list
-- ---------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS maintenance_cleanup_tables;

CREATE TEMPORARY TABLE maintenance_cleanup_tables (
  sequence_no INT NOT NULL,
  table_name VARCHAR(64) NOT NULL,
  cleanup_scope VARCHAR(40) NOT NULL,
  cleanup_group VARCHAR(60) NOT NULL,
  notes VARCHAR(255) NULL,
  PRIMARY KEY (table_name)
) ENGINE=Memory;

INSERT INTO maintenance_cleanup_tables
  (sequence_no, table_name, cleanup_scope, cleanup_group, notes)
VALUES
  -- Work order runtime and imports
  (100, 'wo_task_upload', 'standard', 'work_order_runtime', 'WO uploaded images/signatures'),
  (110, 'wo_task_assist', 'standard', 'work_order_runtime', 'WO assistant assignments'),
  (120, 'wo_task_parts', 'standard', 'work_order_runtime', 'WO material request lines'),
  (130, 'wo_task_public', 'standard', 'work_order_runtime', 'Public complaint details'),
  (140, 'wo_task_request_2', 'standard', 'work_order_runtime', 'MRF extension/detail rows'),
  (150, 'wo_task_request', 'standard', 'work_order_runtime', 'MRF/material request headers'),
  (160, 'wo_import_log', 'standard', 'work_order_runtime', 'WO import validation/import log'),
  (170, 'wo_import_batch', 'standard', 'work_order_runtime', 'WO import batches'),
  (180, 'wo_migration', 'standard', 'work_order_runtime', 'WO migration staging/runtime rows'),
  (190, 'wo_task', 'standard', 'work_order_runtime', 'WO/WR transaction headers'),

  -- PPM task execution runtime; PPM master definitions are optional below.
  (300, 'ppm_task_upload', 'standard', 'ppm_runtime', 'PPM execution uploads'),
  (310, 'ppm_task_parts', 'standard', 'ppm_runtime', 'PPM consumed/requested parts'),
  (320, 'ppm_task_assist', 'standard', 'ppm_runtime', 'PPM assistant assignments'),
  (330, 'ppm_task_frequency', 'standard', 'ppm_runtime', 'Generated task frequencies'),
  (340, 'ppm_task_qual', 'standard', 'ppm_runtime', 'PPM qualitative execution answers'),
  (350, 'ppm_task_quan', 'standard', 'ppm_runtime', 'PPM quantitative execution answers'),
  (360, 'ppm_task_section', 'standard', 'ppm_runtime', 'PPM task section state'),
  (370, 'ppm_offline_sync_log', 'standard', 'ppm_runtime', 'Mobile offline sync idempotency/log rows'),
  (380, 'ppm_task', 'standard', 'ppm_runtime', 'Generated PPM task headers'),

  -- Workflow runtime instances; workflow definitions are preserved.
  (500, 'wfl_task_assign', 'standard', 'workflow_runtime', 'Generated workflow task assignments'),
  (510, 'wfl_task', 'standard', 'workflow_runtime', 'Generated workflow tasks'),
  (520, 'wfl_transaction', 'standard', 'workflow_runtime', 'Workflow transaction instances'),
  (530, 'wfl_user_report', 'standard', 'workflow_runtime', 'Workflow user reporting rows'),

  -- Uploads/PDFs/logs/queues. Templates and parameters are preserved.
  (700, 'email_send', 'standard', 'system_runtime', 'Email queue rows'),
  (710, 'email_log', 'standard', 'system_runtime', 'Email logs'),
  (720, 'noti_send', 'standard', 'system_runtime', 'Push notification queue rows'),
  (730, 'noti_log', 'standard', 'system_runtime', 'Push notification logs'),
  (740, 'noti_web', 'standard', 'system_runtime', 'Web notification inbox rows'),
  (750, 'sys_audit', 'standard', 'system_runtime', 'Audit trail rows'),
  (760, 'sys_pdf', 'standard', 'system_runtime', 'Generated PDF rows'),
  (770, 'sys_upload', 'standard', 'system_runtime', 'Uploaded blobs/files metadata'),

  -- Permit-to-work runtime
  (900, 'ptw_document', 'standard', 'ptw_runtime', 'PTW uploaded documents'),
  (910, 'ptw_worker', 'standard', 'ptw_runtime', 'PTW worker list rows'),
  (920, 'ptw_approval_log', 'standard', 'ptw_runtime', 'PTW approval logs'),
  (930, 'ptw_status_history', 'standard', 'ptw_runtime', 'PTW status history'),
  (940, 'ptw_number_sequence', 'standard', 'ptw_runtime', 'PTW daily number sequences'),
  (950, 'ptw_permit', 'standard', 'ptw_runtime', 'PTW permits/applications'),

  -- Procurement, delivery order, inventory movement, and return runtime
  (1100, 'inventory_logs', 'standard', 'inventory_runtime', 'Inventory movement audit logs'),
  (1110, 'material_returns', 'standard', 'inventory_runtime', 'Material return workflow rows'),
  (1120, 'do_upload', 'standard', 'inventory_runtime', 'Delivery order uploads'),
  (1130, 'do_item', 'standard', 'inventory_runtime', 'Delivery order item rows'),
  (1140, 'do', 'standard', 'inventory_runtime', 'Delivery order headers'),
  (1150, 'pr_supplier', 'standard', 'inventory_runtime', 'Purchase request supplier rows'),
  (1160, 'pr_item', 'standard', 'inventory_runtime', 'Purchase request item rows'),
  (1170, 'pr', 'standard', 'inventory_runtime', 'Purchase request headers'),
  (1180, 'ref_item_image', 'standard', 'inventory_runtime', 'Item image upload references'),

  -- Attendance, utility, KPI/GMI generated values, FCA runtime
  (1300, 'att_transaction', 'standard', 'attendance_runtime', 'Attendance punch/transaction rows'),
  (1310, 'utl_utility', 'standard', 'utility_runtime', 'Utility reading rows'),
  (1320, 'gmi_weekly', 'standard', 'reporting_runtime', 'Gamification weekly generated values'),
  (1330, 'gmi_monthly', 'standard', 'reporting_runtime', 'Gamification monthly generated values'),
  (1340, 'fca_task_section', 'standard', 'fca_runtime', 'FCA task section rows'),
  (1350, 'fca_task', 'standard', 'fca_runtime', 'FCA task rows'),
  (1360, 'fca_report', 'standard', 'fca_runtime', 'FCA report rows'),

  -- Documents/modules that depend on uploaded files
  (1500, 'lic_license', 'standard', 'document_runtime', 'License management documents'),
  (1510, 'drawing', 'standard', 'document_runtime', 'Drawing/document records'),
  (1520, 'cli_site_manual', 'standard', 'document_runtime', 'Site manual upload rows'),
  (1530, 'spc_reservation', 'standard', 'space_runtime', 'Space reservation rows'),
  (1540, 'spc_space_media', 'standard', 'space_runtime', 'Space media upload rows'),
  (1550, 'vm_visit', 'standard', 'visitor_runtime', 'Visitor check-in/check-out rows'),

  -- Migration/staging residue
  (1700, 'z_jai_batch_ppm', 'standard', 'staging_runtime', 'JAI batch staging rows'),
  (1710, 'z_migration', 'standard', 'staging_runtime', 'Migration staging rows'),
  (1720, 'z_raw', 'standard', 'staging_runtime', 'Raw staging rows'),

  -- Optional current stock rows
  (2000, 'ast_part_sub', 'inventory_stock', 'inventory_stock', 'Current stock instance rows and checkout state'),

  -- Optional business/master setup reset. Keep disabled unless the target should
  -- be reduced to only system/reference/site/user configuration.
  (3000, 'ppm_asset', 'business_master', 'ppm_master', 'PPM-to-asset mapping'),
  (3010, 'ppm_set_asset', 'business_master', 'ppm_master', 'PPM set asset mapping'),
  (3020, 'ppm', 'business_master', 'ppm_master', 'PPM master schedules'),
  (3030, 'ppm_set', 'business_master', 'ppm_master', 'PPM set definitions'),
  (3040, 'ppm_group_user', 'business_master', 'ppm_master', 'PPM group user mappings'),
  (3050, 'ppm_checklist_qual', 'business_master', 'ppm_master', 'PPM qualitative checklist master'),
  (3060, 'ppm_checklist_quan', 'business_master', 'ppm_master', 'PPM quantitative checklist master'),
  (3070, 'ppm_checklist', 'business_master', 'ppm_master', 'PPM checklist headers'),
  (3080, 'ppm_group', 'business_master', 'ppm_master', 'PPM groups'),
  (3200, 'ast_asset', 'business_master', 'asset_master', 'Asset register'),
  (3210, 'ast_part', 'business_master', 'asset_master', 'Part master/stock catalog'),
  (3300, 'att_participant', 'business_master', 'attendance_master', 'Attendance participants'),
  (3310, 'att_group', 'business_master', 'attendance_master', 'Attendance groups'),
  (3400, 'spc_space_asset', 'business_master', 'space_master', 'Space asset mappings'),
  (3410, 'spc_space', 'business_master', 'space_master', 'Space master records'),
  (3420, 'fca_zone', 'business_master', 'fca_master', 'FCA zone master records'),
  (3430, 'utl_meter', 'business_master', 'utility_master', 'Utility meter master records'),
  (3440, 'vm_host', 'business_master', 'visitor_master', 'Visitor host master records'),
  (3450, 'kpi', 'business_master', 'reporting_master', 'KPI setup/data rows'),
  (3460, 'kpi_info', 'business_master', 'reporting_master', 'KPI info setup/data rows'),
  (3470, 'kpi_ppns', 'business_master', 'reporting_master', 'KPI PPNS setup/data rows'),

  -- Optional user runtime metadata. User accounts, roles, groups, and profiles
  -- are still preserved.
  (4000, 'sys_user_signature', 'user_runtime', 'user_runtime', 'Modern user signature metadata');

-- Skip tables absent from the target subsidiary schema.
DELETE t
  FROM maintenance_cleanup_tables t
  LEFT JOIN information_schema.tables it
    ON it.table_schema = DATABASE()
   AND it.table_name = t.table_name
   AND it.table_type = 'BASE TABLE'
 WHERE it.table_name IS NULL;

-- ---------------------------------------------------------------------------
-- Helper procedures
-- ---------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS maintenance_null_column_if_exists;
DROP PROCEDURE IF EXISTS maintenance_reset_cli_site_column;
DROP PROCEDURE IF EXISTS maintenance_reset_sys_site_column;
DROP PROCEDURE IF EXISTS maintenance_run_non_config_cleanup;

DELIMITER $$

CREATE PROCEDURE maintenance_null_column_if_exists(
  IN p_table_name VARCHAR(64),
  IN p_column_name VARCHAR(64)
)
BEGIN
  IF EXISTS (
    SELECT 1
      FROM information_schema.columns
     WHERE table_schema = DATABASE()
       AND table_name = p_table_name
       AND column_name = p_column_name
  ) THEN
    SET @maintenance_sql := CONCAT(
      'UPDATE `', REPLACE(p_table_name, '`', '``'),
      '` SET `', REPLACE(p_column_name, '`', '``'), '` = NULL'
    );
    PREPARE maintenance_stmt FROM @maintenance_sql;
    EXECUTE maintenance_stmt;
    DEALLOCATE PREPARE maintenance_stmt;
  END IF;
END $$

CREATE PROCEDURE maintenance_reset_cli_site_column(IN p_column_name VARCHAR(64))
BEGIN
  IF EXISTS (
    SELECT 1
      FROM information_schema.columns
     WHERE table_schema = DATABASE()
       AND table_name = 'cli_site'
       AND column_name = p_column_name
  ) THEN
    SET @maintenance_sql := CONCAT(
      'UPDATE `cli_site` SET `', REPLACE(p_column_name, '`', '``'), '` = 1'
    );
    PREPARE maintenance_stmt FROM @maintenance_sql;
    EXECUTE maintenance_stmt;
    DEALLOCATE PREPARE maintenance_stmt;
  END IF;
END $$

CREATE PROCEDURE maintenance_reset_sys_site_column(IN p_column_name VARCHAR(64))
BEGIN
  IF EXISTS (
    SELECT 1
      FROM information_schema.columns
     WHERE table_schema = DATABASE()
       AND table_name = 'sys_site'
       AND column_name = p_column_name
  ) THEN
    SET @maintenance_sql := CONCAT(
      'UPDATE `sys_site` SET `', REPLACE(p_column_name, '`', '``'), '` = 1'
    );
    PREPARE maintenance_stmt FROM @maintenance_sql;
    EXECUTE maintenance_stmt;
    DEALLOCATE PREPARE maintenance_stmt;
  END IF;
END $$

CREATE PROCEDURE maintenance_run_non_config_cleanup()
BEGIN
  DECLARE v_done TINYINT DEFAULT 0;
  DECLARE v_table_name VARCHAR(64);

  DECLARE cleanup_cursor CURSOR FOR
    SELECT table_name
      FROM maintenance_cleanup_tables
     WHERE cleanup_scope = 'standard'
        OR (cleanup_scope = 'business_master' AND @INCLUDE_BUSINESS_MASTER_DATA = 1)
        OR (cleanup_scope = 'inventory_stock' AND (@CLEAR_INVENTORY_STOCK_ROWS = 1 OR @INCLUDE_BUSINESS_MASTER_DATA = 1))
        OR (cleanup_scope = 'user_runtime' AND @CLEAR_USER_RUNTIME_DATA = 1)
     ORDER BY sequence_no;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    IF @old_foreign_key_checks IS NOT NULL THEN
      SET FOREIGN_KEY_CHECKS = @old_foreign_key_checks;
    END IF;
    RESIGNAL;
  END;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

  IF @CONFIRM_CLEANUP <> 1 THEN
    SELECT
      'DRY RUN ONLY - set @CONFIRM_CLEANUP := 1 to execute truncation' AS cleanup_status,
      @INCLUDE_BUSINESS_MASTER_DATA AS include_business_master_data,
      @CLEAR_INVENTORY_STOCK_ROWS AS clear_inventory_stock_rows,
      @CLEAR_USER_RUNTIME_DATA AS clear_user_runtime_data,
      @RESET_RUNNING_NUMBERS AS reset_running_numbers;
  ELSE
    SET @cleanup_started_at := NOW();
    SET @old_foreign_key_checks := @@FOREIGN_KEY_CHECKS;
    SET FOREIGN_KEY_CHECKS = 0;

    -- User rows are preserved, but upload/signature pointers must not point to
    -- sys_upload rows after sys_upload has been cleared.
    CALL maintenance_null_column_if_exists('sys_user', 'upload_id');
    CALL maintenance_null_column_if_exists('sys_user', 'user_signature');

    OPEN cleanup_cursor;

    cleanup_loop: LOOP
      FETCH cleanup_cursor INTO v_table_name;
      IF v_done = 1 THEN
        LEAVE cleanup_loop;
      END IF;

      SET @maintenance_sql := CONCAT('TRUNCATE TABLE `', REPLACE(v_table_name, '`', '``'), '`');
      PREPARE maintenance_stmt FROM @maintenance_sql;
      EXECUTE maintenance_stmt;
      DEALLOCATE PREPARE maintenance_stmt;
    END LOOP;

    CLOSE cleanup_cursor;

    IF @RESET_RUNNING_NUMBERS = 1 THEN
      CALL maintenance_reset_cli_site_column('site_running_no');
      CALL maintenance_reset_cli_site_column('site_running_no_wo');
      CALL maintenance_reset_cli_site_column('site_running_no_wr');
      CALL maintenance_reset_cli_site_column('site_running_no_req');
      CALL maintenance_reset_cli_site_column('site_running_no_fca');
      IF @CLEAR_INVENTORY_STOCK_ROWS = 1 OR @INCLUDE_BUSINESS_MASTER_DATA = 1 THEN
        CALL maintenance_reset_cli_site_column('site_running_no_part_sub');
      END IF;
      CALL maintenance_reset_sys_site_column('site_running_no');
    END IF;

    SET FOREIGN_KEY_CHECKS = @old_foreign_key_checks;

    SELECT
      'EXECUTED' AS cleanup_status,
      @cleanup_started_at AS started_at,
      NOW() AS finished_at,
      COUNT(*) AS tables_cleared
      FROM maintenance_cleanup_tables
     WHERE cleanup_scope = 'standard'
        OR (cleanup_scope = 'business_master' AND @INCLUDE_BUSINESS_MASTER_DATA = 1)
        OR (cleanup_scope = 'inventory_stock' AND (@CLEAR_INVENTORY_STOCK_ROWS = 1 OR @INCLUDE_BUSINESS_MASTER_DATA = 1))
        OR (cleanup_scope = 'user_runtime' AND @CLEAR_USER_RUNTIME_DATA = 1);
  END IF;
END $$

DELIMITER ;

-- ---------------------------------------------------------------------------
-- Preview target tables. TABLE_ROWS is approximate for InnoDB but fast.
-- ---------------------------------------------------------------------------
SELECT
  t.cleanup_scope,
  t.cleanup_group,
  t.table_name,
  COALESCE(it.table_rows, 0) AS approximate_rows,
  t.notes
FROM maintenance_cleanup_tables t
JOIN information_schema.tables it
  ON it.table_schema = DATABASE()
 AND it.table_name = t.table_name
WHERE t.cleanup_scope = 'standard'
   OR (t.cleanup_scope = 'business_master' AND @INCLUDE_BUSINESS_MASTER_DATA = 1)
  OR (t.cleanup_scope = 'inventory_stock' AND (@CLEAR_INVENTORY_STOCK_ROWS = 1 OR @INCLUDE_BUSINESS_MASTER_DATA = 1))
   OR (t.cleanup_scope = 'user_runtime' AND @CLEAR_USER_RUNTIME_DATA = 1)
ORDER BY t.sequence_no;

CALL maintenance_run_non_config_cleanup();

-- ---------------------------------------------------------------------------
-- Cleanup helper objects from the session/schema.
-- ---------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS maintenance_run_non_config_cleanup;
DROP PROCEDURE IF EXISTS maintenance_reset_sys_site_column;
DROP PROCEDURE IF EXISTS maintenance_reset_cli_site_column;
DROP PROCEDURE IF EXISTS maintenance_null_column_if_exists;
DROP TEMPORARY TABLE IF EXISTS maintenance_cleanup_tables;

-- ---------------------------------------------------------------------------
-- Preserved configuration/master tables by default include, but are not limited
-- to:
--   ref_*, sys_nav, sys_nav_second, sys_nav_role, sys_group, sys_user,
--   sys_user_profile, sys_user_group, sys_user_role, sys_audit_action,
--   sys_audit_module, sys_version, cli_client, cli_site, cli_contract,
--   cli_contract_user, cli_location_code, cli_store, cli_zone,
--   cli_site_problem_type, email_template, email_parameter, noti_text,
--   noti_parameter, wfl_flow, wfl_checkpoint, wfl_checkpoint_assign,
--   wfl_checkpoint_user, ast_asset_group, ast_asset_category, ast_asset_type,
--   ast_asset_brand, ast_asset_model, att_type, ppm_frequency, gmi_config.
-- ---------------------------------------------------------------------------
