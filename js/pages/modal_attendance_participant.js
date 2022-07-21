function ModalAttendanceParticipant () {

    const className = 'ModalAttendanceParticipant';
    let self = this;
    let formValidate;
    let classFrom;
    let vData;
    let attParticipantId;
    let userId;
    let siteId;
    let attGroupId;
    let refAttGroup;
    let refAttType;
    let refDesignation;
    let refAssetGroup;
    let isEditor;

    this.init = function () {
        isEditor = mzIsRoleExist('1,10');
        mzOptionV2('optMtpDesignation', refDesignation, 'Please Select', 'designationDesc', {designationStatus: 1}, 'required');
        mzOptionV2('optMtpCategory', refAssetGroup, 'Please Select', 'assetGroupName', {assetGroupStatus: 1}, 'required');

        vData = [
            {
                field_id: 'txtMtpGfId',
                type: 'text',
                name: 'GF ID',
                validator: {
                    minLength: 4,
                    maxLength: 30
                }
            },
            {
                field_id: 'optMtpDesignation',
                type: 'select',
                name: 'Designation',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMtpContactNo',
                type: 'text',
                name: 'Contact No.',
                validator: {
                    notEmpty: true,
                    digit: true,
                    minLength: 8,
                    maxLength: 15
                }
            },
            {
                field_id: 'txtMtpEmail',
                type: 'text',
                name: 'Email',
                validator: {
                    notEmpty: true,
                    email: true,
                    maxLength: 100
                }
            },
            {
                field_id: 'txtMtpYearStartService',
                type: 'text',
                name: 'Required Weekly Hours',
                validator: {
                    numeric: true,
                    min: 1990,
                    max: 2025
                }
            },
            {
                field_id: 'txtMtpCidbExpiry',
                type: 'text',
                name: 'CIDB Card Expiry',
                validator: {
                    maxLength: 30
                }
            },
            {
                field_id: 'txtMtpCompetency',
                type: 'text',
                name: 'Required Weekly Hours',
                validator: {
                    maxLength: 200
                }
            },
            {
                field_id: 'optMtpGroup',
                type: 'select',
                name: 'Attendance Group',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMtpCategory',
                type: 'select',
                name: 'Category',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMtpReqWeekHours',
                type: 'text',
                name: 'Required Weekly Hours',
                validator: {
                    notEmpty: true,
                    numeric: true,
                    min: 8,
                    max: 70
                }
            },
            {
                field_id: 'optMtpShiftMode',
                type: 'select',
                name: 'Shift Mode',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMtpShift',
                type: 'select',
                name: 'Current Shift',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMtpHoliday',
                type: 'select',
                name: 'Weekly Holiday',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'radMtpStatus',
                type: 'radio',
                name: 'Status',
                validator: {
                    notEmptyCheck: true
                }
            }
        ];

        formValidate = new MzValidate('formMtp');
        formValidate.registerFields(vData);
        
        $('#optMtpGroup').on('change', function () {
            const newGroup = $(this).val();
            ShowLoader();
            setTimeout(function () {
                try {
                    $('.divMtpGroupSelected').show();
                    if (attGroupId !== newGroup) {
                        attGroupId = parseInt(newGroup);
                        mzOptionStopV2('optMtpShift', refAttType, 'Please Select', 'attTypeName', {attTypeMode: refAttGroup[attGroupId]['attGroupShiftMode'], attTypeStatus: 1}, 'required', false);
                        mzSetFieldValue('MtpCategory', refAttGroup[attGroupId]['assetGroupId'], 'select');
                        mzSetFieldValue('MtpReqWeekHours', refAttGroup[attGroupId]['attGroupReqWeekHours'], 'text');
                        mzSetFieldValue('MtpShiftMode', refAttGroup[attGroupId]['attGroupShiftMode'], 'select');
                        mzSetFieldValue('MtpHoliday', refAttGroup[attGroupId]['attGroupHoliday'], 'select');
                        formValidate.clearInvalid('optMtpCategory');
                        formValidate.clearInvalid('txtMtpReqWeekHours');
                        formValidate.clearInvalid('optMtpShiftMode');
                        formValidate.clearInvalid('optMtpHoliday');
                        formValidate.clearInvalid('radMtpStatus');
                        if (refAttGroup[attGroupId]['attGroupStatus'] === 2) {
                            toastr['warning']('The selected group '+refAttGroup[attGroupId]['attGroupName']+' currently is disable. However you can still register this staff under this group.', _ALERT_TITLE_WARNING);
                        }
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 100);
        });

        $('#optMtpShiftMode').on('change', function () {
            const shiftMode = $(this).val();
            ShowLoader();
            setTimeout(function () {
                try {
                    mzOptionStopV2('optMtpShift', refAttType, 'Please Select', 'attTypeName', {attTypeMode: shiftMode, attTypeStatus: 1}, 'required', false);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 100);
        });

        $('#btnMtpSubmit').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        mzAjaxRequest2('att_participant', 'POST', self.getDataSubmitted());
                        if (classFrom.getClassName() === 'SectionAttendanceConfigSite') {
                            classFrom.genTableGroup();
                            classFrom.genTableParticipant();
                            classFrom.reloadTopStatistic();
                            classFrom.setHasEdit(true);
                        }
                        $('#modal_attendance_participant').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        $('#btnMtpSave').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        mzAjaxRequest2('att_participant/'+attParticipantId, 'PUT', self.getDataSubmitted());
                        if (classFrom.getClassName() === 'SectionAttendanceConfigSite') {
                            classFrom.genTableGroup();
                            classFrom.genTableParticipant();
                            classFrom.reloadTopStatistic();
                            classFrom.setHasEdit(true);
                        }
                        $('#modal_attendance_participant').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
    };

    this.getDataSubmitted = function () {
        try {
            return {
                userId: userId,
                attGroupId: attGroupId,
                attParticipantGfId: $('#txtMtpGfId').val(),
                designationId: $('#optMtpDesignation').val(),
                userContactNo: $('#txtMtpContactNo').val(),
                userEmail: $('#txtMtpEmail').val(),
                attParticipantYearService: $('#txtMtpYearStartService').val(),
                attParticipantCidbCardExpiry: mzConvertDate($('#txtMtpCidbExpiry').val()),
                attParticipantCompetency: $('#txtMtpCompetency').val(),
                assetGroupId: $('#optMtpCategory').val(),
                attParticipantReqWeekHours: $('#txtMtpReqWeekHours').val(),
                attParticipantShiftMode: $('#optMtpShiftMode').val(),
                attTypeId: $('#optMtpShift').val(),
                attParticipantHoliday: $('#optMtpHoliday').val(),
                attParticipantStatus: $("input[name='radMtpStatus']:checked").val()
            };
        } catch (e) {
            throw new Error(_ALERT_MSG_ERROR_DEFAULT);
        }
    };

    this.load = function (_userId, _attParticipantId, _siteId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_userId]);
                if (!isEditor) {
                    throw new Error(_ALERT_MSG_ROLES_NO_ACCESS);
                }                
                userId = _userId;
                attParticipantId = _attParticipantId;
                siteId = _siteId;
                $('#btnMtpSubmit, #btnMtpSave').hide();

                const userProfile = mzAjaxRequest2('user_profile/by_user_id/'+userId, 'GET');
                if (parseInt(userProfile['siteId']) !== siteId) {
                    classFrom.genTableParticipant();
                    throw new Error('This user site has been changed by other user');
                }
                let isGroupExist = false;
                $.each(refAttGroup, function (n, u) {
                    if (typeof u !== 'undefined' && u['siteId'] === siteId) {
                        isGroupExist = true;
                        return false;
                    }
                });
                if (!isGroupExist) {
                    throw new Error('There is no group created yet under this site. Please add Attendance Group before adding the participant.');
                }

                formValidate.clearValidation();
                mzOptionStopV2('optMtpGroup', refAttGroup, 'Choose Attendance Group', 'attGroupName', {siteId: siteId}, 'required');
                mzSetFieldValue('MtpContactNo', userProfile['userContactNo'], 'text');
                mzSetFieldValue('MtpEmail', userProfile['userEmail'], 'text');
                mzSetFieldValue('MtpDesignation', userProfile['designationId'], 'select');
                if (attParticipantId !== null) {
                    const attParticipant = mzAjaxRequest2('att_participant/'+attParticipantId, 'GET');
                    attGroupId = attParticipant['attGroupId'];
                    mzOptionStopV2('optMtpShift', refAttType, 'Please Select', 'attTypeName', {attTypeMode: attParticipant['attParticipantShiftMode'], attTypeStatus: 1}, 'required', false);
                    mzSetFieldValue('MtpGfId', attParticipant['attParticipantGfId'], 'text');
                    mzSetFieldValue('MtpYearStartService', attParticipant['attParticipantYearService'], 'text');
                    mzSetFieldValue('MtpCidbExpiry', attParticipant['attParticipantCidbCardExpiry'], 'date');
                    mzSetFieldValue('MtpCompetency', attParticipant['attParticipantCompetency'], 'text');
                    mzSetFieldValue('MtpGroup', attParticipant['attGroupId'], 'select');
                    mzSetFieldValue('MtpCategory', attParticipant['assetGroupId'], 'select');
                    mzSetFieldValue('MtpReqWeekHours', attParticipant['attParticipantReqWeekHours'], 'text');
                    mzSetFieldValue('MtpShiftMode', attParticipant['attParticipantShiftMode'], 'select');
                    mzSetFieldValue('MtpShift', attParticipant['attTypeId'], 'select');
                    mzSetFieldValue('MtpHoliday', attParticipant['attParticipantHoliday'], 'select');
                    mzSetFieldValue('MtpStatus', attParticipant['attParticipantStatus'], 'radio');
                    $('#lblMtpModalTitle').html('<i class="fas fa-user-edit text-white mr-2"></i> Edit Attendance Employee');
                    $('#btnMtpSave').show();
                    $('.divMtpGroupSelected').show();
                } else {
                    attGroupId = '';
                    $('#lblMtpModalTitle').html('<i class="fas fa-user-plus text-white mr-2"></i> Add Attendance Employee');
                    $('#btnMtpSubmit').show();
                    $('.divMtpGroupSelected').hide();
                }

                $('#modal_attendance_participant').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setParticipantName = function (_participantName) {
        mzSetFieldValue('MtpParticipantName', _participantName, 'text');
    };

    this.setSiteName = function (_siteName) {
        mzSetFieldValue('MtpSiteName', _siteName, 'text');
    };

    this.setRefDesignation = function (_refDesignation) {
        refDesignation = _refDesignation;
    };

    this.setRefAttGroup = function (_refAttGroup) {
        refAttGroup = _refAttGroup;
    };

    this.setRefAttType = function (_refAttType) {
        refAttType = _refAttType;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };
}