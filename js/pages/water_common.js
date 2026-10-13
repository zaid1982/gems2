/**
 * Shared helpers for the Water Monitoring screens.
 * Same call pattern as energy_common.js, aimed at the water API.
 */
function WaterCommon() {
    const self = this;
    this.caps = { isAdmin: false, canRecord: false, canSetup: false, canView: false, siteId: 0 };
    this.sites = [];

    this.MONTHS = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];

    this.isTabler = function () {
        return !!(document.body && document.body.classList.contains('gems-tabler'));
    };

    this.api = function (path, method, data) {
        return mzAjaxRequest3('water/' + path, method || 'GET', data || '');
    };

    this.apiGet = function (path) {
        return mzAjaxRequest2('water/' + path, 'GET');
    };

    /** Non-blocking write. Does not show the full-page overlay. */
    this.apiAsync = function (path, method, data) {
        return mzFetch('water/' + path, method || 'GET', data || {}, false, true);
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

    /** Run async work one-at-a-time so overlapping month rebuilds cannot race. */
    this.serialQueue = function () {
        let tail = Promise.resolve();
        return function (fn) {
            const run = tail.then(fn, fn);
            tail = run.catch(function () { /* keep the queue moving */ });
            return run;
        };
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

    /** Blank when no reading covers that day. Zero is a real reading. */
    this.fmtVolume = function (value, digits) {
        if (value === null || value === undefined || value === '') {
            return '<span class="text-muted">—</span>';
        }
        const n = Number(value);
        if (!Number.isFinite(n)) { return '<span class="text-muted">—</span>'; }
        const d = digits === undefined ? 2 : digits;
        return n.toLocaleString(undefined, { minimumFractionDigits: d, maximumFractionDigits: d });
    };

    this.fmtRm = function (value) {
        if (value === null || value === undefined || value === '') {
            return '<span class="text-muted">—</span>';
        }
        return self.fmtNumber(value, 2);
    };

    this.fmtPct = function (value) {
        if (value === null || value === undefined || value === '') {
            return '<span class="text-muted">—</span>';
        }
        const n = Number(value);
        if (!Number.isFinite(n)) { return '<span class="text-muted">—</span>'; }
        const sign = n > 0 ? '+' : '';
        return sign + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';
    };

    this.fmtNumber = function (value, digits) {
        const n = Number(value);
        if (!Number.isFinite(n)) { return '—'; }
        const d = digits === undefined ? 2 : digits;
        return n.toLocaleString(undefined, { minimumFractionDigits: d, maximumFractionDigits: d });
    };

    this.escape = function (value) {
        if (window.GemsUI) { return GemsUI.escape(value); }
        if (value === null || value === undefined) { return ''; }
        return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
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
        return '<span class="badge-status ' + (status[kind] || 'neutral') + '">' + label + '</span>';
    };

    this.activeBadge = function (status) {
        return Number(status) === 1
            ? self.badge('success', 'Active')
            : self.badge('secondary', 'Inactive');
    };

    this.actionBtn = function (opts) {
        if (window.GemsUI) { return GemsUI.actionBtn(opts); }
        const href = opts.href ? ' href="' + opts.href + '"' : ' type="button"';
        const tag = opts.href ? 'a' : 'button';
        const extra = opts.extra || '';
        const id = opts.id ? ' id="' + opts.id + '"' : '';
        return '<' + tag + href + id + ' class="btn gems-btn-action ' + (opts.tint || '') + ' ' + (opts.cls || '') +
            '" data-bs-toggle="tooltip" title="' + opts.title + '" aria-label="' + (opts.label || opts.title) + '" ' + extra +
            '><i class="' + opts.icon + '"></i></' + tag + '>';
    };

    this.initTooltips = function (root) {
        if (window.GemsUI) { return GemsUI.initTooltips(root); }
        if (typeof window.gemsInitTooltips === 'function') {
            window.gemsInitTooltips(root || document);
        }
    };

    this.emptyState = function (icon, text) {
        if (window.GemsUI) { return GemsUI.emptyState(icon, text); }
        return '<div class="gems-empty-state"><i class="fas ' + (icon || 'fa-inbox') + '"></i><p>' + text + '</p></div>';
    };

    /** Export an HTML table to CSV. Input values are included. */
    this.exportTable = function (tableId, title) {
        const rows = [];
        $('#' + tableId + ' thead tr').each(function () {
            const cells = [];
            $(this).find('th').each(function () { cells.push($(this).text().trim()); });
            rows.push(cells);
        });
        $('#' + tableId + ' tbody tr, #' + tableId + ' tfoot tr').each(function () {
            const cells = [];
            $(this).find('td').each(function () {
                const input = $(this).find('input');
                const text = input.length ? input.val() : $(this).text().trim().replace(/—/g, '');
                cells.push(text);
            });
            rows.push(cells);
        });
        const csv = rows.map(function (r) {
            return r.map(function (c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(',');
        }).join('\n');
        const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = title.replace(/[^A-Za-z0-9 _-]/g, '') + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };
}
