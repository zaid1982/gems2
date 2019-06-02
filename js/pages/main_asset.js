function MainAsset() {

    const className = 'MainAsset';
    let self = this;
    let modalConfirmDeleteClass;
    let refStatus;
    let oTableAsset;
    let modalAssetClass;

    this.init = function () {
        oTableAsset =  $('#dtAszAsset').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [1, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableAsset.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkAszAssetEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAsset.row(parseInt(rowId)).data();
                        modalAssetClass.edit(currentRow['assetId'], rowId);
                    }
                });
                $('.lnkAszAssetDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAsset.row(parseInt(rowId)).data();
                        modalAssetClass.deactivate(currentRow['assetId'], rowId);
                    }
                });
                $('.lnkAszAssetActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAsset.row(parseInt(rowId)).data();
                        modalAssetClass.activate(currentRow['assetId'], rowId);
                    }
                });
                $('.lnkAszAssetDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAsset.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['assetId'], rowId, modalAssetClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'assetName'},
                    {mData: 'assetCode'},
                    {mData: null},
                    {mData: null},
                    {mData: null},
                    {mData: null},
                    {mData: null},
                    {mData: null},
                    {mData: 'assetLocationCode'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['assetStatus']]['statusColor']+' z-depth-2">'+refStatus[row['assetStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkAszAssetEdit" id="lnkAszAssetEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Kemaskini"></i></a>&nbsp;&nbsp;';
                            if (row['assetStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkAszAssetDeactivate" id="lnkAszAssetDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Nyahaktifkan"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkAszAssetActivate" id="lnkAszAssetActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Aktifkan"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkAszAssetDelete" id="lnkAszAssetDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Hapus"></i></a>';
                            return label;
                        }
                    },
                    {mData: 'assetId', visible: false}
                ]
        });
        $("#dtAszAsset_filter").hide();
        $('#txtAszAssetSearch').on('keyup change', function () {
            oTableAsset.search($(this).val()).draw();
        });

        let cntAsset;
        let btnAssetOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntAsset = 1;
                        }
                        if (column === 3) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntAsset++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableAsset, {
            buttons: [
                $.extend( true, {}, btnAssetOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Asset List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnAssetOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Asset List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnAssetOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Asset List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtAszAssetExport'));

        $('#btnAszAssetAdd').on('click', function () {
            modalAssetClass.add();
        });

        $('#btnDtAszAssetRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAsz();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableAsz();
    };

    this.genTableAsz = function () {
        //mzAjaxRequest('asset.php', 'GET', {Reportid: '1', 'Cache-Control': 'no-cache, no-transform'}, 'userManagementClass_.displayChart()');
        const dataAsset = mzAjaxRequest('asset.php', 'GET');
        oTableAsset.clear().rows.add(dataAsset).draw();
    };

    this.addTableAsz = function (_dataAdd) {
        oTableAsset.row.add(_dataAdd).draw();
    };

    this.updateTableAsz = function (_dataEdit, _rowEdit) {
        const currentRow = oTableAsset.row(_rowEdit).data();
        if (typeof _dataEdit['assetName'] !== 'undefined') {
            currentRow['assetName'] = _dataEdit['assetName'];
        }
        if (typeof _dataEdit['assetDesc'] !== 'undefined') {
            currentRow['assetDesc'] = _dataEdit['assetDesc'];
        }
        if (typeof _dataEdit['assetStatus'] !== 'undefined') {
            currentRow['assetStatus'] = _dataEdit['assetStatus'];
        }
        oTableAsset.row(_rowEdit).data(currentRow).draw();
    };

    this.deleteTableAsz = function (_rowDelete) {
        oTableAsset.row(_rowDelete).remove().draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setModalAssetClass = function (_modalAssetClass) {
        modalAssetClass = _modalAssetClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}