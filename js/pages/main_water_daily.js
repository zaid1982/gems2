function MainWaterDaily() {
    const wc = new WaterCommon();
    let data = null;
    const enqueue = wc.serialQueue();
    let saveGen = 0;
    let savedHideTimer = null;

    const siteId = function () { return $('#optWdySite').val() || ''; };
    const year = function () { return $('#optWdyYear').val() || new Date().getFullYear(); };
    const month = function () { return $('#optWdyMonth').val() || (new Date().getMonth() + 1); };

    const consHtml = function (cell) {
        let html = wc.fmtVolume(cell.consumptionVolume);
        if (cell.estimated) {
            html += ' <span class="text-secondary" title="Spread across a missed morning">est.</span>';
        }
        return html;
    };

    const renderHead = function () {
        const meters = data.meters || [];
        let top = '<tr><th rowspan="2">Date</th>';
        let sub = '<tr class="enr-grid-sub">';
        meters.forEach(function (m) {
            top += '<th colspan="5" class="text-center enr-meter-head">' + wc.escape(m.meterName) + '</th>';
            sub += '<th class="enr-meter-head">Cumulative (m&sup3;)</th><th>Consumption (m&sup3;)</th><th>IoT (m&sup3;)</th><th>Variance %</th><th>Remark</th>';
        });
        top += '<th rowspan="2">Total m&sup3;</th></tr>';
        sub += '</tr>';
        $('#theadWdy').html(top + sub);
    };

    const renderBody = function () {
        const editable = !!(data.canRecord && wc.caps.canRecord);
        const html = (data.rows || []).map(function (row) {
            const weekend = row.dayName === 'Sat' || row.dayName === 'Sun';
            let tds = '<td><strong>' + row.day + '</strong> <span class="text-muted">' + row.dayName + '</span></td>';
            row.meters.forEach(function (cell) {
                const cumulative = cell.cumulativeVolume === null ? '' : cell.cumulativeVolume;
                const iot = cell.iotVolume === null ? '' : cell.iotVolume;
                tds += '<td class="enr-meter-head">' +
                    (editable
                        ? '<input type="number" step="0.01" min="0" class="form-control wdyCumulative" data-meter="' + cell.meterId + '" data-date="' + row.date + '" data-reading="' + (cell.readingId || '') + '" value="' + cumulative + '">'
                        : wc.fmtVolume(cell.cumulativeVolume)) +
                    '</td>';
                tds += '<td class="enr-calc wdyCons" data-meter="' + cell.meterId + '" data-date="' + row.date + '">' + consHtml(cell) + '</td>';
                tds += '<td>' +
                    (editable
                        ? '<input type="number" step="0.01" min="0" class="form-control wdyIot" data-meter="' + cell.meterId + '" data-date="' + row.date + '" value="' + iot + '">'
                        : wc.fmtVolume(cell.iotVolume)) +
                    '</td>';
                tds += '<td class="enr-calc wdyVar" data-meter="' + cell.meterId + '" data-date="' + row.date + '">' + wc.fmtPct(cell.variancePct) + '</td>';
                tds += '<td>' +
                    (editable
                        ? '<input type="text" maxlength="300" class="form-control enr-remark wdyRemark" data-meter="' + cell.meterId + '" data-date="' + row.date + '" value="' + wc.escape(cell.remark || '') + '">'
                        : wc.escape(cell.remark || '')) +
                    '</td>';
            });
            tds += '<td class="enr-calc wdyRowTotal" data-date="' + row.date + '"><strong>' + wc.fmtVolume(row.totalVolume) + '</strong></td>';
            return '<tr class="' + (weekend ? 'is-weekend' : '') + '">' + tds + '</tr>';
        }).join('');
        $('#tbodyWdy').html(html);
    };

    const renderFoot = function () {
        const meters = data.meters || [];
        const totals = {};
        (data.meterTotals || []).forEach(function (t) { totals[t.meterId] = t.totalVolume; });
        let tds = '<td>Monthly total</td>';
        meters.forEach(function (m) {
            tds += '<td class="enr-meter-head"></td>';
            tds += '<td class="enr-calc">' + wc.fmtVolume(totals[m.meterId]) + '</td>';
            tds += '<td colspan="3"></td>';
        });
        tds += '<td class="enr-calc">' + wc.fmtVolume(data.totalVolume) + '</td>';
        let avg = '<td>Average daily</td>';
        meters.forEach(function () { avg += '<td colspan="5"></td>'; });
        avg += '<td class="enr-calc">' + wc.fmtVolume(data.averageDaily) + '</td>';
        $('#tfootWdy').html('<tr>' + tds + '</tr><tr>' + avg + '</tr>');
    };

    const renderIssues = function () {
        const issues = data.issues || [];
        if (!issues.length) {
            $('#divWdyIssues').addClass('d-none');
            return;
        }
        $('#lblWdyIssues').html(issues.map(function (i) {
            return '<div>' + wc.escape((i.meterName ? i.meterName + ': ' : '') + i.message) + '</div>';
        }).join(''));
        $('#divWdyIssues').removeClass('d-none');
    };

    const applyMetrics = function () {
        $('#mWdyTotal').text(wc.fmtNumber(data.totalVolume, 2));
        $('#mWdyAvg').text(wc.fmtNumber(data.averageDaily, 2));
        $('#mWdyDays').text(data.daysWithData + ' / ' + data.daysInMonth);
        $('#mWdyEstimated').text(String(data.estimatedDays));
        $('#lblWdyUpdated').text('Last refreshed: ' + (data.refreshedAt || '-'));
    };

    const render = function () {
        data = wc.apiGet('daily?siteId=' + siteId() + '&year=' + year() + '&month=' + month());
        if (!data) { return; }
        renderHead();
        renderBody();
        renderFoot();
        renderIssues();
        applyMetrics();
        $('#lblWdyPeriod').text(data.periodLabel + ' · ' + (data.siteName || ''));
        if (!(data.meters || []).length) {
            $('#theadWdy').html('');
            $('#tfootWdy').html('');
            $('#tbodyWdy').html('<tr><td class="gems-empty-cell">' + wc.emptyState('fa-gauge', 'No water meters are configured for this site. Use Meter setup to add one.') + '</td></tr>');
        }
    };

    const patchGrid = function (next) {
        data = next;
        (data.rows || []).forEach(function (row) {
            (row.meters || []).forEach(function (cell) {
                $('.wdyCons[data-meter="' + cell.meterId + '"][data-date="' + row.date + '"]').html(consHtml(cell));
                $('.wdyVar[data-meter="' + cell.meterId + '"][data-date="' + row.date + '"]').html(wc.fmtPct(cell.variancePct));
                $('.wdyCumulative[data-meter="' + cell.meterId + '"][data-date="' + row.date + '"]').attr('data-reading', cell.readingId || '');
            });
            $('.wdyRowTotal[data-date="' + row.date + '"]').html('<strong>' + wc.fmtVolume(row.totalVolume) + '</strong>');
        });
        renderFoot();
        renderIssues();
        applyMetrics();
    };

    const showSaved = function () {
        wc.setSaveState('lblWdySaveState', 'saved');
        clearTimeout(savedHideTimer);
        savedHideTimer = setTimeout(function () { wc.setSaveState('lblWdySaveState', 'idle'); }, 1600);
    };

    const runSave = function (work) {
        const gen = ++saveGen;
        wc.setSaveState('lblWdySaveState', 'saving');
        return enqueue(function () {
            return work().then(function (next) {
                if (gen === saveGen) { patchGrid(next); showSaved(); }
                return next;
            }).catch(function (e) {
                if (gen === saveGen) {
                    wc.setSaveState('lblWdySaveState', 'error', e.message);
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                throw e;
            });
        });
    };

    const fieldValues = function (meterId, date) {
        return {
            meterId: meterId,
            readingDate: date,
            cumulativeVolume: $('.wdyCumulative[data-meter="' + meterId + '"][data-date="' + date + '"]').val(),
            iotVolume: $('.wdyIot[data-meter="' + meterId + '"][data-date="' + date + '"]').val(),
            remark: $('.wdyRemark[data-meter="' + meterId + '"][data-date="' + date + '"]').val(),
            autosave: 1
        };
    };

    const findReadingId = function (meterId, date) {
        const row = (data.rows || []).find(function (r) { return r.date === date; });
        if (!row) { return null; }
        const cell = (row.meters || []).find(function (c) { return String(c.meterId) === String(meterId); });
        return cell ? cell.readingId : null;
    };

    const saveFields = function (meterId, date) {
        const body = fieldValues(meterId, date);
        const readingId = findReadingId(meterId, date) || $('.wdyCumulative[data-meter="' + meterId + '"][data-date="' + date + '"]').attr('data-reading');
        if (body.cumulativeVolume === '' && body.iotVolume === '' && body.remark === '' && !readingId) {
            return;
        }
        runSave(function () { return wc.apiAsync('reading', 'POST', body); });
    };

    const renderMeters = function (rows) {
        const body = $('#tblWmtList tbody');
        if (window.GemsUI) { GemsUI.disposeTooltips(body[0]); }
        if (!rows || !rows.length) {
            body.html('<tr><td colspan="5" class="gems-empty-cell">' + wc.emptyState('fa-gauge', 'No meters yet') + '</td></tr>');
            return;
        }
        body.html(rows.map(function (r) {
            const active = Number(r.meterStatus) === 1;
            return '<tr>' +
                '<td><strong>' + wc.escape(r.meterName) + '</strong></td>' +
                '<td>' + wc.escape(r.meterDesc || '') + '</td>' +
                '<td>' + r.sortOrder + '</td>' +
                '<td>' + wc.activeBadge(r.meterStatus) + '</td>' +
                '<td class="text-nowrap">' +
                wc.actionBtn({ tint: 'gems-btn-action-edit', cls: 'lnkWmtEdit', title: 'Edit', icon: 'fas fa-pen-to-square', extra: 'data-id="' + r.meterId + '"' }) +
                (active ? wc.actionBtn({ tint: 'gems-btn-action-delete', cls: 'lnkWmtDel', title: 'Deactivate', icon: 'fas fa-ban', extra: 'data-id="' + r.meterId + '"' }) : '') +
                '</td></tr>';
        }).join(''));
        body.data('rows', rows);
        wc.initTooltips(body[0]);
    };

    const openMeters = function () {
        renderMeters(wc.apiGet('meter?siteId=' + siteId()));
        $('#hidWmtId').val('');
        $('#txtWmtName').val('');
        $('#txtWmtDesc').val('');
        $('#txtWmtOrder').val(((data.meters || []).length) + 1);
        $('#modal_water_meter').modal({ backdrop: 'static' });
    };

    this.init = function () {
        wc.loadCaps();
        wc.loadSites();
        wc.fillSelect('optWdySite', wc.sites, 'siteId', function (r) { return r.siteName; }, null, wc.caps.siteId || '');
        wc.fillMonths('optWdyMonth', new Date().getMonth() + 1);
        wc.fillYears('optWdyYear', new Date().getFullYear(), 5, 1);
        $('#btnWdyMeters').toggle(!!wc.caps.canSetup);

        render();

        $('#optWdySite, #optWdyMonth, #optWdyYear').off('change').on('change', render);
        $('#btnWdyRefresh').off('click').on('click', render);
        $('#btnWdyExport').off('click').on('click', function () {
            wc.exportTable('tblWdy', 'GEMS Daily Water ' + (data ? data.periodLabel : ''));
        });
        $('#btnWdyMeters').off('click').on('click', openMeters);

        const onField = wc.debounce(function (input) {
            saveFields($(input).data('meter'), $(input).data('date'));
        }, 400);
        $(document).off('change', '.wdyCumulative').on('change', '.wdyCumulative', function () { onField(this); });
        $(document).off('change', '.wdyIot').on('change', '.wdyIot', function () { onField(this); });
        $(document).off('change', '.wdyRemark').on('change', '.wdyRemark', function () { onField(this); });

        $(document).off('click', '#btnWmtSave').on('click', '#btnWmtSave', function () {
            const id = $('#hidWmtId').val();
            const body = {
                siteId: siteId(),
                meterName: $('#txtWmtName').val(),
                meterDesc: $('#txtWmtDesc').val(),
                sortOrder: $('#txtWmtOrder').val()
            };
            if (!body.meterName) { toastr['warning']('Enter the meter name.', _ALERT_TITLE_WARNING); return; }
            ShowLoader();
            try {
                wc.api(id ? ('meter/' + id) : 'meter', id ? 'PUT' : 'POST', body);
                renderMeters(wc.apiGet('meter?siteId=' + siteId()));
                $('#hidWmtId').val('');
                $('#txtWmtName').val('');
                $('#txtWmtDesc').val('');
                render();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
        $(document).off('click', '.lnkWmtEdit').on('click', '.lnkWmtEdit', function (e) {
            e.preventDefault();
            const id = $(this).data('id');
            const rows = $('#tblWmtList tbody').data('rows') || [];
            const row = rows.find(function (r) { return String(r.meterId) === String(id); });
            if (!row) { return; }
            $('#hidWmtId').val(row.meterId);
            $('#txtWmtName').val(row.meterName);
            $('#txtWmtDesc').val(row.meterDesc || '');
            $('#txtWmtOrder').val(row.sortOrder);
        });
        $(document).off('click', '.lnkWmtDel').on('click', '.lnkWmtDel', function (e) {
            e.preventDefault();
            if (!window.confirm('Deactivate this meter? Existing readings are kept.')) { return; }
            ShowLoader();
            try {
                wc.api('meter/' + $(this).data('id'), 'DELETE');
                renderMeters(wc.apiGet('meter?siteId=' + siteId()));
                render();
            } catch (err) { toastr['error'](err.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
    };
}
