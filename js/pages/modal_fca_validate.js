function ModalFcaValidate () {

    const className = 'ModalFcaValidate';
    let self = this;
    let vData;
    let formValidate;
    let classFrom;
    let fcaTaskId;
    let evaluationType;
    let refConditionScale;
    let refEvaluationType;
    let imageSize = [[0,0],[0,0]];

    this.init = function () {
        vData = [
            {
                field_id: 'radMfvResult',
                type: 'radio',
                name: 'Validation Result',
                validator: {
                    notEmptyCheck: true
                }
            },
            {
                field_id: 'txaMfvValidation',
                type: 'text',
                name: 'Validation Remark',
                validator: {
                    notEmpty: false,
                    maxLength: 2000
                }
            },
            {
                field_id: 'txfMfvImage1',
                type: 'file',
                name: 'Rectification Image 1',
                validator: {
                    notEmptyFile: false,
                    imageType: true,
                    fileSize : 6
                }
            },
            {
                field_id: 'txfMfvImage2',
                type: 'file',
                name: 'Rectification Image 2',
                validator: {
                    notEmptyFile: false,
                    imageType: true,
                    fileSize : 6
                }
            }
        ];

        formValidate = new MzValidate('formMfv');
        formValidate.registerFields(vData);

        $("input[name='radMfvResult']:radio").on('change', function () {
            const action = parseInt($(this).val());
            ShowLoader();
            setTimeout(function () {
                try {
                    if (action === 1) {
                        if (evaluationType === 1) {
                            vData[1]['validator']['notEmpty'] = false;
                            vData[2]['validator']['notEmpty'] = false;
                            $('#spanMfvAsteriskValidation').text('');
                            $('#spanMfvAsteriskImage').text('');
                        } else {
                            vData[1]['validator']['notEmpty'] = true;
                            vData[2]['validator']['notEmpty'] = true;
                            $('#spanMfvAsteriskValidation').text('*');
                            $('#spanMfvAsteriskImage').text('*');
                        }
                        $('.divMfvImage').show();
                    } else if(action === 2) {
                        vData[1]['validator']['notEmpty'] = true;
                        vData[2]['validator']['notEmpty'] = false;
                        $('#spanMfvAsteriskValidation').text('*');
                        $('#spanMfvAsteriskImage').text('');
                        $('.divMfvImage').hide();
                    }
                    $('#divMfvRemark').show();
                    formValidate.registerFields(vData);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 100);
        });

        $('#txfMfvImage1').on('change', function () {
            try {
                self.saveImageSize(this.files[0], 0);
                $('#imgMfvImage1').attr('src', 'img/background/upload_placeholder.png');
                mzDisplayImageFileInput(this, 'imgMfvImage1');
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
        });

        $('#txfMfvImage2').on('change', function () {
            try {
                self.saveImageSize(this.files[0], 1);
                $('#imgMfvImage2').attr('src', 'img/background/upload_placeholder.png');
                mzDisplayImageFileInput(this, 'imgMfvImage2');
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
        });

        document.getElementById('txfMfvImage1').addEventListener('change', mzHandleFileSelect, false);
        document.getElementById('txfMfvImage2').addEventListener('change', mzHandleFileSelect, false);

        $('#btnMfvSubmit').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else {
                setTimeout(function () {
                    try {
                        const result = parseInt($("input[name='radMfvResult']:checked").val());
                        const image1 = $('#txfMfvImage1').prop('files')[0];
                        const image2 = $('#txfMfvImage2').prop('files')[0];
                        if (result === 1) {
                            const image1Data = typeof image1 === 'undefined' ? {} : {
                                name: 'FCA Rectification Image 1',
                                filename: image1.name,
                                size: image1.size,
                                width: imageSize[0][0],
                                height: imageSize[0][1],
                                type: image1.type,
                                data: $('#txfMfvImage1Blob').val(),
                                description: 'FCA Rectification Image 1'
                            };
                            const image2Data = typeof image2 === 'undefined' ? {} : {
                                name: 'FCA Rectification Image 2',
                                filename: image2.name,
                                size: image2.size,
                                width: imageSize[1][0],
                                height: imageSize[1][1],
                                type: image2.type,
                                data: $('#txfMfvImage2Blob').val(),
                                description: 'FCA Rectification Image 2'
                            };
                            const data = {
                                fcaTaskValidation: $('#txaMfvValidation').val(),
                                image1: image1Data,
                                image2: image2Data
                            };
                            mzAjaxRequest2('fca_task/validate/'+fcaTaskId, 'POST', data);
                            classFrom.displayInfo(false, true, true);
                        }
                        else {
                            const data = {
                                fcaTaskValidation: $('#txaMfvValidation').val()
                            };
                            mzAjaxRequest2('fca_task/correction/'+fcaTaskId, 'POST', data);
                            classFrom.displayInfo(false, false, true);
                        }
                        classFrom.setIsUpdated(true);
                        $('#modal_fca_validate').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
    };

    this.load = function (_fcaTaskId, _conditionScale, _evaluationType) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_fcaTaskId, _conditionScale, _evaluationType]);
                fcaTaskId = _fcaTaskId;
                evaluationType = _evaluationType;
                formValidate.clearValidation();
                vData[1]['validator']['notEmpty'] = false;
                vData[2]['validator']['notEmpty'] = false;
                formValidate.registerFields(vData);
                mzSetFieldValue('MfvConditionScale', refConditionScale[_conditionScale], 'text');
                mzSetFieldValue('MfvEvaluationType', refEvaluationType[evaluationType], 'text');
                $('.divMfvImage, #divMfvRemark').hide();
                $('#imgMfvImage1, #imgMfvImage2').attr('src', 'img/background/upload_placeholder.png');
                imageSize = [[0,0],[0,0]];
                $('#modal_fca_validate').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
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

    this.setRefConditionScale = function (_refConditionScale) {
        refConditionScale = _refConditionScale;
    };

    this.setRefEvaluationType = function (_refEvaluationType) {
        refEvaluationType = _refEvaluationType;
    };
}