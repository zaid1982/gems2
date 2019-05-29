function MainAssetType() {

    const className = 'MainAssetType';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let refAssetGroup;
    let refAssetCategory;
    let oTableAssetType;
    let modalAssetTypeClass;

    this.init = function () {
        oTableAssetType =  $('#dtAtyAssetType').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [[1, 'asc'],[2, 'asc'],[3, 'asc']],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableAssetType.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkAtyAssetTypeEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetType.row(parseInt(rowId)).data();
                        modalAssetTypeClass.edit(currentRow['assetTypeId'], rowId);
                    }
                });
                $('.lnkAtyAssetTypeDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetType.row(parseInt(rowId)).data();
                        modalAssetTypeClass.deactivate(currentRow['assetTypeId'], rowId);
                    }
                });
                $('.lnkAtyAssetTypeAtyivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetType.row(parseInt(rowId)).data();
                        modalAssetTypeClass.activate(currentRow['assetTypeId'], rowId);
                    }
                });
                $('.lnkAtyAssetTypeDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetType.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['assetTypeId'], rowId, modalAssetTypeClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'assetGroupId', mRender: function (data){
                            return refAssetGroup[data]['assetGroupName'];
                        }},
                    {mData: 'assetCategoryId', mRender: function (data){
                            return refAssetCategory[data]['assetCategoryName'];
                        }},
                    {mData: 'assetTypeName'},
                    {mData: 'assetTypeDesc'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['assetTypeStatus']]['statusColor']+' z-depth-2">'+refStatus[row['assetTypeStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkAtyAssetTypeEdit" id="lnkAtyAssetTypeEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Kemaskini"></i></a>&nbsp;&nbsp;';
                            if (row['assetTypeStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkAtyAssetTypeDeactivate" id="lnkAtyAssetTypeDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Nyahaktifkan"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkAtyAssetTypeAtyivate" id="lnkAtyAssetTypeAtyivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Aktifkan"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkAtyAssetTypeDelete" id="lnkAtyAssetTypeDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Hapus"></i></a>';
                            return label;
                        }
                    },
                    {mData: 'assetTypeId', visible: false}
                ]
        });
        $("#dtAtyAssetType_filter").hide();
        $('#txtAtyAssetTypeSearch').on('keyup change', function () {
            oTableAssetType.search($(this).val()).draw();
        });

        let cntAssetType;
        let btnAssetTypeOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntAssetType = 1;
                        }
                        if (column === 5) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntAssetType++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableAssetType, {
            buttons: [
                $.extend( true, {}, btnAssetTypeOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Asset Type List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnAssetTypeOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Asset Type List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnAssetTypeOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Asset Type List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtAtyAssetTypeExport'));

        $('#btnAtyAssetTypeAdd').on('click', function () {
            modalAssetTypeClass.add();
        });

        $('#btnDtAtyAssetTypeRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAty(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableAty(0);
    };

    this.genTableAty = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refAssetType = mzGetLocalRaw('gems_assetType', versionLocal, [], 'asset_type');
        oTableAssetType.clear().rows.add(refAssetType).draw();
    };

    this.addTableAty = function (_dataAdd) {
        oTableAssetType.row.add(_dataAdd).draw();
    };

    this.updateTableAty = function (_dataEdit, _rowEdit) {
        const currentRow = oTableAssetType.row(_rowEdit).data();
        if (typeof _dataEdit['assetTypeName'] !== 'undefined') {
            currentRow['assetTypeName'] = _dataEdit['assetTypeName'];
        }
        if (typeof _dataEdit['assetTypeDesc'] !== 'undefined') {
            currentRow['assetTypeDesc'] = _dataEdit['assetTypeDesc'];
        }
        if (typeof _dataEdit['assetTypeStatus'] !== 'undefined') {
            currentRow['assetTypeStatus'] = _dataEdit['assetTypeStatus'];
        }
        oTableAssetType.row(_rowEdit).data(currentRow).draw();
    };

    this.deleteTableAty = function (_rowDelete) {
        oTableAssetType.row(_rowDelete).remove().draw();
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

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefAssetCategory = function (_refAssetCategory) {
        refAssetCategory = _refAssetCategory;
    };

    this.setModalAssetTypeClass = function (_modalAssetTypeClass) {
        modalAssetTypeClass = _modalAssetTypeClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}