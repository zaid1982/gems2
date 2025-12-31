# Inventory Reviewer Backend Plan
_Date: 12 Dec 2025_

## Scope Recap
- Insert a mandatory Inventory Reviewer gate between technician submissions and current Inventory Admin approval.
- Ensure storekeeper actions (reserve / checkout / order) only operate after reviewer and admin approvals complete.
- Surface reviewer decisions across all MR feeds the mobile client consumes.

## Immediate Prerequisites
- **Schema inventory**: export the current MR status enumerations (table + PHP constants) so we can safely append reviewer states without colliding with legacy values.
- **API contract sync**: align with the mobile team on the payload additions (field names, nullability, sample JSON) before backend responses change.
- **Role registry**: confirm whether reviewer will reuse an existing role slot or requires a fresh `user_role` entry + seeding script; capture IDs for QA fixtures.
- **Audit/logging expectations**: decide which audit tables capture reviewer activity to avoid surprises once endpoints launch.
- **Test data**: prepare at least one MR request per critical branch (happy path, reviewer reject, re-review) for local + staging validation.

See `docs/mr_status_inventory.md` for the latest enumeration snapshot that all migration scripts should reference.

## Delivery Steps
1. **Status Model Update**
   - Add new status IDs (e.g., `AWAITING_REVIEW`, `REVIEW_COMPLETED`, `REJECTED_BY_REVIEWER`).
   - Update enum helpers and reference tables; expose friendly text + numeric ID in every MR payload.
   - Document transitions to keep state machine clear for QA.
2. **Detail Payload Extensions**
   - `/wo_request/request_details/{id}` and `/wo_parts/wo_parts_list/` must return reviewer metadata (user, timestamp, recommendation, per-line overrides, attachments).
   - Include reviewer blocks in history arrays so mobile/admin UI can display decisions inline.
3. **Reviewer Endpoints**
   - `GET /wo_request/review_details/{id}` → request body + reviewer card (mirroring admin view but scoped to reviewer role).
   - `POST /wo_request/review/{id}` → accept decision, remarks, optional line-level quantity suggestions and files; append audit log entries.
   - Optional `GET /wo_request/review_history/{id}` for future auditing (plan now, implement later if needed).
4. **Admin & Storekeeper Gating**
   - `PUT /wo_request/approve_request/{id}` validates reviewer completion + "recommended" outcome before processing.
   - Storekeeper endpoints (`reserve_request`, `check_out_request`, `order_request`) must hard-fail with descriptive errors unless status == fully approved.
   - Add regression tests for edge cases (missing reviewer, rejected reviewer, double approvals).
5. **Listing / Queue Adjustments**
   - Reviewer dashboards: new filters + list endpoints exposing only `AWAITING_REVIEW` requests.
   - Admin lists should surface reviewer summary columns for quick triage.
   - Mobile lists (`/wo_request/list`, etc.) need flags so technicians understand when requests sit with reviewers versus admins.
6. **Role & Access Control**
   - Introduce Inventory Reviewer role/flag; limit new endpoints to that role.
   - Update profile payload (used by mobile) to include reviewer flag so app can show reviewer workspace.
7. **Documentation & Tracking**
   - Update API docs + Postman collections with the new endpoints and payload shapes.
   - Record migration steps (SQL scripts) and communicate version bump to mobile team before rollout.

## Tracking Notes
- Treat each numbered step as a work package; create tickets or PRs referencing this document.
- Block downstream work until statuses and payload extensions (Steps 1–2) land; other steps depend on them.
- QA should script end-to-end flows once gating logic (Step 4) is in place.
