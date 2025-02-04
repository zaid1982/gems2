function SectionWo () {

    const className = 'SectionWo';
    let self = this;
    let classFrom;
    let refStatus;
    let refUser;
    let refSite;
    let refSeverity;
    let refPpmGroup;
    let refWoType = {
        1: 'Client Complaint',
        2: 'Self Finding',
        3: 'Request',
        4: 'Breakdown',
        5: 'Defect',
        6: 'Public Complaint'
    };
    let woTaskId;
    let formValidateSwoa;
    let formValidateSwov;
    let arrPpmGroupUser;
    let oTableCurrentTask;
    let oTableMaterials;
    let oTableAssistants;
    let isSubmitted = false;

    const vDataSwoa = [
        {
            field_id: 'optSwoaType',
            type: 'select',
            name: 'Category',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optSwoaSeverity',
            type: 'select',
            name: 'Severity',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optSwoaPpmGroup',
            type: 'select',
            name: 'Executor Group',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optSwoaAssignedTo',
            type: 'select',
            name: 'Executor',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optSwoaMaxAssistants',
            type: 'select',
            name: 'Maximum Assistants',
            validator: {
                notEmpty: true,
            }
        }
    ];

    const vDataSwov = [
        {
            field_id: 'radSwovAction',
            type: 'radio',
            name: 'Action',
            validator: {
                notEmptyCheck: true
            }
        },
        {
            field_id: 'radSwovRating',
            type: 'radio',
            name: 'Rating',
            validator: {
                notEmptyCheck: true
            }
        },
        {
            field_id: 'txaSwovRemark',
            type: 'text',
            name: 'Remark / Comment',
            validator: {
                notEmpty: false,
                maxLength: 1000
            }
        },
    ];

    this.init = function () {
        self.hideSection();
        
        $('#btnSwoBack').on('click', function () {
            self.hideSection();
            classFrom.showMain(isSubmitted);
            $(window).scrollTop(0);
        });

        formValidateSwoa = new MzValidate('formSwoa');
        formValidateSwoa.registerFields(vDataSwoa);

        formValidateSwov = new MzValidate('formSwov');
        formValidateSwov.registerFields(vDataSwov);

        $('#optSwoaPpmGroup').on('change', function () {
            const ppmGroupId = $(this).val();
            ShowLoader(); setTimeout(function () {
                try {
                    arrPpmGroupUser = mzAjaxRequest('wo.php?type=ppm_group_user_list&ppmGroupId='+ppmGroupId, 'GET');
                    mzOptionStop('optSwoaAssignedTo', arrPpmGroupUser, 'Select Executor', 'userId', 'userFirstName', {}, 'required');
                    mzDisableSelect('optSwoaAssignedTo', false);
                } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader(); }, 200);
        });

        $('#optSwoaAssignedTo').on('change', function () {
            const assignedTo = parseInt($(this).val());
            ShowLoader(); setTimeout(function () {
                try {
                    mzSetFieldValue('SwoaUserName', refUser[assignedTo]['userFirstName'], 'text');
                    mzSetFieldValue('SwoaUserContactNo', refUser[assignedTo]['userContactNo'], 'text');
                    mzSetFieldValue('SwoaUserEmail', refUser[assignedTo]['userEmail'], 'text');
                    const dataResult = mzAjaxRequest('wo.php?type=technician_current_task&userId='+assignedTo, 'GET');
                    oTableCurrentTask.clear().rows.add(dataResult).draw();
                    $('.divSwoaExecutorDetails').show();
                } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader(); }, 200);
        });

        $("input[name='radSwovAction']:radio").on('change', function () {
            const action = parseInt($(this).val());
            try {
                if (action === 1) {
                    $('#optSwovRating_').show();
                    formValidateSwov.enableField('radSwovRating');
                } else {
                    $('#optSwovRating_').hide();
                    formValidateSwov.disableField('radSwovRating');
                }
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        oTableCurrentTask =  $('#dtSwoaCurrentTask').DataTable({
            bLengthChange: false,
            bFilter: false,
            bInfo: false,
            ordering: false,
            bPaginate: true,
            language: _DATATABLE_LANGUAGE,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                $('td', nRow).eq(0).html(iDisplayIndex + 1);
            },
            aoColumns: [
                { mData: null, sClass: 'text-center'},
                { mData: 'woTaskNo', sClass: 'text-center'},
                { mData: 'dateReceived', sClass: 'text-center'}
            ]
        });
        
        oTableAssistants =  $('#dtSwoAssistants').DataTable({
            bLengthChange: false,
            bFilter: false,
            bInfo: false,
            ordering: false,
            bPaginate: true,
            language: _DATATABLE_LANGUAGE,
            autoWidth: false,
            columnDefs: [
                { bSortable: false, targets: [0] },
                //{ visible: false, targets: [5, 6] },
                { className: 'text-center', targets: [0, 2] },
                { className: 'noVis', targets: [0] }
            ],
            dom: "<'row'<'col-12px-0 pb-2'B>>" +
                "<'row'<'col-sm-12'tr>>",
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFctObserveHide', text:'<i class="fas fa-print"></i>', title:'GEMS - Assisted Technicians List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - Assisted Technicians List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Assisted Technicians List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-2 btnFctObserveHide', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Assisted Technicians List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                $('td', nRow).eq(0).html(iDisplayIndex + 1);
            },
            aoColumns: [
                { mData: null},
                { mData: 'userId', mRender: function (data) {
                        return data !== null ? refUser[data]['userFirstName'] : '';
                    }},
                { mData: 'userId', mRender: function (data) {
                        return data !== null ? refUser[data]['userContactNo'] : '';
                    }},
                { mData: 'userId', mRender: function (data) {
                        return data !== null ? refUser[data]['userEmail'] : '';
                    }}
            ]
        });
        
        oTableMaterials =  $('#dtSwoMaterials').DataTable({
            bLengthChange: false,
            bFilter: false,
            bInfo: false,
            ordering: false,
            bPaginate: true,
            language: _DATATABLE_LANGUAGE,
            autoWidth: false,
            columnDefs: [
                { bSortable: false, targets: [0, 6] },
                { visible: false, targets: [5, 6] },
                { className: 'text-center', targets: [0, 1, 5, 6] },
                { className: 'text-right', targets: [4] },
                { className: 'noVis', targets: [0] }
            ],
            dom: "<'row'<'col-12px-0 pb-2'B>>" +
                "<'row'<'col-sm-12'tr>>",
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFctObserveHide', text:'<i class="fas fa-print"></i>', title:'GEMS - Material Spare Parts List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - Material Spare Parts List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Material Spare Parts List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-2 btnFctObserveHide', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Material Spare Parts List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                $('td', nRow).eq(0).html(iDisplayIndex + 1);
            },
            aoColumns: [
                { mData: null},
                { mData: 'woTaskRequestNo'},
                { mData: 'itemTypeDesc'},
                { mData: 'itemDescription'},
                { mData: 'woTaskPartsQuantity'},
                { mData: 'woTaskRequestTimeOrdered'},
                { mData: 'woTaskPartsStatus', mRender: function (data) {
                        return '<h6><span class="badge badge-pill z-depth-2 '+refStatus[data]['statusColor']+'">'+refStatus[data]['statusDesc']+'</span></h6>';
                    }}
            ]
        });

        $('#btnSwoaSubmit').on('click', function () {
            try {
                if (!formValidateSwoa.validateNow()) {
                    toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                } else {
                    const data = {
                        woTaskType: mzParseInt($('#optSwoaType').val()),
                        woTaskSeverity: mzParseInt($('#optSwoaSeverity').val()),
                        ppmGroupId: mzParseInt($('#optSwoaPpmGroup').val()),
                        woTaskAssignedTo: mzParseInt($('#optSwoaAssignedTo').val()),
                        woTaskMaxAssistant: mzParseInt($('#optSwoaMaxAssistants').val())
                    };
                    ShowLoader(); setTimeout(function () {
                        mzFetch('wo_v3/submit_assign/'+woTaskId, 'PUT', data).then(res => {
                            isSubmitted = true;
                            self.loadDetails(woTaskId);
                            $('.divSwoAssign').hide();
                            $('.divSwoAssigned').show();
                            $('.divSwoImageLeft').show();
                        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                    }, 200);
                }
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#btnSwovSubmit').on('click', function () {
            try {
                if (!formValidateSwov.validateNow()) {
                    toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                } else {
                    const action = $("input[name='radSwovAction']:checked").val();
                    const rating = $("input[name='radSwovRating']:checked").val();
                    const url = action === '2' ? 'submit_return' : 'return_verify';
                    const data = {
                        remark: mzNullString('txaSwovRemark'),
                        rating: parseInt(rating)
                    };
                    ShowLoader(); setTimeout(function () {
                        mzFetch('wo_v3/'+url+'/'+woTaskId, 'PUT', data).then(res => {
                            isSubmitted = true;
                            self.loadDetails(woTaskId);
                            $('.divSwoAssign').hide();
                            $('.divSwoVerify').hide();
                            $('.divSwoAssigned').show();
                            $('.divSwoImageLeft').show();
                        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                    }, 200);
                }
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });
    };
    
    this.assign = function (_woTaskId) {
        ShowLoader(); setTimeout(function () { try {
            mzCheckFuncParam([_woTaskId]);
            woTaskId = _woTaskId;
            $('.divSwoAssign').show();
            $('.divSwoVerify').hide();
            $('.divSwoAssigned').hide();
            $('.divSwoImageLeft').hide();
            $('.divSwoaExecutorDetails').hide();
            isSubmitted = false;
            self.loadDetails('assign');
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }}, 200);
    };

    this.verify = function (_woTaskId) {
        ShowLoader(); setTimeout(function () { try {
            mzCheckFuncParam([_woTaskId]);
            woTaskId = _woTaskId;
            $('.divSwoVerify').show();
            $('.divSwoAssign').hide();
            $('.divSwoAssigned').show();
            $('.divSwoImageLeft').show();
            isSubmitted = false;
            self.loadDetails('verify');
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }}, 200);
    };
    
    this.view = function (_woTaskId) {
        ShowLoader(); setTimeout(function () { try {
            mzCheckFuncParam([_woTaskId]);
            woTaskId = _woTaskId;
            $('.divSwoAssign').hide();
            $('.divSwoVerify').hide();
            $('.divSwoAssigned').show();
            $('.divSwoImageLeft').show();
            isSubmitted = false;
            self.loadDetails('view');
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }}, 200);
    };
    
    this.loadDetails = function (_type) {
        try {
            Promise.all([
                mzFetch('wo_v3/'+woTaskId),
                mzFetch('wo_task_upload/by_woTaskId/'+woTaskId),
                mzFetch('wo_task_assist_v2/by_woTaskId/'+woTaskId),
                mzFetch('wo_v3/material_list/'+woTaskId)
            ]).then(responses => {
                const woTask = responses[0];
                const images = responses[1];
                const assistants = responses[2];
                const materials = responses[3];
                $('#divSwoWo, #divSwoWr, .divSwoPublic').hide();
                $('#lblSwoWoMainName').text('Work Order No.');
                if (woTask['woTaskRequestNo'] !== null) {
                    $('#divSwoWr').show();
                    $('#pSwoWrNo').text(woTask['woTaskRequestNo']);
                    if (woTask['woTaskNo'] !== woTask['woTaskRequestNo']) {
                        $('#lblSwoWoMainNo').text(woTask['woTaskNo']);
                        $('#divSwoWo').show();
                        $('#pSwoWoNo').text(woTask['woTaskNo']);
                    } else {
                        $('#lblSwoWoMainName').text('Work Request No.');
                        $('#lblSwoWoMainNo').text(woTask['woTaskRequestNo']);
                    }
                } else {
                    $('#lblSwoWoMainNo').text(woTask['woTaskNo']);
                    $('#divSwoWo').show();
                    $('#pSwoWoNo').text(woTask['woTaskNo']);
                }
                $('#pSwoStatus').text(refStatus[woTask['woTaskStatus']]['statusDesc']);
                $('#pSwoComplaint').text(woTask['woTaskComplaint']);
                $('#pSwoSite').text(refSite[woTask['siteId']]['siteName']);
                $('#pSwoCategory').text(refWoType[woTask['woTaskType']]);
                $('#pSwoSeverity').text(mzNullToValue(woTask['woTaskSeverity'], '-', 'severityName', refSeverity));
                $('#pSwoTimeReported').text(moment(woTask['woTaskTimeCreated']).format('MMMM Do, YYYY, hh:mm:ss'));
                $('#pSwoLocation').text(woTask['woTaskLocation']);
                if (woTask['woTaskIsPublic'] === 1) {
                    mzFetch('wo_task_public/by_woTaskId/'+woTaskId).then(woTaskPublic => {
                        $('.divSwoPublic').show();
                        $('#pSwoComplainerName').text(woTaskPublic['woTaskPublicName']);
                        $('#pSwoComplainerPhoneNo').text(woTaskPublic['woTaskPublicPhoneNo']);
                        $('#pSwoComplainerEmail').text(woTaskPublic['woTaskPublicEmail']);
                        $('#pSwoComplainerIcNo').text(woTaskPublic['woTaskPublicIcNo']);
                        $('#pSwoComplainerAgency').text(woTaskPublic['woTaskPublicAgency']);
                    }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                } else {
                    const createdBy = refUser[woTask['woTaskCreatedBy']];
                    $('#pSwoComplainerName').text(createdBy['userFirstName']);
                    $('#pSwoComplainerPhoneNo').text(createdBy['userContactNo']);
                    $('#pSwoComplainerEmail').text(createdBy['userEmail']);
                }

                let divImageComplaint = woTask['woTaskStatus'] === 24 || woTask['woTaskStatus'] === 26 ? $('#divSwoImageComplaint') : $('#divSwoImageComplaintLeft');
                if (images[1].length > 0) {
                    divImageComplaint.html('');
                    let cnt = 0;
                    for (const image of images[1]) {
                        const imageSize = image['uploadFileWidth'] !== null ? image['uploadFileWidth']+'x'+image['uploadFileHeight'] : '500x500'
                        const imageDesc = image['woTaskUploadDesc'] !== null ? image['woTaskUploadDesc'] : 'Image' + (++cnt);
                        divImageComplaint.append('<figure class="mx-1">\n' +
                            '   <a href="'+image['url']+'" data-size="'+imageSize+'">\n' +
                            '     <img src="'+image['url']+'" style="height: 200px" class="img-fluid img-thumbnail" alt="thumbnail" width="100%">\n' +
                            '   </a>\n' +
                            '   <p class="mb-0 font-small text-center">'+imageDesc+'</p>\n' +
                            '</figure>\n');
                    }
                } else {
                    divImageComplaint.html('<i>- empty -</i>');
                }

                if (_type === 'assign') {
                    const arrSeverity = mzAjaxRequest('wo.php?type=severity_list_by_site&siteId='+woTask['siteId'], 'GET');
                    mzOptionStop('optSwoaSeverity', arrSeverity, 'Select Severity', 'severityId', 'severityName', {}, 'required');
                    mzOptionStop('optSwoaPpmGroup', refPpmGroup, 'Select Executor Group', 'ppmGroupId', 'ppmGroupName', {ppmGroupStatus: '1', siteId: woTask['siteId'].toString(), roleId:'8'}, 'required');
                    mzOptionStopClear('optSwoaAssignedTo', '', 'required');
                    mzDisableSelect('optSwoaAssignedTo', true);
                    formValidateSwoa.clearValidation();
                    mzSetFieldValue('SwoaType', woTask['woTaskType'], 'select');
                    mzDisableSelect('optSwoaType', woTask['woTaskType'] === 6);
                } else if (_type === 'verify') {
                    formValidateSwov.clearValidation();
                    $('#optSwovRating_').hide();
                    formValidateSwov.disableField('optSwovRating_');
                }

                if (woTask['woTaskStatus'] !== 24) {
                    $('#pSwoGroup').text(mzNullToValue(woTask['ppmGroupId'], '-', 'ppmGroupName', refPpmGroup));
                    $('#pSwoTechnicianName').text(mzNullToValue(woTask['woTaskAssignedTo'], '-', 'userFirstName', refUser));
                    $('#pSwoTechnicianPhoneNo').text(mzNullToValue(woTask['woTaskAssignedTo'], '-', 'userContactNo', refUser));
                    $('#pSwoTechnicianEmail').text(mzNullToValue(woTask['woTaskAssignedTo'], '-', 'userEmail', refUser));
                    if (woTask['woTaskSeverity'] !== null) {
                        mzFetch('client_severity/by_siteSeverity/'+woTask['siteId']+'/'+woTask['woTaskSeverity']).then(taskSeverity => {
                            const slaRespond = taskSeverity['clientSeverityRespondTime'];
                            const slaExecution = taskSeverity['clientSeverityHour'];
                            $('#pSwoSlaRespond').text(slaRespond === 1 ? '1 minute' : slaRespond + ' minutes');
                            $('#pSwoSlaExecution').text(slaExecution === 1 ? '1 hour' : slaExecution + ' hours');
                            $('#pSwoDurationRespond').text(mzNullToValue(mzDurationStr('', woTask['woTaskTimeCreated'], woTask['woTaskTimeAssigned']), '-'));
                            $('#pSwoDurationExecution').text(mzNullToValue(mzDurationStr('', woTask['woTaskTimeAssigned'], woTask['woTaskTimeExecuted']), '-'));
                        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                    } else {
                        $('#pSwoSlaRespond').text('-');
                        $('#pSwoSlaExecution').text('-');
                        $('#pSwoDurationRespond').text('-');
                        $('#pSwoDurationExecution').text('-');
                    }
                    $('#pSwoTimeRespond').text(mzNullToValue(woTask['woTaskTimeAssigned'], '-', moment(woTask['woTaskTimeAssigned']).format('MMMM Do, YYYY, hh:mm:ss')));
                    $('#pSwoTimeExecution').text(mzNullToValue(woTask['woTaskTimeExecuted'], '-', moment(woTask['woTaskTimeExecuted']).format('MMMM Do, YYYY, hh:mm:ss')));
                    $('#pSwoRating').text(mzNullToValue(woTask['woTaskRate'], '-'));
                    $('#pSwoRepair').text(mzNullToValue(woTask['woTaskRepairDesc'], '-'));
                    
                    if (woTask['woTaskStatus'] === 26) {
                        $('.divSwoAssign').hide();
                        $('.divSwoAssessment').show();
                    } else {
                        let divImageRepair = $('#divSwoImageRepair');
                        divImageRepair.html('');
                        if (images[2].length > 0 || images[3].length > 0 || images[4].length > 0) {
                            $('.divSwoImageRepair').show();
                            for (const image of images[2]) {
                                const imageSize = image['uploadFileWidth'] !== null ? image['uploadFileWidth'] + 'x' + image['uploadFileHeight'] : '500x500'
                                const imageDesc = image['woTaskUploadDesc'] !== null ? ' - ' + image['woTaskUploadDesc'] : '';
                                divImageRepair.append('<figure class="mx-1">\n' +
                                    '   <a href="' + image['url'] + '" data-size="' + imageSize + '">\n' +
                                    '     <img src="' + image['url'] + '" style="height: 200px" class="img-fluid img-thumbnail" alt="thumbnail" width="100%">\n' +
                                    '   </a>\n' +
                                    '   <p class="mb-0 font-small text-center">Before' + imageDesc + '</p>\n' +
                                    '</figure>\n');
                            }
                            for (const image of images[3]) {
                                const imageSize = image['uploadFileWidth'] !== null ? image['uploadFileWidth'] + 'x' + image['uploadFileHeight'] : '500x500'
                                const imageDesc = image['woTaskUploadDesc'] !== null ? ' - ' + image['woTaskUploadDesc'] : '';
                                divImageRepair.append('<figure class="mx-1">\n' +
                                    '   <a href="' + image['url'] + '" data-size="' + imageSize + '">\n' +
                                    '     <img src="' + image['url'] + '" style="height: 200px" class="img-fluid img-thumbnail" alt="thumbnail" width="100%">\n' +
                                    '   </a>\n' +
                                    '   <p class="mb-0 font-small text-center">During' + imageDesc + '</p>\n' +
                                    '</figure>\n');
                            }
                            for (const image of images[4]) {
                                const imageSize = image['uploadFileWidth'] !== null ? image['uploadFileWidth'] + 'x' + image['uploadFileHeight'] : '500x500'
                                const imageDesc = image['woTaskUploadDesc'] !== null ? ' - ' + image['woTaskUploadDesc'] : '';
                                divImageRepair.append('<figure class="mx-1">\n' +
                                    '   <a href="' + image['url'] + '" data-size="' + imageSize + '">\n' +
                                    '     <img src="' + image['url'] + '" style="height: 200px" class="img-fluid img-thumbnail" alt="thumbnail" width="100%">\n' +
                                    '   </a>\n' +
                                    '   <p class="mb-0 font-small text-center">After' + imageDesc + '</p>\n' +
                                    '</figure>\n');
                            }
                        } else {
                            divImageRepair.text('- empty -');
                        }
                    }
                    
                    $('#divSwoAsset, #divSwoAssetEmpty').hide();
                    if (woTask['assetId'] !== null) {
                        $('#divSwoAsset').show();
                        const asset = mzAjaxRequest('asset.php?assetId='+woTask['assetId'], 'GET');
                        console.log(asset);
                        $('#pSwoAssetNo').text(asset['assetNo']);
                        $('#pSwoAssetName').text(asset['assetName']);
                    } else {
                        $('#divSwoAssetEmpty').show();
                    }
                    
                    $('#divSwoAssistants, #divSwoAssistantsEmpty').hide();
                    if (assistants.length > 0) {
                        $('#divSwoAssistants').show();
                        oTableAssistants.clear().rows.add(assistants).draw();
                    } else {
                        $('#divSwoAssistantsEmpty').show();
                    }
                    
                    $('#divSwoMaterials, #divSwoMaterialsEmpty').hide();
                    if (materials.length > 0) {
                        $('#divSwoMaterials').show();
                        oTableMaterials.clear().rows.add(materials).draw();
                    } else {
                        $('#divSwoMaterialsEmpty').show();
                    }
                }
                classFrom.hideMain();
                self.showSection();
                window.scrollTo({top: 0, behavior: 'smooth'});
            }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
    };

    this.showSection = function () {
        $('.sectionWo').show();
    };

    this.hideSection = function () {
        $('.sectionWo').hide();
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };
    
    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };
    
    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };
    
    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefSeverity = function (_refSeverity) {
        refSeverity = _refSeverity;
    };

    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };
    
    this.setIsSubmitted = function (_isSubmitted) {
        isSubmitted = _isSubmitted;
    };
    
    this.getIsSubmitted = function () {
        return isSubmitted;
    };
}