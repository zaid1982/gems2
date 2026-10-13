-- GEMS Water Monitoring
-- Daily cumulative water readings, IoT comparison, tariff and monthly bill.
-- Shares the morning-reading rule in EnergyCalculator. Idempotent.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- Water meters. Seeded with one main meter per active site; more can be added.
-- Column names avoid digits because DbMysql::convertToDbString() inserts an
-- underscore before every digit.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wtr_meter` (
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
  UNIQUE KEY `uk_wtr_meter` (`site_id`, `meter_name`),
  KEY `idx_wtr_meter_site` (`site_id`, `meter_status`, `sort_order`),
  CONSTRAINT `wtr_meter_site_fk` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- One row per meter per date. cumulative_volume is the register. It may be
-- null when the row only holds an IoT figure or a remark. iot_volume is that
-- day's IoT consumption, not a second cumulative register.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wtr_reading` (
  `reading_id` int NOT NULL AUTO_INCREMENT,
  `meter_id` int NOT NULL,
  `reading_date` date NOT NULL,
  `cumulative_volume` decimal(16,2) DEFAULT NULL,
  `iot_volume` decimal(16,2) DEFAULT NULL,
  `remark` varchar(300) DEFAULT NULL,
  `reading_created_by` int DEFAULT NULL,
  `reading_updated_by` int DEFAULT NULL,
  `reading_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reading_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reading_id`),
  UNIQUE KEY `uk_wtr_reading` (`meter_id`, `reading_date`),
  KEY `idx_wtr_reading_date` (`reading_date`),
  CONSTRAINT `wtr_reading_meter_fk` FOREIGN KEY (`meter_id`) REFERENCES `wtr_meter` (`meter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Effective-dated tariff. The last tier of a set has a null upper bound.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wtr_tariff` (
  `tariff_id` int NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `effective_from` date NOT NULL,
  `tier_no` smallint NOT NULL,
  `from_volume` decimal(16,2) NOT NULL,
  `to_volume` decimal(16,2) DEFAULT NULL,
  `rate_rm` decimal(12,4) NOT NULL,
  `tariff_created_by` int DEFAULT NULL,
  `tariff_updated_by` int DEFAULT NULL,
  `tariff_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tariff_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tariff_id`),
  UNIQUE KEY `uk_wtr_tariff` (`site_id`, `effective_from`, `tier_no`),
  KEY `idx_wtr_tariff_site` (`site_id`, `effective_from`),
  CONSTRAINT `wtr_tariff_site_fk` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Optional utility invoice. The calculated bill is derived, not stored here.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wtr_bill_actual` (
  `bill_id` int NOT NULL AUTO_INCREMENT,
  `site_id` smallint NOT NULL,
  `bill_year` smallint NOT NULL,
  `bill_month` tinyint NOT NULL,
  `actual_rm` decimal(14,2) NOT NULL,
  `bill_updated_by` int DEFAULT NULL,
  `bill_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bill_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`bill_id`),
  UNIQUE KEY `uk_wtr_bill` (`site_id`, `bill_year`, `bill_month`),
  CONSTRAINT `wtr_bill_site_fk` FOREIGN KEY (`site_id`) REFERENCES `cli_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Audit. Module 20 already exists for electricity. Keep actions 256-261.
-- ---------------------------------------------------------------------------
UPDATE `sys_audit_module`
SET `audit_module_desc` = 'Utility Monitoring', `audit_module_status` = 1
WHERE `audit_module_id` = 20;

INSERT INTO `sys_audit_action` (`audit_action_id`, `audit_action_desc`, `audit_module_id`, `audit_action_status`) VALUES
  (262, 'Update Water Meter', 20, 1),
  (263, 'Save Water Reading', 20, 1),
  (264, 'Delete Water Reading', 20, 1),
  (265, 'Update Water Tariff', 20, 1),
  (266, 'Save Water Invoice', 20, 1)
ON DUPLICATE KEY UPDATE
  `audit_action_desc` = VALUES(`audit_action_desc`),
  `audit_module_id` = VALUES(`audit_module_id`),
  `audit_action_status` = VALUES(`audit_action_status`);

-- ---------------------------------------------------------------------------
-- One main meter per active site
-- ---------------------------------------------------------------------------
INSERT INTO `wtr_meter` (`site_id`, `meter_name`, `meter_desc`, `sort_order`, `meter_status`)
SELECT s.site_id, 'Main Water Meter', 'Main water supply', 1, 1
FROM cli_site s
WHERE s.site_status = 1
ON DUPLICATE KEY UPDATE `meter_desc` = VALUES(`meter_desc`), `sort_order` = VALUES(`sort_order`);

-- ---------------------------------------------------------------------------
-- Workbook tariff, effective 1 Jan 2026: first 35 m3 at 2.62, the rest at 2.86.
-- Inserted per tier so the open-ended bound stays NULL.
-- ---------------------------------------------------------------------------
INSERT INTO `wtr_tariff` (`site_id`, `effective_from`, `tier_no`, `from_volume`, `to_volume`, `rate_rm`)
SELECT s.site_id, '2026-01-01', 1, 0.00, 35.00, 2.6200
FROM cli_site s
WHERE s.site_status = 1
  AND NOT EXISTS (
    SELECT 1 FROM wtr_tariff t
    WHERE t.site_id = s.site_id AND t.effective_from = '2026-01-01' AND t.tier_no = 1
  );

INSERT INTO `wtr_tariff` (`site_id`, `effective_from`, `tier_no`, `from_volume`, `to_volume`, `rate_rm`)
SELECT s.site_id, '2026-01-01', 2, 35.00, NULL, 2.8600
FROM cli_site s
WHERE s.site_status = 1
  AND NOT EXISTS (
    SELECT 1 FROM wtr_tariff t
    WHERE t.site_id = s.site_id AND t.effective_from = '2026-01-01' AND t.tier_no = 2
  );

UPDATE `sys_version` SET `version_no` = `version_no` + 1 WHERE `version_id` = 2;
