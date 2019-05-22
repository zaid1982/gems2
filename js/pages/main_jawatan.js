function MainJawatan() {

    const className = 'MainJawatan';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let oTableJawatan;
    let modalJawatanClass;

    this.init = function () {
        oTableJawatan =  $('#dtJwtJawatan').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [1, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableJawatan.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkJwtJawatanEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableJawatan.row(parseInt(rowId)).data();
                        modalJawatanClass.edit(currentRow['jawatanId'], rowId);
                    }
                });
                $('.lnkJwtJawatanDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableJawatan.row(parseInt(rowId)).data();
                        modalJawatanClass.deactivate(currentRow['jawatanId'], rowId);
                    }
                });
                $('.lnkJwtJawatanActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableJawatan.row(parseInt(rowId)).data();
                        modalJawatanClass.activate(currentRow['jawatanId'], rowId);
                    }
                });
                $('.lnkJwtJawatanDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableJawatan.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['jawatanId'], rowId, modalJawatanClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'jawatanDesc'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['jawatanStatus']]['statusColor']+' z-depth-2">'+refStatus[row['jawatanStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkJwtJawatanEdit" id="lnkJwtJawatanEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Kemaskini"></i></a>&nbsp;&nbsp;';
                            if (row['jawatanStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkJwtJawatanDeactivate" id="lnkJwtJawatanDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Nyahaktifkan"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkJwtJawatanActivate" id="lnkJwtJawatanActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Aktifkan"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkJwtJawatanDelete" id="lnkJwtJawatanDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Hapus"></i></a>';
                            return label;
                        }
                    },
                    {mData: 'jawatanId', visible: false}
                ]
        });
        $("#dtJwtJawatan_filter").hide();
        $('#txtJwtJawatanSearch').on('keyup change', function () {
            oTableJawatan.search($(this).val()).draw();
        });

        let cntJawatan;
        let btnJawatanOpt = {
            exportOptions: {
                columns: [ 0, 1, 2],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntJawatan = 1;
                        }
                        if (column === 2) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntJawatan++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableJawatan, {
            buttons: [
                $.extend( true, {}, btnJawatanOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'Sistem Permohonan Cuti - Jawatan',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnJawatanOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'Sistem Permohonan Cuti - Jawatan',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnJawatanOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'SSistem Permohonan Cuti - Jawatan',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtJwtJawatanExport'));

        $('#btnJwtJawatanAdd').on('click', function () {
            modalJawatanClass.add();
        });

        $('#btnDtJwtJawatanRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableJwt(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableJwt(0);
    };

    this.genTableJwt = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refJawatan = mzGetLocalRaw('jpdpl_jawatan', versionLocal);
        oTableJawatan.clear().rows.add(refJawatan).draw();
    };

    this.addTableJwt = function (_dataAdd) {
        oTableJawatan.row.add(_dataAdd).draw();
    };

    this.updateTableJwt = function (_dataEdit, _rowEdit) {
        const currentRow = oTableJawatan.row(_rowEdit).data();
        if (typeof _dataEdit['jawatanDesc'] !== 'undefined') {
            currentRow['jawatanDesc'] = _dataEdit['jawatanDesc'];
        }
        if (typeof _dataEdit['jawatanStatus'] !== 'undefined') {
            currentRow['jawatanStatus'] = _dataEdit['jawatanStatus'];
        }
        oTableJawatan.row(_rowEdit).data(currentRow).draw();
    };

    this.deleteTableJwt = function (_rowDelete) {
        oTableJawatan.row(_rowDelete).remove().draw();
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

    this.setModalJawatanClass = function (_modalJawatanClass) {
        modalJawatanClass = _modalJawatanClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}