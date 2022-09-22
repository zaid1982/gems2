function SectionAttendanceParticipant () {

    const className = 'SectionAttendanceParticipant';
    let self = this;
    let classFrom = {};
    let selectorCalendar;
    let calendarMonth;
    let dataCalendar = [];
    let attParticipantId = 0;
    let currentYear = 0;
    let currentMonth = 0;

    this.init = function () {
        self.hideMain();

        $('#btnSapBack').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.hideMain();
                    classFrom.showMain();
                    $(window).scrollTop(0);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        const dataChartAttendance = [
            {y: 14, color: 'yellowgreen'},
            {y: 12, color: 'red'},
            {y: 4, color: 'RosyBrown'}
        ];
        const dataChartLeave = [
            {y: 21, color: 'LightSkyBlue'},
            {y: 14, color: 'Salmon'},
            {y: 12, color: 'Tan'},
            {y: 3, color: 'SlateGray'}
        ];
        const dataChartTime = [
            {name: 'No Data', data: [{y: 6, color: 'DarkGray'}, {y: 4, color: 'DarkGray'}]},
            {name: 'Before', data: [{y: 2, color: 'LimeGreen'}, {y: 4, color: 'LightCoral'}]},
            {name: 'After', data: [{y: 4, color: 'LightCoral'}, {y: 5, color: 'LimeGreen'}]}
        ];
        const dataChartPerimeter = [
            {name: 'No Data', data: [{y: 6, color: 'DarkGray'}, {y: 4, color: 'DarkGray'}]},
            {name: 'Outside', data: [{y: 4, color: 'LightCoral'}, {y: 5, color: 'LightCoral'}]},
            {name: 'Inside', data: [{y: 2, color: 'LimeGreen'}, {y: 4, color: 'LimeGreen'}]}
        ];
        self.generateChartColumn('chartSapStatus', 'Total Attendance', ['Present', 'Absent', 'Leave'], dataChartAttendance);
        self.generateChartColumn('chartSapLeaveType', 'Total Leave', ['Medical', 'Annual', 'Rest', 'Off'], dataChartLeave);
        self.generateChartBar('chartSapCheckIn', 'Time Validity', dataChartTime);
        self.generateChartBar('chartSapCheckOut', 'Perimeter Validity', dataChartPerimeter);
        self.generateChartGauge('chartSapWorkingHours', 'Total Working Hours', 123, 23);
        self.generateCalendar('chartSapCalendar');

        const divCardCalendar = $('#chartSapCalendar').height();
        //console.log(divCardCalendar);
        $('#mapSapLocation').css('minHeight', divCardCalendar+'px');

        window.scrollTo({top: 0, behavior: 'smooth'});
    };

    this.load = function (_attParticipantId, _year, _month) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_attParticipantId, _year, _month]);
                attParticipantId = _attParticipantId;
                currentYear = _year;
                currentMonth = _month;

                classFrom.hideMain();
                self.showMain();
                window.scrollTo({top: 0, behavior: 'smooth'});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.generateChartColumn = function (chartId, title, categories, data) {
        Highcharts.chart(chartId, {
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
                text: title
            },
            xAxis: {
                categories: categories
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
                name: 'Attendance Result',
                data: data
            }]
        });
    };

    this.generateChartBar = function (chartId, title, data) {
        Highcharts.chart(chartId, {
            chart: {
                type: 'bar',
            },
            exporting: {
                enabled: false
            },
            title: {
                text: ''
            },
            subtitle: {
                text: title
            },
            xAxis: {
                categories: ['Clock In', 'Clock Out']
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
                series: {
                    stacking: 'normal',
                    pointPadding: 0,
                    groupPadding: 0.1
                },
                bar: {
                    stacking: 'percent',
                    dataLabels: {
                        enabled: true,
                        format: '{series.name} - {y}',
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
            series: data
        });
    };

    this.generateChartGauge = function (chartId, title, onTime, late) {
        const perc = onTime + late === 0 ? 0 : Math.round(onTime/(onTime+late)*100);
        Highcharts.chart(chartId, {
            chart: {
                type: 'pie'
            },
            exporting: {
                enabled: false
            },
            title: {
                text: '1,244 hr<br>32.3 %',
                style: { "fontSize": "14px" },
                align: 'center',
                verticalAlign: 'middle',
                y: 80
            },
            subtitle: {
                text: title
            },
            plotOptions: {
                pie: {
                    dataLabels: {
                        enabled: false
                    },
                    startAngle: -90,
                    endAngle: 90,
                    center: ['50%', '100%'],
                    size: '190%'
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                type: 'pie',
                name: title,
                innerSize: '50%',
                data: [
                    {name:'Total PPM On-Time', y:onTime, color: 'DeepSkyBlue'},
                    {name:'Total PPM Late', y:late, color: 'PowderBlue'}
                ]
            }]
        });
    };

    this.generateCalendar = function (id) {
        selectorCalendar = $('#'+id);
        const todayDate = moment(); //moment('2020/2/1', 'YYYY/M/DD');
        calendarMonth = moment(todayDate.format('YYYY')+'-'+todayDate.format('MM'));
        this.displayCalendar();
        this.displayCalendarDetails(todayDate.format('YYYY-MM-DD'));
    };

    this.nextMonth = function () {
        calendarMonth.add(1, 'month');
        this.displayCalendar();
    };

    this.previousMonth = function () {
        calendarMonth.subtract(1, 'month');
        this.displayCalendar();
    };

    this.setDataCalendar =  function () {
        const dataDb = mzAjaxRequest('inspection_pengguna/list_monthly/'+calendarMonth.format('YYYY')+'/'+calendarMonth.format('M'), 'GET');
        dataCalendar = {};
        $.each(dataDb, function(n, u) {
            let thisDateArr = [];
            if (typeof dataCalendar[u['inspectionPenggunaScheduleDate']] !== 'undefined') {
                thisDateArr = dataCalendar[u['inspectionPenggunaScheduleDate']];
            }
            thisDateArr.push(u);
            dataCalendar[u['inspectionPenggunaScheduleDate']] = thisDateArr;
        });
    };

    this.displayCalendarDetails = function (_date){
        const selectedDate = moment(_date, 'YYYY-MM-DD');
        let selectorDetails = $('#divCalendarDetails');
        selectorDetails.html('');
        selectorDetails.append(
            $('<h6/>').addClass('mt-2 mb-2 font-weight-bold').text(selectedDate.format('D MMMM YYYY'))
        );
        if (typeof dataCalendar[_date] === 'undefined') {
            selectorDetails.append(
                $('<p/>').addClass('text-muted my-0').html('- Status : <b><span class="text-success">Present</span></b>')
            );
            selectorDetails.append(
                $('<p/>').addClass('text-muted my-0').html('- Clock in : <b><span class="text-success">12:30 pm</span> - <span class="text-danger">Outside</span></b>')
            );
            selectorDetails.append(
                $('<p/>').addClass('text-muted my-0').html('- Clock : <b><span class="text-success">12:30 pm</span> - <span class="text-success">Inside</span></b>')
            );
        } else {
            $.each(dataCalendar[_date], function(n, u) {
                if (u['inspectionPenggunaStatus'] === '19') {
                    selectorDetails.append(
                        $('<div/>').addClass('form-check teal-checkbox pl-0 mb-1').append(
                            $('<input/>').attr('type', 'checkbox').addClass('form-check-input filled-in').attr('id', 'chkCalendarCompany'+n).attr('disabled', 'true')
                        ).append(
                            $('<label/>').addClass('form-check-label').attr('for', 'chkCalendarCompany'+n).text(u['inspectionPenggunaName'])
                        )
                    );
                } else {
                    selectorDetails.append(
                        $('<div/>').addClass('form-check teal-checkbox pl-0 mb-1').append(
                            $('<input/>').attr('type', 'checkbox').addClass('form-check-input filled-in').attr('id', 'chkCalendarCompany'+n).attr('checked', 'true').attr('disabled', 'true')
                        ).append(
                            $('<label/>').addClass('form-check-label').attr('for', 'chkCalendarCompany'+n).text(u['inspectionPenggunaName'])
                        )
                    );
                }
            });
        }
        selectorDetails.show();
    };

    this.displayCalendar = function () {
        //this.setDataCalendar();
        selectorCalendar.html('');
        const days = Array.from({length: calendarMonth.daysInMonth()}, (x, i) => moment().startOf('month').add(i, 'days').format('D'));
        let selectorDate = $('<ul/>').addClass('list-unstyled days');
        const startMonth = calendarMonth.startOf('month').format('d');
        for (let i=0; i<parseInt(startMonth); i++) {
            selectorDate.append($('<li/>').text(''));
        }
        $.each(days, function(n, u) {
            const thisDate = moment(calendarMonth.format('YYYY')+'-'+calendarMonth.format('MM')+'-'+u, 'YYYY-MM-D');
            if (typeof dataCalendar[thisDate.format('YYYY-MM-DD')] !== 'undefined') {
                selectorDate.append($('<li/>').addClass('active pink darken-3 rounded-left rounded-right').attr('onclick', 'mzChartsClass_.displayCalendarDetails("'+thisDate.format('YYYY-MM-DD')+'");').html('<a>'+u+'</a>'));
            } else {
                selectorDate.append($('<li/>').text(u));
            }
        });

        selectorCalendar.append(
            $('<div/>').addClass('card-body rounded-top white-text pr-0').append(
                $('<h6/>').addClass('font-weight-bold text-center text-white mt-0 mb-3').html('Monthly Attendance Calendar')
            ).append(
                $('<div/>').addClass('my-0').append(
                    $('<ul/>').addClass('list-unstyled d-flex justify-content-between mr-4 mb-0').append(
                        $('<li/>').addClass('h6').text(calendarMonth.format('MMMM'))
                    ).append(
                        $('<li/>').addClass('h6').text(calendarMonth.format('YYYY'))
                    )
                )
            ).append(
                $('<ul/>').addClass('list-unstyled weekdays text-white-50 my-0').append(
                    $('<li/>').text('Sun')
                ).append(
                    $('<li/>').text('Mon')
                ).append(
                    $('<li/>').text('Tue')
                ).append(
                    $('<li/>').text('Wed')
                ).append(
                    $('<li/>').text('Thu')
                ).append(
                    $('<li/>').text('Fri')
                ).append(
                    $('<li/>').text('Sat')
                )
            ).append(
                selectorDate
            )
        ).append(
            $('<div/>').addClass('card card-form-2 red lighten-5').append(
                $('<div/>').addClass('card-body').attr('id', 'divCalendarDetails')
            )
        );
        $('#divCalendarDetails').hide();
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.showMain = function () {
        $('.sectionAttendanceParticipant').show();
    };

    this.hideMain = function () {
        $('.sectionAttendanceParticipant').hide();
    };
}