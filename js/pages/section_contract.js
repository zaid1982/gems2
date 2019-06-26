function SectionContract() {

    const className = "SectionContract";
    let self = this;
    let modalConfirmDeleteClass;
    let contractId = '';
    let rowRefresh = '';
    let classFrom;
    let refStatus;
    let refClient;
    let refSite;
    let refAssetGroup;
    let refUser;
    let oTableLocationCode;
    let oTableLocationUser;
    let modalLocationCodeClass;
    let modalContractUserClass;

    this.init = function () {
        $('.sectionContract').hide();

        $('#btnSctBack').on('click', function () {
            $('.sectionContract').hide();
            if (classFrom.getClassName() === 'MainContract') {
                $('.sectionCcrMain').show();
            }
            $(window).scrollTop(0);
        });

        oTableLocationCode = $('#dtSctLocationCode').DataTable({
            bLengthChange: false,
            bFilter: true,
            autoWidth: false,
            bPaginate: false,
            bInfo : false,
            aaSorting: [1, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableLocationCode.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkSctLocationCodeEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableLocationCode.row(parseInt(rowId)).data();
                        modalLocationCodeClass.edit(currentRow['locationCodeId'], currentRow['contractId'], rowId);
                    }
                });
                $('.lnkSctLocationCodeDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableLocationCode.row(parseInt(rowId)).data();
                        modalLocationCodeClass.deactivate(currentRow['locationCodeId'], rowId);
                    }
                });
                $('.lnkSctLocationCodeActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableLocationCode.row(parseInt(rowId)).data();
                        modalLocationCodeClass.activate(currentRow['locationCodeId'], rowId);
                    }
                });
                $('.lnkSctLocationCodeDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableLocationCode.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['locationCodeId'], modalLocationCodeClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'locationCodeName', bSortable: false},
                    {mData: null, bSortable: false,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['locationCodeStatus']]['statusColor']+' z-depth-2">'+refStatus[row['locationCodeStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkSctLocationCodeEdit" id="lnkSctLocationCodeEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                            if (row['locationCodeStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkSctLocationCodeDeactivate" id="lnkSctLocationCodeDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkSctLocationCodeActivate" id="lnkSctLocationCodeActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkSctLocationCodeDelete" id="lnkSctLocationCodeDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                            return label;
                        }
                    }
                ]
        });
        $("#dtSctLocationCode_filter").hide();

        let cntLocationCode;
        let btnLocationCodeOpt = {
            exportOptions: {
                columns: [ 0, 1, 2],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntLocationCode = 1;
                        }
                        if (column === 2) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntLocationCode++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableLocationCode, {
            buttons: [
                $.extend( true, {}, btnLocationCodeOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Location Code List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnLocationCodeOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Location Code List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnLocationCodeOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Location Code List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtSctLocationCodeExport'));

        $('#btnSctLocationCodeAdd').on('click', function () {
            modalLocationCodeClass.add(contractId);
        });

        $('#btnDtSctLocationCodeRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableLocationCode();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        oTableLocationUser = $('#dtSctLocationUser').DataTable({
            bLengthChange: false,
            bFilter: true,
            autoWidth: false,
            bPaginate: false,
            bInfo : false,
            aaSorting: [1, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableLocationUser.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'locationCodeName', bSortable: false},
                    {mData: 'userId', bSortable: false, mRender: function (data){
                            return refUser[data]['userFullName'];
                        }},
                    {mData: 'assetGroupId', bSortable: false, mRender: function (data){
                            return refAssetGroup[data]['assetGroupName'];
                        }},
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-trash-alt lnkSctLocationUserDelete" id="lnkSctLocationUserDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                            return label;
                        }
                    }
                ]
        });
        $("#dtSctLocationUser_filter").hide();

        let cntLocationUser;
        let btnLocationUserOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntLocationUser = 1;
                        }
                        return column === 0 ? cntLocationUser++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableLocationUser, {
            buttons: [
                $.extend( true, {}, btnLocationUserOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Technician Assigned List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnLocationUserOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Technician Assigned List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnLocationUserOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Technician Assigned List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtSctLocationUserExport'));

        $('#btnSctLocationUserAdd').on('click', function () {
            modalContractUserClass.add(contractId);
        });

        $('#btnDtSctLocationUserRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableLocationUser();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.load = function (_contractId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                contractId = _contractId;
                rowRefresh = _rowRefresh;

                const dataMcr = mzAjaxRequest('contract.php?contractId='+contractId, 'GET');
                const siteId = dataMcr['siteId'];
                const clientId = refSite[siteId]['clientId'];
                const contractStatus = dataMcr['contractStatus'];
                mzSetFieldValue('SctClientName', refClient[clientId]['clientName'], 'text');
                mzSetFieldValue('SctSiteName', refSite[siteId]['siteName'], 'text');
                mzSetFieldValue('SctContractName', dataMcr['contractName'], 'text');
                mzSetFieldValue('SctContractDesc', dataMcr['contractDesc'], 'textarea');
                mzSetFieldValue('SctContractDateStart', dataMcr['contractDateStart'], 'text');
                mzSetFieldValue('SctContractDateEnd', dataMcr['contractDateEnd'], 'text');
                mzSetFieldValue('SctContractStatus', refStatus[contractStatus]['statusDesc'], 'text');

                self.genTableLocationCode();
                self.genTableLocationUser();

                const versionLocal = mzGetDataVersion();
                const refContract = mzGetLocalArray('gems_contract', versionLocal, 'contractId', [], 'contract');
                modalLocationCodeClass.setRefContract(refContract);
                modalContractUserClass.setRefContract(refContract);

                $('.sectionContract').show();
                if (classFrom.getClassName() === 'MainContract') {
                    $('.sectionCcrMain').hide();
                }
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.genTableLocationCode = function () {
        const dataLocationCode = mzAjaxRequest('location_code.php?contractId='+contractId, 'GET');
        oTableLocationCode.clear().rows.add(dataLocationCode).draw();
    };

    this.genTableLocationUser = function () {
        const dataLocationUser = mzAjaxRequest('contract_user.php?contractId='+contractId, 'GET');
        oTableLocationUser.clear().rows.add(dataLocationUser).draw();
    };

    this.addTableLocationCode = function (_dataAdd) {
        oTableLocationCode.row.add(_dataAdd).draw();
    };

    this.updateTableLocationCode = function (_dataEdit, _rowEdit) {
        const currentRow = oTableLocationCode.row(_rowEdit).data();
        if (typeof _dataEdit['locationCodeName'] !== 'undefined') {
            currentRow['locationCodeName'] = _dataEdit['locationCodeName'];
        }
        if (typeof _dataEdit['locationCodeStatus'] !== 'undefined') {
            currentRow['locationCodeStatus'] = _dataEdit['locationCodeStatus'];
        }
        oTableLocationCode.row(_rowEdit).data(currentRow).draw();
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

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setModalLocationCodeClass = function (_modalLocationCodeClass) {
        modalLocationCodeClass = _modalLocationCodeClass;
    };

    this.setModalContractUserClass = function (_modalContractUserClass) {
        modalContractUserClass = _modalContractUserClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}