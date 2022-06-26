function SectionFcaInfo () {

    const className = 'SectionFcaInfo';
    let self = this;
    let classFrom;
    let fcaTaskId;
    let sectionFrom;
    let isUpdated = false;
    let dataDb;
    let refStatus;
    let refUser;
    let refSite;
    let refAssetGroup;
    let refDefectCategory;
    let refFcaZone;
    let refConditionScale;
    let refEvaluationType;
    let modalFcaRecommendClass;
    let modalFcaValidateClass;
    let modalFcaAddClass;
    let modalConfirmDeleteClass;

    this.init = function () {
        self.hideSection();

        $('#btnSfcBack').on('click', function () {
            self.hideSection();
            if (isUpdated) {
                classFrom.genTable();
            }
            classFrom.showMain(sectionFrom==='Correction'?'Observe':sectionFrom);
            $(window).scrollTop(0);
        });

        $('#btnSfcRecommend').on('click', function () {
            modalFcaRecommendClass.load(fcaTaskId, dataDb);
        });

        $('#btnSfcValidate').on('click', function () {
            modalFcaValidateClass.load(fcaTaskId, mzReplaceNull(dataDb['fcaTaskConditionScale'], 0), mzReplaceNull(dataDb['fcaTaskEvaluationType'], 0));
        });

        $('#btnSfcCorrection').on('click', function () {
            modalFcaAddClass.setClassFrom(self);
            modalFcaAddClass.correct(fcaTaskId);
        });

        $('#btnSfcExcludeReport').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    mzAjaxRequest2('fca_task/exclude_report/'+fcaTaskId, 'PUT');
                    $('#btnSfcExcludeReport').hide();
                    $('#btnSfcIncludeReport').show();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnSfcIncludeReport').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    mzAjaxRequest2('fca_task/include_report/'+fcaTaskId, 'PUT');
                    $('#btnSfcIncludeReport').hide();
                    $('#btnSfcExcludeReport').show();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnSfcRemove').on('click', function () {
            modalConfirmDeleteClass.delete(fcaTaskId, self);
        });
    };

    this.load = function (_fcaTaskId, _sectionFrom) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_fcaTaskId, _sectionFrom]);
                fcaTaskId = _fcaTaskId;
                sectionFrom = _sectionFrom;
                isUpdated = false;
                self.displayInfo(true, true, true);
                classFrom.hideMain();
                self.showSection();
                window.scrollTo({top: 0, behavior: 'smooth'});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.displayInfo = function (isImage, isImageRectify, isTask) {
        try {
            dataDb = mzAjaxRequest2('fca_task/'+fcaTaskId, 'GET');
            $('#lblSfcAuditNo').text(dataDb['fcaTaskNo']);
            $('#lblSfcStatus').text(refStatus[dataDb['fcaTaskStatus']]['statusDesc']);
            $('#pSfcObservation').text(dataDb['fcaTaskObservation']);
            $('#pSfcRecommendation').html(mzReplaceNull(dataDb['fcaTaskRecommendation'], '<i>[to be filled in Recommendation Process]</i>'));
            $('#pSfcValidation').html(mzReplaceNull(dataDb['fcaTaskValidation'], '<i>[to be filled in Validation Process]</i>'));
            $('#pSfcSite').text(dataDb['siteId']!==null?refSite[dataDb['siteId']]['siteName']:'-');
            $('#pSfcZone').text(dataDb['fcaZoneId']!==null?refFcaZone[dataDb['fcaZoneId']]['fcaZoneName']:'-');
            $('#pSfcArea').text(mzReplaceNull(dataDb['fcaTaskArea'], '-'));
            $('#pSfcAssetGroup').text(dataDb['assetGroupId']!==null?refAssetGroup[dataDb['assetGroupId']]['assetGroupName']:'-');
            $('#pSfcAssetNo').text(mzReplaceNull(dataDb['fcaTaskAssetNo'], '-'));
            $('#pSfcEvaluatedAsset').text(mzReplaceNull(dataDb['fcaTaskAssetEvaluated'], '-'));
            $('#pSfcDefectItem').text(mzReplaceNull(dataDb['fcaTaskDefectItem'], '-'));
            $('#pSfcDefectCategory').text(dataDb['fcaDefectCategoryId']!==null?refDefectCategory[dataDb['fcaDefectCategoryId']]['fcaDefectCategoryName']:'-');
            $('#pSfcConditionScale').text(dataDb['fcaTaskConditionScale']!==null?refConditionScale[dataDb['fcaTaskConditionScale']]:'-');
            $('#pSfcEvaluationType').text(dataDb['fcaTaskEvaluationType']!==null?refEvaluationType[dataDb['fcaTaskEvaluationType']]:'-');
            $('#pSfcAuditor').text(dataDb['fcaTaskCreatedBy']!==null?refUser[dataDb['fcaTaskCreatedBy']]['userFirstName']:'-');
            $('#pSfcValidator').text(dataDb['fcaTaskValidateBy']!==null?refUser[dataDb['fcaTaskValidateBy']]['userFirstName']:'-');
            $('#pSfcAuditDate').text(mzConvertDateDisplay(dataDb['fcaTaskTimeCreated']));
            $('#pSfcValidateDate').text(mzReplaceNull(mzConvertDateDisplay(dataDb['fcaTaskTimeValidated']), '-'));

            $('#btnSfcExcludeReport, #btnSfcIncludeReport').hide();
            if (dataDb['fcaTaskExcludeReport'] === 1) {
                $('#btnSfcIncludeReport').show();
            } else {
                $('#btnSfcExcludeReport').show();
            }

            if (isImage) {
                let divImageAudit = $('#divSfcImageAudit');
                divImageAudit.html('');
                if (dataDb['fcaTaskImage1'] !== null) {
                    const image1 = mzAjaxRequest2('document/upload/'+dataDb['fcaTaskImage1'], 'GET');
                    if (image1['fileExist'] && image1['uploadFileWidth'] !== null && image1['uploadFileHeight'] !== null) {
                        divImageAudit.append('<figure class="col-md-6">\n' +
                            '   <a href="'+image1['src']+'" data-size="'+image1['uploadFileWidth']+'x'+image1['uploadFileHeight']+'">\n' +
                            '     <img src="'+image1['src']+'" class="img-fluid img-thumbnail" alt="thumbnail" width="100%">\n' +
                            '   </a>\n' +
                            '   <p class="mb-0 font-small text-center">Audit Image 1</p>\n' +
                            '</figure>\n');
                    } else {
                        divImageAudit.append(self.getNoImageHtml('Audit Image 1'));
                    }
                } else {
                    divImageAudit.append(self.getNoImageHtml('Audit Image 1'));
                }
                if (dataDb['fcaTaskImage2'] !== null) {
                    const image2 = mzAjaxRequest2('document/upload/'+dataDb['fcaTaskImage2'], 'GET');
                    if (image2['fileExist'] && image2['uploadFileWidth'] !== null && image2['uploadFileHeight'] !== null) {
                        divImageAudit.append('<figure class="col-md-6">\n' +
                            '   <a href="'+image2['src']+'" data-size="'+image2['uploadFileWidth']+'x'+image2['uploadFileHeight']+'">\n' +
                            '     <img src="'+image2['src']+'" class="img-fluid img-thumbnail" alt="thumbnail" width="100%">\n' +
                            '   </a>\n' +
                            '   <p class="mb-0 font-small text-center">Audit Image 2</p>\n' +
                            '</figure>\n');
                    } else {
                        divImageAudit.append(self.getNoImageHtml('Audit Image 2'));
                    }
                } else {
                    divImageAudit.append(self.getNoImageHtml('Audit Image 2'));
                }
            }

            if (isImageRectify) {
                let divImageRectify = $('#divSfcImageRectify');
                divImageRectify.html('');
                if (dataDb['fcaTaskImageRectify1'] !== null) {
                    const imageRectify1 = mzAjaxRequest2('document/upload/' + dataDb['fcaTaskImageRectify1'], 'GET');
                    if (imageRectify1['fileExist'] && imageRectify1['uploadFileWidth'] !== null && imageRectify1['uploadFileHeight'] !== null) {
                        divImageRectify.append('<figure class="col-md-6">\n' +
                            '   <a href="' + imageRectify1['src'] + '" data-size="' + imageRectify1['uploadFileWidth'] + 'x' + imageRectify1['uploadFileHeight'] + '">\n' +
                            '     <img src="' + imageRectify1['src'] + '" class="img-fluid img-thumbnail" alt="thumbnail" width="100%">\n' +
                            '   </a>\n' +
                            '   <p class="mb-0 font-small text-center">Rectify Image 1</p>\n' +
                            '</figure>\n');
                    } else {
                        divImageRectify.append(self.getNoImageHtml('Rectify Image 1'));
                    }
                } else {
                    divImageRectify.append(self.getNoImageHtml('Rectify Image 1'));
                }
                if (dataDb['fcaTaskImageRectify2'] !== null) {
                    const imageRectify2 = mzAjaxRequest2('document/upload/' + dataDb['fcaTaskImageRectify1'], 'GET');
                    if (imageRectify2['fileExist'] && imageRectify2['uploadFileWidth'] !== null && imageRectify2['uploadFileHeight'] !== null) {
                        divImageRectify.append('<figure class="col-md-6">\n' +
                            '   <a href="' + imageRectify2['src'] + '" data-size="' + imageRectify2['uploadFileWidth'] + 'x' + imageRectify2['uploadFileHeight'] + '">\n' +
                            '     <img src="' + imageRectify2['src'] + '" class="img-fluid img-thumbnail" alt="thumbnail" width="100%">\n' +
                            '   </a>\n' +
                            '   <p class="mb-0 font-small text-center">Rectify Image 2</p>\n' +
                            '</figure>\n');
                    } else {
                        divImageRectify.append(self.getNoImageHtml('Rectify Image 2'));
                    }
                } else {
                    divImageRectify.append(self.getNoImageHtml('Rectify Image 2'));
                }
            }

            if (isTask) {
                $('#divSfcProgress').html('');
                const dataProgress = mzAjaxRequest2('fca_task/progress/'+dataDb['transactionId'], 'GET');
                $.each(dataProgress, function (n, progress) {
                    $('#divSfcProgress').append('<p class="mb-0 ml-1 font-small"><strong>'+progress['checkpointDesc']+'</strong><span class="float-right">'+progress['duration']+'</span></p>\n' +
                        '<div class="progress md-progress mb-0" style="height: 20px">\n' +
                        '   <div class="progress-bar progress-bar-striped progress-bar-animated '+progress['checkpointColor']+'" role="progressbar" style="width: '+progress['barPercent']+'%; height: 20px" aria-valuenow="'+progress['barPercent']+'%" aria-valuemin="0" aria-valuemax="100"></div>\n' +
                        '</div>\n' +
                        '<p class="mt-0 mb-2 ml-1 font-small font-italic">'+mzConvertDateDisplay(progress['taskTimeSubmit'])+'</p>');
                });

                $('#btnSfcEditAudit, #btnSfcEditRecommend, #btnSfcEditValidate, #btnSfcRecommend, #btnSfcValidate, #btnSfcCorrection').hide();
                if (dataDb['fcaTaskStatus'] === 55 && sectionFrom === 'Recommend') {
                    $('#btnSfcRecommend').show();
                } else if (dataDb['fcaTaskStatus'] === 56) {
                    if (sectionFrom === 'Validate') {
                        $('#btnSfcValidate').show();
                    }
                } else if (dataDb['fcaTaskStatus'] === 57) {
                    if (sectionFrom === 'Correction') {
                        $('#btnSfcCorrection').show();
                    }
                }
            }
        } catch (e) {
            throw new Error(_ALERT_MSG_ERROR_DEFAULT);
        }
    };

    this.delete = function (_fcaTaskId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_fcaTaskId]);
                mzAjaxRequest2('fca_task/'+_fcaTaskId, 'DELETE');
                self.hideSection();
                classFrom.genTable();
                classFrom.showMain(sectionFrom==='Correction'?'Observe':sectionFrom);
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.getNoImageHtml = function (_description) {
        return '<figure class="col-md-6">\n' +
            '   <a href="img/background/no-image.png" data-size="284x177">\n' +
            '       <img src="img/background/no-image.png" class="img-fluid img-thumbnail" alt="thumbnail" width="100%">\n' +
            '   </a>\n' +
            '   <p class="mb-0 font-small text-center">'+_description+'</p>\n' +
            '</figure>\n';
    };

    this.showSection = function () {
        $('.sectionFcaInfo').show();
    };

    this.hideSection = function () {
        $('.sectionFcaInfo').hide();
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setIsUpdated = function (_isUpdated) {
        isUpdated = _isUpdated;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
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

    this.setRefFcaZone = function (_refFcaZone) {
        refFcaZone = _refFcaZone;
    };

    this.setRefConditionScale = function (_refConditionScale) {
        refConditionScale = _refConditionScale;
    };

    this.setRefEvaluationType = function (_refEvaluationType) {
        refEvaluationType = _refEvaluationType;
    };

    this.setModalFcaRecommendClass = function (_modalFcaRecommendClass) {
        modalFcaRecommendClass = _modalFcaRecommendClass;
    };

    this.setModalFcaValidateClass = function (_modalFcaValidateClass) {
        modalFcaValidateClass = _modalFcaValidateClass;
    };

    this.setModalFcaAddClass = function (_modalFcaAddClass) {
        modalFcaAddClass = _modalFcaAddClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}