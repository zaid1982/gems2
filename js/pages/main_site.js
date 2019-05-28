function MainSite() {

    const className = 'MainSite';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let refClient;
    let oTableSite;
    let modalSiteClass;

    this.init = function () {
        oTableSite =  $('#dtSteSite').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [[1, 'asc'],[2, 'asc']],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableSite.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkSteSiteEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableSite.row(parseInt(rowId)).data();
                        modalSiteClass.edit(currentRow['siteId'], rowId);
                    }
                });
                $('.lnkSteSiteDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableSite.row(parseInt(rowId)).data();
                        modalSiteClass.deactivate(currentRow['siteId'], rowId);
                    }
                });
                $('.lnkSteSiteActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableSite.row(parseInt(rowId)).data();
                        modalSiteClass.activate(currentRow['siteId'], rowId);
                    }
                });
                $('.lnkSteSiteDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableSite.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['siteId'], rowId, modalSiteClass);
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
                    {mData: 'siteName'},
                    {mData: 'siteDesc'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['siteStatus']]['statusColor']+' z-depth-2">'+refStatus[row['siteStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkSteSiteEdit" id="lnkSteSiteEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Kemaskini"></i></a>&nbsp;&nbsp;';
                            if (row['siteStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkSteSiteDeactivate" id="lnkSteSiteDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Nyahaktifkan"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkSteSiteActivate" id="lnkSteSiteActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Aktifkan"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkSteSiteDelete" id="lnkSteSiteDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Hapus"></i></a>';
                            return label;
                        }
                    },
                    {mData: 'siteId', visible: false}
                ]
        });
        $("#dtSteSite_filter").hide();
        $('#txtSteSiteSearch').on('keyup change', function () {
            oTableSite.search($(this).val()).draw();
        });

        let cntSite;
        let btnSiteOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntSite = 1;
                        }
                        if (column === 4) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntSite++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableSite, {
            buttons: [
                $.extend( true, {}, btnSiteOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Site List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnSiteOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Site List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnSiteOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Site List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtSteSiteExport'));

        $('#btnSteSiteAdd').on('click', function () {
            modalSiteClass.add();
        });

        $('#btnDtSteSiteRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableSte(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableSte(0);
    };

    this.genTableSte = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refSite = mzGetLocalRaw('gems_site', versionLocal, [], 'site');
        oTableSite.clear().rows.add(refSite).draw();
    };

    this.addTableSte = function (_dataAdd) {
        oTableSite.row.add(_dataAdd).draw();
    };

    this.updateTableSte = function (_dataEdit, _rowEdit) {
        const currentRow = oTableSite.row(_rowEdit).data();
        if (typeof _dataEdit['siteName'] !== 'undefined') {
            currentRow['siteName'] = _dataEdit['siteName'];
        }
        if (typeof _dataEdit['siteDesc'] !== 'undefined') {
            currentRow['siteDesc'] = _dataEdit['siteDesc'];
        }
        if (typeof _dataEdit['siteStatus'] !== 'undefined') {
            currentRow['siteStatus'] = _dataEdit['siteStatus'];
        }
        oTableSite.row(_rowEdit).data(currentRow).draw();
    };

    this.deleteTableSte = function (_rowDelete) {
        oTableSite.row(_rowDelete).remove().draw();
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

    this.setModalSiteClass = function (_modalSiteClass) {
        modalSiteClass = _modalSiteClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}