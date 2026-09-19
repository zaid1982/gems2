function MainKpaEvaluation() {
    const kc = new KpaCommon();
    let dt;
    let dtPi;
    let months = [];
    let current = null;

    const siteId = function () {
        return $('#optKevSite').val() || '';
    };

    const qs = function () {
        const p = [];
        if (siteId()) p.push('siteId=' + siteId());
        if ($('#optKevYear').val()) p.push('year=' + $('#optKevYear').val());
        if ($('#optKevStatus').val()) p.push('status=' + $('#optKevStatus').val());
        return p.join('&');
    };

    const reload = function () {
        months = kc.apiGet('evaluation' + (qs() ? ('?' + qs()) : '')) || [];
        dt.clear().rows.add(months).draw();
    };

    const renderDetail = function (evalId) {
        current = kc.apiGet('evaluation/' + evalId);
        if (!current) { return; }
        $('#divKevDetail').removeClass('d-none');
        $('#mKevMpv').text(kc.fmtMoney(current.mpv));
        $('#mKevPeriod').text(current.periodLabel + ' · ' + (current.siteName || ''));
        $('#mKevApdMax').text(kc.fmtMoney(current.apdMaxAmount));
        $('#mKevApdDeducted').text(kc.fmtMoney(current.totalApdDeducted));
        $('#mKevApdRetained').text(kc.fmtMoney(current.apdRetained));
        $('#mKevDemerit').text(current.totalDemerit);
        $('#mKevSubmitted').text(current.piSubmitted + ' / ' + current.piTotal);
        $('#lblKevDetailTitle').text('Performance Indicators — ' + current.periodLabel);
        $('#lblKevDetailSub').text('Maximum APD ' + kc.fmtMoney(current.apdMaxAmount) +
            ' at ' + kc.fmtNumber(current.maxApdPct, 2) + '% of the MPV. ' +
            (current.evalStatus === 'COMPLETED' ? 'Every indicator has been submitted.' : 'Open an indicator to capture its parameters.'));
        $('#btnKevEditMpv').toggle(!!kc.caps.canAdmin);
        dtPi.clear().rows.add(current.indicators || []).draw();
        $('html, body').animate({ scrollTop: $('#divKevDetail').offset().top - 90 }, 250);
    };

    const openNew = function () {
        const defaults = kc.apiGet('evaluation/defaults?siteId=' + siteId()) || {};
        kc.fillYears('optKenYear', defaults.year, 3, 1);
        kc.fillMonths('optKenMonth', defaults.month);
        $('#txtKenMpv').val(defaults.mpv || '');
        $('#txtKenMaxApd').val(kc.fmtNumber(defaults.maxApdPct, 2));
        $('#lblKenPiCount').text(defaults.piCount || 0);
        $('#txtKenRemarks').val('');
        recalcPreview();
        $('#modal_kpa_eval_new').modal({ backdrop: 'static' });
    };

    const recalcPreview = function () {
        const mpv = Number($('#txtKenMpv').val()) || 0;
        const pct = Number(String($('#txtKenMaxApd').val()).replace(/[^0-9.]/g, '')) || 0;
        $('#txtKenApdMax').val(kc.fmtMoney(mpv * pct / 100));
    };

    const createMonth = function () {
        const body = {
            siteId: siteId(),
            year: $('#optKenYear').val(),
            month: $('#optKenMonth').val(),
            mpv: $('#txtKenMpv').val(),
            remarks: $('#txtKenRemarks').val()
        };
        if (!body.mpv) { toastr['warning']('Enter the Monthly Payment Value.', _ALERT_TITLE_WARNING); return; }
        ShowLoader();
        try {
            const created = kc.api('evaluation', 'POST', body);
            $('#modal_kpa_eval_new').modal('hide');
            reload();
            if (created && created.evalId) { renderDetail(created.evalId); }
        } catch (e) {
            toastr['error'](e.message, _ALERT_TITLE_ERROR);
        }
        HideLoader();
    };

    const openMpv = function () {
        if (!current) { return; }
        $('#hidKmvEvalId').val(current.evalId);
        $('#pKmvPeriod').text(current.periodLabel + ' · ' + (current.siteName || ''));
        $('#txtKmvMpv').val(current.mpv);
        $('#txtKmvMaxApd').val(current.maxApdPct);
        $('#txtKmvRemarks').val(current.remarks || '');
        $('#modal_kpa_eval_mpv').modal({ backdrop: 'static' });
    };

    const saveMpv = function () {
        ShowLoader();
        try {
            kc.api('evaluation/' + $('#hidKmvEvalId').val(), 'PUT', {
                mpv: $('#txtKmvMpv').val(),
                maxApdPct: $('#txtKmvMaxApd').val(),
                remarks: $('#txtKmvRemarks').val()
            });
            $('#modal_kpa_eval_mpv').modal('hide');
            reload();
            renderDetail($('#hidKmvEvalId').val());
        } catch (e) {
            toastr['error'](e.message, _ALERT_TITLE_ERROR);
        }
        HideLoader();
    };

    const reopenPi = function (evalPiId) {
        if (!window.confirm('Reopen this PI so the values can be edited again?')) { return; }
        ShowLoader();
        try {
            kc.api('evaluation_pi/' + evalPiId + '/reopen', 'POST', {});
            reload();
            renderDetail(current.evalId);
        } catch (e) {
            toastr['error'](e.message, _ALERT_TITLE_ERROR);
        }
        HideLoader();
    };

    this.init = function () {
        kc.loadCaps();
        if (!kc.caps.canView) {
            toastr['warning']('You are not allowed to view KPI results.', _ALERT_TITLE_WARNING);
        }
        kc.loadSites();
        kc.fillSelect('optKevSite', kc.sites, 'siteId', function (r) { return r.siteName; }, null, kc.caps.siteId || '');
        kc.fillYears('optKevYear', new Date().getFullYear(), 5, 1);
        $('#btnKevAdd').toggle(!!kc.caps.canAdmin);

        dt = $('#dtKev').DataTable({
            data: [], bLengthChange: false, pageLength: 12, autoWidth: false,
            language: _DATATABLE_LANGUAGE, ordering: false, dom: "t<'row'<'col-sm-6'i><'col-sm-6'p>>",
            columns: [
                { data: 'periodLabel' },
                { data: 'mpv', render: function (v) { return kc.fmtNumber(v, 2); } },
                { data: 'maxApdPct', render: function (v) { return kc.fmtNumber(v, 2) + ' %'; } },
                { data: 'apdMaxAmount', render: function (v) { return kc.fmtNumber(v, 2); } },
                { data: 'totalDemerit' },
                { data: 'totalApdDeducted', render: function (v) { return kc.fmtNumber(v, 2); } },
                { data: null, render: function (r) { return r.piSubmitted + ' / ' + r.piTotal; } },
                { data: 'evalStatus', render: kc.evalStatusBadge },
                { data: null, orderable: false, className: 'noVis', render: function (r) {
                    return '<a href="#" class="text-primary lnkKevOpen" data-id="' + r.evalId + '" title="Open"><i class="fas fa-folder-open"></i></a>';
                } }
            ]
        });

        dtPi = $('#dtKevPi').DataTable({
            data: [], bLengthChange: false, pageLength: 25, autoWidth: false,
            language: _DATATABLE_LANGUAGE, ordering: false,
            dom: "<'row align-items-center mb-2'<'col-sm-12 col-lg-6 px-0 pb-2'B><'col-sm-12 col-lg-6 px-0 pb-2'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-6'i><'col-sm-6'p>>",
            buttons: kc.dtButtons('GEMS - Monthly KPI Evaluation'),
            columns: [
                { data: 'groupNo' },
                { data: 'piNo' },
                { data: 'piName' },
                { data: null, render: function (r) { return kc.fmtNumber(r.targetValue, 2) + (r.targetUnit === 'BEI' ? '' : ' %'); } },
                { data: null, render: function (r) { return kc.fmtActual(r.actualValue, r.targetUnit); } },
                { data: 'isPass', render: kc.passBadge },
                { data: null, render: function (r) { return r.demeritImposed + ' / ' + r.demeritPoint; } },
                { data: 'weightagePct', render: function (v) { return kc.fmtNumber(v, 2) + ' %'; } },
                { data: 'apdValue', render: function (v) { return kc.fmtNumber(v, 2); } },
                { data: 'apdDeducted', render: function (v) { return kc.fmtNumber(v, 2); } },
                { data: 'piStatus', render: kc.piStatusBadge },
                { data: null, orderable: false, className: 'noVis', render: function (r) {
                    let html = '<a class="text-primary mr-2" href="p_kpa_pi_entry?id=' + r.evalPiId + '" title="Open PI"><i class="fas fa-pen-to-square"></i></a>';
                    if (r.canReopen) {
                        html += '<a href="#" class="text-warning lnkKevReopen" data-id="' + r.evalPiId + '" title="Reopen"><i class="fas fa-lock-open"></i></a>';
                    }
                    if (!r.isAssigned && r.piStatus === 'DRAFT') {
                        html += '<span class="text-muted small ml-1" title="Not assigned to you"><i class="fas fa-user-slash"></i></span>';
                    }
                    return html;
                } }
            ]
        });
        dtPi.buttons().container().appendTo($('#btnDtKevPiExport'));

        reload();
        const preselect = kc.queryParam('evalId');
        if (preselect) { renderDetail(preselect); }

        $('#optKevSite, #optKevYear, #optKevStatus').off('change').on('change', reload);
        $('#btnKevRefresh').off('click').on('click', function () {
            reload();
            if (current) { renderDetail(current.evalId); }
        });
        $('#btnKevAdd').off('click').on('click', openNew);
        $('#btnKevEditMpv').off('click').on('click', openMpv);
        $(document).off('click', '.lnkKevOpen').on('click', '.lnkKevOpen', function (e) {
            e.preventDefault();
            renderDetail($(this).data('id'));
        });
        $(document).off('click', '.lnkKevReopen').on('click', '.lnkKevReopen', function (e) {
            e.preventDefault();
            reopenPi($(this).data('id'));
        });
        $(document).off('click', '#btnKenCreate').on('click', '#btnKenCreate', createMonth);
        $(document).off('click', '#btnKmvSave').on('click', '#btnKmvSave', saveMpv);
        $(document).off('input', '#txtKenMpv').on('input', '#txtKenMpv', recalcPreview);
    };
}
