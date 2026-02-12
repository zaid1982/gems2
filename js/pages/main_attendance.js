function MainAttendance() {

    const className = 'MainAttendance';
    let self = this;
    let modalAttendanceShiftClass;

    this.init = function () {
        $('#btnAttShiftMorning, #btnAttShiftEvening, #btnAttShiftNight').on('click', function () {
            modalAttendanceShiftClass.edit();
        });

        Highcharts.chart('chartAtt1', {
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: 0,
                plotShadow: false
            },
            title: {
                text: '65 %',
                style: { "fontSize": "40px" },
                align: 'center',
                verticalAlign: 'middle',
                y: 40
            },
            plotOptions: {
                pie: {
                    dataLabels: {
                        enabled: false
                    },
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                type: 'pie',
                name: 'Days Sales Outstanding',
                innerSize: '70%',
                data: [
                    {name:'a', y:65, color: 'darkblue'},
                    {name:'a', y:35, color: 'lightblue'}
                ]
            }]
        });


        Highcharts.chart('chartAtt2', {
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: 0,
                plotShadow: false
            },
            title: {
                text: '65 %',
                style: { "fontSize": "40px" },
                align: 'center',
                verticalAlign: 'middle',
                y: 40
            },
            plotOptions: {
                pie: {
                    dataLabels: {
                        enabled: false
                    },
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                type: 'pie',
                name: 'Days Sales Outstanding',
                innerSize: '70%',
                data: [
                    {name:'a', y:65, color: 'darkblue'},
                    {name:'a', y:35, color: 'lightblue'}
                ]
            }]
        });


        Highcharts.chart('chartAtt3', {
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: 0,
                plotShadow: false
            },
            title: {
                text: '65 %',
                style: { "fontSize": "40px" },
                align: 'center',
                verticalAlign: 'middle',
                y: 40
            },
            plotOptions: {
                pie: {
                    dataLabels: {
                        enabled: false
                    },
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                type: 'pie',
                name: 'Days Sales Outstanding',
                innerSize: '70%',
                data: [
                    {name:'a', y:65, color: 'darkblue'},
                    {name:'a', y:35, color: 'lightblue'}
                ]
            }]
        });


        Highcharts.chart('chartAtt4', {
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: 0,
                plotShadow: false
            },
            title: {
                text: '65 %',
                style: { "fontSize": "40px" },
                align: 'center',
                verticalAlign: 'middle',
                y: 40
            },
            plotOptions: {
                pie: {
                    dataLabels: {
                        enabled: false
                    },
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                type: 'pie',
                name: 'Days Sales Outstanding',
                innerSize: '70%',
                data: [
                    {name:'a', y:65, color: 'darkblue'},
                    {name:'a', y:35, color: 'lightblue'}
                ]
            }]
        });

        Highcharts.chart('chartAtt5', {
            chart: {
                type: 'heatmap',
                marginTop: 40,
                marginBottom: 80,
                plotBorderWidth: 1
            },
            title: {
                text: 'Total Staff\'s Working Hours'
            },

            xAxis: {
                categories: ['Amri Yazid', 'Razak', 'Ali Mohd', 'Jojo Roki', 'Saiful', 'Maria', 'Leon', 'Anna']
            },

            yAxis: {
                categories: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'],
                title: null,
                reversed: true
            },
            colorAxis: {
                min: 0,
                minColor: '#FFFFFF',
                maxColor: Highcharts.getOptions().colors[0]
            },
            legend: {
                align: 'right',
                layout: 'vertical',
                margin: 0,
                verticalAlign: 'top',
                y: 25,
                symbolHeight: 350
            },
            tooltip: {
                formatter: function () {
                    return '<b>' + self.getPointCategoryName(this.point, 'x') + '</b> sold <br><b>' +
                        this.point.value + '</b> items on <br><b>' + self.getPointCategoryName(this.point, 'y') + '</b>';
                }
            },
            series: [{
                name: 'Sales per employee',
                borderWidth: 1,
                data: [[0, 0, 10], [0, 1, 19], [0, 2, 8], [0, 3, 48], [0, 4, 48],
                    [1, 0, 44], [1, 1, 48], [1, 2, 48], [1, 3, 48], [1, 4, 48],
                    [2, 0, 35], [2, 1, 15], [2, 2, 48], [2, 3, 48], [2, 4, 48],
                    [3, 0, 48], [3, 1, 48], [3, 2, 48], [3, 3, 19], [3, 4, 16],
                    [4, 0, 38], [4, 1, 5], [4, 2, 8], [4, 3, 48], [4, 4, 48],
                    [5, 0, 48], [5, 1, 32], [5, 2, 12], [5, 3, 6], [5, 4, 48],
                    [6, 0, 13], [6, 1, 44], [6, 2, 48], [6, 3, 48], [6, 4, 48],
                    [7, 0, 31], [7, 1, 1], [7, 2, 48], [7, 3, 32], [7, 4, 30]],
                dataLabels: {
                    enabled: true,
                    color: '#000000'
                }
            }],
            responsive: {
                rules: [{
                    condition: {
                        maxWidth: 500
                    },
                    chartOptions: {
                        yAxis: {
                            labels: {
                                formatter: function () {
                                    return this.value.charAt(0);
                                }
                            }
                        }
                    }
                }]
            },
            credits: {
                enabled: false
            }
        });

        let dataCalendars = [];
        let textColor = ['white', 'white', 'white', 'white', 'black'];
        let backgroundColor = ['blue', 'green', 'red', 'black', 'yellow'];

        //const params = userRole === 2 ? '?userId='+userId : '';
        //const activityUsers = mzAjaxRequest('activity.php'+params, 'GET', {'Reportid': 'activity_user_calendar'});
        const activityUsers = [
            {a:'Simpan & Rujuk Rakan', b:'2021-08-02T08:00:00', c:'2021-08-02T14:00:00', d:'0'},
            {a:'Setia dan Menang', b:'2021-08-02', c:'2021-08-02', d:'1'},
            {a:'Setia dan Menang', b:'2021-08-03', c:'2021-08-03', d:'2'}
        ];
        $.each(activityUsers, function (n, u) {
            let dataCalendar = [];
            dataCalendar['title'] = u['a'];
            dataCalendar['start'] = u['b'];
            dataCalendar['end'] = u['c'];
            //dataCalendar['activityId'] = u['activityId'];
            dataCalendar['textColor'] = textColor[parseInt(u['d'])%5];
            dataCalendar['backgroundColor'] = backgroundColor[parseInt(u['d'])%5];
            dataCalendars.push(dataCalendar);
        });

        const calendarEl = document.getElementById('calendar');
        let calendar = new FullCalendar.Calendar(calendarEl, {
            headerToolbar: {
                left: 'prev,next',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            height: 450,
            //defaultDate: '2017-08-16',
            navLinks: true, // can click day/week names to navigate views
            nowIndicator: true,
            editable: false,
            eventLimit: true, // allow "more" link when too many events
            //events: dataCalendars,
            events: [
                {
                    title: 'Morning',
                    start: '2021-08-02T08:00:00',
                    end: '2021-08-02T16:00:00',
                    backgroundColor: 'blue'
                },
                {
                    title: 'Evening',
                    start: '2021-08-02T16:00:00',
                    end: '2021-08-03T00:00:00',
                    backgroundColor: 'orange'
                },
                {
                    title: 'Night',
                    start: '2021-08-03T00:00:00',
                    end: '2021-08-03T08:00:00',
                    backgroundColor: 'grey'
                },
                {
                    title: 'Morning',
                    start: '2021-08-03T08:00:00',
                    end: '2021-08-03T16:00:00',
                    backgroundColor: 'blue'
                },
                {
                    title: 'Evening',
                    start: '2021-08-03T16:00:00',
                    end: '2021-08-04T00:00:00',
                    backgroundColor: 'orange'
                },
                {
                    title: 'Night',
                    start: '2021-08-04T00:00:00',
                    end: '2021-08-04T08:00:00',
                    backgroundColor: 'grey'
                },
                {
                    title: 'Morning',
                    start: '2021-08-04T08:00:00',
                    end: '2021-08-04T16:00:00',
                    backgroundColor: 'blue'
                },
                {
                    title: 'Evening',
                    start: '2021-08-04T16:00:00',
                    end: '2021-08-05T00:00:00',
                    backgroundColor: 'orange'
                },
                {
                    title: 'Night',
                    start: '2021-08-05T00:00:00',
                    end: '2021-08-05T08:00:00',
                    backgroundColor: 'grey'
                },
                {
                    title: 'Morning',
                    start: '2021-08-05T08:00:00',
                    end: '2021-08-05T16:00:00',
                    backgroundColor: 'blue'
                },
                {
                    title: 'Evening',
                    start: '2021-08-05T16:00:00',
                    end: '2021-08-06T00:00:00',
                    backgroundColor: 'orange'
                },
                {
                    title: 'Night',
                    start: '2021-08-06T00:00:00',
                    end: '2021-08-06T08:00:00',
                    backgroundColor: 'grey'
                },
                {
                    title: 'Morning',
                    start: '2021-08-06T08:00:00',
                    end: '2021-08-06T16:00:00',
                    backgroundColor: 'blue'
                },
                {
                    title: 'Evening',
                    start: '2021-08-06T16:00:00',
                    end: '2021-08-07T00:00:00',
                    backgroundColor: 'orange'
                },
                {
                    title: 'Night',
                    start: '2021-08-07T00:00:00',
                    end: '2021-08-07T08:00:00',
                    backgroundColor: 'grey'
                },{
                    title: 'Morning',
                    start: '2021-08-09T08:00:00',
                    end: '2021-08-09T16:00:00',
                    backgroundColor: 'blue'
                },
                {
                    title: 'Evening',
                    start: '2021-08-09T16:00:00',
                    end: '2021-08-10T00:00:00',
                    backgroundColor: 'orange'
                },
                {
                    title: 'Night',
                    start: '2021-08-10T00:00:00',
                    end: '2021-08-10T08:00:00',
                    backgroundColor: 'grey'
                },
                {
                    title: 'Morning',
                    start: '2021-08-10T08:00:00',
                    end: '2021-08-10T16:00:00',
                    backgroundColor: 'blue'
                },
                {
                    title: 'Evening',
                    start: '2021-08-10T16:00:00',
                    end: '2021-08-11T00:00:00',
                    backgroundColor: 'orange'
                },
                {
                    title: 'Night',
                    start: '2021-08-11T00:00:00',
                    end: '2021-08-11T08:00:00',
                    backgroundColor: 'grey'
                },
                {
                    title: 'Morning',
                    start: '2021-08-11T08:00:00',
                    end: '2021-08-11T16:00:00',
                    backgroundColor: 'blue'
                },
                {
                    title: 'Evening',
                    start: '2021-08-11T16:00:00',
                    end: '2021-08-12T00:00:00',
                    backgroundColor: 'orange'
                },
                {
                    title: 'Night',
                    start: '2021-08-12T00:00:00',
                    end: '2021-08-12T08:00:00',
                    backgroundColor: 'grey'
                },
                {
                    title: 'Morning',
                    start: '2021-08-12T08:00:00',
                    end: '2021-08-12T16:00:00',
                    backgroundColor: 'blue'
                },
                {
                    title: 'Evening',
                    start: '2021-08-12T16:00:00',
                    end: '2021-08-13T00:00:00',
                    backgroundColor: 'orange'
                },
                {
                    title: 'Night',
                    start: '2021-08-13T00:00:00',
                    end: '2021-08-13T08:00:00',
                    backgroundColor: 'grey'
                },
                {
                    title: 'Morning',
                    start: '2021-08-13T08:00:00',
                    end: '2021-08-13T16:00:00',
                    backgroundColor: 'blue'
                },
                {
                    title: 'Evening',
                    start: '2021-08-13T16:00:00',
                    end: '2021-08-14T00:00:00',
                    backgroundColor: 'orange'
                },
                {
                    title: 'Night',
                    start: '2021-08-14T00:00:00',
                    end: '2021-08-14T08:00:00',
                    backgroundColor: 'grey'
                },{
                    title: 'Morning',
                    start: '2021-08-16T08:00:00',
                    end: '2021-08-16T16:00:00',
                    backgroundColor: 'blue'
                },
                {
                    title: 'Evening',
                    start: '2021-08-16T16:00:00',
                    end: '2021-08-17T00:00:00',
                    backgroundColor: 'orange'
                },
                {
                    title: 'Night',
                    start: '2021-08-17T00:00:00',
                    end: '2021-08-17T08:00:00',
                    backgroundColor: 'grey'
                },
                {
                    title: 'Morning',
                    start: '2021-08-17T08:00:00',
                    end: '2021-08-17T16:00:00',
                    backgroundColor: 'blue'
                },
                {
                    title: 'Evening',
                    start: '2021-08-17T16:00:00',
                    end: '2021-08-18T00:00:00',
                    backgroundColor: 'orange'
                },
                {
                    title: 'Night',
                    start: '2021-08-18T00:00:00',
                    end: '2021-08-18T08:00:00',
                    backgroundColor: 'grey'
                },
                {
                    title: 'Morning',
                    start: '2021-08-18T08:00:00',
                    end: '2021-08-18T16:00:00',
                    backgroundColor: 'blue'
                },
                {
                    title: 'Evening',
                    start: '2021-08-18T16:00:00',
                    end: '2021-08-19T00:00:00',
                    backgroundColor: 'orange'
                },
                {
                    title: 'Night',
                    start: '2021-08-19T00:00:00',
                    end: '2021-08-19T08:00:00',
                    backgroundColor: 'grey'
                },
                {
                    title: 'Morning',
                    start: '2021-08-19T08:00:00',
                    end: '2021-08-19T16:00:00',
                    backgroundColor: 'blue'
                },
                {
                    title: 'Evening',
                    start: '2021-08-19T16:00:00',
                    end: '2021-08-20T00:00:00',
                    backgroundColor: 'orange'
                },
                {
                    title: 'Night',
                    start: '2021-08-20T00:00:00',
                    end: '2021-08-20T08:00:00',
                    backgroundColor: 'grey'
                },
                {
                    title: 'Morning',
                    start: '2021-08-20T08:00:00',
                    end: '2021-08-20T16:00:00',
                    backgroundColor: 'blue'
                },
                {
                    title: 'Evening',
                    start: '2021-08-20T16:00:00',
                    end: '2021-08-21T00:00:00',
                    backgroundColor: 'orange'
                },
                {
                    title: 'Night',
                    start: '2021-08-21T00:00:00',
                    end: '2021-08-21T08:00:00',
                    backgroundColor: 'grey'
                }
            ],
            eventClick: function(calEvent, jsEvent, view) {
                //alert('Event: ' + calEvent.title);
                //alert('Coordinates: ' + jsEvent.pageX + ',' + jsEvent.pageY);
                //alert('View: ' + view.name);
                // change the border color just for fun
            }
        });
        calendar.render();
    };

    this.setModalAttendanceShiftClass = function (_modalAttendanceShiftClass) {
        modalAttendanceShiftClass = _modalAttendanceShiftClass;
    };

    this.getPointCategoryName = function(point, dimension) {
        const series = point.series,
            isY = dimension === 'y',
            axis = series[isY ? 'yAxis' : 'xAxis'];
        return axis.categories[point[isY ? 'y' : 'x']];
    }
}