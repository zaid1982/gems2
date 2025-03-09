function SectionPpmAsset () {

    const className = 'SectionPpmAsset';
    let self = this;
    let classFrom;
    let refStatus;
    let refPpmGroup;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let modalConfirmDeleteClass;
    let modalPpmAssetClass;
    let dtSpgAsset;
    let dtSpgTask;
    let ppmId;
    let isUpdate = false;

    this.init = function () {
        self.hideSection();

        $('#btnSpgBack').on('click', function () {
            self.hideSection();
            $(window).scrollTop(0);
        });

        $('#btnSpgEdit').on('click', function () {
            modalPpmAssetClass.setClassFrom(self);
            modalPpmAssetClass.edit(ppmId);
        });

        $('#btnSpgRemove').on('click', function () {
            modalPpmAssetClass.setClassFrom(self);
            modalConfirmDeleteClass.delete(ppmId, modalPpmAssetClass);
        });

        dtSpgAsset = $('#dtSpgAsset').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-12 col-sm-7 px-0 pb-2'B><'col-sm-5 d-none d-sm-block pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 6] },
                { className: 'text-center', targets: [0, 1] },
                { visible: false, targets: [5] },
                { className: 'noVis', targets: [0, 6] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'three-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0', text:'<i class="fas fa-print"></i>', title:'GEMS - PPM Asset Group - Asset List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0', text:'<i class="fas fa-copy"></i>', title:'GEMS - PPM Asset Group - Asset List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - PPM Asset Group - Asset List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - PPM Asset Group - Asset List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt},
                { text: '<i class="fas fa-sync"></i>', className: 'btn btn-outline-purple btn-sm px-2 ml-0 mr-2', attr: { id: 'btnSpgAssetRefresh' }, titleAttr: 'Refresh'},
                { text: '<i class="fas fa-plus mr-2"></i>Add Asset', className: 'btn btn-outline-blue btn-sm px-2 ml-0', attr: { id: 'btnSpgAssetAdd' }, titleAttr: 'Add Asset'}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('#btnSpgAssetRefresh').off('click').on('click', function () {
                    self.genTableAsset();
                });
                $('#btnSpgAssetAdd').off('click').on('click', function () {

                });
            },
            aoColumns: [
                { mData: null},
                { mData: 'assetNo'},
                { mData: 'assetName'},
                { mData: 'assetSerialNo'},
                { mData: 'assetLocationCode'},
                { mData: 'assetDesc'},
                { mData: null, bSortable: false, mRender: function (data, type, row, meta) {
                        return '<a><i class="far fa-trash-alt lnkSpgAssetRemove" id="lnkSpgAssetRemove_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Remove"></i></a>';
                    }}
            ]
        });
    };

    this.load = function (_ppmId, _isRefresh) {
        ShowLoader(); setTimeout(function () {
            try {
                ppmId = _ppmId;
                const isRefresh = typeof _isRefresh !== 'undefined' && _isRefresh === true;
                isUpdate = isRefresh ? isUpdate : false;
                mzFetch('ppm_v3/'+ppmId).then(res => {
                    self.genTableAsset();
                    if (isRefresh) {

                    }
                    const ppm = res;
                    $('#pSpgName, #lblSpgName').text(mzNullToValue(ppm['ppmName'], '-'));
                    const assetTypeId = ppm['assetTypeId'];
                    $('#pSpgAssetType').text(mzNullToValue(assetTypeId, '-', 'assetTypeName', refAssetType));
                    const assetCategoryId = refAssetType[assetTypeId]['assetCategoryId'];
                    $('#pSpgAssetCategory').text(mzNullToValue(assetCategoryId, '-', 'assetCategoryName', refAssetCategory));
                    const assetGroupId = refAssetCategory[assetCategoryId]['assetGroupId'];
                    $('#pSpgAssetGroup').text(mzNullToValue(assetGroupId, '-', 'assetGroupName', refAssetGroup));
                    $('#pSpgTaskNo').text(mzNullToValue(ppm['ppmTaskNo'], '-'));
                    $('#pSpgIssueNo').text(mzNullToValue(ppm['ppmIssueNo'], '-'));
                    $('#pSpgFrequency').text(mzNullToValue(ppm['ppmFrequency'], '-'));
                    $('#pSpgDateStart').text(mzNullToValue(ppm['ppmDateStart'], '-', moment(ppm['ppmDateStart']).format('MMMM Do, YYYY')));
                    $('#pSpgTimeCreated').text(mzNullToValue(ppm['ppmTimeCreated'], '-', moment(ppm['ppmTimeCreated']).format('MMMM Do, YYYY, hh:mm:ss')));
                    $('#pSpgRemark').text(mzNullToValue(ppm['ppmRemark'], '-'));
                    $('#pSpgStatus').text(mzNullToValue(ppm['ppmStatus'], '-', 'statusDesc', refStatus));
                    classFrom.hideMain();
                    self.showSection();
                    window.scrollTo({top: 0, behavior: 'smooth'});
                }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        }, 200);
    };

    this.genTableAsset = function () {
        ShowLoader(); setTimeout(function () { mzFetch('ppm_asset/list/'+ppmId).then(res => {
            dtSpgAsset.clear().rows.add(res).draw();
        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); }); }, 200);
    };

    this.showSection = function () {
        $('.sectionPpmAsset').show();
    };

    this.hideSection = function () {
        $('.sectionPpmAsset').hide();
        classFrom.showMain();
        if (isUpdate) {
            classFrom.genTable();
        }
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
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

    this.setRefAssetType = function (_refAssetType) {
        refAssetType = _refAssetType;
    };

    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };

    this.setModalPpmAssetClass = function (_modalPpmAssetClasss) {
        modalPpmAssetClass = _modalPpmAssetClasss;
    };

    this.setContractName = function (_contractName) {
        $('#pSpgContract').text(_contractName);
    };

    this.setSiteName = function (_siteName) {
        $('#pSpgSite').text(_siteName);
    };

    this.setIsUpdate = function (_isUpdate) {
        isUpdate = _isUpdate;
    };
}