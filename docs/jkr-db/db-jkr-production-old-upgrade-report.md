# Old Production Schema Upgrade Report

Generated: 2026-05-22

## Summary
- Old tables: 116
- Current tables: 141
- Missing tables added by upgrade: 29
- Extra old tables preserved: 4
- Current views added/replaced: 3
- Existing tables with missing columns: 9
- Missing columns on existing tables: 42
- Existing tables with semantic column definition changes: 30
- Semantic column definition changes: 65 total; 61 applied by the live-upgrade script, 4 skipped as data-preserving exceptions

## Follow-Up Config Seed
- Run `docs/jkr-db/seed_audit_config_from_current.sql` after the schema upgrade if the target database was created from the old structure-only dump.
- This seeds `sys_audit_module` and `sys_audit_action` only; it does not insert runtime `sys_audit` log rows.
- It fixes FK errors such as missing audit action `118` (`Preview WO PDF`) when `save_audit(...)` writes to `sys_audit`.

## Missing Tables
- cli_zone
- gmi_config
- gmi_weekly
- inventory_logs
- lic_license
- ppm_asset
- ppm_set
- ppm_set_asset
- ptw_approval_log
- ptw_document
- ptw_number_sequence
- ptw_status_history
- ptw_worker
- ref_space_category
- ref_space_location
- ref_space_status
- ref_space_type
- spc_reservation
- spc_space
- spc_space_asset
- spc_space_media
- sys_nav_role_backup_20251021
- sys_site
- sys_user_signature
- vm_host
- vm_visit
- wo_import_batch
- wo_task_public
- wo_task_request_2

## Extra Old Tables Preserved
- wo_task_out_scope
- z_jai_batch_ppm
- z_migration
- z_raw

## Missing Views
- vw_item_image
- vw_part_with_image
- vw_ppm_set_asset_details

## Existing Tables With Missing Columns
- ast_asset: zone_id, asset_lifespan_year, asset_lifespan_start_date, asset_lifespan_alert, asset_value_depreciation, asset_value_alert, asset_repair_alert, asset_running_hours, asset_disposal_status, asset_disposal_date, asset_disposal_item_cost, asset_disposal_service_cost, asset_mtbf_alert, asset_mttr_alert
- ast_part_sub: part_sub_return_id, part_sub_returned_date, part_sub_returned_by
- cli_site: site_is_public
- noti_web: nav_id, nav_second_id
- ppm: ppm_name, ppm_set_id, ppm_is_group, asset_type_id, ppm_frequency, ppm_remark
- ppm_task: ppm_task_is_group_executed, ppm_task_completed_offline
- sys_user: user_designation
- wo_task: wo_task_external_ref, wo_task_is_imported, wo_task_is_public, zone_id, wo_task_is_pdf_wr, wo_task_is_pdf
- wo_task_request: wo_task_no, store_id, wo_task_request_severity, wo_task_request_is_standalone, wo_task_request_mrf_generate, wo_task_request_mrf_pdf, wo_task_request_time_created

## Existing Tables With Semantic Column Changes
- ast_asset: asset_no (skipped in live-upgrade script because old production stores this as TEXT), asset_name (skipped in live-upgrade script because old production stores this as TEXT), asset_desc, asset_warranty_notes, asset_technician_notes
- ast_part: part_remark
- att_group: att_group_remark
- cli_location_code: location_code_name, location_code_desc
- drawing: drawing_remark
- fca_task: fca_task_observation, fca_task_recommendation, fca_task_validation
- kpi_info: kpi_info_target_value, kpi_info_remark
- material_returns: return_remarks
- noti_log: noti_to, noti_html
- noti_web: noti_web_type
- ppm: asset_id
- ppm_checklist: checklist_guideline
- ppm_checklist_qual: checklist_qual_desc
- ppm_checklist_quan: checklist_quan_desc, checklist_quan_unit
- ppm_offline_sync_log: request_payload, response_payload
- ppm_task: ppm_task_guideline, ppm_task_remark
- ppm_task_qual: ppm_task_qual_desc
- ppm_task_quan: ppm_task_quan_desc
- ptw_permit: ptw_work_types, ptw_extension_requested_remarks, ptw_remarks, ptw_hazards, ptw_control_measures, ptw_checklist_cold_work, ptw_checklist_hot_work, ptw_checklist_confined_space, ptw_hazard_checklist, ptw_declaration_checklist, ptw_supporting_docs_checklist, ptw_certificate_numbers, ptw_complete_form_data, ptw_supervisor_comments, ptw_she_remarks, ptw_fm_remarks, cancel_reason, suspend_reason, ptw_hazardous_activities
- ref_item: item_remark
- sys_audit: audit_remark
- sys_upload: upload_blob_data
- wfl_task: task_remark
- wfl_transaction: asset_no (skipped in live-upgrade script because old production has values longer than current varchar(30))
- wo_import_log: error_message, row_data
- wo_migration: wo_task_complaint, wo_task_repair_desc
- wo_task: wo_task_location (skipped in live-upgrade script because old production allows longer values), wo_task_complaint, wo_task_wr_check, wo_task_repair_desc
- wo_task_parts: wo_task_parts_remark
- wo_task_request: wo_task_request_remark
- wo_task_upload: wo_task_upload_type

## Extra Old Columns Preserved
- ppm: ppm_is_routine
- sys_pdf: pdf_time_update
- wo_task: location_code_id, wo_task_done_out_scope
- wo_task_parts: wo_task_parts_return
