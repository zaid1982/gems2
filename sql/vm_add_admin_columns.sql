-- Adds optional columns for admin operations
ALTER TABLE `vm_visit`
  ADD COLUMN `archived_at` DATETIME NULL AFTER `status`,
  ADD COLUMN `deleted_at` DATETIME NULL AFTER `archived_at`;

-- Optionally extend status enum to include ARCHIVED (safe to run only if not exists; adjust manually if needed)
-- Example (may require exact current enum list):
-- ALTER TABLE `vm_visit`
--   MODIFY `status` ENUM('CHECKED_IN','CHECKED_OUT','CANCELLED','ARCHIVED') NOT NULL DEFAULT 'CHECKED_IN';
