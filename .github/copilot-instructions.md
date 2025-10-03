## GEMS2 — AI Agent Quick Guide

Purpose: make agents productive fast in this PHP monolith by documenting real patterns, workflows, and gotchas used here.

### Architecture
- Stack: Plain PHP under Apache/XAMPP at `htdocs/gems2`.
  - Frontend: `*.html` pages use modules in `js/pages/` with helpers in `js/common.js`.
  - Backend APIs: `api/*.php`; shared classes in `api/class/`, traits in `api/trait/`.
- Two API styles coexist:
  1) Core (e.g., Work Orders) built on `General.php` + `DbMysql.php` + traits.
  2) PTW module uses `api/function/Class_*` helpers with their own flows.
- Auth: JWT required by default. Public endpoints explicitly use an `ext` path segment to bypass. Toggle debug with `Constant::$isLogged`.

### Core API conventions (General/DbMysql stack)
- Routing: `$apiName` from filename → `$urlArr = General::getUrlArr($_SERVER['REQUEST_URI'], $apiName)`.
- Envelope: always return `{ success, result, error, errmsg }` then `json_encode` and exit.
- Transactions: wrap writes in `DbMysql::beginTransaction()`/`commit()`; `rollback()` on exceptions.
- Site filtering: apply via `SiteFilterTrait` (admins roles `1,10` are exempt). Add `/site/{siteId}` variants and validate access.
- Example (`api/wo_v3.php`):
  - `GET /api/wo_v3.php/pending_assign/site/{siteId}` → `WoTask::pendingAssignBySite($siteId)`.
  - `PUT /api/wo_v3.php/submit_assign/{woTaskId}` with `{ woTaskAssignedTo }` → submit, email/noti, audit.
  - `GET /api/wo_v3.php/preview_mrf_pdf/{woTaskId}` → ensure PDF via TCPDF and return link.

### Frontend patterns
- Each page wires a JS module in `js/pages/*`.
  - Role/site helpers: `mzGetUserInfoByParam('siteId')`, `mzIsRoleExist('1,10')` from `js/common.js`.
  - Non-admin UIs disable site dropdowns and call site-scoped endpoints like `.../endpoint/site/${userSite}`.

### Developer workflows
- Run locally: serve via Apache/XAMPP and open `http://localhost/gems2/home.html` (or any page).
- Dependencies: `composer install` (Excel via `phpoffice/phpspreadsheet`, etc.).
- PDFs: Work Orders use TCPDF at `api/pdf/tcpdf_include.php`; PTW PDFs via `api/ptw_pdf.php` with `api/pdf/templates/ptw_form_html_replica.html`.
- Database: apply root `*.sql` and curated bundles under `maintenance/` (e.g., staging deployment scripts) with your MySQL client.
- Deploy: `./deploy_production.sh` (rsync) — excludes `developer/`, `.git/`, logs. Never deploy `developer/`.

### Work Orders (WO) highlights
- Main API: `api/wo_v3.php`; domain/services: `WoTask`, `WflTask`, `Email`, `Noti`, `NotiWeb`, `WoMrfPdf`.
- Public complaint create: `POST /api/wo_v3.php` with `{ siteId, complaint, image? }` → generate WO no, create workflow, notify, audit.
- Lists: `pending_assign|submitted_assign|pending_verify|submitted_verify` (+ `/site/{siteId}` variants).
- Admin update: `PUT /api/wo_v3.php/update_by_admin/{woTaskId}`; details via `GET /api/wo_v3.php/{woTaskId}`.
- Patterns: standard envelope, wrap writes in transactions, call `saveAudit` on state changes.

### Email and notifications
- Email: `api/class/Email.php` → `prepare(receiverId, templateId, params, fullName?, email?)` enqueues to `email_send`.
- Mobile push: `api/class/Noti.php` → `prepare(receiverId, notiTextId, params)` enqueues to `noti_send` (requires `sys_user.userToken`).
- Web noti: `api/class/NotiWeb.php` → `insert/getByUserId/delete` (types: 1=WO assign, 2=WR assign, 3=WO verify, 4=WO images zip).
- Frontend polls `noti_web` (via rewrite to `api/noti_web.php`); delete for type 4 also removes file.

### PTW module overview
- Files: `api/ptw.php` (create/update/list + supervisor), `api/ptw_approve.php` (SHE/FM + extend/cancel/suspend/close), `api/ptw_approval.php` (view/inline approve), logic in `api/function/f_ptw.php`.
- Public form: `ptw_form.html` posts to `api/ptw.php` with `public_user=Public User` and numeric `site_id`.
- Auth/site: `Class_login::check_jwt(Authorization)` with dev token allowances; site from `sys_user.site_id` unless public submit.
- Status flow: `DRAFT → PENDING_SUPERVISOR → PENDING_SHE → PENDING_FM → ACTIVE` → `EXTENDED` or closure path; history records `action_type`.
- Numbers: RQ at submit `RQPTW{site}{yymmdd}{seq}`; PTW at supervisor approval `PTW{site}{yymmdd}{seq}`.

### Asset Management (PTW-style)
- APIs: `api/asset.php` (CRUD + totals/list by contract), refs: `api/asset_group.php`, `api/asset_category.php`, `api/asset_type.php`, `api/asset_brand.php`, `api/asset_model.php`.
- JWT mandatory; non-admins constrained to `sys_user.site_id` for reads. Totals (`type=total_asset`) force site to user site.
- Actions: `PUT action=save|submit|update|deactivate|activate`, audits `57-61`; delete audits `62`. Refs update version/audits `31-35`.
- Frontend: `asset.html` + `js/pages/main_asset.js`; ref modals like `js/pages/modal_asset_group.js` using `mzAjaxRequest('asset_group.php', ...)` and toasts.

Key files to study: `api/wo_v3.php`, `api/class/General.php`, `api/class/DbMysql.php`, `api/trait/SiteFilterTrait.php`, `api/ptw*.php`, `api/function/f_ptw.php`, `js/common.js`, `deploy_production.sh`, `developer/README.md`.
## GEMS2 – AI Agent Quick Guide

Purpose: Make agents productive fast in this PHP monolith by documenting the real patterns, workflows, and conventions used here.

### Architecture at a glance
- Stack: Plain PHP under Apache/XAMPP (`htdocs/gems2`). Frontend pages in `*.html` with JS modules in `js/pages/`. Backend APIs in `api/*.php` with core classes in `api/class/` and shared traits in `api/trait/`.
- Two API styles coexist:
  1) Core APIs (e.g., Work Orders) use `General.php` + `DbMysql.php` + trait helpers.
  2) PTW module uses `api/function/*` classes (`Class_*`) and its own helpers.
- Auth: JWT required by default; public endpoints use a path segment `ext` to bypass. Debug logs toggle with `Constant::$isLogged`.

### Core API conventions (General/DbMysql stack)
- Route entry: `require_once` class files → `$apiName` from filename → `$urlArr = General::getUrlArr($_SERVER['REQUEST_URI'], $apiName)` → enforce JWT unless URL starts with `ext`.
- Response envelope is always `{ success, result, error, errmsg }` and JSON-encoded at the end.
- Transactions: wrap writes in `DbMysql::beginTransaction()`/`commit()` with rollback on exceptions.
- Site filtering: Use `SiteFilterTrait` via `General`; admins (roles `1,10`) are exempt. See `SITE_FILTERING_IMPLEMENTATION.md`.
- Example (from `api/wo_v3.php`):
  - `GET /api/wo_v3.php/pending_assign/site/{siteId}` → `WoTask::pendingAssignBySite($siteId)` (validates site access).
  - `POST /api/wo_v3.php` (no path) creates a public complaint (requires `siteId`).
  - `GET /api/wo_v3.php/preview_mrf_pdf/{woTaskId}` generates/returns MRF PDF via TCPDF.

### Frontend patterns
- Each page `*.html` uses a module in `js/pages/*`. Shared helpers live in `js/common.js`:
  - `mzGetUserInfoByParam('siteId')` to get user site, `mzIsRoleExist('1,10')` for admin checks.
  - Non-admins call site-scoped endpoints like `.../endpoint/site/${userSite}` and UI site dropdowns are disabled.

### Developer workflows
- Run locally: host under Apache/XAMPP and open `http://localhost/gems2/home.html` (or other pages).
- PHP deps (Excel): run `composer install` (see `composer.json` for `phpoffice/phpspreadsheet`).
- PDFs: Work Orders use TCPDF (`api/pdf/tcpdf_include.php`). PTW PDFs use `api/ptw_pdf.php` with HTML template `api/pdf/templates/ptw_form_html_replica.html`.
- Database: apply root `*.sql` files and curated bundles in `maintenance/` (e.g., `staging-deployment-db-schema-safe.sql`) using your MySQL client.
- Deploy: use `./deploy_production.sh` (rsync). Excludes `developer/`, `.git/`, logs, etc. Never deploy `developer/` (see `developer/README.md`).

### Adding a new core API endpoint
1) Create `api/feature_x.php` and include needed class/trait files.
2) Parse route with `General::getUrlArr`, enforce JWT unless `ext` path is intentional.
3) Return the standard envelope; log via `General::logDebug` when `Constant::$isLogged` is true.
4) For site-scoped data, add `/site/{siteId}` variant that calls `*BySite($siteId)` and validates access.

### Work Orders (WO) lifecycle
- Entry/API: `api/wo_v3.php` with `WoTask` (domain), `WflTask` (workflow), `Email`, `Noti`, `NotiWeb`, and `WoMrfPdf` (PDF).
- Public complaint create (no path): `POST /api/wo_v3.php` with JSON `{ siteId, complaint, image? }`.
  - Server selects Public User for the site, validates `cli_site.groupId` vs `sys_user_role(roleId=6)` group, optionally saves image upload, generates WO number (`WoTask::generateNo(siteId)`), creates workflow (`WflTask::createNew`), submits initial complaint, then emails/notifies recipients and writes audit.
- Assignment lists:
  - `GET /api/wo_v3.php/pending_assign` and `/pending_assign/site/{siteId}`
  - `GET /api/wo_v3.php/submitted_assign` and `/submitted_assign_total` (+ `/site/{siteId}` variants)
- Verification lists:
  - `GET /api/wo_v3.php/pending_verify` and `/pending_verify/site/{siteId}`
  - `GET /api/wo_v3.php/submitted_verify` and `/submitted_verify_total` (+ `/site/{siteId}` variants)
- Assign work: `PUT /api/wo_v3.php/submit_assign/{woTaskId}` with body `{ woTaskAssignedTo }`.
  - Loads task, binds workflow by transaction, validates allowed state (`checkValidity`), submits assignment, triggers emails/notifications, saves audit; response sets `errmsg` to assign message.
- Reassign: `PUT /api/wo_v3.php/reassign/{woTaskId}` with body `{ woTaskAssignedTo }`.
- Reject public complaint: `PUT /api/wo_v3.php/reject_complaint/{woTaskId}` with body `{ remark }`.
  - Valid only when `woTaskTypeInit === 6` (Public Complaint). Sends email to public contact and saves audit.
- Admin update: `PUT /api/wo_v3.php/update_by_admin/{woTaskId}` body `{ ...fields }` → updates fields and audits.
- Details and helpers:
  - `GET /api/wo_v3.php/{woTaskId}` for full details
  - `GET /api/wo_v3.php/by_assetId/{assetId}`
  - `GET /api/wo_v3.php/material_list/{id}`
- MRF PDF: `GET /api/wo_v3.php/preview_mrf_pdf/{woTaskId}` ensures PDF exists (`WoMrfPdf::createPdf`) and returns link via `WoTask::getPdfLink`.
- Patterns to mirror:
  - Standard envelope `{success,result,error,errmsg}`; wrap writes in transactions; call `saveAudit` on state changes.
  - Site variants use `/site/{siteId}` and must validate access; non-admin frontends call the site-scoped endpoints.
  - Frontend modules: `js/pages/main_wo_assign.js`, `js/pages/main_wo_verify.js`, `js/pages/main_mrf.js` use `mzGetUserInfoByParam` and `mzIsRoleExist` for site/admin logic.

### Email and notifications
- Services (core stack):
  - Email: `api/class/Email.php` → `prepare(receiverId, templateId, params, fullName?, emailAddress?)`
    - Loads `email_template` + `email_parameter`, validates all placeholders exist in `params`, substitutes (also `[fullName]`), resolves recipient from `sys_user`/`sys_user_profile` unless overridden, then enqueues into `email_send`.
  - Mobile push: `api/class/Noti.php` → `prepare(receiverId, notiTextId, params)`
    - Loads `noti_text` + `noti_parameter`, validates placeholders, resolves `sys_user.userToken`, enqueues into `noti_send`.
  - Web notifications: `api/class/NotiWeb.php` → `insert(type, userId, info)` / `getByUserId()` / `delete(id)`
    - Writes to `noti_web` with display metadata. Types: 1=WO assign, 2=WR assign, 3=WO verify, 4=WO images zip link. `delete` removes the linked file for type 4.
- Queues and delivery:
  - Enqueue tables: `email_send`, `noti_send` (processed by background jobs; PTW uses helpers in `api/function/f_email.php` with batching and deletion post-send). Most API flows enqueue; they do not send synchronously.
- API endpoints and frontend:
  - `.htaccess` rewrites `noti_web` → `api/noti_web.php`. Frontend (`js/common.js`) calls `GET noti_web/by_userId` to fetch and `DELETE noti_web/{id}` to clear.
- Usage patterns (examples from `api/wo_v3.php`):
  - On public complaint create: For each recipient →
    - `Email->prepare(recipientUserId, 4, { task_no })`
    - `Noti->prepare(recipientUserId, 5, { task_no })`
    - `NotiWeb->insert(isWr ? 2 : 1, recipientUserId, woTaskNo)`
  - On assignment submit: template/text chosen by WR vs WO
    - Email template: `11` (WR) or `5` (WO); Noti text: `12` (WR) or `6` (WO)
  - On reject public complaint: send to public email without a user account
    - `Email->prepare(1, 10, { task_no, comment }, publicName, publicEmail)`
- Gotchas:
  - All template placeholders must be provided or `prepare` throws.
  - Mobile push requires `sys_user.userToken`; missing tokens result in no insert.
  - Toggle debug logs via `Constant::$isLogged` to trace enqueues.

### PTW module (Permit to Work)
- Files: `api/ptw.php` (create/update/list + supervisor actions), `api/ptw_approve.php` (SHE/FM approvals + extend/cancel/suspend/close), `api/ptw_approval.php` (view-mode + inline approvals), logic in `api/function/f_ptw.php`, PDFs in `api/ptw_pdf*.php`.
- Public entry: `ptw_form.html` posts to `api/ptw.php` with `public_user=Public User` and must include `site_id` (server assigns request number and routes workflow).
- Auth: `Class_login::check_jwt(Authorization)` with dev allowances (e.g., `Bearer valid_test_token_for_fm_dashboard` or fallback `userId=1` for local). Site scope from `sys_user.site_id` unless public submit provides `site_id`.
- Workflow/status: `DRAFT → PENDING_SUPERVISOR → PENDING_SHE → PENDING_FM → ACTIVE`; then `EXTENDED` or closure (`PENDING_CLOSURE → COMPLETED`). Other transitions: `REJECTED`, `CANCELLED`, `SUSPENDED`, `EXPIRED`. History table records `action_type` events.
- Numbering: request at submit (`RQPTW{site}{yymmdd}{seq}`) and permit at supervisor approval (`PTW{site}{yymmdd}{seq}`) based on per-site daily sequence.
- Endpoints (examples):
  - GET `api/ptw.php?action=list|statistics|dashboard_data|details&permit_id=...`
  - POST `api/ptw.php` with `action=supervisor_approve|supervisor_reject|supervisor_return_for_modification` (or create when no `action`)
  - POST `api/ptw_approve.php` with `action=she_approve|she_reject|fm_approve|fm_reject|request_extend|approve_extend|request_close|approve_close|request_cancel|approve_cancel|request_suspend|approve_suspend`
  - View mode via `api/ptw_approval.php` (also supports `public_token`).
- Public submit rules: required `work_description`, `work_area`, `work_type`, `valid_from`, `applicant_name`, and numeric `site_id`. Date-only `valid_from/valid_to` are expanded to 08:00/17:00.
- PDFs: Prefer `api/ptw_pdf.php` with `type=html_template`; guarded preview generates only after final approval.

### Key files to study
- Core: `api/wo_v3.php`, `api/class/General.php`, `api/class/DbMysql.php`, `api/trait/SiteFilterTrait.php`.
- PTW: `api/ptw.php`, `api/ptw_approve.php`, `api/ptw_approval.php`, `api/function/f_ptw.php`, `api/ptw_pdf.php`.
- Frontend helpers: `js/common.js` (role/site helpers). Deploy/tools: `deploy_production.sh`, `developer/README.md`, `SITE_FILTERING_IMPLEMENTATION.md`.

### Asset Management
- Backend APIs (PTW-style classes):
  - `api/asset.php` (assets CRUD + totals/list by contract)
  - Reference/masters: `api/asset_group.php`, `api/asset_category.php`, `api/asset_type.php`, `api/asset_brand.php`, `api/asset_model.php`
- Auth & site filtering:
  - JWT via `Class_login::check_jwt` is required.
  - Non-admins (not roles `1,10`) are site-restricted: asset reads validate that the asset/contract belongs to the user’s `sys_user.site_id`; totals (`type=total_asset`) force `siteId` to the user’s site if provided differently.
- Core endpoints (examples):
  - GET `api/asset.php?assetId={id}` → single asset (site-validated for non-admins)
  - GET `api/asset.php?contractId={id}` → list assets by contract (site-validated)
  - GET `api/asset.php?type=total_asset&clientId={cid}&siteId={sid}` → totals (site forced for non-admins)
  - POST `api/asset.php` → create, then internal update; audits with codes `56`
  - PUT `api/asset.php?assetId={id}` with `action=save|submit|update|deactivate|activate` → updates status/fields; audits `57/58/59/60/61` and success messages from `Class_constant`
  - DELETE `api/asset.php?assetId={id}` → deletes; audit `62`
- Reference data CRUD patterns (same envelope/transactions): `api/asset_group.php` supports GET list/single, POST add, PUT update/deactivate/activate, DELETE with audit/version updates (`31-35`). Similar files exist for category/type/brand/model.
- Frontend modules:
  - Main pages: `asset.html` + `js/pages/main_asset.js` (DataTables with filters; non-admins use their site to filter contract options via `mzGetUserInfoByParam` and `mzIsRoleExist`).
  - Reference modals: `js/pages/modal_asset_group.js` (add/edit/delete), and similar for category/type/brand/model; use `mzAjaxRequest('asset_group.php', ...)` style endpoints and show toasts.
  - Patterns: filter options use `mzOption`/`mzOptionStop`, search via DataTables, action columns toggled by status, and role checks hide add buttons for non-privileged roles.
