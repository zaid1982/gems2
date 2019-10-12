function MainReportWoPending() {

    const className = 'MainReportWoPending';
    let self = this;
    let refClient;
    let refSite;
    let refStatus;
    let refUser;
    const yearArr = mzGetYearArray();
    const monthArr = mzGetMonthArray();
    let oTableWoPending;
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

        oTableWoPending = $('#dtRwpWoPending').DataTable({
            bLengthChange: false,
            bFilter: true,
            autoWidth: false,
            "aaSorting": [3, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableWoPending.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'woTaskCreatedBy', mRender: function (data, type, row){
                            return data !== null ? refUser[data]['userFullName'] : '';
                        }},
                    {mData: 'woTaskComplaint'},
                    {mData: 'woTaskTimeCreated', mRender: function (data){
                            return data.substr(0, 10);
                        }},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['woTaskStatus']]['statusColor']+' z-depth-2">'+refStatus[row['woTaskStatus']]['statusDesc']+'</span></h6>';
                        }
                    }
                ]
        });
        $("#dtRwpWoPending_filter").hide();
        $('#txtRwpWoPendingSearch').on('keyup change', function () {
            oTableWoPending.search($(this).val()).draw();
        });

        let cntWoPending;
        let btnWoPendingOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntWoPending = 1;
                        }
                        if (column === 4) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntWoPending++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableWoPending, {
            buttons: [
                $.extend( true, {}, btnWoPendingOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Outstanding Word Order List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnWoPendingOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Outstanding Word Order List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnWoPendingOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Outstanding Word Order List',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtRwpWoPendingExport'));

        $('#btnDtRwpWoPendingRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableWoPending();
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
                    self.genTableWoPending();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        self.genTableWoPending();
    };

    this.genTableWoPending = function () {
        const dataWoPending = mzAjaxRequest('wo.php?type=report_wo_pending_list&siteId='+siteId+'&year='+selectedYear+'&month='+(parseInt(selectedMonth)-1), 'GET');
        oTableWoPending.clear().rows.add(dataWoPending).draw();
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

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };
}