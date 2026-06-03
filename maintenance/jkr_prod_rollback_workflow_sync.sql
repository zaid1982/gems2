-- ============================================================================
-- JKR PROD — FULL WORKFLOW SYNC (ROLLBACK)          target: gems_jkr_prod
-- ============================================================================
-- Reverts jkr_prod_deploy_workflow_sync.sql back to the pre-deploy state.
--
--   * Restores routing/role CONFIG so WO and MR behave exactly as before deploy.
--   * Removes only what the deploy ADDED: deploy-added performers (CP20 users,
--     CP19 role-7 users, CP56 reviewer pool), the CP20 in-flight assignments, and
--     the PTW roles 24-26. Each delete is guarded against FK references.
--   * KEEPS the checkpoints CP20/CP46/CP56 and roles 23/27 - the pre-flight
--     confirmed these PRE-EXISTED on prod (unwired) before the deploy. Reverting
--     the routing (CP13->14, CP41->42) un-wires them again so they go inert.
--
-- *** THE DEFINITIVE ROLLBACK IS RESTORING THE PRE-DEPLOY BACKUP. ***
--   Use this script only for a quick config-level revert shortly after deploy.
--   Two things it CANNOT undo (restore the backup if these matter):
--     1. The CP19 in-flight task fix (deploy step 1.9) overwrote the original
--        (wrong) assignee, which was not preserved.
--     2. Any task that has ENTERED CP20 / CP56 since deploy stays where it is
--        (the precheck below counts these).
--
--   Idempotent. Run with:
--     mysql -u <prod_user> -p gems_jkr_prod < jkr_prod_rollback_workflow_sync.sql
-- ============================================================================

-- ----------------------------------------------------------------------------
-- PRE-CHECK (read-only) — read this BEFORE relying on the cleanup below.
-- If any count > 0, that object will be KEPT (config is still reverted), and a
-- backup restore is recommended for a fully clean state.
-- ----------------------------------------------------------------------------
SELECT 'live/historical tasks at CP20 (Check WO)'  AS precheck, COUNT(*) AS n FROM wfl_task WHERE checkpoint_id=20
UNION ALL SELECT 'live/historical tasks at CP56 (MR Reviewer)', COUNT(*) FROM wfl_task WHERE checkpoint_id=56
UNION ALL SELECT 'live/historical tasks at CP46 (Std MR)',      COUNT(*) FROM wfl_task WHERE checkpoint_id=46
UNION ALL SELECT 'users currently holding role 27 (MR Reviewer)', COUNT(*) FROM sys_user_role WHERE role_id=27;

START TRANSACTION;

-- ############################################################################
-- SECTION 1 : revert MATERIAL REQUEST (flow 4)
-- ############################################################################
UPDATE wfl_checkpoint SET checkpoint_next = 42 WHERE checkpoint_id = 41 AND flow_id = 4;  -- gate OFF
UPDATE wfl_checkpoint SET checkpoint_order = 5 WHERE checkpoint_id = 45 AND flow_id = 4;  -- restore order
DELETE FROM wfl_checkpoint_user WHERE checkpoint_id = 56;                                 -- reviewer pool (deploy-added)
-- NOTE: CP56 & CP46 are NOT deleted - they PRE-EXISTED on prod before the deploy
-- (confirmed by the pre-flight). Reverting CP41->42 already un-wires CP56 (inert).

-- ############################################################################
-- SECTION 2 : remove roles 23-27  (only those with NO remaining references)
-- ############################################################################
-- Only the PTW roles 24-26 were ADDED by the deploy. Roles 23 & 27 PRE-EXISTED on
-- prod, so they are left in place. Each delete is guarded against any references.
DELETE FROM ref_role
WHERE role_id IN (24, 25, 26)
  AND NOT EXISTS (SELECT 1 FROM wfl_checkpoint      x WHERE x.role_id = ref_role.role_id)
  AND NOT EXISTS (SELECT 1 FROM wfl_task            x WHERE x.role_id = ref_role.role_id)
  AND NOT EXISTS (SELECT 1 FROM wfl_task_assign     x WHERE x.role_id = ref_role.role_id)
  AND NOT EXISTS (SELECT 1 FROM wfl_checkpoint_user x WHERE x.role_id = ref_role.role_id)
  AND NOT EXISTS (SELECT 1 FROM sys_user_role       x WHERE x.role_id = ref_role.role_id);

-- ############################################################################
-- SECTION 3 : revert WORK ORDER (flow 2)
-- ############################################################################
-- 3.1  routing/role config back to pre-deploy values
UPDATE wfl_checkpoint SET checkpoint_next = 14, checkpoint_case_3 = NULL WHERE checkpoint_id = 13 AND flow_id = 2;
UPDATE wfl_checkpoint SET role_id = 6, group_id = NULL                   WHERE checkpoint_id = 19 AND flow_id = 2;

-- 3.2  remove the 4 added assign rules, and restore the original 11->19 rule
DELETE FROM wfl_checkpoint_assign WHERE (checkpoint_id, checkpoint_to) IN ((12,20),(17,16),(17,19),(17,20));
INSERT INTO wfl_checkpoint_assign (checkpoint_assign_type, checkpoint_id, checkpoint_to)
SELECT 1, 11, 19 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wfl_checkpoint_assign WHERE checkpoint_id = 11 AND checkpoint_to = 19);
-- ^ restores ORIGINAL pre-deploy behaviour (this is the rule the deploy removed
--   as a bugfix). Required so the reverted role-6 CP19 has its assignment source.

-- 3.3  remove the performers the deploy added (originals on CP19 were role-6, untouched)
DELETE FROM wfl_checkpoint_user WHERE checkpoint_id = 20;
DELETE FROM wfl_checkpoint_user WHERE checkpoint_id = 19 AND role_id = 7;

-- 3.4  remove the in-flight CP20 assignments the deploy created (step 1.8)
DELETE FROM wfl_task_assign WHERE checkpoint_id = 20;

-- 3.5  CP20 itself is NOT deleted - it PRE-EXISTED on prod before the deploy
-- (confirmed by the pre-flight). Reverting CP13.next->14 already un-wires it (inert).

COMMIT;
-- ROLLBACK;  -- use instead of COMMIT to abort

-- ============================================================================
-- VERIFICATION (run after COMMIT) — confirms config is back to pre-deploy state
-- ============================================================================
SELECT 'WO: CP13 next back to 14'      AS check_name, IF((SELECT checkpoint_next FROM wfl_checkpoint WHERE checkpoint_id=13)=14,'OK','CHECK') AS result
UNION ALL SELECT 'WO: CP19 role back to 6', IF((SELECT role_id FROM wfl_checkpoint WHERE checkpoint_id=19)=6,'OK','CHECK')
UNION ALL SELECT 'WO: 4 assign rules removed', IF(COUNT(*)=0,'OK',CONCAT('left ',COUNT(*))) FROM wfl_checkpoint_assign WHERE (checkpoint_id,checkpoint_to) IN ((12,20),(17,16),(17,19),(17,20))
UNION ALL SELECT 'WO: 11->19 restored', IF(COUNT(*)=1,'OK','CHECK') FROM wfl_checkpoint_assign WHERE checkpoint_id=11 AND checkpoint_to=19
UNION ALL SELECT 'WO: CP20 deploy-added users removed', IF(COUNT(*)=0,'OK',CONCAT(COUNT(*),' left')) FROM wfl_checkpoint_user WHERE checkpoint_id=20
UNION ALL SELECT 'WO: CP20 kept (pre-existing, now un-wired)', CONCAT(COUNT(*),' present') FROM wfl_checkpoint WHERE checkpoint_id=20
UNION ALL SELECT 'MR: CP41 next back to 42', IF((SELECT checkpoint_next FROM wfl_checkpoint WHERE checkpoint_id=41)=42,'OK','CHECK')
UNION ALL SELECT 'MR: CP56/CP46 kept (pre-existing)', CONCAT((SELECT COUNT(*) FROM wfl_checkpoint WHERE checkpoint_id IN (46,56)),' present')
UNION ALL SELECT 'Roles: PTW 24-26 removed', IF(COUNT(*)=0,'OK',CONCAT(COUNT(*),' left')) FROM ref_role WHERE role_id IN (24,25,26)
UNION ALL SELECT 'Roles: 23 & 27 kept (pre-existing)', CONCAT(COUNT(*),' present') FROM ref_role WHERE role_id IN (23,27);
