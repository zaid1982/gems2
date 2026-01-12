-- Generated from docs/meta-gems.coffee -> docs/prod_gfm.coffee
-- Target: MariaDB 10.4 (XAMPP). Review before running in production.
-- Forward-only and avoids DROPs.

SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS=0;


CREATE INDEX `idx_asset_id` ON `ast_asset` (`asset_id`);
CREATE INDEX `idx_asset_no` ON `ast_asset` (`asset_no`);

CREATE INDEX `fk_part_sub_returned_by` ON `ast_part_sub` (`part_sub_returned_by`);
CREATE INDEX `idx_returned_date` ON `ast_part_sub` (`part_sub_returned_date`);
CREATE INDEX `idx_return_id` ON `ast_part_sub` (`part_sub_return_id`);

CREATE INDEX `site_id` ON `gmi_weekly` (`site_id`);

CREATE INDEX `ppm_ibfk_6` ON `ppm` (`ppm_set_id`);

CREATE INDEX `idx_ppm_task_assigned` ON `ppm_task` (`ppm_task_assigned_to`);
CREATE INDEX `idx_ppm_task_no` ON `ppm_task` (`ppm_task_no`);
CREATE INDEX `idx_ppm_task_status` ON `ppm_task` (`ppm_task_status`);

CREATE INDEX `idx_created_at` ON `wo_import_batch` (`created_at`);
CREATE INDEX `idx_import_status` ON `wo_import_batch` (`import_status`);
CREATE INDEX `idx_site_imported_by` ON `wo_import_batch` (`site_id`, `imported_by`);

CREATE INDEX `idx_batch_id` ON `wo_import_log` (`batch_id`);
CREATE INDEX `idx_import_status` ON `wo_import_log` (`import_status`);
CREATE INDEX `idx_wo_task_id` ON `wo_import_log` (`wo_task_id`);

CREATE INDEX `idx_external_ref` ON `wo_task` (`wo_task_external_ref`);
CREATE INDEX `idx_is_imported` ON `wo_task` (`wo_task_is_imported`);
CREATE INDEX `wo_task_severity` ON `wo_task` (`wo_task_severity`);

CREATE INDEX `wo_task_public_ibfk_2` ON `wo_task_public` (`transaction_id`);
CREATE INDEX `wo_task_public_ibfk_3` ON `wo_task_public` (`user_id`);

CREATE INDEX `store_id` ON `wo_task_request` (`store_id`);
CREATE INDEX `wo_task_request_severity` ON `wo_task_request` (`wo_task_request_severity`);

CREATE INDEX `wo_task_id` ON `wo_task_request_2` (`wo_task_id`);


SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
