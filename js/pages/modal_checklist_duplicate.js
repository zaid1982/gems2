function ModalChecklistDuplicate () {

    const className = 'ModalChecklistDuplicate';
    let self = this;
    let formValidate;
    let classFrom;
    let checklistId;

    this.init = function () {
        const vData = [
            {
                field_id: 'txtMcxDocumentNo',
                type: 'text',
                name: 'Document No',
                validator: {
                    notEmpty: true,
                    maxLength: 30
                }
            },
            {
                field_id: 'txtMcxChecklistName',
                type: 'text',
                name: 'Checklist Name',
                validator: {
                    notEmpty: true,
                    maxLength: 255
                }
            }
        ];

        formValidate = new MzValidate('formMcx');
        formValidate.registerFields(vData);

        $('#btnMcxSubmit').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const data = {
                            checklistDocumentNo: $('#txtMcxDocumentNo').val(),
                            checklistName: $('#txtMcxChecklistName').val()
                        };
                        mzAjaxRequest('checklist.php?action=duplicate&checklistId='+checklistId, 'POST', data);
                        classFrom.genTablePcmChecklistRefresh();
                        $('#modal_checklist_duplicate').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
    };

    this.add = function (_checklistId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_checklistId]);
                checklistId = _checklistId;
                //formValidate.clearValidation();
                $('#modal_checklist_duplicate').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
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

    this.setDocumentNo = function (_documentNo) {
        $('#pMcxDocumentNo').text(_documentNo);
    };

    this.setChecklistName = function (_checklistName) {
        $('#pMcxChecklistName').text(_checklistName);
    };

    this.setAssetType = function (_assetType) {
        $('#pMcxAssetType').text(_assetType);
    };
}