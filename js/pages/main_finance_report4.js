function MainFinanceReport4() {

    this.init = function () {

        Highcharts.chart('chartFrf1', {
            chart: {
                type: 'column',
            },
            exporting: {
                enabled: false
            },
            title: {
                text: ''
            },
            subtitle: {
                text: 'Compared to last year'
            },
            xAxis: {
                categories: ['Last year', 'This year']
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
                name: 'New Revenue',
                data: [
                    {y:8.993, color: 'moccasin'},
                    {y:4.532, color: 'darkorange'}
                ]
            }]
        });

        Highcharts.chart('chartFrf2', {
            chart: {
                type: 'column',
            },
            exporting: {
                enabled: false
            },
            title: {
                text: ''
            },
            subtitle: {
                text: 'Compared to last year'
            },
            xAxis: {
                categories: ['Last year', 'This year']
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
                        },
                        format: '{point.y}%'
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
                name: 'Contract Renewal',
                data: [
                    {y:100, color: 'palegreen'},
                    {y:60, color: 'darkgreen'}
                ]
            }]
        });

        Highcharts.chart('chartFrf3', {
            title: {
                text: ''
            },
            exporting: {
                enabled: false
            },
            xAxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            yAxis: {
                title: {
                    text: null
                },
                tickInterval: 50
            },
            legend: {
                enabled: false
            },
            credits: {
                enabled: false
            },
            series: [{
                type: 'spline',
                name: 'Order Book',
                dashStyle: 'shortdot',
                color: 'dodgerblue',
                marker: {
                    enabled: false
                },
                data: [360, 350, 340, 330, 320, 310, 300, 290, 280, 270, 260, 250]
            }, {
                type: 'column',
                name: 'Order Book Line',
                color: 'dodgerblue',
                data: [360, 350, 340, 330, 320, 310, 300, 290, 280, 270, 260, 250]
            }]
        });

        Highcharts.chart('chartFrf4', {
            chart: {
                type: 'column',
            },
            exporting: {
                enabled: false
            },
            title: {
                text: ''
            },
            subtitle: {
                text: 'CAC vs CLV'
            },
            xAxis: {
                categories: ['CAC', 'CLV']
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
                name: 'New Revenue',
                data: [
                    {y:2400, color: 'orchid'},
                    {y:27563, color: 'darkslateblue'}
                ]
            }]
        });
    };

}