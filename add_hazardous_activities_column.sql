-- Add hazardous activities column to PTW permit table
-- This column stores the JSON data for all hazardous activities checkboxes

-- Check if column exists first, then add if it doesn't
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'ptw_permit' 
     AND COLUMN_NAME = 'ptw_hazardous_activities') = 0,
    'ALTER TABLE `ptw_permit` ADD COLUMN `ptw_hazardous_activities` JSON DEFAULT NULL;',
    'SELECT "Column ptw_hazardous_activities already exists" AS message;'
));

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update comment for documentation
ALTER TABLE `ptw_permit` 
COMMENT = 'PTW Permit table with enhanced checklist support including hazardous activities';
