function SectionAssetDetails() {

    const className = 'SectionAssetDetails';
    let self = this;
    let assetId = '';
    let rowRefresh = '';
    let classFrom;
    let refStatus;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refAssetBrand;
    let refAssetModel;
    let refUser;
    let refContract;
    let refSite;
    let refClient;
    let refPpmGroup;
    let formValidate;
    let versionLocal;
    let qrCodeImg;

    this.init = function () {
        $('.sectionAssetDetails').hide();

        $('#btnSszBack').on('click', function () {
            $('.sectionAssetDetails').hide();
            if (classFrom.getClassName() === 'MainAsset') {
                $('.sectionAszMain').show();
            } else if (classFrom.getClassName() === 'MainPpmManagement') {
                $('.sectionPmgMain').show();
            }
            $(window).scrollTop(0);
        });

        const vData = [
            {
                field_id: 'txtSszAssetName',
                type: 'text',
                name: 'Asset Name',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'txtSszAssetNo',
                type: 'text',
                name: 'Asset No',
                validator: {
                    notEmpty: true,
                    maxLength: 30
                }
            },
            {
                field_id: 'txtSszAssetSerialNo',
                type: 'text',
                name: 'Asset Serial No.',
                validator: {
                    maxLength: 100
                }
            },
            {
                field_id: 'txtSszAssetDesc',
                type: 'text',
                name: 'Asset Description',
                validator: {
                    maxLength: 255
                }
            },
            {
                field_id: 'optSszAssetGroupId',
                type: 'select',
                name: 'Asset Group',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optSszAssetCategoryId',
                type: 'select',
                name: 'Asset Category',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optSszAssetTypeId',
                type: 'select',
                name: 'Asset Type',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optSszAssetBrandId',
                type: 'select',
                name: 'Asset Brand',
                validator: {
                }
            },
            {
                field_id: 'optSszAssetModelId',
                type: 'select',
                name: 'Asset Model',
                validator: {
                }
            },
            {
                field_id: 'txtSszAssetCapacity',
                type: 'text',
                name: 'Capacity',
                validator: {
                    maxLength: 30
                }
            },
            {
                field_id: 'optSszPpmGroupId',
                type: 'select',
                name: 'PPM Group',
                validator: {
                }
            },
            {
                field_id: 'txtSszAssetBlock',
                type: 'text',
                name: 'Asset Block',
                validator: {
                    maxLength: 30
                }
            },
            {
                field_id: 'txtSszAssetLevel',
                type: 'text',
                name: 'Asset Level',
                validator: {
                    maxLength: 30
                }
            }
        ];

        formValidate = new MzValidate('formAsz');
        formValidate.registerFields(vData);

        $('#formSsz').on('keyup change', function () {
            $('#btnSszSubmit, #btnSszUpdate').attr('disabled', !formValidate.validateForm());
        });

        $('#optSszAssetGroupId').on('change', function () {
            mzOptionStop('optSszAssetCategoryId', refAssetCategory, 'Choose Asset Category', 'assetCategoryId', 'assetCategoryName', {assetGroupId: $(this).val(), assetCategoryStatus: '1'}, 'required');
            mzOptionStopClear('optSszAssetTypeId','Choose Asset Type', 'required');
            mzOptionStopClear('optSszAssetBrandId','Choose Asset Brand');
            mzOptionStopClear('optSszAssetModelId','Choose Asset Model');
        });

        $('#optSszAssetCategoryId').on('change', function () {
            mzOptionStop('optSszAssetTypeId', refAssetType, 'Choose Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: $(this).val(), assetTypeStatus: '1'}, 'required');
            mzOptionStopClear('optSszAssetBrandId','Choose Asset Brand');
            mzOptionStopClear('optSszAssetModelId','Choose Asset Model');
        });

        $('#optSszAssetTypeId').on('change', function () {
            const refAssetBrandGroup = mzGetLocalArray('gems_assetBrandGroup', versionLocal, 'assetBrandId', {assetTypeId: $(this).val()});
            mzOptionStop('optSszAssetBrandId', refAssetBrandGroup, 'Choose Asset Brand', 'assetBrandId', 'assetBrandName', {assetBrandStatus: '1'});
            mzOptionStopClear('optSszAssetModelId','Choose Asset Model');
        });

        $('#optSszAssetBrandId').on('change', function () {
            mzOptionStop('optSszAssetModelId', refAssetModel, 'Choose Asset Model', 'assetModelId', 'assetModelName', {assetBrandId: $(this).val(), assetTypeId: $('#optSszAssetTypeId').val(), assetModelStatus: '1'});
        });

        qrCodeImg = new QRCode(document.getElementById("divSszQrCodeImg"), {
            //width : 100,
            //height : 100
        });

        $('#btnSszSave').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    const assetGroupId = $('#optSszAssetGroupId').val();
                    const assetCategoryId = $('#optSszAssetCategoryId').val();
                    const assetTypeId = $('#optSszAssetTypeId').val();
                    const assetBrandId = $('#optSszAssetBrandId').val();
                    const assetModelId = $('#optSszAssetModelId').val();
                    const ppmGroupId = $('#optSszPpmGroupId').val();
                    const data = {
                        action: 'save',
                        assetName: $('#txtSszAssetName').val(),
                        assetNo: $('#txtSszAssetNo').val(),
                        assetSerialNo: $('#txtSszAssetSerialNo').val(),
                        assetDesc: $('#txtSszAssetDesc').val(),
                        assetGroupId: assetGroupId !== null ? assetGroupId : '',
                        assetCategoryId: assetCategoryId !== null ? assetCategoryId : '',
                        assetTypeId: assetTypeId !== null ? assetTypeId : '',
                        assetBrandId: assetBrandId !== null ? assetBrandId : '',
                        assetModelId: assetModelId !== null ? assetModelId : '',
                        locationCodeId: locationCodeId !== null ? locationCodeId : '',
                        ppmGroupId: ppmGroupId !== null ? ppmGroupId : '',
                        assetCapacity: $('#txtSszAssetCapacity').val(),
                        assetBlock: $('#txtSszAssetBlock').val(),
                        assetLevel: $('#txtSszAssetLevel').val()
                    };

                    mzAjaxRequest('asset.php?assetId='+assetId, 'PUT', data);
                    if (classFrom.getClassName() === 'MainAsset') {
                        classFrom.updateTableAsz(data, rowRefresh);
                        $('.sectionAszMain').show();
                    }
                    $('.sectionAssetDetails').hide();
                    $(window).scrollTop(0);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('#btnSszSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const data = {
                            action: 'submit',
                            assetName: $('#txtSszAssetName').val(),
                            assetNo: $('#txtSszAssetNo').val(),
                            assetSerialNo: $('#txtSszAssetSerialNo').val(),
                            assetDesc: $('#txtSszAssetDesc').val(),
                            assetGroupId: $('#optSszAssetGroupId').val(),
                            assetCategoryId: $('#optSszAssetCategoryId').val(),
                            assetTypeId: $('#optSszAssetTypeId').val(),
                            assetBrandId: $('#optSszAssetBrandId').val(),
                            assetModelId: $('#optSszAssetModelId').val(),
                            ppmGroupId: $('#optSszPpmGroupId').val(),
                            assetCapacity: $('#txtSszAssetCapacity').val(),
                            assetBlock: $('#txtSszAssetBlock').val(),
                            assetLevel: $('#txtSszAssetLevel').val()
                        };

                        mzAjaxRequest('asset.php?assetId='+assetId, 'PUT', data);
                        if (classFrom.getClassName() === 'MainAsset') {
                            classFrom.updateTableAsz(data, rowRefresh);
                            $('.sectionAszMain').show();
                        }
                        $('.sectionAssetDetails').hide();
                        $(window).scrollTop(0);
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('#btnSszUpdate').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const data = {
                            action: 'update',
                            assetName: $('#txtSszAssetName').val(),
                            assetSerialNo: $('#txtSszAssetSerialNo').val(),
                            assetDesc: $('#txtSszAssetDesc').val(),
                            assetBrandId: $('#optSszAssetBrandId').val(),
                            assetModelId: $('#optSszAssetModelId').val(),
                            assetCapacity: $('#txtSszAssetCapacity').val(),
                            ppmGroupId: $('#optSszPpmGroupId').val(),
                            assetBlock: $('#txtSszAssetBlock').val(),
                            assetLevel: $('#txtSszAssetLevel').val()
                        };

                        mzAjaxRequest('asset.php?assetId='+assetId, 'PUT', data);
                        if (classFrom.getClassName() === 'MainAsset') {
                            classFrom.updateTableAsz(data, rowRefresh);

                            $('.sectionAssetDetails').hide();
                            $('.sectionAszMain').show();
                            $(window).scrollTop(0);
                        }
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.getDetails = function () {
        const dataSsz = mzAjaxRequest('asset.php?assetId='+assetId, 'GET');
        const registeredBy = dataSsz['assetRegisteredBy']!==''?refUser[dataSsz['assetRegisteredBy']]['userFullName']:'';
        const assetGroupId = dataSsz['assetGroupId'];
        const assetCategoryId = dataSsz['assetCategoryId'];
        const assetTypeId = dataSsz['assetTypeId'];
        const assetBrandId = dataSsz['assetBrandId'];
        const assetModelId = dataSsz['assetModelId'];
        const ppmGroupId = dataSsz['ppmGroupId'];
        const contractId = dataSsz['contractId'];
        const assetStatus = dataSsz['assetStatus'];
        const siteId = refContract[contractId]['siteId'];
        const clientId = refSite[siteId]['clientId'];

        mzOptionStop('optSszAssetGroupId', refAssetGroup, 'Choose Asset Group', 'assetGroupId', 'assetGroupName', {assetGroupStatus: '1'}, 'required');
        mzOptionStop('optSszAssetCategoryId', refAssetCategory, 'Choose Asset Category', 'assetCategoryId', 'assetCategoryName', {assetGroupId: assetGroupId, assetCategoryStatus: '1'}, 'required');
        mzOptionStop('optSszAssetTypeId', refAssetType, 'Choose Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: assetCategoryId, assetTypeStatus: '1'}, 'required');
        const refAssetBrandGroup = mzGetLocalArray('gems_assetBrandGroup', versionLocal, 'assetBrandId', {assetTypeId: assetTypeId});
        mzOptionStop('optSszAssetBrandId', refAssetBrandGroup, 'Choose Asset Brand', 'assetBrandId', 'assetBrandName', {assetBrandStatus: '1'});
        mzOptionStop('optSszAssetModelId', refAssetModel, 'Choose Asset Model', 'assetModelId', 'assetModelName', {assetBrandId: assetBrandId, assetTypeId: assetTypeId, assetModelStatus: '1'});
        mzOptionStop('optSszPpmGroupId', refPpmGroup, 'Choose PPM Group', 'ppmGroupId', 'ppmGroupName', {siteId: siteId, roleId: '5', ppmGroupStatus: '1'});

        mzDisableSelect('optSszAssetGroupId', false);
        mzDisableSelect('optSszAssetCategoryId', false);
        mzDisableSelect('optSszAssetTypeId', false);
        mzDisableSelect('optSszAssetBrandId', false);
        mzDisableSelect('optSszAssetModelId', false);

        formValidate.enableField('optSszAssetGroupId');
        formValidate.enableField('optSszAssetCategoryId');
        formValidate.enableField('optSszAssetTypeId');
        formValidate.enableField('optSszAssetBrandId');
        formValidate.enableField('optSszAssetModelId');

        mzSetFieldValue('SszAssetName', dataSsz['assetName'], 'text');
        mzSetFieldValue('SszAssetNo', dataSsz['assetNo'], 'text');
        mzSetFieldValue('SszAssetSerialNo', dataSsz['assetSerialNo'], 'text');
        mzSetFieldValue('SszAssetDesc', dataSsz['assetDesc'], 'text');
        mzSetFieldValue('SszAssetCapacity', dataSsz['assetCapacity'], 'text');
        mzSetFieldValue('SszAssetGroupId', assetGroupId, 'select', 'Asset Group *');
        mzSetFieldValue('SszAssetCategoryId', assetCategoryId, 'select', 'Asset Category *');
        mzSetFieldValue('SszAssetTypeId', assetTypeId, 'select', 'Asset Type *');
        mzSetFieldValue('SszAssetBrandId', assetBrandId, 'select', 'Asset Brand *');
        mzSetFieldValue('SszAssetModelId', assetModelId, 'select', 'Asset Model *');
        mzSetFieldValue('SszPpmGroupId', ppmGroupId, 'select', 'PPM Group');
        mzSetFieldValue('SszAssetRegisteredBy', registeredBy, 'text');
        mzSetFieldValue('SszAssetTimeRegistered', mzConvertDateDisplay(dataSsz['assetTimeRegistered']), 'text');
        mzSetFieldValue('SszAssetStatus', refStatus[assetStatus]['statusDesc'], 'text');
        mzSetFieldValue('SszAssetBlock', dataSsz['assetBlock'], 'text');
        mzSetFieldValue('SszAssetLevel', dataSsz['assetLevel'], 'text');

        $('#lblSszContactName').html(refContract[contractId]['contractName']);
        $('#lblSszSiteName').html(refSite[siteId]['siteName']);
        $('#lblSszClientName').html(refClient[clientId]['clientName']);

        qrCodeImg.makeCode(dataSsz['assetNo']);
        return assetStatus;
    };

    this.add = function (_contractId, _assetGroupId, _assetCategoryId, _assetTypeId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_contractId]);

                const data = {
                    contractId: _contractId,
                    assetGroupId: _assetGroupId,
                    assetCategoryId: _assetCategoryId,
                    assetTypeId: _assetTypeId
                };
                assetId = mzAjaxRequest('asset.php', 'POST', data);
                const tempRow = {
                    assetId: assetId,
                    assetGroupId: '',
                    assetCategoryId: '',
                    assetTypeId: '',
                    assetStatus: '5',
                    locationCodeId: '',
                    ppmGroupId: ''
                };
                rowRefresh = classFrom.addTableAsz(tempRow);

                formValidate.clearValidation();
                self.getDetails();
                $('#divSszQrCode').hide();
                $('#txtSszAssetName, #txtSszAssetNo, #txtSszSerialNo, #txtSszAssetDesc, #txtSszAssetCapacity, #txtSszAssetBlock, #txtSszAssetLevel').prop('disabled', false);

                $('#btnSszSubmit').prop('disabled', true);
                $('.divSszRegisterInfo, #btnSszUpdate, #btnSszQr, #btnSszPrint').hide();
                $('.sectionAssetDetails, #btnSszSubmit, #btnSszSave').show();

                if (classFrom.getClassName() === 'MainAsset') {
                    $('.sectionAszMain').hide();
                }
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.edit = function (_assetId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetId, _rowRefresh]);
                assetId = _assetId;
                rowRefresh = _rowRefresh;

                formValidate.clearValidation();
                const assetStatus = self.getDetails();
                if (assetStatus === '5') {
                    $('#divSszQrCode, #btnSszUpdate, #btnSszQr, #btnSszPrint, .divSszRegisterInfo').hide();
                    $('#btnSszSave, #btnSszSubmit').show();
                    $('#btnSszSubmit').prop('disabled', !formValidate.validateForm());
                } else {
                    $('#divSszQrCode, #btnSszUpdate, #btnSszQr, #btnSszPrint, .divSszRegisterInfo').show();
                    $('#btnSszSave, #btnSszSubmit').hide();

                    mzDisableSelect('optSszAssetGroupId', true);
                    mzDisableSelect('optSszAssetCategoryId', true);
                    mzDisableSelect('optSszAssetTypeId', true);

                    formValidate.disableField('optSszAssetGroupId');
                    formValidate.disableField('optSszAssetCategoryId');
                    formValidate.disableField('optSszAssetTypeId');
                }
                $('#txtSszAssetName, #txtSszAssetNo, #txtSszSerialNo, #txtSszAssetDesc, #txtSszAssetCapacity, #txtSszAssetBlock, #txtSszAssetLevel').prop('disabled', false);

                $('#btnSszUpdate').prop('disabled', true);
                $('.sectionAssetDetails').show();

                if (classFrom.getClassName() === 'MainAsset') {
                    $('.sectionAszMain').hide();
                }
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.view = function (_assetId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetId]);
                assetId = _assetId;

                formValidate.clearValidation();
                self.getDetails();
                $('#divSszQrCode').show();
                $('#txtSszAssetName, #txtSszAssetNo, #txtSszSerialNo, #txtSszAssetSerialNo, #txtSszAssetDesc, #txtSszAssetCapacity, #txtSszAssetBlock, #txtSszAssetLevel').prop('disabled', true);

                mzDisableSelect('optSszAssetGroupId', true);
                mzDisableSelect('optSszAssetCategoryId', true);
                mzDisableSelect('optSszAssetTypeId', true);
                mzDisableSelect('optSszAssetBrandId', true);
                mzDisableSelect('optSszAssetModelId', true);

                $('#btnSszSubmit, #btnSszSave, #btnSszUpdate').hide();
                $('.sectionAssetDetails, .divSszRegisterInfo, #btnSszQr, #btnSszPrint').show();

                if (classFrom.getClassName() === 'MainAsset') {
                    $('.sectionAszMain').hide();
                } else if (classFrom.getClassName() === 'MainPpmManagement') {
                    $('.sectionPmgMain').hide();
                }
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_assetId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetId, _rowRefresh]);
                mzAjaxRequest('asset.php?assetId='+_assetId, 'PUT', {action: 'deactivate'});
                const tempRow = {assetStatus:'2'};
                if (classFrom.getClassName() === 'MainAsset') {
                    classFrom.updateTableAsz(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_assetId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetId, _rowRefresh]);
                mzAjaxRequest('asset.php?assetId='+_assetId, 'PUT', {action: 'activate'});
                const tempRow = {assetStatus:'1'};
                if (classFrom.getClassName() === 'MainAsset') {
                    classFrom.updateTableAsz(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_assetId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetId]);
                mzAjaxRequest('asset.php?assetId='+_assetId, 'DELETE');
                if (classFrom.getClassName() === 'MainAsset') {
                    classFrom.deleteTableAsz();
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

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
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

    this.setRefAssetModel = function (_refAssetModel) {
        refAssetModel = _refAssetModel;
    };

    this.setRefContract = function (_refContract) {
        refContract = _refContract;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };

    this.setVersionLocal = function (_versionLocal) {
        versionLocal = _versionLocal;
    };
}