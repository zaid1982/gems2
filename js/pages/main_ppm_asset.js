function MainPpmAsset () {

    const className = 'MainPpmAsset';
    let self = this;
    let dtPgr;
    let refStatus;
    let refUser;
    let refContract;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refPpmGroup;
    let modalPpmAssetClass;
    let sectionPpmAssetClass;
    let userContractId = null;
    let contractId;

    this.init = function () {
        $('#ulPgrContract').hide();
        const userSiteId = mzGetUserInfoByParam('siteId');
        if (!mzIsRoleExist('1')) {
            for (const id in refContract) {
                if (refContract[id]['siteId'] === userSiteId) {
                    userContractId = parseInt(refContract[id]['contractId']);
                }
            }
            contractId = userContractId;
        } else {
            $('#ulPgrContract').show();
            userContractId = 20;
            contractId = userContractId;
            $.each(refContract, function (_contractId, _contract) {
                if (typeof _contract !== 'undefined') {
                    if (mzIsRoleExist('1,10')) {
                        $('#divPgrContract').append('<a class="dropdown-item lnkPgrContract" href="#" id="lnkPgrContract_'+_contractId+'">'+_contract['contractName']+'</a>');
                    }
                }
            });
            $('#lnkPgrContract_'+contractId).addClass('active').addClass('text-white');
        }
        $('#lblPgrContractName').text(refContract[contractId]['contractName']);

        $('.lnkPgrContract').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            try {
                if (linkIndex > 0) {
                    const selector = $('#lnkPgrContract_'+contractId);
                    selector.removeClass('active').removeClass('text-white');
                    contractId = parseInt(linkId.substr(linkIndex + 1));
                    $('#lnkPgrContract_'+contractId).addClass('active').addClass('text-white');
                    $('#lblPgrContractName').text(refContract[contractId]['contractName']);
                    self.genTable();
                }
            } catch (e) {  toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        dtPgr = $('#dtPgr').DataTable({
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
                { bSortable: false, targets: [0, 14] },
                { className: 'text-center', targets: [0, 8, 12, 13, 14] },
                { className: 'text-right', targets: [7, 10, 11] },
                { visible: false, targets: [2, 7, 8, 12] },
                { className: 'noVis', targets: [0, 14] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'three-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0', text:'<i class="fas fa-print"></i>', title:'GEMS - PPM Asset Group List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0', text:'<i class="fas fa-copy"></i>', title:'GEMS - PPM Asset Group List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - PPM Asset Group List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - PPM Asset Group List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt},
                { text: '<i class="fas fa-sync"></i>', className: 'btn btn-outline-purple btn-sm px-2 ml-0 mr-2', attr: { id: 'btnPgrPendingRefresh' }, titleAttr: 'Refresh'},
                { text: '<i class="fas fa-users-medical mr-2"></i>Add New Group', className: 'btn btn-outline-blue btn-sm px-2 ml-0', attr: { id: 'btnPgrAdd' }, titleAttr: 'Add New Group'}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('#btnPgrPendingRefresh').off('click').on('click', function () {
                    self.genTable();
                });
                $('#btnPgrAdd').off('click').on('click', function () {
                    modalPpmAssetClass.setClassFrom(self);
                    modalPpmAssetClass.add(contractId, refContract[contractId]['siteId']);
                });
                $('.lnkPgrEdit').off('click').on('click', function () {
                    const ppmId = mzGetLinkId($(this), dtPgr, 'ppmId');
                    sectionPpmAssetClass.load(ppmId);
                });
            },
            aoColumns: [
                { mData: null},
                { mData: 'ppmName'},
                { mData: 'ppmRemark'},
                { mData: 'assetTypeId', mRender: function (data){
                        const assetCategoryId = refAssetType[data]['assetCategoryId'];
                        const assetGroupId = refAssetCategory[assetCategoryId]['assetGroupId'];
                        return refAssetGroup[assetGroupId]['assetGroupName'];
                    }},
                { mData: 'assetTypeId', mRender: function (data){
                        const assetCategoryId = refAssetType[data]['assetCategoryId'];
                        return refAssetCategory[assetCategoryId]['assetCategoryName'];
                    }},
                { mData: 'assetTypeId', mRender: function (data){ return refAssetType[data]['assetTypeName']; }},  // 5
                { mData: 'ppmTaskNo'},
                { mData: 'ppmIssueNo'},
                { mData: 'ppmDateStart'},
                { mData: 'ppmFrequency'},
                { mData: 'totalAsset', width: '5%'}, // 10
                { mData: 'totalTask', width: '5%'},
                { mData: 'ppmTimeCreated'},
                { mData: 'ppmStatus', mRender: function (data) {
                        return '<h6 class="mb-0"><span class="badge badge-pill '+refStatus[data]['statusColor']+'">'+refStatus[data]['statusDesc']+'</span></h6>';
                    }},
                { mData: null, bSortable: false, mRender: function (data, type, row, meta) {
                        return '<a><i class="far fa-edit lnkPgrEdit" id="lnkPgrEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit/Info"></i></a>';
                    }}
            ]
        });

        self.genTable();
    };

    this.genTable = function () {
        ShowLoader(); setTimeout(function () { mzFetch('ppm_v3/listPpmGroup/'+contractId).then(res => {
            dtPgr.clear().rows.add(res).draw();
        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); }); }, 200);
    };

    this.showMain = function () {
        $('.sectionPgrMain').show();
    };

    this.hideMain = function () {
        $('.sectionPgrMain').hide();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
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

    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };

    this.setModalPpmAssetClass = function (_modalPpmAssetClasss) {
        modalPpmAssetClass = _modalPpmAssetClasss;
    };

    this.setSectionPpmAssetClass = function (_sectionPpmAssetClass) {
        sectionPpmAssetClass = _sectionPpmAssetClass;
    };
}