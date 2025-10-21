function MainDesignation() {

    const className = 'MainDesignation';
    const momentAvailable = (typeof moment === 'function');
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let oTableDesignation;
    let modalDesignationClass;
    let lastUpdated = null;
    let statusFilterFn;

    function formatTimestamp(value) {
        if (!value) {
            return 'Updated —';
        }
        if (momentAvailable) {
            return 'Updated ' + moment(value).format('DD MMM YYYY, hh:mm A');
        }
        return 'Updated ' + new Date(value).toLocaleString();
    }

    function updateMetrics() {
        if (!oTableDesignation) {
            return;
        }
        const data = oTableDesignation.rows({search: 'applied'}).data();
        let total = 0;
        let active = 0;
        let inactive = 0;
        for (let i = 0; i < data.length; i++) {
            const row = data[i];
            if (!row) {
                continue;
            }
            total++;
            if (row['designationStatus'] === '1') {
                active++;
            } else {
                inactive++;
            }
        }
        $('#metricDsgTotal').text(total.toLocaleString());
        $('#metricDsgActive').text(active.toLocaleString());
        $('#metricDsgInactive').text(inactive.toLocaleString());
    }

    function updateSummary() {
        if (!oTableDesignation) {
            return;
        }
        const info = (oTableDesignation && typeof oTableDesignation.page === 'function' && typeof oTableDesignation.page.info === 'function')
            ? oTableDesignation.page.info()
            : null;
        if (info) {
            $('#lblDsgDesignationCount').text('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal + ' records');
        }
        $('#lblDsgDesignationUpdated').text(formatTimestamp(lastUpdated));
    }

    function updateFilterSummary() {
        const statusVal = $('#optDsgDesignationStatus').val();
        let label = 'Status: All';
        if (statusVal && statusVal !== 'all' && refStatus && refStatus[statusVal]) {
            label = 'Status: ' + refStatus[statusVal]['statusDesc'];
        }
        $('#lblDsgDesignationFilter').text(label);
    }

    function buildStatusOptions() {
        const $select = $('#optDsgDesignationStatus');
        if (!$select.length) {
            return;
        }
        const currentValue = $select.val() || 'all';
        $select.find('option:not([value="all"])').remove();
        if (refStatus) {
            Object.keys(refStatus).forEach(function (key) {
                const status = refStatus[key];
                if (!status) {
                    return;
                }
                $select.append('<option value="' + key + '">' + status['statusDesc'] + '</option>');
            });
        }
        $select.val(currentValue);
    }

    this.init = function () {
        oTableDesignation =  $('#dtDsgDesignation').DataTable({
            bLengthChange: false,
            bFilter: false,
            "aaSorting": [1, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = (oTableDesignation && oTableDesignation.page && typeof oTableDesignation.page.info === 'function')
                    ? oTableDesignation.page.info()
                    : null;
                const rowNumber = info ? (info.page * info.length + (iDisplayIndex + 1)) : (iDisplayIndex + 1);
                $('td', nRow).eq(0).html(rowNumber).attr('data-label', '#');
                $('td', nRow).eq(1).attr('data-label', 'Designation');
                $('td', nRow).eq(2).attr('data-label', 'Status');
                $('td', nRow).eq(3).attr('data-label', 'Actions');
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkDsgDesignationEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableDesignation.row(parseInt(rowId)).data();
                        modalDesignationClass.edit(currentRow['designationId'], rowId);
                    }
                });
                $('.lnkDsgDesignationDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableDesignation.row(parseInt(rowId)).data();
                        modalDesignationClass.deactivate(currentRow['designationId'], rowId);
                    }
                });
                $('.lnkDsgDesignationActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableDesignation.row(parseInt(rowId)).data();
                        modalDesignationClass.activate(currentRow['designationId'], rowId);
                    }
                });
                $('.lnkDsgDesignationDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableDesignation.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['designationId'], modalDesignationClass);
                    }
                });
                updateMetrics();
                updateSummary();
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'designationDesc'},
                {mData: null,
                    mRender: function (data, type, row) {
                        return '<h6><span class="badge badge-pill ' + refStatus[row['designationStatus']]['statusColor'] + ' z-depth-2">' + refStatus[row['designationStatus']]['statusDesc'] + '</span></h6>';
                    }
                },
                {mData: null, bSortable: false, sClass: 'text-center',
                    mRender: function (data, type, row, meta) {
                        let label = '<a><i class="fas fa-edit lnkDsgDesignationEdit" id="lnkDsgDesignationEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                        if (row['designationStatus'] === '1') {
                            label += '<a><i class="fas fa-toggle-off lnkDsgDesignationDeactivate" id="lnkDsgDesignationDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>&nbsp;&nbsp;';
                        } else {
                            label += '<a><i class="fas fa-toggle-on lnkDsgDesignationActivate" id="lnkDsgDesignationActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>&nbsp;&nbsp;';
                        }
                        label += '<a><i class="fas fa-trash-alt lnkDsgDesignationDelete" id="lnkDsgDesignationDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                        return label;
                    }
                },
                {mData: 'designationId', visible: false}
            ]
        });
        $('#dtDsgDesignation_filter').hide();

        statusFilterFn = function (settings, data, dataIndex) {
            if (!settings.nTable || settings.nTable.id !== 'dtDsgDesignation') {
                return true;
            }
            const statusVal = $('#optDsgDesignationStatus').val();
            if (!statusVal || statusVal === 'all') {
                return true;
            }
            const rowData = oTableDesignation.row(dataIndex).data();
            return rowData && rowData['designationStatus'] === statusVal;
        };
        $.fn.dataTable.ext.search.push(statusFilterFn);
        $('#txtDsgDesignationSearch').on('keyup change', function () {
            oTableDesignation.search($(this).val()).draw();
        });

        let cntDesignation;
        let btnDesignationOpt = {
            exportOptions: {
                columns: [ 0, 1, 2],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntDesignation = 1;
                        }
                        if (column === 2) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntDesignation++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableDesignation, {
            buttons: [
                $.extend( true, {}, btnDesignationOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Designation List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-primary btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnDesignationOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Designation List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-primary btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnDesignationOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Designation List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-primary btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtDsgDesignationExport'));

        $('#btnDsgDesignationAdd').on('click', function () {
            modalDesignationClass.add();
        });

        buildStatusOptions();
        updateFilterSummary();

        $('#optDsgDesignationStatus').on('change', function () {
            oTableDesignation.draw();
            updateFilterSummary();
        });

        $('#btnDtDsgDesignationRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableDsg(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableDsg(0);
    };

    this.genTableDsg = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refDesignation = mzGetLocalRaw('gems_designation', versionLocal, [], 'designation');
        lastUpdated = new Date();
        oTableDesignation.clear().rows.add(refDesignation).draw();
    };

    this.addTableDsg = function (_dataAdd) {
        lastUpdated = new Date();
        oTableDesignation.row.add(_dataAdd).draw();
    };

    this.updateTableDsg = function (_dataEdit, _rowEdit) {
        const currentRow = oTableDesignation.row(_rowEdit).data();
        if (typeof _dataEdit['designationDesc'] !== 'undefined') {
            currentRow['designationDesc'] = _dataEdit['designationDesc'];
        }
        if (typeof _dataEdit['designationStatus'] !== 'undefined') {
            currentRow['designationStatus'] = _dataEdit['designationStatus'];
        }
        lastUpdated = new Date();
        oTableDesignation.row(_rowEdit).data(currentRow).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setVersionLocal = function (_versionLocal) {
        versionLocal = _versionLocal;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
        buildStatusOptions();
        updateFilterSummary();
        if (oTableDesignation) {
            oTableDesignation.rows().invalidate();
            oTableDesignation.draw(false);
        }
    };

    this.setModalDesignationClass = function (_modalDesignationClass) {
        modalDesignationClass = _modalDesignationClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}