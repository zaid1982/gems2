function ModalAttendanceDaily () {

    const className = 'ModalAttendanceDaily';
    let self = this;
    let formValidate;
    let classFrom;
    let attTransactionId;
    let refStatus;
    let refUser;
    let refDesignation;
    let refAssetGroup;
    let refAttGroup;
    let refAttType;

    this.init = function () {

    };

    this.load =  function (_data) {
        try {
            if (_data.length === 0) {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
            console.log(_data);
            attTransactionId = _data['attTransactionId'];
            let pMtdWorkingTimeTotal = $('#pMtdWorkingTimeTotal');
            let pMtdCheckInLateness = $('#pMtdCheckInLateness');
            let pMtdCheckOutEarliness = $('#pMtdCheckOutEarliness');
            let pMtdValidityIn = $('#pMtdValidityIn');
            let pMtdValidityOut = $('#pMtdValidityOut');
            let pMtdStatus = $('#pMtdStatus');
            $('#pMtdName').text(refUser[_data['userId']]['userFirstName']);
            $('#pMtdDesignation').text(refDesignation[_data['designationId']]['designationDesc']);
            $('#pMtdGroup').text(refAttGroup[_data['attGroupId']]['attGroupName']);
            $('#pMtdCategory').text(refAssetGroup[_data['assetGroupId']]['assetGroupName']);
            $('#pMtdShiftMode').text(_data['attParticipantShiftMode']);
            $('#pMtdShiftCurrent').text(refAttType[_data['attTypeId']]['attTypeName']);
            $('#pMtdDate').text(_data['attTransactionDate']);
            $('#pMtdDay').text(_data['dayName']);
            $('#pMtdWorkingTimeRequired').html(mzReplaceNull(_data['durationNeeded'], '-'));
            pMtdWorkingTimeTotal.html(mzReplaceNull(_data['durationWork'], '-')).removeClass('text-success text-danger');
            $('#pMtdCheckInStart').html(mzReplaceNull(_data['attTransactionShiftStart'], '-'));
            $('#pMtdCheckInTime').html(mzReplaceNull(_data['attTransactionTimeIn'], '-'));
            pMtdCheckInLateness.html(_data['durationIn'] !== null ? _data['durationIn']+' '+_data['durationInLate'] : '-').removeClass('text-success text-danger');
            $('#pMtdCheckOutStart').html(mzReplaceNull(_data['attTransactionShiftEnd'], '-'));
            $('#pMtdCheckOutTime').html(mzReplaceNull(_data['attTransactionTimeOut'], '-'));
            pMtdCheckOutEarliness.html(_data['durationOut'] !== null ? _data['durationOut']+' '+_data['durationOutLate'] : '-').removeClass('text-success text-danger');
            pMtdValidityIn.html(mzReplaceNull(_data['locationInsideIn'], '-')).removeClass('text-success text-danger');
            pMtdValidityOut.html(mzReplaceNull(_data['locationInsideOut'], '-')).removeClass('text-success text-danger');
            pMtdStatus.text(_data['result']).removeClass('text-success text-danger');
            if (_data['durationEnoughHour'] === 'Yes') {                pMtdWorkingTimeTotal.addClass('text-success'); }
            if (_data['durationEnoughHour'] === 'No') {                 pMtdWorkingTimeTotal.addClass('text-danger'); }
            if (_data['durationInLate'] === 'Early') {                  pMtdCheckInLateness.addClass('text-success'); }
            if (_data['durationInLate'] === 'Late') {                   pMtdCheckInLateness.addClass('text-danger'); }
            if (_data['durationOutLate'] === 'Late') {                  pMtdCheckOutEarliness.addClass('text-success'); }
            if (_data['durationOutLate'] === 'Early') {                 pMtdCheckOutEarliness.addClass('text-danger'); }
            if (_data['locationInsideIn'] === 'Inside Parameter') {     pMtdValidityIn.addClass('text-success'); }
            if (_data['locationInsideIn'] === 'Outside Parameter') {    pMtdValidityIn.addClass('text-danger'); }
            if (_data['locationInsideOut'] === 'Inside Parameter') {    pMtdValidityOut.addClass('text-success'); }
            if (_data['locationInsideOut'] === 'Outside Parameter') {   pMtdValidityOut.addClass('text-danger'); }
            if (_data['result'] === 'Present') {                        pMtdStatus.addClass('text-success'); }
            if (_data['result'] === 'Absent') {                         pMtdStatus.addClass('text-danger'); }
            $('#modal_attendance_daily').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
        } catch (e) {
            toastr['error'](e.message, _ALERT_TITLE_ERROR);
        }
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

    this.setRefDesignation = function (_refDesignation) {
        refDesignation = _refDesignation;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefAttGroup = function (_refAttGroup) {
        refAttGroup = _refAttGroup;
    };

    this.setRefAttType = function (_refAttType) {
        refAttType = _refAttType;
    };
}