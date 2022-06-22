function ModalFcaAdd () {

    const className = 'ModalFcaAdd';
    let self = this;
    let formValidate;
    let classFrom;
    let refSite;
    let refAssetGroup;
    let refDefectCategory;
    let refDefectCategorySite;
    let refFcaZone;
    let imageSize = [[0,0],[0,0]];

    this.init = function () {
        mzOptionV2('optMfaSite', refSite, 'Select Site *', 'siteName', {siteStatus: 1}, 'required');
        mzOptionV2('optMfaAssetGroup', refAssetGroup, 'Select Asset Group *', 'assetGroupName', {assetGroupStatus: 1}, 'required');

        const vData = [
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
           /* {
                field_id: 'txtMfaAssetNo',
                type: 'text',
                name: 'Asset No.',
                validator: {
                    notEmpty: false,
                    maxLength: 50
                }
            },*/
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
                    notEmptyFile: true
                }
            },
            {
                field_id: 'txfMfaImage2',
                type: 'file',
                name: 'Image 2',
                validator: {
                    notEmptyFile: false
                }
            },
        ];

        formValidate = new MzValidate('formMfa');
        formValidate.registerFields(vData);

        $('#optMfaSite').on('change', function () {
            const siteId = parseInt($(this).val());
            ShowLoader();
            setTimeout(function () {
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
            const image1 = $('#txfMfaImage1').prop('files')[0];
            const image2 = $('#txfMfaImage2').prop('files')[0];
            let errMsg = false;
            if (typeof image1 !== 'undefined') {
                if (!(image1.type === 'image/jpeg' || image1.type === 'image/png')) {
                    errMsg = true;
                    $('#txfMfaImage1Err').text('Image no. 1 type is not allowed. Please make sure image type in JPEG or PNG only.');
                } else if (image1.size/1024/1024 > 10) {
                    errMsg = true;
                    $('#txfMfaImage1Err').text('Image no. 1 size is exceeded. Please make sure image size less than 10 GB.');
                }
            }
            if (typeof image2 !== 'undefined') {
                if (!(image2.type === 'image/jpeg' || image2.type === 'image/png')) {
                    errMsg = true;
                    $('#txfMfaImage2Err').text('Image no. 2 type is not allowed. Please make sure image type in JPEG or PNG only.');
                } else if (image2.size/1024/1024 > 10) {
                    errMsg = true;
                    $('#txfMfaImage2Err').text('Image no. 2 size is exceeded. Please make sure image size less than 10 GB.');
                }
            }
            if (errMsg) {
                toastr['error']('Image type or size are not allowed.', _ALERT_TITLE_ERROR);
                return false;
            }
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const image1Data = {
                            name: 'FCA Audit Image 1',
                            filename: image1.name,
                            size: image1.size,
                            width: imageSize[0][0],
                            height: imageSize[0][1],
                            type: image1.type,
                            data: $('#txfMfaImage1Blob').val(),
                            description: $('#txaMfaImgDesc1').val()
                        };
                        const image2Data = typeof image2 === 'undefined' ? {} : {
                            name: 'FCA Audit Image 2',
                            filename: image2.name,
                            size: image2.size,
                            width: imageSize[1][0],
                            height: imageSize[1][1],
                            type: image2.type,
                            data: $('#txfMfaImage2Blob').val(),
                            description: $('#txaMfaImgDesc2').val()
                        };
                        const data = {
                            siteId: parseInt($('#optMfaSite').val()),
                            fcaZoneId: parseInt($('#optMfaZone').val()),
                            fcaTaskArea: $('#txtMfaArea').val(),
                            assetGroupId: parseInt($('#optMfaAssetGroup').val()),
                            //fcaTaskAssetNo: $('#txtMfaAssetNo').val(),
                            fcaTaskAssetEvaluated: $('#txtMfaEvaluated').val(),
                            fcaTaskDefectItem: $('#txtMfaDefectItem').val(),
                            fcaDefectCategoryId: parseInt($('#optMfaDefectCategory').val()),
                            fcaTaskObservation: $('#txaMfaObservation').val(),
                            image1: image1Data,
                            image2: image2Data
                        };
                        mzAjaxRequest2('fca_task', 'POST', data);
                        classFrom.genTable(1);
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
                mzOptionStopClear('optMfaZone', 'Select Defect Category');
                mzOptionStopClear('optMfaDefectCategory', 'Select Defect Category');
                $('#imgMfaImage1, #imgMfaImage2').attr('src', 'img/background/upload_placeholder.png');
                imageSize = [[0,0],[0,0]];
                $('#modal_fca_add').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
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
}