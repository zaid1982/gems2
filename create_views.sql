-- Views for Weekly Gamification System
-- Fixed to work with existing gamification code

-- PPM Daily View
CREATE OR REPLACE VIEW `vw_gamification_ppm_daily` AS
SELECT 
    pt.ppm_task_assigned_to as ppmTaskAssignedTo,
    u.site_id as siteId,
    NULL as gmiId,
    DATE(pt.ppm_task_time_assigned) as task_date,
    COUNT(*) as ppmTotal,
    SUM(CASE WHEN pt.ppm_task_status = 16 THEN 1 ELSE 0 END) as ppmCompleted,
    SUM(CASE WHEN pt.ppm_task_status = 16 AND pt.ppm_task_time_verified <= pt.ppm_task_schedule_date THEN 1 ELSE 0 END) as ppmOnTime,
    SUM(CASE WHEN pt.ppm_task_status = 16 AND pt.ppm_task_time_verified > pt.ppm_task_schedule_date THEN 1 ELSE 0 END) as ppmLate,
    SUM(CASE WHEN pt.ppm_task_status = 16 AND pt.ppm_task_time_verified <= DATE_ADD(pt.ppm_task_schedule_date, INTERVAL 1 DAY) THEN 1 ELSE 0 END) as ppmWithin
FROM ppm_task pt
JOIN sys_user u ON pt.ppm_task_assigned_to = u.user_id
WHERE pt.ppm_task_time_assigned IS NOT NULL
AND pt.ppm_task_assigned_to IS NOT NULL
GROUP BY pt.ppm_task_assigned_to, u.site_id, DATE(pt.ppm_task_time_assigned);

-- PPM Assist Daily View
CREATE OR REPLACE VIEW `vw_gamification_ppm_assist_daily` AS
SELECT 
    pta.user_id as userId,
    u.site_id as siteId,
    NULL as gmiId,
    DATE(pt.ppm_task_time_assigned) as task_date,
    COUNT(*) as ppmTotal,
    SUM(CASE WHEN pt.ppm_task_status = 16 THEN 1 ELSE 0 END) as ppmCompleted,
    SUM(CASE WHEN pt.ppm_task_status = 16 AND pt.ppm_task_time_verified <= pt.ppm_task_schedule_date THEN 1 ELSE 0 END) as ppmOnTime,
    SUM(CASE WHEN pt.ppm_task_status = 16 AND pt.ppm_task_time_verified > pt.ppm_task_schedule_date THEN 1 ELSE 0 END) as ppmLate,
    SUM(CASE WHEN pt.ppm_task_status = 16 AND pt.ppm_task_time_verified <= DATE_ADD(pt.ppm_task_schedule_date, INTERVAL 1 DAY) THEN 1 ELSE 0 END) as ppmWithin
FROM ppm_task_assist pta
JOIN ppm_task pt ON pta.ppm_task_id = pt.ppm_task_id
JOIN sys_user u ON pta.user_id = u.user_id
WHERE pt.ppm_task_time_assigned IS NOT NULL
AND pta.user_id IS NOT NULL
GROUP BY pta.user_id, u.site_id, DATE(pt.ppm_task_time_assigned);

-- WO Daily View
CREATE OR REPLACE VIEW `vw_gamification_wo_daily` AS
SELECT 
    wt.wo_task_assigned_to as woTaskAssignedTo,
    wt.site_id as siteId,
    NULL as gmiId,
    DATE(wt.wo_task_time_assigned) as task_date,
    COUNT(*) as woTotal,
    SUM(CASE WHEN wt.wo_task_status = 16 THEN 1 ELSE 0 END) as woCompleted,
    SUM(CASE WHEN wt.wo_task_status = 16 AND wt.wo_task_time_verified <= DATE_ADD(wt.wo_task_time_assigned, INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as woOnTime,
    SUM(CASE WHEN wt.wo_task_status = 16 AND wt.wo_task_time_verified > DATE_ADD(wt.wo_task_time_assigned, INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as woLate,
    SUM(CASE WHEN wt.wo_task_created_by = wt.wo_task_assigned_to THEN 1 ELSE 0 END) as woSelfFinding
FROM wo_task wt
WHERE wt.wo_task_time_assigned IS NOT NULL
AND wt.wo_task_assigned_to IS NOT NULL
GROUP BY wt.wo_task_assigned_to, wt.site_id, DATE(wt.wo_task_time_assigned);

-- WO Assist Daily View
CREATE OR REPLACE VIEW `vw_gamification_wo_assist_daily` AS
SELECT 
    wta.user_id as userId,
    wt.site_id as siteId,
    NULL as gmiId,
    DATE(wt.wo_task_time_assigned) as task_date,
    COUNT(*) as woTotal,
    SUM(CASE WHEN wt.wo_task_status = 16 THEN 1 ELSE 0 END) as woCompleted,
    SUM(CASE WHEN wt.wo_task_status = 16 AND wt.wo_task_time_verified <= DATE_ADD(wt.wo_task_time_assigned, INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as woOnTime,
    SUM(CASE WHEN wt.wo_task_status = 16 AND wt.wo_task_time_verified > DATE_ADD(wt.wo_task_time_assigned, INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as woLate
FROM wo_task_assist wta
JOIN wo_task wt ON wta.wo_task_id = wt.wo_task_id
WHERE wt.wo_task_time_assigned IS NOT NULL
AND wta.user_id IS NOT NULL
GROUP BY wta.user_id, wt.site_id, DATE(wt.wo_task_time_assigned);
