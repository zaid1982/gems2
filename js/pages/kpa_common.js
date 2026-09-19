/**
 * Shared helpers for the KPI & APD screens. Mirrors WasteCommon so both
 * modules read the same way.
 */
function KpaCommon() {
    const self = this;
    this.caps = { isAdmin: false, canAdmin: false, canEntry: false, canView: false, siteId: 0, assignedPiIds: [] };
    this.sites = [];

    this.MONTHS = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];

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
        el.classList.add('custom-select', 'gems-plain-select', 'browser-default');
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

    this.passBadge = function (isPass) {
        if (isPass === null || isPass === undefined) {
            return '<span class="badge-status neutral"><i class="fas fa-minus"></i>Not calculated</span>';
        }
        return isPass
            ? '<span class="badge-status completed"><i class="fas fa-check-circle"></i>Met</span>'
            : '<span class="badge-status incomplete"><i class="fas fa-circle-xmark"></i>Not met</span>';
    };

    this.piStatusBadge = function (status) {
        if (status === 'SUBMITTED') { return '<span class="badge-status completed"><i class="fas fa-lock"></i>Submitted</span>'; }
        if (status === 'NOT_STARTED') { return '<span class="badge-status neutral"><i class="fas fa-minus"></i>Not started</span>'; }
        return '<span class="badge-status in-progress"><i class="fas fa-pen"></i>Draft</span>';
    };

    this.evalStatusBadge = function (status) {
        if (status === 'COMPLETED') { return '<span class="badge-status completed"><i class="fas fa-check-circle"></i>Completed</span>'; }
        if (status === 'NOT_STARTED') { return '<span class="badge-status neutral"><i class="fas fa-minus"></i>Not started</span>'; }
        return '<span class="badge-status in-progress"><i class="fas fa-hourglass-half"></i>Open</span>';
    };

    this.userLabel = function (row) {
        const name = ((row.userFirstName || '') + ' ' + (row.userLastName || '')).trim();
        return name ? name + ' (' + row.userName + ')' : row.userName;
    };

    this.queryParam = function (name) {
        return new URLSearchParams(window.location.search).get(name);
    };

    this.escape = function (value) {
        if (value === null || value === undefined) { return ''; }
        return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    };

    this.dtButtons = function (title) {
        return [
            { extend: 'colvis', columns: ':not(.noVis)', fade: 400, text: '<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility' },
            { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2', text: '<i class="fas fa-print"></i>', title: title, titleAttr: 'Print', exportOptions: typeof mzExportOpt !== 'undefined' ? mzExportOpt : {} },
            { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0', text: '<i class="fas fa-file-excel"></i>', title: title, titleAttr: 'Excel', exportOptions: typeof mzExportExcelOpt !== 'undefined' ? mzExportExcelOpt : {} },
            { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-3', text: '<i class="fas fa-file-pdf"></i>', title: title, titleAttr: 'PDF', orientation: 'landscape', exportOptions: typeof mzExportOpt !== 'undefined' ? mzExportOpt : {} }
        ];
    };
}
