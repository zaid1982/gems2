function MainEnergyDaily() {
    const ec = new EnergyCommon();
    let data = null;
    const enqueue = ec.serialQueue();
    let saveGen = 0;
    let savedHideTimer = null;

    const siteId = function () { return $('#optEdySite').val() || ''; };
    const year = function () { return $('#optEdyYear').val() || new Date().getFullYear(); };
    const month = function () { return $('#optEdyMonth').val() || (new Date().getMonth() + 1); };

    const renderHead = function () {
        const meters = data.meters || [];
        let top = '<tr><th rowspan="2">Date</th>';
        let sub = '<tr class="enr-grid-sub">';
        meters.forEach(function (m) {
            top += '<th colspan="3" class="text-center enr-meter-head">' + ec.escape(m.meterName) + '</th>';
            sub += '<th class="enr-meter-head">Cumulative (kWh)</th><th>Consumption (kWh)</th><th>MD (kW)</th>';
        });
        top += '<th rowspan="2">Total kWh</th><th rowspan="2">Chiller hrs</th><th rowspan="2">Remark</th></tr>';
        sub += '</tr>';
        $('#theadEdy').html(top + sub);
    };

    const renderBody = function () {
        const editable = !!(data.canRecord && ec.caps.canRecord);
        const html = (data.rows || []).map(function (row) {
            const weekend = row.dayName === 'Sat' || row.dayName === 'Sun';
            let tds = '<td><strong>' + row.day + '</strong> <span class="text-muted">' + row.dayName + '</span></td>';
            row.meters.forEach(function (cell) {
                const cumulative = cell.cumulativeKwh === null ? '' : cell.cumulativeKwh;
                const md = cell.maxDemandKw === null ? '' : cell.maxDemandKw;
                tds += '<td class="enr-meter-head">' +
                    (editable
                        ? '<input type="number" step="0.01" min="0" class="form-control edyCumulative" data-meter="' + cell.meterId + '" data-date="' + row.date + '" data-reading="' + (cell.readingId || '') + '" value="' + cumulative + '">'
                        : ec.fmtKwh(cell.cumulativeKwh)) +
                    '</td>';
                tds += '<td class="enr-calc edyCons" data-meter="' + cell.meterId + '" data-date="' + row.date + '">' + ec.fmtKwh(cell.consumptionKwh) + '</td>';
                tds += '<td>' +
                    (editable
                        ? '<input type="number" step="0.01" min="0" class="form-control edyMd" data-meter="' + cell.meterId + '" data-date="' + row.date + '" value="' + md + '">'
                        : ec.fmtKwh(cell.maxDemandKw)) +
                    '</td>';
            });
            tds += '<td class="enr-calc edyRowTotal" data-date="' + row.date + '"><strong>' + ec.fmtKwh(row.totalKwh) + '</strong></td>';
            tds += '<td>' +
                (editable
                    ? '<input type="number" step="0.01" min="0" class="form-control edyChiller" data-date="' + row.date + '" value="' + (row.chillerRunningHours === null ? '' : row.chillerRunningHours) + '">'
                    : ec.fmtKwh(row.chillerRunningHours)) +
                '</td>';
            tds += '<td>' +
                (editable
                    ? '<input type="text" class="form-control enr-remark edyRemark" data-date="' + row.date + '" value="' + ec.escape(row.remark || '') + '">'
                    : ec.escape(row.remark || '')) +
                '</td>';
            return '<tr class="' + (weekend ? 'is-weekend' : '') + '">' + tds + '</tr>';
        }).join('');
        $('#tbodyEdy').html(html);
    };

    const renderFoot = function () {
        const meters = data.meters || [];
        const totals = {};
        (data.meterTotals || []).forEach(function (t) { totals[t.meterId] = t.totalKwh; });
        let tds = '<td>Monthly total</td>';
        meters.forEach(function (m) {
            tds += '<td class="enr-meter-head"></td>';
            tds += '<td class="enr-calc">' + ec.fmtKwh(totals[m.meterId]) + '</td>';
            tds += '<td></td>';
        });
        tds += '<td class="enr-calc">' + ec.fmtKwh(data.totalKwh) + '</td><td colspan="2"></td>';
        let avg = '<td>Average daily</td>';
        meters.forEach(function () { avg += '<td colspan="3"></td>'; });
        avg += '<td class="enr-calc">' + ec.fmtKwh(data.averageDaily) + '</td>' +
            '<td colspan="2" class="text-muted">' + data.daysWithData + ' of ' + data.daysInMonth + ' days have readings</td>';
        $('#tfootEdy').html('<tr>' + tds + '</tr><tr>' + avg + '</tr>');
    };

    const renderIssues = function () {
        const issues = data.issues || [];
        if (!issues.length) {
            $('#divEdyIssues').addClass('d-none');
            return;
        }
        $('#lblEdyIssues').html(issues.map(function (i) {
            return '<div>' + ec.escape((i.meterName ? i.meterName + ': ' : '') + i.message) + '</div>';
        }).join(''));
        $('#divEdyIssues').removeClass('d-none');
    };

    const render = function () {
        data = ec.apiGet('daily?siteId=' + siteId() + '&year=' + year() + '&month=' + month());
        if (!data) { return; }
        renderHead();
        renderBody();
        renderFoot();
        renderIssues();
        $('#mEdyTotal').text(ec.fmtNumber(data.totalKwh, 2));
        $('#mEdyAvg').text(ec.fmtNumber(data.averageDaily, 2));
        $('#mEdyDays').text(data.daysWithData + ' / ' + data.daysInMonth);
        $('#mEdyMeters').text((data.meters || []).length);
        $('#lblEdyPeriod').text(data.periodLabel + ' · ' + (data.siteName || ''));
        $('#lblEdyUpdated').text('Last refreshed: ' + (data.refreshedAt || '-'));
        if (!(data.meters || []).length) {
            $('#theadEdy').html('');
            $('#tfootEdy').html('');
            $('#tbodyEdy').html('<tr><td class="gems-empty-cell">' + ec.emptyState('fa-gauge', 'No incoming meters are configured for this site. Use Meter setup to add one.') + '</td></tr>');
        }
    };

    const applyMetrics = function () {
        $('#mEdyTotal').text(ec.fmtNumber(data.totalKwh, 2));
        $('#mEdyAvg').text(ec.fmtNumber(data.averageDaily, 2));
        $('#mEdyDays').text(data.daysWithData + ' / ' + data.daysInMonth);
        $('#lblEdyUpdated').text('Last refreshed: ' + (data.refreshedAt || '-'));
    };

    const patchGrid = function (next) {
        data = next;
        (data.rows || []).forEach(function (row) {
            (row.meters || []).forEach(function (cell) {
                $('.edyCons[data-meter="' + cell.meterId + '"][data-date="' + row.date + '"]').html(ec.fmtKwh(cell.consumptionKwh));
                $('.edyCumulative[data-meter="' + cell.meterId + '"][data-date="' + row.date + '"]').attr('data-reading', cell.readingId || '');
            });
            $('.edyRowTotal[data-date="' + row.date + '"]').html('<strong>' + ec.fmtKwh(row.totalKwh) + '</strong>');
        });
        renderFoot();
        renderIssues();
        applyMetrics();
    };

    const showSaved = function () {
        ec.setSaveState('lblEdySaveState', 'saved');
        clearTimeout(savedHideTimer);
        savedHideTimer = setTimeout(function () { ec.setSaveState('lblEdySaveState', 'idle'); }, 1600);
    };

    const runSave = function (work) {
        const gen = ++saveGen;
        ec.setSaveState('lblEdySaveState', 'saving');
        return enqueue(function () {
            return work().then(function (next) {
                if (gen === saveGen) { patchGrid(next); showSaved(); }
                return next;
            }).catch(function (e) {
                if (gen === saveGen) {
                    ec.setSaveState('lblEdySaveState', 'error', e.message);
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                throw e;
            });
        });
    };

    const saveReading = function (meterId, date, cumulative, maxDemand) {
        return runSave(function () {
            return ec.apiAsync('reading', 'POST', {
                meterId: meterId,
                readingDate: date,
                cumulativeKwh: cumulative,
                maxDemandKw: maxDemand,
                autosave: 1
            });
        });
    };

    const cellValues = function (meterId, date) {
        return {
            cumulative: $('.edyCumulative[data-meter="' + meterId + '"][data-date="' + date + '"]').val(),
            md: $('.edyMd[data-meter="' + meterId + '"][data-date="' + date + '"]').val()
        };
    };

    const findReadingId = function (meterId, date) {
        const row = (data.rows || []).find(function (r) { return r.date === date; });
        if (!row) { return null; }
        const cell = row.meters.find(function (c) { return String(c.meterId) === String(meterId); });
        return cell ? cell.readingId : null;
    };

    // ------------------------------------------------------------ meter setup

    const renderMeters = function (rows) {
        const body = $('#tblEmtList tbody');
        if (window.GemsUI) { GemsUI.disposeTooltips(body[0]); }
        if (!rows || !rows.length) {
            body.html('<tr><td colspan="5" class="gems-empty-cell">' + ec.emptyState('fa-gauge', 'No meters yet') + '</td></tr>');
            return;
        }
        body.html(rows.map(function (r) {
            const active = Number(r.meterStatus) === 1;
            return '<tr>' +
                '<td><strong>' + ec.escape(r.meterName) + '</strong></td>' +
                '<td>' + ec.escape(r.meterDesc || '') + '</td>' +
                '<td>' + r.sortOrder + '</td>' +
                '<td>' + ec.activeBadge(r.meterStatus) + '</td>' +
                '<td class="text-nowrap">' +
                ec.actionBtn({ tint: 'gems-btn-action-edit', cls: 'lnkEmtEdit', title: 'Edit', icon: 'fas fa-pen-to-square', extra: 'data-id="' + r.meterId + '"' }) +
                (active ? ec.actionBtn({ tint: 'gems-btn-action-delete', cls: 'lnkEmtDel', title: 'Deactivate', icon: 'fas fa-ban', extra: 'data-id="' + r.meterId + '"' }) : '') +
                '</td></tr>';
        }).join(''));
        body.data('rows', rows);
        ec.initTooltips(body[0]);
    };

    const openMeters = function () {
        renderMeters(ec.apiGet('meter?siteId=' + siteId()));
        $('#hidEmtId').val('');
        $('#txtEmtName').val('');
        $('#txtEmtDesc').val('');
        $('#txtEmtOrder').val(((data.meters || []).length) + 1);
        $('#modal_energy_meter').modal({ backdrop: 'static' });
    };

    this.init = function () {
        ec.loadCaps();
        ec.loadSites();
        ec.fillSelect('optEdySite', ec.sites, 'siteId', function (r) { return r.siteName; }, null, ec.caps.siteId || '');
        ec.fillMonths('optEdyMonth', new Date().getMonth() + 1);
        ec.fillYears('optEdyYear', new Date().getFullYear(), 5, 1);
        $('#btnEdyMeters').toggle(!!ec.caps.canSetup);

        render();

        $('#optEdySite, #optEdyMonth, #optEdyYear').off('change').on('change', render);
        $('#btnEdyRefresh').off('click').on('click', render);
        $('#btnEdyExport').off('click').on('click', function () {
            ec.exportTable('tblEdy', 'GEMS Daily Electricity ' + (data ? data.periodLabel : ''));
        });
        $('#btnEdyMeters').off('click').on('click', openMeters);

        const onCumulative = ec.debounce(function (input) {
            const meterId = $(input).data('meter');
            const date = $(input).data('date');
            const value = $(input).val();
            if (value === '') {
                const readingId = findReadingId(meterId, date) || $(input).attr('data-reading');
                if (!readingId) { return; }
                runSave(function () { return ec.apiAsync('reading/' + readingId, 'DELETE', { autosave: 1 }); });
                return;
            }
            saveReading(meterId, date, value, cellValues(meterId, date).md);
        }, 400);

        $(document).off('change', '.edyCumulative').on('change', '.edyCumulative', function () {
            onCumulative(this);
        });

        const onMd = ec.debounce(function (input) {
            const meterId = $(input).data('meter');
            const date = $(input).data('date');
            const values = cellValues(meterId, date);
            if (values.cumulative === '') {
                toastr['warning']('Enter the cumulative reading for this day before the maximum demand.', _ALERT_TITLE_WARNING);
                return;
            }
            saveReading(meterId, date, values.cumulative, values.md);
        }, 400);
        $(document).off('change', '.edyMd').on('change', '.edyMd', function () { onMd(this); });

        const saveNote = function (date) {
            return runSave(function () {
                return ec.apiAsync('daily_note', 'POST', {
                    siteId: siteId(),
                    date: date,
                    chillerRunningHours: $('.edyChiller[data-date="' + date + '"]').val(),
                    remark: $('.edyRemark[data-date="' + date + '"]').val(),
                    autosave: 1
                });
            });
        };
        const onNote = ec.debounce(function (date) { saveNote(date); }, 400);
        $(document).off('change', '.edyChiller').on('change', '.edyChiller', function () { onNote($(this).data('date')); });
        $(document).off('change', '.edyRemark').on('change', '.edyRemark', function () { onNote($(this).data('date')); });

        $(document).off('click', '#btnEmtSave').on('click', '#btnEmtSave', function () {
            const id = $('#hidEmtId').val();
            const body = {
                siteId: siteId(),
                meterName: $('#txtEmtName').val(),
                meterDesc: $('#txtEmtDesc').val(),
                sortOrder: $('#txtEmtOrder').val()
            };
            if (!body.meterName) { toastr['warning']('Enter the meter name.', _ALERT_TITLE_WARNING); return; }
            ShowLoader();
            try {
                ec.api(id ? ('meter/' + id) : 'meter', id ? 'PUT' : 'POST', body);
                renderMeters(ec.apiGet('meter?siteId=' + siteId()));
                $('#hidEmtId').val('');
                $('#txtEmtName').val('');
                $('#txtEmtDesc').val('');
                render();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
        $(document).off('click', '.lnkEmtEdit').on('click', '.lnkEmtEdit', function (e) {
            e.preventDefault();
            const id = $(this).data('id');
            const rows = $('#tblEmtList tbody').data('rows') || [];
            const row = rows.find(function (r) { return String(r.meterId) === String(id); });
            if (!row) { return; }
            $('#hidEmtId').val(row.meterId);
            $('#txtEmtName').val(row.meterName);
            $('#txtEmtDesc').val(row.meterDesc || '');
            $('#txtEmtOrder').val(row.sortOrder);
        });
        $(document).off('click', '.lnkEmtDel').on('click', '.lnkEmtDel', function (e) {
            e.preventDefault();
            if (!window.confirm('Deactivate this meter? Existing readings are kept.')) { return; }
            ShowLoader();
            try {
                ec.api('meter/' + $(this).data('id'), 'DELETE');
                renderMeters(ec.apiGet('meter?siteId=' + siteId()));
                render();
            } catch (err) { toastr['error'](err.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
    };
}
