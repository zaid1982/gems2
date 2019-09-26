function ModalEditPassword() {

    const className = 'ModalEditPassword';
    let self = this;
    let rowRefresh = '';
    let classFrom;
    let userId = '';
    let formValidate;

    this.init = function () {
        const vDataMpz = [
            {
                field_id: 'txtMpzUserName',
                type: 'text',
                name: 'User Name',
                validator: {
                }
            },
            {
                field_id: 'txtMpzNewPassword',
                type: 'text',
                name: 'New Password',
                validator: {
                    notEmpty: true,
                    maxLength: 20,
                    minLength: 6
                }
            },
            {
                field_id: 'txtMpzConfirmPassword',
                type: 'text',
                name: 'Confirm New Password',
                validator: {
                    notEmpty: true,
                    maxLength: 20,
                    minLength: 6,
                    similar: {
                        id: "txtMpzNewPassword",
                        label: "New Password"
                    }
                }
            }
        ];

        formValidate = new MzValidate('formMpz');
        formValidate.registerFields(vDataMpz);

        $('#formMpz').on('keyup change', function () {
            $('#btnMpzSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_edit_password').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMpzSubmit').attr('disabled', true);
        });

        $('#btnMpzSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        if (classFrom.getClassName() === 'MainUserManagement') {
                            const data = {
                                action: 'edit_password',
                                newPassword: $('#txtMpzConfirmPassword').val()
                            };
                            mzAjaxRequest('profile.php?userId=' + userId, 'PUT', data);
                        } else {
                            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
                        }
                        $('#modal_edit_password').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.edit = function (_userId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_userId, _rowRefresh]);
                userId = _userId;
                rowRefresh = _rowRefresh;
                //mzAjaxRequest('profile.php?userId='+_userId, 'PUT', {action: 'deactivate'});
                const dataUser = mzAjaxRequest('profile.php?userId='+_userId, 'GET');
                mzSetFieldValue('MpzUserName', dataUser['userName'], 'text');
                $('#modal_edit_password').modal({backdrop: 'static', keyboard: false});
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
}