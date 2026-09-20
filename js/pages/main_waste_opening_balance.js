function MainWasteOpeningBalance() {
    const wc = new WasteCommon();
    let dt;

    const reload = function () {
        const q = $('#optWobSite').val() ? ('?siteId=' + $('#optWobSite').val()) : '';
        dt.clear().rows.add(wc.apiGet('opening_balance' + q) || []).draw();
    };

    this.init = function () {
        wc.loadCaps();
        wc.loadLookups(wc.caps.siteId);
        wc.fillSelect('optWobSite', wc.sites, 'siteId', function (r) { return r.siteName; }, 'All authorised');
        if (!wc.caps.canOpening) { $('#btnWobAdd').hide(); }
        dt = $('#dtWob').DataTable({
            data: [], bLengthChange: false, pageLength: 15, autoWidth: false, searching: false,
            language: wc.dtEmpty('fa-balance-scale', 'No opening balances recorded yet.'),
            dom: wc.dtDomButtons,
            buttons: wc.dtButtons('GEMS - Opening Balance'),
            columns: [
                { data: null }, { data: 'siteName' }, { data: 'swCode' }, { data: 'swDescription' },
                { data: 'asAtDate', render: wc.fmtDate },
                { data: null, className: 'gems-num', render: function (r) { return Number(r.qty).toLocaleString() + ' ' + r.unit + ' (' + wc.fmtQty(r.qtyKg) + ')'; } },
                { data: 'locationName' },
                { data: null, orderable: false, className: 'text-center text-nowrap', render: function (r) {
                    return wc.caps.canOpening ? wc.actionBtn({ tint: 'gems-btn-action-edit', cls: 'lnkWobEdit', title: 'Edit', icon: 'fas fa-pen', extra: 'data-id="' + r.openingId + '"' }) : '';
                } }
            ],
            fnRowCallback: function (n, d, i) { $('td', n).eq(0).html(i + 1); }
        });
        dt.buttons().container().appendTo($('#btnDtWobExport'));
        wc.bindDtTooltips('#dtWob');
        reload();
        $('#btnWobRefresh, #optWobSite').on('click change', function (e) {
            if (e.type === 'click' && this.id !== 'btnWobRefresh') return;
            reload();
        });
        const openForm = function (row) {
            wc.fillSelect('optWobFormSite', wc.sites, 'siteId', function (r) { return r.siteName; }, 'Select premise', row ? row.siteId : wc.caps.siteId);
            wc.fillSelect('optWobSw', wc.swCodes, 'swCodeId', function (r) { return r.swCode + ' — ' + r.swDescription; }, 'Select SW code', row ? row.swCodeId : '');
            wc.fillSelect('optWobLoc', wc.locations, 'locationId', 'locationName', 'Optional', row ? row.locationId : '');
            $('#hidWobId').val(row ? row.openingId : '');
            $('#txtWobAsAt').val(row ? row.asAtDate : '');
            $('#txtWobQty').val(row ? row.qty : '');
            $('#optWobUnit').val(row ? row.unit : 'KG');
            $('#txaWobRemarks').val(row ? (row.remarks || '') : '');
            $('#lblWobDesc').text(row ? row.swDescription : '-');
            $('#modal_waste_opening_balance').modal({ backdrop: 'static' });
        };
        $('#btnWobAdd').on('click', function () { openForm(null); });
        $(document).on('click', '.lnkWobEdit', function () {
            const id = Number($(this).data('id'));
            const row = dt.rows().data().toArray().find(function (r) { return Number(r.openingId) === id; });
            openForm(row);
        });
        $(document).on('change', '#optWobSw', function () {
            const row = wc.swCodes.find(function (r) { return String(r.swCodeId) === String($('#optWobSw').val()); });
            $('#lblWobDesc').text(row ? row.swDescription : '-');
        });
        $(document).on('click', '#btnWobSave', async function () {
            ShowLoader();
            try {
                const body = {
                    openingId: $('#hidWobId').val(),
                    siteId: $('#optWobFormSite').val(),
                    swCodeId: $('#optWobSw').val(),
                    asAtDate: $('#txtWobAsAt').val(),
                    qty: $('#txtWobQty').val(),
                    unit: $('#optWobUnit').val(),
                    locationId: $('#optWobLoc').val(),
                    remarks: $('#txaWobRemarks').val()
                };
                const file = await wc.readFile(document.getElementById('txfWobFile'));
                if (file) { body.fileUpload = file; }
                wc.api('opening_balance' + (body.openingId ? ('/' + body.openingId) : ''), body.openingId ? 'PUT' : 'POST', body);
                $('#modal_waste_opening_balance').modal('hide');
                reload();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
    };
}
