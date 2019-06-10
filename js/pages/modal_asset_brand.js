function ModalAssetBrand() {

    const className = 'ModalAssetBrand';
    let self = this;
    let assetBrandId = '';
    let rowRefresh = '';
    let classFrom;

    this.init = function () {
        const vData = [
            {
                field_id: 'txtMzbName',
                type: 'text',
                name: 'Asset Brand',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'txaMzbDesc',
                type: 'text',
                name: 'Description',
                validator: {
                    maxLength: 255
                }
            },
            {
                field_id: 'chkMzbStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        let formValidate = new MzValidate('formMzb');
        formValidate.registerFields(vData);

        $('#formMzb').on('keyup change', function () {
            $('#btnMzbSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_asset_brand').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMzbSubmit').attr('disabled', true);
        });

        $('#btnMzbSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const txtName = $('#txtMzbName').val();
                        const txtDesc = $('#txaMzbDesc').val();
                        const statusVal = $("input[name='chkMzbStatus']").is(":checked") ? '1' : '2';
                        const data = {
                            assetBrandName: txtName,
                            assetBrandDesc: txtDesc,
                            assetBrandStatus: statusVal
                        };

                        let tempRow = {};
                        if (assetBrandId === '') {
                            assetBrandId = mzAjaxRequest('asset_brand.php', 'POST', data);
                            if (classFrom.getClassName() === 'MainAssetBrand') {
                                tempRow['assetBrandId'] = assetBrandId;
                                tempRow['assetBrandName'] = txtName;
                                tempRow['assetBrandDesc'] = txtDesc;
                                tempRow['assetBrandStatus'] = statusVal;
                                classFrom.addTableAbr(tempRow);
                            }
                        } else {
                            data['action'] = 'update';
                            mzAjaxRequest('asset_brand.php?assetBrandId='+assetBrandId, 'PUT', data);
                            if (classFrom.getClassName() === 'MainAssetBrand') {
                                tempRow['assetBrandId'] = assetBrandId;
                                tempRow['assetBrandName'] = txtName;
                                tempRow['assetBrandDesc'] = txtDesc;
                                tempRow['assetBrandStatus'] = statusVal;
                                classFrom.updateTableAbr(tempRow, rowRefresh);
                            }
                        }
                        $('#modal_asset_brand').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.add = function () {
        assetBrandId = '';
        rowRefresh = '';

        mzSetFieldValue('MzbStatus', '1', 'checkSingle', '1');
        $('#lblMzbTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add Asset Brand');
        $('#modal_asset_brand').modal({backdrop: 'static', keyboard: false});
    };

    this.edit = function (_assetBrandId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetBrandId, _rowRefresh]);
                assetBrandId = _assetBrandId;
                rowRefresh = _rowRefresh;

                const dataMzb = mzAjaxRequest('asset_brand.php?assetBrandId='+assetBrandId, 'GET');
                mzSetFieldValue('MzbName', dataMzb['assetBrandName'], 'text');
                mzSetFieldValue('MzbDesc', dataMzb['assetBrandDesc'], 'textarea');
                mzSetFieldValue('MzbStatus', dataMzb['assetBrandStatus'], 'checkSingle', '1');

                $('#lblMzbTitle').html('<i class="far fa-edit text-white"></i> &nbsp;Edit Asset Brand');
                $('#modal_asset_brand').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_assetBrandId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetBrandId, _rowRefresh]);
                mzAjaxRequest('asset_brand.php?assetBrandId='+_assetBrandId, 'PUT', {action: 'deactivate'});
                const tempRow = {assetBrandStatus:'2'};
                if (classFrom.getClassName() === 'MainAssetBrand') {
                    classFrom.updateTableAbr(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_assetBrandId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetBrandId, _rowRefresh]);
                mzAjaxRequest('asset_brand.php?assetBrandId='+_assetBrandId, 'PUT', {action: 'activate'});
                const tempRow = {assetBrandStatus:'1'};
                if (classFrom.getClassName() === 'MainAssetBrand') {
                    classFrom.updateTableAbr(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_assetBrandId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetBrandId]);
                mzAjaxRequest('asset_brand.php?assetBrandId='+_assetBrandId, 'DELETE');
                if (classFrom.getClassName() === 'MainAssetBrand') {
                    classFrom.genTableAbr(1);
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