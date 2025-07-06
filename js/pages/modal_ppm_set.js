function ModalPpmSet () {

    const className = 'ModalPpmSet';
    let self = this;
    let formValidate;
    let classFrom; // Reference to MainPpmSet class
    let submitType = ''; // 'add' or 'put'
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refPpmGroup;
    let ppmSetId; // Renamed from ppmId for ppm_set
    let contractId;
    let siteId;

    const vData = [
        {
            field_id: 'txtMpsName', // Renamed ID
            type: 'text',
            name: 'PPM Set Name',
            validator: {
                notEmpty: true,
                maxLength: 200
            }
        },
        {
            field_id: 'txaMpsDesc', // Renamed ID
            type: 'textarea',
            name: 'Description',
            validator: {
                notEmpty: false,
                maxLength: 1000
            }
        },
        {
            field_id: 'optMpsAssetGroup', // Renamed ID
            type: 'select',
            name: 'Asset Group',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optMpsAssetCategory', // Renamed ID
            type: 'select',
            name: 'Asset Category',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optMpsAssetType', // Renamed ID
            type: 'select',
            name: 'Asset Type',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optMpsPpmGroupId', // Renamed ID
            type: 'select',
            name: 'PPM Executor Group',
            validator: {
                notEmpty: true
            }
        }
        // Removed: Checklist ID, Frequency, Date Start - they are not ppm_set fields
    ];

    this.init = function () {
        // No min date for ppm_set, as it doesn't have a dateStart field
        mzOption('optMpsAssetGroup', refAssetGroup, 'Select Asset Group', 'assetGroupId', 'assetGroupName', {assetGroupStatus: '1'}, 'required'); // Renamed ID

        $('#optMpsAssetGroup').on('change', function () { // Renamed ID
            const id = $(this).val();
            try {
                mzOptionStop('optMpsAssetCategory', refAssetCategory, 'Select Asset Category', 'assetCategoryId', 'assetCategoryName', {assetGroupId: id, assetCategoryStatus: '1'}, 'required'); // Renamed ID
                mzDisableSelect('optMpsAssetCategory', false); // Renamed ID
                mzDisableSelect('optMpsAssetType', true); // Renamed ID
                mzDisableSelect('optMpsPpmGroupId', true); // Renamed ID
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#optMpsAssetCategory').on('change', function () { // Renamed ID
            const id = $(this).val();
            try {
                mzOptionStop('optMpsAssetType', refAssetType, 'Select Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: id, assetTypeStatus: '1'}, 'required'); // Renamed ID
                mzDisableSelect('optMpsAssetType', false); // Renamed ID
                mzDisableSelect('optMpsPpmGroupId', true); // Renamed ID
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#optMpsAssetType').on('change', function () { // Renamed ID
            const id = $(this).val(); // assetTypeId
            try {
                mzOptionStop('optMpsPpmGroupId', refPpmGroup, 'Select PPM Executor Group', 'ppmGroupId', 'ppmGroupName', {roleId: '5', siteId: siteId, ppmGroupStatus: '1'}, 'required'); // Renamed ID
                mzDisableSelect('optMpsPpmGroupId', false); // Renamed ID
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#btnMpsSubmit').on('click', function () { // Renamed ID
            try {
                if (!formValidate.validateNow()) {
                    toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                } else {
                    let data = {
                        ppmSetName: mzNullString('txtMpsName'), // Renamed ID
                        ppmSetDesc: mzNullString('txaMpsDesc'), // Renamed ID
                        assetTypeId: mzNullInt('optMpsAssetType'), // Renamed ID
                        ppmGroupId: mzNullInt('optMpsPpmGroupId') // Renamed ID
                    };
                    ShowLoader(); setTimeout(function () {
                        if (submitType === 'add') {
                            // API call for creating a new ppm_set
                            mzFetch('api/ppm.php?action=create_ppm_set', 'POST', data).then(res => {
                                toastr['success']('PPM Set "' + data.ppmSetName + '" successfully created!', _ALERT_TITLE_SUCCESS);
                                classFrom.genTable(); // Tell MainPpmSet to refresh its table
                                $('#modal_ppm_set').modal('hide'); // Hide new modal ID
                            }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                        } else if (submitType === 'put') {
                            // API call for updating an existing ppm_set (to be implemented later)
                            data['ppmSetId'] = ppmSetId; // Add ppmSetId for update
                            mzFetch('api/ppm.php?action=update_ppm_set', 'PUT', data).then(res => {
                                toastr['info']('Edit functionality for PPM Set not yet implemented!', _ALERT_TITLE_INFO); // Temporarily block
                                HideLoader(); // Hide loader if temporarily blocking
                            }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                        }
                    }, 200);
                }
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        formValidate = new MzValidate('formMps'); // Renamed ID
        formValidate.registerFields(vData);
    };

    this.resetOption = function () {
        mzOptionStopClear('optMpsAssetGroup', 'Select Asset Group', 'required'); // Renamed ID
        mzDisableSelect('optMpsAssetGroup', false); // Enable all group dropdowns on reset
        mzOptionStopClear('optMpsAssetCategory', 'Select Asset Category', 'required'); // Renamed ID
        mzDisableSelect('optMpsAssetCategory', true); // Initially disable category
        mzOptionStopClear('optMpsAssetType', 'Select Asset Type', 'required'); // Renamed ID
        mzDisableSelect('optMpsAssetType', true); // Initially disable type
        mzOptionStopClear('optMpsPpmGroupId', 'Select PPM Executor Group', 'required'); // Renamed ID
        mzDisableSelect('optMpsPpmGroupId', true); // Initially disable executor group

        // Clear text fields
        mzSetFieldValue('txtMpsName', '', 'text'); // Renamed ID
        mzSetFieldValue('txaMpsDesc', '', 'text'); // Renamed ID
        $('#lblMpsId').val(''); // Clear hidden ID
    };

    this.add = function () {
        try {
            submitType = 'add';
            formValidate.clearValidation();
            self.resetOption(); // Clear and reset dropdowns
            $('#h4MpsTitle').html('<i class="fas fa-plus mr-2"></i>Add PPM Set'); // Renamed ID and text
            $('#modal_ppm_set').modal({backdrop: 'static', keyboard: false}).scrollTop(0); // Show new modal ID
        } catch (e) { toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR); }
    };

    this.edit = function (_ppmSetId) { // Renamed _ppmId to _ppmSetId
        try {
            mzCheckFuncParam([_ppmSetId]);
            ppmSetId = _ppmSetId; // Use ppmSetId
            submitType = 'put';
            ShowLoader(); setTimeout(function () {
                // Fetch ppm_set details
                mzFetch('api/ppm.php?type=ppm_set_details&ppmSetId='+ppmSetId, 'GET').then(res => { // New API call
                    formValidate.clearValidation();
                    self.resetOption(); // Clear and reset dropdowns
                    
                    // Populate fields with fetched data
                    mzSetFieldValue('lblMpsId', res['ppmSetId']); // Set hidden ID
                    mzSetFieldValue('txtMpsName', res['ppmSetName']); // Renamed ID
                    mzSetFieldValue('txaMpsDesc', res['ppmSetDesc']); // Renamed ID
                    
                    // Populate asset group/category/type dropdowns and trigger change to load dependents
                    mzSetFieldValue('optMpsAssetGroup', res['assetGroupId']); // Renamed ID
                    $('#optMpsAssetGroup').trigger('change');
                    mzSetFieldValue('optMpsAssetCategory', res['assetCategoryId']); // Renamed ID
                    $('#optMpsAssetCategory').trigger('change');
                    mzSetFieldValue('optMpsAssetType', res['assetTypeId']); // Renamed ID
                    $('#optMpsAssetType').trigger('change'); // Ensure executor group loads

                    mzSetFieldValue('optMpsPpmGroupId', res['ppmGroupId']); // Renamed ID
                    
                    // Disable fields if status requires (e.g., if set is inactive, cannot edit some fields)
                    const isDisable = res['ppmSetStatus'] !== 1; // Assuming 1 is active status
                    mzDisableSelect('optMpsAssetGroup', isDisable);
                    mzDisableSelect('optMpsAssetCategory', isDisable);
                    mzDisableSelect('optMpsAssetType', isDisable);
                    mzDisableSelect('optMpsPpmGroupId', isDisable);
                    $('#txtMpsName').prop('disabled', isDisable);
                    $('#txaMpsDesc').prop('disabled', isDisable);

                    formValidate.disableField('optMpsAssetGroup', isDisable);
                    formValidate.disableField('optMpsAssetCategory', isDisable);
                    formValidate.disableField('optMpsAssetType', isDisable);
                    formValidate.disableField('optMpsPpmGroupId', isDisable);
                    formValidate.disableField('txtMpsName', isDisable);
                    formValidate.disableField('txaMpsDesc', isDisable);

                    mzSetFieldValue('lblMpsId', res['ppmSetId']); // Set the hidden ID
                    mzSetFieldValue('txtMpsName', res['ppmSetName']);
                    mzSetFieldValue('txaMpsDesc', res['ppmSetDesc']); // Ensure this maps correctly


                    $('#h4MpsTitle').html('<i class="fas fa-edit mr-2"></i>Edit PPM Set'); // Renamed ID and text
                    $('#modal_ppm_set').modal({backdrop: 'static', keyboard: false}).scrollTop(0); // Show new modal ID
                }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });


            }, 200);
        } catch (e) { toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR); }
    };

    this.delete = function (_ppmSetId) { // Renamed _ppmId to _ppmSetId
        try {
            mzCheckFuncParam([_ppmSetId]);
            toastr['info']('Delete functionality for PPM Set not yet implemented!', _ALERT_TITLE_INFO); // Temporarily block
            // ShowLoader(); setTimeout(function () {
            //     mzFetch('api/ppm.php?action=delete_ppm_set&ppmSetId='+_ppmSetId, 'DELETE').then(res => { // Example future call
            //         classFrom.genTable(); // Refresh table after delete
            //         $('#modal_ppm_set').modal('hide'); // Hide modal if deleting from here
            //         toastr['success']('PPM Set successfully deleted!', _ALERT_TITLE_SUCCESS);
            //     }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
            // }, 200);
        } catch (e) { toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR); }
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