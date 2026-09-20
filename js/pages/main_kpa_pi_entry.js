function MainKpaPiEntry() {
    const kc = new KpaCommon();
    let record = null;
    let saveTimer = null;
    let saveInFlight = null;
    let saveQueued = false;
    let savedHideTimer = null;

    const ruleText = function (rule, unit) {
        const noun = unit === 'BEI' ? 'BEI' : 'achievement';
        if (rule === 'LTE_TARGET') { return 'Passes when the ' + noun + ' is at or below target'; }
        if (rule === 'EQ_TARGET') { return 'Passes when the ' + noun + ' equals target'; }
        return 'Passes when the ' + noun + ' is at or above target';
    };

    const renderParams = function () {
        const box = $('#divKpeParams').empty();
        const params = record.params || [];
        if (!params.length) {
            box.html('<div class="gems-field gems-field-12"><div class="gems-empty-state"><i class="fas fa-sliders"></i><p>This indicator has no parameters configured. Ask a KPI Admin to add them.</p></div></div>');
            return;
        }
        params.forEach(function (p) {
            const step = p.dataType === 'INT' ? '1' : 'any';
            const auto = p.sourceType === 'GEMS'
                ? '<div class="form-hint">Marked for GEMS+ automation. Enter the value manually for now.</div>'
                : '';
            const value = (p.paramValue === null || p.paramValue === undefined) ? '' : p.paramValue;
            box.append(
                '<div class="gems-field gems-field-6">' +
                '<label class="form-label" for="txtKpe_' + p.paramKey + '">' + kc.escape(p.paramLabel) +
                (p.isRequired ? ' <span class="required" aria-hidden="true">*</span>' : '') + '</label>' +
                '<input type="number" step="' + step + '" class="form-control kpeParam" id="txtKpe_' + p.paramKey + '" data-key="' + p.paramKey + '" ' +
                'value="' + value + '"' + (p.isRequired ? ' aria-required="true"' : '') + '>' +
                '<div class="form-hint">Key <code>' + p.paramKey + '</code> · ' + p.dataType + '</div>' + auto +
                '</div>'
            );
        });
    };

    const renderResults = function () {
        $('#mKpeTarget').text(kc.fmtNumber(record.targetValue, 2) + (record.targetUnit === 'BEI' ? '' : '%'));
        $('#mKpeRule').text(ruleText(record.passRule, record.targetUnit));
        $('#mKpeActual').html(kc.fmtActual(record.actualValue, record.targetUnit));
        $('#mKpeResult').html(record.isPass === null || record.isPass === undefined
            ? 'Not calculated'
            : (record.isPass ? 'Target met' : 'Target not met'));
        $('#mKpeApdValue').text(kc.fmtMoney(record.apdValue));
        $('#mKpeWeight').text(kc.fmtNumber(record.weightagePct, 2) + '% of ' + kc.fmtMoney(record.apdMaxAmount));
        $('#mKpeApdDeducted').text(kc.fmtMoney(record.apdDeducted));
        $('#mKpeDemerit').text(record.demeritImposed + ' of ' + record.demeritPoint + ' demerit point(s) imposed');
        $('#lblKpeStatus').html(kc.piStatusBadge(record.piStatus));

        if (record.calcMessage) {
            $('#lblKpeMessage').text(record.calcMessage);
            $('#divKpeMessage').removeClass('d-none');
        } else {
            $('#divKpeMessage').addClass('d-none');
        }
    };

    const applyChrome = function (row) {
        const readOnly = !row.canEdit;
        $('.kpeParam, #txtKpeRemarks').prop('disabled', readOnly);
        $('#btnKpeSave, #btnKpeSubmit').toggle(!readOnly);
        $('#btnKpeReopen').toggle(!!row.canReopen);
        if (row.piStatus === 'SUBMITTED') {
            $('#lblKpeFooterNote').text('Submitted' +
                (row.submittedByName ? ' by ' + row.submittedByName : '') +
                (row.submittedAt ? ' on ' + row.submittedAt : '') +
                '. A KPI Admin can reopen it.');
        } else if (readOnly) {
            $('#lblKpeFooterNote').text('This indicator is not assigned to you.');
        } else {
            $('#lblKpeFooterNote').text('Values save as you leave each field. Submitting locks them.');
        }
    };

    const applyRecord = function (row) {
        record = row;
        $('#lblKpeGroup').text(row.groupNo + ' — ' + row.groupName);
        $('#lblKpeTitle').text('PI ' + row.piNo + ' · ' + row.piName);
        $('#lblKpeSub').text(row.monthName + ' ' + row.evalYear + ' · ' + (row.siteName || ''));
        $('#lnkKpeBack').attr('href', 'p_kpa_evaluation?evalId=' + row.evalId);
        $('#txtKpeRemarks').val(row.remarks || '');
        $('#lblKpeFormula').html(row.calcType === 'EXPRESSION' && row.formulaExpr
            ? 'Calculated as <span class="kpa-formula">' + kc.escape(row.formulaExpr) + '</span>'
            : 'Calculated by the built-in <strong>' + row.calcType + '</strong> rule.');

        renderParams();
        renderResults();
        applyChrome(row);
    };

    const applyResults = function (row) {
        if (!row) { return; }
        [
            'actualValue', 'isPass', 'apdValue', 'apdDeducted', 'demeritImposed', 'demeritPoint',
            'weightagePct', 'targetValue', 'targetUnit', 'passRule', 'calcMessage', 'piStatus',
            'apdMaxAmount', 'canEdit', 'canReopen', 'submittedByName', 'submittedAt'
        ].forEach(function (key) {
            if (row[key] !== undefined) { record[key] = row[key]; }
        });
        renderResults();
        applyChrome(record);
    };

    const collect = function () {
        const params = {};
        $('.kpeParam').each(function () {
            params[$(this).data('key')] = $(this).val();
        });
        return params;
    };

    const showSaved = function () {
        kc.setSaveState('lblKpeSaveState', 'saved');
        clearTimeout(savedHideTimer);
        savedHideTimer = setTimeout(function () { kc.setSaveState('lblKpeSaveState', 'idle'); }, 1600);
    };

    const persist = function (opts) {
        opts = opts || {};
        const silent = !!opts.silent;
        const autosave = !!opts.autosave;
        const payload = {
            params: collect(),
            remarks: $('#txtKpeRemarks').val(),
            autosave: autosave ? 1 : 0
        };

        const send = function () {
            if (autosave) {
                kc.setSaveState('lblKpeSaveState', 'saving');
                const done = function () {
                    saveInFlight = null;
                    if (saveQueued) {
                        saveQueued = false;
                        persist({ silent: true, autosave: true });
                    }
                };
                const req = kc.apiAsync('evaluation_pi/' + record.evalPiId + '/params', 'PUT', payload)
                    .then(function (row) {
                        applyResults(row);
                        showSaved();
                        done();
                        return row;
                    }, function (e) {
                        kc.setSaveState('lblKpeSaveState', 'error', e.message);
                        if (!silent) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
                        done();
                        throw e;
                    });
                saveInFlight = req;
                return req;
            }

            ShowLoader();
            return kc.apiAsync('evaluation_pi/' + record.evalPiId + '/params', 'PUT', payload)
                .then(function (row) {
                    applyResults(row);
                    showSaved();
                    return row;
                })
                .catch(function (e) {
                    if (!silent) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
                    throw e;
                })
                .finally(function () { HideLoader(); });
        };

        if (autosave && saveInFlight) {
            saveQueued = true;
            return saveInFlight;
        }
        return send();
    };

    const scheduleAutosave = function () {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(function () {
            if (record && record.canEdit) {
                persist({ silent: true, autosave: true });
            }
        }, 400);
    };

    const flushAutosave = function () {
        clearTimeout(saveTimer);
        if (saveInFlight) { return saveInFlight; }
        if (record && record.canEdit) {
            return persist({ silent: true, autosave: true });
        }
        return Promise.resolve();
    };

    const save = function (silent) {
        clearTimeout(saveTimer);
        const wait = saveInFlight || Promise.resolve();
        return wait.then(function () {
            return persist({ silent: !!silent, autosave: false });
        }).then(function () { return true; }, function () { return false; });
    };

    const submit = function () {
        flushAutosave().then(function () {
            if (!window.confirm('Submit PI ' + record.piNo + '? The values are locked once submitted.')) { return; }
            ShowLoader();
            return kc.apiAsync('evaluation_pi/' + record.evalPiId + '/submit', 'POST', {})
                .then(function (row) { applyRecord(row); })
                .catch(function (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); })
                .finally(function () { HideLoader(); });
        }).catch(function (e) {
            toastr['error'](e.message || 'Save the values before submitting.', _ALERT_TITLE_ERROR);
        });
    };

    const reopen = function () {
        if (!window.confirm('Reopen PI ' + record.piNo + ' for editing?')) { return; }
        ShowLoader();
        kc.apiAsync('evaluation_pi/' + record.evalPiId + '/reopen', 'POST', {})
            .then(function (row) { applyRecord(row); })
            .catch(function (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); })
            .finally(function () { HideLoader(); });
    };

    this.init = function () {
        kc.loadCaps();
        const evalPiId = kc.queryParam('id');
        if (!evalPiId) {
            toastr['error']('No Performance Indicator was selected.', _ALERT_TITLE_ERROR);
            return;
        }
        applyRecord(kc.apiGet('evaluation_pi/' + evalPiId));

        $('#btnKpeSave').off('click').on('click', function () { save(false); });
        $('#btnKpeSubmit').off('click').on('click', submit);
        $('#btnKpeReopen').off('click').on('click', reopen);
        $(document).off('change', '.kpeParam').on('change', '.kpeParam', function () {
            if (record && record.canEdit) { scheduleAutosave(); }
        });
        $('#txtKpeRemarks').off('change').on('change', function () {
            if (record && record.canEdit) { scheduleAutosave(); }
        });
    };
}
