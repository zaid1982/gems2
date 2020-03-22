function ModalSeverity() {

    const className = 'ModalSeverity';
    let self = this;
    let severityId = '';
    let rowRefresh = '';
    let classFrom;

    this.init = function () {
        const vData = [
            {
                field_id: 'txtMsvName',
                type: 'text',
                name: 'Severity',
                validator: {
                    notEmpty: true,
                    maxLength: 100
                }
            },
            {
                field_id: 'chkMsvStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        let formValidate = new MzValidate('formMsv');
        formValidate.registerFields(vData);

        $('#formMsv').on('keyup change', function () {
            $('#btnMsvSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_severity').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMsvSubmit').attr('disabled', true);
        });

        $('#btnMsvSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const txtName = $('#txtMsvName').val();
                        const statusVal = $("input[name='chkMsvStatus']").is(":checked") ? '1' : '2';
                        const data = {
                            severityName: txtName,
                            severityStatus: statusVal
                        };

                        let tempRow = {};
                        if (severityId === '') {
                            severityId = mzAjaxRequest('severity.php', 'POST', data);
                            if (classFrom.getClassName() === 'MainSeverity') {
                                tempRow['severityId'] = severityId;
                                tempRow['severityName'] = txtName;
                                tempRow['severityStatus'] = statusVal;
                                classFrom.addTableSvr(tempRow);
                            }
                        } else {
                            data['action'] = 'update';
                            mzAjaxRequest('severity.php?severityId='+severityId, 'PUT', data);
                            if (classFrom.getClassName() === 'MainSeverity') {
                                tempRow['severityId'] = severityId;
                                tempRow['severityName'] = txtName;
                                tempRow['severityStatus'] = statusVal;
                                classFrom.updateTableSvr(tempRow, rowRefresh);
                            }
                        }
                        $('#modal_severity').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.add = function () {
        severityId = '';
        rowRefresh = '';

        mzSetFieldValue('MsvStatus', '1', 'checkSingle', '1');
        $('#lblMsvTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add Severity');
        $('#modal_severity').modal({backdrop: 'static', keyboard: false});
    };

    this.edit = function (_severityId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_severityId, _rowRefresh]);
                severityId = _severityId;
                rowRefresh = _rowRefresh;

                const dataMsv = mzAjaxRequest('severity.php?severityId='+severityId, 'GET');
                mzSetFieldValue('MsvName', dataMsv['severityName'], 'text');
                mzSetFieldValue('MsvStatus', dataMsv['severityStatus'], 'checkSingle', '1');

                $('#lblMsvTitle').html('<i class="far fa-edit text-white"></i> &nbsp;Edit Severity');
                $('#modal_severity').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_severityId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_severityId, _rowRefresh]);
                mzAjaxRequest('severity.php?severityId='+_severityId, 'PUT', {action: 'deactivate'});
                const tempRow = {severityStatus:'2'};
                if (classFrom.getClassName() === 'MainSeverity') {
                    classFrom.updateTableSvr(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_severityId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_severityId, _rowRefresh]);
                mzAjaxRequest('severity.php?severityId='+_severityId, 'PUT', {action: 'activate'});
                const tempRow = {severityStatus:'1'};
                if (classFrom.getClassName() === 'MainSeverity') {
                    classFrom.updateTableSvr(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_severityId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_severityId]);
                mzAjaxRequest('severity.php?severityId='+_severityId, 'DELETE');
                if (classFrom.getClassName() === 'MainSeverity') {
                    classFrom.genTableSvr(1);
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