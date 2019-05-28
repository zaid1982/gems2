function ModalChangePassword() {

    let mpwCallFrom = '';
    let mpwUserId = '';

    this.init = function () {
        const vDataMpw = [
            {
                field_id: 'txtOldPassword',
                type: 'text',
                name: 'Current Password',
                validator: {
                    notEmpty: true,
                    maxLength: 20,
                    minLength: 6
                }
            },
            {
                field_id: 'txtNewPassword',
                type: 'text',
                name: 'New Password',
                validator: {
                    notEmpty: true,
                    maxLength: 20,
                    minLength: 6
                }
            },
            {
                field_id: 'txtConfirmPassword',
                type: 'text',
                name: 'Confirm New Password',
                validator: {
                    notEmpty: true,
                    maxLength: 20,
                    minLength: 6,
                    similar: {
                        id: "txtNewPassword",
                        label: "New Password"
                    }
                }
            }
        ];

        let formMpwValidate = new MzValidate('formMpw');
        formMpwValidate.registerFields(vDataMpw);

        $('#formMpw').on('keyup change', function () {
            $('#btnMpwSubmit').attr('disabled', !formMpwValidate.validateForm());
        });

        $('#modal_change_password')
            .on('hidden.bs.modal', function(){
                formMpwValidate.clearValidation();
            })
            .on('shown.bs.modal', function(){
                $('#btnMpfSubmit').attr('disabled', true);
                if (mpwCallFrom === 'Top') {
                    let userInfo = sessionStorage.getItem('userInfo');
                    userInfo = JSON.parse(userInfo);
                    mpwUserId = userInfo['userId'];
                }
            });

        $('#btnMpwSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formMpwValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        if (mpwCallFrom === 'Top') {
                            const data = {
                                action: 'password',
                                oldPassword: $('#txtOldPassword').val(),
                                newPassword: $('#txtConfirmPassword').val()
                            };
                            mzAjaxRequest('profile.php?userId=' + mpwUserId, 'PUT', data);
                        } else {
                            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
                        }
                        $('#modal_change_password').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.edit = function (callFrom, userId) {
        if (typeof callFrom === 'undefined' || callFrom === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        if (typeof userId === 'undefined' || userId === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        mpwCallFrom = callFrom;
        mpwUserId = typeof userId === 'undefined' ? '' : userId;
        $('#modal_change_password')
            .modal({backdrop: 'static', keyboard: false})
            .scrollTop(0);
    };

    this.init();
}