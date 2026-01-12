-- OPTIONAL: strict column modifications to match meta exactly.
-- Review carefully: some changes can be destructive (NOT NULL, shrinking varchar, charset/collation).

-- Generated from docs/meta-gems.coffee -> docs/prod_gfm.coffee
-- Target: MariaDB 10.4 (XAMPP). Review before running in production.
-- Forward-only and avoids DROPs.

SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS=0;


-- ast_asset.asset_life_cycle differs in: COLUMN_TYPE
ALTER TABLE `ast_asset` MODIFY COLUMN `asset_life_cycle` smallint(3) NULL DEFAULT NULL;

-- att_group.att_group_name differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `att_group` MODIFY COLUMN `att_group_name` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL;
-- att_group.att_group_holiday differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `att_group` MODIFY COLUMN `att_group_holiday` enum('Saturday & Sunday','Sunday') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- att_group.att_group_shift_mode differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `att_group` MODIFY COLUMN `att_group_shift_mode` enum('Normal','2 Shifts','3 Shifts') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- att_group.att_group_remark differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `att_group` MODIFY COLUMN `att_group_remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;

-- att_participant.att_participant_gf_id differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `att_participant` MODIFY COLUMN `att_participant_gf_id` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT '';
-- att_participant.att_participant_holiday differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `att_participant` MODIFY COLUMN `att_participant_holiday` enum('Sunday','Saturday & Sunday') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- att_participant.att_participant_shift_mode differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `att_participant` MODIFY COLUMN `att_participant_shift_mode` enum('Normal','2 Shifts','3 Shifts') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- att_participant.att_participant_year_service differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `att_participant` MODIFY COLUMN `att_participant_year_service` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- att_participant.att_participant_competency differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `att_participant` MODIFY COLUMN `att_participant_competency` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;

-- att_transaction.att_transaction_result differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `att_transaction` MODIFY COLUMN `att_transaction_result` enum('Present','Absent','Leave','Training') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- att_transaction.att_transaction_status differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `att_transaction` MODIFY COLUMN `att_transaction_status` enum('Checked In','Checked Out','Ready') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'Ready';

-- att_type.att_type_name differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `att_type` MODIFY COLUMN `att_type_name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL;
-- att_type.att_type_short differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `att_type` MODIFY COLUMN `att_type_short` varchar(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL;
-- att_type.att_type_mode differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `att_type` MODIFY COLUMN `att_type_mode` enum('Normal','2 Shifts','3 Shifts','Leave','Training') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- att_type.att_type_color differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `att_type` MODIFY COLUMN `att_type_color` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- att_type.att_type_color_done differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `att_type` MODIFY COLUMN `att_type_color_done` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;

-- email_log.email_address differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `email_log` MODIFY COLUMN `email_address` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;
-- email_log.email_title differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `email_log` MODIFY COLUMN `email_title` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;
-- email_log.email_html differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `email_log` MODIFY COLUMN `email_html` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;
-- email_log.email_attachment differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `email_log` MODIFY COLUMN `email_attachment` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- email_log.email_filename differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `email_log` MODIFY COLUMN `email_filename` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;

-- email_parameter.email_param_code differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `email_parameter` MODIFY COLUMN `email_param_code` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;
-- email_parameter.email_param_desc differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `email_parameter` MODIFY COLUMN `email_param_desc` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;

-- email_send.email_address differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `email_send` MODIFY COLUMN `email_address` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;
-- email_send.email_title differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `email_send` MODIFY COLUMN `email_title` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;
-- email_send.email_html differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `email_send` MODIFY COLUMN `email_html` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;
-- email_send.email_attachment differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `email_send` MODIFY COLUMN `email_attachment` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- email_send.email_filename differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `email_send` MODIFY COLUMN `email_filename` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;

-- email_template.email_template_name differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `email_template` MODIFY COLUMN `email_template_name` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;
-- email_template.email_template_desc differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `email_template` MODIFY COLUMN `email_template_desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- email_template.email_template_title differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `email_template` MODIFY COLUMN `email_template_title` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;
-- email_template.email_template_html differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `email_template` MODIFY COLUMN `email_template_html` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;

-- fca_defect_category.fca_defect_category_name differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `fca_defect_category` MODIFY COLUMN `fca_defect_category_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL;

-- fca_report.fca_report_name differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `fca_report` MODIFY COLUMN `fca_report_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- fca_report.fca_report_sort_by differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `fca_report` MODIFY COLUMN `fca_report_sort_by` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;

-- fca_task.fca_task_no differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `fca_task` MODIFY COLUMN `fca_task_no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- fca_task.fca_task_area differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `fca_task` MODIFY COLUMN `fca_task_area` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- fca_task.fca_task_asset_no differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `fca_task` MODIFY COLUMN `fca_task_asset_no` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- fca_task.fca_task_asset_evaluated differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `fca_task` MODIFY COLUMN `fca_task_asset_evaluated` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- fca_task.fca_task_defect_item differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `fca_task` MODIFY COLUMN `fca_task_defect_item` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- fca_task.fca_task_observation differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `fca_task` MODIFY COLUMN `fca_task_observation` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- fca_task.fca_task_recommendation differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `fca_task` MODIFY COLUMN `fca_task_recommendation` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- fca_task.fca_task_validation differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `fca_task` MODIFY COLUMN `fca_task_validation` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;

-- fca_task_section.fca_task_section_code differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `fca_task_section` MODIFY COLUMN `fca_task_section_code` varchar(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL;
-- fca_task_section.fca_task_section_name differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `fca_task_section` MODIFY COLUMN `fca_task_section_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL;

-- fca_zone.fca_zone_name differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `fca_zone` MODIFY COLUMN `fca_zone_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL;

-- gmi_monthly.gmi_ppm_tier_name differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `gmi_monthly` MODIFY COLUMN `gmi_ppm_tier_name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- gmi_monthly.gmi_wo_tier_name differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `gmi_monthly` MODIFY COLUMN `gmi_wo_tier_name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;

-- gmi_weekly.gmw_ppm_tier_name differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `gmi_weekly` MODIFY COLUMN `gmw_ppm_tier_name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- gmi_weekly.gmw_wo_tier_name differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `gmi_weekly` MODIFY COLUMN `gmw_wo_tier_name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;

-- kpi.kpi_year differs in: COLUMN_TYPE
ALTER TABLE `kpi` MODIFY COLUMN `kpi_year` smallint(4) NOT NULL;
-- kpi.kpi_month differs in: COLUMN_TYPE
ALTER TABLE `kpi` MODIFY COLUMN `kpi_month` tinyint(2) NOT NULL;

-- noti_web.noti_web_type differs in: COLUMN_COMMENT
ALTER TABLE `noti_web` MODIFY COLUMN `noti_web_type` tinyint(4) NOT NULL COMMENT '1=WO assign
2=WO verify';
-- noti_web.noti_web_title differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `noti_web` MODIFY COLUMN `noti_web_title` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL;
-- noti_web.noti_web_text differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `noti_web` MODIFY COLUMN `noti_web_text` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL;
-- noti_web.noti_web_icon differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `noti_web` MODIFY COLUMN `noti_web_icon` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL;
-- noti_web.noti_web_color differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `noti_web` MODIFY COLUMN `noti_web_color` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL;
-- noti_web.noti_web_link differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `noti_web` MODIFY COLUMN `noti_web_link` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- noti_web.nav_id differs in: COLUMN_TYPE
ALTER TABLE `noti_web` MODIFY COLUMN `nav_id` tinyint(4) NULL DEFAULT NULL;
-- noti_web.nav_second_id differs in: COLUMN_TYPE
ALTER TABLE `noti_web` MODIFY COLUMN `nav_second_id` tinyint(4) NULL DEFAULT NULL;

-- ppm.asset_id differs in: IS_NULLABLE, COLUMN_DEFAULT
ALTER TABLE `ppm` MODIFY COLUMN `asset_id` bigint(20) NULL DEFAULT NULL;

-- ppm_checklist_quan.checklist_quan_desc differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `ppm_checklist_quan` MODIFY COLUMN `checklist_quan_desc` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;

-- ppm_set.ppm_set_name differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `ppm_set` MODIFY COLUMN `ppm_set_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- ppm_set.ppm_set_desc differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `ppm_set` MODIFY COLUMN `ppm_set_desc` varchar(1000) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;

-- ref_city.city_desc differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `ref_city` MODIFY COLUMN `city_desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;

-- ref_country.country_desc differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `ref_country` MODIFY COLUMN `country_desc` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;

-- ref_document.document_desc differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `ref_document` MODIFY COLUMN `document_desc` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;
-- ref_document.document_type differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `ref_document` MODIFY COLUMN `document_type` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;

-- ref_state.state_desc differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `ref_state` MODIFY COLUMN `state_desc` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '';

-- sys_address.address_desc differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_address` MODIFY COLUMN `address_desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- sys_address.address_postcode differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_address` MODIFY COLUMN `address_postcode` varchar(5) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- sys_address.address_city differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_address` MODIFY COLUMN `address_city` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;

-- sys_audit.audit_ip differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_audit` MODIFY COLUMN `audit_ip` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- sys_audit.audit_place differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_audit` MODIFY COLUMN `audit_place` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- sys_audit.audit_remark differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_audit` MODIFY COLUMN `audit_remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;

-- sys_audit_action.audit_action_desc differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_audit_action` MODIFY COLUMN `audit_action_desc` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;
-- sys_audit_action.audit_action_status differs in: COLUMN_TYPE
ALTER TABLE `sys_audit_action` MODIFY COLUMN `audit_action_status` tinyint(255) NOT NULL DEFAULT 1;

-- sys_audit_module.audit_module_desc differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_audit_module` MODIFY COLUMN `audit_module_desc` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;

-- sys_group.group_name differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_group` MODIFY COLUMN `group_name` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- sys_group.group_reg_no differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_group` MODIFY COLUMN `group_reg_no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;

-- sys_location.location_desc differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_location` MODIFY COLUMN `location_desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;

-- sys_nav_second.nav_second_desc differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_nav_second` MODIFY COLUMN `nav_second_desc` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;
-- sys_nav_second.nav_second_page differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_nav_second` MODIFY COLUMN `nav_second_page` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;

-- sys_upload.upload_name differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_upload` MODIFY COLUMN `upload_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- sys_upload.upload_uplname differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_upload` MODIFY COLUMN `upload_uplname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '';
-- sys_upload.upload_filename differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_upload` MODIFY COLUMN `upload_filename` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- sys_upload.upload_extension differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_upload` MODIFY COLUMN `upload_extension` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- sys_upload.upload_folder differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_upload` MODIFY COLUMN `upload_folder` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- sys_upload.upload_blob_type differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_upload` MODIFY COLUMN `upload_blob_type` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '';

-- sys_user.user_type differs in: COLUMN_TYPE
ALTER TABLE `sys_user` MODIFY COLUMN `user_type` tinyint(3) NOT NULL DEFAULT 1;

-- sys_user_profile.user_contact_no differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_user_profile` MODIFY COLUMN `user_contact_no` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- sys_user_profile.user_email differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_user_profile` MODIFY COLUMN `user_email` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;

-- sys_version.version_name differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `sys_version` MODIFY COLUMN `version_name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;

-- wfl_checkpoint.checkpoint_desc differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wfl_checkpoint` MODIFY COLUMN `checkpoint_desc` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;
-- wfl_checkpoint.checkpoint_icon differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wfl_checkpoint` MODIFY COLUMN `checkpoint_icon` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- wfl_checkpoint.checkpoint_color differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wfl_checkpoint` MODIFY COLUMN `checkpoint_color` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;

-- wfl_flow.flow_desc differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wfl_flow` MODIFY COLUMN `flow_desc` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;

-- wfl_task.task_remark differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wfl_task` MODIFY COLUMN `task_remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;

-- wfl_transaction.transaction_no differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wfl_transaction` MODIFY COLUMN `transaction_no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;
-- wfl_transaction.asset_no differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wfl_transaction` MODIFY COLUMN `asset_no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;

-- wo_import_batch.batch_id differs in: COLUMN_TYPE
ALTER TABLE `wo_import_batch` MODIFY COLUMN `batch_id` int(11) NOT NULL auto_increment;

-- wo_import_log.log_id differs in: COLUMN_TYPE
ALTER TABLE `wo_import_log` MODIFY COLUMN `log_id` int(11) NOT NULL auto_increment;
-- wo_import_log.batch_id differs in: COLUMN_TYPE
ALTER TABLE `wo_import_log` MODIFY COLUMN `batch_id` int(11) NOT NULL;

-- wo_migration.wo_task_no differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wo_migration` MODIFY COLUMN `wo_task_no` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- wo_migration.wo_task_location differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wo_migration` MODIFY COLUMN `wo_task_location` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- wo_migration.wo_task_complaint differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wo_migration` MODIFY COLUMN `wo_task_complaint` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- wo_migration.wo_task_longitude differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wo_migration` MODIFY COLUMN `wo_task_longitude` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- wo_migration.wo_task_latitude differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wo_migration` MODIFY COLUMN `wo_task_latitude` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- wo_migration.wo_task_repair_desc differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wo_migration` MODIFY COLUMN `wo_task_repair_desc` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;
-- wo_migration.pdf_id differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wo_migration` MODIFY COLUMN `pdf_id` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;

-- wo_task.wo_task_is_imported differs in: COLUMN_COMMENT
ALTER TABLE `wo_task` MODIFY COLUMN `wo_task_is_imported` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flag to indicate if WO was imported from external system';

-- wo_task_public.transaction_id differs in: IS_NULLABLE, COLUMN_DEFAULT
ALTER TABLE `wo_task_public` MODIFY COLUMN `transaction_id` bigint(20) NOT NULL;
-- wo_task_public.wo_task_public_name differs in: COLUMN_TYPE, CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wo_task_public` MODIFY COLUMN `wo_task_public_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- wo_task_public.wo_task_public_ic_no differs in: COLUMN_TYPE, CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wo_task_public` MODIFY COLUMN `wo_task_public_ic_no` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- wo_task_public.wo_task_public_agency differs in: COLUMN_TYPE, CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wo_task_public` MODIFY COLUMN `wo_task_public_agency` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- wo_task_public.wo_task_public_phone_no differs in: COLUMN_TYPE, CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wo_task_public` MODIFY COLUMN `wo_task_public_phone_no` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- wo_task_public.wo_task_public_email differs in: COLUMN_TYPE, CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wo_task_public` MODIFY COLUMN `wo_task_public_email` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;
-- wo_task_public.wo_task_public_complaint differs in: CHARACTER_SET_NAME, COLLATION_NAME
ALTER TABLE `wo_task_public` MODIFY COLUMN `wo_task_public_complaint` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;

-- wo_task_request_2.wo_task_request_id differs in: IS_NULLABLE, COLUMN_DEFAULT, EXTRA
ALTER TABLE `wo_task_request_2` MODIFY COLUMN `wo_task_request_id` bigint(20) NOT NULL auto_increment;

-- wo_task_upload.wo_task_upload_type differs in: COLUMN_COMMENT
ALTER TABLE `wo_task_upload` MODIFY COLUMN `wo_task_upload_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1=complain, 2=before, 3=during, 4=after, 5=signiture complainer, 6=signiture responder, 7=signiture executor, 8=signiture verified, 9=signiture wr checked, 10=signiture wr verified, 12=signiture check';


SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
