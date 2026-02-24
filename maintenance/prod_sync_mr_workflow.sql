-- =============================================================================
-- PROD FIX: Material Request (Flow 4) — Add MR Reviewer Step (CP 56)
-- =============================================================================
-- Date: 2026-02-25
-- Database: gems2 @ 10.101.11.69
-- Issue: MR Reviewer step (CP 56) exists in DEV but missing in PROD.
--        CP 41 (Request Material) points directly to CP 42 (Verify Request),
--        skipping the MR Reviewer entirely.
-- Effect: Supervisor (role 27) never sees material requests in their pending list.
--         Requests go straight to Manager Approval (CP 42) bypassing review.
-- =============================================================================
-- DEV Flow 4:  CP 41 → CP 56 (MR Reviewer) → CP 42 → CP 43 → CP 44/45
-- PROD Flow 4: CP 41 → CP 42 → CP 43 → CP 44/45  (CP 56 MISSING!)
-- =============================================================================

-- =============================================
-- FIX 1: Insert CP 56 (MR Reviewer) checkpoint
-- =============================================
-- Matches DEV exactly: flow_id=4, type=2 (intermediate), claim_type=1,
-- next=42 (Verify Request), case_1=45 (Rejected), role_id=27, order=2
INSERT INTO wfl_checkpoint (
    checkpoint_id, flow_id, checkpoint_desc, checkpoint_type,
    checkpoint_claim_type, checkpoint_due_day, checkpoint_next,
    checkpoint_case_1, checkpoint_case_2, checkpoint_case_3,
    checkpoint_icon, role_id, group_id, checkpoint_order,
    checkpoint_color, checkpoint_skip
) VALUES (
    56, 4, 'MR Reviewer', 2,
    1, NULL, 42,
    45, NULL, NULL,
    NULL, 27, NULL, 2,
    NULL, ''
);

-- =============================================
-- FIX 2: Update CP 41 to route to CP 56 first
-- =============================================
-- Before: checkpoint_next = 42 (skips reviewer)
-- After:  checkpoint_next = 56 (goes to MR Reviewer first)
UPDATE wfl_checkpoint
SET checkpoint_next = 56
WHERE checkpoint_id = 41 AND flow_id = 4;

-- =============================================
-- FIX 3: Populate CP 56 users from role 27
-- =============================================
-- Add all PROD users who have MR Reviewer role (role_id=27) as CP 56 users.
-- Uses sys_user_role to find role 27 users, and sys_user_group for group_id.
-- DEV had 3 users; PROD has 9 users with role 27.
INSERT INTO wfl_checkpoint_user (checkpoint_id, user_id, role_id, group_id)
SELECT 56, ur.user_id, 27, COALESCE(ug.group_id, 1)
FROM sys_user_role ur
LEFT JOIN sys_user_group ug ON ur.user_id = ug.user_id
WHERE ur.role_id = 27
AND ur.user_id NOT IN (
    SELECT user_id FROM wfl_checkpoint_user WHERE checkpoint_id = 56
);

-- =============================================================================
-- VERIFICATION QUERIES (run after applying fixes)
-- =============================================================================

-- Verify CP 56 exists with correct routing
-- Expected: checkpoint_next=42, checkpoint_case_1=45, role_id=27
-- SELECT checkpoint_id, checkpoint_desc, checkpoint_type, checkpoint_next,
--        checkpoint_case_1, role_id, group_id, checkpoint_order
-- FROM wfl_checkpoint WHERE flow_id = 4 ORDER BY checkpoint_order;

-- Verify CP 41 now routes to CP 56
-- Expected: checkpoint_next=56
-- SELECT checkpoint_id, checkpoint_next FROM wfl_checkpoint WHERE checkpoint_id = 41;

-- Verify CP 56 users populated
-- Expected: 9 users (all PROD role 27 users)
-- SELECT cu.checkpoint_id, cu.user_id, cu.role_id, cu.group_id, u.user_name
-- FROM wfl_checkpoint_user cu
-- JOIN sys_user u ON cu.user_id = u.user_id
-- WHERE cu.checkpoint_id = 56;

-- =============================================================================
-- NOTE: Existing in-flight tasks at CP 42 (status=8, unclaimed) are NOT affected.
-- Those 333 pending tasks already skipped the reviewer step. They can still be
-- approved/rejected normally at CP 42. Only NEW material requests submitted
-- AFTER this fix will go through CP 56 (MR Reviewer) first.
-- =============================================================================
