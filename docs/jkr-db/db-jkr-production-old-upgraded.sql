-- ============================================================================
-- GEMS JKR schema upgraded from old production structure
-- Generated: 2026-05-22
-- Old source: docs/jkr-db/db-jkr-production-old.sql
-- Current source: docs/jkr-db/gems_jkr_staging_prod.sql
-- Schema only: no data rows are inserted.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Table structure for ast_asset
CREATE TABLE IF NOT EXISTS `ast_asset`  (
  `asset_id` bigint NOT NULL AUTO_INCREMENT,
  `asset_no` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_serial_no` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_desc` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `asset_capacity` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_group_id` smallint NULL DEFAULT NULL,
  `asset_category_id` smallint NULL DEFAULT NULL,
  `asset_type_id` smallint NULL DEFAULT NULL,
  `asset_brand_id` smallint NULL DEFAULT NULL,
  `asset_model_id` mediumint NULL DEFAULT NULL,
  `contract_id` smallint NULL DEFAULT NULL,
  `zone_id` smallint NULL DEFAULT NULL,
  `location_code_id` int NULL DEFAULT NULL,
  `asset_location_code` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_location_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_group_id` smallint NULL DEFAULT NULL,
  `asset_block` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_level` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_manufacturer` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_supplier` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_agency` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_department` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_construction_zone` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_operation_zone` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_room` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_compartment` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_auth_employee` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_criticality` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_contractor` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_warranty` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_warranty_exp_date` date NULL DEFAULT NULL,
  `asset_life_cycle` smallint NULL DEFAULT NULL,
  `asset_warranty_notes` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `asset_technician_notes` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `asset_purchase_price` decimal(10, 2) NULL DEFAULT NULL,
  `asset_commissioned_date` date NULL DEFAULT NULL,
  `asset_disposed_date` date NULL DEFAULT NULL,
  `asset_current_value` decimal(10, 2) NULL DEFAULT NULL,
  `asset_estimated_life` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_lifetime_date` date NULL DEFAULT NULL,
  `asset_lifespan_year` tinyint NULL DEFAULT NULL,
  `asset_lifespan_start_date` date NULL DEFAULT NULL,
  `asset_lifespan_alert` tinyint NULL DEFAULT NULL,
  `asset_value_depreciation` tinyint NULL DEFAULT NULL,
  `asset_value_alert` decimal(10, 2) NULL DEFAULT NULL,
  `asset_repair_alert` decimal(10, 2) NULL DEFAULT NULL,
  `asset_running_hours` smallint NULL DEFAULT NULL,
  `asset_disposal_status` tinyint(1) NULL DEFAULT NULL,
  `asset_disposal_date` date NULL DEFAULT NULL,
  `asset_disposal_item_cost` decimal(10, 2) NULL DEFAULT NULL,
  `asset_disposal_service_cost` decimal(10, 2) NULL DEFAULT NULL,
  `asset_mtbf_alert` smallint NULL DEFAULT NULL,
  `asset_mttr_alert` smallint NULL DEFAULT NULL,
  `migration_id` smallint NULL DEFAULT NULL,
  `document_no` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_registered_by` int NULL DEFAULT NULL,
  `asset_time_registered` timestamp NULL DEFAULT NULL,
  `asset_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `asset_status` tinyint NOT NULL DEFAULT 1,
  `asset_temp_flag` tinyint(1) NULL DEFAULT NULL,
  `checklist_id` int NULL DEFAULT NULL,
  PRIMARY KEY (`asset_id`) USING BTREE,
  INDEX `contract_id`(`contract_id` ASC) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id` ASC) USING BTREE,
  INDEX `asset_category_id`(`asset_category_id` ASC) USING BTREE,
  INDEX `asset_type_id`(`asset_type_id` ASC) USING BTREE,
  INDEX `asset_brand_id`(`asset_brand_id` ASC) USING BTREE,
  INDEX `asset_model_id`(`asset_model_id` ASC) USING BTREE,
  INDEX `asset_status`(`asset_status` ASC) USING BTREE,
  INDEX `asset_registered_by`(`asset_registered_by` ASC) USING BTREE,
  INDEX `ppm_group_id`(`ppm_group_id` ASC) USING BTREE,
  INDEX `checklist_id`(`checklist_id` ASC) USING BTREE,
  INDEX `zone_id`(`zone_id` ASC) USING BTREE,
  INDEX `idx_asset_id`(`asset_id` ASC) USING BTREE,
  INDEX `idx_asset_no`(`asset_no` ASC) USING BTREE,
  CONSTRAINT `ast_asset_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `cli_contract` (`contract_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_ibfk_10` FOREIGN KEY (`ppm_group_id`) REFERENCES `ppm_group` (`ppm_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_ibfk_11` FOREIGN KEY (`checklist_id`) REFERENCES `ppm_checklist` (`checklist_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_ibfk_12` FOREIGN KEY (`zone_id`) REFERENCES `cli_zone` (`zone_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_ibfk_3` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_ibfk_4` FOREIGN KEY (`asset_category_id`) REFERENCES `ast_asset_category` (`asset_category_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_ibfk_5` FOREIGN KEY (`asset_type_id`) REFERENCES `ast_asset_type` (`asset_type_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_ibfk_6` FOREIGN KEY (`asset_brand_id`) REFERENCES `ast_asset_brand` (`asset_brand_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_ibfk_7` FOREIGN KEY (`asset_model_id`) REFERENCES `ast_asset_model` (`asset_model_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_ibfk_8` FOREIGN KEY (`asset_registered_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ast_asset_brand
CREATE TABLE IF NOT EXISTS `ast_asset_brand`  (
  `asset_brand_id` smallint NOT NULL AUTO_INCREMENT,
  `asset_brand_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `asset_brand_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_brand_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `asset_brand_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`asset_brand_id`) USING BTREE,
  INDEX `asset_brand_status`(`asset_brand_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ast_asset_category
CREATE TABLE IF NOT EXISTS `ast_asset_category`  (
  `asset_category_id` smallint NOT NULL AUTO_INCREMENT,
  `asset_category_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `asset_category_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_group_id` smallint NOT NULL,
  `asset_category_time_created` timestamp NULL DEFAULT NULL,
  `asset_category_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`asset_category_id`) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id` ASC) USING BTREE,
  INDEX `asset_category_status`(`asset_category_status` ASC) USING BTREE,
  CONSTRAINT `ast_asset_category_ibfk_1` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ast_asset_group
CREATE TABLE IF NOT EXISTS `ast_asset_group`  (
  `asset_group_id` smallint NOT NULL AUTO_INCREMENT,
  `asset_group_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `asset_group_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_group_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `asset_group_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`asset_group_id`) USING BTREE,
  INDEX `asset_group_status`(`asset_group_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ast_asset_model
CREATE TABLE IF NOT EXISTS `ast_asset_model`  (
  `asset_model_id` mediumint NOT NULL AUTO_INCREMENT,
  `asset_model_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `asset_model_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_brand_id` smallint NOT NULL,
  `asset_type_id` smallint NOT NULL,
  `asset_model_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `asset_model_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`asset_model_id`) USING BTREE,
  INDEX `asset_brand_id`(`asset_brand_id` ASC) USING BTREE,
  INDEX `asset_model_status`(`asset_model_status` ASC) USING BTREE,
  INDEX `asset_type_id`(`asset_type_id` ASC) USING BTREE,
  CONSTRAINT `ast_asset_model_ibfk_1` FOREIGN KEY (`asset_brand_id`) REFERENCES `ast_asset_brand` (`asset_brand_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_model_ibfk_2` FOREIGN KEY (`asset_type_id`) REFERENCES `ast_asset_type` (`asset_type_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ast_asset_type
CREATE TABLE IF NOT EXISTS `ast_asset_type`  (
  `asset_type_id` smallint NOT NULL AUTO_INCREMENT,
  `asset_type_name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `asset_type_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_type_speacial` tinyint(1) NULL DEFAULT NULL,
  `asset_category_id` smallint NOT NULL,
  `asset_type_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `asset_type_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`asset_type_id`) USING BTREE,
  INDEX `asset_category_id`(`asset_category_id` ASC) USING BTREE,
  INDEX `asset_type_status`(`asset_type_status` ASC) USING BTREE,
  CONSTRAINT `ast_asset_type_ibfk_1` FOREIGN KEY (`asset_category_id`) REFERENCES `ast_asset_category` (`asset_category_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ast_part
CREATE TABLE IF NOT EXISTS `ast_part`  (
  `part_id` bigint NOT NULL AUTO_INCREMENT,
  `site_id` smallint NULL DEFAULT NULL,
  `store_id` smallint NULL DEFAULT NULL,
  `asset_group_id` smallint NULL DEFAULT NULL,
  `item_type_id` smallint NULL DEFAULT NULL,
  `item_id` smallint NULL DEFAULT NULL,
  `part_count` smallint NULL DEFAULT NULL,
  `part_locked` smallint NULL DEFAULT NULL,
  `part_threshold` smallint NULL DEFAULT NULL,
  `part_min_order` smallint NULL DEFAULT NULL,
  `part_max_order` smallint NULL DEFAULT NULL,
  `part_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `part_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`part_id`) USING BTREE,
  INDEX `part_status`(`part_status` ASC) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  INDEX `store_id`(`store_id` ASC) USING BTREE,
  INDEX `item_id`(`item_id` ASC) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id` ASC) USING BTREE,
  INDEX `item_type_id`(`item_type_id` ASC) USING BTREE,
  CONSTRAINT `ast_part_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_part_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `cli_store` (`store_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_part_ibfk_3` FOREIGN KEY (`item_id`) REFERENCES `ref_item` (`item_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_part_ibfk_4` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_part_ibfk_5` FOREIGN KEY (`item_type_id`) REFERENCES `ref_item_type` (`item_type_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ast_part_sub
CREATE TABLE IF NOT EXISTS `ast_part_sub`  (
  `part_sub_id` bigint NOT NULL AUTO_INCREMENT,
  `part_id` bigint NOT NULL,
  `item_id` smallint NULL DEFAULT NULL,
  `part_sub_no` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `do_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `do_item_id` bigint NULL DEFAULT NULL,
  `wo_task_parts_id` bigint NULL DEFAULT NULL,
  `wo_task_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `wo_task_request_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `part_sub_location` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `part_sub_warranty` tinyint NULL DEFAULT NULL,
  `part_sub_validity` date NULL DEFAULT NULL,
  `part_sub_cost` decimal(12, 2) NULL DEFAULT NULL,
  `part_sub_registered_by` int NULL DEFAULT NULL,
  `part_sub_collected_by` int NULL DEFAULT NULL,
  `part_sub_time_registered` timestamp NOT NULL DEFAULT current_timestamp,
  `part_sub_time_reserved` timestamp NULL DEFAULT NULL,
  `part_sub_time_check_out` timestamp NULL DEFAULT NULL,
  `part_sub_status` tinyint NULL DEFAULT NULL,
  `part_sub_return_id` bigint NULL DEFAULT NULL COMMENT 'FK to material_returns if returned',
  `part_sub_returned_date` datetime NULL DEFAULT NULL COMMENT 'When part was returned to inventory',
  `part_sub_returned_by` int NULL DEFAULT NULL COMMENT 'User who returned the part',
  PRIMARY KEY (`part_sub_id`) USING BTREE,
  INDEX `part_id`(`part_id` ASC) USING BTREE,
  INDEX `item_id`(`item_id` ASC) USING BTREE,
  INDEX `wo_task_parts_id`(`wo_task_parts_id` ASC) USING BTREE,
  INDEX `do_item_id`(`do_item_id` ASC) USING BTREE,
  INDEX `idx_return_id`(`part_sub_return_id` ASC) USING BTREE,
  INDEX `idx_returned_date`(`part_sub_returned_date` ASC) USING BTREE,
  INDEX `fk_part_sub_returned_by`(`part_sub_returned_by` ASC) USING BTREE,
  CONSTRAINT `ast_part_sub_ibfk_1` FOREIGN KEY (`part_id`) REFERENCES `ast_part` (`part_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_part_sub_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `ref_item` (`item_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_part_sub_ibfk_4` FOREIGN KEY (`wo_task_parts_id`) REFERENCES `wo_task_parts` (`wo_task_parts_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_part_sub_ibfk_5` FOREIGN KEY (`do_item_id`) REFERENCES `do_item` (`do_item_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_part_sub_return` FOREIGN KEY (`part_sub_return_id`) REFERENCES `material_returns` (`return_id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_part_sub_returned_by` FOREIGN KEY (`part_sub_returned_by`) REFERENCES `sys_user` (`user_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for att_group
CREATE TABLE IF NOT EXISTS `att_group`  (
  `att_group_id` smallint NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `att_group_name` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `asset_group_id` smallint NULL DEFAULT NULL,
  `att_group_supervisor` int NULL DEFAULT NULL,
  `att_group_polygon` polygon NULL,
  `att_group_map_center` point NULL,
  `att_group_map_zoom` tinyint NULL DEFAULT NULL,
  `att_group_normal_start` time NULL DEFAULT NULL,
  `att_group_normal_end` time NULL DEFAULT NULL,
  `att_group_am_start` time NULL DEFAULT NULL,
  `att_group_am_end` time NULL DEFAULT NULL,
  `att_group_pm_start` time NULL DEFAULT NULL,
  `att_group_pm_end` time NULL DEFAULT NULL,
  `att_group_morning_start` time NULL DEFAULT NULL,
  `att_group_morning_end` time NULL DEFAULT NULL,
  `att_group_evening_start` time NULL DEFAULT NULL,
  `att_group_evening_end` time NULL DEFAULT NULL,
  `att_group_night_start` time NULL DEFAULT NULL,
  `att_group_night_end` time NULL DEFAULT NULL,
  `att_group_holiday` enum('Saturday & Sunday','Sunday') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_group_req_week_hours` smallint NULL DEFAULT NULL,
  `att_group_shift_mode` enum('Normal','2 Shifts','3 Shifts') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_group_ot_approver` int NULL DEFAULT NULL,
  `att_group_remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL,
  `att_group_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `att_group_time_updated` timestamp NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  `att_group_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`att_group_id`) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  INDEX `att_group_supervisor`(`att_group_supervisor` ASC) USING BTREE,
  INDEX `att_group_ot_approver`(`att_group_ot_approver` ASC) USING BTREE,
  INDEX `att_group_status`(`att_group_status` ASC) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id` ASC) USING BTREE,
  CONSTRAINT `att_group_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `att_group_ibfk_2` FOREIGN KEY (`att_group_supervisor`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `att_group_ibfk_3` FOREIGN KEY (`att_group_ot_approver`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `att_group_ibfk_4` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- Table structure for att_participant
CREATE TABLE IF NOT EXISTS `att_participant`  (
  `att_participant_id` smallint NOT NULL AUTO_INCREMENT,
  `att_group_id` smallint NOT NULL,
  `user_id` int NOT NULL,
  `att_participant_gf_id` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT '',
  `asset_group_id` smallint NULL DEFAULT NULL,
  `att_type_id` tinyint NULL DEFAULT NULL,
  `att_participant_holiday` enum('Sunday','Saturday & Sunday') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_participant_req_week_hours` smallint NULL DEFAULT NULL,
  `att_participant_shift_mode` enum('Normal','2 Shifts','3 Shifts') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_participant_year_service` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_participant_cidb_card_expiry` date NULL DEFAULT NULL,
  `att_participant_competency` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_participant_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `att_participant_time_updated` timestamp NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  `att_participant_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`att_participant_id`) USING BTREE,
  INDEX `att_group_id`(`att_group_id` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id` ASC) USING BTREE,
  INDEX `att_type_id`(`att_type_id` ASC) USING BTREE,
  CONSTRAINT `att_participant_ibfk_1` FOREIGN KEY (`att_group_id`) REFERENCES `att_group` (`att_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `att_participant_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `att_participant_ibfk_3` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `att_participant_ibfk_4` FOREIGN KEY (`att_type_id`) REFERENCES `att_type` (`att_type_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- Table structure for att_transaction
CREATE TABLE IF NOT EXISTS `att_transaction`  (
  `att_transaction_id` bigint NOT NULL AUTO_INCREMENT,
  `att_transaction_date` date NOT NULL,
  `att_participant_id` smallint NOT NULL,
  `att_group_id` smallint NOT NULL,
  `user_id` int NOT NULL,
  `att_type_id` tinyint NULL DEFAULT NULL,
  `att_transaction_shift_start` timestamp NULL DEFAULT NULL,
  `att_transaction_shift_end` timestamp NULL DEFAULT NULL,
  `att_transaction_time_in` timestamp NULL DEFAULT NULL,
  `att_transaction_time_out` timestamp NULL DEFAULT NULL,
  `att_transaction_location_in` point NULL,
  `att_transaction_location_out` point NULL,
  `att_transaction_result` enum('Present','Absent','Leave','Training') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_transaction_status` enum('Checked In','Checked Out','Ready') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'Ready',
  PRIMARY KEY (`att_transaction_id`) USING BTREE,
  INDEX `att_type_id`(`att_type_id` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  INDEX `att_participant_id`(`att_participant_id` ASC) USING BTREE,
  INDEX `att_group_id`(`att_group_id` ASC) USING BTREE,
  CONSTRAINT `att_transaction_ibfk_1` FOREIGN KEY (`att_type_id`) REFERENCES `att_type` (`att_type_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `att_transaction_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `att_transaction_ibfk_3` FOREIGN KEY (`att_participant_id`) REFERENCES `att_participant` (`att_participant_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `att_transaction_ibfk_4` FOREIGN KEY (`att_group_id`) REFERENCES `att_group` (`att_group_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- Table structure for att_type
CREATE TABLE IF NOT EXISTS `att_type`  (
  `att_type_id` tinyint NOT NULL AUTO_INCREMENT,
  `att_type_name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `att_type_short` varchar(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `att_type_mode` enum('Normal','2 Shifts','3 Shifts','Leave','Training') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_type_color` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_type_color_done` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_type_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`att_type_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- Table structure for cli_client
CREATE TABLE IF NOT EXISTS `cli_client`  (
  `client_id` smallint NOT NULL AUTO_INCREMENT,
  `client_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `client_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `client_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `client_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`client_id`) USING BTREE,
  INDEX `client_status`(`client_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for cli_client_failure_code
CREATE TABLE IF NOT EXISTS `cli_client_failure_code`  (
  `client_failure_code_id` int NOT NULL AUTO_INCREMENT,
  `client_id` smallint NOT NULL,
  `failure_code_id` smallint NOT NULL,
  PRIMARY KEY (`client_failure_code_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for cli_client_severity
CREATE TABLE IF NOT EXISTS `cli_client_severity`  (
  `client_severity_id` int NOT NULL AUTO_INCREMENT,
  `client_id` smallint NOT NULL,
  `severity_id` tinyint NOT NULL,
  `client_severity_hour` smallint NOT NULL DEFAULT 1,
  `client_severity_respond_time` smallint NOT NULL DEFAULT 1,
  PRIMARY KEY (`client_severity_id`) USING BTREE,
  INDEX `client_id`(`client_id` ASC) USING BTREE,
  INDEX `severity_id`(`severity_id` ASC) USING BTREE,
  CONSTRAINT `cli_client_severity_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `cli_client` (`client_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `cli_client_severity_ibfk_3` FOREIGN KEY (`severity_id`) REFERENCES `ref_severity` (`severity_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for cli_contract
CREATE TABLE IF NOT EXISTS `cli_contract`  (
  `contract_id` smallint NOT NULL AUTO_INCREMENT,
  `contract_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `contract_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `contract_date_start` date NOT NULL,
  `contract_date_end` date NOT NULL,
  `site_id` smallint NOT NULL,
  `contract_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `contract_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`contract_id`) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  INDEX `contract_status`(`contract_status` ASC) USING BTREE,
  CONSTRAINT `cli_contract_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for cli_contract_user
CREATE TABLE IF NOT EXISTS `cli_contract_user`  (
  `contract_user_id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `contract_id` smallint NOT NULL,
  `location_code_id` smallint NOT NULL,
  `asset_group_id` smallint NOT NULL,
  PRIMARY KEY (`contract_user_id`) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  INDEX `contract_id`(`contract_id` ASC) USING BTREE,
  INDEX `location_code_id`(`location_code_id` ASC) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id` ASC) USING BTREE,
  CONSTRAINT `cli_contract_user_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `cli_contract_user_ibfk_2` FOREIGN KEY (`contract_id`) REFERENCES `cli_contract` (`contract_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `cli_contract_user_ibfk_3` FOREIGN KEY (`location_code_id`) REFERENCES `cli_location_code` (`location_code_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `cli_contract_user_ibfk_4` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for cli_location_code
CREATE TABLE IF NOT EXISTS `cli_location_code`  (
  `location_code_id` smallint NOT NULL AUTO_INCREMENT,
  `location_code_name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `location_code_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `location_code_type` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `site_id` smallint NOT NULL,
  `location_code_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`location_code_id`) USING BTREE,
  INDEX `cli_location_code_ibfk_1`(`site_id` ASC) USING BTREE,
  CONSTRAINT `cli_location_code_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for cli_site
CREATE TABLE IF NOT EXISTS `cli_site`  (
  `site_id` smallint NOT NULL AUTO_INCREMENT,
  `site_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `site_code` varchar(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `site_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `site_running_no` int NOT NULL DEFAULT 1,
  `site_running_no_wo` int NOT NULL DEFAULT 1,
  `site_running_no_wr` int NOT NULL DEFAULT 1,
  `site_running_no_req` int NOT NULL DEFAULT 1,
  `site_running_no_part_sub` int NOT NULL DEFAULT 1,
  `site_running_no_fca` int NOT NULL DEFAULT 1,
  `site_is_launched` tinyint(1) NOT NULL DEFAULT 0,
  `site_is_manual` tinyint(1) NOT NULL DEFAULT 0,
  `site_is_wr` tinyint(1) NOT NULL DEFAULT 0,
  `site_is_material` tinyint(1) NOT NULL DEFAULT 0,
  `site_is_attendance` tinyint(1) NOT NULL DEFAULT 0,
  `site_is_public` tinyint(1) NOT NULL DEFAULT 0,
  `client_id` smallint NOT NULL,
  `group_id` smallint NOT NULL,
  `site_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `site_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`site_id`) USING BTREE,
  INDEX `client_id`(`client_id` ASC) USING BTREE,
  INDEX `group_id`(`group_id` ASC) USING BTREE,
  CONSTRAINT `cli_site_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `cli_client` (`client_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `cli_site_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for cli_site_manual
CREATE TABLE IF NOT EXISTS `cli_site_manual`  (
  `site_manual_id` smallint NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `site_manual_date` date NOT NULL,
  `site_manual_open0` int NOT NULL DEFAULT 0,
  `site_manual_open1` int NOT NULL DEFAULT 0,
  `site_manual_open2` int NOT NULL DEFAULT 0,
  `site_manual_open3` int NOT NULL DEFAULT 0,
  `site_manual_open4` int NOT NULL DEFAULT 0,
  `site_manual_open5` int NOT NULL DEFAULT 0,
  `site_manual_closed0` int NOT NULL DEFAULT 0,
  `site_manual_closed1` int NOT NULL DEFAULT 0,
  `site_manual_closed2` int NOT NULL DEFAULT 0,
  `site_manual_closed3` int NOT NULL DEFAULT 0,
  `site_manual_closed4` int NOT NULL DEFAULT 0,
  `site_manual_closed5` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`site_manual_id`) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  CONSTRAINT `cli_site_manual_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for cli_site_problem_type
CREATE TABLE IF NOT EXISTS `cli_site_problem_type`  (
  `site_problem_type_id` smallint NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `site_problem_type_name` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `site_problem_type_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`site_problem_type_id`) USING BTREE,
  INDEX `problem_type_status`(`site_problem_type_status` ASC) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  CONSTRAINT `cli_site_problem_type_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for cli_store
CREATE TABLE IF NOT EXISTS `cli_store`  (
  `store_id` smallint NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `store_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `store_desc` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `store_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`store_id`) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  CONSTRAINT `cli_store_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for cli_zone
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

-- Table structure for do
CREATE TABLE IF NOT EXISTS `do`  (
  `do_id` int NOT NULL AUTO_INCREMENT,
  `pr_id` int NULL DEFAULT NULL,
  `site_id` smallint NULL DEFAULT NULL,
  `do_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `do_type` enum('Partial','Normal') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `do_upload` bigint NULL DEFAULT NULL,
  `do_date` date NULL DEFAULT NULL,
  `supplier_id` smallint NULL DEFAULT NULL,
  `supplier_name` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `do_created_by` int NULL DEFAULT NULL,
  `do_received_by` int NULL DEFAULT NULL,
  `do_timestamp` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `do_status` tinyint NULL DEFAULT NULL,
  PRIMARY KEY (`do_id`) USING BTREE,
  INDEX `pr_do_ibfk_1`(`pr_id` ASC) USING BTREE,
  INDEX `pr_do_ibfk_2`(`do_upload` ASC) USING BTREE,
  INDEX `pr_do_ibfk_3`(`site_id` ASC) USING BTREE,
  CONSTRAINT `pr_do_ibfk_1` FOREIGN KEY (`pr_id`) REFERENCES `pr` (`pr_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `pr_do_ibfk_2` FOREIGN KEY (`do_upload`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `pr_do_ibfk_3` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for do_item
CREATE TABLE IF NOT EXISTS `do_item`  (
  `do_item_id` bigint NOT NULL AUTO_INCREMENT,
  `do_id` int NULL DEFAULT NULL,
  `part_id` bigint NULL DEFAULT NULL,
  `do_item_total` smallint NULL DEFAULT NULL,
  `do_item_warranty` tinyint NULL DEFAULT NULL,
  `do_item_validity` date NULL DEFAULT NULL,
  `do_item_cost` decimal(12, 2) NULL DEFAULT NULL,
  `do_item_total_cost` decimal(12, 2) NULL DEFAULT NULL,
  `do_item_timestamp` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`do_item_id`) USING BTREE,
  INDEX `part_id`(`part_id` ASC) USING BTREE,
  INDEX `do_id`(`do_id` ASC) USING BTREE,
  CONSTRAINT `do_item_ibfk_3` FOREIGN KEY (`part_id`) REFERENCES `ast_part` (`part_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `do_item_ibfk_4` FOREIGN KEY (`do_id`) REFERENCES `do` (`do_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for do_upload
CREATE TABLE IF NOT EXISTS `do_upload`  (
  `do_upload_id` int NOT NULL AUTO_INCREMENT,
  `do_id` int NULL DEFAULT NULL,
  `upload_id` bigint NOT NULL,
  `do_upload_timestamp` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`do_upload_id`) USING BTREE,
  INDEX `do_id`(`do_id` ASC) USING BTREE,
  INDEX `upload_id`(`upload_id` ASC) USING BTREE,
  CONSTRAINT `do_upload_ibfk_1` FOREIGN KEY (`do_id`) REFERENCES `do` (`do_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `do_upload_ibfk_2` FOREIGN KEY (`upload_id`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for drawing
CREATE TABLE IF NOT EXISTS `drawing`  (
  `drawing_id` bigint NOT NULL AUTO_INCREMENT,
  `drawing_title` varchar(250) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `drawing_id_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `drawing_version` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_group_id` smallint NULL DEFAULT NULL,
  `drawing_block` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `drawing_level` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `drawing_permission_level` tinyint(1) NULL DEFAULT NULL,
  `drawing_published_by` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `drawing_published_date` date NULL DEFAULT NULL,
  `drawing_dwg` bigint NULL DEFAULT NULL,
  `drawing_pdf` bigint NULL DEFAULT NULL,
  `drawing_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `drawing_created_by` int NULL DEFAULT NULL,
  `drawing_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `drawing_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`drawing_id`) USING BTREE,
  INDEX `drawing_published_by`(`drawing_published_by` ASC) USING BTREE,
  INDEX `drawing_dwg`(`drawing_dwg` ASC) USING BTREE,
  INDEX `drawing_pdf`(`drawing_pdf` ASC) USING BTREE,
  INDEX `drawing_created_by`(`drawing_created_by` ASC) USING BTREE,
  INDEX `drawing_status`(`drawing_status` ASC) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id` ASC) USING BTREE,
  CONSTRAINT `drawing_ibfk_2` FOREIGN KEY (`drawing_dwg`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `drawing_ibfk_3` FOREIGN KEY (`drawing_pdf`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `drawing_ibfk_4` FOREIGN KEY (`drawing_created_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `drawing_ibfk_5` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for email_log
CREATE TABLE IF NOT EXISTS `email_log`  (
  `email_log_id` bigint NOT NULL AUTO_INCREMENT,
  `email_template_id` smallint NOT NULL,
  `email_address` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_title` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_html` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_id` int NULL DEFAULT NULL,
  `email_attachment` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `email_filename` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `email_id` bigint NULL DEFAULT NULL,
  `email_retry_no` tinyint NOT NULL DEFAULT 0,
  `email_time_set` timestamp NOT NULL DEFAULT current_timestamp,
  `email_time_sent` timestamp NOT NULL DEFAULT current_timestamp,
  `email_log_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`email_log_id`) USING BTREE,
  INDEX `email_send_template_id`(`email_template_id` ASC) USING BTREE,
  INDEX `email_send_time_set`(`email_time_sent` ASC) USING BTREE,
  INDEX `email_send_ibfk_2`(`user_id` ASC) USING BTREE,
  CONSTRAINT `email_log_ibfk_1` FOREIGN KEY (`email_template_id`) REFERENCES `email_template` (`email_template_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for email_parameter
CREATE TABLE IF NOT EXISTS `email_parameter`  (
  `email_param_id` bigint NOT NULL AUTO_INCREMENT,
  `email_template_id` smallint NOT NULL,
  `email_param_code` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_param_desc` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`email_param_id`) USING BTREE,
  INDEX `email_template_id`(`email_template_id` ASC) USING BTREE,
  CONSTRAINT `email_parameter_ibfk_1` FOREIGN KEY (`email_template_id`) REFERENCES `email_template` (`email_template_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for email_send
CREATE TABLE IF NOT EXISTS `email_send`  (
  `email_id` bigint NOT NULL AUTO_INCREMENT,
  `email_template_id` smallint NOT NULL,
  `email_address` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_title` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_html` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_id` int NULL DEFAULT NULL,
  `email_retry_no` tinyint NOT NULL DEFAULT 0,
  `email_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `email_time_set` timestamp NOT NULL DEFAULT current_timestamp,
  `email_attachment` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `email_filename` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`email_id`) USING BTREE,
  INDEX `email_send_template_id`(`email_template_id` ASC) USING BTREE,
  INDEX `email_send_time_set`(`email_time_set` ASC) USING BTREE,
  INDEX `email_send_ibfk_2`(`user_id` ASC) USING BTREE,
  CONSTRAINT `email_send_ibfk_1` FOREIGN KEY (`email_template_id`) REFERENCES `email_template` (`email_template_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `email_send_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for email_template
CREATE TABLE IF NOT EXISTS `email_template`  (
  `email_template_id` smallint NOT NULL AUTO_INCREMENT,
  `email_template_name` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_template_desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `email_template_title` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_template_html` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_template_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`email_template_id`) USING BTREE,
  INDEX `email_template_status`(`email_template_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for fca_defect_category
CREATE TABLE IF NOT EXISTS `fca_defect_category`  (
  `fca_defect_category_id` smallint NOT NULL AUTO_INCREMENT,
  `fca_defect_category_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `fca_defect_category_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`fca_defect_category_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- Table structure for fca_defect_category_site
CREATE TABLE IF NOT EXISTS `fca_defect_category_site`  (
  `fca_defect_category_site_id` int NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `fca_defect_category_id` smallint NOT NULL,
  PRIMARY KEY (`fca_defect_category_site_id`) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  INDEX `fca_defect_category_id`(`fca_defect_category_id` ASC) USING BTREE,
  CONSTRAINT `fca_defect_category_site_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_defect_category_site_ibfk_2` FOREIGN KEY (`fca_defect_category_id`) REFERENCES `fca_defect_category` (`fca_defect_category_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- Table structure for fca_report
CREATE TABLE IF NOT EXISTS `fca_report`  (
  `fca_report_id` smallint NOT NULL AUTO_INCREMENT,
  `fca_report_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `fca_report_date_from` date NULL DEFAULT NULL,
  `fca_report_date_to` date NULL DEFAULT NULL,
  `site_id` smallint NULL DEFAULT NULL,
  `asset_group_id` smallint NULL DEFAULT NULL,
  `fca_report_exclude_list` tinyint(1) NOT NULL DEFAULT 0,
  `fca_report_sort_by` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `fca_report_total` int NULL DEFAULT NULL,
  `pdf_id` bigint NULL DEFAULT NULL,
  `fca_report_created_by` int NOT NULL,
  `fca_report_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `fca_report_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`fca_report_id`) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id` ASC) USING BTREE,
  INDEX `pdf_id`(`pdf_id` ASC) USING BTREE,
  CONSTRAINT `fca_report_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_report_ibfk_2` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_report_ibfk_3` FOREIGN KEY (`pdf_id`) REFERENCES `sys_pdf` (`pdf_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- Table structure for fca_task
CREATE TABLE IF NOT EXISTS `fca_task`  (
  `fca_task_id` int NOT NULL AUTO_INCREMENT,
  `fca_task_no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `transaction_id` bigint NOT NULL,
  `site_id` smallint NULL DEFAULT NULL,
  `fca_zone_id` smallint NULL DEFAULT NULL,
  `fca_task_area` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `asset_group_id` smallint NULL DEFAULT NULL,
  `fca_task_asset_no` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `fca_task_asset_evaluated` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `fca_task_defect_item` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `fca_defect_category_id` smallint NULL DEFAULT NULL,
  `fca_task_condition_scale` tinyint(1) NULL DEFAULT NULL,
  `fca_task_evaluation_type` tinyint(1) NULL DEFAULT NULL,
  `fca_task_image_1` bigint NULL DEFAULT NULL,
  `fca_task_image_2` bigint NULL DEFAULT NULL,
  `fca_task_image_rectify_1` bigint NULL DEFAULT NULL,
  `fca_task_image_rectify_2` bigint NULL DEFAULT NULL,
  `fca_task_observation` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL,
  `fca_task_recommendation` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL,
  `fca_task_validation` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL,
  `fca_task_exclude_report` tinyint(1) NOT NULL DEFAULT 0,
  `fca_task_removed` tinyint(1) NOT NULL DEFAULT 0,
  `fca_task_created_by` int NULL DEFAULT NULL,
  `fca_task_recommend_by` int NULL DEFAULT NULL,
  `fca_task_validate_by` int NULL DEFAULT NULL,
  `fca_task_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `fca_task_time_recommended` timestamp NULL DEFAULT NULL,
  `fca_task_time_validated` timestamp NULL DEFAULT NULL,
  `fca_task_time_completed` timestamp NULL DEFAULT NULL,
  `fca_task_status` tinyint NOT NULL DEFAULT 5,
  PRIMARY KEY (`fca_task_id`) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  INDEX `transaction_id`(`transaction_id` ASC) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id` ASC) USING BTREE,
  INDEX `fca_defect_category_id`(`fca_defect_category_id` ASC) USING BTREE,
  INDEX `fca_task_image_1`(`fca_task_image_1` ASC) USING BTREE,
  INDEX `fca_task_image_2`(`fca_task_image_2` ASC) USING BTREE,
  INDEX `fca_zone_id`(`fca_zone_id` ASC) USING BTREE,
  INDEX `fca_task_image_rectify_1`(`fca_task_image_rectify_1` ASC) USING BTREE,
  INDEX `fca_task_image_rectify_2`(`fca_task_image_rectify_2` ASC) USING BTREE,
  INDEX `fca_task_removed`(`fca_task_removed` ASC) USING BTREE,
  INDEX `fca_task_exclude_report`(`fca_task_exclude_report` ASC) USING BTREE,
  CONSTRAINT `fca_task_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_task_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `wfl_transaction` (`transaction_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_task_ibfk_3` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_task_ibfk_4` FOREIGN KEY (`fca_defect_category_id`) REFERENCES `fca_defect_category` (`fca_defect_category_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_task_ibfk_5` FOREIGN KEY (`fca_task_image_1`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_task_ibfk_6` FOREIGN KEY (`fca_task_image_2`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_task_ibfk_7` FOREIGN KEY (`fca_zone_id`) REFERENCES `fca_zone` (`fca_zone_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_task_ibfk_8` FOREIGN KEY (`fca_task_image_rectify_1`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_task_ibfk_9` FOREIGN KEY (`fca_task_image_rectify_2`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- Table structure for fca_task_section
CREATE TABLE IF NOT EXISTS `fca_task_section`  (
  `fca_task_section_id` bigint NOT NULL AUTO_INCREMENT,
  `fca_task_section_code` varchar(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `fca_task_section_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `fca_task_id` int NOT NULL,
  `fca_task_section_status` tinyint NOT NULL DEFAULT 17,
  PRIMARY KEY (`fca_task_section_id`) USING BTREE,
  INDEX `fca_task_id`(`fca_task_id` ASC) USING BTREE,
  CONSTRAINT `fca_task_section_ibfk_1` FOREIGN KEY (`fca_task_id`) REFERENCES `fca_task` (`fca_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- Table structure for fca_zone
CREATE TABLE IF NOT EXISTS `fca_zone`  (
  `fca_zone_id` smallint NOT NULL AUTO_INCREMENT,
  `fca_zone_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `site_id` smallint NOT NULL,
  `fca_zone_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`fca_zone_id`) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  CONSTRAINT `fca_zone_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- Table structure for gmi_config
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

-- Table structure for gmi_monthly
CREATE TABLE IF NOT EXISTS `gmi_monthly`  (
  `gmi_id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `site_id` smallint NULL DEFAULT NULL,
  `gmi_year` smallint NULL DEFAULT NULL,
  `gmi_month` tinyint NULL DEFAULT NULL,
  `gmi_ppm_tier_name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `gmi_ppm_tier_point` decimal(3, 1) NULL DEFAULT NULL,
  `gmi_ppm_total` smallint NULL DEFAULT NULL,
  `gmi_ppm_completed` smallint NULL DEFAULT NULL,
  `gmi_ppm_on_time` smallint NULL DEFAULT NULL,
  `gmi_ppm_late` smallint NULL DEFAULT NULL,
  `gmi_ppm_within` smallint NULL DEFAULT NULL,
  `gmi_ppm_rework` smallint NULL DEFAULT NULL,
  `gmi_ppm_assist` smallint NULL DEFAULT NULL,
  `gmi_wo_tier_name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `gmi_wo_tier_point` decimal(3, 1) NULL DEFAULT NULL,
  `gmi_wo_total` smallint NULL DEFAULT NULL,
  `gmi_wo_completed` smallint NULL DEFAULT NULL,
  `gmi_wo_on_time` smallint NULL DEFAULT NULL,
  `gmi_wo_late` smallint NULL DEFAULT NULL,
  `gmi_wo_rework` smallint NULL DEFAULT NULL,
  `gmi_wo_self_finding` smallint NULL DEFAULT NULL,
  `gmi_wo_assist` smallint NULL DEFAULT NULL,
  `gmi_mbv` smallint NULL DEFAULT NULL,
  `gmi_tier_point` tinyint(1) NULL DEFAULT NULL,
  `gmi_point_completed` int NULL DEFAULT NULL,
  `gmi_point_on_time` int NULL DEFAULT NULL,
  `gmi_point_late` int NULL DEFAULT NULL,
  `gmi_point_rework` int NULL DEFAULT NULL,
  `gmi_point_self_finding` int NULL DEFAULT NULL,
  `gmi_point_total` int NULL DEFAULT NULL,
  `gmi_productivity_level` decimal(6, 2) NULL DEFAULT NULL,
  `gmi_productivity_deduction` decimal(6, 2) NULL DEFAULT NULL,
  `gmi_point_less_productive` int NULL DEFAULT NULL,
  `gmi_point_before_minus` int NULL DEFAULT NULL,
  `gmi_point_after_minus` int NULL DEFAULT NULL,
  PRIMARY KEY (`gmi_id`) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  CONSTRAINT `gmi_monthly_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `gmi_monthly_ibfk_2` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- Table structure for gmi_weekly
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

-- Table structure for inventory_logs
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

-- Table structure for kpi
CREATE TABLE IF NOT EXISTS `kpi`  (
  `kpi_id` smallint NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `kpi_info_version` tinyint NOT NULL DEFAULT 1,
  `kpi_year` smallint NOT NULL,
  `kpi_month` tinyint NOT NULL,
  `kpi_portion_perc` tinyint NOT NULL DEFAULT 5,
  `kpi_portion_total_fee` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `kpi_last_update` timestamp NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`kpi_id`) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  CONSTRAINT `kpi_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for kpi_info
CREATE TABLE IF NOT EXISTS `kpi_info`  (
  `kpi_info_id` smallint NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `kpi_info_version` tinyint NOT NULL DEFAULT 1,
  `kpi_info_category` tinyint NOT NULL,
  `kpi_info_service_description` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kpi_info_performance_measure` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kpi_info_target_value` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `kpi_info_source_of_data` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kpi_info_weightage` decimal(3, 3) NOT NULL DEFAULT 0.000,
  `kpi_info_ncp` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kpi_info_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `kpi_info_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`kpi_info_id`) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  CONSTRAINT `kpi_info_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for kpi_ppns
CREATE TABLE IF NOT EXISTS `kpi_ppns`  (
  `kpi_ppns_id` int NOT NULL AUTO_INCREMENT,
  `kpi_id` smallint NOT NULL,
  `kpi_ppns_category` tinyint NULL DEFAULT NULL,
  `kpi_ppns_param_1` decimal(12, 2) NULL DEFAULT NULL,
  `kpi_ppns_param_2` decimal(12, 2) NULL DEFAULT NULL,
  `kpi_ppns_param_3` decimal(12, 2) NULL DEFAULT NULL,
  `kpi_ppns_param_4` decimal(12, 2) NULL DEFAULT NULL,
  `kpi_ppns_param_5` decimal(12, 2) NULL DEFAULT NULL,
  `kpi_ppns_param_6` decimal(12, 2) NULL DEFAULT NULL,
  `kpi_ppns_param_7` decimal(12, 2) NULL DEFAULT NULL,
  `kpi_ppns_param_8` decimal(12, 2) NULL DEFAULT NULL,
  `kpi_ppns_param_9` decimal(12, 2) NULL DEFAULT NULL,
  `kpi_ppns_weightage` decimal(3, 3) NOT NULL DEFAULT 0.000,
  `kpi_ppns_ncp` decimal(6, 2) NOT NULL DEFAULT 0.00,
  `kpi_ppns_time_update` timestamp NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`kpi_ppns_id`) USING BTREE,
  INDEX `kpi_id`(`kpi_id` ASC) USING BTREE,
  CONSTRAINT `kpi_ppns_ibfk_1` FOREIGN KEY (`kpi_id`) REFERENCES `kpi` (`kpi_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for lic_license
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

-- Table structure for material_returns
CREATE TABLE IF NOT EXISTS `material_returns`  (
  `return_id` bigint NOT NULL AUTO_INCREMENT,
  `wo_task_parts_id` bigint NOT NULL COMMENT 'Reference to original material collection',
  `part_id` bigint NOT NULL COMMENT 'Reference to parts table',
  `technician_user_id` int NOT NULL COMMENT 'User who requested the return',
  `storekeeper_user_id` int NULL DEFAULT NULL COMMENT 'User who confirmed the return',
  `quantity_returned` int NOT NULL COMMENT 'Quantity being returned (supports partial returns)',
  `return_status` enum('pending','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'pending' COMMENT 'Return workflow status',
  `return_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Reason from dropdown: unused_excess, wrong_part, damaged, other',
  `return_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT 'Optional free text remarks',
  `return_request_date` datetime NOT NULL COMMENT 'When technician submitted return',
  `return_confirmed_date` datetime NULL DEFAULT NULL COMMENT 'When storekeeper confirmed receipt',
  `return_deadline_date` datetime NULL DEFAULT NULL COMMENT 'Optional deadline for return (not enforced)',
  `created_at` timestamp NULL DEFAULT current_timestamp,
  `updated_at` timestamp NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`return_id`) USING BTREE,
  INDEX `idx_technician`(`technician_user_id` ASC) USING BTREE,
  INDEX `idx_storekeeper`(`storekeeper_user_id` ASC) USING BTREE,
  INDEX `idx_status`(`return_status` ASC) USING BTREE,
  INDEX `idx_request_date`(`return_request_date` ASC) USING BTREE,
  INDEX `idx_wo_task_parts`(`wo_task_parts_id` ASC) USING BTREE,
  INDEX `idx_part_id`(`part_id` ASC) USING BTREE,
  CONSTRAINT `material_returns_ibfk_1` FOREIGN KEY (`wo_task_parts_id`) REFERENCES `wo_task_parts` (`wo_task_parts_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `material_returns_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `ast_part` (`part_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `material_returns_ibfk_3` FOREIGN KEY (`technician_user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `material_returns_ibfk_4` FOREIGN KEY (`storekeeper_user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Material return requests and confirmations' ROW_FORMAT = Dynamic;

-- Table structure for noti_log
CREATE TABLE IF NOT EXISTS `noti_log`  (
  `noti_log_id` bigint NOT NULL AUTO_INCREMENT,
  `noti_text_id` smallint NULL DEFAULT NULL,
  `noti_to` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `noti_title` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `noti_html` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `user_id` int NULL DEFAULT NULL,
  `noti_id` bigint NULL DEFAULT NULL,
  `noti_log_time_sent` timestamp NOT NULL DEFAULT current_timestamp,
  `noti_log_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`noti_log_id`) USING BTREE,
  INDEX `noti_log_status`(`noti_log_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for noti_parameter
CREATE TABLE IF NOT EXISTS `noti_parameter`  (
  `noti_param_id` bigint NOT NULL AUTO_INCREMENT,
  `noti_text_id` smallint NOT NULL,
  `noti_param_code` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `noti_param_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`noti_param_id`) USING BTREE,
  INDEX `noti_text_id`(`noti_text_id` ASC) USING BTREE,
  CONSTRAINT `noti_parameter_ibfk_1` FOREIGN KEY (`noti_text_id`) REFERENCES `noti_text` (`noti_text_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for noti_send
CREATE TABLE IF NOT EXISTS `noti_send`  (
  `noti_id` bigint NOT NULL AUTO_INCREMENT,
  `noti_text_id` smallint NULL DEFAULT NULL,
  `noti_to` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `noti_title` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `noti_html` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `user_id` int NULL DEFAULT NULL,
  `noti_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`noti_id`) USING BTREE,
  INDEX `noti_text_id`(`noti_text_id` ASC) USING BTREE,
  CONSTRAINT `noti_send_ibfk_1` FOREIGN KEY (`noti_text_id`) REFERENCES `noti_text` (`noti_text_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for noti_text
CREATE TABLE IF NOT EXISTS `noti_text`  (
  `noti_text_id` smallint NOT NULL AUTO_INCREMENT,
  `noti_text_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `noti_text_title` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `noti_text_html` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `noti_text_status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`noti_text_id`) USING BTREE,
  INDEX `noti_text_status`(`noti_text_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for noti_web
CREATE TABLE IF NOT EXISTS `noti_web`  (
  `noti_web_id` bigint NOT NULL AUTO_INCREMENT,
  `noti_web_type` tinyint NOT NULL COMMENT '1=WO assign\n2=WO verify',
  `user_id` int NOT NULL,
  `noti_web_title` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `noti_web_text` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `noti_web_icon` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `noti_web_color` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `noti_web_link` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `nav_id` tinyint NULL DEFAULT NULL,
  `nav_second_id` tinyint NULL DEFAULT NULL,
  `noti_web_timestamp` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`noti_web_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- Table structure for ppm
CREATE TABLE IF NOT EXISTS `ppm`  (
  `ppm_id` bigint NOT NULL AUTO_INCREMENT,
  `ppm_task_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_issue_no` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_name` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_set_id` smallint NULL DEFAULT NULL,
  `ppm_is_group` tinyint(1) NULL DEFAULT NULL,
  `ppm_date_start` date NULL DEFAULT NULL,
  `asset_id` bigint NULL DEFAULT NULL,
  `asset_type_id` smallint NULL DEFAULT NULL,
  `checklist_id` int NULL DEFAULT NULL,
  `ppm_frequency` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `contract_id` smallint NOT NULL,
  `ppm_group_id` smallint NULL DEFAULT NULL,
  `ppm_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `ppm_created_by` int NULL DEFAULT NULL,
  `ppm_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `ppm_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`ppm_id`) USING BTREE,
  INDEX `asset_id`(`asset_id` ASC) USING BTREE,
  INDEX `ppm_status`(`ppm_status` ASC) USING BTREE,
  INDEX `contract_id`(`contract_id` ASC) USING BTREE,
  INDEX `checklist_id`(`checklist_id` ASC) USING BTREE,
  INDEX `ppm_created_by`(`ppm_created_by` ASC) USING BTREE,
  INDEX `ppm_group_id`(`ppm_group_id` ASC) USING BTREE,
  INDEX `ppm_ibfk_6`(`ppm_set_id` ASC) USING BTREE,
  CONSTRAINT `ppm_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `ast_asset` (`asset_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_ibfk_2` FOREIGN KEY (`contract_id`) REFERENCES `cli_contract` (`contract_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_ibfk_3` FOREIGN KEY (`checklist_id`) REFERENCES `ppm_checklist` (`checklist_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_ibfk_4` FOREIGN KEY (`ppm_created_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_ibfk_5` FOREIGN KEY (`ppm_group_id`) REFERENCES `ppm_group` (`ppm_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_ibfk_6` FOREIGN KEY (`ppm_set_id`) REFERENCES `ppm_set` (`ppm_set_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ppm_asset
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

-- Table structure for ppm_checklist
CREATE TABLE IF NOT EXISTS `ppm_checklist`  (
  `checklist_id` int NOT NULL AUTO_INCREMENT,
  `checklist_type` tinyint(1) NOT NULL DEFAULT 1,
  `checklist_name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `checklist_document_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `checklist_issue_no` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `checklist_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `checklist_guideline` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `asset_type_id` smallint NULL DEFAULT NULL,
  `pdf_id` bigint NULL DEFAULT NULL,
  `checklist_min_exec_time` time NULL DEFAULT NULL,
  `checklist_max_exec_time` time NULL DEFAULT NULL,
  `checklist_max_assistant` tinyint NULL DEFAULT NULL,
  `checklist_time_registered` timestamp NULL DEFAULT NULL,
  `checklist_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `checklist_registered_by` int NULL DEFAULT NULL,
  `checklist_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`checklist_id`) USING BTREE,
  INDEX `asset_type_id`(`asset_type_id` ASC) USING BTREE,
  INDEX `checklist_status`(`checklist_status` ASC) USING BTREE,
  INDEX `checklist_registered_by`(`checklist_registered_by` ASC) USING BTREE,
  INDEX `pdf_id`(`pdf_id` ASC) USING BTREE,
  CONSTRAINT `ppm_checklist_ibfk_3` FOREIGN KEY (`asset_type_id`) REFERENCES `ast_asset_type` (`asset_type_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_checklist_ibfk_6` FOREIGN KEY (`checklist_registered_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_checklist_ibfk_7` FOREIGN KEY (`pdf_id`) REFERENCES `sys_pdf` (`pdf_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ppm_checklist_qual
CREATE TABLE IF NOT EXISTS `ppm_checklist_qual`  (
  `checklist_qual_id` bigint NOT NULL AUTO_INCREMENT,
  `checklist_qual_desc` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `checklist_qual_numb` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `frequency_id` tinyint NULL DEFAULT NULL,
  `checklist_id` int NOT NULL,
  `checklist_qual_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`checklist_qual_id`) USING BTREE,
  INDEX `checklist_id`(`checklist_id` ASC) USING BTREE,
  INDEX `frequency_id`(`frequency_id` ASC) USING BTREE,
  INDEX `checklist_qual_numb`(`checklist_qual_numb` ASC) USING BTREE,
  INDEX `checklist_qual_status`(`checklist_qual_status` ASC) USING BTREE,
  CONSTRAINT `ppm_checklist_qual_ibfk_1` FOREIGN KEY (`checklist_id`) REFERENCES `ppm_checklist` (`checklist_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_checklist_qual_ibfk_2` FOREIGN KEY (`frequency_id`) REFERENCES `ppm_frequency` (`frequency_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ppm_checklist_quan
CREATE TABLE IF NOT EXISTS `ppm_checklist_quan`  (
  `checklist_quan_id` bigint NOT NULL AUTO_INCREMENT,
  `checklist_quan_desc` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL,
  `checklist_quan_numb` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `checklist_quan_unit` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `checklist_quan_set_values` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `frequency_id` tinyint NULL DEFAULT NULL,
  `checklist_id` int NOT NULL,
  `checklist_quan_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`checklist_quan_id`) USING BTREE,
  INDEX `checklist_id`(`checklist_id` ASC) USING BTREE,
  INDEX `frequency_id`(`frequency_id` ASC) USING BTREE,
  INDEX `checklist_quan_numb`(`checklist_quan_numb` ASC) USING BTREE,
  CONSTRAINT `ppm_checklist_quan_ibfk_1` FOREIGN KEY (`checklist_id`) REFERENCES `ppm_checklist` (`checklist_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_checklist_quan_ibfk_2` FOREIGN KEY (`frequency_id`) REFERENCES `ppm_frequency` (`frequency_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ppm_frequency
CREATE TABLE IF NOT EXISTS `ppm_frequency`  (
  `frequency_id` tinyint NOT NULL AUTO_INCREMENT,
  `frequency_name` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `frequency_code` varchar(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `frequency_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `frequency_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`frequency_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ppm_group
CREATE TABLE IF NOT EXISTS `ppm_group`  (
  `ppm_group_id` smallint NOT NULL AUTO_INCREMENT,
  `ppm_group_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `site_id` smallint NOT NULL,
  `role_id` tinyint NOT NULL,
  `ppm_group_report_to` smallint NULL DEFAULT NULL,
  `ppm_group_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`ppm_group_id`) USING BTREE,
  INDEX `role_id`(`role_id` ASC) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  INDEX `ppm_group_status`(`ppm_group_status` ASC) USING BTREE,
  INDEX `ppm_report_to`(`ppm_group_report_to` ASC) USING BTREE,
  CONSTRAINT `ppm_group_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_group_ibfk_2` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_group_ibfk_3` FOREIGN KEY (`ppm_group_report_to`) REFERENCES `ppm_group` (`ppm_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ppm_group_user
CREATE TABLE IF NOT EXISTS `ppm_group_user`  (
  `ppm_group_user_id` int NOT NULL AUTO_INCREMENT,
  `ppm_group_id` smallint NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`ppm_group_user_id`) USING BTREE,
  INDEX `ppm_group_id`(`ppm_group_id` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  CONSTRAINT `ppm_group_user_ibfk_1` FOREIGN KEY (`ppm_group_id`) REFERENCES `ppm_group` (`ppm_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_group_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ppm_offline_sync_log
CREATE TABLE IF NOT EXISTS `ppm_offline_sync_log`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `ppm_task_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'PPM Task ID being synced',
  `sync_timestamp` datetime NOT NULL COMMENT 'Client-provided sync timestamp for idempotency',
  `device_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mobile device identifier',
  `user_id` int NOT NULL COMMENT 'User performing the sync',
  `total_actions` int NOT NULL DEFAULT 0 COMMENT 'Total number of actions in batch',
  `success_count` int NOT NULL DEFAULT 0 COMMENT 'Number of successful actions',
  `failed_count` int NOT NULL DEFAULT 0 COMMENT 'Number of failed actions',
  `request_payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT 'Full JSON request body',
  `response_payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT 'Full JSON response body',
  `created_at` datetime NOT NULL DEFAULT current_timestamp COMMENT 'Server timestamp when sync was processed',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_sync`(`ppm_task_id` ASC, `sync_timestamp` ASC, `device_id` ASC) USING BTREE,
  INDEX `idx_ppm_task`(`ppm_task_id` ASC) USING BTREE,
  INDEX `idx_user`(`user_id` ASC) USING BTREE,
  INDEX `idx_created`(`created_at` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Tracks PPM offline batch sync attempts for idempotency' ROW_FORMAT = Dynamic;

-- Table structure for ppm_set
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

-- Table structure for ppm_set_asset
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

-- Table structure for ppm_task
CREATE TABLE IF NOT EXISTS `ppm_task`  (
  `ppm_task_id` bigint NOT NULL AUTO_INCREMENT,
  `ppm_task_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_schedule_date` date NULL DEFAULT NULL,
  `ppm_task_start_date` date NULL DEFAULT NULL,
  `ppm_id` bigint NOT NULL,
  `ppm_task_guideline` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `ppm_task_is_parts` tinyint(1) NULL DEFAULT NULL,
  `ppm_task_is_additional_report` tinyint(1) NULL DEFAULT NULL,
  `ppm_task_refer_to` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `transaction_id` bigint NOT NULL,
  `pdf_id` bigint NULL DEFAULT NULL,
  `ppm_task_is_scheduled` tinyint(1) NOT NULL DEFAULT 2,
  `ppm_task_assigned_to` int NULL DEFAULT NULL,
  `ppm_task_min_exec_time` time NULL DEFAULT NULL,
  `ppm_task_max_exec_time` time NULL DEFAULT NULL,
  `ppm_task_max_assistant` tinyint(1) NULL DEFAULT NULL,
  `ppm_task_is_group_executed` tinyint(1) NOT NULL DEFAULT 0,
  `ppm_task_serviced_by` int NULL DEFAULT NULL,
  `ppm_task_checked_by` int NULL DEFAULT NULL,
  `ppm_task_verified_by` int NULL DEFAULT NULL,
  `ppm_task_time_start` timestamp NULL DEFAULT NULL,
  `ppm_task_time_serviced` timestamp NULL DEFAULT NULL,
  `ppm_task_time_checked` timestamp NULL DEFAULT NULL,
  `ppm_task_time_verified` timestamp NULL DEFAULT NULL,
  `ppm_task_completed_offline` tinyint(1) NULL DEFAULT 0 COMMENT '1 if task was completed offline',
  `ppm_task_time_assigned` timestamp NOT NULL DEFAULT current_timestamp,
  `ppm_task_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`ppm_task_id`) USING BTREE,
  INDEX `ppm_id`(`ppm_id` ASC) USING BTREE,
  INDEX `ppm_task_status`(`ppm_task_status` ASC) USING BTREE,
  INDEX `ppm_submitted_by`(`ppm_task_serviced_by` ASC) USING BTREE,
  INDEX `ppm_task_checked_by`(`ppm_task_checked_by` ASC) USING BTREE,
  INDEX `ppm_task_verified_by`(`ppm_task_verified_by` ASC) USING BTREE,
  INDEX `transaction_id`(`transaction_id` ASC) USING BTREE,
  INDEX `ppm_task_assigned_to`(`ppm_task_assigned_to` ASC) USING BTREE,
  INDEX `pdf_id`(`pdf_id` ASC) USING BTREE,
  INDEX `ppm_task_schedule_date`(`ppm_task_schedule_date` ASC) USING BTREE,
  INDEX `ppm_task_start_date`(`ppm_task_start_date` ASC) USING BTREE,
  INDEX `idx_ppm_task_no`(`ppm_task_no` ASC) USING BTREE,
  INDEX `idx_ppm_task_status`(`ppm_task_status` ASC) USING BTREE,
  INDEX `idx_ppm_task_assigned`(`ppm_task_assigned_to` ASC) USING BTREE,
  CONSTRAINT `ppm_task_ibfk_1` FOREIGN KEY (`ppm_id`) REFERENCES `ppm` (`ppm_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_ibfk_2` FOREIGN KEY (`ppm_task_serviced_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_ibfk_3` FOREIGN KEY (`ppm_task_checked_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_ibfk_4` FOREIGN KEY (`ppm_task_verified_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_ibfk_6` FOREIGN KEY (`ppm_task_assigned_to`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_ibfk_7` FOREIGN KEY (`pdf_id`) REFERENCES `sys_pdf` (`pdf_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ppm_task_assist
CREATE TABLE IF NOT EXISTS `ppm_task_assist`  (
  `ppm_task_assist_id` bigint NOT NULL AUTO_INCREMENT,
  `ppm_task_id` bigint NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`ppm_task_assist_id`) USING BTREE,
  INDEX `ppm_task_id`(`ppm_task_id` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  CONSTRAINT `ppm_task_assist_ibfk_1` FOREIGN KEY (`ppm_task_id`) REFERENCES `ppm_task` (`ppm_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_assist_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- Table structure for ppm_task_frequency
CREATE TABLE IF NOT EXISTS `ppm_task_frequency`  (
  `ppm_task_freq_id` bigint NOT NULL AUTO_INCREMENT,
  `ppm_task_id` bigint NOT NULL,
  `frequency_id` tinyint NOT NULL,
  PRIMARY KEY (`ppm_task_freq_id`) USING BTREE,
  INDEX `ppm_task_id`(`ppm_task_id` ASC) USING BTREE,
  INDEX `frequency_id`(`frequency_id` ASC) USING BTREE,
  CONSTRAINT `ppm_task_frequency_ibfk_1` FOREIGN KEY (`ppm_task_id`) REFERENCES `ppm_task` (`ppm_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_frequency_ibfk_2` FOREIGN KEY (`frequency_id`) REFERENCES `ppm_frequency` (`frequency_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ppm_task_parts
CREATE TABLE IF NOT EXISTS `ppm_task_parts`  (
  `ppm_task_parts_id` bigint NOT NULL AUTO_INCREMENT,
  `ppm_task_parts_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `ppm_task_id` bigint NOT NULL,
  PRIMARY KEY (`ppm_task_parts_id`) USING BTREE,
  INDEX `ppm_task_id`(`ppm_task_id` ASC) USING BTREE,
  CONSTRAINT `ppm_task_parts_ibfk_1` FOREIGN KEY (`ppm_task_id`) REFERENCES `ppm_task` (`ppm_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ppm_task_qual
CREATE TABLE IF NOT EXISTS `ppm_task_qual`  (
  `ppm_task_qual_id` bigint NOT NULL AUTO_INCREMENT,
  `ppm_task_qual_numb` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_qual_desc` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `frequency_id` tinyint NULL DEFAULT NULL,
  `ppm_task_qual_result` tinyint(1) NULL DEFAULT NULL COMMENT '0=fail, 1=pass, 2=N/A',
  `ppm_task_qual_remark` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_id` bigint NOT NULL,
  `checklist_qual_id` bigint NOT NULL,
  PRIMARY KEY (`ppm_task_qual_id`) USING BTREE,
  INDEX `ppm_task_id`(`ppm_task_id` ASC) USING BTREE,
  INDEX `checklist_qual_id`(`checklist_qual_id` ASC) USING BTREE,
  INDEX `frequency_id`(`frequency_id` ASC) USING BTREE,
  CONSTRAINT `ppm_task_qual_ibfk_1` FOREIGN KEY (`ppm_task_id`) REFERENCES `ppm_task` (`ppm_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_qual_ibfk_3` FOREIGN KEY (`frequency_id`) REFERENCES `ppm_frequency` (`frequency_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ppm_task_quan
CREATE TABLE IF NOT EXISTS `ppm_task_quan`  (
  `ppm_task_quan_id` bigint NOT NULL AUTO_INCREMENT,
  `ppm_task_quan_numb` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_quan_desc` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `frequency_id` tinyint NULL DEFAULT NULL,
  `ppm_task_quan_unit` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_quan_set_values` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_quan_measured_values` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_quan_limit` varchar(100) CHARACTER SET latin2 COLLATE latin2_general_ci NULL DEFAULT NULL,
  `ppm_task_quan_result` tinyint(1) NULL DEFAULT NULL COMMENT '0=fail, 1=pass, 2=N/A',
  `ppm_task_quan_remark` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_id` bigint NOT NULL,
  `checklist_quan_id` bigint NOT NULL,
  PRIMARY KEY (`ppm_task_quan_id`) USING BTREE,
  INDEX `ppm_task_id`(`ppm_task_id` ASC) USING BTREE,
  INDEX `checklist_quan_id`(`checklist_quan_id` ASC) USING BTREE,
  INDEX `frequency_id`(`frequency_id` ASC) USING BTREE,
  CONSTRAINT `ppm_task_quan_ibfk_1` FOREIGN KEY (`ppm_task_id`) REFERENCES `ppm_task` (`ppm_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_quan_ibfk_3` FOREIGN KEY (`frequency_id`) REFERENCES `ppm_frequency` (`frequency_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ppm_task_section
CREATE TABLE IF NOT EXISTS `ppm_task_section`  (
  `ppm_task_section_id` bigint NOT NULL AUTO_INCREMENT,
  `ppm_task_section_name` varchar(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `ppm_task_id` bigint NOT NULL,
  `ppm_task_section_status` tinyint NOT NULL DEFAULT 17,
  PRIMARY KEY (`ppm_task_section_id`) USING BTREE,
  INDEX `ppm_task_id`(`ppm_task_id` ASC) USING BTREE,
  INDEX `ppm_task_section_status`(`ppm_task_section_status` ASC) USING BTREE,
  CONSTRAINT `ppm_task_section_ibfk_1` FOREIGN KEY (`ppm_task_id`) REFERENCES `ppm_task` (`ppm_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ppm_task_upload
CREATE TABLE IF NOT EXISTS `ppm_task_upload`  (
  `ppm_task_upload_id` bigint NOT NULL AUTO_INCREMENT,
  `ppm_task_upload_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=before, 1=during, 2=after, 3=additional report, 4=signiture techician, 5=signiture checked, 6=signiture verified',
  `ppm_task_id` bigint NOT NULL,
  `ppm_task_upload_longitude` double(12, 6) NULL DEFAULT NULL,
  `ppm_task_upload_latitude` double(12, 6) NULL DEFAULT NULL,
  `ppm_task_upload_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `upload_id` bigint NOT NULL,
  `ppm_task_upload_timestamp` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`ppm_task_upload_id`) USING BTREE,
  INDEX `ppm_task_id`(`ppm_task_id` ASC) USING BTREE,
  INDEX `upload_id`(`upload_id` ASC) USING BTREE,
  CONSTRAINT `ppm_task_upload_ibfk_1` FOREIGN KEY (`ppm_task_id`) REFERENCES `ppm_task` (`ppm_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_upload_ibfk_2` FOREIGN KEY (`upload_id`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for pr
CREATE TABLE IF NOT EXISTS `pr`  (
  `pr_id` int NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint NULL DEFAULT NULL,
  `site_id` smallint NULL DEFAULT NULL,
  `pr_request_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `pr_quotation_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `pr_quotation_upload` bigint NULL DEFAULT NULL,
  `pr_po_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `po_po_upload` bigint NULL DEFAULT NULL,
  `supplier_id` smallint NULL DEFAULT NULL,
  `pr_other_cost` decimal(10, 2) NULL DEFAULT NULL,
  `pr_total_cost` decimal(10, 2) NULL DEFAULT NULL,
  `pr_requested_by` int NULL DEFAULT NULL,
  `pr_prepared_by` int NULL DEFAULT NULL,
  `pr_approved_by` int NULL DEFAULT NULL,
  `pr_sap_by` int NULL DEFAULT NULL,
  `pr_po_by` int NULL DEFAULT NULL,
  `pr_time_requested` timestamp NULL DEFAULT NULL,
  `pr_time_submit` timestamp NULL DEFAULT NULL,
  `pr_time_approved` timestamp NULL DEFAULT NULL,
  `pr_time_stocked` timestamp NULL DEFAULT NULL,
  `pr_time_sap` timestamp NULL DEFAULT NULL,
  `pr_time_po` timestamp NULL DEFAULT NULL,
  `pr_status` tinyint NULL DEFAULT NULL,
  PRIMARY KEY (`pr_id`) USING BTREE,
  INDEX `supplier_id`(`supplier_id` ASC) USING BTREE,
  INDEX `pr_request_by`(`pr_requested_by` ASC) USING BTREE,
  INDEX `transaction_id`(`transaction_id` ASC) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  INDEX `pr_quotation_upload`(`pr_quotation_upload` ASC) USING BTREE,
  INDEX `po_po_upload`(`po_po_upload` ASC) USING BTREE,
  CONSTRAINT `pr_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `pr_supplier` (`supplier_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `pr_ibfk_2` FOREIGN KEY (`pr_requested_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `pr_ibfk_3` FOREIGN KEY (`transaction_id`) REFERENCES `wfl_transaction` (`transaction_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `pr_ibfk_4` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `pr_ibfk_5` FOREIGN KEY (`pr_quotation_upload`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `pr_ibfk_6` FOREIGN KEY (`po_po_upload`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for pr_item
CREATE TABLE IF NOT EXISTS `pr_item`  (
  `pr_item_id` bigint NOT NULL AUTO_INCREMENT,
  `part_id` bigint NULL DEFAULT NULL,
  `store_id` smallint NULL DEFAULT NULL,
  `asset_group_id` smallint NULL DEFAULT NULL,
  `item_type_id` smallint NULL DEFAULT NULL,
  `item_id` smallint NULL DEFAULT NULL,
  `pr_item_cost` decimal(12, 2) NULL DEFAULT NULL,
  `pr_item_request` smallint NULL DEFAULT NULL,
  `pr_item_pr` smallint NULL DEFAULT NULL,
  `pr_item_received` smallint NULL DEFAULT NULL,
  `pr_item_status` tinyint NULL DEFAULT NULL,
  PRIMARY KEY (`pr_item_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for pr_supplier
CREATE TABLE IF NOT EXISTS `pr_supplier`  (
  `supplier_id` smallint NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `supplier_reg_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `supplier_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`supplier_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ptw_approval_log
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

-- Table structure for ptw_document
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

-- Table structure for ptw_number_sequence
CREATE TABLE IF NOT EXISTS `ptw_number_sequence`  (
  `site_id` int NOT NULL,
  `seq_date` date NOT NULL,
  `seq_type` enum('REQUEST','PERMIT') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `next_value` int NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`site_id`, `seq_date`, `seq_type`) USING BTREE,
  INDEX `idx_seq_updated`(`updated_at` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for ptw_permit
CREATE TABLE IF NOT EXISTS `ptw_permit`  (
  `ptw_permit_id` int NOT NULL AUTO_INCREMENT,
  `ptw_permit_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_request_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_permit_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ptw_work_area` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ptw_work_type` enum('Cold Work','Hot Work','Confined Space') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ptw_work_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ptw_risk_level` enum('LOW','MEDIUM','HIGH','CRITICAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LOW',
  `ptw_valid_from` datetime NOT NULL,
  `ptw_valid_to` datetime NOT NULL,
  `cancel_requested_by` int NULL DEFAULT NULL,
  `cancel_requested_at` datetime NULL DEFAULT NULL,
  `ptw_extension_requested_to` datetime NULL DEFAULT NULL,
  `ptw_extension_requested_by` int NULL DEFAULT NULL,
  `ptw_extension_requested_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ptw_extension_requested_at` datetime NULL DEFAULT NULL,
  `ptw_contractor_company` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_contractor_supervisor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Contractor Supervisor Name',
  `ptw_staff_nric` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Staff NRIC/IC Number',
  `ptw_supervisor_contact` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Supervisor Contact Number',
  `ptw_identification_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Identification Number',
  `ptw_level` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Level/Floor',
  `ptw_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ptw_status` enum('DRAFT','SUBMITTED','PENDING_SUPERVISOR','PENDING_SHE','PENDING_FM','APPROVED','ACTIVE','PENDING_CLOSURE','COMPLETED','PENDING_CANCELLATION','PENDING_SUSPENSION','PENDING_EXTENSION','CANCELLED','SUSPENDED','REJECTED','EXTENDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `ptw_applicant_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ptw_applicant_contact` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_applicant_company_dept` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_work_duration` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_hazards` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ptw_control_measures` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ptw_checklist_cold_work` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `ptw_checklist_hot_work` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `ptw_checklist_confined_space` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `ptw_hazard_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `ptw_declaration_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `ptw_supporting_docs_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT 'URS Supporting Documents Checklist',
  `ptw_certificate_numbers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT 'Certificate and Permit Numbers',
  `public_token` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `public_token_expires_at` datetime NULL DEFAULT NULL,
  `public_link_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `public_token_revoked_at` datetime NULL DEFAULT NULL,
  `ptw_complete_form_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `site_id` int NOT NULL,
  `created_by` int NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp,
  `updated_by` int NULL DEFAULT NULL,
  `updated_date` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `approved_supervisor_by` int NULL DEFAULT NULL,
  `approved_supervisor_date` timestamp NULL DEFAULT NULL,
  `ptw_supervisor_comments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ptw_supervisor_approval_date` timestamp NULL DEFAULT NULL,
  `ptw_supervisor_id` int NULL DEFAULT NULL,
  `ptw_supervisor_rejection_date` timestamp NULL DEFAULT NULL,
  `ptw_supervisor_approval` enum('PENDING','APPROVED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'PENDING',
  `ptw_she_approval` enum('PENDING','APPROVED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'PENDING',
  `approved_she_by` int NULL DEFAULT NULL,
  `approved_she_date` timestamp NULL DEFAULT NULL,
  `ptw_she_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ptw_fm_approval` enum('PENDING','APPROVED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'PENDING',
  `approved_fm_by` int NULL DEFAULT NULL,
  `approved_fm_date` timestamp NULL DEFAULT NULL,
  `ptw_fm_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `activated_by` int NULL DEFAULT NULL,
  `activated_date` timestamp NULL DEFAULT NULL,
  `completed_by` int NULL DEFAULT NULL,
  `completed_date` timestamp NULL DEFAULT NULL,
  `cancelled_by` int NULL DEFAULT NULL,
  `cancelled_date` timestamp NULL DEFAULT NULL,
  `cancel_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `suspend_requested_by` int NULL DEFAULT NULL,
  `suspend_requested_at` datetime NULL DEFAULT NULL,
  `suspend_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `suspend_ncr_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `suspended_by` int NULL DEFAULT NULL,
  `suspended_date` datetime NULL DEFAULT NULL,
  `ptw_hazardous_activities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `ptw_contractor_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Stores contractor representative name',
  `ptw_contractor_designation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Stores contractor designation',
  `ptw_contractor_date` date NULL DEFAULT NULL COMMENT 'Stores acknowledgment date',
  PRIMARY KEY (`ptw_permit_id`) USING BTREE,
  UNIQUE INDEX `uk_ptw_permit_number`(`ptw_permit_number` ASC) USING BTREE,
  UNIQUE INDEX `uk_ptw_request_number`(`ptw_request_number` ASC) USING BTREE,
  INDEX `idx_ptw_site_id`(`site_id` ASC) USING BTREE,
  INDEX `idx_ptw_status`(`ptw_status` ASC) USING BTREE,
  INDEX `idx_ptw_created_by`(`created_by` ASC) USING BTREE,
  INDEX `idx_ptw_valid_period`(`ptw_valid_from` ASC, `ptw_valid_to` ASC) USING BTREE,
  INDEX `idx_public_token`(`public_token` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for ptw_status_history
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

-- Table structure for ptw_worker
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

-- Table structure for ref_city
CREATE TABLE IF NOT EXISTS `ref_city`  (
  `city_id` smallint NOT NULL AUTO_INCREMENT,
  `city_desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `state_id` smallint NOT NULL,
  `city_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`city_id`) USING BTREE,
  INDEX `state_id`(`state_id` ASC) USING BTREE,
  INDEX `city_status`(`city_status` ASC) USING BTREE,
  INDEX `city_desc`(`city_desc` ASC) USING BTREE,
  CONSTRAINT `ref_city_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `ref_state` (`state_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for ref_country
CREATE TABLE IF NOT EXISTS `ref_country`  (
  `country_id` smallint NOT NULL AUTO_INCREMENT,
  `country_desc` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `country_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`country_id`) USING BTREE,
  INDEX `ref_country_country_status_index`(`country_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for ref_department
CREATE TABLE IF NOT EXISTS `ref_department`  (
  `department_id` tinyint NOT NULL AUTO_INCREMENT,
  `department_desc` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `department_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`department_id`) USING BTREE,
  INDEX `department_status`(`department_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ref_designation
CREATE TABLE IF NOT EXISTS `ref_designation`  (
  `designation_id` tinyint NOT NULL AUTO_INCREMENT,
  `designation_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `designation_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`designation_id`) USING BTREE,
  INDEX `designation_status`(`designation_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ref_document
CREATE TABLE IF NOT EXISTS `ref_document`  (
  `document_id` smallint NOT NULL AUTO_INCREMENT,
  `document_desc` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `document_type` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `document_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`document_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for ref_failure_code
CREATE TABLE IF NOT EXISTS `ref_failure_code`  (
  `failure_code_id` smallint NOT NULL AUTO_INCREMENT,
  `failure_code_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `failure_code_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`failure_code_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ref_item
CREATE TABLE IF NOT EXISTS `ref_item`  (
  `item_id` smallint NOT NULL AUTO_INCREMENT,
  `item_description` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `item_type_id` smallint NULL DEFAULT NULL,
  `item_threshold` smallint NULL DEFAULT NULL,
  `item_min_order` smallint NULL DEFAULT NULL,
  `item_max_order` smallint NULL DEFAULT NULL,
  `item_turn` tinyint NULL DEFAULT NULL,
  `item_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `item_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`item_id`) USING BTREE,
  INDEX `item_type_id`(`item_type_id` ASC) USING BTREE,
  INDEX `item_status`(`item_status` ASC) USING BTREE,
  CONSTRAINT `ref_item_ibfk_1` FOREIGN KEY (`item_type_id`) REFERENCES `ref_item_type` (`item_type_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ref_item_image
CREATE TABLE IF NOT EXISTS `ref_item_image`  (
  `item_image_id` int NOT NULL AUTO_INCREMENT,
  `upload_id` bigint NOT NULL,
  `item_id` smallint NOT NULL,
  `item_image_timestamp` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`item_image_id`) USING BTREE,
  INDEX `upload_id`(`upload_id` ASC) USING BTREE,
  INDEX `item_id`(`item_id` ASC) USING BTREE,
  CONSTRAINT `ref_item_image_ibfk_1` FOREIGN KEY (`upload_id`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ref_item_image_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `ref_item` (`item_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ref_item_type
CREATE TABLE IF NOT EXISTS `ref_item_type`  (
  `item_type_id` smallint NOT NULL AUTO_INCREMENT,
  `asset_group_id` smallint NULL DEFAULT NULL,
  `item_type_desc` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `item_type_turn` tinyint NULL DEFAULT NULL,
  `item_type_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`item_type_id`) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id` ASC) USING BTREE,
  INDEX `item_type_status`(`item_type_status` ASC) USING BTREE,
  CONSTRAINT `ref_item_type_ibfk_1` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ref_role
CREATE TABLE IF NOT EXISTS `ref_role`  (
  `role_id` tinyint NOT NULL AUTO_INCREMENT,
  `role_desc` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `role_type` tinyint NULL DEFAULT NULL,
  `role_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`role_id`) USING BTREE,
  INDEX `ref_role_role_status_index`(`role_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ref_severity
CREATE TABLE IF NOT EXISTS `ref_severity`  (
  `severity_id` tinyint NOT NULL AUTO_INCREMENT,
  `severity_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `severity_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`severity_id`) USING BTREE,
  INDEX `severity_status`(`severity_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for ref_space_category
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

-- Table structure for ref_space_location
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

-- Table structure for ref_space_status
CREATE TABLE IF NOT EXISTS `ref_space_status`  (
  `space_status_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `space_status_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `space_status_order` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `space_status_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`space_status_code`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- Table structure for ref_space_type
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

-- Table structure for ref_state
CREATE TABLE IF NOT EXISTS `ref_state`  (
  `state_id` smallint NOT NULL AUTO_INCREMENT,
  `country_id` smallint NOT NULL,
  `state_desc` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `state_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`state_id`) USING BTREE,
  INDEX `ref_state_state_status_index`(`state_status` ASC) USING BTREE,
  INDEX `ref_state_ref_country_country_id_fk`(`country_id` ASC) USING BTREE,
  INDEX `state_desc`(`state_desc` ASC) USING BTREE,
  CONSTRAINT `ref_state_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `ref_country` (`country_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for ref_status
CREATE TABLE IF NOT EXISTS `ref_status`  (
  `status_id` tinyint NOT NULL,
  `status_desc` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `status_action` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `status_color` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `status_color_code` varchar(7) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`status_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci COMMENT = 'Reference status' ROW_FORMAT = Dynamic;

-- Table structure for spc_reservation
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

-- Table structure for spc_space
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

-- Table structure for spc_space_asset
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

-- Table structure for spc_space_media
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

-- Table structure for sys_address
CREATE TABLE IF NOT EXISTS `sys_address`  (
  `address_id` bigint NOT NULL AUTO_INCREMENT,
  `address_desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `address_postcode` varchar(5) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `address_city` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `state_id` smallint NULL DEFAULT NULL,
  PRIMARY KEY (`address_id`) USING BTREE,
  INDEX `state_id`(`state_id` ASC) USING BTREE,
  CONSTRAINT `sys_address_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `ref_state` (`state_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for sys_audit
CREATE TABLE IF NOT EXISTS `sys_audit`  (
  `audit_id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int NULL DEFAULT NULL,
  `audit_ip` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `audit_place` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `audit_timestamp` timestamp NOT NULL DEFAULT current_timestamp,
  `audit_action_id` smallint NOT NULL,
  `audit_remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL,
  PRIMARY KEY (`audit_id`) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  INDEX `audit_action_id`(`audit_action_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for sys_audit_action
CREATE TABLE IF NOT EXISTS `sys_audit_action`  (
  `audit_action_id` smallint NOT NULL AUTO_INCREMENT,
  `audit_action_desc` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `audit_module_id` smallint NOT NULL,
  `audit_action_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`audit_action_id`) USING BTREE,
  INDEX `audit_module_id`(`audit_module_id` ASC) USING BTREE,
  INDEX `audit_action_status`(`audit_action_status` ASC) USING BTREE,
  CONSTRAINT `sys_audit_action_ibfk_1` FOREIGN KEY (`audit_module_id`) REFERENCES `sys_audit_module` (`audit_module_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for sys_audit_module
CREATE TABLE IF NOT EXISTS `sys_audit_module`  (
  `audit_module_id` smallint NOT NULL AUTO_INCREMENT,
  `audit_module_desc` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `audit_module_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`audit_module_id`) USING BTREE,
  INDEX `audit_module_status`(`audit_module_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for sys_group
CREATE TABLE IF NOT EXISTS `sys_group`  (
  `group_id` smallint NOT NULL AUTO_INCREMENT,
  `group_name` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `group_type` tinyint NOT NULL COMMENT '1=Internal, 2=Company, 3=Public',
  `group_reg_no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `group_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `group_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`group_id`) USING BTREE,
  INDEX `group_status`(`group_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for sys_location
CREATE TABLE IF NOT EXISTS `sys_location`  (
  `location_id` int NOT NULL AUTO_INCREMENT,
  `location_desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `location_status` tinyint NOT NULL DEFAULT 1,
  `location_longitude` float(11, 6) NOT NULL,
  `location_latitude` float(11, 6) NOT NULL,
  PRIMARY KEY (`location_id`) USING BTREE,
  INDEX `location_status`(`location_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for sys_nav
CREATE TABLE IF NOT EXISTS `sys_nav`  (
  `nav_id` smallint NOT NULL AUTO_INCREMENT,
  `nav_desc` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `nav_page` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `nav_icon` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `nav_status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`nav_id`) USING BTREE,
  INDEX `sys_nav_nav_status_index`(`nav_status` ASC) USING BTREE,
  INDEX `sys_nav_nav_status_index_2`(`nav_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci COMMENT = 'Left navigation' ROW_FORMAT = Dynamic;

-- Table structure for sys_nav_role
CREATE TABLE IF NOT EXISTS `sys_nav_role`  (
  `nav_role_id` smallint NOT NULL AUTO_INCREMENT,
  `role_id` tinyint NOT NULL,
  `nav_id` smallint NOT NULL,
  `nav_second_id` smallint NULL DEFAULT NULL,
  `nav_role_turn` smallint NOT NULL DEFAULT 1,
  PRIMARY KEY (`nav_role_id`) USING BTREE,
  INDEX `sys_nav_role_sys_nav_nav_id_fk`(`nav_id` ASC) USING BTREE,
  INDEX `sys_nav_role_ref_role_role_id_fk`(`role_id` ASC) USING BTREE,
  INDEX `nav_role_turn`(`nav_role_turn` ASC) USING BTREE,
  INDEX `nav_second_id`(`nav_second_id` ASC) USING BTREE,
  CONSTRAINT `sys_nav_role_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_nav_role_ibfk_2` FOREIGN KEY (`nav_id`) REFERENCES `sys_nav` (`nav_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_nav_role_ibfk_3` FOREIGN KEY (`nav_second_id`) REFERENCES `sys_nav_second` (`nav_second_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for sys_nav_role_backup_20251021
CREATE TABLE IF NOT EXISTS `sys_nav_role_backup_20251021`  (
  `nav_role_id` smallint NOT NULL DEFAULT 0,
  `role_id` tinyint NOT NULL,
  `nav_id` smallint NOT NULL,
  `nav_second_id` smallint NULL DEFAULT NULL,
  `nav_role_turn` smallint NOT NULL DEFAULT 1
) ENGINE = InnoDB CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- Table structure for sys_nav_second
CREATE TABLE IF NOT EXISTS `sys_nav_second`  (
  `nav_second_id` smallint NOT NULL AUTO_INCREMENT,
  `nav_id` smallint NOT NULL,
  `nav_second_desc` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `nav_second_page` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `nav_second_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`nav_second_id`) USING BTREE,
  INDEX `sys_nav_second_sys_nav_nav_id_fk`(`nav_id` ASC) USING BTREE,
  INDEX `sys_nav_second_nav_second_status_index`(`nav_second_status` ASC) USING BTREE,
  CONSTRAINT `sys_nav_second_ibfk_1` FOREIGN KEY (`nav_id`) REFERENCES `sys_nav` (`nav_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for sys_pdf
CREATE TABLE IF NOT EXISTS `sys_pdf`  (
  `pdf_id` bigint NOT NULL AUTO_INCREMENT,
  `pdf_type` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `pdf_folder` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `pdf_filename` varchar(80) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `pdf_timeCreated` timestamp NOT NULL DEFAULT current_timestamp,
  `pdf_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`pdf_id`) USING BTREE,
  INDEX `pdf_type`(`pdf_type` ASC) USING BTREE,
  INDEX `pdf_status`(`pdf_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for sys_site
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

-- Table structure for sys_upload
CREATE TABLE IF NOT EXISTS `sys_upload`  (
  `upload_id` bigint NOT NULL AUTO_INCREMENT,
  `document_id` smallint NOT NULL,
  `upload_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `upload_uplname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `upload_filename` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `upload_extension` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `upload_folder` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `upload_filesize` int NULL DEFAULT NULL,
  `upload_file_width` int NULL DEFAULT NULL,
  `upload_file_height` int NULL DEFAULT NULL,
  `upload_blob_type` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `upload_blob_data` blob NULL,
  `upload_time_upload` timestamp NOT NULL DEFAULT current_timestamp,
  `upload_created_by` int NULL DEFAULT NULL,
  `upload_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`upload_id`) USING BTREE,
  INDEX `document_id`(`document_id` ASC) USING BTREE,
  INDEX `upload_status`(`upload_status` ASC) USING BTREE,
  CONSTRAINT `sys_upload_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `ref_document` (`document_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for sys_user
CREATE TABLE IF NOT EXISTS `sys_user`  (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `user_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `user_password` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `user_password_temp` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `user_type` tinyint NOT NULL DEFAULT 1,
  `user_activation_key` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `user_first_name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `user_last_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `user_mykad_no` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `user_passport_no` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `site_id` smallint NULL DEFAULT NULL,
  `user_device_id` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `upload_id` bigint NULL DEFAULT NULL,
  `user_fail_attempt` tinyint(1) NULL DEFAULT 0,
  `user_time_created` timestamp NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  `user_time_activate` timestamp NULL DEFAULT NULL,
  `user_time_login` timestamp NULL DEFAULT NULL,
  `user_time_block` timestamp NULL DEFAULT NULL,
  `user_token` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `user_signature` bigint NULL DEFAULT NULL,
  `user_status` tinyint NOT NULL DEFAULT 3,
  `user_designation` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`) USING BTREE,
  UNIQUE INDEX `user_name`(`user_name` ASC) USING BTREE,
  INDEX `user_status`(`user_status` ASC) USING BTREE,
  INDEX `user_type`(`user_type` ASC) USING BTREE,
  INDEX `user_password`(`user_password` ASC) USING BTREE,
  INDEX `upload_id`(`upload_id` ASC) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  INDEX `user_signature`(`user_signature` ASC) USING BTREE,
  INDEX `idx_user_name`(`user_name` ASC) USING BTREE,
  CONSTRAINT `sys_user_ibfk_1` FOREIGN KEY (`user_status`) REFERENCES `ref_status` (`status_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_ibfk_2` FOREIGN KEY (`upload_id`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_ibfk_3` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_ibfk_4` FOREIGN KEY (`user_signature`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci COMMENT = 'System user' ROW_FORMAT = Dynamic;

-- Table structure for sys_user_group
CREATE TABLE IF NOT EXISTS `sys_user_group`  (
  `user_group_id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `group_id` smallint NOT NULL,
  PRIMARY KEY (`user_group_id`) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  INDEX `group_id`(`group_id` ASC) USING BTREE,
  CONSTRAINT `sys_user_group_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_group_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for sys_user_profile
CREATE TABLE IF NOT EXISTS `sys_user_profile`  (
  `user_profile_id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `address_id` bigint NULL DEFAULT NULL,
  `user_contact_no` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `user_email` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `department_id` tinyint NULL DEFAULT NULL,
  `designation_id` tinyint NULL DEFAULT NULL,
  `user_profile_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_profile_id`) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  INDEX `address_id`(`address_id` ASC) USING BTREE,
  INDEX `user_profile_status`(`user_profile_status` ASC) USING BTREE,
  INDEX `department_id`(`department_id` ASC) USING BTREE,
  INDEX `designation_id`(`designation_id` ASC) USING BTREE,
  CONSTRAINT `sys_user_profile_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_profile_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `sys_address` (`address_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_profile_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `ref_department` (`department_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_profile_ibfk_4` FOREIGN KEY (`designation_id`) REFERENCES `ref_designation` (`designation_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for sys_user_role
CREATE TABLE IF NOT EXISTS `sys_user_role`  (
  `user_role_id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `role_id` tinyint NOT NULL,
  `group_id` smallint NOT NULL,
  `user_role_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`user_role_id`) USING BTREE,
  INDEX `sys_user_role_sys_user_user_id_fk`(`user_id` ASC) USING BTREE,
  INDEX `sys_user_role_ref_role_role_id_fk`(`role_id` ASC) USING BTREE,
  INDEX `group_id`(`group_id` ASC) USING BTREE,
  CONSTRAINT `sys_user_role_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_role_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_role_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for sys_user_signature
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

-- Table structure for sys_version
CREATE TABLE IF NOT EXISTS `sys_version`  (
  `version_id` smallint NOT NULL AUTO_INCREMENT,
  `version_name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `version_no` int NOT NULL DEFAULT 1,
  `version_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`version_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for utl_meter
CREATE TABLE IF NOT EXISTS `utl_meter`  (
  `meter_id` smallint NOT NULL AUTO_INCREMENT,
  `site_id` smallint NULL DEFAULT NULL,
  `meter_type` enum('Water','Electricity') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `meter_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `meter_location` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `meter_status` tinyint NULL DEFAULT NULL,
  PRIMARY KEY (`meter_id`) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  CONSTRAINT `utl_meter_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for utl_utility
CREATE TABLE IF NOT EXISTS `utl_utility`  (
  `utility_id` int NOT NULL AUTO_INCREMENT,
  `utility_date` date NULL DEFAULT NULL,
  `utility_type` enum('Water','Electricity') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `utility_reading_type` enum('Daily','Monthly') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `utility_shift` enum('Night','Evening','Morning') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `site_id` smallint NULL DEFAULT NULL,
  `meter_id` smallint NULL DEFAULT NULL,
  `utility_opening` decimal(12, 3) NULL DEFAULT NULL,
  `utility_reading` double(12, 3) NULL DEFAULT NULL,
  `utility_total` decimal(12, 3) NULL DEFAULT NULL,
  `utility_unit` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `utility_max_demand` double(12, 3) NULL DEFAULT NULL,
  `utility_total_rm` double(12, 2) NULL DEFAULT NULL,
  `utility_image` bigint NULL DEFAULT NULL,
  `utility_recorded_by` int NULL DEFAULT NULL,
  `utility_timestamp` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`utility_id`) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  INDEX `meter_id`(`meter_id` ASC) USING BTREE,
  INDEX `utility_recorded_by`(`utility_recorded_by` ASC) USING BTREE,
  INDEX `utility_image`(`utility_image` ASC) USING BTREE,
  CONSTRAINT `utl_utility_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `utl_utility_ibfk_2` FOREIGN KEY (`meter_id`) REFERENCES `utl_meter` (`meter_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `utl_utility_ibfk_3` FOREIGN KEY (`utility_recorded_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `utl_utility_ibfk_4` FOREIGN KEY (`utility_image`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for vm_host
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

-- Table structure for vm_visit
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

-- Table structure for wfl_checkpoint
CREATE TABLE IF NOT EXISTS `wfl_checkpoint`  (
  `checkpoint_id` smallint NOT NULL AUTO_INCREMENT,
  `flow_id` tinyint NOT NULL,
  `checkpoint_desc` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `checkpoint_type` tinyint NOT NULL DEFAULT 1 COMMENT '1=Start, 2=Normal, 3=End',
  `checkpoint_claim_type` tinyint NOT NULL COMMENT '1=No, 2=Yes, 3=Assigned User, 4=Assigned Group',
  `checkpoint_due_day` tinyint NULL DEFAULT NULL,
  `checkpoint_next` smallint NULL DEFAULT NULL,
  `checkpoint_case_1` smallint NULL DEFAULT NULL,
  `checkpoint_case_2` smallint NULL DEFAULT NULL,
  `checkpoint_case_3` smallint NULL DEFAULT NULL,
  `checkpoint_icon` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `role_id` tinyint NULL DEFAULT NULL,
  `group_id` smallint NULL DEFAULT NULL,
  `checkpoint_order` tinyint NOT NULL DEFAULT 1,
  `checkpoint_color` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `checkpoint_skip` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`checkpoint_id`) USING BTREE,
  INDEX `flow_id`(`flow_id` ASC) USING BTREE,
  INDEX `role_id`(`role_id` ASC) USING BTREE,
  INDEX `group_id`(`group_id` ASC) USING BTREE,
  CONSTRAINT `wfl_checkpoint_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_checkpoint_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_checkpoint_ibfk_3` FOREIGN KEY (`flow_id`) REFERENCES `wfl_flow` (`flow_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for wfl_checkpoint_assign
CREATE TABLE IF NOT EXISTS `wfl_checkpoint_assign`  (
  `checkpoint_assign_id` smallint NOT NULL AUTO_INCREMENT,
  `checkpoint_assign_type` tinyint NOT NULL COMMENT '1=Assign to himself\r\n2=Assign to User\r\n3=Assign to Group',
  `checkpoint_id` smallint NOT NULL,
  `checkpoint_to` smallint NOT NULL,
  PRIMARY KEY (`checkpoint_assign_id`) USING BTREE,
  INDEX `checkpoint_id`(`checkpoint_id` ASC) USING BTREE,
  INDEX `checkpoint_to`(`checkpoint_to` ASC) USING BTREE,
  CONSTRAINT `wfl_checkpoint_assign_ibfk_1` FOREIGN KEY (`checkpoint_id`) REFERENCES `wfl_checkpoint` (`checkpoint_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_checkpoint_assign_ibfk_2` FOREIGN KEY (`checkpoint_to`) REFERENCES `wfl_checkpoint` (`checkpoint_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for wfl_checkpoint_user
CREATE TABLE IF NOT EXISTS `wfl_checkpoint_user`  (
  `checkpoint_user_id` bigint NOT NULL AUTO_INCREMENT,
  `checkpoint_id` smallint NOT NULL,
  `user_id` int NOT NULL,
  `role_id` tinyint NOT NULL,
  `group_id` smallint NOT NULL,
  PRIMARY KEY (`checkpoint_user_id`) USING BTREE,
  INDEX `checkpoint_id`(`checkpoint_id` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  INDEX `group_id`(`group_id` ASC) USING BTREE,
  INDEX `role_id`(`role_id` ASC) USING BTREE,
  CONSTRAINT `wfl_checkpoint_user_ibfk_1` FOREIGN KEY (`checkpoint_id`) REFERENCES `wfl_checkpoint` (`checkpoint_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_checkpoint_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_checkpoint_user_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_checkpoint_user_ibfk_4` FOREIGN KEY (`role_id`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for wfl_flow
CREATE TABLE IF NOT EXISTS `wfl_flow`  (
  `flow_id` tinyint NOT NULL AUTO_INCREMENT,
  `flow_desc` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `flow_due_day` smallint NOT NULL DEFAULT 30,
  `flow_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`flow_id`) USING BTREE,
  INDEX `flow_status`(`flow_status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for wfl_task
CREATE TABLE IF NOT EXISTS `wfl_task`  (
  `task_id` bigint NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint NOT NULL,
  `checkpoint_id` smallint NOT NULL,
  `role_id` tinyint NOT NULL,
  `group_id` smallint NULL DEFAULT NULL,
  `task_current` tinyint NOT NULL DEFAULT 1,
  `task_created_user` int NOT NULL,
  `task_created_group` smallint NOT NULL,
  `task_claimed_user` int NULL DEFAULT NULL,
  `task_remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL,
  `task_date_due` date NULL DEFAULT NULL,
  `task_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `task_time_claimed` timestamp NULL DEFAULT NULL,
  `task_time_submit` timestamp NULL DEFAULT NULL,
  `task_status_previous` tinyint NULL DEFAULT NULL,
  `task_status_save` tinyint NULL DEFAULT NULL,
  `task_status` tinyint NOT NULL,
  PRIMARY KEY (`task_id`) USING BTREE,
  INDEX `transaction_id`(`transaction_id` ASC) USING BTREE,
  INDEX `checkpoint_id`(`checkpoint_id` ASC) USING BTREE,
  INDEX `task_status`(`task_status` ASC) USING BTREE,
  INDEX `group_id`(`group_id` ASC) USING BTREE,
  INDEX `task_created_user`(`task_created_user` ASC) USING BTREE,
  INDEX `task_created_group`(`task_created_group` ASC) USING BTREE,
  INDEX `task_claimed_user`(`task_claimed_user` ASC) USING BTREE,
  INDEX `task_current`(`task_current` ASC) USING BTREE,
  INDEX `role_id`(`role_id` ASC) USING BTREE,
  INDEX `task_date_due`(`task_date_due` ASC) USING BTREE,
  CONSTRAINT `wfl_task_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `wfl_transaction` (`transaction_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_ibfk_2` FOREIGN KEY (`checkpoint_id`) REFERENCES `wfl_checkpoint` (`checkpoint_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_ibfk_3` FOREIGN KEY (`role_id`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_ibfk_4` FOREIGN KEY (`group_id`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_ibfk_5` FOREIGN KEY (`task_created_user`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_ibfk_6` FOREIGN KEY (`task_claimed_user`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_ibfk_7` FOREIGN KEY (`task_created_group`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for wfl_task_assign
CREATE TABLE IF NOT EXISTS `wfl_task_assign`  (
  `task_assign_id` bigint NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint NOT NULL,
  `checkpoint_id` smallint NOT NULL,
  `role_id` tinyint NOT NULL,
  `group_id` smallint NOT NULL,
  `user_id` int NULL DEFAULT NULL,
  PRIMARY KEY (`task_assign_id`) USING BTREE,
  INDEX `transaction_id`(`transaction_id` ASC) USING BTREE,
  INDEX `checkpoint_id`(`checkpoint_id` ASC) USING BTREE,
  INDEX `group_id`(`group_id` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  INDEX `role_id`(`role_id` ASC) USING BTREE,
  CONSTRAINT `wfl_task_assign_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `wfl_transaction` (`transaction_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_assign_ibfk_2` FOREIGN KEY (`checkpoint_id`) REFERENCES `wfl_checkpoint` (`checkpoint_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_assign_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_assign_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_assign_ibfk_5` FOREIGN KEY (`role_id`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for wfl_transaction
CREATE TABLE IF NOT EXISTS `wfl_transaction`  (
  `transaction_id` bigint NOT NULL AUTO_INCREMENT,
  `transaction_no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `flow_id` tinyint NOT NULL,
  `user_id` int NOT NULL,
  `group_id` smallint NOT NULL,
  `asset_no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `transaction_date_due` date NULL DEFAULT NULL,
  `transaction_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `transaction_time_update` timestamp NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  `transaction_time_complete` timestamp NULL DEFAULT NULL,
  `transaction_status` tinyint NOT NULL,
  PRIMARY KEY (`transaction_id`) USING BTREE,
  INDEX `flow_id`(`flow_id` ASC) USING BTREE,
  INDEX `transaction_status`(`transaction_status` ASC) USING BTREE,
  INDEX `group_id`(`group_id` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  CONSTRAINT `wfl_transaction_ibfk_1` FOREIGN KEY (`flow_id`) REFERENCES `wfl_flow` (`flow_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_transaction_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_transaction_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for wfl_user_report
CREATE TABLE IF NOT EXISTS `wfl_user_report`  (
  `user_report_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `role_id` tinyint NOT NULL,
  `report_to` int NOT NULL,
  `report_role` tinyint NOT NULL,
  PRIMARY KEY (`user_report_id`) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  INDEX `role_id`(`role_id` ASC) USING BTREE,
  INDEX `report_to`(`report_to` ASC) USING BTREE,
  INDEX `report_role`(`report_role` ASC) USING BTREE,
  CONSTRAINT `wfl_user_report_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_user_report_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_user_report_ibfk_3` FOREIGN KEY (`report_to`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_user_report_ibfk_4` FOREIGN KEY (`report_role`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for wo_import_batch
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

-- Table structure for wo_import_log
CREATE TABLE IF NOT EXISTS `wo_import_log`  (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `batch_id` int NOT NULL,
  `import_row_number` int NOT NULL,
  `import_status` enum('SUCCESS','SKIPPED','FAILED','ERROR') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `row_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT 'JSON data of the imported row',
  `wo_task_id` int NULL DEFAULT NULL COMMENT 'Created work order ID if successful',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`log_id`) USING BTREE,
  INDEX `idx_batch_id`(`batch_id` ASC) USING BTREE,
  INDEX `idx_import_status`(`import_status` ASC) USING BTREE,
  INDEX `idx_wo_task_id`(`wo_task_id` ASC) USING BTREE,
  CONSTRAINT `wo_import_log_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `wo_import_batch` (`batch_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- Table structure for wo_migration
CREATE TABLE IF NOT EXISTS `wo_migration`  (
  `wo_task_id` bigint NULL DEFAULT NULL,
  `wo_task_no` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `wo_task_type` tinyint(1) NULL DEFAULT NULL,
  `wo_task_type_init` tinyint(1) NULL DEFAULT NULL,
  `site_id` smallint NULL DEFAULT NULL,
  `wo_task_location` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `wo_task_complaint` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL,
  `wo_task_longitude` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_latitude` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_assigned_to` int NULL DEFAULT NULL,
  `ppm_group_id` smallint NULL DEFAULT NULL,
  `wo_task_severity` tinyint(1) NULL DEFAULT NULL,
  `wo_task_repair_desc` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL,
  `wo_task_rate` tinyint(1) NULL DEFAULT NULL,
  `transaction_id` bigint NULL DEFAULT NULL,
  `pdf_id` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_created_by` int NULL DEFAULT NULL,
  `wo_task_assigned_by` int NULL DEFAULT NULL,
  `wo_task_fixed_by` int NULL DEFAULT NULL,
  `wo_task_verified_by` int NULL DEFAULT NULL,
  `wo_task_time_created` timestamp NULL DEFAULT NULL,
  `wo_task_time_responded` timestamp NULL DEFAULT NULL,
  `wo_task_time_assigned` timestamp NULL DEFAULT NULL,
  `wo_task_time_executed` timestamp NULL DEFAULT NULL,
  `wo_task_time_verified` timestamp NULL DEFAULT NULL,
  `wo_task_status` tinyint NULL DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- Table structure for wo_task
CREATE TABLE IF NOT EXISTS `wo_task`  (
  `wo_task_id` bigint NOT NULL AUTO_INCREMENT,
  `wo_task_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `wo_task_request_no` varchar(31) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `wo_task_type_init` tinyint(1) NOT NULL COMMENT '1=external complaint, 2=internal complaint',
  `wo_task_type` tinyint(1) NULL DEFAULT NULL COMMENT '1=external complaint, 2=internal complaint',
  `wo_task_is_wr` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=no, 1=yes',
  `wo_task_is_wr_verified_together` tinyint(1) NOT NULL DEFAULT 0,
  `wo_task_wr_confirm` tinyint(1) NOT NULL DEFAULT 0,
  `wo_task_is_helpdesk` tinyint(1) NOT NULL DEFAULT 0,
  `wo_task_external_ref` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'External system work order reference',
  `wo_task_is_imported` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flag to indicate if WO was imported from external system',
  `wo_task_is_public` tinyint(1) NOT NULL DEFAULT 0,
  `wo_task_is_invalid` tinyint(1) NOT NULL DEFAULT 0,
  `site_id` smallint NULL DEFAULT NULL,
  `zone_id` smallint NULL DEFAULT NULL,
  `wo_task_location` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `wo_task_complaint` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `wo_task_longitude` double(12, 6) NULL DEFAULT NULL,
  `wo_task_latitude` double(12, 6) NULL DEFAULT NULL,
  `wo_task_assigned_to` int NULL DEFAULT NULL,
  `wo_task_done_asset` tinyint(1) NOT NULL DEFAULT 0,
  `wo_task_done_assistant` tinyint(1) NOT NULL DEFAULT 0,
  `asset_id` bigint NULL DEFAULT NULL,
  `ppm_group_id` smallint NULL DEFAULT NULL,
  `wo_task_severity` tinyint(1) NULL DEFAULT NULL COMMENT '1=Non-Critical, 2=Critical',
  `wo_task_wr_check` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `wo_task_has_parts` tinyint(1) NULL DEFAULT NULL,
  `wo_task_repair_desc` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `wo_task_rate` tinyint(1) NULL DEFAULT NULL,
  `wo_task_max_assistant` tinyint(1) NULL DEFAULT NULL,
  `transaction_id` bigint NOT NULL,
  `pdf_id` bigint NULL DEFAULT NULL,
  `pdf_id_wr` bigint NULL DEFAULT NULL,
  `wo_task_is_pdf_wr` tinyint(1) NOT NULL DEFAULT 0,
  `wo_task_is_pdf` tinyint(1) NOT NULL DEFAULT 0,
  `wo_task_created_by` int NULL DEFAULT NULL,
  `wo_task_assigned_by` int NULL DEFAULT NULL,
  `wo_task_wr_checked_by` int NULL DEFAULT NULL,
  `wo_task_wr_verified_by` int NULL DEFAULT NULL,
  `wo_task_fixed_by` int NULL DEFAULT NULL,
  `wo_task_verified_by` int NULL DEFAULT NULL,
  `wo_task_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `wo_task_time_responded` timestamp NULL DEFAULT NULL,
  `wo_task_time_assigned` timestamp NULL DEFAULT NULL,
  `wo_task_time_rectified` timestamp NULL DEFAULT NULL,
  `wo_task_time_wr_checked` timestamp NULL DEFAULT NULL,
  `wo_task_time_wr_verified` timestamp NULL DEFAULT NULL,
  `wo_task_time_executed` timestamp NULL DEFAULT NULL,
  `wo_task_time_verified` timestamp NULL DEFAULT NULL,
  `wo_task_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`wo_task_id`) USING BTREE,
  INDEX `transaction_id`(`transaction_id` ASC) USING BTREE,
  INDEX `wo_task_status`(`wo_task_status` ASC) USING BTREE,
  INDEX `pdf_id`(`pdf_id` ASC) USING BTREE,
  INDEX `wo_task_created_by`(`wo_task_created_by` ASC) USING BTREE,
  INDEX `wo_task_responsed_by`(`wo_task_assigned_by` ASC) USING BTREE,
  INDEX `wo_task_executed_by`(`wo_task_fixed_by` ASC) USING BTREE,
  INDEX `wo_task_verified_by`(`wo_task_verified_by` ASC) USING BTREE,
  INDEX `site_id`(`site_id` ASC) USING BTREE,
  INDEX `ppm_group_id`(`ppm_group_id` ASC) USING BTREE,
  INDEX `pdf_id_wr`(`pdf_id_wr` ASC) USING BTREE,
  INDEX `wo_task_wr_checked_by`(`wo_task_wr_checked_by` ASC) USING BTREE,
  INDEX `wo_task_wr_verified_by`(`wo_task_wr_verified_by` ASC) USING BTREE,
  INDEX `asset_id`(`asset_id` ASC) USING BTREE,
  INDEX `wo_task_severity`(`wo_task_severity` ASC) USING BTREE,
  INDEX `zone_id`(`zone_id` ASC) USING BTREE,
  INDEX `idx_external_ref`(`wo_task_external_ref` ASC) USING BTREE,
  INDEX `idx_is_imported`(`wo_task_is_imported` ASC) USING BTREE,
  CONSTRAINT `wo_task_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `wfl_transaction` (`transaction_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_10` FOREIGN KEY (`wo_task_wr_checked_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_11` FOREIGN KEY (`wo_task_wr_verified_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_12` FOREIGN KEY (`asset_id`) REFERENCES `ast_asset` (`asset_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_13` FOREIGN KEY (`wo_task_severity`) REFERENCES `ref_severity` (`severity_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_14` FOREIGN KEY (`zone_id`) REFERENCES `cli_zone` (`zone_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_2` FOREIGN KEY (`pdf_id`) REFERENCES `sys_pdf` (`pdf_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_3` FOREIGN KEY (`wo_task_created_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_4` FOREIGN KEY (`wo_task_assigned_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_5` FOREIGN KEY (`wo_task_fixed_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_6` FOREIGN KEY (`wo_task_verified_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_7` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_8` FOREIGN KEY (`ppm_group_id`) REFERENCES `ppm_group` (`ppm_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_9` FOREIGN KEY (`pdf_id_wr`) REFERENCES `sys_pdf` (`pdf_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for wo_task_assist
CREATE TABLE IF NOT EXISTS `wo_task_assist`  (
  `wo_task_assist_id` bigint NOT NULL AUTO_INCREMENT,
  `wo_task_id` bigint NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`wo_task_assist_id`) USING BTREE,
  INDEX `wo_task_id`(`wo_task_id` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  CONSTRAINT `wo_task_assist_ibfk_1` FOREIGN KEY (`wo_task_id`) REFERENCES `wo_task` (`wo_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_assist_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for wo_task_parts
CREATE TABLE IF NOT EXISTS `wo_task_parts`  (
  `wo_task_parts_id` bigint NOT NULL AUTO_INCREMENT,
  `wo_task_request_id` bigint NULL DEFAULT NULL,
  `part_id` bigint NULL DEFAULT NULL,
  `wo_task_parts_quantity` int NULL DEFAULT NULL,
  `wo_task_parts_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `wo_task_parts_status` tinyint NULL DEFAULT NULL,
  PRIMARY KEY (`wo_task_parts_id`) USING BTREE,
  INDEX `part_id`(`part_id` ASC) USING BTREE,
  INDEX `wo_task_request_id`(`wo_task_request_id` ASC) USING BTREE,
  CONSTRAINT `wo_task_parts_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `ast_part` (`part_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_parts_ibfk_3` FOREIGN KEY (`wo_task_request_id`) REFERENCES `wo_task_request` (`wo_task_request_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for wo_task_public
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

-- Table structure for wo_task_request
CREATE TABLE IF NOT EXISTS `wo_task_request`  (
  `wo_task_request_id` bigint NOT NULL AUTO_INCREMENT,
  `wo_task_id` bigint NULL DEFAULT NULL,
  `transaction_id` bigint NULL DEFAULT NULL,
  `wo_task_request_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `wo_task_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `store_id` smallint NULL DEFAULT NULL,
  `wo_task_request_severity` tinyint(1) NULL DEFAULT NULL,
  `wo_task_request_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `wo_task_request_order_by` int NULL DEFAULT NULL,
  `wo_task_request_is_standalone` tinyint(1) NOT NULL DEFAULT 0,
  `wo_task_request_mrf_generate` tinyint(1) NOT NULL DEFAULT 1,
  `wo_task_request_mrf_pdf` bigint NULL DEFAULT NULL,
  `wo_task_request_time_created` timestamp NOT NULL DEFAULT current_timestamp,
  `wo_task_request_time_ordered` timestamp NULL DEFAULT NULL,
  `wo_task_request_time_collected` timestamp NULL DEFAULT NULL,
  `wo_task_request_time_rejected` timestamp NULL DEFAULT NULL,
  `wo_task_request_status` tinyint NULL DEFAULT NULL,
  PRIMARY KEY (`wo_task_request_id`) USING BTREE,
  INDEX `wo_task_id`(`wo_task_id` ASC) USING BTREE,
  INDEX `transaction_id`(`transaction_id` ASC) USING BTREE,
  INDEX `wo_task_request_mrf_pdf`(`wo_task_request_mrf_pdf` ASC) USING BTREE,
  INDEX `wo_task_request_severity`(`wo_task_request_severity` ASC) USING BTREE,
  INDEX `store_id`(`store_id` ASC) USING BTREE,
  CONSTRAINT `wo_task_request_ibfk_1` FOREIGN KEY (`wo_task_id`) REFERENCES `wo_task` (`wo_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_request_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `wfl_transaction` (`transaction_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_request_ibfk_3` FOREIGN KEY (`wo_task_request_mrf_pdf`) REFERENCES `sys_pdf` (`pdf_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_request_ibfk_4` FOREIGN KEY (`wo_task_request_severity`) REFERENCES `ref_severity` (`severity_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_request_ibfk_5` FOREIGN KEY (`store_id`) REFERENCES `cli_store` (`store_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- Table structure for wo_task_request_2
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

-- Table structure for wo_task_upload
CREATE TABLE IF NOT EXISTS `wo_task_upload`  (
  `wo_task_upload_id` bigint NOT NULL AUTO_INCREMENT,
  `wo_task_upload_type` tinyint NOT NULL DEFAULT 1 COMMENT '1=complain, 2=before, 3=during, 4=after, 5=signiture complainer, 6=signiture responder, 7=signiture executor, 8=signiture verified, 9=signiture wr checked, 10=signiture wr verified, 12=signiture check',
  `wo_task_id` bigint NOT NULL,
  `wo_task_upload_longitude` double(12, 6) NULL DEFAULT NULL,
  `wo_task_upload_latitude` double(12, 6) NULL DEFAULT NULL,
  `wo_task_upload_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `upload_id` bigint NOT NULL,
  `wo_task_upload_timestamp` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`wo_task_upload_id`) USING BTREE,
  INDEX `upload_id`(`upload_id` ASC) USING BTREE,
  INDEX `wo_task_upload_type`(`wo_task_upload_type` ASC) USING BTREE,
  INDEX `wo_task_id`(`wo_task_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;

-- View structure for vw_item_image
CREATE OR REPLACE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_item_image` AS select `ii`.`item_image_id` AS `itemImageId`,`ii`.`item_id` AS `itemId`,`ii`.`upload_id` AS `uploadId`,`u`.`upload_name` AS `uploadName`,`u`.`upload_filename` AS `uploadFilename`,`u`.`upload_extension` AS `uploadExtension`,`u`.`upload_folder` AS `uploadFolder`,`u`.`upload_file_width` AS `uploadFileWidth`,`u`.`upload_file_height` AS `uploadFileHeight` from (`ref_item_image` `ii` join `sys_upload` `u` on(`ii`.`upload_id` = `u`.`upload_id`)) where `u`.`upload_status` = 1 ;

-- View structure for vw_part_with_image
CREATE OR REPLACE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_part_with_image` AS select `p`.`part_id` AS `partId`,`p`.`site_id` AS `siteId`,`p`.`store_id` AS `storeId`,`p`.`asset_group_id` AS `assetGroupId`,`p`.`item_type_id` AS `itemTypeId`,`p`.`item_id` AS `itemId`,`p`.`part_count` AS `partCount`,`p`.`part_locked` AS `partLocked`,`p`.`part_threshold` AS `partThreshold`,`p`.`part_min_order` AS `partMinOrder`,`p`.`part_max_order` AS `partMaxOrder`,`p`.`part_remark` AS `partRemark`,`p`.`part_status` AS `partStatus`,group_concat(distinct concat(`u`.`upload_folder`,'/',`u`.`upload_filename`,'.',`u`.`upload_extension`) order by `ii`.`item_image_id` ASC separator '||') AS `uploadList`,group_concat(distinct `u`.`upload_name` order by `ii`.`item_image_id` ASC separator '||') AS `titleList`,group_concat(distinct `u`.`upload_file_width` order by `ii`.`item_image_id` ASC separator '||') AS `widthList`,group_concat(distinct `u`.`upload_file_height` order by `ii`.`item_image_id` ASC separator '||') AS `heightList` from ((`ast_part` `p` left join `ref_item_image` `ii` on(`p`.`item_id` = `ii`.`item_id`)) left join `sys_upload` `u` on(`ii`.`upload_id` = `u`.`upload_id` and `u`.`upload_status` = 1)) group by `p`.`part_id`,`p`.`site_id`,`p`.`store_id`,`p`.`asset_group_id`,`p`.`item_type_id`,`p`.`item_id`,`p`.`part_count`,`p`.`part_locked`,`p`.`part_threshold`,`p`.`part_min_order`,`p`.`part_max_order`,`p`.`part_remark`,`p`.`part_status` ;

-- View structure for vw_ppm_set_asset_details
CREATE OR REPLACE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_ppm_set_asset_details` AS select `psa`.`ppm_set_asset_id` AS `ppm_set_asset_id`,`psa`.`ppm_set_id` AS `ppm_set_id`,`psa`.`asset_id` AS `asset_id`,`aa`.`asset_no` AS `asset_no`,`aa`.`asset_name` AS `asset_name`,`aa`.`asset_location_desc` AS `asset_location_desc` from (`ppm_set_asset` `psa` join `ast_asset` `aa` on(`psa`.`asset_id` = `aa`.`asset_id`)) ;

SELECT 'db-jkr-production-old-upgraded.sql schema load completed' AS status;
