function MainWasteRecords() {
    const wc = new WasteCommon();
    let dt;

    const qs = function () {
        const p = [];
        if ($('#optWtrSite').val()) p.push('siteId=' + encodeURIComponent($('#optWtrSite').val()));
        if ($('#optWtrType').val()) p.push('type=' + encodeURIComponent($('#optWtrType').val()));
        if ($('#optWtrStatus').val()) p.push('status=' + encodeURIComponent($('#optWtrStatus').val()));
        if ($('#optWtrSw').val()) p.push('swCodeId=' + encodeURIComponent($('#optWtrSw').val()));
        if ($('#txtWtrFrom').val()) p.push('from=' + encodeURIComponent($('#txtWtrFrom').val()));
        if ($('#txtWtrTo').val()) p.push('to=' + encodeURIComponent($('#txtWtrTo').val()));
        if ($('#txtWtrSearch').val()) p.push('ref=' + encodeURIComponent($('#txtWtrSearch').val()));
        return p.join('&');
    };

    const reload = function () {
        const rows = wc.apiGet('transaction' + (qs() ? ('?' + qs()) : '')) || [];
        dt.clear().rows.add(rows).draw();
        const finalRows = rows.filter(function (r) { return r.txnStatus === 'FINAL'; });
        $('#mFinal').text(finalRows.length);
        $('#mDraft').text(rows.filter(function (r) { return r.txnStatus === 'DRAFT'; }).length);
        const prod = finalRows.filter(function (r) { return r.txnType === 'P'; }).reduce(function (s, r) { return s + Number(r.qtyKg || 0); }, 0);
        const disp = finalRows.filter(function (r) { return r.txnType === 'D'; }).reduce(function (s, r) { return s + Number(r.qtyKg || 0); }, 0);
        $('#mProd').text(wc.fmtQty(prod));
        $('#mDisp').text(wc.fmtQty(disp));
        $('#lblWtrCount').text(rows.length ? ('Showing ' + rows.length + ' record' + (rows.length === 1 ? '' : 's')) : 'No records match the current filters');
        $('#lblWtrUpdated strong').text(typeof moment === 'function' ? moment().format('DD MMM YYYY, h:mm A') : new Date().toLocaleString());
    };

    this.init = function () {
        wc.loadCaps();
        wc.loadLookups(wc.caps.siteId);
        wc.fillSelect('optWtrSite', wc.sites, 'siteId', function (r) { return r.siteName; }, 'All authorised', wc.queryParam('siteId') || '');
        wc.fillSelect('optWtrSw', wc.swCodes, 'swCodeId', function (r) { return r.swCode; }, 'All', wc.queryParam('swCodeId') || '');
        if (wc.queryParam('from')) $('#txtWtrFrom').val(wc.queryParam('from'));
        if (wc.queryParam('to')) $('#txtWtrTo').val(wc.queryParam('to'));
        if (wc.queryParam('type')) $('#optWtrType').val(wc.queryParam('type'));
        if (wc.queryParam('status')) $('#optWtrStatus').val(wc.queryParam('status'));

        dt = $('#dtWtr').DataTable({
            data: [], bLengthChange: false, pageLength: 15, autoWidth: false, language: _DATATABLE_LANGUAGE, order: [[2, 'desc']],
            dom: "<'row align-items-center mb-2'<'col-sm-12 col-lg-6 px-0 pb-2'B>><'row'<'col-sm-12'tr>><'row'<'col-sm-6'i><'col-sm-6'p>>",
            buttons: wc.dtButtons('GEMS - Waste Records'),
            columns: [
                { data: null },
                { data: 'txnRef' },
                { data: 'eventDate', render: wc.fmtDate },
                { data: null, render: function (r) { return (r.siteCode || '') + ' ' + (r.siteName || ''); } },
                { data: 'txnType', render: wc.typeLabel },
                { data: null, render: function (r) { return r.swCode + ' — ' + (r.swDescription || ''); } },
                { data: null, render: function (r) { return Number(r.qty).toLocaleString() + ' ' + r.unit; } },
                { data: 'balanceEffectKg', render: function (v) { return v === null || v === undefined ? '-' : ((v > 0 ? '+' : '') + wc.fmtQty(v)); } },
                { data: 'hasEvidence', render: function (v) { return v ? '<i class="fas fa-paperclip text-success"></i>' : '<span class="text-muted">—</span>'; } },
                { data: 'txnStatus', render: wc.statusBadge },
                { data: null, orderable: false, className: 'noVis', render: function (r) {
                    let html = '<a class="text-primary mr-2" href="p_waste_record_form?id=' + r.txnId + '" title="View"><i class="fas fa-eye"></i></a>';
                    if (r.txnStatus === 'DRAFT') {
                        html += '<a class="text-info mr-2" href="p_waste_record_form?id=' + r.txnId + '" title="Edit"><i class="fas fa-pen-to-square"></i></a>';
                    }
                    return html;
                } }
            ],
            fnRowCallback: function (n, d, i) { $('td', n).eq(0).html(this._iDisplayStart + i + 1); }
        });
        reload();
        $('#btnWtrRefresh, #optWtrSite, #optWtrType, #optWtrStatus, #optWtrSw, #txtWtrFrom, #txtWtrTo').on('change click', function (e) {
            if (e.type === 'click' && this.id !== 'btnWtrRefresh') return;
            reload();
        });
        let t; $('#txtWtrSearch').on('input', function () { clearTimeout(t); t = setTimeout(reload, 250); });
    };
}
