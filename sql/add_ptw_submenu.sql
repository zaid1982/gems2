-- Add PTW Management submenu items to sys_second_nav table
-- Main menu ID 24 is for "Permit to Work"

INSERT INTO sys_second_nav (nav_id, nav_second_desc, nav_second_page, nav_second_status) VALUES
(24, 'PTW Dashboard', 'ptw_management.html', 1),
(24, 'PTW Supervisor', 'ptw_supervisor.html', 1),
(24, 'PTW SHE', 'ptw_she_dashboard.html', 1),
(24, 'PTW Facility Manager', 'ptw_fm_dashboard.html', 1);

-- Get the IDs of the newly inserted submenu items
-- Note: You'll need to run this after the INSERT to get the actual IDs
SELECT id, nav_id, description, page FROM sys_second_nav WHERE nav_id = 24 ORDER BY nav_order;

-- Add navigation permissions for admin user (user_id = 1) to sys_nav_role table
-- Replace the @submenu_id values with the actual IDs from the SELECT query above
-- You can run this query template after getting the actual IDs:

/*
INSERT INTO sys_nav_role (user_id, nav_id, second_nav_id) VALUES
(1, 24, @ptw_dashboard_id),     -- Replace @ptw_dashboard_id with actual ID
(1, 24, @ptw_supervisor_id),    -- Replace @ptw_supervisor_id with actual ID  
(1, 24, @ptw_she_id),          -- Replace @ptw_she_id with actual ID
(1, 24, @ptw_fm_id);           -- Replace @ptw_fm_id with actual ID
*/

-- Alternative: If you want to add permissions automatically, you can use this approach:
-- First, let's check what the next available IDs will be
SELECT AUTO_INCREMENT FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sys_second_nav';

-- Then insert the navigation permissions using the calculated IDs
-- Assuming the next auto_increment ID is X, the IDs will be X, X+1, X+2, X+3
-- You'll need to replace X with the actual auto_increment value

/*
Example if auto_increment is 100:
INSERT INTO sys_nav_role (user_id, nav_id, second_nav_id) VALUES
(1, 24, 100),  -- PTW Dashboard
(1, 24, 101),  -- PTW Supervisor  
(1, 24, 102),  -- PTW SHE
(1, 24, 103);  -- PTW Facility Manager
*/

-- Verification queries to check the results:
-- Check if submenu items were added correctly
SELECT sn.id, sn.nav_id, sn.description, sn.page, sn.nav_order, sn.is_active
FROM sys_second_nav sn 
WHERE sn.nav_id = 24 
ORDER BY sn.nav_order;

-- Check if permissions were added correctly for admin user
SELECT snr.user_id, snr.nav_id, snr.second_nav_id, sn.description as submenu_name
FROM sys_nav_role snr
JOIN sys_second_nav sn ON snr.second_nav_id = sn.id
WHERE snr.user_id = 1 AND snr.nav_id = 24
ORDER BY sn.nav_order;

-- Check main navigation to confirm the parent menu exists
SELECT id, description, page FROM sys_nav WHERE id = 24;
