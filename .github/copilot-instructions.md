## GEMS2 — AI Agent Quick Guide

### Architecture & Workflows
- Plain PHP app served by Apache/XAMPP (`htdocs/gems2`). Pages in the repo root load JS controllers from `js/pages/*` while shared utilities live in `js/common.js`.
- Navigation and modal fragments are stored in `html/` and injected with `$('.includeHtml').each(...).load('html/'+id.substr(2)+'.html')` before `initiatePages()` runs.
- APIs under `api/*.php` split into the General/DbMysql stack and PTW helpers (`api/function/Class_*`). Responses must follow `{ success, result, error, errmsg }` and enforce JWT unless a route starts with `ext`.
- Role/site enforcement is handled client-side through `mzGetUserInfoByParam`/`mzIsRoleExist` and server-side via `SiteFilterTrait`; many list endpoints expose `/site/{siteId}` variants.

### Styling System Summary
- Bootstrap 4 + Material Design for Bootstrap (MDB Pro) provide the base skin (`css/bootstrap.min.css`, `css/mdb.min.css`), including body skins like `light-blue-skin` and gradient helpers such as `aqua-gradient`.
- Project-specific utilities (spacing tweaks, bold table headers, hover color pairs) live in `css/style.css`; examples: `.pl-4_5`, `.th-strong`, `.hoverable`.
- Vendor add-ons ship precompiled: DataTables CSS under `js/vendor/datatables/css`, MDB extras under `css/addons*/` and `css/modules/`.
- Font Awesome Pro 6.5.1 is bundled in `css/fontawesome-pro-6.5.1-web`; use `fa`/`fas`/`fa-duotone` classes instead of embedding SVGs manually.
- Complex pages embed scoped `<style>` blocks near the top of each `.html` (see `space_calendar.html` for `:root` tokens or `ptw_form.html` for view-mode styling).
- There is no Tailwind/PostCSS pipeline. The `scss/` directory mirrors MDB sources but nothing compiles automatically—edit `css/style.css` for quick overrides and only recompile SCSS if you deliberately refresh vendor bundles.

### Global Layout & Utilities
- Typical skeleton loads shared chrome before page content:

```html
<!-- asset_type.html -->
<body class="fixed-sn light-blue-skin">
  <header>
    <div class="includeHtml" id="h-nav_left"></div>
    <div class="includeHtml" id="h-nav_top"></div>
  </header>
  <main>
    <div class="container-fluid">
      <section class="mb-5 mt-lg-5">
        <div class="card card-cascade narrower">
          <div class="view view-cascade gradient-card-header aqua-gradient ...">
            ...
```

- Pages call `ShowLoader()` → `initiatePages()` → instantiate their `Main*` module once the fragments have loaded.
- Core utility snippet from `css/style.css`:

```css
/* css/style.css */
.pl-4_5 { padding-left: 40px !important; }
.th-strong th { font-weight: 900 !important; }
.success-lighter-hover { color: #98F874; transition: .4s; }
.success-lighter-hover:hover { color: #35BD02; }
```

- Example token usage on `space_calendar.html`:

```css
:root {
  --primary-color: #4f46e5;
  --primary-dark: #3730a3;
}
.page-header {
  background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
  border-radius: 16px;
}
```

### Component Patterns
- **Buttons & tabs** – Combine MDB classes for contour and color. Example from `asset_type.html`: `<button class="btn btn-outline-white btn-rounded btn-sm px-2 material-tooltip-main">…</button>`.
- **Cards & headers** – Wrap sections in `card card-cascade narrower` with gradient headers (`view view-cascade gradient-card-header aqua-gradient`) and `z-depth-*` shadows (`home.html`, `asset.html`).
- **Forms** – Use `md-form md-outline` structures with `mdb-select` for dropdowns; re-run `$('.mdb-select').materialSelect()` after options change (`js/pages/main_asset.js`).
- **Tables/DataTables** – Markup: `<table class="table table-hover mb-0 display responsive">` and header row `.th-strong`. Status badges are rendered in JS as `<span class="badge badge-pill ${statusColor} z-depth-2">…</span>` (`js/pages/main_asset.js`, rows ~63-96).
- **Modals** – Follow MDB pattern: `modal-header primary-color white-text` for creation flows, `warning-color` for destructive actions (`space_calendar.html`, `ptw_form.html`).
- **Extending hover variants** – Duplicate the lighten/darken pattern in `css/style.css` when introducing new color families:

```diff
 /* css/style.css */
 .success-lighter-hover { color: #98F874; transition: .4s; }
 .success-lighter-hover:hover { color: #35BD02; }
+.success-darker-hover { color: #00C851; transition: .4s; }
+.success-darker-hover:hover { color: #007c32; }
```

### Page-by-Page Styling Notes
| Route/Page | Layout wrapper | Notable classes/components | Local module/style files | Gotchas |
| --- | --- | --- | --- | --- |
| `home.html` + `js/pages/main_home.js` | `sectionHmeMain` inside `card card-cascade` stat grid | `cascading-admin-card`, stacked `mdb-color` navbars, progress bars | Relies on `css/style.css`; no inline overrides | Background images under `img/background/` supply card art—keep ids `lblHme*` intact for JS |
| `asset.html` + `js/pages/main_asset.js` | `card card-cascade narrower` split into filter panel + chart + table | `mdb-select md-outline` filters, striped status list, DataTables export buttons | Inline `.title-box*` styles near top + global `css/style.css` utilities | Re-init `mdb-select` after `mzOptionStop`; search input expects `.md-form` wrapper |
| `asset_type.html` + `js/pages/main_asset_type.js` | Dual gradient cards with stacked DataTables | `btn-outline-white` toolbars, popovers, nested cards | No inline CSS; depends on `css/style.css` utilities and `html/modal_*` fragments | `sectionAtyModel` stays hidden until a type is selected; tooltips require `material-tooltip-main` |
| `wo_assign.html` + `js/pages/main_wo_assign.js` | Navbar ribbons plus `z-depth-3` table cards under `sectionWssMain` | `btnWssTab` pseudo-tabs, `table-custom-badge`, cyan accent bars | Global utilities only | Load `html/section_wo.html` before `SectionWo()` or styles/scripts for modal cards are missing |
| `ptw_form.html` + `js/pages/main_ptw_form.js` | Wide form inside `#divPtwPageWidth` with steppers | MDB Stepper (`css/addons-pro/steppers.min.css`), `view-mode-*` helpers, signature pad styling | Extensive inline CSS block governs view/edit state + `css/style.css` | File is >6k lines—update both view and public mode styles; keep gradient colors aligned with approval roles |
| `space_calendar.html` + `js/pages/main_space_calendar.js` | Gradient `.page-header` + `.main-content-card` around FullCalendar | `btn-outline-white btn-rounded` actions, FullCalendar container `#spcCalendar` | Inline `:root` token block + `js/vendor/full-calendar-5.9.0/lib/main.min.css` | JS waits for `.includeHtml` fragments (see pending counter) before calling `boot()`—don’t move the loader |

### Do / Don’t
- **Do** add reusable tweaks to `css/style.css`; follow existing naming like `.mt-4_5` for fractional spacing helpers.
- **Do** load shared fragments through `.includeHtml` before instantiating page controllers; many styles rely on nav markup being present.
- **Do** re-run MDB initialisers (`materialSelect`, `initPhotoSwipeFromDOM`, etc.) after injecting dynamic HTML so styles attach.
- **Don’t** edit `css/mdb.min.css` or other vendor bundles directly—override in `css/style.css` or recompile the SCSS sources if you must.
- **Don’t** drop body skin classes (`fixed-sn light-blue-skin`); they control sidenav theming and scrollbar styling.
- **Don’t** introduce new icon fonts; stick with the bundled Font Awesome Pro set.

### Build/Test Styling
- No automated CSS build—edits to `css/style.css` or page-level `<style>` blocks take effect on refresh. Back up the file before large changes.
- Optional rebuild: compile MDB in a temp file and diff before replacing production assets, e.g.

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/gems2
sass scss/mdb.scss css/mdb.custom.css
```

  (requires local Sass CLI; only needed when you intentionally update MDB sources.)
- Preview pages via Apache at `http://localhost/gems2/<page>.html`. For DataTables/FullCalendar screens, click through filtering/export actions to confirm gradients and hover helpers remain legible.

---
Let me know what feels unclear or if you need deeper styling notes for other pages.
