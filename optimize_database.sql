-- Database Optimization Script for PPM Import Performance
-- Run this script to improve import performance

-- =====================================================
-- CRITICAL INDEXES FOR PPM IMPORT
-- =====================================================

-- Check if indexes exist before creating
SET @sql = '';

-- Index for PPM task number lookups
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.statistics 
WHERE table_schema = DATABASE() 
AND table_name = 'ppm_task' 
AND index_name = 'idx_ppm_task_no';

SET @sql = IF(@index_exists = 0, 
    'CREATE INDEX idx_ppm_task_no ON ppm_task(ppm_task_no)',
    'SELECT "Index idx_ppm_task_no already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index for asset code lookups
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.statistics 
WHERE table_schema = DATABASE() 
AND table_name = 'ast_asset' 
AND index_name = 'idx_asset_no';

SET @sql = IF(@index_exists = 0, 
    'CREATE INDEX idx_asset_no ON ast_asset(asset_no)',
    'SELECT "Index idx_asset_no already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index for user name lookups
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.statistics 
WHERE table_schema = DATABASE() 
AND table_name = 'sys_user' 
AND index_name = 'idx_user_name';

SET @sql = IF(@index_exists = 0, 
    'CREATE INDEX idx_user_name ON sys_user(user_name)',
    'SELECT "Index idx_user_name already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- ADDITIONAL PERFORMANCE INDEXES
-- =====================================================

-- Index for PPM task status queries
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.statistics 
WHERE table_schema = DATABASE() 
AND table_name = 'ppm_task' 
AND index_name = 'idx_ppm_task_status';

SET @sql = IF(@index_exists = 0, 
    'CREATE INDEX idx_ppm_task_status ON ppm_task(ppm_task_status)',
    'SELECT "Index idx_ppm_task_status already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index for PPM task assignment queries
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.statistics 
WHERE table_schema = DATABASE() 
AND table_name = 'ppm_task' 
AND index_name = 'idx_ppm_task_assigned';

SET @sql = IF(@index_exists = 0, 
    'CREATE INDEX idx_ppm_task_assigned ON ppm_task(ppm_task_assigned_to)',
    'SELECT "Index idx_ppm_task_assigned already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Composite index for common PPM task queries
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.statistics 
WHERE table_schema = DATABASE() 
AND table_name = 'ppm_task' 
AND index_name = 'idx_ppm_task_composite';

SET @sql = IF(@index_exists = 0, 
    'CREATE INDEX idx_ppm_task_composite ON ppm_task(ppm_task_status, ppm_task_assigned_to, ppm_task_schedule_date)',
    'SELECT "Index idx_ppm_task_composite already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- PERFORMANCE ANALYSIS QUERIES
-- =====================================================

-- Show current table sizes
SELECT 
    TABLE_NAME as 'Table',
    TABLE_ROWS as 'Rows',
    ROUND(DATA_LENGTH/1024/1024, 2) as 'Data Size (MB)',
    ROUND(INDEX_LENGTH/1024/1024, 2) as 'Index Size (MB)'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('ppm_task', 'ast_asset', 'sys_user')
ORDER BY DATA_LENGTH DESC;

-- Show indexes on critical tables
SELECT 
    TABLE_NAME as 'Table',
    INDEX_NAME as 'Index',
    COLUMN_NAME as 'Column',
    NON_UNIQUE as 'Non-Unique',
    CARDINALITY as 'Cardinality'
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('ppm_task', 'ast_asset', 'sys_user')
ORDER BY TABLE_NAME, INDEX_NAME;

-- =====================================================
-- QUERY OPTIMIZATION RECOMMENDATIONS
-- =====================================================

-- Check for missing indexes on foreign keys
SELECT 
    DISTINCT
    CONCAT(
        'Consider adding index: CREATE INDEX idx_', 
        TABLE_NAME, '_', COLUMN_NAME, 
        ' ON ', TABLE_NAME, '(', COLUMN_NAME, ');'
    ) as 'Recommendation'
FROM information_schema.KEY_COLUMN_USAGE k
LEFT JOIN information_schema.STATISTICS s ON (
    s.TABLE_SCHEMA = k.TABLE_SCHEMA 
    AND s.TABLE_NAME = k.TABLE_NAME 
    AND s.COLUMN_NAME = k.COLUMN_NAME
    AND s.INDEX_NAME != 'PRIMARY'
)
WHERE k.TABLE_SCHEMA = DATABASE()
AND k.REFERENCED_TABLE_NAME IS NOT NULL
AND s.COLUMN_NAME IS NULL
AND k.TABLE_NAME IN ('ppm_task', 'ast_asset', 'sys_user');

-- =====================================================
-- DATABASE CONFIGURATION CHECKS
-- =====================================================

-- Show important MySQL variables
SELECT 
    'Current MySQL Configuration' as 'Section',
    '' as 'Variable',
    '' as 'Value',
    '' as 'Recommendation'
UNION ALL
SELECT 
    '',
    'innodb_buffer_pool_size',
    @@innodb_buffer_pool_size / 1024 / 1024 as 'Value (MB)',
    'Should be 70-80% of available RAM'
UNION ALL
SELECT 
    '',
    'query_cache_size',
    @@query_cache_size / 1024 / 1024 as 'Value (MB)',
    'Recommended: 32-64MB'
UNION ALL
SELECT 
    '',
    'max_connections',
    @@max_connections,
    'Adjust based on concurrent users'
UNION ALL
SELECT 
    '',
    'innodb_log_file_size',
    @@innodb_log_file_size / 1024 / 1024 as 'Value (MB)',
    'Recommended: 256MB+'
UNION ALL
SELECT 
    '',
    'innodb_flush_log_at_trx_commit',
    @@innodb_flush_log_at_trx_commit,
    'Use 2 for better performance (less durability)';

-- Show slow query log status
SHOW VARIABLES LIKE 'slow_query_log%';
SHOW VARIABLES LIKE 'long_query_time';

-- =====================================================
-- CLEANUP AND MAINTENANCE
-- =====================================================

-- Optimize tables after index creation
OPTIMIZE TABLE ppm_task;
OPTIMIZE TABLE ast_asset;
OPTIMIZE TABLE sys_user;

-- Update table statistics
ANALYZE TABLE ppm_task;
ANALYZE TABLE ast_asset;
ANALYZE TABLE sys_user;

SELECT 'Database optimization completed! Check the output above for any missing indexes or configuration recommendations.' as 'Status';
