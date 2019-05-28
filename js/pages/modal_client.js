function ModalClient() {

    const className = 'ModalClient';
    let self = this;
    let clientId = '';
    let rowRefresh = '';
    let classFrom;

    this.init = function () {
        const vData = [
            {
                field_id: 'txtMclName',
                type: 'text',
                name: 'Client',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'txaMclDesc',
                type: 'text',
                name: 'Description',
                validator: {
                    maxLength: 255
                }
            },
            {
                field_id: 'chkMclStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        let formValidate = new MzValidate('formMcl');
        formValidate.registerFields(vData);

        $('#formMcl').on('keyup change', function () {
            $('#btnMclSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_client').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMclSubmit').attr('disabled', true);
        });

        $('#btnMclSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const txtName = $('#txtMclName').val();
                        const txtDesc = $('#txaMclDesc').val();
                        const statusVal = $("input[name='chkMclStatus']").is(":checked") ? '1' : '2';
                        const data = {
                            clientName: txtName,
                            clientDesc: txtDesc,
                            clientStatus: statusVal
                        };

                        let tempRow = {};
                        if (clientId === '') {
                            clientId = mzAjaxRequest('client.php', 'POST', data);
                            if (classFrom.getClassName() === 'MainClient') {
                                tempRow['clientId'] = clientId;
                                tempRow['clientName'] = txtName;
                                tempRow['clientDesc'] = txtDesc;
                                tempRow['clientStatus'] = statusVal;
                                classFrom.addTableCln(tempRow);
                            }
                        } else {
                            data['action'] = 'update';
                            mzAjaxRequest('client.php?clientId=' + clientId, 'PUT', data);
                            if (classFrom.getClassName() === 'MainClient') {
                                tempRow['clientId'] = clientId;
                                tempRow['clientName'] = txtName;
                                tempRow['clientDesc'] = txtDesc;
                                tempRow['clientStatus'] = statusVal;
                                classFrom.updateTableCln(tempRow, rowRefresh);
                            }
                        }
                        $('#modal_client').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.add = function () {
        clientId = '';
        rowRefresh = '';

        $('#lblMclTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add Client');
        $('#modal_client').modal({backdrop: 'static', keyboard: false});
    };

    this.edit = function (_clientId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_clientId, _rowRefresh]);
                clientId = _clientId;
                rowRefresh = _rowRefresh;

                const dataMcl = mzAjaxRequest('client.php?clientId='+clientId, 'GET');
                mzSetFieldValue('MclName', dataMcl['clientName'], 'text');
                mzSetFieldValue('MclDesc', dataMcl['clientDesc'], 'textarea');
                mzSetFieldValue('MclStatus', dataMcl['clientStatus'], 'checkSingle', '1');

                $('#lblMclTitle').html('<i class="far fa-edit text-white"></i> &nbsp;Edit Client');
                $('#modal_client').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_clientId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_clientId, _rowRefresh]);
                mzAjaxRequest('client.php?clientId='+_clientId, 'PUT', {action: 'deactivate'});
                const tempRow = {clientStatus:'2'};
                if (classFrom.getClassName() === 'MainClient') {
                    classFrom.updateTableCln(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_clientId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_clientId, _rowRefresh]);
                mzAjaxRequest('client.php?clientId='+_clientId, 'PUT', {action: 'activate'});
                const tempRow = {clientStatus:'1'};
                if (classFrom.getClassName() === 'MainClient') {
                    classFrom.updateTableCln(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_clientId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_clientId, _rowRefresh]);
                mzAjaxRequest('client.php?clientId='+_clientId, 'DELETE');
                if (classFrom.getClassName() === 'MainClient') {
                    classFrom.deleteTableCln(_rowRefresh);
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
}