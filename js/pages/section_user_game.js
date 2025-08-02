function SectionUserGame () {

    const className = 'SectionAttendanceConfigSite';
    let self = this;
    let classFrom;
    let gmiId;
    let userId;
    let refDesignation;
    let refSite;
    let refUser;
    let oTableSugHistory;
    let monthArray;
    const defaultPictureUrl = 'https://mdbootstrap.com/img/Photos/Avatars/img%20(2).jpg';

    this.init = function () {
        $('.sectionUserGame').hide();

        $('#btnSugBack').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    $('.sectionUserGame').hide();
                    classFrom.showMain();
                    $(window).scrollTop(0);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        monthArray = mzGetMonthArray();
        let exportSugHistoryPdf = Object.assign({}, mzExportOpt);
        exportSugHistoryPdf['columns'] = [0, 1, 2, 3, 4, 17, 18, 19, 20, 25, 26, 29];
        oTableSugHistory = $('#dtSugHistory').DataTable({
            bLengthChange: false,
            bFilter: false,
            ordering: false,
            language: _DATATABLE_LANGUAGE,
            autoWidth: false,
            dom: "<'row'<'col-12 px-0'B>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { className: 'text-center', targets: [1, 2] },
                { className: 'text-right', targets: [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'four-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ', text:'<i class="fas fa-print"></i>', title:'GEMS - Performance History', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 ', text:'<i class="fas fa-copy"></i>', title:'GEMS - Performance History', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 ', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Performance History', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 ', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Performance History', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportSugHistoryPdf}
            ],
            aoColumns: [
                {mData: null, mRender: function (data, type, row) {
                        return monthArray[parseInt(row['gmiMonth'])-1]['monthName'] + ' ' + row['gmiYear'];
                    }},
                {mData: 'gmiWoTierName', width: '12%'},
                {mData: 'gmiPpmTierName', width: '12%'},
                {mData: 'gmiWoTierPoint', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data, 1);
                    }},
                {mData: 'gmiPpmTierPoint', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data, 1);
                    }},
                {mData: 'gmiPpmTotal', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPpmCompleted', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPpmOnTime', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPpmWithin', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPpmLate', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPpmAssist', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiWoTotal', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiWoCompleted', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiWoOnTime', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiWoLate', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiWoAssist', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiWoSelfFinding', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: null, width: '8%', mRender: function (data, type, row) {
                        return mzFormatNumber(parseInt(row['gmiWoTotal']) + parseInt(row['gmiPpmTotal']));
                    }},
                {mData: null, width: '8%', mRender: function (data, type, row) {
                        return mzFormatNumber(parseInt(row['gmiWoCompleted']) + parseInt(row['gmiPpmCompleted']));
                    }},
                {mData: null, width: '8%', mRender: function (data, type, row) {
                        return mzFormatNumber(parseInt(row['gmiWoOnTime']) + parseInt(row['gmiPpmOnTime']));
                    }},
                {mData: null, width: '8%', mRender: function (data, type, row) {
                        return mzFormatNumber(parseInt(row['gmiWoLate']) + parseInt(row['gmiPpmLate']));
                    }},
                {mData: 'gmiPointCompleted', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPointOnTime', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPointLate', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPointSelfFinding', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPointTotal', width: '10%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiProductivityLevel', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data, 2) + '%';
                    }},
                {mData: 'gmiProductivityDeduction', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data, 2) + '%';
                    }},
                {mData: 'gmiPointLessProductive', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPointAfterMinus', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }}
            ]
        });
        oTableSugHistory.column(3).visible(false);
        oTableSugHistory.column(4).visible(false);
        oTableSugHistory.column(5).visible(false);
        oTableSugHistory.column(6).visible(false);
        oTableSugHistory.column(7).visible(false);
        oTableSugHistory.column(8).visible(false);
        oTableSugHistory.column(9).visible(false);
        oTableSugHistory.column(10).visible(false);
        oTableSugHistory.column(11).visible(false);
        oTableSugHistory.column(12).visible(false);
        oTableSugHistory.column(13).visible(false);
        oTableSugHistory.column(14).visible(false);
        oTableSugHistory.column(15).visible(false);
        oTableSugHistory.column(16).visible(false);
        oTableSugHistory.column(20).visible(false);
        oTableSugHistory.column(21).visible(false);
        oTableSugHistory.column(22).visible(false);
        oTableSugHistory.column(23).visible(false);
        oTableSugHistory.column(24).visible(false);
        oTableSugHistory.column(27).visible(false);
        oTableSugHistory.column(28).visible(false);

        // Add row click handler for Performance History table
        let oTableSugHistoryTbody = $('#dtSugHistory tbody');
        oTableSugHistoryTbody.delegate('tr', 'click', function (evt) {
            const data = $('#dtSugHistory').DataTable().row(this).data();
            if (data) {
                self.showWoDetails(data['gmiYear'], data['gmiMonth'], data['userId']);
            }
        });
        oTableSugHistoryTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtSugHistory').DataTable().row(this).data();
            if (data) {
                const cell = $(evt.target).closest('td');
                cell.css('cursor', 'pointer');
                cell.attr('data-toggle', 'tooltip');
                cell.attr('title', 'Click to see work order details for ' + monthArray[parseInt(data['gmiMonth'])-1]['monthName'] + ' ' + data['gmiYear']);
                $('[data-toggle="tooltip"]').tooltip();
            }
        });

        // Initialize work order details modal
        $('#modalWoDetails').on('hidden.bs.modal', function () {
            // Cleanup tooltips when modal is closed
            $('[data-toggle="tooltip"]').tooltip('dispose');
        });
    };

    this.load = function (_gmiId, _userId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_gmiId, _userId]);
                gmiId = _gmiId;
                userId = _userId;

                const data = mzAjaxRequest2('gamification/gmi_monthly/'+gmiId, 'GET');
                $('#h3SugPointAddition').html(mzFormatNumber(parseInt(data['gmiPointCompleted'])+parseInt(data['gmiPointOnTime'])));
                $('#h3SugPointDeduction').html(mzFormatNumber(parseInt(data['gmiPointLate'])));
                $('#h3SugPointBonus').html(mzFormatNumber(parseInt(data['gmiPointSelfFinding'])));
                $('#h3SugPointTotal').html(mzFormatNumber(parseInt(data['gmiPointTotal'])));
                $('#h4SugPpmTierName').html(data['gmiPpmTierName']);
                $('#h4SugPpmCompleted').html(mzFormatNumber(data['gmiPpmCompleted']));
                self.generateChartPerformance('chartSugPpmPerformance', 'Plan Preventive Performance', parseInt(data['gmiPpmOnTime']), parseInt(data['gmiPpmLate']));
                self.generateChartPpmLateness('chartSugPpmLateness', parseInt(data['gmiPpmOnTime']), parseInt(data['gmiPpmWithin']), parseInt(data['gmiPpmLate']));
                self.generateChartPpmType('chartSugPpmType', parseInt(data['gmiPpmTotal']), parseInt(data['gmiPpmAssist']))
                $('#h4SugWoTierName').html(data['gmiWoTierName']);
                $('#h4SugWoCompleted').html(mzFormatNumber(data['gmiWoCompleted']));
                $('#h3SugProductivityLevel').html(mzFormatNumber(parseInt(data['gmiProductivityLevel']), 2)+'%');
                $('#h3SugProductivityDeduction').html(mzFormatNumber(parseInt(data['gmiProductivityDeduction']), 2)+'%');
                $('#h3SugLessProductivePoint').html(mzFormatNumber(parseInt(data['gmiPointLessProductive'])));
                $('#h3SugPointAfterMinus').html(mzFormatNumber(parseInt(data['gmiPointAfterMinus'])));
                self.generateChartPerformance('chartSugWoPerformance', 'Work Order', parseInt(data['gmiWoOnTime']), parseInt(data['gmiWoLate']));
                self.generateChartWoLateness('chartSugWoLateness', parseInt(data['gmiWoOnTime']), parseInt(data['gmiWoLate']));
                self.generateChartWoType('chartSugWoType', parseInt(data['gmiWoTotal']), parseInt(data['gmiWoSelfFinding']), parseInt(data['gmiWoAssist']))
                self.loadProfile(userId);

                const dataHistory = mzAjaxRequest2('gamification/gmi_monthly_history/'+data['gmiYear']+'/'+data['gmiMonth']+'/'+userId, 'GET');
                oTableSugHistory.clear().rows.add(dataHistory).draw();
                if (dataHistory.length > 0 && typeof dataHistory[1] !== 'undefined') {
                    const totalDiff = parseInt(data['gmiPointTotal']) - parseInt(dataHistory[1]['gmiPointTotal']);
                    if (totalDiff < 0) {
                        $('#h3SugPerformanceTrending').html('<span class="text-danger">-' + mzFormatNumber(-totalDiff) + '</span> <i class="fas fa-angle-down text-danger"></i>');
                    } else {
                        $('#h3SugPerformanceTrending').html('<span class="text-success">+' + mzFormatNumber(totalDiff) + '</span>  <i class="fas fa-angle-up text-success"></i>');
                    }
                } else {
                    $('#h3SugPerformanceTrending').html(mzFormatNumber(parseInt(data['gmiPointTotal'])) + ' <i class="fas fa-angle-up text-success"></i>');
                }

                $('.sectionUserGame').show();
                classFrom.hideMain();
                /*const divCardWoHeight = $('#divCardWo').height();
                $('#divCardPpm').css('minHeight', divCardWoHeight+'px');*/

                window.scrollTo({top: 0, behavior: 'smooth'});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.loadProfile = function (_userId) {
        const user = mzAjaxRequest('profile.php?userId='+userId, 'GET');
        $('#imgSugProfile').attr('src', user['imgUrl'] !== '' ? user['imgUrl'] : defaultPictureUrl);
        $('#h6SugFullName').html(user['userFullName']);
        $('#pSugFullName').html(user['userFullName']);
        const designation = user['designationId'] !== '' ? refDesignation[user['designationId']]['designationDesc'] : '&nbsp;';
        $('#smallSugDesignation').html(designation);
        $('#pSugDesignation').html(designation);
        $('#pSugSite').html(user['siteId'] !== '' ? refSite[user['siteId']]['siteCode'] : '&nbsp;');
        $('#pSugEmail').html(user['userEmail'] !== '' ? user['userEmail'] : '&nbsp;');
        $('#pSugPhoneNo').html(user['userContactNo'] !== '' ? user['userContactNo'] : '&nbsp;');

        let sugGfId = '&nbsp;';
        let yearOfService = '&nbsp;';
        let greenCardExpiry = '&nbsp;';
        let competency = '&nbsp;';
        let category = '&nbsp;';
        let groupName = '&nbsp;';
        let supervisor = '&nbsp;';
        /*const attParticipant = mzAjaxRequest2('att_participant/'+userId, 'GET');
        if (attParticipant) {
            sugGfId = attParticipant['attParticipantGfId'] !== '' ? attParticipant['attParticipantGfId'] : sugGfId;
            yearOfService = attParticipant['attParticipantYearService'] !== '' ? attParticipant['attParticipantYearService'] : yearOfService;
            greenCardExpiry = attParticipant['attParticipantCidbCardExpiry'] !== '' ? attParticipant['attParticipantCidbCardExpiry'] : greenCardExpiry;
            competency = attParticipant['attParticipantCompetency'] !== '' ? attParticipant['attParticipantCompetency'] : competency;
            if (attParticipant['attGroupId'] !== '') {
                const attGroup = mzAjaxRequest2('att_group/'+attParticipant['attGroupId'], 'GET');
                category = attGroup['attGroupCategory'];
                groupName = attGroup['attGroupName'];
                supervisor = attGroup['attGroupSupervisor'] !== '' ? refUser[attGroup['attGroupSupervisor']]['userFullName'] : supervisor;
            }
        }*/
        $('#pSugGfId').html(sugGfId);
        $('#pSugYearService').html(yearOfService);
        $('#pSugGreenCardExpiry').html(greenCardExpiry);
        $('#pSugCompetency').html(competency);
        $('#pSugCategory').html(category);
        $('#pSugTeam').html(groupName);
        $('#pSugSuperior').html(supervisor);
    };

    this.generateChartPerformance = function (chartId, title, onTime, late) {
        const perc = onTime + late === 0 ? 0 : Math.round(onTime/(onTime+late)*100);
        Highcharts.chart(chartId, {
            chart: {
                type: 'pie'
            },
            exporting: {
                enabled: false
            },
            title: {
                text: perc+'%',
                style: { "fontSize": "20px" },
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
                    center: ['50%', '150%'],
                    size: '250%'
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                type: 'pie',
                name: title,
                innerSize: '60%',
                data: [
                    {name:'Total PPM On-Time', y:onTime, color: 'Gold'},
                    {name:'Total PPM Late', y:late, color: 'LightGoldenrodYellow'}
                ]
            }]
        });
    };

    this.generateChartPpmLateness = function (chartId, onTime, within, late) {
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
            xAxis: {
                categories: ['On-time', 'Within', 'Late']
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
                name: 'Total Lateness',
                data: [
                    {y: onTime, color: 'limegreen'},
                    {y: within, color: 'darkgreen'},
                    {y: late, color: 'red'}
                ]
            }]
        });
    };

    this.generateChartWoLateness = function (chartId, onTime, late) {
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
            xAxis: {
                categories: ['On-time', 'Late']
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
                name: 'Total Lateness',
                data: [
                    {y: onTime, color: 'limegreen'},
                    {y: late, color: 'red'}
                ]
            }]
        });
    };

    this.generateChartPpmType = function (chartId, total, assist) {
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
            xAxis: {
                categories: ['Normal', 'Assist']
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
                name: 'Wo Type Total',
                data: [
                    {y: total-assist, color: 'dodgerblue'},
                    {y: assist, color: 'deeppink'}
                ]
            }]
        });
    };

    this.generateChartWoType = function (chartId, total, selfFinding, assist) {
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
            xAxis: {
                categories: ['Normal', 'Self-Finding', 'Assist']
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
                name: 'Wo Type Total',
                data: [
                    {y: total-selfFinding-assist, color: 'dodgerblue'},
                    {y: selfFinding, color: 'yellowgreen'},
                    {y: assist, color: 'deeppink'}
                ]
            }]
        });
    };

    this.showWoDetails = function (year, month, userId) {
        ShowLoader();
        setTimeout(function () {
            try {
                // Set modal title
                $('#spanWoDetailsMonth').text(monthArray[parseInt(month)-1]['monthName'] + ' ' + year);
                
                // Fetch work order details for the specific month and user using the same pattern as other calls
                try {
                    const woData = mzAjaxRequest2('gamification/wo_details/' + year + '/' + month + '/' + userId, 'GET');
                    
                    // Ensure woData is an array
                    let woDataArray = Array.isArray(woData) ? woData : [];
                    
                    // Update summary cards
                    const totalWo = woDataArray.length;
                    const completedWo = woDataArray.filter(wo => wo.woStatus === 'Completed').length;
                    $('#h4WoDetailsTotal').text(mzFormatNumber(totalWo));
                    $('#h4WoDetailsCompleted').text(mzFormatNumber(completedWo));
                    
                    // Initialize/update the DataTable
                    if ($.fn.DataTable.isDataTable('#dtWoDetails')) {
                        $('#dtWoDetails').DataTable().clear().rows.add(woDataArray).draw();
                    } else {
                        $('#dtWoDetails').DataTable({
                            data: woDataArray,
                            bLengthChange: false,
                            bFilter: true,
                            ordering: true,
                            language: _DATATABLE_LANGUAGE,
                            autoWidth: false,
                            dom: "<'row'<'col-6 px-0'l><'col-6 pb-0'f>>" +
                                "<'row'<'col-sm-12'tr>>" +
                                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
                            columnDefs: [
                                { className: 'text-center', targets: [3, 4, 8, 9, 10] },
                                { className: 'text-nowrap', targets: [5, 6, 7] }
                            ],
                            columns: [
                                { data: 'woNo', title: 'WO No' },
                                { data: 'woDesc', title: 'Description', 
                                    render: function(data) {
                                        return data && data.length > 50 ? data.substring(0, 50) + '...' : data || '';
                                    }
                                },
                                { data: 'assetDesc', title: 'Asset' },
                                { data: 'woStatus', title: 'Status' },
                                { data: 'woPriority', title: 'Priority' },
                                { data: 'woCreateDate', title: 'Created Date',
                                    render: function(data) {
                                        return data ? moment(data).format('DD/MM/YYYY') : '';
                                    }
                                },
                                { data: 'woTargetDate', title: 'Target Date',
                                    render: function(data) {
                                        return data ? moment(data).format('DD/MM/YYYY') : '';
                                    }
                                },
                                { data: 'woCompletedDate', title: 'Completed Date',
                                    render: function(data) {
                                        return data ? moment(data).format('DD/MM/YYYY') : '';
                                    }
                                },
                                { data: 'woOnTimeStatus', title: 'On-Time Status',
                                    render: function(data) {
                                        if (data === 'On-Time') {
                                            return '<span class="badge badge-success">On-Time</span>';
                                        } else if (data === 'Late') {
                                            return '<span class="badge badge-danger">Late</span>';
                                        } else if (data === 'Within') {
                                            return '<span class="badge badge-warning">Within</span>';
                                        }
                                        return '<span class="badge badge-secondary">Pending</span>';
                                    }
                                },
                                { data: 'woType', title: 'Type' },
                                { data: 'woRole', title: 'Role',
                                    render: function(data) {
                                        if (data === 'Assigned') {
                                            return '<span class="badge badge-primary">Assigned</span>';
                                        } else if (data === 'Assist') {
                                            return '<span class="badge badge-info">Assist</span>';
                                        }
                                        return '<span class="badge badge-secondary">' + (data || 'Unknown') + '</span>';
                                    }
                                }
                            ]
                        });
                    }
                    
                    // Show the modal
                    $('#modalWoDetails').modal('show');
                    
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
                
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
                HideLoader();
            }
        }, 200);
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setRefDesignation = function (_refDesignation) {
        refDesignation = _refDesignation;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };
}