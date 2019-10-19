function ModalReportTotalWo() {

    const className = 'ModalAssetCategory';
    let self = this;
    let siteId;
    let rowRefresh = '';
    let selectedYear;
    let selectedMonth;
    let classFrom;

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
            }
        ];

        let formValidate = new MzValidate('formMmt');
        formValidate.registerFields(vData);

        $('#formMmt').on('keyup change', function () {
            $('#btnMmtSubmit').attr('disabled', !formValidate.validateForm());
        });
    };

    this.edit = function (_siteId, _rowRefresh, _selectedYear, _selectedMonth, _siteName) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteId, _rowRefresh, _selectedYear, _selectedMonth, _siteName]);
                rowRefresh = _rowRefresh;
                siteId = _siteId;
                selectedYear = _selectedYear;
                selectedMonth = _selectedMonth;

                mzSetFieldValue('MmtSiteName', _siteName, 'text');
                mzSetFieldValue('MmtYear', selectedYear, 'text');
                mzSetFieldValue('MmtMonth', selectedMonth, 'text');

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
}