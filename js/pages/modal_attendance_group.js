function ModalAttendanceGroup () {
	
	const className = 'ModalAttendanceGroup';
    let self = this;
    let formValidate;
    let drawingId;
    let siteId;
    let vData;
    let classFrom;
    let refUser;
	
	this.init = function () {
        vData = [
            {
                field_id: 'txtMtgTitle',
                type: 'text',
                name: 'Document Title',
                validator: {
                    notEmpty: true,
                    maxLength: 250
                }
            },
            {
                field_id: 'txtMtgIdNo',
                type: 'text',
                name: 'Document ID No.',
                validator: {
                    notEmpty: true,
                    maxLength: 30
                }
            },
            {
                field_id: 'txtMtgVersion',
                type: 'text',
                name: 'Document Version',
                validator: {
                    notEmpty: true,
                    maxLength: 10
                }
            },
            {
                field_id: 'txtMtgPublishedBy',
                type: 'text',
                name: 'Published By',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'txtMtgPublishedDate',
                type: 'text',
                name: 'Published Date',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMtgBlock',
                type: 'text',
                name: 'Document Version',
                validator: {
                    notEmpty: false,
                    maxLength: 100
                }
            },
            {
                field_id: 'txtMtgLevel',
                type: 'text',
                name: 'Document Version',
                validator: {
                    notEmpty: false,
                    maxLength: 20
                }
            },
            {
                field_id: 'optMtgGroup',
                type: 'select',
                name: 'Group',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMtgPermission',
                type: 'select',
                name: 'Permission Level',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txfMtgFile',
                type: 'file',
                name: 'Drawing DWG File',
                validator: {
                    notEmptyFile: false,
                    dwgType: true
                }
            },
            {
                field_id: 'txfMtgPdfFile',
                type: 'file',
                name: 'Drawing PDF File',
                validator: {
                    notEmptyFile: false,
                    pdfType: true
                }
            },
            {
                field_id: 'txaMtgRemark',
                type: 'text',
                name: 'Remarks',
                validator: {
                    maxLength: 5000
                }
            }
        ];

        formValidate = new MzValidate('formMtg');
        formValidate.registerFields(vData);

        $('#btnMtgSubmit').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }
            else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        let dataDrawing = {                        
                            drawingTitle: $('#txtMtgTitle').val(),
                            drawingIdNo: $('#txtMtgIdNo').val(),
                            drawingVersion: $('#txtMtgVersion').val(),
                            drawingPublishedBy: $('#txtMtgPublishedBy').val(),
                            drawingPublishedDate: mzConvertDate($('#txtMtgPublishedDate').val()),
                            drawingBlock: $('#txtMtgBlock').val(),
                            drawingLevel: $('#txtMtgLevel').val(),
                            assetGroupId: $('#optMtgGroup').val(),
                            drawingPermissionLevel: $('#optMtgPermission').val(),
                            drawingRemark: $('#txaMtgRemark').val()
                        };
                        const fileDwg = $('#txfMtgFile').prop('files');
                        if (fileDwg.length > 0) {
                            const dataDwgUpload = {
                                name: $('#txtMtgTitle').val(),
                                filename: fileDwg[0].name,
                                size: fileDwg[0].size,
                                type: fileDwg[0].type,
                                data: $('#txfMtgFileBlob').val(),
                                description: $('#txtMtgTitle').val()
                            };
                            dataDrawing['drawingDwg'] = mzAjaxRequest2('drawing/upload_dwg_drawing', 'POST', dataDwgUpload);
                        }
                        const filePdf = $('#txfMtgPdfFile').prop('files');
                        if (filePdf.length > 0) {
                            const dataPdfUpload = {
                                name: $('#txtMtgTitle').val(),
                                filename: filePdf[0].name,
                                size: filePdf[0].size,
                                type: filePdf[0].type,
                                data: $('#txfMtgPdfFileBlob').val(),
                                description: $('#txtMtgTitle').val()
                            };
                            dataDrawing['drawingPdf'] = mzAjaxRequest2('drawing/upload_pdf_drawing', 'POST', dataPdfUpload);
                        }
                        mzAjaxRequest2('drawing', 'POST', dataDrawing);
                        if (classFrom.getClassName() === 'MainDrawingRecords') {
                            classFrom.genTable();
                        }
                        $('#modal_attendance_group').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        $('#btnMtgSave').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }
            else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        let dataDrawing = {                        
                            drawingTitle: $('#txtMtgTitle').val(),
                            drawingIdNo: $('#txtMtgIdNo').val(),
                            drawingVersion: $('#txtMtgVersion').val(),
                            drawingPublishedBy: $('#txtMtgPublishedBy').val(),
                            drawingPublishedDate: mzConvertDate($('#txtMtgPublishedDate').val()),
                            drawingBlock: $('#txtMtgBlock').val(),
                            drawingLevel: $('#txtMtgLevel').val(),
                            assetGroupId: $('#optMtgGroup').val(),
                            drawingPermissionLevel: $('#optMtgPermission').val(),
                            drawingRemark: $('#txaMtgRemark').val()
                        };
                        const fileDwg = $('#txfMtgFile').prop('files');
                        if (fileDwg.length > 0) {
                            const dataDwgUpload = {
                                name: $('#txtMtgTitle').val(),
                                filename: fileDwg[0].name,
                                size: fileDwg[0].size,
                                type: fileDwg[0].type,
                                data: $('#txfMtgFileBlob').val(),
                                description: $('#txtMtgTitle').val()
                            };                        
                            mzAjaxRequest2('drawing/update_dwg_drawing/'+drawingId, 'PUT', dataDwgUpload);
                        }
                        const filePdf = $('#txfMtgPdfFile').prop('files');
                        if (filePdf.length > 0) {
                            const dataPdfUpload = {
                                name: $('#txtMtgTitle').val(),
                                filename: filePdf[0].name,
                                size: filePdf[0].size,
                                type: filePdf[0].type,
                                data: $('#txfMtgPdfFileBlob').val(),
                                description: $('#txtMtgTitle').val()
                            };
                            mzAjaxRequest2('drawing/update_pdf_drawing/'+drawingId, 'PUT', dataPdfUpload);
                        }
                        mzAjaxRequest2('drawing/'+drawingId, 'PUT', dataDrawing);
                        if (classFrom.getClassName() === 'MainDrawingRecords') {
                            classFrom.genTable();
                        }
                        $('#modal_attendance_group').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
	};
	
	this.add = function (_siteId) {
		ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteId]);
                siteId = _siteId;
                formValidate.clearValidation();



                $('#lblMtgModalTitle').html('<i class="fas fa-plus text-white mr-2"></i> Add Attendance Group');
                mzOptionStop('optMtgSupervisor', refUser, 'Choose Supervisor', 'userId', 'userFullName', {userType: '1', userStatus: '1', siteId: siteId}, 'required');
                $('#btnMtgSave').hide();
                $('#btnMtgSubmit').show();

                $('#modal_attendance_group').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
	};

    this.edit = function (_drawingId) {
		ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_drawingId]);
                drawingId = _drawingId;
                formValidate.clearValidation();                

                const drawing = mzAjaxRequest2('drawing/'+drawingId, 'GET');
                mzSetFieldValue('MtgTitle', drawing['drawingTitle'], 'text');
                mzSetFieldValue('MtgIdNo', drawing['drawingIdNo'], 'text');
                mzSetFieldValue('MtgVersion', drawing['drawingVersion'], 'text');
                mzSetFieldValue('MtgPublishedBy', drawing['drawingPublishedBy'], 'text');
                mzSetFieldValue('MtgPublishedDate', drawing['drawingPublishedDate'], 'date');
                mzSetFieldValue('MtgBlock', drawing['drawingBlock'], 'text');
                mzSetFieldValue('MtgLevel', drawing['drawingLevel'], 'text');
                mzSetFieldValue('MtgGroup', drawing['assetGroupId'], 'select');
                mzSetFieldValue('MtgPermission', drawing['drawingPermissionLevel'], 'select');
                mzSetFieldValue('MtgRemark', drawing['drawingRemark'], 'textarea');
                
                $('#lblMtgModalTitle').html('<i class="fas fa-edit text-white mr-2"></i> Edit Drawing File');
                $('#lblMtgFile').text('Replace Drawing DWG File');
                $('#lblMtgPdfFile').text('Replace Drawing PDF File');
                $('#btnMtgSubmit').hide();
                $('#btnMtgSave').show();

                $('#modal_attendance_group').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
	};

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setSiteName = function (_siteName) {
        mzSetFieldValue('MtgSiteName', _siteName, 'text');
    };

    this.setSiteCode = function (_siteCode) {
        mzSetFieldValue('MtgSiteCode', _siteCode, 'text');
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };
}