function ModalUser() {

    const musClassName = 'ModalUser';
    let self = this;
    let musCallFrom = '';
    let musUserId = '';
    let musRowRefresh = '';
    let musRefJabatan;
    let musRefPangkat;
    let musRefJawatan;
    let formMusValidate;

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
                field_id: 'txtMusUserBadanNo',
                type: 'text',
                name: 'No. Badan',
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
                field_id: 'optMusPangkatId',
                type: 'select',
                name: 'Pangkat',
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

        formMusValidate = new MzValidate('formMus');
        formMusValidate.registerFields(vDataMus);

        $('#formMus').on('keyup change', function () {
            $('#btnMusSubmit').attr('disabled', !formMusValidate.validateForm());
        });

        $('#modal_user').on('hidden.bs.modal', function(){
            formMusValidate.clearValidation();
        });

        $('#optMusJabatanId').on('change', function () {
            $('#lblMusJabatanId').html('Jabatan *').addClass('active');
        });

        $('#optMusPangkatId').on('change', function () {
            $('#lblMusPangkatId').html('Pangkat *').addClass('active');
        });

        $('#optMusJawatanId').on('change', function () {
            $('#lblMusJawatanId').html('Jawatan *').addClass('active');
        });

        $('#btnMusSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formMusValidate.validateForm()) {
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
                        userBadanNo: $('#txtMusUserBadanNo').val(),
                        userContactNo: $('#txtMusUserContactNo').val(),
                        userEmail: $('#txtMusUserEmail').val(),
                        jabatanId: $('#optMusJabatanId').val(),
                        pangkatId: $('#optMusPangkatId').val(),
                        jawatanId: $('#optMusJawatanId').val(),
                        roles: rolesStr
                    };

                    tempRow['userName'] = $('#txtMusUserName').val();
                    tempRow['userFullName'] = $('#txtMusUserFirstName').val();
                    tempRow['userBadanNo'] = $('#txtMusUserBadanNo').val();
                    tempRow['userContactNo'] = $('#txtMusUserContactNo').val();
                    tempRow['jabatanId'] = $('#optMusJabatanId').val();
                    tempRow['pangkatId'] = $('#optMusPangkatId').val();
                    tempRow['jawatanId'] = $('#optMusJawatanId').val();
                    tempRow['roles'] = rolesStr;
                    tempRow['userStatus'] = '1';

                    if (musUserId === '') {
                        data['action'] = 'add_user';
                        tempRow['userId'] = mzAjaxRequest('profile.php?', 'POST', data);
                        if (musCallFrom === 'umn') {
                            oTableUmnUser.row.add(tempRow).draw();
                        }
                    } else {
                        data['action'] = 'update_user';
                        mzAjaxRequest('profile.php?userId='+musUserId, 'PUT', data);
                        tempRow['userId'] = musUserId;
                        if (musCallFrom === 'umn') {
                            oTableUmnUser.row(musRowRefresh).data(tempRow).draw();
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

    this.add = function (callFrom) {
        if (typeof callFrom === 'undefined' || callFrom === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        musCallFrom = callFrom;
        musUserId = '';
        musRowRefresh = '';

        ShowLoader();
        setTimeout(function () {
            try {
                mzOption('optMusJabatanId', musRefJabatan, 'Pilih Jabatan *', 'jabatanId', 'jabatanDesc', {jabatanStatus: '1'}, 'required');
                mzOption('optMusPangkatId', musRefPangkat, 'Pilih Pangkat *', 'pangkatId', 'pangkatDesc', {pangkatStatus: '1'}, 'required');
                mzOption('optMusJawatanId', musRefJawatan, 'Pilih Jawatan *', 'jawatanId', 'jawatanDesc', {jawatanStatus: '1'}, 'required');

                formMusValidate.enableField('txtMusUserName');
                formMusValidate.enableField('txtMusUserPassword');

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

    this.edit = function (callFrom, userId, rowRefresh) {
        if (typeof callFrom === 'undefined' || callFrom === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        if (typeof userId === 'undefined' || userId === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        if (typeof rowRefresh === 'undefined' || rowRefresh === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        musCallFrom = callFrom;
        musUserId = userId;
        musRowRefresh = rowRefresh;

        ShowLoader();
        setTimeout(function () {
            try {
                mzOption('optMusJabatanId', musRefJabatan, 'Pilih Jabatan *', 'jabatanId', 'jabatanDesc');
                mzOption('optMusPangkatId', musRefPangkat, 'Pilih Pangkat *', 'pangkatId', 'pangkatDesc');
                mzOption('optMusJawatanId', musRefJawatan, 'Pilih Jawatan *', 'jawatanId', 'jawatanDesc');

                const dataUser = mzAjaxRequest('profile.php?userId='+musUserId, 'GET');
                const roles = dataUser['roles'];
                formMusValidate.disableField('txtMusUserName');
                formMusValidate.disableField('txtMusUserPassword');
                mzSetFieldValue('MusUserName', dataUser['userName'], 'text');
                mzSetFieldValue('MusUserFirstName', dataUser['userFirstName'], 'text');
                mzSetFieldValue('MusUserBadanNo', dataUser['userBadanNo'], 'text');
                mzSetFieldValue('MusUserContactNo', dataUser['userContactNo'], 'text');
                mzSetFieldValue('MusUserEmail', dataUser['userEmail'], 'text');
                mzSetFieldValue('MusJabatanId', dataUser['jabatanId'], 'select', 'Jabatan *');
                mzSetFieldValue('MusPangkatId', dataUser['pangkatId'], 'select', 'Pangkat *');
                mzSetFieldValue('MusJawatanId', dataUser['jawatanId'], 'select', 'Jawatan *');
                mzSetFieldValue('MusRole', roles.split(','), 'check');
                formMusValidate.validateForm();

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

    this.view = function (callFrom, userId) {
        if (typeof callFrom === 'undefined' || callFrom === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        if (typeof userId === 'undefined' || userId === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        musCallFrom = callFrom;
        musUserId = userId;
        musRowRefresh = '';

        ShowLoader();
        setTimeout(function () {
            try {
                mzOption('optMusJabatanId', musRefJabatan, 'Pilih Jabatan *', 'jabatanId', 'jabatanDesc');
                mzOption('optMusPangkatId', musRefPangkat, 'Pilih Pangkat *', 'pangkatId', 'pangkatDesc');
                mzOption('optMusJawatanId', musRefJawatan, 'Pilih Jawatan *', 'jawatanId', 'jawatanDesc');

                const dataUser = mzAjaxRequest('profile.php?userId='+musUserId, 'GET');
                const roles = dataUser['roles'];
                formMusValidate.disableField('txtMusUserName');
                formMusValidate.disableField('txtMusUserPassword');
                formMusValidate.disableField('txtMusUserFirstName');
                formMusValidate.disableField('txtMusUserBadanNo');
                formMusValidate.disableField('txtMusUserContactNo');
                formMusValidate.disableField('txtMusUserEmail');
                formMusValidate.disableField('optMusJabatanId');
                formMusValidate.disableField('optMusPangkatId');
                formMusValidate.disableField('optMusJawatanId');
                formMusValidate.disableField('chkMusRole[]');
                mzSetFieldValue('MusUserName', dataUser['userName'], 'text');
                mzSetFieldValue('MusUserFirstName', dataUser['userFirstName'], 'text');
                mzSetFieldValue('MusUserBadanNo', dataUser['userBadanNo'], 'text');
                mzSetFieldValue('MusUserContactNo', dataUser['userContactNo'], 'text');
                mzSetFieldValue('MusUserEmail', dataUser['userEmail'], 'text');
                mzSetFieldValue('MusJabatanId', dataUser['jabatanId'], 'select', 'Jabatan *');
                mzSetFieldValue('MusPangkatId', dataUser['pangkatId'], 'select', 'Pangkat *');
                mzSetFieldValue('MusJawatanId', dataUser['jawatanId'], 'select', 'Jawatan *');
                mzSetFieldValue('MusRole', roles.split(','), 'check');
                formMusValidate.validateForm();

                $('.divMusAddOnly, #btnMusSubmit').hide();
                $('#lblMusTitle').html('<i class="fas fa-user"></i> &nbsp;Maklumat Pengguna Sistem');
                $('#optMusJabatanId, #optMusPangkatId, #optMusJawatanId').material_select('destroy');
                $('#txtMusUserName, #txtMusUserFirstName, #txtMusUserBadanNo, #txtMusUserContactNo, #txtMusUserEmail, #optMusJabatanId, #optMusPangkatId, #optMusJawatanId').prop('disabled', true);
                $('#optMusJabatanId, #optMusPangkatId, #optMusJawatanId').material_select();
                $('input[name="chkMusRole[]"]').prop("disabled", true);
                $('#modal_user').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (callFrom, userId, rowRefresh) {
        if (typeof callFrom === 'undefined' || callFrom === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        if (typeof userId === 'undefined' || userId === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        if (typeof rowRefresh === 'undefined' || rowRefresh === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        ShowLoader();
        setTimeout(function () {
            try {
                mzAjaxRequest('profile.php?userId='+userId, 'PUT', {action: 'deactivate'});
                if (callFrom === 'umn') {
                    const currentRow = oTableUmnUser.row(rowRefresh).data();
                    let tempRow = currentRow;
                    tempRow['userStatus'] = '2';
                    oTableUmnUser.row(rowRefresh).data(tempRow).draw();
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (callFrom, userId, rowRefresh) {
        if (typeof callFrom === 'undefined' || callFrom === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        if (typeof userId === 'undefined' || userId === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        if (typeof rowRefresh === 'undefined' || rowRefresh === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        ShowLoader();
        setTimeout(function () {
            try {
                mzAjaxRequest('profile.php?userId='+userId, 'PUT', {action: 'activate'});
                if (callFrom === 'umn') {
                    const currentRow = oTableUmnUser.row(rowRefresh).data();
                    let tempRow = currentRow;
                    tempRow['userStatus'] = '1';
                    oTableUmnUser.row(rowRefresh).data(tempRow).draw();
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.setRefJabatan = function (refJabatan) {
        musRefJabatan = refJabatan;
    };

    this.setRefPangkat = function (refPangkat) {
        musRefPangkat = refPangkat;
    };

    this.setRefJawatan = function (refJawatan) {
        musRefJawatan = refJawatan;
    };

    this.init();
}