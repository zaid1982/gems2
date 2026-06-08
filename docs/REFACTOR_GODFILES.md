# Decomposing the god files

`api/function/f_wo.php` (~200KB, 87 methods) and `api/function/f_ppm.php`
(~300KB, 87 methods) are the two largest classes, with `f_gamification.php`,
`f_task.php` and the 117KB `api/library/sql.php` close behind. This is the
playbook for carving them into cohesive, testable units without a risky big-bang
rewrite.

## Target package layout

```
api/src/Gfm/
  WorkOrder/   WorkOrderRepository, WorkOrderAssignmentService, ...
  Ppm/         PpmRepository, PpmScheduleService, ...
  Task/        TaskWorkflowService, ...
  Gamification/
```

A repository owns reads/writes for one slice and extends `Gfm\Support\Repository`
(parameterized `SafeQuery`). Services orchestrate repositories + workflow.

The first slice is implemented and tested: `Gfm\WorkOrder\WorkOrderRepository`
(`findById`, `countByStatusForSite`, `listBySite`).

## Class_wo (f_wo.php) - method map

Group the 87 methods into these units:

- WorkOrderReferenceRepository: `get_severity`, `get_upload_type`, `get_wo_type`,
  `get_wo_task_type`, `get_wo_severity_list_m`, `get_severity_list_by_site`.
- WorkOrderRepository (reads): `getWoTask`, `getWoTaskPublic`, `get_wo_task`,
  `get_complaint_details_m`, `getExecutionInfo`.
- WorkOrderCreationService: `create_wo_no`, `submit_new_complaint`.
- WorkOrderAssignmentService: `get_wo_group_m`, `get_wo_technician_m`,
  `get_technician_details_m`, `save_assigned_technician_m`,
  `saveAssignedTechnicianV2M`, `submit_assign`, `reject_complaint`,
  `get_assigned_technician`.
- WorkOrderWorkflowService (state transitions): `submit_repair`, `submit_check`,
  `return_verify`, `submit_verify`, `return_by_technician`, `submit_wr_check`,
  `submit_wr_check2`, `submit_wr_verify`, `return_wr_verify`,
  `return_wr_by_technician`, `return_from_check_m`, `get_current_task`.
- WorkOrderImageService: `save_wo_image_m`, `save_wo_image_desc_m`,
  `delete_wo_repair_image_m`, `get_wo_repair_images_m`,
  `get_wo_response_images_m`, `get_wo_section_upload_m`.
- WorkOrderDashboardService: `get_wo_task_dashboard_list`,
  `get_wo_task_dashboard_list2`, `get_wo_dashboard_datatable`,
  `get_total_wo_by_site_status`, `..._by_site_type`, `..._by_type`,
  `..._by_status`, `..._by_group`, `get_wo_top5_execute`,
  `get_wo_bottom5_execute`, `get_wo_average_execute_by_trade`.
- WorkOrderReportService: `get_report_wo_summary`, `get_report_wo_total`,
  `get_report_wo_daily`.
- SiteManualRepository: `add_siteManual`, `update_siteManual`.

`Class_ppm` (f_ppm.php) is decomposed the same way (scheduling, tasks, batch
sync, reports). `Class_gamification` and `Class_task` likewise.

## sql.php (Class_sql::get_sql) decomposition

The 1900-line `get_sql($title)` if/else is a stringly-typed view registry. Move
each named view into the repository that owns it as a private constant / method
returning the (now parameterized) query, deleting the `[param]` `str_replace`
substitution as you go (the substitution is the SQL-injection seam hardened in
`db.php`). Migrate one `vw_*`/`dt_*` title at a time.

## Safe migration loop (per slice)

1. Create the repository/service class with parameterized SafeQuery methods.
2. Add unit tests against an in-memory SQLite fixture (see
   `tests/Unit/WorkOrder/WorkOrderRepositoryTest.php`).
3. Change the legacy `Class_*` method to delegate to the new class (keep the old
   public signature so callers/endpoints are untouched).
4. Run `vendor/bin/phpunit` and the staging smoke tests (`tests/Smoke`).
5. Once all callers use the new class, delete the legacy method.

Delegating from the legacy method first (step 3) keeps the API contract intact
and lets the god file shrink method-by-method instead of in one dangerous edit.
