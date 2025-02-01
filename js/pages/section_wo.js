function SectionWo () {

    const className = 'SectionWo';
    let self = this;
    let classFrom;
    let refStatus;
    let refUser;
    let refSite;
    let refSeverity;
    let refWoType = {
        1: 'Client Complaint',
        2: 'Self Finding',
        3: 'Request',
        4: 'Breakdown',
        5: 'Defect',
        6: 'Public Complaint'
    };
    let woTaskId;

    this.init = function () {
        self.hideSection();
        
        $('#btnSwoBack').on('click', function () {
            self.hideSection();
            classFrom.showMain();
            $(window).scrollTop(0);
        });
    };
    
    this.assign = function (_woTaskId) {
        ShowLoader(); setTimeout(function () { try {
            mzCheckFuncParam([_woTaskId]);
            woTaskId = _woTaskId;
            Promise.all([
                mzFetch('wo_v3/'+woTaskId),
                mzFetch('wo_task_upload/by_woTaskId/'+woTaskId)
            ]).then(responses => {
                const woTask = responses[0];
                const images = responses[1];
                console.log(woTask);
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
                if (woTask['woTaskStatus'] === 24 || woTask['woTaskStatus'] === 26) {
                    $('.divSwoAssign').show();
                    $('.divSwoAssigned').hide();
                    $('.divSwoImageLeft').hide();
                } else {
                    $('.divSwoAssign').hide();
                    $('.divSwoAssigned').show();
                    $('.divSwoImageLeft').show();
                }
                if (woTask['woTaskStatus'] !== 24) {
                    $('#pSwoGroup').text(woTask['ppmGroupId']);
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
                        $('.divSwoAssigned').hide();
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
                }
                classFrom.hideMain();
                self.showSection();
                window.scrollTo({top: 0, behavior: 'smooth'});
            }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }}, 200);
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
}