function ModalPpmReschedule() {

    const className = 'ModalPpmReschedule';
    let self = this;
    let classFrom;
    let rowRefresh;
    let ppmTaskId;
    let ppmTaskStartDate;

    function pad2(n) {
        return (n < 10 ? '0' : '') + n;
    }

    function toYmd(dateStr) {
        if (!dateStr) {
            return '';
        }
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
            return dateStr;
        }
        const parts = String(dateStr).split('/');
        if (parts.length === 3) {
            return parts[0] + '-' + pad2(parseInt(parts[1], 10)) + '-' + pad2(parseInt(parts[2], 10));
        }
        return '';
    }

    function addDaysYmd(ymd, days) {
        const parts = ymd.split('-');
        const dt = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        dt.setDate(dt.getDate() + days);
        return dt.getFullYear() + '-' + pad2(dt.getMonth() + 1) + '-' + pad2(dt.getDate());
    }

    function setNativeDateBounds(minYmd, maxYmd) {
        const el = document.getElementById('txtMprNewDate');
        if (!el) {
            return;
        }
        if (minYmd) {
            el.min = minYmd;
        } else {
            el.removeAttribute('min');
        }
        if (maxYmd) {
            el.max = maxYmd;
        } else {
            el.removeAttribute('max');
        }
    }

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
            setNativeDateBounds('', '');
        });

        $('#optMprFrequency').on('change', function () {
            $('#txtMprNewDate').val('');
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

                let arrFrequency = [];
                for (let i = 0; i < frequencySplit.length; i++) {
                    if (frequencySplit[i] !== 'Daily') {
                        arrFrequency.push({frequencyId:frequencyIdsSplit[i], frequencyName:frequencySplit[i]});
                    }
                }
                mzOptionStop('optMprFrequency', arrFrequency, 'Choose Frequency', 'frequencyId', 'frequencyName', '', 'required', false);

                const startYmd = toYmd(ppmTaskStartDate);
                const maxYmd = toYmd(ppmTaskScheduleDate);
                let minYmd = startYmd ? addDaysYmd(startYmd, 1) : '';

                if (arrFrequency.length === 1) {
                    const frequencyId = arrFrequency[0]['frequencyId'];
                    mzSetFieldValue('MprFrequency', frequencyId, 'select', 'Frequency *');
                    const dateArr = ppmTaskScheduleDate.split('/');
                    const finalDate = new Date(parseInt(dateArr[0], 10), parseInt(dateArr[1], 10)-1, parseInt(dateArr[2], 10));
                    let minDate = finalDate;
                    if (frequencyId === '4') {
                        minDate.setDate(minDate.getDate() - 7);
                        minYmd = minDate.getFullYear() + '-' + pad2(minDate.getMonth() + 1) + '-' + pad2(minDate.getDate());
                    } else if (frequencyId === '3' || frequencyId === '2' || frequencyId === '1' || frequencyId === '6') {
                        minYmd = minDate.getFullYear() + '-' + pad2(minDate.getMonth() + 1) + '-01';
                    }
                    if (startYmd && minYmd && minYmd <= startYmd) {
                        minYmd = addDaysYmd(startYmd, 1);
                    }
                }

                setNativeDateBounds(minYmd, maxYmd);
                $('#txtMprNewDate').val('');

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
