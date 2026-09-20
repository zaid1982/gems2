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
            field_id: 'optMpaPpmGroupId',
            type: 'select',
            name: 'PPM Executor Group',
            validator: {
                notEmpty: true
            }
        }
    ];

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
            if (row[idKey] === undefined) {
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
    }

    this.init = function () {
        fillNamed(
            'optMpaAssetGroup',
            filterRows(rowsFromRef(refAssetGroup, 'assetGroupId'), {assetGroupStatus: '1'}),
            'assetGroupId',
            'assetGroupName',
            'Select Asset Group'
        );

        $('#optMpaAssetGroup').on('change', function () {
            const id = $(this).val();
            try {
                fillNamed(
                    'optMpaAssetCategory',
                    filterRows(rowsFromRef(refAssetCategory, 'assetCategoryId'), {assetGroupId: id, assetCategoryStatus: '1'}),
                    'assetCategoryId',
                    'assetCategoryName',
                    'Select Asset Category'
                );
                mzDisableSelect('optMpaAssetCategory', false);
                mzDisableSelect('optMpaAssetType', true);
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#optMpaAssetCategory').on('change', function () {
            const id = $(this).val();
            try {
                fillNamed(
                    'optMpaAssetType',
                    filterRows(rowsFromRef(refAssetType, 'assetTypeId'), {assetCategoryId: id, assetTypeStatus: '1'}),
                    'assetTypeId',
                    'assetTypeName',
                    'Select Asset Type'
                );
                mzDisableSelect('optMpaAssetType', false);
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#optMpaAssetType').on('change', function () {
            try {
                fillNamed(
                    'optMpaPpmGroupId',
                    filterRows(rowsFromRef(refPpmGroup, 'ppmGroupId'), {roleId: '5', siteId: siteId, ppmGroupStatus: '1'}),
                    'ppmGroupId',
                    'ppmGroupName',
                    'Select PPM Executor Group'
                );
                mzDisableSelect('optMpaPpmGroupId', false);
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#btnMpaSubmit').on('click', function () {
            try {
                if (!formValidate.validateNow()) {
                    toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                } else {
                    let data = {
                        ppmSetName: mzNullString('txtMpaName'),
                        ppmSetDesc: mzNullString('txaMpaDesc'),
                        assetTypeId: mzNullInt('optMpaAssetType'),
                        ppmGroupId: mzNullInt('optMpaPpmGroupId')
                    };
                    ShowLoader(); setTimeout(function () {
                        if (submitType === 'add') {
                            mzFetch('ppm.php?action=create_ppm_set', 'POST', data).then(res => {
                                toastr['success']('PPM Set "' + data.ppmSetName + '" successfully created!', _ALERT_TITLE_SUCCESS);
                                classFrom.genTable();
                                $('#modal_ppm_asset').modal('hide');
                            }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                        } else if (submitType === 'put') {
                            toastr['error']('Edit functionality for PPM Set not yet implemented!', _ALERT_TITLE_ERROR);
                            HideLoader();
                        }
                    }, 200);
                }
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        formValidate = new MzValidate('formMpa');
        formValidate.registerFields(vData);
    };

    this.resetOption = function () {
        fillNamed('optMpaAssetCategory', [], 'assetCategoryId', 'assetCategoryName', 'Select Asset Category');
        mzDisableSelect('optMpaAssetCategory', true);
        fillNamed('optMpaAssetType', [], 'assetTypeId', 'assetTypeName', 'Select Asset Type');
        mzDisableSelect('optMpaAssetType', true);
        fillNamed('optMpaPpmGroupId', [], 'ppmGroupId', 'ppmGroupName', 'Select PPM Executor Group');
        mzDisableSelect('optMpaPpmGroupId', true);
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
            $('#h4MpaTitle').html('<i class="fas fa-plus me-2"></i>Add PPM Asset Group');
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
                    $('#h4MpaTitle').html('<i class="fas fa-edit me-2"></i>Edit PPM Asset Group');
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
