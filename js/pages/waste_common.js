function WasteCommon() {
    const self = this;
    this.caps = { canRecord: false, canAmendFinal: false, canOpening: false, canReport: false, canSetup: false, isAdmin: false, siteId: 0 };
    this.sites = [];
    this.swCodes = [];
    this.locations = [];
    this.refValues = [];
    this._lookupPacks = {};

    this.api = function (path, method, data) {
        return mzAjaxRequest3('waste/' + path, method || 'GET', data || '');
    };

    this.apiGet = function (path) {
        return mzAjaxRequest2('waste/' + path, 'GET');
    };

    this.apiGetAsync = function (path) {
        return new Promise(function (resolve, reject) {
            const header = sessionStorage.getItem('token') !== null
                ? { Authorization: 'Bearer ' + sessionStorage.getItem('token') }
                : {};
            $.ajax({
                url: 'waste/' + path,
                type: 'GET',
                headers: header,
                dataType: 'json'
            }).done(function (resp) {
                if (resp && resp.success) {
                    resolve(resp.result);
                    return;
                }
                if (resp && resp.errmsg === 'Expired token') {
                    window.location.href = 'p_login?f=2';
                }
                reject(new Error((resp && resp.errmsg) || _ALERT_MSG_ERROR_DEFAULT));
            }).fail(function () {
                reject(new Error(_ALERT_MSG_ERROR_DEFAULT));
            });
        });
    };

    const applyLookupPack = function (pack) {
        pack = pack || {};
        self.sites = pack.sites || [];
        self.swCodes = pack.swCodes || [];
        self.locations = pack.locations || [];
        self.refValues = pack.refValues || [];
    };

    this.loadCaps = function () {
        self.caps = self.apiGet('me') || self.caps;
        return self.caps;
    };

    this.loadLookups = function (siteId, force) {
        const key = String(siteId || 0);
        if (!force && self._lookupPacks[key]) {
            applyLookupPack(self._lookupPacks[key]);
            return self._lookupPacks[key];
        }
        const pack = self.apiGet('lookups' + (siteId ? ('?siteId=' + siteId) : '')) || {};
        self._lookupPacks[key] = pack;
        applyLookupPack(pack);
        return pack;
    };

    this.loadLookupsAsync = function (siteId, force) {
        const key = String(siteId || 0);
        if (!force && self._lookupPacks[key]) {
            applyLookupPack(self._lookupPacks[key]);
            return Promise.resolve(self._lookupPacks[key]);
        }
        return self.apiGetAsync('lookups' + (siteId ? ('?siteId=' + siteId) : '')).then(function (pack) {
            pack = pack || {};
            self._lookupPacks[key] = pack;
            applyLookupPack(pack);
            return pack;
        });
    };

    this.siteById = function (siteId) {
        return (self.sites || []).find(function (row) { return String(row.siteId) === String(siteId); }) || null;
    };

    this.refsByType = function (type) {
        return (self.refValues || []).filter(function (r) { return String(r.valueType) === type && Number(r.valueStatus) === 1; });
    };

    this.fillSelect = function (id, rows, valueKey, labelFn, placeholder, selected) {
        const el = document.getElementById(id);
        if (!el) { return; }
        el.classList.add('form-select');
        el.classList.remove('custom-select', 'gems-plain-select', 'browser-default');
        el.innerHTML = '';
        const first = document.createElement('option');
        first.value = '';
        first.text = placeholder || 'Please choose';
        el.appendChild(first);
        (rows || []).forEach(function (row) {
            const opt = document.createElement('option');
            opt.value = row[valueKey];
            opt.text = typeof labelFn === 'function' ? labelFn(row) : row[labelFn];
            if (String(selected) === String(row[valueKey])) { opt.selected = true; }
            el.appendChild(opt);
        });
    };

    this.fmtDate = function (d) {
        if (!d) { return '-'; }
        if (typeof moment === 'function') {
            const m = moment(d, ['YYYY-MM-DD', moment.ISO_8601], true);
            if (m.isValid()) { return m.format('DD MMM YYYY'); }
        }
        return d;
    };

    this.fmtQty = function (kg) {
        const n = Number(kg);
        if (!Number.isFinite(n)) { return '-'; }
        return n.toLocaleString(undefined, { minimumFractionDigits: 3, maximumFractionDigits: 3 }) + ' kg';
    };

    this.fmtMt = function (kg) {
        const n = Number(kg);
        if (!Number.isFinite(n)) { return '-'; }
        return (n / 1000).toLocaleString(undefined, { minimumFractionDigits: 3, maximumFractionDigits: 3 });
    };

    this.fileSize = function (bytes) {
        const n = Number(bytes);
        if (!Number.isFinite(n) || n <= 0) { return ''; }
        if (n < 1024 * 1024) { return (n / 1024).toFixed(0) + ' KB'; }
        return (n / (1024 * 1024)).toFixed(2) + ' MB';
    };

    this.readFileObject = function (file) {
        const self = this;
        return new Promise(function (resolve) {
            if (!file) { resolve(null); return; }
            if (file.size > 10 * 1024 * 1024) {
                toastr['error']('File must be 10 MB or less.', _ALERT_TITLE_ERROR);
                resolve(null);
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                const base64 = String(e.target.result).split(',')[1] || '';
                resolve({ name: file.name, filename: file.name, size: file.size, type: file.type, data: base64, width: null, height: null });
            };
            reader.readAsDataURL(file);
        });
    };

    this.typeLabel = function (t) {
        return t === 'P' ? 'Produced' : (t === 'D' ? 'Disposed' : t || '-');
    };

    this.statusBadge = function (status) {
        if (status === 'FINAL') { return '<span class="badge gems-badge gems-badge-success">Final</span>'; }
        if (status === 'DRAFT') { return '<span class="badge gems-badge gems-badge-warning">Draft</span>'; }
        return '<span class="badge gems-badge gems-badge-secondary">Cancelled</span>';
    };

    this.collectionBadge = function (status) {
        if (status === 'DISPOSED') { return '<span class="badge gems-badge gems-badge-success">Disposed</span>'; }
        return '<span class="badge gems-badge gems-badge-warning">Pending Collection</span>';
    };

    // Canonical P2 DataTables chrome: hide DT's own filter, scroll ONLY the
    // table, keep info + pagination in a real .card-footer outside the scroller.
    this.dtDom = "<'d-none'f>r<'table-responsive't><'card-footer d-flex align-items-center py-2'i<'ms-auto'p>>";
    this.dtDomButtons = "<'d-none'B>r<'table-responsive't><'card-footer d-flex align-items-center py-2'i<'ms-auto'p>>";
    this.dtEmpty = function (icon, emptyText, zeroText) {
        return $.extend({}, _DATATABLE_LANGUAGE, {
            emptyTable: '<div class="gems-empty-state"><i class="fas ' + (icon || 'fa-inbox') + '"></i><p>' + emptyText + '</p></div>',
            zeroRecords: '<div class="gems-empty-state"><i class="fas fa-filter"></i><p>' + (zeroText || 'No records match the current filters.') + '</p></div>'
        });
    };
    this.actionBtn = function (opts) {
        const href = opts.href ? ' href="' + opts.href + '"' : ' type="button"';
        const tag = opts.href ? 'a' : 'button';
        const extra = opts.extra || '';
        const id = opts.id ? ' id="' + opts.id + '"' : '';
        return '<' + tag + href + id + ' class="btn gems-btn-action ' + (opts.tint || '') + ' ' + (opts.cls || '') +
            '" data-toggle="tooltip" title="' + opts.title + '" aria-label="' + (opts.label || opts.title) + '" ' + extra +
            '><i class="' + opts.icon + '"></i></' + tag + '>';
    };

    this.uuid = function () {
        if (window.crypto && crypto.randomUUID) { return crypto.randomUUID(); }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            const r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    };

    this.queryParam = function (name) {
        const params = new URLSearchParams(window.location.search);
        return params.get(name);
    };

    // Expand the existing More-filters collapse when any control inside it
    // already has a value (dashboard drill-down). Leave it collapsed otherwise.
    this.revealActiveMoreFilters = function (collapseId) {
        const el = document.getElementById(collapseId);
        if (!el) { return false; }
        const fields = el.querySelectorAll('input, select, textarea');
        let active = false;
        for (let i = 0; i < fields.length; i++) {
            if (String(fields[i].value || '').trim()) { active = true; break; }
        }
        if (!active) { return false; }
        el.classList.add('show');
        const trigger = document.querySelector('[data-bs-target="#' + collapseId + '"], [data-target="#' + collapseId + '"]');
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'true');
            trigger.classList.remove('collapsed');
        }
        return true;
    };

    this.goRecords = function (qs) {
        window.location.href = 'p_waste_records' + (qs ? ('?' + qs) : '');
    };

    this.openUpload = function (uploadId) {
        if (!uploadId) { return; }
        const link = mzAjaxRequest2('document/upload_link/' + uploadId, 'GET');
        if (link) { window.open(link, '_blank'); }
    };

    this.readFile = function (inputEl, hiddenBlobId) {
        const file = inputEl && inputEl.files && inputEl.files[0];
        if (!file) { return Promise.resolve(null); }
        return self.readFileObject(file).then(function (payload) {
            if (payload && hiddenBlobId) { $('#' + hiddenBlobId).val(JSON.stringify(payload)); }
            return payload;
        });
    };

    this.dtButtons = function (title) {
        return [
            { extend: 'colvis', columns: ':not(.noVis)', fade: 400, text: '<i class="fas fa-columns"></i>', className: 'btn btn-outline-secondary btn-sm', titleAttr: 'Column visibility' },
            { extend: 'print', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-print"></i>', title: title, titleAttr: 'Print', exportOptions: typeof mzExportOpt !== 'undefined' ? mzExportOpt : {} },
            { extend: 'excelHtml5', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-file-excel"></i>', title: title, titleAttr: 'Excel', exportOptions: typeof mzExportExcelOpt !== 'undefined' ? mzExportExcelOpt : {} },
            { extend: 'pdfHtml5', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-file-pdf"></i>', title: title, titleAttr: 'PDF', orientation: 'landscape', exportOptions: typeof mzExportOpt !== 'undefined' ? mzExportOpt : {} }
        ];
    };

    this.bindDtTooltips = function (tableId) {
        const $table = $(tableId);
        $table.on('draw.dt', function () {
            if (typeof window.gemsInitTooltips === 'function') {
                window.gemsInitTooltips($table.find('tbody')[0]);
            }
        });
    };

    this.pageScripts = function (initFn) {
        document.addEventListener('DOMContentLoaded', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    initiatePages();
                    initFn();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });
    };
}
