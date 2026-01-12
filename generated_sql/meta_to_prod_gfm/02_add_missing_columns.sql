-- Generated from docs/meta-gems.coffee -> docs/prod_gfm.coffee
-- Target: MariaDB 10.4 (XAMPP). Review before running in production.
-- Forward-only and avoids DROPs.

SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS=0;


ALTER TABLE `ast_part_sub` ADD COLUMN `part_sub_return_id` bigint(20) NOT NULL COMMENT 'FK to material_returns if returned' AFTER `part_sub_status`;
ALTER TABLE `ast_part_sub` ADD COLUMN `part_sub_returned_date` datetime NOT NULL COMMENT 'When part was returned to inventory' AFTER `part_sub_return_id`;
ALTER TABLE `ast_part_sub` ADD COLUMN `part_sub_returned_by` int(11) NOT NULL COMMENT 'User who returned the part' AFTER `part_sub_returned_date`;

-- WARNING: meta defines gmi_weekly.gmw_id as AUTO_INCREMENT, but prod already has AUTO_INCREMENT 'gmi_id'.
-- WARNING: added gmi_weekly.gmw_id without AUTO_INCREMENT and as NULL. If you need to migrate keys, do it manually.
ALTER TABLE `gmi_weekly` ADD COLUMN `gmw_id` bigint(20) NULL FIRST;

ALTER TABLE `ppm_set_asset` ADD COLUMN `ppm_set_asset_created_by` int(11) NOT NULL AFTER `asset_id`;

ALTER TABLE `ppm_task` ADD COLUMN `ppm_task_completed_offline` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 if task was completed offline' AFTER `ppm_task_time_verified`;

-- NOTE: wo_import_batch.import_filename is NOT NULL in meta but has no DEFAULT; added as NULL for safety.
ALTER TABLE `wo_import_batch` ADD COLUMN `import_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL AFTER `batch_id`;
-- NOTE: wo_import_batch.site_id is NOT NULL in meta but has no DEFAULT; added as NULL for safety.
ALTER TABLE `wo_import_batch` ADD COLUMN `site_id` int(11) NULL AFTER `import_filename`;
-- NOTE: wo_import_batch.imported_by is NOT NULL in meta but has no DEFAULT; added as NULL for safety.
ALTER TABLE `wo_import_batch` ADD COLUMN `imported_by` int(11) NULL AFTER `site_id`;
ALTER TABLE `wo_import_batch` ADD COLUMN `total_rows` int(11) NOT NULL DEFAULT 0 AFTER `imported_by`;
ALTER TABLE `wo_import_batch` ADD COLUMN `imported_rows` int(11) NOT NULL DEFAULT 0 AFTER `total_rows`;
ALTER TABLE `wo_import_batch` ADD COLUMN `skipped_rows` int(11) NOT NULL DEFAULT 0 AFTER `imported_rows`;
ALTER TABLE `wo_import_batch` ADD COLUMN `import_status` enum('PROCESSING','COMPLETED','FAILED') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PROCESSING' AFTER `skipped_rows`;
-- NOTE: wo_import_batch.created_at is NOT NULL in meta but has no DEFAULT; added as NULL for safety.
ALTER TABLE `wo_import_batch` ADD COLUMN `created_at` datetime NULL AFTER `import_status`;
ALTER TABLE `wo_import_batch` ADD COLUMN `completed_at` datetime NOT NULL AFTER `created_at`;

-- NOTE: wo_import_log.import_row_number is NOT NULL in meta but has no DEFAULT; added as NULL for safety.
ALTER TABLE `wo_import_log` ADD COLUMN `import_row_number` int(11) NULL AFTER `batch_id`;
-- NOTE: wo_import_log.import_status is NOT NULL in meta but has no DEFAULT; added as NULL for safety.
ALTER TABLE `wo_import_log` ADD COLUMN `import_status` enum('SUCCESS','SKIPPED','FAILED','ERROR') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL AFTER `import_row_number`;
ALTER TABLE `wo_import_log` ADD COLUMN `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL AFTER `import_status`;
ALTER TABLE `wo_import_log` ADD COLUMN `row_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'JSON data of the imported row' AFTER `error_message`;
ALTER TABLE `wo_import_log` ADD COLUMN `wo_task_id` int(11) NOT NULL COMMENT 'Created work order ID if successful' AFTER `row_data`;
-- NOTE: wo_import_log.created_at is NOT NULL in meta but has no DEFAULT; added as NULL for safety.
ALTER TABLE `wo_import_log` ADD COLUMN `created_at` datetime NULL AFTER `wo_task_id`;

-- WARNING: meta defines wo_task_public.wo_task_public as AUTO_INCREMENT, but prod already has AUTO_INCREMENT 'public_id'.
-- WARNING: added wo_task_public.wo_task_public without AUTO_INCREMENT and as NULL. If you need to migrate keys, do it manually.
ALTER TABLE `wo_task_public` ADD COLUMN `wo_task_public` bigint(20) NULL FIRST;

ALTER TABLE `wo_task_request_2` ADD COLUMN `wo_task_id` bigint(20) NOT NULL AFTER `wo_task_request_id`;
ALTER TABLE `wo_task_request_2` ADD COLUMN `transaction_id` bigint(20) NOT NULL AFTER `wo_task_id`;
ALTER TABLE `wo_task_request_2` ADD COLUMN `wo_task_request_no` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL AFTER `transaction_id`;
ALTER TABLE `wo_task_request_2` ADD COLUMN `wo_task_request_remark` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL AFTER `wo_task_request_no`;
ALTER TABLE `wo_task_request_2` ADD COLUMN `wo_task_request_order_by` int(11) NOT NULL AFTER `wo_task_request_remark`;
ALTER TABLE `wo_task_request_2` ADD COLUMN `wo_task_request_mrf_generate` tinyint(1) NOT NULL DEFAULT 1 AFTER `wo_task_request_order_by`;
ALTER TABLE `wo_task_request_2` ADD COLUMN `wo_task_request_mrf_pdf` bigint(20) NOT NULL AFTER `wo_task_request_mrf_generate`;
ALTER TABLE `wo_task_request_2` ADD COLUMN `wo_task_request_time_created` timestamp NOT NULL DEFAULT current_timestamp() AFTER `wo_task_request_mrf_pdf`;
ALTER TABLE `wo_task_request_2` ADD COLUMN `wo_task_request_time_ordered` timestamp NOT NULL AFTER `wo_task_request_time_created`;
ALTER TABLE `wo_task_request_2` ADD COLUMN `wo_task_request_time_collected` timestamp NOT NULL AFTER `wo_task_request_time_ordered`;
ALTER TABLE `wo_task_request_2` ADD COLUMN `wo_task_request_time_rejected` timestamp NOT NULL AFTER `wo_task_request_time_collected`;
ALTER TABLE `wo_task_request_2` ADD COLUMN `wo_task_request_status` tinyint(4) NOT NULL AFTER `wo_task_request_time_rejected`;


SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
