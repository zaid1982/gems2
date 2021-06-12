function ModalPartAdd() {

    const className = 'ModalItemType';
    let self = this;
    let formValidate;
    let vData;
    let classFrom;
    let storeId;
    let refAssetGroup;
    let refItemType;
    let refItem;

    this.init = function () {
        vData = [
            {
                field_id: 'optMpaAssetGroup',
                type: 'select',
                name: 'Asset Group',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMpaItemType',
                type: 'select',
                name: 'Item Type',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMpaItem',
                type: 'select',
                name: 'Item Description',
                validator: {
                    notEmpty: true
                }
            }
        ];

        formValidate = new MzValidate('formMpa');
        formValidate.registerFields(vData);

        $('#optMpaAssetGroup').on('change', function(){
            const assetGroupId = $(this).val();
            ShowLoader();
            setTimeout(function () {
                try {
                    const optionItemType = mzAjaxRequest2('part/add_option_item_type/'+storeId+'/'+assetGroupId, 'GET');
                    mzOptionStop('optMpaItemType', refItemType, 'Choose Item Type', 'itemTypeId', 'itemTypeDesc', {itemTypeId: '('+optionItemType.join()+')'}, 'required');
                    mzOptionStopClear('optMpaItem', 'Choose Item Description', 'required');
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#optMpaItemType').on('change', function(){
            const itemTypeId = $(this).val();
            ShowLoader();
            setTimeout(function () {
                try {
                    const optionItem = mzAjaxRequest2('part/add_option_item/'+storeId+'/'+itemTypeId, 'GET');
                    mzOptionStop('optMpaItem', refItem, 'Choose Item Description', 'itemId', 'itemDescription', {itemId: '('+optionItem.join()+')'}, 'required');
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnMpaSubmit').on('click', function(){
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }
            else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const data = {
                            storeId: storeId,
                            itemId: $('#optMpaItem').val()
                        };
                        const partId = mzAjaxRequest2('part', 'POST', data);
                        $('#modal_part_add').modal('hide');
                        classFrom.genTable();
                        classFrom.loadSectionPart(true, partId);
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
    };

    this.add = function (_storeId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_storeId]);
                storeId = _storeId;
                formValidate.clearValidation();

                const optionAssetGroup = mzAjaxRequest2('part/add_option_asset_group/'+storeId, 'GET');
                mzOptionStop('optMpaAssetGroup', refAssetGroup, 'Choose Asset Group', 'assetGroupId', 'assetGroupName', {assetGroupId: '('+optionAssetGroup.join()+')'}, 'required');
                mzOptionStopClear('optMpaItemType', 'Choose Item Type', 'required');
                mzOptionStopClear('optMpaItem', 'Choose Item Description', 'required');

                $('#modal_part_add').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setSiteName = function (_siteName) {
        mzSetFieldValue('MpaSite', _siteName, 'text');
    };

    this.setStoreName = function (_storeName) {
        mzSetFieldValue('MpaStore', _storeName, 'text');
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefItemType = function (_refItemType) {
        refItemType = _refItemType;
    };

    this.setRefItem = function (_refItem) {
        refItem = _refItem;
    };
}