# Inventory Reviewer Status Migration
_Date: 12 Dec 2025_

Objective: introduce reviewer-specific workflow states without breaking the existing MR → Admin → Storekeeper chain. Use this checklist before touching PHP or database logic.

## 1. New Status Codes
| ID | Token | Applies To | Meaning |
|----|-------|-----------|---------|
| 59 | AWAITING_REVIEWER | `wo_task_request.status`, `wo_task_parts.status`, `wfl_transaction.transaction_status` | Request submitted by technician, pending reviewer decision. Replaces the old jump from `32 → 33`.
| 60 | REVIEWER_APPROVED | Same tables | Reviewer completed review with a positive recommendation; request can now flow to Inventory Admin.
| 61 | REVIEWER_REJECTED | Same tables | Reviewer rejected the request (differentiate from existing admin reject `50`).

> IDs 59/60/61 are unused according to `ref_status`, so they give us clean reviewer-only codes without overwriting legacy meanings (see `docs/mr_status_inventory.md`).

## 2. Database Scripts
Sequence these statements inside a single transaction (replace `__` placeholders with actual descriptions).

```sql
START TRANSACTION;

INSERT INTO ref_status (status_id, status_desc, status_action, status_color, status_color_code)
VALUES
  (59, 'Awaiting Reviewer', 'Review', 'orange darken-3', '#e46f0a'),
  (60, 'Reviewer Approved', 'Forward', 'green darken-1', '#2e7d32'),
  (61, 'Reviewer Rejected', 'Reject', 'red darken-2', '#c62828')
ON DUPLICATE KEY UPDATE
  status_desc = VALUES(status_desc),
  status_action = VALUES(status_action),
  status_color = VALUES(status_color),
  status_color_code = VALUES(status_color_code);

COMMIT;
```

If the environment uses seed SQL files (e.g., `maintenance/create_views.sql` or `create_missing_sys_tables.sql`), append the same inserts there for new deployments.

## 3. PHP Enum / Constant Updates
1. **`api/class/Constant.php` or related enum files** – expose the human-readable labels for the new codes (e.g., `public static $woRequestStatus` map).
2. **`api/function/f_wo_request.php`** – replace hardcoded checks (`'33'`, `'34'`, etc.) with named constants and extend logic:
   - `submitRequest()` should set request/part/transaction status to `'35'` instead of `'33'`.
   - `checkRequestTask('approve_request')` must accept `'37'` as the valid status before admin approval.
   - Reviewer reject path sets `'52'` and blocks `resetRequest()` unless admin explicitly reopens.
3. **Views / mobile payloads** (`vw_wo_request_task_m`, `vw_wo_task_parts_mobile`, etc.) – include the new statuses in CASE expressions so legacy rows remain readable.

## 4. Workflow Transition Matrix
```
32 (draft) → submitRequest() → 59 (awaiting reviewer)
59 --(reviewer approve)--> 60 (reviewer approved)
59 --(reviewer reject)--> 61 (reviewer rejected)
60 --(admin approve)--> 34 (approved waiting store)
60 --(admin reject)--> 50 (admin rejected)
34 → reserve_request() → 38
38 → check_out_request() → 36
```
- Storekeeper actions (`reserve_request`, `check_out_request`) must gate on `>=34` (i.e., cannot run if status in {35, 37}).
- Admin approval endpoint (`PUT /wo_request/approve_request/{id}`) must verify the current status is `37` and that reviewer metadata indicates a "recommended" decision.

## 5. Data Backfill
For existing in-flight requests once the feature goes live:
1. Identify rows currently in status `33`. If reviewer review is required, migrate them to `35` and create reviewer tasks; otherwise leave them at `33`.
2. Any rows already approved should remain untouched (>=34).
3. Update `wo_task_parts` and `wfl_transaction` to match the chosen status for each `wo_task_request_id` to keep the triad synchronized.

## 6. Verification Checklist
- `SELECT DISTINCT wo_task_request_status FROM wo_task_request` only returns the known codes {32,35,37,33,34,38,36,50,52}.
- Mobile list endpoints gracefully map `35` and `37` to friendly strings.
- Admin queue filters exclude rows still in `35` unless the user is a reviewer.
- Returning technicians (`material_returns`) still see their tickets once the request reaches `36`.

Use this document as the authoritative reference when scripting migrations or reviewing PRs tied to the reviewer rollout.
