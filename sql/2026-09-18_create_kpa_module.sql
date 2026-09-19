-- GEMS KPI & APD Module V1
-- KPI template (groups / PI / parameters) + monthly evaluation instances + APD engine data.
-- site_id = 0 means the global template shared by every site.
-- Idempotent: safe to run more than once.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- Site configuration (maximum APD percentage)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kpa_config` (
  `site_id` smallint NOT NULL,
  `max_apd_pct` decimal(5,2) NOT NULL DEFAULT 5.00,
  `config_status` tinyint NOT NULL DEFAULT 1,
  `config_created_by` int DEFAULT NULL,
  `config_updated_by` int DEFAULT NULL,
  `config_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `config_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- KPI groups
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kpa_group` (
  `group_id` int NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL DEFAULT 0,
  `group_no` varchar(5) NOT NULL,
  `group_name` varchar(200) NOT NULL,
  `sort_order` smallint NOT NULL DEFAULT 1,
  `group_status` tinyint NOT NULL DEFAULT 1,
  `group_created_by` int DEFAULT NULL,
  `group_updated_by` int DEFAULT NULL,
  `group_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `group_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `uk_kpa_group` (`site_id`, `group_no`),
  KEY `idx_kpa_group_status` (`site_id`, `group_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Performance Indicators
--   pass_rule : GTE_TARGET | LTE_TARGET | EQ_TARGET
--   calc_type : EXPRESSION | BACKLOG_AVG | BEI | AVG_PARAMS | DIRECT
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kpa_pi` (
  `pi_id` int NOT NULL AUTO_INCREMENT,
  `group_id` int NOT NULL,
  `pi_no` varchar(10) NOT NULL,
  `pi_name` varchar(300) NOT NULL,
  `pi_description` text,
  `target_value` decimal(12,4) NOT NULL DEFAULT 100.0000,
  `target_unit` varchar(10) NOT NULL DEFAULT '%',
  `demerit_point` smallint NOT NULL DEFAULT 1,
  `weightage_pct` decimal(6,2) NOT NULL DEFAULT 0.00,
  `pass_rule` varchar(15) NOT NULL DEFAULT 'GTE_TARGET',
  `calc_type` varchar(15) NOT NULL DEFAULT 'EXPRESSION',
  `formula_expr` varchar(500) DEFAULT NULL,
  `source_type` varchar(10) NOT NULL DEFAULT 'MANUAL',
  `sort_order` smallint NOT NULL DEFAULT 1,
  `pi_status` tinyint NOT NULL DEFAULT 1,
  `effective_from` date DEFAULT NULL,
  `pi_created_by` int DEFAULT NULL,
  `pi_updated_by` int DEFAULT NULL,
  `pi_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pi_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pi_id`),
  UNIQUE KEY `uk_kpa_pi` (`group_id`, `pi_no`),
  KEY `idx_kpa_pi_status` (`pi_status`),
  CONSTRAINT `kpa_pi_group_fk` FOREIGN KEY (`group_id`) REFERENCES `kpa_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- PI parameters (inputs used by the formula / calculator)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kpa_pi_param` (
  `param_id` int NOT NULL AUTO_INCREMENT,
  `pi_id` int NOT NULL,
  `param_key` varchar(10) NOT NULL,
  `param_label` varchar(200) NOT NULL,
  `data_type` varchar(10) NOT NULL DEFAULT 'NUMBER',
  `source_type` varchar(10) NOT NULL DEFAULT 'MANUAL',
  `gems_hook` varchar(60) DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint NOT NULL DEFAULT 1,
  `param_status` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`param_id`),
  UNIQUE KEY `uk_kpa_param` (`pi_id`, `param_key`),
  CONSTRAINT `kpa_param_pi_fk` FOREIGN KEY (`pi_id`) REFERENCES `kpa_pi` (`pi_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Monthly evaluation instance
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kpa_evaluation` (
  `eval_id` int NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `eval_year` smallint NOT NULL,
  `eval_month` tinyint NOT NULL,
  `mpv` decimal(14,3) NOT NULL DEFAULT 0.000,
  `max_apd_pct` decimal(5,2) NOT NULL DEFAULT 5.00,
  `apd_max_amount` decimal(16,2) NOT NULL DEFAULT 0.00,
  `eval_status` varchar(12) NOT NULL DEFAULT 'OPEN',
  `total_demerit` smallint NOT NULL DEFAULT 0,
  `total_apd_deducted` decimal(16,2) NOT NULL DEFAULT 0.00,
  `pi_total` smallint NOT NULL DEFAULT 0,
  `pi_submitted` smallint NOT NULL DEFAULT 0,
  `remarks` varchar(500) DEFAULT NULL,
  `eval_created_by` int DEFAULT NULL,
  `eval_updated_by` int DEFAULT NULL,
  `eval_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `eval_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`eval_id`),
  UNIQUE KEY `uk_kpa_eval_period` (`site_id`, `eval_year`, `eval_month`),
  KEY `idx_kpa_eval_status` (`site_id`, `eval_status`),
  CONSTRAINT `kpa_eval_site_fk` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Snapshot of each PI inside a monthly evaluation
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kpa_evaluation_pi` (
  `eval_pi_id` int NOT NULL AUTO_INCREMENT,
  `eval_id` int NOT NULL,
  `pi_id` int DEFAULT NULL,
  `group_no` varchar(5) NOT NULL,
  `group_name` varchar(200) NOT NULL,
  `pi_no` varchar(10) NOT NULL,
  `pi_name` varchar(300) NOT NULL,
  `target_value` decimal(12,4) NOT NULL,
  `target_unit` varchar(10) NOT NULL DEFAULT '%',
  `demerit_point` smallint NOT NULL DEFAULT 1,
  `weightage_pct` decimal(6,2) NOT NULL DEFAULT 0.00,
  `pass_rule` varchar(15) NOT NULL DEFAULT 'GTE_TARGET',
  `calc_type` varchar(15) NOT NULL DEFAULT 'EXPRESSION',
  `formula_expr` varchar(500) DEFAULT NULL,
  `source_type` varchar(10) NOT NULL DEFAULT 'MANUAL',
  `sort_order` smallint NOT NULL DEFAULT 1,
  `actual_value` decimal(16,4) DEFAULT NULL,
  `result_pct` decimal(9,4) DEFAULT NULL,
  `is_pass` tinyint(1) DEFAULT NULL,
  `demerit_imposed` smallint NOT NULL DEFAULT 0,
  `apd_value` decimal(16,2) NOT NULL DEFAULT 0.00,
  `apd_deducted` decimal(16,2) NOT NULL DEFAULT 0.00,
  `calc_message` varchar(300) DEFAULT NULL,
  `pi_status` varchar(12) NOT NULL DEFAULT 'DRAFT',
  `remarks` varchar(500) DEFAULT NULL,
  `submitted_by` int DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reopened_by` int DEFAULT NULL,
  `reopened_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`eval_pi_id`),
  UNIQUE KEY `uk_kpa_eval_pi` (`eval_id`, `pi_no`),
  KEY `idx_kpa_eval_pi_status` (`eval_id`, `pi_status`),
  CONSTRAINT `kpa_eval_pi_eval_fk` FOREIGN KEY (`eval_id`) REFERENCES `kpa_evaluation` (`eval_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Captured parameter values per evaluation PI
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kpa_evaluation_param` (
  `eval_param_id` int NOT NULL AUTO_INCREMENT,
  `eval_pi_id` int NOT NULL,
  `param_id` int DEFAULT NULL,
  `param_key` varchar(10) NOT NULL,
  `param_label` varchar(200) NOT NULL,
  `data_type` varchar(10) NOT NULL DEFAULT 'NUMBER',
  `source_type` varchar(10) NOT NULL DEFAULT 'MANUAL',
  `gems_hook` varchar(60) DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint NOT NULL DEFAULT 1,
  `param_value` decimal(18,4) DEFAULT NULL,
  `source_used` varchar(10) NOT NULL DEFAULT 'MANUAL',
  PRIMARY KEY (`eval_param_id`),
  UNIQUE KEY `uk_kpa_eval_param` (`eval_pi_id`, `param_key`),
  CONSTRAINT `kpa_eval_param_pi_fk` FOREIGN KEY (`eval_pi_id`) REFERENCES `kpa_evaluation_pi` (`eval_pi_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- PI Entry user assignment
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kpa_pi_assignment` (
  `assign_id` int NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `pi_id` int NOT NULL,
  `user_id` int NOT NULL,
  `assign_status` tinyint NOT NULL DEFAULT 1,
  `assign_created_by` int DEFAULT NULL,
  `assign_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`assign_id`),
  UNIQUE KEY `uk_kpa_assign` (`site_id`, `pi_id`, `user_id`),
  KEY `idx_kpa_assign_user` (`user_id`, `assign_status`),
  CONSTRAINT `kpa_assign_pi_fk` FOREIGN KEY (`pi_id`) REFERENCES `kpa_pi` (`pi_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Audit trail
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kpa_history` (
  `history_id` int NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(30) NOT NULL,
  `entity_id` int NOT NULL,
  `site_id` smallint DEFAULT NULL,
  `action` varchar(20) NOT NULL,
  `old_values` longtext,
  `new_values` longtext,
  `reason` varchar(500) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  KEY `idx_kpa_hist_entity` (`entity_type`, `entity_id`),
  KEY `idx_kpa_hist_site` (`site_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Roles
-- ---------------------------------------------------------------------------
INSERT INTO `ref_role` (`role_id`, `role_desc`, `role_type`, `role_status`)
SELECT 30, 'KPI Admin', 2, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ref_role WHERE role_id = 30);

INSERT INTO `ref_role` (`role_id`, `role_desc`, `role_type`, `role_status`)
SELECT 31, 'PI Entry', 2, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ref_role WHERE role_id = 31);

INSERT INTO `ref_role` (`role_id`, `role_desc`, `role_type`, `role_status`)
SELECT 32, 'KPI Viewer', 2, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ref_role WHERE role_id = 32);

-- ---------------------------------------------------------------------------
-- Audit module / actions
-- ---------------------------------------------------------------------------
INSERT INTO `sys_audit_module` (`audit_module_id`, `audit_module_desc`, `audit_module_status`)
VALUES (19, 'KPI & APD', 1)
ON DUPLICATE KEY UPDATE `audit_module_desc` = VALUES(`audit_module_desc`), `audit_module_status` = VALUES(`audit_module_status`);

INSERT INTO `sys_audit_action` (`audit_action_id`, `audit_action_desc`, `audit_module_id`, `audit_action_status`) VALUES
  (249, 'Update KPI Structure', 19, 1),
  (250, 'Create Monthly KPI Evaluation', 19, 1),
  (251, 'Update Monthly KPI Evaluation', 19, 1),
  (252, 'Save PI Parameters', 19, 1),
  (253, 'Submit PI', 19, 1),
  (254, 'Reopen PI', 19, 1),
  (255, 'Assign PI User', 19, 1)
ON DUPLICATE KEY UPDATE
  `audit_action_desc` = VALUES(`audit_action_desc`),
  `audit_module_id` = VALUES(`audit_module_id`),
  `audit_action_status` = VALUES(`audit_action_status`);

-- ---------------------------------------------------------------------------
-- Global template: 4 KPI groups
-- ---------------------------------------------------------------------------
INSERT INTO `kpa_group` (`site_id`, `group_no`, `group_name`, `sort_order`, `group_status`) VALUES
  (0, '1', 'FMM Service Delivery related to Core Business', 1, 1),
  (0, '2', 'Asset Performance', 2, 1),
  (0, '3', 'Building Energy Efficiency', 3, 1),
  (0, '4', 'Safety & Statutory Compliance', 4, 1)
ON DUPLICATE KEY UPDATE `group_name` = VALUES(`group_name`), `sort_order` = VALUES(`sort_order`);

SET @g1 := (SELECT group_id FROM kpa_group WHERE site_id = 0 AND group_no = '1' LIMIT 1);
SET @g2 := (SELECT group_id FROM kpa_group WHERE site_id = 0 AND group_no = '2' LIMIT 1);
SET @g3 := (SELECT group_id FROM kpa_group WHERE site_id = 0 AND group_no = '3' LIMIT 1);
SET @g4 := (SELECT group_id FROM kpa_group WHERE site_id = 0 AND group_no = '4' LIMIT 1);

-- ---------------------------------------------------------------------------
-- Global template: 21 Performance Indicators
-- Weightage totals 100 (61 + 20 + 10 + 9).
-- ---------------------------------------------------------------------------
INSERT INTO `kpa_pi`
  (`group_id`, `pi_no`, `pi_name`, `target_value`, `target_unit`, `demerit_point`, `weightage_pct`,
   `pass_rule`, `calc_type`, `formula_expr`, `source_type`, `sort_order`, `pi_status`) VALUES
  (@g1, '1A', 'Customer Satisfaction Survey rating', 80.0000, '%', 1, 5.00, 'GTE_TARGET', 'EXPRESSION', '(p1/p2)*100', 'MANUAL', 1, 1),
  (@g1, '1B', 'Customer Rating in Work Order sheet', 70.0000, '%', 1, 5.00, 'GTE_TARGET', 'EXPRESSION', '(p2/p1)*100', 'MANUAL', 2, 1),
  (@g1, '1C', 'Response Time meets target', 100.0000, '%', 1, 5.00, 'GTE_TARGET', 'EXPRESSION', '(p2/p1)*100', 'GEMS', 3, 1),
  (@g1, '1D', 'Execution Time meets target', 95.0000, '%', 2, 5.00, 'GTE_TARGET', 'EXPRESSION', '(p2/p1)*100', 'GEMS', 4, 1),
  (@g1, '1E', 'Pending / Backlog Work Order Completion', 100.0000, '%', 1, 5.00, 'GTE_TARGET', 'BACKLOG_AVG', NULL, 'GEMS', 5, 1),
  (@g1, '1F', 'Self Finding Work Order quantity from total Work Order', 80.0000, '%', 1, 10.00, 'GTE_TARGET', 'EXPRESSION', '(p1/(p1+p2))*100', 'GEMS', 6, 1),
  (@g1, '1G', 'Cleaning Performance', 85.0000, '%', 2, 6.00, 'GTE_TARGET', 'EXPRESSION', '(p1/p2)*100', 'MANUAL', 7, 1),
  (@g1, '1H', 'Pest Control Performance', 95.0000, '%', 1, 5.00, 'GTE_TARGET', 'EXPRESSION', '(p1/p2)*100', 'MANUAL', 8, 1),
  (@g1, '1I', 'Critical Services availability', 95.0000, '%', 3, 8.00, 'GTE_TARGET', 'EXPRESSION', '100-((p1/p2)*100)', 'MANUAL', 9, 1),
  (@g1, '1J', 'Normal Services availability', 85.0000, '%', 1, 7.00, 'GTE_TARGET', 'EXPRESSION', '100-((p1/p2)*100)', 'MANUAL', 10, 1),
  (@g2, '2A', 'PPM for Architecture and C&S assets implemented', 100.0000, '%', 1, 4.00, 'GTE_TARGET', 'EXPRESSION', '100-((p1/p2)*100)', 'GEMS', 1, 1),
  (@g2, '2B', 'PPM for Mechanical assets implemented', 100.0000, '%', 1, 4.00, 'GTE_TARGET', 'EXPRESSION', '100-((p1/p2)*100)', 'GEMS', 2, 1),
  (@g2, '2C', 'PPM for Electrical assets implemented', 100.0000, '%', 1, 4.00, 'GTE_TARGET', 'EXPRESSION', '100-((p1/p2)*100)', 'GEMS', 3, 1),
  (@g2, '2D', 'Engineering Reports & Recommendation action taken', 100.0000, '%', 1, 4.00, 'GTE_TARGET', 'EXPRESSION', '(p2/p1)*100', 'MANUAL', 4, 1),
  (@g2, '2E', 'Work done as specification / asset quality meets standards', 100.0000, '%', 1, 4.00, 'GTE_TARGET', 'EXPRESSION', '100-((p1/p2)*100)', 'MANUAL', 5, 1),
  (@g3, '3A', 'Energy Conservation programs implemented', 100.0000, '%', 1, 4.00, 'GTE_TARGET', 'EXPRESSION', '(p2/p1)*100', 'MANUAL', 1, 1),
  (@g3, '3B', 'Building Energy Index (BEI) target met', 170.6500, 'BEI', 1, 3.00, 'LTE_TARGET', 'BEI', NULL, 'MANUAL', 2, 1),
  (@g3, '3C', 'Utility Consumption - no wastage', 100.0000, '%', 1, 3.00, 'GTE_TARGET', 'EXPRESSION', '100-p1', 'MANUAL', 3, 1),
  (@g4, '4A', 'Relevant Acts & Regulations complied', 100.0000, '%', 1, 3.00, 'GTE_TARGET', 'EXPRESSION', '(p2/p1)*100', 'MANUAL', 1, 1),
  (@g4, '4B', 'HSE programs implemented', 100.0000, '%', 1, 3.00, 'GTE_TARGET', 'EXPRESSION', '(p2/p1)*100', 'MANUAL', 2, 1),
  (@g4, '4C', 'Reports submitted on time with sufficient content', 100.0000, '%', 1, 3.00, 'GTE_TARGET', 'AVG_PARAMS', NULL, 'MANUAL', 3, 1)
ON DUPLICATE KEY UPDATE
  `pi_name` = VALUES(`pi_name`),
  `target_value` = VALUES(`target_value`),
  `target_unit` = VALUES(`target_unit`),
  `demerit_point` = VALUES(`demerit_point`),
  `weightage_pct` = VALUES(`weightage_pct`),
  `pass_rule` = VALUES(`pass_rule`),
  `calc_type` = VALUES(`calc_type`),
  `formula_expr` = VALUES(`formula_expr`),
  `source_type` = VALUES(`source_type`),
  `sort_order` = VALUES(`sort_order`);

-- ---------------------------------------------------------------------------
-- Global template: PI parameters
-- ---------------------------------------------------------------------------
INSERT INTO `kpa_pi_param` (`pi_id`, `param_key`, `param_label`, `data_type`, `source_type`, `gems_hook`, `is_required`, `sort_order`)
SELECT p.pi_id, v.k, v.l, v.dt, v.st, v.hook, v.req, v.so
FROM (
  SELECT '1A' AS pino, 'p1' AS k, 'Total Rating Marks Obtained' AS l, 'NUMBER' AS dt, 'MANUAL' AS st, NULL AS hook, 1 AS req, 1 AS so
  UNION ALL SELECT '1A', 'p2', 'Total Possible Marks', 'NUMBER', 'MANUAL', NULL, 1, 2
  UNION ALL SELECT '1B', 'p1', 'Work Orders Generated and Done', 'INT', 'GEMS', 'WO_DONE', 1, 1
  UNION ALL SELECT '1B', 'p2', 'Total Customer Rating Marks', 'NUMBER', 'MANUAL', NULL, 1, 2
  UNION ALL SELECT '1C', 'p1', 'Work Orders Generated including NCR', 'INT', 'GEMS', 'WO_GENERATED', 1, 1
  UNION ALL SELECT '1C', 'p2', 'Work Orders Within Response Time', 'INT', 'GEMS', 'WO_WITHIN_RESPONSE', 1, 2
  UNION ALL SELECT '1D', 'p1', 'Work Orders Generated', 'INT', 'GEMS', 'WO_GENERATED', 1, 1
  UNION ALL SELECT '1D', 'p2', 'Work Orders Closed Within Allocated Time', 'INT', 'GEMS', 'WO_WITHIN_EXECUTION', 1, 2
  UNION ALL SELECT '1E', 'p1', 'Backlog Work Orders under 30 days - Total', 'INT', 'GEMS', 'WO_BACKLOG_30_TOTAL', 1, 1
  UNION ALL SELECT '1E', 'p2', 'Backlog Work Orders under 30 days - Completed', 'INT', 'GEMS', 'WO_BACKLOG_30_DONE', 1, 2
  UNION ALL SELECT '1E', 'p3', 'Backlog Work Orders under 60 days - Total', 'INT', 'GEMS', 'WO_BACKLOG_60_TOTAL', 1, 3
  UNION ALL SELECT '1E', 'p4', 'Backlog Work Orders under 60 days - Completed', 'INT', 'GEMS', 'WO_BACKLOG_60_DONE', 1, 4
  UNION ALL SELECT '1E', 'p5', 'Backlog Work Orders under 90 days - Total', 'INT', 'GEMS', 'WO_BACKLOG_90_TOTAL', 1, 5
  UNION ALL SELECT '1E', 'p6', 'Backlog Work Orders under 90 days - Completed', 'INT', 'GEMS', 'WO_BACKLOG_90_DONE', 1, 6
  UNION ALL SELECT '1F', 'p1', 'Self Finding Work Orders', 'INT', 'GEMS', 'WO_SELF_FINDING', 1, 1
  UNION ALL SELECT '1F', 'p2', 'Corrective Work Orders', 'INT', 'GEMS', 'WO_CORRECTIVE', 1, 2
  UNION ALL SELECT '1G', 'p1', 'Total Cleaning Audit Marks Obtained', 'NUMBER', 'MANUAL', NULL, 1, 1
  UNION ALL SELECT '1G', 'p2', 'Total Maximum Cleaning Audit Marks', 'NUMBER', 'MANUAL', NULL, 1, 2
  UNION ALL SELECT '1H', 'p1', 'Total Pest Control Audit Marks Obtained', 'NUMBER', 'MANUAL', NULL, 1, 1
  UNION ALL SELECT '1H', 'p2', 'Total Maximum Pest Control Audit Marks', 'NUMBER', 'MANUAL', NULL, 1, 2
  UNION ALL SELECT '1I', 'p1', 'Affected DAK Rooms (critical services)', 'INT', 'MANUAL', NULL, 1, 1
  UNION ALL SELECT '1I', 'p2', 'Total DAK Rooms (critical services)', 'INT', 'MANUAL', NULL, 1, 2
  UNION ALL SELECT '1J', 'p1', 'Affected Areas (normal services)', 'INT', 'MANUAL', NULL, 1, 1
  UNION ALL SELECT '1J', 'p2', 'Total Areas (normal services)', 'INT', 'MANUAL', NULL, 1, 2
  UNION ALL SELECT '2A', 'p1', 'Overdue NCR - Architecture and C&S', 'INT', 'GEMS', 'PPM_NCR_OVERDUE_CS', 1, 1
  UNION ALL SELECT '2A', 'p2', 'Total NCR - Architecture and C&S', 'INT', 'GEMS', 'PPM_NCR_TOTAL_CS', 1, 2
  UNION ALL SELECT '2B', 'p1', 'Overdue NCR - Mechanical', 'INT', 'GEMS', 'PPM_NCR_OVERDUE_M', 1, 1
  UNION ALL SELECT '2B', 'p2', 'Total NCR - Mechanical', 'INT', 'GEMS', 'PPM_NCR_TOTAL_M', 1, 2
  UNION ALL SELECT '2C', 'p1', 'Overdue NCR - Electrical', 'INT', 'GEMS', 'PPM_NCR_OVERDUE_E', 1, 1
  UNION ALL SELECT '2C', 'p2', 'Total NCR - Electrical', 'INT', 'GEMS', 'PPM_NCR_TOTAL_E', 1, 2
  UNION ALL SELECT '2D', 'p1', 'Engineering Reports Requiring Action', 'INT', 'MANUAL', NULL, 1, 1
  UNION ALL SELECT '2D', 'p2', 'Actions Taken', 'INT', 'MANUAL', NULL, 1, 2
  UNION ALL SELECT '2E', 'p1', 'Overdue Work Quality NCR', 'INT', 'MANUAL', NULL, 1, 1
  UNION ALL SELECT '2E', 'p2', 'Total Work Quality NCR', 'INT', 'MANUAL', NULL, 1, 2
  UNION ALL SELECT '3A', 'p1', 'Energy Conservation Programs Planned', 'INT', 'MANUAL', NULL, 1, 1
  UNION ALL SELECT '3A', 'p2', 'Energy Conservation Programs Implemented', 'INT', 'MANUAL', NULL, 1, 2
  UNION ALL SELECT '3B', 'p1', 'Electricity Energy Consumption (kWh)', 'NUMBER', 'MANUAL', 'ENERGY_BEI_ELECTRICITY', 1, 1
  UNION ALL SELECT '3B', 'p2', 'Chilled Water Consumption (kWh)', 'NUMBER', 'MANUAL', 'ENERGY_BEI_CHILLED', 1, 2
  UNION ALL SELECT '3B', 'p3', 'Gross Floor Area (m2)', 'NUMBER', 'MANUAL', 'ENERGY_BEI_FLOOR_AREA', 1, 3
  UNION ALL SELECT '3B', 'p4', 'Annualisation factor (blank = none, 12 for monthly readings)', 'NUMBER', 'MANUAL', 'ENERGY_BEI_ANNUALISE', 0, 4
  UNION ALL SELECT '3C', 'p1', 'Number of Utility Wastage Findings', 'INT', 'MANUAL', NULL, 1, 1
  UNION ALL SELECT '4A', 'p1', 'Total Applicable Acts & Regulations', 'INT', 'MANUAL', NULL, 1, 1
  UNION ALL SELECT '4A', 'p2', 'Acts & Regulations Complied', 'INT', 'MANUAL', NULL, 1, 2
  UNION ALL SELECT '4B', 'p1', 'HSE Programs Planned', 'INT', 'MANUAL', NULL, 1, 1
  UNION ALL SELECT '4B', 'p2', 'HSE Programs Implemented', 'INT', 'MANUAL', NULL, 1, 2
  UNION ALL SELECT '4C', 'p1', 'Report Timeliness (%)', 'PERCENT', 'MANUAL', NULL, 1, 1
  UNION ALL SELECT '4C', 'p2', 'Report Content Score (%)', 'PERCENT', 'MANUAL', NULL, 1, 2
) v
INNER JOIN kpa_pi p ON p.pi_no = v.pino
INNER JOIN kpa_group g ON g.group_id = p.group_id AND g.site_id = 0
ON DUPLICATE KEY UPDATE
  `param_label` = VALUES(`param_label`),
  `data_type` = VALUES(`data_type`),
  `source_type` = VALUES(`source_type`),
  `gems_hook` = VALUES(`gems_hook`),
  `is_required` = VALUES(`is_required`),
  `sort_order` = VALUES(`sort_order`);

UPDATE `sys_version` SET `version_no` = `version_no` + 1 WHERE `version_id` = 2;
