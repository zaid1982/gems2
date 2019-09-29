function MainFinanceReport3() {

    this.init = function () {

        Highcharts.chart('chartFrf1', {
            chart: {
                type: 'area',
            },
            exporting: {
                enabled: false
            },
            title: {
                text: ''
            },
            xAxis: {
                visible: false,
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            yAxis: {
                title: {
                    text: null
                },
                visible: false
            },
            plotOptions: {
                area: {
                    dataLabels: {
                        enabled: false
                    },
                    marker: {
                        enabled: false
                    },
                    borderWidth: 0
                }
            },
            legend: {
                enabled: false
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Quick Ratio',
                data: [5, 7, 8, 5, 6, 8, 6, 6, 6, 6, 6, 6],
                color: 'mediumorchid'
            }]
        });

        Highcharts.chart('chartFrf2', {
            chart: {
                type: 'area'
            },
            exporting: {
                enabled: false
            },
            title: {
                text: ''
            },
            xAxis: {
                visible: false,
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            yAxis: {
                title: {
                    text: null
                },
                visible: false
            },
            plotOptions: {
                area: {
                    dataLabels: {
                        enabled: false
                    },
                    marker: {
                        enabled: false
                    },
                    borderWidth: 0
                }
            },
            legend: {
                enabled: false
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Current Ratio',
                data: [5, 7, 8, 5, 6, 8, 6, 6, 6, 6, 6, 6],
                color: 'mediumorchid'
            }]
        });

        Highcharts.chart('chartFrf3', {
            chart: {
                type: 'column',
            },
            title: {
                text: 'Cash & Cash Equivalent'
            },
            xAxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            yAxis: {
                title: {
                    text: null
                }
            },
            plotOptions: {
                column: {
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontSize: '12px',
                            fontWeight: 200
                        }
                    },
                    borderWidth: 0
                }
            },
            legend: {
                enabled: false
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Cash & Cash Equivalent',
                data: [60, 60, 60, 60, 60, 60, 60, 60, 60, 60, 60, 60],
                color: 'mediumaquamarine'
            }]
        });

        Highcharts.chart('chartFrf4', {
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: 0,
                plotShadow: false
            },
            exporting: {
                enabled: false
            },
            title: {
                text: '62',
                style: { "fontSize": "40px" },
                align: 'center',
                verticalAlign: 'middle',
                y: 60
            },
            plotOptions: {
                pie: {
                    dataLabels: {
                        enabled: false
                    },
                    startAngle: -90,
                    endAngle: 90,
                    center: ['50%', '120%'],
                    size: '230%'
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                type: 'pie',
                name: 'Days Sales Outstanding',
                innerSize: '60%',
                data: [
                    {name:'a', y:60, color: 'palegreen'},
                    {name:'a', y:60, color: 'yellowgreen'},
                    {name:'a', y:60, color: 'darkgreen'}
                ]
            }]
        });

        Highcharts.chart('chartFrf5', {
            chart: {
                type: 'column',
            },
            exporting: {
                enabled: false
            },
            title: {
                text: ''
            },
            xAxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            yAxis: {
                title: {
                    text: null
                },
                visible: false
            },
            plotOptions: {
                column: {
                    dataLabels: {
                        enabled: false
                    },
                    borderWidth: 0
                }
            },
            credits: {
                enabled: false
            },
            series: [
                {
                    name: 'Days Sales Outstanding',
                    data: [60, 60, 60, 60, 60, 60, 60, 60, 60, 60, 60, 60],
                    color: 'lightgreen'
                },
                {
                    name: 'Days Payable Outstanding',
                    data: [30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],
                    color: 'darkgreen'
                }
            ]
        });

        Highcharts.chart('chartFrf6', {
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: 0,
                plotShadow: false
            },
            exporting: {
                enabled: false
            },
            title: {
                text: '32',
                style: { "fontSize": "40px" },
                align: 'center',
                verticalAlign: 'middle',
                y: 60
            },
            plotOptions: {
                pie: {
                    dataLabels: {
                        enabled: false
                    },
                    startAngle: -90,
                    endAngle: 90,
                    center: ['50%', '120%'],
                    size: '230%'
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                type: 'pie',
                name: 'Days Payable Outstanding',
                innerSize: '60%',
                data: [
                    {name:'a', y:60, color: 'lightblue'},
                    {name:'a', y:60, color: 'cornflowerblue'},
                    {name:'a', y:60, color: 'darkblue'}
                ]
            }]
        });

        Highcharts.chart('chartFrf7', {
            chart: {
                type: 'column',
            },
            exporting: {
                enabled: false
            },
            title: {
                text: ''
            },
            xAxis: {
                categories: ['Not Due', '<30 days', '<60 days', '<60 days', '<120 days']
            },
            yAxis: {
                title: {
                    text: null
                },
                visible: false
            },
            plotOptions: {
                column: {
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontSize: '12px',
                            fontWeight: 200
                        }
                    },
                    borderWidth: 0
                }
            },
            legend: {
                enabled: false
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Accounts Payable by Payment Target',
                data: [10, 6, 4, 3, 1.5],
                color: 'cornflowerblue'
            }]
        });

        Highcharts.chart('chartFrf8', {
            chart: {
                type: 'area',
            },
            exporting: {
                enabled: false
            },
            title: {
                text: ''
            },
            xAxis: {
                categories: ['1Q 2017', '2Q 2017', '3Q 2017', '4Q 2017']
            },
            plotOptions: {
                area: {
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontSize: '12px',
                            fontWeight: 200,
                            textOutline: '0px'
                        }
                    },
                    marker: {
                        enabled: false
                    },
                    borderWidth: 0
                }
            },
            yAxis: {
                title: {
                    text: null
                },
                visible: false
            },
            legend: {
                enabled: false
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Working Capital Ratio',
                data: [3.2, 5.6, 7.6, 4.3],
                color: 'goldenrod'
            }]
        });
    };

}