function ModalConfirmDelete() {

    let id;
    let rowRefresh;
    let returnClass;

    this.init = function () {
        $('#btnMcdSubmit').on('click', function () {
            switch (returnClass.getClassName()) {
                case 'ModalDesignation':
                case 'ModalClient':
                case 'ModalSite':
                case 'ModalContract':
                    if (typeof returnClass !== 'undefined') {
                        returnClass.delete(id, rowRefresh);
                    }
                    break;
                //default:
                    //toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            }
            $('#modal_confirm_delete').modal('hide');
        });
    };

    this.delete = function (_id, _rowRefresh, _returnClass) {
        if (typeof _id === 'undefined' || _id === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        if (typeof _rowRefresh === 'undefined' || _rowRefresh === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        id = _id;
        rowRefresh = _rowRefresh;
        returnClass = typeof _returnClass !== 'undefined' ? _returnClass : '';
        $('#modal_confirm_delete').modal({backdrop: 'static', keyboard: false});
    };

    this.init();
}