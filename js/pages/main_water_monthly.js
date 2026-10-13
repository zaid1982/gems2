function MainWaterMonthly() {
    const wc = new WaterCommon();
    let data = null;
    const enqueue = wc.serialQueue();
    let saveGen = 0;
    let savedHideTimer = null;
    const palette = (window.GemsUI && GemsUI.chartColors()) || ['#6b9cd6', '#6bcfcd', '#7ab597', '#c4a46f', '#eb8181', '#70bfd2'];

    const canEditBill = function () {
        return !!((data.canRecord || data.canSetup) && (wc.caps.canRecord || wc.caps.canSetup));
    };

    const render = function () {
        data = wc.apiGet('monthly?siteId=' + ($('#optWmoSite').val() || '') + '&year=' + ($('#optWmoYear').val() || new Date().getFullYear()));
        if (!data) { return; }
        const meters = data.meters || [];
        const editable = canEditBill();

        let head = '<tr><th>Month</th>';
        meters.forEach(function (m) { head += '<th class="enr-calc">' + wc.escape(m.meterName) + ' (m&sup3;)</th>'; });
        head += '<th class="enr-calc">Total m&sup3;</th><th class="enr-calc">Average / day</th>' +
            '<th class="enr-calc">Highest</th><th class="enr-calc">Lowest</th>' +
            '<th class="enr-calc">Days with data</th><th class="enr-calc">Estimated</th><th class="enr-calc">Bill days</th>' +
            '<th class="enr-calc">Calculated RM</th><th class="enr-calc">Volume change</th><th class="enr-calc">Cost change</th>' +
            '<th class="enr-calc">Actual RM</th><th class="enr-calc">Difference</th></tr>';
        $('#theadWmo').html(head);

        $('#tbodyWmo').html((data.months || []).map(function (m) {
            const byMeter = {};
            (m.meterTotals || []).forEach(function (t) { byMeter[t.meterId] = t.totalVolume; });
            let tds = '<td><strong>' + m.monthName + '</strong></td>';
            meters.forEach(function (meter) {
                const value = m.daysWithData > 0 ? (byMeter[meter.meterId] || 0) : null;
                tds += '<td class="enr-calc">' + wc.fmtVolume(value) + '</td>';
            });
            tds += '<td class="enr-calc"><strong>' + wc.fmtVolume(m.daysWithData > 0 ? m.totalVolume : null) + '</strong></td>';
            tds += '<td class="enr-calc">' + wc.fmtVolume(m.daysWithData > 0 ? m.averageDaily : null) + '</td>';
            tds += '<td class="enr-calc">' + wc.fmtVolume(m.highestDaily) + '</td>';
            tds += '<td class="enr-calc">' + wc.fmtVolume(m.lowestDaily) + '</td>';
            tds += '<td class="enr-calc">' + m.daysWithData + ' / ' + m.daysInMonth + '</td>';
            tds += '<td class="enr-calc">' + m.estimatedDays + '</td>';
            tds += '<td class="enr-calc">' + m.billDays + '</td>';
            const billTitle = m.billMessage ? ' title="' + wc.escape(m.billMessage) + '"' : '';
            tds += '<td class="enr-calc"' + billTitle + '>' + wc.fmtRm(m.calculatedRm) + '</td>';
            tds += '<td class="enr-calc">' + wc.fmtPct(m.volumeVariancePct) + '</td>';
            tds += '<td class="enr-calc">' + wc.fmtPct(m.costVariancePct) + '</td>';
            const actual = m.actualRm === null || m.actualRm === undefined ? '' : m.actualRm;
            tds += '<td class="enr-calc">' +
                (editable
                    ? '<input type="number" step="0.01" min="0" class="form-control wmoActual" data-month="' + m.month + '" value="' + actual + '">'
                    : wc.fmtRm(m.actualRm)) +
                '</td>';
            tds += '<td class="enr-calc wmoDiff" data-month="' + m.month + '">' + wc.fmtRm(m.differenceRm) + '</td>';
            return '<tr>' + tds + '</tr>';
        }).join(''));

        const totals = {};
        (data.meterTotals || []).forEach(function (t) { totals[t.meterId] = t.totalVolume; });
        let foot = '<tr><td>Year total</td>';
        meters.forEach(function (m) { foot += '<td class="enr-calc">' + wc.fmtVolume(totals[m.meterId] || 0) + '</td>'; });
        foot += '<td class="enr-calc">' + wc.fmtVolume(data.totalVolume) + '</td>';
        foot += '<td colspan="6"></td>';
        foot += '<td class="enr-calc">' + wc.fmtRm(data.totalRm) + '</td><td colspan="4"></td></tr>';
        $('#tfootWmo').html(foot);

        const missing = (data.months || []).filter(function (m) { return m.tariffMissing; });
        if (missing.length) {
            $('#lblWmoTariff').text('Some months have no tariff effective on the 1st. Calculated RM stays blank for those months.');
            $('#divWmoTariff').removeClass('d-none');
        } else {
            $('#divWmoTariff').addClass('d-none');
        }

        $('#lblWmoSummary').text('Year ' + data.year + ' · ' + (data.siteName || '') + ' · ' + wc.fmtNumber(data.totalVolume, 2) + ' m³');

        if (typeof Highcharts === 'undefined' || !meters.length) {
            if (window.GemsUI && GemsUI.emptyChart) {
                GemsUI.emptyChart('chartWmo', 'No data available', 'No water meters are configured for this site.');
            } else {
                $('#chartWmo').html(wc.emptyState('fa-gauge', 'No water meters are configured for this site'));
            }
            return;
        }
        Highcharts.chart('chartWmo', {
            chart: { type: 'column', backgroundColor: 'transparent', spacing: [8, 8, 8, 8] },
            title: { text: null },
            credits: { enabled: false },
            xAxis: { categories: (data.months || []).map(function (m) { return m.monthName.substr(0, 3); }) },
            yAxis: { title: { text: 'm³' }, min: 0, gridLineColor: '#E2E8F0', stackLabels: { enabled: true, format: '{total:,.0f}' } },
            tooltip: { shared: true, valueDecimals: 2 },
            legend: { itemStyle: { fontWeight: 600 } },
            plotOptions: { column: { stacking: 'normal', borderRadius: 3, borderWidth: 0 } },
            series: meters.map(function (meter, i) {
                return {
                    name: meter.meterName,
                    color: palette[i % palette.length],
                    data: (data.months || []).map(function (m) {
                        const found = (m.meterTotals || []).find(function (t) { return String(t.meterId) === String(meter.meterId); });
                        return found && m.daysWithData > 0 ? Number(found.totalVolume) : 0;
                    })
                };
            })
        });
    };

    const showSaved = function () {
        wc.setSaveState('lblWmoSaveState', 'saved');
        clearTimeout(savedHideTimer);
        savedHideTimer = setTimeout(function () { wc.setSaveState('lblWmoSaveState', 'idle'); }, 1600);
    };

    const saveBill = function (month, amount) {
        const gen = ++saveGen;
        wc.setSaveState('lblWmoSaveState', 'saving');
        return enqueue(function () {
            return wc.apiAsync('bill', 'POST', {
                siteId: $('#optWmoSite').val(),
                year: $('#optWmoYear').val(),
                month: month,
                actualRm: amount,
                autosave: 1
            }).then(function (next) {
                if (gen !== saveGen || !next) { return next; }
                data = next;
                const row = (data.months || []).find(function (m) { return String(m.month) === String(month); });
                if (row) {
                    $('.wmoDiff[data-month="' + month + '"]').html(wc.fmtRm(row.differenceRm));
                }
                showSaved();
                return next;
            }).catch(function (e) {
                if (gen === saveGen) {
                    wc.setSaveState('lblWmoSaveState', 'error', e.message);
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                throw e;
            });
        });
    };

    this.init = function () {
        wc.loadCaps();
        wc.loadSites();
        wc.fillSelect('optWmoSite', wc.sites, 'siteId', function (r) { return r.siteName; }, null, wc.caps.siteId || '');
        wc.fillYears('optWmoYear', new Date().getFullYear(), 5, 1);
        render();
        $('#optWmoSite, #optWmoYear').off('change').on('change', render);
        $('#btnWmoRefresh').off('click').on('click', render);
        $('#btnWmoExport').off('click').on('click', function () {
            wc.exportTable('tblWmo', 'GEMS Water Summary ' + (data ? data.year : ''));
        });
        const onBill = wc.debounce(function (input) {
            saveBill($(input).data('month'), $(input).val());
        }, 400);
        $(document).off('change', '.wmoActual').on('change', '.wmoActual', function () { onBill(this); });
    };
}
