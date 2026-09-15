function MainWasteSetup() {
    const wc = new WasteCommon();
    let tab = 'premise';
    let dtP, dtF, dtL, dtR;

    const actions = function (kind, id) {
        return '<a class="text-primary lnkEdit mr-2" data-kind="' + kind + '" data-id="' + id + '" title="Edit"><i class="fas fa-pen-to-square"></i></a>';
    };

    const initTables = function () {
        const common = { bLengthChange: false, pageLength: 10, autoWidth: false, language: _DATATABLE_LANGUAGE, dom: "<'row'<'col-sm-12'tr>><'row'<'col-sm-6'i><'col-sm-6'p>>" };
        dtP = $('#dtPremise').DataTable($.extend(true, {}, common, { columns: [
            { data: null }, { data: 'siteName' }, { data: 'siteCode' }, { data: 'premiseContactNo' },
            { data: 'cutoverDate', render: wc.fmtDate },
            { data: null, render: function (r) { return (Number(r.evidenceRequiredProduced) ? 'P ' : '') + (Number(r.evidenceRequiredDisposed) ? 'D' : '') || '-'; } },
            { data: null, orderable: false, render: function (r) { return actions('premise', r.siteId); } }
        ], fnRowCallback: function (n, d, i) { $('td', n).eq(0).html(i + 1); } }));
        dtF = $('#dtProfile').DataTable($.extend(true, {}, common, { columns: [
            { data: null }, { data: 'swCode' }, { data: 'swDescription' }, { data: 'profileAlias' },
            { data: 'profileStatus', render: function (s) { return Number(s) === 1 ? 'Active' : 'Inactive'; } },
            { data: null, orderable: false, render: function (r) { return actions('profile', r.profileId); } }
        ], fnRowCallback: function (n, d, i) { $('td', n).eq(0).html(i + 1); } }));
        dtL = $('#dtLocation').DataTable($.extend(true, {}, common, { columns: [
            { data: null }, { data: 'locationName' }, { data: 'locationType' },
            { data: 'locationStatus', render: function (s) { return Number(s) === 1 ? 'Active' : 'Inactive'; } },
            { data: null, orderable: false, render: function (r) { return actions('location', r.locationId); } }
        ], fnRowCallback: function (n, d, i) { $('td', n).eq(0).html(i + 1); } }));
        dtR = $('#dtRef').DataTable($.extend(true, {}, common, { columns: [
            { data: null }, { data: 'valueType' }, { data: 'valueName' },
            { data: 'siteId', render: function (v) { return v ? 'Premise' : 'Global'; } },
            { data: 'valueStatus', render: function (s) { return Number(s) === 1 ? 'Active' : 'Inactive'; } },
            { data: null, orderable: false, render: function (r) { return actions('ref', r.refValueId); } }
        ], fnRowCallback: function (n, d, i) { $('td', n).eq(0).html(i + 1); } }));
    };

    const reload = function () {
        wc.loadCaps();
        wc.loadLookups(wc.caps.siteId, true);
        dtP.clear().rows.add(wc.sites).draw();
        dtF.clear().rows.add(wc.apiGet('profile' + (wc.caps.siteId ? ('?siteId=' + wc.caps.siteId) : '')) || []).draw();
        dtL.clear().rows.add(wc.locations).draw();
        dtR.clear().rows.add(wc.refValues).draw();
        if (!wc.caps.canSetup) { $('#btnWstAdd').hide(); }
        wc.fillSelect('optWpfSite', wc.sites, 'siteId', function (r) { return r.siteName; }, 'Select premise', wc.caps.siteId);
        wc.fillSelect('optWlcSite', wc.sites, 'siteId', function (r) { return r.siteName; }, 'Select premise', wc.caps.siteId);
        wc.fillSelect('optWrvSite', wc.sites, 'siteId', function (r) { return r.siteName; }, 'Global / all', '');
        wc.fillSelect('optWpfSw', wc.swCodes, 'swCodeId', function (r) { return r.swCode + ' — ' + r.swDescription; }, 'Select SW code');
    };

    const openPremise = function (siteId) {
        const row = (wc.sites || []).find(function (s) { return Number(s.siteId) === Number(siteId); }) || {};
        $('#hidWprSiteId').val(siteId);
        $('#lblWprName').text((row.siteName || '') + ' (' + (row.siteCode || '') + ')');
        $('#txaWprAddress').val(row.premiseAddress || '');
        $('#txtWprContact').val(row.premiseContactNo || '');
        $('#txtWprCutover').val(row.cutoverDate || '');
        $('#chkWprEvP').prop('checked', Number(row.evidenceRequiredProduced) === 1);
        $('#chkWprEvD').prop('checked', Number(row.evidenceRequiredDisposed) === 1);
        $('#modal_waste_premise').modal({ backdrop: 'static' });
    };

    this.init = function () {
        initTables();
        reload();
        $('#wstSetupTabs a').on('shown.bs.tab', function (e) { tab = $(e.target).data('tab'); });
        $('#btnWstRefresh').on('click', reload);
        $('#btnWstAdd').on('click', function () {
            if (tab === 'profile') {
                $('#hidWpfId').val(''); $('#txtWpfAlias').val(''); $('#optWpfStatus').val('1'); $('#lblWpfDesc').text('-');
                $('#modal_waste_profile').modal({ backdrop: 'static' });
            } else if (tab === 'location') {
                $('#hidWlcId').val(''); $('#txtWlcName').val(''); $('#optWlcType').val('STORAGE'); $('#optWlcStatus').val('1');
                $('#modal_waste_location').modal({ backdrop: 'static' });
            } else if (tab === 'ref') {
                $('#hidWrvId').val(''); $('#txtWrvName').val(''); $('#optWrvStatus').val('1');
                $('#modal_waste_ref_value').modal({ backdrop: 'static' });
            } else {
                const first = wc.sites[0];
                if (first) { openPremise(first.siteId); }
            }
        });
        $(document).on('click', '.lnkEdit', function () {
            const kind = $(this).data('kind'); const id = $(this).data('id');
            if (kind === 'premise') { openPremise(id); }
            if (kind === 'profile') {
                const row = dtF.rows().data().toArray().find(function (r) { return Number(r.profileId) === Number(id); });
                $('#hidWpfId').val(id); $('#optWpfSite').val(row.siteId); $('#optWpfSw').val(row.swCodeId);
                $('#txtWpfAlias').val(row.profileAlias || ''); $('#optWpfStatus').val(row.profileStatus);
                $('#lblWpfDesc').text(row.swDescription); $('#modal_waste_profile').modal({ backdrop: 'static' });
            }
            if (kind === 'location') {
                const row = dtL.rows().data().toArray().find(function (r) { return Number(r.locationId) === Number(id); });
                $('#hidWlcId').val(id); $('#optWlcSite').val(row.siteId); $('#txtWlcName').val(row.locationName);
                $('#optWlcType').val(row.locationType); $('#optWlcStatus').val(row.locationStatus);
                $('#modal_waste_location').modal({ backdrop: 'static' });
            }
            if (kind === 'ref') {
                const row = dtR.rows().data().toArray().find(function (r) { return Number(r.refValueId) === Number(id); });
                $('#hidWrvId').val(id); $('#optWrvType').val(row.valueType); $('#txtWrvName').val(row.valueName);
                $('#optWrvSite').val(row.siteId || ''); $('#optWrvStatus').val(row.valueStatus);
                $('#modal_waste_ref_value').modal({ backdrop: 'static' });
            }
        });
        $('#optWpfSw').on('change', function () {
            const row = wc.swCodes.find(function (r) { return String(r.swCodeId) === String($('#optWpfSw').val()); });
            $('#lblWpfDesc').text(row ? row.swDescription : '-');
        });
        $(document).on('click', '#btnWprSave', function () {
            ShowLoader();
            try {
                wc.api('premise', 'POST', {
                    siteId: $('#hidWprSiteId').val(), premiseAddress: $('#txaWprAddress').val(),
                    premiseContactNo: $('#txtWprContact').val(), cutoverDate: $('#txtWprCutover').val(),
                    evidenceRequiredProduced: $('#chkWprEvP').is(':checked') ? 1 : 0,
                    evidenceRequiredDisposed: $('#chkWprEvD').is(':checked') ? 1 : 0
                });
                $('#modal_waste_premise').modal('hide'); reload();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
        $(document).on('click', '#btnWpfSave', function () {
            ShowLoader();
            try {
                const id = $('#hidWpfId').val();
                const payload = { siteId: $('#optWpfSite').val(), swCodeId: $('#optWpfSw').val(), profileAlias: $('#txtWpfAlias').val(), profileStatus: $('#optWpfStatus').val() };
                if (id) { wc.api('profile/' + id, 'PUT', payload); } else { wc.api('profile', 'POST', payload); }
                $('#modal_waste_profile').modal('hide'); reload();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
        $(document).on('click', '#btnWlcSave', function () {
            ShowLoader();
            try {
                const id = $('#hidWlcId').val();
                const payload = { siteId: $('#optWlcSite').val(), locationName: $('#txtWlcName').val(), locationType: $('#optWlcType').val(), locationStatus: $('#optWlcStatus').val() };
                if (id) { wc.api('location/' + id, 'PUT', payload); } else { wc.api('location', 'POST', payload); }
                $('#modal_waste_location').modal('hide'); reload();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
        $(document).on('click', '#btnWrvSave', function () {
            ShowLoader();
            try {
                const id = $('#hidWrvId').val();
                const payload = { valueType: $('#optWrvType').val(), valueName: $('#txtWrvName').val(), siteId: $('#optWrvSite').val(), valueStatus: $('#optWrvStatus').val() };
                if (id) { wc.api('ref_value/' + id, 'PUT', payload); } else { wc.api('ref_value', 'POST', payload); }
                $('#modal_waste_ref_value').modal('hide'); reload();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
    };
}
