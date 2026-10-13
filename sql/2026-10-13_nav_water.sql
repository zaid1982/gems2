-- Water pages under the electricity menu, renamed Utility Monitoring.
-- Grants match the energy set: Administrator (1), Utility Reader (18), KPI Admin (30).
-- Idempotent.

SET NAMES utf8mb4;

-- A later script splits this into Utilities - Electrical and Utilities - Water.
-- Do not recreate the mixed parent once either split name exists.
UPDATE sys_nav SET nav_desc = 'Utility Monitoring'
WHERE nav_desc = 'Energy Monitoring'
  AND NOT EXISTS (SELECT 1 FROM (SELECT nav_id FROM sys_nav WHERE nav_desc IN ('Utilities - Electrical', 'Utilities - Water')) already);

INSERT INTO sys_nav (nav_desc, nav_page, nav_icon, nav_status)
SELECT 'Utility Monitoring', 'p_energy_daily', 'bolt', 1
WHERE NOT EXISTS (SELECT 1 FROM sys_nav WHERE nav_desc = 'Utility Monitoring')
  AND NOT EXISTS (SELECT 1 FROM sys_nav WHERE nav_desc = 'Utilities - Electrical')
  AND NOT EXISTS (SELECT 1 FROM sys_nav WHERE nav_desc = 'Utilities - Water');

SET @NAV_UTL := (SELECT nav_id FROM sys_nav WHERE nav_desc = 'Utility Monitoring' LIMIT 1);
UPDATE sys_nav
SET nav_page = 'p_energy_daily', nav_icon = 'bolt', nav_status = 1
WHERE nav_id = @NAV_UTL;

INSERT INTO sys_nav_second (nav_id, nav_second_desc, nav_second_page, nav_second_status)
SELECT @NAV_UTL, v.d, v.p, 1
FROM (
  SELECT 'Daily Water' AS d, 'p_water_daily' AS p
  UNION ALL SELECT 'Water Summary', 'p_water_monthly'
  UNION ALL SELECT 'Water Tariff', 'p_water_tariff'
) v
WHERE @NAV_UTL IS NOT NULL AND NOT EXISTS (
  SELECT 1 FROM sys_nav_second s WHERE s.nav_id = @NAV_UTL AND s.nav_second_page = v.p
);

DROP TEMPORARY TABLE IF EXISTS tmp_nav_grant;
CREATE TEMPORARY TABLE tmp_nav_grant (
  role_id tinyint NOT NULL,
  nav_id smallint NOT NULL,
  nav_second_id smallint NULL,
  nav_role_turn smallint NOT NULL
) ENGINE=InnoDB;

-- Same roles as the shipped energy grants. Turns sit after Building Energy Index (164).
INSERT INTO tmp_nav_grant (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT r.rid, @NAV_UTL, n.sid, n.turn
FROM (SELECT 1 AS rid UNION ALL SELECT 18 UNION ALL SELECT 30) r
CROSS JOIN (
  SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_UTL AND nav_second_page = 'p_water_daily' LIMIT 1) AS sid, 165 AS turn
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_UTL AND nav_second_page = 'p_water_monthly' LIMIT 1), 166
  UNION ALL SELECT (SELECT nav_second_id FROM sys_nav_second WHERE nav_id = @NAV_UTL AND nav_second_page = 'p_water_tariff' LIMIT 1), 167
) n
WHERE @NAV_UTL IS NOT NULL AND n.sid IS NOT NULL
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
