-- Indexes for notification deep-link lookups in NotiHelper.
-- ppm_task.ppm_task_no already has idx_ppm_task_no.
-- wo_task.wo_task_no and wo_task_request.wo_task_request_no do not.
-- Safe to run more than once.

SELECT COUNT(*) INTO @index_exists
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name = 'wo_task'
  AND index_name = 'idx_wo_task_no';

SET @sql = IF(@index_exists = 0,
    'CREATE INDEX idx_wo_task_no ON wo_task (wo_task_no)',
    'SELECT "Index idx_wo_task_no already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @index_exists
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name = 'wo_task_request'
  AND index_name = 'idx_wo_task_request_no';

SET @sql = IF(@index_exists = 0,
    'CREATE INDEX idx_wo_task_request_no ON wo_task_request (wo_task_request_no)',
    'SELECT "Index idx_wo_task_request_no already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
