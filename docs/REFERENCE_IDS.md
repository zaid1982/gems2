# Magic ID reference map

The legacy code passes many bare numeric strings around (`save_audit('136', ...)`,
`create_new_task('2', ...)`, `submit_task($id, $user, '10', ...)`, status `'24'`,
checkpoint `'13'`, ...). This document is the working map used to replace those
magic values with named typed constants under `Gfm\Domain\*`.

Rule of thumb: confirm every value against the corresponding DB reference table
before adding it to a constants class. Do not guess.

## Categories and where to confirm them

| Category | Source of truth (DB) | Constants class |
| --- | --- | --- |
| Audit actions | audit reference table (`save_audit` arg) | `Gfm\Domain\AuditAction` |
| Task status | `ref_status` | TODO `Gfm\Domain\TaskStatus` |
| Roles | `ref_role` | TODO `Gfm\Domain\Role` |
| Task types | task type ref table | TODO `Gfm\Domain\TaskType` |
| Workflow checkpoints | `wfl_checkpoint` | TODO `Gfm\Domain\Checkpoint` |
| Exception/result codes | code-level (not DB) | `Gfm\Domain\ErrorCode` (done) |

## Confirmed so far

### Audit actions (`Gfm\Domain\AuditAction`)

| ID | Name | Evidence |
| --- | --- | --- |
| 1 | LOGIN | `m_login.php`/`login.php` after successful login |
| 4 | FORGOT_PASSWORD | `*login.php` forgot_password action |
| 103 | RESET_PASSWORD | `m_login.php` reset_password action |
| 124 | WO_DELETE | `wo.php` DELETE -> SUC_WO_DELETE |
| 125 | WO_MANUAL_REPORT_ADD | `wo.php` insert_site_manual -> SUC_WO_MANUAL_REPORT_ADD |
| 126 | WO_MANUAL_REPORT_EDIT | `wo.php` update_site_manual -> SUC_WO_MANUAL_REPORT_EDIT |
| 136 | WO_HELPDESK_ASSIGN | `wo.php` submit_helpdesk_complaint assignment |

### Error/result codes (`Gfm\Domain\ErrorCode`)

| Code | Name | Evidence |
| --- | --- | --- |
| 0 | INTERNAL | default; generic message shown |
| 30 | NOT_FOUND | `db_*` throwEmpty = 2 |
| 31 | USER_FACING | login/validation errors surfaced as `errmsg` |
| 32 | BLOCKED | login lockout / wrong password |

## To do

Populate `TaskStatus`, `Role`, `TaskType`, `Checkpoint` from the reference
tables (e.g. `SELECT * FROM ref_status;`) and migrate call sites incrementally,
one domain at a time, verifying with the smoke tests after each batch.
