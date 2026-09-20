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
    let bulkContractId;
    let bulkAssetGroupId;
    let bulkAssetCategoryId;
    let bulkAssetTypeId;

    function pad2(n) {
        return (n < 10 ? '0' : '') + n;
    }

    function toYmd(dateStr) {
        if (!dateStr) {
            return '';
        }
        const raw = String(dateStr).trim();
        if (/^\d{4}-\d{2}-\d{2}/.test(raw)) {
            return raw.substr(0, 10);
        }
        const parts = raw.split('/');
        if (parts.length === 3) {
            return parts[0] + '-' + pad2(parseInt(parts[1], 10)) + '-' + pad2(parseInt(parts[2], 10));
        }
        return '';
    }

    function setNativeDateBounds(minYmd, maxYmd) {
        const el = document.getElementById('txtMpmPpmDateStart');
        if (!el) {
            return;
        }
        if (minYmd) {
            el.min = minYmd;
        } else {
            el.removeAttribute('min');
        }
        if (maxYmd) {
            el.max = maxYmd;
        } else {
            el.removeAttribute('max');
        }
    }

    function rowsFromRef(ref, idKey) {
        const rows = [];
        if (!ref) {
            return rows;
        }
        $.each(ref, function (id, rec) {
            if (!rec || typeof rec !== 'object') {
                return;
            }
            const row = $.extend({}, rec);
            if (row[idKey] === undefined || row[idKey] === null || row[idKey] === '') {
                row[idKey] = String(id);
            }
            rows.push(row);
        });
        return rows;
    }

    function filterRows(rows, filters) {
        return (rows || []).filter(function (row) {
            const keys = Object.keys(filters || {});
            for (let i = 0; i < keys.length; i++) {
                if (String(row[keys[i]]) !== String(filters[keys[i]])) {
                    return false;
                }
            }
            return true;
        });
    }

    function fillNamed(id, rows, valueKey, labelKey, placeholder, selected) {
        const sorted = (rows || []).slice().sort(function (a, b) {
            return String(a[labelKey] || '').localeCompare(String(b[labelKey] || ''));
        });
        GemsUI.fillSelect(id, sorted, valueKey, function (row) {
            return row[labelKey] || '';
        }, placeholder, selected);
        $('#' + id + 'Err').html('');
    }

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

        $('#modal_ppm').on('hidden.bs.modal', function () {
            setNativeDateBounds('', '');
        });

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
                        } else if (submitType === 'bulkByFilter') {
                            data = {
                                action: 'assign_ppm_bulk_by_filter',
                                contractId: bulkContractId,
                                assetGroupId: bulkAssetGroupId,
                                assetCategoryId: bulkAssetCategoryId,
                                assetTypeId: bulkAssetTypeId,
                                checklistId: checklistId,
                                ppmGroupId: ppmGroupId,
                                ppmDateStart: ppmDateStart
                            };
                            const bulkAssignReturn = mzAjaxRequest('ppm.php', 'POST', data);
                            toastr['success'](bulkAssignReturn.totalAssigned + ' asset(s) successfully assigned in bulk!', _ALERT_TITLE_SUCCESS);
                            classFrom.genTablePmg();
                            classFrom.displayStatsChart();
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
            mzCheckFuncParam([_contractId, _assetGroupId, _assetCategoryId, _assetTypeId]);

            bulkContractId = _contractId;
            bulkAssetGroupId = _assetGroupId;
            bulkAssetCategoryId = _assetCategoryId;
            bulkAssetTypeId = _assetTypeId;
            assetTypeId = _assetTypeId;

            submitType = 'bulkByFilter';
            formValidate.clearValidation();

            formValidate.disableField('optMpmAsset');

            ShowLoader(); setTimeout(function () {
                const countResult = mzAjaxRequest('ppm.php?action=count_eligible_assets_by_filter', 'GET', {
                    contractId: bulkContractId,
                    assetGroupId: bulkAssetGroupId,
                    assetCategoryId: bulkAssetCategoryId,
                    assetTypeId: bulkAssetTypeId
                });

                if (countResult.totalEligibleAssets === 0) {
                    throw new Error('No assets found matching the selected filters. Please adjust your filters.');
                }

                mzSetFieldValue('MpmContractName', refContract[bulkContractId]['contractName'], 'text');
                mzSetFieldValue('MpmAssetGroupName', refAssetGroup[bulkAssetGroupId]['assetGroupName'], 'text');
                mzSetFieldValue('MpmAssetCategoryName', refAssetCategory[bulkAssetCategoryId]['assetCategoryName'], 'text');
                mzSetFieldValue('MpmAssetTypeName', refAssetType[bulkAssetTypeId]['assetTypeName'], 'text');
                fillNamed('optMpmAsset', [], 'id', 'display', 'Choose Asset');
                $('#lblMpmAsset').html(countResult.totalEligibleAssets + ' Assets Selected (by filter)');
                $('#lblMpmAsset').addClass('active');

                const refChecklist = mzAjaxRequest('checklist.php?assetTypeId=' + assetTypeId, 'GET');
                fillNamed(
                    'optMpmChecklistId',
                    filterRows(rowsFromRef(refChecklist, 'checklistId'), {checklistStatus: '1'}),
                    'checklistId',
                    'checklistName',
                    'Choose PPM Checklist'
                );
                const siteId = refContract[bulkContractId]['siteId'];
                fillNamed(
                    'optMpmPpmGroupId',
                    filterRows(rowsFromRef(refPpmGroup, 'ppmGroupId'), {roleId: '5', siteId: siteId, ppmGroupStatus: '1'}),
                    'ppmGroupId',
                    'ppmGroupName',
                    'Choose PPM Executor Group'
                );

                const contractDateStart = refContract[bulkContractId]['contractDateStart'];
                const contractDateEnd = refContract[bulkContractId]['contractDateEnd'];
                setNativeDateBounds(toYmd(contractDateStart), toYmd(contractDateEnd));

                $('.divMpmHideBulk').hide();
                $('.divMpmShowBulk').show();
                $('#lblMpmTitle').html('<i class="fas fa-layer-plus me-2"></i>PPM Bulk Assign');
                $('#modal_ppm').modal({backdrop: 'static', keyboard: false});
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
            setNativeDateBounds(toYmd(contractDateStart), toYmd(contractDateEnd));

            const refChecklist = mzAjaxRequest('checklist.php?assetTypeId=' + assetTypeId, 'GET');
            fillNamed(
                'optMpmChecklistId',
                filterRows(rowsFromRef(refChecklist, 'checklistId'), {checklistStatus: '1'}),
                'checklistId',
                'checklistName',
                'Choose PPM Checklist'
            );
            fillNamed(
                'optMpmPpmGroupId',
                filterRows(rowsFromRef(refPpmGroup, 'ppmGroupId'), {roleId: '5', siteId: siteId, ppmGroupStatus: '1'}),
                'ppmGroupId',
                'ppmGroupName',
                'Choose PPM Executor Group'
            );

            $('#lblMpmTitle').html('<i class="fas fa-pen-alt me-2"></i>Assign PPM');
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
                mzFetch('ppm_asset/listSelection/' + _contractId + '/' + assetTypeId, 'GET').then(res => {
                    if (res.length === 0) {
                        throw new Error('No asset available to assign');
                    }
                    const rows = [];
                    for (const i in res) {
                        if (!res[i] || typeof res[i] !== 'object') {
                            continue;
                        }
                        const row = $.extend({}, res[i]);
                        if (row['id'] === undefined) {
                            row['id'] = String(i);
                        }
                        row['display'] = (row['assetNo'] || '') + ' - ' + (row['assetName'] || '');
                        rows.push(row);
                    }
                    fillNamed(
                        'optMpmAsset',
                        filterRows(rows, {assetStatus: 1}),
                        'id',
                        'display',
                        'Choose Asset'
                    );
                    const refChecklist = mzAjaxRequest('checklist.php?assetTypeId=' + assetTypeId, 'GET');
                    const siteId = refContract[_contractId]['siteId'];
                    const contractDateStart = refContract[_contractId]['contractDateStart'];
                    const contractDateEnd = refContract[_contractId]['contractDateEnd'];
                    const assetCategoryId = refAssetType[assetTypeId]['assetCategoryId'];
                    const assetGroupId = refAssetCategory[assetCategoryId]['assetGroupId'];
                    fillNamed(
                        'optMpmChecklistId',
                        filterRows(rowsFromRef(refChecklist, 'checklistId'), {checklistStatus: '1'}),
                        'checklistId',
                        'checklistName',
                        'Choose PPM Checklist'
                    );
                    fillNamed(
                        'optMpmPpmGroupId',
                        filterRows(rowsFromRef(refPpmGroup, 'ppmGroupId'), {roleId: '5', siteId: siteId, ppmGroupStatus: '1'}),
                        'ppmGroupId',
                        'ppmGroupName',
                        'Choose PPM Executor Group'
                    );
                    formValidate.enableField('optMpmAsset');
                    mzSetFieldValue('MpmContractName', refContract[_contractId]['contractName'], 'text');
                    mzSetFieldValue('MpmContractDateStart', mzConvertDateDisplay(contractDateStart), 'text');
                    mzSetFieldValue('MpmContractDateEnd', mzConvertDateDisplay(contractDateEnd), 'text');
                    mzSetFieldValue('MpmAssetGroupName', refAssetGroup[assetGroupId]['assetGroupName'], 'text');
                    mzSetFieldValue('MpmAssetCategoryName', refAssetCategory[assetCategoryId]['assetCategoryName'], 'text');
                    mzSetFieldValue('MpmAssetTypeName', refAssetType[assetTypeId]['assetTypeName'], 'text');
                    setNativeDateBounds(toYmd(contractDateStart), toYmd(contractDateEnd));
                    $('.divMpmHideBulk').hide();
                    $('.divMpmShowBulk').show();
                    $('#lblMpmTitle').html('<i class="fas fa-layer-plus me-2"></i>PPM Bulk Assign');
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
