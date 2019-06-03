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

        let formValidate = new MzValidate('formAsz');
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

    this.add = function (_contractId, _assetGroupId, _assetCategoryId, _assetTypeId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_contractId]);

                $('.sectionAszMain').hide();
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

                const dataSsz = mzAjaxRequest('asset.php?assetId='+assetId, 'GET');
                console.log(dataSsz);
                mzOption('optSszAssetGroupId', refAssetGroup, 'Choose Asset Group', 'assetGroupId', 'assetGroupName', {assetGroupStatus: '1'}, 'required');
                mzOption('optSszAssetCategoryId', refAssetCategory, 'Choose Asset Category', 'assetCategoryId', 'assetCategoryName', {assetGroupId: dataSsz['assetGroupId'], assetCategoryStatus: '1'}, 'required');
                mzOption('optSszAssetTypeId', refAssetType, 'Choose Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: dataSsz['assetCategoryId'], assetTypeStatus: '1'}, 'required');
                mzOption('optSszAssetBrandId', refAssetBrandGroup, 'Choose Asset Brand', 'assetBrandId', 'assetBrandName', {assetTypeId: dataSsz['assetTypeId'], assetBrandStatus: '1'}, 'required');
                mzOption('optSszAssetModelId', refAssetModel, 'Choose Asset Model', 'assetModelId', 'assetModelName', {assetBrandId: dataSsz['assetBrandId'], assetTypeId: dataSsz['assetTypeId'], assetModelStatus: '1'}, 'required');

                mzDisableSelect('optSszAssetGroupId', false);
                mzDisableSelect('optSszAssetCategoryId', false);
                mzDisableSelect('optSszAssetTypeId', false);
                mzDisableSelect('optSszAssetBrandId', false);
                mzDisableSelect('optSszAssetModelId', false);

                mzSetFieldValue('SszAssetGroupId', dataSsz['assetGroupId'], 'select', 'Asset Group *');
                mzSetFieldValue('SszAssetCategoryId', dataSsz['assetCategoryId'], 'select', 'Asset Category *');
                mzSetFieldValue('SszAssetTypeId', dataSsz['assetTypeId'], 'select', 'Asset Type *');
                mzSetFieldValue('SszAssetBrandId', dataSsz['assetBrandId'], 'select', 'Asset Brand *');
                mzSetFieldValue('SszAssetModelId', dataSsz['assetModelId'], 'select', 'Asset Model *');

                $('.sectionAszMain').hide();
                $('.sectionAssetDetails').show();
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
}