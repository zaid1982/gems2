function MainGamification () {

    const className = 'MainGamification';
    let self = this;
    let monthArray;
    let currentType;
    let currentYear;
    let currentMonth;
    let currentTitle;
    let tempType;
    let tempYear;
    let tempMonth;
    let tempTitle;
    let oTableGmiTop5;
    let oTableGmiStats;
    let refUser;
    let refSite;
    let refDesignation;
    let sectionUserGameClass;

    this.init = function () {
        monthArray = mzGetMonthArray();
        let dateEarliest = new Date();
        dateEarliest.setFullYear(2019, 2, 1);
        let dateCtr = new Date();
        currentType = 1;
        currentYear = dateCtr.getFullYear();
        currentMonth = dateCtr.getMonth();
        currentTitle = 'Monthly - ' + monthArray[currentMonth]['monthName'] + ', ' +currentYear;
        tempType = currentType;
        tempYear = currentYear;
        tempMonth = currentMonth;
        tempTitle = currentTitle;

        for (let i = 2019; i <= currentYear; i++) {
            const isActive = (i === currentYear) ? 'active text-white' : '';
            $('#divGmiYear').append('<a class="dropdown-item lnkGmiYear '+isActive+'" href="#" id="lnkGmiYear_'+i+'">'+i+'</a>');
        }

        for (let i = 0; i < monthArray.length; i++) {
            const isActive = (i === currentMonth) ? 'active text-white' : '';
            $('#divGmiMonth').append('<a class="dropdown-item lnkGmiMonth '+isActive+'" href="#" id="lnkGmiMonth_'+i+'">'+monthArray[i]['monthName']+'</a>');
        }

        $('#navGmiType').text('Monthly');
        $('#navGmiYear').text(currentYear);
        $('#navGmiMonth').text(monthArray[currentMonth]['monthName']);
        $('.lblGmiSelectedTitle').text(currentTitle);

        $('.lnkGmiType').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            $('#lnkGmiType_'+tempType).removeClass('active').removeClass('text-white');
            tempType = parseInt(linkId.substr(linkIndex + 1));
            $('#navGmiType').text(tempType === 2 ? 'Weekly' : 'Monthly');
            $('#lnkGmiType_'+tempType).addClass('active').addClass('text-white');
        });

        $('.lnkGmiYear').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            $('#lnkGmiYear_'+tempYear).removeClass('active').removeClass('text-white');
            tempYear = parseInt(linkId.substr(linkIndex + 1));
            $('#navGmiYear').text(tempYear);
            $('#lnkGmiYear_'+tempYear).addClass('active').addClass('text-white');
        });

        $('.lnkGmiMonth').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            $('#lnkGmiMonth_'+tempMonth).removeClass('active').removeClass('text-white');
            tempMonth = parseInt(linkId.substr(linkIndex + 1));
            $('#navGmiMonth').text(monthArray[tempMonth]['monthName']);
            $('#lnkGmiMonth_'+tempMonth).addClass('active').addClass('text-white');
        });

        $('#btnGmiFilter').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    currentType = tempType;
                    currentYear = tempYear;
                    currentMonth = tempMonth;
                    currentTitle = tempTitle;
                    $('#navGmiType').text(currentType === 2 ? 'Weekly' : 'Monthly');
                    $('#navGmiYear').text(currentYear);
                    $('#navGmiMonth').text(monthArray[currentMonth]['monthName']);
                    currentTitle = (currentType === 2 ? 'Weekly' : 'Monthly') + ' - ' + monthArray[currentMonth]['monthName'] + ', ' +currentYear;
                    $('.lblGmiSelectedTitle').text(currentTitle);
                    self.genTableTop5();
                    self.genTableStats();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnGmiSyncData').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    const runType = currentType === 2 ? 'run_weekly' : 'run_monthly';
                    mzAjaxRequest2('gamification/'+runType+'/'+currentYear+'/'+(currentMonth+1), 'PUT');
                    self.genTableTop5();
                    self.genTableStats();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        oTableGmiTop5 = $('#dtGmiTop5').DataTable({
            bLengthChange: false,
            bFilter: false,
            ordering: false,
            language: _DATATABLE_LANGUAGE,
            autoWidth: false,
            dom: "<'row'<'col-12 px-0'B>>" +
                "<'row'<'col-sm-12'tr>>",
            columnDefs: [
                { className: 'text-center', targets: [2, 3, 4] }
            ],
            buttons: [
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ', text:'<i class="fas fa-print"></i>', title:'GEMS - Top Leaderboard : '+currentTitle, titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 ', text:'<i class="fas fa-copy"></i>', title:'GEMS - Top Leaderboard : '+currentTitle, titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 ', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Top Leaderboard : '+currentTitle, titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 ', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Top Leaderboard : '+currentTitle, titleAttr: 'PDF', exportOptions: mzExportOpt}
            ],
            aoColumns: [
                {mData: 'userId', mRender: function (data) {
                        return refUser[data]['userFullName'];
                    }},
                {mData: 'siteId', mRender: function (data) {
                        return refSite[data]['siteName'];
                    }},
                {mData: 'gmiWoTierName'},
                {mData: 'gmiPpmTierName'},
                {mData: 'gmiPointTotal', mRender: function (data) {
                        return mzFormatNumber(data);
                    }}
            ]
        });
        let oTableGmiTop5Tbody = $('#dtGmiTop5 tbody');
        oTableGmiTop5Tbody.delegate('tr', 'click', function (evt) {
            const data = $('#dtGmiTop5').DataTable().row(this).data();
            sectionUserGameClass.load(data['gmiId'], data['userId']);
        });
        oTableGmiTop5Tbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtGmiTop5').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', 'Click to see '+refUser[data['userId']]['userFullName']+' profile');
            $('[data-toggle="tooltip"]').tooltip();
        });
        if ($('#dtGmiTop5').width() < 520) {
            oTableGmiTop5.column(1).visible(false);
        }

        let exportGmiStatsPdf = Object.assign({}, mzExportOpt);
        exportGmiStatsPdf['columns'] = [0, 1, 2, 3, 4, 5, 20, 21, 22, 23, 28];
        oTableGmiStats = $('#dtGmiStats').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[27, 'desc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            autoWidth: false,
            dom: "<'row'<'col-5 px-0'B><'col-7 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [4, 5] },
                { className: 'text-right', targets: [6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'four-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ', text:'<i class="fas fa-print"></i>', title:'GEMS - Gamification Stats : '+currentTitle, titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 ', text:'<i class="fas fa-copy"></i>', title:'GEMS - Gamification Stats : '+currentTitle, titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 ', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Gamification Stats : '+currentTitle, titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 ', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Gamification Stats : '+currentTitle, titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportGmiStatsPdf}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            aoColumns: [
                {mData: null},
                {mData: 'userId', mRender: function (data) {
                        return refUser[data]['userFullName'];
                    }},
                {mData: 'siteId', mRender: function (data) {
                        return refSite[data]['siteName'];
                    }},
                {mData: null, mRender: function (data, type, row) {
                        const userId = row['userId'];
                        const designationId = refUser[userId]['designationId'];
                        return designationId !== '' ? refDesignation[designationId]['designationDesc'] : '';
                    }},
                {mData: 'gmiWoTierName', width: '9%'},
                {mData: 'gmiPpmTierName', width: '9%'},
                {mData: 'gmiWoTierPoint', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data, 1);
                    }},
                {mData: 'gmiPpmTierPoint', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data, 1);
                    }},
                {mData: 'gmiPpmTotal', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPpmCompleted', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPpmOnTime', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPpmWithin', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPpmLate', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPpmAssist', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiWoTotal', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiWoCompleted', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiWoOnTime', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiWoLate', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiWoAssist', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiWoSelfFinding', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: null, width: '6%', mRender: function (data, type, row) {
                        return mzFormatNumber(parseInt(row['gmiWoTotal']) + parseInt(row['gmiPpmTotal']));
                    }},
                {mData: null, width: '6%', mRender: function (data, type, row) {
                        return mzFormatNumber(parseInt(row['gmiWoCompleted']) + parseInt(row['gmiPpmCompleted']));
                    }},
                {mData: null, width: '6%', mRender: function (data, type, row) {
                        return mzFormatNumber(parseInt(row['gmiWoOnTime']) + parseInt(row['gmiPpmOnTime']));
                    }},
                {mData: null, width: '6%', mRender: function (data, type, row) {
                        return mzFormatNumber(parseInt(row['gmiWoLate']) + parseInt(row['gmiPpmLate']));
                    }},
                {mData: 'gmiPointCompleted', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPointOnTime', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPointLate', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPointSelfFinding', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'gmiPointTotal', width: '6%', mRender: function (data) {
                        return mzFormatNumber(data);
                    }}
            ]
        });
        oTableGmiStats.column(3).visible(false);
        oTableGmiStats.column(6).visible(false);
        oTableGmiStats.column(7).visible(false);
        oTableGmiStats.column(8).visible(false);
        oTableGmiStats.column(9).visible(false);
        oTableGmiStats.column(10).visible(false);
        oTableGmiStats.column(11).visible(false);
        oTableGmiStats.column(12).visible(false);
        oTableGmiStats.column(13).visible(false);
        oTableGmiStats.column(14).visible(false);
        oTableGmiStats.column(15).visible(false);
        oTableGmiStats.column(16).visible(false);
        oTableGmiStats.column(17).visible(false);
        oTableGmiStats.column(18).visible(false);
        oTableGmiStats.column(19).visible(false);
        oTableGmiStats.column(24).visible(false);
        oTableGmiStats.column(25).visible(false);
        oTableGmiStats.column(26).visible(false);
        oTableGmiStats.column(27).visible(false);

        let oTableGmiStatsTbody = $('#dtGmiStats tbody');
        oTableGmiStatsTbody.delegate('tr', 'click', function (evt) {
            const data = $('#dtGmiStats').DataTable().row(this).data();
            sectionUserGameClass.load(data['gmiId'], data['userId']);
        });
        oTableGmiStatsTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtGmiStats').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', 'Click to see '+refUser[data['userId']]['userFullName']+' profile');
            $('[data-toggle="tooltip"]').tooltip();
        });

        self.genTableTop5();
        self.genTableStats();
    };

    this.genTableTop5 = function () {
        const runType = currentType === 2 ? 'gmi_weekly_top_5' : 'gmi_monthly_top_5';
        const dataDb = mzAjaxRequest2('gamification/'+runType+'/'+currentYear+'/'+(currentMonth+1), 'GET');
        oTableGmiTop5.clear().rows.add(dataDb).draw();
    };

    this.genTableStats = function () {
        const runType = currentType === 2 ? 'gmi_weekly' : 'gmi_monthly';
        const dataDb = mzAjaxRequest2('gamification/'+runType+'/'+currentYear+'/'+(currentMonth+1), 'GET');
        oTableGmiStats.clear().rows.add(dataDb).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.showMain = function () {
        $('.sectionGmiMain').show();
    };

    this.hideMain = function () {
        $('.sectionGmiMain').hide();
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefDesignation = function (_refDesignation) {
        refDesignation = _refDesignation;
    };

    this.setSectionUserGameClass = function (_sectionUserGameClass) {
        sectionUserGameClass = _sectionUserGameClass
    };
}