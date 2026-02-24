-- =============================================================================
-- PROD FIX: WO Workflow — CP 19 (Verify WR) Wrong User Assignment
-- =============================================================================
-- Date: 2026-02-25
-- Database: gems2 @ 10.101.11.69
-- Issue: CP 19 (Verify WR) is being assigned to the Client/Complainer instead
--        of the WO Assigner. This is because PROD has an extra checkpoint_assign
--        rule (id=11) that pre-assigns the complainer at CP 11 to CP 19.
--        DEV does NOT have this rule — in DEV, CP 19 is assigned by CP 17
--        (Assign WR, the WO Assigner).
--
-- Root Cause: checkpoint_assign id=11 (type=1, CP 11→CP 19, "assign to himself")
--             runs when the complaint is submitted, pre-assigning the CLIENT to
--             CP 19. When CP 17 later tries to assign the WO Assigner to CP 19,
--             the record already exists (same transaction + checkpoint + role),
--             so the assignment is SKIPPED. The client ends up as the verifier.
--
-- DEV assign rules for CP 11: [id=2: CP11→CP11], [id=3: CP11→CP14]
-- PROD assign rules for CP 11: [id=2: CP11→CP11], [id=3: CP11→CP14], [id=11: CP11→CP19] ← EXTRA
-- =============================================================================

-- =============================================
-- FIX 1: Delete the wrong checkpoint_assign rule
-- =============================================
-- This prevents future complaints from pre-assigning the client to CP 19.
DELETE FROM wfl_checkpoint_assign
WHERE checkpoint_assign_id = 11
AND checkpoint_id = 11
AND checkpoint_to = 19;

-- =============================================
-- FIX 2: Fix in-flight task_assign records
-- =============================================
-- For all pending CP 19 tasks, update the wfl_task_assign record to use the
-- WO Assigner from CP 17 (who should be the verifier) instead of the client.
-- Only updates records where the current assigned user is NOT in CP 19's
-- checkpoint_user list (i.e., they're a client, not a valid verifier).
UPDATE wfl_task_assign ta
JOIN (
    SELECT 
        ta2.task_assign_id,
        cp17_task.task_claimed_user AS correct_user,
        cp17_task.group_id AS correct_group
    FROM wfl_task_assign ta2
    JOIN wfl_task pending ON ta2.transaction_id = pending.transaction_id
        AND pending.checkpoint_id = 19 AND pending.task_status = 8
    JOIN wfl_task cp17_task ON ta2.transaction_id = cp17_task.transaction_id
        AND cp17_task.checkpoint_id = 17
    WHERE ta2.checkpoint_id = 19
    AND ta2.user_id NOT IN (
        SELECT user_id FROM wfl_checkpoint_user WHERE checkpoint_id = 19
    )
) fix ON ta.task_assign_id = fix.task_assign_id
SET ta.user_id = fix.correct_user,
    ta.group_id = fix.correct_group,
    ta.role_id = 7;

-- =============================================
-- FIX 3: Fix in-flight wfl_task claimed user
-- =============================================
-- Update the actual task records so the correct WO Assigner is claimed.
UPDATE wfl_task t
JOIN (
    SELECT 
        pending.task_id,
        cp17_task.task_claimed_user AS correct_user,
        cp17_task.group_id AS correct_group
    FROM wfl_task pending
    JOIN wfl_task cp17_task ON pending.transaction_id = cp17_task.transaction_id
        AND cp17_task.checkpoint_id = 17
    WHERE pending.checkpoint_id = 19 AND pending.task_status = 8
    AND pending.task_claimed_user NOT IN (
        SELECT user_id FROM wfl_checkpoint_user WHERE checkpoint_id = 19
    )
) fix ON t.task_id = fix.task_id
SET t.task_claimed_user = fix.correct_user,
    t.group_id = fix.correct_group;

-- =============================================================================
-- VERIFICATION QUERIES
-- =============================================================================

-- Verify assign rule deleted
-- Expected: NO row with checkpoint_to=19 under checkpoint_id=11
-- SELECT * FROM wfl_checkpoint_assign WHERE checkpoint_id = 11;

-- Verify pending CP 19 tasks now have correct users
-- SELECT t.task_id, t.transaction_id, t.task_claimed_user, u.user_name, t.task_status
-- FROM wfl_task t JOIN sys_user u ON t.task_claimed_user = u.user_id
-- WHERE t.checkpoint_id = 19 AND t.task_status = 8 ORDER BY t.task_time_created DESC;
