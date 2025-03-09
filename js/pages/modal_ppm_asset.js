function ModalPpmAsset () {

    const className = 'ModalPpmAsset';
    let self = this;
    let formValidate;
    let classFrom;
    let submitType = '';
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refPpmGroup;
    let ppmId;
    let contractId;
    let siteId;

    const vData = [
        {
            field_id: 'txtMpaName',
            type: 'text',
            name: 'PPM Asset Group Name',
            validator: {
                notEmpty: true,
                maxLength: 200
            }
        },
        {
            field_id: 'txaMpaDesc',
            type: 'textarea',
            name: 'Description',
            validator: {
                notEmpty: false,
                maxLength: 1000
            }
        },
        {
            field_id: 'optMpaAssetGroup',
            type: 'select',
            name: 'Asset Group',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optMpaAssetCategory',
            type: 'select',
            name: 'Asset Category',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optMpaAssetType',
            type: 'select',
            name: 'Asset Type',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optMpaChecklistId',
            type: 'select',
            name: 'PPM Checklist',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optMpaPpmGroupId',
            type: 'select',
            name: 'PPM Executor Group',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'txtMpaFrequency',
            type: 'text',
            name: 'PPM Frequency',
            validator: {
                notEmpty: false
            }
        },
        {
            field_id: 'txtMpaPpmDateStart',
            type: 'text',
            name: 'Start Cycle Date',
            validator: {
                notEmpty: true,
                maxLength: 30
            }
        }
    ];

    this.init = function () {
        mzDateSetMin('txtMpaPpmDateStart', moment().format('YYYY-MM-DD'));
        mzOption('optMpaAssetGroup', refAssetGroup, 'Select Asset Group', 'assetGroupId', 'assetGroupName', {assetGroupStatus: '1'}, 'required');

        $('#optMpaAssetGroup').on('change', function () {
            const id = $(this).val();
            try {
                mzOptionStop('optMpaAssetCategory', refAssetCategory, 'Select Asset Category', 'assetCategoryId', 'assetCategoryName', {assetGroupId: id, assetCategoryStatus: '1'}, 'required');
                mzDisableSelect('optMpaAssetCategory', false);
                mzDisableSelect('optMpaAssetType', true);
                mzDisableSelect('optMpaChecklistId', true);
                mzDisableSelect('optMpaPpmGroupId', true);
                mzSetFieldValue('txtMpaFrequency', '', 'text');
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#optMpaAssetCategory').on('change', function () {
            const id = $(this).val();
            try {
                mzOptionStop('optMpaAssetType', refAssetType, 'Select Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: id, assetTypeStatus: '1'}, 'required');
                mzDisableSelect('optMpaAssetType', false);
                mzDisableSelect('optMpaChecklistId', true);
                mzDisableSelect('optMpaPpmGroupId', true);
                mzSetFieldValue('txtMpaFrequency', '', 'text');
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#optMpaAssetType').on('change', function () {
            const id = $(this).val();
            //ShowLoader(); setTimeout(function () { try {
            try {
                const refChecklist = mzAjaxRequest('checklist.php?assetTypeId='+id, 'GET');
                mzOptionStop('optMpaChecklistId', refChecklist, 'Select PPM Checklist', 'checklistId', 'checklistName', {checklistStatus: '1'}, 'required', true);
                mzOptionStop('optMpaPpmGroupId', refPpmGroup, 'Select PPM Executor Group', 'ppmGroupId', 'ppmGroupName', {roleId: '5', siteId: siteId, ppmGroupStatus: '1'}, 'required');
                mzDisableSelect('optMpaChecklistId', false);
                mzDisableSelect('optMpaPpmGroupId', false);
                mzSetFieldValue('txtMpaFrequency', '', 'text');
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            //} catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); } HideLoader(); }, 200);
        });

        $('#optMpaChecklistId').on('change', function () {
            const id = $(this).val();
            try {
                const frequency = mzAjaxRequest('checklist.php?type=frequency&checklistId='+id, 'GET');
                mzSetFieldValue('MpaFrequency', frequency, 'text');
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#btnMpaSubmit').on('click', function () {
            try {
                if (!formValidate.validateNow()) {
                    toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                } else {
                    let data = {
                        ppmName: mzNullString('txtMpaName'),
                        ppmRemark: mzNullString('txaMpaDesc'),
                        assetTypeId: mzNullInt('optMpaAssetType'),
                        checklistId: mzNullInt('optMpaChecklistId'),
                        ppmGroupId: mzNullInt('optMpaPpmGroupId'),
                        ppmFrequency: mzNullString('txtMpaFrequency'),
                        ppmDateStart: mzConvertDate($('#txtMpaPpmDateStart').val())
                    };
                    ShowLoader(); setTimeout(function () {
                        if (submitType === 'add') {
                            data['contractId'] = contractId;
                            data['ppmIsGroup'] = 1;
                            data['ppmStatus'] = 11;
                            mzFetch('ppm_v3/ppmAssetGroup', 'POST', data).then(res => {
                                classFrom.genTable();
                                $('#modal_ppm_asset').modal('hide');
                            }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                        } else if (submitType === 'put') {
                            mzFetch('ppm_v3/ppmAssetGroup/'+ppmId, 'PUT', data).then(res => {
                                classFrom.load(ppmId, true);
                                classFrom.setIsUpdate(true);
                                $('#modal_ppm_asset').modal('hide');
                            }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                        }
                    }, 200);
                }
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        formValidate = new MzValidate('formMpa');
        formValidate.registerFields(vData);
    };

    this.resetOption = function () {
        mzOptionStopClear('optMpaAssetCategory', 'Select Asset Category', 'required');
        mzDisableSelect('optMpaAssetCategory', true);
        mzOptionStopClear('optMpaAssetType', 'Select Asset Type', 'required');
        mzDisableSelect('optMpaAssetType', true);
        mzOptionStopClear('optMpaChecklistId', 'Select PPM Checklist', 'required');
        mzDisableSelect('optMpaChecklistId', true);
        mzOptionStopClear('optMpaPpmGroupId', 'Select PPM Executor Group', 'required');
        mzDisableSelect('optMpaPpmGroupId', true);
        $('#txtMpaPpmDateStart').prop('disable', false);
    };

    this.add = function () {
        try {
            submitType = 'add';
            formValidate.clearValidation();
            self.resetOption();
            mzDisableSelect('optMpaAssetGroup', false);
            formValidate.enableField('optMpaAssetGroup');
            formValidate.enableField('optMpaAssetCategory');
            formValidate.enableField('optMpaAssetType');
            formValidate.enableField('optMpaPpmGroupId');
            formValidate.enableField('txtMpaPpmDateStart');
            $('#h4MpaTitle').html('<i class="fa-duotone fa-plus mr-2"></i>Add PPM Asset Group');
            $('#modal_ppm_asset').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
        } catch (e) { toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR); }
    };

    this.edit = function (_ppmId) {
        try {
            mzCheckFuncParam([_ppmId]);
            ppmId = _ppmId;
            submitType = 'put';
            ShowLoader(); setTimeout(function () {
                mzFetch('ppm_v3/'+ppmId, 'GET').then(res => {
                    formValidate.clearValidation();
                    self.resetOption();
                    console.log(res);
                    mzSetFieldValue('txtMpaName', res['ppmName']);
                    mzSetFieldValue('txaMpaDesc', res['ppmRemark']);
                    const assetTypeId = res['assetTypeId'];
                    const assetCategoryId = refAssetType[assetTypeId]['assetCategoryId'];
                    const assetGroupId = refAssetCategory[assetCategoryId]['assetGroupId'];
                    mzSetFieldValue('optMpaAssetGroup', assetGroupId);
                    $('#optMpaAssetGroup').trigger('change');
                    mzSetFieldValue('optMpaAssetCategory', assetCategoryId);
                    $('#optMpaAssetCategory').trigger('change');
                    mzSetFieldValue('optMpaAssetType', assetTypeId);
                    $('#optMpaAssetType').trigger('change');
                    mzSetFieldValue('optMpaChecklistId', res['checklistId']);
                    mzSetFieldValue('optMpaPpmGroupId', res['ppmGroupId']);
                    $('#optMpaChecklistId').trigger('change');
                    mzSetFieldValue('MpaPpmDateStart', res['ppmDateStart'], 'date2');
                    const isDisable = res['ppmStatus'] !== 11;
                    mzDisableSelect('optMpaAssetGroup', isDisable);
                    mzDisableSelect('optMpaAssetCategory', isDisable);
                    mzDisableSelect('optMpaAssetType', isDisable);
                    mzDisableSelect('optMpaChecklistId', isDisable);
                    mzDisableSelect('optMpaPpmGroupId', isDisable);
                    $('#txtMpaPpmDateStart').prop('disabled', isDisable);
                    formValidate.disableField('optMpaAssetGroup', isDisable);
                    formValidate.disableField('optMpaAssetCategory', isDisable);
                    formValidate.disableField('optMpaAssetType', isDisable);
                    formValidate.disableField('optMpaChecklistId', isDisable);
                    formValidate.disableField('optMpaPpmGroupId', isDisable);
                    formValidate.disableField('txtMpaPpmDateStart', isDisable);
                    $('#h4MpaTitle').html('<i class="fa-duotone fa-edit mr-2"></i>Edit PPM Asset Group');
                    $('#modal_ppm_asset').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
                }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
            }, 200);
        } catch (e) { toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR); }
    };

    this.delete = function (_ppmId) {
        try {
            mzCheckFuncParam([_ppmId]);
            ShowLoader(); setTimeout(function () {
                mzFetch('ppm_v3/'+_ppmId, 'DELETE').then(res => {
                    classFrom.setIsUpdate(true);
                    classFrom.hideSection();
                    $('#modal_ppm_asset').modal('hide');
                }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
            }, 200);
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefAssetCategory = function (_refAssetCategory) {
        refAssetCategory = _refAssetCategory;
    };

    this.setRefAssetType = function (_refAssetType) {
        refAssetType = _refAssetType;
    };

    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };

    this.setContractId = function (_contractId) {
        contractId = _contractId;
    };

    this.setSiteId = function (_siteId) {
        siteId = _siteId;
    };
}