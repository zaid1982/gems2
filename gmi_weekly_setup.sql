-- SQL script to set up weekly gamification system for existing gmi_weekly table
-- This script creates the necessary views for weekly-based gamification calculations

-- Note: Your existing gmi_weekly table structure is already compatible!
-- Table: gmi_weekly with gmw_ prefixed columns and gmw_year/gmw_week (no month column needed)

-- Create daily views for PPM data (Updated for actual table structure)
-- Based on your actual tables: ppm_task, ppm_task_assist, wo_task, wo_task_assist
-- Status 16 = Completed in your system

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
WHERE pt.ppm_task_time_assigned >= '$dateStart' AND pt.ppm_task_time_assigned <= '$dateEnd'
AND pt.ppm_task_assigned_to IS NOT NULL
GROUP BY pt.ppm_task_assigned_to, u.site_id, DATE(pt.ppm_task_time_assigned);

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
WHERE pt.ppm_task_time_assigned >= '$dateStart' AND pt.ppm_task_time_assigned <= '$dateEnd'
AND pta.user_id IS NOT NULL
GROUP BY pta.user_id, u.site_id, DATE(pt.ppm_task_time_assigned);

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
WHERE wt.wo_task_time_assigned >= '$dateStart' AND wt.wo_task_time_assigned <= '$dateEnd'
AND wt.wo_task_assigned_to IS NOT NULL
GROUP BY wt.wo_task_assigned_to, wt.site_id, DATE(wt.wo_task_time_assigned);

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
WHERE wt.wo_task_time_assigned >= '$dateStart' AND wt.wo_task_time_assigned <= '$dateEnd'
AND wta.user_id IS NOT NULL
GROUP BY wta.user_id, wt.site_id, DATE(wt.wo_task_time_assigned);

-- Add new configuration for weekly calculations if needed
INSERT INTO gmi_config (config_key, config_value, data_type, description, last_updated_by, last_updated_at, status) VALUES
('weekly_calculation_enabled', '1', 'int', 'Enable weekly-based gamification calculations (1=enabled, 0=disabled)', 'system', NOW(), 1)
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), last_updated_at = NOW();

-- Optional: Create a view to get weekly summaries using your existing table structure
CREATE OR REPLACE VIEW `vw_gmi_weekly_summary` AS
SELECT 
    gw.*,
    u.user_name,
    s.site_name
FROM gmi_weekly gw
LEFT JOIN users u ON gw.user_id = u.user_id
LEFT JOIN site s ON gw.site_id = s.site_id
ORDER BY gw.gmw_year DESC, gw.gmw_week DESC, gw.gmw_point_total DESC;

-- Optional: Create a view to get monthly aggregated data from weekly calculations
CREATE OR REPLACE VIEW `vw_gmi_monthly_from_weekly` AS
SELECT 
    user_id,
    gmw_year,
    MONTH(CONCAT(gmw_year, '-01-01') + INTERVAL (gmw_week - 1) WEEK) as gmi_month,
    site_id,
    SUM(gmw_ppm_total) as gmi_ppm_total,
    SUM(gmw_ppm_completed) as gmi_ppm_completed,
    SUM(gmw_ppm_on_time) as gmi_ppm_on_time,
    SUM(gmw_ppm_late) as gmi_ppm_late,
    SUM(gmw_ppm_within) as gmi_ppm_within,
    SUM(gmw_ppm_assist) as gmi_ppm_assist,
    MAX(gmw_ppm_tier_point) as gmi_ppm_tier_point,
    MAX(gmw_ppm_tier_name) as gmi_ppm_tier_name,
    SUM(gmw_wo_total) as gmi_wo_total,
    SUM(gmw_wo_completed) as gmi_wo_completed,
    SUM(gmw_wo_on_time) as gmi_wo_on_time,
    SUM(gmw_wo_late) as gmi_wo_late,
    SUM(gmw_wo_self_finding) as gmi_wo_self_finding,
    SUM(gmw_wo_assist) as gmi_wo_assist,
    MAX(gmw_wo_tier_point) as gmi_wo_tier_point,
    MAX(gmw_wo_tier_name) as gmi_wo_tier_name,
    SUM(gmw_point_completed) as gmi_point_completed,
    SUM(gmw_point_on_time) as gmi_point_on_time,
    SUM(gmw_point_late) as gmi_point_late,
    SUM(gmw_point_self_finding) as gmi_point_self_finding,
    SUM(gmw_point_total) as gmi_point_total,
    SUM(gmw_mbv) as gmi_mbv,
    AVG(gmw_tier_point) as gmi_tier_point,
    AVG(gmw_productivity_level) as gmi_productivity_level,
    AVG(gmw_productivity_deduction) as gmi_productivity_deduction,
    SUM(gmw_point_less_productive) as gmi_point_less_productive,
    SUM(gmw_point_before_minus) as gmi_point_before_minus,
    SUM(gmw_point_after_minus) as gmi_point_after_minus
FROM gmi_weekly
GROUP BY user_id, gmw_year, MONTH(CONCAT(gmw_year, '-01-01') + INTERVAL (gmw_week - 1) WEEK), site_id
ORDER BY gmw_year DESC, gmi_month DESC, gmi_point_total DESC;
