function ModalSite() {

    const className = 'ModalSite';
    let self = this;
    let siteId = '';
    let rowRefresh = '';
    let classFrom;
    let refClient;

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
                name: 'Site',
                validator: {
                    notEmpty: true,
                    maxLength: 150
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

            const selectorClientId = $('#optMstClientId');
            selectorClientId.material_select('destroy');
            selectorClientId.prop('disabled', false);
            selectorClientId.material_select();
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
                        const txtName = $('#txtMzgName').val();
                        const txtDesc = $('#txaMzgDesc').val();
                        const statusVal = $("input[name='chkMstStatus']").is(":checked") ? '1' : '2';
                        const data = {
                            clientId :clientId,
                            siteName: txtName,
                            siteDesc: txtDesc,
                            siteStatus: statusVal
                        };

                        let tempRow = {};
                        if (siteId === '') {
                            siteId = mzAjaxRequest('site.php', 'POST', data);
                            if (classFrom.getClassName() === 'MainSite') {
                                tempRow['siteId'] = siteId;
                                tempRow['clientId'] = clientId;
                                tempRow['siteName'] = txtName;
                                tempRow['siteDesc'] = txtDesc;
                                tempRow['siteStatus'] = statusVal;
                                classFrom.addTableSte(tempRow);
                            }
                        } else {
                            data['action'] = 'update';
                            mzAjaxRequest('site.php?siteId='+siteId, 'PUT', data);
                            if (classFrom.getClassName() === 'MainSite') {
                                tempRow['siteId'] = siteId;
                                tempRow['siteName'] = txtName;
                                tempRow['siteDesc'] = txtDesc;
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
    };

    this.add = function () {
        siteId = '';
        rowRefresh = '';

        ShowLoader();
        setTimeout(function () {
            try {
                mzOption('optMstClientId', refClient, 'Choose Client', 'clientId', 'clientName', {clientStatus: '1'}, 'required');

                $('#lblMstTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add Site');
                $('#modal_site').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.edit = function (_siteId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzOption('optMstClientId', refClient, 'Choose Client', 'clientId', 'clientName');
                mzCheckFuncParam([_siteId, _rowRefresh]);
                siteId = _siteId;
                rowRefresh = _rowRefresh;

                const dataMst = mzAjaxRequest('site.php?siteId='+siteId, 'GET');
                mzSetFieldValue('MstClientId', dataMst['clientId'], 'select', 'Client *');
                mzSetFieldValue('MstName', dataMst['siteName'], 'text');
                mzSetFieldValue('MstDesc', dataMst['siteDesc'], 'textarea');
                mzSetFieldValue('MstStatus', dataMst['siteStatus'], 'checkSingle', '1');

                const selectorClientId = $('#optMstClientId');
                selectorClientId.material_select('destroy');
                selectorClientId.prop('disabled', true);
                selectorClientId.material_select();

                $('#lblMstTitle').html('<i class="far fa-edit text-white"></i> &nbsp;Edit Site');
                $('#modal_site').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
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

    this.delete = function (_siteId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteId, _rowRefresh]);
                mzAjaxRequest('site.php?siteId='+_siteId, 'DELETE');
                if (classFrom.getClassName() === 'MainSite') {
                    classFrom.deleteTableSte(_rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };
}