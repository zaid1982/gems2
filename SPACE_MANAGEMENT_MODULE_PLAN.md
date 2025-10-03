# Space Management Module – Implementation Plan

## Scope snapshot
- Provide CRUD for physical spaces with metadata (name, location, category/type, area, occupancy, status).
- Link existing assets (from `api/asset.php`) to spaces without duplicating asset records.
- Offer calendar-based reservation flows with auto-approval, admin cancellation, and user notifications.
- Surface spaces (directory + detail views), assets, photos, floorplans, and availability state transitions.
- Respect existing auth/site filtering, audit logging, notification, and file storage conventions.

## Data model (MySQL)
- `spc_space`
  - `space_id` (PK), `site_id`, `space_name`, `space_location_id`, `space_category_id`, `space_type_id`, `space_area`, `space_capacity`, `space_status` (`ACTIVE|AVAILABLE|RESERVED|DISABLED`), `space_desc`, `created_by`, `created_date`, `modified_by`, `modified_date`.
  - `space_status` should be constrained via lookup table (`ref_space_status`) or validated in code to align with constants.
- `spc_space_asset`
  - Junction table: `space_asset_id` (PK), `space_id`, `asset_id`, `linked_by`, `linked_date`.
  - Enforce FK to `ast_asset.asset_id` and cascade delete when space removed.
- `spc_space_media`
  - Store attachments: `media_id`, `space_id`, `media_type` (`PHOTO|FLOORPLAN`), `file_path`, `file_name`, `mime_type`, `uploaded_by`, `uploaded_date`.
  - Reuse existing file storage helpers in `General.php` (e.g., `General::uploadBase64`) to place assets under `/upload/space/`.
- `spc_reservation`
  - `reservation_id` (PK), `space_id`, `site_id`, `reservation_start`, `reservation_end`, `reservation_status` (`RESERVED|CANCELED`), `requested_by`, `requested_by_name`, `requested_by_contact`, `special_request`, `auto_approved_at`, `canceled_by`, `canceled_at`, `cancel_reason`.
  - Index by `space_id`, `reservation_start/reservation_end` for availability checks.
- Optional lookup tables: `ref_space_category`, `ref_space_type`, `ref_space_location` seeded via migration for dropdowns.

## Backend API design
- Main entry: `api/space.php` following the General/Db stack patterns (see `api/wo_v3.php`).
  - Include `General.php`, `DbMysql.php`, and a new domain class `api/class/Space.php` + trait `SiteFilterTrait`.
  - Parse routes with `General::getUrlArr($_SERVER['REQUEST_URI'], 'space')`.
  - Require JWT; apply site filtering via trait (admins roles `1,10` bypass).
  - Response envelope `{ success, result, error, errmsg }` with transactions for writes.
- Supporting classes:
  - `api/class/Space.php`: encapsulate CRUD, asset linking, reservation logic, audit hooks, and helper queries.
  - `api/class/SpaceReservation.php` (optional split) for reservation-specific operations and validation.
- Routes (examples):
  - `GET /api/space.php` → list spaces (filter by site/status, includes linked assets + latest reservation status).
  - `GET /api/space.php/{spaceId}` → detailed space profile (metadata, assets, photos, floorplan link, upcoming reservations).
  - `POST /api/space.php` → create space (payload described below) with transaction + `saveAudit('70', ...)` (reserve new audit codes).
  - `PUT /api/space.php/{spaceId}` → update metadata/status, manage linked assets (pass delta arrays), save audit per action.
  - `DELETE /api/space.php/{spaceId}` → soft delete or disable (decide per business rule; prefer status toggle to `DISABLED`).
  - `POST /api/space.php/{spaceId}/reservation` → create reservation (auto-approve); returns reservation id/status.
  - `GET /api/space.php/{spaceId}/reservation?from=...&to=...` → fetch reservations window for calendar.
  - `PUT /api/space.php/reservation/{reservationId}/cancel` → admin cancellation.
- Payload conventions:
  - Space create/update: `{ spaceName, siteId, locationId, categoryId, typeId, area, capacity, status, description?, assetIds: [], photos: [{name, base64}], floorplan?: {name, base64} }`.
  - Reservation create: `{ startDateTime, endDateTime, specialRequest?, contactInfo? }`.
- Availability enforcement:
  - Prior to auto-approval, query overlapping `RESERVED` reservations for the same space.
  - Status transitions:
    - Space `ACTIVE` implies visible but may be blocked; `AVAILABLE` is the default bookable state; `RESERVED` is derived (space has active reservation) — store as `space_status` but update when reservation created/canceled.
    - Reservation `RESERVED` on create; `CANCELED` on admin action.

## Integration touchpoints
- Asset module:
  - Validate incoming `assetIds` exist via `Class_asset->check_asset($assetId)` or direct `ast_asset` lookup.
  - For non-admins, verify assets belong to the same `site_id`; reuse site filtering logic similar to `api/asset.php`.
  - Consider helper method `Space::syncAssets($spaceId, $assetIds)` that diffs existing vs. new and writes to `spc_space_asset`.
- Notifications:
  - On reservation create: use `NotiWeb` to alert admins (type 1 or new type). For canceled reservations, notify the requester (via email or noti) using templates similar to WO flows (`Email::prepare`, `Noti::prepare`).
  - Define new email/noti templates + parameters in DB (document expected placeholders).
- Auditing:
  - Assign new audit codes (e.g., `70` create space, `71` update, `72` disable, `73` reservation create, `74` reservation cancel) and add to `Class_constant` messages.
- File storage:
  - Store uploads under `/upload/space/{spaceId}/` (photos) and `/upload/space/{spaceId}/floorplan/`.
  - Record relative paths in `spc_space_media`; reuse download endpoints (or add `GET /api/space_media.php/{mediaId}` similar to existing file serving patterns).

## Frontend deliverables
- New page `space.html` matching existing layout conventions (see `asset.html`).
- JS module `js/pages/main_space.js`:
  - Fetch list via `GET /api/space.php`; render DataTable with filters (site, status, category).
  - Button actions: add/edit space (modal), view details, open calendar.
  - Respect role checks (`mzIsRoleExist('1,10')`) for admin-only features (create, disable, cancel reservation).
- Modal scripts:
  - `js/pages/modal_space_form.js`: handles create/update, asset linking (multi-select list populated from `GET api/asset.php?contractId=...` or new endpoint `assets?siteId=...`).
  - `js/pages/modal_space_assets.js`: optional dedicated modal for linking/unlinking assets.
  - `js/pages/modal_space_reservation.js`: user reservation form with date/time pickers, special request textarea.
- Calendar view:
  - Reuse FullCalendar (`js/vendor/full-calendar-5.9.0`) to show bookings; load events via `GET /api/space.php/{spaceId}/reservation`.
- Detail drawer/page:
  - Display metadata, asset list (with asset names pulled via existing asset API), photo carousel, floorplan download.
- Toasts, loading spinners, validation follow same helpers from `js/common.js` / `mzAjaxRequest`.

## Progress tracker
- [x] DB migrations — `create_space_module.sql` added with tables, FKs, lookup seeds, and `ref_document` entry.
- [x] Core constants — `Constant::$space` & `Constant::$spaceReservation` messages prepared.
- [x] Domain classes — scaffold `api/class/Space.php` with CRUD, asset sync, media handling, reservations.
- [x] API endpoint — `api/space.php` added with routing, JWT enforcement, standard envelope, transactions.
- [ ] Notifications — wire email/noti/notiWeb hooks once template IDs confirmed.
- [~] Frontend pages/modules — initial `space.html` and `js/pages/main_space.js` added to list/filter spaces; forms/modals pending.
- [ ] Calendar integration — load reservations via FullCalendar with status colors.
- [ ] File upload lifecycle — ensure dedicated helpers/cleanup for space media.
- [ ] Testing — cover CRUD, reservations, permissions, availability conflicts.
- [ ] Documentation — refresh `.github/copilot-instructions.md` & related READMEs post-implementation.

## Workflow outline
1. **DB migrations**: create tables (`spc_space`, `spc_space_asset`, `spc_space_media`, `spc_reservation`, lookups) with indexes and FKs. **Status:** ✅ script ready, pending execution.
2. **Domain classes** (`Space`) implemented with reservations and media; optional `SpaceReservation` class can be split later. **Status:** ✅ done (first pass).
3. **API endpoint** `api/space.php` implemented with route handling and site filtering hooks. **Status:** ✅ done (first pass).
4. **Notification templates**: add email/noti/notiWeb entries for reservation create/cancel.
5. **Frontend pages/modules** (`space.html`, `main_space.js`, modals) following existing UI scaffolding.
6. **Calendar integration**: configure FullCalendar event sources, status color mapping.
7. **File upload handling**: extend `General.php` if needed for new directory type, ensure cleanup on delete.
8. **Testing**: manual + automated (if available) covering space CRUD, asset linkage, overlapping reservations, cancellation.
9. **Documentation**: update `.github/copilot-instructions.md` and relevant READMEs once implementation stabilizes.

### Quick test notes
- Backend API: `GET /api/space.php` lists spaces; `POST`/`PUT`/`DELETE` and reservation routes available per design above.
- Frontend: open `http://localhost/gems2/space.html` (ensure login header/menus load). Non-admin users will be locked to their site; admins can select site if options populated.

## Open points / assumptions
- Site scoping: assume spaces belong to a single `site_id`; multi-site spaces not supported.
- Reservation requester identity: use JWT `userId` and `fullName`; allow optional contact info override in payload.
- Auto-approval: no workflow queue; immediate insert with status `RESERVED`.
- Availability: when space `status = DISABLED`, block new reservations; existing reservations remain but flagged on frontend.
- Photos/floorplans storage limits: follow existing upload size constraints (check `General::$maxFileSize`).
- Email/noti templates to be defined by business (IDs TBD); placeholders include `[spaceName]`, `[reservationDate]`, `[cancelReason]`.

Next up: wire notifications on reservation create/cancel, build frontend pages (`space.html`, `main_space.js`), and add focused tests for overlapping windows and site access.

Document owner: keep this plan updated as requirements evolve. Once implementation begins, track progress in the project board or relevant README.
