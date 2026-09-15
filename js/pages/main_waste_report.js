function MainWasteReport() {
    const wc = new WasteCommon();
    let dt;

    const reload = function () {
        const q = $('#optWrpSite').val() ? ('?siteId=' + $('#optWrpSite').val()) : '';
        dt.clear().rows.add(wc.apiGet('report' + q) || []).draw();
    };

    const bodyFromModal = function () {
        return {
            siteId: $('#optWrgSite').val(),
            periodFrom: $('#txtWrgFrom').val(),
            periodTo: $('#txtWrgTo').val(),
            asAt: $('#txtWrgTo').val(),
            includeDetail: $('#chkWrgDetail').is(':checked') ? 1 : 0,
            includeZero: $('#chkWrgZero').is(':checked') ? 1 : 0,
            remarks: $('#txaWrgRemarks').val()
        };
    };

    this.init = function () {
        wc.loadCaps();
        wc.loadLookups(wc.caps.siteId);
        wc.fillSelect('optWrpSite', wc.sites, 'siteId', function (r) { return r.siteName; }, 'All authorised');
        if (!wc.caps.canReport) { $('#btnWrpNew').hide(); }
        dt = $('#dtWrp').DataTable({
            data: [], bLengthChange: false, pageLength: 12, autoWidth: false, language: _DATATABLE_LANGUAGE, order: [[2, 'desc']],
            columns: [
                { data: null },
                { data: 'siteName' },
                { data: null, render: function (r) { return wc.fmtDate(r.periodStart) + ' – ' + wc.fmtDate(r.periodEnd); } },
                { data: 'versionNo', render: function (v) { return 'v' + v; } },
                { data: null, render: function (r) { return wc.fmtDate((r.generatedAt || '').slice(0, 10)) + ' · ' + (r.generatedByName || ''); } },
                { data: 'changedSinceGenerated', render: function (v) { return v ? '<span class="badge-status incomplete">Changed</span>' : '<span class="badge-status completed">Current</span>'; } },
                { data: null, orderable: false, render: function (r) {
                    let html = '';
                    if (r.pdfUploadId) html += '<a href="#" class="mr-2 lnkFile" data-id="' + r.pdfUploadId + '">PDF</a>';
                    if (r.excelUploadId) html += '<a href="#" class="mr-2 lnkFile" data-id="' + r.excelUploadId + '">Excel</a>';
                    if (wc.caps.canReport) html += '<a href="#" class="lnkSubmit" data-id="' + r.reportId + '">Submission</a>';
                    return html;
                } }
            ],
            fnRowCallback: function (n, d, i) { $('td', n).eq(0).html(i + 1); }
        });
        reload();
        $('#btnWrpRefresh, #optWrpSite').on('click change', function (e) {
            if (e.type === 'click' && this.id !== 'btnWrpRefresh') return;
            reload();
        });
        $('#btnWrpNew').on('click', function () {
            const now = new Date();
            wc.fillSelect('optWrgSite', wc.sites, 'siteId', function (r) { return r.siteName; }, 'Select premise', wc.caps.siteId);
            $('#txtWrgFrom').val(new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10));
            $('#txtWrgTo').val(new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10));
            $('#modal_waste_report_generate').modal({ backdrop: 'static' });
        });
        $(document).on('click', '#btnWrgPreview', function () {
            ShowLoader();
            try {
                const snap = wc.api('report/preview', 'POST', bodyFromModal());
                const tb = $('#tblPreview tbody').empty();
                (snap.summary || []).forEach(function (l) {
                    tb.append('<tr><td>' + l.swCode + '</td><td>' + l.swDescription + '</td><td>' + wc.fmtQty(l.openingKg) + '</td><td>' + wc.fmtQty(l.producedKg) + '</td><td>' + wc.fmtQty(l.disposedKg) + '</td><td>' + wc.fmtQty(l.closingKg) + '</td></tr>');
                });
                $('#divPreview').show();
                $('#modal_waste_report_generate').modal('hide');
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
        $(document).on('click', '#btnWrgGenerate', function () {
            ShowLoader();
            try {
                wc.api('report', 'POST', bodyFromModal());
                $('#modal_waste_report_generate').modal('hide');
                reload();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
        $(document).on('click', '.lnkFile', function (e) { e.preventDefault(); wc.openUpload($(this).data('id')); });
        $(document).on('click', '.lnkSubmit', function (e) {
            e.preventDefault();
            $('#hidWrsId').val($(this).data('id'));
            wc.fillSelect('optWrsChannel', wc.refsByType('SUBMISSION_CHANNEL'), 'refValueId', 'valueName', 'Select channel');
            $('#txtWrsDate').val(new Date().toISOString().slice(0, 10));
            $('#modal_waste_report_submission').modal({ backdrop: 'static' });
        });
        $(document).on('click', '#btnWrsSave', async function () {
            ShowLoader();
            try {
                const body = {
                    submittedAt: $('#txtWrsDate').val(),
                    recipient: $('#txtWrsTo').val(),
                    channelId: $('#optWrsChannel').val(),
                    submissionRef: $('#txtWrsRef').val(),
                    notes: $('#txaWrsNotes').val()
                };
                const file = await wc.readFile(document.getElementById('txfWrsFile'));
                if (file) body.fileUpload = file;
                wc.api('report/' + $('#hidWrsId').val() + '/submission', 'POST', body);
                $('#modal_waste_report_submission').modal('hide');
                reload();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
    };
}
