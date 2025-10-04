-- Add Space module items into the left navigation
-- This script adds submenu entries under an existing main nav (defaults to "Asset Management")
-- and grants access to appropriate roles.
--
-- Tables used (consistent with vw_menu in api/library/sql.php):
--   - sys_nav (main menus)
--   - sys_nav_second (submenu items)
--   - sys_nav_role (role to menu mapping; orders by nav_role_turn)
--
-- Idempotent inserts: each INSERT only runs when the row does not already exist.

-- 1) Resolve parent nav_id for the Space module
-- Prefer an existing "Asset Management" main menu; fallback to explicit 7 when not found
SET @PARENT_NAV_DESC := 'Asset Management';
SELECT nav_id INTO @PARENT_NAV_ID FROM sys_nav WHERE nav_desc = @PARENT_NAV_DESC LIMIT 1;
SET @PARENT_NAV_ID := IFNULL(@PARENT_NAV_ID, 7);

-- 2) Create Space submenus (if not present) under the resolved parent nav
-- User-facing
INSERT INTO sys_nav_second (nav_id, nav_second_desc, nav_second_page, nav_second_status)
SELECT @PARENT_NAV_ID, 'Spaces', 'space.html', 1
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_second WHERE nav_id = @PARENT_NAV_ID AND nav_second_page = 'space.html'
);

INSERT INTO sys_nav_second (nav_id, nav_second_desc, nav_second_page, nav_second_status)
SELECT @PARENT_NAV_ID, 'My Reservations', 'my_reservations.html', 1
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_second WHERE nav_id = @PARENT_NAV_ID AND nav_second_page = 'my_reservations.html'
);

-- Admin references
INSERT INTO sys_nav_second (nav_id, nav_second_desc, nav_second_page, nav_second_status)
SELECT @PARENT_NAV_ID, 'Space Location', 'space_location.html', 1
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_second WHERE nav_id = @PARENT_NAV_ID AND nav_second_page = 'space_location.html'
);

INSERT INTO sys_nav_second (nav_id, nav_second_desc, nav_second_page, nav_second_status)
SELECT @PARENT_NAV_ID, 'Space Category', 'space_category.html', 1
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_second WHERE nav_id = @PARENT_NAV_ID AND nav_second_page = 'space_category.html'
);

INSERT INTO sys_nav_second (nav_id, nav_second_desc, nav_second_page, nav_second_status)
SELECT @PARENT_NAV_ID, 'Space Type', 'space_type.html', 1
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_second WHERE nav_id = @PARENT_NAV_ID AND nav_second_page = 'space_type.html'
);

-- 3) Capture the nav_second_id values to use for role mappings
SELECT nav_second_id INTO @SID_SPACES
FROM sys_nav_second WHERE nav_id = @PARENT_NAV_ID AND nav_second_page = 'space.html' LIMIT 1;

SELECT nav_second_id INTO @SID_MYRES
FROM sys_nav_second WHERE nav_id = @PARENT_NAV_ID AND nav_second_page = 'my_reservations.html' LIMIT 1;

SELECT nav_second_id INTO @SID_LOC
FROM sys_nav_second WHERE nav_id = @PARENT_NAV_ID AND nav_second_page = 'space_location.html' LIMIT 1;

SELECT nav_second_id INTO @SID_CAT
FROM sys_nav_second WHERE nav_id = @PARENT_NAV_ID AND nav_second_page = 'space_category.html' LIMIT 1;

SELECT nav_second_id INTO @SID_TYPE
FROM sys_nav_second WHERE nav_id = @PARENT_NAV_ID AND nav_second_page = 'space_type.html' LIMIT 1;

-- Helper: functionally, vw_menu orders by the minimal nav_role_turn across a role’s mappings.
-- Here we choose the next available number per role to avoid collisions and preserve order.

-- 4) Grant menu access to roles
-- Admin roles (1,10) see all Space items
-- General roles (2,3,4,5,7,8,9) see Spaces and My Reservations

-- Admin role 1
INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT 1, @PARENT_NAV_ID, @SID_SPACES, COALESCE((SELECT MAX(nav_role_turn)+1 FROM sys_nav_role WHERE role_id = 1), 1)
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_role WHERE role_id = 1 AND nav_id = @PARENT_NAV_ID AND nav_second_id = @SID_SPACES
);
INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT 1, @PARENT_NAV_ID, @SID_MYRES, COALESCE((SELECT MAX(nav_role_turn)+1 FROM sys_nav_role WHERE role_id = 1), 1)
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_role WHERE role_id = 1 AND nav_id = @PARENT_NAV_ID AND nav_second_id = @SID_MYRES
);
INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT 1, @PARENT_NAV_ID, @SID_LOC, COALESCE((SELECT MAX(nav_role_turn)+1 FROM sys_nav_role WHERE role_id = 1), 1)
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_role WHERE role_id = 1 AND nav_id = @PARENT_NAV_ID AND nav_second_id = @SID_LOC
);
INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT 1, @PARENT_NAV_ID, @SID_CAT, COALESCE((SELECT MAX(nav_role_turn)+1 FROM sys_nav_role WHERE role_id = 1), 1)
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_role WHERE role_id = 1 AND nav_id = @PARENT_NAV_ID AND nav_second_id = @SID_CAT
);
INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT 1, @PARENT_NAV_ID, @SID_TYPE, COALESCE((SELECT MAX(nav_role_turn)+1 FROM sys_nav_role WHERE role_id = 1), 1)
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_role WHERE role_id = 1 AND nav_id = @PARENT_NAV_ID AND nav_second_id = @SID_TYPE
);

-- Admin role 10
INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT 10, @PARENT_NAV_ID, @SID_SPACES, COALESCE((SELECT MAX(nav_role_turn)+1 FROM sys_nav_role WHERE role_id = 10), 1)
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_role WHERE role_id = 10 AND nav_id = @PARENT_NAV_ID AND nav_second_id = @SID_SPACES
);
INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT 10, @PARENT_NAV_ID, @SID_MYRES, COALESCE((SELECT MAX(nav_role_turn)+1 FROM sys_nav_role WHERE role_id = 10), 1)
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_role WHERE role_id = 10 AND nav_id = @PARENT_NAV_ID AND nav_second_id = @SID_MYRES
);
INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT 10, @PARENT_NAV_ID, @SID_LOC, COALESCE((SELECT MAX(nav_role_turn)+1 FROM sys_nav_role WHERE role_id = 10), 1)
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_role WHERE role_id = 10 AND nav_id = @PARENT_NAV_ID AND nav_second_id = @SID_LOC
);
INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT 10, @PARENT_NAV_ID, @SID_CAT, COALESCE((SELECT MAX(nav_role_turn)+1 FROM sys_nav_role WHERE role_id = 10), 1)
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_role WHERE role_id = 10 AND nav_id = @PARENT_NAV_ID AND nav_second_id = @SID_CAT
);
INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT 10, @PARENT_NAV_ID, @SID_TYPE, COALESCE((SELECT MAX(nav_role_turn)+1 FROM sys_nav_role WHERE role_id = 10), 1)
WHERE NOT EXISTS (
  SELECT 1 FROM sys_nav_role WHERE role_id = 10 AND nav_id = @PARENT_NAV_ID AND nav_second_id = @SID_TYPE
);

-- Dynamically grant to all active non-admin roles (every role in ref_role with status=1 and NOT IN (1,10))
-- Spaces
INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT r.role_id,
       @PARENT_NAV_ID,
       @SID_SPACES,
       COALESCE((SELECT MAX(nav_role_turn)+1 FROM sys_nav_role WHERE role_id = r.role_id), 1)
FROM ref_role r
WHERE r.role_status = 1
  AND r.role_id NOT IN (1,10)
  AND NOT EXISTS (
    SELECT 1 FROM sys_nav_role x
    WHERE x.role_id = r.role_id AND x.nav_id = @PARENT_NAV_ID AND x.nav_second_id = @SID_SPACES
  );

-- My Reservations
INSERT INTO sys_nav_role (role_id, nav_id, nav_second_id, nav_role_turn)
SELECT r.role_id,
       @PARENT_NAV_ID,
       @SID_MYRES,
       COALESCE((SELECT MAX(nav_role_turn)+1 FROM sys_nav_role WHERE role_id = r.role_id), 1)
FROM ref_role r
WHERE r.role_status = 1
  AND r.role_id NOT IN (1,10)
  AND NOT EXISTS (
    SELECT 1 FROM sys_nav_role x
    WHERE x.role_id = r.role_id AND x.nav_id = @PARENT_NAV_ID AND x.nav_second_id = @SID_MYRES
  );

-- 5) Verification helpers (optional; run manually)
-- List the Space submenu entries under the chosen main menu
SELECT nav_second_id, nav_id, nav_second_desc, nav_second_page, nav_second_status
FROM sys_nav_second
WHERE nav_id = @PARENT_NAV_ID AND nav_second_page IN ('space.html','my_reservations.html','space_location.html','space_category.html','space_type.html')
ORDER BY nav_second_desc;

-- Show role mappings for these items
SELECT snr.role_id, snr.nav_id, snr.nav_second_id, sn.nav_second_desc
FROM sys_nav_role snr
JOIN sys_nav_second sn ON sn.nav_second_id = snr.nav_second_id
WHERE sn.nav_id = @PARENT_NAV_ID AND sn.nav_second_page IN ('space.html','my_reservations.html','space_location.html','space_category.html','space_type.html')
ORDER BY snr.role_id, sn.nav_second_desc;
