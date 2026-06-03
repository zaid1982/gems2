-- ============================================================================
-- JKR PROD PRE-FLIGHT ASSESSMENT  (100% READ-ONLY - safe to run anytime)
-- ============================================================================
-- Run this on gems_jkr_prod BEFORE jkr_sync_wo_workflow.sql / jkr_sync_mr_workflow.sql.
-- It reports prod's actual state and flags anything that would make the sync
-- scripts behave unexpectedly. It performs NO INSERT/UPDATE/DELETE.
--
--   Usage on the server:
--     mysql -u <prod_user> -p gems_jkr_prod < jkr_preflight_assessment.sql
-- ============================================================================

SELECT '========== 1) ID SAFETY (explicit ids the scripts insert) ==========' AS report;
-- Each id must be FREE (will be created) or already MATCH the expected row.
-- A 'CONFLICT' means prod uses that id for something else -> STOP and tell me.
SELECT 'checkpoint 20 (Check WO)' AS id,
  CASE WHEN COUNT(*)=0 THEN 'FREE -> will be created'
       WHEN MAX(checkpoint_desc)='Check WO' AND MAX(flow_id)=2 THEN 'MATCHES -> ok (skip)'
       ELSE CONCAT('*** CONFLICT: holds "', MAX(checkpoint_desc), '" flow ', MAX(flow_id)) END AS verdict
FROM wfl_checkpoint WHERE checkpoint_id=20
UNION ALL
SELECT 'checkpoint 46 (Std MR)',
  CASE WHEN COUNT(*)=0 THEN 'FREE -> will be created'
       WHEN MAX(checkpoint_desc)='Request Material Standalone' THEN 'MATCHES -> ok (skip)'
       ELSE CONCAT('*** CONFLICT: holds "', MAX(checkpoint_desc), '"') END
FROM wfl_checkpoint WHERE checkpoint_id=46
UNION ALL
SELECT 'checkpoint 56 (MR Reviewer)',
  CASE WHEN COUNT(*)=0 THEN 'FREE -> will be created'
       WHEN MAX(checkpoint_desc)='MR Reviewer' THEN 'MATCHES -> ok (skip)'
       ELSE CONCAT('*** CONFLICT: holds "', MAX(checkpoint_desc), '"') END
FROM wfl_checkpoint WHERE checkpoint_id=56;

SELECT 'roles 23-27' AS id,
  CASE WHEN COUNT(*)=0 THEN 'all FREE -> will be created'
       WHEN COUNT(*)=5 THEN 'all 5 present -> verify names below match cloud'
       ELSE CONCAT(COUNT(*),'/5 present (ids: ', GROUP_CONCAT(role_id ORDER BY role_id),') -> verify below') END AS verdict
FROM ref_role WHERE role_id BETWEEN 23 AND 27;
SELECT role_id, role_desc, role_type FROM ref_role WHERE role_id BETWEEN 23 AND 27 ORDER BY role_id;
-- Expected (cloud): 23 Standalone Material Requestor(2), 24 PTW Supervisor(2),
--                   25 PTW SHE(2), 26 PTW Facility Manager(2), 27 MR Reviewer(1)

SELECT '========== 2) WORK ORDER (flow 2) config state ==========' AS report;
-- If "needs WO sync" = YES, run jkr_sync_wo_workflow.sql. (On a fresh prod it will be YES.)
SELECT
  (SELECT COUNT(*) FROM wfl_checkpoint WHERE checkpoint_id=20)                                   AS cp20_exists_want_1,
  (SELECT checkpoint_next FROM wfl_checkpoint WHERE checkpoint_id=13)                            AS cp13_next_want_20,
  (SELECT role_id FROM wfl_checkpoint WHERE checkpoint_id=19)                                    AS cp19_role_want_7,
  (SELECT COUNT(*) FROM wfl_checkpoint_assign WHERE checkpoint_id=11 AND checkpoint_to=19)       AS bug_11_19_want_0,
  (SELECT COUNT(*) FROM wfl_checkpoint_user WHERE checkpoint_id=20)                              AS cp20_users,
  (SELECT COUNT(*) FROM wfl_checkpoint_user WHERE checkpoint_id=19 AND role_id=7)                AS cp19_role7_users;

SELECT '========== 3) WORK ORDER in-flight blast radius (Part D scope on prod) ==========' AS report;
SELECT
  SUM(w.wo_task_type_init NOT IN ('2','6')) AS cp13_type1_need_cp20_backfill,
  SUM(w.wo_task_type_init IN ('2','6'))     AS cp13_type2_safe_no_action
FROM wfl_task t
JOIN wfl_transaction tr ON tr.transaction_id=t.transaction_id AND tr.flow_id=2
JOIN wo_task w          ON w.transaction_id=t.transaction_id
WHERE t.task_current=1 AND t.checkpoint_id=13;

-- Will every type-1 CP13 ticket resolve to a CP20 assignee? UNRESOLVED>0 = review those.
SELECT
  SUM(uid IS NOT NULL) AS resolvable,
  SUM(uid IS NULL)     AS UNRESOLVED_will_not_get_cp20_assign
FROM (
  SELECT t.transaction_id,
    COALESCE(
      (SELECT a.user_id FROM wfl_task_assign a WHERE a.transaction_id=t.transaction_id AND a.checkpoint_id=16 ORDER BY a.task_assign_id DESC LIMIT 1),
      (SELECT c.task_claimed_user FROM wfl_task c WHERE c.transaction_id=t.transaction_id AND c.checkpoint_id=12 ORDER BY c.task_id DESC LIMIT 1),
      w.wo_task_assigned_by) AS uid
  FROM wfl_task t
  JOIN wfl_transaction tr ON tr.transaction_id=t.transaction_id AND tr.flow_id=2
  JOIN wo_task w          ON w.transaction_id=t.transaction_id AND w.wo_task_type_init NOT IN ('2','6')
  WHERE t.task_current=1 AND t.checkpoint_id=13
) s;

-- CP19 tickets currently mis-assigned vs their CP17 assigner (Part D.2 scope).
SELECT COUNT(*) AS cp19_misassigned_to_fix
FROM wfl_task ct
JOIN (SELECT x.transaction_id, x.task_claimed_user AS assigner FROM wfl_task x
      JOIN (SELECT transaction_id, MAX(task_id) mid FROM wfl_task WHERE checkpoint_id=17 GROUP BY transaction_id) m
        ON m.transaction_id=x.transaction_id AND m.mid=x.task_id) c17 ON c17.transaction_id=ct.transaction_id
WHERE ct.task_current=1 AND ct.checkpoint_id=19 AND ct.task_status=8
  AND c17.assigner IS NOT NULL AND ct.task_claimed_user<>c17.assigner;

SELECT '========== 4) MATERIAL REQUEST (flow 4) state ==========' AS report;
SELECT
  (SELECT checkpoint_next FROM wfl_checkpoint WHERE checkpoint_id=41)        AS cp41_next_42off_56on,
  (SELECT COUNT(*) FROM wfl_checkpoint WHERE checkpoint_id=56)               AS cp56_exists,
  (SELECT COUNT(*) FROM wfl_checkpoint WHERE checkpoint_id=46)               AS cp46_exists,
  (SELECT COUNT(*) FROM ref_role WHERE role_id IN (23,27))                   AS mr_roles_present_of_2,
  (SELECT COUNT(*) FROM sys_user_role WHERE role_id=27)                      AS mr_reviewer_users,
  (SELECT COUNT(*) FROM wfl_task t JOIN wfl_transaction tr ON tr.transaction_id=t.transaction_id
     AND tr.flow_id=4 WHERE t.task_current=1 AND t.checkpoint_id=41)         AS mr_inflight_at_cp41;

SELECT '========== END OF PRE-FLIGHT (no changes were made) ==========' AS report;
