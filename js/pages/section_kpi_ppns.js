function SectionKpiPpns () {

    const className = 'SectionKpiPpns';
    let self = this;
    let classFrom;
    let kpiId;
    let kpiPpnsCategory;
    let kpiPpnsId;
    let isEdit;

    this.init = function () {
        $('.sectionKpiPpns').hide();

        $('#btnSkpBack').on('click', function () {
            $('.sectionKpiPpns').hide();
            classFrom.showMain();
            $(window).scrollTop(0);
        });
    };

    this.load = function (_isEdit, _kpiId, _kpiPpnsCategory) {
        try {
            mzCheckFuncParam([_isEdit, _kpiId, _kpiPpnsCategory]);
            isEdit = _isEdit;
            kpiId = _kpiId;
            kpiPpnsCategory = _kpiPpnsCategory;

            $('.sectionKpiPpns').show();
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