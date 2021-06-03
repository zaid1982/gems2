function ModalItemType () {

    const className = 'ModalItemType';
    let self = this;
    let formValidate;
    let itemTypeId;
    let vData;
    let classFrom;
    let refAssetGroup;

    this.init = function () {
        vData = [
            {
                field_id: 'optMitAssetGroup',
                type: 'select',
                name: 'Asset Group',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txaMitDesc',
                type: 'text',
                name: 'Item Type',
                validator: {
                    maxLength: 150,
                    notEmpty: true
                }
            },
            {
                field_id: 'chkMitStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        formValidate = new MzValidate('formMit');
        formValidate.registerFields(vData);

        $('#btnMitSave').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }
            else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        let data = {
                            itemTypeDesc: $('#txaMitDesc').val(),
                            assetGroupId: $('#optMitAssetGroup').val(),
                            itemTypeStatus: $("input[name='chkMitStatus']").is(":checked") ? '1' : '2'
                        };
                        mzAjaxRequest2('item_type/'+itemTypeId, 'PUT', data);
                        classFrom.genTable();
                        $('#modal_item_type').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        $('#btnMitSubmit').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }
            else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        let data = {
                            itemTypeDesc: $('#txaMitDesc').val(),
                            assetGroupId: $('#optMitAssetGroup').val(),
                            itemTypeStatus: $("input[name='chkMitStatus']").is(":checked") ? '1' : '2'
                        };
                        mzAjaxRequest2('item_type', 'POST', data);
                        classFrom.genTable();
                        $('#modal_item_type').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
    };

    this.add = function () {
        ShowLoader();
        setTimeout(function () {
            try {
                itemTypeId = '';
                mzOptionStop('optMitAssetGroup', refAssetGroup, 'Choose Asset Group', 'assetGroupId', 'assetGroupName', {assetGroupStatus: '1'}, 'required');
                formValidate.clearValidation();

                mzSetFieldValue('MitStatus', '1', 'checkSingle', '1');
                $('#lblMitModalTitle').html('<i class="fas fa-plus text-white"></i> Add Item Type');
                $('#btnMitSave').hide();
                $('#btnMitSubmit').show();

                $('#modal_item_type').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.edit = function (_itemTypeId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_itemTypeId]);
                itemTypeId = _itemTypeId;
                mzOptionStop('optMitAssetGroup', refAssetGroup, 'Choose Asset Group', 'assetGroupId', 'assetGroupName');
                formValidate.clearValidation();

                const itemType = mzAjaxRequest2('item_type/'+itemTypeId, 'GET');
                mzSetFieldValue('MitDesc', itemType['itemTypeDesc'], 'textarea');
                mzSetFieldValue('MitAssetGroup', itemType['assetGroupId'], 'select');
                mzSetFieldValue('MitStatus', itemType['itemTypeStatus'], 'checkSingle', '1');

                $('#lblMitModalTitle').html('<i class="fas fa-edit text-white"></i> Edit Item Type');
                $('#btnMitSubmit').hide();
                $('#btnMitSave').show();

                $('#modal_item_type').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.delete = function (_itemTypeId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_itemTypeId]);
                mzAjaxRequest2('item_type/'+_itemTypeId, 'DELETE');
                classFrom.genTable();
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