function ModalUser() {

    const className = 'ModalUser';
    let self = this;
    let rowRefresh = '';
    let classFrom;
    let userId = '';
    let refDesignation;
    let refClient;
    let refSite;
    // New: hold reference role map
    let refRole;
    let formValidate;

    // Helper: map role description to roleId from refRole
    function getRoleIdByDesc(desc) {
        if (!refRole) return '';
        // refRole is expected as array or object keyed by roleId
        if (Array.isArray(refRole)) {
            for (const r of refRole) {
                if (!r) continue; // skip holes/undefined
                const d = (r.roleDesc || '').toLowerCase();
                if (d === desc.toLowerCase()) return String(r.roleId);
            }
        } else if (typeof refRole === 'object') {
            for (const k in refRole) {
                if (!Object.prototype.hasOwnProperty.call(refRole, k)) continue;
                const r = refRole[k];
                if (!r) continue;
                const d = (r.roleDesc || '').toLowerCase();
                if (d === desc.toLowerCase()) return String(r.roleId);
            }
        }
        return '';
    }

    // Helper: set PTW checkbox values from refRole
    function applyPtwRoleIds() {
        const rSup = getRoleIdByDesc('PTW Supervisor');
        const rShe = getRoleIdByDesc('PTW SHE');
        const rFm  = getRoleIdByDesc('PTW Facility Manager');
        if (rSup) $('#chkMusRolePTWSUP').val(rSup).attr('data-role-id', rSup); else $('#divMusRolePTWSUP').hide();
        if (rShe) $('#chkMusRolePTWSHE').val(rShe).attr('data-role-id', rShe); else $('#divMusRolePTWSHE').hide();
        if (rFm)  $('#chkMusRolePTWFM').val(rFm).attr('data-role-id', rFm); else $('#divMusRolePTWFM').hide();
    }

    this.init = function () {
        const vDataMus = [
            {
                field_id: 'txtMusUserFirstName',
                type: 'text',
                name: 'Name',
                validator: {
                    notEmpty: true,
                    maxLength: 200
                }
            },
            {
                field_id: 'txtMusUserName',
                type: 'text',
                name: 'Login ID',
                validator: {
                    notEmpty: true,
                    maxLength: 30
                }
            },
            {
                field_id: 'txtMusUserPassword',
                type: 'text',
                name: 'Password',
                validator: {
                    notEmpty: true,
                    minLength: 6,
                    maxLength: 30
                }
            },
            {
                field_id: 'optMusDesignationId',
                type: 'select',
                name: 'Designation',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMusUserContactNo',
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
                field_id: 'txtMusUserEmail',
                type: 'text',
                name: 'Email',
                validator: {
                    notEmpty: true,
                    email: true,
                    maxLength: 100
                }
            },
            {
                field_id: 'chkMusUserType',
                type: 'radio',
                name: 'User Type',
                validator: {
                    notEmptyCheck: true
                }
            },
            {
                field_id: 'optMusClientId',
                type: 'select',
                name: 'Client',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMusSiteId',
                type: 'select',
                name: 'Site',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'chkMusRole[]',
                type: 'check',
                name: 'Roles',
                validator: {
                    notEmptyCheck: true
                }
            }
        ];

        formValidate = new MzValidate('formMus');
        formValidate.registerFields(vDataMus);

        $('#formMus').on('keyup change', function () {
            $('#btnMusSubmit').attr('disabled', !formValidate.validateForm());
        });

        self.defaultPageSetup();

        // map PTW role IDs once modal HTML is ready
        applyPtwRoleIds();

        $('#modal_user').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMusSubmit').attr('disabled', true);
            self.defaultPageSetup();
        });

        $("input[name='chkMusUserType']:radio").on('click', function () {
            $("input[name='chkMusRole[]']:checkbox").prop('checked',false);
            formValidate.validateForm();
            if ($(this).val() === '1') {
                $('.divMusRoles').show();
                $('#divMusRole1, #divMusRole19, #divMusRole2, #divMusRole3, #divMusRole5, #divMusRole7, #divMusRole8, #divMusRole9, #divMusRole10, #divMusRole11, #divMusRole12, #divMusRole13, #divMusRole14, #divMusRole16, #divMusRole17, #divMusRole18, #divMusRole4, #divMusRole20, #divMusRole21, #divMusRolePTWSUP, #divMusRolePTWSHE, #divMusRolePTWFM').show();
                $('#divMusRole6').hide();
            } else if ($(this).val() === '2') {
                $('.divMusRoles').show();
                $('#divMusRole1, #divMusRole19, #divMusRole2, #divMusRole3, #divMusRole5, #divMusRole7, #divMusRole8, #divMusRole9, #divMusRole10, #divMusRole11, #divMusRole12, #divMusRole13, #divMusRole14, #divMusRole16, #divMusRole17, #divMusRole18, #divMusRole20, #divMusRole21, #divMusRolePTWSUP, #divMusRolePTWSHE, #divMusRolePTWFM').hide();
                $('#divMusRole6, #divMusRole4').show();
                $('#divMusReportGap, #divMusExecutor, #divMusReviewer').hide();
            }
        });

        $('#optMusClientId').on('change', function () {
            mzOptionStop('optMusSiteId', refSite, 'Choose Site', 'siteId', 'siteName', {clientId: $(this).val(), siteStatus: '1'}, 'required');
        });

        $('#btnMusSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        let rolesStr = '';
                        let isClient = false;
                        let isInternalComplainer = false;
                        let isWoAssigner = false;
                        let isWoHelpdesk = false;
                        $("input[name='chkMusRole[]']:checked").map(function(){
                            const val = ($(this).val() || '').trim();
                            if (val !== '') {
                                rolesStr += ','+val;
                            }
                            if ($(this).val() === '6') {
                                isClient = true;
                            } else if ($(this).val() === '9') {
                                isInternalComplainer = true;
                            } else if ($(this).val() === '7') {
                                isWoAssigner = true;
                            } else if ($(this).val() === '11') {
                                isWoHelpdesk = true;
                            }
                        });
                        rolesStr = rolesStr.length > 0 ? rolesStr.slice(1) : rolesStr;
                        const userType = $("input[name='chkMusUserType']:checked").val();

                        if (userType === '1' && isInternalComplainer === false) {
                            toastr['error']('User must have WO Complainer roles. Please check on WO Complainer roles.', _ALERT_TITLE_ERROR);
                        } else if (userType === '2' && isClient === false) {
                            toastr['error']('Client type must have Client/Complainer roles. Please check on Client/Complainer roles.', _ALERT_TITLE_ERROR);
                        }
                        if (isWoHelpdesk === true && isWoAssigner === false) {
                            toastr['error']('WO Helpdesk must have WO Assigner roles. Please check on WO Assigner roles.', _ALERT_TITLE_ERROR);
                        }
                        else {
                            const data = {
                                userName: $('#txtMusUserName').val(),
                                userPassword: $('#txtMusUserPassword').val(),
                                userFirstName: $('#txtMusUserFirstName').val(),
                                userContactNo: $('#txtMusUserContactNo').val(),
                                userEmail: $('#txtMusUserEmail').val(),
                                designationId: $('#optMusDesignationId').val(),
                                userType: userType,
                                siteId: $('#optMusSiteId').val(),
                                roles: rolesStr
                            };

                            if (userId === '') {
                                data['action'] = 'add_user';
                                mzAjaxRequest('profile.php?', 'POST', data);
                                if (classFrom.getClassName() === 'MainUserManagement') {
                                    classFrom.genTableUser();
                                }
                            } else {
                                data['action'] = 'update_user';
                                mzAjaxRequest('profile.php?userId='+userId, 'PUT', data);
                                if (classFrom.getClassName() === 'MainUserManagement') {
                                    classFrom.genTableUser();
                                }
                            }

                            $('#modal_user').modal('hide');
                        }
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.defaultPageSetup = function () {
        $('.divMusAddOnly, .divMusRoles, #divMusReportGap, #divMusExecutor, #divMusReviewer').hide();
        $('#chkMusUserType1, #chkMusUserType2').prop('disabled', false);
        // Ensure PTW roles are hidden until user type selection
        $('#divMusRolePTWSUP, #divMusRolePTWSHE, #divMusRolePTWFM').hide();
        // Clear PTW checkbox values until refRole is set
        $('#chkMusRolePTWSUP, #chkMusRolePTWSHE, #chkMusRolePTWFM').val('');
    };

    this.add = function () {
        userId = '';
        rowRefresh = '';

        ShowLoader();
        setTimeout(function () {
            try {
                mzOptionStop('optMusDesignationId', refDesignation, 'Choose Designation', 'designationId', 'designationDesc', {designationStatus: '1'}, 'required');
                mzOptionStop('optMusClientId', refClient, 'Choose Client', 'clientId', 'clientName', {clientStatus: '1'}, 'required');
                mzOptionStopClear('optMusSiteId','Choose Site', 'required');

                formValidate.enableField('txtMusUserName');
                formValidate.enableField('txtMusUserPassword');

                // Re-apply PTW role ids (in case refRole loaded later)
                applyPtwRoleIds();

                $('.divMusAddOnly').show();
                $('#lblMusTitle').html('<i class="fas fa-user-plus text-white"></i> &nbsp;Register New User');
                $('#txtMusUserName').prop('disabled', false);
                $('#modal_user').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.edit = function (_userId, _rowRefresh) {
        userId = _userId;
        rowRefresh = _rowRefresh;

        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_userId, _rowRefresh]);
                $('#chkMusUserType1, #chkMusUserType2').prop('disabled', true);
                mzOptionStop('optMusDesignationId', refDesignation, 'Choose Designation *', 'designationId', 'designationDesc', {designationStatus: '1'}, 'required');
                mzOptionStop('optMusClientId', refClient, 'Choose Client', 'clientId', 'clientName', {clientStatus: '1'}, 'required');
                mzOptionStopClear('optMusSiteId','Choose Site', 'required');

                // Ensure PTW role ids are set before setting checked values
                applyPtwRoleIds();

                const dataUser = mzAjaxRequest('profile.php?userId='+userId, 'GET');
                const roles = dataUser['roles'];
                const userType = dataUser['userType'];
                formValidate.disableField('txtMusUserName');
                formValidate.disableField('txtMusUserPassword');
                mzSetFieldValue('MusUserName', dataUser['userName'], 'text');
                mzSetFieldValue('MusUserType', userType, 'check');
                mzSetFieldValue('MusUserFirstName', dataUser['userFirstName'], 'text');
                mzSetFieldValue('MusUserContactNo', dataUser['userContactNo'], 'text');
                mzSetFieldValue('MusUserEmail', dataUser['userEmail'], 'text');
                mzSetFieldValue('MusDesignationId', dataUser['designationId'], 'select', 'Designation *');
                mzSetFieldValue('MusRole', roles.split(','), 'check');

                mzOptionStop('optMusSiteId', refSite, 'Choose Site', 'siteId', 'siteName', {clientId: dataUser['clientId'], siteStatus: '1'}, 'required');
                mzSetFieldValue('MusClientId', dataUser['clientId'], 'select', 'Client *');
                mzSetFieldValue('MusSiteId', dataUser['siteId'], 'select', 'Site *');

                if (userType === '1') {
                    $('.divMusRoles').show();
                    $('#divMusRole1, #divMusRole19, #divMusRole2, #divMusRole3, #divMusRole5, #divMusRole7, #divMusRole8, #divMusRole9, #divMusRole10, #divMusRole11, #divMusRole12, #divMusRole13, #divMusRole14, #divMusRole16, #divMusRole17, #divMusRole18, #divMusRole4, #divMusRole20, #divMusRole21, #divMusRolePTWSUP, #divMusRolePTWSHE, #divMusRolePTWFM').show();
                    $('#divMusRole6').hide();
                }
                else if (userType === '2') {
                    $('.divMusRoles').show();
                    $('#divMusRole1, #divMusRole19, #divMusRole2, #divMusRole3, #divMusRole5, #divMusRole7, #divMusRole8, #divMusRole9, #divMusRole10, #divMusRole11, #divMusRole12, #divMusRole13, #divMusRole14, #divMusRole16, #divMusRole17, #divMusRole18, #divMusRole11, #divMusRole20, #divMusRole21, #divMusRolePTWSUP, #divMusRolePTWSHE, #divMusRolePTWFM').hide();
                    $('#divMusRole6, #divMusRole4').show();
                }
                formValidate.validateForm();

                $('#lblMusTitle').html('<i class="fas fa-user-edit text-white"></i> &nbsp;Edit User Profile');
                $('#txtMusUserName').prop('disabled', true);
                $('#modal_user').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_userId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_userId, _rowRefresh]);
                mzAjaxRequest('profile.php?userId='+_userId, 'PUT', {action: 'deactivate'});
                const tempRow = {userStatus:'2'};
                if (classFrom.getClassName() === 'MainUserManagement') {
                    classFrom.updateTableUmn(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_userId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_userId, _rowRefresh]);
                mzAjaxRequest('profile.php?userId='+_userId, 'PUT', {action: 'activate'});
                const tempRow = {userStatus:'1'};
                if (classFrom.getClassName() === 'MainUserManagement') {
                    classFrom.updateTableUmn(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setRefDesignation = function (_refDesignation) {
        refDesignation = _refDesignation;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    // New: accept refRole from page
    this.setRefRole = function (_refRole) {
        refRole = _refRole;
        applyPtwRoleIds();
    };
}