function SectionMonthlyWater () {

    const className = 'SectionMonthlyWater';
    let self = this;
    let classFrom;
    let monthlySummary;
    let refSite;
    let refUser;
    let oTableSmw;

    this.init = function () {
        $('.sectionMonthlyWater').hide();

        $('#btnSmwBack').on('click', function () {
            $('.sectionMonthlyWater').hide();
            classFrom.showMain();
            $(window).scrollTop(0);
        });

        let exportOptSmw = [];
        exportOptSmw['columns'] = [0, 1, 2, 3, 4, 5, 6, 7, 9];
        oTableSmw = $('#dtSmwData').DataTable({
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
                { className: 'text-center', targets: [0] },
                { className: 'text-right', targets: [1, 2, 3, 4, 5, 6] },
                { className: 'text-right font-weight-bold', targets: [7] },
                { className: 'text-center font-weight-bold', targets: [8] },
                { visible: false, targets: [9] },
                { className: 'noVis', targets: [9] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Utility Daily Records - Electricity', titleAttr: 'Print', exportOptions: exportOptSmw},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Utility Daily Records - Electricity', titleAttr: 'Copy', exportOptions: exportOptSmw},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Utility Daily Records - Electricity', titleAttr: 'Excel', exportOptions: exportOptSmw},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Utility Daily Records - Electricity', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOptSmw}
            ],
            aoColumns: [
                {mData: 'utilityDate', width: '80px'},
                {mData: 'readingMorning', mRender: function (data){
                        return mzFormatNumber(data, 2);
                    }},
                {mData: 'totalMorning', mRender: function (data){
                        return mzFormatNumber(data, 2);
                    }},
                {mData: 'readingEvening', mRender: function (data){
                        return mzFormatNumber(data, 2);
                    }},
                {mData: 'totalEvening', mRender: function (data){
                        return mzFormatNumber(data, 2);
                    }},
                {mData: 'readingNight', mRender: function (data){
                        return mzFormatNumber(data, 2);
                    }},
                {mData: 'totalNight', mRender: function (data){
                        return mzFormatNumber(data, 2);
                    }},
                {mData: 'totalDaily', mRender: function (data){
                        return mzFormatNumber(data, 2);
                    }},
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
                    console.log(monthlySummary);
                    $('#lblSmwMonthName').text(monthlySummary['utilityMonthName']);
                    $('#lblSmwSiteName').text(refSite[monthlySummary['siteId']]['siteName']);
                    const changes = parseFloat(monthlySummary['changePerc']);
                    if (changes === 0) {
                        $('#lblSmwChangePerc').html('');
                    } else if (changes > 0) {
                        $('#lblSmwChangePerc').html('<i class="fas fa-arrow-alt-circle-up"></i>  '+mzFormatNumber(changes,2)+'%');
                    } else {
                        $('#lblSmwChangePerc').html('<i class="fas fa-arrow-alt-circle-down"></i>  '+mzFormatNumber(changes*-1,2)+'%');
                    }
                    $('#lblSmwTotalMonthlyConsumption').text(mzFormatNumber(monthlySummary['utilityTotalUsage']));
                    $('#lblSmwTotalMonthlyCharges').text(mzFormatNumber(monthlySummary['utilityTotalUsageRm'],2));

                    const dataDailyWater = mzAjaxRequest2('utility/data_daily_analyzed/Water/'+monthlySummary['siteId']+'/'+monthlySummary['utilityYear']+'/'+monthlySummary['utilityMonth'], 'GET');
                    console.log(dataDailyWater);
                    oTableSmw.clear().rows.add(dataDailyWater).draw();
                    self.generateChartM3('chartSmwM3', dataDailyWater);

                    $('.sectionMonthlyWater').show();
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

    this.generateChartM3 = function (chartId, dataSet) {
        let categories = [];
        let dataMorning = [];
        let dataEvening = [];
        let dataNight = [];
        for (let i=0; i<dataSet.length; i++) {
            const dataDate = dataSet[i]['utilityDate'];
            categories.push(dataDate);
            dataMorning.push(parseFloat(dataSet[i]['totalMorning']))
            dataEvening.push(parseFloat(dataSet[i]['totalEvening']))
            dataNight.push(parseFloat(dataSet[i]['totalNight']))
        }
        const data = [
            {name:'Morning Shift', data:dataMorning, color: Highcharts.getOptions().colors[0]},
            {name:'Evening Shift', data:dataEvening, color: Highcharts.getOptions().colors[3]},
            {name:'Night Shift', data:dataNight, color: Highcharts.getOptions().colors[1]}
        ];

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
                text: 'Daily Water Consumption (m3)'
            },
            subtitle: {
                text: monthlySummary['utilityMonthName']
            },
            xAxis: {
                categories: categories
            },
            yAxis: {
                //min: 0,
                //max: 1500000,
                title: {
                    text: 'Water Consumption (m3)'
                }
            },
            legend: {
                align: 'right',
                x: -30,
                verticalAlign: 'top',
                y: 55,
                floating: true,
                backgroundColor:
                    Highcharts.defaultOptions.legend.backgroundColor || 'white',
                borderColor: '#CCC',
                borderWidth: 1,
                shadow: false
            },
            tooltip: {
                //xDateFormat: '%B %Y',
                //valueSuffix: ' % of best month',
                //pointFormat: 'Population : <b>{point.y:.1f} millions</b>'
                headerFormat: '<b>{point.x}</b><br/>',
                pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
            },
            plotOptions: {
                column: {
                    stacking: 'normal',
                    pointPadding: 0,
                    borderWidth: 0,
                    dataLabels: {
                        enabled: false
                    }
                }
            },
            series: data,
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