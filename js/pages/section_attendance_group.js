function SectionAttendanceGroup () {

    const className = 'SectionAttendanceGroup';
    let self = this;
    let classFrom;
    let attGroupId;

    this.init = function () {
        self.hideMain();
    };

    this.load = function (_attGroupId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_attGroupId]);
                attGroupId = _attGroupId;

                if (typeof classFrom !== 'undefined') {
                    classFrom.hideMain();
                    //$('#divSagBack').show();
                }
                self.showMain();
                window.scrollTo({top: 0, behavior: 'smooth'});
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

    this.getClassFrom = function () {
        return classFrom;
    };

    this.showMain = function () {
        $('.sectionAttendanceGroup').show();
    };

    this.hideMain = function () {
        $('.sectionAttendanceGroup').hide();
    };
}