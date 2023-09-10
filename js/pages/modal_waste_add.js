function ModalWasteAdd () {

    const className = 'ModalWasteAdd';
    let self = this;
    let classFrom;

    this.init = function () {
        $('#btnMwaSave').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    $('#modal_waste_add').modal('hide');
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });
    };

    this.add = function () {
        ShowLoader();
        setTimeout(function () {
            try {
                $('#modal_waste_add').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };
}