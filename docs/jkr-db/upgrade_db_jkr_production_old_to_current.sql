-- ============================================================================
-- GEMS JKR schema upgraded from old production structure
-- Generated: 2026-05-22
-- Old source: docs/jkr-db/db-jkr-production-old.sql
-- Current source: docs/jkr-db/gems_jkr_staging_prod.sql
-- Schema only: no data rows are inserted.
-- ============================================================================

-- Data-preserving upgrade script for databases imported from the old schema.
-- This script creates missing current tables/views, adds missing current columns,
-- and aligns changed column definitions. It intentionally does not drop old tables
-- or old columns; review the report before deciding on destructive cleanup.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1) Tables missing from the old schema
-- New table: cli_zone
CREATE TABLE IF NOT EXISTS `cli_zone`  (
  `zone_id` smallint NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `zone_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `zone_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `zone_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `zone_status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`zone_id`) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  CONSTRAINT `cli_zone_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- New table: gmi_config
CREATE TABLE IF NOT EXISTS `gmi_config`  (
  `config_id` int NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `config_value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `data_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `last_updated_by` int NULL DEFAULT NULL,
  `last_updated_at` timestamp NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`config_id`) USING BTREE,
  UNIQUE INDEX `config_key`(`config_key` ASC) USING BTREE,
  UNIQUE INDEX `idx_config_key_unique`(`config_key` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- New table: gmi_weekly
CREATE TABLE IF NOT EXISTS `gmi_weekly`  (
  `gmw_id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `site_id` smallint NULL DEFAULT NULL,
  `gmw_year` smallint NULL DEFAULT NULL,
  `gmw_week` tinyint NULL DEFAULT NULL,
  `gmw_ppm_tier_name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `gmw_ppm_tier_point` decimal(3, 1) NULL DEFAULT NULL,
  `gmw_ppm_total` smallint NULL DEFAULT NULL,
  `gmw_ppm_completed` smallint NULL DEFAULT NULL,
  `gmw_ppm_on_time` smallint NULL DEFAULT NULL,
  `gmw_ppm_late` smallint NULL DEFAULT NULL,
  `gmw_ppm_within` smallint NULL DEFAULT NULL,
  `gmw_ppm_assist` smallint NULL DEFAULT NULL,
  `gmw_wo_tier_name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `gmw_wo_tier_point` decimal(3, 1) NULL DEFAULT NULL,
  `gmw_wo_total` smallint NULL DEFAULT NULL,
  `gmw_wo_completed` smallint NULL DEFAULT NULL,
  `gmw_wo_on_time` smallint NULL DEFAULT NULL,
  `gmw_wo_late` smallint NULL DEFAULT NULL,
  `gmw_wo_rework` smallint NULL DEFAULT NULL,
  `gmw_wo_self_finding` smallint NULL DEFAULT NULL,
  `gmw_wo_assist` smallint NULL DEFAULT NULL,
  `gmw_mbv` smallint NULL DEFAULT NULL,
  `gmw_tier_point` tinyint(1) NULL DEFAULT NULL,
  `gmw_point_completed` int NULL DEFAULT NULL,
  `gmw_point_on_time` int NULL DEFAULT NULL,
  `gmw_point_late` int NULL DEFAULT NULL,
  `gmw_point_rework` int NULL DEFAULT NULL,
  `gmw_point_self_finding` int NULL DEFAULT NULL,
  `gmw_point_total` int NULL DEFAULT NULL,
  `gmw_productivity_level` decimal(6, 2) NULL DEFAULT NULL,
  `gmw_productivity_deduction` decimal(6, 2) NULL DEFAULT NULL,
  `gmw_point_less_productive` int NULL DEFAULT NULL,
  `gmw_point_before_minus` int NULL DEFAULT NULL,
  `gmw_point_after_minus` int NULL DEFAULT NULL,
  PRIMARY KEY (`gmw_id`) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- New table: inventory_logs
CREATE TABLE IF NOT EXISTS `inventory_logs`  (
  `log_id` bigint NOT NULL AUTO_INCREMENT,
  `part_id` bigint NOT NULL,
  `change_type` enum('purchase','checkout','return','adjustment','transfer') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `quantity_change` int NOT NULL COMMENT 'Positive for add, negative for remove',
  `quantity_before` int NOT NULL,
  `quantity_after` int NOT NULL,
  `user_id` int NOT NULL,
  `reference_id` bigint NULL DEFAULT NULL COMMENT 'ID of related record (return_id, wo_task_parts_id, etc)',
  `reference_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Type of reference (material_return, wo_checkout, etc)',
  `change_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `change_date` datetime NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`log_id`) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  INDEX `idx_part_id`(`part_id` ASC) USING BTREE,
  INDEX `idx_change_date`(`change_date` ASC) USING BTREE,
  INDEX `idx_change_type`(`change_type` ASC) USING BTREE,
  INDEX `idx_reference`(`reference_type` ASC, `reference_id` ASC) USING BTREE,
  CONSTRAINT `inventory_logs_ibfk_1` FOREIGN KEY (`part_id`) REFERENCES `ast_part` (`part_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `inventory_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Audit trail for all inventory changes' ROW_FORMAT = Dynamic;

-- New table: lic_license
CREATE TABLE IF NOT EXISTS `lic_license`  (
  `license_id` int NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `license_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `license_start_date` date NOT NULL,
  `license_end_date` date NOT NULL,
  `upload_id` bigint NULL DEFAULT NULL,
  `warning_days` int NULL DEFAULT NULL,
  `warning_date` date NULL DEFAULT NULL,
  `license_status` tinyint(1) NOT NULL DEFAULT 1,
  `license_created_by` int NULL DEFAULT NULL,
  `license_updated_by` int NULL DEFAULT NULL,
  `license_created_date` datetime NOT NULL DEFAULT current_timestamp,
  `license_updated_date` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`license_id`) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  INDEX `upload_id`(`upload_id` ASC) USING BTREE,
  CONSTRAINT `lic_license_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `lic_license_ibfk_2` FOREIGN KEY (`upload_id`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- New table: ppm_asset
CREATE TABLE IF NOT EXISTS `ppm_asset`  (
  `ppm_asset_id` int NOT NULL AUTO_INCREMENT,
  `ppm_id` bigint NOT NULL,
  `asset_id` bigint NOT NULL,
  PRIMARY KEY (`ppm_asset_id`) USING BTREE,
  UNIQUE INDEX `ppm_id`(`ppm_id` ASC, `asset_id` ASC) USING BTREE,
  INDEX `asset_id`(`asset_id` ASC) USING BTREE,
  CONSTRAINT `ppm_asset_ibfk_1` FOREIGN KEY (`ppm_id`) REFERENCES `ppm` (`ppm_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_asset_ibfk_2` FOREIGN KEY (`asset_id`) REFERENCES `ast_asset` (`asset_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- New table: ppm_set
CREATE TABLE IF NOT EXISTS `ppm_set`  (
  `ppm_set_id` smallint NOT NULL AUTO_INCREMENT,
  `asset_type_id` smallint NOT NULL,
  `ppm_group_id` smallint NOT NULL,
  `ppm_set_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `ppm_set_desc` varchar(1000) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `asset_group_id` smallint NOT NULL,
  `asset_category_id` smallint NOT NULL,
  `ppm_set_created_by` int NULL DEFAULT NULL,
  `ppm_set_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `ppm_set_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`ppm_set_id`) USING BTREE,
  INDEX `asset_type_id`(`asset_type_id` ASC) USING BTREE,
  INDEX `ppm_group_id`(`ppm_group_id` ASC) USING BTREE,
  CONSTRAINT `ppm_set_ibfk_1` FOREIGN KEY (`asset_type_id`) REFERENCES `ast_asset_type` (`asset_type_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_set_ibfk_2` FOREIGN KEY (`ppm_group_id`) REFERENCES `ppm_group` (`ppm_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- New table: ppm_set_asset
CREATE TABLE IF NOT EXISTS `ppm_set_asset`  (
  `ppm_set_asset_id` int NOT NULL AUTO_INCREMENT,
  `ppm_set_id` smallint NOT NULL,
  `asset_id` bigint NOT NULL,
  `ppm_set_asset_created_by` int NULL DEFAULT NULL,
  PRIMARY KEY (`ppm_set_asset_id`) USING BTREE,
  INDEX `ppm_set_id`(`ppm_set_id` ASC) USING BTREE,
  INDEX `asset_id`(`asset_id` ASC) USING BTREE,
  CONSTRAINT `ppm_set_asset_ibfk_1` FOREIGN KEY (`ppm_set_id`) REFERENCES `ppm_set` (`ppm_set_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_set_asset_ibfk_2` FOREIGN KEY (`asset_id`) REFERENCES `ast_asset` (`asset_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- New table: ptw_approval_log
CREATE TABLE IF NOT EXISTS `ptw_approval_log`  (
  `ptw_approval_log_id` int NOT NULL AUTO_INCREMENT,
  `ptw_permit_id` int NOT NULL,
  `approval_type` enum('SUPERVISOR','SHE','FM','ACTIVATION','COMPLETION','CANCELLATION') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `previous_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `new_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `approved_by` int NOT NULL,
  `approved_date` timestamp NOT NULL DEFAULT current_timestamp,
  `signature_path_snapshot` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `signature_sha256_snapshot` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`ptw_approval_log_id`) USING BTREE,
  INDEX `idx_ptw_approval_permit_id`(`ptw_permit_id` ASC) USING BTREE,
  CONSTRAINT `fk_ptw_approval_permit` FOREIGN KEY (`ptw_permit_id`) REFERENCES `ptw_permit` (`ptw_permit_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- New table: ptw_document
CREATE TABLE IF NOT EXISTS `ptw_document`  (
  `ptw_document_id` int NOT NULL AUTO_INCREMENT,
  `ptw_permit_id` int NOT NULL,
  `document_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_category` enum('SUPPORTING_DOC','CERTIFICATE','PERMIT','OTHER') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'SUPPORTING_DOC',
  `is_required` tinyint(1) NULL DEFAULT 0 COMMENT 'Whether this document is mandatory',
  `document_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_size` int NULL DEFAULT NULL,
  `document_mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `uploaded_by` int NOT NULL,
  `uploaded_date` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`ptw_document_id`) USING BTREE,
  INDEX `idx_ptw_document_permit_id`(`ptw_permit_id` ASC) USING BTREE,
  INDEX `idx_ptw_document_category`(`document_category` ASC) USING BTREE,
  CONSTRAINT `fk_ptw_document_permit` FOREIGN KEY (`ptw_permit_id`) REFERENCES `ptw_permit` (`ptw_permit_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- New table: ptw_number_sequence
CREATE TABLE IF NOT EXISTS `ptw_number_sequence`  (
  `site_id` int NOT NULL,
  `seq_date` date NOT NULL,
  `seq_type` enum('REQUEST','PERMIT') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `next_value` int NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`site_id`, `seq_date`, `seq_type`) USING BTREE,
  INDEX `idx_seq_updated`(`updated_at` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- New table: ptw_status_history
CREATE TABLE IF NOT EXISTS `ptw_status_history`  (
  `ptw_status_history_id` int NOT NULL AUTO_INCREMENT,
  `ptw_permit_id` int NOT NULL,
  `action_type` enum('DRAFT','SUBMITTED','PENDING_SUPERVISOR','PENDING_SHE','PENDING_FM','APPROVED','ACTIVE','PENDING_CLOSURE','COMPLETED','PENDING_CANCELLATION','PENDING_SUSPENSION','PENDING_EXTENSION','CANCELLED','SUSPENDED','REJECTED','EXTENDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `previous_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `new_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `action_by` int NOT NULL,
  `action_date` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`ptw_status_history_id`) USING BTREE,
  INDEX `idx_ptw_status_permit_id`(`ptw_permit_id` ASC) USING BTREE,
  INDEX `idx_ptw_status_action_type`(`action_type` ASC) USING BTREE,
  INDEX `idx_ptw_status_action_date`(`action_date` ASC) USING BTREE,
  CONSTRAINT `fk_ptw_status_permit` FOREIGN KEY (`ptw_permit_id`) REFERENCES `ptw_permit` (`ptw_permit_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- New table: ptw_worker
CREATE TABLE IF NOT EXISTS `ptw_worker`  (
  `ptw_worker_id` int NOT NULL AUTO_INCREMENT,
  `ptw_permit_id` int NOT NULL,
  `worker_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `worker_ic_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `worker_phone_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `worker_company` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `worker_designation` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `worker_role` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `worker_identification` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `is_certified` tinyint(1) NULL DEFAULT 0,
  `worker_ptw_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`ptw_worker_id`) USING BTREE,
  INDEX `idx_ptw_worker_permit_id`(`ptw_permit_id` ASC) USING BTREE,
  CONSTRAINT `fk_ptw_worker_permit` FOREIGN KEY (`ptw_permit_id`) REFERENCES `ptw_permit` (`ptw_permit_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- New table: ref_space_category
CREATE TABLE IF NOT EXISTS `ref_space_category`  (
  `space_category_id` smallint UNSIGNED NOT NULL AUTO_INCREMENT,
  `space_category_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `space_category_desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `space_category_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp,
  `created_by` int NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  `updated_by` int NULL DEFAULT NULL,
  PRIMARY KEY (`space_category_id`) USING BTREE,
  UNIQUE INDEX `uq_ref_space_category_name`(`space_category_name` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- New table: ref_space_location
CREATE TABLE IF NOT EXISTS `ref_space_location`  (
  `space_location_id` smallint UNSIGNED NOT NULL AUTO_INCREMENT,
  `space_location_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `space_location_desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `space_location_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp,
  `created_by` int NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  `updated_by` int NULL DEFAULT NULL,
  PRIMARY KEY (`space_location_id`) USING BTREE,
  UNIQUE INDEX `uq_ref_space_location_name`(`space_location_name` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- New table: ref_space_status
CREATE TABLE IF NOT EXISTS `ref_space_status`  (
  `space_status_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `space_status_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `space_status_order` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `space_status_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`space_status_code`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- New table: ref_space_type
CREATE TABLE IF NOT EXISTS `ref_space_type`  (
  `space_type_id` smallint UNSIGNED NOT NULL AUTO_INCREMENT,
  `space_type_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `space_type_desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `space_type_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp,
  `created_by` int NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  `updated_by` int NULL DEFAULT NULL,
  `space_category_id` smallint UNSIGNED NOT NULL,
  PRIMARY KEY (`space_type_id`) USING BTREE,
  UNIQUE INDEX `uq_ref_space_type_name`(`space_type_name` ASC) USING BTREE,
  INDEX `idx_ref_space_type_category`(`space_category_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- New table: spc_reservation
CREATE TABLE IF NOT EXISTS `spc_reservation`  (
  `reservation_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `space_id` int UNSIGNED NOT NULL,
  `site_id` smallint UNSIGNED NOT NULL,
  `reservation_start` datetime NOT NULL,
  `reservation_end` datetime NOT NULL,
  `reservation_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'RESERVED',
  `special_request` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `requested_by` int NULL DEFAULT NULL,
  `requested_by_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `requested_by_contact` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `auto_approved_at` datetime NULL DEFAULT NULL,
  `canceled_by` int NULL DEFAULT NULL,
  `canceled_at` datetime NULL DEFAULT NULL,
  `cancel_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp,
  `updated_at` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reservation_id`) USING BTREE,
  INDEX `idx_spc_reservation_space`(`space_id` ASC) USING BTREE,
  INDEX `idx_spc_reservation_site`(`site_id` ASC) USING BTREE,
  INDEX `idx_spc_reservation_status`(`reservation_status` ASC) USING BTREE,
  CONSTRAINT `fk_spc_reservation_space` FOREIGN KEY (`space_id`) REFERENCES `spc_space` (`space_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- New table: spc_space
CREATE TABLE IF NOT EXISTS `spc_space`  (
  `space_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` smallint UNSIGNED NOT NULL,
  `space_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `space_location_id` smallint UNSIGNED NULL DEFAULT NULL,
  `space_category_id` smallint UNSIGNED NULL DEFAULT NULL,
  `space_type_id` smallint UNSIGNED NULL DEFAULT NULL,
  `space_area` decimal(10, 2) NULL DEFAULT NULL,
  `space_capacity` smallint UNSIGNED NULL DEFAULT NULL,
  `space_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'AVAILABLE',
  `space_desc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `space_created_by` int NULL DEFAULT NULL,
  `space_created_at` datetime NOT NULL DEFAULT current_timestamp,
  `space_updated_by` int NULL DEFAULT NULL,
  `space_updated_at` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`space_id`) USING BTREE,
  UNIQUE INDEX `uq_spc_space_site_name`(`site_id` ASC, `space_name` ASC) USING BTREE,
  INDEX `idx_spc_space_site`(`site_id` ASC) USING BTREE,
  INDEX `idx_spc_space_status`(`space_status` ASC) USING BTREE,
  INDEX `fk_spc_space_location`(`space_location_id` ASC) USING BTREE,
  INDEX `fk_spc_space_category`(`space_category_id` ASC) USING BTREE,
  INDEX `fk_spc_space_type`(`space_type_id` ASC) USING BTREE,
  CONSTRAINT `fk_spc_space_category` FOREIGN KEY (`space_category_id`) REFERENCES `ref_space_category` (`space_category_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_spc_space_location` FOREIGN KEY (`space_location_id`) REFERENCES `ref_space_location` (`space_location_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_spc_space_type` FOREIGN KEY (`space_type_id`) REFERENCES `ref_space_type` (`space_type_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- New table: spc_space_asset
CREATE TABLE IF NOT EXISTS `spc_space_asset`  (
  `space_asset_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `space_id` int UNSIGNED NOT NULL,
  `asset_id` bigint NOT NULL,
  `linked_by` int NULL DEFAULT NULL,
  `linked_at` datetime NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`space_asset_id`) USING BTREE,
  UNIQUE INDEX `uq_spc_space_asset`(`space_id` ASC, `asset_id` ASC) USING BTREE,
  INDEX `idx_spc_space_asset_asset`(`asset_id` ASC) USING BTREE,
  CONSTRAINT `fk_spc_space_asset_asset` FOREIGN KEY (`asset_id`) REFERENCES `ast_asset` (`asset_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_spc_space_asset_space` FOREIGN KEY (`space_id`) REFERENCES `spc_space` (`space_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- New table: spc_space_media
CREATE TABLE IF NOT EXISTS `spc_space_media`  (
  `space_media_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `space_id` int UNSIGNED NOT NULL,
  `upload_id` bigint NOT NULL,
  `media_type` enum('PHOTO','FLOORPLAN') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `media_caption` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `is_cover` tinyint(1) NOT NULL DEFAULT 0,
  `media_created_by` int NULL DEFAULT NULL,
  `media_created_at` datetime NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`space_media_id`) USING BTREE,
  INDEX `idx_spc_space_media_space`(`space_id` ASC) USING BTREE,
  INDEX `fk_spc_space_media_upload`(`upload_id` ASC) USING BTREE,
  INDEX `idx_spc_space_media_cover`(`space_id` ASC, `is_cover` ASC) USING BTREE,
  CONSTRAINT `fk_spc_space_media_space` FOREIGN KEY (`space_id`) REFERENCES `spc_space` (`space_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_spc_space_media_upload` FOREIGN KEY (`upload_id`) REFERENCES `sys_upload` (`upload_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- New table: sys_nav_role_backup_20251021
CREATE TABLE IF NOT EXISTS `sys_nav_role_backup_20251021`  (
  `nav_role_id` smallint NOT NULL DEFAULT 0,
  `role_id` tinyint NOT NULL,
  `nav_id` smallint NOT NULL,
  `nav_second_id` smallint NULL DEFAULT NULL,
  `nav_role_turn` smallint NOT NULL DEFAULT 1
) ENGINE = InnoDB CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- New table: sys_site
CREATE TABLE IF NOT EXISTS `sys_site`  (
  `site_id` int NOT NULL AUTO_INCREMENT,
  `site_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `site_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `site_running_no` int NOT NULL DEFAULT 0,
  `site_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`site_id`) USING BTREE,
  UNIQUE INDEX `site_code`(`site_code` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- New table: sys_user_signature
CREATE TABLE IF NOT EXISTS `sys_user_signature`  (
  `user_id` int NOT NULL,
  `signature_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Filesystem relative path to current signature image',
  `signature_sha256` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SHA-256 hash of file content for integrity',
  `mime_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image/png',
  `width` smallint NULL DEFAULT NULL,
  `height` smallint NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`) USING BTREE,
  CONSTRAINT `fk_user_sig_user` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- New table: vm_host
CREATE TABLE IF NOT EXISTS `vm_host`  (
  `host_id` int NOT NULL AUTO_INCREMENT,
  `site_id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `contact_no` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `department` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp,
  `updated_at` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`host_id`) USING BTREE,
  UNIQUE INDEX `uq_vm_host_site_name`(`site_id` ASC, `name` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- New table: vm_visit
CREATE TABLE IF NOT EXISTS `vm_visit`  (
  `visit_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `arrived_at` datetime NOT NULL DEFAULT current_timestamp,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ic_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `party_size` int UNSIGNED NOT NULL DEFAULT 1,
  `host_id` int NULL DEFAULT NULL,
  `photo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `host_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `purpose` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('CHECKED_IN','CHECKED_OUT','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CHECKED_IN',
  `archived_at` datetime NULL DEFAULT NULL,
  `deleted_at` datetime NULL DEFAULT NULL,
  `access_card_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_via` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'WEB_FORM',
  `created_by` bigint UNSIGNED NULL DEFAULT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`visit_id`) USING BTREE,
  INDEX `idx_vm_visit_site_arrived`(`site_id` ASC, `arrived_at` ASC) USING BTREE,
  INDEX `idx_vm_visit_status`(`status` ASC) USING BTREE,
  INDEX `idx_vm_visit_host_id`(`host_id` ASC) USING BTREE,
  CONSTRAINT `fk_vm_visit_site` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- New table: wo_import_batch
CREATE TABLE IF NOT EXISTS `wo_import_batch`  (
  `batch_id` int NOT NULL AUTO_INCREMENT,
  `import_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `site_id` int NOT NULL,
  `imported_by` int NOT NULL,
  `total_rows` int NOT NULL DEFAULT 0,
  `imported_rows` int NOT NULL DEFAULT 0,
  `skipped_rows` int NOT NULL DEFAULT 0,
  `import_status` enum('PROCESSING','COMPLETED','FAILED') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PROCESSING',
  `created_at` datetime NOT NULL,
  `completed_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`batch_id`) USING BTREE,
  INDEX `idx_site_imported_by`(`site_id` ASC, `imported_by` ASC) USING BTREE,
  INDEX `idx_created_at`(`created_at` ASC) USING BTREE,
  INDEX `idx_import_status`(`import_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- New table: wo_task_public
CREATE TABLE IF NOT EXISTS `wo_task_public`  (
  `wo_task_public` bigint NOT NULL AUTO_INCREMENT,
  `wo_task_id` bigint NOT NULL,
  `transaction_id` bigint NOT NULL,
  `wo_task_public_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_public_ic_no` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_public_agency` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_public_phone_no` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_public_email` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_public_complaint` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL,
  `user_id` int NULL DEFAULT NULL,
  PRIMARY KEY (`wo_task_public`) USING BTREE,
  INDEX `wo_task_public_ibfk_1`(`wo_task_id` ASC) USING BTREE,
  INDEX `wo_task_public_ibfk_2`(`transaction_id` ASC) USING BTREE,
  INDEX `wo_task_public_ibfk_3`(`user_id` ASC) USING BTREE,
  CONSTRAINT `wo_task_public_ibfk_1` FOREIGN KEY (`wo_task_id`) REFERENCES `wo_task` (`wo_task_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_public_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `wfl_transaction` (`transaction_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_public_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- New table: wo_task_request_2
CREATE TABLE IF NOT EXISTS `wo_task_request_2`  (
  `wo_task_request_id` bigint NOT NULL AUTO_INCREMENT,
  `wo_task_id` bigint NULL DEFAULT NULL,
  `transaction_id` bigint NULL DEFAULT NULL,
  `wo_task_request_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `wo_task_request_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `wo_task_request_order_by` int NULL DEFAULT NULL,
  `wo_task_request_mrf_generate` tinyint(1) NOT NULL DEFAULT 1,
  `wo_task_request_mrf_pdf` bigint NULL DEFAULT NULL,
  `wo_task_request_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `wo_task_request_time_ordered` timestamp NULL DEFAULT NULL,
  `wo_task_request_time_collected` timestamp NULL DEFAULT NULL,
  `wo_task_request_time_rejected` timestamp NULL DEFAULT NULL,
  `wo_task_request_status` tinyint NULL DEFAULT NULL,
  PRIMARY KEY (`wo_task_request_id`) USING BTREE,
  INDEX `wo_task_id`(`wo_task_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- 2) Columns missing from tables that already exist in the old schema
ALTER TABLE `ast_asset`
  ADD COLUMN IF NOT EXISTS `zone_id` smallint NULL DEFAULT NULL AFTER `contract_id`;
ALTER TABLE `ast_asset`
  ADD COLUMN IF NOT EXISTS `asset_lifespan_year` tinyint NULL DEFAULT NULL AFTER `asset_lifetime_date`;
ALTER TABLE `ast_asset`
  ADD COLUMN IF NOT EXISTS `asset_lifespan_start_date` date NULL DEFAULT NULL AFTER `asset_lifespan_year`;
ALTER TABLE `ast_asset`
  ADD COLUMN IF NOT EXISTS `asset_lifespan_alert` tinyint NULL DEFAULT NULL AFTER `asset_lifespan_start_date`;
ALTER TABLE `ast_asset`
  ADD COLUMN IF NOT EXISTS `asset_value_depreciation` tinyint NULL DEFAULT NULL AFTER `asset_lifespan_alert`;
ALTER TABLE `ast_asset`
  ADD COLUMN IF NOT EXISTS `asset_value_alert` decimal(10, 2) NULL DEFAULT NULL AFTER `asset_value_depreciation`;
ALTER TABLE `ast_asset`
  ADD COLUMN IF NOT EXISTS `asset_repair_alert` decimal(10, 2) NULL DEFAULT NULL AFTER `asset_value_alert`;
ALTER TABLE `ast_asset`
  ADD COLUMN IF NOT EXISTS `asset_running_hours` smallint NULL DEFAULT NULL AFTER `asset_repair_alert`;
ALTER TABLE `ast_asset`
  ADD COLUMN IF NOT EXISTS `asset_disposal_status` tinyint(1) NULL DEFAULT NULL AFTER `asset_running_hours`;
ALTER TABLE `ast_asset`
  ADD COLUMN IF NOT EXISTS `asset_disposal_date` date NULL DEFAULT NULL AFTER `asset_disposal_status`;
ALTER TABLE `ast_asset`
  ADD COLUMN IF NOT EXISTS `asset_disposal_item_cost` decimal(10, 2) NULL DEFAULT NULL AFTER `asset_disposal_date`;
ALTER TABLE `ast_asset`
  ADD COLUMN IF NOT EXISTS `asset_disposal_service_cost` decimal(10, 2) NULL DEFAULT NULL AFTER `asset_disposal_item_cost`;
ALTER TABLE `ast_asset`
  ADD COLUMN IF NOT EXISTS `asset_mtbf_alert` smallint NULL DEFAULT NULL AFTER `asset_disposal_service_cost`;
ALTER TABLE `ast_asset`
  ADD COLUMN IF NOT EXISTS `asset_mttr_alert` smallint NULL DEFAULT NULL AFTER `asset_mtbf_alert`;

ALTER TABLE `ast_part_sub`
  ADD COLUMN IF NOT EXISTS `part_sub_return_id` bigint NULL DEFAULT NULL COMMENT 'FK to material_returns if returned' AFTER `part_sub_status`;
ALTER TABLE `ast_part_sub`
  ADD COLUMN IF NOT EXISTS `part_sub_returned_date` datetime NULL DEFAULT NULL COMMENT 'When part was returned to inventory' AFTER `part_sub_return_id`;
ALTER TABLE `ast_part_sub`
  ADD COLUMN IF NOT EXISTS `part_sub_returned_by` int NULL DEFAULT NULL COMMENT 'User who returned the part' AFTER `part_sub_returned_date`;

ALTER TABLE `cli_site`
  ADD COLUMN IF NOT EXISTS `site_is_public` tinyint(1) NOT NULL DEFAULT 0 AFTER `site_is_attendance`;

ALTER TABLE `noti_web`
  ADD COLUMN IF NOT EXISTS `nav_id` tinyint NULL DEFAULT NULL AFTER `noti_web_link`;
ALTER TABLE `noti_web`
  ADD COLUMN IF NOT EXISTS `nav_second_id` tinyint NULL DEFAULT NULL AFTER `nav_id`;

ALTER TABLE `ppm`
  ADD COLUMN IF NOT EXISTS `ppm_name` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL AFTER `ppm_issue_no`;
ALTER TABLE `ppm`
  ADD COLUMN IF NOT EXISTS `ppm_set_id` smallint NULL DEFAULT NULL AFTER `ppm_name`;
ALTER TABLE `ppm`
  ADD COLUMN IF NOT EXISTS `ppm_is_group` tinyint(1) NULL DEFAULT NULL AFTER `ppm_set_id`;
ALTER TABLE `ppm`
  ADD COLUMN IF NOT EXISTS `asset_type_id` smallint NULL DEFAULT NULL AFTER `asset_id`;
ALTER TABLE `ppm`
  ADD COLUMN IF NOT EXISTS `ppm_frequency` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL AFTER `checklist_id`;
ALTER TABLE `ppm`
  ADD COLUMN IF NOT EXISTS `ppm_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL AFTER `ppm_group_id`;

ALTER TABLE `ppm_task`
  ADD COLUMN IF NOT EXISTS `ppm_task_is_group_executed` tinyint(1) NOT NULL DEFAULT 0 AFTER `ppm_task_max_assistant`;
ALTER TABLE `ppm_task`
  ADD COLUMN IF NOT EXISTS `ppm_task_completed_offline` tinyint(1) NULL DEFAULT 0 COMMENT '1 if task was completed offline' AFTER `ppm_task_time_verified`;

ALTER TABLE `sys_user`
  ADD COLUMN IF NOT EXISTS `user_designation` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL AFTER `user_status`;

ALTER TABLE `wo_task`
  ADD COLUMN IF NOT EXISTS `wo_task_external_ref` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'External system work order reference' AFTER `wo_task_is_helpdesk`;
ALTER TABLE `wo_task`
  ADD COLUMN IF NOT EXISTS `wo_task_is_imported` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flag to indicate if WO was imported from external system' AFTER `wo_task_external_ref`;
ALTER TABLE `wo_task`
  ADD COLUMN IF NOT EXISTS `wo_task_is_public` tinyint(1) NOT NULL DEFAULT 0 AFTER `wo_task_is_imported`;
ALTER TABLE `wo_task`
  ADD COLUMN IF NOT EXISTS `zone_id` smallint NULL DEFAULT NULL AFTER `site_id`;
ALTER TABLE `wo_task`
  ADD COLUMN IF NOT EXISTS `wo_task_is_pdf_wr` tinyint(1) NOT NULL DEFAULT 0 AFTER `pdf_id_wr`;
ALTER TABLE `wo_task`
  ADD COLUMN IF NOT EXISTS `wo_task_is_pdf` tinyint(1) NOT NULL DEFAULT 0 AFTER `wo_task_is_pdf_wr`;

ALTER TABLE `wo_task_request`
  ADD COLUMN IF NOT EXISTS `wo_task_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL AFTER `wo_task_request_no`;
ALTER TABLE `wo_task_request`
  ADD COLUMN IF NOT EXISTS `store_id` smallint NULL DEFAULT NULL AFTER `wo_task_no`;
ALTER TABLE `wo_task_request`
  ADD COLUMN IF NOT EXISTS `wo_task_request_severity` tinyint(1) NULL DEFAULT NULL AFTER `store_id`;
ALTER TABLE `wo_task_request`
  ADD COLUMN IF NOT EXISTS `wo_task_request_is_standalone` tinyint(1) NOT NULL DEFAULT 0 AFTER `wo_task_request_order_by`;
ALTER TABLE `wo_task_request`
  ADD COLUMN IF NOT EXISTS `wo_task_request_mrf_generate` tinyint(1) NOT NULL DEFAULT 1 AFTER `wo_task_request_is_standalone`;
ALTER TABLE `wo_task_request`
  ADD COLUMN IF NOT EXISTS `wo_task_request_mrf_pdf` bigint NULL DEFAULT NULL AFTER `wo_task_request_mrf_generate`;
ALTER TABLE `wo_task_request`
  ADD COLUMN IF NOT EXISTS `wo_task_request_time_created` timestamp NOT NULL DEFAULT current_timestamp AFTER `wo_task_request_mrf_pdf`;

-- 3) Current column definition alignment for columns present in both schemas
-- Data-preserving exception:
-- Old production stores these as TEXT. Keep them as-is during live upgrade if
-- any value is longer than the current VARCHAR limits.
SELECT 'ast_asset.asset_no values longer than 100 chars' AS check_name,
       COUNT(*) AS rows_over_100,
       MAX(CHAR_LENGTH(`asset_no`)) AS max_length
  FROM `ast_asset`
 WHERE `asset_no` IS NOT NULL
   AND CHAR_LENGTH(`asset_no`) > 100;
SELECT 'ast_asset.asset_name values longer than 150 chars' AS check_name,
       COUNT(*) AS rows_over_150,
       MAX(CHAR_LENGTH(`asset_name`)) AS max_length
  FROM `ast_asset`
 WHERE `asset_name` IS NOT NULL
   AND CHAR_LENGTH(`asset_name`) > 150;
-- Skipped until long values are reviewed/cleaned:
-- ALTER TABLE `ast_asset`
--   MODIFY COLUMN `asset_no` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;
-- ALTER TABLE `ast_asset`
--   MODIFY COLUMN `asset_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;
ALTER TABLE `ast_asset`
  MODIFY COLUMN `asset_desc` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;
ALTER TABLE `ast_asset`
  MODIFY COLUMN `asset_warranty_notes` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;
ALTER TABLE `ast_asset`
  MODIFY COLUMN `asset_technician_notes` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;

ALTER TABLE `ast_part`
  MODIFY COLUMN `part_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;

ALTER TABLE `att_group`
  MODIFY COLUMN `att_group_remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL;

ALTER TABLE `cli_location_code`
  MODIFY COLUMN `location_code_name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
ALTER TABLE `cli_location_code`
  MODIFY COLUMN `location_code_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;

ALTER TABLE `drawing`
  MODIFY COLUMN `drawing_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;

ALTER TABLE `fca_task`
  MODIFY COLUMN `fca_task_observation` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL;
ALTER TABLE `fca_task`
  MODIFY COLUMN `fca_task_recommendation` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL;
ALTER TABLE `fca_task`
  MODIFY COLUMN `fca_task_validation` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL;

ALTER TABLE `kpi_info`
  MODIFY COLUMN `kpi_info_target_value` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;
ALTER TABLE `kpi_info`
  MODIFY COLUMN `kpi_info_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;

ALTER TABLE `material_returns`
  MODIFY COLUMN `return_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT 'Optional free text remarks';

ALTER TABLE `noti_log`
  MODIFY COLUMN `noti_to` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;
ALTER TABLE `noti_log`
  MODIFY COLUMN `noti_html` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;

ALTER TABLE `noti_web`
  MODIFY COLUMN `noti_web_type` tinyint NOT NULL COMMENT '1=WO assign\n2=WO verify';

ALTER TABLE `ppm`
  MODIFY COLUMN `asset_id` bigint NULL DEFAULT NULL;

ALTER TABLE `ppm_checklist`
  MODIFY COLUMN `checklist_guideline` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;

ALTER TABLE `ppm_checklist_qual`
  MODIFY COLUMN `checklist_qual_desc` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;

ALTER TABLE `ppm_checklist_quan`
  MODIFY COLUMN `checklist_quan_desc` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL;
ALTER TABLE `ppm_checklist_quan`
  MODIFY COLUMN `checklist_quan_unit` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;

ALTER TABLE `ppm_offline_sync_log`
  MODIFY COLUMN `request_payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT 'Full JSON request body';
ALTER TABLE `ppm_offline_sync_log`
  MODIFY COLUMN `response_payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT 'Full JSON response body';

ALTER TABLE `ppm_task`
  MODIFY COLUMN `ppm_task_guideline` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;
ALTER TABLE `ppm_task`
  MODIFY COLUMN `ppm_task_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;

ALTER TABLE `ppm_task_qual`
  MODIFY COLUMN `ppm_task_qual_desc` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;

ALTER TABLE `ppm_task_quan`
  MODIFY COLUMN `ppm_task_quan_desc` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;

ALTER TABLE `ptw_permit`
  MODIFY COLUMN `ptw_work_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `ptw_extension_requested_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `ptw_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `ptw_hazards` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `ptw_control_measures` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `ptw_checklist_cold_work` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL;
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `ptw_checklist_hot_work` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL;
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `ptw_checklist_confined_space` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL;
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `ptw_hazard_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL;
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `ptw_declaration_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL;
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `ptw_supporting_docs_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT 'URS Supporting Documents Checklist';
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `ptw_certificate_numbers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT 'Certificate and Permit Numbers';
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `ptw_complete_form_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `ptw_supervisor_comments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `ptw_she_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `ptw_fm_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `cancel_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `suspend_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
ALTER TABLE `ptw_permit`
  MODIFY COLUMN `ptw_hazardous_activities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL;

ALTER TABLE `ref_item`
  MODIFY COLUMN `item_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;

ALTER TABLE `sys_audit`
  MODIFY COLUMN `audit_remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL;

ALTER TABLE `sys_upload`
  MODIFY COLUMN `upload_blob_data` blob NULL;

ALTER TABLE `wfl_task`
  MODIFY COLUMN `task_remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL;

-- Data-preserving exception:
-- Old production stores `wfl_transaction`.`asset_no` as TEXT and has values
-- longer than the current VARCHAR(30) schema. Do not shrink this column during
-- live upgrade, otherwise MySQL raises 1406 "Data too long" and aborts.
SELECT 'wfl_transaction.asset_no values longer than 30 chars' AS check_name,
       COUNT(*) AS rows_over_30,
       MAX(CHAR_LENGTH(`asset_no`)) AS max_length
  FROM `wfl_transaction`
 WHERE `asset_no` IS NOT NULL
   AND CHAR_LENGTH(`asset_no`) > 30;
-- Skipped until long values are reviewed/cleaned:
-- ALTER TABLE `wfl_transaction`
--   MODIFY COLUMN `asset_no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;

ALTER TABLE `wo_import_log`
  MODIFY COLUMN `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;
ALTER TABLE `wo_import_log`
  MODIFY COLUMN `row_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT 'JSON data of the imported row';

ALTER TABLE `wo_migration`
  MODIFY COLUMN `wo_task_complaint` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL;
ALTER TABLE `wo_migration`
  MODIFY COLUMN `wo_task_repair_desc` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL;

-- Data-preserving exception:
-- Old production allows 225 characters here; current schema narrows to 150.
SELECT 'wo_task.wo_task_location values longer than 150 chars' AS check_name,
       COUNT(*) AS rows_over_150,
       MAX(CHAR_LENGTH(`wo_task_location`)) AS max_length
  FROM `wo_task`
 WHERE `wo_task_location` IS NOT NULL
   AND CHAR_LENGTH(`wo_task_location`) > 150;
-- Skipped until long values are reviewed/cleaned:
-- ALTER TABLE `wo_task`
--   MODIFY COLUMN `wo_task_location` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;
ALTER TABLE `wo_task`
  MODIFY COLUMN `wo_task_complaint` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;
ALTER TABLE `wo_task`
  MODIFY COLUMN `wo_task_wr_check` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;
ALTER TABLE `wo_task`
  MODIFY COLUMN `wo_task_repair_desc` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;

ALTER TABLE `wo_task_parts`
  MODIFY COLUMN `wo_task_parts_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;

ALTER TABLE `wo_task_request`
  MODIFY COLUMN `wo_task_request_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;

ALTER TABLE `wo_task_upload`
  MODIFY COLUMN `wo_task_upload_type` tinyint NOT NULL DEFAULT 1 COMMENT '1=complain, 2=before, 3=during, 4=after, 5=signiture complainer, 6=signiture responder, 7=signiture executor, 8=signiture verified, 9=signiture wr checked, 10=signiture wr verified, 12=signiture check';

SET FOREIGN_KEY_CHECKS = 1;

-- 4) Views missing from the old schema
-- View: vw_item_image
CREATE OR REPLACE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_item_image` AS select `ii`.`item_image_id` AS `itemImageId`,`ii`.`item_id` AS `itemId`,`ii`.`upload_id` AS `uploadId`,`u`.`upload_name` AS `uploadName`,`u`.`upload_filename` AS `uploadFilename`,`u`.`upload_extension` AS `uploadExtension`,`u`.`upload_folder` AS `uploadFolder`,`u`.`upload_file_width` AS `uploadFileWidth`,`u`.`upload_file_height` AS `uploadFileHeight` from (`ref_item_image` `ii` join `sys_upload` `u` on(`ii`.`upload_id` = `u`.`upload_id`)) where `u`.`upload_status` = 1 ;

-- View: vw_part_with_image
CREATE OR REPLACE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_part_with_image` AS select `p`.`part_id` AS `partId`,`p`.`site_id` AS `siteId`,`p`.`store_id` AS `storeId`,`p`.`asset_group_id` AS `assetGroupId`,`p`.`item_type_id` AS `itemTypeId`,`p`.`item_id` AS `itemId`,`p`.`part_count` AS `partCount`,`p`.`part_locked` AS `partLocked`,`p`.`part_threshold` AS `partThreshold`,`p`.`part_min_order` AS `partMinOrder`,`p`.`part_max_order` AS `partMaxOrder`,`p`.`part_remark` AS `partRemark`,`p`.`part_status` AS `partStatus`,group_concat(distinct concat(`u`.`upload_folder`,'/',`u`.`upload_filename`,'.',`u`.`upload_extension`) order by `ii`.`item_image_id` ASC separator '||') AS `uploadList`,group_concat(distinct `u`.`upload_name` order by `ii`.`item_image_id` ASC separator '||') AS `titleList`,group_concat(distinct `u`.`upload_file_width` order by `ii`.`item_image_id` ASC separator '||') AS `widthList`,group_concat(distinct `u`.`upload_file_height` order by `ii`.`item_image_id` ASC separator '||') AS `heightList` from ((`ast_part` `p` left join `ref_item_image` `ii` on(`p`.`item_id` = `ii`.`item_id`)) left join `sys_upload` `u` on(`ii`.`upload_id` = `u`.`upload_id` and `u`.`upload_status` = 1)) group by `p`.`part_id`,`p`.`site_id`,`p`.`store_id`,`p`.`asset_group_id`,`p`.`item_type_id`,`p`.`item_id`,`p`.`part_count`,`p`.`part_locked`,`p`.`part_threshold`,`p`.`part_min_order`,`p`.`part_max_order`,`p`.`part_remark`,`p`.`part_status` ;

-- View: vw_ppm_set_asset_details
CREATE OR REPLACE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_ppm_set_asset_details` AS select `psa`.`ppm_set_asset_id` AS `ppm_set_asset_id`,`psa`.`ppm_set_id` AS `ppm_set_id`,`psa`.`asset_id` AS `asset_id`,`aa`.`asset_no` AS `asset_no`,`aa`.`asset_name` AS `asset_name`,`aa`.`asset_location_desc` AS `asset_location_desc` from (`ppm_set_asset` `psa` join `ast_asset` `aa` on(`psa`.`asset_id` = `aa`.`asset_id`)) ;

-- 5) Extra old objects intentionally preserved
-- Extra old tables not dropped: wo_task_out_scope, z_jai_batch_ppm, z_migration, z_raw
-- Extra old columns not dropped from `ppm`: ppm_is_routine
-- Extra old columns not dropped from `sys_pdf`: pdf_time_update
-- Extra old columns not dropped from `wo_task`: location_code_id, wo_task_done_out_scope
-- Extra old columns not dropped from `wo_task_parts`: wo_task_parts_return

SELECT 'upgrade_db_jkr_production_old_to_current.sql completed' AS status;
