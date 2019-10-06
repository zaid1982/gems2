function MainReportWoSummary() {

    const className = 'MainTrackMonitoring';
    let self = this;
    let refStatus;
    let refClient;
    let refSite;
    const yearArr = mzGetYearArray();
    const monthArr = mzGetMonthArray();
    let oTableWoSummary;
    let clientId;
    let selectedYear;
    let selectedMonth;
    let siteColumns = [];

    this.init = function () {
        mzOption('optRwsClientId', refClient, 'Choose Client', 'clientId', 'clientName', {}, 'required');
        //mzOption('optRwsSiteId', refSite, 'Choose Site', 'siteId', 'siteDesc', {clientId: '1', siteStatus: '1'}, 'required');

        $('#optRwsClientId').val('1');
        $('#optRwsSiteId').val('1');

        let dateCurrent = new Date();
        selectedMonth = dateCurrent.getMonth()+1;
        selectedYear = dateCurrent.getFullYear();

        mzOption('optRwsYearId', yearArr, 'Choose Year', 'yearId', 'yearName', {}, 'required', false);
        $('#optRwsYearId').val(selectedYear);

        mzOption('optRwsMonthId', monthArr, 'Choose Month', 'monthId', 'monthName', {}, 'required', false);
        $('#optRwsMonthId').val(selectedMonth);

        const vData = [
            {
                field_id: 'optRwsClientId',
                type: 'select',
                name: 'Client',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optRwsMonthId',
                type: 'select',
                name: 'Year',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optRwsYearId',
                type: 'select',
                name: 'Month',
                validator: {
                    notEmpty: true
                }
            }
        ];

        let formValidate = new MzValidate('formRwsSearch');
        formValidate.registerFields(vData);

        $('#btnRwsSearch').attr('disabled', !formValidate.validateForm());
        $('#formRwsSearch').on('keyup change', function () {
            $('#btnRwsSearch').attr('disabled', !formValidate.validateForm());
        });

        oTableWoSummary = $('#dtRwsWoSummary').DataTable({
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            bPaginate: false,
            autoWidth: false,
            ordering: false,
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: 'woTaskType'},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return siteColumns.length > 0 ? mzFormatNumber(row['open'+siteColumns[0]['siteId']]) : '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return siteColumns.length > 0 ? mzFormatNumber(row['close'+siteColumns[0]['siteId']]) : '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return siteColumns.length > 1 ? mzFormatNumber(row['open'+siteColumns[1]['siteId']]) : '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return siteColumns.length > 1 ? mzFormatNumber(row['close'+siteColumns[1]['siteId']]) : '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return siteColumns.length > 2 ? mzFormatNumber(row['open'+siteColumns[2]['siteId']]) : '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return siteColumns.length > 2 ? mzFormatNumber(row['close'+siteColumns[2]['siteId']]) : '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return siteColumns.length > 3 ? mzFormatNumber(row['open'+siteColumns[3]['siteId']]) : '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            return siteColumns.length > 3 ? mzFormatNumber(row['close'+siteColumns[3]['siteId']]) : '';
                        }},
                ]
        });
        $("#dtRwsWoSummary_filter").hide();

        $('#btnRwsSearch').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableWoSummary();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        self.genTableWoSummary();
    };

    this.genTableWoSummary = function () {
        const dataWoSummary = mzAjaxRequest('wo.php?type=report_wo_summary&clientId='+clientId+'&year='+selectedYear+'&month='+selectedMonth, 'GET');
        /*const dataWoSummary = [
            {siteId:'22132', open1:'121', close1:'12', open2:'221', close2:'22', open3:'321', close3:'32', open4:'421', close4:'42'},
            {siteId:'22132', open1:'121', close1:'12', open2:'221', close2:'22', open3:'321', close3:'32', open4:'421', close4:'42'},
            {siteId:'22132', open1:'121', close1:'12', open2:'221', close2:'22', open3:'321', close3:'32', open4:'421', close4:'42'},
            {siteId:'22132', open1:'121', close1:'12', open2:'221', close2:'22', open3:'321', close3:'32', open4:'421', close4:'42'}
        ];*/
        siteColumns = [];
        $.each(refSite, function (n, u) {
            if (typeof u !== 'undefined' && u['siteStatus'] !== '2' && u['clientId'] !== clientId) {
                siteColumns.push(u);
            }
        });
        $.each(siteColumns, function (n, u) {
            $('#thRwsSiteDesc'+n).html(u['siteDesc']);
        });
        oTableWoSummary.clear().rows.add(dataWoSummary).draw();
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