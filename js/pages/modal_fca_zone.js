function ModalFcaZone () {

    const className = 'ModalFcaZone';
    let self = this;
    let formValidate;
    let classFrom;
    let refSite;
    let fcaZoneId;
    let modalConfirmDeleteClass;

    this.init = function () {
        mzOptionV2('optMfzSite', refSite, 'Select Site *', 'siteName', {siteStatus: 1}, 'required');

        const vData = [
            {
                field_id: 'optMfzSite',
                type: 'select',
                name: 'Site',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMfzName',
                type: 'text',
                name: 'Zone Name',
                validator: {
                    notEmpty: true,
                    maxLength: 30
                }
            },
            {
                field_id: 'radMfzStatus',
                type: 'radio',
                name: 'Status',
                validator: {
                    notEmptyCheck: true
                }
            }
        ];

        formValidate = new MzValidate('formMfz');
        formValidate.registerFields(vData);

        $('#btnMfzSubmit').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const data = {
                            siteId: parseInt($('#optMfzSite').val()),
                            fcaZoneName: $('#txtMfzName').val(),
                            fcaZoneStatus: $("input[name='radMfzStatus']:checked").val()
                        };
                        mzAjaxRequest2('fca_zone', 'POST', data);
                        classFrom.genTable();
                        $('#modal_fca_zone').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        $('#btnMfzSave').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const data = {
                            siteId: parseInt($('#optMfzSite').val()),
                            fcaZoneName: $('#txtMfzName').val(),
                            fcaZoneStatus: $("input[name='radMfzStatus']:checked").val()
                        };
                        mzAjaxRequest2('fca_zone/'+fcaZoneId, 'PUT', data);
                        classFrom.genTable();
                        $('#modal_fca_zone').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        $('#btnMfzDelete').on('click', function () {
            try {
                $('#modal_fca_zone').modal('hide');
                modalConfirmDeleteClass.delete(fcaZoneId, self);
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
                $('#btnMfzSubmit').show();
                $('#btnMfzDelete, #btnMfzSave').hide();
                $('#h4MfzTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add New FCA Zone');
                $('#modal_fca_zone').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.edit = function (_fcaZoneId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_fcaZoneId]);
                fcaZoneId = _fcaZoneId;
                formValidate.clearValidation();
                const data = mzAjaxRequest2('fca_zone/'+fcaZoneId, 'GET');
                mzSetFieldValue('MfzSite', data['siteId'], 'select');
                mzSetFieldValue('MfzName', data['fcaZoneName'], 'text');
                mzSetFieldValue('MfzStatus', data['fcaZoneStatus'], 'radio');
                $('#btnMfzSubmit').hide();
                $('#btnMfzDelete, #btnMfzSave').show();
                $('#h4MfzTitle').html('<i class="fas fa-edit text-white"></i> &nbsp;Edit FCA Zone');
                $('#modal_fca_zone').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.delete = function (_fcaZoneId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_fcaZoneId]);
                mzAjaxRequest2('fca_zone/'+_fcaZoneId, 'DELETE');
                classFrom.genTable();
                $('#modal_fca_zone').modal('hide');
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

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}