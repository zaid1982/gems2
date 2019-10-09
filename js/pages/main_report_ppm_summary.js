function MainReportPpmSummary() {

    const className = 'MainReportPpmSummary';
    let self = this;
    let refStatus;
    let refClient;
    let refSite;
    const yearArr = mzGetYearArray();
    const monthArr = mzGetMonthArray();
    let oTablePpmSummary;
    let clientId;
    let selectedYear;
    let selectedMonth;

    this.init = function () {
        mzOption('optRpsClientId', refClient, 'Choose Client', 'clientId', 'clientName', {}, 'required');
        //mzOption('optRpsSiteId', refSite, 'Choose Site', 'siteId', 'siteDesc', {clientId: '1', siteStatus: '1'}, 'required');

        clientId = '1';
        $('#optRpsClientId').val(clientId);

        let dateCurrent = new Date();
        selectedMonth = dateCurrent.getMonth()+1;
        selectedYear = dateCurrent.getFullYear();

        mzOption('optRpsYearId', yearArr, 'Choose Year', 'yearId', 'yearName', {}, 'required', false);
        $('#optRpsYearId').val(selectedYear);

        mzOption('optRpsMonthId', monthArr, 'Choose Month', 'monthId', 'monthName', {}, 'required', false);
        $('#optRpsMonthId').val(selectedMonth);

        const vData = [
            {
                field_id: 'optRpsClientId',
                type: 'select',
                name: 'Client',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optRpsMonthId',
                type: 'select',
                name: 'Year',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optRpsYearId',
                type: 'select',
                name: 'Month',
                validator: {
                    notEmpty: true
                }
            }
        ];

        let formValidate = new MzValidate('formRpsSearch');
        formValidate.registerFields(vData);

        $('#btnRpsSearch').attr('disabled', !formValidate.validateForm());
        $('#formRpsSearch').on('keyup change', function () {
            $('#btnRpsSearch').attr('disabled', !formValidate.validateForm());
        });

        oTablePpmSummary = $('#dtRpsPpmSummary').DataTable({
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
                            return '1';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return '1';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return '1';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return '1';
                        }}
                ]
        });
        $("#dtRpsPpmSummary_filter").hide();

        let cntPpmSummary;
        let btnPpmSummaryOpt = {
            exportOptions: {
            }
        };

        new $.fn.dataTable.Buttons(oTablePpmSummary, {
            buttons: [
                $.extend( true, {}, btnPpmSummaryOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - PPM Summary',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnPpmSummaryOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - PPM Summary',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnPpmSummaryOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - PPM Summary',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtRpsPpmSummaryExport'));

        $('#btnRpsSearch').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    clientId = $('#optRpsClientId').val();
                    selectedYear = $('#optRpsYearId').val();
                    selectedMonth = $('#optRpsMonthId').val();
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
        //const dataPpmSummary = mzAjaxRequest('ppm.php?type=report_ppm_summary&clientId='+clientId+'&year='+selectedYear+'&month='+selectedMonth, 'GET');
        //oTablePpmSummary.clear().rows.add(dataPpmSummary).draw();
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