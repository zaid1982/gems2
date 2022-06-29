function MainFcaReport () {

    const className = 'MainFcaReport';
    let self = this;
    let oTableFcr;
    let refSite;
    let isAuditor = false;
    let modalFcaReportClass;

    this.init = function () {
        isAuditor = mzIsRoleExist('22');

        oTableFcr = $('#dtFcrData').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[9, 'desc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-5 col-sm-7 px-0 pb-2'B><'col-7 col-sm-5 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0, 3, 4, 6, 7, 9, 10] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'one-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFcrHide', text:'<i class="fas fa-print"></i>', title:'GEMS - FCA Report List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFcrHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - FCA Report List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnFcrHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - FCA Report List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-3 btnFcrHide', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - FCA Report List', titleAttr: 'PDF', exportOptions: mzExportOpt},
                { text: '<i class="fas fa-plus mr-2"></i>Generate New Report', className: 'btn btn-outline-red btn-sm px-2 ml-0', attr: { id: 'btnFcrAdd' }, titleAttr: 'Generate New FCA Report'}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('#btnFcrAdd').off('click').on('click', function () {
                    if (!isAuditor) {
                        toastr['error']('You don\'t have permission as FCA Auditor role to generate PDF Report!', _ALERT_TITLE_ERROR);
                        return false;
                    }
                    ShowLoader();
                    setTimeout(function () {
                        try {
                            const data = {
                                fcaReportName: 'Test Report',
                                fcaReportDateFrom: '2022-01-01',
                                fcaReportDateTo: '2022-08-01',
                                siteId: 19,
                                assetGroupId: '',
                                fcaReportExcludeList: 1,
                                fcaReportSortBy: 'asset_group_name'
                            };
                            mzAjaxRequest2('fca_report', 'POST', data);
                        } catch (e) {
                            toastr['error'](e.message, _ALERT_TITLE_ERROR);
                        }
                        HideLoader();
                    }, 200);
                    //modalFcaReportClass.add();
                });
                if ($('#divFcrCard').width() < 546) {
                    $(this).DataTable().column(3).visible(false);
                    $(this).DataTable().column(4).visible(false);
                    $(this).DataTable().column(5).visible(false);
                    $(this).DataTable().column(6).visible(false);
                    $(this).DataTable().column(7).visible(false);
                    $(this).DataTable().column(8).visible(false);
                    $(this).DataTable().column(9).visible(false);
                    $('.btnFcrHide').hide();
                }
            },
            aoColumns: [
                {mData: null},
                {mData: 'siteId', mRender: function(data) {
                        return refSite[data]['siteName'];
                    }},
                {mData: 'fcaReportName'},
                {mData: 'fcaReportStatus', mRender: function(data) {
                        return refStatus[data]['statusDesc'];
                    }}
            ]
        });
        let oTableFcrTbody = $('#dtFcrData tbody');
        oTableFcrTbody.delegate('tr', 'click', function () {
            const data = $('#dtFcrData').DataTable().row(this).data();
            //modalFcaReportClass.edit(data['fcaReportId']);
        });
        oTableFcrTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtFcrData').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', 'Click to download '+data['fcaReportName']);
            $('[data-toggle="tooltip"]').tooltip();
        });

        //self.genTable();
    };

    this.genTable = function () {
        const dataDb = mzAjaxRequest2('fca_report', 'GET');
        oTableFcr.clear().rows.add(dataDb).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setModalFcaReportClass = function (_modalFcaReportClass) {
        modalFcaReportClass = _modalFcaReportClass;
    };
}