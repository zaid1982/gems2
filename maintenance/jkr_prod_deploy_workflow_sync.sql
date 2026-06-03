-- ============================================================================
-- JKR PROD — FULL WORKFLOW SYNC (DEPLOY)            target: gems_jkr_prod
-- ============================================================================
-- Brings the JKR workflow configuration in line with gems Global (cloud).
-- Combines, in ONE atomic transaction:
--     SECTION 1 - Work Order (flow 2): config + in-flight backfill
--     SECTION 2 - Roles (ref_role parity 23-27)
--     SECTION 3 - Material Request (flow 4): config + reviewer pool (gate OFF)
--
-- PROPERTIES
--   * Single transaction (all-or-nothing). Any error rolls the whole thing back.
--   * Idempotent: re-running makes no further changes (guards on every statement).
--   * Data-driven: the in-flight backfill adapts to whatever prod actually has.
--   * No schema changes. The MR Reviewer GATE is intentionally left OFF (3.5).
--
-- BEFORE RUNNING: take a backup and run jkr_preflight_assessment.sql first.
--   Run with:  mysql -u <prod_user> -p gems_jkr_prod < jkr_prod_deploy_workflow_sync.sql
-- ============================================================================

START TRANSACTION;

-- ############################################################################
-- SECTION 1 : WORK ORDER  (wfl_flow.flow_id = 2)
-- ############################################################################

-- 1.1  CP 20 "Check WO": create if missing, then ENFORCE config to cloud values.
--      (Prod may already have CP20 but unwired/mis-configured - enforce corrects it.)
INSERT INTO wfl_checkpoint
  (checkpoint_id, flow_id, checkpoint_desc, checkpoint_type, checkpoint_claim_type,
   checkpoint_due_day, checkpoint_next, checkpoint_case_1, checkpoint_case_2, checkpoint_case_3,
   checkpoint_icon, role_id, group_id, checkpoint_order, checkpoint_color, checkpoint_skip)
SELECT 20, 2, 'Check WO', 2, 3, 1, 14, 16, 13, NULL, 'fas fa-check', 7, 1, 5, NULL, b'0'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM wfl_checkpoint WHERE checkpoint_id = 20);
UPDATE wfl_checkpoint SET flow_id=2, checkpoint_desc='Check WO', checkpoint_type=2, checkpoint_claim_type=3,
   checkpoint_due_day=1, checkpoint_next=14, checkpoint_case_1=16, checkpoint_case_2=13, checkpoint_case_3=NULL,
   role_id=7, group_id=1, checkpoint_order=5, checkpoint_skip=b'0'
WHERE checkpoint_id=20;

-- 1.2  CP 13 "Execute WO": route to Check WO (next=20), allow return-to-self (case_3=13).
UPDATE wfl_checkpoint SET checkpoint_next = 20, checkpoint_case_3 = 13
WHERE checkpoint_id = 13 AND flow_id = 2;

-- 1.3  CP 19 "Verify WR": owned by WO Assigner (role 7, group 1).
UPDATE wfl_checkpoint SET role_id = 7, group_id = 1
WHERE checkpoint_id = 19 AND flow_id = 2;

-- 1.4  Add the 4 missing pre-assignment rules.
INSERT INTO wfl_checkpoint_assign (checkpoint_assign_type, checkpoint_id, checkpoint_to)
SELECT v.t, v.f, v.o
FROM (        SELECT 1 AS t, 12 AS f, 20 AS o
        UNION SELECT 1, 17, 16
        UNION SELECT 1, 17, 19
        UNION SELECT 1, 17, 20 ) v
WHERE NOT EXISTS (SELECT 1 FROM wfl_checkpoint_assign a
  WHERE a.checkpoint_assign_type = v.t AND a.checkpoint_id = v.f AND a.checkpoint_to = v.o);

-- 1.5  Remove the buggy rule (Submit Complaint -> Verify WR) that mis-assigns the complainer.
DELETE FROM wfl_checkpoint_assign WHERE checkpoint_id = 11 AND checkpoint_to = 19;

-- 1.6  CP 20 performers = JKR's own "Assign WO" (CP 12) users.
INSERT INTO wfl_checkpoint_user (checkpoint_id, user_id, role_id, group_id)
SELECT 20, cu.user_id, cu.role_id, cu.group_id
FROM wfl_checkpoint_user cu
WHERE cu.checkpoint_id = 12
  AND NOT EXISTS (SELECT 1 FROM wfl_checkpoint_user x
    WHERE x.checkpoint_id = 20 AND x.user_id = cu.user_id AND x.role_id = cu.role_id AND x.group_id <=> cu.group_id);

-- 1.7  CP 19 role-7 performers = JKR's own "Assign WR" (CP 17) users (legacy role-6 rows left as-is).
INSERT INTO wfl_checkpoint_user (checkpoint_id, user_id, role_id, group_id)
SELECT 19, cu.user_id, cu.role_id, cu.group_id
FROM wfl_checkpoint_user cu
WHERE cu.checkpoint_id = 17
  AND NOT EXISTS (SELECT 1 FROM wfl_checkpoint_user x
    WHERE x.checkpoint_id = 19 AND x.user_id = cu.user_id AND x.role_id = cu.role_id AND x.group_id <=> cu.group_id);

-- 1.8  IN-FLIGHT: create the missing CP 20 assignment for type-1 WOs sitting at Execute WO.
--      Assignee derived from the WO's own assigner: CP16 assign -> latest CP12 task -> wo_task_assigned_by.
INSERT INTO wfl_task_assign (transaction_id, checkpoint_id, role_id, group_id, user_id)
SELECT s.transaction_id, 20, 7, s.group_id, s.user_id
FROM (
  SELECT t.transaction_id,
    COALESCE(
      (SELECT a.group_id FROM wfl_task_assign a WHERE a.transaction_id=t.transaction_id AND a.checkpoint_id=16 ORDER BY a.task_assign_id DESC LIMIT 1),
      (SELECT c.group_id      FROM wfl_task c WHERE c.transaction_id=t.transaction_id AND c.checkpoint_id=12 ORDER BY c.task_id DESC LIMIT 1),
      t.group_id, 1) AS group_id,
    COALESCE(
      (SELECT a.user_id FROM wfl_task_assign a WHERE a.transaction_id=t.transaction_id AND a.checkpoint_id=16 ORDER BY a.task_assign_id DESC LIMIT 1),
      (SELECT c.task_claimed_user FROM wfl_task c WHERE c.transaction_id=t.transaction_id AND c.checkpoint_id=12 ORDER BY c.task_id DESC LIMIT 1),
      w.wo_task_assigned_by) AS user_id
  FROM wfl_task t
  JOIN wfl_transaction tr ON tr.transaction_id=t.transaction_id AND tr.flow_id=2
  JOIN wo_task w          ON w.transaction_id=t.transaction_id AND w.wo_task_type_init NOT IN ('2','6')
  WHERE t.task_current=1 AND t.checkpoint_id=13
    AND NOT EXISTS (SELECT 1 FROM wfl_task_assign x WHERE x.transaction_id=t.transaction_id AND x.checkpoint_id=20)
) s
WHERE s.user_id IS NOT NULL;

-- 1.9  IN-FLIGHT: re-point any "Verify WR" (CP19) tasks from the complainer to the real CP17 assigner.
UPDATE wfl_task ct
JOIN (SELECT x.transaction_id, x.task_claimed_user AS assigner, x.group_id FROM wfl_task x
      JOIN (SELECT transaction_id, MAX(task_id) mid FROM wfl_task WHERE checkpoint_id=17 GROUP BY transaction_id) m
        ON m.transaction_id=x.transaction_id AND m.mid=x.task_id) c17 ON c17.transaction_id=ct.transaction_id
SET ct.task_claimed_user=c17.assigner, ct.group_id=COALESCE(c17.group_id,1), ct.role_id=7
WHERE ct.task_current=1 AND ct.checkpoint_id=19 AND ct.task_status=8
  AND c17.assigner IS NOT NULL AND (ct.task_claimed_user IS NULL OR ct.task_claimed_user<>c17.assigner);

UPDATE wfl_task_assign ta
JOIN (SELECT x.transaction_id, x.task_claimed_user AS assigner, x.group_id FROM wfl_task x
      JOIN (SELECT transaction_id, MAX(task_id) mid FROM wfl_task WHERE checkpoint_id=17 GROUP BY transaction_id) m
        ON m.transaction_id=x.transaction_id AND m.mid=x.task_id) c17 ON c17.transaction_id=ta.transaction_id
SET ta.user_id=c17.assigner, ta.group_id=COALESCE(c17.group_id,1), ta.role_id=7
WHERE ta.checkpoint_id=19 AND c17.assigner IS NOT NULL
  AND ta.transaction_id IN (SELECT transaction_id FROM wfl_task WHERE task_current=1 AND checkpoint_id=19 AND task_status=8)
  AND (ta.user_id IS NULL OR ta.user_id<>c17.assigner);

-- ############################################################################
-- SECTION 2 : ROLES  (ref_role parity - create all roles missing in JKR: 23-27)
-- ############################################################################
-- 23 & 27 are required by the MR flow (Section 3). 24-26 are PTW-module roles,
-- added for full ref_role parity. Explicit ids + types match cloud exactly.
INSERT INTO ref_role (role_id, role_desc, role_type, role_status)
SELECT v.id, v.d, v.t, 1
FROM (        SELECT 23 AS id, 'Standalone Material Requestor' AS d, 2 AS t
        UNION SELECT 24, 'PTW Supervisor',       2
        UNION SELECT 25, 'PTW SHE',              2
        UNION SELECT 26, 'PTW Facility Manager', 2
        UNION SELECT 27, 'MR Reviewer',          1 ) v
WHERE NOT EXISTS (SELECT 1 FROM ref_role r WHERE r.role_id = v.id);

-- ############################################################################
-- SECTION 3 : MATERIAL REQUEST  (wfl_flow.flow_id = 4)
-- ############################################################################

-- 3.1  CP 56 "MR Reviewer": create if missing, then ENFORCE config to cloud values.
INSERT INTO wfl_checkpoint
  (checkpoint_id, flow_id, checkpoint_desc, checkpoint_type, checkpoint_claim_type,
   checkpoint_due_day, checkpoint_next, checkpoint_case_1, checkpoint_case_2, checkpoint_case_3,
   checkpoint_icon, role_id, group_id, checkpoint_order, checkpoint_color, checkpoint_skip)
SELECT 56, 4, 'MR Reviewer', 2, 1, NULL, 42, 45, NULL, NULL, NULL, 27, NULL, 2, NULL, b'0'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM wfl_checkpoint WHERE checkpoint_id = 56);
UPDATE wfl_checkpoint SET flow_id=4, checkpoint_desc='MR Reviewer', checkpoint_type=2, checkpoint_claim_type=1,
   checkpoint_due_day=NULL, checkpoint_next=42, checkpoint_case_1=45, checkpoint_case_2=NULL, checkpoint_case_3=NULL,
   role_id=27, group_id=NULL, checkpoint_order=2, checkpoint_skip=b'0'
WHERE checkpoint_id=56;

-- 3.2  CP 46 "Request Material Standalone": create if missing, then ENFORCE config.
INSERT INTO wfl_checkpoint
  (checkpoint_id, flow_id, checkpoint_desc, checkpoint_type, checkpoint_claim_type,
   checkpoint_due_day, checkpoint_next, checkpoint_case_1, checkpoint_case_2, checkpoint_case_3,
   checkpoint_icon, role_id, group_id, checkpoint_order, checkpoint_color, checkpoint_skip)
SELECT 46, 4, 'Request Material Standalone', 1, 1, 1, 42, NULL, NULL, NULL, 'fas fa-shopping-cart', 23, 1, 1, NULL, b'0'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM wfl_checkpoint WHERE checkpoint_id = 46);
UPDATE wfl_checkpoint SET flow_id=4, checkpoint_desc='Request Material Standalone', checkpoint_type=1, checkpoint_claim_type=1,
   checkpoint_due_day=1, checkpoint_next=42, checkpoint_case_1=NULL, checkpoint_case_2=NULL, checkpoint_case_3=NULL,
   role_id=23, group_id=1, checkpoint_order=1, checkpoint_skip=b'0'
WHERE checkpoint_id=46;

-- 3.3  CP 45 "Request Rejected": display order 5 -> 4 (matches cloud).
UPDATE wfl_checkpoint SET checkpoint_order = 4 WHERE checkpoint_id = 45 AND flow_id = 4;

-- 3.4  CP 56 reviewer pool = JKR's own role-27 users (0 until reviewers are assigned).
INSERT INTO wfl_checkpoint_user (checkpoint_id, user_id, role_id, group_id)
SELECT 56, ur.user_id, 27, COALESCE(ug.group_id, 1)
FROM sys_user_role ur
LEFT JOIN sys_user_group ug ON ug.user_id = ur.user_id
WHERE ur.role_id = 27
  AND NOT EXISTS (SELECT 1 FROM wfl_checkpoint_user x WHERE x.checkpoint_id = 56 AND x.user_id = ur.user_id AND x.role_id = 27);

-- 3.5  ENABLE THE MR REVIEWER GATE  *** DISABLED ON PURPOSE ***
--      Uncomment ONLY after at least one user holds role 27, else new MRs stall at CP56.
-- UPDATE wfl_checkpoint SET checkpoint_next = 56 WHERE checkpoint_id = 41 AND flow_id = 4;

COMMIT;
-- ROLLBACK;  -- use instead of COMMIT to abort after reviewing (when run interactively)

-- ============================================================================
-- VERIFICATION (run after COMMIT) — every row should read OK
-- ============================================================================
SELECT 'WO: CP20 exists'                  AS check_name, IF(COUNT(*)=1,'OK','FAIL') AS result FROM wfl_checkpoint WHERE checkpoint_id=20 AND flow_id=2
UNION ALL SELECT 'WO: CP13 next=20 & case_3=13', IF(COUNT(*)=1,'OK','FAIL') FROM wfl_checkpoint WHERE checkpoint_id=13 AND checkpoint_next=20 AND checkpoint_case_3=13
UNION ALL SELECT 'WO: CP19 role=7 & group=1',     IF(COUNT(*)=1,'OK','FAIL') FROM wfl_checkpoint WHERE checkpoint_id=19 AND role_id=7 AND group_id=1
UNION ALL SELECT 'WO: 4 assign rules present',     IF(COUNT(*)=4,'OK',CONCAT('FAIL ',COUNT(*),'/4')) FROM wfl_checkpoint_assign WHERE (checkpoint_id,checkpoint_to) IN ((12,20),(17,16),(17,19),(17,20))
UNION ALL SELECT 'WO: buggy 11->19 removed',       IF(COUNT(*)=0,'OK','FAIL') FROM wfl_checkpoint_assign WHERE checkpoint_id=11 AND checkpoint_to=19
UNION ALL SELECT 'WO: CP20 performers present',    IF(COUNT(*)>0,'OK','FAIL') FROM wfl_checkpoint_user WHERE checkpoint_id=20
UNION ALL SELECT 'WO: CP19 role-7 performers',     IF(COUNT(*)>0,'OK','FAIL') FROM wfl_checkpoint_user WHERE checkpoint_id=19 AND role_id=7
UNION ALL SELECT 'WO: in-flight CP13 all have CP20 assign', IF(COUNT(*)=0,'OK',CONCAT('FAIL ',COUNT(*))) FROM wfl_task t
   JOIN wfl_transaction tr ON tr.transaction_id=t.transaction_id AND tr.flow_id=2
   JOIN wo_task w ON w.transaction_id=t.transaction_id AND w.wo_task_type_init NOT IN ('2','6')
   WHERE t.task_current=1 AND t.checkpoint_id=13 AND NOT EXISTS (SELECT 1 FROM wfl_task_assign x WHERE x.transaction_id=t.transaction_id AND x.checkpoint_id=20)
UNION ALL SELECT 'ROLES: 23-27 all exist',         IF(COUNT(*)=5,'OK',CONCAT('FAIL ',COUNT(*),'/5')) FROM ref_role WHERE role_id BETWEEN 23 AND 27
UNION ALL SELECT 'MR: CP56 exists',                IF(COUNT(*)=1,'OK','FAIL') FROM wfl_checkpoint WHERE checkpoint_id=56 AND flow_id=4
UNION ALL SELECT 'MR: CP46 exists',                IF(COUNT(*)=1,'OK','FAIL') FROM wfl_checkpoint WHERE checkpoint_id=46 AND flow_id=4
UNION ALL SELECT 'MR: CP45 order=4',               IF(COUNT(*)=1,'OK','FAIL') FROM wfl_checkpoint WHERE checkpoint_id=45 AND checkpoint_order=4
UNION ALL SELECT 'MR: reviewer pool (0 = assign reviewers later)', CONCAT(COUNT(*),' user(s)') FROM wfl_checkpoint_user WHERE checkpoint_id=56
UNION ALL SELECT 'MR: gate OFF (CP41 next=42)',    IF((SELECT checkpoint_next FROM wfl_checkpoint WHERE checkpoint_id=41)=42,'OK (off)','ON (56)');
