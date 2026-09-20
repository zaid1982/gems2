function ModalAssetModel() {

    const className = 'ModalAssetModel';
    let self = this;
    let assetModelId = '';
    let rowRefresh = '';
    let classFrom;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refAssetBrand;
    let assetTypeId;

    function objectRows(ref, nameKey) {
        const rows = [];
        $.each(ref, function (key, row) {
            if (!row || typeof row !== 'object') {
                return true;
            }
            rows.push(row);
            return true;
        });
        rows.sort(function (a, b) {
            return (a[nameKey] || '').localeCompare(b[nameKey] || '');
        });
        return rows;
    }

    function assetBrandRows(activeOnly) {
        const rows = [];
        $.each(refAssetBrand, function (key, brand) {
            if (!brand || typeof brand !== 'object') {
                return true;
            }
            if (activeOnly && String(brand['assetBrandStatus']) !== '1') {
                return true;
            }
            rows.push(brand);
            return true;
        });
        rows.sort(function (a, b) {
            return (a['assetBrandName'] || '').localeCompare(b['assetBrandName'] || '');
        });
        return rows;
    }

    function fillContextSelects(groupId, categoryId, typeId) {
        GemsUI.fillSelect(
            'optMzmAssetGroupId',
            objectRows(refAssetGroup, 'assetGroupName'),
            'assetGroupId',
            function (row) {
                return row['assetGroupName'] || '';
            },
            'Choose Asset Group',
            groupId
        );
        GemsUI.fillSelect(
            'optMzmAssetCategoryId',
            objectRows(refAssetCategory, 'assetCategoryName'),
            'assetCategoryId',
            function (row) {
                return row['assetCategoryName'] || '';
            },
            'Choose Asset Category',
            categoryId
        );
        GemsUI.fillSelect(
            'optMzmAssetTypeId',
            objectRows(refAssetType, 'assetTypeName'),
            'assetTypeId',
            function (row) {
                return row['assetTypeName'] || '';
            },
            'Choose Asset Type',
            typeId
        );
    }

    function fillAssetBrandSelect(activeOnly, selected) {
        GemsUI.fillSelect(
            'optMzmAssetBrandId',
            assetBrandRows(activeOnly),
            'assetBrandId',
            function (row) {
                return row['assetBrandName'] || '';
            },
            'Choose Asset Brand',
            selected
        );
    }

    function setBrandDisabled(disabled) {
        $('#optMzmAssetBrandId').prop('disabled', !!disabled);
    }

    function typeContextIds() {
        const assetCategoryId = refAssetType[assetTypeId]['assetCategoryId'];
        const assetGroupId = refAssetCategory[assetCategoryId]['assetGroupId'];
        return { assetGroupId: assetGroupId, assetCategoryId: assetCategoryId };
    }

    this.init = function () {
        fillContextSelects('', '', '');

        const vData = [
            {
                field_id: 'optMzmAssetGroupId',
                type: 'select',
                name: 'Asset Group',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMzmAssetCategoryId',
                type: 'select',
                name: 'Asset Category',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMzmAssetTypeId',
                type: 'select',
                name: 'Asset Type',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMzmAssetBrandId',
                type: 'select',
                name: 'Asset Brand',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMzmName',
                type: 'text',
                name: 'Asset Model',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'txaMzmDesc',
                type: 'text',
                name: 'Description',
                validator: {
                    maxLength: 255
                }
            },
            {
                field_id: 'chkMzmStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        let formValidate = new MzValidate('formMzm');
        formValidate.registerFields(vData);

        $('#formMzm').on('keyup change', function () {
            $('#btnMzmSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_asset_model').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMzmSubmit').attr('disabled', true);
            setBrandDisabled(false);
        });

        $('#btnMzmSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const assetBrandId = $('#optMzmAssetBrandId').val();
                        const txtName = $('#txtMzmName').val();
                        const txtDesc = $('#txaMzmDesc').val();
                        const statusVal = $("input[name='chkMzmStatus']").is(":checked") ? '1' : '2';
                        const data = {
                            assetModelName: txtName,
                            assetModelDesc: txtDesc,
                            assetBrandId: assetBrandId,
                            assetTypeId: assetTypeId,
                            assetModelStatus: statusVal
                        };

                        let tempRow = {};
                        if (assetModelId === '') {
                            assetModelId = mzAjaxRequest('asset_model.php', 'POST', data);
                            if (classFrom.getClassName() === 'MainAssetType') {
                                tempRow['assetModelId'] = assetModelId;
                                tempRow['assetModelName'] = txtName;
                                tempRow['assetModelDesc'] = txtDesc;
                                tempRow['assetBrandId'] = assetBrandId;
                                tempRow['assetTypeId'] = assetTypeId;
                                tempRow['assetModelStatus'] = statusVal;
                                classFrom.addTableAtyModel(tempRow);
                            }
                        } else {
                            data['action'] = 'update';
                            mzAjaxRequest('asset_model.php?assetModelId='+assetModelId, 'PUT', data);
                            if (classFrom.getClassName() === 'MainAssetType') {
                                tempRow['assetModelId'] = assetModelId;
                                tempRow['assetModelName'] = txtName;
                                tempRow['assetModelDesc'] = txtDesc;
                                tempRow['assetModelStatus'] = statusVal;
                                classFrom.updateTableAtyModel(tempRow, rowRefresh);
                            }
                        }
                        $('#modal_asset_model').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.add = function () {
        ShowLoader();
        setTimeout(function () {
            try {
                fillAssetBrandSelect(true);
                assetModelId = '';
                rowRefresh = '';

                const context = typeContextIds();
                fillContextSelects(context.assetGroupId, context.assetCategoryId, assetTypeId);
                setBrandDisabled(false);
                mzSetFieldValue('MzmStatus', '1', 'checkSingle', '1');

                $('#lblMzmTitle').html('<i class="fas fa-plus me-2"></i>Add Asset Model');
                $('#modal_asset_model').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.edit = function (_assetModelId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetModelId, _rowRefresh]);
                assetModelId = _assetModelId;
                rowRefresh = _rowRefresh;

                const dataMzm = mzAjaxRequest('asset_model.php?assetModelId='+assetModelId, 'GET');
                if (assetTypeId !== dataMzm['assetTypeId']) {
                    toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
                }
                else {
                    const context = typeContextIds();
                    fillContextSelects(context.assetGroupId, context.assetCategoryId, assetTypeId);
                    fillAssetBrandSelect(false, dataMzm['assetBrandId']);
                    mzSetFieldValue('MzmName', dataMzm['assetModelName'], 'text');
                    mzSetFieldValue('MzmDesc', dataMzm['assetModelDesc'], 'textarea');
                    mzSetFieldValue('MzmStatus', dataMzm['assetModelStatus'], 'checkSingle', '1');

                    setBrandDisabled(true);

                    $('#lblMzmTitle').html('<i class="far fa-edit me-2"></i>Edit Asset Model');
                    $('#modal_asset_model').modal({backdrop: 'static', keyboard: false});
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_assetModelId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetModelId, _rowRefresh]);
                mzAjaxRequest('asset_model.php?assetModelId='+_assetModelId, 'PUT', {action: 'deactivate'});
                const tempRow = {assetModelStatus:'2'};
                if (classFrom.getClassName() === 'MainAssetType') {
                    classFrom.updateTableAtyModel(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_assetModelId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetModelId, _rowRefresh]);
                mzAjaxRequest('asset_model.php?assetModelId='+_assetModelId, 'PUT', {action: 'activate'});
                const tempRow = {assetModelStatus:'1'};
                if (classFrom.getClassName() === 'MainAssetType') {
                    classFrom.updateTableAtyModel(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_assetModelId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetModelId]);
                mzAjaxRequest('asset_model.php?assetModelId='+_assetModelId, 'DELETE');
                if (classFrom.getClassName() === 'MainAssetType') {
                    classFrom.deleteTableAtyModel();
                }
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

    this.setAssetTypeId = function (_assetTypeId) {
        assetTypeId = _assetTypeId;
    };
}
