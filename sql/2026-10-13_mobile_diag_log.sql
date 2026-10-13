-- Mobile diagnostic activity log.
-- Maintenance requests a trail; the phone uploads it once on the next open.
-- Created: 2026-10-13

CREATE TABLE IF NOT EXISTS mob_diag_request (
    diag_request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    fulfilled_at DATETIME NULL,
    INDEX idx_mob_diag_request_user (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Pending or received request for a mobile activity trail';

CREATE TABLE IF NOT EXISTS mob_diag_log (
    diag_log_id INT AUTO_INCREMENT PRIMARY KEY,
    diag_request_id INT NULL,
    user_id INT NOT NULL,
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    app_version VARCHAR(40) NULL,
    online_flag TINYINT(1) NULL,
    log_text MEDIUMTEXT NOT NULL,
    INDEX idx_mob_diag_log_user (user_id, received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Activity trail uploaded from the mobile app';
