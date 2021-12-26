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

        oTableSugHistory = $('#dtSugHistory').DataTable({
            bLengthChange: false,
            bFilter: true,
            //aaSorting: [[11, 'desc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            autoWidth: false,
            dom: "<'row'<'col-12 px-0'B>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [1, 2] },
                { className: 'text-right', targets: [3, 4, 5, 6, 7, 8, 9, 10] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ', text:'<i class="fas fa-print"></i>', title:'GEMS - Performance History', titleAttr: 'Print'},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 ', text:'<i class="fas fa-copy"></i>', title:'GEMS - Performance History', titleAttr: 'Copy'},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 ', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Performance History', titleAttr: 'Excel'},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 ', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Performance History', titleAttr: 'PDF', orientation: 'landscape'}
            ],
            aoColumns: [
                {mData: null, mRender: function (data, type, row) {
                        return monthArray[parseInt(row['gmiMonth'])-1]['monthName'] + ' ' + row['gmiYear'];
                    }},
                {mData: 'gmiWoTierName', width: '12%'},
                {mData: 'gmiPpmTierName', width: '12%'},
                {mData: 'gmiWoTierPoint', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data, 1);
                    }},
                {mData: 'gmiPpmTierPoint', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data, 1);
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
                {mData: null, width: '8%', mRender: function (data, type, row) {
                        return '--';
                    }},
                {mData: 'gmiPointTotal', width: '8%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }}
            ]
        });
        oTableSugHistory.column(3).visible(false);
        oTableSugHistory.column(4).visible(false);
    };

    this.load = function (_gmiId, _userId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_gmiId, _userId]);
                gmiId = _gmiId;
                userId = _userId;

                const data = mzAjaxRequest2('gamification/gmi_monthly/'+gmiId, 'GET');
                console.log(data);
                $('#h3SugPointDeduction').html(mzFormatNumber(Math.abs(parseInt(data['gmiPointLate'])+parseInt(data['gmiWoLate']))));
                $('#h3SugPointTotal').html(mzFormatNumber(parseInt(data['gmiPointTotal'])+parseInt(data['gmiWoTotal'])));
                $('#h4SugRatingPpm').html(data['gmiPpmTierName']);
                $('#h4SugRatingWo').html(data['gmiWoTierName']);
                $('#h2SugPpmDone').html(mzFormatNumber(data['gmiPpmCompleted']));
                $('#h2SugWoDone').html(mzFormatNumber(data['gmiWoCompleted']));
                $('#h2SugLatePpm').html(mzFormatNumber(data['gmiPpmLate']));
                $('#h2SugLateWo').html(mzFormatNumber(data['gmiWoLate']));
                self.generateChartPerformancePpm(parseInt(data['gmiPpmOnTime']), parseInt(data['gmiPpmLate']));
                self.generateChartPerformanceWo(parseInt(data['gmiWoOnTime']), parseInt(data['gmiWoLate']));
                self.loadProfile(userId);

                const dataHistory = mzAjaxRequest2('gamification/gmi_monthly_history/'+data['gmiYear']+'/'+data['gmiMonth']+'/'+userId, 'GET');
                console.log(dataHistory);
                oTableSugHistory.clear().rows.add(dataHistory).draw();

                $('.sectionUserGame').show();
                classFrom.hideMain();
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
        const attParticipant = mzAjaxRequest2('att_participant/'+userId, 'GET');
        if (attParticipant !== '') {
            sugGfId = attParticipant['attParticipantGfId'] !== '' ? attParticipant['attParticipantGfId'] : sugGfId;
            yearOfService = attParticipant['attParticipantYearService'] !== '' ? attParticipant['attParticipantYearService'] : yearOfService;
            greenCardExpiry = attParticipant['attParticipantCidbCardExpiry'] !== '' ? attParticipant['attParticipantCidbCardExpiry'] : greenCardExpiry;
            competency = attParticipant['attParticipantCompetency'] !== '' ? attParticipant['attParticipantCompetency'] : competency;
            if (attParticipant['attGroupId'] !== '') {
                const attGroup = mzAjaxRequest2('att_group/'+attParticipant['attGroupId'], 'GET');
                category = attGroup['attGroupCategory'];
                groupName = attGroup['attGroupName'];
                supervisor = attGroup['attGroupSupervisor'] !== '' ? refUser[user['attGroupSupervisor']]['userFullName'] : supervisor;
            }
        }
        $('#pSugGfId').html(sugGfId);
        $('#pSugYearService').html(yearOfService);
        $('#pSugGreenCardExpiry').html(greenCardExpiry);
        $('#pSugCompetency').html(competency);
        $('#pSugCategory').html(category);
        $('#pSugTeam').html(groupName);
        $('#pSugSuperior').html(supervisor);
    };

    this.generateChartPerformancePpm = function (onTime, late) {
        const perc = onTime + late === 0 ? 0 : Math.round(onTime/(onTime+late)*100);
        Highcharts.chart('chartSugPerfPpm', {
            chart: {
                type: 'pie'
            },
            exporting: {
                enabled: false
            },
            title: {
                text: perc+'%',
                style: { "fontSize": "40px" },
                align: 'center',
                verticalAlign: 'middle',
                y: 85
            },
            plotOptions: {
                pie: {
                    dataLabels: {
                        enabled: false
                    },
                    startAngle: -90,
                    endAngle: 90,
                    center: ['50%', '120%'],
                    size: '220%'
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                type: 'pie',
                name: 'Plan Preventive Maintenance',
                innerSize: '60%',
                data: [
                    {name:'Total PPM On-Time', y:onTime, color: 'yellowgreen'},
                    {name:'Total PPM Late', y:late, color: 'palegreen'}
                ]
            }]
        });
    };

    this.generateChartPerformanceWo = function (onTime, late) {
        const perc = onTime + late === 0 ? 0 : Math.round(onTime/(onTime+late)*100);
        Highcharts.chart('chartSugPerfWo', {
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: 0,
                plotShadow: false
            },
            exporting: {
                enabled: false
            },
            title: {
                text: perc+'%',
                style: { "fontSize": "40px" },
                align: 'center',
                verticalAlign: 'middle',
                y: 85
            },
            plotOptions: {
                pie: {
                    dataLabels: {
                        enabled: false
                    },
                    startAngle: -90,
                    endAngle: 90,
                    center: ['50%', '120%'],
                    size: '220%'
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                type: 'pie',
                name: 'Work Order',
                innerSize: '60%',
                data: [
                    {name:'Total On-time', y:onTime, color: 'cornflowerblue'},
                    {name:'Total Late', y:late, color: 'lightblue'}
                ]
            }]
        });
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