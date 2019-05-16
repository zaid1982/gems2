let mpfCallFrom = '';
let mpfUserId = '';

function initiateModalProfile() {

    const vDataMpf = [
        {
            field_id: 'txtMpfEmail',
            type: 'text',
            name: 'Emel',
            validator: {
                notEmpty: true,
                maxLength: 100,
                email: true
            }
        },
        {
            field_id: 'txtMpfFirstName',
            type: 'text',
            name: 'Nama (<i>first name</i>)',
            validator: {
                notEmpty: true,
                maxLength: 100
            }
        },
        {
            field_id: 'txtMpfLastName',
            type: 'text',
            name: 'Nama (<i>last name</i>)',
            validator: {
                notEmpty: true,
                maxLength: 100
            }
        },
        {
            field_id: 'txtMpfIdno',
            type: 'text',
            name: 'No MyKad',
            validator: {
                eqLength: 12,
                digit: true
            }
        },
        {
            field_id: 'txtMpfPhone',
            type: 'text',
            name: 'No Telefon',
            validator: {
                minLength: 8,
                maxLength: 11,
                digit: true
            }
        }
    ];

    let formMpfValidate = new MzValidate('formMpf');
    formMpfValidate.registerFields(vDataMpf);

    $('#formMpf').on('keyup change', function () {
        $('#btnMpfSubmit').attr('disabled', !formMpfValidate.validateForm());
    });

    $('#modal_profile').on('hidden.bs.modal', function(){
        formMpfValidate.clearValidation();
    });

    $('#modal_profile').on('shown.bs.modal', function(){
        $('#btnMpfSubmit').attr('disabled', true);
        ShowLoader();
        setTimeout(function () {
            try {
                if (mpfUserId === '') {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
                if (mpfCallFrom === 'Top') {
                    let userInfo = sessionStorage.getItem('userInfo');
                    userInfo = JSON.parse(userInfo);
                    mzSetFieldValue('MpfEmail', userInfo.userEmail, 'text');
                    mzSetFieldValue('MpfFirstName', userInfo.userFirstName, 'text');
                    mzSetFieldValue('MpfLastName', userInfo.userLastName, 'text');
                    mzSetFieldValue('MpfIdno', userInfo.userMykadNo, 'text');
                    mzSetFieldValue('MpfPhone', userInfo.profileContactNo, 'text');
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
                $('#modal_participant').modal('hide');
            }
            HideLoader();
        }, 300);
    });

    $('#btnMpfSubmit').on('click', function () {
        ShowLoader();
        setTimeout(function () {
            try {
                if (!formMpfValidate.validateForm()) {
                    throw new Error(_ALERT_MSG_VALIDATION);
                }
                if (mpfCallFrom === 'Top') {
                    const data = {
                        action: 'profile',
                        userEmail: $('#txtMpfEmail').val(),
                        userFirstName: $('#txtMpfFirstName').val(),
                        userLastName: $('#txtMpfLastName').val(),
                        userMykadNo: $('#txtMpfIdno').val(),
                        profileContactNo: $('#txtMpfPhone').val()
                    };
                    mzAjaxRequest('profile.php?userId='+mpfUserId, 'PUT', data);

                    let userInfo = sessionStorage.getItem('userInfo');
                    userInfo = JSON.parse(userInfo);
                    userInfo.userEmail = $('#txtMpfEmail').val();
                    userInfo.userFirstName = $('#txtMpfFirstName').val();
                    userInfo.userLastName = $('#txtMpfLastName').val();
                    userInfo.userMykadNo = $('#txtMpfIdno').val();
                    userInfo.profileContactNo = $('#txtMpfPhone').val();
                    sessionStorage.setItem('userInfo', JSON.stringify(userInfo));
                    toastr['success'](_ALERT_MSG_SUCCESS_UPDATE_USER, _ALERT_TITLE_SUCCESS);
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
                $('#modal_profile').modal('hide');
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    });
}

function loadModalProfile(callFrom, userId) {
    mpfCallFrom = callFrom;
    mpfUserId = typeof userId === 'undefined' ? '' : userId;

    $('#modal_profile').modal({backdrop: 'static', keyboard: false});
    $('#modal_profile').scrollTop(0);
}