# Work Order (WO) Workflow — Complete Documentation

> **Generated**: 24 February 2026  
> **Purpose**: Reverse-engineered from codebase + database inspection (DEV `gems` @ metadatasystem.my / PROD `gems2` @ 10.101.11.69)  
> **Scope**: Covers `wo.php` (web), `m_wo.php` (mobile), `f_wo.php`, `f_task.php`, and all related DB configuration tables

---

## Table of Contents
1. [Architecture Overview](#1-architecture-overview)
2. [Database Tables Involved](#2-database-tables-involved)
3. [Workflow Engine (wfl_* tables)](#3-workflow-engine)
4. [Checkpoint Configuration](#4-checkpoint-configuration)
5. [Complete WO Lifecycle Flow](#5-complete-wo-lifecycle-flow)
6. [WR vs WO Flow (site_is_wr)](#6-wr-vs-wo-flow)
7. [Role & Permission Model](#7-role--permission-model)
8. [Status Codes Reference](#8-status-codes-reference)
9. [API Endpoints Mapping](#9-api-endpoints-mapping)
10. [⚠️ DEV vs PROD Differences (Critical)](#10-dev-vs-prod-differences)
11. [Site Configuration Differences](#11-site-configuration-differences)
12. [Recommendations to Fix](#12-recommendations-to-fix)

---

## 1. Architecture Overview

### Two API Files, Same Logic
| File | Platform | DB Class Used |
|------|----------|---------------|
| `api/wo.php` | Web (admin panel) | `Class_db` (legacy) |
| `api/m_wo.php` | Mobile (Flutter app) | `Class_db` + `DbMysql` (new) |

Both files share the same function classes:
- `api/function/f_wo.php` → `Class_wo` — All WO business logic
- `api/function/f_task.php` → `Class_task` — Workflow engine (state machine)
- `api/function/f_email.php` → `Class_email` — Email + push notifications
- `api/function/f_general.php` → `Class_general` — Uploads, audits, logging

### Request/Response Pattern
```
Every API returns: { success: bool, result: mixed, error: string, errmsg: string }
All POST/PUT/DELETE run inside: beginTransaction → logic → commit (rollback on exception)
JWT auth required on every request via Authorization header
```

---

## 2. Database Tables Involved

### Core WO Tables
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `wo_task` | Main WO record | `wo_task_id`, `wo_task_no`, `wo_task_status`, `wo_task_is_wr`, `transaction_id` |
| `wo_task_upload` | Images & signatures | `wo_task_id`, `upload_type` (1=complaint, 7=repair sig, 8=verify sig, 11=response, 12=check sig) |
| `wo_task_assist` | Assistant technicians | `wo_task_id`, `user_id` |
| `wo_task_request` | Material request forms | `wo_task_id`, `request_status` |

### Workflow Engine Tables
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `wfl_flow` | Defines workflow types | `flow_id=2` → Work Order, `flow_due_day=10` |
| `wfl_checkpoint` | Step definitions in a flow | `checkpoint_id`, `checkpoint_next`, `checkpoint_case_1/2/3`, `role_id` |
| `wfl_transaction` | Workflow instance per WO | `transaction_id`, `transaction_status`, `transaction_no` |
| `wfl_task` | Current step instance | `task_id`, `checkpoint_id`, `task_current=1` (active), `task_status` |
| `wfl_task_assign` | Pre-assigned users for future steps | `task_id`, `checkpoint_id`, `user_id` |
| `wfl_checkpoint_user` | Which users can act on which checkpoint | `checkpoint_id`, `user_id` |
| `wfl_checkpoint_assign` | Auto-assignment rules when transitioning | `checkpoint_id`, `checkpoint_to`, `checkpoint_assign_type` |

### Reference/Config Tables
| Table | Purpose |
|-------|---------|
| `ref_status` | Status ID → description/color mapping |
| `ref_role` | Role definitions (1=Admin, 6=Client, 7=Assigner, 8=Executor, 9=Internal Complainer) |
| `cli_site` | Site configuration including `site_is_wr` and `site_is_material` flags |
| `sys_user_role` | User ↔ Role ↔ Group assignments |
| `ppm_group` / `ppm_group_user` | Technician grouping (reused for WO technician assignment) |

---

## 3. Workflow Engine

### How `submit_task()` Works (f_task.php)
This is the **heart of the state machine**. Every WO action calls `submit_task()` which:

1. **Validates** the current task is still active (`task_current = 1`)
2. **Marks** current task as done (`task_current = 2`, `task_time_submit = NOW()`)
3. **Determines next checkpoint** based on branching:
   - `$next = ''` (empty) → use `checkpoint_next` (default path)
   - `$next = '1'` → use `checkpoint_case_1` (branch 1)
   - `$next = '2'` → use `checkpoint_case_2` (branch 2)  
   - `$next = '3'` → use `checkpoint_case_3` (branch 3)
4. **Checks checkpoint type** of next step:
   - `checkpoint_type = 3` (final) → Status **7** (Done), record `transaction_time_complete`
   - `checkpoint_type = 2` (intermediate) → Create new task at next checkpoint, status **8** (Received)
5. **Handles claim types** for the new task:
   - `claim_type = 1` → Unclaimed (anyone with access can claim)
   - `claim_type = 3` → Pre-assigned to specific user
   - `claim_type = 4` → Assigned to a group

### How `create_new_task()` Works (f_task.php)
Creates a brand new workflow instance:
1. Finds the start checkpoint (`checkpoint_type = 1`) or specified `$checkpointId`
2. Inserts into `wfl_transaction` (status 5=Draft, due date = today + flow_due_day)
3. Inserts into `wfl_task` (status 5=Draft, links to checkpoint)
4. Processes `wfl_checkpoint_assign` to pre-assign users to future checkpoints via `wfl_task_assign`

---

## 4. Checkpoint Configuration

### DEV Database (gems @ metadatasystem.my)
```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ CP ID │ Description          │ Type │ Claim │ Next │ Case1 │ Case2 │ Case3 │ Role │ Order │ Users Assigned │
├───────┼──────────────────────┼──────┼───────┼──────┼───────┼───────┼───────┼──────┼───────┼────────────────┤
│  10   │ Internal Complaint   │  1   │   3   │  12  │ NULL  │ NULL  │ NULL  │   9  │   1   │    1,099       │
│  11   │ Submit Complaint     │  1   │   3   │  12  │  17   │ NULL  │ NULL  │   6  │   1   │      255       │
│  12   │ Assign WO            │  2   │   1   │  13  │  15   │ NULL  │ NULL  │   7  │   2   │      272       │
│  13   │ Execute WO           │  2   │   3   │  20  │  12   │  16   │  13   │   8  │   5   │      718       │
│  14   │ Verify and Close WO  │  2   │   3   │  15  │  13   │ NULL  │ NULL  │   6  │   6   │      252       │
│  15   │ Closed               │  3   │   1   │ NULL │ NULL  │ NULL  │ NULL  │   6  │   7   │        1       │
│  16   │ Verify and Close WO  │  2   │   3   │  15  │  13   │ NULL  │ NULL  │   7  │   6   │      272       │
│  17   │ Assign WR            │  2   │   1   │  18  │  15   │ NULL  │ NULL  │   7  │   2   │      272       │
│  18   │ Check WR             │  2   │   3   │  13  │  19   │  17   │  15   │   8  │   3   │      718       │
│  19   │ Verify WR            │  2   │   3   │  13  │  15   │  18   │ NULL  │   7  │   4   │      259       │
│  20   │ Check WO ⭐ NEW      │  2   │   3   │  14  │  16   │  13   │ NULL  │   7  │   5   │      272       │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### PROD Database (gems2 @ 10.101.11.69)
```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ CP ID │ Description          │ Type │ Claim │ Next │ Case1 │ Case2 │ Case3 │ Role │ Order │ Users Assigned │
├───────┼──────────────────────┼──────┼───────┼──────┼───────┼───────┼───────┼──────┼───────┼────────────────┤
│  10   │ Internal Complaint   │  1   │   3   │  12  │ NULL  │ NULL  │ NULL  │   9  │   1   │    1,128       │
│  11   │ Submit Complaint     │  1   │   3   │  12  │  17   │ NULL  │ NULL  │   6  │   1   │      257       │
│  12   │ Assign WO            │  2   │   1   │  13  │  15   │ NULL  │ NULL  │   7  │   2   │      270       │
│  13   │ Execute WO           │  2   │   3   │  14  │  12   │  16   │ NULL  │   8  │   5   │      737       │
│  14   │ Verify and Close WO  │  2   │   3   │  15  │  13   │ NULL  │ NULL  │   6  │   6   │      254       │
│  15   │ Closed               │  3   │   1   │ NULL │ NULL  │ NULL  │ NULL  │   6  │   7   │        1       │
│  16   │ Verify and Close WO  │  2   │   3   │  15  │  13   │ NULL  │ NULL  │   7  │   6   │      270       │
│  17   │ Assign WR            │  2   │   1   │  18  │  15   │ NULL  │ NULL  │   7  │   2   │      270       │
│  18   │ Check WR             │  2   │   3   │  13  │  19   │  17   │  15   │   8  │   3   │      737       │
│  19   │ Verify WR            │  2   │   3   │  13  │  15   │  18   │ NULL  │   6  │   4   │      254       │
│  ❌   │ Check WO (MISSING!)  │  -   │   -   │  -   │  -    │  -    │  -    │   -  │   -   │        0       │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### Checkpoint Types
| Value | Meaning |
|-------|---------|
| 1 | **Start** checkpoint (entry point) |
| 2 | **Intermediate** checkpoint (normal step) |
| 3 | **Final** checkpoint (terminates workflow) |

### Claim Types
| Value | Meaning |
|-------|---------|
| 1 | **Open** — Anyone authorized can claim/act |
| 3 | **Pre-assigned** — System assigns to specific user |
| 4 | **Group** — Assigned to a group |

---

## 5. Complete WO Lifecycle Flow

### Flow A: Direct WO (site_is_wr = 0 OR Self-Finding Internal)

```
                    ┌─────────────────────────┐
                    │  COMPLAINT SUBMITTED     │
                    │  (Mobile/Web/Helpdesk)   │
                    └────────────┬─────────────┘
                                 │
                    CP 10 (Internal, role 9)
                    CP 11 (Client/External, role 6)
                                 │
                    ┌────────────▼─────────────┐
                    │  Status 24: ASSIGN       │
                    │  CP 12: Assign WO        │
                    │  Role 7 (WO Assigner)    │
                    │                          │
                    │  Actions available:       │
                    │  - save_assigned_tech     │
                    │  - save_wo_severity       │
                    │  - submit_assign          │
                    │  - reject_complaint → 25  │
                    └────────────┬─────────────┘
                                 │ submit_assign
                                 │ (WR→WO conversion for Self-Finding)
                    ┌────────────▼─────────────┐
                    │  Status 13: IN PROGRESS  │
                    │  CP 13: Execute WO       │
                    │  Role 8 (WO Executor)    │
                    │                          │
                    │  Actions available:       │
                    │  - save_wo_repair_work    │
                    │  - upload_repair_image    │
                    │  - upload_response_image  │
                    │  - save_asset_no          │
                    │  - submit_repair          │
                    │  - return_by_technician   │
                    │    → back to CP 12 (26)   │
                    └────────────┬─────────────┘
                                 │ submit_repair
                                 │
                    ┌────────────▼─────────────┐     ┌──── DEV ONLY ────────┐
                    │  *** BRANCHING HERE ***    │     │                      │
                    │                          │     │  CP 20: Check WO     │
                    │  DEV:  next=20 (Check WO)│────►│  Role 7 (Assigner)   │
                    │  PROD: next=14 (Verify)  │     │  Status 14: CHECK    │
                    │                          │     │                      │
                    └────────────┬─────────────┘     │  → next=14 (Verify)  │
                                 │                    │  → case1=16 (Verify) │
                                 │                    │  → case2=13 (Return) │
                  (PROD skips    │                    └──────────┬───────────┘
                   to Verify)    │                               │
                                 │◄──────────────────────────────┘
                    ┌────────────▼─────────────┐
                    │  Status 15: VERIFY       │
                    │  CP 14: Verify & Close WO│
                    │  Role 6 (Client)         │
                    │  — OR —                   │
                    │  CP 16: Verify & Close WO│
                    │  Role 7 (WO Assigner)    │
                    │                          │
                    │  Actions:                 │
                    │  - submit_verify → 16     │
                    │  - return_verify → 21     │
                    │    (back to CP 13)        │
                    └────────────┬─────────────┘
                                 │ submit_verify
                    ┌────────────▼─────────────┐
                    │  Status 16: COMPLETED    │
                    │  CP 15: Closed (Final)   │
                    │  Workflow Done (status 7) │
                    └──────────────────────────┘
```

### Flow B: WR → WO Path (site_is_wr = 1, Client/Public Complaint)

```
                    ┌─────────────────────────┐
                    │  CLIENT COMPLAINT        │
                    │  CP 11 (role 6)          │
                    │  Always starts as WR     │
                    │  Status 24: ASSIGN       │
                    └────────────┬─────────────┘
                                 │ submit_assign 
                                 │ (case_1 → CP 17 for WR sites)
                    ┌────────────▼─────────────┐
                    │  Status 27: WR CHECK     │
                    │  CP 17: Assign WR        │
                    │  Role 7 (WO Assigner)    │
                    │  → Assigns technician     │
                    └────────────┬─────────────┘
                                 │ (next → CP 18)
                    ┌────────────▼─────────────┐
                    │  Status 27: WR CHECK     │
                    │  CP 18: Check WR         │
                    │  Role 8 (WO Executor)    │
                    │                          │
                    │  submit_wr_check          │
                    │  → Status 28: WR VERIFIED│
                    │                          │
                    │  Branching:               │
                    │  next → CP 13 (valid→WO)  │
                    │  case1 → CP 19 (verify)   │
                    │  case2 → CP 17 (return)   │
                    │  case3 → CP 15 (close)    │
                    └────────────┬─────────────┘
                                 │ case1 → CP 19
                    ┌────────────▼─────────────┐
                    │  CP 19: Verify WR        │
                    │  DEV: Role 7 (Assigner)  │
                    │  PROD: Role 6 (Client)   │  ⚠️ DIFFERENT!
                    │                          │
                    │  submit_wr_verified:      │
                    │  If VALID:                │
                    │    → WR converts to WO    │
                    │    → New WO number         │
                    │    → Status 13 (Progress)  │
                    │    → Flow continues to     │
                    │      Execute WO (CP 13)    │
                    │                          │
                    │  If INVALID:              │
                    │    → Status 30 (Invalid)   │
                    │    → Workflow closes        │
                    │                          │
                    │  return_wr_verify:         │
                    │    → Status 31 (Re-Open)   │
                    │    → Back to CP 18 (Check) │
                    └──────────────────────────┘
```

---

## 6. WR vs WO Flow (site_is_wr Flag)

The `cli_site.site_is_wr` flag controls whether a site uses the **Work Request** pre-screening process:

| site_is_wr | Behavior |
|------------|----------|
| **0** | Complaints go directly to WO assignment → execution → verify. No WR step. |
| **1** | Client complaints start as WR → WR Check → WR Verify → then convert to WO → execute → verify |

### How `submit_assign()` Decides the Path (f_wo.php)
```
IF wo_task_type_init = 2 (Self-Finding / Internal)
    → ALWAYS converts WR→WO immediately (new WO number)
    → Status 13 (In Progress) → CP 13 (Execute)

ELSE IF wo_task_is_wr = 1 (Client/Public complaint on WR site)
    → Stays as WR
    → Status 27 (WR Check) → CP 17 (Assign WR)

ELSE (reassignment or already WO)
    → Status 13 → CP 13 (Execute)
```

### How `create_wo_no()` Generates Numbers
```
IF isInitialComplaint = true
    → Always generates WR{siteCode}{yymmdd}{seq}
    → Increments site_running_no_wr

IF isWo = true OR site_is_wr = 0
    → Generates WO{siteCode}{yymmdd}{seq}
    → Increments site_running_no_wo

ELSE (site_is_wr = 1)
    → Generates WR{siteCode}{yymmdd}{seq}
    → Increments site_running_no_wr
```

---

## 7. Role & Permission Model

### Roles Involved in WO Workflow
| Role ID | Name | WO Function |
|---------|------|-------------|
| **1** | Administrator | Full access, bypasses site filters |
| **6** | Client/Complainer | Submits complaints (CP 11), verifies & closes WO (CP 14) |
| **7** | WO Assigner | Assigns WO (CP 12), checks WO (CP 20 DEV), verifies WO (CP 16) |
| **8** | WO Executor | Technician — executes repair (CP 13), checks WR (CP 18) |
| **9** | Internal Complainer | Self-finding complaints (CP 10) |
| **10** | GFM Management | Full access, bypasses site filters |
| **11** | WO Helpdesk | Can submit complaints on behalf of clients |
| **17** | WO Supervisor | Additional supervisor role |

### Permission Chain
```
wfl_checkpoint_user → Links user_id to checkpoint_id
    → Controls WHO can act on each step
    → When a new user is registered, they must be added to relevant checkpoint_user entries
    → This is a CRITICAL configuration table!
```

---

## 8. Status Codes Reference

### WO-Specific Statuses (wo_task_status)
| ID | Name | Trigger | Next Step |
|----|------|---------|-----------|
| **24** | Assign | `submit_new_complaint` | Waiting for assignment |
| **13** | In Progress | `submit_assign` (for WO) | Technician executing |
| **14** | Check | `submit_repair` | Supervisor checking (DEV only path) |
| **15** | Verify | `submit_check` or `submit_repair(isVerify)` | Verifier reviewing |
| **16** | Completed | `submit_verify` | Workflow done |
| **21** | Re-Open | `return_verify`, `return_from_check_m` | Back to technician |
| **25** | Out of Scope | `reject_complaint` | Rejected/closed |
| **26** | Revisit | `return_by_technician` | Back to assigner |

### WR-Specific Statuses
| ID | Name | Trigger | Next Step |
|----|------|---------|-----------|
| **27** | WR Check | `submit_assign` (for WR sites) | Waiting for tech check |
| **28** | WR Verified | `submit_wr_check` | Waiting for verifier |
| **29** | WR Reassign | Return from WR check | Back to assigner |
| **30** | WR Invalid | `submit_wr_verify(rejected)` | Closed as invalid |
| **31** | WR Re-Open | `return_wr_verify` | Back to tech for recheck |

### Workflow Engine Statuses (task_status in wfl_task)
| ID | Name | Meaning |
|----|------|---------|
| **5** | Draft | Initial creation |
| **7** | Done | Workflow completed |
| **8** | Received | Task arrived at new checkpoint |
| **9** | Submitted | Task submitted/processed |
| **10** | Assigned | Task assigned to user |
| **20** | Returned | Task returned to previous step |

---

## 9. API Endpoints Mapping

### Mobile API (m_wo.php)

#### GET Endpoints
| type parameter | Function | Description |
|----------------|----------|-------------|
| `submitted_wo` | `get_submitted_wo_m` | List user's submitted work orders |
| `pending_task` | `get_pending_task_m` | List user's pending tasks |
| `section_status` | `get_section_status_m` | WO section completion status |
| `section_status_assign` | `get_section_status_assign_m` | Assignment section status |
| `section_status_wr` | `get_section_status_wr_m` | WR section status |
| `complaint_details` | `get_complaint_details_m` | Complaint info |
| `wo_group_list` | `get_wo_group_m` | Available groups for assignment |
| `wo_technician_list` | `get_wo_technician_m` | Technicians in a group |
| `technician_details` | `get_technician_details_m` | Technician details |
| `assign_and_severity` | `get_wo_assign_severity_m` | Current assignment + severity info |
| `wo_repair_work` | `get_wo_repair_desc_m` | Repair description |
| `wo_repair_images` | `get_wo_repair_images_m` | Repair images |
| `wo_response_images` | `get_wo_response_images_m` | Response images |
| `preview_pdf` | Generate + return PDF | WO/WR PDF generation |
| `wo_rate` | `get_wo_rate_m` | WO rating |
| `wo_severity_list` | `get_wo_severity_list_m` | Available severities |
| `wr_rectification_time` | `get_wr_rectification_time_m` | WR rectification time |

#### POST Actions
| action parameter | Function | Status Change |
|------------------|----------|---------------|
| `submit_complain` | Creates new WR/WO complaint | → **24** (Assign) |
| `save_assigned_technician` | Saves tech assignment | No status change |
| `save_wo_severity` | Saves severity | No status change |
| `save_wr_rectification_time` | Saves rectification time | No status change |
| `save_asset_no` | Saves asset number | No status change |
| `submit_assign` | Assigns WO to technician | → **13** or **27** |
| `submit_wr_check` | Technician checks WR | → **28** |
| `submit_wr_verified` | Verifier validates WR | → **13** or **30** |
| `return_by_verifier` | Return WR to tech | → **31** |
| `return_from_check` | Return from Check to Execute | → **21** |
| `reject_complaint` | Reject as out of scope | → **25** |
| `save_wo_repair_work` | Save repair description | No status change |
| `upload_repair_image` | Upload repair image | No status change |
| `upload_response_image` | Upload response image | No status change |
| `save_wo_repair_image_desc` | Save image description | No status change |
| `return_by_technician` | Tech returns to assigner | → **26** |
| `submit_repair` | Submit repair done | → **14** or **15** |
| `submit_check` | Submit supervisor check | → **15** |
| `return_verify` | Return to technician | → **21** |
| `submit_verify` | Verify and close | → **16** |
| `save_wo_rate` | Save rating | No status change |

### Web API (wo.php)

#### GET Endpoints
| type parameter | Description |
|----------------|-------------|
| `dashboard_list` | Dashboard WO list with filters |
| `dashboard_table` | Server-side DataTable for dashboard |
| `total_by_site_status` | Totals by site and status |
| `total_by_site_type` | Totals by site and type |
| `total_by_type` | Totals by type |
| `total_by_status` | Totals by status |
| `total_by_group` | Totals by group |
| `top5_execute` / `bottom5_execute` | Performance rankings |
| `average_execute_by_trade` | Average execution time by trade |
| `report_wo_summary` | Monthly report summary |
| `report_wo_pending_list` | Pending WO list |
| `report_wo_total` | Total WO report |
| `report_wo_daily` | Daily WO report |
| `helpdesk_list` | Helpdesk complaints |
| `severity_list_by_site` | Severities per site |
| `technician_current_task` | Current tech task |

#### POST Actions (Web)
| action | Description |
|--------|-------------|
| `insert_site_manual` | Add manual site report |
| `submit_helpdesk_complaint` | Submit helpdesk complaint (includes immediate assignment) |
| `generate_pdf` / `generate_pdf_wr` | Generate PDF |

---

## 10. ⚠️ DEV vs PROD Differences (Critical)

### 🔴 CRITICAL: Checkpoint 20 (Check WO) — Missing in PROD

| Aspect | DEV (gems) | PROD (gems2) |
|--------|-----------|--------------|
| **CP 20 exists?** | ✅ YES — "Check WO" | ❌ **NO — DOES NOT EXIST** |
| **CP 13 next** | → **20** (Check WO) | → **14** (Verify & Close) |
| **CP 13 case_3** | → **13** (self/reassign) | → **NULL** |
| **CP 20 users** | 272 users assigned | N/A (doesn't exist) |

**Impact**: In DEV, after technician submits repair (CP 13), the workflow goes to **CP 20 (Check WO)** where a supervisor reviews before verification. In PROD, it goes **directly to CP 14 (Verify & Close)**, skipping the supervisor check.

**Code reference**: `m_wo.php` has `return_from_check` action that calls `get_current_task('14', '20')` — this **will fail in PROD** because CP 20 doesn't exist.

### 🔴 CRITICAL: Checkpoint 19 (Verify WR) Role Difference

| Aspect | DEV (gems) | PROD (gems2) |
|--------|-----------|--------------|
| **CP 19 role_id** | **7** (WO Assigner) | **6** (Client/Complainer) |
| **CP 19 group_id** | **1** | **NULL** |
| **CP 19 users** | 259 users | 254 users |

**Impact**: In DEV, the WO Assigner (role 7) verifies WR. In PROD, the Client/Complainer (role 6) verifies WR. This means **different people are authorized** to verify work requests.

### 🟡 WARNING: Checkpoint Assign Rules Differ

| Entry | DEV | PROD |
|-------|-----|------|
| CP 11 → CP 19 | ❌ Not present | ✅ Present (assign_id=11) |
| CP 12 → CP 20 | ✅ Present (assign_id=18) | ❌ Not present (CP 20 doesn't exist) |
| CP 17 → CP 16 | ✅ Present (assign_id=16) | ❌ Not present |
| CP 17 → CP 19 | ✅ Present (assign_id=11) | ❌ Not present |
| CP 17 → CP 20 | ✅ Present (assign_id=19) | ❌ Not present |

**Impact**: The auto-assignment of users to future checkpoints differs. When a WO is created, the system pre-assigns users to checkpoints via `wfl_task_assign`. Missing entries mean users won't be auto-assigned to certain steps.

### 🟡 WARNING: wo_task Table Column Order Differs

| DEV (gems) | PROD (gems2) |
|-----------|-------------|
| `wo_task_external_ref` at position 10 | `wo_task_external_ref` at position 49 |
| `wo_task_is_imported` at position 11 | `wo_task_is_imported` at position 50 |
| `wo_task_is_pdf_wr` at position 34 | `wo_task_is_pdf_wr` at position 14 |
| `wo_task_is_pdf` at position 35 | `wo_task_is_pdf` at position 15 |

**Impact**: Column ordering shouldn't matter for named queries, but **any code using positional indexes** (unlikely but possible) would break.

---

## 11. Site Configuration Differences

### Sites with Different WR/Material Settings

| Site | DEV site_is_wr | PROD site_is_wr | DEV site_is_material | PROD site_is_material |
|------|---------------|-----------------|---------------------|----------------------|
| **9 (LKIM)** | 1 | 1 | **1** | **0** ⚠️ |
| **10 (UITMT)** | 1 | 1 | **0** | **1** ⚠️ |
| **17 (ASB)** | **1** | **0** ⚠️ | 1 | 1 |
| **23 (IN/JKR)** | **1** | **0** ⚠️ | 1 | 1 |
| 24 (P14) | N/A | 0 | N/A | 0 |
| 25 (GFMIN) | N/A | 1 | N/A | 0 |
| 26 (GFMUM) | N/A | 1 | N/A | 0 |
| 27 (GFMUT) | N/A | 1 | N/A | 0 |

**Impact of site_is_wr differences**:
- **ASB (17)**: DEV treats complaints as WR first → WR Check → WR Verify → WO. PROD treats them as direct WO.
- **JKR IN (23)**: Same issue — WR flow in DEV, direct WO flow in PROD.

---

## 12. Recommendations to Fix

### Priority 1: Add Checkpoint 20 to PROD (if intended)
If the "Check WO" step is the desired behavior, run on PROD:
```sql
-- Add CP 20 (Check WO) to PROD
INSERT INTO wfl_checkpoint 
(checkpoint_id, flow_id, checkpoint_desc, checkpoint_type, checkpoint_claim_type, 
 checkpoint_due_day, checkpoint_next, checkpoint_case_1, checkpoint_case_2, checkpoint_case_3, 
 role_id, group_id, checkpoint_order, checkpoint_skip)
VALUES 
(20, 2, 'Check WO', 2, 3, 1, 14, 16, 13, NULL, 7, 1, 5, 0);

-- Update CP 13 to point to CP 20 instead of CP 14
UPDATE wfl_checkpoint 
SET checkpoint_next = 20, checkpoint_case_3 = 13 
WHERE checkpoint_id = 13 AND flow_id = 2;

-- Copy CP 20 user assignments from DEV
-- (Need to run query on DEV first to get user list, then insert into PROD)

-- Add checkpoint_assign entries
INSERT INTO wfl_checkpoint_assign (checkpoint_assign_type, checkpoint_id, checkpoint_to)
VALUES (1, 12, 20), (1, 17, 20);
```

**OR if PROD behavior (no Check WO) is correct**, update DEV:
```sql
-- Remove CP 20 from DEV
DELETE FROM wfl_checkpoint WHERE checkpoint_id = 20 AND flow_id = 2;
DELETE FROM wfl_checkpoint_user WHERE checkpoint_id = 20;
DELETE FROM wfl_checkpoint_assign WHERE checkpoint_to = 20;

-- Update CP 13 to skip Check WO
UPDATE wfl_checkpoint 
SET checkpoint_next = 14, checkpoint_case_3 = NULL 
WHERE checkpoint_id = 13 AND flow_id = 2;
```

### Priority 2: Fix CP 19 Role Mismatch
Decide which role should verify WR:
```sql
-- If WO Assigner (role 7) should verify WR (match DEV):
UPDATE wfl_checkpoint SET role_id = 7, group_id = 1 WHERE checkpoint_id = 19 AND flow_id = 2;
-- Then need to update wfl_checkpoint_user for CP 19 to match role 7 users

-- If Client (role 6) should verify WR (match PROD):
-- Update DEV instead
```

### Priority 3: Sync checkpoint_assign Rules
```sql
-- Add missing entries to PROD (if CP 20 is added):
INSERT INTO wfl_checkpoint_assign (checkpoint_assign_type, checkpoint_id, checkpoint_to)
VALUES 
(1, 17, 16),  -- WR Assign → Verify WO
(1, 17, 19);  -- WR Assign → Verify WR
```

### Priority 4: Sync Site Configurations
Review and align `site_is_wr` and `site_is_material` for sites 9, 10, 17, 23.

### Priority 5: Code Guards
Add defensive code in `m_wo.php` for `return_from_check`:
```php
// Check if CP 20 exists before trying to use it
if ($action === 'return_from_check') {
    // Try CP 20 first (new Check WO step), fall back to CP 14 (old flow)
    try {
        $currentTask = $fn_wo->get_current_task('14', '20');
    } catch (Exception $e) {
        $currentTask = $fn_wo->get_current_task('15', '14'); // fallback
    }
}
```

---

## Appendix A: Complete Checkpoint State Machine Diagram

```
                         CHECKPOINT FLOW MAP (Flow ID = 2)
                         ══════════════════════════════════

 ┌──────────┐   ┌──────────┐
 │ CP 10    │   │ CP 11    │     ← START POINTS (checkpoint_type=1)
 │ Internal │   │ Client   │
 │ (role 9) │   │ (role 6) │
 └────┬─────┘   └───┬──┬───┘
      │              │  │
      │    default   │  │ case_1 (WR site)
      └──────┬───────┘  │
             ▼          ▼
      ┌──────────┐   ┌──────────┐
      │ CP 12    │   │ CP 17    │
      │ Assign WO│   │ Assign WR│
      │ (role 7) │   │ (role 7) │
      └──┬───┬───┘   └──┬───┬───┘
 default │   │case1      │   │case1
         │   │           │   │
         ▼   ▼           ▼   ▼
  ┌──────────┐ ┌─────┐ ┌──────────┐ ┌─────┐
  │ CP 13    │ │CP 15│ │ CP 18    │ │CP 15│
  │Execute WO│ │Close│ │ Check WR │ │Close│
  │ (role 8) │ │     │ │ (role 8) │ │     │
  └──┬─┬─┬───┘ └─────┘ └──┬─┬─┬───┘ └─────┘
     │ │ │                 │ │ │
     │ │ │ DEV: default    │ │ │ default    case1       case2       case3
     │ │ │ ┌──────────┐    │ │ └──────►CP 13  └──►CP 19   └──►CP 17   └──►CP 15
     │ │ └►│ CP 20    │    │ │        (Execute)  (Verify WR) (Return)   (Close)
     │ │   │ Check WO │    │ │
     │ │   │ (role 7) │    │ │
     │ │   └──┬──┬────┘    │ │
     │ │      │  │         │ │
     │ │ next │  │case1    │ │
     │ │      ▼  ▼         │ │
     │ │  CP 14  CP 16     │ │
     │ │                   │ │
     │ │ PROD: default     │ │
     │ └──────►CP 14       │ │
     │         ┌──────────┐│ │
     │    case1│ CP 14    ││ │
     └────────►│Verify WO ││ │     ┌──────────┐
               │ (role 6) ││ │     │ CP 19    │
               └──┬───┬───┘│ │     │ Verify WR│
          default │   │    │ │     │DEV:role 7│
                  ▼   ▼    │ │     │PRD:role 6│
           ┌─────┐ CP 13   │ │     └──┬───┬───┘
           │CP 15│ (return) │ │  next  │   │case1
           │Close│          │ │        ▼   ▼
           │FINAL│          │ │   CP 13  CP 15
           └─────┘          │ │   (WO)   (Close)
                            │ │
        ┌──────────┐        │ │case2
        │ CP 16    │        │ └──►CP 18 (return to Check WR)
        │Verify WO │        │
        │ (role 7) │        │
        └──┬───┬───┘        │
    default│   │case1       │
           ▼   ▼            │
     ┌─────┐  CP 13        │
     │CP 15│  (return)      │
     │Close│                │
     └─────┘                │
```

## Appendix B: Notification Templates

### Email Templates Used in WO Flow
| Template ID | Name | Used When |
|-------------|------|-----------|
| 5 | WO assigned | After `submit_assign` for WO |
| 11 | WR assigned | After `submit_assign` for WR |
| 12 | WR checked | After `submit_wr_check` |
| 15 | WR returned by verifier | After `return_by_verifier` |

### Push Notification Texts
| Text ID | Name | Used When |
|---------|------|-----------|
| 6 | WO executor received assigned work order | After WO assignment |
| 12 | WR executor received assigned work request | After WR assignment |
| 13 | WR verifier received checked work request | After WR check |
| 15 | WR complainer received invalid work request | WR rejected |
| 16 | WR executor received returned task | WR re-opened |

## Appendix C: Audit Trail Action IDs

| Audit ID | Action | Context |
|----------|--------|---------|
| 104 | Submit complaint | New WR complaint |
| 110 | Save assigned technician | Technician assignment saved |
| 111 | Save severity / rectification time | Severity or time updated |
| 118 | Preview PDF | PDF generated |
| 124 | Delete WO | WO deleted |
| 125 | Add manual report | Site manual report added |
| 126 | Update manual report | Site manual report updated |
| 129 | Submit assign | WO assigned to technician |
| 131 | Submit WR check | WR checked by technician |
| 135 | Save asset no | Asset number saved |
| 136 | Submit helpdesk complaint | Helpdesk complaint submitted |
| 147 | Return by verifier | WR returned for re-check |
