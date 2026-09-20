/**
 * Shared helpers for the KPI & APD screens. Mirrors WasteCommon so both
 * modules read the same way.
 *
 * kpa_common.js is also loaded by the unmigrated MDB page pages/kpi_in.html.
 * Helpers that that page calls (fillSelect, fillYears, fillMonths, badges,
 * apiGet) must keep working on both stacks: Tabler classes when
 * body.gems-tabler is present, the original MDB/plain-select path otherwise.
 */
function KpaCommon() {
    const self = this;
    this.caps = { isAdmin: false, canAdmin: false, canEntry: false, canView: false, siteId: 0, assignedPiIds: [] };
    this.sites = [];

    this.MONTHS = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];

    this.isTabler = function () {
        return !!(document.body && document.body.classList.contains('gems-tabler'));
    };

    this.api = function (path, method, data) {
        return mzAjaxRequest3('kpa/' + path, method || 'GET', data || '');
    };

    this.apiGet = function (path) {
        return mzAjaxRequest2('kpa/' + path, 'GET');
    };

    /** Non-blocking write. Does not show the full-page overlay. */
    this.apiAsync = function (path, method, data) {
        return mzFetch('kpa/' + path, method || 'GET', data || {}, false, true);
    };

    this.debounce = function (fn, wait) {
        let timer = null;
        return function () {
            const ctx = this;
            const args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(ctx, args); }, wait || 400);
        };
    };

    this.setSaveState = function (id, state, message) {
        const el = document.getElementById(id);
        if (!el) { return; }
        if (!state || state === 'idle') {
            el.hidden = true;
            el.className = 'autosave-state';
            el.textContent = '';
            return;
        }
        const labels = { saving: 'Saving…', saved: 'Saved', error: message || 'Save failed' };
        el.hidden = false;
        el.className = 'autosave-state is-' + state;
        el.textContent = labels[state] || message || '';
    };

    this.loadCaps = function () {
        self.caps = self.apiGet('me') || self.caps;
        return self.caps;
    };

    this.loadSites = function () {
        self.sites = self.apiGet('site') || [];
        return self.sites;
    };

    this.fillSelect = function (id, rows, valueKey, labelFn, placeholder, selected) {
        const el = document.getElementById(id);
        if (!el) { return; }
        if (self.isTabler()) {
            el.classList.add('form-select');
            el.classList.remove('custom-select', 'gems-plain-select', 'browser-default');
        } else {
            el.classList.add('custom-select', 'gems-plain-select', 'browser-default');
        }
        el.innerHTML = '';
        if (placeholder !== null && placeholder !== undefined) {
            const first = document.createElement('option');
            first.value = '';
            first.text = placeholder;
            el.appendChild(first);
        }
        (rows || []).forEach(function (row) {
            const opt = document.createElement('option');
            opt.value = row[valueKey];
            opt.text = typeof labelFn === 'function' ? labelFn(row) : row[labelFn];
            if (String(selected) === String(row[valueKey])) { opt.selected = true; }
            el.appendChild(opt);
        });
    };

    this.fillMonths = function (id, selected) {
        const rows = self.MONTHS.map(function (name, i) { return { v: i + 1, n: name }; });
        self.fillSelect(id, rows, 'v', function (r) { return r.n; }, null, selected);
    };

    this.fillYears = function (id, selected, back, forward) {
        const now = new Date().getFullYear();
        const rows = [];
        for (let y = now + (forward || 1); y >= now - (back || 5); y--) { rows.push({ y: y }); }
        self.fillSelect(id, rows, 'y', function (r) { return String(r.y); }, null, selected || now);
    };

    this.monthName = function (month) {
        const i = Number(month) - 1;
        return self.MONTHS[i] || String(month);
    };

    this.fmtMoney = function (value) {
        const n = Number(value);
        if (!Number.isFinite(n)) { return 'RM 0.00'; }
        return 'RM ' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    this.fmtNumber = function (value, digits) {
        const n = Number(value);
        if (!Number.isFinite(n)) { return '—'; }
        const d = digits === undefined ? 2 : digits;
        return n.toLocaleString(undefined, { minimumFractionDigits: d, maximumFractionDigits: d });
    };

    /** Achievement cell: blank when nothing has been calculated yet. */
    this.fmtActual = function (value, unit) {
        if (value === null || value === undefined || value === '') {
            return '<span class="text-muted">—</span>';
        }
        const suffix = unit === 'BEI' ? '' : '%';
        return self.fmtNumber(value, 2) + suffix;
    };

    this.badge = function (kind, label) {
        if (window.GemsUI) { return GemsUI.badge(kind, label); }
        if (self.isTabler()) {
            return '<span class="badge gems-badge gems-badge-' + kind + '">' + label + '</span>';
        }
        const status = {
            success: 'completed',
            warning: 'in-progress',
            danger: 'incomplete',
            secondary: 'neutral',
            info: 'in-progress'
        };
        const icon = {
            success: 'fa-check-circle',
            warning: 'fa-hourglass-half',
            danger: 'fa-circle-xmark',
            secondary: 'fa-minus',
            info: 'fa-pen'
        };
        return '<span class="badge-status ' + (status[kind] || 'neutral') + '"><i class="fas ' +
            (icon[kind] || 'fa-minus') + '"></i>' + label + '</span>';
    };

    this.passBadge = function (isPass) {
        if (isPass === null || isPass === undefined) {
            return self.badge('secondary', 'Not calculated');
        }
        return isPass
            ? self.badge('success', 'Met')
            : self.badge('danger', 'Not met');
    };

    this.piStatusBadge = function (status) {
        if (status === 'SUBMITTED') { return self.badge('success', 'Submitted'); }
        if (status === 'NOT_STARTED') { return self.badge('secondary', 'Not started'); }
        return self.badge('warning', 'Draft');
    };

    this.evalStatusBadge = function (status) {
        if (status === 'COMPLETED') { return self.badge('success', 'Completed'); }
        if (status === 'NOT_STARTED') { return self.badge('secondary', 'Not started'); }
        return self.badge('warning', 'Open');
    };

    this.activeBadge = function (status) {
        return Number(status) === 1
            ? self.badge('success', 'Active')
            : self.badge('secondary', 'Inactive');
    };

    this.userLabel = function (row) {
        const name = ((row.userFirstName || '') + ' ' + (row.userLastName || '')).trim();
        return name ? name + ' (' + row.userName + ')' : row.userName;
    };

    this.queryParam = function (name) {
        return new URLSearchParams(window.location.search).get(name);
    };

    this.escape = function (value) {
        if (window.GemsUI) { return GemsUI.escape(value); }
        if (value === null || value === undefined) { return ''; }
        return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    };

    // Canonical P2/P3A DataTables chrome. Delegate to GemsUI on Tabler;
    // keep the local bodies so kpi_in.html (MDB) still works without GemsUI.
    this.dtDom = window.GemsUI ? GemsUI.dtDom : "<'d-none'f>r<'table-responsive't><'card-footer d-flex align-items-center py-2'i<'ms-auto'p>>";
    this.dtDomButtons = window.GemsUI ? GemsUI.dtDomButtons : "<'d-none'B>r<'table-responsive't><'card-footer d-flex align-items-center py-2'i<'ms-auto'p>>";
    this.dtEmpty = function (icon, emptyText, zeroText) {
        if (window.GemsUI) { return GemsUI.dtEmpty(icon, emptyText, zeroText); }
        return $.extend({}, _DATATABLE_LANGUAGE, {
            emptyTable: '<div class="gems-empty-state"><i class="fas ' + (icon || 'fa-inbox') + '"></i><p>' + emptyText + '</p></div>',
            zeroRecords: '<div class="gems-empty-state"><i class="fas fa-filter"></i><p>' + (zeroText || 'No records match the current filters.') + '</p></div>'
        });
    };

    this.actionBtn = function (opts) {
        if (window.GemsUI) { return GemsUI.actionBtn(opts); }
        const href = opts.href ? ' href="' + opts.href + '"' : ' type="button"';
        const tag = opts.href ? 'a' : 'button';
        const extra = opts.extra || '';
        const id = opts.id ? ' id="' + opts.id + '"' : '';
        return '<' + tag + href + id + ' class="btn gems-btn-action ' + (opts.tint || '') + ' ' + (opts.cls || '') +
            '" data-toggle="tooltip" title="' + opts.title + '" aria-label="' + (opts.label || opts.title) + '" ' + extra +
            '><i class="' + opts.icon + '"></i></' + tag + '>';
    };

    this.bindDtTooltips = function (tableId) {
        if (window.GemsUI) { return GemsUI.bindDtTooltips(tableId); }
        const $table = $(tableId);
        $table.on('draw.dt', function () {
            if (typeof window.gemsInitTooltips === 'function') {
                window.gemsInitTooltips($table.find('tbody')[0]);
            }
        });
    };

    this.dtButtons = function (title) {
        if (window.GemsUI) { return GemsUI.dtButtons(title); }
        const btnClass = self.isTabler()
            ? 'btn btn-outline-secondary btn-sm'
            : 'btn btn-outline-grey btn-sm px-2 ml-0';
        return [
            { extend: 'colvis', columns: ':not(.noVis)', fade: 400, text: '<i class="fas fa-columns"></i>', className: btnClass, titleAttr: 'Column Visibility' },
            { extend: 'print', className: btnClass, text: '<i class="fas fa-print"></i>', title: title, titleAttr: 'Print', exportOptions: typeof mzExportOpt !== 'undefined' ? mzExportOpt : {} },
            { extend: 'excelHtml5', className: btnClass, text: '<i class="fas fa-file-excel"></i>', title: title, titleAttr: 'Excel', exportOptions: typeof mzExportExcelOpt !== 'undefined' ? mzExportExcelOpt : {} },
            { extend: 'pdfHtml5', className: btnClass, text: '<i class="fas fa-file-pdf"></i>', title: title, titleAttr: 'PDF', orientation: 'landscape', exportOptions: typeof mzExportOpt !== 'undefined' ? mzExportOpt : {} }
        ];
    };
}
