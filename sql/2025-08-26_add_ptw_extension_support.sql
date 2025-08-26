-- PTW Extension Workflow Support
-- 1) Add extension request columns to ptw_permit
ALTER TABLE `ptw_permit`
  ADD COLUMN IF NOT EXISTS `ptw_extension_requested_to` DATETIME NULL AFTER `ptw_valid_to`,
  ADD COLUMN IF NOT EXISTS `ptw_extension_requested_by` INT NULL AFTER `ptw_extension_requested_to`,
  ADD COLUMN IF NOT EXISTS `ptw_extension_requested_remarks` TEXT NULL AFTER `ptw_extension_requested_by`,
  ADD COLUMN IF NOT EXISTS `ptw_extension_requested_at` DATETIME NULL AFTER `ptw_extension_requested_remarks`;

-- 2) Extend ptw_status_history.action_type enum to include extension events
ALTER TABLE `ptw_status_history`
  MODIFY `action_type` enum(
    'CREATED','SUBMITTED',
    'SUPERVISOR_APPROVED','SUPERVISOR_REJECTED',
    'SHE_APPROVED','SHE_REJECTED',
    'FM_APPROVED','FM_REJECTED',
    'ACTIVATED',
    'CLOSURE_REQUESTED','CLOSED',
    'EXTENSION_REQUESTED','EXTENDED',
    'COMPLETED','CANCELLED'
  ) NOT NULL;
