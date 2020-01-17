function MainAuditTrail() {

    const className = 'MainAuditTrail';
    let self = this;
    let refUser;
    let refAuditModule;
    let refAuditAction;
    let oTableAdt;
    const monthFull = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    this.init = function () {
        let arrYear = [];
        let arrMonth = [];
        let dt = new Date();
        const currentYear = dt.getFullYear();
        const currentMonth = dt.getMonth();

        for (let year = 2019; year <= currentYear; year++) {
            arrYear.push({yearId:year.toString(), yearDesc:year.toString()});
        }
        for (let i = 0; i <= monthFull.length; i++) {
            arrMonth.push({monthId:i, monthDesc:monthFull[i]});
        }

        mzOption('optAdtYear', arrYear, 'Select Year', 'yearId', 'yearDesc', {}, 'required');
        mzOption('optAdtMonth', arrMonth, 'Select Month', 'monthId', 'monthDesc', {}, 'required', false);
        mzOption('optAdtModule', refAuditModule, 'All Module', 'auditModuleId', 'auditModuleDesc', {}, '', false);
        mzOption('optAdtAction', refAuditAction, 'All Action', 'auditActionId', 'auditActionDesc', {auditModuleId: '0'}, '', false);

        $('#optAdtYear').val(currentYear);
        $('#optAdtMonth').val(currentMonth);

        $('#optAdtModule').on('change', function () {
            mzOptionStop('optAdtAction', refAuditAction, 'All Action', 'auditActionId', 'auditActionDesc', {auditModuleId: $(this).val()}, '', false);
        });

        const vData = [
            {
                field_id: 'optAdtModule',
                type: 'select',
                name: 'Module',
                validator: {
                }
            },
            {
                field_id: 'optAdtAction',
                type: 'select',
                name: 'Action',
                validator: {
                }
            },
            {
                field_id: 'optAdtYear',
                type: 'select',
                name: 'Year',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optAdtMonth',
                type: 'select',
                name: 'Month',
                validator: {
                    notEmpty: true
                }
            }
        ];

        let formValidate = new MzValidate('formAdtSearch');
        formValidate.registerFields(vData);

        $('#formAdtSearch').on('keyup change', function () {
            $('#btnAdtSearch').attr('disabled', !formValidate.validateForm());
        });

        oTableAdt =  $('#dtAdtList').DataTable({
            bLengthChange: false,
            bFilter: true,
            pageLength: 100,
            "aaSorting": [1, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableAdt.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'auditTimestamp'},
                    {mData: 'userId', mRender: function (data, type, row){
                            return data !== '' ? refUser[data]['userFirstName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            if (row['auditActionId'] !== '') {
                                const moduleId = refAuditAction[row['auditActionId']]['auditModuleId'];
                                return refAuditModule[moduleId]['auditModuleDesc'];
                            }
                            return '';
                        }},
                    {mData: 'auditActionId', mRender: function (data, type, row){
                            return data !== '' ? refAuditAction[data]['auditActionDesc'] : '';
                        }},
                    {mData: 'auditIp'},
                    {mData: 'auditRemark'}
                ]
        });
        $("#dtAdtList_filter").hide();
        $('#txtAdtListSearch').on('keyup change', function () {
            oTableAdt.search($(this).val()).draw();
        });

        let cntAdt;
        let btnAdtOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntAdt = 1;
                        }
                        return column === 0 ? cntAdt++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableAdt, {
            buttons: [
                $.extend( true, {}, btnAdtOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Audit Trail List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnAdtOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Audit Trail List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnAdtOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Audit Trail List',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtAdtListExport'));

        $('#btnAdtSearch').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAdt();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        self.genTableAdt();
    };

    this.genTableAdt = function () {
        const dataAdt = mzAjaxRequest('audit.php?year='+$('#optAdtYear').val()+'&month='+$('#optAdtMonth').val()+'&moduleId='+$('#optAdtModule').val()+'&actionId='+$('#optAdtAction').val(), 'GET');
        oTableAdt.clear().rows.add(dataAdt).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefAuditModule = function (_refAuditModule) {
        refAuditModule = _refAuditModule;
    };

    this.setRefAuditAction = function (_refAuditAction) {
        refAuditAction = _refAuditAction;
    };
}