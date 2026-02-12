-- ============================================================================
-- GEMS2 Material Returns Module - Database Schema (MySQL/MariaDB Safe)
-- Created: 10 November 2025
-- Purpose: Enable technicians to return collected parts to storekeeper
-- Tested: MariaDB 10.4.28
-- ============================================================================

-- Add new status codes for material returns
INSERT INTO ref_status (status_id, status_desc) VALUES 
(47, 'Returned'),
(48, 'Return Pending')
ON DUPLICATE KEY UPDATE status_desc = VALUES(status_desc);

-- Create material_returns table
CREATE TABLE IF NOT EXISTS material_returns (
    return_id BIGINT(20) PRIMARY KEY AUTO_INCREMENT,
    wo_task_parts_id BIGINT(20) NOT NULL COMMENT 'Reference to original material collection',
    part_id BIGINT(20) NOT NULL COMMENT 'Reference to parts table',
    technician_user_id INT(11) NOT NULL COMMENT 'User who requested the return',
    storekeeper_user_id INT(11) NULL COMMENT 'User who confirmed the return',
    quantity_returned INT NOT NULL COMMENT 'Quantity being returned (supports partial returns)',
    return_status ENUM('pending', 'completed') DEFAULT 'pending' COMMENT 'Return workflow status',
    return_reason VARCHAR(255) NOT NULL COMMENT 'Reason from dropdown: unused_excess, wrong_part, damaged, other',
    return_remarks TEXT NULL COMMENT 'Optional free text remarks',
    return_request_date DATETIME NOT NULL COMMENT 'When technician submitted return',
    return_confirmed_date DATETIME NULL COMMENT 'When storekeeper confirmed receipt',
    return_deadline_date DATETIME NULL COMMENT 'Optional deadline for return (not enforced)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (wo_task_parts_id) REFERENCES wo_task_parts(wo_task_parts_id) ON DELETE RESTRICT,
    FOREIGN KEY (part_id) REFERENCES ast_part(part_id) ON DELETE RESTRICT,
    FOREIGN KEY (technician_user_id) REFERENCES sys_user(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (storekeeper_user_id) REFERENCES sys_user(user_id) ON DELETE SET NULL,
    
    -- Indexes for performance
    INDEX idx_technician (technician_user_id),
    INDEX idx_storekeeper (storekeeper_user_id),
    INDEX idx_status (return_status),
    INDEX idx_request_date (return_request_date),
    INDEX idx_wo_task_parts (wo_task_parts_id),
    INDEX idx_part_id (part_id)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Material return requests and confirmations';

-- ============================================================================
-- Add return tracking fields to ast_part_sub
-- Safe approach: Check and add each item separately
-- ============================================================================

-- Check if columns exist, add if they don't
SET @col_count = (
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ast_part_sub' 
    AND column_name = 'part_sub_return_id'
);

SET @sql_add_col1 = IF(@col_count = 0,
    'ALTER TABLE ast_part_sub ADD COLUMN part_sub_return_id BIGINT(20) NULL COMMENT "FK to material_returns if returned"',
    'SELECT "Column part_sub_return_id already exists" AS message'
);
PREPARE stmt FROM @sql_add_col1;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_count = (
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ast_part_sub' 
    AND column_name = 'part_sub_returned_date'
);

SET @sql_add_col2 = IF(@col_count = 0,
    'ALTER TABLE ast_part_sub ADD COLUMN part_sub_returned_date DATETIME NULL COMMENT "When part was returned to inventory"',
    'SELECT "Column part_sub_returned_date already exists" AS message'
);
PREPARE stmt FROM @sql_add_col2;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_count = (
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ast_part_sub' 
    AND column_name = 'part_sub_returned_by'
);

SET @sql_add_col3 = IF(@col_count = 0,
    'ALTER TABLE ast_part_sub ADD COLUMN part_sub_returned_by INT(11) NULL COMMENT "User who returned the part"',
    'SELECT "Column part_sub_returned_by already exists" AS message'
);
PREPARE stmt FROM @sql_add_col3;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes (safe check)
SET @idx_count = (
    SELECT COUNT(*) 
    FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ast_part_sub' 
    AND index_name = 'idx_return_id'
);

SET @sql_add_idx1 = IF(@idx_count = 0,
    'ALTER TABLE ast_part_sub ADD INDEX idx_return_id (part_sub_return_id)',
    'SELECT "Index idx_return_id already exists" AS message'
);
PREPARE stmt FROM @sql_add_idx1;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_count = (
    SELECT COUNT(*) 
    FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ast_part_sub' 
    AND index_name = 'idx_returned_date'
);

SET @sql_add_idx2 = IF(@idx_count = 0,
    'ALTER TABLE ast_part_sub ADD INDEX idx_returned_date (part_sub_returned_date)',
    'SELECT "Index idx_returned_date already exists" AS message'
);
PREPARE stmt FROM @sql_add_idx2;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign keys (safe check)
SET @fk_count = (
    SELECT COUNT(*) 
    FROM information_schema.table_constraints 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ast_part_sub' 
    AND constraint_name = 'fk_part_sub_return'
);

SET @sql_add_fk1 = IF(@fk_count = 0,
    'ALTER TABLE ast_part_sub ADD CONSTRAINT fk_part_sub_return FOREIGN KEY (part_sub_return_id) REFERENCES material_returns(return_id) ON DELETE SET NULL',
    'SELECT "Foreign key fk_part_sub_return already exists" AS message'
);
PREPARE stmt FROM @sql_add_fk1;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_count = (
    SELECT COUNT(*) 
    FROM information_schema.table_constraints 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ast_part_sub' 
    AND constraint_name = 'fk_part_sub_returned_by'
);

SET @sql_add_fk2 = IF(@fk_count = 0,
    'ALTER TABLE ast_part_sub ADD CONSTRAINT fk_part_sub_returned_by FOREIGN KEY (part_sub_returned_by) REFERENCES sys_user(user_id) ON DELETE SET NULL',
    'SELECT "Foreign key fk_part_sub_returned_by already exists" AS message'
);
PREPARE stmt FROM @sql_add_fk2;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- IMPORTANT: Views are NOT created in database
-- ============================================================================
-- GEMS2 uses on-demand view generation via api/library/sql.php
-- The following views have been added to Class_sql::get_sql() method:
--   1. vw_return_eligible_items - Lists items available for return
--   2. vw_storekeeper_pending_returns - Lists pending return requests
-- These views are generated dynamically when queried, not stored in database.
-- ============================================================================

-- Create optional inventory_logs table for audit trail (recommended)
CREATE TABLE IF NOT EXISTS inventory_logs (
    log_id BIGINT(20) PRIMARY KEY AUTO_INCREMENT,
    part_id BIGINT(20) NOT NULL,
    change_type ENUM('purchase', 'checkout', 'return', 'adjustment', 'transfer') NOT NULL,
    quantity_change INT NOT NULL COMMENT 'Positive for add, negative for remove',
    quantity_before INT NOT NULL,
    quantity_after INT NOT NULL,
    user_id INT(11) NOT NULL,
    reference_id BIGINT(20) NULL COMMENT 'ID of related record (return_id, wo_task_parts_id, etc)',
    reference_type VARCHAR(50) NULL COMMENT 'Type of reference (material_return, wo_checkout, etc)',
    change_reason TEXT NULL,
    change_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (part_id) REFERENCES ast_part(part_id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES sys_user(user_id) ON DELETE RESTRICT,
    
    INDEX idx_part_id (part_id),
    INDEX idx_change_date (change_date),
    INDEX idx_change_type (change_type),
    INDEX idx_reference (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Audit trail for all inventory changes';

-- ============================================================================
-- Verification Queries (run after migration to verify)
-- ============================================================================

SELECT '=== Material Returns Migration Complete ===' AS status;

-- Check if tables exist
SELECT 'Checking tables...' AS step;
SHOW TABLES LIKE '%return%';

-- Check material_returns structure
SELECT 'Material returns table structure:' AS step;
DESCRIBE material_returns;

-- Check ast_part_sub new columns
SELECT 'Checking ast_part_sub columns...' AS step;
SELECT column_name, column_type, is_nullable 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'ast_part_sub' 
AND column_name LIKE '%return%';

-- Verify status codes
SELECT 'Checking status codes...' AS step;
SELECT * FROM ref_status WHERE status_id IN (47, 48);

-- Check indexes
SELECT 'Checking indexes...' AS step;
SELECT DISTINCT index_name 
FROM information_schema.statistics 
WHERE table_schema = DATABASE() 
AND table_name = 'ast_part_sub' 
AND index_name IN ('idx_return_id', 'idx_returned_date');

-- Check foreign keys
SELECT 'Checking foreign keys...' AS step;
SELECT constraint_name, table_name 
FROM information_schema.table_constraints 
WHERE table_schema = DATABASE() 
AND table_name = 'ast_part_sub' 
AND constraint_name LIKE '%return%';

SELECT '=== Verification Complete ===' AS status;

-- ============================================================================
-- Rollback Script (run this to undo changes if needed)
-- ============================================================================
/*
-- Drop foreign keys
ALTER TABLE ast_part_sub DROP FOREIGN KEY fk_part_sub_return;
ALTER TABLE ast_part_sub DROP FOREIGN KEY fk_part_sub_returned_by;

-- Drop indexes
ALTER TABLE ast_part_sub DROP INDEX idx_return_id;
ALTER TABLE ast_part_sub DROP INDEX idx_returned_date;

-- Drop columns
ALTER TABLE ast_part_sub DROP COLUMN part_sub_return_id;
ALTER TABLE ast_part_sub DROP COLUMN part_sub_returned_date;
ALTER TABLE ast_part_sub DROP COLUMN part_sub_returned_by;

-- Drop tables
DROP TABLE IF EXISTS inventory_logs;
DROP TABLE IF EXISTS material_returns;

-- Remove status codes
DELETE FROM ref_status WHERE status_id IN (47, 48);
*/

-- ============================================================================
-- End of Migration Script
-- ============================================================================
