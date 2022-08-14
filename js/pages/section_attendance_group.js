function SectionAttendanceGroup () {

    const className = 'SectionAttendanceGroup';
    let self = this;
    let classFrom = {};
    let attGroupId = 0;
    let attGroup = {};
    let currentYear = 0;
    let currentMonth = 0;
    let thisDate;
    let refStatus = [];
    let refUser = [];
    let refAssetGroup = [];
    let refAttType = [];
    let refAttGroup = [];
    let refSite = [];
    let hasEditGroup = false;
    let hasEditParticipant = false;
    let hasEditPlanner = false;
    let googleMapsDrawingPolygonClass = {};
    let oTableSagParticipant;
    let userId;
    let isSupervisor;

    this.init = function () {
        self.hideMain();
        thisDate = moment();
        googleMapsDrawingPolygonClass.initMapDrawing('mapSagPerimeter');
        googleMapsDrawingPolygonClass.setDrawingControl(false);
        userId = parseInt(mzGetUserId());
        isSupervisor = mzIsRoleExist('20');

        $('#btnSagBack').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!jQuery.isEmptyObject(classFrom)) {
                        if (classFrom.getClassName() === 'SectionAttendanceSite') {
                            if (hasEditGroup) {
                                classFrom.genTableGroup();
                                classFrom.genChart();
                            }
                            if (hasEditParticipant) {
                                classFrom.genTableParticipant();
                            }
                            if (hasEditPlanner) {
                                classFrom.genTablePlanner();
                            }
                        }
                        classFrom.showMain();
                    }
                    self.hideMain();
                    $(window).scrollTop(0);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        oTableSagParticipant = $('#dtSagParticipant').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[13, 'asc'], [1, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 100,
            autoWidth: false,
            dom: "<'row'<'col-12 col-sm-6 px-0 pb-2'B><'col-sm-6 d-none d-sm-block pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 26] },
                { className: 'text-center', targets: [0, 2, 4, 7, 10, 11, 12, 13, 26] },
                { className: 'text-right', targets: [9, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25] },
                { visible: false, targets: [4, 5, 6, 7, 8, 9, 10, 11, 12, 16, 17, 18, 19, 20, 21, 23, 25] },
                { className: 'noVis', targets: [0, 26] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'four-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2', text:'<i class="fas fa-print"></i>', title:'GEMS - Monthly Employee Summary', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0', text:'<i class="fas fa-copy"></i>', title:'GEMS - Monthly Employee Summary', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Monthly Employee Summary', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Monthly Employee Summary', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkSagParticipantEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableSagParticipant.row(parseInt(rowId)).data();
                        if (isSupervisor && userId === currentRow['attGroupSupervisor']) {
                            //modalAttendanceParticipantClass.setParticipantName(currentRow['userFirstName']);
                            //modalAttendanceParticipantClass.setSiteName(siteName);
                            //modalAttendanceParticipantClass.load(currentRow['userIds'], currentRow['attParticipantId'], siteId);
                        } else {
                            toastr['error']('You don\'t have permission as Supervisor role to perform this task!', _ALERT_TITLE_ERROR);
                            return false;
                        }
                    }
                });
                $('.lnkSagParticipantDetail').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableSagParticipant.row(parseInt(rowId)).data();
                        //sectionAttendancePlannerClass.load(currentRow['attParticipantId']);
                    }
                });
                if ($('#divPageWidth').width() < 880) {
                    $(this).DataTable().column(2).visible(false);
                    $(this).DataTable().column(3).visible(false);
                    $(this).DataTable().column(4).visible(false);
                    $(this).DataTable().column(13).visible(false);
                    $(this).DataTable().column(22).visible(false);
                    $(this).DataTable().column(24).visible(false);
                }
                if (!jQuery.isEmptyObject(classFrom)) {
                    $(this).DataTable().column(26).visible(false);
                }
            },
            aoColumns: [
                {mData: null},
                {mData: 'userFirstName'},
                {mData: 'attParticipantGfId'},
                {mData: 'designationDesc'},
                {mData: 'userContactNo'},
                {mData: 'userEmail'},   // 5
                {mData: 'attParticipantCompetency'},
                {mData: 'attParticipantCidbCardExpiry'},
                {mData: 'assetGroupId', mRender: function(data) {
                        return data !== null ? refAssetGroup[data]['assetGroupName'] : '';
                    }},
                {mData: 'attParticipantReqWeekHours'},
                {mData: 'attParticipantShiftMode'}, // 10
                {mData: 'attTypeId', mRender: function(data) {
                        return data !== null ? refAttType[data]['attTypeName'] : '';
                    }},
                {mData: 'attParticipantHoliday'},
                {mData: 'participantStatus', width: '8%', mRender: function(data) {
                        return refStatus[data]['statusDesc'];
                    }},
                {mData: 'totalPresent', mRender: function(data, type, row) {
                        return row['participantStatus'] === 1 ? (data !== '0' ? '<span class="green-text">'+data+'</span>' : '0') : '';
                    }},
                {mData: 'totalAbsent', mRender: function(data, type, row) { // 15
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
                {mData: 'totalRd', mRender: function(data, type, row) {
                        return row['participantStatus'] === 1 ? data : '';
                    }},
                {mData: 'totalPh', mRender: function(data, type, row) { // 20
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
                {mData: 'totalOutside', mRender: function(data, type, row) {
                        return row['participantStatus'] === 1 ? (data !== '0' ? '<span class="red-text">'+data+'</span>' : '0') : '';
                    }},
                {mData: 'totalHours', mRender: function(data, type, row) { // 25
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
                        if (isSupervisor && userId === row['attGroupSupervisor']) {
                            label = '<a><i class="fas fa-edit mr-1 lnkSagParticipantEdit" id="lnkSagParticipantEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Click to edit ' + row['userFirstName'] + ' configuration"></i></a>';
                        }
                        label += '<a><i class="fas fa-user lnkSagParticipantDetail" id="lnkSagParticipantDetail_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Click to see ' + row['userFirstName'] + ' informations"></i></a>';
                        return label;
                    }}
            ]
        });
    };

    this.load = function (_attGroupId, _year, _month) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_attGroupId, _year, _month]);
                attGroupId = _attGroupId;
                currentYear = _year;
                currentMonth = _month;
                hasEditGroup = false;
                hasEditParticipant = false;
                hasEditPlanner = false;
                thisDate.set({'year': currentYear, 'month': currentMonth - 1});
                $('#lblSagSelected').text(thisDate.format('MMMM, YYYY'));

                if (jQuery.isEmptyObject(attGroup)) {
                    $('#liSagGroup').hide();
                }
                if (attGroupId === 0) {
                    if ($('#divPageWidth').width() < 544) {
                        $('#lblSagSelected').hide();
                    }
                    const groupArr = mzAjaxRequest2('att_group/supervisor', 'GET');
                    if (groupArr.length > 1) {
                        $('#liSagGroup').show();
                        $.each(groupArr, function (i, groupId) {
                            if (attGroupId === 0 && refAttGroup[groupId]['attGroupStatus'] === 1) {
                                attGroupId = groupId;
                            }
                            const isActive = attGroupId === groupId ? 'active text-white' : '';
                            $('#divSagGroup').append('<a class="dropdown-item lnkSagGroup '+isActive+'" href="#" id="lnkSagGroup_'+groupId+'">'+refAttGroup[groupId]['attGroupName']+'</a>');
                        });
                        if (attGroupId === 0) {
                            attGroupId = groupArr[0];
                        } else {
                            $('.lnkSagGroup').off('click').on('click', function () {
                                const linkId = $(this).attr('id');
                                ShowLoader();
                                setTimeout(function () {
                                    try {
                                        const linkArray = linkId.split('_');
                                        if (linkArray.length === 2 && linkArray[0] === 'lnkSagGroup') {
                                            self.load(parseInt(linkArray[1]), currentYear, currentMonth);
                                            $('.lnkSagGroup').removeClass('active').removeClass('text-white');
                                            $('#lnkSagGroup_' + linkArray[1]).addClass('active').addClass('text-white');
                                        } else {
                                            throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                                        }
                                    } catch (e) {
                                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                                    }
                                    HideLoader();
                                }, 300);
                            });
                        }
                    } else if (groupArr.length === 1) {
                        attGroupId = groupArr[0];
                    } else {
                        throw Error('You have no Attendance Group assigned to you as Supervisor. Please contact Site Admin!');
                    }
                }

                attGroup = mzAjaxRequest2('att_group/'+attGroupId, 'GET');
                console.log(attGroup);
                $('#lblSagSiteName').text(refSite[attGroup['siteId']]['siteName']);
                $('#lblSagGroupName').text(attGroup['attGroupName']);
                $('#lblSagTotalActive').text(mzReplaceNull(attGroup['totalParticipantActive'], '0')+' active employee'+(attGroup['totalParticipantActive']>1?'s':''));
                $('#pSagName').text(refSite[attGroup['siteId']]['siteName']);
                $('#pSagCategory').text(refAssetGroup[attGroup['assetGroupId']]['assetGroupName']);
                $('#pSagSupervisor').text(refUser[attGroup['attGroupSupervisor']]['userFirstName']);
                $('#pSagRequiredHours').text(attGroup['attGroupReqWeekHours']);
                $('#pSagHoliday').text(attGroup['attGroupHoliday']);
                $('#pSagOtApprover').text(refUser[attGroup['attGroupOtApprover']]['userFirstName']);
                $('#pSagRemark').text(mzReplaceNull(attGroup['attGroupRemark'], '-'));
                $('#pSagStatus').text(refStatus[attGroup['attGroupStatus']]['statusDesc']);
                $('#pSagNormalHour').text(attGroup['attGroupNormalHours']);
                $('#pSagAmHour').text(attGroup['attGroupAmHours']);
                $('#pSagPmHour').text(attGroup['attGroupPmHours']);
                $('#pSagMorningHour').text(attGroup['attGroupMorningHours']);
                $('#pSagEveningHour').text(attGroup['attGroupEveningHours']);
                $('#pSagNightHour').text(attGroup['attGroupNightHours']);
                const polygon = JSON.parse(attGroup['attGroupPolygon']);
                googleMapsDrawingPolygonClass.setDrawingManager('mapSagPerimeter', attGroup['attGroupMapCenterLat'], attGroup['attGroupMapCenterLng'], attGroup['attGroupMapZoom']-1);
                googleMapsDrawingPolygonClass.drawPolygon('mapSagPerimeter', polygon['coordinates'][0]);

                self.genTableParticipant();

                if (jQuery.isEmptyObject(classFrom)) {
                    $('#liSagYear, #liSagMonth, #divSagEdit').show();
                    $('#divSagBack').hide();
                } else {
                    classFrom.hideMain();
                    $('#liSagYear, #liSagMonth, #divSagEdit').hide();
                    $('#divSagBack').show();
                }
                self.showMain();
                window.scrollTo({top: 0, behavior: 'smooth'});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.genTableParticipant = function () {
        try {
            const dataDb = mzAjaxRequest2('att_participant/by_group/'+attGroupId+'/'+currentYear+'/'+currentMonth, 'GET');
            oTableSagParticipant.clear().rows.add(dataDb).draw();
            let totalAll = 0, totalAbsent = 0, totalLate = 0, totalOutside = 0;
            let percAbsent = 0, percLate = 0, percOutside = 0;
            $.each(dataDb, function (n, row) {
                totalAll += parseInt(row['totalPresent']) + parseInt(row['totalAbsent']);
                totalAbsent += parseInt(row['totalAbsent']);
                totalLate += parseInt(row['totalInLate']);
                totalOutside += parseInt(row['totalOutside']);
            });
            if (totalAll !== 0) {
                percAbsent = Math.round(totalAbsent/totalAll*100);
                percLate = Math.round(totalLate/totalAll*100);
                percOutside = Math.round(totalOutside/(totalAll*2)*100);
            }
            $('#lblSagTotalAbsent').text(totalAbsent);
            $('#lblSagTotalLateness').text(totalLate);
            $('#lblSagTotalOutside').text(totalOutside);
            $('#progressSagAbsent').css('width',percAbsent+'%').attr('aria-valuenow', percAbsent);
            $('#pSagTotalAbsent').text(percAbsent+'% out of '+totalAll);
            $('#progressSagLateness').css('width',percLate+'%').attr('aria-valuenow', percLate);
            $('#pSagTotalLateness').text(percLate+'% out of '+(totalAll*2));
            $('#progressSagOutside').css('width',percOutside+'%').attr('aria-valuenow', percOutside);
            $('#pSagTotalOutside').text(percOutside+'% out of '+totalAll);

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
        $('.sectionAttendanceGroup').show();
    };

    this.hideMain = function () {
        $('.sectionAttendanceGroup').hide();
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

    this.setRefAttGroup = function (_refAttGroup) {
        refAttGroup = _refAttGroup;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setHasEditGroup = function (_hasEditGroup) {
        hasEditGroup = _hasEditGroup;
    };

    this.setHasEditParticipant = function (_hasEditParticipant) {
        hasEditParticipant = _hasEditParticipant;
    };

    this.setHasEditPlanner = function (_hasEditPlanner) {
        hasEditPlanner = _hasEditPlanner;
    };

    this.setGoogleMapsDrawingPolygon = function (_googleMapsDrawingPolygonClass) {
        googleMapsDrawingPolygonClass = _googleMapsDrawingPolygonClass;
    };
}