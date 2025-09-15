## GEMS2 – Copilot Instructions (AI Agent Guide)

Purpose: Make AI agents productive fast in this PHP monolith by documenting real patterns, workflows, and non‑obvious conventions.

### Architecture snapshot
- Stack: PHP app under Apache/XAMPP (`htdocs/gems2`), no framework. Frontend pages in `*.html` + `js/`, backend APIs in `api/*.php` with classes in `api/class/` and shared traits in `api/trait/`.
- Key classes: `api/class/General.php` (JWT auth, URL parsing, logging, audit, helpers; uses `SiteFilterTrait`), `api/class/DbMysql.php` (DB access), domain classes like `WoTask.php`, `WflTask.php`, `Email.php`, `Noti.php`.
- Auth: JWT header validated via `General::checkJwt(apache_request_headers())`. Public endpoints use a URL prefix segment `ext` to bypass auth (see `api/wo_v3.php`).
- Site data segregation: Implemented centrally with `api/trait/SiteFilterTrait.php` and helpers in `General`. Admin roles 1 and 10 are exempt; all others filtered by `userSite` (see `SITE_FILTERING_IMPLEMENTATION.md`).

### API conventions (very important)
- Entry pattern: `require_once` class files, then:
  - Parse route: `General::getUrlArr($_SERVER['REQUEST_URI'], $apiName)`.
  - Set `Constant::$isLogged` to toggle debug logs.
  - Enforce JWT unless URL begins with `ext`.
  - Route by `$requestMethod` and `$urlArr[n]` segments.
- Response envelope: `$formData = ['success'=>false,'result'=>'','error'=>'','errmsg'=>''];` and `json_encode` at end; set `success=true` on success.
- Transactions: `DbMysql::beginTransaction(); ... DbMysql::commit();` Use try/catch to rollback on exceptions.
- DB helpers: `DbMysql::select/ selectColumn`, etc. Prefer class methods that encapsulate SQL and site filters.
- Example route pattern with site filter:
  - `GET /api/wo_v3.php/pending_assign/site/{siteId}` → `WoTask::pendingAssignBySite(int $siteId)` after `validateSiteAccess()`.
  - Without `/site/{id}` calls the unfiltered variant for admins.

### Frontend patterns
- Pages: `*.html` in root map to JS modules in `js/pages/*`. Use helpers like `mzGetUserInfoByParam('siteId')` and `mzIsRoleExist('1,10')` to adjust UI and pick site‑scoped endpoints (see JS changes summarized in `SITE_FILTERING_IMPLEMENTATION.md`).
- Example: When non‑admin, fetch `.../endpoint/site/${userSite}` and disable site dropdowns.

### Notable modules/examples
- Work Orders v3: `api/wo_v3.php` shows canonical routing, public complaint POST flow, PDF generation stub, email/notification integration.
- Site filtering: `api/class/General.php` + `api/trait/SiteFilterTrait.php` used across APIs; mirror this when adding features.
- Imports: `api/wo_import.php`, `api/ppm_import.php` (frontend `wo_import.html`, `ppm_import.html`). Excel handled via `phpoffice/phpspreadsheet` (see `composer.json`).
- Gamification weekly: compatible with existing `gmi_weekly` structure (`EXISTING_TABLE_COMPATIBILITY.md`, SQL in `gmi_weekly_setup.sql`).

### Developer workflows
- Local run: Serve via Apache/XAMPP under `/Applications/XAMPP/xamppfiles/htdocs/gems2` and browse pages like `http://localhost/gems2/home.html`.
- Install PHP deps (for Excel):
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/gems2
composer install
```
- Database: SQL scripts in `/sql/`, root `*.sql`, and `migrations/`. Apply with your MySQL client; e.g. staging bundles under `maintenance/` (see `staging-deployment-db-schema-safe.sql`).
- Deployment: `./deploy_production.sh` uses `rsync` and excludes `developer/`, `.git/`, logs, etc. Do not deploy the `developer/` folder (`developer/README.md`).

### Adding a new API endpoint (recipe)
1) Create `api/feature_x.php`; include needed classes/traits via `require_once`.
2) `$apiName` matches filename; parse URL with `General::getUrlArr`.
3) Enforce JWT unless `ext` path is used intentionally.
4) For site‑scoped data, add `/site/{siteId}` variant and call a `*BySite($siteId)` method that validates access via `General::validateSiteAccess` or trait helper.
5) Return the standard envelope and log via `General::logDebug` when `Constant::$isLogged` is true.

### Gotchas and house rules
- Keep response envelope consistent; clients expect `success/result/error/errmsg`.
- Always guard DB changes with transactions in PUT/POST flows.
- Use centralized site filtering and role checks (roles `1,10` are admins).
- Public POST flows (e.g., complaint) must verify `siteId` and group mapping like in `wo_v3.php` before creating records.
- Exclude `developer/` from production; production uses `validate_import.html`, not dev utilities.

Key files to study: `api/wo_v3.php`, `api/class/General.php`, `api/trait/SiteFilterTrait.php`, `deploy_production.sh`, `developer/README.md`.

### PTW module (Permit to Work)
- Code paths: Backend `api/ptw.php` (create/update/list + supervisor actions), `api/ptw_approve.php` (SHE/FM approvals + extend/cancel/suspend/close), `api/ptw_approval.php` (view mode data API), `api/function/f_ptw.php` (business logic), PDFs `api/ptw_pdf*.php`.
- Public entrypoint: `ptw_form.html` is the entry for new PTW submissions (public access). Public forms are posted to `api/ptw.php` with `public_user=Public User` and must include `site_id`; the API assigns the request number on submit and routes into the approval chain.
- Roles & RBAC: Roles come from `vw_roles`; use constants in `Class_constant`: `PTW_ROLE_SUPERVISOR`, `PTW_ROLE_SHE`, `PTW_ROLE_FM`, `ROLE_ADMIN`. Helper guards `ptw_enforce()` (in `ptw.php`) allow admin bypass; approvals in `ptw_approve.php` recheck roles.
- Status flow (enums in DB + history): `DRAFT` → `PENDING_SUPERVISOR` → `PENDING_SHE` → `PENDING_FM` → `ACTIVE` (aka Approved). Extensions can yield `EXTENDED`. Closure adds `PENDING_CLOSURE` → `COMPLETED`. Other transitions: `REJECTED`, `CANCELLED`, `SUSPENDED`, `EXPIRED`. History table logs `action_type` events (e.g., `SUBMITTED`, `SHE_APPROVED`, `FM_APPROVED`, `EXTENDED`, `CLOSURE_REQUESTED`).
- Numbering: Request number assigned at first submit (e.g., `RQPTW{site}{yymmdd}{seq}`), permit number at supervisor approval (e.g., `PTW{site}{yymmdd}{seq}`) via per-site daily sequence in `ptw_number_sequence` (`Class_ptw::assign_request_number/assign_permit_number`).
- Endpoints (examples):
  - GET `api/ptw.php?action=list|statistics|dashboard_data|details&permit_id=...` (site-filtered via user’s site)
  - POST `api/ptw.php` with `action=supervisor_approve|supervisor_reject|supervisor_return_for_modification` or create when no `action`
  - POST `api/ptw_approve.php` with `action=she_approve|she_reject|fm_approve|fm_reject|request_extend|approve_extend|request_close|approve_close|request_cancel|approve_cancel|request_suspend|approve_suspend` and `permit_id`
  - GET/POST `api/ptw_approval.php` for view-mode data and inline approvals; supports resolving by `ptw_permit_id`, `ptw_permit_number`, `ptw_request_number` or `public_token`
- Auth patterns: Uses `Class_login::check_jwt(Authorization)`; some dev flows accept a test token (`Bearer valid_test_token_for_fm_dashboard`) or fall back to userId 1 if missing (keep strict in production). Site scoping uses `sys_user.site_id` and explicit `site_id` filters in queries.
- View mode UX: Open `ptw_form.html?mode=view&role={supervisor|she|fm}&id={PTW_ID}` in a modal/iframe from dashboards (see `ptw_supervisor_dashboard.html`); form loads via `api/ptw_approval.php` and posts back approvals.
- Public token: `api/ptw_public_token.php` (`action=regenerate`) issues a `public_token` tied to a permit’s validity; `api/ptw_approval.php` can resolve a permit by token for limited access flows.
- Data model notes: Enhanced JSON/text fields for checklists and hazards exist (e.g., `ptw_hazardous_activities` JSON, `ptw_checklist_cold_work`); migrations under `/sql/` keep enums and workflow states in sync (pending closure, rejected enum, extension support, cancel/suspend).
- Response shape: Same envelope `success/result/error/errmsg`; many endpoints return normalized fields (e.g., `ptw_display_status` via `Class_ptw::map_display_status`).
 - Active-state actions: Once `ACTIVE`, the Supervisor can request `extend`, `suspend`, `cancel`, or `close` using `api/ptw_approve.php` actions. Maximum extension is one time; after status becomes `EXTENDED`, only `close` or `cancel` are allowed (suspend is no longer allowed). Closure flows through `PENDING_CLOSURE` before `COMPLETED`.

#### Public submission: required fields and checks
- Captcha: Not currently enforced. You may propose one, but there is no requirement today.
- Required on create (server-validated in `api/ptw.php:create_ptw_permit`): `work_description`, `work_area`, `work_type`, `valid_from`, `applicant_name`.
- `site_id` is mandatory for public submits and must be a numeric string; the API rejects missing/invalid `site_id` for `public_user` flows.
- Optional but supported: `valid_to` (defaults to `valid_from` when absent), `risk_level` (defaults to `LOW`), hazards and control measures text, selected work types list, JSON checklists (`checklist_data`, `checklist_hot_work`, `checklist_cold_work`, `checklist_confined_space`, `declaration_checklist`, `supporting_docs_checklist`), `certificate_numbers`, `hazardous_activities`, contractor/applicant extended fields, and `workers` array. None of the checklist/worker fields are currently mandatory for public submission.

#### Time defaults on submit
- Date-only inputs are auto-expanded by the API: if `valid_from` is `YYYY-MM-DD`, it becomes `YYYY-MM-DD 08:00:00`; if `valid_to` is date-only, it becomes `YYYY-MM-DD 17:00:00`.
- If `valid_to` is omitted, it defaults to the same date/time base as `valid_from` (after expansion). Document this behavior when building clients that send date-only values.

#### PDF generation (interim guidance)
- Prefer `api/ptw_pdf.php` for new integrations. It supports:
  - Stable download via `GET api/ptw_pdf.php?action=get_file&ptw_id={permitId}`
  - Template-based generation with `type=html_template` that references the canonical design in `api/pdf/templates/ptw_form_html_replica.html`
  - A guarded `type=preview_pdf` that only generates after final approval (ACTIVE/COMPLETED)
- `api/ptw_pdf_simple.php` exists for smoke tests and quick basic PDFs; avoid wiring new product flows to it.
- Until a final template decision is made, use `type=html_template` with `ptw_form_html_replica.html` to keep output consistent. We can switch the backing template later without changing client URLs.
