-- PPM Offline Sync Log Table
-- Purpose: Track batch sync attempts for idempotency and audit trail
-- Created: 2025-11-11
-- Feature: PPM Offline Batch Sync

CREATE TABLE IF NOT EXISTS ppm_offline_sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ppm_task_id VARCHAR(50) NOT NULL COMMENT 'PPM Task ID being synced',
    sync_timestamp DATETIME NOT NULL COMMENT 'Client-provided sync timestamp for idempotency',
    device_id VARCHAR(100) NOT NULL COMMENT 'Mobile device identifier',
    user_id INT NOT NULL COMMENT 'User performing the sync',
    
    -- Sync metrics
    total_actions INT NOT NULL DEFAULT 0 COMMENT 'Total number of actions in batch',
    success_count INT NOT NULL DEFAULT 0 COMMENT 'Number of successful actions',
    failed_count INT NOT NULL DEFAULT 0 COMMENT 'Number of failed actions',
    
    -- Payloads for debugging and audit
    request_payload TEXT COMMENT 'Full JSON request body',
    response_payload TEXT COMMENT 'Full JSON response body',
    
    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Server timestamp when sync was processed',
    
    -- Idempotency constraint: same task + timestamp + device = duplicate
    UNIQUE KEY unique_sync (ppm_task_id, sync_timestamp, device_id),
    
    -- Indexes for queries
    INDEX idx_ppm_task (ppm_task_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tracks PPM offline batch sync attempts for idempotency';

-- Add completedOffline flag to ppm_task if not exists
ALTER TABLE ppm_task 
ADD COLUMN IF NOT EXISTS ppm_task_completed_offline TINYINT(1) DEFAULT 0 COMMENT '1 if task was completed offline' 
AFTER ppm_task_time_verified;

-- Success message
SELECT 'PPM Offline Sync Log table created successfully!' as message;
