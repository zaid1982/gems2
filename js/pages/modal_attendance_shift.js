function ModalAttendanceShift() {

    const className = 'ModalAttendanceShift';
    let self = this;

    this.init = function () {

    };

    this.edit = function () {
        ShowLoader();
        setTimeout(function () {
            try {
                $('#modal_attendance_shift').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };
}