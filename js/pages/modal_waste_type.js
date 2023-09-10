function ModalWasteType () {

    const className = 'ModalWasteType';
    let self = this;
    let classFrom;

    this.init = function () {
        $('#btnMwtSave').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    $('#modal_waste_type').modal('hide');
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
                $('#modal_waste_type').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
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