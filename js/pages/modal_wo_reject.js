function ModalWoReject () {

    const className = 'ModalWoReject';
    let self = this;
    let formValidate;
    let classFrom;
    let woTaskId;

    const vData = [
        {
            field_id: 'txaMwjRemark',
            type: 'text',
            name: 'Remark',
            validator: {
                notEmpty: true,
                maxLength: 1000
            }
        }
    ];


    this.init = function () {
        formValidate = new MzValidate('formMwj');
        formValidate.registerFields(vData);

        $('#btnMwjSubmit').on('click', function () {
            try {
                if (!formValidate.validateNow()) {
                    toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                } else {
                    ShowLoader(); setTimeout(function () {
                        mzFetch('wo_v3/reject_complaint/'+woTaskId, 'PUT', {remark: mzNullString('txaMwjRemark')}).then(res => {
                            classFrom.afterReject();
                            $('#modal_wo_reject').modal('hide');
                        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                    }, 200);
                }
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });
    };

    this.load = function (_woTaskId) {
        try {
            mzCheckFuncParam([_woTaskId]);
            woTaskId = _woTaskId;
            formValidate.clearValidation();
            $('#modal_wo_reject').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
    }

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };
}