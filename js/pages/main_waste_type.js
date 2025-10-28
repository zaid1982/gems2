function MainWasteType () {
    const className = 'MainWasteType';
    let self = this;
    let dtWtt;
    let modalWasteType;
    let statusFilter = 'all';
    let rowsCache = [];
    let searchTimer;

    const badge = function (statusKey) {
        if (statusKey === 'active') {
            return '<span class="badge-status active"><i class="fas fa-check-circle"></i>Active</span>';
        }
        return '<span class="badge-status inactive"><i class="fas fa-ban"></i>Inactive</span>';
    };

    const fmtDate = function (dateStr) {
        if (!dateStr) { return '-'; }
        if (typeof moment === 'function') {
            const m = moment(dateStr, ['YYYY-MM-DD', moment.ISO_8601], true);
            if (m.isValid()) {
                return m.format('DD MMM YYYY');
            }
        }
        const parsed = new Date(dateStr);
        if (!isNaN(parsed.getTime())) {
            return parsed.toLocaleDateString();
        }
        return dateStr;
    };

    const isRecent = function (dateStr) {
        if (!dateStr) { return false; }
        if (typeof moment === 'function') {
            const m = moment(dateStr, ['YYYY-MM-DD', moment.ISO_8601], true);
            if (m.isValid()) {
                return moment().diff(m, 'days') <= 30;
            }
        }
        const parsed = new Date(dateStr);
        if (isNaN(parsed.getTime())) { return false; }
        const diff = (Date.now() - parsed.getTime()) / (1000 * 60 * 60 * 24);
        return diff <= 30;
    };

    const buildRows = function () {
        return [
            { wasteType: 'Hazardous Waste - Type A', category: 'Hazardous', createdOn: '2024-08-22', statusKey: 'active' },
            { wasteType: 'Clinical Waste - Type B', category: 'Clinical', createdOn: '2024-07-15', statusKey: 'active' },
            { wasteType: 'General Waste - Paper', category: 'General', createdOn: '2024-05-02', statusKey: 'inactive' },
            { wasteType: 'Recyclable Plastics', category: 'Recyclable', createdOn: '2024-08-05', statusKey: 'active' },
            { wasteType: 'Chemical Waste - Lab', category: 'Chemical', createdOn: '2024-06-09', statusKey: 'inactive' }
        ].map(function (row, idx) {
            return $.extend({}, row, {
                id: idx + 1,
                actions: '<a class="text-primary lnkWttEdit mr-2" data-toggle="tooltip" title="Edit"><i class="fas fa-pen-to-square"></i></a>' +
                    '<a class="text-danger lnkWttDelete" data-toggle="tooltip" title="Delete"><i class="fas fa-trash-alt"></i></a>'
            });
        });
    };

    const updateMetrics = function () {
        if (!dtWtt) { return; }
        const rows = dtWtt.rows({ search: 'applied' }).data().toArray();
        const total = rows.length;
        const active = rows.filter(function (row) { return row.statusKey === 'active'; }).length;
        const inactive = rows.filter(function (row) { return row.statusKey === 'inactive'; }).length;
        const recent = rows.filter(function (row) { return isRecent(row.createdOn); }).length;

        $('#metricWttTotal').text(total);
        $('#metricWttActive').text(active);
        $('#metricWttInactive').text(inactive);
        $('#metricWttNew').text(recent);
    };

    const updateSummary = function () {
        if (!dtWtt) { return; }
    const info = dtWtt.page.info();
        if (!info) { return; }
        const showing = info.recordsDisplay;
        const total = info.recordsTotal;
        $('#lblWttCount').text(showing === 0 ? 'No waste types match the filters' : 'Showing ' + showing + ' of ' + total + ' type' + (total === 1 ? '' : 's'));
    };

    const updateStatusSummary = function () {
        const label = $('#lblWttSummary span');
        if (!label.length) { return; }
        var text = 'Status: ';
        if (statusFilter === 'active') {
            text += 'Active only';
        } else if (statusFilter === 'inactive') {
            text += 'Inactive only';
        } else {
            text += 'All types';
        }
        label.text(text);
    };

    const updateLastRefreshed = function () {
        const label = $('#lblWttUpdated strong');
        if (!label.length) { return; }
        var stamp = new Date().toLocaleString();
        if (typeof moment === 'function') {
            stamp = moment().format('DD MMM YYYY, h:mm A');
        }
        label.text(stamp);
    };

    const bindRowActions = function () {
        const tbody = $('#dtWtt tbody');
        tbody.off('click', '.lnkWttEdit').on('click', '.lnkWttEdit', function (e) {
            e.preventDefault();
            if (modalWasteType && typeof modalWasteType.add === 'function') {
                modalWasteType.add();
            }
        });
        tbody.off('click', '.lnkWttDelete').on('click', '.lnkWttDelete', function (e) {
            e.preventDefault();
            toastr['info']('Delete handling is not wired yet.', 'Coming Soon');
        });
    };

    const initTable = function () {
        dtWtt = $('#dtWtt').DataTable({
            data: rowsCache,
            bLengthChange: false,
            searching: true,
            order: [[1, 'asc']],
            language: _DATATABLE_LANGUAGE,
            autoWidth: false,
            pageLength: 10,
            dom: "<'row align-items-center mb-2'<'col-sm-12 col-lg-5 px-0 pb-2'B>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row align-items-center'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>",
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'three-column', text: '<i class=\"fas fa-columns\"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility' },
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFctObserveHide', text: '<i class=\"fas fa-print\"></i>', title: 'GEMS - Waste Type List', titleAttr: 'Print', exportOptions: mzExportOpt },
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFctObserveHide', text: '<i class=\"fas fa-copy\"></i>', title: 'GEMS - Waste Type List', titleAttr: 'Copy', exportOptions: mzExportOpt },
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnFctObserveHide', text: '<i class=\"fas fa-file-excel\"></i>', title: 'GEMS - Waste Type List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt },
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-3 btnFctObserveHide', text: '<i class=\"fas fa-file-pdf\"></i>', title: 'GEMS - Waste Type List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt }
            ],
            columnDefs: [
                { bSortable: false, targets: [0, 5] },
                { className: 'text-center', targets: [0, 3, 4, 5] },
                { className: 'noVis', targets: [0] }
            ],
            columns: [
                { data: null },
                { data: 'wasteType' },
                { data: 'category' },
                {
                    data: 'createdOn', render: function (data, type) {
                        if (type === 'display' || type === 'filter') {
                            return fmtDate(data);
                        }
                        return data;
                    }
                },
                { data: 'statusKey', render: function (data) { return badge(data); }, orderable: false },
                { data: 'actions', orderable: false, searchable: false }
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const start = typeof this._iDisplayStart === 'number' ? this._iDisplayStart : 0;
                $('td', nRow).eq(0).html(start + iDisplayIndex + 1);
                const labels = ['#', 'Waste Type', 'Category', 'Date Created', 'Status', 'Actions'];
                $('td', nRow).each(function (idx) {
                    $(this).attr('data-label', labels[idx]);
                });
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip({ container: 'body' });
                bindRowActions();
            }
        });

        dtWtt.on('draw', function () {
            updateMetrics();
            updateSummary();
        });
    };

    const bindControls = function () {
        $('#btnWttAddWaste').off('click').on('click', function () {
            if (modalWasteType && typeof modalWasteType.add === 'function') {
                modalWasteType.add();
            }
        });

        $('#btnWttRefresh').off('click').on('click', function () {
            $('#txtWttSearch').val('');
            statusFilter = 'all';
            $('#optWttStatus').val('all');
            updateStatusSummary();
            dtWtt.search('');
            dtWtt.draw();
            updateLastRefreshed();
        });

        $('#txtWttSearch').off('input').on('input', function () {
            const term = this.value || '';
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                dtWtt.search(term).draw();
            }, 180);
        });

        $('#optWttStatus').off('change').on('change', function () {
            statusFilter = this.value || 'all';
            updateStatusSummary();
            dtWtt.draw();
        });
    };

    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!dtWtt || settings.nTable !== dtWtt.table().node()) {
            return true;
        }
        const rowData = settings.aoData[dataIndex] ? settings.aoData[dataIndex]._aData : null;
        if (!rowData) { return true; }
        if (statusFilter !== 'all' && rowData.statusKey !== statusFilter) {
            return false;
        }
        return true;
    });

    this.init = function () {
        rowsCache = buildRows();
        initTable();
        bindControls();
        updateStatusSummary();
        updateMetrics();
        updateSummary();
        updateLastRefreshed();
    };

    this.setModalWasteType = function (_modalWasteType) {
        modalWasteType = _modalWasteType;
    };
}
