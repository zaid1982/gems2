function ModalUser() {

    const className = 'ModalUser';
    let self = this;
    let rowRefresh = '';
    let classFrom;
    let userId = '';
    let refDesignation;
    let refClient;
    let refSite;
    let refRole;
    let formValidate;

    function rowsFromRef(ref, idKey, labelKey, predicate, selectedId) {
        const rows = [];
        let hasSelected = false;
        $.each(ref || {}, function (key, item) {
            if (!item || typeof item !== 'object') {
                return true;
            }
            const row = $.extend({}, item);
            if (row[idKey] === undefined || row[idKey] === null || row[idKey] === '') {
                row[idKey] = key;
            }
            if (!row[labelKey] && (row[idKey] === undefined || row[idKey] === '')) {
                return true;
            }
            const isSelected = selectedId !== undefined && selectedId !== null && selectedId !== ''
                && String(row[idKey]) === String(selectedId);
            if (predicate && !predicate(row) && !isSelected) {
                return true;
            }
            if (isSelected) {
                hasSelected = true;
            }
            rows.push(row);
            return true;
        });
        if (selectedId !== undefined && selectedId !== null && selectedId !== '' && !hasSelected && ref && ref[selectedId]) {
            const extra = $.extend({}, ref[selectedId]);
            if (extra[idKey] === undefined || extra[idKey] === null || extra[idKey] === '') {
                extra[idKey] = selectedId;
            }
            rows.push(extra);
        }
        rows.sort(function (a, b) {
            return String(a[labelKey] || '').localeCompare(String(b[labelKey] || ''));
        });
        return rows;
    }

    function fillDesignationSelect(selected) {
        GemsUI.fillSelect(
            'optMusDesignationId',
            rowsFromRef(refDesignation, 'designationId', 'designationDesc', function (row) {
                return String(row['designationStatus']) === '1';
            }, selected),
            'designationId',
            function (row) {
                return row['designationDesc'] || '';
            },
            'Choose Designation',
            selected
        );
    }

    function fillClientSelect(selected) {
        GemsUI.fillSelect(
            'optMusClientId',
            rowsFromRef(refClient, 'clientId', 'clientName', function (row) {
                return String(row['clientStatus']) === '1';
            }, selected),
            'clientId',
            function (row) {
                return row['clientName'] || '';
            },
            'Choose Client',
            selected
        );
    }

    function fillSiteSelect(clientId, selected) {
        GemsUI.fillSelect(
            'optMusSiteId',
            rowsFromRef(refSite, 'siteId', 'siteName', function (row) {
                if (clientId !== undefined && clientId !== null && clientId !== ''
                    && String(row['clientId']) !== String(clientId)) {
                    return false;
                }
                return String(row['siteStatus']) === '1';
            }, selected),
            'siteId',
            function (row) {
                return row['siteName'] || '';
            },
            'Choose Site',
            selected
        );
    }

    function clearSiteSelect() {
        GemsUI.fillSelect('optMusSiteId', [], 'siteId', 'siteName', 'Choose Site', '');
    }

    function getRoleIdByDesc(desc) {
        if (!refRole) return '';
        if (Array.isArray(refRole)) {
            for (const r of refRole) {
                if (!r) continue;
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

    function applyPtwRoleIds() {
        console.log('[modal_user] applyPtwRoleIds called, refRole:', refRole);
        let rSup = getRoleIdByDesc('PTW Supervisor');
        let rShe = getRoleIdByDesc('PTW SHE');
        let rFm  = getRoleIdByDesc('PTW Facility Manager');
        if (!rSup) {
            rSup = String($('#chkMusRolePTWSUP').attr('data-role-id') || $('#chkMusRolePTWSUP').val() || '24').trim();
        }
        if (!rShe) {
            rShe = String($('#chkMusRolePTWSHE').attr('data-role-id') || $('#chkMusRolePTWSHE').val() || '25').trim();
        }
        if (!rFm) {
            rFm = String($('#chkMusRolePTWFM').attr('data-role-id') || $('#chkMusRolePTWFM').val() || '26').trim();
        }
        console.log('[modal_user] PTW role IDs: Supervisor=' + rSup + ', SHE=' + rShe + ', FM=' + rFm);
        $('#chkMusRolePTWSUP').val(rSup).attr('data-role-id', rSup);
        $('#chkMusRolePTWSHE').val(rShe).attr('data-role-id', rShe);
        $('#chkMusRolePTWFM').val(rFm).attr('data-role-id', rFm);

        let rMrReviewer = getRoleIdByDesc('MR Reviewer');
        if (!rMrReviewer) {
            const existing = String($('#chkMusRoleMRReviewer').attr('data-role-id') || $('#chkMusRoleMRReviewer').val() || '').trim();
            rMrReviewer = existing ? existing : '27';
        }
        $('#chkMusRoleMRReviewer').val(rMrReviewer).attr('data-role-id', rMrReviewer);
    }

    function checkDynamicRolesByValue(roleIds) {
        if (!roleIds || !Array.isArray(roleIds)) return;
        const dynamicRoleCheckboxes = [
            '#chkMusRoleMRReviewer',
            '#chkMusRolePTWSUP',
            '#chkMusRolePTWSHE',
            '#chkMusRolePTWFM'
        ];
        dynamicRoleCheckboxes.forEach(function(selector) {
            const $chk = $(selector);
            const chkVal = String($chk.val() || '').trim();
            if (chkVal && roleIds.indexOf(chkVal) !== -1) {
                $chk.prop('checked', true);
            }
        });
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
                $('#divMusRole1, #divMusRole19, #divMusRole2, #divMusRole3, #divMusRole5, #divMusRole7, #divMusRole8, #divMusRole9, #divMusRole10, #divMusRole11, #divMusRole12, #divMusRole13, #divMusRole14, #divMusRole16, #divMusRole17, #divMusRole18, #divMusRole4, #divMusRole20, #divMusRole21, #divMusRoleMRReviewer, #divMusRolePTWSUP, #divMusRolePTWSHE, #divMusRolePTWFM').show();
                $('#divMusRole6').hide();
            } else if ($(this).val() === '2') {
                $('.divMusRoles').show();
                $('#divMusRole1, #divMusRole19, #divMusRole2, #divMusRole3, #divMusRole5, #divMusRole7, #divMusRole8, #divMusRole9, #divMusRole10, #divMusRole11, #divMusRole12, #divMusRole13, #divMusRole14, #divMusRole16, #divMusRole17, #divMusRole18, #divMusRole20, #divMusRole21, #divMusRoleMRReviewer, #divMusRolePTWSUP, #divMusRolePTWSHE, #divMusRolePTWFM').hide();
                $('#divMusRole6, #divMusRole4').show();
                $('#divMusReportGap, #divMusExecutor, #divMusReviewer').hide();
            }
        });

        $('#optMusClientId').on('change', function () {
            fillSiteSelect($(this).val(), '');
        });

        $('#btnMusSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateNow()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        let rolesStr = '';
                        let isClient = false;
                        let isInternalComplainer = false;
                        let isWoAssigner = false;
                        let isWoHelpdesk = false;
                        console.log('[modal_user] Collecting checked roles:');
                        $("input[name='chkMusRole[]']:checked").map(function(){
                            const val = ($(this).val() || '').trim();
                            console.log('[modal_user] Checkbox id=' + $(this).attr('id') + ', val=' + val);
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
                        console.log('[modal_user] Final rolesStr:', rolesStr);
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
        $("input[name='chkMusRole[]']:checkbox").prop('checked', false);
        $('#divMusRolePTWSUP, #divMusRolePTWSHE, #divMusRolePTWFM').hide();
        $('#divMusRoleMRReviewer').hide();
        $('#chkMusRolePTWSUP, #chkMusRolePTWSHE, #chkMusRolePTWFM').val('');
        $('#chkMusRoleMRReviewer').val('');
    };

    this.add = function () {
        userId = '';
        rowRefresh = '';

        ShowLoader();
        setTimeout(function () {
            try {
                fillDesignationSelect();
                fillClientSelect();
                clearSiteSelect();

                formValidate.enableField('txtMusUserName');
                formValidate.enableField('txtMusUserPassword');

                applyPtwRoleIds();
                $("input[name='chkMusRole[]']:checkbox").prop('checked', false);

                $('.divMusAddOnly').show();
                $('#lblMusTitle').html('<i class="fas fa-user-plus me-2"></i>Register New User');
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

                applyPtwRoleIds();
                $("input[name='chkMusRole[]']:checkbox").prop('checked', false);

                const dataUser = mzAjaxRequest('profile.php?userId='+userId, 'GET');
                const roles = dataUser['roles'];
                const rolesArray = roles ? roles.split(',') : [];
                const userType = dataUser['userType'];

                fillDesignationSelect(dataUser['designationId']);
                fillClientSelect(dataUser['clientId']);
                fillSiteSelect(dataUser['clientId'], dataUser['siteId']);

                formValidate.disableField('txtMusUserName');
                formValidate.disableField('txtMusUserPassword');
                mzSetFieldValue('MusUserName', dataUser['userName'], 'text');
                mzSetFieldValue('MusUserType', userType, 'check');
                mzSetFieldValue('MusUserFirstName', dataUser['userFirstName'], 'text');
                mzSetFieldValue('MusUserContactNo', dataUser['userContactNo'], 'text');
                mzSetFieldValue('MusUserEmail', dataUser['userEmail'], 'text');
                mzSetFieldValue('MusRole', rolesArray, 'check');
                checkDynamicRolesByValue(rolesArray);

                if (userType === '1') {
                    $('.divMusRoles').show();
                    $('#divMusRole1, #divMusRole19, #divMusRole2, #divMusRole3, #divMusRole5, #divMusRole7, #divMusRole8, #divMusRole9, #divMusRole10, #divMusRole11, #divMusRole12, #divMusRole13, #divMusRole14, #divMusRole16, #divMusRole17, #divMusRole18, #divMusRole4, #divMusRole20, #divMusRole21, #divMusRoleMRReviewer, #divMusRolePTWSUP, #divMusRolePTWSHE, #divMusRolePTWFM').show();
                    $('#divMusRole6').hide();
                }
                else if (userType === '2') {
                    $('.divMusRoles').show();
                    $('#divMusRole1, #divMusRole19, #divMusRole2, #divMusRole3, #divMusRole5, #divMusRole7, #divMusRole8, #divMusRole9, #divMusRole10, #divMusRole11, #divMusRole12, #divMusRole13, #divMusRole14, #divMusRole16, #divMusRole17, #divMusRole18, #divMusRole11, #divMusRole20, #divMusRole21, #divMusRoleMRReviewer, #divMusRolePTWSUP, #divMusRolePTWSHE, #divMusRolePTWFM').hide();
                    $('#divMusRole6, #divMusRole4').show();
                }
                formValidate.validateForm();

                $('#lblMusTitle').html('<i class="fas fa-user-edit me-2"></i>Edit User Profile');
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

    this.setRefRole = function (_refRole) {
        refRole = _refRole;
        applyPtwRoleIds();
    };
}
