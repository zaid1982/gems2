function ModalForgotPassword() {

    this.init = function () {
        const vDataMfp = [
            {
                field_id: 'txtMfpUserId',
                type: 'text',
                name: 'User ID',
                validator: {
                    notEmpty: true,
                    maxLength: 100
                }
            }
        ];

        let formMfpValidate = new MzValidate('formMfpForgotPassword');
        formMfpValidate.registerFields(vDataMfp);

        $('#formMfpForgotPassword').on('keyup', function () {
            $('#btnMfpSend').attr('disabled', !formMfpValidate.validateForm());
        });

        $('#modalForgotPassword').on('shown.bs.modal', function () {
            formMfpValidate.clearValidation();
            $('#btnMfpSend').attr('disabled', true);
        });

        $('#btnMfpSend').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formMfpValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const data = {
                            action: 'forgot_password',
                            username: $('#txtMfpUserId').val()
                        };
                        mzAjaxRequest('login.php', 'POST', data);
                        $('#modalForgotPassword').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.init();
}