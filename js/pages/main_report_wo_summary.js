function MainReportWoSummary() {

    const className = 'MainReportWoSummary';
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
    let lastUpdated;

    this.init = function () {
        mzOption('optRwsClientId', refClient, 'Choose Client', 'clientId', 'clientName', {}, 'required');
        //mzOption('optRwsSiteId', refSite, 'Choose Site', 'siteId', 'siteDesc', {clientId: '1', siteStatus: '1'}, 'required');

        clientId = '1';
        $('#optRwsClientId').val(clientId);

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
            fnRowCallback: function (nRow) {
                const columnLabels = getColumnLabels();
                $('td', nRow).each(function (idx) {
                    if (columnLabels[idx]) {
                        $(this).attr('data-label', columnLabels[idx]);
                    } else {
                        $(this).removeAttr('data-label');
                    }
                });
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                updateSummary();
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: 'woTaskType',  mRender: function (data) { return data === 'TOTAL' ? '<strong>'+data+'</strong>' : data}},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (siteColumns.length > 0 && typeof row['open'+siteColumns[0]['siteId']] !== 'undefined') {
                                const val = row['open'+siteColumns[0]['siteId']];
                                if (row['woTaskType'] === 'TOTAL') {
                                    return '<strong>'+mzFormatNumber(val)+'</strong>';
                                } else {
                                    return mzFormatNumber(val);
                                }
                            }
                            return '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (siteColumns.length > 0 && typeof row['closed'+siteColumns[0]['siteId']] !== 'undefined') {
                                const val = row['closed'+siteColumns[0]['siteId']];
                                if (row['woTaskType'] === 'TOTAL') {
                                    return '<strong>'+mzFormatNumber(val)+'</strong>';
                                } else {
                                    return mzFormatNumber(val);
                                }
                            }
                            return '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (siteColumns.length > 0 && typeof siteColumns[1] !== 'undefined') {
                                const val = row['open'+siteColumns[1]['siteId']];
                                if (row['woTaskType'] === 'TOTAL') {
                                    return '<strong>'+mzFormatNumber(val)+'</strong>';
                                } else {
                                    return mzFormatNumber(val);
                                }
                            }
                            return '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (siteColumns.length > 0 && typeof siteColumns[1] !== 'undefined') {
                                const val = row['closed'+siteColumns[1]['siteId']];
                                if (row['woTaskType'] === 'TOTAL') {
                                    return '<strong>'+mzFormatNumber(val)+'</strong>';
                                } else {
                                    return mzFormatNumber(val);
                                }
                            }
                            return '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (siteColumns.length > 0 && typeof siteColumns[2] !== 'undefined') {
                                const val = row['open'+siteColumns[2]['siteId']];
                                if (row['woTaskType'] === 'TOTAL') {
                                    return '<strong>'+mzFormatNumber(val)+'</strong>';
                                } else {
                                    return mzFormatNumber(val);
                                }
                            }
                            return '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (siteColumns.length > 0 && typeof siteColumns[2] !== 'undefined') {
                                const val = row['closed'+siteColumns[2]['siteId']];
                                if (row['woTaskType'] === 'TOTAL') {
                                    return '<strong>'+mzFormatNumber(val)+'</strong>';
                                } else {
                                    return mzFormatNumber(val);
                                }
                            }
                            return '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (siteColumns.length > 0 && typeof siteColumns[3] !== 'undefined') {
                                const val = row['open'+siteColumns[3]['siteId']];
                                if (row['woTaskType'] === 'TOTAL') {
                                    return '<strong>'+mzFormatNumber(val)+'</strong>';
                                } else {
                                    return mzFormatNumber(val);
                                }
                            }
                            return '';
                        }},
                    {mData: null, sClass: 'text-right',
                        mRender: function (data, type, row) {
                            if (siteColumns.length > 0 && typeof siteColumns[3] !== 'undefined') {
                                const val = row['closed'+siteColumns[3]['siteId']];
                                if (row['woTaskType'] === 'TOTAL') {
                                    return '<strong>'+mzFormatNumber(val)+'</strong>';
                                } else {
                                    return mzFormatNumber(val);
                                }
                            }
                            return '';
                        }}
                ]
        });
        $("#dtRwsWoSummary_filter").hide();

        let cntWoSummary;
        let btnWoSummaryOpt = {
            exportOptions: {
            }
        };

        new $.fn.dataTable.Buttons(oTableWoSummary, {
            buttons: [
                $.extend( true, {}, btnWoSummaryOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print text-dark"></i>',
                    title:     'GEMS 2.0 - Work Order Summary',
                    titleAttr: 'Print',
                    className: 'btn btn-light btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnWoSummaryOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel text-dark"></i>',
                    title:     'GEMS 2.0 - Work Order Summary',
                    titleAttr: 'Excel',
                    className: 'btn btn-light btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnWoSummaryOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf text-dark"></i>',
                    title:     'GEMS 2.0 - Work Order Summary',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-light btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtRwsWoSummaryExport'));

        $('#btnDtRwsWoSummaryRefresh').on('click', function () {
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

        $('#btnRwsSearch').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    clientId = $('#optRwsClientId').val();
                    selectedYear = $('#optRwsYearId').val();
                    selectedMonth = $('#optRwsMonthId').val();
                    self.genTableWoSummary();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#txtRwsSearch').on('keyup change', function () {
            const term = $(this).val();
            oTableWoSummary.search(term);
            oTableWoSummary.draw();
        });

        self.genTableWoSummary();
    };

    this.genTableWoSummary = function () {
        siteColumns = [];
        for (let i = 1; i <= 8; i++) {
            oTableWoSummary.column(i).visible(false);
        }
        for (let j = 0; j < 4; j++) {
            $('#thRwsSiteDesc'+j).html('');
        }
        $.each(refSite, function (n, u) {
            if (typeof u !== 'undefined' && u['siteStatus'] !== '2' && u['clientId'] === clientId) {
                siteColumns.push(u);
            }
        });
        $.each(siteColumns, function (n, u) {
            oTableWoSummary.column(n*2+1).visible(true);
            oTableWoSummary.column(n*2+2).visible(true);
            $('#thRwsSiteDesc'+n).html(u['siteDesc']);
        });

        const dataWoSummary = mzAjaxRequest('wo.php?type=report_wo_summary&clientId='+clientId+'&year='+selectedYear+'&month='+selectedMonth, 'GET');
        oTableWoSummary.clear().rows.add(dataWoSummary).draw();
        updateMetrics(dataWoSummary);
        lastUpdated = moment();
        updateSummary();
    };

    function getColumnLabels() {
        const labels = ['Work Type'];
        $.each(siteColumns, function (idx, site) {
            labels.push(site['siteDesc'] + ' Open');
            labels.push(site['siteDesc'] + ' Closed');
        });
        while (labels.length < 9) {
            labels.push('');
        }
        return labels;
    }

    function updateMetrics(data) {
        let totalOpen = 0;
        let totalClosed = 0;

        $.each(data, function (idx, row) {
            if (row['woTaskType'] === 'TOTAL') {
                $.each(siteColumns, function (i, site) {
                    const openKey = 'open' + site['siteId'];
                    const closedKey = 'closed' + site['siteId'];
                    totalOpen += parseFloat(row[openKey]) || 0;
                    totalClosed += parseFloat(row[closedKey]) || 0;
                });
            }
        });

        $('#metricTotalOpen').text(mzFormatNumber(totalOpen));
        $('#metricTotalClosed').text(mzFormatNumber(totalClosed));

        if (selectedYear && selectedMonth) {
            const monthText = moment({ year: parseInt(selectedYear, 10), month: parseInt(selectedMonth, 10) - 1, day: 1 }).format('MMM YYYY');
            $('#metricMonthLabel').text(monthText);
        } else {
            $('#metricMonthLabel').text('-');
        }

        $('#metricSiteCount').text(siteColumns.length);
    }

    function updateSummary() {
        if (!oTableWoSummary) {
            return;
        }
    const allRows = oTableWoSummary.rows().data().toArray();
    const visibleRows = oTableWoSummary.rows({ search: 'applied' }).data().toArray();
    const total = allRows.filter(function (row) { return row && row['woTaskType'] !== 'TOTAL'; }).length;
    const filtered = visibleRows.filter(function (row) { return row && row['woTaskType'] !== 'TOTAL'; }).length;
    $('#lblRwsCount').text('Showing ' + filtered + ' of ' + total + ' work types');
        if (lastUpdated) {
            $('#lblRwsUpdated').text('Updated ' + lastUpdated.format('DD MMM YYYY, HH:mm'));
        }
    }

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