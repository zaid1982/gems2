function MainPurchaseRequest () {

    const className = 'MainPurchaseRequest';
    let self = this;
    let refUser;
    let sectionPrClass;
    let oTablePcrNew;

    this.init = function () {
        let exportOptPcrNew = Object.assign({}, mzExportOpt);
        exportOptPcrNew['columns'] = [0, 1, 2, 3, 4, 5, 6, 7];
        oTablePcrNew = $('#dtPcrNew').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [6, 'desc'],
            language: _DATATABLE_LANGUAGE,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkPcrNewEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTablePcrNew.row(parseInt(rowId)).data();
                        modalDrawingClass.edit(currentRow['drawingId']);
                    }
                });
            },
            dom: "<'row'<'col-5 px-0'B><'col-7 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { visible: false, targets: [] },
                { className: 'text-right', targets: [5] },
                { className: 'text-center', targets: [0, 2, 3, 7] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - New PR Request List', titleAttr: 'Print', exportOptions: exportOptPcrNew},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - New PR Request List', titleAttr: 'Copy', exportOptions: exportOptPcrNew},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - New PR Request List', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - New PR Request List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOptPcrNew}
            ],
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'prRequestNo'},
                {mData: null , mRender: function (data){
                    return 'PPNS';
                }},
                {mData: 'prQuotationNo'},
                {mData: 'supplierId'},
                {mData: 'prTotalCost'},
                {mData: 'prRequestedBy', mRender: function (data){
                        return data !== '' ? refUser[data]['userFullName'] : '';
                    }},
                {mData: 'prTimeRequested'}
            ]
        });
        let oTablePcrNewTbody = $('#dtPcrNew tbody');
        oTablePcrNewTbody.delegate('tr', 'click', function (evt) {
            const data = $('#dtPcrNew').DataTable().row(this).data();
            ShowLoader();
            setTimeout(function () {
                try {
                    // const cell = $(evt.target).closest('td'); //if (cell.index() === 1) {
                    sectionPrClass.load(false, data['prId']);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });
        oTablePcrNewTbody.delegate('tr', 'mouseenter', function (evt) {
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
        });

        self.genTablePcrNew();
    };

    this.showMain = function () {
        $('.sectionPcrMain').show();
    };

    this.hideMain = function () {
        $('.sectionPcrMain').hide();
    };

    this.genTablePcrNew = function () {
        const dataDb = mzAjaxRequest2('pr/new_request', 'GET');
        oTablePcrNew.clear().rows.add(dataDb).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setSectionPrClass = function (_sectionPrClass) {
        sectionPrClass = _sectionPrClass;
    };
}