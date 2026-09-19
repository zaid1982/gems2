function MainKpaAssignment() {
    const kc = new KpaCommon();
    let dt;

    const siteId = function () {
        return $('#optKasSite').val() || '';
    };

    const loadPiOptions = function () {
        const rows = (kc.apiGet('pi?activeOnly=1' + (siteId() ? ('&siteId=' + siteId()) : '')) || []);
        kc.fillSelect('optKasPi', rows, 'piId', function (r) {
            return r.piNo + ' — ' + r.piName;
        }, 'Select a Performance Indicator');
    };

    const loadUserOptions = function () {
        const rows = kc.apiGet('assignment/users' + (siteId() ? ('?siteId=' + siteId()) : '')) || [];
        kc.fillSelect('optKasUsers', rows, 'userId', kc.userLabel, null);
        $('#divKasNoUsers').toggleClass('d-none', rows.length > 0);
        $('#btnKasAssign').prop('disabled', rows.length === 0 || !kc.caps.canAdmin);
    };

    const reload = function () {
        dt.clear().rows.add(kc.apiGet('assignment' + (siteId() ? ('?siteId=' + siteId()) : '')) || []).draw();
    };

    const assign = function () {
        const piId = $('#optKasPi').val();
        const userIds = $('#optKasUsers').val() || [];
        if (!piId) { toastr['warning']('Select a Performance Indicator.', _ALERT_TITLE_WARNING); return; }
        if (!userIds.length) { toastr['warning']('Select at least one user.', _ALERT_TITLE_WARNING); return; }
        ShowLoader();
        try {
            dt.clear().rows.add(kc.api('assignment', 'POST', {
                siteId: siteId(),
                piId: piId,
                userIds: userIds
            }) || []).draw();
            $('#optKasUsers').val([]);
        } catch (e) {
            toastr['error'](e.message, _ALERT_TITLE_ERROR);
        }
        HideLoader();
    };

    this.init = function () {
        kc.loadCaps();
        if (!kc.caps.canAdmin) {
            toastr['warning']('Only a KPI Admin can change PI assignments.', _ALERT_TITLE_WARNING);
        }
        kc.loadSites();
        kc.fillSelect('optKasSite', kc.sites, 'siteId', function (r) { return r.siteName; }, null, kc.caps.siteId || '');

        dt = $('#dtKas').DataTable({
            data: [], bLengthChange: false, pageLength: 25, autoWidth: false,
            language: _DATATABLE_LANGUAGE, ordering: false, dom: "t<'row'<'col-sm-6'i><'col-sm-6'p>>",
            columns: [
                { data: null },
                { data: null, render: function (r) { return r.groupNo + ' — ' + r.groupName; } },
                { data: 'piNo' },
                { data: 'piName' },
                { data: null, render: kc.userLabel },
                { data: null, orderable: false, className: 'noVis', render: function (r) {
                    if (!kc.caps.canAdmin) { return ''; }
                    return '<a href="#" class="text-danger lnkKasDel" data-id="' + r.assignId + '" title="Remove"><i class="fas fa-trash"></i></a>';
                } }
            ],
            fnRowCallback: function (n, d, i) { $('td', n).eq(0).html(i + 1); }
        });

        loadPiOptions();
        loadUserOptions();
        reload();

        $('#optKasSite').off('change').on('change', function () {
            loadPiOptions();
            loadUserOptions();
            reload();
        });
        $('#btnKasRefresh').off('click').on('click', reload);
        $('#btnKasAssign').off('click').on('click', assign);
        $(document).off('click', '.lnkKasDel').on('click', '.lnkKasDel', function (e) {
            e.preventDefault();
            if (!window.confirm('Remove this PI assignment?')) { return; }
            ShowLoader();
            try { dt.clear().rows.add(kc.api('assignment/' + $(this).data('id'), 'DELETE') || []).draw(); }
            catch (err) { toastr['error'](err.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
    };
}
