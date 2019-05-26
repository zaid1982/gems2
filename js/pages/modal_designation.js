function ModalDesignation() {

    const className = 'ModalDesignation';
    let self = this;
    let designationId = '';
    let rowRefresh = '';
    let classFrom;

    this.init = function () {
        const vData = [
            {
                field_id: 'txtMdgDesc',
                type: 'text',
                name: 'Designation',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'chkMdgStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        let formValidate = new MzValidate('formMdg');
        formValidate.registerFields(vData);

        $('#formMdg').on('keyup change', function () {
            $('#btnMdgSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_designation').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
        });

        $('#btnMdgSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        throw new Error(_ALERT_MSG_VALIDATION);
                    }
                    const statusVal = $("input[name='chkMdgStatus']").is(":checked") ? '1' : '2';
                    const data = {
                        designationDesc: $('#txtMdgDesc').val(),
                        designationStatus: statusVal
                    };

                    let tempRow = {};
                    if (designationId === '') {
                        designationId = mzAjaxRequest('designation.php', 'POST', data);
                        if (classFrom.getClassName() === 'MainDesignation') {
                            tempRow['designationId'] = designationId;
                            tempRow['designationDesc'] = $('#txtMdgDesc').val();
                            tempRow['designationStatus'] = statusVal;
                            classFrom.addTableDsg(tempRow);
                        }
                    } else {
                        data['action'] = 'update';
                        mzAjaxRequest('designation.php?designationId='+designationId, 'PUT', data);
                        if (classFrom.getClassName() === 'MainDesignation') {
                            tempRow['designationId'] = designationId;
                            tempRow['designationDesc'] = $('#txtMdgDesc').val();
                            tempRow['designationStatus'] = statusVal;
                            classFrom.updateTableDsg(tempRow, rowRefresh);
                        }
                    }
                    $('#modal_designation').modal('hide');
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.add = function () {
        designationId = '';
        rowRefresh = '';

        $('#lblMdgTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add Designation');
        $('#btnMdgSubmit').html('<i class="far fa-paper-plane ml-1"></i> Submit');
        $('#btnMdgSubmit').attr('disabled', true);
        $('#modal_designation').modal({backdrop: 'static', keyboard: false});
    };

    this.edit = function (_designationId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_designationId, _rowRefresh]);
                designationId = _designationId;
                rowRefresh = _rowRefresh;

                const dataMdg = mzAjaxRequest('designation.php?designationId='+designationId, 'GET');
                mzSetFieldValue('MdgDesc', dataMdg['designationDesc'], 'text');
                mzSetFieldValue('MdgStatus', dataMdg['designationStatus'], 'checkSingle', '1');

                $('#lblMdgTitle').html('<i class="far fa-edit text-white"></i> &nbsp;Edit Designation');
                $('#btnMdgSubmit').html('<i class="far fa-paper-plane ml-1"></i> Submit');
                $('#btnMdgSubmit').attr('disabled', true);
                $('#modal_designation').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_designationId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_designationId, _rowRefresh]);
                mzAjaxRequest('designation.php?designationId='+_designationId, 'PUT', {action: 'deactivate'});
                const tempRow = {designationStatus:'2'};
                if (classFrom.getClassName() === 'MainDesignation') {
                    classFrom.updateTableDsg(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_designationId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_designationId, _rowRefresh]);
                mzAjaxRequest('designation.php?designationId='+_designationId, 'PUT', {action: 'activate'});
                const tempRow = {designationStatus:'1'};
                if (classFrom.getClassName() === 'MainDesignation') {
                    classFrom.updateTableDsg(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_designationId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_designationId, _rowRefresh]);
                mzAjaxRequest('designation.php?designationId='+_designationId, 'DELETE');
                if (classFrom.getClassName() === 'MainDesignation') {
                    classFrom.deleteTableDsg(_rowRefresh);
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