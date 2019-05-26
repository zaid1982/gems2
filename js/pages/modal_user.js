function ModalUser() {

    const className = 'ModalUser';
    let self = this;
    let rowRefresh = '';
    let classFrom;
    let userId = '';
    let refDesignation;
    let refClient;
    let refSite;
    let formValidate;

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

        $('#modal_user').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            self.defaultPageSetup();
        });

        $("input[name='chkMusUserType']:radio").on('click', function () {
            $('#optMusClientId').val(null);
            $('#optMusSiteId').val(null);
            $("input[name='chkMusRole[]']:checkbox").prop('checked',false);
            formValidate.validateForm();
            if ($(this).val() === '1') {
                $('.divMusRoles').show();
                $('#divMusClient, #divMusSite').hide();
                $('#divMusRole1, #divMusRole2, #divMusRole3, #divMusRole4, #divMusRole5').show();
                $('#divMusRole6').hide();
                formValidate.disableField('optMusClientId');
                formValidate.disableField('optMusSiteId');
            } else if ($(this).val() === '2') {
                $('.divMusRoles, #divMusClient, #divMusSite').show();
                $('#divMusRole1, #divMusRole2, #divMusRole3, #divMusRole4, #divMusRole5').hide();
                $('#divMusRole6').show();
                formValidate.enableField('optMusClientId');
                formValidate.enableField('optMusSiteId');
            }
        });

        $('#optMusClientId').on('change', function () {
            mzOption('optMusSiteId', refSite, 'Choose Site', 'siteId', 'siteName', {clientId: $(this).val(), siteStatus: '1'}, 'required');
        });

        $('#btnMusSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        throw new Error(_ALERT_MSG_VALIDATION);
                    }

                    let rolesStr = '';
                    $("input[name='chkMusRole[]']:checked").map(function(){
                        rolesStr += ','+$(this).val();
                    });
                    rolesStr = rolesStr.substr(1);
                    const userType = $("input[name='chkMusUserType']:checked").val();

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
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.defaultPageSetup = function () {
        $('.divMusAddOnly, #divMusClient, #divMusSite, .divMusRoles').hide();
        $('#chkMusUserType1, #chkMusUserType2, #optMusClientId, #optMusSiteId').prop('disabled', false);
        $('#btnMusSubmit').show();
        $('#btnMusSubmit').prop('disabled', true);
    };

    this.add = function () {
        userId = '';
        rowRefresh = '';

        ShowLoader();
        setTimeout(function () {
            try {
                mzOption('optMusDesignationId', refDesignation, 'Choose Designation', 'designationId', 'designationDesc', {designationStatus: '1'}, 'required');
                mzOption('optMusClientId', refClient, 'Choose Client', 'clientId', 'clientName', {clientStatus: '1'}, 'required');

                formValidate.enableField('txtMusUserName');
                formValidate.enableField('txtMusUserPassword');

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
                $('#chkMusUserType1, #chkMusUserType2, #optMusClientId, #optMusSiteId').prop('disabled', true);
                mzOption('optMusDesignationId', refDesignation, 'Choose Designation *', 'designationId', 'designationDesc', {designationStatus: '1'}, 'required');
                mzOption('optMusClientId', refClient, 'Choose Client', 'clientId', 'clientName', {clientStatus: '1'}, 'required');

                const dataUser = mzAjaxRequest('profile.php?userId='+userId, 'GET');
                const roles = dataUser['roles'];
                const groupId = dataUser['groupId'];
                formValidate.disableField('txtMusUserName');
                formValidate.disableField('txtMusUserPassword');
                mzSetFieldValue('MusUserName', dataUser['userName'], 'text');
                mzSetFieldValue('MusUserFirstName', dataUser['userFirstName'], 'text');
                mzSetFieldValue('MusUserContactNo', dataUser['userContactNo'], 'text');
                mzSetFieldValue('MusUserEmail', dataUser['userEmail'], 'text');
                mzSetFieldValue('MusDesignationId', dataUser['designationId'], 'select', 'Designation *');
                mzSetFieldValue('MusRole', roles.split(','), 'check');

                if (groupId === '1') {
                    mzSetFieldValue('MusUserType', '1', 'check');
                    $('.divMusRoles').show();
                    $('#divMusRole1, #divMusRole2, #divMusRole3, #divMusRole4, #divMusRole5').show();
                    $('#divMusRole6').hide();
                    formValidate.disableField('optMusClientId');
                    formValidate.disableField('optMusSiteId');
                }
                else if (groupId === '2') {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
                else {
                    mzOption('optMusSiteId', refSite, 'Choose Site', 'siteId', 'siteName', {clientId: dataUser['clientId'], siteStatus: '1'}, 'required');
                    mzSetFieldValue('MusUserType', '2', 'check');
                    mzSetFieldValue('MusClientId', dataUser['clientId'], 'select', 'Client *');
                    mzSetFieldValue('MusSiteId', dataUser['siteId'], 'select', 'Site *');
                    $('.divMusRoles, #divMusClient, #divMusSite').show();
                    $('#divMusRole1, #divMusRole2, #divMusRole3, #divMusRole4, #divMusRole5').hide();
                    $('#divMusRole6').show();
                    formValidate.disableField('optMusClientId');
                    formValidate.disableField('optMusSiteId');
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
}