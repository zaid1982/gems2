function MainWasteDashboard() {
    const wc = new WasteCommon();
    let data = null;
    let dt;
    const colors = { produced: '#00ada8', disposed: '#dc2626', current: '#0055b8' };

    const filters = function () {
        const p = [];
        if ($('#txtWdbFrom').val()) p.push('periodFrom=' + $('#txtWdbFrom').val());
        if ($('#txtWdbTo').val()) p.push('periodTo=' + $('#txtWdbTo').val() + '&asAt=' + $('#txtWdbTo').val());
        if ($('#optWdbSite').val()) p.push('siteIds=' + $('#optWdbSite').val());
        if ($('#optWdbSw').val()) p.push('swCodeIds=' + $('#optWdbSw').val());
        p.push('granularity=' + ($('#optWdbGrain').val() || 'day'));
        return p.join('&');
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
        $('#lblWdbUpdated').text('Last refreshed: ' + (data.refreshedAt || '-'));
        $('#lblWdbFilter').text('Period ' + (data.filters.periodFrom) + ' to ' + data.filters.periodTo + ' · As-at ' + data.filters.asAt);

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
            language: _DATATABLE_LANGUAGE,
            dom: 't<"dataTables-footer"ip>',
            columns: [
                { data: 'siteName' },
                { data: 'swCode' },
                { data: 'swDescription' },
                { data: 'openingKg', className: 'text-qty', render: wc.fmtQty },
                { data: 'producedKg', className: 'text-qty', render: wc.fmtQty },
                { data: 'disposedKg', className: 'text-qty', render: wc.fmtQty },
                { data: 'closingKg', className: 'text-qty', render: wc.fmtQty }
            ],
            createdRow: function (row) {
                const labels = ['Premise', 'SW Code', 'Description', 'Opening', 'Produced', 'Disposed', 'Closing'];
                $('td', row).each(function (i) { $(this).attr('data-label', labels[i]); });
            }
        });
        render();
        $('#btnWdbRefresh, #txtWdbFrom, #txtWdbTo, #optWdbSite, #optWdbSw, #optWdbGrain').on('change click', function (e) {
            if (e.type === 'click' && this.id !== 'btnWdbRefresh') return;
            render();
        });
        $('#optWdbAnalysis').on('change', renderAnalysis);
        $('.metric-card').on('click', function () {
            const kpi = $(this).data('kpi');
            if (kpi === 'produced') drill('type=P&status=FINAL');
            else if (kpi === 'disposed') drill('type=D&status=FINAL');
            else if (kpi === 'final') drill('status=FINAL');
            else if (kpi === 'draft') drill('status=DRAFT');
            else drill('status=FINAL');
        });
    };
}
