function MainWoAssign () {

    const className = 'MainWoAssign';
    let self = this;
    let oTableWssPending;
    let sectionWoClass;
    let refStatus;
    let refUser;
    let refWoType = {
        1: 'Client Complaint',
        2: 'Self Finding',
        3: 'Request',
        4: 'Breakdown',
        5: 'Defect',
        6: 'Public Complaint'
    };
    let currentTab;

    this.init = function () {
        currentTab = 'Pending';
        self.showMain();
        
        oTableWssPending = $('#dtWssPending').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'desc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-12 col-sm-7 px-0 pb-2'B><'col-sm-5 d-none d-sm-block pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 7] },
                { className: 'text-center', targets: [0, 1, 2, 6, 7] },
                { className: 'noVis', targets: [0, 7] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'three-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFctObserveHide', text:'<i class="fas fa-print"></i>', title:'GEMS - Assign WO or WR Pending List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - Assign WO or WR Pending List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Assign WO or WR Pending List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-2 btnFctObserveHide', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Assign WO or WR Pending List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt},
                { text: '<i class="fas fa-sync"></i>', className: 'btn btn-outline-purple btn-sm px-2 ml-0', attr: { id: 'btnWssPendingRefresh' }, titleAttr: 'Refresh'}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('#btnWssPendingRefresh').off('click').on('click', function () {
                    self.genTable();
                });
                $('.lnkWssPendingEdit').off('click').on('click', function () {
                    const woTaskId = mzGetLinkId($(this), oTableWssPending, 'woTaskId');
                    sectionWoClass.assign(woTaskId);
                });
            },
            aoColumns: [
                { mData: null},
                { mData: 'taskTimeCreated'},
                { mData: 'woTaskNo'},
                { mData: 'woTaskLocation'},
                { mData: 'woTaskType', mRender: function (data) {
                        return data !== null ? refWoType[data] : '';
                    }},
                { mData: 'woTaskCreatedBy', mRender: function (data) {
                        return data !== null ? refUser[data]['userFirstName'] : '';
                    }},
                { mData: 'woTaskStatus', mRender: function (data) {
                        return '<h6><span class="badge badge-pill z-depth-2 '+refStatus[data]['statusColor']+'">'+refStatus[data]['statusDesc']+'</span></h6>';
                    }},
                { mData: null, bSortable: false, mRender: function (data, type, row, meta) {
                        let label = '';
                        if (row['woTaskIsWr'] === 1) {
                            label += '<a><i class="fas fa-file-alt lnkWssPendingPdfWr" id="lnkWssPendingPdfWr_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Work Request PDF"></i></a>&nbsp;';
                        }
                        if (row['woTaskIsWr'] !== 1 || row['woTaskTimeWrVerified'] !== null) {
                            label += '<a><i class="far fa-file-pdf lnkWssPendingPdf" id="lnkWssPendingPdf_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Work Order PDF"></i></a>';
                        }
                        label += '&nbsp;<a><i class="fas fa-edit lnkWssPendingEdit" id="lnkWssPendingEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Process"></i></a>';
                        return label;
                    }}
            ]
        });

        $('#btnWssPending').on('click', function () {
            ShowLoader(); setTimeout(function () {
                $('.sectionWssTask').hide();
                $('.sectionWssPending').show();
                $('.btnWssTab').removeClass('lighten-2').addClass('lighten-2');
                $('#btnWssPending').removeClass('lighten-2');
                window.scrollTo({top: 0, behavior: 'smooth'});
                HideLoader(); }, 200);
        });

        $('#btnWssSubmitted').on('click', function () {
            ShowLoader(); setTimeout(function () {
                $('.sectionWssTask').hide();
                $('.sectionWssSubmitted').show();
                $('.btnWssTab').removeClass('lighten-2').addClass('lighten-2');
                $('#btnWssSubmitted').removeClass('lighten-2');
                window.scrollTo({top: 0, behavior: 'smooth'});
            HideLoader(); }, 200);
        });
    };

    this.genTable = function () {
        ShowLoader(); setTimeout(function () { mzFetch('wo_v3/pending_assign').then(res => {
            $('#badgeWssTotalPending').text(res.length);
            oTableWssPending.clear().rows.add(res).draw();
        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); }); }, 200);
    };

    this.getClassName = function () {
        return className;
    };
    
    this.showMain = function () {
        $('.sectionWssMain').show();
        $('.sectionWssTask').hide();
        $('.sectionWss'+currentTab).show();
    };
    
    this.hideMain = function () {
        $('.sectionWssMain').hide();
    };
    
    this.setSectionWoClass = function (_sectionWoClass) {
        sectionWoClass = _sectionWoClass;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };
}