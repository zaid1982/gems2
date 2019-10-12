function MainReportWoPending() {

    const className = 'MainReportWoPending';
    let self = this;
    let refClient;
    let refSite;
    const yearArr = mzGetYearArray();
    const monthArr = mzGetMonthArray();
    let oTablePpmSummary;
    let clientId;
    let siteId;
    let selectedYear;
    let selectedMonth;

    this.init = function () {
        mzOption('optRwpClientId', refClient, 'Choose Client', 'clientId', 'clientName', {}, 'required');
        mzOption('optRwpSiteId', refSite, 'Choose Site', 'siteId', 'siteDesc', {clientId: '1', siteStatus: '1'}, 'required');

        clientId = '1';
        siteId = '1';
        $('#optRwpClientId').val(clientId);
        $('#optRwpSiteId').val(siteId);

        let dateCurrent = new Date();
        selectedMonth = dateCurrent.getMonth()+1;
        selectedYear = dateCurrent.getFullYear();

        mzOption('optRwpYearId', yearArr, 'Choose Year', 'yearId', 'yearName', {}, 'required', false);
        $('#optRwpYearId').val(selectedYear);

        mzOption('optRwpMonthId', monthArr, 'Choose Month', 'monthId', 'monthName', {}, 'required', false);
        $('#optRwpMonthId').val(selectedMonth);

        $('#optRwpClientId').on('change', function () {
            clientId = $(this).val();
            mzOptionStop('optRwpSiteId', refSite, 'Choose Site', 'siteId', 'siteName', {clientId: $(this).val(), siteStatus: '1'}, 'required');
        });

        const vData = [
            {
                field_id: 'optRwpClientId',
                type: 'select',
                name: 'Client',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optRwpMonthId',
                type: 'select',
                name: 'Year',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optRwpYearId',
                type: 'select',
                name: 'Month',
                validator: {
                    notEmpty: true
                }
            }
        ];

        let formValidate = new MzValidate('formRwpSearch');
        formValidate.registerFields(vData);

        $('#btnRwpSearch').attr('disabled', !formValidate.validateForm());
        $('#formRwpSearch').on('keyup change', function () {
            $('#btnRwpSearch').attr('disabled', !formValidate.validateForm());
        });

        oTablePpmSummary = $('#dtRwpPpmSummary').DataTable({
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            bPaginate: false,
            autoWidth: false,
            "aaSorting": [6, 'DESC'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTablePpmSummary.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'assetTypeName'},
                    {mData: 'frequency'},
                    {mData: 'noAsset', sClass: 'text-right',
                        mRender: function (data) {
                            return mzFormatNumber(data);
                        }},
                    {mData: 'totalPpm', sClass: 'text-right',
                        mRender: function (data) {
                            return mzFormatNumber(data);
                        }},
                    {mData: 'ppmDone', sClass: 'text-right',
                        mRender: function (data) {
                            return mzFormatNumber(data);
                        }},
                    {mData: 'totalPercDone', sClass: 'text-right',
                        mRender: function (data) {
                            return mzFormatNumber(data, 2)+'%';
                        }}
                ]
        });
        $("#dtRwpPpmSummary_filter").hide();

        let cntPpmSummary;
        let btnPpmSummaryOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntPpmSummary = 1;
                        }
                        return column === 0 ? cntPpmSummary++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTablePpmSummary, {
            buttons: [
                $.extend( true, {}, btnPpmSummaryOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Outstanding Word Order List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnPpmSummaryOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Outstanding Word Order List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnPpmSummaryOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Outstanding Word Order List',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtRwpPpmSummaryExport'));

        $('#btnDtRwpPpmSummaryRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTablePpmSummary();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnRwpSearch').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    siteId = $('#optRwpSiteId').val();
                    selectedYear = $('#optRwpYearId').val();
                    selectedMonth = $('#optRwpMonthId').val();
                    self.genTablePpmSummary();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        self.genTablePpmSummary();
    };

    this.genTablePpmSummary = function () {
        const dataPpmSummary = mzAjaxRequest('ppm.php?type=report_ppm_summary&siteId='+siteId+'&year='+selectedYear+'&month='+selectedMonth, 'GET');
        oTablePpmSummary.clear().rows.add(dataPpmSummary).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };
}