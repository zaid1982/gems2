function SectionAttendanceSite () {

    const className = 'SectionAttendanceSite';
    let self = this;
    let classFrom;
    let hasEdit = false;
    let siteId;
    let siteName;
    let userId;
    let refStatus;
    let refUser;
    let refAssetGroup;
    let refAttGroup;
    let refAttType;
    let oTableSacGroup;
    let oTableSacParticipant;
    let modalAttendanceGroupClass;
    let modalAttendanceParticipantClass;
    let modalAttendanceDailyClass;
    let modalConfirmSubmitClass;
    let sectionAttendancePlannerClass;
    let isAdmin;
    let isSiteAdmin;
    let isSupervisor;
    let todayDate;

    this.init = function () {
        $('#btnSacBack').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (hasEdit) {
                        classFrom.genTable();
                    }
                    self.hideMain();
                    sectionAttendancePlannerClass.hideMain();
                    classFrom.showMain();
                    $(window).scrollTop(0);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        userId = mzGetUserId();
        isAdmin = mzIsRoleExist('1');
        isSiteAdmin = mzIsRoleExist('19');
        isSupervisor = mzIsRoleExist('20');

        oTableSacGroup = $('#dtSacGroup').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 100,
            autoWidth: false,
            dom: "<'row'<'col-7 px-0'B><'col-5 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 17] },
                { className: 'text-center', targets: [0, 5, 6, 7, 8, 9, 10, 11, 12, 16, 17] },
                { className: 'text-right', targets: [4, 15] },
                { visible: false, targets: [7, 8, 9, 10, 11, 12, 14] },
                { className: 'noVis', targets: [0, 17] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'four-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ', text:'<i class="fas fa-print"></i>', title:'GEMS - Attendance Group List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 ', text:'<i class="fas fa-copy"></i>', title:'GEMS - Attendance Group List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 ', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Attendance Group List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 ', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Attendance Group List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt},
                { text: 'Add Attendance Group', className: 'btn btn-outline-red btn-sm px-2 ml-3', attr: { id: 'btnSacAddGroup' }}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                if (isAdmin) {
                    $('#btnSacAddGroup').off('click').on('click', function () {
                        modalAttendanceGroupClass.add(siteId);
                    });
                } else {
                    $('#btnSacAddGroup').hide();
                }
                $('.lnkSacGroupEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableSacGroup.row(parseInt(rowId)).data();
                        modalAttendanceGroupClass.edit(currentRow['attGroupId'], siteId);
                    }
                });
                $('.lnkSacGroupDetail').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableSacGroup.row(parseInt(rowId)).data();
                        //sectionAttendancePlannerClass.load(currentRow['attGroupId']);
                    }
                });
                if ($('#divPageWidth').width() < 880) {
                    $(this).DataTable().column(4).visible(false);
                    $(this).DataTable().column(5).visible(false);
                    $(this).DataTable().column(6).visible(false);
                    $(this).DataTable().column(13).visible(false);
                }
            },
            aoColumns: [
                {mData: null},
                {mData: 'attGroupName'},
                {mData: 'attGroupSupervisor', mRender: function(data) {
                        return data !== null ? refUser[data]['userFirstName'] : '-';
                    }},
                {mData: 'assetGroupId', mRender: function(data) {
                        return refAssetGroup[data]['assetGroupName'];
                    }},
                {mData: 'attGroupReqWeekHours', width: '8%'},
                {mData: 'attGroupShiftMode'},   // 5
                {mData: 'attGroupHoliday'},
                {mData: 'attGroupNormalHours'},
                {mData: 'attGroupAmHours'},
                {mData: 'attGroupPmHours'},
                {mData: 'attGroupMorningHours'}, // 10
                {mData: 'attGroupEveningHours'},
                {mData: 'attGroupNightHours'},
                {mData: 'attGroupOtApprover', mRender: function(data) {
                        return data !== null ? refUser[data]['userFirstName'] : '-';
                    }},
                {mData: 'attGroupRemark'},
                {mData: 'totalParticipantActive', width: '8%', mRender: function(data) { // 15
                        return data !== null ? data : '0';
                    }},
                {mData: 'attGroupStatus',  mRender: function(data) {
                        return refStatus[data]['statusDesc'];
                    }},
                {mData: null, mRender: function(data, type, row, meta) {
                        let label = '';
                        if (isAdmin || (!isAdmin && isSupervisor && userId === row['attGroupSupervisor'])) {
                            label = '<a><i class="fas fa-edit mr-1 lnkSacGroupEdit" id="lnkSacGroupEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Click to edit ' + row['attGroupName'] + ' configuration"></i></a>';
                        }
                        label += '<a><i class="fas fa-users lnkSacGroupDetail" id="lnkSacGroupDetail_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Click to see ' + row['attGroupName'] + ' informations"></i></a>';
                        return label;
                    }}
            ]
        });

        oTableSacParticipant = $('#dtSacParticipant').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[14, 'asc'], [2, 'asc'], [1, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 100,
            autoWidth: false,
            dom: "<'row'<'col-7 px-0 pb-2'B><'col-5 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 15] },
                { className: 'text-center', targets: [0, 3, 5, 8, 11, 12, 13, 14, 15] },
                { className: 'text-right', targets: [10] },
                { visible: false, targets: [5, 6, 7, 8, 9, 10, 13] },
                { className: 'noVis', targets: [0, 15] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2', text:'<i class="fas fa-print"></i>', title:'GEMS - Attendance Participant List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0', text:'<i class="fas fa-copy"></i>', title:'GEMS - Attendance Participant List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Attendance Participant List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Attendance Participant List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt},
                { text: 'All', className: 'btn btn-outline-blue btn-sm px-2 ml-3', attr: { id: 'btnSacParticipantStatusAll' }},
                { text: 'Assigned', className: 'btn btn-outline-success btn-sm px-2 ml-0', attr: { id: 'btnSacParticipantStatusAssigned' }},
                { text: 'Unregistered', className: 'btn btn-outline-grey btn-sm px-2 ml-0', attr: { id: 'btnSacParticipantStatusNotAssigned' }}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('#btnSacParticipantStatusAll').off('click').on('click', function () {
                    oTableSacParticipant.search('').columns().search('').draw();
                    $('#dtSacParticipantTitle').text('Employee List')
                });
                $('#btnSacParticipantStatusAssigned').off('click').on('click', function () {
                    oTableSacParticipant.search('').columns().search('');
                    oTableSacParticipant.column(14).search('^(Active|Disabled)$', true, false).draw();
                    $('#dtSacParticipantTitle').text('Employee List (Assigned)')
                });
                $('#btnSacParticipantStatusNotAssigned').off('click').on('click', function () {
                    oTableSacParticipant.search('').columns().search('');
                    oTableSacParticipant.column(14).search('^(Unregistered)$', true, false).draw();
                    $('#dtSacParticipantTitle').text('Employee List (Unregistered)')
                });
                $('.lnkSacParticipantEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableSacParticipant.row(parseInt(rowId)).data();
                        if (isAdmin || isSupervisor) {
                            modalAttendanceParticipantClass.setParticipantName(currentRow['userFirstName']);
                            modalAttendanceParticipantClass.setSiteName(siteName);
                            modalAttendanceParticipantClass.load(currentRow['userIds'], currentRow['attParticipantId'], siteId);
                        } else {
                            toastr['error']('You don\'t have permission as Site Admin or Supervisor role to perform this task!', _ALERT_TITLE_ERROR);
                            return false;
                        }
                    }
                });
                $('.lnkSacParticipantDetail').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableSacParticipant.row(parseInt(rowId)).data();
                        //sectionAttendancePlannerClass.load(currentRow['attParticipantId']);
                    }
                });
                if ($('#divPageWidth').width() < 880) {
                    $(this).DataTable().column(3).visible(false);
                    $(this).DataTable().column(4).visible(false);
                    $(this).DataTable().column(5).visible(false);
                    $(this).DataTable().column(12).visible(false);
                }
            },
            aoColumns: [
                {mData: null},
                {mData: 'userFirstName'},
                {mData: 'attGroupId', mRender: function(data) {
                        return data !== null ? refAttGroup[data]['attGroupName'] : '';
                    }},
                {mData: 'attParticipantGfId'},
                {mData: 'designationDesc'},
                {mData: 'userContactNo'},   // 5
                {mData: 'userEmail'},
                {mData: 'attParticipantCompetency'},
                {mData: 'attParticipantCidbCardExpiry'},
                {mData: 'assetGroupId', mRender: function(data) {
                        return  data !== null ? refAssetGroup[data]['assetGroupName'] : '';
                    }},
                {mData: 'attParticipantReqWeekHours'},
                {mData: 'attParticipantShiftMode'},
                {mData: 'attTypeId', mRender: function(data) {
                        return  data !== null ? refAttType[data]['attTypeName'] : '';
                    }},
                {mData: 'attParticipantHoliday'},
                {mData: 'participantStatus', width: '8%', mRender: function(data) {
                        return refStatus[data]['statusDesc'];
                    }},
                {mData: null, mRender: function(data, type, row, meta) {
                        let label = '';
                        if (isAdmin || (!isAdmin && isSupervisor && userId === row['attGroupSupervisor'])) {
                            label = '<a><i class="fas fa-edit mr-1 lnkSacParticipantEdit" id="lnkSacParticipantEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Click to edit ' + row['userFirstName'] + ' configuration"></i></a>';
                        }
                        label += '<a><i class="fas fa-user lnkSacParticipantDetail" id="lnkSacParticipantDetail_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Click to see ' + row['userFirstName'] + ' informations"></i></a>';
                        return label;
                    }}
            ]
        });

        $('#btnSacActivate').on('click', function () {
            try {
                modalConfirmSubmitClass.setClassFrom(self);
                modalConfirmSubmitClass.load(siteId, 'Activate', 'Are you sure to <b>ACTIVATE</b> attendance for <b>'+siteName+'</b> site?');
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
        });

        $('#btnSacDeactivate').on('click', function () {
            try {
                modalConfirmSubmitClass.setClassFrom(self);
                modalConfirmSubmitClass.load(siteId, 'Deactivate', 'Are you sure to <b>DEACTIVATE</b> attendance for <b>'+siteName+'</b> site?');
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
        });

        self.hideMain();
    };

    this.reloadTopStatistic = function () {
        try {
            mzCheckFuncParam([siteId]);
            const attSite = mzAjaxRequest2('att_group/site/'+siteId, 'GET');
            $('#lblSacTotalGroup').html(attSite['totalGroup']);
            $('#lblSacTotalParticipant').html(attSite['totalParticipant']);
            if (attSite['siteIsAttendance']==='1') {
                $('#lblSacSiteStatus').html('Enabled');
                $('#btnSacActivate').hide();
                $('#btnSacDeactivate').show();
            } else {
                $('#lblSacSiteStatus').html('Disabled');
                $('#btnSacActivate').show();
                $('#btnSacDeactivate').hide();
            }
        } catch (e) {
            throw new Error(e.message);
        }
    };

    this.load = function (_siteId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteId]);
                siteId = _siteId;

                const attSite = mzAjaxRequest2('att_group/site/'+siteId, 'GET');
                siteName = attSite['siteName'];
                $('#lblSacSiteName').html(siteName);
                $('#lblSacSiteCode').html(attSite['siteCode']);
                $('#lblSacTotalGroup').html(attSite['totalGroup']);
                $('#lblSacTotalParticipant').html(attSite['totalParticipant']);
                if (attSite['siteIsAttendance'] === 1) {
                    $('#lblSacSiteStatus').html('Enabled');
                    $('#btnSacActivate').hide();
                    $('#btnSacDeactivate').show();
                } else {
                    $('#lblSacSiteStatus').html('Disabled');
                    $('#btnSacActivate').show();
                    $('#btnSacDeactivate').hide();
                }
                modalAttendanceGroupClass.setSiteName(attSite['siteName']);
                modalAttendanceGroupClass.setSiteCode(attSite['siteCode']);

                todayDate = moment();
                self.genTableGroup();
                self.genTableParticipant();

                const chartData = mzAjaxRequest2('att_group/chart_site/'+siteId, 'GET');
                self.genChart('chartSacAbsent', 'Absenteeism Performance', 'Group Absenteeism Rate (%)', chartData[0]);
                self.genChart('chartSacLateness', 'Punctuality Performance', 'Group Punctuality Rate (%)', chartData[1]);
                self.genChart('chartSacValidity', 'Geo-Validity Performance', 'Group Geo-Validity Rate (%)', chartData[2]);
                oTableSacParticipant.search('').columns().search('').draw();

                $('#dtSacParticipantTitle').text('Employee List');
                hasEdit = false;
                classFrom.hideMain();
                self.showMain();
                window.scrollTo({top: 0, behavior: 'smooth'});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.confirmSubmit = function (_siteId, _flag) {
        try {
            ShowLoader();
            setTimeout(function () {
                try {
                    mzCheckFuncParam([_siteId, _flag]);
                    if (_flag === 'Activate') {
                        mzAjaxRequest2('att_group/activate_site/'+_siteId, 'PUT');
                        $('#lblSacSiteStatus').html('Enabled');
                        $('#btnSacActivate').hide();
                        $('#btnSacDeactivate').show();
                        hasEdit = true;
                    } else if (_flag === 'Deactivate') {
                        mzAjaxRequest2('att_group/deactivate_site/'+_siteId, 'PUT');
                        $('#lblSacSiteStatus').html('Disabled');
                        $('#btnSacActivate').show();
                        $('#btnSacDeactivate').hide();
                        hasEdit = true;
                    } else {
                        toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        } catch (e) {
            throw new Error(e.message);
        }
    };

    this.genTableGroup = function () {
        try {
            const dataDb = mzAjaxRequest2('att_group/by_site/'+siteId, 'GET');
            oTableSacGroup.clear().rows.add(dataDb).draw();
            refAttGroup = mzGetLocalArrayV2('gems_attGroup', mzGetDataVersion(), 'att_group/ref');
            modalAttendanceParticipantClass.setRefAttGroup(refAttGroup);
            modalAttendanceDailyClass.setRefAttGroup(refAttGroup);
        } catch (e) {
            throw new Error(e.message);
        }
    };

    this.genTableParticipant = function () {
        try {
            const dataDb = mzAjaxRequest2('att_participant/by_site/'+siteId, 'GET');
            oTableSacParticipant.clear().rows.add(dataDb).draw();
            sectionAttendancePlannerClass.loadSite(siteId, todayDate.format('YYYY'), todayDate.format('M'));
        } catch (e) {
            throw new Error(e.message);
        }
    };

    this.genChart = function (_chartId, _chartTitle, _subTitle, _data) {
        try {
            Highcharts.chart(_chartId, {
                chart: {
                    type: 'column'
                },
                title: {
                    text: _chartTitle
                },
                subtitle: {
                    text: _subTitle
                },
                xAxis: {
                    type: 'category',
                    title: {
                        text: null
                    }
                },
                yAxis: {
                    min: 0,
                    max: 100,
                    title: {
                        text: null
                    },
                    labels: {
                        overflow: 'justify'
                    }
                },
                legend: {
                    enabled: false
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.y:.2f} %</b>'
                },
                colorAxis: {
                    maxColor: '#077DD3',
                    minColor: '#7FC6FA'
                },
                plotOptions: {
                    column: {
                        dataLabels: {
                            enabled: true
                        },
                        borderRadius: 3,
                        borderWidth: 0
                    }
                },
                credits: {
                    enabled: false
                },
                series: [{
                    name: 'Performance',
                    data: _data
                }]
            });
        } catch (e) {
            throw new Error(e.message);
        }
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.getClassFrom = function () {
        return classFrom;
    };

    this.showMain = function () {
        $('.sectionAttendanceSite').show();
    };

    this.hideMain = function () {
        $('.sectionAttendanceSite').hide();
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefAttType = function (_refAttType) {
        refAttType = _refAttType;
    };

    this.setModalAttendanceGroupClass = function (_modalAttendanceGroupClass) {
        modalAttendanceGroupClass = _modalAttendanceGroupClass;
    };

    this.setModalAttendanceParticipantClass = function (_modalAttendanceParticipantClass) {
        modalAttendanceParticipantClass = _modalAttendanceParticipantClass;
    };

    this.setModalAttendanceDailyClass = function (_modalAttendanceDailyClass) {
        modalAttendanceDailyClass = _modalAttendanceDailyClass;
    };

    this.setModalConfirmSubmitClass = function (_modalConfirmSubmitClass) {
        modalConfirmSubmitClass = _modalConfirmSubmitClass;
    };

    this.setSectionAttendancePlannerClass = function (_sectionAttendancePlannerClass) {
        sectionAttendancePlannerClass = _sectionAttendancePlannerClass;
    };

    this.setHasEdit = function (_hasEdit) {
        hasEdit = _hasEdit;
    };
}