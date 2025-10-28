function MainUtility () {

    const className = 'MainStoreManagement';
    let self = this;
    let oTableUtmMonthly;
    let oTableUtmMonthlyWater;
    let sectionMonthlyElectricityClass;
    let sectionMonthlyWaterClass;
    let refSite;

    this.init = function () {
        let exportOptUtmMonthly = Object.assign({}, mzExportOpt);
        exportOptUtmMonthly['columns'] = [0, 1, 2, 3, 4, 5, 7];

        $('#btnUtmRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.generateElectricityMonthly();
                    self.generateWaterMonthly();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 100);
        });
        oTableUtmMonthly = $('#dtUtmMonthlyData').DataTable({
            bLengthChange: false,
            bFilter: false,
            //ordering: false,
            language: _DATATABLE_LANGUAGE,
            pageLength: 50,
            autoWidth: false,
            aaSorting: [[8, 'desc'], [9, 'desc']],
            dom: "<'row'<'col-12 px-0'B>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 1, 2, 3, 4, 5, 6, 7] },
                { className: 'text-right', targets: [1, 2, 3, 4, 5] },
                { className: 'text-center font-weight-bold', targets: [6] },
                { visible: false, targets: [3, 7, 8, 9] },
                { className: 'noVis', targets: [7, 8, 9] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Utility Monthly Records - Electricity', titleAttr: 'Print', exportOptions: exportOptUtmMonthly},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Utility Monthly Records - Electricity', titleAttr: 'Copy', exportOptions: exportOptUtmMonthly},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Utility Monthly Records - Electricity', titleAttr: 'Excel', exportOptions: exportOptUtmMonthly},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Utility Monthly Records - Electricity', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOptUtmMonthly}
            ],
            aoColumns: [
                {mData: 'utilityMonthName'},
                {mData: 'utilityTotalKwh', mRender: function (data){
                        return mzFormatNumber(data);
                    }},
                {mData: 'utilityActualMaxDemand', mRender: function (data){
                        return mzFormatNumber(data);
                    }},
                {mData: 'utilityMaxDemand', mRender: function (data){
                        return mzFormatNumber(data);
                    }},
                {mData: 'cajSambunganBeban', width: '15%', mRender: function (data){
                        return mzFormatNumber(data);
                    }},
                {mData: 'electricityBillRm', mRender: function (data){
                        return mzFormatNumber(data,2);
                    }},
                {mData: 'changePerc', width: '65px', mRender: function (data){
                        const changes = parseFloat(data);
                        if (changes === 0) {
                            return '-';
                        } else if (changes > 0) {
                            return '<a class="text-success"><i class="fas fa-arrow-down"></i> '+mzFormatNumber(changes,2)+'%</a>';
                        } else {
                            return '<a class="text-danger"><i class="fas fa-arrow-up"></i> '+mzFormatNumber(changes*-1,2)+'%</a>';
                        }
                    }},
                {mData: null, mRender: function (data, type, row){
                        const changes = parseFloat(row['changePerc']);
                        if (changes === 0) {
                            return '-';
                        } else {
                            return mzFormatNumber(changes*-1,2)+'%';
                        }
                    }},
                {mData: 'utilityYear'},
                {mData: 'utilityMonth'}
            ]
        });
        let oTableUtmMonthlyTbody = $('#dtUtmMonthlyData tbody');
        oTableUtmMonthlyTbody.delegate('tr', 'click', function (evt) {
            const data = $('#dtUtmMonthlyData').DataTable().row(this).data();
            sectionMonthlyElectricityClass.setMonthlySummary(data);
            sectionMonthlyElectricityClass.load();
        });
        oTableUtmMonthlyTbody.delegate('tr', 'mouseenter', function (evt) {
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', 'Click to see the Monthly report');
            $('[data-toggle="tooltip"]').tooltip();
        });

        let exportOptUtmMonthlyWater = Object.assign({}, mzExportOpt);
        exportOptUtmMonthlyWater['columns'] = [0, 1, 2, 3];
        oTableUtmMonthlyWater = $('#dtUtmMonthlyWaterData').DataTable({
            bLengthChange: false,
            bFilter: false,
            //ordering: false,
            language: _DATATABLE_LANGUAGE,
            pageLength: 50,
            autoWidth: false,
            aaSorting: [[5, 'desc'], [6, 'desc']],
            dom: "<'row'<'col-12 px-0'B>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 1, 2, 3, 4] },
                { className: 'text-right', targets: [1, 2] },
                { className: 'text-center font-weight-bold', targets: [3] },
                { visible: false, targets: [4, 5, 6] },
                { className: 'noVis', targets: [4, 5, 6] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Utility Monthly Records - Water', titleAttr: 'Print', exportOptions: exportOptUtmMonthlyWater},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Utility Monthly Records - Water', titleAttr: 'Copy', exportOptions: exportOptUtmMonthlyWater},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Utility Monthly Records - Water', titleAttr: 'Excel', exportOptions: exportOptUtmMonthlyWater},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Utility Monthly Records - Water', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOptUtmMonthlyWater}
            ],
            aoColumns: [
                {mData: 'utilityMonthName'},
                {mData: 'utilityTotalUsage', mRender: function (data){
                        return mzFormatNumber(data);
                    }},
                {mData: 'utilityTotalUsageRm', mRender: function (data){
                        return mzFormatNumber(data,2);
                    }},
                {mData: 'changePerc', width: '80px', mRender: function (data){
                        const changes = parseFloat(data);
                        if (changes === 0) {
                            return '-';
                        } else if (changes > 0) {
                            return '<a class="text-success"><i class="fas fa-arrow-down"></i> '+mzFormatNumber(changes,2)+'%</a>';
                        } else {
                            return '<a class="text-danger"><i class="fas fa-arrow-up"></i> '+mzFormatNumber(changes*-1,2)+'%</a>';
                        }
                    }},
                {mData: null, mRender: function (data, type, row){
                        const changes = parseFloat(row['changePerc']);
                        if (changes === 0) {
                            return '-';
                        } else {
                            return mzFormatNumber(changes*-1,2)+'%';
                        }
                    }},
                {mData: 'utilityYear'},
                {mData: 'utilityMonth'}
            ]
        });
        let oTableUtmMonthlyWaterTbody = $('#dtUtmMonthlyWaterData tbody');
        oTableUtmMonthlyWaterTbody.delegate('tr', 'click', function (evt) {
            const data = $('#dtUtmMonthlyWaterData').DataTable().row(this).data();
            sectionMonthlyWaterClass.setMonthlySummary(data);
            sectionMonthlyWaterClass.load();
        });
        oTableUtmMonthlyWaterTbody.delegate('tr', 'mouseenter', function (evt) {
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', 'Click to see the Monthly report');
            $('[data-toggle="tooltip"]').tooltip();
        });
        
        self.generateElectricityMonthly();
        self.generateWaterMonthly();
        window.scrollTo({top: 0, behavior: 'smooth'});
    };

    this.generateElectricityMonthly = function () {
        const dataMonthlyElectrics = mzAjaxRequest2('utility/data_monthly_analyzed/Electricity', 'GET');
        oTableUtmMonthly.clear().rows.add(dataMonthlyElectrics).draw();
        self.generateChartKhw('chartUtmKwh', dataMonthlyElectrics);
        self.generateChartMd('chartUtmMd', dataMonthlyElectrics);
        self.generateChartRm('chartUtmRm', dataMonthlyElectrics);
        self.updateElectricityMetrics(dataMonthlyElectrics);
    };

    this.generateWaterMonthly = function () {
        const dataMonthlyWater = mzAjaxRequest2('utility/data_monthly_analyzed/Water', 'GET');
        oTableUtmMonthlyWater.clear().rows.add(dataMonthlyWater).draw();
        self.generateChartWaterM3('chartUtmWaterM3', dataMonthlyWater);
        self.generateChartWaterRm('chartUtmWaterRm', dataMonthlyWater);
        self.updateWaterMetrics(dataMonthlyWater);
    };

    this.generateChartKhw = function (chartId, dataSet) {
        let data = [];
        let plotBands = [];
        let plotYear = '', plotFrom = 0, plotCount = 0, plotTotal = 0;
        let siteId = '';
        const plotColor = ['#FFEFF0', '#E9FFF2'];
        for (let i=0; i<dataSet.length; i++) {
            const dataYear = dataSet[i]['utilityYear'];
            const utilityTotalKwh = parseFloat(dataSet[i]['utilityTotalKwh']);
            data.push({name:dataSet[i]['utilityMonthName'], y:utilityTotalKwh, color: Highcharts.getOptions().colors[(parseInt(dataYear)+7)%10],
                utilityYear:dataYear, utilityMonth:dataSet[i]['utilityMonth']});
            if (plotYear !== dataYear && i > 0) {
                plotBands.push(
                    {
                        from: plotFrom - 0.5,
                        to: i - 0.5,
                        color: plotColor[parseInt(plotYear) % 2],
                        label: {
                            text: plotYear + '<br> <em>Avg : </em> <b>'+mzFormatNumber(plotTotal/plotCount)+'</b>',
                            style: {
                                color: '#999999'
                            },
                            y: 20
                        }
                    }
                );
                plotFrom = i;
                plotCount = 0;
                plotTotal = 0;
            }
            plotYear = dataYear;
            plotCount++;
            plotTotal += utilityTotalKwh;
            siteId = siteId === '' ? dataSet[i]['siteId'] : '';
        }
        if (plotCount > 0) {
            let plotTo = dataSet.length - 0.5;
            if (plotCount === 1) {
                data.push({name:'', y:0, color: Highcharts.getOptions().colors[0],
                    utilityYear:'', utilityMonth:''});
                plotTo = dataSet.length + 0.5;
            }
            plotBands.push(
                {
                    from: plotFrom - 0.5,
                    to: plotTo,
                    color: plotColor[parseInt(plotYear) % 2],
                    label: {
                        text: plotYear + '<br> <em>Avg : </em> <b>'+mzFormatNumber(plotTotal/plotCount)+'</b>',
                        style: {
                            color: '#999999'
                        },
                        y: 20
                    }
                }
            );
        }

        Highcharts.chart(chartId, {
            chart: {
                type: 'column',
                zoomType: 'x',
                backgroundColor: {
                    linearGradient: [0, 0, 1000, 1000],
                    stops: [
                        [0, 'rgb(255, 255, 255)'],
                        [1, 'rgb(204, 229, 255)']
                    ]
                }
            },
            title: {
                text: 'Electricity Consumption (kWh)'
            },
            subtitle: {
                text: siteId !== '' ? refSite[siteId]['siteName'] : ''
            },
            xAxis: {
                type: 'category',
                plotBands: plotBands
            },
            yAxis: {
                //min: 0,
                max: 1500000,
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

    this.generateChartMd = function (chartId, dataSet) {
        let data = [];
        let data2 = [];
        let plotBands = [];
        let plotYear = '', plotFrom = 0, plotCount = 0, plotTotal = 0;
        let siteId = '';
        const plotColor = ['#FFEFF0', '#E9FFF2'];
        for (let i=0; i<dataSet.length; i++) {
            const dataYear = dataSet[i]['utilityYear'];
            const utilityActualMaxDemand = parseFloat(dataSet[i]['utilityActualMaxDemand']);
            const utilityMaxDemand = parseFloat(dataSet[i]['utilityMaxDemand']);
            data.push({name:dataSet[i]['utilityMonthName'], y:utilityActualMaxDemand});
            data2.push({name:dataSet[i]['utilityMonthName'], y:utilityMaxDemand});
            if (plotYear !== dataYear && i > 0) {
                plotBands.push(
                    {
                        from: plotFrom - 0.5,
                        to: i - 0.5,
                        color: plotColor[parseInt(plotYear) % 2],
                        label: {
                            text: plotYear + '<br> <em>Avg : </em> <b>'+mzFormatNumber(plotTotal/plotCount)+'</b>',
                            style: {
                                color: '#999999'
                            },
                            y: 20
                        }
                    }
                );
                plotFrom = i;
                plotCount = 0;
                plotTotal = 0;
            }
            plotYear = dataYear;
            plotCount++;
            plotTotal += utilityActualMaxDemand;
            siteId = siteId === '' ? dataSet[i]['siteId'] : '';
        }
        if (plotCount > 0) {
            let plotTo = dataSet.length - 0.5;
            if (plotCount === 1) {
                data.push({name:'', y:''});
                data2.push({name:'', y:''});
                plotTo = dataSet.length + 0.5;
            }
            plotBands.push(
                {
                    from: plotFrom - 0.5,
                    to: plotTo,
                    color: plotColor[parseInt(plotYear) % 2],
                    label: {
                        text: plotYear + '<br> <em>Avg : </em> <b>'+mzFormatNumber(plotTotal/plotCount)+'</b>',
                        style: {
                            color: '#999999'
                        },
                        y: 20
                    }
                }
            );
        }

        Highcharts.chart(chartId, {
            chart: {
                type: 'line',
                zoomType: 'x',
                backgroundColor: {
                    linearGradient: [0, 0, 1000, 1000],
                    stops: [
                        [0, 'rgb(255, 255, 255)'],
                        [1, 'rgb(204, 229, 255)']
                    ]
                }
            },
            title: {
                text: 'Maximum Demand (kW)'
            },
            subtitle: {
                text: siteId !== '' ? refSite[siteId]['siteName'] : ''
            },
            xAxis: {
                type: 'category',
                plotBands: plotBands
            },
            yAxis: {
                //min: 0,
                max: 7000,
                title: {
                    text: 'Maximum Demand (kW)'
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
                name: 'Actual Maximum Demand (kW)',
                data: data
            },{
                name: 'Maximum Demand (kW)',
                color: Highcharts.getOptions().colors[5],
                data: data2
            }],
            credits: {
                enabled: false
            }
        });
    };

    this.generateChartRm = function (chartId, dataSet) {
        let data = [];
        let plotBands = [];
        let plotYear = '', plotFrom = 0, plotCount = 0, plotTotal = 0;
        const plotColor = ['#FFEFF0', '#E9FFF2'];
        let siteId = '';
        for (let i=0; i<dataSet.length; i++) {
            const dataYear = dataSet[i]['utilityYear'];
            const utilityTotalRm = parseFloat(dataSet[i]['electricityBillRm']);
            data.push({name:dataSet[i]['utilityMonthName'], y:utilityTotalRm, color: Highcharts.getOptions().colors[(parseInt(dataYear)+7)%10],
                utilityYear:dataYear, utilityMonth:dataSet[i]['utilityMonth']});
            if (plotYear !== dataYear && i > 0) {
                plotBands.push(
                    {
                        from: plotFrom - 0.5,
                        to: i - 0.5,
                        color: plotColor[parseInt(plotYear) % 2],
                        label: {
                            text: plotYear + '<br> <em>Avg : </em> <b>'+mzFormatNumber(plotTotal/plotCount, 2)+'</b>',
                            style: {
                                color: '#999999'
                            },
                            y: 20
                        }
                    }
                );
                plotFrom = i;
                plotCount = 0;
                plotTotal = 0;
            }
            plotYear = dataYear;
            plotCount++;
            plotTotal += utilityTotalRm;
            siteId = siteId === '' ? dataSet[i]['siteId'] : '';
        }
        if (plotCount > 0) {
            let plotTo = dataSet.length - 0.5;
            if (plotCount === 1) {
                data.push({name:'', y:0, color: Highcharts.getOptions().colors[0],
                    utilityYear:'', utilityMonth:''});
                plotTo = dataSet.length + 0.5;
            }
            plotBands.push(
                {
                    from: plotFrom - 0.5,
                    to: plotTo,
                    color: plotColor[parseInt(plotYear) % 2],
                    label: {
                        text: plotYear + '<br> <em>Avg : </em> <b>'+mzFormatNumber(plotTotal/plotCount, 2)+'</b>',
                        style: {
                            color: '#999999'
                        },
                        y: 20
                    }
                }
            );
        }

        Highcharts.chart(chartId, {
            chart: {
                type: 'column',
                zoomType: 'x',
                backgroundColor: {
                    linearGradient: [0, 0, 1000, 1000],
                    stops: [
                        [0, 'rgb(255, 255, 255)'],
                        [1, 'rgb(204, 229, 255)']
                    ]
                }
            },
            title: {
                text: 'Electricity Consumption (RM)'
            },
            subtitle: {
                text: siteId !== '' ? refSite[siteId]['siteName'] : ''
            },
            xAxis: {
                type: 'category',
                plotBands: plotBands
            },
            yAxis: {
                //min: 0,
                max: 700000,
                title: {
                    text: 'Electricity Consumption (RM)'
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
                name: 'Electricity Consumption (RM)',
                data: data
            }],
            credits: {
                enabled: false
            }
        });
    };

    this.generateChartWaterM3 = function (chartId, dataSet) {
        let data = [];
        let plotBands = [];
        let plotYear = '', plotFrom = 0, plotCount = 0, plotTotal = 0;
        let siteId = '';
        const plotColor = ['#FFEFF0', '#E9FFF2'];
        for (let i=0; i<dataSet.length; i++) {
            const dataYear = dataSet[i]['utilityYear'];
            const utilityTotalUsage = parseFloat(dataSet[i]['utilityTotalUsage']);
            data.push({name:dataSet[i]['utilityMonthName'], y:utilityTotalUsage, color: Highcharts.getOptions().colors[(parseInt(dataYear)+7)%10],
                utilityYear:dataYear, utilityMonth:dataSet[i]['utilityMonth']});
            if (plotYear !== dataYear && i > 0) {
                plotBands.push(
                    {
                        from: plotFrom - 0.5,
                        to: i - 0.5,
                        color: plotColor[parseInt(plotYear) % 2],
                        label: {
                            text: plotYear + '<br> <em>Avg : </em> <b>'+mzFormatNumber(plotTotal/plotCount)+'</b>',
                            style: {
                                color: '#999999'
                            },
                            y: 20
                        }
                    }
                );
                plotFrom = i;
                plotCount = 0;
                plotTotal = 0;
            }
            plotYear = dataYear;
            plotCount++;
            plotTotal += utilityTotalUsage;
            siteId = siteId === '' ? dataSet[i]['siteId'] : '';
        }
        if (plotCount > 0) {
            let plotTo = dataSet.length - 0.5;
            if (plotCount === 1) {
                data.push({name:'', y:0, color: Highcharts.getOptions().colors[0],
                    utilityYear:'', utilityMonth:''});
                plotTo = dataSet.length + 0.5;
            }
            plotBands.push(
                {
                    from: plotFrom - 0.5,
                    to: plotTo,
                    color: plotColor[parseInt(plotYear) % 2],
                    label: {
                        text: plotYear + '<br> <em>Avg : </em> <b>'+mzFormatNumber(plotTotal/plotCount)+'</b>',
                        style: {
                            color: '#999999'
                        },
                        y: 20
                    }
                }
            );
        }

        Highcharts.chart(chartId, {
            chart: {
                type: 'column',
                zoomType: 'x',
                backgroundColor: {
                    linearGradient: [0, 0, 1000, 1000],
                    stops: [
                        [0, 'rgb(255, 255, 255)'],
                        [1, 'rgb(204, 229, 255)']
                    ]
                }
            },
            title: {
                text: 'Water Domestic Consumption (m3)'
            },
            subtitle: {
                text: siteId !== '' ? refSite[siteId]['siteName'] : ''
            },
            xAxis: {
                type: 'category',
                plotBands: plotBands
            },
            yAxis: {
                //min: 0,
                //max: 1500000,
                title: {
                    text: 'Water Domestic Consumption (m3)'
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
                name: 'Water Domestic Consumption (m3)',
                data: data
            }],
            credits: {
                enabled: false
            }
        });
    };

    this.generateChartWaterRm = function (chartId, dataSet) {
        let data = [];
        let plotBands = [];
        let plotYear = '', plotFrom = 0, plotCount = 0, plotTotal = 0;
        let siteId = '';
        const plotColor = ['#FFEFF0', '#E9FFF2'];
        for (let i=0; i<dataSet.length; i++) {
            const dataYear = dataSet[i]['utilityYear'];
            const utilityTotalUsageRm = parseFloat(dataSet[i]['utilityTotalUsageRm']);
            data.push({name:dataSet[i]['utilityMonthName'], y:utilityTotalUsageRm, color: Highcharts.getOptions().colors[(parseInt(dataYear)+7)%10],
                utilityYear:dataYear, utilityMonth:dataSet[i]['utilityMonth']});
            if (plotYear !== dataYear && i > 0) {
                plotBands.push(
                    {
                        from: plotFrom - 0.5,
                        to: i - 0.5,
                        color: plotColor[parseInt(plotYear) % 2],
                        label: {
                            text: plotYear + '<br> <em>Avg : </em> <b>'+mzFormatNumber(plotTotal/plotCount, 2)+'</b>',
                            style: {
                                color: '#999999'
                            },
                            y: 20
                        }
                    }
                );
                plotFrom = i;
                plotCount = 0;
                plotTotal = 0;
            }
            plotYear = dataYear;
            plotCount++;
            plotTotal += utilityTotalUsageRm;
            siteId = siteId === '' ? dataSet[i]['siteId'] : '';
        }
        if (plotCount > 0) {
            let plotTo = dataSet.length - 0.5;
            if (plotCount === 1) {
                data.push({name:'', y:0, color: Highcharts.getOptions().colors[0],
                    utilityYear:'', utilityMonth:''});
                plotTo = dataSet.length + 0.5;
            }
            plotBands.push(
                {
                    from: plotFrom - 0.5,
                    to: plotTo,
                    color: plotColor[parseInt(plotYear) % 2],
                    label: {
                        text: plotYear + '<br> <em>Avg : </em> <b>'+mzFormatNumber(plotTotal/plotCount, 2)+'</b>',
                        style: {
                            color: '#999999'
                        },
                        y: 20
                    }
                }
            );
        }

        Highcharts.chart(chartId, {
            chart: {
                type: 'column',
                zoomType: 'x',
                backgroundColor: {
                    linearGradient: [0, 0, 1000, 1000],
                    stops: [
                        [0, 'rgb(255, 255, 255)'],
                        [1, 'rgb(204, 229, 255)']
                    ]
                }
            },
            title: {
                text: 'Water Domestic Consumption (RM)'
            },
            subtitle: {
                text: siteId !== '' ? refSite[siteId]['siteName'] : ''
            },
            xAxis: {
                type: 'category',
                plotBands: plotBands
            },
            yAxis: {
                //min: 0,
                max: 22000,
                title: {
                    text: 'Water Domestic Consumption (RM)'
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
                name: 'Water Domestic Consumption (RM)',
                data: data
            }],
            credits: {
                enabled: false
            }
        });
    };

    this.showMain = function () {
        $('.sectionUtmMain').show();
    };

    this.hideMain = function () {
        $('.sectionUtmMain').hide();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setSectionMonthlyElectricityClass = function (_sectionMonthlyElectricityClass) {
        sectionMonthlyElectricityClass = _sectionMonthlyElectricityClass;
    };

    this.setSectionMonthlyWaterClass = function (_sectionMonthlyWaterClass) {
        sectionMonthlyWaterClass = _sectionMonthlyWaterClass;
    };

    this.updateElectricityMetrics = function (dataSet) {
        if (!Array.isArray(dataSet) || dataSet.length === 0) {
            $('#metricUtmElectricUsage').text('0');
            $('#metricUtmElectricCharges').text('RM 0');
            $('#metricUtmElectricPeriod').text('Latest: —');
            $('#metricUtmElectricDemand').text('Peak MD: —');
            return;
        }

        let totalKwh = 0;
        let totalCharges = 0;
        let peakDemand = 0;
        let peakDemandRecord = null;
        let latestRecord = null;
        let latestKey = -Infinity;

        dataSet.forEach(function (row) {
            const kwh = parseFloat(row['utilityTotalKwh'] || 0);
            const charges = parseFloat(row['electricityBillRm'] || 0);
            const demand = parseFloat(row['utilityActualMaxDemand'] || 0);
            const year = parseInt(row['utilityYear'] || 0, 10);
            const month = parseInt(row['utilityMonth'] || 0, 10);
            const compositeKey = (year * 100) + month;

            totalKwh += isNaN(kwh) ? 0 : kwh;
            totalCharges += isNaN(charges) ? 0 : charges;

            if (!isNaN(demand) && demand >= peakDemand) {
                peakDemand = demand;
                peakDemandRecord = row;
            }

            if (compositeKey >= latestKey) {
                latestKey = compositeKey;
                latestRecord = row;
            }
        });

        $('#metricUtmElectricUsage').text(mzFormatNumber(totalKwh));
        $('#metricUtmElectricCharges').text('RM ' + mzFormatNumber(totalCharges, 2));

        if (latestRecord) {
            $('#metricUtmElectricPeriod').text('Latest: ' + latestRecord['utilityMonthName'] + ' ' + latestRecord['utilityYear']);
        } else {
            $('#metricUtmElectricPeriod').text('Latest: —');
        }

        if (peakDemandRecord) {
            const peakMonth = peakDemandRecord['utilityMonthName'] + ' ' + peakDemandRecord['utilityYear'];
            $('#metricUtmElectricDemand').text('Peak MD: ' + mzFormatNumber(peakDemand) + ' kW (' + peakMonth + ')');
        } else {
            $('#metricUtmElectricDemand').text('Peak MD: —');
        }
    };

    this.updateWaterMetrics = function (dataSet) {
        if (!Array.isArray(dataSet) || dataSet.length === 0) {
            $('#metricUtmWaterUsage').text('0');
            $('#metricUtmWaterCharges').text('RM 0');
            $('#metricUtmWaterPeriod').text('Latest: —');
            $('#metricUtmWaterPeak').text('Peak Bill: —');
            return;
        }

        let totalUsage = 0;
        let totalCharges = 0;
        let latestRecord = null;
        let latestKey = -Infinity;
        let peakBill = 0;
        let peakBillRecord = null;

        dataSet.forEach(function (row) {
            const usage = parseFloat(row['utilityTotalUsage'] || 0);
            const charges = parseFloat(row['utilityTotalUsageRm'] || 0);
            const year = parseInt(row['utilityYear'] || 0, 10);
            const month = parseInt(row['utilityMonth'] || 0, 10);
            const compositeKey = (year * 100) + month;

            totalUsage += isNaN(usage) ? 0 : usage;
            totalCharges += isNaN(charges) ? 0 : charges;

            if (!isNaN(charges) && charges >= peakBill) {
                peakBill = charges;
                peakBillRecord = row;
            }

            if (compositeKey >= latestKey) {
                latestKey = compositeKey;
                latestRecord = row;
            }
        });

        $('#metricUtmWaterUsage').text(mzFormatNumber(totalUsage));
        $('#metricUtmWaterCharges').text('RM ' + mzFormatNumber(totalCharges, 2));

        if (latestRecord) {
            $('#metricUtmWaterPeriod').text('Latest: ' + latestRecord['utilityMonthName'] + ' ' + latestRecord['utilityYear']);
        } else {
            $('#metricUtmWaterPeriod').text('Latest: —');
        }

        if (peakBillRecord) {
            const peakMonth = peakBillRecord['utilityMonthName'] + ' ' + peakBillRecord['utilityYear'];
            $('#metricUtmWaterPeak').text('Peak Bill: RM ' + mzFormatNumber(peakBill, 2) + ' (' + peakMonth + ')');
        } else {
            $('#metricUtmWaterPeak').text('Peak Bill: —');
        }
    };
}