-- Work Order Import System Database Schema
-- Execute this SQL to create the required tables for WO import functionality

-- Import batch tracking table
CREATE TABLE IF NOT EXISTS `wo_import_batch` (
  `batch_id` int(11) NOT NULL AUTO_INCREMENT,
  `import_filename` varchar(255) NOT NULL,
  `site_id` int(11) NOT NULL,
  `imported_by` int(11) NOT NULL,
  `total_rows` int(11) NOT NULL DEFAULT 0,
  `imported_rows` int(11) NOT NULL DEFAULT 0,
  `skipped_rows` int(11) NOT NULL DEFAULT 0,
  `import_status` enum('PROCESSING','COMPLETED','FAILED') NOT NULL DEFAULT 'PROCESSING',
  `created_at` datetime NOT NULL,
  `completed_at` datetime NULL,
  PRIMARY KEY (`batch_id`),
  KEY `idx_site_imported_by` (`site_id`, `imported_by`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_import_status` (`import_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Import log for individual row tracking
CREATE TABLE IF NOT EXISTS `wo_import_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `row_number` int(11) NOT NULL,
  `import_status` enum('SUCCESS','SKIPPED','FAILED','ERROR') NOT NULL,
  `error_message` text NULL,
  `row_data` text NULL COMMENT 'JSON data of the imported row',
  `wo_task_id` int(11) NULL COMMENT 'Created work order ID if successful',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_import_status` (`import_status`),
  KEY `idx_wo_task_id` (`wo_task_id`),
  FOREIGN KEY (`batch_id`) REFERENCES `wo_import_batch` (`batch_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add columns to wo_task table for import tracking
ALTER TABLE `wo_task` 
ADD COLUMN `wo_task_external_ref` varchar(100) NULL COMMENT 'External system work order reference' AFTER `wo_task_is_helpdesk`,
ADD COLUMN `wo_task_is_imported` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flag to indicate if WO was imported from external system' AFTER `wo_task_external_ref`,
ADD INDEX `idx_external_ref` (`wo_task_external_ref`),
ADD INDEX `idx_is_imported` (`wo_task_is_imported`);

-- Create view for import statistics
CREATE OR REPLACE VIEW `vw_wo_import_stats` AS
SELECT 
    wib.batch_id,
    wib.import_filename,
    s.site_name,
    u.user_name as imported_by_name,
    wib.total_rows,
    wib.imported_rows,
    wib.skipped_rows,
    wib.import_status,
    wib.created_at,
    wib.completed_at,
    TIMESTAMPDIFF(MINUTE, wib.created_at, COALESCE(wib.completed_at, NOW())) as processing_minutes,
    ROUND((wib.imported_rows / wib.total_rows) * 100, 2) as success_rate
FROM wo_import_batch wib
LEFT JOIN cli_site s ON wib.site_id = s.site_id
LEFT JOIN sys_user u ON wib.imported_by = u.user_id
ORDER BY wib.created_at DESC;

-- Sample configuration for import templates
INSERT IGNORE INTO `gmi_config` (`config_key`, `config_value`, `data_type`, `description`, `status`, `created_at`) VALUES
('wo_import_max_file_size', '10485760', 'int', 'Maximum file size for WO import in bytes (10MB)', '1', NOW()),
('wo_import_allowed_formats', 'csv,xlsx,xls', 'string', 'Allowed file formats for WO import', '1', NOW()),
('wo_import_batch_size', '1000', 'int', 'Maximum number of rows per import batch', '1', NOW()),
('wo_import_duplicate_action', 'skip', 'string', 'Action for duplicate external references: skip, update, error', '1', NOW());
