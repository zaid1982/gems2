function SectionAttendanceGroup () {

    const className = 'SectionAttendanceConfigSite';
    let self = this;
    let classFrom;
    let attGroupId;

    /*$('td', nRow).eq(2).addClass('red text-white');
    mRender: function (data, type, row) {
        if (row.assetGroupId < 5) {
            return '<a class="float-right"><i class="fas fa-exclamation-circle fa-sm mr-1"></i></a><a class="float-right"><i class="far fa-check-square fa-sm mr-1"></i></a><span class="ml-3">P</span>';
        } else if (row.assetGroupId < 10) {
            return '<a class="float-right"><i class="fas fa-exclamation-circle fa-sm mr-1"></i></a><a class="float-right"><i class="far fa-check-square fa-sm mr-1"></i></a><a class="ml-3">P</a>'; // <a class="btn btn-sm btn-light text-dark px-1 py-1 ml-1">SET</a>
        }
        return '<span class="ml-3">P</span>';
    }*/

    this.init = function () {
        //self.hideMain();

        $('#btnSagBack').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.hideMain();
                    classFrom.showMain();
                    $(window).scrollTop(0);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });
    };

    this.load = function (_attGroupId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_attGroupId]);
                attGroupId = _attGroupId;

                classFrom.hideMain();
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