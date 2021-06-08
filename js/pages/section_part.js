function SectionPart () {

    const className = 'SectionPart';
    let self = this;
    let classFrom;
    let isEdit;
    let partId;

    this.init = function () {
        $('.sectionPart').hide();

        $('#btnSiyBack').on('click', function () {
            $('.sectionPart').hide();
            classFrom.showMain();
            $(window).scrollTop(0);
        });

        initPhotoSwipeFromDOM('.mdb-lightbox');
    };

    this.load = function (_isEdit, _partId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_isEdit, _partId]);
                isEdit = _isEdit;
                partId = _partId;
                
                
                $('.sectionPart').show();
                classFrom.hideMain();
                $(window).scrollTop(0);
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