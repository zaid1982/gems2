function MainFinanceReport1() {

    this.init = function () {

        Highcharts.chart('chartFrf1', {
            title: {
                text: 'Revenue'
            },
            xAxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            yAxis: {
                title: {
                    text: null
                }
            },
            legend: {
                align: 'right',
                verticalAlign: 'middle',
                layout: 'vertical'
            },
            credits: {
                enabled: false
            },
            series: [{
                type: 'column',
                name: 'Revenue',
                color: Highcharts.getOptions().colors[0],
                data: [8.2, 8.2, 8.2, 8.2, 8.2, 8.2, 8.2, 8.2, 8.2, 8.2, 8.2, 8.2]
            }, {
                type: 'column',
                name: 'AMP',
                color: Highcharts.getOptions().colors[3],
                data: [9.5, 9.5, 9.5, 9.5, 9.5, 9.5, 9.5, 9.5, 9.5, 9.5, 9.5, 9.5]
            }, {
                type: 'spline',
                name: 'Last Year',
                color: '#bdbdbd',
                data: [8, 6, 6.5, 7.8, 7, 8, 6, 6.4, 6.7, 7, 7.3, 8]
            }]
        });

        Highcharts.chart('chartFrf2', {
            title: {
                text: 'Profit After Taxes'
            },
            xAxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            yAxis: {
                title: {
                    text: null
                },
                tickInterval: 2
            },
            legend: {
                align: 'right',
                verticalAlign: 'middle',
                layout: 'vertical'
            },
            credits: {
                enabled: false
            },
            series: [{
                type: 'spline',
                name: 'Revenue',
                color: Highcharts.getOptions().colors[0],
                data: [8.3, 8.7, 9.0, 8.3, 8.5, 9.0, 8.7, 8.3, 8.3, 8.3, 8.3, 8.3]
            }, {
                type: 'spline',
                name: 'AMP',
                color: Highcharts.getOptions().colors[3],
                data: [9.5, 9.5, 9.5, 9.5, 9.5, 9.5, 9.5, 9.5, 9.5, 9.5, 9.5, 9.5]
            }, {
                type: 'column',
                name: 'Last Year',
                color: '#bdbdbd',
                data: [7.7, 6, 6.5, 7.8, 7, 8, 6, 6.4, 6.7, 7, 7.3, 8]
            }]
        });
    };

}