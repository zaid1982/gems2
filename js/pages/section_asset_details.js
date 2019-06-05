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
    let refAssetBrandGroup;
    let refAssetModel;
    let refUser;
    let refContract;
    let refSite;
    let refClient;
    let formValidate;

    this.init = function () {
        $('.sectionAssetDetails').hide();

        $('#btnSszBack').on('click', function () {
            $('.sectionAssetDetails').hide();
            $('.sectionAszMain').show();
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
                    notEmpty: true
                }
            },
            {
                field_id: 'optSszAssetModelId',
                type: 'select',
                name: 'Asset Model',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtSszAssetLocationCode',
                type: 'text',
                name: 'Location Code',
                validator: {
                    maxLength: 30
                }
            },
            {
                field_id: 'txtSszAssetCapacity',
                type: 'text',
                name: 'Capacity',
                validator: {
                    maxLength: 30
                }
            }
        ];

        formValidate = new MzValidate('formAsz');
        formValidate.registerFields(vData);

        $('#formSsz').on('keyup change', function () {
            $('#btnSszUpdate, #btnSszSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#optSszAssetGroupId').on('change', function () {
            mzOption('optSszAssetCategoryId', refAssetCategory, 'All Asset Category', 'assetCategoryId', 'assetCategoryName', {assetGroupId: $(this).val(), assetCategoryStatus: '1'}, 'required');
            mzOption('optSszAssetTypeId', refAssetType, 'All Asset Type', 'assetTypeId', 'assetTypeName', {assetTypeId: '0'}, 'required');
            mzOption('optSszAssetBrandId', refAssetBrandGroup, 'All Asset Brand', 'assetBrandId', 'assetBrandName', {assetBrandId: '0'}, 'required');
            mzOption('optSszAssetModelId', refAssetModel, 'All Asset Model', 'assetModelId', 'assetModelName', {assetModelId: '0'}, 'required');
        });

        $('#optSszAssetCategoryId').on('change', function () {
            mzOption('optSszAssetTypeId', refAssetType, 'All Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: $(this).val(), assetTypeStatus: '1'}, 'required');
            mzOption('optSszAssetBrandId', refAssetBrandGroup, 'All Asset Brand', 'assetBrandId', 'assetBrandName', {assetBrandId: '0'}, 'required');
            mzOption('optSszAssetModelId', refAssetModel, 'All Asset Model', 'assetModelId', 'assetModelName', {assetModelId: '0'}, 'required');
        });

        $('#optSszAssetTypeId').on('change', function () {
            mzOption('optSszAssetBrandId', refAssetBrandGroup, 'All Asset Brand', 'assetBrandId', 'assetBrandName', {assetTypeId: $(this).val(), assetBrandStatus: '1'}, 'required');
            mzOption('optSszAssetModelId', refAssetModel, 'All Asset Model', 'assetModelId', 'assetModelName', {assetModelId: '0'}, 'required');
        });

        $('#optSszAssetBrandId').on('change', function () {
            mzOption('optSszAssetModelId', refAssetModel, 'All Asset Model', 'assetModelId', 'assetModelName', {assetBrandId: $(this).val(), assetTypeId: $('#optSszAssetTypeId').val(), assetModelStatus: '1'}, 'required');
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
        const contractId = dataSsz['contractId'];
        const assetStatus = dataSsz['assetStatus'];

        mzOption('optSszAssetGroupId', refAssetGroup, 'Choose Asset Group', 'assetGroupId', 'assetGroupName', {assetGroupStatus: '1'}, 'required');
        mzOption('optSszAssetCategoryId', refAssetCategory, 'Choose Asset Category', 'assetCategoryId', 'assetCategoryName', {assetGroupId: assetGroupId, assetCategoryStatus: '1'}, 'required');
        mzOption('optSszAssetTypeId', refAssetType, 'Choose Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: assetCategoryId, assetTypeStatus: '1'}, 'required');
        mzOption('optSszAssetBrandId', refAssetBrandGroup, 'Choose Asset Brand', 'assetBrandId', 'assetBrandName', {assetTypeId: assetTypeId, assetBrandStatus: '1'}, 'required');
        mzOption('optSszAssetModelId', refAssetModel, 'Choose Asset Model', 'assetModelId', 'assetModelName', {assetBrandId: assetBrandId, assetTypeId: assetTypeId, assetModelStatus: '1'}, 'required');

        mzDisableSelect('optSszAssetGroupId', false);
        mzDisableSelect('optSszAssetCategoryId', false);
        mzDisableSelect('optSszAssetTypeId', false);
        mzDisableSelect('optSszAssetBrandId', false);
        mzDisableSelect('optSszAssetModelId', false);

        mzSetFieldValue('SszAssetName', dataSsz['assetName'], 'text');
        mzSetFieldValue('SszAssetNo', dataSsz['assetNo'], 'text');
        mzSetFieldValue('SszAssetSerialNo', dataSsz['assetSerialNo'], 'text');
        mzSetFieldValue('SszAssetDesc', dataSsz['assetDesc'], 'text');
        mzSetFieldValue('SszAssetLocationCode', dataSsz['assetLocationCode'], 'text');
        mzSetFieldValue('SszAssetCapacity', dataSsz['assetCapacity'], 'text');
        mzSetFieldValue('SszAssetGroupId', assetGroupId, 'select', 'Asset Group *');
        mzSetFieldValue('SszAssetCategoryId', assetCategoryId, 'select', 'Asset Category *');
        mzSetFieldValue('SszAssetTypeId', assetTypeId, 'select', 'Asset Type *');
        mzSetFieldValue('SszAssetBrandId', assetBrandId, 'select', 'Asset Brand *');
        mzSetFieldValue('SszAssetModelId', assetModelId, 'select', 'Asset Model *');
        mzSetFieldValue('SszAssetRegisteredBy', registeredBy, 'text');
        mzSetFieldValue('SszAssetTimeRegistered', mzConvertDateDisplay(dataSsz['assetTimeRegistered']), 'text');
        mzSetFieldValue('SszAssetStatus', refStatus[assetStatus]['statusDesc'], 'text');

        const siteId = refContract[contractId]['siteId'];
        const clientId = refSite[siteId]['clientId'];
        $('#lblSszContactName').html(refContract[contractId]['contractName']);
        $('#lblSszSiteName').html(refSite[siteId]['siteName']);
        $('#lblSszClientName').html(refClient[clientId]['clientName']);
        return assetStatus;
    };

    this.add = function (_contractId, _assetGroupId, _assetCategoryId, _assetTypeId) {
        ShowLoader();
        setTimeout(function () {    alert(_assetGroupId);
            try {
                mzCheckFuncParam([_contractId]);
                assetId = '2';
                rowRefresh = '';

                formValidate.clearValidation();
                self.getDetails();
                $('#divSszQrCode').hide();
                $('#txtSszAssetNo').prop('disabled', false);
                $('#txtSszAssetName, #txtSszAssetCode, #txtSszSerialNo, #txtSszAssetDesc, #txtSszAssetLocationCode, #txtSszAssetCapacity').prop('disabled', false);
                formValidate.enableField('txtSszAssetCode');

                $('#btnSszSubmit').prop('disabled', false);
                $('.sectionAszMain, #divSszRegisterInfo, #btnSszUpdate, #btnSszQr, #btnSszPrint').hide();
                $('.sectionAssetDetails, #btnSszSubmit, #btnSszSave').show();
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
                    $('#divSszQrCode, #btnSszUpdate, #btnSszQr, #btnSszPrint').hide();
                    $('#btnSszSave, #btnSszSubmit').show();
                    $('#txtSszAssetNo').prop('disabled', false);
                    formValidate.enableField('txtSszAssetNo');
                } else {
                    $('#divSszQrCode, #btnSszUpdate, #btnSszQr, #btnSszPrint').show();
                    $('#btnSszSave, #btnSszSubmit').hide();
                    $('#txtSszAssetNo').prop('disabled', true);
                    formValidate.disableField('txtSszAssetNo');
                }
                $('#txtSszAssetName, #txtSszAssetCode, #txtSszSerialNo, #txtSszAssetDesc, #txtSszAssetLocationCode, #txtSszAssetCapacity').prop('disabled', false);

                $('#btnSszUpdate').prop('disabled', false);
                $('.sectionAszMain').hide();
                $('.sectionAssetDetails, #divSszRegisterInfo').show();
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.view = function (_assetId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetId, _rowRefresh]);
                assetId = _assetId;
                rowRefresh = _rowRefresh;

                formValidate.clearValidation();
                self.getDetails();
                $('#divSszQrCode').show();
                $('#txtSszAssetName, #txtSszAssetCode, #txtSszSerialNo, #txtSszAssetSerialNo, #txtSszAssetDesc, #txtSszAssetLocationCode, #txtSszAssetCapacity').prop('disabled', true);

                mzDisableSelect('optSszAssetGroupId', true);
                mzDisableSelect('optSszAssetCategoryId', true);
                mzDisableSelect('optSszAssetTypeId', true);
                mzDisableSelect('optSszAssetBrandId', true);
                mzDisableSelect('optSszAssetModelId', true);

                $('.sectionAszMain, #btnSszSubmit, #btnSszSave, #btnSszUpdate').hide();
                $('.sectionAssetDetails, #divSszRegisterInfo, #btnSszQr, #btnSszPrint').show();
                $(window).scrollTop(0);
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

    this.setRefAssetBrandGroup = function (_refAssetBrandGroup) {
        refAssetBrandGroup = _refAssetBrandGroup;
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
}