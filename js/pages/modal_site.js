function ModalSite() {

    const className = 'ModalSite';
    let self = this;
    let siteId = '';
    let rowRefresh = '';
    let classFrom;
    let refStatus;
    let refClient;
    let oTableProblemType;
    let oTableSiteLocation;

    this.init = function () {
        const vData = [
            {
                field_id: 'optMstClientId',
                type: 'select',
                name: 'Client',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMstName',
                type: 'text',
                name: 'Site Name',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'txtMstCode',
                type: 'text',
                name: 'Site Code',
                validator: {
                    notEmpty: true,
                    maxLength: 5
                }
            },
            {
                field_id: 'txaMstDesc',
                type: 'text',
                name: 'Description',
                validator: {
                    maxLength: 255
                }
            },
            {
                field_id: 'chkMstWorkRequest',
                type: 'checkSingle',
                name: 'Work Request',
                validator: {
                }
            },
            {
                field_id: 'chkMstPublic',
                type: 'checkSingle',
                name: 'Public QR Scan',
                validator: {
                }
            },
            {
                field_id: 'chkMstStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        let formValidate = new MzValidate('formMst');
        formValidate.registerFields(vData);

        $('#formMst').on('keyup change', function () {
            $('#btnMstSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_site').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMstSubmit').attr('disabled', true);
            mzDisableSelect('optMstClientId', false);
        });

        $('#btnMstSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const clientId = $('#optMstClientId').val();
                        const txtName = $('#txtMstName').val();
                        const txtCode = $('#txtMstCode').val();
                        const txtDesc = $('#txaMstDesc').val();
                        const siteIsWr = $("input[name='chkMstWorkRequest']").is(":checked") ? '1' : '0';
                        const siteIsPublic = $("input[name='chkMstPublic']").is(":checked") ? '1' : '0';
                        const statusVal = $("input[name='chkMstStatus']").is(":checked") ? '1' : '2';
                        const data = {
                            clientId :clientId,
                            siteName: txtName,
                            siteCode: txtCode,
                            siteDesc: txtDesc,
                            siteIsWr: siteIsWr,
                            siteIsPublic: siteIsPublic,
                            siteStatus: statusVal
                        };

                        let tempRow = {};
                        if (siteId === '') {
                            siteId = mzAjaxRequest('site.php', 'POST', data);
                            if (classFrom.getClassName() === 'MainSite') {
                                tempRow['siteId'] = siteId;
                                tempRow['clientId'] = clientId;
                                tempRow['siteName'] = txtName;
                                tempRow['siteCode'] = txtCode;
                                tempRow['siteDesc'] = txtDesc;
                                tempRow['siteIsWr'] = siteIsWr;
                                tempRow['siteIsPublic'] = siteIsPublic;
                                tempRow['siteStatus'] = statusVal;
                                classFrom.addTableSte(tempRow);
                            }
                        } else {
                            data['action'] = 'update';
                            mzAjaxRequest('site.php?siteId='+siteId, 'PUT', data);
                            if (classFrom.getClassName() === 'MainSite') {
                                tempRow['siteId'] = siteId;
                                tempRow['siteName'] = txtName;
                                tempRow['siteCode'] = txtCode;
                                tempRow['siteDesc'] = txtDesc;
                                tempRow['siteIsWr'] = siteIsWr;
                                tempRow['siteIsPublic'] = siteIsPublic;
                                tempRow['siteStatus'] = statusVal;
                                classFrom.updateTableSte(tempRow, rowRefresh);
                            }
                        }
                        $('#modal_site').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        oTableProblemType =  $('#dtMstProblemType').DataTable({
            bLengthChange: false,
            autoWidth: false,
            bFilter: false,
            aaSorting: [1, 'asc'],
            language: _DATATABLE_LANGUAGE,
            dom: "<'row'<'col-sm-12'B>>" +
                "<'row'<'col-sm-12'tr>>"+
                "<'row'<'col-sm-12 col-md-5 d-none d-md-block'i><'col-sm-12 col-md-7'p>>",
            buttons: [
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 mx-1 mb-1', text:'<i class="fas fa-print text-dark"></i>', title:'SPDP 2.0 - Senarai Semak Pematuhan - Standard Keselamatan (Para 4 dan 5)', titleAttr: 'Cetak', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 mx-1 mb-1', text:'<i class="fas fa-copy text-dark"></i>', title:'SPDP 2.0 - Senarai Semak Pematuhan - Standard Keselamatan (Para 4 dan 5)', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 mx-1 mb-1', text:'<i class="fas fa-file-excel text-dark"></i>', title:'SPDP 2.0 - Senarai Semak Pematuhan - Standard Keselamatan (Para 4 dan 5)', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 mx-1 mb-1', text:'<i class="fas fa-file-pdf text-dark"></i>', title:'SPDP 2.0 - Senarai Semak Pematuhan - Standard Keselamatan (Para 4 dan 5)', titleAttr: 'PDF', exportOptions: mzExportOpt}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            aoColumns:
                [
                    {mData: null, bSortable: false, sClass: 'text-center'},
                    {mData: 'siteProblemTypeName'},
                    {mData: null, sClass: 'text-center',
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['siteProblemTypeStatus']]['statusColor']+' z-depth-2">'+refStatus[row['siteProblemTypeStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: 'siteProblemTypeStatus', visible: false,
                        mRender: function (data, type, row) {
                            return refStatus[row['siteProblemTypeStatus']]['statusDesc'];
                        }
                    }
                ]
        });

        oTableSiteLocation =  $('#dtMstSiteLocation').DataTable({
            bLengthChange: false,
            autoWidth: false,
            bFilter: false,
            aaSorting: [1, 'asc'],
            language: _DATATABLE_LANGUAGE,
            dom: "<'row'<'col-sm-12'B>>" +
                "<'row'<'col-sm-12'tr>>"+
                "<'row'<'col-sm-12 col-md-5 d-none d-md-block'i><'col-sm-12 col-md-7'p>>",
            buttons: [
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 mx-1 mb-1', text:'<i class="fas fa-print text-dark"></i>', title:'SPDP 2.0 - Senarai Semak Pematuhan - Standard Keselamatan (Para 4 dan 5)', titleAttr: 'Cetak', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 mx-1 mb-1', text:'<i class="fas fa-copy text-dark"></i>', title:'SPDP 2.0 - Senarai Semak Pematuhan - Standard Keselamatan (Para 4 dan 5)', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 mx-1 mb-1', text:'<i class="fas fa-file-excel text-dark"></i>', title:'SPDP 2.0 - Senarai Semak Pematuhan - Standard Keselamatan (Para 4 dan 5)', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 mx-1 mb-1', text:'<i class="fas fa-file-pdf text-dark"></i>', title:'SPDP 2.0 - Senarai Semak Pematuhan - Standard Keselamatan (Para 4 dan 5)', titleAttr: 'PDF', exportOptions: mzExportOpt}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableSite.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'siteLocationName'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['siteLocationStatus']]['statusColor']+' z-depth-2">'+refStatus[row['siteLocationStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: 'siteLocationStatus', visible: false,
                        mRender: function (data, type, row) {
                            return refStatus[row['siteLocationStatus']]['statusDesc'];
                        }
                    }
                ]
        });


    };

    this.add = function () {
        siteId = '';
        rowRefresh = '';

        ShowLoader();
        setTimeout(function () {
            try {
                $('.isMstWorkRequest').hide();
                console.log(refClient);
                mzOptionStop('optMstClientId', refClient, 'Choose Client', 'clientId', 'clientName', {clientStatus: '1'}, 'required');

                mzSetFieldValue('MstStatus', '1', 'checkSingle', '1');
                $('#lblMstTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add Site');
                $('#modal_site').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.edit = function (_siteId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                $('.isMstWorkRequest').hide();
                mzOptionStop('optMstClientId', refClient, 'Choose Client', 'clientId', 'clientName');
                mzCheckFuncParam([_siteId, _rowRefresh]);
                siteId = _siteId;
                rowRefresh = _rowRefresh;

                const dataMst = mzAjaxRequest('site.php?siteId='+siteId, 'GET');
                mzSetFieldValue('MstClientId', dataMst['clientId'], 'select', 'Client *');
                mzSetFieldValue('MstName', dataMst['siteName'], 'text');
                mzSetFieldValue('MstCode', dataMst['siteCode'], 'text');
                mzSetFieldValue('MstDesc', dataMst['siteDesc'], 'textarea');
                mzSetFieldValue('MstWorkRequest', dataMst['siteIsWr'], 'checkSingle', '1');
                mzSetFieldValue('MstPublic', dataMst['siteIsPublic'], 'checkSingle', '1');
                mzSetFieldValue('MstStatus', dataMst['siteStatus'], 'checkSingle', '1');

                mzDisableSelect('optMstClientId', true);
                if (dataMst['siteIsWr'] === '1') {
                    $('.isMstWorkRequest').show();
                    self.genTableProblemType();
                }

                $('#lblMstTitle').html('<i class="far fa-edit text-white"></i> &nbsp;Edit Site');
                $('#modal_site').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.deactivate = function (_siteId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteId, _rowRefresh]);
                mzAjaxRequest('site.php?siteId='+_siteId, 'PUT', {action: 'deactivate'});
                const tempRow = {siteStatus:'2'};
                if (classFrom.getClassName() === 'MainSite') {
                    classFrom.updateTableSte(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_siteId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteId, _rowRefresh]);
                mzAjaxRequest('site.php?siteId='+_siteId, 'PUT', {action: 'activate'});
                const tempRow = {siteStatus:'1'};
                if (classFrom.getClassName() === 'MainSite') {
                    classFrom.updateTableSte(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_siteId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteId]);
                mzAjaxRequest('site.php?siteId='+_siteId, 'DELETE');
                if (classFrom.getClassName() === 'MainSite') {
                    classFrom.genTableSte(1);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.genTableProblemType = function () {
        const dataDb = mzAjaxRequest('site_problem_type.php?siteId='+siteId, 'GET');
        oTableProblemType.clear().rows.add(dataDb).draw();
    };

    this.genTableLocation = function () {
        const dataDb = mzAjaxRequest('site.php?type=problemType&siteId='+siteId, 'GET');
        oTableSiteLocation.clear().rows.add(dataDb).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };
}