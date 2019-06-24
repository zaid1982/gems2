function MainContract() {

    const className = 'MainContract';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let refClient;
    let refSite;
    let oTableContract;
    let modalContractClass;
    let sectionContractClass;

    this.init = function () {
        oTableContract =  $('#dtCcrContract').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [[1, 'asc'],[2, 'asc'],[3, 'asc']],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableContract.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkCcrContractEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableContract.row(parseInt(rowId)).data();
                        modalContractClass.edit(currentRow['contractId'], rowId);
                    }
                });
                $('.lnkCcrContractDetails').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableContract.row(parseInt(rowId)).data();
                        sectionContractClass.load(currentRow['contractId'], rowId);
                    }
                });
                $('.lnkCcrContractDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableContract.row(parseInt(rowId)).data();
                        modalContractClass.deactivate(currentRow['contractId'], rowId);
                    }
                });
                $('.lnkCcrContractActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableContract.row(parseInt(rowId)).data();
                        modalContractClass.activate(currentRow['contractId'], rowId);
                    }
                });
                $('.lnkCcrContractDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableContract.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['contractId'], modalContractClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'clientId', mRender: function (data){
                            return refClient[data]['clientName'];
                        }},
                    {mData: 'siteId', mRender: function (data){
                            return refSite[data]['siteName'];
                        }},
                    {mData: 'contractName'},
                    {mData: 'contractDesc'},
                    {mData: 'contractDateStart'},
                    {mData: 'contractDateEnd'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['contractStatus']]['statusColor']+' z-depth-2">'+refStatus[row['contractStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkCcrContractEdit" id="lnkCcrContractEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                            label += '<a><i class="fas fa-search-plus lnkCcrContractDetails" id="lnkCcrContractDetails_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Details"></i></a>&nbsp;&nbsp;';
                            if (row['contractStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkCcrContractDeactivate" id="lnkCcrContractDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkCcrContractActivate" id="lnkCcrContractActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkCcrContractDelete" id="lnkCcrContractDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                            return label;
                        }
                    },
                    {mData: 'contractId', visible: false}
                ]
        });
        $("#dtCcrContract_filter").hide();
        $('#txtCcrContractSearch').on('keyup change', function () {
            oTableContract.search($(this).val()).draw();
        });

        let cntContract;
        let btnContractOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6, 7],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntContract = 1;
                        }
                        if (column === 7) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntContract++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableContract, {
            buttons: [
                $.extend( true, {}, btnContractOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Contract List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnContractOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Contract List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnContractOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Contract List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtCcrContractExport'));

        $('#btnCcrContractAdd').on('click', function () {
            modalContractClass.add();
        });

        $('#btnDtCcrContractRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableCcr(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableCcr(0);
    };

    this.genTableCcr = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refContract = mzGetLocalRaw('gems_contract', versionLocal, [], 'contract');
        oTableContract.clear().rows.add(refContract).draw();
    };

    this.addTableCcr = function (_dataAdd) {
        oTableContract.row.add(_dataAdd).draw();
    };

    this.updateTableCcr = function (_dataEdit, _rowEdit) {
        const currentRow = oTableContract.row(_rowEdit).data();
        if (typeof _dataEdit['contractName'] !== 'undefined') {
            currentRow['contractName'] = _dataEdit['contractName'];
        }
        if (typeof _dataEdit['contractDesc'] !== 'undefined') {
            currentRow['contractDesc'] = _dataEdit['contractDesc'];
        }
        if (typeof _dataEdit['contractDateStart'] !== 'undefined') {
            currentRow['contractDateStart'] = _dataEdit['contractDateStart'];
        }
        if (typeof _dataEdit['contractDateEnd'] !== 'undefined') {
            currentRow['contractDateEnd'] = _dataEdit['contractDateEnd'];
        }
        if (typeof _dataEdit['contractStatus'] !== 'undefined') {
            currentRow['contractStatus'] = _dataEdit['contractStatus'];
        }
        oTableContract.row(_rowEdit).data(currentRow).draw();
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

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setModalContractClass = function (_modalContractClass) {
        modalContractClass = _modalContractClass;
    };

    this.setSectionContractClass = function (_sectionContractClass) {
        sectionContractClass = _sectionContractClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}