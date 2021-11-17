function ModalConfirmSubmit() {

    let id;
    let classFrom;
    let flag;

    this.init = function () {
        $('#btnMcsSubmit').on('click', function () {
            if (typeof classFrom !== 'undefined') {
                classFrom.confirmSubmit(id, flag);
            }
            $('#modal_confirm_submit').modal('hide');
        });
    };

    this.load = function (_id, _flag, _confirmText) {
        mzCheckFuncParam([_id]);
        id = _id;
        flag = _flag;
        $('#lblMcsConfirmation').html(typeof _confirmText !== 'undefined' ? _confirmText : 'Are you sure?')
        $('#modal_confirm_submit').modal({backdrop: 'static', keyboard: false});
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.init();
}