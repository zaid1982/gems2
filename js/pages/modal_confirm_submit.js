function ModalConfirmSubmit() {

    let mcsType;
    let mcsCallFrom;

    this.init = function () {
        $('#btnMcsSubmit').on('click', function () {
            switch (mcsType) {
                case 'submit_workorder':
                    workOrderClass.submitWorkorder();
                    break;
                default:
                    toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            }
            $('#modal_confirm_submit').modal('hide');
        });
    };

    this.submit = function (callFrom, type) {
        if (typeof callFrom === 'undefined' || callFrom === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        if (typeof type === 'undefined' || type == '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        mcsCallFrom = callFrom;
        mcsType = type;
        $('#modal_confirm_submit').modal({backdrop: 'static', keyboard: false});
    };

    this.init();
}