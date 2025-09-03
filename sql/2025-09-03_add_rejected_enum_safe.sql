-- Safely add 'REJECTED' to existing ENUM columns without losing current values
-- This script inspects current ENUM definitions and appends 'REJECTED' only if missing.

DELIMITER $$
DROP PROCEDURE IF EXISTS add_rejected_enum $$
CREATE PROCEDURE add_rejected_enum()
BEGIN
  DECLARE coltype TEXT;

  -- 1) ptw_permit.ptw_status
  SELECT COLUMN_TYPE INTO coltype
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'ptw_permit'
    AND COLUMN_NAME = 'ptw_status';

  IF coltype IS NOT NULL AND LOCATE('REJECTED', coltype) = 0 THEN
    SET @new_enum := REPLACE(coltype, ')', ',''REJECTED'')');
    SET @sql := CONCAT('ALTER TABLE `ptw_permit` MODIFY `ptw_status` ', @new_enum, ' NOT NULL DEFAULT ''DRAFT''');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;

  -- 2) ptw_status_history.action_type
  SELECT COLUMN_TYPE INTO coltype
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'ptw_status_history'
    AND COLUMN_NAME = 'action_type';

  IF coltype IS NOT NULL AND LOCATE('REJECTED', coltype) = 0 THEN
    SET @new_enum2 := REPLACE(coltype, ')', ',''REJECTED'')');
    SET @sql2 := CONCAT('ALTER TABLE `ptw_status_history` MODIFY `action_type` ', @new_enum2, ' NOT NULL');
    PREPARE stmt2 FROM @sql2;
    EXECUTE stmt2;
    DEALLOCATE PREPARE stmt2;
  END IF;
END $$
DELIMITER ;

CALL add_rejected_enum();
DROP PROCEDURE add_rejected_enum;
