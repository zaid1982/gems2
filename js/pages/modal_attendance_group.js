function ModalAttendanceGroup () {
	
	const className = 'ModalAttendanceGroup';
    let self = this;
    let formValidate;
    let drawingId;
    let siteId;
    let vData;
    let classFrom;
    let refUser;
    let googleMapsDrawingPolygonClass;
	
	this.init = function () {
        vData = [
            {
                field_id: 'txtMtgGroupName',
                type: 'text',
                name: 'Group Name',
                validator: {
                    notEmpty: true,
                    maxLength: 200
                }
            },
            {
                field_id: 'optMtgSupervisor',
                type: 'select',
                name: 'Supervisor',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMtgGroupCategory',
                type: 'select',
                name: 'Category',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMtgGroupHoliday',
                type: 'select',
                name: 'Default Weekly Holiday',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMtgGroupReqWeekHours',
                type: 'text',
                name: 'Default Required Weekly Hours',
                validator: {
                    notEmpty: true,
                    numeric: true,
                    min: 8,
                    max: 70
                }
            },
            {
                field_id: 'optMtgGroupShiftMode',
                type: 'select',
                name: 'Default Shift Mode',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMtgGroupDayShiftStart',
                type: 'select',
                name: 'Day Shift Start',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMtgGroupDayShiftEnd',
                type: 'select',
                name: 'Day Shift End',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMtgGroupNightShiftStart',
                type: 'select',
                name: 'Night Shift Start',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMtgGroupNightShiftEnd',
                type: 'select',
                name: 'Night Shift End',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txaMtgGroupRemark',
                type: 'text',
                name: 'Remarks',
                validator: {
                    maxLength: 5000
                }
            }
        ];

        formValidate = new MzValidate('formMtg');
        formValidate.registerFields(vData);

        $('#btnMtgSubmit').on('click', function () {
            console.log(googleMapsDrawingPolygonClass.getSelectedCoordinate());
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }
            else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        let data = {
                            attGroupName: $('#txtMtgGroupName').val(),
                            attGroupSupervisor: $('#optMtgSupervisor').val(),
                            attGroupCategory: $('#optMtgGroupCategory').val(),
                            attGroupHoliday: $('#optMtgGroupHoliday').val(),
                            attGroupReqWeekHours: $('#txtMtgGroupReqWeekHours').val(),
                            attGroupShiftMode: $('#optMtgGroupShiftMode').val(),
                            attGroupDayShiftStart: $('#optMtgGroupDayShiftStart').val(),
                            attGroupDayShiftEnd: $('#optMtgGroupDayShiftEnd').val(),
                            attGroupDNightShiftStart: $('#optMtgGroupNightShiftStart').val(),
                            attGroupDNightShiftEnd: $('#optMtgGroupNightShiftEnd').val(),
                            attGroupRemark: $('#txaMtgGroupRemark').val()
                        };
                        //mzAjaxRequest2('att_group', 'POST', data);
                        if (classFrom.getClassName() === 'SectionAttendanceConfigSite') {
                            //classFrom.genTable();
                        }
                        $('#modal_attendance_group').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        $('#btnMtgSave').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }
            else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        if (classFrom.getClassName() === 'SectionAttendanceConfigSite') {
                            classFrom.genTable();
                        }
                        $('#modal_attendance_group').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        $('#btnMtgMapRedraw').on('click', function () {
            googleMapsDrawingPolygonClass.deleteSelectedShape();
        });
	};
	
	this.add = function (_siteId) {
		ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteId]);
                siteId = _siteId;
                formValidate.clearValidation();

                $('#lblMtgModalTitle').html('<i class="fas fa-plus text-white mr-2"></i> Add Attendance Group');
                mzOptionStop('optMtgSupervisor', refUser, 'Choose Supervisor', 'userId', 'userFullName', {userType: '1', userStatus: '1', siteId: siteId}, 'required');
                $('#btnMtgSave').hide();
                $('#btnMtgSubmit').show();
                googleMapsDrawingPolygonClass.setDrawingManager('mapMtgGroup');

                $('#modal_attendance_group').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
	};

    this.edit = function (_drawingId) {
		ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_drawingId]);
                drawingId = _drawingId;
                formValidate.clearValidation();                

                const drawing = mzAjaxRequest2('drawing/'+drawingId, 'GET');
                mzSetFieldValue('MtgTitle', drawing['drawingTitle'], 'text');
                mzSetFieldValue('MtgIdNo', drawing['drawingIdNo'], 'text');
                mzSetFieldValue('MtgVersion', drawing['drawingVersion'], 'text');
                mzSetFieldValue('MtgPublishedBy', drawing['drawingPublishedBy'], 'text');
                mzSetFieldValue('MtgPublishedDate', drawing['drawingPublishedDate'], 'date');
                mzSetFieldValue('MtgBlock', drawing['drawingBlock'], 'text');
                mzSetFieldValue('MtgLevel', drawing['drawingLevel'], 'text');
                mzSetFieldValue('MtgGroup', drawing['assetGroupId'], 'select');
                mzSetFieldValue('MtgPermission', drawing['drawingPermissionLevel'], 'select');
                mzSetFieldValue('MtgRemark', drawing['drawingRemark'], 'textarea');
                
                $('#lblMtgModalTitle').html('<i class="fas fa-edit text-white mr-2"></i> Edit Drawing File');
                $('#lblMtgFile').text('Replace Drawing DWG File');
                $('#lblMtgPdfFile').text('Replace Drawing PDF File');
                $('#btnMtgSubmit').hide();
                $('#btnMtgSave').show();

                $('#modal_attendance_group').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
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

    this.setSiteName = function (_siteName) {
        mzSetFieldValue('MtgSiteName', _siteName, 'text');
    };

    this.setSiteCode = function (_siteCode) {
        mzSetFieldValue('MtgSiteCode', _siteCode, 'text');
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setGoogleMapsDrawingPolygon = function (_googleMapsDrawingPolygonClass) {
        googleMapsDrawingPolygonClass = _googleMapsDrawingPolygonClass;
    };
}