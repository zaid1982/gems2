function MainWasteMain () {
    const className = 'MainWasteMain';
    let self = this;
    let dtWtm;
    let modalWasteAdd;
    let statusFilter = 'all';
    let yearFilter = 'all';
    let monthFilter = 'all';
    let rowsCache = [];
    let searchTimer;

    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    const badge = function (statusKey) {
        if (statusKey === 'completed') {
            return '<span class="badge-status completed"><i class="fas fa-check-circle"></i>Completed</span>';
        }
        if (statusKey === 'in-progress') {
            return '<span class="badge-status in-progress"><i class="fas fa-hourglass-half"></i>In Progress</span>';
        }
        return '<span class="badge-status incomplete"><i class="fas fa-exclamation-triangle"></i>Overdue</span>';
    };

    const fmtDate = function (dateStr) {
        if (!dateStr) { return '-'; }
        if (typeof moment === 'function') {
            const m = moment(dateStr, ['YYYY-MM-DD', moment.ISO_8601], true);
            if (m.isValid()) {
                return m.format('DD MMM YYYY');
            }
        }
        return dateStr;
    };

    const buildRows = function () {
        return [
            {
                scheduledDate: '2024-09-03',
                dueDate: '2024-09-15',
                collectedDate: null,
                wasteType: 'Hazardous Waste - Type A',
                pic: 'Siti Aisyah',
                weightage: '—',
                statusKey: 'incomplete'
            },
            {
                scheduledDate: '2024-08-18',
                dueDate: '2024-08-25',
                collectedDate: '2024-08-26',
                wasteType: 'Clinical Waste - Type B',
                pic: 'Junaidah Harun',
                weightage: '70 kg',
                statusKey: 'completed'
            },
            {
                scheduledDate: '2024-08-10',
                dueDate: '2024-08-18',
                collectedDate: '2024-08-19',
                wasteType: 'General Waste - Type C',
                pic: 'Hassan Ishak',
                weightage: '55 kg',
                statusKey: 'completed'
            },
            {
                scheduledDate: '2024-08-01',
                dueDate: '2024-08-12',
                collectedDate: null,
                wasteType: 'Hazardous Waste - Type C',
                pic: 'Ismail Hambal',
                weightage: '—',
                statusKey: 'incomplete'
            },
            {
                scheduledDate: '2024-07-27',
                dueDate: '2024-08-05',
                collectedDate: null,
                wasteType: 'Recyclable - Paper',
                pic: 'Nur Afiqah',
                weightage: '120 kg',
                statusKey: 'in-progress'
            },
            {
                scheduledDate: '2024-06-15',
                dueDate: '2024-06-25',
                collectedDate: '2024-06-24',
                wasteType: 'Chemical Waste - Lab',
                pic: 'Samuel Khor',
                weightage: '42 kg',
                statusKey: 'completed'
            }
        ].map(function (row, idx) {
            const scheduledMoment = typeof moment === 'function' ? moment(row.scheduledDate, 'YYYY-MM-DD', true) : null;
            const year = scheduledMoment && scheduledMoment.isValid() ? scheduledMoment.year() : new Date(row.scheduledDate).getFullYear();
            const month = scheduledMoment && scheduledMoment.isValid() ? scheduledMoment.month() + 1 : (new Date(row.scheduledDate).getMonth() + 1);
            return $.extend({}, row, {
                id: idx + 1,
                scheduledYear: year,
                scheduledMonth: month,
                actions: '<a class="text-primary lnkWtmEdit mr-2" data-toggle="tooltip" title="Edit"><i class="fas fa-pen-to-square"></i></a>' +
                    '<a class="text-danger lnkWtmDelete" data-toggle="tooltip" title="Delete"><i class="fas fa-trash-alt"></i></a>'
            });
        });
    };

    const populateYearOptions = function () {
        const select = $('#optWtmYear');
        const years = rowsCache.map(function (row) { return row.scheduledYear; });
        const uniqueYears = years.filter(function (value, index, self) { return self.indexOf(value) === index; }).sort(function (a, b) { return b - a; });
        select.find('option:not([value="all"])').remove();
        uniqueYears.forEach(function (year) {
            select.append('<option value="' + year + '">' + year + '</option>');
        });
    };

    const updateMetrics = function () {
        if (!dtWtm) { return; }
        const rows = dtWtm.rows({ search: 'applied' }).data().toArray();
        const total = rows.length;
        const completed = rows.filter(function (row) { return row.statusKey === 'completed'; }).length;
        const inProgress = rows.filter(function (row) { return row.statusKey === 'in-progress'; }).length;
        const overdue = rows.filter(function (row) { return row.statusKey === 'incomplete'; }).length;

        $('#metricWtmTotal').text(total);
        $('#metricWtmCompleted').text(completed);
        $('#metricWtmInProgress').text(inProgress);
        $('#metricWtmOverdue').text(overdue);
    };

    const updateSummary = function () {
        if (!dtWtm) { return; }
        const info = dtWtm.page.info();
        if (!info) { return; }
        const showing = info.recordsDisplay;
        const total = info.recordsTotal;
        $('#lblWtmCount').text(showing === 0 ? 'No schedules match the current filters' : 'Showing ' + showing + ' of ' + total + ' schedule' + (total === 1 ? '' : 's'));
    };

    const updateLastRefreshed = function () {
        const label = $('#lblWtmUpdated strong');
        if (!label.length) { return; }
        var stamp = new Date().toLocaleString();
        if (typeof moment === 'function') {
            stamp = moment().format('DD MMM YYYY, h:mm A');
        }
        label.text(stamp);
    };

    const updateSelectedPeriod = function () {
        var yearText = yearFilter === 'all' ? 'All years' : yearFilter;
        var monthText;
        if (monthFilter === 'all') {
            monthText = 'All months';
        } else {
            var idx = parseInt(monthFilter, 10) - 1;
            monthText = idx >= 0 && idx < monthNames.length ? monthNames[idx] : monthFilter;
        }
        $('#lblWtmSelected span').text(monthText + ' · ' + yearText);
    };

    const bindActionButtons = function () {
        const tbody = $('#dtWtm tbody');
        tbody.off('click', '.lnkWtmEdit').on('click', '.lnkWtmEdit', function (e) {
            e.preventDefault();
            if (modalWasteAdd && typeof modalWasteAdd.add === 'function') {
                modalWasteAdd.add();
            }
        });
        tbody.off('click', '.lnkWtmDelete').on('click', '.lnkWtmDelete', function (e) {
            e.preventDefault();
            toastr['info']('Delete action is not yet wired.', 'Coming Soon');
        });
    };

    const initTable = function () {
        dtWtm = $('#dtWtm').DataTable({
            data: rowsCache,
            bLengthChange: false,
            searching: true,
            order: [[1, 'desc']],
            language: _DATATABLE_LANGUAGE,
            autoWidth: false,
            pageLength: 10,
            dom: "<'row align-items-center mb-2'<'col-sm-12 col-lg-5 px-0 pb-2'B>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row align-items-center'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>",
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'three-column', text: '<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility' },
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFctObserveHide', text: '<i class="fas fa-print"></i>', title: 'GEMS - Waste Collection List', titleAttr: 'Print', exportOptions: mzExportOpt },
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFctObserveHide', text: '<i class="fas fa-copy"></i>', title: 'GEMS - Waste Collection List', titleAttr: 'Copy', exportOptions: mzExportOpt },
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnFctObserveHide', text: '<i class="fas fa-file-excel"></i>', title: 'GEMS - Waste Collection List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt },
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-3 btnFctObserveHide', text: '<i class="fas fa-file-pdf"></i>', title: 'GEMS - Waste Collection List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt }
            ],
            columnDefs: [
                { bSortable: false, targets: [0, 5, 6, 7, 8] },
                { className: 'text-center', targets: [0, 3, 5, 6, 7] },
                { className: 'text-right', targets: [8] },
                { className: 'noVis', targets: [0, 8] }
            ],
            columns: [
                { data: null },
                {
                    data: 'scheduledDate', render: function (data, type) {
                        if (type === 'display' || type === 'filter') {
                            return fmtDate(data);
                        }
                        return data;
                    }
                },
                { data: 'wasteType' },
                {
                    data: 'dueDate', render: function (data, type) {
                        if (type === 'display' || type === 'filter') {
                            return fmtDate(data);
                        }
                        return data;
                    }
                },
                { data: 'pic' },
                { data: 'weightage', render: function (data) { return data && data !== '' ? data : '—'; } },
                {
                    data: 'collectedDate', render: function (data, type) {
                        if (!data) { return type === 'display' ? '<span class="text-muted">Pending</span>' : ''; }
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

                const labels = ['#', 'Scheduled Date', 'Waste Type', 'Due Collection', 'PIC', 'Weightage', 'Date Collected', 'Status', 'Actions'];
                $('td', nRow).each(function (idx) {
                    $(this).attr('data-label', labels[idx]);
                });
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip({ container: 'body' });
                bindActionButtons();
            }
        });

        dtWtm.on('draw', function () {
            updateMetrics();
            updateSummary();
        });
    };

    const bindControls = function () {
        $('#btnWtmAddWaste').off('click').on('click', function () {
            if (modalWasteAdd && typeof modalWasteAdd.add === 'function') {
                modalWasteAdd.add();
            }
        });

        $('#btnWtmRefresh').off('click').on('click', function () {
            $('#txtWtmSearch').val('');
            statusFilter = 'all';
            yearFilter = 'all';
            monthFilter = 'all';
            $('#optWtmStatus').val('all');
            $('#optWtmYear').val('all');
            $('#optWtmMonth').val('all');
            updateSelectedPeriod();
            dtWtm.search('');
            dtWtm.draw();
            updateLastRefreshed();
        });

        $('#txtWtmSearch').off('input').on('input', function () {
            const term = this.value || '';
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                dtWtm.search(term).draw();
            }, 180);
        });

        $('#optWtmStatus').off('change').on('change', function () {
            statusFilter = this.value || 'all';
            dtWtm.draw();
        });

        $('#optWtmYear').off('change').on('change', function () {
            yearFilter = this.value || 'all';
            updateSelectedPeriod();
            dtWtm.draw();
        });

        $('#optWtmMonth').off('change').on('change', function () {
            monthFilter = this.value || 'all';
            updateSelectedPeriod();
            dtWtm.draw();
        });
    };

    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!dtWtm || settings.nTable !== dtWtm.table().node()) {
            return true;
        }
        const rowData = settings.aoData[dataIndex] ? settings.aoData[dataIndex]._aData : null;
        if (!rowData) { return true; }

        if (statusFilter !== 'all' && rowData.statusKey !== statusFilter) {
            return false;
        }

        if (yearFilter !== 'all' && String(rowData.scheduledYear) !== String(yearFilter)) {
            return false;
        }

        if (monthFilter !== 'all' && parseInt(monthFilter, 10) !== rowData.scheduledMonth) {
            return false;
        }

        return true;
    });

    this.init = function () {
        rowsCache = buildRows();
        populateYearOptions();
        initTable();
        bindControls();
        updateSelectedPeriod();
        updateMetrics();
        updateSummary();
        updateLastRefreshed();
    };

    this.setModalWasteAdd = function (_modalWasteAdd) {
        modalWasteAdd = _modalWasteAdd;
    };
}