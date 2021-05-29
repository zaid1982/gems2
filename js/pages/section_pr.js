function SectionPr() {

    const className = 'SectionPr';
    let self = this;
    let classFrom;
    let isEdit;
    let prId;

    this.init = function () {
        $('.sectionPr').hide();

        $('#btnSprBack').on('click', function () {
            $('.sectionPr').hide();
            classFrom.showMain();
        });
    };

    this.load = function (_isEdit, _prId) {
        try {

            mzCheckFuncParam([_isEdit, _prId]);
            isEdit = _isEdit;
            prId = _prId;

            $('.sectionPr').show();
            classFrom.hideMain();
            window.scrollTo({top: 0, behavior: 'smooth'});
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