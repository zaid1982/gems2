function ModalDrawing () {
	
	const className = 'ModalDrawing';
    let self = this;
    let formValidate;
    let drawingId;
    let vData;
    let classFrom;
	
	this.init = function () {
		vData = [
            {
                field_id: 'txtMdwTitle',
                type: 'text',
                name: 'Document Title',
                validator: {
                    notEmpty: true,
                    maxLength: 250
                }
            },
            {
                field_id: 'txtMdwIdNo',
                type: 'text',
                name: 'Document ID No.',
                validator: {
                    notEmpty: true,
                    maxLength: 30
                }
            },
            {
                field_id: 'txtMdwVersion',
                type: 'text',
                name: 'Document Version',
                validator: {
                    notEmpty: true,
                    maxLength: 10
                }
            },
            {
                field_id: 'txtMdwPublishedBy',
                type: 'text',
                name: 'Published By',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'txtMdwPublishedDate',
                type: 'text',
                name: 'Published Date',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMdwBlock',
                type: 'text',
                name: 'Document Version',
                validator: {
                    notEmpty: false,
                    maxLength: 100
                }
            },
            {
                field_id: 'txtMdwLevel',
                type: 'text',
                name: 'Document Version',
                validator: {
                    notEmpty: false,
                    maxLength: 20
                }
            },
            {
                field_id: 'optMdwGroup',
                type: 'select',
                name: 'Group',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMdwPermission',
                type: 'select',
                name: 'Permission Level',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txfMdwFile',
                type: 'file',
                name: 'Drawing DWG File',
                validator: {
                    notEmptyFile: false,
                    dwgType: true
                }
            },
            {
                field_id: 'txfMdwPdfFile',
                type: 'file',
                name: 'Drawing PDF File',
                validator: {
                    notEmptyFile: false,
                    pdfType: true
                }
            },
            {
                field_id: 'txaMdwRemark',
                type: 'text',
                name: 'Remarks',
                validator: {
                    maxLength: 5000
                }
            }
        ];

        formValidate = new MzValidate('formMdw');
        formValidate.registerFields(vData);

        document.getElementById('txfMdwFile').addEventListener('change', mzHandleFileSelect, false);
        document.getElementById('txfMdwPdfFile').addEventListener('change', mzHandleFileSelect, false);
        
        $('#btnMdwSubmit').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }
            else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        let dataDrawing = {                        
                            drawingTitle: $('#txtMdwTitle').val(),
                            drawingIdNo: $('#txtMdwIdNo').val(),
                            drawingVersion: $('#txtMdwVersion').val(),
                            drawingPublishedBy: $('#txtMdwPublishedBy').val(),
                            drawingPublishedDate: mzConvertDate($('#txtMdwPublishedDate').val()),
                            drawingBlock: $('#txtMdwBlock').val(),
                            drawingLevel: $('#txtMdwLevel').val(),
                            assetGroupId: $('#optMdwGroup').val(),
                            drawingPermissionLevel: $('#optMdwPermission').val(),
                            drawingRemark: $('#txaMdwRemark').val()
                        };
                        const fileDwg = $('#txfMdwFile').prop('files');
                        if (fileDwg.length > 0) {
                            const dataDwgUpload = {
                                name: $('#txtMdwTitle').val(),
                                filename: fileDwg[0].name,
                                size: fileDwg[0].size,
                                type: fileDwg[0].type,
                                data: $('#txfMdwFileBlob').val(),
                                description: $('#txtMdwTitle').val()
                            };
                            dataDrawing['drawingDwg'] = mzAjaxRequest2('drawing/upload_dwg_drawing', 'POST', dataDwgUpload);
                        }
                        const filePdf = $('#txfMdwPdfFile').prop('files');
                        if (filePdf.length > 0) {
                            const dataPdfUpload = {
                                name: $('#txtMdwTitle').val(),
                                filename: filePdf[0].name,
                                size: filePdf[0].size,
                                type: filePdf[0].type,
                                data: $('#txfMdwPdfFileBlob').val(),
                                description: $('#txtMdwTitle').val()
                            };
                            dataDrawing['drawingPdf'] = mzAjaxRequest2('drawing/upload_pdf_drawing', 'POST', dataPdfUpload);
                        }
                        mzAjaxRequest2('drawing', 'POST', dataDrawing);
                        if (classFrom.getClassName() === 'MainDrawingRecords') {
                            classFrom.genTable();
                        }
                        $('#modal_drawing').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        $('#btnMdwSave').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }
            else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        let dataDrawing = {                        
                            drawingTitle: $('#txtMdwTitle').val(),
                            drawingIdNo: $('#txtMdwIdNo').val(),
                            drawingVersion: $('#txtMdwVersion').val(),
                            drawingPublishedBy: $('#txtMdwPublishedBy').val(),
                            drawingPublishedDate: mzConvertDate($('#txtMdwPublishedDate').val()),
                            drawingBlock: $('#txtMdwBlock').val(),
                            drawingLevel: $('#txtMdwLevel').val(),
                            assetGroupId: $('#optMdwGroup').val(),
                            drawingPermissionLevel: $('#optMdwPermission').val(),
                            drawingRemark: $('#txaMdwRemark').val()
                        };
                        const fileDwg = $('#txfMdwFile').prop('files');
                        if (fileDwg.length > 0) {
                            const dataDwgUpload = {
                                name: $('#txtMdwTitle').val(),
                                filename: fileDwg[0].name,
                                size: fileDwg[0].size,
                                type: fileDwg[0].type,
                                data: $('#txfMdwFileBlob').val(),
                                description: $('#txtMdwTitle').val()
                            };                        
                            mzAjaxRequest2('drawing/update_dwg_drawing/'+drawingId, 'PUT', dataDwgUpload);
                        }
                        const filePdf = $('#txfMdwPdfFile').prop('files');
                        if (filePdf.length > 0) {
                            const dataPdfUpload = {
                                name: $('#txtMdwTitle').val(),
                                filename: filePdf[0].name,
                                size: filePdf[0].size,
                                type: filePdf[0].type,
                                data: $('#txfMdwPdfFileBlob').val(),
                                description: $('#txtMdwTitle').val()
                            };
                            mzAjaxRequest2('drawing/update_pdf_drawing/'+drawingId, 'PUT', dataPdfUpload);
                        }
                        mzAjaxRequest2('drawing/'+drawingId, 'PUT', dataDrawing);
                        if (classFrom.getClassName() === 'MainDrawingRecords') {
                            classFrom.genTable();
                        }
                        $('#modal_drawing').modal('hide');
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
                drawingId = '';
                formValidate.clearValidation();                

                $('#lblMdwModalTitle').html('<i class="fas fa-upload text-white"></i> Upload Drawing File');
                $('#lblMdwFile').text('Drawing DWG File');
                $('#lblMdwPdfFile').text('Drawing PDF File');
                formValidate.registerFields(vData);
                $('#btnMdwSave').hide();
                $('#btnMdwSubmit').show();

                $('#modal_drawing').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
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
                console.log(drawing);
                mzSetFieldValue('MdwTitle', drawing['drawingTitle'], 'text');
                mzSetFieldValue('MdwIdNo', drawing['drawingIdNo'], 'text');
                mzSetFieldValue('MdwVersion', drawing['drawingVersion'], 'text');
                mzSetFieldValue('MdwPublishedBy', drawing['drawingPublishedBy'], 'text');
                mzSetFieldValue('MdwPublishedDate', drawing['drawingPublishedDate'], 'date');
                mzSetFieldValue('MdwBlock', drawing['drawingBlock'], 'text');
                mzSetFieldValue('MdwLevel', drawing['drawingLevel'], 'text');
                mzSetFieldValue('MdwGroup', drawing['assetGroupId'], 'select');
                mzSetFieldValue('MdwPermission', drawing['drawingPermissionLevel'], 'select');
                mzSetFieldValue('MdwRemark', drawing['drawingRemark'], 'textarea');
                
                $('#lblMdwModalTitle').html('<i class="fas fa-edit text-white"></i> Edit Drawing File');
                $('#lblMdwFile').text('Replace Drawing DWG File');
                $('#lblMdwPdfFile').text('Replace Drawing PDF File');
                formValidate.registerFields(vData);
                $('#btnMdwSubmit').hide();
                $('#btnMdwSave').show();

                $('#modal_drawing').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
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
}