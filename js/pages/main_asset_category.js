function MainAssetCategory() {

    const className = 'MainAssetCategory';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let refAssetGroup;
    let oTableAssetCategory;
    let modalAssetCategoryClass;

    this.init = function () {
        oTableAssetCategory =  $('#dtActAssetCategory').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [[1, 'asc'],[2, 'asc']],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableAssetCategory.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkActAssetCategoryEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetCategory.row(parseInt(rowId)).data();
                        modalAssetCategoryClass.edit(currentRow['assetCategoryId'], rowId);
                    }
                });
                $('.lnkActAssetCategoryDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetCategory.row(parseInt(rowId)).data();
                        modalAssetCategoryClass.deactivate(currentRow['assetCategoryId'], rowId);
                    }
                });
                $('.lnkActAssetCategoryActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetCategory.row(parseInt(rowId)).data();
                        modalAssetCategoryClass.activate(currentRow['assetCategoryId'], rowId);
                    }
                });
                $('.lnkActAssetCategoryDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetCategory.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['assetCategoryId'], modalAssetCategoryClass);
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
                    {mData: 'assetCategoryName'},
                    {mData: 'assetCategoryDesc'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['assetCategoryStatus']]['statusColor']+' z-depth-2">'+refStatus[row['assetCategoryStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkActAssetCategoryEdit" id="lnkActAssetCategoryEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                            if (row['assetCategoryStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkActAssetCategoryDeactivate" id="lnkActAssetCategoryDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkActAssetCategoryActivate" id="lnkActAssetCategoryActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkActAssetCategoryDelete" id="lnkActAssetCategoryDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                            return label;
                        }
                    },
                    {mData: 'assetCategoryId', visible: false}
                ]
        });
        $("#dtActAssetCategory_filter").hide();
        $('#txtActAssetCategorySearch').on('keyup change', function () {
            oTableAssetCategory.search($(this).val()).draw();
        });

        let cntAssetCategory;
        let btnAssetCategoryOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntAssetCategory = 1;
                        }
                        if (column === 4) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntAssetCategory++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableAssetCategory, {
            buttons: [
                $.extend( true, {}, btnAssetCategoryOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Asset Category List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnAssetCategoryOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Asset Category List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnAssetCategoryOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Asset Category List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtActAssetCategoryExport'));

        $('#btnActAssetCategoryAdd').on('click', function () {
            modalAssetCategoryClass.add();
        });

        $('#btnDtActAssetCategoryRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAct(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableAct(0);
    };

    this.genTableAct = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refAssetCategory = mzGetLocalRaw('gems_assetCategory', versionLocal, [], 'asset_category');
        oTableAssetCategory.clear().rows.add(refAssetCategory).draw();
    };

    this.addTableAct = function (_dataAdd) {
        oTableAssetCategory.row.add(_dataAdd).draw();
    };

    this.updateTableAct = function (_dataEdit, _rowEdit) {
        const currentRow = oTableAssetCategory.row(_rowEdit).data();
        if (typeof _dataEdit['assetCategoryName'] !== 'undefined') {
            currentRow['assetCategoryName'] = _dataEdit['assetCategoryName'];
        }
        if (typeof _dataEdit['assetCategoryDesc'] !== 'undefined') {
            currentRow['assetCategoryDesc'] = _dataEdit['assetCategoryDesc'];
        }
        if (typeof _dataEdit['assetCategoryStatus'] !== 'undefined') {
            currentRow['assetCategoryStatus'] = _dataEdit['assetCategoryStatus'];
        }
        oTableAssetCategory.row(_rowEdit).data(currentRow).draw();
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

    this.setModalAssetCategoryClass = function (_modalAssetCategoryClass) {
        modalAssetCategoryClass = _modalAssetCategoryClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}