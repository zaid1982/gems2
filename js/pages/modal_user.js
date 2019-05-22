function ModalUser() {

    const className = 'ModalUser';
    let self = this;
    let rowRefresh = '';
    let classFrom;
    let userId = '';
    let refJabatan;
    let refJawatan;
    let formValidate;

    this.init = function () {
        const vDataMus = [
            {
                field_id: 'txtMusUserFirstName',
                type: 'text',
                name: 'Nama',
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
                name: 'Kata Laluan',
                validator: {
                    notEmpty: true,
                    maxLength: 30
                }
            },
            {
                field_id: 'optMusJabatanId',
                type: 'select',
                name: 'Jabatan',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMusJawatanId',
                type: 'select',
                name: 'Jawatan',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMusUserContactNo',
                type: 'text',
                name: 'No. Telefon',
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
                name: 'Emel',
                validator: {
                    notEmpty: true,
                    email: true,
                    maxLength: 100
                }
            },
            {
                field_id: 'chkMusRole[]',
                type: 'check',
                name: 'Peranan',
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

        $('#modal_user').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
        });

        $('#optMusJabatanId').on('change', function () {
            $('#lblMusJabatanId').html('Jabatan *').addClass('active');
        });

        $('#optMusJawatanId').on('change', function () {
            $('#lblMusJawatanId').html('Jawatan *').addClass('active');
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
                        jabatanId: $('#optMusJabatanId').val(),
                        jawatanId: $('#optMusJawatanId').val(),
                        roles: rolesStr
                    };

                    tempRow['userName'] = $('#txtMusUserName').val();
                    tempRow['userFullName'] = $('#txtMusUserFirstName').val();
                    tempRow['userContactNo'] = $('#txtMusUserContactNo').val();
                    tempRow['jabatanId'] = $('#optMusJabatanId').val();
                    tempRow['jawatanId'] = $('#optMusJawatanId').val();
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

    this.add = function () {
        userId = '';
        rowRefresh = '';

        ShowLoader();
        setTimeout(function () {
            try {
                mzOption('optMusJabatanId', refJabatan, 'Pilih Jabatan *', 'jabatanId', 'jabatanDesc', {jabatanStatus: '1'}, 'required');
                mzOption('optMusJawatanId', refJawatan, 'Pilih Jawatan *', 'jawatanId', 'jawatanDesc', {jawatanStatus: '1'}, 'required');

                formValidate.enableField('txtMusUserName');
                formValidate.enableField('txtMusUserPassword');

                $('.divMusAddOnly').show();
                $('#lblMusTitle').html('<i class="fas fa-user-plus"></i> &nbsp;Daftar Pengguna Sistem');
                $('#txtMusUserName').prop('disabled', false);
                $('#btnMusSubmit').prop('disabled', true);
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
                mzOption('optMusJabatanId', refJabatan, 'Pilih Jabatan *', 'jabatanId', 'jabatanDesc');
                mzOption('optMusJawatanId', refJawatan, 'Pilih Jawatan *', 'jawatanId', 'jawatanDesc');

                const dataUser = mzAjaxRequest('profile.php?userId='+userId, 'GET');
                const roles = dataUser['roles'];
                formValidate.disableField('txtMusUserName');
                formValidate.disableField('txtMusUserPassword');
                mzSetFieldValue('MusUserName', dataUser['userName'], 'text');
                mzSetFieldValue('MusUserFirstName', dataUser['userFirstName'], 'text');
                mzSetFieldValue('MusUserContactNo', dataUser['userContactNo'], 'text');
                mzSetFieldValue('MusUserEmail', dataUser['userEmail'], 'text');
                mzSetFieldValue('MusJabatanId', dataUser['jabatanId'], 'select', 'Jabatan *');
                mzSetFieldValue('MusJawatanId', dataUser['jawatanId'], 'select', 'Jawatan *');
                mzSetFieldValue('MusRole', roles.split(','), 'check');
                formValidate.validateForm();

                $('.divMusAddOnly').hide();
                $('#lblMusTitle').html('<i class="fas fa-user"></i> &nbsp;Kemaskini Pengguna Sistem');
                $('#btnMusSubmit, #txtMusUserName').prop('disabled', true);
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
                mzOption('optMusJabatanId', refJabatan, 'Pilih Jabatan *', 'jabatanId', 'jabatanDesc');
                mzOption('optMusJawatanId', refJawatan, 'Pilih Jawatan *', 'jawatanId', 'jawatanDesc');

                const dataUser = mzAjaxRequest('profile.php?userId='+userId, 'GET');
                const roles = dataUser['roles'];
                formValidate.disableField('txtMusUserName');
                formValidate.disableField('txtMusUserPassword');
                formValidate.disableField('txtMusUserFirstName');
                formValidate.disableField('txtMusUserContactNo');
                formValidate.disableField('txtMusUserEmail');
                formValidate.disableField('optMusJabatanId');
                formValidate.disableField('optMusJawatanId');
                formValidate.disableField('chkMusRole[]');
                mzSetFieldValue('MusUserName', dataUser['userName'], 'text');
                mzSetFieldValue('MusUserFirstName', dataUser['userFirstName'], 'text');
                mzSetFieldValue('MusUserContactNo', dataUser['userContactNo'], 'text');
                mzSetFieldValue('MusUserEmail', dataUser['userEmail'], 'text');
                mzSetFieldValue('MusJabatanId', dataUser['jabatanId'], 'select', 'Jabatan *');
                mzSetFieldValue('MusJawatanId', dataUser['jawatanId'], 'select', 'Jawatan *');
                mzSetFieldValue('MusRole', roles.split(','), 'check');
                formValidate.validateForm();

                $('.divMusAddOnly, #btnMusSubmit').hide();
                $('#lblMusTitle').html('<i class="fas fa-user"></i> &nbsp;Maklumat Pengguna Sistem');
                $('#optMusJabatanId, #optMusJawatanId').material_select('destroy');
                $('#txtMusUserName, #txtMusUserFirstName, #txtMusUserContactNo, #txtMusUserEmail, #optMusJabatanId, #optMusJawatanId').prop('disabled', true);
                $('#optMusJabatanId, #optMusJawatanId').material_select();
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

    this.setRefJabatan = function (_refJabatan) {
        refJabatan = _refJabatan;
    };

    this.setRefJawatan = function (_refJawatan) {
        refJawatan = _refJawatan;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };
}