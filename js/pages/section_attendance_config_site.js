function SectionAttendanceConfigSite () {

    const className = 'SectionAttendanceConfigSite';
    let self = this;
    let classFrom;
    let hasEdit = false;
    let siteId;
    let siteName;
    let userId;
    let refStatus;
    let refUser;
    let refAttGroup;
    let oTableSacGroup;
    let oTableSacParticipant;
    let modalAttendanceGroupClass;
    let modalConfirmSubmitClass;
    let isAdmin;
    let isSupervisor;

    this.init = function () {
        $('#btnSacBack').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (hasEdit) {
                        classFrom.genTable();
                    }
                    $('.sectionAttendanceConfigSite').hide();
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
        isSupervisor = mzIsRoleExist('20');

        oTableSacGroup = $('#dtSacGroup').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 50,
            autoWidth: false,
            dom: "<'row'<'col-5 px-0'B><'col-7 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0,3,4,5,7,9] },
                { className: 'text-right', targets: [6,8] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ', text:'<i class="fas fa-print"></i>', title:'GEMS - Attendance Group List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 ', text:'<i class="fas fa-copy"></i>', title:'GEMS - Attendance Group List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 ', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Attendance Group List', titleAttr: 'Excel', exportOptions: mzExportOpt},
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
            },
            aoColumns: [
                {mData: null},
                {mData: 'attGroupName'},
                {mData: 'attGroupSupervisor', mRender: function(data) {
                        return data !== '' ? refUser[parseInt(data)]['userFullName'] : '-';
                    }},
                {mData: null, width: '12%', mRender: function(data, type, row) {
                        return row['attGroupDayShiftStart'] + ' to ' + row['attGroupDayShiftEnd'];
                    }},
                {mData: null, width: '12%', mRender: function(data, type, row) {
                        return row['attGroupNightShiftStart'] + ' to ' + row['attGroupNightShiftEnd'];
                    }},
                {mData: 'attGroupHoliday', width: '10%'},
                {mData: 'attGroupReqWeekHours', width: '8%'},
                {mData: 'attGroupShiftMode', width: '8%'},
                {mData: 'totalParticipantActive', width: '8%', mRender: function(data) {
                        return data !== '' ? data : '0';
                    }},
                {mData: 'attGroupStatus', width: '6%', mRender: function(data) {
                        return refStatus[parseInt(data)]['statusDesc'];
                    }}
            ]
        });
        if ($('#headerSacGroup').width() < 880) {
            oTableSacGroup.column(3).visible(false);
            oTableSacGroup.column(4).visible(false);
            oTableSacGroup.column(5).visible(false);
        }
        let oTableSacGroupTbody = $('#dtSacGroup tbody');
        oTableSacGroupTbody.delegate('tr', 'click', function (evt) {
            const data = $('#dtSacGroup').DataTable().row(this).data();
            if (typeof data !== 'undefined' && (isAdmin || (!isAdmin && isSupervisor && userId === data['attGroupSupervisor']))) {
                modalAttendanceGroupClass.edit(data['attGroupId'], siteId);
            }
        });
        oTableSacGroupTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtSacGroup').DataTable().row(this).data();
            if (typeof data !== 'undefined' && (isAdmin || (!isAdmin && isSupervisor && userId === data['attGroupSupervisor']))) {
                const cell = $(evt.target).closest('td');
                cell.css('cursor', 'pointer');
                cell.attr('data-toggle', 'tooltip');
                cell.attr('title', 'Click to edit '+data['attGroupName']+' configuration');
                $('[data-toggle="tooltip"]').tooltip();
            }
        });

        oTableSacParticipant = $('#dtSacParticipant').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[10, 'asc'], [1, 'asc'], [2, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 50,
            autoWidth: false,
            dom: "<'row'<'col-5 px-0'B><'col-7 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0,3,7,9,10] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2', text:'<i class="fas fa-print"></i>', title:'GEMS - Attendance Participant List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0', text:'<i class="fas fa-copy"></i>', title:'GEMS - Attendance Participant List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Attendance Participant List', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Attendance Participant List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt},
                { text: 'All', className: 'btn btn-outline-blue btn-sm px-2 ml-3', attr: { id: 'btnSacParticipantStatusAll' }},
                { text: 'Assigned', className: 'btn btn-outline-success btn-sm px-2 ml-0', attr: { id: 'btnSacParticipantStatusAssigned' }},
                { text: 'Not Assigned', className: 'btn btn-outline-grey btn-sm px-2 ml-0', attr: { id: 'btnSacParticipantStatusNotAssigned' }}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('#btnSacParticipantStatusAll').off('click').on('click', function () {
                    oTableSacParticipant.search('').columns().search('').draw();
                    $('#dtSacParticipantTitle').text('Participant List')
                });
                $('#btnSacParticipantStatusAssigned').off('click').on('click', function () {
                    oTableSacParticipant.search('').columns().search('');
                    oTableSacParticipant.column(10).search('^(Active|Disabled)$', true, false).draw();
                    $('#dtSacParticipantTitle').text('Participant List (Assigned)')
                });
                $('#btnSacParticipantStatusNotAssigned').off('click').on('click', function () {
                    oTableSacParticipant.search('').columns().search('');
                    oTableSacParticipant.column(10).search('^(Not Assigned)$', true, false).draw();
                    $('#dtSacParticipantTitle').text('Participant List (Not Assigned)')
                });
            },
            aoColumns: [
                {mData: null},
                {mData: 'userFirstName'},
                {mData: 'attGroupId', mRender: function(data) {
                        return data !== '' ? refAttGroup[parseInt(data)]['attGroupName'] : '';
                    }},
                {mData: 'attParticipantGfId'},
                {mData: 'designationDesc'},
                {mData: 'userContactNo'},
                {mData: 'userEmail'},
                {mData: 'attParticipantShift'},
                {mData: 'attParticipantCompetency'},
                {mData: 'attParticipantCidbCardExpiry'},
                {mData: 'participantStatus', width: '6%', mRender: function(data) {
                        return refStatus[parseInt(data)]['statusDesc'];
                    }}
            ]
        });
        oTableSacParticipant.column(6).visible(false);
        oTableSacParticipant.column(8).visible(false);
        oTableSacParticipant.column(9).visible(false);
        if ($('#headerSacParticipant').width() < 880) {
            oTableSacParticipant.column(4).visible(false);
            oTableSacParticipant.column(5).visible(false);
            oTableSacParticipant.column(7).visible(false);
        }
        let oTableSacParticipantTbody = $('#dtSacParticipant tbody');
        oTableSacParticipantTbody.delegate('tr', 'click', function (evt) {
            const data = $('#dtSacParticipant').DataTable().row(this).data();
            if (typeof data !== 'undefined' && (isAdmin || (!isAdmin && isSupervisor && userId === data['attGroupSupervisor']))) {
                //modalAttendanceGroupClass.edit(data['attGroupId'], siteId);
            }
        });
        oTableSacParticipantTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtSacParticipant').DataTable().row(this).data();
            if (typeof data !== 'undefined' && (isAdmin || (!isAdmin && isSupervisor && userId === data['attGroupSupervisor']))) {
                const cell = $(evt.target).closest('td');
                cell.css('cursor', 'pointer');
                cell.attr('data-toggle', 'tooltip');
                cell.attr('title', 'Click to edit '+data['attGroupName']+' configuration');
                $('[data-toggle="tooltip"]').tooltip();
            }
        });

        $('#btnSacActivate').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    modalConfirmSubmitClass.setClassFrom(self);
                    modalConfirmSubmitClass.load(siteId, 'Activate', 'Are you sure to <b>ACTIVATE</b> attendance for <b>'+siteName+'</b> site?');
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnSacDeactivate').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    modalConfirmSubmitClass.setClassFrom(self);
                    modalConfirmSubmitClass.load(siteId, 'Deactivate', 'Are you sure to <b>DEACTIVATE</b> attendance for <b>'+siteName+'</b> site?');
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('.sectionAttendanceConfigSite').hide();
    };

    this.loadDetails = function () {
        try {
            mzCheckFuncParam([siteId]);
            const attSite = mzAjaxRequest2('att_group/site/'+siteId, 'GET');
            siteName = attSite['siteName'];
            $('#lblSacSiteName').html(siteName);
            $('#lblSacSiteCode').html(attSite['siteCode']);
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
            modalAttendanceGroupClass.setSiteName(attSite['siteName']);
            modalAttendanceGroupClass.setSiteCode(attSite['siteCode']);
            self.genTableGroup();
            self.genTableParticipant();
            oTableSacParticipant.search('').columns().search('').draw();
            $('#dtSacParticipantTitle').text('Participant List')
        } catch (e) {
            throw new Error(e.message);
        }
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
                self.loadDetails();
                hasEdit = false;
                $('.sectionAttendanceConfigSite').show();
                classFrom.hideMain();
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
        const dataDb = mzAjaxRequest2('att_group/by_site/'+siteId, 'GET');
        refAttGroup = [];
        $.each(dataDb, function (n, row) {
            refAttGroup[parseInt(row['attGroupId'])] = row;
        });
        oTableSacGroup.clear().rows.add(dataDb).draw();
    };

    this.genTableParticipant = function () {
        const dataDb = mzAjaxRequest2('att_participant/by_site/'+siteId, 'GET');
        oTableSacParticipant.clear().rows.add(dataDb).draw();
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

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setModalAttendanceGroupClass = function (_modalAttendanceGroupClass) {
        modalAttendanceGroupClass = _modalAttendanceGroupClass;
    };

    this.setModalConfirmSubmitClass = function (_modalConfirmSubmitClass) {
        modalConfirmSubmitClass = _modalConfirmSubmitClass;
    };

    this.setHasEdit = function (_hasEdit) {
        hasEdit = _hasEdit;
    };
}