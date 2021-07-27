function SectionMonthlyElectricity () {

    const className = 'SectionMonthlyElectricity';
    let self = this;
    let classFrom;
    let monthlySummary;
    let refSite;
    let refUser;
    let oTableSme;

    this.init = function () {
        $('.sectionMonthlyElectricity').hide();

        $('#btnSmeBack').on('click', function () {
            $('.sectionMonthlyElectricity').hide();
            classFrom.showMain();
            $(window).scrollTop(0);
        });

        let exportOptSme = [];
        exportOptSme['columns'] = [0, 1, 2, 3, 4, 5, 6, 8];
        oTableSme = $('#dtSmeData').DataTable({
            bLengthChange: false,
            bFilter: false,
            ordering: false,
            language: _DATATABLE_LANGUAGE,
            pageLength: 50,
            autoWidth: false,
            dom: "<'row'<'col-12 px-0'B>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { className: 'text-center', targets: [1] },
                { className: 'text-right', targets: [2, 3, 4, 6] },
                { className: 'text-center font-weight-bold', targets: [7] },
                { visible: false, targets: [5, 6, 8] },
                { className: 'noVis', targets: [8] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Utility Daily Records - Electricity', titleAttr: 'Print', exportOptions: exportOptSme},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Utility Daily Records - Electricity', titleAttr: 'Copy', exportOptions: exportOptSme},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Utility Daily Records - Electricity', titleAttr: 'Excel', exportOptions: exportOptSme},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Utility Daily Records - Electricity', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOptSme}
            ],
            aoColumns: [
                {mData: 'utilityDate', width: '80px'},
                {mData: 'utilityMaxDemand', mRender: function (data){
                        return mzFormatNumber(data);
                    }},
                {mData: 'utilityOpening', mRender: function (data){
                        return mzFormatNumber(data);
                    }},
                {mData: 'utilityReading', mRender: function (data){
                        return mzFormatNumber(data);
                    }},
                {mData: 'utilityTotal', mRender: function (data){
                        return mzFormatNumber(data);
                    }},
                {mData: 'utilityRecordedBy', mRender: function (data){
                        return refUser[data]['userFirstName'];
                    }},
                {mData: 'utilityTimestamp'},
                {mData: 'changePerc', width: '75px', mRender: function (data){
                        const changes = parseFloat(data);
                        if (changes === 0) {
                            return '-';
                        } else if (changes > 0) {
                            return '<a class="text-danger"><i class="fas fa-arrow-up"></i> '+mzFormatNumber(changes,2)+'%</a>';
                        } else {
                            return '<a class="text-success"><i class="fas fa-arrow-down"></i> '+mzFormatNumber(changes*-1,2)+'%</a>';
                        }
                    }},
                {mData: null, mRender: function (data, type, row){
                        const changes = parseFloat(row['changePerc']);
                        if (changes === 0) {
                            return '-';
                        } else {
                            return mzFormatNumber(changes*-1,2)+'%';
                        }
                    }}
            ]
        });
    };

    this.load = function () {
        try {
            ShowLoader();
            setTimeout(function () {
                try {
                    $('#lblSmeMonthName').text(monthlySummary['utilityMonthName']);
                    $('#lblSmeSiteName').text(refSite[monthlySummary['siteId']]['siteName']);
                    const changes = parseFloat(monthlySummary['changePerc']);
                    if (changes === 0) {
                        $('#lblSmeChangePerc').html('');
                    } else if (changes > 0) {
                        $('#lblSmeChangePerc').html('<i class="fas fa-arrow-alt-circle-up"></i>  '+mzFormatNumber(changes,2)+'%');
                    } else {
                        $('#lblSmeChangePerc').html('<i class="fas fa-arrow-alt-circle-down"></i>  '+mzFormatNumber(changes*-1,2)+'%');
                    }
                    $('#lblSmeTotalMonthlyConsumption').text(mzFormatNumber(monthlySummary['utilityTotalKwh']));
                    $('#lblSmeUtilityTotalKwh').text(mzFormatNumber(monthlySummary['utilityTotalKwh']));
                    $('#lblSmeUtilityUsageRate').text(monthlySummary['utilityUsageRate']);
                    $('#lblSmeUtilityUsageRm').text(mzFormatNumber(monthlySummary['utilityUsageRm'],2));
                    $('#lblSmeCajSambunganBeban').text(mzFormatNumber(monthlySummary['cajSambunganBeban']));
                    $('#lblSmeCajSambunganBebanRate').text(monthlySummary['cajSambunganBebanRate']);
                    $('#lblSmeCajSambunganBebanRm').text(mzFormatNumber(monthlySummary['cajSambunganBebanRm'],2));
                    $('#lblSmeUtilityActualMaxDemand').text(mzFormatNumber(monthlySummary['utilityActualMaxDemand']));
                    $('#lblSmeUtilityMaxDemandRate').text(mzFormatNumber(monthlySummary['kwtbbRm'],2));
                    $('#lblSmeUtilityMaxDemandRm').text(mzFormatNumber(monthlySummary['utilityMaxDemandRate'],2));
                    $('#lblSmeElectricityAmountRm').text(mzFormatNumber(monthlySummary['utilityMaxDemandRm'],2));
                    $('#lblSmeKwtbbPerc').text(mzFormatNumber(monthlySummary['kwtbbRm']));
                    $('#lblSmeKwtbbRm').text(mzFormatNumber(monthlySummary['kwtbbPerc'],2));
                    $('#lblSmeElectricityBillRm').text(mzFormatNumber(monthlySummary['electricityBillRm'],2));
                    $('#lblSmeTotalMonthlyCharges').text(mzFormatNumber(monthlySummary['electricityBillRm'],2));

                    const dataDailyElectrics = mzAjaxRequest2('utility/data_daily_analyzed/Electricity/'+monthlySummary['siteId']+'/'+monthlySummary['utilityYear']+'/'+monthlySummary['utilityMonth'], 'GET');
                    oTableSme.clear().rows.add(dataDailyElectrics).draw();
                    self.generateChartKhw('chartSmeKwh', dataDailyElectrics);

                    $('.sectionMonthlyElectricity').show();
                    classFrom.hideMain();
                    window.scrollTo({top: 0, behavior: 'smooth'});
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        } catch (e) {
            throw new Error(e.message);
        }
    };

    this.generateChartKhw = function (chartId, dataSet) {
        let data = [];
        for (let i=0; i<dataSet.length; i++) {
            const dataDate = dataSet[i]['utilityDate'];
            const utilityTotalKwh = parseFloat(dataSet[i]['utilityTotal']);
            data.push({name:dataDate, y:utilityTotalKwh});
        }

        Highcharts.chart(chartId, {
            chart: {
                type: 'column',
                zoomType: 'x',
               /* backgroundColor: {
                    linearGradient: [0, 0, 1000, 1000],
                    stops: [
                        [0, 'rgb(255, 255, 255)'],
                        [1, 'rgb(204, 229, 255)']
                    ]
                }*/
            },
            title: {
                text: 'Electricity Consumption (kWh)'
            },
            subtitle: {
                text: monthlySummary['utilityMonthName']
            },
            xAxis: {
                type: 'category'
            },
            yAxis: {
                //min: 0,
                //max: 1500000,
                title: {
                    text: 'Electricity Consumption (kWh)'
                }
            },
            legend: {
                enabled: false
            },
            tooltip: {
                //xDateFormat: '%B %Y',
                //valueSuffix: ' % of best month',
                //pointFormat: 'Population : <b>{point.y:.1f} millions</b>'
            },
            plotOptions: {
                column: {
                    pointPadding: 0,
                    borderWidth: 0
                }
            },
            series: [{
                name: 'Electricity Consumption (kWh)',
                data: data
            }],
            credits: {
                enabled: false
            }
        });
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setMonthlySummary = function (_monthlySummary) {
        monthlySummary = _monthlySummary;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };
}