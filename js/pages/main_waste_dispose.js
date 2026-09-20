function MainWasteDispose() {
    const wc = new WasteCommon();
    let record = null;
    const files = { during: null, after: null, note: null, receipt: null };

    const isImage = function (payload) {
        return payload && typeof payload.type === 'string' && payload.type.indexOf('image/') === 0;
    };

    const preview = function (slot, payload) {
        const prev = $('#prev' + slot);
        const hint = $('#hint' + slot);
        const drop = $('#drop' + slot);
        if (!payload) {
            prev.empty();
            drop.removeClass('is-set');
            return;
        }
        drop.addClass('is-set');
        hint.text(payload.name + ' · ' + wc.fileSize(payload.size));
        prev.empty();
        if (isImage(payload)) {
            prev.append($('<img>').attr('src', 'data:' + payload.type + ';base64,' + payload.data));
        } else {
            prev.append('<div class="mt-2"><i class="fas fa-file-pdf text-danger me-1"></i>' + payload.name + '</div>');
        }
    };

    const refreshSaveState = function () {
        const ready = !!(files.during && files.after) && !!record && record.collectionStatus === 'PENDING';
        $('#btnWdsSave').prop('disabled', !ready);
        if (!record || record.collectionStatus !== 'PENDING') {
            $('#lblWdsFooterNote').text('This record is not pending collection.');
        } else if (!files.during && !files.after) {
            $('#lblWdsFooterNote').text('Both disposal images are required before you can save.');
        } else if (!files.during) {
            $('#lblWdsFooterNote').text('The during disposal image is still required.');
        } else if (!files.after) {
            $('#lblWdsFooterNote').text('The after disposal image is still required.');
        } else {
            $('#lblWdsFooterNote').text('Ready to save. The actual weight becomes the official disposed quantity.');
        }
    };

    const showVariance = function () {
        if (!record) { return; }
        const actual = Number($('#txtWdsActualKg').val());
        const registered = Number(record.registeredKg);
        if (!Number.isFinite(actual) || actual <= 0) {
            $('#divWdsVariance').hide();
            return;
        }
        const diff = Math.round((actual - registered) * 1000) / 1000;
        if (diff === 0) {
            $('#divWdsVariance').hide();
            return;
        }
        if (diff > 0) {
            $('#lblWdsVariance').html('The actual weight is <strong>' + wc.fmtQty(diff) + '</strong> more than the registered weight. The premise must hold enough of this waste type or the save is rejected.');
        } else {
            $('#lblWdsVariance').html('The actual weight is <strong>' + wc.fmtQty(Math.abs(diff)) + '</strong> less than the registered weight. The remainder stays in the premise balance.');
        }
        $('#divWdsVariance').show();
    };

    const bindFile = function (inputId, slot, key) {
        $('#' + inputId).off('change').on('change', function () {
            const file = this.files && this.files[0];
            if (!file) { return; }
            wc.readFileObject(file).then(function (payload) {
                if (!payload) { return; }
                files[key] = payload;
                preview(slot, payload);
                refreshSaveState();
            });
        });
    };

    const applyRecord = function (row) {
        record = row;
        $('#hidWdsId').val(row.txnId);
        $('#txtWdsRef').val(row.txnRef);
        $('#txtWdsType').val(row.swCode + ' — ' + (row.swDescription || ''));
        $('#txtWdsGenDate').val(wc.fmtDate(row.eventDate));
        $('#txtWdsRegKg').val(wc.fmtQty(row.registeredKg));
        $('#txtWdsSite').val((row.siteCode || '') + ' ' + (row.siteName || ''));
        $('#lblWdsRefChip').text(row.txnRef);
        $('#txtWdsActualKg').val(row.registeredKg);

        const badge = $('#lblWdsStatusBadge');
        if (row.collectionStatus === 'DISPOSED') {
            badge.attr('class', 'badge gems-badge gems-badge-success').text('Disposed').show();
            $('#formWds :input').prop('disabled', true);
            $('#pWdsSubtitle').text('This waste record has already been disposed.');
        } else {
            badge.attr('class', 'badge gems-badge gems-badge-warning').text('Pending Collection').show();
        }
        refreshSaveState();
    };

    const save = function () {
        const actual = Number($('#txtWdsActualKg').val());
        if (!files.during || !files.after) {
            toastr['warning']('Attach both the during and after disposal images.', _ALERT_TITLE_WARNING);
            return;
        }
        if (!Number.isFinite(actual) || actual <= 0) {
            toastr['warning']('Enter the actual disposed weight.', _ALERT_TITLE_WARNING);
            return;
        }
        const disposalDate = $('#txtWdsDate').val();
        if (!disposalDate) { toastr['warning']('Enter the disposal date.', _ALERT_TITLE_WARNING); return; }

        const body = {
            disposalDate: disposalDate,
            actualQtyKg: actual,
            remarks: $('#txtWdsRemarks').val() || '',
            duringImage: { fileUpload: files.during },
            afterImage: { fileUpload: files.after },
            consignmentNoteRef: $('#txtWdsNoteRef').val() || '',
            consignmentReceiptRef: $('#txtWdsReceiptRef').val() || ''
        };
        if (files.note) { body.consignmentNote = { fileUpload: files.note }; }
        if (files.receipt) { body.consignmentReceipt = { fileUpload: files.receipt }; }

        ShowLoader();
        try {
            wc.api('generation/' + $('#hidWdsId').val() + '/dispose', 'POST', body);
            window.location.href = 'p_waste_pending';
        } catch (e) {
            toastr['error'](e.message, _ALERT_TITLE_ERROR);
            HideLoader();
        }
    };

    this.init = function () {
        wc.loadCaps();
        const txnId = wc.queryParam('id');
        if (!txnId) {
            toastr['error']('No waste record was selected.', _ALERT_TITLE_ERROR);
            return;
        }
        $('#txtWdsDate').val(moment().format('YYYY-MM-DD')).attr('max', moment().format('YYYY-MM-DD'));
        applyRecord(wc.apiGet('generation/' + txnId));
        if (!wc.caps.canDispose) {
            $('#btnWdsSave').prop('disabled', true);
            $('#lblWdsFooterNote').text('You are not allowed to record disposals.');
        }

        bindFile('filWdsDuring', 'WdsDuring', 'during');
        bindFile('filWdsAfter', 'WdsAfter', 'after');
        bindFile('filWdsNote', 'WdsNote', 'note');
        bindFile('filWdsReceipt', 'WdsReceipt', 'receipt');
        $('#txtWdsActualKg').off('input change').on('input change', showVariance);
        $('#btnWdsSave').off('click').on('click', save);
    };
}
