function MainAssetType() {

    const className = 'MainAssetType';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetBrand;
    let oTableAssetType;
    let modalAssetTypeClass;
    let oTableAssetModel;
    let modalAssetModelClass;
    let assetTypeId;
    let rowIdModel;

    this.init = function () {
        $('.sectionAtyModel').hide();

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
                $('.lnkAtyAssetTypeActivate').off('click').on('click', function () {
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
                $('.lnkAtyAssetTypeModel').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetType.row(parseInt(rowId)).data();
                        ShowLoader();
                        setTimeout(function () {
                            try {
                                self.genTableAtyModel(0, currentRow['assetTypeId'], rowId);
                            } catch (e) {
                                toastr['error'](e.message, _ALERT_TITLE_ERROR);
                            }
                            HideLoader();
                        }, 300);
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
                    {mData: 'totalModel',
                        mRender: function (data, type, row, meta) {
                            let label;
                            if (data === 0) {
                                label = '<a class="trigger red lighten-1 text-white">'+data+'</a>';
                            } else {
                                label = '<a class="trigger cyan accent-4 text-white lnkAtyAssetTypeModel" id="lnkAtyAssetTypeTotal_' + meta.row + '">'+data+'</a>';
                            }
                            return label;
                        }
                    },
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['assetTypeStatus']]['statusColor']+' z-depth-2">'+refStatus[row['assetTypeStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkAtyAssetTypeEdit" id="lnkAtyAssetTypeEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                            if (row['totalModel'] !== 0) {
                                label += '<a><i class="fas fa-list-ul lnkAtyAssetTypeModel" id="lnkAtyAssetTypeModel_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Brand list"></i></a>&nbsp;&nbsp;';
                            }
                            if (row['assetTypeStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkAtyAssetTypeDeactivate" id="lnkAtyAssetTypeDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkAtyAssetTypeActivate" id="lnkAtyAssetTypeActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkAtyAssetTypeDelete" id="lnkAtyAssetTypeDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
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
                columns: [ 0, 1, 2, 3, 4, 5, 6],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntAssetType = 1;
                        }
                        if (column === 5) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</a>','');
                        }
                        else if (column === 6) {
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

        oTableAssetModel =  $('#dtAtyAssetModel').DataTable({
            bLengthChange: false,
            bFilter: true,
            autoWidth: false,
            "aaSorting": [[1, 'asc'],[2, 'asc']],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableAssetModel.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkAtyAssetModelEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetModel.row(parseInt(rowId)).data();
                        modalAssetModelClass.edit(currentRow['assetModelId'], rowId);
                    }
                });
                $('.lnkAtyAssetModelDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetModel.row(parseInt(rowId)).data();
                        modalAssetModelClass.deactivate(currentRow['assetModelId'], rowId);
                    }
                });
                $('.lnkAtyAssetModelActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetModel.row(parseInt(rowId)).data();
                        modalAssetModelClass.activate(currentRow['assetModelId'], rowId);
                    }
                });
                $('.lnkAtyAssetModelDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetModel.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['assetModelId'], rowId, modalAssetModelClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'assetBrandId', mRender: function (data){
                            return refAssetBrand[data]['assetBrandName'];
                        }},
                    {mData: 'assetModelName'},
                    {mData: 'assetModelDesc'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="trigger badge badge-pill '+refStatus[row['assetModelStatus']]['statusColor']+' z-depth-2">'+refStatus[row['assetModelStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkAtyAssetModelEdit" id="lnkAtyAssetModelEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                            if (row['assetModelStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkAtyAssetModelDeactivate" id="lnkAtyAssetModelDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkAtyAssetModelActivate" id="lnkAtyAssetModelActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkAtyAssetModelDelete" id="lnkAtyAssetModelDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                            return label;
                        }
                    },
                    {mData: 'assetModelId', visible: false}
                ]
        });
        $("#dtAtyAssetModel_filter").hide();
        $('#txtAtyAssetModelSearch').on('keyup change', function () {
            oTableAssetModel.search($(this).val()).draw();
        });

        let cntAssetModel;
        let btnAssetModelOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntAssetModel = 1;
                        }
                        if (column === 4) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntAssetModel++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableAssetModel, {
            buttons: [
                $.extend( true, {}, btnAssetModelOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Asset Model List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnAssetModelOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Asset Model List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnAssetModelOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Asset Model List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtAtyAssetModelExport'));

        $('#btnAtyAssetModelAdd').on('click', function () {
            modalAssetModelClass.add();
        });

        $('#btnDtAtyAssetModelRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAtyModel(1, assetTypeId, rowIdModel);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        self.genTableAty(0);
    };

    this.genTableAty = function (_type) {
        $('.sectionAtyModel').hide();
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

    this.genTableAtyModel = function (_type, _assetTypeId, _rowIdModel) {
        assetTypeId = _assetTypeId;
        rowIdModel = _rowIdModel;
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refAssetModel = mzGetLocalRaw('gems_assetModel', versionLocal, {assetTypeId:assetTypeId}, 'asset_model');
        oTableAssetModel.clear().rows.add(refAssetModel).draw();
        modalAssetModelClass.setAssetTypeId(assetTypeId);
        $('.sectionAtyModel').show();
    };

    this.addTableAtyModel = function (_dataAdd) {
        oTableAssetModel.row.add(_dataAdd).draw();
        const currentRow = oTableAssetType.row(rowIdModel).data();
        currentRow['totalModel'] = parseInt(currentRow['totalModel']) + 1;
        oTableAssetType.row(rowIdModel).data(currentRow).draw();
    };

    this.updateTableAtyModel = function (_dataEdit, _rowEdit) {
        const currentRow = oTableAssetModel.row(_rowEdit).data();
        if (typeof _dataEdit['assetModelName'] !== 'undefined') {
            currentRow['assetModelName'] = _dataEdit['assetModelName'];
        }
        if (typeof _dataEdit['assetModelDesc'] !== 'undefined') {
            currentRow['assetModelDesc'] = _dataEdit['assetModelDesc'];
        }
        if (typeof _dataEdit['assetModelStatus'] !== 'undefined') {
            currentRow['assetModelStatus'] = _dataEdit['assetModelStatus'];
        }
        oTableAssetModel.row(_rowEdit).data(currentRow).draw();
    };

    this.deleteTableAtyModel = function (_rowDelete) {
        oTableAssetModel.row(_rowDelete).remove().draw();
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

    this.setRefAssetBrand = function (_refAssetBrand) {
        refAssetBrand = _refAssetBrand;
    };

    this.setModalAssetTypeClass = function (_modalAssetTypeClass) {
        modalAssetTypeClass = _modalAssetTypeClass;
    };

    this.setModalAssetModelClass = function (_modalAssetModelClass) {
        modalAssetModelClass = _modalAssetModelClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}