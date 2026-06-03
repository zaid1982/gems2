-- ============================================================================
-- JKR PROD HOTFIX: Backfill missing wfl_task_assign rows for in-flight WRs
-- ============================================================================
-- Symptom (server log, hundreds per day):
--   [Class_task:submit_task:407] - [373] - Data taskAssign empty when assigned to user
--   [API:api_m_wo:599] - (ErrCode:0005) [Class_task:submit_task:408] -
--     Data taskAssign empty when assigned to user
--
-- Triggered when the WR Check button is tapped in the mobile app
-- (action=submit_wr_check). The engine submits CP 18 -> CP 19 (Verify WR).
-- CP 19 has checkpoint_claim_type=3 (assign-to-user), so submit_task expects
-- a wfl_task_assign row for (transaction_id, checkpoint_id=19, role_id=7).
-- That row is created by the CP17 -> CP19 rule in wfl_checkpoint_assign
-- (added by jkr_sync_wo_workflow.sql Part B). WRs that were assigned BEFORE
-- that rule was deployed have no such row -> every WR Check submit fails
-- with "Data taskAssign empty when assigned to user" and rolls back.
--
-- prod_fix_cp19_assignment.sql and jkr_sync_wo_workflow.sql Part D.2 only
-- handle WRs whose CP19 row EXISTS but points to the wrong user. They do
-- NOT insert missing rows. This script fills that gap.
--
-- Also backfills:
--   * CP 16 (Verify and Close WO) for in-flight WO at CP 13 that came from
--     an Assign WR (CP 17) before the 17->16 rule existed.
--   * CP 20 (Check WO) for the same in-flight CP 13 WOs, in case Part D.1
--     missed any because it scoped to wo_task_type_init NOT IN ('2','6').
--
-- The script is idempotent: every INSERT is guarded by NOT EXISTS, so it is
-- safe to re-run. No DELETE / UPDATE is performed on existing rows.
--
-- USAGE:
--   1) Take a fresh prod backup (mysqldump gems2 > backup_pre_fix.sql)
--   2) Run the DIAGNOSTIC block first (read only) to see how many tickets
--      are affected.
--   3) If counts look right, run the FIX block.
--   4) Run the VERIFICATION block to confirm.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 0) DIAGNOSTIC (read-only). Run this first. All counts should be > 0 if you
--    are seeing the "Data taskAssign empty" errors.
-- ----------------------------------------------------------------------------
SELECT 'in-flight CP18 WRs missing CP19 assignment' AS check_name, COUNT(*) AS affected
FROM wfl_task t
JOIN wfl_transaction tr ON tr.transaction_id = t.transaction_id AND tr.flow_id = 2
WHERE t.task_current = 1 AND t.checkpoint_id = 18 AND t.task_status = 8
  AND NOT EXISTS (
        SELECT 1 FROM wfl_task_assign a
        WHERE a.transaction_id = t.transaction_id
          AND a.checkpoint_id = 19
          AND a.role_id = 7
      )
UNION ALL
SELECT 'in-flight WR-origin CP13 WOs missing CP16 assignment', COUNT(*)
FROM wfl_task t
JOIN wfl_transaction tr ON tr.transaction_id = t.transaction_id AND tr.flow_id = 2
JOIN wo_task w         ON w.transaction_id  = t.transaction_id
WHERE t.task_current = 1 AND t.checkpoint_id = 13
  AND EXISTS (SELECT 1 FROM wfl_task x WHERE x.transaction_id = t.transaction_id AND x.checkpoint_id = 17)
  AND NOT EXISTS (
        SELECT 1 FROM wfl_task_assign a
        WHERE a.transaction_id = t.transaction_id
          AND a.checkpoint_id = 16
      )
UNION ALL
SELECT 'in-flight WR-origin CP13 WOs missing CP20 assignment', COUNT(*)
FROM wfl_task t
JOIN wfl_transaction tr ON tr.transaction_id = t.transaction_id AND tr.flow_id = 2
JOIN wo_task w         ON w.transaction_id  = t.transaction_id
WHERE t.task_current = 1 AND t.checkpoint_id = 13
  AND EXISTS (SELECT 1 FROM wfl_task x WHERE x.transaction_id = t.transaction_id AND x.checkpoint_id = 17)
  AND NOT EXISTS (
        SELECT 1 FROM wfl_task_assign a
        WHERE a.transaction_id = t.transaction_id
          AND a.checkpoint_id = 20
      );

-- Optional: list the actual WR task numbers that are stuck (limit 50).
SELECT t.transaction_id, w.wo_task_no, w.wo_task_status, t.task_claimed_user AS wr_checker,
       c17.task_claimed_user AS wr_assigner,
       w.wo_task_time_created
FROM wfl_task t
JOIN wfl_transaction tr ON tr.transaction_id = t.transaction_id AND tr.flow_id = 2
JOIN wo_task w          ON w.transaction_id  = t.transaction_id
LEFT JOIN (
    SELECT x.transaction_id, x.task_claimed_user, x.group_id
    FROM wfl_task x
    JOIN (SELECT transaction_id, MAX(task_id) mid FROM wfl_task WHERE checkpoint_id=17 GROUP BY transaction_id) m
      ON m.transaction_id = x.transaction_id AND m.mid = x.task_id
) c17 ON c17.transaction_id = t.transaction_id
WHERE t.task_current = 1 AND t.checkpoint_id = 18 AND t.task_status = 8
  AND NOT EXISTS (
        SELECT 1 FROM wfl_task_assign a
        WHERE a.transaction_id = t.transaction_id
          AND a.checkpoint_id = 19
          AND a.role_id = 7
      )
ORDER BY w.wo_task_time_created DESC
LIMIT 50;

-- ============================================================================
-- 1) FIX (writes). Wrap in a transaction so you can ROLLBACK if numbers wrong.
-- ============================================================================
START TRANSACTION;

-- 1.1  Backfill missing CP 19 (Verify WR) task_assign rows for in-flight CP18
--      WRs, using the CP 17 (Assign WR) assigner as the verifier. Role = 7
--      because CP 19 expects role 7 after jkr_sync_wo_workflow.sql Part A.
INSERT INTO wfl_task_assign (transaction_id, checkpoint_id, role_id, group_id, user_id)
SELECT s.transaction_id, 19, 7, COALESCE(s.group_id, 1), s.user_id
FROM (
    SELECT t.transaction_id,
           c17.group_id,
           c17.task_claimed_user AS user_id
    FROM wfl_task t
    JOIN wfl_transaction tr ON tr.transaction_id = t.transaction_id AND tr.flow_id = 2
    JOIN (
        SELECT x.transaction_id, x.task_claimed_user, x.group_id
        FROM wfl_task x
        JOIN (SELECT transaction_id, MAX(task_id) mid FROM wfl_task WHERE checkpoint_id=17 GROUP BY transaction_id) m
          ON m.transaction_id = x.transaction_id AND m.mid = x.task_id
    ) c17 ON c17.transaction_id = t.transaction_id
    WHERE t.task_current = 1 AND t.checkpoint_id = 18 AND t.task_status = 8
      AND c17.task_claimed_user IS NOT NULL
      AND NOT EXISTS (
            SELECT 1 FROM wfl_task_assign a
            WHERE a.transaction_id = t.transaction_id
              AND a.checkpoint_id = 19
              AND a.role_id = 7
          )
) s;

SELECT 'rows inserted into wfl_task_assign for CP19' AS step, ROW_COUNT() AS rows_inserted;

-- 1.2  Backfill missing CP 16 (Verify and Close WO) task_assign rows for
--      in-flight CP 13 (Execute WO) WOs that originated from an Assign WR
--      (CP 17). Role = 7 to match CP 16's expected verifier role.
INSERT INTO wfl_task_assign (transaction_id, checkpoint_id, role_id, group_id, user_id)
SELECT s.transaction_id, 16, 7, COALESCE(s.group_id, 1), s.user_id
FROM (
    SELECT t.transaction_id,
           c17.group_id,
           c17.task_claimed_user AS user_id
    FROM wfl_task t
    JOIN wfl_transaction tr ON tr.transaction_id = t.transaction_id AND tr.flow_id = 2
    JOIN (
        SELECT x.transaction_id, x.task_claimed_user, x.group_id
        FROM wfl_task x
        JOIN (SELECT transaction_id, MAX(task_id) mid FROM wfl_task WHERE checkpoint_id=17 GROUP BY transaction_id) m
          ON m.transaction_id = x.transaction_id AND m.mid = x.task_id
    ) c17 ON c17.transaction_id = t.transaction_id
    WHERE t.task_current = 1 AND t.checkpoint_id = 13
      AND c17.task_claimed_user IS NOT NULL
      AND NOT EXISTS (
            SELECT 1 FROM wfl_task_assign a
            WHERE a.transaction_id = t.transaction_id
              AND a.checkpoint_id = 16
          )
) s;

SELECT 'rows inserted into wfl_task_assign for CP16' AS step, ROW_COUNT() AS rows_inserted;

-- 1.3  Backfill missing CP 20 (Check WO) task_assign rows for in-flight CP 13
--      WOs that originated from Assign WR. (Part D.1 of jkr_sync_wo_workflow
--      scoped this to wo_task_type_init NOT IN ('2','6'); this catches the
--      remaining WR-origin ones that the broader filter missed.)
INSERT INTO wfl_task_assign (transaction_id, checkpoint_id, role_id, group_id, user_id)
SELECT s.transaction_id, 20, 7, COALESCE(s.group_id, 1), s.user_id
FROM (
    SELECT t.transaction_id,
           c17.group_id,
           c17.task_claimed_user AS user_id
    FROM wfl_task t
    JOIN wfl_transaction tr ON tr.transaction_id = t.transaction_id AND tr.flow_id = 2
    JOIN (
        SELECT x.transaction_id, x.task_claimed_user, x.group_id
        FROM wfl_task x
        JOIN (SELECT transaction_id, MAX(task_id) mid FROM wfl_task WHERE checkpoint_id=17 GROUP BY transaction_id) m
          ON m.transaction_id = x.transaction_id AND m.mid = x.task_id
    ) c17 ON c17.transaction_id = t.transaction_id
    WHERE t.task_current = 1 AND t.checkpoint_id = 13
      AND c17.task_claimed_user IS NOT NULL
      AND NOT EXISTS (
            SELECT 1 FROM wfl_task_assign a
            WHERE a.transaction_id = t.transaction_id
              AND a.checkpoint_id = 20
          )
) s;

SELECT 'rows inserted into wfl_task_assign for CP20' AS step, ROW_COUNT() AS rows_inserted;

-- If the counts above look reasonable, COMMIT. Otherwise ROLLBACK and tell me.
COMMIT;
-- ROLLBACK;

-- ============================================================================
-- 2) VERIFICATION (read-only). All four checks must be OK.
-- ============================================================================
SELECT 'CP19 assign rule (17->19) present' AS check_name,
       IF(COUNT(*) = 1, 'OK', 'FAIL - run jkr_sync_wo_workflow.sql first') AS result
FROM wfl_checkpoint_assign WHERE checkpoint_id = 17 AND checkpoint_to = 19
UNION ALL
SELECT 'in-flight CP18 WRs missing CP19 assignment (after fix)',
       IF(COUNT(*) = 0, 'OK', CONCAT('FAIL (', COUNT(*), ' still missing)'))
FROM wfl_task t
JOIN wfl_transaction tr ON tr.transaction_id = t.transaction_id AND tr.flow_id = 2
WHERE t.task_current = 1 AND t.checkpoint_id = 18 AND t.task_status = 8
  AND NOT EXISTS (
        SELECT 1 FROM wfl_task_assign a
        WHERE a.transaction_id = t.transaction_id
          AND a.checkpoint_id = 19
          AND a.role_id = 7
      )
UNION ALL
SELECT 'in-flight WR-origin CP13 missing CP16 assignment (after fix)',
       IF(COUNT(*) = 0, 'OK', CONCAT('FAIL (', COUNT(*), ' still missing)'))
FROM wfl_task t
JOIN wfl_transaction tr ON tr.transaction_id = t.transaction_id AND tr.flow_id = 2
WHERE t.task_current = 1 AND t.checkpoint_id = 13
  AND EXISTS (SELECT 1 FROM wfl_task x WHERE x.transaction_id = t.transaction_id AND x.checkpoint_id = 17)
  AND NOT EXISTS (
        SELECT 1 FROM wfl_task_assign a
        WHERE a.transaction_id = t.transaction_id
          AND a.checkpoint_id = 16
      )
UNION ALL
SELECT 'in-flight WR-origin CP13 missing CP20 assignment (after fix)',
       IF(COUNT(*) = 0, 'OK', CONCAT('FAIL (', COUNT(*), ' still missing)'))
FROM wfl_task t
JOIN wfl_transaction tr ON tr.transaction_id = t.transaction_id AND tr.flow_id = 2
WHERE t.task_current = 1 AND t.checkpoint_id = 13
  AND EXISTS (SELECT 1 FROM wfl_task x WHERE x.transaction_id = t.transaction_id AND x.checkpoint_id = 17)
  AND NOT EXISTS (
        SELECT 1 FROM wfl_task_assign a
        WHERE a.transaction_id = t.transaction_id
          AND a.checkpoint_id = 20
      );
