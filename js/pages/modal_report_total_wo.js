function ModalReportTotalWo() {

    const className = 'ModalAssetCategory';
    let self = this;
    let siteId;
    let rowRefresh = '';
    let selectedYear;
    let selectedMonth;
    let classFrom;

    this.init = function () {

    };

    this.edit = function (_siteId, _rowRefresh, _selectedYear, _selectedMonth) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteId, _rowRefresh, _selectedYear, _selectedMonth]);
                rowRefresh = _rowRefresh;
                siteId = _siteId;
                selectedYear = _selectedYear;
                selectedMonth = _selectedMonth;

                mzSetFieldValue('MmtSiteName', dataMzc['assetCategoryName'], 'text');

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