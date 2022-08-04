function SectionAttendancePlanner () {

    const className = 'SectionAttendancePlanner';
    let self = this;
    let classFrom;
    let attGroupId;
    let siteId;
    let oTableStx;
    let year;
    let month;
    let refUser;
    let refAttType;
    let thisDate;
    let modalAttendanceDailyClass;

    this.init = function () {
        self.hideMain();
        thisDate = moment();

        oTableStx = $('#dtStxData').DataTable({
            bLengthChange: false,
            bFilter: false,
            bInfo: false,
            aaSorting: [[1, 'asc']],
            ordering: false,
            language: _DATATABLE_LANGUAGE,
            bPaginate: false,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
                for (let i = 1; i <= parseInt(thisDate.endOf('month').format('D')); i++) {
                    const attTypeId = aData['data'][i]['attTypeId'];
                    $('td', nRow).eq(i+1).addClass(refAttType[attTypeId]['attTypeColor']);
                }
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                const lastDay = parseInt(thisDate.endOf('month').format('D'));
                $(this).DataTable().column(30).visible(true);
                $(this).DataTable().column(31).visible(true);
                $(this).DataTable().column(32).visible(true);
                if (lastDay === 28) {
                    $(this).DataTable().column(30).visible(false);
                    $(this).DataTable().column(31).visible(false);
                    $(this).DataTable().column(32).visible(false);
                } else if (lastDay === 29) {
                    $(this).DataTable().column(31).visible(false);
                    $(this).DataTable().column(32).visible(false);
                } else if (lastDay === 30) {
                    $(this).DataTable().column(32).visible(false);
                }
                $('.lnkStxInfo').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkArray = linkId.split('_');
                    if (linkArray.length === 3 && linkArray[0] === 'lnkStxInfo') {
                        const currentRow = oTableStx.row(parseInt(linkArray[1])).data();
                        modalAttendanceDailyClass.load(currentRow['data'][parseInt(linkArray[2])]);
                    }
                });
            },
            columnDefs: [
                { className: 'text-left', targets: [1] }
            ],
            aoColumns: [
                {mData: null},
                {mData: 'userId', mRender: function(data) {
                        return '<div class="table-grid-cut-text">'+refUser[data]['userFirstName']+'</div>';
                    }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 1, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 2, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 3, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 4, meta.row); }}, // 5
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 5, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 6, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 7, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 8, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 9, meta.row); }}, // 10
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 10, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 11, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 12, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 13, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 14, meta.row); }}, // 15
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 15, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 16, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 17, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 18, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 19, meta.row); }}, // 20
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 20, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 21, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 22, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 23, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 24, meta.row); }}, // 25
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 25, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 26, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 27, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 28, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 29, meta.row); }}, // 30
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 30, meta.row); }},
                {mData: 'data', mRender: function(data, type, row, meta) {   return self.getCellDisplay(data, 31, meta.row); }}
            ]
        });
    };

    this.getCellDisplay = function (_data, _day, _metaRow) {
        try {
            if (typeof _day !== 'number' || _day < 1 || _day > parseInt(thisDate.endOf('month').format('D'))) {
                return '';
            }
            const attTypeId = _data[_day]['attTypeId'];
            let colorHoover = 'white-darker-hover';
            const shiftMode = refAttType[attTypeId]['attTypeMode'];
            if (shiftMode === 'Normal' || shiftMode === '2 Shifts' || shiftMode === '3 Shifts') {
                if (_data[_day]['result'] === 'Present') {
                    //colorHoover = 'success-lighter-hover';
                    colorHoover = 'white-darker-hover';
                } else if (_data[_day]['result'] === 'Absent') {
                    colorHoover = 'danger-lighter-hover';
                } else {
                    colorHoover = moment().isAfter(_data[_day]['attTransactionDate']) ? 'danger-lighter-hover' : 'white-darker-hover';
                }
            }
            const dayName = _data[_day]['dayName'];
            $('#thStxDay'+_day).text(dayName.substr(0, 2));
            return '<a><span class="' + colorHoover + ' lnkStxInfo" id="lnkStxInfo_' + _metaRow + '_' + _day + '" data-toggle="tooltip" data-placement="top" title="Click to view / edit daily details">' + refAttType[attTypeId]['attTypeShort'] + '</span></a>';
        } catch (e) {
            throw new Error(e.message);
        }
    };

    this.loadSite = function (_siteId, _year, _month) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteId, _year, _month]);
                siteId = _siteId;
                year = _year;
                month = _month;
                thisDate = moment();
                thisDate.set({'year': year, 'month': parseInt(month) - 1});
                self.genTable();
                self.showMain();
                window.scrollTo({top: 0, behavior: 'smooth'});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.genTable = function () {
        try {
            const dataDb = mzAjaxRequest2('att_transaction/monthly/site/'+siteId+'/'+year+'/'+month, 'GET');
            oTableStx.clear().rows.add(dataDb).draw();
            if (dataDb.length === 0) {
                $('#btnStxRunPlanner').show();
            } else {
                $('#btnStxRunPlanner').hide();
            }
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

    this.getClassFrom = function () {
        return classFrom;
    };

    this.showMain = function () {
        $('.sectionAttendancePlanner').show();
    };

    this.hideMain = function () {
        $('.sectionAttendancePlanner').hide();
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefAttType = function (_refAttType) {
        refAttType = _refAttType;
    };

    this.setModalAttendanceDailyClass = function (_modalAttendanceDailyClass) {
        modalAttendanceDailyClass = _modalAttendanceDailyClass;
    };
}