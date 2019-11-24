function ModalPpmReschedule() {

    const className = 'ModalPpmReschedule';
    let self = this;
    let classFrom;
    let rowRefresh;
    let ppmTaskId;
    let ppmTaskStartDate;

    this.init = function () {
        const vData = [
            {
                field_id: 'optMprFrequency',
                type: 'select',
                name: 'Frequency',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMprNewDate',
                type: 'text',
                name: 'New Scheduled Date',
                validator: {
                    notEmpty: true
                }
            }
        ];

        let formValidate = new MzValidate('formMpr');
        formValidate.registerFields(vData);

        $('#formMpr').on('keyup change', function () {
            $('#btnMprSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_ppm_reschedule').on('hidden.bs.modal', function(){
            $('#btnMprSubmit').attr('disabled', true);
            formValidate.clearValidation();
            mzDateEnable('txtMprNewDate', ppmTaskStartDate);
        });

        $('#optMprFrequency').on('change', function () {
            $('#txtMprNewDate').val('');
            $('#lblMprNewDate').removeClass('active');
        });

        $('#btnMprSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    } else {
                        const data = {
                            action: 'reschedule_date',
                            frequency: $('#optMprFrequency').val(),
                            newDate: mzConvertDate($('#txtMprNewDate').val())
                        };
                        mzAjaxRequest('ppm.php?ppmTaskId=' + ppmTaskId, 'PUT', data);
                        if (classFrom.getClassName() === 'MainPpmReschedule') {
                            classFrom.genTablePrsPpm();
                        }
                        $('#modal_ppm_reschedule').modal('hide');
                    }
                } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 300);
        });
    };

    this.edit = function (_ppmTaskId, _rowRefresh, _passParam) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_ppmTaskId, _rowRefresh, _passParam]);
                ppmTaskId = _ppmTaskId;
                rowRefresh = _rowRefresh;

                ppmTaskStartDate = _passParam['ppmTaskStartDate'];
                const ppmTaskScheduleDate = _passParam['ppmTaskScheduleDate'];
                const frequency = _passParam['frequency'];
                const frequencyIds = _passParam['frequencyIds'];
                const frequencySplit = frequency.split(', ');
                const frequencyIdsSplit = frequencyIds.split(',');

                mzSetFieldValue('MprPpmTaskNo', _passParam['ppmTaskNo'], 'text');
                mzSetFieldValue('MprPpmTaskStartDate', ppmTaskStartDate, 'text');
                mzSetFieldValue('MprPpmTaskScheduleDate', ppmTaskScheduleDate, 'text');
                //const dataMzc = mzAjaxRequest('asset_category.php?assetCategoryId='+assetCategoryId, 'GET');
                //mzSetFieldValue('MzcName', dataMzc['assetCategoryName'], 'text');

                let arrFrequency = [];
                for (let i = 0; i < frequencySplit.length; i++) {
                    if (frequencySplit[i] !== 'Daily') {
                        arrFrequency.push({frequencyId:frequencyIdsSplit[i], frequencyName:frequencySplit[i]});
                    }
                }
                mzOptionStop('optMprFrequency', arrFrequency, 'Choose Frequency', 'frequencyId', 'frequencyName', '', 'required', false);
                mzDateSetMax('txtMprNewDate', ppmTaskScheduleDate);
                mzDateDisable('txtMprNewDate', ppmTaskStartDate);

                if (arrFrequency.length === 1) {
                    const frequencyId = arrFrequency[0]['frequencyId'];
                    mzSetFieldValue('MprFrequency', frequencyId, 'select', 'Frequency *');
                    const dateArr = ppmTaskScheduleDate.split('/');
                    const finalDate = new Date(parseInt(dateArr[0]), parseInt(dateArr[1])-1, parseInt(dateArr[2]));
                    let minDate = finalDate;
                    if (frequencyId === '4') {  // weekly
                        minDate.setDate(minDate.getDate() - 7);
                        mzDateSetMin('txtMprNewDate', minDate.getFullYear()+'/'+(minDate.getMonth()+1)+'/'+minDate.getDate());
                    }
                    else if (frequencyId === '3' || frequencyId === '2' || frequencyId === '1' || frequencyId === '6') {  // monthly
                        mzDateSetMin('txtMprNewDate', minDate.getFullYear()+'/'+(minDate.getMonth()+1)+'/1');
                    }
                }

                $('#modal_ppm_reschedule').modal({backdrop: 'static', keyboard: false});
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
}