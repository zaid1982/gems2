function MainReportWoSummary() {

    const className = 'MainReportWoSummary';
    let self = this;
    let refStatus;
    let refClient;
    let refSite;
    const yearArr = mzGetYearArray();
    const monthArr = mzGetMonthArray();
    let oTableWoSummary;
    let clientId;
    let selectedYear;
    let selectedMonth;
    let siteColumns = [];

    this.init = function () {
        mzOption('optRwsClientId', refClient, 'Choose Client', 'clientId', 'clientName', {}, 'required');
        //mzOption('optRwsSiteId', refSite, 'Choose Site', 'siteId', 'siteDesc', {clientId: '1', siteStatus: '1'}, 'required');

        clientId = '1';
        $('#optRwsClientId').val(clientId);

        let dateCurrent = new Date();
        selectedMonth = dateCurrent.getMonth()+1;
        selectedYear = dateCurrent.getFullYear();

        mzOption('optRwsYearId', yearArr, 'Choose Year', 'yearId', 'yearName', {}, 'required', false);
        $('#optRwsYearId').val(selectedYear);

        mzOption('optRwsMonthId', monthArr, 'Choose Month', 'monthId', 'monthName', {}, 'required', false);
        $('#optRwsMonthId').val(selectedMonth);

        const vData = [
            {
                field_id: 'optRwsClientId',
                type: 'select',
                name: 'Client',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optRwsMonthId',
                type: 'select',
                name: 'Year',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optRwsYearId',
                type: 'select',
                name: 'Month',
                validator: {
                    notEmpty: true
                }
            }
        ];

        let formValidate = new MzValidate('formRwsSearch');
        formValidate.registerFields(vData);

        $('#btnRwsSearch').attr('disabled', !formValidate.validateForm());
        $('#formRwsSearch').on('keyup change', function () {
            $('#btnRwsSearch').attr('disabled', !formValidate.validateForm());
        });

        oTableWoSummary = $('#dtRwsWoSummary').DataTable({
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
                    {mData: 'woTaskType'},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return siteColumns.length > 0 ? mzFormatNumber(row['open'+siteColumns[0]['siteId']]) : '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return siteColumns.length > 0 ? mzFormatNumber(row['close'+siteColumns[0]['siteId']]) : '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return siteColumns.length > 1 ? mzFormatNumber(row['open'+siteColumns[1]['siteId']]) : '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return siteColumns.length > 1 ? mzFormatNumber(row['close'+siteColumns[1]['siteId']]) : '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return siteColumns.length > 2 ? mzFormatNumber(row['open'+siteColumns[2]['siteId']]) : '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return siteColumns.length > 2 ? mzFormatNumber(row['close'+siteColumns[2]['siteId']]) : '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return siteColumns.length > 3 ? mzFormatNumber(row['open'+siteColumns[3]['siteId']]) : '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return siteColumns.length > 3 ? mzFormatNumber(row['close'+siteColumns[3]['siteId']]) : '';
                        }},
                ]
        });
        $("#dtRwsWoSummary_filter").hide();

        let cntWoSummary;
        let btnWoSummaryOpt = {
            exportOptions: {
            }
        };

        new $.fn.dataTable.Buttons(oTableWoSummary, {
            buttons: [
                $.extend( true, {}, btnWoSummaryOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Work Order Summary',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnWoSummaryOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Work Order Summary',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnWoSummaryOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Work Order Summary',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtRwsWoSummaryExport'));

        $('#btnDtRwsWoSummaryRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableWoSummary();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnRwsSearch').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    clientId = $('#optRwsClientId').val();
                    selectedYear = $('#optRwsYearId').val();
                    selectedMonth = $('#optRwsMonthId').val();
                    self.genTableWoSummary();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        self.genTableWoSummary();
    };

    this.genTableWoSummary = function () {
        siteColumns = [];
        for (let i = 1; i <= 8; i++) {
            oTableWoSummary.column(i).visible(false);
        }
        $.each(refSite, function (n, u) {
            if (typeof u !== 'undefined' && u['siteStatus'] !== '2' && u['clientId'] === clientId) {
                siteColumns.push(u);
            }
        });
        $.each(siteColumns, function (n, u) {
            oTableWoSummary.column(n*2+1).visible(true);
            oTableWoSummary.column(n*2+2).visible(true);
            $('#thRwsSiteDesc'+n).html(u['siteDesc']);
        });

        const dataWoSummary = mzAjaxRequest('wo.php?type=report_wo_summary&clientId='+clientId+'&year='+selectedYear+'&month='+selectedMonth, 'GET');
        oTableWoSummary.clear().rows.add(dataWoSummary).draw();
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