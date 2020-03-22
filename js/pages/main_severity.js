function MainSeverity() {

    const className = 'MainSeverity';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let oTableSeverity;
    let modalSeverityClass;

    this.init = function () {
        oTableSeverity =  $('#dtSvrSeverity').DataTable({
            bLengthChange: false,
            bFilter: true,
            //"aaSorting": [1, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableSeverity.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkSvrSeverityEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableSeverity.row(parseInt(rowId)).data();
                        modalSeverityClass.edit(currentRow['severityId'], rowId);
                    }
                });
                $('.lnkSvrSeverityDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableSeverity.row(parseInt(rowId)).data();
                        modalSeverityClass.deactivate(currentRow['severityId'], rowId);
                    }
                });
                $('.lnkSvrSeverityActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableSeverity.row(parseInt(rowId)).data();
                        modalSeverityClass.activate(currentRow['severityId'], rowId);
                    }
                });
                $('.lnkSvrSeverityDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableSeverity.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['severityId'], modalSeverityClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'severityName'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['severityStatus']]['statusColor']+' z-depth-2">'+refStatus[row['severityStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkSvrSeverityEdit" id="lnkSvrSeverityEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                            if (row['severityStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkSvrSeverityDeactivate" id="lnkSvrSeverityDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkSvrSeverityActivate" id="lnkSvrSeverityActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkSvrSeverityDelete" id="lnkSvrSeverityDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                            return label;
                        }
                    },
                    {mData: 'severityId', visible: false}
                ]
        });
        $("#dtSvrSeverity_filter").hide();
        $('#txtSvrSeveritySearch').on('keyup change', function () {
            oTableSeverity.search($(this).val()).draw();
        });

        let cntSeverity;
        let btnSeverityOpt = {
            exportOptions: {
                columns: [ 0, 1, 2],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntSeverity = 1;
                        }
                        if (column === 2) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntSeverity++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableSeverity, {
            buttons: [
                $.extend( true, {}, btnSeverityOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Severity List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnSeverityOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Severity List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnSeverityOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Severity List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtSvrSeverityExport'));

        $('#btnSvrSeverityAdd').on('click', function () {
            modalSeverityClass.add();
        });

        $('#btnDtSvrSeverityRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableSvr(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableSvr(0);
    };

    this.genTableSvr = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refSeverity = mzGetLocalRaw('gems_severity', versionLocal, [], 'asset_group');
        oTableSeverity.clear().rows.add(refSeverity).draw();
    };

    this.addTableSvr = function (_dataAdd) {
        oTableSeverity.row.add(_dataAdd).draw();
    };

    this.updateTableSvr = function (_dataEdit, _rowEdit) {
        const currentRow = oTableSeverity.row(_rowEdit).data();
        if (typeof _dataEdit['severityName'] !== 'undefined') {
            currentRow['severityName'] = _dataEdit['severityName'];
        }
        if (typeof _dataEdit['severityStatus'] !== 'undefined') {
            currentRow['severityStatus'] = _dataEdit['severityStatus'];
        }
        oTableSeverity.row(_rowEdit).data(currentRow).draw();
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

    this.setModalSeverityClass = function (_modalSeverityClass) {
        modalSeverityClass = _modalSeverityClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}