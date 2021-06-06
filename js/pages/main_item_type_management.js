function MainItemTypeManagement () {

    const className = 'MainItemTypeManagement';
    let self = this;
    let oTableItm;
    let modalConfirmDeleteClass;
    let modalItemTypeClass;
    let refStatus;
    let refAssetGroup;

    this.init = function () {
        let exportOpt = Object.assign({}, mzExportOpt);
        exportOpt['columns'] = [0, 1, 2, 3, 4];

        oTableItm = $('#dtItmData').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc'], [3, 'asc']],
            language: _DATATABLE_LANGUAGE,
            pageLength: 50,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkItmEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableItm.row(parseInt(rowId)).data();
                        modalItemTypeClass.edit(currentRow['itemTypeId']);
                    }
                });
                $('.lnkItmDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableItm.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['itemTypeId'], modalItemTypeClass);
                    }
                });
            },
            dom: "<'row'<'col-5 px-0'B><'col-7 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 5] },
                { className: 'text-center', targets: [0, 4, 5] },
                { className: 'text-right', targets: [3] },
                { className: 'noVis', targets: [0, 5] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Item Type List', titleAttr: 'Print', exportOptions: exportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Item Type List', titleAttr: 'Copy', exportOptions: exportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Item Type List', titleAttr: 'Excel', exportOptions: exportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Item Type List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOpt}
            ],
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'assetGroupId', mRender: function (data){
                        return data !== '' ? refAssetGroup[data]['assetGroupName'] : '';
                    }},
                {mData: 'itemTypeDesc'},
                {mData: 'itemTypeTurn'},
                {mData: 'itemTypeStatus', mRender: function (data){
                        return data !== '' ? refStatus[data]['statusDesc'] : '';
                    }},
                {mData: null, width: '55px',
                    mRender: function (data, type, row, meta) {
                        let label = '<a><i class="fas fa-edit lnkItmEdit" id="lnkItmEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;';
                        label += '<a><i class="fas fa-trash-alt lnkItmDelete" id="lnkItmDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                        return label;
                    }}
            ]
        });

        if ($('#dtItmData').width() < 520) {
            oTableItm.column(3).visible(false);
            oTableItm.column(4).visible(false);
        }

        $('#btnDtItmDataRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTable();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnItmDataAdd').on('click', function () {
            modalItemTypeClass.add();
        });

        self.genTable();
    };

    this.genTable = function () {
        const dataDb = mzAjaxRequest2('item_type', 'GET');
        oTableItm.clear().rows.add(dataDb).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };

    this.setModalItemTypeClass = function (_modalItemTypeClass) {
        modalItemTypeClass = _modalItemTypeClass;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };
}