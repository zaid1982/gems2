function MainFcaTask () {

    const className = 'MainFcaTask';
    let self = this;
    let oTableFctObserve;
    let isAuditor = false;
    let modalFcaAddClass;
    let refStatus;
    let refUser;
    let refSite;
    let refAssetGroup;
    let refDefectCategory;

    this.init = function () {
        isAuditor = mzIsRoleExist('21');
        
        let exportOptFctObserve = Object.assign({}, mzExportOpt);
        exportOptFctObserve['columns'] = [0, 1, 2, 3, 4, 5, 6, 7, 10, 13, 14];
        oTableFctObserve = $('#dtFctObserve').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 50,
            autoWidth: false,
            dom: "<'row'<'col-5 col-sm-7 px-0 pb-2'B><'col-7 col-sm-5 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0, 1, 4, 13, 14] },
                { visible: false, targets: [7, 8, 9, 10, 11, 12, 13] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'four-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFctObserveHide', text:'<i class="fas fa-print"></i>', title:'GEMS - FCA Submitted Observation List', titleAttr: 'Print', exportOptions: exportOptFctObserve},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - FCA Submitted Observation List', titleAttr: 'Copy', exportOptions: exportOptFctObserve},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - FCA Submitted Observation List', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-3 btnFctObserveHide', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - FCA Submitted Observation List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOptFctObserve},
                { text: '<i class="fas fa-plus mr-2"></i>Add New FCA', className: 'btn btn-outline-red btn-sm px-2 ml-0', attr: { id: 'btnFctAddNew' }, titleAttr: 'Add New FCA Observation'}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('#btnFctAddNew').off('click').on('click', function () {
                    if (!isAuditor) {
                        toastr['error']('You don\'t have permission as FCA Auditor role to perform this task!', _ALERT_TITLE_ERROR);
                        return false;
                    }
                    modalFcaAddClass.add();
                });
                if ($('#divFctObserveHeader').width() < 546) {
                    $(this).DataTable().column(2).visible(false);
                    $(this).DataTable().column(3).visible(false);
                    $(this).DataTable().column(4).visible(false);
                    $(this).DataTable().column(6).visible(false);
                    $('.btnFctObserveHide').hide();
                }
            },
            aoColumns: [
                {mData: null},
                {mData: 'fcaTaskNo'},
                {mData: 'siteId', mRender: function(data) {
                        return refSite[data]['siteCode'];
                    }},
                {mData: 'assetGroupId', mRender: function(data) {
                        return refAssetGroup[data]['assetGroupName'];
                    }},
                {mData: 'fcaTaskAssetNo'},
                {mData: 'fcaTaskAssetEvaluated'},  <!--5-->
                {mData: 'fcaTaskDefectItem'},
                {mData: 'fcaDefectCategoryId', mRender: function(data) {
                        return refDefectCategory[data]['fcaDefectCategoryName'];
                    }},
                {mData: 'fcaTaskBlock'},
                {mData: 'fcaTaskLevel'},
                {mData: 'fcaTaskArea'},  <!--10-->
                {mData: 'fcaTaskCreatedBy', mRender: function(data) {
                        return data !== null ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'fcaTaskValidateBy', mRender: function(data) {
                        return data !== null ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'fcaTaskTimeCreated'},
                {mData: 'fcaTaskStatus', mRender: function(data) {
                        return refStatus[data]['statusAction'];
                    }}
            ]
        });
        let oTableFctObserveTbody = $('#dtFctObserve tbody');
        oTableFctObserveTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtFctObserve').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', 'Click to see '+data['fcaTaskNo']+' details');
            $('[data-toggle="tooltip"]').tooltip();
        });

        self.genTable(1);
    };

    this.genTable = function (_tableId) {
        if (_tableId === 1) {
            const dataDb = mzAjaxRequest2('fca_task/audit', 'GET');
            oTableFctObserve.clear().rows.add(dataDb).draw();
        }
    };

    this.showMain = function () {
        $('.sectionFctMain').show();
    };

    this.hideMain = function () {
        $('.sectionFctMain').hide();
    };

    this.setModalFcaAddClass = function (_modalFcaAddClass) {
        modalFcaAddClass = _modalFcaAddClass;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefDefectCategory = function (_refDefectCategory) {
        refDefectCategory = _refDefectCategory;
    };
}