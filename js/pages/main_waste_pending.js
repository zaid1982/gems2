function MainWastePending() {
    const wc = new WasteCommon();
    let dt;
    let rows = [];

    const qs = function () {
        const p = [];
        if ($('#optWpdSite').val()) p.push('siteId=' + encodeURIComponent($('#optWpdSite').val()));
        if ($('#optWpdSw').val()) p.push('swCodeId=' + encodeURIComponent($('#optWpdSw').val()));
        if ($('#optWpdStatus').val()) p.push('status=' + encodeURIComponent($('#optWpdStatus').val()));
        if ($('#txtWpdFrom').val()) p.push('from=' + encodeURIComponent($('#txtWpdFrom').val()));
        if ($('#txtWpdTo').val()) p.push('to=' + encodeURIComponent($('#txtWpdTo').val()));
        if ($('#txtWpdSearch').val()) p.push('ref=' + encodeURIComponent($('#txtWpdSearch').val()));
        return p.join('&');
    };

    const renderSummary = function () {
        const siteId = $('#optWpdSite').val();
        const summary = wc.apiGet('generation/pending_summary' + (siteId ? ('?siteId=' + siteId) : '')) || [];
        const body = $('#tblWpdSummary tbody');
        if (!summary.length) {
            body.html('<tr><td colspan="4" class="text-muted">Nothing is pending disposal</td></tr>');
            $('#mWpdTypes').text('0');
            $('#mWpdKg').text(wc.fmtQty(0));
            return;
        }
        body.html(summary.map(function (r) {
            return '<tr>' +
                '<td><strong>' + r.swCode + '</strong></td>' +
                '<td>' + (r.swDescription || '') + '</td>' +
                '<td>' + r.recordCount + '</td>' +
                '<td>' + wc.fmtQty(r.pendingKg) + '</td>' +
                '</tr>';
        }).join(''));
        $('#mWpdTypes').text(summary.length);
        $('#mWpdKg').text(wc.fmtQty(summary.reduce(function (s, r) { return s + Number(r.pendingKg || 0); }, 0)));
    };

    const reload = function () {
        rows = wc.apiGet('generation' + (qs() ? ('?' + qs()) : '')) || [];
        dt.clear().rows.add(rows).draw();
        $('#mWpdCount').text(rows.filter(function (r) { return r.collectionStatus === 'PENDING'; }).length);
        $('#mWpdDisposed').text(rows.filter(function (r) { return r.collectionStatus === 'DISPOSED'; }).length);
        $('#lblWpdCount').text(rows.length ? ('Showing ' + rows.length + ' record' + (rows.length === 1 ? '' : 's')) : 'No records match the current filters');
        $('#lblWpdUpdated strong').text(typeof moment === 'function' ? moment().format('DD MMM YYYY, h:mm A') : new Date().toLocaleString());
        renderSummary();
    };

    const rowById = function (txnId) {
        return rows.find(function (r) { return String(r.txnId) === String(txnId); }) || null;
    };

    const openEdit = function (txnId) {
        const row = rowById(txnId);
        if (!row) { return; }
        wc.loadLookups(row.siteId, true);
        const active = (wc.swCodes || []).filter(function (r) { return Number(r.swStatus) === 1; });
        wc.fillSelect('optWpeSw', active, 'swCodeId', function (r) {
            return r.swCode + (r.profileAlias ? ' — ' + r.profileAlias : '');
        }, 'Select waste type', row.swCodeId);
        $('#hidWpeId').val(row.txnId);
        $('#lblWpeRef').val(row.txnRef);
        $('#txtWpeQty').val(row.registeredKg);
        $('#txtWpeDate').val(row.eventDate).attr('max', moment().format('YYYY-MM-DD'));
        $('#txtWpeRemarks').val(row.remarks || '');
        $('#txaWpeReason').val('');
        $('#modal_waste_pending_edit').modal({ backdrop: 'static' });
    };

    const saveEdit = function () {
        const txnId = $('#hidWpeId').val();
        const qtyKg = Number($('#txtWpeQty').val());
        if (!Number.isFinite(qtyKg) || qtyKg <= 0) { toastr['warning']('Enter a weight greater than zero.', _ALERT_TITLE_WARNING); return; }
        if (!$('#txtWpeDate').val()) { toastr['warning']('Enter the generated date.', _ALERT_TITLE_WARNING); return; }
        ShowLoader();
        try {
            wc.api('generation/' + txnId, 'PUT', {
                swCodeId: $('#optWpeSw').val(),
                qtyKg: qtyKg,
                eventDate: $('#txtWpeDate').val(),
                remarks: $('#txtWpeRemarks').val() || '',
                reason: $('#txaWpeReason').val() || ''
            });
            $('#modal_waste_pending_edit').modal('hide');
            reload();
        } catch (e) {
            toastr['error'](e.message, _ALERT_TITLE_ERROR);
        }
        HideLoader();
    };

    const openDelete = function (txnId) {
        const row = rowById(txnId);
        if (!row) { return; }
        $('#hidWpxId').val(row.txnId);
        $('#lblWpxRef').text(row.txnRef);
        $('#txaWpxReason').val('');
        $('#modal_waste_pending_delete').modal({ backdrop: 'static' });
    };

    const confirmDelete = function () {
        const reason = ($('#txaWpxReason').val() || '').trim();
        if (!reason) { toastr['warning']('Enter the reason for deleting this record.', _ALERT_TITLE_WARNING); return; }
        ShowLoader();
        try {
            wc.api('generation/' + $('#hidWpxId').val(), 'DELETE', { reason: reason });
            $('#modal_waste_pending_delete').modal('hide');
            reload();
        } catch (e) {
            toastr['error'](e.message, _ALERT_TITLE_ERROR);
        }
        HideLoader();
    };

    this.init = function () {
        wc.loadCaps();
        wc.loadLookups(wc.caps.siteId);
        wc.fillSelect('optWpdSite', wc.sites, 'siteId', function (r) { return r.siteName; }, 'All authorised', wc.queryParam('siteId') || '');
        wc.fillSelect('optWpdSw', wc.swCodes, 'swCodeId', function (r) { return r.swCode; }, 'All', wc.queryParam('swCodeId') || '');
        if (wc.queryParam('status')) { $('#optWpdStatus').val(wc.queryParam('status')); }
        wc.revealActiveMoreFilters('wpdMoreFilters');

        dt = $('#dtWpd').DataTable({
            data: [], bLengthChange: false, pageLength: 15, autoWidth: false, searching: false,
            language: wc.dtEmpty('fa-truck-ramp-box', 'No pending disposal records.'),
            order: [[3, 'desc']],
            dom: wc.dtDomButtons,
            buttons: wc.dtButtons('GEMS - Pending Waste Disposal'),
            columns: [
                { data: null },
                { data: 'txnRef' },
                { data: null, render: function (r) { return r.swCode + ' — ' + (r.swDescription || ''); } },
                { data: 'eventDate', render: wc.fmtDate },
                { data: 'registeredKg', className: 'gems-num', render: wc.fmtQty },
                { data: 'disposedKg', className: 'gems-num', render: function (v) { return v === null || v === undefined ? '<span class="text-muted">—</span>' : wc.fmtQty(v); } },
                { data: 'collectionStatus', render: wc.collectionBadge },
                { data: null, orderable: false, className: 'noVis text-center text-nowrap', render: function (r) {
                    let html = wc.actionBtn({ href: 'p_waste_record_form?id=' + r.txnId, tint: 'gems-btn-action-view', cls: 'lnkWpdView', title: 'View', icon: 'fas fa-eye' });
                    if (r.canDispose && wc.caps.canDispose) {
                        html += wc.actionBtn({ href: 'p_waste_dispose?id=' + r.txnId, tint: 'gems-btn-action-edit', title: 'Execute disposal', icon: 'fas fa-truck-ramp-box' });
                        html += wc.actionBtn({ href: '#', tint: 'gems-btn-action-edit', cls: 'lnkWpdEdit', title: 'Edit', icon: 'fas fa-pen', extra: 'data-id="' + r.txnId + '"' });
                        html += wc.actionBtn({ href: '#', tint: 'gems-btn-action-delete', cls: 'lnkWpdDel', title: 'Delete', icon: 'fas fa-trash', extra: 'data-id="' + r.txnId + '"' });
                    } else if (r.disposalRef) {
                        html += '<span class="text-muted small">' + r.disposalRef + '</span>';
                    }
                    return html;
                } }
            ],
            fnRowCallback: function (n, d, i) { $('td', n).eq(0).html(this._iDisplayStart + i + 1); }
        });
        dt.buttons().container().appendTo($('#btnDtWpdExport'));
        wc.bindDtTooltips('#dtWpd');
        reload();

        $('#btnWpdRefresh').off('click').on('click', reload);
        $('#optWpdSite, #optWpdSw, #optWpdStatus, #txtWpdFrom, #txtWpdTo').off('change').on('change', reload);
        let t;
        $('#txtWpdSearch').off('input').on('input', function () { clearTimeout(t); t = setTimeout(reload, 250); });

        $(document).off('click', '.lnkWpdEdit').on('click', '.lnkWpdEdit', function (e) {
            e.preventDefault();
            openEdit($(this).data('id'));
        });
        $(document).off('click', '.lnkWpdDel').on('click', '.lnkWpdDel', function (e) {
            e.preventDefault();
            openDelete($(this).data('id'));
        });
        $(document).off('click', '#btnWpeSave').on('click', '#btnWpeSave', saveEdit);
        $(document).off('click', '#btnWpxDelete').on('click', '#btnWpxDelete', confirmDelete);
    };
}
