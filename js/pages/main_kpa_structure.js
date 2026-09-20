function MainKpaStructure() {
    const kc = new KpaCommon();
    let dtPi;
    let dtGroup;
    let groups = [];
    let piRows = [];

    const siteId = function () {
        return $('#optKstSite').val() || '';
    };

    const passRuleLabel = function (rule) {
        if (rule === 'LTE_TARGET') { return 'Actual &le; Target'; }
        if (rule === 'EQ_TARGET') { return 'Actual = Target'; }
        return 'Actual &ge; Target';
    };

    const calcLabel = function (row) {
        if (row.calcType === 'EXPRESSION') {
            return '<code>' + kc.escape(row.formulaExpr || '') + '</code>';
        }
        const map = {
            BACKLOG_AVG: 'Backlog bucket average',
            BEI: 'Building Energy Index',
            AVG_PARAMS: 'Average of parameters',
            DIRECT: 'Direct value'
        };
        return map[row.calcType] || row.calcType;
    };

    const loadConfig = function () {
        const cfg = kc.apiGet('config' + (siteId() ? ('?siteId=' + siteId()) : '')) || {};
        $('#txtKstMaxApd').val(cfg.maxApdPct !== undefined ? cfg.maxApdPct : 5);
        $('#txtKstSource').val(cfg.usesTemplate ? 'Shared KPI template' : 'Site-specific structure');
    };

    const loadGroups = function () {
        groups = kc.apiGet('group' + (siteId() ? ('?siteId=' + siteId()) : '')) || [];
        dtGroup.clear().rows.add(groups).draw();
    };

    const loadPi = function () {
        piRows = kc.apiGet('pi' + (siteId() ? ('?siteId=' + siteId()) : '')) || [];
        dtPi.clear().rows.add(piRows).draw();
        const total = piRows.reduce(function (s, r) {
            return Number(r.piStatus) === 1 ? s + Number(r.weightagePct || 0) : s;
        }, 0);
        $('#txtKstWeightage').val(kc.fmtNumber(total, 2) + ' %');
        const balanced = Math.abs(total - 100) < 0.01;
        $('#lblKstWeightWarn')
            .html(balanced
                ? '<i class="fas fa-check-circle text-success me-1"></i>Active weightage totals 100%.'
                : '<i class="fas fa-triangle-exclamation text-warning me-1"></i>Active weightage totals ' + kc.fmtNumber(total, 2) + '%. APD exposure will not add up to the maximum until this is 100%.');
    };

    const reload = function () {
        loadConfig();
        loadGroups();
        loadPi();
    };

    // ---------------------------------------------------------------- groups

    const openGroup = function (row) {
        $('#hidKgpId').val(row ? row.groupId : '');
        $('#h4KgpTitle').text(row ? 'Edit KPI Group' : 'New KPI Group');
        $('#txtKgpNo').val(row ? row.groupNo : '');
        $('#txtKgpName').val(row ? row.groupName : '');
        $('#txtKgpOrder').val(row ? row.sortOrder : (groups.length + 1));
        $('#optKgpStatus').val(row ? row.groupStatus : 1);
        $('#modal_kpa_group').modal({ backdrop: 'static' });
    };

    const saveGroup = function () {
        const id = $('#hidKgpId').val();
        const body = {
            siteId: siteId(),
            groupNo: $('#txtKgpNo').val(),
            groupName: $('#txtKgpName').val(),
            sortOrder: $('#txtKgpOrder').val(),
            groupStatus: $('#optKgpStatus').val()
        };
        if (!body.groupNo || !body.groupName) {
            toastr['warning']('Enter the group number and name.', _ALERT_TITLE_WARNING);
            return;
        }
        ShowLoader();
        try {
            kc.api(id ? ('group/' + id) : 'group', id ? 'PUT' : 'POST', body);
            $('#modal_kpa_group').modal('hide');
            reload();
        } catch (e) {
            toastr['error'](e.message, _ALERT_TITLE_ERROR);
        }
        HideLoader();
    };

    // -------------------------------------------------------------------- PI

    const activeGroups = function () {
        return groups.filter(function (g) { return Number(g.groupStatus) === 1; });
    };

    const toggleFormulaFields = function () {
        const isExpr = $('#optKpiCalc').val() === 'EXPRESSION';
        $('#divKpiFormula').toggle(isExpr);
        $('#divKpiTest').toggle(true);
        renderTestInputs();
    };

    /** Builds an input per parameter key referenced by the formula. */
    const renderTestInputs = function () {
        const box = $('#divKpiTestInputs').empty();
        let keys = [];
        if ($('#optKpiCalc').val() === 'EXPRESSION') {
            const matches = ($('#txtKpiFormula').val() || '').match(/p[0-9]+/gi) || [];
            keys = Array.from(new Set(matches.map(function (k) { return k.toLowerCase(); })));
        } else {
            const piId = $('#hidKpiId').val();
            const row = piRows.find(function (r) { return String(r.piId) === String(piId); });
            const count = row ? Number(row.paramCount || 0) : 0;
            for (let i = 1; i <= Math.max(count, 3); i++) { keys.push('p' + i); }
        }
        keys.sort();
        keys.forEach(function (key) {
            box.append(
                '<div class="gems-field gems-field-3">' +
                '<label class="form-label" for="txtKpiTest_' + key + '">' + key + '</label>' +
                '<input type="number" step="any" class="form-control kpiTestInput" data-key="' + key + '" id="txtKpiTest_' + key + '">' +
                '</div>'
            );
        });
    };

    const testFormula = function () {
        const piId = $('#hidKpiId').val();
        const params = {};
        $('.kpiTestInput').each(function () {
            params[$(this).data('key')] = $(this).val();
        });
        const body = {
            formulaExpr: $('#txtKpiFormula').val(),
            calcType: $('#optKpiCalc').val(),
            targetValue: $('#txtKpiTarget').val(),
            targetUnit: $('#optKpiUnit').val(),
            passRule: $('#optKpiPassRule').val(),
            params: params
        };
        ShowLoader();
        try {
            // A saved PI is needed as the anchor for the dry run.
            const anchor = piId || (piRows.length ? piRows[0].piId : 0);
            if (!anchor) {
                toastr['warning']('Save the indicator once before testing the formula.', _ALERT_TITLE_WARNING);
                HideLoader();
                return;
            }
            const res = kc.api('pi/' + anchor + '/test', 'POST', body);
            if (res && res.ok) {
                const verdict = res.isPass === null || res.isPass === undefined
                    ? ''
                    : (res.isPass ? ' — target met' : ' — target not met');
                $('#lblKpiTestResult').removeClass('text-danger').addClass('text-success')
                    .text('Result: ' + kc.fmtNumber(res.value, 4) + verdict);
            } else {
                $('#lblKpiTestResult').removeClass('text-success').addClass('text-danger')
                    .text((res && res.message) || 'The formula could not be calculated.');
            }
        } catch (e) {
            $('#lblKpiTestResult').removeClass('text-success').addClass('text-danger').text(e.message);
        }
        HideLoader();
    };

    const openPi = function (row) {
        kc.fillSelect('optKpiGroup', activeGroups(), 'groupId', function (g) {
            return g.groupNo + ' — ' + g.groupName;
        }, 'Select KPI group', row ? row.groupId : '');
        $('#hidKpiId').val(row ? row.piId : '');
        $('#h4KpiTitle').text(row ? ('Edit PI ' + row.piNo) : 'New Performance Indicator');
        $('#txtKpiNo').val(row ? row.piNo : '');
        $('#txtKpiName').val(row ? row.piName : '');
        $('#txtKpiTarget').val(row ? row.targetValue : 100);
        $('#optKpiUnit').val(row ? row.targetUnit : '%');
        $('#txtKpiDemerit').val(row ? row.demeritPoint : 1);
        $('#txtKpiWeight').val(row ? row.weightagePct : '');
        $('#optKpiPassRule').val(row ? row.passRule : 'GTE_TARGET');
        $('#optKpiCalc').val(row ? row.calcType : 'EXPRESSION');
        $('#optKpiSource').val(row ? row.sourceType : 'MANUAL');
        $('#txtKpiFormula').val(row ? (row.formulaExpr || '') : '');
        $('#txtKpiOrder').val(row ? row.sortOrder : 1);
        $('#optKpiStatus').val(row ? row.piStatus : 1);
        $('#txtKpiEffective').val(row ? (row.effectiveFrom || '') : '');
        $('#txaKpiDesc').val(row ? (row.piDescription || '') : '');
        $('#lblKpiTestResult').text('');
        toggleFormulaFields();
        $('#modal_kpa_pi').modal({ backdrop: 'static' });
    };

    const savePi = function () {
        const id = $('#hidKpiId').val();
        const body = {
            groupId: $('#optKpiGroup').val(),
            piNo: $('#txtKpiNo').val(),
            piName: $('#txtKpiName').val(),
            piDescription: $('#txaKpiDesc').val(),
            targetValue: $('#txtKpiTarget').val(),
            targetUnit: $('#optKpiUnit').val(),
            demeritPoint: $('#txtKpiDemerit').val(),
            weightagePct: $('#txtKpiWeight').val(),
            passRule: $('#optKpiPassRule').val(),
            calcType: $('#optKpiCalc').val(),
            formulaExpr: $('#txtKpiFormula').val(),
            sourceType: $('#optKpiSource').val(),
            sortOrder: $('#txtKpiOrder').val(),
            piStatus: $('#optKpiStatus').val(),
            effectiveFrom: $('#txtKpiEffective').val()
        };
        if (!body.groupId || !body.piNo || !body.piName) {
            toastr['warning']('Enter the KPI group, PI number and name.', _ALERT_TITLE_WARNING);
            return;
        }
        ShowLoader();
        try {
            kc.api(id ? ('pi/' + id) : 'pi', id ? 'PUT' : 'POST', body);
            $('#modal_kpa_pi').modal('hide');
            reload();
        } catch (e) {
            toastr['error'](e.message, _ALERT_TITLE_ERROR);
        }
        HideLoader();
    };

    // ------------------------------------------------------------ parameters

    const renderParams = function (rows) {
        const body = $('#tblKprList tbody');
        if (!rows || !rows.length) {
            body.html('<tr><td colspan="7"><div class="gems-empty-state"><i class="fas fa-sliders"></i><p>No parameters yet</p></div></td></tr>');
            return;
        }
        body.html(rows.map(function (r) {
            return '<tr>' +
                '<td><code>' + r.paramKey + '</code></td>' +
                '<td>' + kc.escape(r.paramLabel) + '</td>' +
                '<td>' + r.dataType + '</td>' +
                '<td>' + r.sourceType + '</td>' +
                '<td>' + (r.isRequired ? 'Yes' : 'No') + '</td>' +
                '<td>' + r.sortOrder + '</td>' +
                '<td class="text-nowrap">' +
                kc.actionBtn({ tint: 'gems-btn-action-edit', cls: 'lnkKprEdit', title: 'Edit', icon: 'fas fa-pen-to-square', extra: 'data-id="' + r.paramId + '"' }) +
                kc.actionBtn({ tint: 'gems-btn-action-delete', cls: 'lnkKprDel', title: 'Remove', icon: 'fas fa-trash', extra: 'data-id="' + r.paramId + '"' }) +
                '</td></tr>';
        }).join(''));
        $('#tblKprList tbody').data('rows', rows);
    };

    const clearParamForm = function () {
        $('#hidKprParamId').val('');
        $('#txtKprKey').val('');
        $('#txtKprLabel').val('');
        $('#optKprType').val('NUMBER');
        $('#optKprSource').val('MANUAL');
        $('#txtKprHook').val('');
        $('#txtKprOrder').val(1);
        $('#chkKprRequired').prop('checked', true);
    };

    const openParams = function (row) {
        $('#hidKprPiId').val(row.piId);
        $('#h4KprTitle').text('Parameters for PI ' + row.piNo);
        $('#pKprHelp').text('Parameters for "' + row.piName + '". The formula refers to these keys.');
        clearParamForm();
        renderParams(kc.apiGet('pi/' + row.piId + '/param'));
        $('#modal_kpa_param').modal({ backdrop: 'static' });
    };

    const saveParam = function () {
        const piId = $('#hidKprPiId').val();
        const paramId = $('#hidKprParamId').val();
        const body = {
            paramKey: $('#txtKprKey').val(),
            paramLabel: $('#txtKprLabel').val(),
            dataType: $('#optKprType').val(),
            sourceType: $('#optKprSource').val(),
            gemsHook: $('#txtKprHook').val(),
            sortOrder: $('#txtKprOrder').val(),
            isRequired: $('#chkKprRequired').is(':checked') ? 1 : 0
        };
        if (!body.paramKey || !body.paramLabel) {
            toastr['warning']('Enter the parameter key and label.', _ALERT_TITLE_WARNING);
            return;
        }
        ShowLoader();
        try {
            const url = paramId ? ('pi/' + piId + '/param/' + paramId) : ('pi/' + piId + '/param');
            renderParams(kc.api(url, paramId ? 'PUT' : 'POST', body));
            clearParamForm();
            loadPi();
        } catch (e) {
            toastr['error'](e.message, _ALERT_TITLE_ERROR);
        }
        HideLoader();
    };

    this.init = function () {
        kc.loadCaps();
        if (!kc.caps.canAdmin) {
            toastr['warning']('Only a KPI Admin can change the KPI structure.', _ALERT_TITLE_WARNING);
            $('#btnKstPiAdd, #btnKstGroupAdd, #btnKstSaveConfig').prop('disabled', true);
        }
        kc.loadSites();
        kc.fillSelect('optKstSite', kc.sites, 'siteId', function (r) { return r.siteName; }, null, kc.caps.siteId || '');

        dtGroup = $('#dtKstGroup').DataTable({
            data: [], bLengthChange: false, searching: false, pageLength: 25, autoWidth: false,
            language: kc.dtEmpty('fa-layer-group', 'No KPI groups yet.'),
            ordering: false, dom: kc.dtDom,
            columns: [
                { data: null },
                { data: 'groupNo' },
                { data: 'groupName' },
                { data: 'piCount' },
                { data: 'weightageTotal', className: 'gems-num', render: function (v) { return kc.fmtNumber(v, 2) + ' %'; } },
                { data: 'sortOrder' },
                { data: 'groupStatus', render: kc.activeBadge },
                { data: null, orderable: false, className: 'noVis text-nowrap', render: function (r) {
                    return kc.actionBtn({ tint: 'gems-btn-action-edit', cls: 'lnkKgpEdit', title: 'Edit', icon: 'fas fa-pen-to-square', extra: 'data-id="' + r.groupId + '"' }) +
                        kc.actionBtn({ tint: 'gems-btn-action-delete', cls: 'lnkKgpDel', title: 'Deactivate', icon: 'fas fa-ban', extra: 'data-id="' + r.groupId + '"' });
                } }
            ],
            fnRowCallback: function (n, d, i) { $('td', n).eq(0).html(i + 1); }
        });

        dtPi = $('#dtKstPi').DataTable({
            data: [], bLengthChange: false, searching: false, pageLength: 25, autoWidth: false,
            language: kc.dtEmpty('fa-list-check', 'No performance indicators yet.'),
            ordering: false, dom: kc.dtDomButtons,
            buttons: kc.dtButtons('GEMS - KPI Structure'),
            columns: [
                { data: null, render: function (r) { return r.groupNo + ' — ' + r.groupName; } },
                { data: 'piNo' },
                { data: 'piName' },
                { data: null, className: 'gems-num', render: function (r) { return kc.fmtNumber(r.targetValue, 2) + (r.targetUnit === 'BEI' ? '' : ' %'); } },
                { data: 'demeritPoint' },
                { data: 'weightagePct', className: 'gems-num', render: function (v) { return kc.fmtNumber(v, 2) + ' %'; } },
                { data: 'passRule', render: passRuleLabel },
                { data: null, render: calcLabel },
                { data: 'sourceType' },
                { data: 'paramCount' },
                { data: 'piStatus', render: kc.activeBadge },
                { data: null, orderable: false, className: 'noVis text-nowrap', render: function (r) {
                    return kc.actionBtn({ tint: 'gems-btn-action-view', cls: 'lnkKpiParam', title: 'Parameters', icon: 'fas fa-sliders', extra: 'data-id="' + r.piId + '"' }) +
                        kc.actionBtn({ tint: 'gems-btn-action-edit', cls: 'lnkKpiEdit', title: 'Edit', icon: 'fas fa-pen-to-square', extra: 'data-id="' + r.piId + '"' }) +
                        kc.actionBtn({ tint: 'gems-btn-action-delete', cls: 'lnkKpiDel', title: 'Deactivate', icon: 'fas fa-ban', extra: 'data-id="' + r.piId + '"' });
                } }
            ]
        });
        dtPi.buttons().container().appendTo($('#btnDtKstPiExport'));
        kc.bindDtTooltips('#dtKstPi');
        kc.bindDtTooltips('#dtKstGroup');

        reload();

        $('#kstTabs a').on('shown.bs.tab', function () {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });

        $('#optKstSite').off('change').on('change', reload);
        $('#btnKstRefresh').off('click').on('click', reload);
        $('#btnKstSaveConfig').off('click').on('click', function () {
            ShowLoader();
            try {
                kc.api('config', 'POST', { siteId: siteId(), maxApdPct: $('#txtKstMaxApd').val() });
                loadConfig();
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        });

        $('#btnKstGroupAdd').off('click').on('click', function () { openGroup(null); });
        $('#btnKstPiAdd').off('click').on('click', function () {
            if (!activeGroups().length) {
                toastr['warning']('Create a KPI group first.', _ALERT_TITLE_WARNING);
                return;
            }
            openPi(null);
        });

        $(document).off('click', '.lnkKgpEdit').on('click', '.lnkKgpEdit', function (e) {
            e.preventDefault();
            const id = $(this).data('id');
            openGroup(groups.find(function (g) { return String(g.groupId) === String(id); }));
        });
        $(document).off('click', '.lnkKgpDel').on('click', '.lnkKgpDel', function (e) {
            e.preventDefault();
            if (!window.confirm('Deactivate this KPI group and all of its indicators?')) { return; }
            ShowLoader();
            try { kc.api('group/' + $(this).data('id'), 'DELETE'); reload(); }
            catch (err) { toastr['error'](err.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });

        $(document).off('click', '.lnkKpiEdit').on('click', '.lnkKpiEdit', function (e) {
            e.preventDefault();
            const id = $(this).data('id');
            openPi(piRows.find(function (r) { return String(r.piId) === String(id); }));
        });
        $(document).off('click', '.lnkKpiDel').on('click', '.lnkKpiDel', function (e) {
            e.preventDefault();
            if (!window.confirm('Deactivate this Performance Indicator?')) { return; }
            ShowLoader();
            try { kc.api('pi/' + $(this).data('id'), 'DELETE'); reload(); }
            catch (err) { toastr['error'](err.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
        $(document).off('click', '.lnkKpiParam').on('click', '.lnkKpiParam', function (e) {
            e.preventDefault();
            const id = $(this).data('id');
            openParams(piRows.find(function (r) { return String(r.piId) === String(id); }));
        });

        $(document).off('click', '#btnKgpSave').on('click', '#btnKgpSave', saveGroup);
        $(document).off('click', '#btnKpiSave').on('click', '#btnKpiSave', savePi);
        $(document).off('click', '#btnKpiTest').on('click', '#btnKpiTest', testFormula);
        $(document).off('click', '#btnKprSave').on('click', '#btnKprSave', saveParam);
        $(document).off('change', '#optKpiCalc').on('change', '#optKpiCalc', toggleFormulaFields);
        $(document).off('input', '#txtKpiFormula').on('input', '#txtKpiFormula', renderTestInputs);

        $(document).off('click', '.lnkKprEdit').on('click', '.lnkKprEdit', function (e) {
            e.preventDefault();
            const id = $(this).data('id');
            const rows = $('#tblKprList tbody').data('rows') || [];
            const row = rows.find(function (r) { return String(r.paramId) === String(id); });
            if (!row) { return; }
            $('#hidKprParamId').val(row.paramId);
            $('#txtKprKey').val(row.paramKey);
            $('#txtKprLabel').val(row.paramLabel);
            $('#optKprType').val(row.dataType);
            $('#optKprSource').val(row.sourceType);
            $('#txtKprHook').val(row.gemsHook || '');
            $('#txtKprOrder').val(row.sortOrder);
            $('#chkKprRequired').prop('checked', !!row.isRequired);
        });
        $(document).off('click', '.lnkKprDel').on('click', '.lnkKprDel', function (e) {
            e.preventDefault();
            if (!window.confirm('Remove this parameter?')) { return; }
            ShowLoader();
            try {
                renderParams(kc.api('pi/' + $('#hidKprPiId').val() + '/param/' + $(this).data('id'), 'DELETE'));
                clearParamForm();
                loadPi();
            } catch (err) { toastr['error'](err.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
    };
}
