function SectionAssetDetails() {

    const className = 'SectionAssetDetails';
    let self = this;
    let assetId = '';
    let rowRefresh = '';
    let classFrom;
    let refStatus;
    let refContract;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refAssetBrand;
    let refAssetBrandGroup;
    let refAssetModel;
    let refUser;
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
            }
        ];

        formValidate = new MzValidate('formAsz');
        formValidate.registerFields(vData);

        $('#formSsz').on('keyup change', function () {
            $('#butSszSubmit').attr('disabled', !formValidate.validateForm());
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
        mzSetFieldValue('SszAssetCode', dataSsz['assetCode'], 'text');
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

        if (assetStatus === '5') {
            $('#divSszQrCode').hide();
            $('#txtSszAssetSerialNo').prop('disabled', false);
            // enable txtSszAssetSerialNo
        } else {
            $('#divSszQrCode').show();
            $('#txtSszAssetSerialNo').prop('disabled', true);
            // disable txtSszAssetSerialNo
        }
    };

    this.add = function (_contractId, _assetGroupId, _assetCategoryId, _assetTypeId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_contractId]);
                assetId = '2';
                rowRefresh = '';

                self.getDetails();
                $('#txtSszAssetName, #txtSszAssetCode, #txtSszSerialNo, #txtSszAssetDesc, #txtSszAssetLocationCode, #txtSszAssetCapacity').prop('disabled', false);
                // enable all

                $('.sectionAszMain, #divSszRegisterInfo').hide();
                $('.sectionAssetDetails').show();
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

                self.getDetails();
                $('#txtSszAssetName, #txtSszAssetCode, #txtSszSerialNo, #txtSszAssetDesc, #txtSszAssetLocationCode, #txtSszAssetCapacity').prop('disabled', false);
                // enable all above

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

                self.getDetails();
                $('#txtSszAssetName, #txtSszAssetCode, #txtSszSerialNo, #txtSszAssetSerialNo, #txtSszAssetDesc, #txtSszAssetLocationCode, #txtSszAssetCapacity').prop('disabled', true);
                // disable all

                $('.sectionAszMain').hide();
                $('.sectionAssetDetails, #divSszRegisterInfo').show();
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

    this.setRefContract = function (_refContract) {
        refContract = _refContract;
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

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };
}