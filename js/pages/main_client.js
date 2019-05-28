function MainClient() {

    const className = 'MainClient';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let oTableClient;
    let modalClientClass;

    this.init = function () {
        oTableClient =  $('#dtClnClient').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [1, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableClient.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkClnClientEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableClient.row(parseInt(rowId)).data();
                        modalClientClass.edit(currentRow['clientId'], rowId);
                    }
                });
                $('.lnkClnClientDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableClient.row(parseInt(rowId)).data();
                        modalClientClass.deactivate(currentRow['clientId'], rowId);
                    }
                });
                $('.lnkClnClientActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableClient.row(parseInt(rowId)).data();
                        modalClientClass.activate(currentRow['clientId'], rowId);
                    }
                });
                $('.lnkClnClientDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableClient.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['clientId'], rowId, modalClientClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'clientName'},
                    {mData: 'clientDesc'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['clientStatus']]['statusColor']+' z-depth-2">'+refStatus[row['clientStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkClnClientEdit" id="lnkClnClientEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Kemaskini"></i></a>&nbsp;&nbsp;';
                            if (row['clientStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkClnClientDeactivate" id="lnkClnClientDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Nyahaktifkan"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkClnClientActivate" id="lnkClnClientActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Aktifkan"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkClnClientDelete" id="lnkClnClientDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Hapus"></i></a>';
                            return label;
                        }
                    },
                    {mData: 'clientId', visible: false}
                ]
        });
        $("#dtClnClient_filter").hide();
        $('#txtClnClientSearch').on('keyup change', function () {
            oTableClient.search($(this).val()).draw();
        });

        let cntClient;
        let btnClientOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntClient = 1;
                        }
                        if (column === 3) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntClient++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableClient, {
            buttons: [
                $.extend( true, {}, btnClientOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Client List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnClientOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Client List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnClientOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Client List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtClnClientExport'));

        $('#btnClnClientAdd').on('click', function () {
            modalClientClass.add();
        });

        $('#btnDtClnClientRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableCln(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableCln(0);
    };

    this.genTableCln = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refClient = mzGetLocalRaw('gems_client', versionLocal, [], 'client');
        oTableClient.clear().rows.add(refClient).draw();
    };

    this.addTableCln = function (_dataAdd) {
        oTableClient.row.add(_dataAdd).draw();
    };

    this.updateTableCln = function (_dataEdit, _rowEdit) {
        const currentRow = oTableClient.row(_rowEdit).data();
        if (typeof _dataEdit['clientName'] !== 'undefined') {
            currentRow['clientName'] = _dataEdit['clientName'];
        }
        if (typeof _dataEdit['clientDesc'] !== 'undefined') {
            currentRow['clientDesc'] = _dataEdit['clientDesc'];
        }
        if (typeof _dataEdit['clientStatus'] !== 'undefined') {
            currentRow['clientStatus'] = _dataEdit['clientStatus'];
        }
        oTableClient.row(_rowEdit).data(currentRow).draw();
    };

    this.deleteTableCln = function (_rowDelete) {
        oTableClient.row(_rowDelete).remove().draw();
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

    this.setModalClientClass = function (_modalClientClass) {
        modalClientClass = _modalClientClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}