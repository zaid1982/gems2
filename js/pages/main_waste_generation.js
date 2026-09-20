function MainWasteGeneration() {
    const wc = new WasteCommon();
    let dt;

    const wasteTypeLabel = function (row) {
        const alias = row.profileAlias ? ' — ' + row.profileAlias : '';
        return row.swCode + alias;
    };

    const fillWasteTypes = function (siteId, selected) {
        wc.loadLookups(siteId, true);
        // Prefer the premise's own approved profiles; fall back to all active SW codes.
        const profiled = (wc.swCodes || []).filter(function (r) {
            return r.profileId && Number(r.profileStatus) === 1 && Number(r.swStatus) === 1;
        });
        const rows = profiled.length ? profiled : (wc.swCodes || []).filter(function (r) { return Number(r.swStatus) === 1; });
        wc.fillSelect('optWgnSw', rows, 'swCodeId', wasteTypeLabel, 'Select waste type', selected || '');
        showSwDesc();
    };

    const showSwDesc = function () {
        const id = $('#optWgnSw').val();
        const row = (wc.swCodes || []).find(function (r) { return String(r.swCodeId) === String(id); });
        if (row && row.swDescription) {
            $('#lblWgnSwDesc').removeClass('d-none').text(row.swDescription);
        } else {
            $('#lblWgnSwDesc').addClass('d-none').text('');
        }
    };

    const reloadRecent = function () {
        const siteId = $('#optWgnSite').val();
        const rows = wc.apiGet('generation' + (siteId ? ('?siteId=' + siteId) : '')) || [];
        dt.clear().rows.add(rows.slice(0, 10)).draw();
    };

    const clearForm = function () {
        $('#txtWgnQty').val('');
        $('#txtWgnRemarks').val('');
        $('#txtWgnDate').val(moment().format('YYYY-MM-DD'));
        $('#optWgnSw').val('');
        showSwDesc();
    };

    const save = function () {
        const siteId = $('#optWgnSite').val();
        const swCodeId = $('#optWgnSw').val();
        const qtyKg = Number($('#txtWgnQty').val());
        const eventDate = $('#txtWgnDate').val();
        if (!siteId) { toastr['warning']('Select a premise.', _ALERT_TITLE_WARNING); return; }
        if (!swCodeId) { toastr['warning']('Select a waste type.', _ALERT_TITLE_WARNING); return; }
        if (!Number.isFinite(qtyKg) || qtyKg <= 0) { toastr['warning']('Enter a waste weight greater than zero.', _ALERT_TITLE_WARNING); return; }
        if (!eventDate) { toastr['warning']('Enter the generation date.', _ALERT_TITLE_WARNING); return; }
        if (eventDate > moment().format('YYYY-MM-DD')) { toastr['warning']('The generation date cannot be in the future.', _ALERT_TITLE_WARNING); return; }

        ShowLoader();
        try {
            wc.api('generation', 'POST', {
                siteId: siteId,
                swCodeId: swCodeId,
                qtyKg: qtyKg,
                eventDate: eventDate,
                remarks: $('#txtWgnRemarks').val() || ''
            });
            clearForm();
            reloadRecent();
        } catch (e) {
            toastr['error'](e.message, _ALERT_TITLE_ERROR);
        }
        HideLoader();
    };

    this.init = function () {
        wc.loadCaps();
        if (!wc.caps.canGenerate) {
            $('#btnWgnSave').prop('disabled', true);
            toastr['warning']('You are not allowed to record waste.', _ALERT_TITLE_WARNING);
        }
        wc.loadLookups(wc.caps.siteId);
        wc.fillSelect('optWgnSite', wc.sites, 'siteId', function (r) { return r.siteName; }, 'Select premise', wc.caps.siteId || '');
        if (!wc.caps.isAdmin) { $('#optWgnSite').prop('disabled', true); }
        if (!$('#optWgnSite').val() && (wc.sites || []).length === 1) {
            $('#optWgnSite').val(wc.sites[0].siteId);
        }
        fillWasteTypes($('#optWgnSite').val());
        $('#txtWgnDate').val(moment().format('YYYY-MM-DD'));
        $('#txtWgnDate').attr('max', moment().format('YYYY-MM-DD'));

        dt = $('#dtWgnRecent').DataTable({
            data: [], bLengthChange: false, pageLength: 10, autoWidth: false,
            language: wc.dtEmpty('fa-dumpster', 'No generation records yet.'),
            searching: false, ordering: false, paging: false, info: false,
            dom: "<'table-responsive't>",
            columns: [
                { data: null },
                { data: 'txnRef' },
                { data: 'eventDate', render: wc.fmtDate },
                { data: null, render: function (r) { return r.swCode + ' — ' + (r.swDescription || ''); } },
                { data: 'registeredKg', className: 'gems-num', render: wc.fmtQty },
                { data: 'collectionStatus', render: wc.collectionBadge }
            ],
            fnRowCallback: function (n, d, i) { $('td', n).eq(0).html(this._iDisplayStart + i + 1); }
        });
        reloadRecent();

        $('#optWgnSite').off('change').on('change', function () {
            fillWasteTypes($(this).val());
            reloadRecent();
        });
        $('#optWgnSw').off('change').on('change', showSwDesc);
        $('#btnWgnSave').off('click').on('click', save);
        $('#btnWgnReset').off('click').on('click', clearForm);
    };
}
