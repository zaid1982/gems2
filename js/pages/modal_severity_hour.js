function ModalSeverityHour() {

    const className = 'ModalSeverityHour';
    let self = this;
    let classFrom;
    let rowRefresh;
    let clientId;
    let severityId;
    let refSeverity;

    this.init = function () {
        const vData = [
            {
                field_id: 'txtMvhHour',
                type: 'text',
                name: 'KPI Hour',
                validator: {
                    notEmpty: true,
                    min: 1,
                    max: 48,
                    digit: true
                }
            }
        ];

        let formValidate = new MzValidate('formMvh');
        formValidate.registerFields(vData);

        $('#formMvh').on('keyup change', function () {
            $('#btnMvhSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_severity_hour').on('hidden.bs.modal', function(){
            $('#btnMvhSubmit').attr('disabled', true);
            formValidate.clearValidation();
            mzDateEnable('txtMvhNewDate', ppmTaskStartDate);
        });

        $('#btnMvhSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    } else {
                        const data = {
                            action: 'update_severity_hour',
                            clientName: $('#txtMvhClient').val(),
                            severityId: severityId,
                            severityName: $('#txtMvhSeverity').val(),
                            severityHour: $('#txtMvhHour').val()
                        };
                        mzAjaxRequest('client.php?clientId=' + clientId, 'PUT', data);
                        if (classFrom.getClassName() === 'MainClient') {
                            classFrom.genTableCln();
                        }
                        $('#modal_severity_hour').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.edit = function (_clientId, _rowRefresh, _passParam) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_clientId, _rowRefresh, _passParam]);
                clientId = _clientId;
                rowRefresh = _rowRefresh;

                severityId = _passParam['severityId'];
                const clientName = _passParam['clientName'];
                const severityHour = _passParam['severityHour'];

                mzSetFieldValue('MvhClient', clientName, 'text');
                mzSetFieldValue('MvhSeverity', refSeverity[severityId]['severityName'], 'text');
                mzSetFieldValue('MvhHour', severityHour, 'text');

                $('#modal_severity_hour').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefSeverity = function (_refSeverity) {
        refSeverity = _refSeverity;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };
}