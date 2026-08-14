-- Push notification retry / dead-letter support
-- Run once on gems2 (and gfm_jkr if used) before deploying scheduler retry changes.
-- Safe to re-run on MariaDB (IF NOT EXISTS).

ALTER TABLE noti_send
    ADD COLUMN IF NOT EXISTS noti_retry_count TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER noti_time_created;

ALTER TABLE noti_send
    ADD COLUMN IF NOT EXISTS noti_next_retry_at DATETIME NULL DEFAULT NULL AFTER noti_retry_count;

ALTER TABLE noti_send
    ADD COLUMN IF NOT EXISTS noti_last_error TEXT NULL AFTER noti_next_retry_at;

-- Helps the scheduler pick due rows quickly
CREATE INDEX IF NOT EXISTS idx_noti_send_next_retry ON noti_send (noti_next_retry_at, noti_id);
