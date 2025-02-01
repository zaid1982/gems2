function SectionWo () {

    const className = 'SectionWo';
    let self = this;
    let classFrom;
    let refStatus;
    let refUser;
    let refSite;
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
                $('#pSwoSeverity').text(mzNullToValue(woTask['woTaskSeverity'], '-'));
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
                let divImageComplaint = $('#divSwoImageComplaint');
                if (images[1].length > 0) {
                    divImageComplaint.html('');
                    let cnt = 0;
                    for (const image of images[1]) {
                        const imageSize = image['uploadFileWidth'] !== null ? image['uploadFileWidth']+'x'+image['uploadFileHeight'] : '500x500'
                        divImageComplaint.append('<figure class="mx-1">\n' +
                            '   <a href="'+image['url']+'" data-size="'+imageSize+'">\n' +
                            '     <img src="'+image['url']+'" style="height: 200px" class="img-fluid img-thumbnail" alt="thumbnail" width="100%">\n' +
                            '   </a>\n' +
                            '   <p class="mb-0 font-small text-center">Complaint Image '+(++cnt)+'</p>\n' +
                            '</figure>\n');
                    }
                } else {
                    divImageComplaint.html('<i>- empty -</i>');
                }
                if (woTask['woTaskStatus'] === 24) {
                    $('.divSwoAssigned').hide();
                } else {
                    $('.divSwoAssigned').show();
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
}