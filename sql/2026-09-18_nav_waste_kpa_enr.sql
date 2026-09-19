-- Navigation for: new Waste lifecycle pages, KPI & APD module, Energy Monitoring module.
--
-- vw_menu (api/library/sql.php) resolves the sidebar with
--     SELECT nav_id, nav_second_id, MAX(nav_role_turn) ... WHERE role_id IN (...)
--     GROUP BY nav_id, nav_second_id ORDER BY turn
-- so the ordering is shared by every role a user holds. Each menu item therefore
-- gets ONE fixed turn value across all roles; otherwise a user with two roles
-- could see a child ordered before its parent. Existing grants are updated to
-- the fixed value rather than left at whatever an earlier script used.
--
-- Turn map (chosen against the live gems database on 2026-09-18)
--   125-129  Waste Management  (parent, Dashboard, Generation, Pending, Record)
--   155-160  KPI & APD         (parent, Summary, Evaluation, History, Structure, Assignment)
--   161-164  Energy Monitoring (parent, Daily, Monthly, BEI)
--
-- Do not reuse 140 / 142 / 150: those turns already belong to Leaderboard
-- Dashboard and Attendance > Site Admin for Administrator.
-- Reuse the existing sys_nav row "KPI / APD" (nav_id 16) instead of inserting
-- a second KPI parent.
--
-- Idempotent: safe to run more than once.

SET NAMES utf8mb4;

-- ===========================================================================
-- 1. Waste Management: add Waste Generation + Pending Disposal
-- ===========================================================================
SET @NAV_WASTE := (SELECT nav_id FROM sys_nav WHERE nav_desc = 'Waste Management' LIMIT 1);

INSERT INTO sys_nav_second (nav_id, nav_second_desc, nav_second_page, nav_second_status)
SELECT @NAV_WASTE, v.d, v.p, 1
FROM (
  SELECT 'Waste Generation' AS d, 'p_waste_generation' AS p
  UNION ALL SELECT 'Pending Disposal', 'p_waste_pending'
) v
WHERE @NAV_WASTE IS NOT NULL AND NOT EXISTS (
  SELECT 1 FROM sys_nav_second s WHERE s.nav_id = @NAV_WASTE AND s.nav_second_page = v.p
);

UPDATE sys_nav_second SET nav_second_status = 1
WHERE nav_id = @NAV_WASTE AND nav_second_page IN ('p_waste_generation', 'p_waste_pending');

-- p_waste_dispose is reached from the pending list only.
UPDATE sys_nav_second SET nav_second_status = 0
WHERE nav_id = @NAV_WASTE AND nav_second_page = 'p_waste_dispose';

-- ===========================================================================
-- 2. KPI & APD
-- ===========================================================================
SET @NAV_KPA := (
  SELECT nav_id FROM sys_nav
  WHERE nav_desc IN ('KPI & APD', 'KPI / APD')
  ORDER BY CASE WHEN nav_desc = 'KPI & APD' THEN 0 ELSE 1 END
  LIMIT 1
);

INSERT INTO sys_nav (nav_desc, nav_page, nav_icon, nav_status)
SELECT 'KPI & APD', 'p_kpi_in', 'gauge-high', 1
WHERE @NAV_KPA IS NULL;

SET @NAV_KPA := (
  SELECT nav_id FROM sys_nav
  WHERE nav_desc IN ('KPI & APD', 'KPI / APD')
  ORDER BY CASE WHEN nav_desc = 'KPI & APD' THEN 0 ELSE 1 END
  LIMIT 1
);
UPDATE sys_nav
SET nav_desc = 'KPI & APD', nav_page = 'p_kpi_in', nav_icon = 'gauge-high', nav_status = 1
WHERE nav_id = @NAV_KPA;

INSERT INTO sys_nav_second (nav_id, nav_second_desc, nav_second_page, nav_second_status)
SELECT @NAV_KPA, v.d, v.p, 1
FROM (
  SELECT 'KPI / APD Summary' AS d, 'p_kpi_in' AS p
  UNION ALL SELECT 'Monthly Evaluation', 'p_kpa_evaluation'
  UNION ALL SELECT 'KPI History', 'p_kpa_history'
  UNION ALL SELECT 'KPI Structure', 'p_kpa_structure'
  UNION ALL SELECT 'PI Assignment', 'p_kpa_assignment'
) v
WHERE @NAV_KPA IS NOT NULL AND NOT EXISTS (
  SELECT 1 FROM sys_nav_second s WHERE s.nav_id = @NAV_KPA AND s.nav_second_page = v.p
);

-- ===========================================================================
-- 3. Energy Monitoring
-- ===========================================================================
INSERT INTO sys_nav (nav_desc, nav_page, nav_icon, nav_status)
SELECT 'Energy Monitoring', 'p_energy_daily', 'bolt', 1
WHERE NOT EXISTS (SELECT 1 FROM sys_nav WHERE nav_desc = 'Energy Monitoring');

SET @NAV_ENR := (SELECT nav_id FROM sys_nav WHERE nav_desc = 'Energy Monitoring' LIMIT 1);
UPDATE sys_nav SET nav_page = 'p_energy_daily', nav_icon = 'bolt', nav_status = 1 WHERE nav_id = @NAV_ENR;

INSERT INTO sys_nav_second (nav_id, nav_second_desc, nav_second_page, nav_second_status)
SELECT @NAV_ENR, v.d, v.p, 1
FROM (
  SELECT 'Daily Electricity' AS d, 'p_energy_daily' AS p
  UNION ALL SELECT 'Monthly Summary', 'p_energy_monthly'
  UNION ALL SELECT 'Building Energy Index', 'p_energy_bei'
) v
WHERE @NAV_ENR IS NOT NULL AND NOT EXISTS (
  SELECT 1 FROM sys_nav_second s WHERE s.nav_id = @NAV_ENR AND s.nav_second_page = v.p
);

-- ===========================================================================
-- 4. Grants
-- ===========================================================================
-- Every (role, menu item) pair to grant, with the item's fixed turn.
DROP TEMPORARY TABLE IF EXISTS tmp_nav_grant;
CREATE TEMPORARY TABLE tmp_nav_grant (
  role_id tinyint NOT NULL,
  nav_id smallint NOT NULL,
  nav_second_id smallint NULL,
  nav_role_turn smallint NOT NULL
) ENGINE=InnoDB;

-- Waste Management: Administrator (1), Waste User (28), Waste Officer (29)
INSERT INTO tmp_nav_grant (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT r.rid, @NAV_WASTE, n.sid, n.turn
FROM (SELECT 1 AS rid UNION ALL SELECT 28 UNION ALL SELECT 29) r
CROSS JOIN (
  SELECT NULL AS sid, 125 AS turn
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_WASTE AND nav_second_page = 'p_waste_dashboard' AND nav_second_status = 1 LIMIT 1), 126
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_WASTE AND nav_second_page = 'p_waste_generation' AND nav_second_status = 1 LIMIT 1), 127
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_WASTE AND nav_second_page = 'p_waste_pending' AND nav_second_status = 1 LIMIT 1), 128
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_WASTE AND nav_second_page = 'p_waste_records' AND nav_second_status = 1 LIMIT 1), 129
) n
WHERE @NAV_WASTE IS NOT NULL AND (n.sid IS NOT NULL OR n.turn = 125)
  AND EXISTS (SELECT 1 FROM ref_role x WHERE x.role_id = r.rid);

-- KPI & APD: Administrator (1) and KPI Admin (30) see everything
INSERT INTO tmp_nav_grant (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT r.rid, @NAV_KPA, n.sid, n.turn
FROM (SELECT 1 AS rid UNION ALL SELECT 30) r
CROSS JOIN (
  SELECT NULL AS sid, 155 AS turn
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_KPA AND nav_second_page = 'p_kpi_in' LIMIT 1), 156
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_KPA AND nav_second_page = 'p_kpa_evaluation' LIMIT 1), 157
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_KPA AND nav_second_page = 'p_kpa_history' LIMIT 1), 158
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_KPA AND nav_second_page = 'p_kpa_structure' LIMIT 1), 159
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_KPA AND nav_second_page = 'p_kpa_assignment' LIMIT 1), 160
) n
WHERE @NAV_KPA IS NOT NULL AND (n.sid IS NOT NULL OR n.turn = 155)
  AND EXISTS (SELECT 1 FROM ref_role x WHERE x.role_id = r.rid);

-- PI Entry (31): evaluation, summary and history
INSERT INTO tmp_nav_grant (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT 31, @NAV_KPA, n.sid, n.turn
FROM (
  SELECT NULL AS sid, 155 AS turn
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_KPA AND nav_second_page = 'p_kpi_in' LIMIT 1), 156
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_KPA AND nav_second_page = 'p_kpa_evaluation' LIMIT 1), 157
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_KPA AND nav_second_page = 'p_kpa_history' LIMIT 1), 158
) n
WHERE @NAV_KPA IS NOT NULL AND (n.sid IS NOT NULL OR n.turn = 155)
  AND EXISTS (SELECT 1 FROM ref_role x WHERE x.role_id = 31);

-- KPI Viewer (32): summary and history
INSERT INTO tmp_nav_grant (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT 32, @NAV_KPA, n.sid, n.turn
FROM (
  SELECT NULL AS sid, 155 AS turn
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_KPA AND nav_second_page = 'p_kpi_in' LIMIT 1), 156
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_KPA AND nav_second_page = 'p_kpa_history' LIMIT 1), 158
) n
WHERE @NAV_KPA IS NOT NULL AND (n.sid IS NOT NULL OR n.turn = 155)
  AND EXISTS (SELECT 1 FROM ref_role x WHERE x.role_id = 32);

-- Energy Monitoring: Administrator (1), Utility Reader (18), KPI Admin (30)
INSERT INTO tmp_nav_grant (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT r.rid, @NAV_ENR, n.sid, n.turn
FROM (SELECT 1 AS rid UNION ALL SELECT 18 UNION ALL SELECT 30) r
CROSS JOIN (
  SELECT NULL AS sid, 161 AS turn
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_ENR AND nav_second_page = 'p_energy_daily' LIMIT 1), 162
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_ENR AND nav_second_page = 'p_energy_monthly' LIMIT 1), 163
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_ENR AND nav_second_page = 'p_energy_bei' LIMIT 1), 164
) n
WHERE @NAV_ENR IS NOT NULL AND (n.sid IS NOT NULL OR n.turn = 161)
  AND EXISTS (SELECT 1 FROM ref_role x WHERE x.role_id = r.rid);

-- Insert the grants that are missing.
INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT g.role_id, g.nav_id, g.nav_second_id, g.nav_role_turn
FROM tmp_nav_grant g
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_role e
  WHERE e.role_id = g.role_id AND e.nav_id = g.nav_id
    AND ((e.nav_second_id IS NULL AND g.nav_second_id IS NULL) OR e.nav_second_id = g.nav_second_id)
);

-- Realign the turn of grants that already existed (an earlier script may have
-- used a different value, which would break the parent-then-children order).
UPDATE sys_nav_role e
INNER JOIN tmp_nav_grant g
  ON g.role_id = e.role_id AND g.nav_id = e.nav_id
 AND ((e.nav_second_id IS NULL AND g.nav_second_id IS NULL) OR e.nav_second_id = g.nav_second_id)
SET e.nav_role_turn = g.nav_role_turn;

DROP TEMPORARY TABLE IF EXISTS tmp_nav_grant;

UPDATE `sys_version` SET `version_no` = `version_no` + 1 WHERE `version_id` = 2;
