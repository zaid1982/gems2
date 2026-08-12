-- Mobile notification routing + inbox read state
-- Run once on gems2 database before deploying notification changes.

ALTER TABLE noti_send
    ADD COLUMN IF NOT EXISTS noti_data TEXT NULL AFTER noti_html;

ALTER TABLE noti_log
    ADD COLUMN IF NOT EXISTS noti_data TEXT NULL AFTER noti_html;

ALTER TABLE noti_log
    ADD COLUMN IF NOT EXISTS noti_log_read_at TIMESTAMP NULL DEFAULT NULL AFTER noti_log_status;
