function MainEnergyBei() {
    const ec = new EnergyCommon();
    let data = null;
    const enqueue = ec.serialQueue();
    let saveGen = 0;
    let savedHideTimer = null;

    const siteId = function () { return $('#optEbeSite').val() || ''; };
    const year = function () { return $('#optEbeYear').val() || new Date().getFullYear(); };

    const resultBadge = function (row) {
        if (row.isPass === null || row.isPass === undefined) {
            return '<span class="badge-status neutral"><i class="fas fa-minus"></i>Not scored</span>';
        }
        return row.isPass
            ? '<span class="badge-status completed"><i class="fas fa-check-circle"></i>100%</span>'
            : '<span class="badge-status incomplete"><i class="fas fa-circle-xmark"></i>0%</span>';
    };

    const statusBadge = function (status) {
        if (status === 'FINAL') { return '<span class="badge-status completed"><i class="fas fa-lock"></i>Final</span>'; }
        if (status === 'NOT_SAVED') { return '<span class="badge-status neutral"><i class="fas fa-minus"></i>Not saved</span>'; }
        return '<span class="badge-status in-progress"><i class="fas fa-pen"></i>Draft</span>';
    };

    const renderRows = function () {
        const editable = !!(data.canRecord && ec.caps.canRecord);
        $('#tblEbe tbody').html((data.months || []).map(function (m) {
            const locked = m.beiStatus === 'FINAL' || !editable;
            const override = m.electricityIsOverride
                ? '<span class="bei-override d-block">Override · derived ' + ec.fmtNumber(m.derivedElectricityKwh, 2) + '</span>'
                : '<span class="text-muted d-block" style="font-size:0.7rem">From daily readings</span>';
            return '<tr data-month="' + m.month + '">' +
                '<td><strong>' + m.monthName + '</strong></td>' +
                '<td>' + (locked
                    ? ec.fmtKwh(m.electricityKwh)
                    : '<input type="number" step="0.01" min="0" class="ebeElectricity" data-month="' + m.month + '" value="' + (m.electricityIsOverride ? m.electricityKwh : '') + '" placeholder="' + ec.fmtNumber(m.derivedElectricityKwh, 2) + '">') +
                    override + '</td>' +
                '<td>' + (locked
                    ? ec.fmtKwh(m.chilledWaterKwh)
                    : '<input type="number" step="0.01" min="0" class="ebeChilled" data-month="' + m.month + '" value="' + (m.chilledWaterKwh || '') + '">') + '</td>' +
                '<td class="bei-calc bei-total">' + ec.fmtKwh(m.totalKwh) + '</td>' +
                '<td class="bei-calc bei-area">' + ec.fmtKwh(m.floorAreaSqm) + '</td>' +
                '<td class="bei-calc bei-actual"><strong>' + ec.fmtKwh(m.actualBei, 4) + '</strong></td>' +
                '<td class="bei-calc bei-target">' + ec.fmtKwh(m.targetBei, 4) + '</td>' +
                '<td class="bei-result">' + resultBadge(m) + '</td>' +
                '<td>' + (locked
                    ? ec.escape(m.remarks || '')
                    : '<input type="text" class="ebeRemarks" data-month="' + m.month + '" value="' + ec.escape(m.remarks || '') + '">') + '</td>' +
                '<td class="bei-status">' + statusBadge(m.beiStatus) + '</td>' +
                '<td class="text-right bei-actions">' + actions(m, editable) + '</td>' +
                '</tr>';
        }).join(''));
    };

    const actions = function (m, editable) {
        if (!editable) { return ''; }
        if (m.beiStatus === 'FINAL') {
            return ec.caps.canSetup
                ? '<a href="#" class="text-warning lnkEbeReopen" data-id="' + m.beiId + '" title="Reopen"><i class="fas fa-lock-open"></i></a>'
                : '';
        }
        let html = '<a href="#" class="text-primary mr-2 lnkEbeSave" data-month="' + m.month + '" title="Save"><i class="fas fa-save"></i></a>';
        if (m.beiId) {
            html += '<a href="#" class="text-success lnkEbeFinal" data-id="' + m.beiId + '" title="Finalise"><i class="fas fa-check"></i></a>';
        }
        return html;
    };

    const renderChart = function () {
        const months = data.months || [];
        const target = Number(data.config.targetBei) || 0;
        if (typeof Highcharts === 'undefined') { return; }
        if (!target) {
            $('#chartEbe').html('<div class="text-muted text-center py-5">Set a target BEI in the site configuration to chart the results</div>');
            return;
        }
        Highcharts.chart('chartEbe', {
            chart: { backgroundColor: 'transparent', spacing: [8, 8, 8, 8] },
            title: { text: null },
            credits: { enabled: false },
            xAxis: { categories: months.map(function (m) { return m.monthName.substr(0, 3); }) },
            yAxis: { title: { text: 'BEI (kWh/m2)' }, min: 0, gridLineColor: '#E2E8F0' },
            tooltip: { shared: true, valueDecimals: 4 },
            legend: { itemStyle: { fontWeight: 600 } },
            series: [
                {
                    type: 'column', name: 'Actual BEI', borderRadius: 3, borderWidth: 0,
                    data: months.map(function (m) {
                        return {
                            y: m.actualBei === null ? null : Number(m.actualBei),
                            color: m.isPass === false ? '#dc2626' : '#00ada8'
                        };
                    })
                },
                {
                    type: 'line', name: 'Target BEI', color: '#64748b', dashStyle: 'ShortDash',
                    marker: { enabled: false },
                    data: months.map(function () { return target; })
                }
            ]
        });
    };

    const render = function () {
        data = ec.apiGet('bei?siteId=' + siteId() + '&year=' + year());
        if (!data) { return; }
        $('#txtEbeFloor').val(data.config.floorAreaSqm || '');
        $('#txtEbeTarget').val(data.config.targetBei || '');
        $('#txtEbeFactor').val(data.config.annualiseFactor || 1);
        $('#btnEbeSaveConfig').prop('disabled', !data.config.canSetup);
        $('#mEbeTarget').text(ec.fmtNumber(data.config.targetBei, 4));
        const months = data.months || [];
        $('#mEbePass').text(months.filter(function (m) { return m.isPass === true; }).length);
        $('#mEbeFail').text(months.filter(function (m) { return m.isPass === false; }).length);
        $('#mEbeTotal').text(ec.fmtNumber(months.reduce(function (s, m) { return s + Number(m.totalKwh || 0); }, 0), 2));
        renderRows();
        renderChart();
    };

    const applyMetrics = function () {
        const months = data.months || [];
        $('#mEbePass').text(months.filter(function (m) { return m.isPass === true; }).length);
        $('#mEbeFail').text(months.filter(function (m) { return m.isPass === false; }).length);
        $('#mEbeTotal').text(ec.fmtNumber(months.reduce(function (s, m) { return s + Number(m.totalKwh || 0); }, 0), 2));
    };

    const patchRow = function (m, editable) {
        const tr = $('#tblEbe tbody tr[data-month="' + m.month + '"]');
        if (!tr.length) { return; }
        tr.find('.bei-total').html(ec.fmtKwh(m.totalKwh));
        tr.find('.bei-area').html(ec.fmtKwh(m.floorAreaSqm));
        tr.find('.bei-actual').html('<strong>' + ec.fmtKwh(m.actualBei, 4) + '</strong>');
        tr.find('.bei-target').html(ec.fmtKwh(m.targetBei, 4));
        tr.find('.bei-result').html(resultBadge(m));
        tr.find('.bei-status').html(statusBadge(m.beiStatus));
        tr.find('.bei-actions').html(actions(m, editable));
        const hint = tr.find('td').eq(1).find('.bei-override, span.text-muted').first();
        if (hint.length) {
            hint.replaceWith(m.electricityIsOverride
                ? '<span class="bei-override d-block">Override · derived ' + ec.fmtNumber(m.derivedElectricityKwh, 2) + '</span>'
                : '<span class="text-muted d-block" style="font-size:0.7rem">From daily readings</span>');
        }
    };

    const patchGrid = function (next) {
        data = next;
        const editable = !!(data.canRecord && ec.caps.canRecord);
        (data.months || []).forEach(function (m) { patchRow(m, editable); });
        applyMetrics();
        renderChart();
    };

    const showSaved = function () {
        ec.setSaveState('lblEbeSaveState', 'saved');
        clearTimeout(savedHideTimer);
        savedHideTimer = setTimeout(function () { ec.setSaveState('lblEbeSaveState', 'idle'); }, 1600);
    };

    const saveMonth = function (month, opts) {
        opts = opts || {};
        const gen = ++saveGen;
        const overlay = !!opts.overlay;
        if (overlay) { ShowLoader(); } else { ec.setSaveState('lblEbeSaveState', 'saving'); }
        return enqueue(function () {
            return ec.apiAsync('bei', 'POST', {
                siteId: siteId(),
                year: year(),
                month: month,
                electricityKwh: $('.ebeElectricity[data-month="' + month + '"]').val(),
                chilledWaterKwh: $('.ebeChilled[data-month="' + month + '"]').val() || 0,
                remarks: $('.ebeRemarks[data-month="' + month + '"]').val() || '',
                autosave: overlay ? 0 : 1
            }).then(function (next) {
                if (gen === saveGen) { patchGrid(next); if (!overlay) { showSaved(); } }
                return next;
            }).catch(function (e) {
                if (gen === saveGen) {
                    ec.setSaveState('lblEbeSaveState', 'error', e.message);
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                throw e;
            }).finally(function () { if (overlay) { HideLoader(); } });
        });
    };

    this.init = function () {
        ec.loadCaps();
        ec.loadSites();
        ec.fillSelect('optEbeSite', ec.sites, 'siteId', function (r) { return r.siteName; }, null, ec.caps.siteId || '');
        ec.fillYears('optEbeYear', new Date().getFullYear(), 5, 1);
        render();

        $('#optEbeSite, #optEbeYear').off('change').on('change', render);
        $('#btnEbeRefresh').off('click').on('click', render);
        $('#btnEbeExport').off('click').on('click', function () {
            ec.exportTable('tblEbe', 'GEMS Building Energy Index ' + (data ? data.year : ''));
        });
        $('#btnEbeSaveConfig').off('click').on('click', function () {
            ShowLoader();
            try {
                ec.api('config', 'POST', {
                    siteId: siteId(),
                    floorAreaSqm: $('#txtEbeFloor').val(),
                    targetBei: $('#txtEbeTarget').val(),
                    annualiseFactor: $('#txtEbeFactor').val()
                });
                render();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });

        $(document).off('click', '.lnkEbeSave').on('click', '.lnkEbeSave', function (e) {
            e.preventDefault();
            saveMonth($(this).data('month'), { overlay: true });
        });
        const onMonthField = ec.debounce(function (month) { saveMonth(month); }, 400);
        $(document).off('change', '.ebeChilled, .ebeElectricity, .ebeRemarks').on('change', '.ebeChilled, .ebeElectricity, .ebeRemarks', function () {
            onMonthField($(this).data('month'));
        });
        $(document).off('click', '.lnkEbeFinal').on('click', '.lnkEbeFinal', function (e) {
            e.preventDefault();
            if (!window.confirm('Finalise this month? The values are locked once finalised.')) { return; }
            const id = $(this).data('id');
            ShowLoader();
            enqueue(function () {
                return ec.apiAsync('bei/' + id + '/finalise', 'POST', {})
                    .then(function (next) { patchGrid(next); })
                    .catch(function (err) { toastr['error'](err.message, _ALERT_TITLE_ERROR); })
                    .finally(function () { HideLoader(); });
            });
        });
        $(document).off('click', '.lnkEbeReopen').on('click', '.lnkEbeReopen', function (e) {
            e.preventDefault();
            const id = $(this).data('id');
            ShowLoader();
            enqueue(function () {
                return ec.apiAsync('bei/' + id + '/reopen', 'POST', {})
                    .then(function (next) { patchGrid(next); })
                    .catch(function (err) { toastr['error'](err.message, _ALERT_TITLE_ERROR); })
                    .finally(function () { HideLoader(); });
            });
        });
    };
}
