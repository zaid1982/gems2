function SectionPpmAsset () {

    const className = 'SectionPpmAsset';
    let self = this;
    let classFrom;
    let refStatus;
    let refPpmGroup;

    this.init = function () {
        //self.hideSection();

        $('#btnSpgBack').on('click', function () {
            self.hideSection();
            classFrom.showMain();
            $(window).scrollTop(0);
        });

    };

    this.load = function (_ppmId) {
        try {

            classFrom.hideMain();
            self.showSection();
            window.scrollTo({top: 0, behavior: 'smooth'});
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
    };

    this.showSection = function () {
        $('.sectionPpmAsset').show();
    };

    this.hideSection = function () {
        $('.sectionPpmAsset').hide();
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };
}