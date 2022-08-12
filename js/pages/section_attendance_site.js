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
    let oTableSacPlanner;
    let modalAttendanceGroupClass;
    let modalAttendanceParticipantClass;
    let modalAttendanceDailyClass;
    let modalConfirmSubmitClass;
    let sectionAttendanceGroupClass;
    let isAdmin;
    let isSiteAdmin;
    let isSupervisor;
    let currentYear = 0;
    let currentMonth = 0;
    let thisDate;

    this.init = function () {
        $('#btnSacBack').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (hasEdit) {
                        classFrom.genTable();
                    }
                    self.hideMain();
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
        thisDate = moment();

        oTableSacGroup = $('#dtSacGroup').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 100,
            autoWidth: false,
            dom: "<'row'<'col-12 col-sm-7 px-0 pb-2'B><'col-sm-5 d-none d-sm-block pb-0'f>>" +
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
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnSacGroupHide', text:'<i class="fas fa-print"></i>', title:'GEMS - Attendance Group List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnSacGroupHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - Attendance Group List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnSacGroupHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Attendance Group List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Attendance Group List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt},
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
                        sectionAttendanceGroupClass.load(currentRow['attGroupId']);
                    }
                });
                if ($('#divPageWidth').width() < 880) {
                    $(this).DataTable().column(4).visible(false);
                    $(this).DataTable().column(5).visible(false);
                    $(this).DataTable().column(6).visible(false);
                    $(this).DataTable().column(13).visible(false);
                    $(this).DataTable().column(16).visible(false);
                    $('.btnSacGroupHide').hide();
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
            dom: "<'row'<'col-12 col-sm-7 px-0 pb-2'B><'col-sm-5 d-none d-sm-block pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 27] },
                { className: 'text-center', targets: [0, 3, 5, 8, 11, 12, 13, 14, 27] },
                { className: 'text-right', targets: [10, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26] },
                { visible: false, targets: [5, 6, 7, 8, 9, 10, 11, 12, 13, 21, 22, 23, 24, 25] },
                { className: 'noVis', targets: [0, 27] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'four-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnSacParticipantHide', text:'<i class="fas fa-print"></i>', title:'GEMS - Attendance Participant List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnSacParticipantHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - Attendance Participant List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnSacParticipantHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Attendance Participant List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
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
                    $('#dtSacParticipantTitle').text('Monthly Employee Summary')
                });
                $('#btnSacParticipantStatusAssigned').off('click').on('click', function () {
                    oTableSacParticipant.search('').columns().search('');
                    oTableSacParticipant.column(14).search('^(Active|Disabled)$', true, false).draw();
                    $('#dtSacParticipantTitle').text('Monthly Employee Summary (Assigned)');
                });
                $('#btnSacParticipantStatusNotAssigned').off('click').on('click', function () {
                    oTableSacParticipant.search('').columns().search('');
                    oTableSacParticipant.column(14).search('^(Unregistered)$', true, false).draw();
                    $('#dtSacParticipantTitle').text('Monthly Employee Summary (Unregistered)');
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
                    $(this).DataTable().column(15).visible(false);
                    $(this).DataTable().column(16).visible(false);
                    $(this).DataTable().column(17).visible(false);
                    $(this).DataTable().column(18).visible(false);
                    $(this).DataTable().column(19).visible(false);
                    $(this).DataTable().column(20).visible(false);
                    $(this).DataTable().column(26).visible(false);
                    $('.btnSacParticipantHide').hide();
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
                        return data !== null ? refAssetGroup[data]['assetGroupName'] : '';
                    }},
                {mData: 'attParticipantReqWeekHours'}, // 10
                {mData: 'attParticipantShiftMode'},
                {mData: 'attTypeId', mRender: function(data) {
                        return data !== null ? refAttType[data]['attTypeName'] : '';
                    }},
                {mData: 'attParticipantHoliday'},
                {mData: 'participantStatus', width: '8%', mRender: function(data) {
                        return refStatus[data]['statusDesc'];
                    }},
                {mData: 'totalPresent', mRender: function(data, type, row) { // 15
                        return row['participantStatus'] === 1 ? (data !== '0' ? '<span class="green-text">'+data+'</span>' : '0') : '';
                    }},
                {mData: 'totalAbsent', mRender: function(data, type, row) {
                        return row['participantStatus'] === 1 ? (data !== '0' ? '<span class="red-text">'+data+'</span>' : '0') : '';
                    }},
                {mData: 'totalMc', mRender: function(data, type, row) {
                        return row['participantStatus'] === 1 ? data : '';
                    }},
                {mData: 'totalAl', mRender: function(data, type, row) {
                        return row['participantStatus'] === 1 ? data : '';
                    }},
                {mData: 'totalOd', mRender: function(data, type, row) {
                        return row['participantStatus'] === 1 ? data : '';
                    }},
                {mData: 'totalRd', mRender: function(data, type, row) { // 20
                        return row['participantStatus'] === 1 ? data : '';
                    }},
                {mData: 'totalPh', mRender: function(data, type, row) {
                        return row['participantStatus'] === 1 ? data : '';
                    }},
                {mData: 'totalTraining', mRender: function(data, type, row) {
                        return row['participantStatus'] === 1 ? data : '';
                    }},
                {mData: 'totalInLate', mRender: function(data, type, row) {
                        return row['participantStatus'] === 1 ? (data !== '0' ? '<span class="red-text">'+data+'</span>' : '0') : '';
                    }},
                {mData: 'totalOutEarly', mRender: function(data, type, row) {
                        return row['participantStatus'] === 1 ? (data !== '0' ? '<span class="red-text">'+data+'</span>' : '0') : '';
                    }},
                {mData: 'totalOutside', mRender: function(data, type, row) { // 25
                        return row['participantStatus'] === 1 ? (data !== '0' ? '<span class="red-text">'+data+'</span>' : '0') : '';
                    }},
                {mData: 'totalHours', mRender: function(data, type, row) {
                        let str = '';
                        if (data !== null) {
                            if (parseInt(data) > row['attParticipantReqWeekHours']) {
                                str = '<span class="green-text">'+data+'</span>';
                            } else {
                                str = '<span class="red-text">'+data+'</span>';
                            }
                        } else if (row['participantStatus'] === 1) {
                            str = '<span class="red-text">0</span>';
                        }
                        return str;
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

        oTableSacPlanner = $('#dtSacPlanner').DataTable({
            bLengthChange: false,
            bFilter: false,
            bInfo: false,
            aaSorting: [[1, 'asc']],
            ordering: false,
            language: _DATATABLE_LANGUAGE,
            bPaginate: false,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
                for (let i = 1; i <= parseInt(thisDate.endOf('month').format('D')); i++) {
                    const attTypeId = aData['data'][i]['attTypeId'];
                    $('td', nRow).eq(i+1).addClass(refAttType[attTypeId]['attTypeColor']);
                }
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                const lastDay = parseInt(thisDate.endOf('month').format('D'));
                $(this).DataTable().column(30).visible(true);
                $(this).DataTable().column(31).visible(true);
                $(this).DataTable().column(32).visible(true);
                if (lastDay === 28) {
                    $(this).DataTable().column(30).visible(false);
                    $(this).DataTable().column(31).visible(false);
                    $(this).DataTable().column(32).visible(false);
                } else if (lastDay === 29) {
                    $(this).DataTable().column(31).visible(false);
                    $(this).DataTable().column(32).visible(false);
                } else if (lastDay === 30) {
                    $(this).DataTable().column(32).visible(false);
                }
                $('.lnkSacPlannerInfo').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkArray = linkId.split('_');
                    if (linkArray.length === 3 && linkArray[0] === 'lnkSacPlannerInfo') {
                        const currentRow = oTableSacPlanner.row(parseInt(linkArray[1])).data();
                        modalAttendanceDailyClass.setClassFrom(self);
                        modalAttendanceDailyClass.load(currentRow['data'][parseInt(linkArray[2])]);
                    }
                });
            },
            columnDefs: [
                { className: 'text-left', targets: [1] }
            ],
            aoColumns: [
                {mData: null},
                {mData: 'userId', mRender: function(data) {
                        return '<div class="table-grid-cut-text">'+refUser[data]['userFirstName']+'</div>';
                    }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 1, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 2, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 3, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 4, meta.row); }}, // 5
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 5, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 6, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 7, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 8, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 9, meta.row); }}, // 10
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 10, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 11, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 12, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 13, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 14, meta.row); }}, // 15
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 15, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 16, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 17, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 18, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 19, meta.row); }}, // 20
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 20, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 21, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 22, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 23, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 24, meta.row); }}, // 25
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 25, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 26, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 27, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 28, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 29, meta.row); }}, // 30
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 30, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 31, meta.row); }}
            ]
        });

        $('#btnSacRunPlanner').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    mzAjaxRequest2('att_transaction/reschedule/site/'+siteId+'/'+currentYear+'/'+currentMonth, 'PUT');
                    self.genTablePlanner();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('.lnkSacYear').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            ShowLoader();
            setTimeout(function () {
                try {
                    const linkArray = linkId.split('_');
                    if (linkArray.length === 2 && linkArray[0] === 'lnkSacYear') {
                        self.setMonthYear(parseInt(linkArray[1]), currentMonth);
                        self.setPageByMonth();
                    } else {
                        throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('.lnkSacMonth').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            ShowLoader();
            setTimeout(function () {
                try {
                    const linkArray = linkId.split('_');
                    if (linkArray.length === 2 && linkArray[0] === 'lnkSacMonth') {
                        self.setMonthYear(currentYear, parseInt(linkArray[1]));
                        self.setPageByMonth();
                    } else {
                        throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
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
                $('#divSacActivate').hide();
                $('#divSacDeactivate').show();
            } else {
                $('#lblSacSiteStatus').html('Disabled');
                $('#divSacActivate').show();
                $('#divSacDeactivate').hide();
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
                    $('#divSacActivate').hide();
                    $('#divSacDeactivate').show();
                } else {
                    $('#lblSacSiteStatus').html('Disabled');
                    $('#divSacActivate').show();
                    $('#divSacDeactivate').hide();
                }
                modalAttendanceGroupClass.setSiteName(attSite['siteName']);
                modalAttendanceGroupClass.setSiteCode(attSite['siteCode']);

                const todayDate = moment();
                self.setMonthYear(parseInt(todayDate.format('YYYY')), parseInt(todayDate.format('M')));
                self.genTableGroup();
                self.setPageByMonth();

                hasEdit = false;
                $('#divSacBack').hide();
                if (typeof classFrom !== 'undefined') {
                    classFrom.hideMain();
                    $('#divSacBack').show();
                }
                self.showMain();
                window.scrollTo({top: 0, behavior: 'smooth'});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.setPageByMonth = function () {
        try {
            self.genTableParticipant();
            oTableSacParticipant.search('').columns().search('');
            oTableSacParticipant.column(14).search('^(Active|Disabled)$', true, false).draw();
            $('#dtSacParticipantTitle').text('Monthly Employee Summary (Assigned)');

            const chartData = mzAjaxRequest2('att_group/chart_site/'+siteId+'/'+currentYear+'/'+currentMonth, 'GET');
            self.genChart('chartSacAbsent', 'Attendance Performance', 'Group Attendance Rate (%)', chartData[0]);
            self.genChart('chartSacLateness', 'Punctuality Performance', 'Group Punctuality Rate (%)', chartData[1]);
            self.genChart('chartSacValidity', 'Geo-Validity Performance', 'Inside Parameter Rate (%)', chartData[2]);

            self.genTablePlanner();
        } catch (e) {
            toastr['error'](e.message, _ALERT_TITLE_ERROR);
        }
    };

    this.setMonthYear = function (_year, _month) {
        try {
            mzCheckFuncParam([_year, _month]);
            if (typeof _year !== 'number' || typeof _month !== 'number') {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
            if (_year < 2022 || _month < 1 || _month > 12) {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
            if (currentMonth !== _month) {
                currentMonth = _month;
                $('.lnkSacMonth').removeClass('active').removeClass('text-white');
                $('#lnkSacMonth_'+currentMonth).addClass('active').addClass('text-white');
            }
            if (currentYear !== _year) {
                currentYear = _year;
                $('.lnkSacYear').removeClass('active').removeClass('text-white');
                $('#lnkSacYear_' + currentYear).addClass('active').addClass('text-white');
            }
            thisDate.set({'year': currentYear, 'month': currentMonth - 1});
            $('#lblSacSelected').text(thisDate.format('MMMM, YYYY'));
        } catch (e) {
            toastr['error'](e.message, _ALERT_TITLE_ERROR);
        }
    };

    this.confirmSubmit = function (_siteId, _flag) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteId, _flag]);
                if (_flag === 'Activate') {
                    mzAjaxRequest2('att_group/activate_site/'+_siteId, 'PUT');
                    $('#lblSacSiteStatus').html('Enabled');
                    $('#divSacActivate').hide();
                    $('#divSacDeactivate').show();
                    hasEdit = true;
                } else if (_flag === 'Deactivate') {
                    mzAjaxRequest2('att_group/deactivate_site/'+_siteId, 'PUT');
                    $('#lblSacSiteStatus').html('Disabled');
                    $('#divSacActivate').show();
                    $('#divSacDeactivate').hide();
                    hasEdit = true;
                } else {
                    toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
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
            const dataDb = mzAjaxRequest2('att_participant/by_site/'+siteId+'/'+currentYear+'/'+currentMonth, 'GET');
            oTableSacParticipant.clear().rows.add(dataDb).draw();
        } catch (e) {
            throw new Error(e.message);
        }
    };

    this.genTablePlanner = function () {
        try {
            const dataDb = mzAjaxRequest2('att_transaction/monthly/site/'+siteId+'/'+currentYear+'/'+currentMonth, 'GET');
            oTableSacPlanner.clear().rows.add(dataDb).draw();
        } catch (e) {
            throw new Error(e.message);
        }
    };

    this.getCellDisplay = function (_data, _day, _metaRow) {
        try {
            if (typeof _day !== 'number' || _day < 1 || _day > parseInt(thisDate.endOf('month').format('D'))) {
                return '';
            }
            const attTypeId = _data[_day]['attTypeId'];
            let colorHoover = 'white-darker-hover';
            const shiftMode = refAttType[attTypeId]['attTypeMode'];
            if (shiftMode === 'Normal' || shiftMode === '2 Shifts' || shiftMode === '3 Shifts') {
                if (_data[_day]['result'] === 'Present') {
                    //colorHoover = 'success-lighter-hover';
                    colorHoover = 'white-darker-hover';
                } else if (_data[_day]['result'] === 'Absent') {
                    colorHoover = 'danger-lighter-hover';
                } else {
                    colorHoover = moment().isAfter(_data[_day]['attTransactionDate']) ? 'danger-lighter-hover' : 'white-darker-hover';
                }
            }
            const dayName = _data[_day]['dayName'];
            $('#thSacPlannerDay'+_day).text(dayName.substr(0, 2));
            return '<a><span class="' + colorHoover + ' lnkSacPlannerInfo" id="lnkSacPlannerInfo_' + _metaRow + '_' + _day + '" data-toggle="tooltip" data-placement="top" title="Click to view / edit daily details">' + refAttType[attTypeId]['attTypeShort'] + '</span></a>';
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

    this.setSectionAttendanceGroupClass = function (_sectionAttendanceGroupClass) {
        sectionAttendanceGroupClass = _sectionAttendanceGroupClass;
    };

    this.setHasEdit = function (_hasEdit) {
        hasEdit = _hasEdit;
    };
}