function MainPpmAsset () {

    const className = 'MainPpmAsset';
    let self = this;
    let dtPgr;
    let refStatus;
    let refUser;
    let refSite;
    let refContract;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refPpmGroup;
    let modalPpmAssetClass;
    let sectionPpmAssetClass;
    let userContractId = null;
    let contractId;

    function statusBadge(statusId, type) {
        const rec = refStatus && refStatus[statusId];
        const label = rec ? rec['statusDesc'] : String(statusId);
        if (type !== 'display') {
            return label;
        }
        const colorMap = {
            'badge-primary': 'info',
            'badge-info': 'info',
            'badge-success': 'success',
            'badge-danger': 'danger',
            'badge-warning': 'warning',
            'badge-secondary': 'secondary'
        };
        return GemsUI.badge(colorMap[rec && rec['statusColor']] || 'secondary', GemsUI.escape(label));
    }

    function applyContractContext() {
        $('#lblPgrContractName').text(refContract[contractId]['contractName']);
        const siteId = refContract[contractId]['siteId'];
        modalPpmAssetClass.setContractId(contractId);
        modalPpmAssetClass.setSiteId(siteId);
        sectionPpmAssetClass.setContractName(refContract[contractId]['contractName']);
        sectionPpmAssetClass.setSiteName(refSite[siteId]['siteName']);
    }

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
            const contractRows = [];
            $.each(refContract, function (_contractId, _contract) {
                if (typeof _contract !== 'undefined') {
                    if (mzIsRoleExist('1,10')) {
                        const row = $.extend({}, _contract);
                        if (row['contractId'] === undefined) {
                            row['contractId'] = String(_contractId);
                        }
                        contractRows.push(row);
                    }
                }
            });
            GemsUI.fillSelect('optPgrContract', contractRows, 'contractId', function (row) {
                return row['contractName'] || '';
            }, null, contractId);
            $('#optPgrContract').val(contractId);
        }

        applyContractContext();

        $('#optPgrContract').on('change', function () {
            try {
                const nextId = parseInt($(this).val(), 10);
                if (!nextId) {
                    return;
                }
                contractId = nextId;
                applyContractContext();
                self.genTable();
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
        });

        dtPgr = $('#dtPgr').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            ordering: true,
            language: GemsUI.dtEmpty('fa-users', 'No PPM asset groups for this contract.'),
            pageLength: 10,
            autoWidth: false,
            dom: GemsUI.dtDom,
            columnDefs: [
                { bSortable: false, targets: [0, 14] },
                { className: 'text-center', targets: [0, 8, 12, 13, 14] },
                { className: 'text-right', targets: [7, 10, 11] },
                { visible: false, targets: [2, 7, 8, 12] },
                { className: 'noVis', targets: [0, 14] }
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
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
                { mData: 'assetTypeId', mRender: function (data){ return refAssetType[data]['assetTypeName']; }},
                { mData: 'ppmTaskNo'},
                { mData: 'ppmIssueNo'},
                { mData: 'ppmDateStart'},
                { mData: 'ppmFrequency'},
                { mData: 'totalAsset', width: '5%'},
                { mData: 'totalTask', width: '5%'},
                { mData: 'ppmTimeCreated'},
                { mData: 'ppmStatus', mRender: function (data, type) {
                        return statusBadge(data, type);
                    }},
                { mData: null, bSortable: false, mRender: function (data, type, row, meta) {
                        if (type !== 'display') {
                            return '';
                        }
                        return GemsUI.actionBtn({id: 'lnkPgrEdit_' + meta.row, cls: 'lnkPgrEdit', icon: 'far fa-edit', title: 'Edit/Info'});
                    }}
            ]
        });
        GemsUI.bindDtTooltips('#dtPgr');
        new $.fn.dataTable.Buttons(dtPgr, {
            buttons: GemsUI.dtButtons('GEMS - PPM Asset Group List')
        }).container().appendTo($('#btnDtPgrExport'));

        $('#btnPgrPendingRefresh').on('click', function () {
            self.genTable();
        });
        $('#btnPgrAdd').on('click', function () {
            modalPpmAssetClass.setClassFrom(self);
            modalPpmAssetClass.add();
        });
        $('#dtPgr').on('click', '.lnkPgrEdit', function () {
            const ppmId = mzGetLinkId($(this), dtPgr, 'ppmId');
            sectionPpmAssetClass.load(ppmId);
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

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
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
