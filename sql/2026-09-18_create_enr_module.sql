-- GEMS Energy & Utility Monitoring V1
-- Daily incoming electricity meter readings, monthly summary and Building Energy Index (BEI).
-- Idempotent: safe to run more than once.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- Incoming meters (configurable per site, seeded with two)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `enr_meter` (
  `meter_id` int NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `meter_name` varchar(100) NOT NULL,
  `meter_desc` varchar(200) DEFAULT NULL,
  `sort_order` smallint NOT NULL DEFAULT 1,
  `meter_status` tinyint NOT NULL DEFAULT 1,
  `meter_created_by` int DEFAULT NULL,
  `meter_updated_by` int DEFAULT NULL,
  `meter_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `meter_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`meter_id`),
  UNIQUE KEY `uk_enr_meter` (`site_id`, `meter_name`),
  KEY `idx_enr_meter_site` (`site_id`, `meter_status`, `sort_order`),
  CONSTRAINT `enr_meter_site_fk` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Cumulative meter readings. Readings may skip days; consumption is spread
-- across the gap by EnergyCalculator.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `enr_reading` (
  `reading_id` int NOT NULL AUTO_INCREMENT,
  `meter_id` int NOT NULL,
  `reading_date` date NOT NULL,
  `cumulative_kwh` decimal(16,2) NOT NULL,
  `max_demand_kw` decimal(12,2) DEFAULT NULL,
  `remark` varchar(300) DEFAULT NULL,
  `image_upload_id` bigint DEFAULT NULL,
  `reading_created_by` int DEFAULT NULL,
  `reading_updated_by` int DEFAULT NULL,
  `reading_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reading_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reading_id`),
  UNIQUE KEY `uk_enr_reading` (`meter_id`, `reading_date`),
  KEY `idx_enr_reading_date` (`reading_date`),
  CONSTRAINT `enr_reading_meter_fk` FOREIGN KEY (`meter_id`) REFERENCES `enr_meter` (`meter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Per-day site notes (chiller running hours, remark)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `enr_daily_note` (
  `site_id` smallint NOT NULL,
  `note_date` date NOT NULL,
  `chiller_running_hours` decimal(8,2) DEFAULT NULL,
  `remark` varchar(300) DEFAULT NULL,
  `note_updated_by` int DEFAULT NULL,
  `note_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `note_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`site_id`, `note_date`),
  CONSTRAINT `enr_note_site_fk` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Site BEI configuration
-- ---------------------------------------------------------------------------
-- NOTE: column names avoid digits because DbMysql::convertToDbString() would
-- rewrite "m2" as "m_2" when camel/snake keys are converted.
CREATE TABLE IF NOT EXISTS `enr_site_config` (
  `site_id` smallint NOT NULL,
  `floor_area_sqm` decimal(14,2) NOT NULL DEFAULT 0.00,
  `target_bei` decimal(12,4) NOT NULL DEFAULT 0.0000,
  -- BEI is conventionally kWh/m2/year. Monthly consumption needs a factor of
  -- 12 to be comparable with an annual target; 1.00 keeps the raw ratio.
  `annualise_factor` decimal(8,4) NOT NULL DEFAULT 1.0000,
  `config_status` tinyint NOT NULL DEFAULT 1,
  `config_created_by` int DEFAULT NULL,
  `config_updated_by` int DEFAULT NULL,
  `config_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `config_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`site_id`),
  CONSTRAINT `enr_config_site_fk` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Monthly BEI result
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `enr_monthly_bei` (
  `bei_id` int NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `bei_year` smallint NOT NULL,
  `bei_month` tinyint NOT NULL,
  `electricity_kwh` decimal(16,2) NOT NULL DEFAULT 0.00,
  `electricity_is_override` tinyint(1) NOT NULL DEFAULT 0,
  `chilled_water_kwh` decimal(16,2) NOT NULL DEFAULT 0.00,
  `floor_area_sqm` decimal(14,2) NOT NULL DEFAULT 0.00,
  `target_bei` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `annualise_factor` decimal(8,4) NOT NULL DEFAULT 1.0000,
  `total_kwh` decimal(16,2) NOT NULL DEFAULT 0.00,
  `actual_bei` decimal(12,4) DEFAULT NULL,
  `result_pct` decimal(9,4) DEFAULT NULL,
  `remarks` varchar(500) DEFAULT NULL,
  `bei_status` varchar(10) NOT NULL DEFAULT 'DRAFT',
  `bei_created_by` int DEFAULT NULL,
  `bei_updated_by` int DEFAULT NULL,
  `bei_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bei_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`bei_id`),
  UNIQUE KEY `uk_enr_bei_period` (`site_id`, `bei_year`, `bei_month`),
  CONSTRAINT `enr_bei_site_fk` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Audit module / actions
-- ---------------------------------------------------------------------------
INSERT INTO `sys_audit_module` (`audit_module_id`, `audit_module_desc`, `audit_module_status`)
VALUES (20, 'Energy Monitoring', 1)
ON DUPLICATE KEY UPDATE `audit_module_desc` = VALUES(`audit_module_desc`), `audit_module_status` = VALUES(`audit_module_status`);

INSERT INTO `sys_audit_action` (`audit_action_id`, `audit_action_desc`, `audit_module_id`, `audit_action_status`) VALUES
  (256, 'Update Energy Meter', 20, 1),
  (257, 'Save Meter Reading', 20, 1),
  (258, 'Delete Meter Reading', 20, 1),
  (259, 'Update Energy Configuration', 20, 1),
  (260, 'Save Monthly BEI', 20, 1),
  (261, 'Finalise Monthly BEI', 20, 1)
ON DUPLICATE KEY UPDATE
  `audit_action_desc` = VALUES(`audit_action_desc`),
  `audit_module_id` = VALUES(`audit_module_id`),
  `audit_action_status` = VALUES(`audit_action_status`);

-- ---------------------------------------------------------------------------
-- Seed two incoming meters for every active site
-- ---------------------------------------------------------------------------
INSERT INTO `enr_meter` (`site_id`, `meter_name`, `meter_desc`, `sort_order`, `meter_status`)
SELECT s.site_id, v.nm, v.ds, v.so, 1
FROM cli_site s
CROSS JOIN (
  SELECT 'Incoming No.1' AS nm, 'Main incoming supply 1' AS ds, 1 AS so
  UNION ALL SELECT 'Incoming No.2', 'Main incoming supply 2', 2
) v
WHERE s.site_status = 1
ON DUPLICATE KEY UPDATE `meter_desc` = VALUES(`meter_desc`), `sort_order` = VALUES(`sort_order`);

UPDATE `sys_version` SET `version_no` = `version_no` + 1 WHERE `version_id` = 2;
