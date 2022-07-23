function ModalAttendanceDaily () {

    const className = 'ModalAttendanceDaily';
    let self = this;
    let formValidate;
    let classFrom;
    let attTransactionId;
    let dataTransaction;
    let refStatus;
    let refUser;
    let refDesignation;
    let refAssetGroup;
    let refAttGroup;
    let refAttType;
    let googleMapsDrawingPolygonClass;

    this.init = function () {
        const vData = [
            {
                field_id: 'optMtdAttType',
                type: 'select',
                name: 'New Attendance Status',
                validator: {
                }
            },
            {
                field_id: 'chkMtdStatus',
                type: 'checkSingle',
                name: 'Attendance Status',
                validator: {
                }
            }
        ];

        formValidate = new MzValidate('formMtd');
        formValidate.registerFields(vData);

        $('#optMtdAttType').on('change', function () {
            let attTypeId = $(this).val();
            if (attTypeId === '') {
                attTypeId = dataTransaction['attTypeId'];
            }
            const attTypeMode = refAttType[parseInt(attTypeId)]['attTypeMode'];
            if (attTypeMode === 'Leave' || attTypeMode === 'Training' || !moment().isAfter(dataTransaction['attTransactionDate'])) {
                $('#divMtdStatus').hide();
            } else {
                $('#divMtdStatus').show();
                $('#lblMtdStatus').text('Set to ' + (dataTransaction['result'] === 'Present' ? 'Absent' : 'Present'));
            }
            $('#chkMtdStatus').prop('checked',false);
        });

        $('#btnMtdSubmit').on('click', function () {
            let attTypeId = $('#optMtdAttType').val();
            const statusChecked = $("input[name='chkMtdStatus']").is(":checked");
            if (!formValidate.validateNow() || (attTypeId === '' && statusChecked === false)) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const statusText = $('#lblMtdStatus').text();
                        let data = {};
                        if (attTypeId === '') {
                            if (statusText === 'Set to Present') {
                                data['attTransactionResult'] = 'Present';
                            } else if (statusText === 'Set to Absent') {
                                data['attTransactionResult'] = 'Absent';
                            }
                        } else {
                            data['attTypeId'] = parseInt(attTypeId);
                            const attTypeMode = refAttType[parseInt(attTypeId)]['attTypeMode'];
                            if (attTypeMode === 'Leave' || attTypeMode === 'Training') {
                                data['attTransactionResult'] = attTypeMode;
                            } else if (statusChecked === true && statusText === 'Set to Present') {
                                data['attTransactionResult'] = 'Present';
                            } else if (statusChecked === true && statusText === 'Set to Absent') {
                                data['attTransactionResult'] = 'Absent';
                            } else if (data['attTransactionStatus'] === 'Checked In' || data['attTransactionStatus'] === 'Checked Out') {
                                data['attTransactionResult'] = 'Present';
                            } else {
                                data['attTransactionResult'] = '';
                            }
                        }
                        mzAjaxRequest2('att_transaction/'+attTransactionId, 'PUT', data);
                        if (classFrom.getClassName() === 'SectionAttendancePlanner') {
                            classFrom.genTable();
                        }
                        $('#modal_attendance_daily').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
    };

    this.load =  function (_data) {
        try {
            if (_data.length === 0) {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
            formValidate.clearValidation();

            attTransactionId = _data['attTransactionId'];
            const attGroup = refAttGroup[_data['attGroupId']];
            const attTypeId = _data['attTypeId'];
            let pMtdWorkingTimeTotal = $('#pMtdWorkingTimeTotal');
            let pMtdCheckInLateness = $('#pMtdCheckInLateness');
            let pMtdCheckOutEarliness = $('#pMtdCheckOutEarliness');
            let pMtdValidityIn = $('#pMtdValidityIn');
            let pMtdValidityOut = $('#pMtdValidityOut');
            let pMtdStatus = $('#pMtdStatus');

            $('#pMtdName').text(refUser[_data['userId']]['userFirstName']);
            $('#pMtdDesignation').text(refDesignation[_data['designationId']]['designationDesc']);
            $('#pMtdGroup').text(attGroup['attGroupName']);
            $('#pMtdCategory').text(refAssetGroup[_data['assetGroupId']]['assetGroupName']);
            $('#pMtdShiftMode').text(_data['attParticipantShiftMode']);
            $('#pMtdShiftCurrent').text(refAttType[attTypeId]['attTypeName']);
            $('#pMtdDate').text(_data['attTransactionDate']+' ('+_data['dayName']+')');
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
            pMtdStatus.text(mzReplaceNull(_data['result'], '-')).removeClass('text-success text-danger');

            const polygon = JSON.parse(attGroup['attGroupPolygon']);
            googleMapsDrawingPolygonClass.setDrawingManager('mapMtdLocation', attGroup['attGroupMapCenterLat'], attGroup['attGroupMapCenterLng'], attGroup['attGroupMapZoom']-1);
            googleMapsDrawingPolygonClass.drawPolygon('mapMtdLocation', polygon['coordinates'][0]);
            googleMapsDrawingPolygonClass.setDrawingControl(false);
            googleMapsDrawingPolygonClass.addMarker(_data['locationInX'], _data['locationInY'], 'Check In Location', _data['attTransactionTimeIn'], 'In');
            googleMapsDrawingPolygonClass.addMarker(_data['locationOutX'], _data['locationOutY'], 'Check In Location', _data['attTransactionTimeOut'], 'Out');

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

            let divMtdUpdate = $('.divMtdUpdate');
            divMtdUpdate.hide();
            if (mzIsRoleExist('1,19,20')) {
                let refShift = [];
                $.each(refAttType, function (i, attType) {
                    if (parseInt(i) !== attTypeId) {
                        if (attType['attTypeMode'] === _data['attParticipantShiftMode'] || attType['attTypeMode'] === 'Leave' || attType['attTypeMode'] === 'Training') {
                            refShift[i] = attType;
                        }
                    }
                });
                mzOptionStopV2('optMtdAttType', refShift, 'Please Select', 'attTypeName', {attTypeStatus: 1}, '', false);
                divMtdUpdate.show();
                if (refAttType[attTypeId]['attTypeMode'] === 'Leave' || refAttType[attTypeId]['attTypeMode'] === 'Training' || !moment().isAfter(_data['attTransactionDate'])) {
                    $('#divMtdStatus').hide();
                } else {
                    $('#lblMtdStatus').text('Set to ' + (_data['result'] === 'Present' ? 'Absent' : 'Present'));
                }
            }

            dataTransaction = _data;
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

    this.setGoogleMapsDrawingPolygon = function (_googleMapsDrawingPolygonClass) {
        googleMapsDrawingPolygonClass = _googleMapsDrawingPolygonClass;
    };
}