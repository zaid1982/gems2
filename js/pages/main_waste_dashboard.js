function MainWasteDashboard() {
    const wc = new WasteCommon();
    let data = null;
    let dt;
    let dtHist;
    const colors = { produced: '#00ada8', disposed: '#dc2626', current: '#0055b8', pending: '#f59e0b' };

    const filters = function () {
        const p = [];
        if ($('#txtWdbFrom').val()) p.push('periodFrom=' + $('#txtWdbFrom').val());
        if ($('#txtWdbTo').val()) p.push('periodTo=' + $('#txtWdbTo').val() + '&asAt=' + $('#txtWdbTo').val());
        if ($('#optWdbSite').val()) p.push('siteIds=' + $('#optWdbSite').val());
        if ($('#optWdbSw').val()) p.push('swCodeIds=' + $('#optWdbSw').val());
        p.push('granularity=' + ($('#optWdbGrain').val() || 'day'));
        return p.join('&');
    };

    /**
     * Month/Year selection drives the date inputs so the period filters and the
     * quick filter never disagree.
     */
    const applyMonthYear = function () {
        const year = Number($('#optWdbYear').val());
        const month = Number($('#optWdbMonth').val());
        if (!year) { return; }
        if (month >= 1 && month <= 12) {
            const start = moment({ year: year, month: month - 1, day: 1 });
            $('#txtWdbFrom').val(start.format('YYYY-MM-DD'));
            $('#txtWdbTo').val(start.endOf('month').format('YYYY-MM-DD'));
        } else {
            $('#txtWdbFrom').val(moment({ year: year, month: 0, day: 1 }).format('YYYY-MM-DD'));
            $('#txtWdbTo').val(moment({ year: year, month: 11, day: 31 }).format('YYYY-MM-DD'));
        }
    };

    const drill = function (extra) {
        const q = [];
        if ($('#txtWdbFrom').val()) q.push('from=' + $('#txtWdbFrom').val());
        if ($('#txtWdbTo').val()) q.push('to=' + $('#txtWdbTo').val());
        if ($('#optWdbSite').val()) q.push('siteId=' + $('#optWdbSite').val());
        if ($('#optWdbSw').val()) q.push('swCodeId=' + $('#optWdbSw').val());
        if (extra) q.push(extra);
        wc.goRecords(q.join('&'));
    };

    const emptyChart = function (id, message) {
        $('#' + id).html('<div class="waste-empty">' + message + '</div>');
    };

    const chart = function (id, type, cats, series, title) {
        if (!cats.length || typeof Highcharts === 'undefined') {
            emptyChart(id, 'No confirmed transactions in this period');
            return;
        }
        Highcharts.chart(id, {
            chart: { type: type, backgroundColor: 'transparent', spacing: [8, 8, 8, 8] },
            title: { text: null },
            xAxis: { categories: cats, labels: { style: { color: '#5b676f' } } },
            yAxis: { title: { text: title || 'kg' }, min: 0, gridLineColor: '#E2E8F0' },
            legend: { itemStyle: { fontWeight: 600 } },
            credits: { enabled: false },
            colors: [colors.produced, colors.disposed, colors.current],
            plotOptions: { column: { borderRadius: 4, borderWidth: 0, groupPadding: 0.18 } },
            series: series
        });
    };

    const renderAnalysis = function () {
        if (!data) { return; }
        const key = $('#optWdbAnalysis').val() || 'packaging';
        const rows = (data.analysis && data.analysis[key]) || [];
        const groups = {};
        rows.forEach(function (r) {
            const name = r.groupName || 'Not specified';
            if (!groups[name]) groups[name] = { p: 0, d: 0 };
            if (r.txnType === 'P') groups[name].p = Number(r.qtyKg); else groups[name].d = Number(r.qtyKg);
        });
        const cats = Object.keys(groups);
        chart('chartAnalysis', 'column', cats, [
            { name: 'Produced', data: cats.map(function (c) { return groups[c].p; }) },
            { name: 'Disposed', data: cats.map(function (c) { return groups[c].d; }) }
        ]);
    };

    const renderRecent = function (recent) {
        const rows = recent || [];
        if (!rows.length) {
            $('#tblRecent tbody').html('<tr><td colspan="5" class="text-muted">No confirmed transactions</td></tr>');
            return;
        }
        $('#tblRecent tbody').html(rows.map(function (r) {
            return '<tr>' +
                '<td><a href="p_waste_record_form?id=' + r.txnId + '">' + (r.txnRef || '-') + '</a></td>' +
                '<td>' + wc.typeLabel(r.txnType) + '</td>' +
                '<td>' + (r.swCode || '-') + '</td>' +
                '<td>' + wc.fmtDate(r.eventDate) + '</td>' +
                '<td>' + wc.statusBadge(r.txnStatus) + '</td>' +
                '</tr>';
        }).join(''));
    };

    /**
     * One row per waste type combining period movement, pending and closing.
     */
    const renderHistorical = function () {
        const pendingMap = {};
        (data.pendingBySw || []).forEach(function (r) { pendingMap[r.swCode] = Number(r.pendingKg); });
        const disposedMap = {};
        (data.disposedBySw || []).forEach(function (r) { disposedMap[r.swCode] = Number(r.disposedKg); });
        const codes = {};
        (data.bySwCode || []).forEach(function (r) {
            codes[r.swCode] = {
                swCode: r.swCode,
                swDescription: r.swDescription,
                producedKg: Number(r.producedKg),
                disposedKg: Number(r.disposedKg),
                pendingKg: pendingMap[r.swCode] || 0,
                closingKg: Number(r.closingKg)
            };
        });
        Object.keys(pendingMap).concat(Object.keys(disposedMap)).forEach(function (code) {
            if (!codes[code]) {
                codes[code] = {
                    swCode: code,
                    swDescription: '',
                    producedKg: 0,
                    disposedKg: disposedMap[code] || 0,
                    pendingKg: pendingMap[code] || 0,
                    closingKg: 0
                };
            }
        });
        dtHist.clear().rows.add(Object.keys(codes).sort().map(function (c) { return codes[c]; })).draw();
    };

    const render = function () {
        data = wc.apiGet('dashboard?' + filters());
        const k = data.kpis || {};
        $('#kOpen').text(wc.fmtQty(k.openingKg || 0));
        $('#kProd').text(wc.fmtQty(k.producedKg || 0));
        $('#kDisp').text(wc.fmtQty(k.disposedKg || 0));
        $('#kNet').text(wc.fmtQty(k.netKg || 0));
        $('#kCur').text(wc.fmtQty(k.currentKg || 0));
        $('#kFinal').text(k.finalTransactions || 0);
        $('#kDraft').text(k.draftRecords || 0);
        $('#kPending').text(wc.fmtQty(k.pendingTotalKg || 0));
        $('#lblWdbUpdated').text('Last refreshed: ' + (data.refreshedAt || '-'));
        $('#lblWdbFilter').text('Period ' + (data.filters.periodFrom) + ' to ' + data.filters.periodTo + ' · As-at ' + data.filters.asAt);

        const pending = data.pendingBySw || [];
        chart('chartPending', 'bar', pending.map(function (r) { return r.swCode; }), [
            { name: 'Pending', color: colors.pending, data: pending.map(function (r) { return Number(r.pendingKg); }) }
        ]);
        const disposed = data.disposedBySw || [];
        chart('chartDisposed', 'bar', disposed.map(function (r) { return r.swCode; }), [
            { name: 'Disposed', color: colors.disposed, data: disposed.map(function (r) { return Number(r.disposedKg); }) }
        ]);
        renderHistorical();

        const trend = data.trend || [];
        chart('chartTrend', 'column', trend.map(function (r) { return r.period; }), [
            { name: 'Produced', color: colors.produced, data: trend.map(function (r) { return Number(r.producedKg); }) },
            { name: 'Disposed', color: colors.disposed, data: trend.map(function (r) { return Number(r.disposedKg); }) }
        ]);
        const by = (data.bySwCode || []).filter(function (r) { return Number(r.producedKg) > 0; });
        if (!by.length || typeof Highcharts === 'undefined') {
            emptyChart('chartSw', 'No produced quantity in this period');
        } else {
            Highcharts.chart('chartSw', {
                chart: { type: 'pie', backgroundColor: 'transparent' },
                title: { text: null },
                credits: { enabled: false },
                tooltip: { pointFormat: '{series.name}: <b>{point.y:,.3f} kg</b>' },
                plotOptions: { pie: { innerSize: '58%', borderWidth: 0, dataLabels: { enabled: true, format: '{point.name}' } } },
                series: [{ name: 'Produced kg', data: by.map(function (r) { return { name: r.swCode, y: Number(r.producedKg) }; }) }]
            });
        }
        const prem = data.premiseComparison || [];
        chart('chartPremise', 'column', prem.map(function (r) { return r.siteCode; }), [
            { name: 'Produced', color: colors.produced, data: prem.map(function (r) { return Number(r.producedKg); }) },
            { name: 'Disposed', color: colors.disposed, data: prem.map(function (r) { return Number(r.disposedKg); }) },
            { name: 'Current', color: colors.current, data: prem.map(function (r) { return Number(r.currentKg); }) }
        ]);
        renderAnalysis();
        dt.clear().rows.add(data.balanceTable || []).draw();
        renderRecent(data.recent);
    };

    this.init = function () {
        const now = new Date();
        const first = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10);
        const last = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10);
        $('#txtWdbFrom').val(first);
        $('#txtWdbTo').val(last);
        const thisYear = now.getFullYear();
        const years = [];
        for (let y = thisYear + 1; y >= thisYear - 5; y--) { years.push({ y: y }); }
        wc.fillSelect('optWdbYear', years, 'y', function (r) { return String(r.y); }, 'All years', thisYear);
        $('#optWdbMonth').val(String(now.getMonth() + 1));
        wc.loadCaps();
        wc.loadLookups(wc.caps.siteId);
        wc.fillSelect('optWdbSite', wc.sites, 'siteId', function (r) { return r.siteName; }, 'All authorised');
        wc.fillSelect('optWdbSw', wc.swCodes, 'swCodeId', function (r) { return r.swCode + ' — ' + (r.swDescription || ''); }, 'All');
        dt = $('#dtBal').DataTable({
            data: [],
            bLengthChange: false,
            searching: false,
            pageLength: 10,
            autoWidth: false,
            language: wc.dtEmpty('fa-balance-scale', 'No balance rows for this period.'),
            dom: wc.dtDom,
            columns: [
                { data: 'siteName' },
                { data: 'swCode' },
                { data: 'swDescription' },
                { data: 'openingKg', className: 'gems-num', render: wc.fmtQty },
                { data: 'producedKg', className: 'gems-num', render: wc.fmtQty },
                { data: 'disposedKg', className: 'gems-num', render: wc.fmtQty },
                { data: 'closingKg', className: 'gems-num', render: wc.fmtQty }
            ]
        });
        dtHist = $('#dtWdbHist').DataTable({
            data: [],
            bLengthChange: false,
            searching: false,
            pageLength: 10,
            autoWidth: false,
            language: wc.dtEmpty('fa-layer-group', 'No historical waste by type for this period.'),
            dom: wc.dtDomButtons,
            buttons: wc.dtButtons('GEMS - Historical Waste by Type'),
            columns: [
                { data: 'swCode' },
                { data: 'swDescription' },
                { data: 'producedKg', className: 'gems-num', render: wc.fmtQty },
                { data: 'disposedKg', className: 'gems-num', render: wc.fmtQty },
                { data: 'pendingKg', className: 'gems-num', render: wc.fmtQty },
                { data: 'closingKg', className: 'gems-num', render: wc.fmtQty }
            ]
        });
        dtHist.buttons().container().appendTo($('#btnDtWdbHistExport'));

        render();
        $('#optWdbMonth, #optWdbYear').on('change', function () {
            applyMonthYear();
            render();
        });
        $('#btnWdbRefresh, #txtWdbFrom, #txtWdbTo, #optWdbSite, #optWdbSw, #optWdbGrain').on('change click', function (e) {
            if (e.type === 'click' && this.id !== 'btnWdbRefresh') return;
            render();
        });
        $('#optWdbAnalysis').on('change', renderAnalysis);
        $('#btnWdbPendExcel').on('click', function () { dtHist.button('.buttons-excel').trigger(); });
        $('#btnWdbPendPrint').on('click', function () {
            const c = Highcharts.charts.find(function (x) { return x && x.renderTo && x.renderTo.id === 'chartPending'; });
            if (c) { c.print(); }
        });
        $('.metric-card').on('click', function () {
            const kpi = $(this).data('kpi');
            if (kpi === 'produced') drill('type=P&status=FINAL');
            else if (kpi === 'disposed') drill('type=D&status=FINAL');
            else if (kpi === 'final') drill('status=FINAL');
            else if (kpi === 'draft') drill('status=DRAFT');
            else if (kpi === 'pending') { window.location.href = 'p_waste_pending'; }
            else drill('status=FINAL');
        });
    };
}
