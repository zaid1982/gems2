function ModalAssetCategory() {

    const className = 'ModalAssetCategory';
    let self = this;
    let assetCategoryId = '';
    let rowRefresh = '';
    let classFrom;
    let refAssetGroup;

    this.init = function () {
        const vData = [
            {
                field_id: 'optMzcAssetGroupId',
                type: 'select',
                name: 'Asset Group',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMzcName',
                type: 'text',
                name: 'Asset Category',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'txaMzcDesc',
                type: 'text',
                name: 'Description',
                validator: {
                    maxLength: 255
                }
            },
            {
                field_id: 'chkMzcStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        let formValidate = new MzValidate('formMzc');
        formValidate.registerFields(vData);

        $('#formMzc').on('keyup change', function () {
            $('#btnMzcSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_asset_category').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMzcSubmit').attr('disabled', true);
            mzDisableSelect('optMzcAssetGroupId', false);
        });

        $('#btnMzcSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const assetGroupId = $('#optMzcAssetGroupId').val();
                        const txtName = $('#txtMzcName').val();
                        const txtDesc = $('#txaMzcDesc').val();
                        const statusVal = $("input[name='chkMzcStatus']").is(":checked") ? '1' : '2';
                        const data = {
                            assetGroupId: assetGroupId,
                            assetCategoryName: txtName,
                            assetCategoryDesc: txtDesc,
                            assetCategoryStatus: statusVal
                        };

                        let tempRow = {};
                        if (assetCategoryId === '') {
                            assetCategoryId = mzAjaxRequest('asset_category.php', 'POST', data);
                            if (classFrom.getClassName() === 'MainAssetCategory') {
                                tempRow['assetGroupId'] = assetGroupId;
                                tempRow['assetCategoryId'] = assetCategoryId;
                                tempRow['assetCategoryName'] = txtName;
                                tempRow['assetCategoryDesc'] = txtDesc;
                                tempRow['assetCategoryStatus'] = statusVal;
                                classFrom.addTableAct(tempRow);
                            }
                        } else {
                            data['action'] = 'update';
                            mzAjaxRequest('asset_category.php?assetCategoryId='+assetCategoryId, 'PUT', data);
                            if (classFrom.getClassName() === 'MainAssetCategory') {
                                tempRow['assetGroupId'] = assetGroupId;
                                tempRow['assetCategoryId'] = assetCategoryId;
                                tempRow['assetCategoryName'] = txtName;
                                tempRow['assetCategoryDesc'] = txtDesc;
                                tempRow['assetCategoryStatus'] = statusVal;
                                classFrom.updateTableAct(tempRow, rowRefresh);
                            }
                        }
                        $('#modal_asset_category').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.add = function () {
        assetCategoryId = '';
        rowRefresh = '';

        ShowLoader();
        setTimeout(function () {
            try {
                mzOption('optMzcAssetGroupId', refAssetGroup, 'Choose Asset Group', 'assetGroupId', 'assetGroupName', {assetGroupStatus: '1'}, 'required');

                $('#lblMzcTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add Asset Category');
                $('#modal_asset_category').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.edit = function (_assetCategoryId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzOption('optMzcAssetGroupId', refAssetGroup, 'Choose Asset Group', 'assetGroupId', 'assetGroupName');
                mzCheckFuncParam([_assetCategoryId, _rowRefresh]);
                assetCategoryId = _assetCategoryId;
                rowRefresh = _rowRefresh;

                const dataMzc = mzAjaxRequest('asset_category.php?assetCategoryId='+assetCategoryId, 'GET');
                mzSetFieldValue('MzcAssetGroupId', dataMzc['assetGroupId'], 'select', 'Asset Group *');
                mzSetFieldValue('MzcName', dataMzc['assetCategoryName'], 'text');
                mzSetFieldValue('MzcDesc', dataMzc['assetCategoryDesc'], 'textarea');
                mzSetFieldValue('MzcStatus', dataMzc['assetCategoryStatus'], 'checkSingle', '1');

                mzDisableSelect('optMzcAssetGroupId', true);

                $('#lblMzcTitle').html('<i class="far fa-edit text-white"></i> &nbsp;Edit Asset Category');
                $('#modal_asset_category').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_assetCategoryId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetCategoryId, _rowRefresh]);
                mzAjaxRequest('asset_category.php?assetCategoryId='+_assetCategoryId, 'PUT', {action: 'deactivate'});
                const tempRow = {assetCategoryStatus:'2'};
                if (classFrom.getClassName() === 'MainAssetCategory') {
                    classFrom.updateTableAct(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_assetCategoryId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetCategoryId, _rowRefresh]);
                mzAjaxRequest('asset_category.php?assetCategoryId='+_assetCategoryId, 'PUT', {action: 'activate'});
                const tempRow = {assetCategoryStatus:'1'};
                if (classFrom.getClassName() === 'MainAssetCategory') {
                    classFrom.updateTableAct(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_assetCategoryId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetCategoryId, _rowRefresh]);
                mzAjaxRequest('asset_category.php?assetCategoryId='+_assetCategoryId, 'DELETE');
                if (classFrom.getClassName() === 'MainAssetCategory') {
                    classFrom.deleteTableAct(_rowRefresh);
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
}