function ModalJabatan() {

    const className = 'ModalJabatan';
    let self = this;
    let jabatanId = '';
    let rowRefresh = '';
    let classFrom;

    this.init = function () {
        const vData = [
            {
                field_id: 'txtMjbDesc',
                type: 'text',
                name: 'Jabatan',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'chkMjbStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        let formValidate = new MzValidate('formMjb');
        formValidate.registerFields(vData);

        $('#formMjb').on('keyup change', function () {
            $('#btnMjbSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_jabatan').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
        });

        $('#btnMjbSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        throw new Error(_ALERT_MSG_VALIDATION);
                    }
                    const statusVal = $("input[name='chkMjbStatus']").is(":checked") ? '1' : '2';
                    const data = {
                        jabatanDesc: $('#txtMjbDesc').val(),
                        jabatanStatus: statusVal
                    };

                    let tempRow = {};
                    if (jabatanId === '') {
                        jabatanId = mzAjaxRequest('jabatan.php', 'POST', data);
                        if (classFrom.getClassName() === 'MainJabatan') {
                            tempRow['jabatanId'] = jabatanId;
                            tempRow['jabatanDesc'] = $('#txtMjbDesc').val();
                            tempRow['jabatanStatus'] = statusVal;
                            classFrom.addTableJbt(tempRow);
                        }
                    } else {
                        data['action'] = 'update';
                        mzAjaxRequest('jabatan.php?jabatanId='+jabatanId, 'PUT', data);
                        if (classFrom.getClassName() === 'MainJabatan') {
                            tempRow['jabatanId'] = jabatanId;
                            tempRow['jabatanDesc'] = $('#txtMjbDesc').val();
                            tempRow['jabatanStatus'] = statusVal;
                            classFrom.updateTableJbt(tempRow, rowRefresh);
                        }
                    }
                    $('#modal_jabatan').modal('hide');
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.add = function () {
        jabatanId = '';
        rowRefresh = '';

        $('#lblMjbTitle').html('<i class="fas fa-plus"></i> &nbsp;Tambah Jabatan');
        $('#btnMjbSubmit').html('<i class="far fa-paper-plane ml-1"></i> Hantar');
        $('#btnMjbSubmit').attr('disabled', true);
        $('#modal_jabatan').modal({backdrop: 'static', keyboard: false});
    };

    this.edit = function (_jabatanId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_jabatanId, _rowRefresh]);
                jabatanId = _jabatanId;
                rowRefresh = _rowRefresh;

                const dataMjb = mzAjaxRequest('jabatan.php?jabatanId='+jabatanId, 'GET');
                mzSetFieldValue('MjbDesc', dataMjb['jabatanDesc'], 'text');
                mzSetFieldValue('MjbStatus', dataMjb['jabatanStatus'], 'checkSingle', '1');

                $('#lblMjbTitle').html('<i class="far fa-edit"></i> &nbsp;Kemaskini Jabatan');
                $('#btnMjbSubmit').html('<i class="fas fa-highlighter ml-1"></i> Kemaskini');
                $('#btnMjbSubmit').attr('disabled', true);
                $('#modal_jabatan').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_jabatanId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_jabatanId, _rowRefresh]);
                mzAjaxRequest('jabatan.php?jabatanId='+_jabatanId, 'PUT', {action: 'deactivate'});
                const tempRow = {jabatanStatus:'2'};
                if (classFrom.getClassName() === 'MainJabatan') {
                    classFrom.updateTableJbt(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_jabatanId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_jabatanId, _rowRefresh]);
                mzAjaxRequest('jabatan.php?jabatanId='+_jabatanId, 'PUT', {action: 'activate'});
                const tempRow = {jabatanStatus:'1'};
                if (classFrom.getClassName() === 'MainJabatan') {
                    classFrom.updateTableJbt(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_jabatanId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_jabatanId, _rowRefresh]);
                mzAjaxRequest('jabatan.php?jabatanId='+_jabatanId, 'DELETE');
                if (classFrom.getClassName() === 'MainJabatan') {
                    classFrom.deleteTableJbt(_rowRefresh);
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