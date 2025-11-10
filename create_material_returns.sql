-- ============================================================================
-- GEMS2 Material Returns Module - Database Schema
-- Created: 9 November 2025
-- Purpose: Enable technicians to return collected parts to storekeeper
-- ============================================================================

-- Add new status codes for material returns
INSERT INTO ref_status (status_id, status_desc, status_group) VALUES 
(47, 'Returned', 'Parts'),
(48, 'Return Pending', 'Parts')
ON DUPLICATE KEY UPDATE status_desc = VALUES(status_desc);

-- Create material_returns table
CREATE TABLE IF NOT EXISTS material_returns (
    return_id INT PRIMARY KEY AUTO_INCREMENT,
    wo_task_parts_id INT NOT NULL COMMENT 'Reference to original material collection',
    part_id INT NOT NULL COMMENT 'Reference to parts table',
    technician_user_id INT NOT NULL COMMENT 'User who requested the return',
    storekeeper_user_id INT NULL COMMENT 'User who confirmed the return',
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

-- Add return tracking fields to ast_part_sub (MySQL 5.7 compatible)
-- Note: MySQL doesn't support IF NOT EXISTS for ALTER TABLE ADD COLUMN
-- These statements will fail if columns already exist, which is safe to ignore

-- Add columns (will error if exists, safe to ignore)
ALTER TABLE ast_part_sub 
ADD COLUMN part_sub_return_id INT NULL COMMENT 'FK to material_returns if returned';

ALTER TABLE ast_part_sub 
ADD COLUMN part_sub_returned_date DATETIME NULL COMMENT 'When part was returned to inventory';

ALTER TABLE ast_part_sub 
ADD COLUMN part_sub_returned_by INT NULL COMMENT 'User who returned the part';

-- Add indexes (check if exists first)
SET @exist_idx_return := (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() AND table_name = 'ast_part_sub' AND index_name = 'idx_return_id');
SET @sql_idx_return := IF(@exist_idx_return = 0, 
    'ALTER TABLE ast_part_sub ADD INDEX idx_return_id (part_sub_return_id)', 
    'SELECT "Index idx_return_id already exists"');
PREPARE stmt FROM @sql_idx_return;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_idx_returned_date := (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() AND table_name = 'ast_part_sub' AND index_name = 'idx_returned_date');
SET @sql_idx_returned_date := IF(@exist_idx_returned_date = 0, 
    'ALTER TABLE ast_part_sub ADD INDEX idx_returned_date (part_sub_returned_date)', 
    'SELECT "Index idx_returned_date already exists"');
PREPARE stmt FROM @sql_idx_returned_date;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign keys (check if exists first)
SET @exist_fk_return := (SELECT COUNT(*) FROM information_schema.table_constraints 
    WHERE table_schema = DATABASE() AND table_name = 'ast_part_sub' AND constraint_name = 'fk_part_sub_return');
SET @sql_fk_return := IF(@exist_fk_return = 0, 
    'ALTER TABLE ast_part_sub ADD CONSTRAINT fk_part_sub_return FOREIGN KEY (part_sub_return_id) REFERENCES material_returns(return_id) ON DELETE SET NULL', 
    'SELECT "Foreign key fk_part_sub_return already exists"');
PREPARE stmt FROM @sql_fk_return;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_fk_returned_by := (SELECT COUNT(*) FROM information_schema.table_constraints 
    WHERE table_schema = DATABASE() AND table_name = 'ast_part_sub' AND constraint_name = 'fk_part_sub_returned_by');
SET @sql_fk_returned_by := IF(@exist_fk_returned_by = 0, 
    'ALTER TABLE ast_part_sub ADD CONSTRAINT fk_part_sub_returned_by FOREIGN KEY (part_sub_returned_by) REFERENCES sys_user(user_id) ON DELETE SET NULL', 
    'SELECT "Foreign key fk_part_sub_returned_by already exists"');
PREPARE stmt FROM @sql_fk_returned_by;
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
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    part_id INT NOT NULL,
    change_type ENUM('purchase', 'checkout', 'return', 'adjustment', 'transfer') NOT NULL,
    quantity_change INT NOT NULL COMMENT 'Positive for add, negative for remove',
    quantity_before INT NOT NULL,
    quantity_after INT NOT NULL,
    user_id INT NOT NULL,
    reference_id INT NULL COMMENT 'ID of related record (return_id, wo_task_parts_id, etc)',
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
-- Sample Test Data (for development/testing only)
-- ============================================================================

-- Insert sample return reasons (these will be used in frontend dropdown)
-- Store in a configuration table or handle in code
-- VALUES: 'unused_excess', 'wrong_part', 'damaged', 'other'

-- ============================================================================
-- Rollback Script (run this to undo changes if needed)
-- ============================================================================
/*
-- Remove foreign keys from ast_part_sub
ALTER TABLE ast_part_sub 
DROP FOREIGN KEY fk_part_sub_return;

ALTER TABLE ast_part_sub 
DROP FOREIGN KEY fk_part_sub_returned_by;

ALTER TABLE ast_part_sub 
DROP INDEX idx_return_id;

ALTER TABLE ast_part_sub 
DROP INDEX idx_returned_date;

ALTER TABLE ast_part_sub 
DROP COLUMN part_sub_return_id;

ALTER TABLE ast_part_sub 
DROP COLUMN part_sub_returned_date;

ALTER TABLE ast_part_sub 
DROP COLUMN part_sub_returned_by;

-- Drop tables
DROP TABLE IF EXISTS inventory_logs;
DROP TABLE IF EXISTS material_returns;

-- Remove status codes
DELETE FROM ref_status WHERE status_id IN (47, 48);
*/

-- ============================================================================
-- Verification Queries (run after migration to verify)
-- ============================================================================

-- Check if tables exist
SHOW TABLES LIKE '%return%';

-- Check material_returns structure
DESCRIBE material_returns;

-- Check ast_part_sub new columns
DESCRIBE ast_part_sub;

-- Check if views exist (should be empty - views are virtual in GEMS2)
SHOW FULL TABLES WHERE table_type = 'VIEW' AND Tables_in_gems2 LIKE '%return%';

-- Verify status codes
SELECT * FROM ref_status WHERE status_id IN (47, 48);

-- ============================================================================
-- End of Migration Script
-- ============================================================================
