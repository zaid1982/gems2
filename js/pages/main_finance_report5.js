function MainFinanceReport5() {

    this.init = function () {

        Highcharts.chart('chartFrf1', {
            chart: {
                type: 'funnel'
            },
            exporting: {
                enabled: false
            },
            title: {
                text: ''
            },
            plotOptions: {
                series: {
                    dataLabels: {
                        enabled: true,
                        format: '{point.name} <b>{point.y:,.0f}</b>',
                        inside: true,
                        style: {
                            fontSize: '18px',
                            fontWeight: 500
                        }
                    },
                    center: ['50%', '50%'],
                    neckWidth: '50%',
                    neckHeight: '0%',
                    width: '100%'
                }
            },
            legend: {
                enabled: false
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Unique users',
                data: [
                    {name:'Opportunities', y:8, color: 'mediumaquamarine'},
                    {name:'Proposal', y:10, color: 'darkkhaki'},
                    {name:'Negotiation', y:5, color: 'moccasin'},
                    {name:'Win', y:3, color: 'lightblue'}
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
                        enabled: true
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
            series: [
                {
                    name: 'Days Sales Outstanding',
                    data: [1, 0, 0, 1, 2, 0, 0, 1, 0, 1, 0, 0],
                    color: 'mediumpurple'
                }
            ]
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
                        enabled: true
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
            series: [
                {
                    name: 'Days Sales Outstanding',
                    data: [1, 0, 0, 1, 2, 0, 0, 1, 0, 1, 0, 0],
                    color: 'mediumpurple'
                }
            ]
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
                        enabled: true
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
            series: [
                {
                    name: 'Days Sales Outstanding',
                    data: [1, 0, 0, 1, 2, 0, 0, 1, 0, 1, 0, 0],
                    color: 'mediumpurple'
                }
            ]
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
                        enabled: true
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
            series: [
                {
                    name: 'Days Sales Outstanding',
                    data: [1, 0, 0, 1, 2, 0, 0, 1, 0, 1, 0, 0],
                    color: 'mediumpurple'
                }
            ]
        });

        Highcharts.chart('chartFrf6', {
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
                        enabled: true
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
            series: [
                {
                    name: 'Days Sales Outstanding',
                    data: [1, 0, 0, 1, 2, 0, 0, 1, 0, 1, 0, 0],
                    color: 'mediumpurple'
                }
            ]
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
                        enabled: true
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
            series: [
                {
                    name: 'Days Sales Outstanding',
                    data: [1, 0, 0, 1, 2, 0, 0, 1, 0, 1, 0, 0],
                    color: 'mediumpurple'
                }
            ]
        });

    };

}