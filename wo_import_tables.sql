-- Work Order Import System Database Schema - Tables Only
-- Execute this SQL to create the required tables for WO import functionality
-- Views are handled through the GEMS sql.php system
use `gems`;

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

-- Check if wo_task_external_ref column exists before adding it
-- SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
--                WHERE table_name = 'wo_task' 
--                AND column_name = 'wo_task_external_ref' 
--                AND table_schema = database());
-- SET @sqlstmt := IF(@exist=0,'ALTER TABLE `wo_task` ADD COLUMN `wo_task_external_ref` varchar(100) NULL COMMENT ''External system work order reference'' AFTER `wo_task_is_helpdesk`','SELECT ''Column wo_task_external_ref already exists'' as msg');
-- PREPARE stmt FROM @sqlstmt;
-- EXECUTE stmt;
-- DEALLOCATE PREPARE stmt;

ALTER TABLE `wo_task`
  ADD COLUMN IF NOT EXISTS `wo_task_external_ref`
    VARCHAR(100) NULL
    COMMENT 'External system work order reference'
    AFTER `wo_task_is_helpdesk`;


-- Check if wo_task_is_imported column exists before adding it
-- SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
--                WHERE table_name = 'wo_task' 
--                AND column_name = 'wo_task_is_imported' 
--                AND table_schema = database());
-- SET @sqlstmt := IF(@exist=0,'ALTER TABLE `wo_task` ADD COLUMN `wo_task_is_imported` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''Flag to indicate if WO was imported from external system'' AFTER `wo_task_external_ref`','SELECT ''Column wo_task_is_imported already exists'' as msg');
-- PREPARE stmt FROM @sqlstmt;
-- EXECUTE stmt;
-- DEALLOCATE PREPARE stmt;

ALTER TABLE `wo_task`
  ADD COLUMN IF NOT EXISTS `wo_task_is_imported`
    TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Flag to indicate if WO was imported from external system'
    AFTER `wo_task_external_ref`;

-- Add indexes if they don't exist
CREATE INDEX IF NOT EXISTS `idx_external_ref` ON `wo_task` (`wo_task_external_ref`);
CREATE INDEX IF NOT EXISTS `idx_is_imported` ON `wo_task` (`wo_task_is_imported`);

-- Sample configuration for import templates (check if gmi_config table exists)
INSERT IGNORE INTO `gmi_config` (`config_key`, `config_value`, `data_type`, `description`, `status`, `last_updated_at`) VALUES
('wo_import_max_file_size', '10485760', 'int', 'Maximum file size for WO import in bytes (10MB)', '1', NOW()),
('wo_import_allowed_formats', 'csv,xlsx,xls', 'string', 'Allowed file formats for WO import', '1', NOW()),
('wo_import_batch_size', '1000', 'int', 'Maximum number of rows per import batch', '1', NOW()),
('wo_import_duplicate_action', 'skip', 'string', 'Action for duplicate external references: skip, update, error', '1', NOW());

SELECT 'Work Order Import tables created successfully!' as result;
