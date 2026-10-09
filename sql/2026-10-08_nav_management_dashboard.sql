-- Management Dashboard landing page.
--
-- Web login opens userInfo.menu[0].navPage, and vw_menu orders each item by
-- MAX(sys_nav_role.nav_role_turn). Home is turn 1 for the existing roles, so a
-- new item at turn 0 becomes the first page for the roles that receive it.
--
-- Grants are limited to Administrator (1) and GFM Management (10). Every other
-- role keeps Home as its first page. Flutter does not read this menu.
--
-- This does not revive the dormant finance "Management Dashboard" row. The new
-- item is identified only by nav_page = management_dashboard.html.
--
-- Idempotent: a second run inserts nothing, realigns nothing, and does not
-- bump sys_version again.

SET NAMES utf8mb4;

SET @NAV_BEFORE := (
  SELECT nav_id
  FROM sys_nav
  WHERE nav_page = 'management_dashboard.html'
  LIMIT 1
);

INSERT INTO sys_nav (nav_desc, nav_page, nav_icon, nav_status)
SELECT 'Management Dashboard', 'management_dashboard.html', 'chart-pie', 1
WHERE @NAV_BEFORE IS NULL;

UPDATE sys_nav
SET nav_desc = 'Management Dashboard',
    nav_icon = 'chart-pie',
    nav_status = 1
WHERE nav_page = 'management_dashboard.html'
  AND (
    nav_desc <> 'Management Dashboard'
    OR nav_icon <> 'chart-pie'
    OR nav_status <> 1
  );

SET @NAV_ROW_CHANGED := ROW_COUNT();

SET @NAV_MGMT := (
  SELECT nav_id
  FROM sys_nav
  WHERE nav_page = 'management_dashboard.html'
  LIMIT 1
);

DROP TEMPORARY TABLE IF EXISTS tmp_mgmt_dash_grant;
CREATE TEMPORARY TABLE tmp_mgmt_dash_grant (
  role_id tinyint NOT NULL,
  nav_id smallint NOT NULL,
  nav_second_id smallint NULL,
  nav_role_turn smallint NOT NULL
) ENGINE=InnoDB;

INSERT INTO tmp_mgmt_dash_grant (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT role.role_id, @NAV_MGMT, NULL, 0
FROM ref_role role
WHERE role.role_id IN (1, 10)
  AND role.role_status = 1
  AND @NAV_MGMT IS NOT NULL;

SET @MGMT_NAV_CHANGED := (
  SELECT IF(COUNT(*) > 0 OR @NAV_BEFORE IS NULL OR @NAV_ROW_CHANGED > 0, 1, 0)
  FROM tmp_mgmt_dash_grant desired
  LEFT JOIN sys_nav_role existing
    ON existing.role_id = desired.role_id
   AND existing.nav_id = desired.nav_id
   AND existing.nav_second_id <=> desired.nav_second_id
  WHERE existing.nav_role_id IS NULL
     OR existing.nav_role_turn <> desired.nav_role_turn
);

INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT desired.role_id, desired.nav_id, desired.nav_second_id, desired.nav_role_turn
FROM tmp_mgmt_dash_grant desired
WHERE NOT EXISTS (
  SELECT 1
  FROM sys_nav_role existing
  WHERE existing.role_id = desired.role_id
    AND existing.nav_id = desired.nav_id
    AND existing.nav_second_id <=> desired.nav_second_id
);

UPDATE sys_nav_role existing
INNER JOIN tmp_mgmt_dash_grant desired
  ON existing.role_id = desired.role_id
 AND existing.nav_id = desired.nav_id
 AND existing.nav_second_id <=> desired.nav_second_id
SET existing.nav_role_turn = desired.nav_role_turn
WHERE existing.nav_role_turn <> desired.nav_role_turn;

UPDATE sys_version
SET version_no = version_no + @MGMT_NAV_CHANGED
WHERE version_id = 2;

DROP TEMPORARY TABLE IF EXISTS tmp_mgmt_dash_grant;
