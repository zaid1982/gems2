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
            name: 'PPM Asset Group Name', // This maps to ppm_set_name
            validator: {
                notEmpty: true,
                maxLength: 200
            }
        },
        {
            field_id: 'txaMpaDesc', // This was 'ppmRemark' in old JS, now maps to ppm_set_desc
            type: 'textarea',
            name: 'Description',
            validator: {
                notEmpty: false,
                maxLength: 1000
            }
        },
        {
            field_id: 'optMpaAssetGroup', // This is for ppm_set.asset_type_id
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
            field_id: 'optMpaAssetType', // This is for ppm_set.asset_type_id
            type: 'select',
            name: 'Asset Type',
            validator: {
                notEmpty: true
            }
        },
        // Removed 'optMpaChecklistId' as checklist is not directly on ppm_set
        // Removed 'txtMpaFrequency' as frequency is not directly on ppm_set
        {
            field_id: 'optMpaPpmGroupId', // This is for ppm_set.ppm_group_id
            type: 'select',
            name: 'PPM Executor Group',
            validator: {
                notEmpty: true
            }
        },
        // Removed 'txtMpaPpmDateStart' as dateStart is not directly on ppm_set
    ];

    this.init = function () {
        mzDateSetMin('txtMpaPpmDateStart', moment().format('YYYY-MM-DD')); // This field should be removed from HTML if it's not needed.
        mzOption('optMpaAssetGroup', refAssetGroup, 'Select Asset Group', 'assetGroupId', 'assetGroupName', {assetGroupStatus: '1'}, 'required');

        $('#optMpaAssetGroup').on('change', function () {
            const id = $(this).val();
            try {
                mzOptionStop('optMpaAssetCategory', refAssetCategory, 'Select Asset Category', 'assetCategoryId', 'assetCategoryName', {assetGroupId: id, assetCategoryStatus: '1'}, 'required');
                mzDisableSelect('optMpaAssetCategory', false);
                mzDisableSelect('optMpaAssetType', true);
                // mzDisableSelect('optMpaChecklistId', true); // REMOVED
                // mzDisableSelect('optMpaPpmGroupId', true); // No need to disable ppmGroupId based on AssetCategory
                // mzSetFieldValue('txtMpaFrequency', '', 'text'); // REMOVED
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#optMpaAssetCategory').on('change', function () {
            const id = $(this).val();
            try {
                mzOptionStop('optMpaAssetType', refAssetType, 'Select Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: id, assetTypeStatus: '1'}, 'required');
                mzDisableSelect('optMpaAssetType', false);
                // mzDisableSelect('optMpaChecklistId', true); // REMOVED
                // mzDisableSelect('optMpaPpmGroupId', true); // No need to disable ppmGroupId based on AssetType
                // mzSetFieldValue('txtMpaFrequency', '', 'text'); // REMOVED
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#optMpaAssetType').on('change', function () {
            const id = $(this).val(); // assetTypeId
            try {
                // Only enable the PPM Executor Group now
                mzOptionStop('optMpaPpmGroupId', refPpmGroup, 'Select PPM Executor Group', 'ppmGroupId', 'ppmGroupName', {roleId: '5', siteId: siteId, ppmGroupStatus: '1'}, 'required');
                mzDisableSelect('optMpaPpmGroupId', false);
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#btnMpaSubmit').on('click', function () {
            try {
                if (!formValidate.validateNow()) {
                    toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                } else {
                    let data = {
                        ppmSetName: mzNullString('txtMpaName'), // Map ppmName to ppmSetName
                        ppmSetDesc: mzNullString('txaMpaDesc'), // Map ppmRemark to ppmSetDesc
                        assetTypeId: mzNullInt('optMpaAssetType'),
                        ppmGroupId: mzNullInt('optMpaPpmGroupId'),
                        // Removed: checklistId, ppmFrequency, ppmDateStart
                        // Removed: ppmIsGroup, ppmStatus (these are ppm table fields)
                        // Removed: contractId (passed implicitly by API endpoint ppm.php)
                    };
                    ShowLoader(); setTimeout(function () {
                        if (submitType === 'add') {
                            // --- NEW API CALL FOR CREATING ppm_set ---
                            mzFetch('ppm.php?action=create_ppm_set', 'POST', data).then(res => { // Corrected URL and action
                                toastr['success']('PPM Set "' + data.ppmSetName + '" successfully created!', _ALERT_TITLE_SUCCESS); // Updated success message
                                classFrom.genTable();
                                $('#modal_ppm_asset').modal('hide');
                            }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                        } else if (submitType === 'put') {
                            // --- NEW API CALL FOR UPDATING ppm_set ---
                            // This logic will be implemented later, but ensure it points to the correct new endpoint and data.
                            // For now, let's just make it throw an error to prevent accidental incorrect updates.
                            toastr['error']('Edit functionality for PPM Set not yet implemented!', _ALERT_TITLE_ERROR);
                            HideLoader(); // Hide loader if we're throwing an error here.
                            // mzFetch('ppm.php?action=update_ppm_set&ppmSetId='+ppmId, 'PUT', data).then(res => { // Example future call
                            //     classFrom.load(ppmId, true);
                            //     classFrom.setIsUpdate(true);
                            //     $('#modal_ppm_asset').modal('hide');
                            // }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
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
        // mzOptionStopClear('optMpaChecklistId', 'Select PPM Checklist', 'required'); // REMOVED
        // mzDisableSelect('optMpaChecklistId', true); // REMOVED
        mzOptionStopClear('optMpaPpmGroupId', 'Select PPM Executor Group', 'required');
        mzDisableSelect('optMpaPpmGroupId', true);
        // $('#txtMpaPpmDateStart').prop('disable', false); // REMOVED
        // Also ensure txtMpaName and txaMpaDesc are cleared if mzSetFieldValue does not reset them for empty values.
        mzSetFieldValue('txtMpaName', '', 'text');
        mzSetFieldValue('txaMpaDesc', '', 'text');
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