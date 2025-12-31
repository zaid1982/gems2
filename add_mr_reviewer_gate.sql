-- Add MR Reviewer gate for WO material request workflow (flow_id=4)
-- Goal: Technician submit -> MR Reviewer (recommend / not recommend) -> existing Manager/Supervisor approval (checkpoint_id=42)
-- Notes:
-- - This assumes existing flow_id=4 and existing manager checkpoint_id=42 remain unchanged.
-- - Reviewer role is created by name (role_desc='MR Reviewer').
-- - Reviewer checkpoint is created by name (checkpoint_desc='MR Reviewer') under flow_id=4.
-- - Start checkpoint (flow 4, type=1, role_id=8) is rewired to point to reviewer checkpoint.

START TRANSACTION;

-- 1) Ensure role exists
INSERT INTO ref_role (role_desc, role_type, role_status)
SELECT 'MR Reviewer', 1, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ref_role WHERE role_desc = 'MR Reviewer');

SET @mr_reviewer_role_id := (SELECT role_id FROM ref_role WHERE role_desc = 'MR Reviewer' LIMIT 1);

-- 2) Ensure flow has an end checkpoint (type=3) for case routing
SET @mr_end_checkpoint_id := (
  SELECT checkpoint_id FROM wfl_checkpoint
  WHERE flow_id = 4 AND checkpoint_type = 3
  ORDER BY checkpoint_id DESC
  LIMIT 1
);

INSERT INTO wfl_checkpoint (
  flow_id,
  checkpoint_desc,
  checkpoint_type,
  checkpoint_claim_type,
  checkpoint_due_day,
  checkpoint_next,
  checkpoint_icon,
  role_id,
  group_id,
  checkpoint_order,
  checkpoint_color,
  checkpoint_skip
)
SELECT
  4,
  'MR End',
  3,
  1,
  NULL,
  NULL,
  NULL,
  8,
  1,
  99,
  NULL,
  b'0'
FROM DUAL
WHERE @mr_end_checkpoint_id IS NULL;

SET @mr_end_checkpoint_id := (
  SELECT checkpoint_id FROM wfl_checkpoint
  WHERE flow_id = 4 AND checkpoint_type = 3
  ORDER BY checkpoint_id DESC
  LIMIT 1
);

-- 3) Create reviewer checkpoint (normal) if missing
INSERT INTO wfl_checkpoint (
  flow_id,
  checkpoint_desc,
  checkpoint_type,
  checkpoint_claim_type,
  checkpoint_due_day,
  checkpoint_next,
  checkpoint_case_1,
  checkpoint_icon,
  role_id,
  group_id,
  checkpoint_order,
  checkpoint_color,
  checkpoint_skip
)
SELECT
  4,
  'MR Reviewer',
  2,
  1,
  NULL,
  42,
  @mr_end_checkpoint_id,
  NULL,
  @mr_reviewer_role_id,
  1,
  2,
  NULL,
  b'0'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM wfl_checkpoint
  WHERE flow_id = 4 AND checkpoint_desc = 'MR Reviewer'
);

SET @mr_reviewer_checkpoint_id := (
  SELECT checkpoint_id FROM wfl_checkpoint
  WHERE flow_id = 4 AND checkpoint_desc = 'MR Reviewer'
  ORDER BY checkpoint_id DESC
  LIMIT 1
);

-- Ensure routing stays correct (idempotent)
UPDATE wfl_checkpoint
SET checkpoint_next = 42,
    checkpoint_case_1 = @mr_end_checkpoint_id
WHERE checkpoint_id = @mr_reviewer_checkpoint_id;

-- 4) Rewire flow start checkpoint (Technician) to reviewer
UPDATE wfl_checkpoint
SET checkpoint_next = @mr_reviewer_checkpoint_id
WHERE flow_id = 4 AND checkpoint_type = 1 AND role_id = 8;

COMMIT;
