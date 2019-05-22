function MainJabatan() {

    const className = 'MainJabatan';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let oTableJabatan;
    let modalJabatanClass;

    this.init = function () {
        oTableJabatan =  $('#dtJbtJabatan').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [1, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableJabatan.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkJbtJabatanEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableJabatan.row(parseInt(rowId)).data();
                        modalJabatanClass.edit(currentRow['jabatanId'], rowId);
                    }
                });
                $('.lnkJbtJabatanDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableJabatan.row(parseInt(rowId)).data();
                        modalJabatanClass.deactivate(currentRow['jabatanId'], rowId);
                    }
                });
                $('.lnkJbtJabatanActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableJabatan.row(parseInt(rowId)).data();
                        modalJabatanClass.activate(currentRow['jabatanId'], rowId);
                    }
                });
                $('.lnkJbtJabatanDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableJabatan.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['jabatanId'], rowId, modalJabatanClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'jabatanDesc'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['jabatanStatus']]['statusColor']+' z-depth-2">'+refStatus[row['jabatanStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkJbtJabatanEdit" id="lnkJbtJabatanEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Kemaskini"></i></a>&nbsp;&nbsp;';
                            if (row['jabatanStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkJbtJabatanDeactivate" id="lnkJbtJabatanDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Nyahaktifkan"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkJbtJabatanActivate" id="lnkJbtJabatanActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Aktifkan"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkJbtJabatanDelete" id="lnkJbtJabatanDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Hapus"></i></a>';
                            return label;
                        }
                    },
                    {mData: 'jabatanId', visible: false}
                ]
        });
        $("#dtJbtJabatan_filter").hide();
        $('#txtJbtJabatanSearch').on('keyup change', function () {
            oTableJabatan.search($(this).val()).draw();
        });

        let cntJabatan;
        let btnJabatanOpt = {
            exportOptions: {
                columns: [ 0, 1, 2],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntJabatan = 1;
                        }
                        if (column === 2) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntJabatan++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableJabatan, {
            buttons: [
                $.extend( true, {}, btnJabatanOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'Sistem Permohonan Cuti - Jabatan',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnJabatanOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'Sistem Permohonan Cuti - Jabatan',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnJabatanOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'SSistem Permohonan Cuti - Jabatan',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtJbtJabatanExport'));

        $('#btnJbtJabatanAdd').on('click', function () {
            modalJabatanClass.add();
        });

        $('#btnDtJbtJabatanRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableJbt(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableJbt(0);
    };

    this.genTableJbt = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refJabatan = mzGetLocalRaw('jpdpl_jabatan', versionLocal);
        oTableJabatan.clear().rows.add(refJabatan).draw();
    };

    this.addTableJbt = function (_dataAdd) {
        oTableJabatan.row.add(_dataAdd).draw();
    };

    this.updateTableJbt = function (_dataEdit, _rowEdit) {
        const currentRow = oTableJabatan.row(_rowEdit).data();
        if (typeof _dataEdit['jabatanDesc'] !== 'undefined') {
            currentRow['jabatanDesc'] = _dataEdit['jabatanDesc'];
        }
        if (typeof _dataEdit['jabatanStatus'] !== 'undefined') {
            currentRow['jabatanStatus'] = _dataEdit['jabatanStatus'];
        }
        oTableJabatan.row(_rowEdit).data(currentRow).draw();
    };

    this.deleteTableJbt = function (_rowDelete) {
        oTableJabatan.row(_rowDelete).remove().draw();
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

    this.setModalJabatanClass = function (_modalJabatanClass) {
        modalJabatanClass = _modalJabatanClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}