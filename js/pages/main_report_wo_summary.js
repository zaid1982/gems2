function MainReportWoSummary() {

    const className = 'MainTrackMonitoring';
    let self = this;
    let refStatus;
    let refClient;
    let refSite;
    const yearArr = mzGetYearArray();
    const monthArr = mzGetMonthArray();

    this.init = function () {
        mzOption('optRwsClientId', refClient, 'Choose Client', 'clientId', 'clientName', {}, 'required');
        mzOption('optRwsSiteId', refSite, 'Choose Site', 'siteId', 'siteDesc', {clientId: '1', siteStatus: '1'}, 'required');

        $('#optRwsClientId').val('1');
        $('#optRwsSiteId').val('1');

        let dateCurrent = new Date();
        const currentMonth = dateCurrent.getMonth();
        const currentYear = dateCurrent.getFullYear();

        mzOption('optRwsYearId', yearArr, 'Choose Year', 'yearId', 'yearName', {}, 'required', false);
        $('#optRwsYearId').val(currentYear);

        mzOption('optRwsMonthId', monthArr, 'Choose Month', 'monthId', 'monthName', {}, 'required', false);
        $('#optRwsMonthId').val(currentMonth+1);
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };
}