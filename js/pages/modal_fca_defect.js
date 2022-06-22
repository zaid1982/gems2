function ModalFcaDefect () {

    const className = 'ModalFcaDefect';
    let self = this;
    let formValidate;
    let classFrom;
    let fcaDefectCategoryId;
    let modalConfirmDeleteClass;

    this.init = function () {

        const vData = [
            {
                field_id: 'txtMfdName',
                type: 'text',
                name: 'Defect Category Name',
                validator: {
                    notEmpty: true,
                    maxLength: 30
                }
            },
            {
                field_id: 'radMfdStatus',
                type: 'radio',
                name: 'Status',
                validator: {
                    notEmptyCheck: true
                }
            }
        ];

        formValidate = new MzValidate('formMfd');
        formValidate.registerFields(vData);

        $('#btnMfdSubmit').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const data = {
                            fcaDefectCategoryName: $('#txtMfdName').val(),
                            fcaDefectCategoryStatus: $("input[name='radMfdStatus']:checked").val()
                        };
                        mzAjaxRequest2('fca_defect_category', 'POST', data);
                        classFrom.genTableDefect();
                        $('#modal_fca_defect').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        $('#btnMfdSave').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const data = {
                            fcaDefectCategoryName: $('#txtMfdName').val(),
                            fcaDefectCategoryStatus: $("input[name='radMfdStatus']:checked").val()
                        };
                        mzAjaxRequest2('fca_defect_category/'+fcaDefectCategoryId, 'PUT', data);
                        classFrom.genTableDefect();
                        $('#modal_fca_defect').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        $('#btnMfdDelete').on('click', function () {
            try {
                $('#modal_fca_defect').modal('hide');
                modalConfirmDeleteClass.delete(fcaDefectCategoryId, self);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
        });
    };

    this.add = function () {
        ShowLoader();
        setTimeout(function () {
            try {
                formValidate.clearValidation();
                $('#btnMfdSubmit').show();
                $('#btnMfdDelete, #btnMfdSave').hide();
                $('#h4MfdTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add New FCA Defect Category');
                $('#modal_fca_defect').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.edit = function (_fcaDefectCategoryId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_fcaDefectCategoryId]);
                fcaDefectCategoryId = _fcaDefectCategoryId;
                formValidate.clearValidation();
                const data = mzAjaxRequest2('fca_defect_category/'+fcaDefectCategoryId, 'GET');
                mzSetFieldValue('MfdName', data['fcaDefectCategoryName'], 'text');
                mzSetFieldValue('MfdStatus', data['fcaDefectCategoryStatus'], 'radio');
                $('#btnMfdSubmit').hide();
                $('#btnMfdDelete, #btnMfdSave').show();
                $('#h4MfdTitle').html('<i class="fas fa-edit text-white"></i> &nbsp;Edit FCA Defect Category');
                $('#modal_fca_defect').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.delete = function (_fcaDefectCategoryId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_fcaDefectCategoryId]);
                mzAjaxRequest2('fca_defect_category/'+_fcaDefectCategoryId, 'DELETE');
                classFrom.genTableDefect();
                $('#modal_fca_defect').modal('hide');
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

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}