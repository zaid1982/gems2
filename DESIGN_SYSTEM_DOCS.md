# GEMS2 Design System Modernization Log

## Phase 0 – Foundations

### Step 1 · Style Inventory (Completed)
- **Stack**: Bootstrap 4 + Material Design for Bootstrap (MDB Pro), Font Awesome Pro 6.5.1, jQuery (3.7 + migrate), DataTables, various MDB add-ons (Steppers, FullCalendar, Prime Slider, Castos embeds).
- **Custom assets**: Global overrides in `css/style.css`; legacy SCSS sources under `scss/` mirror MDB but are not part of any active build pipeline.
- **Layout primitives**: Navigation and modal fragments reside in `html/`; pages live at repo root and load them through `.includeHtml` before running page controllers in `js/pages/*`.
- **Theming**: Heavy use of MDB gradients (`aqua-gradient`, `blue-gradient`, etc.), `z-depth-*` shadows, and body skin `light-blue-skin`; inline styles frequently set backgrounds, spacing and typography tweaks.

### Step 2 · Corporate Brand Tokens (Completed)

| Token | Hex / Value | Primary Usage Notes |
| --- | --- | --- |
| `primary` | `#0055B8` | Hero banners, key nav accents, CTA focus states |
| `secondary` | `#00ADA8` | Callouts, hover fills, icon wrappers, tab accents |
| `tertiary` | `#09AEB2` | Hotspot overlays, gradient midpoints, hover transitions |
| `accent` | `#CEEFF0` (often translucent) | Ghost buttons, chip backgrounds, subtle panels |
| `ink-strong` (`--e-global-color-917f2c2`) | `#243746` | Headings, heavy text, dark iconography |
| `ink` (`--e-global-color-text`) | `#5B676F` | Body copy, form labels, default link color |
| `ink-muted` (`--e-global-color-c3c7e01`) | `#7E8F9A` | Subtitles, table meta text, divider copy |
| `cloud` (`--e-global-color-7801c8a`) | `#C3C3C3` | Neutral borders, icon outlines, disabled states |
| `mist` (`--e-global-color-608D7CE`) | `#E7EAEC` | Section backgrounds, cards, data tiles |
| `white` | `#FFFFFF` | Base canvas, inverted icons, card fills |
| `alert` | `#DF4E4F` | Error states, destructive actions (Prime Slider / CTAs) |
| `action-dark` | `#69727D` | Default Elementor button background, search bars |

**Typography scale**
- Primary headings: `Lato`, weight `900`, base size `48px` (clamps to 42/36/32px on smaller breakpoints), letter spacing `1px`, uppercase in hero locks.
- Secondary headings: `Lato` 900 at `34px` (down to `28px` on tablet), used for section titles and key stats.
- Body/UI text: `Lato` regular (`400`) at `14px`, line-height `1.6`; provides baseline for forms, nav, icon lists.
- Accent numerals & badges: `Lato Black` (900) for counters, KPI digits, uppercase stats.
- Special-case font: `Poppins` appears in podcast/player embeds; treat as module-specific override.

### Step 3 · UI Baseline & Pain Points (Completed)

#### Application shell
- **Body** uses `light-blue-skin` with MDB’s sidenav/scrollbar styling; background leans teal and conflicts with corporate navy/teal palette.
- **Left nav (`html/nav_left.html`)**: Fixed sidenav with `sn-bg-4` gradient, 2019 copyright, low-resolution logo at 80px. Active links rely on MDB defaults with minimal contrast.
- **Top nav (`html/nav_top.html`)**: Double-nav pattern; icons in default MDB gray, dropdowns use warm gradients and rounded badges inconsistent with corporate flat aesthetic. Notification dropdown mixes multiple gradient classes (`winter-neva-gradient`, `warm-flame-gradient`).
- **Global utility classes**: Widespread inline `style="background-image: …"`, bespoke spacing helpers (`.mt-4_5`, `.pl-4_5`) and gradients. Tokens are not centralized, making global restyle brittle.

#### Dashboard (`home.html`)
- **Filters**: Dual stacked navbars with `mdb-color` gradients and white text; filter fields reuse MDB `md-form` components with teal labels. Inputs lack consistent spacing and are hard to scan.
- **Hero metrics**: Cascade cards use background image overlays (`img/background/pen_book.jpg`) plus inline gradient overlays. Icons use mixed color tokens (`blue accent-1`, `warning-color`) not aligned with corporate palette.
- **Charts**: Plain white cards with `z-depth-4`; minimal padding results in cramped chart legends once Highcharts/AmCharts render. Backgrounds contrast poorly against teal shell.
- **Tables**: DataTables wrappers leverage gradient headers (`blue-gradient`), `btn-outline-white` actions, and multiple inline tooltip/popover triggers. Column selectors rely on `mdb-select`, which conflicts with DataTables toolbar for accessibility.

#### Asset Management (`asset.html`)
- **Search pane**: Inline `<style>` block defines `.title-box*` wrappers. Gradient header (`rgba-cyan-strong`) and `z-depth-4` panel stand out against otherwise neutral content, producing inconsistent visual hierarchy.
- **Filter controls**: Heavy use of `mdb-select md-outline` components with default teal focus states. Labels float inconsistently on load due to JS initialization timing.
- **Data table section**: Reuses gradient header pattern with aqua/white outline buttons. Table columns specify inline widths; responsive behavior depends on DataTables responsive plugin, leading to horizontal scroll on smaller breakpoints.

#### Forms, modals & reusable sections
- Modals in `html/` use MDB gradient headers (`primary-color`, `warning-color`). Many embed inline width/height adjustments, hindering responsive tuning.
- Forms mix `md-outline` and standard Bootstrap inputs; error states default to MDB red `#d9534f`, not the corporate alert color.
- Stepper-based flows (e.g., `ptw_form.html`) include massive inline `<style>` blocks with hard-coded colors (`#007bff`, `#1abc9c`, etc.).

#### General pain points & opportunities
- **Color debt**: Legacy MDB palettes (`mdb-color`, `blue-gradient`, random accent classes) dominate; none map to corporate tokens, complicating future theming.
- **Typography drift**: Default fonts fall back to system sans-serif; `Lato` is not loaded globally, so headings/body text do not reflect corporate typography.
- **Shadow/Depth overuse**: Frequent `z-depth-*` layers create busy visuals and inconsistent lighting.
- **Inline styles**: Background images, spacing tweaks, and hover effects are scattered inline, increasing refactor effort.
- **Component duplication**: Similar card headers and toolbar patterns are hand-coded per page instead of shared partials/components.
- **Accessibility**: Contrast issues (white-on-gradient, pale labels) and small font sizes (8px copyright) fail WCAG.

#### Baseline capture checklist
- **Primary screens**: `home.html`, `asset.html`, `ppm_management.html`, `ptw_form.html`, `gamification.html`, `attendance.html`.
- **Key modals**: `html/modal_create_complaint.html`, `html/modal_asset_type.html`, `html/modal_ppm.html`.
- **Specialty layouts**: `space_calendar.html` (FullCalendar), `wo_assign.html` (tabbed ribbon), `ptw_form.html` (stepper & signatures).
- Recommended workflow: load each page through Apache (`http://localhost/gems2/<page>.html>`), capture full-height screenshots pre-refactor, and catalog asset references (background images, icons) for modernization mapping.

#### Quick win targets for Phase 1
1. Swap global body/nav colors to corporate `primary/secondary` while preserving layout – verifies token layer.
2. Load `Lato` (regular, bold, black) via self-hosted or Google Fonts and apply to headings/body.
3. Centralize gradient/header styles in a new tokenized SCSS/CSS file to eliminate inline overrides on dashboards and tables.
4. Replace `btn-outline-white` action clusters with accessible button set using `primary`, `secondary`, and neutral tokens.

---

_Maintained by the Phase 0 modernization effort. Update this log as subsequent phases introduce tokens, components, and rollout schedules._

## Phase 1 – Theme Scaffold

### Step 1 · Token Layer (Completed)
- Declared global CSS custom properties for the corporate palette, neutral ramp, typography scale, and motion primitives directly inside `css/style.css`.
- Added helper utility classes (`.theme-text-*`, `.theme-bg-*`, `.theme-gradient-primary`, `.theme-surface`) to accelerate incremental refactors without touching legacy markup immediately.
- Established baseline radius, shadow, and transition values to guide future component restyles.

### Step 2 · Base Theme Wiring (Completed)
- Loaded the `Lato` 400/700/900 webfont stack and applied it globally via the new typography tokens.
- Updated body, heading, and link defaults to use corporate colors and spacing, improving consistency without removing MDB components yet.
- Re-skinned the fixed sidenav and top navbar with corporate gradients, contrast-safe text colors, and refined shadows to preview the new brand direction across the shell.

### Step 3 · Component Quick Wins (Completed)
- Introduced token-aware button overrides (`.btn-*`, `.btn-outline-*`, `.btn-outline-white`) with refreshed hover states, pill rounding support, and consistent typography.
- Harmonised card/gradient headers to use the new primary gradient, softened shadows, and subtle hover elevation for dashboard tiles.
- Updated badges and table headers to align with the neutral palette, reducing reliance on legacy MDB accent colors while retaining structure.

### Step 4 · Pilot Page – `home.html` (Completed)
- Removed the legacy `light-blue-skin` body theme and swapped dashboard navbars to tokenized gradient utilities.
- Rebuilt the four KPI stat cards with the new `theme-surface` shells, unified gradient headers, branded progress bar, and neutral body text.
- Updated chart containers, tables, and DataTable export button placeholders to inherit the refreshed card treatments without inline background images.

### Step 5 · Rollout Page – `asset.html` (Completed)
- Migrated the asset management shell off `light-blue-skin`, applying `theme-surface` cards and the standardized gradient header.
- Updated search/filter panels, status list, and chart container to leverage tokenized typography and neutral surfaces while leaving JS behavior untouched.
- Normalized the DataTable toolbar markup to use the refreshed button group styling, matching the dashboard experience.

### Step 6 · Rollout Page – `ppm_management.html` (Completed)
- Extended the modern shell and gradient header treatments to the PPM management screen, removing legacy `light-blue-skin`, `aqua-gradient`, and `z-depth-*` dependencies.
- Applied `theme-surface` containers to the filter, chart, and table modules while reinforcing typography hierarchy with `theme-text-ink-strong` utilities.
- Brought DataTable toolbars in line with the standardized button-group markup and tidied supporting CSS to keep component overrides consistent across pages.

### Step 7 · Rollout Page – `ppm_group.html` (Completed)
- Converted the PPM group administration workflow to the new theme, swapping out remote Font Awesome assets, `light-blue-skin`, and MDB gradient classes for tokenized surfaces and headers.
- Introduced the reusable `.theme-icon-chip` helper and applied it to the hero card, aligning iconography with corporate gradients while trimming legacy `z-depth-*` shadows.
- Restyled action buttons and DataTable wrappers to match the refreshed component palette and reinforced typography cues inside the detail panel for consistency with prior rollouts.

### Step 8 · Rollout Page – `ptw_form.html` (Completed)
- Wired the permit-to-work experience into the modern shell by loading local Font Awesome assets, tagging the body with `.ptw-page`, and moving all primary cards onto `theme-surface` wrappers.
- Added PTW-specific utilities (`.ptw-card`, `.ptw-tab-header`, `.ptw-card-header--*`) to `css/style.css`, overriding legacy gradients with token-driven variants and standardising tab, header, and approval section treatments.
- Maintained existing inline rules for complex stepper behaviours while layering higher-specificity overrides to align focus states, approval banners, and CTA gradients with the corporate palette.

### Step 9 · Rollout Page – `wo_assign.html` (Completed)
- Refreshed the work-order assignment hub by replacing `light-blue-skin` navbars and `z-depth-*` cards with tokenised `theme-surface` wrappers and gradient headers.
- Introduced WO-specific helpers (`.wo-overview`, `.wo-tab-button`, `.wo-card-header`) to restyle pseudo-tabs, counters, and list panels while preserving existing JavaScript behaviours.
- Normalised tab button states using the legacy `lighten-2` toggle so the updated styling drops in without touching `main_wo_assign.js`.

### Step 10 · Rollout Page – `ppm_import.html` (Completed)
- Migrated the import workflow onto `theme-surface` panels, replacing inline gradients and neutral blocks with token-driven `.ppm-card` and `.import-step` treatments.
- Centralized the step, badge, and drop-zone styling in `css/style.css`, delivering gradient headers, accent-aware alert badges, and branded hover states.
- Retained existing JavaScript behaviour while tightening button spacing and elevating key actions through the new gradient palette.

### Step 11 · Rollout Page – `ppm_reschedule.html` (Completed)
- Replaced the legacy `light-blue-skin` shell and MDB gradient stack with a `.ppm-reschedule-page` treatment using the standardized gradient header and theme surfaces.
- Introduced filter and table scaffolds (`.ppm-reschedule-search`, `.ppm-reschedule-table-card`, `.ppm-reschedule-table__*`) to tame inline styles, flex the toolbar layout, and enforce consistent column widths.
- Swapped remote icon assets for the bundled Font Awesome Pro set while keeping existing DataTables and popover behaviours untouched.

### Step 12 · Rollout Page – `ppm_set_form.html` (Completed)
- Traded the blue-grey navbar and inline cyan accent bars for a `.ppm-set-toolbar` gradient hero and `.ppm-set-card` wrappers tied to the tokenized palette.
- Centralized header, action, and table styling in `css/style.css`, adding icon blocks, column width helpers, and responsive handling for the assets list.
- Retained existing form and DataTables logic while refreshing CTAs and back navigation to the modern button set.

### Step 13 · Rollout Page – `ppm_set_management.html` (Completed)
- Replaced the stacked legacy navbars and `light-blue-skin` shell with a gradient `.ppm-set-management-hero`, contract filter surface, and tokenized list card.
- Added dedicated management helpers in `css/style.css` for hero iconography, contract dropdown summary, and table column sizing while keeping IDs consumed by existing JS controllers.
- Preserved DataTables behaviours and modal wiring, ensuring the refreshed visuals drop-in without touching `main_ppm_set.js`.

### Step 14 · Modal Refresh – `html/modal_ppm_set.html` (Completed)
- Rebuilt the PPM set modal with a gradient header, icon chip, and modern close control via the new `.ppm-set-modal` utility block.
- Updated body and footer treatments to align with `theme-surface`, swapping button colors to the standard primary/outline-secondary pair while retaining all field IDs.
- Centralized modal-specific styles in `css/style.css` so the modal can align with future token updates without altering the JavaScript controller.

### Step 15 · Modal Refresh – `html/modal_ppm_set_select_asset.html` (Completed)
- Introduced a `.ppm-set-asset-modal` shell with gradient header, subtitle, and icon chip to replace the blue-grey header and cyan card ribbon.
- Restyled the available-assets list into a tokenized card with column width helpers and responsive button grouping while preserving table IDs for `ModalPpmSetSelectAsset`.
- Shifted modal footer buttons to the primary/secondary palette, wiring the new styles into `css/style.css` for consistent future updates.

### Step 16 · Rollout Page – `ppm_asset.html` (Completed)
- Replaced the dual legacy navbars and cyan card ribbons with a gradient `.ppm-asset-hero`, tokenized contract filter surface, and themed list card.
- Added `.ppm-asset-*` helpers to `css/style.css` to manage hero iconography, dropdown spacing, and table column widths without disturbing existing IDs.
- Left the underlying DataTables configuration intact so `MainPpmAsset` and related controllers continue working without modification.

### Step 17 · Modal Refresh – `html/modal_ppm_asset.html` (Completed)
- Restyled the modal wrapper using `.ppm-asset-modal`, layering a gradient header, icon chip, and descriptive subtitle in place of the legacy modal-notify skin.
- Updated form spacing, select dropdown themes, and footer buttons to the primary/outline-secondary palette without touching IDs consumed by the page controller.
- Codified all modal-specific overrides in `css/style.css` for future maintenance.

### Step 18 · Modal Refresh – `html/modal_ppm_asset_select.html` (Completed)
- Introduced a `.ppm-asset-select-modal` scaffold with tokenized header, summary card, and dropdown styling, replacing the modal-notify gradient and inline grey accents.
- Applied new summary card treatments and column width helpers while keeping the select IDs intact for `ModalPpmAssetSelect`.
- Logged the supporting styles in `css/style.css`, covering responsive footer buttons and hover states.

### Step 19 · Detail Section Refresh – `html/section_ppm_asset.html` (Completed)
- Converted the asset detail view into a gradient hero with tokenized meta stats, replacing legacy aqua headers and heavy shadows.
- Introduced `.ppm-asset-detail__*` helpers for toolbar layout, meta cards, table wrappers, and responsive handling in `css/style.css`.
- Preserved existing IDs and data attributes so `MainPpmAsset` continues driving the section without JavaScript changes.

### Step 20 · Modal Refresh – `html/modal_ppm.html` (Completed)
- Rebuilt the PPM task creation modal with a tokenized hero header, icon chip, and contextual subtitle instead of the legacy modal-notify skin.
- Grouped read-only asset metadata and scheduling controls into `ppm-task-modal__section` blocks with refreshed select, datepicker, and bulk assignment styling.
- Updated button palette and layout to the primary/secondary standard while keeping form field IDs for `ModalPpm` JavaScript handlers.

### Step 21 · Modal Refresh – `html/modal_ppm_group.html` (Completed)
- Converted the group creation modal to the tokenized layout with gradient header, icon chip, and contextual subtitle.
- Split legacy field stack into `ppm-group-modal__section` blocks for context and assignment details, keeping IDs intact for `ModalPpmGroup` logic.
- Updated disabled inputs, `Report To` selector, and footer actions to the standardized typography and button treatments.

### Step 22 · Modal Refresh – `html/modal_ppm_user.html` (Completed)
- Applied the tokenized modal shell with hero header, icon chip, and subtitle to the group user assignment flow.
- Grouped readonly context fields and user selection into separate `ppm-group-user-modal__section` blocks while reusing existing IDs.
- Updated MDB select styling, disabled field treatments, and footer buttons to align with the refreshed PPM modal pattern.

### Step 23 · Modal Refresh – `html/modal_ppm_reschedule.html` (Completed)
- Rebuilt the reschedule dialog with the gradient hero header and contextual subtitle, replacing the modal-notify skin.
- Organized task metadata and rescheduling controls into `ppm-reschedule-modal__section` blocks to mirror other PPM modals.
- Retained existing IDs while refreshing select, datepicker, and footer button styling to the tokenized palette.

### Step 24 · Detail Section Refresh – `ppm_group.html` (Completed)
- Replaced the legacy detail pane with the new `.ppm-group-detail__*` scaffold, introducing gradient hero header, icon chip, and structured body layout.
- Grouped readonly metadata, report-to options, status control, and actions into tokenized cards while preserving all form field IDs.
- Updated the user assignment list to share the same styling system, including refreshed toolbar buttons and bordered table wrapper.

### Step 25 · Section Rollout – PPM Group Boards (`ppm_group.html`) (Completed)
- Converted the four group listing cards to the `.ppm-group-board__*` scaffold with gradient headers, icon chips, and tokenized action buttons.
- Applied consistent table wrappers, borders, and responsive handling while retaining DataTable IDs for the technician, supervisor, engineer, and WO views.
- Provided variant gradients per board and centralized styling in `css/style.css` for easier future adjustments.

## Phase 2 – Asset Administration & Global Chrome

### Step 26 · Navigation Shell Consolidation
- Refresh `html/nav_left.html` and `html/nav_top.html` with the Phase 1 token system, introducing shared `.app-shell__*` helpers for brand-compliant side/top navigation, notification trays, and responsive states.
- Replace legacy gradient classes and inline spacing with centralized styles in `css/style.css`, ensuring accessibility (contrast, focus, keyboard traversal) and long-title support.
- Document shell patterns for reuse across future modules and prepare hooks for dark-mode/compact variants in later phases.

### Step 27 · Shared Utility Modal Suite
- Restyle global utility modals (`html/modal_change_password.html`, `html/modal_confirm_delete.html`, `html/modal_pdf.html`) to align with the new modal scaffolding, including gradient headers, iconography, and standardized button palettes.
- Create `.app-modal__*` utilities covering confirmation, alert, and large-content layouts; rewire footer actions for consistent spacing and mobile responsiveness.
- Audit cross-page invocations to validate the refreshed markup works with existing JavaScript controllers and loader flows.

### Step 28 · Asset Category Console Refresh (`asset_category.html`)
- Migrate the category list view onto `theme-surface` wrappers with hero header, filter ribbon, and summary chips consistent with the PPM treatments.
- Normalize DataTables toolbars, search filters, and status badges using new `.asset-console__*` helpers inside `css/style.css` while keeping DOM hooks intact.
- Remove inline gradients/shadows and document the finalized layout patterns for other asset submodules.

### Step 29 · Asset Group Console Refresh (`asset_group.html`)
- Apply the tokenized hero, filter summary, and grouped card structure to the group administration screen, replacing legacy aqua gradients and stacked cards.
- Harmonize the group detail panes and tabular summaries with the shared `.asset-console__table` patterns for responsive handling.
- Reconcile toolbar buttons and modal launchers with the standardized button palette introduced in Phase 1.

### Step 30 · Asset Type Console Refresh (`asset_type.html`)
- Rebuild the dual-card layout with gradient headers, icon chips, and filter controls guided by the asset console utilities.
- Standardize table density, hover states, and export toolbar to match the modernized DataTables experience.
- Align inline tooltips/popovers with the refreshed color palette and ensure Material Select instances are reinitialized after DOM updates.

### Step 31 · Asset Brand Console Refresh (`asset_brand.html`)
- Convert the brand maintenance board to the new hero + surface pattern, including updated summary stats and action clusters.
- Introduce responsive wrappers for the brand mapping tables and centralize badge color logic into `css/style.css`.
- Validate modal triggers and pagination components continue to work with the simplified markup.

### Step 32 · Asset Modal Suite Modernization
- Restyle every asset-related modal (`modal_asset_category.html`, `modal_asset_group.html`, `modal_asset_type.html`, `modal_asset_brand.html`, `modal_asset_model.html`) with the standardized gradient header and `.asset-modal__section` scaffolding.
- Align form spacing, disabled state styling, and footer button arrangements with the Phase 1 modal conventions to maintain a consistent cross-module experience.
- Ensure modal-specific scripts (validators, select refreshers) still function and add documentation for any new utility classes introduced.

### Step 33 · Asset Detail Drawer & Section Refresh (`html/section_asset_details.html`)
- Transform the asset detail section into a tokenized hero with metrics bar, tabbed content, and responsive information cards, mirroring the PPM detail treatments.
- Introduce `.asset-detail__*` utilities for info blocks, attachment galleries, and audit trails, reducing reliance on inline styles and ad-hoc spacing helpers.
- Confirm the refreshed section integrates cleanly with `MainAsset` controllers and modal hand-offs (view/edit flows).

### Step 34 · Asset DataTable Toolkit Harmonization
- Build a shared DataTable mixin covering toolbar layout, column density, export button groups, and responsive breakpoints for all asset-related screens.
- Replace page-specific overrides with the consolidated toolkit, minimizing duplication and ensuring consistent hover/focus behavior.
- Update documentation with usage guidelines and identify any residual pages requiring opt-out handling (e.g., ultra-wide technical tables).

### Step 35 · Documentation & QA Hardening
- Capture before/after screenshots for each asset module, update `DESIGN_SYSTEM_DOCS.md` with implementation notes, and log any tech-debt follow-ups.
- Draft a smoke-test checklist covering navigation, modals, CRUD flows, and DataTables interactions across the asset suite.
- Prepare a dependency audit for Phase 3 planning (e.g., evaluate jQuery plugin replacements, MDB dep updates) to keep modernization momentum.
