function SectionAttendanceConfigSite () {

    const className = 'SectionAttendanceConfigSite';
    let self = this;
    let classFrom;
    let siteId;

    this.init = function () {
        $('.sectionAttendanceConfigSite').hide();

        $('#btnSacBack').on('click', function () {
            $('.sectionAttendanceConfigSite').hide();
            classFrom.showMain();
            $(window).scrollTop(0);
        });
    };

    this.load = function (_siteId) {
        try {
            ShowLoader();
            setTimeout(function () {
                try {
                    mzCheckFuncParam([_siteId]);
                    siteId = _siteId;

                    $('.sectionAttendanceConfigSite').show();
                    classFrom.hideMain();
                    window.scrollTo({top: 0, behavior: 'smooth'});
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        } catch (e) {
            throw new Error(e.message);
        }
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };
}