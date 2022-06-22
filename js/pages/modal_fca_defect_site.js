function ModalFcaDefectSite () {

    const className = 'ModalFcaDefectSite';
    let self = this;
    let formValidate;
    let classFrom;
    let refSite;
    let refDefectCategory;
    let fcaDefectCategoryId;

    this.init = function () {
        mzOptionV2('optMfySite', refSite, 'Select Site *', 'siteName', {siteStatus: 1}, 'required');

        const vData = [
            {
                field_id: 'optMfySite',
                type: 'select',
                name: 'Site',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMfyDefectCategory',
                type: 'select',
                name: 'Defect Category',
                validator: {
                    notEmpty: true
                }
            }
        ];

        formValidate = new MzValidate('formMfy');
        formValidate.registerFields(vData);

        $('#btnMfySubmit').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const data = {
                            siteId: parseInt($('#optMfySite').val()),
                            fcaDefectCategoryId: $('#optMfyDefectCategory').val()
                        };
                        mzAjaxRequest2('fca_defect_category_site', 'POST', data);
                        classFrom.genTableSite();
                        $('#modal_fca_defect_site').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
    };

    this.add = function (_siteId) {
        ShowLoader();
        setTimeout(function () {
            try {
                formValidate.clearValidation();
                mzOptionStopV2('optMfyDefectCategory', refDefectCategory, 'Select Defect Category *', 'fcaDefectCategoryName', {fcaDefectCategoryStatus: 1}, 'required');
                if (typeof _siteId !== 'undefined') {
                    mzSetFieldValue('MfySite', _siteId, 'select');
                }
                $('#modal_fca_defect_site').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.delete = function (_deleteId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_deleteId]);
                const idArr = _deleteId.split('_');
                mzAjaxRequest2('fca_defect_category_site/'+idArr[0]+'/'+idArr[1], 'DELETE');
                classFrom.genTableSite();
                $('#modal_fca_defect_site').modal('hide');
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

    this.setRefDefectCategory = function (_refDefectCategory) {
        refDefectCategory = _refDefectCategory;
    };
}