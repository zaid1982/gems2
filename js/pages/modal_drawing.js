function ModalDrawing () {
	
	const className = 'ModalDrawing';
    let self = this;
    let formValidate;
	
	this.init = function () {
		const vData = [
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
                    notEmptyFile: true,
                    dwgType: true
                }
            },
            {
                field_id: 'txfMdwPdfFile',
                type: 'file',
                name: 'Drawing PDF File',
                validator: {
                    notEmptyFile: true,
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

        $('#btnMdwSubmit').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }
            else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const data = {
                            drawingTitle: $('#txtMdwTitle').val(),
                            drawingIdNo: $('#txtMdwIdNo').val(),
                            drawingVersion: $('#txtMdwVersion').val(),
                            drawingPublishedBy: $('#txtMdwPublishedBy').val(),
                            drawingPublishedDate: mzConvertDate($('#txtMdwPublishedDate').val()),
                            assetGroupId: $('#optMdwGroup').val(),
                            drawingPermissionLevel: $('#optMdwPermission').val(),
                            drawingRemark: $('#txaMdwRemark').val(),
                            dwgUpload: {
                                name: $('#txfMdwFile').prop('files')[0].name,
                                filename: $('#txfMdwFile').prop('files')[0].size,
                                size: $('#txfMdwFile').prop('files')[0].type,
                                data: $('#txfMdwFileBlob').val(),
                                description: $('#txtMdwTitle').val()
                            },
                            pdfUpload: {
                                name: $('#txfMdwPdfFile').prop('files')[0].name,
                                filename: $('#txfMdwPdfFile').prop('files')[0].size,
                                size: $('#txfMdwPdfFile').prop('files')[0].type,
                                data: $('#txfMdwPdfFileBlob').val(),
                                description: $('#txtMdwTitle').val()
                            }
                        };
                        mzAjaxRequest('drawing', 'POST', data);
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
	
	this.getClassName = function () {
        return className;
    };
	
	this.add = function () {
		ShowLoader();
        setTimeout(function () {
            try {
                formValidate.clearValidation();                
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