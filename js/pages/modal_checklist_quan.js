function ModalChecklistQuan() {

    const className = 'ModalChecklistQuan';
    let self = this;
    let checklistQuanId = '';
    let rowRefresh = '';
    let classFrom;
    let refFrequency;
    let checklistId = '';

    this.init = function () {
        mzOption('optMqnFrequencyId', refFrequency, 'Choose Frequency', 'frequencyId', 'frequencyName', {frequencyStatus: '1'}, 'required', false);

        const vData = [
            {
                field_id: 'txtMqnChecklistQuanNumb',
                type: 'text',
                name: 'Numbering',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'txaMqnChecklistQuanDesc',
                type: 'text',
                name: 'Description',
                validator: {
                    notEmpty: true,
                    maxLength: 255
                }
            },
            {
                field_id: 'txtMqnChecklistQuanUnit',
                type: 'text',
                name: 'Unit',
                validator: {
                    maxLength: 30
                }
            },
            {
                field_id: 'optMqnFrequencyId',
                type: 'select',
                name: 'Frequency',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'chkMqnChecklistQuanStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        let formValidate = new MzValidate('formMqn');
        formValidate.registerFields(vData);

        $('#formMqn').on('keyup change', function () {
            $('#btnMqnSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_checklist_quan').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMqnSubmit').attr('disabled', true);
        });

        $('#btnMqnSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const statusVal = $("input[name='chkMqnChecklistQuanStatus']").is(":checked") ? '1' : '2';
                        const data = {
                            checklistQuanNumb: $('#txtMqnChecklistQuanNumb').val(),
                            checklistQuanDesc: $('#txaMqnChecklistQuanDesc').val(),
                            checklistQuanUnit: $('#txtMqnChecklistQuanUnit').val(),
                            frequencyId: $('#optMqnFrequencyId').val(),
                            checklistId: checklistId,
                            checklistQuanStatus: statusVal
                        };

                        if (checklistQuanId === '') {
                            checklistQuanId = mzAjaxRequest('checklist_quan.php', 'POST', data);
                            data['checklistQuanId'] = checklistQuanId;
                            if (classFrom.getClassName() === 'SectionChecklist') {
                                classFrom.addTableSckChecklistQuan(data);
                            }
                        } else {
                            data['action'] = 'update';
                            mzAjaxRequest('checklist_quan.php?checklistQuanId='+checklistQuanId, 'PUT', data);
                            if (classFrom.getClassName() === 'SectionChecklist') {
                                classFrom.updateTableSckChecklistQuan(data, rowRefresh);
                            }
                        }
                        $('#modal_checklist_quan').modal('hide');
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
                checklistQuanId = '';
                rowRefresh = '';
                checklistId = _checklistId;

                mzSetFieldValue('MqnChecklistQuanStatus', '1', 'checkSingle', '1');
                $('#lblMqnTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add Quanitative Task');
                $('#modal_checklist_quan').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.edit = function (_checklistQuanId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                checklistQuanId = _checklistQuanId;
                rowRefresh = _rowRefresh;
                mzOption('optMqnFrequencyId', refFrequency, 'Choose Frequency', 'frequencyId', 'frequencyName', [], '', false);
                mzCheckFuncParam([_checklistQuanId, _rowRefresh]);

                const dataMqn = mzAjaxRequest('checklist_quan.php?checklistQuanId='+checklistQuanId, 'GET');
                checklistId = dataMqn['checklistId'];
                mzSetFieldValue('MqnChecklistQuanNumb', dataMqn['checklistQuanNumb'], 'text');
                mzSetFieldValue('MqnChecklistQuanDesc', dataMqn['checklistQuanDesc'], 'textarea');
                mzSetFieldValue('MqnChecklistQuanUnit', dataMqn['checklistQuanUnit'], 'text');
                mzSetFieldValue('MqnFrequencyId', dataMqn['frequencyId'], 'select', 'Frequency *');
                mzSetFieldValue('MqnChecklistQuanStatus', dataMqn['checklistQuanStatus'], 'checkSingle', '1');

                $('#lblMqnTitle').html('<i class="far fa-edit text-white"></i> &nbsp;Edit Quanitative Task');
                $('#modal_checklist_quan').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_checklistQuanId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_checklistQuanId, _rowRefresh]);
                mzAjaxRequest('checklist_quan.php?checklistQuanId='+_checklistQuanId, 'PUT', {action: 'deactivate'});
                const tempRow = {checklistQuanStatus:'2'};
                if (classFrom.getClassName() === 'SectionChecklist') {
                    classFrom.updateTableSckChecklistQuan(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_checklistQuanId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_checklistQuanId, _rowRefresh]);
                mzAjaxRequest('checklist_quan.php?checklistQuanId='+_checklistQuanId, 'PUT', {action: 'activate'});
                const tempRow = {checklistQuanStatus:'1'};
                if (classFrom.getClassName() === 'SectionChecklist') {
                    classFrom.updateTableSckChecklistQuan(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_checklistQuanId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_checklistQuanId]);
                mzAjaxRequest('checklist_quan.php?checklistQuanId='+_checklistQuanId, 'DELETE');
                if (classFrom.getClassName() === 'SectionChecklist') {
                    classFrom.genTableSckChecklistQuan();
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