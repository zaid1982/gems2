function MainAsset() {

    const className = 'MainAsset';
    let self = this;
    let modalConfirmDeleteClass;
    let refStatus;
    let refContract;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refAssetBrand;
    let refAssetModel;
    let oTableAsset;
    let sectionAssetClass;
    let contractId;

    this.init = function () {
        mzOption('optAszContractId', refContract, 'Choose Contract', 'contractId', 'contractDesc', {}, 'required');
        mzOption('optAszGroupId', refAssetGroup, 'All Asset Group', 'assetGroupId', 'assetGroupDesc', {});

        for(let contract of refContract) {
            if (typeof contract !== 'undefined') {
                contractId = contract['contractId'];
                mzSetFieldValue('AszContractId', contractId, 'select', 'Contract');
                break;
            }
        }

        oTableAsset =  $('#dtAszAsset').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [2, 'asc'],
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
                        sectionAssetClass.edit(currentRow['assetId'], rowId);
                    }
                });
                $('.lnkAszAssetDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAsset.row(parseInt(rowId)).data();
                        sectionAssetClass.deactivate(currentRow['assetId'], rowId);
                    }
                });
                $('.lnkAszAssetActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAsset.row(parseInt(rowId)).data();
                        sectionAssetClass.activate(currentRow['assetId'], rowId);
                    }
                });
                $('.lnkAszAssetDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAsset.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['assetId'], rowId, sectionAssetClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'assetName'},
                    {mData: 'assetNo'},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetGroupId'] !== '' ? refAssetGroup[row['assetGroupId']]['assetGroupName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetCategoryId'] !== '' ? refAssetCategory[row['assetCategoryId']]['assetCategoryName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetTypeId'] !== '' ? refAssetType[row['assetTypeId']]['assetTypeName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetBrandId'] !== '' ? refAssetBrand[row['assetBrandId']]['assetBrandName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetModelId'] !== '' ? refAssetModel[row['assetModelId']]['assetModelName'] : '';
                        }},
                    {mData: 'assetCapacity'},
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
                            } else if (row['assetStatus'] === '2') {
                                label += '<a><i class="fas fa-toggle-on lnkAszAssetActivate" id="lnkAszAssetActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Aktifkan"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkAszAssetDelete" id="lnkAszAssetDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Hapus"></i></a>';
                            return label;
                        }
                    },
                    {mData: 'assetId', visible: false},
                    {mData: 'assetGroupId', visible: false},
                    {mData: 'assetCategoryId', visible: false},
                    {mData: 'assetTypeId', visible: false},
                    {mData: 'assetStatus', visible: false}
                ]
        });
        $("#dtAszAsset_filter").hide();
        $('#txtAszAssetSearch').on('keyup change', function () {
            oTableAsset.search($(this).val()).draw();
        });
        $('#linkAszAll').on('click', function () {
            oTableAsset.column(16).search('').draw();
        });
        $('#linkAsz1').on('click', function () {
            oTableAsset.column(16).search('1', false, true, false).draw();
        });
        $('#linkAsz2').on('click', function () {
            oTableAsset.column(16).search('2', false, true, false).draw();
        });
        $('#linkAsz5').on('click', function () {
            oTableAsset.column(16).search('5', false, true, false).draw();
        });

        $('#optAszGroupId').on('change', function () {
            mzOption('optAszCategoryId', refAssetCategory, 'All Asset Category', 'assetCategoryId', 'assetCategoryName', {assetGroupId: $(this).val()});
            mzOption('optAszTypeId', refAssetType, 'All Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: '0'});
            oTableAsset.column(13).search($(this).val(), false, true, false).draw();
        });

        $('#optAszCategoryId').on('change', function () {
            mzOption('optAszTypeId', refAssetType, 'All Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: $(this).val()});
            oTableAsset.column(14).search($(this).val(), false, true, false).draw();
        });

        $('#optAszTypeId').on('change', function () {
            mzOption('optAszTypeId', refAssetType, 'All Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: $(this).val()});
            oTableAsset.column(15).search($(this).val(), false, true, false).draw();
        });

        let cntAsset;
        let btnAssetOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntAsset = 1;
                        }
                        if (column === 10) {
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
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtAszAssetExport'));

        oTableAsset.column(1).visible(false);
        oTableAsset.column(8).visible(false);
        oTableAsset.column(9).visible(false);

        $('#optAszColumns').on('change', function () {
            for (let i=1; i<=10; i++) {
                oTableAsset.column(i).visible(false);
            }
            const selectedColumns = $(this).val();
            $.each(selectedColumns, function (n, u) {
                oTableAsset.column(parseInt(u)).visible(true);
            });
        });

        $('#optAszContractId').on('change', function () {
            $('#optAszGroupId').val(null);
            mzOption('optAszCategoryId', refAssetCategory, 'All Asset Category', 'assetCategoryId', 'assetCategoryName', {assetGroupId: '0'});
            mzOption('optAszTypeId', refAssetType, 'All Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: '0'});
            oTableAsset.column(13).search($(this).val(), false, true, false).draw();
            oTableAsset.column(14).search($(this).val(), false, true, false).draw();
            oTableAsset.column(15).search($(this).val(), false, true, false).draw();
        });

        $('#btnAszAssetAdd').on('click', function () {
            sectionAssetClass.add(contractId);
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

        let totalAll = 0;
        let total1 = 0;
        let total2 = 0;
        let total5 = 0;

        $.each(dataAsset, function (n, u) {
            switch (u['assetStatus']) {
                case '1':
                    total1++;
                    totalAll++;
                    break;
                case '2':
                    total2++;
                    totalAll++;
                    break;
                case '5':
                    total5++;
                    totalAll++;
                    break;
                default:
                    totalAll++;
            }
        });

        $('#linkAszAll').html('<span class="bullet blue z-depth-2"></span> All Status <span class="badge blue float-right">'+mzFormatNumber(totalAll)+'</span>');
        $('#linkAsz1').html('<span class="bullet yellow z-depth-2"></span> '+refStatus[1]['statusDesc']+' <span class="badge yellow float-right">'+mzFormatNumber(total1)+'</span>');
        $('#linkAsz2').html('<span class="bullet light-green z-depth-2"></span> '+refStatus[2]['statusDesc']+' <span class="badge light-green float-right">'+mzFormatNumber(total2)+'</span>');
        $('#linkAsz5').html('<span class="bullet blue-grey accent-2 z-depth-2"></span> '+refStatus[5]['statusDesc']+' <span class="badge blue-grey accent-2 float-right">'+mzFormatNumber(total5)+'</span>');

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

    this.setRefContract = function (_refContract) {
        refContract = _refContract;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefAssetCategory = function (_refAssetCategory) {
        refAssetCategory = _refAssetCategory;
    };

    this.setRefAssetType = function (_refAssetType) {
        refAssetType = _refAssetType;
    };

    this.setRefAssetBrand = function (_refAssetBrand) {
        refAssetBrand = _refAssetBrand;
    };

    this.setRefAssetModel = function (_refAssetModel) {
        refAssetModel = _refAssetModel;
    };

    this.setSectionAssetClass = function (_sectionAssetClass) {
        sectionAssetClass = _sectionAssetClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}