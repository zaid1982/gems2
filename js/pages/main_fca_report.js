function MainFcaReport () {

    const className = 'MainFcaReport';
    let self = this;
    let oTableFcr;
    let refSite;
    let refAssetGroup;
    let refUser;
    let isAuditor = false;
    let modalFcaReportClass;
    let modalConfirmDeleteClass;
    const refSort = {
        fca_task_time_created: 'Audit Date',
        asset_group_name: 'Asset Group',
        fca_zone_name: 'Zone',
        'fca_task_condition_scale DESC': 'Condition Scale'
    };

    this.init = function () {
        isAuditor = mzIsRoleExist('22');

        let exportOptFcr = Object.assign({}, mzExportOpt);
        exportOptFcr['columns'] = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        oTableFcr = $('#dtFcrData').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[10, 'desc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-5 col-sm-7 px-0 pb-2'B><'col-7 col-sm-5 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0,11] },
                { className: 'text-center', targets: [0, 3, 4, 6, 10, 11] },
                { className: 'text-right', targets: [8] },
                { visible: false, targets: [9] },
                { className: 'noVis', targets: [0, 11] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'four-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFcrHide', text:'<i class="fas fa-print"></i>', title:'GEMS - FCA Report List', titleAttr: 'Print', exportOptions: exportOptFcr},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFcrHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - FCA Report List', titleAttr: 'Copy', exportOptions: exportOptFcr},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnFcrHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - FCA Report List', titleAttr: 'Excel', exportOptions: exportOptFcr},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-3 btnFcrHide', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - FCA Report List', titleAttr: 'PDF', exportOptions: exportOptFcr},
                { text: '<i class="fas fa-plus mr-2"></i>Generate New Report', className: 'btn btn-outline-red btn-sm px-2 ml-0', attr: { id: 'btnFcrAdd' }, orientation: 'landscape', titleAttr: 'Generate New FCA Report'}
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
                    modalFcaReportClass.add();
                });
                if ($('#divFcrCard').width() < 546) {
                    $(this).DataTable().column(3).visible(false);
                    $(this).DataTable().column(4).visible(false);
                    $(this).DataTable().column(5).visible(false);
                    $(this).DataTable().column(6).visible(false);
                    $(this).DataTable().column(7).visible(false);
                    $(this).DataTable().column(8).visible(false);
                    $(this).DataTable().column(9).visible(false);
                    $(this).DataTable().column(10).visible(false);
                    $('.btnFcrHide').hide();
                }
                $('.lnkFcrPdf').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableFcr.row(parseInt(rowId)).data();
                        mzCheckFuncParam([currentRow['pdfId']]);
                        mzOpenPdfUpload2(currentRow['pdfId'], 'FCA Report: '+currentRow['fcaReportName']);
                    }
                });
                $('.lnkFcrDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableFcr.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['fcaReportId'], modalFcaReportClass);
                    }
                });
            },
            aoColumns: [
                {mData: null},
                {mData: 'siteId', mRender: function(data) {
                        return refSite[data]['siteName'];
                    }},
                {mData: 'fcaReportName'},
                {mData: 'fcaReportDateFrom'},
                {mData: 'fcaReportDateTo'},
                {mData: 'assetGroupId', mRender: function(data) {   // 5
                        return data !== null ? refAssetGroup[data]['assetGroupName'] : '';
                    }},
                {mData: 'fcaReportExcludeList', mRender: function(data) {
                        return data === 1 ? 'Yes' : 'No';
                    }},
                {mData: 'fcaReportSortBy', mRender: function(data) {
                        return refSort[data];
                    }},
                {mData: 'fcaReportTotal', mRender: function(data) {
                        return mzFormatNumber(data);
                    }},
                {mData: 'fcaReportCreatedBy', mRender: function(data) {
                        return data !== null ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'fcaReportTimeCreated'},    // 10
                {mData: null, mRender: function(data, type, row, meta) {
                        return '<a><i class="fas fa-file-pdf mr-1 lnkFcrPdf" id="lnkFcrPdf_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Click to view ' + row['fcaReportName'] + ' PDF"></i></a>'
                            + '<a><i class="fas fa-trash-alt lnkFcrDelete" id="lnkFcrDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Click to delete ' + row['fcaReportName'] + '"></i></a>';
                    }}
            ]
        });

        self.genTable();
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

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setModalFcaReportClass = function (_modalFcaReportClass) {
        modalFcaReportClass = _modalFcaReportClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}