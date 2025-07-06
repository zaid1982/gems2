function MainPpmSet () {

    const className = 'MainPpmSet';
    let self = this;
    let dtPsm; // Renamed from dtPgr
    let refStatus;
    let refUser;
    let refSite;
    let refContract;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refPpmGroup;
    let modalPpmSetClass; // New modal for PPM Set
    let modalPpmSetSelectAssetClass; // New modal for asset selection
    let userContractId = null;
    let contractId;

    this.init = function () {
        $('#ulPsmContract').hide(); // Renamed from ulPgrContract
        const userSiteId = mzGetUserInfoByParam('siteId');
        if (!mzIsRoleExist('1')) { // Role 1 is typically Admin
            for (const id in refContract) {
                if (refContract[id]['siteId'] === userSiteId) {
                    userContractId = parseInt(refContract[id]['contractId']);
                }
            }
            contractId = userContractId;
        } else {
            $('#ulPsmContract').show();
            userContractId = 20; // Default contract for admin
            contractId = userContractId;
            $.each(refContract, function (_contractId, _contract) {
                if (typeof _contract !== 'undefined') {
                    if (mzIsRoleExist('1,10')) { // Roles 1 and 10 (Admin and Super Admin?)
                        $('#divPsmContract').append('<a class="dropdown-item lnkPsmContract" href="#" id="lnkPsmContract_'+_contractId+'">'+_contract['contractName']+'</a>'); // Renamed
                    }
                }
            });
            $('#lnkPsmContract_'+contractId).addClass('active').addClass('text-white'); // Renamed
        }

        $('#lblPsmContractName').text(refContract[contractId]['contractName']); // Renamed
        const siteId = refContract[contractId]['siteId'];
        // Pass contractId and siteId to the new PPM Set modal
        modalPpmSetClass.setContractId(contractId);
        modalPpmSetClass.setSiteId(siteId);

        $('.lnkPsmContract').off('click').on('click', function () { // Renamed
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            try {
                if (linkIndex > 0) {
                    const selector = $('#lnkPsmContract_'+contractId); // Renamed
                    selector.removeClass('active').removeClass('text-white');
                    contractId = parseInt(linkId.substr(linkIndex + 1));
                    $('#lnkPsmContract_'+contractId).addClass('active').addClass('text-white'); // Renamed
                    $('#lblPsmContractName').text(refContract[contractId]['contractName']); // Renamed
                    self.genTable();
                    const siteId = refContract[contractId]['siteId'];
                    modalPpmSetClass.setContractId(contractId);
                    modalPpmSetClass.setSiteId(siteId);
                }
            } catch (e) {  toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        // Initialize DataTable for PPM Sets
        dtPsm = $('#dtPsm').DataTable({ // Renamed from dtPgr
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']], // Sort by PPM Set Name
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-12 col-sm-7 px-0 pb-2'B><'col-sm-5 d-none d-sm-block pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 7] }, // # and last column (actions)
                { className: 'text-center', targets: [0, 6, 7] },
                { className: 'text-right', targets: [5] }, // Total Assets
                { visible: false, targets: [2] }, // Hide Description by default (optional)
                { className: 'noVis', targets: [0, 7] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'three-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0', text:'<i class="fas fa-print"></i>', title:'GEMS - PPM Set List', titleAttr: 'Print', exportOptions: mzExportOpt}, // Updated Title
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0', text:'<i class="fas fa-copy"></i>', title:'GEMS - PPM Set List', titleAttr: 'Copy', exportOptions: mzExportOpt}, // Updated Title
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - PPM Set List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt}, // Updated Title
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - PPM Set List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}, // Updated Title
                { text: '<i class="fas fa-sync"></i>', className: 'btn btn-outline-purple btn-sm px-2 ml-0 mr-2', attr: { id: 'btnPsmRefresh' }, titleAttr: 'Refresh'}, // Renamed ID
                { text: '<i class="fas fa-plus mr-2"></i>Add New Set', className: 'btn btn-outline-blue btn-sm px-2 ml-0', attr: { id: 'btnPsmAdd' }, titleAttr: 'Add New Set'} // Renamed ID and Text
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('#btnPsmRefresh').off('click').on('click', function () { // Renamed ID
                    self.genTable();
                });
                $('#btnPsmAdd').off('click').on('click', function () { // Renamed ID
                    modalPpmSetClass.setClassFrom(self); // Pass self reference
                    modalPpmSetClass.add();
                });
                $('.lnkPsmEdit').off('click').on('click', function () { // Renamed class
                    const ppmSetId = mzGetLinkId($(this), dtPsm, 'ppmSetId'); // Get ppmSetId now
                    modalPpmSetClass.setClassFrom(self); // Pass self reference
                    modalPpmSetClass.edit(ppmSetId);
                });
                $('.lnkPsmDelete').off('click').on('click', function () { // New delete link
                    const ppmSetId = mzGetLinkId($(this), dtPsm, 'ppmSetId');
                    // modalConfirmDeleteClass_.setClassFrom(self); // Not sure if this class has a delete method
                    // modalConfirmDeleteClass_.delete(ppmSetId); // Need to implement delete for ppm_set
                    toastr['info']('Delete functionality for PPM Set not yet implemented!', _ALERT_TITLE_INFO);
                });
            },
            // Define columns for PPM Set data
            aoColumns: [
                { mData: null}, // #
                { mData: 'ppmSetName'}, // PPM Set Name
                { mData: 'ppmSetDesc'}, // Description
                { mData: 'assetTypeName', mRender: function (data, type, row) { // Asset Type Name for the set
                        return self.getRefName(row.assetTypeId, refAssetType, 'assetTypeName');
                    }},
                { mData: 'ppmGroupName', mRender: function (data, type, row) { // PPM Executor Group for the set
                        return self.getRefName(row.ppmGroupId, refPpmGroup, 'ppmGroupName');
                    }},
                { mData: 'totalAssets', width: '5%'}, // Total Assets in Set (calculated in backend)
                { mData: 'ppmSetStatus', mRender: function (data) { // Status of the PPM Set
                        return '<h6 class="mb-0"><span class="badge badge-pill '+refStatus[data]['statusColor']+'">'+refStatus[data]['statusDesc']+'</span></h6>';
                    }},
                { mData: null, bSortable: false, mRender: function (data, type, row, meta) {
                        let actions = '<a><i class="far fa-edit lnkPsmEdit" id="lnkPsmEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit/Info"></i></a>';
                        actions += '<a><i class="far fa-trash-alt lnkPsmDelete" id="lnkPsmDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>'; // Add delete button
                        // Link to manage assets within the set will be added later
                        return actions;
                    }}
            ]
        });

        self.genTable(); // Initial table load
    };

    // Helper to get reference name safely (like mzGetRefName, but explicit)
    this.getRefName = function (id, refArray, key) {
        if (id && refArray && refArray[id] && refArray[id][key]) {
            return refArray[id][key];
        }
        return '';
    };

    this.genTable = function () {
        ShowLoader(); setTimeout(function () {
            // Corrected API endpoint for listing PPM Sets
            // Add 'api/' prefix to the URL
            mzFetch('api/ppm.php?type=ppm_set_list', 'GET').then(res => { // ADDED 'api/' prefix
                dtPsm.clear().rows.add(res).draw();
            }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
        }, 200);
    };

    this.showMain = function () {
        $('.sectionPsmMain').show(); // Renamed class
    };

    this.hideMain = function () {
        $('.sectionPsmMain').hide(); // Renamed class
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
    // Setters for new modal classes
    this.setModalPpmSetClass = function (_modalPpmSetClass) {
        modalPpmSetClass = _modalPpmSetClass;
    };
    this.setModalPpmSetSelectAssetClass = function (_modalPpmSetSelectAssetClass) {
        modalPpmSetSelectAssetClass = _modalPpmSetSelectAssetClass;
    };
    // Removed setSectionPpmAssetClass as it's replaced by this MainPpmSet class
}