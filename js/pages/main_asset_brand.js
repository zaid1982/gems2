function MainAssetBrand() {

    const className = 'MainAssetBrand';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let oTableAssetBrand;
    let modalAssetBrandClass;

    this.init = function () {
        oTableAssetBrand =  $('#dtAbrAssetBrand').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [1, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableAssetBrand.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkAbrAssetBrandEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetBrand.row(parseInt(rowId)).data();
                        modalAssetBrandClass.edit(currentRow['assetBrandId'], rowId);
                    }
                });
                $('.lnkAbrAssetBrandDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetBrand.row(parseInt(rowId)).data();
                        modalAssetBrandClass.deactivate(currentRow['assetBrandId'], rowId);
                    }
                });
                $('.lnkAbrAssetBrandActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetBrand.row(parseInt(rowId)).data();
                        modalAssetBrandClass.activate(currentRow['assetBrandId'], rowId);
                    }
                });
                $('.lnkAbrAssetBrandDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetBrand.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['assetBrandId'], rowId, modalAssetBrandClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'assetBrandName'},
                    {mData: 'assetBrandDesc'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['assetBrandStatus']]['statusColor']+' z-depth-2">'+refStatus[row['assetBrandStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkAbrAssetBrandEdit" id="lnkAbrAssetBrandEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Kemaskini"></i></a>&nbsp;&nbsp;';
                            if (row['assetBrandStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkAbrAssetBrandDeactivate" id="lnkAbrAssetBrandDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Nyahaktifkan"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkAbrAssetBrandActivate" id="lnkAbrAssetBrandActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Aktifkan"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkAbrAssetBrandDelete" id="lnkAbrAssetBrandDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Hapus"></i></a>';
                            return label;
                        }
                    },
                    {mData: 'assetBrandId', visible: false}
                ]
        });
        $("#dtAbrAssetBrand_filter").hide();
        $('#txtAbrAssetBrandSearch').on('keyup change', function () {
            oTableAssetBrand.search($(this).val()).draw();
        });

        let cntAssetBrand;
        let btnAssetBrandOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntAssetBrand = 1;
                        }
                        if (column === 3) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntAssetBrand++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableAssetBrand, {
            buttons: [
                $.extend( true, {}, btnAssetBrandOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Asset Brand List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnAssetBrandOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Asset Brand List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnAssetBrandOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Asset Brand List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtAbrAssetBrandExport'));

        $('#btnAbrAssetBrandAdd').on('click', function () {
            modalAssetBrandClass.add();
        });

        $('#btnDtAbrAssetBrandRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAbr(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableAbr(0);
    };

    this.genTableAbr = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refAssetBrand = mzGetLocalRaw('gems_assetBrand', versionLocal, [], 'asset_brand');
        oTableAssetBrand.clear().rows.add(refAssetBrand).draw();
    };

    this.addTableAbr = function (_dataAdd) {
        oTableAssetBrand.row.add(_dataAdd).draw();
    };

    this.updateTableAbr = function (_dataEdit, _rowEdit) {
        const currentRow = oTableAssetBrand.row(_rowEdit).data();
        if (typeof _dataEdit['assetBrandName'] !== 'undefined') {
            currentRow['assetBrandName'] = _dataEdit['assetBrandName'];
        }
        if (typeof _dataEdit['assetBrandDesc'] !== 'undefined') {
            currentRow['assetBrandDesc'] = _dataEdit['assetBrandDesc'];
        }
        if (typeof _dataEdit['assetBrandStatus'] !== 'undefined') {
            currentRow['assetBrandStatus'] = _dataEdit['assetBrandStatus'];
        }
        oTableAssetBrand.row(_rowEdit).data(currentRow).draw();
    };

    this.deleteTableAbr = function (_rowDelete) {
        oTableAssetBrand.row(_rowDelete).remove().draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setVersionLocal = function (_versionLocal) {
        versionLocal = _versionLocal;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setModalAssetBrandClass = function (_modalAssetBrandClass) {
        modalAssetBrandClass = _modalAssetBrandClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}