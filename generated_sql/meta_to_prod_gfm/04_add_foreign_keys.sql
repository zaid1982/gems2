-- Generated from docs/meta-gems.coffee -> docs/prod_gfm.coffee
-- Target: MariaDB 10.4 (XAMPP). Review before running in production.
-- Forward-only and avoids DROPs.

SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS=0;


ALTER TABLE `ast_part_sub` ADD CONSTRAINT `fk_part_sub_return` FOREIGN KEY (`part_sub_return_id`) REFERENCES `material_returns` (`return_id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `ast_part_sub` ADD CONSTRAINT `fk_part_sub_returned_by` FOREIGN KEY (`part_sub_returned_by`) REFERENCES `sys_user` (`user_id`) ON DELETE SET NULL ON UPDATE RESTRICT;

ALTER TABLE `ppm` ADD CONSTRAINT `ppm_ibfk_6` FOREIGN KEY (`ppm_set_id`) REFERENCES `ppm_set` (`ppm_set_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `wo_import_log` ADD CONSTRAINT `wo_import_log_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `wo_import_batch` (`batch_id`) ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `wo_task` ADD CONSTRAINT `wo_task_ibfk_13` FOREIGN KEY (`wo_task_severity`) REFERENCES `ref_severity` (`severity_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `wo_task` ADD CONSTRAINT `wo_task_ibfk_14` FOREIGN KEY (`zone_id`) REFERENCES `cli_zone` (`zone_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `wo_task_public` ADD CONSTRAINT `wo_task_public_ibfk_1` FOREIGN KEY (`wo_task_id`) REFERENCES `wo_task` (`wo_task_id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `wo_task_public` ADD CONSTRAINT `wo_task_public_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `wfl_transaction` (`transaction_id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `wo_task_public` ADD CONSTRAINT `wo_task_public_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`user_id`) ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `wo_task_request` ADD CONSTRAINT `wo_task_request_ibfk_4` FOREIGN KEY (`wo_task_request_severity`) REFERENCES `ref_severity` (`severity_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `wo_task_request` ADD CONSTRAINT `wo_task_request_ibfk_5` FOREIGN KEY (`store_id`) REFERENCES `cli_store` (`store_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;


SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
