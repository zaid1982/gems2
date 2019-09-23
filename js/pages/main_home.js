function MainHome() {

    const className = 'MainHome';
    let self = this;
    let refClient;
    let refSite;
    let refContract;
    let refUser;
    let refPpmGroup;
    let refStatus;
    let clientId = '1';
    let siteId = '0';
    let currentMonth;
    let currentYear;
    let reportId = '2';
    let reportType = 'Work Order';
    let oTableWo;
    const monthFull = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    this.init = function () {
        $('.divHmeTopStats_ppm').hide();
        refSite[0] = {clientId:'1', siteDesc:'Overall'};
        $('#lnkHmeReportType_2').addClass('active').addClass('text-white');

        $.each(refClient, function (_clientId, _client) {
            if (typeof _client !== 'undefined') {
                $('#divHmeClient').append('<a class="dropdown-item lnkHmeClient" href="#" id="lnkHmeClient_'+_clientId+'">'+_client['clientName']+'</a>');
            }
        });
        $('#lnkHmeClient_'+clientId).addClass('active').addClass('text-white');

        $('.lnkHmeClient').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            ShowLoader();
            setTimeout(function () {
                try {
                    if (linkIndex > 0) {
                        $('#lnkHmeClient_'+clientId).removeClass('active').removeClass('text-white');
                        clientId = linkId.substr(linkIndex + 1);
                        $('#lnkHmeClient_'+clientId).addClass('active').addClass('text-white');
                        siteId = '0';
                        self.setOptionSite();
                        if (reportId === '1') {
                            $('.divHmeTopStats_ppm').show();
                            self.generateTotalAsset();
                            self.generateTotalPpmTask();
                            self.generateTotalPpmLate();
                            self.generatePercPpmDone();
                        } else if (reportId === '2') {
                            self.generateChartWoBySite();
                            self.generateChartWoByCategory();
                            self.generateChartWoByType();
                            self.generateChartWoByProgress();
                            self.generateChartWoByTrade();
                            self.genTableHmeDataWo();
                        }
                        $('#lblHmeSelected').html(reportType+' <i>('+refClient[clientId]['clientName']+' - '+refSite[siteId]['siteDesc']+', '+monthFull[currentMonth]+' '+currentYear+')</i>');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        let dateEarliest = new Date();
        dateEarliest.setFullYear(2019, 8, 1);
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

        $('.lnkHmeMonth').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            ShowLoader();
            setTimeout(function () {
                try {
                    if (linkIndex > 0) {
                        const year = linkId.substr(linkIndex + 1, 4);
                        const month = linkId.substr(linkIndex + 1 + 4);
                        $('#lnkHmeMonth_'+currentYear+currentMonth).removeClass('active').removeClass('text-white');
                        $('#lnkHmeMonth_'+year+month).addClass('active').addClass('text-white');
                        currentMonth = month;
                        currentYear = year;
                        if (reportId === '1') {
                            $('.divHmeTopStats_ppm').show();
                            self.generateTotalAsset();
                            self.generateTotalPpmTask();
                            self.generateTotalPpmLate();
                            self.generatePercPpmDone();
                        } else if (reportId === '2') {
                            self.generateChartWoBySite();
                            self.generateChartWoByCategory();
                            self.generateChartWoByType();
                            self.generateChartWoByProgress();
                            self.generateChartWoByTrade();
                            self.genTableHmeDataWo();
                        }
                        $('#lblHmeSelected').html(reportType+' <i>('+refClient[clientId]['clientName']+' - '+refSite[siteId]['siteDesc']+', '+monthFull[currentMonth]+' '+currentYear+')</i>');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('.lnkHmeReportType').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            ShowLoader();
            setTimeout(function () {
                try {
                    if (linkIndex > 0) {
                        $('#lnkHmeReportType_'+reportId).removeClass('active').removeClass('text-white');
                        reportId = linkId.substr(linkIndex + 1);
                        $('#lnkHmeReportType_'+reportId).addClass('active').addClass('text-white');
                        siteId = '0';
                        if (reportId === '1') {
                            reportType = 'PPM';
                            $('.divHmeTopStats_ppm').show();
                            self.generateTotalAsset();
                            //self.generateTotalPpmTask();
                            //self.generateTotalPpmLate();
                            //self.generatePercPpmDone();
                        } else if (reportId === '2') {
                            reportType = 'Work Order';
                            $('.divHmeTopStats_wo').hide();
                            self.generateChartWoBySite();
                            self.generateChartWoByCategory();
                            self.generateChartWoByType();
                            self.generateChartWoByProgress();
                            self.generateChartWoByTrade();
                            self.genTableHmeDataWo();
                        }
                        $('#lblHmeSelected').html(reportType+' <i>('+refClient[clientId]['clientName']+' - '+refSite[siteId]['siteDesc']+', '+monthFull[currentMonth]+' '+currentYear+')</i>');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        self.setOptionSite();

        oTableWo =  $('#dtHmeDataWo').DataTable({
            bLengthChange: false,
            bFilter: true,
            //"aaSorting": [1, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableWo.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();

            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'woTaskTimeCreated', mRender: function (data, type, row){
                            return data.substr(0, 10);
                        }},
                    {mData: 'woTaskNo'},
                    {mData: null, mRender: function (data, type, row){
                            return refSite[row['siteId']]['siteDesc'];
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return refUser[row['woTaskCreatedBy']]['userFirstName'];
                        }},
                    {mData: 'woTaskComplaint'},
                    {mData: null, mRender: function (data, type, row){
                            return row['ppmGroupId'] !== '' ? refPpmGroup[row['ppmGroupId']]['ppmGroupName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['woTaskAssignedTo'] !== '' ? refUser[row['woTaskAssignedTo']]['userFirstName'] : '';
                        }},
                    {mData: 'woTaskTypeDesc'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['woTaskStatus']]['statusColor']+' z-depth-2">'+refStatus[row['woTaskStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: 'siteId', visible: false},
                    {mData: 'ppmGroupId', visible: false},
                    {mData: 'woTaskStatus', visible: false},
                    {mData: 'woTaskType', visible: false}
                ]
        });
        $("#dtHmeDataWo_filter").hide();
        $('#txtHmeDataWoSearch').on('keyup change', function () {
            oTableWo.search($(this).val()).draw();
        });

        let cntWo;
        let btnWoOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntWo = 1;
                        }
                        if (column === 9) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntWo++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableWo, {
            buttons: [
                $.extend( true, {}, btnWoOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Work Order List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnWoOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Work Order List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnWoOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Work Order List',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtHmeDataWoExport'));

        $('#btnDtHmeDataWoRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableHmeDataWo();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('#lblHmeSelected').html(reportType+' <i>('+refClient[clientId]['clientName']+' - '+refSite[siteId]['siteDesc']+', '+monthFull[currentMonth]+' '+currentYear+')</i>');

        if (reportId === '1') {
            $('.divHmeTopStats_ppm').show();
            self.generateTotalAsset();
            self.generateTotalPpmTask();
            self.generateTotalPpmLate();
            self.generatePercPpmDone();
        } else if (reportId === '2') {
            self.generateChartWoBySite();
            self.generateChartWoByCategory();
            self.generateChartWoByType();
            self.generateChartWoByProgress();
            self.generateChartWoByTrade();
            self.genTableHmeDataWo();
        }
    };

    this.setOptionSite = function () {
        siteId = '0';
        $('#divHmeSite').html('');
        $.each(refSite, function (_siteId, _site) {
            if (typeof _site !== 'undefined') {
                if (_site['clientId'] === clientId) {
                    $('#divHmeSite').append('<a class="dropdown-item lnkHmeSite" href="#" id="lnkHmeSite_'+_siteId+'">'+_site['siteDesc']+'</a>');
                }
            }
        });
        $('#lnkHmeSite_'+siteId).addClass('active').addClass('text-white');

        $('.lnkHmeSite').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            ShowLoader();
            setTimeout(function () {
                try {
                    if (linkIndex > 0) {
                        $('#lnkHmeSite_'+siteId).removeClass('active').removeClass('text-white');
                        siteId = linkId.substr(linkIndex + 1);
                        $('#lnkHmeSite_'+siteId).addClass('active').addClass('text-white');

                        if (reportId === '1') {
                            $('.divHmeTopStats_ppm').show();
                            self.generateTotalAsset();
                            self.generateTotalPpmTask();
                            self.generateTotalPpmLate();
                            self.generatePercPpmDone();
                        } else if (reportId === '2') {
                            self.generateChartWoBySite();
                            self.generateChartWoByCategory();
                            self.generateChartWoByType();
                            self.generateChartWoByProgress();
                            self.generateChartWoByTrade();
                            self.genTableHmeDataWo();
                        }
                        $('#lblHmeSelected').html(reportType+' <i>('+refClient[clientId]['clientName']+' - '+refSite[siteId]['siteDesc']+', '+monthFull[currentMonth]+' '+currentYear+')</i>');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });
    };

    this.genTableHmeDataWo = function () {
        const dataWo = mzAjaxRequest('wo.php?type=dashboard_list&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth, 'GET');
        oTableWo.clear().rows.add(dataWo).draw();
    };

    this.generateTotalAsset = function () {
        $.ajax({
            url: 'api/asset.php?type=total_asset&clientId='+clientId+'&siteId='+siteId,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    $('#lblHmeTotalAsset').html(mzFormatNumber(resp.result));
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateTotalPpmTask = function () {
        $.ajax({
            url: 'api/ppm.php?type=total_ppm_task&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    $('#lblHmeTotalPpm').html(mzFormatNumber(resp.result));
                    $('#lblHmeTotalPpmTitle').html('Total PPM <br/><strong>'+monthFull[currentMonth]+' '+currentYear+'</strong>');
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateTotalPpmLate = function () {
        $.ajax({
            url: 'api/ppm.php?type=total_ppm_late&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    $('#lblHmeTotalPpmLate').html(mzFormatNumber(resp.result));
                    $('#lblHmeTotalPpmLateTitle').html('Total Late PPM <br/><strong>'+monthFull[currentMonth]+' '+currentYear+'</strong>');
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generatePercPpmDone = function () {
        $.ajax({
            url: 'api/ppm.php?type=perc_ppm_done&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    const percPpmDone = resp.result;
                    const percDone = mzFormatNumber(percPpmDone,2)+'%';
                    $('#lblHmePercPpmDone').html(percDone);
                    $('#divBarHmePercAttendance').css('width', percDone);
                    $('#lblHmePercPpmDoneTitle').html('PPM done for <strong>'+monthFull[currentMonth]+' '+currentYear+'</strong>');
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartWoBySite = function () {
        $.ajax({
            url: 'api/wo.php?type=total_by_site_status&clientId='+clientId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    let siteDescs = [];
                    let siteIds = resp.result.categories;
                    siteIds.forEach(function(key){
                        siteDescs.push(refSite[key]['siteDesc']);
                    });
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
                            categories: siteDescs,
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
                        },
                        plotOptions: {
                            column: {
                                dataLabels: {
                                    enabled: true
                                },
                                borderRadius: 3,
                                borderWidth: 0
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function(event) {
                                            oTableWo.search('').columns().search('').draw();
                                            oTableWo.column(10).search(siteIds[siteDescs.indexOf(this.category)], false, true, false);
                                            oTableWo.column(12).search(event.point.series.userOptions.woTaskStatus, true, false).draw();
                                        }
                                    }
                                }
                            }
                            /*series: {
                                events: {
                                    legendItemClick: function () {
                                        const visibility = this.visible ? 'visible' : 'hidden';
                                        if (!confirm('The series is currently ' +
                                            visibility + '. Do you want to change that?')) {
                                            return false;
                                        }
                                    }
                                }
                            }*/
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
                        series: resp.result.series
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartWoByCategory = function () {
        $.ajax({
            url: 'api/wo.php?type=total_by_site_type&clientId='+clientId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    let siteDescs = [];
                    let siteIds = resp.result.categories;
                    siteIds.forEach(function(key){
                        siteDescs.push(refSite[key]['siteDesc']);
                    });
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
                            categories: siteDescs,
                            crosshair: true
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Total Work Order'
                            }
                        },
                        tooltip: {
                        },
                        /*tooltip: {
                            headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
                            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                                '<td style="padding:0"><b>{point.y}</b></td></tr>',
                            footerFormat: '</table>',
                            shared: true,
                            useHTML: true
                        },*/
                        plotOptions: {
                            column: {
                                dataLabels: {
                                    enabled: true
                                },
                                borderWidth: 0,
                                borderRadius: 3
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function(event) {
                                            oTableWo.search('').columns().search('').draw();
                                            oTableWo.column(10).search(siteIds[siteDescs.indexOf(this.category)], false, true, false);
                                            oTableWo.column(13).search(event.point.series.userOptions.woTaskType, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: resp.result.series
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartWoByType = function () {
        $.ajax({
            url: 'api/wo.php?type=total_by_type&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
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
                            pointFormat: '{series.name}: <b>{point.y} - {point.percentage:.1f}%</b>'
                        },
                        plotOptions: {
                            pie: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: {
                                    enabled: true,
                                    formatter: function() {
                                        if (this.y != 0) {
                                            return this.y;
                                        } else {
                                            return null;
                                        }
                                    },
                                    //format: '<b>{point.name}</b><br>{point.y}',
                                    distance: -20
                                },
                                showInLegend: true,
                                borderWidth: 0
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function() {
                                            oTableWo.search('').columns().search('').draw();
                                            oTableWo.column(13).search(this.woTaskType, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Total',
                            colorByPoint: true,
                            innerSize: '30%',
                            data: resp.result
                        }]
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartWoByProgress = function () {
        $.ajax({
            url: 'api/wo.php?type=total_by_status&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
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
                            categories: resp.result.categories,
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
                        tooltip: {
                            pointFormat: '{series.name}: <b>{point.y} - {point.percentage:.1f}%</b>'
                        },
                        plotOptions: {
                            bar: {
                                dataLabels: {
                                    enabled: true
                                },
                                borderRadius: 3,
                                borderWidth: 0
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function() {
                                            oTableWo.search('').columns().search('').draw();
                                            oTableWo.column(12).search(this.woTaskStatus, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Total',
                            data: resp.result.data,
                            color: '#f45b5b'
                        }]
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartWoByTrade = function () {
        $.ajax({
            url: 'api/wo.php?type=total_by_group&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
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
                            pointFormat: '{series.name}: <b>{point.y} - {point.percentage:.1f}%</b>'
                        },
                        plotOptions: {
                            pie: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: {
                                    enabled: true,
                                    formatter: function() {
                                        if (this.y != 0) {
                                            return this.y;
                                        } else {
                                            return null;
                                        }
                                    },
                                    //format: '<b>{point.name}</b><br>{point.y}',
                                    distance: -20
                                },
                                showInLegend: true,
                                borderWidth: 0
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function() {
                                            oTableWo.search('').columns().search('').draw();
                                            oTableWo.column(11).search(this.ppmGroupId, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Total',
                            colorByPoint: true,
                            data: resp.result
                        }]
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.getClassName = function () {
        return className;
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

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

}