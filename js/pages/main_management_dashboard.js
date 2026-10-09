function MainManagementDashboard() {
    const self = this;
    const state = {
        sites: [],
        clients: [],
        modules: {},
        defaultPeriod: { from: '', to: '' },
        data: {},
        requestId: 0
    };
    const moduleKey = {
        wo: 'workOrder',
        ppm: 'ppm',
        assets: 'assets',
        ptw: 'ptw',
        waste: 'waste',
        licenses: 'license'
    };
    const charts = {
        wo: ['chartWoStatus', 'chartWoDonut', 'chartWoType', 'chartWoTrend'],
        ppm: ['chartPpmStatus', 'chartPpmDonut', 'chartPpmTrend'],
        assets: ['chartAssets'],
        ptw: ['chartPtwDonut', 'chartPtwSites'],
        waste: ['chartWasteTrend', 'chartWasteSites'],
        licenses: ['chartLicense']
    };
    const kpiIds = {
        wo: ['kpiWoOpen', 'kpiWoDone'],
        ppm: ['kpiPpmScheduled', 'kpiPpmLate', 'kpiPpmDone'],
        assets: ['kpiAssets'],
        ptw: ['kpiPtwActive', 'kpiPtwSoon'],
        waste: ['kpiWasteGen', 'kpiWasteDis', 'kpiWastePend'],
        licenses: ['kpiLicExp', 'kpiLic30']
    };
    const alerts = {
        wo: 'alertWo',
        ppm: 'alertPpm',
        assets: 'alertAssets',
        ptw: 'alertPtw',
        waste: 'alertWaste',
        licenses: 'alertLicense'
    };

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatInt(value) {
        return Number(value || 0).toLocaleString();
    }

    function formatKg(value) {
        return Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 1 });
    }

    function formatPct(value) {
        return Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 1 }) + '%';
    }

    function formatDate(iso) {
        const parts = String(iso || '').split('-');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        if (parts.length !== 3) {
            return iso || '';
        }
        return parseInt(parts[2], 10) + ' ' + (months[parseInt(parts[1], 10) - 1] || '') + ' ' + parts[0];
    }

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = value;
        }
    }

    const CHART_COLORS = ['#2F7CF6', '#20B97A', '#FF9838', '#8258E8', '#F65353', '#14B8B1', '#F7B719', '#4AB7E8', '#B87540', '#94A3B8'];
    const STATUS_COLORS = {
        'Completed': '#20B97A',
        'Disposed': '#20B97A',
        'In Progress': '#2F7CF6',
        'Open': '#2F7CF6',
        'Active': '#2F7CF6',
        'Created': '#2F7CF6',
        'Scheduled': '#2F7CF6',
        'Assets': '#2F7CF6',
        'Responding': '#4AB7E8',
        'Approved': '#14B8B1',
        'Generated': '#14B8B1',
        'Verify': '#F7B719',
        'Pending': '#F7B719',
        'Within 30 days': '#F7B719',
        'Check': '#FF9838',
        'Open work orders': '#FF9838',
        'Late PPM': '#F65353',
        'Expired': '#F65353',
        'Cancelled': '#94A3B8',
        'Draft': '#94A3B8',
        'Other': '#8258E8'
    };

    function colorFor(name, index) {
        if (STATUS_COLORS[name]) {
            return STATUS_COLORS[name];
        }
        const n = Number(index);
        return CHART_COLORS[(isNaN(n) ? 0 : n) % CHART_COLORS.length];
    }

    function tint(hex, alpha) {
        const value = String(hex || '#2F7CF6').replace('#', '');
        const red = parseInt(value.substring(0, 2), 16);
        const green = parseInt(value.substring(2, 4), 16);
        const blue = parseInt(value.substring(4, 6), 16);
        return 'rgba(' + red + ',' + green + ',' + blue + ',' + alpha + ')';
    }

    function endOverlay(id) {
        $('#overlay-' + id).removeClass('show');
    }

    function beginCharts(ids) {
        (ids || []).forEach(function (id) {
            $('#overlay-' + id).addClass('show');
        });
    }

    function showEmpty(id, title, detail) {
        endOverlay(id);
        if (window.GemsUI && typeof GemsUI.emptyChart === 'function') {
            GemsUI.emptyChart(id, title, detail);
            return;
        }
        const el = document.getElementById(id);
        if (el) {
            el.textContent = title || 'No data';
        }
    }

    function mountChart(id, options) {
        endOverlay(id);
        if (typeof Highcharts === 'undefined') {
            showEmpty(id, 'Charts are unavailable');
            return;
        }
        (Highcharts.charts || []).forEach(function (chart) {
            if (chart && chart.renderTo && chart.renderTo.id === id) {
                chart.destroy();
            }
        });
        const el = document.getElementById(id);
        if (el) {
            el.innerHTML = '';
        }
        Highcharts.chart(id, options);
    }

    function reflowCharts() {
        if (typeof Highcharts === 'undefined') {
            return;
        }
        (Highcharts.charts || []).forEach(function (chart) {
            if (chart) {
                chart.reflow();
            }
        });
    }

    function topRows(rows, score, limit) {
        return (rows || []).slice().sort(function (a, b) {
            return score(b) - score(a);
        }).slice(0, limit || 12);
    }

    function stackedColumn(id, rows, nameOf, seriesDefs, emptyTitle) {
        const kept = (seriesDefs || []).filter(function (series) {
            return rows.some(function (row) { return Number(row[series.key] || 0) > 0; });
        });
        if (!rows.length || !kept.length) {
            showEmpty(id, emptyTitle || 'No records in this period');
            return;
        }
        mountChart(id, {
            chart: { type: 'column', backgroundColor: '#ffffff' },
            title: { text: null },
            xAxis: {
                categories: rows.map(nameOf),
                labels: { rotation: rows.length > 6 ? -35 : 0 }
            },
            yAxis: { min: 0, title: { text: null }, stackLabels: { enabled: false } },
            plotOptions: { column: { stacking: 'normal' } },
            legend: { enabled: true },
            series: kept.map(function (series, index) {
                return {
                    name: series.name,
                    color: colorFor(series.name, index),
                    data: rows.map(function (row) { return Number(row[series.key] || 0); })
                };
            })
        });
    }

    function groupedColumn(id, rows, nameOf, seriesDefs, emptyTitle) {
        const kept = (seriesDefs || []).filter(function (series) {
            return rows.some(function (row) { return Number(row[series.key] || 0) > 0; });
        });
        if (!rows.length || !kept.length) {
            showEmpty(id, emptyTitle || 'No records in this view');
            return;
        }
        mountChart(id, {
            chart: { type: 'column', backgroundColor: '#ffffff' },
            title: { text: null },
            xAxis: { categories: rows.map(nameOf), labels: { rotation: rows.length > 6 ? -35 : 0 } },
            yAxis: { min: 0, title: { text: null } },
            legend: { enabled: kept.length > 1 },
            series: kept.map(function (series, index) {
                return {
                    name: series.name,
                    color: series.color || colorFor(series.name, index),
                    data: rows.map(function (row) { return Number(row[series.key] || 0); })
                };
            })
        });
    }

    function donut(id, points, emptyTitle) {
        const data = (points || []).filter(function (point) {
            return Number(point.y) > 0;
        }).map(function (point, index) {
            return { name: point.name, y: Number(point.y), color: colorFor(point.name, index) };
        });
        if (!data.length) {
            showEmpty(id, emptyTitle || 'No records in this view');
            return;
        }
        const total = data.reduce(function (sum, point) { return sum + point.y; }, 0);
        mountChart(id, {
            chart: { type: 'pie', backgroundColor: '#ffffff' },
            title: {
                text: total.toLocaleString(),
                align: 'center',
                verticalAlign: 'middle',
                y: 8,
                style: { fontSize: '22px', fontWeight: '700', color: '#172033' }
            },
            subtitle: {
                text: 'Total',
                align: 'center',
                verticalAlign: 'middle',
                y: 28,
                style: { fontSize: '11px', fontWeight: '500', color: '#94A3B8' }
            },
            legend: { align: 'center', verticalAlign: 'bottom', itemStyle: { color: '#64748B', fontWeight: '500' } },
            plotOptions: {
                pie: { innerSize: '68%', borderWidth: 2, borderColor: '#ffffff', dataLabels: { enabled: false }, showInLegend: true }
            },
            series: [{ name: 'Records', data: data }]
        });
    }

    function trendChart(id, rows, seriesDefs, emptyTitle) {
        if (!rows || !rows.length) {
            showEmpty(id, emptyTitle || 'No records in this period');
            return;
        }
        mountChart(id, {
            chart: { type: 'areaspline', backgroundColor: '#ffffff' },
            title: { text: null },
            xAxis: { categories: rows.map(function (row) { return row.period; }) },
            yAxis: { min: 0, title: { text: null } },
            legend: { enabled: true },
            series: seriesDefs.map(function (series, index) {
                const color = series.color || colorFor(series.name, index);
                return {
                    name: series.name,
                    color: color,
                    fillColor: {
                        linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                        stops: [[0, tint(color, 0.28)], [1, tint(color, 0.03)]]
                    },
                    lineWidth: 2,
                    data: rows.map(function (row) { return Number(row[series.key] || 0); })
                };
            })
        });
    }

    function horizontal(id, categories, seriesDefs, emptyTitle) {
        if (!categories.length) {
            showEmpty(id, emptyTitle || 'Nothing needs attention in this view');
            return;
        }
        mountChart(id, {
            chart: { type: 'bar', backgroundColor: '#ffffff' },
            title: { text: null },
            xAxis: { categories: categories },
            yAxis: { min: 0, title: { text: null } },
            plotOptions: { series: { stacking: 'normal' } },
            legend: { enabled: true },
            series: (seriesDefs || []).map(function (series, index) {
                const next = Object.assign({}, series);
                if (!next.color) {
                    next.color = colorFor(next.name, index);
                }
                return next;
            })
        });
    }

    function clearKpis(key) {
        (kpiIds[key] || []).forEach(function (id) { setText(id, '—'); });
    }

    function showAlert(key, message) {
        const id = alerts[key];
        if (!id) {
            return;
        }
        $('#' + id).removeClass('d-none').html(
            esc(message || 'This section could not be loaded.') +
            ' <button type="button" class="btn btn-sm btn-outline-danger ms-2" data-retry="' + key + '">Retry</button>'
        );
    }

    function clearAlert(key) {
        const id = alerts[key];
        if (id) {
            $('#' + id).addClass('d-none').empty();
        }
    }

    function failCharts(ids, message) {
        (ids || []).forEach(function (id) {
            showEmpty(id, 'Unable to load this chart', message);
        });
    }

    function queryString() {
        const params = new URLSearchParams();
        const clientId = $('#selMgdClient').val();
        const sites = selectedSiteIds();
        const siteBoxes = $('#listMgdSites .chk-mgd-site');
        if (clientId) {
            params.set('clientId', clientId);
        }
        if (sites.length && sites.length < siteBoxes.length) {
            params.set('siteIds', sites.join(','));
        }
        params.set('dateFrom', $('#txtMgdDateFrom').val());
        params.set('dateTo', $('#txtMgdDateTo').val());
        params.set('granularity', $('#selMgdGrain').val() || 'day');
        return params.toString();
    }

    function dashFetch(url) {
        const headers = { 'Accept': 'application/json' };
        const token = sessionStorage.getItem('token');
        if (token) {
            headers.Authorization = 'Bearer ' + token;
        }
        return fetch(url, { method: 'GET', headers: headers }).then(function (response) {
            if (!response.ok) {
                throw new Error(response.statusText || 'Request failed');
            }
            return response.json().catch(function () {
                throw new Error('This section could not be read. Try again.');
            });
        }).then(function (body) {
            const detail = body && (body.error || body.errmsg || '');
            if (body && (body.errmsg === 'Expired token' || /expired token|signature verification|wrong number of segments/i.test(detail))) {
                window.location.href = 'p_login?f=2';
                throw new Error('Expired token');
            }
            if (!body || !body.success) {
                throw new Error((body && body.errmsg) ? body.errmsg : 'Unable to load this section.');
            }
            return body.result;
        });
    }

    function renderWo(result) {
        const kpis = result.kpis || {};
        setText('kpiWoOpen', formatInt(kpis.open));
        setText('kpiWoDone', formatInt(kpis.completed));
        const rows = topRows(result.bySite || [], function (row) { return Number(row.total || 0); }, 12);
        stackedColumn('chartWoStatus', rows, function (row) { return row.siteName; }, [
            { key: 'responding', name: 'Responding' },
            { key: 'inProgress', name: 'In Progress' },
            { key: 'verify', name: 'Verify' },
            { key: 'completed', name: 'Completed' },
            { key: 'cancelled', name: 'Cancelled' },
            { key: 'other', name: 'Other' }
        ]);
        donut('chartWoDonut', [
            { name: 'Responding', y: kpis.responding },
            { name: 'In Progress', y: kpis.inProgress },
            { name: 'Verify', y: kpis.verify },
            { name: 'Completed', y: kpis.completed },
            { name: 'Cancelled', y: kpis.cancelled },
            { name: 'Other', y: kpis.other }
        ]);
        donut('chartWoType', result.byType || []);
        trendChart('chartWoTrend', result.trend || [], [
            { key: 'created', name: 'Created' },
            { key: 'completed', name: 'Completed' }
        ]);
    }

    function renderPpm(result) {
        const kpis = result.kpis || {};
        setText('kpiPpmScheduled', formatInt(kpis.scheduled));
        setText('kpiPpmLate', formatInt(kpis.late));
        setText('kpiPpmDone', formatPct(kpis.percDone));
        const rows = topRows(result.bySite || [], function (row) { return Number(row.scheduled || 0); }, 12);
        stackedColumn('chartPpmStatus', rows, function (row) { return row.siteName; }, [
            { key: 'statusOpen', name: 'Open' },
            { key: 'statusInProgress', name: 'In Progress' },
            { key: 'statusCheck', name: 'Check' },
            { key: 'statusVerify', name: 'Verify' },
            { key: 'statusCompleted', name: 'Completed' },
            { key: 'statusOther', name: 'Other' }
        ]);
        donut('chartPpmDonut', [
            { name: 'Open', y: kpis.statusOpen },
            { name: 'In Progress', y: kpis.statusInProgress },
            { name: 'Check', y: kpis.statusCheck },
            { name: 'Verify', y: kpis.statusVerify },
            { name: 'Completed', y: kpis.statusCompleted },
            { name: 'Other', y: kpis.statusOther }
        ]);
        trendChart('chartPpmTrend', result.trend || [], [
            { key: 'scheduled', name: 'Scheduled' },
            { key: 'completed', name: 'Completed' }
        ]);
    }

    function renderAssets(result) {
        setText('kpiAssets', formatInt((result.kpis || {}).active));
        const rows = topRows(result.bySite || [], function (row) { return Number(row.active || 0); }, 12);
        groupedColumn('chartAssets', rows, function (row) { return row.siteName; }, [
            { key: 'active', name: 'Assets' }
        ], 'No assets on active contracts');
    }

    function renderPtw(result) {
        const kpis = result.kpis || {};
        setText('kpiPtwActive', formatInt(kpis.active));
        setText('kpiPtwSoon', formatInt(kpis.expiring7d));
        donut('chartPtwDonut', [
            { name: 'Active', y: kpis.active },
            { name: 'Pending', y: kpis.pendingApproval },
            { name: 'Approved', y: kpis.approved },
            { name: 'Draft', y: kpis.draft },
            { name: 'Completed', y: kpis.completed },
            { name: 'Expired', y: kpis.expired },
            { name: 'Cancelled', y: kpis.cancelled },
            { name: 'Other', y: kpis.other }
        ], 'No permits for the selected sites');
        const rows = topRows(result.bySite || [], function (row) {
            return Number(row.active || 0) + Number(row.pendingApproval || 0);
        }, 12).filter(function (row) {
            return Number(row.active || 0) + Number(row.pendingApproval || 0) > 0;
        });
        stackedColumn('chartPtwSites', rows, function (row) { return row.siteName; }, [
            { key: 'active', name: 'Active' },
            { key: 'pendingApproval', name: 'Pending' }
        ], 'No active or pending permits');
    }

    function renderWaste(result) {
        const kpis = result.kpis || {};
        setText('kpiWasteGen', formatKg(kpis.generatedKg));
        setText('kpiWasteDis', formatKg(kpis.disposedKg));
        setText('kpiWastePend', formatKg(kpis.pendingKg));
        trendChart('chartWasteTrend', result.trend || [], [
            { key: 'generatedKg', name: 'Generated' },
            { key: 'disposedKg', name: 'Disposed' }
        ], 'No waste movement in this period');
        const rows = topRows(result.bySite || [], function (row) { return Number(row.generatedKg || 0); }, 12)
            .filter(function (row) { return Number(row.generatedKg || 0) > 0 || Number(row.disposedKg || 0) > 0; });
        groupedColumn('chartWasteSites', rows, function (row) { return row.siteName; }, [
            { key: 'generatedKg', name: 'Generated' },
            { key: 'disposedKg', name: 'Disposed' }
        ], 'No waste movement in this period');
    }

    function renderLicenses(result) {
        const kpis = result.kpis || {};
        setText('kpiLicExp', formatInt(kpis.expired));
        setText('kpiLic30', formatInt(kpis.expiring30));
        const rows = topRows(result.bySite || [], function (row) {
            return Number(row.expired || 0) + Number(row.expiring30 || 0);
        }, 8).filter(function (row) {
            return Number(row.expired || 0) + Number(row.expiring30 || 0) > 0;
        });
        groupedColumn('chartLicense', rows, function (row) { return row.siteName; }, [
            { key: 'expired', name: 'Expired' },
            { key: 'expiring30', name: 'Within 30 days' }
        ], 'No licenses are expired or due within 30 days');
        const items = result.items || [];
        if (!items.length) {
            $('#licItems').html('<div class="list-group-item text-secondary">No licenses expire within 90 days.</div>');
            return;
        }
        $('#licItems').html(items.map(function (item) {
            const days = Number(item.daysRemaining || 0);
            const when = days < 0 ? (Math.abs(days) + ' days overdue') : (days === 0 ? 'Due today' : days + ' days left');
            const kind = days < 0 ? 'danger' : (days <= 30 ? 'warning' : 'info');
            return '<a class="list-group-item list-group-item-action" href="p_license">' +
                '<div class="d-flex justify-content-between gap-2"><strong>' + esc(item.title) + '</strong>' +
                '<span class="badge bg-' + kind + '-lt">' + esc(when) + '</span></div>' +
                '<div class="text-secondary small">' + esc(item.siteName) + ' · ' + esc(formatDate(item.endDate)) + '</div></a>';
        }).join(''));
    }

    function indexRows(payload) {
        const map = {};
        if (!payload || payload.available === false) {
            return map;
        }
        (payload.bySite || []).forEach(function (row) {
            map[row.siteId] = row;
        });
        return map;
    }

    function renderAttention() {
        if (!state.modules.workOrder && !state.modules.ppm) {
            endOverlay('chartAttention');
            return;
        }
        beginCharts(['chartAttention']);
        const wo = indexRows(state.data.wo);
        const ppm = indexRows(state.data.ppm);
        const seen = {};
        Object.keys(wo).concat(Object.keys(ppm)).forEach(function (id) { seen[id] = true; });
        const rows = Object.keys(seen).map(function (id) {
            const work = wo[id] || {};
            const task = ppm[id] || {};
            return {
                name: work.siteName || task.siteName || ('Site ' + id),
                open: Number(work.open || 0),
                late: Number(task.late || 0)
            };
        }).filter(function (row) {
            return row.open + row.late > 0;
        }).sort(function (a, b) {
            return (b.open + b.late) - (a.open + a.late);
        }).slice(0, 8);
        horizontal('chartAttention', rows.map(function (row) { return row.name; }), [
            { name: 'Open work orders', data: rows.map(function (row) { return row.open; }) },
            { name: 'Late PPM', data: rows.map(function (row) { return row.late; }) }
        ], 'No open work orders or late PPM in this view');
    }

    function renderScore() {
        const columns = [{ title: 'Site', data: 'site', render: function (site) {
            return '<a href="home.html">' + esc(site.siteName || '') + '</a>' +
                (site.siteCode ? '<div class="text-secondary small">' + esc(site.siteCode) + '</div>' : '');
        }}];
        if (state.modules.assets) {
            columns.push({ title: 'Assets', data: 'assets', className: 'text-end', render: function (v) { return v == null ? '—' : formatInt(v); } });
        }
        if (state.modules.workOrder) {
            columns.push({ title: 'WO open', data: 'woOpen', className: 'text-end', render: function (v) { return v == null ? '—' : formatInt(v); } });
            columns.push({ title: 'WO done', data: 'woDone', className: 'text-end', render: function (v) { return v == null ? '—' : formatInt(v); } });
        }
        if (state.modules.ppm) {
            columns.push({ title: 'PPM', data: 'ppm', className: 'text-end', render: function (v) { return v == null ? '—' : formatInt(v); } });
            columns.push({ title: 'PPM late', data: 'ppmLate', className: 'text-end', render: function (v) { return v == null ? '—' : formatInt(v); } });
            columns.push({ title: 'PPM done', data: 'ppmDone', className: 'text-end', render: function (v) { return v == null ? '—' : formatPct(v); } });
        }
        if (state.modules.ptw) {
            columns.push({ title: 'PTW active', data: 'ptw', className: 'text-end', render: function (v) { return v == null ? '—' : formatInt(v); } });
            columns.push({ title: 'PTW expiring', data: 'ptwSoon', className: 'text-end', render: function (v) { return v == null ? '—' : formatInt(v); } });
        }
        if (state.modules.waste) {
            columns.push({ title: 'Waste pending kg', data: 'waste', className: 'text-end', render: function (v) { return v == null ? '—' : formatKg(v); } });
        }
        if (state.modules.license) {
            columns.push({ title: 'Licenses expired', data: 'license', className: 'text-end', render: function (v) { return v == null ? '—' : formatInt(v); } });
        }

        const maps = {
            wo: indexRows(state.data.wo),
            ppm: indexRows(state.data.ppm),
            assets: indexRows(state.data.assets),
            ptw: indexRows(state.data.ptw),
            waste: indexRows(state.data.waste),
            licenses: indexRows(state.data.licenses)
        };
        const source = ['wo', 'ppm', 'assets', 'ptw', 'waste', 'licenses'].map(function (key) {
            return state.data[key];
        }).find(function (payload) {
            return payload && payload.available !== false && payload.bySite && payload.bySite.length;
        });
        const value = function (row, field) {
            return row && Object.prototype.hasOwnProperty.call(row, field) ? row[field] : null;
        };
        const data = ((source && source.bySite) || []).map(function (site) {
            const id = site.siteId;
            return {
                site: site,
                assets: value(maps.assets[id], 'active'),
                woOpen: value(maps.wo[id], 'open'),
                woDone: value(maps.wo[id], 'completed'),
                ppm: value(maps.ppm[id], 'scheduled'),
                ppmLate: value(maps.ppm[id], 'late'),
                ppmDone: value(maps.ppm[id], 'percDone'),
                ptw: value(maps.ptw[id], 'active'),
                ptwSoon: value(maps.ptw[id], 'expiring7d'),
                waste: value(maps.waste[id], 'pendingKg'),
                license: value(maps.licenses[id], 'expired')
            };
        });
        const orderIndex = columns.findIndex(function (column) { return column.data === 'woOpen'; });
        if ($.fn.dataTable.isDataTable('#dtMgdScore')) {
            $('#dtMgdScore').DataTable().clear().destroy();
        }
        $('#dtMgdScore').html('<thead><tr>' + columns.map(function (column) {
            return '<th>' + esc(column.title) + '</th>';
        }).join('') + '</tr></thead><tbody></tbody>');
        $('#dtMgdScore').DataTable({
            data: data,
            columns: columns,
            order: [[orderIndex > 0 ? orderIndex : 0, 'desc']],
            pageLength: 10,
            autoWidth: false,
            dom: window.GemsUI ? GemsUI.dtDom : "<'table-responsive't><'card-footer d-flex align-items-center py-2'i<'ms-auto'p>>",
            language: window.GemsUI ? GemsUI.dtEmpty('fa-building', 'No sites in this selection.', 'No sites match the current search.') : {}
        });
        const term = $('#txtMgdScoreSearch').val();
        if (term) {
            $('#dtMgdScore').DataTable().search(term).draw();
        }
    }

    function describe(result) {
        const filters = result && result.filters;
        if (!filters) {
            return;
        }
        const count = (filters.siteIds || []).length;
        setText('kpiSites', formatInt(count));
        setText('lblMgdScope', count + (count === 1 ? ' site · ' : ' sites · ') + formatDate(filters.dateFrom) + ' – ' + formatDate(filters.dateTo));
        const stamp = document.querySelector('#lblMgdUpdated span');
        if (stamp) {
            stamp.textContent = new Date().toLocaleString();
        }
    }

    function loadKey(key, requestId) {
        if (!state.modules[moduleKey[key]]) {
            state.data[key] = null;
            return Promise.resolve();
        }
        beginCharts(charts[key]);
        clearAlert(key);
        const path = {
            wo: 'dashboard/wo',
            ppm: 'dashboard/ppm',
            assets: 'dashboard/assets',
            ptw: 'dashboard/ptw',
            waste: 'dashboard/waste',
            licenses: 'dashboard/licenses'
        }[key];
        return dashFetch(path + '?' + queryString()).then(function (result) {
            if (requestId !== state.requestId) {
                return;
            }
            if (!result || result.available === false) {
                state.data[key] = null;
                $('[data-module="' + moduleKey[key] + '"]').addClass('d-none');
                return;
            }
            state.data[key] = result;
            const renderers = {
                wo: renderWo,
                ppm: renderPpm,
                assets: renderAssets,
                ptw: renderPtw,
                waste: renderWaste,
                licenses: renderLicenses
            };
            renderers[key](result);
            describe(result);
        }).catch(function (error) {
            if (requestId !== state.requestId || /expired token/i.test(error.message || '')) {
                return;
            }
            state.data[key] = null;
            clearKpis(key);
            failCharts(charts[key], error.message);
            showAlert(key, error.message);
        });
    }

    function loadAll() {
        const from = $('#txtMgdDateFrom').val();
        const to = $('#txtMgdDateTo').val();
        if (from && to && from > to) {
            toastr.error('Date From must be on or before Date To.');
            return;
        }
        const requestId = ++state.requestId;
        $('#btnMgdApply, #btnMgdRefresh').prop('disabled', true);
        $('#alertPage').addClass('d-none').empty();
        const jobs = ['wo', 'ppm', 'assets', 'ptw', 'waste', 'licenses'].map(function (key) {
            return loadKey(key, requestId);
        });
        Promise.all(jobs).then(function () {
            if (requestId !== state.requestId) {
                return;
            }
            try {
                renderAttention();
                renderScore();
            } catch (error) {
                $('#alertPage').removeClass('d-none').text(error.message || 'Unable to draw the scorecard.');
            }
            $('#btnMgdApply, #btnMgdRefresh').prop('disabled', false);
            if (typeof HideLoader === 'function') {
                HideLoader();
            }
            setTimeout(reflowCharts, 50);
        });
    }

    function applyModules() {
        Object.keys(moduleKey).forEach(function (key) {
            const name = moduleKey[key];
            $('[data-module="' + name + '"]').toggleClass('d-none', !state.modules[name]);
        });
        const showAttention = !!(state.modules.workOrder || state.modules.ppm);
        $('#colAttention').toggleClass('d-none', !showAttention);
        $('#secAttention').toggleClass('d-none', !showAttention && !state.modules.assets);
    }

    function fillFilters(me) {
        state.sites = me.sites || [];
        state.clients = me.clients || [];
        state.modules = me.modules || {};
        state.defaultPeriod = me.defaultPeriod || { from: '', to: '' };
        const clientHtml = ['<option value="">All clients</option>'].concat(state.clients.map(function (client) {
            return '<option value="' + client.clientId + '">' + esc(client.clientName) + '</option>';
        })).join('');
        $('#selMgdClient').html(clientHtml);
        const accountClientId = String(me.clientId || '');
        if (accountClientId && state.clients.some(function (client) {
            return String(client.clientId) === accountClientId;
        })) {
            $('#selMgdClient').val(accountClientId);
        }
        $('#txtMgdDateFrom').val(state.defaultPeriod.from || '');
        $('#txtMgdDateTo').val(state.defaultPeriod.to || '');
        fillSites(true);
        applyModules();
    }

    function selectedSiteIds() {
        return $('#listMgdSites .chk-mgd-site:checked').map(function () {
            return this.value;
        }).get();
    }

    function updateSiteSummary() {
        const options = $('#listMgdSites .chk-mgd-site');
        const selected = options.filter(':checked');
        const all = options.length > 0 && selected.length === options.length;
        $('#chkMgdSitesAll').prop('checked', all);
        $('#chkMgdSitesAll').prop('indeterminate', selected.length > 0 && !all);
        let label = 'All sites';
        if (selected.length === 1 && !all) {
            label = $.trim(selected.closest('label').find('span').text());
        } else if (selected.length > 1 && !all) {
            label = selected.length + ' sites';
        }
        $('#btnMgdSites').text(label);
    }

    function fillSites(selectAll) {
        const clientId = String($('#selMgdClient').val() || '');
        const previous = selectedSiteIds();
        const options = state.sites.filter(function (site) {
            return !clientId || String(site.clientId) === clientId;
        });
        const chosen = {};
        if (selectAll) {
            options.forEach(function (site) { chosen[String(site.siteId)] = true; });
        } else {
            previous.forEach(function (id) { chosen[String(id)] = true; });
        }
        $('#txtMgdSiteSearch').val('');
        $('#listMgdSites').html(options.map(function (site) {
            const id = String(site.siteId);
            const mark = chosen[id] ? ' checked' : '';
            return '<label class="dropdown-item d-flex align-items-center gap-2 rounded px-2 mgd-site-option">' +
                '<input class="form-check-input m-0 chk-mgd-site" type="checkbox" value="' + id + '"' + mark + '>' +
                '<span>' + esc(site.siteName) + '</span></label>';
        }).join('') || '<div class="text-secondary small px-2 py-1">No sites for this client.</div>');
        updateSiteSummary();
    }

    function loadFragment(id) {
        return new Promise(function (resolve) {
            const node = document.getElementById(id);
            if (!node) {
                resolve();
                return;
            }
            $('#' + id).load('html/' + id.substr(2) + '.html?' + new Date().valueOf(), function () {
                resolve();
            });
        });
    }

    this.init = function () {
        Promise.all([
            loadFragment('h-nav_left_tabler'),
            loadFragment('h-nav_top_tabler'),
            loadFragment('h-modal_change_password')
        ]).then(function () {
            initiatePages();
            window.changePasswordClass_ = new ModalChangePassword();
            return dashFetch('dashboard/me');
        }).then(function (me) {
            if (!me || !me.isManagement) {
                if (typeof HideLoader === 'function') {
                    HideLoader();
                }
                toastr.info('The management dashboard is available to administrators and GFM management.');
                window.setTimeout(function () {
                    window.location.href = 'home.html';
                }, 700);
                return;
            }
            fillFilters(me);
            loadAll();
        }).catch(function (error) {
            if (/expired token/i.test(error.message || '')) {
                return;
            }
            $('#alertPage').removeClass('d-none').text(error.message || 'Unable to open the dashboard.');
            if (typeof HideLoader === 'function') {
                HideLoader();
            }
        });

        $('#selMgdClient').on('change', function () {
            fillSites(true);
        });
        $('#menuMgdSites').on('change', '#chkMgdSitesAll', function () {
            $('#listMgdSites .chk-mgd-site').prop('checked', true);
            updateSiteSummary();
        });
        $('#menuMgdSites').on('change', '.chk-mgd-site', function () {
            if (selectedSiteIds().length === 0) {
                this.checked = true;
            }
            updateSiteSummary();
        });
        $('#txtMgdSiteSearch').on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
            }
        });
        $('#txtMgdSiteSearch').on('input', function () {
            const query = $.trim(this.value).toLowerCase();
            $('#listMgdSites .mgd-site-option').each(function () {
                const name = $.trim($(this).text()).toLowerCase();
                $(this).toggleClass('d-none', query !== '' && name.indexOf(query) === -1);
            });
        });
        $('#formMgdFilters').on('submit', function (event) {
            event.preventDefault();
            loadAll();
        });
        $('#btnMgdRefresh').on('click', loadAll);
        $('#txtMgdScoreSearch').on('input', function () {
            if ($.fn.dataTable.isDataTable('#dtMgdScore')) {
                $('#dtMgdScore').DataTable().search(this.value).draw();
            }
        });
        $(document).on('click', '[data-retry]', function () {
            const key = $(this).attr('data-retry');
            const requestId = state.requestId;
            loadKey(key, requestId).then(function () {
                if (requestId !== state.requestId) {
                    return;
                }
                renderAttention();
                renderScore();
            });
        });
        window.addEventListener('resize', function () {
            clearTimeout(self.resizeTimer);
            self.resizeTimer = setTimeout(reflowCharts, 150);
        });
        document.addEventListener('click', function (event) {
            if (event.target.closest('.navbar-toggler, [data-bs-toggle="collapse"]')) {
                setTimeout(reflowCharts, 350);
            }
        });
    };
}

document.addEventListener('DOMContentLoaded', function () {
    if (typeof ShowLoader === 'function') {
        ShowLoader();
    }
    try {
        new MainManagementDashboard().init();
    } catch (error) {
        if (window.toastr) {
            toastr.error(error.message || 'Unable to open the dashboard.');
        }
        if (typeof HideLoader === 'function') {
            HideLoader();
        }
    }
});
