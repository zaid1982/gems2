function ModalPpm() {

    const className = 'ModalPpm';
    let self = this;
    let classFrom;
    let rowRefresh = '';
    let assetId = '';
    let refContract;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refAssetBrand;
    let refAssetModel;
    let refPpmGroup;
    let formValidate;
    let assetTypeId;
    let submitType;

    this.init = function () {
        const vData = [
            {
                field_id: 'optMpmChecklistId',
                type: 'select',
                name: 'PPM Checklist',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMpmPpmGroupId',
                type: 'select',
                name: 'PPM Executor Group',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMpmPpmDateStart',
                type: 'text',
                name: 'Start Cycle Date',
                validator: {
                    notEmpty: true,
                    maxLength: 30
                }
            },
            {
                field_id: 'optMpmAsset',
                type: 'select',
                name: 'Asset',
                validator: {
                    notEmpty: true
                }
            }
        ];

        formValidate = new MzValidate('formMpm');
        formValidate.registerFields(vData);

        /*$('#formMpm').on('keyup change', function () {
            $('#btnMpmSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_ppm').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMpmSubmit').attr('disabled', true);
        });*/

        $('#btnMpmSubmit').on('click', function () {
            try {
                if (!formValidate.validateNow()) {
                    toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                } else {
                    const checklistId = $('#optMpmChecklistId').val();
                    const ppmGroupId = $('#optMpmPpmGroupId').val();
                    const ppmDateStart = mzConvertDate($('#txtMpmPpmDateStart').val());
                    let data = {
                        action: 'assign_ppm_single',
                        assetId: assetId,
                        checklistId: checklistId,
                        ppmGroupId: ppmGroupId,
                        ppmDateStart: ppmDateStart
                    };
                    ShowLoader(); setTimeout(function () {
                        if (submitType === 'single') {
                            const ppmReturn = mzAjaxRequest('ppm.php', 'POST', data);
                            if (classFrom.getClassName() === 'MainPpmManagement') {
                                data['ppmId'] = ppmReturn['ppmId'];
                                data['ppmTaskNo'] = ppmReturn['ppmTaskNo'];
                                data['ppmStatus'] = '10';
                                data['assignedStatus'] = '10';
                                data['ppmGroupId'] = ppmGroupId;
                                classFrom.updateTablePmg(data, rowRefresh);
                            }
                        } else if (submitType === 'bulk') {
                            const assetList = $('#optMpmAsset').val();
                            for (const id in assetList) {
                                data['assetId'] = assetList[id];
                                mzAjaxRequest('ppm.php', 'POST', data);
                            }
                            classFrom.genTablePmg();
                            classFrom.displayStatsChart();
                        } else if (submitType === 'bulkByFilter') { // Renamed from 'bulk'
                            // Data for new bulk assignment by filter API
                            data = {
                                action: 'assign_ppm_bulk_by_filter', // NEW action for backend
                                contractId: bulkContractId, // Pass filter criteria
                                assetGroupId: bulkAssetGroupId,
                                assetCategoryId: bulkAssetCategoryId,
                                assetTypeId: bulkAssetTypeId,
                                checklistId: checklistId,
                                ppmGroupId: ppmGroupId,
                                ppmDateStart: ppmDateStart
                            };
                            // Call the new backend bulk assignment API
                            // This will be a single call to process multiple assets on backend
                            const bulkAssignReturn = mzAjaxRequest('ppm.php', 'POST', data);
                            // bulkAssignReturn might contain {totalAssigned: X}
                            toastr['success'](bulkAssignReturn.totalAssigned + ' asset(s) successfully assigned in bulk!', _ALERT_TITLE_SUCCESS);
                            classFrom.genTablePmg(); // Refresh main table after bulk assignment
                            classFrom.displayStatsChart(); // Refresh stats/chart
                        }
                        $('#modal_ppm').modal('hide')
                        HideLoader();
                    }, 200);
                }
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); HideLoader(); }
        });
    };

    this.setBulkByFilter = function (_contractId, _assetGroupId, _assetCategoryId, _assetTypeId) {
        try {
            mzCheckFuncParam([_contractId, _assetGroupId, _assetCategoryId, _assetTypeId]); // Check all filter parameters
            
            bulkContractId = _contractId; // Store filter criteria
            bulkAssetGroupId = _assetGroupId;
            bulkAssetCategoryId = _assetCategoryId;
            bulkAssetTypeId = _assetTypeId;
            assetTypeId = _assetTypeId; // Set common assetTypeId for checklist filtering

            submitType = 'bulkByFilter'; // Set new submit type
            formValidate.clearValidation(); //

            // NEW: Conditionally disable 'Asset' dropdown validation for bulk mode
            formValidate.disableField('optMpmAsset'); // Disable validation for this field in bulk mode
            // mzSetFieldValue('optMpmAsset', '', 'select'); // Clear and hide its label/pre-fill

            ShowLoader(); setTimeout(function () { //
                // Step 1 (NEW): Make an API call to count eligible assets matching the filter
                // This gives user feedback on how many assets will be affected.
                const countResult = mzAjaxRequest('ppm.php?action=count_eligible_assets_by_filter', 'GET', {
                    contractId: bulkContractId,
                    assetGroupId: bulkAssetGroupId,
                    assetCategoryId: bulkAssetCategoryId,
                    assetTypeId: bulkAssetTypeId
                });

                if (countResult.totalEligibleAssets === 0) {
                    throw new Error('No assets found matching the selected filters. Please adjust your filters.');
                }
                
                // Populate display fields related to the bulk assignment criteria
                mzSetFieldValue('MpmContractName', refContract[bulkContractId]['contractName'], 'text');
                mzSetFieldValue('MpmAssetGroupName', refAssetGroup[bulkAssetGroupId]['assetGroupName'], 'text');
                mzSetFieldValue('MpmAssetCategoryName', refAssetCategory[bulkAssetCategoryId]['assetCategoryName'], 'text');
                mzSetFieldValue('MpmAssetTypeName', refAssetType[bulkAssetTypeId]['assetTypeName'], 'text');
                // Display the count of eligible assets (e.g., in the Asset dropdown's label or a dedicated span)
                mzSetFieldValue('optMpmAsset', '', 'select'); // Clear Asset dropdown
                // Add a hidden span to display the count for optMpmAsset
                $('#lblMpmAsset').html(countResult.totalEligibleAssets + ' Assets Selected (by filter)'); // Update label
                $('#lblMpmAsset').addClass('active');

                // Populate Checklist and PPM Executor Group dropdowns
                const refChecklist = mzAjaxRequest('checklist.php?assetTypeId='+assetTypeId, 'GET'); //
                mzOptionStop('optMpmChecklistId', refChecklist, 'Choose PPM Checklist', 'checklistId', 'checklistName', {checklistStatus: '1'}, 'required', true); //
                const siteId = refContract[bulkContractId]['siteId']; // Get siteId from the bulk contract
                mzOptionStop('optMpmPpmGroupId', refPpmGroup, 'Choose PPM Executor Group', 'ppmGroupId', 'ppmGroupName', {roleId:'5', siteId:siteId, ppmGroupStatus: '1'}, 'required'); //

                const contractDateStart = refContract[bulkContractId]['contractDateStart']; //
                const contractDateEnd = refContract[bulkContractId]['contractDateEnd']; //
                mzDateSetMin('txtMpmPpmDateStart', contractDateStart);
                mzDateSetMax('txtMpmPpmDateStart', contractDateEnd);
                
                // Manage visibility of bulk-specific elements
                $('.divMpmHideBulk').hide(); // Hide single-assignment elements
                $('.divMpmShowBulk').show(); // Show bulk-assignment elements (e.g. a count, not the dropdown)
                $('#lblMpmTitle').html('<span class="gems-modal-icon"><i class="fas fa-layer-plus"></i></span><span class="gems-modal-heading">PPM Bulk Assign</span>'); //
                $('#modal_ppm').modal({backdrop: 'static', keyboard: false}); //
            }, 200);
         } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); } HideLoader(); 
    }

    this.setSingle = function (_assetId, _rowRefresh) {
        ShowLoader(); setTimeout(function () { try {
            mzCheckFuncParam([_assetId, _rowRefresh]);
            assetId = _assetId;
            rowRefresh = _rowRefresh;
            submitType = 'single';
            formValidate.clearValidation();
            formValidate.disableField('optMpmAsset');
            $('.divMpmHideBulk').show();
            $('.divMpmShowBulk').hide();

            const rowData = classFrom.getTablePmgRow(rowRefresh);
            const contractId = rowData['contractId'];
            const assetGroupId = rowData['assetGroupId'];
            const assetCategoryId = rowData['assetCategoryId'];
            const assetTypeId = rowData['assetTypeId'];
            const assetBrandId = rowData['assetBrandId'];
            const assetModelId = rowData['assetModelId'];
            const contractDateStart = refContract[contractId]['contractDateStart'];
            const contractDateEnd = refContract[contractId]['contractDateEnd'];
            const siteId = refContract[contractId]['siteId'];

            mzSetFieldValue('MpmContractName', refContract[contractId]['contractName'], 'text');
            mzSetFieldValue('MpmContractDateStart', mzConvertDateDisplay(contractDateStart), 'text');
            mzSetFieldValue('MpmContractDateEnd', mzConvertDateDisplay(contractDateEnd), 'text');
            mzSetFieldValue('MpmAssetNo', rowData['assetNo'], 'text');
            mzSetFieldValue('MpmAssetName', rowData['assetName'], 'text');
            mzSetFieldValue('MpmAssetGroupName', refAssetGroup[assetGroupId]['assetGroupName'], 'text');
            mzSetFieldValue('MpmAssetCategoryName', refAssetCategory[assetCategoryId]['assetCategoryName'], 'text');
            mzSetFieldValue('MpmAssetTypeName', refAssetType[assetTypeId]['assetTypeName'], 'text');
            mzSetFieldValue('MpmAssetBrandName',  assetBrandId != '' ? refAssetBrand[assetBrandId]['assetBrandName'] : '', 'text');
            mzSetFieldValue('MpmAssetModelName', assetModelId != '' ? refAssetModel[assetModelId]['assetModelName'] : '', 'text');
            mzSetFieldValue('MpmAssetCapacity', rowData['assetCapacity'], 'text');
            mzSetFieldValue('MpmLocationCodeName', rowData['locationCodeName'], 'text');
            mzDateSetMin('txtMpmPpmDateStart', contractDateStart);
            mzDateSetMax('txtMpmPpmDateStart', contractDateEnd);

            const refChecklist = mzAjaxRequest('checklist.php?assetTypeId='+assetTypeId, 'GET');
            mzOptionStop('optMpmChecklistId', refChecklist, 'Choose PPM Checklist', 'checklistId', 'checklistName', {checklistStatus: '1'}, 'required', true);
            mzOptionStop('optMpmPpmGroupId', refPpmGroup, 'Choose PPM Executor Group', 'ppmGroupId', 'ppmGroupName', {roleId:'5', siteId:siteId, ppmGroupStatus: '1'}, 'required');

            $('#lblMpmTitle').html('<span class="gems-modal-icon"><i class="fas fa-pen-alt"></i></span><span class="gems-modal-heading">Assign PPM</span>');
            $('#modal_ppm').modal({backdrop: 'static', keyboard: false});
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); } HideLoader(); }, 200);
    };

    this.setBulk = function (_contractId, _assetTypeId) {
        try {
            mzCheckFuncParam([_assetTypeId]);
            assetTypeId = _assetTypeId;
            submitType = 'bulk';
            formValidate.clearValidation();
            ShowLoader(); setTimeout(function () {
                mzFetch('ppm_asset/listSelection/'+_contractId+'/'+assetTypeId, 'GET').then(res => {
                    if (res.length === 0) {
                        throw new Error('No asset available to assign');
                    }
                    for (const i in res) {
                        res[i]['display'] = res[i]['assetNo'] + ' - ' + res[i]['assetName'];
                    }
                    mzOptionStopV2('optMpmAsset', res, 'Choose Asset', 'display', {assetStatus: 1}, 'required');
                    const refChecklist = mzAjaxRequest('checklist.php?assetTypeId='+assetTypeId, 'GET');
                    const siteId = refContract[_contractId]['siteId'];
                    const contractDateStart = refContract[_contractId]['contractDateStart'];
                    const contractDateEnd = refContract[_contractId]['contractDateEnd'];
                    const assetCategoryId = refAssetType[assetTypeId]['assetCategoryId'];
                    const assetGroupId = refAssetCategory[assetCategoryId]['assetGroupId'];
                    mzOptionStop('optMpmChecklistId', refChecklist, 'Choose PPM Checklist', 'checklistId', 'checklistName', {checklistStatus: '1'}, 'required', true);
                    mzOptionStop('optMpmPpmGroupId', refPpmGroup, 'Choose PPM Executor Group', 'ppmGroupId', 'ppmGroupName', {roleId:'5', siteId:siteId, ppmGroupStatus: '1'}, 'required');
                    formValidate.enableField('optMpmAsset');
                    mzSetFieldValue('MpmContractName', refContract[_contractId]['contractName'], 'text');
                    mzSetFieldValue('MpmContractDateStart', mzConvertDateDisplay(contractDateStart), 'text');
                    mzSetFieldValue('MpmContractDateEnd', mzConvertDateDisplay(contractDateEnd), 'text');
                    mzSetFieldValue('MpmAssetGroupName', refAssetGroup[assetGroupId]['assetGroupName'], 'text');
                    mzSetFieldValue('MpmAssetCategoryName', refAssetCategory[assetCategoryId]['assetCategoryName'], 'text');
                    mzSetFieldValue('MpmAssetTypeName', refAssetType[assetTypeId]['assetTypeName'], 'text');
                    $('.divMpmHideBulk').hide();
                    $('.divMpmShowBulk').show();
                    $('#lblMpmTitle').html('<span class="gems-modal-icon"><i class="fas fa-layer-plus"></i></span><span class="gems-modal-heading">PPM Bulk Assign</span>');
                    $('#modal_ppm').modal({backdrop: 'static', keyboard: false});
                }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
            }, 200);
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
    }

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setRefContract = function (_refContract) {
        refContract = _refContract;
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

    this.setRefAssetBrand = function (_refAssetBrand) {
        refAssetBrand = _refAssetBrand;
    };

    this.setRefAssetModel = function (_refAssetModel) {
        refAssetModel = _refAssetModel;
    };

    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };

}