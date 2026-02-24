-- ============================================================================
-- PRODUCTION FIX: Self-Finding WR-to-WO Conversion
-- Date: 2025-07-12
-- ============================================================================
-- 
-- ROOT CAUSE:
-- The web API (wo_v3.php → WoTask::submitAssign()) did NOT have the 
-- self-finding WR-to-WO conversion logic that the mobile API (m_wo.php → 
-- Class_wo::submit_assign()) has. When self-finding tickets were assigned
-- via the web interface, they remained as WRs instead of converting to WOs.
--
-- CODE FIX: 
-- api/class/WoTask.php submitAssign() now checks woTaskTypeInit=2 and 
-- converts WR→WO (generates new WO number, sets is_wr=0, status=13).
-- api/wo_v3.php now uses updated woTaskIsWr/woTaskNo after submitAssign.
--
-- DATA FIX (this script):
-- 3 tickets stuck as WRs that should have been converted to WOs.
-- All are from Demo Client site (site_id=19, site_code=DEMO, group_id=21).
-- Current site_running_no_wo = 162.
-- 
-- Tickets to fix:
-- 1. 112180 (WRDEMO25100700206) → CP 13 Execute WO, status 27 → should be 13
-- 2. 119308 (WRDEMO26022500245) → CP 15 Closed, status 25 → keep status, fix WR flag
-- 3. 119310 (WRDEMO26022500247) → CP 13 Execute WO, status 27 → should be 13
-- ============================================================================

-- STEP 0: Verify current state
SELECT wo_task_id, wo_task_no, wo_task_type_init, wo_task_is_wr, wo_task_status, 
       wo_task_is_pdf, wo_task_is_pdf_wr, transaction_id
FROM wo_task 
WHERE wo_task_id IN (112180, 119308, 119310);

-- STEP 1: Fix ticket 112180 (oldest, at Execute WO)
-- New WO number: WODEMO + today's date + 00162
UPDATE wo_task SET
    wo_task_no = CONCAT('WODEMO', DATE_FORMAT(NOW(), '%y%m%d'), '00162'),
    wo_task_is_wr = 0,
    wo_task_status = 13,
    wo_task_is_pdf = 1,
    wo_task_is_pdf_wr = 0
WHERE wo_task_id = 112180;

UPDATE wfl_transaction SET transaction_status = 13
WHERE transaction_id = 2346334;

-- STEP 2: Fix ticket 119308 (at Closed, status 25 — keep status, just fix WR flag & number)
-- New WO number: WODEMO + today's date + 00163
UPDATE wo_task SET
    wo_task_no = CONCAT('WODEMO', DATE_FORMAT(NOW(), '%y%m%d'), '00163'),
    wo_task_is_wr = 0,
    wo_task_is_pdf = 1,
    wo_task_is_pdf_wr = 0
WHERE wo_task_id = 119308;
-- Note: wo_task_status=25 and transaction_status=25 kept as-is since ticket is at Closed checkpoint

-- STEP 3: Fix ticket 119310 (at Execute WO)
-- New WO number: WODEMO + today's date + 00164
UPDATE wo_task SET
    wo_task_no = CONCAT('WODEMO', DATE_FORMAT(NOW(), '%y%m%d'), '00164'),
    wo_task_is_wr = 0,
    wo_task_status = 13,
    wo_task_is_pdf = 1,
    wo_task_is_pdf_wr = 0
WHERE wo_task_id = 119310;

UPDATE wfl_transaction SET transaction_status = 13
WHERE transaction_id = 2353756;

-- STEP 4: Update site running number (used 162, 163, 164 → next is 165)
UPDATE cli_site SET site_running_no_wo = 165
WHERE site_id = 19;

-- STEP 5: Verify fix
SELECT wo_task_id, wo_task_no, wo_task_type_init, wo_task_is_wr, wo_task_status,
       wo_task_is_pdf, wo_task_is_pdf_wr
FROM wo_task 
WHERE wo_task_id IN (112180, 119308, 119310);

SELECT site_id, site_name, site_running_no_wo, site_running_no_wr
FROM cli_site WHERE site_id = 19;
