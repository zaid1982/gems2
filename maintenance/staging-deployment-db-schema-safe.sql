-- ===================================================================
-- Production Schema Sync Script (SAFE VERSION)
-- Ensures production schema matches development schema
-- Uses proper conditional checks compatible with all MariaDB versions
-- ===================================================================

SET SESSION sql_mode = REPLACE(@@SESSION.sql_mode, 'ONLY_FULL_GROUP_BY', '');
START TRANSACTION;

-- ============================================================================
-- 1) CREATE tables that exist in dev but are missing in prod
-- ============================================================================

CREATE TABLE IF NOT EXISTS `cli_zone` (
  `zone_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `site_id` smallint(6) NOT NULL,
  `zone_code` varchar(20) NOT NULL,
  `zone_type` varchar(100) DEFAULT NULL,
  `zone_name` varchar(200) NOT NULL,
  `zone_status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`zone_id`),
  KEY `site_id` (`site_id`),
  CONSTRAINT `cli_zone_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gmi_config` (
  `config_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(50) NOT NULL,
  `config_value` varchar(255) NOT NULL,
  `config_group` varchar(50) DEFAULT NULL,
  `config_desc` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`config_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `gmi_weekly` (
  `gmi_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `site_id` smallint(6) DEFAULT NULL,
  `week_start` date DEFAULT NULL,
  `week_end` date DEFAULT NULL,
  `gmi_ppm_total` smallint(6) DEFAULT NULL,
  `gmi_ppm_completed` smallint(6) DEFAULT NULL,
  `gmi_wo_total` smallint(6) DEFAULT NULL,
  `gmi_wo_completed` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`gmi_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `gmi_weekly_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `noti_web` (
  `noti_web_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `noti_web_type` tinyint(4) NOT NULL,
  `user_id` int(11) NOT NULL,
  `noti_web_title` varchar(30) NOT NULL,
  `noti_web_text` varchar(255) NOT NULL,
  `noti_web_icon` varchar(30) NOT NULL,
  `noti_web_color` varchar(30) NOT NULL,
  `noti_web_link` varchar(100) DEFAULT NULL,
  `nav_id` tinyint(4) DEFAULT NULL,
  `nav_second_id` tinyint(4) DEFAULT NULL,
  `noti_web_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`noti_web_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ppm_set` (
  `ppm_set_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `asset_type_id` smallint(6) NOT NULL,
  `ppm_group_id` smallint(6) NOT NULL,
  `ppm_set_name` varchar(200) DEFAULT NULL,
  `ppm_set_desc` varchar(1000) DEFAULT NULL,
  `asset_group_id` smallint(6) NOT NULL,
  `asset_category_id` smallint(6) NOT NULL,
  `ppm_set_created_by` int(11) DEFAULT NULL,
  `ppm_set_time_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ppm_set_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ppm_set_id`),
  KEY `asset_type_id` (`asset_type_id`),
  KEY `ppm_group_id` (`ppm_group_id`),
  CONSTRAINT `ppm_set_ibfk_1` FOREIGN KEY (`asset_type_id`) REFERENCES `ast_asset_type` (`asset_type_id`),
  CONSTRAINT `ppm_set_ibfk_2` FOREIGN KEY (`ppm_group_id`) REFERENCES `ppm_group` (`ppm_group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ppm_set_asset` (
  `ppm_set_asset_id` int(11) NOT NULL AUTO_INCREMENT,
  `ppm_set_id` smallint(6) NOT NULL,
  `asset_id` bigint(20) NOT NULL,
  PRIMARY KEY (`ppm_set_asset_id`),
  KEY `ppm_set_id` (`ppm_set_id`),
  KEY `asset_id` (`asset_id`),
  CONSTRAINT `ppm_set_asset_ibfk_1` FOREIGN KEY (`ppm_set_id`) REFERENCES `ppm_set` (`ppm_set_id`),
  CONSTRAINT `ppm_set_asset_ibfk_2` FOREIGN KEY (`asset_id`) REFERENCES `ast_asset` (`asset_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `ppm_asset` (
  `ppm_asset_id` int(11) NOT NULL AUTO_INCREMENT,
  `ppm_id` bigint(20) NOT NULL,
  `asset_id` bigint(20) NOT NULL,
  PRIMARY KEY (`ppm_asset_id`),
  UNIQUE KEY `ppm_id` (`ppm_id`,`asset_id`),
  KEY `asset_id` (`asset_id`),
  CONSTRAINT `ppm_asset_ibfk_1` FOREIGN KEY (`ppm_id`) REFERENCES `ppm` (`ppm_id`),
  CONSTRAINT `ppm_asset_ibfk_2` FOREIGN KEY (`asset_id`) REFERENCES `ast_asset` (`asset_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `wo_import_batch` (
  `batch_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `batch_name` varchar(100) NOT NULL,
  `batch_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`batch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `wo_import_log` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `batch_id` bigint(20) NOT NULL,
  `record_data` text NOT NULL,
  `log_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  CONSTRAINT `wo_import_log_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `wo_import_batch` (`batch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `wo_task_public` (
  `public_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `wo_task_id` bigint(20) NOT NULL,
  `public_note` text NOT NULL,
  `public_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`public_id`),
  CONSTRAINT `wo_task_public_ibfk_1` FOREIGN KEY (`wo_task_id`) REFERENCES `wo_task` (`wo_task_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `wo_task_request_2` (
  `request2_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `wo_task_request_id` bigint(20) DEFAULT NULL,
  `additional_info` text DEFAULT NULL,
  `request2_time_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`request2_id`),
  CONSTRAINT `wo_task_request_2_ibfk_1` FOREIGN KEY (`wo_task_request_id`) REFERENCES `wo_task_request` (`wo_task_request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- ============================================================================
-- 2) ADD missing columns to existing tables using procedures
-- ============================================================================

-- Add columns to cli_location_code
DELIMITER $$
DROP PROCEDURE IF EXISTS `add_cli_location_code_columns` $$
CREATE PROCEDURE `add_cli_location_code_columns`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'cli_location_code'
    AND column_name = 'location_code_type'
  ) THEN
    ALTER TABLE `cli_location_code`
    ADD COLUMN `location_code_type` varchar(100) DEFAULT NULL AFTER `location_code_status`;
  END IF;
END $$
DELIMITER ;

CALL `add_cli_location_code_columns`();
DROP PROCEDURE IF EXISTS `add_cli_location_code_columns`;

-- Add columns to cli_site
DELIMITER $$
DROP PROCEDURE IF EXISTS `add_cli_site_columns` $$
CREATE PROCEDURE `add_cli_site_columns`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'cli_site'
    AND column_name = 'site_is_public'
  ) THEN
    ALTER TABLE `cli_site`
    ADD COLUMN `site_is_public` tinyint(1) NOT NULL DEFAULT 0 AFTER `site_status`;
  END IF;
END $$
DELIMITER ;

CALL `add_cli_site_columns`();
DROP PROCEDURE IF EXISTS `add_cli_site_columns`;

-- Add columns to ast_asset
DELIMITER $$
DROP PROCEDURE IF EXISTS `add_ast_asset_columns` $$
CREATE PROCEDURE `add_ast_asset_columns`()
BEGIN
  -- Add zone_id column
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ast_asset'
    AND column_name = 'zone_id'
  ) THEN
    ALTER TABLE `ast_asset`
    ADD COLUMN `zone_id` smallint(6) DEFAULT NULL AFTER `checklist_id`;
  END IF;
  
  -- Add asset_lifespan_year column
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ast_asset'
    AND column_name = 'asset_lifespan_year'
  ) THEN
    ALTER TABLE `ast_asset`
    ADD COLUMN `asset_lifespan_year` tinyint(4) DEFAULT NULL AFTER `zone_id`;
  END IF;
  
  -- Add asset_lifespan_start_date column
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ast_asset'
    AND column_name = 'asset_lifespan_start_date'
  ) THEN
    ALTER TABLE `ast_asset`
    ADD COLUMN `asset_lifespan_start_date` date DEFAULT NULL AFTER `asset_lifespan_year`;
  END IF;
  
  -- Add remaining columns one by one to avoid issues
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ast_asset'
    AND column_name = 'asset_lifespan_alert'
  ) THEN
    ALTER TABLE `ast_asset`
    ADD COLUMN `asset_lifespan_alert` tinyint(4) DEFAULT NULL AFTER `asset_lifespan_start_date`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ast_asset'
    AND column_name = 'asset_value_depreciation'
  ) THEN
    ALTER TABLE `ast_asset`
    ADD COLUMN `asset_value_depreciation` tinyint(4) DEFAULT NULL AFTER `asset_lifespan_alert`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ast_asset'
    AND column_name = 'asset_value_alert'
  ) THEN
    ALTER TABLE `ast_asset`
    ADD COLUMN `asset_value_alert` decimal(10,2) DEFAULT NULL AFTER `asset_value_depreciation`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ast_asset'
    AND column_name = 'asset_repair_alert'
  ) THEN
    ALTER TABLE `ast_asset`
    ADD COLUMN `asset_repair_alert` decimal(10,2) DEFAULT NULL AFTER `asset_value_alert`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ast_asset'
    AND column_name = 'asset_running_hours'
  ) THEN
    ALTER TABLE `ast_asset`
    ADD COLUMN `asset_running_hours` smallint(6) DEFAULT NULL AFTER `asset_repair_alert`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ast_asset'
    AND column_name = 'asset_disposal_status'
  ) THEN
    ALTER TABLE `ast_asset`
    ADD COLUMN `asset_disposal_status` tinyint(1) DEFAULT NULL AFTER `asset_running_hours`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ast_asset'
    AND column_name = 'asset_disposal_date'
  ) THEN
    ALTER TABLE `ast_asset`
    ADD COLUMN `asset_disposal_date` date DEFAULT NULL AFTER `asset_disposal_status`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ast_asset'
    AND column_name = 'asset_disposal_item_cost'
  ) THEN
    ALTER TABLE `ast_asset`
    ADD COLUMN `asset_disposal_item_cost` decimal(10,2) DEFAULT NULL AFTER `asset_disposal_date`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ast_asset'
    AND column_name = 'asset_disposal_service_cost'
  ) THEN
    ALTER TABLE `ast_asset`
    ADD COLUMN `asset_disposal_service_cost` decimal(10,2) DEFAULT NULL AFTER `asset_disposal_item_cost`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ast_asset'
    AND column_name = 'asset_mtbf_alert'
  ) THEN
    ALTER TABLE `ast_asset`
    ADD COLUMN `asset_mtbf_alert` smallint(6) DEFAULT NULL AFTER `asset_disposal_service_cost`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ast_asset'
    AND column_name = 'asset_mttr_alert'
  ) THEN
    ALTER TABLE `ast_asset`
    ADD COLUMN `asset_mttr_alert` smallint(6) DEFAULT NULL AFTER `asset_mtbf_alert`;
  END IF;
END $$
DELIMITER ;

CALL `add_ast_asset_columns`();
DROP PROCEDURE IF EXISTS `add_ast_asset_columns`;

-- Add columns to ppm table
DELIMITER $$
DROP PROCEDURE IF EXISTS `add_ppm_columns` $$
CREATE PROCEDURE `add_ppm_columns`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ppm'
    AND column_name = 'ppm_name'
  ) THEN
    ALTER TABLE `ppm`
    ADD COLUMN `ppm_name` varchar(200) DEFAULT NULL AFTER `ppm_status`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ppm'
    AND column_name = 'ppm_set_id'
  ) THEN
    ALTER TABLE `ppm`
    ADD COLUMN `ppm_set_id` smallint(6) DEFAULT NULL AFTER `ppm_name`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ppm'
    AND column_name = 'ppm_is_group'
  ) THEN
    ALTER TABLE `ppm`
    ADD COLUMN `ppm_is_group` tinyint(1) DEFAULT NULL AFTER `ppm_set_id`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ppm'
    AND column_name = 'asset_type_id'
  ) THEN
    ALTER TABLE `ppm`
    ADD COLUMN `asset_type_id` smallint(6) DEFAULT NULL AFTER `ppm_is_group`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ppm'
    AND column_name = 'ppm_frequency'
  ) THEN
    ALTER TABLE `ppm`
    ADD COLUMN `ppm_frequency` varchar(100) DEFAULT NULL AFTER `asset_type_id`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ppm'
    AND column_name = 'ppm_remark'
  ) THEN
    ALTER TABLE `ppm`
    ADD COLUMN `ppm_remark` text DEFAULT NULL AFTER `ppm_frequency`;
  END IF;
END $$
DELIMITER ;

CALL `add_ppm_columns`();
DROP PROCEDURE IF EXISTS `add_ppm_columns`;

-- Add columns to ppm_task table
DELIMITER $$
DROP PROCEDURE IF EXISTS `add_ppm_task_columns` $$
CREATE PROCEDURE `add_ppm_task_columns`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'ppm_task'
    AND column_name = 'ppm_task_is_group_executed'
  ) THEN
    ALTER TABLE `ppm_task`
    ADD COLUMN `ppm_task_is_group_executed` tinyint(1) NOT NULL DEFAULT 0 AFTER `ppm_task_status`;
  END IF;
END $$
DELIMITER ;

CALL `add_ppm_task_columns`();
DROP PROCEDURE IF EXISTS `add_ppm_task_columns`;

-- Add columns to wo_task table
DELIMITER $$
DROP PROCEDURE IF EXISTS `add_wo_task_columns` $$
CREATE PROCEDURE `add_wo_task_columns`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'wo_task'
    AND column_name = 'wo_task_external_ref'
  ) THEN
    ALTER TABLE `wo_task`
    ADD COLUMN `wo_task_external_ref` varchar(100) DEFAULT NULL COMMENT 'External system work order reference' AFTER `wo_task_status`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'wo_task'
    AND column_name = 'wo_task_is_imported'
  ) THEN
    ALTER TABLE `wo_task`
    ADD COLUMN `wo_task_is_imported` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Imported flag' AFTER `wo_task_external_ref`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'wo_task'
    AND column_name = 'wo_task_is_public'
  ) THEN
    ALTER TABLE `wo_task`
    ADD COLUMN `wo_task_is_public` tinyint(1) NOT NULL DEFAULT 0 AFTER `wo_task_is_imported`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'wo_task'
    AND column_name = 'zone_id'
  ) THEN
    ALTER TABLE `wo_task`
    ADD COLUMN `zone_id` smallint(6) DEFAULT NULL AFTER `wo_task_is_public`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'wo_task'
    AND column_name = 'wo_task_is_pdf_wr'
  ) THEN
    ALTER TABLE `wo_task`
    ADD COLUMN `wo_task_is_pdf_wr` tinyint(1) NOT NULL DEFAULT 0 AFTER `zone_id`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'wo_task'
    AND column_name = 'wo_task_is_pdf'
  ) THEN
    ALTER TABLE `wo_task`
    ADD COLUMN `wo_task_is_pdf` tinyint(1) NOT NULL DEFAULT 0 AFTER `wo_task_is_pdf_wr`;
  END IF;
END $$
DELIMITER ;

CALL `add_wo_task_columns`();
DROP PROCEDURE IF EXISTS `add_wo_task_columns`;

-- Add columns to wo_task_request table
DELIMITER $$
DROP PROCEDURE IF EXISTS `add_wo_task_request_columns` $$
CREATE PROCEDURE `add_wo_task_request_columns`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'wo_task_request'
    AND column_name = 'wo_task_no'
  ) THEN
    ALTER TABLE `wo_task_request`
    ADD COLUMN `wo_task_no` varchar(30) DEFAULT NULL AFTER `wo_task_request_status`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'wo_task_request'
    AND column_name = 'store_id'
  ) THEN
    ALTER TABLE `wo_task_request`
    ADD COLUMN `store_id` smallint(6) DEFAULT NULL AFTER `wo_task_no`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'wo_task_request'
    AND column_name = 'wo_task_request_severity'
  ) THEN
    ALTER TABLE `wo_task_request`
    ADD COLUMN `wo_task_request_severity` tinyint(1) DEFAULT NULL AFTER `store_id`;
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'wo_task_request'
    AND column_name = 'wo_task_request_is_standalone'
  ) THEN
    ALTER TABLE `wo_task_request`
    ADD COLUMN `wo_task_request_is_standalone` tinyint(1) NOT NULL DEFAULT 0 AFTER `wo_task_request_severity`;
  END IF;
END $$
DELIMITER ;

CALL `add_wo_task_request_columns`();
DROP PROCEDURE IF EXISTS `add_wo_task_request_columns`;

-- ============================================================================
-- 3) ADD INDEX on sys_user if it doesn't exist
-- ============================================================================

DELIMITER $$
DROP PROCEDURE IF EXISTS `add_idx_sys_user` $$
CREATE PROCEDURE `add_idx_sys_user`()
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name   = 'sys_user'
    AND index_name   = 'idx_user_name'
  ) THEN
    ALTER TABLE `sys_user`
    ADD INDEX `idx_user_name` (`user_name`);
  END IF;
END $$
DELIMITER ;

CALL `add_idx_sys_user`();
DROP PROCEDURE IF EXISTS `add_idx_sys_user`;

COMMIT;

-- ============================================================================
-- Schema sync completed successfully
-- ============================================================================
