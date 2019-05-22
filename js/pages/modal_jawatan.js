function ModalJawatan() {

    const className = 'ModalJawatan';
    let self = this;
    let jawatanId = '';
    let rowRefresh = '';
    let classFrom;

    this.init = function () {
        const vData = [
            {
                field_id: 'txtMjwDesc',
                type: 'text',
                name: 'Jawatan',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'chkMjwStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        let formValidate = new MzValidate('formMjw');
        formValidate.registerFields(vData);

        $('#formMjw').on('keyup change', function () {
            $('#btnMjwSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_jawatan').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
        });

        $('#btnMjwSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        throw new Error(_ALERT_MSG_VALIDATION);
                    }
                    const statusVal = $("input[name='chkMjwStatus']").is(":checked") ? '1' : '2';
                    const data = {
                        jawatanDesc: $('#txtMjwDesc').val(),
                        jawatanStatus: statusVal
                    };

                    let tempRow = {};
                    if (jawatanId === '') {
                        jawatanId = mzAjaxRequest('jawatan.php', 'POST', data);
                        if (classFrom.getClassName() === 'MainJawatan') {
                            tempRow['jawatanId'] = jawatanId;
                            tempRow['jawatanDesc'] = $('#txtMjwDesc').val();
                            tempRow['jawatanStatus'] = statusVal;
                            classFrom.addTableJwt(tempRow);
                        }
                    } else {
                        data['action'] = 'update';
                        mzAjaxRequest('jawatan.php?jawatanId='+jawatanId, 'PUT', data);
                        if (classFrom.getClassName() === 'MainJawatan') {
                            tempRow['jawatanId'] = jawatanId;
                            tempRow['jawatanDesc'] = $('#txtMjwDesc').val();
                            tempRow['jawatanStatus'] = statusVal;
                            classFrom.updateTableJwt(tempRow, rowRefresh);
                        }
                    }
                    $('#modal_jawatan').modal('hide');
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.add = function () {
        jawatanId = '';
        rowRefresh = '';

        $('#lblMjwTitle').html('<i class="fas fa-plus"></i> &nbsp;Tambah Jawatan');
        $('#btnMjwSubmit').html('<i class="far fa-paper-plane ml-1"></i> Hantar');
        $('#btnMjwSubmit').attr('disabled', true);
        $('#modal_jawatan').modal({backdrop: 'static', keyboard: false});
    };

    this.edit = function (_jawatanId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_jawatanId, _rowRefresh]);
                jawatanId = _jawatanId;
                rowRefresh = _rowRefresh;

                const dataMjw = mzAjaxRequest('jawatan.php?jawatanId='+jawatanId, 'GET');
                mzSetFieldValue('MjwDesc', dataMjw['jawatanDesc'], 'text');
                mzSetFieldValue('MjwStatus', dataMjw['jawatanStatus'], 'checkSingle', '1');

                $('#lblMjwTitle').html('<i class="far fa-edit"></i> &nbsp;Kemaskini Jawatan');
                $('#btnMjwSubmit').html('<i class="fas fa-highlighter ml-1"></i> Kemaskini');
                $('#btnMjwSubmit').attr('disabled', true);
                $('#modal_jawatan').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_jawatanId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_jawatanId, _rowRefresh]);
                mzAjaxRequest('jawatan.php?jawatanId='+_jawatanId, 'PUT', {action: 'deactivate'});
                const tempRow = {jawatanStatus:'2'};
                if (classFrom.getClassName() === 'MainJawatan') {
                    classFrom.updateTableJwt(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_jawatanId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_jawatanId, _rowRefresh]);
                mzAjaxRequest('jawatan.php?jawatanId='+_jawatanId, 'PUT', {action: 'activate'});
                const tempRow = {jawatanStatus:'1'};
                if (classFrom.getClassName() === 'MainJawatan') {
                    classFrom.updateTableJwt(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_jawatanId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_jawatanId, _rowRefresh]);
                mzAjaxRequest('jawatan.php?jawatanId='+_jawatanId, 'DELETE');
                if (classFrom.getClassName() === 'MainJawatan') {
                    classFrom.deleteTableJwt(_rowRefresh);
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