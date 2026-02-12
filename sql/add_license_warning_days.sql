-- Add per-license warning threshold in days (nullable, default 30 when null is treated in code)
ALTER TABLE lic_license
  ADD COLUMN warning_days INT NULL AFTER upload_id;

-- Add explicit date-based warning start (optional precedence over days)
ALTER TABLE lic_license
  ADD COLUMN warning_date DATE NULL AFTER warning_days;

-- Optional: backfill with a sensible default (30 days) for existing data
-- UPDATE lic_license SET warning_days = 30 WHERE warning_days IS NULL;
