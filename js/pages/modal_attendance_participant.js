function ModalAttendanceParticipant () {

    const className = 'ModalAttendanceParticipant';
    let self = this;
    let formValidate;
    let classFrom;
    let vData;
    let attParticipantId;
    let userId;

    this.init = function () {

    };

    this.load = function (_userId, _attParticipantId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_userId]);
                userId = _userId;
                attParticipantId = _attParticipantId;

                const userProfile = mzAjaxRequest2('user_profile/by_user_id/'+userId, 'GET');
                if (attParticipantId !== '') {
                    const attParticipant = mzAjaxRequest2('att_participant/'+attParticipantId, 'GET');
                }

                $('#modal_attendance_participant').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            }catch (e) {
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
}