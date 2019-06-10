function SectionChecklist() {

    const className = 'SectionAssetDetails';
    let self = this;
    let checklistId = '';
    let rowRefresh = '';
    let classFrom;
    let refStatus;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refUser;
    let formValidate;

    this.init = function () {
        if (classFrom.getClassName() === 'MainChecklist') {
            $('.sectionChecklist').hide();
        }

        $('#btnSckBack').on('click', function () {
            $('.sectionChecklist').hide();
            $('.sectionPcmMain').show();
            $(window).scrollTop(0);
        });

        const vData = [
            {
                field_id: 'txtSckChecklistName',
                type: 'text',
                name: 'Checklist Name',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'txtSckChecklistVersion',
                type: 'text',
                name: 'Checklist Version',
                validator: {
                    notEmpty: true,
                    maxLength: 30
                }
            },
            {
                field_id: 'txaSckChecklistDesc',
                type: 'text',
                name: 'Description',
                validator: {
                    maxLength: 255
                }
            },
            {
                field_id: 'txaSckChecklistGuideline',
                type: 'summernote',
                name: 'General Guidelines',
                validator: {
                    notEmptySummernote: true
                }
            },
            {
                field_id: 'txtSckAssetGroupName',
                type: 'text',
                name: 'Asset Group',
                validator: {}
            },
            {
                field_id: 'txtSckAssetCategoryName',
                type: 'text',
                name: 'Asset Category',
                validator: {}
            },
            {
                field_id: 'txtSckAssetTypeName',
                type: 'text',
                name: 'Asset Type',
                validator: {}
            }
        ];

        formValidate = new MzValidate('formSck');
        formValidate.registerFields(vData);

        $('#formSck').on('keyup change', function () {
            $('#btnSckSubmit, #btnSckUpdate').attr('disabled', !formValidate.validateForm());
        });

        $('#txaSckChecklistGuideline').summernote({
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']]
            ],
            height: 240,
            disableDragAndDrop: true,
            callbacks: {
                onKeyup: function(e) {
                    formValidate.validateSummernote();
                }
            }
        });

        $('#btnSckSave').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    formValidate.clearValidation();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('#btnSckSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        console.log($('#txaSckChecklistGuideline').summernote('code'));
                        console.log($('#txaSckChecklistGuideline').summernote('isEmpty'));
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.getDetails = function () {
        const dataSck = mzAjaxRequest('checklist.php?checklistId='+checklistId, 'GET');
        const registeredBy = dataSck['checklistRegisteredBy']!==''?refUser[dataSck['checklistRegisteredBy']]['userFullName']:'';
        const assetTypeId = dataSck['assetTypeId'];
        const assetCategoryId = refAssetType[assetTypeId]['assetCategoryId'];
        const assetGroupId = refAssetCategory[assetCategoryId]['assetGroupId'];
        const checklistStatus = dataSck['checklistStatus'];

        mzSetFieldValue('SckChecklistName', dataSck['checklistName'], 'text');
        mzSetFieldValue('SckChecklistVersion', dataSck['checklistVersion'], 'text');
        mzSetFieldValue('SckChecklistDesc', dataSck['checklistDesc'], 'text');
        mzSetFieldValue('SckChecklistGuideline', dataSck['checklistGuideline'], 'text');
        mzSetFieldValue('SckAssetTypeName', refAssetType[assetTypeId]['assetTypeName'], 'text');
        mzSetFieldValue('SckAssetCategoryName', refAssetCategory[assetCategoryId]['assetCategoryName'], 'text');
        mzSetFieldValue('SckAssetGroupName', refAssetGroup[assetGroupId]['assetGroupName'], 'text');
        mzSetFieldValue('SckChecklistRegisteredBy', registeredBy, 'text');
        mzSetFieldValue('SckChecklistTimeRegistered', mzConvertDateDisplay(dataSck['checklistTimeRegistered']), 'text');
        mzSetFieldValue('SckChecklistStatus', refStatus[checklistStatus]['statusDesc'], 'text');

        return checklistStatus;
    };

    this.add = function (_assetTypeId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetTypeId]);

                checklistId = mzAjaxRequest('checklist.php', 'POST', {assetTypeId: _assetTypeId});
                const tempRow = {
                    checklistId: checklistId,
                    checklistName: '',
                    checklistVersion: '',
                    checklistTimeRegistered: '',
                    assetTypeId: _assetTypeId,
                    checklistStatus: '5'
                };
                rowRefresh = classFrom.addTablePcmChecklist(tempRow);

                formValidate.clearValidation();
                self.getDetails();
                $('#txtSckChecklistName, #txtSckChecklistVersion, #txaSckChecklistDesc').prop('disabled', false);
                $('#txaSckChecklistGuideline').summernote('enable');

                $('#btnSckSubmit').prop('disabled', true);
                $('.sectionPcmMain, #btnSckUpdate').hide();
                $('.sectionChecklist, #btnSckSubmit, #btnSckSave').show();
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

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };
}