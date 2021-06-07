function SectionItem () {

    const className = 'SectionItem';
    let self = this;
    let classFrom;
    let isEdit;
    let formValidate;
    let itemId;
    let vData;
    let refStatus;
    let refAssetGroup;
    let refItemType;
    let oTableSiyPicture;
    let modalConfirmDeleteClass;
    let modalUploadPictureClass;

    this.init = function () {
        $('.sectionItem').hide();

        $('#btnSiyBack').on('click', function () {
            $('.sectionItem').hide();
            classFrom.showMain();
            $(window).scrollTop(0);
        });

        vData = [
            {
                field_id: 'optSiyAssetGroup',
                type: 'select',
                name: 'Asset Group',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optSiyItemType',
                type: 'select',
                name: 'Item Type',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtSiyDescription',
                type: 'text',
                name: 'Item Description',
                validator: {
                    maxLength: 200,
                    notEmpty: true
                }
            },
            {
                field_id: 'txtSiyThreshold',
                type: 'text',
                name: 'Default Threshold',
                validator: {
                    maxLength: 4,
                    notEmpty: true,
                    digit: true,
                    numeric: true,
                    min: 1
                }
            },
            {
                field_id: 'txtSiyMinOrder',
                type: 'text',
                name: 'Minimum Order',
                validator: {
                    maxLength: 4,
                    notEmpty: true,
                    digit: true,
                    numeric: true,
                    min: 1,
                    higher: {
                        id: 'txtSiyThreshold',
                        label: 'Default Threshold'
                    }
                }
            },
            {
                field_id: 'txtSiyMaxOrder',
                type: 'text',
                name: 'Maximum Order',
                validator: {
                    maxLength: 4,
                    notEmpty: true,
                    digit: true,
                    numeric: true,
                    min: 1,
                    higher: {
                        id: 'txtSiyMinOrder',
                        label: 'Minimum Order'
                    }
                }
            },
            {
                field_id: 'txtSiyTurn',
                type: 'text',
                name: 'Option Turn',
                validator: {
                    maxLength: 3,
                    notEmpty: true,
                    digit: true,
                    numeric: true
                }
            },
            {
                field_id: 'txaSiyRemark',
                type: 'text',
                name: 'Remark',
                validator: {
                    maxLength: 1000,
                    notEmpty: true
                }
            },
            {
                field_id: 'txtSiyStatus',
                type: 'text',
                name: 'Status',
                validator: {
                }
            }
        ];

        formValidate = new MzValidate('formSiy');
        formValidate.registerFields(vData);

        oTableSiyPicture =  $('#dtSiyPicture').DataTable({
            bLengthChange: false,
            bFilter: false,
            language: _DATATABLE_LANGUAGE,
            bInfo: false,
            bPaginate: false,
            ordering: false,
            autoWidth: false,
            columnDefs: [
                { className: 'text-center', targets: [0, 2, 3] }
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                initPhotoSwipeFromDOM('.mdb-lightbox');
                $('.lnkSiyPictureDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableSiyPicture.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['itemImageId'], modalUploadPictureClass);
                    }
                });
            },
            aoColumns: [
                {mData: null},
                {mData: 'uploadName'},
                {mData: null, width: '30%',
                    mRender: function (data, type, row) {
                        let dataSize = '1600x1067';
                        if (row['uploadFileWidth'] !== '' && row['uploadFileHeight'] !== '') {
                            dataSize = row['uploadFileWidth'] + 'x' + row['uploadFileHeight'];
                        }
                        return '<div class="mdb-lightbox no-margin">\n' +
                            '<figure class="col-md-12">\n' +
                            '<a href="'+mzUrlDownload+row['uploadFolder']+'/'+row['uploadFilename']+'.'+row['uploadExtension']+'" data-size="'+dataSize+'">\n' +
                            '<img src="'+mzUrlDownload+row['uploadFolder']+'/'+row['uploadFilename']+'.'+row['uploadExtension']+'" class="img-fluid" width="100%">\n' +
                            '</a>\n' +
                            '</figure>\n' +
                            '</div>';
                    }},
                {mData: null, width: '40px',
                    mRender: function (data, type, row, meta) {
                        return '<a><i class="fas fa-trash-alt fa-2x red-text lnkSiyPictureDelete" id="lnkSiyPictureDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete Item Image"></i></a>';
                    }}
            ]
        });

        $('#btnSiySave').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }
            else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        let data = {
                            itemDescription: $('#txtSiyDescription').val(),
                            itemThreshold: $('#txtSiyThreshold').val(),
                            itemMinOrder: $('#txtSiyMinOrder').val(),
                            itemMaxOrder: $('#txtSiyMaxOrder').val(),
                            itemRemark: $('#txaSiyRemark').val(),
                            itemTurn: $('#txtSiyTurn').val()
                        };
                        if (itemId === '') {
                            data['itemTypeId'] = $('#optSiyItemType').val();
                            itemId = mzAjaxRequest2('item', 'POST', data);
                            mzSetFieldValue('SiyStatus', refStatus[1]['statusDesc'], 'text');
                            $('#btnSiyDisable').show();
                        } else {
                            mzAjaxRequest2('item/'+itemId, 'PUT', data);
                        }
                        classFrom.genTable();
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        $('#btnSiyDisable').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    mzAjaxRequest2('item/disable/'+itemId, 'PUT');
                    mzSetFieldValue('SiyStatus', refStatus[2]['statusDesc'], 'text');
                    $('#btnSiyDisable').hide();
                    $('#btnSiyEnable').show();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnSiyEnable').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    mzAjaxRequest2('item/enable/'+itemId, 'PUT');
                    mzSetFieldValue('SiyStatus', refStatus[1]['statusDesc'], 'text');
                    $('#btnSiyEnable').hide();
                    $('#btnSiyDisable').show();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnSiyDelete').on('click', function () {
            modalConfirmDeleteClass.delete(itemId, self);
        });

        $('#optSiyAssetGroup').on('change', function () {
            const assetGroupId = $(this).val();
            ShowLoader();
            setTimeout(function () {
                try {
                    self.setOptionItemType(assetGroupId);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnSiyUpload').on('click', function () {
            if (itemId === '') {
                toastr['error']('Please save the item for the first time before uploading the item image', _ALERT_TITLE_ERROR);
                return false;
            }
            modalUploadPictureClass.add(itemId);
        });
    };

    this.setOptionItemType =  function (assetGroupId) {
        if (assetGroupId !== '') {
            mzOptionStop('optSiyItemType', refItemType, 'Choose Item Type', 'itemTypeId', 'itemTypeDesc', {itemTypeStatus: '1', assetGroupId: assetGroupId}, 'required');
        } else {
            mzOptionStopClear('optSiyItemType', 'Choose Item Type', 'required');
        }
    };

    this.add = function () {
        ShowLoader();
        setTimeout(function () {
            try {
                itemId = '';
                isEdit = true;
                formValidate.clearValidation();

                mzOptionStop('optSiyAssetGroup', refAssetGroup, 'Choose Asset Group', 'assetGroupId', 'assetGroupName', {assetGroupStatus: '1'}, 'required');

                mzSetFieldValue('SiyStatus', 'Draft', 'text');
                mzDisableSelect('optSiyAssetGroup', false);
                mzDisableSelect('optSiyItemType', false);
                self.setOptionItemType('');

                $('#btnSiyDisable, #btnSiyEnable').hide();
                $('.sectionItem').show();
                classFrom.hideMain();
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.load = function (_isEdit, _itemId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_isEdit, _itemId]);
                isEdit = _isEdit;
                itemId = _itemId;
                formValidate.clearValidation();

                if (_isEdit) {
                    mzOptionStop('optSiyAssetGroup', refAssetGroup, 'Choose Asset Group', 'assetGroupId', 'assetGroupName', {assetGroupStatus: '1'}, 'required');
                } else {
                    mzOptionStop('optSiyAssetGroup', refAssetGroup, 'Choose Asset Group', 'assetGroupId', 'assetGroupName');
                }

                const item = mzAjaxRequest2('item/'+itemId, 'GET');
                let assetGroupId = '';
                const itemTypeId = item['itemTypeId'];
                if (itemTypeId !== '') {
                    const itemType = refItemType[itemTypeId];
                    assetGroupId = itemType['assetGroupId'];
                    self.setOptionItemType(assetGroupId);
                }
                mzSetFieldValue('SiyAssetGroup', assetGroupId, 'select');
                mzSetFieldValue('SiyItemType', itemTypeId, 'select');
                mzSetFieldValue('SiyDescription', item['itemDescription'], 'text');
                mzSetFieldValue('SiyThreshold', item['itemThreshold'], 'text');
                mzSetFieldValue('SiyMinOrder', item['itemMinOrder'], 'text');
                mzSetFieldValue('SiyMaxOrder', item['itemMaxOrder'], 'text');
                mzSetFieldValue('SiyTurn', item['itemTurn'], 'text');
                mzSetFieldValue('SiyRemark', item['itemRemark'], 'textarea');
                mzSetFieldValue('SiyStatus', refStatus[item['itemStatus']]['statusDesc'], 'text');
                mzDisableSelect('optSiyAssetGroup', true);
                mzDisableSelect('optSiyItemType', true);
                self.genTablePicture();

                $('#btnSiyDisable, #btnSiyEnable').hide();
                if (item['itemStatus'] === '1') {
                    $('#btnSiyDisable').show();
                } else {
                    $('#btnSiyEnable').show();
                }
                $('.sectionItem').show();
                classFrom.hideMain();
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.delete = function (_itemId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_itemId]);
                mzAjaxRequest2('item/'+_itemId, 'DELETE');
                classFrom.genTable();
                $('.sectionItem').hide();
                classFrom.showMain();
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.genTablePicture = function (type) {
        const dataDb = mzAjaxRequest2('item_image/'+itemId, 'GET');
        oTableSiyPicture.clear().rows.add(dataDb).draw();
        if (typeof type !== 'undefined' && type === 1) {
            classFrom.genTable();
        }
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefItemType = function (_refItemType) {
        refItemType = _refItemType;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };

    this.setModalUploadPictureClass = function (_modalUploadPictureClass) {
        modalUploadPictureClass = _modalUploadPictureClass;
    };
}