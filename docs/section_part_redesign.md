# Section Part Module Redesign Log (Dec 2025)

## Context
- Scope: Replace legacy Bootstrap layout in `html/section_part.html` with the modern module shell (gradient hero, metric row, dual-panel body, stacked data cards).
- Goal: Align the Store Part detail page with the latest design system, unblock the Inventory Reviewer flow, and consolidate all widgets (gallery, forms, tables) under a single responsive surface.
- Owners: Frontend/UI (section_part.html + section_part.js) with Store Inventory API consumers (`/wo_request/request_details`, `/wo_parts/wo_parts_list`).

## Layout Map
- **Module Shell** – `.module-part-shell` wraps the entire page, providing the new lavender background, 32px radius, and drop shadow.
- **Hero Header** – `.module-page-header` now mirrors `license.html`: eyebrow copy (`Store Inventory`), title `txtSptDescription`, subtitle `txtSptItemType`, tag group for site/store/asset/status, and six icon buttons (`btnSptBack`, `btnSptEnable`, `btnSptDisable`, `btnSptSave`, `btnSptTransfer`, `btnSptDelete`).
- **Metric Row** – `.metrics-row` hosts three `.metric-card`s feeding `lblTopTotalInStore`, `lblTopTotalILocked`, and `lblTopTotalAvailable`.
- **Body Grid** – `.part-body-grid` splits into:
  - **Side Panel**: gallery card (`divSptImages`) and form card (`formSpt`) retaining all MDB inputs plus inline validation spans.
  - **Main Panel**: filter card + five stacked `.module-data-card`s for part list, pending requests, check-in, check-out, and return history tables.
- **Filter Card** – Search input `txtSptSearch`, status select `optSptStatus`, and summary pill (`lblSptCount`, `lblSptUpdated`).
- **Tables** – Each DataTable keeps its historical ID (`dtSptPartList`, `dtSptPending`, `dtSptCheckIn`, `dtSptCheckOut`, `dtSptReturnHistory`) and now lives inside `.module-table-wrapper` to support the mobile card treatment.

## Interaction & Data Hooks
- Header buttons rely on existing JS handlers; tooltips preserved via `data-toggle="tooltip"`.
- Status pill `sptStatusPill` toggles styling via `data-state` (JS responsible for applying `disabled`).
- Summary counters live within `.summary-pill` to keep auto-refresh messaging consistent with other modernized modules.
- Export/action placeholders (`sptPartListActions`, `sptPendingActions`, `sptCheckInActions`, `sptCheckOutActions`, `sptReturnActions`) remain intact for DataTables button injection.
- Mobile responsive behavior hides `<thead>` and remaps `td::before` labels; ensure DataTables `fnRowCallback` populates `data-label` attributes when customizing rows.

## Styling & Accessibility Notes
- Gradient header reused from the Modern UI blueprint; includes decorative `::after` circle and ensures minimum contrast > 4.5:1.
- Buttons and inputs received rounded radii (12-14px) per system spec; no custom fonts introduced.
- `.module-part-page .dataTables_filter` forced hidden to prevent duplicate search controls.
- Media queries at 1199.98px (stack panels) and 767.98px (card tables) keep layout readable; update JS-driven column vis if needed.

## QA / Regression Checklist
1. Load `section_part.html` on desktop + mobile viewport; verify no overlapping legacy containers.
2. Exercise each header button → confirm tooltips show, actions remain wired via `section_part.js`.
3. Search + status filter propagate to `dtSptPartList` (listen to `keyup` and change events from JS).
4. Validate DataTables init still supplies `data-label` values for mobile cards; inspect `dtSptPending` and `dtSptReturnHistory` rows.
5. Confirm gallery lightbox (`divSptImages`) maintains MDB markup and images inherit rounded corners.
6. Run through enable/disable/threshold edits to ensure form validation labels still display in-line.

## Follow-Ups / Dependencies
- Backend reviewer workflow still pending: new statuses, API payloads, and gating instructions documented separately (Inventory Reviewer brief).
- Consider extracting inline `<style>` into SCSS once the rest of Store Inventory adopts the module shell, to avoid duplication with `license.html` patterns.
- When reviewer endpoints land, append documentation regarding new buttons/pills (e.g., `btnSptSubmitReviewer`) to this log.
