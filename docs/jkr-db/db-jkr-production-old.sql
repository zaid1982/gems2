/*
 Navicat MySQL Data Transfer

 Source Server         : GEMS+
 Source Server Type    : MariaDB
 Source Server Version : 101002 (10.10.2-MariaDB)
 Source Host           : 10.8.80.131:3306
 Source Schema         : gems

 Target Server Type    : MariaDB
 Target Server Version : 101002 (10.10.2-MariaDB)
 File Encoding         : 65001

 Date: 22/05/2026 11:52:55
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for ast_asset
-- ----------------------------
DROP TABLE IF EXISTS `ast_asset`;
CREATE TABLE `ast_asset`  (
  `asset_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `asset_no` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_name` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_serial_no` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_desc` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_capacity` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_group_id` smallint(6) NULL DEFAULT NULL,
  `asset_category_id` smallint(6) NULL DEFAULT NULL,
  `asset_type_id` smallint(6) NULL DEFAULT NULL,
  `asset_brand_id` smallint(6) NULL DEFAULT NULL,
  `asset_model_id` mediumint(9) NULL DEFAULT NULL,
  `contract_id` smallint(6) NULL DEFAULT NULL,
  `location_code_id` int(11) NULL DEFAULT NULL,
  `asset_location_code` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_location_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_group_id` smallint(6) NULL DEFAULT NULL,
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
  `asset_life_cycle` smallint(6) NULL DEFAULT NULL,
  `asset_warranty_notes` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_technician_notes` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_purchase_price` decimal(10, 2) NULL DEFAULT NULL,
  `asset_commissioned_date` date NULL DEFAULT NULL,
  `asset_disposed_date` date NULL DEFAULT NULL,
  `asset_current_value` decimal(10, 2) NULL DEFAULT NULL,
  `asset_estimated_life` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_lifetime_date` date NULL DEFAULT NULL,
  `migration_id` smallint(6) NULL DEFAULT NULL,
  `document_no` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_registered_by` int(11) NULL DEFAULT NULL,
  `asset_time_registered` timestamp NULL DEFAULT NULL,
  `asset_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `asset_status` tinyint(4) NOT NULL DEFAULT 1,
  `asset_temp_flag` tinyint(1) NULL DEFAULT NULL,
  `checklist_id` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`asset_id`) USING BTREE,
  INDEX `contract_id`(`contract_id`) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id`) USING BTREE,
  INDEX `asset_category_id`(`asset_category_id`) USING BTREE,
  INDEX `asset_type_id`(`asset_type_id`) USING BTREE,
  INDEX `asset_brand_id`(`asset_brand_id`) USING BTREE,
  INDEX `asset_model_id`(`asset_model_id`) USING BTREE,
  INDEX `asset_status`(`asset_status`) USING BTREE,
  INDEX `asset_registered_by`(`asset_registered_by`) USING BTREE,
  INDEX `ppm_group_id`(`ppm_group_id`) USING BTREE,
  INDEX `checklist_id`(`checklist_id`) USING BTREE,
  CONSTRAINT `ast_asset_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `cli_contract` (`contract_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_ibfk_10` FOREIGN KEY (`ppm_group_id`) REFERENCES `ppm_group` (`ppm_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_ibfk_11` FOREIGN KEY (`checklist_id`) REFERENCES `ppm_checklist` (`checklist_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_ibfk_3` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_ibfk_4` FOREIGN KEY (`asset_category_id`) REFERENCES `ast_asset_category` (`asset_category_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_ibfk_5` FOREIGN KEY (`asset_type_id`) REFERENCES `ast_asset_type` (`asset_type_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_ibfk_6` FOREIGN KEY (`asset_brand_id`) REFERENCES `ast_asset_brand` (`asset_brand_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_ibfk_7` FOREIGN KEY (`asset_model_id`) REFERENCES `ast_asset_model` (`asset_model_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_ibfk_8` FOREIGN KEY (`asset_registered_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 181771 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ast_asset_brand
-- ----------------------------
DROP TABLE IF EXISTS `ast_asset_brand`;
CREATE TABLE `ast_asset_brand`  (
  `asset_brand_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `asset_brand_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `asset_brand_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_brand_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `asset_brand_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`asset_brand_id`) USING BTREE,
  INDEX `asset_brand_status`(`asset_brand_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 653 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ast_asset_category
-- ----------------------------
DROP TABLE IF EXISTS `ast_asset_category`;
CREATE TABLE `ast_asset_category`  (
  `asset_category_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `asset_category_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `asset_category_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_group_id` smallint(6) NOT NULL,
  `asset_category_time_created` timestamp NULL DEFAULT NULL,
  `asset_category_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`asset_category_id`) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id`) USING BTREE,
  INDEX `asset_category_status`(`asset_category_status`) USING BTREE,
  CONSTRAINT `ast_asset_category_ibfk_1` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 358 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ast_asset_group
-- ----------------------------
DROP TABLE IF EXISTS `ast_asset_group`;
CREATE TABLE `ast_asset_group`  (
  `asset_group_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `asset_group_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `asset_group_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_group_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `asset_group_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`asset_group_id`) USING BTREE,
  INDEX `asset_group_status`(`asset_group_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 32 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ast_asset_model
-- ----------------------------
DROP TABLE IF EXISTS `ast_asset_model`;
CREATE TABLE `ast_asset_model`  (
  `asset_model_id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `asset_model_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `asset_model_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_brand_id` smallint(6) NOT NULL,
  `asset_type_id` smallint(6) NOT NULL,
  `asset_model_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `asset_model_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`asset_model_id`) USING BTREE,
  INDEX `asset_brand_id`(`asset_brand_id`) USING BTREE,
  INDEX `asset_model_status`(`asset_model_status`) USING BTREE,
  INDEX `asset_type_id`(`asset_type_id`) USING BTREE,
  CONSTRAINT `ast_asset_model_ibfk_1` FOREIGN KEY (`asset_brand_id`) REFERENCES `ast_asset_brand` (`asset_brand_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_asset_model_ibfk_2` FOREIGN KEY (`asset_type_id`) REFERENCES `ast_asset_type` (`asset_type_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1117 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ast_asset_type
-- ----------------------------
DROP TABLE IF EXISTS `ast_asset_type`;
CREATE TABLE `ast_asset_type`  (
  `asset_type_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `asset_type_name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `asset_type_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_type_speacial` tinyint(1) NULL DEFAULT NULL,
  `asset_category_id` smallint(6) NOT NULL,
  `asset_type_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `asset_type_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`asset_type_id`) USING BTREE,
  INDEX `asset_category_id`(`asset_category_id`) USING BTREE,
  INDEX `asset_type_status`(`asset_type_status`) USING BTREE,
  CONSTRAINT `ast_asset_type_ibfk_1` FOREIGN KEY (`asset_category_id`) REFERENCES `ast_asset_category` (`asset_category_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 3366 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ast_part
-- ----------------------------
DROP TABLE IF EXISTS `ast_part`;
CREATE TABLE `ast_part`  (
  `part_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `site_id` smallint(6) NULL DEFAULT NULL,
  `store_id` smallint(6) NULL DEFAULT NULL,
  `asset_group_id` smallint(6) NULL DEFAULT NULL,
  `item_type_id` smallint(6) NULL DEFAULT NULL,
  `item_id` smallint(6) NULL DEFAULT NULL,
  `part_count` smallint(6) NULL DEFAULT NULL,
  `part_locked` smallint(6) NULL DEFAULT NULL,
  `part_threshold` smallint(6) NULL DEFAULT NULL,
  `part_min_order` smallint(6) NULL DEFAULT NULL,
  `part_max_order` smallint(6) NULL DEFAULT NULL,
  `part_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `part_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`part_id`) USING BTREE,
  INDEX `part_status`(`part_status`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  INDEX `store_id`(`store_id`) USING BTREE,
  INDEX `item_id`(`item_id`) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id`) USING BTREE,
  INDEX `item_type_id`(`item_type_id`) USING BTREE,
  CONSTRAINT `ast_part_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_part_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `cli_store` (`store_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_part_ibfk_3` FOREIGN KEY (`item_id`) REFERENCES `ref_item` (`item_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_part_ibfk_4` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_part_ibfk_5` FOREIGN KEY (`item_type_id`) REFERENCES `ref_item_type` (`item_type_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 654 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ast_part_sub
-- ----------------------------
DROP TABLE IF EXISTS `ast_part_sub`;
CREATE TABLE `ast_part_sub`  (
  `part_sub_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `part_id` bigint(20) NOT NULL,
  `item_id` smallint(6) NULL DEFAULT NULL,
  `part_sub_no` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `do_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `do_item_id` bigint(20) NULL DEFAULT NULL,
  `wo_task_parts_id` bigint(20) NULL DEFAULT NULL,
  `wo_task_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `wo_task_request_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `part_sub_location` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `part_sub_warranty` tinyint(4) NULL DEFAULT NULL,
  `part_sub_validity` date NULL DEFAULT NULL,
  `part_sub_cost` decimal(12, 2) NULL DEFAULT NULL,
  `part_sub_registered_by` int(11) NULL DEFAULT NULL,
  `part_sub_collected_by` int(11) NULL DEFAULT NULL,
  `part_sub_time_registered` timestamp NOT NULL DEFAULT current_timestamp(),
  `part_sub_time_reserved` timestamp NULL DEFAULT NULL,
  `part_sub_time_check_out` timestamp NULL DEFAULT NULL,
  `part_sub_status` tinyint(4) NULL DEFAULT NULL,
  PRIMARY KEY (`part_sub_id`) USING BTREE,
  INDEX `part_id`(`part_id`) USING BTREE,
  INDEX `item_id`(`item_id`) USING BTREE,
  INDEX `wo_task_parts_id`(`wo_task_parts_id`) USING BTREE,
  INDEX `do_item_id`(`do_item_id`) USING BTREE,
  CONSTRAINT `ast_part_sub_ibfk_1` FOREIGN KEY (`part_id`) REFERENCES `ast_part` (`part_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_part_sub_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `ref_item` (`item_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_part_sub_ibfk_4` FOREIGN KEY (`wo_task_parts_id`) REFERENCES `wo_task_parts` (`wo_task_parts_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ast_part_sub_ibfk_5` FOREIGN KEY (`do_item_id`) REFERENCES `do_item` (`do_item_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 20482 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for att_group
-- ----------------------------
DROP TABLE IF EXISTS `att_group`;
CREATE TABLE `att_group`  (
  `att_group_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `site_id` smallint(6) NOT NULL,
  `att_group_name` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `asset_group_id` smallint(6) NULL DEFAULT NULL,
  `att_group_supervisor` int(11) NULL DEFAULT NULL,
  `att_group_polygon` polygon NULL,
  `att_group_map_center` point NULL,
  `att_group_map_zoom` tinyint(4) NULL DEFAULT NULL,
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
  `att_group_req_week_hours` smallint(6) NULL DEFAULT NULL,
  `att_group_shift_mode` enum('Normal','2 Shifts','3 Shifts') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_group_ot_approver` int(11) NULL DEFAULT NULL,
  `att_group_remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_group_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `att_group_time_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  `att_group_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`att_group_id`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  INDEX `att_group_supervisor`(`att_group_supervisor`) USING BTREE,
  INDEX `att_group_ot_approver`(`att_group_ot_approver`) USING BTREE,
  INDEX `att_group_status`(`att_group_status`) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id`) USING BTREE,
  CONSTRAINT `att_group_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `att_group_ibfk_2` FOREIGN KEY (`att_group_supervisor`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `att_group_ibfk_3` FOREIGN KEY (`att_group_ot_approver`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `att_group_ibfk_4` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 74 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for att_participant
-- ----------------------------
DROP TABLE IF EXISTS `att_participant`;
CREATE TABLE `att_participant`  (
  `att_participant_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `att_group_id` smallint(6) NOT NULL,
  `user_id` int(11) NOT NULL,
  `att_participant_gf_id` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT '',
  `asset_group_id` smallint(6) NULL DEFAULT NULL,
  `att_type_id` tinyint(4) NULL DEFAULT NULL,
  `att_participant_holiday` enum('Sunday','Saturday & Sunday') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_participant_req_week_hours` smallint(6) NULL DEFAULT NULL,
  `att_participant_shift_mode` enum('Normal','2 Shifts','3 Shifts') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_participant_year_service` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_participant_cidb_card_expiry` date NULL DEFAULT NULL,
  `att_participant_competency` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_participant_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `att_participant_time_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  `att_participant_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`att_participant_id`) USING BTREE,
  INDEX `att_group_id`(`att_group_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id`) USING BTREE,
  INDEX `att_type_id`(`att_type_id`) USING BTREE,
  CONSTRAINT `att_participant_ibfk_1` FOREIGN KEY (`att_group_id`) REFERENCES `att_group` (`att_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `att_participant_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `att_participant_ibfk_3` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `att_participant_ibfk_4` FOREIGN KEY (`att_type_id`) REFERENCES `att_type` (`att_type_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 126 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for att_transaction
-- ----------------------------
DROP TABLE IF EXISTS `att_transaction`;
CREATE TABLE `att_transaction`  (
  `att_transaction_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `att_transaction_date` date NOT NULL,
  `att_participant_id` smallint(6) NOT NULL,
  `att_group_id` smallint(6) NOT NULL,
  `user_id` int(11) NOT NULL,
  `att_type_id` tinyint(4) NULL DEFAULT NULL,
  `att_transaction_shift_start` timestamp NULL DEFAULT NULL,
  `att_transaction_shift_end` timestamp NULL DEFAULT NULL,
  `att_transaction_time_in` timestamp NULL DEFAULT NULL,
  `att_transaction_time_out` timestamp NULL DEFAULT NULL,
  `att_transaction_location_in` point NULL,
  `att_transaction_location_out` point NULL,
  `att_transaction_result` enum('Present','Absent','Leave','Training') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_transaction_status` enum('Checked In','Checked Out','Ready') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'Ready',
  PRIMARY KEY (`att_transaction_id`) USING BTREE,
  INDEX `att_type_id`(`att_type_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `att_participant_id`(`att_participant_id`) USING BTREE,
  INDEX `att_group_id`(`att_group_id`) USING BTREE,
  CONSTRAINT `att_transaction_ibfk_1` FOREIGN KEY (`att_type_id`) REFERENCES `att_type` (`att_type_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `att_transaction_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `att_transaction_ibfk_3` FOREIGN KEY (`att_participant_id`) REFERENCES `att_participant` (`att_participant_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `att_transaction_ibfk_4` FOREIGN KEY (`att_group_id`) REFERENCES `att_group` (`att_group_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 15418 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for att_type
-- ----------------------------
DROP TABLE IF EXISTS `att_type`;
CREATE TABLE `att_type`  (
  `att_type_id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `att_type_name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `att_type_short` varchar(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `att_type_mode` enum('Normal','2 Shifts','3 Shifts','Leave','Training') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_type_color` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_type_color_done` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `att_type_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`att_type_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 24 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for cli_client
-- ----------------------------
DROP TABLE IF EXISTS `cli_client`;
CREATE TABLE `cli_client`  (
  `client_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `client_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `client_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `client_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `client_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`client_id`) USING BTREE,
  INDEX `client_status`(`client_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 23 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for cli_client_failure_code
-- ----------------------------
DROP TABLE IF EXISTS `cli_client_failure_code`;
CREATE TABLE `cli_client_failure_code`  (
  `client_failure_code_id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` smallint(6) NOT NULL,
  `failure_code_id` smallint(6) NOT NULL,
  PRIMARY KEY (`client_failure_code_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for cli_client_severity
-- ----------------------------
DROP TABLE IF EXISTS `cli_client_severity`;
CREATE TABLE `cli_client_severity`  (
  `client_severity_id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` smallint(6) NOT NULL,
  `severity_id` tinyint(4) NOT NULL,
  `client_severity_hour` smallint(6) NOT NULL DEFAULT 1,
  `client_severity_respond_time` smallint(6) NOT NULL DEFAULT 1,
  PRIMARY KEY (`client_severity_id`) USING BTREE,
  INDEX `client_id`(`client_id`) USING BTREE,
  INDEX `severity_id`(`severity_id`) USING BTREE,
  CONSTRAINT `cli_client_severity_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `cli_client` (`client_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `cli_client_severity_ibfk_3` FOREIGN KEY (`severity_id`) REFERENCES `ref_severity` (`severity_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 106 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for cli_contract
-- ----------------------------
DROP TABLE IF EXISTS `cli_contract`;
CREATE TABLE `cli_contract`  (
  `contract_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `contract_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `contract_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `contract_date_start` date NOT NULL,
  `contract_date_end` date NOT NULL,
  `site_id` smallint(6) NOT NULL,
  `contract_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `contract_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`contract_id`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  INDEX `contract_status`(`contract_status`) USING BTREE,
  CONSTRAINT `cli_contract_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 25 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for cli_contract_user
-- ----------------------------
DROP TABLE IF EXISTS `cli_contract_user`;
CREATE TABLE `cli_contract_user`  (
  `contract_user_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `contract_id` smallint(6) NOT NULL,
  `location_code_id` smallint(6) NOT NULL,
  `asset_group_id` smallint(6) NOT NULL,
  PRIMARY KEY (`contract_user_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `contract_id`(`contract_id`) USING BTREE,
  INDEX `location_code_id`(`location_code_id`) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id`) USING BTREE,
  CONSTRAINT `cli_contract_user_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `cli_contract_user_ibfk_2` FOREIGN KEY (`contract_id`) REFERENCES `cli_contract` (`contract_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `cli_contract_user_ibfk_3` FOREIGN KEY (`location_code_id`) REFERENCES `cli_location_code` (`location_code_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `cli_contract_user_ibfk_4` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 7024 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for cli_location_code
-- ----------------------------
DROP TABLE IF EXISTS `cli_location_code`;
CREATE TABLE `cli_location_code`  (
  `location_code_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `location_code_name` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `location_code_desc` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `location_code_type` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `site_id` smallint(6) NOT NULL,
  `location_code_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`location_code_id`) USING BTREE,
  INDEX `cli_location_code_ibfk_1`(`site_id`) USING BTREE,
  CONSTRAINT `cli_location_code_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 156 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for cli_site
-- ----------------------------
DROP TABLE IF EXISTS `cli_site`;
CREATE TABLE `cli_site`  (
  `site_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `site_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `site_code` varchar(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `site_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `site_running_no` int(11) NOT NULL DEFAULT 1,
  `site_running_no_wo` int(11) NOT NULL DEFAULT 1,
  `site_running_no_wr` int(11) NOT NULL DEFAULT 1,
  `site_running_no_req` int(11) NOT NULL DEFAULT 1,
  `site_running_no_part_sub` int(11) NOT NULL DEFAULT 1,
  `site_running_no_fca` int(11) NOT NULL DEFAULT 1,
  `site_is_launched` tinyint(1) NOT NULL DEFAULT 0,
  `site_is_manual` tinyint(1) NOT NULL DEFAULT 0,
  `site_is_wr` tinyint(1) NOT NULL DEFAULT 0,
  `site_is_material` tinyint(1) NOT NULL DEFAULT 0,
  `site_is_attendance` tinyint(1) NOT NULL DEFAULT 0,
  `client_id` smallint(6) NOT NULL,
  `group_id` smallint(6) NOT NULL,
  `site_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `site_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`site_id`) USING BTREE,
  INDEX `client_id`(`client_id`) USING BTREE,
  INDEX `group_id`(`group_id`) USING BTREE,
  CONSTRAINT `cli_site_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `cli_client` (`client_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `cli_site_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 24 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for cli_site_manual
-- ----------------------------
DROP TABLE IF EXISTS `cli_site_manual`;
CREATE TABLE `cli_site_manual`  (
  `site_manual_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `site_id` smallint(6) NOT NULL,
  `site_manual_date` date NOT NULL,
  `site_manual_open0` int(11) NOT NULL DEFAULT 0,
  `site_manual_open1` int(11) NOT NULL DEFAULT 0,
  `site_manual_open2` int(11) NOT NULL DEFAULT 0,
  `site_manual_open3` int(11) NOT NULL DEFAULT 0,
  `site_manual_open4` int(11) NOT NULL DEFAULT 0,
  `site_manual_open5` int(11) NOT NULL DEFAULT 0,
  `site_manual_closed0` int(11) NOT NULL DEFAULT 0,
  `site_manual_closed1` int(11) NOT NULL DEFAULT 0,
  `site_manual_closed2` int(11) NOT NULL DEFAULT 0,
  `site_manual_closed3` int(11) NOT NULL DEFAULT 0,
  `site_manual_closed4` int(11) NOT NULL DEFAULT 0,
  `site_manual_closed5` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`site_manual_id`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  CONSTRAINT `cli_site_manual_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2365 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for cli_site_problem_type
-- ----------------------------
DROP TABLE IF EXISTS `cli_site_problem_type`;
CREATE TABLE `cli_site_problem_type`  (
  `site_problem_type_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `site_id` smallint(6) NOT NULL,
  `site_problem_type_name` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `site_problem_type_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`site_problem_type_id`) USING BTREE,
  INDEX `problem_type_status`(`site_problem_type_status`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  CONSTRAINT `cli_site_problem_type_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for cli_store
-- ----------------------------
DROP TABLE IF EXISTS `cli_store`;
CREATE TABLE `cli_store`  (
  `store_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `site_id` smallint(6) NOT NULL,
  `store_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `store_desc` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `store_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`store_id`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  CONSTRAINT `cli_store_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 16 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for do
-- ----------------------------
DROP TABLE IF EXISTS `do`;
CREATE TABLE `do`  (
  `do_id` int(11) NOT NULL AUTO_INCREMENT,
  `pr_id` int(11) NULL DEFAULT NULL,
  `site_id` smallint(6) NULL DEFAULT NULL,
  `do_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `do_type` enum('Partial','Normal') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `do_upload` bigint(20) NULL DEFAULT NULL,
  `do_date` date NULL DEFAULT NULL,
  `supplier_id` smallint(6) NULL DEFAULT NULL,
  `supplier_name` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `do_created_by` int(11) NULL DEFAULT NULL,
  `do_received_by` int(11) NULL DEFAULT NULL,
  `do_timestamp` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `do_status` tinyint(4) NULL DEFAULT NULL,
  PRIMARY KEY (`do_id`) USING BTREE,
  INDEX `pr_do_ibfk_1`(`pr_id`) USING BTREE,
  INDEX `pr_do_ibfk_2`(`do_upload`) USING BTREE,
  INDEX `pr_do_ibfk_3`(`site_id`) USING BTREE,
  CONSTRAINT `pr_do_ibfk_1` FOREIGN KEY (`pr_id`) REFERENCES `pr` (`pr_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `pr_do_ibfk_2` FOREIGN KEY (`do_upload`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `pr_do_ibfk_3` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 455 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for do_item
-- ----------------------------
DROP TABLE IF EXISTS `do_item`;
CREATE TABLE `do_item`  (
  `do_item_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `do_id` int(11) NULL DEFAULT NULL,
  `part_id` bigint(20) NULL DEFAULT NULL,
  `do_item_total` smallint(6) NULL DEFAULT NULL,
  `do_item_warranty` tinyint(4) NULL DEFAULT NULL,
  `do_item_validity` date NULL DEFAULT NULL,
  `do_item_cost` decimal(12, 2) NULL DEFAULT NULL,
  `do_item_total_cost` decimal(12, 2) NULL DEFAULT NULL,
  `do_item_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`do_item_id`) USING BTREE,
  INDEX `part_id`(`part_id`) USING BTREE,
  INDEX `do_id`(`do_id`) USING BTREE,
  CONSTRAINT `do_item_ibfk_3` FOREIGN KEY (`part_id`) REFERENCES `ast_part` (`part_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `do_item_ibfk_4` FOREIGN KEY (`do_id`) REFERENCES `do` (`do_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 426 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for do_upload
-- ----------------------------
DROP TABLE IF EXISTS `do_upload`;
CREATE TABLE `do_upload`  (
  `do_upload_id` int(11) NOT NULL AUTO_INCREMENT,
  `do_id` int(11) NULL DEFAULT NULL,
  `upload_id` bigint(20) NOT NULL,
  `do_upload_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`do_upload_id`) USING BTREE,
  INDEX `do_id`(`do_id`) USING BTREE,
  INDEX `upload_id`(`upload_id`) USING BTREE,
  CONSTRAINT `do_upload_ibfk_1` FOREIGN KEY (`do_id`) REFERENCES `do` (`do_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `do_upload_ibfk_2` FOREIGN KEY (`upload_id`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 321 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for drawing
-- ----------------------------
DROP TABLE IF EXISTS `drawing`;
CREATE TABLE `drawing`  (
  `drawing_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `drawing_title` varchar(250) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `drawing_id_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `drawing_version` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_group_id` smallint(6) NULL DEFAULT NULL,
  `drawing_block` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `drawing_level` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `drawing_permission_level` tinyint(1) NULL DEFAULT NULL,
  `drawing_published_by` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `drawing_published_date` date NULL DEFAULT NULL,
  `drawing_dwg` bigint(20) NULL DEFAULT NULL,
  `drawing_pdf` bigint(20) NULL DEFAULT NULL,
  `drawing_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `drawing_created_by` int(11) NULL DEFAULT NULL,
  `drawing_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `drawing_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`drawing_id`) USING BTREE,
  INDEX `drawing_published_by`(`drawing_published_by`) USING BTREE,
  INDEX `drawing_dwg`(`drawing_dwg`) USING BTREE,
  INDEX `drawing_pdf`(`drawing_pdf`) USING BTREE,
  INDEX `drawing_created_by`(`drawing_created_by`) USING BTREE,
  INDEX `drawing_status`(`drawing_status`) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id`) USING BTREE,
  CONSTRAINT `drawing_ibfk_2` FOREIGN KEY (`drawing_dwg`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `drawing_ibfk_3` FOREIGN KEY (`drawing_pdf`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `drawing_ibfk_4` FOREIGN KEY (`drawing_created_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `drawing_ibfk_5` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2587 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for email_log
-- ----------------------------
DROP TABLE IF EXISTS `email_log`;
CREATE TABLE `email_log`  (
  `email_log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `email_template_id` smallint(6) NOT NULL,
  `email_address` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_title` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_html` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_id` int(11) NULL DEFAULT NULL,
  `email_attachment` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `email_filename` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `email_id` bigint(20) NULL DEFAULT NULL,
  `email_retry_no` tinyint(4) NOT NULL DEFAULT 0,
  `email_time_set` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_time_sent` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_log_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`email_log_id`) USING BTREE,
  INDEX `email_send_template_id`(`email_template_id`) USING BTREE,
  INDEX `email_send_time_set`(`email_time_sent`) USING BTREE,
  INDEX `email_send_ibfk_2`(`user_id`) USING BTREE,
  CONSTRAINT `email_log_ibfk_1` FOREIGN KEY (`email_template_id`) REFERENCES `email_template` (`email_template_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `email_log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1764439 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for email_parameter
-- ----------------------------
DROP TABLE IF EXISTS `email_parameter`;
CREATE TABLE `email_parameter`  (
  `email_param_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `email_template_id` smallint(6) NOT NULL,
  `email_param_code` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_param_desc` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`email_param_id`) USING BTREE,
  INDEX `email_template_id`(`email_template_id`) USING BTREE,
  CONSTRAINT `email_parameter_ibfk_1` FOREIGN KEY (`email_template_id`) REFERENCES `email_template` (`email_template_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 45 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for email_send
-- ----------------------------
DROP TABLE IF EXISTS `email_send`;
CREATE TABLE `email_send`  (
  `email_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `email_template_id` smallint(6) NOT NULL,
  `email_address` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_title` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_html` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_id` int(11) NULL DEFAULT NULL,
  `email_retry_no` tinyint(4) NOT NULL DEFAULT 0,
  `email_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_time_set` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_attachment` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `email_filename` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`email_id`) USING BTREE,
  INDEX `email_send_template_id`(`email_template_id`) USING BTREE,
  INDEX `email_send_time_set`(`email_time_set`) USING BTREE,
  INDEX `email_send_ibfk_2`(`user_id`) USING BTREE,
  CONSTRAINT `email_send_ibfk_1` FOREIGN KEY (`email_template_id`) REFERENCES `email_template` (`email_template_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `email_send_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 14688271 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for email_template
-- ----------------------------
DROP TABLE IF EXISTS `email_template`;
CREATE TABLE `email_template`  (
  `email_template_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `email_template_name` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_template_desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `email_template_title` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_template_html` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_template_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`email_template_id`) USING BTREE,
  INDEX `email_template_status`(`email_template_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 25 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for fca_defect_category
-- ----------------------------
DROP TABLE IF EXISTS `fca_defect_category`;
CREATE TABLE `fca_defect_category`  (
  `fca_defect_category_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `fca_defect_category_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `fca_defect_category_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`fca_defect_category_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 17 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for fca_defect_category_site
-- ----------------------------
DROP TABLE IF EXISTS `fca_defect_category_site`;
CREATE TABLE `fca_defect_category_site`  (
  `fca_defect_category_site_id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` smallint(6) NOT NULL,
  `fca_defect_category_id` smallint(6) NOT NULL,
  PRIMARY KEY (`fca_defect_category_site_id`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  INDEX `fca_defect_category_id`(`fca_defect_category_id`) USING BTREE,
  CONSTRAINT `fca_defect_category_site_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_defect_category_site_ibfk_2` FOREIGN KEY (`fca_defect_category_id`) REFERENCES `fca_defect_category` (`fca_defect_category_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 19 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for fca_report
-- ----------------------------
DROP TABLE IF EXISTS `fca_report`;
CREATE TABLE `fca_report`  (
  `fca_report_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `fca_report_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `fca_report_date_from` date NULL DEFAULT NULL,
  `fca_report_date_to` date NULL DEFAULT NULL,
  `site_id` smallint(6) NULL DEFAULT NULL,
  `asset_group_id` smallint(6) NULL DEFAULT NULL,
  `fca_report_exclude_list` tinyint(1) NOT NULL DEFAULT 0,
  `fca_report_sort_by` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `fca_report_total` int(11) NULL DEFAULT NULL,
  `pdf_id` bigint(20) NULL DEFAULT NULL,
  `fca_report_created_by` int(11) NOT NULL,
  `fca_report_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `fca_report_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`fca_report_id`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id`) USING BTREE,
  INDEX `pdf_id`(`pdf_id`) USING BTREE,
  CONSTRAINT `fca_report_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_report_ibfk_2` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_report_ibfk_3` FOREIGN KEY (`pdf_id`) REFERENCES `sys_pdf` (`pdf_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for fca_task
-- ----------------------------
DROP TABLE IF EXISTS `fca_task`;
CREATE TABLE `fca_task`  (
  `fca_task_id` int(11) NOT NULL AUTO_INCREMENT,
  `fca_task_no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `transaction_id` bigint(20) NOT NULL,
  `site_id` smallint(6) NULL DEFAULT NULL,
  `fca_zone_id` smallint(6) NULL DEFAULT NULL,
  `fca_task_area` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `asset_group_id` smallint(6) NULL DEFAULT NULL,
  `fca_task_asset_no` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `fca_task_asset_evaluated` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `fca_task_defect_item` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `fca_defect_category_id` smallint(6) NULL DEFAULT NULL,
  `fca_task_condition_scale` tinyint(1) NULL DEFAULT NULL,
  `fca_task_evaluation_type` tinyint(1) NULL DEFAULT NULL,
  `fca_task_image_1` bigint(20) NULL DEFAULT NULL,
  `fca_task_image_2` bigint(20) NULL DEFAULT NULL,
  `fca_task_image_rectify_1` bigint(20) NULL DEFAULT NULL,
  `fca_task_image_rectify_2` bigint(20) NULL DEFAULT NULL,
  `fca_task_observation` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `fca_task_recommendation` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `fca_task_validation` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `fca_task_exclude_report` tinyint(1) NOT NULL DEFAULT 0,
  `fca_task_removed` tinyint(1) NOT NULL DEFAULT 0,
  `fca_task_created_by` int(11) NULL DEFAULT NULL,
  `fca_task_recommend_by` int(11) NULL DEFAULT NULL,
  `fca_task_validate_by` int(11) NULL DEFAULT NULL,
  `fca_task_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `fca_task_time_recommended` timestamp NULL DEFAULT NULL,
  `fca_task_time_validated` timestamp NULL DEFAULT NULL,
  `fca_task_time_completed` timestamp NULL DEFAULT NULL,
  `fca_task_status` tinyint(4) NOT NULL DEFAULT 5,
  PRIMARY KEY (`fca_task_id`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  INDEX `transaction_id`(`transaction_id`) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id`) USING BTREE,
  INDEX `fca_defect_category_id`(`fca_defect_category_id`) USING BTREE,
  INDEX `fca_task_image_1`(`fca_task_image_1`) USING BTREE,
  INDEX `fca_task_image_2`(`fca_task_image_2`) USING BTREE,
  INDEX `fca_zone_id`(`fca_zone_id`) USING BTREE,
  INDEX `fca_task_image_rectify_1`(`fca_task_image_rectify_1`) USING BTREE,
  INDEX `fca_task_image_rectify_2`(`fca_task_image_rectify_2`) USING BTREE,
  INDEX `fca_task_removed`(`fca_task_removed`) USING BTREE,
  INDEX `fca_task_exclude_report`(`fca_task_exclude_report`) USING BTREE,
  CONSTRAINT `fca_task_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_task_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `wfl_transaction` (`transaction_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_task_ibfk_3` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_task_ibfk_4` FOREIGN KEY (`fca_defect_category_id`) REFERENCES `fca_defect_category` (`fca_defect_category_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_task_ibfk_5` FOREIGN KEY (`fca_task_image_1`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_task_ibfk_6` FOREIGN KEY (`fca_task_image_2`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_task_ibfk_7` FOREIGN KEY (`fca_zone_id`) REFERENCES `fca_zone` (`fca_zone_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_task_ibfk_8` FOREIGN KEY (`fca_task_image_rectify_1`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fca_task_ibfk_9` FOREIGN KEY (`fca_task_image_rectify_2`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 127 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for fca_task_section
-- ----------------------------
DROP TABLE IF EXISTS `fca_task_section`;
CREATE TABLE `fca_task_section`  (
  `fca_task_section_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `fca_task_section_code` varchar(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `fca_task_section_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `fca_task_id` int(11) NOT NULL,
  `fca_task_section_status` tinyint(4) NOT NULL DEFAULT 17,
  PRIMARY KEY (`fca_task_section_id`) USING BTREE,
  INDEX `fca_task_id`(`fca_task_id`) USING BTREE,
  CONSTRAINT `fca_task_section_ibfk_1` FOREIGN KEY (`fca_task_id`) REFERENCES `fca_task` (`fca_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 376 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for fca_zone
-- ----------------------------
DROP TABLE IF EXISTS `fca_zone`;
CREATE TABLE `fca_zone`  (
  `fca_zone_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `fca_zone_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `site_id` smallint(6) NOT NULL,
  `fca_zone_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`fca_zone_id`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  CONSTRAINT `fca_zone_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 37 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for gmi_monthly
-- ----------------------------
DROP TABLE IF EXISTS `gmi_monthly`;
CREATE TABLE `gmi_monthly`  (
  `gmi_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `site_id` smallint(6) NULL DEFAULT NULL,
  `gmi_year` smallint(6) NULL DEFAULT NULL,
  `gmi_month` tinyint(4) NULL DEFAULT NULL,
  `gmi_ppm_tier_name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `gmi_ppm_tier_point` decimal(3, 1) NULL DEFAULT NULL,
  `gmi_ppm_total` smallint(6) NULL DEFAULT NULL,
  `gmi_ppm_completed` smallint(6) NULL DEFAULT NULL,
  `gmi_ppm_on_time` smallint(6) NULL DEFAULT NULL,
  `gmi_ppm_late` smallint(6) NULL DEFAULT NULL,
  `gmi_ppm_within` smallint(6) NULL DEFAULT NULL,
  `gmi_ppm_rework` smallint(6) NULL DEFAULT NULL,
  `gmi_ppm_assist` smallint(6) NULL DEFAULT NULL,
  `gmi_wo_tier_name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `gmi_wo_tier_point` decimal(3, 1) NULL DEFAULT NULL,
  `gmi_wo_total` smallint(6) NULL DEFAULT NULL,
  `gmi_wo_completed` smallint(6) NULL DEFAULT NULL,
  `gmi_wo_on_time` smallint(6) NULL DEFAULT NULL,
  `gmi_wo_late` smallint(6) NULL DEFAULT NULL,
  `gmi_wo_rework` smallint(6) NULL DEFAULT NULL,
  `gmi_wo_self_finding` smallint(6) NULL DEFAULT NULL,
  `gmi_wo_assist` smallint(6) NULL DEFAULT NULL,
  `gmi_mbv` smallint(6) NULL DEFAULT NULL,
  `gmi_tier_point` tinyint(1) NULL DEFAULT NULL,
  `gmi_point_completed` int(11) NULL DEFAULT NULL,
  `gmi_point_on_time` int(11) NULL DEFAULT NULL,
  `gmi_point_late` int(11) NULL DEFAULT NULL,
  `gmi_point_rework` int(11) NULL DEFAULT NULL,
  `gmi_point_self_finding` int(11) NULL DEFAULT NULL,
  `gmi_point_total` int(11) NULL DEFAULT NULL,
  `gmi_productivity_level` decimal(6, 2) NULL DEFAULT NULL,
  `gmi_productivity_deduction` decimal(6, 2) NULL DEFAULT NULL,
  `gmi_point_less_productive` int(11) NULL DEFAULT NULL,
  `gmi_point_before_minus` int(11) NULL DEFAULT NULL,
  `gmi_point_after_minus` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`gmi_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  CONSTRAINT `gmi_monthly_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `gmi_monthly_ibfk_2` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 4097 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for kpi
-- ----------------------------
DROP TABLE IF EXISTS `kpi`;
CREATE TABLE `kpi`  (
  `kpi_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `site_id` smallint(6) NOT NULL,
  `kpi_info_version` tinyint(4) NOT NULL DEFAULT 1,
  `kpi_year` smallint(6) NOT NULL,
  `kpi_month` tinyint(4) NOT NULL,
  `kpi_portion_perc` tinyint(4) NOT NULL DEFAULT 5,
  `kpi_portion_total_fee` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `kpi_last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`kpi_id`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  CONSTRAINT `kpi_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 18 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for kpi_info
-- ----------------------------
DROP TABLE IF EXISTS `kpi_info`;
CREATE TABLE `kpi_info`  (
  `kpi_info_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `site_id` smallint(6) NOT NULL,
  `kpi_info_version` tinyint(4) NOT NULL DEFAULT 1,
  `kpi_info_category` tinyint(4) NOT NULL,
  `kpi_info_service_description` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kpi_info_performance_measure` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kpi_info_target_value` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kpi_info_source_of_data` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kpi_info_weightage` decimal(3, 3) NOT NULL DEFAULT 0.000,
  `kpi_info_ncp` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kpi_info_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kpi_info_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`kpi_info_id`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  CONSTRAINT `kpi_info_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 14 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for kpi_ppns
-- ----------------------------
DROP TABLE IF EXISTS `kpi_ppns`;
CREATE TABLE `kpi_ppns`  (
  `kpi_ppns_id` int(11) NOT NULL AUTO_INCREMENT,
  `kpi_id` smallint(6) NOT NULL,
  `kpi_ppns_category` tinyint(4) NULL DEFAULT NULL,
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
  `kpi_ppns_time_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`kpi_ppns_id`) USING BTREE,
  INDEX `kpi_id`(`kpi_id`) USING BTREE,
  CONSTRAINT `kpi_ppns_ibfk_1` FOREIGN KEY (`kpi_id`) REFERENCES `kpi` (`kpi_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 242 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for material_returns
-- ----------------------------
DROP TABLE IF EXISTS `material_returns`;
CREATE TABLE `material_returns`  (
  `return_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `wo_task_parts_id` bigint(20) NOT NULL COMMENT 'Reference to original material collection',
  `part_id` bigint(20) NOT NULL COMMENT 'Reference to parts table',
  `technician_user_id` int(11) NOT NULL COMMENT 'User who requested the return',
  `storekeeper_user_id` int(11) NULL DEFAULT NULL COMMENT 'User who confirmed the return',
  `quantity_returned` int(11) NOT NULL COMMENT 'Quantity being returned (supports partial returns)',
  `return_status` enum('pending','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'pending' COMMENT 'Return workflow status',
  `return_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Reason from dropdown: unused_excess, wrong_part, damaged, other',
  `return_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Optional free text remarks',
  `return_request_date` datetime NOT NULL COMMENT 'When technician submitted return',
  `return_confirmed_date` datetime NULL DEFAULT NULL COMMENT 'When storekeeper confirmed receipt',
  `return_deadline_date` datetime NULL DEFAULT NULL COMMENT 'Optional deadline for return (not enforced)',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`return_id`) USING BTREE,
  INDEX `idx_technician`(`technician_user_id`) USING BTREE,
  INDEX `idx_storekeeper`(`storekeeper_user_id`) USING BTREE,
  INDEX `idx_status`(`return_status`) USING BTREE,
  INDEX `idx_request_date`(`return_request_date`) USING BTREE,
  INDEX `idx_wo_task_parts`(`wo_task_parts_id`) USING BTREE,
  INDEX `idx_part_id`(`part_id`) USING BTREE,
  CONSTRAINT `material_returns_ibfk_1` FOREIGN KEY (`wo_task_parts_id`) REFERENCES `wo_task_parts` (`wo_task_parts_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `material_returns_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `ast_part` (`part_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `material_returns_ibfk_3` FOREIGN KEY (`technician_user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `material_returns_ibfk_4` FOREIGN KEY (`storekeeper_user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Material return requests and confirmations' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for noti_log
-- ----------------------------
DROP TABLE IF EXISTS `noti_log`;
CREATE TABLE `noti_log`  (
  `noti_log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `noti_text_id` smallint(6) NULL DEFAULT NULL,
  `noti_to` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `noti_title` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `noti_html` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `user_id` int(11) NULL DEFAULT NULL,
  `noti_id` bigint(20) NULL DEFAULT NULL,
  `noti_log_time_sent` timestamp NOT NULL DEFAULT current_timestamp(),
  `noti_log_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`noti_log_id`) USING BTREE,
  INDEX `noti_log_status`(`noti_log_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 12200209 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for noti_parameter
-- ----------------------------
DROP TABLE IF EXISTS `noti_parameter`;
CREATE TABLE `noti_parameter`  (
  `noti_param_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `noti_text_id` smallint(6) NOT NULL,
  `noti_param_code` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `noti_param_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`noti_param_id`) USING BTREE,
  INDEX `noti_text_id`(`noti_text_id`) USING BTREE,
  CONSTRAINT `noti_parameter_ibfk_1` FOREIGN KEY (`noti_text_id`) REFERENCES `noti_text` (`noti_text_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 34 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for noti_send
-- ----------------------------
DROP TABLE IF EXISTS `noti_send`;
CREATE TABLE `noti_send`  (
  `noti_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `noti_text_id` smallint(6) NULL DEFAULT NULL,
  `noti_to` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `noti_title` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `noti_html` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `user_id` int(11) NULL DEFAULT NULL,
  `noti_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`noti_id`) USING BTREE,
  INDEX `noti_text_id`(`noti_text_id`) USING BTREE,
  CONSTRAINT `noti_send_ibfk_1` FOREIGN KEY (`noti_text_id`) REFERENCES `noti_text` (`noti_text_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 12430535 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for noti_text
-- ----------------------------
DROP TABLE IF EXISTS `noti_text`;
CREATE TABLE `noti_text`  (
  `noti_text_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `noti_text_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `noti_text_title` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `noti_text_html` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `noti_text_status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`noti_text_id`) USING BTREE,
  INDEX `noti_text_status`(`noti_text_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 26 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for noti_web
-- ----------------------------
DROP TABLE IF EXISTS `noti_web`;
CREATE TABLE `noti_web`  (
  `noti_web_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `noti_web_type` tinyint(4) NOT NULL,
  `user_id` int(11) NOT NULL,
  `noti_web_title` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `noti_web_text` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `noti_web_icon` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `noti_web_color` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `noti_web_link` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `noti_web_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`noti_web_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ppm
-- ----------------------------
DROP TABLE IF EXISTS `ppm`;
CREATE TABLE `ppm`  (
  `ppm_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ppm_task_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_issue_no` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_date_start` date NULL DEFAULT NULL,
  `asset_id` bigint(20) NOT NULL,
  `checklist_id` int(11) NULL DEFAULT NULL,
  `contract_id` smallint(6) NOT NULL,
  `ppm_group_id` smallint(6) NULL DEFAULT NULL,
  `ppm_created_by` int(11) NULL DEFAULT NULL,
  `ppm_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `ppm_status` tinyint(4) NOT NULL DEFAULT 1,
  `ppm_is_routine` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ppm_id`) USING BTREE,
  INDEX `asset_id`(`asset_id`) USING BTREE,
  INDEX `ppm_status`(`ppm_status`) USING BTREE,
  INDEX `contract_id`(`contract_id`) USING BTREE,
  INDEX `checklist_id`(`checklist_id`) USING BTREE,
  INDEX `ppm_created_by`(`ppm_created_by`) USING BTREE,
  INDEX `ppm_group_id`(`ppm_group_id`) USING BTREE,
  CONSTRAINT `ppm_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `ast_asset` (`asset_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_ibfk_2` FOREIGN KEY (`contract_id`) REFERENCES `cli_contract` (`contract_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_ibfk_3` FOREIGN KEY (`checklist_id`) REFERENCES `ppm_checklist` (`checklist_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_ibfk_4` FOREIGN KEY (`ppm_created_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_ibfk_5` FOREIGN KEY (`ppm_group_id`) REFERENCES `ppm_group` (`ppm_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 62581 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ppm_checklist
-- ----------------------------
DROP TABLE IF EXISTS `ppm_checklist`;
CREATE TABLE `ppm_checklist`  (
  `checklist_id` int(11) NOT NULL AUTO_INCREMENT,
  `checklist_type` tinyint(1) NOT NULL DEFAULT 1,
  `checklist_name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `checklist_document_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `checklist_issue_no` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `checklist_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `checklist_guideline` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `asset_type_id` smallint(6) NULL DEFAULT NULL,
  `pdf_id` bigint(20) NULL DEFAULT NULL,
  `checklist_min_exec_time` time NULL DEFAULT NULL,
  `checklist_max_exec_time` time NULL DEFAULT NULL,
  `checklist_max_assistant` tinyint(4) NULL DEFAULT NULL,
  `checklist_time_registered` timestamp NULL DEFAULT NULL,
  `checklist_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `checklist_registered_by` int(11) NULL DEFAULT NULL,
  `checklist_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`checklist_id`) USING BTREE,
  INDEX `asset_type_id`(`asset_type_id`) USING BTREE,
  INDEX `checklist_status`(`checklist_status`) USING BTREE,
  INDEX `checklist_registered_by`(`checklist_registered_by`) USING BTREE,
  INDEX `pdf_id`(`pdf_id`) USING BTREE,
  CONSTRAINT `ppm_checklist_ibfk_3` FOREIGN KEY (`asset_type_id`) REFERENCES `ast_asset_type` (`asset_type_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_checklist_ibfk_6` FOREIGN KEY (`checklist_registered_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_checklist_ibfk_7` FOREIGN KEY (`pdf_id`) REFERENCES `sys_pdf` (`pdf_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1794 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ppm_checklist_qual
-- ----------------------------
DROP TABLE IF EXISTS `ppm_checklist_qual`;
CREATE TABLE `ppm_checklist_qual`  (
  `checklist_qual_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `checklist_qual_desc` varchar(1000) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `checklist_qual_numb` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `frequency_id` tinyint(4) NULL DEFAULT NULL,
  `checklist_id` int(11) NOT NULL,
  `checklist_qual_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`checklist_qual_id`) USING BTREE,
  INDEX `checklist_id`(`checklist_id`) USING BTREE,
  INDEX `frequency_id`(`frequency_id`) USING BTREE,
  INDEX `checklist_qual_numb`(`checklist_qual_numb`) USING BTREE,
  INDEX `checklist_qual_status`(`checklist_qual_status`) USING BTREE,
  CONSTRAINT `ppm_checklist_qual_ibfk_1` FOREIGN KEY (`checklist_id`) REFERENCES `ppm_checklist` (`checklist_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_checklist_qual_ibfk_2` FOREIGN KEY (`frequency_id`) REFERENCES `ppm_frequency` (`frequency_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 20486 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ppm_checklist_quan
-- ----------------------------
DROP TABLE IF EXISTS `ppm_checklist_quan`;
CREATE TABLE `ppm_checklist_quan`  (
  `checklist_quan_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `checklist_quan_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `checklist_quan_numb` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `checklist_quan_unit` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `checklist_quan_set_values` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `frequency_id` tinyint(4) NULL DEFAULT NULL,
  `checklist_id` int(11) NOT NULL,
  `checklist_quan_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`checklist_quan_id`) USING BTREE,
  INDEX `checklist_id`(`checklist_id`) USING BTREE,
  INDEX `frequency_id`(`frequency_id`) USING BTREE,
  INDEX `checklist_quan_numb`(`checklist_quan_numb`) USING BTREE,
  CONSTRAINT `ppm_checklist_quan_ibfk_1` FOREIGN KEY (`checklist_id`) REFERENCES `ppm_checklist` (`checklist_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_checklist_quan_ibfk_2` FOREIGN KEY (`frequency_id`) REFERENCES `ppm_frequency` (`frequency_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 69694 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ppm_frequency
-- ----------------------------
DROP TABLE IF EXISTS `ppm_frequency`;
CREATE TABLE `ppm_frequency`  (
  `frequency_id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `frequency_name` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `frequency_code` varchar(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `frequency_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `frequency_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`frequency_id`) USING BTREE,
  INDEX `frequency_id`(`frequency_id`) USING BTREE,
  INDEX `frequency_id_2`(`frequency_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ppm_group
-- ----------------------------
DROP TABLE IF EXISTS `ppm_group`;
CREATE TABLE `ppm_group`  (
  `ppm_group_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `ppm_group_name` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `site_id` smallint(6) NOT NULL,
  `role_id` tinyint(4) NOT NULL,
  `ppm_group_report_to` smallint(6) NULL DEFAULT NULL,
  `ppm_group_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ppm_group_id`) USING BTREE,
  INDEX `role_id`(`role_id`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  INDEX `ppm_group_status`(`ppm_group_status`) USING BTREE,
  INDEX `ppm_report_to`(`ppm_group_report_to`) USING BTREE,
  CONSTRAINT `ppm_group_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_group_ibfk_2` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_group_ibfk_3` FOREIGN KEY (`ppm_group_report_to`) REFERENCES `ppm_group` (`ppm_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 741 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ppm_group_user
-- ----------------------------
DROP TABLE IF EXISTS `ppm_group_user`;
CREATE TABLE `ppm_group_user`  (
  `ppm_group_user_id` int(11) NOT NULL AUTO_INCREMENT,
  `ppm_group_id` smallint(6) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`ppm_group_user_id`) USING BTREE,
  INDEX `ppm_group_id`(`ppm_group_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  CONSTRAINT `ppm_group_user_ibfk_1` FOREIGN KEY (`ppm_group_id`) REFERENCES `ppm_group` (`ppm_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_group_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 8204 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ppm_offline_sync_log
-- ----------------------------
DROP TABLE IF EXISTS `ppm_offline_sync_log`;
CREATE TABLE `ppm_offline_sync_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ppm_task_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'PPM Task ID being synced',
  `sync_timestamp` datetime NOT NULL COMMENT 'Client-provided sync timestamp for idempotency',
  `device_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mobile device identifier',
  `user_id` int(11) NOT NULL COMMENT 'User performing the sync',
  `total_actions` int(11) NOT NULL DEFAULT 0 COMMENT 'Total number of actions in batch',
  `success_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of successful actions',
  `failed_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of failed actions',
  `request_payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Full JSON request body',
  `response_payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Full JSON response body',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Server timestamp when sync was processed',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_sync`(`ppm_task_id`, `sync_timestamp`, `device_id`) USING BTREE,
  INDEX `idx_ppm_task`(`ppm_task_id`) USING BTREE,
  INDEX `idx_user`(`user_id`) USING BTREE,
  INDEX `idx_created`(`created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Tracks PPM offline batch sync attempts for idempotency' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ppm_task
-- ----------------------------
DROP TABLE IF EXISTS `ppm_task`;
CREATE TABLE `ppm_task`  (
  `ppm_task_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ppm_task_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_schedule_date` date NULL DEFAULT NULL,
  `ppm_task_start_date` date NULL DEFAULT NULL,
  `ppm_id` bigint(20) NOT NULL,
  `ppm_task_guideline` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_is_parts` tinyint(1) NULL DEFAULT NULL,
  `ppm_task_is_additional_report` tinyint(1) NULL DEFAULT NULL,
  `ppm_task_refer_to` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `transaction_id` bigint(20) NOT NULL,
  `pdf_id` bigint(20) NULL DEFAULT NULL,
  `ppm_task_is_scheduled` tinyint(1) NOT NULL DEFAULT 2,
  `ppm_task_assigned_to` int(11) NULL DEFAULT NULL,
  `ppm_task_min_exec_time` time NULL DEFAULT NULL,
  `ppm_task_max_exec_time` time NULL DEFAULT NULL,
  `ppm_task_max_assistant` tinyint(1) NULL DEFAULT NULL,
  `ppm_task_serviced_by` int(11) NULL DEFAULT NULL,
  `ppm_task_checked_by` int(11) NULL DEFAULT NULL,
  `ppm_task_verified_by` int(11) NULL DEFAULT NULL,
  `ppm_task_time_start` timestamp NULL DEFAULT NULL,
  `ppm_task_time_serviced` timestamp NULL DEFAULT NULL,
  `ppm_task_time_checked` timestamp NULL DEFAULT NULL,
  `ppm_task_time_verified` timestamp NULL DEFAULT NULL,
  `ppm_task_time_assigned` timestamp NOT NULL DEFAULT current_timestamp(),
  `ppm_task_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ppm_task_id`) USING BTREE,
  INDEX `ppm_id`(`ppm_id`) USING BTREE,
  INDEX `ppm_task_status`(`ppm_task_status`) USING BTREE,
  INDEX `ppm_submitted_by`(`ppm_task_serviced_by`) USING BTREE,
  INDEX `ppm_task_checked_by`(`ppm_task_checked_by`) USING BTREE,
  INDEX `ppm_task_verified_by`(`ppm_task_verified_by`) USING BTREE,
  INDEX `transaction_id`(`transaction_id`) USING BTREE,
  INDEX `ppm_task_assigned_to`(`ppm_task_assigned_to`) USING BTREE,
  INDEX `pdf_id`(`pdf_id`) USING BTREE,
  INDEX `ppm_task_schedule_date`(`ppm_task_schedule_date`) USING BTREE,
  INDEX `ppm_task_start_date`(`ppm_task_start_date`) USING BTREE,
  INDEX `ppm_task_id`(`ppm_task_id`) USING BTREE,
  INDEX `ppm_task_id_2`(`ppm_task_id`) USING BTREE,
  CONSTRAINT `ppm_task_ibfk_1` FOREIGN KEY (`ppm_id`) REFERENCES `ppm` (`ppm_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_ibfk_2` FOREIGN KEY (`ppm_task_serviced_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_ibfk_3` FOREIGN KEY (`ppm_task_checked_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_ibfk_4` FOREIGN KEY (`ppm_task_verified_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_ibfk_6` FOREIGN KEY (`ppm_task_assigned_to`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_ibfk_7` FOREIGN KEY (`pdf_id`) REFERENCES `sys_pdf` (`pdf_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 5510054 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ppm_task_assist
-- ----------------------------
DROP TABLE IF EXISTS `ppm_task_assist`;
CREATE TABLE `ppm_task_assist`  (
  `ppm_task_assist_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ppm_task_id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`ppm_task_assist_id`) USING BTREE,
  INDEX `ppm_task_id`(`ppm_task_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  CONSTRAINT `ppm_task_assist_ibfk_1` FOREIGN KEY (`ppm_task_id`) REFERENCES `ppm_task` (`ppm_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_assist_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 33966 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ppm_task_frequency
-- ----------------------------
DROP TABLE IF EXISTS `ppm_task_frequency`;
CREATE TABLE `ppm_task_frequency`  (
  `ppm_task_freq_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ppm_task_id` bigint(20) NOT NULL,
  `frequency_id` tinyint(4) NOT NULL,
  PRIMARY KEY (`ppm_task_freq_id`) USING BTREE,
  INDEX `ppm_task_id`(`ppm_task_id`) USING BTREE,
  INDEX `frequency_id`(`frequency_id`) USING BTREE,
  CONSTRAINT `ppm_task_frequency_ibfk_1` FOREIGN KEY (`ppm_task_id`) REFERENCES `ppm_task` (`ppm_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_frequency_ibfk_2` FOREIGN KEY (`frequency_id`) REFERENCES `ppm_frequency` (`frequency_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 5690187 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ppm_task_parts
-- ----------------------------
DROP TABLE IF EXISTS `ppm_task_parts`;
CREATE TABLE `ppm_task_parts`  (
  `ppm_task_parts_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ppm_task_parts_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `ppm_task_id` bigint(20) NOT NULL,
  PRIMARY KEY (`ppm_task_parts_id`) USING BTREE,
  INDEX `ppm_task_id`(`ppm_task_id`) USING BTREE,
  CONSTRAINT `ppm_task_parts_ibfk_1` FOREIGN KEY (`ppm_task_id`) REFERENCES `ppm_task` (`ppm_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 150596 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ppm_task_qual
-- ----------------------------
DROP TABLE IF EXISTS `ppm_task_qual`;
CREATE TABLE `ppm_task_qual`  (
  `ppm_task_qual_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ppm_task_qual_numb` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_qual_desc` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `frequency_id` tinyint(4) NULL DEFAULT NULL,
  `ppm_task_qual_result` tinyint(1) NULL DEFAULT NULL COMMENT '0=fail, 1=pass, 2=N/A',
  `ppm_task_qual_remark` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_id` bigint(20) NOT NULL,
  `checklist_qual_id` bigint(20) NOT NULL,
  PRIMARY KEY (`ppm_task_qual_id`) USING BTREE,
  INDEX `ppm_task_id`(`ppm_task_id`) USING BTREE,
  INDEX `checklist_qual_id`(`checklist_qual_id`) USING BTREE,
  INDEX `frequency_id`(`frequency_id`) USING BTREE,
  CONSTRAINT `ppm_task_qual_ibfk_1` FOREIGN KEY (`ppm_task_id`) REFERENCES `ppm_task` (`ppm_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_qual_ibfk_3` FOREIGN KEY (`frequency_id`) REFERENCES `ppm_frequency` (`frequency_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 36164084 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ppm_task_quan
-- ----------------------------
DROP TABLE IF EXISTS `ppm_task_quan`;
CREATE TABLE `ppm_task_quan`  (
  `ppm_task_quan_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ppm_task_quan_numb` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_quan_desc` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `frequency_id` tinyint(4) NULL DEFAULT NULL,
  `ppm_task_quan_unit` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_quan_set_values` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_quan_measured_values` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_quan_limit` varchar(100) CHARACTER SET latin2 COLLATE latin2_general_ci NULL DEFAULT NULL,
  `ppm_task_quan_result` tinyint(1) NULL DEFAULT NULL COMMENT '0=fail, 1=pass, 2=N/A',
  `ppm_task_quan_remark` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ppm_task_id` bigint(20) NOT NULL,
  `checklist_quan_id` bigint(20) NOT NULL,
  PRIMARY KEY (`ppm_task_quan_id`) USING BTREE,
  INDEX `ppm_task_id`(`ppm_task_id`) USING BTREE,
  INDEX `checklist_quan_id`(`checklist_quan_id`) USING BTREE,
  INDEX `frequency_id`(`frequency_id`) USING BTREE,
  CONSTRAINT `ppm_task_quan_ibfk_1` FOREIGN KEY (`ppm_task_id`) REFERENCES `ppm_task` (`ppm_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_quan_ibfk_3` FOREIGN KEY (`frequency_id`) REFERENCES `ppm_frequency` (`frequency_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 38864988 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ppm_task_section
-- ----------------------------
DROP TABLE IF EXISTS `ppm_task_section`;
CREATE TABLE `ppm_task_section`  (
  `ppm_task_section_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ppm_task_section_name` varchar(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `ppm_task_id` bigint(20) NOT NULL,
  `ppm_task_section_status` tinyint(4) NOT NULL DEFAULT 17,
  PRIMARY KEY (`ppm_task_section_id`) USING BTREE,
  INDEX `ppm_task_id`(`ppm_task_id`) USING BTREE,
  INDEX `ppm_task_section_status`(`ppm_task_section_status`) USING BTREE,
  CONSTRAINT `ppm_task_section_ibfk_1` FOREIGN KEY (`ppm_task_id`) REFERENCES `ppm_task` (`ppm_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 44181972 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ppm_task_upload
-- ----------------------------
DROP TABLE IF EXISTS `ppm_task_upload`;
CREATE TABLE `ppm_task_upload`  (
  `ppm_task_upload_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ppm_task_upload_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=before, 1=during, 2=after, 3=additional report, 4=signiture techician, 5=signiture checked, 6=signiture verified',
  `ppm_task_id` bigint(20) NOT NULL,
  `ppm_task_upload_longitude` double(12, 6) NULL DEFAULT NULL,
  `ppm_task_upload_latitude` double(12, 6) NULL DEFAULT NULL,
  `ppm_task_upload_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `upload_id` bigint(20) NOT NULL,
  `ppm_task_upload_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ppm_task_upload_id`) USING BTREE,
  INDEX `ppm_task_id`(`ppm_task_id`) USING BTREE,
  INDEX `upload_id`(`upload_id`) USING BTREE,
  CONSTRAINT `ppm_task_upload_ibfk_1` FOREIGN KEY (`ppm_task_id`) REFERENCES `ppm_task` (`ppm_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ppm_task_upload_ibfk_2` FOREIGN KEY (`upload_id`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1981128 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for pr
-- ----------------------------
DROP TABLE IF EXISTS `pr`;
CREATE TABLE `pr`  (
  `pr_id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint(20) NULL DEFAULT NULL,
  `site_id` smallint(6) NULL DEFAULT NULL,
  `pr_request_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `pr_quotation_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `pr_quotation_upload` bigint(20) NULL DEFAULT NULL,
  `pr_po_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `po_po_upload` bigint(20) NULL DEFAULT NULL,
  `supplier_id` smallint(6) NULL DEFAULT NULL,
  `pr_other_cost` decimal(10, 2) NULL DEFAULT NULL,
  `pr_total_cost` decimal(10, 2) NULL DEFAULT NULL,
  `pr_requested_by` int(11) NULL DEFAULT NULL,
  `pr_prepared_by` int(11) NULL DEFAULT NULL,
  `pr_approved_by` int(11) NULL DEFAULT NULL,
  `pr_sap_by` int(11) NULL DEFAULT NULL,
  `pr_po_by` int(11) NULL DEFAULT NULL,
  `pr_time_requested` timestamp NULL DEFAULT NULL,
  `pr_time_submit` timestamp NULL DEFAULT NULL,
  `pr_time_approved` timestamp NULL DEFAULT NULL,
  `pr_time_stocked` timestamp NULL DEFAULT NULL,
  `pr_time_sap` timestamp NULL DEFAULT NULL,
  `pr_time_po` timestamp NULL DEFAULT NULL,
  `pr_status` tinyint(4) NULL DEFAULT NULL,
  PRIMARY KEY (`pr_id`) USING BTREE,
  INDEX `supplier_id`(`supplier_id`) USING BTREE,
  INDEX `pr_request_by`(`pr_requested_by`) USING BTREE,
  INDEX `transaction_id`(`transaction_id`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  INDEX `pr_quotation_upload`(`pr_quotation_upload`) USING BTREE,
  INDEX `po_po_upload`(`po_po_upload`) USING BTREE,
  CONSTRAINT `pr_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `pr_supplier` (`supplier_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `pr_ibfk_2` FOREIGN KEY (`pr_requested_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `pr_ibfk_3` FOREIGN KEY (`transaction_id`) REFERENCES `wfl_transaction` (`transaction_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `pr_ibfk_4` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `pr_ibfk_5` FOREIGN KEY (`pr_quotation_upload`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `pr_ibfk_6` FOREIGN KEY (`po_po_upload`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for pr_item
-- ----------------------------
DROP TABLE IF EXISTS `pr_item`;
CREATE TABLE `pr_item`  (
  `pr_item_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `part_id` bigint(20) NULL DEFAULT NULL,
  `store_id` smallint(6) NULL DEFAULT NULL,
  `asset_group_id` smallint(6) NULL DEFAULT NULL,
  `item_type_id` smallint(6) NULL DEFAULT NULL,
  `item_id` smallint(6) NULL DEFAULT NULL,
  `pr_item_cost` decimal(12, 2) NULL DEFAULT NULL,
  `pr_item_request` smallint(6) NULL DEFAULT NULL,
  `pr_item_pr` smallint(6) NULL DEFAULT NULL,
  `pr_item_received` smallint(6) NULL DEFAULT NULL,
  `pr_item_status` tinyint(4) NULL DEFAULT NULL,
  PRIMARY KEY (`pr_item_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for pr_supplier
-- ----------------------------
DROP TABLE IF EXISTS `pr_supplier`;
CREATE TABLE `pr_supplier`  (
  `supplier_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `supplier_reg_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `supplier_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`supplier_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ptw_permit
-- ----------------------------
DROP TABLE IF EXISTS `ptw_permit`;
CREATE TABLE `ptw_permit`  (
  `ptw_permit_id` int(11) NOT NULL AUTO_INCREMENT,
  `ptw_permit_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_request_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_permit_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ptw_work_area` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ptw_work_type` enum('Cold Work','Hot Work','Confined Space') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ptw_work_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_risk_level` enum('LOW','MEDIUM','HIGH','CRITICAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LOW',
  `ptw_valid_from` datetime NOT NULL,
  `ptw_valid_to` datetime NOT NULL,
  `cancel_requested_by` int(11) NULL DEFAULT NULL,
  `cancel_requested_at` datetime NULL DEFAULT NULL,
  `ptw_extension_requested_to` datetime NULL DEFAULT NULL,
  `ptw_extension_requested_by` int(11) NULL DEFAULT NULL,
  `ptw_extension_requested_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_extension_requested_at` datetime NULL DEFAULT NULL,
  `ptw_contractor_company` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_contractor_supervisor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Contractor Supervisor Name',
  `ptw_staff_nric` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Staff NRIC/IC Number',
  `ptw_supervisor_contact` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Supervisor Contact Number',
  `ptw_identification_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Identification Number',
  `ptw_level` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Level/Floor',
  `ptw_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_status` enum('DRAFT','SUBMITTED','PENDING_SUPERVISOR','PENDING_SHE','PENDING_FM','APPROVED','ACTIVE','PENDING_CLOSURE','COMPLETED','PENDING_CANCELLATION','PENDING_SUSPENSION','PENDING_EXTENSION','CANCELLED','SUSPENDED','REJECTED','EXTENDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `ptw_applicant_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ptw_applicant_contact` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_applicant_company_dept` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_work_duration` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_hazards` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_control_measures` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_checklist_cold_work` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL CHECK (json_valid(`ptw_checklist_cold_work`)),
  `ptw_checklist_hot_work` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL CHECK (json_valid(`ptw_checklist_hot_work`)),
  `ptw_checklist_confined_space` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL CHECK (json_valid(`ptw_checklist_confined_space`)),
  `ptw_hazard_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL CHECK (json_valid(`ptw_hazard_checklist`)),
  `ptw_declaration_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL CHECK (json_valid(`ptw_declaration_checklist`)),
  `ptw_supporting_docs_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL CHECK (json_valid(`ptw_supporting_docs_checklist`)) COMMENT 'URS Supporting Documents Checklist',
  `ptw_certificate_numbers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL CHECK (json_valid(`ptw_certificate_numbers`)) COMMENT 'Certificate and Permit Numbers',
  `public_token` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `public_token_expires_at` datetime NULL DEFAULT NULL,
  `public_link_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `public_token_revoked_at` datetime NULL DEFAULT NULL,
  `ptw_complete_form_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `site_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) NULL DEFAULT NULL,
  `updated_date` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `approved_supervisor_by` int(11) NULL DEFAULT NULL,
  `approved_supervisor_date` timestamp NULL DEFAULT NULL,
  `ptw_supervisor_comments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_supervisor_approval_date` timestamp NULL DEFAULT NULL,
  `ptw_supervisor_id` int(11) NULL DEFAULT NULL,
  `ptw_supervisor_rejection_date` timestamp NULL DEFAULT NULL,
  `ptw_supervisor_approval` enum('PENDING','APPROVED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'PENDING',
  `ptw_she_approval` enum('PENDING','APPROVED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'PENDING',
  `approved_she_by` int(11) NULL DEFAULT NULL,
  `approved_she_date` timestamp NULL DEFAULT NULL,
  `ptw_she_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ptw_fm_approval` enum('PENDING','APPROVED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'PENDING',
  `approved_fm_by` int(11) NULL DEFAULT NULL,
  `approved_fm_date` timestamp NULL DEFAULT NULL,
  `ptw_fm_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `activated_by` int(11) NULL DEFAULT NULL,
  `activated_date` timestamp NULL DEFAULT NULL,
  `completed_by` int(11) NULL DEFAULT NULL,
  `completed_date` timestamp NULL DEFAULT NULL,
  `cancelled_by` int(11) NULL DEFAULT NULL,
  `cancelled_date` timestamp NULL DEFAULT NULL,
  `cancel_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `suspend_requested_by` int(11) NULL DEFAULT NULL,
  `suspend_requested_at` datetime NULL DEFAULT NULL,
  `suspend_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `suspend_ncr_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `suspended_by` int(11) NULL DEFAULT NULL,
  `suspended_date` datetime NULL DEFAULT NULL,
  `ptw_hazardous_activities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL CHECK (json_valid(`ptw_hazardous_activities`)),
  `ptw_contractor_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Stores contractor representative name',
  `ptw_contractor_designation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Stores contractor designation',
  `ptw_contractor_date` date NULL DEFAULT NULL COMMENT 'Stores acknowledgment date',
  PRIMARY KEY (`ptw_permit_id`) USING BTREE,
  UNIQUE INDEX `uk_ptw_permit_number`(`ptw_permit_number`) USING BTREE,
  UNIQUE INDEX `uk_ptw_request_number`(`ptw_request_number`) USING BTREE,
  INDEX `idx_ptw_site_id`(`site_id`) USING BTREE,
  INDEX `idx_ptw_status`(`ptw_status`) USING BTREE,
  INDEX `idx_ptw_created_by`(`created_by`) USING BTREE,
  INDEX `idx_ptw_valid_period`(`ptw_valid_from`, `ptw_valid_to`) USING BTREE,
  INDEX `idx_public_token`(`public_token`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ref_city
-- ----------------------------
DROP TABLE IF EXISTS `ref_city`;
CREATE TABLE `ref_city`  (
  `city_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `city_desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `state_id` smallint(6) NOT NULL,
  `city_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`city_id`) USING BTREE,
  INDEX `state_id`(`state_id`) USING BTREE,
  INDEX `city_status`(`city_status`) USING BTREE,
  INDEX `city_desc`(`city_desc`) USING BTREE,
  CONSTRAINT `ref_city_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `ref_state` (`state_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ref_country
-- ----------------------------
DROP TABLE IF EXISTS `ref_country`;
CREATE TABLE `ref_country`  (
  `country_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `country_desc` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `country_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`country_id`) USING BTREE,
  INDEX `ref_country_country_status_index`(`country_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 183 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ref_department
-- ----------------------------
DROP TABLE IF EXISTS `ref_department`;
CREATE TABLE `ref_department`  (
  `department_id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `department_desc` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `department_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`department_id`) USING BTREE,
  INDEX `department_status`(`department_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ref_designation
-- ----------------------------
DROP TABLE IF EXISTS `ref_designation`;
CREATE TABLE `ref_designation`  (
  `designation_id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `designation_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `designation_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`designation_id`) USING BTREE,
  INDEX `designation_status`(`designation_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ref_document
-- ----------------------------
DROP TABLE IF EXISTS `ref_document`;
CREATE TABLE `ref_document`  (
  `document_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `document_desc` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `document_type` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `document_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`document_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 27 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ref_failure_code
-- ----------------------------
DROP TABLE IF EXISTS `ref_failure_code`;
CREATE TABLE `ref_failure_code`  (
  `failure_code_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `failure_code_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `failure_code_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`failure_code_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ref_item
-- ----------------------------
DROP TABLE IF EXISTS `ref_item`;
CREATE TABLE `ref_item`  (
  `item_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `item_description` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `item_type_id` smallint(6) NULL DEFAULT NULL,
  `item_threshold` smallint(6) NULL DEFAULT NULL,
  `item_min_order` smallint(6) NULL DEFAULT NULL,
  `item_max_order` smallint(6) NULL DEFAULT NULL,
  `item_turn` tinyint(4) NULL DEFAULT NULL,
  `item_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `item_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`item_id`) USING BTREE,
  INDEX `item_type_id`(`item_type_id`) USING BTREE,
  INDEX `item_status`(`item_status`) USING BTREE,
  CONSTRAINT `ref_item_ibfk_1` FOREIGN KEY (`item_type_id`) REFERENCES `ref_item_type` (`item_type_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 571 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ref_item_image
-- ----------------------------
DROP TABLE IF EXISTS `ref_item_image`;
CREATE TABLE `ref_item_image`  (
  `item_image_id` int(11) NOT NULL AUTO_INCREMENT,
  `upload_id` bigint(20) NOT NULL,
  `item_id` smallint(6) NOT NULL,
  `item_image_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`item_image_id`) USING BTREE,
  INDEX `upload_id`(`upload_id`) USING BTREE,
  INDEX `item_id`(`item_id`) USING BTREE,
  CONSTRAINT `ref_item_image_ibfk_1` FOREIGN KEY (`upload_id`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ref_item_image_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `ref_item` (`item_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 416 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ref_item_type
-- ----------------------------
DROP TABLE IF EXISTS `ref_item_type`;
CREATE TABLE `ref_item_type`  (
  `item_type_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `asset_group_id` smallint(6) NULL DEFAULT NULL,
  `item_type_desc` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `item_type_turn` tinyint(4) NULL DEFAULT NULL,
  `item_type_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`item_type_id`) USING BTREE,
  INDEX `asset_group_id`(`asset_group_id`) USING BTREE,
  INDEX `item_type_status`(`item_type_status`) USING BTREE,
  CONSTRAINT `ref_item_type_ibfk_1` FOREIGN KEY (`asset_group_id`) REFERENCES `ast_asset_group` (`asset_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 122 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ref_role
-- ----------------------------
DROP TABLE IF EXISTS `ref_role`;
CREATE TABLE `ref_role`  (
  `role_id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `role_desc` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `role_type` tinyint(4) NULL DEFAULT NULL,
  `role_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`role_id`) USING BTREE,
  INDEX `ref_role_role_status_index`(`role_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 23 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ref_severity
-- ----------------------------
DROP TABLE IF EXISTS `ref_severity`;
CREATE TABLE `ref_severity`  (
  `severity_id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `severity_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `severity_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`severity_id`) USING BTREE,
  INDEX `severity_status`(`severity_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 20 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ref_state
-- ----------------------------
DROP TABLE IF EXISTS `ref_state`;
CREATE TABLE `ref_state`  (
  `state_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `country_id` smallint(6) NOT NULL,
  `state_desc` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `state_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`state_id`) USING BTREE,
  INDEX `ref_state_state_status_index`(`state_status`) USING BTREE,
  INDEX `ref_state_ref_country_country_id_fk`(`country_id`) USING BTREE,
  INDEX `state_desc`(`state_desc`) USING BTREE,
  CONSTRAINT `ref_state_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `ref_country` (`country_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 17 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ref_status
-- ----------------------------
DROP TABLE IF EXISTS `ref_status`;
CREATE TABLE `ref_status`  (
  `status_id` tinyint(4) NOT NULL,
  `status_desc` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `status_action` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `status_color` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `status_color_code` varchar(7) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`status_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci COMMENT = 'Reference status' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sys_address
-- ----------------------------
DROP TABLE IF EXISTS `sys_address`;
CREATE TABLE `sys_address`  (
  `address_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `address_desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `address_postcode` varchar(5) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `address_city` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `state_id` smallint(6) NULL DEFAULT NULL,
  PRIMARY KEY (`address_id`) USING BTREE,
  INDEX `state_id`(`state_id`) USING BTREE,
  CONSTRAINT `sys_address_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `ref_state` (`state_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sys_audit
-- ----------------------------
DROP TABLE IF EXISTS `sys_audit`;
CREATE TABLE `sys_audit`  (
  `audit_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NULL DEFAULT NULL,
  `audit_ip` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `audit_place` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `audit_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `audit_action_id` smallint(6) NOT NULL,
  `audit_remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`audit_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `audit_action_id`(`audit_action_id`) USING BTREE,
  CONSTRAINT `sys_audit_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_audit_ibfk_2` FOREIGN KEY (`audit_action_id`) REFERENCES `sys_audit_action` (`audit_action_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 8070787 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sys_audit_action
-- ----------------------------
DROP TABLE IF EXISTS `sys_audit_action`;
CREATE TABLE `sys_audit_action`  (
  `audit_action_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `audit_action_desc` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `audit_module_id` smallint(6) NOT NULL,
  `audit_action_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`audit_action_id`) USING BTREE,
  INDEX `audit_module_id`(`audit_module_id`) USING BTREE,
  INDEX `audit_action_status`(`audit_action_status`) USING BTREE,
  CONSTRAINT `sys_audit_action_ibfk_1` FOREIGN KEY (`audit_module_id`) REFERENCES `sys_audit_module` (`audit_module_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 217 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sys_audit_module
-- ----------------------------
DROP TABLE IF EXISTS `sys_audit_module`;
CREATE TABLE `sys_audit_module`  (
  `audit_module_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `audit_module_desc` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `audit_module_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`audit_module_id`) USING BTREE,
  INDEX `audit_module_status`(`audit_module_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 17 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sys_group
-- ----------------------------
DROP TABLE IF EXISTS `sys_group`;
CREATE TABLE `sys_group`  (
  `group_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `group_type` tinyint(4) NOT NULL COMMENT '1=Internal, 2=Company, 3=Public',
  `group_reg_no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `group_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `group_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`group_id`) USING BTREE,
  INDEX `group_status`(`group_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 25 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sys_location
-- ----------------------------
DROP TABLE IF EXISTS `sys_location`;
CREATE TABLE `sys_location`  (
  `location_id` int(11) NOT NULL AUTO_INCREMENT,
  `location_desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `location_status` tinyint(4) NOT NULL DEFAULT 1,
  `location_longitude` float(11, 6) NOT NULL,
  `location_latitude` float(11, 6) NOT NULL,
  PRIMARY KEY (`location_id`) USING BTREE,
  INDEX `location_status`(`location_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sys_nav
-- ----------------------------
DROP TABLE IF EXISTS `sys_nav`;
CREATE TABLE `sys_nav`  (
  `nav_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `nav_desc` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `nav_page` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `nav_icon` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `nav_status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`nav_id`) USING BTREE,
  INDEX `sys_nav_nav_status_index`(`nav_status`) USING BTREE,
  INDEX `sys_nav_nav_status_index_2`(`nav_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 23 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci COMMENT = 'Left navigation' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sys_nav_role
-- ----------------------------
DROP TABLE IF EXISTS `sys_nav_role`;
CREATE TABLE `sys_nav_role`  (
  `nav_role_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `role_id` tinyint(4) NOT NULL,
  `nav_id` smallint(6) NOT NULL,
  `nav_second_id` smallint(6) NULL DEFAULT NULL,
  `nav_role_turn` smallint(6) NOT NULL DEFAULT 1,
  PRIMARY KEY (`nav_role_id`) USING BTREE,
  INDEX `sys_nav_role_sys_nav_nav_id_fk`(`nav_id`) USING BTREE,
  INDEX `sys_nav_role_ref_role_role_id_fk`(`role_id`) USING BTREE,
  INDEX `nav_role_turn`(`nav_role_turn`) USING BTREE,
  INDEX `nav_second_id`(`nav_second_id`) USING BTREE,
  CONSTRAINT `sys_nav_role_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_nav_role_ibfk_2` FOREIGN KEY (`nav_id`) REFERENCES `sys_nav` (`nav_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_nav_role_ibfk_3` FOREIGN KEY (`nav_second_id`) REFERENCES `sys_nav_second` (`nav_second_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 316 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sys_nav_second
-- ----------------------------
DROP TABLE IF EXISTS `sys_nav_second`;
CREATE TABLE `sys_nav_second`  (
  `nav_second_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `nav_id` smallint(6) NOT NULL,
  `nav_second_desc` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `nav_second_page` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `nav_second_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`nav_second_id`) USING BTREE,
  INDEX `sys_nav_second_sys_nav_nav_id_fk`(`nav_id`) USING BTREE,
  INDEX `sys_nav_second_nav_second_status_index`(`nav_second_status`) USING BTREE,
  CONSTRAINT `sys_nav_second_ibfk_1` FOREIGN KEY (`nav_id`) REFERENCES `sys_nav` (`nav_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 52 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sys_pdf
-- ----------------------------
DROP TABLE IF EXISTS `sys_pdf`;
CREATE TABLE `sys_pdf`  (
  `pdf_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pdf_type` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `pdf_folder` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `pdf_filename` varchar(80) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `pdf_timeCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `pdf_time_update` timestamp NOT NULL DEFAULT current_timestamp(),
  `pdf_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`pdf_id`) USING BTREE,
  INDEX `pdf_type`(`pdf_type`) USING BTREE,
  INDEX `pdf_status`(`pdf_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 469072 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sys_upload
-- ----------------------------
DROP TABLE IF EXISTS `sys_upload`;
CREATE TABLE `sys_upload`  (
  `upload_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `document_id` smallint(6) NOT NULL,
  `upload_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `upload_uplname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `upload_filename` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `upload_extension` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `upload_folder` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `upload_filesize` int(11) NULL DEFAULT NULL,
  `upload_file_width` int(11) NULL DEFAULT NULL,
  `upload_file_height` int(11) NULL DEFAULT NULL,
  `upload_blob_type` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `upload_blob_data` blob NULL DEFAULT NULL,
  `upload_time_upload` timestamp NOT NULL DEFAULT current_timestamp(),
  `upload_created_by` int(11) NULL DEFAULT NULL,
  `upload_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`upload_id`) USING BTREE,
  INDEX `document_id`(`document_id`) USING BTREE,
  INDEX `upload_status`(`upload_status`) USING BTREE,
  CONSTRAINT `sys_upload_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `ref_document` (`document_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 3186394 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sys_user
-- ----------------------------
DROP TABLE IF EXISTS `sys_user`;
CREATE TABLE `sys_user`  (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `user_password` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `user_password_temp` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `user_type` tinyint(4) NOT NULL DEFAULT 1,
  `user_activation_key` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `user_first_name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `user_last_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `user_mykad_no` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `user_passport_no` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `site_id` smallint(6) NULL DEFAULT NULL,
  `user_device_id` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `upload_id` bigint(20) NULL DEFAULT NULL,
  `user_fail_attempt` tinyint(1) NULL DEFAULT 0,
  `user_time_created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  `user_time_activate` timestamp NULL DEFAULT NULL,
  `user_time_login` timestamp NULL DEFAULT NULL,
  `user_time_block` timestamp NULL DEFAULT NULL,
  `user_token` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `user_signature` bigint(20) NULL DEFAULT NULL,
  `user_status` tinyint(4) NOT NULL DEFAULT 3,
  PRIMARY KEY (`user_id`) USING BTREE,
  UNIQUE INDEX `user_name`(`user_name`) USING BTREE,
  INDEX `user_status`(`user_status`) USING BTREE,
  INDEX `user_type`(`user_type`) USING BTREE,
  INDEX `user_password`(`user_password`) USING BTREE,
  INDEX `upload_id`(`upload_id`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  INDEX `user_signature`(`user_signature`) USING BTREE,
  CONSTRAINT `sys_user_ibfk_1` FOREIGN KEY (`user_status`) REFERENCES `ref_status` (`status_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_ibfk_2` FOREIGN KEY (`upload_id`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_ibfk_3` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_ibfk_4` FOREIGN KEY (`user_signature`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1529 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci COMMENT = 'System user' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sys_user_group
-- ----------------------------
DROP TABLE IF EXISTS `sys_user_group`;
CREATE TABLE `sys_user_group`  (
  `user_group_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `group_id` smallint(6) NOT NULL,
  PRIMARY KEY (`user_group_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `group_id`(`group_id`) USING BTREE,
  CONSTRAINT `sys_user_group_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_group_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1512 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sys_user_profile
-- ----------------------------
DROP TABLE IF EXISTS `sys_user_profile`;
CREATE TABLE `sys_user_profile`  (
  `user_profile_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `address_id` bigint(20) NULL DEFAULT NULL,
  `user_contact_no` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `user_email` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `department_id` tinyint(4) NULL DEFAULT NULL,
  `designation_id` tinyint(4) NULL DEFAULT NULL,
  `user_profile_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_profile_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `address_id`(`address_id`) USING BTREE,
  INDEX `user_profile_status`(`user_profile_status`) USING BTREE,
  INDEX `department_id`(`department_id`) USING BTREE,
  INDEX `designation_id`(`designation_id`) USING BTREE,
  CONSTRAINT `sys_user_profile_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_profile_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `sys_address` (`address_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_profile_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `ref_department` (`department_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_profile_ibfk_4` FOREIGN KEY (`designation_id`) REFERENCES `ref_designation` (`designation_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1512 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sys_user_role
-- ----------------------------
DROP TABLE IF EXISTS `sys_user_role`;
CREATE TABLE `sys_user_role`  (
  `user_role_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_id` tinyint(4) NOT NULL,
  `group_id` smallint(6) NOT NULL,
  `user_role_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_role_id`) USING BTREE,
  INDEX `sys_user_role_sys_user_user_id_fk`(`user_id`) USING BTREE,
  INDEX `sys_user_role_ref_role_role_id_fk`(`role_id`) USING BTREE,
  INDEX `group_id`(`group_id`) USING BTREE,
  CONSTRAINT `sys_user_role_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_role_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sys_user_role_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 6003 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sys_version
-- ----------------------------
DROP TABLE IF EXISTS `sys_version`;
CREATE TABLE `sys_version`  (
  `version_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `version_name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `version_no` int(11) NOT NULL DEFAULT 1,
  `version_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`version_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 37 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for utl_meter
-- ----------------------------
DROP TABLE IF EXISTS `utl_meter`;
CREATE TABLE `utl_meter`  (
  `meter_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `site_id` smallint(6) NULL DEFAULT NULL,
  `meter_type` enum('Water','Electricity') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `meter_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `meter_location` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `meter_status` tinyint(4) NULL DEFAULT NULL,
  PRIMARY KEY (`meter_id`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  CONSTRAINT `utl_meter_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 70 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for utl_utility
-- ----------------------------
DROP TABLE IF EXISTS `utl_utility`;
CREATE TABLE `utl_utility`  (
  `utility_id` int(11) NOT NULL AUTO_INCREMENT,
  `utility_date` date NULL DEFAULT NULL,
  `utility_type` enum('Water','Electricity') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `utility_reading_type` enum('Daily','Monthly') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `utility_shift` enum('Night','Evening','Morning') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `site_id` smallint(6) NULL DEFAULT NULL,
  `meter_id` smallint(6) NULL DEFAULT NULL,
  `utility_opening` decimal(12, 3) NULL DEFAULT NULL,
  `utility_reading` double(12, 3) NULL DEFAULT NULL,
  `utility_total` decimal(12, 3) NULL DEFAULT NULL,
  `utility_unit` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `utility_max_demand` double(12, 3) NULL DEFAULT NULL,
  `utility_total_rm` double(12, 2) NULL DEFAULT NULL,
  `utility_image` bigint(20) NULL DEFAULT NULL,
  `utility_recorded_by` int(11) NULL DEFAULT NULL,
  `utility_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`utility_id`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  INDEX `meter_id`(`meter_id`) USING BTREE,
  INDEX `utility_recorded_by`(`utility_recorded_by`) USING BTREE,
  INDEX `utility_image`(`utility_image`) USING BTREE,
  CONSTRAINT `utl_utility_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `utl_utility_ibfk_2` FOREIGN KEY (`meter_id`) REFERENCES `utl_meter` (`meter_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `utl_utility_ibfk_3` FOREIGN KEY (`utility_recorded_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `utl_utility_ibfk_4` FOREIGN KEY (`utility_image`) REFERENCES `sys_upload` (`upload_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 5377 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for wfl_checkpoint
-- ----------------------------
DROP TABLE IF EXISTS `wfl_checkpoint`;
CREATE TABLE `wfl_checkpoint`  (
  `checkpoint_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `flow_id` tinyint(4) NOT NULL,
  `checkpoint_desc` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `checkpoint_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1=Start, 2=Normal, 3=End',
  `checkpoint_claim_type` tinyint(4) NOT NULL COMMENT '1=No, 2=Yes, 3=Assigned User, 4=Assigned Group',
  `checkpoint_due_day` tinyint(4) NULL DEFAULT NULL,
  `checkpoint_next` smallint(6) NULL DEFAULT NULL,
  `checkpoint_case_1` smallint(6) NULL DEFAULT NULL,
  `checkpoint_case_2` smallint(6) NULL DEFAULT NULL,
  `checkpoint_case_3` smallint(6) NULL DEFAULT NULL,
  `checkpoint_icon` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `role_id` tinyint(4) NULL DEFAULT NULL,
  `group_id` smallint(6) NULL DEFAULT NULL,
  `checkpoint_order` tinyint(4) NOT NULL DEFAULT 1,
  `checkpoint_color` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `checkpoint_skip` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`checkpoint_id`) USING BTREE,
  INDEX `flow_id`(`flow_id`) USING BTREE,
  INDEX `role_id`(`role_id`) USING BTREE,
  INDEX `group_id`(`group_id`) USING BTREE,
  CONSTRAINT `wfl_checkpoint_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_checkpoint_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_checkpoint_ibfk_3` FOREIGN KEY (`flow_id`) REFERENCES `wfl_flow` (`flow_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 56 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for wfl_checkpoint_assign
-- ----------------------------
DROP TABLE IF EXISTS `wfl_checkpoint_assign`;
CREATE TABLE `wfl_checkpoint_assign`  (
  `checkpoint_assign_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `checkpoint_assign_type` tinyint(4) NOT NULL COMMENT '1=Assign to himself\r\n2=Assign to User\r\n3=Assign to Group',
  `checkpoint_id` smallint(6) NOT NULL,
  `checkpoint_to` smallint(6) NOT NULL,
  PRIMARY KEY (`checkpoint_assign_id`) USING BTREE,
  INDEX `checkpoint_id`(`checkpoint_id`) USING BTREE,
  INDEX `checkpoint_to`(`checkpoint_to`) USING BTREE,
  CONSTRAINT `wfl_checkpoint_assign_ibfk_1` FOREIGN KEY (`checkpoint_id`) REFERENCES `wfl_checkpoint` (`checkpoint_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_checkpoint_assign_ibfk_2` FOREIGN KEY (`checkpoint_to`) REFERENCES `wfl_checkpoint` (`checkpoint_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 16 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for wfl_checkpoint_user
-- ----------------------------
DROP TABLE IF EXISTS `wfl_checkpoint_user`;
CREATE TABLE `wfl_checkpoint_user`  (
  `checkpoint_user_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `checkpoint_id` smallint(6) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` tinyint(4) NOT NULL,
  `group_id` smallint(6) NOT NULL,
  PRIMARY KEY (`checkpoint_user_id`) USING BTREE,
  INDEX `checkpoint_id`(`checkpoint_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `group_id`(`group_id`) USING BTREE,
  INDEX `role_id`(`role_id`) USING BTREE,
  CONSTRAINT `wfl_checkpoint_user_ibfk_1` FOREIGN KEY (`checkpoint_id`) REFERENCES `wfl_checkpoint` (`checkpoint_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_checkpoint_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_checkpoint_user_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_checkpoint_user_ibfk_4` FOREIGN KEY (`role_id`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 8931 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for wfl_flow
-- ----------------------------
DROP TABLE IF EXISTS `wfl_flow`;
CREATE TABLE `wfl_flow`  (
  `flow_id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `flow_desc` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `flow_due_day` smallint(6) NOT NULL DEFAULT 30,
  `flow_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`flow_id`) USING BTREE,
  INDEX `flow_status`(`flow_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for wfl_task
-- ----------------------------
DROP TABLE IF EXISTS `wfl_task`;
CREATE TABLE `wfl_task`  (
  `task_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint(20) NOT NULL,
  `checkpoint_id` smallint(6) NOT NULL,
  `role_id` tinyint(4) NOT NULL,
  `group_id` smallint(6) NULL DEFAULT NULL,
  `task_current` tinyint(4) NOT NULL DEFAULT 1,
  `task_created_user` int(11) NOT NULL,
  `task_created_group` smallint(6) NOT NULL,
  `task_claimed_user` int(11) NULL DEFAULT NULL,
  `task_remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `task_date_due` date NULL DEFAULT NULL,
  `task_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `task_time_claimed` timestamp NULL DEFAULT NULL,
  `task_time_submit` timestamp NULL DEFAULT NULL,
  `task_status_previous` tinyint(4) NULL DEFAULT NULL,
  `task_status_save` tinyint(4) NULL DEFAULT NULL,
  `task_status` tinyint(4) NOT NULL,
  PRIMARY KEY (`task_id`) USING BTREE,
  INDEX `transaction_id`(`transaction_id`) USING BTREE,
  INDEX `checkpoint_id`(`checkpoint_id`) USING BTREE,
  INDEX `task_status`(`task_status`) USING BTREE,
  INDEX `group_id`(`group_id`) USING BTREE,
  INDEX `task_created_user`(`task_created_user`) USING BTREE,
  INDEX `task_created_group`(`task_created_group`) USING BTREE,
  INDEX `task_claimed_user`(`task_claimed_user`) USING BTREE,
  INDEX `task_current`(`task_current`) USING BTREE,
  INDEX `role_id`(`role_id`) USING BTREE,
  INDEX `task_date_due`(`task_date_due`) USING BTREE,
  CONSTRAINT `wfl_task_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `wfl_transaction` (`transaction_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_ibfk_2` FOREIGN KEY (`checkpoint_id`) REFERENCES `wfl_checkpoint` (`checkpoint_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_ibfk_3` FOREIGN KEY (`role_id`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_ibfk_4` FOREIGN KEY (`group_id`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_ibfk_5` FOREIGN KEY (`task_created_user`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_ibfk_6` FOREIGN KEY (`task_claimed_user`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_ibfk_7` FOREIGN KEY (`task_created_group`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 7175833 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for wfl_task_assign
-- ----------------------------
DROP TABLE IF EXISTS `wfl_task_assign`;
CREATE TABLE `wfl_task_assign`  (
  `task_assign_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint(20) NOT NULL,
  `checkpoint_id` smallint(6) NOT NULL,
  `role_id` tinyint(4) NOT NULL,
  `group_id` smallint(6) NOT NULL,
  `user_id` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`task_assign_id`) USING BTREE,
  INDEX `transaction_id`(`transaction_id`) USING BTREE,
  INDEX `checkpoint_id`(`checkpoint_id`) USING BTREE,
  INDEX `group_id`(`group_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `role_id`(`role_id`) USING BTREE,
  CONSTRAINT `wfl_task_assign_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `wfl_transaction` (`transaction_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_assign_ibfk_2` FOREIGN KEY (`checkpoint_id`) REFERENCES `wfl_checkpoint` (`checkpoint_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_assign_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_assign_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_task_assign_ibfk_5` FOREIGN KEY (`role_id`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1408051 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for wfl_transaction
-- ----------------------------
DROP TABLE IF EXISTS `wfl_transaction`;
CREATE TABLE `wfl_transaction`  (
  `transaction_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `transaction_no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `flow_id` tinyint(4) NOT NULL,
  `user_id` int(11) NOT NULL,
  `group_id` smallint(6) NOT NULL,
  `asset_no` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `transaction_date_due` date NULL DEFAULT NULL,
  `transaction_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `transaction_time_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  `transaction_time_complete` timestamp NULL DEFAULT NULL,
  `transaction_status` tinyint(4) NOT NULL,
  PRIMARY KEY (`transaction_id`) USING BTREE,
  INDEX `flow_id`(`flow_id`) USING BTREE,
  INDEX `transaction_status`(`transaction_status`) USING BTREE,
  INDEX `group_id`(`group_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  CONSTRAINT `wfl_transaction_ibfk_1` FOREIGN KEY (`flow_id`) REFERENCES `wfl_flow` (`flow_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_transaction_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_transaction_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `sys_group` (`group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 5698597 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for wfl_user_report
-- ----------------------------
DROP TABLE IF EXISTS `wfl_user_report`;
CREATE TABLE `wfl_user_report`  (
  `user_report_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_id` tinyint(4) NOT NULL,
  `report_to` int(11) NOT NULL,
  `report_role` tinyint(4) NOT NULL,
  PRIMARY KEY (`user_report_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `role_id`(`role_id`) USING BTREE,
  INDEX `report_to`(`report_to`) USING BTREE,
  INDEX `report_role`(`report_role`) USING BTREE,
  CONSTRAINT `wfl_user_report_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_user_report_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_user_report_ibfk_3` FOREIGN KEY (`report_to`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wfl_user_report_ibfk_4` FOREIGN KEY (`report_role`) REFERENCES `ref_role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 67 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for wo_import_log
-- ----------------------------
DROP TABLE IF EXISTS `wo_import_log`;
CREATE TABLE `wo_import_log`  (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `import_row_number` int(11) NOT NULL,
  `import_status` enum('SUCCESS','SKIPPED','FAILED','ERROR') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `row_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'JSON data of the imported row',
  `wo_task_id` int(11) NULL DEFAULT NULL COMMENT 'Created work order ID if successful',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`log_id`) USING BTREE,
  INDEX `idx_batch_id`(`batch_id`) USING BTREE,
  INDEX `idx_import_status`(`import_status`) USING BTREE,
  INDEX `idx_wo_task_id`(`wo_task_id`) USING BTREE,
  CONSTRAINT `wo_import_log_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `wo_import_batch` (`batch_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for wo_migration
-- ----------------------------
DROP TABLE IF EXISTS `wo_migration`;
CREATE TABLE `wo_migration`  (
  `wo_task_id` bigint(20) NULL DEFAULT NULL,
  `wo_task_no` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `wo_task_type` tinyint(1) NULL DEFAULT NULL,
  `wo_task_type_init` tinyint(1) NULL DEFAULT NULL,
  `site_id` smallint(6) NULL DEFAULT NULL,
  `wo_task_location` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `wo_task_complaint` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `wo_task_longitude` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_latitude` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_assigned_to` int(11) NULL DEFAULT NULL,
  `ppm_group_id` smallint(6) NULL DEFAULT NULL,
  `wo_task_severity` tinyint(1) NULL DEFAULT NULL,
  `wo_task_repair_desc` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `wo_task_rate` tinyint(1) NULL DEFAULT NULL,
  `transaction_id` bigint(20) NULL DEFAULT NULL,
  `pdf_id` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_created_by` int(11) NULL DEFAULT NULL,
  `wo_task_assigned_by` int(11) NULL DEFAULT NULL,
  `wo_task_fixed_by` int(11) NULL DEFAULT NULL,
  `wo_task_verified_by` int(11) NULL DEFAULT NULL,
  `wo_task_time_created` timestamp NULL DEFAULT NULL,
  `wo_task_time_responded` timestamp NULL DEFAULT NULL,
  `wo_task_time_assigned` timestamp NULL DEFAULT NULL,
  `wo_task_time_executed` timestamp NULL DEFAULT NULL,
  `wo_task_time_verified` timestamp NULL DEFAULT NULL,
  `wo_task_status` tinyint(4) NULL DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for wo_task
-- ----------------------------
DROP TABLE IF EXISTS `wo_task`;
CREATE TABLE `wo_task`  (
  `wo_task_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `wo_task_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `wo_task_request_no` varchar(31) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `wo_task_type_init` tinyint(1) NOT NULL COMMENT '1=external complaint, 2=internal complaint',
  `wo_task_type` tinyint(1) NULL DEFAULT NULL COMMENT '1=external complaint, 2=internal complaint',
  `wo_task_is_wr` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=no, 1=yes',
  `wo_task_is_wr_verified_together` tinyint(1) NOT NULL DEFAULT 0,
  `wo_task_wr_confirm` tinyint(1) NOT NULL DEFAULT 0,
  `wo_task_is_helpdesk` tinyint(1) NOT NULL DEFAULT 0,
  `wo_task_is_invalid` tinyint(1) NOT NULL DEFAULT 0,
  `site_id` smallint(6) NULL DEFAULT NULL,
  `location_code_id` smallint(6) NULL DEFAULT NULL,
  `wo_task_location` varchar(225) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `wo_task_complaint` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `wo_task_longitude` double(12, 6) NULL DEFAULT NULL,
  `wo_task_latitude` double(12, 6) NULL DEFAULT NULL,
  `wo_task_assigned_to` int(11) NULL DEFAULT NULL,
  `wo_task_done_asset` tinyint(1) NOT NULL DEFAULT 0,
  `wo_task_done_assistant` tinyint(1) NOT NULL DEFAULT 0,
  `wo_task_done_out_scope` tinyint(1) NOT NULL DEFAULT 0,
  `asset_id` bigint(20) NULL DEFAULT NULL,
  `ppm_group_id` smallint(6) NULL DEFAULT NULL,
  `wo_task_severity` tinyint(1) NULL DEFAULT NULL COMMENT '1=Non-Critical, 2=Critical',
  `wo_task_wr_check` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `wo_task_has_parts` tinyint(1) NULL DEFAULT NULL,
  `wo_task_repair_desc` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `wo_task_rate` tinyint(1) NULL DEFAULT NULL,
  `wo_task_max_assistant` tinyint(1) NULL DEFAULT NULL,
  `transaction_id` bigint(20) NOT NULL,
  `pdf_id` bigint(20) NULL DEFAULT NULL,
  `pdf_id_wr` bigint(20) NULL DEFAULT NULL,
  `wo_task_created_by` int(11) NULL DEFAULT NULL,
  `wo_task_assigned_by` int(11) NULL DEFAULT NULL,
  `wo_task_wr_checked_by` int(11) NULL DEFAULT NULL,
  `wo_task_wr_verified_by` int(11) NULL DEFAULT NULL,
  `wo_task_fixed_by` int(11) NULL DEFAULT NULL,
  `wo_task_verified_by` int(11) NULL DEFAULT NULL,
  `wo_task_time_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `wo_task_time_responded` timestamp NULL DEFAULT NULL,
  `wo_task_time_assigned` timestamp NULL DEFAULT NULL,
  `wo_task_time_rectified` timestamp NULL DEFAULT NULL,
  `wo_task_time_wr_checked` timestamp NULL DEFAULT NULL,
  `wo_task_time_wr_verified` timestamp NULL DEFAULT NULL,
  `wo_task_time_executed` timestamp NULL DEFAULT NULL,
  `wo_task_time_verified` timestamp NULL DEFAULT NULL,
  `wo_task_status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`wo_task_id`) USING BTREE,
  INDEX `transaction_id`(`transaction_id`) USING BTREE,
  INDEX `wo_task_status`(`wo_task_status`) USING BTREE,
  INDEX `pdf_id`(`pdf_id`) USING BTREE,
  INDEX `wo_task_created_by`(`wo_task_created_by`) USING BTREE,
  INDEX `wo_task_responsed_by`(`wo_task_assigned_by`) USING BTREE,
  INDEX `wo_task_executed_by`(`wo_task_fixed_by`) USING BTREE,
  INDEX `wo_task_verified_by`(`wo_task_verified_by`) USING BTREE,
  INDEX `site_id`(`site_id`) USING BTREE,
  INDEX `ppm_group_id`(`ppm_group_id`) USING BTREE,
  INDEX `pdf_id_wr`(`pdf_id_wr`) USING BTREE,
  INDEX `wo_task_wr_checked_by`(`wo_task_wr_checked_by`) USING BTREE,
  INDEX `wo_task_wr_verified_by`(`wo_task_wr_verified_by`) USING BTREE,
  INDEX `asset_id`(`asset_id`) USING BTREE,
  CONSTRAINT `wo_task_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `wfl_transaction` (`transaction_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_10` FOREIGN KEY (`wo_task_wr_checked_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_11` FOREIGN KEY (`wo_task_wr_verified_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_12` FOREIGN KEY (`asset_id`) REFERENCES `ast_asset` (`asset_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_2` FOREIGN KEY (`pdf_id`) REFERENCES `sys_pdf` (`pdf_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_3` FOREIGN KEY (`wo_task_created_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_4` FOREIGN KEY (`wo_task_assigned_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_5` FOREIGN KEY (`wo_task_fixed_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_6` FOREIGN KEY (`wo_task_verified_by`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_7` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_8` FOREIGN KEY (`ppm_group_id`) REFERENCES `ppm_group` (`ppm_group_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_ibfk_9` FOREIGN KEY (`pdf_id_wr`) REFERENCES `sys_pdf` (`pdf_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 185950 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for wo_task_assist
-- ----------------------------
DROP TABLE IF EXISTS `wo_task_assist`;
CREATE TABLE `wo_task_assist`  (
  `wo_task_assist_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `wo_task_id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`wo_task_assist_id`) USING BTREE,
  INDEX `wo_task_id`(`wo_task_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  CONSTRAINT `wo_task_assist_ibfk_1` FOREIGN KEY (`wo_task_id`) REFERENCES `wo_task` (`wo_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_assist_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 112966 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for wo_task_out_scope
-- ----------------------------
DROP TABLE IF EXISTS `wo_task_out_scope`;
CREATE TABLE `wo_task_out_scope`  (
  `wo_task_out_scope_id` int(11) NOT NULL AUTO_INCREMENT,
  `wo_task_id` bigint(20) NOT NULL,
  `wo_task_out_scope_contractor_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `wo_task_out_scope_date_commencement` date NULL DEFAULT NULL,
  `wo_task_out_scope_date_completion` date NULL DEFAULT NULL,
  `wo_task_out_scope_final_cost` decimal(10, 2) NULL DEFAULT NULL,
  PRIMARY KEY (`wo_task_out_scope_id`) USING BTREE,
  INDEX `wo_task_id`(`wo_task_id`) USING BTREE,
  CONSTRAINT `wo_task_out_scope_ibfk_1` FOREIGN KEY (`wo_task_id`) REFERENCES `wo_task` (`wo_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for wo_task_parts
-- ----------------------------
DROP TABLE IF EXISTS `wo_task_parts`;
CREATE TABLE `wo_task_parts`  (
  `wo_task_parts_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `wo_task_request_id` bigint(20) NULL DEFAULT NULL,
  `part_id` bigint(20) NULL DEFAULT NULL,
  `wo_task_parts_quantity` int(11) NULL DEFAULT NULL,
  `wo_task_parts_return` int(11) NULL DEFAULT NULL,
  `wo_task_parts_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `wo_task_parts_status` tinyint(4) NULL DEFAULT NULL,
  PRIMARY KEY (`wo_task_parts_id`) USING BTREE,
  INDEX `part_id`(`part_id`) USING BTREE,
  INDEX `wo_task_request_id`(`wo_task_request_id`) USING BTREE,
  CONSTRAINT `wo_task_parts_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `ast_part` (`part_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_parts_ibfk_3` FOREIGN KEY (`wo_task_request_id`) REFERENCES `wo_task_request` (`wo_task_request_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1697 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for wo_task_request
-- ----------------------------
DROP TABLE IF EXISTS `wo_task_request`;
CREATE TABLE `wo_task_request`  (
  `wo_task_request_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `wo_task_id` bigint(20) NULL DEFAULT NULL,
  `transaction_id` bigint(20) NULL DEFAULT NULL,
  `wo_task_request_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `wo_task_request_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `wo_task_request_order_by` int(11) NULL DEFAULT NULL,
  `wo_task_request_time_ordered` timestamp NULL DEFAULT NULL,
  `wo_task_request_time_collected` timestamp NULL DEFAULT NULL,
  `wo_task_request_time_rejected` timestamp NULL DEFAULT NULL,
  `wo_task_request_status` tinyint(4) NULL DEFAULT NULL,
  PRIMARY KEY (`wo_task_request_id`) USING BTREE,
  INDEX `wo_task_id`(`wo_task_id`) USING BTREE,
  INDEX `transaction_id`(`transaction_id`) USING BTREE,
  CONSTRAINT `wo_task_request_ibfk_1` FOREIGN KEY (`wo_task_id`) REFERENCES `wo_task` (`wo_task_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `wo_task_request_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `wfl_transaction` (`transaction_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1377 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for wo_task_upload
-- ----------------------------
DROP TABLE IF EXISTS `wo_task_upload`;
CREATE TABLE `wo_task_upload`  (
  `wo_task_upload_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `wo_task_upload_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1=complain, 2=before, 3=during, 4=after, 5=signiture complainer, 6=signiture responder, 7=signiture executor, 8=signiture verified, 9=signiture wr checked, 10=signiture wr verified',
  `wo_task_id` bigint(20) NOT NULL,
  `wo_task_upload_longitude` double(12, 6) NULL DEFAULT NULL,
  `wo_task_upload_latitude` double(12, 6) NULL DEFAULT NULL,
  `wo_task_upload_desc` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `upload_id` bigint(20) NOT NULL,
  `wo_task_upload_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`wo_task_upload_id`) USING BTREE,
  INDEX `upload_id`(`upload_id`) USING BTREE,
  INDEX `wo_task_upload_type`(`wo_task_upload_type`) USING BTREE,
  INDEX `wo_task_id`(`wo_task_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1184545 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for z_jai_batch_ppm
-- ----------------------------
DROP TABLE IF EXISTS `z_jai_batch_ppm`;
CREATE TABLE `z_jai_batch_ppm`  (
  `jai_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `asset_id` bigint(20) NOT NULL,
  `checklist_id` int(11) NOT NULL,
  `ppm_group_id` smallint(6) NOT NULL,
  `start_date` date NOT NULL,
  PRIMARY KEY (`jai_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 37665 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for z_migration
-- ----------------------------
DROP TABLE IF EXISTS `z_migration`;
CREATE TABLE `z_migration`  (
  `transaction_id` bigint(20) NULL DEFAULT NULL,
  `wo_task_no` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL,
  `wo_task_desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_location` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_severity` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_repair_desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `ppm_group_id` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_created_by` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_assigned_by` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_fixed_by` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_verified_by` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `wo_task_time_created` timestamp NULL DEFAULT NULL,
  `wo_task_time_assigned` timestamp NULL DEFAULT NULL,
  `wo_task_time_executed` timestamp NULL DEFAULT NULL,
  `wo_task_time_verified` timestamp NULL DEFAULT NULL,
  `wo_task_status` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET = utf8mb3 COLLATE = utf8mb3_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for z_raw
-- ----------------------------
DROP TABLE IF EXISTS `z_raw`;
CREATE TABLE `z_raw`  (
  `Work Order No.` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Progress` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Work Order Description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Work Order Type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Location Description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Severity` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Repair Description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Trade` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Customer Type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Complainant` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Assigned By` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Fixed By` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Verified By` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Complaint Time` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Respond Duration` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Assigned Time` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Executed Time` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Verified Time` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL,
  `Action Time(Minute)` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
