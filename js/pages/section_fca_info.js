function SectionFcaInfo () {

    const className = 'SectionFcaInfo';
    let self = this;
    let classFrom;
    let fcaTaskId;

    this.init = function () {
        self.hideSection();

        $('#btnSfcBack').on('click', function () {
            self.hideSection();
            classFrom.showMain();
            $(window).scrollTop(0);
        });
    };

    this.load = function (_fcaTaskId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_fcaTaskId]);
                fcaTaskId = _fcaTaskId;
                classFrom.hideMain();
                self.showSection();
                window.scrollTo({top: 0, behavior: 'smooth'});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.showSection = function () {
        $('.sectionFcaInfo').show();
    };

    this.hideSection = function () {
        $('.sectionFcaInfo').hide();
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };
}