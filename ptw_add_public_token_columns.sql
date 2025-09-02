-- PTW Public Token Migration
-- Adds columns to gate public view access via opaque token

ALTER TABLE `ptw_permit`
  ADD COLUMN `public_token` VARCHAR(128) NULL AFTER `ptw_certificate_numbers`,
  ADD COLUMN `public_token_expires_at` DATETIME NULL AFTER `public_token`,
  ADD COLUMN `public_link_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `public_token_expires_at`,
  ADD COLUMN `public_token_revoked_at` DATETIME NULL AFTER `public_link_enabled`,
  ADD INDEX `idx_public_token` (`public_token`);

-- Rollback (optional)
-- ALTER TABLE `ptw_permit`
--   DROP INDEX `idx_public_token`,
--   DROP COLUMN `public_token_revoked_at`,
--   DROP COLUMN `public_link_enabled`,
--   DROP COLUMN `public_token_expires_at`,
--   DROP COLUMN `public_token`;
