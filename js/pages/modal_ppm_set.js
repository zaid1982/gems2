function ModalPpmSet () {

    const className = 'ModalPpmSet';
    let self = this;
    let formValidate;
    let classFrom;
    let submitType = '';
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refPpmGroup;
    let ppmSetId;
    let contractId;
    let siteId;

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

    function clearAndDisable(id, valueKey, labelKey, placeholder) {
        fillNamed(id, [], valueKey, labelKey, placeholder);
        mzDisableSelect(id, true);
    }

    function successAfterSubmit() {
        const msg = submitType === 'add' ? 'PPM Set successfully created!' : 'PPM Set successfully updated!';
        toastr['success'](msg, _ALERT_TITLE_SUCCESS);
        classFrom.genTable();
        $('#modal_ppm_set').modal('hide');
        HideLoader();
    }

    this.init = function () {

        $('#optMpsAssetGroup').on('change', function () {
            const id = $(this).val();
            try {
                clearAndDisable('optMpsPpmGroupId', 'ppmGroupId', 'ppmGroupName', 'Select PPM Executor Group');
                clearAndDisable('optMpsAssetType', 'assetTypeId', 'assetTypeName', 'Select Asset Type');
                fillNamed(
                    'optMpsAssetCategory',
                    filterRows(rowsFromRef(refAssetCategory, 'assetCategoryId'), {assetGroupId: id, assetCategoryStatus: '1'}),
                    'assetCategoryId',
                    'assetCategoryName',
                    'Select Asset Category'
                );
                mzDisableSelect('optMpsAssetCategory', false);
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#optMpsAssetCategory').on('change', function () {
            const id = $(this).val();
            try {
                clearAndDisable('optMpsPpmGroupId', 'ppmGroupId', 'ppmGroupName', 'Select PPM Executor Group');
                fillNamed(
                    'optMpsAssetType',
                    filterRows(rowsFromRef(refAssetType, 'assetTypeId'), {assetCategoryId: id, assetTypeStatus: '1'}),
                    'assetTypeId',
                    'assetTypeName',
                    'Select Asset Type'
                );
                mzDisableSelect('optMpsAssetType', false);
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#optMpsAssetType').on('change', function () {
            try {
                fillNamed(
                    'optMpsPpmGroupId',
                    filterRows(rowsFromRef(refPpmGroup, 'ppmGroupId'), {roleId: '5', siteId: siteId, ppmGroupStatus: '1'}),
                    'ppmGroupId',
                    'ppmGroupName',
                    'Select PPM Executor Group'
                );
                mzDisableSelect('optMpsPpmGroupId', false);
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#btnMpsSubmit').on('click', function () {
            try {
                if (!formValidate.validateNow()) {
                    toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                } else {
                    let data = {
                        ppmSetName: mzNullString('txtMpsName'),
                        ppmSetDesc: mzNullString('txaMpsDesc'),
                        assetTypeId: mzNullInt('optMpsAssetType'),
                        ppmGroupId: mzNullInt('optMpsPpmGroupId'),
                        assetGroupId: mzNullInt('optMpsAssetGroup'),
                        assetCategoryId: mzNullInt('optMpsAssetCategory')
                    };
                    ShowLoader(); setTimeout(function () {
                        try {
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
                        } catch (e) {
                            toastr['error'](e.message, _ALERT_TITLE_ERROR);
                            HideLoader();
                        }
                    }, 200);
                }
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        formValidate = new MzValidate('formMps');
        formValidate.registerFields(vData);
    };

    this.resetOption = function () {
        clearAndDisable('optMpsPpmGroupId', 'ppmGroupId', 'ppmGroupName', 'Select PPM Executor Group');
        clearAndDisable('optMpsAssetType', 'assetTypeId', 'assetTypeName', 'Select Asset Type');
        clearAndDisable('optMpsAssetCategory', 'assetCategoryId', 'assetCategoryName', 'Select Asset Category');
        fillNamed('optMpsAssetGroup', [], 'assetGroupId', 'assetGroupName', 'Select Asset Group');

        mzSetFieldValue('txtMpsName', '', 'text');
        mzSetFieldValue('txaMpsDesc', '', 'text');
        $('#lblMpsId').val('');
    };

    this.add = function () {
        try {
            submitType = 'add';
            formValidate.clearValidation();
            self.resetOption();

            fillNamed(
                'optMpsAssetGroup',
                filterRows(rowsFromRef(refAssetGroup, 'assetGroupId'), {assetGroupStatus: '1'}),
                'assetGroupId',
                'assetGroupName',
                'Select Asset Group'
            );
            mzDisableSelect('optMpsAssetGroup', false);

            $('#h4MpsTitle').html('<i class="fas fa-plus me-2"></i>Add PPM Set');
            $('#modal_ppm_set').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
        } catch (e) { toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR); }
    };

    this.edit = function (_ppmSetId) {
        try {
            mzCheckFuncParam([_ppmSetId]);
            ppmSetId = _ppmSetId;
            submitType = 'put';
            ShowLoader();
            mzFetch('api/ppm.php?type=ppm_set_details&ppmSetId='+ppmSetId, 'GET').then(res => {
                formValidate.clearValidation();
                self.resetOption();

                $('#lblMpsId').val(res['ppmSetId']);

                mzSetFieldValue('txtMpsName', res['ppmSetName']);
                mzSetFieldValue('txaMpsDesc', res['ppmSetDesc']);

                fillNamed(
                    'optMpsAssetGroup',
                    filterRows(rowsFromRef(refAssetGroup, 'assetGroupId'), {assetGroupStatus: '1'}),
                    'assetGroupId',
                    'assetGroupName',
                    'Select Asset Group'
                );
                mzDisableSelect('optMpsAssetGroup', false);

                mzSetFieldValue('optMpsAssetGroup', res['assetGroupId']);
                $('#optMpsAssetGroup').trigger('change');
                mzSetFieldValue('optMpsAssetCategory', res['assetCategoryId']);
                $('#optMpsAssetCategory').trigger('change');
                mzSetFieldValue('optMpsAssetType', res['assetTypeId']);
                $('#optMpsAssetType').trigger('change');

                mzSetFieldValue('optMpsPpmGroupId', res['ppmGroupId']);

                const isDisable = res['ppmSetStatus'] != 1;
                if (isDisable) {
                    mzDisableSelect('optMpsAssetGroup', true);
                    mzDisableSelect('optMpsAssetCategory', true);
                    mzDisableSelect('optMpsAssetType', true);
                    mzDisableSelect('optMpsPpmGroupId', true);
                    $('#txtMpsName').prop('disabled', true);
                    $('#txaMpsDesc').prop('disabled', true);
                }

                formValidate.disableField('optMpsAssetGroup', isDisable);
                formValidate.disableField('optMpsAssetCategory', isDisable);
                formValidate.disableField('optMpsAssetType', isDisable);
                formValidate.disableField('optMpsPpmGroupId', isDisable);
                formValidate.disableField('txtMpsName', isDisable);
                formValidate.disableField('txaMpsDesc', isDisable);

                $('#h4MpsTitle').html('<i class="fas fa-edit me-2"></i>Edit PPM Set');
                $('#modal_ppm_set').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
                HideLoader();
            }).catch((e) => {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
                HideLoader();
            });
        } catch (e) {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            HideLoader();
        }
    };

    this.delete = function (_ppmSetId) {
        try {
            mzCheckFuncParam([_ppmSetId]);
            toastr['info']('Delete functionality for PPM Set not yet implemented!', _ALERT_TITLE_INFO);
            // ShowLoader(); setTimeout(function () {
            //     mzFetch('api/ppm.php?action=delete_ppm_set&ppmSetId='+_ppmSetId, 'DELETE').then(res => {
            //         classFrom.genTable();
            //         $('#modal_ppm_set').modal('hide');
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
