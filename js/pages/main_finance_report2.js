function MainFinanceReport2() {

    this.init = function () {

        Highcharts.chart('chartFrf1', {
            chart: {
                backgroundColor: 'rgba(0, 150, 136, 0)',
                type: 'column',
            },
            exporting: {
                enabled: false
            },
            title: {
                text: ''
            },
            xAxis: {
                categories: ['1Q 2017', '2Q 2017', '3Q 2017', '4Q 2017'],
                labels: {
                    style: {
                        color: 'whitesmoke'
                    }
                }
            },
            plotOptions: {
                column: {
                    dataLabels: {
                        enabled: true,
                        style: {
                            color: 'whitesmoke',
                            fontSize: '12px',
                            fontWeight: 200,
                            textOutline: '0px'
                        }
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
                name: 'Return on Assets',
                data: [60, 60, 65, 60],
                color: 'whitesmoke'
            }]
        });

        Highcharts.chart('chartFrf2', {
            chart: {
                backgroundColor: 'rgba(0, 150, 136, 0)',
                type: 'column',
            },
            exporting: {
                enabled: false
            },
            title: {
                text: ''
            },
            xAxis: {
                categories: ['1Q 2017', '2Q 2017', '3Q 2017', '4Q 2017'],
                labels: {
                    style: {
                        color: 'whitesmoke'
                    }
                }
            },
            plotOptions: {
                column: {
                    dataLabels: {
                        enabled: true,
                        style: {
                            color: 'whitesmoke',
                            fontSize: '12px',
                            fontWeight: 200,
                            textOutline: '0px'
                        }
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
                name: 'Return on Equity',
                data: [32, 32, 35, 32],
                color: 'whitesmoke'
            }]
        });

        Highcharts.chart('chartFrf3', {
            chart: {
                backgroundColor: 'rgba(0, 150, 136, 0)',
                type: 'area',
            },
            exporting: {
                enabled: false
            },
            title: {
                text: ''
            },
            xAxis: {
                categories: ['1Q 2017', '2Q 2017', '3Q 2017', '4Q 2017'],
                labels: {
                    style: {
                        color: 'whitesmoke'
                    }
                }
            },
            plotOptions: {
                area: {
                    dataLabels: {
                        enabled: true,
                        style: {
                            color: 'whitesmoke',
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
                name: 'Debt-Equity Ratio',
                data: [0.3, 0.4, 0.2, 0.5],
                color: 'whitesmoke'
            }]
        });

        Highcharts.chart('chartFrf4', {
            chart: {
                backgroundColor: 'rgba(0, 150, 136, 0)',
                type: 'column',
            },
            exporting: {
                enabled: false
            },
            title: {
                text: ''
            },
            xAxis: {
                categories: ['1Q 2017', '2Q 2017', '3Q 2017', '4Q 2017'],
                labels: {
                    style: {
                        color: 'whitesmoke'
                    }
                }
            },
            plotOptions: {
                column: {
                    dataLabels: {
                        enabled: true,
                        style: {
                            color: 'whitesmoke',
                            fontSize: '12px',
                            fontWeight: 200,
                            textOutline: '0px'
                        }
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
                name: 'Share Price',
                data: [0.46, 0.65, 0.72, 0.60],
                color: 'whitesmoke'
            }]
        });

        Highcharts.chart('chartFrf5', {
            chart: {
                backgroundColor: 'rgba(0, 150, 136, 0)',
                type: 'area',
            },
            exporting: {
                enabled: false
            },
            title: {
                text: ''
            },
            xAxis: {
                categories: ['1Q 2017', '2Q 2017', '3Q 2017', '4Q 2017'],
                labels: {
                    style: {
                        color: 'whitesmoke'
                    }
                }
            },
            plotOptions: {
                area: {
                    dataLabels: {
                        enabled: true,
                        style: {
                            color: 'whitesmoke',
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
                name: 'P/E Ratio',
                data: [29.7, 24.3, 7.6, 4.3],
                color: 'whitesmoke'
            }]
        });

        Highcharts.chart('chartFrf6', {
            chart: {
                backgroundColor: 'rgba(0, 150, 136, 0)',
                type: 'area',
            },
            exporting: {
                enabled: false
            },
            title: {
                text: ''
            },
            xAxis: {
                categories: ['1Q 2017', '2Q 2017', '3Q 2017', '4Q 2017'],
                labels: {
                    style: {
                        color: 'whitesmoke'
                    }
                }
            },
            plotOptions: {
                area: {
                    dataLabels: {
                        enabled: true,
                        style: {
                            color: 'whitesmoke',
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
                color: 'whitesmoke'
            }]
        });
    };

}