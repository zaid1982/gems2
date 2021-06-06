function ModalUploadPicture() {

    const className = 'ModalUploadPicture';
    let self = this;
    let classFrom;
    let formMupValidate;
    let itemId;

    this.init = function () {
        const vDataMup = [
            {
                field_id: 'txtMupTitle',
                type: 'text',
                name: 'Image Title',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'txfMupFile',
                type: 'file',
                name: 'Image File',
                validator: {
                    notEmptyFile: true,
                    imageType: true
                }
            }
        ];

        formMupValidate = new MzValidate('formMup');
        formMupValidate.registerFields(vDataMup);

        document.getElementById('txfMupFile').addEventListener('change', mzHandleFileSelect, false);
        document.getElementById('txfMupFile').addEventListener('change', mzHandleFileDimension, false);

        $('#btnMupSubmit').on('click', function () {
            if (!formMupValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }
            else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const fileUpload = $('#txfMupFile').prop('files')[0];
                        const data = {
                            name: $('#txtMupTitle').val(),
                            filename: fileUpload['name'],
                            size: fileUpload['size'],
                            type: fileUpload['type'],
                            data: $('#txfMupFileBlob').val(),
                            width: $('#txfMupFileWidth').val(),
                            height: $('#txfMupFileHeight').val()
                        };
                        mzAjaxRequest2('item_image/'+itemId, 'POST', data);
                        classFrom.genTablePicture(1);
                        $('#modal_upload_picture').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
    };

    this.add = function (_itemId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_itemId]);
                itemId = _itemId;
                formMupValidate.clearValidation();
                $('#modal_upload_picture').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.delete = function (_itemImageId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_itemImageId]);
                mzAjaxRequest2('item_image/'+_itemImageId, 'DELETE');
                classFrom.genTablePicture(1);
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