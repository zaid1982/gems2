function ModalAssetGroup() {

    const className = 'ModalAssetGroup';
    let self = this;
    let assetGroupId = '';
    let rowRefresh = '';
    let classFrom;

    this.init = function () {
        const vData = [
            {
                field_id: 'txtMzgName',
                type: 'text',
                name: 'Asset Group',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'txaMzgDesc',
                type: 'text',
                name: 'Description',
                validator: {
                    maxLength: 255
                }
            },
            {
                field_id: 'chkMzgStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        let formValidate = new MzValidate('formMzg');
        formValidate.registerFields(vData);

        $('#formMzg').on('keyup change', function () {
            $('#btnMzgSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_asset_group').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMzgSubmit').attr('disabled', true);
        });

        $('#btnMzgSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const txtName = $('#txtMzgName').val();
                        const txtDesc = $('#txaMzgDesc').val();
                        const statusVal = $("input[name='chkMzgStatus']").is(":checked") ? '1' : '2';
                        const data = {
                            assetGroupName: txtName,
                            assetGroupDesc: txtDesc,
                            assetGroupStatus: statusVal
                        };

                        let tempRow = {};
                        if (assetGroupId === '') {
                            assetGroupId = mzAjaxRequest('asset_group.php', 'POST', data);
                            if (classFrom.getClassName() === 'MainAssetGroup') {
                                tempRow['assetGroupId'] = assetGroupId;
                                tempRow['assetGroupName'] = txtName;
                                tempRow['assetGroupDesc'] = txtDesc;
                                tempRow['assetGroupStatus'] = statusVal;
                                classFrom.addTableAgr(tempRow);
                            }
                        } else {
                            data['action'] = 'update';
                            mzAjaxRequest('asset_group.php?assetGroupId='+assetGroupId, 'PUT', data);
                            if (classFrom.getClassName() === 'MainAssetGroup') {
                                tempRow['assetGroupId'] = assetGroupId;
                                tempRow['assetGroupName'] = txtName;
                                tempRow['assetGroupDesc'] = txtDesc;
                                tempRow['assetGroupStatus'] = statusVal;
                                classFrom.updateTableAgr(tempRow, rowRefresh);
                            }
                        }
                        $('#modal_asset_group').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.add = function () {
        assetGroupId = '';
        rowRefresh = '';

        mzSetFieldValue('MzgStatus', '1', 'checkSingle', '1');
        $('#lblMzgTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add Asset Group');
        $('#modal_asset_group').modal({backdrop: 'static', keyboard: false});
    };

    this.edit = function (_assetGroupId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetGroupId, _rowRefresh]);
                assetGroupId = _assetGroupId;
                rowRefresh = _rowRefresh;

                const dataMzg = mzAjaxRequest('asset_group.php?assetGroupId='+assetGroupId, 'GET');
                mzSetFieldValue('MzgName', dataMzg['assetGroupName'], 'text');
                mzSetFieldValue('MzgDesc', dataMzg['assetGroupDesc'], 'textarea');
                mzSetFieldValue('MzgStatus', dataMzg['assetGroupStatus'], 'checkSingle', '1');

                $('#lblMzgTitle').html('<i class="far fa-edit text-white"></i> &nbsp;Edit Asset Group');
                $('#modal_asset_group').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_assetGroupId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetGroupId, _rowRefresh]);
                mzAjaxRequest('asset_group.php?assetGroupId='+_assetGroupId, 'PUT', {action: 'deactivate'});
                const tempRow = {assetGroupStatus:'2'};
                if (classFrom.getClassName() === 'MainAssetGroup') {
                    classFrom.updateTableAgr(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_assetGroupId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetGroupId, _rowRefresh]);
                mzAjaxRequest('asset_group.php?assetGroupId='+_assetGroupId, 'PUT', {action: 'activate'});
                const tempRow = {assetGroupStatus:'1'};
                if (classFrom.getClassName() === 'MainAssetGroup') {
                    classFrom.updateTableAgr(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_assetGroupId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetGroupId]);
                mzAjaxRequest('asset_group.php?assetGroupId='+_assetGroupId, 'DELETE');
                if (classFrom.getClassName() === 'MainAssetGroup') {
                    classFrom.genTableAgr(1);
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
}