# Material Request Status Inventory
_Date: 12 Dec 2025_

This note documents every status value currently used by the Material Request + Material Return flows so we can safely insert the Inventory Reviewer gate without breaking existing behaviour.

## 1. WO Request / WO Parts / Transaction Statuses
`wo_task_request.wo_task_request_status`, `wo_task_parts.wo_task_parts_status`, and `wfl_transaction.transaction_status` move in lock-step. The current IDs and transitions are inferred from `api/function/f_wo_request.php`.

| ID | Working Label | Description / Trigger | Key Code Paths | Next States |
|----|----------------|-----------------------|----------------|-------------|
| 32 | draft / building list | Technician is assembling items. Created by `addWoPartsMobile()` and `resetRequest()`. Parts still editable. | `checkRequestTask('submit_request')` pulls this row before submission. | 33 when `submitRequest()` succeeds, or stays 32 while editing. |
| 33 | pending_admin | Admin approval queue. `submitRequest()` sets the status while `wfl_task` checkpoint 42 is active. | `checkRequestTask('approve_request'/'reject_request')` requires status 33. | 34 on approve, 50 on reject. |
| 34 | approved_waiting_store | Admin approved, waiting for storekeeper to reserve. | `submitApprove()` sets 34 and pushes checkpoint 43. `reserve_request` validation expects this state. | 38 once `submitReserve()` locks stock. |
| 38 | reserved / locked | Storekeeper reserved serialised stock. `ast_part_sub` rows move to status 51. | `submitReserve()` applies this state; `checkRequestTask('check_out_request')` verifies 38. | 36 after checkout. |
| 36 | collected | Parts handed to technician. Inventory count decremented, `ast_part_sub` set to 36. | `submitCheckOutRequest()` triggers it. | Downstream flows (returns) operate from here; no further status change except archival. |
| 50 | rejected | Admin rejected the request, remark stored. | `submitReject()` sets 50. | `resetRequest()` clones a new row in status 32 if technician needs to resubmit. |

**Observations**
- IDs 35, 37, 39, etc. are unused in this workflow, so we can safely introduce reviewer-specific states without touching the legacy ones.
- Mobile apps rely on these numeric codes (e.g., `vw_check_out_mobile_list` filters by 36), so new states must be additive with backward-compatible filters.

## 2. `wfl_task` Checkpoints / Roles
| Checkpoint ID | Role Expectation | Usage |
|---------------|------------------|-------|
| 42 | Role 17 (Inventory Admin) | Approval stage (`approve_request` / `reject_request`).
| 43 | Role 16 (Storekeeper) | Reserve + Check-out stages (`reserve_request`, `check_out_request`).

Reviewer gate will require either a new checkpoint or a pre-check inside checkpoint 42 before the current task advances.

## 3. `ast_part_sub.part_sub_status`
Serialised inventory pieces use the following states (see `submitReserve`, `submitCheckOutRequest`, `returnCollectedParts`, `verifyReturnTicket`).

| ID | Meaning | Entry Point |
|----|---------|-------------|
| 46 | Available in store | Default stock; `submitReserve()` selects rows in this state. |
| 51 | Reserved / locked | Applied during `submitReserve()` while awaiting checkout. |
| 36 | Collected / with technician | Set during `submitCheckOutRequest()`. |
| 37 | Pending return verification | Set by `returnCollectedParts()` when technician submits a return payload.
| 47 | Return auto-confirmed | Used by legacy `confirmReturnReceipt()` path (bypasses manual verification).
| 38 | Return rejected | `verifyReturnTicket()` sets 38 for items the storekeeper rejects; keeps them linked to the ticket for auditing.

Approved returns move back to 46 with cleared `wo_task_parts_id`, meaning the serial is back in stock.

## 4. `material_returns.return_status`
| Value | Meaning | Notes |
|-------|---------|-------|
| `pending` | Waiting for storekeeper verification. Tickets are surfaced in `vw_storekeeper_pending_returns`. |
| `completed` | All serials within the ticket processed (fully approved, fully rejected, or mix). The UI still derives finer states (approved / partially_approved / rejected) from per-item outcomes. |

## 5. Mobile Views / Filters Impacted
- `vw_check_out_mobile_list` expects request status 36 (`getCheckOutMobileList`).
- `vw_return_eligible_items` collects rows with part_sub_status 36; reviewer statuses must not block this view unless the request is rolled back prior to checkout.
- `vw_return_mobile_list` and `vw_storekeeper_pending_returns` hinge on `material_returns.return_status` + `ast_part_sub.part_sub_status` (36/37).

## 6. Implications for the Reviewer Gate
- Introduce a reviewer-only status between 33 and 34 (e.g., `35 = awaiting_reviewer`, `37 = reviewer_published`) while keeping legacy IDs for admin/store phases. `wfl_transaction` needs matching values.
- Checkpoint 42 should only receive tasks once reviewer status is satisfied; until then, reviewer queue (new checkpoint) handles the request.
- Storekeeper operations (`reserve_request`, `check_out_request`) must ensure status ≥ approved (i.e., 34 or new `reviewer_approved`) before proceeding.
- Update all list/detail APIs to emit both the numeric ID and a friendly `label` so the mobile client can distinguish `awaiting_reviewer` vs `pending_admin` without hardcoding IDs.

Refer to this inventory when drafting the migration SQL and PHP constants to avoid clashing with existing codes.
