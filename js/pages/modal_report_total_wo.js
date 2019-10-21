function ModalReportTotalWo() {

    const className = 'ModalAssetCategory';
    let self = this;
    let siteManualId;
    let siteId;
    let siteName;
    let rowRefresh = '';
    let selectedYear;
    let selectedMonth;
    let selectedDate;
    let classFrom;
    let formValidate;
    let editData = [];

    this.init = function () {
        const vData = [
            {
                field_id: 'txtMmtSiteName',
                type: 'text',
                name: 'Site Name',
                validator: {
                }
            },
            {
                field_id: 'txtMmtYear',
                type: 'text',
                name: 'Year',
                validator: {
                }
            },
            {
                field_id: 'txtMmtMonth',
                type: 'text',
                name: 'Month',
                validator: {
                }
            },
            {
                field_id: 'txtMmtDate',
                type: 'text',
                name: 'Date',
                validator: {
                    notEmpty: true,
                    numeric: true,
                    min: 1,
                    max: 31
                }
            },
            {
                field_id: 'txtMmtOpen0',
                type: 'text',
                name: 'Month',
                validator: {
                    notEmpty: true,
                    numeric: true,
                    min: 0,
                    max: 999999
                }
            },
            {
                field_id: 'txtMmtClosed0',
                type: 'text',
                name: 'Month',
                validator: {
                    notEmpty: true,
                    numeric: true,
                    min: 0,
                    max: 999999
                }
            },
            {
                field_id: 'txtMmtOpen1',
                type: 'text',
                name: 'Month',
                validator: {
                    notEmpty: true,
                    numeric: true,
                    min: 0,
                    max: 999999
                }
            },
            {
                field_id: 'txtMmtClosed1',
                type: 'text',
                name: 'Month',
                validator: {
                    notEmpty: true,
                    numeric: true,
                    min: 0,
                    max: 999999
                }
            },
            {
                field_id: 'txtMmtOpen2',
                type: 'text',
                name: 'Month',
                validator: {
                    notEmpty: true,
                    numeric: true,
                    min: 0,
                    max: 999999
                }
            },
            {
                field_id: 'txtMmtClosed2',
                type: 'text',
                name: 'Month',
                validator: {
                    notEmpty: true,
                    numeric: true,
                    min: 0,
                    max: 999999
                }
            },
            {
                field_id: 'txtMmtOpen3',
                type: 'text',
                name: 'Month',
                validator: {
                    notEmpty: true,
                    numeric: true,
                    min: 0,
                    max: 999999
                }
            },
            {
                field_id: 'txtMmtClosed3',
                type: 'text',
                name: 'Month',
                validator: {
                    notEmpty: true,
                    numeric: true,
                    min: 0,
                    max: 999999
                }
            },
            {
                field_id: 'txtMmtOpen4',
                type: 'text',
                name: 'Month',
                validator: {
                    notEmpty: true,
                    numeric: true,
                    min: 0,
                    max: 999999
                }
            },
            {
                field_id: 'txtMmtClosed4',
                type: 'text',
                name: 'Month',
                validator: {
                    notEmpty: true,
                    numeric: true,
                    min: 0,
                    max: 999999
                }
            },
            {
                field_id: 'txtMmtOpen5',
                type: 'text',
                name: 'Month',
                validator: {
                    notEmpty: true,
                    numeric: true,
                    min: 0,
                    max: 999999
                }
            },
            {
                field_id: 'txtMmtClosed5',
                type: 'text',
                name: 'Month',
                validator: {
                    notEmpty: true,
                    numeric: true,
                    min: 0,
                    max: 999999
                }
            }
        ];

        formValidate = new MzValidate('formMmt');
        formValidate.registerFields(vData);

        $('#formMmt').on('keyup change', function () {
            $('#btnMmtSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_report_total_wo').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMmtSubmit').attr('disabled', true);
        });

        $('#btnMmtSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const data = {
                            siteManualId: siteManualId,
                            siteId: siteId,
                            selectedYear: selectedYear,
                            selectedMonth: selectedMonth,
                            selectedDate: $('#txtMmtDate').val(),
                            siteName: siteName,
                            open0: $('#txtMmtOpen0').val(),
                            closed0: $('#txtMmtClosed0').val(),
                            open1: $('#txtMmtOpen1').val(),
                            closed1: $('#txtMmtClosed1').val(),
                            open2: $('#txtMmtOpen2').val(),
                            closed2: $('#txtMmtClosed2').val(),
                            open3: $('#txtMmtOpen3').val(),
                            closed3: $('#txtMmtClosed3').val(),
                            open4: $('#txtMmtOpen4').val(),
                            closed4: $('#txtMmtClosed4').val(),
                            open5: $('#txtMmtOpen5').val(),
                            closed5: $('#txtMmtClosed5').val()
                        };

                        if (siteManualId === '') {
                            data['action'] = 'insert_site_manual';
                            mzAjaxRequest('wo.php', 'POST', data);
                            if (classFrom.getClassName() === 'MainReportWoTotal') {
                                classFrom.genTableWoDaily();
                                classFrom.genTableWoTotal();
                            }
                        } else {
                            data['action'] = 'update_site_manual';
                            mzAjaxRequest('wo.php?siteManualId='+siteManualId, 'PUT', data);
                            if (classFrom.getClassName() === 'MainReportWoTotal') {
                                classFrom.genTableWoDaily();
                                classFrom.genTableWoTotal();
                            }
                        }
                        $('#modal_report_total_wo').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.add = function (_siteId, _selectedYear, _selectedMonth, _siteName) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteId, _selectedYear, _selectedMonth, _siteName]);
                siteManualId = '';
                siteId = _siteId;
                selectedYear = _selectedYear;
                selectedMonth = _selectedMonth;
                selectedDate = '';
                siteName = _siteName;

                mzSetFieldValue('MmtSiteName', _siteName, 'text');
                mzSetFieldValue('MmtYear', selectedYear, 'text');
                mzSetFieldValue('MmtMonth', selectedMonth, 'text');

                $('#txtMmtDate').prop('disabled', false);
                formValidate.enableField('txtMmtDate');

                $('#lblMmtTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add Daily Manual WO Report');
                $('#modal_report_total_wo').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.edit = function (_siteManualId, _siteId, _rowRefresh, _selectedYear, _selectedMonth, _siteName, _selectedDate) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteManualId, _siteId, _rowRefresh, _selectedYear, _selectedMonth, _siteName, _selectedDate]);
                siteManualId = _siteManualId;
                rowRefresh = _rowRefresh;
                siteId = _siteId;
                selectedYear = _selectedYear;
                selectedMonth = _selectedMonth;
                selectedDate = _selectedDate;
                siteName = _siteName;

                mzSetFieldValue('MmtSiteName', _siteName, 'text');
                mzSetFieldValue('MmtYear', selectedYear, 'text');
                mzSetFieldValue('MmtMonth', selectedMonth, 'text');
                mzSetFieldValue('MmtDate', selectedDate, 'text');
                mzSetFieldValue('MmtOpen0', editData[0], 'text');
                mzSetFieldValue('MmtClosed0', editData[1], 'text');
                mzSetFieldValue('MmtOpen1', editData[2], 'text');
                mzSetFieldValue('MmtClosed1', editData[3], 'text');
                mzSetFieldValue('MmtOpen2', editData[4], 'text');
                mzSetFieldValue('MmtClosed2', editData[5], 'text');
                mzSetFieldValue('MmtOpen3', editData[6], 'text');
                mzSetFieldValue('MmtClosed3', editData[7], 'text');
                mzSetFieldValue('MmtOpen4', editData[8], 'text');
                mzSetFieldValue('MmtClosed4', editData[9], 'text');
                mzSetFieldValue('MmtOpen5', editData[10], 'text');
                mzSetFieldValue('MmtClosed5', editData[11], 'text');

                $('#txtMmtDate').prop('disabled', true);
                formValidate.disableField('txtMmtDate');

                $('#lblMmtTitle').html('<i class="fas fa-edit text-white"></i> &nbsp;Edit Manual WO Report');
                $('#modal_report_total_wo').modal({backdrop: 'static', keyboard: false});
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

    this.setEditData = function (_editData) {
        editData = _editData;
    };
}