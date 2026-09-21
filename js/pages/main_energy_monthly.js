function MainEnergyMonthly() {
    const ec = new EnergyCommon();
    let data = null;
    const palette = (window.GemsUI && GemsUI.chartColors()) || ['#6b9cd6', '#6bcfcd', '#7ab597', '#c4a46f', '#eb8181', '#70bfd2'];

    const render = function () {
        data = ec.apiGet('monthly?siteId=' + ($('#optEmoSite').val() || '') + '&year=' + ($('#optEmoYear').val() || new Date().getFullYear()));
        if (!data) { return; }
        const meters = data.meters || [];

        let head = '<tr><th>Month</th>';
        meters.forEach(function (m) { head += '<th class="enr-calc">' + ec.escape(m.meterName) + ' (kWh)</th>'; });
        head += '<th class="enr-calc">Total kWh</th><th class="enr-calc">Average Daily</th><th class="enr-calc">Days with data</th></tr>';
        $('#theadEmo').html(head);

        $('#tbodyEmo').html((data.months || []).map(function (m) {
            const byMeter = {};
            (m.meterTotals || []).forEach(function (t) { byMeter[t.meterId] = t.totalKwh; });
            let tds = '<td><strong>' + m.monthName + '</strong></td>';
            meters.forEach(function (meter) {
                tds += '<td class="enr-calc">' + ec.fmtKwh(byMeter[meter.meterId] || 0) + '</td>';
            });
            tds += '<td class="enr-calc"><strong>' + ec.fmtKwh(m.totalKwh) + '</strong></td>';
            tds += '<td class="enr-calc">' + ec.fmtKwh(m.averageDaily) + '</td>';
            tds += '<td class="enr-calc">' + m.daysWithData + ' / ' + m.daysInMonth + '</td>';
            return '<tr>' + tds + '</tr>';
        }).join(''));

        const totals = {};
        (data.meterTotals || []).forEach(function (t) { totals[t.meterId] = t.totalKwh; });
        let foot = '<tr><td>Year total</td>';
        meters.forEach(function (m) { foot += '<td class="enr-calc">' + ec.fmtKwh(totals[m.meterId] || 0) + '</td>'; });
        foot += '<td class="enr-calc">' + ec.fmtKwh(data.totalKwh) + '</td><td colspan="2"></td></tr>';
        $('#tfootEmo').html(foot);

        $('#lblEmoSummary').text('Year ' + data.year + ' · ' + (data.siteName || '') + ' · ' + ec.fmtNumber(data.totalKwh, 2) + ' kWh total');

        if (typeof Highcharts === 'undefined' || !meters.length) {
            if (window.GemsUI && GemsUI.emptyChart) {
                GemsUI.emptyChart('chartEmo', 'No data available', 'No incoming meters are configured for this site.');
            } else {
                $('#chartEmo').html(ec.emptyState('fa-gauge', 'No incoming meters are configured for this site'));
            }
            return;
        }
        Highcharts.chart('chartEmo', {
            chart: { type: 'column', backgroundColor: 'transparent', spacing: [8, 8, 8, 8] },
            title: { text: null },
            credits: { enabled: false },
            xAxis: { categories: (data.months || []).map(function (m) { return m.monthName.substr(0, 3); }) },
            yAxis: { title: { text: 'kWh' }, min: 0, gridLineColor: '#E2E8F0', stackLabels: { enabled: true, format: '{total:,.0f}' } },
            tooltip: { shared: true, valueDecimals: 2 },
            legend: { itemStyle: { fontWeight: 600 } },
            plotOptions: { column: { stacking: 'normal', borderRadius: 3, borderWidth: 0 } },
            series: meters.map(function (meter, i) {
                return {
                    name: meter.meterName,
                    color: palette[i % palette.length],
                    data: (data.months || []).map(function (m) {
                        const found = (m.meterTotals || []).find(function (t) { return String(t.meterId) === String(meter.meterId); });
                        return found ? Number(found.totalKwh) : 0;
                    })
                };
            })
        });
    };

    this.init = function () {
        ec.loadCaps();
        ec.loadSites();
        ec.fillSelect('optEmoSite', ec.sites, 'siteId', function (r) { return r.siteName; }, null, ec.caps.siteId || '');
        ec.fillYears('optEmoYear', new Date().getFullYear(), 5, 1);
        render();
        $('#optEmoSite, #optEmoYear').off('change').on('change', render);
        $('#btnEmoRefresh').off('click').on('click', render);
        $('#btnEmoExport').off('click').on('click', function () {
            ec.exportTable('tblEmo', 'GEMS Monthly Electricity ' + (data ? data.year : ''));
        });
    };
}
