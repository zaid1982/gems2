-- Migration: Seed PTW audit module/actions used by api/ptw_approve.php
-- Fixes sys_audit foreign-key failures for PTW close/extend/cancel/suspend actions.

INSERT INTO `sys_audit_module` (`audit_module_id`, `audit_module_desc`, `audit_module_status`) VALUES
  (17, 'Permit To Work', 1)
ON DUPLICATE KEY UPDATE
  `audit_module_desc` = VALUES(`audit_module_desc`),
  `audit_module_status` = VALUES(`audit_module_status`);

INSERT INTO `sys_audit_action` (`audit_action_id`, `audit_action_desc`, `audit_module_id`, `audit_action_status`) VALUES
  (228, 'Request PTW Closure', 17, 1),
  (229, 'Approve PTW Closure', 17, 1),
  (230, 'Request PTW Extension', 17, 1),
  (231, 'Approve PTW Extension', 17, 1),
  (232, 'Request PTW Cancellation', 17, 1),
  (233, 'Approve PTW Cancellation', 17, 1),
  (234, 'Request PTW Suspension', 17, 1),
  (235, 'Approve PTW Suspension', 17, 1)
ON DUPLICATE KEY UPDATE
  `audit_action_desc` = VALUES(`audit_action_desc`),
  `audit_module_id` = VALUES(`audit_module_id`),
  `audit_action_status` = VALUES(`audit_action_status`);
