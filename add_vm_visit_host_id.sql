-- Migration: add host_id column to vm_visit and backfill from host_name where possible
-- Idempotent: safe to run multiple times
-- Add column host_id if missing (portable across MySQL versions)
SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vm_visit' AND COLUMN_NAME = 'host_id'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE vm_visit ADD COLUMN host_id INT NULL AFTER party_size',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Backfill: join on site_id + name match (may be ambiguous; choose first match)
UPDATE vm_visit v
JOIN vm_host h ON h.site_id = v.site_id AND h.name = v.host_name
SET v.host_id = h.host_id
WHERE v.host_id IS NULL;

-- Add photo_path column for storing visitor photo path if missing
SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vm_visit' AND COLUMN_NAME = 'photo_path'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE vm_visit ADD COLUMN photo_path VARCHAR(255) NULL AFTER host_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Optional: add index for faster lookups
-- Add index if missing (portable across MySQL versions)
SET @idx := (
  SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vm_visit' AND INDEX_NAME = 'idx_vm_visit_host_id'
);
SET @sql := IF(@idx = 0,
  'ALTER TABLE vm_visit ADD INDEX idx_vm_visit_host_id (host_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Optional FK (uncomment after data clean ensuring all host_id values exist in vm_host):
-- ALTER TABLE vm_visit ADD CONSTRAINT fk_vm_visit_host FOREIGN KEY (host_id) REFERENCES vm_host(host_id);
