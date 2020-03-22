function ModalFailureCode() {

    const className = 'ModalFailureCode';
    let self = this;
    let failureCodeId = '';
    let rowRefresh = '';
    let classFrom;

    this.init = function () {
        const vData = [
            {
                field_id: 'txtMfcName',
                type: 'text',
                name: 'Failure Code',
                validator: {
                    notEmpty: true,
                    maxLength: 100
                }
            },
            {
                field_id: 'chkMfcStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        let formValidate = new MzValidate('formMfc');
        formValidate.registerFields(vData);

        $('#formMfc').on('keyup change', function () {
            $('#btnMfcSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_failure_code').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMfcSubmit').attr('disabled', true);
        });

        $('#btnMfcSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const txtName = $('#txtMfcName').val();
                        const statusVal = $("input[name='chkMfcStatus']").is(":checked") ? '1' : '2';
                        const data = {
                            failureCodeName: txtName,
                            failureCodeStatus: statusVal
                        };

                        let tempRow = {};
                        if (failureCodeId === '') {
                            failureCodeId = mzAjaxRequest('failure_code.php', 'POST', data);
                            if (classFrom.getClassName() === 'MainFailureCode') {
                                tempRow['failureCodeId'] = failureCodeId;
                                tempRow['failureCodeName'] = txtName;
                                tempRow['failureCodeStatus'] = statusVal;
                                classFrom.addTableFlc(tempRow);
                            }
                        } else {
                            data['action'] = 'update';
                            mzAjaxRequest('failure_code.php?failureCodeId='+failureCodeId, 'PUT', data);
                            if (classFrom.getClassName() === 'MainFailureCode') {
                                tempRow['failureCodeId'] = failureCodeId;
                                tempRow['failureCodeName'] = txtName;
                                tempRow['failureCodeStatus'] = statusVal;
                                classFrom.updateTableFlc(tempRow, rowRefresh);
                            }
                        }
                        $('#modal_failure_code').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.add = function () {
        failureCodeId = '';
        rowRefresh = '';

        mzSetFieldValue('MfcStatus', '1', 'checkSingle', '1');
        $('#lblMfcTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add Failure Code');
        $('#modal_failure_code').modal({backdrop: 'static', keyboard: false});
    };

    this.edit = function (_failureCodeId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_failureCodeId, _rowRefresh]);
                failureCodeId = _failureCodeId;
                rowRefresh = _rowRefresh;

                const dataMfc = mzAjaxRequest('failure_code.php?failureCodeId='+failureCodeId, 'GET');
                mzSetFieldValue('MfcName', dataMfc['failureCodeName'], 'text');
                mzSetFieldValue('MfcStatus', dataMfc['failureCodeStatus'], 'checkSingle', '1');

                $('#lblMfcTitle').html('<i class="far fa-edit text-white"></i> &nbsp;Edit Failure Code');
                $('#modal_failure_code').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_failureCodeId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_failureCodeId, _rowRefresh]);
                mzAjaxRequest('failure_code.php?failureCodeId='+_failureCodeId, 'PUT', {action: 'deactivate'});
                const tempRow = {failureCodeStatus:'2'};
                if (classFrom.getClassName() === 'MainFailureCode') {
                    classFrom.updateTableFlc(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_failureCodeId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_failureCodeId, _rowRefresh]);
                mzAjaxRequest('failure_code.php?failureCodeId='+_failureCodeId, 'PUT', {action: 'activate'});
                const tempRow = {failureCodeStatus:'1'};
                if (classFrom.getClassName() === 'MainFailureCode') {
                    classFrom.updateTableFlc(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_failureCodeId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_failureCodeId]);
                mzAjaxRequest('failure_code.php?failureCodeId='+_failureCodeId, 'DELETE');
                if (classFrom.getClassName() === 'MainFailureCode') {
                    classFrom.genTableFlc(1);
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