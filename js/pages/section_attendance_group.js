function SectionAttendanceGroup () {

    const className = 'SectionAttendanceGroup';
    let self = this;
    let formValidate;
    let classFrom = {};
    let attGroupId = 0;
    let attGroup = {};
    let currentYear = 0;
    let currentMonth = 0;
    let siteId = 0;
    let thisDate;
    let todayDate;
    let dailyDate;
    let refStatus;
    let refUser;
    let refAssetGroup;
    let refAttType;
    let refAttGroup;
    let refSite;
    let hasEditGroup = false;
    let hasEditParticipant = false;
    let hasEditPlanner = false;
    let googleMapsDrawingPolygonClass = {};
    let oTableSagDaily;
    let oTableSagParticipant;
    let oTableSagPlanner;
    let userId = 0;
    let isSupervisor = false;
    let modalAttendanceGroupClass;
    let modalAttendanceParticipantClass;
    let modalAttendanceDailyClass;

    this.init = function () {
        self.hideMain();
        thisDate = moment();
        todayDate = moment();
        dailyDate = moment();
        googleMapsDrawingPolygonClass.initMapDrawing('mapSagPerimeter');
        googleMapsDrawingPolygonClass.setDrawingControl(false);
        userId = parseInt(mzGetUserId());
        isSupervisor = mzIsRoleExist('20');

        const vData = [
            {
                field_id: 'txtSagDailyDate',
                type: 'text',
                name: 'Attendance Date',
                validator: {
                    notEmpty: true
                }
            }
        ];

        formValidate = new MzValidate('formSag');
        formValidate.registerFields(vData);

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

        oTableSagDaily = $('#dtSagDaily').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 100,
            autoWidth: false,
            dom: "<'row'<'col-12 col-sm-6 px-0 pb-2'B><'col-sm-6 d-none d-sm-block pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0, 2, 3, 4, 5, 7, 8, 10, 11, 12, 13, 14, 15] },
                { visible: false, targets: [1, 4, 6, 7, 9, 10, 14] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'four-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2', text:'<i class="fas fa-print"></i>', title:'GEMS - Daily Summary', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0', text:'<i class="fas fa-copy"></i>', title:'GEMS - Daily Summary', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Daily Summary', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Daily Summary', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                if ($('#divPageWidth').width() < 880) {
                    $(this).DataTable().column(2).visible(false);
                }
            },
            aoColumns: [
                {mData: null},
                {mData: 'userId', mRender: function(data) {
                        return refUser[data]['userFirstName'];
                    }},
                {mData: 'attParticipantShiftMode'},
                {mData: 'attTypeId', mRender: function(data) {
                        return data !== null ? refAttType[data]['attTypeName'] : '';
                    }},
                {mData: 'attTransactionShiftStart'},
                {mData: 'attTransactionTimeIn', mRender: function(data, type, row) {
                        if (data === null) {
                            return '';
                        }
                        const color = row['durationInLate'] === 'Early' ? 'green' : 'red';
                        return '<span class="'+color+'-text">'+data+'</span>';
                    }}, // 5
                {mData: 'durationIn', mRender: function(data, type, row) {
                        if (data === null) {
                            return '';
                        }
                        const color = row['durationInLate'] === 'Early' ? 'green' : 'red';
                        return '<span class="'+color+'-text">'+data+' '+row['durationInLate']+'</span>';
                    }},
                {mData: 'attTransactionShiftEnd'},
                {mData: 'attTransactionTimeOut', mRender: function(data, type, row) {
                        if (data === null) {
                            return '';
                        }
                        const color = row['durationOutLate'] === 'Early' ? 'red' : 'green';
                        return '<span class="'+color+'-text">'+data+'</span>';
                    }},
                {mData: 'durationOut', mRender: function(data, type, row) {
                        if (data === null) {
                            return '';
                        }
                        const color = row['durationOutLate'] === 'Early' ? 'red' : 'green';
                        return '<span class="'+color+'-text">'+data+' '+row['durationOutLate']+'</span>';
                    }},
                {mData: 'durationNeeded'}, // 10
                {mData: 'durationWork'},
                {mData: 'locationInsideIn', mRender: function(data) {
                        if (data === null) {
                            return '';
                        }
                        const color = data === 'Inside Parameter' ? 'green' : 'red';
                        return '<span class="'+color+'-text">'+data+'</span>';
                    }},
                {mData: 'locationInsideOut', mRender: function(data) {
                        if (data === null) {
                            return '';
                        }
                        const color = data === 'Inside Parameter' ? 'green' : 'red';
                        return '<span class="'+color+'-text">'+data+'</span>';
                    }},
                {mData: 'attTransactionStatus'},
                {mData: 'result', mRender: function(data) {
                        if (data === null) {
                            return '';
                        }
                        const color = data === 'Present' ? 'green' : 'red';
                        return '<span class="'+color+'-text">'+data+'</span>';
                    }}
            ]
        });

        oTableSagParticipant = $('#dtSagParticipant').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
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
                            modalAttendanceParticipantClass.setParticipantName(currentRow['userFirstName']);
                            modalAttendanceParticipantClass.setSiteName(refSite[siteId]['siteName']);
                            modalAttendanceParticipantClass.load(currentRow['userIds'], currentRow['attParticipantId'], siteId);
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

        oTableSagPlanner = $('#dtSagPlanner').DataTable({
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
                $('.lnkSagPlannerInfo').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkArray = linkId.split('_');
                    if (linkArray.length === 3 && linkArray[0] === 'lnkSagPlannerInfo') {
                        const currentRow = oTableSagPlanner.row(parseInt(linkArray[1])).data();
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

        $('#btnSagRunPlanner').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    mzAjaxRequest2('att_transaction/reschedule/group/'+attGroupId+'/'+currentYear+'/'+currentMonth, 'PUT');
                    self.genTablePlanner();
                    hasEditPlanner = true;
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('.lnkSagYear').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            ShowLoader();
            setTimeout(function () {
                try {
                    const linkArray = linkId.split('_');
                    if (linkArray.length === 2 && linkArray[0] === 'lnkSagYear') {
                        currentYear = parseInt(linkArray[1]);
                        thisDate.set({'year': currentYear, 'month': currentMonth - 1, 'date': 1});
                        dailyDate.set(thisDate.format('YYYY') === todayDate.format('YYYY') && thisDate.format('M') === todayDate.format('M') ? todayDate.toObject() : thisDate.toObject());
                        $('#lblSagSelected').text(thisDate.format('MMMM, YYYY'));
                        $('.lnkSagYear').removeClass('active').removeClass('text-white');
                        $('#lnkSagYear_' + currentYear).addClass('active').addClass('text-white');
                        self.genTableDaily();
                        self.genTableParticipant();
                        self.genTablePlanner();
                        let tempDate = moment();
                        tempDate.set(dailyDate.toObject());
                        tempDate.set({'date': 1});
                        mzDateSetMin('txtSagDailyDate', tempDate.format('YYYY-MM-DD'));
                        tempDate.set({'date': parseInt(tempDate.endOf('month').format('D'))});
                        mzDateSetMax('txtSagDailyDate', tempDate.format('YYYY-MM-DD'));
                        mzSetFieldValue('SagDailyDate', dailyDate.format('YYYY-MM-DD'), 'date');
                    } else {
                        throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('.lnkSagMonth').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            ShowLoader();
            setTimeout(function () {
                try {
                    const linkArray = linkId.split('_');
                    if (linkArray.length === 2 && linkArray[0] === 'lnkSagMonth') {
                        currentMonth = parseInt(linkArray[1]);
                        thisDate.set({'year': currentYear, 'month': currentMonth - 1, 'date': 1});
                        dailyDate.set(thisDate.format('YYYY') === todayDate.format('YYYY') && thisDate.format('M') === todayDate.format('M') ? todayDate.toObject() : thisDate.toObject());
                        $('#lblSagSelected').text(thisDate.format('MMMM, YYYY'));
                        $('.lnkSagMonth').removeClass('active').removeClass('text-white');
                        $('#lnkSagMonth_' + currentMonth).addClass('active').addClass('text-white');
                        self.genTableDaily();
                        self.genTableParticipant();
                        self.genTablePlanner();
                        let tempDate = moment();
                        tempDate.set(dailyDate.toObject());
                        tempDate.set({'date': 1});
                        mzDateSetMin('txtSagDailyDate', tempDate.format('YYYY-MM-DD'));
                        tempDate.set({'date': parseInt(tempDate.endOf('month').format('D'))});
                        mzDateSetMax('txtSagDailyDate', tempDate.format('YYYY-MM-DD'));
                        mzSetFieldValue('SagDailyDate', dailyDate.format('YYYY-MM-DD'), 'date');
                    } else {
                        throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('#btnSagEdit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (isSupervisor && userId === refAttGroup[attGroupId]['attGroupSupervisor']) {
                        modalAttendanceGroupClass.setSiteName(refSite[siteId]['siteName']);
                        modalAttendanceGroupClass.setSiteCode(refSite[siteId]['siteCode']);
                        modalAttendanceGroupClass.edit(attGroupId, siteId);
                    } else {
                        toastr['error']('You don\'t have permission as Supervisor role for this group to perform this task!', _ALERT_TITLE_ERROR);
                        return false;
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnSagDailyDate').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }  else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        dailyDate = moment(mzConvertDate($('#txtSagDailyDate').val()));
                        self.genTableDaily();
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
    };

    this.load = function (_attGroupId, _year, _month) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_attGroupId]);
                attGroupId = _attGroupId;
                if (typeof _year !== 'undefined') {
                    currentYear = _year;
                }
                if (typeof _month !== 'undefined') {
                    currentMonth = _month;
                }
                hasEditGroup = false;
                hasEditParticipant = false;
                hasEditPlanner = false;
                thisDate.set({'year': currentYear, 'month': currentMonth - 1, 'date': 1});
                $('#lblSagSelected').text(thisDate.format('MMMM, YYYY'));
                $('.lnkSagYear').removeClass('active').removeClass('text-white');
                $('#lnkSagYear_' + currentYear).addClass('active').addClass('text-white');
                $('.lnkSagMonth').removeClass('active').removeClass('text-white');
                $('#lnkSagMonth_' + currentMonth).addClass('active').addClass('text-white');

                if (typeof _month !== 'undefined') {
                    dailyDate.set(thisDate.format('YYYY') === todayDate.format('YYYY') && thisDate.format('M') === todayDate.format('M') ? todayDate.toObject() : thisDate.toObject());
                }

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
                        }
                        $('.lnkSagGroup').off('click').on('click', function () {
                            const linkId = $(this).attr('id');
                            ShowLoader();
                            setTimeout(function () {
                                try {
                                    const linkArray = linkId.split('_');
                                    if (linkArray.length === 2 && linkArray[0] === 'lnkSagGroup') {
                                        self.load(parseInt(linkArray[1]));
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
                    } else if (groupArr.length === 1) {
                        attGroupId = groupArr[0];
                    } else {
                        throw Error('You have no Attendance Group assigned to you as Supervisor. Please contact Site Admin!');
                    }
                }

                attGroup = mzAjaxRequest2('att_group/'+attGroupId, 'GET');
                siteId = attGroup['siteId'];
                $('#lblSagSiteName').text(refSite[siteId]['siteName']);
                $('#lblSagGroupName').text(attGroup['attGroupName']);
                $('#lblSagTotalActive').text(mzReplaceNull(attGroup['totalParticipantActive'], '0')+' active employee'+(attGroup['totalParticipantActive']>1?'s':''));
                $('#pSagName').text(refSite[siteId]['siteName']);
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

                self.genTableDaily();
                self.genTableParticipant();
                self.genTablePlanner();

                let tempDate = moment();
                tempDate.set(dailyDate.toObject());
                tempDate.set({'date': 1});
                mzDateSetMin('txtSagDailyDate', tempDate.format('YYYY-MM-DD'));
                tempDate.set({'date': parseInt(tempDate.endOf('month').format('D'))});
                mzDateSetMax('txtSagDailyDate', tempDate.format('YYYY-MM-DD'));
                mzSetFieldValue('SagDailyDate', dailyDate.format('YYYY-MM-DD'), 'date');

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

    this.genTableDaily = function () {
        try {
            $('#h4SagDailyDate').text(dailyDate.format('(dddd, D/M/YYYY)'));
            const dataDb = mzAjaxRequest2('att_transaction/daily/'+attGroupId+'/'+dailyDate.format('YYYY-MM-DD'), 'GET');
            oTableSagDaily.clear().rows.add(dataDb).draw();
        } catch (e) {
            throw new Error(e.message);
        }
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

    this.genTablePlanner = function () {
        try {
            const dataDb = mzAjaxRequest2('att_transaction/monthly/group/'+attGroupId+'/'+currentYear+'/'+currentMonth, 'GET');
            oTableSagPlanner.clear().rows.add(dataDb).draw();
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
            $('#thSagPlannerDay'+_day).text(dayName.substr(0, 2));
            return '<a><span class="' + colorHoover + ' lnkSagPlannerInfo" id="lnkSagPlannerInfo_' + _metaRow + '_' + _day + '" data-toggle="tooltip" data-placement="top" title="Click to view / edit daily details">' + refAttType[attTypeId]['attTypeShort'] + '</span></a>';
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

    this.setModalAttendanceGroupClass = function (_modalAttendanceGroupClass) {
        modalAttendanceGroupClass = _modalAttendanceGroupClass;
    };

    this.setModalAttendanceDailyClass = function (_modalAttendanceDailyClass) {
        modalAttendanceDailyClass = _modalAttendanceDailyClass;
    };

    this.setModalAttendanceParticipantClass = function (_modalAttendanceParticipantClass) {
        modalAttendanceParticipantClass = _modalAttendanceParticipantClass;
    };
}