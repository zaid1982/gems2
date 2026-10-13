-- Devices signed in to a mobile or browser session.
-- A removed device clears its local account data the next time it opens.
-- Created: 2026-10-13

CREATE TABLE IF NOT EXISTS mob_user_device (
    user_device_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_key VARCHAR(64) NOT NULL,
    platform VARCHAR(20) NOT NULL DEFAULT 'unknown',
    device_label VARCHAR(120) NOT NULL,
    app_version VARCHAR(40) NULL,
    last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    removed_at DATETIME NULL,
    UNIQUE KEY uniq_user_device (user_id, device_key),
    INDEX idx_mob_user_device_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Phones and browsers signed in to a user account';

SET @mob_diag_device_col = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'mob_diag_request'
      AND COLUMN_NAME = 'device_key'
);
SET @mob_diag_device_sql = IF(
    @mob_diag_device_col = 0,
    'ALTER TABLE mob_diag_request ADD COLUMN device_key VARCHAR(64) NULL AFTER user_id',
    'SELECT 1'
);
PREPARE mob_diag_device_stmt FROM @mob_diag_device_sql;
EXECUTE mob_diag_device_stmt;
DEALLOCATE PREPARE mob_diag_device_stmt;
