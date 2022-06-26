function ModalFcaAdd () {

    const className = 'ModalFcaAdd';
    let self = this;
    let formValidate;
    let vData;
    let classFrom;
    let actionType;
    let fcaTaskId;
    let refSite;
    let refAssetGroup;
    let refDefectCategory;
    let refDefectCategorySite;
    let refFcaZone;
    let refConditionScale;
    let refEvaluationType;
    let imageSize = [[0,0],[0,0]];
    let dataDb;

    this.init = function () {
        mzOptionV2('optMfaSite', refSite, 'Select Site *', 'siteName', {siteStatus: 1}, 'required');
        mzOptionV2('optMfaAssetGroup', refAssetGroup, 'Select Asset Group *', 'assetGroupName', {assetGroupStatus: 1}, 'required');

        vData = [
            {
                field_id: 'optMfaSite',
                type: 'select',
                name: 'Site',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMfaZone',
                type: 'select',
                name: 'Zone',
                validator: {
                    notEmpty: false
                }
            },
            {
                field_id: 'txtMfaArea',
                type: 'text',
                name: 'Area',
                validator: {
                    notEmpty: true,
                    maxLength: 30
                }
            },
            {
                field_id: 'optMfaAssetGroup',
                type: 'select',
                name: 'Asset Group',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMfaEvaluated',
                type: 'text',
                name: 'Evaluated Asset',
                validator: {
                    notEmpty: false,
                    maxLength: 100
                }
            },
            {
                field_id: 'txtMfaDefectItem',
                type: 'text',
                name: 'Defect Item',
                validator: {
                    notEmpty: true,
                    maxLength: 100
                }
            },
            {
                field_id: 'optMfaDefectCategory',
                type: 'select',
                name: 'Defect Category',
                validator: {
                    notEmpty: false
                }
            },
            {
                field_id: 'txaMfaObservation',
                type: 'text',
                name: 'Observation',
                validator: {
                    notEmpty: true,
                    maxLength: 2000
                }
            },
            {
                field_id: 'txfMfaImage1',
                type: 'file',
                name: 'Image 1',
                validator: {
                    notEmptyFile: true,
                    imageType: true,
                    fileSize : 6
                }
            },
            {
                field_id: 'txfMfaImage2',
                type: 'file',
                name: 'Image 2',
                validator: {
                    notEmptyFile: false,
                    imageType: true,
                    fileSize : 6
                }
            }
        ];

        formValidate = new MzValidate('formMfa');
        formValidate.registerFields(vData);

        $('#optMfaSite').on('change', function () {
            const siteId = parseInt($(this).val());
            ShowLoader();
            setTimeout(function () {
                self.reloadSiteOptions(siteId);
                HideLoader();
            }, 100);
        });

        $('#txfMfaImage1').on('change', function () {
            try {
                self.saveImageSize(this.files[0], 0);
                $('#imgMfaImage1').attr('src', 'img/background/upload_placeholder.png');
                mzDisplayImageFileInput(this, 'imgMfaImage1');
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
        });

        $('#txfMfaImage2').on('change', function () {
            try {
                self.saveImageSize(this.files[0], 1);
                $('#imgMfaImage2').attr('src', 'img/background/upload_placeholder.png');
                mzDisplayImageFileInput(this, 'imgMfaImage2');
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
        });

        document.getElementById('txfMfaImage1').addEventListener('change', mzHandleFileSelect, false);
        document.getElementById('txfMfaImage2').addEventListener('change', mzHandleFileSelect, false);

        $('#btnMfaSubmit').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const image1 = $('#txfMfaImage1').prop('files')[0];
                        const image2 = $('#txfMfaImage2').prop('files')[0];
                        const image1Data = typeof image1 === 'undefined' ? {} : {
                            name: 'FCA Audit Image 1',
                            filename: image1.name,
                            size: image1.size,
                            width: imageSize[0][0],
                            height: imageSize[0][1],
                            type: image1.type,
                            data: $('#txfMfaImage1Blob').val(),
                            description: 'FCA Audit Image 1'
                        };
                        const image2Data = typeof image2 === 'undefined' ? {} : {
                            name: 'FCA Audit Image 2',
                            filename: image2.name,
                            size: image2.size,
                            width: imageSize[1][0],
                            height: imageSize[1][1],
                            type: image2.type,
                            data: $('#txfMfaImage2Blob').val(),
                            description: 'FCA Audit Image 2'
                        };
                        const data = {
                            siteId: mzParseInt($('#optMfaSite').val()),
                            fcaZoneId: mzParseInt($('#optMfaZone').val()),
                            fcaTaskArea: $('#txtMfaArea').val(),
                            assetGroupId: mzParseInt($('#optMfaAssetGroup').val()),
                            fcaTaskAssetEvaluated: $('#txtMfaEvaluated').val(),
                            fcaTaskDefectItem: $('#txtMfaDefectItem').val(),
                            fcaDefectCategoryId: mzParseInt($('#optMfaDefectCategory').val()),
                            fcaTaskObservation: $('#txaMfaObservation').val(),
                            image1: image1Data,
                            image2: image2Data
                        };
                        if (actionType === 'add') {
                            mzAjaxRequest2('fca_task', 'POST', data);
                            classFrom.genTable();
                        } else if (actionType === 'correct' && fcaTaskId > 0) {
                            mzAjaxRequest2('fca_task/resubmit/'+fcaTaskId, 'POST', data);
                            classFrom.displayInfo(true, false, true);
                            classFrom.setIsUpdated(true);
                        }
                        $('#modal_fca_add').modal('hide');
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
                formValidate.clearValidation();
                actionType = 'add';
                fcaTaskId = 0;
                mzOptionStopClear('optMfaZone', 'Select Defect Category');
                mzOptionStopClear('optMfaDefectCategory', 'Select Defect Category');
                $('#imgMfaImage1, #imgMfaImage2').attr('src', 'img/background/upload_placeholder.png');
                imageSize = [[0,0],[0,0]];
                vData[8]['validator']['notEmptyFile'] = true;
                formValidate.registerFields(vData);
                $('#divMfaValidationInfo').hide();
                $('#spanMfaTitle').text('Create New FCA Audit');
                $('#btnMfaSubmit').html('<i class="far fa-paper-plane mr-1"></i> Submit');
                $('#modal_fca_add').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.correct = function (_fcaTaskId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_fcaTaskId]);
                fcaTaskId = _fcaTaskId;
                formValidate.clearValidation();
                actionType = 'correct';
                $('#imgMfaImage1, #imgMfaImage2').attr('src', 'img/background/upload_placeholder.png');
                imageSize = [[0,0],[0,0]];
                self.getFieldsValue();
                $('#divMfaValidationInfo').show();
                mzSetFieldValue('MfaConditionScale', refConditionScale[dataDb['fcaTaskConditionScale']], 'text');
                mzSetFieldValue('MfaEvaluationType', refEvaluationType[dataDb['fcaTaskEvaluationType']], 'text');
                mzSetFieldValue('MfaValidation', dataDb['fcaTaskValidation'], 'textarea');
                $('#spanMfaTitle').text('Resubmit FCA Audit Correction');
                $('#btnMfaSubmit').html('<i class="far fa-paper-plane mr-1"></i> Resubmit');
                $('#modal_fca_add').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.reloadSiteOptions = function (siteId) {
        try {
            mzOptionV2('optMfaZone', refFcaZone, 'Select Zone', 'fcaZoneName', {siteId: siteId, fcaZoneStatus: 1});
            let filterArr = {fcaDefectCategoryStatus: 1};
            if (typeof refDefectCategorySite[siteId] !== 'undefined') {
                filterArr['id'] = '('+refDefectCategorySite[siteId]+')';
            }
            mzOptionV2('optMfaDefectCategory', refDefectCategory, 'Select Defect Category', 'fcaDefectCategoryName', filterArr);
        } catch (e) {
            toastr['error'](e.message, _ALERT_TITLE_ERROR);
        }
    };

    this.getFieldsValue = function () {
        try {
            dataDb = mzAjaxRequest2('fca_task/'+fcaTaskId, 'GET');
            self.reloadSiteOptions(dataDb['siteId']);
            mzSetFieldValue('MfaSite', dataDb['siteId'], 'select');
            mzSetFieldValue('MfaZone', dataDb['fcaZoneId'], 'select');
            mzSetFieldValue('MfaArea', dataDb['fcaTaskArea'], 'text');
            mzSetFieldValue('MfaAssetGroup', dataDb['assetGroupId'], 'select');
            mzSetFieldValue('MfaEvaluated', dataDb['fcaTaskAssetEvaluated'], 'text');
            mzSetFieldValue('MfaDefectItem', dataDb['fcaTaskDefectItem'], 'text');
            mzSetFieldValue('MfaDefectCategory', dataDb['fcaDefectCategoryId'], 'select');
            mzSetFieldValue('MfaObservation', dataDb['fcaTaskObservation'], 'textarea');
            $('#imgMfaImage1').attr('src', dataDb['fcaTaskImage1'] !== null ? mzAjaxRequest2('document/upload_link/'+dataDb['fcaTaskImage1'], 'GET') : 'img/background/upload_placeholder.png');
            $('#imgMfaImage2').attr('src', dataDb['fcaTaskImage2'] !== null ? mzAjaxRequest2('document/upload_link/'+dataDb['fcaTaskImage2'], 'GET') : 'img/background/upload_placeholder.png');
            vData[8]['validator']['notEmptyFile'] = dataDb['fcaTaskImage1'] === null;
            formValidate.registerFields(vData);
        } catch (e) {
            throw new Error(_ALERT_MSG_ERROR_DEFAULT);
        }
    };

    this.saveImageSize = function (files, imageId) {
        let reader = new FileReader();
        let img = new Image();
        reader.onload = function (e) {
            img.src = e.target.result;
            img.onload = function () {
                imageSize[imageId] = [this.width, this.height];
            };
        };
        reader.readAsDataURL(files);
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefDefectCategory = function (_refDefectCategory) {
        refDefectCategory = _refDefectCategory;
    };

    this.setRefDefectCategorySite = function (_refDefectCategorySite) {
        refDefectCategorySite = _refDefectCategorySite;
    };

    this.setRefFcaZone = function (_refFcaZone) {
        refFcaZone = _refFcaZone;
    };

    this.setRefConditionScale = function (_refConditionScale) {
        refConditionScale = _refConditionScale;
    };

    this.setRefEvaluationType = function (_refEvaluationType) {
        refEvaluationType = _refEvaluationType;
    };
}