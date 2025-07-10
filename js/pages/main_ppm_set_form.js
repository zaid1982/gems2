function MainPpmSetForm () {

    const className = 'MainPpmSetForm';
    let self = this;
    let formValidate;
    let submitType = ''; // 'add' or 'put'
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refPpmGroup;
    let ppmSetId; // ID of the current PPM Set
    let currentContractId; // Store the contract ID of the PPM Set's main asset (if in edit mode)
    let currentAssetGroupId; // Store the asset group ID of the PPM Set (if in edit mode)
    let currentAssetCategoryId; // Store the asset category ID of the PPM Set (if in edit mode)
    let currentAssetTypeId; // Store the asset type ID of the PPM Set (if in edit mode)

    let dtPpsa; // DataTable for Assets in PPM Set

    // Dependencies
    let modalPpmSetSelectAssetClass;
    let modalConfirmDeleteClass;

    const vData = [
        {
            field_id: 'txtMpsName',
            type: 'text',
            name: 'PPM Set Name',
            validator: {
                notEmpty: true,
                maxLength: 200
            }
        },
        {
            field_id: 'txaMpsDesc',
            type: 'textarea',
            name: 'Description',
            validator: {
                notEmpty: false,
                maxLength: 1000
            }
        },
        {
            field_id: 'optMpsAssetGroup',
            type: 'select',
            name: 'Asset Group',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optMpsAssetCategory',
            type: 'select',
            name: 'Asset Category',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optMpsAssetType',
            type: 'select',
            name: 'Asset Type',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optMpsPpmGroupId',
            type: 'select',
            name: 'PPM Executor Group',
            validator: {
                notEmpty: true
            }
        }
    ];

    const userSiteId = mzGetUserInfoByParam('siteId');

    // Helper to destroy and re-initialize a single MDB select
    function reinitializeSelect(selectId, dataArray, defaultText, valueKey, textKey, filters, type, isSort, sortIndex) {
        const $select = $('#' + selectId);
        $select.materialSelect('destroy'); // Destroy existing instance
        mzOption(selectId, dataArray, defaultText, valueKey, textKey, filters, type, isSort, sortIndex); // Populate native select
        $select.materialSelect(); // Re-initialize materialSelect for this element
        // Ensure label is active
        $('#lbl' + selectId.substr(3)).removeClass('active').addClass('active');
    }

    this.init = function () {
        // Dropdown change handlers (UPDATED to use reinitializeSelect helper)
        $('#optMpsAssetGroup').on('change', function () {
            const id = $(this).val();
            try {
                reinitializeSelect('optMpsAssetCategory', refAssetCategory, 'Select Asset Category', 'assetCategoryId', 'assetCategoryName', {assetGroupId: id, assetCategoryStatus: '1'}, 'required');
                // Reset Asset Type and Asset Category
                mzSetFieldValue('optMpsAssetType', '', 'select');
                mzSetFieldValue('optMpsPpmGroupId', '', 'select');
                mzDisableSelect('optMpsAssetCategory', false);
                mzDisableSelect('optMpsAssetType', true);
                mzDisableSelect('optMpsPpmGroupId', false);
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#optMpsAssetCategory').on('change', function () {
            const id = $(this).val();
            try {
                reinitializeSelect('optMpsAssetType', refAssetType, 'Select Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: id, assetTypeStatus: '1'}, 'required');
                mzDisableSelect('optMpsAssetType', false);
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        // Submit button handler
        $('#btnMpsSubmit').on('click', function () {
            // try {
                if (!formValidate.validateNow()) {
                    toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                } else {
                    let data = {
                        ppmSetName: mzNullString('txtMpsName'),
                        ppmSetDesc: mzNullString('txaMpsDesc'),
                        assetGroupId: mzNullInt('optMpsAssetGroup'),
                        assetCategoryId: mzNullInt('optMpsAssetCategory'),
                        assetTypeId: mzNullInt('optMpsAssetType'),
                        ppmGroupId: mzNullInt('optMpsPpmGroupId')
                    };
                    ShowLoader(); setTimeout(function () {
                        if (submitType === 'add') {
                            mzFetch('api/ppm.php?action=create_ppm_set', 'POST', data).then(res => {
                                toastr['success']('PPM Set "' + data.ppmSetName + '" successfully created!', _ALERT_TITLE_SUCCESS);
                                // Redirect to edit mode for the newly created set, or back to list
                                window.location.href = 'ppm_set_form.html?ppmSetId=' + res.ppmSetId;
                            }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                        } else if (submitType === 'put') {
                            data['ppmSetId'] = parseInt(ppmSetId);
                            data['action'] = 'update_ppm_set'; // Action for backend PUT request

                            try
                            {
                                toastr['success']('PPM Set "' + data.ppmSetName + '" successfully updated!', _ALERT_TITLE_SUCCESS);
                            } catch (e) { 
                                toastr['error'](e.message, _ALERT_TITLE_ERROR); 
                            } finally {
                                HideLoader(); // Ensure loader is hidden after operation
                            }
                        }
                    }, 200);
                }
            // } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        formValidate = new MzValidate('formMps');
        formValidate.registerFields(vData);

        // Determine Add or Edit mode from URL
        const urlParams = new URLSearchParams(window.location.search);
        const urlPpmSetId = urlParams.get('ppmSetId');

        if (urlPpmSetId) {
            ppmSetId = parseInt(urlPpmSetId);
            self.loadPpmSetForEdit(ppmSetId);
        } else {
            self.initForAdd();
        }

        // NEW: Initialize DataTable for Assets in PPM Set
        dtPpsa = $('#dtPpsa').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']], // Sort by Asset No
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 5, // A smaller page size for this embedded list
            autoWidth: false,
            dom: "<'row'<'col-12'tr>>" + // Only table rows, no buttons/search for embedded
                 "<'row'<'col-sm-6 col-md-5'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { className: 'text-center', targets: [0, 4] }, // # and Action column
                { className: 'text-right', targets: [] },
                { visible: false, targets: [] },
                { className: 'noVis', targets: [0] }
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1)); // Set # column
            },
            aoColumns: [
                { mData: null }, // #
                { mData: 'assetNo' }, // Asset No
                { mData: 'assetName' }, // Asset Name
                { mData: 'assetLocationDesc' }, // Location
                { // Action column for 'Remove'
                    mData: null, bSortable: false, mRender: function (data, type, row) {
                        // Pass ppmSetId and ppmSetAssetId for removal
                        return '<a class="btn btn-sm btn-danger waves-effect waves-light p-2 btn-remove-asset" data-ppmset-asset-id="' + row.ppmSetAssetId + '" data-asset-id="' + row.assetId + '" data-asset-no="' + row.assetNo + '" data-toggle="tooltip" data-placement="top" title="Remove"><i class="fas fa-minus-circle"></i></a>';
                    }
                }
            ]
        });

        // Modify the 'Add Assets' button click handler to pass all three IDs
    $('#btnAddAssetsToSet').on('click', function () {
        // Ensure we are in edit mode and have a ppmSetId
        if (submitType === 'put' && ppmSetId) {
            // Check if Asset Group, Category, and Type are defined for the current PPM Set
            if (!currentAssetGroupId || !currentAssetCategoryId || !currentAssetTypeId) {
                 toastr['warning']('PPM Set Asset Group, Category, or Type is not defined. Cannot add assets.', _ALERT_TITLE_WARNING);
                 return;
            }
            // Open the asset selection modal, passing all three crucial IDs
            modalPpmSetSelectAssetClass.show(ppmSetId, currentAssetGroupId, currentAssetCategoryId, currentAssetTypeId);
        } else {
            toastr['warning']('Please save the PPM Set first before adding assets.', _ALERT_TITLE_WARNING);
        }
    });

        // NEW: Event delegation for 'Remove Asset' buttons in dtPpsa
        $('#dtPpsa').on('click', '.btn-remove-asset', function () {
            const btn = $(this);
            const ppmSetAssetIdToRemove = btn.data('ppmset-asset-id');
            const assetIdToRemove = btn.data('asset-id');
            const assetNoToRemove = btn.data('asset-no');

            modalConfirmDeleteClass.show(
                'Asset from PPM Set',
                'Are you sure you want to remove Asset No: ' + assetNoToRemove + ' from this PPM Set?',
                'mainPpmSetFormClass_.removeAssetFromPpmSetConfirmed(' + ppmSetId + ', [' + assetIdToRemove + '])'
            );
        });

        // REMOVED THE GLOBAL $('.mdb-select').materialSelect(); from here in init().
    };

    this.initForAdd = function () {
        submitType = 'add';
        formValidate.clearValidation();
        self.resetFormFields();

        // Initialize the first dropdown for Add mode
        // Call mzOption directly, as mzOption itself doesn't re-init materialSelect.
        reinitializeSelect('optMpsAssetGroup', refAssetGroup, 'Select Asset Group', 'assetGroupId', 'assetGroupName', {assetGroupStatus: '1'}, 'required');

        // For loadPpmSetForEdit:
        reinitializeSelect('optMpsPpmGroupId', refPpmGroup, 'Select PPM Executor Group', 'ppmGroupId', 'ppmGroupName', {roleId: '5', siteId: userSiteId, ppmGroupStatus: '1'}, 'required');

        $('#h5PpmSetFormTitle').html('<i class="fas fa-plus mr-1"></i> Add New PPM Set');
        $('#sectionAssetsInSet').hide();

        // IMPORTANT: Call a function to initialize ALL MDB selects after initial form setup
        // self.refreshAllMdbSelects();
    };

    this.loadPpmSetForEdit = function (_ppmSetId) {
        // try {
            submitType = 'put';
            ShowLoader(); 

            // For loadPpmSetForEdit:
            // reinitializeSelect('optMpsPpmGroupId', refPpmGroup, 'Select PPM Executor Group', 'ppmGroupId', 'ppmGroupName', {roleId: '5', ppmGroupStatus: '1'}, 'required');

            setTimeout(function () {
                mzFetch('api/ppm.php?type=ppm_set_details&ppmSetId='+_ppmSetId, 'GET').then(res => {
                    formValidate.clearValidation();
                    self.resetFormFields(); // Clears fields before populating (and destroys MDB selects)

                    // Populate fields
                    $('#lblMpsId').val(res['ppmSetId']);
                    mzSetFieldValue('txtMpsName', res['ppmSetName']);
                    $('#txtMpsName').change();

                    mzSetFieldValue('txaMpsDesc', res['ppmSetDesc']);
                    $('#txaMpsDesc').change();

                    currentContractId = res['contractId']; // Ensure your ppm_set_details returns this if needed elsewhere
                    currentAssetGroupId = res['assetGroupId']; // Store Asset Group ID
                    currentAssetCategoryId = res['assetCategoryId']; // Store Asset Category ID
                    currentAssetTypeId = res['assetTypeId']; // Store Asset Type ID


                    // Initialize and set values for cascading dropdowns
                    // Populate with mzOption (native select), then trigger change for cascade which uses reinitializeSelect
                    // mzOption('optMpsAssetGroup', refAssetGroup, 'Select Asset Group', 'assetGroupId', 'assetGroupName', {assetGroupStatus: '1'}, 'required');
                    reinitializeSelect('optMpsAssetGroup', refAssetGroup, 'Select Asset Group', 'assetGroupId', 'assetGroupName', {assetGroupStatus: '1'}, 'required');

                     // For loadPpmSetForEdit:
                    reinitializeSelect('optMpsPpmGroupId', refPpmGroup, 'Select PPM Executor Group', 'ppmGroupId', 'ppmGroupName', {roleId: '5', siteId: userSiteId, ppmGroupStatus: '1'}, 'required');

                    mzSetFieldValue('optMpsAssetGroup', res['assetGroupId']);
                    $('#optMpsAssetGroup').trigger('change');

                    setTimeout(() => {
                        mzSetFieldValue('optMpsAssetCategory', res['assetCategoryId']);
                        $('#optMpsAssetCategory').trigger('change');

                        setTimeout(() => {
                            mzSetFieldValue('optMpsAssetType', res['assetTypeId']);
                            $('#optMpsAssetType').trigger('change');

                            // setTimeout(() => {
                            //     mzSetFieldValue('optMpsPpmGroupId', res['ppmGroupId']);
                            // }, 100);
                        }, 100);
                    }, 100);

                    // Disable fields based on status
                    const isDisable = res['ppmSetStatus'] != 1;
                    mzDisableSelect('optMpsAssetGroup', isDisable);
                    mzDisableSelect('optMpsAssetCategory', isDisable);
                    mzDisableSelect('optMpsAssetType', isDisable);
                    mzDisableSelect('optMpsPpmGroupId', isDisable);
                    $('#txtMpsName').prop('disabled', isDisable);
                    $('#txaMpsDesc').prop('disabled', isDisable);
                    $('#btnMpsSubmit').prop('disabled', isDisable);

                    formValidate.disableField('optMpsAssetGroup', isDisable);
                    formValidate.disableField('optMpsAssetCategory', isDisable);
                    formValidate.disableField('optMpsAssetType', isDisable);
                    formValidate.disableField('optMpsPpmGroupId', isDisable);
                    formValidate.disableField('txtMpsName', isDisable);
                    formValidate.disableField('txaMpsDesc', isDisable);

                    $('#h5PpmSetFormTitle').html('<i class="fas fa-edit mr-1"></i> Edit PPM Set: ' + res['ppmSetName']);
                    $('#sectionAssetsInSet').show(); //

                    self.loadAssetsInPpmSet(ppmSetId); //

                    // IMPORTANT: Call a function to initialize ALL MDB selects after form is loaded and populated
                    // self.refreshAllMdbSelects();
                    HideLoader();

                }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); HideLoader(); });
            }, 200);
        // } catch (e) { toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR); HideLoader(); }
    };

    this.loadAssetsInPpmSet = function (_ppmSetId) {
        dtPpsa.clear().draw(); // Clear existing data

        ShowLoader(); setTimeout(function () {
            console.log('Loading assets for PPM Set ID:', _ppmSetId);
            mzFetch('api/ppm.php?type=assets_in_ppm_set&ppmSetId=' + _ppmSetId, 'GET').then(res => { //
                console.log('Assets in PPM Set:', res);
                dtPpsa.rows.add(res).draw(); // Add new data
                HideLoader(); //
            }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); HideLoader(); });
        }, 100); // Small delay
    };

    // NEW: Callback from ModalConfirmDelete for removing asset
    this.removeAssetFromPpmSetConfirmed = function (_ppmSetId, _assetIds) {
        ShowLoader(); setTimeout(function () {
            mzAjaxRequest('ppm.php', 'DELETE', {
                action: 'remove_assets_from_ppm_set',
                ppmSetId: _ppmSetId,
                assetIds: JSON.stringify(_assetIds) // Ensure array is stringified for backend
            }).then(res => {
                toastr['success'](res.errmsg, _ALERT_TITLE_SUCCESS);
                self.loadAssetsInPpmSet(_ppmSetId); // Refresh asset list after removal
            }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
        }, 200);
    };

    // NEW: Callback for when assets are added via modal_ppm_set_select_asset
    this.assetsAddedToPpmSetCallback = function () {
        // This function will be called by modalPpmSetSelectAssetClass_ after successful asset addition.
        // It needs to refresh the assets list in this form.
        if (ppmSetId) {
            self.loadAssetsInPpmSet(ppmSetId);
        }
    };

    // NEW: Function to clear and reset all form fields (excluding asset list dt)
    this.resetFormFields = function () {
        formValidate.clearValidation();
        // Destroy MDB selects BEFORE using mzOption to clear options
        $('#optMpsAssetGroup').materialSelect('destroy');
        $('#optMpsAssetCategory').materialSelect('destroy');
        $('#optMpsAssetType').materialSelect('destroy');
        $('#optMpsPpmGroupId').materialSelect('destroy');

        // Use mzOption to clear options (it populates native select, DOES NOT re-init materialSelect)
        mzOption('optMpsAssetGroup', [], 'Select Asset Group', 'assetGroupId', 'assetGroupName', {}, 'required');
        mzDisableSelect('optMpsAssetGroup', false);
        mzOption('optMpsAssetCategory', [], 'Select Asset Category', 'assetCategoryId', 'assetCategoryName', {}, 'required');
        mzDisableSelect('optMpsAssetCategory', true);
        mzOption('optMpsAssetType', [], 'Select Asset Type', 'assetTypeId', 'assetTypeName', {}, 'required');
        mzDisableSelect('optMpsAssetType', true);
        mzOption('optMpsPpmGroupId', [], 'Select PPM Executor Group', 'ppmGroupId', 'ppmGroupName', {}, 'required');
        mzDisableSelect('optMpsPpmGroupId', true);

        mzSetFieldValue('txtMpsName', '', 'text');
        mzSetFieldValue('txaMpsDesc', '', 'text');
        $('#lblMpsId').val('');

        // No global materialSelect() call here. It's handled by refreshAllMdbSelects.
    };

    // NEW: Centralized function to refresh all MDB select instances
    this.refreshAllMdbSelects = function() {
        $('.mdb-select').materialSelect(); // Re-initializes ALL MDB selects on the page
        // Ensure labels are active (MDB sometimes loses this after re-init)
        $('select.mdb-select').each(function() {
            const labelId = 'lbl' + $(this).attr('id').substr(3);
            if ($('#' + labelId).length) {
                $('#' + labelId).removeClass('active').addClass('active');
            }
        });
    };


    // Reference setters (copied from ModalPpmSet)
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
    this.setModalPpmSetSelectAssetClass = function (_modalPpmSetSelectAssetClass) {
        modalPpmSetSelectAssetClass = _modalPpmSetSelectAssetClass;
        // IMPORTANT: Set a callback on the modal so it can notify this page when assets are added
        modalPpmSetSelectAssetClass.setCallbackOnAdd(self.assetsAddedToPpmSetCallback);
    };
    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };

    this.getClassName = function () {
        return className;
    };
}