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

    function successAfterSubmit() {
        toastr['success']('PPM Set successfully updated!', _ALERT_TITLE_SUCCESS);
        classFrom.genTable(); // Refresh table after update
        $('#modal_ppm_set').modal('hide'); // Hide modal after update
        HideLoader(); // Remove loading
    }

    /**
     * Populate a cascading MDB materialSelect in ONE pass (destroy → populate → create).
     * Replaces the old mzOptionStop + mzDisableSelect(field, false) combo which
     * caused a double materialSelect destroy→create cycle and severe lag on
     * large option lists (e.g. Mechanical asset categories).
     */
    function _populateSelect(name, data, defaultText, keyIndex, valIndex, filters, type) {
        var $s = $('#' + name);
        $s.prop('disabled', false).removeClass('grey lighten-4');
        try { $s.materialSelect('destroy'); } catch (e) { /* ignore */ }
        mzOption(name, data, defaultText, keyIndex, valIndex, filters, type);
        $s.materialSelect({ visibleOptions: 15 });
        $s.removeClass('invalid');
        $('#' + name + 'Err').html('');
    }

    /**
     * Clear a cascading MDB materialSelect to its placeholder and disable it
     * in ONE pass. Replaces the old mzOptionStopClear + mzDisableSelect(field, true)
     * combo that triggered two full rebuilds.
     */
    function _clearAndDisable(name, defaultText, type) {
        var $s = $('#' + name);
        try { $s.materialSelect('destroy'); } catch (e) { /* ignore */ }
        removeOptions(document.getElementById(name));
        var opt0 = new Option(defaultText, '', true, true);
        if (type === 'required') { opt0.disabled = true; }
        document.getElementById(name).options[0] = opt0;
        $s.val(null).prop('disabled', true).addClass('grey lighten-4');
        $s.materialSelect({ visibleOptions: 15 });
        $s.removeClass('invalid');
        $('#' + name + 'Err').html('');
        $('#lbl' + name.substr(3)).removeClass('active').addClass('active');
    }

    this.init = function () {

        $('#optMpsAssetGroup').on('change', function () {
            const id = $(this).val();
            try {
                // Single-pass: populate Category, clear & disable downstream
                _clearAndDisable('optMpsPpmGroupId', 'Select PPM Executor Group', 'required');
                _clearAndDisable('optMpsAssetType', 'Select Asset Type', 'required');
                _populateSelect('optMpsAssetCategory', refAssetCategory, 'Select Asset Category', 'assetCategoryId', 'assetCategoryName', {assetGroupId: id, assetCategoryStatus: '1'}, 'required');
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#optMpsAssetCategory').on('change', function () {
            const id = $(this).val();
            try {
                _clearAndDisable('optMpsPpmGroupId', 'Select PPM Executor Group', 'required');
                _populateSelect('optMpsAssetType', refAssetType, 'Select Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: id, assetTypeStatus: '1'}, 'required');
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#optMpsAssetType').on('change', function () {
            const id = $(this).val();
            try {
                _populateSelect('optMpsPpmGroupId', refPpmGroup, 'Select PPM Executor Group', 'ppmGroupId', 'ppmGroupName', {roleId: '5', siteId: siteId, ppmGroupStatus: '1'}, 'required');
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
                        ppmGroupId: mzNullInt('optMpsPpmGroupId'), // Renamed ID
                        assetGroupId: mzNullInt('optMpsAssetGroup'),      // Add assetGroupId
                        assetCategoryId: mzNullInt('optMpsAssetCategory'), // Add assetCategoryId
                    };
                    ShowLoader(); setTimeout(function () {
                        if (submitType === 'add') {
                            data['action'] = 'create_ppm_set';
                            mzAjaxRequest('ppm.php', 'POST', data);
                            successAfterSubmit();
                        } else if (submitType === 'put') {
                            data['ppmSetId'] = parseInt(ppmSetId);
                            data['action'] = 'update_ppm_set';
                            mzAjaxRequest('ppm.php?ppmSetId='+parseInt(ppmSetId), 'PUT', data);
                            successAfterSubmit();
                        }
                    }, 200);
                }
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        formValidate = new MzValidate('formMps'); // Renamed ID
        formValidate.registerFields(vData);
    };

    this.resetOption = function () {
        // Clear & disable cascading selects bottom-up (single materialSelect pass each)
        _clearAndDisable('optMpsPpmGroupId', 'Select PPM Executor Group', 'required');
        _clearAndDisable('optMpsAssetType', 'Select Asset Type', 'required');
        _clearAndDisable('optMpsAssetCategory', 'Select Asset Category', 'required');
        // Asset Group: clear & leave enabled (add/edit populates it right after)
        mzOptionStopClear('optMpsAssetGroup', 'Select Asset Group', 'required');

        // Clear text fields
        mzSetFieldValue('txtMpsName', '', 'text');
        mzSetFieldValue('txaMpsDesc', '', 'text');
        $('#lblMpsId').val('');
    };

    this.add = function () {
        try {
            submitType = 'add';
            formValidate.clearValidation();
            self.resetOption(); // Clear and reset dropdowns

            // ADDED: Initialize the first dropdown here when adding a new record
            // This ensures refAssetGroup is populated when 'add' is called
            mzOptionStop('optMpsAssetGroup', refAssetGroup, 'Select Asset Group', 'assetGroupId', 'assetGroupName', {assetGroupStatus: '1'}, 'required');

            $('#h4MpsTitle').html('<i class="fas fa-plus mr-2"></i>Add PPM Set'); // Renamed ID and text
            $('#modal_ppm_set').modal({backdrop: 'static', keyboard: false}).scrollTop(0); // Show new modal ID
        } catch (e) { toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR); }
    };

    this.edit = function (_ppmSetId) {
        try {
            mzCheckFuncParam([_ppmSetId]);
            ppmSetId = _ppmSetId;
            submitType = 'put';
            ShowLoader(); 
            // Corrected API call for fetching ppm_set details
            mzFetch('api/ppm.php?type=ppm_set_details&ppmSetId='+ppmSetId, 'GET').then(res => {
                formValidate.clearValidation();
                self.resetOption();
                
                // --- SPECIAL HANDLING FOR lblMpsId ---
                // Direct jQuery val() for hidden input, bypass mzSetFieldValue for this one.
                $('#lblMpsId').val(res['ppmSetId']); // This sets the value without mzSetFieldValue

                mzSetFieldValue('txtMpsName', res['ppmSetName']); // This will work with 'txt' prefix
                mzSetFieldValue('txaMpsDesc', res['ppmSetDesc']); // This will work with 'txa' prefix

                // ADDED: Initialize the first dropdown here when editing, before setting its value
                // This ensures refAssetGroup is populated when 'edit' is called
                mzOptionStop('optMpsAssetGroup', refAssetGroup, 'Select Asset Group', 'assetGroupId', 'assetGroupName', {assetGroupStatus: '1'}, 'required');
                
                mzSetFieldValue('optMpsAssetGroup', res['assetGroupId']);
                $('#optMpsAssetGroup').trigger('change');
                mzSetFieldValue('optMpsAssetCategory', res['assetCategoryId']);
                $('#optMpsAssetCategory').trigger('change');
                mzSetFieldValue('optMpsAssetType', res['assetTypeId']);
                $('#optMpsAssetType').trigger('change');

                mzSetFieldValue('optMpsPpmGroupId', res['ppmGroupId']); // This will work with 'opt' prefix
                
                const isDisable = res['ppmSetStatus'] != 1; // Using loose equality to check for both 1 and "1"
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

                $('#h4MpsTitle').html('<i class="fas fa-edit mr-2"></i>Edit PPM Set');
                $('#modal_ppm_set').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            }).catch((e) => { 
                toastr['error'](e.message, _ALERT_TITLE_ERROR); 
                HideLoader(); 
            });
        } catch (e) { 
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR); 
        } finally {
            HideLoader(); // Ensure loader is hidden even if an error occurs
        }
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