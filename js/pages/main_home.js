function MainHome() {

    const className = 'MainHome';
    let self = this;
    let refClient;
    let refSite;
    let refContract;
    let clientId = '1';
    let siteId = '1';
    let currentMonth;
    let currentYear;
    const monthFull = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    this.init = function () {
        $('#divHmeTopStats').hide();
        $.each(refClient, function (_clientId, _client) {
            if (typeof _client !== 'undefined') {
                $('#divHmeClient').append('<a class="dropdown-item lnkHmeClient" href="#" id="lnkHmeClient_'+_clientId+'">'+_client['clientName']+'</a>');
            }
        });

        $('.lnkHmeClient').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            ShowLoader();
            setTimeout(function () {
                try {
                    if (linkIndex > 0) {
                        clientId = linkId.substr(linkIndex + 1);
                        self.generateTotalAsset();
                        self.generateTotalPpmTask();
                        self.generateTotalPpmLate();
                        self.generatePercPpmDone();
                        $('#lblHmeSelected').html('<i>'+refClient[clientId]['clientName']+', '+monthFull[currentMonth]+' '+currentYear+'</i>');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        let dateEarliest = new Date();
        dateEarliest.setFullYear(2019, 3, 1);
        let dateCtr = new Date();
        currentMonth = dateCtr.getMonth();
        currentYear = dateCtr.getFullYear();
        dateCtr.setDate(1);
        for (let i = 0; i < 100; i++) {
            if (dateCtr < dateEarliest) {
                break;
            }
            const isActive = (dateCtr.getMonth() === currentMonth && dateCtr.getFullYear() === currentYear) ? 'active text-white' : '';
            $('#divHmeMonth').append('<a class="dropdown-item lnkHmeMonth '+isActive+'" href="#" id="lnkHmeMonth_'+dateCtr.getFullYear()+dateCtr.getMonth()+'">'+monthFull[dateCtr.getMonth()] + ' ' + dateCtr.getFullYear() + '</a>');
            dateCtr.setMonth(dateCtr.getMonth() - 1);
        }

        $('.lnkHmeMonth').off('click').on('click', function () {ShowLoader();
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            setTimeout(function () {
                try {
                    if (linkIndex > 0) {
                        const year = linkId.substr(linkIndex + 1, 4);
                        const month = linkId.substr(linkIndex + 1 + 4);
                        $('#lnkHmeMonth_'+currentYear+currentMonth).removeClass('active');
                        $('#lnkHmeMonth_'+currentYear+currentMonth).removeClass('text-white');
                        $('#lnkHmeMonth_'+year+month).addClass('active');
                        $('#lnkHmeMonth_'+year+month).addClass('text-white');
                        currentMonth = month;
                        currentYear = year;
                        self.generateTotalPpmTask();
                        self.generateTotalPpmLate();
                        self.generatePercPpmDone();
                        $('#lblHmeSelected').html('<i>'+refClient[clientId]['clientName']+', '+monthFull[currentMonth]+' '+currentYear+'</i>');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('#lblHmeSelected').html('<i>'+refClient[clientId]['clientName']+', '+monthFull[currentMonth]+' '+currentYear+'</i>');
        //self.generateTotalAsset();
        //self.generateTotalPpmTask();
        //self.generateTotalPpmLate();
        //self.generatePercPpmDone();
        self.generateChartHme1();
        self.generateChartHme2();
        self.generateChartHme3();
        self.generateChartHme4();
        self.generateChartHme5();
    };

    this.generateTotalAsset = function () {
        const totalAsset = mzAjaxRequest('asset.php?type=total_asset&clientId='+clientId, 'GET');
        $('#lblHmeTotalAsset').html(mzFormatNumber(totalAsset));
    };

    this.generateTotalPpmTask = function () {
        const totalppmTask = mzAjaxRequest('ppm.php?type=total_ppm_task&clientId='+clientId+'&year='+currentYear+'&month='+currentMonth, 'GET');
        $('#lblHmeTotalPpm').html(mzFormatNumber(totalppmTask));
        $('#lblHmeTotalPpmTitle').html('Total PPM <br/><strong>'+monthFull[currentMonth]+' '+currentYear+'</strong>');
    };

    this.generateTotalPpmLate = function () {
        const totalppmLate = mzAjaxRequest('ppm.php?type=total_ppm_late&clientId='+clientId+'&year='+currentYear+'&month='+currentMonth, 'GET');
        $('#lblHmeTotalPpmLate').html(mzFormatNumber(totalppmLate));
        $('#lblHmeTotalPpmLateTitle').html('Total Late PPM <br/><strong>'+monthFull[currentMonth]+' '+currentYear+'</strong>');
    };

    this.generatePercPpmDone = function () {
        const percPpmDone = mzAjaxRequest('ppm.php?type=perc_ppm_done&clientId='+clientId+'&year='+currentYear+'&month='+currentMonth, 'GET');
        const percDone = mzFormatNumber(percPpmDone,2)+'%';
        $('#lblHmePercPpmDone').html(percDone);
        $('#divBarHmePercAttendance').css('width', percDone);
        $('#lblHmePercPpmDoneTitle').html('PPM done for <strong>'+monthFull[currentMonth]+' '+currentYear+'</strong>');
    };

    this.generateChartHme1 = function () {
        Highcharts.chart('chartHme1', {
            chart: {
                type: 'column'
            },
            title: {
                text: 'WO By Site'
            },
            subtitle: {
                text: 'Total Work Order Status by Site'
            },
            xAxis: {
                categories: ['HQ', 'Suasana Kijang', 'Tunas Kijang', 'Data Center'],
                title: {
                    text: ''
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Total Work Order'
                },
                labels: {
                    overflow: 'justify'
                }
            },
            tooltip: {
                valueSuffix: ' millions'
            },
            plotOptions: {
                column: {
                    dataLabels: {
                        enabled: true
                    },
                    borderRadius: 5,
                    borderWidth: 0
                },
                series: {
                    events: {
                        legendItemClick: function () {
                            var visibility = this.visible ? 'visible' : 'hidden';
                            if (!confirm('The series is currently ' +
                                visibility + '. Do you want to change that?')) {
                                return false;
                            }
                        }
                    }
                }
            },
            /*legend: {
                layout: 'vertical',
                align: 'right',
                verticalAlign: 'top',
                x: -10,
                y: 40,
                floating: true,
                borderWidth: 1,
                backgroundColor:
                    Highcharts.defaultOptions.legend.backgroundColor || '#FFFFFF',
                shadow: true
            },*/
            credits: {
                enabled: false
            },
            series: [{
                name: 'Completed',
                data: [107, 31, 635, 1203]
            }, {
                name: 'Responding',
                data: [133, 156, 947, 2408]
            }, {
                name: 'In Progress',
                data: [814, 841, 3714, 1727]
            }, {
                name: 'Cancelled',
                data: [1216, 1001, 4436, 2738]
            }]
        });
    };

    this.generateChartHme2 = function () {
        Highcharts.chart('chartHme2', {
            chart: {
                type: 'column'
            },
            title: {
                text: 'WO By Category'
            },
            subtitle: {
                text: 'Total Work Order by Category'
            },
            xAxis: {
                categories: [
                    'HQ',
                    'Suasana Kijang',
                    'Tunas Kijang',
                    'Data Center'
                ],
                crosshair: true
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Total Work Order'
                }
            },
            tooltip: {
                headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
                pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                    '<td style="padding:0"><b>{point.y:.1f} mm</b></td></tr>',
                footerFormat: '</table>',
                shared: true,
                useHTML: true
            },
            plotOptions: {
                column: {
                    dataLabels: {
                        enabled: true
                    },
                    borderWidth: 0,
                    borderRadius: 5
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Complaint',
                data: [49.9, 71.5, 106.4, 129.2]

            }, {
                name: 'Request',
                data: [83.6, 78.8, 98.5, 93.4]

            }, {
                name: 'Pro Active',
                data: [48.9, 38.8, 39.3, 41.4]

            }, {
                name: 'Breakdown',
                data: [42.4, 33.2, 34.5, 39.7],
                //pointPlacement: 0.05

            }]
        });
    };

    this.generateChartHme3 = function () {
        Highcharts.chart('chartHme3', {
            chart: {
                type: 'pie'
            },
            title: {
                text: 'Work Order Type'
            },
            subtitle: {
                text: 'Total Work Order by Type'
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        distance: -20
                    },
                    showInLegend: true,
                    borderWidth: 0
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Type',
                colorByPoint: true,
                innerSize: '30%',
                data: [
                {
                    name: 'Self Finding',
                    y: 14,
                    sliced: true,
                    selected: true
                }, {
                    name: 'Complaint',
                    y: 16
                }, {
                    name: 'Request',
                    y: 3
                }, {
                    name: 'Breakdown',
                    y: 4
                }, {
                    name: 'Defect',
                    y: 1
                }]
            }]
        });
    };

    this.generateChartHme4 = function () {
        Highcharts.chart('chartHme4', {
            chart: {
                type: 'bar'
            },
            title: {
                text: 'Work Order Progress'
            },
            subtitle: {
                text: 'Total Work Order by Current Progress'
            },
            xAxis: {
                categories: ['Unassigned', 'Cancelled', 'In Progress', 'Responding', 'Completed'],
                title: {
                    text: null
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Total WO',
                    align: 'high'
                },
                labels: {
                    overflow: 'justify'
                }
            },
            legend: {
                enabled: false
            },
            plotOptions: {
                bar: {
                    dataLabels: {
                        enabled: true
                    },
                    borderRadius: 5,
                    borderWidth: 0
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                data: [107, 31, 635, 203, 32],
                color: '#f45b5b'
            }]
        });
    };

    this.generateChartHme5 = function () {
        Highcharts.chart('chartHme5', {
            chart: {
                type: 'pie'
            },
            title: {
                text: 'Trade'
            },
            subtitle: {
                text: 'Total Work Order by Trade'
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        distance: -25
                    },
                    showInLegend: true,
                    borderWidth: 0
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Type',
                colorByPoint: true,
                data: [
                    {
                        name: 'Mechanical',
                        y: 14,
                        sliced: true,
                        selected: true
                    }, {
                        name: 'Electrical',
                        y: 16
                    }, {
                        name: 'Civil',
                        y: 3
                    }, {
                        name: 'Custodial',
                        y: 4
                    }]
            }]
        });
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefContract = function (_refContract) {
        refContract = _refContract;
    };

}