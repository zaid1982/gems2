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

                    let tempRow = {};
                    let rolesStr = '';
                    $("input[name='chkMusRole[]']:checked").map(function(){
                        rolesStr += ','+$(this).val();
                    });
                    rolesStr = rolesStr.substr(1);

                    const data = {
                        userName: $('#txtMusUserName').val(),
                        userPassword: $('#txtMusUserPassword').val(),
                        userFirstName: $('#txtMusUserFirstName').val(),
                        userContactNo: $('#txtMusUserContactNo').val(),
                        userEmail: $('#txtMusUserEmail').val(),
                        designationId: $('#optMusDesignationId').val(),
                        roles: rolesStr
                    };

                    tempRow['userName'] = $('#txtMusUserName').val();
                    tempRow['userFullName'] = $('#txtMusUserFirstName').val();
                    tempRow['designationId'] = $('#optMusDesignationId').val();
                    tempRow['userContactNo'] = $('#txtMusUserContactNo').val();
                    tempRow['userEmail'] = $('#txtMusUserEmail').val();
                    tempRow['roles'] = rolesStr;
                    tempRow['userStatus'] = '1';

                    if (userId === '') {
                        data['action'] = 'add_user';
                        tempRow['userId'] = mzAjaxRequest('profile.php?', 'POST', data);
                        if (classFrom.getClassName() === 'MainUserManagement') {
                            classFrom.addTableUmn(tempRow);
                        }
                    } else {
                        data['action'] = 'update_user';
                        mzAjaxRequest('profile.php?userId='+userId, 'PUT', data);
                        tempRow['userId'] = userId;
                        if (classFrom.getClassName() === 'MainUserManagement') {
                            classFrom.updateTableUmn(tempRow, rowRefresh);
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
        if (typeof _userId === 'undefined' || _userId === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        if (typeof _rowRefresh === 'undefined' || _rowRefresh === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        userId = _userId;
        rowRefresh = _rowRefresh;

        ShowLoader();
        setTimeout(function () {
            try {
                mzOption('optMusDesignationId', refDesignation, 'Choose Designation *', 'designationId', 'designationDesc');

                const dataUser = mzAjaxRequest('profile.php?userId='+userId, 'GET');
                const roles = dataUser['roles'];
                formValidate.disableField('txtMusUserName');
                formValidate.disableField('txtMusUserPassword');
                mzSetFieldValue('MusUserName', dataUser['userName'], 'text');
                mzSetFieldValue('MusUserFirstName', dataUser['userFirstName'], 'text');
                mzSetFieldValue('MusUserContactNo', dataUser['userContactNo'], 'text');
                mzSetFieldValue('MusUserEmail', dataUser['userEmail'], 'text');
                mzSetFieldValue('MusDesignationId', dataUser['designationId'], 'select', 'Designation *');
                mzSetFieldValue('MusRole', roles.split(','), 'check');
                formValidate.validateForm();

                $('#lblMusTitle').html('<i class="fas fa-user"></i> &nbsp;Edit User Profile');
                $('#txtMusUserName').prop('disabled', true);
                $('#modal_user').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.view = function (_userId) {
        if (typeof _userId === 'undefined' || _userId === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        userId = _userId;
        rowRefresh = '';

        ShowLoader();
        setTimeout(function () {
            try {
                mzOption('optMusDesignationId', refDesignation, 'Choose Designation *', 'designationId', 'designationDesc');

                const dataUser = mzAjaxRequest('profile.php?userId='+userId, 'GET');
                const roles = dataUser['roles'];
                formValidate.disableField('txtMusUserName');
                formValidate.disableField('txtMusUserPassword');
                formValidate.disableField('txtMusUserFirstName');
                formValidate.disableField('txtMusUserContactNo');
                formValidate.disableField('txtMusUserEmail');
                formValidate.disableField('optMusDesignationId');
                formValidate.disableField('chkMusRole[]');
                mzSetFieldValue('MusUserName', dataUser['userName'], 'text');
                mzSetFieldValue('MusUserFirstName', dataUser['userFirstName'], 'text');
                mzSetFieldValue('MusUserContactNo', dataUser['userContactNo'], 'text');
                mzSetFieldValue('MusUserEmail', dataUser['userEmail'], 'text');
                mzSetFieldValue('MusDesignationId', dataUser['designationId'], 'select', 'Designation *');
                mzSetFieldValue('MusRole', roles.split(','), 'check');
                formValidate.validateForm();

                $('#btnMusSubmit').hide();
                $('#lblMusTitle').html('<i class="fas fa-user"></i> &nbsp;User Profile Information');
                $('#optMusDesignationId').material_select('destroy');
                $('#txtMusUserName, #txtMusUserFirstName, #txtMusUserContactNo, #txtMusUserEmail, #optMusDesignationId').prop('disabled', true);
                $('#optMusDesignationId').material_select();
                $('input[name="chkMusRole[]"]').prop("disabled", true);
                $('#modal_user').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_userId, _rowRefresh) {
        if (typeof _userId === 'undefined' || _userId === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        if (typeof _rowRefresh === 'undefined' || _rowRefresh === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        ShowLoader();
        setTimeout(function () {
            try {
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
        if (typeof _userId === 'undefined' || _userId === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        if (typeof _rowRefresh === 'undefined' || _rowRefresh === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        ShowLoader();
        setTimeout(function () {
            try {
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