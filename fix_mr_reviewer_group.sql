-- Fix: allow MR Reviewer to work for site users
-- Problem: MR Reviewer checkpoint (flow_id=4, checkpoint_desc='MR Reviewer') was created with group_id=1.
-- Site users (userType=2) have group_id = cli_site.group_id, so their wfl_checkpoint_user rows won't match group_id=1.
-- Result: MR Reviewer does not see tasks and cannot submit recommend/not-recommend.
--
-- This fix makes the MR Reviewer checkpoint group-agnostic (group_id = NULL).
-- Combined with the code change in api/function/f_task.php (inherit current group when next group_id is NULL),
-- the reviewer task will stay within the WO's site group.

START TRANSACTION;

UPDATE wfl_checkpoint
SET group_id = NULL
WHERE flow_id = 4 AND checkpoint_desc = 'MR Reviewer';

COMMIT;
