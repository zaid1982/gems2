function MainFinanceReport6() {

    this.init = function () {

        Highcharts.chart('chartFrf1', {
            chart: {
                type: 'column',
            },
            exporting: {
                enabled: false
            },
            title: {
                text: 'Absenteeism'
            },
            xAxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            yAxis: {
                title: {
                    text: null
                },
                tickInterval: 2,
                labels: {
                    enabled: false
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
                name: 'Absenteeism',
                data: [6, 7, 5, 7, 3, 4, 6, 8, 10, 3, 1, 6],
                color: '#ff4444'
            }]
        });

        Highcharts.chart('chartFrf2', {
            chart: {
                type: 'line',
            },
            exporting: {
                enabled: false
            },
            title: {
                text: 'Overtime Hours'
            },
            xAxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            yAxis: {
                title: {
                    text: null
                },
                visible: false,
                min: 0
            },
            plotOptions: {
                line: {
                    dataLabels: {
                        enabled: false
                    }
                }
            },
            legend: {
                enabled: false
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Overtime Hours',
                data: [2, 3, 4, 2, 7, 6, 5, 3, 4, 6, 8, 2],
                color: '#ff4444'
            }]
        });

        Highcharts.chart('chartFrf3', {
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
                categories: ['< 25', '26 - 35', '35 - 45', '> 45']
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
                name: 'Overtime Hours',
                data: [1, 2, 3, 4],
                color: '#ff4444'
            }]
        });

        Highcharts.chart('chartFrf4', {
            chart: {
                type: 'line',
            },
            exporting: {
                enabled: false
            },
            title: {
                text: 'OLE = 83%'
            },
            xAxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            yAxis: {
                title: {
                    text: null
                },
                tickInterval: 2,
                labels: {
                    enabled: false
                }
            },
            plotOptions: {
                line: {
                    dataLabels: {
                        enabled: false
                    }
                }
            },
            legend: {
                enabled: false
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'OLE',
                data: [6.5, 6, 5.5, 6, 2, 2.5, 3, 3.5, 5, 4.5, 2, 6],
                color: '#ff4444'
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
                categories: ['Project 1', 'Project 2', 'Project 3', 'Project 4', 'Project 5', 'Project 6', 'Project 7', 'Project 8', 'Project 9', 'Project 10',
                    'Project 11', 'Project 12', 'Project 13', 'Project 14', 'Project 15', 'Project 16', 'Project 17', 'Project 18', 'Project 19', 'Project 20',
                    'Project 21', 'Project 22'],
                labels: {
                    rotation: 270
                }
            },
            yAxis: {
                title: {
                    text: null
                },
                tickInterval: 2,
                labels: {
                    enabled: false
                }
            },
            plotOptions: {
                column: {
                    dataLabels: {
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
                name: 'OLE',
                data: [6, 7, 5, 7, 3, 4, 6, 8, 10, 3, 11, 6, 5, 7, 3, 4, 6, 8, 10, 3, 1, 6],
                color: '#ff4444'
            }]
        });
    };

}