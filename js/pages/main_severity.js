function MainSeverity() {

    const className = 'MainSeverity';
    const momentAvailable = (typeof moment === 'function');
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let oTableSeverity;
    let modalSeverityClass;
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
        if (!oTableSeverity) {
            return;
        }
        const data = oTableSeverity.rows({search: 'applied'}).data();
        let total = 0;
        let active = 0;
        let inactive = 0;
        for (let i = 0; i < data.length; i++) {
            const row = data[i];
            if (!row) {
                continue;
            }
            total++;
            if (row['severityStatus'] === '1') {
                active++;
            } else {
                inactive++;
            }
        }
        $('#metricSvrTotal').text(total.toLocaleString());
        $('#metricSvrActive').text(active.toLocaleString());
        $('#metricSvrInactive').text(inactive.toLocaleString());
    }

    function updateSummary() {
        if (!oTableSeverity) {
            return;
        }
    const info = (oTableSeverity && typeof oTableSeverity.page === 'function' && typeof oTableSeverity.page.info === 'function')
            ? oTableSeverity.page.info()
            : null;
        if (info) {
            $('#lblSvrSeverityCount').text('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal + ' records');
        }
        $('#lblSvrSeverityUpdated').text(formatTimestamp(lastUpdated));
    }

    function updateFilterSummary() {
        const statusVal = $('#optSvrSeverityStatus').val();
        let label = 'Status: All';
        if (statusVal && statusVal !== 'all' && refStatus && refStatus[statusVal]) {
            label = 'Status: ' + refStatus[statusVal]['statusDesc'];
        }
        $('#lblSvrSeverityFilter').text(label);
    }

    function buildStatusOptions() {
        const $select = $('#optSvrSeverityStatus');
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
        oTableSeverity =  $('#dtSvrSeverity').DataTable({
            bLengthChange: false,
            bFilter: false,
            "aaSorting": [1, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = (oTableSeverity && oTableSeverity.page && typeof oTableSeverity.page.info === 'function')
                    ? oTableSeverity.page.info()
                    : null;
                const rowNumber = info ? (info.page * info.length + (iDisplayIndex + 1)) : (iDisplayIndex + 1);
                $('td', nRow).eq(0).html(rowNumber).attr('data-label', '#');
                $('td', nRow).eq(1).attr('data-label', 'Severity');
                $('td', nRow).eq(2).attr('data-label', 'Status');
                $('td', nRow).eq(3).attr('data-label', 'Actions');
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
                updateMetrics();
                updateSummary();
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'severityName'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill ' + refStatus[row['severityStatus']]['statusColor'] + ' z-depth-2">' + refStatus[row['severityStatus']]['statusDesc'] + '</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<div class="action-btn-group">';
                            label += '<button type="button" class="btn-action btn-edit lnkSvrSeverityEdit" id="lnkSvrSeverityEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"><i class="fas fa-edit"></i></button>';
                            if (row['severityStatus'] === '1') {
                                label += '<button type="button" class="btn-action btn-deactivate lnkSvrSeverityDeactivate" id="lnkSvrSeverityDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"><i class="fas fa-toggle-off"></i></button>';
                            } else {
                                label += '<button type="button" class="btn-action btn-activate lnkSvrSeverityActivate" id="lnkSvrSeverityActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Activate"><i class="fas fa-toggle-on"></i></button>';
                            }
                            label += '<button type="button" class="btn-action btn-delete lnkSvrSeverityDelete" id="lnkSvrSeverityDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"><i class="fas fa-trash-alt"></i></button>';
                            label += '</div>';
                            return label;
                        }
                    },
                    {mData: 'severityId', visible: false}
                ]
        });
        $('#dtSvrSeverity_filter').hide();

        statusFilterFn = function (settings, data, dataIndex) {
            if (!settings.nTable || settings.nTable.id !== 'dtSvrSeverity') {
                return true;
            }
            const statusVal = $('#optSvrSeverityStatus').val();
            if (!statusVal || statusVal === 'all') {
                return true;
            }
            const rowData = oTableSeverity.row(dataIndex).data();
            return rowData && rowData['severityStatus'] === statusVal;
        };
        $.fn.dataTable.ext.search.push(statusFilterFn);

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
                    className: 'btn btn-outline-primary btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnSeverityOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Severity List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-primary btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnSeverityOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Severity List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-primary btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtSvrSeverityExport'));

        $('#btnSvrSeverityAdd').on('click', function () {
            modalSeverityClass.add();
        });

        buildStatusOptions();
        updateFilterSummary();

        $('#optSvrSeverityStatus').on('change', function () {
            oTableSeverity.draw();
            updateFilterSummary();
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
        const refSeverity = mzGetLocalRaw('gems_severity', versionLocal, [], 'severity');
        lastUpdated = new Date();
        oTableSeverity.clear().rows.add(refSeverity).draw();
    };

    this.addTableSvr = function (_dataAdd) {
        lastUpdated = new Date();
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
        lastUpdated = new Date();
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
        buildStatusOptions();
        updateFilterSummary();
        if (oTableSeverity) {
            oTableSeverity.rows().invalidate();
            oTableSeverity.draw(false);
        }
    };

    this.setModalSeverityClass = function (_modalSeverityClass) {
        modalSeverityClass = _modalSeverityClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}