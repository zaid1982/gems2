-- ============================================================
-- GEMS2 PROD MIGRATION: Sync WO Workflow with DEV
-- Date: 2026-02-24
-- Run on: gems2 @ 10.101.11.69
-- ============================================================

START TRANSACTION;

-- FIX 1: Add Checkpoint 20 (Check WO) - missing from PROD
INSERT INTO wfl_checkpoint
  (checkpoint_id, flow_id, checkpoint_desc, checkpoint_type, checkpoint_claim_type,
   checkpoint_due_day, checkpoint_next, checkpoint_case_1, checkpoint_case_2, checkpoint_case_3,
   role_id, group_id, checkpoint_order, checkpoint_skip)
VALUES
  (20, 2, 'Check WO', 2, 3, 1, 14, 16, 13, NULL, 7, 1, 5, 0);

-- FIX 2: Update CP 13 next from 14 to 20, add case_3=13
UPDATE wfl_checkpoint
SET checkpoint_next = 20, checkpoint_case_3 = 13
WHERE checkpoint_id = 13 AND flow_id = 2;

-- FIX 3: Update CP 19 role from 6 (Client) to 7 (Assigner)
UPDATE wfl_checkpoint
SET role_id = 7, group_id = 1
WHERE checkpoint_id = 19 AND flow_id = 2;

-- FIX 4: Add missing checkpoint_assign rules
INSERT INTO wfl_checkpoint_assign (checkpoint_assign_type, checkpoint_id, checkpoint_to)
VALUES
  (1, 12, 20),
  (1, 17, 16),
  (1, 17, 19),
  (1, 17, 20);

-- FIX 5: Copy CP 12 users to CP 20 (same role 7 = WO Assigner)
INSERT INTO wfl_checkpoint_user (checkpoint_id, user_id, role_id, group_id)
SELECT 20, user_id, role_id, group_id FROM wfl_checkpoint_user WHERE checkpoint_id = 12;

-- FIX 6: Replace CP 19 users with role 7 users (copy from CP 17)
DELETE FROM wfl_checkpoint_user WHERE checkpoint_id = 19;
INSERT INTO wfl_checkpoint_user (checkpoint_id, user_id, role_id, group_id)
SELECT 19, user_id, role_id, group_id FROM wfl_checkpoint_user WHERE checkpoint_id = 17;

COMMIT;

SELECT 'MIGRATION COMPLETE' AS status;
