function MainReportWoTotal() {

    const className = 'MainReportWoTotal';
    let self = this;
    const yearArr = mzGetYearArray();
    const monthArr = mzGetMonthArray();
    const totalColumnLabels = ['Site', 'PPM Open', 'PPM Closed', 'Client Complaint Open', 'Client Complaint Closed', 'Self Finding Open', 'Self Finding Closed', 'Request Open', 'Request Closed', 'Breakdown Open', 'Breakdown Closed', 'Defect Open', 'Defect Closed', 'Total'];
    const dailyColumnLabels = ['Date', 'PPM Open', 'PPM Closed', 'Client Complaint Open', 'Client Complaint Closed', 'Self Finding Open', 'Self Finding Closed', 'Request Open', 'Request Closed', 'Breakdown Open', 'Breakdown Closed', 'Defect Open', 'Defect Closed', 'Total'];
    let oTableWoTotal;
    let oTableWoDaily;
    let selectedYear;
    let selectedMonth;
    let modalReportTotalWo;
    let siteId;
    let siteName;
    let isManual;
    let totalDataCache = [];
    let dailyDataCache = [];
    let lastTotalUpdated = null;
    let lastDailyUpdated = null;
    const momentAvailable = (typeof moment === 'function');

    function refreshMaterialSelect(selector) {
        const el = $(selector);
        if (!el.length) {
            return;
        }
        try {
            el.materialSelect('destroy');
        } catch (err) {
            // ignore when component not initialised yet
        }
        el.materialSelect();
    }

    function updatePeriodLabel() {
        const pill = $('#lblRwtPeriod');
        if (!pill.length) {
            return;
        }
        const yearInt = parseInt(selectedYear, 10);
        const monthInt = parseInt(selectedMonth, 10);
        if (momentAvailable && !isNaN(yearInt) && !isNaN(monthInt)) {
            const label = moment({ year: yearInt, month: monthInt - 1, day: 1 }).format('MMM YYYY');
            pill.text('Reporting window: ' + label);
        } else if (!isNaN(yearInt) && !isNaN(monthInt)) {
            pill.text('Reporting window: ' + monthInt + '/' + yearInt);
        } else {
            pill.text('Reporting window: --');
        }
    }

    function formatTimestamp(value) {
        if (!value) {
            return 'Updated —';
        }
        if (momentAvailable && moment.isMoment(value)) {
            return 'Updated ' + value.format('DD MMM YYYY, hh:mm A');
        }
        return 'Updated ' + new Date(value).toLocaleString();
    }

    function updateTotalSummary() {
        if (!oTableWoTotal) {
            return;
        }
        const info = typeof oTableWoTotal.page === 'function' ? oTableWoTotal.page.info() : null;
        if (info) {
            $('#lblRwtTotalCount').text('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal + ' rows');
        }
        $('#lblRwtTotalUpdated').text(formatTimestamp(lastTotalUpdated));
    }

    function updateDailySummary() {
        if (!oTableWoDaily) {
            return;
        }
        const info = typeof oTableWoDaily.page === 'function' ? oTableWoDaily.page.info() : null;
        if (info) {
            $('#lblRwtDailyCount').text('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal + ' rows');
        }
        $('#lblRwtDailyUpdated').text(formatTimestamp(lastDailyUpdated));
    }

    function computeCategorySum(row, prefix) {
        let total = 0;
        for (let i = 0; i <= 5; i++) {
            const val = parseInt(row ? row[prefix + i] : 0, 10);
            if (!isNaN(val)) {
                total += val;
            }
        }
        return total;
    }

    function updateMetrics(data) {
        if (!Array.isArray(data)) {
            data = [];
        }
        const totalRow = data.find(function (row) { return row && row.siteName === 'TOTAL'; }) || {};
        const pendingRow = data.find(function (row) { return row && row.siteName === 'PENDING'; }) || {};
        const siteRows = data.filter(function (row) {
            return row && row.siteName !== 'TOTAL' && row.siteName !== 'PENDING';
        });

        const totalOpen = computeCategorySum(totalRow, 'open');
        const totalClosed = computeCategorySum(totalRow, 'closed');
        const pendingTotal = computeCategorySum(pendingRow, 'open');
        const sitesTracked = siteRows.length;

        $('#metricRwtOpen').text(totalOpen.toLocaleString());
        $('#metricRwtClosed').text(totalClosed.toLocaleString());
        $('#metricRwtPending').text(pendingTotal.toLocaleString());
        $('#metricRwtSites').text(sitesTracked.toLocaleString());
    }

    function setDataLabelsForTable(selector, labels) {
        const $table = $(selector);
        if (!$table.length) {
            return;
        }
        $table.find('tbody tr').each(function () {
            $('td:visible', this).each(function (idx) {
                const label = labels[idx] || '';
                $(this).attr('data-label', label);
            });
        });
    }

    this.init = function () {
        $('#divRwtWoDaily').hide();
        let dateCurrent = new Date();
        selectedMonth = dateCurrent.getMonth()+1;
        selectedYear = dateCurrent.getFullYear();

        mzOption('optRwtYearId', yearArr, 'Choose Year', 'yearId', 'yearName', {}, 'required', false);
        $('#optRwtYearId').val(selectedYear);

        mzOption('optRwtMonthId', monthArr, 'Choose Month', 'monthId', 'monthName', {}, 'required', false);
        $('#optRwtMonthId').val(selectedMonth);

        refreshMaterialSelect('#optRwtYearId');
        refreshMaterialSelect('#optRwtMonthId');

        const vData = [
            {
                field_id: 'optRwtMonthId',
                type: 'select',
                name: 'Year',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optRwtYearId',
                type: 'select',
                name: 'Month',
                validator: {
                    notEmpty: true
                }
            }
        ];

        let formValidate = new MzValidate('formRwtSearch');
        formValidate.registerFields(vData);

        $('#btnRwtSearch').attr('disabled', !formValidate.validateForm());

        $('#formRwtSearch').on('keyup change', 'select, input', function () {
            $('#btnRwtSearch').attr('disabled', !formValidate.validateForm());
        });

        oTableWoTotal = $('#dtRwtWoTotal').DataTable({
            bLengthChange: false,
            bFilter: false,
            bInfo: false,
            bPaginate: false,
            autoWidth: false,
            ordering: false,
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkRwtDailyView').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableWoTotal.row(parseInt(rowId)).data();
                        self.drillDaily(currentRow['siteId'], currentRow['siteName'], currentRow['isManual']);
                    }
                });
                setDataLabelsForTable('#dtRwtWoTotal', totalColumnLabels);
                updateTotalSummary();
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: 'siteName',
                        mRender: function (data, type, row, meta) {
                            if (data === 'TOTAL' || data === 'PENDING') {
                                return '<strong>'+data+'</strong>';
                            } else {
                                return data + '&nbsp;&nbsp;<a><i class="fas fa-folder-open lnkRwtDailyView" id="lnkRwtDailyView_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Daily Summary"></i></a>';
                            }
                        }},
                    {mData: 'open0', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL' || row['siteName'] === 'PENDING') {
                                return '<strong>'+(row['siteName'] === 'TOTAL'?mzFormatNumber(data):'')+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed0', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL' || row['siteName'] === 'PENDING') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open1', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL' || row['siteName'] === 'PENDING') {
                                return '<strong>'+(row['siteName'] === 'TOTAL'?mzFormatNumber(data):'')+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed1', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL' || row['siteName'] === 'PENDING') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open2', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL' || row['siteName'] === 'PENDING') {
                                return '<strong>'+(row['siteName'] === 'TOTAL'?mzFormatNumber(data):'')+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed2', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL' || row['siteName'] === 'PENDING') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open3', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL' || row['siteName'] === 'PENDING') {
                                return '<strong>'+(row['siteName'] === 'TOTAL'?mzFormatNumber(data):'')+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed3', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL' || row['siteName'] === 'PENDING') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open4', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL' || row['siteName'] === 'PENDING') {
                                return '<strong>'+(row['siteName'] === 'TOTAL'?mzFormatNumber(data):'')+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed4', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL' || row['siteName'] === 'PENDING') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open5', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL' || row['siteName'] === 'PENDING') {
                                return '<strong>'+(row['siteName'] === 'TOTAL'?mzFormatNumber(data):'')+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed5', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL' || row['siteName'] === 'PENDING') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: null, sClass: 'text-right',mRender: function (data, type, row) {
                            const total = mzFormatNumber(parseInt(row['open0'])+parseInt(row['open1'])+parseInt(row['open2'])+
                                parseInt(row['open3'])+parseInt(row['open4'])+parseInt(row['open5']));
                            const totalPending = mzFormatNumber(parseInt(row['closed0'])+parseInt(row['closed1'])+parseInt(row['closed2'])+
                                parseInt(row['closed3'])+parseInt(row['closed4'])+parseInt(row['closed5']));
                            if (row['siteName'] === 'TOTAL' || row['siteName'] === 'PENDING') {
                                return '<strong>'+(row['siteName'] === 'TOTAL'?total:totalPending)+'</strong>';
                            } else {
                                return total;
                            }
                        }},
                    {mData: 'siteId', visible: false}
                ]
        });
        $("#dtRwtWoTotal_filter").hide();

        let btnWoTotalOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]
            }
        };

        new $.fn.dataTable.Buttons(oTableWoTotal, {
            buttons: [
                $.extend( true, {}, btnWoTotalOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Work Order Total',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnWoTotalOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Total Work Order Summary',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnWoTotalOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Total Work Order Summary',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtRwtWoTotalExport'));

        $('#txtRwtTotalSearch').on('keyup change', function () {
            const value = $(this).val();
            oTableWoTotal.search(value).draw();
        });

        $('#btnDtRwtWoTotalRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableWoTotal();
                } catch (e) {
                        console.error('Error in btnDtRwtWoTotalRefresh:', e);
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        oTableWoDaily = $('#dtRwtWoDaily').DataTable({
            bLengthChange: false,
            bFilter: false,
            bInfo: false,
            bPaginate: false,
            autoWidth: false,
            ordering: false,
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkRwtManualEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableWoDaily.row(parseInt(rowId)).data();
                        const selectedDate = currentRow['siteManualDate'];
                        const dateSplit = selectedDate.split("/");
                        const editData = [currentRow['open0'], currentRow['closed0'], currentRow['open1'], currentRow['closed1'], currentRow['open2'], currentRow['closed2'],
                            currentRow['open3'], currentRow['closed3'],currentRow['open4'], currentRow['closed4'], currentRow['open5'], currentRow['closed5']];
                        modalReportTotalWo.setEditData(editData);
                        modalReportTotalWo.edit(currentRow['siteManualId'], siteId, rowId, selectedYear, selectedMonth, siteName, dateSplit[0]);
                    }
                });
                setDataLabelsForTable('#dtRwtWoDaily', dailyColumnLabels);
                updateDailySummary();
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: 'siteManualDate', sClass: 'pl-2',
                        mRender: function (data, type, row, meta) {
                            if (data === 'TOTAL' || data === 'PENDING') {
                                return '<strong>'+data+'</strong>';
                            } else if (isManual) {
                                return data + '&nbsp;&nbsp;<a><i class="fas fa-edit lnkRwtManualEdit" id="lnkRwtManualEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>';
                            }
                            return data;
                        }},
                    {mData: 'open0', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteManualDate'] === 'TOTAL' || row['siteManualDate'] === 'PENDING') {
                                return '<strong>'+(row['siteManualDate'] === 'TOTAL'?mzFormatNumber(data):'')+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed0', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteManualDate'] === 'TOTAL' || row['siteManualDate'] === 'PENDING') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open1', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteManualDate'] === 'TOTAL' || row['siteManualDate'] === 'PENDING') {
                                return '<strong>'+(row['siteManualDate'] === 'TOTAL'?mzFormatNumber(data):'')+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed1', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteManualDate'] === 'TOTAL' || row['siteManualDate'] === 'PENDING') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open2', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteManualDate'] === 'TOTAL' || row['siteManualDate'] === 'PENDING') {
                                return '<strong>'+(row['siteManualDate'] === 'TOTAL'?mzFormatNumber(data):'')+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed2', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteManualDate'] === 'TOTAL' || row['siteManualDate'] === 'PENDING') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open3', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteManualDate'] === 'TOTAL' || row['siteManualDate'] === 'PENDING') {
                                return '<strong>'+(row['siteManualDate'] === 'TOTAL'?mzFormatNumber(data):'')+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed3', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteManualDate'] === 'TOTAL' || row['siteManualDate'] === 'PENDING') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open4', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteManualDate'] === 'TOTAL' || row['siteManualDate'] === 'PENDING') {
                                return '<strong>'+(row['siteManualDate'] === 'TOTAL'?mzFormatNumber(data):'')+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed4', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteManualDate'] === 'TOTAL' || row['siteManualDate'] === 'PENDING') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open5', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteManualDate'] === 'TOTAL' || row['siteManualDate'] === 'PENDING') {
                                return '<strong>'+(row['siteManualDate'] === 'TOTAL'?mzFormatNumber(data):'')+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed5', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteManualDate'] === 'TOTAL' || row['siteManualDate'] === 'PENDING') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: null, sClass: 'text-right',mRender: function (data, type, row) {
                            const total = mzFormatNumber(parseInt(row['open0'])+parseInt(row['open1'])+parseInt(row['open2'])+
                                parseInt(row['open3'])+parseInt(row['open4'])+parseInt(row['open5']));
                            const totalPending = mzFormatNumber(parseInt(row['closed0'])+parseInt(row['closed1'])+parseInt(row['closed2'])+
                                parseInt(row['closed3'])+parseInt(row['closed4'])+parseInt(row['closed5']));
                            if (row['siteManualDate'] === 'TOTAL' || row['siteManualDate'] === 'PENDING') {
                                return '<strong>'+(row['siteManualDate'] === 'TOTAL'?total:totalPending)+'</strong>';
                            } else {
                                return total;
                            }
                        }},
                    {mData: 'siteManualId', visible: false}
                ]
        });
        $("#dtRwtWoDaily_filter").hide();

        let btnWoDailyOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]
            }
        };

        new $.fn.dataTable.Buttons(oTableWoDaily, {
            buttons: [
                $.extend( true, {}, btnWoDailyOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Work Order Daily',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnWoDailyOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Daily Work Order Summary',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnWoDailyOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Daily Work Order Summary',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtRwtWoDailyExport'));

        $('#txtRwtDailySearch').on('keyup change', function () {
            const value = $(this).val();
            oTableWoDaily.search(value).draw();
        });

        $('#btnDtRwtWoDailyRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableWoDaily();
                } catch (e) {
                    console.error('Error in btnDtRwtWoDailyRefresh:', e);
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnRwtSearch').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    selectedYear = $('#optRwtYearId').val();
                    selectedMonth = $('#optRwtMonthId').val();
                    $('#divRwtWoDaily').hide();
                    updatePeriodLabel();
                    self.genTableWoTotal();
                } catch (e) {
                    console.error('Error in btnRwtSearch:', e);
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnDtRwtWoDailyAdd').on('click', function () {
            modalReportTotalWo.add(siteId, selectedYear, selectedMonth, siteName);
        });

        updatePeriodLabel();
        self.genTableWoTotal();
    };

    this.genTableWoTotal = function () {
        const dataWoTotal = mzAjaxRequest('wo.php?type=report_wo_total&year='+selectedYear+'&month='+selectedMonth, 'GET');
        totalDataCache = Array.isArray(dataWoTotal) ? dataWoTotal : [];
        oTableWoTotal.clear().rows.add(totalDataCache).draw();
        updateMetrics(totalDataCache);
        lastTotalUpdated = momentAvailable ? moment() : new Date();
        updateTotalSummary();
    };

    this.genTableWoDaily = function () {
        const dataWoDaily = mzAjaxRequest('wo.php?type=report_wo_daily&siteId='+siteId+'&year='+selectedYear+'&month='+selectedMonth+'&isManual='+isManual, 'GET');
        dailyDataCache = Array.isArray(dataWoDaily) ? dataWoDaily : [];
        oTableWoDaily.clear().rows.add(dailyDataCache).draw();
        lastDailyUpdated = momentAvailable ? moment() : new Date();
        updateDailySummary();
    };

    this.drillDaily = function (_siteId, _siteName, _isManual) {
        ShowLoader();
        setTimeout(function () {
            try {
                siteId = _siteId;
                siteName = _siteName;
                isManual = _isManual;
                $('#spanRwtWoDailySiteName').html(siteName);
                isManual ? $('#btnDtRwtWoDailyAdd').show() : $('#btnDtRwtWoDailyAdd').hide();
                self.genTableWoDaily();
                $('#divRwtWoDaily').show();
            } catch (e) {
                console.error('Error in drillDaily:', e);
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.getClassName = function () {
        return className;
    };

    this.setModalReportTotalWo = function (_modalReportTotalWo) {
        modalReportTotalWo = _modalReportTotalWo;
    };
}