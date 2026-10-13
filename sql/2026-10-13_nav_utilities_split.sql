-- Split the mixed Utility Monitoring parent into two sidebar menus.
-- GEMS has no third menu level, so electricity and water each need a parent.
-- Idempotent.

SET NAMES utf8mb4;

-- Electrical keeps the existing parent row, the bolt icon, and its three pages.
UPDATE sys_nav
SET nav_desc = 'Utilities - Electrical',
    nav_page = 'p_energy_daily',
    nav_icon = 'bolt',
    nav_status = 1
WHERE nav_desc IN ('Energy Monitoring', 'Utility Monitoring', 'Utilities - Electrical');

INSERT INTO sys_nav (nav_desc, nav_page, nav_icon, nav_status)
SELECT 'Utilities - Electrical', 'p_energy_daily', 'bolt', 1
WHERE NOT EXISTS (SELECT 1 FROM sys_nav WHERE nav_desc = 'Utilities - Electrical');

SET @NAV_ELEC := (SELECT nav_id FROM sys_nav WHERE nav_desc = 'Utilities - Electrical' LIMIT 1);

INSERT INTO sys_nav (nav_desc, nav_page, nav_icon, nav_status)
SELECT 'Utilities - Water', 'p_water_daily', 'droplet', 1
WHERE NOT EXISTS (SELECT 1 FROM sys_nav WHERE nav_desc = 'Utilities - Water');

SET @NAV_WATER := (SELECT nav_id FROM sys_nav WHERE nav_desc = 'Utilities - Water' LIMIT 1);
UPDATE sys_nav
SET nav_page = 'p_water_daily', nav_icon = 'droplet', nav_status = 1
WHERE nav_id = @NAV_WATER;

-- Move water pages off the electrical parent. Insert them if they were never created.
UPDATE sys_nav_second
SET nav_id = @NAV_WATER
WHERE @NAV_WATER IS NOT NULL
  AND nav_second_page IN ('p_water_daily', 'p_water_monthly', 'p_water_tariff');

INSERT INTO sys_nav_second (nav_id, nav_second_desc, nav_second_page, nav_second_status)
SELECT @NAV_WATER, v.d, v.p, 1
FROM (
  SELECT 'Daily Water' AS d, 'p_water_daily' AS p
  UNION ALL SELECT 'Water Summary', 'p_water_monthly'
  UNION ALL SELECT 'Water Tariff', 'p_water_tariff'
) v
WHERE @NAV_WATER IS NOT NULL AND NOT EXISTS (
  SELECT 1 FROM sys_nav_second s WHERE s.nav_second_page = v.p
);

-- Point the existing water grants at the water parent, and order the parent first.
UPDATE sys_nav_role e
INNER JOIN sys_nav_second s ON s.nav_second_id = e.nav_second_id
SET e.nav_id = @NAV_WATER,
    e.nav_role_turn = CASE s.nav_second_page
        WHEN 'p_water_daily' THEN 166
        WHEN 'p_water_monthly' THEN 167
        WHEN 'p_water_tariff' THEN 168
        ELSE e.nav_role_turn
    END
WHERE @NAV_WATER IS NOT NULL
  AND s.nav_second_page IN ('p_water_daily', 'p_water_monthly', 'p_water_tariff');

DROP TEMPORARY TABLE IF EXISTS tmp_nav_grant;
CREATE TEMPORARY TABLE tmp_nav_grant (
  role_id tinyint NOT NULL,
  nav_id smallint NOT NULL,
  nav_second_id smallint NULL,
  nav_role_turn smallint NOT NULL
) ENGINE=InnoDB;

-- Same roles as electricity: Administrator (1), Utility Reader (18), KPI Admin (30).
INSERT INTO tmp_nav_grant (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT r.rid, @NAV_WATER, NULL, 165
FROM (SELECT 1 AS rid UNION ALL SELECT 18 UNION ALL SELECT 30) r
WHERE @NAV_WATER IS NOT NULL
  AND EXISTS (SELECT 1 FROM ref_role x WHERE x.role_id = r.rid);

INSERT INTO tmp_nav_grant (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT r.rid, @NAV_WATER, n.sid, n.turn
FROM (SELECT 1 AS rid UNION ALL SELECT 18 UNION ALL SELECT 30) r
CROSS JOIN (
  SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_second_page = 'p_water_daily' LIMIT 1) AS sid, 166 AS turn
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_second_page = 'p_water_monthly' LIMIT 1), 167
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_second_page = 'p_water_tariff' LIMIT 1), 168
) n
WHERE @NAV_WATER IS NOT NULL AND n.sid IS NOT NULL
  AND EXISTS (SELECT 1 FROM ref_role x WHERE x.role_id = r.rid);

INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT g.role_id, g.nav_id, g.nav_second_id, g.nav_role_turn
FROM tmp_nav_grant g
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_role e
  WHERE e.role_id = g.role_id AND e.nav_id = g.nav_id
    AND ((e.nav_second_id IS NULL AND g.nav_second_id IS NULL) OR e.nav_second_id = g.nav_second_id)
);

UPDATE sys_nav_role e
INNER JOIN tmp_nav_grant g
  ON g.role_id = e.role_id AND g.nav_id = e.nav_id
 AND ((e.nav_second_id IS NULL AND g.nav_second_id IS NULL) OR e.nav_second_id = g.nav_second_id)
SET e.nav_role_turn = g.nav_role_turn;

DROP TEMPORARY TABLE IF EXISTS tmp_nav_grant;

UPDATE `sys_version` SET `version_no` = `version_no` + 1 WHERE `version_id` = 2;
