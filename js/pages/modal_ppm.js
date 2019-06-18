function ModalPpm() {

    const className = 'ModalPpm';
    let self = this;
    let classFrom;
    let rowRefresh = '';
    let assetId = '';
    let refContract;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refAssetBrand;
    let refAssetModel;
    let formValidate;

    this.init = function () {
        $('#optMpmChecklistId').on('change', function () {
            console.log($(this).val());
        });

        const vData = [
            {
                field_id: 'optMpmChecklistId',
                type: 'select',
                name: 'PPM Checklist',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMpmPpmDateCycle',
                type: 'text',
                name: 'Start Cycle Date',
                validator: {
                    notEmpty: true,
                    maxLength: 30
                }
            }
        ];

        formValidate = new MzValidate('formMpm');
        formValidate.registerFields(vData);

        $('#formMpm').on('keyup change', function () {
            $('#btnMpmSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_ppm').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMpmSubmit').attr('disabled', true);
        });

        $('#btnMpmSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const checklistId = $('#optMpmChecklistId').val();
                        const ppmDateCycle = mzConvertDate($('#txtMpmPpmDateCycle').val());
                        let data = {
                            action: 'assign_ppm_single',
                            assetId: assetId,
                            checklistId: checklistId,
                            ppmDateCycle: ppmDateCycle
                        };

                        const ppmReturn = mzAjaxRequest('ppm.php', 'POST', data);
                        if (classFrom.getClassName() === 'MainPpmManagement') {
                            data['ppmId'] = ppmReturn['ppmId'];
                            data['ppmTaskNo'] = ppmReturn['ppmTaskNo'];
                            data['ppmStatus'] = '10';
                            data['assignedStatus'] = '10';
                            classFrom.updateTablePmg(data, rowRefresh);
                        }
                        $('#modal_ppm').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.setSingle = function (_assetId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetId, _rowRefresh]);
                assetId = _assetId;
                rowRefresh = _rowRefresh;

                const rowData = classFrom.getTablePmgRow(rowRefresh);
                const contractId = rowData['contractId'];
                const assetGroupId = rowData['assetGroupId'];
                const assetCategoryId = rowData['assetCategoryId'];
                const assetTypeId = rowData['assetTypeId'];
                const assetBrandId = rowData['assetBrandId'];
                const assetModelId = rowData['assetModelId'];
                const contractDateStart = refContract[contractId]['contractDateStart'];
                const contractDateEnd = refContract[contractId]['contractDateEnd'];

                mzSetFieldValue('MpmContractName', refContract[contractId]['contractName'], 'text');
                mzSetFieldValue('MpmContractDateStart', mzConvertDateDisplay(contractDateStart), 'text');
                mzSetFieldValue('MpmContractDateEnd', mzConvertDateDisplay(contractDateEnd), 'text');
                mzSetFieldValue('MpmAssetNo', rowData['assetNo'], 'text');
                mzSetFieldValue('MpmAssetName', rowData['assetName'], 'text');
                mzSetFieldValue('MpmAssetGroupName', refAssetGroup[assetGroupId]['assetGroupName'], 'text');
                mzSetFieldValue('MpmAssetCategoryName', refAssetCategory[assetCategoryId]['assetCategoryName'], 'text');
                mzSetFieldValue('MpmAssetTypeName', refAssetType[assetTypeId]['assetTypeName'], 'text');
                mzSetFieldValue('MpmAssetBrandName', refAssetBrand[assetBrandId]['assetBrandName'], 'text');
                mzSetFieldValue('MpmAssetModelName', refAssetModel[assetModelId]['assetModelName'], 'text');
                mzSetFieldValue('MpmAssetCapacity', rowData['assetCapacity'], 'text');
                mzDateSetMin('txtMpmPpmDateCycle', contractDateStart);
                mzDateSetMax('txtMpmPpmDateCycle', contractDateEnd);

                const refChecklist = mzAjaxRequest('checklist.php?assetTypeId='+assetTypeId, 'GET');
                mzOptionStop('optMpmChecklistId', refChecklist, 'Choose PPM Checklist', 'checklistId', 'checklistName', {checklistStatus: '1'}, 'required', true);

                $('#lblMpmTitle').html('<i class="fas fa-pen-alt text-white"></i> &nbsp;Assign PPM');
                $('#modal_ppm').modal({backdrop: 'static', keyboard: false});
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

    this.setRefAssetModel = function (_refAssetModel) {
        refAssetModel = _refAssetModel;
    };

}