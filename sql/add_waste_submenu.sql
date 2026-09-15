-- Waste Management navigation
-- Parent: Waste Management
-- Submenus: Waste Dashboard, Waste Record (list first)
-- Other waste pages stay reachable by URL but are hidden from the menu.

SET @PARENT_NAV_ID := 22;
SELECT nav_id INTO @PARENT_NAV_ID FROM sys_nav WHERE nav_desc = 'Waste Management' LIMIT 1;
SET @PARENT_NAV_ID := IFNULL(@PARENT_NAV_ID, 22);

UPDATE sys_nav
SET nav_desc = 'Waste Management',
    nav_page = 'p_waste_dashboard',
    nav_icon = 'dumpster',
    nav_status = 1
WHERE nav_id = @PARENT_NAV_ID;

-- Visible items
UPDATE sys_nav_second
SET nav_second_desc = 'Waste Dashboard',
    nav_second_page = 'p_waste_dashboard',
    nav_second_status = 1
WHERE nav_id = @PARENT_NAV_ID AND nav_second_id = 50;

UPDATE sys_nav_second
SET nav_second_desc = 'Waste Record',
    nav_second_page = 'p_waste_records',
    nav_second_status = 1
WHERE nav_id = @PARENT_NAV_ID AND nav_second_id = 51;

INSERT INTO sys_nav_second (nav_id, nav_second_desc, nav_second_page, nav_second_status)
SELECT @PARENT_NAV_ID, 'Waste Dashboard', 'p_waste_dashboard', 1
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_second WHERE nav_id = @PARENT_NAV_ID AND nav_second_page = 'p_waste_dashboard'
);

INSERT INTO sys_nav_second (nav_id, nav_second_desc, nav_second_page, nav_second_status)
SELECT @PARENT_NAV_ID, 'Waste Record', 'p_waste_records', 1
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_second WHERE nav_id = @PARENT_NAV_ID AND nav_second_page = 'p_waste_records' AND nav_second_status = 1
);

-- Hide leftover / extra waste pages from the sidebar
UPDATE sys_nav_second
SET nav_second_status = 0
WHERE nav_id = @PARENT_NAV_ID
  AND nav_second_page IN (
    'p_waste_record_form',
    'p_waste_opening_balance',
    'p_waste_report',
    'p_waste_setup',
    'p_waste_main',
    'p_waste_type'
  );

-- Hide the old duplicate list item (id 72) once 51 is the visible Waste Record list
UPDATE sys_nav_second
SET nav_second_status = 0
WHERE nav_id = @PARENT_NAV_ID
  AND nav_second_page = 'p_waste_records'
  AND nav_second_id <> 51;

SELECT nav_second_id INTO @SID_DASH
FROM sys_nav_second
WHERE nav_id = @PARENT_NAV_ID AND nav_second_page = 'p_waste_dashboard' AND nav_second_status = 1
LIMIT 1;

SELECT nav_second_id INTO @SID_LIST
FROM sys_nav_second
WHERE nav_id = @PARENT_NAV_ID AND nav_second_page = 'p_waste_records' AND nav_second_status = 1
LIMIT 1;

-- Administrator only
DELETE FROM sys_nav_role
WHERE nav_id = @PARENT_NAV_ID
  AND role_id <> 1;

-- Drop grants for hidden submenus
DELETE FROM sys_nav_role
WHERE nav_id = @PARENT_NAV_ID
  AND nav_second_id IS NOT NULL
  AND nav_second_id NOT IN (@SID_DASH, @SID_LIST);

-- Login builds submenus from consecutive nav_role_turn rows: parent first, then children.
SET @TURN_PARENT := 125;
SET @TURN_DASH := 126;
SET @TURN_LIST := 127;

INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT 1, @PARENT_NAV_ID, NULL, @TURN_PARENT
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_role
  WHERE role_id = 1 AND nav_id = @PARENT_NAV_ID AND nav_second_id IS NULL
);

INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT 1, @PARENT_NAV_ID, @SID_DASH, @TURN_DASH
FROM DUAL
WHERE @SID_DASH IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM sys_nav_role
    WHERE role_id = 1 AND nav_id = @PARENT_NAV_ID AND nav_second_id = @SID_DASH
  );

INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT 1, @PARENT_NAV_ID, @SID_LIST, @TURN_LIST
FROM DUAL
WHERE @SID_LIST IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM sys_nav_role
    WHERE role_id = 1 AND nav_id = @PARENT_NAV_ID AND nav_second_id = @SID_LIST
  );

UPDATE sys_nav_role SET nav_role_turn = @TURN_PARENT
WHERE role_id = 1 AND nav_id = @PARENT_NAV_ID AND nav_second_id IS NULL;
UPDATE sys_nav_role SET nav_role_turn = @TURN_DASH
WHERE role_id = 1 AND nav_id = @PARENT_NAV_ID AND nav_second_id = @SID_DASH;
UPDATE sys_nav_role SET nav_role_turn = @TURN_LIST
WHERE role_id = 1 AND nav_id = @PARENT_NAV_ID AND nav_second_id = @SID_LIST;
