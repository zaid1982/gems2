-- ============================================================================
-- SYNC MATERIAL REQUEST WORKFLOW: gfm_jkr  ->  match gems (Global / cloud)
-- ============================================================================
-- Purpose : Bring the Material Request workflow (wfl_flow.flow_id = 4) in the
--           JKR database (gfm_jkr @ localhost) in line with gems Global
--           (gems @ www.metadatasystem.my), the source of truth.
--
-- Generated from a READ-ONLY comparison of both live databases on 2026-05-29.
--
-- CONTEXT
-- ------------------------------------------------------------
--   Of the 5 workflow flows, only MR (flow 4) still differs:
--     PPM (1), Purchase Request (3), FCA (5)  -> already identical
--     Work Order (2)                          -> already synced separately
--     Material Request (4)                     -> THIS SCRIPT
--
-- DIFFERENCES (Global = expected, JKR = current) for flow_id = 4:
--   ref_role (ALL roles missing in JKR vs cloud - created in Part A for parity):
--     role 23 "Standalone Material Requestor" (type 2)   [MR flow - used below]
--     role 24 "PTW Supervisor"                (type 2)   [PTW module]
--     role 25 "PTW SHE"                       (type 2)   [PTW module]
--     role 26 "PTW Facility Manager"          (type 2)   [PTW module]
--     role 27 "MR Reviewer"                   (type 1)   [MR flow - used below]
--   wfl_checkpoint:
--     CP 56 "MR Reviewer"                : MISSING in JKR -> INSERT (Part B.1)
--     CP 46 "Request Material Standalone": MISSING in JKR -> INSERT (Part B.2)
--     CP 45 "Request Rejected"           : order 5 -> 4   -> UPDATE (Part B.3)
--     CP 41 "Request Material"           : next 42 -> 56  -> UPDATE (Part D, GATED)
--   wfl_checkpoint_user:
--     CP 56 needs MR-Reviewer (role 27) performers        -> Part C
--
-- !!! IMPORTANT - READ BEFORE RUNNING !!!
-- ------------------------------------------------------------
--   The MR Reviewer step (CP 56) is a GATE that every new material request
--   passes through once CP 41 routes to it (Part D). It uses claim_type=1, so a
--   user holding role 27 (MR Reviewer) must exist or new MRs will STALL at CP 56.
--
--   JKR currently has 0 users with role 27. Therefore this script:
--     * Parts A/B/C  : create the roles, checkpoints and (empty) reviewer pool
--                      -> 100% SAFE, additive, changes NO existing behaviour.
--     * Part D       : rewires CP 41 -> CP 56 to actually ENABLE the gate.
--                      -> LEFT COMMENTED OUT. Only enable AFTER assigning at
--                         least one MR Reviewer (give a user role_id = 27).
--
--   No in-flight impact: there are currently 0 MR tasks sitting at CP 41, so the
--   rewire only affects NEW material requests (existing CP 42/43 tasks are fine).
--
-- SAFETY: single transaction, idempotent (guards on every statement), no schema
--   changes. Verification block prints at the end.
-- ============================================================================

START TRANSACTION;

-- ============================================================================
-- PART A : ref_role parity - create ALL roles missing in JKR vs cloud (23-27)
-- ----------------------------------------------------------------------------
-- 23 & 27 are required by the MR flow in Parts B-D below. 24-26 are PTW-module
-- roles, included for full ref_role parity (per sync decision). Creating the
-- PTW roles only achieves ref_role parity - the PTW MODULE itself (menus,
-- permissions, ptw_* tables) is a separate sync (see sql/add_ptw_*.sql and
-- sql/ptw_database_setup_clean.sql). Explicit IDs + types match cloud exactly.
-- ============================================================================
INSERT INTO ref_role (role_id, role_desc, role_type, role_status)
SELECT v.id, v.d, v.t, 1
FROM (
        SELECT 23 AS id, 'Standalone Material Requestor' AS d, 2 AS t   -- MR flow
  UNION SELECT 24,       'PTW Supervisor',                    2          -- PTW module
  UNION SELECT 25,       'PTW SHE',                           2          -- PTW module
  UNION SELECT 26,       'PTW Facility Manager',              2          -- PTW module
  UNION SELECT 27,       'MR Reviewer',                       1          -- MR flow
) v
WHERE NOT EXISTS (SELECT 1 FROM ref_role r WHERE r.role_id = v.id);

-- ============================================================================
-- PART B : wfl_checkpoint (configuration)
-- ============================================================================

-- B.1  CP 56 "MR Reviewer" (intermediate; recommend -> CP42, reject -> CP45).
INSERT INTO wfl_checkpoint
  (checkpoint_id, flow_id, checkpoint_desc, checkpoint_type, checkpoint_claim_type,
   checkpoint_due_day, checkpoint_next, checkpoint_case_1, checkpoint_case_2, checkpoint_case_3,
   checkpoint_icon, role_id, group_id, checkpoint_order, checkpoint_color, checkpoint_skip)
SELECT 56, 4, 'MR Reviewer', 2, 1,
       NULL, 42, 45, NULL, NULL,
       NULL, 27, NULL, 2, NULL, b'0'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wfl_checkpoint WHERE checkpoint_id = 56);

-- B.2  CP 46 "Request Material Standalone" (alternate start point; role 23).
--      Harmless until a role-23 user exists - it is just an extra entry point.
INSERT INTO wfl_checkpoint
  (checkpoint_id, flow_id, checkpoint_desc, checkpoint_type, checkpoint_claim_type,
   checkpoint_due_day, checkpoint_next, checkpoint_case_1, checkpoint_case_2, checkpoint_case_3,
   checkpoint_icon, role_id, group_id, checkpoint_order, checkpoint_color, checkpoint_skip)
SELECT 46, 4, 'Request Material Standalone', 1, 1,
       1, 42, NULL, NULL, NULL,
       'fas fa-shopping-cart', 23, 1, 1, NULL, b'0'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wfl_checkpoint WHERE checkpoint_id = 46);

-- B.3  CP 45 "Request Rejected": display order 5 -> 4 (cosmetic, matches cloud).
UPDATE wfl_checkpoint
SET checkpoint_order = 4
WHERE checkpoint_id = 45 AND flow_id = 4;

-- ============================================================================
-- PART C : wfl_checkpoint_user  (MR Reviewer pool - JKR's OWN role-27 users)
-- ----------------------------------------------------------------------------
-- Populates CP 56 from whoever holds role 27 in JKR. NOTE: this inserts 0 rows
-- until you assign the MR Reviewer role to at least one user. Re-run this script
-- (or just this statement) after assigning reviewers - it is idempotent.
-- ============================================================================
INSERT INTO wfl_checkpoint_user (checkpoint_id, user_id, role_id, group_id)
SELECT 56, ur.user_id, 27, COALESCE(ug.group_id, 1)
FROM sys_user_role ur
LEFT JOIN sys_user_group ug ON ug.user_id = ur.user_id
WHERE ur.role_id = 27
  AND NOT EXISTS (
    SELECT 1 FROM wfl_checkpoint_user x
    WHERE x.checkpoint_id = 56 AND x.user_id = ur.user_id AND x.role_id = 27
  );

-- ============================================================================
-- PART D : ENABLE THE GATE  (rewire CP 41 -> CP 56)   *** DISABLED ***
-- ----------------------------------------------------------------------------
-- Uncomment ONLY after at least one user holds role 27 (MR Reviewer), otherwise
-- every new material request will stall at CP 56 with no one able to action it.
-- Verify first:  SELECT COUNT(*) FROM wfl_checkpoint_user WHERE checkpoint_id = 56;
-- ----------------------------------------------------------------------------
-- UPDATE wfl_checkpoint SET checkpoint_next = 56 WHERE checkpoint_id = 41 AND flow_id = 4;

COMMIT;
-- ROLLBACK;  -- use instead of COMMIT to abort after reviewing verification

-- ============================================================================
-- VERIFICATION (run after COMMIT)
-- ============================================================================
SELECT 'roles 23-27 all exist (ref_role parity)' AS check_name,
       IF(COUNT(*)=5,'OK',CONCAT('FAIL (',COUNT(*),'/5)')) AS result FROM ref_role WHERE role_id BETWEEN 23 AND 27
UNION ALL SELECT 'CP56 MR Reviewer exists', IF(COUNT(*)=1,'OK','FAIL') FROM wfl_checkpoint WHERE checkpoint_id=56 AND flow_id=4
UNION ALL SELECT 'CP46 Standalone exists', IF(COUNT(*)=1,'OK','FAIL') FROM wfl_checkpoint WHERE checkpoint_id=46 AND flow_id=4
UNION ALL SELECT 'CP45 order = 4', IF(COUNT(*)=1,'OK','FAIL') FROM wfl_checkpoint WHERE checkpoint_id=45 AND checkpoint_order=4
UNION ALL SELECT 'CP56 reviewer pool size (0 = assign reviewers!)', CONCAT(COUNT(*),' user(s)') FROM wfl_checkpoint_user WHERE checkpoint_id=56
UNION ALL SELECT 'gate enabled (CP41->56)?', IF((SELECT checkpoint_next FROM wfl_checkpoint WHERE checkpoint_id=41)=56,'YES','no (still 42)');
