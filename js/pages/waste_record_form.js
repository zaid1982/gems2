function WasteRecordForm() {
    const wc = new WasteCommon();
    let pendingDocs = [];
    let current = null;
    let reasonMode = 'amend';

    const payload = function () {
        return {
            clientRef: $('#hidWrfClientRef').val(),
            siteId: $('#optWrfSite').val(),
            txnType: $('input[name="txnType"]:checked').val(),
            eventDate: $('#txtWrfEvent').val(),
            swCodeId: $('#optWrfSw').val(),
            sourceActivity: $('#txtWrfSource').val(),
            woRef: $('#txtWrfWo').val(),
            handlingMethodId: $('#optWrfHandle').val(),
            locationId: $('#optWrfLoc').val(),
            locationText: $('#txtWrfLocText').val(),
            remarks: $('#txaWrfRemarks').val(),
            qty: $('#txtWrfQty').val(),
            unit: $('#optWrfUnit').val() || 'MT',
            packagingTypeId: $('#optWrfPack').val(),
            packageCount: $('#txtWrfPkg').val(),
            transporterId: $('#optWrfTrans').val(),
            transporterText: $('#txtWrfTrans').val(),
            receiverId: $('#optWrfRecv').val(),
            receiverText: $('#txtWrfRecv').val(),
            vehicleReg: $('#txtWrfVeh').val(),
            externalRef: $('#txtWrfExt').val()
        };
    };

    const defaultDocType = function () {
        const types = wc.refsByType('DOCUMENT_TYPE');
        if (types.length) {
            $('#optWrfDocType').val(String(types[0].refValueId));
        }
        if (!$('#optWrfDocType').val()) {
            $('#optWrfDocType').find('option').each(function () {
                if (this.value) { $('#optWrfDocType').val(this.value); return false; }
            });
        }
    };

    const fillPremise = function (siteId) {
        const p = siteId ? (wc.siteById(siteId) || {}) : {};
        $('#txtWrfAddress').val(p.premiseAddress || p.siteDesc || '');
        $('#txtWrfPhone').val(p.premiseContactNo || '');
    };

    const syncLocationFallback = function () {
        const hasOptions = $('#optWrfLoc option').filter(function () { return this.value; }).length > 0;
        $('#txtWrfLocText').toggleClass('d-none', hasOptions);
    };

    const syncChrome = function () {
        const status = String($('#txtWrfStatus').val() || 'DRAFT').toUpperCase();
        const ref = $('#txtWrfRef').val();
        const labels = { DRAFT: 'Draft', FINAL: 'Final', CANCELLED: 'Cancelled' };
        const icons = { DRAFT: 'fa-hourglass-half', FINAL: 'fa-check-circle', CANCELLED: 'fa-ban' };
        $('#lblWrfRefChip').text(ref && ref !== '(generated on save)' ? ref : 'New record');
        if (!$('#hidWrfId').val() || !status) {
            $('#lblWrfStatusBadge').hide().empty();
        } else {
            $('#lblWrfStatusBadge')
                .show()
                .attr('class', 'waste-status-badge is-' + status.toLowerCase())
                .html('<i class="fas ' + (icons[status] || 'fa-circle') + '"></i>' + (labels[status] || status));
        }
        $('#formWrf').toggleClass('is-disposed', $('input[name="txnType"]:checked').val() === 'D')
            .toggleClass('is-produced', $('input[name="txnType"]:checked').val() === 'P');
        $('#pWrfSubtitle').text('Fifth Schedule (Regulation 11)');
    };

    const toggleType = function () {
        syncChrome();
        applyBalanceLocal();
    };

    const syncAliasHeight = function () {
        const el = document.getElementById('txtWrfAlias');
        if (!el) { return; }
        el.style.height = 'auto';
        el.style.height = Math.max(58, el.scrollHeight) + 'px';
    };

    const refreshDesc = function () {
        const row = wc.swCodes.find(function (r) { return String(r.swCodeId) === String($('#optWrfSw').val()); });
        const alias = row && row.profileAlias ? row.profileAlias : (row ? row.swDescription : '');
        const official = row ? (row.swDescription || '') : '';
        $('#txtWrfAlias').val(alias || '');
        $('#lblWrfDesc').text(official);
        $('#lblWrfDesc').toggleClass('d-none', !official || official === alias);
        syncAliasHeight();
        refreshBalance();
    };

    const qtyKg = function () {
        const qty = Number($('#txtWrfQty').val() || 0);
        return ($('#optWrfUnit').val() === 'KG') ? qty : qty * 1000;
    };

    const refreshQty = function () {
        const kg = qtyKg();
        $('#txtWrfQtyKg').val($('#txtWrfQty').val() ? (kg.toFixed(3) + ' kg') : '');
        applyBalanceLocal();
    };

    let balCache = { key: '', beforeKg: null };
    let balTimer = null;
    let balSeq = 0;

    const balanceKey = function () {
        const exclude = $('#hidWrfId').val() && current && current.txnStatus === 'FINAL' ? String($('#hidWrfId').val()) : '';
        return [$('#optWrfSite').val() || '', $('#optWrfSw').val() || '', $('#txtWrfEvent').val() || '', exclude].join('|');
    };

    const applyBalanceLocal = function () {
        const siteId = $('#optWrfSite').val();
        const sw = $('#optWrfSw').val();
        const asAt = $('#txtWrfEvent').val();
        if (!siteId || !sw || !asAt || balCache.key !== balanceKey() || balCache.beforeKg == null) {
            if (!siteId || !sw || !asAt) {
                $('#txtWrfBalBefore').val('');
                $('#txtWrfQty').removeClass('is-invalid');
                $('#txtWrfBalBefore').removeClass('is-invalid');
            }
            return;
        }
        const qty = Number($('#txtWrfQty').val() || 0);
        const type = $('input[name="txnType"]:checked').val();
        const beforeKg = Number(balCache.beforeKg || 0);
        $('#txtWrfBalBefore').val(wc.fmtMt(beforeKg));
        const after = type === 'D' ? beforeKg - qtyKg() : beforeKg + qtyKg();
        $('#txtWrfBalAfter').val(wc.fmtQty(after));
        const over = type === 'D' && qty > 0 && after < -0.0005;
        $('#txtWrfQty').toggleClass('is-invalid', over);
        $('#txtWrfBalBefore').toggleClass('is-invalid', over);
    };

    const refreshBalance = function () {
        const siteId = $('#optWrfSite').val();
        const sw = $('#optWrfSw').val();
        const asAt = $('#txtWrfEvent').val();
        if (!siteId || !sw || !asAt) {
            balCache = { key: '', beforeKg: null };
            applyBalanceLocal();
            return;
        }
        const key = balanceKey();
        if (balCache.key === key && balCache.beforeKg != null) {
            applyBalanceLocal();
            return;
        }
        const exclude = $('#hidWrfId').val() && current && current.txnStatus === 'FINAL' ? ('&excludeId=' + $('#hidWrfId').val()) : '';
        if (!$('#txtWrfBalBefore').val()) {
            $('#txtWrfBalBefore').val('…');
        }
        clearTimeout(balTimer);
        balTimer = setTimeout(function () {
            const seq = ++balSeq;
            wc.apiGetAsync('balance?siteId=' + encodeURIComponent(siteId) + '&swCodeId=' + encodeURIComponent(sw) + '&asAt=' + encodeURIComponent(asAt) + exclude)
                .then(function (data) {
                    if (seq !== balSeq) { return; }
                    balCache = { key: key, beforeKg: Number((data && data.balanceBeforeKg) || 0) };
                    applyBalanceLocal();
                })
                .catch(function () { /* ignore live preview errors */ });
        }, 180);
    };

    const currentKeep = function () {
        return {
            site: $('#optWrfSite').val(),
            sw: $('#optWrfSw').val(),
            handle: $('#optWrfHandle').val(),
            loc: $('#optWrfLoc').val(),
            pack: $('#optWrfPack').val()
        };
    };

    const paintLookups = function (siteId, keep, includeSites) {
        keep = keep || {};
        if (includeSites) {
            wc.fillSelect('optWrfSite', wc.sites, 'siteId', function (r) { return r.siteName + ' (' + r.siteCode + ')'; }, 'Select premise', siteId || keep.site);
        }
        wc.fillSelect('optWrfSw', (wc.swCodes || []).filter(function (r) {
            return Number(r.swStatus) === 1 && (r.profileStatus === null || r.profileStatus === undefined || Number(r.profileStatus) === 1);
        }), 'swCodeId', function (r) { return r.swCode; }, 'Select code', keep.sw);
        wc.fillSelect('optWrfHandle', wc.refsByType('HANDLING_METHOD'), 'refValueId', 'valueName', 'Select handling', keep.handle);
        wc.fillSelect('optWrfLoc', wc.locations.filter(function (l) { return Number(l.locationStatus) === 1; }), 'locationId', 'locationName', 'Select location', keep.loc);
        wc.fillSelect('optWrfPack', wc.refsByType('PACKAGING_TYPE'), 'refValueId', 'valueName', 'Select packaging', keep.pack);
        wc.fillSelect('optWrfTrans', wc.refsByType('TRANSPORTER'), 'refValueId', 'valueName', 'Optional');
        wc.fillSelect('optWrfRecv', wc.refsByType('RECEIVER'), 'refValueId', 'valueName', 'Optional');
        wc.fillSelect('optWrfDocType', wc.refsByType('DOCUMENT_TYPE'), 'refValueId', 'valueName', 'Select type');
        defaultDocType();
        syncLocationFallback();
        fillPremise(siteId || $('#optWrfSite').val());
    };

    const fillLookups = function (siteId, keep) {
        wc.loadLookups(siteId);
        paintLookups(siteId, keep, true);
    };

    const changeSite = function (siteId) {
        const keep = currentKeep();
        keep.site = siteId;
        fillPremise(siteId);
        wc.loadLookupsAsync(siteId).then(function () {
            if (String($('#optWrfSite').val() || '') !== String(siteId || '')) { return; }
            paintLookups(siteId, keep, false);
            refreshDesc();
        }).catch(function () {
            refreshDesc();
        });
    };

    const renderDocs = function (docs) {
        const box = $('#divWrfDocChips').empty();
        const saved = docs || [];
        saved.forEach(function (d) {
            box.append(
                '<div class="waste-file-chip">' +
                '<i class="fas fa-file-alt"></i>' +
                '<span><strong>' + (d.docRef || d.documentType || 'Document') + '</strong><small>' + wc.fmtDate(d.docDate) + (d.uploadedByName ? ' · ' + d.uploadedByName : '') + '</small></span>' +
                (d.uploadId ? '<a href="#" class="lnkDocOpen" data-id="' + d.uploadId + '">Open</a>' : '') +
                (d.docId ? '<a href="#" class="text-danger lnkDocDel" data-id="' + d.docId + '">&times;</a>' : '') +
                '</div>'
            );
        });
        pendingDocs.forEach(function (d, i) {
            box.append(
                '<div class="waste-file-chip">' +
                '<i class="fas fa-file-alt"></i>' +
                '<span><strong>' + (d.fileUpload && d.fileUpload.name ? d.fileUpload.name : 'Pending file') + '</strong><small>' + wc.fileSize(d.fileUpload && d.fileUpload.size) + (d.docDate ? ' · ' + d.docDate : ' · Pending save') + '</small></span>' +
                '<a href="#" class="text-danger lnkPendDel" data-i="' + i + '">&times;</a>' +
                '</div>'
            );
        });
    };

    const attachFile = async function (file) {
        const payloadFile = await wc.readFileObject(file);
        if (!payloadFile) { return; }
        defaultDocType();
        const doc = {
            documentTypeId: $('#optWrfDocType').val(),
            documentTypeName: $('#optWrfDocType option:selected').text() || 'Supporting document',
            docRef: payloadFile.name,
            docDate: $('#txtWrfDocDate').val() || '',
            fileUpload: payloadFile
        };
        if ($('#hidWrfId').val() && current && current.txnStatus !== 'CANCELLED') {
            ShowLoader();
            try { applyRecord(wc.api('transaction/' + $('#hidWrfId').val() + '/document', 'POST', doc)); }
            catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        } else {
            pendingDocs.push(doc);
            renderDocs(current ? current.documents : []);
        }
        $('#txfWrfFile').val('');
    };

    /**
     * A Produced record shows its disposal, a Disposed record shows the
     * generation it came from, together with the typed disposal evidence.
     */
    const renderLinked = function (row) {
        const linked = row.linked;
        const box = $('#divWrfLinked');
        if (!linked || !linked.txnId) {
            box.addClass('d-none');
            return;
        }
        const isDisposal = linked.txnType === 'D';
        $('#lblWrfLinkedTitle').text(isDisposal ? 'Disposal collection' : 'Waste generation');
        const fields = [
            ['Reference', linked.txnRef],
            [isDisposal ? 'Disposal date' : 'Generated date', wc.fmtDate(linked.eventDate)],
            [isDisposal ? 'Actual disposed weight' : 'Registered weight', wc.fmtQty(linked.qtyKg)],
            ['Status', linked.txnStatus]
        ];
        if (isDisposal && linked.consignmentNoteRef) { fields.push(['Consignment note', linked.consignmentNoteRef]); }
        if (isDisposal && linked.consignmentReceiptRef) { fields.push(['Consignment receipt', linked.consignmentReceiptRef]); }
        if (isDisposal && linked.disposalRemarks) { fields.push(['Disposal remarks', linked.disposalRemarks]); }
        $('#divWrfLinkedFields').html(fields.map(function (f) {
            return '<div class="gems-field gems-field-6"><label class="form-label">' + f[0] + '</label>' +
                '<input type="text" class="form-control" value="' + String(f[1] === null || f[1] === undefined ? '-' : f[1]).replace(/"/g, '&quot;') + '" readonly disabled></div>';
        }).join(''));
        $('#divWrfLinkedDocs').html((linked.documents || []).map(function (d) {
            return '<div class="waste-file-chip">' +
                '<i class="fas fa-image"></i>' +
                '<span><strong>' + (d.documentType || d.docDescription || 'Document') + '</strong><small>' +
                wc.fmtDate(d.docDate) + (d.docRef ? ' · ' + d.docRef : '') + '</small></span>' +
                (d.uploadId ? '<a href="#" class="lnkDocOpen" data-id="' + d.uploadId + '">Open</a>' : '') +
                '</div>';
        }).join(''));
        box.removeClass('d-none');
    };

    const applyRecord = function (row) {
        current = row;
        $('#hidWrfId').val(row.txnId);
        $('#hidWrfClientRef').val(row.clientRef || wc.uuid());
        $('#txtWrfRef').val(row.txnRef);
        $('#txtWrfStatus').val(row.txnStatus);
        $('#txtWrfRecorded').val(row.txnCreatedAt ? wc.fmtDate(row.txnCreatedAt) : '');
        $('input[name="txnType"][value="' + row.txnType + '"]').prop('checked', true);
        fillLookups(row.siteId, {
            site: row.siteId,
            sw: row.swCodeId,
            handle: row.handlingMethodId,
            loc: row.locationId,
            pack: row.packagingTypeId
        });
        $('#optWrfSite').val(row.siteId);
        $('#optWrfSw').val(row.swCodeId);
        $('#txtWrfEvent').val(row.eventDate);
        $('#txtWrfSource').val(row.sourceActivity || '');
        $('#txtWrfWo').val(row.woRef || '');
        $('#optWrfHandle').val(row.handlingMethodId || '');
        $('#optWrfLoc').val(row.locationId || '');
        $('#txtWrfLocText').val(row.locationText || '');
        $('#txaWrfRemarks').val(row.remarks || '');
        $('#txtWrfQty').val(row.qty);
        $('#optWrfUnit').val(row.unit || 'MT');
        $('#optWrfPack').val(row.packagingTypeId || '');
        $('#txtWrfPkg').val(row.packageCount || '');
        $('#optWrfTrans').val(row.transporterId || '');
        $('#txtWrfTrans').val(row.transporterText || '');
        $('#optWrfRecv').val(row.receiverId || '');
        $('#txtWrfRecv').val(row.receiverText || '');
        $('#txtWrfVeh').val(row.vehicleReg || '');
        $('#txtWrfExt').val(row.externalRef || '');
        $('#lblWrfDesc').text(row.swDescription || '');
        $('#txtWrfAlias').val(row.profileAlias || row.swDescription || '');
        $('#lblWrfDesc').text(row.swDescription || '');
        $('#lblWrfDesc').toggleClass('d-none', !row.swDescription || row.swDescription === (row.profileAlias || row.swDescription));
        syncAliasHeight();
        pendingDocs = [];
        renderDocs(row.documents || []);
        renderLinked(row);
        if (row.history && row.history.length) {
            $('#divWrfHistory').removeClass('d-none');
            $('#divWrfHistList').html(row.history.map(function (h) {
                return '<div class="waste-history-item"><div class="waste-history-dot"></div><div><div class="waste-history-title">' + h.action + '</div><div class="waste-history-meta">' +
                    (h.userName || 'System') + ' · ' + h.createdAt + (h.reason ? (' — ' + h.reason) : '') + '</div></div></div>';
            }).join(''));
        }
        const readonly = row.txnStatus !== 'DRAFT';
        $('#formWrf').find('input, select, textarea').not('#txtWrfDocDate,#txfWrfFile,#txaWamReason').prop('disabled', readonly);
        $('#txtWrfAddress, #txtWrfPhone, #txtWrfRecorded, #txtWrfAlias, #txtWrfBalBefore').prop('disabled', true);
        $('#btnWrfFinal').toggle((!row.txnId || row.txnStatus === 'DRAFT') && wc.caps.canRecord);
        $('#btnWrfCancel').toggle(!row.txnId || row.txnStatus === 'DRAFT' || row.txnStatus === 'FINAL');
        $('#btnWrfAmend').toggle(row.txnStatus === 'FINAL' && wc.caps.canAmendFinal);
        $('#divWrfDrop').toggle((!row.txnId || row.txnStatus === 'DRAFT' || row.txnStatus === 'FINAL') && wc.caps.canRecord);
        $('#btnWrfAmend').data('ready', 0).html('<i class="fas fa-pen me-2"></i>Amend');
        syncChrome();
        refreshQty();
        refreshBalance();
        if (row.reportImpact && row.reportImpact.length && row.txnStatus === 'FINAL') {
            toastr['info']('This record falls in a period with an existing JKR report version.', 'Report impact');
        }
    };

    const askReason = function (mode, fn) {
        reasonMode = mode;
        $('#h4WamTitle').text(mode === 'cancel' ? 'Cancel Draft' : 'Amend Final record');
        $('#pWamHelp').text(mode === 'cancel' ? 'Enter the reason this Draft is being cancelled.' : 'Enter the reason for changing this Final record.');
        $('#txaWamReason').val('');
        $('#modal_waste_amend').modal({ backdrop: 'static' });
        $(document).off('click.wam').on('click.wam', '#btnWamOk', function () {
            const reason = $('#txaWamReason').val();
            if (!reason) { toastr['error']('Enter the reason for changing this Final record.', _ALERT_TITLE_ERROR); return; }
            $('#modal_waste_amend').modal('hide');
            fn(reason);
        });
    };

    this.init = function () {
        wc.loadCaps();
        $('#hidWrfClientRef').val(wc.uuid());
        fillLookups(wc.caps.siteId);
        const today = new Date().toISOString().slice(0, 10);
        $('#txtWrfEvent').val(today);
        $('#txtWrfRecorded').val(wc.fmtDate(today));
        $('#txtWrfDocDate').val(today);
        $('#optWrfUnit').val('MT');
        $('#btnWrfAmend').hide();
        if (!wc.caps.canRecord) { $('#btnWrfFinal, #divWrfDrop').hide(); }
        toggleType();
        refreshBalance();
        $('input[name="txnType"]').on('change', toggleType);
        $('#optWrfSw').on('change', refreshDesc);
        $('#txtWrfQty, #optWrfUnit').on('input change', refreshQty);
        $('#txtWrfEvent').on('change', refreshBalance);
        $('#optWrfSite').on('change', function () {
            changeSite(this.value);
        });

        const drop = document.getElementById('divWrfDrop');
        const fileInput = document.getElementById('txfWrfFile');
        if (drop && fileInput) {
            drop.addEventListener('dragover', function (e) { e.preventDefault(); drop.classList.add('is-over'); });
            drop.addEventListener('dragleave', function () { drop.classList.remove('is-over'); });
            drop.addEventListener('drop', function (e) {
                e.preventDefault();
                drop.classList.remove('is-over');
                const file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
                if (file) { attachFile(file); }
            });
            fileInput.addEventListener('change', function () {
                if (fileInput.files && fileInput.files[0]) { attachFile(fileInput.files[0]); }
            });
        }

        const id = wc.queryParam('id');
        if (id) {
            applyRecord(wc.apiGet('transaction/' + id));
        }

        $('#btnWrfFinal').on('click', function () {
            ShowLoader();
            try {
                const body = payload();
                body.documents = pendingDocs;
                if ($('#hidWrfId').val()) { body.txnId = $('#hidWrfId').val(); }
                const saved = wc.api('transaction/submit', 'POST', body);
                pendingDocs = [];
                if (saved && saved.possibleDuplicate && saved.possibleDuplicate.length) {
                    toastr['warning']('A similar waste record already exists.', 'Possible duplicate');
                }
                wc.goRecords();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
        $('#btnWrfCancel').on('click', function () {
            wc.goRecords();
        });
        $('#btnWrfAmend').on('click', function () {
            if ($('#btnWrfAmend').data('ready') !== 1) {
                $('#formWrf').find('input, select, textarea').prop('disabled', false);
                $('#txtWrfRef, #txtWrfStatus, #txtWrfRecorded, #txtWrfQtyKg, #txtWrfBalBefore, #txtWrfBalAfter, #txtWrfAlias, #txtWrfAddress, #txtWrfPhone').prop('disabled', true);
                $('#btnWrfAmend').data('ready', 1).html('<i class="fas fa-save me-2"></i>Save Amendment');
                toastr['info']('Update the fields, then save the amendment.', 'Amend Final');
                return;
            }
            askReason('amend', function (reason) {
                ShowLoader();
                try {
                    const body = payload();
                    body.reason = reason;
                    applyRecord(wc.api('transaction/' + $('#hidWrfId').val() + '/amend', 'POST', body));
                    $('#btnWrfAmend').data('ready', 0).html('<i class="fas fa-pen me-2"></i>Amend Final');
                } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
                HideLoader();
            });
        });
        $('#btnWrfAddDoc').on('click', function () {
            const input = document.getElementById('txfWrfFile');
            if (input && input.files && input.files[0]) { attachFile(input.files[0]); }
        });
        $(document).on('click', '.lnkDocOpen', function (e) { e.preventDefault(); wc.openUpload($(this).data('id')); });
        $(document).on('click', '.lnkDocDel', function (e) {
            e.preventDefault();
            ShowLoader();
            try { applyRecord(mzAjaxRequest3('waste/transaction/' + $('#hidWrfId').val() + '/document/' + $(this).data('id'), 'DELETE', {})); }
            catch (err) { toastr['error'](err.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
        $(document).on('click', '.lnkPendDel', function (e) { e.preventDefault(); pendingDocs.splice(Number($(this).data('i')), 1); renderDocs(current ? current.documents : []); });
    };
}
