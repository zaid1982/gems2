function ModalAttendanceGroup () {
	
	const className = 'ModalAttendanceGroup';
    let self = this;
    let formValidate;
    let attGroupId;
    let siteId;
    let vData;
    let classFrom;
    let refUser;
    let googleMapsDrawingPolygonClass;
    let isAdmin;
    let isSupervisor;
	
	this.init = function () {
        isAdmin = mzIsRoleExist('1');
        isSupervisor = mzIsRoleExist('20');

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
            },
            {
                field_id: 'optMtgOtApprover',
                type: 'select',
                name: 'OT Approver',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'radMtgGroupStatus',
                type: 'radio',
                name: 'Status',
                validator: {
                    notEmptyCheck: true
                }
            }
        ];

        formValidate = new MzValidate('formMtg');
        formValidate.registerFields(vData);

        $('#btnMtgSubmit').on('click', function () {
            const coordinates = googleMapsDrawingPolygonClass.getSelectedCoordinate();
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else if (coordinates.length === 0) {
                toastr['error']('Please make sure site/office Perimeter for attendance verification selected from the Map.', _ALERT_TITLE_ERROR);
            } else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        mzAjaxRequest2('att_group', 'POST', self.getDataSubmitted());
                        if (classFrom.getClassName() === 'SectionAttendanceConfigSite') {
                            classFrom.genTableGroup();
                            classFrom.setHasEdit(true);
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
                        mzAjaxRequest2('att_group/'+attGroupId, 'PUT', self.getDataSubmitted());
                        if (classFrom.getClassName() === 'SectionAttendanceConfigSite') {
                            classFrom.reloadTopStatistic();
                            classFrom.genTableGroup();
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

    this.getDataSubmitted = function () {
        try {
            const data = {
                siteId: siteId,
                attGroupName: $('#txtMtgGroupName').val(),
                attGroupSupervisor: $('#optMtgSupervisor').val(),
                attGroupCategory: $('#optMtgGroupCategory').val(),
                attGroupHoliday: $('#optMtgGroupHoliday').val(),
                attGroupReqWeekHours: $('#txtMtgGroupReqWeekHours').val(),
                attGroupShiftMode: $('#optMtgGroupShiftMode').val(),
                attGroupDayShiftStart: $('#optMtgGroupDayShiftStart').val(),
                attGroupDayShiftEnd: $('#optMtgGroupDayShiftEnd').val(),
                attGroupNightShiftStart: $('#optMtgGroupNightShiftStart').val(),
                attGroupNightShiftEnd: $('#optMtgGroupNightShiftEnd').val(),
                attGroupOtApprover: $('#optMtgOtApprover').val(),
                attGroupRemark: $('#txaMtgGroupRemark').val(),
                attGroupStatus: $("input[name='radMtgGroupStatus']:checked").val()
            };
            const maps = {
                coordinates: googleMapsDrawingPolygonClass.getSelectedCoordinate(),
                mapCenter: googleMapsDrawingPolygonClass.getMapCenter(),
                zoomLevel: googleMapsDrawingPolygonClass.getZoomLevel()
            };
            return {data: data, maps: maps};
        } catch (e) {
            throw new Error(_ALERT_MSG_ERROR_DEFAULT);
        }
    };
	
	this.add = function (_siteId) {
		ShowLoader();
        setTimeout(function () {
            try {
                if (!isAdmin) {
                    throw new Error('Your Role is disallowed to add Attendance Group');
                }

                mzCheckFuncParam([_siteId]);
                siteId = _siteId;
                formValidate.clearValidation();
                formValidate.enableField('txtMtgGroupName');
                formValidate.enableField('optMtgSupervisor');
                $('.divMtgAdminView').show();
                $('.divMtgAdminHide').hide();

                $('#lblMtgModalTitle').html('<i class="fas fa-plus text-white mr-2"></i> Add Attendance Group');
                mzOptionStop('optMtgSupervisor', refUser, 'Choose Supervisor', 'userId', 'userFullName', {userType: '1', userStatus: '1', siteId: siteId, roles: '#20'}, 'required');
                mzOptionStop('optMtgOtApprover', refUser, 'Choose OT Approver', 'userId', 'userFullName', {userType: '1', userStatus: '1', siteId: siteId, roles: '#21'}, 'required');
                googleMapsDrawingPolygonClass.setDrawingManager('mapMtgGroup');
                googleMapsDrawingPolygonClass.drawPolygon('mapMtgGroup');
                $('#btnMtgSave').hide();
                $('#btnMtgSubmit').show();

                $('#modal_attendance_group').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
	};

    this.edit = function (_attGroupId, _siteId) {
		ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_attGroupId, _siteId]);
                attGroupId = _attGroupId;
                siteId = _siteId;
                formValidate.clearValidation();

                if (isAdmin) {
                    formValidate.enableField('txtMtgGroupName');
                    formValidate.enableField('optMtgSupervisor');
                    $('.divMtgAdminView').show();
                    $('.divMtgAdminHide').hide();
                } else if (isSupervisor) {
                    formValidate.disableField('txtMtgGroupName');
                    formValidate.disableField('optMtgSupervisor');
                    $('.divMtgAdminView').hide();
                    $('.divMtgAdminHide').show();
                } else {
                    throw new Error('Your Role is disallowed to configure this Attendance Group');
                }

                mzOptionStop('optMtgSupervisor', refUser, 'Choose Supervisor', 'userId', 'userFullName', {userType: '1', userStatus: '1', siteId: siteId, roles: '#20'}, 'required');
                mzOptionStop('optMtgOtApprover', refUser, 'Choose OT Approver', 'userId', 'userFullName', {userType: '1', userStatus: '1', siteId: siteId, roles: '#21'}, 'required');
                const attGroup = mzAjaxRequest2('att_group/'+attGroupId, 'GET');
                const polygon = JSON.parse(attGroup['attGroupPolygon']);
                mzSetFieldValue('MtgGroupName', attGroup['attGroupName'], 'text');
                mzSetFieldValue('MtgGroupNameLbl', attGroup['attGroupName'], 'text');
                mzSetFieldValue('MtgSupervisor', attGroup['attGroupSupervisor'], 'select');
                mzSetFieldValue('MtgSupervisorName', refUser[attGroup['attGroupSupervisor']]['userFullName'], 'text');
                mzSetFieldValue('MtgGroupCategory', attGroup['attGroupCategory'], 'select');
                mzSetFieldValue('MtgGroupHoliday', attGroup['attGroupHoliday'], 'select');
                mzSetFieldValue('MtgGroupReqWeekHours', attGroup['attGroupReqWeekHours'], 'text');
                mzSetFieldValue('MtgGroupShiftMode', attGroup['attGroupShiftMode'], 'select');
                mzSetFieldValue('MtgGroupDayShiftStart', attGroup['attGroupDayShiftStart2'], 'select');
                mzSetFieldValue('MtgGroupDayShiftEnd', attGroup['attGroupDayShiftEnd2'], 'select');
                mzSetFieldValue('MtgGroupNightShiftStart', attGroup['attGroupNightShiftStart2'], 'select');
                mzSetFieldValue('MtgGroupNightShiftEnd', attGroup['attGroupNightShiftEnd2'], 'select');
                mzSetFieldValue('MtgOtApprover', attGroup['attGroupOtApprover'], 'select');
                mzSetFieldValue('MtgGroupRemark', attGroup['attGroupRemark'], 'textarea');
                mzSetFieldValue('MtgGroupStatus', attGroup['attGroupStatus'], 'radio');
                googleMapsDrawingPolygonClass.setDrawingManager('mapMtgGroup', attGroup['attGroupMapCenterLat'], attGroup['attGroupMapCenterLng'], attGroup['attGroupMapZoom']);
                googleMapsDrawingPolygonClass.drawPolygon('mapMtgGroup', polygon['coordinates'][0]);
                $('#btnMtgSave').show();
                $('#btnMtgSubmit').hide();

                $('#lblMtgModalTitle').html('<i class="fas fa-edit text-white mr-2"></i> Edit Attendance Group');
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