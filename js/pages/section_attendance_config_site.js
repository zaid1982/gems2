function SectionAttendanceConfigSite () {

    const className = 'SectionAttendanceConfigSite';
    let self = this;
    let classFrom;
    let siteId;
    let refStatus;
    let oTableSacGroup;
    let oTableSacParticipant;
    let modalAttendanceGroupClass;

    this.init = function () {
        $('.sectionAttendanceConfigSite').hide();

        $('#btnSacBack').on('click', function () {
            $('.sectionAttendanceConfigSite').hide();
            classFrom.showMain();
            $(window).scrollTop(0);
        });

        oTableSacGroup = $('#dtSacGroup').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 50,
            autoWidth: false,
            dom: "<'row'<'col-5 px-0'B><'col-7 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0] }
            ],
            buttons: [
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ', text:'<i class="fas fa-print"></i>', title:'GEMS - Attendance Group List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 ', text:'<i class="fas fa-copy"></i>', title:'GEMS - Attendance Group List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 ', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Attendance Group List', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 ', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Attendance Group List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt},
                { text: 'Add Attendance Group', className: 'btn btn-danger btn-sm px-2 btnSacAddGroup'}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.btnSacAddGroup').off('click').on('click', function () {
                    modalAttendanceGroupClass.add(siteId);
                });
            },
            aoColumns: [
                {mData: null},
                {mData: 'attGroupDesc'},
                {mData: 'attGroupSupervisor'},
                {mData: 'attGroupPolygon'},
                {mData: 'dayShiftTime'},
                {mData: 'nightShiftTime'},
                {mData: 'attGroupHoliday'},
                {mData: 'attGroupReqWeeklyHours'},
                {mData: 'attGroupShiftMode'},
                {mData: 'totalParticipant'},
                {mData: null, width: '5%'}
            ]
        });
    };

    this.loadDetails = function () {
        try {
            mzCheckFuncParam([siteId]);
            const attSite = mzAjaxRequest2('att_group/site/'+siteId, 'GET');
            $('#lblSacSiteName').html(attSite['siteName']);
            $('#lblSacSiteCode').html(attSite['siteCode']);
            $('#lblSacTotalGroup').html(attSite['totalGroup']);
            $('#lblSacTotalParticipant').html(attSite['totalParticipant']);
            $('#lblSacSiteStatus').html((attSite['siteIsAttendance']==='1'?'Enabled':'Disabled'));
            modalAttendanceGroupClass.setSiteName(attSite['siteName']);
            modalAttendanceGroupClass.setSiteCode(attSite['siteCode']);
        } catch (e) {
            throw new Error(e.message);
        }
    };

    this.load = function (_siteId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteId]);
                siteId = _siteId;
                self.loadDetails();
                $('.sectionAttendanceConfigSite').show();
                classFrom.hideMain();
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

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setModalAttendanceGroupClass = function (_modalAttendanceGroupClass) {
        modalAttendanceGroupClass = _modalAttendanceGroupClass;
    };
}