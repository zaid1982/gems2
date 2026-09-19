-- Waste Generation / Pending Collection / Disposal lifecycle
-- Extends the existing JKR Scheduled Waste Register (wst_transaction) in place.
-- Idempotent: safe to run more than once.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- wst_transaction: lifecycle + disposal columns
-- ---------------------------------------------------------------------------

-- entry_mode: REGISTER = full Fifth Schedule form, SIMPLE = generation/disposal flow
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wst_transaction' AND COLUMN_NAME = 'entry_mode') = 0,
  'ALTER TABLE `wst_transaction` ADD COLUMN `entry_mode` varchar(10) NOT NULL DEFAULT ''REGISTER'' AFTER `txn_type`',
  'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- collection_status: on Produced records only. PENDING | DISPOSED
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wst_transaction' AND COLUMN_NAME = 'collection_status') = 0,
  'ALTER TABLE `wst_transaction` ADD COLUMN `collection_status` varchar(12) DEFAULT NULL AFTER `entry_mode`',
  'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- disposal_txn_id: Produced -> its Disposed record
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wst_transaction' AND COLUMN_NAME = 'disposal_txn_id') = 0,
  'ALTER TABLE `wst_transaction` ADD COLUMN `disposal_txn_id` int DEFAULT NULL AFTER `collection_status`',
  'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- parent_txn_id: Disposed -> originating Produced record
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wst_transaction' AND COLUMN_NAME = 'parent_txn_id') = 0,
  'ALTER TABLE `wst_transaction` ADD COLUMN `parent_txn_id` int DEFAULT NULL AFTER `disposal_txn_id`',
  'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wst_transaction' AND COLUMN_NAME = 'consignment_note_ref') = 0,
  'ALTER TABLE `wst_transaction` ADD COLUMN `consignment_note_ref` varchar(80) DEFAULT NULL',
  'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wst_transaction' AND COLUMN_NAME = 'consignment_receipt_ref') = 0,
  'ALTER TABLE `wst_transaction` ADD COLUMN `consignment_receipt_ref` varchar(80) DEFAULT NULL',
  'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wst_transaction' AND COLUMN_NAME = 'disposal_remarks') = 0,
  'ALTER TABLE `wst_transaction` ADD COLUMN `disposal_remarks` varchar(500) DEFAULT NULL',
  'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Index for the pending queue
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wst_transaction' AND INDEX_NAME = 'idx_wst_txn_collection') = 0,
  'ALTER TABLE `wst_transaction` ADD INDEX `idx_wst_txn_collection` (`site_id`, `collection_status`, `event_date`)',
  'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wst_transaction' AND INDEX_NAME = 'idx_wst_txn_parent') = 0,
  'ALTER TABLE `wst_transaction` ADD INDEX `idx_wst_txn_parent` (`parent_txn_id`)',
  'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Existing rows keep the register behaviour.
UPDATE `wst_transaction` SET `entry_mode` = 'REGISTER' WHERE `entry_mode` IS NULL OR `entry_mode` = '';

-- ---------------------------------------------------------------------------
-- wst_premise: defaults used to auto-fill SIMPLE records
-- ---------------------------------------------------------------------------
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wst_premise' AND COLUMN_NAME = 'default_handling_method_id') = 0,
  'ALTER TABLE `wst_premise` ADD COLUMN `default_handling_method_id` int DEFAULT NULL',
  'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wst_premise' AND COLUMN_NAME = 'default_location_id') = 0,
  'ALTER TABLE `wst_premise` ADD COLUMN `default_location_id` int DEFAULT NULL',
  'SELECT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- Document types for the disposal evidence
-- ---------------------------------------------------------------------------
INSERT INTO `wst_ref_value` (`value_type`, `site_id`, `value_name`, `value_status`)
SELECT v.t, NULL, v.n, 1
FROM (
  SELECT 'DOCUMENT_TYPE' AS t, 'Disposal Image - During' AS n
  UNION ALL SELECT 'DOCUMENT_TYPE', 'Disposal Image - After'
  UNION ALL SELECT 'DOCUMENT_TYPE', 'Consignment Receipt'
) v
WHERE NOT EXISTS (
  SELECT 1 FROM wst_ref_value r
  WHERE r.value_type = v.t AND r.site_id IS NULL AND r.value_name = v.n
);

-- ---------------------------------------------------------------------------
-- Audit actions for the new lifecycle
-- ---------------------------------------------------------------------------
INSERT INTO `sys_audit_action` (`audit_action_id`, `audit_action_desc`, `audit_module_id`, `audit_action_status`) VALUES
  (245, 'Generate Waste', 18, 1),
  (246, 'Edit Pending Waste', 18, 1),
  (247, 'Delete Pending Waste', 18, 1),
  (248, 'Execute Waste Disposal', 18, 1)
ON DUPLICATE KEY UPDATE
  `audit_action_desc` = VALUES(`audit_action_desc`),
  `audit_module_id` = VALUES(`audit_module_id`),
  `audit_action_status` = VALUES(`audit_action_status`);

UPDATE `sys_version` SET `version_no` = `version_no` + 1 WHERE `version_id` = 2;
