function MainReportWoTotal() {

    const className = 'MainReportWoTotal';
    let self = this;
    const yearArr = mzGetYearArray();
    const monthArr = mzGetMonthArray();
    let oTableWoTotal;
    let oTableWoDaily;
    let selectedYear;
    let selectedMonth;
    let modalReportTotalWo;
    let siteId;
    let siteName;
    let isManual;

    this.init = function () {
        $('#divRwtWoDaily').hide();
        let dateCurrent = new Date();
        selectedMonth = dateCurrent.getMonth()+1;
        selectedYear = dateCurrent.getFullYear();

        mzOption('optRwtYearId', yearArr, 'Choose Year', 'yearId', 'yearName', {}, 'required', false);
        $('#optRwtYearId').val(selectedYear);

        mzOption('optRwtMonthId', monthArr, 'Choose Month', 'monthId', 'monthName', {}, 'required', false);
        $('#optRwtMonthId').val(selectedMonth);

        const vData = [
            {
                field_id: 'optRwtClientId',
                type: 'select',
                name: 'Client',
                validator: {
                    notEmpty: true
                }
            },
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
        $('#formRwtSearch').on('keyup change', function () {
            $('#btnRwtSearch').attr('disabled', !formValidate.validateForm());
        });

        oTableWoTotal = $('#dtRwtWoTotal').DataTable({
            bLengthChange: false,
            bFilter: true,
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
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: 'siteName',
                        mRender: function (data, type, row, meta) {
                            if (data === 'TOTAL') {
                                return '<strong>'+data+'</strong>';
                            } else {
                                return data + '&nbsp;&nbsp;<a><i class="fas fa-folder-open lnkRwtDailyView" id="lnkRwtDailyView_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Daily Summary"></i></a>';
                            }
                        }},
                    {mData: 'open0', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed0', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open1', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed1', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open2', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed2', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open3', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed3', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open4', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed4', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open5', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed5', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'siteId', visible: false}
                ]
        });
        $("#dtRwtWoTotal_filter").hide();

        let cntWoTotal;
        let btnWoTotalOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]
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

        $('#btnDtRwtWoTotalRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableWoTotal();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        oTableWoDaily = $('#dtRwtWoDaily').DataTable({
            bLengthChange: false,
            bFilter: true,
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
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: 'siteManualDate', sClass: 'pl-2',
                        mRender: function (data, type, row, meta) {
                            if (data === 'TOTAL') {
                                return '<strong>'+data+'</strong>';
                            } else if (isManual) {
                                return data + '&nbsp;&nbsp;<a><i class="fas fa-edit lnkRwtManualEdit" id="lnkRwtManualEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>';
                            }
                            return data;
                        }},
                    {mData: 'open0', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed0', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open1', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed1', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open2', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed2', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open3', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed3', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open4', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed4', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'open5', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'closed5', sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (row['siteName'] === 'TOTAL') {
                                return '<strong>'+mzFormatNumber(data)+'</strong>';
                            } else {
                                return mzFormatNumber(data);
                            }
                        }},
                    {mData: 'siteManualId', visible: false}
                ]
        });
        $("#dtRwtWoDaily_filter").hide();

        let cntWoDaily;
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

        $('#btnDtRwtWoDailyRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableWoDaily();
                } catch (e) {
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
                    self.genTableWoTotal();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnDtRwtWoDailyAdd').on('click', function () {
            modalReportTotalWo.add(siteId, selectedYear, selectedMonth, siteName);
        });

        self.genTableWoTotal();
    };

    this.genTableWoTotal = function () {
        const dataWoTotal = mzAjaxRequest('wo.php?type=report_wo_total&year='+selectedYear+'&month='+selectedMonth, 'GET');
        oTableWoTotal.clear().rows.add(dataWoTotal).draw();
    };

    this.genTableWoDaily = function () {
        const dataWoDaily = mzAjaxRequest('wo.php?type=report_wo_daily&siteId='+siteId+'&year='+selectedYear+'&month='+selectedMonth+'&isManual='+isManual, 'GET');
        oTableWoDaily.clear().rows.add(dataWoDaily).draw();
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