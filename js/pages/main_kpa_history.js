function MainKpaHistory() {
    const kc = new KpaCommon();
    let dt;
    let data = null;
    const colors = {
        max: (window.GemsUI && GemsUI.kindColor('primary')) || '#0055b8',
        deducted: (window.GemsUI && GemsUI.kindColor('danger')) || '#dc2626',
        demerit: (window.GemsUI && GemsUI.kindColor('warning')) || '#9a6206',
        actual: (window.GemsUI && GemsUI.kindColor('info')) || '#00ada8',
        target: (window.GemsUI && GemsUI.kindColor('secondary')) || '#5b676f'
    };

    const emptyChart = function (id, message) {
        if (window.GemsUI && GemsUI.emptyChart) {
            GemsUI.emptyChart(id, 'No data available', message);
            return;
        }
        $('#' + id).html('<div class="gems-empty-state"><i class="fas fa-chart-column"></i><p>' + message + '</p></div>');
    };

    const renderApdChart = function () {
        const months = data.months || [];
        if (!months.length || typeof Highcharts === 'undefined') {
            emptyChart('chartKhiApd', 'No evaluation months in the selected range');
            return;
        }
        Highcharts.chart('chartKhiApd', {
            chart: { backgroundColor: 'transparent', spacing: [8, 8, 8, 8] },
            title: { text: null },
            credits: { enabled: false },
            xAxis: { categories: months.map(function (m) { return m.periodLabel; }) },
            yAxis: [
                { title: { text: 'RM' }, min: 0, gridLineColor: '#E2E8F0' },
                { title: { text: 'Demerit points' }, min: 0, opposite: true, gridLineWidth: 0 }
            ],
            tooltip: { shared: true },
            legend: { itemStyle: { fontWeight: 600 } },
            plotOptions: { column: { borderRadius: 4, borderWidth: 0 } },
            series: [
                { type: 'column', name: 'APD Maximum', color: colors.max, data: months.map(function (m) { return Number(m.apdMaxAmount); }) },
                { type: 'column', name: 'APD Deducted', color: colors.deducted, data: months.map(function (m) { return Number(m.totalApdDeducted); }) },
                { type: 'line', name: 'Demerit Points', color: colors.demerit, yAxis: 1, data: months.map(function (m) { return Number(m.totalDemerit); }) }
            ]
        });
    };

    const renderPiChart = function () {
        const piNo = $('#optKhiPi').val();
        const series = (data.piSeries || []).find(function (s) { return s.piNo === piNo; });
        if (!series || !series.points.length || typeof Highcharts === 'undefined') {
            emptyChart('chartKhiPi', 'Select an indicator with captured results');
            $('#lblKhiPiTitle').text('Indicator achievement');
            return;
        }
        $('#lblKhiPiTitle').text('PI ' + series.piNo + ' — ' + series.piName);
        const categories = series.points.map(function (p) { return p.periodLabel; });
        Highcharts.chart('chartKhiPi', {
            chart: { backgroundColor: 'transparent', spacing: [8, 8, 8, 8] },
            title: { text: null },
            credits: { enabled: false },
            xAxis: { categories: categories },
            yAxis: { title: { text: 'Achievement' }, gridLineColor: '#E2E8F0' },
            tooltip: { shared: true },
            series: [
                {
                    type: 'column', name: 'Achievement', color: colors.actual,
                    data: series.points.map(function (p) {
                        return {
                            y: p.resultPct === null ? null : Number(p.resultPct),
                            color: p.isPass === false ? colors.deducted : colors.actual
                        };
                    })
                },
                {
                    type: 'line', name: 'Target', color: colors.target, dashStyle: 'ShortDash',
                    marker: { enabled: false },
                    data: categories.map(function () { return Number(series.targetValue); })
                }
            ]
        });
    };

    const render = function () {
        const p = [];
        if ($('#optKhiSite').val()) { p.push('siteId=' + $('#optKhiSite').val()); }
        p.push('fromYear=' + ($('#optKhiFrom').val() || new Date().getFullYear()));
        p.push('toYear=' + ($('#optKhiTo').val() || new Date().getFullYear()));
        data = kc.apiGet('report/history?' + p.join('&'));
        if (!data) { return; }

        const totals = data.totals || {};
        $('#mKhiApdMax').text(kc.fmtMoney(totals.apdMax));
        $('#mKhiApdDeducted').text(kc.fmtMoney(totals.apdDeducted));
        $('#mKhiApdRetained').text(kc.fmtMoney((Number(totals.apdMax) || 0) - (Number(totals.apdDeducted) || 0)));
        $('#mKhiDemerit').text(totals.demerit || 0);
        $('#lblKhiSummary').text((data.months || []).length + ' evaluation month(s) · ' + (data.siteName || ''));

        const previous = $('#optKhiPi').val();
        kc.fillSelect('optKhiPi', data.piSeries || [], 'piNo', function (s) {
            return s.piNo + ' — ' + s.piName;
        }, null, previous || ((data.piSeries || [])[0] || {}).piNo);

        dt.clear().rows.add(data.months || []).draw();
        renderApdChart();
        renderPiChart();
    };

    this.init = function () {
        kc.loadCaps();
        kc.loadSites();
        kc.fillSelect('optKhiSite', kc.sites, 'siteId', function (r) { return r.siteName; }, null, kc.caps.siteId || '');
        const thisYear = new Date().getFullYear();
        kc.fillYears('optKhiFrom', thisYear - 1, 5, 0);
        kc.fillYears('optKhiTo', thisYear, 5, 1);

        dt = $('#dtKhi').DataTable({
            data: [], bLengthChange: false, searching: false, pageLength: 24, autoWidth: false,
            language: kc.dtEmpty('fa-clock-rotate-left', 'No evaluation months in the selected range.'),
            ordering: false, dom: kc.dtDomButtons,
            buttons: kc.dtButtons('GEMS - KPI History'),
            columns: [
                { data: 'periodLabel' },
                { data: 'mpv', className: 'gems-num', render: function (v) { return kc.fmtNumber(v, 2); } },
                { data: 'maxApdPct', render: function (v) { return kc.fmtNumber(v, 2) + ' %'; } },
                { data: 'apdMaxAmount', className: 'gems-num', render: function (v) { return kc.fmtNumber(v, 2); } },
                { data: 'totalApdDeducted', className: 'gems-num', render: function (v) { return kc.fmtNumber(v, 2); } },
                { data: 'apdRetained', className: 'gems-num', render: function (v) { return kc.fmtNumber(v, 2); } },
                { data: 'totalDemerit' },
                { data: null, render: function (r) { return r.piSubmitted + ' / ' + r.piTotal; } },
                { data: 'evalStatus', render: kc.evalStatusBadge }
            ]
        });
        dt.buttons().container().appendTo($('#btnDtKhiExport'));
        kc.bindDtTooltips('#dtKhi');

        render();
        $('#optKhiSite, #optKhiFrom, #optKhiTo').off('change').on('change', render);
        $('#optKhiPi').off('change').on('change', renderPiChart);
        $('#btnKhiRefresh').off('click').on('click', render);
    };
}
