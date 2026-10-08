-- JKR legacy Waste Management navigation grants.
--
-- JKR has the Waste User (28) and Waste Officer (29) roles, but older
-- databases do not map those roles to the Waste Management sidebar. Assigning
-- either role therefore grants API capability without showing any menu.
--
-- This script is intentionally limited to the legacy JKR waste pages. Do not
-- replace it with add_waste_submenu.sql (which removes non-admin grants) or
-- 2026-09-18_nav_waste_kpa_enr.sql (which also installs lifecycle/KPI/Energy
-- navigation that a legacy JKR database may not support).
--
-- Idempotent: existing grants are retained and their order is realigned.

SET NAMES utf8mb4;

SET @NAV_WASTE := (
  SELECT nav_id
  FROM sys_nav
  WHERE nav_desc = 'Waste Management'
  LIMIT 1
);

DROP TEMPORARY TABLE IF EXISTS tmp_jkr_waste_nav_grant;
CREATE TEMPORARY TABLE tmp_jkr_waste_nav_grant (
  role_id tinyint NOT NULL,
  nav_id smallint NOT NULL,
  nav_second_id smallint NULL,
  nav_role_turn smallint NOT NULL
) ENGINE=InnoDB;

-- Waste User: dashboard, record entry and records list.
INSERT INTO tmp_jkr_waste_nav_grant (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT 28, @NAV_WASTE, item.nav_second_id, item.nav_role_turn
FROM (
  SELECT NULL AS nav_second_id, 125 AS nav_role_turn
  UNION ALL
  SELECT (
    SELECT nav_second_id
    FROM sys_nav_second
    WHERE nav_id = @NAV_WASTE
      AND nav_second_page = 'p_waste_dashboard'
      AND nav_second_status = 1
    LIMIT 1
  ), 126
  UNION ALL
  SELECT (
    SELECT nav_second_id
    FROM sys_nav_second
    WHERE nav_id = @NAV_WASTE
      AND nav_second_page = 'p_waste_record_form'
      AND nav_second_status = 1
    LIMIT 1
  ), 127
  UNION ALL
  SELECT (
    SELECT nav_second_id
    FROM sys_nav_second
    WHERE nav_id = @NAV_WASTE
      AND nav_second_page = 'p_waste_records'
      AND nav_second_status = 1
    LIMIT 1
  ), 128
) item
WHERE @NAV_WASTE IS NOT NULL
  AND EXISTS (SELECT 1 FROM ref_role WHERE role_id = 28 AND role_status = 1)
  AND (item.nav_second_id IS NOT NULL OR item.nav_role_turn = 125);

-- Waste Officer: Waste User pages plus opening balance and JKR reports.
INSERT INTO tmp_jkr_waste_nav_grant (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT 29, @NAV_WASTE, item.nav_second_id, item.nav_role_turn
FROM (
  SELECT NULL AS nav_second_id, 125 AS nav_role_turn
  UNION ALL
  SELECT (
    SELECT nav_second_id
    FROM sys_nav_second
    WHERE nav_id = @NAV_WASTE
      AND nav_second_page = 'p_waste_dashboard'
      AND nav_second_status = 1
    LIMIT 1
  ), 126
  UNION ALL
  SELECT (
    SELECT nav_second_id
    FROM sys_nav_second
    WHERE nav_id = @NAV_WASTE
      AND nav_second_page = 'p_waste_record_form'
      AND nav_second_status = 1
    LIMIT 1
  ), 127
  UNION ALL
  SELECT (
    SELECT nav_second_id
    FROM sys_nav_second
    WHERE nav_id = @NAV_WASTE
      AND nav_second_page = 'p_waste_records'
      AND nav_second_status = 1
    LIMIT 1
  ), 128
  UNION ALL
  SELECT (
    SELECT nav_second_id
    FROM sys_nav_second
    WHERE nav_id = @NAV_WASTE
      AND nav_second_page = 'p_waste_opening_balance'
      AND nav_second_status = 1
    LIMIT 1
  ), 129
  UNION ALL
  SELECT (
    SELECT nav_second_id
    FROM sys_nav_second
    WHERE nav_id = @NAV_WASTE
      AND nav_second_page = 'p_waste_report'
      AND nav_second_status = 1
    LIMIT 1
  ), 130
) item
WHERE @NAV_WASTE IS NOT NULL
  AND EXISTS (SELECT 1 FROM ref_role WHERE role_id = 29 AND role_status = 1)
  AND (item.nav_second_id IS NOT NULL OR item.nav_role_turn = 125);

SET @WASTE_NAV_CHANGED := (
  SELECT IF(COUNT(*) > 0, 1, 0)
  FROM tmp_jkr_waste_nav_grant desired
  LEFT JOIN sys_nav_role existing
    ON existing.role_id = desired.role_id
   AND existing.nav_id = desired.nav_id
   AND existing.nav_second_id <=> desired.nav_second_id
  WHERE existing.nav_role_id IS NULL
     OR existing.nav_role_turn <> desired.nav_role_turn
);

INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT desired.role_id, desired.nav_id, desired.nav_second_id, desired.nav_role_turn
FROM tmp_jkr_waste_nav_grant desired
WHERE NOT EXISTS (
  SELECT 1
  FROM sys_nav_role existing
  WHERE existing.role_id = desired.role_id
    AND existing.nav_id = desired.nav_id
    AND existing.nav_second_id <=> desired.nav_second_id
);

UPDATE sys_nav_role existing
INNER JOIN tmp_jkr_waste_nav_grant desired
  ON existing.role_id = desired.role_id
 AND existing.nav_id = desired.nav_id
 AND existing.nav_second_id <=> desired.nav_second_id
SET existing.nav_role_turn = desired.nav_role_turn
WHERE existing.nav_role_turn <> desired.nav_role_turn;

UPDATE sys_version
SET version_no = version_no + @WASTE_NAV_CHANGED
WHERE version_id = 2;

DROP TEMPORARY TABLE IF EXISTS tmp_jkr_waste_nav_grant;
