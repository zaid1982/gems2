-- Ensure ptw_checklist_cold_work column exists on ptw_permit
-- Safe for MySQL 8.0+ (uses IF NOT EXISTS); for older versions, apply conditionally.

ALTER TABLE `ptw_permit`
  ADD COLUMN IF NOT EXISTS `ptw_checklist_cold_work` TEXT NULL AFTER `ptw_hazard_checklist`;

-- Optional: add an index for JSON search in future (commented out)
-- CREATE INDEX IF NOT EXISTS idx_ptw_checklist_cold_work_json ON ptw_permit ((CAST(ptw_checklist_cold_work AS JSON)));
