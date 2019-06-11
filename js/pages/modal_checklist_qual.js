function ModalChecklistQual() {

    const className = 'ModalChecklistQual';
    let self = this;
    let checklistQualId = '';
    let rowRefresh = '';
    let classFrom;
    let refFrequency;
    let checklistId = '';

    this.init = function () {
        mzOption('optMqlFrequencyId', refFrequency, 'Choose Frequency', 'frequencyId', 'frequencyName', {frequencyStatus: '1'}, 'required', false);

        const vData = [
            {
                field_id: 'txtMqlChecklistQualNumb',
                type: 'text',
                name: 'Numbering',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'txaMqlChecklistQualDesc',
                type: 'text',
                name: 'Description',
                validator: {
                    notEmpty: true,
                    maxLength: 255
                }
            },
            {
                field_id: 'optMqlFrequencyId',
                type: 'select',
                name: 'Frequency',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'chkMqlChecklistQualStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        let formValidate = new MzValidate('formMql');
        formValidate.registerFields(vData);

        $('#formMql').on('keyup change', function () {
            $('#btnMqlSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_checklist_qual').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMqlSubmit').attr('disabled', true);
        });

        $('#btnMqlSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const statusVal = $("input[name='chkMqlChecklistQualStatus']").is(":checked") ? '1' : '2';
                        const data = {
                            checklistQualNumb: $('#txtMqlChecklistQualNumb').val(),
                            checklistQualDesc: $('#txaMqlChecklistQualDesc').val(),
                            frequencyId: $('#optMqlFrequencyId').val(),
                            checklistId: checklistId,
                            checklistQualStatus: statusVal
                        };

                        if (checklistQualId === '') {
                            checklistQualId = mzAjaxRequest('checklist_qual.php', 'POST', data);
                            data['checklistQualId'] = checklistQualId;
                            if (classFrom.getClassName() === 'SectionChecklist') {
                                classFrom.addTableSckChecklistQual(data);
                            }
                        } else {
                            data['action'] = 'update';
                            mzAjaxRequest('checklist_qual.php?checklistQualId='+checklistQualId, 'PUT', data);
                            if (classFrom.getClassName() === 'SectionChecklist') {
                                classFrom.updateTableSckChecklistQual(data, rowRefresh);
                            }
                        }
                        $('#modal_checklist_qual').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.add = function (_checklistId) {
        ShowLoader();
        setTimeout(function () {
            try {
                checklistQualId = '';
                rowRefresh = '';
                checklistId = _checklistId;

                mzSetFieldValue('MqlChecklistQualStatus', '1', 'checkSingle', '1');
                $('#lblMqlTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add Qualitative Task');
                $('#modal_checklist_qual').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.edit = function (_checklistQualId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                checklistQualId = _checklistQualId;
                rowRefresh = _rowRefresh;
                mzOption('optMqlFrequencyId', refFrequency, 'Choose Frequency', 'frequencyId', 'frequencyName', [], '', false);
                mzCheckFuncParam([_checklistQualId, _rowRefresh]);

                const dataMql = mzAjaxRequest('checklist_qual.php?checklistQualId='+checklistQualId, 'GET');
                checklistId = dataMql['checklistId'];
                mzSetFieldValue('MqlChecklistQualNumb', dataMql['checklistQualNumb'], 'text');
                mzSetFieldValue('MqlChecklistQualDesc', dataMql['checklistQualDesc'], 'textarea');
                mzSetFieldValue('MqlFrequencyId', dataMql['frequencyId'], 'select', 'Frequency *');
                mzSetFieldValue('MqlChecklistQualStatus', dataMql['checklistQualStatus'], 'checkSingle', '1');

                $('#lblMqlTitle').html('<i class="far fa-edit text-white"></i> &nbsp;Edit Qualitative Task');
                $('#modal_checklist_qual').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };;

    this.deactivate = function (_checklistQualId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_checklistQualId, _rowRefresh]);
                mzAjaxRequest('checklist_qual.php?checklistQualId='+_checklistQualId, 'PUT', {action: 'deactivate'});
                const tempRow = {checklistQualStatus:'2'};
                if (classFrom.getClassName() === 'SectionChecklist') {
                    classFrom.updateTableSckChecklistQual(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_checklistQualId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_checklistQualId, _rowRefresh]);
                mzAjaxRequest('checklist_qual.php?checklistQualId='+_checklistQualId, 'PUT', {action: 'activate'});
                const tempRow = {checklistQualStatus:'1'};
                if (classFrom.getClassName() === 'SectionChecklist') {
                    classFrom.updateTableSckChecklistQual(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_checklistQualId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_checklistQualId]);
                mzAjaxRequest('checklist_qual.php?checklistQualId='+_checklistQualId, 'DELETE');
                if (classFrom.getClassName() === 'SectionChecklist') {
                    classFrom.genTableSckChecklistQual();
                }
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

    this.setRefFrequency = function (_refFrequency) {
        refFrequency = _refFrequency;
    };
}