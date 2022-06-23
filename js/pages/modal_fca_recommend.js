function ModalFcaRecommend () {

    const className = 'ModalFcaRecommend';
    let self = this;
    let formValidate;
    let classFrom;

    this.init = function () {

    };

    this.load = function (_fcaTaskId, _taskId) {
        ShowLoader();
        setTimeout(function () {
            try {
                //formValidate.clearValidation();
                $('#modal_fca_recommend').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };
}