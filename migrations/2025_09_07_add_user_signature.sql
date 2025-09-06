-- Migration: Add user signature master table and snapshot columns for PTW approvals
-- Date: 2025-09-07
-- Purpose: Store reusable approver signatures and persist snapshot copies used at approval time
-- Execution: Run manually (e.g. via mysql CLI). Idempotent where possible.

-- 1. Master table to hold the latest signature per user
CREATE TABLE IF NOT EXISTS `sys_user_signature` (
  `user_id` INT NOT NULL,
  `signature_path` VARCHAR(255) NOT NULL COMMENT 'Filesystem relative path to current signature image',
  `signature_sha256` CHAR(64) NOT NULL COMMENT 'SHA-256 hash of file content for integrity',
  `mime_type` VARCHAR(50) NOT NULL DEFAULT 'image/png',
  `width` SMALLINT DEFAULT NULL,
  `height` SMALLINT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_user_sig_user` FOREIGN KEY (`user_id`) REFERENCES `sys_user`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Snapshot columns on ptw_approval_log so historical PDFs remain stable even if user updates signature later
-- MySQL < 8 has no IF NOT EXISTS for ADD COLUMN; running twice will raise 1060 (duplicate column). Ignore that error if re-running.
ALTER TABLE `ptw_approval_log` 
  ADD COLUMN `signature_path_snapshot` VARCHAR(255) NULL AFTER `approved_date`;

ALTER TABLE `ptw_approval_log` 
  ADD COLUMN `signature_sha256_snapshot` CHAR(64) NULL AFTER `signature_path_snapshot`;

-- 3. (Optional) Index if querying logs by presence of snapshot
-- CREATE INDEX idx_ptw_approval_signature_path ON `ptw_approval_log`(`signature_path_snapshot`);

-- 4. Suggested filesystem layout (create manually, ensure web server write perms):
--   /uploads/signatures/master/user_<userId>.png            (latest master)
--   /uploads/signatures/ptw/permit_<permitId>_log_<logId>_user_<userId>.png (immutable snapshot)

-- 5. Rollback (manual):
--   DROP TABLE IF EXISTS `sys_user_signature`;
--   ALTER TABLE `ptw_approval_log` DROP COLUMN `signature_sha256_snapshot`;
--   ALTER TABLE `ptw_approval_log` DROP COLUMN `signature_path_snapshot`;

-- End of migration