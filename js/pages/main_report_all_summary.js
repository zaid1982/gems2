function MainReportWoTotal() {

    const className = 'MainReportWoTotal';
    let self = this;
    let refStatus;
    let refClient;
    let refSite;
    const yearArr = mzGetYearArray();
    const monthArr = mzGetMonthArray();
    let oTableWoTotal;
    let selectedYear;
    let selectedMonth;
    let siteColumns = [];

    this.init = function () {
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
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: 'siteName',  mRender: function (data) { return data === 'TOTAL' ? '<strong>'+data+'</strong>' : data}},
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
                ]
        });
        $("#dtRwtWoTotal_filter").hide();

        let cntWoTotal;
        let btnWoTotalOpt = {
            exportOptions: {
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

        $('#btnRwtSearch').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    selectedYear = $('#optRwtYearId').val();
                    selectedMonth = $('#optRwtMonthId').val();
                    self.genTableWoTotal();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        self.genTableWoTotal();
    };

    this.genTableWoTotal = function () {
        const dataWoTotal = mzAjaxRequest('wo.php?type=report_wo_total&year='+selectedYear+'&month='+selectedMonth, 'GET');
        oTableWoTotal.clear().rows.add(dataWoTotal).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };
}