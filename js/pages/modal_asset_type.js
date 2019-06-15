function ModalAssetType() {

    const className = 'ModalAssetType';
    let self = this;
    let assetTypeId = '';
    let rowRefresh = '';
    let classFrom;
    let refAssetCategory;
    let refAssetGroup;

    this.init = function () {
        $('#optMztAssetGroupId').on('change', function () {
            mzOption('optMztAssetCategoryId', refAssetCategory, 'Choose Asset Category', 'assetCategoryId', 'assetCategoryName', {assetCategoryId: $(this).val(), assetCategoryStatus: '1'}, 'required');
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
                    maxLength: 150
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
            mzDisableSelect('optMztAssetGroupId', false);
            mzDisableSelect('optMztAssetCategoryId', false);
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
                mzOptionStop('optMztAssetGroupId', refAssetGroup, 'Choose Asset Group', 'assetGroupId', 'assetGroupName', {assetGroupStatus: '1'}, 'required');

                mzSetFieldValue('MztStatus', '1', 'checkSingle', '1');
                $('#lblMztTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add Asset Type');
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
                mzOptionStop('optMztAssetGroupId', refAssetGroup, 'Choose Asset Group', 'assetGroupId', 'assetGroupName');
                mzOptionStop('optMztAssetCategoryId', refAssetCategory, 'Choose Asset Category', 'assetCategoryId', 'assetCategoryName');
                mzCheckFuncParam([_assetTypeId, _rowRefresh]);
                assetTypeId = _assetTypeId;
                rowRefresh = _rowRefresh;

                const dataMzt = mzAjaxRequest('asset_type.php?assetTypeId='+assetTypeId, 'GET');
                const assetCategoryId = dataMzt['assetCategoryId'];
                mzSetFieldValue('MztAssetGroupId', refAssetCategory[assetCategoryId]['assetGroupId'], 'select', 'Asset Group *');
                mzSetFieldValue('MztAssetCategoryId', assetCategoryId, 'select', 'Asset Category *');
                mzSetFieldValue('MztName', dataMzt['assetTypeName'], 'text');
                mzSetFieldValue('MztDesc', dataMzt['assetTypeDesc'], 'textarea');
                mzSetFieldValue('MztStatus', dataMzt['assetTypeStatus'], 'checkSingle', '1');

                mzDisableSelect('optMztAssetGroupId', true);
                mzDisableSelect('optMztAssetCategoryId', true);

                $('#lblMztTitle').html('<i class="far fa-edit text-white"></i> &nbsp;Edit Asset Type');
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