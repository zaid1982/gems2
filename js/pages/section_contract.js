function SectionContract() {

    const className = "SectionContract";
    let self = this;
    let modalConfirmDeleteClass;
    let contractId = '';
    let rowRefresh = '';
    let classFrom;

    this.init = function () {
        $('.sectionContract').hide();

        $('#btnSctBack').on('click', function () {
            $('.sectionContract').hide();
            if (classFrom.getClassName() === 'MainContract') {
                $('.sectionCcrMain').show();
            }
            $(window).scrollTop(0);
        });

    };

    this.view = function (_contractId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                contractId = _contractId;
                rowRefresh = _rowRefresh;

                $('.sectionContract').show();
                if (classFrom.getClassName() === 'MainContract') {
                    $('.sectionCcrMain').hide();
                }
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };
}