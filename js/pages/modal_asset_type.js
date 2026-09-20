function ModalAssetType() {

    const className = 'ModalAssetType';
    let self = this;
    let assetTypeId = '';
    let rowRefresh = '';
    let classFrom;
    let refAssetCategory;
    let refAssetGroup;

    function assetGroupRows(activeOnly) {
        const rows = [];
        $.each(refAssetGroup, function (key, group) {
            if (!group || typeof group !== 'object') {
                return true;
            }
            if (activeOnly && String(group['assetGroupStatus']) !== '1') {
                return true;
            }
            rows.push(group);
            return true;
        });
        rows.sort(function (a, b) {
            return (a['assetGroupName'] || '').localeCompare(b['assetGroupName'] || '');
        });
        return rows;
    }

    function assetCategoryRows(activeOnly, groupId) {
        const rows = [];
        $.each(refAssetCategory, function (key, category) {
            if (!category || typeof category !== 'object') {
                return true;
            }
            if (groupId && String(category['assetGroupId']) !== String(groupId)) {
                return true;
            }
            if (activeOnly && String(category['assetCategoryStatus']) !== '1') {
                return true;
            }
            rows.push(category);
            return true;
        });
        rows.sort(function (a, b) {
            return (a['assetCategoryName'] || '').localeCompare(b['assetCategoryName'] || '');
        });
        return rows;
    }

    function fillAssetGroupSelect(activeOnly, selected) {
        GemsUI.fillSelect(
            'optMztAssetGroupId',
            assetGroupRows(activeOnly),
            'assetGroupId',
            function (row) {
                return row['assetGroupName'] || '';
            },
            'Choose Asset Group',
            selected
        );
    }

    function fillAssetCategorySelect(activeOnly, groupId, selected) {
        const rows = groupId || !activeOnly
            ? assetCategoryRows(activeOnly, activeOnly ? groupId : null)
            : [];
        GemsUI.fillSelect(
            'optMztAssetCategoryId',
            rows,
            'assetCategoryId',
            function (row) {
                return row['assetCategoryName'] || '';
            },
            'Choose Asset Category',
            selected
        );
    }

    function setParentSelectsDisabled(disabled) {
        $('#optMztAssetGroupId').prop('disabled', !!disabled);
        $('#optMztAssetCategoryId').prop('disabled', !!disabled);
    }

    this.init = function () {
        $('#optMztAssetGroupId').on('change', function () {
            fillAssetCategorySelect(true, $(this).val(), '');
        });

        const vData = [
            {
                field_id: 'optMztAssetGroupId',
                type: 'select',
                name: 'Asset Group',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMztAssetCategoryId',
                type: 'select',
                name: 'Asset Category',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMztName',
                type: 'text',
                name: 'Asset Type',
                validator: {
                    notEmpty: true,
                    maxLength: 255
                }
            },
            {
                field_id: 'txaMztDesc',
                type: 'text',
                name: 'Description',
                validator: {
                    maxLength: 255
                }
            },
            {
                field_id: 'chkMztStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        let formValidate = new MzValidate('formMzt');
        formValidate.registerFields(vData);

        $('#formMzt').on('keyup change', function () {
            $('#btnMztSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_asset_type').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMztSubmit').attr('disabled', true);
            setParentSelectsDisabled(false);
        });

        $('#btnMztSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const assetGroupId = $('#optMztAssetGroupId').val();
                        const assetCategoryId = $('#optMztAssetCategoryId').val();
                        const txtName = $('#txtMztName').val();
                        const txtDesc = $('#txaMztDesc').val();
                        const statusVal = $("input[name='chkMztStatus']").is(":checked") ? '1' : '2';
                        const data = {
                            assetCategoryId: assetCategoryId,
                            assetTypeName: txtName,
                            assetTypeDesc: txtDesc,
                            assetTypeStatus: statusVal
                        };

                        let tempRow = {};
                        if (assetTypeId === '') {
                            assetTypeId = mzAjaxRequest('asset_type.php', 'POST', data);
                            if (classFrom.getClassName() === 'MainAssetType') {
                                tempRow['assetGroupId'] = assetGroupId;
                                tempRow['assetCategoryId'] = assetCategoryId;
                                tempRow['assetTypeId'] = assetTypeId;
                                tempRow['assetTypeName'] = txtName;
                                tempRow['assetTypeDesc'] = txtDesc;
                                tempRow['totalModel'] = 0;
                                tempRow['assetTypeStatus'] = statusVal;
                                classFrom.addTableAty(tempRow);
                            }
                        } else {
                            data['action'] = 'update';
                            mzAjaxRequest('asset_type.php?assetTypeId='+assetTypeId, 'PUT', data);
                            if (classFrom.getClassName() === 'MainAssetType') {
                                tempRow['assetTypeId'] = assetTypeId;
                                tempRow['assetTypeName'] = txtName;
                                tempRow['assetTypeDesc'] = txtDesc;
                                tempRow['assetTypeStatus'] = statusVal;
                                classFrom.updateTableAty(tempRow, rowRefresh);
                            }
                        }
                        $('#modal_asset_type').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.add = function () {
        assetTypeId = '';
        rowRefresh = '';

        ShowLoader();
        setTimeout(function () {
            try {
                fillAssetGroupSelect(true);
                fillAssetCategorySelect(true, '', '');
                setParentSelectsDisabled(false);

                mzSetFieldValue('MztStatus', '1', 'checkSingle', '1');
                $('#lblMztTitle').html('<i class="fas fa-plus me-2"></i>Add Asset Type');
                $('#modal_asset_type').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.edit = function (_assetTypeId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetTypeId, _rowRefresh]);
                assetTypeId = _assetTypeId;
                rowRefresh = _rowRefresh;

                const dataMzt = mzAjaxRequest('asset_type.php?assetTypeId='+assetTypeId, 'GET');
                const assetCategoryId = dataMzt['assetCategoryId'];
                const assetGroupId = refAssetCategory[assetCategoryId]['assetGroupId'];
                fillAssetGroupSelect(false, assetGroupId);
                fillAssetCategorySelect(false, null, assetCategoryId);
                mzSetFieldValue('MztName', dataMzt['assetTypeName'], 'text');
                mzSetFieldValue('MztDesc', dataMzt['assetTypeDesc'], 'textarea');
                mzSetFieldValue('MztStatus', dataMzt['assetTypeStatus'], 'checkSingle', '1');

                setParentSelectsDisabled(true);

                $('#lblMztTitle').html('<i class="far fa-edit me-2"></i>Edit Asset Type');
                $('#modal_asset_type').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_assetTypeId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetTypeId, _rowRefresh]);
                mzAjaxRequest('asset_type.php?assetTypeId='+_assetTypeId, 'PUT', {action: 'deactivate'});
                const tempRow = {assetTypeStatus:'2'};
                if (classFrom.getClassName() === 'MainAssetType') {
                    classFrom.updateTableAty(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_assetTypeId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetTypeId, _rowRefresh]);
                mzAjaxRequest('asset_type.php?assetTypeId='+_assetTypeId, 'PUT', {action: 'activate'});
                const tempRow = {assetTypeStatus:'1'};
                if (classFrom.getClassName() === 'MainAssetType') {
                    classFrom.updateTableAty(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_assetTypeId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetTypeId]);
                mzAjaxRequest('asset_type.php?assetTypeId='+_assetTypeId, 'DELETE');
                if (classFrom.getClassName() === 'MainAssetType') {
                    classFrom.genTableAty(1);
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
}
