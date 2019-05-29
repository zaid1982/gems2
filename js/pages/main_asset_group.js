function MainAssetGroup() {

    const className = 'MainAssetGroup';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let oTableAssetGroup;
    let modalAssetGroupClass;

    this.init = function () {
        oTableAssetGroup =  $('#dtAgrAssetGroup').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [1, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableAssetGroup.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkAgrAssetGroupEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetGroup.row(parseInt(rowId)).data();
                        modalAssetGroupClass.edit(currentRow['assetGroupId'], rowId);
                    }
                });
                $('.lnkAgrAssetGroupDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetGroup.row(parseInt(rowId)).data();
                        modalAssetGroupClass.deactivate(currentRow['assetGroupId'], rowId);
                    }
                });
                $('.lnkAgrAssetGroupActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetGroup.row(parseInt(rowId)).data();
                        modalAssetGroupClass.activate(currentRow['assetGroupId'], rowId);
                    }
                });
                $('.lnkAgrAssetGroupDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAssetGroup.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['assetGroupId'], rowId, modalAssetGroupClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'assetGroupName'},
                    {mData: 'assetGroupDesc'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['assetGroupStatus']]['statusColor']+' z-depth-2">'+refStatus[row['assetGroupStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkAgrAssetGroupEdit" id="lnkAgrAssetGroupEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Kemaskini"></i></a>&nbsp;&nbsp;';
                            if (row['assetGroupStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkAgrAssetGroupDeactivate" id="lnkAgrAssetGroupDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Nyahaktifkan"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkAgrAssetGroupActivate" id="lnkAgrAssetGroupActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Aktifkan"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkAgrAssetGroupDelete" id="lnkAgrAssetGroupDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Hapus"></i></a>';
                            //label += '<div class="dropdown dropright float-right">';
                            //label += '<a class="" type="" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="far fa-plus-square"></i></a>';
                            //label += '<div class="dropdown-menu dropdown-primary">';
                            //label += '<a class="dropdown-item p-1" href="#">Add new asset with same Asset Group</a>';
                            //label += '<a class="dropdown-item p-1" href="#">Add new asset with same Asset Category</a>';
                            //label += '<a class="dropdown-item p-1" href="#">Add new asset with same Asset Type</a>';
                            //label += '<a class="dropdown-item p-1" href="#">Add new asset with same Brand</a>';
                            //label += '<a class="dropdown-item p-1" href="#">Add new asset with same Model</a>';
                            //label += '</div></div>';
                            return label;
                        }
                    },
                    {mData: 'assetGroupId', visible: false}
                ]
        });
        $("#dtAgrAssetGroup_filter").hide();
        $('#txtAgrAssetGroupSearch').on('keyup change', function () {
            oTableAssetGroup.search($(this).val()).draw();
        });

        let cntAssetGroup;
        let btnAssetGroupOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntAssetGroup = 1;
                        }
                        if (column === 3) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntAssetGroup++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableAssetGroup, {
            buttons: [
                $.extend( true, {}, btnAssetGroupOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Asset Group List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnAssetGroupOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Asset Group List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnAssetGroupOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Asset Group List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtAgrAssetGroupExport'));

        $('#btnAgrAssetGroupAdd').on('click', function () {
            modalAssetGroupClass.add();
        });

        $('#btnDtAgrAssetGroupRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAgr(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableAgr(0);
    };

    this.genTableAgr = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refAssetGroup = mzGetLocalRaw('gems_assetGroup', versionLocal, [], 'asset_group');
        oTableAssetGroup.clear().rows.add(refAssetGroup).draw();
    };

    this.addTableAgr = function (_dataAdd) {
        oTableAssetGroup.row.add(_dataAdd).draw();
    };

    this.updateTableAgr = function (_dataEdit, _rowEdit) {
        const currentRow = oTableAssetGroup.row(_rowEdit).data();
        if (typeof _dataEdit['assetGroupName'] !== 'undefined') {
            currentRow['assetGroupName'] = _dataEdit['assetGroupName'];
        }
        if (typeof _dataEdit['assetGroupDesc'] !== 'undefined') {
            currentRow['assetGroupDesc'] = _dataEdit['assetGroupDesc'];
        }
        if (typeof _dataEdit['assetGroupStatus'] !== 'undefined') {
            currentRow['assetGroupStatus'] = _dataEdit['assetGroupStatus'];
        }
        oTableAssetGroup.row(_rowEdit).data(currentRow).draw();
    };

    this.deleteTableAgr = function (_rowDelete) {
        oTableAssetGroup.row(_rowDelete).remove().draw();
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

    this.setModalAssetGroupClass = function (_modalAssetGroupClass) {
        modalAssetGroupClass = _modalAssetGroupClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}