function MainFcaZone () {

    const className = 'MainFcaZone';
    let self = this;
    let oTableFcz;
    let refStatus;
    let refSite;
    let isAuditor = false;
    let modalFcaZoneClass;

    this.init = function () {
        isAuditor = mzIsRoleExist('22');

        oTableFcz = $('#dtFczData').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc'],[2, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-5 col-sm-7 px-0 pb-2'B><'col-7 col-sm-5 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0, 3] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'one-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFczHide', text:'<i class="fas fa-print"></i>', title:'GEMS - FCA Zone List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFczHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - FCA Zone List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnFczHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - FCA Zone List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-3 btnFczHide', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - FCA Zone List', titleAttr: 'PDF', exportOptions: mzExportOpt},
                { text: '<i class="fas fa-plus mr-2"></i>Add New Zone', className: 'btn btn-outline-red btn-sm px-2 ml-0', attr: { id: 'btnFczAdd' }, titleAttr: 'Add New FCA Zone'}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('#btnFczAdd').off('click').on('click', function () {
                    if (!isAuditor) {
                        toastr['error']('You don\'t have permission as FCA Auditor role to perform this task!', _ALERT_TITLE_ERROR);
                        return false;
                    }
                    modalFcaZoneClass.add();
                });
                if ($('#divFczCard').width() < 546) {
                    $(this).DataTable().column(3).visible(false);
                    $('.btnFczHide').hide();
                }
            },
            aoColumns: [
                {mData: null},
                {mData: 'siteId', mRender: function(data) {
                        return refSite[data]['siteName'];
                    }},
                {mData: 'fcaZoneName'},
                {mData: 'fcaZoneStatus', mRender: function(data) {
                        return refStatus[data]['statusDesc'];
                    }}
            ]
        });
        let oTableFczTbody = $('#dtFczData tbody');
        oTableFczTbody.delegate('tr', 'click', function () {
            if (!isAuditor) {
                toastr['error']('You don\'t have permission as FCA Auditor role to perform this task!', _ALERT_TITLE_ERROR);
                return false;
            }
            const data = $('#dtFczData').DataTable().row(this).data();
            modalFcaZoneClass.edit(data['fcaZoneId']);
        });
        oTableFczTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtFczData').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', 'Click to edit '+data['fcaZoneName']+' details');
            $('[data-toggle="tooltip"]').tooltip();
        });

        self.genTable();
    };

    this.genTable = function () {
        const dataDb = mzAjaxRequest2('fca_zone', 'GET');
        oTableFcz.clear().rows.add(dataDb).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setModalFcaZoneClass = function (_modalFcaZoneClass) {
        modalFcaZoneClass = _modalFcaZoneClass;
    };
}